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
	die('<!-- $Id: dicts_browse.inc.php 496 2008-06-14 06:42:53Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* The list of dictionaries */
$arSql =& $this->gw_this['ar_dict_list'];

/* The list of languages */
$languagelist =& $this->gw_this['vars']['ar_languages'];

/* The map of topics */
$ar =& $this->gw_this['ar_topics_list'];

/* The list of allowed dictionaries */
$ar_allowed_dicts = $this->oSess->user_get('dictionaries');

global $arTopicIDs, $arId;
$arDictMap = array();
$strGroupBy = 'tpname';
for (reset($arSql); list($arK, $arV) = each($arSql);)
{
	$arDictMap[$arV['id_topic']][$arK] = $arV;
}
/* Select the first topic by default */
if ( !$this->gw_this['vars']['w1'] )
{
	$arTopicIDs = array_keys($arDictMap);
	$arTopicIDs = array_reverse($arTopicIDs);
	if (isset($arTopicIDs[0]))
	{
		$this->gw_this['vars']['w1'] = $arTopicIDs[0];
	}
}

$cnt = 0;
$strSubtopics = '';
$arData = array();
$dict_nmax = 99;
if (isset($ar[0]['ch'])) // Root branch ->
{
	$tmp['int_parent_total'] = sizeof($ar[0]['ch']);
	for ($i0 = 1; $i0 <= $tmp['int_parent_total']; $i0++) // Root -> Topic
	{
		// count dictionaries
		$cnt_dict = 0;
		// keys for Root -> Topic
		$k = key($ar[0]['ch']);
		$arVar[$cnt]['tp_subparent'] = array();
		$arLevel2 = array();
		// if Root -> Topic -> Subtopic
		if (isset($ar[$k]['ch']))
		{
			$tmp['int_subparent_total'] = sizeof($ar[$k]['ch']);
			$cnt_sub = 0; // count subtopics
			while (is_array($ar[$k]['ch']) && list($k2, $v2) = each($ar[$k]['ch']))
			{
				if (($cnt_sub < $dict_nmax) || ($dict_nmax == 0))
				{
					// read a few subtopics...
					$ar[$k2]['title'] = ($this->gw_this['vars']['w1'] == $k2) ? '<strong>'.$ar[$k2]['title'].'</strong>' : $ar[$k2]['title'];
					$arVar[$cnt]['tp_subparent'][$cnt_sub]['non:tp_subparent'] = $this->oHtml->a(($this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&t='. GW_T_DICTS. '&w1='.$ar[$k2]['id']), $ar[$k2]['title']);
					$arVar[$cnt]['tp_subparent'][$cnt_sub]['txt_sep_subparent'] = ', ';
				}
				else // ...then exit from while()
				{
					continue;
				}
				$cnt_sub++;
			} // end with childs
			if ($cnt_sub == $tmp['int_subparent_total'])
			{
				$arVar[$cnt]['tp_subparent'][$cnt_sub-1]['txt_sep_subparent'] = '';
			}
		} // end of subtopics
		// now count the number of dictionairies in each topic
		$arId = array();
		$arTreeId = ctlgGetTree($ar, $k);
		$arTreeId[$k] = $k;
		while (is_array($arTreeId) && list($kn, $vn) = each($arTreeId))
		{
			if (isset($arDictMap[$kn]))
			{
				$cnt_dict += sizeof( $arDictMap[$kn] );
			}
		}
		/* 1.8.7: include all topics */
		$arVar[$cnt]['non:int_tp_parent_cnt'] = 0;
		$arVar[$cnt]['non:tp_parent'] = $ar[$k]['title'];
		if ($cnt_dict > 0)
		{
			$ar[$k]['title'] = ($this->gw_this['vars']['w1'] == $k) ? '<strong>'.$ar[$k]['title'].'</strong>' : $ar[$k]['title'];
			$arVar[$cnt]['non:tp_parent'] = $this->oHtml->a(($this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&t='.GW_T_DICTS. '&w1='.$k), $ar[$k]['title']);
			$arVar[$cnt]['non:int_tp_parent_cnt'] = $cnt_dict;
			$cnt++;
		}
		$strSubtopics = '';
		next($ar[0]['ch']);
	} // for
} // end of parsing childs for root level
$cnt = 0;

include_once( $this->sys['path_gwlib'] . '/class.cells_tpl.php' );
$oCells = new gw_cells_tpl();
$oCells->class_tpl = $this->sys['class_tpl'];
$oCells->tpl = 'tpl_cells_topic';
$oCells->id_theme = $this->gw_this['vars']['visualtheme'];
$oCells->arK = $arVar;
$oCells->X = 2;
$oCells->Y = 99;
$oCells->tBorder = 0;
$oCells->tSpacing = 2;
$oCells->tPadding = 2;
$oCells->tAttrClass = 'tbl-browse';
$this->str .= $oCells->output();
/* */
$arAlltopics = array();
$arId = array();
if ($this->gw_this['vars']['w1'] )
{
	$arAlltopics = ctlgGetTree( $ar, $this->gw_this['vars']['w1'] );
}
while (is_array($arAlltopics) && list($kp, $tp) = each ($arAlltopics))
{
	/* Topic selected */
	if (isset($arDictMap[$tp]) && is_array($arDictMap[$tp]))
	{
		$cnt_dict = 0;
		foreach ($arDictMap[$tp] as $k => $arV)
		{
			$is_allow_terms = 0;  
			if ( $this->oSess->is('is-sys-settings')
				|| $this->oSess->is('is-dicts')
				|| (isset($ar_allowed_dicts[$arV['id']]) && $this->oSess->is('is-dicts-own') )
			)
			{
				$is_allow_terms = 1; 
			}
			/* */
			$bgc = $cnt_dict % 2 ? $this->ar_theme['color_2'] : $this->ar_theme['color_1'];
			$menu = array();

			$this->str .= '<div style="border-top:1px #EEE solid;padding:5px;background:'.$bgc.';text-align:'.$this->sys['css_align_left'].'">';
			$this->str .= '<div class="xw">'.$arV['title'].'</div>';
			$this->str .= '<div class="xt">'.$arV['announce'].'</div>';
			$this->str .= '</div>';

			$this->str .= '<table style="border-bottom:1px #EEE solid;" border="0" width="100%" cellpadding="3" cellspacing="1">';
			$this->str .= '<tbody><tr style="background:'.$bgc.';text-align:'.$this->sys['css_align_left'].';vertical-align:top">';
			$this->str .= '<td style="width:1%" class="xt nobr"><span class="gray">ID: </span>'.$arV["id"].'</td>';
			$this->str .= '<td class="actions-third"><span>';
			/* 1.8.7: added link to search for all terms */
			($is_allow_terms) ? $menu[] = $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH .'&'. GW_TARGET.'='.GW_T_DICTS. '&q=*&srch[in]=1&id=' . $arV['id'], '…&#160;'.$this->oL->m('3_browse'), $this->oL->m('terms').': '.$this->oL->m('3_browse') ) : '';
			($is_allow_terms) ? $menu[] = $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT .'&'. GW_TARGET.'='.GW_T_DICTS. '&tid='.$arV['id'].'&id='.$arV['id'], '±&#160;'.$this->oL->m('3_edit'), $this->oL->m('1335').': '.$this->oL->m('3_edit') ) : '';
			($is_allow_terms) ? $menu[] = $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_ADD .'&'. GW_TARGET.'='.GW_T_TERMS .'&id='.$arV['id'], '+&#160;' . $this->oL->m('3_add_term'), $this->oL->m('terms').': '.$this->oL->m('3_add') ) : '';
			($is_allow_terms && $this->oSess->is('is-terms-export')) ? $menu[] = $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EXPORT .'&'. GW_TARGET.'='.GW_T_TERMS. '&id='.$arV['id'], $this->oL->m('3_export'), $this->oL->m('terms').': '.$this->oL->m('3_export')  ) : '';
			($is_allow_terms && $this->oSess->is('is-terms-import')) ? $menu[] = $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_IMPORT .'&'. GW_TARGET.'='.GW_T_TERMS. '&id='.$arV['id'], $this->oL->m('3_import'), $this->oL->m('terms').': '.$this->oL->m('3_import')  ) : '';

			$this->str .= implode(' ', $menu);
			$this->str .= '</span>';
			$this->str .= '</td>';

			/* The number of terms */
			$this->str .= '<td class="actions-third" style="width:20%;text-align:'.$this->sys['css_align_right'].'">';
			if ($arV['int_terms'])
			{
				$this->str .= $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arV['id'].'&q=*&srch[in]=103&t=dicts', '<span class="green">'.$this->oFunc->number_format($arV['int_terms'], 0, $this->oL->languagelist('4')).'</span>', $this->oL->m('1320'));
			}
			else
			{
				$this->str .= '<del title="'.$this->oL->m('1320').'">0</del>';
			}
			$this->str .= ' / ';
			if ($arV['int_terms_total']-$arV['int_terms'])
			{
				$this->str .= $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arV['id'].'&q=*&srch[in]=100&t=dicts', '<span class="red">'.$this->oFunc->number_format($arV['int_terms_total']-$arV['int_terms'], 0, $this->oL->languagelist('4')).'</span>', $this->oL->m('srch_7'));
			}
			else
			{
				$this->str .= '<del title="'.$this->oL->m('srch_7').'">0</del>';
			}
			$this->str .= ' / ';
			if ($arV['int_terms_total'])
			{
				$this->str .= $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arV['id'].'&q=*&srch[in]=1&t=dicts', $this->oFunc->number_format($arV['int_terms_total'], 0, $this->oL->languagelist('4')), $this->oL->m('total'));
			}
			else
			{
				$this->str .= '<del title="'.$this->oL->m('total').'">0</del>';
			}
			$this->str .= '</td>';
			/* Interface language */
			$this->str .= '<td class="xq" style="width:15%;text-align:center"><span class="gray">' . ($arV['lang'] ? $languagelist[$arV['lang']] : $languagelist[$this->sys['locale_name']] ) . "</span></td>";

			/* 1.8.7: Turn on/off */
			$href_onoff = $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&tid='.$arV['id'].'&id='.$arV['id'].'&w1='.$this->gw_this['vars']['w1'];
			$this->str .= '<td class="actions-third" style="width:1%;text-align:center">';
			if ($is_allow_terms)
			{
				$this->str .= ($arV['is_active'] 
							? $this->oHtml->a($href_onoff.'&mode=off', '<span class="green">'.$this->oL->m('is_1').'</span>')
							: $this->oHtml->a($href_onoff.'&mode=on', '<span class="red">'.$this->oL->m('is_0').'</span>', $this->oL->m('1057') ) );
			}
			else
			{
				$this->str .= '&#160;'. ($arV['is_active'] 
							? '<del><span class="green">'.$this->oL->m('is_1').'</span></del>'
							: '<del><span class="red">'.$this->oL->m('is_0').'</span></del>') . '&#160;';
			}
			$this->str .= '</td>';

			$this->str .= '</tr>';
			$this->str .= '</tbody></table>';
#			$this->str .= '<div style="font-size:1px;height:2px;background:'.$this->ar_theme['color_1'].'"></div>';
			++$cnt_dict;
		} // foreach $arDictMap
	} // isset parent
} // while
unset($ar);
unset($arDictMap);


if ($this->gw_this['vars']['tid'] == '')
{
	/* Last updated dictionaries */
	$this->str .= '<br />';
	$this->str .= gw_html_block_small(
			$this->oL->m('r_dict_updated'),
			getTop10('DICT_UPDATED', $this->sys['max_dict_top'], 1),
			0, 0);
}









?>