<?php

declare(strict_types=1);

/**
 * HTML markup normalization tests for writeHTML().
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfHtmlMarkupTest extends TcpdfTestCase
{
    /**
     * Run the protected markup normalization on the given HTML.
     */
    private function normalize(string $html): string
    {
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public function normalize(string $html): string
            {
                return $this->normalizeHtmlMarkup($html);
            }
        };

        return $pdf->normalize($html);
    }

    /**
     * The test runs in a separate process: the JIT does not honour the backtrack
     * limit, and turning it off only affects patterns compiled afterwards.
     * The limits are restored before returning, as PHPUnit itself calls
     * preg_replace() after the test method.
     *
     * @mago-expect lint:no-ini-set
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testTableLargerThanBacktrackLimitIsKept(): void
    {
        $html = '<table border="1">';
        for ($row = 1; $row <= 200; ++$row) {
            $html .= '<tr><td>Row ' . $row . '</td><td><img src="data:," align="middle"></td></tr>';
        }

        $html .= '</table>';

        $jit = (string) ini_get('pcre.jit');
        $limit = (string) ini_get('pcre.backtrack_limit');
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1000');
        try {
            $out = $this->normalize($html);
        } finally {
            ini_set('pcre.backtrack_limit', $limit);
            ini_set('pcre.jit', $jit);
        }

        $this->assertSame(200, substr_count($out, '<tr>'), 'every table row must be kept');
        $this->assertStringContainsString('Row 200</td>', $out);
        $this->assertStringEndsWith('</table>', $out);
        $this->assertStringNotContainsString('align=', $out);
    }

    public function testImageAlignIsDroppedOnlyInsideTables(): void
    {
        $out = $this->normalize(
            '<p><img src="data:," align="middle"></p>'
            . '<TABLE><tr><td>'
            . '<table><tr><td><img src="data:," align="top"></td></tr></table>'
            . '<img src="data:," align="bottom">'
            . '</td></tr></TABLE>'
            . '<img src="data:," align="baseline">'
            . '<tablex><img src="data:," align="top"></tablex>',
        );

        $this->assertStringContainsString('<p><img src="data:," align="middle"></p>', $out);
        $this->assertStringContainsString('<td><img src="data:,"></td></tr></table><img src="data:,"></td>', $out);
        $this->assertStringContainsString('</TABLE><img src="data:," align="baseline">', $out);
        $this->assertStringContainsString('<tablex><img src="data:," align="top"></tablex>', $out);
    }

    public function testColorRepairLeavesTextAndCharacterReferencesUnchanged(): void
    {
        $html = '<p>Order #1, issue #12, ref #12345, item #123456, long #1234567, tag #abc</p>'
            . '<p>A&#38;B, x&#35;y, a&#39;b, &#039;q&#039;, &#8364;, &#12354;</p>'
            . '<p>if a < b then color=#12 > 0</p>';

        $this->assertSame($html, $this->normalize($html));
    }

    public function testColorRepairLeavesNonColorAttributesUnchanged(): void
    {
        $html = '<a href="#chapter2" title="#1 A&#38;B">x</a><img alt="#12" src="data:," /><p id="a1">y</p>';

        $this->assertSame($html, $this->normalize($html));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function colorAttributeProvider(): array
    {
        return [
            'color look-alike letter' => ['<font color="#4169el">x</font>', '<font color="#4169e1">x</font>'],
            'bgcolor short' => ['<td bgcolor="#ab">x</td>', '<td bgcolor="#ab0000">x</td>'],
            'bordercolor single quotes' => ["<td BorderColor='#f0l'>x</td>", "<td BorderColor='#f01000'>x</td>"],
            'unquoted color' => ['<font color=#12>x</font>', '<font color=#120000>x</font>'],
            'valid color' => ['<font color="#ff7f50">x</font>', '<font color="#ff7f50">x</font>'],
            'long color' => ['<font color="#1234567">x</font>', '<font color="#123456">x</font>'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('colorAttributeProvider')]
    public function testColorRepairFixesColorAttributes(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->normalize($html));
    }

    public function testColorRepairFixesStyleAttributeValues(): void
    {
        $out = $this->normalize(
            '<span style="color:#12;font-family:&#39;courier&#39;;border:1px solid #f0l">s</span>'
            . "<span style='background-color:#ab'>t</span>",
        );

        $this->assertSame(
            '<span style="color:#120000;font-family:&#39;courier&#39;;border:1px solid #f01000">s</span>'
            . "<span style='background-color:#ab0000'>t</span>",
            $out,
        );
    }

    public function testColorRepairFixesOnlyStyleBlockDeclarationValues(): void
    {
        $out = $this->normalize(
            '<style>#a1 { color:#4169el; } a:hover #a1 { background:#ab } p { content:"#1" }</style>'
            . '<p id="a1">#1</p>',
        );

        $this->assertSame(
            '<style>#a1 { color:#4169e1; } a:hover #a1 { background:#ab0000 } p { content:"#1" }</style>'
            . '<p id="a1">#1</p>',
            $out,
        );
    }

    public function testWriteHtmlKeepsNumbersAndCharacterReferences(): void
    {
        $pdf = $this->newPdf();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->writeHTML(
            '<p>Order #1, issue #12, ref #12345, item #123456, tag #abc</p>'
            . '<p>A&#38;B, x&#35;y, a&#39;b, &#039;q&#039;, &#8364;</p>',
        );

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('Order #1, issue #12, ref #12345, item #123456, tag #abc', $text);
        $this->assertStringContainsString("A&B, x#y, a'b, 'q', \u{20AC}", $text);
    }

    public function testLegacyPageAnchorsBecomeInternalLinks(): void
    {
        $out = $this->normalize('<a href="#2">p2</a><a href=\'#3,25.5\'>p3</a><a href="#chapter2">c2</a>');

        $this->assertMatchesRegularExpression(
            '/^<a href="@\d+">p2<\/a><a href=\'@\d+\'>p3<\/a><a href="#chapter2">c2<\/a>$/',
            $out,
        );
    }

    public function testLegacyPageAnchorLinksToThePage(): void
    {
        $pdf = $this->newPdf();
        $pdf->setCompression(false);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->writeHTML('<a href="#2">link to page 2</a>');
        $pdf->AddPage();
        $pdf->writeHTML('page 2');

        $raw = (string) $pdf->getPDFData();
        $pages = [];
        preg_match_all('/(\d+) 0 obj\s*<<\s*\/Type \/Page\b/', $raw, $pages);
        $this->assertCount(2, $pages[1] ?? []);
        $dest = [];
        $this->assertSame(1, preg_match('/\/Dest \[(\d+) 0 R \/XYZ /', $raw, $dest));
        $this->assertSame($pages[1][1] ?? '', $dest[1] ?? '');
    }
}
