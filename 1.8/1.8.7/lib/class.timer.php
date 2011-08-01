<?php 
/**
 * © 2003-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 * http://glossword.info/dev/
 */
/* --------------------------------------------------------
 * Simple timer class
 * - 23 july 2003: Allow multiple instances
 * - 02 feb  2004: Function for a visual report, endp()
 * ----------------------------------------------------- */
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.timer.php,v 1.6 2006/10/06 12:11:57 yrtimd Exp $ -->');
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