<?php
/**
 * @file
 * This is a PHP function for parsing and creating ASN.1 syntax for handle minimum timeStamp request and response.
 * @author Hida
 * @version 1.0.1
 * Last Update : 17/05/2023
 */
function asn1_first($hex) {
  $asn1_Id = substr($hex, 0, 2);
  $header = substr($hex, 2, 2);
  if($asn1_Id == 'bf') {
    if(hexdec($header) > 128) {
      $headerLength = hexdec(substr($hex, 6, 2));
      $reduced = 8; // the string reduced by id & headerLength
      $expNum = (128*(hexdec($header)-128))+hexdec(substr($hex, 4, 2));
      $header2 = substr($hex, 4, 2);
      if(hexdec($header2) >= 128) {
        $headerLength = hexdec(substr($hex, 8, 2));
        $reduced = 10;
        $expNum = (16384*(hexdec($header)-128))+(128*(hexdec($header2)-128))+hexdec(substr($hex, 6, 2));
      }
    } else {
      $headerLength = hexdec(substr($hex, 4, 2));
      $reduced = 6;
      $expNum = hexdec(substr($hex, 2, 2));
    }
    $asn1_Id = "EXP:"."$expNum";
  } else {
    //echo "$header==";
    if($header == '83') {
      $headerLength = hexdec(substr($hex, 4, 6));
      $reduced = 10;
    } elseif ($header == '82') {
      $headerLength = hexdec(substr($hex, 4, 4));
      $reduced = 8;
    } elseif ($header == '81') {
      $headerLength = hexdec(substr($hex, 4, 2));
      $reduced = 6;
    } else {
      $l=0;
      $l = hexdec(substr($hex, 2, 2));
      $headerLength = $l;
      $reduced = 4;
    //echo "$headerLength --".substr($hex, 2, 2)."--<br>";
    
    }
  }
  $str_remains = substr($hex, $reduced+($headerLength*2));
  $content = substr($hex, $reduced, $headerLength*2);
  $return['res'] = array($asn1_Id, $content); // array 0=>iD(sequence be 30, integer be 02, etc) 1=>contents of id
  $return['rem'] = $str_remains; // the remain string returned
  if($str_remains == '' && $content == '') { // if remains string was empty & contents also empty, function return FALSE
    $return = false;
  }
  return $return;
}

function asn1parse($hex) {
  //$return =false;
  while(asn1_first($hex) != false) { // while asn1_first() still return string
    $r = asn1_first($hex);
    $return[] = array($r['res'][0],$r['res'][1]);
    $hex = $r['rem']; // $hex now be result of asn1_first()
  }
  if(!is_array(@$return)) {
		return false;
  }
  return $return;
}

function asn1_header($str) {
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

function SEQ($hex)  {
  $ret = "30".asn1_header($hex).$hex;
  return $ret;
}
function OCT($hex)  {
  $ret = "04".asn1_header($hex).$hex;
  return $ret;
}
function INT($int)  {
  if(strlen($int)%2 != 0)  {
    $int = "0$int";
  }
  $int = "$int";
  $ret = "02".asn1_header($int).$int;
  return $ret;
}
function SET($hex)  {
  $ret = "31".asn1_header($hex).$hex;
  return $ret;
}
//function EXPLICIT($num="0", $hex)  {
function EXPLICIT($num, $hex)  {
  $ret = "a$num".asn1_header($hex).$hex;
  return $ret;
}
?>