#!/usr/bin/env php
<?php
/**
 * This script compares two runs of the test suite.
 *
 * The comparison is not bit by bit, as most of the generated
 * files have some time-induced differences.
 *
 * This script will however point out gross discrepancies.
 *
 * PDF documents will be compared as PNG images.
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

require __DIR__ . '/vendor/autoload.php';

$options = getopt('o:vhr:', array(
    'group:',
    'output-dir:',
    'reference-dir:',
    'stop-on-defect',
    'verbose',
    'help'
));

function printCompareRunsHelp()
{
    echo 'Usage:' . PHP_EOL;
    echo '  php compare_runs.php [-chv] [-o <path>] -r referenceDir compareDir' . PHP_EOL;
    echo 'Description:' . PHP_EOL;
    echo '  Compares 2 runs of the TCPDF test suite.' . PHP_EOL;
    echo "  PDF files are first converted to PNG images using XpdfReader's `pdftopng` tool" . PHP_EOL;
    echo "  or using Poppler's `pdftoppm`." . PHP_EOL;
    echo "  PNG images are compared using ImageMagick's `magick` tool." . PHP_EOL;
    echo 'Supported environment variables:' . PHP_EOL;
    echo '  PDFINFO_BINARY  Path to pdfinfo executable to use.' . PHP_EOL;
    echo '                  For more information on pdfinfo, visit https://www.xpdfreader.com/' . PHP_EOL;
    echo '                  or https://poppler.freedesktop.org/' . PHP_EOL;
    echo '  PDFTOPNG_BINARY Path to pdftopng executable to use.' . PHP_EOL;
    echo '                  For more information on pdftopng, visit https://www.xpdfreader.com/' . PHP_EOL;
    echo '  PDFTOPPM_BINARY Path to pdftoppm executable to use.' . PHP_EOL;
    echo '                  For more information on pdftoppm, visit https://poppler.freedesktop.org/' . PHP_EOL;
    echo '  MAGICK_BINARY   Path to magick executable to use.' . PHP_EOL;
    echo '                  For more information on magick, visit https://imagemagick.org/' . PHP_EOL;
    echo 'Arguments:' . PHP_EOL;
    echo '  compareDir' . PHP_EOL;
    echo '    Path to the folder containing the results of test run to compare' . PHP_EOL;
    echo '    with the reference.' . PHP_EOL;
    echo 'Options:' . PHP_EOL;
    echo '  -c, --clean-up' . PHP_EOL;
    echo '    Clean up generated files. NOT IMPLEMENTED YET.' . PHP_EOL;
    echo '  -o <path>, --output-dir=<path>' . PHP_EOL;
    echo '    The folder in which files should be generated.' . PHP_EOL;
    echo '    Default is to create a folder in the system\'s temporary folder.' . PHP_EOL;
    echo '  -r <path>, --reference-dir=<path>' . PHP_EOL;
    echo '    Path to the folder containing the results of the test run of reference.' . PHP_EOL;
    echo '    If no reference folder is provided, a set will be automatically downloaded.' . PHP_EOL;
    echo '  --group=<name>' . PHP_EOL;
    echo '    Filter the tests to compare based on the @group annotation present in the file.' . PHP_EOL;
    echo '  --stop-on-defect' . PHP_EOL;
    echo '    Stop execution upon first difference.' . PHP_EOL;
    echo '  -v, --verbose' . PHP_EOL;
    echo '    Outputs more information.' . PHP_EOL;
    echo '  -h, --help' . PHP_EOL;
    echo '    Prints this message.' . PHP_EOL;
}

if (false === $options
    || array_key_exists('h', $options)
    || array_key_exists('help', $options)) {
    printCompareRunsHelp();
    exit(false === $options ? -1 : 0);
}

if (!empty($options['o'])) {
    $outputDir = $options['o'];
}
if (!empty($options['output-dir'])) {
    $outputDir = $options['output-dir'];
}

if (!empty($options['r'])) {
    $referenceDirArg = $options['r'];
}
if (!empty($options['reference-dir'])) {
    $referenceDirArg = $options['reference-dir'];
}
if (!isset($referenceDirArg)) {
    echo "Please provide the reference folder using the -r or --reference-dir argument." . PHP_EOL;
    exit(-1);
}

// TODO Automate the cleanup of generated files (PNG images from PDF conversion)
if (array_key_exists('c', $options) || array_key_exists('clean-up', $options)) {
    $preserveOutputFiles = false;
} elseif (isset($outputDir)) {
    $preserveOutputFiles = true;
} else {
    $preserveOutputFiles = false;
}

$stopOnDefect = array_key_exists('stop-on-defect', $options);

$verbose = array_key_exists('v', $options) || array_key_exists('verbose', $options);

$groups = array();
if (!empty($options['group'])) {
    if (is_array($options['group'])) {
        $groups = $options['group'];
    } else {
        $groups = explode(',', $options['group']);
    }
}

// Yes, this is a very basic check.
if ($argc < 2) {
    echo "Please provide folder path" . PHP_EOL;
    exit(-1);
}
$compareDirArg = $argv[$argc - 1];

if (!is_dir(realpath($compareDirArg))) {
    echo "Could not find folder to compare: $compareDirArg" . PHP_EOL;
    exit(-1);
}
// Normalize the folder path to end with a slash
$compareDir = rtrim(realpath($compareDirArg), '/\\') . DIRECTORY_SEPARATOR;

if (!is_dir(realpath($referenceDirArg))) {
    echo "Could not find reference folder: $referenceDirArg" . PHP_EOL;
    exit(-1);
}
// Normalize the folder path to end with a slash
$referenceDir = trim(realpath($referenceDirArg), '/\\') . DIRECTORY_SEPARATOR;

$rootDir = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
$exampleDir = $rootDir . 'examples' . DIRECTORY_SEPARATOR;

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
$pdftopng = getenv('PDFTOPNG_BINARY');
if (empty($pdftopng)) {
    $paths = ($isBinaryLocatorAvailable)
        ? LocateBinaries::locateInstalledBinaries('pdftopng')
        : array();
    if (!empty($paths)) {
        $pdftopng = reset($paths);
    }
}
$pdftoppm = getenv('PDFTOPPM_BINARY');
if (empty($pdftoppm)) {
    $paths = ($isBinaryLocatorAvailable)
        ? LocateBinaries::locateInstalledBinaries('pdftoppm')
        : array();
    if (!empty($paths)) {
        $pdftoppm = reset($paths);
    }
}
if (empty($pdftopng) && empty($pdftoppm)) {
    echo 'pdftopng nor pdftoppm could not be located.' . PHP_EOL;
    echo 'Please set the PDFTOPNG_BINARY or PDFTOPPM_BINARY environment variable.' . PHP_EOL;
    if (!$isBinaryLocatorAvailable) {
        echo 'You could install rosell-dk/locate-binaries via composer to detect binaries.' . PHP_EOL;
    }
    exit(-1);
}

$tools = array(
    'pdfinfo' => $pdfinfo,
    'pdftopng' => $pdftopng,
    'pdftoppm' => $pdftoppm,
);
$pdfTools = new PdfTools($tools, $verbose);
echo 'pdfinfo: ' . $pdfinfo . PHP_EOL;
echo 'pdfinfo version: ' . $pdfTools->getPdfinfoVersionInfo() . PHP_EOL;
echo PHP_EOL;
echo 'pdftopng: ' . $pdftopng . PHP_EOL;
echo 'pdftopng version: ' . $pdfTools->getPdftopngVersionInfo() . PHP_EOL;
echo PHP_EOL;

$magick = getenv('MAGICK_BINARY');
if (empty($magick)) {
    $paths = ($isBinaryLocatorAvailable)
        ? LocateBinaries::locateInstalledBinaries('magick')
        : array();
    if (empty($paths)) {
        echo 'magick could not be located.' . PHP_EOL;
        echo 'Please set the MAGICK_BINARY environment variable.' . PHP_EOL;
        if (!$isBinaryLocatorAvailable) {
            echo 'You could install rosell-dk/locate-binaries via composer to detect binaries.' . PHP_EOL;
        }
        exit(-1);
    }
    $magick = reset($paths);
}
$imagemagick = new ImageMagick($magick, $verbose);
echo 'magick: ' . $magick . PHP_EOL;
echo 'magick version: ' . $imagemagick->getMagickVersionInfo() . PHP_EOL;
echo PHP_EOL;

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

echo "Example folder: $exampleDir" . PHP_EOL;

$outputDir = realpath($outputDir);
$outputDir = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR;
echo "Output folder: $outputDir" . PHP_EOL;
echo PHP_EOL;

echo 'Comparing folders:' . PHP_EOL;
echo "< $referenceDir" . PHP_EOL;
echo "> $compareDir" . PHP_EOL;

$separator = '----------------------------------';

$testRunner = new TestRunner($exampleDir);

$differences = array();
$ignored = array();
$comparisons = 0;
$bitByBitComparisons = 0;

$testFiles = $testRunner
    ->filterByGroup($groups)
    ->getTestFiles()
;
foreach ($testFiles as $file => $type) {
    ++$comparisons;
    $outputFile = basename($file, '.php') . '.output.' . strtolower($type);

    $outputFile1 = $referenceDir . $outputFile;
    $outputFile2 = $compareDir . $outputFile;

    $exists1 = file_exists($outputFile1);
    $exists2 = file_exists($outputFile2);

    if (!$exists1 && !$exists2) {
        $ignored[] = $file;
        continue;
    }
    if ($exists1 && !$exists2) {
        $differences[] = $file . ' (FILE PRESENCE)';
        echo $separator . PHP_EOL;
        echo $outputFile . PHP_EOL;
        echo '< PRESENT' . PHP_EOL;
        echo '> MISSING' . PHP_EOL;
        if ($stopOnDefect) {
            break;
        }
        continue;
    }
    if (!$exists1 && $exists2) {
        $differences[] = $file . ' (FILE PRESENCE)';
        echo $separator . PHP_EOL;
        echo $outputFile . PHP_EOL;
        echo '< ABSENT' . PHP_EOL;
        echo '> PRESENT' . PHP_EOL;
        if ($stopOnDefect) {
            break;
        }
        continue;
    }

    // Exact size comparison is not workable for most of the files
    $size1 = filesize($outputFile1);
    $size2 = filesize($outputFile2);
    if ($size1 === 0 && $size2 > 0) {
        $differences[] = $file . ' (FILE SIZE)';
        echo $separator . PHP_EOL;
        echo $outputFile . PHP_EOL;
        echo '< EMPTY' . PHP_EOL;
        echo '> HAS CONTENT' . PHP_EOL;
        if ($stopOnDefect) {
            break;
        }
        continue;
    }
    if ($size1 > 0 && $size2 === 0) {
        $differences[] = $file . ' (FILE SIZE)';
        echo $separator . PHP_EOL;
        echo $outputFile . PHP_EOL;
        echo '< HAS CONTENT' . PHP_EOL;
        echo '> EMPTY' . PHP_EOL;
        if ($stopOnDefect) {
            break;
        }
        continue;
    }
    if ($size1 === 0 && $size2 === 0) {
        echo $separator . PHP_EOL;
        echo $outputFile . PHP_EOL;
        echo '< EMPTY' . PHP_EOL;
        echo '> EMPTY' . PHP_EOL;
        continue;
    }

    // Can we compare files?
    // For now, just some example files are easily comparable.
    // (See the @group comparable docBlock annotation)
    $examplePHPSource = file_get_contents($exampleDir . $file);
    if (strpos($examplePHPSource, '* @group comparable') !== false) {
        ++$bitByBitComparisons;

        $pngFiles1 = null;
        $pngFiles2 = null;

        if ('PDF' !== $type) {
            $hash1 = sha1_file($outputFile1);
            $hash2 = sha1_file($outputFile2);
            if ($hash1 !== $hash2) {
                $differences[] = $file . ' (CONTENT)';
                echo $separator . PHP_EOL;
                echo $outputFile . PHP_EOL;
                echo '< ORIGIN' . PHP_EOL;
                echo '> DIFFERENT' . PHP_EOL;
                if ($stopOnDefect) {
                    break;
                }
                continue;
            }
        }

        if ('PNG' === $type) {
            $pngFiles1 = array($outputFile1);
            $pngFiles2 = array($outputFile2);
        }

        // For PDF files, we generate PNG files (one per page) with pdftopng
        if ('PDF' === $type) {
            // Let's first check there are both valid PDF files
            $isPdf1 = $pdfTools->isPdf($outputFile1);
            $isPdf2 = $pdfTools->isPdf($outputFile2);
            if ($isPdf1 && !$isPdf2) {
                $differences[] = $file . ' (CONTENT)';
                echo $separator . PHP_EOL;
                echo $outputFile . PHP_EOL;
                echo '< PDF' . PHP_EOL;
                echo '> NOT PDF' . PHP_EOL;
                if ($stopOnDefect) {
                    break;
                }
                continue;
            }
            if (!$isPdf1 && $isPdf2) {
                $differences[] = $file . ' (CONTENT)';
                echo $separator . PHP_EOL;
                echo $outputFile . PHP_EOL;
                echo '< NOT PDF' . PHP_EOL;
                echo '> PDF' . PHP_EOL;
                if ($stopOnDefect) {
                    break;
                }
                continue;
            }
            if (!$isPdf1 && !$isPdf2) {
                echo $separator . PHP_EOL;
                echo $outputFile . PHP_EOL;
                echo '< NOT PDF' . PHP_EOL;
                echo '> NOT PDF' . PHP_EOL;
                continue;
            }

            // Now we convert each page of the PDF into PNG
            $conversionDir = $outputDir . basename($file, '.php') . DIRECTORY_SEPARATOR;
            $pngFiles1 = $pdfTools->convertToPng($outputFile1, $conversionDir . 'ref');
            $pngFiles2 = $pdfTools->convertToPng($outputFile2, $conversionDir . 'cmp');

            if (count($pngFiles1) !== count($pngFiles2)) {
                $differences[] = $file . ' (CONTENT)';
                echo $separator . PHP_EOL;
                echo $outputFile . PHP_EOL;
                echo '< ' . count($pngFiles1) . ' page(s)' . PHP_EOL;
                echo '> ' . count($pngFiles2) . ' page(s)' . PHP_EOL;
                if ($stopOnDefect) {
                    break;
                }
                continue;
            }
        }

        if (isset($pngFiles1, $pngFiles2)) {
            $pngCount = count($pngFiles1);
            for ($i = 0; $i < $pngCount; ++$i) {
                if (!$imagemagick->areSimilar($pngFiles1[$i], $pngFiles2[$i])) {
                    $differences[] = $file . ' (CONTENT)';
                    echo $separator . PHP_EOL;
                    echo $outputFile . PHP_EOL;
                    echo '< ORIGIN' . PHP_EOL;
                    echo '> DIFFERENT' . PHP_EOL;
                    if ($stopOnDefect) {
                        break 2;
                    }
                    continue 2;
                }
            }
        }
    }
}

$passed = empty($differences);
echo PHP_EOL;
if ($passed) {
    echo 'Comparison: IDENTICAL' . PHP_EOL;
} else {
    echo 'Comparison: DIFFERENCES EXIST' . PHP_EOL;
}
echo "    Total tests: $comparisons" . PHP_EOL;

$ignoredCount = count($ignored);
if ($ignoredCount > 0) {
    echo '    Probably skipped tests: ' . $ignoredCount . PHP_EOL;
    foreach ($ignored as $ignoredFile) {
        echo '        ' . $ignoredFile . PHP_EOL;
    }
}

echo "    Exact content comparisons: " . $bitByBitComparisons . PHP_EOL;

if ($differences) {
    echo '    Differences: ' . count($differences) . PHP_EOL;
    echo '    Files:' . PHP_EOL;
    foreach ($differences as $file) {
        echo "        $file" . PHP_EOL;
    }
}

$exitCode = (!$passed) ? 1 : 0;
echo 'Exit code: ' . $exitCode . PHP_EOL;
exit($exitCode);
