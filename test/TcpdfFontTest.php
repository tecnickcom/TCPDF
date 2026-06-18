<?php

declare(strict_types=1);

/**
 * Font and text metrics family tests.
 *
 * @package com.tecnick.tcpdf
 */

require_once __DIR__ . '/TcpdfTestCase.php';

class TcpdfFontTest extends TcpdfTestCase
{
    public function testFontStateAccessors(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('times', 'BI', 14);
        $this->assertSame('times', $pdf->getFontFamily());
        $this->assertSame('BI', $pdf->getFontStyle());
        $this->assertSame(14.0, $pdf->getFontSizePt());
        $this->assertEqualsWithDelta(14.0 / $pdf->getScaleFactor(), $pdf->getFontSize(), 0.0001);

        $pdf->setFontSize(10);
        $this->assertSame(10.0, $pdf->getFontSizePt());
        $this->assertSame('times', $pdf->getFontFamily());
    }

    public function testDecorationLettersAreTracked(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', 'BU', 12);
        $this->assertSame('BU', $pdf->getFontStyle());
        $this->assertSame('helvetica', $pdf->getFontFamily());
    }

    public function testStringWidthIsPlausible(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $width = $pdf->GetStringWidth('Hello World');
        $this->assertIsFloat($width);
        // 62.004 points at 12pt helvetica = 21.87 mm.
        $this->assertEqualsWithDelta(21.87, $width, 0.1);

        $wider = $pdf->GetStringWidth('Hello World Hello World');
        $this->assertGreaterThan($width, $wider);

        $widths = $pdf->GetStringWidth('abc', '', '', 0, true);
        $this->assertIsArray($widths);
        $this->assertCount(3, $widths);
    }

    public function testCharacterHelpers(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $this->assertSame(4, $pdf->GetNumChars('abcd'));
        $this->assertSame(4, $pdf->GetNumChars('àbcd'), 'multibyte text must count codepoints');
        $this->assertGreaterThan(0, $pdf->GetCharWidth('M'));
        $this->assertTrue($pdf->isCharDefined(65));
        $this->assertGreaterThan(0, $pdf->getFontAscent('helvetica', '', 12));
        $this->assertGreaterThan(0, $pdf->getFontDescent('helvetica', '', 12));
    }

    public function testAddFontRegistersWithoutChangingCurrent(): void
    {
        $pdf = $this->newPdf();
        $pdf->setFont('helvetica', '', 12);
        $fontdata = $pdf->AddFont('courier');
        $this->assertIsArray($fontdata);
        $this->assertSame('courier', $fontdata['family'] ?? null);
        $this->assertSame('helvetica', $pdf->getFontFamily(), 'AddFont must not change the current font');
    }

    public function testFontSubsettingFlag(): void
    {
        $pdf = $this->newPdf();
        $this->assertTrue($pdf->getFontSubsetting(), 'legacy default is true');
        $pdf->setFontSubsetting(false);
        $this->assertFalse($pdf->getFontSubsetting());
    }
}
