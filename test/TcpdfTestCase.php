<?php

declare(strict_types=1);

/**
 * Shared helpers for the TCPDF facade test suite.
 *
 * @package com.tecnick.tcpdf
 */

use PHPUnit\Framework\TestCase;

abstract class TcpdfTestCase extends TestCase
{
    /**
     * Create a document with sane test defaults (no header/footer).
     */
    protected function newPdf(string $orientation = 'P', string $unit = 'mm', mixed $format = 'A4'): TCPDF
    {
        $pdf = new TCPDF($orientation, $unit, $format, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setCompression(true);
        return $pdf;
    }

    /**
     * Run a poppler tool on the rendered document and return its output.
     */
    protected function popplerTool(string $tool, TCPDF $pdf, string $args = ''): string
    {
        $raw = $pdf->getPDFData();
        $this->assertIsString($raw);
        $this->assertStringStartsWith('%PDF-', $raw);

        $tmpfile = tempnam(sys_get_temp_dir(), 'tcpdf_test_');
        $this->assertNotFalse($tmpfile);
        file_put_contents($tmpfile, $raw);
        try {
            $lines = [];
            $code = 1;
            exec($tool . ' ' . escapeshellarg($tmpfile) . ' ' . $args . ' 2>/dev/null', $lines, $code);
            $this->assertSame(0, $code, $tool . ' must parse the document');
            return implode("\n", $lines);
        } finally {
            unlink($tmpfile);
        }
    }

    /**
     * Extract the text content of the rendered document.
     */
    protected function extractText(TCPDF $pdf): string
    {
        return $this->popplerTool('pdftotext', $pdf, '-');
    }

    /**
     * Return the pdfinfo report of the rendered document.
     */
    protected function pdfInfo(TCPDF $pdf): string
    {
        return $this->popplerTool('pdfinfo', $pdf);
    }
}
