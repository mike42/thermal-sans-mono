<?php

class Point {
  protected $x;
  protected $y;
  
  public function __construct(float $x, float $y) {
    $this -> x = $x;
    $this -> y = $y;
  }

  public function getX() {
    return $this -> x;
  }

  public function getY() {
    return $this -> y;
  }

  public function map(Geometry $src, Geometry $dst) {
    $newX = $this -> transformVal($this -> x - $src -> getOffsetX(), $src -> getWidth(), $dst -> getWidth()) + $dst -> getOffsetX();
    $newY = $this -> transformVal($this -> y - $src -> getOffsetY(), $src -> getHeight(), $dst -> getHeight()) + $dst -> getOffsetY();
    return new Point($newX, $newY);
  }

  private function transformVal(float $val, float $oldSize, float $newSize) {
    // Linear interpolation: 0 => 0, (oldWidth - 1) => (newWidth - 1).
    return (float)($val * ($newSize - 1) / ($oldSize - 1));
  }

  public function toString() {
    $fmt = "% 6.2f";
    return "(" . sprintf($fmt, $this -> x) . ", " . sprintf($fmt, $this -> y) . ")";
  }
}

class Geometry {
  protected $width;
  protected $height;
  protected $offsetX;
  protected $offsetY;

  public function __construct(float $width, float $height, float $offsetX = null, float $offsetY = null) {
    $this -> width = $width;
    $this -> height = $height;
    $this -> offsetX = $offsetX === null ? 0 : $offsetX;
    $this -> offsetY = $offsetY === null ? 0 : $offsetY;
  }

  public function getWidth() {
    return $this -> width;
  }

  public function getHeight() {
    return $this -> height;
  }

  public function getOffsetX() {
    return $this -> offsetX;
  }

  public function getOffsetY() {
    return $this -> offsetY;
  }

  public static function fromString($str) {
    $parts = preg_split("/[x\\+]/", $str);
    $width = (float)$parts[0];
    $height = (float)$parts[1];
    if(count($parts) > 2) {
      $offsetX = (float)$parts[2];
      $offsetY = (float)$parts[3];
    } else {
      $offsetX = (float)0;
      $offsetY = (float)0;
    }
    return new Geometry($width, $height, $offsetX, $offsetY);
  }

  public function mul(float $val) {
    $this -> width *= $val;
    $this -> height *= $val;
    $this -> offsetX *= $val;
    $this -> offsetY *= $val;
  }

  public function toString() {
    return $this -> width . "x" . $this -> height . "+" . $this -> offsetX . "+" . $this -> offsetY;
  }
}

/*
 * Grep an SVG that was outputted for debugging purposes, then render a bold
 * version of the glyph (which fontscale-utils is not very good at).
 */
if(count($argv) != 9) {
  die("Usage: " . $argv[0] . " in.svg stroke-width out-large.png out-large.pbm out.pbm dimensions inpGeo outpGeo\n");
}
$in = $argv[1];
$strokeWidth = (int)$argv[2];
$outLargePng = $argv[3];
$outLargePbm = $argv[4];
$outSmallPbm = $argv[5];
$dimensions = $argv[6];
$inpGeo = $argv[7];
$outpGeo = $argv[8];

// Extract lines, dimensions
$inpWidth = `xmllint $in --xpath 'string(/*/@width)'`;
$inpHeight = `xmllint $in --xpath 'string(/*/@height)'`;
$data = `xmllint $in  -format | grep line | cut -d' ' -f4-7 | tr -d '"'`;
$linedefs = explode("\n", trim($data));
$lines = [];
foreach($linedefs as $linedef) {
  $linedefParts = explode(" ", $linedef);
  $line = [];
  foreach($linedefParts as $part) {
    $partPart = explode("=", $part);
    $key = $partPart[0];
    $val = $partPart[1];
    $line[$key] = (float)$val;
  }
  $lines[] = $line;
}

// Determine basic geometry we are aiming for. Note that the debug SVG is scaled up 10x.
$inputGeometry = new Geometry((float)$inpWidth, (float)$inpHeight);
$outputGeometry = Geometry::fromString($dimensions);
$outputGeometry -> mul((float)10);
$width = $outputGeometry -> getWidth();
$height = $outputGeometry -> getHeight();
// SOURCE region geometry
if($inpGeo == "detect" && count($lines) > 0) {
  // Source geometry based on glyph size. There is some strange maths here to
  // account for the lines in the SVG being in the middle of each 10px box.
  $maxX = (float)-9999;
  $maxY = (float)-9999;
  $minX = (float)99999;
  $minY = (float)99999;
  foreach($lines as $line) {
    $maxX = max($maxX, $line['x1'], $line['x2']);
    $minX = min($minX, $line['x1'], $line['x2']);
    $maxY = max($maxY, $line['y1'], $line['y2']);
    $minY = min($minY, $line['y1'], $line['y2']);
  }
  $internalWidth = ($maxX - $minX) + 10;
  $internalHeight = ($maxY - $minY) + 10;
  $srcGeometry = new Geometry((float)$internalWidth, (float)$internalHeight, (float)($minX - 5), (float)($minY - 5));
} else if($inpGeo != "full") {
  // Custom source geometry
  $srcGeometry = Geometry::fromString($inpGeo);
  $srcGeometry -> mul(10);
} else {
  // Full input as source geometry
  $srcGeometry = $inputGeometry;
}
// DEST region geometry
if($outpGeo == "full") {
  $destGeometry = $outputGeometry;
} else {
  $destGeometry = Geometry::fromString($outpGeo);
  $destGeometry -> mul(10);
}
// Quick status update
echo "Mapping lines from canvas " . $inputGeometry -> toString() . " to canvas " . $outputGeometry -> toString() ."\n";
echo "  Source region: " . $srcGeometry -> toString() . "\n";
echo "  Dest region: " . $destGeometry -> toString() . "\n";

// map lines to new size
$mappedLines = [];
foreach($lines as $line) {
  $from = new Point($line['x1'], $line['y1']);
  $fromMapped = $from -> map($srcGeometry, $destGeometry);
  $to = new Point($line['x2'], $line['y2']);
  $toMapped = $to -> map($srcGeometry, $destGeometry);
  echo "Mapped line " . $from -> toString() . "->" . $to -> toString() . " to " . $fromMapped -> toString() . "->" . $toMapped -> toString() . "\n";
  $mappedLines[] = [
    'x1' => $fromMapped -> getX(),
    'x2' => $toMapped -> getX(),
    'y1' => $fromMapped -> getY(),
    'y2' => $toMapped -> getY()
  ];
}

// Making a new raster.
$img = new Imagick();
$img -> newImage($width, $height, new ImagickPixel('white'));

$draw = new ImagickDraw();
$draw->setFillColor('#f00');
$draw->setStrokeColor('#000');
$draw->setStrokeWidth($strokeWidth);
$hit = [];
foreach($mappedLines as $line) {
  $draw->line($line['x1'], $line['y1'], $line['x2'], $line['y2']);
  if(!isset($hit[$line['x1']])) {
    $hit[$line['x1']] = [];
  }
  if(!isset($hit[$line['x1']][$line['y1']])) {
    $hit[$line['x1']][$line['y1']] = 0;
  }
  $hit[$line['x1']][$line['y1']]++;
}

$draw->setStrokeWidth(0);
$draw->setStrokeOpacity(0);

$r = $strokeWidth / 2;
foreach($hit as $x => $xHit) {
  foreach($xHit as $y => $count) {
    // Can set to 1 to only draw circles on joined lines.
    if($count > 0) {
      $draw->circle ($x, $y, $x + $r, $y);
    }
  }
}

$img->drawImage($draw);

// Picture of lines and joins for debugging..
$img->setImageFormat('png');
$img->writeImage($outLargePng);

// Pure black and white, output in high res again
$max = $img->getQuantumRange();
$max = $max["quantumRangeLong"]; 
$img->thresholdImage(0.9 * $max);
$img->setImageFormat('pbm');
$img->writeImage($outLargePbm);

// Scale down and return
$target = (int)$height / 10;
$cmd = "convert $outLargePbm -resize x$target $outSmallPbm";
system($cmd, $retval);
exit($retval);

