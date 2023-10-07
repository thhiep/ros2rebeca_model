<?php
define("PI",3.14);
define("INFINITY",9999);
define("SQRT2",1.4142);
define("SIN45",0.7071);
define("COS45",0.7071);

//diagonal movements
define("DiagonalMovement_Never",0);
define("DiagonalMovement_OnlyWhenNoObstacles",1);
define("DiagonalMovement_IfAtMostOneObstacle",2);
define("DiagonalMovement_Always",3);

//occupancy grid
class OGrid{
	
	var $map=[];
	var $flip = false;
	
	//path finding options
	var $allowDiagonal = true;
	var $dontCrossCorners = true;
	var $heuristic = 'm';
	var $weight = 1;
	var $diagonalMovement = DiagonalMovement_OnlyWhenNoObstacles;
	
	function __construct($bits,$margin=1,$flip=false){
		$this->setMap($bits,$margin,$flip);
		$this->setOptions(true,true,1);
	}
	
	function expandObstacles($margin=1){
		//expand
		$map2 = $this->map;
		$neighbors = [[1,0],[1,1],[0,1],[-1,1],[-1,0],[-1,-1],[0,-1],[1,-1]];
		for($i=0;$i<$rows;$i++){
			for ($j=0;$j<$cols;$j++){
				if ($this->map[$i][$j]){
					for($k=0;$k<count($neighbors);$k++){
						$nx = $i+$neighbors[$k][0];
						$ny = $j+$neighbors[$k][1];
						if ($nx>=0 && $nx<$rows && $ny>=0 && $ny<$cols && !$this->map[$nx][$ny]) 
							$map2[$nx][$ny]=true;
					}
				}
			}
		}
		return $map2;		
	}
	
	//set the occupied cells, expand by a margin to avoid collision
	function setMap($bits,$margin=1,$flip=false){
		$rows = count($bits);
		$cols = count($bits[0]);	
		$this->flip = $flip;
		if ($flip){
			$this->map=[];
			//(0,0) is lower-left corner instead of upper-right
			for($i=0;$i<$rows;$i++)
				for ($j=0;$j<$cols;$j++)
					$this->map[$j][$rows-$i-1] = $bits[$i][$j];
		} else {
			$this->map=$bits;
		}
		//expand
		if ($margin){
			$map2 = $this->map;
			$neighbors = [[1,0],[1,1],[0,1],[-1,1],[-1,0],[-1,-1],[0,-1],[1,-1]];
			for($i=0;$i<$rows;$i++){
				for ($j=0;$j<$cols;$j++){
					if ($this->map[$i][$j]){
						for($k=0;$k<count($neighbors);$k++){
							$nx = $i+$neighbors[$k][0];
							$ny = $j+$neighbors[$k][1];
							if ($nx>=0 && $nx<$rows && $ny>=0 && $ny<$cols && !$this->map[$nx][$ny]) 
								$map2[$nx][$ny]=true;
						}
					}
				}
			}
			for($i=0;$i<$rows;$i++){
				for ($j=0;$j<$cols;$j++){
					$this->map[$i][$j]=$map2[$i][$j];
				}
			}
		}
	}
	//xmax+1
	function cols(){
		return count($this->map[0]);
	}
	
	//ymax+1
	function rows(){
		return count($this->map);
	}
	
	function getBitOnes($encode=false){
		$bitones=[];
		for($i=0;$i<$this->rows();$i++){
			for($j=0;$j<$this->cols();$j++){
				if ($this->map[$i][$j]) {
					if ($encode)
						$bitones[]= xy2idx($i,$j);
					else {	
						$bitones[]=$i;
						$bitones[]=$j;
					}
				}
			}
		}
		return $bitones;
	}
	
	function isInside($x,$y){
		return ($x >= 0 && $x < $this->rows()) && ($y >= 0 && $y < $this->cols());
	}
	
	function isWalkableAt($x,$y){
		return $this->isInside($x,$y) && !$this->map[$x][$y];
	}
	
	function distance($x1,$y1,$x2,$y2,$type='m'){
		$dx = abs($x2-$x1);
		$dy = abs($y2-$y1);
		//manhattan
		if ($type=='m') return ($dx)+($dy);
		//octile
		else if ($type=='o'){
			$f = 1.4142 - 1;//sqrt(2)-1;
			return ($dx < $dy) ? $f * $dx + $dy : $f * $dy + $dx;
		}
		//Chebyshev
		else if ($type=='c') return max($dx,$dy);
		
		//Euclide
		return sqrt($dx*$dx + $dy*$dy);
	}
	
	function edistance($x1,$y1,$x2,$y2){
		$dx = $x2-$x1;
		$dy = $y2-$y1;
		return sqrt($dx*$dx + $dy*$dy);
	}
	
	function mdistance($x1,$y1,$x2,$y2){
		return abs($x2-$x1) + abs($y2-$y1);
	}
	
	function odistance($x1,$y1,$x2,$y2){
		$dx = abs($x2-$x1);
		$dy = abs($y2-$y1);
		$f = 1.4142 - 1;//sqrt(2)-1;
		return ($dx < $dy) ? $f * $dx + $dy : $f * $dy + $dx;
	}

	function sign($x){
		return $x>0?1:($x<0?-1:0);
	}

	function between($x,$x1,$x2){
		return ( $this->sign($x-$x1) * $this->sign($x-$x2) )<=0;
	}
	/*
	function xy2idx($x,$y){
		return ($x % 1000)*1000 + ($y % 1000);
	}

	function idx2xy($idx){
		$x = ($idx/1000) % 1000;
		$y = $idx % 1000;
		return [$x,$y];
	}
	*/
	function xy2idx($ox,$oy){	
		if ($ox<0){
			$sx=2;$x=-$ox;
		}else{
			$sx=1;$x=$ox;		
		}
		if ($oy<0){
			$sy=2;$y=-$oy;
		}else{
			$sy=1;$y=$oy;		
		}
		$ex = $sx*1000 + $x;
		$ey = $sy*1000 + $y;
		$idx = $ey*10000 + $ex;
		//echo "\n[$ox,$oy] -> $idx;\n";
		return $idx;
	}

	function idx2xy($idx){
		$ex = $idx % 10000;
		$ey = ($idx - $ex) / 10000;
		$sx = (int)($ex/1000);
		$x = $ex % 1000;
		$sy = (int)($ey/1000);
		$y = $ey % 1000;
		if ($sx==2) $x=-$x;
		if ($sy==2) $y=-$y;
		//echo "\n$idx -> [$x,$y];\n";
		return [$x,$y];
	}

	function removeFromList($a,$k){
		$b=[];
		$b[0]=0;
		if ($k<1) $k=1;
		if ($k>$a[0]) $k=$a[0];
		if ($a[0]>0 && $k>=1 && $k<=$a[0]){
			$b[0]=$a[0]-1;
			for($i=1;$i<$k;$i++){
				$b[$i]=$a[$i];
			}
			for($i=$k;$i<$a[0];$i++){
				$b[$i]=$a[$i+1];
			}
		}
		return $b;
	}

	function addToList($a,$x){
		$a[0]++;
		$a[$a[0]]=$x;
		return $a;
	}
	
	function inList($a,$x){
		$in=false;
		for($i=1;$i<=$a[0];$i++) if ($a[$i]==$x){$in=true;break;}
		return $in;
	}

	/**
	 * Get the neighbors of the given node.
	 *
	 *     offsets      diagonalOffsets:
	 *  +---+---+---+    +---+---+---+
	 *  |   | 0 |   |    | 0 |   | 1 |
	 *  +---+---+---+    +---+---+---+
	 *  | 3 |   | 1 |    |   |   |   |
	 *  +---+---+---+    +---+---+---+
	 *  |   | 2 |   |    | 3 |   | 2 |
	 *  +---+---+---+    +---+---+---+
	 *
	 *  When allowDiagonal is true, if offsets[i] is valid, then
	 *  diagonalOffsets[i] and
	 *  diagonalOffsets[(i + 1) % 4] is valid.
	 * @param {Node} node
	 * @param {DiagonalMovement} diagonalMovement
	 */
	function getNeighbors($x,$y,$diagonalMovement) {
		$neighbors=[];
		$s0 = false; $d0 = false;
		$s1 = false; $d1 = false;
		$s2 = false; $d2 = false;
		$s3 = false; $d3 = false;
	
		// ↑
		if ($this->isWalkableAt($x, $y - 1)) {
			$neighbors[]=[$y - 1,$x];
			$s0 = true;
		}
		// →
		if ($this->isWalkableAt($x + 1, $y)) {
			$neighbors[]=[$y,$x + 1];
			$s1 = true;
		}
		// ↓
		if ($this->isWalkableAt($x, $y + 1)) {
			$neighbors[]=[$y + 1,$x];
			$s2 = true;
		}
		// ←
		if ($this->isWalkableAt($x - 1, $y)) {
			$neighbors[]=[$y,$x - 1];
			$s3 = true;
		}

		if ($diagonalMovement == DiagonalMovement_Never) {
			return $neighbors;
		}

		if ($diagonalMovement == DiagonalMovement_OnlyWhenNoObstacles) {
			$d0 = $s3 && $s0;
			$d1 = $s0 && $s1;
			$d2 = $s1 && $s2;
			$d3 = $s2 && $s3;
		} else if ($diagonalMovement == DiagonalMovement_IfAtMostOneObstacle) {
			$d0 = $s3 || $s0;
			$d1 = $s0 || $s1;
			$d2 = $s1 || $s2;
			$d3 = $s2 || $s3;
		} else if ($diagonalMovement == DiagonalMovement_Always) {
			$d0 = true;
			$d1 = true;
			$d2 = true;
			$d3 = true;
		} else {
			die('Incorrect value of diagonalMovement');
		}

		// ↖
		if ($d0 && $this->isWalkableAt($x - 1, $y - 1)) {
			$neighbors[]=[$y - 1,$x - 1];
		}
		// ↗
		if ($d1 && $this->isWalkableAt($x + 1, $y - 1)) {
			$neighbors[]=[$y - 1,$x + 1];
		}
		// ↘
		if ($d2 && $this->isWalkableAt($x + 1, $y + 1)) {
			$neighbors[]=[$y + 1,$x + 1];
		}
		// ↙
		if ($d3 && $this->isWalkableAt($x - 1, $y + 1)) {
			$neighbors[]=[$y + 1,$x - 1];
		}
		return $neighbors;
	}
	
	function getValue($a,$key,$default){
		return isset($a[$key])?$a[$key]:$default;
	}
	
	function setOptions($allowDiagonal,$dontCrossCorners,$weight=1){
		$this->allowDiagonal = $allowDiagonal?true:false;
		$this->dontCrossCorners = $dontCrossCorners?true:false;
		$this->heuristic = 'm';
		$this->weight = (int)$weight;

		if (!$this->allowDiagonal) {
			$this->diagonalMovement = DiagonalMovement_Never;
		} else {
			if ($this->dontCrossCorners) {
				$this->diagonalMovement = DiagonalMovement_OnlyWhenNoObstacles;
			} else {
				$this->diagonalMovement = DiagonalMovement_IfAtMostOneObstacle;
			}
		}
		// When diagonal movement is allowed the manhattan heuristic is not 
		// admissible. It should be octile instead
		if ($this->diagonalMovement == DiagonalMovement_Never) {
			$this->heuristic = 'm';
		} else {
			$this->heuristic = 'o';
		}	
	}
   
	//generate a path from (x,y) to (targetX,targetY) using a selected algorithm
	function generatePath($x,$y,$targetX,$targetY,$algo=0){		
		$nodes=[];
		$rows = $this->rows();
		$cols = $this->cols();
		for($i=0;$i<$rows;$i++){
			for($j=0;$j<$cols;$j++){
				$nodes[$i][$j][0]=0;		//not checked
				$nodes[$i][$j][1]=INFINITY;  //g_score = distance to starting point
				$nodes[$i][$j][2]=INFINITY;	//h_score = distance to ending point
				$nodes[$i][$j][3]=-1;	//(x,y) of preceding node
				$nodes[$i][$j][4]=-1;
			}
		}
		
		$open=[];
		
		//add starting point
		$open[0]=1;
		$open[1]=$this->xy2idx($x,$y);		
		$nodes[$x][$y][0]=1;//opened
		$nodes[$x][$y][1]=0;//g_score
		$nodes[$x][$y][2]=$this->distance($x,$y,$targetX,$targetY,$this->heuristic);//h_score
		
		//neighboring cells in 8 directions
		$found=false;
		$maxopen=0;
		while ($open[0]>0){
			$lf = INFINITY;
			$li=-1;
			$lj=-1;
			$lk=-1;
			if ($open[0]>$maxopen)$maxopen=$open[0];
			for($k=1;$k<=$open[0];$k++){
				if ($open[$k]>=0){
					$ij = $this->idx2xy($open[$k]);
					$i = $ij[0];
					$j = $ij[1];
					if ($i>=0 && $j>=0 && ($nodes[$i][$j][1] + $nodes[$i][$j][2]) < $lf){
							$lf=$nodes[$i][$j][1] + $nodes[$i][$j][2];
							$li=$i;$lj=$j;$lk=$k;	
					}
				}
			}

			//echo "\nNode #{$lk}[$li,$lj]=$lf";
			if ($li<0 && $lj<0) {
				echo "Failed to find path";
				break;
			}
			$nodes[$li][$lj][0]=2;//removed from open list
			if ($li==$targetX && $lj==$targetY){
				$found = true;
				break;
			}
			$open = $this->removeFromList($open,$lk);			
		
			// $neighbors = $this->getNeighbors($li,$lj,$this->diagonalMovement);		
			// for($k=0;$k<count($neighbors);$k++){
				// $nx=$neighbors[$k][0];
				// $ny=$neighbors[$k][1];
			
			$neighbors = [[1,0],[1,1],[0,1],[-1,1],[-1,0],[-1,-1],[0,-1],[1,-1]];
			//$neighbors = $this->getNeighbors($li,$lj,$this->diagonalMovement);
			for($k=0;$k<count($neighbors);$k++){
				$nx = $li + $neighbors[$k][0];
				$ny = $lj + $neighbors[$k][1]; 
				
				if (!$this->isWalkableAt($nx,$ny)) continue;	//outside map or occupied
				if ($nodes[$nx][$ny][0]!=0) continue;	//checked
							
				//$g = $nodes[$li][$lj][1] + $this->distance($nx,$ny,$x,$y,$this->heuristic);
				 $g = $nodes[$li][$lj][1]  + (($nx==$li||$ny==$lj) ? 1 : SQRT2);

				if ($g < $nodes[$nx][$ny][1]){
					//echo "\n<br>[$nx,$ny] <-[$li,$lj]";
					$nodes[$nx][$ny][0] = 1;	//checked
					$nodes[$nx][$ny][1] = $g;
					$nodes[$nx][$ny][2] = $this->distance($nx,$ny,$targetX,$targetY,$this->heuristic);	
					$nodes[$nx][$ny][3] = $li;
					$nodes[$nx][$ny][4] = $lj;		
					
					$added=false;
					$code = $this->xy2idx($nx,$ny);
					for($i=1;$i<=$open[0];$i++){
						if ($code == $open[$i]){$added=true;break;}
					}
					if (!$added){
						//echo "\n<br>[$nx,$ny] <-[$li,$lj]";
						$open[0]++;
						$open[$open[0]]= $code;
					}
				}
			}
		}
		
		echo "\nMax open list = $maxopen";
		
		$path=[];$path[0]=0;
		$rpath=[];$rpath[0]=0;
		if ($found){
			//construct the path based on traceback graph 
			$i=$targetX;$j=$targetY;
			$rpath[0]=1;
			$rpath[1]=$this->xy2idx($i,$j);
			while(true){
				$li=(int)$nodes[$i][$j][3];
				$lj=(int)$nodes[$i][$j][4];
				if ($li>=0 && $lj>=0){
					$rpath[0]++;
					$rpath[$rpath[0]] = $this->xy2idx($li,$lj);
				} else {
					break;
				}
				$i = $li; $j=$lj;
			}
			//echo "\n".(implode(',',array_slice($rpath,1)));
			
			//assertion(rpath[1]==x && rpath[2]==y,"something wrong");		
			
			//get it in reversed order
			$li=$x;$lj=$y;$k=0;
			$path[0]=0;
			for($i=$rpath[0];$i>0;$i--){
				$path[0]++;
				$path[$path[0]]=$rpath[$i];
			}
			//echo "\n".(implode(',',array_slice($path,1)));
		} else {
			echo "\nPath is not found";
		}
		return $path;
	}
	
	function getAngleFromDir($dir){
		$dir = $dir % 8;
		if ($dir<0) $dir+=8;
		return $dir * 45; 
	}
	
	function getNewCoord($x,$y,$a,$b,$dir){
		$dir = $dir % 8;
		if ($dir<0) $dir+=8;
		
		//theta = 0,45,90,135,180,235,270,360
		//cos = x, sin = y, tan = y/x
		
		//use ENV for these constant arrays will cause the model not compilable, don't know the reason
		$coss = array(1,COS45,0,-COS45,-1,-COS45,0,COS45);		
		$sins = array(0,SIN45,1,SIN45,0,-SIN45,-1,-SIN45);

		$p=[];
		$p[0] = (($x - $a)*$coss[$dir] - ($y - $b)*$sins[$dir]);
		$p[1] = (($x - $a)*$sins[$dir] + ($y - $b)*$coss[$dir]);
		
		return $p;		
	}
	
	function scanObstacles($rx,$ry,$rdir,$ROBOT_LENGTH=1,$ROBOT_BREADTH=1,$fov=180,$max_distance=50){
		//prepare scan data here --> list of nearest obstacles around the robot, return (X,Y) coordinates to the robot's axises
		$scandata=[];
		$mx = $max_distance;//STOP_ZONE;
		$my = $max_distance;//SAFE_MARGIN;
		$dx = round($ROBOT_LENGTH/2.0);
		$dy = round($ROBOT_BREADTH/2.0);
		//int radius = ROBOT_RADIUS;
		
		//inner bounded rectangle and outer bounded rectangle in the robot's axises
		$x1 = $dx;
		$x2 = $ROBOT_LENGTH - $x1;
		$y1 = $dy;
		$y2 = $ROBOT_BREADTH - $y1;		
		$xo1 = $x1 + $mx;	//outer boundary
		$xo2 = $x2 - $mx;
		$yo1 = $y1 + $my;
		$yo2 = $y2 - $my;
		echo "\n\nRobot boundary: [$x1,$x2], [$y1,$y2]";
		
		//map origin in robot's axises
		$om = $this->getNewCoord(0,0,$rx,$ry,$rdir);
						
		for($i=0;$i<$max_distance;$i++){
			$xi = $x1 + $i;
			for($yi=$y2;$yi<=$y1;$yi++){
				$p = $this->getNewCoord($xi,$yi,$om[0],$om[1],-$rdir);	//convert to map's coordinates
				if ( $this->isInside($p[0],$p[1]) && $this->map[$p[0]][$p[1]] ){
					$scandata[] = [$xi,$yi,$p[0],$p[1]];					
					break;	//only take nearest obstacle
				}
			}
		}
		for($i=0;$i<$x1+$max_distance;$i++){		
			$xi = $i;
			for($j=0;$j<$max_distance;$j++){
				$yi = $y1 + $j;
				$p = $this->getNewCoord($xi,$yi,$om[0],$om[1],-$rdir);	//convert to map's coordinates
				if ( $this->isInside($p[0],$p[1]) && $this->map[$p[0]][$p[1]] ){
					$scandata[] = [$xi,$yi,$p[0],$p[1]];					
					break;	//only take nearest obstacle
				}
			}				
			for($j=0;$j<$max_distance;$j++){
				$yi = $y2 - $j;
				$p = $this->getNewCoord($xi,$yi,$om[0],$om[1],-$rdir);	//convert to map's coordinates
				if ( $this->isInside($p[0],$p[1]) && $this->map[$p[0]][$p[1]] ){
					$scandata[] = [$xi,$yi,$p[0],$p[1]];					
					break;	//only take nearest obstacle
				}
			}				
		}
/*		
		for($yi=$ymin;$yi<=$ymax;$yi++){
			for ($xi=$xmin;$xi<=$xmax;$xi++){
				$p = $this->getNewCoord($xi,$yi,$om[0],$om[1],-$rdir);	//convert to map's coordinates
				if ( $this->isInside($p[0],$p[1]) && $this->map[$p[0]][$p[1]] ){
					$scandata[] = [$xi,$yi,$p[0],$p[1]];					
					break;	//only take nearest obstacle
				}		
			}
		}
		*/
		return $scandata;
	}
	
	function precalcAngles($angleIncrement=1){
	  $angles=[];
	  for ($angle=0; $angle<=360; $angle+=$angleIncrement) {
			// Calculate the current angle
			$angleRad = deg2rad($angle);
			$angles[]=[$angle,round(cos($angleRad),4),round(sin($angleRad),4),round(tan($angleRad),4)];
	  }
	  $n = count($angles);
	  $nn = $n*4;
	  $f = fopen("data/angles-$angleIncrement.array","w+");
	  if (!$f){
		  echo "cannot create file";
		  return false;
	  }
	  fwrite($f,"\ndouble[$nn] a;\n");
	  $j=0;
	  for($i=0;$i<count($angles);$i++){		  
		  fwrite($f,sprintf("a[%d]=%0.1f;",$j++,$angles[$i][0]));
		  fwrite($f,sprintf("a[%d]=%0.4f;",$j++,$angles[$i][1]));
		  fwrite($f,sprintf("a[%d]=%0.4f;",$j++,$angles[$i][2]));
		  fwrite($f,sprintf("a[%d]=%0.4f;",$j++,$angles[$i][3]));
		  if ($i>0 && 0 == $i%100) fwrite($f,"\n");
		  /*
		fwrite($f,sprintf("\na[%d]={%0.4f,%0.4f,%0.4f,%0.4f};",
			$i,$angles[$i][0],$angles[$i][1],$angles[$i][2],$angles[$i][3]));
			*/
	  }
	  fclose($f);
	  return $angles;
	}
	
	function simulateLaserScan($rx, $ry, $angleMin, $angleMax, $angleIncrement=1, $maxRange=10) {
		$rows = $this->rows(); 
		$cols = $this->cols();

		$beams = ceil(abs($angleMax - $angleMin) / $angleIncrement);
		$maxRange = (int)max($rx,$this->rows()-$rx,$ry,$this->cols()-$ry);
		$step = 0.8;//ceil(min($rows, $cols) / $maxRange);		
		
		$scandata = []; 
		// Initialize an array to store the ranges for each angle
		$ranges = array_fill(0, $beams, INFINITY);
		
		$precalc = $this->precalcAngles(1);
	
		$inc = $angleMin < $angleMax? $angleIncrement:-$angleIncrement;
		$ai = $angleMin;
		$aimax = floor(360/$angleIncrement);
		for ($i = 0; $i < $beams; $i++, $ai+=$inc) {
			$ai_n=$ai % 360;
			if ($ai_n<0) $ai_n+=360;
			//$angle = $this->normalizeAngle($angleMin + $i * $angleIncrement);
			//$angleRad = deg2rad($angle);
					
			$cosa = $precalc[$ai_n][1];
			$sina = $precalc[$ai_n][2];
			//echo "<br>$angle = $ai";			
			//$cosa = cos($angleRad);
			//$sina = sin($angleRad);
			
			// Initialize the range for this angle to the maximum range
			$range = INFINITY;

			// Iterate over each step along the current ray
			for ($d = $step; $d < $maxRange; $d += $step) {
			  // Calculate the current position along the ray
			  $x = floor($rx + $d * $cosa);
			  $y = floor($ry + $d * $sina);
			  // Check if the current position is within the grid
			  if ($this->isInside($x,$y)){
				  if ($this->map[$x][$y]) {
					  // Update the range for this angle
					  $range = $d;
					  // Store the range for this angle in the ranges array
					  $ranges[$i] = $range;
					  $prev = end($scandata);
					  if ($prev==null || ($prev && !($prev[0]==$x && $prev[1]==$y)))
							$scandata[] = array($x,$y,$ai_n);
					  break;
				} 
			  } else {
				  /*
					if ($x<0) $x = 0;
					if ($x>$this->rows()-1) $x = $this->rows()-1;
					if ($y<0) $y = 0;
					if ($y>$this->cols()-1) $y = $this->cols()-1;
					$range = $j;
					  // Store the range for this angle in the ranges array
					  $ranges[$i] = $range;
					$prev = end($scandata);
					  if ($prev==null || ($prev && !($prev[0]==$x && $prev[1]==$y)))
							$scandata[] = array($x,$y,$ai_n);
					*/  
					break;		
				}
			}			
		}

		// Return the ranges array
		return $scandata;
	}
	
	function getObstacles($rx,$ry,$rdir,$scandata,$ROBOT_LENGTH=1,$ROBOT_BREADTH=1,$SAFE_MARGIN=1){
				//reset matrix of nearest obstacles in 8 directions
		$obstacles=[];		
		$distances=[];
		for($i=0;$i<8;$i++)$distances[$i] = INFINITY;
		if ($rx==0){
			$distances[3] = $distances[4] = $distances[5] = 0;
		}else if ($rx==$this->rows()-1){
			$distances[7] = $distances[1] = $distances[0] = 0;
		}
		if ($ry==0){
			$distances[5] = $distances[6] = $distances[7] = 0;			
		}else if ($ry==$this->cols()-1){
			$distances[1] = $distances[2] = $distances[3] = 0;
		}		
		//robot boundary
		$xmax = ceil($ROBOT_LENGTH/2);
		$xmin = $ROBOT_LENGTH - $xmax;
		
		$ymax = ceil($ROBOT_BREADTH/2.0);
		$ymin = $ROBOT_BREADTH - $ymax;
		$ymax += $SAFE_MARGIN;
		$ymin -= $SAFE_MARGIN;
				
		/* obstacle direction relative to the robot:
				ymin	ymax
			3	|	2	|	1
		--------|-------|-------- xmax
			4	|		|	0 (+ rdir if convert to map direction)
		--------|-------|-------- xmin
			5	|	6	|	7
				|		|
		*/		
		$offsets = [[1,0],[1,1],[0,1],[-1,1],[-1,0],[-1,-1],[0,-1],[1,-1]];
		$dir=0;		
		for($i=0;$i<count($scandata);$i++){
			$x= $scandata[$i][0];
			$y= $scandata[$i][1];
			
			//$mdir = ($dir + $rdir) % 8;
			$mdir = floor(($scandata[$i][2]*2)/45) + $rdir;
			$mdir%=8;
			if ($mdir<0)$mdir+=8;
			//echo "<br>Robot: [$x,$y]-->$dir, map: [$x,$y] --> $mdir";
			
			$howfar = ceil($this->odistance($rx,$ry,$x,$y));
			if ($howfar < $distances[$mdir]) {
				$distances[$mdir] = $howfar;
				$obstacles[$mdir] = "($x,$y)";
			}
		}
		asort($obstacles);
		asort($distances);
		return array($obstacles,$distances);
	}
	
	function normalizeAngle($a){
		if ($a<0) $a+=360;
		return $a;
	}

	
	function setOccupied($x,$y,$occupied=true){
		if ($this->isInside($x,$y)) $this->map[$x][$y] = $occupied;
	}
	
	function visualize($path){
		$rows = $this->rows();
		$cols = $this->cols();
		$grid=[];
		$grid = $this->map;
		for($i=1;$i<=$path[0];$i++){
			$p = $this->idx2xy($path[$i]);
			$v = $grid[$p[0]][$p[1]];
			$grid[$p[0]][$p[1]] = "[$v]";
		}
		echo "\nPath:";
		echo "\n<table>";
		for($i=0;$i<$rows;$i++){
			echo "\n<tr>"; 
			for($j=0;$j<$cols;$j++){
				echo sprintf("<td>%5s</td>",$grid[$i][$j]);
			}
			echo "\n</tr>";
		}
		echo "\n</table>";	
	}
	
	function visualize2($marks){
		$rows = $this->rows();
		$cols = $this->cols();
		echo "\n<table>";
		for($i=0;$i<$rows;$i++){
			echo "\n<tr>"; 
			for($j=0;$j<$cols;$j++){
				$v = $this->map[$i][$j]?1:0;
				if (isset($marks[$i][$j])) $v="[$v]";
				echo sprintf("<td>%5s</td>",$v);
			}
			echo "\n</tr>";
		}
		echo "\n</table>";	
	}
}


	
