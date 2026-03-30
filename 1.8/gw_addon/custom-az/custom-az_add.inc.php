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

$table_name = gw_get_tbl_name('custom_az_profiles');

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

$ar_req_fields = ['profile_name'];

if ($this->gw_this['vars']['post'] == '') {
    $ar_post = [
        'profile_name' => '',
        'is_active'    => 1,
    ];

    $this->str .= $this->get_form_custom_az($ar_post, 0, 0, $ar_req_fields);
} else {
    $ar_post = $this->gw_this['vars']['arPost'];

    /* Validate posted values */
    $ar_broken = validatePostWalk($ar_post, $ar_req_fields);

    if (empty($ar_broken)) {
        $ar_query = [];
        $ar_insert = $ar_post;

        /* Legacy-compatible manual ID generation */
        $ar_insert['id_profile'] = (int)$this->oDb->NextId($table_name, 'id_profile');

        $ar_query[] = gw_sql_insert($ar_insert, $table_name);

        $redirect_url = $this->oUrlBuilder->build_admin_url(
            GW_A_BROWSE,
            $this->component,
            [GW_TARGET_ID => (int)$ar_insert['id_profile']]
        );

        $this->str .= postQuery(
            $ar_query,
            $redirect_url,
            $this->sys['isDebugQ'],
            $this->sys['isPause']
        );
    } else {
        $this->oTpl->addVal(
            'v:note_afterpost',
            gw_get_note_afterpost($this->oL->m(1370))
        );

        $this->str .= $this->get_form_custom_az(
            $ar_post,
            1,
            $ar_broken,
            $ar_req_fields
        );
    }
}

$this->str .= '</td></tr></tbody></table>';

