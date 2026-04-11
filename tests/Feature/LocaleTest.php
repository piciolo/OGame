<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use OGame\Http\Middleware\Locale;
use Tests\TestCase;

/**
 * Smoke tests for the 27-language locale rollout (PR 5/5).
 *
 * These tests guard the invariants of the multi-language system:
 *   • Every code in Locale::SUPPORTED_LOCALES has a real lang directory.
 *   • Every locale's t_ingame.php is parsable and exposes the language_*
 *     autoglonym keys (the regression that prompted PR 4 was that the 23
 *     generated dirs were built BEFORE these keys were added).
 *   • The /lang/{lang} route accepts every supported code and falls back
 *     to 'en' for unsupported input.
 */
class LocaleTest extends TestCase
{
    /**
     * Sanity check: the constant itself contains the expected 27 codes.
     */
    public function testSupportedLocalesContains27Codes(): void
    {
        $this->assertCount(27, Locale::SUPPORTED_LOCALES);

        // Spot-check a representative subset across legacy + new codes.
        foreach (['en', 'de', 'it', 'nl', 'fr', 'es', 'jp', 'ru', 'tw', 'yu'] as $code) {
            $this->assertContains($code, Locale::SUPPORTED_LOCALES, "Missing locale: {$code}");
        }
    }

    /**
     * Every supported locale must have a directory containing at least the
     * t_ingame.php file (the only file the autoglonym contract depends on).
     * Other files (t_overview, t_messages, …) are not uniformly present in
     * the four hand-maintained locales — only generated locales ship them all.
     */
    public function testEverySupportedLocaleHasLangDirectory(): void
    {
        foreach (Locale::SUPPORTED_LOCALES as $code) {
            $dir = lang_path($code);
            $this->assertDirectoryExists($dir, "Missing lang directory for {$code}");
            $this->assertFileExists($dir . DIRECTORY_SEPARATOR . 't_ingame.php', "Missing t_ingame.php for {$code}");
        }
    }

    /**
     * Every supported locale's t_ingame.php must parse to a PHP array.
     * This catches generator regressions like stray backticks or unbalanced quotes.
     */
    public function testEveryTIngameFileIsParsable(): void
    {
        foreach (Locale::SUPPORTED_LOCALES as $code) {
            $path = lang_path($code . DIRECTORY_SEPARATOR . 't_ingame.php');
            $data = require $path;
            $this->assertIsArray($data, "t_ingame.php for {$code} did not return an array");
            $this->assertNotEmpty($data, "t_ingame.php for {$code} is empty");
        }
    }

    /**
     * Every locale must expose a language_xx autoglonym for every other locale.
     * This is the key the in-game options dropdown reads to render the picker
     * and the regression we fixed in PR 4 (23 generated dirs were missing these).
     */
    public function testEveryLocaleExposesAllLanguageAutoglonyms(): void
    {
        foreach (Locale::SUPPORTED_LOCALES as $uiLocale) {
            App::setLocale($uiLocale);

            foreach (Locale::SUPPORTED_LOCALES as $targetLocale) {
                $key = 't_ingame.options.language_' . $targetLocale;
                $value = __($key);

                $this->assertNotSame(
                    $key,
                    $value,
                    "Locale {$uiLocale} is missing autoglonym key {$key} (translator returned the key)."
                );
                $this->assertIsString($value);
                $this->assertNotEmpty($value, "Empty autoglonym for {$key} in {$uiLocale}");
            }
        }

        App::setLocale(config('app.locale'));
    }

    /**
     * The /lang/{lang} endpoint accepts every supported code and persists it
     * in the session.
     */
    public function testLanguageSwitchEndpointAcceptsAllSupportedLocales(): void
    {
        foreach (Locale::SUPPORTED_LOCALES as $code) {
            $response = $this->get("/lang/{$code}");

            // Controller always issues a redirect (back or fallback to login).
            $response->assertRedirect();
            $this->assertSame($code, session('locale'), "Session locale not set to {$code}");
        }
    }

    /**
     * Unsupported codes must NOT pollute the session and must fall back to 'en'.
     */
    public function testLanguageSwitchEndpointFallsBackToEnglishForUnsupported(): void
    {
        foreach (['xx', 'klingon', 'zz', 'frCA'] as $bogus) {
            $response = $this->get('/lang/' . $bogus);
            $response->assertRedirect();
            $this->assertSame('en', session('locale'), "Bogus code {$bogus} should fall back to 'en'");
        }
    }

    /**
     * Lang::has() should report that the language autoglonym key exists in
     * every supported locale (independent of the __() fallback chain).
     */
    public function testLangHasAutoglonymKeyInEveryLocale(): void
    {
        foreach (Locale::SUPPORTED_LOCALES as $code) {
            $this->assertTrue(
                Lang::has('t_ingame.options.language_en', $code),
                "Lang::has() reports missing language_en in {$code}"
            );
        }
    }
}
