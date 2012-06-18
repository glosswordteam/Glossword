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
if ( !defined( 'IN_GW' ) )
{
	die( '<!-- $Id: visual-themes_admin.php 549 2008-08-16 14:29:59Z glossword_team $ -->' );
}
/* */

class gw_addon_visual_themes_admin extends gw_addon
{

	var $component = 'visual-themes';
	var $int_found;
	/* Autoexec */


	function gw_addon_visual_themes_admin ()
	{
		$this->init();
	}

	/* */


	function _get_nav ()
	{
		return '<div class="actions-secondary">' .
		implode( ' ', $this->gw_this['ar_actions_list'][$this->component] ) .
		'</div>';
	}

	/* */


	function get_tpl_pages ( $id_theme )
	{
		$ar_theme_gp = array ( );
		$url_part = $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $id_theme;
		if ( $id_theme == 'gw_admin' )
		{
			$ar_theme_gp[1] = $this->oHtml->a( $url_part . '&w1=1', $this->oL->m( '1141' ) );
			$ar_theme_gp[12] = $this->oHtml->a( $url_part . '&w1=12', $this->oL->m( '2_page__' ) );
			$ar_theme_gp[11] = $this->oHtml->a( $url_part . '&w1=11', $this->oL->m( '2_login' ) );
			$ar_theme_gp[14] = $this->oHtml->a( $url_part . '&w1=14', $this->oL->m( '1144' ) );
			$ar_theme_gp[13] = $this->oHtml->a( $url_part . '&w1=13', $this->oL->m( '1145' ) );
			$ar_theme_gp[15] = $this->oHtml->a( $url_part . '&w1=15', $this->oL->m( '1214' ) );
			$ar_theme_gp[0] = $this->oHtml->a( $url_part . '&w1=css', 'CSS' );
		}
		else
		{
			$ar_theme_gp[1] = $this->oHtml->a( $url_part . '&w1=1', $this->oL->m( '1141' ) );
			$ar_theme_gp[3] = $this->oHtml->a( $url_part . '&w1=3', $this->oL->m( '1143' ) );
			$ar_theme_gp[4] = $this->oHtml->a( $url_part . '&w1=4', $this->oL->m( 'dict' ) );
			$ar_theme_gp[5] = $this->oHtml->a( $url_part . '&w1=5', $this->oL->m( '2_page_term_browse' ) );
			$ar_theme_gp[6] = $this->oHtml->a( $url_part . '&w1=6', $this->oL->m( '1144' ) );
			$ar_theme_gp[7] = $this->oHtml->a( $url_part . '&w1=7', $this->oL->m( 'term' ) );
			$ar_theme_gp[8] = $this->oHtml->a( $url_part . '&w1=8', $this->oL->m( 'printversion' ) );
			$ar_theme_gp[9] = $this->oHtml->a( $url_part . '&w1=9', $this->oL->m( 'custom_pages' ) );
			$ar_theme_gp[10] = $this->oHtml->a( $url_part . '&w1=10', $this->oL->m( '1107' ) );
			$ar_theme_gp[13] = $this->oHtml->a( $url_part . '&w1=13', $this->oL->m( '1145' ) );
			$ar_theme_gp[2] = $this->oHtml->a( $url_part . '&w1=2', $this->oL->m( '1142' ) );
			$ar_theme_gp[0] = $this->oHtml->a( $url_part . '&w1=css', 'CSS' );
		}
		return $ar_theme_gp;
	}

	/* */


	function get_form_tpl ( $vars, $runtime = 0, $ar_broken = array ( ), $ar_req = array ( ) )
	{
		$str_hidden = '';
		$str_form = '';
		$v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->Set( 'action', $this->sys['page_admin'] );
		$oForm->Set( 'submitdel', $this->oL->m( '3_remove' ) );
		$oForm->Set( 'submitok', $this->oL->m( '3_save' ) );
		$oForm->Set( 'submitcancel', $this->oL->m( '3_cancel' ) );
		$oForm->Set( 'formbgcolor', $this->ar_theme['color_2'] );
		$oForm->Set( 'formbordercolor', $this->ar_theme['color_4'] );
		$oForm->Set( 'formbordercolorL', $this->ar_theme['color_1'] );
		$oForm->Set( 'align_buttons', $this->sys['css_align_right'] );
		$oForm->Set( 'formwidth', '100%' );
		$oForm->Set( 'charset', $this->sys['internal_encoding'] );
		$ar_req = array_flip( $ar_req );
		/* mark fields as "Required" and display error message */
		while ( is_array( $vars ) && list($k, $v) = each( $vars ) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if ( isset( $ar_req[$k] ) )
			{
				$ar_req_msg[$k] = '&#160;<span class="red"><strong>*</strong></span>';
			}
			if ( isset( $ar_broken[$k] ) )
			{
				$ar_broken_msg[$k] = '<span class="red"><strong>' . $this->oL->m( 'reason_9' ) . '</strong></span><br />';
			}
		}
		/* */
		$str_form .= getFormTitleNav( $this->oL->m( '1136' ), '<strong class="xw">' . $this->gw_this['vars']['tid'] . '</strong>' );

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset gray" width="100%">';
		$str_form .= '<thead><tr><td style="width:' . $v_td1_width . '"></td><td></td></tr></thead><tbody>';

		$int_sort = 0;
		for ( reset( $vars ); list($k, $arV) = each( $vars ); )
		{
			$int_sort += 10;
			$str_form .= '<tr>' .
					'<td class="td1">' . $arV['settings_key'] .
					$oForm->field( 'hidden', 'arPost[' . $arV['settings_key'] . '][int_sort]', $int_sort ) .
					$oForm->field( 'hidden', 'arPost[' . $arV['settings_key'] . '][old]', $arV['settings_key'] ) .
					':</td>' .
					'<td class="td2">' . $oForm->field( 'input', 'arPost[' . $arV['settings_key'] . '][new]', htmlspecialchars_ltgt( $arV['settings_key'] ) ) . '</td>' .
					'</tr>';
			if ( $arV['settings_key'] == $this->gw_this['vars']['w2'] )
			{
				$int_sort += 10;
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( '3_add' ) .
						$oForm->field( 'hidden', 'arPost[new_template][int_sort]', $int_sort ) .
						':</td>' .
						'<td class="td2">' . $oForm->field( 'input', 'arPost[new_template][new]', '' ) . '</td>' .
						'</tr>';
			}
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field( 'hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION] );
		$str_form .= $oForm->field( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$str_form .= $oForm->field( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		$str_form .= $oForm->field( 'hidden', 'tid', $this->gw_this['vars']['tid'] );
		$str_form .= $oForm->field( 'hidden', 'w1', $this->gw_this['vars']['w1'] );
		$str_form .= $oForm->field( 'hidden', 'w2', $this->gw_this['vars']['w2'] );
		$str_form .= $str_hidden;
		return $oForm->Output( $str_form );
	}

	/* */


	function get_form_theme ( $vars, $runtime = 0, $ar_broken = array ( ), $ar_req = array ( ) )
	{
		$str_hidden = '';
		$str_form = '';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->Set( 'action', $this->sys['page_admin'] );
		$oForm->Set( 'submitdel', $this->oL->m( '3_remove' ) );
		$oForm->Set( 'submitok', $this->oL->m( '3_save' ) );
		$oForm->Set( 'submitcancel', $this->oL->m( '3_cancel' ) );
		$oForm->Set( 'formbgcolor', $this->ar_theme['color_2'] );
		$oForm->Set( 'formbordercolor', $this->ar_theme['color_4'] );
		$oForm->Set( 'formbordercolorL', $this->ar_theme['color_1'] );
		$oForm->Set( 'align_buttons', $this->sys['css_align_right'] );
		$oForm->Set( 'formwidth', '100%' );
		$oForm->Set( 'charset', $this->sys['internal_encoding'] );
		$ar_req = array_flip( $ar_req );
		/* mark fields as "Required" and display error message */
		while ( is_array( $vars ) && list($k, $v) = each( $vars ) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if ( isset( $ar_req[$k] ) )
			{
				$ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>';
			}
			if ( isset( $ar_broken[$k] ) )
			{
				$ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m( 'reason_9' ) . '</b></span><br />';
			}
		}
		$str_form .= '<script type="text/javascript">/*<![CDATA[*/
function switch2edit(id)
{
	el = gw_getElementById("arPost_" + id + "_");
	el_d = gw_getElementById("d_" + id);
	el_d.style.display = "none";
	el.style.display = "block";
}
/*]]>*/
</script>
';
		/* */
		$str_form .= getFormTitleNav( $this->cur_template, '<strong class="xw">' . $this->gw_this['vars']['tid'] . '</strong>' );

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset gray" width="100%">';
		$str_form .= '<tbody>';

		$oForm->setTag( 'input', 'size', '25' );
		$oForm->setTag( 'input', 'dir', 'ltr' );
		for ( reset( $vars ); list($k, $arV) = each( $vars ); )
		{
			$arV['settings_value'] = preg_replace( '/ $/', '&#32;', $arV['settings_value'] );
#			$arV['settings_value'] = preg_replace('/{(\w)/', '{%\\1', $arV['settings_value']);
#			$arV['settings_value'] = preg_replace('/(\w)}/', '\\1%}', $arV['settings_value']);
#			$arV['settings_value'] = str_replace(array('{', '}'), array('{%', '%}'), $arV['settings_value']);
			$oForm->setTag( 'input', 'class', 'input' );
			$oForm->setTag( 'input', 'maxlength', '250' );
			$bg_tag = '';
			$bg_ctrl = '';
			/* Do not allow to edit admin theme */
			if ( $this->gw_this['vars']['tid'] == 'gw_admin' )
			{
				if ( $arV['settings_key'] == 'id_theme' || $arV['settings_key'] == 'is_active' )
				{
					continue;
				}
			}
			if ( preg_match( "/color_/", $arV['settings_key'] ) )
			{
				$oForm->setTag( 'input', 'class', 'input50' );
				$oForm->setTag( 'input', 'maxlength', '7' );
				$invert_color = $this->oFunc->math_hex2negative( $arV['settings_value'] );
				$bg_tag = ' style="border:1px #CCC solid;color:#' . $invert_color . ';background:' . $arV['settings_value'] . '"';
				#$bg_ctrl = ' [ Edit ] ';
			}
			if ( is_numeric( $this->gw_this['vars']['w1'] ) && ($this->gw_this['vars']['w1'] <= 2) )
			{
				/* Colors, theme setting (author, etc.) */
				if ( preg_match( "/^is_/", $arV['settings_key'] ) )
				{
					/* create checkbox */
					$str_form .= '<tr>' .
							'<td class="' . $v_class_1 . '"' . $bg_tag . '><label for="' . $oForm->text_field2id( 'arPost[' . $arV['settings_key'] . ']' ) . '">' . $arV['settings_key'] . ':</label></td>' .
							'<td style="width:75%" class="' . $v_class_2 . '">' . $oForm->field( 'checkbox', 'arPost[' . $arV['settings_key'] . ']', htmlspecialchars_ltgt( $arV['settings_value'] ) ) . $bg_ctrl . '</td>' .
							'</tr>';
				}
				else
				{
					/* input field otherwise */
					$str_form .= '<tr>' .
							'<td class="' . $v_class_1 . '"' . $bg_tag . '>' . $arV['settings_key'] . ':</td>' .
							'<td style="width:75%" class="' . $v_class_2 . '">' . $oForm->field( 'input', 'arPost[' . $arV['settings_key'] . ']', htmlspecialchars_ltgt( $arV['settings_value'] ) ) . $bg_ctrl . '</td>' .
							'</tr>';
				}
			}
			else
			{
				/* Controls for the group of templates */
				$url_tpl_add = $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_ADD . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $this->gw_this['vars']['tid'] . '&w1=' . $this->gw_this['vars']['w1'] . '&w2=' . $arV['settings_key'];
				$url_tpl_remove = $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_REMOVE . '&' . GW_TARGET . '=' . $this->component . '&isConfirm=1&tid=' . $this->gw_this['vars']['tid'] . '&w1=' . $this->gw_this['vars']['w1'] . '&w2=' . $arV['settings_key'];

				$str_tpl_ctrl = '<span class="actions-third" style="float:right">' . $this->oHtml->a( $url_tpl_add, $this->oL->m( '3_add' ) ) . ' ';

				$this->oHtml->setTag( 'a', 'class', 'submitdel' );
				$this->oHtml->setTag( 'a', 'onclick', 'return confirm(\'' . $this->oL->m( '3_remove' ) . ': &quot;' . $arV['settings_key'] . '&quot;. ' . $this->oL->m( '9_remove' ) . '\' )' );
				$str_tpl_ctrl .= $this->oHtml->a( $url_tpl_remove, $this->oL->m( '3_remove' ) );
				$this->oHtml->setTag( 'a', 'onclick', '' );
				$this->oHtml->setTag( 'a', 'class', '' );

				$str_tpl_ctrl .= '</span>';

				$int_height = $this->oFunc->getFormHeight( $arV['settings_value'], 20 ) + 2;
				$bg_tag = ' dir="ltr" style="height:' . $int_height . 'em"';

				$this->oHtml->unsetTag( 'input' );
				$this->oHtml->setTag( 'input', 'type', 'button' );
				$this->oHtml->setTag( 'input', 'class', 'btn' );
				$this->oHtml->setTag( 'input', 'style', 'float:right;width:8em' );
				$this->oHtml->setTag( 'input', 'onmouseover', 'this.className=\'btnO\'' );
				$this->oHtml->setTag( 'input', 'onmouseout', 'this.className=\'btn\'' );
				$this->oHtml->setTag( 'input', 'onmousedown', 'this.className=\'btnD\'' );
				$this->oHtml->setTag( 'input', 'onmouseup', 'this.className=\'btn\'' );
				$this->oHtml->setTag( 'input', 'onclick', 'switch2edit(\'' . $arV['settings_key'] . '\')' );
				$url_edit = $this->oHtml->input( '#', $this->oL->m( '3_edit' ) );

				$oForm->setTag( 'textarea', 'style', 'text-align:left;width:100%;overflow:auto;display:none' );

				$arV['settings_value'] = gw_fix_db_to_field( $arV['settings_value'] );
				$str_textarea = $oForm->field( 'textarea', 'arPost[' . $arV['settings_key'] . ']', ($arV['settings_value'] ), 15 );

				$str_form .= '<tr>' .
						'<td class="' . $v_class_2 . '">' .
						'<div class="xu" style="margin-bottom:2px;height:2em;overflow:hidden;">' . $str_tpl_ctrl . ' <strong>' . $arV['settings_key'] . '</strong>: ' . '</div>' .
						'<div class="codeinput" id="d_' . $arV['settings_key'] . '"' . $bg_tag . '>' . $url_edit .
						(gw_bbcode_html( $arV['settings_value'] )) . '</div>' . $str_textarea . '</td>' .
						'</tr>';
			}
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field( 'hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION] );
		$str_form .= $oForm->field( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$str_form .= $oForm->field( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		$str_form .= $oForm->field( 'hidden', 'tid', $this->gw_this['vars']['tid'] );
		$str_form .= $oForm->field( 'hidden', 'w1', $this->gw_this['vars']['w1'] );
		$str_form .= $str_hidden;
		return $oForm->Output( $str_form );
	}

	/* */


	function get_form_export ( $vars )
	{
		$str_form = '';
		/* */
		$oForm = new gwForms();
		$oForm->Set( 'action', $this->sys['page_admin'] );
		$oForm->Set( 'submitdel', $this->oL->m( '3_remove' ) );
		$oForm->Set( 'submitok', $this->oL->m( '3_export' ) );
		$oForm->Set( 'submitcancel', $this->oL->m( '3_cancel' ) );
		$oForm->Set( 'formbgcolor', $this->ar_theme['color_2'] );
		$oForm->Set( 'formbordercolor', $this->ar_theme['color_4'] );
		$oForm->Set( 'formbordercolorL', $this->ar_theme['color_1'] );
		$oForm->Set( 'align_buttons', $this->sys['css_align_right'] );
		$oForm->Set( 'formwidth', '100%' );
		$oForm->Set( 'charset', $this->sys['internal_encoding'] );
		/* */
		$str_form .= getFormTitleNav( $this->oL->m( '3_export' ), '<strong class="xw">' . $this->gw_this['vars']['tid'] . '</strong>' );

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody>';
		$str_form .= '<tr><td style="width:15%"></td><td></td></tr><tbody>';
		$str_form .= '<tr>' .
				'<td class="td1"></td>' .
				'<td>';

		$str_form .= '<table class="gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
		$str_form .= '<tbody>';
		/* Per each page */
		for (; list($k, $page) = each( $vars['tpl_pages'] ); )
		{
			$str_external_link = preg_replace( "/>(.*?)<\/a>/u", '>&gt;&gt;&gt;</a>', $page ) . ' ';
			$str_external_link = str_replace( 'a href', 'a title="' . $this->oL->m( '3_edit' ) . '" href', $str_external_link );
			$str_checkbox = $oForm->field( 'checkbox', 'arPost[tpl_page][' . $k . ']', 1 );
			$str_label = '<label for="arPost_tpl_page_' . $k . '_">' . strip_tags( $page ) . '</label>';
			$str_form .= '<tr><td style="width:1%">' . $str_checkbox . '</td><td class="td2 actions-third">' . $str_external_link . $str_label . '</td></tr>';
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</td></tr>';

		$str_form .= '<tr>' .
				'<td class="td1">' . $oForm->field( 'checkbox', 'arPost[is_as_file]', $vars['is_as_file'] ) . '</td>' .
				'<td class="td2">' . '<label for="' . $oForm->text_field2id( 'arPost[is_as_file]' ) . '">' . $this->oL->m( '1299' ) . '</label></td>' .
				'</tr>';

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field( 'hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION] );
		$str_form .= $oForm->field( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$str_form .= $oForm->field( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		$str_form .= $oForm->field( 'hidden', 'tid', $this->gw_this['vars']['tid'] );
		$str_form .= $oForm->field( 'hidden', 'arPost[is_binary]', 1 );
		return $oForm->Output( $str_form );
	}


	function get_form_import ( $vars )
	{
		$str_form = '';
		/* */
		$oForm = new gwForms();
		$oForm->Set( 'action', $this->sys['page_admin'] );
		$oForm->Set( 'submitok', $this->oL->m( '3_save' ) );
		$oForm->Set( 'submitcancel', $this->oL->m( '3_cancel' ) );
		$oForm->Set( 'formbgcolor', $this->ar_theme['color_2'] );
		$oForm->Set( 'formbordercolor', $this->ar_theme['color_4'] );
		$oForm->Set( 'formbordercolorL', $this->ar_theme['color_1'] );
		$oForm->Set( 'align_buttons', $this->sys['css_align_right'] );
		$oForm->Set( 'formwidth', '100%' );
		$oForm->Set( 'charset', $this->sys['internal_encoding'] );
		if ( $this->sys['is_upload'] )
		{
			$oForm->Set( 'enctype', 'multipart/form-data' );
		}
		/* */
		$str_form .= getFormTitleNav( $this->oL->m( '3_import' ), '<span style="float:right">' . $oForm->get_button( 'submit' ) . '</span>' );

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset gray" width="100%">';
		$str_form .= '<tbody>';
		$str_form .= '<tr><td style="width:22%"></td><td></td></tr><tbody>';

		$str_form .= '<tr>' .
				'<td class="td1">XML:</td>' .
				'<td class="td2"><textarea ' .
				' onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'arPost[\'+\'xml\'+\']\'].select();}"' .
				' style="width:100%;font:85% verdana,arial,sans-serif"' .
				' name="arPost[xml]" id="arPost_xml_" dir="ltr" cols="45" rows="10">' . htmlspecialchars_ltgt( $vars['xml'] ) . '</textarea>' .
				'</td>' .
				'</tr>';

		/* Allows to upload a file */
		if ( $this->sys['is_upload'] )
		{
			$oForm->setTag( 'select', 'class', 'input' );
			$oForm->setTag( 'select', 'style', '' );
			$oForm->setTag( 'file', 'id', 'file_location_xml' );
			$oForm->setTag( 'file', 'dir', 'ltr' );
			$oForm->setTag( 'file', 'size', '25' );
			$str_form .= '<tr>' .
					'<td class="td1">&#160;</td>' .
					'<td class="td2">' . $oForm->field( 'file', 'file_location', $vars['file_location'] ) . '</td>' .
					'</tr>';
			$this->gw_this['ar_themes_select']['0'] = '(' . $this->oL->m( '1148' ) . ')';
			if ( $this->gw_this['vars']['tid'] == '' )
			{
				$this->gw_this['vars']['tid'] = 0;
			}
			$str_form .= '<tr>' .
					'<td class="td1">' . $this->oL->m( '1147' ) . '</td>' .
					'<td class="td2">' . $oForm->field( 'select', 'arPost[id_theme]', $this->gw_this['vars']['tid'], 0, $this->gw_this['ar_themes_select'] ) . '</td>' .
					'</tr>';
			$str_form .= '<tr>' .
					'<td class="td1">&#160;</td>' .
					'<td class="td2">' . $this->oL->m( '1146' ) . '</td>' .
					'</tr>';
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field( 'hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION] );
		$str_form .= $oForm->field( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$str_form .= $oForm->field( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		return $oForm->Output( $str_form );
	}

	/* */


	function alpha ()
	{
		global $strR;
		/* Call an action */
		if ( file_exists( $this->sys['path_component_action'] ) )
		{
			/* check for permissions */
			$ar_perms = $this->oSess->ar_permissions;
			foreach ( $ar_perms AS $permission => $is )
			{
				if ( !$is )
				{
					unset( $ar_perms[$permission] );
				}
			}
			$ar_sql_like2 = 'cmm.req_permission_map LIKE "%:' . implode( ':%" OR cmm.req_permission_map LIKE "%:', array_keys( $ar_perms ) ) . ':%"';
			$arSql = $this->oDb->sqlRun( $this->oSqlQ->getQ( 'get-component-action-perm',
									$ar_sql_like2,
									$this->gw_this['vars'][GW_ACTION],
									$this->gw_this['vars'][GW_TARGET] )
			);
			$this->ar_component = isset( $arSql[0] ) ? $arSql[0] : array ( );
			/* Component settings found */
			if ( !empty( $this->ar_component ) )
			{
				$this->sys['id_current_status'] = $this->oL->m( $this->ar_component['cname'] ) . ': ' . $this->oL->m( $this->ar_component['aname'] );
				$this->component = & $this->ar_component['id_component_name'];
				include_once( $this->sys['path_component_action'] );
				$strR .= $this->str;
			}
			else
			{
				$this->sys['id_current_status'] = '';
				$strR .= '<p class="xu">' . $this->oL->m( 'reason_13' ) . '</p>';
				$strR .= '<p class="xt">' . $this->gw_this['vars'][GW_TARGET] . ': ' . $this->gw_this['vars'][GW_ACTION] . '</p>';
			}
		}
	}

}

/* */
$oAddonAdm = new gw_addon_visual_themes_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['visual-themes_' . GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>