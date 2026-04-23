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

/* --------------------------------------------------------
 * Simple timer class
 * - 23 july 2003: Allow multiple instances
 * - 02 feb 2004: Function for a visual report, endp()
 * - 22 mar 2026: Compatible with PHP 5.6.
 * ----------------------------------------------------- */
/* -------------------------------------------------------- */
if (!defined('IS_CLASS_TIMER')) {
    define('IS_CLASS_TIMER', 1);

    class gw_timer
    {

        private $_prefix = '';

        /* */
        public function __construct($prefix = '')
        {
            $this->_prefix = $prefix;
            $var = $this->_prefix . '_starttime';
            global $$var;
            $mtime = explode(' ', microtime());
            $$var = (float)$mtime[1] + (float)$mtime[0];
        }

        public function end()
        {
            $var = $this->_prefix . '_starttime';
            global $$var;
            $mtime = explode(' ', microtime());
            $endtime = (float)$mtime[1] + (float)$mtime[0];
            return ($endtime - $$var);
        }

        public function endp($str_line = '', $str_file = '')
        {
            return sprintf("<div style=\"margin:0;color:#000;background:#FFF\">%1.6f <tt>%s <= %s</tt></div>", $this->end(), $str_line, $str_file);
        }
    }
}
