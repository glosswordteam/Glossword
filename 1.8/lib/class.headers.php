<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2021 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* --------------------------------------------------------
 * Simple HTTP-headers class
 * ----------------------------------------------------- */
if ( ! defined('IN_GW')) {
    die('<!-- Not in App -->');
}
/* ----------------------------------------------------- */
if ( ! class_exists('gw_headers')) {
    class gw_headers
    {
        public $is_debug = GW_DEBUG_HTTP;
        public $arH = array();
        public $arHText = array();

        public function add($str)
        {
            if ($str != '') {
                $this->arH[] = $str;
            }
        }

        public function output()
        {
            foreach ($this->arH as $k => $v) {
                @header($v);
                if ($this->is_debug) {
                    $this->arHText[] = $v;
                }
            }
        }

        public function get()
        {
            return $this->arH;
        }
    } /* end of class */
    /* Autostart */
    $oHdr = new gw_headers;
}
