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

$topic_id = isset($this->gw_this['vars']['tid']) ? (int) $this->gw_this['vars']['tid'] : 0;
if ($topic_id <= 0) {
    $this->str .= '<p class="xr"><span class="red">' . $this->oL->m('reason_11') . '</span></p>';

    return;
}

/* Check permission to edit the topic */
$ar_sql = $this->oDb->sqlExec(
    $this->oSqlQ->getQ('get-topics-adm', $topic_id),
    'page'
);

$ar_parsed = isset($ar_sql[0]) ? $ar_sql[0] : [];
$is_allow_edit = 0;

if ($this->oSess->is('is-topics')) {
    $is_allow_edit = 1;
} elseif (
    $this->oSess->is('is-topics-own')
    && isset($ar_parsed['id_user'])
    && ((int) $ar_parsed['id_user'] === (int) $this->oSess->id_user)
) {
    $is_allow_edit = 1;
}

if (!$is_allow_edit) {
    $this->str .= '<p class="xu">' . $this->oL->m('reason_13') . '</p>';

    return;
}

$ar_topics = &$this->gw_this['ar_topics_list'];
$is_error = 0;
$msg_error = '';
$ar_query = [];

/* Topic must exist */
if (!isset($ar_topics[$topic_id])) {
    $is_error = 1;
    $msg_error .= '<br />' . $this->oL->m('reason_12');
}

/* Topic cannot be removed when assigned to a dictionary */
$sql = 'SELECT `id`, `title`
        FROM `' . $this->sys['tbl_prefix'] . 'dict`
        WHERE `id_topic` = ' . $topic_id;
$ar_sql = $this->oDb->sqlExec($sql);

if (!empty($ar_sql)) {
    $is_error = 1;
    $msg_error .= '<br />' . $this->oL->m('reason_3');
}

/* The last root topic cannot be deleted */
if (
    isset($ar_topics[$topic_id]['id_parent'])
    && (int) $ar_topics[$topic_id]['id_parent'] === 0
) {
    $sql = 'SELECT COUNT(*) AS n
            FROM `' . $this->sys['tbl_prefix'] . 'topics`
            WHERE `id_parent` = 0';
    $ar_sql = $this->oDb->sqlExec($sql);
    $root_topics_count = isset($ar_sql[0]['n']) ? (int) $ar_sql[0]['n'] : 0;

    if ($root_topics_count <= 1) {
        $is_error = 1;
        $msg_error .= '<br />' . $this->oL->m('reason_1');
    }
}

if ($is_error) {
    $this->str .= '<p class="xr"><span class="red">'
        . $this->oL->m('reason_11')
        . '</span> '
        . $msg_error
        . '</p>';

    return;
}

/* Read the topic tree */
$ar_keys = gw_ctlg_get_tree($ar_topics, $topic_id);

if (empty($ar_keys)) {
    return;
}

$sql_ids = implode(', ', $ar_keys);

/* Remove topics */
$ar_query[] = 'DELETE FROM `'
    . $this->sys['tbl_prefix']
    . 'topics` WHERE `id_topic` IN ('
    . $sql_ids
    . ')';

/* Remove topic phrases */
$ar_query[] = 'DELETE FROM `'
    . $this->sys['tbl_prefix']
    . 'topics_phrase` WHERE `id_topic` IN ('
    . $sql_ids
    . ')';

/* Redirect */
$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
