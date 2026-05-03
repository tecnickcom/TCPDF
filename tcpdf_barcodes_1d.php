<?php
//============================================================+
// File name   : tcpdf_barcodes_1d.php
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2026 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
// -------------------------------------------------------------------
//
// Description : PHP class to creates array representations for
//               common 1D barcodes to be used with TCPDF.
//
//============================================================+

/**
 * @file
 * PHP class to creates array representations for common 1D barcodes to be used with TCPDF.
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 */

/**
 * @class TCPDFBarcode
 * PHP class to creates array representations for common 1D barcodes to be used with TCPDF (https://tcpdf.org).<br>
 * @package com.tecnick.tcpdf 
 * @author Nicola Asuni
 */

if (!function_exists('ctype_xdigit')) {
	/**
	 * Polyfill for environments where ext-ctype is not loaded.
	 *
	 * @param mixed $text
	 * @return bool
	 */
	function ctype_xdigit($text) {
		return (is_scalar($text) && preg_match('/^[0-9A-Fa-f]+$/', (string) $text) === 1);
	}
}

class TCPDFBarcode {

	/**
	 * Array representation of barcode.
	 * @protected
	 */
	protected $barcode_array = array();

	/**
	 * Original barcode content.
	 * @protected
	 */
	protected $barcode_code = '';

	/**
	 * Original barcode type.
	 * @protected
	 */
	protected $barcode_type = '';

	/**
	 * tc-lib-barcode model when delegation is available.
	 * @protected
	 */
	protected $barcode_obj = null;

	/**
	 * This is the class constructor.
	 * Return an array representations for common 1D barcodes:<ul>
	 * <li>$arrcode['code'] code to be printed on text label</li>
	 * <li>$arrcode['maxh'] max barcode height</li>
	 * <li>$arrcode['maxw'] max barcode width</li>
	 * <li>$arrcode['bcode'][$k] single bar or space in $k position</li>
	 * <li>$arrcode['bcode'][$k]['t'] bar type: true = bar, false = space.</li>
	 * <li>$arrcode['bcode'][$k]['w'] bar width in units.</li>
	 * <li>$arrcode['bcode'][$k]['h'] bar height in units.</li>
	 * <li>$arrcode['bcode'][$k]['p'] bar top position (0 = top, 1 = middle)</li></ul>
	 * @param string $code code to print
 	 * @param string $type type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extension</li><li>EAN5 : 5-Digits UPC-Based Extension</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
 	 * @public
	 */
	public function __construct($code, $type) {
		$this->setBarcode($code, $type);
	}

	/**
	 * Return an array representations of barcode.
 	 * @return array
 	 * @public
	 */
	public function getBarcodeArray() {
		return $this->barcode_array;
	}

	/**
	 * Create a tc-lib-barcode object for rendering.
	 * @param int $width barcode width in user units.
	 * @param int $height barcode height in user units.
	 * @param string $color foreground color.
	 * @return \Com\Tecnick\Barcode\Model|null
	 */
	protected function getTcLibBarcodeObject($width=-1, $height=-1, $color='black') {
		if (!class_exists('\\Com\\Tecnick\\Barcode\\Barcode')) {
			return null;
		}
		try {
			$barcode = new \Com\Tecnick\Barcode\Barcode();
			return $barcode->getBarcodeObj((string) $this->barcode_type, (string) $this->barcode_code, (int) $width, (int) $height, (string) $color, array(0, 0, 0, 0));
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Convert tc-lib bars into the legacy TCPDF 1D barcode array structure.
	 * @param \Com\Tecnick\Barcode\Model $barcodeobj tc-lib barcode model.
	 * @return array
	 */
	protected function convertTcLibBarcodeArray($barcodeobj) {
		$data = $barcodeobj->getArray();
		$bars = $barcodeobj->getBarsArrayXYWH();
		usort($bars, static function ($left, $right) {
			if ($left[0] === $right[0]) {
				return $left[1] <=> $right[1];
			}
			return $left[0] <=> $right[0];
		});
		$legacy = array(
			'code' => ($data['extcode'] !== '') ? $data['extcode'] : $data['code'],
			'maxh' => $data['height'],
			'maxw' => $data['width'],
			'bcode' => array(),
		);
		$position = 0.0;
		foreach ($bars as $bar) {
			$x = (float) $bar[0];
			$y = (float) $bar[1];
			$w = (float) $bar[2];
			$h = (float) $bar[3];
			if ($x > $position) {
				$legacy['bcode'][] = array('t' => false, 'w' => ($x - $position), 'h' => $data['height'], 'p' => 0);
			}
			$legacy['bcode'][] = array('t' => true, 'w' => $w, 'h' => $h, 'p' => $y);
			$position = $x + $w;
		}
		if ($position < $data['width']) {
			$legacy['bcode'][] = array('t' => false, 'w' => ($data['width'] - $position), 'h' => $data['height'], 'p' => 0);
		}
		return $legacy;
	}

	/**
	 * Convert a legacy RGB color array to a CSS rgb() string.
	 * @param array $color color array.
	 * @return string
	 */
	protected function getCssRgbColor($color) {
		return sprintf('rgb(%d,%d,%d)', (int) $color[0], (int) $color[1], (int) $color[2]);
	}

	/**
	 * Send barcode as SVG image object to the standard output (delegates to tc-lib-barcode).
	 * @param int $w Minimum width of a single bar in user units.
	 * @param int $h Height of barcode in user units.
	 * @param string $color Foreground color (in SVG format) for bar elements.
	 * @public
	 */
	public function getBarcodeSVG($w=2, $h=30, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['maxw'] * $w), (int) round($h), (string) $color);
		if ($obj !== null) {
			$obj->getSvg();
		}
	}

	/**
	 * Return a SVG string representation of barcode (delegates to tc-lib-barcode).
	 * @param int $w Minimum width of a single bar in user units.
	 * @param int $h Height of barcode in user units.
	 * @param string $color Foreground color (in SVG format) for bar elements.
	 * @return string SVG code (empty when the barcode could not be created).
	 * @public
	 */
	public function getBarcodeSVGcode($w=2, $h=30, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['maxw'] * $w), (int) round($h), (string) $color);
		return ($obj !== null) ? $obj->getSvgCode() : '';
	}

	/**
	 * Return an HTML representation of barcode (delegates to tc-lib-barcode).
	 * @param int $w Width of a single bar element in pixels.
	 * @param int $h Height of a single bar element in pixels.
	 * @param string $color Foreground color for bar elements.
	 * @return string HTML code (empty when the barcode could not be created).
	 * @public
	 */
	public function getBarcodeHTML($w=2, $h=30, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['maxw'] * $w), (int) round($h), (string) $color);
		return ($obj !== null) ? $obj->getHtmlDiv() : '';
	}

	/**
	 * Send a PNG image representation of barcode (delegates to tc-lib-barcode; needs GD or Imagick).
	 * @param int $w Width of a single bar element in pixels.
	 * @param int $h Height of a single bar element in pixels.
	 * @param array $color RGB (0-255) foreground color for bar elements.
	 * @public
	 */
	public function getBarcodePNG($w=2, $h=30, $color=array(0,0,0)) {
		$obj = $this->getRenderObject((int) round($this->barcode_array['maxw'] * $w), (int) round($h), $this->getCssRgbColor($color));
		if ($obj !== null) {
			$obj->getPng();
		}
	}

	/**
	 * Return a PNG image representation of barcode (delegates to tc-lib-barcode; needs GD or Imagick).
	 * @param int $w Width of a single bar element in pixels.
	 * @param int $h Height of a single bar element in pixels.
	 * @param array $color RGB (0-255) foreground color for bar elements.
	 * @return string|Imagick|false image, or false on error.
	 * @public
	 */
	public function getBarcodePngData($w=2, $h=30, $color=array(0,0,0)) {
		$obj = $this->getRenderObject((int) round($this->barcode_array['maxw'] * $w), (int) round($h), $this->getCssRgbColor($color));
		if ($obj === null) {
			return false;
		}
		try {
			return $obj->getPngData(!function_exists('imagecreate'));
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Build a tc-lib-barcode model sized for rendering. Returns null when the
	 * underlying type is unknown or the library is unavailable.
	 * @param int $width
	 * @param int $height
	 * @param string $color
	 * @return \Com\Tecnick\Barcode\Model|null
	 */
	protected function getRenderObject($width, $height, $color) {
		return ($this->barcode_obj !== null)
			? $this->getTcLibBarcodeObject($width, $height, $color)
			: null;
	}

	/**
	 * Set the barcode.
	 * @param string $code code to print
 	 * @param string $type type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extension</li><li>EAN5 : 5-Digits UPC-Based Extension</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>IMBPRE: Pre-processed Intelligent Mail Barcode - Onecode - USPS-B-3200, using only F,A,D,T letters</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
 	 * @return void
 	 * @public
	 */
	public function setBarcode($code, $type) {
		$this->barcode_code = (string) $code;
		$this->barcode_type = strtoupper((string) $type);
		$this->barcode_obj = $this->getTcLibBarcodeObject();
		$this->barcode_array = ($this->barcode_obj !== null)
			? $this->convertTcLibBarcodeArray($this->barcode_obj)
			: array();
	}

}
