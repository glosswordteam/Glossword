<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: visual-themes_import.inc.php 531 2008-07-09 19:20:16Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();



$ar_req_fields = array();
if ($this->gw_this['vars']['post'] == '')
{
	$arV = array();
	$arV['file_location'] = '';
	$arV['xml'] = '';
	/* Not submitted */
	$this->str .= $this->get_form_import($arV);
}
else
{
	$file_location = array('name' => '');
	if (isset($this->gw_this['vars']['_files']['file_location']))
	{
		$file_location = $this->gw_this['vars']['_files']['file_location'];
	}

	$arPost =& $this->gw_this['vars']['arPost'];
	/* */
	$xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
	$file_target = urlencode(time().'_'.$file_location['name']);
	/* Create directory */
	$this->oFunc->file_put_contents($this->sys['path_temporary'].'/t/'.$file_target, '');
	
	if ( is_uploaded_file($xml_file) )
	{
		$arPost['xml'] = $this->oFunc->file_get_contents($xml_file);
	}
#$this->sys['isDebugQ'] = 1;
	/* Do import using DOM model */
	$oDom = new gw_domxml;
	$oDom->is_skip_white = 0;
	$oDom->strData =& $arPost['xml'];
	$oDom->parse();
	$oDom->strData = '';
	$arXmlLine = $oDom->get_elements_by_tagname('group');
	$arQ = $q1 = array();
	if ($arPost['id_theme'] == '0')
	{
		$q1['id_theme'] = $oDom->get_attribute('id_theme', 'style', $oDom->arData).'-'.time();
	}
	else
	{
		$q1['id_theme'] = $arPost['id_theme'];
	}
	$q1['theme_name'] = $oDom->get_attribute('theme_name', 'style', $oDom->arData);
	$q1['theme_author'] = $oDom->get_attribute('theme_author', 'style', $oDom->arData);
	$q1['theme_email'] = $oDom->get_attribute('theme_email', 'style', $oDom->arData);
	$q1['theme_url'] = $oDom->get_attribute('theme_url', 'style', $oDom->arData);
	
	@list($q1['v1'], $q1['v2'], $q1['v3']) = explode('.', $oDom->get_attribute('version', 'style', $oDom->arData));
	
	$arQ[0] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'theme');
	$arQ[] = sprintf('DELETE FROM `%s` WHERE `id_theme` = "%s"', $this->sys['tbl_prefix'].'theme_settings', gw_text_sql($q1['id_theme']));
	/* */
	$is_error_xml = 1;
	$cnt_themes = 0;
	$this->str .= '<ul class="xt">';
	for (; list($k1, $v1) = each($arXmlLine);)
	{
		/* per each group */
		if (!isset($v1['children'])) { continue; }
		$id_group = $oDom->get_attribute('id', $v1['tag'], $v1);
		for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
		{
			if (!is_array($v2)){ continue; }
			$q2 = array();
			if ($id_group == 'settings')
			{
				$q2['id_theme'] = $q1['id_theme'];
				$q2['date_compiled'] = 0;
				$q2['date_modified'] = $this->sys['time_now_gmt_unix'];
				$q2['settings_key'] = $oDom->get_attribute('key', $v2['tag'], $v2);
				$q2['settings_value'] = $oDom->get_content($v2);
				$q2['settings_value'] = str_replace('&lt;![CDATA[', '<![CDATA[', $q2['settings_value']);
				$q2['settings_value'] = str_replace(']]&gt;', ']]>', $q2['settings_value']);
				$q2['code'] = '';
				$q2['code_i'] = '';
				$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'theme_settings');
				$is_error_xml = 0;
			}
			else if ($id_group == 'binary')
			{
				$filename = $this->sys['path_temporary'].'/t/'.$q1['id_theme'].'/'.$oDom->get_attribute('key', $v2['tag'], $v2);
				if ($this->sys['isDebugQ'])
				{
					$this->str .= '<li>'. $filename . '</li>';
				}
				else
				{
					$file_contents = text_hex2bin($oDom->get_content($v2));
					$this->str .= '<li><span class="gray">';
					$this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';
					$isWrite = $this->oFunc->file_put_contents($filename, $file_contents, 'w');
					$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($file_contents), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
				}
			}
		}
	}
	$this->str .= '</ul>';
	/* Check for errors in XML */
	if ($is_error_xml)
	{
		$this->str .= '<ul class="xt"><li>'.$this->oL->m('reason_9').'</li>';
		$this->str .= '<li>'.$oDom->msg_error.'</li>';
		$this->str .= '</ul>';
		return;
	}
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 1);
}

?>