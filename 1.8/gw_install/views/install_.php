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
 * $Id: install_.php 552 2008-08-17 17:40:40Z glossword_team $
 */
if (!defined('IS_IN_GW2')){die();}

$this->oHtml->append_html_title( $this->oTkit->_(20002) );

$this->set_steps(7);

switch($this->gv['step'])
{
	case 1:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20022) );
		$this->oHtml->append_html_title( $this->oTkit->_(20022) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* License agreement */

		/* Only English and Russian versions of the license agreement are avaiable */
		$license_file = ($this->gv['il'] == 'russian') ? 'license_ru.html' : 'license_en.html';
		if (file_exists($this->g('path_locale').'/'.$license_file))
		{
			$license_text = implode('', file($this->g('path_locale').'/'.$license_file));

			$this->oTpl->addVal( 'v:text_inside', '<div class="iframe">'.$license_text.'</div>' );
			$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">'.
					'<p>'.$this->oTkit->_(20023).'</p><p>'.$this->oTkit->_(20024).'</p>' 
			);

			$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				'<p>'.$this->oTkit->_(20025).'</p>'.
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20026).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(20027).'</a>'.
				'</div></form>');
		}
	break;
	case 2:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20028) );
		$this->oHtml->append_html_title( $this->oTkit->_(20028) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* == Database settings == */

		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">'.
			'<p>'.$this->oTkit->_(20029).'</p>'
		);

		if (empty($this->gv['arp']))
		{
			$this->gv['arp']['db_host'] = 'localhost';
			$this->gv['arp']['db_user'] = 'root';
			$this->gv['arp']['db_pass'] = '';
			$this->gv['arp']['db_prefix'] = 'gw_';
			$this->gv['arp']['db_name_new'] = $this->gv['arp']['db_name_existent'] = 'glossword';
			$is_str_checked_new = '';
			$is_str_checked_existent = ' checked="checked"';
			$is_str_checked_preinstall = ' checked="checked"';
		}
		else
		{
			$is_str_checked_new = $is_str_checked_existent = $is_str_checked_preinstall = '';
			/* Checkbox */
			if (isset($this->gv['arp']['is_preinstall']))
			{
				$is_str_checked_preinstall = ' checked="checked"';
			}
			/* Radio */
			if ($this->gv['arp']['use_db'] == 'existent')
			{
				$is_str_checked_existent = ' checked="checked"';
			}
			else
			{
				$is_str_checked_new = ' checked="checked"';
			}
		}
		/* Highlight incorrect fields */
		foreach ($this->gv['arp'] as $field_name => $v)
		{
			$ar_class_incorrect[$field_name] = '';
		}
		foreach ($this->ar_broken as $field_name => $v)
		{
			$ar_class_incorrect[$field_name] = ' class="state-warning"';
		}
		
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arp[post]" value="1" />'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);
		
		/* */
		$this->oTpl->addVal( 'v:text_inside', '
<fieldset><legend>'.$this->oTkit->_(20028).'</legend>

<label for="arp-db-type-"><em>'.$this->oTkit->_(20030).'&#160;<strong class="state-warning">*</strong></em>
	<select id="arp-db-type-" class="inp w25" name="arp[db_type]"><option value="mysql" selected="selected">MySQL</option></select>
</label>

<label for="arp-db-host-"'.$ar_class_incorrect['db_host'].'><em>'.$this->oTkit->_(20031).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-db-host-" type="text" class="inp w50" name="arp[db_host]" value="'.$this->gv['arp']['db_host'].'"/>
	<em class="tip">'.$this->oTkit->_(20032).'</em>
</label>

<label for="arp-db-user-"'.$ar_class_incorrect['db_user'].'><em>'.$this->oTkit->_(20033).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-db-user-" type="text" class="inp w50" name="arp[db_user]" value="'.$this->gv['arp']['db_user'].'"/>
	<em class="tip">'.$this->oTkit->_(20034).'</em>
</label>

<label for="arp-db-pass-"'.$ar_class_incorrect['db_pass'].'><em>'.$this->oTkit->_(20035).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-db-pass-" type="password" class="inp w25" name="arp[db_pass]" value="'.$this->gv['arp']['db_pass'].'"/>
	<em class="tip">'.$this->oTkit->_(20036).'</em>
</label>

<label for="arp-db-prefix-"><em>'.$this->oTkit->_(20037).'</em>
	<input id="arp-db-prefix-" type="text" class="inp w25" name="arp[db_prefix]" value="'.$this->gv['arp']['db_prefix'].'" />
	<em class="tip">'.$this->oTkit->_(20038).'</em>
</label>

<label for="arp-is-preinstall-"><em>&#160;<input id="arp-is-preinstall-" type="checkbox" name="arp[is_preinstall]" value="1" '.$is_str_checked_preinstall.'/></em>
	'.$this->oTkit->_(20039).'
	<em class="tip">'.$this->oTkit->_(20040).'</em>
</label>

</fieldset>

<fieldset>
<legend>'.$this->oTkit->_(20041).'</legend>

<label for="arp-name-new"'.$ar_class_incorrect['db_name_new'].'><em>'.$this->oTkit->_(20042).'</em>
<input onkeydown="INSTALL_select_db_name(\'arp-use-new\')" onclick="INSTALL_select_db_name(\'arp-use-new\')" type="text" id="arp-name-new" name="arp[db_name_new]" class="inp w50" value="'.$this->gv['arp']['db_name_new'].'" />
<input type="radio" id="arp-use-new" name="arp[use_db]" value="new"'.$is_str_checked_new.' />
<em class="tip">'.$this->oTkit->_(20044).'</em>
</label>

<label for="arp-name-existent"'.$ar_class_incorrect['db_name_existent'].'><em>'.$this->oTkit->_(20043).'</em>
<input onkeydown="INSTALL_select_db_name(\'arp-use-existent\')" onclick="INSTALL_select_db_name(\'arp-use-existent\')" type="text" id="arp-name-existent" name="arp[db_name_existent]" class="inp w50" value="'.$this->gv['arp']['db_name_existent'].'" />
<input type="radio" id="arp-use-existent" name="arp[use_db]" value="existent"'.$is_str_checked_existent.' />
<em class="tip">'.$this->oTkit->_(20045).'</em>
</label>

</fieldset>

<script type="text/javascript">
function INSTALL_select_db_name(id)
{
	document.forms[0][id].checked = true;
}
</script>
				');
		
	break;
	case 3:
		/* Check database requirements */
		/* Validate */
		$is_error_db_name_existent = $is_error_db_name_new = 0;
		foreach ($this->gv['arp'] as $k => $v)
		{
			if ($v == '')
			{
				$this->ar_broken[$k] = true;
			}
			/* Special mode for radio buttons */
			if ($k == 'use_db' && $v == 'existent' && $this->gv['arp']['db_name_existent'] == '')
			{
				$this->ar_broken['db_name_existent'] = true;
				$is_error_db_name_existent = 1;
			}
			elseif ($k == 'use_db' && $v == 'new' && $this->gv['arp']['db_name_new'] == '')
			{
				$this->ar_broken['db_name_new'] = true;
				$is_error_db_name_new = 1;
			}
		}
		/* */
		if (!$is_error_db_name_existent && !$is_error_db_name_new)
		{
			if (isset($this->ar_broken['db_name_existent']))
			{
				unset($this->ar_broken['db_name_existent']);
			}
			if (isset($this->ar_broken['db_name_new']))
			{
				unset($this->ar_broken['db_name_new']);
			}
		}
		/* Not required */
		if (isset($this->ar_broken['db_prefix']))
		{
			unset($this->ar_broken['db_prefix']);
		}
		/* Fields are incorrect */
		if (!empty($this->ar_broken))
		{
			$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( $this->oTkit->_(20046), false ) );
			$this->gv['step']--;
			$this->page_body();
			return;
		}
		/* Check database connection */
		$db_conn = @mysql_connect( $this->gv['arp']['db_host'], $this->gv['arp']['db_user'], $this->gv['arp']['db_pass'] );
		if ($db_conn)
		{
			if ($this->gv['arp']['use_db'] == 'new')
			{
				/* Create new database */
				if (@mysql_select_db($this->gv['arp']['db_name_new'], $db_conn))
				{
					/* Database already exists */
					$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( 
						$this->oTkit->_(20052, '<strong>'.$this->gv['arp']['db_name_new'].'</strong>').
						'<br />'.$this->oTkit->_(20053), 
						false ) 
					);
					$this->ar_broken['db_name_new'] = true;
					$this->gv['step']--;
					$this->page_body();
					return;
				}
				elseif (!mysql_query('CREATE DATABASE `'. $this->gv['arp']['db_name_new'].'`', $db_conn))
				{
					/* Unable to create new database */
					$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( 
						$this->oTkit->_(20050, '<strong>'.$this->gv['arp']['db_name_new'].'</strong>').
						'<br />'.$this->oTkit->_(20051), 
						false ) 
					);
					$this->ar_broken['db_name_new'] = true;
					$this->gv['step']--;
					$this->page_body();
					return;
				}
				$this->gv['arp']['db_name'] = $this->gv['arp']['db_name_new'];
			}
			elseif ($this->gv['arp']['use_db'] == 'existent')
			{
				/* Use an existent database */
				if (@mysql_select_db($this->gv['arp']['db_name_existent'], $db_conn))
				{
					/* New database selected */
					$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( $this->oTkit->_(20054, $this->gv['arp']['db_name_existent']), true ) );
				}
				else
				{
					/* No such database, could not select */
					$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( 
						$this->oTkit->_(20049, '<strong>'.$this->gv['arp']['db_name_existent'].'</strong>').
						'<br />'.$this->oTkit->_(20048), 
						false ) 
					);
					$this->ar_broken['db_name_existent'] = true;
					$this->gv['step']--;
					$this->page_body();
					return;
				}
				$this->gv['arp']['db_name'] = $this->gv['arp']['db_name_existent'];
			}
		}
		else
		{
			/* Could not connect */
			$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( 
				$this->oTkit->_(20047, '<strong>'.$this->gv['arp']['db_host'].'</strong>', '<strong>'.$this->gv['arp']['db_user'].'</strong>').
				'<br />'.$this->oTkit->_(20048), 
				false ) 
			);
			$this->gv['step']--;
			$this->page_body();
			return;
		}
		/* Continue */
		$this->oChecker->SetCfg( $this->oXml->get('reqcheck_glossword.xml') );
		$ar_info = $this->oChecker->GetInfo();
		$ar_results = $this->oChecker->GetResults();
		$points = $this->oChecker->GetPoints();
		
		/* Select version */
		$result = mysql_query('SELECT VERSION() AS version');
		$db_version = '';
		while ($arV = mysql_fetch_assoc($result))
		{
			$db_version = $arV['version'];
		}
		$ar_results[] = array(
			'tag' => 'db',
			'name' => $this->oTkit->_(10043),
			'val_ini' => $db_version,
			'val_req' => '4.1.0',
			'point' => '',
			'descr' => $this->oTkit->_(10044),
			'status' => (version_compare($db_version, '4.1.0') > 0)
		);
		/* Select max_packed size */
		$result = mysql_query('SHOW VARIABLES LIKE "max_allowed_packet"', $db_conn);
		$db_max_allowed_packet = 0;
		while ($arV = mysql_fetch_assoc($result))
		{
			$db_max_allowed_packet = $arV['Value'];
		}
		$ar_results[] = array(
			'tag' => 'db',
			'name' => $this->oTkit->_(10041, 'max_allowed_packet'),
			'val_ini' => $this->oTkit->number_format($db_max_allowed_packet/1024/1024, 1).' '.$this->oTkit->_(10052),
			'val_req' => '1 '.$this->oTkit->_(10052),
			'point' => '',
			'descr' => $this->oTkit->_(10042),
			'status' => ($db_max_allowed_packet >= 512*1024)
		);
		/* Checking permissions for folders */
		$ar_paths = array(
			$this->g('path_temp_app'),
			$this->g('path_temp_app').'/t',
			$this->g('path_temp_app').'/a',
			$this->g('path_temp_app').'/gw_cache_sql',
			$this->g('path_temp_app').'/gw_export',
			$this->g('path_temp_app').'/gw_logs',
		);
		foreach ($ar_paths as $folder)
		{
			@$this->oFunc->file_put_contents($folder.'/index.html', 'Browsing disabled', 'w');
			$is_return = false;
			if ( file_exists($folder) && is_dir($folder) && is_writeable($folder) )
			{
				$is_return = true;
			}
			$real_folder = realpath($folder) ? realpath($folder): $folder;
			$real_folder = str_replace('\\', '/', $real_folder);
			$ar_results[] = array(
				'tag' => 'dir',
				'name' => $this->oTkit->_(10045, $folder ),
				'val_ini' => $is_return,
				'val_req' => true,
				'point' => '',
				'descr' => $this->oTkit->_(10046, $real_folder),
				'status' => $is_return
			);
		}
		/* Checking permissions for folders */
		$ar_files = array(
			'../db_config.php'
		);
		foreach ($ar_files as $filename)
		{
			$is_return = false;
			if ( file_exists($filename) && is_file($filename) && is_writeable($filename) )
			{
				$is_return = true;
			}
			$real_filename = realpath(dirname($filename)) ? realpath(dirname($filename)).'/'.basename($filename) : $filename;
			$real_filename = str_replace('\\', '/', $real_filename);
			$ar_results[] = array(
				'tag' => 'file',
				'name' => $this->oTkit->_( 10047, $filename ),
				'val_ini' => $is_return,
				'val_req' => true,
				'point' => '',
				'descr' => $this->oTkit->_( 10046, $real_filename ),
				'status' => $is_return
			);
		}
		
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(10012) . ': <strong>'.$ar_info['product'].'</strong> '.$ar_info['version'] );
		$this->oHtml->append_html_title( $this->oTkit->_(10012) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		/* Start HTML */
		$this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);
		/* */
		foreach ($ar_results as $k => $v)
		{
			$class_li = $v['status'] ? 'status-ok' : 'status-error';
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == false ? $this->oTkit->_(10016) : $v['val_ini'];
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == true ? $this->oTkit->_(10017) : $v['val_ini'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == false ? $this->oTkit->_(10016) : $v['val_req'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == true ? $this->oTkit->_(10017) : $v['val_req'];
			$v['val_req'] = ($v['val_req'] == '-1') ? $this->oTkit->_(10018) : $v['val_req'];
			/* */
			switch ($v['name'])
			{
				case 'PHP_VERSION':
					$v['name'] = $this->oTkit->_(10021);
					$v['descr'] = $this->oTkit->_(10022);
				break;
				case 'register_globals':
					$v['descr'] = $this->oTkit->_(10024);
				break;
				case 'getimagesize':
					$v['descr'] = $this->oTkit->_(10026);
				break;
				case 'PCRE_UTF8':
					$v['name'] = $this->oTkit->_(10027);
					$v['descr'] = $this->oTkit->_(10028);
				break;
				case 'REQUEST_URI':
					$v['descr'] = $this->oTkit->_(10031);
				break;
				case 'mysql':
					$v['descr'] = $this->oTkit->_(10032);
				break;
				case 'xml':
					$v['descr'] = $this->oTkit->_(10033);
				break;
				case 'gd':
					$v['descr'] = $this->oTkit->_(10034);
				break;
				case 'zlib':
					$v['descr'] = $this->oTkit->_(10040);
				break;
				case 'mbstring':
					$v['descr'] = $this->oTkit->_(10035);
				break;
				case 'mbstring.func_overload':
					$v['descr'] = $this->oTkit->_(10036);
				break;
				case 'mbstring.encoding_translation':
					$v['descr'] = $this->oTkit->_(10037);
				break;
				case 'mbstring.http_input':
					$v['descr'] = $this->oTkit->_(10038);
				break;
				case 'mbstring.http_output':
					$v['descr'] = $this->oTkit->_(10039);
				break;
			}
			/* */
			switch ($v['tag'])
			{
				case 'ini':
					$v['name'] = $this->oTkit->_(10023, $v['name']);
				break;
				case 'extension':
					$v['name'] = $this->oTkit->_(10029, $v['name']);
				break;
				case 'function':
					$v['name'] = $this->oTkit->_(10025, $v['name']);
				break;
				case 'servervar':
					$v['name'] = $this->oTkit->_(10030, $v['name']);
				break;
				/* Additions: to gwreqcheck */
				case 'db':
				case 'dir':
				case 'file':
					/* Overwrite checker setting */
					if ($this->oChecker->is_checked_total)
					{
						$this->oChecker->is_checked_total = $v['status'];
					}
				break;
			}
			/* */
			$this->oTpl->assign(array(
				'v:id' => hash('md5', $v['name']),
				'v:li_class' => $class_li,
				'v:subject' => $v['name'],
				'v:val_ini' => $v['val_ini'],
				'v:val_req' => $v['val_req'],
				'v:pts' => $v['point'],
				'v:description' => $v['descr'],
				'v:passed_failed' => $v['status'] ? $this->oTkit->_(10007) : $this->oTkit->_(10006),
			));
			$this->oTpl->parseDynamic('foreach:sequence');
		}
		/* Total */
		$this->oTpl->assign(array(
			'v:total_points' => '',
			'v:total_passed_failed' => $this->oChecker->GetChecked() ? $this->oTkit->_(10007) : $this->oTkit->_(10006)
		));
		$this->oTpl->tmp['d']['if:sequence'] = true;

		/* Next step */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">');
		$this->oTpl->addVal( 'v:text_inside', 
				'<input type="hidden" name="arg[target]" value="'.$this->gv['target'].'" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arp[db_settings]" value="'.base64_encode(serialize($this->gv['arp'])).'" />
		');
		if ( $this->oChecker->GetChecked() )
		{
			$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
					' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
					' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
					'</div></form>'
			);
		}
		else
		{
			$this->oTpl->addVal( 'v:text_after', 
					$this->oHtml->get_note_afterpost( $this->oTkit->_(10048), false ).
					'<br /><br /><div class="center">'.
					' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
					' <a href="#" class="submitcancel" onclick="window.location.reload();return false">'.$this->oTkit->_(10049).'</a>'.
					'</div></form>'
			);
		}
	break;
	case 4:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20067) );
		$this->oHtml->append_html_title( $this->oTkit->_(20067) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );
		
		/* Admin account and installation path */
		if (!isset($this->gv['arp']['post']))
		{
			$this->gv['arp']['admin_name'] = 'Admin User';
			$this->gv['arp']['admin_login'] = 'admin';
			$this->gv['arp']['admin_pass'] = '';
			$this->gv['arp']['server_host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '' );
			$this->gv['arp']['admin_email'] = 'admin@'.$this->gv['arp']['server_host'];
			$this->gv['arp']['server_dir'] = dirname(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' );
			$ar_path = explode("/", $this->gv['arp']['server_dir']);
			unset( $ar_path[sizeof($ar_path)-1] );
			$this->gv['arp']['server_dir'] = implode('/', $ar_path);
		}

		/* Highlight incorrect fields */
		foreach ($this->gv['arp'] as $field_name => $v)
		{
			$ar_class_incorrect[$field_name] = '';
		}
		foreach ($this->ar_broken as $field_name => $v)
		{
			$ar_class_incorrect[$field_name] = ' class="state-warning"';
		}

		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				'<input type="hidden" name="arg[target]" value="install" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arp[post]" value="1" />'.
				'<input type="hidden" name="arp[db_settings]" value="'.($this->gv['arp']['db_settings']).'" />'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);

		$this->oTpl->addVal( 'v:text_inside', '
<fieldset><legend>'.$this->oTkit->_(20068).'</legend>
<label for="arp-admin-name-"'.$ar_class_incorrect['admin_name'].'><em>'.$this->oTkit->_(20055).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-admin-name-" type="text" class="inp w50" name="arp[admin_name]" value="'.$this->gv['arp']['admin_name'].'" />
</label>

<label for="arp-admin-email-"><em>'.$this->oTkit->_(20056).'</em>
	<input id="arp-admin-email-" type="text" class="inp w50" name="arp[admin_email]" value="'.$this->gv['arp']['admin_email'].'" />
</label>

<label for="arp-admin-login-"'.$ar_class_incorrect['admin_login'].'><em>'.$this->oTkit->_(20057).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-admin-login-" type="text" class="inp w50" name="arp[admin_login]" value="'.$this->gv['arp']['admin_login'].'" />
</label>

<label for="arp-admin-pass-"'.$ar_class_incorrect['admin_pass'].'><em>'.$this->oTkit->_(20058).'&#160;<strong class="state-warning">*</strong></em>
	<input id="arp-admin-pass-" type="password" class="inp w50" name="arp[admin_pass]" value="'.$this->gv['arp']['admin_pass'].'" />
</label>
</fieldset>

<fieldset><legend>'.$this->oTkit->_(20059).'</legend>

<label for="arp-server-proto-"><em>'.$this->oTkit->_(20060).'&#160;<strong class="state-warning">*</strong></em>
<select id="arp-server-proto-" name="arp[server_proto]" class="inp w25"><option value="http://" selected="selected">http://</option><option value="https://">https://</option></select></label>

<label for="arp-server-host-"'.$ar_class_incorrect['server_host'].'><em>'.$this->oTkit->_(20061).'&#160;<strong class="state-warning">*</strong></em>
<input id="arp-server-host-" name="arp[server_host]" maxlength="255" type="text" class="inp w75" value="'.$this->gv['arp']['server_host'].'" />
<em class="tip">127.0.0.1, www.domain.tld</em></label>

<label for="arp-server-dir-"'.$ar_class_incorrect['server_dir'].'><em>'.$this->oTkit->_(20062).'&#160;<strong class="state-warning">*</strong></em>
<input id="arp-server-dir-" name="arp[server_dir]" maxlength="255" type="text" class="inp w75" value="'.$this->gv['arp']['server_dir'].'" />
<em class="tip">'.$this->oTkit->_(20064).'</em>
</label>

<label>
<em>'.$this->oTkit->_(20063).'</em>
<textarea rows="2" cols="40" id="full-path" type="text" class="inp w75 disabled" disabled="disabled"></textarea>
</label>

</fieldset>

<script type="text/javascript">
function INSTALL_update_full_path()
{
	document.forms[0][\'full-path\'].value = 
		document.forms[0][\'arp-server-proto-\'].value +
		document.forms[0][\'arp-server-host-\'].value + 
		document.forms[0][\'arp-server-dir-\'].value;
	start_timer_full_path();
}
function start_timer_full_path()
{
	setTimeout(function(){INSTALL_update_full_path();}, 500);
}
start_timer_full_path();
</script>
'
		);
	break;
	case 5:
		/* Validate */
		$is_error_db_name_existent = $is_error_db_name_new = 0;
		foreach ($this->gv['arp'] as $k => $v)
		{
			if ($v == '')
			{
				$this->ar_broken[$k] = true;
			}
		}
		/* Not required */
		if (isset($this->ar_broken['admin_email']))
		{
			unset($this->ar_broken['admin_email']);
		}
		if (isset($this->ar_broken['server_dir']))
		{
			unset($this->ar_broken['server_dir']);
		}
		/* Fields are incorrect */
		if (!empty($this->ar_broken))
		{
			$this->oTpl->addVal( 'v:note_afterpost', $this->oHtml->get_note_afterpost( $this->oTkit->_(20046), false ) );
			$this->gv['step']--;
			$this->page_body();
			return;
		}
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20065) );
		$this->oHtml->append_html_title( $this->oTkit->_(20065) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		$ar_results = array();

		$this->gv['arp']['db_settings'] = unserialize(base64_decode($this->gv['arp']['db_settings']));

		/* Prepare configuration file */
		$str_file = '<'.'?php';
		$str_file .= CRLF . '/* Database settings for Glossword */';
		$str_file .= CRLF . sprintf("define('GW_DB_HOST', '%s');", $this->gv['arp']['db_settings']['db_host']);
		$str_file .= CRLF . sprintf("define('GW_DB_DATABASE', '%s');", $this->gv['arp']['db_settings']['db_name']);
		$str_file .= CRLF . sprintf("define('GW_DB_USER', '%s');", $this->gv['arp']['db_settings']['db_user']);
		$str_file .= CRLF . sprintf("define('GW_DB_PASSWORD', '%s');", $this->gv['arp']['db_settings']['db_pass']);
		$str_file .= CRLF . sprintf("\$sys['tbl_prefix'] = '%s';", $this->gv['arp']['db_settings']['db_prefix']);
		$str_file .= CRLF . sprintf("\$sys['db_type'] = '%s';", $this->gv['arp']['db_settings']['db_type']);
		$str_file .= CRLF . '/* Path names for Glossword */';
		$str_file .= CRLF . sprintf("\$sys['server_proto'] = '%s';", $this->gv['arp']['server_proto']);
		$str_file .= CRLF . sprintf("\$sys['server_host'] = '%s';", $this->gv['arp']['server_host']);
		$str_file .= CRLF . sprintf("\$sys['server_dir'] = '%s';", $this->gv['arp']['server_dir']);
		$str_file .= CRLF . '/* Path to sources */';
		$str_file .= CRLF . "\$sys['server_url'] = \$sys['server_proto'].\$sys['server_host'].\$sys['server_dir'];";
		$str_file .= CRLF . "\$sys['file_login'] = 'gw_login.php';";
		$str_file .= CRLF . "\$sys['file_admin'] = 'gw_admin.php';";
		$str_file .= CRLF . "\$sys['path_addon'] = 'gw_addon';";
		$str_file .= CRLF . "\$sys['path_admin'] = 'gw_admin';";
		$str_file .= CRLF . "\$sys['path_gwlib'] = 'lib';";
		$str_file .= CRLF . "\$sys['path_img'] = 'img';";
		$str_file .= CRLF . "\$sys['path_include'] = 'inc';";
		$str_file .= CRLF . "\$sys['path_include_local'] = 'inc';";
		$str_file .= CRLF . "\$sys['path_locale'] = 'gw_locale';";
		$str_file .= CRLF . "\$sys['path_tpl'] = 'templates';";
		$str_file .= CRLF . "\$sys['path_css_script'] = \$sys['server_dir'];";
		$str_file .= CRLF . "\$sys['page_admin'] = \$sys['server_dir'] .'/'. \$sys['file_admin'];";
		$str_file .= CRLF . "\$sys['page_login'] = \$sys['server_dir'] .'/'. \$sys['file_login'];";
		$str_file .= CRLF . "\$sys['token'] = '".substr(md5(uniqid(mt_rand(), true)), 0, 8)."';";
		$str_file .= CRLF . "\$sys['is_allow_tech_support'] = 0;";
		$str_file .= CRLF . '?'.'>';

		/* Connect to database */
		/* Set Database class */
		$this->oDb = $this->_init_db($this->gv['arp']['db_settings']);
		$this->oDb->db_debug_q = false;

		/* Creating database tables */
		$sql_structure = $this->oFunc->file_get_contents('sql/install-structure.sql');
		$sql_structure = str_replace('{prefix}', $this->gv['arp']['db_settings']['db_prefix'], $sql_structure);
		$sql_structure = preg_replace("/--(.*)\n/", '', $sql_structure);
		$sql_structure = str_replace(array("\r\n", "\r", "\n"), ' ', $sql_structure);
		$sql_structure = preg_replace("/[ ]{2,}/", ' ', $sql_structure);
		$ar_sql = explode(';', $sql_structure);
		$sql_structure = '';
		$is_return = true;
		$ar_sql_tables = array();
		foreach ($ar_sql as $sql)
		{
			if (trim($sql) == ''){ continue; }
			preg_match("/`(.*?)`$/", $sql, $preg);
			if (isset($preg[1]))
			{
				$ar_sql_tables[] = $preg[1];
			}
			if (!$this->oDb->query($sql))
			{
				$is_return = false;
			}
		}
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_( 20066 ),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => '('.sizeof($ar_sql_tables).') '.implode(', ', $ar_sql_tables),
			'status' => $is_return
		);
		/* Insert data */
		$sql_data = $this->oFunc->file_get_contents('sql/install-data.sql');
		$sql_data = str_replace('{prefix}', $this->gv['arp']['db_settings']['db_prefix'], $sql_data);
		$sql_data = preg_replace("/--(.*)\n/", '', $sql_data);
		$sql_data = str_replace(array("\r\n", "\r", "\n"), "\n", $sql_data);
		$sql_data = preg_replace("/[ ]{2,}/", ' ', $sql_data);
		$ar_sql = explode(";\n", $sql_data);
		foreach ($ar_sql as $sql)
		{
			if (trim($sql) == ''){ continue; }
			if (!$this->oDb->query($sql))
			{
				$is_return = false;
			}
		}
		/* Change default settings */
		$this->oDb->update( 'users', array(
				'password' => hash('md5', $this->gv['arp']['admin_pass']),
				'login' => $this->gv['arp']['admin_login'],
				'is_active' => 1,
				'date_reg' => $this->g('time_gmt'),
				'user_fname' => $this->gv['arp']['admin_name'],
				'user_sname' => ' ',
				'user_email' => $this->gv['arp']['admin_email'],
				'user_perm' => 'a:16:{s:8:"IS-EMAIL";i:1;s:8:"IS-LOGIN";i:1;s:11:"IS-PASSWORD";i:1;s:8:"IS-USERS";i:1;s:13:"IS-TOPICS-OWN";i:1;s:9:"IS-TOPICS";i:1;s:12:"IS-DICTS-OWN";i:1;s:8:"IS-DICTS";i:1;s:12:"IS-TERMS-OWN";i:1;s:8:"IS-TERMS";i:1;s:15:"IS-TERMS-IMPORT";i:1;s:15:"IS-TERMS-EXPORT";i:1;s:13:"IS-CPAGES-OWN";i:1;s:9:"IS-CPAGES";i:1;s:15:"IS-SYS-SETTINGS";i:1;s:10:"IS-SYS-MNT";i:1;}',
				'user_settings' => 'a:10:{s:10:"avatar_img";s:0:"";s:12:"avatar_img_y";s:0:"";s:12:"avatar_img_x";s:0:"";s:10:"gmt_offset";s:1:"3";s:9:"is_htmled";s:1:"1";s:13:"is_use_avatar";i:0;s:11:"locale_name";s:7:"en-utf8";s:8:"location";s:0:"";s:11:"visualtheme";s:9:"gw_silver";s:12:"dictionaries";a:0:{}}'
			),
			array('id_user' => '2')
		);
		/* Change system settings */
		$this->oDb->update( 'settings', array('settings_val' => $this->g('version')), array('settings_key' => 'version') );
		$this->oDb->update( 'settings', array('settings_val' => $this->gv['arp']['admin_name']), array('settings_key' => 'y\_name') );
		$this->oDb->update( 'settings', array('settings_val' => $this->gv['arp']['admin_email']), array('settings_key' => 'y\_email') );
		$this->oDb->update( 'settings', array('settings_val' => $this->gv['arp']['admin_email']), array('settings_key' => 'site\_email') );
		$this->oDb->update( 'settings', array('settings_val' => $this->gv['arp']['admin_email']), array('settings_key' => 'site\_email\_from') );
		$this->oDb->update( 'settings', array('settings_val' => 'gw\_brand'), array('settings_key' => 'visualtheme') );
		$this->oDb->update( 'settings', array('settings_val' => $this->g('site_name') ), array( 'settings_key' => 'site\_name' ) );
		$this->oDb->update( 'settings', array('settings_val' => $this->g('site_desc') ), array( 'settings_key' => 'site\_desc' ) );
				
		/* Importing XML data:
			1. Topics
			2. Custom pages
			3. Visual themes
			4. Alphabetic orders
			5. Dictionaries
		*/

		/* Import custom pages */
		$is_return = $this->import_custom_pages_file('xml/gw_custom_pages.xml');
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_(20010).': '.$this->oTkit->_(20069),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => 'gw_custom_pages.xml',
			'status' => $is_return
		);

		/* Writing configuration file */
		$filename = '../db_config.php';
		$real_filename = realpath(dirname($filename)) ? realpath(dirname($filename)).'/'.basename($filename) : $filename;
		$real_filename = str_replace('\\', '/', $real_filename);
			
		$is_return = $this->oFunc->file_put_contents('../db_config.php', $str_file, 'w');
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_( 20070 ),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => $this->oTkit->_( 10047, $real_filename ),
			'status' => $is_return
		);

		/* Write OpenSearch */
		$str_oo = $this->oFunc->file_get_contents('../templates/common/opensearch.xml');
		$str_oo = str_replace('{v:site_name}', $this->g('site_name'), $str_oo);
		$str_oo = str_replace('{v:site_desc}', $this->g('site_desc'), $str_oo);
		$str_oo = str_replace('{v:server_url}', strip_tags($this->gv['arp']['server_proto'].$this->gv['arp']['server_host'].$this->gv['arp']['server_dir']), $str_oo);
		$this->oFunc->file_put_contents( $this->g('path_temp_app').'/opensearch.xml', $str_oo, 'w');

		/* */
		$this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);
		/* */
		foreach ($ar_results as $k => $v)
		{
			$class_li = $v['status'] ? 'status-ok' : 'status-error';
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == false ? $this->oTkit->_(10016) : $v['val_ini'];
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == true ? $this->oTkit->_(10017) : $v['val_ini'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == false ? $this->oTkit->_(10016) : $v['val_req'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == true ? $this->oTkit->_(10017) : $v['val_req'];
			$v['val_req'] = ($v['val_req'] == '-1') ? $this->oTkit->_(10018) : $v['val_req'];
			$this->oTpl->assign(array(
				'v:id' => hash('md5', $v['name']),
				'v:li_class' => $class_li,
				'v:subject' => $v['name'],
				'v:val_ini' => $v['val_ini'],
				'v:val_req' => $v['val_req'],
				'v:pts' => $v['point'],
				'v:description' => $v['descr'],
				'v:passed_failed' => $v['status'] ? $this->oTkit->_(10007) : $this->oTkit->_(10006),
			));
			$this->oTpl->parseDynamic('foreach:sequence');
		}
		/* Always "Passed" */
		$this->oTpl->assign(array(
			'v:total_points' => '',
			'v:total_passed_failed' => $this->oTkit->_(10007)
		));
		$this->oTpl->tmp['d']['if:sequence'] = true;
		/* */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_inside', 
				'<input type="hidden" name="arg[target]" value="install" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arp" value="'.base64_encode(serialize($this->gv['arp'])).'" />'
		);
		
		
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);
	break;
	case 6;
		/* Import: topics, visual themes, custom alphabetic order */

		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20071) );
		$this->oHtml->append_html_title( $this->oTkit->_(20071) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );

		$is_return = true;
		$ar_results = array();
		$this->gv['arp'] = unserialize(base64_decode($this->gv['arp']));

		/* Connect to database */
		/* Set Database class */
		$this->oDb = $this->_init_db($this->gv['arp']['db_settings']);
		$this->oDb->db_debug_q = false;

		/* Import sample data */
		if (isset($this->gv['arp']['db_settings']['is_preinstall']))
		{
			$sql_structure = $this->oFunc->file_get_contents('sql/install-example.sql');
			$sql_structure = str_replace('{prefix}', $this->gv['arp']['db_settings']['db_prefix'], $sql_structure);
			$sql_structure = preg_replace("/--(.*)\n/", '', $sql_structure);
			$sql_structure = str_replace(array("\r\n", "\r", "\n"), ' ', $sql_structure);
			$sql_structure = preg_replace("/[ ]{2,}/", ' ', $sql_structure);
			$ar_sql = explode(';', $sql_structure);
			$sql_structure = '';
			$is_return = true;
		
			foreach ($ar_sql as $sql)
			{
				if (trim($sql) == ''){ continue; }
				if (!$this->oDb->query($sql))
				{
					$is_return = false;
				}
			}
			/* Update dictionary dates */
			$this->oDb->update( 'dict', array('date_created' => $this->g('time_gmt')-60, 'date_modified' => $this->g('time_gmt')), array('id' => '1') );
			$this->oDb->update( 'history_terms', array('date_created' => $this->g('time_gmt')-60, 'date_modified' => $this->g('time_gmt')), array('id_dict' => '1') );
			$ar_results[] = array(
				'tag' => 'db',
				'name' => $this->oTkit->_( 20039 ),
				'val_ini' => $is_return,
				'val_req' => true,
				'point' => '',
				'descr' => $this->oTkit->_( 20040 ),
				'status' => $is_return
			);
		}
		/* Import topics */
		$is_return = $this->import_topics_file('xml/gw_topics_map.xml');
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_(20010).': '.$this->oTkit->_(20012),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => 'gw_topics_map.xml',
			'status' => $is_return
		);

		/* Import visual themes */
		$ar_items = array();
		foreach (glob('xml/visual-themes_*.xml') as $filename)
		{
			$is_return = $this->import_visual_themes_file($filename);
			$ar_items[] = str_replace('xml/', '', $filename);
		}
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_(20010).': '.$this->oTkit->_(20011),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => '('.sizeof($ar_items).') '.implode(', ', $ar_items),
			'status' => $is_return
		);
		
		/* Import alphabetic orders */
		$this->oDb->truncate('custom_az');
		$this->oDb->truncate('custom_az_profiles');
		$this->oDb->insert('custom_az_profiles', array('id_profile' => '1', 'is_active' => '1', 'profile_name' => '! UTF-8 Order'));
		$ar_items = array();
		foreach (glob('xml/custom-az_*.xml') as $filename)
		{
			$is_return = $this->import_custom_az_file($filename);
			$ar_items[] = str_replace('xml/', '', $filename);
		}
		$ar_results[] = array(
			'tag' => 'file',
			'name' => $this->oTkit->_(20010).': '.$this->oTkit->_(20072),
			'val_ini' => $is_return,
			'val_req' => true,
			'point' => '',
			'descr' => '('.sizeof($ar_items).') '.implode(', ', $ar_items),
			'status' => $is_return
		);
		/* Optimize and check tables */
		$this->oDb->query( 'CHECK TABLE `'.$this->gv['arp']['db_settings']['db_prefix'].'custom_az`' );

		/* */
		$this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);
		/* */
		foreach ($ar_results as $k => $v)
		{
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == false ? $this->oTkit->_(10016) : $v['val_ini'];
			$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == true ? $this->oTkit->_(10017) : $v['val_ini'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == false ? $this->oTkit->_(10016) : $v['val_req'];
			$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == true ? $this->oTkit->_(10017) : $v['val_req'];
			$v['val_req'] = ($v['val_req'] == '-1') ? $this->oTkit->_(10018) : $v['val_req'];
			$class_li = $v['status'] ? 'status-ok' : 'status-error';
			$this->oTpl->assign(array(
				'v:id' => hash('md5', $v['name']),
				'v:li_class' => $class_li,
				'v:subject' => $v['name'],
				'v:val_ini' => $v['val_ini'],
				'v:val_req' => $v['val_req'],
				'v:pts' => $v['point'],
				'v:description' => $v['descr'],
				'v:passed_failed' => $v['status'] ? $this->oTkit->_(10007) : $this->oTkit->_(10006),
			));
			$this->oTpl->parseDynamic('foreach:sequence');
		}
		$this->oTpl->assign(array(
			'v:total_points' => '',
			'v:total_passed_failed' => $this->oTkit->_(10007)
		));
		$this->oTpl->tmp['d']['if:sequence'] = true;

		/* */
		$this->oTpl->addVal( 'v:text_before', '<form accept-charset="utf-8" action="'.$this->g('file_index') .'" enctype="application/x-www-form-urlencoded" id="form-install" method="post">' );
		$this->oTpl->addVal( 'v:text_inside', 
				'<input type="hidden" name="arg[target]" value="install" />'.
				'<input type="hidden" name="arg[step]" value="'.$this->get_next_step($this->gv['step']).'" />'.
				'<input type="hidden" name="arg[il]" value="'.$this->gv['il'].'" />'.
				'<input type="hidden" name="arp" value="'.base64_encode(serialize($this->gv['arp'])).'" />'
		);
		$this->oTpl->addVal( 'v:text_after', '<div class="center">'.
				' <a href="#" class="submitcancel" onclick="history.back(-1);return false">'.$this->oTkit->_(20007).'</a>'.
				' <a href="#" class="submitok" onclick="document.forms[0].submit();return false">'.$this->oTkit->_(10002).'</a>'.
				'</div></form>'
		);


	break;
	case 7:
		/* Heading */
		$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(20074) );
		$this->oHtml->append_html_title( $this->oTkit->_(20074) );
		$this->oHtml->append_html_title( $this->oTkit->_(10001).' '.$this->gv['step'] );
		
		$this->gv['arp'] = unserialize(base64_decode($this->gv['arp']));
	
		/* Link to administrative interface */
		$this->gv['arp']['server_url'] = $this->gv['arp']['server_proto'].$this->gv['arp']['server_host'].$this->gv['arp']['server_dir'];
		$this->oTpl->addVal( 'v:text_inside', '
<label>
<em>'.$this->oTkit->_(20057).'</em>
<strong>'.$this->gv['arp']['admin_login'].'</strong>
</label>
<label>
<em>'.$this->oTkit->_(20058).'</em>
<strong>'.$this->gv['arp']['admin_pass'].'</strong>
</label>
<label>
<em>'.$this->oTkit->_(20068).'</em>
<a href="#" onclick="window.open(\''.$this->gv['arp']['server_url'].'/gw_login.php\');return false;">'.$this->gv['arp']['server_url'].'/gw_login.php</a>
</label>
<label>
<em>'.$this->oTkit->_(20073).'</em>
<a href="#" onclick="window.open(\''.$this->gv['arp']['server_url'].'/\');return false;">'.$this->gv['arp']['server_url'].'/</a>
</label>
'
		);
		
		/* Lock installator */
		$this->oFunc->file_put_contents($this->g('file_lock'), 'When this file is here, installation script is disabled.', 'w');

	break;
}

/* */
$this->oTpl->tmp['d']['if:install'] = true;


?>