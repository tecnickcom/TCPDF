<?php
//============================================================+
// File name   : example_016.php
// Begin       : 2008-03-04
// Last Update : 2009-09-30
// 
// Description : Example 016 for TCPDF class
//               Document Encryption / Security
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
 * @abstract TCPDF - Example: Document Encryption / Security
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

// Set PDF protection (RSA 40bit encryption)
/*
* The permission array is composed of values taken from the following ones:
* - copy: copy text and images to the clipboard
* - print: print the document
* - modify: modify it (except for annotations and forms)
* - annot-forms: add annotations and forms 
* If you don't set any password, the document will open as usual. 
* If you set a user password, the PDF viewer will ask for it before 
* displaying the document. The master password, if different from 
* the user one, can be used to get full access.
* Note: protecting a document requires to encrypt it, which increases the 
* processing time a lot. This can cause a PHP time-out in some cases, 
* especially if the document contains images or fonts.
*/
$pdf->SetProtection(array('print'));

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 016');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array('helvetica', '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array('helvetica', '', PDF_FONT_SIZE_DATA));

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
$pdf->SetFont('times', '', 20);

// add a page
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 10, 'Encryption Example', 1, 1, 'C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_016.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
