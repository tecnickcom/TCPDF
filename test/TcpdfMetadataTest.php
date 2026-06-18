<?php

declare(strict_types=1);

/**
 * Document metadata family tests (delegated setters).
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfMetadataTest extends TcpdfTestCase
{
    public function testDocumentInformationIsWrittenToThePdf(): void
    {
        $pdf = $this->newPdf();
        $pdf->setCreator('facade-creator');
        $pdf->setAuthor('facade-author');
        $pdf->setTitle('facade-title');
        $pdf->setSubject('facade-subject');
        $pdf->setKeywords('facade, keywords');
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'metadata test');

        $info = $this->pdfInfo($pdf);
        $this->assertStringContainsString('facade-title', $info);
        $this->assertStringContainsString('facade-author', $info);
        $this->assertStringContainsString('facade-subject', $info);
        $this->assertStringContainsString('facade-creator', $info);
        $this->assertStringContainsString('facade, keywords', $info);
    }

    public function testPdfVersionCanBeSelected(): void
    {
        $pdf = $this->newPdf();
        $pdf->setPDFVersion('1.6');
        $pdf->AddPage();
        $pdf->Cell(0, 0, 'version test');

        $info = $this->pdfInfo($pdf);
        $this->assertMatchesRegularExpression('/PDF version:\s+1\.6/', $info);
    }
}
