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
}
