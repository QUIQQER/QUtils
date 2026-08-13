<?php

namespace QUITest\QUI\Utils\Text;

use PHPUnit\Framework\TestCase;
use QUI\Utils\Text\BBCode;

class BBCodeTest extends TestCase
{
    public function testParseToHtmlConvertsFormattingAndBlocks(): void
    {
        $Parser = new BBCode();
        $Parser->addSmiley(':-)', 'smile');

        $html = $Parser->parseToHTML(
            '[b]bold[/b][i]italic[/i][u]underline[/u][s]strike[/s]' .
            '[p]paragraph[/p][code]code[/code][php]php[/php]' .
            '[center]center[/center][left]left[/left][right]right[/right]' .
            '[h1]title[/h1][br]:-)'
        );

        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('<i>italic</i>', $html);
        $this->assertStringContainsString('<u>underline</u>', $html);
        $this->assertStringContainsString('<strike>strike</strike>', $html);
        $this->assertStringContainsString('<p>paragraph</p>', $html);
        $this->assertStringContainsString('<pre class="code">code</pre>', $html);
        $this->assertStringContainsString('<pre class="php">php</pre>', $html);
        $this->assertStringContainsString('<span class="smile"><span>:-)</span></span>', $html);
    }

    public function testParseToHtmlConvertsLinksImagesAndMail(): void
    {
        $Parser = new BBCode();
        $html = $Parser->parseToHTML(
            '[url="http://example.com"]external HTTP[/url]' .
            '[url="https://example.com"]external HTTPS[/url]' .
            '[url="/internal"]internal[/url]' .
            '[img="image.jpg" width="20" height="10"]' .
            '[email]person@example.com[/email]' .
            '[email=other@example.com]Other[/email]'
        );

        $this->assertStringContainsString('href="http://example.com" class="extern"', $html);
        $this->assertStringContainsString('href="https://example.com" class="extern"', $html);
        $this->assertStringContainsString('href="/internal" class="intern"', $html);
        $this->assertStringContainsString('<img src="image.jpg" width="20" height="10" />', $html);
        $this->assertStringContainsString('href="mailto:person@example.com"', $html);
        $this->assertStringContainsString('href="mailto:other@example.com"', $html);
    }

    public function testParseToBbCodeConvertsFormattingLinksAndImages(): void
    {
        $Parser = new BBCode();
        $Parser->setAttribute('extern_image', true);
        $Parser->addSmiley(':-)', 'smile');

        $bbcode = $Parser->parseToBBCode(
            '<p><strong>bold</strong></p>' .
            '<span style="font-style: italic; text-decoration: underline">styled</span>' .
            '<div style="text-align: center">center</div>' .
            '<a href="https://example.com">link</a>' .
            '<img src="image.jpg" style="width: 20px; height: 10px" align="left">' .
            '<span class="smile"><span>:-)</span></span>'
        );

        $this->assertStringContainsString('[p][b]bold[/b][/p]', $bbcode);
        $this->assertStringContainsString('[u][i]styled[/i][/u]', $bbcode);
        $this->assertStringContainsString('[center]center[/center]', $bbcode);
        $this->assertStringContainsString('[url="https://example.com" class="extern"]link[/url]', $bbcode);
        $this->assertStringContainsString('[img="image.jpg" width="20" height="10" align="left"]', $bbcode);
        $this->assertStringContainsString(':-)', $bbcode);
    }

    public function testSmileyCanBeRemoved(): void
    {
        $Parser = new BBCode();
        $Parser->addSmiley(':D', 'big-grin');
        $this->assertStringContainsString('big-grin', $Parser->parseToHTML(':D'));

        $this->assertTrue($Parser->removeSmiley(':D'));
        $this->assertSame(':D', $Parser->parseToHTML(':D'));
    }

    public function testParseToBbCodeCoversAdditionalHtmlBranches(): void
    {
        $Parser = new BBCode();
        $Parser->setAttribute('extern_image', true);

        $bbcode = $Parser->parseToBBCode(
            '<div>block</div>' .
            '<span>inline</span>' .
            '<span style="font-weight: bold; text-decoration: line-through; text-align: left">left</span>' .
            '<span style="text-align: right">right</span>' .
            '<a class="intern" href="/internal">internal</a>' .
            '<img src="image.jpg" width="20" height="10">' .
            '<span class="unknown"><span>fallback</span></span>'
        );

        $this->assertStringContainsString('block', $bbcode);
        $this->assertStringContainsString('inline', $bbcode);
        $this->assertStringContainsString('[left][s][b]left[/b][/s][/left]', $bbcode);
        $this->assertStringContainsString('[right]right[/right]', $bbcode);
        $this->assertStringContainsString('[url="/internal" class="intern"]internal[/url]', $bbcode);
        $this->assertStringContainsString('[img="image.jpg" width="20" height="10"]', $bbcode);
        $this->assertStringContainsString('fallback', $bbcode);
    }

    public function testParseToBbCodeRejectsImagesWithoutSourceOrPermission(): void
    {
        $Parser = new BBCode();
        $Parser->setAttribute('extern_image', true);
        $this->assertSame('', $Parser->parseToBBCode('<img width="20">'));

        $Parser->setAttribute('extern_image', false);
        $this->assertSame('', $Parser->parseToBBCode('<img src="image.jpg">'));
    }
}
