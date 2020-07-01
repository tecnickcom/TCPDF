<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2014-12-11
//
// Description : Configuration file for TCPDF.
// Author      : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2004-2014  Nicola Asuni - Tecnick.com LTD
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

// IMPORTANT:
// If you define the constant K_TCPDF_EXTERNAL_CONFIG, all the following settings will be ignored.
// If you use the tcpdf_autoconfig.php, then you can overwrite some values here.


/**
 * Installation path (/var/www/tcpdf/).
 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_MAIN', '');

/**
 * URL path to tcpdf installation folder (http://localhost/tcpdf/).
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_URL', '');

/**
 * Path for PDF fonts.
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

/**
 * Default images directory.
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_IMAGES', '');

/**
 * Deafult image logo used be the default Header() method.
 * Please set here your own logo or an empty string to disable it.
 */
//define ('PDF_HEADER_LOGO', '');

/**
 * Header logo image width in user units.
 */
//define ('PDF_HEADER_LOGO_WIDTH', 0);

/**
 * Cache directory for temporary files (full path).
 */
//define ('K_PATH_CACHE', '/tmp/');

/**
 * Generic name for a blank image.
 */
if (!defined('K_BLANK_IMAGE')) {
    define('K_BLANK_IMAGE', '_blank.png');
}

/**
 * Page format.
 */
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

/**
 * Page orientation (P=portrait, L=landscape).
 */
if (!defined('PDF_PAGE_ORIENTATION')) {
    define ('PDF_PAGE_ORIENTATION', 'P');
}

/**
 * Document creator.
 */
if (!defined('PDF_CREATOR')) {
    define ('PDF_CREATOR', 'TCPDF');
}

/**
 * Document author.
 */
if (!defined('PDF_AUTHOR')) {
    define('PDF_AUTHOR', 'TCPDF');
}
/**
 * Header title.
 */
if (!defined('PDF_HEADER_TITLE')) {
    define('PDF_HEADER_TITLE', 'TCPDF Example');
}

/**
 * Header description string.
 */
if (!defined('PDF_HEADER_STRING')) {
    define('PDF_HEADER_STRING', "by Nicola Asuni - Tecnick.com\nwww.tcpdf.org");
}

/**
 * Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch].
 */
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}

/**
 * Header margin.
 */
if (!defined('PDF_MARGIN_HEADER')) {
    define('PDF_MARGIN_HEADER', 5);
}

/**
 * Footer margin.
 */
if (!defined('PDF_MARGIN_FOOTER')) {
    define('PDF_MARGIN_FOOTER', 10);
}

/**
 * Top margin.
 */
if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 27);
}

/**
 * Bottom margin.
 */
if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 25);
}

/**
 * Left margin.
 */
if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}

/**
 * Right margin.
 */
if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}

/**
 * Default main font name.
 */
if (!defined('PDF_FONT_NAME_MAIN')) {
    define('PDF_FONT_NAME_MAIN', 'helvetica');
}

/**
 * Default main font size.
 */
if (!defined('PDF_FONT_SIZE_MAIN')) {
    define('PDF_FONT_SIZE_MAIN', 10);
}

/**
 * Default data font name.
 */
if (!defined('PDF_FONT_NAME_DATA')) {
    define('PDF_FONT_NAME_DATA', 'helvetica');
}

/**
 * Default data font size.
 */
if (!defined('PDF_FONT_SIZE_DATA')) {
    define('PDF_FONT_SIZE_DATA', 8);
}

/**
 * Default monospaced font name.
 */
if (!defined('PDF_FONT_MONOSPACED')) {
    define('PDF_FONT_MONOSPACED', 'courier');
}

/**
 * Ratio used to adjust the conversion of pixels to user units.
 */
if (!defined('PDF_IMAGE_SCALE_RATIO')) {
    define('PDF_IMAGE_SCALE_RATIO', 1.25);
}

/**
 * Magnification factor for titles.
 */
if (!defined('HEAD_MAGNIFICATION')) {
    define('HEAD_MAGNIFICATION', 1.1);
}

/**
 * Height of cell respect font height.
 */
if (!defined('K_CELL_HEIGHT_RATIO')) {
    define('K_CELL_HEIGHT_RATIO', 1.25);
}

/**
 * Title magnification respect main font size.
 */
if (!defined('K_TITLE_MAGNIFICATION')) {
    define('K_TITLE_MAGNIFICATION', 1.3);
}

/**
 * Reduction factor for small font.
 */
if (!defined('K_SMALL_RATIO')) {
    define('K_SMALL_RATIO', 2 / 3);
}

/**
 * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language.
 */
if (!defined('K_THAI_TOPCHARS')) {
    define('K_THAI_TOPCHARS', true);
}

/**
 * If true allows to call TCPDF methods using HTML syntax
 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
 */
if (!defined('K_TCPDF_CALLS_IN_HTML')) {
    define('K_TCPDF_CALLS_IN_HTML', false);
}

/**
 * If true and PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
 */
if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR')) {
    define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
}

/**
 * Default timezone for datetime functions
 */
if (!defined('K_TIMEZONE')) {
    define('K_TIMEZONE', 'UTC');
}

//============================================================+
// END OF FILE
//============================================================+
