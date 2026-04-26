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
 * Enables multilingual variables for dictionary pages
 * and for the whole website
 */
/* */
function gw_addon_multilingual_vars_load($filename, $obj_tpl)
{
    global $oFunc, $sys, $gw_this, $arDictParam;
    global $$obj_tpl;

    $tpl = $$obj_tpl;

    $oDomCode = new gw_domxml();
    $oDomCode->strData = $oFunc->file_get_contents($filename);
    $oDomCode->parse();

    // Compute once outside the loop
    $targetLang = $gw_this['vars'][GW_LANG_I];
    $localeName = str_replace('-utf8', '', $sys['locale_name']);

    foreach ($oDomCode->get_elements_by_tagname('tu') as $tu) {
        if (empty($tu['children']) || !is_array($tu['children'])) {
            continue;
        }

        $varname = '';
        $targetValue = null;
        $defaultValue = null;

        foreach ($tu['children'] as $child) {
            $tag = isset($child['tag']) ? $child['tag'] : '';
            $value = isset($child['value']) ? $child['value'] : '';
            $lang = isset($child['attributes']['xml:lang']) ? $child['attributes']['xml:lang'] : '';
            $segValue = isset($child['children'][0]['value']) ? $child['children'][0]['value'] : null;

            if ($tag === 'prop' && $value !== '') {
                $varname = $value;
            } elseif ($segValue !== null) {
                // Exact language match takes priority over locale fallback
                if ($lang === $targetLang) {
                    $targetValue = $segValue;
                } elseif ($lang === $localeName && $targetValue === null) {
                    $defaultValue = $segValue;
                }
            }
        }

        if ($varname !== '') {
            $result = $targetValue !== null ? $targetValue : $defaultValue;
            if ($result !== null) {
                $tpl->addVal($varname, $result);
            }
        }
    }
}

/* */
function gw_addon_multilingual_vars($id_dict = 0, $obj_tpl = 'oTpl')
{
    if ($id_dict > 0) {
        $filename = sprintf("gw_xml/multilingual_vars/%d.xml", $id_dict);
        gw_addon_multilingual_vars_load($filename, $obj_tpl);
    }
    $filename = sprintf("gw_xml/multilingual_vars/common.xml", $id_dict);
    gw_addon_multilingual_vars_load($filename, $obj_tpl);
}

/* Load multilingual vars per dictionary */
if (isset($arDictParam['id']) && $arDictParam['id']) {
    gw_addon_multilingual_vars($arDictParam['id']);
}
/* Allow multilingual_vars in admin */
if (GW_IS_BROWSE_WEB || (GW_IS_BROWSE_ADMIN && ${GW_ACTION} != GW_A_EDIT)) {
    gw_addon_multilingual_vars();
}
