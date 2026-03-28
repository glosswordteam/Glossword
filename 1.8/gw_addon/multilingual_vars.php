<?php
if (!defined('IN_GW'))
{
	die("<!-- $Id: multilingual_vars.php,v 1.6 2006/10/06 12:06:09 yrtimd Exp $ -->");
}
/**
 *  Glossword - glossary compiler (http://glossword.info/dev/)
 *  © 2002-2006 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
/* ------------------------------------------------------- */
/**
 * Enables multilingual variables for dictionary pages
 * and for the whole website
 */
/* */
function gw_addon_multilingual_vars_load($filename = '', $obj_tpl)
{
	global $oFunc, $$obj_tpl;
	global $sys, $gw_this, $arDictParam;

	// Read additional template variables
	$oDomCode = new gw_domxml;
	$oDomCode->strData = $oFunc->file_get_contents($filename);
	$oDomCode->parse();
	// start parsing every translation unit, <tu>
	$oEl = $oDomCode->get_elements_by_tagname('tu');
	while (list($elK1, $elV1) = each($oEl))
	{
		if ( isset($elV1['children']) && is_array($elV1['children']) )
		{
			$tmp['xml_varname'] = '';
			for (reset($elV1['children']) ; list($elK2, $elV2) = each($elV1['children']);)
			{
				if (isset($elV2['tag']) && isset($elV2['value']) && ($elV2['tag'] == 'prop'))
				{
					// get variable names
					$tmp['xml_varname'] = $elV2['value'];
				}
				elseif (isset($elV2['attributes']['xml:lang'])
					&& isset($elV2['children'][0]['value'])
					&& $elV2['attributes']['xml:lang'] == $gw_this['vars'][GW_LANG_I]
				)
				{
					// set assigned value
#					$$oTpl->addVal( $tmp['xml_varname'], '<span class="gwvar">'.$elV2['children'][0]['value'].'</span>&#32;');
					$$obj_tpl->addVal( $tmp['xml_varname'], $elV2['children'][0]['value']);
				}
				elseif (isset($elV2['attributes']['xml:lang'])
					&& isset($elV2['children'][0]['value'])
					&& $elV2['attributes']['xml:lang'] == str_replace('-utf8', '', $sys['locale_name'])
				)
				{
					// set default value
					$$obj_tpl->addVal( $tmp['xml_varname'], $elV2['children'][0]['value']);
				}
			} // each setting
		}
	} // while
}
/* */
function gw_addon_multilingual_vars($id_dict = 0, $obj_tpl = 'oTpl')
{
	if ($id_dict > 0)
	{
		$filename = sprintf("gw_xml/multilingual_vars/%d.xml", $id_dict);
		gw_addon_multilingual_vars_load($filename, $obj_tpl);
	}
	$filename = sprintf("gw_xml/multilingual_vars/common.xml", $id_dict);
	gw_addon_multilingual_vars_load($filename, $obj_tpl);

}
/* Load multilingual vars per dictionary */
if (isset($arDictParam['id']) && $arDictParam['id'])
{
	gw_addon_multilingual_vars($arDictParam['id']);
}
/* Allow multilingual_vars in admin */
if (GW_IS_BROWSE_WEB || (GW_IS_BROWSE_ADMIN && ${GW_ACTION} != GW_A_EDIT) )
{
	gw_addon_multilingual_vars();
}

/* end of file */
?>