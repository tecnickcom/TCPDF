<?php

declare(strict_types=1);

/**
 * Local file allowlist tests for the TCPDF compatibility facade.
 *
 * Cover image masks, image size probing and CSS url() resources, which must
 * follow the same path allowlist as plain Image() calls.
 *
 * @package com.tecnick.tcpdf
 */

use Com\Tecnick\Pdf\Image\Exception as ImageException;

class TcpdfFileSandboxTest extends TcpdfTestCase
{
    /** Scratch directory inside the system temp dir. */
    private string $tempdir = '';

    /** Scratch directory inside the package tree. */
    private string $localdir = '';

    /** @throws \Random\RandomException */
    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $this->tempdir = sys_get_temp_dir() . '/tcpdf_Sandbox_' . $suffix;
        $this->localdir = dirname(__DIR__) . '/target/test/Sandbox_' . $suffix;
        mkdir($this->tempdir, 0o700, true);
        mkdir($this->localdir, 0o700, true);
        foreach ([$this->tempdir, $this->localdir] as $dir) {
            $this->writePng($dir . '/Pic.png', 0x33_66_99);
            $this->writePng($dir . '/Mask.png', 0x80_80_80);
        }
    }

    protected function tearDown(): void
    {
        foreach ([$this->tempdir, $this->localdir] as $dir) {
            if ($dir === '' || !is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*');
            foreach ($files === false ? [] : $files as $file) {
                unlink($file);
            }

            rmdir($dir);
        }
    }

    public function testMaskedImageOutsideAllowlistIsRejected(): void
    {
        $pdf = $this->newSandboxedPdf();
        $pdf->AddPage();
        $mask = $pdf->Image($this->tempdir . '/Mask.png', 0, 0, 0, 0, '', '', '', false, 300, '', true);

        $this->expectException(ImageException::class);
        $pdf->Image($this->tempdir . '/Pic.png', 10, 10, 10, 10, '', '', '', false, 300, '', false, $mask);
    }

    public function testMaskedImageInsideAllowlistKeepsSoftMask(): void
    {
        $pdf = $this->newPdf();
        $pdf->setCompression(false);
        $pdf->AddPage();
        $mask = $pdf->Image($this->tempdir . '/Mask.png', 0, 0, 0, 0, '', '', '', false, 300, '', true);
        $pdf->Image($this->tempdir . '/Pic.png', 10, 10, 10, 10, '', '', '', false, 300, '', false, $mask);

        $this->assertStringContainsString('/SMask ', (string) $pdf->getPDFData());
    }

    public function testImageSizeOutsideAllowlistIsUnknown(): void
    {
        $pdf = $this->newSandboxedPdf();
        $pdf->AddPage();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Unable to determine the size of the image/');
        $pdf->Image($this->tempdir . '/Pic.png', 10, 10);
    }

    public function testMarkupAllowlistExcludesSystemTemp(): void
    {
        $temp = realpath(sys_get_temp_dir());
        $method = new \ReflectionMethod(TCPDF::class, 'fileOptions');
        $options = $method->invoke($this->newPdf());
        $this->assertIsArray($options);
        $allowed = $options['allowedPaths'] ?? null;
        $markup = $options['markupAllowedPaths'] ?? null;
        $this->assertIsArray($allowed);
        $this->assertIsArray($markup);

        $this->assertContains($temp, $allowed);
        $this->assertNotContains($temp, $markup);
    }

    public function testCssUrlOutsideAllowlistIsNotCopied(): void
    {
        $before = $this->cssCopies();
        $pdf = $this->newPdf();
        $pdf->setCompression(false);
        $pdf->AddPage();

        $embedded = 0;
        try {
            $pdf->writeHTML($this->listImageHtml($this->tempdir . '/Pic.png'));
            $embedded = substr_count((string) $pdf->getPDFData(), '/Subtype /Image');
        } catch (ImageException $e) {
            $this->assertStringContainsString('Unable to read image file', $e->getMessage());
        }

        $this->assertSame(0, $embedded);
        $this->assertSame($before, $this->cssCopies());
    }

    public function testCssUrlInsideAllowlistKeepsUppercasePath(): void
    {
        $before = $this->cssCopies();
        $pdf = $this->newPdf();
        $pdf->setCompression(false);
        $pdf->AddPage();
        $pdf->writeHTML($this->listImageHtml($this->localdir . '/Pic.png'));

        $this->assertSame(1, substr_count((string) $pdf->getPDFData(), '/Subtype /Image'));
        $this->assertSame($before, $this->cssCopies());
    }

    /**
     * Create a document whose path allowlist does not contain the system temp dir.
     */
    private function newSandboxedPdf(): TCPDF
    {
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            /**
             * @return array<int, string>
             */
            protected function fileAllowedPaths(): array
            {
                $temp = realpath(sys_get_temp_dir());
                return array_values(array_filter(
                    parent::fileAllowedPaths(),
                    static fn(string $path): bool => $path !== $temp,
                ));
            }
        };
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        return $pdf;
    }

    private function listImageHtml(string $src): string
    {
        return '<ul style="list-style-image:url(' . $src . ')"><li>item</li></ul>';
    }

    /**
     * @return array<int, string>
     */
    private function cssCopies(): array
    {
        $files = glob(sys_get_temp_dir() . '/tcpdf_css_*');
        return $files === false ? [] : $files;
    }

    private function writePng(string $file, int $color): void
    {
        $img = imagecreatetruecolor(8, 8);
        imagefill($img, 0, 0, $color);
        imagepng($img, $file);
    }
}
