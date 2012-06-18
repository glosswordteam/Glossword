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
	die('<!-- $Id: func.catalog.inc.php 496 2008-06-14 06:42:53Z glossword_team $ -->');
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
	for (; list($letter, $azv) = each($ar0z);)
	{
		$str .= '<h5>'. $oHtml->a($sys['page_index'].
					'?'.GW_ACTION.'='.GW_A_LIST.
					'&'.GW_ID_DICT.'='.$id_dict.
					'&w1='.$letter, $letter).'</h5>';
		/* */
		$sql = $oSqlQ->getQ('get-az-terms', $dict_tablename, $letter, $sys['time_now_db'], $arDictParam['az_sql'], $sys['max_terms_in_index']);
		$arSql = $oDb->sqlExec($sql);
		$ar_terms = array();
		for (; list($arK, $arV) = each($arSql);)
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
	$arA = array();
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

		for (; list($arK, $arV) = each($arSql);)
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
			while (!$sys['is_debug_output']
					&& is_array($sys['filters_defn'])
					&& list($k, $v) = each($sys['filters_defn']) )
			{
				$tmp['str_defn'] = $v($tmp['str_defn']);
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
	$tmp['href_term'] = array();
	if (empty($arA[0]))
	{
		return;
	}
	for (reset($arA); list($k1, $v1) = each($arA);)
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
	$oTpl->addVal( 'v:terms_on_page', $oFunc->number_format($strA[1], 0, $oL->languagelist('4')) );
	$oTpl->addVal( 'l:terms_on_page', $oL->m('str_on_page') );
	$oTpl->addVal( 'v:page_of_page',  sprintf($oL->m('str_page_of_page'), $oFunc->number_format($p, 0, $oL->languagelist('4')), $oFunc->number_format($strA[3], 0, $oL->languagelist('4'))) );

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
		$oTpl->tmp['d']['list_item'] = array();
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
	$arSql = array();
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
	$arSqlNew = array();
	for (reset($arSql); list($k, $v) = each($arSql);)
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
	$arDictMap = array();
	if (sizeof($arSql) > 0)
	{
		if (GW_IS_BROWSE_WEB)
		{
			#$arDictMap[0] = '-'.$oL->m('1115').'-';
		}
		for (reset($arSql); list($arK, $arV) = each($arSql);)
		{
			$arDictMap[$arV['id']] = strip_tags($arV['title']);
		}
		/* Sort alphabetically */
		asort($arDictMap);
		if ($is_form_only && GW_IS_BROWSE_WEB)
		{
			$arDictMap = array_merge_clobber(array(0 => $oL->m('srch_all')), $arDictMap);
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
		return htmlFormsSelect($arDictMap, $id_dict, 'd', 'input',  'width:100%', 'ltr');
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
	for (reset($arSql); list($arK, $arV) = each($arSql);)
	{
		switch ($sys['pages_link_mode'])
		{
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
	if (empty($ar)){ return; }
	// display catalog in web mode
	if (GW_IS_BROWSE_WEB)
	{
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
		while (is_array($ar[$p]['ch']) && list($k, $v) = each($ar[$p]['ch'])) // (Root or Topic) -> Topic
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
					while (is_array($arDictMap[$k]) && list($k2, $v2) = each($arDictMap[$k]))
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
								$str .= '&#32;(' . $oFunc->number_format($v2['int_terms'], 0, $oL->languagelist('4')) . ')';
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
function gw_create_tree_custom_pages($id = 0)
{
	global $oSqlQ, $oDb;
	/* Disable caching for admin mode */
	if (GW_IS_BROWSE_ADMIN)
	{
		$arSqlc = $oDb->sqlExec($oSqlQ->getQ('get-custompages-list'));
	}
	elseif (GW_IS_BROWSE_WEB)
	{
		$arSqlc = $oDb->sqlRun($oSqlQ->getQ('get-custompages-list', 'AND cp.is_active = "1"'), 'st');
	}
	/* Create the list of topics */
	$arSqlc = gw_rearrange_to_locale($arSqlc, 'id_page');
	return gw_rearrange_to_tree($arSqlc, $id, 'id_page');
}


/* */
function gw_rearrange_to_tree($arSql, $id = 0, $id_name = 'id_page')
{
	$arStr = array(array());
	$arStr2 = array();
	for (reset($arSql); list($arK, $arV) = each($arSql);)
	{
		list($int_sort, $id) = sscanf($arV[$id_name], "%05d%03d");
		$arV['id'] = $arV[$id_name] = $id;
		$i = $arV['id'];
		$p = $arV['p'];
		$arStr2[$i] = $arV;
		$arStr[$p]['ch'][$i] = $i;
		$arStr[$p]['max'] = $i;
		if (!isset($arStr[$p]['max'])) $arStr[$p]['max'] = $i;
		if (!isset($arStr[$p]['min'])) $arStr[$p]['min'] = $i;
	}
	/* Merge */
	while (is_array($arStr2) && list($key, $val) = each($arStr2) )
	{
		if (isset($arStr[$key]))
		{
			while (is_array($val) && list($k2, $v2) = each($val) )
			{
				$arStr[$key][$k2] = $v2;
			}
		}
		else
		{
			$arStr[$key] = $arStr2[$key];
		}
	}
	return $arStr;
}
/* */
function gw_rearrange_to_locale($arSql, $id_name = 'id_page')
{
	global $oSess, $gw_this, $sys;
	$arSql2 = array();
	$arSql3 = array();
	/* re-arrange */
	for (; list($k, $arV) = each($arSql);)
	{
		$arV[$id_name] = sprintf("%05d", $arV['int_sort']).sprintf("%03d", $arV[$id_name]);
		$arSql2[$arV[$id_name]][$arV['id_lang']] = $arV;
	}
	$arSql = array();
	$cnt = 0;
	$int_size = sizeof($arSql2);
	for (; list($k, $arV) = each($arSql2);)
	{
		$cur_id_lang = $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'];
		if (isset($arV[$cur_id_lang]))
		{
			$arV = $arV[$cur_id_lang];
		}
		elseif (isset($arV[$gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']]))
		{
			$arV = $arV[$gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']];
		}
		else
		{
			$ar_keys = array_keys($arV);
			$arV = $arV[$ar_keys[0]];
		}
		$arSql3[$arV[$id_name]] = $arV;
	}
	$arSql2 = array();
	return $arSql3;
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
function ctlgGetTopicsRow($ar = array(), $startId = 0, $cntRow = 1)
{
	global $arImgTread, $arTxtTread, $cntRow, $tid, $arParents, $a, $t, $sys, $ar_theme, $gw_this;
	global $oSess, $oHtml, $oL, $oFunc, $topic_mode;

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
		$arParents = ctlgGetTree($ar, $tid);
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
					$topic_len = $oFunc->mb_strlen($ar[$startId]['title']);
					if ($topic_len > 45)
					{
						$ar[$startId]['title'] = $oFunc->mb_substr($ar[$startId]['title'], 0, 45).'&#8230;';
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
 * Get branch from any tree. Recursive.
 * 
 * @users   global $arId;
 * @param   array with a tree structure
 * @param   chunk id
 * @return  array
 */
function ctlgGetTree($ar, $id)
{
	global $arId;
	if (isset($ar[$id]['ch']))
	{
		while(is_array($ar[$id]['ch']) && list($k, $v) = each($ar[$id]['ch']) )
		{
			if (isset($ar[$k]['ch']))
			{
				ctlgGetTree($ar, $k);
			}
			$arId[$k] = $k;
		}
	}
	$arId[$id] = $id;
	return $arId;
}


/* */
function gw_get_thread_pages($ar = array(), $startId = 0, $cntRow = 1)
{
	global $arImgTread, $arTxtTread, $cntRow, $arParents, $sys, $ar_theme, $gw_this;
	global $oSess, $oHtml, $oL, $oFunc;
	global $topic_mode;
	
	/* Using $sys variable instead of global variable */
	if (isset($sys['topic_mode']))
	{
		$topic_mode = $sys['topic_mode'];
	}

	$str = $strT = $image = '';
	$isDn = $isUp = 1;
	$isReset = 0;
	$page_index = $sys['page_index'];
	if (GW_IS_BROWSE_ADMIN)
	{
		$page_index = $sys['page_admin'];
	}
	/* Parents with selected tid (same for delete) */
	if ($cntRow == 1)
	{
		$arParents = ctlgGetTree($ar, $gw_this['vars']['tid']);
	}
	if (sizeof($ar) > 0)
	{
		if ($startId != 0)
		{
			$ar[$startId]['title'] = isset($ar[$startId]['title']) ? $ar[$startId]['title'] : 0;
			$ar[$startId]['id'] = isset($ar[$startId]['id']) ? $ar[$startId]['id'] : 0;
			$p = isset($ar[$startId]['p']) ? $ar[$startId]['p'] : 0;
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
				if ($p != 0)
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['c'] : $arTxtTread['c'];
				}
				else
				{
					$image .= ($topic_mode == 'html') ? $arImgTread['space'] : $arTxtTread['space'];
				}
			}
			if ( isset($ar[$p]['max']) && ($ar[$p]['max'] == $startId)){ $isDn = 0; }
			if ( isset($ar[$p]['min']) && ($ar[$p]['min'] == $startId)){ $isUp = 0; $isReset = 1; }
			if (!$isUp && !$isDn) { $isReset = 0; }
			$cntRow % 2 ? ($bgcolor = $ar_theme['color_1']) : ($bgcolor = $ar_theme['color_2']);
			if ($topic_mode == 'form')
			{
				$selected = '';
				if (isset($ar[$gw_this['vars']['tid']]['p']))
				{
					$selected = ($ar[$gw_this['vars']['tid']]['p'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
				}
				$isBuild = 0;
				// do not allow to place topic under the same topic and parent under the same parent
				// Edit mode
				if (!isset($arParents[$startId]) && ($ar[$startId]['id'] != $gw_this['vars']['tid']))
				{
					$isBuild = 1;
				}
				// exclusion for Add mode
				if ($gw_this['vars'][GW_ACTION] == GW_A_ADD || ($gw_this['vars'][GW_TARGET] == GW_T_DICTS))
				{
					$isBuild = 1;
					// rule for auto-selection in Add mode
					if (isset($ar[$gw_this['vars']['tid']]['p']))
					{
						$selected = ($ar[$gw_this['vars']['tid']]['id'] == $ar[$startId]['id']) ? 'selected="selected" ' : '';
					}
				}
				if ($isBuild)
				{
					$int_title_len = $oFunc->mb_strlen($ar[$startId]['title']);
					if ($int_title_len > 45)
					{
						$ar[$startId]['title'] = $oFunc->mb_substr($ar[$startId]['title'], 0, 45). '&#8230;';
					}
					$str .= '<option ' . $selected . 'style="background:'.$bgcolor.'" value="'.$ar[$startId]['id'].'">';
					$str .= '&#160;' . $image . '&#160;' . $ar[$startId]['title'];
					$str .= '</option>';
				}
			} // form
			elseif ($topic_mode == 'html')
			{
				$int_title_len = $oFunc->mb_strlen($ar[$startId]['title']);
				
				if ($gw_this['vars'][GW_TARGET] == 'topics')
				{
					/* Check permission to edit the topic */
					$is_allow_edit = ($oSess->is('is-topics') ? 1 : ($oSess->is('is-topics-own') && ($ar[$startId]['id_user'] == $oSess->id_user)) ? 1 : 0);
				}
				else if ($gw_this['vars'][GW_TARGET] == 'custom-pages')
				{
					/* Check permission to edit the page */
					$is_allow_edit = ($oSess->is('is-cpages') ? 1 : ($oSess->is('is-cpages-own') && ($ar[$startId]['id_user'] == $oSess->id_user)) ? 1 : 0);
				}
				
				if ($int_title_len > 45)
				{
					$ar[$startId]['title'] = $oFunc->mb_substr($ar[$startId]['title'], 0, 45). '&#8230;';
				}
				$str .= '<tr style="background:'.$bgcolor.'">';
				$str .= '<td class="xt n" style="text-align:'.$sys['css_align_right'].'">' .  $cntRow . '</td>';
				$str .= '<td>';
				$str .= '<table cellspacing="0" cellpadding="0" border="0"><tbody><tr class="xu">';
				$str .= '<td class="nobr">' . $arImgTread['space'] . $image . '</td>';
				$str .= '<td>&#160;';

				$oHtml->setTag('a', 'title', $ar[$startId]['title']);
				$str .= ($is_allow_edit ? $oHtml->a(
							$page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid=' . $ar[$startId]['id'],
							$ar[$startId]['title'], $oL->m('3_edit') )
						: $ar[$startId]['title']);
				$oHtml->setTag('a', 'title', '');
				
				$str .= '</td>';
				$str .= '</tr>';
				$str .= '</tbody></table>';
				$str .= '</td>';

				if (isset($ar[$startId]['int_items']))
				{
					$str .= '<td class="n actions-third" style="text-align:right">';
					if ($ar[$startId]['int_items'] > 0)
					{
						$str .= $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.GW_T_DICTS.'&w1='.$ar[$startId]['id'], 
								$oFunc->number_format($ar[$startId]['int_items'], 0, $oL->languagelist('4')) 
								);
					}
					else
					{
						$str .= '<del>0</del>';
					}
					$str .= '</td>';
				}
				
				$str .= '<td class="actions-third" style="text-align:center">';
				$str .= ($isUp && $is_allow_edit) ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&mode=up&tid=' . $ar[$startId]['id'], $oL->m('3_up')) : '<del>'.$oL->m('3_up').'</del>';
				$str .= ' ';
				$str .= ($isDn && $is_allow_edit) ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&mode=dn&tid=' . $ar[$startId]['id'], $oL->m('3_down')) : '<del>'.$oL->m('3_down').'</del>';
				$str .= ' ';
				$str .= ($isReset && $is_allow_edit) ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&mode=reset&tid=' . $ar[$startId]['id'], $oL->m('3_reset')) : '<del>'.$oL->m('3_reset').'</del>';
				$str .= '</td>';
				$str .= '<td class="actions-third" style="text-align:center">';
				$str .= ($is_allow_edit ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_ADD.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid='.$ar[$startId]['id'], $oL->m('3_add') ) : '<del>'.$oL->m('3_add').'</del>' );
				$str .= ' ';
				$str .= ($is_allow_edit ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid='.$ar[$startId]['id'], $oL->m('3_edit') ) : '<del>'.$oL->m('3_edit').'</del>' );
				$str .= ' ';

				$oHtml->setTag('a', 'onclick', 'return confirm(\''.$oL->m('3_remove').': &quot;'.htmlspecialchars($ar[$startId]['title']).'&quot;. '.$oL->m('9_remove').'\' )');
				$str .= ($is_allow_edit ? $oHtml->a( $page_index . '?'.GW_ACTION.'='.GW_A_REMOVE.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&isConfirm=1&tid='.$ar[$startId]['id'], $oL->m('3_remove') ) :'<del>'.$oL->m('3_remove').'</del>' );
				$oHtml->setTag('a', 'onclick', '');
				
				$str .= '</td>';
				/* 1.8.7: Turn on/off */
				$href_onoff = $page_index . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid=' . $ar[$startId]['id'];
				$str .= '<td class="actions-third" style="text-align:center">';
				$str .= ($is_allow_edit ? ($ar[$startId]['is_active'] 
							? $oHtml->a($href_onoff.'&mode=off', '<span class="green">'.$oL->m('is_1').'</span>')
							: $oHtml->a($href_onoff.'&mode=on', '<span class="red">'.$oL->m('is_0').'</span>', $oL->m('1057') )
						) : ($ar[$startId]['is_active'] 
							? '<del><span class="green">'.$oL->m('is_1').'</span></del>'
							: '<del><span class="red">'.$oL->m('is_0').'</span></del>'
						));
				$str .= '</td>';
				$str .= '</tr>';
			}
		}
		if (isset($ar[$startId]['ch']) && is_array($ar[$startId]['ch']))
		{
			$cnt = sizeof($ar[$startId]['ch']);
			for ($i = 1; $i <= $cnt; $i++)
			{
				$k = key($ar[$startId]['ch']);
				$cntRow++;
				$strT .= gw_get_thread_pages($ar, $k);
				next($ar[$startId]['ch']);
			}
		}
	} // count > 1
	return $str . $strT;
}


/* */
function gw_breadcrumbs_pages_ar($ar, $tid = 0, $ar_bc = array())
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



/* end of file */
?>