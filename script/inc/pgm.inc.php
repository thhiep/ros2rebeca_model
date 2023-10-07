<?php

class PgmFile {
	var $mapname;
	var $with=0;
	var $height=0;
	var $maxlevel=255;
	var $occupied_level=204;
	var $grays=[];	//grid of gray levels
	var $bits=[];	//grid of occupancy
	var $onebits=0;	//number of occupied cells, bit 1's
	
	function __construct(){
	}
	
	function debug(){
		echo "\nFile name: {$this->mapname}";
		echo sprintf("\nSize = %d x %d",$this->width,$this->height);
		echo sprintf("\nMax level = %d",$this->maxlevel);
		echo sprintf("\nOccupied cells = %d",$this->onebits);
	}
	
	function parse($mapname){
		unset($this->grays);
		unset($this->bits);
		$this->onebits=0;
		$this->width=0;
		$this->height=0;
		
		if (!file_exists($mapname)) return false;
		$f = fopen($mapname,"r");
		if (!$f) return false;
		$this->mapname = $mapname;
		
		while($line=trim(fgets($f))){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
		if (!($line==="P5")) {
			return false;
		}
		while($line=fgets($f)){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
		$size = explode(' ',$line);
		$size[0]=(int)($size[0]);
		$size[1]=(int)($size[1]);
		$this->width = $size[0];
		$this->height = $size[1];
		while($line=fgets($f)){if (!($line=='' || preg_match('/^#.*$/',$line))) break;};
		$this->maxlevel = (int)$line;
		$this->bits=[];
		$this->onebits=0;
		for($i=0;$i<$this->width;$i++){
			$s = $this->width*$i;
			for($j=0;$j<$this->height;$j++){
				$graylevel = ord(fgetc($f));
				$this->grays[$i][$j]=$graylevel;
				$this->bits[$i][$j]= $this->getOccupiedLevel($graylevel);
				$this->onebits+=$this->bits[$i][$j];
			}
		}
		return true;
	}
	
	function getOccupiedLevel($graylevel){
		return ($graylevel>=$this->occupied_level)?0:1;
	}
	
	function toPgm($fn=''){
		if (!$fn) $fn = sprintf("%s.pgm",$this->mapname);
		$f = fopen("$fn.pgm","w+");
		if (!$f) return false;
		fwrite($f,"P5\n");
		fwrite($f,"#converted from png file\n");
		fwrite($f,sprintf("%d %d\n",$this->width,$this->height));
		fwrite($f,"255\n");
		for ($x = 0; $x < $this->width; $x++){
			for ($y = 0; $y < $this->height; $y++) {
				fwrite($f,chr($this->grays[$x][$y]));
			}
		}
		fclose($f);
		return $fn;
	}
	
	function toCsv(){
		$fn = sprintf("%s.csv",$this->mapname);
		$f = fopen($fn,"w+");
		if (!$f) return false;
		for($i=0;$i<$this->width;$i++){
			fputcsv($f,$this->bits[$i]);
		}
		fclose($f);
		echo "\n$fn was created";
		return $fn;
	}
	
	function toArray(){
		$fn = sprintf("%s.array",$this->mapname);
		$f = fopen($fn,"w+");
		if (!$f) return false;
		fwrite($f,sprintf("int map_width=%d;\n",$this->width));
		fwrite($f,sprintf("int map_height=%d;\n",$this->height));
		fwrite($f,sprintf("int map_occupied=%d;\n",$this->onebits));
		fwrite($f,sprintf("boolean[%d][%d] map;\n",$this->width,$this->height));
		for($i=0;$i<$this->width;$i++){
			for($j=0;$j<$this->height;$j++){
				if ($this->bits[$i][$j]){
					fwrite($f,sprintf("map[%d][%d]=%s;",$i,$j,$this->bits[$i][$j]?"true":"false"));
				}
			}		
		}
		fclose($f);
		echo "\n$fn was created";		
		return $fn;
	}
	
	function toMap(){
		$fn = sprintf("%s.map",$this->mapname);
		$f = fopen($fn,"w+");
		if (!$f) return false;
		fwrite($f,sprintf("int map_width=%d;\n",$this->width));
		fwrite($f,sprintf("int map_height=%d;\n",$this->height));
		fwrite($f,sprintf("int map_occupied=%d;\n",$this->onebits));
		fwrite($f,sprintf("boolean[%d][%d] map;\n",$this->width,$this->height));
		for($x=0;$x<$this->width;$x++){
			for($y=0;$y<$this->height;$y++){
				$bi = $y;
				$bj = $this->width-1-$x;
				if ($this->bits[$bi][$bj]){
					fwrite($f,sprintf("map[%d][%d]=%s;",$x,$y,$this->bits[$bi][$bj]?"true":"false"));
				}
			}		
		}
		fclose($f);
		echo "\n$fn was created";		
		return $fn;
	}

	function toPng(){
		$fn = sprintf("%s.png",$this->mapname);

		// Create a blank image
		$image = imagecreatetruecolor($this->width, $this->height);
		if (!$image) return false;

		// Define a color for occupied cells
		$occupied_color = imagecolorallocate($image, 0, 0, 0);
		$unoccupied_color = imagecolorallocate($image, 255, 255, 255);

		// Iterate over each cell in the grid and set its color
		for ($i = 0; $i < $this->width; $i++) {
			for ($j = 0; $j < $this->height; $j++) {		
				imagesetpixel($image, $i, $j, $this->bits[$i][$j]?$occupied_color:$unoccupied_color);
			}
		}

		// Save the image to a PNG file
		imagepng($image, $fn);
		// Free up memory
		imagedestroy($image);
		echo "\n$fn was created";		
		return $fn;
	}
	
	function resizePNG($filename, $newSize) {
		$newSize = (int)$newSize;
		// Load the PNG image
		$image = imagecreatefrompng($filename);
		if (!$image) return false;
		
		$pi = pathinfo($filename);
		$newFileName = sprintf("%s/%s.%d.%s",$pi["dirname"],$pi["filename"],$newSize,$pi["extension"]);

		// Get the dimensions of the image
		$width = imagesx($image);
		$height = imagesy($image); 

		$size = max($width,$height);
		if ($size<=$newSize) return false;

		// Calculate the aspect ratio of the image
		$aspectRatio = (double)$width / (double)$height;	 

		// Calculate the new height based on the aspect ratio and the new width
		if ($width>$height){
			$newWidth = $newSize;
			$newHeight = $newWidth / $aspectRatio;
		} else {
			  $newHeight = $newSize;
			  $newWidth = $newHeight * $aspectRatio;
		}

		// Create a new blank image with the new dimensions
		$newImage = imagecreatetruecolor($newWidth, $newHeight);

		// Copy and resize the original image to the new image
		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		// Save the new image as a PNG file
		imagepng($newImage, $newFileName);

		// Free up memory by destroying the images
		imagedestroy($image);
		imagedestroy($newImage);
		
		return $newFileName;
	}
	
	function rgb2gray($r,$g,$b,$w){
		//A = (R + G + B)/3;
		
		//Y = 0.299 * R + 0.587 * G + 0.114 * B

		//Z = 0.2126 * R + 0.7152 * G + 0.0722 * B
		if (is_string($w)){
			$name = "$w";
			$weights = array(
				"avg"=>[0.3333,0.3333,0.3333],
				"lumin"=>[0.2126,0.7152,0.0722],
				"pal"=>[0.299,0.587,0.114],				
				//"hdtv"=>[0.21,0.72,0.07],
				//"pal"=>[0.3,0.59,0.11],
			);
			if (!isset($weights[$name])) $name = "avg";
			$w = $weights[$name];		
		}
		
		return round($w[0]*$r + $w[1]*$g + $w[2]*$b);
	}

	function fromPng($fn){
		$image = imagecreatefrompng($fn);
		if (!$image) return false;
		$width = imagesx($image);
		$height = imagesy($image);
		$cols = $width;
		$rows = $height;
		$grayLevels = array();
		for ($y = 0; $y < $height; $y++) {
		for ($x = 0; $x < $width; $x++) {
					
				$rgb = imagecolorat($image, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$gray = $this->rgb2gray($r,$g,$b,"avg");
				$bit = $this->getOccupiedLevel($gray);
				$grayLevels[$y][$x] = $gray;
				//if ($bit) echo "\nmap[$y][$x]=1";
			}
		}
		imagedestroy($image);
		$f = fopen("$fn.pgm","w+");
		fwrite($f,"P5\n");
		fwrite($f,"#converted from png file\n");
		fwrite($f,"$width $height\n");
		fwrite($f,"255\n");
		for ($y = 0; $y < $height; $y++) {
		for ($x = 0; $x < $width; $x++) {				
				fwrite($f,chr($grayLevels[$y][$x]));
			}
		}
		fclose($f);
		unset($grayLevels);
		return "$fn.pgm";
	}
	
	function test($mapname){
		header("content-type:text/plain");
		if (isset($_GET["f"])) $mapname=$_GET["f"];
		$pgm = new PgmFile();
		$pgm->parse($mapname);
		$pgm->debug();
		$pgm->toCsv();
		$pgm->toArray();
		$png = $pgm->toPng();

		//$png = "simple_maze.pgm.png";
		//($pgm->fromPng($png));
	}
}

