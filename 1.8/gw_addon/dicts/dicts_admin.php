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
/* */
class gw_addon_dicts_admin extends gw_addon
{
	/* Current component name */
	var $component;
	/* Autoexec */
	function __construct()
	{
		$this->init();
	}
	/* */
	function _get_nav()
	{
		/* The list of profiles */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-vkbd-profiles-adm'), $this->component);
		$ar_profiles = array();
		$this->ar_profiles = array();
		foreach ((is_array($arSql) ? $arSql : array()) as $k => $arV)
		{
			/* For <select> */
			$this->ar_profiles[$arV['id_profile']] = $arV;
		}
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			($this->gw_this['vars']['id'] ? ' '.$this->oHtml->a(
				$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_ADD.'&'.GW_TARGET.'='.GW_T_TERMS.'&id=' . $this->gw_this['vars']['id'],
				$this->oL->m('3_add_term'),
				$this->oL->m('terms').': '.$this->oL->m('3_add')
			) : '' ).
			'</div>';
	}
	/**
	 * HTML-form for a profile
	 */
	function get_form_dict($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		global $topic_mode, $arFields, $gw_this;
		$topic_mode = 'form';

        $str_hidden  = '';
        $str_form    = '';
        $v_class_1   = 'td1';
        $v_class_2   = 'td2';
        $v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('3_save'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('formwidth', '100%');
		$oForm->Set('charset', 'utf-8');

		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
		{
			$oForm->isButtonDel = 1;
			$oForm->Set('submitdelname', 'remove');
			$oForm->Set('submitdel', $this->oL->m('3_remove'));
		}

        /* Mark required fields and display validation errors */
        $ar_req = array_flip($ar_req);
        foreach ($vars as $field_name => $field_value) {
            $ar_req_msg[$field_name] = '';
            $ar_broken_msg[$field_name] = '';

            if (isset($ar_req[$field_name])) {
                $ar_req_msg[$field_name] = '&#160;<span class="red"><strong>*</strong></span>';
            }

            if (isset($ar_broken[$field_name])) {
                $ar_broken_msg[$field_name] = '<span class="red"><strong>'
                    . $this->oL->m('reason_9')
                    . '</strong></span><br />';
            }
        }

		/* Search length */
		$arNums = array(0 => $this->oL->m('1038'), 1, 2, 3, 4, 5);
		/* Tree of topics */
		$arTopics = gw_create_tree_topics();

		/* */
		$str_form .= gw_get_form_title_nav($this->oL->m('1137'), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );


		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$oForm->Set('isButtonSubmit', 1);

			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', "arPost[is_active]", $vars['is_active']) . '</td>'.
						'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('dict_name') . ':' . $ar_req_msg['title'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['title'] . $oForm->field("textarea", "arPost[title]", textcodetoform($vars['title']), 3) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('descr') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field("textarea", "arPost[description]", textcodetoform($vars['description']), 7) . '</td>'.
						'</tr>';
			/* Used to select the currect topic */
			$gw_this['vars']['id_topic'] = $vars['id_topic'];
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('topic')  . ':' . $ar_req_msg['id_topic'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_topic'].
						'<select class="input75" dir="ltr" name="arPost[id_topic]">' . ctlgGetTopicsRow($arTopics, 0, 1) . '</select>' .
						'</td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
			/* */
			$str_form .= gw_get_form_title_nav($this->oL->m('1136'), '');
			$oForm->setTag('select', 'class',  'input75');
			$oForm->setTag('select', 'style',  '');
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('default_lang')  . ':' . $ar_req_msg['lang'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[lang]', $vars['lang'], '0', array_merge(array($this->oL->m('1270')), $this->gw_this['vars']['ar_languages'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('visual_theme') . ':' . $ar_req_msg['visualtheme'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[visualtheme]', $vars['visualtheme'], 0, array_merge(array($this->oL->m('1270')), $this->gw_this['ar_themes_select'])) . '</td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';

			/* System settings */
			$str_form .= gw_get_form_title_nav($this->oL->m('1138'), '');
			$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('sysname') . ':' . $ar_req_msg['tablename'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['tablename'] . $oForm->field("input", "arPost[tablename]", textcodetoform( $vars['tablename'] ) ) . '</td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
			$oForm->setTag('input', 'onkeyup', '');

			$str_form .= $oForm->field("hidden", GW_ACTION, GW_A_ADD);
			$str_form .= $oForm->field("hidden", GW_TARGET, GW_T_DICT);
			$str_form .= $oForm->field("hidden", "arPost[uid]", $vars['tablename']);
			$oForm->unsetTag('select');
		}
		else
		{
			$oForm->setTag('select', 'class',  'input75');

            /**
             * -----------------------------------------------------------------------------
             *  General settings
             * -----------------------------------------------------------------------------
             */
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';

			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>'.
						'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('dict_name') . ':' . $ar_req_msg['title'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['title'] . $oForm->field('textarea', 'arPost[title]', gw_fix_db_to_field($vars['title']), 2) . '</td>'.
						'</tr>';
			
			/* 1.8.5 Dictionary URI */
			if ($this->sys['pages_link_mode'] == GW_PAGE_LINK_URI)
			{
				$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
				$oForm->setTag('input', 'maxlength', '255');
				$oForm->setTag('input', 'size', '50');
				$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1073') . ':' . $ar_req_msg['dict_uri'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['dict_uri'] . $oForm->field("input", "arPost[dict_uri]", textcodetoform( $vars['dict_uri'] ) ) . '</td>'.
						'</tr>';
				$oForm->setTag('input', 'onkeyup', '');
			}
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('announce') . ':' . $ar_req_msg['announce'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['announce'] . $oForm->field('textarea', 'arPost[announce]',  gw_fix_db_to_field($vars['announce']), $this->oFunc->getFormHeight($vars['announce'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('descr')  . ':' . $ar_req_msg['description'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPost[description]', gw_fix_db_to_field($vars['description']), $this->oFunc->getFormHeight($vars['description'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('keywords') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPost[keywords]', gw_fix_db_to_field($vars['keywords']), $this->oFunc->getFormHeight($vars['keywords'])) . '</td>'.
						'</tr>';
			/* Used to select the currect topic */
			$gw_this['vars']['tid'] = $vars['id_topic'];

			$str_form .= '<tr>'.
						'<td class="td1">' . $this->oL->m('topic')  . ':' . $ar_req_msg['id_topic'] . '</td>'.
						'<td class="td2 gray">' . $ar_broken_msg['id_topic']
							   . '<select class="input75" dir="ltr" name="arPost[id_topic]">'
							   . gw_get_thread_pages($arTopics, 0, 1)
							   . '</select>';
			/* Shortcut to the list of topics */
            if ($this->oSess->is('is-topics')) {
                $edit_url = $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, GW_T_TOPICS);
                $str_form .= ' <span class="actions-third">' . $this->oHtml->a(
                        $edit_url,
                        $this->oL->m('3_edit')
                    ) . '</span>';
            }
			$str_form .= '</td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';

			/**
             * -----------------------------------------------------------------------------
             *  Visual settings
             * -----------------------------------------------------------------------------
             */
			$str_form .= gw_get_form_title_nav($this->oL->m('1136'), '');
			$objCells = new htmlRenderCells();
			$objCells->tClass = '';
			$oForm->setTag('select', 'style',  '');
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
			$str_form .= '<tr>'.
						'<td class="td1">' . $this->oL->m('default_lang') . ':' . $ar_req_msg['lang'] . '</td>'.
						'<td class="td2">' . $oForm->field('select', 'arPost[lang]', $vars['lang'], 0, array_merge(array($this->oL->m('1270')), $this->gw_this['vars']['ar_languages'])) . '</td>'.
						'</tr>';

			/* Visual themes */
			$str_form .= '<tr>'.
						'<td class="td1">' . $this->oL->m('visual_theme') . ':' . $ar_req_msg['visualtheme'] . '</td>'.
						'<td class="td2 gray">' . $oForm->field('select', 'arPost[visualtheme]', $vars['visualtheme'], 0, array_merge(array($this->oL->m('1270')), $this->gw_this['ar_themes_select']));
			if ($this->oSess->is('is-sys-settings'))
			{
                $edit_url = $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, GW_T_THEME);
				$str_form .= ' <span class="actions-third">'.$this->oHtml->a($edit_url, $this->oL->m('3_edit')).'</span>';
			}
			$str_form .= '</td>'.
						'</tr>';

            /* 1.8.6: Custom alphabetic order */
            $ar_sql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-custom_az-profiles'), 'custom_az');
            $ar_custom_az = [];
            foreach ($ar_sql as $ar_v) {
                $ar_custom_az[$ar_v['id_profile']] = $ar_v['profile_name'];
            }
            $str_form .= '<tr>' .
                '<td class="td1">' . $this->oL->m('custom_az') . ':' . $ar_req_msg['id_custom_az'] . '</td>' .
                '<td class="td2 gray">' . $oForm->field(
                    'select',
                    'arPost[id_custom_az]',
                    $vars['id_custom_az'],
                    0,
                    $ar_custom_az
                );
            if ($this->oSess->is('is-sys-settings')) {
                $edit_url = $this->oUrlBuilder->build_admin_url(
                    GW_A_BROWSE,
                    GW_T_CUSTOM_AZ,
                    [GW_TARGET_ID => $vars['id_custom_az']]
                );
                $str_form .= ' <span class="actions-third">' . $this->oHtml->a(
                        $edit_url,
                        $this->oL->m('3_edit')
                    ) . '</span>';
            }
            $str_form .= '</td>' .
                '</tr>';

            /* 1.8.7: Virtual keyboards */
            $ar_sql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-vkbd-profiles'), 'vkbd');
            $ar_vkbd = [0 => $this->oL->m('is_0')];
            foreach ($ar_sql as $ar_v) {
                $ar_vkbd[$ar_v['id_profile']] = $ar_v['vkbd_name'];
            }
            $str_form .= '<tr>' .
                '<td class="td1">' . $this->oL->m('virtual_keyboard') . ':' . $ar_req_msg['id_vkbd'] . '</td>' .
                '<td class="td2 gray">' . $oForm->field('select', 'arPost[id_vkbd]', $vars['id_vkbd'], 0, $ar_vkbd);

            if ($this->oSess->is('is-sys-settings')) {
                $edit_url = $this->oUrlBuilder->build_admin_url(
                    GW_A_BROWSE,
                    GW_T_VIRTUAL_KEYBOARD,
                    [GW_TARGET_ID => $vars['id_vkbd']]
                );
            }
            $str_form .= ' <span class="actions-third">' . $this->oHtml->a($edit_url, $this->oL->m('3_edit')) . '</span>';

            $str_form .= '</td>'
                . '</tr>';

            // Options
            $oForm->setTag('input', 'class', 'input0');
			$oForm->setTag('input', 'style', '');
			$oForm->setTag('input', 'maxlength', '4');
			$oForm->setTag('input', 'size', '5');
			$oForm->setTag('input', 'dir', $this->sys['css_dir_numbers'] );
#			$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $ar_broken_msg['page_limit'] . $oForm->field('input', 'arPost[page_limit]', $vars['page_limit']) . '</td>'.
                        '<td class="'.$v_class_2.'"><label for="' . $oForm->text_field2id('arPost[page_limit]') . '">' . $this->oL->m('page_limit') . '</label>' .
						'</tr>';
			$oForm->setTag('input', 'dir', $this->sys['css_dir_numbers'] );
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('input', 'arPost[page_limit_search]', $vars['page_limit_search']) . '</td>'.
                        '<td class="'.$v_class_2.'"><label for="' . $oForm->text_field2id('arPost[page_limit_search]') . '">' . $this->oL->m('1096') . '</label>' .
						'</tr>';
			$oForm->setTag('input', 'class', '');
			$oForm->setTag('input', 'dir', '');
			$oForm->setTag('input', 'size', '');
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_az]', $vars['is_show_az']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_show_az_">' . $this->oL->m('allow_letters') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_full]', $vars['is_show_full']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_show_full_">' . $this->oL->m('is_show_full') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_tooltip_defn]', $vars['is_show_tooltip_defn']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_show_tooltip_defn_">' . $this->oL->m('1045') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_authors]', $vars['is_show_authors']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_show_authors_">' . $this->oL->m('is_show_authors') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_date_modified]', $vars['is_show_date_modified']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_show_date_modified_">' . $this->oL->m('is_show_date_modified') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_abbr_long]', $vars['is_abbr_long']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_abbr_long_">' . $this->oL->m('is_abbr_long') . '</label></td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';

			/* Recently added */
#			$str_form .= getFormTitleNav($this->oL->m('recent'), '');
#			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<fieldset class="admform"><legend class="xt gray">'.$this->oL->m('recent').'</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1288') . ':' . $ar_req_msg['recent_terms_display'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[recent_terms_display]', $vars['recent_terms_display'], 0, array($this->oL->m('is_0'), $this->oL->m('1286'), $this->oL->m('1287'))) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1274') . ':' . $ar_req_msg['recent_terms_sorting'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[recent_terms_sorting]', $vars['recent_terms_sorting'], 0, array($this->oL->m('1272'), $this->oL->m('1273'))) . '</td>'.
						'</tr>';
			$oForm->setTag('input', 'class', 'input0');
			$oForm->setTag('input', 'style', '');
			$oForm->setTag('input', 'maxlength', '4');
			$oForm->setTag('input', 'size', '5');
			$oForm->setTag('input', 'dir', $this->sys['css_dir_numbers'] );
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('input', 'arPost[recent_terms_number]', $vars['recent_terms_number']) . '</td>'.
                        '<td class="'.$v_class_2.'"><label for="' . $oForm->text_field2id('arPost[recent_terms_number]') . '">' . $this->oL->m('termsamount') . '</label>' .
						'</tr>';

			$oForm->setTag('input', 'class', 'input');
			$oForm->setTag('input', 'dir', '');
			$oForm->setTag('input', 'size', '');
			$oForm->setTag('input', 'maxlength', '255');
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
			
			/* Page options */
#			$str_form .= getFormTitleNav($this->oL->m('page_options'), '');
			$str_form .= '<fieldset class="admform"><legend class="xt gray">'.$this->oL->m('page_options').'</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody><tr><td>';
			$ar_page_options = array(
				'is_show_term_suggest' => '1095',
				'is_show_term_report' => 'bug_report',
				'is_show_page_refresh' => 'page_refresh',
				'is_show_page_send' => '1275',
				'is_show_add_to_favorites' => 'page_fav',
				'is_show_add_to_search' => '1271',
				'is_show_printversion' => 'printversion'
			);
            foreach ($ar_page_options as $fieldname => $caption) {
                $ar_page_options_cell[] = '<table cellspacing="0" cellpadding="0" border="0" width="100%">' .
                    '<tbody><tr style="vertical-align:middle" class="xt">' .
                    '<td class="td1" style="width:25%">' . $oForm->field(
                        'checkbox',
                        'arPost[' . $fieldname . ']',
                        $vars[$fieldname]
                    ) . '</td>' .
                    '<td><label for="' . $oForm->text_field2id('arPost[' . $fieldname . ']') . '">' . $this->oL->m(
                        $caption
                    ) . '</label></td>' .
                    '</tr></tbody>' .
                    '</table>';
            }
			$objCells->X = 3;
			$objCells->Y = 99;
			$objCells->ar = $ar_page_options_cell;
			$objCells->tClass = 'table-cells';
			$str_form .= $objCells->RenderCells();
			$str_form .= '</td>';
			$str_form .= '</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';

            /**
             * -----------------------------------------------------------------------------
             *  Dictionary elements settings
             * -----------------------------------------------------------------------------
             */
			$str_form .= gw_get_form_title_nav($this->oL->m('sect_edit_dict'), '');
			$str_form .= '<fieldset class="admform"><legend class="xt gray">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody>';
			$str_form .= '<tr><td>';
			/* Construct fields */
			reset($arFields);
			/* term is required by default */
			unset($arFields[1], $arFields[-1], $arFields[-2], $arFields[-3], $arFields[-4], $arFields[-5]);
			$arFieldsStr = array();
			foreach ($arFields as $k => $v)
			{
				$fieldname = 'is_'.$v[0];
				$vars[$fieldname] = isset($vars[$fieldname]) ? $vars[$fieldname] : 0;
				$arFieldsStr[] = '<table cellspacing="0" cellpadding="0" border="0" width="100%">'.
								 '<tbody><tr style="vertical-align:middle" class="xt">'.
								 '<td class="td1" style="width:25%">' . $oForm->field('checkbox', 'arPost['.$fieldname.']', $vars[$fieldname]) . '</td>'.
								 '<td><label for="'.$oForm->text_field2id('arPost['.$fieldname.']').'">' . $this->oL->m($v[0]) . '</label></td>'.
								 '</tr></tbody>'.
								 '</table>';
			}
			$objCells->X = 3;
			$objCells->Y = 99;
			$objCells->ar = $arFieldsStr;
			$objCells->tClass = 'table-cells';
			$str_form .= $objCells->RenderCells();
			$str_form .= '</td></tr></tbody></table>';
			$str_form .= '</fieldset>';

            /**
             * -----------------------------------------------------------------------------
             *  Settings for internal search engine
             * -----------------------------------------------------------------------------
             */
			$str_form .= gw_get_form_title_nav($this->oL->m('1056'), '');
			$str_form .= '<fieldset class="admform"><legend class="xt gray">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('min_srch_length') . ':' . $ar_req_msg['min_srch_length'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field("select", "arPost[min_srch_length]", $vars['min_srch_length'], 0, $arNums ) . '</td>'.
						'</tr>';
			/* Check for existent stopwords */
			$ar_lang_stopwords = gw_get_stop_words_locales();
    		$oForm->setTag('select', 'multiple', 'multiple');
			$oForm->setTag('select', 'size', '4');
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', "arPost[is_filter_stopwords]", $vars['is_filter_stopwords']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="arPost_is_filter_stopwords_">' . $this->oL->m('1047') . ':</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td></td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[ar_filter_stopwords][]', $vars['ar_filter_stopwords'], 0, $ar_lang_stopwords) . '</td>'.
						'</tr>';
			$oForm->setTag('select', 'multiple', '');
			$oForm->setTag('select', 'size', '');
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';

            /**
             * -----------------------------------------------------------------------------
             *  System settings
             * -----------------------------------------------------------------------------
             */
			$str_form .= gw_get_form_title_nav($this->oL->m('1138'), '');
			$str_form .= '<fieldset class="admform"><legend class="xt gray">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('date_created') . ':</td>'.
					'<td class="'.$v_class_2.'">' . htmlFormSelectDate("arPost[date_created]", @date("YmdHis", $vars['date_created'])) . '</td>'.
					'</tr>';
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('date_modif') . ':</td>'.
					'<td class="disabled">' . date_extract_int($vars['date_modified'], "%d %F %Y %H:%i") . '&#160;</td>'.
					'</tr>';
			/* 1.8.5 Checking input characters */
			$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('sysname') . ':' . $ar_req_msg['tablename'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['tablename'] . $oForm->field("input", "arPost[tablename]", textcodetoform( $vars['tablename'] ) ) . '</td>'.
					'</tr>';
			$oForm->setTag('input', 'onkeyup', '');
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_dict_as_index]', $vars['is_dict_as_index']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_dict_as_index_">' . $this->oL->m('is_dict_as_index') . '</label></td>'.
					'</tr>';
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_leech]', $vars['is_leech']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost[is_leech]').'">' . $this->oL->m('allow_leecher') . '</label></td>'.
					'</tr>';
			$str_form .= '</tbody></table>';

			$str_form .= '</fieldset>';
		} // add/edit

        if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) {
            $str_form .= $oForm->field('hidden', GW_TARGET_ID, $this->gw_this['vars'][GW_TARGET_ID]);
        }
        $str_form .= $oForm->field('hidden', 'arPost[tablename_old]', $vars['tablename']);
        $str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
        $str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
        $str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
        $str_form .= $oForm->field('hidden', 'id', $this->gw_this['vars']['id']);
        $str_form .= $oForm->field('hidden', 'post', 1);
        $str_form .= $str_hidden;
        return $oForm->Output($str_form);
	}

	/* */
    function alpha()
    {
        global $strR;
        /* Call an action */
        if (file_exists($this->sys['path_component_action'])) {
            /* check for permissions */
            $ar_perms = $this->oSess->ar_permissions;
            foreach ($ar_perms as $permission => $is) {
                if (!$is) {
                    unset($ar_perms[$permission]);
                }
            }
            $ar_sql_like2 = 'cmm.req_permission_map LIKE "%:' . implode(
                    ':%" OR cmm.req_permission_map LIKE "%:',
                    array_keys($ar_perms)
                ) . ':%"';
            $arSql = $this->oDb->sqlRun(
                $this->oSqlQ->getQ(
                    'get-component-action-perm',
                    $ar_sql_like2,
                    $this->gw_this['vars'][GW_ACTION],
                    $this->gw_this['vars'][GW_TARGET]
                )
            );
            $this->ar_component = isset($arSql[0]) ? $arSql[0] : [];
            /* Component settings found */
            if (!empty($this->ar_component)) {
                $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']) . ': ' . $this->oL->m(
                        $this->ar_component['aname']
                    );
                $this->component                =& $this->ar_component['id_component_name'];
                include_once($this->sys['path_component_action']);
                $strR .= $this->str;
            } else {
                $this->sys['id_current_status'] = '';
                $strR                           .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';
                $strR                           .= '<p class="xt">' . $this->gw_this['vars'][GW_TARGET] . ': ' . $this->gw_this['vars'][GW_ACTION] . '</p>';
            }
        }
    }
}

/* */
$oAddonAdm = new gw_addon_dicts_admin;
$oAddonAdm->alpha();
/* Do not load old components */
$pathAction = '';
