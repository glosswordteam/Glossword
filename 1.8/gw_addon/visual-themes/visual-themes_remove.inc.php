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
	die('<!-- $Id: visual-themes_remove.inc.php 477 2008-05-28 20:31:16Z glossword_team $ -->');
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

$arQ = array();
if ($this->gw_this['vars']['w2'])
{
	/* Remove template from the group of templates */
	$arQ[] = sprintf('DELETE FROM `%s'.'theme_settings` WHERE settings_key = "%s"', $this->sys['tbl_prefix'], gw_text_sql($this->gw_this['vars']['w2']));
	$arQ[] = sprintf('DELETE FROM `%s'.'theme_group` WHERE settings_key = "%s" AND id_group = "%d"', $this->sys['tbl_prefix'], gw_text_sql($this->gw_this['vars']['w2']), gw_text_sql($this->gw_this['vars']['w1']));
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_EDIT .'&'. GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&tid='.$this->gw_this['vars']['tid'].'&w1='.$this->gw_this['vars']['w1'], $this->sys['isDebugQ'], 0);
}
else
{
	/*
	1. Delete theme from the list of themes.
	2. Delete theme settings.
	3. Change theme for dictionaries to default theme.
	*/
	$arQ[] = sprintf('DELETE FROM `%s'.'theme` WHERE id_theme = "%s"', $this->sys['tbl_prefix'], gw_text_sql($this->gw_this['vars']['tid']));
	$arQ[] = sprintf('DELETE FROM `%s'.'theme_settings` WHERE id_theme = "%s"', $this->sys['tbl_prefix'], gw_text_sql($this->gw_this['vars']['tid']));
	$arQ[] = sprintf('UPDATE `%s'.'dict` SET visualtheme = "%s" WHERE visualtheme = "%s"', $this->sys['tbl_prefix'], gw_text_sql($this->sys['visualtheme']), gw_text_sql($this->gw_this['vars']['tid']));
	$arQ[] = 'CHECK TABLE `'.$this->sys['tbl_prefix'].'theme_settings`';
	$path_template = $this->sys['path_temporary'].'/t/'.$this->gw_this['vars']['tid'];
	$ar_files = file_readDirF($path_template, '//');
	for (; list($k, $filename) = each($ar_files);)
	{
		@chmod ($path_template.'/'.$filename, 0777);
		@unlink($path_template.'/'.$filename);
	}
	@chmod($path_template, 0777);
	@rmdir($path_template);
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
}


?>