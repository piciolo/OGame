<?php
ini_set('memory_limit','2G');
ini_set('pcre.backtrack_limit','100000000');

$harPath = 'D:/OGAME WEB/s275-it.ogame.gameforge.com.har';
$cssOut  = 'D:/ogame/_research/ogame_scrollbar.css';
$titlesOut = 'D:/ogame/database/seeders/data/titles_extracted.json';
$avatarsOut = 'D:/ogame/database/seeders/data/avatars_authoritative.json';
$avatarImgDir = 'D:/ogame/public/img/achievements/avatars/';

$har = json_decode(file_get_contents($harPath), true);
function dec($c){ $t=$c['text']??''; if($t==='')return''; return ($c['encoding']??'')==='base64'?base64_decode($t):$t; }
$entries = $har['log']['entries'];

// ========== TASK A: scrollbar CSS ==========
$cssBlocks = [];
$cssWith = 0;
foreach ($entries as $e) {
    $mt = strtolower($e['response']['content']['mimeType']??'');
    if (strpos($mt,'text/css')!==0) continue;
    $body = dec($e['response']['content']);
    if ($body==='') continue;
    $url = $e['request']['url']??'';
    $found = [];
    if (preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $r) {
            $sel = trim($r[1]); $bod = trim($r[2]);
            $hit = false;
            if (stripos($sel,'scrollbar')!==false) $hit=true;
            elseif (stripos($bod,'scrollbar-width')!==false || stripos($bod,'scrollbar-color')!==false) $hit=true;
            elseif (stripos($sel,'achievementsOverviewList')!==false
                 || stripos($sel,'achievementContentList')!==false) $hit=true;
            if ($hit) $found[] = $sel . " {\n  " . $bod . "\n}";
        }
    }
    if ($found) { $cssWith++; $cssBlocks[] = "/* SOURCE: $url */\n" . implode("\n\n", $found); }
}
file_put_contents($cssOut,
    "/* OGame scrollbar + achievement CSS extracted from HAR */\n/* Generated: ".date('c')." */\n\n".
    implode("\n\n/* ---- */\n\n",$cssBlocks)."\n");

// ========== TASK B: titoli ==========
// pattern: data-title-id="KEY" ... <div class="titleHolder" lang="it">TESTO</div>
$titles = [];
foreach ($entries as $e) {
    $mt = strtolower($e['response']['content']['mimeType']??'');
    if (strpos($mt,'text/html')!==0) continue;
    $body = dec($e['response']['content']);
    if ($body==='' || stripos($body,'_Tit_ID')===false) continue;
    if (preg_match_all('/data-title-id="(A\d+_T\d+_Tit_ID\d+)"[^>]*>\s*<div\s+class="titleHolder"\s+lang="[a-z]+">([^<]+)<\/div>/si', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $row) {
            $key = $row[1]; $val = trim(html_entity_decode($row[2], ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if ($val!=='' && !isset($titles[$key])) $titles[$key] = $val;
        }
    }
    // pattern 2 con ordine inverso o whitespace differente, fallback più ampio
    if (preg_match_all('/data-title-id="(A\d+_T\d+_Tit_ID\d+)"(.{0,400}?)<div\s+class="titleHolder"[^>]*>([^<]+)<\/div>/si', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $row) {
            $key = $row[1]; $val = trim(html_entity_decode($row[3], ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if ($val!=='' && !isset($titles[$key])) $titles[$key] = $val;
        }
    }
}
ksort($titles);
file_put_contents($titlesOut, json_encode($titles, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

// ========== TASK C: avatar mapping ==========
// Avatar urls non sono in HTML diretto: cerca nelle CSS regole come:
// .A1_T2_Ava_ID1 { background:url(...) } oppure profile-picture.A1_T2_Ava_ID1{...}
$avatars = [];
foreach ($entries as $e) {
    $mt = strtolower($e['response']['content']['mimeType']??'');
    if (strpos($mt,'text/css')!==0) continue;
    $body = dec($e['response']['content']);
    if ($body==='' || stripos($body,'_Ava_ID')===false) continue;
    $base = $e['request']['url']??'';
    // strip filename to get base
    $cssBase = preg_replace('#/[^/]+$#','/', $base);
    // Match selectors that contain Ava_ID and capture url(...) inside the rule
    if (preg_match_all('/([^{}]*A\d+_T\d+_Ava_ID\d+[^{}]*)\{([^{}]*)\}/s', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $row) {
            $sel = $row[1]; $bod = $row[2];
            // collect every Ava key in selector
            preg_match_all('/(A\d+_T\d+_Ava_ID\d+)/', $sel, $keys);
            if (!$keys[1]) continue;
            // find url(...)
            if (!preg_match('/url\(\s*["\']?([^"\')]+)["\']?\s*\)/i', $bod, $um)) continue;
            $url = $um[1];
            // resolve relative
            if (strpos($url,'//')===0) $url = 'https:'.$url;
            elseif (strpos($url,'http')!==0) $url = rtrim($cssBase,'/').'/'.ltrim($url,'/');
            foreach (array_unique($keys[1]) as $k) {
                if (!isset($avatars[$k])) $avatars[$k] = $url;
            }
        }
    }
}
ksort($avatars);
file_put_contents($avatarsOut, json_encode($avatars, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

// ========== Save images present in HAR ==========
$urlToKey = [];
foreach ($avatars as $k=>$u) {
    $clean = preg_replace('/\?.*$/','',$u);
    $urlToKey[$clean] = $k;
    $urlToKey[$u] = $k;
}
$saved=0; $skipped=0; $notFoundInHar=0;
$savedKeys=[];
foreach ($entries as $e) {
    $mt = strtolower($e['response']['content']['mimeType']??'');
    if (strpos($mt,'image/')!==0) continue;
    $url = $e['request']['url']??'';
    $clean = preg_replace('/\?.*$/','',$url);
    $key = $urlToKey[$clean] ?? $urlToKey[$url] ?? null;
    if (!$key) continue;
    $body = dec($e['response']['content']);
    if ($body==='') continue;
    $ext = 'jpg';
    if (preg_match('/\.(jpe?g|png|webp|gif)/i',$clean,$em)) $ext=strtolower($em[1]);
    $dest = $avatarImgDir.$key.'.'.$ext;
    if (file_exists($dest)) { $skipped++; continue; }
    file_put_contents($dest,$body);
    $saved++;
    $savedKeys[] = $key;
}
foreach ($avatars as $k=>$u) {
    $found=false;
    foreach (['jpg','jpeg','png','webp','gif'] as $x) if (file_exists($avatarImgDir.$k.'.'.$x)) { $found=true; break; }
    if (!$found) $notFoundInHar++;
}

echo "CSS entries with relevant rules: $cssWith\n";
echo "Titles extracted: ".count($titles)."\n";
echo "Avatars mapped: ".count($avatars)."\n";
echo "Avatar images saved: $saved, skipped existing: $skipped, missing-from-HAR: $notFoundInHar\n";
echo "Output: $titlesOut\n$avatarsOut\n$cssOut\n";
