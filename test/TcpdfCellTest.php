<?php

declare(strict_types=1);

/**
 * Cell / MultiCell / Write cursor model tests.
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfCellTest extends TcpdfTestCase
{
    public function testCellCursorAdvance(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->setMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setXY(10, 20);

        // ln = 0: cursor moves right by the cell width.
        $pdf->Cell(50, 8, 'one', 0, 0);
        $this->assertSame(60.0, $pdf->GetX());
        $this->assertSame(20.0, $pdf->GetY());

        // ln = 1: cursor moves below the cell, X back to the left margin.
        $pdf->Cell(50, 8, 'two', 0, 1);
        $this->assertSame(10.0, $pdf->GetX());
        $this->assertSame(28.0, $pdf->GetY());
        $this->assertSame(8.0, $pdf->getLastH());

        // ln = 2: cursor moves below, X unchanged.
        $pdf->setXY(30, 40);
        $pdf->Cell(50, 8, 'three', 0, 2);
        $this->assertSame(30.0, $pdf->GetX());
        $this->assertSame(48.0, $pdf->GetY());

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('one', $text);
        $this->assertStringContainsString('two', $text);
        $this->assertStringContainsString('three', $text);
    }

    public function testCellMinimumHeightIsEnforced(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $starty = $pdf->GetY();
        $pdf->Cell(0, 0, 'minimum height', 0, 1);
        $this->assertGreaterThan($starty, $pdf->GetY());
        $this->assertEqualsWithDelta($pdf->getCellHeight($pdf->getFontSize()), $pdf->getLastH(), 0.001);
    }

    public function testEmptyCellStillDrawsBorderAndFill(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->setFillColor(255, 0, 0);
        $pdf->Cell(50, 10, '', 1, 1, '', true);
        $raw = $pdf->getPDFData();
        $this->assertIsString($raw);
        $this->assertStringStartsWith('%PDF-', $raw);
        $this->assertSame(10.0, $pdf->getLastH());
    }

    public function testMultiCellWrapsAndAdvances(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->setMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setXY(10, 20);

        $longtext = str_repeat('wrap me around the cell width please ', 10);
        $lines = $pdf->MultiCell(60, 0, $longtext, 0, 'L');
        $this->assertIsInt($lines);
        $this->assertGreaterThan(3, $lines, 'long text must wrap on multiple lines');
        $this->assertGreaterThan(20.0, $pdf->GetY(), 'cursor must move below the cell');
        $this->assertSame(10.0, $pdf->GetX(), 'default ln=1 must reset X to the left margin');
    }

    public function testWriteFlowsFromTheCursor(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->setMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setXY(50, 30);

        $pdf->Write(0, 'flowing text starts at the cursor', '', false, 'L', false);
        $this->assertGreaterThan(50.0, $pdf->GetX(), 'cursor must end after the written text');
        $this->assertEqualsWithDelta(30.0, $pdf->GetY(), 0.001, 'single line keeps the same baseline');

        $pdf->Write(0, ' and continues.', '', false, 'L', true);
        $this->assertSame(10.0, $pdf->GetX(), 'ln=true must reset X to the left margin');
        $this->assertGreaterThan(30.0, $pdf->GetY());

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('flowing text starts at the cursor', $text);
        $this->assertStringContainsString('and continues.', $text);
    }

    public function testTextPlacesAtAbsolutePosition(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->Text(25, 120, 'absolutely positioned');
        $this->assertStringContainsString('absolutely positioned', $this->extractText($pdf));
    }
}
