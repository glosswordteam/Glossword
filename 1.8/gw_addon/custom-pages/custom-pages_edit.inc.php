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
/* Included from $oAddonAdm->alpha(); */

$table_pages = gw_get_tbl_name('pages');
$table_pages_phrase = gw_get_tbl_name('pages_phrase');
$page_id = $this->gw_this['vars'][GW_TARGET_ID];
$mode = $this->gw_this['vars']['mode'];

/* Check permission to edit the page */
$ar_sql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-adm', $page_id));
$ar_parsed = isset($ar_sql[0]) ? $ar_sql[0] : [];

/* No such page */
if (empty($ar_parsed)) {
    $page_id = 0;
}

$ar_queries = [];

/* Page ID is not defined */
if (!$page_id) {
    /* Change heading */
    $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']) . ': ' . $this->oL->m('3_browse');
    $this->gw_this['vars'][GW_ACTION] = GW_A_BROWSE;
    $this->sys['path_component_action'] = $this->sys['path_addon']
        . '/'
        . $this->gw_this['vars'][GW_TARGET]
        . '/'
        . $this->gw_this['vars'][GW_TARGET]
        . '_'
        . $this->gw_this['vars'][GW_ACTION]
        . '.inc.php';

    include_once($this->sys['path_component_action']);

    return;
}

// Check permissions
$page_owner_id = isset($ar_parsed['id_user']) ? (int)$ar_parsed['id_user'] : 0;
$session_user_id = (int)$this->oSess->id_user;

$can_edit_all = $this->oSess->is('is-cpages');
$can_edit_own = $this->oSess->is('is-cpages-own') && ($page_owner_id === $session_user_id);

$is_allow_edit = ($can_edit_all || $can_edit_own) ? 1 : 0;

if (!$is_allow_edit) {
    $this->str .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';
    return;
}

switch ($mode) {
    case 'up':
    case 'dn':
        if ($this->_move_page($page_id, $mode)) {
            return;
        }
        break;

    case 'reset':
        if ($this->_reset_page_sort_by_page($page_id)) {
            return;
        }
        break;

    case 'off':
    case 'on':
        if ($this->_set_page_tree_active($page_id, $mode)) {
            return;
        }
        break;
}


/* Navigation */
$this->str .= $this->_get_nav();

/* Editing */
$ar_req_fields = [];

/* Not submitted */
if ($this->gw_this['vars']['post'] == '') {
    /* Default settings */
    $ar_sql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-lang-adm', $page_id));
    $ar_parsed['page'] = $ar_sql2;
    $ar_parsed['ar'] = $this->ar;

    /* Removing */
    if (!empty($this->gw_this['vars']['remove'])) {
        $str_pagename = (string)$page_id;

        foreach ($ar_parsed['page'] as $ar_page) {
            if (!isset($ar_page['id_lang']) || $ar_page['id_lang'] != $this->gw_this['vars']['locale_name']) {
                continue;
            }

            $str_pagename = '<div>'
                . htmlspecialchars((string)$ar_page['page_title'], ENT_QUOTES, 'UTF-8')
                . '</div>';
            $str_pagename .= '<div>'
                . htmlspecialchars((string)$ar_page['page_descr'], ENT_QUOTES, 'UTF-8')
                . '</div>';
        }

        /* Change heading */
        $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname'])
            . ': '
            . $this->oL->m('3_remove');

        $o_form_confirm = new gwConfirmWindow();
        $o_form_confirm->action = $this->sys['page_admin'];
        $o_form_confirm->submitok = $this->oL->m('3_remove');
        $o_form_confirm->submitcancel = $this->oL->m('3_cancel');
        $o_form_confirm->formbgcolor = $this->ar_theme['color_2'];
        $o_form_confirm->formbordercolor = $this->ar_theme['color_4'];
        $o_form_confirm->formbordercolorL = $this->ar_theme['color_1'];
        $o_form_confirm->setQuestion(
            '<p class="xr"><strong class="red">' . $this->oL->m('9_remove')
            . '</strong></p><p class="xt"><span class="gray">' . $this->oL->m('3_remove')
            . ': </span>' . $str_pagename . '</p>'
        );
        $o_form_confirm->tAlign = 'center';
        $o_form_confirm->formwidth = '400';
        $o_form_confirm->setField('hidden', 'tid', $page_id);
        $o_form_confirm->setField('hidden', 'w1', isset($this->gw_this['vars']['w1']) ? $this->gw_this['vars']['w1'] : '');
        $o_form_confirm->setField('hidden', 'w2', isset($this->gw_this['vars']['w2']) ? $this->gw_this['vars']['w2'] : '');
        $o_form_confirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
        $o_form_confirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
        $o_form_confirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);

        $this->str .= $o_form_confirm->Form();

        return;
    }

    $ar_pre = isset($this->gw_this['vars']['arPre']) ? $this->gw_this['vars']['arPre'] : null;
    $is_first = 1;

    if (is_array($ar_pre)) {
        $is_first = 0;
        $ar_parsed = gw_ParsePre($ar_parsed, $ar_pre);
    }

    $this->str .= $this->get_form($ar_parsed, $is_first, 0, $ar_req_fields);

    /* Editing tips */
    $ar_help_map = [
        'dict_name' => 'tip028',
        'announce'  => 'tip029',
        '1058'      => 'tip030',
        'keywords'  => 'tip007',
        '1073'      => 'tip031',
        '1059'      => 'tip032',
    ];

    $str_help = '<dl>';
    foreach ($ar_help_map as $key => $value) {
        $str_help .= '<dt><strong>' . $this->oL->m($key) . '</strong></dt>';
        $str_help .= '<dd>' . $this->oL->m($value) . '</dd>';
    }
    $str_help .= '</dl>';

    $this->str .= '<br />' . kTbHelp($this->oL->m('2_tip'), $str_help);
} else {
    $ar_pre = $this->gw_this['vars']['arPre'];

    /* Fix on/off options */
    $ar_pre['is_active'] = isset($ar_pre['is_active']) ? (int)$ar_pre['is_active'] : 0;
    $ar_pre['id_parent'] = isset($ar_pre['id_parent']) ? (int)$ar_pre['id_parent'] : 0;

    $page_uri = isset($ar_pre['page_uri']) ? trim((string)$ar_pre['page_uri']) : '';
    if ($page_uri === '') {
        $page_uri = 'page-' . (int)$this->sys['time_now_gmt_unix'];
    }

    $q_page = [
        'id_parent'     => $ar_pre['id_parent'],
        'is_active'     => ($ar_pre['is_active'] ? 1 : 0),
        'page_icon'     => isset($ar_pre['page_icon']) ? (string)$ar_pre['page_icon'] : '',
        'page_php_1'    => isset($ar_pre['page_php_1']) ? (string)$ar_pre['page_php_1'] : '',
        'page_php_2'    => isset($ar_pre['page_php_2']) ? (string)$ar_pre['page_php_2'] : '',
        'page_uri'      => $page_uri,
        'id_user'       => $this->oSess->id_user,
        'date_modified' => $this->sys['time_now_gmt_unix'],
    ];

    /* Set is_active for subpages */
    if (isset($this->ar[$page_id]['ch'])) {
        $ar_keys = gw_ctlg_get_tree($this->ar, $page_id);

        if (is_array($ar_keys)) {
            foreach ($ar_keys as $page_id) {
                $page_id = (int)$page_id;
                if ($page_id <= 0) {
                    continue;
                }

                $ar_queries[] = 'UPDATE `' . $table_pages . '`
                    SET `is_active` = ' . (int)$q_page['is_active'] . '
                    WHERE `id_parent` = ' . $page_id;
            }
        }
    }

    $ar_queries[] = 'DELETE FROM `' . $table_pages_phrase . '` WHERE `id_page` = ' . $page_id;

    $id_page_phrase_base = (int)$this->oDb->MaxId($table_pages_phrase, 'id_page_phrase');

    if (isset($ar_pre['page']) && is_array($ar_pre['page'])) {
        foreach ($ar_pre['page'] as $page_offset => $ar_v) {
            $page_title = isset($ar_v['page_title']) ? gw_fix_input_to_db($ar_v['page_title']) : '';
            $page_descr = isset($ar_v['page_descr']) ? gw_fix_input_to_db($ar_v['page_descr']) : '';
            $page_content = isset($ar_v['page_content']) ? gw_fix_input_to_db($ar_v['page_content']) : '';

            if (isset($ar_v['id_page_phrase']) && (int)$ar_v['id_page_phrase'] > 0) {
                $id_page_phrase = (int)$ar_v['id_page_phrase'];
            } else {
                $id_page_phrase = $id_page_phrase_base + (int)$page_offset;
            }

            $q_page_phrase = [
                'id_page_phrase' => $id_page_phrase,
                'id_page'        => $page_id,
                'page_title'     => $page_title,
                'page_descr'     => $page_descr,
                'page_content'   => $page_content,
                'page_keywords'  => isset($ar_v['page_keywords']) ? (string)$ar_v['page_keywords'] : '',
                'id_lang'        => isset($ar_v['id_lang']) ? (string)$ar_v['id_lang'] : '',
            ];

            $ar_queries[] = gw_sql_insert(
                $q_page_phrase,
                $table_pages_phrase,
                'id_page_phrase = ' . $id_page_phrase
            );
        }
    }

    $ar_queries[] = gw_sql_update($q_page, $table_pages, 'id_page = ' . $page_id);

    $this->str .= postQuery(
        $ar_queries,
        $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->gw_this['vars'][GW_TARGET], ['note_afterpost' => $this->oL->m('1332')]),
        $this->sys['isDebugQ'],
        $this->sys['isPause']
    );
}