<?php
//============================================================+
// File name   : pdf417compact.php
// Version     : 1.0.0
// Begin       : 2015-08-25
// Last Update : 2015-08-25
// Author      : Jeff Rego - RetailMeNot - www.retailmenot.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2015 - Jeff Rego - RetailMeNot
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// DESCRIPTION :
//
// Subclass of PDF417 to create PDF417 barcode arrays with truncated stop codes for TCPDF class.
// Truncated stop codes are described http://www.neodynamic.com/Products/Help/BarcodeCF2.0/barcodes/CompactPdf417.htm
// PDF417 (ISO/IEC 15438:2006) is a 2-dimensional stacked bar code created by Symbol Technologies in 1991.
// It is one of the most popular 2D codes because of its ability to be read with slightly modified handheld laser or linear CCD scanners.
// TECHNICAL DATA / FEATURES OF PDF417:
//		Encodable Character Set:     All 128 ASCII Characters (including extended)
//		Code Type:                   Continuous, Multi-Row
//		Symbol Height:               3 - 90 Rows
//		Symbol Width:                90X - 583X
//		Bidirectional Decoding:      Yes
//		Error Correction Characters: 2 - 512
//		Maximum Data Characters:     1850 text, 2710 digits, 1108 bytes
//
//============================================================+

/**
 * @file
 * Subclass of PDF417 to create PDF417 barcode arrays with truncated stop codes for TCPDF class.
 * PDF417 (ISO/IEC 15438:2006) is a 2-dimensional stacked bar code created by Symbol Technologies in 1991.
 * (requires PHP bcmath extension)
 * @package com.tecnick.tcpdf
 * @author Jeff Rego
 * @version 1.0.0
 */

require_once(dirname(__FILE__).'/pdf417.php');

class PDF417Compact extends PDF417 {

	/**
	 * Stop pattern override.
	 * @protected
	 */
	protected $stop_pattern = '1';

} // end PDF417Compact class

//============================================================+
// END OF FILE
//============================================================+
