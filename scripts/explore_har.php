<?php
ini_set('memory_limit', '2G');
$harPath = 'D:/OGAME WEB/s275-it.ogame.gameforge.com.har';
$json = json_decode(file_get_contents($harPath), true);
$entries = $json['log']['entries'] ?? [];
echo "Totale entries: ".count($entries)."\n\n";

// Mappa per tipo MIME
$byMime = [];
foreach ($entries as $i => $e) {
    $mime = $e['response']['content']['mimeType'] ?? '?';
    $byMime[$mime] = ($byMime[$mime] ?? 0) + 1;
}
echo "=== MIME breakdown ===\n";
foreach ($byMime as $m=>$c) echo "  $c × $m\n";

echo "\n=== Tutte le URL (151) ===\n";
foreach ($entries as $i => $e) {
    $url = $e['request']['url'] ?? '';
    $mime = $e['response']['content']['mimeType'] ?? '?';
    $sz = $e['response']['content']['size'] ?? 0;
    echo " [$i] sz=$sz $mime  $url\n";
}
