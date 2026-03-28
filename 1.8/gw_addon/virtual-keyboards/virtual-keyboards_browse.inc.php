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
    die('<!-- Not in App  -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* */
$this->str .= '<table id="tbl-vkbd" class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr>';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('1289') . '</th>';
$this->str .= '<th>' . $this->oL->m('1306') . '</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('1401') . '</th>';
$this->str .= '<th style="width:15%">' . $this->oL->m('action') . '</th>';
$this->str .= '</tr></thead>';

/* The list of keyboards */
$countRow = 1;
foreach ($this->ar_profiles as $profileKey => $profile) {
    $this->oHtml->setTag('a', 'class', '');
    $profile['vkbd_letters'] = str_replace(',', ', ', $profile['vkbd_letters']);
    $bgColor = ($countRow % 2) ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];

    $hrefEdit = $this->oUrlBuilder->build_admin_url(GW_A_EDIT, $this->component, [GW_TARGET_ID => $profile['id_profile']]);
    $hrefRemove = $this->oUrlBuilder->build_admin_url(GW_A_REMOVE, $this->component, [GW_TARGET_ID => $profile['id_profile'], 'isConfirm' => 1, 'remove' => 1]);
    /* 1.8.12: on/off elements */
    $hrefTurnOn = $this->oUrlBuilder->build_admin_url(GW_A_EDIT, $this->component, [GW_TARGET_ID => $profile['id_profile'], 'mode' => 'on']);
    $hrefTurnOff = $this->oUrlBuilder->build_admin_url(GW_A_EDIT, $this->component, [GW_TARGET_ID => $profile['id_profile'], 'mode' => 'off']);
    $strStatus = $profile['is_active'] ? '' : ' <span class="badge badge-secondary"> ' . $this->oL->m('not_published') . '</span>';

    $this->str .= '<tr style="color:' . $this->ar_theme['color_5'] . ';background:' . $bgColor . '">';
    $this->str .= '<td class="xt n" style="text-align:' . $this->sys['css_align_right'] . '">' . $countRow . '</td>';
    $this->str .= '<td class="xu gray">' . $this->oHtml->a($hrefEdit, $profile['vkbd_name']) . $strStatus . '</td>';
    $this->str .= '<td class="xu gray">' . $this->oHtml->a($hrefEdit, $profile['vkbd_letters']) . '</td>';

    $this->str .= '<td class="actions-third" style="width:1%;text-align:center">';
    $this->str .= $profile['is_index_page']
        ? $this->oHtml->a($hrefTurnOff, '<span class="green">' . $this->oL->m('is_1') . '</span>')
        : $this->oHtml->a($hrefTurnOn, '<span class="red">' . $this->oL->m('is_0') . '</span>');
    $this->str .= '</td>';

    $this->str .= '<td class="actions-third" style="text-align:center">';
    $this->str .= $this->oHtml->a($hrefEdit, $this->oL->m('3_edit'));
    $this->str .= ' ';

    $this->oHtml->setTag('a', 'class', 'submitdel');
    $this->oHtml->setTag(
        'a',
        'onclick',
        'return confirm(\'' . $this->oL->m('3_remove') . ': &quot;' . $profile['vkbd_name'] . '&quot;. ' . $this->oL->m('9_remove') . '\' )'
    );

    $this->str .= $this->oHtml->a($hrefRemove, $this->oL->m('3_remove'));
    $this->oHtml->setTag('a', 'onclick', '');

    $this->str .= '</td>';
    $this->str .= '</tr>';
    $countRow++;
}
$this->str .= '</tbody></table>';
$this->str .= '<br />';