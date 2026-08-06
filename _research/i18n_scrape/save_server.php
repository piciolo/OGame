<?php
// Tiny POST receiver for shop scrape JSONs.
// Writes body to D:\ogame\_research\i18n_scrape\shop_items\<lang>.json
// CORS allow-all so the OGame domain can POST to 127.0.0.1.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'POST only';
    exit;
}

$lang = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', $_GET['lang']) : '';
if (!$lang || strlen($lang) < 2 || strlen($lang) > 4) {
    http_response_code(400);
    echo 'bad lang';
    exit;
}

$body = file_get_contents('php://input');
if (!$body) {
    http_response_code(400);
    echo 'empty body';
    exit;
}

$dir = __DIR__ . '/shop_items';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$path = $dir . '/' . $lang . '.json';
file_put_contents($path, $body);

echo 'ok ' . strlen($body) . ' bytes -> ' . $path;
