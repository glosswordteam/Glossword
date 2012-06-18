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

$this->str .= '<h3>'.$this->oL->m('2_page_custom_az_browse').'</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';
if ($this->gw_this['vars']['tid'])
{
	/* Display Actions */
	$this->str .= $this->_get_nav();

	/* Start new FORM object */
	$oForm = new gwForms();
	$oForm->Set('action', $this->sys['page_admin']);
	$oForm->Set('formwidth', '100%');

	$this->str .= '<form accept-charset="UTF-8" action="'.$this->sys['page_admin'].'" enctype="application/x-www-form-urlencoded" id="form-custom-az" method="get">';
	/* */
	$this->str .= '<table id="tbl-custom_az" class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
	$this->str .= '<thead><tr>';
	$this->str .= '<th style="width:1%">N</th>';
	$this->str .= '<th style="width:20%"></th>';
	$this->str .= '<th style="width:10%">' . $this->oCase->uc($this->oL->m('1292')) . '</th>';
	$this->str .= '<th style="width:10%">' . $this->oCase->lc($this->oL->m('1292')) . '</th>';
	$this->str .= '<th>' . $this->oL->m('action') . '</th>';
	$this->str .= '</tr></thead>';

	/* Allow to add a letter */
	if ($this->gw_this['vars']['tid'] > 1)
	{
		$oForm->setTag('input', 'style', 'width:2em;font-size:200%');
		$oForm->setTag('input', 'maxlength', '3');
		$this->str .= '<tfoot><tr style="color:'.$this->ar_theme['color_5'].'">';
		$this->str .= '<td></td><td></td>';
		$this->str .= '<td style="text-align:'.$this->sys['css_align_right'].'">'.$oForm->field('input', 'arPost[az_value][]', '').'</td>';
		$this->str .= '<td style="text-align:'.$this->sys['css_align_left'].'">'.$oForm->field('input', 'arPost[az_value_lc][]', '').'</td>';
		$this->str .= '<td class="xt" style="text-align:'.$this->sys['css_align_left'].'">';
		$this->str .= '<input name="post" class="submitok" type="submit" value="'.$this->oL->m('3_save').'" />';
		$this->str .= '</td>';
		$this->str .= '</tr></tfoot>';
	}
	else
	{
		$this->str .= '<tfoot class="xt"><tr>';
		$this->str .= '<td></td><td colspan="4" class="center" style="padding:1em">'.$this->oL->m('1293').'</td>';
		$this->str .= '</tr></tfoot>';
	}

	$this->str .= '<tbody>';
	/* The list of letters */
	$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-adm', $this->gw_this['vars']['tid']), $this->component);
	$int_max_sort = sizeof($arSql);
	$cnt_row = 1;
	while (list($k, $arV) = each($arSql))
	{
		$isDn = $isUp = 1;
		if ($k == 0){ $isUp = 0; }
		if ($int_max_sort == $cnt_row){ $isDn = 0; }
		$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
		if ($arV['id_letter'] == $this->gw_this['vars']['w1'])
		{
			$oForm->setTag('input', 'style', 'border:2px solid #777;width:2em;font-size:200%');
		}
		$arV['az_value'] = urldecode($arV['az_value']);
		$arV['az_value_lc'] = urldecode($arV['az_value_lc']);
		$this->str .= '<tr id="az-'.$arV['id_letter'].'" style="background:'.$bgcolor.'">';
		$this->str .= '<td class="xt n" style="text-align:'.$this->sys['css_align_right'].'">' .  $cnt_row . '</td>';
		$this->str .= '<td class="gray center xw">'.$arV['az_value'].' '.$arV['az_value_lc'].'</td>';
		$this->str .= '<td style="text-align:'.$this->sys['css_align_right'].'">'.$oForm->field('input', 'arPost[az_value]['.$arV['id_letter'].']', $arV['az_value'] ).'</td>';
		$this->str .= '<td style="text-align:'.$this->sys['css_align_left'].'">'.$oForm->field('input', 'arPost[az_value_lc]['.$arV['id_letter'].']', $arV['az_value_lc'] ).'</td>';
		$this->str .= '<td class="actions-third" style="text-align:'.$this->sys['css_align_left'].'">';
		$this->str .= ($isUp) ? $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&mode=up&tid='.$this->gw_this['vars']['tid'].'&w1='.$arV['id_letter'], $this->oL->m('3_up')) : '<del>'.$this->oL->m('3_up').'</del>';
		$this->str .= ' ';
		$this->str .= ($isDn) ? $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&mode=down&tid='.$this->gw_this['vars']['tid'].'&w1='.$arV['id_letter'], $this->oL->m('3_down')) : '<del>'.$this->oL->m('3_down').'</del>';
		$this->str .= ' ';
		$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_remove').': &quot;'.$arV['az_value'].$arV['az_value_lc'].'&quot;. '.$this->oL->m('9_remove').'\' )');
		$this->str .= $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&mode=remove&tid='.$this->gw_this['vars']['tid'].'&w1='.$arV['id_letter'], $this->oL->m('3_remove') );
		$this->oHtml->setTag('a', 'onclick', '');
		$this->str .= '</td>';
		$this->str .= '</tr>';
		$cnt_row++;
		if ($arV['id_letter'] == $this->gw_this['vars']['w1'])
		{
			$oForm->setTag('input', 'style', 'width:2em;font-size:200%');
		}
	}
	$this->str .= '</tbody></table>';
	$this->str .= '<div>';
	$this->str .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
	$this->str .= $oForm->field('hidden', GW_ACTION, GW_A_EDIT);
	$this->str .= $oForm->field('hidden', 'mode', 'update');
	$this->str .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
	$this->str .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
	$this->str .= '</div>';
	$this->str .= '</form>';

	$this->str .= CRLF.'<script type="text/javascript">/*<![CDATA[*/';
	$this->str .= 'window.scrollTo(0, jsUtils.GetRealPos(gw_getElementById("az-'.$this->gw_this['vars']['w1'].'")).top );';
	$this->str .= '/*]]>*/</script>';
}
$this->str .= '</td>';
$this->str .= '</tr>';
$this->str .= '</tbody></table>';


?>