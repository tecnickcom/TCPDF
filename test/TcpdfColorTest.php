<?php

declare(strict_types=1);

/**
 * Color family tests (legacy component conventions to engine operators).
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfColorTest extends TcpdfTestCase
{
    public function testDrawColorOperators(): void
    {
        $pdf = $this->newPdf();
        $pdf->AddPage();

        // RGB 0-255 components produce a stroking 'RG' operator.
        $cmd = $pdf->setDrawColor(255, 0, 0, -1, true);
        $this->assertIsString($cmd);
        $this->assertStringContainsString('RG', $cmd);

        // Grayscale single component produces a stroking gray operator.
        $cmd = $pdf->setDrawColor(128, -1, -1, -1, true);
        $this->assertIsString($cmd);
        $this->assertMatchesRegularExpression('/(G|RG)/', $cmd);

        // CMYK 0-100 four components produce a 'K' operator.
        $cmd = $pdf->setDrawColor(100, 0, 0, 50, true);
        $this->assertIsString($cmd);
        $this->assertStringContainsString('K', $cmd);
    }

    public function testFillColorOperators(): void
    {
        $pdf = $this->newPdf();
        $pdf->AddPage();

        $cmd = $pdf->setFillColor(0, 255, 0, -1, true);
        $this->assertIsString($cmd);
        $this->assertStringContainsString('rg', $cmd);

        $cmd = $pdf->setFillColorArray([0, 0, 100, 0], true);
        $this->assertIsString($cmd);
        $this->assertStringContainsString('k', $cmd);
    }

    public function testTextColorIsAppliedToText(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->setTextColor(200, 10, 10);
        $pdf->Cell(0, 0, 'colored text', 0, 1);

        $this->assertStringContainsString('colored text', $this->extractText($pdf));
    }
}
