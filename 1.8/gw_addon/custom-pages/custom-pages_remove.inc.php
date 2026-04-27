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

$ar_queries = [];

if (!$this->gw_this['vars']['isConfirm']) {
    /* Deletion must be confirmed. */
    return;
}

$page_id = (int)$this->gw_this['vars'][GW_TARGET_ID];

if ($page_id < 1) {
    return;
}

/* Check page permissions. */
$ar_sql = $this->oDb->sqlExec(
    $this->oSqlQ->getQ('get-custompages-adm', $page_id)
);

if (empty($ar_sql)) {
    return;
}

$ar_parsed = $ar_sql[0];

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

/* @TODO: Reset parents migration */
$ar_query[] = 'UPDATE `' . gw_get_tbl_name('pages') . '` '
    . 'SET `id_parent` = 0 '
    . 'WHERE `id_parent` = ' . $page_id;

/* Remove one custom page. */
$ar_queries[] = 'DELETE FROM `' . gw_get_tbl_name('pages') . '` '
    . 'WHERE `id_page` = ' . $page_id;

/* Remove custom page phrases. */
$ar_queries[] = 'DELETE FROM `' . gw_get_tbl_name('pages_phrase') . '` '
    . 'WHERE `id_page` = ' . $page_id;

/* Redirect. */
$this->str .= postQuery(
    $ar_queries,
    $this->oUrlBuilder->build_admin_url(
        GW_A_BROWSE,
        $this->gw_this['vars'][GW_TARGET]
    ),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
