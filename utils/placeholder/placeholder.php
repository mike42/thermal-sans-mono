<?php
/**
 * Small implementation of basic raster operations on PBM files to support
 * creation of placeholder glyphs
 */
class pbmImage {
  protected $width;

  protected $bytesPerRow;

  protected $height;

  protected $data;

  public function invert() {
    array_walk($this -> data, 'self::invertByte');
  }

  public function clear() {
    array_walk($this -> data, 'self::clearByte');
  }

  protected static function invertByte(&$item, $key) {
    $item = ~ $item;
  }

  protected static function clearByte(&$item, $key) {
    $item = 0;
  }

  public function getWidth() {
    return $this -> width;
  }

  public function getHeight() {
    return $this -> height;
  }


  public function setPixel(int $x, int $y, $value) {
    if($x < 0 || $x >= $this -> width) {
      return;
    }
    if($y < 0 || $y >= $this -> height) {
      return;
    }
    $byte = $y * $this -> bytesPerRow + intdiv($x, 8);
    $bit = $x % 8;
    if($value === 0) {
      // Clear
      $this -> data[$byte] &= ~(1 << (7 - $bit));
    } else {
      // Set
      $this -> data[$byte] |= (1 << (7 - $bit));
    }
  }

  public function getPixel(int $x, int $y) {
    if($x < 0 || $x >= $this -> width) {
      return 0;
    }
    if($y < 0 || $y >= $this -> height) {
      return 0;
    }
    $byte = $y * $this -> bytesPerRow + intdiv($x, 8);
    $bit = $x % 8;
    return ($this -> data[$byte] >> (7 - $bit)) & 0x01;
  }

  protected function __construct($width, $height, array $data) {
    $this -> width = $width;
    $this -> height = $height;
    $this -> data = $data;
    $this -> bytesPerRow = intdiv($width + 7, 8);
  }

  public function write($filename) {
    $dimensions = $this -> width . " " . $this -> height;
    $data = pack("C*", ... $this -> data);
    $contents = "P4\n$dimensions\n$data";
    file_put_contents($filename, $contents);
  }

  public static function create($width, $height) {
    $bytesPerRow = intdiv($width + 7, 8);
    $expectedBytes = $bytesPerRow * $height;
    $dataValues = array_values(array_fill(0, $expectedBytes, 0));
    return new pbmImage($width, $height, $dataValues);
  }

  public static function fromFile($url) {
    $im_data = file_get_contents($url);
    // Read header line
    $im_hdr_line = substr($im_data, 0, 3);
    if($im_hdr_line !== "P4\n") {
      throw new Exception("Format not supported. Expected P4 bitmap in $url.");
    }
    // Skip comments
    $line_end = self::skipComments($im_data, 2);
    // Read image size
    $next_line_end = strpos($im_data, "\n", $line_end + 1);
    if($next_line_end === false) {
      throw new Exception("Unexpected end of file in $url, probably corrupt.");
    }
    $size_line = substr($im_data, $line_end + 1, ($next_line_end - $line_end) - 1);
    $sizes = explode(" " , $size_line);
    if(count($sizes) != 2 || !is_numeric($sizes[0]) || !is_numeric($sizes[1])) {
       throw new Exception("Image size is bogus, file probably corrupt.");
    }
    $width = $sizes[0];
    $height = $sizes[1];
    $line_end = $next_line_end;
    // Skip comments again
    $line_end = self::skipComments($im_data, $line_end);
    // Extract data.. 
    $bytesPerRow = intdiv($width + 7, 8);
    $expectedBytes = $bytesPerRow * $height;
    $data = substr($im_data, $line_end + 1);
    $actualBytes = strlen($data);
    if($expectedBytes != $actualBytes) {
      throw new Exception("Expected $expectedBytes data, but got $actualBytes, file probably corrupt.");
    }
    $dataUnpacked = unpack("C*", $data);
    $dataValues = array_values($dataUnpacked);
    return new pbmImage($width, $height, $dataValues);
  }

  private static function skipComments($im_data, $line_end) {
    while($line_end !== false && substr($im_data, $line_end + 1, 1) == "#") {
      $line_end = strpos($im_data, "\n", $line_end + 1);
    }
    if($line_end === false) {
      throw new Exception("Unexpected end of file in $url, probably corrupt.");
    }
    return $line_end;
  }

  public function toString() {
    $out = "";
    for($y = 0; $y < $this -> getHeight(); $y += 2) {
      for($x = 0; $x < $this -> getWidth(); $x++) {
        $upper = $this -> getPixel($x, $y) == 1;
        $lower = $this -> getPixel($x, $y + 1) == 1;
        if($upper && $lower) {
          $char = "█";
        } else if($upper) {
          $char = "▀";
        } else if ($lower) {
          $char = "▄";
        } else {
          $char = " ";
        }
        $out .= $char;
      }
      $out .= "\n";
    }
    return $out;
  }

  public function rect($startX, $startY, $width, $height, $filled = false, $outline = 1, $fill = 1) {
    $this -> horizontalLine($startY, $startX, $startX + $width - 1, $outline);
    $this -> horizontalLine($startY + $height - 1, $startX, $startX + $width - 1, $outline);
    $this -> verticalLine($startX, $startY, $startY + $height - 1, $outline);
    $this -> verticalLine($startX + $width - 1, $startY, $startY + $height - 1, $outline);
    if($filled) {
      // Fill center of the rectangle
      for($y = $startY + 1; $y < $startY + $height - 1; $y++) {
        for($x = $startX + 1; $x < $startX + $width - 1; $x++) {
              $this -> setPixel($x, $y, $fill);
        }
      }
    }
  }

  protected function horizontalLine($y, $startX, $endX, $outline) {
    for($x = $startX; $x <= $endX; $x++) {
      $this -> setPixel($x, $y, $outline);
    }
  }

  protected function verticalLine($x, $startY, $endY, $outline) {
    for($y = $startY; $y <= $endY; $y++) {
      $this -> setPixel($x, $y, $outline);
    }
  }

  public function subImage($startX, $startY, $width, $height) {
    $ret = self::create($width, $height);
    $ret -> compose($this, $startX, $startY, 0, 0, $width, $height);
    return $ret;
  }

  public function compose(pbmImage $source, $startX, $startY, $destStartX, $destStartY, $width, $height) {
    for($y = 0; $y < $height; $y++) {
        $srcY = $y + $startY;
        $destY = $y + $destStartY;
        for($x = 0; $x < $width; $x++) {
          $srcX = $x + $startX;
          $destX = $x + $destStartX;
          $this -> setPixel($destX, $destY, $source -> getPixel($srcX, $srcY));
        }
    }
  }
}

if(count($argv) != 4) {
  die("Usage: " . $argv[0] . " ABCD { small | large } ABCD.pbm\n");
}

// Inputs
$codePoint = str_split($argv[1]);
$renderLarge = $argv[2] == "large";
$outFile = $argv[3];
if(count($codePoint) != 4) {
  die("Code point must be 4 digits");
}


if($renderLarge) {
  $font = pbmImage::fromFile(dirname(__FILE__). "/fonts/10x10hex.pbm");
  $charWidth = 10;
  $charHeight = 10;
} else {
  $font = pbmImage::fromFile(dirname(__FILE__). "/fonts/5x7hex.pbm");
  $charWidth = 5;
  $charHeight = 7;
}

// Calculate chars
$chars = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"];
$subImages = [];
for($i = 0; $i < count($codePoint); $i++) {
  $id = array_search($codePoint[$i], $chars);
  if($id === false) {
    die("Don't know how to encode " . $codePoint[$i]);
  }
  $subImages[] = $font -> subImage($id * $charWidth, 0, $charWidth, $charHeight);
}

// Place them all in a box
if($renderLarge) {
  $out = pbmImage::create(24, 24);
  $out -> rect(0, 0, 24, 24);
  $out -> rect(3, 0, 18, 24, true, 0, 0);
  $out -> compose($subImages[0], 0, 0, 2, 1, $charWidth, $charHeight);
  $out -> compose($subImages[1], 0, 0, 12, 1, $charWidth, $charHeight);
  $out -> compose($subImages[2], 0, 0, 2, 13, $charWidth, $charHeight);
  $out -> compose($subImages[3], 0, 0, 12, 13, $charWidth, $charHeight);
} else {
  $out = pbmImage::create(18, 17);
  $out -> rect(0, 0, 18, 17);
  $out -> rect(3, 0, 12, 17, true, 0, 0);
  $out -> compose($subImages[0], 0, 0, 4, 1, $charWidth, $charHeight);
  $out -> compose($subImages[1], 0, 0, 10, 1, $charWidth, $charHeight);
  $out -> compose($subImages[2], 0, 0, 4, 9, $charWidth, $charHeight);
  $out -> compose($subImages[3], 0, 0, 10, 9, $charWidth, $charHeight);
}

#Good for debugging ;)
#echo $out -> toString();
$out -> write($outFile);
