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
	die('<!-- $Id: class.render.php 550 2008-08-17 16:16:26Z glossword_team $ -->');
}
/**
 *  Coverts array structure into XML or HTML code.
 *
 *  @todo   normalize raw functions.
 */
// --------------------------------------------------------


class gw_render extends gw_htmlforms
{
	var $is_html_preview = 0;
	var $tag_abbr   = 'acronym'; // 'abbr' for XHTML 2.0
	var $tag_trns   = 'acronym'; // 'abbr' for XHTML 2.0
	// <trsp> tag
	var $prepend_trsp  = '<span class="trsp">[';
	var $split_trsp    = '; ';
	var $append_trsp  = ']</span>';
	// <abbr> tag
	var $prepend_abbr  = '';
	var $postlang_abbr = '&#32;';
	var $split_abbr    = '; <br />';
	var $append_abbr   = '<br />';
	// <trns> tag
	var $prepend_trns  = '';
	var $postlang_trns = '&#32;';
	var $split_trns    = '; <br />';
	var $append_trns   = '';
	// <usg> tag
	var $prepend_usg   = '<dl><dt>';
	var $split_usg     = '</dt><dt>';
	var $append_usg    = '</dt></dl>';
	// <see> and <syn> tags
	var $prepend_see   = '';
	var $split_see     = ', ';
	var $append_see    = '';
	// other tags
	var $split_src     = ', ';
	var $split_address = ', ';
	var $split_phone   = ', ';
	var $tag_stress_rule  = '<span class="stress">|</span>';
	// internal switches, do not change
	var $is_br_begin   = 0;
	var $is_plural     = 0;
	// available functions
	var $arFuncList = array(
					   'make_xml_term' => 1, 'make_xml_trsp' => 1, 'make_xml_defn' => 1,
					   'make_xml_abbr' => 1, 'make_xml_trns' => 1, 'make_xml_audio' => 1,
					   'make_xml_syn'  => 1, 'make_xml_antonym' => 1, 'make_xml_see'  => 1,
					   'make_xml_usg'  => 1, 'make_xml_src'  => 1, 'make_xml_address' => 1, 'make_xml_phone'  => 1
					  );
	var $arFuncList_html = array(
					   'make_html_term' => 1, 'make_html_trsp' => 1, 'make_html_defn' => 1,
					   'make_html_abbr' => 1, 'make_html_trns' => 1, 'make_html_audio' => 1,
					   'make_html_syn'  => 1, 'make_html_antonym' => 1, 'make_html_see'  => 1,
					   'make_html_usg'  => 1, 'make_html_src'  => 1, 'make_html_address' => 1, 'make_html_phone'  => 1
					  );
	/* Autoexec */
	function gw_render()
	{
		global $oSess, $oDb, $oSqlQ, $oL, $oHtml, $oFunc, $oTpl;
		global $sys, $gw_this, $ar_theme;
		$this->oSess =& $oSess;
		$this->oFunc =& $oFunc;
		$this->oHtml =& $oHtml;
		$this->oDb =& $oDb;
		$this->oSqlQ =& $oSqlQ;
		$this->oL =& $oL;
		$this->oTpl =& $oTpl;
		$this->sys =& $sys;
		$this->ar_theme =& $ar_theme;
	}
	function tag2field_xml($fieldname, $ar = array())
	{
		$fieldname = strtolower($fieldname);
		$funcname = 'make_xml_' . $fieldname;
		if (!isset($this->arEl[$fieldname]))
		{
			$this->arEl[$fieldname] = array('value' => '');
		}
		if (isset($this->arFuncList[$funcname]) && $this->arFuncList[$funcname])
		{
			return $this->$funcname($fieldname, $ar);
		}
	}
	function tag2field_html($fieldname, $ar = array())
	{
		$fieldname = strtolower($fieldname);
		$funcname = 'make_html_' . $fieldname;
		if (isset($this->arFuncList_html[$funcname]) && $this->arFuncList_html[$funcname])
		{
			return $this->$funcname($fieldname, $ar);
		}
	}

	//
	function make_xml_term($fieldname, $ar = array())
	{
#		$s = $this->objDom->get_content($this->arEl[$fieldname][0]);
#		return $s;
	}
	function make_html_term($fieldname, $ar = array())
	{
		return '';
	}
	//
	function make_xml_trsp($fieldname, $ar = array(), $tag = 'trsp')
	{
		return $this->make_xml_set_array2textarea($fieldname, $ar, 'trsp');
	}
	//
	function make_html_trsp($fieldname, $ar = array(), $tag = 'trsp')
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		$split_trsp = $this->ar_theme['split_trsp'];
		$prepend_trsp = $this->ar_theme['prepend_trsp'];
		$append_trsp = $this->ar_theme['append_trsp'];
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('link' => ''));
		}
		$tmp['ar_compiled'] = array();
		$i = 0;
		//
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			if ($elV['value'] != '')
			{
				$i++;
				$delimeter = ($i == 1) ? '' : $split_trsp;
				$tmp['strxml'] .= $delimeter . $elV['value'];
			}
		}
		if ($tmp['strxml'] != '')
		{
			$tmp['strxml'] = '<div title="'.$this->oL->m($tag).'" class="gw'.$fieldname.'">' . $prepend_trsp . $tmp['strxml'] . $append_trsp.'</div>';
		}
		return $tmp['strxml'];
		
	}
	/* 26 feb 2008: attached files */
	function make_html_audio($fieldname, $ar = array(), $tag = 'audio')
	{
		$tmp['strhtml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('size' => 0));
		}
		//
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			//
			$tmp['size'] = isset($chV['attributes']['size']) ? $chV['attributes']['size'] : 0;
			$tmp['str'] = $this->objDom->get_content( $chV );
			$tmp['str'] = urlencode($tmp['str']);
			if ($tmp['size'])
			{
				$tmp['strhtml'] .= '<span class="gw-attach"><a href="'.$this->sys['server_dir'].'/'.$this->sys['path_temporary'].'/a/'.$tmp['str'];
				$tmp['strhtml'] .= '" title="'.$this->oFunc->number_format($tmp['size'], 0, $this->oL->languagelist('4')).' '.$this->oL->m('bytes').'">'.$this->oL->m('audio').'</a> ';
				$tmp['strhtml'] .= '</span>';
			}
			$tmp['str'] = '';
		}
		return $tmp['strhtml'];
	}
	function make_xml_audio($fieldname, $ar = array(), $tag = 'audio')
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('size' => 0));
		}
		/* */
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			//
			$tmp['size'] = isset($chV['attributes']['size']) ? $chV['attributes']['size'] : 0;
			$tmp['str'] = $this->objDom->get_content( $chV );
			$tmp['str'] = gw_fix_input_to_db($tmp['str']);
			if ($tmp['size'])
			{
				$tmp['strxml'] .= '<'.$tag;
				$tmp['strxml'] .= ' size="'.$tmp['size'].'"';
				$tmp['strxml'] .= '><![CDATA[';
				$tmp['strxml'] .= $tmp['str'];
				$tmp['strxml'] .= ']]></'.$tag.'>';
			}
			$tmp['str'] = '';
		}
		return $tmp['strxml'];
	}
	/* */
	function make_xml_defn($fieldname, $ar = array())
	{
		$tmp['strxml'] = '';

		// for each definition
		while (list($elK, $elV) = each($this->arEl[$fieldname]))
		{
			// get definition content
			$tmp['strxml'] .= '<defn>';
			//
			// Parse subtags, abbr + trns
			$arTmp['elK'] = $elK;
			if ($this->arDictParam['is_trsp'])
			{
				$tmp['strxml'] .= $this->tag2field_xml('trsp', $arTmp);
			}
			if ($this->arDictParam['is_audio'])
			{
				$tmp['strxml'] .= $this->tag2field_xml('audio', $arTmp);
			}
			if ($this->arDictParam['is_abbr'])
			{
				$tmp['strxml'] .= $this->tag2field_xml('abbr', $arTmp);
			}
			if ($this->arDictParam['is_trns'])
			{
				$tmp['strxml'] .= $this->tag2field_xml('trns', $arTmp);
			}
			/* */
			$elV['value'] = gw_fix_input_to_db($elV['value']);
			//
			$tmp['strxml'] .= '<![CDATA['.$elV['value'].']]>';
			//
			// Parse subtags
			for (reset($this->arFields); list($fK, $fV) = each($this->arFields);)
			{
				// not root elements only
				if ((!isset($fV[4]) || !$fV[4]) && 
					(isset($this->arDictParam['is_'.$fV[0]]) && $this->arDictParam['is_'.$fV[0]])
					&& ($fV[0] != 'abbr' && $fV[0] != 'trns' && $fV[0] != 'trsp' 
					&& $fV[0] != 'file' && $fV[0] != 'audio' && $fV[0] != 'img' && $fV[0] != 'video'))
				{
					$tmp['strxml'] .= $this->tag2field_xml($fV[0], $arTmp);
				}
			}
			$tmp['strxml'] .= '</defn>';
			$tmp['strxml'] = str_replace('<defn><![CDATA[]]></defn>', '', $tmp['strxml']);
#prn_r( $tmp, __FILE__.__LINE__  );
		}
		return $tmp['strxml'];
	}
	function make_html_defn($fieldname, $ar = array())
	{
		$tmp['strhtml'] = $tmp['br'] = '';
		//
		// more than one definition
		$this->is_plural = 0;
		if (!isset($this->arEl[$fieldname][0])) { $this->arEl[$fieldname][0] = ''; }
		if (sizeof($this->arEl[$fieldname]) > 1) { $this->is_plural = 1; }
		$tmp['strhtml'] .= $this->is_plural ? '<ol class="defnblock">' : '<div class="defnblock">';
		//
		// for each definition
		if (!is_array($this->arEl[$fieldname][0]))
		{
			$this->arEl[$fieldname][0] = array('value' => '');
		}
		//
		while (list($elK, $elV) = each($this->arEl[$fieldname]))
		{
			// get definition contents
			$arTmp['elK'] = $elK;
			$tmp['strhtml'] .= ($this->is_plural ? '<li>' : '');
			//
			// Parse subtags, abbr + trns
			$tmp['str_abbr'] = $tmp['str_trns'] = '';
			//
			// add <br /> tag before definitions
#			$this->Set('is_br_begin', 0);
			//
			if ($this->arDictParam['is_trsp'])
			{
				$tmp['strhtml'] .= $this->tag2field_html('trsp', $arTmp);
			}
			if ($this->arDictParam['is_audio'])
			{
				$tmp['strhtml'] .= $this->tag2field_html('audio', $arTmp);
			}
			if ($this->arDictParam['is_abbr'])
			{
				$tmp['str_abbr'] = $this->tag2field_html('abbr', $arTmp);
				$tmp['strhtml'] .= $tmp['str_abbr'];
			}
			if ($this->arDictParam['is_trns'])
			{
				$tmp['str_trns'] = $this->tag2field_html('trns', $arTmp);
				$tmp['strhtml'] .= $tmp['str_trns'];
			}
			//
#			$tmp['br'] = ($this->is_br_begin || $this->is_plural) ? '<br/>' : '';
			//
			$tmp['strhtml'] .= ($elV['value'] != '') ? $tmp['br'] . '<div class="defn">'.$elV['value'].'</div>' : '';
			/* Parse subtags */
			for (reset($this->arFields); list($fK, $fV) = each($this->arFields);)
			{
				/* Not root elements only */
				if ((!isset($fV[4]) || !$fV[4]) && 
					(isset($this->arDictParam['is_'.$fV[0]]) && $this->arDictParam['is_'.$fV[0]])
					&& ($fV[0] != 'abbr' && $fV[0] != 'trns' && $fV[0] != 'trsp' 
					&& $fV[0] != 'file' && $fV[0] != 'audio' && $fV[0] != 'img' && $fV[0] != 'video'))
				{
					$tmp['strhtml'] .= $this->tag2field_html($fV[0], $arTmp);
				}
			}
			$tmp['strhtml'] .= ($this->is_plural ? '</li>' : '');
		}
		$tmp['strhtml'] .= $this->is_plural ? '</ol>' : '</div>';
		/* Modification date */
		if ($this->arDictParam['is_show_date_modified'])
		{
			$tmp['strhtml'] .= '<div class="defnnote">';
			$tmp['strhtml'] .= sprintf('<span class="gray">%s:</span> %s', $this->oL->m('date_modif'), date_extract_int($this->Gtmp['date_modified'], "%d %F %Y") );
			$tmp['strhtml'] .= '</div>';
		}
		/* Authors */
		if ($this->arDictParam['is_show_authors'])
		{
			global $gw_this;
			$id_term = ($this->arDictParam['is_show_full']) ? $this->Gtmp['tid'] : $gw_this['vars']['id_term'];
			$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-users-by-term_id', $id_term));
			$ar_authors = array();
			for (; list($k, $arV) = each($arSql);)
			{
				$ar_authors[] = $this->oHtml->a($this->sys['page_index'].'?'.GW_ACTION.'='.GW_A_PROFILE.'&t=view&id='.$arV['id_user'], $arV['user_name']);
			}
			$tmp['str_authors'] = implode(', ', $ar_authors);
			$tmp['strhtml'] .= '<div class="defnnote">';
			$tmp['strhtml'] .= sprintf('<span class="gray">%s:</span> %s', $this->oL->m('1112'), $tmp['str_authors'] );
			$tmp['strhtml'] .= '</div>';
		}
		return $tmp['strhtml'];
	}
	/* */
	function make_xml_abbr($fieldname, $ar = array(), $tag = 'abbr')
	{
		$tmp['strxml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		//
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			//
			$tmp['attributes'] = $this->objDom->get_attribute('lang', '', $chV);
			$tmp['attributes'] = ($tmp['attributes'] == '') ? '--' : $tmp['attributes'];
			$tmp['str'] = $this->objDom->get_content( $chV );
			$tmp['str'] = gw_fix_input_to_db($tmp['str']);
			//
			if (($tmp['str'] != '') || $tmp['attributes'] != '--')
			{
				$tmp['strxml'] .= '<'.$tag;
				$tmp['strxml'] .= ($tmp['attributes'] == '--') ? '' : ' lang="'.$tmp['attributes'].'"';
				$tmp['strxml'] .= '><![CDATA[';
				$tmp['strxml'] .= $tmp['str'];
				$tmp['strxml'] .= ']]></'.$tag.'>';
			}
		}
		return $tmp['strxml'];
	}
	function make_xml_trns($fieldname, $ar = array(), $tag = 'trns')
	{
		$tmp['strxml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		// do auto fill
		//
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		//
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			//
			$tmp['attributes'] = $this->objDom->get_attribute('lang', '', $chV);
			$tmp['attributes'] = ($tmp['attributes'] == '') ? '--' : $tmp['attributes'];
			$tmp['str'] = $this->objDom->get_content( $chV );
			$tmp['str'] = gw_fix_input_to_db($tmp['str']);
			//
			if (($tmp['str'] != '') || $tmp['attributes'] != '--')
			{
				$tmp['strxml'] .= '<'.$tag;
				$tmp['strxml'] .= ($tmp['attributes'] == '--') ? '' : ' lang="'.$tmp['attributes'].'"';
				$tmp['strxml'] .= '><![CDATA[';
				$tmp['strxml'] .= $tmp['str'];
				$tmp['strxml'] .= ']]></'.$tag.'>';
			}
		}
		return $tmp['strxml'];
	}
	/* HTML-code for abbreviations */
	function make_html_abbr ($fieldname, $ar = array(), $tag = 'abbr')
	{
		global $oDb, $oSqlQ;
		global $gw_this, $arDictParam;
		$tmp['strhtml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();

		$prepend_name = $this->ar_theme['prepend_'.$tag];
		$split_name = $this->ar_theme['split_'.$tag];
		$append_name = $this->ar_theme['append_'.$tag];
		$postlang_name = $this->ar_theme['postlang_'.$tag];

		$tmp['attributes'] = array();
		/* Collect lang codes */
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			$tmp['attributes'][$chK] = $this->objDom->get_attribute('lang', '', $chV);
		}
		$arSql = array();
		$sql_in = implode("', '", $tmp['attributes']);
		if ($sql_in != '')
		{
			$arSql = $oDb->sqlExec($oSqlQ->getQ('get-abbr-code', 
				$gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 
				'AND a.id_abbr IN (\''. $sql_in .'\')'), 
				'st');
		}
		/* Color by default */
		$ar_abbr_groups = array('' => 1);
		while (list($k, $arV) = each($arSql))
		{
			$id_abbr = sprintf('%03d', $arV['id_abbr']);
			if ($arDictParam['is_abbr_long'])
			{
				$tmp['abbr0'][$id_abbr] = $arV['abbr_long'];
				$tmp['abbr1'][$id_abbr] = $arV['abbr_short'];
			}
			else
			{
				$tmp['abbr1'][$id_abbr] = $arV['abbr_long'];
				$tmp['abbr0'][$id_abbr] = $arV['abbr_short'];
			}
			$ar_abbr_groups[$id_abbr] = $arV['id_group'];
		}
		$arSql = array();
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		/* */
		$tmp['ar_compiled'] = array();
		$i = 0;
		reset($tmp['arEl']);
		while (list($chK, $chV) = each($tmp['arEl']))
		{
			$i++;
			$tmp['str_title'] = $tmp['str_acronym'] = '';
			$tmp['str'] = $this->objDom->get_content( $chV );
			
			if (isset($tmp['attributes'][$chK]))
			{
				if (isset($tmp['abbr0'][$tmp['attributes'][$chK]])
					&& isset($tmp['abbr1'][$tmp['attributes'][$chK]]))
				{
					$tmp['str_title'] = ' title="' . ($tmp['abbr1'][$tmp['attributes'][$chK]]) . '"';
					$tmp['str_acronym'] = ($tmp['abbr0'][$tmp['attributes'][$chK]]);
				}
				elseif ($tmp['attributes'][$chK] != '')
				{
					/* show codes which are not exist in database */
					$tmp['str_title'] = '';
					$tmp['str_acronym'] = $tmp['attributes'][$chK];
				}
				/* */
				if (($tmp['str'] != '') || $tmp['attributes'][$chK] != '--')
				{
					$tagname = 'tag_'.$tag;
					$class_name = $tag.'-1';
					if ( isset($ar_abbr_groups[$tmp['attributes'][$chK]]) )
					{
						$class_name = $tag.'-'.$ar_abbr_groups[$tmp['attributes'][$chK]];
					}
					$tmp['ar_compiled'][$i] = ($tmp['str_title'] != '') ? '<'.$this->$tagname.$tmp['str_title'].'>' : '';
					$tmp['ar_compiled'][$i] .= $tmp['str_acronym'];
					$tmp['ar_compiled'][$i] .= ($tmp['str_title'] != '') ? '</'.$this->$tagname.'>' : '';
					$tmp['ar_compiled'][$i] .= ($tmp['str'] != '') ? $postlang_name. '<span class="'.$class_name.'">' . $tmp['str'] . '</span>' : '';
				}
			}
		}
		if (!empty($tmp['ar_compiled']))
		{
			$tmp['strhtml'] .= $prepend_name . implode($split_name, $tmp['ar_compiled']) . $append_name;
		}
		return $tmp['strhtml'];
	}
	/* */
	function make_html_trns($fieldname, $ar = array(), $tag = 'trns')
	{
		return $this->make_html_abbr($fieldname, $ar, $tag);
	}
	//
	function make_xml_usg($fieldname, $ar = array())
	{
		return $this->make_xml_set_array2textarea($fieldname, $ar, 'usg');
	}
	function make_html_usg($fieldname, $ar = array())
	{
		return $this->make_html_set_array2textarea($fieldname, $ar, 'usg');
	}
	//
	function make_xml_src($fieldname, $ar = array())
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'src');
	}
	function make_html_src($fieldname, $ar = array())
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'src');
	}
	//
	function make_xml_address($fieldname, $ar = array())
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'address');
	}
	function make_html_address($fieldname, $ar = array())
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'address');
	}
	//
	function make_xml_phone($fieldname, $ar = array())
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'phone');
	}
	function make_html_phone($fieldname, $ar = array())
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'phone');
	}
	//
	function make_xml_see($fieldname, $ar = array(), $tag = 'see')
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('link' => ''));
		}
		//
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			/* */
			$elV['value'] = gw_fix_input_to_db($elV['value']);
			$tmp['isLink'] = 0;
			if (isset($elV['attributes']['is_link']))
			{
				$tmp['isLink'] = 1;
			}
			$elV = explode(CRLF, $elV['value']);
			while (list($k, $v) = each($elV))
			{
				$tmp['str_text'] = '';
				/* */
				if ($v != '')
				{
					preg_match_all("/(.*)\[\[(.*?)\]\]/", $v, $pregV);
					$v = isset($pregV[1][0]) ? trim($pregV[1][0]) : trim($v);

					/* Exclude http links from linking */
					/* See also `is_parse_url` in `t.term.inc.php` */
					if (preg_match('/(http|https|news|ftp|aim|callto|e2dk):\/\/\w+/', $v))
					{
						$tmp['isLink'] = 0;
					}
					$tmp['str_text'] = isset($pregV[2][0]) ? $pregV[2][0] : '';
					$tmp['strxml'] .= '<'.$tag;
					$tmp['strxml'] .= ($tmp['isLink'] ? ' link="'. strip_tags($v) . '"' : '');
					$tmp['strxml'] .= (($tmp['str_text'] != '') ? ' text="'. $tmp['str_text'] .'"' : '');
					$tmp['strxml'] .= '><![CDATA[';
					$tmp['strxml'] .= $v;
					$tmp['strxml'] .= ']]></'.$tag.'>';
				}
			}
		}
		return $tmp['strxml'];
	}
	/* */
	function make_xml_syn($fieldname, $ar = array())
	{
		return $this->make_xml_see($fieldname, $ar, 'syn');
	}
	/* */
	function make_xml_antonym($fieldname, $ar = array())
	{
		return $this->make_xml_see($fieldname, $ar, 'antonym');
	}
	/* */
	function make_html_see($fieldname, $ar = array(), $tag = 'see')
	{
		global $oHtml;
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();

		$prepend_name = $this->ar_theme['prepend_see_syn'];
		$split_name = $this->ar_theme['split_see_syn'];
		$append_name = $this->ar_theme['append_see_syn'];
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('link' => ''));
		}
		$tmp['ar_compiled'] = array();
		$i = 0;
		//
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			$tmp['is_link'] = isset($elV['attributes']['is_link']) ? 1 : 0;
			$tmp['str_text'] = isset($elV['attributes']['text']) ? $elV['attributes']['text'] : '';
			$v = $elV['value'];
			if ($v != '')
			{
				$i++;
				$delimeter = ($i == 1) ? '' : $this->split_see;
				$urlXref = GW_IS_BROWSE_WEB ? $oHtml->url_normalize($this->Gtmp['xref'] . urlencode(gw_text_parse_href($v))) : chmGetFilename(strip_tags($v));
				$v = ($tmp['is_link'] ? '<a rel="nofollow" class="href-'.$tag.'" href="' . $urlXref . '" title="'.$this->oL->m($tag) . ' ' . gw_text_parse_href($v).'">' . $this->ar_theme['txt_linkmarker'] . $v . '</a>' : $v);
				$v .= (($tmp['str_text'] != '') ? ' '.$tmp['str_text'] : '');
				$tmp['strxml'] .= $delimeter . $v;
			}
		}
		if ($tmp['strxml'] != '')
		{
			$tmp['strxml'] = '<div class="gw'.$fieldname.'"><span class="gray">' . $this->oL->m($tag) . ':</span>&#32;' . $prepend_name . $tmp['strxml'] . $append_name.'</div>';
		}
		return $tmp['strxml'];
	}
	function make_html_syn($fieldname, $ar = array(), $tag = 'syn')
	{
		return $this->make_html_see($fieldname, $ar, $tag);
	}
	function make_html_antonym($fieldname, $ar = array(), $tag = 'antonym')
	{
		return $this->make_html_see($fieldname, $ar, $tag);
	}
	//
	function make_xml_set_textarea($fieldname, $ar = array(), $tag)
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		$this->unsetTag('textarea'); // reset settings for <textarea>
		//
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			$elV['value'] = gw_fix_input_to_db($elV['value']);
			$elV = explode(CRLF, $elV['value']);
			while (list($k, $v) = each($elV))
			{
				if ($v != '')
				{
					$tmp['str'] .= '<'.$tag.'><![CDATA[' . $v . ']]></'.$tag.'>';
				}
			}
		}
		$tmp['strxml'] .= $tmp['str'];
		return $tmp['strxml'];
	}
	//
	function make_html_set_textarea($fieldname, $ar = array(), $tag)
	{
		$tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		$tmp['ar_compiled'] = array();
		$i = 0;
		$split_name = 'split_'.$tag;
		//
		if (!empty($tmp['arEl']))
		{
			$tmp['str'] .= '<div class="gw'.$fieldname.'"><span class="gray">' . $this->oL->m($tag) . ':</span>&#032; ';
			while (list($elK, $elV) = each($tmp['arEl']))
			{
				$elV = explode(CRLF, $elV['value']);
				while (list($k, $v) = each($elV))
				{
					if ($v != '')
					{
						$tmp['ar_compiled'][$i] = $v;
						$i++;
					}
				}
			}
			$tmp['str'] .= implode($this->$split_name, $tmp['ar_compiled']);
			$tmp['str'] .= '</div>';
		}
		return $tmp['str'];
	}
	//
	function make_xml_set_array2textarea($fieldname, $ar = array(), $tag)
	{
		/* */
		$tmp['strform'] = $tmp['str'] = '';
		/* */
		for (reset($this->arEl[$fieldname]); list($elK, $elV) = each($this->arEl[$fieldname]);)
		{
			if (isset($elV['value']) && (intval($ar['elK']) == intval($elK)))
			{
				$elV = explode(CRLF, $elV['value']);
				while (list($k, $v) = each($elV))
				{
					if ($v != '')
					{
						$tmp['str'] .= '<'.$tag.'><![CDATA[' . gw_fix_input_to_db($v) . ']]></'.$tag.'>';
					}
				}
			}
			elseif (intval($ar['elK']) == intval($elK)) /* multiarray */
			{
				while (list($k, $v) = each($elV))
				{
					$tmp['str'] .= '<'.$tag.'><![CDATA[';
					$tmp['str'] .= $v['value'];
					$tmp['str'] .= ']]></'.$tag.'>';
				}
				#print '<br />' . $ar['elK'] . ' == ' . $elK;
			}
		}
		return $tmp['str'];
	}

	function make_html_set_array2textarea($fieldname, $ar = array(), $tag)
	{
		/* */
		$tmp['str'] = '';
		/* */
		$tmp['ar_compiled'] = array();
		$i = 0;
		$split_name = 'split_'.$tag;
		$append_name = 'append_'.$tag;
		$prepend_name = 'prepend_'.$tag;

		$split_name = $this->$split_name;
		$append_name = $this->$append_name;
		$prepend_name = $this->$prepend_name;
		
		if (!empty($this->arEl) && isset($this->arEl[$fieldname]))
		{
			for (reset($this->arEl[$fieldname]); list($elK, $elV) = each($this->arEl[$fieldname]);)
			{
				if (isset($elV['value']) && (intval($ar['elK']) == intval($elK)))
				{
					$elV = explode(CRLF, $elV['value']);
					while (list($k, $v) = each($elV))
					{
						if ($v != '')
						{
							$tmp['str'] .= '['.$tag.']' . $v . '[/'.$tag.']';
						}
					}
				}
				elseif (intval($ar['elK']) == intval($elK)) // multiarray
				{
					$tmp['str'] .= '&#160;'; // IE: required for correct rendering <li>
					$tmp['str'] .= '<div class="gw'.$fieldname.'" title="'.$this->oL->m($tag).'">';
					while (list($k, $v) = each($elV))
					{
						$tmp['ar_compiled'][$i] = $v['value'];
						$i++;
					}
					$tmp['str'] .= $prepend_name . implode($split_name, $tmp['ar_compiled']) . $append_name;
					$tmp['str'] .= '</div>';
				}
			}
		}
		return $tmp['str'];
	}

	/**
	 * Parses structured array and converts it into XML-code
	 *
	 * @param   array  $arPre Fields content structure
	 * @return  string   XML-code (for database)
	 */
	function array_to_xml($arPre)
	{
		$strXml = '';
		$t = new gw_timer;
		// Go for each configured root field.
		for (reset($this->arFields); list($fK, $fV) = each($this->arFields);)
		{
			if (isset($fV[4]) && $fV[4]) // select root elements only here
			{
				if ($fV[0] == 'term') // terms always presents
				{
					#$strXml .= $this->tag2field_xml($fV[0]);
				}
				else
				{
					// other tags are switchable, parsed inside $form class
					if ($this->arDictParam['is_'.$fV[0]])
					{
						$strXml .= $this->tag2field_xml($fV[0]);
					}
				}
			} // end of root elements
		} // end of per-field process
		#$strXml = '<line>'.$strXml.'</line>';
		return $strXml;
	} // end of array_to_xml();
	/**
	 * Parses structured array and converts it into HTML-code
	 *
	 * @param   array  $arPre Fields content structure
	 * @return  string   HTML-code (for website)
	 */
	function array_to_html($arPre)
	{
		global $oHtml;
		$strHtml = '';
		#$t = new gw_timer;
		// Go for each configured root field.
		for (reset($this->arFields); list($fK, $fV) = each($this->arFields);)
		{
			if (isset($fV[4]) && $fV[4]) // select root elements only here
			{
				//
				if ($fV[0] == 'term') // terms always presents
				{
					$strHtml .= $this->tag2field_html($fV[0]);
				}
				else
				{
					// other tags are switchable, parsed inside $form class
					if ($this->arDictParam['is_'.$fV[0]])
					{
						$strHtml .= $this->tag2field_html($fV[0]);
					}
				}
			} // end of root elements
		} // end of per-field process
		// parse additinal tags
		// -------------------------------------------------
		// $tag_stress_rule
		$ar_pairs_src = explode("|", $this->tag_stress_rule);
		/* @TODO: change stress syntax to t`ermin instead of t<stress>e</stress>rmin */
		$strHtml = str_replace('<stress>', $ar_pairs_src[0], $strHtml);
		$strHtml = str_replace('</stress>', $ar_pairs_src[1], $strHtml);

		$strHtml = str_replace('<nowiki>', '', $strHtml);
		$strHtml = str_replace('</nowiki>', '', $strHtml);

		$strHtml = str_replace('<![CDATA[', '', str_replace(']]>', '', $strHtml));
		
#print htmlspecialchars($strHtml);

		/* Additional tags in whole definition */
		$tagsA = array('xref');
		$tagsAttrA = array('link');
		for (;list($kt, $vt) = each($tagsA);)
		{
			for (;list($ka, $va) = each($tagsAttrA);)
			{
				preg_match_all("/<$vt( $va=\"(.*?)\")*\>([^<]*?)\<\/$vt\>/", $strHtml, $strTmpA);
				if ( isset($strTmpA[0]) )
				{
					for (;list($kd, $vd) = each($strTmpA[0]);)
					{
						$urlXref = GW_IS_BROWSE_WEB ? $oHtml->url_normalize($this->Gtmp['xref'] .  urlencode(gw_text_parse_href($strTmpA[2][$kd]))) : chmGetFilename(strip_tags($strTmpA[2][$kd]));
						$tagXref = '<a class="href-see" href="' . $urlXref . '">' . $this->ar_theme['txt_linkmarker'] . $strTmpA[3][$kd] . '</a>';
#						$tagXref = $oHtml->a($this->Gtmp['xref'] . $urlXref, $this->sys['txt_linkmarker'] . $strTmpA[3][$kd]);
						$strHtml = str_replace( $strTmpA[0][$kd], $tagXref, $strHtml);
					}
				}
			} // end of each attribute
		} // end of each tag
		return $strHtml;
	}
} // end of class
?>