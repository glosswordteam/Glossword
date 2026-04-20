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

class gw_addon_custom_az_admin extends gw_addon
{
    public $component          = 'custom-az';
    public $ar_groups          = [];
    public $ar_profile         = [];
    public $ar_profiles        = [];
    public $ar_profiles_browse = [];
    public $left_td_width      = '25%';

    /**
     * Initialize admin addon data for custom AZ profiles.
     */
    public function __construct()
    {
        $this->init();


        // Prepare the list of context profiles
        $sql = $this->oSqlQ->getQ('get-custom_az-profiles-adm');
        if (!$sql) {
            $this->oDb->haltmsg('Query storage error');
            return;
        }
        $ar_sql = $this->oDb->sqlRun($sql, $this->component);

        if (!is_array($ar_sql) || empty($ar_sql)) {
            $this->oDb->haltmsg('Query storage error');
            return;
        }
        foreach ($ar_sql as $ar_v) {
            $profile_title = $ar_v['profile_name'];
            // Highlight selected profile
            if ((int)$ar_v['id_profile'] === (int)$this->gw_this['vars'][GW_TARGET_ID]) {
                $profile_title = '<strong>' . $ar_v['profile_name'] . '</strong>';
            }
            $str_status = $ar_v['is_active'] ? '' : ' <span class="badge badge-secondary"> ' . $this->oL->m('not_published') . '</span>';
            $this->ar_profiles_browse[$ar_v['id_profile']] = $this->oHtml->a(
                $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component, [GW_TARGET_ID => $ar_v['id_profile']]),
                $profile_title
            ) . $str_status;

            $this->ar_profiles[$ar_v['id_profile']] = $ar_v['profile_name'];
        }
    }

    public function _get_nav()
    {
        unset($this->gw_this['ar_actions_list'][$this->component][GW_A_BROWSE]);
        return '<div class="actions-secondary">' .
            implode(' ', $this->gw_this['ar_actions_list'][$this->component]) .
            '</div>';
    }

    /**
     * Build form for custom AZ profile create/edit page.
     *
     * @param array $vars Form values.
     * @param int $runtime Runtime flag.
     * @param array $ar_broken Invalid field names.
     * @param array $ar_req Required field names.
     *
     * @return string
     */
    public function get_form_custom_az($vars, $runtime = 0, $ar_broken = [], $ar_req = [])
    {
        $str_form      = '';
        $td_class_1    = 'td1';
        $td_class_2    = 'td2';
        $td1_width     = '25%';
        $tid           = $this->gw_this['vars'][GW_TARGET_ID];
        $action        = $this->gw_this['vars'][GW_ACTION];
        $target        = $this->gw_this['vars'][GW_TARGET];
        $ar_req_map    = [];
        $ar_req_msg    = [];
        $ar_broken_msg = [];

        if (!empty($ar_req)) {
            $ar_req_map = array_flip($ar_req);
        }

        $o_form = new gwForms();
        $o_form->Set('action', $this->sys['page_admin']);
        $o_form->Set('submitok', $this->oL->m('3_save'));
        $o_form->Set('submitcancel', $this->oL->m('3_cancel'));
        $o_form->Set('formbgcolor', $this->ar_theme['color_2']);
        $o_form->Set('formbordercolor', $this->ar_theme['color_4']);
        $o_form->Set('formbordercolorL', $this->ar_theme['color_1']);
        $o_form->Set('align_buttons', $this->sys['css_align_right']);
        $o_form->Set('formwidth', '100%');
        $o_form->Set('charset', $this->sys['internal_encoding']);

        if (($action == GW_A_EDIT) && ($tid > 1)) {
            $o_form->isButtonDel = 1;
            $o_form->Set('submitdelname', 'remove');
            $o_form->Set('submitdel', $this->oL->m('3_remove'));
        }

        /* Mark required fields and show validation errors */
        foreach ($vars as $field_name => $field_value) {
            $ar_req_msg[$field_name]    = '';
            $ar_broken_msg[$field_name] = '';

            if (isset($ar_req_map[$field_name])) {
                $ar_req_msg[$field_name] = '&#160;<span class="red"><b>*</b></span>';
            }

            if (isset($ar_broken[$field_name])) {
                $ar_broken_msg[$field_name] = '<span class="red"><b>'
                    . $this->oL->m('reason_9')
                    . '</b></span><br />';
            }
        }

        if (!isset($ar_req_msg['profile_name'])) {
            $ar_req_msg['profile_name'] = '';
        }

        if (!isset($ar_broken_msg['profile_name'])) {
            $ar_broken_msg['profile_name'] = '';
        }

        $str_form .= gw_get_form_title_nav(
            $this->oL->m('1137'),
            '<span style="float:right">' . $o_form->get_button('submit') . '</span>'
        );
        $str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
        $str_form .= '<table class="gw2TableFieldset" width="100%">';
        $str_form .= '<tbody><tr><td style="width:' . $td1_width . '"></td><td></td></tr>';

        if ($tid != 1) {
            $str_form .= '<tr>'
                . '<td class="' . $td_class_1 . '">'
                . $o_form->field('checkbox', 'arPost[is_active]', $vars['is_active'])
                . '</td>'
                . '<td class="' . $td_class_2 . '">'
                . '<label for="' . $o_form->text_field2id('arPost[is_active]') . '">'
                . $this->oL->m('1320')
                . '</label></td>'
                . '</tr>';
        }

        $str_form .= '<tr>'
            . '<td class="' . $td_class_1 . '">'
            . $this->oL->m('1289')
            . $ar_req_msg['profile_name']
            . '</td>'
            . '<td class="' . $td_class_2 . '">'
            . $ar_broken_msg['profile_name']
            . $o_form->field('input', 'arPost[profile_name]', $vars['profile_name'])
            . '</td>'
            . '</tr>';

        $str_form .= '</tbody></table>';
        $str_form .= '</fieldset>';

        $str_form .= $o_form->field('hidden', GW_TARGET_ID, $tid);
        $str_form .= $o_form->field('hidden', GW_ACTION, $action);
        $str_form .= $o_form->field('hidden', GW_TARGET, $target);
        $str_form .= $o_form->field('hidden', $this->oSess->sid, $this->oSess->id_sess);

        return $o_form->Output($str_form);
    }

    /* */
    public function get_form_import($vars, $runtime = 0, $ar_broken = [], $ar_req = [])
    {
        $oForm = new gwForms();

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
        $oForm->Set('charset', $this->sys['internal_encoding']);
        if ($this->sys['is_upload']) {
            $oForm->Set('enctype', 'multipart/form-data');
        }

        $ar_req = array_flip($ar_req);
        /* mark fields as "Required" and display error message */
        foreach ((is_array($vars) ? $vars : array()) as $k => $v) {
            $ar_req_msg[$k] = $ar_broken_msg[$k] = '';
            if (isset($ar_req[$k])) {
                $ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>';
            }
            if (isset($ar_broken[$k])) {
                $ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m('reason_9') . '</b></span><br />';
            }
        }
        /* */
        if ($this->gw_this['vars']['tid'] && isset($this->ar_profiles[$this->gw_this['vars']['tid']])) {
            $str_form .= gw_get_form_title_nav(
                $this->ar_profiles[$this->gw_this['vars']['tid']],
                '<span style="float:right">' . $oForm->get_button('submit') . '</span>'
            );
        } else {
            $str_form .= gw_get_form_title_nav(
                $this->oL->m('3_profile'),
                '<span style="float:right">' . $oForm->get_button('submit') . '</span>'
            );
        }
        $str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
        $str_form .= '<table class="gw2TableFieldset" width="100%">';
        $str_form .= '<tbody><tr><td style="width:' . $v_td1_width . '"></td><td>';
        $str_form .= '</td></tr>';

        /* Allows to upload a file */
        if ($this->sys['is_upload']) {
            $oForm->setTag('select', 'class', 'input');
            $oForm->setTag('select', 'style', '');
            $oForm->setTag('file', 'id', 'file_location_xml');
            $oForm->setTag('file', 'dir', 'ltr');
            $oForm->setTag('file', 'size', '25');

            $oForm->setTag('textarea', 'style', 'height:15em;width:100%;font:85% verdana,arial,sans-serif"');
            $str_form .= '<tr>' .
                '<td class="td1">XML</td>' .
                '<td class="td2">' . $oForm->field('textarea', 'arPost[xml]', $vars['xml']) . '</td>' .
                '</tr>';

            $str_form             .= '<tr>' .
                '<td class="td1">&#160;</td>' .
                '<td class="td2">' . $oForm->field('file', 'file_location', $vars['file_location']) . '</td>' .
                '</tr>';
            $this->ar_profiles[0] = '(' . $this->oL->m('3_profile') . ': ' . $this->oL->m('3_add') . ')';
            $str_form             .= '<tr>' .
                '<td class="td1">' . $this->oL->m('3_profile') . '</td>' .
                '<td class="td2">' . $oForm->field(
                    'select',
                    'arPost[id_profile]',
                    $this->gw_this['vars']['tid'],
                    0,
                    $this->ar_profiles
                ) . '</td>' .
                '</tr>';
        }
        $str_form .= '</tbody></table>';
        $str_form .= '</fieldset>';
        $str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
        $str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
        $str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
        $str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
        $str_form .= $str_hidden;
        return $oForm->Output($str_form);
    }

    /* */
    public function get_form_export($vars, $runtime = 0, $ar_broken = [], $ar_req = [])
    {
        $oForm = new gwForms();

        $str_hidden  = '';
        $str_form    = '';
        $v_class_1   = 'td1';
        $v_class_2   = 'td2';
        $v_td1_width = '25%';

        $oForm = new gwForms();
        $oForm->Set('action', $this->sys['page_admin']);
        $oForm->Set('submitok', $this->oL->m('3_export'));
        $oForm->Set('submitcancel', $this->oL->m('3_cancel'));
        $oForm->Set('formbgcolor', $this->ar_theme['color_2']);
        $oForm->Set('formbordercolor', $this->ar_theme['color_4']);
        $oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
        $oForm->Set('align_buttons', $this->sys['css_align_right']);
        $oForm->Set('formwidth', '100%');
        $oForm->Set('charset', $this->sys['internal_encoding']);

        $ar_req = array_flip($ar_req);
        /* mark fields as "Required" and display error message */
        foreach ((is_array($vars) ? $vars : array()) as $k => $v) {
            $ar_req_msg[$k] = $ar_broken_msg[$k] = '';
            if (isset($ar_req[$k])) {
                $ar_req_msg[$k] = '&#160;<span class="red"><strong>*</strong></span>';
            }
            if (isset($ar_broken[$k])) {
                $ar_broken_msg[$k] = '<span class="red"><strong>' . $this->oL->m('reason_9') . '</strong></span><br />';
            }
        }
        /* */
        $str_form .= gw_get_form_title_nav(
            $this->ar_profile['profile_name'],
            '<span style="float:right">' . $oForm->get_button('submit') . '</span>'
        );
        $str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
        $str_form .= '<table class="gw2TableFieldset" width="100%">';
        $str_form .= '<tbody><tr><td style="width:' . $v_td1_width . '"></td><td>';
        $str_form .= '</td></tr>';
        /* */
        $str_form .= '<tr>' .
            '<td class="' . $v_class_1 . '">' . $this->oL->m('1068') . '</td>' .
            '<td class="disabled" style="text-align:left">' . wordwrap(
                $this->sys['path_export'] . '/' . $this->filename,
                16,
                "\xe2\x80\x8b",
                1
            ) . '</td>' .
            '</tr>';
        $str_form .= '<tr>' .
            '<td class="' . $v_class_1 . '">' . $oForm->field(
                'checkbox',
                'arPost[is_as_file]',
                $vars['is_as_file']
            ) . '</td>' .
            '<td class="' . $v_class_2 . '">' . '<label for="' . $oForm->text_field2id(
                'arPost[is_as_file]'
            ) . '">' . $this->oL->m('1299') . '</label></td>' .
            '</tr>';
        $str_form .= '</tbody></table>';
        $str_form .= '</fieldset>';

        $str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
        $str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
        $str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
        $str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
        $str_form .= $str_hidden;
        return $oForm->Output($str_form);
    }

    /**
     * Execute current admin component action.
     *
     * Loads action handler file, checks permissions and appends output.
     *
     * @return void
     */
    public function alpha()
    {
        global $strR;

        $action_file          = $this->sys['path_component_action'];
        $action_name          = $this->gw_this['vars'][GW_ACTION];
        $target_name          = $this->gw_this['vars'][GW_TARGET];
        $permission_names     = [];
        $permissions_like_sql = '1=0';

        /* Call an action */
        if (!is_file($action_file)) {
            return;
        }

        /* Collect granted permissions only */
        foreach ($this->oSess->ar_permissions as $permission_name => $is_allowed) {
            if (!$is_allowed) {
                continue;
            }
            $permission_names[] = $permission_name;
        }

        /*
         * Build legacy LIKE condition for permission map.
         * Use a safe fallback when no permissions are granted.
         */
        if (!empty($permission_names)) {
            $permissions_like_sql = 'cmm.req_permission_map LIKE "%:'
                . implode(':%" OR cmm.req_permission_map LIKE "%:', $permission_names)
                . ':%"';
        }

        $ar_sql = $this->oDb->sqlRun(
            $this->oSqlQ->getQ(
                'get-component-action-perm',
                $permissions_like_sql,
                $action_name,
                $target_name
            )
        );

        $this->ar_component = isset($ar_sql[0]) && is_array($ar_sql[0]) ? $ar_sql[0] : [];

        /* Component settings found */
        if (!empty($this->ar_component)) {
            $this->component                = $this->ar_component['id_component_name'];
            $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname'])
                . ': '
                . $this->oL->m($this->ar_component['aname']);

            include_once($action_file);

            $strR .= $this->str;

            return;
        }

        $strR                           .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';
        $strR                           .= '<p class="xt">' . $target_name . ': ' . $action_name . '</p>';
        $this->sys['id_current_status'] = '';
    }
}

/* */
$oAddonAdm = new gw_addon_custom_az_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['custom_pages_' . GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';

