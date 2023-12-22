<?php
/*
// File name   : tcpdf_cmssignature.php
// Version     : 1.0.1
// Begin       : 2023-05-25
// Last Update : 2023-05-25
// Author      : Hida - https://github.com/hidasw
// License     : GNU GPLv3
*/
/**
 * @class tcpdf_cms_signature
 * Manage CMS Signature for TCPDF.
 * @version 1.0.001
 * @author M Hida
 */
class tcpdf_cms_signature {
	/**
	 * Catch error.
	 * @public
	 */
	public $errorMsg;
	
	/**
	 * result value of pkcs7 EncryptedDigest.
	 * @public
	 */
	public string $pkcs7_EncryptedDigest;
	
	/**
	 * private array parsed of pkcs7 data.
	 * @private
	 */
	private array $pkcs7_dataArray;

	/**
	 * futher implementation
	 * @public
	 */
	public function __construct() {

	}

	/**
	 * Create common tsa query with SHA1 digest, nonce and cert req extension
	 * @param string $binaryData raw/binary data of tsa query
	 * @return string binary tsa query
	 * @public
	 */
	public function tsa_query($binaryData) {
	$hash = hash('sha1', $binaryData);
	$tsReqData = tcpdf_asn1::seq(
															tcpdf_asn1::int(1).
															tcpdf_asn1::seq(
																							tcpdf_asn1::seq("06052B0E03021A"."0500"). // object OBJ_sha1
																							tcpdf_asn1::oct($hash)).
																							tcpdf_asn1::int(hash('crc32', rand())). // tsa nonce
																							'0101ff' // req return cert
															);
	return hex2bin($tsReqData);
	}

	/**
	 * function to get response header of specific name
	 * @param string $headerkeyName header name eg Content-Type
	 * @param string $headerData text of header
	 * @return string value of header name
	 * @protected
	 */
	protected function getResponseHeader($headerkeyName, $headerData) {
		$headers = explode("\n", $headerData);
		foreach ($headers as $key => $r) {
			// Match the header name up to ':', compare lower case
			if (stripos($r, $headerkeyName . ':') === 0) {
				list($headername, $headervalue) = explode(":", $r, 2);
				return trim($headervalue);
			}
		}
	}

	/**
	 * send tsa query with curl
	 * @param string $binarytsReqData binary ts query
	 * @param string $tsa_host='', $tsa_username='', $tsa_password=''
	 * @return string tsa response
	 * @public
	 */
	public function tsa_send($binarytsReqData, $tsa_host='', $tsa_username='', $tsa_password='') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $tsa_host);
		if(isset($tsa_username) && isset($tsa_password)) {
			curl_setopt($ch, CURLOPT_USERPWD, $tsa_username . ":" . $tsa_password);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query','User-Agent: TCPDF'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $binarytsReqData);
		$tsResponse = curl_exec($ch);
		curl_close($ch);

		if($tsResponse) {
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($tsResponse, 0, $header_size);
			$body = substr($tsResponse, $header_size);
			
			// Get the HTTP response code
			$headers = explode("\n", $header);
			foreach ($headers as $key => $r) {
				if (stripos($r, 'HTTP/') === 0) {
					list(,$code, $status) = explode(' ', $r, 3);
					break;
				}
			}
			if($code != '200') {
				 $this->errorMsg = "tsa Response error! Code: $code, Status: $status.";
				return false;
			}
			$contentTypeHeader = $this->getResponseHeader("Content-Type", $header);
			if($contentTypeHeader != 'application/timestamp-reply') {
				$this->errorMsg = "tsa response content type not application/timestamp-reply, but: $contentTypeHeader.";
				return false;
			}
			return $body; // binary response
		}
	}

	/**
	 * parse tsa response to array
	 * @param string $binaryTsaRespData binary tsa response
	 * @return array asn.1 hex structure of tsa response
	 * @private
	 */
	private function tsa_parseResp($binaryTsaRespData) {
		$tcpdf_asn1 = new tcpdf_asn1;
		if(!@$ar = $tcpdf_asn1->parse_recursive(bin2hex($binaryTsaRespData), 3)) {
			$this->errorMsg = "can't parse invalid tsa Response.";
			return false;
		}

		$curr = $ar;
		foreach($curr as $key=>$value) {
			if($value['type'] == '30') {
				$curr['TimeStampResp']=$curr[$key];
				unset($curr[$key]);
				
			}
		}
		$ar=$curr;

		$curr = $ar['TimeStampResp'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '30' && !array_key_exists('status', $curr)) {
					$curr['status']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '30') {
					$curr['timeStampToken']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['TimeStampResp']=$curr;

		$curr = $ar['TimeStampResp']['timeStampToken'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '06') {
					$curr['contentType']=$curr[$key];
					unset($curr[$key]);
				}
				if($value['type'] == 'a0') {
					$curr['content']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['TimeStampResp']['timeStampToken'] = $curr;

		$curr = $ar['TimeStampResp']['timeStampToken']['content'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '30') {
					$curr['TSTInfo']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['TimeStampResp']['timeStampToken']['content'] = $curr;

		if(@$ar['TimeStampResp']['timeStampToken']['content']['hexdump'] != '') {
			return $ar;
		} else {
			 $this->errorMsg = "TimeStampResp data not valid.";
			 return false; 
		}
	}

	/**
	 * append tsa data to pkcs7 signature
	 * @param string $binaryTsaResp binary tsa response
	 * @return string hex pkcs7 with tsa.
	 * @public
	 */
	public function pkcs7_appendTsa($binaryTsaResp) {
		if(!@$tsaResp = $this->tsa_parseResp($binaryTsaResp)) {
			$this->errorMsg = "can't parse tsa response";
			return false;
		}
		$TSTInfo = $tsaResp['TimeStampResp']['timeStampToken']['hexdump'];

		$TimeStampToken = tcpdf_asn1::seq(
											"060B2A864886F70D010910020E".
											tcpdf_asn1::set(
															$TSTInfo
															)
											);

		$time = tcpdf_asn1::seq(
								$this->pkcs7_dataArray['ContentInfo']['content']['SignedData']['signerinfos']['signerinfo']['value_hex']. // ocsp & crl is here
								tcpdf_asn1::explicit(1,$TimeStampToken)
								);
		$pkcs7contentSignedData=tcpdf_asn1::seq(
												$this->pkcs7_dataArray['ContentInfo']['content']['SignedData']['version']['hexdump'].
												$this->pkcs7_dataArray['ContentInfo']['content']['SignedData']['digestAlgorithms']['hexdump'].
												$this->pkcs7_dataArray['ContentInfo']['content']['SignedData']['contentInfo']['hexdump'].
												$this->pkcs7_dataArray['ContentInfo']['content']['SignedData']['certificates']['hexdump'].
												tcpdf_asn1::set($time)
												);
		$pkcs7ContentInfo = tcpdf_asn1::seq(
											"06092A864886F70D010702".
											tcpdf_asn1::explicit(0,$pkcs7contentSignedData)
											);
		return $pkcs7ContentInfo;
	}

	/**
	 * insert pkcs7 signature to parsed
	 * @param string $hex hex pkcs7
	 * @return array pkcs7 form
	 * @set pkcs7_dataArray array pkcs7 form
	 * @set pkcs7_EncryptedDigest string hex pkcs7 EncryptedDigest value
	 * @public
	 */
	public function pkcs7_data($hex) {
		$tcpdf_asn1 = new tcpdf_asn1;
		if(!@$ar = $tcpdf_asn1->parse_recursive($hex, 5)) {
			 $this->errorMsg = "can't parse pkcs7 data.";
			return false;
		}

		$curr = $ar;
		foreach($curr as $key=>$value) {
			if($value['type'] == '30') {
				$curr['ContentInfo']=$curr[$key];
				unset($curr[$key]);
			}
		}
		$ar=$curr;

		$curr = $ar['ContentInfo'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '06') {
					$curr['ContentType']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == 'a0') {
					$curr['content']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['ContentInfo'] = $curr;

		$curr = $ar['ContentInfo']['content'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '30') {
					$curr['SignedData']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['ContentInfo']['content'] = $curr;

		$curr = $ar['ContentInfo']['content']['SignedData'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '02') {
					$curr['version']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '31' && !array_key_exists('digestAlgorithms', $curr)) {
					$curr['digestAlgorithms']=$curr[$key];
					unset($curr[$key]);
				}else if($value['type'] == '30') {
					$curr['contentInfo']=$curr[$key];
					unset($curr[$key]);
				}else if($value['type'] == 'a0') {
					$curr['certificates']=$curr[$key];
					unset($curr[$key]);
				}else if($value['type'] == 'a1') {
					$curr['crls']=$curr[$key];
					unset($curr[$key]);
				}else if($value['type'] == '31') {
					$curr['signerinfos']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['ContentInfo']['content']['SignedData']=$curr;

		$curr = $ar['ContentInfo']['content']['SignedData']['signerinfos'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '30') {
					$curr['signerinfo']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['ContentInfo']['content']['SignedData']['signerinfos'] = $curr;

		$curr=$ar['ContentInfo']['content']['SignedData']['signerinfos']['signerinfo'];
		foreach($curr as $key=>$value) {
			if(is_numeric($key)) {
				if($value['type'] == '02') {
					$curr['version']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '30' && !array_key_exists('issuerAndSerialNumber', $curr)) {
					$curr['issuerAndSerialNumber']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '30'&& !array_key_exists('digestAlgorithm', $curr)) {
					$curr['digestAlgorithm']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == 'a0') {
					$curr['authenticatedAttributes']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '30') {
					$curr['digestEncryptionAlgorithm']=$curr[$key];
					unset($curr[$key]);
				} else if($value['type'] == '04') {
					$curr['encryptedDigest']=$curr[$key];
					unset($curr[$key]);
				}
			}
		}
		$ar['ContentInfo']['content']['SignedData']['signerinfos']['signerinfo']=$curr;

		$this->pkcs7_dataArray = $ar;
		$this->pkcs7_EncryptedDigest=hex2bin($ar['ContentInfo']['content']['SignedData']['signerinfos']['signerinfo']['encryptedDigest']['value_hex']);
		return $ar;
	}
}

/**
 * @class tcpdf_asn1
 * Asn.1 encode/decode
 * @version 1.0.001
 * @author M Hida
 */
class tcpdf_asn1 {
	// throw error to errorMsg
	public string $errorMsg;
	
	/**
	 * parse asn.1 to array
	 * to be called from $this->parse_recursive() function
	 * @param string $hex asn.1 hex form
	 * @return array asn.1 structure
	 * @protected
	 */
	protected function parse($hex) {
		if(!@ctype_xdigit($hex) || @strlen($hex)%2!=0) {
			$this->errorMsg = "input not hex string!.";
			return false;
		}
		$stop = false;
		while($stop == false) {
			$asn1_type = substr($hex, 0, 2);
			$tlv_tagLength = hexdec(substr($hex, 2, 2));
			if($tlv_tagLength > 127) {
				$tlv_lengthLength = $tlv_tagLength-128;
				$tlv_valueLength = substr($hex, 4, ($tlv_lengthLength*2));
			} else {
				$tlv_lengthLength = 0;
				$tlv_valueLength = substr($hex, 2, 2+($tlv_lengthLength*2));
			}
			if($tlv_lengthLength >4) { // limit tlv_lengthLength to FFFF
				return false;
			}
			$tlv_valueLength = hexdec($tlv_valueLength);
			
			$totalTlLength = 2+2+($tlv_lengthLength*2);
			$reduction = 2+2+($tlv_lengthLength*2)+($tlv_valueLength*2);
			$tlv_value = substr($hex, $totalTlLength, $tlv_valueLength*2);
			$remain = substr($hex, $totalTlLength+($tlv_valueLength*2));
			$newhexdump = substr($hex, 0, $totalTlLength+($tlv_valueLength*2));
			
			$result[] = array(
							'tlv_tagLength'=>strlen(dechex($tlv_tagLength))%2==0?dechex($tlv_tagLength):'0'.dechex($tlv_tagLength),
							'tlv_lengthLength'=>$tlv_lengthLength,
							'tlv_valueLength'=>$tlv_valueLength,
							'newhexdump'=>$newhexdump,
							'typ'=>$asn1_type,
							'tlv_value'=>$tlv_value
							);

			if($remain == '') { // if remains string was empty & contents also empty, function return FALSE
				$stop = true;
			} else {
				$hex = $remain;
			}
		}
		return $result;
	}

	/**
	 * parse asn.1 to array recursively
	 * @param string $hex asn.1 hex form
	 * @param int $maxDepth maximum parsing depth
	 * @return array asn.1 structure recursively to specific depth
	 * @public
	 */
	public function parse_recursive($hex, $maxDepth=5) {
		$result = array();
		$info = array();
		$parse_recursive = array();
		$asn1parse_array = $this->parse($hex);
		static $currentDepth = 0;
		if($asn1parse_array) {
			foreach($asn1parse_array as $ff){
				$k = $ff['typ'];
				$v = $ff['tlv_value'];
				$info['depth']=$currentDepth;
				$info['hexdump']=$ff['newhexdump'];
				$info['type'] = $k;  
				$info['value_hex'] = $v;  
				if(($currentDepth <= $maxDepth)) {
					if($k == '06') {

					} else if($k == '13' || $k == '18') {
						$info['value'] = hex2bin($info['value_hex']);
					} else if($k == '03' || $k == '02') {
						$info['value'] = $v;
					} else if($k == '05') {

					} else {
						$currentDepth++;
						$parse_recursive = $this->parse_recursive($v, $maxDepth); 
						$currentDepth--;
					}
					if($parse_recursive) {
						$result[] = array_merge($info, $parse_recursive);
					} else {
						$result[] = $info;
					}
					unset($info['value']);
				}
			}
		} else {
			$result = false;
		}
		return $result;
	}

	/**
	 * create asn.1 TLV tag length, length length and value length
	 * to be called from asn.1 builder functions
	 * @param string $str string value of asn.1
	 * @return string hex of asn.1 TLV tag length
	 * @protected
	 */
	protected static function asn1_header($str) {
		$len = strlen($str)/2;
		$ret = dechex($len);
		if(strlen($ret)%2 != 0) {
			$ret = "0$ret";
		}

		$headerLength = strlen($ret)/2;
		if($len > 127) {
			$ret = "8".$headerLength.$ret;
		}
		return $ret;
	}

	/**
	 * build asn.1 SEQUENCE tag
	 * @param string hex value of asn.1 SEQUENCE
	 * @return tring hex of asn.1 SEQUENCE with value
	 * @public
	 */
	public static function SEQ($hex)  {
		$ret = "30".self::asn1_header($hex).$hex;
		return $ret;
	}

	/**
	 * build asn.1 OCTET tag
	 * @param string hex value of asn.1 OCTET
	 * @return tring hex of asn.1 OCTET with value
	 * @public
	 */
	public static function OCT($hex)  {
		$ret = "04".self::asn1_header($hex).$hex;
		return $ret;
	}

	/**
	 * build asn.1 INTEGER tag
	 * @param string hex value of asn.1 INTEGER
	 * @return tring hex of asn.1 INTEGER with value
	 * @public
	 */
	public static function INT($int)  {
		if(strlen($int)%2 != 0)  {
			$int = "0$int";
		}
		$int = "$int";
		$ret = "02".self::asn1_header($int).$int;
		return $ret;
	}

	/**
	 * build asn.1 SET tag
	 * @param string hex value of asn.1 SET
	 * @return tring hex of asn.1 SET with value
	 * @public
	 */
	public static function SET($hex)  {
		$ret = "31".self::asn1_header($hex).$hex;
		return $ret;
	}

	/**
	 * build asn.1 EXPLICIT tag
	 * @param string hex value of asn.1 EXPLICIT
	 * @return tring hex of asn.1 EXPLICIT with value
	 * @public
	 */
	public static function EXPLICIT($num, $hex)  {
		$ret = "a$num".self::asn1_header($hex).$hex;
		return $ret;
	}
}
?>