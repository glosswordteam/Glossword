<?php 
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2003-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* --------------------------------------------------------
 * Simple timer class
 * - 23 july 2003: Allow multiple instances
 * - 02 feb  2004: Function for a visual report, endp()
 * ----------------------------------------------------- */
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.timer.php 470 2008-05-14 16:25:33Z yrtimd $ -->');
}
/* -------------------------------------------------------- */
if (!defined('IS_CLASS_TIMER'))
{
	define('IS_CLASS_TIMER', 1);

class gw_timer {
	var $prefix = '';
	/* */
	function gw_timer($prefix = '')
	{
		$this->prefix = $prefix;
		$var = $this->prefix.'_starttime';
		global $$var;
		$mtime = explode(' ', microtime());
		$$var = (float)$mtime[1] + (float)$mtime[0];
	}
	function end()
	{
		$var = $this->prefix.'_starttime';
		global $$var;
		$mtime = explode(' ', microtime());
		$endtime = (float)$mtime[1] + (float)$mtime[0];
		return ($endtime - $$var);
	}
	function endp($str_line = '', $str_file = '')
	{
		return sprintf("<div style=\"margin:0;color:#000;background:#FFF\">%1.6f <tt>%s <= %s</tt></div>", $this->end(), $str_line, $str_file );
	}
}

}

?>