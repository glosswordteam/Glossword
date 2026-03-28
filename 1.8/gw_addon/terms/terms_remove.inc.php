<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008 Glossword.biz team
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW')) {
    die('<!-- $Id: terms_remove.inc.php 500 2008-06-15 23:38:18Z glossword_team $ -->');
}

/* Included from $oAddonAdm->alpha(); */

if (!$this->gw_this['vars']['isConfirm']) {
    /* Deletion must be confirmed */
    return;
}

/* Enable debug mode */
# $this->sys['isDebugQ'] = 1;

global $arDictParam;

$ar_post = &$this->gw_this['vars']['arPost'];

/*
 1) Delete term from dictionary, $DBTABLE
 2) Delete term from assigned keywords, TBL_WORDMAP
 3) Delete term from assigned terms to user, TBL_MAP_USER_TERM
 4) Update dictionary settings, table TBL_DICT
 5) Check permissions
 */

$dict_id = isset($this->gw_this['vars']['id']) ? (int) $this->gw_this['vars']['id'] : 0;
$current_user_id = (int) $this->oSess->user_get('id_user');
$dict_table = isset($arDictParam['tablename']) ? (string) $arDictParam['tablename'] : '';

if ($dict_id <= 0 || $dict_table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $dict_table)) {
    return;
}

$ar_query = [];

/* Check permissions */
$sql_where = '';
if (!$this->oSess->is('is-terms')) {
    $sql_where = ' AND `id_user` = ' . $current_user_id;
}

# prn_r($this->gw_this['vars']['arPost']);

if (isset($this->gw_this['vars']['arPost']['is_all'])) {
    if (isset($this->gw_this['vars']['arPost']['is_save_history'])) {
        /* Mark as deleted */
        $ar_query[] = 'UPDATE `' . $dict_table . '` SET `is_active` = 3 WHERE 1 = 1' . $sql_where;

        /* Place into the removal schedule */
        $ar_query[] = 'UPDATE `'
            . $this->sys['tbl_prefix']
            . 'history_terms` SET `is_active` = 3 WHERE `id_dict` = '
            . $dict_id
            . $sql_where;
    } else {
        /* Remove now */
        $ar_query[] = 'DELETE FROM `' . $dict_table . '` WHERE 1 = 1' . $sql_where;
        $ar_query[] = 'DELETE FROM `'
            . $this->sys['tbl_prefix']
            . 'history_terms` WHERE `id_dict` = '
            . $dict_id
            . $sql_where;
    }
} else {
    $ar_term_ids = [];

    if (!isset($this->gw_this['vars']['arPost']['ar_id'])) {
        $ar_term_ids[] = isset($this->gw_this['vars']['tid']) ? (int) $this->gw_this['vars']['tid'] : 0;
    } else {
        foreach ((array) $this->gw_this['vars']['arPost']['ar_id'] as $id_term) {
            $ar_term_ids[] = (int) $id_term;
        }
    }

    $ar_term_ids = array_unique($ar_term_ids);
    $ar_term_ids = array_filter($ar_term_ids);

    foreach ($ar_term_ids as $id_term) {
        if (isset($this->gw_this['vars']['arPost']['is_save_history'])) {
            $ar_query[] = 'UPDATE `'
                . $dict_table
                . '` SET `is_active` = 3 WHERE `id` = '
                . $id_term
                . $sql_where;

            /* See `maintenance_clear_history_terms.php` for old term removal */
            /* -- Change history -- */
            /* Select the latest history record for the current term */
            $ar_current = $this->oDb->sqlExec(
                $this->oSqlQ->getQ('get-history-by-term_id', $id_term, 'LIMIT 1')
            );

            if (!empty($ar_current[0])) {
                $history_id = (int) $ar_current[0]['id'];

                /* Place into the removal schedule and set current user */
                $ar_query[] = 'UPDATE `'
                    . $this->sys['tbl_prefix']
                    . 'history_terms` SET `is_active` = 3, `id_user` = '
                    . $current_user_id
                    . ' WHERE `id` = '
                    . $history_id
                    . $sql_where;
            }
        } else {
            /* Remove now */
            $ar_query[] = 'DELETE FROM `'
                . $dict_table
                . '` WHERE `id` = '
                . $id_term
                . $sql_where;

            $ar_query[] = 'DELETE FROM `'
                . $this->sys['tbl_prefix']
                . 'history_terms` WHERE `id_term` = '
                . $id_term
                . $sql_where;
        }
    }
}

$ar_dict_update = [];
$ar_dict_update['date_modified'] = (int) $this->sys['time_now_gmt_unix'];

$ar_query[] = gw_sql_update(
    $ar_dict_update,
    $this->sys['tbl_prefix'] . 'dict',
    '`id` = ' . $dict_id
);

/* Clear cache */
$this->str .= gw_tmp_clear($dict_id);

/* Redirect to the next page */
$str_url = gw_after_redirect_url($ar_post['after']);
$this->str .= postQuery($ar_query, $str_url, $this->sys['isDebugQ'], 0);

