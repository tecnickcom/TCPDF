<?php
// ASN.1 Parser start 21:31 Sore Kamis 26 Maret 2009
// ASN.1 Parser at 22:10 Sore Kamis 26 Maret 2009 Telah jadi utk standar asn.1
// 
// 06:40 Esuk Jumat 27 Maret 2009 ASN.1 Parser kesulitan dlm memecahkan explicit > 9

// 11:18 Esuk Jumat 27 Maret 2009 parse explicit:xx mulai dipecahkan. kemungkinan tlh jadi
// 17:51 Sore Jumat 27 Maret 2009 memecahkan explicit sampai 2097151 (65536 * 32) kurang 1

// 20:04 Sore Jumat 27 Maret 2009 ASN.1 Parser tlh jadi. Congratulation....
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
?>