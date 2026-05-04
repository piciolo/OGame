<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\ImportExportItem;

/**
 * Catalogo statico Import/Export — 18 item (6 tipi × 3 rarità).
 *
 * Fonti dati:
 * - Nome/descrizione KRAKEN Bronzo: scraping diretto OGame live (s275-it).
 * - Pesi drop, durate, valori effetto: ogame.fandom.com/wiki/Import_Export_Market
 *   + conferme utente (chat 2026-05-04).
 * - Pattern naming/descrizioni altri item: deriva 1:1 dal Kraken (sostituzione del
 *   target: edifici→navi (Detroid), edifici→ricerche (Newtron)) o dal pattern Booster
 *   Risorse OGame standard.
 *
 * Eventuali raffinamenti dei testi seguiranno via TranslationsSeeder appena scrappate
 * tutte le lingue.
 */
class ImportExportCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $rarities = [
            'bronze' => ['label' => 'Bronzo', 'weight' => 9, 'dm_change' => 500,  'duration_label' => '30m', 'duration_sec_accel' => 1800,  'percent_boost' => 10, 'price_base' => 2500],
            'silver' => ['label' => 'Argento', 'weight' => 3, 'dm_change' => 1500, 'duration_label' => '2h',  'duration_sec_accel' => 7200,  'percent_boost' => 20, 'price_base' => 8500],
            'gold'   => ['label' => 'Oro',     'weight' => 1, 'dm_change' => 4500, 'duration_label' => '6h',  'duration_sec_accel' => 21600, 'percent_boost' => 30, 'price_base' => 25000],
        ];

        $accelerators = [
            'kraken'  => [
                'name'        => 'KRAKEN',
                'category'    => 'accelerator',
                'cat_weight'  => 3,
                'description' => 'Il robot da costruzione KRAKEN permette di accelerare la costruzione di strutture e strutture per le risorse grazie alle sue teste con sensori flessibili e le braccia multifunzione.',
                'tooltip_fmt' => 'Fa diminuire (-%s) il tempo di costruzione degli edifici attualmente in costruzione su un pianeta.',
                'icon'        => 'kraken',
            ],
            'detroid' => [
                'name'        => 'DETROID',
                'category'    => 'accelerator',
                'cat_weight'  => 3,
                'description' => 'Il robot da costruzione DETROID accelera la produzione di unità nel cantiere navale grazie alle sue braccia di assemblaggio multifunzione.',
                'tooltip_fmt' => 'Fa diminuire (-%s) il tempo di produzione delle unità attualmente in costruzione nel cantiere navale.',
                'icon'        => 'detroid',
            ],
            'newtron' => [
                'name'        => 'NEWTRON',
                'category'    => 'accelerator',
                'cat_weight'  => 3,
                'description' => 'Il computer di ricerca NEWTRON accelera l`elaborazione dei dati nei laboratori di ricerca grazie ai suoi processori quantici.',
                'tooltip_fmt' => 'Fa diminuire (-%s) il tempo di ricerca attualmente in corso.',
                'icon'        => 'newtron',
            ],
        ];

        $resourceBoosters = [
            'metal_booster' => [
                'name'        => 'Booster Metallo',
                'category'    => 'resource_boost',
                'cat_weight'  => 9,
                'description' => 'Aumenta la produzione di metallo di un pianeta per 7 giorni grazie all`ottimizzazione automatica dei macchinari di estrazione.',
                'tooltip_fmt' => 'Aumenta la produzione di metallo del +%d%% per 7 giorni.',
                'icon'        => 'metal_booster',
            ],
            'crystal_booster' => [
                'name'        => 'Booster Cristallo',
                'category'    => 'resource_boost',
                'cat_weight'  => 9,
                'description' => 'Aumenta la produzione di cristallo di un pianeta per 7 giorni grazie all`ottimizzazione automatica dei macchinari di estrazione.',
                'tooltip_fmt' => 'Aumenta la produzione di cristallo del +%d%% per 7 giorni.',
                'icon'        => 'crystal_booster',
            ],
            'deuterium_booster' => [
                'name'        => 'Booster Deuterio',
                'category'    => 'resource_boost',
                'cat_weight'  => 9,
                'description' => 'Aumenta la produzione di deuterio di un pianeta per 7 giorni grazie all`ottimizzazione automatica dei sintetizzatori.',
                'tooltip_fmt' => 'Aumenta la produzione di deuterio del +%d%% per 7 giorni.',
                'icon'        => 'deuterium_booster',
            ],
        ];

        ImportExportItem::query()->truncate();

        foreach (array_merge($accelerators, $resourceBoosters) as $type => $base) {
            foreach ($rarities as $rarity => $rarityCfg) {
                $isAccel = $base['category'] === 'accelerator';
                $effectValue = $isAccel ? $rarityCfg['duration_sec_accel'] : $rarityCfg['percent_boost'];
                $duration    = $isAccel ? null : 7 * 86400;
                $tooltipArg  = $isAccel ? $rarityCfg['duration_label'] : $rarityCfg['percent_boost'];
                $description = $base['description'] . ' ' . sprintf($base['tooltip_fmt'], $tooltipArg);
                $name        = $base['name'] . ' ' . $rarityCfg['label'];

                ImportExportItem::create([
                    'ref'              => sha1('import_export:' . $type . ':' . $rarity),
                    'category'         => $base['category'],
                    'type'             => $type,
                    'rarity'           => $rarity,
                    'name'             => $name,
                    'description'      => $description,
                    'effect_value'     => $effectValue,
                    'duration_seconds' => $duration,
                    'icon_path'        => 'img/import_export/' . $base['icon'] . '_' . $rarity . '.gif',
                    'drop_weight'      => $base['cat_weight'] * $rarityCfg['weight'],
                    'change_dm_cost'   => $rarityCfg['dm_change'],
                    'price_base'       => $rarityCfg['price_base'],
                    'translations'     => null,
                ]);
            }
        }
    }
}
