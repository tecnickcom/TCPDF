<?php
//============================================================+
// File name   : example_010.php
// Begin       : 2008-03-04
// Last Update : 2010-02-24
// 
// Description : Example 010 for TCPDF class
//               Text on multiple columns
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
 * @abstract TCPDF - Example: Text on multiple columns
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');


/**
* Extend TCPDF to work with multiple columns
*/
class MC_TCPDF extends TCPDF {
	
	/**
	 * @var number of colums
	 * @access protected
	 */
	protected $ncols = 3;
	
	/**
	 * @var columns width
	 * @access protected
	 */
	protected $colwidth = 57;
	
	/**
	 * @var current column
	 * @access protected
	 */
	protected $col = 0;
	
	/**
	 * @var y position of the beginning of column
	 * @access protected
	 */
	protected $col_start_y;
	
	/**
	 * Set position at a given column
	 * @param $col column number (from 0 to $ncols-1)
	 * @access public
	 */
	public function SetCol($col) {
		$this->col = $col;
		// set space between columns
		if ($this->ncols > 1) {
			$column_space = round((float)($this->w - $this->original_lMargin - $this->original_rMargin - ($this->ncols * $this->colwidth)) / ($this->ncols - 1));
		} else {
			$column_space = 0;
		}
		// set X position of the current column by case
		if ($this->rtl) {
			$x = $this->w - $this->original_rMargin - ($col * ($this->colwidth + $column_space));
			$this->SetRightMargin($this->w - $x);
			$this->SetLeftMargin($x - $this->colwidth);
		} else {
			$x = $this->original_lMargin + ($col * ($this->colwidth + $column_space));
			$this->SetLeftMargin($x);
			$this->SetRightMargin($this->w - $x - $this->colwidth);
		}
		$this->x = $x;
		if ($col > 0) {
			// set Y position for the column
			$this->y = $this->col_start_y;
		}
		// fix for HTML mode
		$this->newline = true;
	}
	
	/**
	 * Overwrites the AcceptPageBreak() method to switch between columns
	 * @return boolean false
	 * @access public
	 */
	public function AcceptPageBreak() {
		if($this->col < ($this->ncols - 1)) {
			// go to next column
			$this->SetCol($this->col + 1);
		} else {
			// go back to first column on the new page
			$this->AddPage();
			$this->SetCol(0);
		}
		// avoid page breaking from checkPageBreak()
		return false;
	}
	
	/**
	 * Set chapter title
	 * @param int $num chapter number
	 * @param string $title chapter title
	 * @access public
	 */
	public function ChapterTitle($num, $title) {
		$this->SetFont('helvetica', '', 14);
		$this->SetFillColor(200, 220, 255);
		$this->Cell(0, 6, 'Chapter '.$num.' : '.$title, 0, 1, '', 1);
		$this->Ln(4);
		// save current Y position
		$this->col_start_y = $this->GetY();
	}
	
	/**
	 * Print chapter body
	 * @param string $file name of the file containing the chapter body
	 * @param boolean $mode if true the chapter body is in HTML, otherwise in simple text.
	 * @access public
	 */
	public function ChapterBody($file, $mode=false) {
		// store current margin values
		$lMargin = $this->lMargin;
		$rMargin = $this->rMargin;
		// get esternal file content
		$txt = file_get_contents($file, false);
		// set font
		$this->SetFont('times', '', 9);
		// set first column
		$this->SetCol(0);
		if ($mode) {
			// ------ HTML MODE ------
			$this->writeHTML($txt, true, false, true, false, 'J');
		} else {
			// ------ TEXT MODE ------
			$this->Write(0, $txt, '', 0, 'J', true, 0, false, false, 0);
		}
		$this->Ln();
		// Go back to first column
		$this->SetCol(0);
		// restore previous margin values
		$this->SetLeftMargin($lMargin);
		$this->SetRightMargin($rMargin);
	}
	
	/**
	 * Print chapter
	 * @param int $num chapter number
	 * @param string $title chapter title
	 * @param string $file name of the file containing the chapter body
	 * @param boolean $mode if true the chapter body is in HTML, otherwise in simple text.
	 * @access public
	 */
	public function PrintChapter($num, $title, $file, $mode=false) {
		$this->AddPage();
		$this->ChapterTitle($num, $title);
		$this->ChapterBody($file, $mode);
	}
}

// ---------------------------------------------------------
// EXAMPLE
// ---------------------------------------------------------
// create new PDF document
$pdf = new MC_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 010');
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

// print a text
$pdf->PrintChapter(1, 'A RUNAWAY REEF', '../cache/chapter_demo_1.txt', false);

// print an html text
$pdf->PrintChapter(2, 'THE PROS AND CONS', '../cache/chapter_demo_2.txt', true);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_010.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
