<?php

require("inc/pgm.inc.php");
//require("inc/pathfinding.inc - Copy.php");
require("inc/pathfinding.inc.php");

//header("content-type:text/plain");
if (isset($_GET["f"])) $mapname=$_GET["f"]; else $mapname="data/map.png";
$pgm = new PgmFile();
$png = $pgm->resizePNG($mapname,50);
$mapname = $pgm->fromPng($png);
$pgm->parse($mapname);
$pgm->debug();
$pgm->toCsv();
$pgm->toArray();
$pgm->toMap();
$png = $pgm->toPng();
$map=array(
[1,1,1,1,1,1,1,1,1,1],
[1,0,0,0,0,0,0,0,0,1],
[1,0,0,0,0,0,0,0,0,1],
[1,0,0,0,0,0,0,0,1,1],
[1,0,0,0,0,0,0,1,1,1],
[1,0,0,0,0,0,0,1,0,1],
[1,0,0,0,0,0,0,0,0,1],
[1,0,0,0,0,0,0,1,0,1],
[1,0,0,0,0,0,0,0,0,1],
[1,1,1,1,1,1,1,1,1,1],
);
$grid = new OGrid($map,0,false);
$grid->precalcAngles(1);
$grid->precalcAngles(2);
$grid->precalcAngles(3);
$grid->precalcAngles(5);

//$path = $grid->generatePath(0,0,9,5);
//$grid->visualize($path,[]);
$fov=180;
$rx=3;
$ry=4;
$rdir=0;
$grid->setOccupied(3,5);
$angleStart = -$fov/2 + $grid->getAngleFromDir($rdir);
$angleEnd = $fov/2 + $grid->getAngleFromDir($rdir);
echo "<br>\n$angleStart -> $angleEnd";
$scandata = $grid->simulateLaserScan($rx,$ry,$angleStart,$angleEnd,1,50);
$o = $grid->getObstacles($rx,$ry,$rdir,$scandata,1,1,0);
$obstacles = $o[0];
$distances = $o[1];
print_r($obstacles);
print_r($distances);
$marks=[];
// for($i=1;$i<=$path[0];$i++){
	// $p = $grid->idx2xy($path[$i]);
	// $marks[$p[0]][$p[1]] = 1;
// }
$marks[$rx][$ry]=2;
$scandata2=[];
//x' = x - a;
//y' = y - a;
//x'' = x'cos(theta) - y'sin(theta) = (x-a)cos(theta) - (y-a)sin(theta)
for($i=0;$i<count($scandata);$i++){
	$x= $scandata[$i][0];
	$y= $scandata[$i][1];
	//$p = $grid->getNewCoord($x,$y,$rx,$ry,$rdir);
	//echo "<br>[$x,$y] -> [$p[0],$p[1]]";
	$marks[$x][$y] = 2;
}		
$grid->visualize2($marks);

$grid = new OGrid($pgm->bits,1,false);
//$path = $grid->generatePath(0,49,49,0);
//echo sprintf("\n<br>Path length = %d",$path[0]);
//$grid->visualize($path);

echo "<br><br>";

$robot = array("rx"=>17,"ry"=>14,"rdir"=>1,"len"=>2,"breadth"=>2,"fov"=>180);
$grid->setOccupied(18,15);
$grid->setOccupied(18,27);
$grid->setOccupied(18,35);
$grid->setOccupied(18,45);

print_r($grid->getNewCoord(17,15,17,14,1));
print_r($grid->getNewCoord(18,15,17,14,1));
exit;
$angle_inc = 2;
$max_range = 20;

$angleStart = -$robot["fov"]/2 + $grid->getAngleFromDir($robot["rdir"]);
$angleEnd = $robot["fov"]/2 + $grid->getAngleFromDir($robot["rdir"]);
echo "<br>\n$angleStart -> $angleEnd";
$scandata = $grid->simulateLaserScan(
	$robot["rx"],$robot["ry"],
	$angleStart,$angleEnd,$angle_inc,$max_range
);

$o = $grid->getObstacles($robot["rx"],$robot["ry"],$robot["rdir"],$scandata,
	$robot["len"],$robot["breadth"],1);
$obstacles = $o[0];
$distances = $o[1];
print_r($obstacles);
//print_r($distances);

echo "<br><br>";
$marks=[];
// for($i=1;$i<=$path[0];$i++){
	// $p = $grid->idx2xy($path[$i]);
	// $marks[$p[0]][$p[1]] = 1;
// }
$marks[$robot["rx"]][$robot["ry"]]=2;
for($i=0;$i<count($scandata);$i++){
	$x= $scandata[$i][0];
	$y= $scandata[$i][1];
	$marks[$x][$y] = 2;
}		
$grid->visualize2($marks);

