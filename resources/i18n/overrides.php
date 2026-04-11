<?php

/**
 * Manual translation overrides applied by `php artisan i18n:build-dictionary`
 * AFTER loading the canonical OGame scrape.
 *
 * Two roles:
 *
 *   1. COLLISION FIX — correct an existing dictionary entry where the canonical
 *      scrape captured an inferior translation for a given language.
 *
 *   2. NEW ENTRY    — supply translations for a string that does NOT appear
 *      in the OGame public website scrape (typically OGameX-specific labels
 *      like "Diameter:" / "Hire commander"). When the english text is not
 *      already in the dictionary, the build command CREATES a new entry.
 *
 * Format:
 *   <englishText> => [
 *       <langCode> => <translation>,
 *       ...
 *   ]
 *
 * Documented decisions live alongside each block.
 */

return [
    // ---------------------------------------------------------------------
    // Collision #2 — "Shop"
    // The canonical scrape kept Russian "Снаряжение" (= "Equipment"), which
    // is wrong: in OGame the Shop is the dark-matter store, so the literal
    // "Магазин" is the correct rendering. All other "Shop" languages were
    // free of collisions and stay as scraped.
    // ---------------------------------------------------------------------
    'Shop' => [
        'ru' => 'Магазин',
    ],

    // ---------------------------------------------------------------------
    // OGameX-specific labels — "Planet stats panel" (typewriter animation)
    //
    // These labels are emitted by resources/views/ingame/overview/index.blade.php
    // (textContent[0..9]) but do NOT appear in the OGame public scrape because
    // they live in the OGameX overview page only. Each entry is hand-curated
    // from the official OGame community wiki for the matching server locale.
    // ---------------------------------------------------------------------
    'Diameter' => [
        'ar' => 'Diámetro',  'br' => 'Diâmetro',  'cz' => 'Průměr',     'de' => 'Durchmesser',
        'dk' => 'Diameter',  'es' => 'Diámetro',  'fi' => 'Halkaisija', 'fr' => 'Diamètre',
        'gr' => 'Διάμετρος', 'hr' => 'Promjer',   'hu' => 'Átmérő',     'it' => 'Diametro',
        'jp' => '直径',       'mx' => 'Diámetro',  'nl' => 'Diameter',   'pl' => 'Średnica',
        'pt' => 'Diâmetro',  'ro' => 'Diametru',  'ru' => 'Диаметр',    'se' => 'Diameter',
        'si' => 'Premer',    'sk' => 'Priemer',   'tr' => 'Çap',        'tw' => '直徑',
        'us' => 'Diameter',  'yu' => 'Prečnik',
    ],

    'Temperature' => [
        'ar' => 'Temperatura', 'br' => 'Temperatura',  'cz' => 'Teplota',     'de' => 'Temperatur',
        'dk' => 'Temperatur',  'es' => 'Temperatura',  'fi' => 'Lämpötila',   'fr' => 'Température',
        'gr' => 'Θερμοκρασία', 'hr' => 'Temperatura',  'hu' => 'Hőmérséklet', 'it' => 'Temperatura',
        'jp' => '温度',         'mx' => 'Temperatura',  'nl' => 'Temperatuur', 'pl' => 'Temperatura',
        'pt' => 'Temperatura', 'ro' => 'Temperatură',  'ru' => 'Температура', 'se' => 'Temperatur',
        'si' => 'Temperatura', 'sk' => 'Teplota',      'tr' => 'Sıcaklık',    'tw' => '溫度',
        'us' => 'Temperature', 'yu' => 'Temperatura',
    ],

    'Position' => [
        'ar' => 'Posición', 'br' => 'Posição',  'cz' => 'Pozice',   'de' => 'Position',
        'dk' => 'Position', 'es' => 'Posición', 'fi' => 'Sijainti', 'fr' => 'Position',
        'gr' => 'Θέση',     'hr' => 'Pozicija', 'hu' => 'Pozíció',  'it' => 'Posizione',
        'jp' => '位置',      'mx' => 'Posición', 'nl' => 'Positie',  'pl' => 'Pozycja',
        'pt' => 'Posição',  'ro' => 'Poziție',  'ru' => 'Позиция',  'se' => 'Position',
        'si' => 'Položaj',  'sk' => 'Pozícia',  'tr' => 'Konum',    'tw' => '位置',
        'us' => 'Position', 'yu' => 'Pozicija',
    ],

    'Points' => [
        'ar' => 'Puntos',  'br' => 'Pontos',  'cz' => 'Body',     'de' => 'Punkte',
        'dk' => 'Point',   'es' => 'Puntos',  'fi' => 'Pisteet',  'fr' => 'Points',
        'gr' => 'Πόντοι',  'hr' => 'Bodovi',  'hu' => 'Pontok',   'it' => 'Punti',
        'jp' => 'ポイント',   'mx' => 'Puntos',  'nl' => 'Punten',   'pl' => 'Punkty',
        'pt' => 'Pontos',  'ro' => 'Puncte',  'ru' => 'Очки',     'se' => 'Poäng',
        'si' => 'Točke',   'sk' => 'Body',    'tr' => 'Puanlar',  'tw' => '點數',
        'us' => 'Points',  'yu' => 'Bodovi',
    ],

    'Honour points' => [
        'ar' => 'Puntos de honor',   'br' => 'Pontos de honra', 'cz' => 'Body cti',
        'de' => 'Ehrenpunkte',       'dk' => 'Ærespoint',       'es' => 'Puntos de honor',
        'fi' => 'Kunniapisteet',     'fr' => "Points d'honneur", 'gr' => 'Πόντοι Τιμής',
        'hr' => 'Bodovi časti',      'hu' => 'Becsületpontok',  'it' => 'Punti onore',
        'jp' => '名誉点',              'mx' => 'Puntos de honor', 'nl' => 'Eerepunten',
        'pl' => 'Punkty honoru',     'pt' => 'Pontos de honra', 'ro' => 'Puncte de onoare',
        'ru' => 'Очки чести',         'se' => 'Hederspoäng',     'si' => 'Točke časti',
        'sk' => 'Body cti',          'tr' => 'Onur Puanları',   'tw' => '榮譽點數',
        'us' => 'Honor points',      'yu' => 'Časni bodovi',
    ],

    // "Place" appears only as the noun in the leaderboard fragment "Place X of Y".
    'Place' => [
        'ar' => 'Lugar',  'br' => 'Lugar',  'cz' => 'Místo',  'de' => 'Platz',
        'dk' => 'Plads',  'es' => 'Lugar',  'fi' => 'Sija',   'fr' => 'Place',
        'gr' => 'Θέση',   'hr' => 'Mjesto', 'hu' => 'Hely',   'it' => 'Posto',
        'jp' => '順位',    'mx' => 'Lugar',  'nl' => 'Plaats', 'pl' => 'Miejsce',
        'pt' => 'Lugar',  'ro' => 'Locul',  'ru' => 'Место',  'se' => 'Plats',
        'si' => 'Mesto',  'sk' => 'Miesto', 'tr' => 'Sıra',   'tw' => '名次',
        'us' => 'Place',  'yu' => 'Mesto',
    ],

    // "of" — the connector inside "Place X of Y" (overview.score_of). It is
    // the only standalone "of" leaf in the lang files (verified via grep), so
    // a flat translation is safe even though "of" is otherwise context-bound.
    'of' => [
        'ar' => 'de',   'br' => 'de',   'cz' => 'z',    'de' => 'von',
        'dk' => 'af',   'es' => 'de',   'fi' => '/',    'fr' => 'sur',
        'gr' => 'από',  'hr' => 'od',   'hu' => '/',    'it' => 'su',
        'jp' => '/',    'mx' => 'de',   'nl' => 'van',  'pl' => 'z',
        'pt' => 'de',   'ro' => 'din',  'ru' => 'из',   'se' => 'av',
        'si' => 'od',   'sk' => 'z',    'tr' => '/',    'tw' => '/',
        'us' => 'of',   'yu' => 'od',
    ],

    // ---------------------------------------------------------------------
    // OGameX-specific labels — Premium / Officers page
    // ---------------------------------------------------------------------
    'Your officers' => [
        'ar' => 'Tus oficiales',     'br' => 'Seus oficiais',     'cz' => 'Tvoji důstojníci',
        'de' => 'Deine Offiziere',   'dk' => 'Dine officerer',    'es' => 'Tus oficiales',
        'fi' => 'Upseerisi',         'fr' => 'Vos officiers',     'gr' => 'Οι αξιωματικοί σου',
        'hr' => 'Tvoji časnici',     'hu' => 'Tisztjeid',         'it' => 'I tuoi ufficiali',
        'jp' => 'あなたの士官',         'mx' => 'Tus oficiales',     'nl' => 'Uw officieren',
        'pl' => 'Twoi oficerowie',   'pt' => 'Seus oficiais',     'ro' => 'Ofițerii tăi',
        'ru' => 'Ваши офицеры',       'se' => 'Dina officerare',   'si' => 'Tvoji častniki',
        'sk' => 'Tvoji dôstojníci',  'tr' => 'Subaylarınız',      'tw' => '你的軍官',
        'us' => 'Your officers',     'yu' => 'Tvoji oficiri',
    ],

    'Dark Matter' => [
        'ar' => 'Materia Oscura',  'br' => 'Matéria Escura',  'cz' => 'Temná hmota',
        'de' => 'Dunkle Materie',  'dk' => 'Mørkt stof',      'es' => 'Materia Oscura',
        'fi' => 'Pimeä aine',      'fr' => 'Matière Noire',   'gr' => 'Σκοτεινή Ύλη',
        'hr' => 'Tamna tvar',      'hu' => 'Sötét anyag',     'it' => 'Materia Oscura',
        'jp' => 'ダークマター',         'mx' => 'Materia Oscura',  'nl' => 'Donkere Materie',
        'pl' => 'Ciemna Materia',  'pt' => 'Matéria Escura',  'ro' => 'Materie întunecată',
        'ru' => 'Тёмная материя',  'se' => 'Mörk materia',    'si' => 'Temna snov',
        'sk' => 'Temná hmota',     'tr' => 'Karanlık Madde',  'tw' => '暗物質',
        'us' => 'Dark Matter',     'yu' => 'Tamna materija',
    ],
];
