<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2007 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 */
/* ------------------------------------------------------- */
/* */
function gw_str_random($alphabet, $maxchar = 8)
{
	mt_srand( (double) microtime()*1000000);
	$str = '';
	$alphabet = ($alphabet) ? $alphabet : '23456789bdghkmnqsuvxyz';
	$alphabet = str_shuffle($alphabet);
	$len = strlen($alphabet);
	for ($i = 0; $i < $maxchar; $i++)
	{
		$sed = mt_rand(0, $len-1);
		$str .= $alphabet[$sed];
	}
	return $str;
}
/* */
function gw_make_captcha()
{
	/* font settings */
	$alphabet = 'QWRUSDFGZ23456789';
	$alphabet_length = strlen($alphabet);
	/* characters for a new image */
	$chars = gw_str_random('QWRUSDFGZ23456789', mt_rand(3, 6));
	$len = strlen($chars);
	$foreground_color = array(mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
	$background_color = array(mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
	$width      = 175;
	$height     = 60;
	/* final image */
	$im1   = imagecreatetruecolor($width, $height) or die();
	/* random text */
	$im2  = imagecreatetruecolor($width, $height);
	$bgcol = imagecolorallocate($im2, $background_color[0], $background_color[1], $background_color[2]);
	$fncol = imagecolorallocate($im2, $foreground_color[0], $foreground_color[1], $foreground_color[2]);
	imagefill($im1, 0, 0, $bgcol);
	imagefill($im2, 0, 0, $bgcol);
	/* */
	$ar_fonts = array('font1.png','font2.png','font3.png','font4.png');
	shuffle($ar_fonts);
	/* Load fonts */
	$font_metrics = array();
	foreach ($ar_fonts as $k => $font_file)
	{
		$font_resource[$k] = imagecreatefrompng('img/'.$font_file);
		imagealphablending($font_resource[$k], true);
		$fontfile_width[$k] = imagesx($font_resource[$k]);
		$fontfile_height[$k] = imagesy($font_resource[$k]) - 1;
		$symbol = 0;
		$reading_symbol = false;
		for ($i = 0; $i < $fontfile_width[$k] && $symbol < $alphabet_length; $i++)
		{
			$transparent = (imagecolorat($font_resource[$k], $i, 0) >> 24) == 127;
			if (!$reading_symbol && !$transparent)
			{
				$font_metrics[$k][$alphabet{$symbol}] = array('start' => $i);
				$reading_symbol = true;
				continue;
			}
			if ($reading_symbol && $transparent)
			{
				$font_metrics[$k][$alphabet{$symbol}]['end'] = $i;
				$reading_symbol = false;
				$symbol++;
				continue;
			}
		}
	}
	imagealphablending($im1, true);
	$fluctuation_amplitude = 5;
	$x = 10;
	/* Create text */
	for ($i = 0; $i < $len; $i++)
	{
		$font_file_id = mt_rand(0, sizeof($ar_fonts)-1);
		$m = $font_metrics[$font_file_id][$chars{$i}];
		$y = mt_rand(-$fluctuation_amplitude, $fluctuation_amplitude)+($height-$fontfile_height[$font_file_id])/2+2;
		/* Font size -6 big .. 6 small */
		$shift = $resize = mt_rand(-6, 6);
		imagecopyresampled($im1, $font_resource[$font_file_id], 
			$x-$shift, $y,
			$m['start'], 1,
			$m['end']-$m['start'] - $resize,
			$fontfile_height[$font_file_id] - $resize,
			$m['end']-$m['start'], 
			$fontfile_height[$font_file_id]
		);
		$x += $m['end'] - $m['start'] - $shift;
	}
	/* Clean font images */
	foreach ($font_resource as $k => $v)
	{
		imagedestroy($font_resource[$k]);
	}
	/* The distortion algorithm was taken from KCAPTCHA http://captcha.ru/ */
	$x = $width;
	$center = $x/2;
	// periods
	$rand1=mt_rand(750000,1200000)/10000000;
	$rand2=mt_rand(750000,1200000)/10000000;
	$rand3=mt_rand(750000,1200000)/10000000;
	$rand4=mt_rand(750000,1200000)/10000000;
	// phases
	$rand5=mt_rand(0,3141592)/500000;
	$rand6=mt_rand(0,3141592)/500000;
	$rand7=mt_rand(0,3141592)/500000;
	$rand8=mt_rand(0,3141592)/500000;
	// amplitudes
	$rand9=mt_rand(230,320)/110;
	$rand10=mt_rand(230,350)/110;
	// wave distortion
	for($x=0;$x<$width;$x++){
		for($y=0;$y<$height;$y++){
			$sx=$x+(sin($x*$rand1+$rand5)+sin($y*$rand3+$rand6))*$rand9-$width/2+$center+1;
			$sy=$y+(sin($x*$rand2+$rand7)+sin($y*$rand4+$rand8))*$rand10;
			if($sx<0 || $sy<0 || $sx>=$width-1 || $sy>=$height-1){
				$color=255;
				$color_x=255;
				$color_y=255;
				$color_xy=255;
			}else{
				$color=imagecolorat($im1, $sx, $sy) & 0xFF;
				$color_x=imagecolorat($im1, $sx+1, $sy) & 0xFF;
				$color_y=imagecolorat($im1, $sx, $sy+1) & 0xFF;
				$color_xy=imagecolorat($im1, $sx+1, $sy+1) & 0xFF;
			}
			if($color==0 && $color_x==0 && $color_y==0 && $color_xy==0){
				$newred=$foreground_color[0];
				$newgreen=$foreground_color[1];
				$newblue=$foreground_color[2];
			}else if($color==255 && $color_x==255 && $color_y==255 && $color_xy==255){
				$newred=$background_color[0];
				$newgreen=$background_color[1];
				$newblue=$background_color[2];	
			}else{
				$frsx=$sx-floor($sx);
				$frsy=$sy-floor($sy);
				$frsx1=1-$frsx;
				$frsy1=1-$frsy;
				$newcolor = ($color*$frsx1*$frsy1+ $color_x*$frsx*$frsy1+ $color_y*$frsx1*$frsy+ $color_xy*$frsx*$frsy);
				if ($newcolor>255) { $newcolor=255; }
				$newcolor = $newcolor/255;
				$newcolor0 = 1-$newcolor;
				$newred=$newcolor0*$foreground_color[0]+$newcolor*$background_color[0];
				$newgreen=$newcolor0*$foreground_color[1]+$newcolor*$background_color[1];
				$newblue=$newcolor0*$foreground_color[2]+$newcolor*$background_color[2];
			}
			imagesetpixel($im2, $x, $y, imagecolorallocate($im2, $newred, $newgreen, $newblue));
		}
	}
	imagecopymerge($im2, imagecreatefrompng('img/bg.png'), 0, 0, mt_rand(0, 60), mt_rand(0, 10), 300, 60, mt_rand(5, 25));
	/* */
	header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Content-type: image/jpeg');
	imagejpeg($im2, '', 25);
	imagedestroy($im1);
	imagedestroy($im2);

	/* Insert CAPTCHA number into database */
	global $sys;
	include('../../db_config.php');
	include('../../'.$sys['path_gwlib'].'/class.func.php');
	include('../../'.$sys['path_gwlib'].'/class.db.mysql.php');
	$oDb = new gwtkDb;
	/* Clean old entries */
	$oDb->sqlExec('DELETE FROM `'.$sys['tbl_prefix'].'captcha` WHERE date_created < ' . ($sys['time_gmt'] - 3600));
	/* Insert number */
	$oDb->sqlExec('INSERT INTO `'.$sys['tbl_prefix'].'captcha` (`date_created`, `captcha`) VALUES (\''.$sys['time_gmt'].'\',\''.$chars.'\')');
}
/* Auto time for server */
$maketimes = time();
$offset = @date('Z');
$sys['time_gmt'] = $maketimes - (@date('I') ? ($offset - 3600) : $offset);
/* */
gw_make_captcha();

/* end of file */
?>