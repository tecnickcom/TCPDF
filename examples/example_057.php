<?php
//============================================================+
// File name   : example_057.php
// Begin       : 2010-04-03
// Last Update : 2010-04-06
//
// Description : Example 057 for TCPDF class
//               Cell vertical alignments
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
 * @abstract TCPDF - Example: Cell vertical alignments
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
$pdf->SetTitle('TCPDF Example 057');
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
$pdf->SetFont('helvetica', '', 14);

// add a page
$pdf->AddPage();

$pdf->setCellHeightRatio(3);

$pdf->SetXY(17, 50);

$pdf->Cell(35, 0, 'Cell Top', 1, $ln=0, 'C', 0, '', 0, false, 'T');
$pdf->Cell(35, 0, 'Font Top', 1, $ln=0, 'C', 0, '', 0, false, 'A');
$pdf->Cell(35, 0, 'Font Baseline', 1, $ln=0, 'C', 0, '', 0, false, 'L');
$pdf->Cell(35, 0, 'Font Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'D');
$pdf->Cell(35, 0, 'Cell Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'B');



$pdf->SetXY(30, 70);

$pdf->Cell(50, 0, 'Text Top', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'T');
$pdf->Cell(50, 0, 'Text Center', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'M');
$pdf->Cell(50, 0, 'Text Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'B');


$linestyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '', 'phase' => 0, 'color' => array(255, 0, 0));
$pdf->Line(15, 50, 195, 50, $linestyle);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_057.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
?>
