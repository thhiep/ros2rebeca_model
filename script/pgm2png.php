<?php

header("content-type:text/plain");

function getOccupyLevel($graylevel){
	if ($graylevel>=204) return 0;
	return 1;
}

$mapname = "map.pgm";
if (isset($_GET["f"])) $mapname=$_GET["f"];

echo "Convert $mapname to occupancy grid $mapname.csv";
$f = fopen($mapname,"r");
while($line=trim(fgets($f))){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
if (!($line==="P5")) die("Not PGM P5 file");
while($line=fgets($f)){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
$size = explode(' ',$line);
$size[0]=(int)($size[0]);
$size[1]=(int)($size[1]);
$width = $size[0];
$height = $size[1];
echo sprintf("\nMap size = %d x %d",$size[0],$size[1]);
while($line=fgets($f)){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
$maxlevel = (int)$line;
echo "\nMax white level = $maxlevel";
$bits=[];
$onebits=0;
for($i=0;$i<$size[0];$i++){
	$s = $size[0]*$i;
	for($j=0;$j<$size[1];$j++){
		$graylevel = ord(fgetc($f));
		$bits[$i][$j]= getOccupyLevel($graylevel);
		$onebits+=$bits[$i][$j];
	}
}

echo "\nBit ones = $onebits";

// Create a blank image
$image = imagecreatetruecolor($width, $height);

// Define a color for occupied cells
$occupied_color = imagecolorallocate($image, 0, 0, 0);
$unoccupied_color = imagecolorallocate($image, 255, 255, 255);

// Iterate over each cell in the grid and set its color
for ($i = 0; $i < $width; $i++) {
  for ($j = 0; $j < $height; $j++) {
    imagesetpixel($image, $j, $i, $bits[$i][$j]?$occupied_color:$unoccupied_color);
  }
}

// Save the image to a PNG file
imagepng($image, "$mapname.png");
// Free up memory
imagedestroy($image);
echo "\nDone! $mapname.png was generated.";
