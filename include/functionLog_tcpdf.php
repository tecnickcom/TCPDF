<?php
// create 11:16 AM 10/17/2011
function tsaLog($str, $type = 'i', $nl=true) { // 11:16 AM 10/17/2011
  $dateFormat = date("D M d Y H:i:s");
  switch($type) {
    case 'e' : $errType = 'error'; break;
    case 'w' : $errType = 'warning'; break;
    case 'i' : $errType = 'info'; break;
    case 'n' : $errType = 'info'; break;
    default : $errType = 'notice';
  }
  $clientIpAddress = $_SERVER['REMOTE_ADDR'];
  $clientHostName = gethostbyaddr($_SERVER['REMOTE_ADDR']);
  if($clientHostName == $clientIpAddress) {
    $clientHostName = 'unknownHost';
  }
  
  $prependLog = "[$dateFormat] [$errType] [client $clientHostName ($clientIpAddress)]";
  $explodeStr = explode("\n", $str);
  $prependLogLen = strlen($prependLog);
  $prependLogIdent = str_repeat(' ', $prependLogLen+1);
  $newLine = false;
  if($nl) {
    $newLine = "\r\n";
  }
  $strLog = false;
  foreach($explodeStr as $lineNum=>$strLine) {
    if($lineNum == 0) {
      $strLog .= rtrim($strLine)."\r\n";
    } else {
      $strLog .= $prependLogIdent.rtrim($strLine).$newLine;
    }
  }
  $log = "$prependLog $strLog";
  if(is_writable(getcwd().'/tcpdf_tsa.log')) {
    $handle = fopen(getcwd().'/tcpdf_tsa.log', 'a');
    fwrite($handle, $log);
    fclose($handle);
  } else {
    $handle = @fopen(getcwd().'/tcpdf_tsa.log', 'a');
    fwrite($handle, $log);
    fclose($handle);
    if($type == 'e') {
      echo "<pre>\nCan't write log to file \"hdaLogs.log\", please check file permission. hdaLog return error, these error is:\n";
      echo "$log\n</pre>";
    }
  }
}
?>