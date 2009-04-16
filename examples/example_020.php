<?php
//============================================================+
// File name   : example_020.php
// Begin       : 2008-03-04
// Last Update : 2009-04-16
//
// Description : Example 020 for TCPDF class
//               Two columns composed by MultiCell of different 
//               heights
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
* @abstract TCPDF - Example: Two columns composed by MultiCell of different heights
* @author Nicola Asuni
* @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
* @link http://tcpdf.org
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* @since 2008-03-04
*/

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// extend TCPF with custom functions
class MYPDF extends TCPDF { 
	public function MultiRow($left, $right) {
		//MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)
		
		$page_start = $this->getPage();
		$y_start = $this->GetY();
		
		// write the left cell
		$this->MultiCell(40, 0, $left, 1, 'R', 0, 2, '', '', true, 0);
		
		$page_end_1 = $this->getPage();
		$y_end_1 = $this->GetY();
		
		$this->setPage($page_start);
		
		// write the right cell
		$this->MultiCell(0, 0, $right, 1, 'J', 0, 1, $this->GetX() ,$y_start, true, 0);
		
		$page_end_2 = $this->getPage();
		$y_end_2 = $this->GetY();
		
		// set the new row position by case
		if (max($page_end_1,$page_end_2) == $page_start) {
			$ynew = max($y_end_1, $y_end_2);
		} elseif ($page_end_1 == $page_end_2) {
			$ynew = max($y_end_1, $y_end_2);
		} elseif ($page_end_1 > $page_end_2) {
			$ynew = $y_end_1;
		} else {
			$ynew = $y_end_2;
		}
		
		$this->setPage(max($page_end_1,$page_end_2));
		$this->SetXY($this->GetX(),$ynew);
	}
	
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 020');
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
$pdf->SetFont('times', '', 9);
// add a page
$pdf->AddPage();

//$pdf->SetCellPadding(0);
//$pdf->SetLineWidth(2);

$text = 'Cras eros leo, porttitor porta, accumsan fermentum, ornare ac, est. Praesent dui lorem, imperdiet at, cursus sed, facilisis aliquam, nibh. Nulla accumsan nonummy diam. Donec tempus. Etiam posuere. Proin lectus. Donec purus. Duis in sem pretium urna feugiat vehicula. Ut suscipit velit eget massa. Nam nonummy, enim commodo euismod placerat, tortor elit tempus lectus, quis suscipit metus lorem blandit turpis. Cras nulla nulla, hendrerit et, porttitor eu, adipiscing et, lorem. Pellentesque sit amet augue. Nam lobortis sollicitudin turpis. Sed velit est, mollis non, elementum ac, tempor quis, arcu. Aliquam a pede. Quisque arcu magna, nonummy eget, hendrerit a, lacinia egestas, enim. Donec bibendum. In a ipsum. Sed gravida facilisis sem. Nam tempus, tellus ut tincidunt elementum, augue tellus fermentum quam, sit amet lobortis sem ipsum sed elit.In accumsan ligula nonummy libero. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed vel risus. Vestibulum ut lacus. Proin fermentum, erat a commodo lacinia, lacus dui hendrerit nulla, et pellentesque neque diam at elit. Fusce blandit, dolor pharetra bibendum lacinia, augue sem scelerisque sem, bibendum sodales orci justo et sapien. Etiam nec eros ac turpis lobortis interdum. Integer volutpat nibh a lacus. Duis erat est, rhoncus nec, rhoncus viverra, pulvinar sit amet, leo. Duis blandit. Nunc convallis nisi ac ante. Cras nunc massa, molestie quis, porttitor a, volutpat accumsan, quam. Nullam a erat vitae orci bibendum viverra. Donec tristique leo eget nisl adipiscing pellentesque. Nam vehicula, enim quis aliquet euismod, dolor sem pellentesque libero, nec blandit nisi erat sit amet dui. Integer sapien. Donec molestie metus in neque. Suspendisse porttitor enim a nisl.Maecenas lacinia dolor ornare ligula. Maecenas eu eros. Curabitur non leo non nulla fringilla auctor. Etiam porttitor diam vel quam. Maecenas sed ligula nec massa venenatis faucibus. Curabitur aliquet accumsan tellus. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Suspendisse vitae eros ac purus fermentum suscipit. Curabitur interdum orci a mi. Nunc placerat diam in elit.Nunc elit. Maecenas vulputate, sem sit amet condimentum lacinia, ipsum eros porta dolor, sed luctus magna ante eu nisl. Proin non nisi. Vivamus sed quam et est lobortis porttitor. Cras sit amet urna sit amet elit ultricies consequat. Praesent blandit elit ut urna. Cras hendrerit rhoncus sapien. Fusce ullamcorper lobortis ipsum. Pellentesque vel velit at sem blandit facilisis. Nulla aliquet orci id metus.';

// print some rows just as example
for ($i = 0; $i < 5; $i++) {
	$pdf->MultiRow('Row '.($i+1), $text."\n");
}

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_020.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?> 
