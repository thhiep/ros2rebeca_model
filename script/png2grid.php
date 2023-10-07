<?php

// Load the PNG image
$filename = "turtlebot.png";
$filename = "tbot.png";
$image = imagecreatefrompng($filename);

// Get the dimensions of the image
$width = imagesx($image);
$height = imagesy($image);

// Initialize the occupancy grid
$grid = [];

// Iterate over each pixel in the image and set its value in the grid
for ($y = 0; $y < $height; $y++) {
  for ($x = 0; $x < $width; $x++) {
    $rgb = imagecolorat($image, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    if ($r == 0 && $g == 0 && $b == 0) {
      $grid[$y][$x] = 1;
    } else {
      $grid[$y][$x] = 0;
    }
  }
}

saveGrid($grid,"$filename.csv");

// Free up memory
imagedestroy($image);


function saveGrid($grid,$fname){
	$f = fopen($fname,"w+");
	for($i=0;$i<count($grid);$i++){
		fputcsv($f,$grid[$i]);
	}
	fclose($f);
}
echo "\nDone! $filename.csv file was created.";

