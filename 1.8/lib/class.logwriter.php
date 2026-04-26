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
	public $remote_ip = 0;
	public $remote_ua = '';
	public $remote_ref = '';
	public $current_date;
	public $current_time;
	public $path_logdir;
	public $file_ex = '.log';
	public $str_delim = ' ';
	public $str_endline = "\n";
	/* */
	public function __construct($path_logdir = 'logs')
	{
		$this->path_logdir = $path_logdir;
	}
	/* */
	public function get_filename($dirname = 'default')
	{
		return $this->path_logdir.'/'.$dirname.'/'.$dirname.'_'.date("Y-m-d").$this->file_ex;
	}
	/* */
	public function get_str($str = '')
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
		foreach ($arLog as $k => $v)
		{
			/* Completely decodes url parameters */
			$arLog[$k] = urlencode(urldecode($arLog[$k]));
			$arLog[$k] = str_replace(array_keys($arReplace), array_values($arReplace), $arLog[$k]);
		}
		return implode($this->str_delim, $arLog) . $this->str_endline;
	}
	/* */
	public function make_str($arLog)
	{
		ksort($arLog);
		$arReplace = array('%2F' => '/', '%3A' => ':', '%3F' => '?', '%3D' => '=', '%26' => '&');
		foreach ($arLog as $k => $v)
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

