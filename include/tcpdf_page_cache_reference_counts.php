<?php
//============================================================+
// File name   : tcpdf_page_cache_reference_counts.php
// Version     : 1.0.000
// Begin       : 2023-08-04
// Last Update : 2023-08-04
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2002-2013  Nicola Asuni - Tecnick.com LTD
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
// Description : Class to keep track of page cache references
//
//============================================================+

/**
 * @file
 * PHP page cache reference counts class for TCPDF
 * @package com.tecnick.tcpdf
 */

/**
 * @class TCPDF_PAGE_CACHE_REFERENCE_COUNTS
 * PHP page cache reference counts class for TCPDF
 * @package com.tecnick.tcpdf
 * @version 1.0.000
 */
class TCPDF_PAGE_CACHE_REFERENCE_COUNTS
{
	/** @var int Number of references */
	private $numReferences = 0;

	/**
	 * Increment reference count
	 */
	public function incrementReferenceCount()
	{
		$this->numReferences++;
	}

	/**
	 * Decrement reference count
	 */
	public function decrementReferenceCount()
	{
		$this->numReferences--;
	}

	/**
	 * Get reference count
	 *
	 * @return int Reference count
	 */
	public function getReferenceCount()
	{
		return $this->numReferences;
	}
}
