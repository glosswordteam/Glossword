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
	die('<!-- $Id: terms_add.inc.php 542 2008-07-19 04:51:10Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* */
if (empty($this->gw_this['ar_dict_list']))
{
	$this->str .= '<div class="margin-inside">';
	$this->str .= '<div class="xu">'.$this->oL->m('reason_4').'</div>';
	$this->str .= '<p class="actions-third">'.$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_ADD .'&'. GW_TARGET.'='.GW_T_DICTS, $this->oL->m('3_add'), $this->oL->m(1335).': '.$this->oL->m('3_add')  ).'</p>';
	$this->str .= '</div>';
	return;
}
/* */
if (!$this->gw_this['vars']['id'])
{
	/* Provide the list of dictionaries */
	$this->str .= '<div class="margin-inside">';
	$this->str .= '<div class="xu">'.$this->oL->m('srch_selectdict').':</div>';
	$this->str .= '<ul class="gwsql">';
	$cnt_dict = 0;
	$ar_allowed_dicts = $this->oSess->user_get('dictionaries');
	for (reset($this->gw_this['ar_dict_list']); list($k, $v) = each($this->gw_this['ar_dict_list']);)
	{
		if ( $this->oSess->is('is-sys-settings')
			|| (isset($ar_allowed_dicts[$v['id']])
				&& ( $this->oSess->is('is-terms') || $this->oSess->is('is-terms-own') )
				)
			)
		{
			$this->str .= '<li>'.gw_dict_browse_for_select($v).'</li>';
			++$cnt_dict;
		}
	}
	/* No allowed dictionaries */
	if (!$cnt_dict)
	{
		$this->str .= '<li>'.$this->oL->m('reason_4').'</li>';
		$this->str .= '<li>'.$this->oL->m('reason_13').'</li>';
	}
	$this->str .= '</ul>';
	$this->str .= '<div>';
	return;
}

global $oDom;
$oDom = new gw_domxml;

$arPre =& $this->gw_this['vars']['arPre'];
$arPost =& $this->gw_this['vars']['arPost'];
$ar_req_fields = array('term');

if ($this->gw_this['vars']['post'] == '')
{
	$is_first = 1;
	$arBroken = array();
	// default values
	$arParsed['term'][0]['value'] = '';
	$arParsed['term'][0]['attributes']['uri'] = '';
	$arParsed['term'][0]['attributes']['t1'] = '';
	$arParsed['term'][0]['attributes']['t2'] = '';
	$arParsed['term'][0]['attributes']['t3'] = '';
	$arParsed['term'][0]['attributes']['is_active'] = 1;
	$arParsed['term'][0]['attributes']['is_complete'] = 1;
	$arParsed['trsp'][0]['value'] = '';
	$arParsed['abbr'][0][0]['value'] = '';
	$arParsed['abbr'][0][0]['attributes']['lang'] = '';
	$arParsed['defn'][0]['value'] = '';
	$arParsed['after_is_save'] = $this->oSess->user_get('after_is_save');
	$arParsed['is_parse_url'] = $this->oSess->user_get('is_parse_url');

	/* Restore term settings */
	if ($this->oSess->user_get('form_term_'.$this->gw_this['vars']['id']))
	{
		$arParsed = $this->oSess->user_get('form_term_'.$this->gw_this['vars']['id']);
	}
	/* 1.8.6 */
	$arParsed['is_active'] = 1;
#	$arParsed['is_active'] = $this->oDom->get_attribute('is_active', '', $arParsed['term']);
	/* */
	$arParsed['date_created'] = $arParsed['date_modified'] = $this->sys['time_now_gmt_unix'];
	/* Additional actions */
	if (is_array($arPre))
	{
		$is_first = 0;
		$arParsed = gw_ParsePre($arParsed, $arPre);
	}
	$oDom->setCustomArray($arParsed);
	$this->str .= $this->get_form_term( $arParsed, $is_first, $ar_req_fields );
}
else
{
	/* Debug mode for adding terms */
#$this->sys['isDebugQ'] = 1;

	// A custom DOM model
	$arPre['term'][0]['tag'] = 'term';
	$arPre['after_is_save'] = isset($arPost['after_is_save']) ? 1 : 0;
	$arPre['is_parse_url'] = isset($arPost['is_parse_url']) ? 1 : 0;
	$arPre['term'][0]['attributes']['is_complete'] = isset($arPre['term'][0]['attributes']['is_complete']) ? 1 : 0;
	$arPre['term'][0]['attributes']['is_active'] = isset($arPre['term'][0]['attributes']['is_active']) ? 1 : 0;
	$isPostError = 0;
	$queryA = array();

	$oDom->setCustomArray($arPre);
	// check for broken fields
	if ($arPre['term'][0]['value'] == '')
	{
		$is_first = 0;
		$isPostError = 1;
		$arPre['is_active'] = 1;
		$arPre['date_created'] = $arPre['date_modified'] = $this->sys['time_now_gmt_unix'];

		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_term($arPre, $is_first, $ar_req_fields );
	}
	if (!$isPostError) // final update
	{
		global $arDictParam, $arFields, $arTermParam;

		/* Exclude stopwords */
		$arStop = gw_get_stopwords($arDictParam);
		/* */
		$tmp['cssTrClass'] = 'xt';
		$oRender = new gw_render;
		$oRender->Set('Gtmp', $tmp ); /* to remove */
		$oRender->Set('Gsys', $this->sys ); /* to remove */
		$oRender->Set('oL', $this->oL ); /* to remove */
		$oRender->Set('objDom', $oDom );
		$oRender->Set('arDictParam', $arDictParam ); /* to remove */
		$oRender->Set('arEl', $arPre );
		$oRender->Set('arFields', $arFields ); /* to remove */

		/* Create XML-code from array */
		$arPre['parameters']['xml'] = $oRender->array_to_xml($arPre);
		$arPre['parameters']['action'] = GW_A_UPDATE;
		/* */
		$q = $arQ = array();
		$arPre['is_specialchars'] = 1;
		$arPre['is_overwrite'] = 0;
		$arPre['is_parse_url'] = isset($arPost['is_parse_url']) ? 1 : 0;

		$arTermParam['term'] = $arPre['term'][0]['value'];
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
		/* Making term viewable next after adding */
		$arPre['date_created'] -= 60;
		/* Automatically parse URLs */
		if ($arPre['is_parse_url'])
		{
			$arPre['parameters']['xml'] = preg_replace("/(^|\[|\s)((http|https|news|ftp|aim|callto):\/\/\w+[^\s\[\\]]+)/ie"  , "gw_regex_url(array('html' => '\\2', 'show' => '\\2', 'st' => '\\1'))", $arPre['parameters']['xml']);
		}
#prn_r( $arPre );
		/* Construct queries for the term */
		$ar = gwAddTerm($arPre, $this->gw_this['vars']['id'], $arStop, 1, $arPre['is_specialchars'], $arPre['is_overwrite'], 0);
		/* */
		if (is_array($ar))
		{
			$arQ = array_merge($arQ, $ar);
		}
		else
		{
			$this->str .= $ar;
			return;
		}
		/* Update the last modification date for dictionary */
		$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'dict` SET `date_modified` = ' . $this->sys['time_now_gmt_unix'] . ' WHERE `id`= \'' .$this->gw_this['vars']['id'].'\'';

		/* Clean search results for edited dictionary */
		$arQ[] = $this->oSqlQ->getQ('del-srch-by-dict', $this->gw_this['vars']['id']);

		/* Clear dictionary cache */
		$strR .= gw_tmp_clear($this->gw_this['vars']['id']);

		/* Add term settings to user settings */
		$this->oSess->user_set('is_parse_url', $arPre['is_parse_url'] );
		$this->oSess->user_set('after_is_save', $arPre['after_is_save'] );

		if ($arPre['after_is_save'])
		{
			/* Save, but clear keys */
			$arPre['term'][0]['attributes']['t1'] = '';
			$arPre['term'][0]['attributes']['t2'] = '';
			$arPre['term'][0]['attributes']['t3'] = '';
			$arPre['term'][0]['attributes']['uri'] = '';
			$this->oSess->user_set('form_term_'. $this->gw_this['vars']['id'], array_clear_key($arPre, 'value') );
		}
		else
		{
			$this->oSess->user_unset('form_term_'. $this->gw_this['vars']['id']);
		}
		if (empty($strR))
		{
			/* Redirect to... */
			$str_url = gw_after_redirect_url( $arPost['after'], $arTermParam['tid'] );
#			print htmlspecialchars_ltgt(urldecode($str_url));
			$this->str .= postQuery($arQ, $str_url, $this->sys['isDebugQ'], 0);
		}
	}
}


?>