<?php
	
/**
	Requires:
	- constant CRLF
*/
if (!defined('IS_CLASS_LOGWRITE'))
{
	define('IS_CLASS_LOGWRITE', 1);

	$tmp['mtime'] = explode(' ', microtime());
	$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
	
class gw_logwriter
{
	var $remote_ip = 0;
	var $remote_ua = '';
	var $remote_ref = '';
	var $current_date;
	var $current_time;
	var $path_logdir;
	var $file_ex = '.log';
	var $str_delim = ' ';
	var $str_endline = "\n";
	/* */
	function gw_logwriter($path_logdir = 'logs')
	{
		$this->path_logdir = $path_logdir;
	}
	/* */
	function get_filename($dirname = 'default')
	{
		return $this->path_logdir.'/'.$dirname.'/'.$dirname.'_'.date("Y-m-d").$this->file_ex;
	}
	/* */
	function get_str($str = '')
	{
		$this->current_date = date("Ymd");
		$this->current_time = date("His");
		$this->str_endline = CRLF;
		/* Additional fixes */
		$arReplace = array();
		$arReplace = array('%2F' => '/', '%3A' => ':', '%3F' => '?', '%3D' => '=', '%26' => '&');
		/* Prepare log columns */
		$arLog = array(
			$this->current_date.$this->current_time,
			$this->remote_ip,
			$str,
			$this->remote_ua
		);
		for (reset($arLog); list($k, $v) = each($arLog);)
		{
			/* Completely decodes url parameters */
			$arLog[$k] = urlencode(urldecode($arLog[$k]));
			$arLog[$k] = str_replace(array_keys($arReplace), array_values($arReplace), $arLog[$k]);
		}
		return implode($this->str_delim, $arLog) . $this->str_endline;
	}
	/* */
	function make_str($arLog)
	{
		ksort($arLog);
		$arReplace = array('%2F' => '/', '%3A' => ':', '%3F' => '?', '%3D' => '=', '%26' => '&');
		for (reset($arLog); list($k, $v) = each($arLog);)
		{
			/* Completely decodes url parameters */
			$arLog[$k] = urlencode(urldecode($arLog[$k]));
			$arLog[$k] = str_replace(array_keys($arReplace), array_values($arReplace), $arLog[$k]);
		}
		return implode($this->str_delim, $arLog) . $this->str_endline;
	}
}
$tmp['mtime'] = explode(' ', microtime());
$tmp['endtime'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
$tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
}
/* end of file */
?>