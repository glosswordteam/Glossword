<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id$ -->');
}
/* Included from $oAddonAdm->alpha(); */

$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:'.$this->left_td_width.';background:'.$this->ar_theme['color_2'].';vertical-align:top">';

$this->str .= '<h3>'.$this->oL->m('2_page_custom_az_browse').'</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';

/* */
$this->str .= $this->_get_nav();

/* Prevent import into UTF-8 profile */
if ($this->gw_this['vars']['tid'] == 1)
{
	$this->gw_this['vars']['tid'] = 0;
}
$ar_req_fields = array();
if ($this->gw_this['vars']['post'] == '')
{
	/* Profile */
	$arV = array();
	$arV['file_location'] = '';
	$arV['is_overwrite'] = 0;
	$arV['xml'] = '';
	$this->str .= $this->get_form_import($arV, 0, 0, $ar_req_fields);

	$strHelp = '';
	$strHelp .= '<dl>';
	$strHelp .= '<dt><strong>XML</strong></dt>';
	$strHelp .= '<dd>' . CRLF.'&lt;'.'?xml version="1.0" encoding="UTF-8"'.'?&gt;'.
					'<br />&lt;glossword&gt;'.
					'<br />&lt;custom_az profile_name="A profile" is_active="1"&gt;'.
					'<br />&#160;&lt;entry&gt;'.
					'<br />&#160;&#160;&#160;&lt;az_value&gt;&lt;![CDATA[&#8230;]]&gt;&lt;/az_value&gt;'.
					'<br />&#160;&#160;&#160;&lt;az_value_lc&gt;&lt;![CDATA[&#8230;]]&gt;&lt;/az_value_lc&gt;'.
					'<br />&#160;&#160;&#160;&lt;int_sort&gt;&lt;![CDATA[&#8230;]]&gt;&lt;/int_sort&gt;'.
					'<br />&#160;&lt;/entry&gt;'.
					'<br />&lt;/custom_az&gt;'.
					'<br />&lt;/glossword&gt;' . '</dd>';
	$strHelp .= '</dl>';
	$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);
	
}
else
{
	$file_location = array('name' => '');
	if (isset($this->gw_this['vars']['_files']['file_location']))
	{
		$file_location = $this->gw_this['vars']['_files']['file_location'];
	}

	$arPost =& $this->gw_this['vars']['arPost'];
	/* */
	$xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
	$file_target = urlencode(time().'_'.$file_location['name']);
	/* Create directory */
	$this->oFunc->file_put_contents($this->sys['path_temporary'].'/'.$file_target, '');
	if (is_uploaded_file($xml_file)
		&& move_uploaded_file($xml_file, $this->sys['path_temporary'].'/'.$file_target)
		)
	{
		$arPost['xml'] = $this->oFunc->file_get_contents($this->sys['path_temporary'].'/'.$file_target);
		/* remove uploaded file */
		unlink($this->sys['path_temporary'].'/'.$file_target);
	}
	$arQ = array();
	/* Profile ID */
	if ($arPost['id_profile'])
	{
		/* -- Use an existent ID -- */
		$id_profile = $arPost['id_profile'];
		$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'custom_az` WHERE `id_profile` = "'.$id_profile.'"';
	}
	else
	{
		/* -- Create a new -- */
		$id_profile = $this->oDb->MaxId($this->sys['tbl_prefix'].'custom_az_profiles', 'id_profile');
	}
	/* Debug for import */
#$this->sys['isDebugQ'] = 1;
	/* Do import using DOM model */
	$oDom = new gw_domxml;
	$oDom->is_skip_white = 0;
	$oDom->strData =& $arPost['xml'];
	$oDom->parse();
	$oDom->strData = '';
	$arXmlLine = $oDom->get_elements_by_tagname('custom_az');
	/* */
	$is_error_xml = 1;
	$this->str .= '<ul class="xt">';
	/* one loop */
	for (; list($k1, $v1) = each($arXmlLine);)
	{
		$q2 = array();
		if (!isset($v1['children'])) { continue; }
		$q1['is_active'] = $oDom->get_attribute( 'is_active', 'custom_az', $v1 );
		$q1['profile_name'] = $oDom->get_attribute( 'profile_name', 'custom_az', $v1 );
		$q2['id_profile'] = $q1['id_profile'] = $id_profile;
		if ($arPost['id_profile'])
		{
			/* -- Overwrite -- */
			$arQ[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'custom_az_profiles', 'id_profile = "'.$id_profile.'"');
		}
		else
		{
			/* -- Create a new -- */
			$arQ[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'custom_az_profiles');
		}
		$this->str .= '<li>'.$q1['profile_name'].'</li>';
		$is_error_xml = 0;
		/* for each <entry> */
		for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
		{
			if (!is_array($v2)){ continue; }
			switch ($v2['tag'])
			{
				case 'entry':
					for (reset($v2['children']); list($k3, $v3) = each($v2['children']);)
					{
						if (!is_array($v3)){ continue; }
						$q2[$v3['tag']] = $v3['value'];
					}
					$q2['az_int'] = text_str2ord($q2['az_value']);
					settype($q2['int_sort'], 'integer');
					$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'custom_az');
				break;
			}
		}
	}
	/* Check for errors in XML */
	if ($is_error_xml)
	{
		$this->str .= '<li>'.$this->oL->m('reason_9').'</li>';
		$this->str .= '<li>'.$oDom->msg_error.'</li>';
		$this->str .= '</ul>';
		$this->str .= '</td></tr></tbody></table>';
		return;
	}
	$this->str .= '</ul>';
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE . '&'.GW_TARGET.'='.$this->component . '&tid='.$id_profile, $this->sys['isDebugQ'], 0);
}
$this->str .= '</td></tr></tbody></table>';

?>