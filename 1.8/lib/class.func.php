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

/* --------------------------------------------------------
 * Library functions for daily use
 * ----------------------------------------------------- */

/* --------------------------------------------------------
 * Functions that must work without class initialization
 * ----------------------------------------------------- */

/* --------------------------------------------------------
 * Other useful functions
 * ----------------------------------------------------- */

class gw_functions
{


    public function js_addslashes($t)
    {
        return str_replace(['\\', '\'', "\n", "\r"], ['\\\\', "\\'", "\\n", "\\r"], $t);
    }

    /**
     *
     */
    public function text_crc_unsigned($t, $pass = '')
    {
        return sprintf("%010u", crc32($pass . $t));
    }

    /**
     * Convert HTML-code into Javascript using document.write()
     *
     * @param string Text to output
     * @return  Javascript code
     */
    public function text_html2js($t)
    {
        $t = '<script type="text/javascript">/*<![CDATA[*/'
            . 'document.write(\''
            . str_replace("'", "\'", $t)
            . '\');/*]]>*/</script>';
        return $t;
    }

    /**
     * Generates a random string using a reduced character set.
     *
     * Ambiguous characters are excluded to improve readability.
     * Two groups of confusing symbols are removed.
     *
     * @param int $maxChar Maximum generated string length
     * @param int $charSet Character set selector:
     *                     0 = all,
     *                     1 = numbers,
     *                     2 = lowercase,
     *                     3 = uppercase,
     *                     4 = numbers + lowercase,
     *                     5 = lowercase + uppercase
     * @param string $first Prefix for returned string
     * @return string Generated string
     */
    public function text_make_uid($maxChar = 8, $charSet = 0, $first = '')
    {
        // Exclude ambiguous characters.
        // Group 1: 0, 1, l, I
        // Group 2: a, c, e, o, p, x, A, C, E, H, O, K, M, P, X
        $result = '';
        $charsNumbers = '23456789';
        $charsLower = 'bdfghijkmnqrstuvwyz';
        $charsUpper = 'QWRYUSDFGJLZVN';

        if ($charSet == 1) {
            $chars = $charsNumbers;
        } elseif ($charSet == 2) {
            $chars = $charsLower;
        } elseif ($charSet == 3) {
            $chars = $charsUpper;
        } elseif ($charSet == 4) {
            $chars = $charsNumbers . $charsLower;
        } elseif ($charSet == 5) {
            $chars = $charsLower . $charsUpper;
        } else {
            $chars = $charsNumbers . $charsLower . $charsUpper;
        }

        $charsLength = strlen($chars);

        for ($i = 0; $i < $maxChar; $i++) {
            $randomIndex = mt_rand(0, $charsLength - 1);
            $result .= $chars[$randomIndex];
        }

        $first = (string)$first;
        if ($first !== '') {
            $result = $first . substr($result, 0, strlen($result) - strlen($first));
        }

        return $result;
    }

    /**
     * Returns correct number format in HTML-code.
     * For example, English notation:
     * 1,234.56
     * French (also Russian and many others) notation:
     * 1 234,56
     * This function also fixes problem with HTML-code
     * occured by space in every group of thousands (French notation).
     *
     * @param integer $int numbers
     * @param integer $dec decimals
     * @return  string  complete HTML-code
     */
    public function number_format($int, $dec = 0, $ar = ['decimal_separator' => '.', 'thousands_separator' => ' '])
    {
        return str_replace(
            ' ',
            '&#160;',
            number_format($int, $dec, $ar['decimal_separator'], $ar['thousands_separator'])
        );
    }

    /**
     * Get a random number
     * @return  float  Random number
     */
    public function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float)$sec + ((float)$usec * 100000);
    }

    /**
     * Executes php-code.
     *
     * @param string $filename Full path to filename
     * @param int $is_db_restart Re-connect to database. Useful when included script connects to another database.
     * @return  string  File results
     */
    public function file_exe_contents($filename, $is_db_restart = 1)
    {
        $str = '';
        if (file_exists($filename)) {
            ob_start();
            include($filename);
            $str_return = ob_get_contents();
            ob_end_clean();
            if ($is_db_restart) {
                global $oDb;
                $oDb = new gwtkDb;
            }
            return $str_return;
        } else {
            return '[loadfile: file ' . $filename . ' does not exist]';
        }
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
        if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime(
            )) /* remove slashes, 23 march 2002 */ {
            $str = stripslashes($str);
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
            $oldumask = umask(0002);
            $fp = @fopen($filename, "wb");
            @chmod($filename, 0777);
            if ($fp) {
                fputs($fp, $content);
            } else {
                return false;
            }
            umask($oldumask);
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
     * Removes file from disk
     */
    public function file_remove_f($filename)
    {
        if (file_exists($filename) && is_file($filename) && unlink($filename)) {
            return true;
        }
        return false;
    }

    /**
     * Makes string wrapped, multibyte.
     * 1999
     * 31 march 2003
     * 1 Nov 2005
     * 4 Apr 2008 - fixes for the end of lines
     *
     * @param string $str A string to wrap
     * @param int $len Maximum length, characters
     * @param string $d Delimiter, default is "\n"
     * @param int $isBinary [ 0 - off | 1 - use binary-safe convertion ]
     * @return  string  Parsed string
     */
    public function mb_wordwrap($str, $len, $d = "\n", $isBinary = 0)
    {
#		prn_r( $str, 'mb_wordwrap' );
        $arr = [];
        /* return empty string, 31 march 2003 */
        if ($len <= 0) {
            return $str;
        };
        $str_temp = '';
        $cnt_char = 0;
        preg_match_all("/./u", $str . ' ', $ar_letters);
        foreach ($ar_letters[0] as $k => $v) {
#prn_r( $v .' '.$cnt_char );
            if ($cnt_char < $len) {
                $str_temp .= $v;
            } else {
                if ($isBinary) {
                    $arr[] = $str_temp;
                    $str_temp = $v;
                    $cnt_char = 0;
                } else {
                    if ($v == ' ' || $v == "\r" || $v == "\n") {
                        $arr[] = $str_temp;
                        $str_temp = $v;
                        $cnt_char = 0;
                    } else {
                        $str_temp .= $v;
                    }
                }
            }
            ++$cnt_char;
        }
        $arr[] = $str_temp;
        return implode($d, $arr);
    }

    /* Special for chunking long strings. Returns the first line only. */
    public function mb_wordwrap_first($str, $len, $d = "\n", $isBinary = 0)
    {
        global $sys;
        $arr = [];
        $str = str_replace('&#032;', ' ', $str);
        $str = str_replace('&#020;', ' ', $str);
        $str = str_replace('&#32;', ' ', $str);
        $str = str_replace('&#20;', ' ', $str);
        /* return empty string, 31 march 2003 */
        if ($len < 0) {
            return $str;
        };
        $str_temp = '';
        $cur_length = 0;
        $ar_words = explode(' ', $str . ' ', 100);
        foreach ($ar_words as $k => $v) {
            $cur_length += mb_strlen(' ' . $v);
            if ($cur_length >= $len) {
                return $str_temp . $d;
            }
            $str_temp .= ' ' . $v;
        }
        return $str;
        /*
         too expensive
        preg_match_all("/./u", $str.' ', $ar_letters);
        foreach ($ar_letters[0] as $k => $v)
        {
            if ( $k == ($len * (sizeof($arr) + 1) + $int_char) )
            {
                if ($isBinary)
                {
                    $arr[$k] = $str_temp;
                    $str_temp = '';
                    return $arr[$k].$d;
                }
                else
                {
                    if ($v == ' ')
                    {
                        $int_char = 0;
                        $arr[$k] = $str_temp;
                        $str_temp = '';
                        return $arr[$k].$d;
                    }
                    else
                    {
                        $int_char++;
                    }
                }
            }
            else if ( $len * (sizeof($arr) + 1) + $int_char >= $slen
                && ($k) == $slen )
            {
                $arr[$k] = $str_temp;
            }
            $str_temp .= $v;
        }
        return implode($d, $arr);
        */
    }

    /**
     * Converts a string with e-mail address
     * into unresolvable crap for mail robots.
     *
     * @param string $s String with HTML-tag <a href="mailto:">
     * @return  string  Parsed string
     * @see hardWrap()
     */
    public function text_mailto($s)
    {
        preg_match_all("/href=\"mailto:(.*?)\">(.*?)<\/a>/i", $s, $e);
        /* encode `mailto:' */
        if (isset($e[1][0])) {
            $s = str_replace($e[1][0], '', $s);
            $s = str_replace(
                'href="mailto:',
                'title="mailto:' . $e[1][0] . '" ' .
                'href="mailto:' . $this->text_make_uid(mt_rand(2, 8), 2) . '@' . $this->text_make_uid(
                    mt_rand(2, 8),
                    2
                ) . '.com" onmouseover="this.href=\''
                . $this->mb_wordwrap('mailto:' . strtolower($e[1][0]), mt_rand(2, 4), "'+'", 1)
                . "'",
                $s
            );
            return $s;
        }
    }

    /**
     * Converts a string to a sequence of hex byte values.
     *
     * ASCII characters are kept unchanged. Non-ASCII bytes are converted
     * to hex form, optionally prefixed with "\x".
     *
     * @param string $text Input text
     * @param int $withPrefix Whether to prepend "\x" before each hex byte
     * @return string Converted string
     */
    public function text_bytes_to_hex($text, $withPrefix = 1)
    {
        $result = '';
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $byte = ord($text[$i]);

            if ($byte < 128) {
                $result .= $text[$i];
            } else {
                $hex = sprintf('%02x', $byte);
                $result .= $withPrefix ? '\\x' . $hex : $hex;
            }
        }

        return $result;
    }

    /**
     * Converts a CSS-file contents into one string
     *
     * @param string $t Text data
     * @param int $is_debug Skip convertion
     * @return   string  Optimized string
     */
    public function text_smooth_css($t, $is_debug = 0)
    {
        if ($is_debug) {
            return $t;
        }
        /* Remove comments */
        $t = preg_replace("/\/\*(.*?)\*\//s", ' ', $t);
        /* Remove new lines, spaces */
        $t = preg_replace("/(\s{2,}|[\r\n|\n|\t|\r])/", ' ', $t);
        /* Join rules */
        $t = preg_replace('/([,|;|:|{|}]) /', '\\1', $t);
        $t = str_replace(' {', '{', $t);
        /* Remove ; for the last attribute */
        $t = str_replace(';}', '}', $t);
        $t = str_replace(' }', '}', $t);
        return $t;
    }

    /**
     * Converts a HTML-file contents into one string
     *
     * @param string $t Text data
     * @param int $is_debug Skip convertion
     * @return   string  Optimized string
     * @globals  LF
     */
    public function text_smooth_html($t, $is_debug = 0)
    {
        /* Note that <pre>formatted text will be converted into single line too */
        if ($is_debug) {
            return $t;
        }
        /* Remove new lines and tabs */
        $t = preg_replace("/(\r\n|\n|\r|\t)/", ' ', $t);
        /* Remove comments */
        $t = preg_replace("/<!--(.*?)-->/si", '', $t);
        /* Connect HTML-tags */
        $t = str_replace('> </', '></', $t);
        /* \s is not allowed for multibyte characters */
        $t = preg_replace("/ {2,}/", ' ', $t);
        /* Place a newline character if any */
        $t = str_replace(LF, "\n", $t);
        return $t;
    }

    /**
     * Calculates recommended textarea height based on content.
     *
     * @param string $text Input text
     * @param int $maxRows Maximum number of rows
     * @return int
     */
    public function getFormHeight($text, $maxRows = 25)
    {
        $text = (string)$text;

        $lineBreaks = substr_count($text, "\n");
        $rows = (int)(mb_strlen($text) / 60) + $lineBreaks + 2;

        return ($rows > (int)$maxRows) ? (int)$maxRows : $rows;
    }


    /**
     * Calculates full years passed since a given date.
     *
     * @param int $unixTime Reference Unix timestamp
     * @param int $year Start year
     * @param int $month Start month
     * @param int $day Start day
     * @return int Number of full years passed
     */
    public function dateGetPassedYears($time_unix, $y, $m, $d)
    {
        $currentYear = (int)date('Y', $unixTime);
        $currentMonth = (int)date('n', $unixTime);
        $currentDay = (int)date('j', $unixTime);

        $yearsPassed = $currentYear - (int)$year;

        if (
            $currentMonth < (int)$month
            || ($currentMonth === (int)$month && $currentDay < (int)$day)
        ) {
            $yearsPassed--;
        }

        return $yearsPassed;
    }

    /**
     * Returns current Unix time shifted by GMT offset.
     *
     * Optionally adds one extra hour for daylight saving time.
     *
     * @param float $gmtOffset GMT offset in hours (+3 Moscow, -6 USA & Canada)
     * @param int $useDst Whether to add one DST hour
     * @return int Shifted Unix timestamp
     */
    public function date_get_localtime($gmtOffset, $useDst = 1)
    {
        $offsetSeconds = (float)$gmtOffset * 3600;

        if ((int)$useDst === 1) {
            $offsetSeconds += 3600;
        }

        return time() + (int)$offsetSeconds;
    }

    /**
     * Converts seconds to HH:MM:SS format.
     *
     * @param int $totalSeconds Amount of seconds
     * @return string Time in HH:MM:SS format
     */
    public function dateSecToTime($totalSeconds)
    {
        $totalSeconds = (int)$totalSeconds;

        if ($totalSeconds < 0) {
            $totalSeconds = 0;
        }

        $hours = (int)($totalSeconds / 3600);
        $minutes = (int)(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Checks whether a value is a non-negative integer (digits only).
     *
     * Accepts numeric strings like "123" and integers.
     *
     * @param mixed $value Value to check
     * @return bool TRUE if value is a non-negative integer, FALSE otherwise
     */
    public function is_num($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false && $value >= 0;
    }

    /**
     * Get string length, multibyte.
     *
     * @param string $t Any string content
     * @return  int     String length
     */
    public function mb_strlen($t, $encoding = 'UTF-8')
    {
        /* --enable-mbstring */
        if (function_exists('mb_strlen')) {
            return mb_strlen($t, $encoding);
        } else {
            return strlen(utf8_decode($t));
        }
    }

    /**
     * Decodes numeric HTML entities into UTF-8 characters.
     *
     * Example:
     * - &#1040; -> �
     *
     * @param string $string Input string
     * @return string Decoded string
     */
    public function gw_numeric2character($string)
    {
        if (!function_exists('mb_decode_numericentity')) {
            return $string;
        }

        $convMap = [0x0000, 0x2FFFF, 0, 0xFFFF];

        return mb_decode_numericentity($string, $convMap, 'UTF-8');
    }

    /**
     * Encodes UTF-8 characters as numeric HTML entities.
     *
     * Example:
     * - � -> &#1040;
     *
     * @param string $string Input string
     * @return string Encoded string
     */
    public function gw_character2numeric($string)
    {
        if (!function_exists('mb_encode_numericentity')) {
            return $string;
        }

        $convMap = [0x0000, 0x2FFFF, 0, 0xFFFF];

        return mb_encode_numericentity($string, $convMap, 'UTF-8');
    }

    /**
     * Converts string between character encodings.
     *
     * Prefers iconv() when available because conversion results may differ
     * between iconv() and mb_convert_encoding() for some legacy encodings.
     *
     * @param string $string Input string
     * @param string $fromEncoding Source encoding
     * @param string $toEncoding Target encoding
     * @return string Converted string
     */
    public function gwConvertCharset($string, $fromEncoding, $toEncoding)
    {
        // Skip conversion when encodings are the same
        if ($fromEncoding === $toEncoding) {
            return $string;
        }

        // Prefer iconv() when available
        if (function_exists('iconv')) {
            $result = @iconv($fromEncoding, $toEncoding, $string);

            if ($result !== false) {
                return $result;
            }
        }

        // Fallback to mb_convert_encoding()
        if (function_exists('mb_convert_encoding')) {
            $result = @mb_convert_encoding($string, $toEncoding, $fromEncoding);

            if ($result !== false) {
                return $result;
            }
        }

        // Legacy fallback for older environments
        if (function_exists('recode_string')) {
            $result = @recode_string($fromEncoding . '..' . $toEncoding, $string);

            if ($result !== false) {
                return $result;
            }
        }

        // Return original string if no converter is available
        return $string;
    }

    /**
     * Converts array of hex strings to decimal values.
     *
     * @param array $hexArray Array of hex values (e.g. ['FF', '00', 'AA'])
     * @return array Array of integers
     */
    public function math_hexdec($hexArray)
    {
        foreach ($hexArray as $key => $value) {
            $hexArray[$key] = hexdec($value);
        }

        return $hexArray;
    }

    /**
     * Converts hex color to its "negative" version.
     *
     * @param string $hex Hex color string
     * @return string
     */
    public function math_hex2negative($hex)
    {
        $hexArray = $this->math_hex2ar($hex);
        $decArray = array_map('hexdec', $hexArray);

        foreach ($decArray as $key => $value) {
            $inverted = 255 - $value;

            if ($inverted > 50 && $inverted < 150) {
                $inverted = 255;
            }

            $hexArray[$key] = sprintf('%02X', $inverted);
        }

        return implode('', $hexArray);
    }

    /**
     * Converts hex color into RGB array with integer values.
     *
     * Examples:
     * - math_hex2ar('0F0') returns [0, 255, 0]
     * - math_hex2ar('EE4400') returns [238, 68, 0]
     *
     * @param string $hex Hex color string with or without leading #
     * @return array RGB values
     */
    public function math_hex2ar($hex)
    {
        $hex = str_replace('#', '', $hex);

        // Convert short form like "0F0" to full form "00FF00"
        if (strlen($hex) == 3) {
            list($r, $g, $b) = sscanf($hex, '%1s%1s%1s');
            $hex = $r . $r . $g . $g . $b . $b;
        }

        return array_map('hexdec', str_split($hex, 2));
    }

    /**
     * Calculates factorial (n!) for a non-negative integer.
     *
     * Uses BCMath if available, fallback to integer math.
     *
     * @param int $n Input number (must be >= 0)
     * @return int|float|string Factorial value
     */
    public function math_fact($n)
    {
        $n = (int)$n;

        if ($n < 0) {
            return 0;
        }

        if ($n === 0) {
            return 1;
        }

        // Use BCMath if available (for big numbers)
        if (function_exists('bcmul')) {
            $result = '1';
            for ($i = 2; $i <= $n; $i++) {
                $result = bcmul($result, (string)$i);
            }
            return $result;
        }

        // Fallback (fast, but limited by PHP int/float)
        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }

        return $result;
    }

    /**
     * Converts dotted IP-address (IPV4) into database storable format.
     */
    public function ip2int($ip)
    {
        return sprintf("%u", ip2long($ip));
    }

    /**
     * Create a GZip-compressed string
     *
     * @param string $t Input data
     * @param int $level Gzip compress level [1..9], 1 by default.
     * @param int $is_send_header Use headers class [1 - yes | 0 - no]
     * @return string GZipped text
     * @globals  $_SERVER, $oHdr, PHP_VERSION_INT
     */
    public function text_gzip($str_return, $level = 1, $is_send_header = 1)
    {
        global $_SERVER, $oHdr;
        $int_length = strlen($str_return);
        $encoding = 0;
        if (function_exists('crc32') && function_exists('gzcompress')) {
            /* strpos() should be always compared as boolean */
            if (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
                $encoding = 'x-gzip';
            } elseif (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                $encoding = 'gzip';
            }
            if ($encoding) {
                if (function_exists('gzencode') && PHP_VERSION_INT > 40200) {
                    $str_return = gzencode($str_return, $level);
                } else {
                    $size = strlen($str_return);
                    $crc = crc32($str_return);
                    $str_return = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
                    $str_return .= substr(gzcompress($str_return, $level), 2, -4);
                    $str_return .= pack('V', $crc);
                    $str_return .= pack('V', $size);
                }
                if ($is_send_header) {
                    $oHdr->add('Content-Encoding: ' . $encoding);
                    $oHdr->add('Content-Length: ' . strlen($str_return));
                }
            }
        }
        return $str_return;
    }
}


/* automatic initialization */
$oFunc = new gw_functions;

