<?php

declare(strict_types=1);

/**
 * Document whose HTML tags may call the barcode, Rect and SetDrawColor
 * methods (the test process defines K_TCPDF_CALLS_IN_HTML as false).
 *
 * @package com.tecnick.tcpdf
 */
class TcpdfHtmlTagTestPdf extends TCPDF
{
    protected function htmlTagAllowed(string $method): bool
    {
        return in_array($method, ['write1DBarcode', 'write2DBarcode', 'Rect', 'SetDrawColor'], true);
    }
}
