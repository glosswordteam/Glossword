<?php
/**
 *  Glossword - Glossary Compiler
 *  © 2008 Glossword.biz team (http://glossword.biz/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 * $Id: uninstall_.php 552 2008-08-17 17:40:40Z glossword_team $
 */
if (!defined('IS_IN_GW2')){die();}

/* */
$this->oHtml->append_html_title( $this->oTkit->_(20004) );

$this->oTpl->addVal( 'v:favicon', 'favicon_uninstall.ico' );

$this->set_steps(2);

$ar_status = array();

/* Create the list of database tables */
/* See also settings_maintenance_8.inc.php */
$ar_tables = array('abbr', 'abbr_phrase', 'component', 'component_map', 'component_actions',
	'dict', 'map_user_to_dict', 'map_user_to_term', 'pages', 'pages_phrase', 'search_results',
	'sessions', 'settings', 'stat_dict', 'stat_search', 'theme', 'theme_group', 'captcha',
	'theme_settings', 'topics', 'topics_phrase', 'users', 'wordlist', 'wordmap',
	'history_terms', 'custom_az', 'custom_az_profiles', 'virtual_keyboard','auth_restore',
	'import_sessions'
);
/* Read database configuration file */
include('../db_config.php');
$ar_params['db_host'] = GW_DB_HOST;
$ar_params['db_user'] = GW_DB_USER;
$ar_params['db_name'] = GW_DB_DATABASE;
$ar_params['db_pass'] = GW_DB_PASSWORD;
$ar_params['db_prefix'] = $sys['tbl_prefix'];
$ar_params['db_type'] = $sys['db_type'];
$this->oDb = $this->_init_db($ar_params);
$this->oDb->db_debug_q = false;

/* Check if not installed */
if (!$this->oDb->table_exists('settings'))
{
	$this->oTpl->addVal( 'v:text_inside',
		'<p>'.$this->oTkit->_(20020).'</p>'.
		'<p><a href="'.$this->g('file_index').'">'.$this->oTkit->_(20021).'</a></p>'

	);
	$this->oTpl->tmp['d']['if:install'] = true;
	return;
}

/* */
switch($this->gv['step'])
{
	case 1:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20004) );
		$this->oHtml->append_html_title( $this->oTkit->_(20004) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* Skip collecting information */

		/* Select dictionaries */
		$this->oDb->select('tablename');
		$this->oDb->from('dict');
		$query = $this->oDb->get();
		$arSql = $query->result_array();
		foreach ($arSql as $k => $arV)
		{
			$v = str_replace('\_', '_', $arV['tablename']);
			$v = str_replace($ar_params['db_prefix'], '', $v);
			$ar_tables[] = $v;
		}

		$this->oTpl->addVal( 'v:text_inside',
				'<input type="hidden" name="arg[target]" value="uninstall" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arg[post]" value="1" />'.
				'<div>'.$this->oTkit->_(20006, '<samp>'.$ar_params['db_name'].'</samp>').':</div><br />'.
				'('.sizeof($ar_tables).') '.implode(', ', $ar_tables)
		);
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">');

		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				'<p class="state-warning">'.$this->oTkit->_(20005).'</p>'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(10016).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(20004).'</a>'.
				'</div></form>'
		);
	break;
	case 2:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20004) );
		$this->oHtml->append_html_title( $this->oTkit->_(20004) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* == Uninstall == */

		/* Select dictionaries */
		$this->oDb->select('tablename');
		$this->oDb->from('dict');
		$query = $this->oDb->get();
		$arSql = $query->result_array();
		foreach ($arSql as $k => $arV)
		{
			$v = str_replace('\_', '_', $arV['tablename']);
			$v = str_replace($ar_params['db_prefix'], '', $v);
			$ar_tables[] = $v;
		}
		/* */
		$ar_status[] =  '('.sizeof($ar_tables).') '.implode(', ', $ar_tables);
		foreach ($ar_tables as $tablename)
		{
			$this->oDb->forge->drop_table($tablename);
		}
		$ar_status[] = $this->oTkit->_(20019);
	break;
}

/* */
if (!empty($ar_status))
{
	$this->oTpl->addVal( 'v:text_inside', '<ul class="select-action"><li>'.implode('</li><li>', $ar_status).'</li></ul>' );
}

/* */
$this->oTpl->tmp['d']['if:install'] = true;


?>