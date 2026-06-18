<?php

/**
 * @file
 * Try to automatically configure some TCPDF constants if not defined.
 * @package com.tecnick.tcpdf
 */

// Disable phar stream wrapper globally.
// if (in_array('phar', stream_get_wrappers(), true)) {
//     stream_wrapper_unregister('phar');
// }

// DOCUMENT_ROOT fix for IIS Webserver
if (!isset($_SERVER['DOCUMENT_ROOT']) or empty($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace(
            '\\',
            '/',
            substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])),
        );
    } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace(
            '\\',
            '/',
            substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])),
        );
    } else {
        // define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
        $_SERVER['DOCUMENT_ROOT'] = '/';
    }
}
$_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']);
if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/') {
    $_SERVER['DOCUMENT_ROOT'] .= '/';
}

// Load main configuration file only if the K_TCPDF_EXTERNAL_CONFIG constant is set to false.
if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    // define a list of default config files in order of priority
    $tcpdf_config_files = [
        dirname(__FILE__) . '/config/tcpdf_config.php',
        '/etc/php-tcpdf/tcpdf_config.php',
        '/etc/tcpdf/tcpdf_config.php',
        '/etc/tcpdf_config.php',
    ];
    foreach ($tcpdf_config_files as $tcpdf_config) {
        if (!(@file_exists($tcpdf_config) and is_readable($tcpdf_config))) {
            continue;
        }

        require_once $tcpdf_config;
        break;
    }
}

if (!defined('K_PATH_MAIN')) {
    define('K_PATH_MAIN', dirname(__FILE__) . '/');
}

// Load Composer autoloader if available (for tc-lib dependencies).
if (@file_exists(K_PATH_MAIN . 'vendor/autoload.php')) {
    require_once K_PATH_MAIN . 'vendor/autoload.php';
}

if (!defined('K_TCPDF_LIB_PDF_PATH')) {
    $tcpdf_lib_pdf_path = K_PATH_MAIN . 'vendor/tecnickcom/tc-lib-pdf/src';
    if (@is_dir($tcpdf_lib_pdf_path)) {
        define('K_TCPDF_LIB_PDF_PATH', $tcpdf_lib_pdf_path);
    }
}

if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', K_PATH_MAIN . 'vendor/tecnickcom/tc-lib-pdf-font/target/fonts/');
}

if (!defined('K_PATH_URL')) {
    $k_path_url = K_PATH_MAIN; // default value for console mode
    if (isset($_SERVER['HTTP_HOST']) and !empty($_SERVER['HTTP_HOST'])) {
        if (isset($_SERVER['HTTPS']) and !empty($_SERVER['HTTPS']) and strtolower($_SERVER['HTTPS']) != 'off') {
            $k_path_url = 'https://';
        } else {
            $k_path_url = 'http://';
        }
        $k_path_url .= $_SERVER['HTTP_HOST'];
        $k_path_url .= str_replace('\\', '/', substr(K_PATH_MAIN, strlen($_SERVER['DOCUMENT_ROOT']) - 1));
    }
    define('K_PATH_URL', $k_path_url);
}

if (!defined('K_PATH_IMAGES')) {
    $tcpdf_images_dirs = [
        K_PATH_MAIN . 'examples/images/',
        K_PATH_MAIN . 'images/',
        '/usr/share/doc/php-tcpdf/examples/images/',
        '/usr/share/doc/tcpdf/examples/images/',
        '/usr/share/doc/php/tcpdf/examples/images/',
        '/var/www/tcpdf/images/',
        '/var/www/html/tcpdf/images/',
        '/usr/local/apache2/htdocs/tcpdf/images/',
        K_PATH_MAIN,
    ];
    foreach ($tcpdf_images_dirs as $tcpdf_images_path) {
        if (!@file_exists($tcpdf_images_path)) {
            continue;
        }

        define('K_PATH_IMAGES', $tcpdf_images_path);
        break;
    }
}

if (!defined('PDF_HEADER_LOGO')) {
    $tcpdf_header_logo = '';
    if (@file_exists(K_PATH_IMAGES . 'tcpdf_logo.jpg')) {
        $tcpdf_header_logo = 'tcpdf_logo.jpg';
    }
    define('PDF_HEADER_LOGO', $tcpdf_header_logo);
}

if (!defined('PDF_HEADER_LOGO_WIDTH')) {
    if (!empty($tcpdf_header_logo)) {
        define('PDF_HEADER_LOGO_WIDTH', 30);
    } else {
        define('PDF_HEADER_LOGO_WIDTH', 0);
    }
}

if (!defined('K_PATH_CACHE')) {
    $K_PATH_CACHE = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
    if (substr($K_PATH_CACHE, -1) != '/') {
        $K_PATH_CACHE .= '/';
    }
    define('K_PATH_CACHE', $K_PATH_CACHE);
}

if (!defined('K_BLANK_IMAGE')) {
    define('K_BLANK_IMAGE', '_blank.png');
}

if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}

if (!defined('PDF_CREATOR')) {
    define('PDF_CREATOR', 'TCPDF');
}

if (!defined('PDF_AUTHOR')) {
    define('PDF_AUTHOR', 'TCPDF');
}

if (!defined('PDF_HEADER_TITLE')) {
    define('PDF_HEADER_TITLE', 'TCPDF Example');
}

if (!defined('PDF_HEADER_STRING')) {
    define('PDF_HEADER_STRING', "Nicola Asuni - Tecnick.com\nhttps://tcpdf.org");
}

if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}

if (!defined('PDF_MARGIN_HEADER')) {
    define('PDF_MARGIN_HEADER', 5);
}

if (!defined('PDF_MARGIN_FOOTER')) {
    define('PDF_MARGIN_FOOTER', 10);
}

if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 27);
}

if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 25);
}

if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}

if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}

if (!defined('PDF_FONT_NAME_MAIN')) {
    define('PDF_FONT_NAME_MAIN', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_MAIN')) {
    define('PDF_FONT_SIZE_MAIN', 10);
}

if (!defined('PDF_FONT_NAME_DATA')) {
    define('PDF_FONT_NAME_DATA', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_DATA')) {
    define('PDF_FONT_SIZE_DATA', 8);
}

if (!defined('PDF_FONT_MONOSPACED')) {
    define('PDF_FONT_MONOSPACED', 'courier');
}

if (!defined('PDF_IMAGE_SCALE_RATIO')) {
    define('PDF_IMAGE_SCALE_RATIO', 96 / 72);
}

if (!defined('HEAD_MAGNIFICATION')) {
    define('HEAD_MAGNIFICATION', 1.1);
}

if (!defined('K_CELL_HEIGHT_RATIO')) {
    define('K_CELL_HEIGHT_RATIO', 1.25);
}

if (!defined('K_TITLE_MAGNIFICATION')) {
    define('K_TITLE_MAGNIFICATION', 1.3);
}

if (!defined('K_SMALL_RATIO')) {
    define('K_SMALL_RATIO', 2 / 3);
}

if (!defined('K_THAI_TOPCHARS')) {
    define('K_THAI_TOPCHARS', true);
}

if (!defined('K_TCPDF_CALLS_IN_HTML')) {
    define('K_TCPDF_CALLS_IN_HTML', false);
}

if (!defined('K_ALLOWED_TCPDF_TAGS')) {
    define('K_ALLOWED_TCPDF_TAGS', '');
}

if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR')) {
    define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
}

if (!defined('K_TIMEZONE')) {
    define('K_TIMEZONE', @date_default_timezone_get());
}

// ----------------------------------------------------------------------------
// File-access security model (tc-lib-pdf / tc-lib-file).
//
// External resources (images, fonts, SVG, imported PDFs, ...) are loaded
// through a sandboxed file helper. Local reads are restricted to an allowlist
// of trusted directories and remote (HTTP/HTTPS) reads are DISABLED unless an
// explicit allowlist of host names is provided. The constants below feed that
// sandbox; safe defaults keep remote loading off and only the bundled/runtime
// asset directories readable.
// ----------------------------------------------------------------------------

// Additional trusted local directories the library may read files from.
// Array of absolute path prefixes, merged on top of the built-in defaults
// (system temp dir, K_PATH_MAIN, the bundled vendor dir, the working
// directory, K_PATH_FONTS, K_PATH_IMAGES and the running script directory).
// The built-in defaults are always included; this only ever widens access.
if (!defined('K_ALLOWED_PATHS')) {
    define('K_ALLOWED_PATHS', []);
}

// Trusted remote host names that enable HTTP/HTTPS resource loading.
// Array of exact host names (e.g. ['cdn.example.com', 'example.org']).
// Leave EMPTY to keep remote URL loading disabled (recommended default and
// the only protection against SSRF when rendering untrusted content).
if (!defined('K_ALLOWED_HOSTS')) {
    define('K_ALLOWED_HOSTS', []);
}

// Maximum size, in bytes, accepted for a single remote download.
// Reads exceeding this limit are rejected. Default: 52428800 (50 MiB).
if (!defined('K_MAX_REMOTE_SIZE')) {
    define('K_MAX_REMOTE_SIZE', 52428800);
}

// Custom cURL options (array of CURLOPT_* => value) merged over the library
// defaults for remote downloads. Security-critical options (TLS verification,
// redirect handling) are enforced upstream and cannot be overridden here.
if (!defined('K_CURLOPTS')) {
    define('K_CURLOPTS', []);
}
