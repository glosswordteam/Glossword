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
	die('<!-- $Id: visual-themes_add.inc.php 477 2008-05-28 20:31:16Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


#$this->sys['isDebugQ'] = 1;

$arPost =& $this->gw_this['vars']['arPost'];
$ar_req_fields = $q1 = array();
/* 24 Jun 2006: Add template into the group of templates */
if ($this->gw_this['vars']['w2'])
{
	if ($this->gw_this['vars']['post'] == '')
	{
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ(
				'get-settings-by-gp', gw_text_sql($this->gw_this['vars']['tid']), gw_text_sql($this->gw_this['vars']['w1']))
		);
		$this->str .= $this->get_form_tpl($arSql, 0, 0, $ar_req_fields);
	}
	else
	{
		$q1['settings_key'] = $arPost['new_template']['new'];
		$q1['id_group'] = $this->gw_this['vars']['w1'];
		$q1['int_sort'] = $arPost['new_template']['int_sort'];
		$arQ[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'theme_group');
		unset($arPost['new_template']);
		for (; list($k, $v) = each($arPost);)
		{
			$q2 = array();
			$q2['int_sort'] = $v['int_sort'];
			$q2['settings_key'] = $v['new'];
			$arQ[] = gw_sql_update($q2,
						$this->sys['tbl_prefix'].'theme_group',
						sprintf('settings_key = "%s" AND id_group = "%d"',
							gw_text_sql($v['old']), gw_text_sql($this->gw_this['vars']['w1'])
						)
					);
		}
		/* And empty values to all visual themes */
		/* The list of visual themes */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-themes-adm'), $this->component);
		for (; list($arK, $arV) = each($arSql);)
		{
			$q3 = array();
			/*
				When the selected theme is `gw_admin', add new template to `gw_admin' only.
				Otherwise, add new template to all visual themes but not to `gw_admin'
			*/
			if ($this->gw_this['vars']['tid'] == 'gw_admin')
			{
				if ($arV['id_theme'] == $this->gw_this['vars']['tid'])
				{
					$q3['id_theme'] = $arV['id_theme'];
					$q3['settings_key'] = $q1['settings_key'];
					$q3['settings_value'] = $q3['code'] = $q3['code_i'] = '';
					$arQ[] = gw_sql_insert($q3, $this->sys['tbl_prefix'].'theme_settings');
				}
			}
			else
			{
				if ($arV['id_theme'] == 'gw_admin')
				{
					continue;
				}
				$q3['id_theme'] = $arV['id_theme'];
				$q3['settings_key'] = $q1['settings_key'];
				$q3['settings_value'] = $q3['code'] = $q3['code_i'] = '';
				$arQ[] = gw_sql_insert($q3, $this->sys['tbl_prefix'].'theme_settings');
			}
		}
		$this->str .= postQuery($arQ, 'a='. GW_A_EDIT .'&'. GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&tid='.$this->gw_this['vars']['tid'].'&w1='.$this->gw_this['vars']['w1'], $this->sys['isDebugQ'], 0);
	}
}


?>