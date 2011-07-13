<?php
/**
 * Collects HTTP-headers, outputs collected headers.
 * $Id: class.headers.php 12 2009-05-23 03:51:36Z dshilnikov $
 */
if (!defined('IS_CLASS_TKIT_HEADERS')) { define('IS_CLASS_TKIT_HEADERS', 1);
class tkit_headers
{
	public $is_debug = 0;
	public $arH = array();
	public $arHText = array();

	public function add($str)
	{
		if ($str != '')
		{
			$this->arH[] = $str;
		}
	}
	public function output()
	{
		for (reset($this->arH); list($k, $v) = each($this->arH);)
		{
			@header($v);
		}
	}
	public function get()
	{
		return $this->arH;
	}
	/* */
	public function redirect($url, $is_debug = 0, $fromfile = '', $fromline = '')
	{
		/* fixes &amp; */
		$url = str_replace('&amp;', '&', $url);
		$filename = $linenum = 0;
		if ($is_debug || headers_sent($filename, $linenum))
		{
			if ($filename)
			{
				print 'Headers already sent in '.$filename.' on line '. $linenum.'<br />';
			}
			print 'Location:<br />' . sprintf('<a href="%s">%s</a><br />%s <b>%s</b>', $url, $url, $fromfile, $fromline);
			exit;
		}
		if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
		{
			exit( header('Refresh: 0; URL=' . $url) );
		}
		/* redirect */
		if (!preg_match("/cgi/", php_sapi_name()))
		{
			@header('Status: 301 Moved Permanently');
		}
		header('Location: ' . $url);
		exit;
	}
} /* end of class */
/* Autostart */
$oHdr = new tkit_headers;
}
?>