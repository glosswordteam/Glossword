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

$ar_query = [];
$target_id = (int) $this->gw_this['vars'][GW_TARGET_ID];
$delete_mode = isset($this->gw_this['vars']['w1']) ? (string) $this->gw_this['vars']['w1'] : '';

switch ($delete_mode) {
    case 'primary':
        $ar_query[] = gw_sql_delete(
            $this->sys['tbl_prefix'] . 'component',
            ['id_component' => $target_id]
        );
        $ar_query[] = gw_sql_delete(
            $this->sys['tbl_prefix'] . 'component_map',
            ['id_component' => $target_id]
        );
        break;

    case 'secondary':
        $ar_query[] = gw_sql_delete(
            $this->sys['tbl_prefix'] . 'component_map',
            ['id' => $target_id]
        );
        break;
}

/* Remove orphan actions */
$ar_query[] = 'DELETE cma
    FROM `' . $this->sys['tbl_prefix'] . 'component_actions` AS cma
    LEFT JOIN `' . $this->sys['tbl_prefix'] . 'component_map` AS cmm
        ON cmm.id_action = cma.id_action
    WHERE cmm.id_action IS NULL';

/* Redirect */
$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);