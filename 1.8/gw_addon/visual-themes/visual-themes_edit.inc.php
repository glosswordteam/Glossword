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
	die( '<!-- $Id: visual-themes_edit.inc.php 549 2008-08-16 14:29:59Z glossword_team $ -->' );
}
/* Included from $oAddonAdm->alpha(); */

#$this->sys['isDebugQ'] = 1;
$arQ = array ( );

switch ( $this->gw_this['vars']['mode'] )
{
	case 'off':
		/* Prevent disabling admin theme */
		if ( $this->gw_this['vars']['tid'] == 'gw_admin' )
		{
			$this->gw_this['vars']['tid'] = '';
		}

		$arQ[] = 'UPDATE `' . $this->sys['tbl_prefix'] . 'theme`
						SET `is_active` = "0"
						WHERE `id_theme` = "' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"';

		/* 28 Sep 2010: Redirect with anchor to theme */
		$this->str .= postQuery( $arQ, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '#theme-' . $this->gw_this['vars']['tid'], $this->sys['isDebugQ'], 0 );

		return;
		break;

	case 'on':

		/* Prevent changing admin theme */
		if ( $this->gw_this['vars']['tid'] == 'gw_admin' )
		{
			$this->gw_this['vars']['tid'] = '';
		}
		$arQ[] = 'UPDATE `' . $this->sys['tbl_prefix'] . 'theme`
						SET `is_active` = "1"
						WHERE `id_theme` = "' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"';

		/* 28 Sep 2010: Redirect with anchor to theme */
		$this->str .= postQuery( $arQ, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '#theme-' . $this->gw_this['vars']['tid'], $this->sys['isDebugQ'], 0 );

		return;
		break;
	case 'copy':

		/* 5 Oct 2010: Copy Visual theme */

		/* Prevent copying admin theme */
		if ( $this->gw_this['vars']['tid'] == 'gw_admin' )
		{
			return;
			break;
		}

		/* Set a new Theme system name */
		$s_id_theme = 'copy_of_' . $this->gw_this['vars']['tid'];

		/* Prepare queries for a new Theme */
		$a_q = array ( );

		/* Read the current Theme settings */
		$s_sql = 'SELECT * FROM `' . $this->sys['tbl_prefix'] . 'theme` WHERE `id_theme` = "' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"';
		$a_sql = $this->oDb->sqlExec( $s_sql, $this->component );

		foreach ( $a_sql as $i_row => $a_row )
		{
			unset( $a_row['id'] );
			$a_row['id_theme'] = $s_id_theme;
			$a_row['theme_name'] = 'Copy of '.$a_row['theme_name'];
			$a_row['theme_author'] = '';
			$a_row['theme_email'] = '';
			$a_row['theme_url'] = '';
			$a_q[] = gw_sql_insert( $a_row, $this->sys['tbl_prefix'] . 'theme' );
		}

		/* Read the current templates settings */
		$s_sql = 'SELECT * FROM `' . $this->sys['tbl_prefix'] . 'theme_settings` WHERE `id_theme` = "' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"';
		$a_sql = $this->oDb->sqlExec( $s_sql, $this->component );

		foreach ( $a_sql as $i_row => $a_row )
		{
			unset( $a_row['id'] );
			$a_row['id_theme'] = $s_id_theme;
			$a_q[] = gw_sql_insert( $a_row, $this->sys['tbl_prefix'] . 'theme_settings' );
		}

		/**
		 * -------------------------
		 * Copy Visual Theme files
		 * -------------------------
		 */
		/* Create directory */
		$s_path_target = $this->sys['path_temporary'] . '/t/' . $s_id_theme;
		$this->oFunc->file_put_contents( $s_path_target . '/_', '' );

		$this->str .= '<ul class="xt">';
		foreach ( glob( $this->sys['path_temporary'] . '/t/' . $this->gw_this['vars']['tid'] . '/*.*' ) as $file_src )
		{
			$file_target = $s_path_target . '/' . basename( $file_src );
			$is_copy = copy( $file_src, $file_target );
			$this->str .= '<li>' . $file_target . ' ' . ( $is_copy ? 'ok' : $this->oL->m( 'error' ) ) . '</li>';
		}
		$this->str .= '</ul>';

		/* Redirect to the list of themes and highlight copied theme */
		$this->str .= postQuery( $a_q, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '#theme-' . $s_id_theme, $this->sys['isDebugQ'], 0 );

		return;
		break;
	case 'default':

		/* Change cookie? */
		gw_set_cookie( 'gw_visualtheme', $this->gw_this['vars']['tid'], 0 );

		/* 28 Sep 2010: Assign this theme as system */
		$arQ[] = 'UPDATE `' . $this->sys['tbl_prefix'] . 'settings`
						SET `settings_val` = "' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"
						WHERE `settings_key` = "visualtheme"';
		$this->str .= postQuery( $arQ, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '#theme-' . $this->gw_this['vars']['tid'], $this->sys['isDebugQ'], 0 );

		return;
		break;
}


/* */
$this->str .= $this->_get_nav();

$ar_req_fields = array ( );
if ( $this->gw_this['vars']['post'] == '' )
{
	/* Removing */
	if ( $this->gw_this['vars']['remove'] )
	{
		$str_question = '<p class="xr red"><strong>' . $this->oL->m( '9_remove' ) . '</strong></p>';
		$str_question .= $this->gw_this['vars']['tid'];
		if ( $this->gw_this['vars']['w2'] )
		{
			$str_question .= '<br />' . $this->gw_this['vars']['w2'];
		}
		$oConfirm = new gwConfirmWindow;
		$oConfirm->action = $this->sys['page_admin'];
		$oConfirm->submitok = $this->oL->m( '3_remove' );
		$oConfirm->submitcancel = $this->oL->m( '3_cancel' );
		$oConfirm->formbgcolor = $this->ar_theme['color_2'];
		$oConfirm->formbordercolor = $this->ar_theme['color_4'];
		$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oConfirm->setQuestion( $str_question );
		$oConfirm->tAlign = 'center';
		$oConfirm->formwidth = '400';
		$oConfirm->setField( 'hidden', GW_ACTION, GW_A_REMOVE );
		$oConfirm->setField( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$oConfirm->setField( 'hidden', 'tid', $this->gw_this['vars']['tid'] );
		$oConfirm->setField( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		if ( $this->gw_this['vars']['w2'] )
		{
			$oConfirm->setField( 'hidden', 'w1', $this->gw_this['vars']['w1'] );
			$oConfirm->setField( 'hidden', 'w2', $this->gw_this['vars']['w2'] );
		}
		$this->str .= $oConfirm->Form();
		return;
	}

	/* Not submitted */
	if ( $this->gw_this['vars']['w1'] )
	{
		/* Read the group of settings &w1=id_group */
		$arSql = $this->oDb->sqlExec( $this->oSqlQ->getQ(
								'get-settings-by-gp', gw_text_sql( $this->gw_this['vars']['tid'] ), gw_text_sql( $this->gw_this['vars']['w1'] ) )
		);
		/* Edit CSS-files */
		if ( $this->gw_this['vars']['w1'] == 'css' )
		{
			$path_css = $this->sys['path_temporary'] . '/t/' . $this->gw_this['ar_themes'][$this->gw_this['vars']['tid']]['id_theme'];
			$arSql = array (
				array ( 'settings_key' => 'css_style', 'settings_value' => $this->oFunc->file_get_contents( $path_css . '/style.css' ) ),
				array ( 'settings_key' => 'css_print', 'settings_value' => $this->oFunc->file_get_contents( $path_css . '/style_print.css' ) )
			);
		}
	}
	else
	{
		$this->gw_this['vars']['w1'] = 0;
		$arSql = $this->gw_this['ar_themes'][$this->gw_this['vars']['tid']];
		$arT = array ( );
		$i = 0;
		for (; list($arK, $arV) = each( $arSql ); )
		{
			$arT[$i]['settings_key'] = $arK;
			$arT[$i]['settings_value'] = $arV;
			$i++;
		}
		$arSql = & $arT;
	}
	$ar_tpl_pages = $this->get_tpl_pages( $this->gw_this['vars']['tid'] );
	/* Table heading */
	$this->str .= '<div class="xt" style="padding-bottom:5px">' . implode( ' - ', $ar_tpl_pages ) . '</div>';
	/* The current template name */
	$this->cur_template = isset( $ar_tpl_pages[$this->gw_this['vars']['w1']] ) && ($this->gw_this['vars']['w1'] != 'css') ? strip_tags( $ar_tpl_pages[$this->gw_this['vars']['w1']] ) : $this->oL->m( '1137' );
	if ( $this->gw_this['vars']['w1'] == 'css' )
	{
		$this->cur_template = 'CSS';
	}
	/* */
	$this->sys['id_current_status'] = $this->sys['id_current_status'] . ': ' . $this->cur_template;
	/* */
	$this->str .= $this->get_form_theme( $arSql, 0, 0, $ar_req_fields );
}
else
{
	global $gw_oW;
	$gw_oW->is_strip_tags = 0;
	/* Editing */
#$this->sys['isDebugQ'] = 1;
	$arQ = array ( );
	$vars = & $this->gw_this['vars']['arPost'];
	/* */
	if ( is_numeric( $this->gw_this['vars']['w1'] ) && ($this->gw_this['vars']['w1'] > 0) )
	{
#		if (preg_match("/^is_/", $arV['settings_key']))
		/* Group of theme settings */
		for ( reset( $vars ); list($k, $v) = each( $vars ); )
		{
			$q = array ( );
			$q['date_compiled'] = $this->sys['time_now_gmt_unix'] - 2;
			$q['date_modified'] = $this->sys['time_now_gmt_unix'];
			$q['settings_value'] = gw_fix_input_to_db( $v );
			$arQ[] = gw_sql_update(
							$q,
							$this->sys['tbl_prefix'] . 'theme_settings',
							'id_theme="' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '" AND settings_key="' . gw_text_sql( $k ) . '"'
			);
		}
		/* Service task: check table theme_settings */
		$arQ[] = 'CHECK TABLE `' . $this->sys['tbl_prefix'] . 'theme_settings`';
	}
	elseif ( $this->gw_this['vars']['w1'] == 'css' )
	{
		for ( reset( $vars ); list($k, $v) = each( $vars ); )
		{
			$vars[$k] = str_replace( '&#032;', '&#32;', $vars[$k] );
			$vars[$k] = str_replace( array ( '{%', '%}' ), array ( '{', '}' ), $vars[$k] );
		}
		$path_css = $this->sys['path_temporary'] . '/t/' . $this->gw_this['ar_themes'][$this->gw_this['vars']['tid']]['id_theme'];
		$isWrite = $this->oFunc->file_put_contents( $path_css . '/style.css', $vars['css_style'], 'w' );
		$this->str .= '<ul class="xt">';
		$this->str .= '<li><span class="gray">';
		$this->str .= $path_css . '/style.css' . '</span>&#8230; ';
		$this->str .= ( $isWrite ? 'ok (' . $this->oFunc->number_format( strlen( $vars['css_style'] ), 0, $this->oL->languagelist( '4' ) ) . " " . $this->oL->m( 'bytes' ) . ')' : $this->oL->m( 'error' ) ) . '</li>';
		$this->str .= '<li><span class="gray">';
		$this->str .= $path_css . '/style_print.css' . '</span>&#8230; ';
		$this->str .= ( $isWrite ? 'ok (' . $this->oFunc->number_format( strlen( $vars['css_print'] ), 0, $this->oL->languagelist( '4' ) ) . " " . $this->oL->m( 'bytes' ) . ')' : $this->oL->m( 'error' ) ) . '</li>';
		$this->str .= '</ol>';
	}
	else
	{
		/* General theme settings (author, version etc.) */
		$q1 = array ( );
		if ( !isset( $vars['is_active'] ) )
		{
			$vars['is_active'] = 0;
		}
		/* 26 feb 2008: Admin theme is always on */
		if ( $this->gw_this['vars']['tid'] == 'gw_admin' )
		{
			$vars['is_active'] = 1;
		}
		for ( reset( $vars ); list($k, $v) = each( $vars ); )
		{
			$q1[$k] = $v;
		}
		list($q1['v1'], $q1['v2'], $q1['v3']) = explode( '.', $q1['theme_version'] );
		unset( $q1['theme_version'] );
		$arQ[] = gw_sql_update(
						$q1,
						$this->sys['tbl_prefix'] . 'theme',
						'id_theme="' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"'
		);
		/* Renamed visual theme theme */
		if ( isset( $q1['id_theme'] ) )
		{
			$arQ[] = gw_sql_update(
							array ( 'id_theme' => $q1['id_theme'] ),
							$this->sys['tbl_prefix'] . 'theme_settings',
							'id_theme="' . gw_text_sql( $this->gw_this['vars']['tid'] ) . '"'
			);
			$this->gw_this['vars']['tid'] = $q1['id_theme'];
		}
	}
	$this->str .= postQuery( $arQ, GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $this->gw_this['vars']['tid'] . '&w1=' . $this->gw_this['vars']['w1'] . '&note_afterpost=' . $this->oL->m( '1332' ), $this->sys['isDebugQ'], 0 );
}
?>