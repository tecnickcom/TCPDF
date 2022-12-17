<?php

/**
 * Helper class to execute magick via shell
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

use LogicException;
use RuntimeException;

class ImageMagick
{
    /**
     * @var string|null Path to magick as shell argument
     */
    private $magick = null;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var string
     */
    private $magickVersionInfo;

    /**
     * @param string $magick Path to ImageMagick `magick` executable
     * @param bool $verbose
     */
    public function __construct(
        $magick,
        $verbose = false
    ) {
        $this->magick = escapeshellarg($magick);
        $this->verbose = $verbose;
    }

    /**
     * @return string pdfinfo version information (multiline information)
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getMagickVersionInfo()
    {
        if (null === $this->magick) {
            throw new LogicException('No path to magick. Provide it to ImageMagick PHP class constructor.');
        }
        if (null === $this->magickVersionInfo) {
            $exec = sprintf('%s -version 2>&1', $this->magick);
            if ($this->verbose) {
                echo $exec . PHP_EOL;
            }
            exec($exec, $output, $resultCode);
            if (0 !== $resultCode && 99 !== $resultCode) {
                throw new RuntimeException('Execution failed: ' . $exec);
            }
            $this->magickVersionInfo = implode(PHP_EOL, $output);
        }
        return $this->magickVersionInfo;
    }

    /**
     * @param string $file1 Path to image to compare
     * @param string $file2 Path to image to compare
     * @return bool
     * @throws LogicException
     */
    public function areSimilar($file1, $file2)
    {
        if (null === $this->magick) {
            throw new LogicException('No path to magick. Provide it to ' . __CLASS__ . ' PHP class constructor.');
        }
        $exec = implode(' ', array(
            $this->magick,
            'compare',
            '-metric MAE',
            escapeshellarg($file1),
            escapeshellarg($file2),
            'null:',
            ' 2>&1',
        ));
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        $result = implode(PHP_EOL, $output);
        if ($this->verbose) {
            echo $result . PHP_EOL;
        }
        if (0 !== $resultCode) {
            if (!preg_match('/^[-0-9.e]+\s+\([-0-9.e]+\)$/', $result)) {
                throw new RuntimeException(
                    'An error occurred with magick compare command' . PHP_EOL
                    . $result,
                    $resultCode
                );
            }
        }
        return '0 (0)' === $result;
    }
}
