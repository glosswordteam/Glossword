<?php

/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2010 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if ( !defined( 'IN_GW' ) )
{
	die( '<!-- $Id: func.srch.inc.php 84 2007-06-19 13:01:21Z yrtimd $ -->' );
}


/**
 * Load the list of stopwords based on dictionary settings
 *
 * @param    array  $arDictParam Dictionary parameters
 * @return   array  Array with stopwords (if any)
 */
function gw_get_stopwords ( $arDictParam )
{
	global $oL;
	$a_stopwords = array ( );
	if ( !isset( $arDictParam['is_filter_stopwords'] ) )
	{
		return $a_stopwords;
	}
	if ( $arDictParam['is_filter_stopwords'] && is_array( $arDictParam['ar_filter_stopwords'] ) )
	{
		for ( reset( $arDictParam['ar_filter_stopwords'] ); list($locale_id, $vS) = each( $arDictParam['ar_filter_stopwords'] ); )
		{
			$a_stopwords = array_merge( $a_stopwords, array_flip( $oL->getCustom( 'stop_words', $locale_id, 'return' ) ) );
		}
		$a_stopwords = array_keys( $a_stopwords );
	}
	return $a_stopwords;
}


/**
 * Main function for searching terms.
 *
 * @param    string  $q Search query string
 * @param    array   $arDict_Ids Dictionary IDs
 * @param    array   $a_search_params Search parameters
 * @return   array   String term items, int total terms
 */
function gw_search ( $q, $arDict_Ids, $a_search_params )
{
	global $arDictParam, $sys, $gw_this;
	global $oSqlQ, $oDb, $oHtml, $oFunc, $oL;

	/* Limit the number of search results to 1000 */
	$i_max_search_results = 1000;

	$id_term_redirect = 0;
	
#	@header( 'Content-Type: text/html; charset=UTF-8' );

	/* */
	$arSql = array ( );
	$strA = array ( 0 => '', 1 => '' );
	$tmp['arIn'] = $tmp['redirect_url'] = $tmp['arCache'] = $tmp['a_results_temp'] = $tmp['arResults'] = array ( );
	$i = 0;

	/* Collect data for `gw_search_results' table */
	$tmp['arCache']['q'] = $q;
	$tmp['arCache']['found'] = 0;

	/* */
	if ( !is_array( $a_search_params['in'] ) )
	{
		$a_search_params['in'] = array ( $a_search_params['in'] );
	}
	/* Save search parameters */
	$tmp['arCache']['srch_settings']['in'] = implode( ',', $a_search_params['in'] );
	$tmp['arCache']['srch_settings']['srch_adv'] = $a_search_params['adv'];
	$tmp['arCache']['srch_settings']['srch_by'] = $a_search_params['by'];

	/* Enable wildcards when building keywords */
	global $oCase;
	$oCase->set_replace_sp( array ( '*' => '*', '?' => '?' ) );

	/* */
	if ( GW_IS_BROWSE_ADMIN )
	{
		$page_index = $sys['page_admin'];
		$a_keywords = text2keywords( text_normalize( $q ), 1, 25, $sys['internal_encoding'] );
		if ( $q == '' )
		{
			$tmp['arCache']['found_total'] = 0;
			$tmp['arCache']['hits'] = 0;
			$tmp['arCache']['time'] = 0;
			$tmp['arCache']['html'] = $oL->m( 'reason_5' );
			return $tmp['arCache'];
		}
	}
	else
	{
		$page_index = $sys['page_index'];
		if ( isset( $arDictParam['min_srch_length'] ) )
		{
			/* Search in single dictionary */
			$a_keywords = text2keywords( text_normalize( $q ), $arDictParam['min_srch_length'], 25, $sys['internal_encoding'] );
		}
		else
		{
			/* Search in multiple dictionaries */
			$a_keywords = text2keywords( text_normalize( $q ), 1, 25, $sys['internal_encoding'] );
			$arDictParam['min_srch_length'] = 1;
		}
		/* Check for empty queries */
		if ( $oFunc->mb_strlen( str_replace( '*', '', $q ), $sys['internal_encoding'] ) < $arDictParam['min_srch_length'] )
		{
			$strA[0] = $oL->m( 'error' );
			return $strA;
		}
		/* Check for empty list of dictionaries */
		if ( empty( $arDict_Ids ) )
		{
			$strA[0] = $oL->m( 'error' );
			return $strA;
		}
	}

	/* Add visual theme to URL for redirect */
	if ( !isset( $gw_this['cookie']['gw_visualtheme'] ) )
	{
		$tmp['redirect_url'][0] = 'visualtheme=' . $sys['visualtheme'];
	}
	if ( !isset( $gw_this['cookie']['gw_' . GW_LANG_I] ) )
	{
		$tmp['redirect_url'][] = GW_LANG_I . '=' . $gw_this['vars'][GW_LANG_I];
	}

	/* Search in single dictionary */
	$a_stopwords = array ( );
	if ( sizeof( $arDict_Ids ) == 1 )
	{
		$tmp['arCache']['id_d'] = $arDict_Ids[0];
		$tmp['redirect_url'][0] = 'd=' . $arDict_Ids[0];
		/* Get stopwords */
		$a_stopwords = gw_get_stopwords( $arDictParam );
		$a_keywords = gw_array_exclude( $a_keywords, $a_stopwords );
	}
	for ( reset( $arDict_Ids ); list($k, $dictK) = each( $arDict_Ids ); )
	{
		$tmp['arDictParam'][$dictK] = getDictParam( $dictK );
	}
	$a_stopwords = array ( );
	$tmp['intKeywords'] = sizeof( $a_keywords );
	sort( $a_keywords );

	/* Debug */
	#prn_r( $tmp );
	#prn_r( $a_keywords );
	#prn_r( $arDict_Ids );
	#exit;
	// Assign `Unique ID' to query.
	// Already uppercased, without stopwords, without special symbols, alphabetically sorted. Nice.
	$tmp['arCache']['id_srch'] = md5( implode( '', $a_keywords ) . implode( '', $arDict_Ids ) . $tmp['arCache']['srch_settings']['in'] . $a_search_params['adv'] );
	// -----------------------------------------------
	// Check for an existent query, if exists, get results and add hits + 1
	if ( $sys['is_cache_search'] && $a_search_params['adv'] != 'phrase' )
	{
		$arSql = $oDb->sqlExec( $oSqlQ->getQ( 'srch-result-cnt', $tmp['arCache']['id_srch'] ) );
	}
	$tmp['arCache']['hits'] = isset( $arSql[0]['hits'] ) ? $arSql[0]['hits'] : 1;

	/* */
	if ( GW_IS_BROWSE_WEB && !empty( $arSql ) ) /* prev results found, web only */
	{
		$arSql = isset( $arSql[0] ) ? $arSql[0] : array ( );
		// Increase hits, web only
		// Can't place `hits' field into `srch_settings' because hits are used in SQL-requests
		$tmp['arCache']['hits'] = isset( $arSql['hits'] ) ? $arSql['hits'] + 1 : 1;
		$tmp['arCache']['found'] = $arSql['found'];
		/* Update query text to show for user. */
		$oDb->sqlExec( $oSqlQ->getQ( 'upd-srch-q', $tmp['arCache']['hits'], gw_text_sql( $q ), $tmp['arCache']['id_srch'] ) );
	}
	else
	{
		$sql_term_match = '';
		$sql_table_match = '';
		$sql_term_match_all = '';
		/* Search in specified fields */
		if ( !is_array( $a_search_params['in'] ) )
		{
			$a_search_params['in'] = array ( 0 );
		}
		switch ( $a_search_params['in'][0] )
		{
			case -1:
				/* search in terms and definitions, default */

				break;
			case 0:
				/* Search in definitions only */
				$sql_term_match = 'AND m.term_match != "1" AND t.is_active != "3"';
				$sql_term_match_all = 'WHERE t.is_active != "3"';
				break;
			case 100:
				/* Search in unapproved */
#				$sql_table_match = ', `'.$arDictParam['tablename'].'` AS dict';
				$sql_term_match = 'AND m.term_match = "1" AND t.is_active = "0" AND t.id = m.term_id';
				$sql_term_match_all = 'WHERE t.is_active = "0"';
				break;
			case 101:
				/* Search in incomplete */
#				$sql_table_match = ', `'.$arDictParam['tablename'].'` AS dict';
				$sql_term_match = 'AND m.term_match = "1" AND t.id = m.term_id AND t.is_complete = "0"';
				$sql_term_match_all = 'WHERE t.is_complete = "0" AND t.is_active != "3"';
				break;
			case 102:
				/* Search in removed terms */
				/* Allow definitions */
				#$sql_table_match = ', `'.$arDictParam['tablename'].'` AS dict';
				$sql_term_match = 'AND t.id = m.term_id AND t.is_active = "3"';
				$sql_term_match_all = 'WHERE t.is_active = "3"';
				break;
			case 103:
				/* Search in published (approved) terms only */
				#$sql_table_match = ', `'.$arDictParam['tablename'].'` AS dict';
				$sql_term_match = 'AND m.term_match = "1" AND t.id = m.term_id AND t.is_active = "1"';
				$sql_term_match_all = 'WHERE t.is_active = "1"';
				break;
			default:
				/* Search by fields */
				for ( reset( $a_search_params['in'] ); list($inK, $inV) = each( $a_search_params['in'] ); )
				{
					$tmp['arIn'][] = $inV;
				}
				$sql_term_match = 'AND m.term_match IN (' . implode( ', ', $tmp['arIn'] ) . ') AND t.is_active != "3"';
				$sql_term_match_all = 'WHERE t.is_active != "3"';
				break;
		}

#		prn_r( $sql_term_match );
#		prn_r( $sql_term_match_all );
#		prn_r( $tmp );
#		exit;

		/* Start timer */
		$oTm = new gw_timer( 'srch' );

		/* Configure to search for 'all', 'phrase', 'any'; */
		if ( $a_search_params['adv'] == 'all'
				|| $a_search_params['adv'] == 'phrase' )
		{
			/* Allows wildcard search */
			$a_keywords = gw_text_wildcars( $a_keywords, 'sql' );

			/* List all terms from one dictionary, for admin only */
			if ( str_replace( '*', '', $q ) == '' )
			{
				if ( GW_IS_BROWSE_ADMIN )
				{
					$sql = $oSqlQ->getQ( 'get-terms-all-adm', $arDictParam['tablename'], $sql_term_match_all, $sys['max_terms_search'] );
				}
				else
				{
					$sql = $oSqlQ->getQ( 'get-terms-all', $arDictParam['tablename'], $arDict_Ids[0] );
				}
#prn_r( $sql );
#exit;
				$arSql = $oDb->sqlExec( $sql );
				/* Re-organize search results per dictionary . */
				$i_cnt = 0;
				for (; list($sqlK, $sqlV) = each( $arSql ); )
				{
					$tmp['a_results_temp'][$arDictParam['id']][$sqlV['term_id']][] = $sqlV['term_id'];
					unset( $arSql[$i_cnt] );
					$i_cnt++;
				}
				for ( reset( $tmp['a_results_temp'] ); list($dictK, $resultsV) = each( $tmp['a_results_temp'] ); )
				{
					while ( list($rK, $rV) = each( $resultsV ) )
					{
						$tmp['arResults'][$dictK][] = $rK;
						$tmp['arCache']['found']++;
						$i++;
					}
					$tmp['arResults'][$dictK] = implode( ',', $tmp['arResults'][$dictK] );
					unset( $tmp['a_results_temp'][$dictK] );
				}
				unset( $tmp['a_results_temp'] );
			}
			else /* usual search */
			{
				for ( reset( $arDict_Ids ); list($kk, $id_dict) = each( $arDict_Ids ); )
				{
					/* Go for each word */
					for ( reset( $a_keywords ); list($k, $v) = each( $a_keywords ); )
					{
						/* 11 April 2008: Enable like fulltext seach for Chinese, Japanese and Korean characters */
						if ( preg_match( '/[\x{3040}-\x{312F}|\x{3400}-\x{9FFF}|\x{AC00}-\x{D7AF}]/u', $v, $ar_matches ) )
						{
							$v = '%' . $v . '%';
						}
						/* 18 July 2007: Enable auto-asterisks for Chinese characters */
						/* 06 May 2008: Enable auto-asterisks for Japanese and Korean characters */
						if ( $oFunc->mb_strlen( $v, $sys['internal_encoding'] ) == 1 && function_exists( 'mb_encode_numericentity' ) )
						{
							$v_numeric = $v;
							/* Hiragana, Katakana, Bopomofo */
							$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0x3040, 0x312F, 0, 0xFFFF ), 'UTF-8' );
							/* CJK */
							$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0x3400, 0x9FFF, 0, 0xFFFF ), 'UTF-8' );
							/* Hangul */
							$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0xAC00, 0xD7AF, 0, 0xFFFF ), 'UTF-8' );
							$v_numeric = intval( str_replace( '&#', '', str_replace( ';', '', $v_numeric ) ) );
							if ( ($v_numeric >= 0x3040 && $v_numeric <= 0x312F)
									|| ($v_numeric >= 0x3400 && $v_numeric <= 0x9FFF)
									|| ($v_numeric >= 0xAC00 && $v_numeric <= 0xD7AF)
							)
							{
								$v = '%' . $v . '%';
							}
						}
						/* */
						$sql_word_srch = "k.word_text LIKE '" . $v . "'";
						$sql_table_match = ', `' . $tmp['arDictParam'][$id_dict]['tablename'] . '` as t';
						/* 1.8.7: Exclude removed terms */
						$sql_term_match .= ' AND t.is_active != "3" ';

						/* Main search query */
						if ( $a_search_params['adv'] == 'phrase' )
						{
							$sql = $oSqlQ->getQ( 'srch-word-cnt-phrase', $sql_table_match, $id_dict, $sql_term_match, $sql_word_srch, $sys['time_now_db'], $sys['time_now_db'] );
						}
						else
						{
							$sql = $oSqlQ->getQ( 'srch-word-cnt', $sql_table_match, $id_dict, $sql_term_match, $sql_word_srch, $sys['time_now_db'], $sys['time_now_db'] );
						}
						/* Frontend */
#prn_r( $sql );
#exit;
						$arSql = $oDb->sqlExec( $sql );

						/* Re-organize search results per dictionary */
						$i_cnt = 0;

						/* SEF mode */
						switch ( $sys['pages_link_mode'] )
						{
							case GW_PAGE_LINK_NAME:
								$term_field = 'term';
								break;
							case GW_PAGE_LINK_URI:
								$term_field = 'term_uri';
								break;
							default:
								$term_field = 'term_id';
								break;
						}
#						prn_r( $arSql );
						/* $arSql has always at least one item in array */
						while ( $i_cnt < $i_max_search_results && list( $sqlK, $sqlV ) = each( $arSql ) )
						{
							/* Term */
							if ( isset( $sqlV['term'] ) )
							{
#								$id_term_redirect = ($sqlV[$term_field] == '') ? $sqlV['term'] : $sqlV[$term_field];
							}
							else
							{
								/* Search in all dictionaries */
								$id_term_redirect = $sqlV['term_id'];
								$term_field = 'term_id';
							}
							if ( $a_search_params['adv'] == 'phrase' )
							{
								/* We can select keywords from database or create new */
								$a_keywordsTerm = text2keywords( text_normalize( $sqlV['term'] ), 1, 25, $sys['internal_encoding'] );
#								$sql = $oSqlQ->getQ('srch-keyword-by-term',  implode(',', $arDict_Ids), $sql_term_match, $sqlV['term_id'] );
								sort( $a_keywordsTerm );
								if ( implode( '', $a_keywordsTerm ) == implode( '', $a_keywords ) )
								{
									$tmp['a_results_temp'][$sqlV['dict_id']][$sqlV['term_id']][] = $sqlV[$term_field];
									$i_cnt++;
									$id_term_redirect = ($sqlV[$term_field] == '') ? $sqlV['term'] : $sqlV[$term_field];
								}
								else
								{
									continue;
								}
							}
							else
							{
								$tmp['a_results_temp'][$sqlV['dict_id']][$sqlV['term_id']][] = $sqlV[$term_field];
								$i_cnt++;
							}
						}
						$arSql = array ( );
					}
				}
#prn_r( $tmp );
#exit;
				/* Now sort search results */
				for ( reset( $tmp['a_results_temp'] ); list($dictK, $resultsV) = each( $tmp['a_results_temp'] ); )
				{
					/* Get stopwords per dictionary */
					$a_current_dict_params = getDictParam( $dictK );
					$a_stopwords = gw_get_stopwords( $a_current_dict_params );
					$a_keywordsD = gw_array_exclude( $a_keywords, $a_stopwords );
					$tmp['intKeywords'] = sizeof( $a_keywordsD );
					$tmp['arResults'][$dictK] = array ( );
					while ( list($rK, $id_terms) = each( $resultsV ) )
					{
						if ( $a_search_params['adv'] != 'phrase'
								&& sizeof( $id_terms ) != $tmp['intKeywords'] )
						{
							/* not required, but it cleans some memory, I hope */
							unset( $tmp['a_results_temp'][$dictK][$rK] );
						}
						else
						{
							/* collect matched term IDs */
							$tmp['arResults'][$dictK][] = $rK;
							$tmp['arCache']['found']++;
							$i++;
						}
					}
					$tmp['arResults'][$dictK] = implode( ',', $tmp['arResults'][$dictK] );
					if ( $tmp['arResults'][$dictK] == '' )
					{
						unset( $tmp['arResults'][$dictK] );
					}
					#unset($tmp['a_results_temp'][$dictK]);
				}
			}
		}
		else if ( $a_search_params['adv'] == 'exact' )
		{
			/* ----------------------------------------- */
			/* 28 Sep 2010: Exact match */
			/* ----------------------------------------- */
			foreach ( $arDict_Ids as $kk => $id_dict )
			{
				/* Go for each keyword */
				foreach ( $a_keywords as $k => $v )
				{
					/* 11 April 2008: Enable like fulltext seach for Chinese, Japanese and Korean characters */
					if ( preg_match( '/[\x{3040}-\x{312F}|\x{3400}-\x{9FFF}|\x{AC00}-\x{D7AF}]/u', $v, $ar_matches ) )
					{
						$v = '%' . $v . '%';
					}
					/* 18 July 2007: Enable auto-asterisks for Chinese characters */
					/* 06 May 2008: Enable auto-asterisks for Japanese and Korean characters */
					if ( $oFunc->mb_strlen( $v, $sys['internal_encoding'] ) == 1 && function_exists( 'mb_encode_numericentity' ) )
					{
						$v_numeric = $v;
						/* Hiragana, Katakana, Bopomofo */
						$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0x3040, 0x312F, 0, 0xFFFF ), 'UTF-8' );
						/* CJK */
						$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0x3400, 0x9FFF, 0, 0xFFFF ), 'UTF-8' );
						/* Hangul */
						$v_numeric = mb_encode_numericentity( $v_numeric, array ( 0xAC00, 0xD7AF, 0, 0xFFFF ), 'UTF-8' );
						$v_numeric = intval( str_replace( '&#', '', str_replace( ';', '', $v_numeric ) ) );
						if ( ($v_numeric >= 0x3040 && $v_numeric <= 0x312F)
								|| ($v_numeric >= 0x3400 && $v_numeric <= 0x9FFF)
								|| ($v_numeric >= 0xAC00 && $v_numeric <= 0xD7AF)
						)
						{
							$a_keywords[$k] = '%' . $v . '%';
						}
					}
				}

				/* */
				$sql_word_srch = "k.word_text IN ('" . implode( "','", $a_keywords ) . "')";
				$sql_table_match = ', `' . $tmp['arDictParam'][$id_dict]['tablename'] . '` as t';

				/* Main search query */
				$sql = $oSqlQ->getQ( 'srch-word-cnt-phrase', $sql_table_match, $id_dict, $sql_term_match, $sql_word_srch, $sys['time_now_db'], $sys['time_now_db'] );

				/* */
				$arSql = $oDb->sqlExec( $sql );

				/* Re-organize search results per dictionary */
				$i_cnt = 0;

				/* SEF mode */
				switch ( $sys['pages_link_mode'] )
				{
					case GW_PAGE_LINK_NAME:
						$term_field = 'term';
						break;
					case GW_PAGE_LINK_URI:
						$term_field = 'term_uri';
						break;
					default:
						$term_field = 'term_id';
						break;
				}

#				prn_r( $arSql );
#				exit;


				/* Now sort search results */
				foreach ( $tmp['a_results_temp'] as $id_dict_results => $a_results )
				{
					/* Get stopwords per dictionary */
					$a_current_dict_params = getDictParam( $id_dict_results );
					$a_stopwords = gw_get_stopwords( $a_current_dict_params );
					$a_keywords_diff = gw_array_exclude( $a_keywords, $a_stopwords );

					$tmp['intKeywords'] = sizeof( $a_keywords_diff );
					$tmp['arResults'][$id_dict_results] = array ( );

					while ( list( $rK, $id_terms) = each( $a_results ) )
					{
						/* Collect matched Term IDs */
						$tmp['arResults'][$id_dict_results][] = $rK;
						$tmp['arCache']['found']++;
						$i++;
					}
					/* */
					$tmp['arResults'][$id_dict_results] = implode( ',', $tmp['arResults'][$id_dict_results] );

					if ( $tmp['arResults'][$id_dict_results] == '' )
					{
						unset( $tmp['arResults'][$id_dict_results] );
					}
				}
			}
		}

#		prn_r( $sql );
#		prn_r( $tmp );
#		exit;

		/* Collect data for search log */
		$q_search['id_field'] = $tmp['arCache']['srch_settings']['in'];

		/* Search datetime */
		$tmp['arCache']['srch_date'] = $sys['time_now_gmt_unix'];

		/* Save collected Term IDs */
		$tmp['arCache']['srch_settings']['results'] = & $tmp['arResults'];

		/* Save real search time (SQL + PHP) */
		$tmp['arCache']['srch_settings']['time'] = sprintf( "%1.5f", $oTm->end() );

		/* Encode search results */
		$tmp['arCache']['srch_settings'] = serialize( $tmp['arCache']['srch_settings'] );

		/* ----------------------------------------------- */
		/* Save search results */
#		prn_r( $tmp['arCache'] );
		$oDb->sqlExec( gw_sql_replace( $tmp['arCache'], $sys['tbl_prefix'] . 'search_results' ), '', 0 );
	}

	#prn_r( unserialize( $tmp['arCache']['srch_settings'] ), __LINE__ . ' ' . __FILE__ );
	#prn_r( $tmp['arResults'], __LINE__ . ' ' . __FILE__ );
	#prn_r( $oDb->query_array );
	#prn_r( $id_term_redirect );
	#exit;

	/* Write search log */
	if ( GW_IS_BROWSE_WEB && $sys['is_log_search'] )
	{
		$q_search['id_dict'] = $arDictParam['id'];
		$q_search['found'] = $tmp['arCache']['found'];
		$q_search['date_created'] = $sys['time_now_gmt_unix'];
		$q_search['ip_long'] = $oFunc->ip2int( REMOTE_IP );
		$q_search['q'] = $tmp['arCache']['q'];
		$sql = gw_sql_insert( $q_search, $sys['tbl_prefix'] . 'stat_search' );
		$oDb->sqlExec( $sql );
	}

	/* Only one term found, redirect to that term */
	if ( GW_IS_BROWSE_WEB
			&& $a_search_params['adv'] == 'phrase'
			&& isset( $tmp['arCache']['srch_settings']['results'] )
			&& ($tmp['arCache']['found'] == 1 || !empty( $arSql ))
	)
	{
		$tmp['arCache']['srch_settings'] = unserialize( $tmp['arCache']['srch_settings'] );
		for ( reset( $tmp['arCache']['srch_settings']['results'] ); list($id_dict, $id_term) = each( $tmp['arCache']['srch_settings']['results'] ); )
		{
			gwtk_header( $sys['server_proto'] . $sys['server_host'] .
					$oHtml->url_normalize(
							$sys['page_index'] . '?' .
							GW_ACTION . '=' . GW_T_TERM . '&' .
							GW_ID_DICT . '=' . $arDictParam['uri'] . '&' .
							GW_TARGET . '=' . urlencode( $id_term_redirect )
					), $sys['is_delay_redirect'], __FILE__, __LINE__
			);
		}
	}
	$tmp['redirect_url'][] = 'id_srch=' . $tmp['arCache']['id_srch'];
	$tmp['redirect_url'][] = GW_ACTION . '=' . GW_A_SEARCH;
	$tmp['redirect_url'][] = 'p=1';

	if ( isset( $gw_this['vars']['note_afterpost'] ) )
	{
		$tmp['redirect_url'][] = 'note_afterpost=' . urlencode( $gw_this['vars']['note_afterpost'] );
	}
	/* */
	for ( reset( $sys['ar_url_append'] ); list($k, $v) = each( $sys['ar_url_append'] ); )
	{
		$tmp['redirect_url'][] = $k . '=' . $v;
	}
#	prn_r( $tmp );
#	exit;
	/* Redirect to saved search results */
	gwtk_header( $oHtml->url_normalize( $sys['server_proto'] . $sys['server_host'] . $page_index . '?' . implode( '&', $tmp['redirect_url'] ) ), $sys['is_delay_redirect'] );
}


/**
 *
 */
function gw_search_results ( $id_srch, $p, $id_dict = 0 )
{
	global $oDb, $oSqlQ, $oHtml, $oFunc, $oL, $oSess, $oTpl;
	global $sys, $strict, $gw_this, $ar_theme;
	global $arTplVars, $arDictParam;
	if ( (mt_rand() % 100) < $sys['prbblty_tasks'] )
	{
		/* Clean old search results */
		gw_search_cleanup();
	}
# header('Content-Type: text/html; charset=UTF-8');
	/* Select an existent query */
	$arSql = $oDb->sqlExec( $oSqlQ->getQ( 'srch-result-id', $id_srch ) );
	$strA = array ( 'found' => 0, 'found_total' => 0, 'q' => '', 'cur_id_dict' => 0, 'hits' => 0, 'time' => 0 );
	$arSql = isset( $arSql[0] ) ? $arSql[0] : array ( 'srch_settings' => 'a:0:{}' );
	/* insert all settings into the common array */
	$arSql = array_merge( $arSql, unserialize( $arSql['srch_settings'] ) );
	$arSql = array_merge( $strA, $arSql );
	/* No results */
	if ( empty( $arSql ) || !$arSql['found'] )
	{
		$oTpl->addVal( 'v:search_time', sprintf( "%1.3f", $arSql['time'] ) );
		$oTpl->addVal( 'v:q', $arSql['q'] );
		return $arSql;
	}
	unset( $arSql['srch_settings'] );
	/* */
#prn_r($arSql, __LINE__.' '.__FILE__);
	/* */
	$arResults = array ( );
	/* get Dictionary IDs */
	$arSql['arDictIds'] = array_keys( $arSql['results'] );
	$arSql['found_total'] = $arSql['found'];
	/* If requested dictionary is not in the search results, then reset to 0 */
	$id_dict = in_array( $id_dict, $arSql['arDictIds'] ) ? $id_dict : 0;
	$cnt_dict = 1;
	/* Go for each dictionary */
	for ( reset( $arSql['results'] ); list($dictK, $id_terms) = each( $arSql['results'] ); )
	{
		$tmp['arDictParam'][$dictK] = getDictParam( $dictK );
		if ( $cnt_dict == 1 )
		{
			/* Selected first dictionary from search results. Used in navigation for search results.
			  If no dictionary ID specified, select the first ID from $arSql['results'].
			  Otherwise use specified ID.
			 */
			$id_dict = ($id_dict == 0) ? $dictK : $id_dict;
			$arSql['found'] = sizeof( explode( ',', $arSql['results'][$id_dict] ) );
			$str_terms = $arSql['results'][$id_dict];
		}
		$cnt_dict++;
	}
	$str_compare = str_replace( array ( '*', '?' ), array ( '', '' ), $arSql['q'] );

	$arDictParam = $tmp['arDictParam'][$id_dict];

	/* */
	if ( !isset( $arDictParam['az_sql'] ) )
	{
		/* 1.8.7: Sorting order */
		$arDictParam['az_sql'] = $arDictParam['az_order'] = '';
		/* 1.8.7: Select custom alphabetic order */
		if ( $arDictParam['id_custom_az'] > 1 )
		{
			$arAz = $oDb->sqlExec( $oSqlQ->getQ( 'get-custom_az-int', $arDictParam['id_custom_az'] ) );
			/* A part of SQL-request for listing terms */
			$sql_az = '';
			$ar_az = array ( );
			for (; list($k, $v) = each( $arAz ); )
			{
				$ar_az[] = $v['value'];
			}
			if ( !empty( $ar_az ) )
			{
				$arDictParam['az_order'] = implode( ', ', $ar_az );
				$arDictParam['az_sql'] = ' FIELD( t.term_a, ' . $arDictParam['az_order'] .
						'), FIELD( t.term_b,' . $arDictParam['az_order'] .
						'), FIELD( t.term_c, ' . $arDictParam['az_order'] .
						'), FIELD( t.term_d, ' . $arDictParam['az_order'] .
						'), FIELD( t.term_e, ' . $arDictParam['az_order'] .
						'), FIELD( t.term_f, ' . $arDictParam['az_order'] . '), ';
			}
		}
	}
	/* Get founded terms in selected dictionary */
	if ( GW_IS_BROWSE_ADMIN )
	{
		$arResults = $oDb->sqlExec(
						$oSqlQ->getQ( 'get-terms-in-adm', $tmp['arDictParam'][$id_dict]['tablename'], $str_terms, $arDictParam['az_sql'],
								$oDb->prn_limit( $arSql['found'], $p, $tmp['arDictParam'][$id_dict]['page_limit_search'] ) ) );
	}
	else
	{
		$arResults = $oDb->sqlExec(
						$oSqlQ->getQ( 'get-terms-in', $str_compare, $str_compare, $str_compare, $str_compare, $tmp['arDictParam'][$id_dict]['tablename'], $str_terms, $arDictParam['az_sql'],
								$oDb->prn_limit( $arSql['found'], $p, $tmp['arDictParam'][$id_dict]['page_limit_search'] ) ) );
	}
#prn_r( $arResults );
	/* change global variables */
	$gw_this['vars'][GW_ID_DICT] = $id_dict;
	$arDictParam = $tmp['arDictParam'][$id_dict];
	if ( empty( $arResults ) )
	{
		$arSql['found_total'] = 0;
		return $arSql;
	}
	/* $id_dict is active */
	// --------------------------------------------------------
	// Settings for HTML-form
	// --------------------------------------------------------
#	$tpl_srch = new gwTemplate;
	// allow multilingual_vars
	gw_addon_multilingual_vars( '', 'oTpl' );
	//
	if ( GW_IS_BROWSE_ADMIN )
	{
		/* Save search query */
		$oSess->user_set( 'id_search', $id_srch );
		$oSess->user_set( 'q', $arSql['q'] );
		$oSess->user_set( 'in', $arSql['in'] );
		$oSess->user_set( 'srch_adv', $arSql['srch_adv'] );
		$oSess->user_set( 'srch_by', $arSql['srch_by'] );
		$page_index = $sys['page_admin'];
#		$oTpl->addVal( "v:search_time",   sprintf("%1.5f", $arQ['time']) );

		$oTpl->addVal( 'l:1072', $oL->m( '1072' ) );
		$oTpl->addVal( 'l:1071', $oL->m( '1071' ) );
		$oTpl->addVal( 'l:1320', $oL->m( '1320' ) );
		$oTpl->addVal( 'l:is_0', $oL->m( 'is_0' ) );
		$oTpl->addVal( 'l:is_1', $oL->m( 'is_1' ) );
		$oTpl->addVal( 'l:3_remove', $oL->m( '3_remove' ) );
		$oTpl->addVal( 'l:select_on', $oL->m( 'select_on' ) );
		$oTpl->addVal( 'l:select_off', $oL->m( 'select_off' ) );
	}
	else
	{
		$page_index = $sys['page_index'];
		$oTpl->addVal( 'v:search_time', sprintf( "%1.3f", $arSql['time'] ) );
	}
	$a_search_params['adv'] = & $arSql['srch_adv'];
	$a_search_params['by'] = & $arSql['srch_by'];
	$a_search_params['adv'] = & $arSql['srch_adv'];

	/* Web only */
	$arTplVars['srch']['v:chk_srch_in_term'] = '';
	$arTplVars['srch']['v:chk_srch_in_defn'] = '';
	$arTplVars['srch']['v:chk_srch_in_both'] = '';
	$arTplVars['srch']['v:chk_srch_in_term_unapproved'] = '';
	$arTplVars['srch']['v:chk_srch_in_term_incomplete'] = '';
	if ( $arSql['in'] == -1 )
	{
		$arTplVars['srch']['v:chk_srch_in_both'] = ' checked="checked"';
	}
	elseif ( $arSql['in'] == 0 )
	{
		$arTplVars['srch']['v:chk_srch_in_defn'] = ' checked="checked"';
	}
	elseif ( $arSql['in'] == 1 )
	{
		$arTplVars['srch']['v:chk_srch_in_term'] = ' checked="checked"';
	}
	elseif ( $arSql['in'] == 100 )
	{
		$arTplVars['srch']['v:chk_srch_in_term_unapproved'] = ' checked="checked"';
	}
	elseif ( $arSql['in'] == 101 )
	{
		$arTplVars['srch']['v:chk_srch_in_term_incomplete'] = ' checked="checked"';
	}
	/* make preview */
	$arA = gw_sql2defnpreview( $arResults );
	/* */
	$oHtml->setTag( 'a', 'style', 'text-decoration:underline' );
	/* Resort dictionaries in alphabetic order */
	$arQ['arDictIdsSorted'] = array ( );
	for ( reset( $arSql['arDictIds'] ); list($k, $v_id_dict) = each( $arSql['arDictIds'] ); )
	{
		$arSql['arDictIdsSorted'][$v_id_dict]['id'] = $tmp['arDictParam'][$v_id_dict]['id'];
		$arSql['arDictIdsSorted'][$v_id_dict]['title'] = $tmp['arDictParam'][$v_id_dict]['title'];
	}
	unset( $arSql['arDictIds'] );
	ksort( $arSql['arDictIdsSorted'] );
	$cnt_dicts = sizeof( $arSql['arDictIdsSorted'] );
	$tmp['dict_href'][1] = 'a=' . GW_A_SEARCH;
	$tmp['dict_href'][3] = 'id_srch=' . $id_srch;
	$tmp['dict_href'][4] = 'p=1';
	$cnt = 1;
	for ( reset( $arSql['arDictIdsSorted'] ); list($k1, $arV) = each( $arSql['arDictIdsSorted'] ); )
	{
		$str_found_dict = $k1;
		/* prepare the list of dictionaries with link to search results */
		$oTpl->tmp['d']['list_dict'][$k1]['v:found_dict'] = sizeof( explode( ',', $arSql['results'][$arV['id']] ) );
		if ( !isset( $gw_this['cookie']['gw_visualtheme'] ) || $gw_this['cookie']['gw_visualtheme'] == '' )
		{
			$tmp['dict_href'][5] = 'visualtheme=' . $sys['visualtheme'];
		}
		if ( !isset( $gw_this['cookie']['gw_' . GW_LANG_I] ) || $gw_this['cookie']['gw_' . GW_LANG_I] == '' )
		{
			$tmp['dict_href'][6] = GW_LANG_I . '=' . $gw_this['vars'][GW_LANG_I];
		}
		/* link to Dictionary ID for search results */
		$tmp['dict_href'][2] = GW_ID_DICT . '=' . $arV['id'];
		$arV['title'] = strip_tags( $arV['title'] );
		$oTpl->tmp['d']['list_dict'][$k1]['url:dict_url_search'] = $oHtml->a( $page_index . '?' . implode( '&', $tmp['dict_href'] ), $arV['title'] );

		/* unlink current dictionary */
		if ( $arV['id'] == $id_dict && isset( $ar_theme['txt_linkmarker'] ) )
		{
			$oTpl->tmp['d']['list_dict'][$k1]['url:dict_url_search'] = $ar_theme['txt_linkmarker'] . $arV['title'];
			$arSql['cur_dict_title'] = $arV['title'];
			$arSql['cur_id_dict'] = $tmp['arDictParam'][$arV['id']]['uri'];
		}
		/* text delimiter for the list of dictionaries */
		$oTpl->tmp['d']['list_dict'][$k1]['v:delimeter'] = ', ';
		if ( $cnt == $cnt_dicts )
		{
			$oTpl->tmp['d']['list_dict'][$k1]['v:delimeter'] = '';
		}
		$cnt++;
	}
	$oHtml->setTag( 'a', 'style', '' );
	$cnt = 0;
#	prn_r( $arA );
	/* For each re-formated results */
	for ( reset( $arA ); list($k1, $v1) = each( $arA ); )
	{
		$arDictParam = getDictParam( $v1['d_id'] );

		/* Collect data for template */
		$oTpl->tmp['d']['search_item'][$k1]['v:term_number'] = (($p - 1) * $arDictParam['page_limit_search']) + $k1 + 1;
		$oTpl->tmp['d']['search_item'][$k1]['v:color_odd_even'] = ($cnt % 2 ) ? $ar_theme['color_2'] : $ar_theme['color_1'];
		$oTpl->tmp['d']['search_item'][$k1]['v:id_term'] = $v1['t_id'];
		if ( GW_IS_BROWSE_ADMIN )
		{
			$oTpl->tmp['d']['search_item'][$k1]['v:status'] = $v1['is_active'] ? '' : '<span class="red">' . $oL->m( 'is_0' ) . '</span>';
			/* Term is removed */
			if ( $v1['is_active'] == 3 )
			{
				$oTpl->tmp['d']['search_item'][$k1]['v:term'] = text_highlight( $v1['term'], $arSql['q'] );
				$oTpl->tmp['d']['search_item'][$k1]['v:checkbox'] = '<input onchange="term_selected(this.value)" id="id-term-' . $v1['t_id'] . '" name="arPost[ar_id][]" type="checkbox" value="' . $v1['t_id'] . '" />';
				$oTpl->tmp['d']['search_item'][$k1]['url:term_edit'] = '';
				$oTpl->tmp['d']['search_item'][$k1]['url:term_remove'] = '';
			}
			else
			{
				$oTpl->tmp['d']['search_item'][$k1]['v:term'] = $oHtml->a( $page_index
								. '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_TERMS . '&id=' . $v1['d_id']
								. '&tid=' . $v1['t_id'],
								text_highlight( $v1['term'], $arSql['q'] )
				);
				$oTpl->tmp['d']['search_item'][$k1]['v:checkbox'] = '<input onchange="term_selected(this.value)" id="id-term-' . $v1['t_id'] . '" name="arPost[ar_id][]" type="checkbox" value="' . $v1['t_id'] . '" />';
				$oTpl->tmp['d']['search_item'][$k1]['url:term_edit'] = '<span class="actions-third">' . $oHtml->a( $page_index
								. '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_TERMS . '&id=' . $v1['d_id']
								. '&tid=' . $v1['t_id'], $oL->m( '3_edit' ) ) . '</span>';

				$oHtml->setTag( 'a', 'onclick', 'return confirm(\'' . $oL->m( '3_remove' ) . ': &quot;' . htmlspecialchars( $v1['term_text'] ) . '&quot;. ' . $oL->m( '9_remove' ) . '\' )' );
				$oHtml->setTag( 'a', 'class', 'submitdel' );
				$oTpl->tmp['d']['search_item'][$k1]['url:term_remove'] = '<span class="actions-third">' . $oHtml->a( $page_index
								. '?' . GW_ACTION . '=' . GW_A_REMOVE . '&' . GW_TARGET . '=' . GW_T_TERMS . '&id=' . $v1['d_id'] . '&isConfirm=1&arPost[is_save_history]=1&arPost[after]=' . GW_AFTER_SRCH_BACK
								. '&tid=' . $v1['t_id'], $oL->m( '3_remove' ) ) . '</span>';
				$oHtml->setTag( 'a', 'class', '' );
				$oHtml->setTag( 'a', 'onclick', '' );
			}
			/* Check permissions */
#prn_r( $v1['id_user'] );
			if ( ($v1['id_user'] != $oSess->id_user) && !$oSess->is( 'is-terms' ) )
			{
				$oTpl->tmp['d']['search_item'][$k1]['url:term_remove'] = '';
				$oTpl->tmp['d']['search_item'][$k1]['url:term_edit'] = '';
				$oTpl->tmp['d']['search_item'][$k1]['v:checkbox'] = '';
				$oTpl->tmp['d']['search_item'][$k1]['v:term'] = text_highlight( $v1['term'], $arSql['q'] );
			}
		}
		else
		{
			/* highlighted link from search results */
			if ( trim( $v1['defn'] ) == '' )
			{
				$oTpl->tmp['d']['search_item'][$k1]['v:term'] = text_highlight( $v1['term_text'], $arSql['q'], $sys['internal_encoding'] );
			}
			else
			{
				$oTpl->tmp['d']['search_item'][$k1]['v:term'] = $oHtml->a( $v1['href'], text_highlight( $v1['term_text'], $arSql['q'], $sys['internal_encoding'] ) );
			}
			/* links to edit and remove term */
			/* not in use */
			if ( is_object( $oSess ) && $oSess->is( 'is-terms', $arDictParam['id'] ) )
			{
				$oTpl->tmp['d']['search_item'][$k1]['url:term_edit'] = $oHtml->a( $sys['page_admin']
								. '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_TERM . '&id=' . $v1['d_id']
								. '&tid=' . $v1['t_id'], $oL->m( '3_edit' ) );
				$oTpl->tmp['d']['search_item'][$k1]['url:term_remove'] = $oHtml->a( $sys['page_admin']
								. '?' . GW_ACTION . '=' . GW_A_REMOVE . '&' . GW_TARGET . '=' . GW_T_TERM . '&id=' . $v1['d_id']
								. '&tid=' . $v1['t_id'], $oL->m( '3_remove' ) );
			}
		}
		$oTpl->tmp['d']['search_item'][$k1]['v:defn'] = text_highlight( $v1['defn'], $arSql['q'], $sys['internal_encoding'] );
		$cnt++;
	}
	$oTpl->addVal( 'v:cnt_term_start', (($p - 1) * $arDictParam['page_limit_search']) + 1 );
	$oTpl->addVal( 'v:cnt_term_end', (($p - 1) * $arDictParam['page_limit_search']) + 1 + $cnt );

	$oTpl->addVal( 'l:1371', $oL->m( 1371 ) );
	$oTpl->addVal( 'l:1372', $oL->m( 1372 ) );
	$oTpl->addVal( 'l:1373', $oL->m( 1373 ) );

	if ( GW_IS_BROWSE_WEB && $ar_theme['columns'] > 1 )
	{
		include_once( $sys['path_gwlib'] . '/class.cells_tpl.php' );
		$oCells = new gw_cells_tpl();
		$oCells->class_tpl = $sys['class_tpl'];
		$oCells->tpl = 'tpl_cells_term';
		$oCells->id_theme = $gw_this['vars']['visualtheme'];
		$oCells->arK = & $oTpl->tmp['d']['search_item'];
		$oCells->X = $ar_theme['columns'];
		$oCells->Y = $sys['page_limit'];
		$oCells->int_page = $gw_this['vars']['p'];
		$oCells->is_odd = 1;
		$oCells->tSpacing = 1;
		$oCells->tPadding = 0;
		$oCells->tAttrClass = 'tbl-browse';
		$oTpl->addVal( 'block:columns', $oCells->output() );
		$oTpl->tmp['d']['list_item'] = array ( );
	}
	else if ( GW_IS_BROWSE_WEB )
	{
		$oTpl->tmp['d']['if:one_column'] = true;
	}
	return $arSql;
}


/**
 * Cleans search results table.
 * Removes old queries
 */
function gw_search_cleanup ()
{
	global $oDb, $oSqlQ, $sys;
	$sql = sprintf( 'DELETE FROM `%s` WHERE srch_date < %d',
					$sys['tbl_prefix'] . 'search_results',
					$sys['time_now_gmt_unix'] - ($sys['max_days_searchcache'] * 24) * 3600 );
	$oDb->sqlExec( $sql );
	$sql = sprintf( 'DELETE FROM `%s` WHERE date_created < %d',
					$sys['tbl_prefix'] . 'stat_search',
					$sys['time_now_gmt_unix'] - ($sys['max_days_searchlog'] * 24) * 3600 );
	$oDb->sqlExec( $sql );
	$oDb->sqlExec( 'CHECK TABLE `' . $sys['tbl_prefix'] . 'stat_search`' );
}

/* end of file */
?>