<?php
function simulateLaserScan($grid, $origin, $numAngles, $maxRange, $angleMin, $angleMax, $angleIncrement) {
  // Get the dimensions of the grid
  $rows = count($grid);
  $cols = count($grid[0]);

  // Convert the minimum and maximum angles to radians
  $angleMinRad = deg2rad($angleMin);
  $angleMaxRad = deg2rad($angleMax);

  // Initialize an array to store the ranges for each angle
  $ranges = array_fill(0, $numAngles, $maxRange);

  // Iterate over each angle
  for ($i = 0; $i < $numAngles; $i++) {
    // Calculate the current angle
    $angle = $angleMinRad + $i * deg2rad($angleIncrement);

    // Initialize the range for this angle to the maximum range
    $range = $maxRange;

    // Calculate the step size for each ray
    $step = min($rows, $cols) / $maxRange;

    // Iterate over each step along the current ray
    for ($j = 0; $j < $maxRange; $j += $step) {
      // Calculate the current position along the ray
      $x = $origin[0] + $j * cos($angle);
      $y = $origin[1] + $j * sin($angle);

      // Check if the current position is within the grid
      if ($x >= 0 && $x < $rows && $y >= 0 && $y < $cols) {
        // Check if the current position is an obstacle
        if ($grid[floor($x)][floor($y)] == 1) {
          // Update the range for this angle
          $range = $j;
          break;
        }
      } else {
        // If the current position is outside the grid, set the range to the maximum range
        $range = $maxRange;
        break;
      }
    }

    // Store the range for this angle in the ranges array
    $ranges[$i] = $range;
  }

  // Return the ranges array
  return $ranges;
}

