<?php
//============================================================+
// File name   : example_022.php
// Begin       : 2008-03-04
// Last Update : 2009-03-18
// 
// Description : Example 022 for TCPDF class
//               CMYK colors
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
 * @abstract TCPDF - Example: CMYK colors.
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
$pdf->SetTitle('TCPDF Example 022');
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

// set font
$pdf->SetFont('helvetica', "B", 12);

// add a page
$pdf->AddPage();

$pdf->SetLineWidth(1);

$pdf->SetDrawColor(50, 0, 0, 0);
$pdf->SetFillColor(100, 0, 0, 0);
$pdf->SetTextColor(100, 0, 0, 0);
$pdf->Rect(30, 60, 20, 20, 'DF');
$pdf->Text(30, 85, 'Cyan');

$pdf->SetDrawColor(0, 50, 0, 0);
$pdf->SetFillColor(0, 100, 0, 0);
$pdf->SetTextColor(0, 100, 0, 0);
$pdf->Rect(60, 60, 20, 20, 'DF');
$pdf->Text(60, 85, 'Magenta');

$pdf->SetDrawColor(0, 0, 50, 0);
$pdf->SetFillColor(0, 0, 100, 0);
$pdf->SetTextColor(0, 0, 100, 0);
$pdf->Rect(90, 60, 20, 20, 'DF');
$pdf->Text(90, 85, 'Yellow');

$pdf->SetDrawColor(0, 0, 0, 50);
$pdf->SetFillColor(0, 0, 0, 100);
$pdf->SetTextColor(0, 0, 0, 100);
$pdf->Rect(120, 60, 20, 20, 'DF');
$pdf->Text(120, 85, 'Black');

$pdf->SetDrawColor(128, 0, 0);
$pdf->SetFillColor(255, 0, 0);
$pdf->SetTextColor(255, 0, 0);
$pdf->Rect(30, 100, 20, 20, 'DF');
$pdf->Text(30, 125, 'Red');

$pdf->SetDrawColor(0, 127, 0);
$pdf->SetFillColor(0, 255, 0);
$pdf->SetTextColor(0, 255, 0);
$pdf->Rect(60, 100, 20, 20, 'DF');
$pdf->Text(60, 125, 'Green');

$pdf->SetDrawColor(0, 0, 127);
$pdf->SetFillColor(0, 0, 255);
$pdf->SetTextColor(0, 0, 255);
$pdf->Rect(90, 100, 20, 20, 'DF');
$pdf->Text(90, 125, 'Blue');

$pdf->SetDrawColor(50);
$pdf->SetFillColor(128);
$pdf->SetTextColor(128);
$pdf->Rect(30, 140, 20, 20, 'DF');
$pdf->Text(30, 165, 'Gray');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_022.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
