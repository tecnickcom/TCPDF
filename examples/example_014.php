<?php
//============================================================+
// File name   : example_014.php
// Begin       : 2008-03-04
// Last Update : 2009-03-18
// 
// Description : Example 014 for TCPDF class
//               Javascript Form and user rights
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
 * @abstract TCPDF - Example: Javascript Form and user rights
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
$pdf->SetTitle('TCPDF Example 014');
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
$pdf->SetFont('helvetica', 'BI', 20);

// add a page
$pdf->AddPage();

// Release User's Rights For PDF reader.
// This is required to display and fill form fields on PDF Readers.
$pdf->setUserRights();

/*
Caution: the generated PDF works only with Acrobat Reader 5.1.
It is possible to create text fields, combo boxes, check boxes and buttons. Fields are created at the current position and are given a name. This name allows to manipulate them via JavaScript in order to perform some validation for instance.
Upon field creation, an associative array can be passed to set a number of properties, among which:
	rect: Position and size of field on page.
	borderStyle: Rectangle border appearance.
	strokeColor: Color of bounding rectangle.
	lineWidth: Width of the edge of the surrounding rectangle.
	rotation: Rotation of field in 90-degree increments.
	fillColor: Background color of field (gray, transparent, RGB, or CMYK).
	userName: Short description of field that appears on mouse-over.
	readonly: Whether the user may change the field contents.
	doNotScroll: Whether text fields may scroll.
	display: Whether visible or hidden on screen or in print.
	textFont: Text font.
	textColor: Text color.
	textSize: Text size.
	richText: Rich text.
	richValue: Text.
	comb: Text comb format.
	multiline: Text multiline.
	charLimit: Text limit to number of characters.
	fileSelect: Text file selection format.
	password: Text password format.
	alignment: Text layout in text fields.
	buttonAlignX: X alignment of icon on button face.
	buttonAlignY: Y alignment of icon on button face.
	buttonFitBounds: Relative scaling of an icon to fit inside a button face.
	buttonScaleHow: Relative scaling of an icon to fit inside a button face.
	buttonScaleWhen: Relative scaling of an icon to fit inside a button face.
	highlight: Appearance of a button when pushed.
	style: Glyph style for checkbox and radio buttons.
	numItems: Number of items in a combo box or list box.
	editable: Whether the user can type in a combo box.
	multipleSelection: Whether multiple list box items may be selected.
Colors can be chosen in the following list (case sensitive): black white red green blue cyan magenta yellow dkGray gray ltGray or be in the form #RRGGBB.
*/

$pdf->Cell(0, 5, 'Subscription form', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('', '', 12);

//First name
$pdf->Cell(35, 5, 'First name:');
$pdf->TextField('firstname', 50, 5, array('strokeColor'=>'ltGray'));
$pdf->Ln(6);

//Last name
$pdf->Cell(35, 5, 'Last name:');
$pdf->TextField('lastname', 50, 5, array('strokeColor'=>'ltGray'));
$pdf->Ln(6);

//Gender
$pdf->Cell(35, 5, 'Gender:');
$pdf->ComboBox('gender', 10, 5, array('', 'M', 'F'), array('strokeColor'=>'ltGray'));
$pdf->Ln(6);

//Drink
$pdf->Cell(35, 5, 'Drink:');
$pdf->RadioButton('drink', 5, false);
$pdf->Cell(35, 5, 'Water');
$pdf->Ln(6);
$pdf->Cell(35, 5, '');
$pdf->RadioButton('drink', 5, false);
$pdf->Cell(35, 5, 'Beer');
$pdf->Ln(6);
$pdf->Cell(35, 5, '');
$pdf->RadioButton('drink', 5, false);
$pdf->Cell(35, 5, 'Wine');
// set export values
$pdf->IncludeJS('fdrink.exportValues=["Water", "Beer", "Wine"];'."\n");
// check the second radiobutton
$pdf->IncludeJS("fdrink.checkThisBox(1,true);\n");
$pdf->Ln(10);

//Gender
$pdf->Cell(35, 5, 'List:');
$pdf->ListBox('listbox', 60, 15, array('', 'item1', 'item2', 'item3', 'item4', 'item5', 'item6', 'item7'), array('multipleSelection'=>'true'));
$pdf->Ln(20);

//Adress
$pdf->Cell(35, 5, 'Address:');
$pdf->TextField('address', 60, 18, array('multiline'=>true,'strokeColor'=>'ltGray'));
$pdf->Ln(19);

//E-mail
$pdf->Cell(35, 5, 'E-mail:');
$pdf->TextField('email', 50, 5, array('strokeColor'=>'ltGray'));
$pdf->Ln(6);

//Newsletter
$pdf->Cell(35, 5, 'Receive our', 0, 1);
$pdf->Cell(35, 5, 'newsletter:');
$pdf->CheckBox('newsletter', 5, true);
$pdf->Ln(10);

//Date of the day (determined and formatted by JS)
$pdf->Write(5, 'Date: ');
$pdf->TextField('date', 30, 5);
$pdf->IncludeJS("getField('date').value=util.printd('dd/mm/yyyy',new Date());\n");
$pdf->Ln();
$pdf->Write(5, 'Signature:');
$pdf->Ln(3);

//Button to validate and print
$pdf->SetX(95);
$pdf->Button('print', 20, 8, 'Print', 'Print()', array('textColor'=>'yellow', 'fillColor'=>'#FF5050'));

//Form validation functions
$pdf->IncludeJS("
function CheckField(name,message) {
	var f = getField(name);
	if(f.value == '') {
	    app.alert(message);
	    f.setFocus();
	    return false;
	}
	return true;
}

function Print() {
	//Validation
	if(!CheckField('firstname','First name is mandatory'))
		return;
	if(!CheckField('lastname','Last name is mandatory'))
		return;
	if(!CheckField('gender','Gender is mandatory'))
		return;
	if(!CheckField('address','Address is mandatory'))
		return;
	//Print
	print();
}
");

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_014.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
