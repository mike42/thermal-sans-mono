<?php
// This is a (hopefully) temporary wrapper until we have a better CLI for the
// font scale app.
if(count($argv) != 8) {
  die("Usage " . $argv[0] . " 0030 outp.pbm inp16px.pbm rules.csv unifont.hex 12x24 20\n");
}

$codePoint = basename($argv[1], ".bold");
$outpFile = str_replace(".bold.pbm", ".pbm", $argv[2]);
$inpFile = $argv[3];
$rulesFile = $argv[4];
$unifontFile = $argv[5];
$dimensions = $argv[6];
$strokeWidth = $argv[7];

// Extract unifont hex
$hexVal = trim(`grep ^$codePoint $unifontFile | cut -d':' -f2`);

// Extract geometries
$csvLine = trim(`grep ^$codePoint $rulesFile`);
$rulesFields = explode(",", $csvLine);
$srcGeometry = $rulesFields[3];
$dstGeometry = $rulesFields[4];
$traceOptions = $rulesFields[2];
$jarPath = dirname(__FILE__) . "/fontscale-utils.jar";
$cmd = "java -jar $jarPath scale $hexVal $srcGeometry $dimensions $dstGeometry $outpFile $traceOptions";
echo $cmd ."\n";
system($cmd, $retval);
if($retval != 0) {
  exit($retval);
}

// Flip over to bold glyph renderer
$scriptPath = dirname(__FILE__) . "/svg2pbm.php";
$pi = pathinfo($outpFile);
$targetSvg = $outpFile . "-unscaled.svg";
$targetBoldLargeColoured = $pi['dirname'] . "/" . $pi['filename'] . ".bold.large.png";
$targetBoldLarge = $pi['dirname'] . "/" . $pi['filename'] . ".bold.large.pbm";
$targetBold = $pi['dirname'] . "/" . $pi['filename'] . ".bold.pbm";
$cmd = "php $scriptPath $targetSvg $strokeWidth $targetBoldLargeColoured $targetBoldLarge $targetBold $dimensions $srcGeometry $dstGeometry";
echo $cmd ."\n";
system($cmd, $retval);
if($retval != 0) {
  exit($retval);
}
