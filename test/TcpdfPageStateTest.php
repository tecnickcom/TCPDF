<?php

declare(strict_types=1);

/**
 * Document/page state family tests (margins, dimensions, cursor, breaks).
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfPageStateTest extends TcpdfTestCase
{
    public function testPageDimensionsMatchTheFormat(): void
    {
        $pdf = $this->newPdf();
        $pdf->AddPage();
        $this->assertEqualsWithDelta(210.0, $pdf->getPageWidth(), 0.1);
        $this->assertEqualsWithDelta(297.0, $pdf->getPageHeight(), 0.1);

        $pdf->AddPage('L');
        $this->assertEqualsWithDelta(297.0, $pdf->getPageWidth(), 0.1);
        $this->assertEqualsWithDelta(210.0, $pdf->getPageHeight(), 0.1);

        $pdf->AddPage('P', 'LETTER');
        $this->assertEqualsWithDelta(215.9, $pdf->getPageWidth(), 0.1);
        $this->assertEqualsWithDelta(279.4, $pdf->getPageHeight(), 0.1);
    }

    public function testCustomFormatArrayInUserUnits(): void
    {
        $pdf = $this->newPdf('P', 'mm', [100, 200]);
        $pdf->AddPage();
        $this->assertEqualsWithDelta(100.0, $pdf->getPageWidth(), 0.1);
        $this->assertEqualsWithDelta(200.0, $pdf->getPageHeight(), 0.1);
    }

    public function testScaleFactorMatchesUnit(): void
    {
        $pdfmm = $this->newPdf('P', 'mm');
        $this->assertEqualsWithDelta(72.0 / 25.4, $pdfmm->getScaleFactor(), 0.0001);

        $pdfpt = $this->newPdf('P', 'pt');
        $this->assertEqualsWithDelta(1.0, $pdfpt->getScaleFactor(), 0.0001);
    }

    public function testMarginsAndCursor(): void
    {
        $pdf = $this->newPdf();
        $pdf->setMargins(20, 30, 25);
        $pdf->AddPage();

        $margins = $pdf->getMargins();
        $this->assertIsArray($margins);
        /** @var array{left: float, top: float, right: float} $margins */
        $this->assertSame(20.0, $margins['left']);
        $this->assertSame(30.0, $margins['top']);
        $this->assertSame(25.0, $margins['right']);
        $this->assertSame(20.0, $pdf->GetX());
        $this->assertSame(30.0, $pdf->GetY());

        $pdf->setXY(40, 50);
        $this->assertSame(40.0, $pdf->GetX());
        $this->assertSame(50.0, $pdf->GetY());

        $pdf->setLastH(7.5);
        $pdf->Ln();
        $this->assertSame(20.0, $pdf->GetX(), 'Ln must reset X to the left margin');
        $this->assertSame(57.5, $pdf->GetY());

        $pdf->Ln(10);
        $this->assertSame(67.5, $pdf->GetY());
    }

    public function testAutomaticPageBreakAddsPages(): void
    {
        $pdf = $this->newPdf();
        $pdf->setAutoPageBreak(true, 20);
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        for ($idx = 0; $idx < 60; ++$idx) {
            $pdf->Cell(0, 10, 'line ' . $idx, 0, 1);
        }

        $this->assertGreaterThan(1, $pdf->getNumPages(), 'long content must trigger page breaks');

        $info = $this->pdfInfo($pdf);
        $this->assertMatchesRegularExpression('/Pages:\s+' . $pdf->getNumPages() . '/', $info);
    }

    public function testPageSelection(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->AddPage();
        $this->assertSame(2, $pdf->getPage());

        $pdf->setPage(1);
        $this->assertSame(1, $pdf->getPage());

        $pdf->lastPage();
        $this->assertSame(2, $pdf->getPage());
    }

    public function testCellHeightUsesRatioAndPadding(): void
    {
        $pdf = $this->newPdf();
        $pdf->setCellHeightRatio(1.5);
        $pdf->setCellPaddings(0, 2, 0, 3);
        $this->assertEqualsWithDelta((10 * 1.5) + 5, $pdf->getCellHeight(10.0), 0.001);
        $this->assertEqualsWithDelta(10 * 1.5, $pdf->getCellHeight(10.0, false), 0.001);
    }
}
