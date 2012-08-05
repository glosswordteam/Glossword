<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: func.admin.inc.php 531 2008-07-09 19:20:16Z glossword_team $ -->');
}
/**
 *  Functions for administrative interface.
 */

function gw_dict_browse_for_select($arDictParam)
{
	global $oHtml, $oL, $oFunc, $sys, $gw_this;
	return '<div class="xw">'.
			$oHtml->a($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION] .'&'. GW_TARGET.'='.$gw_this['vars'][GW_TARGET]. '&id='.$arDictParam['id'], $arDictParam['title'] ).
			'</div>'.
			$oL->m(1364).': <strong>'.$arDictParam['id'].'</strong> &#8226; '.
			$oL->m('termsamount').': <strong>'.$oFunc->number_format($arDictParam['int_terms'], 0, $oL->languagelist('4')).'</strong>';
}

/**
 * Recounts the number of dictionaries in each topic.
 */
function gw_topic_recout()
{
	$o = new gw_topics_recounter();
	$o->recout();
}
class gw_topics_recounter
{
	var $oDb, $sys;
	var $ar_sum_totals, $ar_parents, $ar_items_counted = array();
	
	/* Autoexec */
	function gw_topics_recounter()
	{
		global $oDb, $sys;
		$this->oDb =& $oDb;
		$this->sys =& $sys;
	}
	function recout()
	{
		/* Create topic reference */ 
		$sql = 'SELECT id_topic, id_parent
				FROM `'.$this->sys['tbl_prefix'].'topics`
				GROUP BY id_topic, id_parent';
		$arSql = $this->oDb->sqlExec($sql);
		while (is_array($arSql) && list($k, $arV) = each($arSql))
		{
			$this->ar_parents[$arV['id_parent']][] = $arV['id_topic'];
		}
		/* Count items for each topic */
		$sql = 'SELECT id_topic, count(*) as cnt
				FROM `'.$this->sys['tbl_prefix'].'dict`
				GROUP BY id_topic';
		$arSql = $this->oDb->sqlExec($sql);
		while (is_array($arSql) && list($k, $arV) = each($arSql))
		{
			$this->ar_items_counted[$arV['id_topic']] = $arV['cnt'];
		}
		/* Select only root topics */
		$sql = 'SELECT id_topic
				FROM `'.$this->sys['tbl_prefix'].'topics`
				WHERE id_parent ="0"';
		$arSql = $this->oDb->sqlExec($sql);
		/* Count topics for each root */
		while (is_array($arSql) && list($k, $arV) = each($arSql))
		{
			$this->get_subitems($arV['id_topic'], $this->ar_items_counted, $this->ar_parents);
		}
		/* Update database */
		for (reset($this->ar_sum_totals); list($id_topic, $v) = each($this->ar_sum_totals);)
		{
			$this->oDb->sqlExec( gw_sql_update(array('int_items' => $v), $this->sys['tbl_prefix'].'topics', 'id_topic = "'.$id_topic.'"') );
		}
	}
	/* */
	function get_subitems($id_topic, $ar_items_counted, $ar_parents)
	{
		/* Get number of item directly from root topics */
		$cnt = isset($ar_items_counted[$id_topic]) ? $ar_items_counted[$id_topic] : 0;
		/* Number of children items of this id_topic */
		$int_ch = isset($ar_parents[$id_topic]) ? sizeof( $ar_parents[$id_topic] ) : 0;
		for ($i = 0; $i < $int_ch; $i++)
		{
			$cnt += $this->get_subitems( $ar_parents[$id_topic][$i], $ar_items_counted, $ar_parents );
		}
		$this->ar_sum_totals[$id_topic] = $cnt;
		return $cnt;
	}
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

/* Update dictionary settings */
function gw_sys_dict_update()
{
	global $arDictParam, $oDb;
	if (isset($arDictParam['id']) && $arDictParam['id'])
	{
		$qDict['int_terms'] = $arDictParam['int_terms'] = gw_sys_dict_count_terms();
		$qDict['int_terms_total'] = $arDictParam['int_terms_total'] = gw_sys_dict_count_terms_total();
		$qDict['int_bytes'] = $arDictParam['int_bytes'] = gw_sys_dict_count_kb();
		$sql = gw_sql_update($qDict, TBL_DICT, "id = '".$arDictParam['id']."'");
		$oDb->sqlExec($sql);
	}
}
/* Count the number of published terms */
function gw_sys_dict_count_terms()
{
	global $arDictParam, $oDb, $sys;
	if (isset($arDictParam['tablename']))
	{
		$sql = 'SELECT count(*) as n FROM `' . $arDictParam['tablename'].'`
				WHERE is_active = "1" AND date_created <= ' . $sys['time_now_gmt_unix'];
		$arSql = $oDb->sqlExec($sql);
	}
	return isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
}
/* Count the total number of terms */
function gw_sys_dict_count_terms_total()
{
	global $arDictParam, $oDb, $sys;
	if (isset($arDictParam['tablename']))
	{
		$sql = 'SELECT count(*) as n FROM `' . $arDictParam['tablename'].'`
				WHERE is_active != 3 AND date_created <= ' . $sys['time_now_gmt_unix'];
		$arSql = $oDb->sqlExec($sql);
	}
	return isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
}
/* Count bytes */
function gw_sys_dict_count_kb()
{
	global $arDictParam, $oDb;
	if (isset($arDictParam['tablename']))
	{
		$sql = 'SELECT sum(int_bytes) AS bytes FROM `' . $arDictParam['tablename'].'`';
		$arSql = $oDb->sqlExec($sql);
		return isset($arSql[0]['bytes']) ? $arSql[0]['bytes'] : 0;
	}
	return 0;
}
/* CHECK & OPTIMIZE table */
function gw_sys_dict_check($table)
{
	global $oDb;
	$oDb->sqlExec('CHECK TABLE `' . $table.'`');
	$oDb->sqlExec('OPTIMIZE TABLE `' . $table.'`');
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
	global $oL, $oSess, $oHtml, $oDb, $oSqlQ;
	/* */
	$ar_perms = $oSess->ar_permissions;
	foreach ($ar_perms AS $permission => $is)
	{
		if (!$is)
		{
			unset($ar_perms[$permission]);
		}
	}
	/* */
	$ar_sql_like = 'cmm.req_permission_map LIKE "%:'.implode(':%" OR cmm.req_permission_map LIKE "%:', array_keys($ar_perms) ).':%"';
	/* */
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-components-actions', $ar_sql_like, '1=1', ' AND cm.is_active = "1" '));
	$arMenu = array();
	/* Re-arrange array */
	for (; list($k1, $arV) = each($arSql);)
	{
		$arMenu[$arV['id_component_name']][] = $arV;
		unset($arSql[$k1]);
	}
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
	for (; list($id_component, $arV) = each($arMenu);)
	{
		/* for each component */
		$oL->getCustom('addon_'.$id_component, $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
		/* background color */
		$int_menu_el % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);

		$ar_js_ids[$int_menu_el] = str_replace('_', '-', $id_component);

		$gw_this['ar_actions_list'][$id_component] = array();

		$str .= CRLF.'<tr>';
		$str .= '<td onclick="return toggle_collapse(\''.$ar_js_ids[$int_menu_el].'\')" class="admcomponents" style="text-align:' . $sys['css_align_left'] . '">';
		$str .= '<img id="ci-'.$ar_js_ids[$int_menu_el].'" src="'.$sys['path_img'].'/collapse_on.png" alt="" width="9" height="21" />';
		$str .= $oL->m($arV[0]['cname']);
		$str .= '</td>';
		$str .= '</tr><tr><td id="co-'.$ar_js_ids[$int_menu_el].'" class="actions-primary" style="text-align:' . $sys['css_align_left'] . '">';
#		$str .= '<b>'.implode('</b> <b>', $arStr).'</b>';
		/* for each component action */
		for (; list($k2, $arV2) = each($arV);)
		{
			/* Include links to actions for a primary menu */
			if ($arV2['is_in_menu'] == 1)
			{
				$str .= ' '.$oHtml->a( $sys['page_admin'] . '?' .
							GW_TARGET . '=' . $id_component . '&' .
							GW_ACTION . '=' . $arV2['aname_sys'],
							'<span>'.$arV2['icon'].'</span>&#160;'. $oL->m($arV2['aname']),
							 $oL->m($arV2['aname'])
						);
			}
			/* Include links to actions for a secondary menu */
			/* Do not include links to actions with is_in_menu = 0 */
			if ($arV2['is_in_menu'] != 0)
			{
				$and_tid = '';
				if ( $gw_this['vars']['tid'] && ($gw_this['vars'][GW_TARGET] == $id_component) )
				{
					$and_tid = '&tid='.$gw_this['vars']['tid'];
				}
				$gw_this['ar_actions_list'][$id_component][$arV2['aname_sys']] = 
					$oHtml->a($sys['page_admin'] . '?'.GW_ACTION.'='.$arV2['aname_sys'].'&'.GW_TARGET.'='.$arV2['id_component_name'].$and_tid, $oL->m($arV2['aname']), $oL->m($arV2['cname']).': '.$oL->m($arV2['aname']) );
			}
		}
		$str .= '</td></tr>';
		$int_menu_el++;
	}
	$str .= '</tbody></table>';
	
#prn_r( $gw_this['ar_actions_list'] );
#prn_r( sizeof($oL->lang) );
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
	$str_status = isset($GLOBALS['oL']) ? $GLOBALS['oL']->m('2_success') : 'ok';
	$str_continue = isset($GLOBALS['oL']) ? $GLOBALS['oL']->m('2_continue') : 'Continue';
	/**
	 * Outputs all database queries in readable format
	 * @param    array   $arQuery
	 * @return   string  debug information
	 * @access   private
	 * @see  htmlspecialchars2()
	 */
	function _gw_showhtml($arQuery)
	{
		$arQuery = array_map("htmlspecialchars_ltgt", $arQuery);
		$arQuery = array_map("gw_highlight_sql", $arQuery);
		return '<ul class="gwsql"><li>' . implode(';</li><li>', $arQuery). ';</li></ul>';
	}
	$url_to = ($url == '') ? $sys['page_admin'] : $sys['page_admin'] . '?' . $url;
	if ($isDebug)
	{
		return _gw_showhtml($arQuery). '<p>' . $oHtml->a($url_to, $str_continue) . ' <span id="countdown"></span></p>';
	}
	## ----------------------------------------------------
	## Insert into database
	if ($lock != '')
	{
		sqlLock($lock);
	}
	$cntQ = sizeof($arQuery);
	for ($i=0; $i < $cntQ; $i++)
	{
		if ($oDb->sqlExec($arQuery[$i])){ $isPostError = false; }
		if ($isPostError)
		{
			$isPostError = preg_match("/^SELECT/", $arQuery[$i]) ? true : false;
		}
	}
	if ($lock != '')
	{
		sqlUnlock();
	}
	##
	## ----------------------------------------------------
	// 12 jan 2003, No data
	if ($cntQ == 0)
	{
		$isPostError = 0;
	}
	// Return status messages or redirect ofter post
	if ($isPostError)
	{
		return '<span class="xt" class="red">ERROR:</span>' . $cntQ . htmlspecialchars3($arQuery);
	}
	/* Try to update dictionary settings */
	global $arDictParam, $arPost;
	if ( isset($arDictParam) && is_array($arPost) )
	{
		gw_sys_dict_update();
	}
	/* 12 June 2008 */
	/* Recount the number of dictionaries in each topic */
	if ($gw_this['vars'][GW_TARGET] == GW_T_TOPICS || $gw_this['vars'][GW_TARGET] == GW_T_DICTS)
	{
		gw_topic_recout();
	}
	/* */
	if ($isPause)
	{
		global $strR;
		$strR .= '<div class="center"><p class="actions-third xw">' .$oHtml->a($url_to, $str_continue.' <span id="countdown"></span>'). ' </p></div>';
		return;
	}
	else
	{
		$oSess->user_close();
		gwtk_header(append_url($url_to), $sys['is_delay_redirect']);
	}
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
		while(is_array($tmp['arTrsp']) && list($k, $v) = each($tmp['arTrsp']))
		{
			$arPre['trsp'][0][$k]['value'] = $v;
		}
	}
#	if (isset($arPre['see'][0][0]['value']))
#	{
#        $tmp['arSyn'] = explode(CRLF, trim($arPre['syn'][0]['value']));
#        while(is_array($tmp['arSyn']) && list($k, $v) = each($tmp['arSyn']))
#        {
#            $tmp['synText'] = preg_replace("'(.*)\[\[(.*?)\]\]'", ' \\2', $v);
#            $v = preg_replace("'\[\[(.*?)\]\]'", '', $v );
#            $tmp['synText'] = str_replace($v, '', $tmp['synText']);
#            $arPre['syn'][$k]['value'] = $v;
#            $arPre['syn'][$k]['attributes']['text'] = $tmp['synText'];
#        }
#        prn_r($arParsed['syn'], __LINE__.__FILE__);
#	}
	#prn_r($arPre['usg']);
	//
	//
	for (reset($arPre); list($target_name, $arTarget) = each($arPre);) // for each target [ abbr | trns | defn | syn | .. ]
	{
		// replace structures
		$arParsed[$target_name] = $arPre[$target_name];
	}
	for (reset($arPre); list($target_name, $arTarget) = each($arPre);) // for each target [ abbr | trns | defn | syn | .. ]
	{
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
	$str = $d1 = $d2 = '';
	$strDir = $GLOBALS['sys']['path_cache_sql'];
	$str .= '<span class="xt">Cache...';
	$prefix = sprintf("%05d", $prefix);
	if (is_dir($strDir))
	{
		$dir = opendir($strDir);
		while (($f = readdir($dir)) !== false)
		{
			if ($f != '.' && $f != '..' && is_file($strDir.'/'.$f) && (preg_match("/^".$prefix."_/", $f)))
			{
				$d1 .= "<li>".$GLOBALS['sys']['path_cache_sql'].'/'.$f;
				unlink($GLOBALS['sys']['path_cache_sql'].'/'.$f);
			}
		}
	}
	$prefix = 'st';
	if (is_dir($strDir))
	{
		$dir = opendir($strDir);
		while (($f = readdir($dir)) !== false)
		{
			if ($f != '.' && $f != '..' && is_file($strDir.'/'.$f) && (preg_match("/^".$prefix."_/", $f)))
			{
				$d1 .= "<li>".$GLOBALS['sys']['path_cache_sql'].'/'.$f;
				unlink($GLOBALS['sys']['path_cache_sql'].'/'.$f);
			}
		}
	}
	$str .= ($d1) ? ('<ul class="red">' . $d1 . '</ul>') : false;
	$str .= ($d2) ? ('<ul class="red">' . $d2 . '</ul>') : false;
	$str .= ' finished.</span>';
	// No cache found
	if (($d1 == $d2) && ($d1 == ''))
	{
		$str = '';
	}
	return $str;
}

/* end of file */
?>