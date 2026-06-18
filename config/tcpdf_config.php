<?php

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
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
define('K_BLANK_IMAGE', '_blank.png');

/**
 * Page format.
 */
define('PDF_PAGE_FORMAT', 'A4');

/**
 * Page orientation (P=portrait, L=landscape).
 */
define('PDF_PAGE_ORIENTATION', 'P');

/**
 * Document creator.
 */
define('PDF_CREATOR', 'TCPDF');

/**
 * Document author.
 */
define('PDF_AUTHOR', 'TCPDF');

/**
 * Header title.
 */
define('PDF_HEADER_TITLE', 'TCPDF Example');

/**
 * Header description string.
 */
define('PDF_HEADER_STRING', "Nicola Asuni - Tecnick.com\nhttps://tcpdf.org");

/**
 * Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch].
 */
define('PDF_UNIT', 'mm');

/**
 * Header margin.
 */
define('PDF_MARGIN_HEADER', 5);

/**
 * Footer margin.
 */
define('PDF_MARGIN_FOOTER', 10);

/**
 * Top margin.
 */
define('PDF_MARGIN_TOP', 27);

/**
 * Bottom margin.
 */
define('PDF_MARGIN_BOTTOM', 25);

/**
 * Left margin.
 */
define('PDF_MARGIN_LEFT', 15);

/**
 * Right margin.
 */
define('PDF_MARGIN_RIGHT', 15);

/**
 * Default main font name.
 */
define('PDF_FONT_NAME_MAIN', 'helvetica');

/**
 * Default main font size.
 */
define('PDF_FONT_SIZE_MAIN', 10);

/**
 * Default data font name.
 */
define('PDF_FONT_NAME_DATA', 'helvetica');

/**
 * Default data font size.
 */
define('PDF_FONT_SIZE_DATA', 8);

/**
 * Default monospaced font name.
 */
define('PDF_FONT_MONOSPACED', 'courier');

/**
 * Ratio used to adjust the conversion of pixels to user units.
 */
define('PDF_IMAGE_SCALE_RATIO', 1.25);

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
define('K_SMALL_RATIO', 2 / 3);

/**
 * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language.
 */
define('K_THAI_TOPCHARS', true);

/**
 * If true allows to call TCPDF methods using HTML syntax
 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
 */
define('K_TCPDF_CALLS_IN_HTML', false);

/**
 * List of TCPDF methods that are allowed to be called using HTML syntax.
 * Note: each method name must end with surrounded with | (pipe) character.
 * The constant K_TCPDF_CALLS_IN_HTML must be set to true.
 * IMPORTANT: For security reason, disable this feature if you are allowing user HTML content.
 */
define('K_ALLOWED_TCPDF_TAGS', '');

/**
 * If true and PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
 */
define('K_TCPDF_THROW_EXCEPTION_ERROR', false);

/**
 * Default timezone for datetime functions
 */
define('K_TIMEZONE', 'UTC');

/**
 * Optional override of the default language-dependent strings.
 * English values are built into the TCPDF class; define this constant to
 * replace any of them (the given keys are merged over the defaults).
 * Example:
 * define('K_TCPDF_DEFAULT_LANGUAGE', [
 *     'a_meta_charset' => 'UTF-8',
 *     'a_meta_dir' => 'ltr',
 *     'a_meta_language' => 'en',
 *     'w_page' => 'page',
 * ]);
 */

/* ===========================================================================
 * FILE-ACCESS SECURITY MODEL
 * ===========================================================================
 *
 * External resources (images, fonts, SVG, imported PDFs, ...) are loaded
 * through the sandboxed file helper provided by tc-lib-pdf / tc-lib-file.
 * The sandbox enforces two independent allowlists:
 *
 *   1. LOCAL FILES  - only files under an allowlist of trusted directories
 *                     can be read. By default this covers the system temp
 *                     dir, K_PATH_MAIN, the bundled vendor directory, the
 *                     current working directory, K_PATH_FONTS, K_PATH_IMAGES
 *                     and the running script's directory.
 *
 *   2. REMOTE FILES - HTTP/HTTPS downloads are DISABLED unless you provide an
 *                     explicit allowlist of trusted host names. This is the
 *                     primary defense against SSRF when rendering untrusted
 *                     HTML/markup, so keep it empty unless you really need it.
 *
 * The constants below are optional: when left undefined the secure defaults
 * above apply. All values widen or tune the sandbox; none of them can disable
 * the upstream-enforced TLS verification or relax the local allowlist below
 * its built-in defaults.
 */

/**
 * Extra trusted local directories the library may read files from.
 * Array of absolute path prefixes, merged on top of the built-in defaults
 * (the defaults are always kept; this only ever widens local read access).
 */
//define('K_ALLOWED_PATHS', ['/var/www/shared/assets/']);

/**
 * Trusted remote host names that enable HTTP/HTTPS resource loading.
 * Array of exact host names. Leave empty (the default) to keep remote URL
 * loading disabled. IMPORTANT: only add hosts you fully trust, especially
 * when the document content can be influenced by end users (SSRF risk).
 */
//define('K_ALLOWED_HOSTS', ['cdn.example.com', 'example.org']);

/**
 * Maximum size, in bytes, accepted for a single remote download.
 * Reads exceeding this limit are rejected. Default: 52428800 (50 MiB).
 */
//define('K_MAX_REMOTE_SIZE', 52428800);

/**
 * Custom cURL options (CURLOPT_* => value) merged over the library defaults
 * for remote downloads. Security-critical options (TLS verification, redirect
 * handling) are enforced upstream and cannot be overridden here.
 */
//define('K_CURLOPTS', []);
