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
    die('<!-- Not in App  -->');
}

/* */

class gw_addon_vkbd_admin extends gw_addon
{

    /* Current component name */
    public $component = '';
    public $ar_groups = [];
    public $ar_profile = [];
    public $ar_profiles = [];

    /* Autoexec */
    public function __construct()
    {
        $this->init();
    }

    /* */
    public function _get_nav()
    {
        /* The list of profiles */
        $sql = $this->oSqlQ->getQ('get-vkbd-profiles-adm');
        if (!$sql) {
            $this->oDb->haltmsg('Query storage error');
            return '';
        }

        $sqlRows = $this->oDb->sqlRun($sql, $this->component);

        $this->ar_profiles = array();

        if (is_array($sqlRows)) {
            foreach ($sqlRows as $profile) {
                /* For <select> */
                if (isset($profile['id_profile'])) {
                    $this->ar_profiles[$profile['id_profile']] = $profile;
                }
            }
        }

        return '<div class="actions-secondary">'
            . implode(' ', $this->gw_this['ar_actions_list'][$this->component])
            . '</div>';
    }

    /**
     * HTML-form for a profile
     */
    public function get_form_vkbd($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
    {
        $vars = (array)$vars + [
                'is_active'     => 0,
                'is_index_page' => 0,
                'vkbd_name'     => '',
                'vkbd_letters'  => '',
            ];

        $strHidden = '';
        $strForm = '';
        $classTd1 = 'td1';
        $classTd2 = 'td2';
        $td1Width = '25%';

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

        if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) {
            $oForm->isButtonDel = 1;
            $oForm->Set('submitdelname', 'remove');
            $oForm->Set('submitdel', $this->oL->m('3_remove'));
        }

        $requiredFields = array_flip($ar_req);
        $requiredMessages = array();
        $brokenMessages = array();
        $brokenMessage = '<span class="red"><b>' . $this->oL->m('reason_9') . '</b></span><br />';

        /* mark fields as "Required" and display error message */
        foreach ((array)$vars as $key => $value) {
            $requiredMessages[$key] = '';
            $brokenMessages[$key] = '';

            if (isset($requiredFields[$key])) {
                $requiredMessages[$key] = '&#160;<span class="red"><b>*</b></span>';
            }

            if (isset($ar_broken[$key])) {
                $brokenMessages[$key] = $brokenMessage;
            }
        }

        if (!isset($requiredMessages['vkbd_name'])) {
            $requiredMessages['vkbd_name'] = '';
        }
        if (!isset($requiredMessages['vkbd_letters'])) {
            $requiredMessages['vkbd_letters'] = '';
        }
        if (!isset($brokenMessages['vkbd_name'])) {
            $brokenMessages['vkbd_name'] = '';
        }
        if (!isset($brokenMessages['vkbd_letters'])) {
            $brokenMessages['vkbd_letters'] = '';
        }

        /* */
        $strForm .= getFormTitleNav(
            $this->oL->m('1137'),
            '<span style="float:right">' . $oForm->get_button('submit') . '</span>'
        );

        $strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
        $strForm .= '<table class="gw2TableFieldset" width="100%">';
        $strForm .= '<tbody><tr><td style="width:' . $td1Width . '"></td><td>';
        $strForm .= '</td></tr>';

        $strForm .= '<tr>'
            . '<td class="' . $classTd1 . '">'
            . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active'])
            . '</td>'
            . '<td class="' . $classTd2 . '">'
            . '<label for="' . $oForm->text_field2id('arPost[is_active]') . '">'
            . $this->oL->m('1320')
            . '</label></td>'
            . '</tr>';

        $strForm .= '<tr>'
            . '<td class="' . $classTd1 . '">'
            . $oForm->field('checkbox', 'arPost[is_index_page]', $vars['is_index_page'])
            . '</td>'
            . '<td class="' . $classTd2 . '">'
            . '<label for="' . $oForm->text_field2id('arPost[is_index_page]') . '">'
            . $this->oL->m('1401')
            . '</label></td>'
            . '</tr>';

        $strForm .= '<tr>'
            . '<td class="' . $classTd1 . '">'
            . $this->oL->m('1289') . $requiredMessages['vkbd_name']
            . '</td>'
            . '<td class="' . $classTd2 . '">'
            . $brokenMessages['vkbd_name']
            . $oForm->field('textarea', 'arPost[vkbd_name]', $vars['vkbd_name'])
            . '</td>'
            . '</tr>';

        $oForm->setTag('textarea', 'style', 'font-size:200%');

        $strForm .= '<tr>'
            . '<td class="' . $classTd1 . '">'
            . $this->oL->m('1306') . $requiredMessages['vkbd_letters']
            . '</td>'
            . '<td class="' . $classTd2 . '">'
            . $brokenMessages['vkbd_letters']
            . $oForm->field('textarea', 'arPost[vkbd_letters]', $vars['vkbd_letters'])
            . '<div class="tooltip">' . $this->oL->m('1307') . '</div></td>'
            . '</tr>';

        $strForm .= '</tbody></table>';
        $strForm .= '</fieldset>';

        if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) {
            $strForm .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
        }

        $strForm .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
        $strForm .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
        $strForm .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
        $strForm .= $strHidden;

        return $oForm->Output($strForm);
    }

    /* */
    public function alpha()
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
            $ar_sql_like2 = 'cmm.req_permission_map LIKE "%:' . implode(':%" OR cmm.req_permission_map LIKE "%:', array_keys($ar_perms)) . ':%"';
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
                $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']) . ': ' . $this->oL->m($this->ar_component['aname']);
                $this->component =& $this->ar_component['id_component_name'];
                include_once($this->sys['path_component_action']);
                $strR .= $this->str;
            } else {
                $this->sys['id_current_status'] = '';
                $strR .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';
                $strR .= '<p class="xt">' . $this->gw_this['vars'][GW_TARGET] . ': ' . $this->gw_this['vars'][GW_ACTION] . '</p>';
            }
        }
    }
}

/* */
$oAddonAdm = new gw_addon_vkbd_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['virtual-keyboards_' . GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
