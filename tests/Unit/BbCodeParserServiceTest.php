<?php

namespace Tests\Unit;

use OGame\Services\BbCodeParserService;
use Tests\TestCase;

/**
 * Pure-unit tests for BbCodeParserService:
 *  - empty input returns an empty string
 *  - HTML in the input is escaped before BBCode is processed (XSS guard)
 *  - basic formatting tags (b/i/u/s/sup/sub) produce the documented HTML
 *  - color/size tags emit inline-style spans with the captured value
 *  - [url] (one- and two-arg variants) produces target=_blank + rel=noopener
 *  - newlines are converted to <br>
 *  - tags can be nested
 */
class BbCodeParserServiceTest extends TestCase
{
    private BbCodeParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BbCodeParserService();
    }

    public function testEmptyInputReturnsEmptyString(): void
    {
        $this->assertSame('', $this->parser->parse(''));
    }

    public function testRawHtmlIsEscapedBeforeBbCodeProcessing(): void
    {
        $out = $this->parser->parse('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $out, 'Raw <script> must never survive parsing.');
        // htmlspecialchars with ENT_QUOTES escapes both & and <.
        $this->assertStringContainsString('&lt;script&gt;', $out);
        $this->assertStringContainsString('&quot;xss&quot;', $out);
    }

    public function testBoldTag(): void
    {
        $out = $this->parser->parse('hello [b]world[/b]');
        $this->assertStringContainsString('<strong style="font-weight:bold">world</strong>', $out);
    }

    public function testItalicTag(): void
    {
        $out = $this->parser->parse('[i]italic[/i]');
        $this->assertStringContainsString('<em style="font-style:italic">italic</em>', $out);
    }

    public function testUnderlineAndStrikethroughTags(): void
    {
        $u = $this->parser->parse('[u]uu[/u]');
        $s = $this->parser->parse('[s]ss[/s]');
        $this->assertStringContainsString('<span style="text-decoration:underline">uu</span>', $u);
        $this->assertStringContainsString('<span style="text-decoration:line-through">ss</span>', $s);
    }

    public function testSupAndSubTags(): void
    {
        $out = $this->parser->parse('E=mc[sup]2[/sup] and H[sub]2[/sub]O');
        $this->assertStringContainsString('<sup>2</sup>', $out);
        $this->assertStringContainsString('<sub>2</sub>', $out);
    }

    public function testColorTagWithNamedColor(): void
    {
        $out = $this->parser->parse('[color=red]warning[/color]');
        $this->assertStringContainsString('<span style="color:red">warning</span>', $out);
    }

    public function testColorTagWithHexColor(): void
    {
        $out = $this->parser->parse('[color=#ff8800]orange[/color]');
        $this->assertStringContainsString('<span style="color:#ff8800">orange</span>', $out);
    }

    public function testSizeTag(): void
    {
        $out = $this->parser->parse('[size=14]bigger[/size]');
        $this->assertStringContainsString('<span style="font-size:14px">bigger</span>', $out);
    }

    public function testUrlTagOneArgUsesUrlAsLabel(): void
    {
        $out = $this->parser->parse('[url]https://example.com[/url]');
        $this->assertStringContainsString('href="https://example.com"', $out);
        $this->assertStringContainsString('target="_blank"', $out);
        $this->assertStringContainsString('rel="noopener noreferrer"', $out);
        // The label equals the URL when only one arg is passed.
        $this->assertStringContainsString('>https://example.com</a>', $out);
    }

    public function testUrlTagTwoArgUsesCustomLabel(): void
    {
        $out = $this->parser->parse('[url=https://example.com]click me[/url]');
        $this->assertStringContainsString('href="https://example.com"', $out);
        $this->assertStringContainsString('>click me</a>', $out);
    }

    public function testNewlinesAreConvertedToBr(): void
    {
        $out = $this->parser->parse("line1\nline2");
        $this->assertStringContainsString('<br', $out);
        $this->assertStringContainsString('line1', $out);
        $this->assertStringContainsString('line2', $out);
    }

    public function testNestedTagsAreSupported(): void
    {
        $out = $this->parser->parse('[b]bold and [i]italic[/i] inside[/b]');
        $this->assertStringContainsString('<strong style="font-weight:bold">', $out);
        $this->assertStringContainsString('<em style="font-style:italic">italic</em>', $out);
    }

    public function testMultipleSeparateTagsAllConvert(): void
    {
        $out = $this->parser->parse('[b]X[/b] [i]Y[/i] [u]Z[/u]');
        $this->assertStringContainsString('<strong', $out);
        $this->assertStringContainsString('<em', $out);
        $this->assertStringContainsString('text-decoration:underline">Z</span>', $out);
    }

    public function testUnknownTagsAreLeftEscapedAsLiteralText(): void
    {
        // [foo]bar[/foo] is not a recognised tag → htmlspecialchars escapes the
        // brackets, so the output contains the escaped literal — never bare HTML.
        $out = $this->parser->parse('[foo]bar[/foo]');
        $this->assertStringContainsString('[foo]bar[/foo]', $out);
        $this->assertStringNotContainsString('<foo>', $out);
    }
}
