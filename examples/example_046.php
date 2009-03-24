<?php
//============================================================+
// File name   : example_046.php
// Begin       : 2009-02-28
// Last Update : 2009-03-18
// 
// Description : Example 046 for TCPDF class
//               Text Hyphenation
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
 * @abstract TCPDF - Example: text Hyphenation
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2009-02-28
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 046');
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
$pdf->SetFont('times', '', 10);

// add a page
$pdf->AddPage();

/*
You can use an external class to automatically hyphens the text by adding the SHY characters;

Unicode Data for SHY:
	Name : SOFT HYPHEN, commonly abbreviated as SHY
	HTML Entity (decimal): &#173;
	HTML Entity (hex): &#xad;
	HTML Entity (named): &shy;
	How to type in Microsoft Windows: [Alt +00AD] or [Alt 0173]
	UTF-8 (hex): 0xC2 0xAD (c2ad)
*/

// html text with soft hyphens (&shy;)
$html = 'On the other hand, we de&shy;nounce with righ&shy;teous in&shy;dig&shy;na&shy;tion and dis&shy;like men who are so be&shy;guiled and de&shy;mo&shy;r&shy;al&shy;ized by the charms of plea&shy;sure of the mo&shy;ment, so blind&shy;ed by de&shy;sire, that they can&shy;not fore&shy;see the pain and trou&shy;ble that are bound to en&shy;sue; and equal blame be&shy;longs to those who fail in their du&shy;ty through weak&shy;ness of will, which is the same as say&shy;ing through shrink&shy;ing from toil and pain. Th&shy;ese cas&shy;es are per&shy;fect&shy;ly sim&shy;ple and easy to distin&shy;guish. In a free hour, when our pow&shy;er of choice is un&shy;tram&shy;melled and when noth&shy;ing pre&shy;vents our be&shy;ing able to do what we like best, ev&shy;ery plea&shy;sure is to be wel&shy;comed and ev&shy;ery pain avoid&shy;ed. But in cer&shy;tain cir&shy;cum&shy;s&shy;tances and ow&shy;ing to the claims of du&shy;ty or the obli&shy;ga&shy;tions of busi&shy;ness it will fre&shy;quent&shy;ly oc&shy;cur that plea&shy;sures have to be re&shy;pu&shy;di&shy;at&shy;ed and an&shy;noy&shy;ances ac&shy;cept&shy;ed. The wise man there&shy;fore al&shy;ways holds in th&shy;ese mat&shy;ters to this prin&shy;ci&shy;ple of se&shy;lec&shy;tion: he re&shy;jects plea&shy;sures to se&shy;cure other greater plea&shy;sures, or else he en&shy;dures pains to avoid worse pains.';

// print a cell
$pdf->writeHTMLCell(50, 0, '', '', $html, 1, 1, 0, true, 'J');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_046.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
