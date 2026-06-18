<?php

declare(strict_types=1);

/**
 * Header/footer family tests (deferred page decoration).
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfHeaderFooterTest extends TcpdfTestCase
{
    public function testHeaderDataState(): void
    {
        $pdf = $this->newPdf();
        $pdf->setHeaderData('', 0, 'My Title', 'My Header String', [0, 64, 255], [0, 64, 128]);
        $data = $pdf->getHeaderData();
        $this->assertSame('My Title', $data['title']);
        $this->assertSame('My Header String', $data['string']);

        $pdf->setHeaderMargin(7);
        $this->assertSame(7.0, $pdf->getHeaderMargin());
        $pdf->setFooterMargin(9);
        $this->assertSame(9.0, $pdf->getFooterMargin());

        $pdf->setHeaderFont(['times', 'B', 11]);
        $this->assertSame(['times', 'B', 11.0], $pdf->getHeaderFont());
        $pdf->setFooterFont(['courier', '', 8]);
        $this->assertSame(['courier', '', 8.0], $pdf->getFooterFont());
    }

    public function testDefaultHeaderAndFooterAreRendered(): void
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setHeaderData('', 0, 'Header Title 42', 'Header line one', [0, 0, 0], [0, 0, 0]);
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->setMargins(15, 27, 15);
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'page one body', 0, 1);
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'page two body', 0, 1);

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('Header Title 42', $text);
        $this->assertStringContainsString('Header line one', $text);
        // pdftotext does not always preserve the spaces around the slash.
        $compact = str_replace(' ', '', $text);
        $this->assertStringContainsString('1/2', $compact, 'default footer must print real page numbers');
        $this->assertStringContainsString('2/2', $compact);
    }

    public function testHeaderAndFooterCanBeDisabled(): void
    {
        $pdf = $this->newPdf();
        $pdf->setHeaderData('', 0, 'Hidden Header', '', [0, 0, 0], [0, 0, 0]);
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'body only', 0, 1);

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('body only', $text);
        $this->assertStringNotContainsString('Hidden Header', $text);
        $this->assertStringNotContainsString('1 / 1', $text);
    }

    public function testCustomHeaderSubclassIsInvoked(): void
    {
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public function Header()
            {
                $this->setFont('helvetica', 'B', 9);
                $this->Cell(0, 6, 'CUSTOM-HEADER-MARK', 0, 1, 'C');
            }

            public function Footer()
            {
                $this->setFont('helvetica', '', 8);
                $this->Cell(0, 6, 'CUSTOM-FOOTER-' . $this->PageNo(), 0, 0, 'C');
            }
        };
        $pdf->setFont('helvetica', '', 12);
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'subclass body', 0, 1);

        $text = $this->extractText($pdf);
        $this->assertStringContainsString('CUSTOM-HEADER-MARK', $text);
        $this->assertStringContainsString('CUSTOM-FOOTER-1', $text);
    }
}
