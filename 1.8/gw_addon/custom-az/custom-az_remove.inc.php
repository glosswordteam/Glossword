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

$target_id = (int)$this->gw_this['vars'][GW_TARGET_ID];
$ar_query = [];

if (!$this->gw_this['vars']['isConfirm']) {
    /* Deletion must be confirmed */
    return;
}

if ($target_id === 1) {
    // UTF-8 - The default sorting order
    $this->str .= $this->_get_nav();
    $this->str .= '<div class="xt">' . $this->oL->m('1293') . '</div>';

    return;
}

/* Remove from profiles */
$ar_query[] = gw_sql_delete(gw_get_tbl_name('custom_az'), ['id_profile' => $target_id]);

/* Remove from alphabetic orders */
$ar_query[] = gw_sql_delete(gw_get_tbl_name('custom_az_profiles'), ['id_profile' => $target_id]);

/* Replace with the default profile */
$ar_query[] = gw_sql_update(['id_custom_az' => 1], gw_get_tbl_name('dict'), '`id_custom_az` = ' . $target_id);

$redirect_url = $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component, ['r' => time()]);

/* Redirect */
$this->str .= postQuery(
    $ar_query,
    $redirect_url,
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
