<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: settings_maintenance_7.inc.php 425 2008-04-23 09:13:06Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */



/* Script variables below */

/* Script functions below */
function gw_get_html_code()
{
	global $oFunc, $sys, $oL, $gw_this;
	$arDictMap = array();
	for (reset($gw_this['ar_dict_list']); list($arK, $arV) = each($gw_this['ar_dict_list']);)
	{
		$arDictMap[$arV['id']] = strip_tags($arV['title']);
	}
	asort($arDictMap);
	$arDictMap = array_merge_clobber(array(0 => $oL->m('srch_all')), $arDictMap);

	$str1 = $oFunc->file_get_contents($sys['path_tpl'].'/common/search_form_1.html');
	$str1 .= '<form id="w" name="w"><textarea onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'search_form_default\'].select();}" style="border:1px solid #BCC8E2;overflow:auto;width:97%;color:#777;background:#FFF;font:70% verdana,arial,sans-serif" id="search_form_default" cols="70" rows="10">'.
			htmlspecialchars($str1).
			'</textarea></form>';

	$str2 = $oFunc->file_get_contents($sys['path_tpl'].'/common/search_form_2.html');
	$str2 .= '<form id="w" name="w"><textarea onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'search_form_default\'].select();}" style="border:1px solid #BCC8E2;overflow:auto;width:97%;color:#777;background:#FFF;font:70% verdana,arial,sans-serif" id="search_form_default" cols="70" rows="10">'.
			htmlspecialchars($str2).
			'</textarea></form>';

	$str3 = $oFunc->file_get_contents($sys['path_tpl'].'/common/search_form_3.html');
	$str3 .= '<form id="w" name="w"><textarea onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'search_form_default\'].select();}" style="border:1px solid #BCC8E2;overflow:auto;width:97%;color:#777;background:#FFF;font:70% verdana,arial,sans-serif" id="search_form_default" cols="70" rows="10">'.
			htmlspecialchars($str3).
			'</textarea></form>';

	$str = $str1.$str2.$str3;
	$ar_vars_1 = array(
		'{v:form_action}',
		'{v:search_this_site}',
		'{v:this_site}',
		'{l:search_submit}',
		'{block:SearchSelect}'
	);
	$ar_vars_2 = array(
		$sys['server_proto'].$sys['server_host'].$sys['page_index'],
		$oL->m('3_srch_submit').' <a onclick="window.open(this.href);return false" href="'.$sys['server_proto'].$sys['server_host'].$sys['page_index'].'">'.$sys['site_name'].'</a>',
		'<a onclick="window.open(this.href);return false" href="'.$sys['server_proto'].$sys['server_host'].$sys['page_index'].'">'.$sys['site_name'].'</a>',
		$oL->m('3_srch_submit'),
		htmlFormsSelect($arDictMap, 0, "d", '', 'width:16em', $oL->languagelist("1"))
	);
	$str = str_replace($ar_vars_1, $ar_vars_2, $str);
	$str = preg_replace("/(\r\n|\r|\n|\t)/s", '', $str);

	return $str;
}
/* Script action below */
$this->str .= getFormTitleNav($this->oL->m(1007));

$this->str .= '<div>';
$this->str .= gw_get_html_code();
$this->str .= '</div>';

?>