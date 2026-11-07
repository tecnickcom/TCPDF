<?php

//============================================================+
// File name   : example_002.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 002 for TCPDF class
//               Removing Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Removing Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 * @group header
 * @group footer
 * @group page
 * @group pdf
 */

// Include the main TCPDF library (search for installation path).
require_once 'tcpdf_include.php';

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('Nicola Asuni');
$pdf->setTitle('TCPDF Example 002');
$pdf->setSubject('TCPDF Tutorial');
$pdf->setKeywords('TCPDF, PDF, example, test, guide');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

// set auto page breaks
$pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once dirname(__FILE__) . '/lang/eng.php';
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->setFont('times', 'BI', 20);

// add a page
$pdf->AddPage();

// set some text to print
$txt = <<<EOD
    TCPDF Example 002

    Default page header and footer are disabled using setPrintHeader() and setPrintFooter() methods.
    EOD;

// print a block of text using Write()
$pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);

// ---------------------------------------------------------

// The cursor set by setX() only says where a line may start: it carries the
// left cell padding like a line starting at the margin, and it does not move
// the right margin a right aligned line is flush with.
$pdf->setFont('times', '', 12);
$pdf->Ln(10);

$pdf->Write(0, 'left aligned from the left margin', '', 0, 'L', true);
$pdf->setX(80);
$pdf->Write(0, 'left aligned from setX(80)', '', 0, 'L', true);

$pdf->Write(0, 'right aligned from the left margin', '', 0, 'R', true);
$pdf->setX(80);
$pdf->Write(0, 'right aligned from setX(80)', '', 0, 'R', true);

// ---------------------------------------------------------

// Consecutive Write() calls continue the line where the previous text ends:
// each call can switch the style or add a link without leaving a gap.
$pdf->setFont('times', '', 12);
$pdf->Ln(10);
$pdf->Write(0, 'A sentence with ');
$pdf->setFont('times', 'I', 12);
$pdf->Write(0, 'italic');
$pdf->setFont('times', '', 12);
$pdf->Write(0, ', ');
$pdf->setFont('times', 'U', 12);
$pdf->Write(0, 'underlined');
$pdf->setFont('times', '', 12);
$pdf->Write(0, ' and ');
$pdf->Write(0, 'linked', 'https://tcpdf.org');
$pdf->Write(0, ' words.', '', 0, 'L', true);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_002.pdf', 'I');
