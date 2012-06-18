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
 * Glossword plug-in: Export to CSV
 * Location: glossword/inc/export_CSV/index.inc.php
 * by Dmitry N. Shilnikov <dev at glossword dot info>
 * @version $Id: index.inc.php 548 2008-08-15 16:08:17Z glossword_team $
 */
define('FORMAT_NAME', str_replace('export_', '', $arPost['fmt']));
define('FORMAT_EXT', 'txt');
/**
 *
 */
function getFormCsv($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $sys, $oL, $arPost, $arSplit, $arDictParam, $oFunc, $oSess, $ar_theme, $gw_this;
	$strForm = "";
	$trClass = "xt";
	$oForm = new gwForms();
	$oForm->action = append_url($sys['page_admin']);
	$oForm->submitok = $oL->m('3_next').' &gt;';
	$oForm->submitdel = $oL->m('3_remove');
	$oForm->submitcancel = $oL->m('3_cancel');
	$oForm->formbgcolor = $ar_theme['color_2'];
	$oForm->formbordercolor = $ar_theme['color_4'];
	$oForm->formbordercolorL = $ar_theme['color_1'];
	$oForm->Set('charset', $sys['internal_encoding']);
	$oForm->arLtr = array('arPost[split]');
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while(is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = "";
		if (isset($arReq[$key])) { $arReqMsg[$key] = ' <span style="color:#E30"><b>*</b></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="'.$trClass.'" style="color:#E30"><b>' . $oL->m('reason_9') .'</b></span>'; }
	}
	## ----------------------------------------------------
	$oForm->setTag('select', 'class', 'input');

	$strForm = '';
	$strForm .= '<table width="100%">';
	$strForm .= '<tbody><tr style="vertical-align:top"><td style="width:50%">';

		$oForm->setTag('input', 'class', '');
		$oForm->setTag('input', 'size', '6');
		$oForm->setTag('input', 'maxlength', '5');
		$oForm->setTag('input', 'dir', 'ltr' );

		$strForm .= getFormTitleNav($oL->m('3_export'), '<span class="xr">'.FORMAT_NAME.'</span>');
		$strForm .= '<table class="gw2TableFieldset" width="100%">';
		$strForm .= '<tbody><tr><td style="width:11%"></td><td></td></tr>';

#		$strForm .= '<tr>'.
#					'<td class="td1">' . $arBrokenMsg['str_separator'] . $oForm->field('input', 'arPost[str_separator]', htmlspecialchars($vars['str_separator'])) . '</td>'.
#					'<td class="td2">' . $oL->m('1119') . $arReqMsg['str_separator'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					'<td class="td1">' . $arBrokenMsg['str_enclosed'] . $oForm->field('input', 'arPost[str_enclosed]', htmlspecialchars($vars['str_enclosed'])) . '</td>'.
#					'<td class="td2">' . $oL->m('1120') . $arReqMsg['str_enclosed'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					'<td class="td1">' . $arBrokenMsg['str_separator_defn'] . $oForm->field('input', 'arPost[str_separator_defn]', htmlspecialchars($vars['str_separator_defn'])) . '</td>'.
#					'<td class="td2">' . $oL->m('1122') . $arReqMsg['str_separator_defn'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					'<td class="td1">' . $arBrokenMsg['str_escaped'] . $oForm->field('input', 'arPost[str_escaped]', htmlspecialchars($vars['str_escaped'])) . '</td>'.
#					'<td class="td2">' . $oL->m('1121') . $arReqMsg['str_escaped'] . '</td>'.
#					'</tr>';
#		$strForm .= '<tr>'.
#					 '<td class="td1">' . $oForm->field('checkbox', "arPost[is_read_first]", $vars['is_read_first']) . '</td>'.
#					 '<td class="td2"><label for="arPost_is_read_first_">' . $oL->m('1123') . '</label></td>'.
#					 '</tr>';

		$arTmp['id'] = 'arPost-t';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field("radio", "arPost[td_mode]", 't', ($vars['td_mode'] == 't'), $arTmp) . '</td>'.
					'<td class="td2"><label for="arPost-t" style="cursor:pointer;">'. $oL->m('terms') .'</label></td>'.
					'</tr>';
		$arTmp['id'] = 'arPost-d';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field("radio", "arPost[td_mode]", 'd', ($vars['td_mode'] == 'd'), $arTmp) . '</td>'.
					'<td class="td2"><label for="arPost-d" style="cursor:pointer;">'. $oL->m('definitions') .'</label></td>'.
					'</tr>';
		$arTmp['id'] = 'arPost-td';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field("radio", "arPost[td_mode]", 'td', (($vars['td_mode'] == 'td')?1:0), $arTmp) . '</td>'.
					'<td class="td2"><label for="arPost-td" style="cursor:pointer;">'. $oL->m('terms'). ' &amp; ' . $oL->m('definitions') .'</label></td>'.
					'</tr>';

		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_term_t1]', $vars['is_term_t1']) . '</td>'.
					'<td class="td2"><label for="arPost_is_term_t1_">' . $oL->m('alphabetic_order') . ' 1</label></td>'.
					'</tr>';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_term_t2]', $vars['is_term_t2']) . '</td>'.
					'<td class="td2"><label for="arPost_is_term_t2_">' . $oL->m('alphabetic_order') . ' 2</label></td>'.
					'</tr>';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_term_t3]', $vars['is_term_t3']) . '</td>'.
					'<td class="td2"><label for="arPost_is_term_t3_">' . $oL->m('alphabetic_order') . ' 3</label></td>'.
					'</tr>';
		$strForm .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_term_id]', $vars['is_term_id']) . '</td>'.
					'<td class="td2"><label for="arPost_is_term_id_">' . $oL->m('xml_is_term_id') . '</label></td>'.
					'</tr>';



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
			<td class="td1">' . $oForm->field('radio', 'arPost[split_m]', 'list', $vars['is_list1'], $arBoxId) . '</td>
			<td class="td2"><label id="labelList" for="split_list1">'.$oL->m('dictdump_list').'</label></td>
			</tr>';
		$arSplit = array ('100' => '100', '500' => '500', '1000' => '1000', '2500' => '2500', '5000' => '5000');
		$strForm .= '
	  		<tr>
			<td></td>
			<td class="td2">'. $oForm->field('select', 'arPost[split1]', $vars['intsplit'], '100%', $arSplit). '
			</td>
			</tr>';
		$arBoxId['id'] = 'split_custom';
		$arBoxId['onchange'] = 'checkSplit()';
		$strForm .= '
			<tr>
			<td class="td1">' . $oForm->field('radio', 'arPost[split_m]', 'custom', $vars['is_list2'], $arBoxId) . '</td>
			<td class="td2"><label id="labelCustom" for="split_custom">'.$oL->m('dictdump_custom').'</label></td>
			</tr>';
		$strForm .= '
			<tr>
			<td></td>
			<td class="td2">'. $oForm->field('input', "arPost[split2]", $vars['int_terms'], '100%', $arSplit). '
			</td>
			</tr>';
		$strForm .= '</tbody></table>';

		$arBoxId = array();

	$strForm .= '</td>';
	$strForm .= '</tr>';
	$strForm .= '</tbody></table>';

	$filename = $sys['path_addon'].'/'.$gw_this['vars'][GW_TARGET].'/export-'.$vars['fmt'].'/index.js.php';
	include($filename);
	$strForm .= $oForm->field("hidden", 'arPost[fmt]', $vars['fmt']);
	$strForm .= $oForm->field("hidden", 'arPost[int_terms]', $vars['int_terms']);
	$strForm .= $oForm->field("hidden", 'arPost[min]', $vars['min']);
	$strForm .= $oForm->field("hidden", 'arPost[max]', $vars['max']);
	$strForm .= $oForm->field("hidden", 'w1', '3');
	$strForm .= $oForm->field("hidden", 'id', $gw_this['vars']['id']);
	$strForm .= $oForm->field("hidden", GW_TARGET, GW_T_TERMS);
	$strForm .= $oForm->field("hidden", GW_ACTION, GW_A_EXPORT);
	$strForm .= $oForm->field("hidden", $oSess->sid, $oSess->id_sess);
	return $oForm->Output($strForm);
}
// --------------------------------------------------------
global $oL, $gw_this, $oSess, $oDb, $oSqlQ, $oHtml, $sys, $arFields;
$arReq = array('fmt');
// Language
$oL->getCustom('export_csv', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
// --------------------------------------------------------
// split per lines
$is_split   = isset($arPost['is_split']) ? $arPost['is_split'] : 100;
$is_list1   = ( isset($arPost['is_list1']) && $arPost['is_list1'] == "list" ) ? 1 : 0;
$is_list2   = ( isset($arPost['is_list2']) && $arPost['is_list2'] == "custom" ) ? 1 : 0;
if (!($is_list1)&&!($is_list2) ) { $is_list1 = 1; }
$is_term_t1   = isset($arPost['is_term_t1']) ? $arPost['is_term_t1'] : 0;
$is_term_t2   = isset($arPost['is_term_t2']) ? $arPost['is_term_t2'] : 0;
$is_term_t3   = isset($arPost['is_term_t3']) ? $arPost['is_term_t3'] : 0;
$is_term_id   = isset($arPost['is_term_id']) ? $arPost['is_term_id'] : 0;
//
$intSplit = 500;
//
if (!isset($arPost[GW_TARGET])) { $post = ''; }
if ($this->gw_this['vars']['w1'] == '2')
{
	/** not saved */
#	$vars['str_separator'] = ';';
#	$vars['str_separator_defn'] = ' ;; ';
#	$vars['str_enclosed'] = '"';

	/* Get the number of terms */
	$arSql = $oDb->sqlRun($oSqlQ->getQ('cnt-dict-date', $arDictParam['tablename'], $vars['min'], $vars['max']));
	$vars['int_terms'] = isset($arSql['0']['n']) ? $arSql['0']['n'] : 0;

	$vars['fmt'] = $arPost['fmt'];
	$vars['intsplit'] = $intSplit;
	$vars['is_list1'] = $is_list1;
	$vars['is_list2'] = $is_list2;
	$vars['td_mode'] = 'td';
	$vars['is_term_t1'] = 1;
	$vars['is_term_t2'] = 1;
	$vars['is_term_t3'] = 1;
	$vars['is_term_id'] = 1;
	$this->str .= getFormCsv($vars, 0, 0, $arReq);
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
				$this->sys['path_export'] . '/' . @date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).
				'id_'.$this->gw_this['vars']['id'].'_'.$arDictParam['tablename'],
				$cntFiles, FORMAT_EXT
			 );

		if ($arDictParam['int_terms'] == 0)
		{
			return;
		}
		$this->str .= '<ul class="xt">';
		/* Export in progress */
		$oHtml->setTag('a', 'target', '_blank');
		for ($i = 0; $i < $cntFiles; $i++)
		{
			$limit = $oDb->prn_limit($arDictParam['int_terms'], $i + 1,  $int_split);
			$filename = sprintf($fileS, ($i + 1), $cntFiles);
			$strQ = '';
			$this->str .= '<li><span class="gray">';
			$this->str .= $oHtml->a($filename, $filename);
			$this->str .= '</span>&#8230; ';
			$sql = $oSqlQ->getQ('get-term-export', $arDictParam['tablename'], $sqlWhereDate, $limit);
			$arSql = $oDb->sqlExec($sql);
#			prn_r( $sql );
			/* */
			/* MS format */
#			$str_enclose_append = '"';
#			$str_enclose_prepend = '"';
#			$str_split = ";";
#			$str_split_defn = " ;; ";
			/* Plain format */
			$str_enclose_append = '';
			$str_enclose_prepend = '';
			$str_split = "\t";
			$str_split_defn = " ;; ";
			/* Custom format */
#			$str_enclose_append =& $arPost['str_enclosed'];
#			$str_enclose_prepend =& $arPost['str_enclosed'];
#			$str_split =& $arPost['str_separator'];
#			$str_split_defn =& $arPost['str_separator_defn'];
			/* Fix escape sequences */
			$ar_esc = array('\r\n' => "\r\n", '\n' => "\n", '\r' => "\r", '\t' => "\t");
			$str_split = str_replace(array_keys($ar_esc), array_values($ar_esc), $str_split);
			$str_split_defn = str_replace(array_keys($ar_esc), array_values($ar_esc), $str_split_defn);
			/* Microsoft format*/
			$is_ms = 0;
			if ($str_enclose_append == '"')
			{
				$is_ms = 1;
			}
			$oDom = new gw_domxml;
			$arCSV = array();
			/* Add header to CSV-file */
			$incr_term = 0;
#			$arCSV[-1] = 'id';
			$arDictParam['is_term'] = 1;
			for (reset($arFields); list($fK, $fV) = each($arFields);)
			{
				$arCSV[$fV[5]] = $fV[0];
			}
			$arCSV[5] = 'term_uri';
			ksort($arCSV);
			$strQ .= $str_enclose_prepend.implode($str_enclose_append.$str_split.$str_enclose_prepend, $arCSV).$str_enclose_append;
			$strQ .= CRLF;
			$arCSV = array();
			$arDuplicates = array(array());
			/* */
			for (reset($arSql); list($k, $arV) = each($arSql);)
			{
				$incr_term = $k;
				$strQ .= '';
				$arPre = array();
				$arDuplicates = array(array());
				/* Init. */
				for (reset($arFields); list($fK, $fV) = each($arFields);)
				{
					$arCSV[$incr_term][$fV[5]] = '';
				}
				/* Term */
				$arCSV[$incr_term][$arFields[-1][5]] = ($is_term_id) ? $arV['id'] : '';
				$arCSV[$incr_term][$arFields[1][5]] = text_parse_csv_column($arV['term'], $is_ms);
				$arCSV[$incr_term][$arFields[-2][5]] = ($is_term_t1) ? text_parse_csv_column($arV['term_1'], $is_ms) : '';
				$arCSV[$incr_term][$arFields[-3][5]] = ($is_term_t2) ? text_parse_csv_column($arV['term_2'], $is_ms) : '';
				$arCSV[$incr_term][$arFields[-4][5]] = ($is_term_t3) ? text_parse_csv_column($arV['term_3'], $is_ms) : '';
				$arCSV[$incr_term][5] = $arV['term_uri']; /* Hard-coded, see constants.inc.php */
				/* */
				$str_xml = '<term>'.$arV['term'].'</term>';
				$arPre = array_merge_clobber($arPre, gw_Xml2Array($str_xml.$arV['defn']));
				for (reset($arFields); list($fK, $fV) = each($arFields);)
				{
					if ($arPost['td_mode'] == 't')
					{
						continue;
					}
					if ($arPost['td_mode'] == 'd')
					{
						$arV['term'] = '';
						$arCSV[$incr_term][0] = '';
					}
#					if (!isset($arPre[$fV[0]])) { continue; }
					if (!isset($arPre[$fV[0]])) { $arPre[$fV[0]][0] = array(array('value' => '' , 'attributes' => array())); }
					$tmpStr = trim($oDom->get_content( $arPre[$fV[0]] ));
#					if (trim($tmpStr) == ''){ continue; }
					switch ($fV[0])
					{
						case 'defn':
							$tmpf = array();
							for (reset($arPre[$fV[0]]); list($kfV, $vfV) = each($arPre[$fV[0]]);)
							{
								/* Sometimes there are no definitions */
								if ($kfV == 0)
								{
									$arCSV[$incr_term][$fV[5]] .= @text_parse_csv_column( $vfV['value'] );
								}
								else
								{
									$arDuplicates[$incr_term][$kfV][$fV[5]] = $vfV['value'];
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
								$str_attributes = '';
								if (isset($vvfV['attributes']))
								{
									$str_attributes = implode(', ', $vvfV['attributes']);
									$str_attributes = ($str_attributes == '') ? '' : '('.$str_attributes.') ';
								}
								$ar_vvfV[] = strval($str_attributes).$vvfV['value'];
							}
							if ($kfV == 0)
							{
								$arCSV[$incr_term][$fV[5]] .= text_parse_csv_column( implode($str_split_defn, $ar_vvfV) );
							}
							else
							{
								$arDuplicates[$incr_term][$kfV][$fV[5]] = implode($str_split_defn, $ar_vvfV);
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
							  	$str_attributes = '';
								if (isset($vvfV['attributes']) && !empty($vvfV['attributes']) )
								{
									$str_attributes .= '(';
									$str_attributes .= isset($vvfV['attributes']['is_link']) ? '1' : '';
									$str_attributes .= isset($vvfV['attributes']['text']) ? ', '. $vvfV['attributes']['text'] : '';
									$str_attributes .= ') ';
								}
								$ar_vvfV[] = $str_attributes . text_parse_csv_column($vvfV['value']);
							}
							if ($kfV == 0)
							{
								$arCSV[$incr_term][$fV[5]] .= text_parse_csv_column( implode($str_split_defn, $ar_vvfV) );
							}
							else
							{
								$arDuplicates[$incr_term][$kfV][$fV[5]] = implode($str_split_defn, $ar_vvfV);
							}
						}
						break;
						case 'trsp':
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
								$arCSV[$incr_term][$fV[5]] .= text_parse_csv_column( implode($str_split_defn, $ar_vvfV) );
							}
							else
							{
								$arDuplicates[$incr_term][$kfV][$fV[5]] = implode($str_split_defn, $ar_vvfV);
							}
						}
						break;
					}
				} /* end of $arFields */
				ksort($arCSV[$incr_term]);
#prn_r( $arCSV  );
#exit;
				$strQ .= $str_enclose_prepend.implode($str_enclose_append.$str_split.$str_enclose_prepend, $arCSV[$incr_term]).$str_enclose_append;
				$strQ .= CRLF;
				/* Secondary definitions */
				if (!empty($arDuplicates[$incr_term]))
				{
					for (reset($arDuplicates[$incr_term]); list($kD, $arVd) = each($arDuplicates[$incr_term]);)
					{
						for (reset($arVd); list($dKv, $dVv) = each($arVd);)
						{
							$arVd[$dKv] = text_parse_csv_column($dVv);
						}
						/* fill empty values for secondary definitions */
						for (reset($arFields); list($fK, $fV) = each($arFields);)
						{
							if (!isset($arVd[$fV[5]]))
							{
								$arVd[$fV[5]] = '';
							}
						}
						$arVd[-1] = ($is_term_id) ? $arV['id'] : '';
						$arVd[0] = $arV['term'];
						$arVd[1] = '';
						$arVd[2] = '';
						$arVd[3] = '';
						ksort($arVd);
						$strQ .= $str_enclose_prepend.implode($str_enclose_append.$str_split.$str_enclose_prepend, $arVd).$str_enclose_append;
						$strQ .= CRLF;
					}
				}
			}
			$isWrite = $this->oFunc->file_put_contents( $filename, $strQ, $mode);
			$this->str .= ( $isWrite ?  '<span class="green">OK</span> (' . $this->oFunc->number_format(strlen($strQ), 0, $oL->languagelist('4')) . " " . $oL->m('bytes') . ')' : $oL->m('error') ) . '</li>';
		}
		$this->str .= '</ul>';
		$oHtml->unsetTag('a');
	}
}
/* */
function text_parse_csv_column($t, $is_ms = 0)
{
	if ($is_ms)
	{
		$t = str_replace('"', '""', $t);
	}
	$t = str_replace('\r\n', '\\n', $t);
	$t = str_replace('\n', '\\n', $t);
	$t = str_replace('\r', '\\r', $t);
	$t = str_replace('\t', '\\t', $t);

	$t = str_replace("\r\n", '\n', $t);
	$t = str_replace("\n", '\n', $t);
	$t = str_replace("\r", '\r', $t);
	$t = str_replace("\t", '\t', $t);

	$t = str_replace('<![CDATA[', '', $t);
	$t = str_replace(']]>', '', $t);

	return $t;
}

/* End of file */
?>