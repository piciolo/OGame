<?php
/**
 * Ricostruisce playerprofile_achievements.css estraendo TUTTE le regole CSS
 * con selettori che riguardano achievement / profile-picture / space-object-skin /
 * titleHolder / scrollbar dal CSS principale OGame.
 */

$src = file_get_contents(__DIR__ . '/../_research/ogame_css/001_gf3_geo_gfsrv_net_9b6fdb87.css');
$len = strlen($src);

// Pattern di selettori da catturare (regola completa con body)
$wantedSelectors = [
    'achievementsoverviewcomponent',
    'achievementsOverviewCategories',
    'achievementCategory',
    'achievementsOverviewList',
    'achievementContentList',
    'achievementOverviewAchievementHolder',
    'achievementOverviewAchievementTitle',
    'achievementOverviewTierSelectionContainer',
    'achievementOverviewTiersContainer',
    'achievementTitleAndTierSelectionContainer',
    'achievementTier_',
    'achievementTierContainer',
    'achievementTierContainerTitle',
    'achievementTierContainerData',
    'achievementTierStatus',
    'achievementProgress',
    'achievementProgressLabel',
    'progressTarget',
    'progressParent',
    'achievementReward',
    'rewardTitle',
    'rewardDescription',
    'rewardTypeTitle',
    'rewardTypeTitleContainer',
    'unlockedOrProgress',
    'unlockedContainer',
    'achievementFilter',
    'achievementFilteringOptions',
    'achievementOverviewFilterIcon',
    'achievementSeasonDropdownHolder',
    'achievementSeasonTitle',
    'achievementOverviewProfilePictureHolder',
    'achievementOverviewSpaceObjectSkinHolder',
    'achievementOverviewProfileTitleHolder',
    'profile-picture',
    'space-object-skin',
    'titleHolder',
    'horizontalSplit',
    'tier_1', 'tier_2', 'tier_3', 'tier_4', 'tier_5',
    'seasonalSummary',
    'profileTitleSelectBtn', 'profileTitleDeselectBtn',
    'profilePictureSelectBtn', 'profilePictureDeselectBtn',
    'spaceObjectSkinSelectBtn', 'spaceObjectSkinDeselectBtn',
];

// Tokenizer CSS semplice: trova ogni `selectors { body }` e mantiene il blocco
// se uno qualsiasi dei selettori contiene una keyword wanted.
$rules = [];
$pos = 0;
$skipMachineNameRules = '/^\s*(profile-picture|space-object-skin)\.A\d+_T\d+_(Ava|Pskin)_ID\d+\s*$/';

while ($pos < $len) {
    // Trova il prossimo `{`
    $brace = strpos($src, '{', $pos);
    if ($brace === false) break;
    // Selettori = pezzo prima di `{`. Backtrack fino al precedente `}` (o inizio file).
    $prevEnd = max(strrpos(substr($src, 0, $brace), '}'), strrpos(substr($src, 0, $brace), '*/'));
    $selStart = $prevEnd === false ? 0 : ($prevEnd + 1);
    $selectors = trim(substr($src, $selStart, $brace - $selStart));

    // Salta blocchi @media, @supports, @keyframes — verranno gestiti più sotto
    if (str_starts_with($selectors, '@')) {
        // Per @media/@supports cerca `}` matcher al primo livello per saltare
        // (semplificazione: non capture nested rules)
        $depth = 1;
        $i = $brace + 1;
        while ($i < $len && $depth > 0) {
            if ($src[$i] === '{') $depth++;
            elseif ($src[$i] === '}') $depth--;
            $i++;
        }
        $pos = $i;
        continue;
    }

    // Trova chiusura `}` del blocco (con bracket-depth counting per nested rules)
    $depth = 1;
    $i = $brace + 1;
    while ($i < $len && $depth > 0) {
        if ($src[$i] === '{') $depth++;
        elseif ($src[$i] === '}') $depth--;
        $i++;
    }
    if ($depth !== 0) break;
    $close = $i - 1;
    $body = substr($src, $brace + 1, $close - $brace - 1);

    // Verifica match: salta machine-name avatar/skin (gestiti in playerprofile_avatars.css)
    if (preg_match($skipMachineNameRules, $selectors)) {
        $pos = $close + 1;
        continue;
    }

    $matched = false;
    foreach ($wantedSelectors as $kw) {
        if (stripos($selectors, $kw) !== false) {
            $matched = true;
            break;
        }
    }

    if ($matched) {
        $rules[] = $selectors . " {" . $body . "}";
    }

    $pos = $close + 1;
}

// Aggiungi le regole scrollbar globali (sempre presenti)
$globalScrollbar = "\n/* === Scrollbar globale OGame === */\n";
preg_match_all('/::-webkit-scrollbar[^{]*\{[^}]*\}/m', $src, $sm);
foreach ($sm[0] as $r) $globalScrollbar .= $r . "\n";

$out = "/* OGame Trofei — CSS autoritativo (regenerato da scripts/rebuild_achievement_css.php).\n";
$out .= "   Sorgente: _research/ogame_css/001_gf3_geo_gfsrv_net_9b6fdb87.css\n";
$out .= "   Le regole per-machine_name (profile-picture.A*, space-object-skin.A*) vivono in\n";
$out .= "   playerprofile_avatars.css con path locali. */\n\n";
$out .= implode("\n\n", $rules);
$out .= $globalScrollbar;

// Default avatar fallback → local
$out = str_replace(
    '//gf2.geo.gfsrv.net/cdn1e/e7eca98a47726ae2c85a595b29dd82.png',
    '/img/layout/avatar.png',
    $out
);

file_put_contents(__DIR__ . '/../resources/css/ingame/playerprofile_achievements.css', $out);
echo "Wrote " . strlen($out) . " bytes, " . count($rules) . " rules + " . count($sm[0]) . " scrollbar rules\n";
