<?php
/**
 * Glossword installation functions
 * © 2007 Dmitry N. Shilnikov <dev at glossword dot info>
 * $Id$
 */
class gw_setup_install extends gw_setup
{
	var $a = 'install';
	/* */
	function install_step_1()
	{
		$this->ar_tpl[] = 'i_step.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1164') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1169') );

		$this->str_before .= $this->oL->m('1186');
		$this->str_before .= '<ol>';
		$this->str_before .= '<li>'.$this->oL->m('1169').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1187').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1188').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1170').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1228').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1189').'</li>';
		$this->str_before .= '</ol>';
		
		$is_continue = 0;
		/* Get PHP version */
		$this->ar_status[1] = $this->oL->m('1190');
		if (PHP_VERSION_INT > '40308')
		{
			$this->ar_status[1] .= get_html_item_progress(sprintf($this->oL->m('1171'), PHP_VERSION), 1);
			$is_continue = 1;
		}
		else
		{
			$this->ar_status[1] .= get_html_item_progress(sprintf($this->oL->m('1172'), '4.3.8 (13-Jul-2004)'), 3);
		}
		if ($is_continue)
		{
			$is_continue = 0;
			/* Get permisions */
			$this->ar_status[2] = $this->oL->m('1191');

			if ( file_exists('db_config.php') && is_writeable('db_config.php') )
			{
				$this->ar_status[2] .= get_html_item_progress(sprintf($this->oL->m('1173'), 'db_config.php'), 1);
				$is_continue = 1;
			}
			else
			{
				$this->ar_status[2] .= get_html_item_progress(sprintf($this->oL->m('1174'), 'db_config.php'), 3);
			}
			$is_continue = 1;
			/* Check writing permissions */
			$ar_paths = array(
				$this->sys['path_temporary'],
				$this->sys['path_temporary'].'/t',
				$this->sys['path_temporary'].'/a',
				$this->sys['path_temporary'].'/gw_cache_sql',
				$this->sys['path_temporary'].'/gw_export',
			);
			foreach ($ar_paths as $folder)
			{
				@$this->oFunc->file_put_contents($folder.'/index.html', ' ', 'w');
				if ( file_exists($folder) && is_dir($folder) && is_writeable($folder) )
				{
					$this->ar_status[2] .= get_html_item_progress(sprintf($this->oL->m('1173'), $folder.'/'), 1);
				}
				else
				{
					$is_continue = 0;
					$this->ar_status[3] = get_html_item_progress(sprintf($this->oL->m('1174'), $folder.'/'), 3);
				}
			}
		}
		/* Get PHP extensions */
		if ($is_continue)
		{
			$this->ar_status[4] = $this->oL->m('1181');
			$arPhpExt = array();
			if (function_exists('get_loaded_extensions'))
			{
				$arPhpExt = get_loaded_extensions();
			}
			if (empty($arPhpExt))
			{
				$this->ar_status[4] .= get_html_item_progress($this->oL->m('1182'), 3);
				$is_continue = 0;
			}
		}
		/* For each extension */
		if ($is_continue)
		{
			if (in_array('xml', $arPhpExt))
			{
				$this->ar_status[4] .= get_html_item_progress(sprintf($this->oL->m('1175'), 'xml'), 1);
			}
			else
			{
				$this->ar_status[4] .= get_html_item_progress(sprintf($this->oL->m('1176'), 'xml'), 3);
				$is_continue = 0;
			}
			if (function_exists('mysql_query'))
			{
				$this->ar_status[4] .= get_html_item_progress(sprintf($this->oL->m('1175'), 'mysql'), 1);
			}
			else
			{
				$this->ar_status[4] .= get_html_item_progress(sprintf($this->oL->m('1176'), 'mysql'), 3);
				$is_continue = 0;
			}
			if (in_array('iconv', $arPhpExt))
			{
				$this->ar_status[4] .= get_html_item_progress($this->oL->m('1177'), 1);
			}
			else
			{
				$this->ar_status[4] .= get_html_item_progress($this->oL->m('1178'), 2);
			}
			if (in_array('mbstring', $arPhpExt))
			{
				$this->ar_status[4] .= get_html_item_progress($this->oL->m('1179'), 1);
			}
			else
			{
				$this->ar_status[4] .= get_html_item_progress($this->oL->m('1180'), 2);
			}
		}
		$this->ar_status[5] = gw_next_step($is_continue, 'step=2&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I]);
	}
	/* */
	function install_step_2()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1164') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1187') );
		$this->str_before .= '<div class="xu">';
		$this->str_before .= sprintf($this->oL->m('1192'), 'Glossword');
		$this->str_before .= '<p class="xu">'.$this->oL->m('1193').'</p>';
		$this->str_before .= '</div>';
		
		$lang_license = ($this->gv[GW_LANG_I] == 'ru') ? 'ru' : 'en';
		$str_license = $this->oFunc->file_get_contents($this->sys['path_install'].'/supply/license_'.$lang_license.'.html');
		$str_license = preg_replace("/^(.*)<body>/s", '', $str_license);
		$str_license = preg_replace("/<\/body>(.*)$/s", '', $str_license);
		$this->str_before .= '<div class="iframe xt">'. $str_license .'</div>';
		$this->str_before .= '<br />';
		$this->str_after .= $this->get_form(2);
	}
	/* */
	function install_step_3()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1164') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1188') );
		$this->str_before .= '<div class="xu">'.$this->oL->m('1198').'</div>';
		/* for future use */
		$ar_req = array();
		/* */
		if (!$this->is_error)
		{
			$this->gv['arpost']['user_name'] = $this->sys['user_name'];
			$this->gv['arpost']['user_email'] = $this->sys['user_email'];
			$this->gv['arpost']['user_login'] = $this->sys['user_login'];
			$this->gv['arpost']['pass_new'] = $this->oFunc->text_make_uid(8, 0);
#			$this->gv['arpost']['pass_new'] = 'admin';
			$this->gv['arpost']['server_proto'] = $this->sys['server_proto'];
			$this->gv['arpost']['server_host'] = $this->sys['server_host'];
			$this->gv['arpost']['server_dir'] = $this->sys['server_dir'];
			$this->gv['arpost']['dbname'] = trim(str_replace('`', '', $this->gv['arpost']['dbname']));
			$this->gv['arpost']['dbname_create'] = $this->gv['arpost']['dbname'];
			$this->gv['arpost']['dbname_list'] = $this->gv['arpost']['dbname'];
		}
		if (!isset($this->gv['arpost']['dbname_list']))
		{
			$this->gv['arpost']['dbname_list'] = $this->gv['arpost']['dbname_create'];
		}
		$this->str_before .= $this->get_form(3, $ar_req);
	}
	/* */
	function install_step_4()
	{
#		$this->sys['is_debug'] = 1;
		$this->str_before .= '';
		/* Server settings */
		$this->sys['server_proto'] = $this->gv['arpost']['server_proto'];
		$this->sys['server_host'] = $this->gv['arpost']['server_host'];
		$this->sys['server_dir'] = $this->gv['arpost']['server_dir'];
		$this->sys['tbl_prefix'] = $this->gv['arpost']['dbprefix'];
		$this->sys['db_type'] = $this->gv['arpost']['db_type'];
		$is_continue = 0;
		/* Check database connection */
		$db_conn = @mysql_connect($this->gv['arpost']['dbhost'], $this->gv['arpost']['dbuser'], $this->gv['arpost']['dbpass']);
		if ($db_conn)
		{
			/* Connected to `dbhost' */
			if (isset($this->gv['arpost']['dbname_create']) && ($this->gv['arpost']['r_dbname'] == 'custom'))
			{
				/* Create new database */
				if (@mysql_select_db($this->gv['arpost']['dbname_create'], $db_conn))
				{
					/* Database already exists */
					return $this->step_error($this->oL->m('1215'));
				}
				elseif (!mysql_query('CREATE DATABASE `'. $this->gv['arpost']['dbname_create'].'`', $db_conn))
				{
					/* Unable to create new database */
					return $this->step_error($this->oL->m('1217'));
				}
				else
				{
					/* New database created */
					$this->ar_status[] = sprintf($this->oL->m('1220'), $this->gv['arpost']['dbname_create']);
				}
				$this->gv['arpost']['dbname'] = $this->gv['arpost']['dbname_create'];
			}
			elseif (isset($this->gv['arpost']['dbname_list']))
			{
				$this->gv['arpost']['dbname'] = $this->gv['arpost']['dbname_list'];
			}
			else
			{
				$this->gv['arpost']['dbname_list'] = $this->gv['arpost']['dbname_create'] = '';
				return $this->step_error($this->oL->m('1216'));
			}
			/* Use an existent database */
			if (@mysql_select_db($this->gv['arpost']['dbname'], $db_conn))
			{
				/* New database selected */
				$this->ar_status[] = sprintf($this->oL->m('1218'), $this->gv['arpost']['dbname']);
			}
			else
			{
				/* Unable to select new database */
				$this->gv['arpost']['dbname_create'] = $this->gv['arpost']['dbname'];
				return $this->step_error(sprintf($this->oL->m('1219'), $this->gv['arpost']['dbname']));
			}
			$this->oDb->host = $this->gv['arpost']['dbhost'];
			$this->oDb->user = $this->gv['arpost']['dbuser'];
			$this->oDb->password = $this->gv['arpost']['dbpass'];
			$this->oDb->database = $this->gv['arpost']['dbname'];
			$sys['token'] = substr(md5(uniqid(mt_rand(), true)), 0, 8);
			/* Write configuration file */
			$is_continue = 0;
			$str_file = '<'.'?php';
			$str_file .= CRLF . '/* Database settings for Glossword */';
			$str_file .= CRLF . sprintf("define('GW_DB_HOST', '%s');", $this->gv['arpost']['dbhost']);
			$str_file .= CRLF . sprintf("define('GW_DB_DATABASE', '`%s`');", $this->gv['arpost']['dbname']);
			$str_file .= CRLF . sprintf("define('GW_DB_USER', '%s');", $this->gv['arpost']['dbuser']);
			$str_file .= CRLF . sprintf("define('GW_DB_PASSWORD', '%s');", $this->gv['arpost']['dbpass']);
			$str_file .= CRLF . sprintf("\$sys['tbl_prefix'] = '%s';", $this->gv['arpost']['dbprefix']);
			$str_file .= CRLF . sprintf("\$sys['db_type'] = '%s';", $this->gv['arpost']['db_type']);
			$str_file .= CRLF . '/* Path names for Glossword */';
			$str_file .= CRLF . sprintf("\$sys['server_proto'] = '%s';", $this->gv['arpost']['server_proto']);
			$str_file .= CRLF . sprintf("\$sys['server_host'] = '%s';", $this->gv['arpost']['server_host']);
			$str_file .= CRLF . sprintf("\$sys['server_dir'] = '%s';", $this->gv['arpost']['server_dir']);
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
			$str_file .= CRLF . "\$sys['token'] = '".$sys['token']."';";
			$str_file .= CRLF . "\$sys['is_allow_tech_support'] = 0;";
			$str_file .= CRLF . '?'.'>';
			$this->ar_status[] = $this->oL->m('1229');
			if (!$this->sys['is_debug'])
			{
				$is_continue = $this->oFunc->file_put_contents('db_config.php', $str_file, 'w');
			}
			if ($is_continue)
			{
				$this->ar_status[(sizeof($this->ar_status)-1)] .= '... <span class="green">ok</span>';
			}
			else
			{
				$this->ar_status[(sizeof($this->ar_status)-1)] .= get_html_item_progress('<b>db_config.php</b><pre>'.htmlspecialchars_ltgt($str_file).'</pre>', 3);
			}
			/* OpenSearch */
			$str_oo = $this->oFunc->file_get_contents($this->sys['path_tpl'].'/common/opensearch.xml');
			$str_oo = str_replace('{v:site_name}', 'Glossword', $str_oo);
			$str_oo = str_replace('{v:site_desc}', 'Glossary compiler', $str_oo);
			$str_oo = str_replace('{v:server_url}', strip_tags($this->sys['server_url']), $str_oo);
			$this->ar_status[] = $this->oL->m('OpenSearch');
			if (!$this->sys['is_debug'])
			{
				$is_continue = $this->oFunc->file_put_contents($this->sys['path_temporary'].'/opensearch.xml', $str_oo);
			}
			if ($is_continue)
			{
				$this->ar_status[(sizeof($this->ar_status)-1)] .= '... <span class="green">ok</span>';
			}
			else
			{
				$this->ar_status[(sizeof($this->ar_status)-1)] .= get_html_item_progress('<b>opensearch.xml</b><pre>'.htmlspecialchars_ltgt($str_oo).'</pre>', 3);
			}
		}
		else
		{
			$this->gv['arpost']['dbname_list'] = $this->gv['arpost']['dbname_create'] = '';
			return $this->step_error(sprintf($this->oL->m('1221'), $this->gv['arpost']['dbhost'], $this->gv['arpost']['dbuser']));
		}
		$is_continue = 1;
		/* Create database structure */
		$this->ar_status[] = $this->oL->m('1224');
		$sql_file = $this->sys['path_install'].'/sql/glossword1_structure_'.$this->gv['arpost']['db_type'].'.sql';
		$sql_q = implode('', file($sql_file));
		$sql_q = str_replace('{PREFIX}', $this->gv['arpost']['dbprefix'], $sql_q);
		$ar_q = explode(";", $sql_q);
		unset($ar_q[sizeof($ar_q)-1]);
		$ar_q = array_map('trim', $ar_q);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		/* Insert database data  */
		$sql_file = $this->sys['path_install'].'/sql/glossword1_data.sql';
		$sql_q = implode('', file($sql_file));
		$sql_q = str_replace('{PREFIX}', $this->gv['arpost']['dbprefix'], $sql_q);
		$sql_q = str_replace("\r\n", "\n", $sql_q);
		$ar_q = explode("\n", $sql_q);
		unset($ar_q[sizeof($ar_q)-1]);
		$ar_q = array_map('trim', $ar_q);
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$ar_q = array();
		/* Change default settings */
		$q1['id_user'] = 2;
		$q1['login'] = $this->gv['arpost']['user_login'];
		$q1['password'] = md5($this->gv['arpost']['pass_new']);
		$q1['is_active'] = 1;
		$q1['date_reg'] = $this->sys['time_now_gmt_unix'];
		$q1['user_fname'] = $this->gv['arpost']['user_name'];
		$q1['user_sname'] = ' ';
		$q1['user_email'] = $this->gv['arpost']['user_email'];
		$q1['user_settings'] = 'a:10:{s:10:"avatar_img";s:0:"";s:12:"avatar_img_y";s:0:"";s:12:"avatar_img_x";s:0:"";s:10:"gmt_offset";s:1:"3";s:9:"is_htmled";s:1:"1";s:13:"is_use_avatar";i:0;s:11:"locale_name";s:7:"en-utf8";s:8:"location";s:0:"";s:11:"visualtheme";s:9:"gw_silver";s:12:"dictionaries";a:0:{}}';
	
		$ar_user_perm = $this->oSess->get_access_names();
		$ar_permissions = array();
		foreach($ar_user_perm as $k => $v)
		{
			$ar_permissions[strtoupper($k)] = 1;
		}
		$q1['user_perm'] = serialize($ar_permissions);


		$ar_q[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'users');
		/*Change syste settings */
		$ar_q[] = gw_sql_update(
			array('settings_val' => $this->gv['arpost']['user_name']), 
			$this->sys['tbl_prefix'].'settings', 'settings_key="'.gw_text_sql('y_name').'"'
		);
		$ar_q[] = gw_sql_update(
			array('settings_val' => $this->gv['arpost']['user_email']), 
			$this->sys['tbl_prefix'].'settings', 'settings_key="'.gw_text_sql('y_email').'"'
		);
		$ar_q[] = gw_sql_update(
			array('settings_val' => $this->sys['version']), 
			$this->sys['tbl_prefix'].'settings', 'settings_key="'.gw_text_sql('version').'"'
		);
		$ar_q[] = gw_sql_update(
			array('settings_val' => 'gw_brand'), 
			$this->sys['tbl_prefix'].'settings', 'settings_key="'.gw_text_sql('visualtheme').'"'
		);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		/* Importing XML data:
			1. Topics
			2. Custom pages
			3. Visual themes
			4. Dictionaries
		*/
		/* Topics */
		$this->ar_status[] = $this->oL->m('1225');
		$xml_file = $this->sys['path_install'].'/supply/topics/gw_topics_map.xml';
		$ar_q = $this->import_topics_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		/* Custom pages */
		$this->ar_status[] = $this->oL->m('custom_pages');
		$xml_file = $this->sys['path_install'].'/supply/custom_pages/gw_custom_pages.xml';
		$ar_q = $this->import_custom_pages_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		/* Visual themes */
		$this->ar_status[] = $this->oL->m('1227');
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_admin.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_silver.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$this->post_queries($ar_q);
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_bedroom.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$this->post_queries($ar_q);
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_brand.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$this->post_queries($ar_q);
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_zh_lenox.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$this->post_queries($ar_q);

		/* 1.8.7: Import a custom alphabetic orders */
		$this->post_queries( array('INSERT INTO `'.$this->sys['tbl_prefix'].'custom_az_profiles` VALUES(1, 1, \'! UTF-8 Order\')') );
		
		$ar_az = file_readDirF( $this->sys['path_install'].'/supply/custom_az', '(.xml)' );
		foreach ($ar_az as $filename)
		{
			$this->post_queries( $this->import_custom_az_file($this->sys['path_install'].'/supply/custom_az/'. $filename) );
		}

		/* */
		$this->ar_status[] = gw_next_step($is_continue, 'step=5&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I]);
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1164') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1170') );
	}
	/* */
	function install_step_5()
	{
		$ar_q[] = 'ALTER TABLE `'. $this->sys['tbl_prefix'] .'wordlist` PACK_KEYS=1 CHECKSUM=0 DELAY_KEY_WRITE=1';
		$ar_q[] = 'ALTER TABLE `'. $this->sys['tbl_prefix'] .'wordmap` PACK_KEYS=1 CHECKSUM=0 DELAY_KEY_WRITE=1';
		$ar_q[] = 'OPTIMIZE TABLE `'.$this->sys['tbl_prefix'].'wordlist`';
		$ar_q[] = 'OPTIMIZE TABLE `'.$this->sys['tbl_prefix'].'wordmap`';
		$ar_q[] = 'CHECK TABLE `'.$this->sys['tbl_prefix'].'custom_az`';
		/* Change creation date */
		$ar_q[] = 'UPDATE `'.$this->sys['tbl_prefix'].'pages` SET date_created = "'.$this->sys['time_now_gmt_unix'].'", date_modified = "'.$this->sys['time_now_gmt_unix'].'"';
		$ar_q[] = 'UPDATE `'.$this->sys['tbl_prefix'].'topics` SET date_created = "'.$this->sys['time_now_gmt_unix'].'", date_modified = "'.$this->sys['time_now_gmt_unix'].'"';

		$this->post_queries($ar_q);

		$is_continue = 1;
		/* Lock installer */
		$this->ar_status[0] = $this->oL->m('1231');
#$this->sys['is_debug'] = 1;
		if (!$this->sys['is_debug'])
		{
			$is_continue = $this->oFunc->file_put_contents($this->sys['file_lock'], $this->oL->m('1232', $this->sys['file_lock']), 'w');
		}
		if ($is_continue)
		{
			$this->ar_status[0] .= '... <span class="green">ok</span>';
			$is_continue = 0;
		}
		else
		{
			$this->ar_status[(sizeof($this->ar_status)-1)] .= get_html_item_progress('<b>'.$this->sys['file_lock'].'</b><pre>locked</pre>', 3);
		}
		/* COMPLETE */
		$this->ar_status[] = '<strong class="green">'.$this->oL->m('1230').'</strong>';
		/* Link to administrative interface */
		$this->ar_status[] = '<a href="' . $this->sys['server_url'].'/'.$this->sys['file_login']. '" onclick="nw(this);return false">' . $this->sys['server_url'].'/'.$this->sys['file_login'] . '</a>';
		$this->ar_status[] = '<a href="' . $this->sys['server_url'] . '/" onclick="nw(this);return false">' . $this->sys['server_url'] . '/</a>';
		/* */
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1164') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1189') );
	}
	/* */
	function import_dictionary_sql($sql_dir, $id_dict)
	{
		$ar_files = array('dict', 'stats', 'map_user_to_term', '1_of_1', 'gw_wordlist_1_of_1', 'gw_wordmap_1_of_1');
		/* Dictionary structure depends on mysql version */
		$sql_file = $sql_dir . '/' . $id_dict . '_structure_'.$this->sys['db_type'].'.sql';
		$sql_q = implode('', file($sql_file));
		$sql_q = preg_replace("/;$/", ";\n", $sql_q);
		$sql_q = str_replace("\r\n", "\n", $sql_q);
		$sql_q = str_replace("\n", CRLF, $sql_q);
		$ar_q = explode(';'.CRLF, $sql_q);
		unset($ar_q[sizeof($ar_q)-1]);
		$this->post_queries($ar_q);
		/* Dictionary contents */
		for (reset($ar_files); list($k, $v) = each($ar_files);)
		{
			$sql_file = $sql_dir . '/' . $id_dict . '_' . $v . '.sql';
			$sql_q = implode('', file($sql_file));
			$sql_q = str_replace('`gw_dict`', '`'.$this->gv['arpost']['dbprefix'].'dict`', $sql_q);
			$sql_q = str_replace('`gw_stat_dict`', '`'.$this->gv['arpost']['dbprefix'].'stat_dict`', $sql_q);
			$sql_q = str_replace('`gw_map_user_to_term`', '`'.$this->gv['arpost']['dbprefix'].'map_user_to_term`', $sql_q);
			$sql_q = str_replace('`gw_wordmap`', '`'.$this->gv['arpost']['dbprefix'].'wordmap`', $sql_q);
			$sql_q = str_replace('`gw_wordlist`', '`'.$this->gv['arpost']['dbprefix'].'wordlist`', $sql_q);
			$sql_q = preg_replace("/# <!--(.*?)-->/", '', $sql_q);
			$sql_q = preg_replace("/;$/", ";\n", $sql_q);
			$sql_q = str_replace("\r\n", "\n", $sql_q);
			$sql_q = str_replace("\n", CRLF, $sql_q);
			$ar_q = explode(';'.CRLF, $sql_q);
			unset($ar_q[sizeof($ar_q)-1]);
			/* !!! */
			$this->post_queries($ar_q);
		}
		$this->ar_status[] = $id_dict;
	}
	/* */
	function import_topics_file($filename)
	{
		/* Do import using DOM model */
		$oDom = new gw_domxml;
		$oDom->is_skip_white = 0;
		$oDom->strData = implode('', file($filename));
		$oDom->parse();
		$oDom->strData = '';
		$arXmlLine = $oDom->get_elements_by_tagname('topic');
		$ar_q = $q1 = array();
		/* */
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
								$q2['id_lang'] = $id_lang.'-'.$this->gv['lang_enc'];
								$ar_q[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'topics_phrase');
							}
						break;
					}
				}
				if (!isset($q1['date_created']))
				{
					$q1['date_created'] = $q1['date_modified'] = $this->sys['time_now_gmt_unix'];
				}
				$ar_q[] =  gw_sql_replace($q1, $this->sys['tbl_prefix'].'topics');
			}
		return $ar_q;
	}
	/* */
	function import_custom_pages_file($filename)
	{
		/* Do import using DOM model */
		$oDom = new gw_domxml;
		$oDom->is_skip_white = 0;
		$oDom->strData = implode('', file($filename));
		$oDom->parse();
		$oDom->strData = '';
		$arXmlLine = $oDom->get_elements_by_tagname('custom_page');
		$ar_q = $q1 = array();
		for (; list($k1, $v1) = each($arXmlLine);)
		{
			/* per each topic */
			if (!isset($v1['children'])) { continue; }
			$id_page = $oDom->get_attribute('id', $v1['tag'], $v1);
			/* <entry> */
			for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
			{
				if (!is_array($v2)){ continue; }
				switch($v2['tag'])
				{
					case 'parameters':
						$q2 = array();
						$q1 = unserialize($oDom->get_content($v2));
						$q1['id_page'] = $q2['id_page'] = $id_page;
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
							$q2['id_lang'] = $id_lang.'-'.$this->gv['lang_enc'];
							$ar_q[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'pages_phrase');
						}
					break;
					default:
					  /* page_php_1, page_php_2 */
					  $q1[$v2['tag']] = $v2['value'];
					break;
				}
			}
			$q1['date_created'] = $q1['date_modified'] = $this->sys['time_now_gmt_unix'];
			$ar_q[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'pages');
		}
		return $ar_q;
	}
	/* */
	function import_templates_file($filename)
	{
		/* Do import using DOM model */
		$oDom = new gw_domxml;
		$oDom->is_skip_white = 0;
		$oDom->strData = implode('', file($filename));
		$oDom->parse();
		$oDom->strData = '';
		$arXmlLine = $oDom->get_elements_by_tagname('group');
		$arQ = $q1 = array();
		$q1['id_theme'] = $oDom->get_attribute('id_theme', 'style', $oDom->arData);
		$q1['theme_name'] = $oDom->get_attribute('theme_name', 'style', $oDom->arData);
		$q1['theme_author'] = $oDom->get_attribute('theme_author', 'style', $oDom->arData);
		$q1['theme_email'] = $oDom->get_attribute('theme_email', 'style', $oDom->arData);
		$q1['theme_url'] = $oDom->get_attribute('theme_url', 'style', $oDom->arData);
		list($q1['v1'], $q1['v2'], $q1['v3']) = explode('.', $oDom->get_attribute('version', 'style', $oDom->arData));
		$ar_q[0] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'theme');
		$this->str = '';
		for (; list($k1, $v1) = each($arXmlLine);)
		{
			/* per each group */
			if (!isset($v1['children'])) { continue; }
			$id_group = $oDom->get_attribute('id', $v1['tag'], $v1);
			for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
			{
				if (!is_array($v2)){ continue; }
				$q2 = array();
				if ($id_group == 'settings')
				{
					$q2['id_theme'] = $q1['id_theme'];
					$q2['date_modified'] = $this->sys['time_now_gmt_unix'];
					$q2['settings_key'] = $oDom->get_attribute('key', $v2['tag'], $v2);
					$q2['settings_value'] = $oDom->get_content($v2);
					$q2['settings_value'] = str_replace('&lt;![CDATA[', '<![CDATA[', $q2['settings_value']);
					$q2['settings_value'] = str_replace(']]&gt;', ']]>', $q2['settings_value']);
					$q2['code'] = '';
					$q2['code_i'] = '';
					$ar_q[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'theme_settings');
				}
				else if ($id_group == 'binary')
				{
					$filename = $this->sys['path_temporary'].'/t/'.$q1['id_theme'].'/'.$oDom->get_attribute('key', $v2['tag'], $v2);
					$file_contents = text_hex2bin($oDom->get_content($v2));
					$this->str .= '<li><span class="gray">';
					$this->str .= $this->oHtml->a($filename, $filename) . '</span>... ';
					$isWrite = $this->oFunc->file_put_contents($filename, $file_contents, 'w');
					$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($file_contents), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
				}
			}
		}
		return $ar_q;
	}
	/* */
	function get_form($step, $ar_req = array())
	{	
		$tmp = '';
		$this->oForm->set('formbgcolor',       $this->ar_theme['color_2']);
		$this->oForm->set('formbordercolor',   $this->ar_theme['color_4']);
		$this->oForm->set('formbordercolorL',  $this->ar_theme['color_1']);
		$this->oForm->set('formwidth', '75%');
		$this->oForm->set('action', THIS_SCRIPT);
		$this->oForm->set('isButtonCancel', 0);
		$this->oForm->set('method', 'GET');
		$this->oForm->set('align_buttons', 'right');
		/* 1.7.0 */
		if (MYSQL_VERSION_INT >= 40100) { $this->gv['arpost']['db_type'] = 'mysql410'; }
		else { $this->gv['arpost']['db_type'] = 'mysql323'; }

		$ar_broken = validatePostWalk($this->gv['arpost'], $ar_req);
		$ar_req = array_flip($ar_req);
		/* mark fields as "Required" and display error message */
		while (is_array($this->gv['arpost']) && list($k, $v) = each($this->gv['arpost']) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m('reason_9') . '</b></span><br />'; }
		}
		/* */
		switch ($step)
		{
			case 2:
				$this->oForm->set('align_buttons', 'right');
				$this->oForm->set('isButtonCancel', 1);
				$this->oForm->set('strNotes', $this->oL->m('1194'));
				$this->oForm->set('submitok', $this->oL->m('1195') );
				$this->oForm->set('submitcancel', $this->oL->m('1196') );
			break;
			case 3:
				$this->oForm->set('submitok', $this->oL->m('1185') );
				$this->oForm->set('formwidth', '100%');
				$tmp .= $this->get_form_title($this->oL->m('1106'));
				$tmp .= '<table class="gw2TableFieldset" width="100%">';
				$tmp .= '<tbody><tr><td style="width:35%"></td><td></td></tr>';
				$tmp .= $this->get_form_tr($this->oL->m('contact_name'), $this->oForm->field('input', 'arpost[user_name]', $this->gv['arpost']['user_name']), $ar_req_msg['user_name'], $ar_broken_msg['user_name']);
				$tmp .= $this->get_form_tr($this->oL->m('contact_email'), $this->oForm->field('input', 'arpost[user_email]', $this->gv['arpost']['user_email']), $ar_req_msg['user_email'], $ar_broken_msg['user_email']);
				$tmp .= $this->get_form_tr($this->oL->m('login'), $this->oForm->field('input', 'arpost[user_login]', $this->gv['arpost']['user_login']), $ar_req_msg['user_login'], $ar_broken_msg['user_login']);
				$tmp .= $this->get_form_tr($this->oL->m('password'), $this->oForm->field('input', 'arpost[pass_new]', $this->gv['arpost']['pass_new']), $ar_req_msg['pass_new'], $ar_broken_msg['pass_new']);
				$tmp .= '</tbody></table>';
				$tmp .= $this->get_form_title($this->oL->m('1201'));
				$tmp .= '<table class="gw2TableFieldset" width="100%">';
				$tmp .= '<tbody><tr><td style="width:35%"></td><td></td></tr>';
				$tmp .= $this->get_form_tr($this->oL->m('1206'), $this->oForm->field('select', 'arpost[server_proto]', $this->gv['arpost']['server_proto'], '50%', array('http://' => 'http://', 'https://'=> 'https://')));
				$tmp .= $this->get_form_tr($this->oL->m('1207'), $this->oForm->field('input', 'arpost[server_host]', $this->gv['arpost']['server_host']));
				$tmp .= $this->get_form_tr($this->oL->m('1210'), $this->oL->m('1211').'<br/>'.$this->oForm->field('input', 'arpost[server_dir]', $this->gv['arpost']['server_dir']));
				$tmp .= '</tbody></table>';
				$tmp .= $this->get_form_title($this->oL->m('1202'));
				$tmp .= '<table class="gw2TableFieldset" width="100%">';
				$tmp .= '<tbody><tr><td style="width:35%"></td><td></td></tr>';
				$tmp .= $this->get_form_tr($this->oL->m('1209'), $this->oForm->field('input', 'arpost[dbhost]', $this->gv['arpost']['dbhost']));
				/* Database types */
				$arDbTypes = array(
				  	'mysql410' => 'MySQL 4.1.x, 5.x',
				);
				$tmp .= $this->get_form_tr($this->oL->m('1028'), $this->oForm->field('select', 'arpost[db_type]', $this->gv['arpost']['db_type'], '50%', $arDbTypes));
				
				$tmp .= '<tr><td class="td1">'.$this->oL->m('1208').'</td><td>';
				$tmp .= '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
				$tmp .= '<tbody><tr><td style="width:1%"></td><td></td></tr>';
				$arBoxId = array();
				/* ID for radio-button */
				$arBoxId['id'] = 'dbname_list';
				$arBoxId['onchange'] = 'check_dbname()';
				$tmp .= $this->get_form_tr(
					$this->oForm->field('radio', 'arpost[r_dbname]', 'list', 1, $arBoxId),
					'<label id="labelList" for="dbname_list">'.$this->oL->m('1199').'</label>');
				$tmp .= $this->get_form_tr(
					'',
					$this->oForm->field('input', 'arpost[dbname_list]', $this->gv['arpost']['dbname_list']),
					$ar_req_msg['dbname_list'],
					$ar_broken_msg['dbname_list']);
				/* ID for radio-button */
				$arBoxId['id'] = 'dbname_create';
				$arBoxId['onchange'] = 'check_dbname()';
				$tmp .= $this->get_form_tr(
					$this->oForm->field('radio', 'arpost[r_dbname]', 'custom', 1, $arBoxId),
					'<label id="labelCustom" for="dbname_create">'.$this->oL->m('1200').'</label>');
				$tmp .= $this->get_form_tr(
					'',
					$this->oForm->field('input', 'arpost[dbname_create]', $this->gv['arpost']['dbname_create']),
					$ar_req_msg['dbname_create'],
					$ar_broken_msg['dbname_create']);
				$tmp .= '</tbody>';
				$tmp .= '</table>';

				$tmp .= '</td></tr>';
				$tmp .= $this->get_form_tr($this->oL->m('1205'), $this->oForm->field('input', 'arpost[dbprefix]', $this->gv['arpost']['dbprefix']));
				$tmp .= $this->get_form_tr($this->oL->m('1203'), $this->oForm->field('input', 'arpost[dbuser]', $this->gv['arpost']['dbuser']));
				$tmp .= $this->get_form_tr($this->oL->m('1204'), $this->oForm->field('input', 'arpost[dbpass]', $this->gv['arpost']['dbpass']));
				$tmp .= '</tbody></table>';
$tmp .= '<script type="text/javascript">/*<![CDATA[*/
function check_dbname()
{
	if (gw_getElementById(\'dbname_create\').checked) {
		gw_getElementById(\'arpost_dbname_create_\').style.border = "solid 1px #A0D0CC";
		gw_getElementById(\'arpost_dbname_create_\').style.color = "#000";
		gw_getElementById(\'arpost_dbname_create_\').disabled = false;
		gw_getElementById(\'arpost_dbname_list_\').style.border = "solid 1px #CCC";
		gw_getElementById(\'arpost_dbname_list_\').style.color = "#AAA";
		gw_getElementById(\'arpost_dbname_list_\').disabled = true;
	}
	else {
		gw_getElementById(\'arpost_dbname_create_\').style.border = "solid 1px #CCC";
		gw_getElementById(\'arpost_dbname_create_\').style.color = "#AAA";
		gw_getElementById(\'arpost_dbname_create_\').disabled = true;
		gw_getElementById(\'arpost_dbname_list_\').style.border = "solid 1px #A0D0CC";
		gw_getElementById(\'arpost_dbname_list_\').style.color = "#000";
		gw_getElementById(\'arpost_dbname_list_\').disabled = false;
	}
}
check_dbname();
/*]]>*/</script>';
			break;
		}
		$tmp .= $this->oForm->field('hidden', GW_LANG_I, $this->gv[GW_LANG_I]);
		$tmp .= $this->oForm->field('hidden', 'a', $this->gv['a']);
		$tmp .= $this->oForm->field('hidden', 'step', $step+1);
		return '<div class="center">'.$this->oForm->Output($tmp).'</div>';
	}

	/* */
	function import_custom_az_file($filename)
	{
		/* Do import using DOM model */
		$oDom = new gw_domxml;
		$oDom->is_skip_white = 0;
		$oDom->strData = implode('', file($filename));
		$oDom->parse();
		$oDom->strData = '';
		$arXmlLine = $oDom->get_elements_by_tagname('custom_az');
		
		/* -- Create a new -- */
		$id_profile = $this->oDb->MaxId($this->sys['tbl_prefix'].'custom_az_profiles', 'id_profile');
		/* */
		$is_error_xml = 1;
		/* one loop */
		for (; list($k1, $v1) = each($arXmlLine);)
		{
			$q2 = array();
			if (!isset($v1['children'])) { continue; }
			$q1['is_active'] = $oDom->get_attribute( 'is_active', 'custom_az', $v1 );
			$q1['profile_name'] = $oDom->get_attribute( 'profile_name', 'custom_az', $v1 );
			$q2['id_profile'] = $q1['id_profile'] = $id_profile;
			/* -- Create a new -- */
			$arQ[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'custom_az_profiles');
			$is_error_xml = 0;
			/* for each <entry> */
			for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
			{
				if (!is_array($v2)){ continue; }
				switch ($v2['tag'])
				{
					case 'entry':
						for (reset($v2['children']); list($k3, $v3) = each($v2['children']);)
						{
							if (!is_array($v3)){ continue; }
							$q2[$v3['tag']] = $v3['value'];
						}
						$q2['az_int'] = text_str2ord($q2['az_value']);
						$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'custom_az');
					break;
				}
			}
		}
		/* Check for errors in XML */
		if ($is_error_xml)
		{
			return array();
		}
		return $arQ;
	}
	

}
?>