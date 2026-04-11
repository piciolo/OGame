<?php

/**
 * Manual collision overrides applied by `php artisan i18n:build-dictionary`
 * AFTER loading the canonical OGame scrape.
 *
 * Each override targets a single (englishText, langCode) cell of the master
 * dictionary. Overrides are reviewed in the project handoff and exist to
 * resolve cases where the canonical scrape captured an inferior translation
 * (typo, wrong meaning, casing) for a string that appears in multiple OGame
 * pages with conflicting values.
 *
 * Format:
 *   <englishText> => [
 *       <langCode> => <correctedTranslation>,
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
];
