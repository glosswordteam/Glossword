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

$ar_pre = isset($this->gw_this['vars']['arPre']) ? $this->gw_this['vars']['arPre'] : null;

$ar_parsed = [];
$ar_parsed['ar'] = &$this->ar;
$ar_req_fields = [];

if ($this->gw_this['vars']['post'] == '') {
    /* Set default values */
    $cnt = 0;

    foreach ($this->gw_this['vars']['ar_languages'] as $id_lang => $ar_v) {
        $ar_parsed['page'][$cnt] = [
            'id_page_phrase' => 0,
            'page_title'     => '',
            'page_descr'     => '',
            'page_content'   => '',
            'page_keywords'  => '',
            'id_lang'        => $id_lang,
        ];
        $cnt++;
    }

    $ar_parsed['is_active'] = 1;
    $ar_parsed['id_parent'] = 0;
    $ar_parsed['page_icon'] = '';
    $ar_parsed['page_uri'] = 'page-' . (int)time();
    $ar_parsed['page_php_1'] = '';
    $ar_parsed['page_php_2'] = '';

    $is_first = 1;

    /* Additional actions */
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
    if (!is_array($ar_pre)) {
        $ar_pre = [];
    }

    $ar_queries = [];
    $table_pages = gw_get_tbl_name('pages');
    $table_pages_phrase = gw_get_tbl_name('pages_phrase');

    /* Fix on/off options */
    $ar_pre['is_active'] = isset($ar_pre['is_active']) ? (int)$ar_pre['is_active'] : 0;
    $ar_pre['id_parent'] = 0;

    $id_page = $this->oDb->NextId($table_pages, 'id_page');
    $id_page_phrase_base = $this->oDb->NextId($table_pages_phrase, 'id_page_phrase');

    $page_uri = isset($ar_pre['page_uri']) ? trim((string)$ar_pre['page_uri']) : '';
    if ($page_uri === '') {
        $page_uri = 'page-' . $this->sys['time_now_gmt_unix'];
    }

    $q_page = [
        'id_page'       => $id_page,
        'id_parent'     => $ar_pre['id_parent'],
        'is_active'     => ($ar_pre['is_active'] ? 1 : 0),
        'page_icon'     => isset($ar_pre['page_icon']) ? (string)$ar_pre['page_icon'] : '',
        'page_uri'      => $page_uri,
        'id_user'       => $this->oSess->id_user,
        'page_php_1'    => isset($ar_pre['page_php_1']) ? (string)$ar_pre['page_php_1'] : '',
        'page_php_2'    => isset($ar_pre['page_php_2']) ? (string)$ar_pre['page_php_2'] : '',
        'date_modified' => $this->sys['time_now_gmt_unix'],
        'date_created'  => $this->sys['time_now_gmt_unix'],
    ];

    if (isset($ar_pre['page']) && is_array($ar_pre['page'])) {
        foreach ($ar_pre['page'] as $page_offset => $ar_v) {
            $id_page_phrase = 0;
            if (isset($ar_v['id_page_phrase']) && (int)$ar_v['id_page_phrase'] > 0) {
                $id_page_phrase = (int)$ar_v['id_page_phrase'];
            } else {
                $id_page_phrase = $id_page_phrase_base + (int)$page_offset;
            }

            $q_page_phrase = [
                'id_page_phrase' => $id_page_phrase,
                'id_page'        => $id_page,
                'page_title'     => isset($ar_v['page_title']) ? $ar_v['page_title'] : '',
                'page_descr'     => isset($ar_v['page_descr']) ? $ar_v['page_descr'] : '',
                'page_content'   => isset($ar_v['page_content']) ? $ar_v['page_content'] : '',
                'page_keywords'  => isset($ar_v['page_keywords']) ? $ar_v['page_keywords'] : '',
                'id_lang'        => isset($ar_v['id_lang']) ? $ar_v['id_lang'] : '',
            ];

            $ar_queries[] = gw_sql_insert(
                $q_page_phrase,
                $table_pages_phrase,
                'id_page_phrase = ' . $id_page_phrase
            );
        }
    }

    $sql = gw_sql_insert($q_page, $table_pages, 'id_page = ' . $id_page);

    if ($this->sys['isDebugQ']) {
        $ar_queries[] = $sql;
    } else {
        /* Insert main page now */
        $this->oDb->sqlExec($sql);
    }

    $redirect_url = $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, GW_T_CUSTOMPAGE);

    $this->str .= postQuery(
        $ar_queries,
        $redirect_url,
        $this->sys['isDebugQ'],
        $this->sys['isPause']
    );
}

