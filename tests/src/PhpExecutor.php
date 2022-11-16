<?php

/**
 * Helper class to execute PHP via shell
 *
 * @author Philippe Jausions
 * @license http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 */

namespace Tecnickcom\TCPDF\Tests;

use RuntimeException;

class PhpExecutor
{
    /**
     * @var string PHP executable as a shell argument
     */
    private $phpShell;

    /**
     * @var string
     */
    private $extensionDir = null;

    /**
     * @var int
     */
    private $phpVersion = null;

    /**
     * @var string
     */
    private $phpVersionInfo = null;

    /**
     * @var bool[]
     */
    private $builtInExtensions = array();

    /**
     * @var mixed[]
     */
    private $extensionStatuses = array();

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @param string $php Path to PHP executable
     * @param bool $verbose
     */
    public function __construct($php, $verbose = false)
    {
        $this->phpShell = escapeshellarg($php);
        $this->verbose = $verbose;
    }

    public function __toString()
    {
        return $this->phpShell;
    }

    /**
     * @param string $command PHP command to execute
     * @param string|null $cliOptions MUST be properly escaped for the shell
     * @return string Result of the command
     * @throws RuntimeException
     */
    public function executeCommand($command, $cliOptions = null)
    {
        $exec = sprintf(
            '%s %s -r %s',
            $this->phpShell,
            $cliOptions,
            escapeshellarg($command)
        );
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        if (0 !== $resultCode) {
            throw new RuntimeException('Execution failed: ' . $exec);
        }
        return $output[0];
    }

    /**
     * @return string PHP version information (multiline information)
     * @throws RuntimeException
     */
    public function getPhpVersionInfo()
    {
        if (null === $this->phpVersionInfo) {
            $exec = sprintf('%s -n -v', $this->phpShell);
            if ($this->verbose) {
                echo $exec . PHP_EOL;
            }
            exec($exec, $output, $resultCode);
            if (0 !== $resultCode) {
                throw new RuntimeException('Execution failed: ' . $exec);
            }
            $this->phpVersionInfo = implode(PHP_EOL, $output);
        }
        return $this->phpVersionInfo;
    }

    /**
     * @return int PHP version as a integer
     * @throws RuntimeException
     */
    public function getPhpVersion()
    {
        if (null === $this->phpVersion) {
            $this->phpVersion = (int)$this->executeCommand('echo PHP_VERSION_ID;');
        }
        return $this->phpVersion;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getPhpExtensionDir()
    {
        if (null === $this->extensionDir) {
            $this->extensionDir = $this->executeCommand("echo ini_get('extension_dir');");
        }
        return $this->extensionDir;
    }

    /**
     * @return bool
     */
    private function isWindows()
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    /**
     * @param string $extension Extension name (without .so, .dll, or "php_" prefix)
     * @return string
     */
    public function makePhpExtensionFileName($extension)
    {
        if ($this->isWindows()) {
            if ('gd' === $extension && $this->getPhpVersion() < 80000) {
                $extension = 'gd2';
            }
            return "php_$extension.dll";
        }
        return $extension . '.so';
    }

    /**
     * @param string $path
     * @return string
     */
    private function escapePath($path)
    {
        if ($this->isWindows()) {
            // if ($this->getPhpVersion() < 50400) {
            //     $path = str_replace('\\', '/', $path);
            // }
            if (strpos($path, '~') !== false) {
                return "'" . $path . "'";
            }
        }
        if (strpos($path, ' ') !== false) {
            return "'" . $path . "'";
        }
        return escapeshellarg((string)$path);
    }

    /**
     * @param string $path
     * @return string
     */
    private function escapePathForCliOption($path)
    {
        if ($this->isWindows()) {
            if ($this->getPhpVersion() < 50400) {
                $path = str_replace('\\', '/', $path);
                return "'" . $path . "'";
            }
            if (strpos($path, '~') !== false) {
                return "'" . $path . "'";
            }
        }
        if (strpos($path, ' ') !== false) {
            return "'" . $path . "'";
        }
        return (string)$path;
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    public function makeCliOption($name, $value)
    {
        switch ($name) {
            case 'auto_prepend_file':
            case 'extension_dir':
            case 'include_path':
                $arg = $this->escapePathForCliOption($value);
                break;
            default:
                $arg = escapeshellarg($value);
        }

        return sprintf('-d %s=%s', $name, $arg);
    }

    /**
     * @param string $extension Extension name (without .so, .dll, or "php_" prefix)
     * @return bool Whether the extension is available for loading into PHP
     */
    public function isExtensionAvailable($extension)
    {
        $extensionLib = $this->makePhpExtensionFileName($extension);
        return file_exists($this->getPhpExtensionDir() . DIRECTORY_SEPARATOR . $extensionLib);
    }

    /**
     * @param string $extension Extension name (without .so, .dll, or "php_" prefix)
     * @return bool Whether the extension is built in PHP
     * @throws RuntimeException
     */
    public function isExtensionBuiltIn($extension)
    {
        if (!isset($this->builtInExtensions[$extension])) {
            $this->builtInExtensions[$extension] = (bool)$this->executeCommand("echo extension_loaded('$extension') ? 1 : 0;", '-n');
        }
        return $this->builtInExtensions[$extension];
    }

    /**
     * @param string $extension Extension name (without .so, .dll, or "php_" prefix)
     * @return bool|null
     * <ul>
     *  <li>true: built in PHP,</li>
     *  <li>false: available,</li>
     *  <li>null: not available (not detected).</li>
     * </ul>
     */
    public function getExtensionStatus($extension)
    {
        if (!array_key_exists($extension, $this->extensionStatuses)) {
            if ($this->isExtensionBuiltIn($extension)) {
                $this->extensionStatuses[$extension] = true;
            } elseif ($this->isExtensionAvailable($extension)) {
                $this->extensionStatuses[$extension] = false;
            } else {
                $this->extensionStatuses[$extension] = null;
            }
        }
        return $this->extensionStatuses[$extension];
    }

    /**
     * @param string $file Path to file to lint
     * @return bool Whether the PHP syntax of the file is okay
     */
    public function isValidPhpFile($file)
    {
        $exec = sprintf(
            '%s -l %s',
            $this->phpShell,
            $this->escapePath($file)
        );
        if ($this->verbose) {
            echo $exec . PHP_EOL;
        }
        exec($exec, $output, $resultCode);
        return (0 === $resultCode);
    }
}
