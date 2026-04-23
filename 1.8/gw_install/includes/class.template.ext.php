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

/**
 * Template engine
 * File-based version. Added support for the group of templates.
 *
 * Requires:
 * - class $o
 */
class tkit_template extends gwv_template
{

    public $path_source;
    public $path_cache;

    /* File-based */
    public function init($id_style)
    {
        $this->id_style = $id_style;
        $this->var_last_parsed = '';
    }

    /* @access public */
    public function addVal($k, $v)
    {
        $this->assign([$k => $v]);
    }

    /* @access public */
    public function getVal($k = '')
    {
        if ($k == '') {
            return $this->pairsV;
        }
        $k = $this->namespace_default . '::' . sprintf("%u", crc32($k));
        return isset($this->pairsV[$k]) ? $this->pairsV[$k] : false;
    }

    public function set_tpl($id_group, $theme_name = '')
    {
        if ($theme_name == '') {
            $theme_name = $this->id_style;
        }
        $arSql = [];
        $ar_files = ['tpl_index_header', 'tpl_index_body', 'tpl_index_footer'];
        switch ($id_group) {
            /* Load the group of html-templates */ case GW2_TPL_WEB_INDEX:
            $ar_files = ['tpl_index_header', 'tpl_index_body', 'tpl_index_footer'];
            break;
            default:
                /* Load one html-template */ $ar_files = [$id_group];
                break;
        }
        /* Load files */
        foreach ($ar_files as $k => $tplname) {
            $arSql[$k]['settings_key'] = $tplname;
            $arSql[$k]['date_modified'] = $arSql[$k]['date_compiled'] = 0;
            $arSql[$k]['code'] = $arSql[$k]['code_i'] = $arSql[$k]['settings_value'] = '';
            /* if compiled html exists */
            if (file_exists($this->path_cache . '/code-' . $tplname . '.php')) {
                $arSql[$k]['date_compiled'] = filemtime($this->path_cache . '/code-' . $tplname . '.php');
                $arSql[$k]['code'] = implode('', file($this->path_cache . '/code-' . $tplname . '.php'));
                $arSql[$k]['code_i'] = implode('', file($this->path_cache . '/code_i-' . $tplname . '.php'));
            }
            if (file_exists($this->path_source . '/' . $tplname . '.html')) {
                $arSql[$k]['date_modified'] = filemtime($this->path_source . '/' . $tplname . '.html');
                /* Load modified contents */
                if ($arSql[$k]['date_compiled'] <= $arSql[$k]['date_modified']) {
                    $arSql[$k]['settings_value'] = implode('', file($this->path_source . '/' . $tplname . '.html'));
                    $arSql[$k]['settings_value'] = str_replace(['{%', '%}'], ['{', '}'], $arSql[$k]['settings_value']);
                }
            } else {
                print '<div>' . $this->path_source . '/' . $tplname . '.html' . '</div>';
            }
        }
        /* */
        foreach ($arSql as $k => $arV) {
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
                eval(' ?' . '>' . $arV['code_i'] . '<?php ');
            } else {
                eval($this->_compile($tkey));
            }
            if (!isset($arBlockI)) {
                $arBlockI = [];
            }
            $this->arBlockI = array_merge($this->arBlockI, $arBlockI);
        }
    }

    public function _file_load($filename, $field = 'html', $id_style = 1)
    {
        return $filename;
    }

    public function _file_save($filename, $str, $mode = '', $id_style = 1)
    {
        global $o;
#		${$o}->ar_file_events[] = $this->path_cache.'/'.$mode.'-'.$filename.'.php';
        $o->oFunc->file_put_contents($this->path_cache . '/' . $mode . '-' . $filename . '.php', $str, 'w');
    }

    public function _compile($tplName)
    {
        $this->oCmd->_reset();
        $tmp = [];
        $tmp['filename_c'] = '';
        $tmp['str_i'] = $tmp['str'] = '';
        $strInternal = '';
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
            $tmp['str_i'] = '<?' . 'php' . CRLF . '$template_timestamp = ' . (time() - 2) . ';' . CRLF . $strInternal . '?' . '>';
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
                print '<table border="1" cellspacing="0"><tr><td>' . $tkey . '</td></tr><tr><td>';
            }
            @eval(' ?' . '>' . $arV['code'] . '<?' . 'php ');
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