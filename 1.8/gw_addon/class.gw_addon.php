<?php
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
	Interface to addons.
	Example:
	/glossword/gw_addon/gw_addon_name/index.php
	
	include($sys['path_addon'].'/class.gw_addon.php');
	class gw_addon_name extends gw_addon
	{
		function gw_addon_name()
		{
			$this->init();
		}
	}
	
 */
if (!defined('IS_CLASS_ADDON'))
{
	define('IS_CLASS_ADDON', 1);
class gw_addon
{
	var $str;
	var $cfg;
	function init()
	{
		global $oSess, $oDb, $oSqlQ, $oL, $oHtml, $oFunc, $oTpl, $oCase;
		global $sys, $gw_this, $ar_theme, $arDictParam;
		$this->oSess =& $oSess;
		$this->oFunc =& $oFunc;
		$this->oDb =& $oDb;
		$this->oSqlQ =& $oSqlQ;
		$this->oCase =& $oCase;
		$this->oL =& $oL;
		$this->oTpl =& $oTpl;
		$this->oHtml =& $oHtml;
		$this->gw_this =& $gw_this;
		$this->sys =& $sys;
		$this->ar_theme =& $ar_theme;
		$this->arDictParam =& $arDictParam;
	}
	function init_m()
	{
		global $oDb, $oSqlQ, $oFunc;
		global $sys, $gw_this, $arDictParam;
		$this->oFunc =& $oFunc;
		$this->oDb =& $oDb;
		$this->oSqlQ =& $oSqlQ;
		$this->gw_this =& $gw_this;
		$this->sys =& $sys;
		$this->arDictParam =& $arDictParam;
	}
}
}
/* */
$oSqlQ->setAddonQ(array($gw_this['vars'][GW_TARGET]));
/* end of file s*/
?>