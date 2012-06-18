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
	die('<!-- $Id: terms_remove.inc.php 500 2008-06-15 23:38:18Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$ar_q = array();
if (!$this->gw_this['vars']['isConfirm'])
{
	/* Should be confirmed */
	return;
}
/* Enter debug mode */
#$this->sys['isDebugQ'] = 1;

global $arDictParam;

$arPost =& $this->gw_this['vars']['arPost'];
/*
 1) Delete term from dictionary, $DBTABLE
 2) Delete term from assiged keywords, TBL_WORDMAP
 3) Delete term from assiged terms to user, TBL_MAP_USER_TERM
 4) Update dictionary settings, table TBL_DICT
 5) Check for permissions
*/

/* Check for permission */
$sql_where = ($this->oSess->is('is-terms') ? '' : ' AND `id_user` = "'.$this->oSess->user_get('id_user').'"');

#prn_r( $this->gw_this['vars']['arPost'] );

if (isset($this->gw_this['vars']['arPost']['is_all']))
{
	if (isset($this->gw_this['vars']['arPost']['is_save_history']))
	{
		/* Mark as deleted */
		$ar_query[] = 'UPDATE `' . $arDictParam['tablename']. '` SET is_active = "3" WHERE 1=1'.$sql_where;
		/* Place into the schedule for removing, change User ID */
		$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET is_active = "3" WHERE 1=1'.$sql_where;
	}
	else
	{
		/* Remove now */
		$ar_query[] = 'DELETE FROM `' . $arDictParam['tablename']. '` WHERE 1=1'.$sql_where;
		$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'history_terms` WHERE `id_dict` = "'.$arDictParam['id'].'"'.$sql_where;
	}
}
else
{
	$ar_query = array();
	if (!isset($this->gw_this['vars']['arPost']['ar_id']))
	{
		$this->gw_this['vars']['arPost']['ar_id'] = array($this->gw_this['vars']['tid']);
	}
	for (reset($this->gw_this['vars']['arPost']['ar_id']); list($k1, $id_term) = each($this->gw_this['vars']['arPost']['ar_id']);)
	{
		if (isset($this->gw_this['vars']['arPost']['is_save_history']))
		{
			$ar_query[] = 'UPDATE `' . $arDictParam['tablename']. '` SET is_active = "3" WHERE `id` = "' . $id_term . '"'.$sql_where;
			/* See `maintenance_clear_history_terms.php` for the procedure of removing old terms */
			/* -- History of changes -- */
			/* Select History ID for the current term. Latest modification date. */
			$arCurrent = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-by-term_id', $id_term, 'LIMIT 1'));
			if (!empty($arCurrent))
			{
				$arCurrent = $arCurrent[0];
				/* Place into the schedule for removing, change User ID */
				$ar_query[] = 'UPDATE `'.$this->sys['tbl_prefix'].'history_terms` SET is_active = "3", id_user = "'.$this->oSess->user_get('id_user').'" WHERE id = "' . $arCurrent['id'] . '"'.$sql_where;
			}
		}
		else
		{
			/* Remove now */
			$ar_query[] = 'DELETE FROM `' . $arDictParam['tablename']. '` WHERE `id` = "' . $id_term . '"'.$sql_where;
			$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'history_terms` WHERE `id_term` = "' . $id_term. '"'.$sql_where;
		}
	}
}
$qDict['date_modified'] = $this->sys['time_now_gmt_unix'];
$where = 'id = "' . gw_text_sql($this->gw_this['vars']['id']) . '"';
$ar_query[] = gw_sql_update($qDict, $this->sys['tbl_prefix'].'dict', $where);
/* Clear cache */
$this->str .= gw_tmp_clear($this->gw_this['vars']['id']);
/* Redirect to... */
$str_url = gw_after_redirect_url($arPost['after']);
$this->str .= postQuery($ar_query, $str_url, $this->sys['isDebugQ'], 0);

?>