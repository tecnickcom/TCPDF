<?php

/**
 * Helper class to execute PDF-related commands via shell
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

use LogicException;
use RuntimeException;

class PdfTools
{
    /**
     * @var string|null Path to pdfinfo as shell argument
     */
    private $pdfinfo = null;

    /**
     * @var string|null Path to pdftopng as shell argument
     */
    private $pdftopng = null;

    /**
     * @var string|null Path to pdftoppm as shell argument
     */
    private $pdftoppm = null;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var string
     */
    private $pdfinfoVersionInfo;

    /**
     * @var string
     */
    private $pdftopngVersionInfo;

    /**
     * @param string[] $tools Path to PDF tool executables (indexed by tool name)
     * @param bool $verbose
     */
    public function __construct(
        array $tools,
        $verbose = false
    ) {
        if (!empty($tools['pdfinfo'])) {
            $this->pdfinfo = escapeshellarg($tools['pdfinfo']);
        }
        if (!empty($tools['pdftopng'])) {
            $this->pdftopng = escapeshellarg($tools['pdftopng']);
        }
        if (!empty($tools['pdftoppm'])) {
            $this->pdftoppm = escapeshellarg($tools['pdftoppm']);
        }
        $this->verbose = $verbose;
    }

    /**
     * @return string pdfinfo version information (multiline information)
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getPdfinfoVersionInfo()
    {
        if (null === $this->pdfinfo) {
            throw new LogicException('No path to pdfinfo. Provide it to ' . __CLASS__ . ' PHP class constructor.');
        }
        if (null === $this->pdfinfoVersionInfo) {
            $exec = sprintf('%s -v 2>&1', $this->pdfinfo);
            if ($this->verbose) {
                echo $exec . PHP_EOL;
            }
            exec($exec, $output, $resultCode);
            if (0 !== $resultCode && 99 !== $resultCode) {
                throw new RuntimeException('Execution failed: ' . $exec);
            }
            $this->pdfinfoVersionInfo = implode(PHP_EOL, $output);
        }
        return $this->pdfinfoVersionInfo;
    }

    /**
     * @return string pdftopng or pdftoppm version information (multiline information)
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getPdftopngVersionInfo()
    {
        $tool = $this->pdftopng ?: $this->pdftoppm;
        if (null === $tool) {
            throw new LogicException('No path to pdftopng not pdftoppm. Provide it to ' . __CLASS__ . ' PHP class constructor.');
        }
        if (null === $this->pdftopngVersionInfo) {
            $exec = sprintf('%s -v 2>&1', $tool);
            if ($this->verbose) {
                echo $exec . PHP_EOL;
            }
            exec($exec, $output, $resultCode);
            if (0 !== $resultCode && 99 !== $resultCode) {
                throw new RuntimeException('Execution failed: ' . $exec);
            }
            $this->pdftopngVersionInfo = implode(PHP_EOL, $output);
        }
        return $this->pdftopngVersionInfo;
    }

    /**
     * @param string $file Path of file to check
     * @return bool
     * @throws LogicException
     */
    public function isPdf($file)
    {
        if (null === $this->pdfinfo) {
            throw new LogicException('No path to pdfinfo. Provide it to ' . __CLASS__ . ' PHP class constructor.');
        }
        $exec = implode(' ', array(
            $this->pdfinfo,
            escapeshellarg($file)
        ));
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        if ($this->verbose) {
            echo implode(PHP_EOL, $output) . PHP_EOL;
        }
        return (0 === $resultCode);
    }

    private function ensureFolder($path)
    {
        if (file_exists($path) && is_dir($path) && is_writable($path)) {
            return;
        }

        if (!mkdir($path, 0775, true)) {
            throw new RuntimeException('Could not create folder: ' . $path);
        }
    }

    /**
     * @param string $file The path of the PDF document to convert into PNG
     * @param string $pngRoot The root of the generated PNG file names.
     *    Example: if <code>$pngRoot = '/usr/home/TCPDF/compare_runs/my-root'</code>,
     *    the generated PNG files will be as follows:
     *    <ul>
     *     <li><code>/usr/home/TCPDF/compare_runs/my-root-0000001.png</code>,</li>
     *     <li><code>/usr/home/TCPDF/compare_runs/my-root-0000002.png</code>,</li>
     *     <li>...</li>
     *    </ul>
     * @return string[] List of paths for generated PNG (one per page)
     * @throws LogicException
     */
    public function convertToPng($file, $pngRoot)
    {
        if ($this->pdftopng) {
            $tool = $this->pdftopng;
        } elseif ($this->pdftoppm) {
            // When using pdftoppm, we specify the `-png` option to get PNG files
            $tool = $this->pdftoppm . ' -png';
        }
        if (!isset($tool)) {
            throw new LogicException('No path to pdftopng nor pdftoppm. Provide it to ' . __CLASS__ . ' PHP class constructor.');
        }
        $this->ensureFolder(dirname($pngRoot));
        $exec = implode(' ', array(
            $tool,
            escapeshellarg($file),
            escapeshellarg($pngRoot),
            ' 2>&1',
        ));
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        if ($this->verbose) {
            echo implode(PHP_EOL, $output) . PHP_EOL;
        }
        if (0 !== $resultCode) {
            throw new RuntimeException(implode(PHP_EOL, $output));
        }
        $generatedFiles = glob($pngRoot . '*.png');
        if (false === $generatedFiles) {
            throw new RuntimeException('Could not get the list of generated PNG files.');
        }
        return $generatedFiles;
    }
}
