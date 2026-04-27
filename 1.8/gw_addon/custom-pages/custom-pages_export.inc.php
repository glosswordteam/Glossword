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

$this->str .= $this->_get_nav();

/* Wrap text into XML CDATA and split forbidden CDATA terminator. */
$cdata = function ($value) {
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string) $value) . ']]>';
};

/* Select timeframe. */
if (!isset($this->gw_this['vars']['arPost']['fmt'])) {
    $vars = $this->get_dates();
    $vars['is_as_file'] = 1;

    /* Adjust time. */
    $vars['min'] += $this->oSess->user_get_time_seconds();
    $vars['max'] += $this->oSess->user_get_time_seconds();

    $this->str .= $this->get_form_export($vars);
} else {
    $ar_post =& $this->gw_this['vars']['arPost'];
    $is_as_file = isset($ar_post['is_as_file']) ? 1 : 0;

    $vars = [];
    $tmp = [];

    /* The export form sends month, day and time. Year is restored from default export dates. */
    $ar_dates = $this->get_dates();
    $user_time_offset = $this->oSess->user_get_time_seconds();
    $ar_min_date = getdate($ar_dates['min'] + $user_time_offset);
    $ar_max_date = getdate($ar_dates['max'] + $user_time_offset);

    $ar_post['date_minY'] = $ar_min_date['year'];
    $ar_post['date_maxY'] = $ar_max_date['year'];

    $tmp['ar_min_his'] = explode(':', $ar_post['date_minS']);
    $tmp['ar_max_his'] = explode(':', $ar_post['date_maxS']);

    /* hour, minute, second, month, day, year. */
    $vars['min'] = mktime(
        (int) $tmp['ar_min_his'][0],
        (int) $tmp['ar_min_his'][1],
        (int) $tmp['ar_min_his'][2],
        (int) $ar_post['date_minM'],
        (int) $ar_post['date_minD'],
        (int) $ar_post['date_minY']
    );
    $vars['max'] = mktime(
        (int) $tmp['ar_max_his'][0],
        (int) $tmp['ar_max_his'][1],
        (int) $tmp['ar_max_his'][2],
        (int) $ar_post['date_maxM'],
        (int) $ar_post['date_maxD'],
        (int) $ar_post['date_maxY']
    );

    /* Adjust time. */
    $vars['min'] -= $this->oSess->user_get_time_seconds();
    $vars['max'] -= $this->oSess->user_get_time_seconds();

    $xml = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $xml .= '<glossword version="' . htmlspecialchars($this->sys['version'], ENT_QUOTES, 'UTF-8') . '">';

    $ar_sql = $this->oDb->sqlExec(
        $this->oSqlQ->getQ(
            'get-records-date',
            gw_get_tbl_name('pages'),
            $vars['min'],
            $vars['max']
        )
    );

    foreach ($ar_sql as $ar_page) {
        $id_page = (int) $ar_page['id_page'];
        $page_php_1 = $ar_page['page_php_1'];
        $page_php_2 = $ar_page['page_php_2'];

        unset($ar_page['id_page']);
        unset($ar_page['id_parent']);
        unset($ar_page['page_php_1']);
        unset($ar_page['page_php_2']);

        $xml .= CRLF . '<custom_page id="' . $id_page . '">';

        /* Serialize page parameters except legacy nested parent link. */
        $xml .= CRLF . "\t" . '<parameters>' . $cdata(serialize($ar_page)) . '</parameters>';
        $xml .= CRLF . "\t" . '<page_php_1>' . $cdata($page_php_1) . '</page_php_1>';
        $xml .= CRLF . "\t" . '<page_php_2>' . $cdata($page_php_2) . '</page_php_2>';

        /* Export page phrases. */
        $xml .= CRLF . "\t" . '<entry>';

        $ar_phrases = $this->oDb->sqlExec(
            $this->oSqlQ->getQ('get-custompages-lang-adm', $id_page)
        );

        foreach ($ar_phrases as $ar_phrase) {
            /* Remove encoding name. */
            $ar_phrase['id_lang'] = preg_replace('/-([a-z0-9])+$/', '', $ar_phrase['id_lang']);

            $xml .= CRLF . "\t\t" . '<lang xml:lang="'
                . htmlspecialchars($ar_phrase['id_lang'], ENT_QUOTES, 'UTF-8')
                . '">';

            unset($ar_phrase['id_lang']);

            foreach ($ar_phrase as $attr_name => $attr_value) {
                $xml .= CRLF . "\t\t\t" . '<' . $attr_name . '>';
                $xml .= ((string) $attr_value == '') ? '' : $cdata($attr_value);
                $xml .= '</' . $attr_name . '>';
            }

            $xml .= CRLF . "\t\t" . '</lang>';
        }

        $xml .= CRLF . "\t" . '</entry>';
        $xml .= CRLF . '</custom_page>';
    }

    $xml .= CRLF . '</glossword>';

    $filename = 'gw_' . $this->component . '_' . date('Y-m[M]-d', $this->sys['time_now_gmt_unix']) . '.xml';

    if ($is_as_file) {
        /* Send XML as a downloaded file. */
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
            header('Content-Type: application/force-download');
        } else {
            header('Content-Type: application/octet-stream');
        }

        header('Content-Length: ' . strlen($xml));
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        print $xml;
        exit;
    }

    /* Write XML to disk and show the result in admin UI. */
    $mode = 'w';
    $filename = $this->sys['path_export'] . '/' . $filename;

    $this->str .= '<ul class="xt">';
    $this->str .= '<li><span class="gray">';
    $this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';

    $is_write = $this->oFunc->file_put_contents($filename, $xml, $mode);

    $this->str .= $is_write
        ? 'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist(LOCALE_LANG_RULES)) . ' ' . $this->oL->m('bytes') . ')'
        : $this->oL->m('error');
    $this->str .= '</li>';
    $this->str .= '</ul>';
}
