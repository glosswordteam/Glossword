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
	die('<!-- $Id: func.browse.inc.php 551 2008-08-17 17:34:05Z glossword_team $ -->');
}
/**
 *  Math, SQL, HTML functions for browsing dictionary.
 */
// --------------------------------------------------------

function gw_get_themes_select()
{
	global $gw_this;
	$ar = array();
	for (; list($k, $v) = each($gw_this['ar_themes']);)
	{
		$ar[$k] = $v['theme_name'];
		if (GW_IS_BROWSE_ADMIN)
		{
			$ar[$k] .= ' '.$v['theme_version']. ' ('. $v['theme_author'].')';
		}
	}
	return $ar;
}
/**
 * Get the list of active themes.
 *
 * @return  array   The list of themes with id_theme as array keys.
 */
function gw_get_themes_list()
{
	global $sys, $oDb, $oSqlQ;

	if (GW_IS_BROWSE_ADMIN)
	{
		$arSql = $oDb->sqlExec($oSqlQ->getQ('get-themes-adm'));
	}
	else
	{
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-themes'), 'theme');
	}
	/* re-format */
	$arVars = array();
	for (; list($kV, $arV) = each($arSql);)
	{
		$arV['theme_version'] = $arV['v1'].'.'.$arV['v2'].'.'.$arV['v3'];
		unset($arV['v1'], $arV['v2'], $arV['v3']);
		$arVars[$arV['id_theme']] = $arV;
		unset($arSql[$kV]);
	}
	return $arVars;
}
/**
 * Get a group of settings from a visual theme.
 *
 * @param   string  $themename        The name of visual theme
 * @return  array   All settings for the theme
 */
function gw_get_theme($theme_name)
{
	global $sys, $oDb, $oSqlQ;
	$ar_theme = array();
	/* 1,2 - theme settings only (colors, theme credits) */
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme', gw_text_sql($theme_name), '1,2'), 'theme');
	if (empty($arSql))
	{
		/* custom theme is not found, load default theme */
		$theme_name = $sys['path_theme'] = $sys['visualtheme'];
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme', gw_text_sql($theme_name), '1,2'), 'theme');
		if (empty($arSql))
		{
			die('Unable to load visual theme `' . $theme_name.'` from table `'.$sys['tbl_prefix'].'themes`. Check database settings or re-install the software.');
		}
	}
	else
	{
		$sys['path_theme'] = $theme_name;
	}
	for (; list($kV, $arV) = each($arSql);)
	{
		$ar_theme[$arV['settings_key']] = $arV['settings_value'];
		unset($arSql[$kV]);
	}
	return $ar_theme;
}
/**
 * Construct HTML-code for the list of terms.
 *
 * @param   string  $str        XML-code, (from database)
 * @return  array   Fields content structure
 */
function gw_sql2defnpreview($arSql)
{
	global $oFunc, $oHtml, $oL, $ar_theme;
	global $sys, $arFields, $arDictParam, $gw_this;

	$oDom = new gw_domxml;
	$arPreview = array();
	$incr_term = 0;
	$int_fields_total = sizeof($arFields);
	$str_split_defn =& $ar_theme['split_defn'];
	/* default settings for all dictionaries */
	if (!isset($arDictParam['is_show_tooltip_defn']))
	{
		$arDictParam['is_show_tooltip_defn'] = 0;
	}
# @header("Content-Type: text/html; charset=utf-8");
	$arDuplicates = array(array());
	for (; list($k, $arV) = each($arSql);)
	{
		$incr_term = $k;
		$arPre = array();
		/* Init. */
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			$arPreview[$incr_term][0][$fK] = '';
			$arDictParam['is_'.$fV[0]] = 1;
		}
		/* Term */
		$arPreview[$incr_term][1] = array(
			'term' => $arV['term'],
			'term_uri' => $arV['term_uri'],
			'term_1' => $gw_this['vars']['w1'],
			'term_2' => $gw_this['vars']['w2'],
			'term_3' => $gw_this['vars']['w3'],
			'id_term' => $arV['id_term'],
			'id_user' => $arV['id_user'],
			'id_dict' => $gw_this['vars'][GW_ID_DICT],
			'int_bytes' => $arV['int_bytes'],
			'is_active' => $arV['is_active'],
			'is_complete' => $arV['is_complete']
		);
		/* */
		$arPre = array_merge_clobber($arPre, gw_Xml2Array('<term>'.$arV['term'].'</term>'.$arV['defn']));
#prn_r( $arPre );
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			if (!isset($arPre[$fV[0]])){ continue; }
			$tmpStr = $oDom->get_content( $arPre[$fV[0]] );
			if (trim($tmpStr) == '' || $tmpStr == '<![CDATA[]]>' ){ continue; }
			if (!$arDictParam['is_'.$fV[0]]){ continue; }
			switch ($fV[0])
			{
				case 'trsp':
					for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
					{
						$ar_vvfV = array();
						for (reset($vfV); list($kkfV, $vvfV) = each($vfV);)
						{
							$ar_vvfV[] = $vvfV['value'];
						}
						if ($kfV == 0)
						{
							$arPreview[$incr_term][$kfV][$fK] .= '['.gw_text_parse_preview( implode('; ', $ar_vvfV) ).']';
						}
						else
						{
							$arDuplicates[$incr_term][$kfV][$fK][] = '['.gw_text_parse_preview( implode('; ', $ar_vvfV) ).']';
						}
						if (isset($arDuplicates[$incr_term][$kfV][$fK]))
						{
							$arDuplicates[$incr_term][$kfV][$fK] = implode('', $arDuplicates[$incr_term][$kfV][$fK]);
						}
					}
				break;
				case 'defn':
					$tmpf = array();
					for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
					{
						if ($kfV == 0)
						{
							$arPreview[$incr_term][$kfV][$fK] .= gw_text_parse_preview( $vfV['value'] );
						}
						else
						{
							$arDuplicates[$incr_term][$kfV][$fK] = gw_text_parse_preview( $vfV['value'] );
						}
					}
				break;
				case 'abbr':
				case 'trns':
					for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
					{
						$ar_vvfV = array();
						for (reset($vfV); list($kkfV, $vvfV) = each($vfV);)
						{
							if ($vvfV['value'] == '' || $vvfV['value'] == '<![CDATA[]]>')
							{
								continue;
							}
							$ar_vvfV[] = $vvfV['value'];
						}
						if ($kfV == 0)
						{
							if (empty($ar_vvfV))
							{
								$arPreview[$incr_term][$kfV][$fK] = '';
							}
							else
							{
								$arPreview[$incr_term][$kfV][$fK] = $ar_theme['prepend_abbr_preview'].gw_text_parse_preview( implode($ar_theme['split_abbr_preview'], $ar_vvfV) ). $ar_theme['append_abbr_preview'];
							}
						}
						else
						{
							if (empty($ar_vvfV))
							{
								$arDuplicates[$incr_term][$kfV][$fK] = '';
							}
							else
							{
								$arDuplicates[$incr_term][$kfV][$fK] = $ar_theme['prepend_abbr_preview'].gw_text_parse_preview ( implode($ar_theme['split_abbr_preview'], $ar_vvfV) ). $ar_theme['append_abbr_preview'];
							}
						}
					}
				break;
				case 'see':
				case 'syn':
				case 'antonym':
					for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
					{
						$ar_vvfV = array();
						for (reset($vfV); list($kkfV, $vvfV) = each($vfV);)
						{
							$ar_vvfV[] = $vvfV['value'];
						}
						if ($kfV == 0)
						{
							$arPreview[$incr_term][$kfV][$fK] = $oL->m($fV[0]).': ' . gw_text_parse_preview( implode(', ', $ar_vvfV) );
						}
						else
						{
							$arDuplicates[$incr_term][$kfV][] = gw_text_parse_preview(implode(', ', $ar_vvfV));
						}
					}
				break;
				case 'usg':
				case 'src':
				case 'phone':
				case 'address':
					for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
					{
						$ar_vvfV = array();
						for (reset($vfV); list($kkfV, $vvfV) = each($vfV);)
						{
							$ar_vvfV[] = $vvfV['value'];
						}
						if ($kfV == 0)
						{
							$arPreview[$incr_term][$kfV][$fK] .= ' ='.gw_text_parse_preview( implode('; ', $ar_vvfV) );
						}
						else
						{
							if (!isset($arDuplicates[$incr_term][$kfV][$fK]))
							{
								$arDuplicates[$incr_term][$kfV][$fK] = array();
							}
							if (is_string($arDuplicates[$incr_term][$kfV][$fK]))
							{
								$arDuplicates[$incr_term][$kfV][$fK] = array($arDuplicates[$incr_term][$kfV][$fK]);
							}
							$arDuplicates[$incr_term][$kfV][$fK][] = gw_text_parse_preview( implode('; ', $ar_vvfV) );
						}
						if (isset($arDuplicates[$incr_term][$kfV][$fK]))
						{
							$arDuplicates[$incr_term][$kfV][$fK] = gw_text_parse_preview(implode(' ', $arDuplicates[$incr_term][$kfV][$fK]));
						}
					}
				default:
				break;
			}
		} /* end of $arFields */
		unset($arSql[$k]);
	} /* end of $arSql */
	unset($arPre);
#prn_r( $arPreview );
#prn_r( $arDuplicates );
	$arA = array(array());
	$int_timer = 0;
	for (reset($arPreview); list($k, $arV) = each($arPreview);)
	{
		$str_incomplete = $arV[1]['is_complete'] ? '' : '?&#160;';

		$arA[$k]['term'] = $arV[1]['term'];
		$arA[$k]['term_uri'] = $arV[1]['term_uri'];
		$arA[$k]['t_id'] = $arV[1]['id_term'];
		$arA[$k]['d_id'] = $arV[1]['id_dict'];
		$arA[$k]['id_user'] = $arV[1]['id_user'];
		$arA[$k]['kb'] = $arV[1]['int_bytes'];
		$arA[$k]['is_active'] = $arV[1]['is_active'];
		$arA[$k]['is_complete'] = $arV[1]['is_complete'];
		$arA[$k]['defn_tooltip'] = $arA[$k]['defn'] = '';
		
		/* remove some fields from definition preview */
		if (isset($arV[$k][1])) { unset($arV[$k][1]); }
		/* Join all fields into one string */
		$arA[$k]['defn'] = implode(' ', $arV[0]);
		/* Prepare tooltip, text inside <a title="..."> */
		if (isset($arDictParam['is_show_tooltip_defn']) && $arDictParam['is_show_tooltip_defn'])
		{
			$arA[$k]['defn_tooltip'] = strip_tags($arA[$k]['defn']);
			$arA[$k]['defn_tooltip'] = preg_replace('/&#[x0-9a-f]+;/', ' ', $arA[$k]['defn_tooltip']);
			$arA[$k]['defn_tooltip'] = preg_replace('/&[a-z]+;/', ' ', $arA[$k]['defn_tooltip']);
			$arA[$k]['defn_tooltip'] = $oFunc->mb_substr(trim($arA[$k]['defn_tooltip']), 0, 128, $sys['internal_encoding']);
			$arA[$k]['defn_tooltip'] = htmlspecialchars($arA[$k]['defn_tooltip'], ENT_QUOTES, $sys['internal_encoding']);
		}
		if (!empty($arDuplicates[$k]))
		{
			for (reset($arDuplicates[$k]); list($kD, $arVd) = each($arDuplicates[$k]);)
			{
				/* remove some fields from definition preview */
#				if (isset($arVd[1])) { unset($arVd[1]); }
				$arA[$k]['defn'] .= $str_split_defn . gw_text_parse_preview(implode(' ', $arVd));
			}
		}
		/* Create a tooltip on preview mode */
		if ($arDictParam['is_show_tooltip_defn'] && !$arDictParam['is_show_full'])
		{
			$oHtml->setTag('a', 'title', $arA[$k]['defn_tooltip']);
		}
		if (GW_IS_BROWSE_ADMIN)
		{
			$tmp['href_term'][GW_ACTION] = 'edit';
			$tmp['href_term'][GW_TARGET] = 'term';
			$tmp['href_term']['id'] = $arV[1]['id_dict'];
			$tmp['href_term']['tid'] = $arV[1]['id_term'];
			$href_term = $sys['page_admin'] . '?' . $oHtml->paramValue($tmp['href_term'], '&', '');
		}
		else
		{
			$tmp['href_term'][GW_ACTION] = 'term';
			/* $sys['pages_link_mode'] */
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$tmp['href_term']['t'] = urlencode($arV[1]['term']);
				break;
				case GW_PAGE_LINK_URI:
					$tmp['href_term']['t'] = ($arV[1]['term_uri'] == '') ? urlencode($arV[1]['term']) : urlencode($arV[1]['term_uri']);
				break;
				default:
					$tmp['href_term']['t'] = $arV[1]['id_term'];
				break;
			}
			$tmp['href_term'][GW_ID_DICT] = $arDictParam['uri'];
			$href_term = append_url( $sys['page_index'] . '?' . $oHtml->paramValue($tmp['href_term'], '&', ''));
		}
		$arA[$k]['href'] = $href_term;
		$arA[$k]['term_text'] = $arV[1]['term'];
		if (trim($arA[$k]['defn']) != '')
		{
			$arA[$k]['term'] = $str_incomplete . $oHtml->a( $href_term, $arA[$k]['term'] );
		}
		else
		{
		  $arA[$k]['term'] = $str_incomplete . $arA[$k]['term'];
		}
		$oHtml->setTag('a', 'title', '');
		/* Chunk long definitions */
		$int_defn_length = $oFunc->mb_strlen($arA[$k]['defn'], $sys['internal_encoding']);
		if ($int_defn_length > $sys['int_max_char_defn'] )
		{
			$arA[$k]['defn'] = $oFunc->mb_wordwrap_first($arA[$k]['defn'], $sys['int_max_char_defn'], $sys['txt_magic_splitter'], 0);
#			$arA[$k]['defn'] = $arA[$k]['defn'];
			$int_s = $oFunc->mb_strpos($arA[$k]['defn'], $sys['txt_magic_splitter'], $sys['internal_encoding']);
			$arA[$k]['kb'] = $oFunc->number_format($arV[1]['int_bytes'] / 1024, 1, $oL->languagelist('4')) .'&#160;'. $oL->m('kb');
			/* $sys['txt_magic_splitter'] is not found */
			if ($int_s === false)
			{
				$int_s = $int_defn_length;
				$arA[$k]['kb'] = '';
			}
#			$href_term = append_url( $sys['page_index'].'?'.
#							GW_ACTION .'='. GW_T_TERM. '&'.
#							GW_ID_DICT .'='. $arA[$k]['d_id']. '&'.
#							GW_TARGET .'='. $arA[$k]['t_id']);
#			$arA[$k]['term'] = $oHtml->a( $href_term, $arA[$k]['term'] );
			if (GW_IS_BROWSE_ADMIN)
			{
				$arA[$k]['defn'] = ($int_s == $int_defn_length) ? $arA[$k]['defn'] : str_replace($sys['txt_magic_splitter'], '&#8230;', $arA[$k]['defn']);
			}
			else
			{
				$oHtml->setTag('a', 'class', 'more');
				/* add linked "More..." to definition text when $sys['txt_magic_splitter'] exists */
				if ($gw_this['vars']['a'] == GW_A_SEARCH)
				{
					$arA[$k]['defn'] = str_replace($sys['txt_magic_splitter'],
						$oHtml->a( $href_term, '&#8230;' ), $arA[$k]['defn']
					);
				}
				else
				{
					$arA[$k]['defn'] = str_replace($sys['txt_magic_splitter'],
						' '.$oHtml->a( $href_term, $oL->m('3_more') ), $arA[$k]['defn']
					);
				}
				$oHtml->setTag('a', 'class', '');
			}
		}
		unset($arV[1]);
		unset($arPreview[$k]);
	}
	return $arA;
}



/* Create custom page */
function gw_custom_page($id_page)
{
	global $oSqlQ, $oDb, $oTpl, $oFunc, $oHtml, $oL;
	global $gw_this, $sys, $str_current_section, $ar_tpl_construct, $arPost, $layout, $ar_theme;
	$id_lang = 0;
	/* Check languages first */
	switch ($sys['pages_link_mode'])
	{
		case GW_PAGE_LINK_NAME:
			$sql_id_page = 'gpph.page_title = "'.gw_text_sql($id_page).'"';
			if (is_numeric($id_page))
			{
				$sql_id_page = 'gp.id_page = "'.gw_text_sql($id_page).'"';
			}
		break;
		case GW_PAGE_LINK_URI:
			$sql_id_page = 'gp.page_uri = "'.gw_text_sql($id_page).'"';
			if (is_numeric($id_page))
			{
				$sql_id_page = 'gp.id_page = "'.gw_text_sql($id_page).'"';
			}
		break;
		default:
			$sql_id_page = 'gp.id_page = "'.gw_text_sql($id_page).'"';
		break;
	}
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-custompages-lang', $sql_id_page), 'page');
	for (; list($arK, $arV) = each($arSql);)
	{
		if ($arV['id_lang'] == $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'])
		{
			$id_lang = $arV['id_lang'];
			break;
		}
		elseif ($arV['id_lang'] == $sys['locale_name'])
		{
			$id_lang = $arV['id_lang'];
		}
		else
		{
			$id_lang = $arV['id_lang'];
		}
	}
	if (empty($arSql))
	{
		gwtk_header($sys['server_proto'].$sys['server_host'].$sys['page_index'], $sys['is_delay_redirect']);
	}
	$id_page_int = 0;
	if ($id_lang)
	{
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-custompages', $sql_id_page, $id_lang), 'page');
		/* Redirect to new URL */
		$is_redirect = 0;
		switch ($sys['pages_link_mode'])
		{
			case GW_PAGE_LINK_NAME:
				$page_uri = 'page_title';
				$is_redirect = ($id_page != $arSql[0][$page_uri]) && !is_numeric($id_page);
			break;
			case GW_PAGE_LINK_URI:
				$page_uri = 'page_uri';
				$is_redirect = ($arSql[0][$page_uri] && $id_page != $arSql[0][$page_uri]) && !is_numeric($id_page);
			break;
			default:
				$page_uri = 'id_page';
				$is_redirect = ($id_page != $arSql[0][$page_uri]);
			break;
		}
		if ($is_redirect)
		{
			global $oHtml;
			$href_page = $sys['page_index'].'?'.GW_ACTION.'='.'viewpage&'.'&id='.$arSql[0][$page_uri];
			gwtk_header($sys['server_proto'].$sys['server_host'].$oHtml->url_normalize($href_page), $sys['is_delay_redirect'], __FILE__, __LINE__);
		}
		for (; list($arK, $arV) = each($arSql);)
		{
			$id_page_int = $arV['id_page'];
			/* Process text filters */
			while (!$sys['is_debug_output']
					&& is_array($sys['filters_defn'])
					&& list($k, $v) = each($sys['filters_defn']) )
			{
				$arV['page_content'] = $v($arV['page_content']);
			}
			/* Custom content */
			$oTpl->addVal( 'block:page_content', $arV['page_content']);
			$oTpl->addVal( 'block:page_descr', $arV['page_descr']);
			/* Custom PHP-code */
			eval( $arV['page_php_1'] );
			$gw_this['ar_breadcrumb'][] = $gw_this['arTitle'][] = $str_current_section = strip_tags($arV['page_title']);
		}
	}
	/* Create the list of subpages */
	$arSqlc = $oDb->sqlRun($oSqlQ->getQ('get-custompages-list'), 'page');
	$arSqlc = gw_rearrange_to_locale($arSqlc);
	$arSqlc = gw_rearrange_to_tree($arSqlc);
	$ar_page_titles = array();
	$ar_pages_p_uplevel = array();
	$ar_pages_p_level = isset($arSqlc[$id_page_int]) ? $arSqlc[$id_page_int] : array();
	$arTpl['subpages_cnt'] = 0;
	$arTpl['subpages_tpl'] = '';
	$arTpl['subpages_dl'] = '';

	/* The list of pages, 1 level up. */
	$ar_parents = isset($arSqlc[$id_page_int]['p']) ? $arSqlc[$arSqlc[$id_page_int]['p']]['ch'] : array();
	for (; list($page_k, $ar_page_v) = each($ar_parents);)
	{
		if (($arSqlc[$page_k]['p'] == 0) && ($layout != 'title')) { continue; }
		switch ($sys['pages_link_mode'])
		{
			case GW_PAGE_LINK_NAME:
				$str_page_id = urlencode($arSqlc[$page_k]['page_title']);
			break;
			case GW_PAGE_LINK_URI:
				$str_page_id = urlencode($arSqlc[$page_k]['page_uri']);
			break;
			default:
				$str_page_id = $arSqlc[$page_k]['id'];
			break;
		}
#		if ($arSqlc[$page_k]['p'] == 0) { continue; }
		$ar_page_titles[] = $oHtml->a( $sys['page_index'] .
			'?a='.GW_A_CUSTOMPAGE.'&id=' . $str_page_id, $arSqlc[$page_k]['title']
		);
	}
	if (!empty($ar_page_titles))
	{
		$arTpl['subpages_ul'] = '<ul><li>' . implode('</li><li>', $ar_page_titles) . '</li></ul>';
	}
	/* */
	if (isset($arSqlc[$id_page_int]['ch']))
	{
		$arVarPage = array();
		$ar_page_titles = array();
		$subpages_cnt = 0;
		/* The list of subpages, current level. */
		$ar_subpages = $arSqlc[$id_page_int]['ch'];
		for (; list($page_k, $ar_page_v) = each($ar_subpages);)
		{
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$str_page_id = urlencode($arSqlc[$page_k]['title']);
				break;
				case GW_PAGE_LINK_URI:
					$str_page_id = urlencode($arSqlc[$page_k]['page_uri']);
				break;
				default:
					$str_page_id = $arSqlc[$page_k]['id'];
				break;
			}
			$arVarPage[$page_k] = $arSqlc[$page_k];
			$arVarPage[$page_k]['url:page_title'] = $oHtml->a( $sys['page_index'] .
				'?a='.GW_A_CUSTOMPAGE.'&id=' . $str_page_id, $arSqlc[$page_k]['title']
			);
			$ar_page_titles[] = $arVarPage[$page_k]['url:page_title'];
			$subpages_cnt++;
			if (isset($arVarPage[$page_k]['ch']))
			{
				unset($arVarPage[$page_k]['ch']);
			}
		}
		$arTpl['subpages_ul'] = '<ul><li>' . implode('</li><li>', $ar_page_titles) . '</li></ul>';
		/* the list of nested pages with short description */
		$oTplPage = new $sys['class_tpl'];
		$oTplPage->init($gw_this['vars']['visualtheme']);
		$oTplPage->set_tpl('tpl_custom_pages_list');
		if (isset($sys['path_www_images']))
		{
			$oTplPage->addVal( 'v:path_img_www', $sys['dirname'] . '/'. $sys['path_www_images'] );
		}
		for (; list($k2, $v2) = each($arVarPage);)
		{
			for (reset($v2); list($k, $v) = each($v2);)
			{
				$oTplPage->assign(array($k => $v));
			}
			$oTplPage->parseDynamic('list_custom_pages');
		}
		$oTplPage->parse();
		$arTpl['subpages_dl'] = $oTplPage->output();
		$arTpl['subpages_cnt'] = $subpages_cnt;
	}
	for (; list($k, $v) = each($arTpl);)
	{
		$oTpl->addVal($k, $v);
	}
	$gw_this['id_page_int'] = $id_page_int;
	$gw_this['ar_pages'] = $arSqlc;
}



/**
 * Top 10
 *
 * @param    int    $m [ R_DICT_AVERAGEHITS | R_DICT_EFFIC |
 *                       R_DICT_NEWEST | R_TERM_NEWEST |
 *                       R_DICT_UPDATED | R_USER_TOP | R_POLL ] // todo ...
 * @param   int     The amount of items (terms or dictionaries)
 * @param   int     Show terms only (without dates and links to dictionaries)
 * @param   int     Sorting order
 * @return   string complete HTML-code
 * @global $oL
 */
function getTop10($m, $amount = 10, $isItemOnly = 0, $order = 0, $top10_display = 1)
{
	if (!$top10_display) { return '{v:}'; }
	global $sys, $gw_this, $ar_theme, $arDictParam;
	global $oL, $oHtml, $oDb, $oSqlQ, $oFunc, $oSess;
	$str = $str_foot = $str_head = '';
	$cnt = 0;
	/* Current date in UNIX timestamp */
	$curDateMk = $sys['time_now_gmt_unix'];
	$arThText = $arThWidth = $ar_top10_list = array();
	$strData = $strTopicName = '';
	$m = strtolower($m);
	$filename = $sys['path_include'] . '/top.' . $m . '.inc.php';
	if (file_exists($filename))
	{
		include_once( $filename );
	}
	else
	{
		print("<br />Can't find " . $filename);
	}
	/* Create list */
	if ($top10_display > 1)
	{
		$str .= '<h4>'. $strTopicName .'</h4><div class="termpreview">';
		$str .= implode(', ', $ar_top10_list).'</div>';
		return $str;
	}
	/* Create table */
	$intRows = sizeof($arThWidth);
	if (!empty($arThText) || !empty($arThWidth))
	{
		if (GW_IS_BROWSE_ADMIN)
		{
			$str .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
		}
		else
		{
			$str .= '<table style="background:'.$ar_theme['color_1'].'" width="100%" border="0" cellpadding="3" cellspacing="1">';
		}
		if ($strTopicName != '')
		{
			$str_head .= '<tr>'
				 . '<td style="text-align:'.$sys['css_align_left'].'" colspan="' . ($intRows + 1) . '">'
				 . '<h4>' . $strTopicName . '</h4></td>'
				 . '</tr>';
		}
		if (!empty($arThText))
		{
			$str_head .= '<tr>';
			$str_head .= '<th class="gw" style="text-align:center;width:1%">N</th>';
			for (reset($arThText); list ($kT, $vT)= each($arThText);)
			{
				$str_width = '';
				if (isset($arThWidth[$kT]) && $arThWidth[$kT])
				{
					$str_width = ' style="width:'. $arThWidth[$kT] . '"';
				}
				$str_head .= '<th class="gw"'.$str_width.'>' . $vT . '</th>';
			}
			$str_head .= '</tr>';
		}
		else if (!empty($arThWidth))
		{
			$str_foot .= '<tr>';
			$str_foot .= '<th style="font-size:1px;height:1px;width:1%"></th>';
			for (reset($arThWidth); list ($kT, $vT)= each($arThWidth);)
			{
				$str_width = '';
				if (isset($arThWidth[$kT]) && ($arThWidth[$kT] != ''))
				{
					$str_width = ' style="font-size:1px;height:1px;width:'. $arThWidth[$kT] . '"';
				}
				$str_foot .= '<th'.$str_width.'></th>';
			}
			$str_foot .= '</tr>';
		}
		if ($str_head)
		{
			$str .= '<thead>'.$str_head.'</thead>';
		}
		if ($str_foot)
		{
			$str .= '<tfoot>'.$str_foot.'</tfoot>';
		}
		$str .= '<tbody>';
		$str .= $strData;
		$str .= '</tbody>';
		$str .= '</table>';
	}
	return $str;
}

/* Get dictionary IDs, cached */
function getValidDictID()
{
	global $oSqlQ, $oDb;
	return $oDb->sqlRun($oSqlQ->getQ('get-dict-valid'), 'dict');
}

/* */
function getStat()
{
	global $oSqlQ, $oDb, $sys;
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-terms-total'), 'st');
	$arSql = isset($arSql[0]) ? $arSql[0] : array('num' => 0, 'sum' => 0);
	$arSql['date'] = $sys['time_now_gmt_unix'];
	return $arSql;
}

/* */
function getSettings()
{
	global $oSqlQ, $oDb, $oFunc, $sys;
	$strA = array();
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-settings'), 'st');

	/* No system settings found, run install */
	if (empty($arSql))
	{
		if (file_exists('gw_install/index.php'))
		{
			$sys['server_proto'] = 'http://';
			$sys['server_host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '' );
			$sys['server_dir'] = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' );
			$ar_path = explode("/", $sys['server_dir']);
			unset( $ar_path[sizeof($ar_path)-1] );
			$sys['server_dir'] = implode('/', $ar_path);
			gwtk_header( $sys['server_proto'].$sys['server_host'].$sys['server_dir'].'/gw_install/index.php' );
		}
		print '<p>Software is not installed.</p>';
		print '<p><a href="'.$sys['server_dir'].'/gw_install/index.php">Run installation script</a></p>';
		exit;
	}
	for (; list($k, $v) = each($arSql);)
	{
		$strA[$v['settings_key']] = $v['settings_val'];
	}
	return $strA;
}



/**
 * Builds HTML-code for page navigation, like [ Pages: 1 .. 5 6 7 .. 11 ]
 * 12 Mar 2008: Old pagination code has been completely replaced.
 *
 * @return   string  simple HTML-code, ready to put inside a table or else.
 */
function getNavToolbar($page_total, $page_current = 1, $url)
{
	global $sys, $oHtml, $ar_theme;
	if ($page_total == 1 || $page_total == 0)
	{
		return '&#160;';
	}
	$ar_pages = array();
	/* HTML-tag to select the current page */
	$str_tag = 'strong';
	/* Language strings */
	$str_page_prev = '&lt;&lt;&#160;'.$GLOBALS['oL']->m('1_prevpage');
	$str_page_next = $GLOBALS['oL']->m('1_nextpage').'&#160;&gt;&gt;';
	/* String placed between the first and last page numbers */
	$str_more = '..';
	/* String to separate page numbers */
	$str_d = isset($ar_theme['split_pagenumbers']) ? $ar_theme['split_pagenumbers'] : ' | ';
	/* URL for paging */
	$str_url = str_replace('%', '%%', $url).'%d';
	/* The number of links to pages displayed before and after the current page. 1 2 (3) 1 2 */
	$int_max = $sys['max_page_links'];
	/* Some counters */
	$cnt_max = $page_current + $int_max;
	$cnt_min = $page_current - $int_max;
	/* Fix the maximum number of pages */
	if ($cnt_max > $page_total)
	{
		$cnt_max = $page_total;
	}
	/* Links to Next/Prev pages */
	if ($page_current > 1)
	{
		$ar_pages[] = $oHtml->a(sprintf($str_url, ($page_current - 1)), $str_page_prev);
	}
	/* The first page */
	if ($cnt_min > 1)
	{
		$ar_pages[] = $oHtml->a(sprintf($str_url, 1), 1);
		/* Do not show .. for `1 | .. | 2 | 3` */
		if (($page_current - $int_max) != 0)
		{
			$ar_pages[] = $str_more;
		}
	}
	/* For each page number */
	for ($i = 1; $i <= $page_total; $i++)
	{
		if ( ($i >= $cnt_min && $i <= $cnt_max) )
		{
			if ($i == $page_current)
			{
				$ar_pages[] = $oHtml->a(sprintf($str_url, $i), '<'.$str_tag.' class="on">'.$i.'</'.$str_tag.'>');
			}
			else
			{
				$ar_pages[] = $oHtml->a(sprintf($str_url, $i), $i);
			}
		}
	}
	/* The last page */
	if ($cnt_max > 1 && ($page_current + $int_max) < $page_total)
	{
		/* Do not show .. for `25 | 26 | .. | 27` */
		if (($page_current + $int_max + 1) < $page_total)
		{
			$ar_pages[] = $str_more;
		}
		$ar_pages[] = $oHtml->a(sprintf($str_url, $page_total), $page_total);
	}
	/* Links to Next/Prev pages */
	if ($page_current < $page_total)
	{
		$ar_pages[] = $oHtml->a(sprintf($str_url, ($page_current + 1)), $str_page_next);
	}
	return implode($str_d, $ar_pages);
}


/**
 * Get all dictionary parameters, such as:
 * title, description, number of terms, sql-table name etc.
 *
 * @param    int     dictionary ID
 * @return   array   dictionary name, description, total terms etc.
 */
function getDictParam($id_dict)
{
	global $gw_this, $sys, $oDb, $oSqlQ;
	$ar = array();
	/* */
	switch ($sys['pages_link_mode'])
	{
		case GW_PAGE_LINK_NAME:
			$compare_to = 'title';
			if (is_numeric($id_dict))
			{
				$compare_to = 'id';
			}
		break;
		case GW_PAGE_LINK_URI:
			$compare_to = 'dict_uri';
		break;
		default:
			$compare_to = 'id';
		break;
	}
	if (!is_array($gw_this['ar_dict_list']))
	{
		return array();
	}
	if ( GW_IS_BROWSE_ADMIN || ($gw_this['vars']['a'] == GW_A_SEARCH) )
	{
		$compare_to = 'id';
	}
#prn_r( $compare_to );
	/* For for each dictionary */
	for (reset($gw_this['ar_dict_list']); list($kDict, $vDict) = each($gw_this['ar_dict_list']);)
	{
		if ($vDict[$compare_to] == $id_dict)
		{
			$vDict['dict_settings'] = unserialize($vDict['dict_settings']);
			if (is_array($vDict['dict_settings']))
			{
				/* merge dictionary settings into one array */
				$vDict = array_merge($vDict, $vDict['dict_settings']);
			}
			unset($vDict['dict_settings']);
			/* add dictionary uri */
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$vDict['uri'] = urlencode($vDict['title']);
					break;
				case GW_PAGE_LINK_URI:
					$vDict['uri'] = urlencode($vDict['dict_uri']);
					break;
				default:
					$vDict['uri'] = $id_dict;
				break;
			}
#			prn_r( $vDict );
			return $vDict;
		}
	}
	/* no such dictionary */
	return false;
}


/**
 * Get a random term from a random dictionary
 * 
 * @return  array   array with term and dictionary
 */
function getTermRandom()
{
	global $gw_this, $oDb, $oSqlQ;
	$arDictParam = $gw_this['ar_dict_list'][mt_rand(0, sizeof($gw_this['ar_dict_list'])-1)];
	$sql = $oSqlQ->getQ('get-term-rand', $arDictParam['tablename']);
	$arSql = $oDb->sqlExec($sql, '', 0);
	$arSql = isset($arSql[0]) ? $arSql[0] : array();
	$arSql = array_merge($arDictParam, $arSql);
	return $arSql;
}


/**
 * Get term parameters by term ID or by term name.
 *
 * @param    int     term ID
 * @param    string  term name
 * @return   array   term id, defn id, name, definition(s), synonym(s)
 */
function getTermParam($tid = '', $name = '')
{
	global $gw_this, $oL, $arDictParam, $oDb, $oSqlQ, $oSess, $sys;
	$arFound = $arFoundInit = array(
		'is_active' => '0', 'is_complete' => '0', 
		'term' => '', 'term_uri' => '',
		'term_1' => ' ', 'term_2' => ' ', 'term_3' => ' ', 
		'defn' => '', 'tid' => '', 'term_order' => '',
		'date_created' => 0, 'date_modified' => 0
	);
	$term_uri = 'tid';
	if ($tid)
	{
		/* search by id (faster) */
		if (GW_IS_BROWSE_ADMIN)
		{
			$sql = $oSqlQ->getQ('get-term-by-id-adm', $arDictParam['tablename'], $tid);
		}
		else
		{
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$term_uri = 'term';
					$sql = $oSqlQ->getQ('get-term-by-term', $arDictParam['tablename'], gw_text_sql($tid), gw_text_sql($tid), $sys['time_now_db']);
					/* Switch to Term ID (faster) */
					if (is_numeric($tid) || ($gw_this['vars']['a'] == GW_A_CUSTOMPAGE))
					{
						$sql = $oSqlQ->getQ('get-term-by-id', $arDictParam['tablename'], gw_text_sql($tid), $sys['time_now_db']);
					}
				break;
				case GW_PAGE_LINK_URI:
					$term_uri = 'term_uri';
					$sql = $oSqlQ->getQ('get-term-by-term', $arDictParam['tablename'], gw_text_sql($tid), gw_text_sql($tid), $sys['time_now_db']);
					/* Switch to Term ID (faster) */
					if (is_numeric($tid) || ($gw_this['vars']['a'] == GW_A_CUSTOMPAGE))
					{
						$sql = $oSqlQ->getQ('get-term-by-id', $arDictParam['tablename'], gw_text_sql($tid), $sys['time_now_db']);
					}
				break;
				default:
					$term_uri = 'tid';
					$sql = $oSqlQ->getQ('get-term-by-id', $arDictParam['tablename'], gw_text_sql($tid), $sys['time_now_db']);
				break;
			}
		}
		$arFound = $oDb->sqlExec($sql, sprintf("%05d", $gw_this['vars'][GW_ID_DICT]), 0);
		if (isset($arFound[0]))
		{
			$arFound = $arFound[0];
		}
		else
		{
			$arFound = $arFoundInit;
		}
		/* Redirect to new URL */
		if (GW_IS_BROWSE_WEB)
		{
			$is_redirect = 0;
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$is_redirect = ($tid != $arFound['term']);
				break;
				case GW_PAGE_LINK_URI:
					$is_redirect = ($arFound['term_uri'] && ($tid != $arFound['term_uri']));
				break;
				default:
					$is_redirect = ($tid != $arFound['tid']);
				break;
			}
			if ($is_redirect && ($gw_this['vars']['a'] == GW_T_TERM) && !$gw_this['vars']['is_print'])
			{
				global $oHtml;
				$href_term = $sys['page_index'].'?'.GW_ACTION.'='.'term&'.GW_ID_DICT.'='.$arDictParam['uri'].'&t='.$arFound[$term_uri];
				gwtk_header($sys['server_proto'].$sys['server_host'].$oHtml->url_normalize($href_term), $sys['is_delay_redirect'], __FILE__, __LINE__);
			}
		}
		/* temporary */
		$arFound['defn'] = str_replace('<![CDATA[', '', $arFound['defn']);
		$arFound['defn'] = str_replace(']]>', '', $arFound['defn']);
	}
	elseif ($name != '')
	{
		/* search for a term by name */
		/* remove specials */
		$arKeywordsT = text2keywords( text_normalize( gw_stripslashes($name) ), 1);
		$word_srch_sql = "'" . implode("', '", $arKeywordsT) . "'";
		if (GW_IS_BROWSE_ADMIN)
		{
			$sql = $oSqlQ->getQ('get-term-by-name-adm', TBL_WORDLIST, TBL_WORDMAP, $arDictParam['tablename'], $gw_this['vars'][GW_ID_DICT], $word_srch_sql);
		}
		else
		{
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$term_uri = 'term';
					if (is_numeric($tid) && is_numeric($gw_this['vars'][GW_ID_DICT]))
					{
						$term_uri = 'tid';
					}
				break;
				case GW_PAGE_LINK_URI:
					$term_uri = 'term_uri';
					if (is_numeric($tid) && is_numeric($gw_this['vars'][GW_ID_DICT]))
					{
						$term_uri = 'tid';
					}
				break;
				default:
					$term_uri = 'tid';
				break;
			}
			$sql = $oSqlQ->getQ('get-term-by-name', TBL_WORDLIST, TBL_WORDMAP, $arDictParam['tablename'], $gw_this['vars'][GW_ID_DICT], $word_srch_sql);
		}
		$arSql = $oDb->sqlExec($sql, sprintf("%05d", $gw_this['vars'][GW_ID_DICT]), 0);
		for (reset($arSql); list($arK, $arV) = each($arSql);) // compare founded values (Q) with imported (T)
		{
			$isTermExist = 0;
			// first method, 08 july 2000
			if (!$isTermExist && ($arV['term'] == $name))
			{
				$isTermExist = 1;
				$arFound = $arV;
#				prn_r( $arV['term'].' = '.$name."\narsize=".sizeof($arSql) );
				break; // breaks at first loop, usually.
			}
			// Do NOT remove specials
			$arKeywordsQ = text2keywords( text_normalize($arV['term']), 1); // 1 - is the minimum length
			$div1 = sizeof(gw_array_exclude($arKeywordsT, $arKeywordsQ));
			$div2 = sizeof(gw_array_exclude($arKeywordsQ, $arKeywordsT));
			$isTermNotMatched = ($div1 + $div2);
			// if the sum of excluded arrays is 0, this term already exists
			if (!$isTermNotMatched) // in english, double negative means positive. yeah.
			{
				$isTermExist = 1;
			}
			if ($isTermExist)
			{
				$arFound = $arV;
			}
		} // end of for each founded terms
	}
	/* on SEF enabled */
	switch ($sys['pages_link_mode'])
	{
		case GW_PAGE_LINK_NAME:
			$arFound['uri'] = urlencode($arFound['term']);
		break;
		case GW_PAGE_LINK_URI:
			$arFound['uri'] = urlencode($arFound['term_uri']);
		break;
		default:
			$arFound['uri'] = $tid;
		break;
	}
	if (empty($arFound))
	{
		$arFound = $arFoundInit;
	}
	return $arFound;
}





## --------------------------------------------------------
## Toolbar functions A-Z, 00-ZZ

/**
 * === Toolbar functions.
 * 1 of 2 functions to create alphabetic index.
 * Get first letters from all terms in dictionary
 *
 * @param    int     $id_dict dictionary ID
 * @param    int     $w      second symbol (optional) // not in use since 1.3
 * @return   array   initial letters 00-ZZ
 */
function getLettersArray($id_dict, $w = '')
{
	global $oDb, $oSqlQ, $oFunc;
	global $arDictParam, $gw_this, $sys;
	$az_sql = '';
	if ($arDictParam['az_order'])
	{
		$az_sql = 'FIELD(t.term_a, '.$arDictParam['az_order'].
					'), FIELD(t.term_b, '.$arDictParam['az_order'].
					'), FIELD(t.term_c, '.$arDictParam['az_order'].'), ';
	}

	/* Since 1.8.4 cannot be cached because of use delayed postings */
	$arSql = $oDb->sqlExec($oSqlQ->getQ('get-az', $arDictParam['tablename'], $sys['time_now_db'], $az_sql) );

	/* One array for both indexes (single and double) */
	$arA = array();
	$sys['ar_az_last_characters'] = array();
	for (; list($k, $v) = each($arSql);)
	{
		/* Must be mb_substr($v['L1'], 0, 1), but parameter (0, 3) allows to override 
		   Unicode sorting order for diacritics. Example (urlencoded): S%CC%8C overrides %C5%A0
		05 jul 2005: varchar(0, 64) allows to use toolbar as the list of topics.
		24 jul 2006: toolbar limits removed for higher performance.
		16 may 2007: 3rd toolbar added
		27 nov 2007: only 1 letter allowed, varchar(0, 4) - 4 bytes is the maximum length for a single character in UTF-8.
		*/
#		if (!$v['int_sort'])
#		{
#			$sys['ar_az_last_characters'][$v['L1']] = '';
#		}
		if ( $gw_this['vars']['a'] == GW_T_TERM || ($gw_this['vars']['w1'] != '' && $gw_this['vars']['w2'] != '') )
		{
			/* w1, w2, w3 or w1, w2 selected */
			if ($gw_this['vars']['a'] == GW_T_TERM || ($gw_this['vars']['w1'] == $v['L1'] && $gw_this['vars']['w2'] == $v['L2']))
			{
				/* shows w3 for selected w2 only */
				$arA[$v['L1']][$v['L2']][$v['L3']] = '';
			}
			else
			{
				$arA[$v['L1']][$v['L2']] = '';
			}
		}
		elseif ($gw_this['vars']['w1'] != '')
		{
			/* w1 selected */
			/* shows w2 for selected w1 only */
			if ($gw_this['vars']['w1'] == $v['L1'])
			{
				$arA[$v['L1']][$v['L2']] = '';
			}
			else
			{
				$arA[$v['L1']] = '';
			}
		}
		else
		{
			/* nothing selected */
			$arA[$v['L1']] = '';
		}
		unset($arSql[$k]);
	}
	return $arA;
}

/**
 * === Toolbar functions.
 * 2 of 2 functions to create alphabetic index.
 * Get HTML-code for A-Z letters. Universal function.
 *
 * @param    array   $ar array[49][49] = 11;
 * @param    string  $id_dict Dictionary ID, for links only
 * @param    string  $w1 Alphabetic order 1
 * @param    string  $w2 Alphabetic order 2
 * @param    string  $w3 Alphabetic order 3
 * @return   string  HTML-code
 */
function getLetterHtml($ar, $id_dict, $w1 = '', $w2 = '', $w3 = '')
{
	global $oFunc, $oHtml, $sys, $arDictParam;

	/* Basic Multilingual Plane: */
	/* http://www.unicode.org/roadmaps/bmp/ */
	/* Break alphabetic toolbar per every set of characters */
	if (strtoupper($sys['internal_encoding']) == 'UTF-8')
	{
		$arUnicodeMap = array(
			array('20', 'Basic Latin Digits'),
			array('41', 'Basic Latin'),
			array('c280', 'Latin Extended'),
			array('c990', 'IPA Extensions'),
			array('cab0', 'Spacing Modifiers'),
			array('cc80', 'Combining Diacritics'),
			array('cdb0', 'Greek'),
			array('d080', 'Cyrillic, Cyrillic Supplement'),
			array('d4b0', 'Armenian')
		);
	}
	else
	{
		$arUnicodeMap = array(
			array('20', 'Basic Latin Digits'),
			array('41', 'Basic Latin'),
			array('c0', 'Cyrillic, Cyrillic Supplement'),
			array('ff', 'Other')
		);
	}
	$int_cnt = 0;
	$arS = array();
	$ar_tb_last = end($arUnicodeMap);
	$int_tb_last = hexdec($ar_tb_last[0]);
	/* links to letters */
	$arTmp['href'][GW_ACTION] = GW_A_LIST;
	$arTmp['href'][GW_TARGET] = GW_T_DICT;
	$arTmp['href'][GW_ID_DICT] = $id_dict;
	/* for each letter */
	for (reset($ar); list($k1, $v1) = each($ar);)
	{
		$int_cnt++;
		$cnt1_str = (isset($sys['is_print_toolbar_num']) && $sys['is_print_toolbar_num'] == 1) ? $int_cnt : '';
		/* 0-Z */
		if (($w1 != '') && ($w2 == ''))
		{
			$oHtml->setTag('a', 'title', $cnt1_str);
			$arTmp['href']['w1'] = urlencode($k1);
			/* current letter */
			$int_utf2hex = (ord($k1) >= 127) ? ($oFunc->text_utf2hex($k1, 0)) : dechex(ord($k1));
			reset($arUnicodeMap);
			while (($arDictParam['id_custom_az'] == 1) && list($k2, $v2) = each($arUnicodeMap))
			{
				/* next letter */
				if (isset($arUnicodeMap[$k2+1]))
				{
					$int_utf2hex_tb_to = ($arUnicodeMap[$k2+1][0]);
				}
				else
				{
					$int_utf2hex_tb_to = $int_tb_last;
				}
				/* start letter */
				$int_utf2hex_tb_from = ($v2[0]);
				/* start letter > current letter < next letter */
				if (($int_utf2hex >= $int_utf2hex_tb_from) 
					&& ($int_utf2hex < $int_utf2hex_tb_to))
				{
#prn_r( $int_utf2hex_tb_from . '=> '. $int_utf2hex .' ('.  $k1 . ') <=' .$int_utf2hex_tb_to );
					$oHtml->setTag('a', 'class', '');
					if (strval($k1) == (trim($w1)))
					{
						$oHtml->setTag('a', 'class', 'on');
					}
					$arS[$k2][] = $oHtml->a( $sys['page_index'].'?'.
									$oHtml->paramValue($arTmp['href'], '&', ''),
									($k1));

				}
			}
			/* 1.8.6-dev: Custom alphabetic order */
			if ($arDictParam['id_custom_az'] > 1)
			{
				$oHtml->setTag('a', 'class', '');
				if (strval($k1) == (trim($w1)))
				{
					$oHtml->setTag('a', 'class', 'on');
				}
				if (isset($sys['ar_az_last_characters'][$k1]))
				{
					$arS[0][] = $oHtml->a( $sys['page_index'].'?'. $oHtml->paramValue($arTmp['href'], '&', ''), ($k1));
				}
				else
				{
					$arS[1][] = $oHtml->a( $sys['page_index'].'?'. $oHtml->paramValue($arTmp['href'], '&', ''), $k1 );
				}
			}
			$oHtml->setTag('a', 'title', '');
		}
		else if (($w3 != ''))
		{
			/* 000-ZZZ */
			if (strval($k1) == (trim($w1))  )
			{
  				for (reset($v1); list($k2, $v2) = each($v1);)
				{
					if (empty($v1[(trim($w2))])){ continue; }
					if (strval($k2) != (trim($w2)) ) { continue; } /* fix for getLettersArray() */
					for (reset($v2); list($k3, $v3) = each($v2);)
					{
						$oHtml->setTag('a', 'class', '');
						if (strval($k3) == (trim($w3)))
						{
							$oHtml->setTag('a', 'class', 'on');
						}
						$arTmp['href']['w1'] = urlencode($k1);
						$arTmp['href']['w2'] = urlencode($k2);
						$arTmp['href']['w3'] = urlencode($k3);
						$arS['0z'][] = $oHtml->a($sys['page_index'].'?'.
										$oHtml->paramValue($arTmp['href'], '&', ''),
										$k1.$k2.$k3);
					}
				}
			}
		}
		else
		{
			/* 00-ZZ */
			if (strval($k1) == (trim($w1)))
			{
				if (!is_array($v1)){ continue; }
				for (reset($v1); list($k2, $v2) = each($v1);)
				{
					$oHtml->setTag('a', 'class', '');
					if (strval($k2) == (trim($w2)))
					{
						$oHtml->setTag('a', 'class', 'on');
					}
					$arTmp['href']['w1'] = urlencode($k1);
					$arTmp['href']['w2'] = urlencode($k2);
					$arS['0z'][] = $oHtml->a($sys['page_index'].'?'.
										$oHtml->paramValue($arTmp['href'], '&', ''),
										 $k1.$k2);
				}
			}
		}
	}
	unset($ar);
	$oHtml->setTag('a', 'title', '');
	$oHtml->setTag('a', 'class', '');
	/* Build html-code */
	$str = '';
	ksort($arS);
	for (reset($arS); list($k1, $v1) = each($arS);)
	{
		if (is_array($v1))
		{
			$str .= implode(' ', $v1);
			$str .= '<br />';
		}
	}
	return $str;
}
## Toolbar functions A-Z, 00-ZZ
## --------------------------------------------------------




/**
 * Parses XML-data and converts it into structured array.
 *
 * @param   string  $str        XML-code, (from database)
 * @return  array   Fields content structure
 */
function gw_Xml2Array($str)
{
	global $arFields;
	//
	$xmlRoot = array();
	$xmlTags = array();
	$xmlAttr = array('link', 'lang', 'text', 'size'); /* possible attributes */
	$str_tmp = '';
	/* Fix for empty definitions */
	$str = str_replace('<defn><![CDATA[]]></defn>', '', $str);
	// Get defined tags
	for (reset($arFields); list($fk, $fv) = each($arFields);)
	{
		$fieldname = 'is_'.$fv[0];
		if (isset($fv[4]) && $fv[4]) // root
		{
			$xmlRoot[] = $fv[0];
		}
		else // not root elements
		{
			$xmlTags[] = $fv[0];
		}
	}
	/* Go for each root element */
	for (reset($xmlRoot); list($kp, $vp) = each($xmlRoot);)
	{
		preg_match_all("/<$vp>(.+?)<\/$vp>/s", $str, $strDefnA); // root tags without attributes
		if (!isset($strDefnA[0]) || !isset($strDefnA[0][0]) || empty($strDefnA[0][0]))
		{
			continue;
		}
		/* the number of definitions */
		$intDefnS = sizeof($strDefnA[0]);
		/* for each <defn> */
		for ($intDefnC = 0; $intDefnC < $intDefnS; $intDefnC++)
		{
			/* 10 march 2003: based on `value' */
			$parsedAr[$vp][$intDefnC]['value'] = $strDefnA[1][$intDefnC];
			/* search for attributtes */
			for (reset($xmlTags); list($kt, $vt) = each($xmlTags);)
			{
				preg_match_all("/<$vt(.*?)\>(.*?)\<\/$vt\>/s", $strDefnA[1][$intDefnC], $strTmpA);
				if (!isset($strTmpA[0]) || empty($strTmpA[0]))
				{
					continue;
				}
				$intTmpS = sizeof($strTmpA[0]);
				for ($intTmpC = 0; $intTmpC < $intTmpS; $intTmpC++) /* foreach */
				{
					$parsedAr[$vt][$intDefnC][$intTmpC]['value'] = $strTmpA[2][$intTmpC];
					$parsedAr[$vp][$intDefnC]['value'] = trim(str_replace($strTmpA[0][$intTmpC], '', $parsedAr[$vp][$intDefnC]['value']));
					if (!isset($strTmpA[1][0]) || empty($strTmpA[1][0]))
					{
						continue;
					}
					/* 22 jan 2006: read any attributes per any tag */
					for (reset($xmlAttr); list($ka, $va) = each($xmlAttr);)
					{
						preg_match_all("/$va=\"(.*?)\"/", $strTmpA[1][$intTmpC], $ar_attr);
						if (!isset($ar_attr[1][0]) || empty($ar_attr[1][0]))
						{
							continue;
						}
						if ($va == 'link' && $ar_attr[1][0] != '')
						{
							$parsedAr[$vt][$intDefnC][$intTmpC]['attributes']['is_link'] = 1;
						}
						$parsedAr[$vt][$intDefnC][$intTmpC]['attributes'][$va] = $ar_attr[1][0];
					}
				}
			} /* $xmlTags  */
		} /* end of for each defn */
	} /* end of $xmlRoot */
	if (!isset($parsedAr)){ $parsedAr['defn'][0] = ''; }
	return $parsedAr;
}

/* end of file */
?>