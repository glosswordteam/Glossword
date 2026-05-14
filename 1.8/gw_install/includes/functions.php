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

if (!defined('IS_CLASS_GW2_FUNCTIONS')) {
    define('IS_CLASS_GW2_FUNCTIONS', 1);

    /* */
    class tkit_functions
    {

        /* */
        public function make_int16($s)
        {
            $h = hash('md5', $s);
            $n = '';
            for ($i = 0; $i < 32; $i++) {
                $n .= hexdec(substr($h, $i, 2));
                if (strlen($n) > 16) {
                    $n = substr($n, 0, 16);
                    break;
                }
                $i++;
            }
            return $n;
        }

        public function hexbin($s)
        {
            return pack("H*", $s);
        }

        /* */
        public function make_str_random($a = 6)
        {
            $b = 'bdfghijkmnqrstuwzQWRUSDFGJLZN23456789';
            $c = strlen($b);
            $s = '';
            for ($i = 0; $i < $a; $i++) {
                $s .= $b[mt_rand(0, $c - 1)];
            }
            return $s;
        }

        /**
         * Get file contents. Binary and fail safe.
         *
         * @param string $filename Full path to filename
         * @return  string  File contents
         */
        public function file_get_contents($filename)
        {
            if (!file_exists($filename)) {
                return '[file_get_contents: file ' . $filename . ' does not exist]';
            }
            if (function_exists('file_get_contents')) /* PHP4 CVS only */ {
                $str = file_get_contents($filename);
            } else {
                /* file() is binary safe from PHP 4.3.0
                faster: $str = implode('', file($filename));
                */
                $fd = fopen($filename, "rb");
                $str = fread($fd, filesize($filename));
                fclose($fd);
            }
            if ($str == '') {
                return '[loadfile: ' . $filename . ' is empty]';
            }
            return $str;
        }

        /**
         * Put contents into a file. Binary and fail safe.
         *
         * @param string $filename Full path to filename
         * @param string $content File contents
         * @param string $mode [ w = write new file (default) | a = append ]
         * @return  TRUE if success, FALSE otherwise
         */
        public function file_put_contents($filename, $content, $mode = "w")
        {
            $filename = str_replace('\\', '/', $filename);
            /* new file */
            if (!file_exists($filename)) {
                /* check & create directories first */
                $arParts = explode('/', $filename);
                $intParts = (sizeof($arParts) - 1);
                $d = '';
                for ($i = 0; $i < $intParts; $i++) {
                    $d .= $arParts[$i] . '/';
                    if (is_dir($d)) {
                        continue;
                    } else {
                        $oldumask = umask(0);
                        @mkdir($d, 0777);
                        @chmod($d, 0777);
                        umask($oldumask);
                    }
                }
                /* Nothing to write */
                if ($content == '') {
                    return true;
                }
                /* Write to file */
                $fp = @fopen($filename, "wb");
                @chmod($filename, 0777);
                if ($fp) {
                    fputs($fp, $content);
                } else {
                    return false;
                }
                fclose($fp);
            } else {
                /* Append to file */
                /* note: binary mode is transparent */
                if ($fp = @fopen($filename, $mode . 'b')) {
                    $is_allow = flock($fp, 2); /* lock for writing & reading */
                    if ($is_allow) {
                        fputs($fp, $content, strlen($content));
                    }
                    flock($fp, 3); /* unlock */
                    fclose($fp);
                } else {
                    return false;
                }
            }
            return true;
        }

        /**
         * Removes a file or en empty directory from disk
         */
        public function file_remove($filename)
        {
            if (file_exists($filename) && is_file($filename) && unlink($filename)) {
                return true;
            } elseif (file_exists($filename) && is_dir($filename) && rmdir($filename)) {
                return true;
            }
            return false;
        }
    }
}