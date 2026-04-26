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

$ar_query = [];

if (!$this->gw_this['vars']['isConfirm']) {
    /* Deletion must be confirmed */
    return;
}

$page_id = (int)$this->gw_this['vars'][GW_TARGET_ID];

/* Enable debug mode */
# $this->sys['isDebugQ'] = 1;

/* Check page permissions */
$ar_sql = $this->oDb->sqlExec(
    $this->oSqlQ->getQ('get-custompages-adm', $page_id)
);

$ar_parsed = isset($ar_sql[0]) ? $ar_sql[0] : ['id_user' => -1];

$is_allow_edit = 0;
if ($this->oSess->is('is-cpages')) {
    $is_allow_edit = 1;
} elseif (
    $this->oSess->is('is-cpages-own')
    && ((int)$ar_parsed['id_user'] === (int)$this->oSess->id_user)
) {
    $is_allow_edit = 1;
}

if (!$is_allow_edit) {
    $this->str .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';

    return;
}

/* Read the page tree */
$ar_keys = gw_ctlg_get_tree($this->ar, $page_id);

if (empty($ar_keys)) {
    return;
}

$sql_ids = implode(', ', $ar_keys);

/* Remove pages */
$ar_query[] = 'DELETE FROM `' . gw_get_tbl_name('pages') . '` WHERE `id_page` IN (' . $sql_ids . ')';

/* Remove page phrases */
$ar_query[] = 'DELETE FROM `' . gw_get_tbl_name('pages_phrase') . '` WHERE `id_page` IN (' . $sql_ids . ')';

/* Redirect */
$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);