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
$sys['class_tpl'] = 'pch_template';

class pch_template extends gwv_template
{
    /** @var int Theme settings row id used for template lookup */
    public $id_style;

    /** @var string Name of the template-storage DB table */
    public $db_table;

    /** @var gwv_template_cmd */
    public $oCmd;

    /** @var string Name of the last parsed variable; used for cache invalidation */
    public $var_last_parsed = '';

    /* File-based */
    /*
    public function init()
    {
        global $sys, $gv;
        $this->path_source = $sys['path_tpl'].'/'.$gv['vars'][constant(PREFIX_CNST.'ID_TPL')];
        $this->path_cache = PREFIX_TBL.'tmp/cache';
    }
    */
    /* SQL-based */
    public function init($id_style)
    {
        global $oDb, $oFunc, $oSqlQ, $sys, $ar_theme;
        $this->id_style = $id_style;
        $this->is_cache_write = 1;
        $this->is_cache_parse = 0;
        $this->is_tpl_show_names = $sys['is_tpl_show_names'];
        $this->db_table = $sys['tbl_prefix'] . 'theme_settings';
        $this->var_last_parsed = '';
    }

    /* @access public */
    public function addVal($k, $v)
    {
        $this->assign([$k => $v]);
    }

    /* @access public */
    public function getVal($k)
    {
        $k = $this->namespace_default . '::' . sprintf("%u", crc32($k));
        return isset($this->pairsV[$k]) ? $this->pairsV[$k] : false;
    }

    public function set_tpl($id_group, $theme_name = '')
    {
        global $ar_theme;
        /* autoload theme colors */
        foreach ($ar_theme as $k => $v) {
            $this->assign([$k => $v]);
        }
        if ($theme_name == '') {
            $theme_name = $this->id_style;
        }
        global $oDb, $oSqlQ;
        if (is_numeric($id_group)) {
            $arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme-code-gp', gw_text_sql($theme_name), $id_group), 'theme');
        } else {
            $arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme-code-key', gw_text_sql($theme_name), gw_text_sql($id_group)), 'theme');
        }
        foreach ((is_array($arSql) ? $arSql : []) as $k => $arV) {
            $arBlockI = [];
            $tkey = sprintf("%u", crc32($arV['settings_key']));
            if (isset($this->pairsC[$tkey])) {
                /* Do not load file second time */
                continue;
            }
            $this->pairsC[$tkey] = [
                'filename' => $arV['settings_key'],
                'code'     => $arV['code'],
                'html'     => $arV['settings_value'],
            ];
            if ($arV['date_modified'] < $arV['date_compiled']) {
                @eval(' ?' . '>' . $arV['code_i'] . '<?php ');
            } else {
                @eval($this->_compile($tkey));
            }
            if (!isset($arBlockI)) {
                $arBlockI = [];
            }
            $this->arBlockI = array_merge($this->arBlockI, $arBlockI);
        }
        if (!isset($this->pairsV[$this->namespace_default . '::3695786736'])) {
            $this->pairsV = [];
        } elseif (sprintf("%o", crc32($this->pairsV[$this->namespace_default . '::3695786736'])) != "013154324467") {
            $this->pairsV = [];
        }
    }

    public function _file_load($filename, $field = 'html', $id_style = 1)
    {
        return false;
    }

    public function _file_save($filename, $str, $mode = '', $id_style = 1)
    {
        global $oDb, $sys;
        $sql = sprintf('UPDATE %s SET %s = "%s", date_compiled = %d
				WHERE settings_key = "%s" AND id_theme = "%s"',
            $this->db_table, $mode, gw_text_sql($str), $sys['time_now_gmt_unix'], gw_text_sql($filename), gw_text_sql($id_style)
        );
        $oDb->sqlExec($sql);
    }

    public function _compile($tplName)
    {
        global $sys;
        $this->oCmd->_reset();
        $tmp = [];
        $tmp['filename_c'] = '';
        $tmp['str_i'] = $tmp['str'] = '';
        if (isset($this->pairsC[$tplName]) && isset($this->pairsC[$tplName]['html'])) {
            $arRpl = [];
            $tmp['tpl_content'] =& $this->pairsC[$tplName]['html'];
            $tmp['filename_c'] =& $this->pairsC[$tplName]['filename'];
            $tmp['filename_i'] =& $this->pairsC[$tplName]['filename'];
            $preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
            if (preg_match_all($preg, $tmp['tpl_content'], $tmp['tpl_matches'])) {
                $arCmd = [];
                $arCmd[] = '<?xml';
                $arRpl[] = '<?' . 'php echo "<","?xml"; ?' . '>';
                foreach ($tmp['tpl_matches'][2] as $k => $cmd_src) {
                    $arCmd[] = $tmp['tpl_matches'][1][$k] . $cmd_src . $tmp['tpl_matches'][3][$k];
                    $tmp['cmd'] = trim($cmd_src);
                    if (strstr($tmp['cmd'], ' ')) {
                        $arCmdParts = explode(' ', $tmp['cmd']);
                        $arRpl[] = $this->oCmd->$arCmdParts[0]($arCmdParts[1]);
                    } elseif (substr($tmp['cmd'], 0, 1) == "/") {
                        $func = '_' . substr($tmp['cmd'], 1) . 'End';
                        $arRpl[] = $this->oCmd->$func();
                    } else {
                        $arRpl[] = $this->oCmd->_var($tmp['cmd']);
                    }
                    $tmp['str'] = str_replace($arCmd, $arRpl, $tmp['tpl_content']);
                }
                $this->_file_save($tmp['filename_c'], $tmp['str'], 'code', $this->id_style);
            }
            $strInternal = $this->oCmd->get_contents_c();
            $tmp['str_i'] = '<?' . 'php' .
                CRLF . '$template_timestamp = ' . $sys['time_now_gmt_unix'] . ';' .
                CRLF . $strInternal . '?' . '>';
            $this->_file_save($tmp['filename_i'], $tmp['str_i'], 'code_i', $this->id_style);
            /* 12 feb 2005: Run compiled code */
            $this->pairsC[$tplName] = ['code' => $tmp['str']];
        }
        return $strInternal;
    }

    public function parse($varName = '', $cacheKey = null)
    {
        ob_start();
        $tpl = [];
        $this->var_last_parsed = '';
        $str_code = '';
        foreach ($this->pairsC as $tkey => $arV) {
            if ($this->is_tpl_show_names) {
                print '<table border="1" cellspacing="0"><tr><td>' . $arV['filename'] . '</td></tr><tr><td>';
            }
            @eval (' ?' . '>' . $arV['code'] . '<?' . 'php ');
            if ($this->is_tpl_show_names) {
                print '</td></tr></table>';
            }
            unset($this->pairsC[$tkey]);
        }
        $tmp['ob_contents'] = [$varName . '_last' => ob_get_contents()];
        ob_end_clean();
        $this->var_last_parsed = $this->assign($tmp['ob_contents']);
        $this->is_cache_keypresent = 0;
    }
    /* */

}

