<?php
/*
// File name   : tcpdf_cmssignature.php
// Version     : 1.1
// Begin       : 2023-05-25
// Last Update : 2024-04-21
// Author      : Hida - https://github.com/hidasw
// License     : GNU GPLv3
*/
/**
 * @class tcpdf_cmssignature
 * Manage CMS Signature for TCPDF.
 * @version 1.1
 * @author M Hida
 */
class tcpdf_cmssignature {
  
  /**
   * string to logged
   */
  public $log;

  /**
   * write log to file
   */
  public $writeLog = false;

  /**
   * log file
   */
  public $logFile = '../signature.log';

  public $signature_data;
  public $signature_data_ltv = array();
  public $signature_data_tsa = array();

  /**
   * logging at end
   * @public
   */
  public function __destruct() {
    if($this->writeLog) {
      $logs = date("Y-m-d H:i:s")."\n";
      $logs .= "========== START LOG ==========\n";
      $arrLog = explode("\n", rtrim($this->log));
      $newlines = '';
      foreach($arrLog as $line) {
        $head = trim(substr($line, 0, strpos($line, ":")));
        $newhead = str_pad($head, 10, " ");
        $ct = rtrim(substr($line, strpos($line, ":")+1));
        $newline = "$newhead: $ct\n";
        $newlines .= $newline;
      }
      $logs .= $newlines;
      $logs .= "========== END LOG ==========\n\n";
      // echo $logs;
      if(@$h = fopen($this->logFile, 'w')) {
        fwrite($h, $logs);
        fclose($h);
      }
    }
  }


  /**
   * send tsa/ocsp query with curl
   * @param array $reqData
   * @return string response body
   * @public
   */
  public function sendReq($reqData) {
		if (!function_exists('curl_init')) {
			$this->Error('Please enable cURL PHP extension!');
		}
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $reqData['uri']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: {$reqData['req_contentType']}",'User-Agent: TCPDF'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData['data']);
    $tsResponse = curl_exec($ch);
    if($tsResponse) {
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      curl_close($ch);
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
        $this->log .= "error:      response error! Code=\"$code\", Status=\"".trim($status)."\"\n";
        return false;
      }
      $contentTypeHeader = '';
      $headers = explode("\n", $header);
      foreach ($headers as $key => $r) {
        // Match the header name up to ':', compare lower case
        if (stripos($r, "Content-Type".':') === 0) {
          list($headername, $headervalue) = explode(":", $r, 2);
          $contentTypeHeader = trim($headervalue);
        }
      }
      if($contentTypeHeader != $reqData['resp_contentType']) {
        $this->log .= "error:      response content type not {$reqData['resp_contentType']}, but: \"$contentTypeHeader\"\n";
        return false;
      }
      return $body; // binary response
    }
  }

  /**
   * parse tsa response to array
   * @param string $binaryTsaRespData binary tsa response to parse
   * @return array asn.1 hex structure of tsa response
   * @private
   */
  private function tsa_parseResp($binaryTsaRespData) {
    if(!@$ar = asn1::parse(bin2hex($binaryTsaRespData), 3)) {
      $this->log .= "info:    can't parse invalid tsa Response.\n";
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
       return false; 
    }
  }

  /**
   * Create timestamp
   * @param string $data
   * @return string hex TSTinfo.
   * @public
   */
  protected function createTimestamp($data, $hashAlg='sha1') {
    $TSTInfo=false;
    $tsaQuery = x509::tsa_query($data, $hashAlg);
    $reqData = array(
                    'data'=>$tsaQuery,
                    'uri'=>$this->signature_data_tsa['host'],
                    'req_contentType'=>'application/timestamp-query',
                    'resp_contentType'=>'application/timestamp-reply'
                    );
    $this->log .= "info:    sending TSA query to \"".$this->signature_data_tsa['host']."\"...\n";
    if(!$binaryTsaResp = self::sendReq($reqData)) {
      $this->log .= "error:    TSA query FAILED!\n";
    } else {
      $this->log .= "info:    TSA query OK\n";
      $this->log .= "info:    Parsing Timestamp response...";
      if(!$tsaResp = $this->tsa_parseResp($binaryTsaResp)) {
        $this->log .= "FAILED!\n";
      }
      $this->log .= "OK\n";
      $TSTInfo = $tsaResp['TimeStampResp']['timeStampToken']['hexdump'];
    }
    return $TSTInfo;
  }

  /**
   * Perform OCSP/CRL Validation
   * @param
   * @return array
   * @public
   */
  public function LTVvalidation($parsedCert, $ocspURI=null, $crlURIorFILE=null, $issuerURIorFILE=false) {
    $ltvResult['issuer']=false;
    $ltvResult['ocsp']=false;
    $ltvResult['crl']=false;
    $x509 = new x509;
    $certSigner_parse = $parsedCert;
    $this->log .= "info:    getting OCSP address...\n";
    if($ocspURI===false) {
      $this->log .= "info:      OCSP is skipped by FALSE argument.\n";
    } else {
      if(empty(trim($ocspURI))) {
        $msglog = ":      OCSP address \"$ocspURI\" is empty/not set. try getting from certificate AIA OCSP attribute...";
        $ocspURI = @$certSigner_parse['tbsCertificate']['attributes']['1.3.6.1.5.5.7.1.1']['value']['1.3.6.1.5.5.7.48.1'][0];
      } else {
        $msglog = ":     OCSP address is set manually...";
      }
      $this->log .= (empty(trim($ocspURI)))?"warning".$msglog."FAILED! got empty address:\"$ocspURI\"\n":"info".$msglog."OK. address:\"$ocspURI\"\n";
    }
    $ocspURI = trim($ocspURI);
    $this->log .= "info:    getting CRL address...\n";
    if(empty(trim($crlURIorFILE))) {
      $crlURIorFILE = @$certSigner_parse['tbsCertificate']['attributes']['2.5.29.31']['value'][0];
      $msglog = ":      CRL address \"$ocspURI\" is empty/not set. try getting location from certificate CDP attribute...";
    } else {
      // $this->log .= "info:      CRL uri or file is set manually...";
      $msglog = ":      CRL uri or file is set manually...";
    }
    $this->log .= (empty(trim($crlURIorFILE)))?"warning".$msglog."FAILED! got empty address:\"$crlURIorFILE\"\n":"info".$msglog."OK. address:\"$crlURIorFILE\"\n";
    if(empty($ocspURI) && empty($crlURIorFILE)) {
      $this->log .= "error:    can't get OCSP/CRL address! Process terminated.\n";
    } else { // Perform if either ocspURI/crlURIorFILE exists
      $this->log .= "info:    getting Issuer address...\n";
      if(empty(trim($issuerURIorFILE))) {
        $this->log .= "info:      issuer location address \"$issuerURIorFILE\" is empty/not set. use AIA Issuer attribute from certificate...";
        $issuerURIorFILE = @$certSigner_parse['tbsCertificate']['attributes']['1.3.6.1.5.5.7.1.1']['value']['1.3.6.1.5.5.7.48.2'][0];
      } else {
        $this->log .= "info:    issuer location manually specified ($issuerURIorFILE)\n";
      }
      $this->log .= (empty(trim($issuerURIorFILE)))?"FAILED. empty address:\"$issuerURIorFILE\"\n":"OK. address:\"$issuerURIorFILE\"\n";
      $issuerURIorFILE = trim($issuerURIorFILE);
      if(empty($issuerURIorFILE)) {
        $this->log .= "error:    cant get issuer location! Process terminated.\n";
      } else {
        $this->log .= "info:    getting issuer from \"$issuerURIorFILE\"...";
        if($issuerCert = @file_get_contents($issuerURIorFILE)) {
          $this->log .= "OK. size ".round(strlen($issuerCert)/1024,2)."Kb\n";
          $this->log .= "info:      reading issuer certificate...";
          if($issuer_certDER = x509::get_cert($issuerCert)) {
            $this->log .= "OK\n";
            $this->log .= "info:      check if issuer is cert issuer...";
            $certIssuer_parse = $x509::readcert($issuer_certDER, 'oid'); // Parsing Issuer cert
            $certSigner_signatureField = $certSigner_parse['signatureValue'];
            if(openssl_public_decrypt(hex2bin($certSigner_signatureField), $decrypted, $x509::x509_der2pem($issuer_certDER), OPENSSL_PKCS1_PADDING)) {
              $this->log .= "OK.\n";
              $ltvResult['issuer'] = $issuer_certDER;
            } else {
              $this->log .= "FAILED! CA is not issuer.\n";
            }
          } else {
            $this->log .= "FAILED!\n";
          }
        } else {
          $this->log .= "FAILED.\n";
        }
      }
    }

    if($ltvResult['issuer']) {
      if(!empty($ocspURI)) {
        $this->log .= "info:    OCSP start.\n";
        $ocspReq_serialNumber = $certSigner_parse['tbsCertificate']['serialNumber'];
        $ocspReq_issuerNameHash = $certIssuer_parse['tbsCertificate']['subject']['sha1'];
        $ocspReq_issuerKeyHash = $certIssuer_parse['tbsCertificate']['subjectPublicKeyInfo']['sha1'];
        $ocspRequestorSubjName = $certSigner_parse['tbsCertificate']['subject']['hexdump'];
        $this->log .= "info:      OCSP create request...";
        if($ocspReq = $x509::ocsp_request($ocspReq_serialNumber, $ocspReq_issuerNameHash, $ocspReq_issuerKeyHash, $this->signature_data['signcert'], $this->signature_data['privkey'], $ocspRequestorSubjName)) {
          $this->log .= "OK.\n";
          $ocspBinReq = pack("H*", $ocspReq);
          $reqData = array(
                          'data'=>$ocspBinReq,
                          'uri'=>$ocspURI,
                          'req_contentType'=>'application/ocsp-request',
                          'resp_contentType'=>'application/ocsp-response'
                          );
          $this->log .= "info:      OCSP send request to \"$ocspURI\"...\n";
          if($ocspResp = self::sendReq($reqData)) {
            $this->log .= "info:      OCSP send request OK.\n";
            $this->log .= "info:      OCSP parse response...";
            if($ocsp_parse = $x509::ocsp_response_parse($ocspResp, $return)) {
              $this->log .= "OK.\n";
              $this->log .= "info:      OCSP check cert validity...";
              $certStatus = $ocsp_parse['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responses'][0]['certStatus'];
              if($certStatus == 'valid') {
                $this->log .= "OK. cert VALID.\n";
                $ocspRespHex = $ocsp_parse['hexdump'];
                $appendOCSP = asn1::expl(1,
                                          asn1::seq(
                                                    $ocspRespHex
                                                    )
                                          );
                $ltvResult['ocsp'] = $appendOCSP;
              } else {
                $this->log .= "FAILED! cert invalid, status:\"$certStatus\"\n";
              }
            } else {
              $this->log .= "FAILED! Ocsp server status \"$return\"\n";
            }
          } else {
            $this->log .= "error:      FAILED! OCSP FAILED!\n";
          }
        } else {
          $this->log .= "FAILED!\n";
        }
      }

      if(!$ltvResult['ocsp']) {// CRL not processed if OCSP validation already success
        if(!empty($crlURIorFILE)) {
          $this->log .= "info:    processing CRL validation since OCSP not done/failed...\n";
          $this->log .= "info:      getting crl from \"$crlURIorFILE\"...";
          if($crl = @file_get_contents($crlURIorFILE)) {
            $this->log .= "OK. crl size ".round(strlen($crl)/1024,2)."Kb\n";
            $this->log .= "info:      reading crl...";
            if($crlread=$x509->crl_read($crl)) {
              $this->log .= "OK\n";
              $this->log .= "info:      checking if crl issued by CA...";
              $crl_signatureField = $crlread['parse']['signature'];
              if(openssl_public_decrypt(hex2bin($crl_signatureField), $decrypted, $x509::x509_der2pem($issuer_certDER), OPENSSL_PKCS1_PADDING)) {
                $this->log .= "OK\n";
                $crl_parse=$crlread['parse'];
                $crlCertValid=true;
                $this->log .= "info:      check if cert not revoked...";
                if(array_key_exists('revokedCertificates', $crl_parse['TBSCertList'])) {
                  $certSigner_serialNumber = $certSigner_parse['tbsCertificate']['serialNumber'];
                  if(array_key_exists($certSigner_serialNumber, $crl_parse['TBSCertList']['revokedCertificates']['lists'])) {
                    $crlCertValid=false;
                    $this->log .= "FAILED! Certificate Revoked!\n";
                  }
                }
                if($crlCertValid == true) {
                  $this->log .= "OK. VALID\n";
                  $crlHex = current(unpack('H*', $crlread['der']));
                  $appendCrl = asn1::expl(0,
                                            asn1::seq(
                                                      $crlHex
                                                      )
                                            );
                  $ltvResult['crl'] = $appendCrl;
                }
              } else {
                $this->log .= "FAILED! Wrong CRL.\n";
              }
            } else {
              $this->log .= "FAILED!\n";
            }
          } else {
            $this->log .= "FAILED!\n";
          }
        }
      }
    }
    if(!$ltvResult['ocsp'] && !$ltvResult['crl']) {
      return false;
    }
    return $ltvResult;
  }

  /**
   * Perform PKCS7 Signing
   * @param string $binaryData
   * @return string hex
   * @public
   */
  public function pkcs7_sign($binaryData) {
    $hexOidHashAlgos = array(
                            'md2'=>'06082A864886F70D0202',
                            'md4'=>'06082A864886F70D0204',
                            'md5'=>'06082A864886F70D0205',
                            'sha1'=>'06052B0E03021A',
                            'sha224'=>'0609608648016503040204',
                            'sha256'=>'0609608648016503040201',
                            'sha384'=>'0609608648016503040202',
                            'sha512'=>'0609608648016503040203'
                            );
    $hashAlgorithm = $this->signature_data['hashAlgorithm'];
    if(!array_key_exists($hashAlgorithm, $hexOidHashAlgos)) {
      $this->log .= "error:not support hash algorithm!\n";
      return false;
    }
    $this->log .= "info:hash algorithm is \"$hashAlgorithm\"\n";
    if(!$pkey = openssl_pkey_get_private($this->signature_data['privkey'], $this->signature_data['password'])) {
      $this->log .= "error:can't get private key! please check private key or password\n";
      return false;
    }
    $x509 = new x509;
    $extCertsAr = explode("-----BEGIN CERTIFICATE-----", $this->signature_data['extracerts']);
    $hexExtracerts = false;
    foreach($extCertsAr as $certPart) {
      $endPos = strpos($certPart, "-----END CERTIFICATE-----");
      $cert = "-----BEGIN CERTIFICATE-----\n";
      $cert .= trim(substr($certPart, 0, $endPos))."\n";
      $cert .= "-----END CERTIFICATE-----\n";
      $hexExtracerts .= bin2hex($x509->x509_pem2der($cert));
    }
    if(!$certParse = $x509->readcert($this->signature_data['signcert'])) {
      $this->log .= "error:certificate error! check certificate\n";
    }

    $appendLTV = '';
    if(!empty($this->signature_data_ltv)) {
      $this->log .= "info: LTV Validation start...\n";
      $LTVvalidation = self::LTVvalidation($certParse, $this->signature_data_ltv['ocspURI'], $this->signature_data_ltv['crlURIorFILE'], $this->signature_data_ltv['issuerURIorFILE']);
      $this->log .= "info: LTV Validation end ";
      if($LTVvalidation) {
        $this->log .= "Success\n";
        $hexExtracerts .= bin2hex($LTVvalidation['issuer']);
        $appendLTV = asn1::seq("06092A864886F72F010108". // adbe-revocationInfoArchival (1.2.840.113583.1.1.8)
                                  asn1::set(
                                            asn1::seq(
                                                      $LTVvalidation['ocsp'].
                                                      $LTVvalidation['crl']
                                                      )
                                            )
                                );
      } else {
        $this->log .= "Failed!\n";
      }
    }

    $messageDigest = hash($hashAlgorithm, $binaryData);
    $authenticatedAttributes= asn1::seq(
                                        '06092A864886F70D010903'. //OBJ_pkcs9_contentType 1.2.840.113549.1.9.3
                                        asn1::set('06092A864886F70D010701')  //OBJ_pkcs7_data 1.2.840.113549.1.7.1
                                        ).
                              asn1::seq( // signing time
                                        '06092A864886F70D010905'. //OBJ_pkcs9_signingTime 1.2.840.113549.1.9.5
                                        asn1::set(
                                                  asn1::utime(date("ymdHis")) //UTTC Time
                                                  )
                                        ).
                              asn1::seq( // messageDigest 
                                        '06092A864886F70D010904'. //OBJ_pkcs9_messageDigest 1.2.840.113549.1.9.4
                                        asn1::set(
                                                  asn1::oct($messageDigest)
                                                  )
                                        ).
                              $appendLTV;
    
    $tohash = asn1::set($authenticatedAttributes);
    $hash = hash($hashAlgorithm, hex2bin($tohash));
    $toencrypt =  asn1::seq(
                            asn1::seq($hexOidHashAlgos[$hashAlgorithm]."0500").  // OBJ $messageDigest & OBJ_null
                            asn1::oct($hash)
                            );
    if(!openssl_private_encrypt(hex2bin($toencrypt), $encryptedDigest, $pkey, OPENSSL_PKCS1_PADDING)) {
      $this->log .= "error:openssl_private_encrypt error! can't encrypt\n";
    }
    $hexencryptedDigest = bin2hex($encryptedDigest);
    $timeStamp = '';
    if(!empty($this->signature_data_tsa)) {
      $this->log .= "info: TimeStamping start...\n";
      if($TSTInfo = self::createTimestamp($encryptedDigest, $hashAlgorithm)) {
        $this->log .= "info: TimeStamping end Success.\n";
        $TimeStampToken = asn1::seq(
                                    "060B2A864886F70D010910020E". // OBJ_id_smime_aa_timeStampToken 1.2.840.113549.1.9.16.2.14
                                    asn1::set(
                                            $TSTInfo
                                            )
                                    );
        $timeStamp = asn1::expl(1,$TimeStampToken);
      } else {
        $this->log .= "info: TimeStamping end Failed!\n";
      }
    }
    $issuerName = $certParse['tbsCertificate']['issuer']['hexdump'];
    $serialNumber = $certParse['tbsCertificate']['serialNumber'];
    $signerinfos = asn1::seq(
                              asn1::int('1').
                              asn1::seq(
                                        $issuerName.
                                        asn1::int($serialNumber)
                                        ).
                              asn1::seq($hexOidHashAlgos[$hashAlgorithm].'0500').
                              asn1::expl(0, $authenticatedAttributes).
                              asn1::seq(
                                        '06092A864886F70D010101'. //OBJ_rsaEncryption
                                        '0500'
                                        ).
                              asn1::oct($hexencryptedDigest).
                              $timeStamp
                            );
    $crl = asn1::expl(1,
                        '0500' // crls
                        );
    $crl = '';
    $certs = asn1::expl(0,bin2hex($x509->get_cert($this->signature_data['signcert'])).$hexExtracerts);
    $pkcs7contentSignedData=asn1::seq(
                                        asn1::int('1').
                                        asn1::set(
                                                  asn1::seq($hexOidHashAlgos[$hashAlgorithm].'0500')
                                                  ).
                                        asn1::seq('06092A864886F70D010701'). //OBJ_pkcs7_data
                                        $certs.
                                        $crl.
                                        asn1::set($signerinfos)
                                      );
    $pkcs7ContentInfo = asn1::seq(
                                    "06092A864886F70D010702". // Hexadecimal form of pkcs7-signedData
                                    asn1::expl(0,$pkcs7contentSignedData)
                                  );
    return $pkcs7ContentInfo;
  }
}

/**
 * @class x509
 * Perform some x509 operation
 * @version 1.1
 * @author M Hida
 */
class x509 {
  /*
   * create tsa request/query with nonce and cert req extension
   * @param string $binaryData raw/binary data of tsa query
   * @param string $hashAlg hash Algorithm
   * @return string binary tsa query
   * @public
   */
  public static function tsa_query($binaryData, $hashAlg='sha256') {
    $hashAlg = strtolower($hashAlg);
    $hexOidHashAlgos = array(
                            'md2'=>'06082A864886F70D0202',
                            'md4'=>'06082A864886F70D0204',
                            'md5'=>'06082A864886F70D0205',
                            'sha1'=>'06052B0E03021A',
                            'sha224'=>'0609608648016503040204',
                            'sha256'=>'0609608648016503040201',
                            'sha384'=>'0609608648016503040202',
                            'sha512'=>'0609608648016503040203'
                            );
    if(!array_key_exists($hashAlg, $hexOidHashAlgos)) {
      return false;
    }
    $hash = hash($hashAlg, $binaryData);
    $tsReqData = asn1::seq(
                            asn1::int(1).
                            asn1::seq(
                                      asn1::seq($hexOidHashAlgos[$hashAlg]."0500"). // object OBJ $hexOidHashAlgos[$hashAlg] & OBJ_null
                                      asn1::oct($hash)
                                      ).
                            asn1::int(hash('crc32', rand()).'001'). // tsa nonce
                            '0101ff' // req return cert
                          );
    return hex2bin($tsReqData);
  }

  /**
   * Calculate 32 openssl subject hash old and new
   * @param string $hex_subjSequence hex subject name sequence
   * @return array subject hash old and new
   */
  private static function opensslSubjHash($hex_subjSequence){
    $parse = asn1::parse($hex_subjSequence,3);
    $hex_subjSequence_new='';
    foreach($parse[0] as $k=>$v) {
      if(is_numeric($k)) {
        $hex_subjSequence_new .= asn1::set(
                                          asn1::seq(
                                                    $v[0][0]['hexdump'].
                                                    asn1::utf8(strtolower(hex2bin($v[0][1]['value_hex'])))
                                                    )
                                          );
      }
    }
    $tohash = pack("H*", $hex_subjSequence_new);
    $openssl_subjHash_new = hash('sha1', $tohash);
    $openssl_subjHash_new = substr($openssl_subjHash_new, 0, 8);
    $openssl_subjHash_new = str_split($openssl_subjHash_new, 2);
    $openssl_subjHash_new = array_reverse($openssl_subjHash_new);
    $openssl_subjHash_new = implode("", $openssl_subjHash_new);

    $openssl_subjHash_old = hash('md5', hex2bin($hex_subjSequence));
    $openssl_subjHash_old = substr($openssl_subjHash_old, 0, 8);
    $openssl_subjHash_old = str_split($openssl_subjHash_old, 2);
    $openssl_subjHash_old = array_reverse($openssl_subjHash_old);
    $openssl_subjHash_old = implode("", $openssl_subjHash_old);

    return array(
                  "old"=>$openssl_subjHash_old,
                  "new"=>$openssl_subjHash_new
                  );
  }

  /**
   * Parsing ocsp response data
   * @param string $binaryOcspResp binary ocsp response
   * @return array ocsp response structure
   */
  public static function ocsp_response_parse($binaryOcspResp, &$status='') {
    $hex = current(unpack("H*", $binaryOcspResp));
    $parse = asn1::parse($hex,10);
    if($parse[0]['type'] == '30') {
      $ocsp = $parse[0];
    } else {
      return false;
    }
    
   //OCSPResponseStatus ::= ENUMERATED {
   //    successful            (0),  --Response has valid confirmations
   //    malformedRequest      (1),  --Illegal confirmation request
   //    internalError         (2),  --Internal error in issuer
   //    tryLater              (3),  --Try again later
   //                                --(4) is not used
   //    sigRequired           (5),  --Must sign the request
   //    unauthorized          (6)   --Request unauthorized
   //}
    foreach($ocsp as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '0a') {
          $ocsp['responseStatus']=$value['value_hex'];
          unset($ocsp[$key]);
        }
        if($value['type'] == 'a0') {
          $ocsp['responseBytes']=$value;
          unset($ocsp[$key]);
        }
      } else {
        //unset($ocsp[$key]);
        unset($ocsp['depth']);
        unset($ocsp['type']);
        unset($ocsp['typeName']);
        unset($ocsp['value_hex']);
      }
    }
    if(@$ocsp['responseStatus'] != '00') {
      $responseStatus['01']='malformedRequest';
      $responseStatus['02']='internalError';
      $responseStatus['03']='tryLater';
      $responseStatus['05']='sigRequired';
      $responseStatus['06']='unauthorized';
      $status = $responseStatus[$ocsp['responseStatus']];
      return false;
    }

    if(!@$curr = $ocsp['responseBytes']) {
      return false;
    }
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30') {
          $curr['responseType']=self::oidfromhex($value[0]['value_hex']);
          $curr['response']=$value[1];
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes'] = $curr;

    $curr = $ocsp['responseBytes']['response'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30') {
          $curr['BasicOCSPResponse']=$value;
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response'] = $curr;

    $curr = $ocsp['responseBytes']['response']['BasicOCSPResponse'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30' && !array_key_exists('tbsResponseData', $curr)) {
          $curr['tbsResponseData']=$value;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('signatureAlgorithm', $curr)) {
          $curr['signatureAlgorithm']=$value[0]['value_hex'];
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '03') {
          $curr['signature']=substr($value['value_hex'], 2);
          unset($curr[$key]);
        }
        if($value['type'] == 'a0') {
          foreach($value[0] as $certsK=>$certsV) {
            if(is_numeric($certsK)) {
              $certs[$certsK] = $certsV['value_hex'];
            }
          }
          $curr['certs']=$certs;
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response']['BasicOCSPResponse'] = $curr;

    $curr = $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == 'a0') {
          $curr['version']=$value[0]['value'];
          unset($curr[$key]);
        }
        if($value['type'] == 'a1' && !array_key_exists('responderID', $curr)) {
          $curr['responderID']=$value;
          unset($curr[$key]);
        }
        if($value['type'] == 'a2') {
          $curr['responderID']=$value;
          unset($curr[$key]);
        }
        if($value['type'] == '18') {
          $curr['producedAt']=$value['value'];
          unset($curr[$key]);
        }
        if($value['type'] == '30') {
          $curr['responses']=$value;
          unset($curr[$key]);
        }
        if($value['type'] == 'a1') {
          $curr['responseExtensions']=$value;
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData'] = $curr;

    $curr = $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responseExtensions'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30') {
          $curr['lists']=$value;
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responseExtensions'] = $curr;

    $curr = $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responseExtensions']['lists'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30') {
          if($value[0]['value_hex'] == '2b0601050507300102') { // nonce
            $curr['nonce']=$value[0]['value_hex'];
          } else {
            $curr[$value[0]['value_hex']]=$value[1];
          }
          unset($curr[$key]);
        }
      } else {
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responseExtensions']['lists'] = $curr;

    $curr = $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responses'];
    $i=0;
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
          foreach($value as $SingleResponseK=>$SingleResponseV) {
            if(is_numeric($SingleResponseK)) {
              if($SingleResponseK == 0) {
                foreach($SingleResponseV as $certIDk=>$certIDv) {
                  if(is_numeric($certIDk)) {
                    if($certIDv['type'] == '30') {
                      $certID['hashAlgorithm'] = $certIDv[0]['value_hex'];
                    }
                    if($certIDv['type'] == '04' && !array_key_exists('issuerNameHash', $certID)) {
                      $certID['issuerNameHash'] = $certIDv['value_hex'];
                    }
                    if($certIDv['type'] == '04') {
                      $certID['issuerKeyHash'] = $certIDv['value_hex'];
                    }
                    if($certIDv['type'] == '02') {
                      $certID['serialNumber'] = $certIDv['value_hex'];
                    }
                  }
                }
                $cert['certID'] = $certID;
              }
              if($SingleResponseK == 1) {
                if($SingleResponseV['type'] == '82') {
                  $certStatus = 'unknown';
                } elseif($SingleResponseV['type'] == '80') {
                  $certStatus = 'valid';
                } else {
                  $certStatus = 'revoked';
                }
                $cert['certStatus'] = $certStatus;
              }
              if($SingleResponseK == 2) {
                $cert['thisUpdate'] = $SingleResponseV['value'];
              }
              if($SingleResponseK == 3) {
                $cert['nextUpdate'] = $SingleResponseV[0]['value'];
              }
              if($SingleResponseK == 4) {
                $cert['singleExtensions'] = $SingleResponseV;
              }
            }
          }
          $curr[$i] = $cert;
      } else {
        unset($curr[$key]);
        unset($curr['typeName']);
        unset($curr['type']);
        unset($curr['depth']);
      }
    }
    $ocsp['responseBytes']['response']['BasicOCSPResponse']['tbsResponseData']['responses'] = $curr;

    $arrModel = array(
                      'responseStatus'=>'',
                      'responseBytes'=>array(
                                              'response'=>'',
                                              'responseType'=>''
                                              )
                      );
    
    $differ=array_diff_key($arrModel,$ocsp);
    if(count($differ) == 0) {
      $differ=array_diff_key($arrModel['responseBytes'],$ocsp['responseBytes']);
      if(count($differ) > 0) {
        foreach($differ as $key=>$val) {
        }
        return false;
      }
    } else {
      foreach($differ as $key=>$val) {
      }
      return false;
    }
    return $ocsp;
  }

  /**
   * Create ocsp request
   * @param string $serialNumber serial number to check
   * @param string $issuerNameHash sha1 hex form of issuer subject hash
   * @param string $issuerKeyHash sha1 hex form of issuer subject public info hash
   * @param string $signer_cert cert to sign ocsp request
   * @param string $signer_key privkey to sign ocsp request
   * @param string $subjectName hex form of asn1 subject
   * @return string hex form ocsp request
   */
  public static function ocsp_request($serialNumber, $issuerNameHash, $issuerKeyHash, $signer_cert = false, $signer_key = false, $subjectName=false) {
    $Request = false;
    $hashAlgorithm = asn1::seq(
                              "06052B0E03021A". // OBJ_sha1
                              "0500"
                              );
    $issuerNameHash = asn1::oct($issuerNameHash);
    $issuerKeyHash = asn1::oct($issuerKeyHash);
    $serialNumber = asn1::int($serialNumber);
    $CertID = asn1::seq($hashAlgorithm.$issuerNameHash.$issuerKeyHash.$serialNumber);
    $Request = asn1::seq($CertID); // one request
    if($signer_cert) {
      $requestorName = asn1::expl("1", asn1::expl("4", $subjectName));
    } else {
      $requestorName = false;
    }
    $requestList = asn1::seq($Request); // add more request into sequence
    $rand = microtime (true)*rand();
    $nonce = md5(base64_encode($rand).$rand);
    $ReqExts = asn1::seq(
                          '06092B0601050507300102'. // OBJ_id_pkix_OCSP_Nonce
                          asn1::oct("0410".$nonce)
                          );
    $requestExtensions = asn1::expl( "2", asn1::seq(
                                                    $ReqExts
                                                    )
                                    );
    $TBSRequest = asn1::seq($requestorName.$requestList.$requestExtensions);
    $optionalSignature = '';
    if($signer_cert) {
      if(!openssl_sign (hex2bin($TBSRequest), $signature_value, $signer_key)) {
        return false;
        //die("Ora bisa gawe signature maring request");
      }
      $signatureAlgorithm = asn1::seq(
                                      '06092A864886F70D010105'. // OBJ_sha1WithRSAEncryption.
                                      "0500"
                                      );
      $signature = asn1::bit("00".bin2hex($signature_value));
      $signer_cert = x509::x509_pem2der($signer_cert);
      $certs = asn1::expl("0", asn1::seq(bin2hex($signer_cert)));
      $optionalSignature = asn1::expl("0",asn1::seq($signatureAlgorithm.$signature.$certs));
    }
    $OCSPRequest = asn1::seq($TBSRequest.$optionalSignature);
    return $OCSPRequest;
  }

  /**
   * Convert crl from pem to der (binary)
   * @param string $crl pem crl to convert
   * @return string der crl form
   */
  public static function crl_pem2der($crl) {
    $begin = '-----BEGIN X509 CRL-----';
    $end = '-----END X509 CRL-----';
    $beginPos = stripos($crl, $begin);
    if($beginPos===false) {
      return false;
    }
    $crl = substr($crl, $beginPos+strlen($begin));
    $endPos = stripos($crl, $end);
    if($endPos===false) {
      return false;
    }
    $crl = substr($crl, 0, $endPos);
    $crl = str_replace("\n", "", $crl);
    $crl = str_replace("\r", "", $crl);
    $dercrl = base64_decode($crl);
    return $dercrl;
  }
  
  /**
   * Read crl from pem or der (binary)
   * @param string $crl pem or der crl
   * @return array der crl and parsed crl
   */
  public static function crl_read($crl) {
    if(!$crlparse=self::parsecrl($crl)) { // if cant read, thats not crl
      return false;
    }
    if(!$dercrl=self::crl_pem2der($crl)) { // if not pem, thats already der
      $dercrl=$crl;
    }
    $res['der'] = $dercrl;
    $res['parse'] = $crlparse;
    return $res;
  }
  
  /**
   * parsing crl from pem or der (binary)
   * @param string $crl pem or der crl
   * @param string $oidprint option show obj as hex/oid
   * @return array parsed crl
   */
  private static function parsecrl($crl, $oidprint = false) {
    if($derCrl = self::crl_pem2der($crl)) {
      $derCrl = bin2hex($derCrl);
    } else {
      $derCrl = bin2hex($crl);
    }
    
    $curr = asn1::parse($derCrl, 7);
    foreach($curr as $key=>$value) {
      if($value['type'] == '30') {
        $curr['crl']=$curr[$key];
        unset($curr[$key]);
      }
    }
    $ar=$curr;
    if(!array_key_exists('crl', $ar)) {
      return false;
    }
    $curr = $ar['crl'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30' && !array_key_exists('TBSCertList', $curr)) {
          $curr['TBSCertList']=$curr[$key];
          unset($curr[$key]);
        }
        if($value['type'] == '30') {
          $curr['signatureAlgorithm']=self::oidfromhex($value[0]['value_hex']);
          unset($curr[$key]);
        }
        if($value['type'] == '03') {
          $curr['signature']=substr($value['value'], 2);
          unset($curr[$key]);
        }
      } else {
        unset($curr[$key]);
      }
    }
    $ar['crl'] = $curr;

    $curr = $ar['crl']['TBSCertList'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '02') {
          $curr['version']=$curr[$key]['value'];
          unset($curr[$key]);
        }
        if($value['type'] == '30' && !array_key_exists('signature', $curr)) {
          $curr['signature']=$value[0]['value_hex'];
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('issuer', $curr)) {
          $curr['issuer']=$value;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '17' && !array_key_exists('thisUpdate', $curr)) {
          $curr['thisUpdate']=hex2bin($value['value_hex']);
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '17' && !array_key_exists('nextUpdate', $curr)) {
          $curr['nextUpdate']=hex2bin($value['value_hex']);
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('revokedCertificates', $curr)) {
          $curr['revokedCertificates']=$value;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == 'a0') {
          $curr['crlExtensions']=$curr[$key];
          unset($curr[$key]);
        }
      } else {
        unset($curr[$key]);
      }
    }
    $ar['crl']['TBSCertList'] = $curr;
    
    if(array_key_exists('revokedCertificates', $curr)) {
      $curr = $ar['crl']['TBSCertList']['revokedCertificates'];
      foreach($curr as $key=>$value) {
        if(is_numeric($key)) {
          if($value['type'] == '30') {
            $serial = $value[0]['value'];
            $revoked['time']=hex2bin($value[1]['value_hex']);
            $lists[$serial]=$revoked;
            unset($curr[$key]);
          }
        } else {
          unset($curr['depth']);
          unset($curr['type']);
          unset($curr['typeName']);
        }
      }
      $curr['lists'] = $lists;
      $ar['crl']['TBSCertList']['revokedCertificates'] = $curr;
    }
    
    if(array_key_exists('crlExtensions', $ar['crl']['TBSCertList'])) {
      $curr = $ar['crl']['TBSCertList']['crlExtensions'][0];
      unset($ar['crl']['TBSCertList']['crlExtensions']);
      foreach($curr as $key=>$value) {
        if(is_numeric($key)) {
          $attributes_name = self::oidfromhex($value[0]['value_hex']);
          if($oidprint == 'oid') {
            $attributes_name = self::oidfromhex($value[0]['value_hex']);
          }
          if($oidprint == 'hex') {
            $attributes_name = $value[0]['value_hex'];
          }
          $attributes_oid = self::oidfromhex($value[0]['value_hex']);
          if($value['type'] == '30') {
            $crlExtensionsValue = $value[1][0];
            if($attributes_oid  == '2.5.29.20') { // OBJ_crl_number
              $crlExtensionsValue = $crlExtensionsValue['value'];
            }
            if($attributes_oid  == '2.5.29.35') { // OBJ_authority_key_identifier
              foreach($crlExtensionsValue as $authority_key_identifierValueK=>$authority_key_identifierV) {
                if(is_numeric($authority_key_identifierValueK)) {
                  if($authority_key_identifierV['type'] == '80') {
                    $authority_key_identifier['keyIdentifier'] = $authority_key_identifierV['value_hex'];
                  }
                  if($authority_key_identifierV['type'] == 'a1') {
                    $authority_key_identifier['authorityCertIssuer'] = $authority_key_identifierV['value_hex'];
                  }
                  if($authority_key_identifierV['type'] == '82') {
                    $authority_key_identifier['authorityCertSerialNumber'] = $authority_key_identifierV['value_hex'];
                  }
                }
              }
              $crlExtensionsValue = $authority_key_identifier;
            }
            $attribute_list=$crlExtensionsValue;
          }
          $ar['crl']['TBSCertList']['crlExtensions'][$attributes_name] = $attribute_list;
        }
      }
    }

    $curr = $ar['crl']['TBSCertList']['issuer'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '31') {
          if($oidprint == 'oid') {
            $subjOID = self::oidfromhex($curr[$key][0][0]['value_hex']);
          } elseif($oidprint == 'hex') {
            $subjOID = $curr[$key][0][0]['value_hex'];
          } else {
            $subjOID = self::oidfromhex($curr[$key][0][0]['value_hex']);
          }
          $curr[$subjOID][]=hex2bin($curr[$key][0][1]['value_hex']);
          unset($curr[$key]);
          
        }
      } else {
          unset($curr['depth']);
          unset($curr['type']);
          unset($curr['typeName']);
        if($key == 'hexdump') {
           $curr['sha1']=hash('sha1', pack("H*", $value));
        }
      }
    }
    $ar['crl']['TBSCertList']['issuer'] = $curr;

    $arrModel['TBSCertList']['version'] = '';
    $arrModel['TBSCertList']['signature'] = '';
    $arrModel['TBSCertList']['issuer'] = '';
    $arrModel['TBSCertList']['thisUpdate'] = '';
    $arrModel['TBSCertList']['nextUpdate'] = '';
    $arrModel['signatureAlgorithm'] = '';
    $arrModel['signature'] = '';

    $crl = $ar['crl'];
    $differ=array_diff_key($arrModel,$crl);
    if(count($differ) == 0) {
      $differ=array_diff_key($arrModel['TBSCertList'],$crl['TBSCertList']);
      if(count($differ) > 0) {
        foreach($differ as $key=>$val) {
        }
        return false;
      }
    } else {
      foreach($differ as $key=>$val) {
      }
      return false;
    }
    return $ar['crl'];
  }

  /**
   * Convert x509 pem certificate to x509 der
   * @param string $pem pem form cert
   * @return string der form cert
   */
  public static function x509_pem2der($pem) {
    $x509_der = false;
    if($x509_res = @openssl_x509_read($pem)) {
      openssl_x509_export ($x509_res,  $x509_pem);
      $arr_x509_pem = explode("\n", $x509_pem);
      $numarr = count($arr_x509_pem);
      $i=0;
      $cert_pem = false;
      foreach($arr_x509_pem as $val)  {
        if($i > 0 && $i < ($numarr-2))  {
          $cert_pem .= $val;
        }
        $i++;
      }
      $x509_der = base64_decode($cert_pem);
    }
    return $x509_der;
  }
  
  /**
   * Convert x509 der certificate to x509 pem form
   * @param string $der_cert der form cert
   * @return string pem form cert
   */
  public static function x509_der2pem($der_cert) {
    $x509_pem = "-----BEGIN CERTIFICATE-----\r\n";
    $x509_pem .= chunk_split(base64_encode($der_cert),64);
    $x509_pem .= "-----END CERTIFICATE-----\r\n";
    return $x509_pem;
  }

  /**
   * get x.509 DER/PEM Certificate and return DER encoded x.509 Certificate
   * @param string $certin pem/der form cert
   * @return string der form cert
   */
  public static function get_cert($certin) {
    if($rsccert = @openssl_x509_read ($certin)) {
      openssl_x509_export ($rsccert, $cert);
      return self::x509_pem2der($cert);
    } else {
      $pem = @self::x509_der2pem($certin);
      if($rsccert = @openssl_x509_read ($pem)) {
        openssl_x509_export ($rsccert, $cert);
        return self::x509_pem2der($cert);
      } else {
        return false;
      }
    }
  }

  /**
   * parse x.509 DER/PEM Certificate structure
   * @param string $certin pem/der form cert
   * @param string $oidprint show oid as oid number or hex
   * @return array cert structure
   */
  public static function readcert($cert_in, $oidprint=false) {
    if(!$der = self::get_cert($cert_in)) {
      return false;
    }
    $hex = bin2hex($der);
    $curr = asn1::parse($hex,10);
    foreach($curr as $key=>$value) {
      if($value['type'] == '30') {
        $curr['cert']=$curr[$key];
        unset($curr[$key]);
      }
    }
    $ar=$curr;

    $curr = $ar['cert'];
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == '30' && !array_key_exists('tbsCertificate', $curr)) {
          $curr['tbsCertificate']=$curr[$key];
          unset($curr[$key]);
        }
        if($value['type'] == '30') {
          $curr['signatureAlgorithm']=self::oidfromhex($value[0]['value_hex']);
          unset($curr[$key]);
        }
        if($value['type'] == '03') {
          $curr['signatureValue']=substr($value['value'], 2);
          unset($curr[$key]);
        }
      } else {
        unset($curr[$key]);
      }
    }
    $ar['cert'] = $curr;
    $ar['cert']['sha1Fingerprint']=hash('sha1', $der);
    $curr = $ar['cert']['tbsCertificate'];
    $i=0;
    foreach($curr as $key=>$value) {
      if(is_numeric($key)) {
        if($value['type'] == 'a0') {
          $curr['version']=$value[0]['value'];
          unset($curr[$key]);
        }
        if($value['type'] == '02') {
          $curr['serialNumber']=$value['value'];
          unset($curr[$key]);
        }
        if($value['type'] == '30' && !array_key_exists('signature', $curr)) {
          $curr['signature']=$value[0]['value_hex'];
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('issuer', $curr)) {
          foreach($value as $issuerK=>$issuerV) {
            if(is_numeric($issuerK)) {
              $issuerOID = $issuerV[0][0]['value_hex'];
              if($oidprint == 'oid') {
                $issuerOID = self::oidfromhex($issuerOID);
              } elseif($oidprint == 'hex') {
              } else {
                $issuerOID = self::oidfromhex($issuerOID);
              }
              $issuer[$issuerOID][] = hex2bin($issuerV[0][1]['value_hex']);
            }
          }
          $hexdump = $value['hexdump'];
          $issuer['sha1'] = hash('sha1', hex2bin($hexdump));
          $issuer['opensslHash'] = self::opensslSubjHash($hexdump);
          $issuer['hexdump'] = $hexdump;

          $curr['issuer']=$issuer;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('validity', $curr)) {
          $curr['validity']['notBefore']=hex2bin($value[0]['value_hex']);
          $curr['validity']['notAfter']=hex2bin($value[1]['value_hex']);
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('subject', $curr)) {
          $asn1SubjectToHash = '';
          foreach($value as $subjectK=>$subjectV) {
            if(is_numeric($subjectK)) {
              $subjectOID = $subjectV[0][0]['value_hex'];
              if($oidprint == 'oid') {
                $subjectOID = self::oidfromhex($subjectOID);
              } elseif($oidprint == 'hex') {
              } else {
                $subjectOID = self::oidfromhex($subjectOID);
              }
              $subject[$subjectOID][] = hex2bin($subjectV[0][1]['value_hex']);
            }
          }
          $hexdump = $value['hexdump'];
          $subject['sha1'] = hash('sha1', hex2bin($hexdump));
          $subject['opensslHash'] = self::opensslSubjHash($hexdump);
          $subject['hexdump'] = $hexdump;
          
          $curr['subject']=$subject;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == '30' && !array_key_exists('subjectPublicKeyInfo', $curr)) {
          foreach($value as $subjectPublicKeyInfoK=>$subjectPublicKeyInfoV) {
            if(is_numeric($subjectPublicKeyInfoK)) {
              if($subjectPublicKeyInfoV['type'] == '30') {
                $subjectPublicKeyInfo['algorithm']=self::oidfromhex($subjectPublicKeyInfoV[0]['value_hex']);
              }
              if($subjectPublicKeyInfoV['type'] == '03') {
                $subjectPublicKeyInfo['subjectPublicKey']=substr($subjectPublicKeyInfoV['value'], 2);
              }
            } else {
              unset($curr[$key]);
            }
          }
          $subjectPublicKeyInfo['hex']=$value['hexdump'];
          $subjectPublicKey_parse =asn1::parse($subjectPublicKeyInfo['subjectPublicKey']);
          $subjectPublicKeyInfo['keyLength']=(strlen(substr($subjectPublicKey_parse[0][0]['value'], 2))/2)*8;
          $subjectPublicKeyInfo['sha1']=hash('sha1', pack('H*', $subjectPublicKeyInfo['subjectPublicKey']));

          $curr['subjectPublicKeyInfo']=$subjectPublicKeyInfo;
          unset($curr[$key]);
          continue;
        }
        if($value['type'] == 'a3') {
          $curr['attributes']=$value[0];
          unset($curr[$key]);
        }
        $i++;
      } else {
        $tbsCertificateTag[$key]=$value;
      }
    }
    $ar['cert']['tbsCertificate'] = $curr;

    if(array_key_exists('attributes', $ar['cert']['tbsCertificate'])) {
      $curr = $ar['cert']['tbsCertificate']['attributes'];
      foreach($curr as $key=>$value) {
        if(is_numeric($key)) {
          if($value['type'] == '30') {
            $critical = 0;
            $extvalue = $value[1];
            $name_hex = $value[0]['value_hex'];
            $value_hex = $value[1]['hexdump'];

            if($value[1]['type'] == '01' && $value[1]['value_hex'] == 'ff') {
              $critical = 1;
              $extvalue = $value[2];
            }
            if($name_hex == '551d0e') { // OBJ_subject_key_identifier
              $extvalue = $value[1][0]['value_hex'];
            }
            if($name_hex == '551d23') { // OBJ_authority_key_identifier
              foreach($value[1][0] as $OBJ_authority_key_identifierKey=>$OBJ_authority_key_identifierVal) {
                if(is_numeric($OBJ_authority_key_identifierKey)) {
                  if($OBJ_authority_key_identifierVal['type'] == '80') {
                    $OBJ_authority_key_identifier['keyid'] = $OBJ_authority_key_identifierVal['value_hex'];
                  }
                  if($OBJ_authority_key_identifierVal['type'] == 'a1') {
                    $OBJ_authority_key_identifier['issuerName'] = $OBJ_authority_key_identifierVal['value_hex'];
                  }
                  if($OBJ_authority_key_identifierVal['type'] == '82') {
                    $OBJ_authority_key_identifier['issuerSerial'] = $OBJ_authority_key_identifierVal['value_hex'];
                  }
                }
              }
              $extvalue = $OBJ_authority_key_identifier;
            }
            if($name_hex == '2b06010505070101') { // OBJ_info_access
              foreach($value[1][0] as $OBJ_info_accessK=>$OBJ_info_accessV) {
                if(is_numeric($OBJ_info_accessK)) {
                  $OBJ_info_accessHEX = $OBJ_info_accessV[0]['value_hex'];
                  $OBJ_info_accessOID = self::oidfromhex($OBJ_info_accessHEX);
                  $OBJ_info_accessNAME = $OBJ_info_accessOID;
                  $OBJ_info_access[$OBJ_info_accessNAME][] = hex2bin($OBJ_info_accessV[1]['value_hex']);
                }
              }
              $extvalue = $OBJ_info_access;
            }
            if($name_hex == '551d1f') { // OBJ_crl_distribution_points 551d1f
              foreach($value[1][0] as $OBJ_crl_distribution_pointsK=>$OBJ_crl_distribution_pointsV) {
                if(is_numeric($OBJ_crl_distribution_pointsK)) {
                  $OBJ_crl_distribution_points[] = hex2bin($OBJ_crl_distribution_pointsV[0][0][0]['value_hex']);
                }
              }
              $extvalue = $OBJ_crl_distribution_points;
            }
            if($name_hex == '551d0f') { // OBJ_key_usage
              // $extvalue = self::parse_keyUsage($extvalue[0]['value']);
            }
            if($name_hex == '551d13') { // OBJ_basic_constraints
              $bc['ca'] = '0';
              $bc['pathLength'] = '';
              foreach($extvalue[0] as $bck=>$bcv) {
                if(is_numeric($bck)) {
                  if($bcv['type'] == '01') {
                    if($bcv['value_hex'] == 'ff') {
                      $bc['ca'] = '1';
                    }
                  }
                  if($bcv['type'] == '02') {
                    $bc['pathLength'] = $bcv['value'];
                  }
                }
              }
              $extvalue = $bc;
            }
            if($name_hex == '551d25') { // OBJ_ext_key_usage 551d1f
              foreach($extvalue[0] as $OBJ_ext_key_usageK=>$OBJ_ext_key_usageV) {
                if(is_numeric($OBJ_ext_key_usageK)) {
                  $OBJ_ext_key_usageHEX = $OBJ_ext_key_usageV['value_hex'];
                  $OBJ_ext_key_usageOID = self::oidfromhex($OBJ_ext_key_usageHEX);
                  $OBJ_ext_key_usageNAME = $OBJ_ext_key_usageOID;
                  $OBJ_ext_key_usage[] = $OBJ_ext_key_usageNAME;
                }
              }
              $extvalue = $OBJ_ext_key_usage;
            }
            
            $extsVal=array(
                            'name_hex'=>$value[0]['value_hex'],
                            'name_oid'=>self::oidfromhex($value[0]['value_hex']),
                            'name'=>self::oidfromhex($value[0]['value_hex']),
                            'critical'=>$critical,
                            'value'=>$extvalue
                            );
            
            $extNameOID = $value[0]['value_hex'];
            if($oidprint == 'oid') {
              $extNameOID = self::oidfromhex($extNameOID);
            } elseif($oidprint == 'hex') {
            } else {
              $extNameOID = self::oidfromhex($extNameOID);
            }
            $curr[$extNameOID] = $extsVal;
            unset($curr[$key]);
          }
        } else {
          unset($curr[$key]);
        }
        unset($ar['cert']['tbsCertificate']['attributes']);
        $ar['cert']['tbsCertificate']['attributes'] = $curr;
      }
    }
    return $ar['cert'];
  }

  /**
   * read oid number of given hex
   * @param string $hex hex form oid number
   * @return string oid number
   */
  private static function oidfromhex($hex) {
    $split = str_split($hex, 2);
    $i = 0;
    foreach($split as $val) {
      $dec = hexdec($val);
      $mplx[$i] = ($dec-128)*128;
      $i++;
    }
    $i = 0;
    $nex = false;
    $result = false;
    foreach($split as $val) {
      $dec = hexdec($val);
      if($i == 0) {
        if($dec >= 128) {
          $nex = (128*($dec-128))-80;
          if($dec > 129) {
            $nex = (128*($dec-128))-80;
          }
          $result = "2.";
        }
        if($dec >= 80 && $dec < 128) {
          $first = $dec-80;
          $result = "2.$first.";
        }
        if($dec >= 40 && $dec < 80) {
          $first = $dec-40;
          $result = "1.$first.";
        }
        if($dec < 40) {
          $first = $dec-0;
          $result = "0.$first.";
        }
      } else {
        if($dec > 127) {
          if($nex == false) {
            $nex = $mplx[$i];
          } else {
            $nex = ($nex*128)+$mplx[$i];
          }
        } else {
          $result .= ($dec+$nex).".";
          if($dec <= 127) {
            $nex = 0;
          }
        }
      }
      $i++;
    }
    return rtrim($result, ".");
  }
}

/**
 * @class tcpdf_asn1
 * Asn.1 encode/decode
 * @version 1.1
 * @author M Hida
 */
class asn1 {
  // =====Begin ASN.1 Parser section=====
  /**
   * get asn.1 type tag name
   * @param string $id hex asn.1 type tag
   * @return string asn.1 tag name
   * @protected
   */
  protected static function type($id) {
    $asn1_Types = array(
    "00" => "ASN1_EOC",
    "01" => "ASN1_BOOLEAN",
    "02" => "ASN1_INTEGER",
    "03" => "ASN1_BIT_STRING",
    "04" => "ASN1_OCTET_STRING",
    "05" => "ASN1_NULL",
    "06" => "ASN1_OBJECT",
    "07" => "ASN1_OBJECT_DESCRIPTOR",
    "08" => "ASN1_EXTERNAL",
    "09" => "ASN1_REAL",
    "0a" => "ASN1_ENUMERATED",
    "0c" => "ASN1_UTF8STRING",
    "30" => "ASN1_SEQUENCE",
    "31" => "ASN1_SET",
    "12" => "ASN1_NUMERICSTRING",
    "13" => "ASN1_PRINTABLESTRING",
    "14" => "ASN1_T61STRING",
    "15" => "ASN1_VIDEOTEXSTRING",
    "16" => "ASN1_IA5STRING",
    "17" => "ASN1_UTCTIME",
    "18" => "ASN1_GENERALIZEDTIME",
    "19" => "ASN1_GRAPHICSTRING",
    "1a" => "ASN1_VISIBLESTRING",
    "1b" => "ASN1_GENERALSTRING",
    "1c" => "ASN1_UNIVERSALSTRING",
    "1d" => "ASN1_BMPSTRING"
    );
    return array_key_exists($id,$asn1_Types)?$asn1_Types[$id]:$id;
  }

  /**
   * parse asn.1 to array
   * to be called from parse() function
   * @param string $hex asn.1 hex form
   * @return array asn.1 structure
   * @protected
   */
  protected static function oneParse($hex) {
    if($hex == '') {
      return false;
    }
    if(!@ctype_xdigit($hex) || @strlen($hex)%2!=0) {
      // echo "input:\"$hex\" not hex string!.\n";
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
  public static function parse($hex, $maxDepth=5) {
    $result = array();
    static $currentDepth = 0;
    if($asn1parse_array = self::oneParse($hex)) {
      foreach($asn1parse_array as $ff){
        $parse_recursive = false;
        unset($info);
        $k = $ff['typ'];
        $v = $ff['tlv_value'];
        $info['depth']=$currentDepth;
        $info['hexdump']=$ff['newhexdump'];
        $info['type'] = $k;  
        $info['typeName'] = self::type($k);  
        $info['value_hex'] = $v;  
        if(($currentDepth <= $maxDepth)) {
          if($k == '06') {
            
          } else if($k == '13' || $k == '18') {
            $info['value'] = hex2bin($info['value_hex']);
          } else if($k == '03' || $k == '02' || $k == 'a04') {
            $info['value'] = $v;
          } else if($k == '05') {
            
          } else if($k == '01') {

          } else {
            $currentDepth++;
            $parse_recursive = self::parse($v, $maxDepth);
            $currentDepth--;
          }
          if($parse_recursive) {
            $result[] = array_merge($info, $parse_recursive);
          } else {
            $result[] = $info;
          }
        }
      }
    }
    return $result;
  }
  // =====End ASN.1 Parser section=====

  // =====Begin ASN.1 Builder section=====
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
   * Create asn.1 SEQUENCE
   * @param string $hex hex value of asn.1 SEQUENCE
   * @return tring hex of asn.1 SEQUENCE tag with value
   * @public
   */
  public static function SEQ($hex) {
    $ret = "30".self::asn1_header($hex).$hex;
    return $ret;
  }

  /**
   * Create asn.1 OCTET
   * @param string $hex hex value of asn.1 OCTET
   * @return string hex of asn.1 OCTET tag with value
   * @public
   */
  public static function OCT($hex)  {
    $ret = "04".self::asn1_header($hex).$hex;
    return $ret;
  }

  /**
   * Create asn.1 OBJECT
   * @param string $hex hex value of asn.1 OBJECT
   * @return string hex of asn.1 OBJECT tag with value
   * @public
   */
  public static function OBJ($hex)  {
    $ret = "06".self::asn1_header($hex).$hex;
    return $ret;
  }

  /**
   * Create asn.1 BITString
   * @param string $hex hex value of asn.1 BITString
   * @return string hex of asn.1 BITString tag with value
   * @public
   */
  public static function BIT($hex)  {
    $ret = "03".self::asn1_header($hex).$hex;
    return $ret;
  }

  /**
   * Create asn.1 INTEGER
   * @param string $int number value of asn.1 INTEGER
   * @return string hex of asn.1 INTEGER tag with value
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
   * Create asn.1 SET tag
   * @param string $hex hex value of asn.1 SET
   * @return string hex of asn.1 SET with value
   * @public
   */
  public static function SET($hex)  {
    $ret = "31".self::asn1_header($hex).$hex;
    return $ret;
  }

  /**
   * Create asn.1 EXPLICIT
   * @param string $num value of asn.1 EXPLICIT number
   * @param string $hex value of asn.1 EXPLICIT
   * @return string hex of asn.1 EXPLICIT with value
   * @public
   */
  public static function EXPL($num, $hex)  {
    $ret = "a$num".self::asn1_header($hex).$hex;
    return $ret;
  }

	/**
	 * Create asn.1 UTCTIME
	 * @param string $time string value of asn.1 UTCTIME date("ymdHis")
	 * @return string hex of asn.1 UTCTIME tag with value
	 * @public
	 */
	public static function UTIME($time) {
		$ret = "170d".bin2hex($time)."5a";
		return $ret;
	}

  /**
   * Create asn.1 UTF8String
   * @param string $str string value of asn.1 UTF8String
   * @return string hex of asn.1 UTF8String tag with value
   * @public
   */
  public static function UTF8($str) {
    $ret = "0c".self::asn1_header(bin2hex($str)).bin2hex($str);
    return $ret;
  }
  // =====End ASN.1 Builder section=====
}
?>