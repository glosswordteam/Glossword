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
	die('<!-- $Id: topics_edit.inc.php 502 2008-06-21 16:49:57Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* Check permission to edit the topic */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics-adm', $this->gw_this['vars']['tid']), 'page');
$arParsed = isset($arSql[0]) ? $arSql[0] : array();
/* No such topic */
if (empty($arParsed))
{
	$this->gw_this['vars']['tid'] = '';
}

/* Topic ID is not defined */
if (!$this->gw_this['vars']['tid'])
{
	/* Change heading */
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '.$this->oL->m('3_browse');
	$this->gw_this['vars'][GW_ACTION] = 'browse';
	$this->sys['path_component_action'] = $this->sys['path_addon'].'/'.$this->gw_this['vars'][GW_TARGET].'/'.$this->gw_this['vars'][GW_TARGET] . '_' . $this->gw_this['vars'][GW_ACTION].'.inc.php';
	include_once( $this->sys['path_component_action'] );
	return;
}


$arSql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics-lang-adm', $this->gw_this['vars']['tid']));
$arParsed['topic'] =& $arSql2;
$arParsed['ar'] =& $this->gw_this['ar_topics_list'];
$arParsed['date_created'] += ($this->oSess->user_get_time_seconds());
$arParsed['date_modified'] += ($this->oSess->user_get_time_seconds());

$is_allow_edit = ($this->oSess->is('is-topics') ? 1 : ($this->oSess->is('is-topics-own') && ($arParsed['id_user'] == $this->oSess->id_user)) ? 1 : 0);
if (!$is_allow_edit)
{
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	return;
}

/* Sorting */
$arQ = array();
/* Up or Down */
if ($this->gw_this['vars']['mode'] == 'up' || $this->gw_this['vars']['mode'] == 'dn')
{
	$score = ($this->gw_this['vars']['mode'] == 'up') ? "- 15" : "+ 15";
	$sql = sprintf('UPDATE `'.$this->sys['tbl_prefix'].'topics` SET `int_sort` = int_sort %s WHERE `id_topic` = "%d"', $score, $this->gw_this['vars']['tid']);
	$this->oDb->sqlExec($sql);
	$sql = sprintf('SELECT id_topic FROM `'.$this->sys['tbl_prefix'].'topics` WHERE `id_parent` = "%d" ORDER BY int_sort ASC', $arParsed['ar'][$this->gw_this['vars']['tid']]['p']);
	$arSql = $this->oDb->sqlExec($sql);
	$i = 10;
	for (; list($arK, $arV) = each($arSql);)
	{
		$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'topics`
					 SET int_sort = ' . $i . '
					 WHERE id_topic = ' . $arV['id_topic'];
		$i += 10;
	}
	$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'reset')
{
	$i = 10;
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics_id-by-p', $arParsed['ar'][$this->gw_this['vars']['tid']]['p']));
	for (; list($arK, $arV) = each($arSql);)
	{
		$arQ[] = sprintf('UPDATE `'.$this->sys['tbl_prefix'].'topics`
					SET int_sort = "%d"
					WHERE id_topic = "%d"',
					$i, $arV['id_topic']
				);
		$i += 10;
	}
	$this->str .= postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'off')
{
	$arKeys = ctlgGetTree($arParsed['ar'], $this->gw_this['vars']['tid']);
	$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'topics`
						SET `is_active` = "0"
						WHERE `id_topic` IN ('.implode(',', $arKeys).')';
	$this->str .= postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'on')
{
	$arKeys = ctlgGetTree($arParsed['ar'], $this->gw_this['vars']['tid']);
	$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'topics`
						SET `is_active` = "1"
						WHERE `id_topic` IN ('.implode(',', $arKeys).')';
	$this->str .= postQuery($arQ, GW_ACTION.'=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
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

	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		$str_pagename = $this->gw_this['vars']['tid'];
		foreach($arParsed['topic'] as $arTopic)
		{
			if ($arTopic['id_lang'] == $this->gw_this['vars']['locale_name'])
			{
				$str_pagename = '<div>'.htmlspecialchars($arTopic['topic_title']).'</div>';
				$str_pagename .= '<div>'.htmlspecialchars($arTopic['topic_descr']).'</div>';
			}
		}
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		$msg_error = '';

		/* warn if selected topic is a parent */
		if (isset($arParsed['ar'][$this->gw_this['vars']['tid']]['ch']))
		{
			$msg_error .= '<p class="xt">' . $this->oL->m('reason_2').'</p>';
			$arKeys = ctlgGetTree($arParsed['ar'], $this->gw_this['vars']['tid']);
			/* Unset the current Topic ID from subtopics tree */
			unset($arKeys[$this->gw_this['vars']['tid']]);
			while (is_array($arKeys) && list($k, $v) = each($arKeys))
			{
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics` WHERE id_topic = "' . $v . '"';
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase` WHERE id_topic = "' . $v . '"';
			}
		}

		$msg = $str_pagename.$msg_error;

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
	$this->str .= $this->get_form_topic($arParsed, $is_first, 0, $ar_req_fields);
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
	$q1['topic_icon'] = $arPre['topic_icon'];
	$q1['id_user'] = $this->oSess->id_user;
	$q1['date_modified'] = $this->sys['time_now_gmt_unix'];
	/* Set `is_active` for subtopics */
	$ar =& $this->gw_this['ar_topics_list'];
	if (isset($ar[$this->gw_this['vars']['tid']]['ch']))
	{
		$arKeys = ctlgGetTree($ar, $this->gw_this['vars']['tid']);
		while (is_array($arKeys) && list($k, $v) = each($arKeys))
		{
			$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'topics` SET `is_active` = "'.$q1['is_active'].'" WHERE id_parent = "' . $v . '"';
		}
	}

	/* */
	$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase` WHERE `id_topic` = "' . $this->gw_this['vars']['tid'] . '"';
	$id_topic_phrase = $this->oDb->MaxId($this->sys['tbl_prefix'].'topics_phrase', 'id_topic_phrase');
	for (; list($elK, $arV) = each( $arPre['topic']);)
	{
		$arV['topic_title'] = str_replace(array('{%', '%}'), array('{', '}'), $arV['topic_title']);
		$arV['topic_descr'] = str_replace(array('{%', '%}'), array('{', '}'), $arV['topic_descr']);
		$q2['topic_title'] = $arV['topic_title'];
		$q2['topic_descr'] = $arV['topic_descr'];
		$q2['id_lang'] = $arV['id_lang'];
		$q2['id_topic_phrase'] = ($arV['id_topic_phrase'] == '') ? $id_topic_phrase + $elK : $arV['id_topic_phrase'];
		$q2['id_topic'] = $this->gw_this['vars']['tid'];
		$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'topics_phrase', 'id_topic_phrase = "' . $arV['id_topic_phrase'] .'"');
	}
	$arQ[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'topics', 'id_topic = "'. $this->gw_this['vars']['tid'] .'"');
	$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE .'&'. GW_TARGET .'='. $this->gw_this['vars'][GW_TARGET].'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
}


?>