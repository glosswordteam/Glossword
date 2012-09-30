<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: func.text.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/**
 *  Functions for an HTML-code and text operations.
 */

/**
 * Constructs HTML for Virtual keyboard
 * 
 * @required $oDb, $oSqlQ, $oL, $ar_theme
 * @param int $id_profile Virtual keyboard Profile
 * @param int $id_dict Dictionary ID.
 */		
function gw_get_virtual_keyboard( $id_profile = false, $id_dict = false ) {
	global $oDb, $oSqlQ, $oL, $ar_theme;

	$str = '';
	$ar_letters = array();

	if ( $id_dict && $id_profile ) {
		/* Per dictionary */
		$arSql = $oDb->sqlRun( $oSqlQ->getQ( 'get-vkbd-profile', $id_profile ), $id_dict );
	} else {
		/* Default Virtual keyboard */
		$arSql = $oDb->sqlRun( $oSqlQ->getQ( 'get-vkbd-default' ), 'st' );
	}

	foreach ( $arSql as $arV ) {
		$ar_letters = explode( ',', $arV['vkbd_letters'] );
	}
	
	if ( !empty( $ar_letters ) ) {
		array_walk( $ar_letters, create_function( '&$v', '$v = trim( addslashes( $v ) );' ) );
		
		/* "Virtual keyboard" button */
		$str .= '<a title="' . $oL->m( 'virtual_keyboard' ) . '" id="gwkbdcall" onclick="';
		$str .= 'return gwJS.showKbd(\'gw\', ['; // <form id="gw"><input id="gwq">
		$str .= "'" . implode( '\',\'', $ar_letters ) . "'";
		$str .= ']);"';
		$str .= ' class="plain">' . $ar_theme['txt_virtual_keyboard'] . '</a>';
		$str .= '<table style="position:absolute;top:-10;visibility:hidden" id="gwkbd" cellspacing="0"><tbody><tr><td></td></tr></tbody></table>';
	}
	
	return $str;
}

/* */
function gw_get_note_afterpost($text, $status = 0)
{
	$text = '<span class="'.($status == true ? "green" : "red" ).'">'.$text.'</span>';
	return '<div class="note-afterpost" id="note-afterpost">'.
			'<a href="#" onclick="gw_getElementById(\'note-afterpost\').style.display=\'none\';return false" style="font-size:120%;padding:0 5px;display:block;float:right">×</a>'.
			'<script type="text/javascript">/*<![CDATA[*/gwJS.FXfadeOpac(\'note-afterpost\');/*]]>*/</script>'.
			$text.'</div>';
}
/* */ 
class gw_restore_quotes
{
	/* */
	function init()
	{
		$this->is_strip_tags = 1;
		$this->rule_proto = 'http:\/\/|https:\/\/';
		$this->rule_ahref = '[^][<>"\\x00-\\x20\\x7F]';
		$this->rule_atext = '[^\]\\x0a\\x0d]';
		$this->rule_abracket = '/\[(\b(' . ('http\:\/\/|https\:\/\/|ftp\:\/\/|mailto\:|news\:') . ')'.$this->rule_ahref.'+) *('.$this->rule_atext.'*?)\]/S';
		$this->rule_attr = '[A-Za-z0-9\:\-]';
		$this->rule_spaces = '[\x09\x0a\x0d\x20]';
		/* */
		$this->rule_attr_regex = '/(?:^|'.$this->rule_spaces.')('.$this->rule_attr.'+)'.
		'('.$this->rule_spaces.'*=)(.*?[\'"])(?='.$this->rule_spaces.'|$)/xs';
	}
	/* */
	function parse($s)
	{
		if ($this->is_strip_tags)
		{
			$s = $this->strip_tags($s);
		}
		$s = $this->_proc($s);
		return $s;
	}
	/* */
	function _proc($s)
	{
		$ar = explode('<', $s);
		$s = str_replace('>', '&gt;', array_shift($ar));
		foreach ($ar as $k)
		{
			$regs = array();
			preg_match('/^(\\/?)([\w!\?]+)([^>]*?)(\/{0,1}>)([^<]*)$/', $k, $regs);
			@list(/* $qbar */, $slash, $tag, $attrlist, $brace, $rest) = $regs;
			$newattrlist = $this->_attr_fix($attrlist, $tag);
			$rest = str_replace( '>', '&gt;', $rest );
			/* add space for non-pair XHTML tags */
			if ($brace == '/>')
			{
				$brace = ' ' . $brace;
			}
			$s .= '<'.$slash.$tag.$newattrlist.$brace.$rest;
		}
		/* fix for XHTML single tags */
		$s = str_replace( '} />', '}/>', $s );
		return $s;
	}
	/* */
	function _attr_fix($str_attr, $tag)
	{
		if (trim($tag) == '')
		{
			return '';
		}
		$str_attr = preg_replace( '/'.$this->rule_spaces.'+/', ' ', $str_attr );
		$str_attr = str_replace('&quot;', '"', $str_attr);
		$str_attr = str_replace('&#039;', '\'', $str_attr);
		/* keep untouched < ?xml ... ? >, < !DOCTYPE ... >, and comments */
		if ($tag == '?xml' || $tag == '!DOCTYPE' || $tag == '!')
		{
			return $str_attr;
		}
		/* keep HTML-variables in attributes */
		if (preg_match('/'.$this->rule_spaces.'{([a-zA-Z_\:\-]+)}/', $str_attr))
		{
			return $str_attr;
		}
		$ar_attr = $this->_attr_expand($str_attr);
		$ar_newattr = array();
		foreach ($ar_attr as $attr => $val)
		{
			if ($attr == 'a')
			{
				$ar_newattr[] = $val;
			}
			else
			{
				$ar_newattr[] = $attr . '="' . $val.'"';
			}
		}
		return sizeof($ar_newattr) ? ' ' . implode(' ', $ar_newattr ) : '';
	}
	/* */
	function _attr_expand($str_attr)
	{
		$ar_pairs = $ar = array();
		if (trim($str_attr) == '')
		{
			return $ar;
		}
		/* */
		if (!preg_match_all($this->rule_attr_regex, $str_attr, $ar_pairs, PREG_SET_ORDER))
		{
			return $ar;
		}
		foreach ($ar_pairs as $v )
		{
			$attr = strtolower( $v[1] );
			$val = trim($v[3]);
			$val = preg_replace('/(^["\'])/', "", $val);
			$val = preg_replace('/(["\']$)/', "", $val);
			$onclick = '';
			/* Fix target="_blank" */
			if ($attr == 'target')
			{
				$val = 'window.open(this);return false;';
				$attr = 'onclick';
			}
			$val = str_replace('"', '&quot;', $val);
			/* fix repeated attributes */
			if (isset($ar[$attr]))
			{
				$ar[$attr] .= $val;
			}
			else
			{
				$ar[$attr] = $val;
			}
		}
		ksort($ar);
		return $ar;
	}
	/* */
	function strip_tags($s)
	{
		return strip_tags($s, '<nowiki><stress><xref><abbr><acronym><address><blockquote><br><cite><code><dfn><div><em><h1><h2><h3><h4><h5><h6><kbd><p><pre><q><samp><span><strong><var><a><dl><dt><dd><ol><ul><li><object><param><b><big><hr><i><small><sub><sup><tt><del><ins><bdo><button><fieldset><form><input><label><legend><select><optgroup><option><textarea><caption><col><colgroup><table><tbody><td><tfoot><th><thead><tr><img><area><map><noscript><script><style>');
	}
}
$gw_oW = new gw_restore_quotes;
$gw_oW->init();
/* */
function gw_fix_input_to_db($s)
{
	global $gw_oW;
	$s = ($s == ' ') ? '&#032;' : $s;
	/* convert {%v:path_img%} => {v:path_img} */
	$s = str_replace(array('{%', '%}'), array('{', '}'), $s);
	/* the contents of <script> should not be touched */
	preg_match_all('/<script(.*?)>(.*?)<\/script>/s', $s, $scripts);
	foreach ($scripts[2] as $v)
	{
		$s = str_replace($v, base64_encode($v), $s);
	}
	/* the contents of <nowiki> should not be touched */
	preg_match_all('/<nowiki>(.*?)<\/nowiki>/s', $s, $nowiki);
	foreach ($nowiki[1] as $v)
	{
		$s = str_replace($v, base64_encode($v), $s);
	}
	/* replace old tags (HTML 4.01) with new (XHTML 1.1) */
	$s = gw_fix_tagnames(trim($s));
	/* convert &amp;&"' => &amp;&amp;&quot;&#039; */
	$s = gw_htmlspecialamp(gw_unhtmlspecialamp($s));
	/* attribute fixes, convert title=&quot;123&quot;&quot; => title="123&quot;" */
	$s = $gw_oW->parse($s);
	/* restore the contents of <script> */
	preg_match_all('/<script(.*?)>(.*?)<\/script>/s', $s, $scripts);
	foreach ($scripts[2] as $v)
	{
		$s = str_replace($v, base64_decode($v), $s);
	}
	/* restore the contents of <nowiki> */
	preg_match_all('/<nowiki>(.*?)<\/nowiki>/s', $s, $nowiki);
	foreach ($nowiki[1] as $v)
	{
		$s = str_replace($v, base64_decode($v), $s);
	}
	return $s;
}
/* */
function gw_fix_db_to_field($s)
{
	if (is_string($s))
	{
		$s = preg_replace('/{(d [0-9a-zA-Z_\:\-]+|[0-9a-zA-Z_\:\-\/]+)}/', '{%\\1%}', $s);
		$s = gw_unhtmlspecialamp($s);
		$s = str_replace('&', '&amp;', $s);
		$s = str_replace('"', '&quot;', $s);
		$s = str_replace('<', '&lt;', $s);
		$s = str_replace('>', '&gt;', $s);
	}
	return $s;
}
/* */
function gethtml_metarefresh($url, $time_refresh = 5)
{
	return '<meta http-equiv="Refresh" content="' . $time_refresh . ';url=' . $url . '" />';
}
/* */
function gw_bbcode_htmlspecialchars($t)
{
	global $sys;
	$t = stripslashes($t);
	$t = htmlspecialchars($t, ENT_QUOTES, $sys['internal_encoding']);
	return $t;
}
/* */
function gw_bbcode_html($t)
{
	$regexfind[] = '/\&lt;(.+)\&gt;/esiU';
	$t = str_replace('&amp;', '&', $t);
	$t = str_replace('&quot;', '"', $t);
	$t = str_replace('&039;', '\'', $t);
	$regexreplace[] = "gw_bbcode_html_tag(gw_bbcode_htmlspecialchars('\\1'))";
	$t = preg_replace($regexfind, $regexreplace, $t);
#	$t = str_replace('&#', '&amp;#', $t);
	$t = str_replace('![cdata', '![CDATA', $t);
	$t = str_replace('!doctype', '!DOCTYPE', $t);
	$t = str_replace("\t", '&#160;&#160;&#160;&#160;&#160;&#160;&#160;', $t);
	$t = str_replace("\n", '<br />', $t);
	$t = str_replace('  ', '&#160;&#160;', $t);
	return $t;
}
function gw_bbcode_html_tag($t)
{
	$slash_s = $slash_e = '';
	$spacepos = strpos($t, ' ');
	$attr = '';
	$l = strlen($t);
	if ($t{0} == '/')
	{
		$slash_s = '/';
		$t = substr($t, 1);
	}
	if (substr($t, -1) == '/')
	{
		$slash_e = '/';
		$t = substr($t, 0, $l-1);
	}
	if ($spacepos != false)
	{
		$attr = substr($t, $spacepos);
		$t = substr($t, 0, $spacepos);
		$attr = preg_replace('# ([a-z-:]+)=&quot;(.*)&quot;#siU', ' <span style="color:#C00">\1</span>=<span style="color:#C6C">&quot;\2&quot;</span>', $attr);
	}
	$t = strtolower($t);
	switch($t)
	{
		case 'form';
		case 'input':
		case 'select':
		case 'option':
		case 'textarea':
		case 'label':
		case 'fieldset':
		case 'legend':
		case 'meta':
		case 'head':
		case 'link':
		case 'body':
		case 'html':
		case 'img':
		case 'script':
			$c = '#880';
		break;
		default:
			$c = '#808';
		break;
	}
	$t = '<span style="color:'.$c.'">'.$t.'</span>';
	$t = '<span style="color:#00F">&lt;'.$slash_s.'</span>'.$t.$attr.'<span style="color:#00F">'.$slash_e.'&gt;</span>';
	return $t;
}
/** 
 * Draws progressbar in HTML+CSS 
 *
 * @param	int	$percent
 * @param	text	$color_txt Progress bar text color
 * @param	text	$color_bg Progress bar Background color
 * @return	text	HTML-code
 */
function text_progressbar($percent = 100, $color_txt = '#000', $color_bg = '#6C3')
{
	return '<div style="text-align:center;background:#F6F6F6;margin:5px 0;width:100%;border:1px solid #CCC"><div style="font:90% sans-serif;color:'.$color_txt.';background:'.$color_bg.';width:'.$percent.'%">'.$percent.'%</div></div>';
}


/**
 * Converts string into decimal equivalent.
 * 
 * @param	string	$s 
 * @return	integer	A decimal numbers.
 */
function text_str2ord($s)
{
	$int_len = strlen($s);
	$t = '';
	for ($i = 0; $i < $int_len; $i++)
	{
		$t .= ord(substr($s, $i, 1));
	}
	return $t;
}
/**
 * Converts string into hexademical.
 * 
 * @param	string	$s
 * @return	integer	A decimal number.
 */
function text_hex2bin($s)
{
	/* 0.00012 */
	return pack("H".strlen($s), $s);
	/* 0.02489
	$bindata = '';
	for ($i = 0; $i < strlen($t); $i += 2)
	{
		$bindata .= chr(hexdec(substr($t, $i, 2)));
	}
	return $bindata;
	*/
}

/* */
function gw_text_parse_href($t)
{
	/* */
	$t = str_replace('<![CDATA[', '', str_replace(']]>', '', $t));
	/* */
	$t = strip_tags($t);
	return $t;
}
/* */
function gw_text_parse_preview($t)
{
	/* */
#	$t = preg_replace("/(\r\n|\r|\n)/", ' ', $t);
	/* */
	$t = str_replace('><', '> <', $t);
	/* */
	$t = str_replace('<![CDATA[', '', str_replace(']]>', '', $t));
	/* */
	$t = strip_tags($t);
	/* removes `{TEMPLATES}' and `{TEMPLATES}:' */
	$t = preg_replace("/\{([A-Za-z0-9:\-_]+)\}([:])*/", ' ', $t);
	/* remove &#nnn(nn) */
#	$t = preg_replace('/&#[x0-9a-f]+;/', ' ', $t);
#	$t = preg_replace('/&[a-z]+;/', ' ', $t);
	return $t;
}

/**
 * Table template, used for all headers in HTML-form
 *
 * @param    string  $title      text for header, 1st column
 * @param    string  $funcnav    text for header, 2nd column
 * @return   string  string      HTML-code for table header
 * @see getFormDefn()
 */
function getFormTitleNav($title = 'title', $funcnav = '')
{
	global $sys, $ar_theme;
	$str = '';
	$str .= '<table cellspacing="0" cellpadding="2" border="0" width="100%">';
	$str .= '<tbody><tr class="xmtitle">';
	if ($funcnav != '')
	{
		$str .= '<td style="width:50%;text-align:'.$sys['css_align_left'].'">' . $title . '</td>';
		$str .= '<td style="text-align:'.$sys['css_align_right'].'">' . $funcnav . '</td>';
	}
	else
	{
		$str .= '<td style="text-align:'.$sys['css_align_left'].'">' . $title . '</td>';
	}
	$str .= '</tr>';
	$str .= '</tbody></table>';
	return $str;
}

/* Automatically parse URLs */
function gw_regex_url($url)
{
	$is_skip = 0;
	$url['end'] = '';
	/* Fix punctuation */
	if (preg_match( "/([\.,\?]|&#33;)$/", $url['html'], $match))
	{
		$url['end'] .= $match[1];
		$url['html'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['html'] );
		$url['show'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['show'] );
	}
	/* Fix closing tag */
	if (preg_match( "/\[<\/(html|quote|code|sql)/i", $url['html']) )
	{
		return $url['html'];
	}
	/* Fix ampersands and brackets */
	$url['html'] = str_replace('&amp;', '&'  , $url['html']);
	$url['html'] = str_replace('['    , '%5b', $url['html']);
	$url['html'] = str_replace(']'    , '%5d', $url['html']);
	$url['html'] = str_replace('&',   '&amp;', $url['html']);
	/* No Javascript */
	$url['html'] = preg_replace("/javascript:/i", 'java script&#58; ', $url['html']);
	/* http in front */
	if (!preg_match("/^(http|news|https|ftp|aim|callto|e2dk):\/\//", $url['html'] ))
	{
		$url['html'] = 'http://' . $url['html'];
	}
	/* Fix ampersands */
	$url['show'] = str_replace( '&amp;', '&' , $url['show'] );
	$url['show'] = str_replace( '&', '&amp;' , $url['show'] );
	$url['show'] = preg_replace( "/javascript:/i", "javascript&#58; ", $url['show'] );
	/* Used for title="" also */
	$stripped = preg_replace("/^(http|ftp|https|news|aim|callto|e2dk):\/\/(\S+)$/i", "\\2", $url['show']);
	/* Chunk long URLs */
	if (strlen($stripped) > 40 ) { $is_skip = 1; }
	if (!preg_match("/^(http|ftp|https|news|aim|callto|e2dk):\/\//i", $url['show'])) { $is_skip = 1; }
	$str_show = $url['show'];
	if ($is_skip)
	{
		$uri_type = preg_replace("/^(http|ftp|https|news|aim|callto|e2dk):\/\/(\S+)$/i", "\\1", $url['show']);
		$str_show = $uri_type.'://'.substr($stripped, 0, 25 ).'&#8230;'.substr($stripped, -15);
	}
	return $url['st'] . '<a class="ext" href="'. $url['html'] .'" onclick="window.open(this);return false" title="'.rtrim($stripped, '/').'">'.$str_show.'</a>' . $url['end'];
}

/* Function to highlight words inside a text */
function text_highlight($t, $q, $encoding = 'UTF-8')
{
#@header("content-type: text/html; charset=utf-8");
	$t = strip_tags($t);
	if ($q == '') { return $t; }
	
	$classname = 'highlight';
	
	/* #122 */
	$strong_start = '\x00';
	$strong_end = '\x01';

	$is_center = $is_left = 0;
	$is_left = preg_match("/^\*/", $q);
	$is_right = preg_match("/\*$/", $q);
	$is_both = preg_match("/^\*(.*?)\*$/", $q);
	$is_center = (!$is_left && !$is_right) ? 1 : 0;
	$is_both = (!$is_center && preg_match("/\?/", $q)) ? 1 : $is_both;
	
	$q = str_replace("*", ' ', $q);
	$q = str_replace("?", ' ', $q);
	$ar_words = explode(' ', $q);
	
	for (; list($k, $v) = each($ar_words);)
	{
		if ($v == '') { continue; }
		$v = str_replace("/", ' ', $v);
		$v = htmlspecialchars($v);
		/* */
		if ($is_both)
		{
			$t = preg_replace('#('.preg_quote($v, '/').')#i', $strong_start.'\\1'.$strong_end, $t );
		}
		elseif ($is_center)
		{
			$t = preg_replace('#(^|[\x09-\xff])('.preg_quote($v,'/').')([\x09-\xff]|$)#iu', '\\1'.$strong_start.'\\2'.$strong_end.'\\3', $t );
		}
		elseif ($is_right)
		{
			$t = preg_replace('#(^|[\x09-\xff])('.preg_quote($v, '/').')#iu', '\\1'.$strong_start.'\\2'.$strong_end, $t );
		}
		elseif ($is_left)
		{
			$t = preg_replace('#('.preg_quote($v, '/').')([\x09-\xff]|$)#iu', $strong_start.'\\1'.$strong_end, $t );
		}
		else
		{
			$t = preg_replace('#('.preg_quote($v, '/').')#i', $strong_start.'\\1'.$strong_end, $t );
		}
	}
	/* fix &#xn<span class="highlight">n</span>nn; */
	preg_match_all('/&(#)?([0-9a-z="<>\/ ]+);/u', $t, $ar);
	for (; list($k, $v) = each($ar[0]);)
	{
		$t = str_replace($v, strip_tags($v), $t);
	}
	$t = str_replace( $strong_start, '<strong class="'.$classname.'">', $t );
	$t = str_replace( $strong_end, '</strong>', $t );

#	prn_r( $ar );
#	prn_r( $t, __LINE__ );
	return $t;
}

/* Clear value for a specified key, recursive */
function array_clear_key($ar, $key_value)
{
	if (!is_array($ar)) { return $ar; }
	while (list($k, $v) = each($ar))
	{
		if (is_array($v))
		{
			$ar[$k] = array_clear_key($v, $key_value);
		}
		else if (isset($ar[$key_value]))
		{
			$ar[$k] = '';
		}
		else
		{
			$ar[$k] = $v;
		}
	}
	return $ar;
}
/* */
function gw_text_wildcars($t = '', $mode = 'none')
{
	if ($mode == 'sql')
	{
		if (is_array($t))
		{
			while (list($k, $v) = each($t))
			{
				$t[$k] = str_replace('*', '%', str_replace('?', '_', $v));
			}
		}
		else
		{
			$t = str_replace('*', '%', str_replace('?', '_', $t));
		}
	}
	else
	{
		if (is_array($t))
		{
			while (list($k, $v) = each($t))
			{
				$t[$k] = str_replace('*', '', str_replace('?', '', $v));
			}
		}
		else
		{
			$t = str_replace('*', '', str_replace('?', '', $t));
		}
	}
	return $t;
}


/**
 * Generates random string. Better characters' strength,
 * two groups of illegal symbols excluded.
 *
 * @param    string $first    First character for returned string
 * @param    int    $maxchar  Maximum generated string length
 * @param    int    $set      Set of characters Numbers, Lowercase, Uppercase, added 20 Jun 2007
 * @return   string Generated text
 */
function kMakeUid($first = '', $maxchar = 8, $set = 0)
{
	/*
		Exclude bad symbols.
		1st bad group: 0, 1, l, I, Y, V, y, v
		2nd bad group: a, c, e, o, p, x, A, C, E, H, O, K, M, P, X
	*/
	$str = '';
	$chars[1] = 'bdfghijkmnqrstuwz';
	$chars[2] = 'QWRUSDFGJLZN';
	$chars[3] = '23456789';
	$charN = isset($chars[$set]) ? $chars[$set] : implode('', $chars);
	$len = strlen($charN);
	mt_srand( (double) microtime()*1000000);
	for ($i = 0; $i < $maxchar; $i++)
	{
		$sed = mt_rand(0, $len-1);
		$str .= $charN[$sed];
	}
	$str = $first . substr($str, 0, strlen($str) - strlen($first));
	return $str;
}

/**
 * Merges arrays and clobber any existing key/value pairs
 * kc@hireability.com (12-Oct-2000 11:08) PHP-notes
 * Keeps numeric keys, they will be not renumbered.
 * Additional check-ups added by Dmitry N. Shilnikov, 1st feb 2003
 *
 * @param   array   $a1 First array
 * @param   array   $a2 Second array
 * @return  array   Merged arrays
 */
function array_merge_clobber($a1, $a2)
{
	if (!is_array($a1) || !is_array($a2)) { return false; }
	$arNew = $a1;
	while (list($key, $val) = each($a2))
	{
		if (is_array($val) && isset($arNew[$key]) && is_array($arNew[$key]))
		{
			$arNew[$key] = array_merge_clobber($arNew[$key], $val);
		}
		else
		{
			$arNew[$key] = $val;
		}
	}
	return $arNew;
}


/**
 * Inserts value into array between keys.
 * Note: Works with the first key only if multidimensional array.
 *
 * @param   array   Source array data
 * @param   string  Key
 * @param   string  New value
 * @return  array   Result
 */
function gw_array_insert(&$ar, $k, $v)
{
	if (!is_array($ar)) { return false; }
	//
	// Two ways:
	//
	// 1. array_slice method:
	$ar = array_merge(
			array_slice($ar, 0, ($k+1)),
			array(($k+1) => $v),
			array_slice($ar, ($k+1))
	);
	// 2. Array backup with foreach method
	// # Obsolete
}

/**
 * Converts array into string,
 * splitted with defined character
 *
 * @param   array   $ar Array that needs to be converted
 * @param   string  $a2 Character for delimiter between array elements
 * @return  string  Joined array
 */
function gw_array2str($ar, $delimeter = "\n")
{
	$s = array();
	if (is_array($ar))
	{
		while (list($k, $v) = each($ar))
		{
			$s[] = $v;
		}
	}
	else
	{
		$s = explode($delimeter, $ar);
	}
	// reserved
	// ..
	return implode($delimeter, $s);
}


/**
 * Returns key from array by value
 *
 * @param   array  $ar  array with value and key
 * @param   string $str value
 * @return  string key
 */
function gw_array_value($ar, $str)
{
	if (in_array($str, $ar))
	{
		$ar = array_flip($ar);
		return @$ar[$str];
	}
	return $str;
} //
/**
 * Exclude arrays. Target subtracts from Source.
 *
 * @param   array $arA Source array
 * @param   array $arB Target array
 * @return  array Result
 */
function gw_array_exclude($arA, $arB)
{
	if (empty($arB)){ return $arA; } // 15 Dec 2002
	$arC = array_diff($arA, $arB);
	$arC = array_intersect($arC, $arA);
	return $arC;
}

/**
 *
 */
function unhtmlentities($t)
{
##-----------------------------------------------
## 0.003678
#   $trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES));
#   return strtr($t, $trans_tbl);
##-----------------------------------------------
## 0.002137
	$from = array('&nbsp;', '&amp;', '&quot;', '&lt;', '&gt;', '&deg;', '&copy;', '&eth;', '&thorn;');
	$to =   array(' ',      '&',     '"',      '<',     '>',   '',     '',        '&eth;', '&thorn;');
	return str_replace($from, $to, $t);
}
/**
 *
 */
function gw_fix_tagnames($t)
{
	$from = array('<br>', '<i>', '</i>', '<b>', '</b>', '<strike>', '</strike>', '<u>', '</u>', '<center>', '</center>');
	$to =   array('<br />', '<em>', '</em>', '<strong>', '</strong>', '<span class="strike">', '</span>', '<span class="underline">', '</span>', '<div style="text-align:center">', '</div>');
	return str_replace($from, $to, $t);
}
/**
 * Splits the text into keywords.
 *
 * @param   string  $t String that needs to be splitted, multibyte
 * @param   int     $min Minimum length for one keyword
 * @param   int     $max Maximum length for one keyword
 * @return  array   Array with keywords
 */
function text2keywords($t, $min = 1, $max = 25, $enc = 'UTF-8')
{
	global $oFunc;
	if ($min == 0){ return array(); }
	$ar = array();
	$t = $t . ' ';
	/*
		PHP 4.1.0 Unix, PHP 4.2.3 win32
		0.000876
	*/
	$str_temp = ' ';
#	prn_r( $t );
	preg_match_all("/./u", $t, $ar_letters);
	for (; list($k, $v) = each($ar_letters[0]);)
	{
		$str_temp .= $v;
		if ($v == ' ')
		{
			$word = trim($str_temp);
			$mb_len = $oFunc->mb_strlen($word, $enc);
			if (($mb_len >= $min) && ($mb_len < $max))
			{
				$ar[] = $word;
			}
			$str_temp = '';
		}
	}
	$ar = array_values(array_unique($ar));
	return $ar;
}
/* */
function text2keywords_crc($t, $mn = 1, $mx = 25, $e = 'UTF-8')
{
	global $oFunc;
	if ($mn == 0){ return array(); }
	$ar = array();
	$t = $t . ' ';
	/*
		PHP 4.1.0 Unix, PHP 4.2.3 win32
	*/
	$s = ' ';
	$d = 0;
	preg_match_all("/./u", $t, $a);
	for (; list($k, $v) = each($a[0]);)
	{
		$s .= $v;
		if ($v == ' ')
		{
			$w = trim($s);
			$l = $oFunc->mb_strlen($w, $e);
			if (($l >= $mn) && ($l <= $mx))
			{
				$i = $l;
				if ($l <= 2)
				{
					$i = 2;
				}
				elseif ($l > 12 && $l <= 16)
				{
					$i = 16;
				}
				else if ($l > 16 && $l <= 32)
				{
					$i = 32;
				}
				$ar[$i][] = sprintf("%u", crc32($w));
			}
			$s = '';
		}
		unset($a[0][$k]);
	}
	for (; list($i, $v) = each($ar);)
	{
		$ar[$i] = array_values(array_unique($ar[$i]));
	}
	return $ar;
}
/**
 * Prepares text for a TERM field
 */
function text_normalize($t)
{
	$t = str_replace( array('<![CDATA', ']]>' ), ' ', $t );
	global $oCase;
	return $oCase->nc($oCase->rm_($t));
}
/**
 *
 */
function gw_html_block_small($title = '', $content = '', $classN = 0, $alignT = 'left', $alignN = 'left')
{
	global $sys, $gw_this;
	// 25 Jun 2006: no content to display
	if ($content == ''){ return; }
	$classN = ($classN != '0') ? ( ' class="'.$classN.'"' ) : false;
	$alignT = ($alignT != '0') ? ( 'text-align:'.$alignT ) : 'text-align:'.$sys['css_align_left'];
	// 12 december 2002, rtl
	$alignN = ($alignN == 'center') ? $alignN : (($alignN == 'left') ? $sys['css_align_left'] : 'right');
	$str = '';
	$tmp['style_cont_str'] = '';
	if ($alignN != '')
	{
		$tmp['style_cont'] = array('text-align:'.$alignN);
		$tmp['style_cont_str'] = ' style="'.implode(';', $tmp['style_cont']).'"';
	}
	/* 2 feb 2006 */
	$oTpl = new $sys['class_tpl'];
	$oTpl->init($gw_this['vars']['visualtheme']);
	$oTpl->set_tpl('tpl_smallblock');
	if (isset($sys['path_www_images']))
	{
		$oTpl->addVal( 'v:path_img_www', $sys['dirname'] . '/'. $sys['path_www_images'] );
	}
	$oTpl->addVal( 'v:path_img', $sys['server_dir'] . '/'. $sys['path_img'] );
	$oTpl->addVal( 'v:head-text-align', $alignT);
	$oTpl->addVal( 'v:head-attr', $classN.$tmp['style_cont_str']);
	$oTpl->addVal( 'v:head-content', $content);
	$oTpl->addVal( 'v:head-title', $title);
	$oTpl->parse();
	return $oTpl->output();
}
/**
 * Returns date string in defined dateformat
 *
 * @param  string   $d date in timestamp(14) format
 * @param  string   $ftm date format
 * @return string   date
 */
function dateExtract($d, $fmt = "%d %M %Y %H:%i:%s")
{
	global $oCase;
	$monthsL = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_decl'));
	$monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
	if ((sizeof($monthsL) < 12) || (sizeof($monthsS) < 12))
	{
		return '';
	}
	/* TIMESTAMP(14) is not YYYYMMDDHHMMSS anymore since Mysql 4.1! Shit! */
	$d = preg_replace('/[^0-9]/', '', $d);
	/**
	 * %d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
	 * %m - month; i.e. "01" to "12"
	 * %FL - month, textual, long, lowercase; i.e. "january"
	 * %F - month, textual, long; i.e. "January"
	 * %ML - month, textual, 3 letters, lowercase; i.e. "jan"
	 * %M - month, textual, 3 letters; i.e. "Jan"
	 * %Y - year, 4 digits; i.e. "1999"
	 * %H - hour, 24-hour format; i.e. "00..23"
	 * %s - seconds; i.e. "00..59"
	 */
	$fmt = str_replace( "%d",  (substr($d,6,2)/1), $fmt ); // removes leading 0 from date
	$fmt = str_replace( "%m",  substr($d,4,2), $fmt );
	$fmt = str_replace( "%FL", str_replace('_', ' ', $oCase->lc($monthsL[(substr($d,4,2)/1)])), $fmt );
	$fmt = str_replace( "%F",  str_replace('_', ' ', $monthsL[(substr($d,4,2)/1)]), $fmt );
	$fmt = str_replace( "%ML", str_replace('_', ' ', $oCase->lc($monthsS[(substr($d,4,2)/1)])), $fmt );
	$fmt = str_replace( "%M",  str_replace('_', ' ', $monthsS[(substr($d,4,2)/1)]), $fmt );
	$fmt = str_replace( "%Y",  substr($d,0,4), $fmt );
	$fmt = str_replace( "%H",  substr($d,8,2), $fmt );
	$fmt = str_replace( "%i",  substr($d,10,2), $fmt );
	$fmt = str_replace( "%s",  substr($d,12,2), $fmt );
	return $fmt;
}
/**
 * Returns date string in defined dateformat
 *
 * @param  int      $d date in integer(10) format
 * @param  string   $ftm date format
 * @return string   date
 */
function date_extract_int($d, $fmt = "%d %M %Y %H:%i:%s")
{
	global $oCase;
	/* 1099947600 */
	$monthsL = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_decl'));
	$monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
	if ((sizeof($monthsL) < 12) || (sizeof($monthsS) < 12))
	{
		return '';
	}
	$YYYY = @date("Y", $d);
	$MM = @date("n", $d);
	$dd = @date("j", $d);
	$HH = @date("H", $d);
	$ii = @date("i", $d);
	$ss = @date("s", $d);
	$hh = @date("h", $d);
	$a = ($HH > 12) ? ('pm') : 'am';
	$A = ($HH > 12) ? ('PM') : 'AM';
	$mL = str_replace('_', ' ', $monthsL[$MM]);
	$mS = str_replace('_', ' ', $monthsS[$MM]);
	$fmt = str_replace( "%d",  $dd, $fmt );
	$fmt = str_replace( "%m",  $MM, $fmt );
	$fmt = str_replace( "%FL", $oCase->lc($mL), $fmt );
	$fmt = str_replace( "%F",  $mL, $fmt );
	$fmt = str_replace( "%ML", $oCase->lc($mS), $fmt);
	$fmt = str_replace( "%M",  $mS, $fmt );
	$fmt = str_replace( "%Y",  $YYYY, $fmt );
	$fmt = str_replace( "%A",  $A, $fmt );
	$fmt = str_replace( "%a",  $a, $fmt );
	$fmt = str_replace( "%H",  $HH, $fmt );
	$fmt = str_replace( "%h",  $hh, $fmt );
	$fmt = str_replace( "%i",  $ii, $fmt );
	$fmt = str_replace( "%s",  $ss, $fmt );
	return $fmt;
}
/**
 * Converts & into &amp; and encrypts url parameters
 *
 * @param   string  $url url with parameters
 * @param   array   $vars not in use
 * @return  string  converted and encrypted url parameters
 */
function append_url($url, $vars = array())
{
	global $oSess, $arDictParam, $sys;
	/* removes &amp; to avoid any problems with & */
	$url = str_replace("&amp;", "&", $url);
	$str_file = $param = '';
	$ar_param = array();
	if ( preg_match("/\?/", $url) && preg_match("/a=term/", $url) && !preg_match("/q=/", $url) ) // encode link to a term
	{
		list($str_file, $param) = explode("?", $url);
#		if (empty($arDictParam))
#		{
#			parse_str($param, $ar_param);
#			$arDictParam = getDictParam($ar_param['d']);
#		}
		if (isset($arDictParam['is_leech']) && ($arDictParam['is_leech'] == 1))
		{
			$url = $str_file . '?' . url_encrypt($sys['is_hideurl'], $param);
		}
	}
	if (isset($oSess) && $oSess->id_sess)
	{
		$url = str_replace(GW_SID.'='.$oSess->id_sess, '', $url);
		$url = $oSess->url($url);
	}
	$url = str_replace("&", "&amp;", $url);
	return $url;
}
## --------------------------------------------------------
## HTML-library
## (C) 2000-2003 Dmitry Shilnikov
/* 5 Aug 2012 - Use $oHtml to build attributes */
function htmlFormsSelect($arData, $default, $formname = 'select', $class = 'input', $style = '', $dir = 'ltr')
{
	global $oFunc, $sys, $oHtml;
	
	$ar_attr_select = array( );
	
	if ( strlen( $class ) ) {
		$ar_attr_select['class'] = $class;
	}
	if ( strlen( $style ) ) {
		$ar_attr_select['style'] = $style;
	}
	if ( strlen( $dir ) ) {
		$ar_attr_select['dir'] = $dir;
	}
	if ( strlen( $formname ) ) {
		$ar_attr_select['name'] = $formname;
	}

	$str = '<select' . $oHtml->paramValue( $ar_attr_select ) . '>';
	
	for ( reset( $arData ); list($k, $v) = each( $arData ); )
	{
		$ar_attr_option = array( );
		
		/* 8 Oct 2010: Decode quotes to calculate the correct string length */
		$v = htmlspecialchars_decode( $v, ENT_QUOTES );
		$v_src = $v;
		
		/* Cut long names in order to proper display */
		/* trim() is used to solve problems when function parameters comes 
		 * from MySQL and database is not updated to the current MySQL version */
		if ( $oFunc->mb_strlen( trim( $v ) ) > $sys['max_char_combobox'] )
		{
			$ar_attr_option['title'] = htmlspecialchars( $v_src );
			$v = $oFunc->mb_substr( $v, 0, $sys['max_char_combobox'] ) . '…';
		}
		
		$ar_attr_option['value'] = ( string ) $k;
		
		/* SELECTED */
		$ar_attr_option['selected'] = $k == $default ? 'selected' : '';

		$str .= PHP_EOL . '<option' . $oHtml->paramValue( $ar_attr_option ) . '>';
		
		if ( preg_match( "/abbrlang/", $formname ) || preg_match( "/trnslang/", $formname ) )
		{
			$str .= $k;
		}
		else
		{
			$str .= ( $v );
		}

		$str .= '</option>';
	}
	$str .= '</select>';				
	return $str;
}
## HTML-library
## --------------------------------------------------------
/**
 * Outputs nice help window.
 */
function kTbHelp($title, $content, $w = "100%")
{
	global $ar_theme;
	$str = "";
	$str .= '<table cellspacing="1" style="border:1px solid '.$ar_theme['color_4'].'" cellpadding="3" border="0" width="' . $w . '">';
	$str .= '<tbody><tr class="gray"><td style="background:'.$ar_theme['color_3'].'" class="xr">'. $title .'</td></tr>';
	$str .= '<tr><td style="background:'.$ar_theme['color_2'].'" class="xt">'. $content . "</td></tr>";
	$str .= '</tbody></table>';
	return $str;
}

/**
 * @param   array  $ar  keywords
 */
function searchkeys($ar)
{
	$k = implode(',', $ar);
	$k = str_replace("\r\n", ' ', $k);
	$k = str_replace("\r", ' ', $k);
	$k = str_replace("\n", ' ', $k);
	$k = str_replace(', ', ',', $k);
	$wordsA = explode(",", $k);
	for (reset($wordsA); list ($k, $v)= each($wordsA);)
	{
		$v = trim($v);
		$wordsA[$k] = $v;
		if ($v == '') { unset($wordsA[$k]); }
	}
#	$wordsA = gwShuffle(50, $wordsA);
	$str = implode(", ", $wordsA);
	return $str;
}
/**
 * Optimizes HTML-code. Light version
 */
function gw_text_smooth_light($t)
{
	$t = str_replace("<div", "\n<div", $t);
	$t = str_replace("<td", "\n<td", $t);
	$t = str_replace("<tr", "\n<tr", $t);
	$t = str_replace("<table", "\n<table", $t);
	$t = str_replace("\n</", '</', $t);
	return $t;
}
  
/**
 * Optimizes HTML-code
 */
function gw_text_smooth($t, $is_debug = 0)
{
	/* skip optimization when debug mode */
	if ($is_debug)
	{
		return $t;
	}
	$t = str_replace("\r\n", ' ', $t);
	$t = str_replace("\n", ' ', $t);
	$t = str_replace("\r", ' ', $t);
	$t = str_replace("\t", ' ', $t);
	$t = preg_replace("/ {2,}/" , " ", $t);
	$t = preg_replace("/<br>/i" , "<br />", $t);
	$t = str_replace('<center>' , '', $t);
	$t = str_replace('</center>' , '', $t);
	$t = str_replace(LF, "\n", $t);
#	$t = preg_replace("/<script(.*?)>(.*?)<\/script>/si", "<script type=\"text/javasctipt\">\\2</script>", $t);
	$t = preg_replace("/<!--(.*?)-->/s", '',$t);
	$t = preg_replace("/([, ])+([-]{2})([ \w+])/", '\\1&#8212;\\3',$t);
	$t = str_replace(' &#8212;', '&#160;&#8212;', $t);
	$t = preg_replace("/<script(.*?)>(.*?)<\/script>/si", "<script\\1>\\2</script>", $t);
#	$t = str_replace('//]]></script>', CRLF . '//]]></script>', $t);
	return $t;
}
/**
 * Filter for HTML-code of a definition text
 */
function gw_text_smooth_defn($t, $is_debug = 0)
{
	/* skip optimization when debug mode */
	if ($is_debug)
	{
		return $t;
	}
	/* preformatted text */
	/* (.*[^>]) */
	if (preg_match_all("/<pre(.*?)>(.*?)<\/pre>/s", $t, $pre))
	{
		for (; list ($k, $v)= each($pre[2]);)
		{
			$pre[2][$k] = str_replace("\t", "&#160;&#160;&#160;", $pre[2][$k]);
			$pre[2][$k] = str_replace("  ", "&#160;&#160;", $pre[2][$k]);
			$pre[2][$k] = str_replace(CRLF, "<br />", $pre[2][$k]);
			$pre[2][$k] = str_replace("\n", "<br />", $pre[2][$k]);
			$t = str_replace($pre[0][$k], '<tt'.$pre[1][$k].'>'.$pre[2][$k].'</tt>', $t);
			$t = preg_replace("/<tt><br \/>/", '<tt' . "\\1" . ' class="pre">', $t);
		}
	}
	return $t;
}

/**
 * Depreciated function name
 */
function textcodetoform($t)
{
	if (is_string($t))
	{
		$t = str_replace("&#228;", chr("228"), $t);
		$t = htmlspecialchars($t);
	}
	return $t;
}
/**
 * Depreciated
 *
 * Validates HTML-form
 */
function validatePostWalk($a, $reqFieldsA = array())
{
	$brokenFieldsA = array();
	for (reset($a); list($k1, $v1) = each($a);) // read posted array, usually HTTP_POST_VARS
	{
		for (reset($reqFieldsA); list($reqk1, $reqv1) = each($reqFieldsA );) // read required
		{
			if ($k1 == $reqv1) // posted == required
			{
				if (!is_array($v1))
				{
					$v1 = gw_text_sql($v1);
					// url check
					if ($reqv1 == 'url') { if (str_replace("http://","",$v1) == ''){ $v1 = ''; } }
					if ($v1 == ''){ $brokenFieldsA[$k1] = ''; }
				}
				else
				{
					for (reset($v1); list ($k2, $v2)= each($v1);)
					{
						$v1[$k2] = gw_text_sql($v2);
						if ($v1[$k2] == ''){ $brokenFieldsA[$k1] = ''; }
					}
				}
			} //
		} //
	} //
	return $brokenFieldsA;
}

//
## --------------------------------------------------------
## HTML-library
## (C) 1999 Dmitry Shilnikov
/**
* Universal function for date selection.
*
* @param    string  $name  field name
* @param    string  $val  timestamp
* @return   string  HTML-code
*/
function htmlFormSelectDate($name, $val)
{
	global $arLs;
	// month names
	$arLs['0'] = "---";
	// fields width [ year | month | day | (time) ]
	$cfgWidth = array("33%", "34%", "33%");
	// keep zero values?
	$cfgIsNulls = 0;
	// keep seconds?
	$cfgIsSec = 1;
	$str = "";
	if (preg_match( "/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{6})/", $val, $rowDate ))
	{
		// variable       -> variableD
		// variable[]     -> variableD[]
		// variable[name] -> variable[nameD]
		if (preg_match("/(.*?)\[(.*?)\]$/", $name, $rowName))
		{
			if ($rowName['2'] != "") { $rowName['1'] = $rowName['1'] . '[' . $rowName['2']; $rowName['2'] = ']'; }
			else { $rowName['2'] = "[]"; }
		}
		else
		{
			$rowName['1'] = $name; $rowName['2'] = "";
		}
		if ($cfgIsSec){ $cfgWidth = array("25%", "25%", "25%", "25%"); }
		// Year select #-------------------------------------------------
		$strA[0] = '<select name="' . $rowName['1'] . 'Y' . $rowName['2'] . '" class="input">';
		// for 0000
		if ($rowDate[1] == "0000" ) { $strA[0] .= '<option value="0000" selected="selected">0000</option>'; }
		else { if($cfgIsNulls) { $strA[0] .= '<option value="0000">0000</option>'; } }
		$strA[0] .= '<option value="2037">2037</option>';
		for ($y = (@date("Y") + 1); $y > (@date("Y") - 7); $y--)
		{
			// autoselect
			if ($rowDate[1] == $y) { $s = ' selected="selected"'; } else { $s = ""; }
			$strA[0] .= '<option value="' . $y . '"' . $s . '>' . $y . '</option>';
		}
		$strA[0] .= '<option value="1970">1970</option>';
		$strA[0] .= '</select>';
		// Month select #-------------------------------------------------
		$strA[1] = '<select name="' . $rowName['1'] . 'M' . $rowName['2'] . '" class="input">';
		// 30 sep 2002
		$monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
		for ($m = 0; $m <= 12; $m++)
		{
			// autoselect
			if ($rowDate[2] == $m) { $s = ' selected="selected"'; } else { $s = ""; }
			// output
			if ($m == 0) { if ($cfgIsNulls){ $strA[1] .= '<option value="' . sprintf ("%'02s", $m) . '"' . $s . '>' . sprintf ("%'02s", $m) . " " . $arLs[$m] . '</option>'; } }
			else { $strA[1] .= '<option value="' . sprintf ("%'02s", $m) . '"' . $s . '>' . @$monthsS[$m]  . '</option>'; }
		}
		$strA[1] .= "</select>";
		// Day select #-------------------------------------------------
		$strA[2] = '<select name="' . $rowName['1'] . 'D' . $rowName['2'] . '" class="input">';
		for ($d = 0; $d <= 31; $d++)
		{
			// autoselect
			if ($rowDate[3] == $d) { $s = ' selected="selected"'; } else { $s = ""; }
			// output
			if ($d == 0) { if($cfgIsNulls){ $strA[2] .= '<option value="' . sprintf ("%'02s", $d) . '"' . $s . '>' . sprintf ("%'02s", $d) . '</option>'; } }
			else { $strA[2] .= '<option value="' . sprintf ("%'02s", $d) . '"' . $s . '>' . sprintf ("%'02s", $d) . '</option>'; }
		}
		$strA[2] .= '</select>';
		// field for seconds
		if ($cfgIsSec)
		{
			$rowDate[4] = substr($rowDate[4], 0, 2) . ":" . substr($rowDate[4], 2, 2) . ":" . substr($rowDate[4], 4, 2);
			$strA[3] = '<input class="input" size="8" maxlength="8" name="' . $rowName['1'] . 'S' . $rowName['2'] . '" value="' . $rowDate[4] . '" />';
		}
	}
	else
	{
		$str .= "Invalid date format: $val";
	}
	if (isset($strA) && is_array($strA))
	{
		$str = '<table style="font-size:100%;" cellspacing="0" cellpadding="0" border="0" width="100%"><tbody><tr>';
		foreach ($strA as $k => $v)
		{
			$str .= '<td style="width:'.$cfgWidth[$k].'">';
			$str .= $v;
			$str .= '</td>';
		}
		$str .= '</tr></tbody></table>';
	}
	return $str;
}


/* end of file */
?>