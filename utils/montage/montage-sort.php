<?php
if(count($argv) != 4) {
  die("Usage: " . $argv[0] . " unsorted.txt montage-command-list.txt 16x16+0+0\n");
}

// Index by code point..
$inpFilename = $argv[1];
$outpFilename = $argv[2];
$geometry = $argv[3];
$inpStr = file_get_contents($inpFilename);
$fileList = preg_split('/\s+/', $inpStr);
$codePoint = [];
foreach($fileList as $file) {
  $fn = trim($file);
  $thisCodePoint = basename(basename($fn, ".pbm"), ".bold");
  $codePoint[$thisCodePoint] = $fn;
}

// Iterate all code points we expect, in batches of 256
$outpArray = [];
$foundGlyphs = $missedGlyphs = 0;
$fileList = [];
for($i = 0 ; $i <= 65535; $i++ ) {
  $thisCodePoint = str_pad(strtoupper(dechex($i)), 4, "0", STR_PAD_LEFT);
  if(isset($codePoint[$thisCodePoint])) {
    $foundGlyphs++;
    $fileList[] = $codePoint[$thisCodePoint];
  } else {
    $missedGlyphs++;
    $fileList[] = "null:";
  }
  if($i % 256 == 255) {
    $outpArray[] = "montage -geometry $geometry -tile 256x1 " . implode(" ", $fileList) . " " . dirname($outpFilename) . "/row-to-".$thisCodePoint.".pbm";
    $fileList = [];
  }
}
$outpStr = implode("\n", $outpArray);
// Status
echo "Found $foundGlyphs glyphs, leaving blank spaces for $missedGlyphs others.\n";

// Place back in input file
file_put_contents($outpFilename, $outpStr);

