<?php
if(count($argv) != 4) {
  die("Usage: " . $argv[0] . " unifont.hex 00FF foo/out.pbm");
}

$unifont = $argv[1];
$searchPoint = $argv[2];
$outpFile = $argv[3];

$lines = explode("\n", file_get_contents($unifont));
$found = false;
foreach($lines as $unifontLine) {
  $p = strpos($unifontLine, ':');
  if($p === FALSE) {
    continue;
  }
  $codePoint = substr($unifontLine, 0, $p);
  if($codePoint !== $searchPoint) {
    continue;
  }
  $binStr = pack("H*", substr($unifontLine, $p + 1));
  $bytes = strlen($binStr);
  if($bytes == 32) {
      $width = 16;
  } else if($bytes == 16) {
      $width = 8;
  } else {
    throw new Exception("$codePoint not 32 or 16 bytes");
  }
  file_put_contents($outpFile, "P4\n$width 16\n$binStr");
  $found = true;
}
if(!$found) {
  echo "Code point $searchPoint not fount in $unifont";
  exit(1);
}
