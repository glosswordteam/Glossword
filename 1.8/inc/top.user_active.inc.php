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
 * Build list of most active users by terms count.
 *
 * This fragment is included from gw_get_top10().
 * Switch keyname: R_USER_ACTIVE
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

$arSql = $oDb->sqlRun(sprintf($oSqlQ->getQ('top-user-terms', $amount)), 'st');

$arThText = [$oL->m('contact_name'), $oL->m('termsamount')];

/* Set column widths */
$arThWidth = ['70%', '29%'];

/* Set column alignment */
$arThAlign = [$sys['css_align_left'], $sys['css_align_right']];

/* Render each user row */
foreach ($arSql as $arV) {
    if (($cnt % 2) == 1) {
        $bg_color = $ar_theme['color_2'];
    } else {
        $bg_color = $ar_theme['color_1'];
    }

    $cnt++;

    $strData .= '<tr class="xt gray" style="background:' . $bg_color . '">';
    $strData .= '<td>' . $cnt . '</td>';
    $strData .= '<td>' . $oHtml->a(
            $sys['page_index'] . '?' . GW_ACTION . '=' . GW_A_PROFILE . '&t=view&id=' . $arV['id_user'],
            $arV['user_name']
        ) . '</td>';
    $strData .= '<td>';
    $strData .= $oFunc->number_format($arV['int_items'], 0, $oL->languagelist(LOCALE_LANG_RULES));
    $strData .= '</td>';
    $strData .= '</tr>';
}
