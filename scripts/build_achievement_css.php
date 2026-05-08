<?php
// Compila playerprofile_achievements.css dal CSS OGame autoritativo estratto.

$src = file_get_contents(__DIR__ . '/../_research/ogame_achievement_complete.css');

// Rimuovi le regole per-machine_name (gestite in playerprofile_avatars.css)
$src = preg_replace('/profile-picture\.A\d+_T\d+_Ava_ID\d+\s*\{[^}]*\}\s*/m', '', $src);
$src = preg_replace('/space-object-skin\.A\d+_T\d+_Pskin_ID\d+\s*\{[^}]*\}\s*/m', '', $src);

// Default avatar fallback → local
$src = str_replace(
    '//gf2.geo.gfsrv.net/cdn1e/e7eca98a47726ae2c85a595b29dd82.png',
    '/img/layout/profile-default.png',
    $src
);

$header = "/* OGame Trofei - CSS autoritativo estratto da gf3.geo.gfsrv.net/cdn22/9b6fdb87.css\n";
$header .= "   Le regole per-machine_name (profile-picture.A*, space-object-skin.A*) sono in\n";
$header .= "   playerprofile_avatars.css con path locali. */\n\n";

file_put_contents(__DIR__ . '/../resources/css/ingame/playerprofile_achievements.css', $header . $src);
echo "Wrote " . strlen($src) . " bytes\n";

// Path tierstar che il markup OGame attende
@mkdir(__DIR__ . '/../public/cdn/img/avatars', 0777, true);
copy(__DIR__ . '/../public/img/achievements/icons/tierstar.png', __DIR__ . '/../public/cdn/img/avatars/tierstar.png');
echo "tierstar copied\n";
