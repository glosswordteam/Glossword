<?php
class gw_setup_upgrade_to_1_8_5 extends gw_setup
{
	/* */
	function upgrade_to_1_8_5_step_1()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1165'), '1.8.4', '1.8.5') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1169') );

		$this->str_before .= '<div class="xu"><p><b>';
		$this->str_before .= $this->oL->m('1244');
		$this->str_before .= '</b></p><ol>';
		$this->str_before .= '<li class="red"><p>'.$this->oL->m('1250').'</p></li>';
		$this->str_before .= '<li><p>'.sprintf($this->oL->m('1251'), '<b>1.8.4</b>').'</p></li>';
		$this->str_before .= '</ol>';

		$this->str_before .= '<p>'.$this->oL->m('1243').'</p>';
		$this->str_before .= '<ol>';
		$this->str_before .= '<li>'.$this->oL->m('1169').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1188').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1245').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1247').'</li>';
		$this->str_before .= '<li>'.$this->oL->m('1246').'</li>';
		$this->str_before .= '</ol>';
		$this->str_before .= '</div>';

		$is_continue = 0;
		/* Get PHP version */
		$this->ar_status[1] = $this->oL->m('1190');
		if (PHP_VERSION_INT > '40303')
		{
			$this->ar_status[1] .= get_html_item_progress(sprintf($this->oL->m('1171'), PHP_VERSION), 1);
			$is_continue = 1;
		}
		else
		{
			$this->ar_status[1] .= get_html_item_progress(sprintf($this->oL->m('1172'), '4.3.3 (25-08-2003)'), 3);
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
		}
		$this->ar_status[5] = gw_next_step($is_continue, 'step=2&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I]);
	}
	/* */
	function upgrade_to_1_8_5_step_2()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1165'), '1.8.4', '1.8.5') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1188') );
		/* for future use */
		$ar_req = array();
		/* */
		if (!$this->is_error)
		{
			$this->gv['arpost']['user_name'] = $this->sys['user_name'];
			$this->gv['arpost']['user_email'] = $this->sys['user_email'];
			$this->gv['arpost']['user_login'] = $this->sys['user_login'];
#			$this->gv['arpost']['pass_new'] = $this->oFunc->text_make_uid(8, 0);
			$this->gv['arpost']['pass_new'] = 'admin';
			$this->gv['arpost']['server_proto'] = $this->sys['server_proto'];
			$this->gv['arpost']['server_host'] = $this->sys['server_host'];
			$this->gv['arpost']['server_dir'] = $this->sys['server_dir'];
			$this->gv['arpost']['dbname'] = trim(str_replace('`', '', $this->gv['arpost']['dbname']));
		}
		$this->str_before .= $this->get_form(2, $ar_req);
	}
	/* */
	function upgrade_to_1_8_5_step_3()
	{
		/* Overwrite Glossword version number, previously defined in `install.php' */
		$this->sys['version'] = '1.8.5';
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1165'), '1.8.4', '1.8.5') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1245') );
		$this->str_before = '';
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
			/* Use an existent database */
			if (@mysql_select_db($this->gv['arpost']['dbname'], $db_conn))
			{
				/* New database selected */
				$this->ar_status[] = sprintf($this->oL->m('1218'), $this->gv['arpost']['dbname']);
			}
			else
			{
				/* Unable to select new database */
				return $this->step_error(sprintf($this->oL->m('1219'), $this->gv['arpost']['dbname']));
			}
			/* Append system settings */
			$arSql = $this->oDb->sqlExec('SELECT * FROM `'.$this->sys['tbl_prefix'].'settings`');
			for (; list($k, $v) = each($arSql);)
			{
				$this->sys[$v['settings_key']] = $v['settings_val'];
			}
			/* */
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
			$str_file .= CRLF . sprintf("\$sys['server_url'] = '%s';", $this->gv['arpost']['server_proto'].$this->gv['arpost']['server_host'].$this->gv['arpost']['server_dir']);
			$str_file .= CRLF . '/* Path to sources */';
			$str_file .= CRLF . "\$sys['path_addon'] = 'gw_addon';";
			$str_file .= CRLF . "\$sys['path_admin'] = 'gw_admin';";
			$str_file .= CRLF . "\$sys['path_gwlib'] = 'lib';";
			$str_file .= CRLF . "\$sys['path_img'] = 'img';";
			$str_file .= CRLF . "\$sys['path_include'] = 'inc';";
			$str_file .= CRLF . "\$sys['path_include_local'] = 'inc';";
			$str_file .= CRLF . "\$sys['path_locale'] = 'gw_locale';";
			$str_file .= CRLF . "\$sys['path_tpl'] = 'templates';";
			$str_file .= CRLF . "\$sys['path_css_script'] = \$sys['server_dir'];";
			$str_file .= CRLF . "\$sys['page_admin'] = \$sys['server_dir'] .'/gw_admin.php';";
			$str_file .= CRLF . "\$sys['page_login'] = \$sys['server_dir'] .'/'. \$sys['path_admin'] . '/login.php';";
			$str_file .= CRLF . "\$sys['token'] = '".$sys['token']."';";
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
		}
		else
		{
			$this->gv['arpost']['dbname_list'] = $this->gv['arpost']['dbname_create'] = '';
			return $this->step_error(sprintf($this->oL->m('1221'), $this->gv['arpost']['dbhost'], $this->gv['arpost']['dbuser']));
		}
		$is_continue = 1;
		$ar_q_settings = array();
		/* Update database structure */

		/* Insert new database data */
		$sql_file = $this->sys['path_install'].'/sql/glossword1_up185_data.sql';
		$sql_q = implode('', file($sql_file));
		$sql_q = str_replace('{PREFIX}', $this->gv['arpost']['dbprefix'], $sql_q);
		$ar_q = explode("\r\n", $sql_q);
		unset($ar_q[sizeof($ar_q)-1]);
		$ar_q = array_map('trim', $ar_q);
		$is_continue = ($is_continue && $this->post_queries($ar_q));

		$ar_q = $q1 = array();
		/* Update the list of dictinaries */
		$arSql = $this->oDb->sqlExec('SELECT * FROM `'.$this->sys['tbl_prefix'].'dict`');
		for (reset($arSql); list($kQ, $arV) = each($arSql);)
		{
			$q1 = array();
			$q1['dict_settings'] = unserialize($arV['dict_settings']);
			unset($q1['dict_settings']['is_show_prinversion']);
			$q1['dict_settings']['is_show_printversion'] = 1;
			$q1['dict_settings']['recent_terms_display'] = '1';
			$q1['dict_settings'] = serialize($q1['dict_settings']);
			$ar_q[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'dict', 'id = '. $arV['id']);
		}
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$ar_q = $q1 = array();
		/* Topics */
		/* Custom pages */
		/* Custom pages data */
		/* Change user settings */
		$q1['login'] = $this->gv['arpost']['user_login'];
		$q1['password'] = md5($this->gv['arpost']['pass_new']);
		$q1['perm_bits'] = '11111111111111';
		$ar_q[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'auth', 'id_auth = "2"');
		$q1 = array();
		$q1['is_active'] = 1;
		$q1['perm_level'] = 16;
		$ar_q[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'users', 'id_user = "2"');
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$ar_q = array();
		/* Visual themes */
		$this->ar_status[] = $this->oL->m('1227');
		$this->ar_status[] = 'gw_admin';
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_admin.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$this->ar_status[] = 'gw_bedroom';
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_bedroom.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$this->ar_status[] = 'gw_brand';
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_brand.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$this->ar_status[] = 'gw_silver';
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_silver.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));
		$this->ar_status[] = 'gw_zh_lenox';
		$xml_file = $this->sys['path_install'].'/supply/templates/'.'gw_zh_lenox.xml';
		$ar_q = $this->import_templates_file($xml_file);
		/* !!! */
		$is_continue = ($is_continue && $this->post_queries($ar_q));


		$this->ar_status[] = gw_next_step($is_continue, 'step=4&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I]);
		/* */
	}
	/* Update dictionaries */
	function upgrade_to_1_8_5_step_4()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1165'), '1.8.4', '1.8.5') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1247') );
		$is_continue = 1;
		$sql = 'SELECT * FROM `'.$this->sys['tbl_prefix'].'dict` order by id ASC';
		$arSql = $this->oDb->sqlExec($sql);
		$arDicts = $arDictIds = array();
		$this->ar_status[] = $this->oL->m('1234').': <b>' . sizeof($arSql) . '</b>';
		/* create valid dictionary list */
		for (reset($arSql); list($arK, $arV) = each($arSql);)
		{
			if ($arV['id'] >= $this->gv['id_dict'])
			{
				$arDicts[] = $arV['tablename'];
				$arDictIds[] = $arV['id'];
			}
		}
		/* set next dictionary id */
		$this->gv['id_dict'] = isset($arDictIds[1]) ? $arDictIds[1] : ($arDictIds[0] + 1);
		$cntDicts = sizeof($arDicts);
		/* Upgrade dictionaries */
		if ($cntDicts > 0)
		{
			$ar_q = array();
			$this->ar_status[] = sprintf($this->oL->m('1248'), $arDicts[0]);

			$sql_file = $this->sys['path_install'].'/sql/glossword1_up185_dict_'.$this->sys['db_type'].'.sql';
			$ar_q_src = file($sql_file);
			$ar_q_src = array_map('trim', $ar_q_src);
			for (reset($ar_q_src); list($k, $v) = each($ar_q_src);)
			{
				$ar_q[$k] = sprintf($v, $arDicts[0]);
			}
			for (reset($ar_q); list($k, $v) = each($ar_q);)
			{
				if ($v == ''){ continue; }
				if ($this->sys['is_debug'])
				{
					$this->ar_status[] = $v.';';
				}
				else
				{

					$this->oDb->sqlExec($v);
				}
			}
			/* Updating terms */
			$sql_t1 = sprintf("UPDATE `%s` SET `term_uri` = `term`", $arDicts[0]);
			$arT = $this->oDb->sqlExec($sql_t1);
		}
		$arDicts = array();
		/* create the new list of not yet updated dictionaries  */
		for (reset($arSql); list($arK, $arV) = each($arSql);)
		{
			if ($arV['id'] >= $this->gv['id_dict'])
			{
				$arDicts[] = $arV['tablename'];
			}
		}
		$cntDicts = sizeof($arDicts);
		$this->ar_status[] = $this->oL->m('1234').': <b>' . $cntDicts . '</b>';
		$url_refresh = 'step=4&a='.$this->gv['a'].'&id_dict='.$this->gv['id_dict'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I];
		if ($cntDicts > 0)
		{
			$this->ar_status[] = gw_next_step($is_continue, $url_refresh);
		}
		else
		{
			$url_refresh = 'step=5&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I];
			$this->ar_status[] = gw_next_step($is_continue, $url_refresh);
		}
		$this->oTpl->a( 'v:meta_refresh', $this->gethtml_metarefresh('gw_install/'.THIS_SCRIPT.'?'.$url_refresh) );
		/* countdown */
		$this->oTpl->a( 'v:javascripts', '
		<script type="text/javascript">
			var total_sec = '.$this->sys['time_refresh'].';
			function display_countdown() {
				document.getElementById("countdown").innerHTML = total_sec--;
				if (total_sec > 0) {
					setTimeout(\'display_countdown()\', 1000);
				}
			}
			display_countdown();
		</script>');
	}
	/* */
	function upgrade_to_1_8_5_step_5()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1165'), '1.8.4', '1.8.5') );
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1246') );

		$is_continue = 1;
		/* Lock installer */
		$this->ar_status[0] = $this->oL->m('1231');
#		$this->sys['is_debug'] = 1;
		if (!$this->sys['is_debug'])
		{
			$is_continue = $this->oFunc->file_put_contents($this->sys['file_lock'], $this->oL->m('1232', $this->sys['file_lock']), 'w');
		}
		if ($is_continue)
		{
			$this->ar_status[0] .= '... <span class="green">ok</span>';
		}
		else
		{
			$this->ar_status[(sizeof($this->ar_status)-1)] .= get_html_item_progress('<b>'.$this->sys['file_lock'].'</b><pre>locked</pre>', 3);
		}
		/* COMPLETE */
		$this->ar_status[] = '<b class="green">'.$this->oL->m('1249').'</b>';
		/* Link to administrative interface */
		$login_url =  $this->sys['server_proto'].$this->sys['server_host'].$this->sys['server_dir'] . '/'.$this->sys['path_admin'].'/';
		$this->ar_status[] = '<a href="' . $login_url . '" onclick="nw(this);return false">' . $login_url.'</a>';
		$this->ar_status[] = '<a href="' . $this->sys['server_url'] . '/" onclick="nw(this);return false">' . $this->sys['server_url'] .'/</a>';
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
		$ar_q[] = sprintf('DELETE FROM %s WHERE id_theme = "%s"', $this->sys['tbl_prefix'].'theme_settings', gw_text_sql($q1['id_theme']));
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
					$this->str = '<li><span class="gray">';
					$this->str .= $this->oHtml->a($filename, $filename) . '</span>... ';
					$isWrite = $this->oFunc->file_put_contents($filename, $file_contents, 'w');
					$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($file_contents), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
				}
			}
		}
		return $ar_q;
	}
	/* */
	function get_html_steps_progress($step)
	{
		$ar = array();
		for ($i = 1; $i <= 5; $i++)
		{
			$ar[$i] = ' '. $this->oL->m('1168') . ' ' . $i.'  ';
			if ($step == $i) { $ar[$i] = '<b class="green">'.$ar[$i].'</b>'; }
		}
		return '<span class="gray">'.implode('&#x2192;', $ar).'</span>';
	}
	function get_form($step, $ar_req = array())
	{
		$tmp = '';
		$this->oForm->set('formbgcolor',       $this->ar_theme['color_2']);
		$this->oForm->set('formbordercolor',   $this->ar_theme['color_4']);
		$this->oForm->set('formbordercolorL',  $this->ar_theme['color_1']);
		$this->oForm->set('formwidth', '75%');
		$this->oForm->set('action', THIS_SCRIPT);
		$this->oForm->set('isButtonCancel', 0);
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
				$this->oForm->set('submitok', $this->oL->m('1185') );
				$this->oForm->set('formwidth', '100%');
				$tmp .= $this->get_form_title($this->oL->m('1106'));
				$tmp .= '<table class="gw2TableFieldset" width="100%">';
				$tmp .= '<tbody><tr><td style="width:35%"></td><td></td></tr>';
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
				  	'mysql323' => 'MySQL 3.23.x, 4.0.x',
				  	'mysql410' => 'MySQL 4.1.x, 5.x',
				);
				$tmp .= $this->get_form_tr($this->oL->m('1028'), $this->oForm->field('select', 'arpost[db_type]', $this->gv['arpost']['db_type'], '50%', $arDbTypes));
				/* default database name */
				$tmp .= $this->get_form_tr(
					$this->oL->m('1208'),
					$this->oForm->field('input', 'arpost[dbname]', $this->gv['arpost']['dbname']),
					$ar_req_msg['dbname'],
					$ar_broken_msg['dbname']);
				$tmp .= $this->get_form_tr($this->oL->m('1205'), $this->oForm->field('input', 'arpost[dbprefix]', $this->gv['arpost']['dbprefix']));
				$tmp .= $this->get_form_tr($this->oL->m('1203'), $this->oForm->field('input', 'arpost[dbuser]', $this->gv['arpost']['dbuser']));
				$tmp .= $this->get_form_tr($this->oL->m('1204'), $this->oForm->field('input', 'arpost[dbpass]', $this->gv['arpost']['dbpass']));
				$tmp .= '</tbody></table>';
			break;
		}
		$tmp .= $this->oForm->field('hidden', GW_LANG_I, $this->gv[GW_LANG_I]);
		$tmp .= $this->oForm->field('hidden', 'a', $this->gv['a']);
		$tmp .= $this->oForm->field('hidden', 'step', $step+1);
		return '<div class="center">'.$this->oForm->Output($tmp).'</div>';
	}

}
?>