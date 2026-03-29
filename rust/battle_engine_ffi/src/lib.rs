//! # Battle Engine FFI
//!
//! `battle_engine_ffi` is the Rust implementation of the OGameX battle engine.
//!
//! This Rust library is called from the PHP client RustBattleEngine.php via FFI (Foreign Function Interface)
//! and takes the battle input in JSON, processes the battle rounds and returns the battle output in JSON.
//!
//! This battle engine is functionally equivalent to the OGameX PHP battle engine but is optimized
//! for performance and memory usage. It is up to 200x faster than the equivalent PHP implementation
//! and uses up to 10x less memory.
//!
//! # Multi-Attacker Support
//! This engine supports multiple attacker fleets (ACS Attack) and multiple defender fleets (ACS Defend).
//! Each fleet's units are tracked with their fleet_mission_id and owner_id, allowing for accurate
//! per-fleet result reporting.
//!
//! # Stacking Architecture
//! Ships of the same type in the same fleet are grouped into a single BattleUnitStack with a count.
//! Memory complexity is O(ship_types) instead of O(ship_count), making billion-ship battles feasible.
//! Damage is calculated statistically per stack using per-unit averages and expected-value rapidfire.
use serde::{Deserialize, Serialize};
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use rand::Rng;
use std::collections::HashMap;
use memory_stats::memory_stats;

/// Battle input which is provided by the PHP client.
#[derive(Serialize, Deserialize)]
pub struct BattleInput {
    attacker_fleets: Vec<AttackerFleetInput>,
    defender_fleets: Vec<DefenderFleetInput>,
}

/// Input structure for a single attacker fleet.
#[derive(Serialize, Deserialize, Clone)]
struct AttackerFleetInput {
    fleet_mission_id: u32,
    owner_id: u32,
    units: HashMap<i16, BattleUnitInfo>,
}

/// Input structure for a single defender fleet.
#[derive(Serialize, Deserialize, Clone)]
struct DefenderFleetInput {
    fleet_mission_id: u32,
    owner_id: u32,
    units: HashMap<i16, BattleUnitInfo>,
}

/// Battle unit info which is provided by the PHP client.
///
/// This contains static information about the input units and their amount.
#[derive(Serialize, Deserialize, Clone)]
struct BattleUnitInfo {
    unit_id: i16,
    amount: u32,
    attack_power: f32,
    shield_points: f32,
    hull_plating: f32,
    rapidfire: HashMap<i16, u16>,
}

/// Battle unit count to keep track of the amount of units of a certain type.
/// Uses u64 to support billions of ships without overflow.
#[derive(Serialize, Deserialize, Clone)]
struct BattleUnitCount {
    unit_id: i16,
    amount: u64,
}

/// Battle unit stack — groups all ships of the same type in the same fleet into one entry.
///
/// This replaces the old BattleUnitInstance approach (one object per ship) with a stacked
/// model where all N ships of a type share a single struct with per-unit average stats.
/// Memory usage is O(ship_types) regardless of ship count.
#[derive(Clone)]
struct BattleUnitStack {
    unit_id: i16,
    fleet_mission_id: u32,
    /// Number of ships currently alive in this stack.
    count: u64,
    /// Per-unit attack power (constant, from metadata).
    attack_power: f32,
    /// Per-unit maximum shield points — used for shield regeneration after each round.
    max_shield: f32,
    /// Rapidfire table for this ship type.
    rapidfire: HashMap<i16, u16>,
    /// Current per-unit shield (average across the stack). Resets to max_shield after each round.
    current_shield_per_unit: f32,
    /// Current per-unit hull (average across the stack). Persists across rounds until ship dies.
    current_hull_per_unit: f32,
}

/// Battle round which is used to keep track of the battle statistics for a single round.
#[derive(Serialize, Deserialize)]
struct BattleRound {
    /// The units of the attacker remaining at the end of the round.
    attacker_ships: HashMap<i16, BattleUnitCount>,
    /// The units of the defender remaining at the end of the round.
    defender_ships: HashMap<i16, BattleUnitCount>,
    /// Unit losses of the attacker until now which includes previous rounds.
    attacker_losses: HashMap<i16, BattleUnitCount>,
    /// Unit losses of the defender until now which includes previous rounds.
    defender_losses: HashMap<i16, BattleUnitCount>,
    /// Unit losses of the attacker in this round.
    attacker_losses_in_round: HashMap<i16, BattleUnitCount>,
    /// Unit losses of the defender in this round.
    defender_losses_in_round: HashMap<i16, BattleUnitCount>,
    /// Total amount of damage absorbed by the attacker this round.
    absorbed_damage_attacker: f64,
    /// Total amount of damage absorbed by the defender this round.
    absorbed_damage_defender: f64,
    /// Total amount of full strength of the attacker at the start of the round.
    full_strength_attacker: f64,
    /// Total amount of full strength of the defender at the start of the round.
    full_strength_defender: f64,
    /// Total amount of hits the attacker made this round.
    hits_attacker: u64,
    /// Total amount of hits the defender made this round.
    hits_defender: u64,
    /// Per-fleet attacker results keyed by fleet_mission_id.
    attacker_fleet_results: HashMap<u32, AttackerFleetResult>,
    /// Per-fleet defender results keyed by fleet_mission_id.
    defender_fleet_results: HashMap<u32, DefenderFleetResult>,
}

/// Result for a single attacker fleet.
#[derive(Serialize, Deserialize, Clone)]
struct AttackerFleetResult {
    fleet_mission_id: u32,
    owner_id: u32,
    units_start: HashMap<i16, BattleUnitCount>,
    units_result: HashMap<i16, BattleUnitCount>,
    units_lost: HashMap<i16, BattleUnitCount>,
}

/// Result for a single defender fleet.
#[derive(Serialize, Deserialize, Clone)]
struct DefenderFleetResult {
    fleet_mission_id: u32,
    owner_id: u32,
    units_start: HashMap<i16, BattleUnitCount>,
    units_result: HashMap<i16, BattleUnitCount>,
    units_lost: HashMap<i16, BattleUnitCount>,
}

/// Memory metrics which is used to keep track of the peak memory usage during the battle.
#[derive(Serialize, Deserialize)]
struct MemoryMetrics {
    peak_memory: u64, // in kilobytes
}

/// Battle output which is returned to the PHP client.
#[derive(Serialize, Deserialize)]
pub struct BattleOutput {
    rounds: Vec<BattleRound>,
    memory_metrics: MemoryMetrics,
}

/// FFI interface to process the battle rounds and return the battle output.
#[no_mangle]
pub extern "C" fn fight_battle_rounds(input_json: *const c_char) -> *mut c_char {
    let input_str = unsafe { CStr::from_ptr(input_json).to_str().unwrap() };
    let battle_input: BattleInput = serde_json::from_str(input_str).unwrap();
    let battle_output = process_battle_rounds(battle_input);
    let result_json = serde_json::to_string(&battle_output).unwrap();
    let c_str = CString::new(result_json).unwrap();
    c_str.into_raw()
}

/// Process the battle rounds and return the battle output.
fn process_battle_rounds(input: BattleInput) -> BattleOutput {
    let mut peak_memory = 0;
    let mut rounds = Vec::new();

    // Build fleet metadata maps for ownership tracking.
    let mut attacker_fleet_metadata: HashMap<u32, HashMap<i16, BattleUnitInfo>> = HashMap::new();
    let mut attacker_fleet_owners: HashMap<u32, u32> = HashMap::new();
    for fleet in &input.attacker_fleets {
        attacker_fleet_metadata.insert(fleet.fleet_mission_id, fleet.units.clone());
        attacker_fleet_owners.insert(fleet.fleet_mission_id, fleet.owner_id);
    }

    let mut defender_fleet_metadata: HashMap<u32, HashMap<i16, BattleUnitInfo>> = HashMap::new();
    let mut defender_fleet_owners: HashMap<u32, u32> = HashMap::new();
    for fleet in &input.defender_fleets {
        defender_fleet_metadata.insert(fleet.fleet_mission_id, fleet.units.clone());
        defender_fleet_owners.insert(fleet.fleet_mission_id, fleet.owner_id);
    }

    // Build stacks: one entry per (ship_type, fleet) instead of one entry per ship.
    let mut attacker_stacks = build_stacks(&input.attacker_fleets);
    let mut defender_stacks = build_stacks(&input.defender_fleets);

    update_peak_memory(&mut peak_memory);

    // Fight up to 6 rounds.
    for _ in 0..6 {
        let attacker_alive: u64 = attacker_stacks.iter().map(|s| s.count).sum();
        let defender_alive: u64 = defender_stacks.iter().map(|s| s.count).sum();
        if attacker_alive == 0 || defender_alive == 0 {
            break;
        }

        let mut round = BattleRound {
            attacker_ships: HashMap::new(),
            defender_ships: HashMap::new(),
            attacker_losses: HashMap::new(),
            defender_losses: HashMap::new(),
            attacker_losses_in_round: HashMap::new(),
            defender_losses_in_round: HashMap::new(),
            absorbed_damage_attacker: 0.0,
            absorbed_damage_defender: 0.0,
            full_strength_attacker: 0.0,
            full_strength_defender: 0.0,
            hits_attacker: 0,
            hits_defender: 0,
            attacker_fleet_results: HashMap::new(),
            defender_fleet_results: HashMap::new(),
        };

        // Build merged metadata for combat lookups.
        let mut attacker_units_metadata: HashMap<i16, BattleUnitInfo> = HashMap::new();
        for fleet_units in attacker_fleet_metadata.values() {
            for (unit_id, unit_info) in fleet_units {
                attacker_units_metadata.insert(*unit_id, unit_info.clone());
            }
        }
        let mut defender_units_metadata: HashMap<i16, BattleUnitInfo> = HashMap::new();
        for fleet_units in defender_fleet_metadata.values() {
            for (unit_id, unit_info) in fleet_units {
                defender_units_metadata.insert(*unit_id, unit_info.clone());
            }
        }

        // Attackers fire at defenders.
        process_combat(&attacker_stacks, &mut defender_stacks, &mut round, &defender_units_metadata, true);
        // Defenders fire at attackers.
        process_combat(&defender_stacks, &mut attacker_stacks, &mut round, &attacker_units_metadata, false);

        // Remove dead stacks and regenerate shields.
        cleanup_round(&mut round, &mut attacker_stacks, &mut defender_stacks);

        // Update round statistics.
        round.attacker_ships = compress_stacks(&attacker_stacks);
        round.defender_ships = compress_stacks(&defender_stacks);

        calculate_losses(&mut round, &attacker_units_metadata, &defender_units_metadata);
        calculate_fleet_results(&mut round, &attacker_stacks, &defender_stacks, &attacker_fleet_metadata, &defender_fleet_metadata, &attacker_fleet_owners, &defender_fleet_owners);

        rounds.push(round);
        update_peak_memory(&mut peak_memory);
    }

    BattleOutput {
        rounds,
        memory_metrics: MemoryMetrics { peak_memory },
    }
}

/// Build stacks from fleet inputs: one BattleUnitStack per (ship_type, fleet).
/// Memory: O(ship_types * fleets) — independent of ship count.
fn build_stacks(fleets: &Vec<impl FleetInput>) -> Vec<BattleUnitStack> {
    let mut stacks = Vec::new();
    for fleet in fleets {
        for (_, unit) in fleet.get_units() {
            if unit.amount == 0 {
                continue;
            }
            stacks.push(BattleUnitStack {
                unit_id: unit.unit_id,
                fleet_mission_id: fleet.get_fleet_mission_id(),
                count: unit.amount as u64,
                attack_power: unit.attack_power,
                max_shield: unit.shield_points,
                rapidfire: unit.rapidfire.clone(),
                current_shield_per_unit: unit.shield_points,
                current_hull_per_unit: unit.hull_plating,
            });
        }
    }
    stacks
}

/// Trait for fleet input structures.
trait FleetInput {
    fn get_fleet_mission_id(&self) -> u32;
    fn get_units(&self) -> &HashMap<i16, BattleUnitInfo>;
}

impl FleetInput for AttackerFleetInput {
    fn get_fleet_mission_id(&self) -> u32 { self.fleet_mission_id }
    fn get_units(&self) -> &HashMap<i16, BattleUnitInfo> { &self.units }
}

impl FleetInput for DefenderFleetInput {
    fn get_fleet_mission_id(&self) -> u32 { self.fleet_mission_id }
    fn get_units(&self) -> &HashMap<i16, BattleUnitInfo> { &self.units }
}

/// Compress stacks into a unit count map for round statistics.
fn compress_stacks(stacks: &[BattleUnitStack]) -> HashMap<i16, BattleUnitCount> {
    let mut counts: HashMap<i16, u64> = HashMap::new();
    for stack in stacks {
        if stack.count > 0 {
            *counts.entry(stack.unit_id).or_insert(0) += stack.count;
        }
    }
    counts.into_iter()
        .map(|(unit_id, amount)| (unit_id, BattleUnitCount { unit_id, amount }))
        .collect()
}

/// Compress stacks into per-fleet results.
fn compress_fleet_results_stacked(
    stacks: &[BattleUnitStack],
    fleet_mission_id: u32,
    initial_units: &HashMap<i16, BattleUnitInfo>,
) -> (HashMap<i16, BattleUnitCount>, HashMap<i16, BattleUnitCount>, HashMap<i16, BattleUnitCount>) {
    // Count survivors by unit type for this fleet.
    let mut units_result: HashMap<i16, BattleUnitCount> = HashMap::new();
    for stack in stacks.iter().filter(|s| s.fleet_mission_id == fleet_mission_id && s.count > 0) {
        increment_battle_unit_count_amount(&mut units_result, stack.unit_id, stack.count);
    }

    // Build units_start from initial metadata.
    let units_start: HashMap<i16, BattleUnitCount> = initial_units.iter()
        .map(|(unit_id, info)| (*unit_id, BattleUnitCount { unit_id: *unit_id, amount: info.amount as u64 }))
        .collect();

    // Calculate losses.
    let mut units_lost: HashMap<i16, BattleUnitCount> = HashMap::new();
    for (unit_id, start_unit) in &units_start {
        let result_amount = units_result.get(unit_id).map(|u| u.amount).unwrap_or(0);
        if start_unit.amount > result_amount {
            units_lost.insert(*unit_id, BattleUnitCount {
                unit_id: *unit_id,
                amount: start_unit.amount - result_amount,
            });
        }
    }

    (units_start, units_result, units_lost)
}

/// Simulates combat for a single phase between two groups of stacks.
///
/// # Stacking model
/// Instead of iterating over individual ships, this function:
/// 1. For each attacker stack, calculates total expected shots (base + rapidfire chains via
///    geometric-series expected value).
/// 2. Distributes those shots across defender stacks proportionally to their count.
/// 3. Applies batch damage using per-unit averages (uniform distribution assumption).
/// 4. Calculates deaths statistically: deterministic for complete destruction, binomial
///    approximation for partial explosions at < 70% hull.
///
/// This gives O(attacker_types × defender_types) complexity per phase instead of O(ships).
fn process_combat(
    attackers: &[BattleUnitStack],
    defenders: &mut Vec<BattleUnitStack>,
    round: &mut BattleRound,
    defender_unit_metadata: &HashMap<i16, BattleUnitInfo>,
    is_attacker: bool,
) {
    let mut rng = rand::thread_rng();

    // Total alive defenders (used for shot distribution weights).
    let total_defender_count: u64 = defenders.iter().map(|d| d.count).sum();
    if total_defender_count == 0 {
        return;
    }

    for attacker in attackers.iter() {
        if attacker.count == 0 {
            continue;
        }

        let damage = attacker.attack_power;

        // --- Calculate expected total shots from this stack ---
        //
        // For a single ship, expected shots = 1 + Σ_D [ P(hit D) * rf_chain(D) ]
        // where P(hit D) = D.count / total_alive
        // and rf_chain(D) = rf_chance / (1 - rf_chance)  [geometric series]
        //
        // Scale by attacker.count for the full stack.
        let current_total: u64 = defenders.iter().map(|d| d.count).sum();
        if current_total == 0 {
            break;
        }

        let mut expected_shots_per_ship: f64 = 1.0;
        for def in defenders.iter() {
            if def.count == 0 {
                continue;
            }
            if let Some(&rf_amount) = attacker.rapidfire.get(&def.unit_id) {
                let p_hit = def.count as f64 / current_total as f64;
                // rf_chance = 1 - (100 / rf_amount) / 100
                let rf_chance = 1.0 - (1.0 / rf_amount as f64);
                // E[extra shots from one chain] = rf_chance / (1 - rf_chance)
                expected_shots_per_ship += p_hit * rf_chance / (1.0 - rf_chance);
            }
        }

        let total_shots = (attacker.count as f64 * expected_shots_per_ship).ceil() as u64;

        // --- Distribute shots across defender stacks and apply damage ---
        for def in defenders.iter_mut() {
            if def.count == 0 {
                continue;
            }

            let def_metadata = match defender_unit_metadata.get(&def.unit_id) {
                Some(m) => m,
                None => continue,
            };

            // OGame 1% shield rule: if damage < 1% of max shield, attack is negated.
            if damage < 0.01 * def_metadata.shield_points {
                continue;
            }

            // Fraction of shots directed at this stack (proportional to count).
            let weight = def.count as f64 / current_total as f64;
            let shots_on_stack = ((total_shots as f64 * weight).ceil() as u64).max(1);

            // --- Batch damage calculation (uniform distribution model) ---
            //
            // Each of the shots_on_stack shots hits one ship at random.
            // shots_per_unit = average shots received per ship in this stack.
            // For large stacks this converges to the expected value.
            let shots_per_unit = shots_on_stack as f64 / def.count as f64;
            let raw_damage_per_unit = damage as f64 * shots_per_unit;

            // Shield absorption per unit: clamp to current shield.
            let shield_abs_per_unit = raw_damage_per_unit.min(def.current_shield_per_unit as f64);
            let hull_dmg_per_unit = raw_damage_per_unit - shield_abs_per_unit;

            def.current_shield_per_unit = (def.current_shield_per_unit as f64 - shield_abs_per_unit).max(0.0) as f32;
            def.current_hull_per_unit -= hull_dmg_per_unit as f32;

            // --- Death calculation ---
            let deaths: u64 = if def.current_hull_per_unit <= 0.0 {
                // All ships in the stack are destroyed.
                def.count
            } else {
                let hull_ratio = def.current_hull_per_unit / def_metadata.hull_plating;
                if hull_ratio < 0.7 {
                    // Explosion chance matches original formula:
                    // chance = 100 - (hull_ratio * 100)  → as probability: 1 - hull_ratio
                    let explosion_prob = (1.0 - hull_ratio as f64).clamp(0.0, 1.0);
                    // Expected deaths with binomial variance for realism.
                    let expected = def.count as f64 * explosion_prob;
                    let std_dev = (def.count as f64 * explosion_prob * (1.0 - explosion_prob)).sqrt();
                    let noise: f64 = rng.gen_range(-1.0..=1.0);
                    let raw = (expected + std_dev * noise).round() as i64;
                    raw.clamp(0, def.count as i64) as u64
                } else {
                    0
                }
            };

            if deaths > 0 {
                let losses_map = if is_attacker {
                    &mut round.defender_losses_in_round
                } else {
                    &mut round.attacker_losses_in_round
                };
                increment_battle_unit_count_amount(losses_map, def.unit_id, deaths);
                def.count -= deaths;
                // If all ships died, hull is set to 0 for consistency.
                if def.count == 0 {
                    def.current_hull_per_unit = 0.0;
                }
            }

            // --- Round statistics ---
            let total_dmg = raw_damage_per_unit * def.count as f64; // approximate (uses surviving count)
            let total_shield_abs = shield_abs_per_unit * def.count as f64;

            if is_attacker {
                round.hits_attacker += shots_on_stack;
                round.full_strength_attacker += total_dmg;
                round.absorbed_damage_defender += total_shield_abs;
            } else {
                round.hits_defender += shots_on_stack;
                round.full_strength_defender += total_dmg;
                round.absorbed_damage_attacker += total_shield_abs;
            }
        }
    }
}

/// Clean up after all units have attacked: remove dead stacks and regenerate shields.
fn cleanup_round(
    round: &mut BattleRound,
    attackers: &mut Vec<BattleUnitStack>,
    defenders: &mut Vec<BattleUnitStack>,
) {
    // Remove dead attacker stacks and regenerate shields on survivors.
    attackers.retain_mut(|stack| {
        if stack.count == 0 {
            return false;
        }
        // Regenerate shields to full using the stored max_shield value.
        stack.current_shield_per_unit = stack.max_shield;
        true
    });

    // Remove dead defender stacks and regenerate shields on survivors.
    defenders.retain_mut(|stack| {
        if stack.count == 0 {
            return false;
        }
        stack.current_shield_per_unit = stack.max_shield;
        true
    });

    // Note: losses_in_round was already populated during process_combat.
    let _ = round;
}

/// Calculate accumulated losses by comparing current counts with initial metadata.
fn calculate_losses(
    round: &mut BattleRound,
    initial_attacker: &HashMap<i16, BattleUnitInfo>,
    initial_defender: &HashMap<i16, BattleUnitInfo>,
) {
    for (_, unit) in initial_attacker {
        let initial_count = unit.amount as u64;
        let current_count = round.attacker_ships.get(&unit.unit_id).map(|u| u.amount).unwrap_or(0);
        if current_count < initial_count {
            increment_battle_unit_count_amount(&mut round.attacker_losses, unit.unit_id, initial_count - current_count);
        }
    }
    for (_, unit) in initial_defender {
        let initial_count = unit.amount as u64;
        let current_count = round.defender_ships.get(&unit.unit_id).map(|u| u.amount).unwrap_or(0);
        if current_count < initial_count {
            increment_battle_unit_count_amount(&mut round.defender_losses, unit.unit_id, initial_count - current_count);
        }
    }
}

/// Calculate per-fleet results for attackers and defenders.
fn calculate_fleet_results(
    round: &mut BattleRound,
    attacker_stacks: &[BattleUnitStack],
    defender_stacks: &[BattleUnitStack],
    attacker_fleets: &HashMap<u32, HashMap<i16, BattleUnitInfo>>,
    defender_fleets: &HashMap<u32, HashMap<i16, BattleUnitInfo>>,
    attacker_fleet_owners: &HashMap<u32, u32>,
    defender_fleet_owners: &HashMap<u32, u32>,
) {
    for (&fleet_mission_id, initial_units) in attacker_fleets {
        let owner_id = *attacker_fleet_owners.get(&fleet_mission_id).unwrap_or(&0);
        let (units_start, units_result, units_lost) =
            compress_fleet_results_stacked(attacker_stacks, fleet_mission_id, initial_units);
        round.attacker_fleet_results.insert(fleet_mission_id, AttackerFleetResult {
            fleet_mission_id, owner_id, units_start, units_result, units_lost,
        });
    }

    for (&fleet_mission_id, initial_units) in defender_fleets {
        let owner_id = *defender_fleet_owners.get(&fleet_mission_id).unwrap_or(&0);
        let (units_start, units_result, units_lost) =
            compress_fleet_results_stacked(defender_stacks, fleet_mission_id, initial_units);
        round.defender_fleet_results.insert(fleet_mission_id, DefenderFleetResult {
            fleet_mission_id, owner_id, units_start, units_result, units_lost,
        });
    }
}

/// Helper: increment the amount of a BattleUnitCount entry in a map.
fn increment_battle_unit_count_amount(map: &mut HashMap<i16, BattleUnitCount>, unit_id: i16, amount: u64) {
    let entry = map.entry(unit_id).or_insert(BattleUnitCount { unit_id, amount: 0 });
    entry.amount += amount;
}

/// Update peak memory usage statistics (debugging only).
fn update_peak_memory(current_peak: &mut u64) {
    if let Some(usage) = memory_stats() {
        *current_peak = (*current_peak).max(usage.physical_mem as u64 / 1024);
    }
}
