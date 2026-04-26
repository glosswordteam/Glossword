<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * � 2008-2026 Glossword.biz team <team at glossword dot biz>
 * � 2002-2008 Dmitry N. Shilnikov
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
 *  Javascript functions for Import.
 */

$strForm .= '<script type="text/javascript">/*<![CDATA[*/';
$strForm .= 'var is_xslt = '.intval(function_exists('xslt_create')).';';
$strForm .= '
function checkFormat() {

	if (gw_getElementById(\'arPost_format_xml\').checked)
	{
		gw_getElementById(\'table_xml\').style.display = "block";
		gw_getElementById(\'arPost_xml_\').disabled = false;
		gw_getElementById(\'arPost_xml_\').style.overflow = "scroll";
		gw_getElementById(\'file_location_xml\').disabled = false;
		if (is_xslt) {
			gw_getElementById(\'arPost_is_validate_\').disabled = false;
		}
		gw_getElementById(\'table_csv\').style.display = "none";
		gw_getElementById(\'arPost_csv_\').disabled = true;
		gw_getElementById(\'arPost_csv_\').style.overflow = "auto";
		gw_getElementById(\'file_location_csv\').disabled = true;
		gw_getElementById(\'arPost_is_read_first_\').disabled = true;
	}
	else
	{
		gw_getElementById(\'table_xml\').style.display = "none";
		gw_getElementById(\'arPost_xml_\').disabled = true;
		gw_getElementById(\'arPost_xml_\').style.overflow = "auto";
		gw_getElementById(\'file_location_xml\').disabled = true;
		if (is_xslt) {
			gw_getElementById(\'arPost_is_validate_\').disabled = true;
		}
		gw_getElementById(\'table_csv\').style.display = "block";
		gw_getElementById(\'arPost_csv_\').disabled = false;
		gw_getElementById(\'arPost_csv_\').style.overflow = "scroll";
		gw_getElementById(\'file_location_csv\').disabled = false;
		gw_getElementById(\'arPost_is_read_first_\').disabled = false;
	}
	
}
checkFormat();
';
$strForm .= ' /*]]>*/</script>';
