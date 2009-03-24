<?php
//============================================================+
// File name   : example_027.php
// Begin       : 2008-03-04
// Last Update : 2009-03-18
// 
// Description : Example 027 for TCPDF class
//               Barcodes
// 
// Author: Nicola Asuni
// 
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com s.r.l.
//               Via Della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: barcodes.
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 027');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

//set some language-dependent strings
$pdf->setLanguageArray($l); 

// ---------------------------------------------------------

// set a barcode on the page footer
$pdf->setBarcode(date('Y-m-d H:i:s'));

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

$style = array(
	'position' => 'S',
	'border' => true,
	'padding' => 4,
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255),
	'text' => true,
	'font' => 'helvetica',
	'fontsize' => 8,
	'stretchtext' => 4
);

// CODE 39
$pdf->write1DBarcode('CODE 39', 'C39', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39  with checksum
$pdf->write1DBarcode('CODE 39 +', 'C39+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39 EXTENDED
$pdf->write1DBarcode('CODE 39 E', 'C39E', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39 EXTENDED with checksum
$pdf->write1DBarcode('CODE 39 E+', 'C39E+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// Interleaved 2 of 5
$pdf->write1DBarcode('12345678', 'I25', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 A
$pdf->write1DBarcode('CODE 128 A', 'C128A', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 A
$pdf->write1DBarcode('CODE 128 B', 'C128B', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 A
$pdf->write1DBarcode('0123456789', 'C128C', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// EAN 13
$pdf->write1DBarcode('123456789012', 'EAN13', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// UPC-A
$pdf->write1DBarcode('123456789012', 'UPCA', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// UPC-A
$pdf->write1DBarcode('48109-1109', 'POSTNET', '', '', 80, 20, 0.4, $style, 'N');

$pdf->Ln();

// CODABAR
$pdf->write1DBarcode('123456789', 'CODABAR', '', '', 80, 30, 0.4, $style, 'N');

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// TEST BARCDE ALIGNMENTS

// add a page
$pdf->AddPage();

// Left alignment
$style['position'] = 'L';
$pdf->write1DBarcode('LEFT', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

$pdf->Ln();

// Center alignment
$style['position'] = 'C';
$pdf->write1DBarcode('CENTER', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

$pdf->Ln();

// Right alignment
$style['position'] = 'R';
$pdf->write1DBarcode('RIGHT', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_027.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
