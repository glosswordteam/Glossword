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
	die('<!-- $Id: terms_export.inc.php 491 2008-06-13 10:05:06Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


function getExportFilename($f, $cnt, $fmt)
{
	$r = strlen($cnt);
	$seq = "_%0" . $r . "d_of_%0" . $r . "d";
	return $f . $seq . '.' . $fmt;
}


if (empty($this->gw_this['ar_dict_list']))
{
	$this->str .= '<div class="margin-inside">';
	$this->str .= '<div class="xu">'.$this->oL->m('reason_4').'</div>';
	$this->str .= '<p class="actions-third">'.$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_ADD .'&'. GW_TARGET.'='.GW_T_DICTS, $this->oL->m('3_add'), $this->oL->m(1335).': '.$this->oL->m('3_add')  ).'</p>';
	$this->str .= '</div>';
	return;
}
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
				&& $this->oSess->is('is-terms-export') )
			)
		{
			$this->str .= '<li>'.gw_dict_browse_for_select($v).'</li>';
			$cnt_dict++;
		}
	}
	/* No allowed dictionaries */
	if (!$cnt_dict)
	{
		$this->str .= '<li>'.$this->oL->m('reason_13').'</li>';
	}
	$this->str .= '</ul>';
	$this->str .= '</div>';
	return;
}

global $arDictParam, $oSess;
/* Language */
$this->oL->getCustom('export', $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');

$arPost =& $this->gw_this['vars']['arPost'];

/* Checkboxes */
$ar_onoff = array();
/* Required fields */
$ar_required = array('date_min', 'date_max');

#$is_idadd   = isset($arPost['is_idadd']) ? $arPost['is_idadd'] : 0;
#$is_idupdate= isset($arPost['is_idupdate']) ? $arPost['is_idupdate'] : 1;
#$is_struc   = isset($arPost['is_struc']) ? $arPost['is_struc'] : 1;

/* */
/* Not submitted */
if ($this->gw_this['vars']['post'] == '')
{
	/* get MAX and MIN date from terms */
	$vars = $this->get_dates( $arDictParam['tablename'] );
	$vars['fmt_default'] = 'XML';
	/* Read available formats */
	$ar_formats = file_readDirD( $this->sys['path_addon'].'/'.$this->gw_this['vars'][GW_TARGET].'/', '/^export-/');
	$vars['ar_formats'] = str_replace('export-', '', $ar_formats);
	$vars['ar_formats'] = str_replace('-', ' ', $vars['ar_formats']);
	/* Adjust time to user */
	$vars['min'] += $this->oSess->user_get_time_seconds();
	$vars['max'] += $this->oSess->user_get_time_seconds();
	$this->str .= $this->get_form_export($vars);

}
else
{
	$vars['fmt'] = $arPost['fmt'];

	if ($this->gw_this['vars']['w1'] == 3)
	{
		/* Export to selected format */
		for (reset($ar_onoff); list($k, $v) = each($ar_onoff);)
		{
			$arPost[$v] = isset($arPost[$v]) ? '1' : '0';
		}
	}
	elseif ($this->gw_this['vars']['w1'] == 2)
	{

		/* The format settings */
		$tmp['ar_min_his'] = explode(':', $arPost['date_minS']);
		$tmp['ar_max_his'] = explode(':', $arPost['date_maxS']);
		/* hour, minute, second, month, day, year  */
		$vars['min']  = @mktime($tmp['ar_min_his'][0], $tmp['ar_min_his'][1], $tmp['ar_min_his'][2],
							$arPost['date_minM'], $arPost['date_minD'], $arPost['date_minY']);
		$vars['max']  = @mktime($tmp['ar_max_his'][0], $tmp['ar_max_his'][1], $tmp['ar_max_his'][2],
							$arPost['date_maxM'], $arPost['date_maxD'], $arPost['date_maxY']);
		/* Adjust time to db */
		$vars['min'] -= $this->oSess->user_get_time_seconds();
		$vars['max'] -= $this->oSess->user_get_time_seconds();
	}
	/* Increase time limit */
	@set_time_limit(3600);
	$filename = $this->sys['path_addon'].'/'.$this->gw_this['vars'][GW_TARGET].'/export-'.$vars['fmt'].'/index.inc.php';
	file_exists($filename)
		? include_once($filename)
		: printf($oL->m('reason_10'), $filename);
}

?>