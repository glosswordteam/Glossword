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
	die( '<!-- $Id: dicts_add.inc.php 450 2008-04-29 21:48:43Z yrtimd $ -->' );
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array ( 'title', 'id_topic', 'tablename', 'lang', 'visualtheme' );

global $arDictParam, $topic_mode, $arFields;


if ( $this->gw_this['vars']['post'] == '' )
{
	$tid = 0; // global value for topic list
	$vars['title'] = $vars['description'] = $vars['announce'] = "";
	$vars['tablename'] = strtolower( $this->sys['tbl_prefix'] . 'dict_' . kMakeUid( 'x', 8 ) );
	$vars['id_topic'] = $this->gw_this['vars']['tid'];
	$vars['lang'] = 0;
	$vars['visualtheme'] = 0;
	$vars['keywords'] = '';
	$vars['is_active'] = 1;
	$vars['min_srch_length'] = 1;

	$this->str .= $this->get_form_dict( $vars, 0, 0, $ar_req_fields );
	$arHelpMap = array (
		'dict_name' => 'tip010',
#				'announce'    => 'tip009',
		'descr' => 'tip008',
#				'keywords'    => 'tip007',
		'topic' => 'tip006',
		'lang_source' => 'tip005',
		'visual_theme' => 'tip004',
		'sysname' => 'tip003'
	);
	$strHelp = '';
	$strHelp .= '<dl>';
	for (; list($k, $v) = each( $arHelpMap ); )
	{
		$strHelp .= '<dt><b>' . $this->oL->m( $k ) . '</b></dt>';
		$strHelp .= '<dd>' . $this->oL->m( $v ) . '</dd>';
	}
	$strHelp .= '</dl>';
	$this->str .= '<br />' . kTbHelp( $this->oL->m( '2_tip' ), $strHelp );
}
else
{
	/* */
	$arPost = & $this->gw_this['vars']['arPost'];

	$arBroken = validatePostWalk( $arPost, $ar_req_fields );
	$arPost['is_active'] = isset( $arPost['is_active'] ) ? $arPost['is_active'] : 0;
	$isPostError = 1;
	if ( sizeof( $arBroken ) == 0 )
	{
		$isPostError = 0;
	}
	else
	{
		$this->gw_this['vars']['tid'] = & $arPost['id_topic'];
		$this->str .= $this->get_form_dict( $arPost, 1, $arBroken, $ar_req_fields );
	}
	if ( !$isPostError )
	{
#$this->sys['isDebugQ'] = 1;
		/* Fix on/off options */
		$arIsV = array ( 'is_active', 'is_auth', 'is_post' );
		for ( reset( $arFields ); list($k, $v) = each( $arFields ); )
		{
			$arIsV[] = 'is_' . $v[0];
		}
		for (; list($k, $v) = each( $arIsV ); )
		{
			$arPost[$v] = isset( $arPost[$v] ) ? $arPost[$v] : 0;
		}
		/* Fixes for database name */
		$arPost['tablename'] = strtolower( preg_replace( "/![0-9a-zA-Z-]/", '', $arPost['tablename'] ) );
		/* */
		$q = $q3 = array ( );
		/* Default dictionary settings */
		$arDictNewSettings = array (
			'min_srch_length' => 1,
			'is_trsp' => '0',
			'is_trns' => '1',
			'is_abbr' => '1',
			'is_defn' => '1',
			'is_audio' => '0',
			'is_usg' => '0',
			'is_src' => '1',
			'is_syn' => '1',
			'is_antonym' => '0',
			'is_see' => '1',
			'is_phone' => '0',
			'is_address' => '0',
			'is_show_full' => '0',
			'is_dict_as_index' => '0',
			'is_show_tooltip_defn' => '0',
			'is_show_date_modified' => '0',
			'is_show_authors' => '0',
			'is_show_term_suggest' => '1',
			'is_show_term_report' => '1',
			'is_show_page_refresh' => '0',
			'is_show_page_send' => '0',
			'is_show_add_to_favorites' => '0',
			'is_show_add_to_search' => '1',
			'is_show_printversion' => '1',
			'recent_terms_sorting' => '1',
			'recent_terms_number' => 10,
			'recent_terms_display' => 1,
			'is_filter_specials' => 0,
			'is_filter_stopwords' => 0,
			'is_leech' => 0,
			'is_show_az' => 1,
			'is_sens_num' => 1,
			'is_sens_alp' => 1,
			'is_sens_chr' => 1,
			'is_sens_dia' => 0,
			'is_abbr_long' => 0,
			'page_limit' => 20,
			'page_limit_search' => 20,
			'ar_filter_stopwords' => array ( ),
		);
		$q['dict_settings'] = serialize( $arDictNewSettings );
		$q['id_topic'] = $arPost['id_topic'];
		$q['is_active'] = $arPost['is_active'];
		$q['lang'] = $arPost['lang'];
		$q['visualtheme'] = $arPost['visualtheme'];
		$q['tablename'] = $arPost['tablename'];
		$q['id_custom_az'] = 1;
		$q['id_vkbd'] = 0;

		$q['announce'] = '';
		$q['keywords'] = '';
		$q['title'] = gw_fix_input_to_db( $arPost['title'] );
		$q['description'] = gw_fix_input_to_db( $arPost['description'] );
		$q['date_created'] = $q['date_modified'] = $this->sys['time_now_gmt_unix'] - 61;
		$q['id_user'] = $this->oSess->id_user;
		/* Create tables */
		$queryA[] = $this->oSqlQ->getQ( 'create-dict', $q['tablename'] );
		$q['id'] = $q3['dict_id'] = $this->oDb->MaxId( TBL_DICT );

		/* 30 apr 2008: Prepare URI */
		$q['dict_uri'] = $q['id'] . '-' . $this->oCase->rm_entity( $q['title'] );
		$q['dict_uri'] = $this->oCase->translit( $this->oCase->lc( $q['dict_uri'] ) );
		$q['dict_uri'] = preg_replace( '/[^0-9A-Za-z_-]/', '-', $q['dict_uri'] );
		$q['dict_uri'] = preg_replace( '/-{2,}/', '-', $q['dict_uri'] );
		if ( $q['dict_uri'] == '-' )
		{
			$q['dict_uri'] = $q['id'] . '-';
		}

		$queryA[] = gw_sql_insert( $q, TBL_DICT );
		$q = array ( );
		$q['hits'] = 0;
		$q['id'] = $q3['dict_id'];
		$queryA[] = gw_sql_insert( $q, TBL_STAT_DICT );
		/* TODO: rename user_id / id_user */
		$q3['user_id'] = $this->oSess->user_get( 'id_user' );
		$queryA[] = gw_sql_insert( $q3, TBL_MAP_USER_DICT );
		/* Add Dictionary ID to the list of assigned dictionairies */
		$ar_user_dict = $this->oSess->user_get( 'dictionaries' );
		$ar_user_dict[$q3['dict_id']] = 1;
		$this->oSess->user_set( 'dictionaries', $ar_user_dict );
		/* */
		$this->str .= postQuery( $queryA, GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_DICTS . '&id=' . $q['id'], $this->sys['isDebugQ'], 0 );
	}
}
?>