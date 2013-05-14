<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2013-05-14
//
// Description : Configuration file for TCPDF.
// Author      : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2004-2013  Nicola Asuni - Tecnick.com LTD
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
//============================================================+

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
 * @version 4.9.005
 * @since 2004-10-27
 */

// If you define the constant K_TCPDF_EXTERNAL_CONFIG, the following settings will be ignored.

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {

	// DOCUMENT_ROOT fix for IIS Webserver
	if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
		if(isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
		} elseif(isset($_SERVER['PATH_TRANSLATED'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
		} else {
			// define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
			$_SERVER['DOCUMENT_ROOT'] = '/';
		}
	}
	// be sure that the end slash is present
	$_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/');

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Try to automatically set the value for the following K_PATH_MAIN constant.
	// NOTE: delete this section and manually set the K_PATH_MAIN constant below for better performances.
	$tcpdf_install_dirs = array();
	if (strpos(dirname(__FILE__), '/config') !== false) {
		// default config file
		$k_path_main_default = str_replace( '\\', '/', realpath(substr(dirname(__FILE__), 0, 0-strlen('config'))));
		if (substr($k_path_main_default, -1) != '/') {
			$k_path_main_default .= '/';
		}
		$tcpdf_install_dirs[] = $k_path_main_default;
	}
	$tcpdf_install_dirs += array('/usr/share/php/tcpdf/', '/usr/share/tcpdf/', '/usr/share/php-tcpdf/', '/var/www/tcpdf/', '/var/www/html/tcpdf/', '/usr/local/apache2/htdocs/tcpdf/');
	foreach ($tcpdf_install_dirs as $k_path_main) {
		if (file_exists($k_path_main.'tcpdf.php')) {
			break;
		}
	}
	if (!file_exists($k_path_main)) {
		die('TCPDF ERROR: please set the correct path to TCPDF on the configuration file');
	}
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Installation path (/var/www/tcpdf/).
	 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
	 */
	define ('K_PATH_MAIN', $k_path_main);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Try to automatically set the value for the following K_PATH_URL constant.
	// NOTE: delete this section and manually set the K_PATH_URL constant below for better performances.
	$k_path_url = $k_path_main; // default value for console mode
	if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))) {
		if(isset($_SERVER['HTTPS']) AND (!empty($_SERVER['HTTPS'])) AND (strtolower($_SERVER['HTTPS']) != 'off')) {
			$k_path_url = 'https://';
		} else {
			$k_path_url = 'http://';
		}
		$k_path_url .= $_SERVER['HTTP_HOST'];
		$k_path_url .= str_replace( '\\', '/', substr(K_PATH_MAIN, (strlen($_SERVER['DOCUMENT_ROOT']) - 1)));
	}
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * URL path to tcpdf installation folder (http://localhost/tcpdf/).
	 * By default it is automatically set but you can also set it as a fixed string to improve performances.
	 */
	define ('K_PATH_URL', $k_path_url);

	/**
	 * Cache directory for temporary files (full path).
	 */
	define ('K_PATH_CACHE', sys_get_temp_dir().'/');

	/**
	 * Path for PDF fonts.
	 */
	define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

	/**
	 * Default images directory.
	 */
	define ('K_PATH_IMAGES', K_PATH_MAIN.'examples/images/');

	/**
	 * Blank image.
	 */
	define ('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');

	/**
	 * Page format.
	 */
	define ('PDF_PAGE_FORMAT', 'A4');

	/**
	 * Page orientation (P=portrait, L=landscape).
	 */
	define ('PDF_PAGE_ORIENTATION', 'P');

	/**
	 * Document creator.
	 */
	define ('PDF_CREATOR', 'TCPDF');

	/**
	 * Document author.
	 */
	define ('PDF_AUTHOR', 'TCPDF');

	/**
	 * Header title.
	 */
	define ('PDF_HEADER_TITLE', 'TCPDF Example');

	/**
	 * Header description string.
	 */
	define ('PDF_HEADER_STRING', "by Nicola Asuni - Tecnick.com\nwww.tcpdf.org");

	/**
	 * Image logo.
	 */
	define ('PDF_HEADER_LOGO', 'tcpdf_logo.jpg');

	/**
	 * Header logo image width [mm].
	 */
	define ('PDF_HEADER_LOGO_WIDTH', 30);

	/**
	 * Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch].
	 */
	define ('PDF_UNIT', 'mm');

	/**
	 * Header margin.
	 */
	define ('PDF_MARGIN_HEADER', 5);

	/**
	 * Footer margin.
	 */
	define ('PDF_MARGIN_FOOTER', 10);

	/**
	 * Top margin.
	 */
	define ('PDF_MARGIN_TOP', 27);

	/**
	 * Bottom margin.
	 */
	define ('PDF_MARGIN_BOTTOM', 25);

	/**
	 * Left margin.
	 */
	define ('PDF_MARGIN_LEFT', 15);

	/**
	 * Right margin.
	 */
	define ('PDF_MARGIN_RIGHT', 15);

	/**
	 * Default main font name.
	 */
	define ('PDF_FONT_NAME_MAIN', 'helvetica');

	/**
	 * Default main font size.
	 */
	define ('PDF_FONT_SIZE_MAIN', 10);

	/**
	 * Default data font name.
	 */
	define ('PDF_FONT_NAME_DATA', 'helvetica');

	/**
	 * Default data font size.
	 */
	define ('PDF_FONT_SIZE_DATA', 8);

	/**
	 * Default monospaced font name.
	 */
	define ('PDF_FONT_MONOSPACED', 'courier');

	/**
	 * Ratio used to adjust the conversion of pixels to user units.
	 */
	define ('PDF_IMAGE_SCALE_RATIO', 1.25);

	/**
	 * Magnification factor for titles.
	 */
	define('HEAD_MAGNIFICATION', 1.1);

	/**
	 * Height of cell respect font height.
	 */
	define('K_CELL_HEIGHT_RATIO', 1.25);

	/**
	 * Title magnification respect main font size.
	 */
	define('K_TITLE_MAGNIFICATION', 1.3);

	/**
	 * Reduction factor for small font.
	 */
	define('K_SMALL_RATIO', 2/3);

	/**
	 * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language.
	 */
	define('K_THAI_TOPCHARS', true);

	/**
	 * If true allows to call TCPDF methods using HTML syntax
	 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
	 */
	define('K_TCPDF_CALLS_IN_HTML', true);

	/**
	 * If true adn PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
	 */
	define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
}

//============================================================+
// END OF FILE
//============================================================+
