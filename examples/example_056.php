<?php
//============================================================+
// File name   : example_056.php
// Begin       : 2010-03-26
// Last Update : 2010-03-26
//
// Description : Example 056 for TCPDF class
//               Crop marks and color registration bars
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
 * @abstract TCPDF - Example: Crop marks and color registration bars
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2010-03-26
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 056');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// disable header and footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

// color registration bars

$pdf->colorRegistrationBar($x=50, $y=50, $w=40, $h=40, $transition=true, $vertical=false, $colors='A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=90, $y=50, $w=40, $h=40, $transition=true, $vertical=true, $colors='A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=50, $y=95, $w=80, $h=5, $transition=false, $vertical=true, $colors='A,W,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=135, $y=50, $w=5, $h=50, $transition=false, $vertical=false, $colors='A,W,R,G,B,C,M,Y,K');

// corner crop marks

$pdf->cropMark($x=50, $y=50, $w=10, $h=10, $type='A', $color=array(0,0,0));
$pdf->cropMark($x=140, $y=50, $w=10, $h=10, $type='B', $color=array(0,0,0));
$pdf->cropMark($x=50, $y=100, $w=10, $h=10, $type='C', $color=array(0,0,0));
$pdf->cropMark($x=140, $y=100, $w=10, $h=10, $type='D', $color=array(0,0,0));

// various crop marks

$pdf->cropMark($x=95, $y=45, $w=5, $h=5, $type='A,B', $color=array(255,0,0));
$pdf->cropMark($x=95, $y=105, $w=5, $h=5, $type='C,D', $color=array(255,0,0));

$pdf->cropMark($x=45, $y=75, $w=5, $h=5, $type='A,C', $color=array(0,255,0));
$pdf->cropMark($x=145, $y=75, $w=5, $h=5, $type='B,D', $color=array(0,255,0));

$pdf->cropMark($x=95, $y=120, $w=5, $h=5, $type='A,D', $color=array(0,0,255));

// registration marks

$pdf->registrationMark($x=40, $y=40, $r=5, $double=false, $cola=array(0,0,0), $colb=array(255,255,255));
$pdf->registrationMark($x=150, $y=40, $r=5, $double=true, $cola=array(0,0,0), $colb=array(255,255,0));
$pdf->registrationMark($x=40, $y=110, $r=5, $double=true, $cola=array(0,0,0), $colb=array(255,255,0));
$pdf->registrationMark($x=150, $y=110, $r=5, $double=false, $cola=array(0,0,0), $colb=array(255,255,255));

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_056.pdf', 'I');

//============================================================+
// END OF FILE                                              
//============================================================+
?>
