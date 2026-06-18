<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for the TCPDF compatibility facade test suite.
 *
 * Loads the facade (which loads tcpdf_autoconfig.php and the Composer
 * autoloader for the tc-lib-* dependencies).
 *
 * @package com.tecnick.tcpdf
 */

require_once dirname(__DIR__) . '/tcpdf.php';
