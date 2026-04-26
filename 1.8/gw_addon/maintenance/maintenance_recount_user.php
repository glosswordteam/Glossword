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
/*
	Maintenance task
*/
/* */
include($sys['path_addon'] . '/class.gw_addon.php');

/* */

class gw_addon_recount_user extends gw_addon
{
    public $addon_name = 'recount_user';

    /* Autoexec */
    public function __construct()
    {
        $this->init_m();
    }

    /* */
    public function _recount()
    {
        $sql = 'SELECT user_id, count(*) AS n
				FROM `' . $this->sys['tbl_prefix'] . 'map_user_to_term`
				GROUP BY user_id
		';
        $arSql = $this->oDb->sqlExec($sql);
        $arQ = [];
        foreach ($arSql as $k => $arV) {
            $arQ[] = gw_sql_update(['int_items' => $arV['n']], gw_get_tbl_name('users'), "id_user = '" . $arV['user_id'] . "'");
        }
        foreach ($arQ as $sqlk => $sqlv) {
            $this->oDb->sqlExec($sqlv);
        }
    }

    /* */
    public function alpha()
    {
        if ((mt_rand() % 100) < $this->sys['prbblty_tasks']) {
            $this->_recount();
        }
    }

    /* */
    public function omega() {}
}

/* */
$oM = new gw_addon_recount_user;
$oM->alpha();
$oM->omega();
unset($oM);
