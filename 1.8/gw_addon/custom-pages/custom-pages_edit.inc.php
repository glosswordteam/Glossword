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
	die('<!-- $Id: custom-pages_edit.inc.php 544 2008-08-14 20:47:29Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */



/* Check permission to edit the page */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-adm', $this->gw_this['vars']['tid']));
$arParsed = isset($arSql[0]) ? $arSql[0] : array();
/* No such page */
if (empty($arParsed))
{
	$this->gw_this['vars']['tid'] = '';
}

$arQ = array();

/* Page ID is not defined */
if (!$this->gw_this['vars']['tid'])
{
	/* Change heading */
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '.$this->oL->m('3_browse');
	$this->gw_this['vars'][GW_ACTION] = 'browse';
	$this->sys['path_component_action'] = $this->sys['path_addon'].'/'.$this->gw_this['vars'][GW_TARGET].'/'.$this->gw_this['vars'][GW_TARGET] . '_' . $this->gw_this['vars'][GW_ACTION].'.inc.php';
	include_once( $this->sys['path_component_action'] );
	return;
}

#$this->sys['isDebugQ'] = 1;


$is_allow_edit = ($this->oSess->is('is-cpages') ? 1 : ($this->oSess->is('is-cpages-own') && ($arParsed['id_user'] == $this->oSess->id_user)) ? 1 : 0);
if (!$is_allow_edit)
{
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	return;
}

/* Sorting */
/* Up or Down */
if ($this->gw_this['vars']['mode'] == 'up' || $this->gw_this['vars']['mode'] == 'dn')
{
	$score = ($this->gw_this['vars']['mode'] == 'up') ? "- 15" : "+ 15";
	$sql = sprintf('UPDATE `'.$this->sys['tbl_prefix'].'pages` SET `int_sort` = (int_sort %s) WHERE `id_page` = "%d"', $score, $this->gw_this['vars']['tid']);
	$this->oDb->sqlExec($sql);
	$sql = sprintf('SELECT `id_page` FROM `'.$this->sys['tbl_prefix'].'pages` WHERE id_parent = "%d" ORDER BY int_sort ASC', $this->ar[$this->gw_this['vars']['tid']]['p']);
	$arSql = $this->oDb->sqlExec($sql);
	$i = 10;
	for (; list($arK, $arV) = each($arSql);)
	{
		$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'pages`
						 SET `int_sort` = ' . $i . '
						 WHERE `id_page` = "' . $arV['id_page'].'"';
		$i += 10;
	}
	postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'reset')
{
	$i = 10;
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages_id-by-p', $this->ar[$this->gw_this['vars']['tid']]['p']));
	for (; list($arK, $arV) = each($arSql);)
	{
		$arQ[] = sprintf('UPDATE `'.$this->sys['tbl_prefix'].'pages`
						SET `int_sort` = "%d"
						WHERE `id_page` = "%d"',
						$i, $arV['id_page']
				);
		$i += 10;
	}
	postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'off')
{
	$arKeys = ctlgGetTree($this->ar, $this->gw_this['vars']['tid']);
	$this->oDb->sqlExec('UPDATE `'.$this->sys['tbl_prefix'].'pages`
						SET `is_active` = "0"
						WHERE `id_page` IN ('.implode(',', $arKeys).')' );
	postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'on')
{
	$arKeys = ctlgGetTree($this->ar, $this->gw_this['vars']['tid']);
	$this->oDb->sqlExec('UPDATE `'.$this->sys['tbl_prefix'].'pages`
						SET `is_active` = "1"
						WHERE `id_page` IN ('.implode(',', $arKeys).')' );
	postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}

/* */
$this->str .= $this->_get_nav();

/* Editing */
$ar_req_fields = array();


/* Not submitted */
if ($this->gw_this['vars']['post'] == '')
{
	/* Default settings */
	$arSql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-lang-adm', $this->gw_this['vars']['tid']));
	$arParsed['page'] =& $arSql2;
	$arParsed['ar'] =& $this->ar;
	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		$str_pagename = $this->gw_this['vars']['tid'];
		foreach($arParsed['page'] as $arPage)
		{
			if ($arPage['id_lang'] == $this->gw_this['vars']['locale_name'])
			{
				$str_pagename = '<div>'.htmlspecialchars($arPage['page_title']).'</div>';
				$str_pagename .= '<div>'.htmlspecialchars($arPage['page_descr']).'</div>';
			}
		}
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		$msg = $str_pagename;

		$oFormConfirm = new gwConfirmWindow;
		$oFormConfirm->action = $this->sys['page_admin'];
		$oFormConfirm->submitok = $this->oL->m('3_remove');
		$oFormConfirm->submitcancel = $this->oL->m('3_cancel');
		$oFormConfirm->formbgcolor = $this->ar_theme['color_2'];
		$oFormConfirm->formbordercolor = $this->ar_theme['color_4'];
		$oFormConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oFormConfirm->setQuestion('<p class="xr"><strong class="red">' . $this->oL->m('9_remove') .
								'</strong></p><p class="xt"><span class="gray">'. $this->oL->m('3_remove').
								': </span>'.$msg.'</p>');
		$oFormConfirm->tAlign = 'center';
		$oFormConfirm->formwidth = '400';
		$oFormConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
		$oFormConfirm->setField('hidden', 'w1', $this->gw_this['vars']['w1']);
		$oFormConfirm->setField('hidden', 'w2', $this->gw_this['vars']['w2']);
		$oFormConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
		$oFormConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$oFormConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$this->str .= $oFormConfirm->Form();
		return;
	}
	$arPre =& $this->gw_this['vars']['arPre'];
	$is_first = 1;
	if (is_array($arPre))
	{
		$is_first = 0;
		$arParsed = gw_ParsePre($arParsed, $arPre);
	}
	/* Not submitted */
	$this->str .= $this->get_form($arParsed, $is_first, 0, $ar_req_fields);


	/* Editing tips */
	$arHelpMap = array(
			'dict_name'  => 'tip028',
			'announce' => 'tip029',
			'1058'  => 'tip030',
			'keywords'  => 'tip007',
			'1073'  => 'tip031',
			'1059'  => 'tip032'
	);
	$strHelp = '';
	$strHelp .= '<dl>';
	for (; list($k, $v) = each($arHelpMap);)
	{
		$strHelp .= '<dt><strong>' . $this->oL->m($k) . '</strong></dt>';
		$strHelp .= '<dd>' . $this->oL->m($v) . '</dd>';
	}
	$strHelp .= '</dl>';
	$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);

}
else
{
	/* */
	$arPre =& $this->gw_this['vars']['arPre'];
	/* Enter debug mode */
#$this->sys['isDebugQ'] = 1;
	/* Fix on/off options */
	$arIsV = array('is_active');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPre[$v]  = isset($arPre[$v]) ? $arPre[$v] : 0;
	}
	$q1 = $q2 = array();
	$q1['id_parent'] = $arPre['id_parent'];
	$q1['is_active'] = $arPre['is_active'];
	$q1['page_icon'] = $arPre['page_icon'];
	$q1['page_php_1'] = $arPre['page_php_1'];
	$q1['page_php_2'] = $arPre['page_php_2'];
	$q1['page_uri'] = $arPre['page_uri'];
	$q1['id_user'] = $this->oSess->id_user;
	$q1['date_modified'] = $this->sys['time_now_gmt_unix'];
	/* Set ‘is_active' for subpages */
	if (isset($this->ar[$this->gw_this['vars']['tid']]['ch']))
	{
		$arKeys = ctlgGetTree($this->ar, $this->gw_this['vars']['tid']);
		while (is_array($arKeys) && list($k, $v) = each($arKeys))
		{
			$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'pages` SET `is_active` = "'.$q1['is_active'].'" WHERE id_parent = "' . $v . '"';
		}
	}
	/* */
	$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'pages_phrase` WHERE `id_page` = "'. $this->gw_this['vars']['tid'] .'"';
	$id_page_phrase = $this->oDb->MaxId($this->sys['tbl_prefix'].'pages_phrase', 'id_page_phrase');
	for (; list($elK, $arV) = each( $arPre['page']);)
	{
		$arV['page_title'] = gw_fix_input_to_db($arV['page_title']);
		$arV['page_descr'] = gw_fix_input_to_db($arV['page_descr']);
		$arV['page_content'] = gw_fix_input_to_db($arV['page_content']);
		$q2['page_title'] = $arV['page_title'];
		$q2['page_descr'] = $arV['page_descr'];
		$q2['page_content'] = $arV['page_content'];
		$q2['page_keywords'] = $arV['page_keywords'];
		$q2['id_lang'] = $arV['id_lang'];
		$q2['id_page_phrase'] = ($arV['id_page_phrase'] == '') ? $id_page_phrase+$elK : $arV['id_page_phrase'];
		$q2['id_page'] = $this->gw_this['vars']['tid'];
		$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'pages_phrase', '`id_page_phrase` = "' . $arV['id_page_phrase'] .'"');
	}
	$arQ[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'pages', '`id_page` = "'. $this->gw_this['vars']['tid'] .'"');
	$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE .'&'. GW_TARGET .'='. $this->gw_this['vars'][GW_TARGET].'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
}


?>