<?php
/**
 *  $Id: func.shuffle.php 84 2007-06-19 13:01:21Z yrtimd $
 */
/**
 *  Glossword - glossary compiler (http://glossword.info/dev/) 
 *  © 2002-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
// --------------------------------------------------------
/**
 *  Functions to shuffle an array
 */
// --------------------------------------------------------


function gwShuffle($maxBanners=1, $Array){
    $ArrayC = count($Array);
    ### init new array
    for ($i=0; $i < $ArrayC; $i++) { $randA[$i] = $i; }
    ### shuffle
    $randA = sh($randA);
    ### checking max value
    if ($maxBanners > $ArrayC) {$maxBanners = $ArrayC - 1;}
    $str = "";
    $i2 = 0;
	for (reset($Array); list($key, $val) = each($Array);)
	{
		if ($i2 < $maxBanners)
		{
			$numR = $randA[$i2];
			if (preg_match("/^([0-9]{1,16})$/", $key))
			{
				if (isset($Array[$numR]))
				{ 
					$str[] = $Array[$numR];
				}
			}
			else {
				$str[][$key] = $val;
			}
		}
		$i2++;
	}
	return $str;
}
function rnd($val){
    mt_srand((double)microtime()*1000000000);
    $v = rand(0,$val);
    return $v;
} # end of function
function sh($array){
    for ($i=count($array); $i > 0; $i--) {
        $j = rnd($i);
        if ($j >= count($array)) { $j = $j-1;}
        $temp = $array[$i-1];
        $array[$i-1] = $array[$j];
        $array[$j]= $temp;
    }
    $fA = $array;
    return $fA;
} # end of function
?>