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

$target_id = (int)$this->gw_this['vars']['tid'];

/* Remove from profiles */
$ar_query[] = gw_sql_delete(
    gw_get_tbl_name('virtual_keyboard'),
    ['id_profile' => $target_id]
);

/* Replace with the default profile */
$ar_query[] = gw_sql_update(
    ['id_vkbd' => 0],
    gw_get_tbl_name('dict'),
    '`id_vkbd` = ' . $target_id
);

/* Redirect */
$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);