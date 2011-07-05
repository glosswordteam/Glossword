<?php
/**
 * The timer class.
 * $Id: class.timer.php 15 2009-05-23 10:35:06Z dshilnikov $
 */
if (!defined('IS_CLASS_TIMER')) { define('IS_CLASS_TIMER', 1);
class tkit_timer
{
	private $prefix = '';
	/* */
	public function __construct($prefix = '')
	{
		$this->prefix = $prefix;
		$v = $this->prefix.'_starttime';
		global $$v;
		$mtime = explode(' ', microtime());
		$$v = (float)$mtime[1] + (float)$mtime[0];
	}
	/* */
	public function end()
	{
		$v = $this->prefix.'_starttime';
		global $$v;
		$mtime = explode(' ', microtime());
		$endtime = (float)$mtime[1] + (float)$mtime[0];
		return sprintf("%1.6f", ($endtime - $$v));
	}
	/* */
	public function endp($str_line = '', $str_file = '')
	{
		return sprintf("<div style=\"margin:0;color:#000;background:#FFF;text-align:left\">%1.6f <tt>%s <= %s</tt></div>", $this->end(), $str_line, $str_file );
	}
}}
?>