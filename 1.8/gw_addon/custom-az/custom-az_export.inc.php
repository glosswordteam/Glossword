<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id$ -->');
}
/* Included from $oAddonAdm->alpha(); */


$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:'.$this->left_td_width.';background:'.$this->ar_theme['color_2'].';vertical-align:top">';

$this->str .= '<h3>'.$this->oL->m('3_profile').'</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';


/* */
$this->str .= $this->_get_nav();

$is_error = 0;
/* Get profile settings */
$ar_profile = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-profile', $this->gw_this['vars']['tid']), $this->component);
$this->ar_profile = isset($ar_profile[0]) ? $ar_profile[0] : array();
/* Profile not found */
if (empty($this->ar_profile))
{
	$is_error = 1;
}
if ($this->gw_this['vars']['tid'] == 1)
{
	$this->str .= '<div class="xt">'.$this->oL->m('1293').'</div>';
	$is_error = 1;
}
if ($is_error)
{
	if ($this->gw_this['vars']['tid'] != 1)
	{
		$this->str .= $this->oL->m('1341');
	}
	
	$this->str .= '</td></tr></tbody></table>';
	return;
}

/* Filename */
$this->filename = $this->gw_this['vars'][GW_TARGET].'_'.$this->gw_this['vars']['tid'].'_'.preg_replace('#[^a-zA-Z0-9-]#', '', $this->ar_profile['profile_name']).'.xml';

$ar_req_fields = array();
if ($this->gw_this['vars']['post'] == '')
{
	/* Profile */
	$arPost['is_as_file'] = 0;
	$this->str .= $this->get_form_export($arPost, 0, 0, $ar_req_fields);

}
else
{
	$arPost =& $this->gw_this['vars']['arPost'];
	$arPost['is_as_file'] = isset($arPost['is_as_file']) ? 1 : 0;

	/* Get letters */
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-letters', $this->gw_this['vars']['tid']), $this->component);

	$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
	$xml .= '<glossword version="'.$this->sys['version'].'">';
	/* Serialize all parameters. */
	$xml .= CRLF . '<custom_az profile_name="'.htmlspecialchars($this->ar_profile['profile_name']).'" is_active="'.$this->ar_profile['is_active'].'">';
	for (; list($k, $arV) = each($arSql);)
	{
		$xml .= CRLF . "\t". '<entry>';
		$xml .= '<az_value><![CDATA['.$arV['az_value'].']]></az_value>';
		$xml .= '<az_value_lc><![CDATA['.$arV['az_value_lc'].']]></az_value_lc>';
		$xml .= '<int_sort><![CDATA['.$arV['int_sort'].']]></int_sort>';
		$xml .= '</entry>';
	}
	$xml .= CRLF . '</custom_az>';
	$xml .= CRLF . '</glossword>';

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
		$filename = $this->sys['path_export'] . '/'.$this->filename;
		$this->str .= '<ul class="xt">';
		$this->str .= '<li><span class="gray">';
		$this->str .= $this->oHtml->a(urldecode($filename), $filename) . '</span>&#8230; ';
		$isWrite = $this->oFunc->file_put_contents($filename, $xml, $mode);
		$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
		$this->str .= '</ul>';
	}
}
$this->str .= '</td>';
$this->str .= '</tr>';
$this->str .= '</tbody></table>';

?>