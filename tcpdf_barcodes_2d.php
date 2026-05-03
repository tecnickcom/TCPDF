<?php
//============================================================+
// File name   : tcpdf_barcodes_2d.php
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2026 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
// -------------------------------------------------------------------
//
// Description : PHP class to creates array representations for
//               2D barcodes to be used with TCPDF.
//
//============================================================+

/**
 * @file
 * PHP class to creates array representations for 2D barcodes to be used with TCPDF.
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 */

/**
 * @class TCPDF2DBarcode
 * PHP class to creates array representations for 2D barcodes to be used with TCPDF (https://tcpdf.org).
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 */
class TCPDF2DBarcode {

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
	 * Return an array representations for 2D barcodes:<ul>
	 * <li>$arrcode['code'] code to be printed on text label</li>
	 * <li>$arrcode['num_rows'] required number of rows</li>
	 * <li>$arrcode['num_cols'] required number of columns</li>
	 * <li>$arrcode['bcode'][$r][$c] value of the cell is $r row and $c column (0 = transparent, 1 = black)</li></ul>
	 * @param string $code code to print
 	 * @param string $type type of barcode: <ul><li>DATAMATRIX : Datamatrix (ISO/IEC 16022)</li><li>PDF417 : PDF417 (ISO/IEC 15438:2006)</li><li>PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6 : PDF417 with parameters: a = aspect ratio (width/height); e = error correction level (0-8); t = total number of macro segments; s = macro segment index (0-99998); f = file ID; o0 = File Name (text); o1 = Segment Count (numeric); o2 = Time Stamp (numeric); o3 = Sender (text); o4 = Addressee (text); o5 = File Size (numeric); o6 = Checksum (numeric). NOTES: Parameters t, s and f are required for a Macro Control Block, all other parameters are optional. To use a comma character ',' on text options, replace it with the character 255: "\xff".</li><li>QRCODE : QRcode Low error correction</li><li>QRCODE,L : QRcode Low error correction</li><li>QRCODE,M : QRcode Medium error correction</li><li>QRCODE,Q : QRcode Better error correction</li><li>QRCODE,H : QR-CODE Best error correction</li><li>RAW: raw mode - comma-separad list of array rows</li><li>RAW2: raw mode - array rows are surrounded by square parenthesis.</li><li>TEST : Test matrix</li></ul>
	 */
	public function __construct($code, $type) {
		$this->setBarcode($code, $type);
	}

	/**
	 * Return an array representations of barcode.
 	 * @return array
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
	 * Convert tc-lib grid output into the legacy TCPDF 2D barcode array structure.
	 * @param \Com\Tecnick\Barcode\Model $barcodeobj tc-lib barcode model.
	 * @return array
	 */
	protected function convertTcLibBarcodeArray($barcodeobj) {
		$data = $barcodeobj->getArray();
		$grid = $barcodeobj->getGridArray('0', '1');
		$legacy = array(
			'num_rows' => $data['nrows'],
			'num_cols' => $data['ncols'],
			'bcode' => array(),
			'code' => ($data['extcode'] !== '') ? $data['extcode'] : $data['code'],
		);
		foreach ($grid as $row) {
			$legacy['bcode'][] = array_map('intval', $row);
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
	 * @param int $w Width of a single rectangle element in user units.
	 * @param int $h Height of a single rectangle element in user units.
	 * @param string $color Foreground color (in SVG format) for bar elements.
 	 * @public
	 */
	public function getBarcodeSVG($w=3, $h=3, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['num_cols'] * $w), (int) round($this->barcode_array['num_rows'] * $h), (string) $color);
		if ($obj !== null) {
			$obj->getSvg();
		}
	}

	/**
	 * Return a SVG string representation of barcode (delegates to tc-lib-barcode).
	 * @param int $w Width of a single rectangle element in user units.
	 * @param int $h Height of a single rectangle element in user units.
	 * @param string $color Foreground color (in SVG format) for bar elements.
	 * @return string SVG code (empty when the barcode could not be created).
 	 * @public
	 */
	public function getBarcodeSVGcode($w=3, $h=3, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['num_cols'] * $w), (int) round($this->barcode_array['num_rows'] * $h), (string) $color);
		return ($obj !== null) ? $obj->getSvgCode() : '';
	}

	/**
	 * Return an HTML representation of barcode (delegates to tc-lib-barcode).
	 * @param int $w Width of a single rectangle element in pixels.
	 * @param int $h Height of a single rectangle element in pixels.
	 * @param string $color Foreground color for bar elements.
	 * @return string HTML code (empty when the barcode could not be created).
 	 * @public
	 */
	public function getBarcodeHTML($w=10, $h=10, $color='black') {
		$obj = $this->getRenderObject((int) round($this->barcode_array['num_cols'] * $w), (int) round($this->barcode_array['num_rows'] * $h), (string) $color);
		return ($obj !== null) ? $obj->getHtmlDiv() : '';
	}

	/**
	 * Send a PNG image representation of barcode (delegates to tc-lib-barcode; needs GD or Imagick).
	 * @param int $w Width of a single rectangle element in pixels.
	 * @param int $h Height of a single rectangle element in pixels.
	 * @param array $color RGB (0-255) foreground color for bar elements (background is transparent).
 	 * @public
	 */
	public function getBarcodePNG($w=3, $h=3, $color=array(0,0,0)) {
		$obj = $this->getRenderObject((int) round($this->barcode_array['num_cols'] * $w), (int) round($this->barcode_array['num_rows'] * $h), $this->getCssRgbColor($color));
		if ($obj !== null) {
			$obj->getPng();
		}
	}

	/**
	 * Return a PNG image representation of barcode (delegates to tc-lib-barcode; needs GD or Imagick).
	 * @param int $w Width of a single rectangle element in pixels.
	 * @param int $h Height of a single rectangle element in pixels.
	 * @param array $color RGB (0-255) foreground color for bar elements (background is transparent).
	 * @return string|Imagick|false image or false in case of error.
 	 * @public
	 */
	public function getBarcodePngData($w=3, $h=3, $color=array(0,0,0)) {
		$obj = $this->getRenderObject((int) round($this->barcode_array['num_cols'] * $w), (int) round($this->barcode_array['num_rows'] * $h), $this->getCssRgbColor($color));
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
	 * Build a tc-lib-barcode model sized for rendering.
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
 	 * @param string $type type of barcode: <ul><li>DATAMATRIX : Datamatrix (ISO/IEC 16022)</li><li>PDF417 : PDF417 (ISO/IEC 15438:2006)</li><li>PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6 : PDF417 with parameters: a = aspect ratio (width/height); e = error correction level (0-8); t = total number of macro segments; s = macro segment index (0-99998); f = file ID; o0 = File Name (text); o1 = Segment Count (numeric); o2 = Time Stamp (numeric); o3 = Sender (text); o4 = Addressee (text); o5 = File Size (numeric); o6 = Checksum (numeric). NOTES: Parameters t, s and f are required for a Macro Control Block, all other parameters are optional. To use a comma character ',' on text options, replace it with the character 255: "\xff".</li><li>QRCODE : QRcode Low error correction</li><li>QRCODE,L : QRcode Low error correction</li><li>QRCODE,M : QRcode Medium error correction</li><li>QRCODE,Q : QRcode Better error correction</li><li>QRCODE,H : QR-CODE Best error correction</li><li>RAW: raw mode - comma-separad list of array rows</li><li>RAW2: raw mode - array rows are surrounded by square parenthesis.</li><li>TEST : Test matrix</li></ul>
 	 * @return void
	 */
	public function setBarcode($code, $type) {
		$this->barcode_code = (string) $code;
		$this->barcode_type = strtoupper((string) $type);
		$this->barcode_obj = null;
		$mode = explode(',', $type);
		$qrtype = strtoupper($mode[0]);
		switch ($qrtype) {
			case 'RAW':
			case 'RAW2': { // RAW MODE
				// remove spaces
				$code = preg_replace('/[\s]*/si', '', $code);
				if (strlen($code) < 3) {
					break;
				}
				if ($qrtype == 'RAW') {
					// comma-separated rows
					$rows = explode(',', $code);
				} else { // RAW2
					// rows enclosed in square parentheses
					$code = substr($code, 1, -1);
					$rows = explode('][', $code);
				}
				$this->barcode_array['num_rows'] = count($rows);
				$this->barcode_array['num_cols'] = strlen($rows[0]);
				$this->barcode_array['bcode'] = array();
				foreach ($rows as $r) {
					$this->barcode_array['bcode'][] = str_split($r, 1);
				}
				$this->barcode_array['code'] = $code;
				break;
			}
			case 'TEST': { // TEST MODE
				$this->barcode_array['num_rows'] = 5;
				$this->barcode_array['num_cols'] = 15;
				$this->barcode_array['bcode'] = array(
					array(1,1,1,0,1,1,1,0,1,1,1,0,1,1,1),
					array(0,1,0,0,1,0,0,0,1,0,0,0,0,1,0),
					array(0,1,0,0,1,1,0,0,1,1,1,0,0,1,0),
					array(0,1,0,0,1,0,0,0,0,0,1,0,0,1,0),
					array(0,1,0,0,1,1,1,0,1,1,1,0,0,1,0));
				$this->barcode_array['code'] = $code;
				break;
			}
			default: {
				$this->barcode_obj = $this->getTcLibBarcodeObject();
				$this->barcode_array = ($this->barcode_obj !== null)
					? $this->convertTcLibBarcodeArray($this->barcode_obj)
					: array();
			}
		}
	}
}
