<?php

namespace OGame\Console\Commands\Dev;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Reverse-engineering tool: harvests HTML / CSS / JS / assets from a live OGame
 * server for a given feature so we can replicate it faithfully.
 *
 * Usage:
 *   php artisan ogamex:dev:extract-feature ipioverview
 *
 * The command needs:
 *   - A config at  _research/extractors/<feature>.json
 *   - A cookie jar at _research/.cookies   (Netscape cookie format, see below)
 *
 * Output goes to:  _research/extracted/<feature>/<timestamp>/
 *
 * Cookie file format (Netscape, one cookie per line, tab-separated):
 *   .domain.tld  TRUE  /  FALSE  0  COOKIENAME  COOKIEVALUE
 * Extract from Chrome via DevTools > Application > Cookies, or via the
 * "Cookie-Editor" extension > Export > Netscape.
 */
class OgameExtractCommand extends Command
{
    protected $signature = 'ogamex:dev:extract-feature
                            {feature : Feature key (must have _research/extractors/<feature>.json)}
                            {--cookies= : Override path to Netscape cookie jar (default: _research/.cookies)}
                            {--out= : Override output directory}
                            {--skip-css : Do not download CSS bundles}
                            {--skip-js : Do not download JS bundles}
                            {--skip-assets : Do not download discovered assets}';

    protected $description = 'Harvest an OGame feature (HTML+CSS+JS+assets) into a versioned bundle for reverse engineering.';

    private string $outDir;
    private array $config;
    private string $cookiesPath;
    private array $manifest = [];

    public function handle(): int
    {
        $feature = $this->argument('feature');
        $configPath = base_path("_research/extractors/{$feature}.json");

        if (!file_exists($configPath)) {
            $this->error("Config not found: {$configPath}");
            $this->line('Create one based on _research/extractors/ipioverview.json');
            return self::FAILURE;
        }

        $this->config = json_decode(file_get_contents($configPath), true);
        if (!$this->config) {
            $this->error("Invalid JSON in {$configPath}");
            return self::FAILURE;
        }

        $this->cookiesPath = $this->option('cookies') ?? base_path('_research/.cookies');
        if (!file_exists($this->cookiesPath)) {
            $this->warn("Cookie file missing at {$this->cookiesPath} — authenticated endpoints will fail.");
        }

        $ts = date('Y-m-d_His');
        $this->outDir = $this->option('out') ?? base_path("_research/extracted/{$feature}/{$ts}");
        $this->ensureDir($this->outDir);

        $this->line("Feature:    {$feature}");
        $this->line("Config:     {$configPath}");
        $this->line("Output:     {$this->outDir}");
        $this->line("Cookies:    {$this->cookiesPath}");
        $this->newLine();

        $this->manifest = [
            'feature' => $feature,
            'extractedAt' => date('c'),
            'config' => basename($configPath),
            'files' => [],
        ];

        $this->fetchHtml();

        if (!$this->option('skip-css')) {
            $this->fetchCss();
            $this->extractMatchedCss();
        }

        if (!$this->option('skip-js')) {
            $this->fetchJs();
            $this->locateJsModule();
        }

        if (!$this->option('skip-assets')) {
            $this->discoverAndDownloadAssets();
        }

        $this->writeManifest();
        $this->newLine();
        $this->info("Done. Bundle: {$this->outDir}");
        return self::SUCCESS;
    }

    /**
     * Fetch HTML endpoints. Each entry can have a "loop" map that gets cartesian-expanded
     * into the URL/saveAs templates, and an "auth: true" flag for cookie usage.
     */
    private function fetchHtml(): void
    {
        $entries = $this->config['html'] ?? [];
        if (!$entries) {
            $this->line('  no html entries');
            return;
        }
        $this->line('Fetching HTML endpoints...');
        $expanded = [];
        foreach ($entries as $entry) {
            foreach ($this->expandLoop($entry) as $e) {
                $expanded[] = $e;
            }
        }
        $this->fetchInParallel($expanded, 'html');
    }

    private function fetchCss(): void
    {
        $entries = $this->config['css'] ?? [];
        if (!$entries) {
            $this->line('  no css entries');
            return;
        }
        $this->line('Fetching CSS bundles...');
        $this->fetchInParallel($entries, 'css');
    }

    private function fetchJs(): void
    {
        $entries = $this->config['js'] ?? [];
        if (!$entries) {
            $this->line('  no js entries');
            return;
        }
        $this->line('Fetching JS bundles...');
        $this->fetchInParallel($entries, 'js');
    }

    /**
     * Cartesian product expansion of a loop spec.
     * Example: loop: { id: [1,2] }  → 2 expanded entries with {id} replaced.
     */
    private function expandLoop(array $entry): array
    {
        $loop = $entry['loop'] ?? null;
        if (!$loop) return [$entry];

        $combos = [[]];
        foreach ($loop as $key => $values) {
            $newCombos = [];
            foreach ($combos as $c) {
                foreach ($values as $v) {
                    $newCombos[] = $c + [$key => $v];
                }
            }
            $combos = $newCombos;
        }

        $out = [];
        foreach ($combos as $vars) {
            $e = $entry;
            unset($e['loop']);
            foreach (['url', 'saveAs'] as $field) {
                if (isset($e[$field])) {
                    $e[$field] = $this->interpolate($e[$field], $vars);
                }
            }
            $out[] = $e;
        }
        return $out;
    }

    private function interpolate(string $tpl, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{' . $k . '}', (string)$v, $tpl);
        }
        return $tpl;
    }

    /**
     * Run curl downloads in parallel (up to 8 concurrent).
     */
    private function fetchInParallel(array $entries, string $kind): void
    {
        $base = $this->config['baseUrl'] ?? '';
        $maxConcurrent = 8;
        $running = [];

        foreach ($entries as $entry) {
            $url = str_starts_with($entry['url'], 'http') ? $entry['url'] : $base . $entry['url'];
            $saveAs = $this->outDir . '/' . $entry['saveAs'];
            $this->ensureDir(dirname($saveAs));

            $cmd = ['curl', '-sS', '--compressed', '-o', $saveAs];
            if (!empty($entry['auth']) && file_exists($this->cookiesPath)) {
                $cmd[] = '-b';
                $cmd[] = $this->cookiesPath;
            }
            if (!empty($entry['headers']) && is_array($entry['headers'])) {
                foreach ($entry['headers'] as $h) {
                    $cmd[] = '-H';
                    $cmd[] = $h;
                }
            }
            $cmd[] = $url;

            $p = new Process($cmd);
            $p->setTimeout(60);
            $p->start();
            $running[] = ['proc' => $p, 'saveAs' => $saveAs, 'url' => $url, 'kind' => $kind];

            if (count(array_filter($running, fn($r) => $r['proc']->isRunning())) >= $maxConcurrent) {
                $this->drain($running, $maxConcurrent / 2);
            }
        }
        $this->drain($running, 0);
    }

    private function drain(array &$running, int $threshold): void
    {
        while (count(array_filter($running, fn($r) => $r['proc']->isRunning())) > $threshold) {
            usleep(50000);
        }
        foreach ($running as $r) {
            if (!$r['proc']->isRunning()) {
                if ($r['proc']->getExitCode() !== 0) {
                    $this->warn("  FAILED: {$r['url']}");
                } else {
                    $size = file_exists($r['saveAs']) ? filesize($r['saveAs']) : 0;
                    $rel = str_replace($this->outDir . '/', '', $r['saveAs']);
                    $this->line(sprintf('  %s (%d bytes)', $rel, $size));
                    $this->manifest['files'][$rel] = ['kind' => $r['kind'], 'bytes' => $size, 'url' => $r['url']];
                }
            }
        }
        $running = array_filter($running, fn($r) => $r['proc']->isRunning());
    }

    /**
     * Extract CSS rule blocks from the main bundle that contain any of the configured
     * selector keywords. Emits to css/<feature>-rules.css.
     */
    private function extractMatchedCss(): void
    {
        $keywords = $this->config['extractCssFor'] ?? [];
        $sourceFile = $this->config['cssSource'] ?? null;
        if (!$keywords || !$sourceFile) return;

        $sourcePath = $this->outDir . '/' . $sourceFile;
        if (!file_exists($sourcePath)) {
            $this->warn("  CSS source not found: {$sourceFile}");
            return;
        }

        $this->line('Extracting matched CSS rules...');
        $css = file_get_contents($sourcePath);
        $pattern = '/(' . implode('|', array_map('preg_quote', $keywords)) . ')/';

        $rules = [];
        $depth = 0;
        $block = '';
        $len = strlen($css);
        for ($i = 0; $i < $len; $i++) {
            $c = $css[$i];
            $block .= $c;
            if ($c === '{') $depth++;
            elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    if (preg_match($pattern, $block)) {
                        $rules[] = $block;
                    }
                    $block = '';
                }
            }
        }

        $outFile = $this->outDir . '/css/' . $this->config['feature'] . '-rules.css';
        file_put_contents($outFile, implode("\n", $rules));
        $rel = "css/{$this->config['feature']}-rules.css";
        $this->line(sprintf('  %s (%d rules, %d bytes)', $rel, count($rules), filesize($outFile)));
        $this->manifest['files'][$rel] = ['kind' => 'css', 'bytes' => filesize($outFile), 'matchedRules' => count($rules)];
    }

    /**
     * Locate a JS module in the downloaded bundles by marker string and emit it
     * to js/<feature>.module.js.
     */
    private function locateJsModule(): void
    {
        $marker = $this->config['jsModuleMarker'] ?? null;
        $endMarker = $this->config['jsModuleEndMarker'] ?? null;
        if (!$marker) return;

        $this->line('Locating JS module...');
        $jsDir = $this->outDir . '/js';
        if (!is_dir($jsDir)) return;
        foreach (glob($jsDir . '/*.js') as $bundle) {
            $code = file_get_contents($bundle);
            $start = strpos($code, $marker);
            if ($start === false) continue;
            // Find matching closing brace by balanced count from the opening { of the marker
            $openBrace = strpos($code, '{', $start);
            if ($openBrace === false) continue;
            $depth = 0;
            $end = null;
            for ($i = $openBrace; $i < strlen($code); $i++) {
                if ($code[$i] === '{') $depth++;
                elseif ($code[$i] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $end = $i + 1;
                        break;
                    }
                }
            }
            if ($end === null) continue;
            // include the leading "var Foo = " (back to the start of the marker)
            $module = substr($code, $start, $end - $start);
            // also catch trailing semicolon
            if (isset($code[$end]) && $code[$end] === ';') $module .= ';';
            $outFile = $this->outDir . '/js/' . $this->config['feature'] . '.module.js';
            file_put_contents($outFile, $module);
            $rel = "js/{$this->config['feature']}.module.js";
            $linesIn = substr_count(substr($code, 0, $start), "\n") + 1;
            $linesOut = substr_count($module, "\n") + 1;
            $this->line(sprintf('  %s (lines %d-%d in %s, %d bytes)', $rel, $linesIn, $linesIn + $linesOut - 1, basename($bundle), strlen($module)));
            $this->manifest['files'][$rel] = ['kind' => 'js', 'bytes' => strlen($module), 'extractedFrom' => basename($bundle), 'startLine' => $linesIn];
            return;
        }
        $this->warn("  Marker '{$marker}' not found in any JS bundle.");
    }

    /**
     * Scan downloaded HTML and CSS for asset URLs and download them.
     */
    private function discoverAndDownloadAssets(): void
    {
        $this->line('Discovering assets...');
        $urls = [];

        foreach (glob($this->outDir . '/css/*.css') as $f) {
            $css = file_get_contents($f);
            if (preg_match_all('/url\(\s*[\'"]?([^)\'"\s]+)[\'"]?\s*\)/', $css, $m)) {
                foreach ($m[1] as $u) {
                    if (!str_contains($u, 'data:')) $urls[$u] = true;
                }
            }
        }

        foreach (glob($this->outDir . '/html/*.html') as $f) {
            $html = file_get_contents($f);
            if (preg_match_all('/(?:src|background-image:\s*url\()\s*[="\(]?\s*[\'"]?([^"\'\)\s]+)/', $html, $m)) {
                foreach ($m[1] as $u) {
                    if (!str_contains($u, 'data:') && !str_starts_with($u, '#')) $urls[$u] = true;
                }
            }
        }

        $assetDir = $this->outDir . '/assets';
        $this->ensureDir($assetDir);
        $entries = [];
        foreach (array_keys($urls) as $u) {
            $abs = $u;
            if (str_starts_with($u, '//')) $abs = 'https:' . $u;
            elseif (str_starts_with($u, '/')) $abs = ($this->config['baseUrl'] ?? '') . $u;
            elseif (!str_starts_with($u, 'http')) continue;

            // Group by extension
            $ext = strtolower(pathinfo(parse_url($abs, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'bin');
            $sub = match (true) {
                in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']) => 'images',
                in_array($ext, ['woff', 'woff2', 'ttf', 'otf', 'eot']) => 'fonts',
                default => 'misc',
            };
            $hash = substr(md5($abs), 0, 8);
            $base = basename(parse_url($abs, PHP_URL_PATH));
            $entries[] = ['url' => $abs, 'saveAs' => "assets/{$sub}/{$hash}-{$base}"];
        }

        $this->line(sprintf('  found %d asset URLs', count($entries)));
        $this->fetchInParallel($entries, 'asset');
    }

    private function writeManifest(): void
    {
        $path = $this->outDir . '/manifest.json';
        file_put_contents($path, json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('manifest.json written');
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
}
