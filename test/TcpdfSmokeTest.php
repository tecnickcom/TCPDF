<?php

declare(strict_types=1);

/**
 * Seed smoke test for the TCPDF compatibility facade.
 *
 * Exercises the thinnest vertical slice of the legacy API
 * (construct, AddPage, setFont, Cell, Write, Output/getPDFData)
 * and asserts that a real, parseable PDF document is produced.
 *
 * @package com.tecnick.tcpdf
 */

use PHPUnit\Framework\TestCase;

class TcpdfSmokeTest extends TestCase
{
    /**
     * Build the document used by most assertions in this suite.
     */
    private function buildDocument(): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setCreator('tcpdf-facade-test');
        $pdf->setAuthor('PHPUnit');
        $pdf->setTitle('Facade Smoke Test');
        $pdf->setSubject('seed smoke test');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setMargins(15, 27, 15);
        $pdf->setAutoPageBreak(true, 25);
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'Hello facade', 0, 1, 'L');
        $pdf->Write(0, 'Second line of text.', '', false, 'L', true);
        return $pdf;
    }

    public function testProducesParseablePdf(): void
    {
        $pdf = $this->buildDocument();
        $raw = $pdf->getPDFData();

        $this->assertIsString($raw);
        $this->assertNotSame('', $raw, 'PDF output must not be empty');
        $this->assertStringStartsWith('%PDF-', $raw, 'output must start with the PDF header');
        $this->assertStringContainsString('%%EOF', $raw, 'output must contain the PDF trailer');

        // The document must be parseable by an independent parser.
        $tmpfile = tempnam(sys_get_temp_dir(), 'tcpdf_smoke_');
        $this->assertNotFalse($tmpfile);
        file_put_contents($tmpfile, $raw);
        try {
            $info = [];
            $code = 1;
            exec('pdfinfo ' . escapeshellarg($tmpfile) . ' 2>&1', $info, $code);
            $this->assertSame(0, $code, 'pdfinfo must parse the document: ' . implode("\n", $info));
            $this->assertMatchesRegularExpression(
                '/^Pages:\s+1$/m',
                implode("\n", $info),
                'document must contain exactly one page',
            );
        } finally {
            unlink($tmpfile);
        }
    }

    public function testTextIsRendered(): void
    {
        $pdf = $this->buildDocument();
        $raw = $pdf->getPDFData();

        $tmpfile = tempnam(sys_get_temp_dir(), 'tcpdf_smoke_');
        $this->assertNotFalse($tmpfile);
        file_put_contents($tmpfile, $raw);
        try {
            $lines = [];
            $code = 1;
            exec('pdftotext ' . escapeshellarg($tmpfile) . ' - 2>/dev/null', $lines, $code);
            $this->assertSame(0, $code, 'pdftotext must extract text from the document');
            $text = implode("\n", $lines);
            $this->assertStringContainsString('Hello facade', $text);
            $this->assertStringContainsString('Second line of text.', $text);
        } finally {
            unlink($tmpfile);
        }
    }

    public function testPageStateIsTracked(): void
    {
        $pdf = $this->buildDocument();
        $this->assertSame(1, $pdf->getPage());
        $this->assertSame(1, $pdf->getNumPages());
        $this->assertSame(1, $pdf->PageNo());

        $pdf->AddPage();
        $this->assertSame(2, $pdf->getPage());
        $this->assertSame(2, $pdf->getNumPages());
    }

    public function testOutputStringDestination(): void
    {
        $pdf = $this->buildDocument();
        $raw = $pdf->Output('smoke.pdf', 'S');
        $this->assertIsString($raw);
        $this->assertStringStartsWith('%PDF-', $raw);
    }
}
