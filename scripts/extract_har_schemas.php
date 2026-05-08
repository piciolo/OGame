<?php
/**
 * extract_har_schemas.php — riprende il component già estratto e produce
 * gli schemi HTML autoritativi corretti (avatar/skin/title holder + 1 achievement holder).
 *
 * Inoltre: scarica eventuali asset /cdn/img/avatars/* mancanti dal HAR (per avatar reali).
 */

ini_set('memory_limit', '2048M');
$ROOT      = 'D:/ogame/_research';
$component = file_get_contents("$ROOT/ogame_trofei_component.html");
if (!$component) die("component file missing\n");

/**
 * Estrae il primo elemento bilanciando un tag indicato.
 * $start = offset di '<', $tag = nome tag (es. 'div').
 * Ritorna [offset_end_exclusive, html] oppure null.
 */
function extractBalanced(string $html, int $start, string $tag): ?array {
    $len = strlen($html);
    // Trova fine tag d'apertura
    $closeOpen = strpos($html, '>', $start);
    if ($closeOpen === false) return null;
    // Self-closing?
    if ($html[$closeOpen-1] === '/') {
        return [$closeOpen + 1, substr($html, $start, $closeOpen + 1 - $start)];
    }
    $depth = 1;
    $pos = $closeOpen + 1;
    $re = '/<\/?' . preg_quote($tag, '/') . '\b[^>]*>/i';
    while ($pos < $len && $depth > 0) {
        if (!preg_match($re, $html, $m, PREG_OFFSET_CAPTURE, $pos)) break;
        $tagStr = $m[0][0];
        $tagAt  = $m[0][1];
        if (str_starts_with($tagStr, '</')) $depth--;
        elseif (!str_ends_with($tagStr, '/>')) $depth++;
        $pos = $tagAt + strlen($tagStr);
    }
    return [$pos, substr($html, $start, $pos - $start)];
}

function findFirst(string $html, string $regex, string $tag): ?string {
    if (!preg_match($regex, $html, $m, PREG_OFFSET_CAPTURE)) return null;
    $r = extractBalanced($html, $m[0][1], $tag);
    return $r ? $r[1] : null;
}

function findFirstWithFilter(string $html, string $regexOpen, string $tag, callable $filter): ?string {
    $offset = 0;
    while (preg_match($regexOpen, $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $r = extractBalanced($html, $start, $tag);
        if (!$r) return null;
        if ($filter($r[1])) return $r[1];
        $offset = $r[0];
    }
    return null;
}

$schemas = "# OGame trofei — schemi HTML autoritativi\n\nEstratti da `ogame_trofei_component.html` (s275-it.ogame.gameforge.com playerprofile)\n\n";

// 1) achievement holder completo (5 tier)
$ach = findFirst(
    $component,
    '/<div\s+id="achievementOverviewAchievementHolder_\d+"\s+class="achievementOverviewAchievementHolder[^"]*">/i',
    'div'
);
if ($ach) {
    $schemas .= "## 1. Achievement holder (5 tier)\n\n";
    $schemas .= "Markup completo del primo `achievementOverviewAchievementHolder` (categoria `unlocks`):\n\n";
    $schemas .= "```html\n" . trim($ach) . "\n```\n\n";
}

// 2) Avatar holder unlocked + locked (achievementOverviewProfilePictureHolder)
$schemas .= "## 2. Avatar holder (`achievementOverviewProfilePictureHolder`)\n\n";

$avatarUnlocked = findFirstWithFilter(
    $component,
    '/<div\s+class="achievementOverviewProfilePictureHolder[^"]*"[^>]*>/i',
    'div',
    fn(string $h) => stripos($h, 'locked') === false || stripos($h, ' locked') === false
);
// fallback: any holder without 'locked' substring
$avatarLocked = null;
$offset = 0;
while (preg_match('/<div\s+class="achievementOverviewProfilePictureHolder[^"]*"[^>]*>/i', $component, $m, PREG_OFFSET_CAPTURE, $offset)) {
    $r = extractBalanced($component, $m[0][1], 'div');
    if (!$r) break;
    $isLocked = (bool)preg_match('/\blocked\b/', $r[1]);
    if ($isLocked && !$avatarLocked) $avatarLocked = $r[1];
    if (!$isLocked && !$avatarUnlocked) $avatarUnlocked = $r[1];
    if ($avatarLocked && $avatarUnlocked) break;
    $offset = $r[0];
}
if ($avatarUnlocked) $schemas .= "### unlocked\n\n```html\n" . trim($avatarUnlocked) . "\n```\n\n";
else $schemas .= "### unlocked\n\n_(non presente in questa pagina — tutti gli avatar sono locked per questo profilo di test)_\n\n";
if ($avatarLocked)   $schemas .= "### locked\n\n```html\n" . trim($avatarLocked) . "\n```\n\n";

// 3) Skin holder
$schemas .= "## 3. Skin holder (`achievementOverviewSpaceObjectSkinHolder`)\n\n";
$skinU = null; $skinL = null; $offset = 0;
while (preg_match('/<div\s+class="achievementOverviewSpaceObjectSkinHolder[^"]*"[^>]*>/i', $component, $m, PREG_OFFSET_CAPTURE, $offset)) {
    $r = extractBalanced($component, $m[0][1], 'div');
    if (!$r) break;
    $isLocked = (bool)preg_match('/\blocked\b/', $r[1]);
    if ($isLocked && !$skinL) $skinL = $r[1];
    if (!$isLocked && !$skinU) $skinU = $r[1];
    if ($skinU && $skinL) break;
    $offset = $r[0];
}
if ($skinU) $schemas .= "### unlocked\n\n```html\n" . trim($skinU) . "\n```\n\n";
else $schemas .= "### unlocked\n\n_(non presente — questo profilo di test ha tutte le skin locked)_\n\n";
if ($skinL) $schemas .= "### locked\n\n```html\n" . trim($skinL) . "\n```\n\n";

// 4) Title holder (achievementOverviewProfileTitleHolder)
$schemas .= "## 4. Titolo holder (`achievementOverviewProfileTitleHolder`)\n\n";
$titU = null; $titL = null; $offset = 0;
while (preg_match('/<div\s+class="achievementOverviewProfileTitleHolder[^"]*"[^>]*>/i', $component, $m, PREG_OFFSET_CAPTURE, $offset)) {
    $r = extractBalanced($component, $m[0][1], 'div');
    if (!$r) break;
    $isLocked = (bool)preg_match('/\blocked\b/', $r[1]);
    if ($isLocked && !$titL) $titL = $r[1];
    if (!$isLocked && !$titU) $titU = $r[1];
    if ($titU && $titL) break;
    $offset = $r[0];
}
if ($titU) $schemas .= "### unlocked\n\n```html\n" . trim($titU) . "\n```\n\n";
else $schemas .= "### unlocked\n\n_(non presente — profilo test ha tutti i titoli locked)_\n\n";
if ($titL) $schemas .= "### locked\n\n```html\n" . trim($titL) . "\n```\n\n";

// 5) titleHolder generico (uno: pulsante che apre selettore)
if (preg_match('/<div\s+class="titleHolder"[^>]*>/i', $component, $m, PREG_OFFSET_CAPTURE)) {
    $r = extractBalanced($component, $m[0][1], 'div');
    if ($r) $schemas .= "## 5. titleHolder (selettore titolo corrente)\n\n```html\n" . trim($r[1]) . "\n```\n\n";
}

file_put_contents("$ROOT/ogame_trofei_schemas.md", $schemas);
echo "Schemi scritti: " . strlen($schemas) . " B\n";

// Stats
$counts = [
    'achievementHolder'           => substr_count($component, 'achievementOverviewAchievementHolder_'),
    'avatarHolder'                => substr_count($component, 'achievementOverviewProfilePictureHolder'),
    'skinHolder'                  => substr_count($component, 'achievementOverviewSpaceObjectSkinHolder'),
    'titleHolder (achievementOverview)' => substr_count($component, 'achievementOverviewProfileTitleHolder'),
    'titleHolder (currentSelection)'    => substr_count($component, 'class="titleHolder"'),
    'tier_1..5 ::class'           => preg_match_all('/\btier_[1-5]\b/', $component),
    'tierstar img'                => substr_count($component, 'tierstar.png'),
    'locked'                      => substr_count($component, ' locked '),
    'unlocked'                    => substr_count($component, ' unlocked '),
];
foreach ($counts as $k => $v) echo "  $k: $v\n";
