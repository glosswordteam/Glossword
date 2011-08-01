<?php
/**
 *  Glossword Installation 1.8
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
$sys['file_version'] = '$Id: install.php 400 2008-04-06 17:57:36Z yrtimd $';
/* Change current directory to access for website files */
if (!@chdir('..'))
{
    exit("Can't change directory to `..'");
}
define('IN_GW', TRUE);
define('THIS_SCRIPT', 'install.php');
error_reporting(E_ALL);

/* Enter debug mode. None of database queries will be executed */
$sys['is_debug'] = 0;
$sys['path_install'] = 'gw_install';

/* Configuration */
$sys['path_gwlib'] = 'lib';
$sys['path_include'] = 'inc';
$sys['path_include_local'] = 'inc';
$sys['is_prepend'] = 0;
/* Auto time for server  */
$sys['time_now'] = time();
$sys['time_now_gmt_unix'] = $sys['time_now'] - (@date('I') ? (@date('Z') - 3600) : @date('Z'));

include_once('db_config.php');
/* Reset prefix for database tables */
#$sys['tbl_prefix'] = '';
include_once($sys['path_include_local'] . '/config.inc.php');

if (file_exists($sys['path_install'].'/install_functions.php'))
{
	include_once($sys['path_install'].'/install_functions.php');
}
else
{
	printf('<br /><b>Error:</b> File %s required.', $sys['path_install'].'/install_functions.php');
}
include_once($sys['path_include']. '/constants.inc.php');
include_once($sys['path_include']. '/func.sql.inc.php');
include_once($sys['path_include']. '/func.text.inc.php');
include_once($sys['path_include']. '/class.forms.php');
include_once($sys['path_include']. '/class.gwtk.php');
include_once($sys['path_include']. '/func.stat.inc.php');
include_once($sys['path_gwlib']. '/class.timer.php');
include_once($sys['path_gwlib']. '/class.globals.php');
include_once($sys['path_gwlib']. '/class.func.php');
include_once($sys['path_gwlib']. '/class.db.mysql.php');
include_once($sys['path_gwlib']. '/class.db.q.php');
include_once($sys['path_gwlib']. '/class.domxml.php');
include_once($sys['path_gwlib']. '/class.tpl.php');
include_once($sys['path_gwlib']. '/class.html.php');
include_once($sys['path_gwlib']. '/class.session-1.9.php');
include_once($sys['path_install']. '/query_storage.php');

@header('Content-Type: text/html; charset=utf-8');
@header("Expires: " . date("D, d M Y H:i:s", $sys['time_now_gmt_unix']) . " GMT");
@header("Last-Modified: " . date("D, d M Y H:i:s", $sys['time_now_gmt_unix']) . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
@header("Pragma: no-cache");
@set_time_limit(3600);

/* Default settings for Glossword installation */
$sys['user_name'] = 'Dmitry N. Shilnikov';
$sys['user_email'] = 'dev'. '@'.'glossword.info';
$sys['user_login'] = 'admin';
$sys['user_pass'] = '';
$sys['is_mod_rewrite'] = 0;
$sys['visualtheme']  = 'gw_brand';
$sys['version'] = '1.8.7';
/* Load theme colors */
include_once($sys['path_install'].'/template/theme.inc.php');
/* */
$oHtml = new gw_html;
$oHtml->setVar('ar_url_append', $sys['ar_url_append']);
$oHtml->setVar('is_htmlspecialchars', 0);
$oHtml->setVar('is_mod_rewrite', 0);
/* */
$gv = $oGlobals->register(array('is_debug', 'a', 'step', 'arpost', 'id_dict', 'id_user', GW_LANG_I));
$oGlobals->do_default($sys['db_type'], 'mysql410');
$oGlobals->do_default($gv['step'], 0);
$oGlobals->do_default($gv['id_dict'], 0);
$oGlobals->do_default($gv['a'], 'intro');
$oGlobals->do_default($gv['is_debug'], $sys['is_debug']);
$oGlobals->do_default($gv['arpost']['dbhost'], 'localhost');
$oGlobals->do_default($gv['arpost']['dbport'], '');
$oGlobals->do_default($gv['arpost']['dbname'], GW_DB_DATABASE);
$oGlobals->do_default($gv['arpost']['dbprefix'], 'gw_');
$oGlobals->do_default($gv['arpost']['dbuser'], GW_DB_USER);
$oGlobals->do_default($gv['arpost']['dbpass'], GW_DB_PASSWORD);
/* English by default */
$oGlobals->do_default($gv[GW_LANG_I], 'en-utf8');
$oGlobals->do_default($gv['lang_enc'], 'en-utf8');
/* --------------------------------------------------------
 * Translation kit
 * -------------------------------------------------------- */
$gv[GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '', $gv[GW_LANG_I]);
$gv['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $gv['lang_enc']);
$oL = new gwtk;
$oL->setHomeDir($sys['path_locale']);
$oL->setLocale($gv[GW_LANG_I].'-'.$gv['lang_enc']);
$oL->getCustom('tht', $gv[GW_LANG_I].'-'.$gv['lang_enc'], 'join');
$oL->getCustom('err', $gv[GW_LANG_I].'-'.$gv['lang_enc'], 'join');
$oL->getCustom('l_install', $gv[GW_LANG_I].'-'.$gv['lang_enc'], 'join');
/* */
$sys['css_align_right'] = 'right';
$sys['css_align_left'] = 'left';
if ($oL->languagelist('1') == 'rtl')
{
	$sys['css_align_right'] = 'left';
	$sys['css_align_left'] = 'right';
}
/* */
$sys['is_debug'] = $gv['is_debug'];
$sys['html_title'] = sprintf($oL->m('001'), $sys['version']);
$sys['time_refresh'] = 3;
/* --------------------------------------------------------
 * Query storage
 * ----------------------------------------------------- */
$oSqlQ = new $sys['class_queries'];
/* */
function sqlGetQ($keyname)
{
	global $sys, $oSqlQ;

	$args = func_get_args();
	for ($i = 0; $i <= 5; $i++)
	{
		$ar[] = isset($args[$i]) ? $args[$i] : '';
	}
	$oSqlQ->set_suffix('-'.$sys['db_type']);
	return $oSqlQ->getQ($keyname, $ar[1], $ar[2], $ar[3], $ar[4], $ar[5]);
}
/* */
class gw_setup
{
	var $id_dict = 0;
	var $str_step = '';
	var $str_before = '';
	var $str_after = '';
	var $ar_status = '';
	var $str_step_next = '';
	var $cur_funcname = '';
	var $is_error = 0;
	function gw_setup()
	{
		global $oDb, $oFunc, $sys, $gv, $ar_theme, $oL, $oSqlQ, $oHtml;
		$this->oDb = new gwtkDb;
		$this->oDb->on_error_default = ON_ERROR_IGNORE;
		$this->oForm = new gwForms;
		$this->oFunc =& $oFunc;
		$this->oHtml =& $oHtml;
		$this->oSess = new $sys['class_session'];
		  
		$this->oL =& $oL;
		$this->sys =& $sys;
		$this->gv =& $gv;
		$this->oSqlQ =& $oSqlQ;
		$this->ar_theme =& $ar_theme;
		$this->ar_tpl = array('i_header.html');

		include($this->sys['path_install'].'/class.template_plain.php');
		$this->oTpl = new i_template;
		$this->oTpl->init();
		$this->oTpl->assign($this->ar_theme);
	}
	/* */
	function is_locked()
	{
		if ( $this->gv['step'] == 0 && $this->gv['a'] == 'novar')
		{
			return false;
		}
		return file_exists($this->sys['file_lock']);
	}
	/* */
	function get_html_steps_progress($step)
	{
		$ar = array();
		for ($i = 1; $i <= 5; $i++)
		{
			$ar[$i] = ' '. $this->oL->m('1168') . ' ' . $i.'  ';
			if ($step == $i) { $ar[$i] = '<strong class="green">'.$ar[$i].'</strong>'; }
		}
		return '<span class="gray">'.implode('&#x2192;', $ar).'</span>';
	}
	/* */
	function step()
	{
		/* Show 'installation is locked' page */
		if ($this->is_locked())
		{
			$this->ar_status[] = sprintf($this->oL->m('1167'), $this->sys['file_lock']);
			$this->ar_tpl[] = 'i_step.html';
			$this->ar_tpl[] = 'i_footer.html';
			return false;
		}
		else if ($this->gv['step'] > 0)
		{
			/* Show 'steps' for valid installation page only */
			$this->str_step .= $this->get_html_steps_progress($this->gv['step']);
		}
		$oTimer = new gw_timer;
		$this->{$this->cur_funcname}();
		$this->oTpl->a( 'v:runnnig_time', sprintf("%1.5f", $oTimer->end()) );
		$this->ar_tpl[] = 'i_footer.html';
	}
	/* */
	function get_form($step)
	{
	}
	/* */
	function get_form_tr($td1, $td2, $req_msg = '', $broken_msg = '')
	{
		return sprintf('<tr><td class="td1">%s%s</td><td class="td2">%s%s</td></tr>', $td1, $req_msg, $broken_msg, $td2);
	}
	/* */
	function get_form_title($t)
	{
		return '<h3 style="text-align:'.$this->sys['css_align_left'].'">'.$t.'</h3>';
	}
	/* */
	function post_queries($ar_q)
	{
		$this->oDb->query('SET NAMES \'utf8\'');
		for (reset($ar_q); list($k, $v) = each($ar_q);)
		{
			if ($v == ''){ continue; }
			if ($this->sys['is_debug'])
			{
				$this->ar_status[] = htmlspecialchars_ltgt($v);
			}
			elseif (!$this->oDb->query($v))
			{
				$this->ar_status[] = '<span class="red">'.$v.'</span>';
				$this->ar_status[] = $this->oDb->halt($v);
				return false;
			}
		}
		return true;
	}
	/* */
	function step_error($msg_error = '')
	{
		$this->is_error = 1;
		$this->str_after ='';
		$this->str_before = '<div class="errormsg">'.$msg_error.'</div>';
		$this->str_step = '';
		/* Back to previous step */
		$this->gv['step']--;
		$this->cur_funcname = $this->gv['cur_funcname'] = $this->gv['a'].'_step_'.$this->gv['step'];
		$this->step();
		return false;
	}
	/* */
	function output()
	{
		$this->oTpl->a( 'v:language',          $this->oL->languagelist("0") );
		$this->oTpl->a( 'v:text_direction',    $this->oL->languagelist("1") );
		$this->oTpl->a( 'v:charset',           $this->oL->languagelist("2") );
		$this->oTpl->a( 'v:file_version',      $this->sys['file_version'] );
		$this->oTpl->a( 'v:css_align_right',   $this->sys['css_align_right'] );
		$this->oTpl->a( 'v:css_align_left',    $this->sys['css_align_left'] );

		$this->oTpl->a( 'l:web_m_fb', $this->oL->m('web_m_fb'));
		$this->oTpl->a( 'url:to_main', $this->oHtml->a($this->sys['server_dir'].'/'.$this->sys['path_install'].'/'.THIS_SCRIPT, THIS_SCRIPT) );

		$this->oTpl->a( 'v:lang_i_name',       GW_LANG_I );
		$this->oTpl->a( 'v:lang_i_value',      $this->gv[GW_LANG_I] );
		$this->oTpl->a( 'v:glossword_version', $this->sys['version'] );
		/* I request you to retain the copyright notice! */
		$this->oTpl->a( 'v:copyright',         'Powered&#160;by <a href="http://glossword.info/" title="dictionaries, glossaries, references">Glossword</a>&#160;' );

		$str_status = '';

		$this->oTpl->a( 'v:str_step', $this->str_step );

		if (!empty($this->ar_status))
		{
			$str_status .= '<ul class="gwstatus"><li>' . implode('</li><li>', $this->ar_status) . '</li></ul>';
		}
		$this->oTpl->a( 'v:str_before', $this->str_before);
		$this->oTpl->a( 'v:str_status', $str_status);
		$this->oTpl->a( 'v:str_after',  $this->str_after);
		/* */
		$this->oTpl->define($this->ar_tpl);
		$this->oTpl->parse();
		return $this->oTpl->output();
	}
	/**
	 * Meta refresh after submitting a form.
	 * 
	 * @param	string	url to refresh
	 * @return	string	html-code
	 * @requres	$oSess, $sys
	 */
	function gethtml_metarefresh($url)
	{
		return '<meta http-equiv="Refresh" content="'.$this->sys['time_refresh'].';url='.($this->sys['server_url'].'/'.$url).'" />';
	}
}
/* */

/* */
$gv['cur_funcname'] = $gv['a'].'_step_'.$gv['step'];
$gv['cur_filename'] = 'class.'.$gv['a'].'.php';
$gv['cur_classname'] = 'gw_setup_'.$gv['a'];
include($sys['path_install'].'/'.$gv['cur_filename']);
$oSetup = new $gv['cur_classname'];
/* Open step number  */
if (in_array(strtolower($gv['cur_funcname']), get_class_methods($oSetup)))
{
	$oSetup->cur_funcname = $gv['cur_funcname'];
	$oSetup->step();
}
print $oSetup->output();
?>