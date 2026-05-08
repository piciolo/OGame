<?php
/**
 * extract_har_assets.php — scarica TUTTE le immagini referenziate dal component
 * achievementsoverviewcomponent (avatar reali, skin reali, icone reward, ecc.).
 *
 * Strategia: estrae da ogame_trofei_component.html tutti i src="..." e i
 * `background-image:url(...)`, poi cerca per URL nel HAR e salva il body.
 */

ini_set('memory_limit', '2048M');

$ROOT = 'D:/ogame/_research';
$HAR  = 'D:/OGAME WEB/s275-it.ogame.gameforge.com.har';
$component = file_get_contents("$ROOT/ogame_trofei_component.html");

// 1. Estrai URL immagine usati nel component
$urls = [];
if (preg_match_all('/(?:src|data-src)\s*=\s*["\']([^"\']+\.(?:png|jpg|jpeg|gif|webp|svg))["\']/i', $component, $m)) {
    foreach ($m[1] as $u) $urls[$u] = true;
}
if (preg_match_all('/url\(\s*["\']?([^)\'"]+\.(?:png|jpg|jpeg|gif|webp|svg))["\']?\s*\)/i', $component, $m)) {
    foreach ($m[1] as $u) $urls[$u] = true;
}
$urls = array_keys($urls);
echo "URL referenziati dal component: " . count($urls) . "\n";

// 2. Carica HAR e indicizza per URL
echo "Indicizzo HAR...\n";
$j = json_decode(file_get_contents($HAR), true);
$harByUrl = [];
foreach ($j['log']['entries'] as $e) {
    $u = $e['request']['url'] ?? '';
    if (!$u) continue;
    $harByUrl[$u] = $e;
}
echo "  HAR entries: " . count($harByUrl) . "\n";

// 3. Risolvi e scarica
$DIRIMG = 'D:/ogame/public/img/achievements/icons';
@mkdir($DIRIMG, 0777, true);

$saved = 0; $missing = 0; $skipped = 0;
$index = [];
foreach ($urls as $u) {
    // Normalizza URL relativi (es. /cdn/img/avatars/tierstar.png su s275-it.ogame.gameforge.com)
    $abs = $u;
    if (str_starts_with($u, '/')) {
        $abs = 'https://s275-it.ogame.gameforge.com' . $u;
    }
    if (!isset($harByUrl[$abs])) {
        // Prova varianti: alcuni asset CDN possono essere stati richiesti su gf1/2/3
        $missing++;
        $index[] = ['ref' => $u, 'status' => 'NOT_IN_HAR'];
        continue;
    }
    $entry = $harByUrl[$abs];
    $content = $entry['response']['content'] ?? [];
    $text = $content['text'] ?? '';
    if ($text === '') {
        $skipped++;
        $index[] = ['ref' => $u, 'status' => 'EMPTY_BODY'];
        continue;
    }
    $bin = ($content['encoding'] ?? '') === 'base64' ? base64_decode($text) : $text;
    $name = basename(parse_url($abs, PHP_URL_PATH) ?: '');
    if ($name === '' || $name === '/') $name = 'asset_' . substr(md5($abs), 0, 8);
    $dst = "$DIRIMG/$name";
    if (file_exists($dst) && md5_file($dst) !== md5($bin)) {
        $name = substr(md5($abs), 0, 6) . '_' . $name;
        $dst = "$DIRIMG/$name";
    }
    file_put_contents($dst, $bin);
    $saved++;
    $index[] = ['ref' => $u, 'status' => 'OK', 'file' => $name, 'size' => strlen($bin)];
}

echo "Salvati: $saved   Mancanti dal HAR: $missing   Body vuoti: $skipped\n";

// 4. Aggiorna _index.md
$md = "# Asset achievement index (rev2)\n\nReferenze trovate nel component achievementsoverviewcomponent: " . count($urls) . "\n\n";
$md .= "| Ref URL | Status | File | Size |\n|---------|--------|------|------|\n";
foreach ($index as $r) {
    $f = $r['file'] ?? '-';
    $s = isset($r['size']) ? number_format($r['size']) : '-';
    $md .= "| `{$r['ref']}` | {$r['status']} | `$f` | $s |\n";
}
file_put_contents("$DIRIMG/_index.md", $md);
echo "Indice aggiornato.\n";
