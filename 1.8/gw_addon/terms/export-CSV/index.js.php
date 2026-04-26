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

$strForm .= '<script type="text/javascript">/*<![CDATA[*/';
$strForm .= '
function selection() {
    if ("Text" == document.selection.type)
    {
        var tr = document.selection.createRange();
        tr.text = tr.text;
        tr.select();
    }
}
function checkSplit() {
//    selection();
	gw_getElementById(\'arPost_split2_\').style.border = "solid 1px ' . $ar_theme['color_7'] . '";
	gw_getElementById(\'arPost_split2_\').style.color = "' . $ar_theme['color_black'] . '";
    if (gw_getElementById(\'split_list1\').checked)
    {
		gw_getElementById(\'arPost_split1_\').disabled = false;
		gw_getElementById(\'arPost_split2_\').style.border = "solid 1px #CCC";
		gw_getElementById(\'arPost_split2_\').style.color = "#999";
		gw_getElementById(\'arPost_split2_\').disabled = true;
        gw_getElementById(\'labelCustom\').className = "gray";
        gw_getElementById(\'labelList\').className = "";
    }
    else
    {
        gw_getElementById(\'labelCustom\').className = "";
        gw_getElementById(\'labelList\').className = "gray";
		gw_getElementById(\'arPost_split1_\').disabled = true;
		gw_getElementById(\'arPost_split2_\').disabled = false; 
		gw_getElementById(\'arPost_split2_\').style.border = "solid 1px ' . $ar_theme['color_7'] . '";
		gw_getElementById(\'arPost_split2_\').style.color = "' . $ar_theme['color_black'] . '";
		/* create selection */
        if ((gwDOMtype != "") || typeof(slct) == \'undefined\')
        {
            slct = 1;
            el_option = gw_getElementById(\'arPost_split2_\');
            el_option.focus();
            el_option.select();
        }
    }
}
function setCheckboxesSQL(is_check) {
	var ch1 = gw_getElementById(\'arPost_is_dictdescr_\');
	var ch2 = gw_getElementById(\'arPost_is_dictstats_\');	
	var ch3 = gw_getElementById(\'arPost_is_droptable_\');	
	var ch4 = gw_getElementById(\'arPost_is_keywords_\');
	ch1.checked = ch2.checked = ch3.checked = ch4.checked = is_check;
}
checkSplit();
';
$strForm .= '/*]]>*/</script>';
