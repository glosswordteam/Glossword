<?php
/**
 * $Id: class.session-1.9.php 471 2008-05-14 16:29:11Z yrtimd $
 */
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
class gw_session_1_9
{
	var $oDb;
	var $oL;
	var $sys;
	
	/* Field name */
	var $db_user_settings = 'user_settings';
		
	/* Guest User ID */
	var $id_guest = 1;
	var $ar_permissions = array();
	/* User settings */
	var $is_changed = 0;
	var $is_closed = 0;

	/* Used for creatign Session ID */
	var $is_use_remote_ip = 0;
	var $is_use_remote_ua = 0;
	var $str_secret;

	var $ar_sess = array();
	var $ar_user = array();
	var $id_user;
	var $id_sess;
	var $sid;
	var $url_login;
	var $url_append_mode;
	var $uri;
	/* Garbage collect probability in percent */
	var $gc_probability = 4;
	/* Session will be expired in `n` seconds (3600 = 1 hour) */
	var $int_timeout = 3600;
	var $time_changed;
	var $time_now_gmt_unix;
	/* Times used in META tag */
	var $time_refresh = 2;
	/* Messages */
	var $msg_1 = 'Session expired in case of inactivity.';
	var $msg_2 = 'Authorization required. Session does not exist.';
	var $msg_3 = 'You have logged out.';
	var $msg_4 = 'Authorization required. Session expired or does not exist.';
	var $msg_5 = 'Passed: %s minutes';
	var $msg_6 = 'Time to idle: %s minutes';
	var $db_table_users = '';
	var $db_table_sessions = '';

	function load_settings()
	{
		$vars = array(
			'msg_1' => $this->oL->m('1342'),
			'msg_2' => $this->oL->m('1343'),
			'msg_3' => $this->oL->m('1344'),
			'msg_4' => $this->oL->m('1345'),
			'msg_5' => $this->oL->m('1346'),
			'msg_6' => $this->oL->m('1347'),
			'db_table_users' => $this->sys['tbl_prefix'].'users',
			'db_table_sessions' => $this->sys['tbl_prefix'].'sessions',
			'time_refresh' => 1,
			'url_login' => $this->sys['server_url'].'/'.$this->sys['file_login'],
			'uri' => $this->sys['uri'],
			'remote_ip' => REMOTE_IP,
			'sid' => GW_SID,
			'time_now' => $this->sys['time_now'],
			'time_now_gmt_unix' => $this->sys['time_now_gmt_unix'],
			'int_timeout' => 3600 * 6,
		);
		foreach ($vars as $k => $v)
		{
			$this->$k = $v;
		}
	}
	/* Access map per action */
	/* See also users_admin.php */
	function get_access_names()
	{
		return array(
			'is-email' => 0,
			'is-login' => 0,
			'is-password' => 0,
			'is-profile' => 0,
			'is-users' => 0,
			'is-topics-own' => 0,
			'is-topics' => 0,
			'is-dicts-own' => 0,
			'is-dicts' => 0,
			'is-terms-own' => 0,
			'is-terms' => 0,
			'is-terms-import' => 0,
			'is-terms-export' => 0,
			'is-cpages-own' => 0,
			'is-cpages' => 0,
			'is-sys-settings' => 0,
			'is-sys-mnt' => 0
		);
	}
	
	/* */
	function is_auth()
	{
		if (!$this->id_user || $this->id_user == $this->id_guest)
		{
			/* Authorization required */
			$this->error();
		}
	}
	/* */
	function is_perm($access_name)
	{
		if (isset($this->ar_permissions[strtoupper($access_name)]) 
			&& $this->ar_permissions[strtoupper($access_name)])
		{
			return true;
		}
		else
		{
			/* Authorization required */
			$this->error();
		}
		/* Permission not found */
		return false;
	}
	/* */
	function is($access_name)
	{
		if (isset($this->ar_permissions[strtoupper($access_name)]) 
			&& $this->ar_permissions[strtoupper($access_name)])
		{
			return true;
		}
		/* Permission not found */
		return false;
	}

	/* One function that manages sessions */
	function sess_init($id_sess = 0)
	{
		if ($id_sess)
		{
			/* Search Session ID in database. Load user settings also */
			$this->id_sess = $id_sess;
			$sql = 'SELECT s.*, u.* ';
			$sql .= 'FROM `'.$this->db_table_sessions.'` AS s, `'.$this->db_table_users.'` AS u ';
			$sql .= 'WHERE u.id_user = s.id_user AND s.id_sess = "'.$this->id_sess.'" ';
			$sql .= 'LIMIT 1';
			$arSql = $this->oDb->sqlExec($sql);
			$arSql = isset($arSql[0]) ? $arSql[0] : array();
			if (empty($arSql))
			{
				/* Session ID not found */
				/* Remove cookie */
				setcookie( $this->sid.$this->sys['token'], $this->id_sess, $this->time_now - 2, $this->sys['server_dir'] );
				/* Start Guest session then */
				$this->sess_insert($this->id_guest);
			}
			else
			{
				/* Session expired */
				if (($this->time_now_gmt_unix - $arSql['date_changed']) > $this->int_timeout)
				{
					$this->time_changed = $arSql['date_changed'];
					$this->error(1);
				}
				$this->id_user = $arSql['id_user'];
				$this->ar_sess['date_changed'] = $arSql['date_changed'];
				$this->ar_sess['ip'] = $this->remote_ip;
				unset($arSql['id_sess'], $arSql['date_changed'], $arSql['ip']);
				$arSql[$this->db_user_settings] = @unserialize($arSql[$this->db_user_settings]);
				$this->ar_user =& $arSql;
				/* Load user permissions */
				$this->user_start($this->id_user);
			}
		}
		else
		{
			/* Start Guest session */
			$this->sess_insert($this->id_guest);
		}
	}
	/* */
	function sess_insert($id_user)
	{
		if ($id_user == $this->id_guest)
		{
			/* Do not start real session, use virtual session instead */
			$this->id_sess = 0;
			$this->ar_sess['ip'] = $this->remote_ip;
			$this->ar_sess['date_changed'] = $this->time_now_gmt_unix;
			/* Load Guest user settings */
			$this->user_start($id_user);
		}
		else
		{
			/* On login */
			$ar['id_sess'] = $this->id_sess = $this->make_session_id();
			$ar['ip'] = sprintf( "%u", ip2long($this->remote_ip) );
			$ar['id_user'] = $id_user;
			$ar['is_remember'] = $this->is_remember;
			$ar['date_changed'] = $this->time_now_gmt_unix;
			$ar['ua'] = '';
			$sql = gw_sql_insert($ar, $this->db_table_sessions, 1);
			if (!$this->oDb->sqlExec($sql))
			{
				print $sql;
				exit;
			}
		}
	}
	/* Construct session unique number */
	function make_session_id()
	{
		$str_remote_ip = ($this->is_use_remote_ip) ? $this->remote_ip : '';
		$str_remote_ua = ($this->is_use_remote_ua) ? $this->remote_ua : '';
		return md5($this->url_login . $this->time_now . $str_remote_ip . $str_remote_ua . mt_rand() . $this->str_secret);
	}
	/* */
	function url($url)
	{
		$url = preg_replace("/".$this->sid."=[0-9A-Za-z]/", "", $url);
		$url = preg_replace("/[&?]+$/", "", $url);
		if (!$this->id_sess) { return $url; }
		switch ($this->url_append_mode)
		{
			case 'get':
				$url .= ( (strpos($url, "?") != false) ?  "&" : "?" ) .
					urlencode($this->sid) . '=' . $this->id_sess;
			break;
			default:
			break;
		}
		return $url;
	}
	
	/* */
	function sess_close()
	{
		if ($this->is_closed) { return; }
		$this->is_closed = 1;
		$this->user_close();
		$this->ar_sess['date_changed'] = $this->time_now_gmt_unix;
		/* Continue cookie */
		@setcookie( $this->sid.$this->sys['token'], $this->id_sess, $this->time_now + ($this->int_timeout * 2), $this->sys['server_dir'] );
		/* User ID could not be changed during session! */
		if ($this->id_sess)
		{
			$this->ar_sess['ip'] = sprintf( "%u", ip2long($this->ar_sess['ip']) );
			$sql = gw_sql_update($this->ar_sess, $this->db_table_sessions, '`id_sess` = "'.$this->id_sess.'"');
			$this->oDb->sqlExec($sql);
		}
	}
	/* */
	function user_start($id_user)
	{
		$this->id_user = $id_user;
		/* No session or guest user */
		if (empty($this->ar_user))
		{
			$this->user_load_values($this->id_user, 'merge');
		}
		/* $this->ar_user should exist now */
		if (isset($this->ar_user['user_perm']))
		{
			$this->user_register_permissions($this->ar_user['user_perm']);
		}
#prn_r( $this->ar_sess, 'ar_sess' );
#prn_r( $this->ar_user, 'ar_user'  );
#prn_r( $this->id_user, 'id_user'  );
	}
	/* */
	function user_load_values($id_user, $mode = 'single')
	{
		$sql = 'SELECT u.* ';
		$sql .= 'FROM `'.$this->db_table_users.'` AS u ';
		$sql .= 'WHERE u.id_user = "'.gw_text_sql($id_user).'" ';
		$sql .= 'LIMIT 1';
		$arSql = $this->oDb->sqlExec( $sql );
		$arSql = isset($arSql[0]) ? $arSql[0] : array();
		/* No valid user found */
		if (empty($arSql) && $mode == 'merge')
		{
			/* No settings found. Protect againsts endless loop `!= 1`*/
			if ($id_user != $this->id_guest)
			{
				$this->user_load_values($this->id_guest, 'merge');
			}
		}
		else if (!empty($arSql))
		{
			if ($mode == 'merge')
			{
				/* Merge received settings into the class */
				$this->id_user = $arSql['id_user'];
				$this->ar_sess['date_changed'] = $this->time_now_gmt_unix;
				$this->ar_sess['ip'] = $this->remote_ip;
				unset($arSql['id_sess'], $arSql['date_changed'], $arSql['ip']);
				$arSql[$this->db_user_settings] = @unserialize($arSql[$this->db_user_settings]);
				$this->ar_user =& $arSql;
			}
			else
			{
				/* Return only an array with settings */
				$arSql[$this->db_user_settings] = @unserialize($arSql[$this->db_user_settings]);
				return $arSql;
			}
		}
	}
	/* */
	function user_register_permissions($sp)
	{
		$ar_user_perm = unserialize($sp);
		/* No permissions found */
		if (empty($ar_user_perm))
		{
			/* Load default permissions */
			$ar_user_perm = $this->get_access_names();
		}
		/* Add permissions to the class */
		foreach($ar_user_perm as $k => $v)
		{
			$this->ar_permissions[strtoupper($k)] = $v;
		}
	}
	/**
	 * Adds a variable to secondary user settings.
	 */
	function user_set($varname, $value = '')
	{
		$this->is_changed = 1;
		$this->ar_user[$this->db_user_settings][$varname] = $value;
	}
	/**
	 * Adds a variable to primary user settings.
	 */
	function user_set_val($varname, $value = '')
	{
		$this->is_changed = 1;
		$this->ar_user[$varname] = $value;
	}
	/**
	 * Gets user settings.
	 */
	function user_get($varname = '')
	{
		if ($varname == '')
		{
			return $this->ar_user;
		}
		else
		{
			if (empty($this->ar_user) || is_string($this->ar_user)){ return; }
			for (reset($this->ar_user); list($k, $v) = each($this->ar_user);)
			{
				if ($k == $varname)
				{
					return $v;
				}
			}
			for (reset($this->ar_user['user_settings']); list($k, $v) = each($this->ar_user['user_settings']);)
			{
				if ($k == $varname)
				{
					return $v;
				}
			}
		}
		return false;
	}
	/**
	 * Removes a variable from user settings.
	 */
	function user_unset($varname = '')
	{
		$this->is_changed = 1;
		if ($varname == '')
		{
			/* no variable defined, clean all custom variables */
			$this->ar_user['user_settings'] = array();
		}
		else
		{
			/* remove a defined variable only */
			for (reset($this->ar_user['user_settings']); list($k, $v) = each($this->ar_user['user_settings']);)
			{
				if ($k == $varname)
				{
					unset($this->ar_user['user_settings'][$varname]);
				}
			}
		}
		return true;
	}
	/* Close user session. Save user settings if needed */
	function user_close()
	{
		if ($this->is_changed)
		{
			$this->_user_update();
		}
	}
	/* private function to update user settings */
	function _user_update()
	{
		$this->ar_user['user_settings'] = serialize($this->ar_user['user_settings']);
		unset($this->ar_user['is_remember'], $this->ar_user['ua']);
		$sql = gw_sql_update($this->ar_user, $this->db_table_users, '`id_user` = "'.$this->id_user.'"');
		$this->oDb->sqlExec($sql);
		$this->ar_user = array();
	}
	
	/**
	 * Remove session.
	 */
	function logout()
	{
		$sql = 'DELETE ';
		$sql .= 'FROM `'.$this->db_table_sessions.'` ';
		$sql .= 'WHERE `id_sess` = "'.$this->id_sess.'" ';
		$sql .= 'LIMIT 1';
		$this->oDb->sqlExec($sql);
		/* "You have logged out" */
		$this->error(3); 
	}

	/* */
	function user_get_time_seconds()
	{
		$t = ($this->user_get('gmt_offset') * 3600);
		if ($this->user_get('is_dst'))
		{
			$t += 3600;
		}
		return $t;
	}
	function user_get_time()
	{
		$t = $this->sys['time_now_gmt_unix'] + ($this->user_get('gmt_offset') * 3600);
		if ($this->user_get('is_dst'))
		{
			$t += 3600;
		}
		return $t;
	}
	/* */
	function auth_info($id_user = '', $username = '', $email = '', $password = '')
	{
		$sql = '';
		if ($id_user)
		{
			/* Select by id_user */
			$sql = sprintf('SELECT `id_user` FROM `%s` WHERE `id_user` = "%s"',
							$this->db_table_users, gw_text_sql($id_user)
					);
		}
		elseif ($username)
		{
			/* Select by login */
			$sql = sprintf('SELECT `id_user` FROM `%s` WHERE login = "%s"',
							$this->db_table_users, gw_text_sql($username)
					);
		}
		elseif ($email)
		{
			/* Select by email */
			$sql = sprintf('SELECT `id_user` FROM `%s` WHERE `user_email` = "%s"',
							$this->db_table_users, gw_text_sql($email)
					);
		}
		elseif ($password)
		{
			/* Select by password */
			$sql = sprintf('SELECT `id_user` FROM `%s` WHERE `password` = "%s"',
							$this->db_table_users, gw_text_sql($password)
					);
		}
		if ($sql != '')
		{
			return $this->oDb->sqlExec($sql);
		}
		return array();
	}

	/* Error handing */
	function error($error_code = 0)
	{
		
		/* Remove cookies on eny error */
		setcookie( $this->sid.$this->sys['token'], $this->id_sess, $this->time_now - 2, $this->sys['server_dir'] );
		setcookie( $this->sid.'r'.$this->sys['token'], 1, $this->time_now - 2, $this->sys['server_dir'] );
		$msg = '';
		switch ($error_code)
		{
			case 1:
				/* Session expired */
				$msg = $this->msg_1;
				$msg .= '<br />';
				$msg .= sprintf( $this->msg_5, '<strong>'.intval(($this->time_now_gmt_unix - $this->time_changed) / 60).'</strong>.' );
				$msg .= ' ';
				$msg .= sprintf( $this->msg_6, '<strong>'.intval($this->int_timeout / 60).'</strong>.' );
				$this->uri = base64_encode($_SERVER['QUERY_STRING']);
			break;
			case 2:
				$msg = $this->msg_2;
				$this->id_sess = '';
			break;
			case 3:
				/* Logged out */
				$msg = $this->msg_3;
				$this->id_sess = '';
			break;
			default:
				/* Authorization required. Session expired or does not exist. */
				$msg = $this->msg_4;
				$this->id_sess = '';
				$this->uri = base64_encode($_SERVER['QUERY_STRING']);
			break;
		}
		/* Display HTML */
		$url = $this->url( $this->url_login. ($this->uri ? '?uri='.$this->uri : '') );
		@header('Content-Type: text/html; charset=utf-8');
		print '<html><head>';
		print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		print '<meta http-equiv="Refresh" content="'.$this->time_refresh.';url='.$url.'" />';
		print '</head><body>';
		print '<div><div style="font: 100% sans-serif; margin: 1em">';
		print $msg;
		print '<p><a href="'.$url.'">'.$this->oL->m('2_continue').'</a></p>';
		print '</div></div>';
		print '</body></html>';
		$this->user_close();
		exit;
	}
	
}
$sys['class_session'] = 'gw_session_1_9';

?>