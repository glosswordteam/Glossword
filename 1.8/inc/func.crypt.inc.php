<?php
/**
 *  $Id: func.crypt.inc.php 84 2007-06-19 13:01:21Z yrtimd $
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
 *  Encode and decode URL functions
 */
// --------------------------------------------------------
/**
 * HTML-library v1.2 by Dmitry Shilnikov (c) 2002
 *
 * Encrypts url.
 *
 * @param   int $mode   Encryption modes:
 *                        0 - Disabled. Passthrough.
 *                        1 - TinyCrypt. Adds r=RND to url parameters,
 *                            where RND is 10..99 and processed with
 *                            tinyCrypt($in)
 *                        2 - Mcrypt. Adds r=RND to url parameters
 *                            where RNUM is 0..999 and processed with
 *                            str2hex(mcrypt(RNUM)).
 *                           --with-mcrypt required
 * @param   string  $in   Input url parameters. Example: a=view&t=tpl&id=123
 * @return  string  Encrypted string. Examples (mode):
 *                    (0) - a=view&t=tpl&id=123
 *                    (1) - r=999&a=view&t=tpl&id=123
 *                    (2) - u=K7I1NzdItE1NySxRK7EtKchRy0yxTYnPSy0vBgA%3D
 * @see url_decrypt();, tinyCryp();
 */
function str2ord($in)
{
    $str = '';
    for ($i = 0; $i < strlen($in); $i++)
    {
        $str .= sprintf("%03d", ord(substr($in, $i, 1)));
    }
    return $str;
}
function ord2str($in)
{
    $str = '';
    for ($i = 0; $i < strlen($in); $i += 3)
    {
        $str .= chr( substr($in, $i, 3));
    }
    return $str;
}
function str2hex($in)
{
    $str = '';
    for ($i = 0; $i < strlen($in); $i++)
    {
        $str .= dechex(sprintf("%03d", ord("2") + ord(substr($in, $i, 1))));
    }
    return $str;
}
function hex2str($in)
{
    $str = '';
    for ($i = 0; $i < strlen($in); $i+=2)
    {
        $str .= ord2str(hexdec(substr($in, $i, 2)) - ord("2") );
    }
    return $str;
}
/**
 *
 */
function tinyCrypt($i, $s = 'pIzb27bS')
{
    $p = 'z';
    //
    for ($x=0; $x < strlen($i); $x++)
    {
        $i[$x] = chr(ord($i[$x]) ^ ord($s[$x % strlen($s)]) ^ ord($p[$x % strlen($p)]));
    }
    $i = str2hex($i);
    return $i;
}
function tinyDecrypt($k, $s = 'pIzb27bS')
{
    //
    $k = hex2str($k);
    $p = 'z';
    for ($x=0; $x < strlen($k); $x++)
    {
        $k[$x] = chr(ord($k[$x]) ^ ord($s[$x % strlen($s)]) ^ ord($p[$x % strlen($p)]));
    }
    return $k;
}
/**
 *
 */
function url_encrypt($mode, $in)
{
	global $sys;
    if ($mode == '1') // tinycrypt
    {
        $rnd = mt_rand(1, $sys['leech_factor']);
        $in = 'r=' . $rnd . '&' . $in;
        $in = str_replace('a=print&', '', $in);
        $in = str_replace('a=term&', '', $in);
        $in = 'a=term&t=' . tinyCrypt($in, $sys['token']);
    }
    elseif ($mode == '2') // mcrypt
    {
        // todo
    }
    return $in;
} // end of url_encrypt()
/**
 * HTML-library v1.2 by Dmitry Shilnikov (c) 2002
 *
 * Decrypts url.
 *
 * @param   int     $mode   Encryption modes.
 * @param   string  $in     Input url parameters.
 * @return  string  Decrypted string.
 * @see url_encrypt();
 */
function url_decrypt($mode, $in)
{
	global $sys;
    if ($mode == '0') // no
    {
        $in = '&t=' . $in;
    }
    elseif ($mode == '1') // tinycrypt
    {
        $in = str_replace('a=print&t=', '', $in);    	
        $in = str_replace('a=term&t=', '', $in);
        $in = tinyDecrypt($in, $sys['token']);
    }
    elseif ($mode == '2') // mcrypt
    {
        $in = '&t=' . $in;
    }
    return $in;
} // end of url_decrypt()
?>