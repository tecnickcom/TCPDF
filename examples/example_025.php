<?php
//============================================================+
// File name   : example_025.php
// Begin       : 2008-03-04
// Last Update : 2009-09-30
// 
// Description : Example 025 for TCPDF class
//               Object Transparency
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
 * @abstract TCPDF - Example: Object Transparency
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
$pdf->SetTitle('TCPDF Example 025');
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
$pdf->SetFont('helvetica', 'BI', 8);

// add a page
$pdf->AddPage();

/*
 * setAlpha() gives transparency support. You can set the 
 * alpha channel from 0 (fully transparent) to 1 (fully 
 * opaque). It applies to all elements (text, drawings, 
 * images).
 */
 
$pdf->SetLineWidth(1.5);
    
// draw opaque red square
$pdf->SetFillColor(255, 0, 0);
$pdf->Rect(30, 60, 40, 40, 'DF');

// set alpha to semi-transparency
$pdf->SetAlpha(0.5);

// draw green square
$pdf->SetFillColor(0, 255, 0);
$pdf->Rect(40, 70, 40, 40, 'DF');

// draw jpeg image
$pdf->Image('../images/image_demo.jpg', 50, 80, 40, 40, '', 'http://www.tcpdf.org', '', true, 72);

// restore full opacity
$pdf->SetAlpha(1);

// print name
$pdf->Text(55,85,'TRANSPARENCY');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_025.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
