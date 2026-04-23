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

$this->str .= $this->_get_nav();

/**
 * Conditions:
 * - User is an admin.
 * - User is the dictionary owner and may edit own dictionaries.
 * - User may edit other users' dictionaries.
 */
$is_allow_dict = 0;
$dict_id = (int) $this->gw_this['vars']['id'];

/* Dictionary ID is specified */
if ($dict_id > 0) {
    global $arDictParam;

    /* 1.8.9: Quick remove is allowed, confirmation is not required. */
    if (
        $this->oSess->is('is-sys-settings')
        || $this->oSess->is('is-dicts')
        || (
            ((int) $arDictParam['id_user'] === (int) $this->oSess->id_user)
            && $this->oSess->is('is-dicts-own')
        )
    ) {
        $is_allow_dict = 1;
    }

    if (!$is_allow_dict) {
        $this->str .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';
        $this->str .= '<div class="xt">' . $this->oL->m('1258') . '</div>';
        $this->str .= '<div class="xt">' . $this->oL->m('1260') . '</div>';

        return;
    }
} else {
    /* No dictionary ID is specified */
    /* Show the list of dictionaries */
    $this->str .= '<div class="margin-inside">';
    $this->str .= '<div class="xu">' . $this->oL->m('srch_selectdict') . ':</div>';
    $this->str .= '<ul class="gwsql">';

    $cnt_dict = 0;

    foreach ((array) $this->gw_this['ar_dict_list'] as $dict_item) {
        if (
            $this->oSess->is('is-sys-settings')
            || $this->oSess->is('is-dicts')
            || (
                ((int) $dict_item['id_user'] === (int) $this->oSess->id_user)
                && $this->oSess->is('is-dicts-own')
            )
        ) {
            $confirm_title = str_replace(
                ["\\", "'", "\r", "\n"],
                ["\\\\", "\\'", '\r', '\n'],
                (string) $dict_item['title']
            );

            $confirm_message = $this->oL->m('3_remove')
                . ': &quot;'
                . $confirm_title
                . '&quot;. '
                . $this->oL->m('9_remove');

            $this->oHtml->setTag(
                'a',
                'onclick',
                "return confirm('" . $confirm_message . "')"
            );

            $this->str .= '<li>' . gw_dict_browse_for_select($dict_item) . '</li>';
            $cnt_dict++;
        }
    }

    $this->oHtml->setTag('a', 'onclick', '');

    /* No allowed dictionaries */
    if (!$cnt_dict) {
        $this->str .= '<li>' . $this->oL->m('reason_4') . '</li>';
        $this->str .= '<li>' . $this->oL->m('reason_13') . '</li>';
    }

    $this->str .= '</ul>';
    $this->str .= '</div>';

    return;
}

$ar_query = [];
$ar_query[] = $this->oSqlQ->getQ('del-wordmap-by-dict', $dict_id);
$ar_query[] = $this->oSqlQ->getQ('del-by-dict_id', gw_get_tbl_name('map_user_to_dict'), $dict_id);
$ar_query[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_TERM, $dict_id);
$ar_query[] = $this->oSqlQ->getQ('del-by-id', gw_get_tbl_name('dict'), $dict_id);
$ar_query[] = $this->oSqlQ->getQ('del-by-id', gw_get_tbl_name('stat_dict'), $dict_id);
$ar_query[] = gw_sql_delete(
    $this->sys['tbl_prefix'] . 'history_terms',
    ['id_dict' => $dict_id]
);
$ar_query[] = gw_sql_update(
    ['id_dict' => 0],
    $this->sys['tbl_prefix'] . 'abbr',
    '`id_dict` = ' . $dict_id
);

$dict_table_name = isset($arDictParam['tablename']) ? (string) $arDictParam['tablename'] : '';
if ($dict_table_name !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $dict_table_name)) {
    $ar_query[] = $this->oSqlQ->getQ('drop-table', $dict_table_name);
}

$this->str .= gw_tmp_clear($dict_id);
$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, GW_T_DICTS),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
