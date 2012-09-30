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
if ( !defined( 'IN_GW' ) )
{
	die( '<!-- $Id: constructor.inc.php 551 2008-08-17 17:34:05Z glossword_team $ -->' );
}
/**
 *  General page constructor.
 */
$tmp['str_defn'] = '';
$gw_this['ar_breadcrumb'] = array ( );
$oTpl->tmp['d'] = array ( );
// --------------------------------------------------------
// because this is a basic constructor for the whole site,
// we need to separate templates between dictionary and website
if ( $gw_this['vars']['layout'] != '' ) // settings for all dictionary pages
{
	if ( ($gw_this['vars']['layout'] == 'index')
			|| ($gw_this['vars']['layout'] == GW_A_LIST)
			|| ($gw_this['vars']['layout'] == GW_T_TERM) )
	{
		if ( empty( $arDictParam ) || !isset( $arDictParam['title'] ) )
		{
			gwtk_header( $sys['server_proto'] . $sys['server_host'] . $sys['page_index'], $sys['is_delay_redirect'], __FILE__, __LINE__ );
		}
	}
	/* Dictionary pages */
	if ( $gw_this['vars'][GW_ID_DICT] )
	{
		$gw_this['ar_breadcrumb'][0] = strip_tags( $arDictParam['title'] );
		/* hits + 1 for non-localhost installations */
		if ( !IS_MYHOST && ($gw_this['vars'][GW_ACTION] != GW_A_SEARCH) )
		{
			$oDb->sqlExec( $oSqlQ->getQ( 'up-dict-hits', $arDictParam['id'] ) );
		}
		$mktCreated = $arDictParam['date_created'];
		/* Sets last modified date for all dictionary pages (!) */
		if ( $sys['is_cache_http'] )
		{
			/* first header */
			$oHdr->add( "Last-Modified: " . @date( "D, d M Y H:i:s", $mktCreated ) . ' GMT' );
		}
		/* Do we need to link main page from main page? No. */
		if ( $gw_this['vars']['layout'] == 'index' )
		{
			$oTpl->addVal( 'url:dict_name', $arDictParam['title'] );
			/* Authors */
			if ( $arDictParam['is_show_authors'] )
			{
				$arSql = $oDb->sqlRun( $oSqlQ->getQ( 'get-users-by-dict_id', $arDictParam['id'] ), 'dict' );
				$ar_authors = array ( );
				for (; list($k, $arV) = each( $arSql ); )
				{
					$ar_authors[] = $oHtml->a( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_PROFILE . '&t=view&id=' . $arV['id_user'], $arV['user_name'] );
				}
				$oTpl->addVal( 'v:dict_editors', sprintf( '<span class="gray">%s:</span> %s', $oL->m( '1112' ), implode( ', ', $ar_authors ) ) );
			}
		}
		else
		{
			$oTpl->addVal( 'url:dict_name', $oHtml->a( $sys['page_index'] . '?a=index&d=' . $arDictParam['uri'], $arDictParam['title'] ) );
		}
		$oTpl->addVal( 'v:dict_name', $arDictParam['title'] );
		$oTpl->addVal( 'v:dict_descr', $arDictParam['description'] );
		$oTpl->addVal( 'v:dict_terms', $oFunc->number_format( $arDictParam['int_terms'], 0, $oL->languagelist( '4' ) ) );
		/* The number of pages */
		$intSumPages = @ceil( $arDictParam['int_terms'] / $arDictParam['page_limit'] );
		/* ceil() could return 0, we need 1. */
		$intSumPages = (!$intSumPages ? 1 : $intSumPages);
		$oTpl->addVal( 'v:browse_pages', $oHtml->a( $sys['page_index'] . '?a=' . GW_A_LIST . '&' . GW_ID_DICT . '=' . $arDictParam['uri'] . '&p=1',
						$oFunc->number_format( $intSumPages, 0, $oL->languagelist( '4' ) ) . ' (' . $oL->m( '3_browse' ) . ')' )
		);
		// letters 0-Z
		/* show or hide letters toolbar */
		/* 2007 Sep 13: do not show toolbar for Contents page (too intensive) */
		if ( $arDictParam['is_show_az'] == 1 && $gw_this['vars'][GW_ACTION] != GW_A_CONTENTS )
		{
			if ( $gw_this['vars'][GW_ACTION] != GW_A_SEARCH )
			{
				// Construct A-Z toolbar (single letters)
				$ar0z = getLettersArray( $arDictParam['id'] );
			}
			// arrays, dictionary ID, current letter, isLinked, isLine;
			if ( ($gw_this['vars'][GW_ACTION] != GW_T_TERM)
					&& ($gw_this['vars'][GW_ACTION] != GW_A_SEARCH) )
			{
				$oTpl->addVal( 'v:az', getLetterHtml( $ar0z, $arDictParam['uri'], $gw_this['vars']['w1'] . ' ' ) );
			}
		}
		$oTpl->addVal( 'v:id_dict', $arDictParam['id'] );
		
		/* 1.8.7: Virtual keyboard */
		/* 1.8.12: Global virtual keyboard */		
		$oTpl->addVal( 'v:keyboard', gw_get_virtual_keyboard( $arDictParam['id_vkbd'], $arDictParam['id'] ) );

		/* 1.8.1: Link to dictionary contents, all terms A-Z */
		$oTpl->addVal( 'v:dict_contents', $oHtml->a( $sys['page_index'] . '?a=contents&d=' . $arDictParam['uri'], $oL->m( '1058' ) ) );
	}
	else
	{
		/* Non-dictionary pages (top10, feedback, title page) */
		
		/* Get HTML for Virtual keyboard */
		$oTpl->addVal( 'v:keyboard', gw_get_virtual_keyboard() );	
	
		if ( $sys['is_cache_http'] )
		{
			$oHdr->add( "Last-Modified: " . @date( "D, d M Y H:i:s", $sys['time_now_gmt_unix'] ) . " GMT" );
		}
	}
} // end of dictionary pages

// Language settings, from Translation Kit
$oTpl->addVal( 'v:language', $oL->languagelist( "0" ) );
$oTpl->addVal( 'v:text_direction', $oL->languagelist( "1" ) );
$oTpl->addVal( 'v:charset', $oL->languagelist( "2" ) );

// 11 december 2002, r-t-l with l-t-r
$sys['css_align_right'] = 'right';
$sys['css_align_left'] = 'left';
$sys['css_dir_numbers'] = 'rtl';
if ( $oL->languagelist( '1' ) == 'rtl' )
{
	$sys['css_dir_numbers'] = 'ltr';
	$sys['css_align_right'] = 'left';
	$sys['css_align_left'] = 'right';
}
$oTpl->addVal( 'v:css_align_right', $sys['css_align_right'] );
$oTpl->addVal( 'v:css_align_left', $sys['css_align_left'] );

$oTpl->addVal( 'url:site_name', $oHtml->a( $sys['page_index'], strip_tags( $sys['site_name'] ) ) );

/* Append URL for integration */
$tmp['input_url_append'] = '';
for ( reset( $sys['ar_url_append'] ); list($k, $v) = each( $sys['ar_url_append'] ); )
{
	$tmp['input_url_append'] .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
}
$oTpl->addVal( 'v:input_url_append', $tmp['input_url_append'] );

/* Load addons */
$sys['path_component'] = $sys['path_addon'] . '/' . $gw_this['vars'][GW_TARGET] . '/' . $gw_this['vars'][GW_TARGET] . '.php';

file_exists( $sys['path_component'] ) ? include_once($sys['path_component'] ) : '';

/* */
switch ( $gw_this['vars']['layout'] )
{
	case 'browse': /* Browse (something) */
		{

		}
		break;
	case 'empty': /* Empty page */
		{
			if ( !isset( $gw_this['ar_breadcrumb'][0] ) )
			{
				$gw_this['ar_breadcrumb'][0] = '';
			}
		}
		break;
	case GW_A_CONTENTS: /* Dictionary contents, all terms A-Z */
		{
			$oTpl->addVal( 'v:az', '' );
			$oTpl->addVal( 'l:pages', '&#160;' );
			$oTpl->addVal( 'v:nav_pages', '' );

			$oTpl->addVal( 'block:page_content', gw_get_dict_terms( $arDictParam['tablename'], $arDictParam['uri'] ) );
			/* current section */
			$gw_this['ar_breadcrumb'][0] = $oHtml->a( $sys['page_index'] .
							'?' . GW_ACTION . '=index' .
							'&' . GW_ID_DICT . '=' . $arDictParam['uri'],
							strip_tags( $arDictParam['title'] )
			);
			$gw_this['ar_breadcrumb'][1] = $oL->m( '1058' );
			$gw_this['arTitle'][] = $arDictParam['title'];
			$gw_this['arTitle'][] = $oL->m( '1058' );
			$gw_this['id_tpl_page'] = GW_TPL_CUSTOM_PAGE;
		}
		break;
	case 'index': /* Dictionary title page */
		{
			$oTpl->addVal( 'v:terms_recent', getTop10( 'TERM_NEWEST', $arDictParam['recent_terms_number'], 0, $arDictParam['recent_terms_sorting'], $arDictParam['recent_terms_display'] ) );

			$oTpl->addVal( 'v:dict_date_modified', sprintf( '<span class="gray">%s:</span> %s', $oL->m( 'date_modif' ), date_extract_int( $arDictParam['date_modified'], "%d %F %Y" ) ) );
			$oTpl->addVal( 'l:pages', $oL->m( 'L_pages' ) );
			$gw_this['vars']['p'] = 0;
			$oTpl->addVal( 'v:nav_pages', getNavToolbar( $intSumPages, $gw_this['vars']['p'], $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_LIST . '&d=' . $arDictParam['uri'] . '&p=' ) );
			$gw_this['href_add_a_term'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=1&d=' . $arDictParam['uri'] . '&uid=newterm' );
			$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_add_a_term'] . "';return false" );
			$gw_this['url_add_a_term'] = $oHtml->a( 'javascript:void(0)', $oL->m( '1095' ) );
			$oHtml->setTag( 'a', 'onclick', '' );
			$oTpl->addVal( 'url:add_term', $gw_this['url_add_a_term'] );
			$gw_this['arTitle'][] = $arDictParam['title'];
			$gw_this['id_tpl_page'] = GW_TPL_DICT;
		}
		break;
	case GW_A_LIST:
		{
			/* Build the list of terms for preview */
			$gw_this['ar_breadcrumb'][0] = $oHtml->a( $sys['page_index'] .
							'?' . GW_ACTION . '=index' .
							'&' . GW_ID_DICT . '=' . $arDictParam['uri'],
							strip_tags( $arDictParam['title'] )
			);

			if ( ($gw_this['vars']['w1'] != '') && ($gw_this['vars']['w2'] == '') )
			{
				/* 0-Z */
				$listA = getDictWordList( $gw_this['vars']['w1'], $gw_this['vars']['w2'], '', $arDictParam['id'], $gw_this['vars']['p'], 1, $arDictParam['is_show_full'] );
				$gw_this['arTitle'][] = $arDictParam['title'];
				$gw_this['arTitle'][] = sprintf( $oL->m( '1281' ), $gw_this['vars']['w1'] );
				$gw_this['ar_breadcrumb'][] = $gw_this['vars']['w1'];
			}
			elseif ( ($gw_this['vars']['w1'] != '') && ($gw_this['vars']['w2'] != '') && ($gw_this['vars']['w3'] != '') )
			{
				/* 000-ZZZ */
				$listA = getDictWordList( $gw_this['vars']['w1'], $gw_this['vars']['w2'], $gw_this['vars']['w3'], $gw_this['vars'][GW_ID_DICT], $gw_this['vars']['p'], 1, $arDictParam['is_show_full'] );
				$gw_this['arTitle'][] = $arDictParam['title'];
				$gw_this['arTitle'][] = sprintf( $oL->m( '1281' ), $gw_this['vars']['w3'] );
				$gw_this['ar_breadcrumb'][] = $oHtml->a( $sys['page_index'] .
								'?' . GW_ACTION . '=' . GW_A_LIST .
								'&' . GW_ID_DICT . '=' . $arDictParam['uri'] .
								'&' . GW_TARGET . '=' . GW_T_DICT .
								'&w1=' . urlencode( $gw_this['vars']['w1'] ),
								$gw_this['vars']['w1'] );
				$gw_this['ar_breadcrumb'][] = $gw_this['vars']['w3'];
			}
			elseif ( ($gw_this['vars']['w1'] != '') && ($gw_this['vars']['w2'] != '') )
			{
				/* 00-ZZ */
				$listA = getDictWordList( $gw_this['vars']['w1'], $gw_this['vars']['w2'], '', $gw_this['vars'][GW_ID_DICT], $gw_this['vars']['p'], 1, $arDictParam['is_show_full'] );
				$gw_this['arTitle'][] = $arDictParam['title'];
				$gw_this['arTitle'][] = sprintf( $oL->m( '1281' ), $gw_this['vars']['w2'] );
				$gw_this['ar_breadcrumb'][] = $oHtml->a( $sys['page_index'] .
								'?' . GW_ACTION . '=' . GW_A_LIST .
								'&' . GW_ID_DICT . '=' . $arDictParam['uri'] .
								'&' . GW_TARGET . '=' . GW_T_DICT .
								'&w1=' . urlencode( $gw_this['vars']['w1'] ),
								$gw_this['vars']['w1'] );
				$gw_this['ar_breadcrumb'][] = $gw_this['vars']['w2'];
			}
			else
			{
				$gw_this['arTitle'][] = sprintf( $oL->m( '1282' ), $arDictParam['title'] );
				/* By page */
				$listA = getDictWordList( $gw_this['vars']['w1'], $gw_this['vars']['w1'], $gw_this['vars']['w3'], $gw_this['vars'][GW_ID_DICT], $gw_this['vars']['p'], 1, $arDictParam['is_show_full'] );
				if ( $p > 1 )
				{
					$gw_this['arTitle'][] = sprintf( $oL->m( 'str_page_of_page' ), $oFunc->number_format( $gw_this['vars']['p'], 0, $oL->languagelist( '4' ) ), $oFunc->number_format( $listA[3], 0, $oL->languagelist( '4' ) ) );
					$gw_this['ar_breadcrumb'][] = $oHtml->a( $sys['page_index'] .
									'?' . GW_ACTION . '=' . GW_A_LIST .
									'&' . GW_ID_DICT . '=' . $arDictParam['uri'] .
									'&' . GW_TARGET . '=' . GW_T_DICT .
									'&p=' . $gw_this['vars']['p'],
									$gw_this['vars']['p'] );
				}
			}
			$sys['total'] = $listA[1];
			$intSumPages = $listA[3];

			// checking page numbers
			if ( ( $gw_this['vars']['p'] < 1 ) || ($gw_this['vars']['p'] > $intSumPages) )
			{
				$gw_this['vars']['p'] = 1;
			}
			if ( $arDictParam['is_show_az'] ) // show or hide letters toolbar
			{
				/* $w1 already selected */
				$oTpl->addVal( 'v:aazz', getLetterHtml( $ar0z, $arDictParam['uri'], $gw_this['vars']['w1'], $gw_this['vars']['w2'] . ' ' ) );
				/* 3rd toolbar */
				if ( $gw_this['vars']['w2'] != '' )
				{
					$oTpl->addVal( 'v:aaazzz', getLetterHtml( $ar0z, $arDictParam['uri'], $gw_this['vars']['w1'], $gw_this['vars']['w2'] . ' ', $gw_this['vars']['w3'] . ' ' ) );
				}
			}
			if ( $intSumPages > 1 )
			{
				$oTpl->addVal( 'v:nav_pages', getNavToolbar( $intSumPages, $gw_this['vars']['p'], $sys['page_index'] . '?' . GW_ACTION . '=' . $gw_this['vars']['layout'] . '&strict=' . $strict . '&d=' . $gw_this['vars'][GW_ID_DICT] . '&w1=' . urlencode( $w1 ) . '&w2=' . urlencode( $w2 ) . '&w3=' . urlencode( $w3 ) . '&p=' ) );
			}
			$gw_this['id_tpl_page'] = GW_TPL_TERM_LIST;
		}
		break;
	case GW_T_TERM:
		{
		
			/* Term selected */
			$oTpl->addVal( 'v:nav_pages', '' );

			if ( $arDictParam['recent_terms_sorting'] )
			{
				$oTpl->addVal( 'v:terms_recent', getTop10( 'TERM_NEWEST', $arDictParam['recent_terms_number'], 0, $arDictParam['recent_terms_sorting'] ) );
			}

			$oTpl->addVal( 'v:path_img_dict', $sys['path_img'] . '/' . sprintf( '%05d', $gw_this['vars'][GW_ID_DICT] ) );
			$oTpl->addVal( 'l:term_url', $oL->m( 'page_link_term' ) );
			$oTpl->addVal( 'l:dict_url', $oL->m( 'page_link_dict' ) );
			$arTermActions = array ( );

			/* Get term parameters */
			$listA = getTermParam( $gw_this['vars']['t'], $gw_this['vars']['q'] );

			/* Redirect to dictionary title page if here is no definition found for specified Term ID */
			if ( !isset( $listA['defn'] ) )
			{
				gwtk_header( $sys['server_proto'] . $sys['server_host'] . $sys['page_index'] . '?a=list&d=' . $gw_this['vars'][GW_ID_DICT], $sys['is_delay_redirect'], __FILE__, __LINE__ );
			}
			/* Redirect to new URL. This fixes links from old Glossword versions,
			  where "See also" links were presented as `a=term&d=1&q=Term` */
			if ( $gw_this['vars']['q'] )
			{
				gwtk_header( $sys['server_proto'] . $sys['server_host'] . $oHtml->url_normalize( $sys['page_index'] . '?a=' . GW_T_TERM . '&d=' . $arDictParam['uri'] . '&t=' . $listA['uri'] ), $sys['is_delay_redirect'], __FILE__, __LINE__ );
			}
			/* State: Term parameters were taken */

			$gw_this['vars']['id_term'] = $listA['tid'];
			/* 25 may 2002: ?a=term&d=55&t=1 */
			$gw_this['term_linked'] = strip_tags( $listA['term'] );
			$gw_this['term_linked'] = str_replace( '/', ' ', $gw_this['term_linked'] );
			$gw_this['term_linked'] = preg_replace( "/ {2,}/", ' ', $gw_this['term_linked'] );
			$gw_this['term_linked'] = urlencode( $gw_this['term_linked'] );
			$oTpl->addVal( 'url:term_url',
					$oHtml->a(
							$sys['page_index'] . '?a=term&d=' . $arDictParam['id'] . '&t=' . $listA['tid'],
							$sys['server_proto'] . $sys['server_host'] . $oHtml->url_normalize( $sys['page_index'] . '?a=' . GW_T_TERM . '&d=' . $arDictParam['uri'] . '&t=' . $listA['tid'] )
					)
			);
			$oTpl->addVal( 'url:dict_url',
					$oHtml->a(
							$sys['page_index'] . '?a=list&d=' . $arDictParam['uri'],
							$sys['server_proto'] . $sys['server_host'] . $oHtml->url_normalize( $sys['page_index'] . '?a=list&d=' . $arDictParam['uri'] )
					)
			);
			// -------------------------------------------------
			// Process automatic functions
			if ( !empty( $gw_this['vars']['funcnames'][GW_T_TERM] ) )
			{
				for (; list($k, $v) = each( $gw_this['vars']['funcnames'][GW_T_TERM] ); )
				{
					if ( function_exists( $v ) )
					{
						$v();
					}
				}
			}
			// -------------------------------------------------
			$tmp['cssTrClass'] = 'xt';
			$tmp['xref'] = $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_SEARCH . '&amp;srch[adv]=phrase&amp;d=' . $arDictParam['id'] . '&amp;srch[by]=d&amp;srch[in]=1&amp;q=';
			$tmp['href_srch_term'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_SEARCH . '&srch[in]=1&d=%d&q=%s&strict=1' );
			$tmp['href_link_term'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=term&d=%d&q=%s' );
			$arDictParam['lang'] = $gw_this['vars'][GW_LANG_I] . '-' . $gw_this['vars']['lang_enc'];
			//
			// Render HTML page, 25 apr 2003
			//
			$arPre = gw_Xml2Array( $listA['defn'] );
			$tmp['term'] = $listA['term'];
			$tmp['t1'] = $listA['term_1'];
			$tmp['t2'] = $listA['term_2'];
			$tmp['t3'] = $listA['term_3'];
			$tmp['tid'] = $listA['tid'];
			$tmp['date_created'] = $listA['date_created'];
			$tmp['date_modified'] = $listA['date_modified'];
			//
			$objDom = new gw_domxml;
			$objDom->setCustomArray( $arPre );
			$oRender = new $gw_this['vars']['class_render'];
			$oRender->Set( 'Gtmp', $tmp );
			$oRender->Set( 'objDom', $objDom );
			$oRender->Set( 'arDictParam', $arDictParam );
			$oRender->Set( 'arEl', $arPre );
			$oRender->Set( 'arFields', $arFields );
			$oRender->Set( 'ar_theme', $ar_theme );
			//
			$tmp['str_defn'] = $oRender->array_to_html( $arPre );
			/* Process text filters */
			while ( !$sys['is_debug_output']
			&& is_array( $sys['filters_defn'] )
			&& list($k, $v) = each( $sys['filters_defn'] ) )
			{
				$tmp['str_defn'] = $v( $tmp['str_defn'] );
			}
			// -------------------------------------------------
			/* $tag_stress_rule */
			$ar_pairs_src = explode( "|", $oRender->tag_stress_rule );
			$listA['term'] = str_replace( '<stress>', $ar_pairs_src[0], $listA['term'] );
			$listA['term'] = str_replace( '</stress>', $ar_pairs_src[1], $listA['term'] );

			$str_incomplete = $listA['is_complete'] ? '' : '?&#160;';
			// --------------------------------------------------
			$oTpl->addVal( 'v:term', $str_incomplete . $listA['term'] );
			$oTpl->addVal( 'v:id_term', $listA['tid'] );
			$oTpl->varXref = $sys['page_index'] . '?' . GW_ACTION . '=term&amp;' . GW_ID_DICT . '=' . $arDictParam['id'] . '&amp;q=';
			$oTpl->layout = $gw_this['vars']['layout'];
			$oTpl->addVal( 'l:pages', '&#160;' );
			//
			// linked term does not exist
			if ( $listA['term'] == '' )
			{
				$oTpl->addVal( 'v:aazz', '' );

				if ( $q == '' )
				{
					$oTpl->addVal( 'v:defn',
							'<span class="xr">' . $oL->m( 'reason_26' ) . '</span>'
					);
				}
				else
				{
					$oTpl->addVal( 'v:defn',
							'<span class="term">' . htmlspecialchars( $q ) . '</span><br />' .
							'<span class="xr">' . $oHtml->a( '13', $oL->m( 'reason_26' ) ) . '</span>'
					);
				}
#			$oTpl->addVal( 'v:defn', '<span class="term">'. htmlspecialchars($q) .'</span><br /><span class="xr">' .
			}
			$gw_this['ar_breadcrumb'][0] = $oHtml->a( $sys['page_index'] .
							'?' . GW_ACTION . '=index' .
							'&' . GW_ID_DICT . '=' . $arDictParam['uri'],
							strip_tags( $arDictParam['title'] )
			);
			if ( trim( $listA['term_1'] ) != '' )
			{
				$gw_this['ar_breadcrumb'][] = $oHtml->a( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_LIST .
								'&' . GW_ID_DICT . '=' . $arDictParam['uri'] . '&' . GW_TARGET . '=' . GW_T_DICT .
								'&w1=' . urlencode( $listA['term_1'] ),
								$listA['term_1'] );
			}
			/*
			  $gw_this['ar_breadcrumb'][] = $oHtml->a($sys['page_index'].'?'.GW_ACTION.'='.GW_A_LIST.
			  '&'.GW_ID_DICT.'='.$gw_this['vars'][GW_ID_DICT].'&'.GW_TARGET.'='.GW_T_DICT.
			  '&w1='.urlencode($listA['term_1']).
			  '&w2='.urlencode($listA['term_2']),
			  $listA['term_2']);
			 */
			if ( trim( $listA['term'] ) != '' )
			{
				$gw_this['ar_breadcrumb'][] = strip_tags( $listA['term'] );
			}
			/* */
			if ( $gw_this['vars']['is_print'] ) // HTML-code modifications for printable version
			{
				$oTpl->addVal( 'l:print_version', $oL->m( 'printversion' ) );
				$oTpl->addVal( 'l:1379', $oL->m( '1379' ) );
				$tmp['str_defn'] = preg_replace( "/<ol(.*?[^>])>/", "<br />", $tmp['str_defn'] );
				$tmp['str_defn'] = str_replace( "</ol>", " ", $tmp['str_defn'] );
				$tmp['str_defn'] = preg_replace( "/<ul(.*[^>])>/", "<br />", $tmp['str_defn'] );
				$tmp['str_defn'] = str_replace( "</ul>", " ", $tmp['str_defn'] );
				$tmp['str_defn'] = str_replace( "<li>", "&#160;&#8226;", $tmp['str_defn'] );
				$tmp['str_defn'] = str_replace( "</li>", "<br />", $tmp['str_defn'] );
				$tmp['str_defn'] = str_replace( '&#x25BA;', ' ', $tmp['str_defn'] );

#			$oTpl->addVal( 'url:dict_url', '<span style="text-decoration:underline">'.$sys['server_proto'].$sys['server_host'].$oHtml->url_normalize($sys['page_index']. '?a=list&d='.$arDictParam['id']).'</span>' );
#			$oTpl->addVal( 'url:term_url', '<span style="text-decoration:underline">'.$sys['server_proto'] . $sys['server_host'] . $oHtml->url_normalize($sys['page_index']. '?a='.GW_T_TERM.'&d='.$arDictParam['id'] . '&t='.$listA['tid']).'</span>' );
				$oTpl->addVal( 'url:dict_name', $arDictParam['title'] );

				/* */
				$gw_this['id_tpl_page'] = GW_TPL_TERM_PRINT;
			}
			else
			{
				if ( $arDictParam['is_show_az'] )
				{
					/* show or hide letters toolbar */
					$oTpl->addVal( 'v:az', getLetterHtml( $ar0z, $arDictParam['uri'], $listA['term_1'] ) );
					$oTpl->addVal( 'v:aazz', getLetterHtml( $ar0z, $arDictParam['uri'], $listA['term_1'], $listA['term_2'] . ' ' ) );
					$oTpl->addVal( 'v:aaazzz', getLetterHtml( $ar0z, $arDictParam['uri'], $listA['term_1'], $listA['term_2'] . ' ', $listA['term_3'] ) );
				}
				/* Not on use */
				if ( is_object( $oSess ) && $oSess->is( 'is-terms', $arDictParam['id'] ) )
				{
					/* Direct link to edit a term */
					$oHtml->unsetTag( 'a' );
					$gw_this['href_edit'] = $oHtml->a(
									$sys['page_admin'] . '?' . GW_ACTION . '=edit&id=' . $arDictParam['id'] . '&t=terms&tid=' . $listA['tid'],
									$oL->m( '3_edit' )
					);
					$arTermActions[] = $gw_this['href_edit'];
				}
				/* Suggest a term */
				if ( $arDictParam['is_show_term_suggest'] )
				{
					$gw_this['href_term_suggest'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=1&d=' . $arDictParam['uri'] . '&' . GW_TARGET . '=' . $listA['tid'] . '&uid=newterm' );
					$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_term_suggest'] . "';return false" );
					$gw_this['url_term_suggest'] = $oHtml->a( '#', $oL->m( '1095' ), $oL->m( '1375' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_term_suggest'];
				}
				/* Report a bug */
				if ( $arDictParam['is_show_term_report'] )
				{
					$gw_this['href_term_report'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=1&d=' . $arDictParam['uri'] . '&' . GW_TARGET . '=' . $listA['tid'] . '&uid=report' );
					$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_term_report'] . "';return false" );
					$gw_this['url_term_report'] = $oHtml->a( '#', $oL->m( 'bug_report' ), $oL->m( '1376' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_term_report'];
				}
				/* Refresh the page */
				if ( $arDictParam['is_show_page_refresh'] )
				{
					$gw_this['href_page_refresh'] = $oHtml->url_normalize( $sys['server_url'] . '/?' . GW_ACTION . '=' . GW_T_TERM . '&d=' . $arDictParam['uri'] . '&t=' . $listA['tid'] . '&r=' . mt_rand( 0, 999 ) );
					$oHtml->setTag( 'a', 'onclick', "window.location.reload('" . $gw_this['href_page_refresh'] . "');return false" );
					$gw_this['url_page_refresh'] = $oHtml->a( '#', $oL->m( 'page_refresh' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_page_refresh'];
				}
				/* Send the page */
				if ( $arDictParam['is_show_page_send'] )
				{
					$oHtml->setVar( 'is_mod_rewrite', 0 );
					$gw_this['href_page_send'] = $oHtml->url_normalize(
									$sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=1'
									. '&uid=sendpage'
									. '&arPost[title]=' . urlencode( strip_tags( $listA['term'] . ' - ' . $arDictParam['title'] ) )
									. '&arPost[url]=' . urlencode( $sys['server_url'] . '/?a=term&amp;d=' . $arDictParam['id'] . '&amp;t=' . $listA['tid'] )
					);
					$oHtml->setVar( 'is_mod_rewrite', $sys['is_mod_rewrite'] );
					$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_page_send'] . "';return false" );
					$gw_this['url_page_send'] = $oHtml->a( '#', $oL->m( '1275' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_page_send'];
				}
				/* Add to favorites */
				if ( $arDictParam['is_show_add_to_favorites'] )
				{
					$gw_str['javascripts'] .= '
				function gw_addBookmark(url, title)
				{
					if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function"))
					{
						window.sidebar.addPanel(gw_site_name + " - " +  title, url, "");
					}
					else if (typeof window.external == "object")
					{
						window.external.AddFavorite(url, gw_site_name + " - " +  title);
					}
					else
					{
						alert("CTRL+D");
					}
				}
				';
					$gw_this['href_add_to_favorites'] = $sys['server_url'] . '?a=' . GW_A_SEARCH . '&amp;d=' . $arDictParam['uri'] . '&amp;q=' . $gw_this['term_linked'];
					$oHtml->setTag( 'a', 'onclick', 'return gw_addBookmark(\'' . strip_tags( $gw_this['href_add_to_favorites'] ) . "','" . addslashes( strip_tags( $listA['term'] . ' - ' . $arDictParam['title'] ) ) . "')" );
					$oHtml->setTag( 'a', 'title', addslashes( strip_tags( $sys['site_name'] . ' - ' . $listA['term'] . ' - ' . $arDictParam['title'] ) ) );
					#$oHtml->setTag('a', 'rel', 'sidebar');
					$gw_this['url_add_to_favorites'] = $oHtml->a( '#', $oL->m( 'page_fav' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$oHtml->setTag( 'a', 'title', '' );
					#$oHtml->setTag('a', 'rel', '');
					$arTermActions[] = $gw_this['url_add_to_favorites'];
				}
				/* Add to search */
				if ( $arDictParam['is_show_add_to_search'] )
				{
					$gw_str['javascripts'] .= '
				function gw_installSearchEngine() {
					if (window.external && ("AddSearchProvider" in window.external)) {
						window.external.AddSearchProvider(gw_server_url + "/" + gw_path_temp + "/opensearch.xml");
					}
					else {
						alert("The browser does not support OpenSearch.");
					}
					return false;
				}
				';
					$oHtml->setTag( 'a', 'onclick', "return gw_installSearchEngine()" );
					$gw_this['url_show_add_to_search'] = $oHtml->a( '#', $oL->m( '1271' ), $oL->m( '1377' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_show_add_to_search'];
				}
				/* Print version */
				if ( $arDictParam['is_show_printversion'] )
				{
					$gw_this['href_show_printversion'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_PRINT . '&d=' . $arDictParam['uri'] . '&t=' . $listA['uri'] );
					$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_show_printversion'] . "';return false" );
					$gw_this['url_show_printversion'] = $oHtml->a( '#', $oL->m( 'printversion' ), $oL->m( '1378' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$arTermActions[] = $gw_this['url_show_printversion'];
				}
				$oTpl->addVal( 'v:term_actions', implode( $ar_theme['split_pagenumbers'], $arTermActions ) );
				$gw_this['id_tpl_page'] = GW_TPL_TERM;
			}
			$oTpl->addVal( 'v:defn', $tmp['str_defn'] );
			$gw_this['arTitle'][] = $listA['term'];
			$gw_this['arTitle'][] = $arDictParam['title'];
		}
		break;
	case GW_A_SEARCH: /* Search mode */
		{
			/* 8 Oct 2010: List terms on empty search query instead of displayed "Terms not found" page. */
			if ( $gw_this['vars']['q'] == '' && $gw_this['vars']['d'] > 0 )
			{
				$gw_this['vars']['q'] = '*?';
			}

			$str_dict_name = $oL->m( '2_page__srch' );
			$gw_this['vars']['q'] = trim( $oFunc->mb_substr( $gw_this['vars']['q'], 0, 255 ) );
			/* */
			$gw_this['arSrchResults'] = array ( );
			$gw_this['arSrchResults']['id_d'] = 0;
			$gw_this['arSrchResults']['hits'] = 0;
			$gw_this['arSrchResults']['found'] = 0;
			$gw_this['arSrchResults']['found_total'] = 0;
			$gw_this['arSrchResults']['q'] = $gw_this['vars']['q'];
			if ( $gw_this['vars']['id_srch'] == '' )
			{
				/* 1st search query */
				gw_search( $gw_this['vars']['q'], $gw_this['arDictListSrch'], $gw_this['vars']['srch'] );
				#	return;
			}
			else
			{
				/* List search results */
				$gw_this['arSrchResults'] = gw_search_results( $id_srch, $gw_this['vars']['p'], $gw_this['vars'][GW_ID_DICT] );
				if ( isset( $gw_this['arSrchResults']['cur_id_dict'] ) && $gw_this['arSrchResults']['cur_id_dict'] )
				{
					$gw_this['ar_breadcrumb'][0] = $oHtml->a( $sys['page_index'] .
									'?' . GW_ACTION . '=index' .
									'&' . GW_ID_DICT . '=' . $gw_this['arSrchResults']['cur_id_dict'],
									$gw_this['arSrchResults']['cur_dict_title']
					);
					/* on first dictionary in the list of dictionaries */
					if ( !isset( $arDictParam['id'] ) )
					{
						#$arDictParam = getDictParam($gw_this['arSrchResults']['cur_id_dict']);
					}
				}
				/* */
				$gw_this['ar_breadcrumb'][] = $oL->m( '2_page__srch' );
				$gw_this['ar_breadcrumb'][] = htmlspecialchars( $gw_this['arSrchResults']['q'] );
			}

			/* nothing was found */
			if ( $gw_this['arSrchResults']['found'] == 0 )
			{
				$gw_this['ar_breadcrumb'][0] = $oL->m( '2_page__srch' );
				$oTpl->addVal( 'l:reason_5', $oL->m( 'reason_5' ) );
				if ( isset( $gw_this['arSrchResults']['id_d'] ) && $gw_this['arSrchResults']['id_d'] )
				{
					$oTpl->addVal( 'l:srch_trydefn', sprintf(
									$oL->m( 'srch_trydefn' ),
									$oHtml->url_normalize( ($sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_SEARCH . '&d=' . $arDictParam['id'] . '&q=' . urlencode( $gw_this['arSrchResults']['q'] ) . '&in=defn' ) ),
									$oHtml->url_normalize( ($sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_SEARCH . '&q=' . urlencode( $gw_this['arSrchResults']['q'] ) ) ) )
					);
				}
				$oTpl->addVal( 'l:1075', $oL->m( '1075' ) );
				
				/* Suggest a term */
				if ( isset( $arDictParam['is_show_term_suggest'] ) && $arDictParam['is_show_term_suggest'] ) {
					$gw_this['href_term_suggest'] = $oHtml->url_normalize( $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=1&d=' . $arDictParam['uri'] . '&q=' . urlencode( $gw_this['arSrchResults']['q'] )  . '&uid=newterm' );
					$oHtml->setTag( 'a', 'onclick', "self.location='" . $gw_this['href_term_suggest'] . "';return false" );
					$gw_this['url_term_suggest'] = $oHtml->a( '#', $oL->m( '1095' ), $oL->m( '1375' ) );
					$oHtml->setTag( 'a', 'onclick', '' );
					$oTpl->addVal( 'v:term_suggest', $gw_this['url_term_suggest'] );
				}
				
			}
			
			$oTpl->addVal( 'l:search_time', $oL->m( 'srch_6' ) );
			$oTpl->addVal( 'l:search_matches', $oL->m( 'srch_matches' ) );
			$oTpl->addVal( 'l:search_phrase', $oL->m( 'srch_phrase' ) );
			$oTpl->addVal( 'l:search_total', $oL->m( 'srch_5' ) );
			$oTpl->addVal( 'l:found', $oL->m( 'srch_3' ) );
			$oTpl->addVal( 'l:search_found_dict', $oL->m( 'srch_2' ) );
			$oTpl->addVal( 'v:q', htmlspecialchars( $gw_this['arSrchResults']['q'] ) );
			$oTpl->addVal( 'v:found', $gw_this['arSrchResults']['found'] );
			$oTpl->addVal( 'v:found_total', $gw_this['arSrchResults']['found_total'] );
			$oTpl->addVal( 'v:requests', $gw_this['arSrchResults']['hits'] );
			$intSumPages = ceil( $gw_this['arSrchResults']['found'] / (isset( $arDictParam['page_limit_search'] ) ? $arDictParam['page_limit_search'] : $sys['page_limit_search'] ) );
			// fix page number
			if ( ( $gw_this['vars']['p'] < 1 ) || ( $gw_this['vars']['p'] > $intSumPages) )
			{
				$gw_this['vars']['p'] = 1;
			}
			if ( $intSumPages > 1 ) // enable page navigation
			{
				$oTpl->addVal( 'l:pages', $oL->m( 'L_pages' ) . ':' );
				$oTpl->addVal( 'v:nav_pages',
						getNavToolbar( $intSumPages, $gw_this['vars']['p'], $sys['page_index'] . '?' . GW_ACTION . '=' . $gw_this['vars'][GW_ACTION] . '&id_srch=' . $id_srch . '&d=' . $arDictParam['id'] . '&visualtheme=' . $gw_this['vars']['visualtheme'] . '&p=' )
				);
			}
			/* */
			if ( isset( $arDictParam['title'] ) )
			{
				$str_dict_name .= ' ' . $oHtml->a( $sys['page_index'] . '?a=index&d=' . $arDictParam['uri'], $arDictParam['title'] );
			}
			$oTpl->addVal( 'url:dict_name', $str_dict_name );

			$gw_this['arTitle'][] = $gw_this['arSrchResults']['found_total'];
			$gw_this['arTitle'][] = $oL->m( '1144' );
			$gw_this['arTitle'][] = $gw_this['arSrchResults']['q'];
			if ( isset( $arDictParam['title'] ) )
			{
				$gw_this['arTitle'][] = $arDictParam['title'];
			}
			$gw_this['id_tpl_page'] = GW_TPL_SEARCH;
			/* Hightlight Site name selection */
			$oHtml->setTag( 'a', 'class', 'on' );
			$oTpl->addVal( 'url:site_name', $oHtml->a( $sys['page_index'], strip_tags( $sys['site_name'] ) ) );
			$oHtml->setTag( 'a', 'class', '' );
		}
		break;
	case GW_A_CUSTOMPAGE:
		if ( $gw_this['vars'][GW_ID_DICT] )
		{
			$gw_this['arTitle'][0] = strip_tags( $arDictParam['title'] );
		}
		$oTpl->addVal( 'v:az', '' );
		$oTpl->addVal( 'l:pages', '&#160;' );
		$oTpl->addVal( 'v:nav_pages', '' );

		gw_custom_page( $gw_this['vars']['id'] );

		$gw_this['id_tpl_page'] = GW_TPL_CUSTOM_PAGE;
		/* no such page */
		if ( !isset( $gw_this['ar_breadcrumb'][0] ) )
		{
			$gw_this['ar_breadcrumb'][0] = '';
		}
		break;
	case GW_A_PROFILE:
		$gw_this['arTitle'][] = $oL->m( '1107' );
		$gw_this['ar_breadcrumb'][] = $oL->m( '1107' );

		if ( !isset( $oSess ) )
		{
			$oSess = new $sys['class_session'];
			$oSess->oDb = & $oDb;
			$oSess->oL = & $oL;
			$oSess->sys = & $sys;
			$oSess->load_settings();
		}
		$ar_user = $oSess->user_load_values( $gw_this['vars']['id'], 'return' );

		$tmp['str_user_contact'] = '';
#		$tmp['str_user_contact'] .= '<h3>'.$oL->m('user_profile').'</h3>';
		$tmp['str_user_contact'] .= '<dl class="profile">';
		$tmp['str_user_contact'] .= '<dt>' . $oL->m( 'contact_name' ) . ':</dt><dd>' . $ar_user['user_fname'] . ' ' . $ar_user['user_sname'] . '</dd>';
		if ( $ar_user['is_show_contact'] )
		{
			$tmp['str_user_contact'] .= '<dd>' . $oFunc->text_mailto( '<a href="mailto:' . $ar_user['user_email'] . '">' . $oL->m( '1105' ) . '</a>' ) . '</dd>';
			if ( $ar_user['user_settings']['location'] != '' )
			{
				$tmp['str_user_contact'] .= '<dt>' . $oL->m( 'user_location' ) . ':</dt><dd>' . $ar_user['location'] . '</dd>';
			}
		}
		$tmp['str_user_contact'] .= '<dt>' . $oL->m( 'date_register' ) . ':</dt><dd>' . date_extract_int( $ar_user['date_reg'], "%d %F %Y" ) . '</dd>';
		$tmp['str_user_contact'] .= '<dt>' . $oL->m( 'date_logged' ) . ':</dt><dd>' . ((($ar_user['date_login'] / 1) != 0) ? date_extract_int( $ar_user['date_login'], "%d %F %Y" ) : '&#160;') . '</dd>';
		$tmp['str_user_contact'] .= '<dt>' . $oL->m( 'termsamount' ) . ':</dt><dd>' . $ar_user['int_items'] . '</dd>';
		$tmp['str_user_contact'] .= '</dl>';
#prn_r( $ar_user );
		$ar_v['avatar_img'] = '';
		if ( $ar_user['user_settings']['is_use_avatar'] )
		{
			$ar_v['avatar_img'] = '<img src="' . $sys['server_dir'] . '/' . $sys['path_temporary'] . '/a/' . $ar_user['user_settings']['avatar_img'] . '" width="' . $ar_user['user_settings']['avatar_img_x'] . '" height="' . $ar_user['user_settings']['avatar_img_y'] . '" alt="" />';
		}
		$oTpl->addVal( 'v:avatar_img', $ar_v['avatar_img'] );
		$oTpl->addVal( 'v:user_name', $ar_user['login'] );
		$oTpl->addVal( 'block:user_contact', $tmp['str_user_contact'] );

		$gw_this['id_tpl_page'] = GW_TPL_PROFILE;
		break;
	default:
		{
			$gw_this['ar_breadcrumb'][] = '';
			/* Title page */
			$oTpl->addVal( 'v:site_descr', $sys['site_desc'] );
			$oTpl->addVal( 'l:legend_updated', sprintf( $oL->m( 'tip015' ), '<span class="red">' . $oL->m( 'mrk_new' ) . '</span>', $sys['time_new'] ) );
			$oTpl->addVal( 'l:legend_new', sprintf( $oL->m( 'tip014' ), '<span class="green">' . $oL->m( 'mrk_upd' ) . '</span>', $sys['time_upd'] ) );
			## ------------------------------------------------
			## Catalog object
			/* "", links per category, X, Y, order by */
			$oTpl->addVal( 'block:catalog', getDictList( '', 99, 1, 99 ) );
			## ------------------------------------------------
			## Statistics
			$arStatCommon = getStat();
			$oTpl->addVal( 'block:stats', gw_html_block_small( $oL->m( 'web_stat' ),
							'<span class="gray">' . (date_extract_int( $arStatCommon['date'], "%d" ) / 1) . date_extract_int( $arStatCommon['date'], (" %FL %Y" ) ) . '</span>'
							. '<br />' . $oL->m( 'stat_dict' ) . ': ' . $oFunc->number_format( $arStatCommon['num'], 0, $oL->languagelist( '4' ) )
							. '<br />' . $oL->m( 'stat_defn' ) . ': ' . $oFunc->number_format( $arStatCommon['sum'], 0, $oL->languagelist( '4' ) ), 'xt' )
			);
			## ------------------------------------------------
			## Last updated dictionaries
			$oTpl->addVal( 'block:dict_updated', gw_html_block_small( $oL->m( 'r_dict_updated' ), getTop10( 'DICT_UPDATED', $sys['max_dict_updated'], 1 ), 0, 0, $sys['css_align_left'] ) );
			$oTpl->addVal( 'block:term_updated', gw_html_block_small( $oL->m( 'r_term_updated' ), getTop10( 'TERM_UPDATED', $sys['max_dict_top'], 1 ), 0, 0, $sys['css_align_left'] ) );
			$gw_this['id_tpl_page'] = GW_TPL_TITLE;
		}
		break;
}

## ------------------------------------------------
## Get random term
/*
  $gw_this['vars']['arTermRandom'] = getTermRandom();
  $gw_this['vars']['arDictParam'] = getDictParam( $gw_this['vars']['arTermRandom']['id'] );
  if (isset($gw_this['vars']['arTermRandom']['defn']))
  {
  $tmp['cssTrClass'] = 't';
  $tmp['xref'] = $sys['page_index'] . '?a=term&amp;d=' . $gw_this['vars']['arTermRandom']['id'] . '&amp;q=';
  // Render html page, 25 apr 2003
  $arPre = gw_Xml2Array($gw_this['vars']['arTermRandom']['defn']);
  $objDom = new gw_domxml;
  $objDom->setCustomArray($arPre);
  $oRender = new gw_render;
  $oRender->Set('Gtmp', $tmp );
  $oRender->Set('margin_top', '-1em' );
  $oRender->Set('objDom', $objDom );
  $oRender->Set('arDictParam', $gw_this['vars']['arDictParam'] );
  $oRender->Set('arEl', $arPre );
  $oRender->Set('arFields', $arFields );
  $oRender->load_abbr_trns();
  $oTpl->addVal( 'block:randomterm', gw_html_block_small('Randomly choosed term',
  '<span class="xr">'.$gw_this['vars']['arTermRandom']['term'].'</span>'.$oRender->array_to_html($arPre) ) );
  $gw_this['vars']['arTermRandom'] = $gw_this['vars']['arDictParam'] = array();
  }
 */
/* Menu with custom pages */
if ( !isset( $ar_theme['str_nav_prepend_a'] ) )
{
	$ar_theme['str_nav_prepend_a'] = '';
}
if ( !isset( $ar_theme['str_nav_append_a'] ) )
{
	$ar_theme['str_nav_append_a'] = '';
}
if ( !isset( $ar_theme['str_nav_class'] ) )
{
	$ar_theme['str_nav_class'] = '';
}

$arNavBarBottom = $arNavBarTop = array ( );

/* Get the list of custom pages */
$arSql = $oDb->sqlRun( $oSqlQ->getQ( 'get-pages-list' ), 'page' );

$arSql2 = array ( );
/* re-arrange the list of pages */
for (; list($k, $arV) = each( $arSql ); )
{
	$arSql2[sprintf( "%05d", $arV['int_sort'] ) . sprintf( "%03d", $arV['id_page'] )][$arV['id_lang']] = $arV;
}
$arSql = array ( );
/* Foreach custom page */
for (; list($k, $arV) = each( $arSql2 ); )
{
	$oHtml->unsetTag( 'a' );
	$cur_id_lang = $gw_this['vars'][GW_LANG_I] . '-' . $gw_this['vars']['lang_enc'];
	if ( isset( $arV[$cur_id_lang] ) )
	{
		$arV = $arV[$cur_id_lang];
	}
	elseif ( isset( $arV[$sys['locale_name']] ) )
	{
		$arV = $arV[$sys['locale_name']];
	}
	else
	{
		$ar_keys = array_keys( $arV );
		$arV = $arV[$ar_keys[0]];
	}
	/* Custom PHP-code */
	@eval( $arV['page_php_2'] );
	$icon = '';
	if ( $arV['page_icon'] )
	{
		$file_icon = $sys['path_temporary'] . '/t/' . $gw_this['vars']['visualtheme'] . '/' . $arV['page_icon'];
		if ( file_exists( $file_icon ) )
		{
			$icon = '<img style="vertical-align:top;margin:1px" src="' . $sys['server_dir'] . '/' . $file_icon . '" ' .
					' width="13" height="13" alt="" />&#160;';
		}
	}
	/* Link mode */
	switch ( $sys['pages_link_mode'] )
	{
		case GW_PAGE_LINK_NAME:
			$str_page_id = urlencode( $arV['page_title'] );
			$str_cur_page = $arV['page_title'];
			break;
		case GW_PAGE_LINK_URI:
			$str_page_id = urlencode( $arV['page_uri'] );
			$str_cur_page = $arV['page_uri'];
			break;
		default:
			$str_page_id = $arV['id_page'];
			$str_cur_page = $arV['id_page'];
			break;
	}
	/* Indicators active/inactive */
	$oHtml->setTag( 'a', 'class', $ar_theme['str_nav_class'] );
	if ( isset( $gw_this['ar_pages'] )
			&& (($gw_this['vars']['id'] == $arV['id_page'])
			|| ($gw_this['vars']['id'] == $str_cur_page)
			|| gw_breadcrumbs_is_in_root( $gw_this['ar_pages'], $gw_this['id_page_int'], $arV['id_page'] )) )
	{
		/* One of a custom page */
		$oHtml->setTag( 'a', 'class', 'on' );
	}
	elseif ( !isset( $gw_this['ar_pages'] )
			&& ($arV['id_page'] == $sys['id_custom_page_on'])
			&& ($gw_this['vars']['layout'] != 'title') )
	{
		/* One of a dictionary page */
		#$oHtml->setTag('a', 'class', 'on');
	}
	$arNavBarTop[] = $icon . $oHtml->a( $sys['page_index'] .
					'?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=' . $str_page_id,
					$ar_theme['str_nav_prepend_a'] . $arV['page_title'] . $ar_theme['str_nav_append_a']
	);
	$oHtml->unsetTag( 'a' );
	$arNavBarBottom[] = $oHtml->a( $sys['page_index'] .
					'?' . GW_ACTION . '=' . GW_A_CUSTOMPAGE . '&id=' . $str_page_id,
					$arV['page_title']
	);
}


$oTpl->addVal( 'v:nav_top', $ar_theme['str_nav_prepend'] . implode( $ar_theme['str_nav_split'], $arNavBarTop ) . $ar_theme['str_nav_append'] );
$oTpl->addVal( 'v:nav_bottom', $ar_theme['str_navb_prepend'] . implode( $ar_theme['str_navb_split'], $arNavBarBottom ) . $ar_theme['str_navb_append'] );
$oTpl->addVal( 'block:current_section', $gw_this['ar_breadcrumb'][0] );
unset( $arSql );
unset( $arSql2 );
## Site navigation
## --------------------------------------------------------

## --------------------------------------------------------
## Keywords section
if ( !is_array( $k ) )
{
	$k = array ( );
}
if ( !isset( $kw ) || !is_array( $kw ) )
{
	$kw = array ( );
}
if ( $w != '' ) // if letter selected
{
	$k[] = $w;
}
if ( !empty( $listA['term'] ) ) // if term selected
{
	$listA['term'] = (strip_tags( $listA['term'] ));
	// meta keywords
	$k[] = $listA['term'];
}
if ( empty( $k ) )
{
	$gw_this['arTitle'][] = $sys['site_name'];
	$k[] = $sys['site_desc'];
	$metaDescr = $sys['site_desc'];
}
else
{
	$metaDescr = $sys['site_name'] . ' - ' . $sys['site_desc'];
}
if ( isset( $arDictParam['keywords'] ) )
{
	if ( !empty( $arDictParam['keywords'] ) )
	{
		$kw[] = $arDictParam['keywords'];
	}
}
if ( !empty( $sys['keywords'] ) )
{
	$kw[] = $sys['keywords'];
}

/* Javascripts */
$gw_str['javascripts'] .= '
var gw_site_name = "' . strip_tags( $sys['site_name'] ) . '";
var gw_site_desc = "' . strip_tags( $sys['site_desc'] ) . '";
var gw_server_url = "' . $sys['server_url'] . '";
var gw_path_temp = "' . $sys['path_temporary'] . '";
gwVT.init();
';
/* */
if ( $arDictParam['id']
		&& (($gw_this['vars'][GW_ACTION] == 'index')
		|| ($gw_this['vars'][GW_ACTION] == GW_A_SEARCH)
		|| ($gw_this['vars'][GW_ACTION] == 'term')
		|| ($gw_this['vars'][GW_ACTION] == GW_A_LIST)
		|| ($gw_this['vars'][GW_ACTION] == 'contents')
		|| ($gw_this['vars'][GW_ACTION] == GW_A_LIST) ) )
{
	/* Create highlighted link to a dictionary */
	$s_dict_title = $s_dict_title_cut = strip_tags( $arDictParam['title'] );
	/* 29 Oct 2010: Cut a dictionary title */
	if ( $oFunc->mb_strlen($s_dict_title_cut) > 30 )
	{
		$s_dict_title_cut = $oFunc->mb_substr( $s_dict_title, 0, 30 ).'…';
	}
	$oHtml->setTag( 'a', 'class', 'on' );
	$oHtml->setTag( 'a', 'title', $s_dict_title );
	$oTpl->addVal( 'url:dict', $oHtml->a( $sys['page_index'] . '?a=index&d=' . urlencode( $gw_this['vars'][GW_ID_DICT] ), $s_dict_title_cut ) );
	$oHtml->setTag( 'a', 'class', '' );
	$oHtml->setTag( 'a', 'title', '' );

	/* Do not highlight the link to main page */
	$oTpl->addVal( 'url:site_name', $oHtml->a( $sys['page_index'], strip_tags( $sys['site_name'] ) ) );

	/* Turn off link to main page when a dictionary is set as main page */
	if ( $arDictParam['is_dict_as_index'] )
	{
		$oTpl->addVal( 'url:site_name', '' );
	}
}

/* Other variables */
$oTpl->addVal( 'meta:robots', '<meta content="' . $sys['meta_robots'] . '" name="robots" />' );
$oTpl->addVal( 'href:home', $sys['server_proto'] . $sys['server_host'] . $sys['page_index'] );
$oTpl->addVal( 'href:to_main_page', $oHtml->url_normalize( $sys['page_index'] ) );
$oTpl->addVal( 'l:1087', $oL->m( '1087' ) );
$oTpl->addVal( 'l:dict_terms', $oL->m( 'termsamount' ) );
$oTpl->addVal( 'l:main_page', $oL->m( '3_tomain' ) );
$oTpl->addVal( 'l:search_strict', $oL->m( 'srch_strict' ) );
$oTpl->addVal( 'l:top_of_page', $oL->m( '3_top' ) );
$oTpl->addVal( 'url:mailto_contact_name', $oFunc->text_mailto( '<a href="mailto:' . $sys['y_email'] . '">' . $sys['y_name'] . '</a>' ) );
$oTpl->addVal( 'v:action_name', GW_ACTION );
$oTpl->addVal( 'v:action_value', $gw_this['vars'][GW_ACTION] );
$oTpl->addVal( 'v:contact_name', $sys['y_name'] );
$oTpl->addVal( 'v:form_action', $sys['page_index'] );
$oTpl->addVal( 'v:id_name', 'id' );
$oTpl->addVal( 'v:id_value', $gw_this['vars']['id'] );
$oTpl->addVal( 'v:javascripts', '<script type="text/javascript">/*<![CDATA[*/' . $gw_str['javascripts'] . '/*]]>*/</script>' );
$oTpl->addVal( 'v:lang_i_name', GW_LANG_I );
$oTpl->addVal( 'v:lang_i_value', $gw_this['vars'][GW_LANG_I] );
$oTpl->addVal( 'v:path_dict', append_url( $sys['page_index'] . '?a=index&' . GW_ID_DICT . '=' . $gw_this['vars'][GW_ID_DICT] ) );
$oTpl->addVal( 'v:server_dir', $sys['server_dir'] );
$oTpl->addVal( 'v:d', $gw_this['vars']['d'] );

/* 28 Sep 2010: The selection of search area {term | defn} */
$oTpl->addVal( 'v:select_term_defn', '<select name="srch[in]" class="input" style="width:8em"><option value="1">'.$oL->m( 'terms' ).'</option><option value="0">'.$oL->m( 'definitions' ).'</option></select>' );

/* 28 Sep 2010: "Exact match" option */
$s_chk_srch_exact = '';
if ( isset( $gw_this['vars']['srch']['adv'] ) && $gw_this['vars']['srch']['adv'] == 'exact' )
{
	$s_chk_srch_exact = ' checked="checked"';
}
#$oTpl->addVal( 'v:exact_match', '<label><input name="srch[adv]" type="checkbox" value="exact"'.$s_chk_srch_exact.'/>'.$oL->m( 'exact' ).'</label>' );

#$oTpl->addVal( 'v:sid',              $gw_this['vars'][GW_SID] );
#$oTpl->addVal( 'v:sid_name',         GW_SID );
$oTpl->addVal( 'v:site_desc', $sys['site_desc'] );
$oTpl->addVal( 'v:site_name', $sys['site_name'] );
/* I request you to retain the copyright notice! */
$oTpl->addVal( 'v:copyright', $sys['str_branding'] );
if ( $sys['str_branding'] )
{
	$oTpl->addVal( 'v:glossword_version', $sys['version'] );
	$oTpl->addVal( 'meta:generator', '<meta content="Glossword version ' . $sys['version'] . '" name="generator"  />' );
}
$oTpl->addVal( 'l:srch_dict', $oL->m( 'srch_dict' ) );
$oTpl->addVal( 'l:srch_topic', $oL->m( 'srch_topic' ) );
$oTpl->AddVal( 'l:select_dict', $oL->m( 'srch_selectdict' ) );
$oTpl->AddVal( 'l:term', $oL->m( 'term' ) );
$oTpl->AddVal( 'l:lang', $oL->m( 'lang' ) );
$oTpl->AddVal( 'l:catalog', $oL->m( 'catalog' ) );
$oTpl->AddVal( 'l:title_search', $oL->m( 'title_search' ) );

/* the list of topics  */
$topic_mode = 'form';
$gw_this['select_topic'] = '<select name="id_topic" class="input" style="width:100%">';
$gw_this['select_topic'] .= ctlgGetTopicsRow( gw_create_tree_topics(), 0, 1 );
$gw_this['select_topic'] .= '</select>';
$oTpl->addVal( 'v:select_topic', $gw_this['select_topic'] );
/* the list of languages */
if ( sizeof( $gw_this['vars']['ar_languages'] ) >= 1 )
{
	$gw_this['select_lang'] = '<div class="box-themes">';
	$gw_this['select_lang'] .= '<form action="' . GW_REQUEST_URI . '" method="post">';
	$gw_this['select_lang'] .= '<table cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td style="width:99%">';
	$gw_this['select_lang'] .= htmlFormsSelect( $gw_this['vars']['ar_languages'], $gw_this['vars'][GW_LANG_I] . '-' . $gw_this['vars']['lang_enc'], GW_LANG_I, 'xt', 'width:100%', $oL->languagelist( '1' ) );
	$gw_this['select_lang'] .= '</td><td style="width:1%"><input id="ok-il" style="width:3em" name="is[save_il]" type="submit" class="submitok" value="' . $oL->m( '1212' ) . '" />';
	$gw_this['select_lang'] .= '</td></tr></tbody></table></form>';
	$gw_this['select_lang'] .= '</div>';
	$oTpl->addVal( 'v:select_lang', $gw_this['select_lang'] );
}

/* Read visual theme names */
$gw_this['ar_themes'] = gw_get_themes_list();
$gw_this['ar_themes_select'] = gw_get_themes_select();
/* add `theme_author' into the curent theme settings */
if ( isset( $gw_this['ar_themes'][$gw_this['vars']['visualtheme']] ) )
{
	$ar_theme = array_merge( $ar_theme, $gw_this['ar_themes'][$gw_this['vars']['visualtheme']] );
}
else
{
	$gw_this['vars']['visualtheme'] = $sys['visualtheme'];
	$oTpl->init( $gw_this['vars']['visualtheme'] );
}
$oTpl->addVal( 'v:visualtheme', str_replace( '_', '-', $gw_this['vars']['visualtheme'] ) );
$oTpl->addVal( 'v:path_css', $sys['server_dir'] . '/' . $sys['path_temporary'] . '/t/' . $gw_this['vars']['visualtheme'] );
$oTpl->addVal( 'v:path_temp', $sys['path_temporary'] );
$oTpl->addVal( 'v:path_tpl', $sys['server_dir'] . '/' . $sys['path_tpl'] . '/common' );


if ( sizeof( $gw_this['ar_themes_select'] ) > 1 )
{
	$gw_this['select_themes'] = '<div class="box-themes">';
	$gw_this['select_themes'] .= '<form action="' . GW_REQUEST_URI . '" method="post">';
	$gw_this['select_themes'] .= '<table cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td style="width:99%">';
	$gw_this['select_themes'] .= htmlFormsSelect( $gw_this['ar_themes_select'], $gw_this['vars']['visualtheme'], 'visualtheme', 'xt', 'width:100%', $oL->languagelist( '1' ) );
	$gw_this['select_themes'] .= '</td><td style="width:1%"><input id="ok-visualtheme" style="width:3em" type="submit" name="is[save_visualtheme]" class="submitok" value="' . $oL->m( '1212' ) . '" />';
	$gw_this['select_themes'] .= '</td></tr></tbody></table></form>';
	$gw_this['select_themes'] .= '</div>';
	$oTpl->addVal( 'v:select_visualtheme', $gw_this['select_themes'] );
}
$oTpl->addVal( 'l:visual_theme', $oL->m( 'visual_theme' ) );

$oTpl->addVal( 'v:select_dict', getDictSrch( '', 1, 99, '', 1, $arDictParam['id'] ) );
$oTpl->addVal( 'v:breadcrumb', implode( $sys['txt_sep_breadcrump'], $gw_this['ar_breadcrumb'] ) );
$oTpl->addVal( 'v:html_title', strip_tags( implode( $sys['txt_sep_htmltitle'], $gw_this['arTitle'] ) ) );
$oTpl->addVal( 'v:meta_keywords', strip_tags( searchkeys( array_merge( $k, $kw ) ) ) );
$oTpl->addVal( 'v:meta_description', trim( strip_tags( $metaDescr ) ) );
## Keywords section
## --------------------------------------------------------

/* Add previously defined template variables */
for ( reset( $arTplVars['srch'] ); list($k, $v) = each( $arTplVars['srch'] ); )
{
	$oTpl->AddVal( $k, $v );
}

$oTpl->set_tpl( $gw_this['id_tpl_page'] );
/* Parse dynamic blocks */
for ( reset( $oTpl->tmp['d'] ); list($id_dynamic, $arV) = each( $oTpl->tmp['d'] ); )
{
	if ( is_array( $arV ) )
	{
		for ( reset( $arV ); list($k2, $v2) = each( $arV ); )
		{
			for ( reset( $v2 ); list($k, $v) = each( $v2 ); )
			{
				$oTpl->assign( array ( $k => $v ) );
			}
			$oTpl->parseDynamic( $id_dynamic );
		}
	}
	else
	{
		$oTpl->parseDynamic( $id_dynamic );
	}
	unset( $oTpl->tmp['d'][$id_dynamic] );
}
/* end of file */
?>