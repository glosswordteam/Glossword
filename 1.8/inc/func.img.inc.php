<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */

if (!defined('IN_GW')) {
    die('<!-- Not in App -->');
}


define('EXT_GIF', 1);
define('EXT_JPG', 2);
define('EXT_PNG', 3);

/**
 * Resize image and save JPEG file.
 *
 * @param string $srcFile   Source image path.
 * @param string $trgFile   Target image path.
 * @param int    $maxSize   Max output width or height.
 * @param int    $isDebug   Print debug output instead of saving file.
 *
 * @return bool
 */
function gw_image_resize($srcFile, $trgFile, $maxSize, $isDebug = 0)
{
    global $sys, $oFunc;

    if (!function_exists('imagecreatefrompng')) {
        return false;
    }

    if (!is_file($srcFile) || (int) $maxSize <= 0) {
        return false;
    }

    $imageInfo = getimagesize($srcFile);
    if (!$imageInfo || !isset($imageInfo[0], $imageInfo[1], $imageInfo[2])) {
        return false;
    }

    $srcWidth = (int) $imageInfo[0];
    $srcHeight = (int) $imageInfo[1];
    $imageType = (int) $imageInfo[2];

    $ratio = max($srcWidth, $srcHeight) / $maxSize;
    $ratio = max($ratio, 1.0);

    $trgWidth = (int) ($srcWidth / $ratio);
    $trgHeight = (int) ($srcHeight / $ratio);

    if ($imageType == EXT_JPG) {
        $srcImage = imagecreatefromjpeg($srcFile);
    } elseif ($imageType == EXT_PNG) {
        $srcImage = imagecreatefrompng($srcFile);
    } else {
        return false;
    }

    if (!$srcImage) {
        return false;
    }

    $trgImage = imagecreatetruecolor($trgWidth, $trgHeight);
    if (!$trgImage) {
        imagedestroy($srcImage);

        return false;
    }

    imagecopyresampled(
        $trgImage,
        $srcImage,
        0,
        0,
        0,
        0,
        $trgWidth,
        $trgHeight,
        $srcWidth,
        $srcHeight
    );

    ob_start();
    imagejpeg($trgImage, null, $sys['int_jpeg_compression']);
    $imageContent = ob_get_clean();

    imagedestroy($srcImage);
    imagedestroy($trgImage);

    if ($isDebug) {
        print '<br />' . $srcFile . ' => ' . $trgFile;

        return true;
    }

    if ($imageContent === false) {
        return false;
    }

    return (bool) $oFunc->file_put_contents($trgFile, $imageContent);
}

