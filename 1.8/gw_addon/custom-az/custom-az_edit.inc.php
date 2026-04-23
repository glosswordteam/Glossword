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

$tid                      = $this->gw_this['vars'][GW_TARGET_ID];
$selected_letter_id       = $this->gw_this['vars']['w1'];
$mode                     = $this->gw_this['vars']['mode'];
$table_custom_az          = gw_get_tbl_name('custom_az');
$table_custom_az_profiles = gw_get_tbl_name('custom_az_profiles');
$table_dict               = gw_get_tbl_name('dict');

$ar_query      = [];
$ar_sorted     = [];
$ar_req_fields = ['profile_name'];

/* Load letters for sorting/update actions */
$ar_az    = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custom_az-adm', $tid), $this->component);
$ar_az    = is_array($ar_az) ? $ar_az : [];
$max_sort = (count($ar_az) + 1) * 10;

/* Process letter actions */
switch ($mode) {
    case 'up':
        foreach ($ar_az as $row_index => $ar_row) {
            if ((int)$ar_row['id_letter'] === $selected_letter_id) {
                $ar_az[$row_index]['int_sort'] = (int)$ar_az[$row_index]['int_sort'] - 15;
            }
        }

        foreach ($ar_az as $ar_row) {
            $ar_sorted[(int)$ar_row['int_sort']] = (int)$ar_row['id_letter'];
        }
        break;

    case 'down':
        foreach ($ar_az as $row_index => $ar_row) {
            if ((int)$ar_row['id_letter'] === $selected_letter_id) {
                $ar_az[$row_index]['int_sort'] = (int)$ar_az[$row_index]['int_sort'] + 15;
            }
        }

        foreach ($ar_az as $ar_row) {
            $ar_sorted[(int)$ar_row['int_sort']] = (int)$ar_row['id_letter'];
        }
        break;

    case 'remove':
        if ($selected_letter_id > 0) {
            $ar_query[] = gw_sql_delete(
                $table_custom_az,
                ['id_letter' => $selected_letter_id]
            );
        }
        break;

    case 'update':
        $ar_post = isset($this->gw_this['vars']['arPost']) && is_array($this->gw_this['vars']['arPost'])
            ? $this->gw_this['vars']['arPost']
            : [];

        $new_az_value    = isset($ar_post['az_value'][0])
            ? trim((string)$ar_post['az_value'][0])
            : '';
        $new_az_value_lc = isset($ar_post['az_value_lc'][0])
            ? trim((string)$ar_post['az_value_lc'][0])
            : '';

        if ($new_az_value != '') {
            /* Add new letter */
            $ar_query[] = gw_sql_insert(
                [
                    'az_value'    => $new_az_value,
                    'az_value_lc' => $new_az_value_lc,
                    'az_int'      => text_str2ord($new_az_value),
                    'id_profile'  => $tid,
                    'int_sort'    => $max_sort,
                ],
                $table_custom_az
            );
        } else {
            /* Save all letters */
            $ar_post['az_value']    = isset($ar_post['az_value']) && is_array($ar_post['az_value'])
                ? $ar_post['az_value']
                : [];
            $ar_post['az_value_lc'] = isset($ar_post['az_value_lc']) && is_array($ar_post['az_value_lc'])
                ? $ar_post['az_value_lc']
                : [];

            foreach ($ar_post['az_value'] as $id_letter => $az_value) {
                $id_letter = (int)$id_letter;
                $az_value  = trim((string)$az_value);

                if (($id_letter <= 0) || ($az_value == '')) {
                    continue;
                }

                $az_value_lc = isset($ar_post['az_value_lc'][$id_letter])
                    ? trim((string)$ar_post['az_value_lc'][$id_letter])
                    : '';

                $ar_query[] = gw_sql_update(
                    [
                        'az_int'      => text_str2ord($az_value),
                        'az_value'    => $az_value,
                        'az_value_lc' => $az_value_lc,
                    ],
                    $table_custom_az,
                    'id_letter = ' . $id_letter
                );
            }
        }
        break;
}

/* Re-sort letters */
if (!empty($ar_sorted)) {
    ksort($ar_sorted, SORT_NUMERIC);

    $sort_value = 10;
    foreach ($ar_sorted as $id_item) {
        $id_item = (int)$id_item;
        if ($id_item <= 0) {
            continue;
        }

        $ar_query[] = gw_sql_update(
            ['int_sort' => $sort_value],
            $table_custom_az,
            'id_letter = ' . $id_item
        );
        $sort_value += 10;
    }
}

/* Save letter changes */
if (!empty($ar_query)) {
    $redirect_url = $this->oUrlBuilder->build_admin_url(
        GW_A_BROWSE,
        $this->component,
        [GW_TARGET_ID => $tid, 'w1' => $selected_letter_id]
    );

    $this->str .= postQuery(
        $ar_query,
        $redirect_url,
        $this->sys['isDebugQ'],
        $this->sys['isPause']
    );

    return;
}

$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:' . $this->left_td_width . ';background:' . $this->ar_theme['color_2'] . ';vertical-align:top">';

$this->str .= '<h3>' . $this->oL->m('2_page_custom_az_browse') . '</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', (array)$this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';

$this->str .= $this->_get_nav();

/* Get profile settings */
$ar_sql     = $this->oDb->sqlExec(
    $this->oSqlQ->getQ('get-custom_az-profile', $tid),
    $this->component
);
$ar_profile = isset($ar_sql[0]) && is_array($ar_sql[0]) ? $ar_sql[0] : [];

/* Profile is not defined */
if (empty($ar_profile)) {
    $this->str .= $this->oL->m('1341');
    $this->str .= '</td></tr></tbody></table>';

    return;
}

if (!isset($this->gw_this['vars']['post']) || ($this->gw_this['vars']['post'] == '')) {
    /* Removing profile */
    if (!empty($this->gw_this['vars']['remove'])) {
        /* Keep UTF-8 profile */
        if ($tid === 1) {
            $this->str .= '<div class="xt">' . $this->oL->m('1293') . '</div>';
            $this->str .= '</td></tr></tbody></table>';

            return;
        }

        /* Change heading */
        $this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname'])
            . ': '
            . $this->oL->m('3_remove');

        $profile_name = isset($ar_profile['profile_name']) ? (string)$ar_profile['profile_name'] : '';
        $profile_name = htmlspecialchars(
            $profile_name,
            ENT_QUOTES
        );

        $o_confirm                   = new gwConfirmWindow();
        $o_confirm->action           = $this->sys['page_admin'];
        $o_confirm->submitok         = $this->oL->m('3_remove');
        $o_confirm->submitcancel     = $this->oL->m('3_cancel');
        $o_confirm->formbgcolor      = $this->ar_theme['color_2'];
        $o_confirm->formbordercolor  = $this->ar_theme['color_4'];
        $o_confirm->formbordercolorL = $this->ar_theme['color_1'];
        $o_confirm->setQuestion(
            '<p class="xr"><strong class="red">' . $this->oL->m('9_remove') . '</strong></p>'
            . '<p class="xt"><span class="gray">' . $this->oL->m('3_remove') . ': </span>'
            . '&quot;' . $profile_name . '&quot;</p>'
        );
        $o_confirm->tAlign    = 'center';
        $o_confirm->formwidth = '400';
        $o_confirm->setField('hidden', GW_TARGET_ID, $tid);
        $o_confirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
        $o_confirm->setField(
            'hidden',
            GW_TARGET,
            isset($this->gw_this['vars'][GW_TARGET]) ? $this->gw_this['vars'][GW_TARGET] : ''
        );
        $o_confirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);

        $this->str .= $o_confirm->Form();
        $this->str .= '</td></tr></tbody></table>';

        return;
    }

    /* Not submitted */
    $this->str .= $this->get_form_custom_az($ar_profile, 0, [], $ar_req_fields);
} else {
    $ar_post = isset($this->gw_this['vars']['arPost']) && is_array($this->gw_this['vars']['arPost'])
        ? $this->gw_this['vars']['arPost']
        : [];

    $ar_post['profile_name'] = isset($ar_post['profile_name'])
        ? trim((string)$ar_post['profile_name'])
        : '';
    $ar_post['is_active']    = isset($ar_post['is_active']) ? 1 : 0;

    /* Validate posted values */
    $ar_broken = validatePostWalk($ar_post, $ar_req_fields);

    if (empty($ar_broken)) {
        $ar_query  = [];
        $ar_update = $ar_post;

        /* Default profile must stay active */
        if ($tid === 1) {
            $ar_update['is_active'] = 1;
        }

        $ar_query[] = gw_sql_update(
            $ar_update,
            $table_custom_az_profiles,
            'id_profile = ' . $tid
        );

        /* Inactive profile */
        if (!$ar_update['is_active']) {
            $ar_query[] = gw_sql_update(
                ['id_custom_az' => 1],
                $table_dict,
                'id_custom_az = ' . $tid
            );
        }

        $redirect_url = $this->oUrlBuilder->build_admin_url(
            GW_A_BROWSE,
            $this->component,
            [
                GW_TARGET_ID => $tid,
                'r'          => time(),
            ]
        );

        $this->str .= postQuery(
            $ar_query,
            $redirect_url,
            (int)$this->sys['isDebugQ'],
            0
        );
    } else {
        $this->oTpl->addVal(
            'v:note_afterpost',
            gw_get_note_afterpost($this->oL->m(1370))
        );

        $this->str .= $this->get_form_custom_az($ar_post, 1, $ar_broken, $ar_req_fields);
    }
}

$this->str .= '</td></tr></tbody></table>';

?>