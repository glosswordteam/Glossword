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
 * $Id: update_1.8.9-1.8.10.php 552 2008-08-17 17:40:40Z glossword_team $
 */
if (!defined('IS_IN_GW2')){die();}

$str_from = '1.8.9';
$str_to = '1.8.10';

$this->oTpl->addVal( 'v:favicon', 'favicon_update.ico' );

$this->set_steps(3);
$ar_status = array();

/* */
switch($this->gv['step'])
{
	case 1:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );
		/* */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_inside', 
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[action]" value="'.$this->gv['action'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<p>'.$this->oTkit->_(20009, '<strong>'.$str_from.'</strong>').'</p>'.
				'<p>'.$this->oTkit->_(20008).'</p>'
		);
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);
	break;
	case 2:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* Read database configuration file */
		include('../db_config.php');
		$ar_params['db_host'] = GW_DB_HOST;
		$ar_params['db_user'] = GW_DB_USER;
		$ar_params['db_name'] = str_replace('`', '', GW_DB_DATABASE);
		$ar_params['db_pass'] = GW_DB_PASSWORD;
		$ar_params['db_prefix'] = $sys['tbl_prefix'];
		$ar_params['db_type'] = $sys['db_type'];
		$ar_params['db_type'] = str_replace('410', '', $ar_params['db_type']);
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

		/* Import visual themes */
		$ar_status['themes'] = '<div>'.$this->oTkit->_(20010).': '.$this->oTkit->_(20011).'</div>';
		$ar_items = array();
		foreach (glob('xml/visual-themes_*.xml') as $filename)
		{
			$this->import_visual_themes_file($filename);
			$ar_items[] = str_replace('xml/', '', $filename);
		}
		$ar_status['themes'] .= '<p>('.sizeof($ar_items).') '.implode(', ', $ar_items).'</p>';

		/* == Update database structure == */

		/* Update topics */
		$ar_status['topics'] = '<div>'.$this->oTkit->_(20013).': '.$this->oTkit->_(20012).'</div>';
		if (!$this->oDb->field_exists('int_items', 'topics'))
		{
			$ar_field = array('int_items' => array(
						'type' => 'int',
						'constraint' => 10,
						'unsigned' => true,
						'null' => false,
						'default' => 0 )
			);
			$this->oDb->forge->add_column('topics', $ar_field, 'int_sort');
		}

		/* Update dictionaries */
		$ar_status['dict'] = '<div>'.$this->oTkit->_(20013).': '.$this->oTkit->_(20014).'</div>';
		if (!$this->oDb->field_exists('int_terms', 'dict'))
		{
			$ar_field = array('int_terms_total' => array(
							'type' => 'int', 
							'constraint' => 10,
							'unsigned' => true, 
							'null' => false,
							'default' => 0 )
						);
			$this->oDb->forge->add_column('dict', $ar_field, 'int_terms');
		}
		/* Update settings */
		$ar_status['settings'] = '<div>'.$this->oTkit->_(20013).': '.$this->oTkit->_(20015).'</div>';
		$this->oDb->update('abbr', array('id_dict' => 0) );

		$this->oDb->update('settings', array('settings_val' => $str_to), array('settings_key' => 'version') );

		/* Go to next step */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[action]" value="'.$this->gv['action'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);
	break;
	case 3:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_from, $str_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* Lock installator */
		$ar_status[] = $this->oTkit->_(20018);
		$this->oFunc->file_put_contents($this->g('file_lock'), 'When this file is here, installation script is disabled.', 'w');
		
		/* Update complete */
		$ar_status[] = $this->oTkit->_(20016);
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