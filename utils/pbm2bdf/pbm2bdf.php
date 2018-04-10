<?php
# php ./utils/pbm2bdf/pbm2bdf.php     build/bdf/17px/inputs-unsorted.txt 17 outp/thermal-sans-mono-17/thermal-sans-mono-17.bdf

require_once(__DIR__ . "/../../../image-php/vendor/autoload.php");
use Mike42\ImagePhp\Image;

if(count($argv) != 4) {
  die("Usage: " . $argv[0] . " infiles.txt height outfile.bdf");
}

$inpFilename = $argv[1];
$height = $argv[2];
$outFile = $argv[3];

// Index by code point. (straight from montage-sort.php)
$inpStr = file_get_contents($inpFilename);
$fileList = preg_split('/\s+/', $inpStr);
$codePoint = [];
foreach($fileList as $file) {
  $fn = trim($file);
  $thisCodePoint = basename(basename($fn, ".pbm"), ".bold");
  if(trim($thisCodePoint) == "") {
    continue;
  }
  $codePoint[$thisCodePoint] = $fn;
}

$glyphCount = count($codePoint);
if($height == 17) {
  $width = 9;
  $widthDouble = 18;
  $bitmap = "FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80
FF80";
} else if($height == 24) {
  $width = 12;
  $widthDouble = 24;
  $bitmap = "FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0
FFF0";
} else {
  die("Unsupported font size. :(");
}

$f = fopen($outFile, "wb");
fwrite($f, "STARTFONT 2.1
FONT -misc-ThermalSansMono-Medium-R-Normal-Sans-$height-${height}0-75-75-c-${width}0-iso10646-1
SIZE $height 72 72
FONTBOUNDINGBOX $widthDouble $height $ 0 -2
STARTPROPERTIES 24
COPYRIGHT \"Copyright (C) 2018 Michael Billington, derived from Unifont, Copyright (C) 1998-2017 Roman Czyborra, Paul Hardy, Qianqian Fang, Andrew Miller, Johnnie Weaver, David Corbett, et al. License GPLv2+: GNU GPL version 2 or later <http://gnu.org/licenses/gpl.html> with the GNU Font Embedding Exception.\"
FONT_VERSION \"10.0.07\"
FONT_TYPE \"Bitmap\"
FOUNDRY \"GNU\"
FAMILY_NAME \"Thermal Sans Mono\"
WEIGHT_NAME \"Medium\"
SLANT \"R\"
SETWIDTH_NAME \"Normal\"
ADD_STYLE_NAME \"Sans Serif\"
PIXEL_SIZE ${height}
POINT_SIZE ${height}0
RESOLUTION_X 72
RESOLUTION_Y 72
SPACING \"C\"
AVERAGE_WIDTH ${width}0
CHARSET_REGISTRY \"ISO10646\"
CHARSET_ENCODING \"1\"
UNDERLINE_POSITION -2
UNDERLINE_THICKNESS 1
CAP_HEIGHT $height
X_HEIGHT $height
FONT_ASCENT $height
FONT_DESCENT 0
DEFAULT_CHAR 65533
ENDPROPERTIES
CHARS $glyphCount\n");

// Iterate all code points we expect, in batches of 256
for($i = 0 ; $i <= 65535; $i++ ) {
  $thisCodePoint = str_pad(strtoupper(dechex($i)), 4, "0", STR_PAD_LEFT);
  if(!isset($codePoint[$thisCodePoint])) {
    continue;
  }
  // Print each glyph
  $glyphBitmapFile = $codePoint[$thisCodePoint];
  $img = Image::fromFile($glyphBitmapFile);
  echo "U+$thisCodePoint:\n";
  echo $img -> toString();
  // Extract binary data in hex-encoded format.
  $glyphWidth = $img -> getWidth();
  $glyphHeight = $img -> getHeight();
  $glyphDataBin = $img -> getRasterData();
  $bytesPerLine = intdiv($glyphWidth + 7, 8);
  $glyphDataHex = strtoupper(bin2hex($glyphDataBin));
  $glyphDataHexLines = implode("\n", str_split($glyphDataHex, $bytesPerLine * 2));

fwrite($f, "STARTCHAR U+$thisCodePoint
ENCODING $i
SWIDTH 500 0
DWIDTH $width 0
BBX $glyphWidth $glyphHeight 0 0
BITMAP 
$glyphDataHexLines
ENDCHAR\n");

}

// End font
fwrite($f, "ENDFONT\n");
fclose($f);

