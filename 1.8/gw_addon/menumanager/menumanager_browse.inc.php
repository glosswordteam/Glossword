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
	die('<!-- $Id: menumanager_browse.inc.php 491 2008-06-13 10:05:06Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

# switch($w1)

$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-components-actions', '1=1', '1=1'));
$arMenu = array();
/* Re-arrange array */
for (; list($k1, $arV) = each($arSql);)
{
	$arMenu[$arV['id_component_name']][] = $arV;
	unset($arSql[$k1]);
}

$this->str .= $this->_get_nav();

$cnt_row = 1;
$int_primary = 0;
$this->str .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr>';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th style="width:40%">' . $this->oL->m('1357') . '</th>';
$this->str .= '<th style="width:14%">' . $this->oL->m('1358') . '</th>';
$this->str .= '<th style="width:25%">' . $this->oL->m('action') . '</th>';
$this->str .= '</tr></thead>';
$this->str .= '<tbody>';
for (; list($id_component, $arV) = each($arMenu);)
{
	$is_up = ($int_primary > 0) ? 1 : 0;
	$is_down = ($int_primary < sizeof($arMenu)-1) ? 1 : 0;

	$path_component = '<strong>'.$arV[0]['id_component_name'].'</strong>_admin.php';
	$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];

	$href_edit_cm = $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component;
	$this->str .= '<tr style="background:'.$bgcolor.'">';
	$this->str .= '<td class="xt n" style="text-align:'.$this->sys['css_align_right'].'"><strong>' . ($int_primary+1) . '</strong>&#160;</td>';
	$this->str .= '<td class="xw">';
	$this->str .= $this->oHtml->a($href_edit_cm.'&w1=primary&tid='.$arV[0]['id_component'], $this->oL->m($arV[0]['cname']), $this->oL->m('3_edit') );
	$this->str .= '</td>';
	$this->str .= '<td class="xt gray">';
	$this->str .= $path_component;
	$this->str .= '</td>';
#	$this->str .= '<td class="xt gray center">';
#	$this->str .= $arV[0]['vv1'].'.'.$arV[0]['vv2'].'.'.$arV[0]['vv3'];
#	$this->str .= '</td>';
	$this->str .= '<td class="actions-third center">';
	$this->str .= $this->oHtml->a($href_edit_cm.'&w1=primary&tid='.$arV[0]['id_component'], $this->oL->m('3_edit'));
	$this->str .= ' ';
	$this->str .= ($is_up ? $this->oHtml->a($href_edit_cm.'&w2=up&w1=primary&tid='.$arV[0]['id_component'], $this->oL->m('3_up')) : '<del>'.$this->oL->m('3_up').'</del>' );
	$this->str .= ' ';
	$this->str .= ($is_down ?$this->oHtml->a($href_edit_cm.'&w2=down&w1=primary&tid='.$arV[0]['id_component'], $this->oL->m('3_down')) : '<del>'.$this->oL->m('3_down').'</del>' );
	$this->str .= '</td>';
	$this->str .= '</tr>';

	$int_secondary = 0;
	/* for each component action */
	for (; list($k2, $arV2) = each($arV);)
	{
		if (!$arV2['id']){ continue; }
		$is_up = ($int_secondary > 0) ? 1 : 0;
		$is_down = ($int_secondary < sizeof($arV)-1) ? 1 : 0;
		$cnt_row++;
		$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
		$path_component_action = $arV[0]['id_component_name'].'_<strong>'.$arV2['aname_sys'].'</strong>.inc.php';

		$this->str .= '<tr style="background:'.$bgcolor.'">';
		$this->str .= '<td class="xt n">&#160;'.($int_secondary+1).'</td>';
		$this->str .= '<td class="xu">&#160;&#160;';
		$this->str .= $this->oHtml->a($href_edit_cm.'&w1=secondary&tid='.$arV2['id'], $this->oL->m($arV2['aname']), $this->oL->m('3_edit') );
		$this->str .= '</td>';
		$this->str .= '<td class="xt">&#160;&#160;&#160;';
		$this->str .= $path_component_action;
		$this->str .= '</td>';
		$this->str .= '<td class="actions-third center">&#160;&#160;&#160;&#160;';
		$this->str .= $this->oHtml->a($href_edit_cm.'&w1=secondary&tid='.$arV2['id'], $this->oL->m('3_edit'));
		$this->str .= ' ';
		$this->str .= ($is_up ? $this->oHtml->a($href_edit_cm.'&w3='.$arV[0]['id_component'].'&w2=up&w1=secondary&tid='.$arV2['id'], $this->oL->m('3_up')) : '<del>'.$this->oL->m('3_up').'</del>' );
		$this->str .= ' ';
		$this->str .= ($is_down ? $this->oHtml->a($href_edit_cm.'&w3='.$arV[0]['id_component'].'&w2=down&w1=secondary&tid='.$arV2['id'], $this->oL->m('3_down')) : '<del>'.$this->oL->m('3_down').'</del>' );
		$this->str .= '</td>';
		$this->str .= '</tr>';
		$int_secondary++;
	}
	$cnt_row++;
	$int_primary++;
}
$this->str .= '</tbody></table>';

/* end of file */
?>