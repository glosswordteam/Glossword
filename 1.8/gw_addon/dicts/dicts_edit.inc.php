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
	die('<!-- $Id: dicts_edit.inc.php 501 2008-06-18 05:43:06Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* The list of allowed dictionaries */
$ar_allowed_dicts = $this->oSess->user_get('dictionaries');

/* Check for permission */
$is_allow_dict = 0;
if ( $this->oSess->is('is-sys-settings')
		|| $this->oSess->is('is-dicts')
		|| (isset($ar_allowed_dicts[$this->gw_this['vars']['id']]) && $this->oSess->is('is-dicts-own') )
	)
{
	$is_allow_dict = 1;
}
if (!$is_allow_dict)
{
	$this->str .= '<p class="xu">'.$this->oL->m('1297').'</p>';
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	$this->str .= '<div class="xt">'.$this->oL->m('1258').'</div>';
	$this->str .= '<div class="xt">'.$this->oL->m('1260').'</div>';
	return;
}


$ar_req_fields = array('title', 'id_topic', 'uid', 'tablename', 'lang', 'cfg', 'visualtheme');

global $arDictParam, $topic_mode, $arFields;

$arQ = array();
/* Switching On/off */
if ($this->gw_this['vars']['mode'] == 'off')
{
	$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'dict`
						SET `is_active` = "0"
						WHERE `id` = "'.$this->gw_this['vars']['id'].'"';
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&w1='.$this->gw_this['vars']['w1'], $this->sys['isDebugQ'], 0);
	return;
}
elseif ($this->gw_this['vars']['mode'] == 'on')
{
	$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'dict`
						SET `is_active` = "1"
						WHERE `id` = "'.$this->gw_this['vars']['id'].'"';
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&w1='.$this->gw_this['vars']['w1'], $this->sys['isDebugQ'], 0);
	return;
}



if ($this->gw_this['vars']['post'] == '')
{
	$gw_this['vars']['tid'] = $arDictParam['id_topic']; /* global */
	$arDictParam['date_created'] += $this->oSess->user_get_time_seconds();
	$arDictParam['date_modified'] += $this->oSess->user_get_time_seconds();
	$this->str .= $this->get_form_dict( $arDictParam, 0, 0, $ar_req_fields );
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		$oConfirm = new gwConfirmWindow;
		$oConfirm->action           = $this->sys['page_admin'];
		$oConfirm->submitok         = $this->oL->m('3_remove');
		$oConfirm->submitcancel     = $this->oL->m('3_cancel');
		$oConfirm->formbgcolor      = $this->ar_theme['color_2'];
		$oConfirm->formbordercolor  = $this->ar_theme['color_4'];
		$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oConfirm->css_align_right  = $this->sys['css_align_right'];
		$oConfirm->css_align_left   = $this->sys['css_align_left'];
		$oConfirm->setQuestion('<p class="xr"><span class="red"><strong>'.  $this->oL->m('9_remove') .
									'</strong></span></p><p class="xt"><span class="f">' . $this->oL->m('3_remove') .
										':</span><br />'.$arDictParam['title'].'</p>');
		$oConfirm->tAlign = 'center';
		$oConfirm->formwidth = '400';
		$oConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
		$oConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$oConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$oConfirm->setField('hidden', 'id', $this->gw_this['vars']['id']);
		/* 24 feb 2008: Append to URL */
		foreach ($this->sys['ar_url_append'] as $k => $v)
		{
			$oConfirm->setField('hidden', $k, $v);
		}
		$this->str .= $oConfirm->Form();
		return;
	}


	$arBroken = validatePostWalk($arPost, $ar_req_fields);
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
	/* Fix on/off options */
	$arOnOff = array('is_trsp', 'is_trns', 'is_abbr', 'is_defn', 'is_usg', 'is_src', 'is_syn', 'is_audio',
						'is_antonym', 'is_see', 'is_phone', 'is_address', 'is_show_full',
						'is_dict_as_index', 'is_show_tooltip_defn', 'is_filter_specials',
						'is_filter_stopwords', 'is_show_az', 'is_leech',
						'is_sens_num', 'is_sens_alp', 'is_sens_chr', 'is_sens_dia',
						'is_show_date_modified', 'is_show_authors', 'is_abbr_long',
						'is_show_term_suggest', 'is_show_term_report', 'is_show_page_refresh', 'is_show_page_send',
						'is_show_add_to_favorites', 'is_show_add_to_search', 'is_show_printversion'
	);
	for (; list($k, $v) = each($arOnOff);)
	{
		$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* */
	if (isset($ar_req_fields) && sizeof($arBroken) == 0) // no errors
	{
		$isPostError = 0;
	}
	else // on error
	{
		$isPostError = 1;
		// read posted variables, call HTML-form again.
		$arPost['tablename']     = $arDictParam['tablename'];
		$arPost['int_terms']     = $arDictParam['int_terms'];
		$arPost['id']            = $arDictParam['id'];
		$arPost['dict_uri']      = $arDictParam['dict_uri'];
		$arPost['date_created']  = $arPost['date_createdY'].$arPost['date_createdM'].$arPost['date_createdD'].preg_replace('/[^\d+]/', '', $arPost['date_createdS']);
		$arPost['date_modified'] = $this->sys['time_now_gmt_unix'];
		$arPost['ar_filter_stopwords'] = $arDictParam['ar_filter_stopwords'];
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_dict( $arPost, 1, $arBroken, $ar_req_fields );
	}
	if (!$isPostError) /* Final update */
	{
		$arQ = array();
		/* Editing dictionary */
#$this->sys['isDebugQ'] = 1;
		$arDictNewSettings = array(
			'page_limit' => $arPost['page_limit'],
			'page_limit_search' => $arPost['page_limit_search'],
			'min_srch_length' => $arPost['min_srch_length'],
			'recent_terms_sorting' => $arPost['recent_terms_sorting'],
			'recent_terms_number' => $arPost['recent_terms_number'],
			'recent_terms_display' => $arPost['recent_terms_display'],
			'ar_filter_stopwords' => isset($arPost['ar_filter_stopwords']) ? array_flip($arPost['ar_filter_stopwords']) : array(),
			'ar_filter_specials' => isset($arPost['ar_filter_specials']) ? array_flip($arPost['ar_filter_specials']) : array()
		);
		for (reset($arOnOff); list($k, $v) = each($arOnOff);)
		{
			$arDictNewSettings[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
		}
		/* */
		$q['dict_settings'] = serialize($arDictNewSettings);
		$q['id_topic']      = $arPost['id_topic'];
		$q['is_active']     = isset($arPost['is_active']) ? $arPost['is_active'] : 0;
		$q['lang']          = $arPost['lang'];
		$q['id_custom_az']  = $arPost['id_custom_az'];
		$q['id_vkbd']       = $arPost['id_vkbd'];
		$q['visualtheme']   = $arPost['visualtheme'];
		$q['title']         = gw_fix_input_to_db($arPost['title']);
		$q['announce']      = gw_fix_input_to_db($arPost['announce']);
		$q['keywords']      = htmlspecialchars($arPost['keywords']);
		$q['description']   = gw_fix_input_to_db($arPost['description']);
		/* Dictionary URI */
		$q['dict_uri']      = (!isset($arPost['dict_uri']) || !$arPost['dict_uri']) ? $q['title'] : $arPost['dict_uri'];
		/* 22 apr 2008: Prepare URI */
		if (isset($arPost['dict_uri']) && !$arPost['dict_uri'])
		{
			$q['dict_uri'] = $this->gw_this['vars']['id'].'-'.$this->oCase->rm_entity( $q['dict_uri'] );
			$q['dict_uri'] = $this->oCase->translit( $this->oCase->lc( $q['dict_uri'] ) );
			$q['dict_uri'] = preg_replace('/[^0-9A-Za-z_-]/', '-', $q['dict_uri']);
			$q['dict_uri'] = preg_replace('/-{2,}/', '-', $q['dict_uri']);
			if ($q['dict_uri'] == '-')
			{
				$q['dict_uri'] = $this->gw_this['vars']['id'].'-';
			}
		}

		if (($arPost['tablename_old'] != $arPost['tablename'])
		    && !getTableInfo($arPost['tablename']))
		{
			/* Database table name changed */
			$q['tablename'] = $arPost['tablename'];
			$arQ[] = $this->oSqlQ->getQ('rename-tbl', $arPost['tablename_old'], $arPost['tablename']);
		}
		$tmp['ar_created_his'] = explode(':', $arPost['date_createdS']);
		/* hour, minute, second, month, day, year  */
		$q['date_created'] = @mktime($tmp['ar_created_his'][0], $tmp['ar_created_his'][1], $tmp['ar_created_his'][2],
							$arPost['date_createdM'], $arPost['date_createdD'], $arPost['date_createdY']);
		$q['date_created'] -= $this->oSess->user_get_time_seconds();
		$q['int_terms'] = gw_sys_dict_count_terms();
		$q['int_terms_total'] = gw_sys_dict_count_terms_total();
		$q['int_bytes'] = gw_sys_dict_count_kb();

		/* Save dictionary settings */
		$arQ[] = gw_sql_update($q, TBL_DICT, 'id = "'.$this->gw_this['vars']['id'].'"');
		
		/* Clear cache */
		$strR .= gw_tmp_clear($this->gw_this['vars']['id']);

		/* Dictionary optimization */
#		gw_sys_dict_check($arDictParam['tablename']);
#		gw_sys_dict_check(TBL_MAP_USER_TERM);
		/* Redirect to... */
		$arPost['after'] = GW_AFTER_DICT_UPDATE;
		$str_url = gw_after_redirect_url($arPost['after']);
		$this->str .= postQuery($arQ, $str_url.'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
	}
}


?>