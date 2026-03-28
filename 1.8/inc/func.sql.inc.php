<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW')) {
    die('<!-- Not in App  -->');
}
/**
 *   Functions to handle numerios SQL-requests.
 */

/**
 * Adds or updates a term.
 * @param array $arPre Data in structured array
 * @param int $id_dict Dictionary id
 * @param array $arStop Stop words
 * @param int $in_term Do search in term or not
 * @param int $is_specialchars Is allow special characters in a term
 * @param int $is_overwrite Overwrite if already exist
 * @param int $intLine Line number, used for Import to report
 * @param int $isDelete Always delete (all checks for existent term will be skipped)
 */
function gwAddTerm($arPre, $id_dict, $arStop, $in_term, $is_specialchars, $is_overwrite, $intLine = 0, $isDelete = 0)
{
    global $oDom, $oFunc, $oDb, $oSqlQ, $oCase, $oL, $oHtml, $oSess;
    global $arFields, $arDictParam, $arTermParam, $cnt, $tid, $sys;
    global $gw_this, $qT;

#	@header("content-type: text/html; charset=utf-8");

    $id_w = $oDb->NextId($arDictParam['tablename']);
    $isQ = 1;
    $isCleanMap = 0;
    $id_old = isset($tid) ? $tid : 0;
    $queryA = $qT = [];
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
    $qT['id'] = $oDom->get_attribute('id', 'term', $arPre['term']);
    $qT['id'] = preg_replace("/[^0-9]/", '', trim($qT['id']));
    /* */
    if (!$isDelete) {
        /* -- Check for an existed term -- */
        /* prepare keywords for a term */
        if ($is_specialchars) {
            /* Keep specials */
            $str_term_filtered = $oCase->nc($str_term_filtered);
            $str_term_filtered = gw_text_wildcars($str_term_filtered);
            $arKeywordsT = text2keywords($oCase->rm_($str_term_filtered), 1);
        } else {
            /* Remove specials */
            $str_term_filtered = text_normalize($str_term_filtered);
            $arKeywordsT = text2keywords($str_term_filtered, $arDictParam['min_srch_length']);
        }
        /* Are there any keywords? */
        $isTermEmpty = (empty($arKeywordsT) && (strlen(trim($str_term_filtered)) == 0)) ? 1 : 0;
        if (!$isTermEmpty) {
            /* no empty keywords for a term */
            /* SQL-query */
            /* Get existent keywords, standard mode */
            $word_srch_sql = ($qT['id'] != '') ? 't.id = "' . $qT['id'] . '"' : '';
            if ($word_srch_sql == '') {
                $word_srch_sql = "k.word_text IN ('" . implode("', '", $arKeywordsT) . "')";
            }
            $sql = $oSqlQ->getQ(
                'get-term-exists',
                TBL_WORDLIST,
                $arDictParam['tablename'],
                $id_dict,
                $in_term,
                $word_srch_sql
            );
            /* Get existent keywords, keep specialchars mode */
            if (($is_specialchars) || empty($arKeywordsT)) {
                $ar_chars_sql = [
                    '\\'  => '\\\\',
                    '\\%' => '\\\\\\\%',
                    '\\_' => '\\\\\\\_',
                    '\\"' => '\\\\\\\"',
                    "\\'" => "\\\\\\\'",
                ];
                $sql = $oSqlQ->getQ(
                    'get-term-exists-spec',
                    $arDictParam['tablename'],
                    str_replace(array_keys($ar_chars_sql), array_values($ar_chars_sql), gw_addslashes($str_term_src))
                );
            }
            $arSql = $oDb->sqlExec($sql);
#			prn_r($arSql, __LINE__ . '<br />' . $sql);
            $isTermNotMatched = 1; // `No term found' by default

            for (; list($arK, $arV) = each($arSql);) // compare founded values (Q) with imported (T)
            {
                $id_old = $arV['id']; // get ID for existent keywords.
                if ($id_old == $qT['id']) {
                    $isTermNotMatched = 0;
                    break;
                }
                if ($is_specialchars) {
                    /* 1 - is the minimum length */
                    $arKeywordsQ = text2keywords(text_normalize($arV['term']), 1);
                } else {
                    /* $arDictParam['min_srch_length'] - is the minimum length */
                    $arKeywordsQ = text2keywords(text_normalize($arV['term']), $arDictParam['min_srch_length']);
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
            if ($isTermNotMatched > 0) {
                $isQ = 1;
                $isTermExist = 0;
            } else {
                $isTermExist = 1;
            }
        } // !$isTermEmpty
    }
    /* */
    if (!isset($str_term_filtered)) {
        $str_term_filtered = gw_text_wildcars($str_term_filtered);
        $str_term_filtered = text_normalize($str_term_filtered);
    }
    /* 21 jan 2006: new `date_created' for new terms */
    /* 23 apr 2008: subtract 61 second */
    $qT['date_created'] = isset($arPre['date_created']) ? $arPre['date_created'] : $sys['time_now_gmt_unix'];
    $qT['date_modified'] = $sys['time_now_gmt_unix'] - 61;
    $qT['date_created'] -= 61;
    /* 21 jul 2003: Better protection by adding random Next ID number */
    $arTermParam['tid'] = $qT['id'] = ($qT['id'] == '') ? mt_rand(
        $id_w,
        ($sys['leech_factor'] * 2) + $id_w
    ) : $qT['id'];
    $qT['term'] = $str_term_src;
    /* 15 sep 2007: Term URI */
    /* 08 apr 2008: Make link to added term */
    /* 11 apr 2008: Better URI */
    /* 23 apr 2008: Even better URI. Added transliteration. */
    $qT['term_uri'] = $oDom->get_attribute('uri', 'term', $arPre['term']);
    $qT['term_uri'] = ($qT['term_uri'] == '') ? $qT['id'] . '-' . $oCase->translit(
            $oCase->lc($str_term_filtered)
        ) : $qT['term_uri'];
    $qT['term_uri'] = $oCase->rm_entity($qT['term_uri']);
    $qT['term_uri'] = preg_replace('/[^0-9A-Za-z_-]/', '-', $qT['term_uri']);
    $qT['term_uri'] = preg_replace('/-{2,}/', '-', $qT['term_uri']);
    if ($qT['term_uri'] == '-') {
        $qT['term_uri'] = $qT['id'] . '-';
    }
    $arTermParam['term_uri'] = $qT['term_uri'];

    $qT['defn'] =& $arPre['parameters']['xml'];
    /* Alphabetic orders 1,2,3 */
    $qT['term_1'] = $oDom->get_attribute('t1', 'term', $arPre['term']);
    $qT['term_2'] = $oDom->get_attribute('t2', 'term', $arPre['term']);
    $qT['term_3'] = $oDom->get_attribute('t3', 'term', $arPre['term']);
    /* -- Custom Alphabetic Toolbar -- */
    /* Select custom rules for uppercasing */
    $sql = 'SELECT az_value, az_value_lc FROM `' . $sys['tbl_prefix'] . 'custom_az` WHERE `id_profile` = "' . $arDictParam['id_custom_az'] . '"';
    $arSqlAz = $oDb->sqlRun($sql, 'st');
    for (; list($arK, $arV) = each($arSqlAz);) {
        $str_term_src = str_replace($arV['az_value_lc'], $arV['az_value'], $str_term_src);
    }
    /* Unicode uppercase */
    $str_term_src_uc = $oCase->uc($str_term_src);
    /* 1.8.7: Custom sorting order */
    $qT['term_order'] = $oDom->get_attribute('term_order', 'term', $arPre['term']);
    $qT['term_order'] = ($qT['term_order'] == '') ? $str_term_src_uc : $qT['term_order'];
    $qT['term_a'] = $qT['term_b'] = $qT['term_c'] = $qT['term_d'] = $qT['term_e'] = $qT['term_f'] = 0;
    /* Prepare A, AAZZ, AAAZZZ */
    $qT['term_3'] = ($qT['term_3'] == '') ? mb_substr(
        $str_term_filtered,
        2,
        1,
        $sys['internal_encoding']
    ) : $qT['term_3'];
    $qT['term_2'] = ($qT['term_2'] == '') ? mb_substr(
        $str_term_filtered,
        1,
        1,
        $sys['internal_encoding']
    ) : $qT['term_2'];
    $qT['term_1'] = ($qT['term_1'] == '') ? mb_substr(
        $str_term_filtered,
        0,
        1,
        $sys['internal_encoding']
    ) : $qT['term_1'];
    /* */
    $ar_field_names = ['a', 'b', 'c', 'd', 'e', 'f'];
    preg_match_all("/./u", $qT['term_order'], $ar_letters);
    for (; list($cnt_letter, $letter) = each($ar_letters[0]);) {
        if (isset($ar_field_names[$cnt_letter])) {
            $qT['term_' . $ar_field_names[$cnt_letter]] = text_str2ord($letter);
        }
    }
    /* Fix htmlspecial characters */
    $qT['term_3'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_3']));
    $qT['term_2'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_2']));
    $qT['term_1'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_1']));
    /* */
    $qT['is_active'] = $oDom->get_attribute('is_active', 'term', $arPre['term']);
    $qT['is_complete'] = $oDom->get_attribute('is_complete', 'term', $arPre['term']);
#prn_r($qT, __LINE__.__FILE__);
#exit;
    if ($isDelete || $isTermExist) {
        /* Assign Term ID from previously added term */
        $arTermParam['tid'] = $id_old;
        if ($is_overwrite) {
            $qT['id'] = $id_old;
            $isCleanMap = 1;
            $isTermExist = 0;
#			$queryA[] = $oSqlQ->getQ('del-term_id', $arDictParam['tablename'], $id_old, $id_dict);
        }
    }
    if (!$isDelete && $isTermExist) {
        /* Term already exists */
        $ar_matched_terms = [];
        for (reset($arSql); list($arK, $arV) = each($arSql);) {
            $ar_matched_terms[] = $oHtml->a(
                $sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_TERMS . '&id=' . $id_dict . '&tid=' . $arV['id'],
                $arV['term'],
                '',
                '',
                $oL->m('3_edit')
            );
        }
        $str_line = implode(' | ', $ar_matched_terms);
        $queryA = '<dl style="margin:0">';
        $queryA .= '<dt class="xu red">' . $oL->m('reason_25') . ' - <strong>' . $str_term_src . '</strong></dt>';
        $queryA .= '<dd class="termpreview">' . $str_line . '</dd>';
        $queryA .= '</dl>';
        $isQ = 0;
    }
    /* Allow query */
    if ($isQ) {
        /* Prepare keywords per field */
#		$ot = new gw_timer('addterm');
        for (reset($arFields); list($fK, $fV) = each($arFields);) {
            // Init
            $arKeywords[$fK] = [];
            //
#			$int_min_length = (isset($fV[2]) && ($fV[2] != 'auto') && ($fV[2] != '')) ? $fV[2] : $arDictParam['min_srch_length'];
#			$int_min_length = (!isset($fV[2]) || ($fV[2] == 'auto') ) ? $int_min_length : $fV[2];
            //
            // Make keywords from array
            // space is required, otherwise `...word</defn><defn>word...' will become `wordword'
            $tmpStr = '';
            if (isset($arPre[$fV[0]])) {
                $tmpStr = $oDom->get_content($arPre[$fV[0]]);
            }
            if ($tmpStr != '') // do not parse empty strings
            {
                // Get maximum search length per field
                $int_min_length = $fV[2];
                if (is_string($int_min_length)) {
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
                $arKeywords[$fK] = text2keywords(
                    gw_text_sql(text_normalize($tmpStr)),
                    $int_min_length,
                    25,
                    $sys['internal_encoding']
                );
                /* Remove stopwords from parsed strings only (others are empty) */
                $arKeywords[$fK] = gw_array_exclude($arKeywords[$fK], $arStop);
            }
        }
        /* keywords convertion time */
#		prn_r( $arKeywords );
#		print $ot->endp(__LINE__, __FILE__);
#		exit;
        /* Remove double keywords from definition */
        for (reset($arFields); list($fK, $fV) = each($arFields);) {
            if ($fK != 0) {
                $arKeywords[0] = gw_array_exclude($arKeywords[0], $arKeywords[$fK]);
            }
        }
        /** Keywords were prepared */
        /** Add keywords to database! */
#       prn_r($arStop);
#       prn_r($arKeywords);
#       prn_r($arPre);
        gw_add_keywords($id_dict, $qT['id'], $arKeywords, $id_old, $isCleanMap, $qT['date_created']);
        $qT['int_bytes'] = strlen($qT['defn']);
        /* Checksum */
        $qT['crc32u'] = crc32($str_term_src_uc);
        /* Add User ID to term */
        if ($gw_this['vars'][GW_ACTION] == GW_A_ADD) {
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
        for (; list($k, $v) = each($gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_TERM]);) {
            if (function_exists($v)) {
                $v();
            }
        }

        /* REPLACE or UPDATE */
        if ($isDelete || $isTermExist) {
            if ($is_overwrite) {
                unset($qT['id']);
                $queryA[] = gw_sql_update($qT, $arDictParam['tablename'], 'id = "' . $id_old . '"');
            }
        } else {
            $id_old = $qT['id'];
            $queryA[] = gw_sql_insert($qT, $arDictParam['tablename'], 1);
        }

        /* 23 Nov 2007: history of changes */
        $qT['id_term'] = $id_old;
        $qT['id_dict'] = $arDictParam['id'];
        $qT['id_user'] = $oSess->id_user;
        $qT['keywords'] = serialize($arKeywords);
        unset($qT['id']);

        $queryA[] = gw_sql_insert($qT, $sys['tbl_prefix'] . 'history_terms', 1);
        /* Assign edited term to user */
        $queryA[] = gw_sql_replace($q2, TBL_MAP_USER_TERM, 1);
        /* Check table to keep indexes */
        $arQuery[] = 'CHECK TABLE `' . $arDictParam['tablename'] . '`';
    } /* is_query allowed */
    return $queryA;
}



/**
 * Add keywords into wordlist and wordmap tables.
 *
 * @param int   $dictId      Dictionary ID.
 * @param int   $termId      Term ID.
 * @param array $keywords    Keywords grouped by field ID.
 * @param int   $oldTermId   Previous term ID for cleanup.
 * @param int   $isClean     Clean existing map before insert.
 * @param int   $dateCreated Term creation timestamp.
 *
 * @return void
 */
function gw_add_keywords($dictId, $termId, array $keywords, $oldTermId, $isClean, $dateCreated)
{
    global $sys, $oDb, $oSqlQ;

    $keywordIndex = gw_build_keyword_index($keywords);

    if (empty($keywordIndex)) {
        gw_handle_empty_keyword_index($dictId, $oldTermId, $isClean);

        return;
    }

    $existingWords = gw_get_existing_words(array_keys($keywordIndex));

    $queries = [];

    if ($isClean) {
        $queries[] = $oSqlQ->getQ('del-wordmap-by-term-dict', $oldTermId, $dictId);
    }

    $existingMapQueries = gw_build_existing_wordmap_queries(
        $existingWords,
        $keywordIndex,
        $dictId,
        $termId,
        $dateCreated
    );

    $newWordData = gw_build_new_word_queries(
        $keywordIndex,
        $dictId,
        $termId,
        $dateCreated
    );

    if (!empty($existingMapQueries)) {
        $queries[] = implode('', $existingMapQueries);
    }

    if (!empty($newWordData['wordQueries'])) {
        $queries[] = implode('', $newWordData['wordQueries']);
    }

    if (!empty($newWordData['mapQueries'])) {
        $queries[] = implode('', $newWordData['mapQueries']);
    }

    gw_debug_or_exec_queries($queries);
}



/**
 * Build keyword index by field ID.
 *
 * Result format:
 * array(
 *     'keyword1' => array(fieldId1 => 1, fieldId2 => 1),
 *     'keyword2' => array(fieldId3 => 1),
 * )
 *
 * @param array $keywords Keywords grouped by field ID.
 *
 * @return array
 */
function gw_build_keyword_index(array $keywords)
{
    global $arFields;

    $keywordIndex = [];

    foreach ($arFields as $fieldId => $fieldData) {
        if (!isset($keywords[$fieldId]) || !is_array($keywords[$fieldId])) {
            continue;
        }

        foreach ($keywords[$fieldId] as $keyword) {
            if ($keyword === '') {
                continue;
            }

            if (!isset($keywordIndex[$keyword])) {
                $keywordIndex[$keyword] = [];
            }

            $keywordIndex[$keyword][$fieldId] = 1;
        }
    }

    return $keywordIndex;
}


/**
 * Clean wordmap when keyword index is empty.
 *
 * @param int $dictId    Dictionary ID.
 * @param int $oldTermId Previous term ID.
 * @param int $isClean   Clean existing map before insert.
 *
 * @return void
 */
function gw_handle_empty_keyword_index($dictId, $oldTermId, $isClean)
{
    global $sys, $oDb, $oSqlQ;

    if (!$isClean) {
        return;
    }

    $query = $oSqlQ->getQ('del-wordmap-by-term-dict', $oldTermId, $dictId);

    if (!empty($sys['isDebugQ'])) {
        $queryDebug = gw_highlight_sql([$query]);
        print '<ul class="gwsql"><li>' . implode(';</li><li>', $queryDebug) . ';</li></ul>';

        return;
    }

    if ($oDb->sqlExec($query) === false) {
        print '<li class="xt">Error: cannot exec query: ' . $query . ';</li>';
    }
}



/**
 * Get existing words from wordlist.
 *
 * @param array $keywords Keyword list.
 *
 * @return array Word map: word_text => word_id
 */
function gw_get_existing_words(array $keywords)
{
    global $oDb, $oSqlQ;

    if (empty($keywords)) {
        return [];
    }

    $wordTextSql = "'" . implode("', '", $keywords) . "'";
    $rows = $oDb->sqlExec($oSqlQ->getQ('get-word', TBL_WORDLIST, $wordTextSql));

    if (empty($rows)) {
        return [];
    }

    $existingWords = [];

    foreach ($rows as $row) {
        $existingWords[$row['word_text']] = $row['word_id'];
    }

    return $existingWords;
}



/**
 * Build wordmap queries for existing words.
 *
 * Existing words are removed from keyword index.
 *
 * @param array $existingWords Existing words: word_text => word_id
 * @param array $keywordIndex  Keyword index, passed by reference.
 * @param int   $dictId        Dictionary ID.
 * @param int   $termId        Term ID.
 * @param int   $dateCreated   Creation timestamp.
 *
 * @return array
 */
function gw_build_existing_wordmap_queries(array $existingWords, array &$keywordIndex, $dictId, $termId, $dateCreated)
{
    $queries = [];
    $insertIndex = 0;

    foreach ($existingWords as $wordText => $wordId) {
        if (!isset($keywordIndex[$wordText])) {
            continue;
        }

        foreach ($keywordIndex[$wordText] as $fieldId => $tmp) {
            $queryData = [
                'word_id' => $wordId,
                'term_id' => $termId,
                'dict_id' => $dictId,
                'date_created' => $dateCreated,
                'term_match' => $fieldId,
            ];

            $queries[] = gw_sql_insert($queryData, TBL_WORDMAP, 1, $insertIndex);
            $insertIndex++;
        }

        unset($keywordIndex[$wordText]);
    }

    return $queries;
}

/**
 * Build queries for new words and their mappings.
 *
 * @param array $keywordIndex Keyword index.
 * @param int   $dictId       Dictionary ID.
 * @param int   $termId       Term ID.
 * @param int   $dateCreated  Creation timestamp.
 *
 * @return array
 */
function gw_build_new_word_queries(array $keywordIndex, $dictId, $termId, $dateCreated)
{
    global $oDb;

    $wordQueries = [];
    $mapQueries = [];

    if (empty($keywordIndex)) {
        return [
            'wordQueries' => $wordQueries,
            'mapQueries' => $mapQueries,
        ];
    }

    $nextWordId = $oDb->NextId(TBL_WORDLIST, 'word_id');
    $wordInsertIndex = 0;
    $mapInsertIndex = 0;

    foreach ($keywordIndex as $keyword => $fieldIds) {
        $nextWordId++;

        $wordQueryData = [
            'word_id' => $nextWordId,
            'word_text' => $keyword,
        ];

        $wordQueries[] = gw_sql_insert($wordQueryData, TBL_WORDLIST, 1, $wordInsertIndex);
        $wordInsertIndex++;

        foreach ($fieldIds as $fieldId => $tmp) {
            $mapQueryData = [
                'word_id' => $nextWordId,
                'dict_id' => $dictId,
                'term_id' => $termId,
                'date_created' => $dateCreated,
                'term_match' => $fieldId,
            ];

            $mapQueries[] = gw_sql_insert($mapQueryData, TBL_WORDMAP, 1, $mapInsertIndex);
            $mapInsertIndex++;
        }
    }

    return [
        'wordQueries' => $wordQueries,
        'mapQueries' => $mapQueries,
    ];
}


/**
 * Print queries in debug mode or execute them.
 *
 * @param array $queries SQL queries.
 *
 * @return void
 */
function gw_debug_or_exec_queries(array $queries)
{
    global $sys, $oDb;

    if (empty($queries)) {
        return;
    }

    if (!empty($sys['isDebugQ'])) {
        $queryDebug = gw_highlight_sql($queries);
        print '<ul class="gwsql"><li>' . implode(';</li><li>', $queryDebug) . ';</li></ul>';

        return;
    }

    $i = 1;
    foreach ($queries as $query) {
        if ($oDb->sqlExec($query) === false) {
            print '<li class="xt">[' . $i . '] Error: cannot exec query: ' . $query . ';</li>';
        }
        $i++;
    }
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
function gw_add_keywords2($id_dict, $id_term, $arKeywords, $termIdOld, $isClean, $date_created)
{
    global $arFields, $sys, $oDb, $oSqlQ;

    $arQuery = [];
    $arSql = [];
    $arQueryMapExist = [];
    $arQueryWord = [];
    $arQueryMap = [];

    // Keyword index:
    // array(
    //     'keyword1' => array(field_id_1 => 1, field_id_2 => 1),
    //     'keyword2' => array(field_id_3 => 1)
    // )
    $keyword_index = [];

    foreach ($arFields as $id_field => $fV) {
        if (!isset($arKeywords[$id_field]) || !is_array($arKeywords[$id_field])) {
            continue;
        }

        foreach ($arKeywords[$id_field] as $keyword) {
            if ($keyword === '') {
                continue;
            }

            if (!isset($keyword_index[$keyword])) {
                $keyword_index[$keyword] = [];
            }

            $keyword_index[$keyword][$id_field] = 1;
        }
    }

    if (empty($keyword_index)) {
        if ($isClean) {
            $q = $oSqlQ->getQ('del-wordmap-by-term-dict', $termIdOld, $id_dict);

            if (!empty($sys['isDebugQ'])) {
                $arQueryDebug = gw_highlight_sql([$q]);
                print '<ul class="gwsql"><li>' . implode(';</li><li>', $arQueryDebug) . ';</li></ul>';

                return;
            }

            if ($oDb->sqlExec($q) === false) {
                print '<li class="xt">Error: cannot exec query: ' . $q . ';</li>';
            }
        }

        return;
    }

    // Get existing keywords from wordlist
    $word_text_sql = "'" . implode("', '", array_keys($keyword_index)) . "'";
    $arSql = $oDb->sqlExec($oSqlQ->getQ('get-word', TBL_WORDLIST, $word_text_sql));

    // Existing words:
    // array('keyword' => word_id)
    $existing_words = [];

    if (!empty($arSql)) {
        foreach ($arSql as $row) {
            $existing_words[$row['word_text']] = $row['word_id'];
        }
    }

    // Clean old map once, if needed
    if ($isClean) {
        $arQuery[] = $oSqlQ->getQ('del-wordmap-by-term-dict', $termIdOld, $id_dict);
    }

    // Add mappings for existing keywords
    $cnt_map_exist = 0;

    foreach ($existing_words as $word_text => $word_id) {
        if (!isset($keyword_index[$word_text])) {
            continue;
        }

        foreach ($keyword_index[$word_text] as $id_field => $tmp) {
            $q2 = [
                'word_id'      => $word_id,
                'term_id'      => $id_term,
                'dict_id'      => $id_dict,
                'date_created' => $date_created,
                'term_match'   => $id_field,
            ];

            $arQueryMapExist[] = gw_sql_insert($q2, TBL_WORDMAP, 1, $cnt_map_exist);
            $cnt_map_exist++;

            // Remove existing keyword from "new words" pool
            unset($keyword_index[$word_text]);
        }
    }

    // Add new keywords into wordlist and wordmap
    $next_word_id = $oDb->NextId(TBL_WORDLIST, 'word_id');
    $cnt_word = 0;
    $cnt_map = 0;

    foreach ($keyword_index as $newkeyword => $field_ids) {
        $next_word_id++;

        $q1 = [
            'word_id'   => $next_word_id,
            'word_text' => $newkeyword,
        ];

        $arQueryWord[] = gw_sql_insert($q1, TBL_WORDLIST, 1, $cnt_word);
        $cnt_word++;

        foreach ($field_ids as $id_field => $tmp) {
            $q2 = [
                'word_id'      => $next_word_id,
                'dict_id'      => $id_dict,
                'term_id'      => $id_term,
                'date_created' => $date_created,
                'term_match'   => $id_field,
            ];

            $arQueryMap[] = gw_sql_insert($q2, TBL_WORDMAP, 1, $cnt_map);
            $cnt_map++;
        }
    }

    if (!empty($arQueryMapExist)) {
        $arQuery[] = implode('', $arQueryMapExist);
    }

    if (!empty($arQueryWord)) {
        $arQuery[] = implode('', $arQueryWord);
    }

    if (!empty($arQueryMap)) {
        $arQuery[] = implode('', $arQueryMap);
    }

    if (!empty($sys['isDebugQ'])) {
        $arQueryDebug = gw_highlight_sql($arQuery);
        print '<ul class="gwsql"><li>' . implode(';</li><li>', $arQueryDebug) . ';</li></ul>';

        return;
    }

    $i = 1;
    foreach ($arQuery as $vq) {
        if ($oDb->sqlExec($vq) === false) {
            print '<li class="xt">[' . $i . '] Error: cannot exec query: ' . $vq . ';</li>';
        }
        $i++;
    }
}

/**
 * Get shared database instance.
 *
 * @return gwtkDb
 */
function gw_get_db_instance()
{
    global $oDb, $sys;

    if (!defined('IS_CLASS_DB')) {
        include_once $sys['path_gwlib'] . '/class.db.mysqli.php';
    }

    if (!isset($oDb)) {
        $oDb = new gwtkDb();
    }

    return $oDb;
}

/**
 * Get database list.
 *
 * @return array
 */
function gw_get_databases()
{
    $db = gw_get_db_instance();

    return $db->get_databases();
}

/**
 * Get database table info.
 *
 * @param string $tableName Database table name.
 *
 * @return array
 */
function gw_get_table_info($tableName)
{
    $db = gw_get_db_instance();

    return $db->table_info($tableName);
}

/**
 * Build REPLACE SQL query.
 *
 * - 26 Mar 2026:refactored
 * - 8 Feb 2005: hexadecimal
 * - 1, Oct 2002: added
 *
 * @param array $data Field => value map.
 * @param string $table Database table name.
 * @param int $isFields Include field names or not.
 *
 * @return string|false Complete SQL query or false on invalid input.
 */
function gw_sql_replace($data, $table, $isFields = 1)
{
    if (!is_array($data) || empty($data) || $table === '') {
        return '';
    }

    $fieldNames = [];
    $fieldValues = [];

    foreach ($data as $fieldName => $fieldValue) {
        if ($fieldName === '' || $fieldName === 0 || $fieldName === '0') {
            continue;
        }

        $fieldNames[] = str_replace('`', '``', $fieldName);
        $fieldValues[] = gw_sql_format_insert_value($fieldValue);
    }

    if (empty($fieldNames)) {
        return '';
    }

    $fieldsSql = '';
    if ($isFields) {
        $fieldsSql = ' (`' . implode('`, `', $fieldNames) . '`)';
    }

    return CRLF
        . 'REPLACE INTO `'
        . str_replace('`', '``', $table)
        . '`'
        . $fieldsSql
        . ' VALUES ('
        . implode(', ', $fieldValues)
        . ')';
}

/**
 * Build INSERT SQL query.
 *
 * - 26 Mar 2026: DELAYED inserts removed, refactored
 * - 22 Feb 2005: DELAYED inserts
 * - 8 Feb 2005: no quotes for hexadecimal values
 * - 11 aug 2003: do not enclose with quotes all numerals
 * - 4 Oct 2002: short INSERT format (intCnt = [ 0, 1, 2 ... ]
 * - 24 May 2002: $isFields -- include field names to query or not
 *
 * @param array $sqlNames Field => value map.
 * @param string $table Database table name.
 * @param int $isFields Include field names or not.
 * @param int $intCnt Append only VALUES part for multi-insert.
 * @param int $isDelayed Deprecated. INSERT DELAYED is obsolete and ignored in modern MySQL.
 *
 * @return string Complete SQL query part.
 */
function gw_sql_insert($sqlNames, $table, $isFields = 1, $intCnt = 0, $isDelayed = 0)
{
    if (!is_array($sqlNames) || empty($sqlNames) || $table === '') {
        return '';
    }

    $fieldNames = [];
    $fieldValues = [];

    foreach ($sqlNames as $fieldName => $fieldValue) {
        $fieldNames[] = str_replace('`', '``', $fieldName);
        $fieldValues[] = gw_sql_format_insert_value($fieldValue);
    }

    $fieldsSql = '';
    if ($isFields) {
        $fieldsSql = ' (`' . implode('`, `', $fieldNames) . '`)';
    }

    $valuesSql = implode(', ', $fieldValues);

    if (!$intCnt) {
        return CRLF
            . 'INSERT '
            . 'INTO `'
            . str_replace('`', '``', $table)
            . '`'
            . $fieldsSql
            . ' VALUES ('
            . $valuesSql
            . ')';
    }

    return ', (' . $valuesSql . ')';
}

/**
 * Format value for INSERT SQL.
 *
 * @param mixed $value Value to format.
 *
 * @return string SQL-ready value.
 */
function gw_sql_format_insert_value($value)
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    $value = gw_text_sql((string)$value);

    if ($value === '') {
        return "''";
    }

    // Allow trusted hexadecimal literal.
    if (preg_match('/^0x[0-9A-Fa-f]+$/', $value)) {
        return $value;
    }

    return "'" . $value . "'";
}


/**
 * Builds SQL UPDATE query string.
 *
 * @param array $sqlNames Column => value map
 * @param string $tableName Table name
 * @param string $where WHERE clause without the `WHERE` keyword
 *
 * @return string
 */
function gw_sql_update(array $sqlNames, $tableName, $where)
{
    $sqlParts = [];

    foreach ($sqlNames as $column => $value) {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $value = gw_text_sql($value);

        // For now strings are always quoted, including empty string
        $valueFormatted = "'" . $value . "'";

        $sqlParts[] = $column . ' = ' . $valueFormatted;
    }

    $sqlRatioStr = implode(', ', $sqlParts);

    return 'UPDATE `' . $tableName . '` SET ' . $sqlRatioStr . ' WHERE ' . $where;
}





/**
 * Escape string for MySQL SQL literal.
 *
 * Warning: this helper is intended for legacy code only.
 * Assumptions:
 * - connection charset is utf8
 * - SQL mode does not use NO_BACKSLASH_ESCAPES
 *
 * @param mixed $value
 * @return string
 */
function gw_text_sql($value)
{
    return strtr(
        (string) $value,
        [
            "\\" => "\\\\",
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "'" => "\\'",
            '"' => '\\"',
            "\x1a" => "\\Z",
        ]
    );
}

/**
 * Quote SQL identifier.
 *
 * @param string $name
 * @return string
 */
function gw_sql_identifier($name)
{
    return '`' . str_replace('`', '``', (string) $name) . '`';
}

/**
 * Convert PHP value to SQL literal.
 *
 * @param mixed $value
 * @return string
 */
function gw_sql_value($value)
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . gw_text_sql($value) . "'";
}

/**
 * Build DELETE query by equality conditions.
 *
 * @param string $table
 * @param array $where
 * @return string
 */
function gw_sql_delete($table, array $where)
{
    $where_parts = [];

    $table = (string) $table;
    if ($table === '' || empty($where)) {
        return '';
    }

    foreach ($where as $field => $value) {
        $field = (string) $field;
        if ($field === '') {
            continue;
        }

        if ($value === null) {
            $where_parts[] = gw_sql_identifier($field) . ' IS NULL';
            continue;
        }

        $where_parts[] = gw_sql_identifier($field) . ' = ' . gw_sql_value($value);
    }

    if (empty($where_parts)) {
        return '';
    }

    return 'DELETE FROM ' . gw_sql_identifier($table) . ' WHERE ' . implode(' AND ', $where_parts);
}


