<?php
//============================================================+
// File name   : example_029.php
// Begin       : 2008-06-09
// Last Update : 2009-09-30
// 
// Description : Example 029 for TCPDF class
//               Set PDF viewer display preferences.
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
 * @abstract TCPDF - Example: Set PDF viewer display preferences.
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-06-09
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 029');
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

$preferences = array(
	'HideToolbar' => true,
	'HideMenubar' => true,
	'HideWindowUI' => true,
	'FitWindow' => true,
	'CenterWindow' => true,
	'DisplayDocTitle' => true,
	'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
	'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
	'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
	'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
	'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
	'PrintScaling' => 'AppDefault', // None, AppDefault
	'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
	'PickTrayByPDFSize' => true,
	'PrintPageRange' => array(1,1,2,3),
	'NumCopies' => 2
);

// set pdf viewer preferences
$pdf->setViewerPreferences($preferences);

// set font
$pdf->SetFont('times', '', 14);

// add a page
$pdf->AddPage();
// print a line
$pdf->Cell(0, 12, 'DISPLAY PREFERENCES - PAGE 1', 0, 0, 'C');

// add a page
$pdf->AddPage();
// print a line
$pdf->Cell(0, 12, 'DISPLAY PREFERENCES - PAGE 2', 0, 0, 'C');

// add a page
$pdf->AddPage();
// print a line
$pdf->Cell(0, 12, 'DISPLAY PREFERENCES - PAGE 3', 0, 0, 'C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_029.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
