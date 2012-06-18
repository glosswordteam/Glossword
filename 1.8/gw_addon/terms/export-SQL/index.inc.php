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
	die('<!-- $Id: index.inc.php 548 2008-08-15 16:08:17Z glossword_team $ -->');
}
/**
 * Glossword plug-in: Export to SQL
 * Location: glossword/inc/export_SQL/index.inc.php
 * by Dmitry N. Shilnikov <dev at glossword dot info>
 */
define('FORMAT_NAME', str_replace('export_', '', $arPost['fmt']));
define('FORMAT_EXT', 'sql');
/**
 *
 */
function getFormSql($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $sys, $oSess, $oL, $arPost, $oFunc, $arSplit, $arDictParam, $ar_theme, $gw_this;
	$strForm = "";
	$trClass = "xt";
	$form = new gwForms();
	$form->Set('action',           $sys['page_admin']);
	$form->Set('submitok',         $oL->m('3_next').' &gt;');
	$form->Set('submitdel',        $oL->m('3_remove'));
	$form->Set('submitcancel',     $oL->m('3_cancel'));
	$form->Set('formbgcolor',      $ar_theme['color_2']);
	$form->Set('formbordercolor',  $ar_theme['color_4']);
	$form->Set('formbordercolorL', $ar_theme['color_1']);
	$form->Set('align_buttons',    $sys['css_align_right']);
	$form->Set('arLtr',            array('arPost[split]'));
	$form->Set('charset', $sys['internal_encoding']);
	## ----------------------------------------------------
	##
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while(is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = "";
		if (isset($arReq[$key])) { $arReqMsg[$key] = ' <span style="color:#E30"><b>*</b></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="'.$trClass.'" style="color:#E30"><b>' . $oL->m('reason_9') .'</b></span>'; }
	} // end of while
	##
	## ----------------------------------------------------
	$strForm = '';

	$form->setTag('select', 'class', 'input');

	$strForm .= '<table cellspacing="0" cellpadding="2" border="0" width="100%">';
	$strForm .= '<tbody><tr valign="top"><td style="width:50%">';

		$strForm .= getFormTitleNav(FORMAT_NAME);
		$strForm .= '<table cellspacing="3" cellpadding="0" border="0" width="100%" class="gw2TableFieldset">';
		$strForm .= '<tbody><tr><td style="width:30%"></td><td style="width:70%"></td></tr>';
		$arBoxId = array();

		$arBoxId['id'] = "arPost_s";
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[sd_mode]', 's', ($vars['sd_mode'] == 's'), $arBoxId) . '</td>
			<td class="td2"><label for="arPost_s">'.$oL->m('sql_structure').'</label></td>
			</tr>';
		$arBoxId['id'] = "arPost_d";
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[sd_mode]', 'd', ($vars['sd_mode'] == 'd'), $arBoxId) . '</td>
			<td class="td2"><label for="arPost_d">'.$oL->m('sql_data').'</label></td>
			</tr>';
		$arBoxId['id'] = "arPost_sd";
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[sd_mode]', 'sd', ($vars['sd_mode'] == 'sd'), $arBoxId) . '</td>
			<td class="td2"><label for="arPost_sd">'.$oL->m('sql_s_and_d').'</label></td>
			</tr>';


		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('checkbox', 'arPost[is_droptable]', $vars['is_droptable']) . '</td>
			<td class="td2"><label for="arPost_is_droptable_">'.$oL->m('sql_droptable').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('checkbox', 'arPost[is_hex]', $vars['is_hex']) . '</td>
			<td class="td2"><label for="arPost_is_hex_">'.$oL->m('1041').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('checkbox', 'arPost[is_dictdescr]', $vars['is_dictdescr']) . '</td>
			<td class="td2"><label for="arPost_is_dictdescr_">'.$oL->m('sql_dict_descr').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('checkbox', 'arPost[is_dictstats]', $vars['is_dictstats']) . '</td>
			<td class="td2"><label for="arPost_is_dictstats_">'.$oL->m('sql_dict_stats').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('checkbox', 'arPost[is_keywords]', $vars['is_keywords']) . '</td>
			<td class="td2"><label for="arPost_is_keywords_">'.$oL->m('sql_keywords').'</label></td>
			</tr>';
		/* Check/Uncheck All */
		$strForm .= '
			<tr>
			<td></td>
			<td class="td2">
				<a href="#" onclick="setCheckboxesSQL(true);return false">'.$oL->m('select_on').'</a>
				|
				<a href="#" onclick="setCheckboxesSQL(false);return false">'.$oL->m('select_off').'</a>
			</td>
			</tr>';

		$strForm .= '</tbody></table>';


	$strForm .= '</td><td>';


		$strForm .= getFormTitleNav($oL->m('dictdump_split'));
		$strForm .= '<table cellspacing="3" cellpadding="0" border="0" width="100%" class="gw2TableFieldset">';
		$strForm .= '<tbody><tr><td style="width:30%"></td><td style="width:70%"></td></tr>';
		$arBoxId = array();
		$arBoxId['id'] = "split_list1";
		$arBoxId['onchange'] = 'checkSplit()';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[split_m]', 'list', $vars['is_list1'], $arBoxId) . '</td>
			<td class="td2"><label id="labelList" for="split_list1">'.$oL->m('dictdump_list').'</label></td>
			</tr>';
		$arSplit = array ('100' => '100', '500' => '500', '1000' => '1000', '2500' => '2500', '5000' => '5000');
		$strForm .= '
	  		<tr>
			<td></td>
			<td class="td2">'. $form->field('select', 'arPost[split1]', $vars['intsplit'], '100%', $arSplit). '
			</td>
			</tr>';
		$arBoxId['id'] = 'split_custom';
		$arBoxId['onchange'] = 'checkSplit()';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[split_m]', 'custom', $vars['is_list2'], $arBoxId) . '</td>
			<td class="td2"><label id="labelCustom" for="split_custom">'.$oL->m('dictdump_custom').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td></td>
			<td class="td2">'. $form->field('input', "arPost[split2]", $vars['int_terms'], '100%', $arSplit). '
			</td>
			</tr>';
		$strForm .= '</tbody></table>';

		$arBoxId = array();
		$strForm .= getFormTitleNav($oL->m('1044'));
		$strForm .= '<table cellspacing="3" cellpadding="0" border="0" width="100%" class="gw2TableFieldset">';
		$strForm .= '<tbody><tr><td style="width:30%"></td><td style="width:70%"></td></tr>';
#		$arBoxId['id'] = "cmptblty_323";
#		$strForm .= '
#			<tr>
#			<td class="td1">' . $form->field('radio', 'arPost[cmptblty]', '323', $vars['is_cmptblty_323'], $arBoxId) . '</td>
#			<td class="td2"><label for="cmptblty_323">SQL 3.23.x, 4.0.x</label></td>
#			</tr>';
		$arBoxId['id'] = "cmptblty_410";
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[cmptblty]', '410', $vars['is_cmptblty_410'], $arBoxId) . '</td>
			<td class="td2"><label for="cmptblty_410">SQL 4.1.x, 5.x</label></td>
			</tr>';
		$strForm .= '</tbody></table>';

	$strForm .= '</td></tr>';
	$strForm .= '</tbody></table>';

	$filename = $sys['path_addon'].'/'.$gw_this['vars'][GW_TARGET].'/export-'.$vars['fmt'].'/index.js.php';
	include($filename);
	$strForm .= $form->field("hidden", 'arPost[fmt]', $vars['fmt']);
	$strForm .= $form->field("hidden", 'arPost[int_terms]', $vars['int_terms']);
	$strForm .= $form->field("hidden", 'arPost[min]', $vars['min']);
	$strForm .= $form->field("hidden", 'arPost[max]', $vars['max']);
	$strForm .= $form->field("hidden", 'w1', '3');
	$strForm .= $form->field("hidden", 'id', $gw_this['vars']['id']);
	$strForm .= $form->field("hidden", GW_TARGET, GW_T_TERMS);
	$strForm .= $form->field("hidden", GW_ACTION, GW_A_EXPORT);
	$strForm .= $form->field("hidden", $oSess->sid, $oSess->id_sess);

	return $form->Output($strForm);
} //
// --------------------------------------------------------
$arReq = array('fmt');
// --------------------------------------------------------
global $oL, $gw_this, $oSess, $oDb, $oSqlQ, $oHtml, $sys;
// Language
$oL->getCustom('export_sql', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
// --------------------------------------------------------
// split per lines
$is_split       = isset($arPost['is_split']) ? $arPost['is_split'] : 100;
$is_list1       = (isset($arPost['is_list1']) && $arPost['is_list1'] == GW_A_LIST) ? 1 : 0;
$is_list2       = (isset($arPost['is_list2']) && $arPost['is_list2'] == 'custom') ? 1 : 0;

$vars['is_cmptblty_323'] = (isset($arPost['cmptblty']) && $arPost['cmptblty'] == '323') ? 1 : 0;
$vars['is_cmptblty_410'] = (isset($arPost['cmptblty']) && $arPost['cmptblty'] == '410') ? 1 : 0;

$vars['is_droptable'] = isset($arPost['is_droptable']) ? $arPost['is_droptable'] : 0;
$vars['is_hex']       = isset($arPost['is_hex']) ? $arPost['is_hex'] : 0;
$vars['is_keywords']  = isset($arPost['is_keywords']) ? $arPost['is_keywords'] : 0;
$vars['is_dictdescr'] = isset($arPost['is_dictdescr']) ? $arPost['is_dictdescr'] : 0;
$vars['is_dictstats'] = isset($arPost['is_dictstats']) ? $arPost['is_dictstats'] : 0;
$vars['query_type']   = isset($arPost['query_type']) ? $arPost['query_type'] : 'replace';

if (!($is_list1)&&!($is_list2) ) { $is_list1 = 1; }
//
$intSplit = 500;
/* */
if (!isset($arPost[GW_TARGET])) { $post = ''; }
if ($this->gw_this['vars']['w1'] == '2')
{
	/* Get the number of terms */
	$arSql = $oDb->sqlRun($oSqlQ->getQ('cnt-dict-date', $arDictParam['tablename'], $vars['min'], $vars['max']));
	$vars['int_terms'] = isset($arSql['0']['n']) ? $arSql['0']['n'] : 0;

	/* */
	$vars['fmt'] = $arPost['fmt'];
	$vars['intsplit'] = $intSplit;
	$vars['is_list1'] = $is_list1;
	$vars['is_list2'] = $is_list2;
	$vars['sd_mode'] = 'sd';

	$vars['is_droptable'] = 1;
	$vars['is_cmptblty_410'] = 1;
	$vars['is_hex'] = 1;
	$this->str .= getFormSql($vars, 0, 0, $arReq);
}
else
{
	$arBroken = validatePostWalk($arPost, $arReq);
	if (sizeof($arBroken) == 0)
	{
		$isPostError = 0;
	}
	if (!$isPostError) /* single check is ok */
	{
		$i = 0;
		$q = array();
		$mode = 'w';
		$cntFiles = 1000;
		if ($arPost['split_m'] == GW_A_LIST)
		{
			$int_split = $arPost['split1'];
		}
		else
		{
			$int_split = $arPost['split2'];
		}
		$cntFiles = ceil($arPost['int_terms'] / $int_split);

		$fileS = getExportFilename(
					$this->sys['path_export'] . '/'.
					@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'],
					$cntFiles, FORMAT_EXT
			 	);

		$this->str .= '<ul class="xt">';
		$oHtml->setTag('a', 'target', '_blank');

		// Export in progress

		/* Export dictionary description, 27 july 2003 */
		if ($vars['is_dictdescr'])
		{
			$strQ = '';
			$filename = $this->sys['path_export'] . '/'.
					@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'].'_dict.'.FORMAT_EXT;

			$this->str .= '<li>';
			$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
			$strQ .= '# <!-- ' . $filename . ' -->' . CRLF;
			$sql = sprintf('SELECT * FROM `' . TBL_DICT . '` WHERE id = %d', $this->gw_this['vars']['id']);
			$arSql = $oDb->sqlExec($sql, '', 0);
			for (; list($arK, $arV) = each($arSql);)
			{
				if ($vars['is_hex'])
				{
					for (reset($arV); list($kV, $vV) = each($arV);)
					{
						if (($arV[$kV] != '')
							&& (($kV == 'dict_settings')||($kV == 'title')||($kV == 'description')||($kV == 'announce'))
						)
						{
							$arV[$kV] = '0x' . bin2hex($arV[$kV]);
						}
					}
				}
				$strQ .= gw_sql_replace($arV, TBL_DICT, 0) . ';';
			}
			$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
			$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
		}
		/* 15 july 2003: Export dictionary statistics */
		if ($vars['is_dictstats'])
		{
			$strQ = '';
			$filename = $this->sys['path_export'] . '/'.
					@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'].'_stats.'.FORMAT_EXT;

			$this->str .= '<li>';
			$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
			$strQ .= '# <!-- ' . $filename . ' -->' . CRLF;
			$sql = sprintf('SELECT * FROM `' . $sys['tbl_prefix'] . 'stat_dict` WHERE id = "%d"', $this->gw_this['vars']['id']);
			$arSql = $oDb->sqlExec($sql);
			for (; list($arK, $arV) = each($arSql);)
			{
				$strQ .= gw_sql_replace($arV, $sys['tbl_prefix'] . 'stat_dict', 0) . ';';
			}
			$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
			$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
		}
		/* Export structure */
		if (($arPost['sd_mode'] == 'sd') || ($arPost['sd_mode'] == 's'))
		{
			$strQ = '';
			$filename = $this->sys['path_export'] . '/'.
					@date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'].'_structure.'.FORMAT_EXT;
			$this->str .= '<li>';
			$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
			$strQ .= '# <!-- ' . $filename . ' -->' . CRLF;
			if ($vars['is_droptable'])
			{
				$strQ .= 'DROP TABLE IF EXISTS `' . $arDictParam['tablename'] . '`;' . CRLF;
			}
			$strQ .= $oSqlQ->getQ('create-dict', $arDictParam['tablename']) . ';';
			$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
			$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
		}
		/* Export data */
		if (($arPost['sd_mode'] == 'sd') || ($arPost['sd_mode'] == 'd'))
		{
			for ($i = 0; $i < $cntFiles; $i++)
			{
				$sqlWhereDate = 'is_active != 3 AND date_modified >= ' . $arPost['min'] . ' AND date_modified <= ' . $arPost['max'];
				$limit = $oDb->prn_limit($arDictParam['int_terms'], $i + 1, $int_split);
				$filename = sprintf($fileS, ($i + 1), $cntFiles);
				$strQ = '';
				$this->str .= '<li>';
				$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';

				// Get SQL data for a terms and definitions
				$sql = sprintf('SELECT SQL_BIG_RESULT *
						FROM `%s`
						WHERE %s
						ORDER BY id %s',
						$arDictParam['tablename'], $sqlWhereDate, $limit
					);
				$arSql = $oDb->sqlExec($sql, '', 0);
				$strQ .= '# <!--' . $filename . ' -->' . CRLF;
				$strQ .= '# <!--' . sizeof($arSql) . ' record(s) -->';
				$arTermIDs = array();
				for (; list($arK, $arV) = each($arSql);)
				{
#					for (reset($arV); list($kV, $vV) = each($arV);)
#					{
#						$arV[$kV] = gw_addslashes(trim($arV[$kV]));
#					}
					if ($vars['is_hex'])
					{
						for (reset($arV); list($kV, $vV) = each($arV);)
						{
							if (($arV[$kV] != '')
							   && (($kV == 'defn')||($kV == 'term')||($kV == 'term_1')||($kV == 'term_2')||($kV == 'term_3'))
							)
							{
								$arV[$kV] = '0x' . bin2hex($arV[$kV]);
							}
						}
					}
					if ($int_split != $arDictParam['int_terms'])
					{
						/* REPLACE for updated terms */
						$strQ .= gw_sql_replace($arV, $arDictParam['tablename'], 0) . ';';
					}
					else
					{
						/* INSERT for complete dictionary */
						$strQ .= gw_sql_insert($arV, $arDictParam['tablename'], 0) . ';';
					}
					$arTermIDs[] = $arV['id'];
				}
				$strQ .= CRLF . '# <!-- end of ' . $filename . ' -->';
				$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
				$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';

				/* 21 Aug 2007: Export `user to term` mapping */
				$strQ = '';
				$filename = sprintf(getExportFilename(
						$sys['path_export'] . '/' . @date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
						'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'].'_map_user_to_term',
						$cntFiles,
						FORMAT_EXT
					 ), ($i + 1), $cntFiles
				);
				$this->str .= '<li>';
				$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
				$strQ .= '# <!-- ' . $filename . ' -->' . CRLF;
				$sql = sprintf('SELECT *
						FROM `' . $sys['tbl_prefix'] . 'map_user_to_term`
						WHERE dict_id = "%d" AND term_id IN (%s)', $this->gw_this['vars']['id'], implode(',', $arTermIDs)
					);
				$arSql = $oDb->sqlExec($sql);
				for (; list($arK, $arV) = each($arSql);)
				{
					$strQ .= gw_sql_replace($arV, $sys['tbl_prefix'] . 'map_user_to_term', 0) . ';';
				}
				$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
				$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
			}
		}
		if ($vars['is_keywords'])
		{
			// Create definition map
			$sqlWhereDate = 'date_modified >= ' . $arPost['min'] . ' AND date_modified <= ' . $arPost['max'];
			$strQ = '';
			// count number of items in map
			$sql = sprintf('SELECT count(*) as n
					FROM `%s` AS t, `' . TBL_WORDMAP . '` AS m
					WHERE %s
					AND m.term_id = t.id
					AND m.dict_id = "%d"',
					$arDictParam['tablename'], $sqlWhereDate, $this->gw_this['vars']['id']
					);
			$arSql = $oDb->sqlExec($sql, 0, '');
			$int_map = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
			for ($i = 0; $i < $cntFiles; $i++)
			{
				/* filename for wordmap */
				$fileS = getExportFilename(
					$sys['path_export'] . '/' . @date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'] . '_'.TBL_WORDMAP,
					$cntFiles, FORMAT_EXT
				);
				$filename = sprintf($fileS, ($i + 1), $cntFiles) ;
				//
				$tt = new gw_timer('sql_exp');
				$int_perpage = ceil($int_map / $cntFiles);
				$limit = $oDb->prn_limit($int_map, $i + 1, $int_perpage);
				$sql = sprintf('SELECT SQL_BIG_RESULT m.*
						FROM `%s` AS t, `' . TBL_WORDMAP . '` AS m
						WHERE %s
						AND m.term_id = t.id
						AND m.dict_id = "%d"
						ORDER BY m.term_id %s',
						$arDictParam['tablename'], $sqlWhereDate, $this->gw_this['vars']['id'], $limit
					);
				$arSql = $arKeywordsId = $arTermIds = array();
				$arSql = $oDb->sqlExec($sql, '', 0);
				$strQ = $strQmap = $strQdelete = '';
				$strQ .= '# <!--' . $filename . ' -->' . CRLF;
				$strQ .= '# <!--' . sizeof($arSql) . ' record(s) of ' . $int_map . ' -->';
				// clear map first
				if (($i == 0) && ($int_split == $arDictParam['int_terms']))
				{
					$strQdelete .= CRLF . 'DELETE FROM `' . TBL_WORDMAP . '` WHERE dict_id="' . $this->gw_this['vars']['id'] . '";';
				}
				// collect data for wordmap
				for (; list($arK, $arV) = each($arSql);)
				{
					if ($int_split != $arDictParam['int_terms'])
					{
						$arTermIds[$arV['term_id']] = $arV['term_id'];
					}
					// 06 july 2003, optimized sql-export
					$arKeywordsId[$arV['word_id']] = $arV['word_id'];
					$strQmap .= gw_sql_insert($arV, TBL_WORDMAP, 0) . ';';
				} // clear existent terms
				for (; list($kTerm, $vTerm) = each($arTermIds);)
				{
					$strQdelete .= CRLF . $oSqlQ->getQ('del-term_id-dict_d', TBL_WORDMAP, $vTerm, $this->gw_this['vars']['id']).';';
				}
				$strQ .= $strQdelete . $strQmap;
				$strQ .= CRLF . '# <!-- time spend, seconds: '. sprintf("%1.5f", $tt->end()) .' -->';
				$strQ .= CRLF . '# <!-- end of ' . $filename .' -->';
				$this->str .= '<li>';
				$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
				$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
				$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
				$tt = new gw_timer('sql_exp');

				/* File name for wordlist */
				$fileS = getExportFilename(
					$sys['path_export'] . '/' . @date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
					'_id'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'] . '_'.TBL_WORDLIST,
					$cntFiles, FORMAT_EXT
				 );
				$filename = sprintf($fileS, ($i + 1), $cntFiles);
				$arSql = array();
				if (!empty($arKeywordsId))
				{
					$sqlWhereWordlist = 'word_id IN('.implode(', ', $arKeywordsId) . ')';
					$sql = 'SELECT SQL_BIG_RESULT *
							FROM `' . TBL_WORDLIST . '`
							WHERE ' . $sqlWhereWordlist;
					$arSql = $oDb->sqlExec($sql, '', 0);
				}
				$strQ = '';
				$strQ .= '# <!--' . $filename .' -->'. CRLF;
				$strQ .= '# <!--' . sizeof($arSql) . ' records -->';
				for (; list($arK, $arV) = each($arSql);)
				{
				if ($vars['is_hex'])
				{
						for (reset($arV); list($kV, $vV) = each($arV);)
						{
							if (($arV[$kV] != '')
								&& (($kV == 'word_text'))
							)
							{
								$arV[$kV] = '0x' . bin2hex($arV[$kV]);
							}
						}
					}
					$strQ .= gw_sql_replace($arV, TBL_WORDLIST, 0) . ';';
				}
				$strQ .= CRLF . '# <!-- time spend, seconds: '. sprintf("%1.5f", $tt->end()) .' -->';
				$strQ .= CRLF . '# <!-- end of ' . $filename .' -->';
				$this->str .= '<li>';
				$this->str .= $oHtml->a($filename, $filename) . '&#8230; ';
				$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
				$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . ' ' . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
			} // for each file
		} // is_keywords
		$this->str .= '</ul>';
		$oHtml->setTag('a', 'target', '');
	}
}
/* end o file */
?>