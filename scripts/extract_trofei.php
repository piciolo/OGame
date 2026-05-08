<?php
/**
 * Estrae achievements dal dump HTML OGame e produce un JSON strutturato.
 * Usa la sezione finale del file (achievementContentList_*) come registry
 * autoritativo per associare reward (skin/avatar/title) a (achievement, tier).
 */

$srcFile = 'D:/OGAME WEB/trofei.txt';
$outFile = 'D:/ogame/database/seeders/data/achievements_extracted.json';

$html = file_get_contents($srcFile);
if ($html === false) {
    fwrite(STDERR, "Impossibile leggere $srcFile\n");
    exit(1);
}

// ============================================================
// 1) Costruisci il REGISTRY dei reward dalle sezioni finali.
// ============================================================
$registry = []; // key: "ach_id|tier" => [type, machine_name, image, title_text]

// --- SKINS ---
preg_match_all(
    '~<div id="achievementOverviewSpaceObjectSkinHolder_[^"]+"[^>]*data-skin-id="([^"]+)">\s*<space-object-skin[^>]*rel="achievementOverviewAchievementHolder_(\d+)"[^>]*data-tooltip-add-class="tier_(\d+)"[^>]*>\s*<img[^>]*src="([^"]+)"~s',
    $html,
    $skinMatches,
    PREG_SET_ORDER
);
foreach ($skinMatches as $m) {
    $key = $m[2] . '|' . $m[3];
    $registry[$key] = [
        'reward_type' => 'skin',
        'reward_machine_name' => $m[1],
        'reward_image_url' => $m[4],
        'reward_title_text' => null,
    ];
}

// --- AVATARS ---
preg_match_all(
    '~<div class="achievementOverviewProfilePictureHolder[^"]*"\s+data-avatar-id="([^"]+)">\s*<profile-picture[^>]*rel="achievementOverviewAchievementHolder_(\d+)"[^>]*data-tooltip-add-class="tier_(\d+)"~s',
    $html,
    $avaMatches,
    PREG_SET_ORDER
);
foreach ($avaMatches as $m) {
    $key = $m[2] . '|' . $m[3];
    $registry[$key] = [
        'reward_type' => 'avatar',
        'reward_machine_name' => $m[1],
        'reward_image_url' => null,
        'reward_title_text' => null,
    ];
}

// --- TITLES ---
preg_match_all(
    '~<div class="achievementOverviewProfileTitleHolder[^"]*"\s+data-title-id="([^"]+)"\s+rel="achievementOverviewAchievementHolder_(\d+)"[^>]*data-tooltip-add-class="tier_(\d+)"[^>]*>\s*<div class="titleHolder"\s+lang="it">([^<]+)</div>~s',
    $html,
    $titMatches,
    PREG_SET_ORDER
);
foreach ($titMatches as $m) {
    $key = $m[2] . '|' . $m[3];
    $registry[$key] = [
        'reward_type' => 'title',
        'reward_machine_name' => $m[1],
        'reward_image_url' => null,
        'reward_title_text' => trim(html_entity_decode($m[4], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
    ];
}

// ============================================================
// 2) Parsing degli achievement (titolo, descrizioni, target).
// ============================================================
$results = [];
$anomalies = [];
$rewardCounts = ['skin' => 0, 'avatar' => 0, 'title' => 0, 'unknown' => 0];

// Limita il parsing alla sezione "unlocks" (dove vivono i singoli achievement
// con tier visibili) per evitare collisioni con la sezione finale.
$mainSectionEnd = strpos($html, 'achievementContentList_avatars');
$mainHtml = $mainSectionEnd !== false ? substr($html, 0, $mainSectionEnd) : $html;

preg_match_all(
    '~<div id="achievementOverviewAchievementHolder_(\d+)"~',
    $mainHtml,
    $achPositions,
    PREG_OFFSET_CAPTURE
);

$entries = [];
foreach ($achPositions[0] as $i => $pos) {
    $start = $pos[1];
    $end = $achPositions[0][$i + 1][1] ?? strlen($mainHtml);
    $entries[] = [
        'id' => (int) $achPositions[1][$i][0],
        'block' => substr($mainHtml, $start, $end - $start),
    ];
}

foreach ($entries as $entry) {
    $achievementId = $entry['id'];
    $block = $entry['block'];

    // Title: "#N - Nome"
    $title = null;
    if (preg_match('~<div class="achievementOverviewAchievementTitle">\s*<span>\s*#([0-9]+)\s*-\s*(.*?)\s*</span>~s', $block, $tm)) {
        $title = trim(html_entity_decode($tm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    } else {
        $anomalies[] = "Titolo non trovato per achievement_id=$achievementId";
    }

    // Estrai i 5 tier
    $tiers = [];
    preg_match_all(
        '~<div id="achievementTier_' . $achievementId . '_(\d+)"~',
        $block,
        $tierPositions,
        PREG_OFFSET_CAPTURE
    );

    foreach ($tierPositions[0] as $i => $tp) {
        $tStart = $tp[1];
        $tEnd = $tierPositions[0][$i + 1][1] ?? strlen($block);
        $tierBlock = substr($block, $tStart, $tEnd - $tStart);
        $tierNum = (int) $tierPositions[1][$i][0];

        $description = null;
        if (preg_match('~<div class="description">(.*?)</div>~s', $tierBlock, $dm)) {
            $description = trim(html_entity_decode($dm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $target = null;
        if (preg_match('~<span class="progressTarget">\s*([0-9.,]+)\s*</span>~', $tierBlock, $pm)) {
            $target = (int) str_replace(['.', ','], '', $pm[1]);
        }

        $key = $achievementId . '|' . $tierNum;
        $reward = $registry[$key] ?? null;

        // Estrai la sola sezione achievementReward del tier
        $rewardSection = '';
        if (preg_match('~<div class="achievementReward">(.*?)</div>\s*</div>~s', $tierBlock, $rsm)) {
            $rewardSection = $rsm[1];
        }

        if ($reward === null) {
            // Fallback inline (reward presente nell'overview ma non nel registry finale)
            $reward = [
                'reward_type' => null,
                'reward_machine_name' => null,
                'reward_image_url' => null,
                'reward_title_text' => null,
            ];
            if (preg_match('~<space-object-skin[^>]*class="([^"\s]+)[^"]*"[^>]*>\s*<img[^>]*src="([^"]+)"~s', $rewardSection, $sm)) {
                $reward['reward_type'] = 'skin';
                $reward['reward_machine_name'] = $sm[1];
                $reward['reward_image_url'] = $sm[2];
            } elseif (preg_match('~<profile-picture[^>]*class="([^"\s]+)[^"]*"~s', $rewardSection, $sm)) {
                $reward['reward_type'] = 'avatar';
                $reward['reward_machine_name'] = $sm[1];
            } elseif (preg_match('~<div sq100=""\s*class="rewardTypeTitleContainer">\s*<div class="rewardTypeTitle"\s*lang="it">\s*(.*?)\s*</div>~s', $rewardSection, $sm)) {
                $reward['reward_type'] = 'title';
                $reward['reward_title_text'] = trim(html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                // machine_name fallback ricostruito - non disponibile dall'HTML
                $reward['reward_machine_name'] = null;
                $anomalies[] = "Title senza machine_name (no data-title-id) per ach=$achievementId tier=$tierNum text=\"{$reward['reward_title_text']}\"";
            } elseif (trim(strip_tags($rewardSection)) === '' || trim(strip_tags($rewardSection)) === 'Ricompensa') {
                // Reward vuota -> achievement segreto / locked nel rendering
                $anomalies[] = "Reward locked/vuota inline per ach=$achievementId tier=$tierNum";
            } else {
                $anomalies[] = "Reward non determinata per ach=$achievementId tier=$tierNum";
            }
        }

        if ($reward['reward_type'] !== null) {
            $rewardCounts[$reward['reward_type']]++;
        } else {
            $rewardCounts['unknown']++;
        }

        $tiers[] = [
            'tier' => $tierNum,
            'target' => $target,
            'description' => $description,
            'reward_type' => $reward['reward_type'],
            'reward_machine_name' => $reward['reward_machine_name'],
            'reward_image_url' => $reward['reward_image_url'],
            'reward_title_text' => $reward['reward_title_text'],
        ];
    }

    usort($tiers, fn ($a, $b) => $a['tier'] <=> $b['tier']);

    if (count($tiers) !== 5) {
        $anomalies[] = "Achievement $achievementId ha " . count($tiers) . " tier (atteso 5)";
    }

    $results[] = [
        'achievement_id' => $achievementId,
        'title' => $title,
        'tiers' => $tiers,
    ];
}

@mkdir(dirname($outFile), 0777, true);
file_put_contents(
    $outFile,
    json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

$totalTiers = array_sum(array_map(fn ($a) => count($a['tiers']), $results));

echo "Achievement estratti : " . count($results) . "\n";
echo "Tier totali          : $totalTiers\n";
echo "Reward skin          : {$rewardCounts['skin']}\n";
echo "Reward avatar        : {$rewardCounts['avatar']}\n";
echo "Reward title         : {$rewardCounts['title']}\n";
echo "Reward sconosciuti   : {$rewardCounts['unknown']}\n";
echo "Anomalie             : " . count($anomalies) . "\n";
foreach ($anomalies as $a) {
    echo "  - $a\n";
}
echo "Output JSON          : $outFile\n";
