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

$tid                = isset($this->gw_this['vars'][GW_TARGET_ID]) ? (int)$this->gw_this['vars'][GW_TARGET_ID] : 0;
$selected_letter_id = isset($this->gw_this['vars']['w1']) ? (int)$this->gw_this['vars']['w1'] : 0;
$page_admin_url     = $this->sys['page_admin'];
$html_charset       = 'UTF-8';

$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:' . $this->left_td_width . ';background:' . $this->ar_theme['color_2'] . ';vertical-align:top">';

$this->str .= '<h3>' . $this->oL->m('2_page_custom_az_browse') . '</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';

if ($tid > 0) {
    /* Display actions */
    $this->str .= $this->_get_nav();

    /* Start form object */
    $o_form = new gwForms();
    $o_form->Set('action', $page_admin_url);
    $o_form->Set('formwidth', '100%');

    $this->str .= '<form accept-charset="' . $html_charset . '"'
        . ' action="' . $page_admin_url . '"'
        . ' enctype="application/x-www-form-urlencoded"'
        . ' id="form-custom-az"'
        . ' method="get">';

    $this->str .= '<table id="tbl-custom_az" class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
    $this->str .= '<thead><tr>';
    $this->str .= '<th style="width:1%">N</th>';
    $this->str .= '<th style="width:20%"></th>';
    $this->str .= '<th style="width:10%">' . $this->oCase->uc($this->oL->m('1292')) . '</th>';
    $this->str .= '<th style="width:10%">' . $this->oCase->lc($this->oL->m('1292')) . '</th>';
    $this->str .= '<th>' . $this->oL->m('action') . '</th>';
    $this->str .= '</tr></thead>';

    /* Allow adding a letter */
    if ($tid > 1) {
        $o_form->setTag('input', 'style', 'width:2em;font-size:200%');
        $o_form->setTag('input', 'maxlength', '3');

        $this->str .= '<tfoot><tr style="color:' . $this->ar_theme['color_5'] . '">';
        $this->str .= '<td></td><td></td>';
        $this->str .= '<td style="text-align:' . $this->sys['css_align_right'] . '">'
            . $o_form->field('input', 'arPost[az_value][]', '')
            . '</td>';
        $this->str .= '<td style="text-align:' . $this->sys['css_align_left'] . '">'
            . $o_form->field('input', 'arPost[az_value_lc][]', '')
            . '</td>';
        $this->str .= '<td class="xt" style="text-align:' . $this->sys['css_align_left'] . '">';
        $this->str .= '<input name="post" class="submitok" type="submit" value="' . $this->oL->m('3_save') . '" />';
        $this->str .= '</td>';
        $this->str .= '</tr></tfoot>';
    } else {
        // The default sorting order
        $this->str .= '<tfoot class="xt"><tr>';
        $this->str .= '<td></td><td colspan="4" class="center" style="padding:1em">' . $this->oL->m('1293') . '</td>';
        $this->str .= '</tr></tfoot>';
    }

    $this->str .= '<tbody>';

    /* Load letters */
    $ar_sql = $this->oDb->sqlExec(
        $this->oSqlQ->getQ('get-custom_az-adm', $tid),
        $this->component
    );
    $ar_sql = is_array($ar_sql) ? $ar_sql : [];

    $max_sort   = count($ar_sql);
    $row_number = 1;

    foreach ($ar_sql as $row_index => $ar_v) {
        $is_up     = 1;
        $is_down   = 1;
        $id_letter = isset($ar_v['id_letter']) ? (int)$ar_v['id_letter'] : 0;
        $bgcolor   = ($row_number % 2) ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];

        if ($row_index === 0) {
            $is_up = 0;
        }

        if ($row_number === $max_sort) {
            $is_down = 0;
        }

        if ($id_letter === $selected_letter_id) {
            $o_form->setTag('input', 'style', 'border:2px solid #777;width:2em;font-size:200%');
        }

        $az_value         = isset($ar_v['az_value']) ? urldecode($ar_v['az_value']) : '';
        $az_value_lc      = isset($ar_v['az_value_lc']) ? urldecode($ar_v['az_value_lc']) : '';
        $display_value    = htmlspecialchars($az_value, ENT_QUOTES, $html_charset);
        $display_value_lc = htmlspecialchars($az_value_lc, ENT_QUOTES, $html_charset);

        $up_url = $this->oUrlBuilder->build_admin_url(
            GW_A_EDIT,
            $this->component,
            [
                'mode'       => 'up',
                GW_TARGET_ID => $tid,
                'w1'         => $id_letter,
            ]
        );

        $down_url = $this->oUrlBuilder->build_admin_url(
            GW_A_EDIT,
            $this->component,
            [
                'mode'       => 'down',
                GW_TARGET_ID => $tid,
                'w1'         => $id_letter,
            ]
        );

        $remove_url = $this->oUrlBuilder->build_admin_url(
            GW_A_EDIT,
            $this->component,
            [
                'mode'       => 'remove',
                GW_TARGET_ID => $tid,
                'w1'         => $id_letter,
            ]
        );

        $confirm_label = trim(strip_tags($az_value . ' ' . $az_value_lc));
        $confirm_label = str_replace(["\r", "\n", "\t"], ' ', $confirm_label);
        $confirm_text  = $this->oL->m('3_remove') . ': "' . $confirm_label . '". ' . $this->oL->m('9_remove');
        $confirm_text  = str_replace(['\\', '\''], ['\\\\', '\\\''], $confirm_text);

        $this->str .= '<tr id="az-' . $id_letter . '" style="background:' . $bgcolor . '">';
        $this->str .= '<td class="xt n" style="text-align:' . $this->sys['css_align_right'] . '">' . $row_number . '</td>';
        $this->str .= '<td class="gray center xw">' . $display_value . ' ' . $display_value_lc . '</td>';
        $this->str .= '<td style="text-align:' . $this->sys['css_align_right'] . '">'
            . $o_form->field('input', 'arPost[az_value][' . $id_letter . ']', $az_value)
            . '</td>';
        $this->str .= '<td style="text-align:' . $this->sys['css_align_left'] . '">'
            . $o_form->field('input', 'arPost[az_value_lc][' . $id_letter . ']', $az_value_lc)
            . '</td>';
        $this->str .= '<td class="actions-third" style="text-align:' . $this->sys['css_align_left'] . '">';

        if ($is_up) {
            $this->str .= $this->oHtml->a($up_url, $this->oL->m('3_up'));
        } else {
            $this->str .= '<del>' . $this->oL->m('3_up') . '</del>';
        }

        $this->str .= ' ';

        if ($is_down) {
            $this->str .= $this->oHtml->a($down_url, $this->oL->m('3_down'));
        } else {
            $this->str .= '<del>' . $this->oL->m('3_down') . '</del>';
        }

        $this->str .= ' ';

        $this->oHtml->setTag('a', 'onclick', 'return confirm(\'' . $confirm_text . '\')');
        $this->str .= $this->oHtml->a($remove_url, $this->oL->m('3_remove'));
        $this->oHtml->setTag('a', 'onclick', '');

        $this->str .= '</td>';
        $this->str .= '</tr>';

        $row_number++;

        if ($id_letter === $selected_letter_id) {
            $o_form->setTag('input', 'style', 'width:2em;font-size:200%');
        }
    }

    $this->str .= '</tbody></table>';
    $this->str .= '<div>';
    $this->str .= $o_form->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
    $this->str .= $o_form->field('hidden', GW_ACTION, GW_A_EDIT);
    $this->str .= $o_form->field('hidden', 'mode', 'update');
    $this->str .= $o_form->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
    $this->str .= $o_form->field('hidden', GW_TARGET_ID, $tid);
    $this->str .= '</div>';
    $this->str .= '</form>';

    if ($selected_letter_id > 0) {
        $this->str .= CRLF . '<script type="text/javascript">/*<![CDATA[*/';
        $this->str .= 'window.scrollTo(0, jsUtils.GetRealPos(gw_getElementById("az-' . $selected_letter_id . '")).top );';
        $this->str .= '/*]]>*/</script>';
    }
}

$this->str .= '</td>';
$this->str .= '</tr>';
$this->str .= '</tbody></table>';

