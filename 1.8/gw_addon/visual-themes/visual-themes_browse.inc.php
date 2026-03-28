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

if ( !defined( 'IN_GW' ) )
{
	die( '<!-- $Id: visual-themes_browse.inc.php 490 2008-06-13 02:12:52Z glossword_team $ -->' );
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* Maximum number of a visual themes */
$this->cfg['int_max_themes'] = 10;

$arSql = $this->oDb->sqlExec( $this->oSqlQ->getQ( 'cnt-themes-adm' ), $this->component );
$int_found = isset( $arSql[0]['n'] ) ? $arSql[0]['n'] : 0;
$int_pages = ceil( $int_found / $this->cfg['int_max_themes'] );
if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $int_pages) )
{
	$this->gw_this['vars']['p'] = 1;
}
$sql_limit = $this->oDb->prn_limit( $int_found, $this->gw_this['vars']['p'], $this->cfg['int_max_themes'] );

/* The list of visual themes */
$arSql = $this->oDb->sqlExec( $this->oSqlQ->getQ( 'get-themes-adm', $sql_limit ), $this->component );

$str_pages = getNavToolbar( $int_pages, $this->gw_this['vars']['p'],
				$this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->component . '&' . 'p='
);
/* */
if ( $int_found > $this->cfg['int_max_themes'] )
{
	$this->str .= '<table cellspacing="1" cellpadding="4" border="0" width="100%"><tbody><tr class="xt gray">';
	$this->str .= '<td>';
	$this->str .= '</td>';
	$this->str .= '<td style="width:50%;text-align:' . $this->sys['css_align_right'] . '">';
	$this->str .= $str_pages;
	$this->str .= '</td>';
	$this->str .= '</tr></tbody></table>';
}

/* */
$this->str .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr style="color:' . $this->ar_theme['color_1'] . ';background:' . $this->ar_theme['color_6'] . '">';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th style="width:5%">' . $this->oL->m( 'visual_theme' ) . '</th>';
$this->str .= '<th style="width:50%">' . $this->oL->m( '1140' ) . '</th>';
$this->str .= '<th style="width:39%">' . $this->oL->m( 'action' ) . '</th>';
$this->str .= '<th style="width:5%">' . $this->oL->m( '1320' ) . '</th>';
$this->str .= '</tr></thead><tbody>';
$cnt_row = 1;
while ( list($k, $arV) = each( $arSql ) )
{
	$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
	$this->str .= CRLF . CRLF . '<tr id="theme-'.$arV['id_theme'].'" style="background:' . $bgcolor . '">';
	$this->str .= '<td style="text-align:' . $this->sys['css_align_right'] . '"><span class="xt">' . $cnt_row . '</span></td>';
	$this->str .= '<td style="padding:5px 3px" rowspan="3"><span class="xu gray">';
	$arV['theme_name'] = $arV['theme_name'] . ' ' . $arV['v1'] . '.' . $arV['v2'] . '.' . $arV['v3'];
	$img_src = file_exists( $this->sys['path_temporary'] . '/t' . '/' . $arV['id_theme'] . '/thumbnail.png' ) ? $this->sys['path_temporary'] . '/t' . '/' . $arV['id_theme'] . '/thumbnail.png' : 'img/0x0.gif';
	$this->str .= '<img style="border:1px solid ' . $this->ar_theme['color_5'] . '" width="150" height="150" src="' . $img_src . '" alt="' . $arV['theme_name'] . '" />';
	$this->str .= '</span></td>';
	$this->str .= '<td colspan="3" class="xu black" style="height:2em;vertical-align:middle">';
	$this->str .= $arV['theme_name'];
	$this->str .= '</td>';
	$this->str .= '</tr>';
	$this->str .= CRLF . CRLF . '<tr style="background:' . $bgcolor . '">';
	$this->str .= '<td></td>';
	$this->str .= '<td class="xq" style="height:4em;vertical-align:middle">';
	$this->str .= '<a href="mailto:' . $arV['theme_email'] . '">' . $arV['theme_author'] . '</a>';

	if ( $arV['theme_url'] != '' )
	{
		$this->str .= '<br />';
		$this->str .= '<a class="ext" href="' . $arV['theme_url'] . '" onclick="nw(this.href);return false">' . $arV['theme_url'] . '</a>';
	}

	$this->str .= '</td>';
	$this->str .= '<td style="text-align:center;vertical-align:middle" class="actions-third">';

	$a_actions = array();

	/* Edit theme */
	$a_actions[] = $this->oHtml->a( $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $arV['id_theme'], $this->oL->m( '3_edit' ) );
	
	/* 5 Oct 2010: Copy theme */
	if ( $arV['id_theme'] != 'gw_admin' )
	{
		$a_actions[] = $this->oHtml->a( $this->sys['page_admin'] . '?' . GW_ACTION . '=' .GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $arV['id_theme'] . '&mode=copy', $this->oL->m( 'Copy' ) );
	}

	/* Import theme */
	$a_actions[] = $this->oHtml->a( $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_IMPORT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $arV['id_theme'], $this->oL->m( '3_import' ) );

	/* Export theme */
	$a_actions[] = $this->oHtml->a( $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EXPORT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $arV['id_theme'], $this->oL->m( '3_export' ) );
	
	/* Actions for non-admin theme */
	if ( $arV['id_theme'] != 'gw_admin' )
	{
		/* Remove theme */
		$this->oHtml->setTag( 'a', 'class', 'submitdel' );
		$a_actions[] = $this->oHtml->a( $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&remove=1&' . GW_TARGET . '=' . $this->component . '&tid=' . $arV['id_theme'], $this->oL->m( '3_remove' ) );
		$this->oHtml->setTag( 'a', 'class', '' );
	}
	
	$this->str .= implode( ' ',  $a_actions );

	$this->str .= '</td>';

	/* 1.8.7: Turn on/off */
	$href_onoff = $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '&tid=' . $arV['id_theme'];

	$this->str .= '<td class="actions-third" style="text-align:center;vertical-align:middle">';

	if ( $arV['id_theme'] != 'gw_admin' )
	{
		$this->str .= ( $arV['is_active'] ? $this->oHtml->a( $href_onoff . '&mode=off', '<span class="green">' . $this->oL->m( 'is_1' ) . '</span>' ) : $this->oHtml->a( $href_onoff . '&mode=on', '<span class="red">' . $this->oL->m( 'is_0' ) . '</span>', $this->oL->m( '1057' ) ) );

		/* 28 Sep 2010: Assign theme by default */
		$this->str .= $this->oHtml->a( $href_onoff . '&mode=default', $this->oL->m( 'Default' ) );
	}



	$this->str .= '</td>';
#	$this->str .= '<td class="xt" style="vertical-align:middle;text-align:center">'. ($arV['is_active'] ? '<span class="green">'.$this->oL->m('is_1').'</span>' : '') . '</td>';
	$this->str .= '</tr>';

	$this->str .= CRLF . '<tr style="background:' . $bgcolor . '">';
	$this->str .= '<td></td><td colspan="4" class="xt">';
	$this->str .= implode( ' - ', $this->get_tpl_pages( $arV['id_theme'] ) );
	$this->str .= '</td>';
	$this->str .= '</tr>';
	/* */
#	$this->str .= '<tr><td></td><td colspan="4" class="gray"><span class="xt">'.implode(' - ', $this->get_tpl_pages($arV['id_theme'])). '</span></td></tr>';
	$cnt_row++;
}
$this->str .= '</tbody></table>';

$this->str .= '<table cellspacing="1" cellpadding="4" border="0" width="100%"><tbody><tr class="xt gray">';
$this->str .= '<td style="width:50%;text-align:' . $this->sys['css_align_left'] . '">';
$this->str .= $this->oL->m( 'srch_matches' ) . ': <strong>' . $int_found . '</strong>';
$this->str .= '</td>';
$this->str .= '<td style="width:50%;text-align:' . $this->sys['css_align_right'] . '">';
$this->str .= $str_pages;
$this->str .= '</td></tr></tbody></table>';
?>