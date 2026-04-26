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

/* */
$this->str .= $this->_get_nav();

/* Select timeframe */
if (!isset($this->gw_this['vars']['arPost']['fmt'])) {
    $vars = $this->get_dates();
    $vars['is_as_file'] = 1;
    /* Adjust time */
    $vars['min'] += ($this->oSess->user_get_time_seconds());
    $vars['max'] += ($this->oSess->user_get_time_seconds());
    $this->str .= $this->get_form_export($vars);
} else {
    $arPost =& $this->gw_this['vars']['arPost'];
    $arPost['is_as_file'] = isset($arPost['is_as_file']) ? 1 : 0;

    /* Get the number of terms */
    $vars['int_terms'] = 0;
    $tmp['ar_min_his'] = explode(':', $arPost['date_minS']);
    $tmp['ar_max_his'] = explode(':', $arPost['date_maxS']);
    /* hour, minute, second, month, day, year  */
    $vars['min'] = mktime($tmp['ar_min_his'][0], $tmp['ar_min_his'][1], $tmp['ar_min_his'][2],
        $arPost['date_minM'], $arPost['date_minD'], $arPost['date_minY']);
    $vars['max'] = mktime($tmp['ar_max_his'][0], $tmp['ar_max_his'][1], $tmp['ar_max_his'][2],
        $arPost['date_maxM'], $arPost['date_maxD'], $arPost['date_maxY']);
    /* Adjust time */
    $vars['min'] -= ($this->oSess->user_get_time_seconds());
    $vars['max'] -= ($this->oSess->user_get_time_seconds());
    /* */
    $xml = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $xml .= '<glossword version="' . $this->sys['version'] . '">';
    /* */
    $arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-records-date', $this->sys['tbl_prefix'] . 'pages', $vars['min'], $vars['max']));
    foreach ($arSql as $k => $arV) {
        $style_attr = '';
        $id_page = $arV['id_page'];
        $page_php_1 = $arV['page_php_1'];
        $page_php_2 = $arV['page_php_2'];
        unset($arV['id_page']);
        unset($arV['page_php_1']);
        unset($arV['page_php_2']);
        $xml .= CRLF . '<custom_page id="' . $id_page . '">';
        /* Now serialize all parameters. Fast and easy. */
        $xml .= CRLF . "\t" . '<parameters><![CDATA[' . serialize($arV) . ']]></parameters>';
        $xml .= CRLF . "\t" . '<page_php_1><![CDATA[' . $page_php_1 . ']]></page_php_1>';
        $xml .= CRLF . "\t" . '<page_php_2><![CDATA[' . $page_php_2 . ']]></page_php_2>';
        /* get topic names */
        $xml .= CRLF . "\t" . '<entry>';
        $arSql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-lang-adm', $id_page));
        foreach ($arSql2 as $k2 => $arV2) {
            /* remove encoding name */
            $arV2['id_lang'] = preg_replace("/-([a-z0-9])+$/", '', $arV2['id_lang']);
            /* start topic names */
            $xml .= CRLF . "\t\t" . '<lang xml:lang="' . $arV2['id_lang'] . '">';
            unset($arV2['id_lang']);
            foreach ($arV2 as $attrK => $attrV) {
                $xml .= CRLF . "\t\t\t<" . $attrK . '>';
                $xml .= ($attrV == '') ? '' : '<![CDATA[' . $attrV . ']]>';
                $xml .= '</' . $attrK . '>';
            }
            $xml .= CRLF . "\t\t" . '</lang>';
        }
        $xml .= CRLF . "\t" . '</entry>';
        $xml .= CRLF . '</custom_page>';
    }
    $xml .= CRLF . '</glossword>';

    $filename = 'gw_' . $this->component . '_' . date("Y-m[M]-d", $this->sys['time_now_gmt_unix']) . '.xml';

    if ($arPost['is_as_file']) {
        /* Send headers */
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Content-Type: application/force-download');
        } else {
            header('Content-Type: application/octet-stream');
        }
        header('Content-Length: ' . strlen($xml));
        header('Content-disposition: attachment; filename="' . $filename . '"');
        print $xml;
        exit;
    } else {
        /* Write to disk */
        $mode = 'w';
        $filename = $this->sys['path_export'] . '/' . $filename;
        $this->str .= '<ul class="xt">';
        $this->str .= '<li><span class="gray">';
        $this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';
        $isWrite = $this->oFunc->file_put_contents($filename, $xml, $mode);
        $this->str .= ($isWrite ? 'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist(LOCALE_LANG_RULES)) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error')) . '</li>';
        $this->str .= '</ul>';
    }
}
