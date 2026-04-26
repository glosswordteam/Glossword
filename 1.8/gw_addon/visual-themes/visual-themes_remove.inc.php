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

$theme_id = isset($this->gw_this['vars']['tid']) ? (string) $this->gw_this['vars']['tid'] : '';
$group_id = isset($this->gw_this['vars']['w1']) ? (int) $this->gw_this['vars']['w1'] : 0;
$settings_key = isset($this->gw_this['vars']['w2']) ? (string) $this->gw_this['vars']['w2'] : '';

/*
 * Theme ID is used both in SQL and in a filesystem path.
 * Keep it strict to prevent path traversal and invalid theme names.
 */
if ($theme_id !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $theme_id)) {
    $this->str .= '<p class="xu">' . $this->oL->m('reason_11') . '</p>';

    return;
}

if ($settings_key !== '') {
    /* Remove template from the template group */
    $ar_query[] = gw_sql_delete(
        $this->sys['tbl_prefix'] . 'theme_settings',
        ['settings_key' => $settings_key]
    );

    $ar_query[] = gw_sql_delete(
        $this->sys['tbl_prefix'] . 'theme_group',
        [
            'settings_key' => $settings_key,
            'id_group' => $group_id,
        ]
    );

    $this->str .= postQuery(
        $ar_query,
        $this->oUrlBuilder->build_admin_url(GW_A_EDIT, $this->gw_this['vars'][GW_TARGET], ['tid' => $theme_id, 'w1' => $group_id]),
        $this->sys['isDebugQ'],
        $this->sys['isPause']
    );

    return;
}

/*
 * 1. Delete theme from the theme list.
 * 2. Delete theme settings.
 * 3. Reset dictionary theme to the default one.
 */
$ar_query[] = gw_sql_delete(
    $this->sys['tbl_prefix'] . 'theme',
    ['id_theme' => $theme_id]
);

$ar_query[] = gw_sql_delete(
    $this->sys['tbl_prefix'] . 'theme_settings',
    ['id_theme' => $theme_id]
);

$ar_query[] = gw_sql_update(
    ['visualtheme' => (string) $this->sys['visualtheme']],
    $this->sys['tbl_prefix'] . 'dict',
    "`visualtheme` = '" . gw_text_sql($theme_id) . "'"
);

$ar_query[] = 'CHECK TABLE `' . $this->sys['tbl_prefix'] . 'theme_settings`';

/* Remove compiled theme cache */
$path_template = $this->sys['path_temporary'] . '/t/' . $theme_id;
$ar_files = file_readDirF($path_template, '//');

foreach ((array) $ar_files as $filename) {
    @chmod($path_template . '/' . $filename, 0777);
    @unlink($path_template . '/' . $filename);
}

@chmod($path_template, 0777);
@rmdir($path_template);

$this->str .= postQuery(
    $ar_query,
    $this->oUrlBuilder->build_admin_url(GW_A_BROWSE, $this->component),
    $this->sys['isDebugQ'],
    $this->sys['isPause']
);
