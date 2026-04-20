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
 *  Math, SQL, HTML functions for browsing dictionary.
 */
// --------------------------------------------------------

/**
 * Returns list of themes for select input.
 *
 * @return array Theme names indexed by theme ID
 */
function gw_get_themes_select()
{
    global $gw_this;

    $themes = [];

    if (!is_array($gw_this['ar_themes'])) {
        return [];
    }

    foreach ($gw_this['ar_themes'] as $key => $theme) {
        $themes[$key] = $theme['theme_name'];

        if (GW_IS_BROWSE_ADMIN) {
            $themes[$key] .= ' ' . $theme['theme_version'] . ' (' . $theme['theme_author'] . ')';
        }
    }

    return $themes;
}

/**
 * Returns list of active themes.
 *
 * Array keys are id_theme values.
 *
 * @return array List of themes indexed by theme ID
 */
function gw_get_themes_list()
{
    global $sys, $oDb, $oSqlQ;

    if (GW_IS_BROWSE_ADMIN) {
        $themeRows = $oDb->sqlExec($oSqlQ->getQ('get-themes-adm'));
    } else {
        $themeRows = $oDb->sqlRun($oSqlQ->getQ('get-themes'), 'theme');
    }

    // Reformat rows
    $themes = [];

    foreach ($themeRows as $row) {
        $row['theme_version'] = $row['v1'] . '.' . $row['v2'] . '.' . $row['v3'];
        unset($row['v1'], $row['v2'], $row['v3']);

        $themes[$row['id_theme']] = $row;
    }

    return $themes;
}

/**
 * Returns settings for a visual theme.
 *
 * Loads custom theme settings first. If theme is not found,
 * falls back to the default visual theme.
 *
 * @param string $themeName Theme name
 * @return array Theme settings
 */
function gw_get_theme($themeName)
{
    global $sys, $oDb, $oSqlQ;

    $themeSettings = [];
    // 1,2 - theme settings only (colors, theme credits)
    $themeRows = $oDb->sqlRun(
        $oSqlQ->getQ('get-theme', gw_text_sql($themeName), '1,2'),
        'theme'
    );
    if (empty($themeRows)) {
        // Custom theme not found, load default theme
        $themeName = $sys['visualtheme'];
        $sys['path_theme'] = $themeName;

        $sql = $oSqlQ->getQ('get-theme', gw_text_sql($themeName), '1,2');
        $themeRows = $oDb->sqlRun($sql, 'theme');

        if (empty($themeRows)) {
            die(
                'Unable to load visual theme `' . $themeName . '` from table `' .
                $sys['tbl_prefix'] . 'themes`. Check database settings or reinstall the software.'
            );
        }
    } else {
        $sys['path_theme'] = $themeName;
    }

    foreach ($themeRows as $row) {
        $themeSettings[$row['settings_key']] = $row['settings_value'];
    }

    return $themeSettings;
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

    foreach ($arSql as $k => $arV) {
		$incr_term = $k;
		$arPre = array();
		/* Init. */
        foreach ($arFields as $fK => $fV) {
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
		$arPre = gw_array_merge_clobber($arPre, gw_Xml2Array('<term>'. $arV['term'].'</term>'. $arV['defn']));

        foreach ($arFields as $field_index => $field_config) {
            $field_name = $field_config[0];

            if (!isset($arPre[$field_name])) {
                continue;
            }

            $tmp_str = $oDom->get_content($arPre[$field_name]);
            if (trim($tmp_str) == '' || $tmp_str == '<![CDATA[]]>') {
                continue;
            }

            if (!$arDictParam['is_' . $field_name]) {
                continue;
            }

            switch ($field_name) {
                case 'trsp':
                    foreach ($arPre[$field_name] as $preview_index => $preview_group) {
                        $preview_values = gw_collect_nested_preview_values($preview_group);
                        $preview_text = '[' . gw_text_parse_preview(implode('; ', $preview_values)) . ']';

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] .= $preview_text;
                        } else {
                            if (!isset($arDuplicates[$incr_term][$preview_index][$field_index])) {
                                $arDuplicates[$incr_term][$preview_index][$field_index] = '';
                            }

                            $arDuplicates[$incr_term][$preview_index][$field_index] .= $preview_text;
                        }
                    }
                    break;

                case 'defn':
                    foreach ($arPre[$field_name] as $preview_index => $preview_item) {
                        $preview_text = gw_text_parse_preview($preview_item['value']);

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] .= $preview_text;
                        } else {
                            $arDuplicates[$incr_term][$preview_index][$field_index] = $preview_text;
                        }
                    }
                    break;

                case 'abbr':

                    foreach ($arPre[$field_name] as $preview_index => $preview_group) {
                        $preview_values = gw_collect_nested_preview_values($preview_group, true);
                        $preview_text = gw_build_abbr_preview_text($preview_values, $ar_theme);

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] = $preview_text;
                        } else {
                            $arDuplicates[$incr_term][$preview_index][$field_index] = $preview_text;
                        }
                    }
                    break;
                case 'trns':
                    foreach ($arPre[$field_name] as $preview_index => $preview_group) {
                        $preview_values = gw_collect_trns_preview_values($preview_group);
                        $preview_text = gw_build_abbr_preview_text($preview_values, $ar_theme);

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] = $preview_text;
                        } else {
                            $arDuplicates[$incr_term][$preview_index][$field_index] = $preview_text;
                        }
                    }
                    break;

                case 'see':
                case 'syn':
                case 'antonym':
                    foreach ($arPre[$field_name] as $preview_index => $preview_group) {
                        $preview_values = gw_collect_nested_preview_values($preview_group);
                        $preview_text = gw_text_parse_preview(implode(', ', $preview_values));

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] = $oL->m($field_name) . ': ' . $preview_text;
                        } else {
                            // Keep legacy structure without field index key.
                            $arDuplicates[$incr_term][$preview_index][] = $preview_text;
                        }
                    }
                    break;

                case 'usg':
                case 'src':
                case 'phone':
                case 'address':
                    $duplicate_parts = [];

                    foreach ($arPre[$field_name] as $preview_index => $preview_group) {
                        $preview_values = gw_collect_nested_preview_values($preview_group);
                        $preview_text = gw_text_parse_preview(implode('; ', $preview_values));

                        if ($preview_index == 0) {
                            $arPreview[$incr_term][$preview_index][$field_index] .= ' =' . $preview_text;
                        } else {
                            if (!isset($duplicate_parts[$preview_index])) {
                                $duplicate_parts[$preview_index] = [];
                            }

                            $duplicate_parts[$preview_index][] = $preview_text;
                        }
                    }

                    foreach ($duplicate_parts as $preview_index => $preview_texts) {
                        $arDuplicates[$incr_term][$preview_index][$field_index] = gw_text_parse_preview(
                            implode(' ', $preview_texts)
                        );
                    }
                    break;

                default:
                    break;
            }
        }

        unset($arSql[$k]);
	} /* end of $arSql */
	unset($arPre);

#prn_r( $arPreview );
#prn_r( $arDuplicates );

	$arA = array(array());
	$int_timer = 0;
    foreach ($arPreview as $k => $arV) {
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
			$arA[$k]['defn_tooltip'] = mb_substr(trim($arA[$k]['defn_tooltip']), 0, 128, $sys['internal_encoding']);
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
		$int_defn_length = mb_strlen($arA[$k]['defn']);
		if ($int_defn_length > $sys['int_max_char_defn'] )
		{
			$arA[$k]['defn'] = $oFunc->mb_wordwrap_first($arA[$k]['defn'], $sys['int_max_char_defn'], $sys['txt_magic_splitter'], 0);
#			$arA[$k]['defn'] = $arA[$k]['defn'];
			$int_s = mb_strpos($arA[$k]['defn'], $sys['txt_magic_splitter']);
			$arA[$k]['kb'] = $oFunc->number_format($arV[1]['int_bytes'] / 1024, 1, $oL->languagelist(LOCALE_LANG_RULES)) .'&#160;'. $oL->m('kb');
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

/**
 * Collect preview values for translation field with language ids.
 *
 * @param array $preview_group
 *
 * @return array
 */
function gw_collect_trns_preview_values(array $preview_group)
{
    $preview_values = [];

    foreach ($preview_group as $preview_item) {
        $preview_value = isset($preview_item['value']) ? $preview_item['value'] : '';
        $lang_id = isset($preview_item['attributes']['lang']) ? $preview_item['attributes']['lang'] : '';

        if ($preview_value == '' || $preview_value == '<![CDATA[]]>') {
            continue;
        }

        if ($lang_id !== '') {
            $preview_values[] = $lang_id . '  ' . $preview_value;
        } else {
            $preview_values[] = $preview_value;
        }
    }

    return $preview_values;
}



/**
 * Collect values from a nested preview group.
 *
 * @param array $preview_group
 * @param bool $skip_empty_values
 *
 * @return array
 */
function gw_collect_nested_preview_values(array $preview_group, $skip_empty_values = false)
{
    $preview_values = [];

    foreach ($preview_group as $preview_item) {
        $preview_value = isset($preview_item['value']) ? $preview_item['value'] : '';

        if ($skip_empty_values && ($preview_value == '' || $preview_value == '<![CDATA[]]>')) {
            continue;
        }

        $preview_values[] = $preview_value;
    }

    return $preview_values;
}


/**
 * Build preview text for abbreviation-like fields.
 *
 * @param array $preview_values
 * @param array $ar_theme
 *
 * @return string
 */
function gw_build_abbr_preview_text(array $preview_values, array $ar_theme)
{
    if (empty($preview_values)) {
        return '';
    }

    return $ar_theme['prepend_abbr_preview']
        . gw_text_parse_preview(
            implode($ar_theme['split_abbr_preview'], $preview_values)
        )
        . $ar_theme['append_abbr_preview'];
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
 * Render top list block.
 *
 * Supported modes depend on the included file:
 * top.<mode>.inc.php
 *
 * @param string $mode Display mode identifier.
 * @param int    $amount Number of items to show.
 * @param int    $isItemOnly Show terms only, without dates and dictionary links.
 * @param int    $order Sorting order.
 * @param int    $top10Display Display mode:
 *                             0 - disabled,
 *                             1 - table,
 *                             >1 - inline preview list.
 * @return string Rendered HTML.
 */
function gw_get_top10($mode, $amount = 10, $isItemOnly = 0, $order = 0, $top10Display = 1)
{
    global $sys, $gw_this, $ar_theme, $arDictParam;
    global $oL, $oHtml, $oDb, $oSqlQ, $oFunc, $oSess;

    if (!$top10Display) {
        return '{v:}';
    }

    $str = '';
    $strFoot = '';
    $strHead = '';
    $strData = '';
    $strTopicName = '';
    $cnt = 0;

    /* Current date in UNIX timestamp */
    $curDateMk = $sys['time_now_gmt_unix'];

    $arThText = [];
    $arThWidth = [];
    $arTop10List = [];

    $mode = strtolower($mode);
    $filename = $sys['path_include'] . '/top.' . $mode . '.inc.php';

    if (file_exists($filename)) {
        include_once($filename);
    }
    else {
        $str .= "\n" . '<!-- Missing file: ' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . ' -->' . "\n";
    }

    /* Create inline list */
    if ($top10Display > 1) {
        $str .= '<h4>' . $strTopicName . '</h4><div class="termpreview">';
        $str .= implode(', ', $arTop10List) . '</div>';

        return $str;
    }

    /* Create table */
    $intRows = sizeof($arThWidth);

    if (!empty($arThText) || !empty($arThWidth)) {
        if (GW_IS_BROWSE_ADMIN) {
            $str .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
        }
        else {
            $str .= '<table style="background:' . $ar_theme['color_1'] . '" width="100%" border="0" cellpadding="3" cellspacing="1">';
        }

        if ($strTopicName != '') {
            $strHead .= '<tr>'
                . '<td style="text-align:' . $sys['css_align_left'] . '" colspan="' . ($intRows + 1) . '">'
                . '<h4>' . $strTopicName . '</h4></td>'
                . '</tr>';
        }

        if (!empty($arThText)) {
            $strHead .= '<tr>';
            $strHead .= '<th class="gw" style="text-align:center;width:1%">N</th>';

            foreach ($arThText as $keyTh => $valueTh) {
                $strWidth = '';

                if (isset($arThWidth[$keyTh]) && $arThWidth[$keyTh]) {
                    $strWidth = ' style="width:' . $arThWidth[$keyTh] . '"';
                }

                $strHead .= '<th class="gw"' . $strWidth . '>' . $valueTh . '</th>';
            }

            $strHead .= '</tr>';
        }
        else {
            if (!empty($arThWidth)) {
                $strFoot .= '<tr>';
                $strFoot .= '<th style="font-size:1px;height:1px;width:1%"></th>';

                foreach ($arThWidth as $keyTh => $valueTh) {
                    $strWidth = '';

                    if (isset($arThWidth[$keyTh]) && ($arThWidth[$keyTh] != '')) {
                        $strWidth = ' style="font-size:1px;height:1px;width:' . $arThWidth[$keyTh] . '"';
                    }

                    $strFoot .= '<th' . $strWidth . '></th>';
                }

                $strFoot .= '</tr>';
            }
        }

        if ($strHead != '') {
            $str .= '<thead>' . $strHead . '</thead>';
        }

        if ($strFoot != '') {
            $str .= '<tfoot>' . $strFoot . '</tfoot>';
        }

        $str .= '<tbody>';
        $str .= $strData;
        $str .= '</tbody>';
        $str .= '</table>';
    }

    return $str;
}

/**
 * Returns list of valid dictionary IDs (cached).
 *
 * @return array
 */
function get_valid_dict_ids()
{
    global $oSqlQ, $oDb;

    return $oDb->sqlRun($oSqlQ->getQ('get-dict-valid'), 'dict');
}

/**
 * Returns dictionary statistics.
 *
 * Includes total number of terms and current timestamp.
 *
 * @return array ['num' => int, 'sum' => int, 'date' => int]
 */
function gw_get_dict_stats()
{
    global $oSqlQ, $oDb, $sys;

    $rows = $oDb->sqlRun($oSqlQ->getQ('get-terms-total'), 'stat');

    $stats = isset($rows[0])
        ? $rows[0]
        : ['num' => 0, 'sum' => 0];

    $stats['date'] = $sys['time_now_gmt_unix'];

    return $stats;
}

/**
 * Builds HTML code for page navigation.
 *
 * Example: [ Pages: 1 .. 5 6 7 .. 11 ]
 * 12 Mar 2008: old pagination code was fully replaced.
 *
 * @param int $pageTotal Total number of pages
 * @param int $pageCurrent Current page number
 * @param string $url URL pattern without page number suffix
 * @return string HTML code ready to use in layout
 */
function gw_get_pagination($pageTotal, $pageCurrent = 1, $url)
{
    global $sys, $oHtml, $ar_theme;

    if ($pageTotal == 1 || $pageTotal == 0) {
        return '&#160;';
    }

    $pageCurrent = (int)$pageCurrent;
    $pageTotal = (int)$pageTotal;

    if ($pageCurrent < 1) {
        $pageCurrent = 1;
    }
    if ($pageCurrent > $pageTotal) {
        $pageCurrent = $pageTotal;
    }

    $pages = [];

    // HTML tag used to highlight current page
    $tagName = 'strong';

    // Language strings
    $pagePrevText = '&lt;&lt;&#160;' . $GLOBALS['oL']->m('1_prevpage');
    $pageNextText = $GLOBALS['oL']->m('1_nextpage') . '&#160;&gt;&gt;';

    // String placed between distant page numbers
    $moreText = '..';

    // String used to separate page numbers
    $separator = isset($ar_theme['split_pagenumbers']) ? $ar_theme['split_pagenumbers'] : ' | ';

    // URL pattern for paging
    $pageUrl = str_replace('%', '%%', $url) . '%d';

    // Number of page links shown before and after current page: 1 2 (3) 1 2
    $maxLinks = $sys['max_page_links'];

    // Visible page range
    $maxPage = $pageCurrent + $maxLinks;
    $minPage = $pageCurrent - $maxLinks;

    // Fix maximum page number
    if ($maxPage > $pageTotal) {
        $maxPage = $pageTotal;
    }

    // Links to previous page
    if ($pageCurrent > 1) {
        $pages[] = $oHtml->a(sprintf($pageUrl, $pageCurrent - 1), $pagePrevText);
    }

    // First page
    if ($minPage > 1) {
        $pages[] = $oHtml->a(sprintf($pageUrl, 1), 1);

        // Do not show ".." for "1 | 2 | 3"
        if (($pageCurrent - $maxLinks) != 0) {
            $pages[] = $moreText;
        }
    }

    // Page number links
    for ($pageNumber = 1; $pageNumber <= $pageTotal; $pageNumber++) {
        if ($pageNumber >= $minPage && $pageNumber <= $maxPage) {
            if ($pageNumber == $pageCurrent) {
                $pages[] = $oHtml->a(
                    sprintf($pageUrl, $pageNumber),
                    '<' . $tagName . ' class="on">' . $pageNumber . '</' . $tagName . '>'
                );
            } else {
                $pages[] = $oHtml->a(sprintf($pageUrl, $pageNumber), $pageNumber);
            }
        }
    }

    // Last page
    if ($maxPage > 1 && ($pageCurrent + $maxLinks) < $pageTotal) {
        // Do not show ".." for "25 | 26 | 27"
        if (($pageCurrent + $maxLinks + 1) < $pageTotal) {
            $pages[] = $moreText;
        }

        $pages[] = $oHtml->a(sprintf($pageUrl, $pageTotal), $pageTotal);
    }

    // Links to next page
    if ($pageCurrent < $pageTotal) {
        $pages[] = $oHtml->a(sprintf($pageUrl, $pageCurrent + 1), $pageNextText);
    }

    return implode($separator, $pages);
}

/**
 * Returns dictionary parameters such as title, description,
 * number of terms and SQL table name.
 *
 * @param int|string $dictId Dictionary ID, title or URI depending on link mode
 * @return array|false Dictionary parameters or FALSE if not found
 */
function gw_get_dict_param($dictId)
{
    global $gw_this, $sys, $oDb, $oSqlQ;

    $dictParams = [];

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
            $compareTo = 'title';

            if (is_numeric($dictId)) {
                $compareTo = 'id';
            }
            break;

        case GW_PAGE_LINK_URI:
            $compareTo = 'dict_uri';
            break;

        default:
            $compareTo = 'id';
            break;
    }

    if (!is_array($gw_this['ar_dict_list'])) {
        return [];
    }

    if (GW_IS_BROWSE_ADMIN || $gw_this['vars']['a'] == GW_A_SEARCH) {
        $compareTo = 'id';
    }

    // For each dictionary
    foreach ($gw_this['ar_dict_list'] as $dictKey => $dictItem) {
        if ($dictItem[$compareTo] == $dictId) {
            $dictItem['dict_settings'] = unserialize($dictItem['dict_settings']);

            if (is_array($dictItem['dict_settings'])) {
                // Merge dictionary settings into one array
                $dictItem = array_merge($dictItem, $dictItem['dict_settings']);
            }

            unset($dictItem['dict_settings']);

            // Add dictionary URI
            switch ($sys['pages_link_mode']) {
                case GW_PAGE_LINK_NAME:
                    $dictItem['uri'] = urlencode($dictItem['title']);
                    break;

                case GW_PAGE_LINK_URI:
                    $dictItem['uri'] = urlencode($dictItem['dict_uri']);
                    break;

                default:
                    $dictItem['uri'] = $dictId;
                    break;
            }

            return $dictItem;
        }
    }

    // No such dictionary
    return false;
}

/**
 * Get a random term from a random dictionary
 * 
 * @return  array   array with term and dictionary
 */
function get_get_term_random()
{
	global $gw_this, $oDb, $oSqlQ;
	$arDictParam = $gw_this['ar_dict_list'][mt_rand(0, sizeof($gw_this['ar_dict_list'])-1)];
	$sql = $oSqlQ->getQ('get-term-rand', $arDictParam['tablename']);
	$arSql = $oDb->sqlExec($sql);
	$arSql = isset($arSql[0]) ? $arSql[0] : array();
	$arSql = array_merge($arDictParam, $arSql);
	return $arSql;
}

/**
 * Returns default term structure.
 *
 * @return array
 */
function gw_get_term_param_default()
{
    return [
        'is_active' => '0',
        'is_complete' => '0',
        'term' => '',
        'term_uri' => '',
        'term_1' => ' ',
        'term_2' => ' ',
        'term_3' => ' ',
        'defn' => '',
        'tid' => '',
        'term_order' => '',
        'date_created' => 0,
        'date_modified' => 0
    ];
}

/**
 * Returns field name used as term URI in current link mode.
 *
 * @return string
 */
function gw_get_term_uri_field()
{
    global $sys;

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
            return 'term';

        case GW_PAGE_LINK_URI:
            return 'term_uri';

        default:
            return 'tid';
    }
}

/**
 * Builds SQL query to find term by ID, name or URI depending on link mode.
 *
 * @param string|int $termId
 * @return string
 */
function gw_get_term_sql_by_id($termId)
{
    global $gw_this, $arDictParam, $oSqlQ, $sys;

    if (GW_IS_BROWSE_ADMIN) {
        return $oSqlQ->getQ('get-term-by-id-adm', $arDictParam['tablename'], $termId);
    }

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
        case GW_PAGE_LINK_URI:
            $sql = $oSqlQ->getQ(
                'get-term-by-term',
                $arDictParam['tablename'],
                gw_text_sql($termId),
                gw_text_sql($termId),
                $sys['time_now_db']
            );

            // Switch to term ID lookup when possible (faster)
            if (is_numeric($termId) || $gw_this['vars']['a'] == GW_A_CUSTOMPAGE) {
                $sql = $oSqlQ->getQ(
                    'get-term-by-id',
                    $arDictParam['tablename'],
                    gw_text_sql($termId),
                    $sys['time_now_db']
                );
            }

            return $sql;

        default:
            return $oSqlQ->getQ(
                'get-term-by-id',
                $arDictParam['tablename'],
                gw_text_sql($termId),
                $sys['time_now_db']
            );
    }
}

/**
 * Finds term by ID, term text or term URI depending on current mode.
 *
 * @param string|int $termId
 * @return array
 */
function gw_get_term_by_id($termId)
{
    global $gw_this, $oDb;

    $default_term = gw_get_term_param_default();
    $sql = gw_get_term_sql_by_id($termId);

    $rows = $oDb->sqlExec($sql, sprintf('%05d', $gw_this['vars'][GW_ID_DICT]), 0);

    if (!isset($rows[0])) {
        return $default_term;
    }

    $result = $rows[0];

    // Temporary cleanup
    $result['defn'] = str_replace('<![CDATA[', '', $result['defn']);
    $result['defn'] = str_replace(']]>', '', $result['defn']);

    return $result;
}

/**
 * Redirect to canonical term URL when needed.
 *
 * @param string|int $requested_id
 * @param array $term
 * @param string $term_uri_field
 *
 * @return void
 */
function gw_redirect_canonical_term_url($requested_id, $term, $term_uri_field)
{
    global $gw_this, $arDictParam, $sys, $oHtml, $oUrlBuilder;

    if (!GW_IS_BROWSE_WEB) {
        return;
    }

    $is_redirect = 0;

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
            $is_redirect = ($requested_id != $term['term']);
            break;

        case GW_PAGE_LINK_URI:
            $is_redirect = ($term['term_uri'] && ($requested_id != $term['term_uri']));
            break;

        default:
            $is_redirect = ((int)$requested_id != (int)$term['tid']);
            break;
    }

    if ($is_redirect && ($gw_this['vars'][GW_ACTION] == GW_T_TERM) && !$gw_this['vars']['is_print']) {
        $href_term = $oUrlBuilder->build_index_url(GW_T_TERM, $term[$term_uri_field], [GW_ID_DICT => $arDictParam['uri']]);
        gwtk_header(
            $sys['server_proto'] . $sys['server_host'] . $oHtml->url_normalize($href_term),
            $sys['is_delay_redirect'],
            __FILE__,
            __LINE__
        );
    }
}

/**
 * Builds SQL query to search term by normalized name.
 *
 * @param string $wordSearchSql
 * @return string
 */
function getTermParamSqlByName($wordSearchSql)
{
    global $gw_this, $arDictParam, $oSqlQ;

    if (GW_IS_BROWSE_ADMIN) {
        return $oSqlQ->getQ(
            'get-term-by-name-adm',
            TBL_WORDLIST,
            TBL_WORDMAP,
            $arDictParam['tablename'],
            $gw_this['vars'][GW_ID_DICT],
            $wordSearchSql
        );
    }

    return $oSqlQ->getQ(
        'get-term-by-name',
        TBL_WORDLIST,
        TBL_WORDMAP,
        $arDictParam['tablename'],
        $gw_this['vars'][GW_ID_DICT],
        $wordSearchSql
    );
}

/**
 * Selects best matched term from search results.
 *
 * @param array $rows
 * @param string $name
 * @param array $keywordsTarget
 * @return array
 */
function findMatchedTermByName($rows, $name, $keywordsTarget)
{
    $found = [];

    foreach ($rows as $row) {
        $isTermExist = 0;

        // First method, 08 Jul 2000
        if (!$isTermExist && $row['term'] == $name) {
            $isTermExist = 1;
            $found = $row;
            break; // usually stops on first loop
        }

        $keywordsQuery = text2keywords(text_normalize($row['term']), 1); // 1 = minimum length
        $div1 = count(gw_array_exclude($keywordsTarget, $keywordsQuery));
        $div2 = count(gw_array_exclude($keywordsQuery, $keywordsTarget));
        $isTermNotMatched = ($div1 + $div2);

        // If sum of excluded arrays is 0, the term already exists
        if (!$isTermNotMatched) { // double negative, yes
            $isTermExist = 1;
        }

        if ($isTermExist) {
            $found = $row;
        }
    }

    return $found;
}

/**
 * Search term by name.
 *
 * @param string $name
 * @return array
 */
function gw_get_term_by_name($name)
{
    global $gw_this, $oDb;

    // Normalize input and strip special characters.
    // This is legacy behavior used for keyword-based matching.
    $keywordsTarget = text2keywords(text_normalize(stripslashes($name)), 1);
    $wordSearchSql = "'" . implode("', '", $keywordsTarget) . "'";

    $sql = getTermParamSqlByName($wordSearchSql);
    $rows = $oDb->sqlExec($sql, sprintf('%05d', $gw_this['vars'][GW_ID_DICT]), 0);

    return findMatchedTermByName($rows, $name, $keywordsTarget);
}

/**
 * Adds public URI field to found term.
 *
 * @param array $term
 * @param string|int $termId
 * @return array
 */
function gw_apply_uri_for_term_param($term, $termId)
{
    global $sys;

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
            $term['uri'] = urlencode($term['term']);
            break;

        case GW_PAGE_LINK_URI:
            $term['uri'] = urlencode($term['term_uri']);
            break;

        default:
            $term['uri'] = $termId;
            break;
    }

    return $term;
}

/**
 * Returns term parameters by term ID or by term name.
 *
 * @param int|string $tid Term ID, term text or URI depending on current mode
 * @param string $name Term name
 * @return array Term parameters
 */
function getTermParam($tid = '', $name = '')
{
    $result = gw_get_term_param_default();
    $field_name  = gw_get_term_uri_field();

    if ($tid) {
        $result = gw_get_term_by_id($tid);
        gw_redirect_canonical_term_url($tid, $result, $field_name);
    } elseif ($name !== '') {
        $result_by_name = gw_get_term_by_name($name);

        if (!empty($result_by_name)) {
            $result = $result_by_name;
        }
    }

    $result = gw_apply_uri_for_term_param($result, $tid);

    if (empty($result)) {
        $result = gw_get_term_param_default();
    }

    return $result;
}

/**
 * Get term parameters by term ID or by term name.
 *
 * @param    int     term ID
 * @param    string  term name
 * @return   array   term id, defn id, name, definition(s), synonym(s)
 */
function getTermParam2($tid = '', $name = '')
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
        /* @TODO gw_sql_unescape_like */
		$arKeywordsT = text2keywords( text_normalize( stripslashes($name) ), 1);
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
 * Gets first letters from all terms in dictionary.
 *
 *  Example (urlencoded):
 *  S%CC%8C  = "S" + combining caron
 *  %C5%A0   = precomposed "Š"
  *
 * @param int $id_dict Dictionary ID
 * @param string $w Second symbol (optional, not used since 1.3)
 * @return array Initial letters (00-ZZ structure)
 */
function getLettersArray($id_dict, $w = '')
{
    global $oDb, $oSqlQ, $oFunc;
    global $arDictParam, $gw_this, $sys;

    $az_sql = '';

    if ($arDictParam['az_order']) {
        $az_sql = 'FIELD(t.term_a, ' . $arDictParam['az_order'] .
            '), FIELD(t.term_b, ' . $arDictParam['az_order'] .
            '), FIELD(t.term_c, ' . $arDictParam['az_order'] . '), ';
    }

    // Since 1.8.4: cannot be cached because of delayed postings
    $arSql = $oDb->sqlExec(
        $oSqlQ->getQ('get-az', $arDictParam['tablename'], $sys['time_now_db'], $az_sql)
    );

    // One array for both indexes (single and double)
    $arA = [];
    $sys['ar_az_last_characters'] = [];

    foreach ($arSql as $k => $v) {

        /**
         *  Must not be reduced to mb_substr($v['L1'], 0, 1).
         *  Some letters with diacritics may be stored either as a single precomposed
         *  Unicode character or as a base letter followed by combining marks.
         *  Using a longer slice preserves this distinction for alphabetic sorting.
         *
         * 05 Jul 2005: varchar(0, 64) allows using toolbar as a list of topics.
         * 24 Jul 2006: toolbar limits removed for better performance.
         * 16 May 2007: 3rd toolbar level added.
         * 27 Nov 2007: only 1 letter allowed, varchar(0, 4) - max UTF-8 char length (4 bytes).
         */

        /*
        if (!$v['int_sort'])
        {
            $sys['ar_az_last_characters'][$v['L1']] = '';
        }
        */

        if ($gw_this['vars']['a'] == GW_T_TERM
            || ($gw_this['vars']['w1'] != '' && $gw_this['vars']['w2'] != '')
        ) {
            // w1, w2, w3 OR w1, w2 selected
            if ($gw_this['vars']['a'] == GW_T_TERM
                || ($gw_this['vars']['w1'] == $v['L1'] && $gw_this['vars']['w2'] == $v['L2'])
            ) {
                // Show w3 only for selected w2
                $arA[$v['L1']][$v['L2']][$v['L3']] = '';
            } else {
                $arA[$v['L1']][$v['L2']] = '';
            }
        } elseif ($gw_this['vars']['w1'] != '') {
            // w1 selected

            // Show w2 only for selected w1
            if ($gw_this['vars']['w1'] == $v['L1']) {
                $arA[$v['L1']][$v['L2']] = '';
            } else {
                $arA[$v['L1']] = '';
            }
        } else {
            // Nothing selected
            $arA[$v['L1']] = '';
        }

        unset($arSql[$k]); // keep legacy behavior
    }

    return $arA;
}

/**
 * Builds HTML for alphabetic index links.
 *
 * Supports 1-, 2- and 3-level alphabetic navigation.
 *
 * @param array $letters Letters tree
 * @param string $dictId Dictionary ID, used in links only
 * @param string $w1 Alphabetic level 1
 * @param string $w2 Alphabetic level 2
 * @param string $w3 Alphabetic level 3
 * @return string HTML code
 */
function getLetterHtml($letters, $dictId, $w1 = '', $w2 = '', $w3 = '')
{
    global $oFunc, $oHtml, $sys, $arDictParam;

    // Basic Multilingual Plane:
    // http://www.unicode.org/roadmaps/bmp/
    // Split alphabetic toolbar by character block
    $unicodeMap = [
        ['20', 'Basic Latin Digits'],
        ['41', 'Basic Latin'],
        ['c280', 'Latin Extended'],
        ['c990', 'IPA Extensions'],
        ['cab0', 'Spacing Modifiers'],
        ['cc80', 'Combining Diacritics'],
        ['cdb0', 'Greek'],
        ['d080', 'Cyrillic, Cyrillic Supplement'],
        ['d4b0', 'Armenian']
    ];

    $letterCount = 0;
    $sections = [];

    $lastToolbarBlock = end($unicodeMap);
    $lastToolbarHex = hexdec($lastToolbarBlock[0]);

    // Letter link template
    $linkData = [];
    $linkData['href'][GW_ACTION] = GW_A_LIST;
    $linkData['href'][GW_TARGET] = GW_T_DICT;
    $linkData['href'][GW_ID_DICT] = $dictId;

    foreach ($letters as $key1 => $value1) {
        $letterCount++;
        $titleCount = (isset($sys['is_print_toolbar_num']) && $sys['is_print_toolbar_num'] == 1) ? $letterCount : '';

        // 0-Z
        if ($w1 != '' && $w2 == '') {
            $oHtml->setTag('a', 'title', $titleCount);
            $linkData['href']['w1'] = urlencode($key1);

            // Current letter
            $letterHex = (ord($key1) >= 127) ? $oFunc->text_bytes_to_hex($key1, 0) : dechex(ord($key1));

            if ($arDictParam['id_custom_az'] == 1) {
                foreach ($unicodeMap as $key2 => $value2) {
                    // Next block start
                    if (isset($unicodeMap[$key2 + 1])) {
                        $toolbarHexTo = $unicodeMap[$key2 + 1][0];
                    } else {
                        $toolbarHexTo = $lastToolbarHex;
                    }

                    // Current block start
                    $toolbarHexFrom = $value2[0];

                    // Current letter belongs to this block
                    if ($letterHex >= $toolbarHexFrom && $letterHex < $toolbarHexTo) {
                        $oHtml->setTag('a', 'class', '');

                        if ((string)$key1 == trim($w1)) {
                            $oHtml->setTag('a', 'class', 'on');
                        }

                        $sections[$key2][] = $oHtml->a(
                            $sys['page_index'] . '?' . $oHtml->paramValue($linkData['href'], '&', ''),
                            $key1
                        );
                    }
                }
            }

            // 1.8.6-dev: custom alphabetic order
            if ($arDictParam['id_custom_az'] > 1) {
                $oHtml->setTag('a', 'class', '');

                if ((string)$key1 == trim($w1)) {
                    $oHtml->setTag('a', 'class', 'on');
                }

                if (isset($sys['ar_az_last_characters'][$key1])) {
                    $sections[0][] = $oHtml->a(
                        $sys['page_index'] . '?' . $oHtml->paramValue($linkData['href'], '&', ''),
                        $key1
                    );
                } else {
                    $sections[1][] = $oHtml->a(
                        $sys['page_index'] . '?' . $oHtml->paramValue($linkData['href'], '&', ''),
                        $key1
                    );
                }
            }

            $oHtml->setTag('a', 'title', '');
        } elseif ($w3 != '') {
            // 000-ZZZ
            if ((string)$key1 == trim($w1)) {
                foreach ($value1 as $key2 => $value2) {
                    if (empty($value1[trim($w2)])) {
                        continue;
                    }
                    if ((string)$key2 != trim($w2)) {
                        continue; // Fix for getLettersArray()
                    }

                    foreach ($value2 as $key3 => $value3) {
                        $oHtml->setTag('a', 'class', '');

                        if ((string)$key3 == trim($w3)) {
                            $oHtml->setTag('a', 'class', 'on');
                        }

                        $linkData['href']['w1'] = urlencode($key1);
                        $linkData['href']['w2'] = urlencode($key2);
                        $linkData['href']['w3'] = urlencode($key3);

                        $sections['0z'][] = $oHtml->a(
                            $sys['page_index'] . '?' . $oHtml->paramValue($linkData['href'], '&', ''),
                            $key1 . $key2 . $key3
                        );
                    }
                }
            }
        } else {
            // 00-ZZ
            if ((string)$key1 == trim($w1)) {
                if (!is_array($value1)) {
                    continue;
                }

                foreach ($value1 as $key2 => $value2) {
                    $oHtml->setTag('a', 'class', '');

                    if ((string)$key2 == trim($w2)) {
                        $oHtml->setTag('a', 'class', 'on');
                    }

                    $linkData['href']['w1'] = urlencode($key1);
                    $linkData['href']['w2'] = urlencode($key2);

                    $sections['0z'][] = $oHtml->a(
                        $sys['page_index'] . '?' . $oHtml->paramValue($linkData['href'], '&', ''),
                        $key1 . $key2
                    );
                }
            }
        }
    }

    unset($letters);

    $oHtml->setTag('a', 'title', '');
    $oHtml->setTag('a', 'class', '');

    // Build HTML code
    $html = '';
    ksort($sections);

    foreach ($sections as $key1 => $value1) {
        if (is_array($value1)) {
            $html .= implode(' ', $value1);
            $html .= '<br />';
        }
    }

    return $html;
}
## Toolbar functions A-Z, 00-ZZ
## --------------------------------------------------------


/**
 * Parses XML data and converts it into structured array.
 *
 * @param string $xmlString XML code (from database)
 * @return array Parsed field structure
 */
function gw_Xml2Array($xmlString)
{
    global $arFields;

    $xmlRoot = [];
    $xmlTags = [];
    $xmlAttr = ['link', 'lang', 'text', 'size']; // possible attributes

    // Fix empty definitions
    $xmlString = str_replace('<defn><![CDATA[]]></defn>', '', $xmlString);

    // Get defined tags
    foreach ($arFields as $fieldKey => $fieldValue) {
        $fieldName = 'is_' . $fieldValue[0];

        if (isset($fieldValue[4]) && $fieldValue[4]) { // root element
            $xmlRoot[] = $fieldValue[0];
        } else { // non-root element
            $xmlTags[] = $fieldValue[0];
        }
    }

    // Process each root element
    foreach ($xmlRoot as $rootKey => $rootTag) {
        preg_match_all("/<$rootTag>(.+?)<\/$rootTag>/s", $xmlString, $rootMatches); // root tags without attributes

        if (!isset($rootMatches[0]) || !isset($rootMatches[0][0]) || empty($rootMatches[0][0])) {
            continue;
        }

        // Number of root elements found
        $rootCount = count($rootMatches[0]);

        // Process each <defn> / root entry
        for ($rootIndex = 0; $rootIndex < $rootCount; $rootIndex++) {
            // 10 Mar 2003: based on "value"
            $parsedAr[$rootTag][$rootIndex]['value'] = $rootMatches[1][$rootIndex];

            // Search nested tags and their attributes
            foreach ($xmlTags as $tagKey => $tagName) {
                preg_match_all("/<$tagName(.*?)\>(.*?)\<\/$tagName\>/s", $rootMatches[1][$rootIndex], $tagMatches);

                if (!isset($tagMatches[0]) || empty($tagMatches[0])) {
                    continue;
                }

                $tagCount = count($tagMatches[0]);

                for ($tagIndex = 0; $tagIndex < $tagCount; $tagIndex++) { // for each matched tag
                    $parsedAr[$tagName][$rootIndex][$tagIndex]['value'] = $tagMatches[2][$tagIndex];

                    $parsedAr[$rootTag][$rootIndex]['value'] = trim(
                        str_replace($tagMatches[0][$tagIndex], '', $parsedAr[$rootTag][$rootIndex]['value'])
                    );

                    if (!isset($tagMatches[1][0]) || empty($tagMatches[1][0])) {
                        continue;
                    }

                    // 22 Jan 2006: read any attribute for any tag
                    foreach ($xmlAttr as $attrKey => $attrName) {
                        preg_match_all("/$attrName=\"(.*?)\"/", $tagMatches[1][$tagIndex], $attrMatches);

                        if (!isset($attrMatches[1][0]) || empty($attrMatches[1][0])) {
                            continue;
                        }

                        if ($attrName == 'link' && $attrMatches[1][0] != '') {
                            $parsedAr[$tagName][$rootIndex][$tagIndex]['attributes']['is_link'] = 1;
                        }

                        $parsedAr[$tagName][$rootIndex][$tagIndex]['attributes'][$attrName] = $attrMatches[1][0];
                    }
                }
            } // end of $xmlTags
        } // end of root entries loop
    } // end of $xmlRoot

    if (!isset($parsedAr)) {
        $parsedAr['defn'][0] = '';
    }

    return $parsedAr;
}

/* end of file */
