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
/**
 * Filter global variables
 * Requires:
 *     PHP_VERSION_INT
 *     $sys['token']
 *     CRLF
 * 2 Apr 2008: added nesting level
 */
if (!class_exists('gw_register_globals')) {
    class gw_register_globals
    {
        public $max_nesting_level = 10;

        /**
         * Register incoming request values by field names.
         *
         * Priority:
         * 1. POST
         * 2. GET
         * 3. COOKIE
         * 4. Default empty value
         *
         * Uploaded files are stored in _files.
         * Cookie values are stored in _cookie.
         *
         * @param array $ar Field names to register.
         *
         * @return array
         */
        public function register($ar = [])
        {
            global $sys;

            $tmp          = [
                '_files'  => [],
                '_cookie' => [],
            ];
            $cookie_token = isset($sys['token']) ? (string)$sys['token'] : '';

            if (!is_array($ar)) {
                return $tmp;
            }

            foreach ($ar as $field_name) {
                $field_name = (string)$field_name;

                if (isset($_POST[$field_name]) && ($_POST[$field_name] !== '')) {
                    /* Get value from POST */
                    $tmp[$field_name] = $_POST[$field_name];
                    $tmp['_method']   = 'post';
                } elseif (isset($_GET[$field_name]) && ($_GET[$field_name] !== '')) {
                    /* Get value from GET */
                    $tmp[$field_name] = $_GET[$field_name];
                } elseif (isset($_COOKIE[$field_name . $cookie_token]) && ($_COOKIE[$field_name . $cookie_token] !== '')) {
                    /* Get value from COOKIE — PHP already URL-decodes $_COOKIE values */
                    $tmp['_cookie'][$field_name] = $_COOKIE[$field_name . $cookie_token];
                } else {
                    /* Default value */
                    $tmp[$field_name] = '';
                }

                /* Filter incoming value */
                if (isset($tmp['_cookie'][$field_name])) {
                    $tmp['_cookie'][$field_name] = $this->fix_newline($tmp['_cookie'][$field_name]);
                    $tmp['_cookie'][$field_name] = $this->fix_slash($tmp['_cookie'][$field_name]);
                } else {
                    $tmp[$field_name] = $this->fix_newline($tmp[$field_name]);
                    $tmp[$field_name] = $this->fix_slash($tmp[$field_name]);
                }

                if (isset($_FILES[$field_name]) && is_array($_FILES[$field_name]) && !empty($_FILES[$field_name])) {
                    /* Get uploaded file data */
                    $tmp['_files'][$field_name] = $_FILES[$field_name];

                    if (isset($tmp['_files'][$field_name]['name'])) {
                        $tmp['_files'][$field_name]['name'] = $this->fix_slash($tmp['_files'][$field_name]['name']);
                    }
                }
            }

            return $tmp;
        }

        /**
         * 1.8.7: Normalizes new line character
         * 1.8.10: New lines replaced with constant CRLF using strstr().
         * 1.8.13: CRLF replaced with PHP_EOL
         */
        public function fix_newline($v, $level = 0)
        {
            if (is_array($v)) {
                $level++;
                if ($level <= $this->max_nesting_level) {
                    foreach ($v as $k1 => $v1) {
                        $v[$k1] = $this->fix_newline($v[$k1], $level);
                    }
                }
            } else {
                return trim(strtr($v, ["\r\n" => PHP_EOL, "\n" => PHP_EOL, "\r" => PHP_EOL]));
            }

            return $v;
        }

        /* 1.8.7: Fixes "Slash" problem */
        public function fix_slash($v, $level = 0)
        {
            return $v;
        }

        public function sprintf(&$t, $format = '%d')
        {
            return sprintf($format, $t);
        }

        public function do_default(&$t, $v)
        {
            $t = (trim($t) == '') ? $v : $t;
        }

        public function do_numeric(&$t)
        {
            $t = $this->sprintf($t, '%d');
            $t = ($t == 0 ? 1 : $t);
        }

        public function do_numeric_zero(&$t)
        {
            $t = $t + 0;
            $t = $this->sprintf($t, '%u');
        }

        public function do_substring(&$t, $int_limit = 1024)
        {
            $t = substr($t, 0, $int_limit);
        }

        public function do_substring_specials(&$t, $int_limit = 1024)
        {
            $t = strip_tags($t);
            $this->do_substring($t, $int_limit);
            $t = htmlspecialchars($t);
        }
    }

    /* Auto initialization */
    $oGlobals = new gw_register_globals;
    /* We don't need any global variables, really */
    $ar = array_merge($_POST, $_GET, $_COOKIE, $_FILES);
    foreach ($ar as $k => $v) {
        unset($$k);
    }
    unset($ar);
}

