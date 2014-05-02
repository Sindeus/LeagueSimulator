<?php

/*
 * Library for Statistics calculations.
 */

/*
 * Returns an array containing the slope and intercept of a best-fit linear regression for a set of data.
 * Interprets one arg as y values for x = { 0, 1, 2, 3, ... }.
 * Interprets two args as x and y values, respectively.
 * Returns -1 on error.
 */
function stats_reg_lin(array $x, array $y = null) {

    if($x == null || ($y !== null && count($x) !== count($y))) {
        return -1;
    }
    if($y == null) {
        $y = $x;
        $x = array();
        for($i = 0; i < count($y); $i++) {
            $x[] = $i;
        }
    }
    
    $n = count($x);
    $sumx = 0;
    $sumy = 0;
    $sumxy = 0;
    $sumxsq = 0;
    for($i = 0; $i < $n; $i++) {
        $xvar = $x[$i];
        $yvar = $y[$i];
        
        if(!is_numeric($xvar) || !is_numeric($yvar)) {
            return -1;
        }
        
        $sumx += $xvar;
        $sumy += $yvar;
        $sumxy += $xvar * $yvar;
        $sumxsq +=  pow($xvar, 2);
    }
    $slope = ( $n * $sumxy - $sumx * $sumy ) / ( $n * $sumxsq - pow($sumx, 2) );
    $int = ($sumy - $slope * $sumx) / $n;
    return array($slope, $int);
}

/*
 * Returns arithmatic mean of a set of data.
 * Returns -1 on error.
 */
function stats_calc_amean(array $x) {
    
    if($x == null) {
        return -1;
    }
    
    $sum = 0;
    foreach($x as $a) {
        if(!is_numeric($a)) {
            return -1;
        }
        $sum += $a;
    }
    return $sum / count($x);
}

/*
 * Returns variance of a set of data.
 * Returns -1 on error.
 */
function stats_calc_variance(array $x) {
    
    if($x == null) {
        return -1;
    }
    
    if(count($x) == 0) {
        return 0;
    }
    
    $mean = stats_calc_amean($x);
    
    $sum = 0;
    foreach($x as $a) {
        if(!is_numeric($a)) {
            return -1;
        }
        $sum += pow(($a - $mean), 2);
    }
    return $sum / (count($x) - 1);
}

/*
 * Returns standard deviation of a set of data.
 * Returns -1 on error.
 */
function stats_calc_stdev(array $x) {
    if($x == null) {
        return -1;
    }
    return sqrt(stats_calc_variance($x));
}

/*
 * Returns correlation, r, between data sets x and y.
 * Interprets one arg as y values for x = { 0, 1, 2, 3, ... }.
 * Interprets two args as x and y values, respectively.
 * Returns -1 on error.
 */
function stats_calc_r(array $x, array $y = null) {
    
    if($x == null || ($y !== null && count($x) !== count($y))) {
        return -1;
    }
    if($y == null) {
        $y = $x;
        $x = array();
        for($i = 0; i < count($y); $i++) {
            $x[] = $i;
        }
    }
    
    $stdevx = stats_calc_stdev($x);
    $stdevy = stats_calc_stdev($y);
    $meanx = stats_calc_amean($x);
    $meany = stats_calc_amean($y);
    
    $zscore = array();
    for($i = 0; $i < count($x); $i++) {
        if(!is_numeric($x[$i]) || !is_numeric($y[$i])) {
            return -1;
        }
        $zscore[$i] = ( ( $x[$i] - $meanx ) / $stdevx ) * ( ( $y[$i] - $meany ) / $stdevy );
    }
    return array_sum($zscore) / count($x);
} 

/*
 * Generates a random number from a normal distribution with specified mean and standard deviation,
 * optionally between two hard limits min and max.
 * @author: YTowOnt9
 * @source: http://stackoverflow.com/questions/15951563/how-can-i-choose-a-random-number-but-with-a-normal-probability-distribution-in-p
 */
function stats_rand_normal($mean, $std_deviation, $min = null, $max = null){
    $x = mt_rand() / mt_getrandmax();
    $y = mt_rand() / mt_getrandmax();
    $n = sqrt(-2 * log($x)) * cos(2 * pi() *$y) * $std_deviation + $mean;
    if(($min !== null && $n < $min) || ($max !== null && $n > $max)) {
        return stats_rand_normal($mean, $std_deviation, $min = $min, $max = $max);
    }
    return $n;
}



?>
