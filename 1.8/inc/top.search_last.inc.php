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
 * Build list of latest search queries.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: SEARCH_LAST
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

$arSql = $oDb->sqlExec($oSqlQ->getQ('top-search-last', $amount));

if (sizeof($arSql) == 0) {
    return '';
}

/*
Example row:
Array
(
	[id_dict] => 0
	[q] => Gigabyte
	[date_created] => 1151162264
	[cnt] => 4
)
*/

$arThText = [];
$arDs = [];

/* Set column widths */
if ($isItemOnly) {
    $arThWidth = ['99%'];
} else {
    $arThWidth = ['', '30%'];
}

$arThText = [$oL->m('srch_3'), $oL->m('dict')];

/* Set column alignment */
$arThAlign = [$sys['css_align_left'], $sys['css_align_right']];

/* Render each search row */
foreach ($arSql as $arV) {
    if (($cnt % 2) == 1) {
        $bg_color = $ar_theme['color_2'];
    } else {
        $bg_color = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr class="gray" style="background:' . $bg_color . '">';
    $strData .= '<td class="xq">' . $cnt . '</td>';

    $url = $oHtml->a(
        $sys['page_index']
        . '?' . GW_ACTION . '=' . GW_A_SEARCH
        . '&d=' . $arV['id_dict']
        . '&srch[adv]=all&srch[by]=d&srch[in]=-1&q=' . urlencode($arV['q']),
        $arV['q']
    );

    $strData .= '<td class="termpreview">' . $url . '</td>';

    $arDs = [];

    if ($arV['id_dict']) {
        $arDs = getDictParam($arV['id_dict']);

        switch ($sys['pages_link_mode']) {
            case GW_PAGE_LINK_NAME:
                $arV['id_dict'] = urlencode($arDs['title']);
                break;

            case GW_PAGE_LINK_URI:
                $arV['id_dict'] = urlencode($arDs['dict_uri']);
                break;

            default:
                break;
        }
    }

    $strData .= '<td class="xt">';
    $strData .= $arV['id_dict']
        ? $oHtml->a(
            $sys['page_index'] . '?' . GW_ACTION . '=list&p=1&d=' . $arV['id_dict'],
            $arDs['title']
        )
        : $oL->m('1113');
    $strData .= '</td>';

    $strData .= '</tr>';
}

unset($arDs);
