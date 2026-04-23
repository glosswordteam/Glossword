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
 * Build list of newest dictionaries.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: R_DICT_NEWEST
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

$arSql = $oDb->sqlExec($oSqlQ->getQ('top-dict-new', $sys['time_now_db'], $amount));

/* Table headers */
$arThText = [$oL->m('th_1'), $oL->m('th_5')];

/* Column widths */
$arThWidth = ['', '30%'];

/* Column alignment */
$arThAlign = [$sys['css_align_left'], $sys['css_align_right']];

/* Render each dictionary row */
foreach ($arSql as $arV) {
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

    if (($cnt % 2) == 1) {
        $bgColor = $ar_theme['color_2'];
    } else {
        $bgColor = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr class="gray xt" style="background:' . $bgColor . '">';
    $strData .= '<td>' . $cnt . '</td>';
    $strData .= '<td>' . $oHtml->a($sys['page_index'] . '?a=list&p=1&d=' . $arV['id'], $arV['title']) . '</td>';
    $strData .= '<td>';
    $strData .= (date_extract_int($arV['date_created'], '%d') / 1) . date_extract_int($arV['date_created'], ' %F %Y');
    $strData .= '</td>';
    $strData .= '</tr>';
}

/**
 * End of R_DICT_NEWEST
 */
