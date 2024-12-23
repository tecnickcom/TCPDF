#!/usr/bin/env php
<?php

/**
 * Test runner
 *
 * Usage: php launch.php --help
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

use LocateBinaries\LocateBinaries;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo 'Run `composer install` in the tests/ directory first.' . PHP_EOL;
    exit(-1);
}

require_once __DIR__ . '/vendor/autoload.php';

$options = getopt('o:vh', array(
    'group:',
    'output-dir:',
    'stop-on-defect',
    'verbose',
    'help'
));

function printLaunchHelp()
{
    echo 'Usage:' . PHP_EOL;
    echo '  php launch.php [-chv] [-o <path>] [file...]' . PHP_EOL;
    echo 'Description:' . PHP_EOL;
    echo '  Launches the test suite for Tecnickcom\'s TCPDF.' . PHP_EOL;
    echo 'Supported environment variables:' . PHP_EOL;
    echo '  PHP_BINARY      Path to php executable to use.' . PHP_EOL;
    echo '  PDFINFO_BINARY  Path to pdfinfo executable to use.' . PHP_EOL;
    echo '                  For more information on pdfinfo, visit https://www.xpdfreader.com/' . PHP_EOL;
    echo 'Arguments:' . PHP_EOL;
    echo '  file' . PHP_EOL;
    echo '    Test file(s) to run. If not provided all the tests are considered for the run.' . PHP_EOL;
    echo '    Usage example:' . PHP_EOL;
    echo '    php launch.php example_001.php barcodes/example_1d_html.php' . PHP_EOL;
    echo 'Options:' . PHP_EOL;
    echo '  -c, --clean-up' . PHP_EOL;
    echo '    Clean up generated files.' . PHP_EOL;
    echo '    The default is to NOT clean up if the -o option is provided,' . PHP_EOL;
    echo '    and to clean up if the -o option is NOT provided.' . PHP_EOL;
    echo '  -o <path>, --output-dir=<path>' . PHP_EOL;
    echo '    The folder in which files should be generated.' . PHP_EOL;
    echo '    Default is to create a folder in the system\'s temporary folder.' . PHP_EOL;
    echo '  --group=<name>' . PHP_EOL;
    echo '    Filter the tests to run based on the @group annotation present in the file.' . PHP_EOL;
    echo '  --stop-on-defect' . PHP_EOL;
    echo '    Stop execution upon first not-passed test.' . PHP_EOL;
    echo '  -v, --verbose' . PHP_EOL;
    echo '    Outputs more information.' . PHP_EOL;
    echo '  -h, --help' . PHP_EOL;
    echo '    Prints this message.' . PHP_EOL;
}

if (false === $options
    || array_key_exists('h', $options)
    || array_key_exists('help', $options)) {
    printLaunchHelp();
    exit(false === $options ? -1 : 0);
}

if (!empty($options['o'])) {
    $outputDir = $options['o'];
}
if (!empty($options['output-dir'])) {
    $outputDir = $options['output-dir'];
}

if (array_key_exists('c', $options) || array_key_exists('clean-up', $options)) {
    $preserveOutputFiles = false;
} elseif (isset($outputDir)) {
    $preserveOutputFiles = true;
} else {
    $preserveOutputFiles = false;
}

$stopOn = array();
if (array_key_exists('stop-on-defect', $options)) {
    $stopOn[] = 'defect';
}

$verbose = array_key_exists('v', $options) || array_key_exists('verbose', $options);

$groups = array();
if (!empty($options['group'])) {
    if (is_array($options['group'])) {
        $groups = $options['group'];
    } else {
        $groups = explode(',', $options['group']);
    }
}

$isBinaryLocatorAvailable = class_exists('\LocateBinaries\LocateBinaries');

$pdfinfo = getenv('PDFINFO_BINARY');
if (empty($pdfinfo)) {
    $paths = ($isBinaryLocatorAvailable)
        ? LocateBinaries::locateInstalledBinaries('pdfinfo')
        : array();
    if (empty($paths)) {
        echo 'pdfinfo could not be located.' . PHP_EOL;
        echo 'Please set the PDFINFO_BINARY environment variable.' . PHP_EOL;
        if (!$isBinaryLocatorAvailable) {
            echo 'You could install rosell-dk/locate-binaries via composer to detect binaries.' . PHP_EOL;
        }
        exit(-1);
    }
    $pdfinfo = reset($paths);
}
$pdfTools = new PdfTools(array('pdfinfo' => $pdfinfo), $verbose);
echo 'pdfinfo: ' . $pdfinfo . PHP_EOL;
echo 'pdfinfo version: ' . $pdfTools->getPdfinfoVersionInfo() . PHP_EOL;
echo PHP_EOL;

// Allows you to use PHP_BINARY=/usr/bin/php5.3 php ./tests/launch.php
$phpBinary = getenv('PHP_BINARY');
if (empty($phpBinary)) {
    // PHP_BINARY only exists since PHP 5.4
    if (defined('PHP_BINARY')) {
        $phpBinary = PHP_BINARY;
    } else {
        $paths = ($isBinaryLocatorAvailable)
            ? LocateBinaries::locateInstalledBinaries('php')
            : array();
        if (empty($paths)) {
            echo 'php could not be located. Please set PHP_BINARY environment variable.' . PHP_EOL;
            if (!$isBinaryLocatorAvailable) {
                echo 'You could install rosell-dk/locate-binaries via composer to detect binaries.' . PHP_EOL;
            }
            exit(-1);
        }
        $phpBinary = reset($paths);
    }
}

$isWindows = (stripos(PHP_OS, 'WIN') === 0);

$phpExecutor = new PhpExecutor($phpBinary, $verbose);

echo 'PHP: ' . ((string)$phpExecutor) . PHP_EOL;
echo 'PHP version: ' . $phpExecutor->getPhpVersionInfo() . PHP_EOL;
echo PHP_EOL;

/**
 * Map of extension availability.
 * Possible values:
 * - true: built in PHP,
 * - false: available,
 * - null: not available (not detected).
 */
$phpExtensions = array(
    'bcmath' => null,
    'curl' => null,
    'gd' => null,
    'imagick' => null,
    'json' => null,
    'openssl' => null,
    'xml' => null,
);
$phpExtensionDir = $phpExecutor->getPhpExtensionDir();
echo 'PHP extension folder: ' . $phpExtensionDir . PHP_EOL;
if (strpos($phpExtensionDir, ' ') !== false) {
    echo "WARNING: Spaces in extension_dir might cause problems." . PHP_EOL;
    if ($isWindows) {
        echo "         You should use `dir /x` to get the short name of the path," . PHP_EOL;
        echo "         then adjust the extension_dir option of your php.ini file." . PHP_EOL;
    }
    if (!in_array('defect', $stopOn, true)) {
        $stopOn[] = 'defect';
        echo "         --stop-on-defect as been forced to avoid too many failing tests." . PHP_EOL;
    }
}
echo 'Extensions:' . PHP_EOL;
foreach ($phpExtensions as $extension => $_) {
    $status = $phpExecutor->getExtensionStatus($extension);
    $phpExtensions[$extension] = $status;
    echo "    $extension: ";
    if (true === $status) {
        echo 'BUILT-IN';
    } elseif (false === $status) {
        echo 'AVAILABLE';
    } else {
        echo 'NO';
    }
    echo PHP_EOL;
}

if (null === $phpExtensions['gd'] && null === $phpExtensions['imagick']) {
    echo 'gd or imagick extension required.' . PHP_EOL;
    echo 'Exit code: 1' . PHP_EOL;
    exit(1);
}

if (null === $phpExtensions['openssl']) {
    echo 'openssl extension required.' . PHP_EOL;
    echo 'Exit code: 1' . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

$rootDir = dirname(realpath(__DIR__)) . DIRECTORY_SEPARATOR;
echo "Root folder: $rootDir" . PHP_EOL;

$isGeneratedTempDir = false;
if (!isset($outputDir)) {
    echo PHP_EOL;
    echo "The --output-dir option was not used, a temporary folder will be necessary." . PHP_EOL;
    try {
        $outputDir = \Cs278\Mktemp\temporaryDir('TCPDF-tests.XXXXXXXXX') . DIRECTORY_SEPARATOR;
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit(-1);
    }
    $isGeneratedTempDir = true;
}

if (!is_dir(realpath($outputDir))) {
    echo "Could not find output folder: $outputDir" . PHP_EOL;
    exit(-1);
}
$outputDir = realpath($outputDir);
echo "Output folder: $outputDir" . PHP_EOL;
echo PHP_EOL;

$testsDir = $rootDir . 'tests' . DIRECTORY_SEPARATOR;
echo "Test folder: $testsDir" . PHP_EOL;

$testExecutor = new TestExecutor(
    $phpExecutor,
    array_keys($phpExtensions),
    $pdfTools,
    $outputDir,
    $testsDir,
    $verbose
);

// Files that should be excluded from the test suite
$ignored = array(
    'example_006.php',
);

// Check if the script is run for specific test files
$requestedTests = array();
foreach (array_reverse($argv) as $value) {
    // This is a crude way to work around how getopt() parses arguments to script
    if (preg_match('~^(barcodes/)?example_\d[\d_a-z]+\.php$~', $value)) {
        $requestedTests[] = $value;
    }
}

$testRunner = new TestRunner($rootDir . 'examples');
$passed = $testRunner
    ->withTestExecutor($testExecutor)
    ->preserveOutputFiles($preserveOutputFiles)
    ->excludeTests($ignored)
    ->only($requestedTests)
    ->filterByGroup($groups)
    ->stopOn($stopOn)
    ->runTests($outputDir)
;

if (!$preserveOutputFiles && $isGeneratedTempDir) {
    rmdir($outputDir);
}

// Final result
$testRunner->printSummary();
$exitCode = (!$passed) ? 1 : 0;
echo 'Exit code: ' . $exitCode . PHP_EOL;
exit($exitCode);
