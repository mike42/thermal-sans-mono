<?php

$lines = [
  "Thermal Sans Mono",
  "abcdefghijklmnopqrstuvwxyz",
  "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
  "0123456789.:,;(*!?')"
];

$fileLists = [];
foreach($lines as $line) {
  $files = [];
  foreach(str_split($line) as $char) {
    $hex = str_pad(strtoupper(dechex(ord($char))), "0", STR_PAD_LEFT);
    if($hex == "20") {
      $files[] = __DIR__ . "/../../build/scale/24px/0/0/0020.pbm";
    } else {
      $files[] = __DIR__ . "/../../build/trace/24px/0/0/00${hex}.bold.pbm";
    }
  }
  $fileLists[] = "pbm:<(convert " . implode(" ", $files) . " +append pbm:-)"; 
}

$cmd = "convert " . implode(" ", $fileLists) . " -append preview.png";
system("bash -c \"$cmd\"");
