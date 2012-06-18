<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: a.export.js.php 84 2007-06-19 13:01:21Z yrtimd $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/dev/) 
 *  © 2002-2006 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
// --------------------------------------------------------
/**
 *  Javascript functions for Export.
 *
 *  @version $Id: a.export.js.php 84 2007-06-19 13:01:21Z yrtimd $
 */
$yf = @date("Y");
$mf = @date("m");
$df = @date("d");
$dateFromD = @date("YmdH:i:s", @mktime(0,0,0, $mf, ($df - 1), $yf));
$dateTillD = @date("YmdH:i:s", @mktime(23,59,59, $mf, ($df - 1), $yf));
$dateFromM = @date("YmdH:i:s", @mktime(0,0,0, $mf, 1, $yf));
$dateTillM = @date("YmdH:i:s", @mktime(23,59,59, ($mf + 1), 0, $yf));
$strForm .= '<script type="text/javascript">/*<![CDATA[*/';
$strForm .= '

var fy = document.forms[\'vbform\'].elements[\'arPost[date_minY]\'];
var fm = document.forms[\'vbform\'].elements[\'arPost[date_minM]\'];
var fd = document.forms[\'vbform\'].elements[\'arPost[date_minD]\'];
var fs = document.forms[\'vbform\'].elements[\'arPost[date_minS]\'];
var ty = document.forms[\'vbform\'].elements[\'arPost[date_maxY]\'];
var tm = document.forms[\'vbform\'].elements[\'arPost[date_maxM]\'];
var td = document.forms[\'vbform\'].elements[\'arPost[date_maxD]\'];
var ts = document.forms[\'vbform\'].elements[\'arPost[date_maxS]\'];

function setAny()
{
    if (!fy.disabled)
    {
        fy.value = "1970";
        fm.value = "01";
        fd.value = "01";
        fs.value = "00:00:00";
        ty.value = "2037";
        tm.value = "12";
        td.value = "31";
        ts.value = "23:59:59";
    }
}
function setAll()
{
    if (!fy.disabled)
    {
        fy.value = "' . @date("Y", $vars['min']) . '";
        fm.value = "' . @date("m", $vars['min']) . '";
        fd.value = "' . @date("d", $vars['min']) . '";
        fs.value = "' . @date("H", $vars['min']) . ":" . @date("i", $vars['min']) . ":" . @date("s", $vars['min']) . '";
        ty.value = "' . @date("Y", $vars['max']) . '";
        tm.value = "' . @date("m", $vars['max']) . '";
        td.value = "' . @date("d", $vars['max']) . '";
        ts.value = "' . @date("H", $vars['max']) . ":" . @date("i", $vars['max']) . ":" . @date("s", $vars['max']) . '";
    }
}
function setToday()
{
    if (!fy.disabled)
    {
        fs.value = "00:00:00";
        ts.value = "23:59:59";
        td.value = fd.value = "' . @date("d") . '";
        tm.value = fm.value = "' . @date("m") . '";
        ty.value = fy.value = "' . @date("Y") . '";
    }
}
function setD()
{
    if (!fy.disabled)
    {
        fy.value = "' . substr($dateFromD, 0, 4) . '";
        fm.value = "' . substr($dateFromD, 4, 2) . '";
        fd.value = "' . substr($dateFromD, 6, 2) . '";
        fs.value = "' . substr($dateFromD, 8, 8) . '";
        ty.value = "' . substr($dateTillD, 0, 4) . '";
        tm.value = "' . substr($dateTillD, 4, 2) . '";
        td.value = "' . substr($dateTillD, 6, 2) . '";
        ts.value = "' . substr($dateTillD, 8, 8) . '";
    }
}
function setM()
{
    if (!fy.disabled)
    {
        fy.value = "' . substr($dateFromM, 0, 4) . '";
        fm.value = "' . substr($dateFromM, 4, 2) . '";
        fd.value = "' . substr($dateFromM, 6, 2) . '";
        fs.value = "' . substr($dateFromM, 8, 8) . '";
        ty.value = "' . substr($dateTillM, 0, 4) . '";
        tm.value = "' . substr($dateTillM, 4, 2) . '";
        td.value = "' . substr($dateTillM, 6, 2) . '";
        ts.value = "' . substr($dateTillM, 8, 8) . '";
    }
}
';
$strForm .= ' /*]]>*/</script>';
?>