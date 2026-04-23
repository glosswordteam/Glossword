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
 * Build list of recently updated terms.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: R_TERM_UPDATED
 *
 * Expected parent-scope variables:
 * @var int $amount
 * @var array $arThText
 * @var array $arThAlign
 * @var array $arThWidth
 * @var string $strTopicName
 * @var string $strData
 * @var int $curDateMk
 * @var int $cnt
 */

/*
1. Sort dictionaries by modification date.
2. Collect terms until the number of terms reaches $amount.
*/
$ar_dicts = [];

foreach ($gw_this['ar_dict_list'] as $ar_ds) {
    $ar_dicts[] = $ar_ds;
}

usort($ar_dicts, 'gw_cmp_dicts_by_date_modified_desc');

function gw_cmp_dicts_by_date_modified_desc($a, $b)
{
    if ($a['date_modified'] == $b['date_modified']) {
        return 0;
    }

    return ($a['date_modified'] < $b['date_modified']) ? 1 : -1;
}

$cnt_terms = 0;
$ar_terms = [];

foreach ($ar_dicts as $ar_ds) {
    $sql = $oSqlQ->getQ('top-term-new', $ar_ds['tablename'], $sys['time_now_db'], 'date_modified DESC', $amount);
    $arSql = $oDb->sqlExec($sql);

    foreach ($arSql as $arK => $arV) {
        $arV['title'] = $ar_ds['title'];
        $arV['id_dict'] = $ar_ds['id'];

        if (GW_IS_BROWSE_ADMIN) {
            $arV['is_term_edit'] = $oSess->is('is-terms', $ar_ds['id']);
        } else {
            switch ($sys['pages_link_mode']) {
                case GW_PAGE_LINK_NAME:
                    $arV['id_dict'] = urlencode($ar_ds['title']);
                    $arV['id'] = urlencode($arV['term']);
                    break;

                case GW_PAGE_LINK_URI:
                    $arV['id_dict'] = urlencode($ar_ds['dict_uri']);
                    $arV['id'] = ($arV['term_uri'] == '') ? urlencode($arV['term']) : urlencode($arV['term_uri']);
                    break;

                default:
                    break;
            }
        }

        /* '-$arK' is a trick to keep terms with the same date */
        $ar_terms[$arV['date_modified'] - $arK] = $arV;
        $cnt_terms++;
    }

    if ($cnt_terms >= $amount) {
        break;
    }
}

$arThText = [];

if (GW_IS_BROWSE_ADMIN) {
    $arThWidth = ['5%', '50%', '54%'];
    $arThAlign = ['center', $sys['css_align_left'], $sys['css_align_left']];
} else {
    $arThWidth = ['50%', '49%'];
    $arThAlign = [$sys['css_align_left'], $sys['css_align_left']];
}

$strTopicName = '';

if (GW_IS_BROWSE_ADMIN) {
    /* List of dictionaries available to the current user */
    $ar_allowed_dicts = $oSess->user_get('dictionaries');
}

/* Render each term row */
foreach ($ar_terms as $arV) {
    if ($cnt == $amount) {
        break;
    }

    if (($cnt % 2) == 1) {
        $bg_color = $ar_theme['color_2'];
    } else {
        $bg_color = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr class="gray" style="background:' . $bg_color . '">';
    $strData .= '<td class="n xt">' . $cnt . '</td>';

    if (GW_IS_BROWSE_ADMIN) {
        $str_edit = '<del>' . $oL->m('3_edit') . '</del>';
        $url_term = strip_tags($arV['term']);
        $href_edit = $sys['page_admin']
            . '?' . GW_ACTION . '=' . GW_A_EDIT
            . '&' . GW_TARGET . '=' . GW_T_TERMS
            . '&id=' . $arV['id_dict']
            . '&tid=' . $arV['id'];

        /* Check permissions */
        if (
            $oSess->is('is-terms')
                ? 1
                : (
                ($arV['id_user'] == $oSess->id_guest)
                || ($oSess->is('is-terms-own') && ($arV['id_user'] == $oSess->id_user))
            )
                ? 1 : 0
        ) {
            $str_edit = $oHtml->a($href_edit, $oL->m('3_edit'), $oL->m('terms') . ': ' . $oL->m('3_edit'));
            $url_term = $oHtml->a($href_edit, strip_tags($arV['term']));
        }

        $strData .= '<td class="actions-third"><span>' . $str_edit . '</span>&#160;</td>';
        $url_dict = $arV['title'];
    } else {
        $url_term = $oHtml->a(
            append_url(
                $sys['page_index']
                . '?' . GW_ACTION . '=' . GW_T_TERM
                . '&d=' . $arV['id_dict']
                . '&' . GW_TARGET . '=' . $arV['id']
            ),
            strip_tags($arV['term']),
            ''
        );

        $url_dict = $oHtml->a(
            append_url(
                $sys['page_index']
                . '?' . GW_ACTION . '=index&d=' . $arV['id_dict']
            ),
            strip_tags($arV['title']),
            ''
        );
    }

    $strData .= '<td class="termpreview">' . $url_term . '</td>';
    $strData .= '<td class="xt">' . $url_dict . '</td>';
    $strData .= '</tr>';
}
?>