<?php

/**
 * Helper class to execute TCPDF test
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

class TestExecutor
{
    /**
     * @var PhpExecutor
     */
    private $phpExecutor;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $testsDir;

    /**
     * @var string
     */
    private $cliOptions = null;

    /**
     * @var string
     */
    private $cliExtensionOptions = null;

    /**
     * @var string[]
     */
    private $extensionsToLoad;

    /**
     * @var PdfTools
     */
    private $pdfTools;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @param PhpExecutor $phpExecutor
     * @param array $extensionsToLoad
     * @param PdfTools $pdfTools
     * @param string $tempDir Path to tho temporary folder
     * @param string $testsDir Path to this folder
     * @param bool $verbose
     */
    public function __construct(
        PhpExecutor $phpExecutor,
        array $extensionsToLoad,
        PdfTools $pdfTools,
        $tempDir,
        $testsDir,
        $verbose = false
    ) {
        $this->phpExecutor = $phpExecutor;
        $this->tempDir = $tempDir;
        $this->testsDir = $testsDir;
        $this->extensionsToLoad = $extensionsToLoad;
        $this->pdfTools = $pdfTools;
        $this->verbose = $verbose;
    }

    /**
     * @return string[]
     */
    private function extensionsToLoad()
    {
        $toLoad = array();
        foreach ($this->extensionsToLoad as $extension) {
            // "false" means "not built-in but available"
            if ($this->phpExecutor->getExtensionStatus($extension) === false) {
                $toLoad[] = $extension;
            }
        }
        return $toLoad;
    }

    /**
     * @param string $file Path of PHP file to execute
     * @param string $outputFile Path to output file
     * @param string $outputFileError Path to output file error
     * @return bool TRUE if successful, FALSE otherwise
     */
    public function execute(
        $file,
        $outputFile,
        $outputFileError
    ) {
        $exec = implode(' ', array(
            (string)$this->phpExecutor,
            $this->getPhpCliOptions(),
            '-f ' . escapeshellarg($file),
            '1> ' . escapeshellarg($outputFile),
            '2> ' . escapeshellarg($outputFileError)
        ));
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        return (0 === $resultCode);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function assertIsPhpValidFile($file)
    {
        return $this->phpExecutor->isValidPhpFile($file);
    }

    /**
     * @param string $outputFile Path to output file
     * @return bool
     */
    private function isPdfFile($outputFile)
    {
        return $this->pdfTools->isPdf($outputFile);
    }

    /**
     * @param string $outputFile Path to output file
     * @param string $outputFileError Path to error file
     * @param string $type Expected type of output
     * @param bool $preservingFiles
     * @return bool
     */
    public function assertIsFileType(
        $outputFile,
        $outputFileError,
        $type,
        $preservingFiles
    ) {
        $valid = false;

        $expectedHead = array(
            'PDF' => '%PDF',
            'PNG' => chr(0x89) . chr(0x50) . chr(0x4e) . chr(0x47),
            'HTML' => '<div ',
            'SVG' => '<?xml version="1.0" standalone="no"?>',
        );

        $error = file_get_contents($outputFileError);
        $outputHead = file_get_contents($outputFile, false, null, 0, strlen($expectedHead[$type]));

        if ($error || '' === $outputHead || false === $outputHead) {
            echo "    Output: NOT $type FILE" . PHP_EOL;
            if ($preservingFiles) {
                echo '    Output file: ' . $outputFile . PHP_EOL;
                echo '    Output error file: ' . $outputFileError . PHP_EOL;
            }
            echo '    Logs:' . PHP_EOL;
            echo '---------------------------' . PHP_EOL;
            echo $error . PHP_EOL;
            echo '---------------------------' . PHP_EOL;
        } elseif ($expectedHead[$type] !== $outputHead) {
            echo "    Output: NOT $type FILE" . PHP_EOL;
            if ($preservingFiles) {
                echo '    Output file: ' . $outputFile . PHP_EOL;
            }
            echo '    Logs:' . PHP_EOL;
            echo '---------------------------' . PHP_EOL;
            // cut before the output starts and destroys the final logs
            $output = file_get_contents($outputFile);
            $headMarker = strpos($output, $expectedHead[$type]);
            if (false !== $headMarker) {
                echo substr($output, 0, $headMarker) . PHP_EOL;
            } else {
                echo $output . PHP_EOL;
            }
            echo '---------------------------' . PHP_EOL;
        } elseif ('PDF' === $type && !$this->isPdfFile($outputFile)) {
            echo "    Output: NOT PDF FILE" . PHP_EOL;
            if ($preservingFiles) {
                echo '    Output file: ' . $outputFile . PHP_EOL;
            }
        } else {
            $valid = true;
            echo "    Output: $type" . PHP_EOL;
            if ($preservingFiles) {
                echo '    Output file: ' . $outputFile . PHP_EOL;
            }
        }

        return $valid;
    }

    /**
     * @return string
     */
    private function getPhpExtensionCliOptions()
    {
        if (null === $this->cliExtensionOptions) {
            $extensions = array();
            foreach ($this->extensionsToLoad() as $extension) {
                $extensions[] = '-d extension=' . $this->phpExecutor->makePhpExtensionFileName($extension);
            }
            $this->cliExtensionOptions = implode(' ', $extensions);
        }
        return $this->cliExtensionOptions;
    }

    /**
     * @return string
     */
    private function getPhpCliOptions()
    {
        if (null === $this->cliOptions) {
            // Some examples load a bit more into memory (this is why the limit is set to 1G)
            // Avoid side effects on classes installed on the system, set include_path to
            // a folder without php classes (include_path)

            $extensionDir = $this->phpExecutor->makeCliOption(
                'extension_dir',
                $this->phpExecutor->getPhpExtensionDir()
            );
            $includePath = $this->phpExecutor->makeCliOption(
                'include_path',
                $this->tempDir
            );
            $autoPrependFile = $this->phpExecutor->makeCliOption(
                'auto_prepend_file',
                $this->testsDir . 'coverage.php'
            );

            $this->cliOptions = implode(' ', array(
                '-n',
                '-d date.timezone=UTC',
                '-d display_errors=on',
                '-d error_reporting=-1',
                '-d memory_limit=1G',
                $includePath,
                $extensionDir,
                $this->getPhpExtensionCliOptions(),
                $autoPrependFile,
            ));
        }
        return $this->cliOptions;
    }
}
