<?php
/**
 * extract_har_full.php — Estrazione esaustiva CSS/JS/HTML/asset achievement OGame
 *
 * Input : D:/OGAME WEB/s275-it.ogame.gameforge.com.har (56 MB)
 * Output:
 *   D:/ogame/_research/ogame_css/*.css + _index.md
 *   D:/ogame/_research/ogame_achievement_complete.css
 *   D:/ogame/_research/ogame_js/*.js
 *   D:/ogame/_research/ogame_trofei_full.html
 *   D:/ogame/_research/ogame_trofei_schemas.md
 *   D:/ogame/public/img/achievements/icons/* + _index.md
 */

ini_set('memory_limit', '2048M');

$HAR    = 'D:/OGAME WEB/s275-it.ogame.gameforge.com.har';
$ROOT   = 'D:/ogame/_research';
$DIRCSS = "$ROOT/ogame_css";
$DIRJS  = "$ROOT/ogame_js";
$DIRIMG = 'D:/ogame/public/img/achievements/icons';

@mkdir($DIRCSS, 0777, true);
@mkdir($DIRJS, 0777, true);
@mkdir($DIRIMG, 0777, true);

echo "[1/6] Carico HAR...\n";
$rawSize = filesize($HAR);
$json = json_decode(file_get_contents($HAR), true);
if (!$json) { die("HAR JSON parse fail\n"); }
$entries = $json['log']['entries'] ?? [];
$totEntries = count($entries);
echo "    Entries: $totEntries  (size raw " . number_format($rawSize) . " B)\n";

// ============== SELETTORI achievement-related =================
$ACH_SELECTORS = [
    'achievementOverviewAchievementHolder', 'achievementTierContainer', 'achievementReward',
    'rewardTypeTitle', 'achievementContentList', 'achievementsOverviewList',
    'profile-picture', 'space-object-skin', 'tierstar',
    'tier_1', 'tier_2', 'tier_3', 'tier_4', 'tier_5',
    '::-webkit-scrollbar', 'scrollbar-width',
    'achievementProgress', 'progressTarget', 'progressParent',
    'gradient-button', 'custom_btn',
    'locked', 'unlocked', 'selected',
    'tierSelectionContainer', 'titleHolder', 'titleTypeSelection',
];

function safeName(string $url, int $idx, string $ext): string {
    $h = substr(md5($url), 0, 8);
    $host = parse_url($url, PHP_URL_HOST) ?: 'unknown';
    $host = preg_replace('/[^a-z0-9]+/i', '_', $host);
    return sprintf('%03d_%s_%s.%s', $idx, $host, $h, $ext);
}

function decodeContent(array $content): string {
    $text = $content['text'] ?? '';
    if (($content['encoding'] ?? '') === 'base64') {
        $text = base64_decode($text);
    }
    return (string)$text;
}

// Estrai una regola CSS completa (selector { body }) per tutti i match di selettore
function extractCssBlocks(string $css, array $needles): array {
    $blocks = [];
    $len = strlen($css);
    $i = 0;
    // Itera ogni regola top-level e nested in @media -> più semplice: scan brace-balanced
    // Strategy: trova ogni '{' poi backtrack a '}' o ';' o inizio per prendere selector,
    // poi avanza con counter per chiusura.
    while ($i < $len) {
        $brace = strpos($css, '{', $i);
        if ($brace === false) break;
        // Selector start: dopo precedente '}' o ';' o '*/' o inizio
        $start = 0;
        for ($j = $brace - 1; $j >= 0; $j--) {
            $c = $css[$j];
            if ($c === '}' || $c === ';') { $start = $j + 1; break; }
            // Skip comments end '*/'
            if ($c === '/' && $j > 0 && $css[$j-1] === '*') {
                $cstart = strrpos(substr($css, 0, $j-1), '/*');
                if ($cstart !== false) { $j = $cstart; continue; }
            }
        }
        $selector = trim(substr($css, $start, $brace - $start));
        // Bilancia graffe
        $depth = 1;
        $k = $brace + 1;
        while ($k < $len && $depth > 0) {
            $ch = $css[$k];
            if ($ch === '{') $depth++;
            elseif ($ch === '}') $depth--;
            $k++;
        }
        $body = substr($css, $brace, $k - $brace);
        $full = $selector . ' ' . $body;

        // @media / @supports: ricorri sui blocchi interni
        if (str_starts_with($selector, '@media') || str_starts_with($selector, '@supports')
            || str_starts_with($selector, '@-moz-document') || str_starts_with($selector, '@layer')) {
            $inner = substr($css, $brace + 1, $k - $brace - 2);
            $sub = extractCssBlocks($inner, $needles);
            foreach ($sub as $s) {
                $blocks[] = "/* inside: $selector */\n" . $s;
            }
        } else {
            foreach ($needles as $n) {
                if (stripos($full, $n) !== false) {
                    $blocks[] = $full;
                    break;
                }
            }
        }
        $i = $k;
    }
    return $blocks;
}

// ===================== TASK 1+2: CSS =====================
echo "[2/6] Estraggo CSS...\n";
$cssIdx = [];
$achCombined = "/* OGame achievement-related CSS — concatenated extraction */\n\n";
$counter = 0;
foreach ($entries as $e) {
    $resp = $e['response'] ?? [];
    $content = $resp['content'] ?? [];
    $mime = $content['mimeType'] ?? '';
    if (stripos($mime, 'text/css') === false) continue;
    $url = $e['request']['url'] ?? '';
    $body = decodeContent($content);
    if ($body === '') continue;
    $counter++;
    $fname = safeName($url, $counter, 'css');
    file_put_contents("$DIRCSS/$fname", $body);

    // Match selettori
    $matched = [];
    foreach ($ACH_SELECTORS as $sel) {
        if (stripos($body, $sel) !== false) $matched[] = $sel;
    }
    $cssIdx[] = [
        'file' => $fname,
        'url'  => $url,
        'size' => strlen($body),
        'matched' => $matched,
    ];

    if (!empty($matched)) {
        $blocks = extractCssBlocks($body, $ACH_SELECTORS);
        if ($blocks) {
            $achCombined .= "/* ============================================ */\n";
            $achCombined .= "/* SOURCE: $url */\n";
            $achCombined .= "/* FILE  : $fname (" . count($blocks) . " blocks) */\n";
            $achCombined .= "/* ============================================ */\n";
            $achCombined .= implode("\n\n", $blocks) . "\n\n";
        }
    }
}
file_put_contents("$ROOT/ogame_achievement_complete.css", $achCombined);

// _index.md css
$md = "# CSS extraction index\n\nGenerated: " . date('c') . "\n\n";
$md .= "| File | URL | Size | Matched achievement selectors |\n";
$md .= "|------|-----|------|------------------------------|\n";
foreach ($cssIdx as $row) {
    $matched = $row['matched'] ? implode(', ', $row['matched']) : '-';
    $md .= "| `{$row['file']}` | `{$row['url']}` | " . number_format($row['size']) . " | $matched |\n";
}
file_put_contents("$DIRCSS/_index.md", $md);
echo "    CSS files: $counter, achievement-relevant: " .
    count(array_filter($cssIdx, fn($r) => !empty($r['matched']))) . "\n";

// ===================== TASK 3: JS =====================
echo "[3/6] Estraggo JS achievement...\n";
$JS_NEEDLES = [
    'showAchievementTier', 'changeAchievementCategory',
    'expandAllAchievementTiers', 'collapseAllAchievementTiers', 'filterAchievements',
    'selectProfilePicture', 'selectSpaceObjectSkin', 'selectProfileTitle',
    'tierstar', 'achievementTier', 'currentSelection', 'profilePictureSelectBtn',
];
$jsCount = 0; $jsKept = 0;
foreach ($entries as $e) {
    $resp = $e['response'] ?? [];
    $content = $resp['content'] ?? [];
    $mime = $content['mimeType'] ?? '';
    if (stripos($mime, 'javascript') === false) continue;
    $jsCount++;
    $url = $e['request']['url'] ?? '';
    $body = decodeContent($content);
    if ($body === '') continue;
    $hits = [];
    foreach ($JS_NEEDLES as $n) {
        if (strpos($body, $n) !== false) $hits[] = $n;
    }
    if (!$hits) continue;
    $jsKept++;
    $fname = safeName($url, $jsKept, 'js');
    $hdr = "/* SOURCE: $url\n * size: " . strlen($body) . "\n * matched: " . implode(', ', $hits) . "\n */\n\n";
    file_put_contents("$DIRJS/$fname", $hdr . $body);
}
echo "    JS scanned: $jsCount, kept: $jsKept\n";

// ===================== TASK 4: ASSET =====================
echo "[4/6] Estraggo asset (tierstar/lock/avatar/achievements/buttons)...\n";
$IMG_NEEDLES = ['tierstar', 'lock', 'padlock', '/avatars/', '/achievements/', '/buttons/', 'gradient'];
$imgIdx = [];
foreach ($entries as $e) {
    $resp = $e['response'] ?? [];
    $content = $resp['content'] ?? [];
    $mime = $content['mimeType'] ?? '';
    if (stripos($mime, 'image/') === false) continue;
    $url = $e['request']['url'] ?? '';
    $hit = null;
    foreach ($IMG_NEEDLES as $n) {
        if (stripos($url, $n) !== false) { $hit = $n; break; }
    }
    if (!$hit) continue;
    if (!isset($content['text']) || $content['text'] === '') continue;
    $bin = ($content['encoding'] ?? '') === 'base64' ? base64_decode($content['text']) : $content['text'];
    $name = basename(parse_url($url, PHP_URL_PATH) ?: '');
    if ($name === '' || $name === '/') $name = 'asset_' . substr(md5($url), 0, 8);
    // Evita collisioni: se esiste già con stesso nome ma diverso contenuto, prefissa hash
    $dst = "$DIRIMG/$name";
    if (file_exists($dst) && md5_file($dst) !== md5($bin)) {
        $name = substr(md5($url), 0, 6) . "_$name";
        $dst = "$DIRIMG/$name";
    }
    file_put_contents($dst, $bin);
    $imgIdx[] = ['file' => $name, 'url' => $url, 'size' => strlen($bin), 'hit' => $hit];
}
$md = "# Asset achievement index\n\n";
$md .= "| File | URL | Size | Match |\n|------|-----|------|-------|\n";
foreach ($imgIdx as $r) {
    $md .= "| `{$r['file']}` | `{$r['url']}` | " . number_format($r['size']) . " | {$r['hit']} |\n";
}
file_put_contents("$DIRIMG/_index.md", $md);
echo "    Asset salvati: " . count($imgIdx) . "\n";

// ===================== TASK 5: HTML PLAYERPROFILE =====================
echo "[5/6] Estraggo HTML playerprofile...\n";
$bestHtml = null; $bestUrl = null; $bestLen = 0;
foreach ($entries as $e) {
    $resp = $e['response'] ?? [];
    $content = $resp['content'] ?? [];
    $mime = $content['mimeType'] ?? '';
    if (stripos($mime, 'text/html') === false) continue;
    $url = $e['request']['url'] ?? '';
    $body = decodeContent($content);
    if (stripos($url, 'playerprofile') === false && stripos($body, 'achievementsoverviewcomponent') === false) continue;
    if (stripos($body, 'achievementsoverviewcomponent') !== false && strlen($body) > $bestLen) {
        $bestHtml = $body; $bestUrl = $url; $bestLen = strlen($body);
    }
}
if ($bestHtml) {
    file_put_contents("$ROOT/ogame_trofei_full.html",
        "<!-- SOURCE: $bestUrl\n     size: $bestLen -->\n" . $bestHtml);
    echo "    HTML salvato: $bestLen B  url=$bestUrl\n";

    // Estrai blocco achievementsoverviewcomponent
    if (preg_match('/<div[^>]*id=["\']achievementsoverviewcomponent["\'][^>]*>/i', $bestHtml, $m, PREG_OFFSET_CAPTURE)) {
        $startTag = $m[0][1];
        // Bilancia <div>
        $pos = $startTag + strlen($m[0][0]);
        $depth = 1; $len = strlen($bestHtml);
        while ($pos < $len && $depth > 0) {
            $next = preg_match('/<\/?div[\s>]/i', $bestHtml, $mm, PREG_OFFSET_CAPTURE, $pos);
            if (!$next) break;
            $tag = $mm[0][0]; $at = $mm[0][1];
            if (str_starts_with($tag, '</')) $depth--; else $depth++;
            $pos = $at + strlen($tag);
        }
        $component = substr($bestHtml, $startTag, $pos - $startTag);
        file_put_contents("$ROOT/ogame_trofei_component.html", $component);
        echo "    Component achievementsoverviewcomponent: " . strlen($component) . " B\n";

        // Schemi: estrai un campione per categoria
        $schemas = "# OGame trofei — schemi HTML autoritativi\n\nEstratti da `$bestUrl`\n\n";

        // 1 achievement holder completo (primo achievementOverviewAchievementHolder)
        if (preg_match('/<li[^>]*class=["\'][^"\']*achievementOverviewAchievementHolder[^"\']*["\'][^>]*>/i', $component, $am, PREG_OFFSET_CAPTURE)) {
            $s = $am[0][1];
            $pos = $s + strlen($am[0][0]); $depth = 1;
            while ($pos < strlen($component) && $depth > 0) {
                if (!preg_match('/<\/?li[\s>]/i', $component, $mm, PREG_OFFSET_CAPTURE, $pos)) break;
                if (str_starts_with($mm[0][0], '</')) $depth--; else $depth++;
                $pos = $mm[0][1] + strlen($mm[0][0]);
            }
            $schemas .= "## Achievement holder completo (5 tier)\n\n```html\n" . substr($component, $s, $pos - $s) . "\n```\n\n";
        }

        // Cerca avatar / skin / title holder unlocked + locked
        $patterns = [
            'avatar unlocked'  => '/<li[^>]*class=["\'][^"\']*profile-picture(?![^"\']*locked)[^"\']*["\'][^>]*>.*?<\/li>/is',
            'avatar locked'    => '/<li[^>]*class=["\'][^"\']*profile-picture[^"\']*locked[^"\']*["\'][^>]*>.*?<\/li>/is',
            'skin unlocked'    => '/<li[^>]*class=["\'][^"\']*space-object-skin(?![^"\']*locked)[^"\']*["\'][^>]*>.*?<\/li>/is',
            'skin locked'      => '/<li[^>]*class=["\'][^"\']*space-object-skin[^"\']*locked[^"\']*["\'][^>]*>.*?<\/li>/is',
            'title unlocked'   => '/<li[^>]*class=["\'][^"\']*titleHolder(?![^"\']*locked)[^"\']*["\'][^>]*>.*?<\/li>/is',
            'title locked'     => '/<li[^>]*class=["\'][^"\']*titleHolder[^"\']*locked[^"\']*["\'][^>]*>.*?<\/li>/is',
        ];
        foreach ($patterns as $label => $p) {
            if (preg_match($p, $component, $mm)) {
                $schemas .= "## $label\n\n```html\n" . $mm[0] . "\n```\n\n";
            } else {
                $schemas .= "## $label\n\n_(pattern non trovato nell'HTML — selettore variante necessaria)_\n\n";
            }
        }
        file_put_contents("$ROOT/ogame_trofei_schemas.md", $schemas);
    }
} else {
    echo "    !! Nessun HTML playerprofile contenente achievementsoverviewcomponent trovato\n";
}

// ===================== TASK 6: REPORT =====================
echo "[6/6] Riepilogo finale\n";
$mimeBuckets = ['css'=>0,'js'=>0,'html'=>0,'image'=>0,'other'=>0];
foreach ($entries as $e) {
    $m = $e['response']['content']['mimeType'] ?? '';
    if (stripos($m,'text/css')!==false) $mimeBuckets['css']++;
    elseif (stripos($m,'javascript')!==false) $mimeBuckets['js']++;
    elseif (stripos($m,'text/html')!==false) $mimeBuckets['html']++;
    elseif (stripos($m,'image/')!==false) $mimeBuckets['image']++;
    else $mimeBuckets['other']++;
}
echo "    Breakdown: css={$mimeBuckets['css']} js={$mimeBuckets['js']} html={$mimeBuckets['html']} image={$mimeBuckets['image']} other={$mimeBuckets['other']}\n";
echo "FINE.\n";
