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
$sys['class_queries'] = 'gwtk_query_storage';

class gwtk_query_storage extends gw_query_storage
{
    /* */
    public function setQ()
    {
        return $this->q_import(['query_storage_global', 'query_storage_sess']);
    }

    /* */
    public function setAddonQ($ar)
    {
        global $gw_this, $sys;
        $arSql = [];
        foreach ($ar as $k => $v) {
            $target = preg_replace('/[^a-z0-9_\-]/', '', strtolower($gw_this['vars'][GW_TARGET]));
            $query_file = preg_replace('/[^a-z0-9_\-]/', '', strtolower($v));
            $path = $sys['path_addon'] . '/' . $target . '/' . $query_file . $this->str_suffix . '.php';
            if (file_exists($path)) {
                $tmp = [];
                include($path);
                $arSql = array_merge($arSql, $tmp['ar_queries']);
            }
        }
        $this->is_loaded = 1;
        $this->arQ = array_merge($this->arQ, $arSql);
    }
}
