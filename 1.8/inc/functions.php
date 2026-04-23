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
 * Replacement for print_r()
 *
 * @param string $a Any string, object, or array.
 * @param string $c Additional marker for better visual display. Try "__FILE__"
 */
function prn_r($a, $c = '')
{
    if (is_array($a)) {
        ksort($a);
        $a = gw_htmlspecialchars_ltgt($a);
    } elseif (is_object($a) || is_string($a)) {
        $a = gw_htmlspecialchars_ltgt($a);
    }
    /* Set font size in pixels because function can be called from various places */
    print '<pre style="text-align:left;color:#000;background:#FFF;font: 14px/16px Consolas,\'Courier New\',monospace">';
    if ($c) {
        print '===&gt; <strong>' . $c . "</strong>\n";
    }
    /* Placing the output into buffer */
    ob_start();
    print_r($a);
    $b = ob_get_clean();
    /* compress indents */
    $b = preg_replace("/(^)?(    )([\(|\)|\[])?/", "  \\3", $b);
    /* highlight Array and Object */
    $b = str_replace('] => Array', '] => <span style="color:#080">Array</span>', $b);
    $b = str_replace('] => Object', '] => <span style="color:#080">Object</span>', $b);
    /* highlight numeric positive and negative keys */
    $b = preg_replace(
        "/\[(-)?(\d+)\] =>/",
        '<span style="color:#888">&#91;<span style="color:#00C">\\1\\2</span>] =></span>',
        $b
    );
    $b = preg_replace(
        "/\[(.*)\] =>/",
        '<span style="color:#888">&#91;<span style="color:#C50">\\1</span>] =></span>',
        $b
    );
    print $b;
    if ($c) {
        print '&lt;===';
    }
    print '</pre>';
}

/**
 * Adds simple HTML highlighting to SQL fragments.
 *
 * Supports strings and arrays recursively.
 *
 * @param mixed $value String or array to highlight
 * @return mixed Highlighted string or array
 */
function gw_highlight_sql($value)
{
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = gw_highlight_sql($item);
        }
        return $value;
    }

    if (is_string($value)) {
        $value = str_replace('(', '<b>(</b>', $value);
        $value = str_replace(')', '<b>)</b>', $value);
        $value = str_replace('&lt;/', '<strong>&lt;/</strong>', $value);
        $value = str_replace('&lt;', '<strong>&lt;</strong>', $value);
        $value = str_replace('&gt;', '<strong>&gt;</strong>', $value);
        $value = str_replace(',', '<i>,</i>', $value);
    }

    return $value;
}

/**
 * Escape a limited set of HTML-sensitive characters recursively.
 *
 * This helper is intentionally simpler and faster than htmlspecialchars().
 * Arrays are converted recursively.
 * Objects are converted to arrays of declared class properties.
 *
 * @param mixed $value Input value.
 *
 * @return mixed
 */
function gw_htmlspecialchars_ltgt($value)
{
    $result = [];

    if (is_array($value)) {
        foreach ($value as $key => $item_value) {
            $result[$key] = gw_htmlspecialchars_ltgt($item_value);
        }

        return $result;
    }

    if (is_object($value)) {
        $class_vars = get_class_vars(get_class($value));

        foreach ($class_vars as $key => $item_value) {
            $result[$key] = isset($value->$key)
                ? gw_htmlspecialchars_ltgt($value->$key)
                : gw_htmlspecialchars_ltgt($item_value);
        }

        return $result;
    }

    if (is_string($value)) {
        return str_replace(
            ['&', '<', '>', '{', '[', '"'],
            ['&amp;', '&lt;', '&gt;', '&#123;', '&#091;', '&quot;'],
            $value
        );
    }

    return $value;
}

/**
 * Unescape a limited set of HTML-sensitive characters recursively.
 *
 * Arrays are converted recursively.
 * Objects are converted to arrays of declared class properties.
 *
 * @param mixed $value Input value.
 *
 * @return mixed
 */
function gw_unhtmlspecialchars_ltgt($value)
{
    $result = [];

    if (is_array($value)) {
        foreach ($value as $key => $item_value) {
            $result[$key] = gw_unhtmlspecialchars_ltgt($item_value);
        }

        return $result;
    }

    if (is_object($value)) {
        $class_vars = get_class_vars(get_class($value));

        foreach ($class_vars as $key => $item_value) {
            $result[$key] = isset($value->$key)
                ? gw_unhtmlspecialchars_ltgt($value->$key)
                : gw_unhtmlspecialchars_ltgt($item_value);
        }

        return $result;
    }

    if (is_string($value)) {
        return str_replace(
            ['&amp;', '&lt;', '&gt;', '&#123;', '&#091;', '&quot;'],
            ['&', '<', '>', '{', '[', '"'],
            $value
        );
    }

    return $value;
}

/**
 * Escape ampersand and quotes in a plain string.
 *
 * Numeric HTML entities are preserved.
 *
 * @param mixed $value Source value.
 *
 * @return mixed
 */
function gw_htmlspecials_amp($value)
{
    if (!is_string($value)) {
        return $value;
    }

    $value = str_replace(
        ['&', '"', '\''],
        ['&amp;', '&quot;', '&#039;'],
        $value
    );

    $value = preg_replace('/&amp;#([0-9]+);/', '&#$1;', $value);

    return $value;
}

/**
 * Unescape ampersand and quotes in a plain string.
 *
 * @param mixed $value Source value.
 *
 * @return mixed
 */
function gw_unhtmlspecials_amp($value)
{
    if (!is_string($value)) {
        return $value;
    }

    $value = str_replace(
        ['&amp;', '&quot;', '&AMP;', '&QUOT;', '&#039;'],
        ['&', '"', '&', '"', '\''],
        $value
    );

    return $value;
}

/**
 * Escapes string using mysqli connection.
 *
 * @param mysqli $conn Active MySQLi connection
 * @param string $str Input string
 * @return string Escaped string
 */
function gw_mysqli_escape($conn, $str)
{
    return mysqli_real_escape_string($conn, $str);
}

/**
 * Normalizes new line character. Recursive, calls by reference.
 * Note: Windows - CRLF, *nix - LF, Mac - CR
 *
 * @depreciated
 * @param string $str String to normalize
 */
function gw_fix_newline(&$t)
{
    if ($t == '') {
        return;
    }
    if (is_array($t) || is_object($t)) {
        array_walk($t, 'gw_fix_newline');
    } else {
        /* parsing 10 KB: preg_replace = 0.021457, str_replace = 0.000364 */
        $t = str_replace("\r\n", "\x01", $t);
        $t = str_replace("\n", "\x01", $t);
        $t = str_replace("\r", "\x01", $t);
        $t = str_replace("\x01", CRLF, $t);
    }
}

/**
 * Safely redirects to another location. Replacement for header()
 *
 * @param string $url Resourse locator name
 * @param int $isDebug [ 0 - silent | 1 - do not redirect and print URL ]
 */
function gwtk_header($url, $is_debug = 0, $fromfile = '', $fromline = '')
{
    global $db;

    $url = str_replace('&amp;', '&', $url);

    if (!empty($db)) {
        $db->close();
    }

    if ($is_debug || headers_sent($filename, $linenum)) {
        if ($filename) {
            print 'Headers already sent in ' . $filename . ' on line ' . $linenum . '<br />';
        }

        print 'Location:<br />' . sprintf(
                '<a href="%s">%s</a><br />%s <b>%s</b>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($fromfile, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($fromline, ENT_QUOTES, 'UTF-8')
            );
        exit;
    }

    header('Location: ' . $url, true, 303);
    exit;
}

/**
 * Return client IP address.
 *
 * Priority:
 * 1. First valid public IPv4 from X-Forwarded-For
 * 2. REMOTE_ADDR
 *
 * @return string
 */
function gw_get_remote_ip()
{
    $remote_addr = getenv('REMOTE_ADDR');

    if (($remote_addr === false) || ($remote_addr === '')) {
        $remote_addr = '127.0.0.1';
    }

    $x_forwarded_for = getenv('HTTP_X_FORWARDED_FOR');

    if (($x_forwarded_for !== false) && ($x_forwarded_for !== '')) {
        $ip_list = explode(',', $x_forwarded_for);

        foreach ($ip_list as $ip_item) {
            $ip_item = trim($ip_item);

            if ($ip_item === '') {
                continue;
            }

            if (!filter_var($ip_item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }

            if (gw_is_private_ipv4($ip_item)) {
                continue;
            }

            return $ip_item;
        }
    }

    if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === $remote_addr)) {
        return '127.0.0.1';
    }

    return $remote_addr;
}

/**
 * Checks whether IPv4 address is private, loopback, multicast, reserved or local.
 *
 * @param string $ip
 * @return bool
 */
function gw_is_private_ipv4($ip)
{
    return (
        strpos($ip, '127.') === 0 ||
        strpos($ip, '10.') === 0 ||
        strpos($ip, '192.168.') === 0 ||
        preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) ||
        strpos($ip, '0.') === 0 ||
        strpos($ip, '224.') === 0 ||
        strpos($ip, '240.') === 0
    );
}

/**
 * Store flash message (one-time message)
 *
 * @param string $key
 * @param string $message
 * @return void
 */
function gw_flash_set($key, $message)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    $_SESSION['_flash'][$key] = $message;
}

/**
 * Get and remove flash message
 *
 * @param string $key
 * @return string
 */
function gw_flash_get($key)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['_flash'][$key])) {
        return '';
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    // optional cleanup
    if (empty($_SESSION['_flash'])) {
        unset($_SESSION['_flash']);
    }

    return $message;
}

/**
 * Detect current request protocol (http / https).
 *
 * @return string
 */
function gw_get_protocol()
{
    // HTTPS via standard server var
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    ) {
        return 'https://';
    }

    // HTTPS via reverse proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return 'https://';
        }
    }

    return 'http://';
}

/**
 * Return system settings as key-value array.
 *
 * If settings are missing and installer exists, redirect to installation.
 *
 * @return array
 */
function gw_get_settings()
{
    global $oSqlQ, $oDb, $sys;

    $settings = [];
    $settings_rows = $oDb->sqlRun($oSqlQ->getQ('get-settings'), 'st');

    /* No system settings found, run installer */
    if (empty($settings_rows)) {
        if (file_exists('gw_install/index.php')) {
            $sys['server_proto'] = gw_get_protocol();
            $sys['server_host'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $sys['server_dir'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            $path_parts = explode('/', $sys['server_dir']);
            unset($path_parts[count($path_parts) - 1]);
            $sys['server_dir'] = implode('/', $path_parts);

            gwtk_header($sys['server_proto'] . $sys['server_host'] . $sys['server_dir'] . '/gw_install/index.php');
            return [];
        }

        print '<p>Software is not installed.</p>';
        print '<p><a href="' . $sys['server_dir'] . '/gw_install/index.php">Run installation script</a></p>';
        exit;
    }

    foreach ($settings_rows as $row) {
        $settings[$row['settings_key']] = $row['settings_val'];
    }

    return $settings;
}

function gw_get_dict_authors($dict_id)
{
    global $oSqlQ, $oDb, $oHtml, $oTpl, $oUrlBuilder, $arDictParam;

    $ar_authors = [];
    if ($arDictParam['is_show_authors']) {
        $arSql = $oDb->sqlRun($oSqlQ->getQ('get-users-by-dict_id', (int)$dict_id), 'dict');
        $ar_authors = [];

        foreach ($arSql as $ar_v) {
            $ar_authors[] = $oHtml->a(
                $oUrlBuilder->build_index_url(
                    GW_A_PROFILE,
                    null,
                    [GW_TARGET => GW_A_VIEW, 'id' => (int)$ar_v['id_user']]
                ),
                strip_tags((string)$ar_v['user_name'])
            );
        }
    }
    return implode(', ', $ar_authors);
}

/**
 * Load custom language data and merge or return it.
 *
 * @param string $f_name
 * @param string $localename
 * @param string $mode
 *
 * @return array|null
 */
function gw_get_stop_words_locales()
{
    global $gw_this, $oL;
    $result = [];
    foreach ($gw_this['vars']['ar_languages'] as $locale_code => $locale_name_origin) {
        $ar_stop_words = $oL->fetchCustomPhrases('stop_words', $locale_code);
        if (!empty($ar_stop_words)) {
            $result[$locale_code] = $locale_name_origin;
        }
    }
    return $result;
}

/**
 * Recursively merge arrays and overwrite existing values by key.
 *
 * Numeric keys are preserved and are not reindexed.
 * If both values are arrays, they are merged recursively.
 * Otherwise, the value from the second array overwrites the first one.
 *
 * @param array $array_1
 * @param array $array_2
 *
 * @return array|bool
 */
function gw_array_merge_clobber($array_1, $array_2)
{
    if (!is_array($array_1) || !is_array($array_2)) {
        return false;
    }

    $result = $array_1;

    foreach ($array_2 as $key => $value) {
        if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
            $result[$key] = gw_array_merge_clobber($result[$key], $value);
        } else {
            $result[$key] = $value;
        }
    }

    return $result;
}

