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
	die('<!-- $Id: func.sql.inc.php 551 2008-08-17 17:34:05Z glossword_team $ -->');
}
/**
 *   Functions to handle numerios SQL-requests.
 */
/**
 * Adds or updates a term.
 * @param   array   $arPre Data in structured array
 * @param   int     $id_dict Dictionary id
 * @param   array   $arStop Stop words
 * @param   int     $in_term Do search in term or not
 * @param   int     $is_specialchars Is allow special characters in a term
 * @param   int     $is_overwrite Overwrite if already exist
 * @param   int     $intLine Line number, used for Import to report
 * @param   int     $isDelete Always delete (all checks for existent term will be skipped)
 */
function gwAddTerm($arPre, $id_dict, $arStop, $in_term, $is_specialchars, $is_overwrite, $intLine = 0, $isDelete = 0)
{
	global $oDom, $oFunc, $oDb, $oSqlQ, $oCase, $oL, $oHtml, $oSess;
	global $arFields, $arDictParam, $arTermParam, $cnt, $tid, $sys;
	global $gw_this, $qT;

#	@header("content-type: text/html; charset=utf-8");

	$id_w = $oDb->MaxId($arDictParam['tablename']);
	$isQ = 1;
	$isCleanMap = 0;
	$id_old = isset($tid) ? $tid : 0;
	$queryA = $qT = array();
	$isTermExist = 0;
	/* Used for keywords */
	$str_term_filtered = $oDom->get_content($arPre['term']);
	/* remove {TEMPLATES}, {%TEMPLATES%} */
	$str_term_filtered = preg_replace("/{(%)?([A-Za-z0-9:\-_]+)(%)?}/", '', $str_term_filtered);
	$str_term_filtered = trim($str_term_filtered);
	/* Used in database */
	$str_term_src = gw_fix_input_to_db($str_term_filtered);
	
	$str_term_filtered = gw_unhtmlspecialamp($str_term_filtered);
	/* 22 jul 2003: Custom Term ID */
	$qT['id'] = $oDom->get_attribute('id', 'term', $arPre['term'] );
	$qT['id'] = preg_replace("/[^0-9]/", '', trim($qT['id']));
	/* */
	if (!$isDelete)
	{
		/* -- Check for an existed term -- */
		/* prepare keywords for a term */
		if ($is_specialchars)
		{
			/* Keep specials */
			$str_term_filtered = $oCase->nc($str_term_filtered);
			$str_term_filtered = gw_text_wildcars($str_term_filtered);
			$arKeywordsT = text2keywords($oCase->rm_($str_term_filtered), 1);
		}
		else
		{
			/* Remove specials */
			$str_term_filtered = text_normalize($str_term_filtered);
			$arKeywordsT = text2keywords($str_term_filtered, $arDictParam['min_srch_length']);
		}
		/* Are there any keywords? */
		$isTermEmpty = (empty($arKeywordsT) && (strlen(trim( $str_term_filtered )) == 0)) ? 1 : 0;
		if (!$isTermEmpty)
		{
			/* no empty keywords for a term */
			/* SQL-query */
			/* Get existent keywords, standard mode */
			$word_srch_sql = ($qT['id'] != '') ? 't.id = "' . $qT['id'] . '"' : '';
			if ($word_srch_sql == '')
			{
				$word_srch_sql = "k.word_text IN ('" . implode("', '", $arKeywordsT) . "')";
			}
			$sql = $oSqlQ->getQ('get-term-exists', TBL_WORDLIST, $arDictParam['tablename'], $id_dict,
						$in_term, $word_srch_sql
					);
			/* Get existent keywords, keep specialchars mode */
			if (($is_specialchars) || empty($arKeywordsT))
			{
				$ar_chars_sql = array('\\' => '\\\\', '\\%' => '\\\\\\\%', '\\_' => '\\\\\\\_', '\\"' => '\\\\\\\"', "\\'" => "\\\\\\\'");
				$sql = $oSqlQ->getQ('get-term-exists-spec', $arDictParam['tablename'],
						str_replace(array_keys($ar_chars_sql), array_values($ar_chars_sql), gw_addslashes( $str_term_src ))
					);
			}
			$arSql = $oDb->sqlExec($sql);
#			prn_r($arSql, __LINE__ . '<br />' . $sql);
			$isTermNotMatched = 1; // `No term found' by default

			for (; list($arK, $arV) = each($arSql);) // compare founded values (Q) with imported (T)
			{
				$id_old = $arV['id']; // get ID for existent keywords.
				if ($id_old == $qT['id'])
				{
					$isTermNotMatched = 0;
					break;
				}
				if ($is_specialchars)
				{
					/* 1 - is the minimum length */
					$arKeywordsQ = text2keywords( text_normalize($arV['term']), 1);
				}
				else
				{
					/* $arDictParam['min_srch_length'] - is the minimum length */
					$arKeywordsQ = text2keywords( text_normalize($arV['term']), $arDictParam['min_srch_length']);
				}
#				prn_r($arKeywordsQ);
				$div1 = sizeof(gw_array_exclude($arKeywordsT, $arKeywordsQ));
				$div2 = sizeof(gw_array_exclude($arKeywordsQ, $arKeywordsT));
				$isTermNotMatched = ($div1 + $div2);
				// if the sum of excluded arrays is 0, this term already exists
				if (!$isTermNotMatched) // in english, double negative means positive... yeah
				{
					break;
				}
			} // end of for each founded terms
			if ($isTermNotMatched > 0)
			{
				$isQ = 1;
				$isTermExist = 0;
			}
			else
			{
				$isTermExist = 1;
			}
		} // !$isTermEmpty
	}
	/* */
	if (!isset($str_term_filtered))
	{
		$str_term_filtered = gw_text_wildcars($str_term_filtered);
		$str_term_filtered = text_normalize($str_term_filtered);
	}
	/* 21 jan 2006: new `date_created' for new terms */
	/* 23 apr 2008: subtract 61 second */
	$qT['date_created'] = isset($arPre['date_created']) ? $arPre['date_created'] : $sys['time_now_gmt_unix'];
	$qT['date_modified'] = $sys['time_now_gmt_unix'] - 61;
	$qT['date_created'] -= 61;
	/* 21 jul 2003: Better protection by adding random Next ID number */
	$arTermParam['tid'] = $qT['id'] = ($qT['id'] == '') ? mt_rand($id_w, ($sys['leech_factor'] * 2) + $id_w) : $qT['id'];
	$qT['term']      = $str_term_src;
	/* 15 sep 2007: Term URI */
	/* 08 apr 2008: Make link to added term */
	/* 11 apr 2008: Better URI */
	/* 23 apr 2008: Even better URI. Added transliteration. */
	$qT['term_uri'] = $oDom->get_attribute('uri', 'term', $arPre['term'] );
	$qT['term_uri'] = ($qT['term_uri'] == '') ? $qT['id'].'-'.$oCase->translit( $oCase->lc($str_term_filtered)) : $qT['term_uri'];
	$qT['term_uri'] = $oCase->rm_entity($qT['term_uri']);
	$qT['term_uri'] = preg_replace('/[^0-9A-Za-z_-]/', '-', $qT['term_uri']);
	$qT['term_uri'] = preg_replace('/-{2,}/', '-', $qT['term_uri']);
	if ($qT['term_uri'] == '-')
	{
		$qT['term_uri'] = $qT['id'].'-';
	}
	$arTermParam['term_uri'] = $qT['term_uri'];

	$qT['defn']      =& $arPre['parameters']['xml'];
	/* Alphabetic orders 1,2,3 */
	$qT['term_1']    = $oDom->get_attribute('t1', 'term', $arPre['term'] );
	$qT['term_2']    = $oDom->get_attribute('t2', 'term', $arPre['term'] );
	$qT['term_3']    = $oDom->get_attribute('t3', 'term', $arPre['term'] );
	/* -- Custom Alphabetic Toolbar -- */
	/* Select custom rules for uppercasing */
	$sql = 'SELECT az_value, az_value_lc FROM `'.$sys['tbl_prefix'].'custom_az` WHERE `id_profile` = "'.$arDictParam['id_custom_az'].'"';
	$arSqlAz = $oDb->sqlRun($sql, 'st');
	for (; list($arK, $arV) = each($arSqlAz);)
	{
		$str_term_src = str_replace($arV['az_value_lc'], $arV['az_value'], $str_term_src);
	}
	/* Unicode uppercase */
	$str_term_src_uc = $oCase->uc( $str_term_src );
	/* 1.8.7: Custom sorting order */
	$qT['term_order'] = $oDom->get_attribute('term_order', 'term', $arPre['term'] );
	$qT['term_order'] = ($qT['term_order'] == '') ? $str_term_src_uc : $qT['term_order'];
	$qT['term_a'] = $qT['term_b'] = $qT['term_c'] = $qT['term_d'] = $qT['term_e'] = $qT['term_f'] = 0;
	/* Prepare A, AAZZ, AAAZZZ */
	$qT['term_3'] = ($qT['term_3'] == '') ? $oFunc->mb_substr($str_term_filtered, 2, 1, $sys['internal_encoding']) : $qT['term_3'];
	$qT['term_2'] = ($qT['term_2'] == '') ? $oFunc->mb_substr($str_term_filtered, 1, 1, $sys['internal_encoding']) : $qT['term_2'];
	$qT['term_1'] = ($qT['term_1'] == '') ? $oFunc->mb_substr($str_term_filtered, 0, 1, $sys['internal_encoding']) : $qT['term_1'];
	/* */
	$ar_field_names = array('a','b','c','d','e','f');
	preg_match_all("/./u", $qT['term_order'], $ar_letters);
	for (; list($cnt_letter, $letter) = each($ar_letters[0]);)
	{
		if (isset($ar_field_names[$cnt_letter]))
		{
			$qT['term_'.$ar_field_names[$cnt_letter]] = text_str2ord($letter);
		}
	}
	/* Fix htmlspecial characters */
	$qT['term_3']    = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_3']));
	$qT['term_2']    = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_2']));
	$qT['term_1']    = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_1']));
	/* */
	$qT['is_active'] = $oDom->get_attribute('is_active', 'term', $arPre['term']);
	$qT['is_complete'] = $oDom->get_attribute('is_complete', 'term', $arPre['term']);
#prn_r($qT, __LINE__.__FILE__);
#exit;
	if ($isDelete || $isTermExist)
	{
		/* Assign Term ID from previously added term */
		$arTermParam['tid'] = $id_old;
		if ($is_overwrite)
		{
			$qT['id'] = $id_old;
			$isCleanMap = 1;
			$isTermExist = 0;
#			$queryA[] = $oSqlQ->getQ('del-term_id', $arDictParam['tablename'], $id_old, $id_dict);
		}
	}
	if (!$isDelete && $isTermExist)
	{
		/* Term already exists */
		$ar_matched_terms = array();
		for (reset($arSql); list($arK, $arV) = each($arSql);)
		{
			$ar_matched_terms[]  = $oHtml->a($sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_TERMS.'&id='.$id_dict.'&tid=' . $arV['id'],
						 $arV['term'], '', '', $oL->m('3_edit'));
		}
		$str_line = implode(' | ', $ar_matched_terms);
		$queryA = '<dl style="margin:0">';
		$queryA .= '<dt class="xu red">'.$oL->m('reason_25').' - <strong>'. $str_term_src . '</strong></dt>';
		$queryA .= '<dd class="termpreview">' .$str_line. '</dd>';
		$queryA .= '</dl>';
		$isQ = 0;
	}
	/* Allow query */
	if ($isQ)
	{
		/* Prepare keywords per field */
#		$ot = new gw_timer('addterm');
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			// Init
			$arKeywords[$fK] = array();
			//
#			$int_min_length = (isset($fV[2]) && ($fV[2] != 'auto') && ($fV[2] != '')) ? $fV[2] : $arDictParam['min_srch_length'];
#			$int_min_length = (!isset($fV[2]) || ($fV[2] == 'auto') ) ? $int_min_length : $fV[2];
			//
			// Make keywords from array
			// space is required, otherwise `...word</defn><defn>word...' will become `wordword'
			$tmpStr = '';
			if (isset($arPre[$fV[0]]))
			{
				$tmpStr = $oDom->get_content( $arPre[$fV[0]] );
			}
			if ($tmpStr != '') // do not parse empty strings
			{
				// Get maximum search length per field
				$int_min_length = $fV[2];
				if ( is_string($int_min_length) )
				{
					$int_min_length = $arDictParam['min_srch_length'];
				}
#				$isStrip = ($srchLength == 1) ? 0 : 1;
#                prn_r( text_normalize( $tmpStr ) . ' ' . $fV[0] . '; len=' . $int_min_length);
				/* Fix wildcars, 1.6.1 */
				$tmpStr = str_replace('<![CDATA[', '', $tmpStr);
				$tmpStr = str_replace(']]>', '', $tmpStr);
				$tmpStr = gw_text_wildcars($tmpStr);
				/* */
#				prn_r( $fV  );
				$arKeywords[$fK] = text2keywords( gw_text_sql(text_normalize($tmpStr)), $int_min_length, 25, $sys['internal_encoding'] );
				/* Remove stopwords from parsed strings only (others are empty) */
				$arKeywords[$fK] = gw_array_exclude( $arKeywords[$fK], $arStop);
			}
		}
		/* keywords convertion time */
#		prn_r( $arKeywords );
#		print $ot->endp(__LINE__, __FILE__);
#		exit;
		/* Remove double keywords from definition */
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			if ($fK != 0)
			{
				$arKeywords[0] = gw_array_exclude( $arKeywords[0], $arKeywords[$fK]);
			}
		}
		/** Keywords were prepared */
		/** Add keywords to database! */
#       prn_r($arStop);
#       prn_r($arKeywords);
#       prn_r($arPre);
		gwAddNewKeywords($id_dict, $qT['id'], $arKeywords, $id_old, $isCleanMap, $qT['date_created']);
		$qT['int_bytes'] = strlen($qT['defn']);
		/* Checksum */
		$qT['crc32u'] = crc32($str_term_src_uc);
		/* Add User ID to term */
		if ($gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$qT['id_user'] = $oSess->id_user;
		}
		/* Add relation `user to term' */
		$q2['user_id'] = $oSess->id_user;
		$q2['term_id'] = $qT['id'];
		$q2['dict_id'] = $id_dict;
		// -------------------------------------------------
		// Turn on text parsers
		// -------------------------------------------------
		// Process automatic functions
		for (; list($k, $v) = each($gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_TERM]);)
		{
			if (function_exists($v))
			{
				$v();
			}
		}

		/* REPLACE or UPDATE */
		if ($isDelete || $isTermExist)
		{
			if ($is_overwrite)
			{
				unset($qT['id']);
				$queryA[] = gw_sql_update($qT, $arDictParam['tablename'], 'id = "'.$id_old.'"');
			}
		}
		else
		{
			$id_old = $qT['id'];
			$queryA[] = gw_sql_insert($qT, $arDictParam['tablename'], 1);
		}

		/* 23 Nov 2007: history of changes */
		$qT['id_term'] = $id_old;
		$qT['id_dict'] = $arDictParam['id'];
		$qT['id_user'] = $oSess->id_user;
		$qT['keywords'] = serialize($arKeywords);
		unset($qT['id']);
		
		$queryA[] = gw_sql_insert($qT, $sys['tbl_prefix'].'history_terms', 1);
		/* Assign edited term to user */
		$queryA[] = gw_sql_replace($q2, TBL_MAP_USER_TERM, 1);
		/* Check table to keep indexes */
		$arQuery[] = 'CHECK TABLE `'.$arDictParam['tablename'].'`';
	} /* is_query allowed */
	return $queryA;
}

/**
 * Adds new keywords into tables TBL_WORDLIST and TBL_WORDMAP
 *
 * @param int $id_dict Dictionary ID
 * @param int $termId Term ID
 * @param array $arKeywords Array with keywords per field
 * @param int $termIdOld
 * @param int $isClean Do we need to clean the current keywords map
 * @param int $date_created 20 july 2007: Date of term creation
 */
function gwAddNewKeywords($id_dict, $id_term, $arKeywords, $termIdOld, $isClean, $date_created)
{
	global $arFields, $intFields, $arStop, $sys;
	global $oDb, $oSqlQ;
#	@header("content-type: text/html; charset=utf-8");

	// Adding search keywords
	$arKeywordsJoin = array();
	for (reset($arKeywords); list($k, $v) = each($arKeywords);)
	{
		$arKeywordsJoin = array_merge($arKeywordsJoin, $v); // common array with all keywords
	}
	// unique keywords only, have to run second time
	$arKeywordsJoin = array_flip($arKeywordsJoin);

	$arQuery = array();
	$arSql = array();
	if (!empty($arKeywordsJoin))
	{
		$word_text_sql = "'" . implode("', '", array_keys($arKeywordsJoin)) . "'";
		/* Get the list of already known keywords and their IDs */
		$arSql = $oDb->sqlExec($oSqlQ->getQ('get-word', TBL_WORDLIST, $word_text_sql));
	}
#    prn_r($arSql);
	$q2 = $q1 = array();
	if (!empty($arSql)) // some keywords are exist already
	{
		$cnt = 0;
		// for each founded keyword
		for (; list($arK, $arV) = each($arSql);)
		{
			if ($isClean) // overwrite mode
			{
				$arQuery[0] = $oSqlQ->getQ('del-wordmap-by-term-dict', $termIdOld, $id_dict);
			}
			$q2['word_id'] = $arV['word_id'];
			$q2['term_id'] = $id_term;
			$q2['dict_id'] = $id_dict;
			$q2['date_created'] = $date_created;
			// Set Field ID
			for (reset($arFields); list($id_field, $fV) = each($arFields);)
			{
				if (isset($arKeywords[$id_field]) && in_array($arV['word_text'], $arKeywords[$id_field]))
				{
					$q2['term_match'] = $id_field;
					$arQueryMapExist[] = gw_sql_insert($q2, TBL_WORDMAP, 1, $cnt);
					unset($arKeywordsJoin[$arV['word_text']]); // remove existent keyword
					$cnt++;
				}
			}
		}
	}
	//
	// Add new kewords
#    prn_r($arKeywordsJoin);
#    prn_r($arKeywords);
	//
	$q2 = $q1 = array();
	$q1['word_id'] = $q2['word_id'] = ($oDb->MaxId(TBL_WORDLIST, 'word_id') - 1);
	$cnt = $cntMap = 0;
	// for each new keyword
	for (reset($arKeywordsJoin); list($newkeyword, $v2) = each($arKeywordsJoin);)
	{
		$q2['word_id']++;
		$q2['dict_id'] = $id_dict;
		$q2['term_id'] = $id_term;
		$q2['date_created'] = $date_created;
		$q1['word_id']++;
		$q1['word_text'] = '';
		//
		// Set index ID per field
		for (reset($arFields); list($id_field, $fV) = each($arFields);)
		{
			if (isset($arKeywords[$id_field]) && in_array($newkeyword, $arKeywords[$id_field]))
			{
				$q2['term_match'] = $id_field;
				$q1['word_text'] = $newkeyword;
				// add keyword into map
				$arQueryMap[] = gw_sql_insert($q2, TBL_WORDMAP, 1, $cntMap);
				unset($arKeywordsJoin[$newkeyword]); // remove new keyword
				$cntMap++;
			}
		}
		// add new word into wordlist
		if ($q1['word_text'] != '')
		{
			$arQueryWord[] = gw_sql_insert($q1, TBL_WORDLIST, 1, $cnt);
			$cnt++;
		}
	}
	/* Queries for existent keywords */
	if (isset($arQueryMapExist)) { $arQuery[] = implode('', $arQueryMapExist); }
	/* Queries for new keywords */
	if (isset($arQueryWord)) { $arQuery[] = implode('', $arQueryWord); }
	/* Queries for new keywords map */
	if (isset($arQueryMap)) { $arQuery[] = implode('', $arQueryMap); }
	/* Displays queries */
	if ($sys['isDebugQ'])
	{
		$arQuery = gw_highlight_sql($arQuery);
		print '<ul class="gwsql"><li>'.implode(';</li><li>', $arQuery).';</li></ul>';
	}
	else
	{
		for (; list($kq, $vq) = each($arQuery);)
		{
			if (!$oDb->sqlExec($vq))
			{
				print '<li class="xt">Error: cannot exec query: '.$arQuery[$i].';</li>';
			}
		}
	}
}

/**
 *
 */
function sqlLock($tablename)
{
	return true; // disabled for a while
	global $oDb;
	if (!isset($oDb))
	{
		$oDb = new gwtkDb;
	}
	return $oDb->lock($tablename);
}
function sqlUnlock()
{
	return true; // disabled for a while
	global $oDb;
	if (!isset($oDb))
	{
		$oDb = new gwtkDb;
	}
	return $oDb->unlock();
}

function gw_get_databases()
{
	global $oDb, $sys;
	if (!defined('IS_CLASS_DB'))
	{
		include_once($sys['path_gwlib'] . '/class.db.mysql.php' );
	}
	if (!isset($oDb))
	{
		$oDb = new gwtkDb;
	}
	return $oDb->get_databases();
}

/**
 *
 */
function getTableInfo($tablename)
{
	global $oDb, $sys;
	if (!defined('IS_CLASS_DB'))
	{
		include_once($sys['path_gwlib'] . '/class.db.mysql.php' );
	}
	if (!isset($oDb))
	{
		$oDb = new gwtkDb;
	}
	return $oDb->table_info($tablename);
}

/**
 *
 * @param    string  $table       sql-table name // TODO: array
 * @param    int     $mode        [ 1 - structure | 2 - data | 3 - structure & data ]
 * @param    int     $func        [ 1 - insert | 2 - update ]
 * @param    string  $where       Dump only selected (28 may 2002)
 * @param    int     $isFields
 * @param    string  $limit       Apply "LIMIT n, n" to query
 */
function getTableStructure($tablename)
{
	global $oDb, $sys;
	if (!defined('IS_CLASS_DB'))
	{
		include_once( $sys['path_gwlib'] . '/class.db.mysql.php' );
	}
	if (!isset($oDb))
	{
		$oDb = new gwtkDb;
	}
	$strQ = '';
	$ar = array();
	// check for table name
	$isTable = 0;
	$arDbTables = $oDb->table_names($tablename);
	for (; list($k, $v) = each($arDbTables);)
	{
		if ($tablename == $v) { $isTable = 1; break; }
	}
	if (!$isTable) { return ''; }
#    if (!$isTable) { return 'ERROR: ' . $tablename . ' does not exist'; }
	// next
	$sql = 'SHOW CREATE TABLE `'. $tablename.'`';
	$arSql = $oDb->sqlExec($sql, '', 0);
	$arSql = isset($arSql[0]['Create Table']) ? $arSql[0] : array('Create Table');
	$strQ .= $arSql['Create Table'] . ';';
	return $strQ;
} // end of prepareSQLdum

#### -mySQL ####
// 1, Oct 2002
// 8 Feb 2005 - hexadecimal
function gw_sql_replace($arData, $tbl_name, $isFields = 1)
{
	$arFields = $arValues = array();
	$strFields = '';
	if (is_array($arData))
	{
		for (reset($arData); list($k, $v) = each($arData);)
		{
			$v = gw_text_sql($v);
			if ($v == ''){ $vF = "''"; }
			elseif ( preg_match("/^0x[0-9a-f]/", $v)) { $vF = $v; }
			else { $vF = "'" . $v . "'"; }
			$arFields[] = $k;
			$arValues[] = $vF;
		}
		if ($isFields) { $strFields = ' (`' . implode('`, `', $arFields) . '`)'; }
		$sql = CRLF . 'REPLACE INTO `' . $tbl_name .'`'. $strFields . ' VALUES ('.implode(',', $arValues).')';
		return $sql;
	}
	return false;
}

// 11 aug 2003: do not enclose with quotes all numerals
// 4 Oct 2002: short INSERT format (intCnt = [ 0, 1, 2 ... ]
// 24 May 2002: $isFields -- include field names to query or not
// 8 Feb 2005: no quotes for hexadecimal values
// 22 Feb 2005: DELAYED inserts
function gw_sql_insert($SQLnamesA, $table, $isFields = 1, $intCnt = 0, $is_delayed = 0)
{
	$query = '';
	$SQLfileds = '';
	$SQLfiledA = $SQLvalueA = array();
	if (is_array($SQLnamesA))
	{
		for (reset($SQLnamesA); list($k, $v) = each($SQLnamesA);)
		{
			$v = gw_text_sql($v);
			if ($v == '') {
				$vF = "''";
			}
			elseif ( preg_match("/^[0-9]{3,12}$/", $v) && !preg_match("/^[0-1]/", $v)
					||  preg_match("/^0x[0-9a-f]/", $v)) {
				// enum('0','1', .. ,'9'), timestamp(14), hex
				$vF = $v;
			}
			else {
				$vF = "'" . $v . "'";
			}
			$SQLfiledA[] = $k;
			$SQLvalueA[] = $vF;
		}
		$SQLvalues = implode(',', $SQLvalueA);
		if ($isFields) { $SQLfileds = ' (`' . implode('`, `', $SQLfiledA) . '`)'; }
		if (!$intCnt)
		{
			$query = CRLF.'INSERT '.($is_delayed ? 'DELAYED ': '').'INTO `' . $table .'`'. $SQLfileds . ' VALUES ('.$SQLvalues.')';
		}
		else
		{
			$query = ', ('.$SQLvalues.')';
		}
	}
	return $query;
} // end of function
/**
 *
 */
function gw_sql_update($SQLnamesA, $table, $where)
{
	$SQLratioA = array();
	for (reset($SQLnamesA); list($key, $val) = each($SQLnamesA);)
	{
		if (is_array($val)){
			$val = implode(',', $val);
		}
		$val = gw_text_sql($val);
		if ($val == ""){$valF = "'".$val."'";}
		else { $valF = "'".$val."'"; }
		$SQLratioA[] = "$key = $valF";
	}
	$SQLratioStr = implode(', ', $SQLratioA);
	$query = 'UPDATE `'.$table.'` SET '.$SQLratioStr.' WHERE '.$where;
	return $query;
}
/**
 * Function to construct DELETE statement.
 * 
 * @usage gw_sql_delete( 'tablename', array('id' => 1) );
 *        gw_sql_delete( 'tablename', array('id > field') );
 * @param string $table Database table name.
 * @param array $ar_where Where expression.
 * @return string Complete SQL-query.
 */
function gw_sql_delete($table, $ar_where)
{
	$ar_w = array();
	for (reset($ar_where); list($k, $v) = each($ar_where);)
	{
		if ($k)
		{
			$ar_w[] = '`'.$k.'` = "'.$v.'"';
		}
		else
		{
			$ar_w[] = $v;
		}
	}
	$where = implode(' AND ', $ar_w);
	$query = 'DELETE FROM `'.$table.'` WHERE '.$where;
	return $query;
}

#### /mySQL ####
?>