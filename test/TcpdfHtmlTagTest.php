<?php

declare(strict_types=1);

/**
 * HTML <tcpdf> tag tests for writeHTML().
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';
require_once __DIR__ . '/TcpdfHtmlTagTestPdf.php';

class TcpdfHtmlTagTest extends TcpdfTestCase
{
    private function tagPdf(): TCPDF
    {
        $pdf = new TcpdfHtmlTagTestPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setCompression(false);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        return $pdf;
    }

    /**
     * Decompressed content of the document streams.
     */
    private function contents(TCPDF $pdf): string
    {
        $raw = (string) $pdf->getPDFData();
        $streams = [];
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams);
        $out = '';
        foreach ($streams[1] ?? [] as $stream) {
            $data = str_starts_with($stream, "\x78") ? gzuncompress($stream) : $stream;
            $out .= is_string($data) ? $data . "\n" : '';
        }

        return $out;
    }

    /**
     * Number of form XObjects drawn in the document.
     */
    private function placements(TCPDF $pdf): int
    {
        return (int) preg_match_all('/\/XT\d+ Do/', $this->contents($pdf));
    }

    /**
     * Table with a QR code in the right cell of the first row.
     */
    private function tableHtml(string $data): string
    {
        return (
            '<table border="1" cellpadding="2"><tr><td width="70%">cell A3</td>'
            . '<td width="30%"><tcpdf data="'
            . $data
            . '" /></td></tr>'
            . '<tr><td>cell A3 row 2</td><td>cell B3 row 2</td></tr></table><p>END</p>'
        );
    }

    public function testBarcodeTagInATableCellKeepsTheTable(): void
    {
        $pdf = $this->tagPdf();
        $qr = $pdf->serializeTCPDFtag('write2DBarcode', ['https://example.com', 'QRCODE,L', '', '', 25, 25, [], 'N']);
        $pdf->writeHTML($this->tableHtml($qr));

        $this->assertSame(1, $this->placements($pdf));
        $first = $this->wordBox($pdf, 'A3');
        $rowa = $this->wordBox($pdf, 'B3');
        $end = $this->wordBox($pdf, 'END');

        // The second row starts below the 25 mm QR code of the first row.
        $topt = 72.0 / 25.4;
        $this->assertGreaterThan($first['ymin'] + (25.0 * $topt), $rowa['ymin']);
        $this->assertGreaterThan($rowa['ymax'], $end['ymin']);
        $this->assertMatchesRegularExpression('/cell A3 row 2 +cell B3 row 2/', $this->popplerTool(
            'pdftotext',
            $pdf,
            '-layout -',
        ));
    }

    public function testBarcodeTagsInsideListsAndBlocksKeepTheMarkup(): void
    {
        $pdf = $this->tagPdf();
        $bc = $pdf->serializeTCPDFtag('write1DBarcode', ['ABC123', 'C39', '', '', 60, 12, 0.4, [], 'N']);
        $tag = '<tcpdf data="' . $bc . '" />';
        $pdf->writeHTML('<ul><li>first ' . $tag . '</li><li>second</li></ul><div>in div ' . $tag . ' tail</div>');

        $this->assertSame(2, $this->placements($pdf));
        $text = $this->extractText($pdf);
        foreach (['first', 'second', 'in div', 'tail'] as $word) {
            $this->assertStringContainsString($word, $text);
        }
    }

    public function testInlineBarcodeTagKeepsTheLine(): void
    {
        $pdf = $this->tagPdf();
        $qr = $pdf->serializeTCPDFtag('write2DBarcode', ['x', 'QRCODE,L', '', '', 8, 8, [], 'T']);
        $pdf->writeHTML('<p>left <tcpdf data="' . $qr . '" /> right</p>');

        $this->assertSame(1, $this->placements($pdf));
        $left = $this->wordBox($pdf, 'left');
        $right = $this->wordBox($pdf, 'right');
        $this->assertEqualsWithDelta($left['ymin'], $right['ymin'], 0.01);
        $this->assertGreaterThan($left['xmax'] + ((8.0 * 72.0) / 25.4), $right['xmin']);
    }

    public function testBarcodeLabelUsesItsOwnFont(): void
    {
        $style = ['text' => true, 'font' => 'helvetica', 'fontsize' => 8];
        $pdf = $this->tagPdf();
        $pdf->SetFont('helvetica', '', 20);
        $data = $pdf->serializeTCPDFtag('write1DBarcode', ['LABEL', 'C39', '', '', 60, 20, 0.4, $style, 'N']);
        $pdf->writeHTML('<tcpdf data="' . $data . '" />');

        $direct = $this->tagPdf();
        $direct->SetFont('helvetica', '', 20);
        $direct->write1DBarcode('LABEL', 'C39', '', '', 60, 20, 0.4, $style, 'N');

        $tagged = $this->wordBox($pdf, 'L');
        $drawn = $this->wordBox($direct, 'L');
        $this->assertEqualsWithDelta($drawn['ymax'] - $drawn['ymin'], $tagged['ymax'] - $tagged['ymin'], 0.01);
        // 8 pt glyphs, not the 20 pt font active where the barcode is drawn.
        $this->assertLessThan(10.0, $tagged['ymax'] - $tagged['ymin']);
    }

    public function testCallTagDrawsInDocumentOrder(): void
    {
        $pdf = $this->tagPdf();
        $rect = $pdf->serializeTCPDFtag('Rect', [50, 50, 40, 10, 'F', [], [0, 128, 255]]);
        $pdf->writeHTML('<p>before</p><tcpdf data="' . $rect . '" /><p>after</p>');

        $raw = $this->contents($pdf);
        $before = strpos($raw, '(before) Tj');
        $fill = strpos($raw, ' re');
        $after = strpos($raw, '(after) Tj');
        $this->assertNotFalse($before);
        $this->assertNotFalse($fill);
        $this->assertNotFalse($after);
        $this->assertGreaterThan($before, $fill);
        $this->assertGreaterThan($fill, $after);
    }

    public function testForeignTamperedAndDisallowedTagsAreIgnored(): void
    {
        $pdf = $this->tagPdf();
        $other = $this->tagPdf();
        $foreign = $other->serializeTCPDFtag('write2DBarcode', ['x', 'QRCODE,L', '', '', 25, 25, [], 'N']);
        $own = $pdf->serializeTCPDFtag('write2DBarcode', ['x', 'QRCODE,L', '', '', 25, 25, [], 'N']);
        $tampered = str_replace('25', '26', $own);
        $blocked = $pdf->serializeTCPDFtag('AddPage');
        $pdf->writeHTML(
            '<tcpdf data="'
            . $foreign
            . '" /><tcpdf data="'
            . $tampered
            . '" />'
            . '<tcpdf data="'
            . $blocked
            . '" /><p>text</p>',
        );

        $this->assertSame(0, $this->placements($pdf));
        $this->assertSame(1, $pdf->getNumPages());
        $this->assertStringContainsString('text', $this->extractText($pdf));
    }

    public function testTagsAreIgnoredWhenCallsAreDisabled(): void
    {
        $pdf = $this->newPdf();
        $pdf->setCompression(false);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $qr = $pdf->serializeTCPDFtag('write2DBarcode', ['https://example.com', 'QRCODE,L', '', '', 25, 25, [], 'N']);
        $pdf->writeHTML($this->tableHtml($qr) . '<tcpdf data="' . $pdf->serializeTCPDFtag('AddPage') . '" />');

        $this->assertSame(0, $this->placements($pdf));
        $this->assertSame(1, $pdf->getNumPages());
        $this->assertMatchesRegularExpression('/cell A3 row 2 +cell B3 row 2/', $this->popplerTool(
            'pdftotext',
            $pdf,
            '-layout -',
        ));
    }

    public function testTagsWorkAfterATransactionRollback(): void
    {
        $pdf = $this->tagPdf();
        $qr = $pdf->serializeTCPDFtag('write2DBarcode', ['x', 'QRCODE,L', '', '', 10, 10, [], 'N']);
        $pdf->startTransaction();
        $pdf->writeHTML('<p>discarded</p>');
        $pdf->rollbackTransaction(true);
        $pdf->writeHTML('<tcpdf data="' . $qr . '" />');

        $this->assertSame(1, $this->placements($pdf));
    }

    public function testIssueReproductionWithTagCallsEnabled(): void
    {
        $script = tempnam(sys_get_temp_dir(), 'tcpdf_tag_');
        $this->assertNotFalse($script);
        $output = $script . '.pdf';
        $code =
            '<?php'
            . "\ndefine('K_TCPDF_EXTERNAL_CONFIG', true);"
            . "\ndefine('K_TCPDF_CALLS_IN_HTML', true);"
            . "\ndefine('K_ALLOWED_TCPDF_TAGS', '|write1DBarcode|write2DBarcode|');"
            . "\nrequire "
            . var_export(dirname(__DIR__) . '/tcpdf.php', true)
            . ';'
            . "\n\$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);"
            . "\n\$pdf->setPrintHeader(false);"
            . "\n\$pdf->setPrintFooter(false);"
            . "\n\$pdf->AddPage();"
            . "\n\$pdf->SetFont('helvetica', '', 10);"
            . "\n\$qr = \$pdf->serializeTCPDFtag('write2DBarcode', ['https://example.com', 'QRCODE,L', '', '', 25, 25, [], 'N']);"
            . "\n\$bc = \$pdf->serializeTCPDFtag('write1DBarcode', ['ABC123', 'C39', '', '', 60, 12, 0.4, [], 'N']);"
            . "\n\$pdf->writeHTML('<p>Case 3</p><table border=\"1\" cellpadding=\"2\"><tr><td width=\"70%\">cell A3</td>"
            . "<td width=\"30%\"><tcpdf data=\"' . \$qr . '\" /></td></tr><tr><td>cell A3 row 2</td><td>cell B3 row 2</td></tr></table>"
            . '<p>Case 4</p><table border="1" cellpadding="2"><tr><td width="40%">cell A4</td>'
            . "<td width=\"60%\"><tcpdf data=\"' . \$bc . '\" /></td></tr></table><p>END</p>');"
            . "\n\$pdf->Output("
            . var_export($output, true)
            . ", 'F');\n";
        file_put_contents($script, $code);
        try {
            $lines = [];
            $status = 1;
            exec(PHP_BINARY . ' ' . escapeshellarg($script) . ' 2>&1', $lines, $status);
            $this->assertSame(0, $status, implode("\n", $lines));
            $text = [];
            exec('pdftotext -layout ' . escapeshellarg($output) . ' -', $text);
            $layout = implode("\n", $text);
        } finally {
            unlink($script);
            if (is_file($output)) {
                unlink($output);
            }
        }

        $this->assertMatchesRegularExpression('/^\s*cell A3\s*$/m', $layout);
        $this->assertMatchesRegularExpression('/cell A3 row 2 +cell B3 row 2/', $layout);
        $this->assertMatchesRegularExpression('/^\s*cell A4\s*$/m', $layout);
        $this->assertStringContainsString('END', $layout);
    }
}
