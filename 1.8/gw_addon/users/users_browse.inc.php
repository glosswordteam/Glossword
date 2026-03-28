<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: users_browse.inc.php 496 2008-06-14 06:42:53Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

$sql = $this->oSqlQ->getQ('cnt-users', $this->oSess->id_user, $this->oSess->id_guest);
$arSql = $this->oDb->sqlExec($sql);
$int_found = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
$int_pages = ceil($int_found / $this->sys['page_limit_search']);
if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $int_pages) ){ $this->gw_this['p']['page'] = 1; }

$sql_limit = $this->oDb->prn_limit($int_found, $this->gw_this['vars']['p'], $this->sys['page_limit_search']);
$sql = $this->oSqlQ->getQ('get-users', $this->oSess->id_user, $this->oSess->id_guest, $sql_limit);
$arSql = $this->oDb->sqlExec($sql);


$this->str .= '<table id="tbl-custom_az" class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th>' . $this->oL->m('1338').', '.$this->oL->m('1339') . '</th>';
$this->str .= '<th style="width:25%">' . $this->oL->m('contact_email') . '</th>';
$this->str .= '<th style="width:20%">' . $this->oL->m('termsamount') . '</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('date_logged') . '</th>';
$this->str .= '<th style="width:10%">' . $this->oL->m('action') . '</th>';
$this->str .= '</tr></thead>';

if (!$int_found)
{
	$this->str .= '<tfoot class="xt"><tr>';
	$this->str .= '<td class="n"></td><td colspan="5" class="center" style="padding:1em">'.$this->oL->m('1297').'</td>';
	$this->str .= '</tr></tfoot>';
}
else
{
	$this->str .= '<tbody>';

	$cnt_row = 0;
	foreach($arSql as $k => $arV)
	{
		$cnt_row % 2 ? ($bgcolor = $this->ar_theme['color_2']) : ($bgcolor = $this->ar_theme['color_1']);
		$cnt_row++;

		$href = $this->sys['page_admin'].'?'.GW_TARGET.'='.GW_T_USERS .'&'.  GW_ACTION.'='.GW_A_EDIT . '&w1='. $arV['id_user'];
		
		$this->str .= '<tr>';
		$this->str .= '<td class="xt n">'.$cnt_row.'</td>';
		$this->str .= '<td class="xu">'. $this->oHtml->a($href, $arV['user_fname'].' '.$arV['user_sname']) . '</td>';
		$this->str .= '<td class="xt"><a href="mailto:'. $arV['user_email'].'">'.$arV['user_email'].'</a></td>';
		
		$this->str .= '<td class="xt n" style="text-align:'.$this->sys['css_align_right'].'">'.$arV['int_items'].'</td>';
		$this->str .= '<td class="xt" style="text-align:'.$this->sys['css_align_right'].'">'.  ((($arV['date_login'] / 1) != 0) ? date_extract_int($arV['date_login'] + ($this->oSess->user_get_time_seconds()), "%d %FL %Y %H:%i") : '&#160;')  .'</td>';
		$this->str .= '<td class="actions-third" style="text-align:center">'. $this->oHtml->a($href, $this->oL->m('3_edit')) . '</td>';
		$this->str .= '</tr>';
	}
	$this->str .= '</tbody>';
}

$this->str .= '</table>';



?>