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
	die('<!-- $Id: terms_edit.inc.php 551 2008-08-17 17:34:05Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

global $arDictParam;

/* Mass term actions */
$ar_query = array();
#$this->sys['isDebugQ'] = 1;

if (isset($this->gw_this['vars']['arPost']['is_all']))
{
	/* Check for permission */
	$sql_where = ($this->oSess->is('is-terms') ? '' : ' WHERE `id_user` = "'.$this->oSess->user_get('id_user').'"');

	/* Do something with all terms */
	if (isset($this->gw_this['vars']['arPost']['selected_term_on']))
	{
		/* Publish all terms */
		$sql = 'UPDATE `%s` SET `is_active` = "1"'.$sql_where;
		$sql = sprintf($sql, $arDictParam['tablename']);
		$ar_query[] = $sql;
		/* Restore removed term, change User ID */
		$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET `is_active` = "1"'.$sql_where;
	}
	elseif (isset($this->gw_this['vars']['arPost']['selected_term_off']))
	{
		/* Unpublish all terms */
		$sql = 'UPDATE `%s` SET `is_active` = "0"'.$sql_where;
		$sql = sprintf($sql, $arDictParam['tablename']);
		$ar_query[] = $sql;
		/* Restore removed term, change User ID */
		$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET `is_active` = "0"'.$sql_where;
	}
	elseif (isset($this->gw_this['vars']['arPost']['selected_term_move']))
	{
		$arDictParamSource = getDictParam($this->gw_this['vars']['arPost']['id_source']);
		/* Move all terms */
		/*
			1. Add term to Target dictionary
			2. Update gw_wordmap
			3. Update gw_map_user_to_term
			4. Remove term from Source
		*/
		$sql = 'SELECT * FROM `%s` '.$sql_where;
		$arSql = $this->oDb->sqlExec( sprintf($sql, $arDictParamSource['tablename']) );
		$id_term = $this->oDb->MaxId( $arDictParam['tablename'], 'id' );
		while (list($k1, $arV) = each($arSql))
		{
			$ar_q = array();
			$id_term_prev = $arV['id'];
			$arV['id'] = $id_term;
			$ar_q[] = gw_sql_insert($arV, $arDictParam['tablename']);
			$q1['term_id'] = $arV['id'];
			$q1['dict_id'] = $arDictParam['id'];
			$ar_q[] = gw_sql_update($q1, TBL_WORDMAP, sprintf('term_id = "%d" AND dict_id = "%d"', $id_term_prev, $arDictParamSource['id']));
			$ar_q[] = gw_sql_update($q1, TBL_MAP_USER_TERM, sprintf('term_id = "%d" AND dict_id = "%d"', $id_term_prev, $arDictParamSource['id']));
			$ar_q[] = $this->oSqlQ->getQ('del-by-id', $arDictParamSource['tablename'], $id_term_prev);
			$id_term++;
			if ($this->sys['isDebugQ'])
			{
				prn_r( $ar_q );
			}
			else
			{
				/* Post queries */
				for (reset($ar_q); list($qk, $qv) = each($ar_q);)
				{
					$this->oDb->sqlExec($qv);
				}
			}
		}
		/* Update the last modification date for dictionary */
		$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'dict` SET `date_modified` = ' . $this->sys['time_now_gmt_unix'] . ' WHERE `id` = \'' . $arDictParam['id'].'\'';
	}
}
elseif (isset($this->gw_this['vars']['arPost']['ar_id']))
{
	/* Check for permission */
	/* Another syntax required */
	$sql_where = ($this->oSess->is('is-terms') ? '' : ' AND `id_user` = "'.$this->oSess->user_get('id_user').'"');
	/* Do something with selected terms */
	if (isset($this->gw_this['vars']['arPost']['selected_term_on']))
	{
		/* Publish term */
		$sql = 'UPDATE `%s` SET `is_active` = "1" WHERE `id` IN (%s)'.$sql_where;
		$sql = sprintf($sql, $arDictParam['tablename'], implode(',', $this->gw_this['vars']['arPost']['ar_id']));
		$ar_query[] = $sql;
		for (reset($this->gw_this['vars']['arPost']['ar_id']); list($k1, $id_term) = each($this->gw_this['vars']['arPost']['ar_id']);)
		{
			/* -- History of changes -- */
			/* Select History ID for the current term. Latest modification date. */
			$arCurrent = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-by-term_id', $id_term, 'LIMIT 1'));
			if (!empty($arCurrent))
			{
				$arCurrent = $arCurrent[0];
				/* Restore removed term, change User ID */
				$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET `is_active` = "1", `id_user` = "'.$this->oSess->user_get('id_user').'" WHERE `id` = "' . $arCurrent['id'] . '"'.$sql_where;
			}
		}
	}
	elseif (isset($this->gw_this['vars']['arPost']['selected_term_off']))
	{
		/* Unpublish term */
		$sql = 'UPDATE `%s` SET `is_active` = "0" WHERE `id` IN (%s)'.$sql_where;
		$sql = sprintf($sql, $arDictParam['tablename'], implode(',', $this->gw_this['vars']['arPost']['ar_id']));
		$ar_query[] = $sql;
		for (reset($this->gw_this['vars']['arPost']['ar_id']); list($k1, $id_term) = each($this->gw_this['vars']['arPost']['ar_id']);)
		{
			/* -- History of changes -- */
			/* Select History ID for the current term. Latest modification date. */
			$arCurrent = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-by-term_id', $id_term, 'LIMIT 1'));
			if (!empty($arCurrent))
			{
				$arCurrent = $arCurrent[0];
				/* Restore removed term, change User ID */
				$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET `is_active` = "0", `id_user` = "'.$this->oSess->user_get('id_user').'" WHERE `id` = "' . $arCurrent['id'] . '"'.$sql_where;
			}
		}
	}
	elseif (isset($this->gw_this['vars']['arPost']['selected_term_move']))
	{
		/* Check for permission */
		$arDictParamSource = getDictParam($this->gw_this['vars']['arPost']['id_source']);
		/* Move term */
		/*
			1. Add term to Target dictionary
			2. Update gw_wordmap
			3. Update gw_map_user_to_term
			4. Remove term from Source
		*/
		$sql = 'SELECT * FROM `%s` WHERE `id` IN (%s)'.$sql_where;
		$arSql = $this->oDb->sqlExec( sprintf($sql, $arDictParamSource['tablename'], implode(',', $this->gw_this['vars']['arPost']['ar_id'])) );
		$id_term = $this->oDb->MaxId( $arDictParam['tablename'], 'id' );
		while (list($k1, $arV) = each($arSql))
		{
			$ar_q = array();
			$id_term_prev = $arV['id'];
			$arV['id'] = $id_term;
			$ar_q[] = gw_sql_insert($arV, $arDictParam['tablename']);
			$q1['term_id'] = $arV['id'];
			$q1['dict_id'] = $arDictParam['id'];
			$ar_q[] = gw_sql_update($q1, TBL_WORDMAP, sprintf('term_id = "%d" AND dict_id = "%d"', $id_term_prev, $arDictParamSource['id']));
			$ar_q[] = gw_sql_update($q1, TBL_MAP_USER_TERM, sprintf('term_id = "%d" AND dict_id = "%d"', $id_term_prev, $arDictParamSource['id']));
			$ar_q[] = $this->oSqlQ->getQ('del-by-id', $arDictParamSource['tablename'], $id_term_prev);
			$id_term++;
			if ($this->sys['isDebugQ'])
			{
				prn_r( $ar_q );
			}
			else
			{
				/* Post queries */
				for (reset($ar_q); list($qk, $qv) = each($ar_q);)
				{
					$this->oDb->sqlExec($qv);
				}
			}
		}
		/* Update the last modification date for dictionary */
		$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'dict` SET `date_modified` = ' . $this->sys['time_now_gmt_unix'] . ' WHERE `id` = \'' . $arDictParam['id'].'\''.$sql_where;
	}
}
/* */
if (!empty($ar_query))
{
	/* Redirect to... */
	$str_url = gw_after_redirect_url(GW_AFTER_SRCH_BACK);
	$this->str .= postQuery($ar_query, $str_url, $this->sys['isDebugQ'], 0);
	return;
}

/* */
$this->str .= $this->_get_nav();

global $arTermParam;

/* Check permissions before to edit the term */
/**
 * Conditions: 
 * 1. The user is in the list of users for the dictionary
 * 1a. It is allowed to the user to edit terms, which were created by another users
 * 1b. The term is created by guest
 * 1c. The term is created by the user
 * 2. The user is admin
 */
$is_allow_edit = 0;
$ar_allowed_dicts = $this->oSess->user_get('dictionaries');
/* Condition 1. */ 
if ( isset($ar_allowed_dicts[$this->gw_this['vars']['id']]) )
{
	/* Conditions 1a, 1b, 1c. */ 
	$is_allow_edit = ($this->oSess->is('is-terms')
		? 1 
		: (($arTermParam['id_user'] == $this->oSess->id_guest)
			|| ($this->oSess->is('is-terms-own') && ($arTermParam['id_user'] == $this->oSess->id_user))) 
			? 1 : 0
	);
}
/* Condition 2. */ 
/* Skip checking on mass editing */
if ($this->oSess->is('is-sys-settings')
	|| isset($this->gw_this['vars']['arPost']['selected_term_remove']))
{
	$is_allow_edit = 1;
}
if (!$is_allow_edit)
{
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	$this->str .= '<p class="xu">'.$this->oL->m('1261').'</p>';
	$this->str .= '<p class="xu">'.$this->oL->m('1259').'</p>';
	return;
}


/* The history of changes, rollback, keywords */
if ($this->gw_this['vars']['w1'] == 'viewhistory')
{
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '. $this->oL->m(1294);

	$this->str .= getFormTitleNav( $this->oL->m('term') );
	/* Todo: pagination */
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-by-term_id', $this->gw_this['vars']['tid'], 'limit 0, 50'));
	$cnt = 0;
	$this->str .= ' ';
	/* No records found */
	if (empty($arSql))
	{
		$this->str .= $this->oL->m('1297');
		return;
	}
	/* Select the current term */
	$arCurrent = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-by-date', $arTermParam['tid'], $arTermParam['date_modified']));
	$arCurrent = isset($arCurrent[0]) ? $arCurrent[0] : array('id' => 0);
	/* */
	$this->str .= '<table class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
	$this->str .= '<thead><tr>';
	$this->str .= '<th style="width:1%">#</th>';
	$this->str .= '<th style="width:10%">'.$this->oL->m('action').'</th>';
	$this->str .= '<th>'.$this->oL->m('term').', '.$this->oL->m('defn').'</th>';
	$this->str .= '<th style="width:15%">'.$this->oL->m('date_modif').', '.$this->oL->m('user').'</th></thead><tbody>';
	for (; list($arK, $arV) = each($arSql);)
	{
		$cnt % 2 ? ($bgcolor = $this->ar_theme['color_2']) : ($bgcolor = $this->ar_theme['color_1']);
		$cnt++;
		$this->str .= '<tr class="gray" style="background:' . $bgcolor . '">';
		$this->str .= '<td class="xt n">' . $cnt . '</td>';

			$str_edit = '<del>'.$this->oL->m('1295').'</del>';
			$url_term = strip_tags($arV['term']);
			$href_edit = $this->sys['page_admin']. '?'.GW_ACTION.'='.GW_A_EDIT.'&w1=rollback&'.GW_TARGET.'='.GW_T_TERMS. '&id='.$arV['id_dict'] . '&tid='.$arV['id_term'] . '&w2='.$arV['id'];
			if ($is_allow_edit)
			{
				$str_edit = $this->oHtml->a( $href_edit, $this->oL->m('1295') );
				$url_term = $this->oHtml->a( $href_edit, strip_tags($arV['term']) );
			}
			/* Indicate as removed */
			$class_color = ($arV['is_active']==3?'red ':'');
			if ($arV['id'] == $arCurrent['id'] && ($arV['is_active'] != 3))
			{
				$str_edit = '<del>'.$this->oL->m('1295').'</del>';
				/* Indicate as active */
				$class_color = 'green ';
			}
			/* */
			$arV['date_modified'] += ($this->oSess->user_get_time_seconds());
			$this->str .= '<td class="actions-third">' . $str_edit . '</td>';
			$this->str .= '<td class="'.$class_color.'termpreview">' . $arV['term'] . '<div class="xq">'. htmlspecialchars($this->oFunc->mb_substr($arV['defn'], 0, 255, $this->sys['internal_encoding'])) . '</div></td>';
			$this->str .= '<td class="xq" style="white-space:nowrap">';
			$this->str .= date_extract_int($arV['date_modified'], '%H:%i:%s ') . (date_extract_int($arV['date_modified'], '%d') / 1) . date_extract_int($arV['date_modified'], '&#160;%F&#160;%Y');
			$this->oHtml->setTag('a', 'class', 'ext');
			$this->oHtml->setTag('a', 'onclick', 'nw(this);return false');
			if ($arV['user_name'])
			{
				$this->str .= '<br />'.$this->oHtml->a($this->sys['page_index'].'?'.GW_ACTION.'=profile&'.GW_TARGET.'=view&id='.$arV['id_user'], $arV['user_name']);
			}
			$this->oHtml->setTag('a', 'class', '');
			$this->oHtml->setTag('a', 'onclick', '');
			$this->str .= '</td>';
		$this->str .= '</tr>';
	}
	$this->str .= '</tbody></table>';
}
elseif ($this->gw_this['vars']['w1'] == 'rollback')
{
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '. $this->oL->m(1294);

	$arH = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-to-rollback', $this->gw_this['vars']['w2']));
	$arH = $arH[0];
	$id_dict = $arH['id_dict'];
	$arKeywords = unserialize( $arH['keywords']);
	$cnt = 0;
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-term-by-id-adm', $arDictParam['tablename'], $arH['id_term']));
	$arSql = $arSql[0];
	$arQ = array();
	/* Debug mode for rollback */
#$this->sys['isDebugQ'] = 1;
	/* Restore from removed */
	if ($arH['is_active'] == 3)
	{
		$arH['is_active'] = 1;
		$arQ[] = 'UPDATE `' . $this->sys['tbl_prefix'].'history_terms` SET is_active = "1" WHERE id = "' . $this->gw_this['vars']['w2'] . '"';
	}
	/* -- Rollback to selected term -- */
	/* Replace User ID */
	$arQ[] = gw_sql_update(array('user_id' => $arH['id_user']), $this->sys['tbl_prefix'].'map_user_to_term', 'term_id = ' . $arTermParam['tid'].' AND dict_id = '. $id_dict );
	/* Replace Term */
	unset($arH['keywords'], $arH['id_user'], $arH['id_term'], $arH['id_dict'], $arH['id']);
	$arQ[] = gw_sql_update($arH, $arDictParam['tablename'], 'id = '. $arTermParam['tid']);
	/* Replace search keywords */
	gwAddNewKeywords($id_dict, $arTermParam['tid'], $arKeywords, $arTermParam['tid'], 1, $arTermParam['date_created']);
	/* Redirect to... */
	$str_url = GW_ACTION.'='.GW_A_EDIT.'&w1=viewhistory&id='.$id_dict.'&amp;'.GW_TARGET.'='.GW_T_TERMS.'&tid='.$arTermParam['tid'];
	$this->str .= postQuery($arQ, $str_url, $this->sys['isDebugQ'], 0);
}
elseif ($this->gw_this['vars']['w1'] == 'viewkeywords')
{
	$arKeywords = $this->oDb->sqlExec($this->oSqlQ->getQ('get-keywords-by-term_id', $this->gw_this['vars']['tid'], $this->gw_this['vars']['id']));
	$this->str .= getFormTitleNav( $this->oL->m('term') );
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '. $this->oL->m(1284);

	$this->str .= '<table class="gw2TableFieldset" width="100%"><tbody>';
	$this->str .= '<tr><td class="xu">';
	foreach ($arKeywords as $k => $arV)
	{
		$arKeywords[] = '<a href="'.
			$this->oHtml->url_normalize( $this->sys['page_index'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&d='.$this->gw_this['vars']['id']. ($arV['term_match'] == '1' ? '&srch[in]=1' : '') .'&q='.$arV['word_text']).
			'" onclick="window.open(this.href);return false">'.
			($arV['term_match'] == '1' ? '<strong>'.$arV['word_text'].'</strong>' : $arV['word_text']) .'</a>';
		unset($arKeywords[$k]);
	}
	$this->str .= implode(', ', $arKeywords);
	$this->str .= '</td>';
	$this->str .= '</tr></tbody></table>';
}
if ($this->gw_this['vars']['w1'])
{
	return;
}


/* */
global $oDom, $arFields;

$arPre =& $this->gw_this['vars']['arPre'];
$arPost =& $this->gw_this['vars']['arPost'];
$ar_req_fields = array('term');

if ($this->gw_this['vars']['post'] == '')
{
	if (!isset($arTermParam))
	{
		$ar_terms = array();
		if (isset($this->gw_this['vars']['arPost']['ar_id']))
		{
			for (reset($this->gw_this['vars']['arPost']['ar_id']); list($k1, $id_term) = each($this->gw_this['vars']['arPost']['ar_id']);)
			{
				$arTermParam = getTermParam($id_term);
				$ar_terms[] = $arTermParam['term'];
			}
		}
		$arTermParam['term'] = implode(', ', $ar_terms);
		$arTermParam['defn'] = '';
	}
	/* 1.8.7: check for empty terms */
	if (!isset($this->gw_this['vars']['arPost']['ar_id'])
		&& !isset($this->gw_this['vars']['arPost']['is_all'])
		&& !$this->gw_this['vars']['tid'])
	{
		$this->str .= '<p class="xu">'.$this->oL->m('1348').'</p>';
		return;
	}

	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		/* Construct question */
		$str_question = '<p class="xr red"><strong>' . $this->oL->m('9_remove') .'</strong></p>';
		$str_question .= '<p class="xt"><span class="gray">' . $this->oL->m('3_remove') .':</span><br />' . $arTermParam['term']. '</p>';
		if ($arTermParam['defn'] != '')
		{
			$str_question .= '<p class="xt"><span class="gray">'. $this->oL->m('defn') . ':</span><br />';
			$str_question .= $this->oFunc->mb_substr(strip_tags(str_replace('><', '> <', $arTermParam['defn'])), 0, $this->sys['int_max_char_defn']). '&#8230;</p>';
		}
		/* */
		$tmp['after_post'] = $this->oSess->user_get('after_post_term');
		if (!$tmp['after_post'])
		{
			$tmp['after_post'] = GW_AFTER_SRCH_BACK;
		}
		$tmp['ar_after_posting'] = array(
			GW_AFTER_TERM_ADD => $this->oL->m('3_add_term'),
			GW_AFTER_DICT_UPDATE => $this->oL->m('after_post_1'),
			GW_AFTER_SRCH_BACK => $this->oL->m('after_post_3')
		);
		$oForm = new gw_htmlforms;
		$oForm->unsetTag('select');
		$oForm->setTag('select', 'class',  'input');

		$str_question .= '<table class="gw2TableFieldset" width="100%"><tbody>';
		$str_question .= '<tr><td style="width:25%"></td><td></td></tr>';
		$str_question .= '<tr>'.
					'<td class="td1">' . $this->oL->m('after_post') . '</td>'.
					'<td class="td2">' . $oForm->field('select', 'arPost[after]', $tmp['after_post'], '100%', $tmp['ar_after_posting']). '</td>'.
					'</tr>';
		$str_question .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_save_history]', 1). '</td>'.
					'<td class="td2"><label for="arPost_is_save_history_">' . $this->oL->m('1374') . '</label></td>'.
					'</tr>';
		$str_question .= '</tbody></table>';


		$oConfirm = new gwConfirmWindow;
		$oConfirm->action = $this->sys['page_admin'];
		$oConfirm->submitok = $this->oL->m('3_remove');
		$oConfirm->submitcancel = $this->oL->m('3_cancel');
		$oConfirm->formbgcolor = $this->ar_theme['color_2'];
		$oConfirm->formbordercolor = $this->ar_theme['color_4'];
		$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oConfirm->setQuestion($str_question);
		$oConfirm->tAlign = 'center';
		$oConfirm->formwidth = '400';
		$oConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
		$oConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$oConfirm->setField('hidden', 'id', $this->gw_this['vars']['id'] );
		$oConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']  );
		/* 1.8.10: Remove all at once */
		if (isset($this->gw_this['vars']['arPost']['is_all']))
		{
			$oConfirm->setField('hidden', 'arPost[is_all]', $this->gw_this['vars']['tid']  );
		}
		/* Multiple terms selected */
		if (isset($this->gw_this['vars']['arPost']['ar_id']))
		{
			for (reset($this->gw_this['vars']['arPost']['ar_id']); list($k1, $id_term) = each($this->gw_this['vars']['arPost']['ar_id']);)
			{
				$oConfirm->setField('hidden', 'arPost[ar_id][]', $id_term);
			}
		}
		/* 24 feb 2008: Append to URL */
		foreach ($this->sys['ar_url_append'] as $k => $v)
		{
			$oConfirm->setField('hidden', $k, $v);
		}
		$oConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$this->str .= $oConfirm->Form();
		return;
	}
	// read data from database,
	// see `if(!empty($tid))' in admin.php
	if (isset($arTermParam['term']))
	{
		$arParsed['term'][0]['value'] = $arTermParam['term'];
		$arParsed['term'][0]['attributes']['is_active'] = $arTermParam['is_active'];
		$arParsed['term'][0]['attributes']['is_complete'] = $arTermParam['is_complete'];
		$arParsed['term'][0]['attributes']['t1'] = $arTermParam['term_1'];
		$arParsed['term'][0]['attributes']['t2'] = $arTermParam['term_2'];
		$arParsed['term'][0]['attributes']['t3'] = $arTermParam['term_3'];
		$arParsed['term'][0]['attributes']['uri'] = $arTermParam['term_uri'];
		$arParsed['term'][0]['attributes']['term_order'] = $arTermParam['term_order'];
		$arParsed['after_is_save'] = $this->oSess->user_get('after_is_save');
		$arParsed['is_parse_url'] = $this->oSess->user_get('is_parse_url');
		$arParsed['date_created'] = $arTermParam['date_created'];
		$arParsed['date_modified'] = $arTermParam['date_modified'];
		$arParsed['is_active'] = $arTermParam['is_active'];

		/* Convert XML data into structured array */
		$arParsed = array_merge_clobber($arParsed, gw_Xml2Array($arTermParam['defn']));
		/* */
		$oDom = new gw_domxml;
		$oDom->setCustomArray($arParsed);
		/* */
		$is_first = 1;
		if (is_array($arPre))
		{
			$is_first = 0;
			$arParsed = gw_ParsePre($arParsed, $arPre);
		}
		/* Show HTML-form */
#		$arParsed['term'][0]['attributes']['is_complete'] = 2;
		$this->str .= $this->get_form_term( $arParsed, $is_first, $ar_req_fields );
	}
	else
	{
		/* on error */
		$this->str .= $this->oL->m('reason_13');
	}
}
else
{
	$oDom = new gw_domxml;
	$oDom->setCustomArray($arPre);
	$isPostError = 0;
	$arPre['after_is_save'] = isset($arPost['after_is_save']) ? 1 : 0;
	$arPre['is_parse_url'] = isset($arPost['is_parse_url']) ? 1 : 0;
	/* Check for broken fields */
	if ($arPre['term'][0]['value'] == '')
	{
		$is_first = 0;
		$isPostError = 1;
		$arPre['is_active'] = 1;
		$arPre['date_modified'] = $this->sys['time_now_gmt_unix'];
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_term( $arPre, $is_first, $ar_req_fields );
	}
	if (!$isPostError)
	{
		/* Changes for custom DOM model */
		$arPre['term'][0]['tag'] = 'term';
		$arPre['term'][0]['attributes']['is_active'] = isset($arPre['term'][0]['attributes']['is_active']) ? 1 : 0;
		$arPre['term'][0]['attributes']['is_complete'] = isset($arPre['term'][0]['attributes']['is_complete']) ? 1 : 0;
		/* */
		$tmp['cssTrClass'] = 'xt';
		$oRender = new gw_render;
		$oRender->Set('Gtmp', $tmp );
		$oRender->Set('Gsys', $this->sys ); // system, settings
		$oRender->Set('oL', $this->oL ); // language
		$oRender->Set('objDom', $oDom );
		$oRender->Set('arDictParam', $arDictParam );
		$oRender->Set('arEl', $arPre );
		$oRender->Set('arFields', $arFields );
		/* */
		$arPre['parameters']['xml'] = $oRender->array_to_xml($arPre);
		$arPre['parameters']['action'] = GW_A_UPDATE;
		/* */
		$queryA = $q = array();
		$arPre['is_specialchars'] = 1;
		$arPre['is_overwrite'] = 1;
		/* Convert date */
		if (isset($arPost['date_modifS'])) /* Fix date */
		{
			$arPost['date_createdS'] = str_replace(":", "", $arPost['date_createdS']);
			if (!preg_match("/[0-9]{6}/", $arPost['date_createdS'] )) { $arPost['date_createdS'] = '000000'; }
		}
		if (isset($arPost['date_modifS'])) /* Fix date */
		{
			$arPost['date_modifS'] = str_replace(":", "", $arPost['date_modifS']);
			if (!preg_match("/[0-9]{6}/", $arPost['date_modifS'] )) { $arPost['date_modifS'] = '000000'; }
		}
		$arPre['date_created'] = $arPost['date_createdY'].$arPost['date_createdM'].$arPost['date_createdD'].preg_replace('/[^\d+]/', '', $arPost['date_createdS']);
		$tmp['ar_created_his'] = explode(':', $arPost['date_createdS']);
		/* hour, minute, second, month, day, year  */
		$arPre['date_created'] = @mktime($tmp['ar_created_his'][0], $tmp['ar_created_his'][1], $tmp['ar_created_his'][2],
							$arPost['date_createdM'], $arPost['date_createdD'], $arPost['date_createdY']);
		$arPre['date_created'] -= $this->oSess->user_get_time_seconds();
		$arTermParam['term'] = $arPre['term'][0]['value'];
		/* Debug mode for editing */
#$this->sys['isDebugQ'] = 1;

		/* Exclude stopwords */
		$arStop = gw_get_stopwords($arDictParam);
		/* Automatically parse URLs */
		if ($arPre['is_parse_url'])
		{
			$arPre['parameters']['xml'] = preg_replace("/(^|\[|\s)((http|https|news|ftp|aim|callto):\/\/\w+[^\s\[\\]]+)/ie"  , "gw_regex_url(array('html' => '\\2', 'show' => '\\2', 'st' => '\\1'))", $arPre['parameters']['xml']);
		}
		/* Construct queries for the term */
		$ar = gwAddTerm($arPre, $this->gw_this['vars']['id'], $arStop, 1, $arPre['is_specialchars'], $arPre['is_overwrite'], 0, 1);
		/* */
		if (is_array($ar))
		{
			$queryA = array_merge($queryA, $ar);
		}
		else
		{
			$this->str .= $ar;
			return;
		}
		/* Term was edited, clean search results for edited dictionary */
		$queryA[] = $this->oSqlQ->getQ( 'del-srch-by-dict', $this->gw_this['vars']['id'] );
		/* Clear cache */
		$this->str .= gw_tmp_clear( $this->gw_this['vars']['id'] );
		/* */
		$queryA[] = 'UPDATE `'. TBL_DICT .'` SET `date_modified` = ' . $this->sys['time_now_gmt_unix'] . ' WHERE `id` = \'' .$this->gw_this['vars']['id'].'\'';
		/* Redirect to... */
		$str_url = gw_after_redirect_url( $arPost['after'], $this->gw_this['vars']['tid'] );
		$this->str .= postQuery($queryA, $str_url, $this->sys['isDebugQ'], 0);
	}
}

?>