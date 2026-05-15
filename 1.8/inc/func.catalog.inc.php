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
	die('<!-- Not in App -->');
}
/**
 *  Catalog functions.
 *
 *  @package gw_admin
 */
// --------------------------------------------------------

/**
 * Returns the list of terms like "The Contents".
 * 15 March 2008: One SQL-request per each letter.
 *
 * @param    string  $dict_tablename     Dictionary table name
 * @param    int     $id_dict            Dictionary ID, for links only
 * @return   string  The contents
 */
function gw_get_dict_terms($dict_tablename, $id_dict)
{
	global $oDb, $oSqlQ, $oHtml, $oFunc;
	global $sys, $arDictParam, $gw_this;
	$str = '';
	/* Select alphabetic order */
	$ar0z = getLettersArray($arDictParam['id']);

	/* For each letter */
	foreach ($ar0z as $letter => $azv)
	{
		$str .= '<h5>'. $oHtml->a($sys['page_index'].
					'?'.GW_ACTION.'='.GW_A_LIST.
					'&'.GW_ID_DICT.'='.$id_dict.
					'&w1='.$letter, $letter).'</h5>';
		/* */
		$sql = $oSqlQ->getQ('get-az-terms', $dict_tablename, $letter, $sys['time_now_db'], $arDictParam['az_sql'], $sys['max_terms_in_index']);
		$arSql = $oDb->sqlExec($sql);
		$ar_terms = [];
		foreach ($arSql as $arK => $arV)
		{
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$arV['id_term'] = urlencode($arV['term']);
				break;
				case GW_PAGE_LINK_URI:
					$arV['id_term'] = ($arV['term_uri'] == '') ? urlencode($arV['term']) : urlencode($arV['term_uri']);
				break;
				default:
				break;
			}
			$ar_terms[] = $oHtml->a($sys['page_index'].
					'?'.GW_TARGET.'='.$arV['id_term'].
					'&'.GW_ACTION.'='.GW_T_TERM.
					'&'.GW_ID_DICT.'='.$id_dict,
				trim(strip_tags($arV['term'])));
		}
		$str .= '<p class="xu">'.implode(', ', $ar_terms).'&#8230;</p>';
	}
	return $str;
}

/**
 * Main function for listing terms
 *
 * @param    string  $w1      1st search string
 * @param    string  $w2      2nd search string
 * @param    string  $w3      3rd search string
 * @param    int     $id_dict Dictionary ID
 * @param    int     $p       Current page number
 * @param    bool    $descr   True: display description
 * @param    bool    $full    True: display full mode
 * @param    bool    $kb      True: display size in kbytes
 * @return   array   [0] -> string, term items, [1] -> integer, total terms
 */
function getDictWordList($w1, $w2, $w3, $id_dict, $p, $is_descr = true, $is_full = false)
{
	global $oDb, $oSqlQ, $oHtml, $oFunc;
	global $oL, $sys, $arDictParam, $arFields, $ar_theme, $gw_this;
	$strA = array(0 => '', 1 => '', 2 => '', 3=> '');

	/* Count the number of terms before to list */
	if (($w1 != '') && ($w2 == ''))
	{
		/* Alphabetic order 1 selected */
		$sql = $oSqlQ->getQ('cnt-term-by-t1', $arDictParam['tablename'], gw_text_sql($w1), $sys['time_now_db']);
	}
	elseif (($w1 != '') && ($w2 != '') && ($w3 != ''))
	{
		/* Alphabetic orders 1, 2, 3 selected */
		$sql = $oSqlQ->getQ('cnt-term-by-t1t2t3', $arDictParam['tablename'], gw_text_sql($w1), gw_text_sql($w2), gw_text_sql($w3), $sys['time_now_db']);
	}
	elseif (($w1 != '') && ($w2 != ''))
	{
		/* Alphabetic orders 1 and 2 selected */
		$sql = $oSqlQ->getQ('cnt-term-by-t1t2', $arDictParam['tablename'], gw_text_sql($w1), gw_text_sql($w2), $sys['time_now_db']);
	}
	else
	{
		/* per page */
		$sql = $oSqlQ->getQ('cnt-term', $arDictParam['tablename'], $sys['time_now_db']);
	}
	$arSql = $oDb->sqlExec($sql);
	$strA[1] = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
	$strA[3] = ceil($strA[1] / $arDictParam['page_limit']);
	if ( ( $p < 1 ) || ( $p > $strA[3]) ){ $p = 1; }
	$limit = $oDb->prn_limit($strA[1], $p, $arDictParam['page_limit']);
	/* */
	$sql_defn = 't.defn';
	/* */
	if (($w1 != '') && ($w2 == ''))
	{
		$sql = $oSqlQ->getQ('get-term-by-t1', $sql_defn, $arDictParam['tablename'], gw_text_sql($w1), $sys['time_now_db'], $arDictParam['az_sql']) . $limit;
	}
	else if (($w1 != '') && ($w2 != '') && ($w3 != ''))
	{
		$sql = $oSqlQ->getQ('get-term-by-t1t2t3', $sql_defn, $arDictParam['tablename'], gw_text_sql($w1), gw_text_sql($w2) , gw_text_sql($w3), $sys['time_now_db'], $arDictParam['az_sql']) . $limit;
	}
	else if (($w1 != '') && ($w2 != ''))
	{
		$sql = $oSqlQ->getQ('get-term-by-t1t2', $sql_defn, $arDictParam['tablename'], gw_text_sql($w1), gw_text_sql($w2), $sys['time_now_db'], $arDictParam['az_sql']) . $limit;
	}
	else
	{
		$sql = $oSqlQ->getQ('get-term-by-page', $sql_defn, $arDictParam['tablename'], $sys['time_now_db'], $arDictParam['az_sql'], $limit);
	}

	$arSql = $oDb->sqlExec($sql);
	$arA = [];
	$cnt = 0;
	$delmtr = ' ';
	if ($is_full)
	{
		global $gw_this;

		$tmp['cssTrClass'] = 'xt';
		$tmp['xref'] = $sys['page_index'] . '?'.GW_ACTION.'='.GW_A_SEARCH.'&amp;srch[adv]=phrase&amp;d='.$arDictParam['id'].'&amp;srch[by]=d&amp;srch[in]=1&amp;q=';
		$tmp['href_srch_term'] = $sys['page_index'] . '?'.GW_ACTION.'='.GW_A_SEARCH.'&amp;srch[in]=1&amp;d=%d&amp;q=%s&amp;srch[adv]=phrase';
		$tmp['href_link_term'] = $sys['page_index'] . '?'.GW_ACTION.'=term&amp;d=%d&amp;q=%s';
		$arDictParam['lang'] = $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'];

		$oRender = new $gw_this['vars']['class_render'];
		$oRender->Set('Gsys', $sys );
		$oRender->Set('oL', $oL );
		$oRender->Set('arDictParam', $arDictParam );
		$oRender->Set('arFields', $arFields );
		$oRender->load_abbr_trns();

		foreach ($arSql as $arK => $arV)
		{
			$arA[$arK]['defn'] = $arA[$arK]['term'] = '';
			// Render HTML page, 25 apr 2003
			//
			$arPre = gw_Xml2Array($arV['defn']);
			$tmp['term'] = $arV['term'];
			$tmp['t1'] = '';
			$tmp['t2'] = '';
			$tmp['tid'] = $arV['id_term'];
			$tmp['date_created'] = $arV['date_created'];
			$tmp['date_modified'] = $arV['date_modified'];
			//
			$objDom = new gw_domxml;
			$objDom->setCustomArray($arPre);
			$oRender->Set('Gtmp', $tmp );
			$oRender->Set('objDom', $objDom );
			$oRender->Set('arEl', $arPre );
			//
			$tmp['str_defn'] = $oRender->array_to_html($arPre);
			/* Process text filters */
				if (!$sys['is_debug_output'] && is_array($sys['filters_defn']))
				{
					foreach ($sys['filters_defn'] as $k => $v)
					{
						$tmp['str_defn'] = $v($tmp['str_defn']);
					}
				}
			//
			$arA[$arK]['term'] =& $arV['term'];
			$arA[$arK]['defn'] = $tmp['str_defn'];
			$arA[$arK]['t_id'] =& $arV['t_id'];
			$arA[$arK]['d_id'] =& $id_dict;
			$arA[$arK]['kb'] = $arV['int_bytes'];
			if ($arDictParam['is_show_full'])
			{
				$arA[$arK]['kb'] = '';
			}
			/* $tag_stress_rule */
			$ar_pairs_src = explode("|", $oRender->tag_stress_rule);
			$arA[$arK]['term'] = str_replace('<stress>', $ar_pairs_src[0], $arA[$arK]['term']);
			$arA[$arK]['term'] = str_replace('</stress>', $ar_pairs_src[1], $arA[$arK]['term']);
			$arA[$arK]['defn'] = str_replace('<stress>', $ar_pairs_src[0], $arA[$arK]['defn']);
			$arA[$arK]['defn'] = str_replace('</stress>', $ar_pairs_src[1], $arA[$arK]['defn']);
			/* Strike */
			preg_match_all("/<strike>(.*?)<\/strike>/", $arA[$arK]['defn'], $ar);
			if (!empty($ar[0]))
			{
				$arA[$arK]['defn'] = str_replace($ar[0][0], '<span class="strike">'.$ar[1][0].'</span>', $arA[$arK]['defn']);
			}
		}
	}
	else
	{
		$arA = gw_sql2defnpreview($arSql);
	}
	global $oTpl;
	/* */
	/* Prepare parsed data for template */
	$odd = 1;
	$intAr = sizeof($arA);
#	$intRowStep = $sys['dplayout'] ? ceil($intAr / $sys['dplayout']) : 0;
	$intRowStep = 2;
	$intColStep = 1;
	$tmp['href_term'] = [];
	if (empty($arA[0]))
	{
		return;
	}
	foreach ($arA as $k1 => $v1)
	{
		/* Collect data for template */
		if (GW_IS_BROWSE_WEB)
		{
			# 'onmouseover="popLayer(\''.$v1['term'].'\')" onmouseout="hideLayer()"'
		}
		$oTpl->tmp['d']['list_item'][$k1]['v:term'] =& $v1['term'];
		$oTpl->tmp['d']['list_item'][$k1]['v:defn'] =& $v1['defn'];
		$oTpl->tmp['d']['list_item'][$k1]['v:kb'] =& $v1['kb'];
		if ((GW_IS_BROWSE_WEB && trim($oTpl->tmp['d']['list_item'][$k1]['v:defn']) == '')
			|| $is_full)
		{
			$oTpl->tmp['d']['list_item'][$k1]['v:term'] = $v1['term'];
		}
		// Sets even, odd colors
		$oTpl->tmp['d']['list_item'][$k1]['v:term_number'] = ((($p - 1) * $arDictParam['page_limit']) + $k1 + 1);

		if ((($k1+1) % $intRowStep) == 1)
		{
			$odd = 0;
		}
		else
		{
			$odd++;
		}
		// One row
		if ($intRowStep == 1){ $odd = 0; }
		if ($odd % 2)
		{
			$oTpl->tmp['d']['list_item'][$k1]['v:color_odd'] = $ar_theme['color_2'];
			$oTpl->tmp['d']['list_item'][$k1]['v:color_even'] = $ar_theme['color_1'];
		}
		else
		{
			$oTpl->tmp['d']['list_item'][$k1]['v:color_odd'] = $ar_theme['color_1'];
			$oTpl->tmp['d']['list_item'][$k1]['v:color_even'] = $ar_theme['color_2'];
		}
		$oTpl->tmp['d']['list_item'][$k1]['v:css_align_right'] = $sys['css_align_right'];
		$oTpl->tmp['d']['list_item'][$k1]['v:css_align_left'] = $sys['css_align_left'];
	}
	$oTpl->addVal( 'v:terms_on_page', $oFunc->number_format($strA[1], 0, $oL->languagelist(LOCALE_LANG_RULES)) );
	$oTpl->addVal( 'l:terms_on_page', $oL->m('str_on_page') );
	$oTpl->addVal( 'v:page_of_page',  sprintf(
        $oL->m('str_page_of_page'),
        $oFunc->number_format($p, 0, $oL->languagelist(LOCALE_LANG_RULES)), $oFunc->number_format($strA[3], 0, $oL->languagelist(LOCALE_LANG_RULES)))
    );

	global $gw_this;
	if (GW_IS_BROWSE_WEB && $ar_theme['columns'] > 1)
	{
		include_once( $sys['path_gwlib'] . '/class.cells_tpl.php' );
		$oCells = new gw_cells_tpl();
		$oCells->class_tpl = $sys['class_tpl'];
		$oCells->tpl = 'tpl_cells_term';
		$oCells->id_theme = $gw_this['vars']['visualtheme'];
		$oCells->arK =& $oTpl->tmp['d']['list_item'];
		$oCells->X = $ar_theme['columns'];
		$oCells->Y = $arDictParam['page_limit'];
		$oCells->int_page = $gw_this['vars']['p'];
		$oCells->is_odd = 1;
		$oCells->tSpacing = 1;
		$oCells->tPadding = 0;
		$oCells->tAttrClass = 'tbl-browse';
		$oTpl->addVal( 'block:columns', $oCells->output());
		$oTpl->tmp['d']['list_item'] = [];
	}
	else if (GW_IS_BROWSE_WEB)
	{
		$strA[0] = '';
		$oTpl->tmp['d']['if:one_column'] = true;
	}
	return $strA;
}

/**
 * Receives the list of dictionaries.
 *
 * @return  array   All dictionaries with Dictionary ID as key and dictionary settings as value
 */
function getDictArray()
{
	global $oSqlQ, $oDb, $oSess;
	global $sys;
	$arSql = [];
	if (GW_IS_BROWSE_ADMIN)
	{
		// not guest, get all dictionaries.
		// disable caching for admin mode
		$arSql = $oDb->sqlExec( $oSqlQ->getQ('get-dicts-admin') );
	}
	else
	{
		// guest: show only active dictionaries
		// use cache
		$arSql = $oDb->sqlExec( $oSqlQ->getQ('get-dicts-web', $sys['time_now_db']) );
	}
	/* Resort using Dictionary ID */
	$arSqlNew = [];
	foreach ($arSql as $k => $v)
	{
		unset($arSql[$k]);
		$arSqlNew[$v['id']] = $v;
	}
	return $arSqlNew;
}
/**
 * Constructs search form with all dictionaries
 *
 * @return  string Complete HTML-code
 */
function getDictSrch($language = '', $x = 1, $y = 99, $qStrOrder = '', $is_form_only = 0, $id_dict = 0)
{
	global $gw_this, $oL, $sys;
	$str = '';
	$arSql = $gw_this['ar_dict_list'];
	$arDictMap = [];
	if (sizeof($arSql) > 0)
	{
		if (GW_IS_BROWSE_WEB)
		{
			#$arDictMap[0] = '-'.$oL->m('1115').'-';
		}
		foreach ($arSql as $arK => $arV)
		{
			$arDictMap[$arV['id']] = strip_tags($arV['title']);
		}
		/* Sort alphabetically */
		asort($arDictMap);
		if ($is_form_only && GW_IS_BROWSE_WEB)
		{
			$arDictMap = gw_array_merge_clobber(array(0 => $oL->m('srch_all')), $arDictMap);
		}
	}
	else
	{
		$arDictMap[0] = $oL->m('reason_4');
		$str .= '<strong>' . $oL->m('reason_4') . '</strong>';
	}
	$intRand = 0;
	$strRandValue = '';
#	$randSet[0][] = '';
#	$randarray = array("0");
#	$intRand = $randarray[rand(0, (sizeof($randarray)-1))];
#	if (isset($randSet[$intRand]))
#	{
#		$strRandValue = $randSet[$intRand][rand(0, (sizeof($randSet[$intRand])-1) )];
#	}
	global $oTpl;
	$oTpl->AddVal( 'v:q_rnd', $strRandValue );
	if ($is_form_only)
	{
		return gw_html_forms_select($arDictMap, $id_dict, 'd', 'input', 'width:100%', 'ltr');
	}
}


/**
 * Get the list of dictionaries
 *
 * @param    string $v:language   Language
 * @param    int $dict_nmax  links per category
 * @param    int $x  X
 * @param    int $y  Y
 * @param    string  $qStrOrder   Order type for SQL-query
 * @return   string  html-code
 * @global   int    $sys
 * @global   int    $gw_this
 * @global   int    $ar_theme
 */
function getDictList($language = '', $dict_nmax = 5, $x = 1, $y = 99, $qStrOrder = '')
{
    global $sys, $gw_this, $ar_theme;
    global $oHtml, $oFunc, $oSess, $oL;

    $str = '';
    $arSql =& $gw_this['ar_dict_list'];
    $languagelist =& $gw_this['vars']['ar_languages'];
    if (sizeof($arSql) == 0) // no dictionaries
    {
        return '<strong>' . $oL->m('reason_4') . '</strong>';
    }
    $cnt = 0;
    $strGroupBy = 'tpname';
    foreach ($arSql as $arK => $arV) {
        switch ($sys['pages_link_mode']) {
            case GW_PAGE_LINK_NAME:
                $arV['uri'] = urlencode($arV['title']);
                break;
            case GW_PAGE_LINK_URI:
                $arV['uri'] = urlencode($arV['dict_uri']);
                break;
            default:
                $arV['uri'] = $arV['id'];
                break;
        }
        $arDictMap[$arV['id_topic']][$arK] = $arV;
    }
    // get topics map
    $ar =& $gw_this['ar_topics_list'];
    if (empty($ar)) {
        return;
    }
    // display catalog in web mode
    if (GW_IS_BROWSE_WEB) {
        $page_index =& $sys['page_index'];
        $str = getCatalogTitle($ar, $arDictMap, 0, 1, $dict_nmax);
    }
    return $str;
}


/**
 * Recursive function.
 * Returns title page for catalog.
 *
 * @return  array
 */
function getCatalogTitle($ar, $arDictMap, $p = 0, $depth = 1, $dict_nmax, $runtime = 0)
{
	global $curDateMk, $curDate, $oL, $sys, $oFunc, $oHtml, $gw_this, $ar_theme;

	$str = '';
	$runtime++;
	/* Other settings */
	$tpcs_depth = 0;
	$tpcs_nmax = 0;
	$arLanguages = $oL->languagelist();
	
	if (isset($ar[$p]['ch'])) /* a child from Root found */
	{
		$cntTopic = 0;
		// removes limit for catalog page
		$tpcs_nmax = $dict_nmax;
		//
		$str .= CRLF . '<dl class="catalog">';
		foreach ((is_array($ar[$p]['ch']) ? $ar[$p]['ch'] : []) as $k => $v) // (Root or Topic) -> Topic
		{
			/* Reserved for dictionary parameters */
#			prn_r( $k );
			if ($cntTopic > -1) // unlimit topics
			{
				// topic code, term
				$str .= '<dt>';
				$str .= '<span class="xr">';
				// custom icons
				if ($sys['is_list_images'])
				{
					$ar[$k]['topic_icon'] = ($ar[$k]['topic_icon'] == '') ? 'icon_16_topic.gif' : $ar[$k]['topic_icon'];
					$file_icon = $sys['path_temporary'] . '/t/' . $gw_this['vars']['visualtheme'] . '/' . $ar[$k]['topic_icon'];
					if (file_exists($file_icon))
					{
						$str .= '<img style="vertical-align:top" src="' . $sys['server_dir'] . '/' . $file_icon . '" ' .
						' width="16" height="16" alt="" />&#160;';
					}
				}
				$str .= $ar[$k]['title'] . '</span>';
				/* 2006 Jun 23: Show topic description */
				if ($sys['is_show_topic_descr'] &&  $ar[$k]['topic_descr'] != '')
				{
					$str .= '</dt><dt style="padding-left:1.0em" class="xu">';
					$str .= $ar[$k]['topic_descr'];
				}
				$str .= '</dt>';
				// topic code, definition
				$str .= '<dd>';
				//
				if (isset($arDictMap[$k])) // dictionary found
				{
					$cntDict = 0;
					$str .= CRLF . '<dl>';
					foreach ((is_array($arDictMap[$k]) ? $arDictMap[$k] : []) as $k2 => $v2)
					{
						$strMark = '';
						$idcolor = '#999';
						// define "GW_A_UPDATED" mark
						if ( ($sys['time_now_gmt_unix'] - $v2['date_modified']) < ($sys['time_upd'] * 86400) )
						{
							$strMark = '&#160;' . $oL->m('mrk_upd');
							$idcolor = 'green';
						}
						// define "NEW" mark
						if ( ($sys['time_now_gmt_unix'] - $v2['date_created']) < ($sys['time_new'] * 86400) )
						{
							$strMark = '&#160;' . $oL->m('mrk_new');
							$idcolor = 'red';
						}
						if ($cntDict < $dict_nmax) // dictionaries limit
						{
							$strIcon = '';
							if ($sys['is_list_images'])
							{
								$file_icon = $sys['path_temporary'] . '/t/' . $gw_this['vars']['visualtheme'] . '/icon_16_dict.gif';
								if (file_exists($file_icon))
								{
									$strIcon = '<img style="margin:1px;vertical-align:middle" src="' . $sys['server_dir'] . '/' . $file_icon . '" ' .
									'width="16" height="16" alt="" />&#160;';
								}
							}
							$str .= '<dt class="xt">' . ($sys['is_list_images'] ? $strIcon : '') . $oHtml->a( $sys['page_index']  . "?a=index&d=" . $v2['uri'],
									$v2['title']);
							if ($sys['is_list_numbers'])
							{
								$str .= '&#32;(' . $oFunc->number_format($v2['int_terms'], 0, $oL->languagelist(LOCALE_LANG_RULES)) . ')';
							}
							/* mark as foreign language */
							if ($v2['lang'] != $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'])
							{
								$str .= isset($arLanguages[$v2['lang']]) ? ' ' . $arLanguages[$v2['lang']] . '' : '';
							}
							//
							if ($strMark != '') // show previously defined mark with previously defined color
							{
								$str .= '<span class="' . $idcolor . '">' . $strMark . '</span>';
							}
							$str .= '</dt>';
							// topic ends
							// Short description
							if ($sys['is_list_announce']) // show announce only for catalog
							{
								// announce
								if (!empty($v2['announce']) && $v2['announce'] != '-')
								{
									$str .= '<dd class="xt">' . $v2['announce'] . '</dd>';
								}
								else
								{
									$str .= '<dd></dd>';
								}
							} // catalog mode
						} // end of dictionaries limit
						elseif ($cntDict == $dict_nmax)
						{
							$str .= '<dt class="xt">' . $oHtml->a( $sys['page_index'] . '?a=catalog', $oL->m('more') . '&#8230;') . '</dt>';
						}
						else
						{
							continue;
						} // end of $dict_nmax limit
						$cntDict++;
					} // while
					$str .= '</dl>';
					//
					if (!isset($ar[$k]['ch']))
					{
						$str .= '</dd>';
					}
				} // end of (isset($arDictMap[$k]) = if dictionary in topics exists)
				else
				{
					if (!isset($ar[$k]['ch']))
					{
						$str .= '</dd>';
					}
				}
#				if (GW_IS_BROWSE_ADMIN || $gw_this['vars'][GW_ACTION] == '' || $gw_this['vars'][GW_ACTION] == 'catalog') // catalog page
#				{
					$str .= getCatalogTitle($ar, $arDictMap, $k, $depth + 1, $dict_nmax, $runtime);
#				}
				if (isset($ar[$k]['ch']))
				{
					$str .= '</dd>';
				}
			} // topic limit
			else if ($cntTopic == $tpcs_nmax)
			{
				$str .= '<dt class="xr">'
					 .'<img src="'.$GLOBALS['sys']['path_img'] . '/16_folder.gif" width="16" height="16" alt="" />&#160;'
					 . $oHtml->a( $sys['page_index'] . '?a=catalog', $oL->m('more') . '&#8230;') . '</dt>';
				$cntDict++;
			}
			else
			{
				continue;
			} // end of $tpcs_nmax limit
			$cntTopic++;
		} // while childs in root
		$str .= '</dl>';
	} // childs
	return $str;
}


/**
 * Puts catalog structure into array (thread model).
 *
 * @param   int     $id record id
 * @return  array   catalog id, catalog name
 */
function gw_create_tree_topics($id = 0)
{
	global $oSqlQ, $oDb;
	/* Disable caching for admin mode */
	if (GW_IS_BROWSE_ADMIN)
	{
		$arSqlc = $oDb->sqlExec($oSqlQ->getQ('get-subtopics-list'));
	}
	elseif (GW_IS_BROWSE_WEB)
	{
		$arSqlc = $oDb->sqlRun($oSqlQ->getQ('get-subtopics-list', 'AND tp.is_active = "1"'), 'st');
	}
	/* Create the list of topics */
	$arSqlc = gw_rearrange_to_locale($arSqlc, 'id_topic');
	return gw_rearrange_to_tree($arSqlc, $id, 'id_topic');
}

/* */
function gw_create_flat_custom_pages($id = 0)
{
    global $oSqlQ, $oDb;
    /* Disable caching for admin mode */
    if (GW_IS_BROWSE_ADMIN) {
        $arSqlc = $oDb->sqlExec($oSqlQ->getQ('get-custompages-list'));
    } elseif (GW_IS_BROWSE_WEB) {
        $arSqlc = $oDb->sqlRun($oSqlQ->getQ('get-custompages-list', 'AND cp.is_active = "1"'), 'st');
    }
    /* Create the list of topics */
    $arSqlc = gw_rearrange_to_locale($arSqlc, 'id_page');

    return $arSqlc;

    #return gw_rearrange_to_tree($arSqlc, $id, 'id_page');
}


/**
 * Rearrange flat rows into a tree structure.
 *
 * The second argument is kept for backward compatibility and is not used.
 *
 * @param array $ar_sql
 * @param int $unused_id
 * @param string $id_name
 *
 * @return array
 */
function gw_rearrange_to_tree2(array $ar_sql, $root_id = 0, $id_name = 'id_page')
{
    $tree = [
        $root_id => [],
    ];
    $rows_by_id = [];

    foreach ($ar_sql as $row) {
        $parsed = sscanf($row[$id_name], '%05d%03d');

        $int_sort = (int) $parsed[0];
        $row_id = (int) $parsed[1];

        $row['id'] = $row_id;
        $row[$id_name] = $row_id;
        $row['int_sort'] = $int_sort;

        $parent_id = (int) $row['p'];

        $rows_by_id[$row_id] = $row;

        if (!isset($tree[$parent_id])) {
            $tree[$parent_id] = [];
        }

        $tree[$parent_id]['ch'][$row_id] = $row_id;
        $tree[$parent_id]['max'] = $row_id;

        if (!isset($tree[$parent_id]['min'])) {
            $tree[$parent_id]['min'] = $row_id;
        }
    }

    /* Merge row data into tree nodes. */
    foreach ($rows_by_id as $row_id => $row) {
        if (!isset($tree[$row_id])) {
            $tree[$row_id] = $row;
            continue;
        }

        foreach ($row as $field_name => $field_value) {
            $tree[$row_id][$field_name] = $field_value;
        }
    }

    return $tree;
}


/**
 * Rearrange flat localized rows to parent-child tree.
 *
 * Topics keep nested structure.
 *
 * @param array $ar_sql
 * @param int $root_id
 * @param string $id_name
 *
 * @return array
 */
function gw_rearrange_to_tree(array $ar_sql, $root_id = 0, $id_name = 'id_page')
{
    $root_id = (int) $root_id;

    $tree = [
        $root_id => [],
    ];

    $rows_by_id = [];

    foreach ($ar_sql as $row) {
        $parsed = gw_parse_composite_entity_id($row[$id_name]);

        $row_id = (int) $parsed['id'];
        $parent_id = (int) $row['p'];

        $row['id'] = $row_id;
        $row[$id_name] = $row_id;
        $row['int_sort'] = (int) $parsed['int_sort'];

        $rows_by_id[$row_id] = $row;

        if (!isset($tree[$parent_id])) {
            $tree[$parent_id] = [];
        }

        $tree[$parent_id]['ch'][$row_id] = $row_id;
        $tree[$parent_id]['max'] = $row_id;

        if (!isset($tree[$parent_id]['min'])) {
            $tree[$parent_id]['min'] = $row_id;
        }
    }

    /* Merge row data into tree nodes. */
    foreach ($rows_by_id as $row_id => $row) {
        if (!isset($tree[$row_id])) {
            $tree[$row_id] = $row;
        } else {
            foreach ($row as $field_name => $field_value) {
                $tree[$row_id][$field_name] = $field_value;
            }
        }
    }

    return $tree;
}


/**
 * Create localized rows using composite sort+id keys.
 *
 * @param array $ar_sql
 * @param string $id_name
 *
 * @return array
 */
function gw_rearrange_to_locale($ar_sql, $id_name = 'id_page')
{
    global $gw_this;

    $ar_grouped = [];
    $ar_result = [];
    $current_lang = $gw_this['vars'][GW_LANG_I] . '-' . $gw_this['vars']['lang_enc'];

    foreach ($ar_sql as $ar_v) {
        $entity_id = (int) $ar_v[$id_name];
        $composite_id = sprintf('%05d', (int) $ar_v['int_sort']) . sprintf('%05d', $entity_id);

        $ar_v[$id_name] = $composite_id;
        $ar_grouped[$composite_id][$ar_v['id_lang']] = $ar_v;
    }

    ksort($ar_grouped, SORT_STRING);

    foreach ($ar_grouped as $ar_locales) {
        if (isset($ar_locales[$current_lang])) {
            $ar_selected = $ar_locales[$current_lang];
        } else {
            $ar_keys = array_keys($ar_locales);
            $first_key = reset($ar_keys);
            $ar_selected = $ar_locales[$first_key];
        }

        $ar_result[$ar_selected[$id_name]] = $ar_selected;
    }

    return $ar_result;
}

/**
 * Parse composite sort+id value.
 *
 * Example: 0001000001 => sort 10, id 1.
 *
 * @param mixed $value
 *
 * @return array
 */
function gw_parse_composite_entity_id($value)
{
    $value = (string) $value;

    if ($value !== '' && ctype_digit($value) && strlen($value) >= 10) {
        return [
            'int_sort' => (int) substr($value, 0, 5),
            'id' => (int) substr($value, 5, 5),
        ];
    }

    if ($value !== '' && ctype_digit($value) && strlen($value) >= 8) {
        return [
            'int_sort' => (int) substr($value, 0, 5),
            'id' => (int) substr($value, 5, 3),
        ];
    }

    return [
        'int_sort' => 0,
        'id' => (int) $value,
    ];
}

/**
 * Build tree, recursive:
 *
 * @param   array   $ar tree structure
 * @param   int     $startId parent id
 * @param   int     $cntRow number of row (counter)
 * @return compplete HTML-code
 * @globals array   $arImgTread images for branches
 * @globals int     $cntRow counter
 * @globals int     $tid
 * @globals array   $arParents
 * @globals string  $a
 * @globals int     $t
 * @globals object  $auth
 */
function ctlgGetTopicsRow($ar = [], $startId = 0, $cntRow = 1)
{
	global $arImgTread, $arTxtTread, $cntRow, $tid, $arParents, $a, $t, $sys, $ar_theme, $gw_this;
	global $oSess, $oHtml, $oL, $oFunc, $topic_mode;

    #prn_r($ar, 'ctlgGetTopicsRow');
    #return '';

	/* Using $sys variable instead of global variable */
	if (isset($sys['topic_mode']))
	{
		$topic_mode = $sys['topic_mode'];
	}
	if (isset($gw_this['vars']['id_topic']))
	{
		$tid = $gw_this['vars']['id_topic'];
	}

	if (empty($arTxtTread))
	{
		$arTxtTread['c'] = '─';
		$arTxtTread['i'] = '│';
		$arTxtTread['l'] = '└';
		$arTxtTread['m'] = '●';
		$arTxtTread['n'] = '○';
		$arTxtTread['p'] = '●';
		$arTxtTread['t'] = '├';
		$arTxtTread['trans'] = '&#160;';
		$arTxtTread['space'] = '&#160;';
	}
	$str = $strT = $image = '';
	$isDn = $isUp = 1;
	$isReset = 0;
	$page_index = $sys['page_index'];
	if (GW_IS_BROWSE_ADMIN)
	{
		$page_index = $sys['page_admin'];
	}
	/* parents with selected tid (same for delete) */
	if ($cntRow == 1)
	{
		$arParents = gw_ctlg_get_tree($ar, $tid);
	}

	if (sizeof($ar) > 0)
	{
		if ($startId != 0)
		{
			$p = $ar[$startId]['p'];
			if ($p != 0)
			{
				if (!isset($ar[$p]['img']))
				{
					$ar[$p]['img'] = '';
				}
				$image = $ar[$p]['img'];
				if ($ar[$p]['max'] == $ar[$startId]['id'])
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['l'] : $arTxtTread['l'];
				}
				else
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['t'] : $arTxtTread['t'];
				}
			} // if $p != 0;

			if (isset($ar[$startId]['ch']) && is_array($ar[$startId]['ch']))
			{
				$IsDn = $IsUp = 1;
				if (isset($ar[$p]['img']))
				{
					$ar[$startId]['img'] = $ar[$p]['img'];
					if($startId == $ar[$p]['max'])
					{
						$ar[$startId]['img'] .= ($topic_mode == 'html') ? $arImgTread['trans'] : $arTxtTread['trans'];
					}
					else
					{
						$ar[$startId]['img'] .= ($topic_mode == 'html') ? $arImgTread['i'] : $arTxtTread['i'];
					}
				}
				$image .= ($topic_mode == 'html') ? $arImgTread['m'] : $arTxtTread['m'];
			}
			else
			{
				if($ar[$startId]['p'] != 0)
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['c'] : $arTxtTread['c'];
				}
				else
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['space'] : $arTxtTread['space'];
				}
			} // isset($ar[$startId]["ch"])

			if ( isset($ar[$p]['max']) && ($ar[$p]['max'] == $startId)){ $isDn = 0; }
			if ( isset($ar[$p]['min']) && ($ar[$p]['min'] == $startId)){ $isUp = 0; $isReset = 1; }
			if (!$isUp && !$isDn) { $isReset = 0; }

			$cntRow % 2 ? ($bgcolor = $ar_theme['color_1']) : ($bgcolor = $ar_theme['color_2']);
			if ($topic_mode == 'form')
			{
				$selected = '';
				if (isset($ar[$tid]['p']))
				{
					$selected = ($ar[$tid]['p'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
				}
				$isBuild = 0;
				// do not allow to place topic under the same topic and parent under the same parent
				// Edit mode
#
# !isset($arParents[$ar[$startId]["p"]])

				if (!isset($arParents[$startId]) && ($ar[$startId]['id'] != $tid))
				{
					$isBuild = 1;
				}
				// exclusion for Add mode

				if ($a == 'add' || ($t == 'dict'))
				{
					$isBuild = 1;
					// rule for auto-selection in Add mode
					if (isset($ar[$tid]['p']))
					{
						$selected = ($ar[$tid]['id'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
					}
				}
				if ($isBuild)
				{
					/* Strip long topic names */
					$topic_len = mb_strlen($ar[$startId]['title']);
					if ($topic_len > 45)
					{
						$ar[$startId]['title'] = mb_substr($ar[$startId]['title'], 0, 45).'&#8230;';
					}
					$str .= '<option ' . $selected . 'style="background:'.$bgcolor.'" value="'.$ar[$startId]["id"].'">';
					$str .= '&#160;' . $image . '&#160;' . $ar[$startId]['title'];
					$str .= '</option>';
				}
			} // form
			elseif ($topic_mode == 'html')
			{
				$str .= '<tr style="color:'.$ar_theme['color_5'].';background:'.$bgcolor.'">';
				$str .= '<td style="text-align:'.$sys['css_align_right'].'"><span class="xt">' .  $cntRow . '</span></td>';
				$str .= '<td>';
				$str .= '<table cellspacing="0" cellpadding="0" border="0"><tbody><tr class="xu">';
				$str .= '<td style="white-space:nowrap">' . $arImgTread['space'] . $image . '</td>';
				$str .= '<td>&#160;';
				$str .= ($oSess->is('is-topics')
						? $oHtml->a(
							$page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_TOPIC.'&tid=' . $ar[$startId]['id'],
							$ar[$startId]['tpname'], '', '', $oL->m('3_edit') )
						: $ar[$startId]['tpname'] );
				$str .= '</td>';
				$str .= '</tr>';
				$str .= '</tbody></table>';
				$str .= '</td>';
				$str .= '<td class="xt" style="text-align:center">[';
				$str .= ($isUp && $oSess->is('is-topics')) ? $oHtml->a( $page_index . '?a='.GW_A_UPDATE.'&t='.GW_T_TOPIC.'&mode=up&tid=' . $ar[$startId]['id'], $oL->m('3_up')) : $oL->m('3_up');
				$str .= '] [';
				$str .= ($isDn && $oSess->is('is-topics')) ? $oHtml->a( $page_index . '?a='.GW_A_UPDATE.'&t='.GW_T_TOPIC.'&mode=dn&tid=' . $ar[$startId]['id'], $oL->m('3_down')) : $oL->m('3_down');
				$str .= '] [';
				$str .= ($isReset && $oSess->is('is-topics')) ? $oHtml->a( $page_index . '?a='.GW_A_UPDATE.'&t='.GW_T_TOPIC.'&mode=reset&tid=' . $ar[$startId]["id"], $oL->m('3_reset')) : $oL->m('3_reset');
				$str .= ']</td>';
				$str .= '<td class="xt" style="text-align:center">[';
				$str .= ($oSess->is('is-topics')) ? $oHtml->a( $page_index . '?a='.GW_A_ADD.'&t='.GW_T_TOPIC.'&tid=' . $ar[$startId]['id'], $oL->m('3_add_subtopic')) : $oL->m('3_add_subtopic');
				$str .= '] [';
				$str .= ($oSess->is('is-topics')) ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_TOPIC.'&tid=' . $ar[$startId]['id'], $oL->m('3_edit')) : $oL->m('3_edit');
				$str .= '] [';
				$str .= ($oSess->is('is-topics')) ? $oHtml->a( $page_index . '?a='.GW_A_REMOVE.'&t='.GW_T_TOPIC.'&tid=' . $ar[$startId]["id"], $oL->m('3_remove')) : $oL->m('3_remove');
				$str .= ']</td>';
				$str .= '</tr>';
			} // html
		} // end of $startId != 0

		if (isset($ar[$startId]["ch"]) && is_array($ar[$startId]["ch"]))
		{
			$cnt = sizeof($ar[$startId]["ch"]);
			for ($i = 1; $i <= $cnt; $i++)
			{
				$k = key($ar[$startId]["ch"]);
				$cntRow++;
				$strT .= ctlgGetTopicsRow($ar, $k);
				next($ar[$startId]["ch"]);
			}
		}
	} // count > 1
	 return $str . $strT;
}



/**
 * Get a branch from the tree recursively.
 *
 * Returns the specified node and all its child node IDs.
 *
 * @param array $tree Tree structure indexed by node ID.
 * @param int $node_id Start node ID.
 * @param array $result Internal accumulator.
 * @param array $visited Internal list of processed node IDs.
 * @return array
 */
function gw_ctlg_get_tree(array $tree, $node_id, array $result = [], array &$visited = [])
{
    $node_id = (int)$node_id;

    if (isset($visited[$node_id])) {
        return $result;
    }

    $visited[$node_id] = 1;
    $result[$node_id] = $node_id;

    if (!isset($tree[$node_id]['ch']) || !is_array($tree[$node_id]['ch'])) {
        return $result;
    }

    foreach ($tree[$node_id]['ch'] as $child_id => $child_value) {
        $child_id = (int)$child_id;
        $result = gw_ctlg_get_tree($tree, $child_id, $result, $visited);
    }

    return $result;
}

/* */
function gw_get_thread_pages2($ar = [], $startId = 0, $cntRow = 1)
{
    global $arImgTread, $arTxtTread, $cntRow, $arParents, $sys, $ar_theme, $gw_this;
    global $oSess, $oHtml, $oL, $oFunc;
    global $topic_mode;

    /* Using $sys variable instead of global variable */
    if (isset($sys['topic_mode'])) {
        $topic_mode = $sys['topic_mode'];
    }

    $str = $strT = $image = '';
    $isDn = $isUp = 1;
    $isReset = 0;
    $page_index = $sys['page_index'];
    if (GW_IS_BROWSE_ADMIN) {
        $page_index = $sys['page_admin'];
    }
    /* Parents with selected tid (same for delete) */
    if ($cntRow == 1) {
       $arParents = gw_ctlg_get_tree($ar, $gw_this['vars']['tid']);
    }

    if (sizeof($ar) > 0) {
        if ($startId != 0) {
            $ar[$startId]['title'] = isset($ar[$startId]['title']) ? $ar[$startId]['title'] : 0;
            $ar[$startId]['id'] = isset($ar[$startId]['id']) ? $ar[$startId]['id'] : 0;
            $p = isset($ar[$startId]['p']) ? $ar[$startId]['p'] : 0;
            if ($p != 0) {
                if (!isset($ar[$p]['img'])) {
                    $ar[$p]['img'] = '';
                }
                $image = $ar[$p]['img'];
                if ($ar[$p]['max'] == $ar[$startId]['id']) {
                    $image .= ($topic_mode == 'html') ? $arImgTread['l'] : $arTxtTread['l'];
                } else {
                    $image .= ($topic_mode == 'html') ? $arImgTread['t'] : $arTxtTread['t'];
                }
            } // if $p != 0;
            if (isset($ar[$startId]['ch']) && is_array($ar[$startId]['ch'])) {
                $IsDn = $IsUp = 1;
                if (isset($ar[$p]['img'])) {
                    $ar[$startId]['img'] = $ar[$p]['img'];
                    if ($startId == $ar[$p]['max']) {
                        $ar[$startId]['img'] .= ($topic_mode == 'html') ? $arImgTread['trans'] : $arTxtTread['trans'];
                    } else {
                        $ar[$startId]['img'] .= ($topic_mode == 'html') ? $arImgTread['i'] : $arTxtTread['i'];
                    }
                }
                $image .= ($topic_mode == 'html') ? $arImgTread['m'] : $arTxtTread['m'];
            } else {
                if ($p != 0) {
                    $image .= ($topic_mode == 'html') ? $arImgTread['c'] : $arTxtTread['c'];
                } else {
                    $image .= ($topic_mode == 'html') ? $arImgTread['space'] : $arTxtTread['space'];
                }
            }
            if (isset($ar[$p]['max']) && ($ar[$p]['max'] == $startId)) {
                $isDn = 0;
            }
            if (isset($ar[$p]['min']) && ($ar[$p]['min'] == $startId)) {
                $isUp = 0;
                $isReset = 1;
            }
            if (!$isUp && !$isDn) {
                $isReset = 0;
            }
            $cntRow % 2 ? ($bgcolor = $ar_theme['color_1']) : ($bgcolor = $ar_theme['color_2']);
            if ($topic_mode == 'form') {
                $selected = '';
                if (isset($ar[$gw_this['vars']['tid']]['p'])) {
                    $selected = ($ar[$gw_this['vars']['tid']]['p'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
                }
                $isBuild = 0;
                // do not allow to place topic under the same topic and parent under the same parent
                // Edit mode
                if (!isset($arParents[$startId]) && ($ar[$startId]['id'] != $gw_this['vars']['tid'])) {
                    $isBuild = 1;
                }
                // exclusion for Add mode
                if ($gw_this['vars'][GW_ACTION] == GW_A_ADD || ($gw_this['vars'][GW_TARGET] == GW_T_DICTS)) {
                    $isBuild = 1;
                    // rule for auto-selection in Add mode
                    if (isset($ar[$gw_this['vars']['tid']]['p'])) {
                        $selected = ($ar[$gw_this['vars']['tid']]['id'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
                    }
                }
                if ($isBuild) {
                    $int_title_len = mb_strlen($ar[$startId]['title']);
                    if ($int_title_len > 45) {
                        $ar[$startId]['title'] = mb_substr($ar[$startId]['title'], 0, 45) . '&#8230;';
                    }
                    $str .= '<option ' . $selected . 'style="background:' . $bgcolor . '" value="' . $ar[$startId]['id'] . '">';
                    $str .= '&#160;' . $image . '&#160;' . $ar[$startId]['title'];
                    $str .= '</option>';
                }
            } // form
            elseif ($topic_mode == 'html') {
                $int_title_len = mb_strlen($ar[$startId]['title']);

                if ($gw_this['vars'][GW_TARGET] == 'topics') {
                    /* Check permission to edit the topic */
                    $is_allow_edit = ($oSess->is('is-topics') ? 1 : ($oSess->is('is-topics-own') && ($ar[$startId]['id_user'] == $oSess->id_user)) ? 1 : 0);
                } else {
                    if ($gw_this['vars'][GW_TARGET] == 'custom-pages') {
                        /* Check permission to edit the page */
                        $is_allow_edit = ($oSess->is('is-cpages') ? 1 : ($oSess->is('is-cpages-own') && ($ar[$startId]['id_user'] == $oSess->id_user)) ? 1 : 0);
                    }
                }

                if ($int_title_len > 45) {
                    $ar[$startId]['title'] = mb_substr($ar[$startId]['title'], 0, 45) . '&#8230;';
                }
                $str .= '<tr style="background:' . $bgcolor . '">';
                $str .= '<td class="xt n" style="text-align:' . $sys['css_align_right'] . '">' . $cntRow . '</td>';
                $str .= '<td>';
                $str .= '<table cellspacing="0" cellpadding="0" border="0"><tbody><tr class="xu">';
                $str .= '<td class="nobr">' . $arImgTread['space'] . $image . '</td>';
                $str .= '<td>&#160;';

                $oHtml->setTag('a', 'title', $ar[$startId]['title']);
                $str .= ($is_allow_edit ? $oHtml->a(
                    $page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&tid=' . $ar[$startId]['id'],
                    $ar[$startId]['title'], $oL->m('3_edit'))
                    : $ar[$startId]['title']);
                $oHtml->setTag('a', 'title', '');

                $str .= '</td>';
                $str .= '</tr>';
                $str .= '</tbody></table>';
                $str .= '</td>';

                if (isset($ar[$startId]['int_items'])) {
                    $str .= '<td class="n actions-third" style="text-align:right">';
                    if ($ar[$startId]['int_items'] > 0) {
                        $str .= $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . GW_T_DICTS . '&w1=' . $ar[$startId]['id'],
                            $oFunc->number_format($ar[$startId]['int_items'], 0, $oL->languagelist(LOCALE_LANG_RULES))
                        );
                    } else {
                        $str .= '<del>0</del>';
                    }
                    $str .= '</td>';
                }

                $str .= '<td class="actions-third" style="text-align:center">';
                $str .= ($isUp && $is_allow_edit) ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&mode=up&tid=' . $ar[$startId]['id'], $oL->m('3_up')) : '<del>' . $oL->m('3_up') . '</del>';
                $str .= ' ';
                $str .= ($isDn && $is_allow_edit) ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&mode=dn&tid=' . $ar[$startId]['id'], $oL->m('3_down')) : '<del>' . $oL->m('3_down') . '</del>';
                $str .= ' ';
                $str .= ($isReset && $is_allow_edit) ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&mode=reset&tid=' . $ar[$startId]['id'], $oL->m('3_reset')) : '<del>' . $oL->m('3_reset') . '</del>';
                $str .= '</td>';
                $str .= '<td class="actions-third" style="text-align:center">';
                $str .= ($is_allow_edit ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_ADD . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&tid=' . $ar[$startId]['id'], $oL->m('3_add')) : '<del>' . $oL->m('3_add') . '</del>');
                $str .= ' ';
                $str .= ($is_allow_edit ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&tid=' . $ar[$startId]['id'], $oL->m('3_edit')) : '<del>' . $oL->m('3_edit') . '</del>');
                $str .= ' ';

                $oHtml->setTag('a', 'onclick', 'return confirm(\'' . $oL->m('3_remove') . ': &quot;' . htmlspecialchars($ar[$startId]['title']) . '&quot;. ' . $oL->m('9_remove') . '\' )');
                $str .= ($is_allow_edit ? $oHtml->a($page_index . '?' . GW_ACTION . '=' . GW_A_REMOVE . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&isConfirm=1&tid=' . $ar[$startId]['id'], $oL->m('3_remove')) : '<del>' . $oL->m('3_remove') . '</del>');
                $oHtml->setTag('a', 'onclick', '');

                $str .= '</td>';
                /* 1.8.7: Turn on/off */
                $href_onoff = $page_index . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $gw_this['vars'][GW_TARGET] . '&tid=' . $ar[$startId]['id'];
                $str .= '<td class="actions-third" style="text-align:center">';
                $str .= ($is_allow_edit
                    ? ($ar[$startId]['is_active']
                        ? $oHtml->a($href_onoff . '&mode=off', '<span class="green">' . $oL->m('is_1') . '</span>')
                        : $oHtml->a($href_onoff . '&mode=on', '<span class="red">' . $oL->m('is_0') . '</span>', $oL->m('1057'))
                    )
                    : ($ar[$startId]['is_active']
                        ? '<del><span class="green">' . $oL->m('is_1') . '</span></del>'
                        : '<del><span class="red">' . $oL->m('is_0') . '</span></del>'
                    ));
                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        if (isset($ar[$startId]['ch']) && is_array($ar[$startId]['ch'])) {
            $cnt = sizeof($ar[$startId]['ch']);
            for ($i = 1; $i <= $cnt; $i++) {
                $k = key($ar[$startId]['ch']);
                $cntRow++;
                #$strT .= gw_get_thread_pages($ar, $k);
                next($ar[$startId]['ch']);
            }
        }
    } // count > 1
    return $str . $strT;
}


/* */
function gw_breadcrumbs_pages_ar($ar, $tid = 0, $ar_bc = [])
{
	$id_parent = $ar[$tid]['p'];
	/* There is some Parent ID... */
	if ($id_parent)
	{
		$ar_bc[] = $ar[$tid]['page_title'];
		$ar_bc = gw_breadcrumbs_pages_ar($ar, $id_parent, $ar_bc);
	}
	else
	{
		$ar_bc[] = $ar[$tid]['page_title'];
	}
	return $ar_bc;
}
/* */
function gw_breadcrumbs_is_in_root($ar, $tid = 0, $id_root)
{
	$id_parent = isset($ar[$tid]['p']) ? $ar[$tid]['p'] : 0;
	if ($id_parent == $id_root)
	{
		return true;
	}
	/* There is some Parent ID... */
	if ($id_parent)
	{
		return gw_breadcrumbs_is_in_root($ar, $id_parent, $id_root);
	}
	return false;
}


/**
 * Extract real page/topic id from a legacy ordered id value.
 *
 * Legacy value example: 0001000006 = sort 10 + id 6.
 *
 * @param mixed $value
 *
 * @return int
 */
function gw_thread_page_get_real_id($value)
{
    $value = (string) $value;

    if ($value !== '' && ctype_digit($value) && strlen($value) >= 8) {
        return (int) substr($value, 5);
    }

    return (int) $value;
}

/**
 * Extract sort position from a legacy ordered id value.
 *
 * @param mixed $value
 *
 * @return int
 */
function gw_thread_page_get_sort_from_legacy_id($value)
{
    $value = (string) $value;

    if ($value !== '' && ctype_digit($value) && strlen($value) >= 8) {
        return (int) substr($value, 0, 5);
    }

    return 0;
}

/**
 * Return flat page/topic id.
 *
 * @param array $item
 * @param mixed $item_key
 *
 * @return int
 */
function gw_thread_page_get_id(array $item, $item_key)
{
    if (isset($item['id'])) {
        return (int) $item['id'];
    }

    if (isset($item['id_page'])) {
        return gw_thread_page_get_real_id($item['id_page']);
    }

    if (isset($item['id_topic'])) {
        return gw_thread_page_get_real_id($item['id_topic']);
    }

    return gw_thread_page_get_real_id($item_key);
}

/**
 * Return flat page/topic sort value.
 *
 * @param array $item
 * @param mixed $item_key
 *
 * @return int
 */
function gw_thread_page_get_sort(array $item, $item_key)
{
    if (isset($item['int_sort'])) {
        return (int) $item['int_sort'];
    }

    if (isset($item['id_page'])) {
        return gw_thread_page_get_sort_from_legacy_id($item['id_page']);
    }

    if (isset($item['id_topic'])) {
        return gw_thread_page_get_sort_from_legacy_id($item['id_topic']);
    }

    return gw_thread_page_get_sort_from_legacy_id($item_key);
}

/**
 * Build flat list of topics/pages for admin table or select field.
 *
 * The function does not depend on parent-child tree fields.
 *
 * @param array $items
 * @param int $start_id
 * @param int $cnt_row
 *
 * @return string
 */
function gw_get_thread_pages($items = [], $start_id = 0, $cnt_row = 1)
{
    global $arImgTread, $sys, $ar_theme, $gw_this;
    global $oSess, $oHtml, $oL, $oFunc;
    global $topic_mode;

    if (isset($sys['topic_mode'])) {
        $topic_mode = $sys['topic_mode'];
    }

    if (count($items) === 0) {
        return '';
    }

    $target = $gw_this['vars'][GW_TARGET];
    $current_action = $gw_this['vars'][GW_ACTION];
    $current_id = (int) $gw_this['vars'][GW_TARGET_ID];

    $page_index = $sys['page_index'];

    if (defined('GW_IS_BROWSE_ADMIN') && GW_IS_BROWSE_ADMIN) {
        $page_index = $sys['page_admin'];
    }

    $build_url = function ($action, array $params = []) use ($page_index, $target) {
        $query_params = [
                GW_ACTION => $action,
                GW_TARGET => $target,
            ] + $params;

        return $page_index . '?' . http_build_query($query_params, '', '&');
    };

    $rows = [];

    foreach ($items as $item_key => $item) {
        /*
         * Skip service tree nodes such as root node with only `ch`, `min`, `max`.
         * Real rows have `id`, `id_topic` or `id_page`.
         */
        if (
            !is_array($item)
            || (
                !isset($item['id'])
                && !isset($item['id_topic'])
                && !isset($item['id_page'])
            )
        ) {
            continue;
        }

        $item_id = gw_thread_page_get_id($item, $item_key);

        if ($item_id < 1) {
            continue;
        }

        $item['id'] = $item_id;
        $item['_gw_id'] = $item_id;
        $item['_gw_sort'] = gw_thread_page_get_sort($item, $item_key);

        $rows[] = $item;
    }

    usort($rows, function ($item_a, $item_b) {
        if ($item_a['_gw_sort'] == $item_b['_gw_sort']) {
            if ($item_a['_gw_id'] == $item_b['_gw_id']) {
                return 0;
            }

            return ($item_a['_gw_id'] < $item_b['_gw_id']) ? -1 : 1;
        }

        return ($item_a['_gw_sort'] < $item_b['_gw_sort']) ? -1 : 1;
    });

    $total_rows = count($rows);
    $str = '';

    foreach ($rows as $row_index => $item) {
        if ($start_id != 0 && $item['_gw_id'] != (int) $start_id) {
            continue;
        }

        $row_id = (int) $item['_gw_id'];
        $is_up = ($row_index > 0) ? 1 : 0;
        $is_dn = ($row_index < ($total_rows - 1)) ? 1 : 0;

        $bgcolor = ($cnt_row % 2) ? $ar_theme['color_1'] : $ar_theme['color_2'];

        $title = (string) $item['title'];
        $title_short = $title;

        if (mb_strlen($title_short) > 45) {
            $title_short = mb_substr($title_short, 0, 45) . '…';
        }

        $title_html = htmlspecialchars($title_short, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($topic_mode == 'form') {
            $is_build = 1;
            $selected = '';

            // Do not allow selecting the same item as its own parent in edit mode.
            if ($current_action != GW_A_ADD && $target != GW_T_DICTS && $row_id == $current_id) {
                $is_build = 0;
            }

            // In add mode the current item is the selected parent.
            if ($current_action == GW_A_ADD || $target == GW_T_DICTS) {
                $selected = ($row_id == $current_id) ? 'selected="selected" ' : '';
            }

            if ($is_build) {
                $str .= '<option ' . $selected . 'style="background:' . $bgcolor . '" value="' . $row_id . '">';
                $str .= '&#160;' . $title_html;
                $str .= '</option>';
            }
        } elseif ($topic_mode == 'html') {
            $is_allow_edit = 0;

            if ($target == GW_T_TOPICS) {
                // Check permission to edit the topic.
                $is_allow_edit = ($oSess->is('is-topics') || ($oSess->is('is-topics-own') && $item['id_user'] == $oSess->id_user)) ? 1 : 0;
            } elseif ($target == GW_T_CUSTOMPAGE) {
                // Check permission to edit the page.
                $is_allow_edit = ($oSess->is('is-cpages') || ($oSess->is('is-cpages-own') && $item['id_user'] == $oSess->id_user)) ? 1 : 0;
            }

            $str .= '<tr style="background:' . $bgcolor . '">';
            $str .= '<td class="xt n" style="text-align:' . $sys['css_align_right'] . '">' . $cnt_row . '</td>';
            $str .= '<td>';
            $str .= '<table cellspacing="0" cellpadding="0" border="0"><tbody><tr class="xu">';
            $str .= '<td class="nobr">' . $arImgTread['space'] . '</td>';
            $str .= '<td>&#160;';

            $edit_url = $build_url(GW_A_EDIT, [
                GW_TARGET_ID => $row_id,
            ]);

            $oHtml->setTag('a', 'title', $title_html);

            $str .= $is_allow_edit
                ? $oHtml->a($edit_url, $title_html, $oL->m('3_edit'))
                : $title_html;

            $oHtml->setTag('a', 'title', '');

            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</tbody></table>';
            $str .= '</td>';

            if (isset($item['int_items'])) {
                $str .= '<td class="n actions-third" style="text-align:right">';

                if ($item['int_items'] > 0) {
                    $str .= $oHtml->a(
                        $build_url(GW_A_BROWSE, [
                            GW_TARGET => GW_T_DICTS,
                            'w1' => $row_id,
                        ]),
                        $oFunc->number_format($item['int_items'], 0, $oL->languagelist(LOCALE_LANG_RULES))
                    );
                } else {
                    $str .= '<del>0</del>';
                }

                $str .= '</td>';
            }

            $str .= '<td class="actions-third" style="text-align:center">';
            $str .= ($is_up && $is_allow_edit)
                ? $oHtml->a($build_url(GW_A_EDIT, ['mode' => 'up', GW_TARGET_ID => $row_id]), $oL->m('3_up'))
                : '<del>' . $oL->m('3_up') . '</del>';

            $str .= ' ';

            $str .= ($is_dn && $is_allow_edit)
                ? $oHtml->a($build_url(GW_A_EDIT, ['mode' => 'dn', GW_TARGET_ID => $row_id]), $oL->m('3_down'))
                : '<del>' . $oL->m('3_down') . '</del>';

            $str .= '</td>';

            // Actions
            $str .= '<td class="actions-third" style="text-align:center">';

            $str .= $is_allow_edit
                ? $oHtml->a($edit_url, $oL->m('3_edit'))
                : '<del>' . $oL->m('3_edit') . '</del>';

            $str .= ' ';

            $oHtml->setTag('a', 'class', 'submitdel');
            $oHtml->setTag('a', 'onclick', 'return confirm(\'' . $oL->m('3_remove') . ': &quot;' . htmlspecialchars($title) . '&quot;. ' . $oL->m('9_remove') . '\')');

            $str .= $is_allow_edit
                ? $oHtml->a(
                    $build_url(GW_A_REMOVE, [
                        'isConfirm' => 1,
                        GW_TARGET_ID => $row_id,
                    ]),
                    $oL->m('3_remove')
                )
                : '<del>' . $oL->m('3_remove') . '</del>';

            $oHtml->setTag('a', 'onclick', '');
            $oHtml->setTag('a', 'class', '');

            $str .= '</td>';

            $href_onoff = $build_url(GW_A_EDIT, [
                GW_TARGET_ID => $row_id,
            ]);

            $str .= '<td class="actions-third" style="text-align:center">';

            $str .= $is_allow_edit
                ? (
                $item['is_active']
                    ? $oHtml->a($href_onoff . '&mode=off', '<span class="green">' . $oL->m('is_1') . '</span>')
                    : $oHtml->a($href_onoff . '&mode=on', '<span class="red">' . $oL->m('is_0') . '</span>', $oL->m('1057'))
                )
                : (
                $item['is_active']
                    ? '<del><span class="green">' . $oL->m('is_1') . '</span></del>'
                    : '<del><span class="red">' . $oL->m('is_0') . '</span></del>'
                );

            $str .= '</td>';
            $str .= '</tr>';
        }

        $cnt_row++;
    }

    return $str;
}