<?php
/**
 * Build resources/lang/<loc>/t_shop_items_data.php from a scrape JSON.
 *
 * Reads:
 *   - _research/i18n_scrape/shop_items/<loc>.json   (91 shop items + tooltip data)
 *   - resources/lang/<loc>/t_shop_items.php         (booster_*_desc + tier_* patterns)
 *
 * Writes:
 *   - resources/lang/<loc>/t_shop_items_data.php    (91 + 21 booster = 112 entries)
 *
 * The 21 KRAKEN/DETROID/NEWTRON booster items are auctioneer-only and not
 * visible in the regular shop, so they're built from existing translation
 * patterns in t_shop_items.php (booster_kraken_desc / booster_newtron_desc /
 * booster_detroid_desc) with the per-tier reduction substituted inline.
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php build_lang_data.php <locale>\n");
    exit(1);
}
$loc = $argv[1];

$jsonPath = __DIR__ . "/shop_items/$loc.json";
if (!file_exists($jsonPath)) {
    fwrite(STDERR, "Missing $jsonPath\n");
    exit(1);
}
$json = json_decode(file_get_contents($jsonPath), true);
$items = $json['items'];

// Load lang patterns
$langPath = dirname(__DIR__, 2) . "/resources/lang/$loc/t_shop_items.php";
if (!file_exists($langPath)) {
    fwrite(STDERR, "Missing $langPath\n");
    exit(1);
}
$lang = require $langPath;

// 21 auctioneer booster items: ref => [type, tier, isLifeforms, reduction]
// Pattern derivation:
//   name = "<tier> <TYPE>" (or "<tier> <TYPE> (Lifeforms)" for lifeforms variants)
//   description = booster_<type>_desc with :reduction substituted
//   duration_label = duration_instant
//
// Reductions per tier (from OGame canonical):
//   Bronze=-30m, Silver=-2h, Gold=-6h, Platinum=-1d
// Note: the IT DB stores 'g' (giorno) and 'o' (ora). For other languages we
// use canonical English short forms baked in the JSON below to keep the file
// universal. To get exact per-locale duration suffixes, scrape the auctioneer
// page in that locale; otherwise the EN abbreviations are visible to users.
$boosterMeta = [
    // KRAKEN
    '40f6c78e11be01ad3389b7dccd6ab8efa9347f3c' => ['type' => 'kraken', 'tier' => 'bronze',  'lf' => false, 'red' => '-30m'],
    '4a58d4978bbe24e3efb3b0248e21b3b4b1bfbd8a' => ['type' => 'kraken', 'tier' => 'silver',  'lf' => false, 'red' => '-2h'],
    '929d5e15709cc51a4500de4499e19763c879f7f7' => ['type' => 'kraken', 'tier' => 'gold',    'lf' => false, 'red' => '-6h'],
    'f36042d76e6b8b33d931e1d4ae99f35265cd82d1' => ['type' => 'kraken', 'tier' => 'platinum','lf' => false, 'red' => '-1d'],
    '00b42f7113d81f98df865bbfa2280fe3a4465e89' => ['type' => 'kraken', 'tier' => 'bronze',  'lf' => true,  'red' => '-30m'],
    '3c3d356f63dafe10dbd2562b126b4a5104ac8dfc' => ['type' => 'kraken', 'tier' => 'bronze',  'lf' => true,  'red' => '-30m'],
    '5f194777c5b69d5c2a3c68e9e04a4cae9c28bcf2' => ['type' => 'kraken', 'tier' => 'silver',  'lf' => true,  'red' => '-2h'],
    '0ad06bba14dfd0b576f1daef729a60753e2263c7' => ['type' => 'kraken', 'tier' => 'gold',    'lf' => true,  'red' => '-6h'],
    'c19f0e09d862d93d7956beb3185d9ee929b5ef74' => ['type' => 'kraken', 'tier' => 'platinum','lf' => true,  'red' => '-1d'],
    // NEWTRON
    'da4a2a1bb9afd410be07bc9736d87f1c8059e66d' => ['type' => 'newtron','tier' => 'bronze',  'lf' => false, 'red' => '30m'],
    'd26f4dab76fdc5296e3ebec11a1e1d2558c713ea' => ['type' => 'newtron','tier' => 'silver',  'lf' => false, 'red' => '2h'],
    '8a4f9e8309e1078f7f5ced47d558d30ae15b4a1b' => ['type' => 'newtron','tier' => 'gold',    'lf' => false, 'red' => '6h'],
    'a1ba242ede5286b530cdf991796b3d1cae9e4f23' => ['type' => 'newtron','tier' => 'platinum','lf' => false, 'red' => '1d'],
    'ba3e6693f112986b7964c835bcac6ae201900e2f' => ['type' => 'newtron','tier' => 'bronze',  'lf' => true,  'red' => '30m'],
    '9879a36c42797a868416b13f07e033f664cabd70' => ['type' => 'newtron','tier' => 'silver',  'lf' => true,  'red' => '2h'],
    '7fe4cdb098685f8af827ca460a56e00ef46f5f05' => ['type' => 'newtron','tier' => 'gold',    'lf' => true,  'red' => '6h'],
    '9cde936fabc5037617f8261955e7d3f2262eec69' => ['type' => 'newtron','tier' => 'platinum','lf' => true,  'red' => '1d'],
    // DETROID
    'd3d541ecc23e4daa0c698e44c32f04afd2037d84' => ['type' => 'detroid','tier' => 'bronze',  'lf' => false, 'red' => '-30m'],
    '27cbcd52f16693023cb966e5026d8a1efbbfc0f9' => ['type' => 'detroid','tier' => 'silver',  'lf' => false, 'red' => '-2h'],
    '0968999df2fe956aa4a07aea74921f860af7d97f' => ['type' => 'detroid','tier' => 'gold',    'lf' => false, 'red' => '-6h'],
    '3347bcd4ee59f1d3fa03c4d18a25bca2da81de82' => ['type' => 'detroid','tier' => 'platinum','lf' => false, 'red' => '-1d'],
];

$lifeformsLabel = [
    'en' => 'Lifeforms', 'us' => 'Lifeforms', 'fi' => 'Lifeforms',
    'de' => 'Lebensformen',
    'it' => 'Forme di vita',
    'nl' => 'Levensvormen',
    'fr' => 'Formes de vie',
    'es' => 'Formas de vida', 'mx' => 'Formas de vida', 'ar' => 'Formas de vida',
    'br' => 'Formas de vida', 'pt' => 'Formas de vida',
    'pl' => 'Formy życia',
    'ru' => 'Формы жизни',
    'tr' => 'Yaşam formları',
    'jp' => 'ライフフォーム',
    'tw' => '生命形式',
    'cz' => 'Formy života',
    'hu' => 'Életformák',
    'ro' => 'Forme de viață',
    'gr' => 'Μορφές ζωής',
    'dk' => 'Livsformer',
    'se' => 'Livsformer',
    'sk' => 'Formy života',
    'si' => 'Življenjske oblike',
    'hr' => 'Životni oblici',
    'yu' => 'Životni oblici',
];
$lf = $lifeformsLabel[$loc] ?? 'Lifeforms';

$durationInstant = $lang['duration_instant'] ?? 'now';

$out = "<?php\n\n";
$out .= "// Per-ref shop item translations sourced from official OGame.\n";
$out .= "// Source: " . $json['source'] . "\n";
$out .= "// Captured: " . $json['captured_at'] . "\n";
$out .= "// Format: ref => ['name' => ..., 'description' => ..., 'duration_label' => ...]\n";
$out .= "//\n";
$out .= "// Shop-visible items: " . count($items) . "\n";
$out .= "// Auctioneer-only booster items: " . count($boosterMeta) . " (KRAKEN/DETROID/NEWTRON × tier × lifeforms variant)\n\n";

$out .= "return [\n";

// Shop items from JSON
foreach ($items as $it) {
    $ref = $it['r'];
    $name = addcslashes($it['n'], "'\\");
    $desc = addcslashes($it['d'], "'\\");
    $dur  = addcslashes($it['dur'], "'\\");
    $out .= "    '$ref' => ['name' => '$name', 'description' => '$desc', 'duration_label' => '$dur'],\n";
}

$out .= "\n    // Auctioneer-only booster items (KRAKEN/DETROID/NEWTRON × tier × lifeforms variant)\n";

foreach ($boosterMeta as $ref => $m) {
    $tierLabel = $lang['tier_' . $m['tier']] ?? ucfirst($m['tier']);
    $typeName = strtoupper($m['type']);
    $name = $tierLabel . ' ' . $typeName;
    if ($m['lf']) {
        $name .= ' (' . $lf . ')';
    }
    // Description: substitute :reduction with the per-tier value
    $descKey = 'booster_' . $m['type'] . '_desc';
    $descTpl = $lang[$descKey] ?? '';
    $desc = str_replace(':reduction', $m['red'], $descTpl);

    $name = addcslashes($name, "'\\");
    $desc = addcslashes($desc, "'\\");
    $dur = addcslashes($durationInstant, "'\\");
    $out .= "    '$ref' => ['name' => '$name', 'description' => '$desc', 'duration_label' => '$dur'],\n";
}

$out .= "];\n";

$path = dirname(__DIR__, 2) . "/resources/lang/$loc/t_shop_items_data.php";
file_put_contents($path, $out);
echo "Written $path with " . (count($items) + count($boosterMeta)) . " entries\n";
