<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: custom_vars.php 42 2007-05-26 11:23:42Z yrtimd $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* ------------------------------------------------------- */
/**
 *  Any custom variables for Glossword templates can be added here
 */
/* ------------------------------------------------------- */
/**
	Recommendations
	===============

	How to register variable

	1. Register variable.
		Glossword versions 1.6 or below:
		$oTpl->addVal( 'my:variable', $my['variable_name'] );
		$oTpl->addVal( 'my:variable1', $oFunc->get_file_contents('file.txt') );
		$oTpl->addVal( 'my:variable2', $oFunc->file_exe_contents('file.php') );

		Glossword versions 1.7 or above (planned):
		$oTpl->assign( array('my:variable' => $my['variable_name']) );

	2. Add variable name into HTML-template.

			<b>{my:variable}</b>

		Note: `:' is not required, but Glossword template engine
		will support namespaces, so if you start to use this syntax today,
		it will be faster to update to future Glossword versions.

	Keep all your variables in $custom array as possible. For example, use
		$custom['a'] = 1;
	instead of
		$a = 1;

*/
/* Some variables to integrate with a website */
$sys['path_img_www'] = 'www_img';
$sys['path_img_www_ph'] = 'www_img';
$sys['path_inc_www'] = 'www_inc';
$sys['path_index_full'] = $sys['server_proto'].$sys['server_host'].$sys['server_dir'].'/index.php';



/* ------------------------------------------------------- */
// Load custom phrases.
// To load custom language names for dictionary elements,
// see `locale/../d_language_custom.php'.
/* ------------------------------------------------------- */
# $oL->getCustom('l_custom', $gw_this['vars'][GW_LANG_I].'-utf8', 'join');

// --------------------------------------------------------
// Load Add-ons
// --------------------------------------------------------
include_once($sys['path_addon'].'/multilingual_vars.php');
include_once($sys['path_addon'].'/class.autolinks.php');
include_once($sys['path_addon'].'/maintenance/maintenance_recount_dict.php');
include_once($sys['path_addon'].'/maintenance/maintenance_recount_user.php');
include_once($sys['path_addon'].'/maintenance/maintenance_clear_history_terms.php');
include_once($sys['path_addon'].'/maintenance/maintenance_clear_import_sessions.php');
#include_once($sys['path_addon'].'/maintenance/maintenance_check_file_versions.php');

function gw_autolinks($t)
{
	$oAutolinks = new gw_autolinks;
	$oAutolinks->init('gw_xml/autolinks/words.txt');
	return $oAutolinks->autolink($t);
}
$sys['filters_defn'] = array('gw_text_smooth_defn', 'gw_autolinks');


$oTpl->addVal( 'path_img_www',     $sys['server_dir'].'/'.$sys['path_img_www']);
$oTpl->addVal( 'url:dev',          $oFunc->text_mailto( $oHtml->a('mailto:tty01@rambler.ru', '<span id="urldev"></span>')).'<script type="text/javascript">/*<![CDATA[*/DOM_make_mailto(\'urldev\', \''.'Студия Дмитрия Шильникова'.'\');/*]]>*/</script>');
$oTpl->addVal( 'href:valid_xhtml', 'http://validator.w3.org/check?uri='. $sys['server_proto'].$sys['server_host'].urlencode(GW_REQUEST_URI) .';ss=1');
$oTpl->addVal( 'href:valid_css',   'http://jigsaw.w3.org/css-validator/validator?uri='. urlencode($sys['path_index_full']).'&amp;warning=2&amp;profile=css2&amp;usermedium=all');
$oTpl->addVal( 'href:feedback',    $oHtml->url_normalize($sys['server_dir'].'/index.php?a=viewpage&amp;id=1') );
$oTpl->addVal( 'url:feedback',     $oHtml->a($sys['server_dir'].'/index.php?a=viewpage&amp;id=1', $oL->m('web_m_fb')) );

/* use contents from a custom page as main page 
   (custom page id = 5, for example) */
if ($gw_this['vars']['layout'] == 'title')
{
#	gw_custom_page(5);
}

/* ----------------------------------------------------- */
/* Load custom functions */
/* ----------------------------------------------------- */
/*
$gw_this['vars']['funcnames'][GW_T_TERM] = array('my_function_1');
function my_function_1()
{
	global $listA, $tpl, $oFunc;
	$oTpl->addVal( 'my:variable', $oFunc->file_exe_contents('your_script.php') );
}
*/

/* ----------------------------------------------------- */
/* Glossword.info search form (1) */
/* ----------------------------------------------------- */
# $oTpl->addVal( 'block:gwsearch1', $oFunc->file_get_contents($sys['path_tpl'].'/common/gw_info1.html') );
# $oTpl->addVal( 'block:google_ads', $oFunc->file_get_contents($sys['path_tpl'].'/common/google_ads.txt') );


/* ----------------------------------------------------- */
/* Counters */
/* ----------------------------------------------------- */
function gw_cfg_counters()
{
	global $sys;
	$ar_images = array(
		'<img alt="" height="1" width="1" src="'.$sys['path_img'].'/0x0.gif" />',
		'<div id="cntrax"></div><script type="text/javascript">/*<![CDATA[*/DOM_make_rax(\'cntrax\');/*]]>*/</script>',
	);
	$ar_counters = array(
		'rax' => array(38, 31,
						'показана симпатичная кнопка',
						'38x31_li02.gif',
						'http://www.liveinternet.ru/stat/glossword.info/index.html'
					),
	);
	return array($ar_images, $ar_counters);
}
function gw_make_counters()
{
	global $oTpl, $sys;
	$max_links = 4;
	$str = '';
	$str_counters = '';
	$ar = gw_cfg_counters();
	if (!IS_MYHOST)
	{
		$str_images = $ar[0][0];
	}
	else
	{
		$str_images = implode('', $ar[0]);
	}
	shuffle($ar[1]);
	$cnt_counters = 0;
	for (reset($ar[1]); list($k, $v) = each($ar[1]);)
	{
		if ($v[4] == ''){ continue; }
		if ($cnt_counters == $max_links) { continue; }
		$str_counters .= CRLF.'<a href="'.$v[4].'">';
		$str_counters .= '<img src="'.$sys['server_dir'] .'/'. $sys['path_img_www'].'/'.$v[3].'" width="'. $v[0]. '" height="'. $v[1]. '" alt="'.$v[2].'" />';
		$str_counters .= '</a>';
		$cnt_counters++;
	}
	$oTpl->addVal('block:counter_images', '<div style="height:1px">' . $str_images. '</div>');
	$oTpl->addVal('block:counter_links', '<div class="counters">' . $str_counters. '</div>');
}
function gw_make_counters_js()
{
	global $oTpl, $sys;
	$max_links = 4;
	$str = '';
	$str_counters = '';
	$ar = gw_cfg_counters();
	if (IS_MYHOST)
	{
		$str_images = $ar[0][0];
	}
	else
	{
		$str_images = implode('', $ar[0]);
	}
	shuffle($ar[1]);
	$cnt_counters = 0;
	$str_counters_a = '';
	$max_links_script = sizeof($ar[1]);
	$str_counters .= 'js_path_img="'.$sys['server_dir'] . '/'. $sys['path_img_www'].'";';
	for (reset($ar[1]); list($k, $v) = each($ar[1]);)
	{
		if ($v[4] == ''){ continue; }
		if ($cnt_counters == $max_links) { continue; }
		$str_counters_a .= 'new Array("'.$v[3].'","'.$v[4].'","'.$v[2].'")';
		/* Last banner */
		if (($cnt_counters+1 != $max_links_script))
		{
			$str_counters_a .= ',';
		}
		$cnt_counters++;
	}
	$str_counters .= 'gw_ar_img=new Array(';
	$str_counters .= $str_counters_a;
	$str_counters .= ');';
	$str_counters .= 'DOM_make_38x31("js38x31",gw_ar_img);';
	$str_counters = '<div id="js38x31"></div><script type="text/javascript">/*<![CDATA[*/' . $str_counters . '/*]]>*/</script>';

	$oTpl->addVal('block:counter_images', '<div style="height:1px">' . $str_images. '</div>');
	$oTpl->addVal('block:counter_links', '<div class="counters">' . $str_counters. '</div>');
}
# gw_make_counters_js();

/*  */
?>