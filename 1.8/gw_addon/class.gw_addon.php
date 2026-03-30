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
/* ------------------------------------------------------- */
/**
 * Interface to addons.
 * Example:
 * /glossword/gw_addon/gw_addon_name/index.php
 *
 * include($sys['path_addon'].'/class.gw_addon.php');
 * class gw_addon_name extends gw_addon
 * {
 * function gw_addon_name()
 * {
 * $this->init();
 * }
 * }
 */
if (!defined('IS_CLASS_ADDON')) {
    define('IS_CLASS_ADDON', 1);

    class gw_addon
    {

        /** @var gw_session */
        public $oSess;

        /** @var gw_database */
        public $oDb;

        /** @var gw_sql_query */
        public $oSqlQ;

        /** @var gw_language */
        public $oL;

        /** @var gw_html */
        public $oHtml;

        /** @var gw_functions */
        public $oFunc;

        /** @var gw_template */
        public $oTpl;

        /** @var gw_case */
        public $oCase;

        /** @var array */
        public $sys = [];

        /** @var array */
        public $gw_this = [];

        /** @var array */
        public $ar_theme = [];

        /** @var array */
        public $arDictParam = [];

        /** @var gw_url_builder */
        public $oUrlBuilder;

        public $str;
        public $cfg;

        /**
         * Initialize full addon dependencies.
         *
         * @return void
         */
        public function init()
        {
            global $oSess, $oDb, $oSqlQ, $oL, $oHtml, $oFunc, $oTpl, $oCase, $sys, $gw_this, $ar_theme, $arDictParam, $oUrlBuilder;

            $this->oSess       =& $oSess;
            $this->oFunc       =& $oFunc;
            $this->oDb         =& $oDb;
            $this->oSqlQ       =& $oSqlQ;
            $this->oCase       =& $oCase;
            $this->oL          =& $oL;
            $this->oTpl        =& $oTpl;
            $this->oHtml       =& $oHtml;
            $this->gw_this     =& $gw_this;
            $this->sys         =& $sys;
            $this->ar_theme    =& $ar_theme;
            $this->arDictParam =& $arDictParam;
            $this->oUrlBuilder =& $oUrlBuilder;
        }

        /**
         * Initialize minimal addon dependencies for API mode.
         *
         * @return void
         */
        public function init_m()
        {
            global $oDb, $oSqlQ, $oFunc, $sys, $gw_this, $arDictParam, $oUrlBuilder;

            $this->oFunc       =& $oFunc;
            $this->oDb         =& $oDb;
            $this->oSqlQ       =& $oSqlQ;
            $this->gw_this     =& $gw_this;
            $this->sys         =& $sys;
            $this->arDictParam =& $arDictParam;
            $this->oUrlBuilder =& $oUrlBuilder;
        }
    }
}
/* */
$oSqlQ->setAddonQ([$gw_this['vars'][GW_TARGET]]);

