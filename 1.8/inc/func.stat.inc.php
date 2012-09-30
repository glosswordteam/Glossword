<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id$ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  � 2002-2007 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 *  Logging data, file functions, replacement functions.
 */

function gw_set_cookie($n, $value = '', $is_always = 1)
{
	global $sys;
	$expires = time() + (60*60*24*7);
	if ($is_always)
	{
		$expires = time() + (60*60*24*365);
	}
	@setcookie($n.$sys['token'], urlencode($value), $expires, '/', '' );
}


/**
 * Reads directory
 *
 * @param   string  $strDir path to directory
 * @param   string  $ex     preg_match pattern
 *
 * @return  array   The list of directories
 */
function file_readDirD($strDir, $ex = "(.*)"){
	$ar = array();
	if (is_dir($strDir))
	{
		$h_dir = opendir($strDir);
		while (($f = readdir($h_dir)) !== false)
		{
			if ($f != '.' && $f != '..' && is_dir($strDir . '/' . $f))
			{
				if ( preg_match($ex, $f ) )
				{
					$ar[$f] = $f;
				}
			}
		}
		closedir($h_dir);
	}
	return $ar;
}
/**
 * Reads files
 *
 * @param   string  $strDir path to directory with files
 * @param   string  $ex     preg_match pattern
 *
 * @return  array   The list of directories
 */
function file_readDirF($strDir, $ex = "(.*)")
{
	$ar = array();
	if (is_dir($strDir))
	{
		$h_dir = opendir($strDir);
		while (($f = readdir($h_dir)) !== false)
		{
			if ($f != '.' && $f != '..' && is_file($strDir . '/' . $f))
			{
				if ( preg_match($ex, $f) )
				{
					$ar[] = $f;
				}
			}
		}
		closedir($h_dir);
	}
	return $ar;
}


/* Glossword 1.9: Mail messages */
class tkit_mail
{
	var $oTpl;
	var $oFunc;
	var $oL;
	var $sys;
	var $tpl_name;
	var $h_mailer = 'Glossword';
	/* Autoexec */
	function tkit_mail($tpl_name)
	{
		global $oFunc, $oL, $sys;
		$this->oFunc =& $oFunc;
		$this->oL =& $oL;
		$this->sys =& $sys;
		$this->tpl_name = $tpl_name;
		$this->oTpl = new $sys['class_tpl'];
		$this->oTpl->init('gw_admin');
	}
	/* Compose message */
	function create_message($subject, $body)
	{
		$body = $this->oFunc->mb_wordwrap($body, 70, CRLF);
		/* Set internal Template class */
		$this->oTpl->set_tpl( $this->tpl_name );
		/* */
		$this->oTpl->addVal( 'v:body', $body );
		$this->oTpl->addVal( 'v:subject', $subject );
		$this->oTpl->addVal( 'v:footer', sprintf($this->oL->m(1350), '<a onclick="window.open(this);return false" href="'.$this->sys['server_url'].'/">'.strip_tags($this->sys['site_name']).'</a>') );
		$this->oTpl->parse();
		return $this->oTpl->output();
	}
	/**
	 * Sends mail
	 *
	 * @param   string  $from_name Sender
	 * @param   string  $to_name Recepient
	 * @param   string  $subj   Subject.
	 * @param   string  $body   Message body.
	 * @param   int     $is_debug if true, do not send message and display it on screen.
	 *
	 * @return  boolean True if success.
	 */
	function send($from_name, $from_email, $to_name, $to_email, $subject, $body, $is_debug = 0)
	{
		$from = '=?utf-8?B?'.base64_encode($from_name). '?= <'.$from_email.'>';
		$to = '=?utf-8?B?'.base64_encode($to_name). '?= <'.$to_email.'>';
		$subject = '=?utf-8?B?'.base64_encode($subject). '?=';
		$ar_h = array();
		$ar_h[] = 'From: '.$from;
		$ar_h[] = 'Return-Path: ' .$from. ' ';
		$ar_h[] = 'X-Mailer: ' .$this->h_mailer;
		$ar_h[] = 'X-Priority: 3';
		$ar_h[] = 'MIME-Version: 1.0';
		$ar_h[] = 'Content-type: text/html; charset=utf-8';
		$str_h = implode("\n", $ar_h);
		if ($is_debug)
		{
			$str_h = str_replace(array('<','>'), array('&lt;','&gt;'), $str_h);
			echo CRLF,'<div style="text-align:left;background:#FFF;font-size:0.9em; margin:0"><pre>',
				$str_h, 
				CRLF, 'To: ', htmlspecialchars($to), 
				CRLF, 'Subject: ', htmlspecialchars($subject), 
				CRLF, '</pre>', $body, '</div>';
			return $is_debug;
		}
		/* Log mail messages */
		if ($this->sys['is_log_mail'])
		{
			if (!isset($oLog))
			{
				$oLog = new gw_logwriter($this->sys['path_logs']);
			}
			$arLogMail = array(
				$this->sys['time_now_gmt_unix'],
				$this->oFunc->ip2int(REMOTE_IP),
				$body
			);
			$this->oFunc->file_put_contents($oLog->get_filename('mail'), $oLog->make_str($arLogMail), 'a');
		}
		return @mail($to_email, $subject, $body, $str_h);
	}
}


?>