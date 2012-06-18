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
/**
 * Glossword HTML-form class extension
 * 
 * $Id: class.gw_htmlforms.php 499 2008-06-15 13:59:46Z glossword_team $
 */
class gw_htmlforms extends gwForms
{
	var $Gsys     = array();
	var $Gtmp     = array('strform' => '', 'str' => '');
	var $oL       = array();
	var $objDom   = '';
	var $objDict  = '';
	var $oFieldExt  = array();
	var $arFields = array();
	var $arEl     = array();
	var $is_abbr_short = 0; // [ 0 - abbreviation | 1 - abbr.   ]

	// available functions
	var $arFuncList = array(
					   'make_term' => 1, 'make_trsp' => 1, 'make_defn' => 1,
					   'make_abbr' => 1, 'make_trns' => 1, 'make_audio' => 1,
					   'make_syn'  => 1, 'make_antonym'  => 1, 'make_see'  => 1,
					   'make_usg'  => 1, 'make_src'  => 1, 'make_address' => 1, 'make_phone'  => 1
					  );
	// autostart
	function gw_htmlforms()
	{
		// load trns
		// load abbr
	}
	function tag2field($fieldname, $ar = array())
	{
		$fieldname = strtolower($fieldname);
		$funcname = 'make_' . $fieldname;
		if (isset($this->arFuncList[$funcname]) && $this->arFuncList[$funcname])
		{
			return $this->$funcname($fieldname, $ar);
		}
	}
	//
	function html_editor_make_toolbar($id = 0, $html_add = '', $html_remove = '')
	{
		global $oFunc;
		$this->unsetTag('input');
		$this->setTag('input', 'type', 'button');
		$this->setTag('input', 'class', 'btn');

#		$this->setTag('input', 'onmouseover', 'this.className=\'btnO\'');
#		$this->setTag('input', 'onmouseout',  'this.className=\'btn\'');
#		$this->setTag('input', 'onmousedown', 'this.className=\'btnD\'');
#		$this->setTag('input', 'onmouseup',   'this.className=\'btnO\'');

#		$tmp['str'] = CRLF . '<div style="overflow:auto; width:500px; float:'.$this->Gsys['css_align_right'].';"><table class="toolbar" cellspacing="0" cellpadding="1" border="0" width="100%"><tbody><tr>';
		$tmp['str'] = CRLF . '<div style="padding:3px;background:#ECE9D8;overflow:auto;width:500px;float:'.$this->Gsys['css_align_right'].';">';
		/* TODO: replace with Wiki syntax*/
		// [0]type, [1]jsName, [2]caption, [3]text, [4]hint
		$arButtons = array(
			'1||format_bold|| B ||strong||ed_bold',
			'1||format_italic|| I ||em||ed_em',
			'2||format_underline|| U ||&lt;span class=&quot;underline&quot;&gt;|&lt;/span&gt;||ed_underline',
			'1||paragraph|| P ||p||ed_p',
			'1||tt|| TT ||tt||&lt;tt&gt;|&lt;/tt&gt;',
			'spacer||||||||',
			'2||quot1||&quot;|&quot;||&amp;quot;|&amp;quot;||ed_quot',
			'2||quot2||&#171;|&#187;||&amp;#171;|&amp;#187;||ed_elochki',
			'2||xml1||&lt;|&gt;||&lt;|&gt;||ed_tagopen',
			'2||xml2||&lt;/|&gt;||&lt;/|&gt;||ed_tagclose',
			'spacer||||||||',
			'2||stress||&lt;Ś&gt;||&lt;stress&gt;|&lt;/stress&gt;||&lt;stress&gt;|&lt;/stress&gt;',
			'4||xref||&lt;xref&gt;||&lt;xref link=&quot;|&quot;&gt;|&lt;/xref&gt;||&lt;xref link=&quot;|&quot;&gt;&lt;/xref&gt;',
			'spacer||||||||',
			'3||nbsp||&amp;nbsp;||&amp;#160;||ed_nbsp',
			'3||tm||&#8482;||&amp;#8482;||ed_trademark',
			'3||euro||&#8364;||&amp;#8364;||ed_eurosign',
			'3||copy||&#169;||&amp;#169;||ed_copyright',
			'3||reg||&#174;||&amp;#174;||ed_registered',
			'3||deg||&#176;||&amp;#176;||ed_degree',
			'3||dots||&#8230;||&amp;#8230;||ed_dots',
			'spacer||||||||'
		);
		//
		$jsToolbarOff = $jsToolbarSymbOff = $jsToolbarOn = '';
		//
		for (reset($arButtons); list($k, $v) = each($arButtons);)
		{
			$arParam = explode("||", $v);
			$this->setTag('input', 'title', $this->oL->m($arParam[4]));
			if ($oFunc->is_num($arParam[0]))
			{
				$vJs = "'" .  $arParam[1] . "_'+id";
				$this->setTag('input', 'onmouseover', "this.className='btnO'");
				$this->setTag('input', 'onmouseout', "this.className='btn'");
				if ($arParam[0] == 1)
				{
					$this->setTag('input', 'onclick', 'sTextTag('.$id.', \''. $arParam[3].'\')');
				}
				else if ($arParam[0] == 2)
				{
					$ar_pairs_src = explode("|", $arParam[3]);
					$this->setTag('input', 'onclick', 'sTextDoubleSymbol('.$id.', \''.$ar_pairs_src[0].'\', \''.$ar_pairs_src[1].'\')');
				}
				else if ($arParam[0] == 3)
				{
					$this->setTag('input', 'onclick', 'sTextSymbol('.$id.', \''.$arParam[3].'\')');
					$jsToolbarSymbOff .= 'gw_getElementById('.$vJs.').disabled = ';
				}
				else if ($arParam[0] == 4)
				{
					$this->setTag('input', 'onclick', 'sTextDoubleSymbol2('.$id.', \''.$arParam[3].'\')');
				}
#				$tmp['str'] .= '<td>' . $this->field('input', $arParam[1].'_'.$id, $arParam[2]) . '</td>';
				$tmp['str'] .= $this->field('input', $arParam[1].'_'.$id, $arParam[2]);
				$jsToolbarOff .= 'gw_getElementById('.$vJs.').disabled = ';
				$jsToolbarOn .= 'gw_getElementById('.$vJs.').disabled = ';
			}
			else
			{
#				$tmp['str'] .= '<td><img src="gw_admin/images/spacer.gif" width="4" height="22" alt="" /></td>';
				$tmp['str'] .= '<img style="float:left" src="'.$this->Gsys['path_img'].'/spacer.gif" width="4" height="22" alt="" />';
			}
		}
		// add/remove definition buttons
		$tmp['str'] .= ($html_add != '') ? ('<td>'.$html_add.'</td>') : '';
		$tmp['str'] .= ($html_remove != '') ? ('<td>'.$html_remove.'</td>') : '';
		// the end of toolbar table
#		$tmp['str'] .= '</tr></tbody></table></div>';
		$tmp['str'] .= '</div>';

		$jsToolbarSymbOff .= 'true;';
		$jsToolbarOff .= 'true;';
		$jsToolbarOn .= 'false;';

		// enable-disable toolbar
		$tmp['str'] .= '<script type="text/javascript">/*<![CDATA[*/
			function toolbar_on(id) { '.$jsToolbarOn.' }
			function toolbar_off(id) { '.$jsToolbarOff.' }
			function toolbar_symb_off(id) { '.$jsToolbarSymbOff.' }
			/*]]>*/</script>';
		$tmp['str'] .= CRLF.'<script type="text/javascript">/*<![CDATA[*/'.CRLF.'toolbar_off(\''.$id.'\'); '.CRLF.'/*]]>*/</script>';
		return $tmp['str'];
	}
	//
	function make_term($fieldname, $ar = array())
	{
		global $oFunc;
		if (!isset($this->arEl[$fieldname]))
		{
			$this->arEl[$fieldname][0]['value'] = '';
		}
		/* */
		$this->unsetTag('input'); // reset settings for <input>
		/* */
		$tmp['strform'] = '';
		$tmp['strform'] .= '<tr><td style="text-align:'.$this->Gsys['css_align_left'].'">';

		/* Inactive or scheduled for deletion */
		$is_active = $this->objDom->get_attribute('is_active', '', $this->arEl[$fieldname]);

		$tmp['strform'] .= '<fieldset class="admform">';
		$tmp['strform'] .= '<legend class="xt gray">'.$this->oL->m('term').'</legend>';

		$tmp['strform'] .= '<table width="100%" class="gw2TableFieldset" cellpadding="0" cellspacing="0">';
		$tmp['strform'] .= '<tbody><tr>';
		$tmp['strform'] .= '<td class="td1" style="width:13%">' . $this->field('checkbox', 'arPre['.$fieldname.'][0][attributes][is_active]', $is_active) . '</td>';
		$tmp['strform'] .= '<td class="td2'.($is_active==3?' red':'').'"><label for="'. $this->text_field2id('arPre['.$fieldname.'][0][attributes][is_active]').'">' . $this->oL->m('1320') . '</label></td>';
		$tmp['strform'] .= '</tr><tr>';
		$tmp['strform'] .= '<td class="td1">' . $this->field('checkbox', 'arPre['.$fieldname.'][0][attributes][is_complete]', $this->objDom->get_attribute('is_complete', '', $this->arEl[$fieldname])) . '</td>';
		$tmp['strform'] .= '<td class="td2"><label for="'. $this->text_field2id('arPre['.$fieldname.'][0][attributes][is_complete]').'">' . $this->oL->m('1267') . '</label>'.
								' <acronym title="'. sprintf($this->oL->m('1308'), '?') .'">?</acronym></td>';
		$tmp['strform'] .= '</tr></tbody></table>';
		
		$this->setTag('input', 'maxlength', '255');
		$this->setTag('input', 'size', '40');
		$this->setTag('textarea', 'style', 'height:2em;font-size:150%');

#		$tmp['strform'] .= '<div><span class="xt">'.$this->oL->m('term').'</span><span class="red">*</span></div>';
		$tmp['strform'] .= $this->field('textarea', 'arPre['.$fieldname.'][0][value]', gw_fix_db_to_field($this->objDom->get_content($this->arEl[$fieldname][0]) ) );
		$tmp['strform'] .= '</fieldset>';
		/* */
		if ($this->Gsys['pages_link_mode'] == GW_PAGE_LINK_URI)
		{
			$tmp['strform'] .= '<fieldset class="admform">';
			$tmp['strform'] .= '<legend onclick="return toggle_collapse(\'term-uri\')" style="cursor:pointer" class="xt gray">';
			$tmp['strform'] .= '<img style="vertical-align:middle;padding: 0 5px" id="ci-term-uri" src="'.$this->Gsys['path_img'].'/collapse_off.png" alt="" width="9" height="21" />';
			$tmp['strform'] .= $this->oL->m('1073');
			$tmp['strform'] .= '</legend>';
			$tmp['strform'] .= '<table style="display:none" id="co-term-uri" cellpadding="3" cellspacing="0" border="0" width="100%"><tbody><tr><td>';
			$this->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
			$tmp['strform'] .= $this->field('input', 'arPre['.$fieldname.'][0][attributes][uri]', gw_fix_db_to_field($this->objDom->get_attribute('uri', '', $this->arEl[$fieldname]) ) );
			$this->setTag('input', 'onkeyup', '');
			$tmp['strform'] .= '</td></tr></tbody></table>';
			$tmp['strform'] .= '</fieldset>';
		}
		$this->setTag('input', 'maxlength', '3');
		$this->setTag('input', 'size', '5');
		$this->setTag('input', 'style', 'width:96%');
		$tmp['strform'] .= '<fieldset class="admform">';

		$tmp['strform'] .= '<legend onclick="return toggle_collapse(\'term-az\')" style="cursor:pointer" class="xt gray">';
		$tmp['strform'] .= '<img style="vertical-align:middle;padding: 0 5px" id="ci-term-az" src="'.$this->Gsys['path_img'].'/collapse_off.png" alt="" width="9" height="21" />';
		$tmp['strform'] .= $this->oL->m('alphabetic_order');
		$tmp['strform'] .= '</legend>';
		
		$tmp['strform'] .= '<table style="display:none" id="co-term-az" cellpadding="3" cellspacing="0" border="0" width="100%"><tbody><tr><td style="width:33%">';
		$tmp['strform'] .= '<div class="xt">'.$this->oL->m('alphabetic_order').'&#160;1 (<strong>T</strong>erm&gt;T)</div> ';
		$tmp['strform'] .= $this->field('input', 'arPre['.$fieldname.'][0][attributes][t1]', gw_fix_db_to_field($this->objDom->get_attribute('t1', '', $this->arEl[$fieldname]) ) );
		$tmp['strform'] .= '</td><td>';
		$tmp['strform'] .= '<div class="xt">'.$this->oL->m('alphabetic_order').'&#160;2 (T<strong>e</strong>rm&gt;E)</div> ';
		$tmp['strform'] .= $this->field('input', 'arPre['.$fieldname.'][0][attributes][t2]', gw_fix_db_to_field($this->objDom->get_attribute('t2', '', $this->arEl[$fieldname]) ) );
		$tmp['strform'] .= '</td><td style="width:33%">';
		$tmp['strform'] .= '<div class="xt">'.$this->oL->m('alphabetic_order').'&#160;3 (Te<strong>r</strong>m&gt;R)</div> ';
		$tmp['strform'] .= $this->field('input', 'arPre['.$fieldname.'][0][attributes][t3]', gw_fix_db_to_field($this->objDom->get_attribute('t3', '', $this->arEl[$fieldname]) ) );
		$tmp['strform'] .= '</td></tr>';

		/* 1.8.7: on a custom alphabetic order */
		if ($this->arDictParam['id_custom_az'] > 1)
		{
			$tmp['strform'] .= '<tr><td colspan="3">';
			$this->setTag('input', 'maxlength', '');
			$this->setTag('input', 'size', '');
			$this->setTag('input', 'style', 'width:98%');
			$tmp['strform'] .= '<div class="xt">'.$this->oL->m('1309'). '</span></div> ';
			$tmp['strform'] .= $this->field('input', 'arPre['.$fieldname.'][0][attributes][term_order]', gw_fix_db_to_field($this->objDom->get_attribute('term_order', '', $this->arEl[$fieldname])) ).
								'<div class="xt gray">'.$this->oL->m('1310').' '.$this->oL->m('1311').'</div>';
			$tmp['strform'] .= '</td></tr>';
		}
		$tmp['strform'] .= '</tbody></table>';

		$tmp['strform'] .= '</fieldset>';

	
		$tmp['strform'] .= '</td>';
		$tmp['strform'] .= '</tr>';

		return $tmp['strform'];
	}
	/* */
	function make_audio($fieldname, $ar = array())
	{
		$tmp['strform'] = '';
		$tmp['strform'] .= '<tr class="'.$this->Gtmp['cssTrClass'].'">';
		$tmp['strform'] .= '<td style="vertical-align:top;text-align:'.$this->Gsys['css_align_right'].'">';
		$tmp['strform'] .= $this->oL->m('audio').':</td><td colspan="2" style="text-align:'.$this->Gsys['css_align_left'].'">';
		$tmp['strform'] .= '<div id="term-audio-'.$ar['elK'].'">';
		$tmp['strform'] .= $this->setTag( 'file', 'class', '' );
		$tmp['strform'] .= $this->setTag( 'file', 'style', '' );
		$tmp['strform'] .= $this->field( 'file', 'arAudio['.$fieldname.']['.$ar['elK'].'][0]' );
		$tmp['strform'] .= '<br />';
		$tmp['strform'] .= '</div>';
		$tmp['strform'] .= '</td><td style="vertical-align:top">';
		$tmp['strform'] .= '<input onclick="return TERM_audio_add(\''.$fieldname.'\',\''.$ar['elK'].'\')" style="width: 24px; height: 24px;" class="submitok" value="+" type="submit" />';

$tmp['strform'] .= '<script type="text/javascript">/*<![CDATA[*/';
$tmp['strform'] .= '
var TERM_audio_cnt = 0;
function TERM_audio_add(fname, fnum)
{
	TERM_audio_cnt++;
	if (TERM_audio_cnt > 7)
	{
		TERM_audio_cnt = 25; return false;
	}
	el = gw_getElementById("term-audio-"+fnum);
	audioObj = document.createElement("input");
	audioObj.setAttribute("type", "file");
	audioObj.setAttribute("name", "arAudio["+fname+"]["+fnum+"]["+TERM_audio_cnt+"]");
	el.appendChild(audioObj);
	el.appendChild(document.createElement("br"));
	return false;
}
';
$tmp['strform'] .= '/*]]>*/</script>';
		$tmp['strform'] .= '</td>';
		$tmp['strform'] .= '</tr>';

		return $tmp['strform'];
	}
	/* */
	function make_trsp($fieldname, $ar = array())
	{
		return $this->make_set_array2textarea($fieldname, $ar);
	}
	//
	function make_trsp_ext($fieldname, $ar)
	{
		$str = '';
		if (isset($this->Gsys['is_field_extensions']) && $this->Gsys['is_field_extensions'])
		{
			if ($this->arDictParam['is_htmled'] == 1)
			{
				$this->setTag('textarea', 'onclick', 'storeCaret(this)');
				$this->setTag('textarea', 'onselect', 'storeCaret(this)');
				$this->setTag('textarea', 'onkeyup', 'storeCaret(this)');
			}
			$oFieldExt = new gw_fields_extension;
			$str .= $oFieldExt->get_js($fieldname, $ar['elK']);
			$str .= $oFieldExt->get_html($fieldname, $ar['elK']);
		}
		return $str;
	}
	//
	function make_defn($fieldname, $ar = array())
	{
		global $oFunc, $oHtml, $gw_this, $oSess;
		$tmp['strform'] = $tmp['strBtnRemove'] = $tmp['strHtmlTB'] = $tmp['strBtnAdd'] = '';
		//
		$this->unsetTag('textarea'); // reset settings for <textarea>
		//
		// default value
		if (!isset($this->arEl[$fieldname]) || !is_array($this->arEl[$fieldname][0]))
		{
			$this->arEl[$fieldname] = array(0 => array('value' => ''));
		}
		if (!isset($this->arEl[$fieldname]))
		{
			$this->arEl[$fieldname][0]['value'] = '';
		}
		while (list($elK, $elV) = each($this->arEl[$fieldname]))
		{
			// break table for each definition
			$tmp['strform'] .= '</tbody></table>';
			//
			$tmp['strBtnAdd'] = '<input tabindex="7" type="submit" style="width:24px;height:24px" class="submitcancel" name="'.
								'arControl['.$fieldname.']['.GW_A_ADD.']['.$elK.'][0]" title="'.
								$this->oL->m('3_add_defn').'" value="+" />';
			if ( $elK > 0 )
			{
				$tmp['strBtnRemove'] = '<input tabindex="8" type="submit" style="width:24px;height:24px" class="submitdel" name="'.
									   'arControl['.$fieldname.']['.GW_A_REMOVE.']['.$elK.'][0]" title="'.
									   $this->oL->m('3_remove_defn').'" value="&#215;" />';
			}
			$tmp['strHtmlTB'] = $tmp['strBtnRemove'] . $tmp['strBtnAdd'];
			/* enable HTML-editor events */
			if ($this->arDictParam['is_htmled'] == 1)
			{
				$this->oL->getCustom('html_editor', $gw_this['vars']['locale_name'], 'join');
				$tmp['strHtmlTB'] = $this->html_editor_make_toolbar($elK, $tmp['strBtnRemove'], $tmp['strBtnAdd']);
				include($this->Gsys['path_include'].'/edcode.js.php');
#				$this->setTag('textarea', 'onfocus', 'toolbar_on(\''.$elK.'\')');
#				$this->setTag('textarea', 'onblur', 'toolbar_off(\''.$elK.'\')');
#				$this->setTag('textarea', 'onclick', 'storeCaret(this)');
#				$this->setTag('textarea', 'onselect', 'storeCaret(this)');
#				$this->setTag('textarea', 'onkeyup', 'storeCaret(this)');
			}
			$tmp['strform'] .= CRLF . getFormTitleNav($this->oL->m($fieldname).'&#160;'.($elK+1), $tmp['strHtmlTB']);
			//
			//
			$tmp['strform'] .= '<table cellspacing="1" cellpadding="2" border="0" width="100%"><tbody>';
			// get definition contents
			$tmp['str'] =& $elV['value'];
			//
			// Parse subtags, abbr + trns
			$arTmp['elK'] = $elK;
			if ($this->arDictParam['is_trsp'])
			{
				$tmp['strform'] .= $this->tag2field('trsp', $arTmp);
			}
			if (isset($this->arDictParam['is_audio']) && $this->arDictParam['is_audio'])
			{
				$tmp['strform'] .= $this->tag2field('audio', $arTmp);
			}
			if ($this->arDictParam['is_abbr'])
			{
				$tmp['strform'] .= $this->tag2field('abbr', $arTmp);
			}
			if ($this->arDictParam['is_trns'])
			{
				$tmp['strform'] .= $this->tag2field('trns', $arTmp);
			}
			//
			$tmp['intFormHeight'] = $oFunc->getFormHeight( $tmp['str'] );
			if ($tmp['intFormHeight'] <= 3) { $tmp['intFormHeight'] = 4; }
			$tmp['strform'] .= sprintf('<tr style="vertical-align:top"><td style="text-align:%s" class="%s">%s:%s</td>',
								$this->Gsys['css_align_right'], $this->Gtmp['cssTrClass'], $this->oL->m($fieldname), ''
								);
			$tmp['strform'] .= '<td class="gray" style="text-align:'.$this->Gsys['css_align_left'].';width:29%" colspan="2">';
			if ($this->arDictParam['is_htmled'] == 1)
			{
				$this->setTag('textarea', 'onfocus', 'toolbar_on(\''.$elK.'\')');
#				$this->setTag('textarea', 'onblur', 'toolbar_off(\''.$elK.'\')');
				$this->setTag('textarea', 'onclick', 'storeCaret(this)');
				$this->setTag('textarea', 'onselect', 'storeCaret(this)');
				$this->setTag('textarea', 'onkeyup', 'storeCaret(this)');
			}
			if ($tmp['str'] != '')
			{
				$oHtml->unsetTag('input');
				$oHtml->setTag('input', 'type', 'button');
				$oHtml->setTag('input', 'class', 'btn');
				$oHtml->setTag('input', 'style', 'float:right;width:8em');
				$oHtml->setTag('input', 'onmouseover', 'this.className=\'btnO\'');
				$oHtml->setTag('input', 'onmouseout',  'this.className=\'btn\'');
				$oHtml->setTag('input', 'onmousedown', 'this.className=\'btnD\'');
				$oHtml->setTag('input', 'onmouseup',   'this.className=\'btn\'');
				$oHtml->setTag('input', 'onclick', 'switch2edit(\''.$fieldname.'_'.$elK.'\')');
				$url_edit = $oHtml->input('#', $this->oL->m('3_edit'));
				$bg_tag = ' dir="ltr" style="font:90% monospace;border:1px solid #CCC; height:'.$tmp['intFormHeight'].'em;background:#FFF;width:auto;overflow:auto;margin:0"';
				/* definition preview, convert: `&amp; >` => `& >` => `&amp; &gt;` => `<span>&amp; &gt;</span>` */
				$tmp['strform'] .= '<div id="d_'.$fieldname.'_'.$elK.'"'.$bg_tag.'>'. $url_edit .
									(gw_bbcode_html( gw_fix_db_to_field($tmp['str']))) . '</div>';
				$this->setTag('textarea', 'style', 'text-align:'.$this->Gsys['css_align_left'].';width:100%;overflow:auto;display:none');
			}

			$tmp['strform'] .= $this->field('textarea',
									'arPre['.$fieldname.']['.$elK.'][value]',
									gw_fix_db_to_field($tmp['str']),
									$tmp['intFormHeight']-1
								);
			$tmp['strform'] .= '</td><td></td>';
			$tmp['strform'] .= '</tr>';
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
					$tmp['strform'] .= $this->tag2field($fV[0], $arTmp);
				}
			}
			// set width for colums to help browser to render the page,
			// this code also adds a nice visual bottom margin
			$tmp['strform'] .= '<tr><td style="width:14%;height:1px"></td>'.
							   '<td style="width:15%;height:1px"></td>'.
							   '<td style="width:65%;height:1px"></td>'.
							   '<td style="width:1%;height:1px"></td></tr>';
		}
		return $tmp['strform'];
	}
	//
	function load_abbr_trns()
	{
		global $gw_this;
		$this->load_abbr($gw_this['vars']['id']);
		$this->load_trns($gw_this['vars']['id']);
	}
	/* */
	function load_trns($id_dict = 0)
	{
		global $gw_this, $oDb, $oSqlQ, $arDictParam;
		$tmp['strform'] = '';
		// do auto-fill
		$tmp['arTmp'] = array();
		$tmp['arTmp']['--'] = $this->oL->m('000');
		/* */
		$field_name = 'abbr_short';
		if (GW_IS_BROWSE_ADMIN || $arDictParam['is_abbr_long'])
		{
			$field_name = 'abbr_long';
		}
		global $oSess;
		/* The list of translations for the dictionary */
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-abbr-list', $gw_this['vars']['locale_name'], 'AND a.id_group = "4" and a.id_dict = "'.$id_dict.'"'), 'st');
		while (list($abrK, $abrV) = each($arSql))
		{
			$tmp['arTmp'][sprintf("%03d", $abrV['id_abbr'])] = $abrV[$field_name];
		}
		$tmp['arTmp']['-- '] = '------------';
		/* The list of common translations  */
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-abbr-list', $gw_this['vars']['locale_name'], 'AND a.id_group = "4" and a.id_dict = "0"'), 'st');
		while (list($abrK, $abrV) = each($arSql))
		{
			$tmp['arTmp'][sprintf("%03d", $abrV['id_abbr'])] = $abrV[$field_name];
		}
		/* */
		$this->Set('arTrns', $tmp['arTmp']);
	}
	/* */
	function load_abbr($id_dict = 0)
	{
		global $gw_this, $oDb, $oSqlQ, $arDictParam;
		$tmp['strform'] = '';
		// do auto-fill
		$tmp['arTmp']['--'] = $this->oL->m('000');
		/* */
		$field_name = 'abbr_short';
		if (GW_IS_BROWSE_ADMIN || $arDictParam['is_abbr_long'])
		{
			$field_name = 'abbr_long';
		}
		/* The list of custom abbreviations for the dictionary */
		/* The list of custom and common abbreviations */
#		$tmp['arTmp']['--  '] = '------------';
		/* The list of abbreviations for the dictionary */
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-abbr-list', $gw_this['vars']['locale_name'], 'AND a.id_group IN (1,2,3,5) and a.id_dict = "'.$id_dict.'"'), 'st');
		while (list($abrK, $abrV) = each($arSql))
		{
			$tmp['arTmp'][sprintf("%03d", $abrV['id_abbr'])] = $abrV[$field_name];
		}
		$tmp['arTmp']['--   '] = '------------';
		/* The list of abbreviations */
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-abbr-list', $gw_this['vars']['locale_name'], 'AND a.id_group IN (1,2,3,5) and a.id_dict = "0"'), 'st');
		while (list($abrK, $abrV) = each($arSql))
		{
			$tmp['arTmp'][sprintf("%03d", $abrV['id_abbr'])] = $abrV[$field_name];
		}
		/* */
		$this->Set('arAbbr', $tmp['arTmp']);
	}
	/* */
	function make_trns($fieldname, $ar = array())
	{
		return $this->make_abbr($fieldname, $ar, 'trns');
	}
	/* */
	function make_abbr($fieldname, $ar = array(), $tag = 'abbr')
	{
		$tmp['strform'] = '';

		if ($tag == 'abbr')
		{
			$tmp['cur_element'] = 'arAbbr';
		}
		elseif ($tag == 'trns')
		{
			$tmp['cur_element'] = 'arTrns';
		}
		$tmp['cur_array'] = $this->$tmp['cur_element'];

		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		$this->unsetTag('input'); // reset settings for <input>
		$this->setTag('input', 'size', '25');
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
			$tmp['str'] = $this->objDom->get_content( $chV );
			// append new value
			if (!isset($tmp['cur_array'][$tmp['attributes']]))
			{
				if ($tmp['attributes'] == '')
				{
					$tmp['cur_array']['--'] = $this->oL->m('000');
				}
				else
				{
					$tmp['cur_array'][$tmp['attributes']] = $tmp['attributes'];
				}
			}
			/* Abbreviations, translations */
			$this->setTag('select', 'class', 'input0');
			$tmp['strform'] .= sprintf('<tr style="vertical-align:top"><td style="text-align:%s" class="%s">%s:%s</td>',
											$this->Gsys['css_align_right'], $this->Gtmp['cssTrClass'], $this->oL->m($fieldname), ''
									  );
#			prn_r( $tmp['cur_array'] );
			$tmp['strform'] .= '<td>' . $this->field('select', 'arPre['.$fieldname.']['.$ar['elK'].']['.$chK.'][attributes][lang]', $tmp['attributes'], '160', $tmp['cur_array']) . '</td>';
			$this->setTag('select', 'class', '');
			
			// enable HTML-editor events
			if ($this->arDictParam['is_htmled'] == 1)
			{
				$this->setTag('input', 'onfocus', 'toolbar_on(\''.$ar['elK'].'\');toolbar_symb_off(\''.$ar['elK'].'\')');
#				$this->setTag('input', 'onblur', 'toolbar_off(\''.$ar['elK'].'\');toolbar_symb_off(\''.$ar['elK'].'\')');
				$this->setTag('input', 'onclick', 'storeCaret(this)');
				$this->setTag('input', 'onselect', 'storeCaret(this)');
				$this->setTag('input', 'onkeyup', 'storeCaret(this)');
			}
			$tmp['strform'] .= '<td style="text-align:'.$this->Gsys['css_align_left'].'">' . $this->field('input', 'arPre['.$fieldname.']['.$ar['elK'].']['.$chK.'][value]', gw_fix_db_to_field($tmp['str']) ) . '</td>';

			$tmp['strform'] .= '<td>';
			// allow to add new element, default
			if ($chK == 0)
			{
				$tmp['strform'] .= '<input type="submit" style="width:24px;height:24px" class="submitok" name="'.
								   'arControl['.$fieldname.']['.GW_A_ADD.']['.$ar['elK'].']['.$chK.']" title="'.
								   $this->oL->m('3_add_'.$tag).'" value="+" />';
			}
			else // remove abbreviation
			{
				// use javascript to clean values; much faster that submit reload; submit also works
				$js['CurFieldVal'] = $this->text_field2id('arPre['.$fieldname.']['.$ar['elK'].']['.$chK.'][value]');
				$js['CurFieldAtt'] = $this->text_field2id('arPre['.$fieldname.']['.$ar['elK'].']['.$chK.'][attributes][lang]');
				$js['Onclick'] = 'document.forms[\'vbform\'].elements[\''.$js['CurFieldVal'].'\'].value=\'\'';
				$js['Onclick'] .= ';document.forms[\'vbform\'].elements[\''.$js['CurFieldAtt'].'\'].value=\'--\'';
				$tmp['strform'] .= '<input onclick="'.$js['Onclick'].'" type="button" style="width:24px;height:24px" class="submitdel" name="'.
								   'arControl['.$fieldname.']['.GW_A_REMOVE.']['.$ar['elK'].']['.$chK.']" title="'.
								   $this->oL->m('3_remove_'.$tag).'" value="&#215;" />';
			}
			$tmp['strform'] .= '</td>';
			$tmp['strform'] .= '</tr>';
		}
		return $tmp['strform'];
	}
	//
	function make_usg($fieldname, $ar = array())
	{
		return $this->make_set_array2textarea($fieldname, $ar);
	}
	//
	function make_src($fieldname, $ar = array())
	{
		return $this->make_set_textarea($fieldname, $ar);
	}
	//
	function make_syn($fieldname, $ar = array())
	{
		return $this->make_see($fieldname, $ar);
	}
	//
	function make_see($fieldname, $ar = array())
	{
		global $oFunc;
		$tmp['strform'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '', 'attributes' => array('xref' => ''));
		}
		//
		$this->unsetTag('textarea'); // reset settings for <textarea>
		$this->unsetTag('checkbox'); // reset settings for <input>
		$this->setTag('checkbox', 'title', $this->oL->m('1256'));
		$this->setTag('textarea', 'title', $this->oL->m('tip002'));
		/* */
		$tmp['isLink'] = 0;
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			$tmp['str'] .= $elV['value'];
			if (isset($elV['attributes']['text']))
			{
				$tmp['str'] .= ' [['.$elV['attributes']['text'].']]';
			}
			if (isset($elV['attributes']['is_link']))
			{
				$tmp['isLink'] = $elV['attributes']['is_link'];
			}
			$tmp['str'] .= CRLF;
		}
		$tmp['intFormHeight'] = $oFunc->getFormHeight( $tmp['str'] );
		$tmp['strform'] .= '<tr style="vertical-align:top">';
		$tmp['strform'] .= sprintf('<td style="text-align:%s" class="%s">%s:%s</td>',
								$this->Gsys['css_align_right'], $this->Gtmp['cssTrClass'], $this->oL->m($fieldname), ''
							);
		$tmp['strform'] .= '<td colspan="2">';
		$tmp['strform'] .= $this->field('textarea',
								'arPre['.$fieldname.']['.$ar['elK'].'][0][value]',
								gw_fix_db_to_field($tmp['str']),
								(intval($tmp['intFormHeight'] - 1))
							);
		$tmp['strform'] .= '</td>';
		$tmp['strform'] .= '<td>' . $this->field('checkbox', 'arPre['.$fieldname.']['.$ar['elK'].'][0][attributes][is_link]', $tmp['isLink']) . '</td>';
		$tmp['strform'] .= '</tr>';
		return $tmp['strform'];
	}
	/* */
	function make_antonym($fieldname, $ar = array())
	{
		return $this->make_see($fieldname, $ar);
	}
	//
	function make_address($fieldname, $ar = array())
	{
		return $this->make_set_textarea($fieldname, $ar);
	}
	//
	function make_phone($fieldname, $ar = array())
	{
		return $this->make_set_textarea($fieldname, $ar);
	}
	//
	function make_set_textarea($fieldname, $ar = array())
	{
		global $oFunc;
		$tmp['strform'] = $tmp['str'] = '';
		$tmp['arEl'] = isset($this->arEl[$fieldname][$ar['elK']]) ? $this->arEl[$fieldname][$ar['elK']] : array();
		//
		// do auto fill
		if (empty($tmp['arEl']))
		{
			$tmp['arEl'][0] = array('value' => '');
		}
		//
		$this->unsetTag('textarea'); // reset settings for <textarea>
		//
		while (list($elK, $elV) = each($tmp['arEl']))
		{
			$tmp['str'] .= $elV['value'];
			$tmp['str'] .= CRLF;
		}
		$tmp['intFormHeight'] = $oFunc->getFormHeight( $tmp['str'] );
		$tmp['strform'] .= CRLF . '<tr style="vertical-align:top">';
		$tmp['strform'] .= sprintf('<td style="text-align:%s" class="%s">%s:%s</td>',
								$this->Gsys['css_align_right'], $this->Gtmp['cssTrClass'], $this->oL->m($fieldname), ''
							);
		$tmp['strform'] .= '<td colspan="2">';
		$tmp['strform'] .= $this->field('textarea',
								'arPre['.$fieldname.']['.$ar['elK'].'][0][value]',
								gw_fix_db_to_field($tmp['str']),
								(intval($tmp['intFormHeight'] - 1))
							);
		$tmp['strform'] .= '</td>';
		$tmp['strform'] .= '<td></td>';
		$tmp['strform'] .= '</tr>';
		return $tmp['strform'];
	}
	//
	function make_set_array2textarea($fieldname, $ar = array())
	{
		global $oFunc; /* getFormHeight */
		$this->unsetTag('textarea');
		//
		$tmp['strform'] = $tmp['str'] = '';
		//
		if (!isset($this->arEl[$fieldname]))
		{
			$this->arEl[$fieldname] = array();
		}
		if (!isset($ar['elK']))
		{
			$ar['elK'] = 0;
		}
		for (reset($this->arEl[$fieldname]); list($elK, $elV) = each($this->arEl[$fieldname]);)
		{
			if (isset($elV['value']) && ($ar['elK'] == $elK))
			{
				$tmp['str'] .= $elV['value'];
				$tmp['str'] .= CRLF;
			}
			elseif (intval($ar['elK']) == intval($elK)) // multiarray
			{
				while (list($k, $v) = each($elV))
				{
					$tmp['str'] .= $v['value'];
					$tmp['str'] .= CRLF;
				}
				#print '<br>' . $ar['elK'] . ' == ' . $elK;
			}
		}
		//
		$tmp['intFormHeight'] = $oFunc->getFormHeight( $tmp['str'] );
		$tmp['strform'] .= CRLF . '<tr style="vertical-align:top">';
		$tmp['strform'] .= sprintf('<td style="text-align:%s" class="%s">%s:%s</td>',
							$this->Gsys['css_align_right'], $this->Gtmp['cssTrClass'], $this->oL->m($fieldname), ''
						   );
		$tmp['strform'] .= '<td colspan="2">';
		//
		// enable HTML-editor events
		if ($this->arDictParam['is_htmled'] == 1)
		{
#			$this->setTag('textarea', 'onfocus', 'toolbar_on(\''.$ar['elK'].'\')');
#			$this->setTag('textarea', 'onblur', 'toolbar_off(\''.$ar['elK'].'\');toolbar_symb_off(\''.$ar['elK'].'\')');
#			$this->setTag('textarea', 'onclick', 'storeCaret(this)');
#			$this->setTag('textarea', 'onselect', 'storeCaret(this)');
#			$this->setTag('textarea', 'onkeyup', 'storeCaret(this)');
		}
		if (isset($this->Gsys['is_ext_fields']) && $this->Gsys['is_ext_fields'])
		{
			if ($this->arDictParam['is_htmled'] == 1)
			{
				$this->setTag('textarea', 'onclick', 'storeCaret(this)');
				$this->setTag('textarea', 'onselect', 'storeCaret(this)');
				$this->setTag('textarea', 'onkeyup', 'storeCaret(this)');
			}
			$oFieldExt = new gw_fields_extension($fieldname);
			$tmp['strform'] .= $oFieldExt->get_js($fieldname, $ar['elK']);
			$tmp['strform'] .= $oFieldExt->get_html($fieldname, $ar['elK']);
		}
		$tmp['strform'] .= $this->field('textarea',
								'arPre['.$fieldname.']['.$ar['elK'].'][value]',
								gw_fix_db_to_field($tmp['str']),
								(intval($tmp['intFormHeight'] / 2) + 1)
							);
		$tmp['strform'] .= '</td>';
		$tmp['strform'] .= '</tr>';
		return $tmp['strform'];
	}

} // end of class

?>