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

if ($this->gw_this['vars']['post'] == '') {
    $arV = [];
    $arV['file_location'] = '';
    $arV['is_merge'] = 1;
    $arV['is_overwrite'] = 0;
    $arV['xml'] = '';

    /* Not submitted. */
    $this->str .= $this->get_form_import($arV);

    $strHelp = '';
    $strHelp .= '<dl>';
    $strHelp .= '<dt><strong>XML</strong></dt>';
    $strHelp .= '<dd>' . CRLF . '&lt;' . '?xml version="1.0" encoding="UTF-8"' . '?&gt;'
        . '<br />&lt;glossword&gt;'
        . '<br />&lt;custom_page id="1"&gt;'
        . '<br />&#160;&lt;parameters&gt;&#8230;&lt;/parameters&gt;'
        . '<br />&#160;&lt;page_php_1&gt;&#8230;&lt;/page_php_1&gt;'
        . '<br />&#160;&lt;page_php_2&gt;&#8230;&lt;/page_php_2&gt;'
        . '<br />&#160;&lt;entry&gt;'
        . '<br />&#160;&#160;&lt;lang xml:lang="en"&gt;'
        . '<br />&#160;&#160;&#160;&lt;page_title&gt;&#8230;&lt;/page_title&gt;'
        . '<br />&#160;&#160;&#160;&lt;page_descr&gt;&#8230;&lt;/page_descr&gt;'
        . '<br />&#160;&#160;&#160;&lt;page_content&gt;&#8230;&lt;/page_content&gt;'
        . '<br />&#160;&#160;&#160;&lt;page_keywords&gt;&#8230;&lt;/page_keywords&gt;'
        . '<br />&#160;&#160;&#160;&lt;id_page_phrase&gt;&#8230;&lt;/id_page_phrase&gt;'
        . '<br />&#160;&lt;/lang&gt;'
        . '<br />&#160;&lt;/entry&gt;'
        . '<br />&lt;/custom_page&gt;'
        . '<br />&lt;/glossword&gt;'
        . '</dd>';
    $strHelp .= '</dl>';
    $this->str .= '<br />' . kTbHelp($this->oL->m('2_tip'), $strHelp);
} else {
    $file_location = ['name' => ''];

    if (isset($this->gw_this['vars']['_files']['file_location'])) {
        $file_location = $this->gw_this['vars']['_files']['file_location'];
    }

    $arPost =& $this->gw_this['vars']['arPost'];
    $xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
    $file_target = urlencode(time() . '_' . $file_location['name']);

    /* Create a temporary file before moving upload. */
    $this->oFunc->file_put_contents($this->sys['path_temporary'] . '/t/' . $file_target, '');

    if (is_uploaded_file($xml_file)
        && move_uploaded_file($xml_file, $this->sys['path_temporary'] . '/t/' . $file_target)
    ) {
        $arPost['xml'] = $this->oFunc->file_get_contents($this->sys['path_temporary'] . '/t/' . $file_target);
        unlink($this->sys['path_temporary'] . '/t/' . $file_target);
    }

    /* Do import using DOM model. */
    $oDom = new gw_domxml;
    $oDom->is_skip_white = 0;
    $oDom->strData =& $arPost['xml'];
    $oDom->parse();
    $oDom->strData = '';

    $ar_xml_line = $oDom->get_elements_by_tagname('custom_page');
    $ar_queries = [];
    $cnt_pages = 0;
    $tbl_pages = gw_get_tbl_name('pages');
    $tbl_pages_phrase = gw_get_tbl_name('pages_phrase');

    $ar_allowed_page_fields = [
        'id_page' => 1,
        'id_user' => 1,
        'is_active' => 1,
        'int_sort' => 1,
        'date_created' => 1,
        'date_modified' => 1,
        'page_icon' => 1,
        'page_uri' => 1,
        'page_php_1' => 1,
        'page_php_2' => 1,
    ];
    $ar_allowed_phrase_fields = [
        'id_page_phrase' => 1,
        'id_page' => 1,
        'id_lang' => 1,
        'page_title' => 1,
        'page_keywords' => 1,
        'page_descr' => 1,
        'page_content' => 1,
    ];

    if ($arPost['is_overwrite']) {
        $ar_queries[] = 'TRUNCATE TABLE `' . $tbl_pages . '`';
        $ar_queries[] = 'TRUNCATE TABLE `' . $tbl_pages_phrase . '`';
    }

    $this->str .= '<ul class="xt">';

    foreach ($ar_xml_line as $xml_page) {
        if (!is_array($xml_page) || !isset($xml_page['children'])) {
            continue;
        }

        $page_id = (int) $oDom->get_attribute('id', 'custom_page', $xml_page);

        if ($page_id < 1) {
            continue;
        }

        $q_page = [
            'id_page' => $page_id,
            'id_parent' => 0,
        ];

        foreach ($xml_page['children'] as $xml_page_node) {
            if (!is_array($xml_page_node) || !isset($xml_page_node['tag'])) {
                continue;
            }

            $page_node_tag = trim($xml_page_node['tag']);

            if ($page_node_tag == '') {
                continue;
            }

            switch ($page_node_tag) {
                case 'parameters':
                    $serialized_params = $oDom->get_content($xml_page_node);
                    $ar_params = [];

                    /* Import only serialized arrays and ignore legacy nested parent links. */
                    if (!preg_match('/(^|[;{])(?:O|C):\d+:/', $serialized_params)) {
                        $ar_params = @unserialize($serialized_params);
                    }

                    if (!is_array($ar_params)) {
                        $ar_params = [];
                    }

                    foreach ($ar_params as $param_name => $param_value) {
                        if ($param_name == 'id_parent') {
                            continue;
                        }

                        if (isset($ar_allowed_page_fields[$param_name])) {
                            $q_page[$param_name] = $param_value;
                        }
                    }

                    $q_page['id_page'] = $page_id;
                    $q_page['id_parent'] = 0;
                    break;

                case 'entry':
                    if (!isset($xml_page_node['children']) || !is_array($xml_page_node['children'])) {
                        break;
                    }

                    foreach ($xml_page_node['children'] as $xml_lang_node) {
                        if (!is_array($xml_lang_node)
                            || !isset($xml_lang_node['tag'])
                            || $xml_lang_node['tag'] != 'lang'
                            || !isset($xml_lang_node['children'])
                            || !is_array($xml_lang_node['children'])
                        ) {
                            continue;
                        }

                        $id_lang = $oDom->get_attribute('xml:lang', 'lang', $xml_lang_node);

                        if ($id_lang == '') {
                            continue;
                        }

                        $q_phrase = [
                            'id_page' => $page_id,
                            'id_lang' => $id_lang . '-' . $this->gw_this['vars']['lang_enc'],
                        ];

                        foreach ($xml_lang_node['children'] as $xml_phrase_node) {
                            if (!is_array($xml_phrase_node) || !isset($xml_phrase_node['tag'])) {
                                continue;
                            }

                            $phrase_node_tag = trim($xml_phrase_node['tag']);

                            if ($phrase_node_tag == '' || !isset($ar_allowed_phrase_fields[$phrase_node_tag])) {
                                continue;
                            }

                            $q_phrase[$phrase_node_tag] = isset($xml_phrase_node['value'])
                                ? $xml_phrase_node['value']
                                : $oDom->get_content($xml_phrase_node);
                        }

                        $ar_queries[] = gw_sql_replace($q_phrase, $tbl_pages_phrase);
                    }
                    break;

                case 'page_php_1':
                case 'page_php_2':
                    $q_page[$page_node_tag] = isset($xml_page_node['value'])
                        ? $xml_page_node['value']
                        : $oDom->get_content($xml_page_node);
                    break;
            }
        }

        if (!isset($q_page['date_created'])) {
            $q_page['date_created'] = $this->sys['time_now_gmt_unix'];
        }

        if (!isset($q_page['date_modified'])) {
            $q_page['date_modified'] = $this->sys['time_now_gmt_unix'];
        }

        /* Old export files did not have User ID. */
        if (!isset($q_page['id_user'])) {
            $q_page['id_user'] = $this->oSess->id_user;
        }

        /* Custom pages are flat now. */
        $q_page['id_parent'] = 0;

        $ar_queries[] = gw_sql_replace($q_page, $tbl_pages);
        $cnt_pages++;
    }

    $this->str .= '</ul>';

    if (!$cnt_pages) {
        $arPost['is_merge'] = 1;

        if ($arPost['is_overwrite']) {
            $arPost['is_merge'] = 0;
        }

        $arPost['file_location'] = '';
        $this->str .= $this->get_form_import($arPost);

        return;
    }

    $this->str .= postQuery(
        $ar_queries,
        $this->oUrlBuilder->build_admin_url(
            GW_A_BROWSE,
            $this->component,
            [
                'note_afterpost' => $this->oL->m('custom_pages') . ': ' . $cnt_pages,
            ]
        ),
        $this->sys['isDebugQ'],
        $this->sys['isPause']
    );
}
