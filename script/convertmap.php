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

fclose($f);
$f = fopen("$mapname.csv","w+");
for($i=0;$i<$size[0];$i++){
	fputcsv($f,$bits[$i]);
}
fclose($f);
echo "\nDone! $mapname.csv file was created.";

$f = fopen("$mapname.array","w+");
fwrite($f,sprintf("int map_width=%d;\n",$size[0]));
fwrite($f,sprintf("int map_height=%d;\n",$size[1]));
fwrite($f,sprintf("int[%d][%d] map;\n",$size[0],$size[1]));
for($i=0;$i<$size[0];$i++){
	for($j=0;$j<$size[1];$j++){
		if ($bits[$i][$j]){
			fwrite($f,sprintf("map[%d][%d]=%s;",$i,$j,$bits[$i][$j]?"true":"false"));
		}
	}		
}
fwrite($f,sprintf("\nint map_occupied=%d;\n",$onebits));
$oc = $onebits * 2;
$oc100 = round($onebits/100) * 100;
$oc100_rows = $oc100/100;

fwrite($f,"int[$oc] o;\n");
$sets=[];
$sets100=[];
$k=0;
$ks=0;
$ks100=0;
for($i=0;$i<$size[0];$i++){
	for($j=0;$j<$size[1];$j++){
		if ($bits[$i][$j]){
			fwrite($f,sprintf("o[%d]=%d;o[%d]=%d;",$k++,$i,$k++,$j));
			if ($k % 20 == 0) fwrite($f,"\n");
			$ks++; 
			$break="";
			$start="";
			$end="";
			if ($ks==1){
				$start="{";
			}
			if ($ks%100 == 0){
				$break="\n";
				$ks=0;
				$end="}";
			}
			$sets[]=sprintf("%s%d,%d%s%s",$start,$i,$j,$end,$break);
			
			$ks100++;
			$sets100[]=sprintf("%d,%d%s",$i,$j,$break);
		}
	}		
}
if ($onebits<$oc100){
	for($i=$onebits;$i<$oc100;$i++){
		$sets[]="0,0";
	}
	$sets[count($sets)-1].="}";
}

fwrite($f,"\n\nint[$oc100_rows][100] o = {\n");
fwrite($f,implode(",",$sets));
fwrite($f,"\n};\n");
fclose($f);
echo "\nDone! $mapname.array file was created.";

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
