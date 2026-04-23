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
// --------------------------------------------------------
if (!class_exists('gw_query_storage')) {
    class gw_query_storage
    {

        public $str_suffix = '';
        public $is_loaded  = 0;
        public $arQ        = [];

        /* */
        public function set_suffix($v)
        {
            $this->str_suffix = $v;
        }

        /* */
        public function q_import($ar = [])
        {
            global $sys;
            $arSql = [];
            if ($this->is_loaded) {
                return $this->arQ;
            }
            foreach ($ar as $k => $v) {
                $tmp = [];
                if (file_exists($sys['path_include'] . '/' . $v . $this->str_suffix . '.php')) {
                    include($sys['path_include'] . '/' . $v . $this->str_suffix . '.php');
                    $arSql = array_merge($arSql, $tmp['ar_queries']);
                }
            }
            $this->is_loaded = 1;
            $this->arQ = $arSql;

            return $arSql;
        }

        /* */
        public function setCustomQ()
        {
            return [];
        }

        /* */
        public function setQ()
        {
            return $this->q_import(['query_storage_global']);
        }

        /**
         * Return SQL query by key with optional sprintf parameters.
         *
         * @return string
         */
        public function getQ($query_key)
        {
            $args = func_get_args();
            $sql_map = array_merge($this->setQ(), $this->setCustomQ());

            if (!isset($sql_map[$query_key])) {
                return '';
            }

            $sql = str_replace(["\n", "\r", "\t"], ' ', $sql_map[$query_key]);
            $sql = preg_replace('/\s{2,}/', ' ', $sql);

            array_shift($args);
            $args = array_pad($args, 8, '');

            return vsprintf($sql, $args);
        }
    } /* end of class */
}
