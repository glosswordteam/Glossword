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
 * Build list of recently added terms.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: R_TERM_NEWEST
 *
 * Expected parent-scope variables:
 * @var int $amount
 * @var int $order
 * @var int $isItemOnly
 * @var array $arThText
 * @var array $arThAlign
 * @var array $arThWidth
 * @var array $ar_top10_list
 * @var string $strTopicName
 * @var string $strData
 * @var int $curDateMk
 * @var int $cnt
 */

$arSorting = [];
$arSorting[0] = 'date_modified DESC';
$arSorting[1] = 'date_modified DESC';

$arSql = $oDb->sqlExec(
    sprintf(
        $oSqlQ->getQ('top-term-new', $arDictParam['tablename'], $sys['time_now_db'], $arSorting[$order], $amount)
    )
);

$arThText = [];

/* Set column widths */
if ($isItemOnly) {
    $arThWidth = ['99%'];
} else {
    $arThWidth = ['', '29%'];
}

/* Sort the list by term name */
if ($order == 2) {
    global $oCase;

    $arSqlSorted = [];

    foreach ($arSql as $arV) {
        $arSqlSorted[urlencode($arV['term'])] = $arV;
    }

    ksort($arSqlSorted);
    $arSql = $arSqlSorted;
}

/* Set column alignment */
$arThAlign = [$sys['css_align_left'], $sys['css_align_right']];
$strTopicName = $oL->m('recent');

/* Render each term row */
foreach ($arSql as $arV) {
    if (($cnt % 2) == 1) {
        $bgColor = $ar_theme['color_2'];
    } else {
        $bgColor = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr style="background:' . $bgColor . '">';
    $strData .= '<td class="xq">' . $cnt . '</td>';

    $oHtml->setTag(
        'a',
        'title',
        (date_extract_int($arV['date_modified'], '%d') / 1)
        . date_extract_int($arV['date_modified'], '&#160;%F&#160;%Y')
    );

    switch ($sys['pages_link_mode']) {
        case GW_PAGE_LINK_NAME:
            $url = $oHtml->a(
                append_url(
                    $sys['page_index']
                    . '?' . GW_ACTION . '=' . GW_T_TERM
                    . '&d=' . $arDictParam['uri']
                    . '&' . GW_TARGET . '=' . urlencode($arV['term'])
                ),
                strip_tags($arV['term']),
                ''
            );
            break;

        case GW_PAGE_LINK_URI:
            $arV['term_uri'] = ($arV['term_uri'] == '') ? $arV['term'] : $arV['term_uri'];

            $url = $oHtml->a(
                append_url(
                    $sys['page_index']
                    . '?' . GW_ACTION . '=' . GW_T_TERM
                    . '&d=' . $arDictParam['uri']
                    . '&' . GW_TARGET . '=' . urlencode($arV['term_uri'])
                ),
                strip_tags($arV['term']),
                ''
            );
            break;

        default:
            $url = $oHtml->a(
                append_url(
                    $sys['page_index']
                    . '?' . GW_ACTION . '=' . GW_T_TERM
                    . '&d=' . $arDictParam['uri']
                    . '&' . GW_TARGET . '=' . $arV['id']
                ),
                strip_tags($arV['term']),
                ''
            );
            break;
    }

    $ar_top10_list[] = $url;

    $strData .= '<td class="termpreview">' . $url . '</td>';

    if (!$isItemOnly) {
        $strData .= '<td class="xq" style="white-space:nowrap">';
        $strData .= (date_extract_int($arV['date_modified'], '%d') / 1)
            . date_extract_int($arV['date_modified'], '&#160;%F&#160;%Y');
        $strData .= '</td>';
    }

    $strData .= '</tr>';
}

$oHtml->setTag('a', 'title', '');