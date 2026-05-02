<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Request-Private-Network');
header('Access-Control-Allow-Private-Network: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'POST only'; exit; }
$lang = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', $_GET['lang']) : '';
if (!$lang) { http_response_code(400); echo 'bad lang'; exit; }
$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo 'empty'; exit; }
$dir = __DIR__ . '/shop_items';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$path = $dir . '/' . $lang . '_charclass.json';
file_put_contents($path, $body);
echo 'ok ' . strlen($body) . ' -> ' . $path;
