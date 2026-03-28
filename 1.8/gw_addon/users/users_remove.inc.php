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

if (!$this->gw_this['vars']['isConfirm']) {
    /* Deletion must be confirmed */
    return;
}

$target_user_id = (int) $this->gw_this['vars']['w1'];

/* Keep guest user */
/* Keep admin user */
if ($target_user_id === 1 || $target_user_id === 2) {
    $this->str .= $this->_get_nav();
    $this->str .= '<div class="xt">' . $this->oL->m('1293') . '</div>';

    return;
}

$current_user_id = (int) $this->oSess->user_get('id_user');
$ar_query = [];

/**
 * Remove user:
 * 1. Remove the user from gw_users.
 * 2. Remove dictionary assignments for the user.
 * 3. Reassign dictionary ownership to the current admin.
 * 4. Reassign term ownership to the current admin.
 */
$ar_query[] = gw_sql_delete(
    $this->oSess->db_table_users,
    ['id_user' => $target_user_id]
);

$ar_query[] = gw_sql_delete(
    TBL_MAP_USER_DICT,
    ['user_id' => $target_user_id]
);

$ar_query[] = gw_sql_update(
    ['id_user' => $current_user_id],
    TBL_DICT,
    '`id_user` = ' . $target_user_id
);

$ar_query[] = gw_sql_update(
    ['user_id' => $current_user_id],
    TBL_MAP_USER_TERM,
    '`user_id` = ' . $target_user_id
);

$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
