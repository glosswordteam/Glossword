<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: visual-themes_export.inc.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


if ($this->gw_this['vars']['post'] == '')
{
	$arV['tpl_pages'] = $this->get_tpl_pages($this->gw_this['vars']['tid']);
	$arV['is_as_file'] = 0;
	/* Not submitted */
	$this->str .= $this->get_form_export($arV);
		}
		else
		{
	$arPost =& $this->gw_this['vars']['arPost'];
	/* fix Print version */
	if (isset($arPost['tpl_page'][7]))
	{
		$arPost['tpl_page'][8] = 1;
	}
	/* Fix on/off options */
	$arIsV = array('is_binary', 'is_as_file');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* */
	$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
	$path_template = $this->sys['path_temporary'].'/t/'.$this->gw_this['vars']['tid'];
	/* Basic info */
	$arSql = $this->oDb->sqlExec('SELECT * FROM `'.$this->sys['tbl_prefix'].'theme` WHERE `id_theme` = "'. gw_text_sql($this->gw_this['vars']['tid']) .'"');
	$style_attr = '';
	for (; list($k, $arV) = each($arSql);)
	{
		unset($arV['is_active']);
		$arV['version'] = $arV['v1'].'.'.$arV['v2'].'.'.$arV['v3'];
		unset($arV['v1'], $arV['v2'], $arV['v3']);
		for (; list($attrK, $attrV) = each($arV);)
		{
			$style_attr .= CRLF.' '.$attrK.'="'.$attrV.'"';
		}
	}
	$xml .= CRLF . '<style'.$style_attr.'>';
	/* */
	if (!empty($arPost['tpl_page']))
	{
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-theme', gw_text_sql($this->gw_this['vars']['tid']), implode(',', array_keys($arPost['tpl_page']))) );
		$ar_theme = array();
		for (; list($arK, $arV) = each($arSql);)
		{
			$ar_theme[$arV['settings_key']] = $arV['settings_value'];
		}
		$xml .= CRLF . "\t". '<group id="settings">';
		for (; list($settings_key, $settings_value) = each($ar_theme);)
		{
			$xml .= CRLF . "\t\t" . '<setting key="';
			$xml .= $settings_key;
			$xml .= '"><![CDATA[';
			$settings_value = str_replace('<![CDATA[', '&lt;![CDATA[', $settings_value);
			$settings_value = str_replace(']]>', ']]&gt;', $settings_value);
			$xml .= $settings_value;
			$xml .= ']]></setting>';
			unset($ar_theme[$settings_key]);
		}
		$xml .= CRLF . "\t" . '</group>';
	}
	if ($arPost['is_binary'])
	{
		$ar_files = file_readDirF($path_template, '//');
		if (!empty($ar_files))
		{
			$xml .= CRLF . "\t". '<group id="binary">';
			for (; list($k, $v) = each($ar_files);)
			{
				$xml .= CRLF . "\t\t" . '<setting key="';
				$xml .= $v;
				$xml .= '">';
				$xml .= bin2hex($this->oFunc->file_get_contents($path_template.'/'.$v));
				$xml .= '</setting>';
			}
			$xml .= CRLF . "\t" . '</group>';
		}
	}
	$xml .= CRLF . '</style>';
	/* */
	$filename = 'visual-themes_'. $this->gw_this['vars']['tid'].'_'.@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']) .'.xml';
	/* */
	if ($arPost['is_as_file'])
	{
		/* Send headers */
		if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
		{
			header('Content-Type: application/force-download');
		}
		else
		{
			header('Content-Type: application/octet-stream');
		}
		header('Content-Length: '.strlen($xml));
		header('Content-disposition: attachment; filename="'. $filename);
		print $xml;
		exit;
	}
	else
	{
		/* Write to disk */
		$filename = $this->sys['path_export'] . '/'. $filename;
		$mode = 'w';
		$this->str .= '<ul class="xt">';
		$this->str .= '<li><span class="gray">';
		$this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';
		$isWrite = $this->oFunc->file_put_contents($filename, $xml, $mode);
		$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
		$this->str .= '</ul>';
	}
}



?>