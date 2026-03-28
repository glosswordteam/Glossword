<?php
if (!defined('IN_GW'))
{
	die("<!-- $Id: fields_extension.php,v 1.1 2006/10/20 13:33:24 yrtimd Exp $ -->");
}
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2006 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
// --------------------------------------------------------
/**
 * Enables exended optiions for dictionary elements.
 * (transcription, abbreviation, translation etc.)
 */
//
class gw_fields_extension
{
	var $curElementId = '';

	function gw_fields_extension($field_type)
	{

	}
	function get_js($fieldname, $id_element)
	{
	 	$tmp['strJs'] = '<script type="text/javascript">/*<![CDATA[*/';
	 	$tmp['strJs'] .= '

function dummy(parameter)
{
	var n = "value\'s";
}

';
		$tmp['strJs'] .= '/*]]>*/</script>';
		return $tmp['strJs'];
	}

	function get_html($fieldname, $id_element)
	{
		global $oHtml;
		$tmp['strHtml'] = '';
		/* Extended code */
		$tmp['strHtml'] .= '';

		return $tmp['strHtml'];
	}
}
/* end of file */
?>