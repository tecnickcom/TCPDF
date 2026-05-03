<?php
//============================================================+
// File name   : tcpdf_fonts.php
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2026 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
// -------------------------------------------------------------------
//
// Description :Font methods for TCPDF library.
//
//============================================================+

/**
 * @file
 * Unicode data and font methods for TCPDF library.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
 */

/**
 * @class TCPDF_FONTS
 * Font methods for TCPDF library.
 * @package com.tecnick.tcpdf
 * @version 1.1.1
 * @author Nicola Asuni - info@tecnick.com
 */
class TCPDF_FONTS {

	/**
	 * Static cache used for speed up uniord performances
	 * @protected
	 */
	protected static $cache_uniord = array();

	/**
	 * Static cache of recursively discovered font files by search-path set.
	 * @var array<string,array<string,string>>
	 * @protected
	 */
	protected static $cache_fontfiles = array();

	/**
	 * Convert and add the selected TrueType or Type1 font to a writable output path.
	 *
	 * Delegates to \Com\Tecnick\Pdf\Font\Import (tc-lib-pdf-font).
	 * The upstream importer writes a JSON font definition file (.json) instead of the
	 * legacy PHP font definition file (.php). TCPDF automatically tries .json after .php
	 * so both formats are supported at load time.
	 *
	 * @param string $fontfile Font file (full path).
	 * @param string $fonttype Font type. Leave empty for autodetect mode. Valid values are:
	 *               TrueTypeUnicode, TrueType, Type1, CID0JP, CID0KR, CID0CS, CID0CT.
	 * @param string $enc Name of the encoding table to use. Leave empty for default mode.
	 * @param int $flags Unsigned 32-bit integer with font descriptor flags (PDF32000:2008 - 9.8.2).
	 * @param string $outpath Output path for generated font files. Leave empty to use K_PATH_FONTS.
	 * @param int $platid Platform ID for CMAP table (3 = Windows, 1 = Mac).
	 * @param int $encid Encoding ID for CMAP table (1 = Unicode on Windows).
	 * @param boolean $addcbbox Ignored. Character bounding box data is always included by the upstream importer.
	 * @param boolean $link If true, link to system font instead of copying font data (not portable).
	 * @return string|false TCPDF font name or false on error.
	 * @since 5.9.123 (2010-09-30)
	 * @public static
	 */
	public static function addTTFfont($fontfile, $fonttype='', $enc='', $flags=32, $outpath='', $platid=3, $encid=1, $addcbbox=false, $link=false) {
		if (!TCPDF_STATIC::file_exists($fontfile)) {
			return false;
		}
		if (empty($outpath)) {
			$outpath = self::_getfontpath();
		}
		try {
			$import = new \Com\Tecnick\Pdf\Font\Import(
				$fontfile,
				$outpath,
				$fonttype,
				$enc,
				$flags,
				$platid,
				$encid,
				$link
			);
			return $import->getFontName();
		} catch (\Com\Tecnick\Pdf\Font\Exception $e) {
			$msg = $e->getMessage();
			// Import throws when the .json file already exists:
			// "this font has been already imported: /path/to/fontname.json"
			if (strpos($msg, 'already imported:') !== false) {
				$parts = explode('already imported:', $msg, 2);
				return basename(trim($parts[1]), '.json');
			}
			return false;
		}
	}

	/**
	 * Returns a subset of the TrueType font data without the unused glyphs.
	 * Delegates to upstream \Com\Tecnick\Pdf\Font\Subset.
	 * @param string $font TrueType font data.
	 * @param array $subsetchars Array of used characters (the glyphs to keep).
	 * @param array $fontdata Optional TCPDF font metadata used to build upstream fdt input.
	 * @return string A subset of TrueType font data without the unused glyphs.
	 * @since 5.2.000 (2010-06-02)
	 * @public static
	 */
	public static function _getTrueTypeFontSubset($font, $subsetchars, $fontdata=array()) {
		// Normalise to the upstream expected shape: [codepoint => true]
		$subchars = array();
		foreach (array_keys($subsetchars) as $cid) {
			$subchars[(int) $cid] = true;
		}
		ksort($subchars);

		$fdt = array(
			'desc' => isset($fontdata['desc']) && is_array($fontdata['desc']) ? $fontdata['desc'] : array(),
			'type' => $fontdata['type'] ?? 'TrueTypeUnicode',
			'platform_id' => $fontdata['platform_id'] ?? 3,
			'encoding_id' => $fontdata['encoding_id'] ?? 1,
			'linked' => false,
			'input_file' => '',
			'dir' => '',
			'file_name' => '',
		);
		if (!isset($fdt['desc']['Flags'])) {
			$fdt['desc']['Flags'] = 0;
		}
		if (!isset($fdt['desc']['MaxWidth']) || ($fdt['desc']['MaxWidth'] <= 0)) {
			// Force subsetting mode in Import\TrueType::setFontFile() to avoid file I/O.
			$fdt['desc']['MaxWidth'] = 1;
		}
		if (isset($fontdata['enc']) && ($fontdata['enc'] === 'Identity-H')) {
			$fdt['platform_id'] = 3;
			$fdt['encoding_id'] = 1;
		}

		try {
			$subset = new \Com\Tecnick\Pdf\Font\Subset($font, $fdt, $subchars);
			return $subset->getSubsetFont();
		} catch (\Throwable $e) {
			return $font;
		}
	}

	/**
	 * Outputs font widths as a PDF /W array string.
	 * Retained locally: the upstream equivalent \Com\Tecnick\Pdf\Font\OutUtil::getCharWidths()
	 * is a protected method in an abstract class hierarchy (OutUtil -> OutFont -> Output)
	 * and cannot be called as a standalone static function.
	 * @param array $font font data
	 * @param int $cidoffset offset for CID values
	 * @return string PDF command string for font widths
	 * @since 4.4.000 (2008-12-07)
	 * @public static
	 */
	public static function _putfontwidths($font, $cidoffset=0) {
		ksort($font['cw']);
		$rangeid = 0;
		$range = array();
		$prevcid = -2;
		$prevwidth = -1;
		$interval = false;
		// for each character
		foreach ($font['cw'] as $cid => $width) {
			$cid -= $cidoffset;
			if ($font['subset'] AND (!isset($font['subsetchars'][$cid]))) {
				// ignore the unused characters (font subsetting)
				continue;
			}
			if ($width != $font['dw']) {
				if ($cid == ($prevcid + 1)) {
					// consecutive CID
					if ($width == $prevwidth) {
						if ($width == $range[$rangeid][0]) {
							$range[$rangeid][] = $width;
						} else {
							array_pop($range[$rangeid]);
							// new range
							$rangeid = $prevcid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $prevwidth;
							$range[$rangeid][] = $width;
						}
						$interval = true;
						$range[$rangeid]['interval'] = true;
					} else {
						if ($interval) {
							// new range
							$rangeid = $cid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $width;
						} else {
							$range[$rangeid][] = $width;
						}
						$interval = false;
					}
				} else {
					// new range
					$rangeid = $cid;
					$range[$rangeid] = array();
					$range[$rangeid][] = $width;
					$interval = false;
				}
				$prevcid = $cid;
				$prevwidth = $width;
			}
		}
		// optimize ranges
		$prevk = -1;
		$nextk = -1;
		$prevint = false;
		foreach ($range as $k => $ws) {
			$cws = count($ws);
			if (($k == $nextk) AND (!$prevint) AND ((!isset($ws['interval'])) OR ($cws < 4))) {
				if (isset($range[$k]['interval'])) {
					unset($range[$k]['interval']);
				}
				$range[$prevk] = array_merge($range[$prevk], $range[$k]);
				unset($range[$k]);
			} else {
				$prevk = $k;
			}
			$nextk = $k + $cws;
			if (isset($ws['interval'])) {
				if ($cws > 3) {
					$prevint = true;
				} else {
					$prevint = false;
				}
				if (isset($range[$k]['interval'])) {
					unset($range[$k]['interval']);
				}
				--$nextk;
			} else {
				$prevint = false;
			}
		}
		// output data
		$w = '';
		foreach ($range as $k => $ws) {
			if (count(array_count_values($ws)) == 1) {
				// interval mode is more compact
				$w .= ' '.$k.' '.($k + count($ws) - 1).' '.$ws[0];
			} else {
				// range mode
				$w .= ' '.$k.' [ '.implode(' ', $ws).' ]';
			}
		}
		return '/W ['.$w.' ]';
	}




	/**
	 * Update the CIDToGIDMap string with a new value.
	 * @deprecated No longer called internally (was used only by the old addTTFfont() implementation
	 *             which has been replaced by \Com\Tecnick\Pdf\Font\Import delegation).
	 *             Kept as public static for external BC.
	 * @param string $map CIDToGIDMap.
	 * @param int $cid CID value.
	 * @param int $gid GID value.
	 * @return string CIDToGIDMap.
	 * @since 5.9.123 (2011-09-29)
	 * @public static
	 */
	public static function updateCIDtoGIDmap($map, $cid, $gid) {
		if (($cid >= 0) AND ($cid <= 0xFFFF) AND ($gid >= 0)) {
			if ($gid > 0xFFFF) {
				$gid -= 0x10000;
			}
			$map[($cid * 2)] = chr($gid >> 8);
			$map[(($cid * 2) + 1)] = chr($gid & 0xFF);
		}
		return $map;
	}

	/**
	 * Return ordered font search paths.
	 *
	 * Order of precedence:
	 * 1. Explicit K_PATH_FONTS
	 * 2. Composer tc-lib-pdf-font assets
	 *
	 * @return array<int,string>
	 * @public static
	 */
	public static function getFontSearchPaths() {
		$paths = array();
		if (defined('K_PATH_FONTS') AND !TCPDF_STATIC::empty_string(K_PATH_FONTS)) {
			$paths[] = K_PATH_FONTS;
		}
		$tc_lib_font_path = dirname(__FILE__).'/../vendor/tecnickcom/tc-lib-pdf-font/target/fonts/';
		if (@is_dir($tc_lib_font_path)) {
			$paths[] = $tc_lib_font_path;
		}
		$out = array();
		foreach ($paths as $path) {
			if (substr($path, -1) != '/') {
				$path .= '/';
			}
			if (!in_array($path, $out, true)) {
				$out[] = $path;
			}
		}
		return $out;
	}


	/**
	 * Return fonts path
	 * @return string
	 * @public static
	 */
	public static function _getfontpath() {
		if (!defined('K_PATH_FONTS')) {
			$search_paths = self::getFontSearchPaths();
			if (!empty($search_paths)) {
				define('K_PATH_FONTS', $search_paths[0]);
			}
		}
		if (defined('K_PATH_FONTS')) {
			return K_PATH_FONTS;
		}
		$search_paths = self::getFontSearchPaths();
		return $search_paths[0] ?? '';
	}



	/**
	 * Return font full path
	 * @param string $file Font file name.
	 * @param string $fontdir Font directory (set to false fto search on default directories)
	 * @return string Font full path or empty string
	 * @author Nicola Asuni
	 * @since 6.0.025
	 * @public static
	 */
	public static function getFontFullPath($file, $fontdir=false) {
		$fontfile = '';
		// search files on various directories
		if (($fontdir !== false) AND @TCPDF_STATIC::file_exists($fontdir.$file)) {
			$fontfile = $fontdir.$file;
		} else {
			$fontpaths = self::getFontSearchPaths();
			foreach ($fontpaths as $fontpath) {
				if (@TCPDF_STATIC::file_exists($fontpath.$file)) {
					$fontfile = $fontpath.$file;
					break;
				}
			}
			if (TCPDF_STATIC::empty_string($fontfile)) {
				$cachekey = implode('|', $fontpaths);
				if (!isset(self::$cache_fontfiles[$cachekey])) {
					$fontfiles = array();
					foreach ($fontpaths as $fontpath) {
						if (!@is_dir($fontpath)) {
							continue;
						}
						try {
							$iterator = new RecursiveIteratorIterator(
								new RecursiveDirectoryIterator($fontpath, FilesystemIterator::SKIP_DOTS)
							);
							foreach ($iterator as $fileinfo) {
								if (!$fileinfo->isFile()) {
									continue;
								}
								$filename = strtolower($fileinfo->getFilename());
								if (!isset($fontfiles[$filename])) {
									$fontfiles[$filename] = $fileinfo->getPathname();
								}
							}
						} catch (Exception $e) {
							if (($fontsdir = @opendir($fontpath)) !== false) {
								while (($file = readdir($fontsdir)) !== false) {
									$filepath = $fontpath.$file;
									if (@is_file($filepath)) {
										$filename = strtolower($file);
										if (!isset($fontfiles[$filename])) {
											$fontfiles[$filename] = $filepath;
										}
									}
								}
								closedir($fontsdir);
							}
						}
					}
					self::$cache_fontfiles[$cachekey] = $fontfiles;
				}
				$fontindex = self::$cache_fontfiles[$cachekey];
				$lookfor = strtolower(basename($file));
				if (isset($fontindex[$lookfor])) {
					$fontfile = $fontindex[$lookfor];
				}
			}
		}
		if (TCPDF_STATIC::empty_string($fontfile) AND @TCPDF_STATIC::file_exists($file)) {
			$fontfile = $file;
		}
		return $fontfile;
	}




	/**
	 * Get a reference font size.
	 * @param string $size String containing font size value.
	 * @param float $refsize Reference font size in points.
	 * @return float value in points
	 * @public static
	 */
	public static function getFontRefSize($size, $refsize=12) {
		switch ($size) {
			case 'xx-small': {
				$size = ($refsize - 4);
				break;
			}
			case 'x-small': {
				$size = ($refsize - 3);
				break;
			}
			case 'small': {
				$size = ($refsize - 2);
				break;
			}
			case 'medium': {
				$size = $refsize;
				break;
			}
			case 'large': {
				$size = ($refsize + 2);
				break;
			}
			case 'x-large': {
				$size = ($refsize + 4);
				break;
			}
			case 'xx-large': {
				$size = ($refsize + 6);
				break;
			}
			case 'smaller': {
				$size = ($refsize - 3);
				break;
			}
			case 'larger': {
				$size = ($refsize + 3);
				break;
			}
		}
		return $size;
	}


// ====================================================================================================================
// REIMPLEMENTED
// ====================================================================================================================

	/**
	 * Returns the unicode caracter specified by the value
	 * @param int $c UTF-8 value
	 * @param boolean $unicode True if we are in unicode mode, false otherwise.
	 * @return string Returns the specified character.
	 * @since 2.3.000 (2008-03-05)
	 * @public static
	 */
	public static function unichr($c, $unicode=true) {
		$c = intval($c);
		if (!$unicode) {
			return chr($c & 0xFF);
		}
		// Delegate to mb_chr (PHP 7.2+, canonical UTF-8 encoding)
		return (string) @\mb_chr($c, 'UTF-8');
	}

	/**
	 * Returns the unicode caracter specified by UTF-8 value
	 * @param int $c UTF-8 value
	 * @return string Returns the specified character.
	 * @public static
	 */
	public static function unichrUnicode($c) {
		return self::unichr($c, true);
	}

	/**
	 * Returns the unicode caracter specified by ASCII value
	 * @param int $c UTF-8 value
	 * @return string Returns the specified character.
	 * @public static
	 */
	public static function unichrASCII($c) {
		return self::unichr($c, false);
	}

	/**
	 * Converts array of UTF-8 characters to UTF16-BE string.<br>
	 * Based on: http://www.faqs.org/rfcs/rfc2781.html
	 * <pre>
	 *   Encoding UTF-16:
	 *
	 *   Encoding of a single character from an ISO 10646 character value to
	 *    UTF-16 proceeds as follows. Let U be the character number, no greater
	 *    than 0x10FFFF.
	 *
	 *    1) If U < 0x10000, encode U as a 16-bit unsigned integer and
	 *       terminate.
	 *
	 *    2) Let U' = U - 0x10000. Because U is less than or equal to 0x10FFFF,
	 *       U' must be less than or equal to 0xFFFFF. That is, U' can be
	 *       represented in 20 bits.
	 *
	 *    3) Initialize two 16-bit unsigned integers, W1 and W2, to 0xD800 and
	 *       0xDC00, respectively. These integers each have 10 bits free to
	 *       encode the character value, for a total of 20 bits.
	 *
	 *    4) Assign the 10 high-order bits of the 20-bit U' to the 10 low-order
	 *       bits of W1 and the 10 low-order bits of U' to the 10 low-order
	 *       bits of W2. Terminate.
	 *
	 *    Graphically, steps 2 through 4 look like:
	 *    U' = yyyyyyyyyyxxxxxxxxxx
	 *    W1 = 110110yyyyyyyyyy
	 *    W2 = 110111xxxxxxxxxx
	 * </pre>
	 * @param array $unicode array containing UTF-8 unicode values
	 * @param boolean $setbom if true set the Byte Order Mark (BOM = 0xFEFF)
	 * @return string
	 * @protected
	 * @author Nicola Asuni
	 * @since 2.1.000 (2008-01-08)
	 * @public static
	 */
	public static function arrUTF8ToUTF16BE($unicode, $setbom=false) {
		$outstr = ''; // string to be returned
		if ($setbom) {
			$outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
		}
		foreach ($unicode as $char) {
			if ($char == 0x200b) {
				// skip Unicode Character 'ZERO WIDTH SPACE' (DEC:8203, U+200B)
			} elseif ($char == 0xFFFD) {
				$outstr .= "\xFF\xFD"; // replacement character
			} elseif ($char < 0x10000) {
				$outstr .= chr($char >> 0x08);
				$outstr .= chr($char & 0xFF);
			} else {
				$char -= 0x10000;
				$w1 = 0xD800 | ($char >> 0x0a);
				$w2 = 0xDC00 | ($char & 0x3FF);
				$outstr .= chr($w1 >> 0x08);
				$outstr .= chr($w1 & 0xFF);
				$outstr .= chr($w2 >> 0x08);
				$outstr .= chr($w2 & 0xFF);
			}
		}
		return $outstr;
	}

	/**
	 * Convert an array of UTF8 values to array of unicode characters
	 * @param array $ta The input array of UTF8 values.
	 * @param boolean $isunicode True for Unicode mode, false otherwise.
	 * @return array Return array of unicode characters
	 * @since 4.5.037 (2009-04-07)
	 * @public static
	 */
	public static function UTF8ArrayToUniArray($ta, $isunicode=true) {
		if ($isunicode) {
			return array_map(static::class.'::unichrUnicode', $ta);
		}
		return array_map(static::class.'::unichrASCII', $ta);
	}

	/**
	 * Extract a slice of the $strarr array and return it as string.
	 * @param string[] $strarr The input array of characters.
	 * @param int $start the starting element of $strarr.
	 * @param int $end first element that will not be returned.
	 * @param boolean $unicode True if we are in unicode mode, false otherwise.
	 * @return string Return part of a string
	 * @public static
	 */
	public static function UTF8ArrSubString($strarr, $start='', $end='', $unicode=true) {
		if (strlen($start) == 0) {
			$start = 0;
		}
		if (strlen($end) == 0) {
			$end = count($strarr);
		}
		$string = '';
		for ($i = $start; $i < $end; ++$i) {
			$string .= self::unichr($strarr[$i], $unicode);
		}
		return $string;
	}

	/**
	 * Extract a slice of the $uniarr array and return it as string.
	 * @param string[] $uniarr The input array of characters.
	 * @param int $start the starting element of $strarr.
	 * @param int $end first element that will not be returned.
	 * @return string Return part of a string
	 * @since 4.5.037 (2009-04-07)
	 * @public static
	 */
	public static function UniArrSubString($uniarr, $start='', $end='') {
		if (strlen($start) == 0) {
			$start = 0;
		}
		if (strlen($end) == 0) {
			$end = count($uniarr);
		}
		$string = '';
		for ($i=$start; $i < $end; ++$i) {
			$string .= $uniarr[$i];
		}
		return $string;
	}

	/**
	 * Converts UTF-8 characters array to array of Latin1 characters array<br>
	 * @param array $unicode array containing UTF-8 unicode values
	 * @return array
	 * @author Nicola Asuni
	 * @since 4.8.023 (2010-01-15)
	 * @public static
	 */
	public static function UTF8ArrToLatin1Arr($unicode) {
		$outarr = array(); // array to be returned
		foreach ($unicode as $char) {
			if ($char < 256) {
				$outarr[] = $char;
			} elseif (array_key_exists($char, \Com\Tecnick\Unicode\Data\Latin::SUBSTITUTE)) {
				// map from UTF-8
				$outarr[] = \Com\Tecnick\Unicode\Data\Latin::SUBSTITUTE[$char];
			} elseif ($char == 0xFFFD) {
				// skip
			} else {
				$outarr[] = 63; // '?' character
			}
		}
		return $outarr;
	}

	/**
	 * Converts UTF-8 characters array to Latin1 string<br>
	 * @param array $unicode array containing UTF-8 unicode values
	 * @return string
	 * @author Nicola Asuni
	 * @since 4.8.023 (2010-01-15)
	 * @public static
	 */
	public static function UTF8ArrToLatin1($unicode) {
		$outstr = ''; // string to be returned
		foreach ($unicode as $char) {
			if ($char < 256) {
				$outstr .= chr($char);
			} elseif (array_key_exists($char, \Com\Tecnick\Unicode\Data\Latin::SUBSTITUTE)) {
				// map from UTF-8
				$outstr .= chr(\Com\Tecnick\Unicode\Data\Latin::SUBSTITUTE[$char]);
			} elseif ($char == 0xFFFD) {
				// skip
			} else {
				$outstr .= '?';
			}
		}
		return $outstr;
	}

	/**
	 * Converts UTF-8 character to integer value.<br>
	 * Uses the getUniord() method if the value is not cached.
	 * @param string $uch character string to process.
	 * @return int Unicode value
	 * @public static
	 */
	public static function uniord($uch) {
		if (!isset(self::$cache_uniord[$uch])) {
			// Delegate to mb_ord (PHP 7.2+, canonical codepoint extraction)
			$v = @\mb_ord((string) $uch, 'UTF-8');
			self::$cache_uniord[$uch] = ($v !== false && $v >= 0) ? $v : 0xFFFD;
		}
		return self::$cache_uniord[$uch];
	}


	/**
	 * Converts UTF-8 strings to codepoints array.<br>
	 * Invalid byte sequences will be replaced with 0xFFFD (replacement character)<br>
	 * @param string $str string to process.
	 * @param boolean $isunicode True when the documetn is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return array containing codepoints (UTF-8 characters values)
	 * @author Nicola Asuni
	 * @public static
	 */
	public static function UTF8StringToArray($str, $isunicode, &$currentfont) {
		$str = is_null($str) ? '' : (string) $str;
		if ($isunicode) {
			// Delegate to mb_str_split (PHP 7.4+, canonical UTF-8 split)
			$chars = \mb_str_split($str) ?: [];
			$carr = array_map(static::class.'::uniord', $chars);
		} else {
			$chars = str_split($str) ?: [];
			$carr = array_map('ord', $chars);
		}
		if (is_array($currentfont['subsetchars']) && is_array($carr)) {
			$currentfont['subsetchars'] += array_fill_keys($carr, true);
		} else {
			$currentfont['subsetchars'] = array_merge($currentfont['subsetchars'], $carr);
		}
		return $carr;
	}

	/**
	 * Converts UTF-8 strings to Latin1 when using the standard 14 core fonts.<br>
	 * @param string $str string to process.
	 * @param boolean $isunicode True when the documetn is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return string
	 * @since 3.2.000 (2008-06-23)
	 * @public static
	 */
	public static function UTF8ToLatin1($str, $isunicode, &$currentfont) {
		$unicode = self::UTF8StringToArray($str, $isunicode, $currentfont); // array containing UTF-8 unicode values
		return self::UTF8ArrToLatin1($unicode);
	}

	/**
	 * Converts UTF-8 strings to UTF16-BE.<br>
	 * @param string $str string to process.
	 * @param boolean $setbom if true set the Byte Order Mark (BOM = 0xFEFF)
	 * @param boolean $isunicode True when the documetn is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return string
	 * @author Nicola Asuni
	 * @since 1.53.0.TC005 (2005-01-05)
	 * @public static
	 */
	public static function UTF8ToUTF16BE($str, $setbom, $isunicode, &$currentfont) {
		if (!$isunicode) {
			return $str; // string is not in unicode
		}
		$unicode = self::UTF8StringToArray($str, $isunicode, $currentfont); // array containing UTF-8 unicode values
		return self::arrUTF8ToUTF16BE($unicode, $setbom);
	}

	/**
	 * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
	 * @param string $str string to manipulate.
	 * @param bool $setbom if true set the Byte Order Mark (BOM = 0xFEFF)
	 * @param bool $forcertl if true forces RTL text direction
	 * @param boolean $isunicode True if the document is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return string
	 * @author Nicola Asuni
	 * @since 2.1.000 (2008-01-08)
	 * @public static
	 */
	public static function utf8StrRev($str, $setbom, $forcertl, $isunicode, &$currentfont) {
		return self::utf8StrArrRev(self::UTF8StringToArray($str, $isunicode, $currentfont), $str, $setbom, $forcertl, $isunicode, $currentfont);
	}

	/**
	 * Reverse the RLT substrings array using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
	 * @param array $arr array of unicode values.
	 * @param string $str string to manipulate (or empty value).
	 * @param bool $setbom if true set the Byte Order Mark (BOM = 0xFEFF)
	 * @param bool $forcertl if true forces RTL text direction
	 * @param boolean $isunicode True if the document is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return string
	 * @author Nicola Asuni
	 * @since 4.9.000 (2010-03-27)
	 * @public static
	 */
	public static function utf8StrArrRev($arr, $str, $setbom, $forcertl, $isunicode, &$currentfont) {
		return self::arrUTF8ToUTF16BE(self::utf8Bidi($arr, $str, $forcertl, $isunicode, $currentfont), $setbom);
	}

	/**
	 * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
	 * @param array $ta array of characters composing the string.
	 * @param string $str string to process
	 * @param bool $forcertl if 'R' forces RTL, if 'L' forces LTR
	 * @param boolean $isunicode True if the document is in Unicode mode, false otherwise.
	 * @param array $currentfont Reference to current font array.
	 * @return array of unicode chars
	 * @author Nicola Asuni
	 * @since 2.4.000 (2008-03-06)
	 * @public static
	 */
	public static function utf8Bidi($ta, $str, $forcertl, $isunicode, &$currentfont) {
		if (empty($ta)) {
			return $ta;
		}
		if (TCPDF_STATIC::empty_string($str)) {
			$str = self::UTF8ArrSubString($ta, '', '', $isunicode);
		}
		// Early-exit when no Bidi processing is required
		$hasArabic = ($str !== '' && @preg_match(\Com\Tecnick\Unicode\Data\Pattern::ARABIC, $str) === 1);
		$hasRtl    = ($str !== '' && @preg_match(\Com\Tecnick\Unicode\Data\Pattern::RTL, $str) === 1);
		$forced    = ($forcertl !== false && $forcertl !== '' && $forcertl !== null);
		if (!$forced && !$hasArabic && !$hasRtl) {
			return $ta;
		}
		$forcedir = '';
		if (is_string($forcertl) && $forcertl !== '') {
			$forcedir = strtoupper(substr($forcertl, 0, 1));
		} elseif ($forcertl === true) {
			$forcedir = 'R';
		}
		try {
			$bidi   = new \Com\Tecnick\Unicode\Bidi($str, null, $ta, $forcedir, true);
			$result = $bidi->getOrdArray();
			// Track any Arabic-substituted codepoints added by shaping
			if (isset($currentfont['subsetchars']) && is_array($currentfont['subsetchars'])) {
				$currentfont['subsetchars'] += array_fill_keys($result, true);
			}
			return $result;
		} catch (\Throwable $e) {
			return $ta;
		}
	}

}
