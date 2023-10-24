<?php
//============================================================+
// File name   : example_068.php
// Begin       : 2022-01-25
// Last Update : 2022-01-25
//
// Description : Example 068 for TCPDF class
//               Creates an memory intensive PDF document using TCPDF
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 068');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 068', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('helvetica', '', 14, '', true);

// Add a page
$pdf->AddPage();

// Use page cache. Up to 2mb of the cache will be stored in memory. Overflow will be saved to a temporary file
$pageCacheSize = 2;
$pdf->usePageCacheFile($pageCacheSize);

// Write a lot of cells
$cols = ['A', 'B', 'C', 'D', 'E'];
$colWidth = 160 / count($cols);
$lastColIdx = count($cols) - 1;
for ($i = 0; $i < 5000; $i++)
{
	$page = $pdf->getPage();
	$y = $pdf->getY();
	$x = $pdf->getX();
	foreach ($cols as $idx=>$col)
	{
		$pdf->MultiCell($colWidth, 6, str_repeat($col . $i, 3), 1, 'C');
		$x += $colWidth;
		if ($idx !== $lastColIdx)
		{
			if ($pdf->getPage() !== $page)
			{
				// Page break happened, so go back to previous page
				$pdf->setPage($page);
			}

			$pdf->setY($y);
			$pdf->setX($x);
		}
	}
}

// Copy page will restore page from cache
$pdf->copyPage(1);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('example_068.pdf', 'I');

// Show memory usage
// These will cause tests to fail, so leave them commented out, but you can use them to debug.
// fputs(STDERR, 'Page cache size: ' . $pageCacheSize . 'MB' . PHP_EOL);
// fputs(STDERR, sprintf('Peak memory: %0.03fMB', memory_get_peak_usage() / 1024 / 1024) . PHP_EOL);

//============================================================+
// END OF FILE
//============================================================+
