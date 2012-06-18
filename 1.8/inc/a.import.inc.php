<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2004-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: a.import.inc.php 491 2008-06-13 10:05:06Z glossword_team $ -->');
}
/**
	Import. External utility for a dictionary.
	<line>
		<term t1="T" t2="TE" t3="TE" uri="term" is_active="1" is_complete="1" id="1"><![CDATA[term]]></term>
		<defn>
			<trsp><![CDATA[trsp]]></trsp>
			<abbr><![CDATA[abbr]]></abbr>
			<trns><![CDATA[trns]]></trns>
				<![CDATA[defn]]>
			<usg><![CDATA[usg]]></usg>
			<syn><![CDATA[syn]]></syn>
			<antonym><![CDATA[antonym]]></antonym>
			<see><![CDATA[see]]></see>
			<src><![CDATA[src]]></src>
			<address><![CDATA[address]]></address>
			<phone><![CDATA[phone]]></phone>
		</defn>
	</line>
 */

/* */
function text_parse_csv_column_input($t)
{
	$t = str_replace("'", '&#39;', $t);
	$t = str_replace(' "', ' &#34;', $t);
	$t = str_replace('& ', '&amp; ', $t);
	$t = preg_replace('/[^\\\\]\\\n/', "\n", $t);
	$t = preg_replace('/[^\\\\]\\\t/', "\t", $t);
	$t = str_replace('\\\n', '\n', $t);
	$t = str_replace('\\\r', '\r', $t);
	$t = str_replace('\\\t', '\t', $t);
	return $t;
}
/**
 *
 */
function gw_import_xml()
{
	global $oSess, $oDb, $oCase, $oSqlQ, $oFunc;
	global $sys, $str, $arReq, $arFields, $vars, $arBroken, $gw_this, $arDictParam;
	global $file_location, $arStatus, $xml_file;

	if (!isset($file_location['name']))
	{
		$file_location['name'] = '';
	}

	/* */
	$is_next_part = 0;

	/* Start timer */
	$oT = new gw_timer('parse');

	/* 1.8.4: Partial import */
	/* 1.8.10: Added import sessions */
	if (isset($gw_this['vars']['arPost']['id_session']))
	{
		/* Read session settings */
		$sql = 'SELECT date_start, int_items_total, int_items_passed, filename, int_bytes, settings FROM `'.$sys['tbl_prefix'].'import_sessions`
				WHERE id_session = "'.gw_text_sql($gw_this['vars']['arPost']['id_session']).'"
				LIMIT 1
		';
		$arSql = $oDb->sqlExec($sql);
		$arSql = isset($arSql[0]) ? $arSql[0] : array();

		$gw_this['vars']['arPost'] = array_merge($gw_this['vars']['arPost'], unserialize($arSql['settings']));
		$gw_this['vars']['int_items_total'] = $arSql['int_items_total'];
		$gw_this['vars']['int_items_passed'] = $arSql['int_items_passed'];
		$gw_this['vars']['date_start'] = $arSql['date_start'];
		$file_location['name'] = $arSql['filename'];
		$file_location['size'] = $arSql['int_bytes'];
		/* XML: Read data file */
		$arSql['lines_raw'] = $oFunc->file_get_contents( $sys['path_temporary'].'/import-'.$gw_this['vars']['arPost']['id_session'] );
		$ar_lines_raw = @unserialize($arSql['lines_raw']);
		unset($arSql);
	}
	else
	{
		/* Save user settings */
		$oSess->user_set('import_is_validate', $gw_this['vars']['arPost']['is_validate']);

		/* Uploaded file name */
		$xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
		$xml_file_new = $sys['path_temporary'].'/'.urlencode($sys['time_now'].'-'.$file_location['name']);
		if (is_uploaded_file($xml_file)
			&& move_uploaded_file($xml_file, $xml_file_new)
			)
		{
			$gw_this['vars']['arPost']['xml'] = $oFunc->file_get_contents($xml_file_new);
			unlink($xml_file_new);
		}

		/* Fix header if there are single <line>'s only */
		/*
		if (preg_match("/^([\s])*<line/", $gw_this['vars']['arPost']['xml']))
		{
			$gw_this['vars']['arPost']['xml'] = '<'.'?xml version="1.0" encoding="'.$sys['internal_encoding'].'"'.'?>' .
						 '<glossword>' . CRLF . $gw_this['vars']['arPost']['xml'] . '</glossword>';
		}
		*/
		$gw_this['vars']['int_bytes'] = strlen($gw_this['vars']['arPost']['xml']);

		/* XML-syntax check */
		$isPostError = 0;
		/* PHP4 */
		if ($gw_this['vars']['arPost']['is_validate'] && function_exists('xslt_create'))
		{
			include_once( $sys['path_include']. '/class.xmlparse.php' );
			$xml_parser = new xmlTinyParser();
			$is_return = $xml_parser->parse($gw_this['vars']['arPost']['xml']);
			if (!$is_return)
			{
				// Uploaded file can be too big,
				// so we do not need to put file contents into HTML-form,
				// show filename instead
				if (file_exists($xml_file) && is_uploaded_file($xml_file))
				{
					$gw_this['vars']['arPost']['xml'] = $file_location['name'];
				}
				$arBroken['xml'] = $isPostError = 1;
			}
		}
		if ($isPostError)
		{
			/* Call HTML-form again */
			$gw_this['vars']['arPost']['file_location'] = '';
			$gw_this['vars']['arPost']['csv'] = '';
			$gw_this['vars']['arPost']['xml'] = '';
			$str .= getFormImport($gw_this['vars']['arPost'], 1, $arBroken, $arReq[$gw_this['vars'][GW_TARGET]]);
			$str .= '<br />';
			return;
		}
		/* ... */
		/* Escape sequences */
		if ($gw_this['vars']['arPost']['is_convert_esc'])
		{
			$ar_esc = array('\r\n' => "\r\n", '\n' => "\n", '\r' => "\r", '\t' => "\t");
			$gw_this['vars']['arPost']['xml'] = str_replace(array_keys($ar_esc), array_values($ar_esc), $gw_this['vars']['arPost']['xml']);
		}
		/* Whitespace characters */
		if (!$gw_this['vars']['arPost']['is_whitespace'])
		{
			/* 0.005 for str_replace(), 0.052 for strstr() */
			$ar_esc = array("\r\n" => ' ', "\n" => ' ', "\r" => ' ', "\t" => ' ');
			$gw_this['vars']['arPost']['xml'] = str_replace(array_keys($ar_esc), array_values($ar_esc), $gw_this['vars']['arPost']['xml']);
		}
		/* convert old formats */
		if (!preg_match('/\]\]><\/term/', $gw_this['vars']['arPost']['xml']))
		{
			$rule_attr = '[A-Za-z0-9\:\-]';
			$rule_spaces = '[\x09\x0a\x0d\x20]';
			$rule_attr_regex = '/(?:^|'.$rule_spaces.')('.$rule_attr.'+)'.'('.$rule_spaces.'*=)(.*?[\'"])(?='.$rule_spaces.'|$)/xs';
			/* fix attributes */
			preg_match_all('/<term(.*?)>/', $gw_this['vars']['arPost']['xml'], $arTermParam);
			for (reset($arTermParam[1]); list($k1, $v1) = each($arTermParam[1]);)
			{
				if (!$v1){ continue; }
				$str_param = '';
				$ar_attr = $ar_newattr = array();
				if (!preg_match_all($rule_attr_regex, $v1, $ar_pairs, PREG_SET_ORDER))
				{
					continue;
				}
				foreach ($ar_pairs as $v )
				{
					$attr = strtolower( $v[1] );
					$val = trim($v[3]);
					$val = preg_replace('/(^["\'])/', "", $val);
					$val = preg_replace('/(["\']$)/', "", $val);
					$val = trim($val);
					$val = gw_htmlspecialamp(gw_unhtmlspecialamp($val));
					/* fix repeated attributes */
					if (isset($ar_attr[$attr]))
					{
						$ar_attr[$attr] .= $val;
					}
					else
					{
						$ar_attr[$attr] = $val;
					}
				}
				ksort($ar_attr);
				foreach ($ar_attr as $attr => $val)
				{
					$ar_newattr[] = $attr . '="' . $val.'"';
				}
				$gw_this['vars']['arPost']['xml'] = str_replace($v1, ' '.implode(' ', $ar_newattr), $gw_this['vars']['arPost']['xml']);
 			}
 			/* Import from old Glossword versions */
			$gw_this['vars']['arPost']['xml'] = str_replace('<defn>', '<defn><![CDATA[', str_replace('</defn>', ']]></defn>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = preg_replace('/<term(.*?)>/', '<term\\1><![CDATA[', str_replace('</term>', ']]></term>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<trsp>', ']]><trsp><![CDATA[', str_replace('</trsp>', ']]></trsp><![CDATA[', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = preg_replace('/<abbr(.*?)>/', ']]><abbr\\1><![CDATA[', str_replace('</abbr>', ']]></abbr><![CDATA[', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = preg_replace('/<trns(.*?)>/', ']]><trns\\1><![CDATA[', str_replace('</trns>', ']]></trns><![CDATA[', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<usg>', ']]><usg><![CDATA[', str_replace('</usg>', ']]></usg>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = preg_replace('/<see(.*?)>/', ']]><see\\1><![CDATA[', str_replace('</see>', ']]></see>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = preg_replace('/<syn(.*?)>/', ']]><syn\\1><![CDATA[', str_replace('</syn>', ']]></syn>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<src>', ']]><src><![CDATA[', str_replace('</src>', ']]></src>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<phone>', ']]><phone><![CDATA[', str_replace('</phone>', ']]></phone>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<address>', ']]><address><![CDATA[', str_replace('</address>', ']]></address>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<antonym>', ']]><antonym><![CDATA[', str_replace('</antonym>', ']]></antonym>', $gw_this['vars']['arPost']['xml']));
			$gw_this['vars']['arPost']['xml'] = str_replace('<![CDATA[<![CDATA[', '<![CDATA[', $gw_this['vars']['arPost']['xml']);
			$gw_this['vars']['arPost']['xml'] = str_replace(']]>]]>', ']]>', $gw_this['vars']['arPost']['xml']);
			$arTagNames = array();
			for (reset($arFields); list($fK, $fV) = each($arFields);)
			{
				$arTagNames[] = $fV[0];
			}
			/* </syn>  ]]><syn> */
			$gw_this['vars']['arPost']['xml'] = preg_replace('/('.implode('|',$arTagNames).')>(\s){0,}]]></', '\\1\\2><', $gw_this['vars']['arPost']['xml']);
			/* </phone>]]></defn> */
			$gw_this['vars']['arPost']['xml'] = preg_replace('/\/('.implode('|',$arTagNames).')>]]>/', '/\\1\\2>', $gw_this['vars']['arPost']['xml']);
			/* </trsp><![CDATA[<defn>, </trsp>]]><trns */
			$gw_this['vars']['arPost']['xml'] = str_replace('</trsp><![CDATA[', '</trsp>', $gw_this['vars']['arPost']['xml']);
			$gw_this['vars']['arPost']['xml'] = str_replace('</trsp>]]>', '</trsp>', $gw_this['vars']['arPost']['xml']);

			$gw_this['vars']['arPost']['xml'] = str_replace('<![CDATA[ ]]>', ' ', $gw_this['vars']['arPost']['xml']);
		}
	}
#	$cnt = 0;
	$queryA = array();
	
	$gw_this['vars']['arPost']['xml'] = str_replace('</glossword>', '', $gw_this['vars']['arPost']['xml']);

	/* Create array with raw XML data, per <line> */
	/* <line> is not a regular HTML-tag. */
	if (!isset($ar_lines_raw))
	{
		$ar_lines_raw = explode('<line>', $gw_this['vars']['arPost']['xml']);
		unset($ar_lines_raw[0]);
		$gw_this['vars']['arPost']['xml'] = '';

		/* The total number of terms, used in import sessions, in statistics */
		$gw_this['vars']['int_items_total'] = sizeof($ar_lines_raw);
	}

	/* Get stopwords */
	$arStop = array();
	if ($arDictParam['is_filter_stopwords'])
	{
		$arStop = gw_get_stopwords($arDictParam);
	}

	/* Select custom rules for uppercasing */
	$sql = 'SELECT `az_value`, `az_value_lc` FROM `'.$sys['tbl_prefix'].'custom_az` WHERE `id_profile` = "'.$arDictParam['id_custom_az'].'"';
	$arCustomAZOrder = $oDb->sqlRun($sql, 'st');

	/* Parsing time */
	$time_spend = $oT->end('parse');
	/* Memory usage */
	$sys['memory_spend'] = memory_get_usage();
	
	$arStatus = array();

	/* Do import using DOM model */
	$oDom = new gw_domxml;
	$oDom->is_skip_white = 0;

	/* Enter debug mode. XML. None of `INSERT' or `DELETE' queries will be executed. */
#$sys['isDebugQ'] = 1;
	if (empty($ar_lines_raw))
	{
		$arBroken['xml'] = 1;
		$gw_this['vars']['arPost']['file_location'] = '';
		$gw_this['vars']['arPost']['csv'] = '';
		$gw_this['vars']['arPost']['xml'] = '';
		$str .= '<p class="xt red">'.$oDom->msg_error.'</p>';
		$oDom->strData = '';
		/* Call HTML-form again */
		$str .= getFormImport($gw_this['vars']['arPost'], 1, $arBroken, array('xml'));
		$str .= '<br />';
		return;
	}
	/* For each raw <line> */
	for (reset($ar_lines_raw); list($k_raw, $v_raw) = each($ar_lines_raw);)
	{
		/* Start timer per a term */
		$time_start = list($sm, $ss) = explode(' ', microtime());

		/* Before import: Create buffer 3 seconds */
		if ($time_spend >= ($gw_this['vars']['arPost']['max_execution_time']-2))
		{
			$is_next_part = 1;
			break;
		}
		
		/* Create new DOM object */
		$oDom->strData = '<xml><line>'.$v_raw.'</xml>';
		$oDom->parse();
		$arXmlLine = $oDom->get_elements_by_tagname('line');
		if ( !isset($arXmlLine[0]) )
		{
			$arBroken['xml'] = 1;
			$gw_this['vars']['arPost']['file_location'] = '';
			$gw_this['vars']['arPost']['csv'] = '';
			$gw_this['vars']['arPost']['xml'] = '<line>'.implode(CRLF.'<line>', $ar_lines_raw);
			$str .= '<p class="xt red">'.$oDom->msg_error.'</p>';
			$oDom->strData = '';
			/* Call HTML-form again */
			$str .= getFormImport($gw_this['vars']['arPost'], 1, $arBroken, array('xml'));
			$str .= '<br />';
			return;
		}
		$arXmlLine = $arXmlLine[0];
		$qT = $arTermMap = $arQ = array();
		$id_term = $id_term_old = $is_clean_map = $is_term_exists = 0;
		$ar_keywords_raw = $ar_keywords = array();
		if (!isset($arXmlLine['children'])) { continue; }
		for (reset($arXmlLine['children']); list($k2, $v2) = each($arXmlLine['children']);)
		{
			if (!is_array($v2)){ continue; }
			switch ($v2['tag'])
			{
				case 'term':
					/* 22 jul 2003, 26 june 2005: Custom Term ID */
					$qT['id'] = $oDom->get_attribute('id', $v2['tag'], $v2);
					$qT['id'] = preg_replace("/[^0-9]/", '', trim($qT['id']));
					$id_term_db = $oDb->MaxId($arDictParam['tablename'], 'id');
					/* 21 jul 2003: Better protection by using random number for Next ID */
					$qT['id'] = ($qT['id'] == '') ? mt_rand($id_term_db, ($sys['leech_factor']) + $id_term_db) : $qT['id'];

					/* Used for keywords */
					$str_term_filtered = trim(gw_unhtmlspecialamp($oDom->get_content($v2)));
					/* Used in database */
					$str_term_src = gw_htmlspecialamp($str_term_filtered);

					$qT['date_modified'] = $oDom->get_attribute('date_modified', $v2['tag'], $v2);
					$qT['date_modified'] = ($qT['date_modified'] == '') ? $sys['time_now_gmt_unix'] - 60 : $qT['date_modified'];
					$qT['date_created'] = $oDom->get_attribute('date_created', $v2['tag'], $v2);
					$qT['date_created'] = ($qT['date_created'] == '') ? $sys['time_now_gmt_unix'] - 60 : $qT['date_created'];
					$qT['term_3'] = $oDom->get_attribute('t3', $v2['tag'], $v2);
					$qT['term_2'] = $oDom->get_attribute('t2', $v2['tag'], $v2);
					$qT['term_1'] = $oDom->get_attribute('t1', $v2['tag'], $v2);

					/* 23 apr 2008: Even better URI. Added transliteration. */
					$qT['term_uri'] = $oDom->get_attribute( 'uri', $v2['tag'], $v2 );
					$qT['term_uri'] = ($qT['term_uri'] == '') ? $qT['id'].'-'.$oCase->translit( $oCase->lc($str_term_filtered)) : $qT['term_uri'];
					$qT['term_uri'] = $oCase->rm_entity($qT['term_uri']);
					$qT['term_uri'] = preg_replace('/[^0-9A-Za-z_-]/', '-', $qT['term_uri']);
					$qT['term_uri'] = preg_replace('/-{2,}/', '-', $qT['term_uri']);
					if ($qT['term_uri'] == '-')
					{
						$qT['term_uri'] = $qT['id'].'-';
					}
					/* */
					$qT['is_active'] = intval($oDom->get_attribute('is_active', $v2['tag'], $v2));
					$qT['is_active'] = ($gw_this['vars']['arPost']['is_active']) ? $qT['is_active'] : 0;
					$qT['is_complete'] = intval($oDom->get_attribute('is_complete', $v2['tag'], $v2));
#prn_r( $qT );
					$str_term_filtered = $oCase->nc($str_term_filtered);
					$str_term_filtered = gw_text_wildcars($str_term_filtered);
					$ar_keywords[1] = text2keywords($oCase->rm_($str_term_filtered), $arDictParam['min_srch_length'], 25);

					if ($gw_this['vars']['arPost']['is_check_exist'])
					{
						/* Strict search for an existent term including special chars */
						if ($gw_this['vars']['arPost']['is_specialchars'])
						{
							$ar_chars_sql = array('\\' => '\\\\', '\\%' => '\\\\\\\%', '\\_' => '\\\\\\\_', '\\"' => '\\\\\\\"', "\\'" => "\\\\\\\'");
							$sql = $oSqlQ->getQ('get-term-exists-spec',
										$arDictParam['tablename'],
										str_replace(array_keys($ar_chars_sql), array_values($ar_chars_sql), gw_addslashes($str_term_src)),
										$qT['id']
							);
						}
						else
						{
							/* Search for an existent term by matched keywords */
							$sql_word_search = "k.word_text IN ('" . implode("', '", $ar_keywords[1]) . "')";
							$sql = $oSqlQ->getQ('get-term-exists', TBL_WORDLIST,
								$arDictParam['tablename'], $arDictParam['id'], 1, $sql_word_search
							);
						}
						$arSql = $oDb->sqlExec($sql);
#prn_r( $arSql );
						/* Compare founded values with imported values */
						for (; list($arK, $arV) = each($arSql);)
						{
							/* Imported Term ID and an existent Term ID are the same */
							if ($arV['id'] == $qT['id'])
							{
								/* Use old Term ID:
									1) to keep old links to a term
									2) to prevent SQL-table defragmentation
								*/
								$id_term_old = $arV['id'];
								$is_term_exists = 1;
								break;
							}
							/* Compare using keywords */
							$ar_kw_existent_term = text2keywords( text_normalize($arV['term']), 1, 25, $sys['internal_encoding'] );
							sort($ar_keywords[1]);
							sort($ar_kw_existent_term);
							/* Keywords from imported Term ID and from existent Term ID are the same */
							if (implode('', $ar_keywords[1]) == implode('', $ar_kw_existent_term))
							{
								$is_term_exists = 1;
								break;
							}
						} /* END for each matched keywords in a term */
					}
					if ($is_term_exists && $gw_this['vars']['arPost']['is_overwrite'])
					{
						$qT['id'] = $arV['id'];
						/* $id_term_old is used in gwAddNewKeywords() */
						$id_term_old = $arV['id'];
						$is_clean_map = 1;
						$is_term_exists = 0;
#						$arQ[] = $oSqlQ->getQ('del-term_id', $arDictParam['tablename'], $id_term_old, $arDictParam['id']);
					}
					/* */
					$qT['term'] = $str_term_src;

					/* -- Custom Alphabetic Toolbar -- */
					/* Select custom rules for uppercasing */
					for (reset($arCustomAZOrder); list($arK, $arV) = each($arCustomAZOrder);)
					{
						$str_term_src = str_replace($arV['az_value_lc'], $arV['az_value'], $str_term_src);
					}
					/* Unicode uppercase */
					$str_term_src_uc = $oCase->uc( $str_term_src );
					$qT['term_order'] = $str_term_src_uc;
					$qT['term_a'] = $qT['term_b'] = $qT['term_c'] = $qT['term_d'] = $qT['term_e'] = $qT['term_f'] = 0;
					$qT['term_3'] = ($qT['term_3'] == '') ? $oFunc->mb_substr($str_term_src_uc, 2, 1, $sys['internal_encoding']) : $qT['term_3'];
					$qT['term_2'] = ($qT['term_2'] == '') ? $oFunc->mb_substr($str_term_src_uc, 1, 1, $sys['internal_encoding']) : $qT['term_2'];
					$qT['term_1'] = ($qT['term_1'] == '') ? $oFunc->mb_substr($str_term_src_uc, 0, 1, $sys['internal_encoding']) : $qT['term_1'];
					/* 1.8.7 */
					$ar_field_names = array('a','b','c','d','e','f');
					preg_match_all("/./u", $str_term_src_uc, $ar_letters);
					for (; list($cnt_letter, $letter) = each($ar_letters[0]);)
					{
						if (isset($ar_field_names[$cnt_letter]))
						{
							$qT['term_'.$ar_field_names[$cnt_letter]] = text_str2ord($letter);
						}
					}
					$qT['is_active'] = ($qT['is_active'] == '') ? 1 : $qT['is_active'];
					$qT['is_active'] = ($gw_this['vars']['arPost']['is_active']) ? $qT['is_active'] : 0;
					$qT['is_complete'] = ($qT['is_complete'] == '') ? 1 : $qT['is_complete'];

					/* Store definition contents (raw XML-data) */
					$qT['defn'] = $v_raw;
					$qT['defn'] = preg_replace("/<term(.*?)>(.*?)<\/term>/", '', $qT['defn']);
					$qT['defn'] = str_replace('</line>', '', $qT['defn']);
					$qT['defn'] = str_replace('</glossword>', '', $qT['defn']);
					$qT['defn'] = str_replace('<glossword>', '', $qT['defn']);
					$qT['defn'] = preg_replace("/<glossword(.*?)>/", '', $qT['defn']);
					$qT['defn'] = trim($qT['defn']);

					/* Fix htmlspecial characters */
					$qT['term_1'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_1']));
					$qT['term_2'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_2']));
					$qT['term_3'] = gw_htmlspecialamp(gw_unhtmlspecialamp($qT['term_3']));

					$qT['int_bytes'] = strlen($qT['defn']);
					/* 1.8.10: Create checksum for terms only. Not unsigned. */
					$qT['crc32u'] = crc32($str_term_src_uc);

					$qT['id_user'] = $oSess->id_user;

#prn_r( $qT );
					/* Add relation `user to term' */
					if (!$is_term_exists)
					{
						$arTermMap['user_id'] = $oSess->id_user;
						$arTermMap['term_id'] = $qT['id'];
						$arTermMap['dict_id'] = $arDictParam['id'];
					}
				break;
				case 'defn':
					$ar_keywords_raw[$v2['tag']][] = $v2['value'];
					if (!isset($v2['children']))
					{
						continue;
					}
					for (reset($v2['children']); list($k3, $v3) = each($v2['children']);)
					{
						/* no contents for definition */
						if (!is_array($v3) && trim($v3) == '')
						{
							continue;
						}
						elseif (!is_array($v3))
						{
							$ar_keywords_raw[$v2['tag']][] = ' ' . $v3;
							continue;
						}
						/* for each tag */
						#prn_r( $v3, __LINE__ );
						$ar_keywords_raw[$v3['tag']][] = $v3['value'];
					}
				break;
			}
		}
#		prn_r( $ar_keywords_raw );
		/* Collect keywords per fields */
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			#$ar_keywords[$fK] = array();
			if (isset($ar_keywords_raw[$fV[0]]))
			{
				$str_keywords_raw = implode(' ', $ar_keywords_raw[$fV[0]]);
				$ar_keywords[$fK] = text2keywords( text_normalize($str_keywords_raw), $arDictParam['min_srch_length'], 25, $sys['internal_encoding'] );
				/* Remove stopwords from parsed strings only (others are empty) */
				$ar_keywords[$fK] = gw_array_exclude($ar_keywords[$fK], $arStop);
				unset($ar_keywords_raw[$fV[0]]);
				$str_keywords_raw = '';
			}
			if ($fK != 0)
			{
#				$ar_keywords[0] = gw_array_exclude( $ar_keywords[0], $ar_keywords[$fK]);
			}
		}
#prn_r( $ar_keywords );
#		$gw_this['vars']['arPost']['xml'] = str_replace('<line>'.$ar_lines_raw[$k_raw], '', $gw_this['vars']['arPost']['xml']);
		unset($ar_lines_raw[$k_raw]);

		if (!$is_term_exists)
		{
			++$gw_this['vars']['int_items_added'];
			gwAddNewKeywords($arDictParam['id'], $qT['id'], $ar_keywords, $id_term_old, $is_clean_map, $qT['date_created']);
			$arQ[] = gw_sql_replace($qT, $arDictParam['tablename'], 1);
			$arQ[] = gw_sql_replace($arTermMap, TBL_MAP_USER_TERM, 1);

			/* 24 Nov 2007: History of changes */
			$qT['id_term'] = $qT['id'];
			$qT['id_dict'] = $arDictParam['id'];
			$qT['keywords'] = serialize($ar_keywords);
			unset($qT['id']);
			$arQ[] = gw_sql_insert($qT, $sys['tbl_prefix'].'history_terms', 1);
		}
		++$gw_this['vars']['int_items_passed'];

		/* XML import: execute queries */
		if ($sys['isDebugQ'])
		{
			$arStatus = array_merge($arQ, $arStatus);
		}
		else
		{
			/* Post queries */
			for (reset($arQ); list($qk, $qv) = each($arQ);)
			{
				$oDb->sqlExec($qv);
			}
		}
		/* Limit execution time */
		$time_end = list($em, $es) = explode(' ', microtime());
		$time_spend += ($em + $es) - ($sm + $ss);
		/* Create buffer 3 seconds */
		if ($time_spend >= ($gw_this['vars']['arPost']['max_execution_time']-2))
		{
			$is_next_part = 1;
			break;
		}
	}
	if ($is_next_part)
	{
		$sys['import_filename'] =  urlencode($file_location['name']);
		$sys['import_format'] = 'xml';

		/* 1.8.10: Start import session */
		$q['int_items_passed'] = $gw_this['vars']['int_items_passed'];
		if (isset($gw_this['vars']['arPost']['id_session']))
		{
			/* Update session */
			$oDb->sqlExec( gw_sql_update($q, $sys['tbl_prefix'].'import_sessions', 'id_session = "'. $gw_this['vars']['arPost']['id_session'].'"') );
			$gw_this['vars']['id_session'] =& $gw_this['vars']['arPost']['id_session'];
		}
		else
		{
			/* Insert session */
			$q['id_session'] = md5($sys['time_now_gmt_unix'].$oSess->id_user.$file_location['name']);
			$q['id_user'] = $oSess->id_user;
			$q['id_dict'] = $gw_this['vars']['id'];
			$q['date_end'] = 0;
			$q['date_start'] = $sys['time_now_gmt_unix'];
			$q['int_items_total'] = $gw_this['vars']['int_items_total'];
			$q['int_bytes'] = $gw_this['vars']['int_bytes'];
			$q['filename'] = $file_location['name'];
			$q['settings'] = serialize($gw_this['vars']['arPost']);
			$oDb->sqlExec( gw_sql_insert($q, $sys['tbl_prefix'].'import_sessions') );
			$gw_this['vars']['id_session'] =& $q['id_session'];
		}
		$gw_this['vars']['int_items_left'] = $gw_this['vars']['int_items_total']-$gw_this['vars']['int_items_passed'];
		/* Save next part */
		$oFunc->file_put_contents( $sys['path_temporary'].'/import-'.$gw_this['vars']['id_session'], serialize($ar_lines_raw) );
	}
	/* XML: Remove a data file */
	if (!$gw_this['vars']['int_items_left'] && isset($gw_this['vars']['arPost']['id_session']))
	{
		$file_data = $sys['path_temporary'].'/import-'.$gw_this['vars']['arPost']['id_session'];
		if (file_exists($file_data))
		{
			unlink($file_data);
		}
	}
	$sys['memory_spend'] = memory_get_usage();
}


/* */
function gw_import_csv()
{
	global $oSess, $oDb, $oCase, $oSqlQ, $oFunc, $oL;
	global $sys, $arReq, $arFields, $vars, $arBroken, $gw_this, $arDictParam;
	global $file_location, $arStatus, $xml_file;

/* Enter Debug mode. CSV */
#$sys['isDebugQ'] = 1;

	/* ... */
	$gw_this['vars']['arPost']['str_separator'] = "\t";
	$gw_this['vars']['arPost']['str_separator_defn'] = ' ;; ';
	$gw_this['vars']['arPost']['str_enclosed'] = '';

	$arXML = $arDuplicates = array();
	
	if (!isset($file_location['name']))
	{
		$file_location['name'] = '';
	}
	
	$is_next_part = 0;

	/* Start timer */
	$oT = new gw_timer('parse');

	/* Select custom rules for uppercasing */
	$sql = 'SELECT `az_value`, `az_value_lc` FROM `'.$sys['tbl_prefix'].'custom_az` WHERE `id_profile` = "'.$arDictParam['id_custom_az'].'"';
	$arCustomAZOrder = $oDb->sqlRun($sql, 'st');

	/* 1.8.4: Partial import */
	/* 1.8.10: Added import sessions */
	if (isset($gw_this['vars']['arPost']['id_session']))
	{
		/* Read session settings */
		$sql = 'SELECT date_start, int_items_total, int_items_passed, filename, int_bytes, settings FROM `'.$sys['tbl_prefix'].'import_sessions`
				WHERE id_session = "'.gw_text_sql($gw_this['vars']['arPost']['id_session']).'"
				LIMIT 1
		';
		$arSql = $oDb->sqlExec($sql);
		$arSql = isset($arSql[0]) ? $arSql[0] : array();

		$gw_this['vars']['arPost'] = array_merge($gw_this['vars']['arPost'], unserialize($arSql['settings']));
		$gw_this['vars']['int_items_total'] = $arSql['int_items_total'];
		$gw_this['vars']['int_items_passed'] = $arSql['int_items_passed'];
		$gw_this['vars']['date_start'] = $arSql['date_start'];
		$file_location['name'] = $arSql['filename'];
		$file_location['size'] = $arSql['int_bytes'];
		/* CSV: Read data file */
		$arSql['lines_raw'] = $oFunc->file_get_contents( $sys['path_temporary'].'/import-'.$gw_this['vars']['arPost']['id_session'] );
		$arSql = @unserialize($arSql['lines_raw']);
		if (!empty($arSql))
		{
			list($arXML, $arDuplicates) = $arSql;
		}
#		prn_r( $arSql );
		unset($arSql);
	}
	else
	{
		$oSess->user_set('import_is_read_first', $gw_this['vars']['arPost']['is_read_first']);
		/* Uploaded file */
		$xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
		$xml_file_new = $sys['path_temporary'].'/'.urlencode($sys['time_now'].'-'.$file_location['name']);
		if (is_uploaded_file($xml_file)
			&& move_uploaded_file($xml_file, $xml_file_new)
			)
		{
			$gw_this['vars']['arPost']['csv'] = $oFunc->file_get_contents($xml_file_new);
			unlink($xml_file_new);
		}

#		prn_r( $gw_this['vars']['arPost']['csv'] );
		/* shouldn't be here */
#		gw_fix_newline($gw_this['vars']['arPost']['csv']);

		/* trim() can be used, since there is an additional support for incomplete records */
		$ar_lines_csv = explode(CRLF, rtrim($gw_this['vars']['arPost']['csv']));
		$int_fields = sizeof($arFields);
		$int_lines = sizeof($ar_lines_csv);
		$gw_this['vars']['int_bytes'] = strlen($gw_this['vars']['arPost']['csv']);
		$gw_this['vars']['arPost']['csv'] = '';

		/* no data */
		if ($ar_lines_csv[0] == '')
		{
			$ar_lines_csv = array();
			$gw_this['vars']['arPost']['csv'] = $oL->m('1255');
		}
		/* too many lines */
		if ($int_lines > $sys['max_lines_csv'])
		{
			$is_continue = 0;
			$ar_lines_csv = array();
			$gw_this['vars']['arPost']['csv'] = sprintf($oL->m('1254'), $sys['max_lines_csv'], $int_lines);
			$gw_this['vars']['arPost']['xml'] = '';
			$arBroken['csv'] = 1;
			$gw_this['vars']['arPost']['file_location'] = '';
			/* Call HTML-form again */
			$str .= getFormImport($gw_this['vars']['arPost'], 1, $arBroken, $arReq[$gw_this['vars'][GW_TARGET]]);
			$str .= '<br />';
			return;
		}
		if (!$gw_this['vars']['arPost']['is_read_first'])
		{
			unset($ar_lines_csv[0]);
		}
		/**
			Two steps:
			1. convert inputed data into array with XML-code
			2. import XML-code
		*/
		$arXML = array();
		$arDuplicates = array();
		for (reset($ar_lines_csv); list($k1, $v1) = each($ar_lines_csv);)
		{
			/* id, term, ... */
			$ar_line_csv = explode($gw_this['vars']['arPost']['str_separator'], $v1);
			/* Support for incorrectly formed lines */
			if (!preg_match("/^(\d+|\t)\t/", $v1) && !isset($ar_line_csv[1]))
			{
				$v1 = "\t" . $v1;
				/* Re-explode */
				$ar_line_csv = explode($gw_this['vars']['arPost']['str_separator'], $v1);
			}
			/* adjust empty fields */
			if (sizeof($ar_line_csv) == 1)
			{
				$ar_line_csv[1] = $ar_line_csv[0];
				$ar_line_csv[0] = '';
			}
			/* skip empty terms */
			if (!isset($ar_line_csv[1]) || trim($ar_line_csv[1]) == '') { continue; }
			/* support for incomplete records */
			if (!isset($ar_line_csv[$arFields[-2][5]])){ $ar_line_csv[$arFields[-2][5]] = ''; }
			if (!isset($ar_line_csv[$arFields[-3][5]])){ $ar_line_csv[$arFields[-3][5]] = ''; }
			if (!isset($ar_line_csv[$arFields[-4][5]])){ $ar_line_csv[$arFields[-4][5]] = ''; }
			if (!isset($ar_line_csv[$arFields[-5][5]])){ $ar_line_csv[$arFields[-5][5]] = ''; }
			/* for each dictionary field */
			for (reset($arFields); list($fK, $fV) = each($arFields);)
			{
				if (!isset($ar_line_csv[$fV[5]]))
				{
					/* Fill empty fields */
					$ar_line_csv[$fV[5]] = '';
				}
				$ar_line_csv[$fV[5]] = gw_fix_input_to_db($ar_line_csv[$fV[5]]);
				/* Parse CSV */
				switch($fV[0])
				{
					case 'term':
						$uid = crc32($ar_line_csv[$fV[5]]);
						if (!isset($arXML[$uid][$fK]))
						{
							$arXML[$uid][-1] = $ar_line_csv[$arFields[-1][5]]; /* Term ID */
							$arXML[$uid][-2] = $ar_line_csv[$arFields[-2][5]]; /* Alphabetic toolbar 1 */
							$arXML[$uid][-3] = $ar_line_csv[$arFields[-3][5]]; /* Alphabetic toolbar 2 */
							$arXML[$uid][-4] = $ar_line_csv[$arFields[-4][5]]; /* Alphabetic toolbar 3 */
							$arXML[$uid][-5] = $ar_line_csv[$arFields[-5][5]]; /* term_uri */
							$arXML[$uid][$fK] = $ar_line_csv[$fV[5]];
						}
						else
						{
							$arDuplicates[$uid][$k1][$fK] = $ar_line_csv[$fV[5]];
						}
					break;
					case 'defn':
						if (!isset($arXML[$uid][$fK]))
						{
							$arXML[$uid][$fK] = '<![CDATA['.$ar_line_csv[$fV[5]].']]>';
						}
						else
						{
							$arDuplicates[$uid][$k1][$fK] = '<![CDATA['.$ar_line_csv[$fV[5]].']]>';
						}
					break;
					case 'see':
					case 'syn':
					case 'antonym':
						$ar_values = explode($gw_this['vars']['arPost']['str_separator_defn'], @$ar_line_csv[$fV[5]]);
						for (reset($ar_values); list($kTag, $vTag) = each($ar_values);)
						{
							/* Skip empty values */
							if (trim($vTag) == ''){ continue; }
							/* Search for attributes */
							preg_match("/^\((.*?)\) /", $vTag, $ar_attr);
							$str_attr = '';
							if (isset($ar_attr[1]))
							{
								$vTag = str_replace($ar_attr[0], '', $vTag);
								/* is_link, [text]*/
								$ar_attr_v = explode(',', $ar_attr[1]);
								$str_attr .= isset($ar_attr_v[0]) ? ' link="'.$vTag.'"' : '';
								$str_attr .= isset($ar_attr_v[1]) ? ' text="'.trim($ar_attr_v[1]).'"' : '';
							}
							$ar_values[$kTag] = '<'.$fV[0].$str_attr.'><![CDATA['.$vTag.']]></'.$fV[0].'>';
						}
						if (!isset($arXML[$uid][$fK]))
						{
							$arXML[$uid][$fK] = implode('', $ar_values);
						}
						else
						{
							$arDuplicates[$uid][$k1][$fK] = implode('', $ar_values);
						}
					break;
					case 'trns':
					case 'abbr':
						$ar_trnsabbr = explode($gw_this['vars']['arPost']['str_separator_defn'], $ar_line_csv[$fV[5]]);
						for (reset($ar_trnsabbr); list($kTag, $vTag) = each($ar_trnsabbr);)
						{
							/* Skip empty values */
							if (trim($vTag) == ''){ continue; }
							/* Search for attributes */
							preg_match("/^\(([0-9a-zA-Z\.]+)\) /", $vTag, $ar_attr);
							$str_attr = '';
							if (isset($ar_attr[1]))
							{
								$str_attr = ' lang="'.$ar_attr[1].'"';
								$vTag = str_replace($ar_attr[0], '', $vTag);
							}
							$ar_trnsabbr[$kTag] = '<'.$fV[0].$str_attr.'><![CDATA['.$vTag.']]></'.$fV[0].'>';
						}
						if (!isset($arXML[$uid][$fK]))
						{
							$arXML[$uid][$fK] = implode('', $ar_trnsabbr);
						}
						else
						{
							$arDuplicates[$uid][$k1][$fK] = implode('', $ar_trnsabbr);
						}
					break;
					case 'trsp';
					case 'usg':
					case 'src':
					case 'phone':
					case 'address':
						$ar_values = explode($gw_this['vars']['arPost']['str_separator_defn'], @$ar_line_csv[$fV[5]]);
						for (reset($ar_values); list($kTag, $vTag) = each($ar_values);)
						{
							/* Skip empty values */
							if (trim($vTag) == ''){ continue; }
							$ar_values[$kTag] = '<'.$fV[0].'><![CDATA['.$vTag.']]></'.$fV[0].'>';
						}
						if (!isset($arXML[$uid][$fK]))
						{
							$arXML[$uid][$fK] = implode('', $ar_values);
						}
						else
						{
							$arDuplicates[$uid][$k1][$fK] = implode('', $ar_values);
						}
					break;
				}
			} /* $arFields */
			unset($ar_lines_csv[$k1]);
		}
		/* The total number of terms */
		$gw_this['vars']['int_items_total'] = sizeof($arXML);
		
#prn_r( $arDuplicates );
#prn_r( $arXML );

	}
	/* Parsing time */
	$time_spend = $oT->end('parse');
	/* Memory usage */
	$sys['memory_spend'] = memory_get_usage();

	/* Initiate $oCase class, 0.131938 */
	$oCase->nc('a');

	/* Strongly based on $arFields
		1. Create XML-code.
		2. Join primary and secondary definitions.
		3. Create keywords.
	*/
	/* Exclude stopwords */
	$arData = $arQ = $arStatus = array();
	$arStop = gw_get_stopwords($arDictParam);
#	prn_r( $arXML );
	for (reset($arXML); list($uid, $v1) = each($arXML);)
	{
		/* Start timer per a term */
		$time_start = list($sm, $ss) = explode(' ', microtime());

		/* Prepare keywords per field */
		for (reset($v1); list($id_field, $v2) = each($v1);)
		{
			if (!isset($arFields[$id_field])) { continue; }
			$fV =& $arFields[$id_field];
			$v2 = str_replace('<![CDATA[', '', $v2);
			$v2 = str_replace(']]>', '', $v2);
			/* Get maximum search length per field */
			$int_min_length = (isset($fV[2]) && ($fV[2] != 'auto') && ($fV[2] != '')) ? $fV[2] : $arDictParam['min_srch_length'];
			$ar_keywords[$uid][$id_field] = text2keywords( text_normalize($v2), $arDictParam['min_srch_length'], 25, $sys['internal_encoding'] );
			/* Remove stopwords from parsed strings only (others are empty) */
			$ar_keywords[$uid][$id_field] = gw_array_exclude( $ar_keywords[$uid][$id_field], $arStop);
			if (empty($ar_keywords[$uid][$id_field]))
			{
				unset($ar_keywords[$uid][$id_field]);
			}
		}
		$arData[$uid]['id'] = intval($v1[-1]);
		$arData[$uid]['term'] = $v1[1];
		$arData[$uid]['term_1'] = $v1[-2];
		$arData[$uid]['term_2'] = $v1[-3];
		$arData[$uid]['term_3'] = $v1[-4];
		$arData[$uid]['term_uri'] = $v1[-5];
		unset($v1[1]);
		unset($v1[-1]);
		unset($v1[-2]);
		unset($v1[-3]);
		unset($v1[-4]);
		unset($v1[-5]);
		$arData[$uid]['defn'] = '<defn>'.implode('', $v1).'</defn>';
		if (isset($arDuplicates[$uid]))
		{
			for (reset($arDuplicates[$uid]); list($kD, $arVd) = each($arDuplicates[$uid]);)
			{
				unset($arVd[1]);
				$arData[$uid]['defn'] .= '<defn>'.implode('', $arVd).'</defn>';
			}
			unset($arDuplicates[$uid]);
		}
		unset($arXML[$uid]);
		/* Escape sequences */
		if ($gw_this['vars']['arPost']['is_convert_esc'])
		{
			$ar_esc = array('\r\n' => "\r\n", '\\n' => "\n", '\\r' => "\r", '\\t' => "\t");
			$arData[$uid]['defn'] = str_replace(array_keys($ar_esc), array_values($ar_esc), $arData[$uid]['defn']);
		}
		/* Whitespace characters */
		if ($gw_this['vars']['arPost']['is_whitespace'])
		{
			$arData[$uid]['defn'] = preg_replace('/[^\\\](\\\r\\\n|\\\r|\\\n|\\\t)/', '\\\\' . "\\1", $arData[$uid]['defn']);
		}
		/* Automatically parse URLs */
		$arData[$uid]['defn'] = preg_replace("/(^|\[|\s)((http|https|news|ftp|aim|callto|ed2k):\/\/\w+[^\s\[\\]]+)/ie"  , "gw_regex_url(array('html' => '\\2', 'show' => '\\2', 'st' => '\\1'))", $arData[$uid]['defn']);

		$qT = $arTermMap = $arQ = array();
		$id_term = $id_term_old = $is_clean_map = $is_term_exists = 0;
		$qT['id'] = $arData[$uid]['id'];
		if ($qT['id'] == '')
		{
			$id_term_db = $oDb->MaxId($arDictParam['tablename'], 'id');
			$qT['id'] = mt_rand($id_term_db, ($sys['leech_factor'] * 2) + $id_term_db);
		}
		/* Set time */
		$qT['date_modified'] = $qT['date_created'] = $sys['time_now_gmt_unix'] - 60;
		/* */
		$str_term_src = trim($arData[$uid]['term']);
		$str_term_filtered = text_normalize($str_term_src);
		if ($gw_this['vars']['arPost']['is_check_exist'])
		{
			/* Strict search for an existent term including special chars */
			if ($gw_this['vars']['arPost']['is_specialchars'] || empty($ar_keywords[$uid][1]) )
			{
				$ar_chars_sql = array('\\' => '\\\\', '\\%' => '\\\\\\\%', '\\_' => '\\\\\\\_', '\\"' => '\\\\\\\"', "\\'" => "\\\\\\\'");
				$sql = $oSqlQ->getQ('get-term-exists-spec',
								$arDictParam['tablename'],
							str_replace(array_keys($ar_chars_sql), array_values($ar_chars_sql), gw_addslashes($str_term_src)),
							$qT['id']
				);
			}
			else
			{
				/* Search for an existent term by matched keywords */
				$sql_word_search = "k.word_text IN ('" . implode("', '", $ar_keywords[$uid][1]) . "')";
				$sql = $oSqlQ->getQ('get-term-exists', TBL_WORDLIST,
					$arDictParam['tablename'], $arDictParam['id'], 1, $sql_word_search
				);
			}
			$arSql = $oDb->sqlExec($sql);
			/* Compare founded values with imported values */
			for (; list($arK, $arV) = each($arSql);)
			{
				/* Imported Term ID and an existent Term ID are the same */
				if ($arV['id'] == $qT['id'])
				{
					$id_term_old = $arV['id'];
					$is_term_exists = 1;
					break;
				}
				$ar_kw_existent_term = text2keywords( $oCase->nc($arV['term']), 1, 25, $sys['internal_encoding'] );
				sort($ar_keywords[$uid][1]);
				sort($ar_kw_existent_term);
				if (implode('', $ar_keywords[$uid][1]) == implode('', $ar_kw_existent_term))
				{
					$is_term_exists = 1;
					break;
				}
			} /* end for each matched keywords for a term */
		}
		if ($is_term_exists && $gw_this['vars']['arPost']['is_overwrite'])
		{
			$qT['id'] = $arV['id'];
			$id_term_old = $arV['id'];
			$is_clean_map = 1;
			$is_term_exists = 0;
		}
		
		$qT['term'] = $str_term_src;

		/* -- Custom Alphabetic Toolbar -- */
		/* Select custom rules for uppercasing */
		for (reset($arCustomAZOrder); list($arK, $arV) = each($arCustomAZOrder);)
		{
			$str_term_src = str_replace($arV['az_value_lc'], $arV['az_value'], $str_term_src);
		}
		/* Unicode uppercase */
		$str_term_src_uc = $oCase->uc( $str_term_src );
		$qT['term_order'] = $str_term_src_uc;
		$qT['term_a'] = $qT['term_b'] = $qT['term_c'] = $qT['term_d'] = $qT['term_e'] = $qT['term_f'] = 0;
		$qT['term_3'] = ($arData[$uid]['term_3'] == '') ? $oFunc->mb_substr($str_term_src_uc, 2, 1, $sys['internal_encoding']) : $arData[$uid]['term_3'];
		$qT['term_2'] = ($arData[$uid]['term_2'] == '') ? $oFunc->mb_substr($str_term_src_uc, 1, 1, $sys['internal_encoding']) : $arData[$uid]['term_2'];
		$qT['term_1'] = ($arData[$uid]['term_1'] == '') ? $oFunc->mb_substr($str_term_src_uc, 0, 1, $sys['internal_encoding']) : $arData[$uid]['term_1'];

		/* 24 apr 2008: Even better URI. Added transliteration. */
		$qT['term_uri'] = ($arData[$uid]['term_uri'] == '') ? $qT['id'].'-'.$oCase->translit( $oCase->lc($str_term_src)) : $arData[$uid]['term_uri'];
		$qT['term_uri'] = $oCase->rm_entity($qT['term_uri']);
		$qT['term_uri'] = preg_replace('/[^0-9A-Za-z_-]/', '-', $qT['term_uri']);
		$qT['term_uri'] = preg_replace('/-{2,}/', '-', $qT['term_uri']);
		if ($qT['term_uri'] == '-')
		{
			$qT['term_uri'] = $qT['id'].'-';
		}

		/* 0.000203 */
		$ar_field_names = array('a','b','c','d','e','f');
		preg_match_all("/./u", $str_term_src_uc, $ar_letters);
		for (; list($cnt_letter, $letter) = each($ar_letters[0]);)
		{
			if (isset($ar_field_names[$cnt_letter]))
			{
				$qT['term_'.$ar_field_names[$cnt_letter]] = text_str2ord($letter);
			}
		}
		/* */
		$qT['is_active'] = ($gw_this['vars']['arPost']['is_active']) ? 1 : 0;
		$qT['is_complete'] = 1;
		$qT['id_user'] = $oSess->user_get('id_user');
		$qT['defn'] = text_parse_csv_column_input($arData[$uid]['defn']);
		$qT['int_bytes'] = strlen($qT['defn']);
#prn_r( $qT );
#$oTimer = new gw_timer('a');
		$qT['crc32u'] = crc32($str_term_src_uc);
#print $oTimer->endp('a');

		/* Add relation `user to term' */
		if (!$is_term_exists)
		{
			$arTermMap['user_id'] = $oSess->id_user;
			$arTermMap['term_id'] = $qT['id'];
			$arTermMap['dict_id'] = $arDictParam['id'];
			++$gw_this['vars']['int_items_added'];
			gwAddNewKeywords($arDictParam['id'], $qT['id'], $ar_keywords[$uid], $id_term_old, $is_clean_map, $qT['date_created']);
			$arQ[] = gw_sql_replace($qT, $arDictParam['tablename'], 1);
			$arQ[] = gw_sql_replace($arTermMap, TBL_MAP_USER_TERM, 1);

			/* 24 Nov 2007: History of changes */
			$qT['id_term'] = $qT['id'];
			$qT['id_dict'] = $arDictParam['id'];
			$qT['keywords'] = serialize($ar_keywords);
			unset($qT['id']);
			$arQ[] = gw_sql_insert($qT, $sys['tbl_prefix'].'history_terms', 1);
		}
		else
		{
			global $oL;
			$arStatus[] = $oL->m('reason_25').': ' . $qT['term'];
		}
		++$gw_this['vars']['int_items_passed'];
			
		/* */
		if ($sys['isDebugQ'])
		{
			$arStatus = array_merge($arQ, $arStatus);
		}
		else
		{
			/* Post queries */
			for (reset($arQ); list($qk, $qv) = each($arQ);)
			{
				$oDb->sqlExec($qv);
			}
		}
		unset($ar_keywords[$uid]);
		unset($arData[$uid]);
		/* Live counter for the number of added terms */
#		$oFunc->file_put_contents( $sys['path_temporary'].'/'.urlencode('cnt'.$file_location['name']), $int_added_terms);
		/* */
		$time_end = list($em, $es) = explode(' ', microtime());
		$time_spend += ($em + $es) - ($sm + $ss);
		if ($time_spend >= ($gw_this['vars']['arPost']['max_execution_time']-2))
		{
			$is_next_part = 1;
			break;
		}
	}

#	print '<ul class="gwsql"><li>'.implode('</li><li>', $arStatus).'</li></ul>';

	if ($is_next_part)
	{
		$sys['import_filename'] =  urlencode($file_location['name']);
		$sys['import_format'] = 'csv';
		
		/* 1.8.10: Start import session */
		$q['int_items_passed'] = $gw_this['vars']['int_items_passed'];
		if (isset($gw_this['vars']['arPost']['id_session']))
		{
			/* Update session */
			$oDb->sqlExec( gw_sql_update($q, $sys['tbl_prefix'].'import_sessions', 'id_session = "'. $gw_this['vars']['arPost']['id_session'].'"') );
			$gw_this['vars']['id_session'] =& $gw_this['vars']['arPost']['id_session'];
		}
		else
		{
			/* Insert session */
			$q['id_session'] = md5($sys['time_now_gmt_unix'].$oSess->id_user.$file_location['name']);
			$q['id_user'] = $oSess->id_user;
			$q['id_dict'] = $gw_this['vars']['id'];
			$q['date_end'] = 0;
			$q['date_start'] = $sys['time_now_gmt_unix'];
			$q['int_items_total'] = $gw_this['vars']['int_items_total'];
			$q['int_bytes'] = $gw_this['vars']['int_bytes'];
			$q['filename'] = $file_location['name'];
			$q['settings'] = serialize($gw_this['vars']['arPost']);
			$oDb->sqlExec( gw_sql_insert($q, $sys['tbl_prefix'].'import_sessions') );
			$gw_this['vars']['id_session'] =& $q['id_session'];
		}
		$gw_this['vars']['int_items_left'] = $gw_this['vars']['int_items_total']-$gw_this['vars']['int_items_passed'];
		/* Save next part */
		$oFunc->file_put_contents( $sys['path_temporary'].'/import-'.$gw_this['vars']['id_session'], serialize(array($arXML, $arDuplicates)) );
	}
	/* CSV: Remove a data file */
	if (!$gw_this['vars']['int_items_left'] && isset($gw_this['vars']['arPost']['id_session']))
	{
		$file_data = $sys['path_temporary'].'/import-'.$gw_this['vars']['arPost']['id_session'];
		if (file_exists($file_data))
		{
			unlink($file_data);
		}
	}
	/* Real-time counter */
#	$file_cnt = $sys['path_temporary'].'/'.urlencode('cnt'.$file_location['name']);
#	if (file_exists($file_cnt))
#	{
#		unlink($file_cnt);
#	}
	$sys['memory_spend'] = memory_get_usage();
}
/* */
function getFormImport($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $id, $oL, $oSess, $oFunc, $sys, $oCase;
	global $ar_theme, $gw_this;

	$strForm = '';
	$trClass = 'xt';
	$v_class_1 = 'td1';
	$v_class_2 = 'td2';
	$v_td1_width = '20%';

	$oForm = new gwForms();
	$oForm->Set('action',          $sys['page_admin']);
	$oForm->Set('submitok',        $oL->m('3_import'));
	$oForm->Set('submitdel',       $oL->m('3_remove'));
	$oForm->Set('submitcancel',    $oL->m('3_cancel'));
	$oForm->Set('formbgcolor',     $ar_theme['color_2']);
	$oForm->Set('formbordercolor', $ar_theme['color_4']);
	$oForm->Set('formbordercolorL',$ar_theme['color_1']);
	$oForm->Set('align_buttons',   $sys['css_align_right']);
	$oForm->Set('charset', $sys['internal_encoding']);
	// Upload xml-file
	if ($sys['is_upload']) { $oForm->Set('enctype', 'multipart/form-data'); }
	## ----------------------------------------------------
	##
	 // check vars
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while (is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = "";
		if (isset($arReq[$key])) { $arReqMsg[$key] = '&#160;<span class="red"><strong>*</strong></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = '<div class="red"><strong>' . $oL->m('reason_9') .'</strong></div>'; }
	} // end of while
	
	global $arReq;
	//
	##
	## ----------------------------------------------------
	$arBoxId = array();
	$strForm .= getFormTitleNav($oL->m('1061'), '<span style="float:right">'.
				$oForm->get_button('submit').'</span>');
	$strForm .= '<table class="gw2TableFieldset" width="100%"><tbody>';
	$strForm .= '<tr><td style="width:1%"></td><td></td></tr>';

	$arBoxId['id'] = 'arPost_format_xml';

	$arBoxId['onchange'] = 'checkFormat()';
	$arBoxId['onclick'] = 'checkFormat()';
	$strForm .= '<tr>'.
				 '<td class="td1">' . $oForm->field('radio', 'arPost[format]', 'xml', ($vars['format'] == 'xml'), $arBoxId ) . '</td>'.
				 '<td class="td2"><label onclick="checkFormat()" for="arPost_format_xml">XML</label></td>'.
				'</tr>';
	$strForm .= '<tr>';
	$strForm .= '<td></td>';
	$strForm .= '<td>';

		$strForm .= '<div id="table_xml">';
		$strForm .= '<table class="gw2TableFieldset" width="100%" style="border:1px '.$ar_theme['color_4'].' solid"><tbody>';
		$strForm .= '<tr>'.
					'<td class="td1" style="width:5%"></td>'.
					'<td class="td2">' . $arBrokenMsg['xml'] . '<textarea '.
					' onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'arPost[\'+\'xml\'+\']\'].select();}"'.
					' style="width:100%;font:85% \'verdana\',arial,sans-serif"'.
					' name="arPost[xml]" id="arPost_xml_" dir="ltr" cols="45" rows="10">' . htmlspecialchars_ltgt($vars['xml']) . '</textarea>'.
					'</td>'.
					'</tr>';
		/* 3 mar 2003 */
		/* Allows to upload a file */
		if ($sys['is_upload'])
		{
			$oForm->setTag('file', 'id', 'file_location_xml');
			$oForm->setTag('file', 'dir', 'ltr');
			$strForm .= '<tr>'.
						'<td class="td1">'.'&#160;'.'</td>'.
						'<td class="td2">' . $oL->m(1132) . ', '.$oL->m('mb').': <strong>'.$oFunc->number_format($sys['max_upload_size'], 0, $oL->languagelist('4')).'</strong><br />' . $oForm->field('file', 'file_location', '') . '</td>'.
						'</tr>';
		}
		if (function_exists('xslt_create'))
		{
			$strForm .= '<tr>'.
						 '<td class="td1">' . $oForm->field('checkbox', "arPost[is_validate]", $vars['is_validate']) . '</td>'.
						 '<td class="td2"><label for="arPost_is_validate_">' . $oL->m('validate') . '</label></td>'.
						'</tr>';
		}
		$strForm .= '</tbody></table></div>';
	/* */
	$strForm .= '</td></tr>';

	$arBoxId['id'] = 'arPost_format_csv';
	$strForm .= '<tr>'.
				 '<td class="td1">' . $oForm->field('radio', 'arPost[format]', 'csv', ($vars['format'] == 'csv'), $arBoxId ) . '</td>'.
				 '<td class="td2"><label onclick="checkFormat()" for="arPost_format_csv">CSV</label></td>'.
				'</tr>';
	$strForm .= '<tr>';
	$strForm .= '<td></td>';
	$strForm .= '<td>';

		$strForm .= '<div id="table_csv">';
		$strForm .= '<table class="gw2TableFieldset" width="100%" style="border:1px '.$ar_theme['color_4'].' solid"><tbody>';
		$strForm .= '<tr>'.
					'<td class="td1" style="width:5%"></td>'.
					'<td class="td2">' . $arBrokenMsg['csv'] . '<textarea '.
					' onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'arPost[\'+\'csv\'+\']\'].select();}"'.
					' style="width:100%;font:85% verdana,arial,sans-serif"'.
					' name="arPost[csv]" id="arPost_csv_" dir="ltr" cols="45" rows="10">' . htmlspecialchars_ltgt($vars['csv']) . '</textarea>'.
					'</td>'.
					'</tr>';
		/* 3 mar 2003 */
		/* Allows to upload a file */
		if ($sys['is_upload'])
		{
			$oForm->setTag('file', 'id', 'file_location_csv');
			$oForm->setTag('file', 'dir', 'ltr');
			$strForm .= '<tr>'.
						'<td class="'.$v_class_1.'">&#160;</td>'.
						'<td class="'.$v_class_2.'">' . $oL->m(1132) . ', '.$oL->m('mb').': <strong>'.$oFunc->number_format($sys['max_upload_size'], 0, $oL->languagelist('4')).'</strong><br />' . $oForm->field('file', 'file_location', '') . '</td>'.
						'</tr>';
		}
		$oForm->setTag('input', 'class', '');
		$oForm->setTag('input', 'size', '6');
		$oForm->setTag('input', 'maxlength', '5');
		$oForm->setTag('input', 'dir', 'ltr' );
#		$strForm .= '<tr>'.
#					'<td class="'.$v_class_1.'">' . $arBrokenMsg['str_separator'] . $oForm->field('input', 'arPost[str_separator]', htmlspecialchars($vars['str_separator'])) . '</td>'.
#					'<td class="'.$v_class_2.'">' . $oL->m('1119') . $arReqMsg['str_separator'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					'<td class="'.$v_class_1.'">' . $arBrokenMsg['str_enclosed'] . $oForm->field('input', 'arPost[str_enclosed]', htmlspecialchars($vars['str_enclosed'])) . '</td>'.
#					'<td class="'.$v_class_2.'">' . $oL->m('1120') . $arReqMsg['str_enclosed'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					'<td class="'.$v_class_1.'">' . $arBrokenMsg['str_separator_defn'] . $oForm->field('input', 'arPost[str_separator_defn]', htmlspecialchars($vars['str_separator_defn'])) . '</td>'.
#					'<td class="'.$v_class_2.'">' . $oL->m('1122') . $arReqMsg['str_separator_defn'] . '</td>'.
#					'</tr>';
		$strForm .= '<tr>'.
					 '<td class="td1">' . $oForm->field('checkbox', "arPost[is_read_first]", $vars['is_read_first']) . '</td>'.
					 '<td class="td2"><label for="arPost_is_read_first_">' . $oL->m('1123') . '</label></td>'.
					'</tr>';
		$strForm .= '</tbody></table></div>';

	$strForm .= '</td></tr></tbody>';
	$strForm .= '</table>';

	/* After posting... */
	$strForm .= getFormTitleNav($oL->m('options'));
	$tmp['after_post'] = $oSess->user_get('after_post_import');
	if (!$tmp['after_post'])
	{
		/* turn on "Import" option by default */
		$tmp['after_post'] = GW_AFTER_TERM_GW_A_IMPORT;
	}
	$tmp['ar_after_posting'] = array(
		GW_AFTER_TERM_GW_A_IMPORT => $oL->m('3_import'),
		GW_AFTER_DICT_UPDATE => $oL->m('after_post_1'),
		GW_AFTER_SRCH_BACK => $oL->m('after_post_3')
	);
	$oForm->setTag('select', 'class',  'input50');

	$strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
	$strForm .= '<table class="gw2TableFieldset" width="100%">';
	$strForm .= '<tr><td style="width:25%"></td><td></td></tr>';

	$vars['is_overwrite'] = $oSess->user_get('import_is_overwrite') ? 1 : 0;
	$vars['is_specialchars'] = $oSess->user_get('import_is_specialchars') ? 1 : 0;
	$vars['is_whitespace'] = $oSess->user_get('import_is_whitespace') ? 1 : 0;
	$vars['is_convert_esc'] = $oSess->user_get('import_is_convert_esc') ? 1 : 0;
	$vars['is_active'] = 1;

	$strForm .= '<tr>'.
				'<td class="td1">' . $oForm->field( 'checkbox', "arPost[is_active]", $vars['is_active'] ) . '</td>'.
				'<td class="td2"><label for="arPost_is_active_">' . $oL->m('1360') . '</label></td>'.
				'</tr>';
	
	$strForm .= '<tr>'.
				'<td class="td1">' . $oForm->field( 'checkbox', "arPost[is_check_exist]", $vars['is_check_exist'] ) . '</td>'.
				'<td class="td2"><label for="arPost_is_check_exist_">' . $oL->m('1139') . '</label></td>';
	$strForm .= '</tr>';

	$strForm .= '<tr>';
	$strForm .= '<td></td><td>';

		$strForm .= '<table border="0" class="gw2TableFieldset" width="100%"><tbody>';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', "arPost[is_specialchars]", $vars['is_specialchars']) . '</td>'.
					'<td class="td2"><label for="arPost_is_specialchars_">' . $oL->m('is_specialchars') . '</label></td>'.
					'</tr>';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', "arPost[is_overwrite]", $vars['is_overwrite']) . '</td>'.
					'<td class="td2"><label for="arPost_is_overwrite_">' . $oL->m('overwrite') . '</label></td>'.
					'</tr>';
		$strForm .= '<tr><td style="width:1%"></td><td></td></tr>';
		$strForm .= '</tbody></table>';


	$strForm .= '</td>'.
				'</tr>';

	$strForm .= '<tr>'.
				'<td class="td1">' . $oForm->field('checkbox', "arPost[is_whitespace]", $vars['is_whitespace']) . '</td>'.
				'<td class="td2"><label for="arPost_is_whitespace_">' . $oL->m('1039') . '</label></td>'.
				'</tr>';
	$strForm .= '<tr>'.
				'<td class="td1">' . $oForm->field('checkbox', "arPost[is_convert_esc]", $vars['is_convert_esc']) . '</td>'.
				'<td class="td2"><label for="arPost_is_convert_esc_">' . $oL->m('1040') . '</label></td>'.
				'</tr>';
	/* 1.8.10: Maximum execution time of script, seconds */
	$max_execution_time = ini_get('max_execution_time');
	$ar_time_exec = array(5 => 5, 10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50, 60 => 60);
	if ($max_execution_time < 60)
	{
		$ar_time_exec = array();
		for ($i = 5; $i <= $max_execution_time; $i = $i + 5)
		{
			$ar_time_exec[$i] = $i;
		}
	}
	else
	{
		$ar_time_exec[$max_execution_time] = $max_execution_time;
	}
	$strForm .= '<tr>'.
				'<td class="td1">' . $oL->m(1365) .', '.  $oL->m(1368). ':</td>'.
				'<td class="td2">' . $oForm->field('select', 'arPost[max_execution_time]', $vars['max_execution_time'], '', $ar_time_exec ).
				'</td>'.
				'</tr>';

	/* After posting... */
	$strForm .= '<tr>'.
				'<td class="td1">' . $oL->m('after_post') . '</td>'.
				'<td class="td2">' . $oForm->field('select', 'arPost[after]', $tmp['after_post'], '', $tmp['ar_after_posting']).
				'</td>'.
				'</tr>';
	$strForm .= '</tbody>';
	$strForm .= '</table>';
	$strForm .= '</fieldset>';
	
	include($sys['path_include'] . '/a.import.js.php');

	$strForm .= $oForm->field('hidden', 'id', $gw_this['vars']['id']);
	$strForm .= $oForm->field('hidden', GW_TARGET, GW_T_TERMS);
	$strForm .= $oForm->field('hidden', GW_ACTION, GW_A_IMPORT);
	$strForm .= $oForm->field('hidden', $oSess->sid, $oSess->id_sess);
	if ($gw_this['vars']['arPost'][GW_ACTION] == GW_A_ADD)
	{
		$strForm .= $oForm->field("hidden", 'arPost['.GW_ACTION.']', GW_A_ADD);
	}
	else
	{
		$strForm .= $oForm->field("hidden", 'arPost['.GW_ACTION.']', GW_A_UPDATE);
	}
	return $oForm->Output($strForm);
} // end of getFormImport()
// --------------------------------------------------------
// Prepare variables
if ($gw_this['vars']['arPost'] == '') { $gw_this['vars']['arPost'] = array(); }
if (!isset($gw_this['vars']['arPost'][GW_ACTION])) { $gw_this['vars']['arPost'][GW_ACTION] = GW_A_ADD; }

switch ($gw_this['vars']['arPost'][GW_ACTION])
{
	case GW_A_ADD:
## --------------------------------------------------------
## Show HTML-form
	if ($this->gw_this['vars']['post'] == '')
	{
		/* Not saved */
		$cntFiles = 1;
		$vars['xml'] = '';
		$vars['csv'] = '';
		$vars['format'] = $this->oSess->user_get('import_format');
		$vars['is_overwrite'] = $this->oSess->user_get('import_is_overwrite') ? 1 : 0;
		$vars['is_validate'] = $this->oSess->user_get('import_is_validate') ? 1 : 0;
		$vars['is_read_first'] = $this->oSess->user_get('import_is_read_first') ? 1 : 0;
		$vars['is_check_exist'] = $this->oSess->user_get('import_is_check_exist') ? 1 : 0;
		$vars['is_convert_esc'] = $this->oSess->user_get('import_is_convert_esc') ? 1 : 0;
		$vars['is_whitespace'] = $this->oSess->user_get('import_is_whitespace') ? 1 : 0;

		$vars['max_execution_time'] = @ini_get('max_execution_time');

		if ($vars['format'] == '') { $vars['format'] = 'csv'; }

		$this->str .= getFormImport($vars, 0, 0, array());
		$arHelpMap['XML'] = htmlspecialchars_ltgt('<line><term t1="T" t2="TE" is_active="1" id="1"><![CDATA[term]]></term><defn><trsp><![CDATA[trsp]]></trsp> <abbr><![CDATA[abbr]]></abbr> <trns><![CDATA[trns]]></trns><![CDATA[defn]]> <usg><![CDATA[usg]]></usg> <syn><![CDATA[syn]]></syn> <antonym><![CDATA[antonym]]></antonym> <see><![CDATA[see]]></see> <src><![CDATA[src]]></src> <address><![CDATA[address]]></address> <phone><![CDATA[phone]]></phone></defn></line>');
		if (function_exists('xslt_create'))
		{
			$arHelpMap['validate'] = 'tip011';
		}
		$arHelpMap['overwrite'] = 'tip012';
		$arHelpMap['is_specialchars'] = 'tip013';
		$arHelpMap['1039'] = 'tip026';
		$arHelpMap['1040'] = 'tip027';

		$strHelp = '';
		$strHelp .= '<dl>';
		for (; list($k, $v) = each($arHelpMap);)
		{
			$strHelp .= '<dt><b>' . $oL->m($k) . '</b></dt>';
			$strHelp .= '<dd>' . $oL->m($v) . '</dd>';
		}
		$strHelp .= '</dl>';
		$this->str .= '<br />' . kTbHelp($oL->m('2_tip'), $strHelp);
	}
	else
	{
	  
		$file_location = array('name' => '');
		/* Shorthand */
		if (isset($this->gw_this['vars']['_files']['file_location']))
		{
			$file_location = $this->gw_this['vars']['_files']['file_location'];
		}
		if ($file_location['name'] && !$file_location['size'])
		{
			$this->str .= '<p class="xu">0 '.$this->oL->m('kb').'</p>';
			return;
		}

		$arStatus = array();
		$xml_file = '';
		$cnt = 0;
		$this->sys['import_filename'] = '';
		$this->sys['import_format'] = 'xml';

		$this->gw_this['vars']['int_items_added'] = 
		$this->gw_this['vars']['int_items_passed'] = 
		$this->gw_this['vars']['int_items_left'] = 0;

		/* Start import */
		/* Unpack keys, set delay for key write (See MySQL Manual 5.2.12)*/
#		$oDb->sqlExec('ALTER TABLE `'. TBL_WORDLIST .'` PACK_KEYS=0 CHECKSUM=0 DELAY_KEY_WRITE=1');
#		$oDb->sqlExec('ALTER TABLE `'. TBL_WORDMAP .'` PACK_KEYS=0 CHECKSUM=0 DELAY_KEY_WRITE=1');
#		$oDb->sqlExec('ALTER TABLE `'. $arDictParam['tablename'] .'` PACK_KEYS=0 CHECKSUM=0 DELAY_KEY_WRITE=1');
		/* Start timer */
		$oTimer = new gw_timer('import');
		/* Fix on/off options */
		$arIsV = array('is_validate', 'is_overwrite', 'is_specialchars', 'is_whitespace',
						'is_convert_esc', 'is_read_first', 'is_check_exist', 'is_active');
		for (; list($k, $v) = each($arIsV);)
		{
			$gw_this['vars']['arPost'][$v] = isset($gw_this['vars']['arPost'][$v]) ? $gw_this['vars']['arPost'][$v] : 0;
		}
		$this->oSess->user_set('import_format', $gw_this['vars']['arPost']['format']);
		$this->oSess->user_set('import_is_check_exist', $gw_this['vars']['arPost']['is_check_exist']);
		$this->oSess->user_set('import_is_overwrite', $gw_this['vars']['arPost']['is_overwrite']);
		$this->oSess->user_set('import_is_specialchars', $gw_this['vars']['arPost']['is_specialchars']);
		$this->oSess->user_set('import_is_whitespace', $gw_this['vars']['arPost']['is_whitespace']);
		$this->oSess->user_set('import_is_convert_esc', $gw_this['vars']['arPost']['is_convert_esc']);

		/* Switch formats */
		switch ($gw_this['vars']['arPost']['format'])
		{
			case 'xml':
				gw_import_xml();
			break;
			case 'csv':
				gw_import_csv();
			break;
		}
		/* Clean search results for edited dictionary */
		$arQ[] = $oSqlQ->getQ('del-srch-by-dict', $arDictParam['id']);
		
		/* Clear dictionary cache (hidden) */
		gw_tmp_clear($arDictParam['id']);
		
		/* Update dictionary settings */
		/* Requires $arDictParam['id'], $arDictParam['tablename'] */
		gw_sys_dict_update();

		/* 1.8.9: Show progress bar */
		/* 1.8.10: Simplify progress bar */
		if ($this->gw_this['vars']['int_items_left'])
		{
			/* */
			$int_pbar = intval( (100 / $gw_this['vars']['int_items_total']) * $gw_this['vars']['int_items_passed'] );
			/* Correct the last step */
			if ($int_pbar > 99){ $int_pbar = 99; }
			$this->str .= text_progressbar($int_pbar, $this->ar_theme['color_a_hover'], $this->ar_theme['color_5']);
		}
		else
		{
			$this->str .= text_progressbar(100, $this->ar_theme['color_black'], $this->ar_theme['color_4']);

			/* Close import session */
			if (isset($gw_this['vars']['arPost']['id_session']))
			{
				$q['date_end'] = $this->sys['time_now_gmt_unix'];
				$q['int_items_passed'] = $this->gw_this['vars']['int_items_passed'];
				$this->oDb->sqlExec( gw_sql_update($q, $this->sys['tbl_prefix'].'import_sessions', 'id_session = "'. $this->gw_this['vars']['arPost']['id_session'].'"') );
			}

		}

		/* Show time usage */
		$this->str .= '<table cellpadding="2" cellspacing="1" width="100%" border="0"><tbody>';
		/* Max execution time */
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m(1365).', '.$oL->m(1368).':</td><td><strong>' . $this->oFunc->number_format($arPost['max_execution_time'], 0, $this->oL->languagelist('4')) . '</strong></td></tr>';
		/* Memory spend */
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m(1366).', '.$oL->m('kb').':</td><td><strong>' . $this->oFunc->number_format($sys['memory_spend']/1024, 0, $this->oL->languagelist('4')) . '</strong></td></tr>';
		/* Time spend, this step */
		$sys['time_spend'] = $oTimer->end('import');
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].';width:50%">'.$oL->m(1367).', '.$oL->m(1368).':</td>';
		$this->str .= '<td style="width:50%"><strong>' . $this->oFunc->number_format($sys['time_spend'], 2, $this->oL->languagelist('4')) . '</strong></td></tr>';
		/* Time spend, all steps in total */
		if (!isset($gw_this['vars']['date_start']))
		{
			$gw_this['vars']['date_start'] = $this->sys['time_now_gmt_unix'] - $sys['time_spend'];
		}
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m(1369).':</td><td><strong>' . $this->oFunc->date_SecToTime($this->sys['time_now_gmt_unix']-$gw_this['vars']['date_start'])  . '</strong></td></tr>';
		/* Filename */
		if (isset($file_location['name']) && ($file_location['name'] != ''))
		{
			$this->str .= '<tr class="xt"><td colspan="2">&#160;</td></tr>';
			$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m(1321).':</td><td><strong>' . $file_location['name'] . '</strong></td></tr>';
			$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m('size').', '.$oL->m('bytes').':</td><td><strong>'.$this->oFunc->number_format($file_location['size'], 0, $oL->languagelist('4')).'</strong></td></tr>';
		}
		$this->str .= '<tr class="xt"><td colspan="2">&#160;</td></tr>';
		/* The number of added terms */
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m('termsamount').':</td><td><strong>'.$this->oFunc->number_format($this->gw_this['vars']['int_items_added'], 0, $oL->languagelist('4')).'</strong></td></tr>';

		/* redirect to... */
		$str_url = gw_after_redirect_url($gw_this['vars']['arPost']['after']);

		/* Go for the next part */
		$str_countdown = '';
#		$sys['time_refresh'] = 11;
		if ($this->gw_this['vars']['int_items_left'])
		{
			/* countdown */
			$str_countdown = '<script type="text/javascript">
				var total_sec = '.$sys['time_refresh'].'+1;
				function display_countdown() {
					gw_getElementById("countdown").innerHTML = total_sec--;
					if (total_sec > -1) {
						setTimeout(\'display_countdown()\', 1000);
					}
				}
				display_countdown();
			</script>';
			/* Redirect URL */
			$str_url = 'a=import&arPost[format]='.$gw_this['vars']['arPost']['format'].'&arPost[after]='.GW_AFTER_TERM_GW_A_IMPORT.'&'.GW_TARGET.'='.GW_T_TERMS.'&id='.$this->gw_this['vars']['id'].'&post=1&arPost[id_session]='.$this->gw_this['vars']['id_session'];
			$this->oTpl->addVal( 'v:meta_refresh', gethtml_metarefresh($this->sys['page_admin'].'?'.$str_url, $this->sys['time_refresh']) );
			$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'. sprintf("%s+:</td><td><strong>%s</strong></td></tr>", $oL->m('termsamount'), $this->oFunc->number_format($gw_this['vars']['int_items_left'], 0, $oL->languagelist('4')) );
		}
		/* The number of total terms */
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'.$oL->m('total').':</td><td><strong>'.$this->oFunc->number_format($this->gw_this['vars']['int_items_total'], 0, $oL->languagelist('4')).'</strong></td></tr>';
		/* Terms per second */
		$this->str .= '<tr class="xt"><td style="text-align:'.$sys['css_align_right'].'">'. sprintf("%s, %s:</td><td><strong>%s</strong></td></tr>", $oL->m('1149'), $oL->m('1150'), $this->oFunc->number_format($gw_this['vars']['int_items_added']/$oTimer->end('import'), 1, $this->oL->languagelist('4')) );
		$this->str .= '</tbody></table>';

		$this->str .= '<ul class="gwsql">';
		for (reset($arStatus); list($k, $t) = each($arStatus);)
		{
			$t = str_replace("\r\n", ' ', $t);
			$t = str_replace("\n", ' ', $t);
			$t = str_replace("\r", ' ', $t);
			$t = str_replace("\t", ' ', $t);
			$t = htmlspecialchars_ltgt($t);
			$t = gw_highlight_sql($t);
			$this->str .= '<li>'.$t.'</li>';
		}
		$this->str .= '</ul>';
		/* */
#		$this->oDb->sqlExec('CHECK TABLE `'. $arDictParam['tablename'] .'`');
		/* Redirect to... */
		if ($this->gw_this['vars']['int_items_total'])
		{
			$this->str .= postQuery($arQ, $str_url, $sys['isDebugQ'], 1);
		}
		$this->str .= $str_countdown;
		return;
	}
##
## --------------------------------------------------------
break;
default:
break;
}

/* End of file */
?>