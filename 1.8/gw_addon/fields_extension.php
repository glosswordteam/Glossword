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
// --------------------------------------------------------
/**
 * Enables exended optiions for dictionary elements.
 * (transcription, abbreviation, translation etc.)
 */
//
class gw_fields_extension
{
	var $curElementId = '';

	function __construct($field_type)
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
