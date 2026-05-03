<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OGame\Models\IpiTaskAction;
use OGame\Services\ObjectService;

/**
 * Populates the requirement_* and hint_target columns on ipi_task_actions
 * by parsing the human-readable Italian action text.
 *
 * Strategy:
 *   1. Build IT title → machine_name dictionary from ObjectService (live).
 *   2. Add static aliases for plurals/case/synonyms used by the IPI text but
 *      not exactly matching ObjectService titles (e.g. "Cargo leggeri" ≠ "Cargo Leggero").
 *   3. For each action, try the 5 regex patterns. If matched, look up the
 *      object in the dictionary and fill the columns.
 *   4. Apply manual overrides from ipi_action_overrides.json for the 23+
 *      event-type actions that no regex can infer.
 *   5. Report coverage: how many actions ended with non-NULL requirement_type.
 *
 * Run: php artisan db:seed --class=IpiActionRequirementsSeeder
 */
class IpiActionRequirementsSeeder extends Seeder
{
    /** Static aliases (lowercased IT text) → machine_name. Add here when ObjectService title differs from IPI text. */
    private const ALIASES = [
        // Plurals/case
        'miniera di cristalli'              => 'crystal_mine',
        'deposito di cristalli'             => 'crystal_store',
        'cargo leggeri'                     => 'small_cargo',
        'cargo leggero'                     => 'small_cargo',
        'cargo pesanti'                     => 'large_cargo',
        'caccia leggeri'                    => 'light_fighter',
        'caccia pesanti'                    => 'heavy_fighter',

        // Synonyms (IPI text uses different word than the official title)
        'centrale a fusione'                => 'fusion_plant',
        'propulsore a combustione'          => 'combustion_drive',
        'propulsore a impulso'              => 'impulse_drive',
        'propulsore iperspaziale'           => 'hyperspace_drive',
        'tecnologia per lo spionaggio'      => 'espionage_technology',
        'tecnologia delle corazze'          => 'armor_technology',
        'tecnologia dei laser'              => 'laser_technology',
        'fabbrica dei robot'                => 'robot_factory',

        // Compact / shorthand
        'cupola scudo'                      => 'small_shield_dome',
        'cupola-scudo'                      => 'small_shield_dome',
    ];

    /** machine_name → class_name (camelCase) lookup, populated in run(). */
    private array $classNameByMachine = [];

    /** Maps machine_name → hint_target. The DOM convention used by the project's
     * Blade views is `ipiTechnology{class_name}` (e.g. ipiTechnologymetalMine), so
     * we look up the camelCase class_name from ObjectService rather than deriving
     * it from snake_case. */
    private function defaultHintForBuilding(string $machineName): string
    {
        $className = $this->classNameByMachine[$machineName] ?? null;
        if ($className) return 'ipiTechnology' . $className;
        // Fallback: snake_case → camelCase (no upper first letter)
        $parts = explode('_', $machineName);
        $cs = array_shift($parts) . implode('', array_map('ucfirst', $parts));
        return 'ipiTechnology' . $cs;
    }

    public function run(): void
    {
        $os = app(ObjectService::class);
        app()->setLocale('it');

        // Build dictionary from live ObjectService
        $dict = [];
        $typeOf = []; // machine_name → 'building' | 'research' | 'ship' | 'defense'
        foreach ($os->getObjects() as $o) {
            $key = mb_strtolower($o->title);
            $dict[$key] = $o->machine_name;
            if (!empty($o->class_name)) {
                $this->classNameByMachine[$o->machine_name] = $o->class_name;
            }

            $cls = (new \ReflectionClass($o))->getShortName();
            $typeOf[$o->machine_name] = match ($cls) {
                'BuildingObject' => 'building',
                'StationObject' => 'building',  // stations are buildings for IPI purposes
                'ResearchObject' => 'research',
                'ShipObject', 'CivilShipObject', 'MilitaryShipObject' => 'ship',
                'DefenseObject' => 'defense',
                default => 'unknown',
            };
        }

        // Merge static aliases
        foreach (self::ALIASES as $alias => $machineName) {
            $dict[$alias] = $machineName;
        }

        // Load manual overrides
        $overrides = json_decode(
            file_get_contents(database_path('seeders/data/ipi_action_overrides.json')),
            true
        );
        if (!is_array($overrides)) {
            $this->command->error('Could not load ipi_action_overrides.json');
            return;
        }

        $stats = ['matched_regex' => 0, 'matched_override' => 0, 'unresolved' => 0];
        $unresolvedSamples = [];

        DB::transaction(function () use ($dict, $typeOf, $overrides, &$stats, &$unresolvedSamples) {
            foreach (IpiTaskAction::all() as $action) {
                $text = trim($action->text);
                $update = $this->parse($text, $dict, $typeOf);

                // Manual override always wins
                $key = $action->task_id . '.' . $action->sort_order;
                if (isset($overrides[$key]) && is_array($overrides[$key])) {
                    foreach ($overrides[$key] as $col => $val) {
                        if (str_starts_with($col, '_')) continue; // skip _note etc.
                        $update[$col] = $val;
                    }
                    $stats['matched_override']++;
                } elseif ($update['requirement_type']) {
                    $stats['matched_regex']++;
                } else {
                    $stats['unresolved']++;
                    if (count($unresolvedSamples) < 10) {
                        $unresolvedSamples[] = "[task {$action->task_id} act {$action->sort_order}] {$text}";
                    }
                }

                $action->fill($update)->save();
            }
        });

        $this->command->info(sprintf(
            'IPI requirements: regex=%d, override=%d, unresolved=%d (total %d)',
            $stats['matched_regex'],
            $stats['matched_override'],
            $stats['unresolved'],
            $stats['matched_regex'] + $stats['matched_override'] + $stats['unresolved']
        ));
        if (!empty($unresolvedSamples)) {
            $this->command->warn('Unresolved samples:');
            foreach ($unresolvedSamples as $s) $this->command->line('  ' . $s);
        }
    }

    /**
     * Apply the 5 regex patterns. Returns an associative array of column updates.
     */
    private function parse(string $text, array $dict, array $typeOf): array
    {
        $base = ['requirement_type' => null, 'requirement_target' => null, 'requirement_value' => null, 'planet_scope' => null, 'hint_target' => null];

        // 1. Sviluppa <NAME> [sul pianeta colonizzato] e porta l'edificio al livello <N>
        if (preg_match('/^Sviluppa (.+?)(\s+sul pianeta colonizzato)?\s+e porta l[`\']edificio al livello (\d+)$/u', $text, $m)) {
            $name = mb_strtolower(trim($m[1]));
            $level = (int)$m[3];
            $machine = $dict[$name] ?? null;
            return array_merge($base, [
                'requirement_type' => 'building_level',
                'requirement_target' => $machine,
                'requirement_value' => $level,
                'planet_scope' => $m[2] ? 'colonized_planet' : 'home_planet',
                'hint_target' => $machine ? $this->defaultHintForBuilding($machine) : null,
            ]);
        }

        // 2. Approfondisci <NAME> e portala al livello <N>
        if (preg_match('/^Approfondisci (.+?) e portala al livello (\d+)$/u', $text, $m)) {
            // Strip leading articles like "la "
            $name = preg_replace('/^(la|il|lo|le|gli|i)\s+/u', '', mb_strtolower(trim($m[1])));
            $level = (int)$m[2];
            $machine = $dict[$name] ?? null;
            return array_merge($base, [
                'requirement_type' => 'research_level',
                'requirement_target' => $machine,
                'requirement_value' => $level,
                'planet_scope' => 'any',
                'hint_target' => $machine ? $this->defaultHintForBuilding($machine) : null,
            ]);
        }

        // 3. Costruisci <NAME> (<N> volta/e)
        if (preg_match('/^Costruisci (.+?) \((\d+) volta\/e\)$/u', $text, $m)) {
            $name = mb_strtolower(trim($m[1]));
            $qty = (int)$m[2];
            $machine = $dict[$name] ?? null;
            $type = $machine ? ($typeOf[$machine] ?? 'unknown') : 'unknown';
            return array_merge($base, [
                'requirement_type' => $type === 'defense' ? 'defense_built' : 'ship_built',
                'requirement_target' => $machine,
                'requirement_value' => $qty,
                'planet_scope' => 'any',
                'hint_target' => $machine ? $this->defaultHintForBuilding($machine) : null,
            ]);
        }

        // 4. Imposta al N% le prestazioni (della|del|...) <NAME>.
        if (preg_match('/^Imposta al (\d+)% le prestazioni (?:della|del|dello|dei|delle|di) (.+?)\.?$/u', $text, $m)) {
            $pct = (int)$m[1];
            $name = mb_strtolower(trim($m[2]));
            $machine = $dict[$name] ?? null;
            return array_merge($base, [
                'requirement_type' => 'production_setting',
                'requirement_target' => $machine,
                'requirement_value' => $pct,
                'planet_scope' => 'home_planet',
                'hint_target' => 'ipiToolbarResourcesettings',
            ]);
        }

        // 5. Distruggi <NAME>
        if (preg_match('/^Distruggi (.+)$/u', $text, $m)) {
            $name = mb_strtolower(trim($m[1]));
            $machine = $dict[$name] ?? null;
            return array_merge($base, [
                'requirement_type' => 'demolish',
                'requirement_target' => $machine,
                'requirement_value' => null,
                'planet_scope' => 'home_planet',
                'hint_target' => $machine ? $this->defaultHintForBuilding($machine) : null,
            ]);
        }

        return $base;
    }
}
