<?php

/**
 * Helper class to execute the TCPDF test suite
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

class TestRunner
{
    /**
     * @var string
     */
    private $exampleDir;

    /**
     * @var TestExecutor
     */
    private $testExecutor;

    /**
     * @var string[]
     */
    private $excludedFiles;

    /**
     * @var bool
     */
    private $preserveFiles = false;

    /**
     * @var array
     */
    private $failed = array();

    /**
     * @var array
     */
    private $generatedFiles = array();

    /**
     * @var array
     */
    private $ignored = array();

    /**
     * @var array|null
     */
    private $onlyFiles = null;

    /**
     * Test count
     * @var int
     */
    private $count;

    /**
     * @var array
     */
    private $stopOn = array();

    /**
     * @var int|null
     */
    private $runTime = null;

    /**
     * @var array|null
     */
    private $groups = null;

    public function __construct($exampleDir)
    {
        $this->exampleDir = rtrim($exampleDir, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * @param TestExecutor $testExecutor
     * @return $this
     */
    public function withTestExecutor(TestExecutor $testExecutor)
    {
        $this->testExecutor = $testExecutor;
        return $this;
    }

    /**
     * @param bool $preserve
     * @return $this
     */
    public function preserveOutputFiles($preserve = true)
    {
        $this->preserveFiles = (bool)$preserve;
        return $this;
    }

    /**
     * @param array $excluded
     * @return $this
     */
    public function excludeTests(array $excluded)
    {
        $this->excludedFiles = $excluded;
        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function only(array $files)
    {
        $this->onlyFiles = empty($files) ? null : $files;
        return $this;
    }

    /**
     * @param array $groups
     * @return $this
     */
    public function filterByGroup(array $groups)
    {
        $this->groups = (empty($groups)) ? null : $groups;
        return $this;
    }

    /**
     * @param string[] $conditions
     * @return $this
     */
    public function stopOn(array $conditions)
    {
        $this->stopOn = $conditions;
        return $this;
    }

    /**
     * @param string $condition
     * @return bool
     */
    private function shouldStopOn($condition)
    {
        if (in_array('defect', $this->stopOn, true)) {
            return true;
        }
        if (in_array($condition, $this->stopOn, true)) {
            return true;
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getTestFiles()
    {
        chdir($this->exampleDir);
        $exampleFiles = array_flip(glob('example*.php'));
        $exampleFiles = array_flip($exampleFiles);

        $exampleBarcodeFiles = glob('barcodes/example*.php');

        $files = array();
        foreach ($exampleFiles as $exampleFile) {
            $files[$exampleFile] = 'PDF';
        }

        foreach ($exampleBarcodeFiles as $exampleFile) {
            $type = preg_replace('/^.+(html|png|svgi?)$/', '\1', basename($exampleFile, '.php'));
            if ('svgi' === $type) {
                $files[$exampleFile] = 'SVG';
            } else {
                $files[$exampleFile] = strtoupper($type);
            }
        }

        if (null !== $this->onlyFiles) {
            foreach ($files as $file => $type) {
                if (!in_array($file, $this->onlyFiles, true)) {
                    unset($files[$file]);
                }
            }
        }

        if (null !== $this->groups) {
            $regExp = '/\*\s*@group\s+(' . implode('|', $this->groups) . ')\s/';
            foreach ($files as $file => $type) {
                $source = file_get_contents($file);
                if (!preg_match($regExp, $source)) {
                    unset($files[$file]);
                }
            }
        }

        return $files;
    }

    /**
     * @param string $outputDir Path to output folder
     * @return bool TRUE if all tests passed, FALSE otherwise
     */
    public function runTests($outputDir)
    {
        if (!isset($this->testExecutor)) {
            throw new \RuntimeException("Test executor is missing. Did you forget to call withTestExecutor()?");
        }

        $this->runTime = null;
        $startTime = time();

        $outputFolder = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR;
        chdir($this->exampleDir);

        $this->failed = array();
        $this->ignored = array();
        $this->generatedFiles = array();
        $this->count = 0;
        foreach ($this->getTestFiles() as $file => $type) {
            ++$this->count;
            echo 'File: ' . $file . PHP_EOL;
            if (in_array($file, $this->excludedFiles, true)) {
                echo '    Run: SKIPPED' . PHP_EOL;
                $this->ignored[] = $file;
                continue;
            }
            if (!$this->testExecutor->assertIsPhpValidFile($this->exampleDir . $file)) {
                echo '    Lint: FAILED' . PHP_EOL;
                $this->failed[] = $file;
                if ($this->shouldStopOn('failure')) {
                    break;
                }
                continue;
            }
            echo '    Lint: PASSED' . PHP_EOL;

            if (!$this->preserveFiles) {
                $outputFile = $outputFolder . 'output.pdf';
                $outputFileError = $outputFolder . 'errors.txt';
            } else {
                $baseName = $outputFolder . basename($file, '.php');
                $outputFile = $baseName . '.output.' . strtolower($type);
                $outputFileError = $baseName . '.errors.txt';
            }
            $this->generatedFiles[$outputFile] = $file;
            $this->generatedFiles[$outputFileError] = $file;

            $isSuccess = $this->testExecutor->execute(
                $this->exampleDir . $file,
                $outputFile,
                $outputFileError
            );

            if (!$isSuccess) {
                echo '    Run: FAILED' . PHP_EOL;
                $this->failed[] = $file;
                $this->testExecutor->assertIsFileType(
                    $outputFile,
                    $outputFileError,
                    $type,
                    $this->preserveFiles
                );
                if ($this->shouldStopOn('failure')) {
                    break;
                }
            } else {
                echo '    Run: PASSED' . PHP_EOL;
                $isFileType = $this->testExecutor->assertIsFileType(
                    $outputFile,
                    $outputFileError,
                    $type,
                    $this->preserveFiles
                );
                if (!$isFileType) {
                    $this->failed[] = $file;
                    if ($this->shouldStopOn('failure')) {
                        break;
                    }
                }
            }
        }
        $this->runTime = time() - $startTime;

        $this->cleanUp();
        return empty($this->failed);
    }

    /**
     * @return string[]
     */
    public function getFailedTests()
    {
        return $this->failed;
    }

    /**
     * @return string[]
     */
    public function getGeneratedFiles()
    {
        return array_keys($this->generatedFiles);
    }

    /**
     * @return string[]
     */
    public function getSkippedTests()
    {
        return $this->ignored;
    }

    /**
     * @return int
     */
    public function getTotalTestCount()
    {
        return $this->count;
    }

    /**
     * Get last run time in seconds
     * @return int|null
     */
    public function getRunTime()
    {
        return $this->runTime;
    }

    /**
     * @return void
     */
    private function cleanUp()
    {
        if ($this->preserveFiles) {
            echo 'Generated files remaining on disk: ' . count($this->generatedFiles) . PHP_EOL;
            return;
        }

        foreach ($this->getGeneratedFiles() as $generatedFile) {
            if (file_exists($generatedFile)) {
                unlink($generatedFile);
            }
        }
    }

    /**
     * Outputs a summary of the last test suite run
     * @return $this
     */
    public function printSummary()
    {
        $failed = $this->getFailedTests();
        $ignored = $this->getSkippedTests();
        $failedCount = count($failed);
        $ignoredCount = count($ignored);
        if ($failedCount === 0) {
            echo 'Test suite: PASSED' . PHP_EOL;
        } else {
            echo 'Test suite: FAILED' . PHP_EOL;
        }
        echo '    Runtime: ' . $this->getRunTime() . 's' . PHP_EOL;
        echo '    Total tests: ' . $this->getTotalTestCount() . PHP_EOL;
        if ($ignoredCount > 0) {
            echo '    SKipped tests: ' . $ignoredCount . PHP_EOL;
            foreach ($ignored as $ignoredFile) {
                echo '        ' . $ignoredFile . PHP_EOL;
            }
        }
        if ($failedCount > 0) {
            echo '    Failed tests: ' . $failedCount . PHP_EOL;
            foreach ($failed as $failedFile) {
                echo '        ' . $failedFile . PHP_EOL;
            }
        }
        return $this;
    }
}
