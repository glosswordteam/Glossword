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

    $this->str .= '<tr style="color:' . $this->ar_theme['color_5'] . ';background:' . $bgColor . '">';
    $this->str .= '<td class="xt n" style="text-align:' . $this->sys['css_align_right'] . '">' . $countRow . '</td>';
    $this->str .= '<td class="xu gray">' . $this->oHtml->a(
            $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $profile['id_profile'],
            $profile['vkbd_name']
        ) . '</td>';
    $this->str .= '<td class="xu gray">' . $this->oHtml->a(
            $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $profile['id_profile'],
            $profile['vkbd_letters']
        ) . '</td>';

    /* 1.8.12: Default for the index page on/off */
    $hrefOnOff = $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET] . '&tid=' . $profile['id_profile'];

    $this->str .= '<td class="actions-third" style="width:1%;text-align:center">';
    $this->str .= $profile['is_index_page']
        ? $this->oHtml->a($hrefOnOff . '&mode=off', '<span class="green">' . $this->oL->m('is_1') . '</span>')
        : $this->oHtml->a($hrefOnOff . '&mode=on', '<span class="red">' . $this->oL->m('is_0') . '</span>', $this->oL->m('1057'));
    $this->str .= '</td>';

    $this->str .= '<td class="actions-third" style="text-align:center">';
    $this->str .= $this->oHtml->a(
        $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_EDIT . '&' . GW_TARGET . '=' . $this->component . '&tid=' . $profile['id_profile'],
        $this->oL->m('3_edit')
    );
    $this->str .= ' ';

    $this->oHtml->setTag('a', 'class', 'submitdel');
    $this->oHtml->setTag(
        'a',
        'onclick',
        'return confirm(\'' . $this->oL->m('3_remove') . ': &quot;' . $profile['vkbd_name'] . '&quot;. ' . $this->oL->m('9_remove') . '\' )'
    );
    $this->str .= $this->oHtml->a(
        $this->sys['page_admin'] . '?' . GW_ACTION . '=' . GW_A_REMOVE . '&' . GW_TARGET . '=' . $this->component . '&isConfirm=1&remove=1&tid=' . $profile['id_profile'],
        $this->oL->m('3_remove')
    );
    $this->oHtml->setTag('a', 'onclick', '');

    $this->str .= '</td>';
    $this->str .= '</tr>';
    $countRow++;
}
$this->str .= '</tbody></table>';
$this->str .= '<br />';