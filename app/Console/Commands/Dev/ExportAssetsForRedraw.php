<?php

namespace OGame\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportAssetsForRedraw extends Command
{
    protected $signature = 'ogamex:dev:export-assets-redraw
                            {--out=storage/app/redraw_assets : Output directory (relative to project root)}
                            {--zip : Also create a zip archive of the output directory}';

    protected $description = 'Esporta tutti gli asset grafici di OGame organizzati per categoria, per facilitarne il ridisegno e la sostituzione con artwork originale.';

    /** Cartelle già organizzate semanticamente: copia 1:1 sotto la categoria indicata */
    private const DIRECT_COPY = [
        'public/img/objects/buildings'        => 'strutture',
        'public/img/objects/research'         => 'ricerca',
        'public/img/objects/units'            => 'unita_navi_difesa',
        'public/img/merchant'                 => 'mercante',
        'public/img/mco/characterclasses'     => 'classi_personaggio',
        'public/img/mco/allianceclasses'      => 'classi_alleanza',
        'public/img/mco/marketplace'          => 'marketplace',
        'public/img/fleet'                    => 'flotta_missioni',
        'public/img/planets'                  => 'pianeti',
        'public/img/moons'                    => 'lune',
        'public/img/galaxy'                   => 'galassia',
        'public/img/banners'                  => 'banner',
        'public/img/headers'                  => 'header',
        'public/img/navigation'               => 'navigazione',
        'public/img/layout'                   => 'layout',
        'public/img/bg'                       => 'sfondi',
        'public/img/content'                  => 'contenuto',
        'public/img/flags'                    => 'bandiere',
        'public/img/alliance'                 => 'alleanze',
        'public/img/admin'                    => 'admin',
        'public/img/outgame'                  => 'outgame',
    ];

    /** Spritesheet che vanno lasciati interi (l'utente li ritaglia in Photoshop/GIMP) */
    private const SPRITESHEETS = [
        'public/img/resources/spriteset.png'      => 'spritesheets/risorse_spriteset.png',
        'public/img/lifeform/lifeformtype_sprite.png' => 'spritesheets/lifeform_spriteset.png',
        'public/img/sprites/7a9861be0c38bf7de05a57c56d73cf.jpg' => 'spritesheets/sprites_main.jpg',
        'public/img/facilities/e9f54b10dc4e1140ce090106d2f528.jpg' => 'spritesheets/facilities_sprite.jpg',
    ];

    /** Pattern selettore CSS → categoria. Primo match vince. */
    private const SELECTOR_CATEGORIES = [
        '/\b(resource|metal|crystal|deuterium|energy|darkmatter)\b/i' => 'risorse_icone',
        '/\b(officer|commander|admiral|engineer|geologist|technocrat)\b/i' => 'ufficiali',
        '/\b(fleet|ship|mission|movement)\b/i' => 'flotta_icone',
        '/\b(defense|defence|missile|laser|cannon)\b/i' => 'difesa_icone',
        '/\b(research|tech|technology)\b/i' => 'ricerca_icone',
        '/\b(building|facility|mine|store|silo|plant|robot|nanit|shipyard)\b/i' => 'strutture_icone',
        '/\b(alliance|ally)\b/i' => 'alleanze_icone',
        '/\b(merchant|trade|market|auction)\b/i' => 'mercante_icone',
        '/\b(message|chat|inbox|mail)\b/i' => 'messaggi_icone',
        '/\b(premium|shop|item|booster)\b/i' => 'shop_icone',
        '/\b(menu|nav|tab|button|btn)\b/i' => 'ui_icone',
        '/\b(planet|moon|colony)\b/i' => 'pianeti_icone',
        '/\b(galaxy|system|coords?)\b/i' => 'galassia_icone',
        '/\b(lifeform|lf)\b/i' => 'lifeform_icone',
        '/\b(highscore|score|rank)\b/i' => 'classifica_icone',
    ];

    public function handle(): int
    {
        $base = base_path();
        $out  = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->option('out'));

        $this->info("Esportazione asset in: {$out}");

        if (File::isDirectory($out)) {
            if (!$this->confirm("La directory di output esiste già. Vuoi svuotarla e ricominciare?", true)) {
                $this->warn('Operazione annullata.');
                return self::FAILURE;
            }
            File::deleteDirectory($out);
        }
        File::makeDirectory($out, 0755, true);

        $totals = ['copied' => 0, 'mapped' => 0, 'unmapped' => 0];

        // 1) Copia diretta cartelle già organizzate
        $this->info('--- Copia cartelle organizzate ---');
        foreach (self::DIRECT_COPY as $src => $dstCat) {
            $srcPath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $src);
            if (!File::isDirectory($srcPath)) {
                $this->line("  skip (non esiste): {$src}");
                continue;
            }
            $dstPath = $out . DIRECTORY_SEPARATOR . $dstCat;
            File::copyDirectory($srcPath, $dstPath);
            $count = $this->countFiles($dstPath);
            $totals['copied'] += $count;
            $this->line("  ok: {$src} → {$dstCat}/  ({$count} file)");
        }

        // 2) Copia spritesheet
        $this->info('--- Spritesheet (da ritagliare manualmente) ---');
        foreach (self::SPRITESHEETS as $src => $dst) {
            $srcPath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $src);
            if (!File::isFile($srcPath)) {
                continue;
            }
            $dstPath = $out . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dst);
            File::ensureDirectoryExists(dirname($dstPath));
            File::copy($srcPath, $dstPath);
            $totals['copied']++;
            $this->line("  ok: {$dst}");
        }

        // 3) Categorizza icone hash via parsing CSS
        $this->info('--- Categorizzazione icone hash via CSS ---');
        $cssFiles = [
            $base . '/public/css/ingame.css',
            $base . '/public/css/outgame.css',
        ];
        $iconMap = $this->buildIconMap($cssFiles);
        $this->line('  riferimenti CSS estratti: ' . count($iconMap));

        $hashSourceDirs = [
            $base . '/public/img/icons',
            $base . '/public/img/new',
        ];

        $manifest = [['file_originale', 'categoria', 'selettore_css_esempio', 'destinazione']];

        foreach ($hashSourceDirs as $hashDir) {
            if (!File::isDirectory($hashDir)) {
                continue;
            }
            foreach (File::files($hashDir) as $file) {
                $relUrl = '/img/' . basename($hashDir) . '/' . $file->getFilename();
                $info = $iconMap[$relUrl] ?? null;

                if ($info !== null) {
                    $category = $this->categorizeBySelector($info['selectors']);
                    $exampleSelector = $info['selectors'][0] ?? '';
                    $totals['mapped']++;
                } else {
                    $category = 'icone_non_categorizzate';
                    $exampleSelector = '';
                    $totals['unmapped']++;
                }

                $dstDir = $out . DIRECTORY_SEPARATOR . $category;
                File::ensureDirectoryExists($dstDir);
                $dstFile = $dstDir . DIRECTORY_SEPARATOR . $file->getFilename();
                File::copy($file->getPathname(), $dstFile);

                $manifest[] = [
                    $relUrl,
                    $category,
                    $exampleSelector,
                    str_replace($base . DIRECTORY_SEPARATOR, '', $dstFile),
                ];
            }
        }

        // 4) Manifest CSV
        $manifestPath = $out . DIRECTORY_SEPARATOR . 'manifest.csv';
        $fp = fopen($manifestPath, 'w');
        if ($fp === false) {
            $this->error("Impossibile scrivere manifest in {$manifestPath}");
            return self::FAILURE;
        }
        foreach ($manifest as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        // 5) README riassuntivo
        $readme = $this->buildReadme($totals);
        File::put($out . DIRECTORY_SEPARATOR . 'LEGGIMI.txt', $readme);

        // 6) Zip opzionale
        if ($this->option('zip')) {
            $zipPath = $out . '.zip';
            $this->createZip($out, $zipPath);
            $this->info("Archivio creato: {$zipPath}");
        }

        $this->newLine();
        $this->info('=== Riepilogo ===');
        $this->line("File copiati da cartelle organizzate: {$totals['copied']}");
        $this->line("Icone hash categorizzate via CSS:    {$totals['mapped']}");
        $this->line("Icone hash non categorizzate:        {$totals['unmapped']}");
        $this->line("Manifest:  {$manifestPath}");
        $this->line("Output:    {$out}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int,string>  $cssFiles
     * @return array<string, array{selectors: array<int,string>}>
     */
    private function buildIconMap(array $cssFiles): array
    {
        $map = [];
        foreach ($cssFiles as $css) {
            if (!File::isFile($css)) {
                continue;
            }
            $content = (string) File::get($css);

            // Cerca blocchi: selettore { ... background-image: url(...) ... }
            // Approccio semplice: split per "}" e analizza ogni blocco.
            $blocks = explode('}', $content);
            foreach ($blocks as $block) {
                if (!str_contains($block, '/img/')) {
                    continue;
                }
                $parts = explode('{', $block, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $selectorPart = trim($parts[0]);
                $bodyPart = $parts[1];

                if (preg_match_all('#url\(\s*[\'"]?(/img/[^\)\'"]+)#', $bodyPart, $matches)) {
                    foreach ($matches[1] as $url) {
                        $url = strtok($url, '?#'); // taglia querystring/fragment
                        if ($url === false) {
                            continue;
                        }
                        if (!isset($map[$url])) {
                            $map[$url] = ['selectors' => []];
                        }
                        $map[$url]['selectors'][] = $selectorPart;
                    }
                }
            }
        }
        return $map;
    }

    /**
     * @param  array<int,string>  $selectors
     */
    private function categorizeBySelector(array $selectors): string
    {
        $haystack = strtolower(implode(' ', $selectors));
        foreach (self::SELECTOR_CATEGORIES as $regex => $category) {
            if (preg_match($regex, $haystack)) {
                return $category;
            }
        }
        return 'icone_varie';
    }

    private function countFiles(string $dir): int
    {
        if (!File::isDirectory($dir)) {
            return 0;
        }
        $n = 0;
        foreach (File::allFiles($dir) as $_) {
            $n++;
        }
        return $n;
    }

    /** @param array{copied:int,mapped:int,unmapped:int} $totals */
    private function buildReadme(array $totals): string
    {
        return <<<TXT
ASSET OGAME ESPORTATI PER RIDISEGNO
====================================

Scopo: catalogare ogni asset grafico usato dal client di gioco, organizzato per
categoria, in modo da poterlo sostituire con artwork originale (per evitare
qualsiasi violazione di copyright).

Struttura:
- strutture/, ricerca/, unita_navi_difesa/  → sprite con nomi semantici (es. metal_mine_small.jpg)
- mercante/, classi_personaggio/, marketplace/ → asset già organizzati per feature
- flotta_missioni/, pianeti/, lune/, galassia/ → asset di vista mappa/flotta
- spritesheets/  → sprite multiimmagine, da ritagliare a mano (Photoshop/GIMP)
- *_icone/      → icone (ex /img/icons/) categorizzate analizzando i selettori CSS che le usano
- icone_non_categorizzate/ → icone non referenziate da CSS noti (probabili residui o usate via JS)
- manifest.csv  → mappatura: file → categoria → selettore CSS d'esempio → percorso esportato

Statistiche:
- File copiati da cartelle organizzate: {$totals['copied']}
- Icone hash categorizzate via CSS:     {$totals['mapped']}
- Icone hash non categorizzate:         {$totals['unmapped']}

Workflow consigliato per il ridisegno:
1) Apri una categoria alla volta (es. strutture/).
2) Annota ogni asset originale e produci la versione ridisegnata mantenendo
   nome file e dimensioni identici.
3) Sostituisci il file in public/img/<percorso_originale> (vedi colonna
   "file_originale" del manifest).
4) Per gli spritesheet: ridisegna ogni cella mantenendo griglia e dimensioni
   esatte, oppure produci immagini singole e adatta i CSS che fanno
   background-position.

Nota: questo export è uno strumento di lavoro per il ridisegno. Gli asset
originali NON vanno ridistribuiti; vanno usati solo come riferimento per
l'artwork sostitutivo.
TXT;
    }

    private function createZip(string $sourceDir, string $zipPath): void
    {
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            $this->error("Impossibile creare zip: {$zipPath}");
            return;
        }
        $sourceDirReal = realpath($sourceDir);
        if ($sourceDirReal === false) {
            return;
        }
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }
            $rel = ltrim(str_replace($sourceDirReal, '', (string) $file->getRealPath()), DIRECTORY_SEPARATOR);
            $zip->addFile((string) $file->getRealPath(), str_replace(DIRECTORY_SEPARATOR, '/', $rel));
        }
        $zip->close();
    }
}
