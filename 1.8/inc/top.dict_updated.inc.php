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
 * Build list of recently updated dictionaries.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: DICT_UPDATED
 *
 * Expected parent-scope variables:
 * @var int $amount
 * @var int $isItemOnly
 * @var array $arThText
 * @var array $arThAlign
 * @var array $arThWidth
 * @var string $strTopicName
 * @var string $strData
 * @var int $curDateMk
 * @var int $cnt
 */

if ($isItemOnly) {
    $arThText = [];
    $arThWidth = ['99%'];
    $arThAlign = [$sys['css_align_left']];
} else {
    $arThText = [$oL->m('th_1'), $oL->m('date_modif')];
    $arThWidth = ['', '30%'];
    $arThAlign = [$sys['css_align_left'], $sys['css_align_right']];

    if (GW_IS_BROWSE_ADMIN) {
        $arThText = [$oL->m('action'), $oL->m('th_1'), $oL->m('date_modif')];
    }
}

if (GW_IS_BROWSE_ADMIN) {
    /* List of dictionaries available to the current user */
    $arAllowedDicts = $oSess->user_get('dictionaries');

    $arSql = $oDb->sqlExec(sprintf($oSqlQ->getQ('top-dict-updated-adm', $amount)));
    $arThWidth = ['15%', '55%', '29%'];
    $arThAlign = ['center', $sys['css_align_left'], $sys['css_align_right']];
} else {
    $arSql = $oDb->sqlRun(sprintf($oSqlQ->getQ('top-dict-updated', $sys['time_now_db'], $amount)), 'st');
}

$strTopicName = '';

/* Render each dictionary row */
foreach ($arSql as $arV) {
    if (($cnt % 2) == 1) {
        $bgColor = $ar_theme['color_2'];
    } else {
        $bgColor = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr style="background:' . $bgColor . '">';
    $strData .= '<td class="n xt">' . $cnt . '</td>';

    if (GW_IS_BROWSE_ADMIN) {
        if (
            $oSess->is('is-sys-settings')
            || $oSess->is('is-dicts')
            || (isset($arAllowedDicts[$arV['id']]) && $oSess->is('is-dicts-own'))
        ) {
            $strData .= '<td class="actions-third"><span>';
            $strData .= $oHtml->a(
                $sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_DICTS . '&id=' . $arV['id'] . '&tid=' . $arV['id'],
                $oL->m('3_edit'),
                $oL->m('1335') . ': ' . $oL->m('3_edit')
            );
            $strData .= ' ';
            $strData .= $oHtml->a(
                $sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_ADD . '&' . GW_TARGET . '=' . GW_T_TERMS . '&id=' . $arV['id'] . '&tid=' . $arV['id'],
                $oL->m('3_add_term'),
                $oL->m('terms') . ': ' . $oL->m('3_add')
            );
            $strData .= '&#160;</span></td>';
            $strData .= '<td class="termpreview">' . $oHtml->a(
                    $sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . GW_T_DICTS . '&id=' . $arV['id'] . '&tid=' . $arV['id'],
                    $arV['title']
                ) . '</td>';
        } else {
            $strData .= '<td class="actions-third"><span><del>' . $oL->m('3_edit') . '</del> <del>' . $oL->m(
                    '3_add_term'
                ) . '</del>&#160;</span></td>';
            $strData .= '<td class="termpreview">' . $arV['title'] . '&#160;</td>';
        }
    } else {
        switch ($sys['pages_link_mode']) {
            case GW_PAGE_LINK_NAME:
                $arV['id'] = urlencode($arV['title']);
                break;

            case GW_PAGE_LINK_URI:
                $arV['id'] = urlencode($arV['dict_uri']);
                break;

            default:
                break;
        }

        $strData .= '<td class="xt">' . $oHtml->a(
                $sys['page_index'] . '?' . GW_ACTION . '=list&p=1&d=' . $arV['id'],
                $arV['title']
            ) . '</td>';
    }

    if (!$isItemOnly || GW_IS_BROWSE_ADMIN) {
        $strData .= '<td class="xt gray">';
        $strData .= (date_extract_int($arV['date_modified'], '%d') / 1) . date_extract_int(
                $arV['date_modified'],
                ' %F %Y'
            );
        $strData .= '</td>';
    }

    $strData .= '</tr>';
}

