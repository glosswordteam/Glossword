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
	die('<!-- $Id: topics_import.inc.php 497 2008-06-14 07:15:56Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array();
if ($this->gw_this['vars']['post'] == '')
{
			$arV = array();
			$arV['file_location'] = '';
			$arV['xml'] = '';
			/* Not submitted */
			$this->str .= $this->get_form_import($arV);

			$strHelp = '';
			$strHelp .= '<dl>';
			$strHelp .= '<dt><b>XML</b></dt>';
			$strHelp .= '<dd>' . CRLF.'&lt;'.'?xml version="1.0" encoding="UTF-8"'.'?&gt;'.
						'<br />&lt;glossword&gt;'.
						'<br />&lt;topic id="1"&gt;'.
						'<br />&#160;&lt;parameters&gt;&#8230;&lt;/parameters&gt;'.
						'<br />&#160;&lt;entry&gt;'.
						'<br />&#160;&#160;&lt;lang xml:lang="en"&gt;'.
						'<br />&#160;&#160;&#160;&lt;topic_title&gt;&#8230;&lt;/topic_title&gt;'.
						'<br />&#160;&#160;&#160;&lt;topic_descr&gt;&#8230;&lt;/topic_descr&gt;'.
						'<br />&#160;&#160;&#160;&lt;id_topic_phrase&gt;&#8230;&lt;/id_topic_phrase&gt;'.
						'<br />&#160;&lt;/lang&gt;'.
						'<br />&#160;&lt;/entry&gt;'.
						'<br />&lt;/topic&gt;'.
						'<br />&lt;/glossword&gt;' . '</dd>';
			$strHelp .= '</dl>';
			$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);
}
else
{
	$cnt_topics = 0;

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
	$this->oFunc->file_put_contents($this->sys['path_temporary'].'/t/'.$file_target, '');
	if (is_uploaded_file($xml_file)
		&& move_uploaded_file($xml_file, $this->sys['path_temporary'].'/t/'.$file_target)
		)
	{
		$arPost['xml'] = $this->oFunc->file_get_contents($this->sys['path_temporary'].'/t/'.$file_target);
		/* remove uploaded file */
		unlink($this->sys['path_temporary'].'/t/'.$file_target);
	}
#$this->sys['isDebugQ'] = 1;
	/* Do import using DOM model */
	$oDom = new gw_domxml;
	$oDom->is_skip_white = 0;
	$oDom->strData =& $arPost['xml'];
	$oDom->parse();
	$oDom->strData = '';
	$arXmlLine = $oDom->get_elements_by_tagname('topic');
	$arQ = $q1 = array();
	$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics`';
	$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase`';
	/* */
	$is_error_xml = 1;
	$this->str .= '<ul class="xt">';
	for (; list($k1, $v1) = each($arXmlLine);)
	{
		/* per each topic */
		if (!isset($v1['children'])) { continue; }
		$id_topic = $oDom->get_attribute('id', $v1['tag'], $v1);
		/* <entry> */
		for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
		{
			if (!is_array($v2)){ continue; }
			switch($v2['tag'])
			{
				case 'parameters':
					$q2 = array();
					$q1 = unserialize($oDom->get_content($v2));
					$q1['id_topic'] = $q2['id_topic'] = $id_topic;
				break;
				case 'entry':
					if (!isset($v2['children'])) { continue; }
					for (reset($v2['children']); list($k3, $v3) = each($v2['children']);)
					{
						$id_lang = $oDom->get_attribute('xml:lang', 'lang', $v3);
						/* for each element */
						if (!is_array($v3) || !isset($v3['children'])) { continue; }
						for (reset($v3['children']); list($k4, $v4) = each($v3['children']);)
						{
							if (trim($v4['tag']) == ''){ continue; }
							$q2[$v4['tag']] = $v4['value'];
						}
						$q2['id_lang'] = $id_lang.'-'.$this->gw_this['vars']['lang_enc'];
						$arQ[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'topics_phrase');
					}
					$is_error_xml = 0;
				break;
			}
		}
		/* Set the current modification and creation dates */
		if (!isset($q1['date_created']))
		{
			$q1['date_created'] = $q1['date_modified'] = $this->sys['time_now_gmt_unix'];
		}
		/* 1.8.7: Old files didn't have User ID */
		if (!isset($q1['id_user']))
		{
			$q1['id_user'] = $this->oSess->id_user;
		}
		$arQ[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'topics');
		$cnt_topics++;
	}
	$this->str .= '</ul>';
	/* Check for errors in XML */
	if ($is_error_xml)
	{
		$this->str .= '<ul class="xt"><li>'.$this->oL->m('reason_9').'</li>';
		$this->str .= '<li>'.$oDom->msg_error.'</li>';
		$this->str .= '</ul>';
		return;
	}
	/* */
	if (!$cnt_topics)
	{
		$arPost['file_location'] = '';
		$this->str .= $this->get_form_import($arPost);
		return;
	}
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.$this->component.'&note_afterpost='.$this->oL->m('1333').': '.$cnt_topics, $this->sys['isDebugQ'], 0);
}

?>