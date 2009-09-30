<?php
//============================================================+
// File name   : example_007.php
// Begin       : 2008-03-04
// Last Update : 2009-09-30
// 
// Description : Example 007 for TCPDF class
//               Two independent columns with WriteHTMLCell
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
 * @abstract TCPDF - Example: Two independent columns with WriteHTMLCell
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
$pdf->SetTitle('TCPDF Example 007');
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
$pdf->SetFont('times', '', 12);

// add a page
$pdf->AddPage();

// create columns content
$left_column = '<b>left column</b> left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column left column';

$right_column = '<b>right column</b> right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column right column';

// set columns width
$first_column_width = 80;
$second_column_width = 80;

// get current vertical position
$current_y_position = $pdf->getY();

// write the first column
$pdf->writeHTMLCell($first_column_width, '', '', $current_y_position, $left_column, 1, 0, 0, true);

// write the second column
$pdf->writeHTMLCell($second_column_width, '', '', '', $right_column, 1, 1, 0, true);

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_007.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
