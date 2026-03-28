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
	die('<!-- $Id: topics_export.inc.php 497 2008-06-14 07:15:56Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

$this->filename = 'gw_topics_map_'.@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).'.xml';

if ($this->gw_this['vars']['post'] == '')
{
	/* Not submitted */
	$arV['is_include_date'] = 1;
	$arV['is_as_file'] = 1;
	$this->str .= $this->get_form_export($arV);
}
else
{
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Fix on/off options */
	$arIsV = array('is_include_date', 'is_as_file');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* */
	$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
	$xml .= '<glossword>';
	/* */
	$arSql = $this->oDb->sqlExec('SELECT * FROM `'.$this->sys['tbl_prefix'].'topics`');
	for (; list($k, $arV) = each($arSql);)
	{
		$style_attr = '';
		$id_topic = $arV['id_topic'];
		unset($arV['id_topic']);
		if (!$arPost['is_include_date'])
		{
			unset($arV['date_created']);
			unset($arV['date_modified']);
		}
		$xml .= CRLF . '<topic id="'.$id_topic.'">';
		/* Now serialize all parameters. Fast and easy. */
		$xml .= CRLF . "\t". '<parameters><![CDATA['. serialize($arV) .']]></parameters>';
		/* get topic names */
		$xml .= CRLF . "\t". '<entry>';
		$arSql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics-lang-adm', $id_topic));
		for (; list($k2, $arV2) = each($arSql2);)
		{
			/* remove encoding name */
			$arV2['id_lang'] = preg_replace("/-([a-z0-9])+$/", '', $arV2['id_lang']);
			/* start topic names */
			$xml .= CRLF . "\t\t". '<lang xml:lang="'.$arV2['id_lang'].'">';
			unset($arV2['id_lang']);
			for (; list($attrK, $attrV) = each($arV2);)
			{
				$xml .= CRLF . "\t\t\t<". $attrK.'>';
				$xml .= ($attrV == '') ? '' : '<![CDATA['.$attrV.']]>';
				$xml .= '</'. $attrK.'>';
			}
			$xml .= CRLF . "\t\t". '</lang>';
		}
		$xml .= CRLF . "\t". '</entry>';
		$xml .= CRLF . '</topic>';
	}
	$xml .= CRLF . '</glossword>';
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
		header('Content-disposition: attachment; filename="'.$this->filename.'"');
		print $xml;
		exit;
	}
	else
	{
		/* Write to disk */
		$mode = 'w';
		$filename = $this->sys['path_export'] .'/'. $this->filename ;
		$this->str .= '<ul class="xt">';
		$this->str .= '<li><span class="gray">';
		$this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';
		$isWrite = $this->oFunc->file_put_contents($filename, $xml, $mode);
		$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
		$this->str .= '</ul>';
	}
}

?>