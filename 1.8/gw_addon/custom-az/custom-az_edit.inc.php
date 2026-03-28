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
	die('<!-- $Id$ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* Sorting order */
$arAZ = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-adm', $this->gw_this['vars']['tid']), $this->component);
$int_max_sort = (sizeof($arAZ) + 1) * 10;
$ar_sorted = $ar_query = array();
/* */
#$this->sys['isDebugQ'] = 1;
/* */
switch ($this->gw_this['vars']['mode'])
{
	case 'up':
		foreach ($arAZ as $k => $v)
		{
			if ($v['id_letter'] == $this->gw_this['vars']['w1'])
			{
				$arAZ[$k]['int_sort'] -= 15;
			}
		}
		foreach ($arAZ as $k => $v)
		{
			$ar_sorted[$arAZ[$k]['int_sort']] = $arAZ[$k]['id_letter'];
		}
	break;
	case 'down':
		foreach ($arAZ as $k => $v)
		{
			if ($v['id_letter'] == $this->gw_this['vars']['w1'])
			{
				$arAZ[$k]['int_sort'] += 15;
			}
		}
		foreach ($arAZ as $k => $v)
		{
			$ar_sorted[$arAZ[$k]['int_sort']] = $arAZ[$k]['id_letter'];
		}
	break;
	case 'remove':
		/* Remove one letter */
		$ar_query[] = gw_sql_delete($this->sys['tbl_prefix'].'custom_az', array('id_letter' => $this->gw_this['vars']['w1']) );
	break;
	case 'update':
		/* Save alphabetic order */
		if ($this->gw_this['vars']['arPost']['az_value'][0])
		{
			/* Add new letter */
			$ar_query[] = gw_sql_insert(array(
					'az_value' => ($this->gw_this['vars']['arPost']['az_value'][0]),
					'az_value_lc' => ($this->gw_this['vars']['arPost']['az_value_lc'][0]),
					'id_profile' => $this->gw_this['vars']['tid'],
					'int_sort' => $int_max_sort
				), $this->sys['tbl_prefix'].'custom_az'
			);
		}
		else
		{
			/* Save all letters */
			foreach ($this->gw_this['vars']['arPost']['az_value'] as $id_letter => $v)
			{
				if (!$v) { continue; }
				$ar_query[] = gw_sql_update(array('az_int' => text_str2ord($v), 'az_value' => $v, 'az_value_lc' => $this->gw_this['vars']['arPost']['az_value_lc'][$id_letter]), $this->sys['tbl_prefix'].'custom_az', 'id_letter = "'.$id_letter.'"');
			}
		}
	break;
}
/* Re-sort */
if (!empty($ar_sorted))
{
	ksort($ar_sorted);
	$int_sort = 10;
	for (; list($k, $id_item) = each($ar_sorted);)
	{
		$ar_query[] = gw_sql_update(array('int_sort' => $int_sort), $this->sys['tbl_prefix'].'custom_az', 'id_letter = "'.$id_item.'"');
		$int_sort += 10;
	}
}
/* Update letters */
if (!empty($ar_query))
{
	$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&tid='.$this->gw_this['vars']['tid'].'&w1='.$this->gw_this['vars']['w1'], $this->sys['isDebugQ'], 0);
	return;
}


$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:'.$this->left_td_width.';background:'.$this->ar_theme['color_2'].';vertical-align:top">';

$this->str .= '<h3>'.$this->oL->m('2_page_custom_az_browse').'</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';



/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array('profile_name');
/* Get profile settings */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-profile', $this->gw_this['vars']['tid']), $this->component);

/* Profile not found */
if (empty($arSql))
{
	$this->str .= $this->oL->m('1341');
	$this->str .= '</td></tr></tbody></table>';
	return;
}

$arSql = $arSql[0];
if ($this->gw_this['vars']['post'] == '')
{

	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		/* Keep UTF-8 profile */
		if ($this->gw_this['vars']['tid'] == 1)
		{
			$this->str .= $this->_get_nav();
			$this->str .= '<div class="xt">'.$this->oL->m('1293').'</div>';
			return;
		}
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		$msg = '&quot;'.$arSql['profile_name'].'&quot;';

		$oConfirm = new gwConfirmWindow;
		$oConfirm->action = $this->sys['page_admin'];
		$oConfirm->submitok = $this->oL->m('3_remove');
		$oConfirm->submitcancel = $this->oL->m('3_cancel');
		$oConfirm->formbgcolor = $this->ar_theme['color_2'];
		$oConfirm->formbordercolor = $this->ar_theme['color_4'];
		$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oConfirm->setQuestion('<p class="xr"><strong class="red">' . $this->oL->m('9_remove') .
								'</strong></p><p class="xt"><span class="gray">'. $this->oL->m('3_remove').
								': </span>'.$msg.'</p>');
		$oConfirm->tAlign = 'center';
		$oConfirm->formwidth = '400';
		$oConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
		$oConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
		$oConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$oConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$this->str .= $oConfirm->Form();
		$this->str .= '</td></tr></tbody></table>';
		return;
	}
	/* Not submitted */
	$this->str .= $this->get_form_custom_az($arSql, 0, 0, $ar_req_fields);
}
else
{
	$arPost =& $this->gw_this['vars']['arPost'];
#	$arPost['profile_name'] = trim($arPost['profile_name']);
	/* Fix on/off options */
	$arIsV = array('is_active');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* Checking posted vars */
	$errorStr = '';
	$ar_broken = validatePostWalk($arPost, $ar_req_fields);
	if (empty($ar_broken))
	{
		$q1 =& $arPost;
		/* Must be always on */
		if ($this->gw_this['vars']['tid'] == 1)
		{
			$q1['is_active'] = 1;
		}
		$ar_query[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'custom_az_profiles', 'id_profile = "'.$this->gw_this['vars']['tid'].'"');
		/* Inactive profile */
		if (!$arPost['is_active'])
		{
			$ar_query[] = gw_sql_update(array('id_custom_az' => '1'), $this->sys['tbl_prefix'].'dict', 'id_custom_az = "'.$this->gw_this['vars']['tid'].'"');
		}
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&tid='.$this->gw_this['vars']['tid'].'&r='.time(), $this->sys['isDebugQ'], 0);
	}
	else
	{
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_custom_az($arPost, 1, $ar_broken, $ar_req_fields);
	}
}


$this->str .= '</td></tr></tbody></table>';


?>