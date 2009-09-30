<?php
//============================================================+
// File name   : example_049.php
// Begin       : 2009-04-03
// Last Update : 2009-09-30
// 
// Description : Example 049 for TCPDF class
//               WriteHTML with TCPDF callback functions
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
 * @abstract TCPDF - Example: WriteHTML with TCPDF callback functions
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
$pdf->SetTitle('TCPDF Example 049');
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
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

/*
NOTE:
When using TCPDF methods embedded on XHTML code, you have to escape special
characters with equivalent HTML entities:

- replace double quotes with: &quot;
- replace single quote with: &#x5c;&#x27;
- replace > with: &gt;
- replace < with: &lt;

Note that the single quote escape contains an additional back-slash character.
*/

$htmlcontent = <<<EOF
<h1>Test TCPDF Methods in HTML</h1>
<h2>write1DBarcode method in HTML</h2>
<tcpdf method="write1DBarcode" params="'CODE 39', 'C39', '', '', 80, 30, 0.4, array('position'=&gt;'S', 'border'=&gt;true, 'padding'=&gt;4, 'fgcolor'=&gt;array(0,0,0), 'bgcolor'=&gt;array(255,255,255), 'text'=&gt;true, 'font'=&gt;'helvetica', 'fontsize'=&gt;8, 'stretchtext'=&gt;4), 'N'" />
<tcpdf method="write1DBarcode" params="'CODE 128C+ &quot; &#x5c;&#x27;',
'C128C', '', '', 80, 30, 0.4, array('position'=&gt;'S', 'border'=&gt;true,
'padding'=&gt;4, 'fgcolor'=&gt;array(0,0,0),
'bgcolor'=&gt;array(255,255,255), 'text'=&gt;true, 'font'=&gt;'helvetica',
'fontsize'=&gt;8, 'stretchtext'=&gt;4), 'N'" />
<tcpdf method="AddPage" />
<h2> Graphic Functions</h2>
<tcpdf method="SetDrawColor" params="0" />
<tcpdf method="Rect" params="50, 50, 40, 10, 'DF', array(), array(0,128,255)" />
EOF;

// output the HTML content
$pdf->writeHTML($htmlcontent, true, 0, true, 0);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_049.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
