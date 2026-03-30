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
 *  Functions for administrative interface.
 */

/**
 * Build dictionary selector item HTML.
 *
 * @param array $ar_dict_param Dictionary parameters.
 * @return string
 */
function gw_dict_browse_for_select(array $ar_dict_param)
{
    global $gw_this, $oFunc, $oHtml, $oL, $oUrlBuilder;

    $dict_id = isset($ar_dict_param['id']) ? (int)$ar_dict_param['id'] : 0;
    $dict_title = isset($ar_dict_param['title']) ? (string)$ar_dict_param['title'] : '';
    $terms_amount = isset($ar_dict_param['int_terms']) ? (int)$ar_dict_param['int_terms'] : 0;

    return '<div class="xw">'
        . $oHtml->a(
            $oUrlBuilder->build_admin_url(
                $gw_this['vars'][GW_ACTION],
                $gw_this['vars'][GW_TARGET],
                ['id' => $dict_id]
            ),
            $dict_title
        )
        . '</div>'
        . $oL->m(1364) . ': <strong>' . $dict_id . '</strong> &#8226; '
        . $oL->m('termsamount') . ': <strong>'
        . $oFunc->number_format($terms_amount, 0, $oL->languagelist('4'))
        . '</strong>';
}

/**
 * Recounts the number of dictionaries in each topic.
 */
function gw_topic_recount()
{
    global $sys;
    include_once( $sys['path_include'] . '/class.topics_recounter.php' );
	$o = new gw_topics_recounter();
	$o->recount();
}

/* */
function gw_after_redirect_url($action, $id_term = 0)
{
	global $arTermParam, $arDictParam, $oSess, $oHtml, $oDb, $oL, $sys;
	$str_url = '';
	switch ($action)
	{
		case GW_AFTER_DICT_UPDATE:
			/* Redirect to "Editing dictionary settings" page */
			$str_url = GW_ACTION.'='.GW_A_EDIT .'&'. GW_TARGET.'='.GW_T_DICTS . '&id='.$arDictParam['id']. '&tid='.$arDictParam['id'];
		break;
		case GW_AFTER_SRCH_BACK:
			/* Search again */
			if ($oSess->user_get('q'))
			{
				$str_url = GW_ACTION . '=' . GW_A_SEARCH .
							'&q=' . $oSess->user_get('q') .
							'&srch[in]=' . $oSess->user_get('in') .
							'&srch[adv]=' . $oSess->user_get('srch_adv') .
							'&srch[by]=' . $oSess->user_get('srch_by') .
							'&d=' . $arDictParam['id'];
			}
		break;
		case GW_AFTER_TERM_ADD:
			/* Redirect to "Add a term" */
			$str_url = GW_ACTION.'='.GW_A_ADD . '&' .GW_TARGET.'='.GW_T_TERMS . '&id='.$arDictParam['id'];
		break;
		case GW_AFTER_TERM_GW_A_IMPORT:
			/* Import terms page */
			$str_url = GW_ACTION.'='.GW_A_IMPORT. '&' .GW_TARGET.'='.GW_T_TERMS .'&id='.$arDictParam['id'];
		break;
	}
	/* Add link to a term */
	if ($id_term && $arDictParam['id'])
	{
		/* on SEF enabled */
		switch ($sys['pages_link_mode'])
		{
			case GW_PAGE_LINK_NAME:
				$arTermParam['uri'] = urlencode(gw_fix_input_to_db($arTermParam['term']));
			break;
			case GW_PAGE_LINK_URI:
				$arTermParam['uri'] = urlencode($arTermParam['term_uri']);
			break;
			default:
				$arTermParam['uri'] = $id_term;
			break;
		}
		$str_url .= '&note_afterpost='.
				urlencode(strip_tags($arTermParam['term']).': <a href="'.$oHtml->url_normalize($sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT.'&d='.$arDictParam['id'].'&'.GW_TARGET.'='.GW_T_TERMS.'&tid='.$id_term).'">'.$oL->m('3_edit').'</a>');
		if ( $arDictParam['is_active'] == 1 ) {
			$str_url .= '  - '.urlencode('<a class="ext" href="'.$oHtml->url_normalize($sys['page_index'].'?'.GW_ACTION.'='.GW_T_TERM.'&d='.$arDictParam['uri'].'&'.GW_TARGET.'='.$arTermParam['uri']).'" onclick="window.open(this.href);return false">'.$oL->m('1283').'</a>');
		}
	}
#	prn_r( $str_url );
#	exit;
	return $str_url;
}


/**
 * Return validated dictionary table name.
 *
 * @return string
 */
function gw_sys_dict_get_table_name()
{
    global $arDictParam;

    $table_name = isset($arDictParam['tablename']) ? (string)$arDictParam['tablename'] : '';
    if ($table_name === '') {
        return '';
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
        return '';
    }

    return $table_name;
}

/**
 * Read dictionary counters in a single query.
 *
 * Returns:
 * - int_terms: published terms count
 * - int_terms_total: total terms count except deleted
 * - int_bytes: total bytes
 *
 * @param bool $is_reset Reset static cache for the current dictionary.
 * @return array
 */
function gw_sys_dict_get_stats($is_reset = false)
{
    global $arDictParam, $oDb, $sys;

    static $cache = [];

    $table_name = gw_sys_dict_get_table_name();
    if ($table_name === '') {
        return [
            'int_terms'       => 0,
            'int_terms_total' => 0,
            'int_bytes'       => 0,
        ];
    }

    $dict_id = isset($arDictParam['id']) ? (int)$arDictParam['id'] : 0;
    $time_now_gmt_unix = isset($sys['time_now_gmt_unix']) ? (int)$sys['time_now_gmt_unix'] : 0;

    $cache_key = $dict_id . ':' . $table_name . ':' . $time_now_gmt_unix;

    if ($is_reset) {
        unset($cache[$cache_key]);
    }

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $sql = 'SELECT
                SUM(CASE WHEN is_active = 1 AND date_created <= ' . $time_now_gmt_unix . ' THEN 1 ELSE 0 END) AS int_terms,
                SUM(CASE WHEN is_active != 3 AND date_created <= ' . $time_now_gmt_unix . ' THEN 1 ELSE 0 END) AS int_terms_total,
                COALESCE(SUM(int_bytes), 0) AS int_bytes
            FROM `' . $table_name . '`';

    $ar_sql = $oDb->sqlExec($sql);
    $ar_row = isset($ar_sql[0]) ? $ar_sql[0] : [];

    $cache[$cache_key] = [
        'int_terms'       => isset($ar_row['int_terms']) ? (int)$ar_row['int_terms'] : 0,
        'int_terms_total' => isset($ar_row['int_terms_total']) ? (int)$ar_row['int_terms_total'] : 0,
        'int_bytes'       => isset($ar_row['int_bytes']) ? (int)$ar_row['int_bytes'] : 0,
    ];

    return $cache[$cache_key];
}

/**
 * Count the number of published terms.
 *
 * @return int
 */
function gw_sys_dict_count_terms()
{
    $ar_stats = gw_sys_dict_get_stats();

    return $ar_stats['int_terms'];
}

/**
 * Count the total number of terms except deleted ones.
 *
 * @return int
 */
function gw_sys_dict_count_terms_total()
{
    $ar_stats = gw_sys_dict_get_stats();

    return $ar_stats['int_terms_total'];
}

/**
 * Count total bytes for all terms.
 *
 * @return int
 */
function gw_sys_dict_count_bytes()
{
    $ar_stats = gw_sys_dict_get_stats();

    return $ar_stats['int_bytes'];
}

/**
 * Update cached dictionary counters.
 *
 * @return void
 */
function gw_sys_dict_update()
{
    global $arDictParam, $oDb;

    $dict_id = isset($arDictParam['id']) ? (int)$arDictParam['id'] : 0;
    if ($dict_id <= 0) {
        return;
    }

    $q_dict = gw_sys_dict_get_stats();

    $arDictParam['int_terms'] = $q_dict['int_terms'];
    $arDictParam['int_terms_total'] = $q_dict['int_terms_total'];
    $arDictParam['int_bytes'] = $q_dict['int_bytes'];

    $sql = gw_sql_update(
        $q_dict,
        gw_get_tbl_name('dict'),
        '`id` = ' . $dict_id
    );

    $oDb->sqlExec($sql);
}

/**
 * Reset cached dictionary counters.
 *
 * @return void
 */
function gw_sys_dict_reset_stats_cache()
{
    gw_sys_dict_get_stats(true);
}

/**
 * Check and optimize a dictionary table.
 *
 * @param string $table Table name.
 * @return void
 */
function gw_sys_dict_check($table)
{
    global $oDb;

    $table = (string)$table;
    if ($table === '') {
        return;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return;
    }

    $table = '`' . str_replace('`', '``', $table) . '`';

    $oDb->sqlExec('CHECK TABLE ' . $table);
    $oDb->sqlExec('OPTIMIZE TABLE ' . $table);
}


/**
 * Builds an automatically generated navigation toolbar,
 * depends on currect action.
 *
 * @param   string  $a currect target
 * @param   string  $t currect action
 * @return  string  complete HTML-code
 * @globals  object  $oL Translation kit phrases
 */
function gw_admin_menu($a, $t)
{
	global $arDictParam, $sys, $gw_this, $ar_theme, $arPageNumbers;
	global $oL, $oSess, $oHtml, $oDb, $oSqlQ, $oUrlBuilder;

    $arMenu = gw_admin_get_menu_items();

	$gw_this['ar_actions_list'] = array();
	/* Javascript collapsible objects */
	$ar_js_ids = array();
	/* Add search form */
	if ($oSess->is('is-terms') || $oSess->is('is-terms-own'))
	{
		$ar_js_ids[] = 'search';
	}
	$int_menu_el = 1;
	/* */
	$str = '<table id="admmenu" class="admmenu" cellspacing="0" cellpadding="1" border="0" width="100%">';
	$str .= '<tbody>';

    foreach ($arMenu as $id_component => $arV)
	{
		/* for each component */
        $oL->applyCustomPhrases('addon_' . $id_component, $gw_this['vars'][GW_LANG_I] . '-' . $gw_this['vars']['lang_enc']);
		/* background color */
		$int_menu_el % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);

		$ar_js_ids[$int_menu_el] = str_replace('_', '-', $id_component);

        // Context action list
		$gw_this['ar_actions_list'][$id_component] = array();

		$str .= PHP_EOL.'<tr>';
		$str .= '<td onclick="return toggle_collapse(\''.$ar_js_ids[$int_menu_el].'\')" class="admcomponents" style="text-align:' . $sys['css_align_left'] . '">';
		$str .= '<img id="ci-'.$ar_js_ids[$int_menu_el].'" src="'.$sys['path_img'].'/collapse_on.png" alt="" width="9" height="21" />';
		$str .= $oL->m($arV[0]['cname']);
		$str .= '</td>';
		$str .= '</tr><tr><td id="co-'.$ar_js_ids[$int_menu_el].'" class="actions-primary" style="text-align:' . $sys['css_align_left'] . '">';

		/* for each component action */
        foreach ($arV as $k2 => $arV2) {
            /* Include links to actions for a primary menu */
            if ($arV2['is_in_menu'] == 1) {
                // Build link with an action name `aname`
                $hrefInMenu = $oUrlBuilder->build_admin_url($arV2['aname_sys'], $id_component);
                $str .= ' ' . $oHtml->a(
                        $hrefInMenu,
                        '<span>' . $arV2['icon'] . '</span>&#160;' . $oL->m($arV2['aname']),
                        $oL->m($arV2['aname'])
                    );
            }
            /* Include links to actions for a secondary menu */
            /* Do not include links to actions with is_in_menu = 0 */
            if ((int)$arV2['is_in_menu'] !== 0) {
                $link_params = [];

                if ((int)$gw_this['vars']['tid'] > 0 && ($gw_this['vars'][GW_TARGET] == $id_component)) {
                    $link_params[GW_TARGET_ID] = (int)$gw_this['vars'][GW_TARGET_ID];
                }

                $gw_this['ar_actions_list'][$id_component][$arV2['aname_sys']] = $oHtml->a(
                    $oUrlBuilder->build_admin_url(
                        $arV2['aname_sys'],
                        $arV2['id_component_name'],
                        $link_params
                    ),
                    $oL->m($arV2['aname']),
                    $oL->m($arV2['cname']) . ': ' . $oL->m($arV2['aname'])
                );
            }
        }
        $str .= '</td></tr>';
        $int_menu_el++;
    }
	$str .= '</tbody></table>';
	
	/* Restore path to localizaion files */
	$oL->setHomeDir($sys['path_locale']);
	/* Javascript */
	$str .= '<script type="text/javascript">/*<![CDATA[*/';
	$str .= 'var path_img = "'.$sys['path_img'].'/";';
	$str .= 'var ar_coll_obj = new Array(\''.implode('\',\'', $ar_js_ids).'\');';
	$str .= 'uncollapse_all(true);';
	$str .= '/*]]>*/</script>';
	return $str;
}

/**
 * Outputs all database queries in readable format
 *
 * @param array $arQuery
 * @return string debug information
 * @access private
 * @see htmlspecialchars2()
 */
function htmlspecialchars3($arQuery) {
    $arQuery = array_map('gw_htmlspecialchars_ltgt', $arQuery);
    $arQuery = array_map('gw_highlight_sql', $arQuery);

    return '<ul class="gwsql"><li>' . implode(';</li><li>', $arQuery) . ';</li></ul>';
}

/**
 * Post query to database
 *
 * @param    array  $arQuery   all database queries
 * @param    string $url       redirect to path if success
 * @param    bool   $debug     if true, display query and errors
 * @return   string html-code for redirect or an error
 * @access   public
 */
function postQuery($arQuery, $url = '', $isDebug = 0, $isPause = 1, $lock = '')
{
    global $oDb, $oSqlQ, $sys, $gw_this, $oSess, $oHtml;

    $isPostError = true;
    $strContinue = isset($GLOBALS['oL']) ? $GLOBALS['oL']->m('2_continue') : 'Continue';
    $urlTo = ($url == '') ? $sys['page_admin'] : $url;

    if ($isDebug) {
        return htmlspecialchars3($arQuery) . '<p>' . $oHtml->a($urlTo, $strContinue) . ' <span id="countdown"></span></p>';
    }

    ## ----------------------------------------------------
    ## Insert into database
    if (!empty($arQuery)) {
        foreach ($arQuery as $query) {
            $isSelect = (stripos(ltrim($query), 'SELECT') === 0);

            if ($oDb->sqlExec($query)) {
                $isPostError = false;
                continue;
            }

            if ($isSelect) {
                continue;
            }


            return '<span class="xt red">DB ERROR:</span>' . htmlspecialchars3($arQuery);
        }
    } else {
        $isPostError = false;
    }
    ##
    ## ----------------------------------------------------

    // Return status messages or redirect ofter post
    if ($isPostError) {
        return '<span class="xt red">DB ERROR:</span>' . $countQuery . htmlspecialchars3($arQuery);
    }

    /* Try to update dictionary settings */
    global $arDictParam, $arPost;
    if (isset($arDictParam) && is_array($arPost)) {
        gw_sys_dict_update();
    }

    /* 12 June 2008 */
    /* Recount the number of dictionaries in each topic */
    if (
        $gw_this['vars'][GW_TARGET] == GW_T_TOPICS
        || $gw_this['vars'][GW_TARGET] == GW_T_DICTS
    ) {
        gw_topic_recount();
    }

    /* */
    if ($isPause) {
        global $strR;
        $strR .= '<div class="center"><p class="actions-third xw">'
            . $oHtml->a($urlTo, $strContinue . ' <span id="countdown"></span>')
            . ' </p></div>';
        return;
    }

    $oSess->user_close();
    gwtk_header(append_url($urlTo), $sys['is_delay_redirect']);

    return true;
}


/**
 * Join posted variables with content structure
 *
 * @param    array   $arParsed   fields content structure
 * @param    array   $arPre      additional actions
 * @return   array   new $arParsed;
 * @see ParseFieldDbToInput()
 */
function gw_ParsePre($arParsed, $arPre)
{
	global $arDictParam, $gw_this;

	$arControl =& $gw_this['vars']['arControl'];
	if (!is_array($arParsed) || !is_array($arPre))
	{
		return $arParsed;
	}
	// go for $arPre
	//
	// update some arrays and tags...
	//
	//
	if (isset($arPre['trsp'][0][0]['value']))
	{
		$tmp['arTrsp'] = explode(CRLF, trim($arPre['trsp'][0]['value']));
        if (is_array($tmp['arTrsp'])) {
            foreach ($tmp['arTrsp'] as $k => $v) {
                $arPre['trsp'][0][$k]['value'] = $v;
            }
        }
	}
    // for each target [ abbr | trns | defn | syn | .. ]
    foreach ($arPre as $target_name => $arTarget) {
        // replace structures
        $arParsed[$target_name] = $arTarget;
    }
    // for each target [ abbr | trns | defn | syn | .. ]
    foreach ($arPre as $target_name => $arTarget) {
		// is there any direct instructions for this tag?
		if (isset($arControl[$target_name])) // defn | abbr | trns
		{
			// Get ID from current tag followed by direct instructions
			foreach ($arControl[$target_name] as $action => $arId)
			{
				$tmp['action'] = $action;
				foreach ($arId as $elK => $arCh)
				{
					foreach ($arCh as $chK => $ChV)
					{
						$tmp['chK'] = $chK;
						$tmp['elK'] = $elK;
					}
				}
			}
			// Now script knows what are `chK' and `ehK' for current tag

			// How many keys (definitions) in the current tag
			$tmp['intCurChilds'] = (sizeof($arParsed[$target_name][$tmp['elK']]) - 1); // -1 because array

			if ($tmp['action'] == GW_A_ADD)
			{
				// add empty values
				if (!empty($arDictParam))
				{
					if (!isset($arParsed['syn']) && $arDictParam['is_syn'] ) { $arParsed['syn'] = array(); }
					if (!isset($arParsed['antonym']) && $arDictParam['is_antonym'] ) { $arParsed['antonym'] = array(); }
					if (!isset($arParsed['see']) && $arDictParam['is_see']){ $arParsed['see'] = array(); }
					if (!isset($arParsed['usg']) && $arDictParam['is_usg']){ $arParsed['usg'] = array(); }
					if (!isset($arParsed['src']) && $arDictParam['is_src']){ $arParsed['src'] = array(); }
					if (!isset($arParsed['phone']) && $arDictParam['is_phone']){ $arParsed['phone'] = array(); }
					if (!isset($arParsed['address']) && $arDictParam['is_address']){ $arParsed['address'] = array(); }
				}
				//
				if ( ($target_name == 'abbr') || ($target_name == 'trns') )
				{
					// do not add empty attributes
					if ( ($arParsed[$target_name][$tmp['elK']][$tmp['intCurChilds']]['value'] != '') ||
						 ($arParsed[$target_name][$tmp['elK']][$tmp['intCurChilds']]['attributes']['lang'] != '--')
					   )
					{
						$arParsed[$target_name][$tmp['elK']][($tmp['intCurChilds']+1)]['value'] = '';
						$arParsed[$target_name][$tmp['elK']][($tmp['intCurChilds']+1)]['attributes']['lang'] = '--';
					}
				}
				elseif ($target_name == 'defn')
				{
					//
					gw_array_insert($arParsed[$target_name], $tmp['elK'],
							array('value' => '')
					);
					//
					gw_array_insert($arParsed['abbr'], $tmp['elK'],
							array(0 => array('value' => '', 'attributes' => array('lang' => '--')))
					);
					gw_array_insert($arParsed['trns'], $tmp['elK'],
							array(0 => array('value' => '', 'attributes' => array('lang' => '--')))
					);
					//
					gw_array_insert($arParsed['usg'], $tmp['elK'], array('value' => '') );
					gw_array_insert($arParsed['address'], $tmp['elK'], array(0 => array('value' => '')) );
					gw_array_insert($arParsed['phone'], $tmp['elK'], array(0 => array('value' => '')) );
					gw_array_insert($arParsed['src'], $tmp['elK'], array(0 => array('value' => '')) );
					gw_array_insert($arParsed['see'], $tmp['elK'], array(0 => array('value' => '')) );
					gw_array_insert($arParsed['syn'], $tmp['elK'], array(0 => array('value' => '')) );
					gw_array_insert($arParsed['antonym'], $tmp['elK'], array(0 => array('value' => '')) );

					#prn_r($arParsed['usg'], strval($tmp['elK']));
				}
				elseif ($target_name == 'page')
				{
					gw_array_insert($arParsed[$target_name], $tmp['elK'],
							array('page_title' => '', 'page_descr' => '', 'page_keywords' => '', 'page_content' => '', 'id_lang' => '', 'id_page_phrase' => '')
					);
				}
				elseif ($target_name == 'topic')
				{
					gw_array_insert($arParsed[$target_name], $tmp['elK'],
							array('topic_title' => '', 'topic_descr' => '', 'id_lang' => '', 'id_topic_phrase' => '')
					);
				}
			}
			elseif ($tmp['action'] == GW_A_REMOVE)
			{
				// `Remove' pressed
				//
				if ( ($target_name == 'abbr') || ($target_name == 'trns') )
				{
					// do not remove empty attributes
					if ( ($arParsed[$target_name][$tmp['elK']][$tmp['chK']]['value'] != '') ||
						 ($arParsed[$target_name][$tmp['elK']][$tmp['chK']]['attributes']['lang'] != '--')
					   )
					{
						unset($arParsed[$target_name][$tmp['elK']][$tmp['chK']]);
					}
					unset($arParsed[$target_name][$tmp['elK']][$tmp['chK']]);
				}
				else
				{
					// Remove current key from definition and all related to key tags
					for (reset($arParsed); list($targetK, $targetV) = each($arParsed);)
					{
						/* unset only existed keys */
						if (isset($targetV[$tmp['elK']]) && is_array($arParsed[$targetK][$tmp['elK']]))
						{
							unset( $arParsed[$targetK][$tmp['elK']] );
						}
					}
				} // end of target
			} // end of action
#            prn_r($arPre);
#            prn_r($arParsed);
		} // target_name
		//
		#prn_r($arParsed['abbr'], $target_name);
	} // end of root elements, target
	return $arParsed;
}

/**
 * Clears all cached files for selected dictionary
 *
 * @return   str report with information of deleted (or not) files
 */
function gw_tmp_clear($prefix = 'st')
{
    $str = '';
    $deleted_files = '';
    $str_dir = $GLOBALS['sys']['path_cache_sql'];

    $str .= '<span class="xt">Cache...';

    $prefixes = [
        sprintf('%05d', $prefix),
        'st',
    ];

    $processed = [];

    if (is_dir($str_dir)) {
        foreach ($prefixes as $current_prefix) {
            if (isset($processed[$current_prefix])) {
                continue;
            }
            $processed[$current_prefix] = true;

            $dir = opendir($str_dir);
            if ($dir !== false) {
                while (($f = readdir($dir)) !== false) {
                    if ($f === '.' || $f === '..') {
                        continue;
                    }

                    $full_path = $str_dir . '/' . $f;

                    if (!is_file($full_path)) {
                        continue;
                    }

                    if (!preg_match('/^' . preg_quote($current_prefix, '/') . '_/', $f)) {
                        continue;
                    }

                    $deleted_files .= '<li>' . $full_path;
                    unlink($full_path);
                }
                closedir($dir);
            }
        }
    }

    if ($deleted_files !== '') {
        $str .= '<ul class="red">' . $deleted_files . '</ul>';
    }

    $str .= ' finished.</span>';

    if ($deleted_files === '') {
        $str = '';
    }

    return $str;
}


/**
 * Get admin menu items available for current user permissions.
 *
 * The method reads active permissions from session, builds SQL condition
 * for component map permissions and groups fetched rows by component name.
 *
 * @return array
 */
function gw_admin_get_menu_items()
{
    global $oSess, $oDb, $oSqlQ;

    $ar_result      = [];
    $ar_permissions = [];

    foreach ((array)$oSess->ar_permissions as $permission_name => $is_allowed) {
        if ($is_allowed) {
            $ar_permissions[] = gw_text_sql($permission_name);
        }
    }

    if (empty($ar_permissions)) {
        return $ar_result;
    }

    $ar_sql_like = [];
    foreach ($ar_permissions as $permission_name) {
        $ar_sql_like[] = 'cmm.req_permission_map LIKE "%:' . $permission_name . ':%"';
    }

    $sql_permissions = implode(' OR ', $ar_sql_like);

    $ar_sql = $oDb->sqlRun(
        $oSqlQ->getQ('get-components-actions', $sql_permissions, '1=1', ' AND cm.is_active = "1" ')
    );

    foreach ($ar_sql as $ar_row) {
        $ar_result[$ar_row['id_component_name']][] = $ar_row;
    }

    return $ar_result;
}