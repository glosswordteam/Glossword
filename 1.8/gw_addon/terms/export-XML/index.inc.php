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
 * Glossword plug-in: Export to XML
 */
define('FORMAT_NAME', str_replace('export-', '', $arPost['fmt']));
define('FORMAT_EXT', 'xml');
/**
 *
 */
function getFormXml($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $id, $sys, $oL, $arPost, $arSplit, $arDictParam, $oFunc, $oSess, $ar_theme, $gw_this;
	$strForm = "";
	$trClass = "xt";

	$form = new gwForms();
	$form->action = $sys['page_admin'];
	$form->submitok = $oL->m('3_export');
	$form->submitdel = $oL->m('3_remove');
	$form->submitcancel = $oL->m('3_cancel');
	$form->formbgcolor = $ar_theme['color_2'];
	$form->formbordercolor = $ar_theme['color_4'];
	$form->formbordercolorL = $ar_theme['color_1'];
	$form->Set('charset', $sys['internal_encoding']);
	$form->arLtr = array('arPost[split]');
	## ----------------------------------------------------
	##
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while(is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = "";
		if (isset($arReq[$key])) { $arReqMsg[$key] = ' <span style="color:#E30"><strong>*</strong></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="'.$trClass.'" style="color:#E30"><strong>' . $oL->m('reason_9') .'</strong></span>'; }
	} // end of while
	##
	## ----------------------------------------------------
	$form->setTag('select', 'class', 'input');

	$strForm = '';
	$strForm .= '<table width="100%">';
	$strForm .= '<tbody><tr><td style="vertical-align:top;width:50%">';

			$strForm .= getFormTitleNav($oL->m('3_export'), '<span class="xr">'.FORMAT_NAME.'</span>');
			$strForm .= '<table class="gw2TableFieldset" width="100%">';
			$arTmp['id'] = 'arPost-t';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field("radio", "arPost[td_mode]", 't', ($vars['td_mode'] == 't'), $arTmp) . '</td>'.
						'<td class="td2"><label for="arPost-t" style="cursor:pointer;">'. $oL->m('terms') .'</label></td>'.
						'</tr>';
			$arTmp['id'] = 'arPost-d';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field("radio", "arPost[td_mode]", 'd', ($vars['td_mode'] == 'd'), $arTmp) . '</td>'.
						'<td class="td2"><label for="arPost-d" style="cursor:pointer;">'. $oL->m('definitions') .'</label></td>'.
						'</tr>';
			$arTmp['id'] = 'arPost-td';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field("radio", "arPost[td_mode]", 'td', (($vars['td_mode'] == 'td')?1:0), $arTmp) . '</td>'.
						'<td class="td2"><label for="arPost-td" style="cursor:pointer;">'. $oL->m('terms'). ' &amp; ' . $oL->m('definitions') .'</label></td>'.
						'</tr>';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field('checkbox', "arPost[is_term_t1]", $vars['is_term_t1']) . '</td>'.
						'<td class="td2"><label for="arPost_is_term_t1_">' . $oL->m('alphabetic_order') . ' 1</label></td>'.
						'</tr>';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field('checkbox', "arPost[is_term_t2]", $vars['is_term_t2']) . '</td>'.
						'<td class="td2"><label for="arPost_is_term_t2_">' . $oL->m('alphabetic_order') . ' 2</label></td>'.
						'</tr>';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field('checkbox', "arPost[is_term_t3]", $vars['is_term_t3']) . '</td>'.
						'<td class="td2"><label for="arPost_is_term_t3_">' . $oL->m('alphabetic_order') . ' 3</label></td>'.
						'</tr>';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field('checkbox', "arPost[is_term_id]", $vars['is_term_id']) . '</td>'.
						'<td class="td2"><label for="arPost_is_term_id_">' . $oL->m('xml_is_term_id') . '</label></td>'.
						'</tr>';
			$strForm .= '<tr>'.
						'<td class="td1">' . $form->field('checkbox', "arPost[is_term_uri]", $vars['is_term_uri']) . '</td>'.
						'<td class="td2"><label for="arPost_is_term_uri_">' . $oL->m('1073') . '</label></td>'.
						'</tr>';
			$strForm .= '</tbody></table>';

	$strForm .= '</td><td style="vertical-align:top;">';

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

		$arSplit = array('100' => '100', '500' => '500', '1000' => '1000', '2500' => '2500', '5000' => '5000');
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
		$arBoxId['id'] = 'cmptblty_new';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[cmptblty]', 'new', $vars['is_cmptblty_new'], $arBoxId) . '</td>
			<td class="td2"><label for="cmptblty_new">Glossword 1.8.4+</label></td>
			</tr>';
		$arBoxId['id'] = 'cmptblty_old';
		$strForm .= '
			<tr>
			<td class="td1">' . $form->field('radio', 'arPost[cmptblty]', 'old', $vars['is_cmptblty_old'], $arBoxId) . '</td>
			<td class="td2"><label for="cmptblty_old">Glossword &lt; 1.8.4</label></td>
			</tr>';
		$strForm .= '</tbody></table>';


	$strForm .= '</td>';
	$strForm .= '</tr>';
	$strForm .= '</table>';

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
}
/* -------------------------------------------------------- */
global $oL, $gw_this, $oSess, $oDb, $oSqlQ, $oHtml, $sys;
$arReq = array('fmt');
/* Language */
$oL->getCustom('export_xml', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
/* -------------------------------------------------------- */
/* split per lines */
$is_split   = isset($arPost['is_split']) ? $arPost['is_split'] : 100;
$is_list1   = ( isset($arPost['is_list1']) && $arPost['is_list1'] == "list" ) ? 1 : 0;
$is_list2   = ( isset($arPost['is_list2']) && $arPost['is_list2'] == "custom" ) ? 1 : 0;
if (!($is_list1)&&!($is_list2) ) { $is_list1 = 1; }
$is_term_t1   = isset($arPost['is_term_t1']) ? $arPost['is_term_t1'] : 0;
$is_term_t2   = isset($arPost['is_term_t2']) ? $arPost['is_term_t2'] : 0;
$is_term_t3   = isset($arPost['is_term_t3']) ? $arPost['is_term_t3'] : 0;
$is_term_id   = isset($arPost['is_term_id']) ? $arPost['is_term_id'] : 0;
$is_term_uri  = isset($arPost['is_term_uri']) ? $arPost['is_term_uri'] : 0;
$vars['is_cmptblty_old'] = (isset($arPost['cmptblty']) && $arPost['cmptblty'] == 'old') ? 1 : 0;
$vars['is_cmptblty_new'] = (isset($arPost['cmptblty']) && $arPost['cmptblty'] == 'new') ? 1 : 0;
$intSplit = 500;
if (!isset($arPost[GW_TARGET])) { $post = ''; }
if ($this->gw_this['vars']['w1'] == '2')
{
	/* Get the number of terms */
	$arSql = $oDb->sqlRun($oSqlQ->getQ('cnt-dict-date', $arDictParam['tablename'], $vars['min'], $vars['max']));
	$vars['int_terms'] = isset($arSql['0']['n']) ? $arSql['0']['n'] : 0;

	/* */
	$vars['is_cmptblty_new'] = 1;
	$vars['intsplit'] = $intSplit;
	$vars['is_list1'] = $is_list1;
	$vars['is_list2'] = $is_list2;
	$vars['td_mode'] = 'td';
	$vars['is_term_t1'] = 1;
	$vars['is_term_t2'] = 1;
	$vars['is_term_t3'] = 1;
	$vars['is_term_id'] = 1;
	$vars['is_term_uri'] = 1;
	$this->str .= getFormXml($vars, 0, 0, $arReq);
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
		$sqlWhereDate = 'is_active != 3 AND date_modified >= ' . $arPost['min'] . ' AND date_modified <= ' . $arPost['max'];
		$mode = 'w';
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
		/* Export in progress */
		$oHtml->setTag('a', 'target', '_blank');
		for ($i = 0; $i < $cntFiles; $i++)
		{
			$limit = $oDb->prn_limit($arDictParam['int_terms'], $i + 1,  $int_split);
			$filename = sprintf($fileS, ($i + 1), $cntFiles);
			$strQ = '';
			$this->str .= '<li>';
			$this->str .= $oHtml->a($filename, $filename);
			$this->str .= '&#8230; ';
			$sql = $oSqlQ->getQ('get-term-export', $arDictParam['tablename'], $sqlWhereDate, $limit);
			$arSql = $oDb->sqlExec($sql);

			$strQ .= '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . CRLF;
			if ($vars['is_cmptblty_old'])
			{
				$strQ .= '<glossword>' . CRLF;
			}
			else
			{
				$strQ .= '<glossword version="'.$sys['version'].'">' . CRLF;
			}
			//
			for(; list($k, $v) = each($arSql);)
			{
				$strQ .= '<line>';
				if ((($arPost['td_mode'] == 'td') || ($arPost['td_mode'] == 't')) && ($v['term'] != ''))
				{
					$str_is_term_t1 = ($is_term_t1) ? sprintf(' t1="%s"', $v['term_1']) : '';
					$str_is_term_t2 = ($is_term_t2) ? sprintf(' t2="%s"', $v['term_2']) : '';
					$str_is_term_t3 = ($is_term_t3) ? sprintf(' t3="%s"', $v['term_3']) : '';
					$str_is_term_id = ($is_term_id) ? sprintf(' id="%s"', $v['id']) : '';
					$str_is_term_uri = ($is_term_uri) ? sprintf(' uri="%s"', $v['term_uri']) : '';
					if ($vars['is_cmptblty_old'])
					{
						$str_is_term_t3 = '';
						$strQ .= sprintf('<term%s>%s</term>',
									$str_is_term_t1.$str_is_term_t2.$str_is_term_id, $v['term']
								);
					}
					else
					{
						$strQ .= sprintf('<term%s><![CDATA[%s]]></term>',
									$str_is_term_t1.$str_is_term_t2.$str_is_term_t3.$str_is_term_uri.$str_is_term_id, $v['term']
								);
					}
				}
				if ((($arPost['td_mode'] == 'td')||($arPost['td_mode'] == 'd'))&&($v['defn'] != ''))
				{
					if ($vars['is_cmptblty_old'])
					{
						$v['defn'] = str_replace(array('><![CDATA[', ']]><'), array('> ', ' <'), $v['defn']);
					}
					$strQ .= trim($v['defn']);
				}
				$strQ .= '</line>' . CRLF;
			}
			$strQ .= '</glossword>';
			$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
			$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
		}
		$this->str .= '</ul>';
		$oHtml->unsetTag('a');
	}
}
?>