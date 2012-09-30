<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: virtual-keyboards_browse.inc.php 487 2008-06-12 10:33:21Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* */
$this->str .= '<table id="tbl-vkbd" class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr>';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('1289') . '</th>';
$this->str .= '<th>' . $this->oL->m('1306') . '</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('1401') . '</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('action') . '</th>';
$this->str .= '</tr></thead>';

/* The list of keyboards */
$cnt_row = 1;
while (list($k, $arV) = each($this->ar_profiles))
{
	$arV['vkbd_letters'] = str_replace(',', ', ', $arV['vkbd_letters']);
	$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
	$this->str .= '<tr style="color:'.$this->ar_theme['color_5'].';background:'.$bgcolor.'">';
	$this->str .= '<td class="xt n" style="text-align:'.$this->sys['css_align_right'].'">' .  $cnt_row . '</td>';
	$this->str .= '<td class="xu gray">'. $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&tid='.$arV['id_profile'], $arV['vkbd_name'] ) . '</td>';
	$this->str .= '<td class="xu gray">'. $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&tid='.$arV['id_profile'], $arV['vkbd_letters'] ) . '</td>';
	
	/* 1.8.12: Default for the index page on/off */
	$href_onoff = $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&tid='.$arV['id_profile'];
	$this->str .= '<td class="actions-third" style="width:1%;text-align:center">';
	$this->str .= ($arV['is_index_page'] 
				? $this->oHtml->a($href_onoff.'&mode=off', '<span class="green">'.$this->oL->m('is_1').'</span>')
				: $this->oHtml->a($href_onoff.'&mode=on', '<span class="red">'.$this->oL->m('is_0').'</span>', $this->oL->m('1057') ) );
	$this->str .= '</td>';	
	
	$this->str .= '<td class="actions-third" style="text-align:center">';
	$this->str .= $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&tid='.$arV['id_profile'], $this->oL->m('3_edit') );
	$this->str .= ' ';
	$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_remove').': &quot;'.$arV['vkbd_name'].'&quot;. '.$this->oL->m('9_remove').'\' )');
	$this->str .= $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_REMOVE .'&'. GW_TARGET.'='.$this->component.'&isConfirm=1&remove=1&tid='.$arV['id_profile'], $this->oL->m('3_remove') );
	$this->oHtml->setTag('a', 'onclick', '');
	$this->str .= '</td>';
	$this->str .= '</tr>';
	$cnt_row++;
}
$this->str .= '</tbody></table>';
$this->str .= '<br />';

?>