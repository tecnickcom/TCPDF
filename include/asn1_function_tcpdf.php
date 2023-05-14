<?php
// change at 22:37 Sore 04/09/2009
// change at 16:04 Sore 14/05/2023
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