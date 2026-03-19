<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IS_IN_GW2')){die();}

$str_version_from = '1.8.11';
$str_version_to = '1.8.12';

$this->oTpl->addVal( 'v:favicon', 'favicon_update.ico' );

$this->set_steps(3);
$ar_status = array();

/* */
switch($this->gv['step'])
{
	case 1:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_version_from, $str_version_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_version_from, $str_version_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );
		/* */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_inside', 
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[action]" value="'.$this->gv['action'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<p>'.$this->oTkit->_(20009, '<strong>'.$str_version_from.'</strong>').'</p>'.
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
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_version_from, $str_version_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_version_from, $str_version_to) );
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
		
		/* Advanced update  */
		if ( !$this->oDb->field_exists( 'is_index_page', 'virtual_keyboard' ) ) {
			$ar_fields = array(
					'is_index_page' => array(
							'constraint' => 1, // tinyint(1)
							'type' => 'tinyint',
							'unsigned' => TRUE,
							'auto_increment' => FALSE,
							'null' => FALSE,
							'default' => '0'
					) );
			$s_field_after = 'is_active';
			$this->oDb->forge->add_column( 'virtual_keyboard', $ar_fields, $s_field_after );
		}

		/* Update topics */

		/* Update dictionaries */

		/* Update settings */
		$ar_status['settings'] = '<div>'.$this->oTkit->_(20013).': '.$this->oTkit->_(20015).'</div>';

		$this->oDb->update('settings', array('settings_val' => $str_version_to), array('settings_key' => 'version') );

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
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20003, $str_version_from, $str_version_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(20003, $str_version_from, $str_version_to) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* Lock the installer */
		$ar_status[] = $this->oTkit->_(20018);
		$this->oFunc->file_put_contents($this->g('file_lock'), 'When this file is here, installation script is disabled.', 'w');

		/* Update complete */
		$ar_status[] = $this->oTkit->_(20016);
		
		/* Go to title page */
		include('../db_config.php');
		$ar_status[] = $this->oTkit->_(20073) . '<a href="#" onclick="window.open(\''.$sys['server_url'].'/\');return false;">'.$sys['server_url'].'/</a>';

		$ar_status[] = $this->oTkit->_(20068) . '<a href="#" onclick="window.open(\''.$sys['server_url'].'/gw_login.php\');return false;">'.$sys['server_url'].'/gw_login.php</a>';

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