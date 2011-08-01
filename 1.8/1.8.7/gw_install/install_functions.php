<?php
/**
 * Glossword installation functions
 * © 2006 Dmitry N. Shilnikov <dev at glossword dot info>
 * $Id: install_functions.php,v 1.14 2006/10/05 12:28:58 yrtimd Exp $
 */
if (!isset($sys['server_proto']))
{
	$sys['server_proto'] = 'http://';
}
/* HTTP_HOST and GW_REQUEST_URI constants */
if (!isset($sys['server_host']))
{
	$sys['server_host'] = isset($_SERVER["HTTP_HOST"])&&!empty($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"]
					: (isset($HTTP_SERVER_VARS["HTTP_HOST"]) ? $HTTP_SERVER_VARS["HTTP_HOST"]
					: (getenv('SERVER_NAME') != '') ? getenv('SERVER_NAME')
					: 'localhost');
}
define('HTTP_HOST',  $sys['server_host']);
define('GW_REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']
					: ((getenv('REQUEST_URI') != '') ? getenv('REQUEST_URI')
					: ((isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') ? ($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'])
					: $_ENV['PHP_SELF'])));
if (!isset($sys['server_dir']))
{
	$sys['server_dir'] = explode('/', dirname(GW_REQUEST_URI));
	unset($sys['server_dir'][(sizeof($sys['server_dir'])-1)]);
	$sys['server_dir'] = implode('/', $sys['server_dir']);
}

define('CRLF', "\r\n");
define('LF', "\\n\\");
$tmp['arPhpVer'] = explode('.', PHP_VERSION);
define('PHP_VERSION_INT', intval(sprintf('%d%02d%02d', $tmp['arPhpVer'][0], $tmp['arPhpVer'][1], $tmp['arPhpVer'][2])));
$tmp['arMySQLVer'] = array(0, 0, 0);
if (function_exists('mysql_get_client_info')) {
$tmp['arMySQLVer'] = explode('.', mysql_get_client_info());
}
define('MYSQL_VERSION_INT', intval(sprintf('%d%02d%02d', $tmp['arMySQLVer'][0], $tmp['arMySQLVer'][1], $tmp['arMySQLVer'][2])));
define('REMOTE_UA', trim(substr(getenv('HTTP_USER_AGENT'), 0, 256)));


/* Variables */
$sys['is_prepend'] = 1;
$sys['is_mod_rewrite']  = 0;
$sys['path_css'] = 'supply';

function gw_html_open() {
	global $sys;
print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$sys['html_title'].'</title>
<style>
body { margin: 0; padding: 0; background: #FFF; color: #000 }
tt { color:#808; background:transparent; }
#theme { margin: 0 auto; padding: 0; width: 600px }
.red { color:#C30; background:transparent; }
.green { color:#090; background:transparent; }
.xq { font: 61% verdana,arial,sans-serif }
.xw { font: bold 140% "trebuchet ms",verdana,sans-serif }
.xr { font: bold 91% verdana,arial,sans-serif }
.xt { font: 71% verdana,arial,sans-serif }
.xu { font: 90% verdana,arial,sans-serif }
.debugwindow { width: 100%; font: 75% verdana,arial,sans-serif }
.contents { padding: 0.5em 1em; text-align: '.$sys['css_align_left'].' }
.center { margin: 0 auto; text-align: center }
.hr3 { height: 2px; overflow: hidden; background: #EDF2FD }
ol.code { width: 96%; list-style:none; overflow: auto; border: #E0E0E0 2px solid; margin: 0 0 0.4em 0; padding: 0.7em; font: 85% "courier new",courier,monospace; color:#444;background:#F9F9F5;}
.codetitle{ width: 75%; margin: 0.4em 0 0 0; padding: 0.2em; font: bold 80% verdana,sans-serif; color:#000; background:#E3E3E0}
</style>
</head>
<body><div id="theme">
	';
}
function gw_html_close() {
	print '<br /></div></body></html>';
} 
  
function gw_next_step($is_allow, $url)
{
	global $oL;
	return $is_allow
		 ? sprintf('<a href="%s"><strong>%s</a></strong>', THIS_SCRIPT.'?'.$url, $oL->m('1183')).' <span id="countdown"></span>'
		: $oL->m('1184');
}
/* */
function get_html_item_progress($text, $status = 1)
{
	switch($status)
	{
		case 1: $str_class = 'green'; break;
		case 2: $str_class = 'yellow'; break;
		case 3: $str_class = 'red'; break;
		default: $str_class = 'black'; break;
	}
	return sprintf('<div style="margin-left:1em" class="%s">&bull; %s</div>', $str_class, $text);
}


#<script type="text/javascript">/*<![CDATA[*/
#function clipboard() {
#	window.clipboardData.setData('text', document.w.sysinfo.value);
#}


/* */
function html_array_to_table($ar, $is_print = 1)
{
	if (empty($ar)) { $ar = array(); }
	if (is_string($ar)) { $ar = array($ar); }
	$str = '<table cellpadding="2" cellspacing="1" width="75%"><tbody>';
	for (reset($ar); list($k, $v) = each($ar);)
	{
		if (is_string($v) && ($v == '')) { $v = '-'; }
		$str .= '<tr>';
		$str .= '<td style="width:10%"><tt>'. $k .'</tt></td>';
		$str .= '<td>'. $v .'</td>';
		$str .= '</tr>';
	}
	$str .= '</tbody></table>';
	if ($is_print)
	{
		print $str;
	}
	else
	{
		return $str;
	}
}
/* */
function html_array_to_table_multi($ar, $is_print = 1)
{
	if (empty($ar)) { $ar = array(); }
	if (is_string($ar)) { $ar = array(array($ar)); }
	$str = '<table cellpadding="2" cellspacing="1" width="95%" border="0"><tbody>';
	for (reset($ar); list($k, $arV) = each($ar);)
	{
		if (is_string($arV)) { $arV = array($arV); }
		$td_width = empty($arV) ? 1: ceil(100 / sizeof($arV));
		$td_style = '';
		if ($k == 0)
		{
			$td_style = ' style="width:'.$td_width.'%"';
		}
		$str .= '<tr>';
		for (reset($arV); list($k2, $v2) = each($arV);)
		{
			$str .= '<td'. $td_style .'>'.  $v2 .'</td>';
		}
		$str .= '</tr>';
	}
	$str .= '</tbody></table>';
	if ($is_print)
	{
		print $str;
	}
	else
	{
		return $str;
	}
}
/* end of file */
?>