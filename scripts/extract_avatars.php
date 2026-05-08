<?php
/**
 * Estrae i 60 avatar bonus dal HAR.
 *
 * NOTA: il HAR cattura SOLO immagini, niente CSS/HTML/AJAX. Pertanto:
 * - mappiamo i 60 file image/jpeg consecutivi (entries 91..150) come avatar bonus
 * - assegniamo machine_name posizionale A1_T2_Ava_ID{N} (N=1..60) seguendo
 *   l'ordine dei placeholder noti già usati nel resto della codebase
 * - i bytes immagine sono incorporati base64 nel HAR (text + encoding="base64")
 */
ini_set('memory_limit', '2G');
$harPath = 'D:/OGAME WEB/s275-it.ogame.gameforge.com.har';
$outJson = 'D:/ogame/database/seeders/data/avatars_extracted.json';
$outDir  = 'D:/ogame/public/img/achievements/avatars';

$json = json_decode(file_get_contents($harPath), true);
$entries = $json['log']['entries'] ?? [];

// Filtra le 60 jpeg (avatar bonus)
$avatars = [];
foreach ($entries as $e) {
    $mime = $e['response']['content']['mimeType'] ?? '';
    if ($mime !== 'image/jpeg') continue;
    $url  = $e['request']['url'] ?? '';
    if (!str_contains($url, 'gfsrv.net')) continue;
    $avatars[] = $e;
}

echo "Trovate ".count($avatars)." immagini jpeg da gfsrv (candidate avatar)\n";

if (count($avatars) !== 60) {
    echo "ATTENZIONE: attese 60, trovate ".count($avatars)."\n";
}

$out = [];
$saved = 0;
foreach ($avatars as $idx => $e) {
    $n = $idx + 1;
    $machine = "A1_T2_Ava_ID{$n}";
    $url     = $e['request']['url'];
    $content = $e['response']['content'];
    $text    = $content['text'] ?? '';
    $encoding = $content['encoding'] ?? '';
    $bytes = null;
    if ($encoding === 'base64' && $text !== '') {
        $bytes = base64_decode($text);
    } elseif ($text !== '') {
        $bytes = $text; // raw
    }
    $out[] = [
        'machine_name' => $machine,
        'image_url'    => $url,
        'size'         => $content['size'] ?? 0,
    ];
    if ($bytes !== null) {
        $dest = $outDir.'/'.$machine.'.jpg';
        file_put_contents($dest, $bytes);
        $saved++;
    }
}

file_put_contents($outJson, json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo "Scritti $saved file in $outDir\n";
echo "Manifest JSON: $outJson (".count($out)." entries)\n";
