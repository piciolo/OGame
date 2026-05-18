<?php

namespace OGame\Services;

use Throwable;
use OGame\Models\Achievement;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Highscore;

/**
 * Calcola il valore di progresso corrente per ogni achievement
 * partendo dallo stato attuale del giocatore (planets, tech, ships, ecc.)
 * e lo applica via AchievementService::setProgress() che a sua volta
 * sblocca le ricompense quando i tier sono raggiunti.
 *
 * Approccio "stateless recompute": invece di registrare ogni evento,
 * deriviamo il progresso dallo stato corrente. Funziona per tutti gli
 * achievement basati su livelli/quantita correnti, NON per i counter
 * cumulativi (es. "battaglie vinte", "spedizioni completate") che
 * richiederebbero event listener dedicati.
 */
class AchievementProgressTracker
{
    public function __construct(
        private AchievementService $achievementService,
    ) {}

    /**
     * Ricalcola il progresso di TUTTI gli achievement supportati per il giocatore.
     */
    public function recomputeForPlayer(PlayerService $player): void
    {
        $values = $this->computeAllValues($player);

        $achievements = Achievement::with('tiers')->where('is_secret', false)->get();
        foreach ($achievements as $achievement) {
            if (!array_key_exists($achievement->machine_name, $values)) {
                continue; // achievement non ancora supportato (event-based)
            }
            $newValue = (int) $values[$achievement->machine_name];
            $this->achievementService->setProgress($player, $achievement, $newValue);
        }
    }

    /**
     * @return array<string, int>
     */
    public function computeAllValues(PlayerService $player): array
    {
        $tech = $player->getUser()->tech ?? null;
        $planets = $player->planets->all();

        $maxBuildLevels = [];
        $sumShips = [
            'light_fighter' => 0, 'heavy_fighter' => 0, 'cruiser' => 0, 'battle_ship' => 0,
            'battlecruiser' => 0, 'bomber' => 0, 'destroyer' => 0, 'deathstar' => 0,
            'small_cargo' => 0, 'large_cargo' => 0, 'colony_ship' => 0, 'recycler' => 0,
            'espionage_probe' => 0, 'solar_satellite' => 0,
        ];
        $totalBuildings = 0;
        $planetsCount = 0;
        $moonsCount = 0;
        $totalResources = 0;
        $totalMissiles = 0;

        $buildings = [
            'metal_mine', 'crystal_mine', 'deuterium_synthesizer',
            'solar_plant', 'fusion_plant', 'robot_factory', 'nano_factory',
            'shipyard', 'metal_store', 'crystal_store', 'deuterium_store',
            'research_lab', 'alliance_depot', 'missile_silo',
            'terraformer', 'jump_gate',
        ];

        foreach ($planets as $planetSvc) {
            // Conteggio pianeti vs lune.
            try {
                $type = $planetSvc->getPlanetType();
                if ($type === PlanetType::Moon) {
                    $moonsCount++;
                } else {
                    $planetsCount++;
                }
            } catch (Throwable $e) {
                $planetsCount++;
            }

            foreach ($buildings as $b) {
                try {
                    $lvl = $planetSvc->getObjectLevel($b);
                    $maxBuildLevels[$b] = max($maxBuildLevels[$b] ?? 0, $lvl);
                    $totalBuildings += $lvl;
                } catch (Throwable $e) {
                    // Building machine_name non riconosciuto → ignora.
                }
            }
            foreach (array_keys($sumShips) as $ship) {
                try {
                    $sumShips[$ship] += $planetSvc->getObjectAmount($ship);
                } catch (Throwable $e) {
                    // Ship machine_name non valido in questo build OGameX → ignora.
                }
            }
            // Risorse correnti (currently unused for any achievement metric;
            // kept available if needed in future).
            $resources = $planetSvc->getResources();
            $totalResources += (int) $resources->sum();

            // Missili (per #19 Ben equipaggiato): OGame ufficiale richiede
            // "Disponi di Missile" — contiamo sia i Missili interplanetari
            // sia gli ABM. T1-T2 chiedono per-pianeta, T3-T5 totale: usiamo
            // il totale per coerenza con il modello stateless attuale (T1-T2
            // saranno leggermente più lenti che in OGame).
            foreach (['interplanetary_missile', 'anti_ballistic_missile'] as $m) {
                try {
                    $totalMissiles += $planetSvc->getObjectAmount($m);
                } catch (Throwable $e) {
                    // Defense object non riconosciuto, ignora.
                }
            }
        }

        // Ricerca: livelli di tech.
        $researchLevels = [];
        if ($tech) {
            foreach ([
                'energy_technology', 'laser_technology', 'ion_technology', 'hyperspace_technology',
                'plasma_technology', 'combustion_drive', 'impulse_drive', 'hyperspace_drive',
                'espionage_technology', 'computer_technology', 'astrophysics',
                'intergalactic_research_network', 'graviton_technology',
                'weapon_technology', 'shielding_technology', 'armor_technology',
            ] as $r) {
                $researchLevels[$r] = (int) ($tech->{$r} ?? 0);
            }
        }

        // Highscore rank/points.
        $hs = Highscore::where('player_id', $player->getId())->first();
        $generalRank = $hs !== null ? (int) $hs->general_rank : 0;
        $generalPoints = $hs !== null ? (int) $hs->general : 0;

        // Spedizioni completate.
        $expeditionsDone = (int) FleetMission::where('user_id', $player->getId())
            ->where('mission_type', 15)
            ->where('processed', true)
            ->count();

        // Distinct ship types posseduti.
        $distinctShipTypes = count(array_filter($sumShips, fn($n) => $n > 0));

        return [
            'planet_expansion' => $planetsCount,
            'moon_walk' => $moonsCount,
            'full_house' => $totalBuildings,
            'well_equipped' => $totalMissiles,
            'magnetism' => $maxBuildLevels['metal_mine'] ?? 0,
            'crystal_magic' => $maxBuildLevels['crystal_mine'] ?? 0,
            'fusion_fan' => $maxBuildLevels['deuterium_synthesizer'] ?? 0,
            'clean_energy' => $maxBuildLevels['solar_plant'] ?? 0,
            'overload' => $maxBuildLevels['fusion_plant'] ?? 0,
            'robot_revolt' => $maxBuildLevels['robot_factory'] ?? 0,
            'lets_go' => $maxBuildLevels['shipyard'] ?? 0,
            'know_it_all' => $maxBuildLevels['research_lab'] ?? 0,
            'friend_helper' => $maxBuildLevels['alliance_depot'] ?? 0,
            'small_useful' => $maxBuildLevels['missile_silo'] ?? 0,
            'creative_streak' => $maxBuildLevels['terraformer'] ?? 0,
            'jumpgate_man' => $maxBuildLevels['jump_gate'] ?? 0,
            'specialized_electronics' => $researchLevels['ion_technology'] ?? 0,
            'bam_bam' => $researchLevels['weapon_technology'] ?? 0,
            'more_bam' => $researchLevels['shielding_technology'] ?? 0,
            'spacetime_anomaly' => $researchLevels['armor_technology'] ?? 0,
            'stirred_not_shaken' => $researchLevels['energy_technology'] ?? 0,
            'ship_tuning' => $researchLevels['combustion_drive'] ?? 0,
            'impulse_received' => $researchLevels['impulse_drive'] ?? 0,
            'faster_than_physics' => $researchLevels['hyperspace_drive'] ?? 0,
            'secret_strategist' => $researchLevels['espionage_technology'] ?? 0,
            'nerd_corp' => $researchLevels['computer_technology'] ?? 0,
            'nobel_network' => $researchLevels['intergalactic_research_network'] ?? 0,
            'energy_devourer' => $researchLevels['plasma_technology'] ?? 0,
            'scissors' => $sumShips['heavy_fighter'],
            'rock' => $sumShips['cruiser'],
            'paper' => $sumShips['battle_ship'],
            'powerful_variety' => $distinctShipTypes,
            'flora_explora' => $expeditionsDone,
            'rising_star' => $generalPoints,
            'want_to_be_best' => $generalRank > 0 ? max(0, 1000 - $generalRank) : 0,
        ];
    }
}
