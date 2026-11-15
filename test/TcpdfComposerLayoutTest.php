<?php

declare(strict_types=1);

/**
 * Composer dependency layout tests for the TCPDF compatibility facade.
 *
 * TCPDF is copied to <scratch>/vendor/tecnickcom/tcpdf/ and run from
 * <scratch>/public/ in a child process, with the tc-lib packages loaded from
 * the repository vendor directory. The engine is then outside K_PATH_MAIN,
 * the working directory, the script directory and the system temp directory.
 *
 * @package com.tecnick.tcpdf
 */

class TcpdfComposerLayoutTest extends TcpdfTestCase
{
    /** Scratch project directory. */
    private string $scratch = '';

    /** @throws \Random\RandomException */
    protected function setUp(): void
    {
        $root = dirname(__DIR__);
        $this->scratch = $root . '/target/test/Composer_' . bin2hex(random_bytes(6));
        $tcpdf = $this->scratch . '/vendor/tecnickcom/tcpdf';
        mkdir($tcpdf . '/config', 0o700, true);
        mkdir($this->scratch . '/public', 0o700, true);
        mkdir($this->scratch . '/fonts', 0o700, true);
        copy($root . '/tcpdf.php', $tcpdf . '/tcpdf.php');
        copy($root . '/tcpdf_autoconfig.php', $tcpdf . '/tcpdf_autoconfig.php');
        $configs = glob($root . '/config/*');
        foreach ($configs === false ? [] : $configs as $file) {
            copy($file, $tcpdf . '/config/' . basename($file));
        }

        file_put_contents($this->scratch . '/public/run.php', self::script());
    }

    protected function tearDown(): void
    {
        if ($this->scratch !== '' && is_dir($this->scratch)) {
            self::removeTree($this->scratch);
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function iccCaseProvider(): array
    {
        return [
            'PDF/A-1b' => ['pdfa1b'],
            'PDF/A-3' => ['pdfa3'],
            'sRGB mode' => ['srgb'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('iccCaseProvider')]
    public function testIccProfileIsEmbeddedInAComposerInstall(string $case): void
    {
        $raw = $this->render($case, false);

        $this->assertStringContainsString('/OutputIntents', $raw);
        $this->assertSame(1, preg_match('~<< /N 3 /Filter /FlateDecode /Length \d+ >> stream~', $raw));
    }

    public function testCoreFontLoadsWithACustomFontPath(): void
    {
        $raw = $this->render('corefont', true);

        $this->assertStringContainsString('/BaseFont /Helvetica', $raw);
    }

    public function testEmbeddedFontLoadsWithACustomFontPath(): void
    {
        $raw = $this->render('embedfont', true);

        $this->assertStringContainsString('/FontFile2 ', $raw);
    }

    /**
     * The document file helpers still refuse the bundled engine files.
     */
    public function testBundledFilesStayOutsideTheDocumentAllowlists(): void
    {
        $out = $this->runScript('allowlist', true);
        $this->assertSame(0, $out['code'], $out['stderr']);

        /** @var mixed $checks */
        $checks = json_decode($out['stdout'], true);
        $this->assertSame(
            [
                'file icc' => false,
                'markup icc' => false,
                'file font' => false,
                'markup font' => false,
            ],
            $checks,
        );
    }

    /**
     * Render a document in the child process and return the PDF data.
     */
    private function render(string $case, bool $customFonts): string
    {
        $out = $this->runScript($case, $customFonts);
        $this->assertSame(0, $out['code'], $out['stderr']);
        $this->assertStringStartsWith('%PDF-', $out['stdout']);

        return $out['stdout'];
    }

    /**
     * Run the scratch script with the working directory set to <scratch>/public.
     *
     * K_PATH_FONTS is the empty <scratch>/fonts/ directory when $customFonts is true,
     * otherwise the tc-lib font set used by this test suite.
     *
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runScript(string $case, bool $customFonts): array
    {
        $cmd = [
            PHP_BINARY,
            $this->scratch . '/public/run.php',
            $case,
            dirname(__DIR__),
            $customFonts ? $this->scratch . '/fonts/' : (string) K_PATH_FONTS,
        ];
        $pipes = [];
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->scratch . '/public');
        $this->assertIsResource($proc);
        $outPipe = $pipes[1] ?? null;
        $errPipe = $pipes[2] ?? null;
        $this->assertIsResource($outPipe);
        $this->assertIsResource($errPipe);
        $stdout = (string) stream_get_contents($outPipe);
        $stderr = (string) stream_get_contents($errPipe);
        fclose($outPipe);
        fclose($errPipe);

        return ['code' => proc_close($proc), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * Child process script: argv = case, repository root, K_PATH_FONTS.
     */
    private static function script(): string
    {
        return <<<'PHP'
            <?php
            declare(strict_types=1);

            [, $case, $repo, $fonts] = $argv;
            define('K_PATH_FONTS', $fonts);

            define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
            require $repo . '/vendor/autoload.php';
            require dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php';

            class LayoutPdf extends TCPDF
            {
                public function allowed(string $path): array
                {
                    $eng = $this->engine();
                    return [$eng->file->isAllowedFile($path), $eng->markupFile->isAllowedFile($path)];
                }
            }

            try {
                $pdfa = match ($case) {
                    'pdfa1b' => true,
                    'pdfa3' => 3,
                    default => false,
                };
                $pdf = new LayoutPdf('P', 'mm', 'A4', true, 'UTF-8', false, $pdfa);
                $pdf->setCompression(false);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);

                if ($case === 'allowlist') {
                    $engine = (string) (new ReflectionClass(\Com\Tecnick\Pdf\Tcpdf::class))->getFileName();
                    $icc = dirname($engine) . '/include/sRGB.icc.z';
                    $font = \Com\Tecnick\Pdf\Font\FontPaths::getOutputPath() . '/core/helvetica.json';
                    [$fileIcc, $markupIcc] = $pdf->allowed($icc);
                    [$fileFont, $markupFont] = $pdf->allowed($font);
                    echo json_encode([
                        'file icc' => $fileIcc,
                        'markup icc' => $markupIcc,
                        'file font' => $fileFont,
                        'markup font' => $markupFont,
                    ]);
                    exit(0);
                }

                if ($case === 'srgb') {
                    $pdf->setSRGBmode(true);
                }

                $pdf->AddPage();
                $pdf->setFont($case === 'embedfont' ? 'dejavusans' : 'helvetica', '', 12);
                $pdf->Write(0, 'Composer layout');
                echo $pdf->Output('', 'S');
            } catch (\Throwable $e) {
                fwrite(STDERR, $e->getMessage());
                exit(1);
            }
            PHP;
    }

    private static function removeTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }

        $entries = scandir($path);
        foreach ($entries === false ? [] : $entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            self::removeTree($path . '/' . $entry);
        }

        rmdir($path);
    }
}
