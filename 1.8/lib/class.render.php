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
 *  Coverts array structure into XML or HTML code.
 *
 *  @todo   normalize raw functions.
 */
// --------------------------------------------------------


class gw_render extends gw_htmlforms
{
    /** @var array */
    public $sys = [];

    /** @var array */
    public $ar_theme = [];

    /** @var gw_database */
    public $oDb;

    /** @var gw_functions */
    public $oFunc;

    /** @var gw_html */
    public $oHtml;

    /** @var gw_session */
    public $oSess;

    /** @var gw_sql_query */
    public $oSqlQ;

    /** @var gw_template */
    public $oTpl;

    public $is_html_preview = 0;
	public $tag_abbr   = 'acronym'; // 'abbr' for XHTML 2.0
	public $tag_trns   = 'acronym'; // 'abbr' for XHTML 2.0
	// <trsp> tag
	public $prepend_trsp  = '<span class="trsp">[';
	public $split_trsp    = '; ';
	public $append_trsp  = ']</span>';
	// <abbr> tag
	public $prepend_abbr  = '';
	public $postlang_abbr = '&#32;';
	public $split_abbr    = '; <br />';
	public $append_abbr   = '<br />';
	// <trns> tag
	public $prepend_trns  = '';
	public $postlang_trns = '&#32;';
	public $split_trns    = '; <br />';
	public $append_trns   = '';
	// <usg> tag
	public $prepend_usg   = '<dl><dt>';
	public $split_usg     = '</dt><dt>';
	public $append_usg    = '</dt></dl>';
	// <see> and <syn> tags
	public $prepend_see   = '';
	public $split_see     = ', ';
	public $append_see    = '';
	// other tags
	public $split_src     = ', ';
	public $split_address = ', ';
	public $split_phone   = ', ';
	public $tag_stress_rule  = '<span class="stress">|</span>';
	// internal switches, do not change
	public $is_br_begin   = 0;
	public $is_plural     = 0;
	// available functions
	public $arFuncList = array(
					   'make_xml_term' => 1, 'make_xml_trsp' => 1, 'make_xml_defn' => 1,
					   'make_xml_abbr' => 1, 'make_xml_trns' => 1, 'make_xml_audio' => 1,
					   'make_xml_syn'  => 1, 'make_xml_antonym' => 1, 'make_xml_see'  => 1,
					   'make_xml_usg'  => 1, 'make_xml_src'  => 1, 'make_xml_address' => 1, 'make_xml_phone'  => 1
					  );
	public $arFuncList_html = array(
					   'make_html_term' => 1, 'make_html_trsp' => 1, 'make_html_defn' => 1,
					   'make_html_abbr' => 1, 'make_html_trns' => 1, 'make_html_audio' => 1,
					   'make_html_syn'  => 1, 'make_html_antonym' => 1, 'make_html_see'  => 1,
					   'make_html_usg'  => 1, 'make_html_src'  => 1, 'make_html_address' => 1, 'make_html_phone'  => 1
					  );
	/* Autoexec */
	public function __construct()
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
	public function tag2field_xml($fieldname, $ar = [])
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
	public function tag2field_html($fieldname, $ar = [])
	{
		$fieldname = strtolower($fieldname);
		$funcname = 'make_html_' . $fieldname;
		if (isset($this->arFuncList_html[$funcname]) && $this->arFuncList_html[$funcname])
		{
			return $this->$funcname($fieldname, $ar);
		}
	}

	//
	public function make_xml_term($fieldname, $ar = [])
	{
#		$s = $this->objDom->get_content($this->arEl[$fieldname][0]);
#		return $s;
	}
	public function make_html_term($fieldname, $ar = [])
	{
		return '';
	}
	//
	public function make_xml_trsp($fieldname, $ar = [], $tag = 'trsp')
	{
		return $this->make_xml_set_array2textarea($fieldname, $ar, 'trsp');
	}
	//
    public function make_html_trsp($fieldname, $ar = [], $tag = 'trsp')
    {
        $tmp['strxml'] = $tmp['str'] = '';
        $tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
        $split_trsp = $this->ar_theme['split_trsp'];
        $prepend_trsp = $this->ar_theme['prepend_trsp'];
        $append_trsp = $this->ar_theme['append_trsp'];
        // do auto fill
        if (empty($tmp['arEl'])) {
            $tmp['arEl'][0] = ['value' => '', 'attributes' => ['link' => '']];
        }
        $tmp['ar_compiled'] = [];
        $i = 0;
        //
        foreach ($tmp['arEl'] as $elK => $elV) {
            if ($elV['value'] != '') {
                $i++;
                $delimeter = ($i == 1) ? '' : $split_trsp;
                $tmp['strxml'] .= $delimeter . $elV['value'];
            }
        }
        if ($tmp['strxml'] != '') {
            $tmp['strxml'] = '<div title="' . $this->oL->m($tag) . '" class="gw' . $fieldname . '">' . $prepend_trsp . $tmp['strxml'] . $append_trsp . '</div>';
        }
        return $tmp['strxml'];
    }

    /* 26 feb 2008: attached files */
	public function make_html_audio($fieldname, $ar = [], $tag = 'audio')
	{
		$tmp['strhtml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('size' => 0));
		}
		//
		foreach ($tmp['arEl'] as $chK => $chV)
		{
			//
			$tmp['size'] = isset($chV['attributes']['size']) ? $chV['attributes']['size'] : 0;
			$tmp['str'] = $this->objDom->get_content( $chV );
			$tmp['str'] = urlencode($tmp['str']);
			if ($tmp['size'])
			{
				$tmp['strhtml'] .= '<span class="gw-attach"><a href="'.$this->sys['server_dir'].'/'.$this->sys['path_temporary'].'/a/'.$tmp['str'];
				$tmp['strhtml'] .= '" title="'.$this->oFunc->number_format($tmp['size'], 0, $this->oL->languagelist(LOCALE_LANG_RULES)).' '.$this->oL->m('bytes').'">'.$this->oL->m('audio').'</a> ';
				$tmp['strhtml'] .= '</span>';
			}
			$tmp['str'] = '';
		}
		return $tmp['strhtml'];
	}
	public function make_xml_audio($fieldname, $ar = [], $tag = 'audio')
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('size' => 0));
		}
		/* */
		foreach ($tmp['arEl'] as $chK => $chV)
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
	public function make_xml_defn($fieldname, $ar = [])
	{
		$tmp['strxml'] = '';

		// for each definition
		foreach ($this->arEl[$fieldname] as $elK => $elV)
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
			foreach ($this->arFields as $fK => $fV)
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
	public function make_html_defn($fieldname, $ar = [])
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
		foreach ($this->arEl[$fieldname] as $elK => $elV)
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
			foreach ($this->arFields as $fK => $fV)
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
			$ar_authors = [];
			foreach ($arSql as $k => $arV)
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
	public function make_xml_abbr($fieldname, $ar = [], $tag = 'abbr')
	{
		$tmp['strxml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		//
		foreach ($tmp['arEl'] as $chK => $chV)
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
	public function make_xml_trns($fieldname, $ar = [], $tag = 'trns')
	{
		$tmp['strxml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		//
		// do auto fill
		//
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		//
		foreach ($tmp['arEl'] as $chK => $chV)
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
	public function make_html_abbr ($fieldname, $ar = [], $tag = 'abbr')
	{
		global $oDb, $oSqlQ;
		global $gw_this, $arDictParam;
		$tmp['strhtml'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];

		$prepend_name = $this->ar_theme['prepend_'.$tag];
		$split_name = $this->ar_theme['split_'.$tag];
		$append_name = $this->ar_theme['append_'.$tag];
		$postlang_name = $this->ar_theme['postlang_'.$tag];

		$tmp['attributes'] = [];
		/* Collect lang codes */
		foreach ($tmp['arEl'] as $chK => $chV)
		{
			$tmp['attributes'][$chK] = $this->objDom->get_attribute('lang', '', $chV);
		}
		$arSql = [];
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
        foreach ($arSql as $arV) {
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
		$arSql = [];
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('lang' => '--'));
		}
		/* */
		$tmp['ar_compiled'] = [];
		$i = 0;
        foreach ($tmp['arEl'] as $chK => $chV) {
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
	public function make_html_trns($fieldname, $ar = [], $tag = 'trns')
	{
		return $this->make_html_abbr($fieldname, $ar, $tag);
	}
	//
	public function make_xml_usg($fieldname, $ar = [])
	{
		return $this->make_xml_set_array2textarea($fieldname, $ar, 'usg');
	}
	public function make_html_usg($fieldname, $ar = [])
	{
		return $this->make_html_set_array2textarea($fieldname, $ar, 'usg');
	}
	//
	public function make_xml_src($fieldname, $ar = [])
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'src');
	}
	public function make_html_src($fieldname, $ar = [])
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'src');
	}
	//
	public function make_xml_address($fieldname, $ar = [])
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'address');
	}
	public function make_html_address($fieldname, $ar = [])
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'address');
	}
	//
	public function make_xml_phone($fieldname, $ar = [])
	{
		return $this->make_xml_set_textarea($fieldname, $ar, 'phone');
	}
	public function make_html_phone($fieldname, $ar = [])
	{
		return $this->make_html_set_textarea($fieldname, $ar, 'phone');
	}
	//
	public function make_xml_see($fieldname, $ar = [], $tag = 'see')
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('link' => ''));
		}
		//
		foreach ($tmp['arEl'] as $elK => $elV)
		{
			/* */
			$elV['value'] = gw_fix_input_to_db($elV['value']);
			$tmp['isLink'] = 0;
			if (isset($elV['attributes']['is_link']))
			{
				$tmp['isLink'] = 1;
			}
			$elV = explode(CRLF, $elV['value']);
			foreach ($elV as $k => $v)
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
	public function make_xml_syn($fieldname, $ar = [])
	{
		return $this->make_xml_see($fieldname, $ar, 'syn');
	}
	/* */
	public function make_xml_antonym($fieldname, $ar = [])
	{
		return $this->make_xml_see($fieldname, $ar, 'antonym');
	}
	/* */
	public function make_html_see($fieldname, $ar = [], $tag = 'see')
	{
		global $oHtml;
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];

		$prepend_name = $this->ar_theme['prepend_see_syn'];
		$split_name = $this->ar_theme['split_see_syn'];
		$append_name = $this->ar_theme['append_see_syn'];
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('link' => ''));
		}
		$tmp['ar_compiled'] = [];
		$i = 0;
		//
		foreach ($tmp['arEl'] as $elK => $elV)
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
	public function make_html_syn($fieldname, $ar = [], $tag = 'syn')
	{
		return $this->make_html_see($fieldname, $ar, $tag);
	}
	public function make_html_antonym($fieldname, $ar = [], $tag = 'antonym')
	{
		return $this->make_html_see($fieldname, $ar, $tag);
	}
	//
	public function make_xml_set_textarea($fieldname, $ar = [], $tag)
	{
		$tmp['strxml'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		//
		$this->unsetTag('textarea'); // reset settings for <textarea>
		//
		foreach ($tmp['arEl'] as $elK => $elV)
		{
			$elV['value'] = gw_fix_input_to_db($elV['value']);
			$elV = explode(CRLF, $elV['value']);
			foreach ($elV as $k => $v)
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
	public function make_html_set_textarea($fieldname, $ar = [], $tag)
	{
		$tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : [];
		//
		$tmp['ar_compiled'] = [];
		$i = 0;
		$split_name = 'split_'.$tag;
		//
		if (!empty($tmp['arEl']))
		{
			$tmp['str'] .= '<div class="gw'.$fieldname.'"><span class="gray">' . $this->oL->m($tag) . ':</span>&#032; ';
			foreach ($tmp['arEl'] as $elK => $elV)
			{
				$elV = explode(CRLF, $elV['value']);
				foreach ($elV as $k => $v)
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
	public function make_xml_set_array2textarea($fieldname, $ar = [], $tag)
	{
		/* */
		$tmp['strform'] = $tmp['str'] = '';
		/* */
		foreach ($this->arEl[$fieldname] as $elK => $elV)
		{
			if (isset($elV['value']) && (intval($ar['elK']) == intval($elK)))
			{
				$elV = explode(CRLF, $elV['value']);
				foreach ($elV as $k => $v)
				{
					if ($v != '')
					{
						$tmp['str'] .= '<'.$tag.'><![CDATA[' . gw_fix_input_to_db($v) . ']]></'.$tag.'>';
					}
				}
			}
			elseif (intval($ar['elK']) == intval($elK)) /* multiarray */
			{
				foreach ($elV as $k => $v)
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

	public function make_html_set_array2textarea($fieldname, $ar = [], $tag)
	{
		/* */
		$tmp['str'] = '';
		/* */
		$tmp['ar_compiled'] = [];
		$i = 0;
		$split_name = 'split_'.$tag;
		$append_name = 'append_'.$tag;
		$prepend_name = 'prepend_'.$tag;

		$split_name = $this->$split_name;
		$append_name = $this->$append_name;
		$prepend_name = $this->$prepend_name;
		
		if (!empty($this->arEl) && isset($this->arEl[$fieldname]))
		{
			foreach ($this->arEl[$fieldname] as $elK => $elV)
			{
				if (isset($elV['value']) && (intval($ar['elK']) == intval($elK)))
				{
					$elV = explode(CRLF, $elV['value']);
					foreach ($elV as $k => $v)
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
					foreach ($elV as $k => $v)
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
	public function array_to_xml($arPre)
	{
		$strXml = '';
		$t = new gw_timer;
		// Go for each configured root field.
		foreach ($this->arFields as $fK => $fV)
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
	public function array_to_html($arPre)
	{
		global $oHtml;
		$strHtml = '';
		#$t = new gw_timer;
		// Go for each configured root field.
		foreach ($this->arFields as $fK => $fV)
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
		foreach ($tagsA as $kt => $vt)
		{
			foreach ($tagsAttrA as $ka => $va)
			{
				preg_match_all("/<$vt( $va=\"(.*?)\")*\>([^<]*?)\<\/$vt\>/", $strHtml, $strTmpA);
				if ( isset($strTmpA[0]) )
				{
					foreach ($strTmpA[0] as $kd => $vd)
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
