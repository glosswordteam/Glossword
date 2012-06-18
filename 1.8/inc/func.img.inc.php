<?php

define("EXT_GIF", 1);
define("EXT_JPG", 2);
define("EXT_PNG", 3);

	function gw_image_resize($src_file, $trg_file, $new_size, $method = 'gd2', $is_debug = 0)
	{
		global $sys, $oFunc;
		if (!function_exists('imagecreatefrompng')){ return false; }
		$arImgInfo = getimagesize($src_file);
		// height/width
		$tmp['src_width'] = $arImgInfo[0];
		$tmp['src_height'] = $arImgInfo[1];
		// fix image size to width
#		$tmp['ratio'] = $tmp['src_width'] / $new_size;
		// fix image size to height
#		$tmp['ratio'] = $tmp['src_height'] / $new_size;
		// auto
		$tmp['ratio'] = (max($tmp['src_width'], $tmp['src_height']) / $new_size);
		//
		$tmp['ratio'] = max($tmp['ratio'], 1.0);
		$tmp['trg_width'] = intval($tmp['src_width'] / $tmp['ratio']);
		$tmp['trg_height'] = intval($tmp['src_height'] / $tmp['ratio']);
		/* Method for thumbnails creation */
		if ($arImgInfo[2] == EXT_JPG)
		{
			$src_img = imagecreatefromjpeg($src_file);
		}
		else if ($arImgInfo[2] == EXT_PNG)
		{
			$src_img = imagecreatefrompng($src_file);
		}
		else
		{
			return false;
		}
		$trg_img = imagecreatetruecolor($tmp['trg_width'], $tmp['trg_height']);
		/* */
		imagecopyresampled(
				$trg_img, $src_img, 0, 0, 0, 0,
				$tmp['trg_width'], $tmp['trg_height'],
				$tmp['src_width'], $tmp['src_height']
		);
		/* */
		ob_start();
		imagejpeg($trg_img, '', $sys['int_jpeg_compression']);
		$str_img = ob_get_contents();
		ob_end_clean();
		imagedestroy($src_img);
		imagedestroy($trg_img);
		if ($is_debug)
		{
			print '<br />'. $src_file . ' => ' . $trg_file;
		}
		else
		{
			$oFunc->file_put_contents($trg_file, $str_img);
		}
	}

?>