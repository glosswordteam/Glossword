<?php
/**
 * $Id$
/**
 * Session class.
 * Uses ActiveRecord for SQL-queries.
 */
class site_session_2_0
{
	public $oDb;
	public $oTkit;

	/* Field name */
	public $db_user_settings = 'user_settings';

	/* Guest User ID */
	public $id_guest = 1;
	public $ar_permissions = array();
	/* User settings */
	public $is_changed = 0;
	public $is_closed = 0;

	/* Used for creating Session ID */
	public $is_use_remote_ip = 0;
	public $is_use_remote_ua = 0;
	public $str_secret = 'wefrty';
	public $is_use_dynamic_sid = 0;

	public $ar_sess = array();
	public $ar_user = array();
	public $id_user;
	public $id_sess;
	public $sid;
	public $url_login;
	public $url_append_mode;
	public $uri;
	public $remote_ua;
	public $remote_ip;
	/* Used for cookie */
	public $server_dir;
	/* Garbage collect probability in percent */
	public $gc_probability = 4;
	/* Session will be expired in `n` seconds (3600 = 1 hour) */
	public $int_timeout = 3600;
	public $datetime_changed;
	public $time_req;
	public $datetime_req;
	/* Times used in META tag */
	public $time_refresh = 2;
	/* Messages */
	public $msg_1 = 'Session expired in case of inactivity.';
	public $msg_2 = 'Authorization required. Session does not exist.';
	public $msg_3 = 'You have logged out.';
	public $msg_4 = 'Authorization required. Session expired or does not exist.';
	public $msg_5 = 'Passed: %s minutes';
	public $msg_6 = 'Time to idle: %s minutes';
	/* Database table name */
	public $db_table_users, $db_table_sessions, $db_table_groups;

	public function load_settings()
	{
		if ( isset( $this->oTkit ) )
		{
			$vars = array(
				#'msg_1' => $this->oTkit->_( 1342 ),
				#'msg_2' => $this->oTkit->_( 1343 ),
				#'msg_3' => $this->oTkit->_( 1344 ),
				#'msg_4' => $this->oTkit->_( 1345 ),
				#'msg_5' => $this->oTkit->_( 1346 ),
				#'msg_6' => $this->oTkit->_( 1347 ),
				'db_table_users' => 'users',
				'db_table_sessions' => 'sessions',
				'db_table_groups' => 'usergroups',
				'time_refresh' => 1,
				'int_timeout' => 3600 * 12,
			);
		}
		else
		{
			$vars = array(
				'db_table_users' => 'users',
				'db_table_sessions' => 'sessions',
				'db_table_groups' => 'usergroups',
				'time_refresh' => 1,
				'int_timeout' => 3600 * 12,
			);
		}
		foreach ($vars as $k => $v)
		{
			$this->$k = $v;
		}
	}
	/* Access map per action */
	/* See also users_admin.php */
	public function get_access_names()
	{
		return array(
			'import' => 0,
			'export' => 0,
			'items-own' => 0,
			'items' => 0,
			'dicts-own' => 0,
			'dicts' => 0,
			'comments-own' => 0,
			'comments' => 0,
			'profile' => 0,
			'password' => 0,
			'login' => 0,
			'email' => 0,
			'pm' => 0,
			'users' => 0,
			'sys-mnt' => 0,
			'sys-settings' => 0
		);
	}
	/* */
	public function is_guest()
	{
		return ( !$this->id_user || $this->id_user == $this->id_guest );
	}
	/**
	 * Require authorization to continue.
	 */
	public function is_auth_needed()
	{
		return $this->auth_needed(0);
	}
	/* */
	public function auth_needed($is_redirect = 1)
	{
		if (!$this->id_user || $this->id_user == $this->id_guest)
		{
			/* Authorization required message */
			if ( $is_redirect )
			{
				$this->error();
			}
			else
			{
				return true;
			}
		}
		return false;
	}
	
	/* */
	public function is_perm($access_name = '')
	{
		if (!$access_name)
		{
			return $this->ar_permissions;
		}
		if (isset($this->ar_permissions[strtolower($access_name)])
			&& $this->ar_permissions[strtolower($access_name)])
		{
			return true;
		}
		else
		{
			/* Authorization required */
			#$this->error();
		}
		/* Permission not found */
		return false;
	}
	
	/* Alias for is_perm */
	public function is($access_name = '')
	{
	    return $this->is_perm($access_name);
	}

	/* One function that manages sessions */
	public function sess_init($id_sess = 0)
	{
		if ($id_sess)
		{
			/* Search Session ID in database. Load user settings also */
			$this->id_sess = $id_sess;
			$this->oDb->select('s.*, u.*, g.group_perm, g.group_name');
			$this->oDb->from(array($this->db_table_sessions.' AS s', $this->db_table_users.' AS u',  $this->db_table_groups.' AS g'));
			$this->oDb->where(array(
				'u.id_user = s.id_user' => NULL,
				'u.id_group = g.id_group' => NULL,
				's.id_sess' => $id_sess)
			);
			$this->oDb->limit(1);
			$query = $this->oDb->get();
			$arSql = $query->result_array();
			$arSql = isset($arSql[0]) ? $arSql[0] : array();
			if (empty($arSql))
			{
				/* Session ID not found */
				/* Remove cookie */
				setcookie( $this->sid, 'NULL', $this->time_req - 86400, '/' );
				/* Start Guest session then */
				$this->sess_insert( $this->id_guest );
			}
			else
			{
#				prn_r( $this->int_timeout );
#				prn_r( ($this->time_req - strtotime($arSql['mdate'])) );
				/* Session expired */
				if (($this->time_req - strtotime($arSql['mdate'])) > $this->int_timeout)
				{
					$this->datetime_changed = $arSql['mdate'];
					$this->id_sess = 0;
					/* Remove cookie */
					setcookie( $this->sid, 'NULL', $this->time_req - 86400, '/' );
					/* Start Guest session */
					$this->sess_insert( $this->id_guest );
					return;
					#$this->error(1);
				}
				$this->id_user = $arSql['id_user'];
				$this->ar_sess['mdate'] = $arSql['mdate'];
				$this->ar_sess['ip'] = $this->remote_ip;
				unset($arSql['id_sess'], $arSql['mdate'], $arSql['ip']);
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
		if ( $id_user == $this->id_guest )
		{
			/* Do not start real session, use virtual session instead */
			$this->id_sess = 0;
			$this->ar_sess['ip'] = $this->remote_ip;
			$this->ar_sess['mdate'] = $this->datetime_req;
			/* Load Guest user settings */
			$this->user_start( $id_user );
		}
		else
		{
			/* On login */
			$ar['id_sess'] = $this->id_sess = $this->make_session_id();
			$ar['ip'] = $this->remote_ip;
			$ar['ua'] = $this->remote_ua;
			$ar['id_user'] = $id_user;
			$ar['is_remember'] = $this->is_remember;
			$ar['mdate'] = $this->datetime_req;
			$this->oDb->insert( $this->db_table_sessions, $ar );
		}
	}
	/* Construct session unique number */
	function make_session_id()
	{
		$str_remote_ip = ($this->is_use_remote_ip) ? $this->remote_ip : '';
		$str_remote_ua = ($this->is_use_remote_ua) ? $this->remote_ua : '';
		return md5($this->url_login . $this->time_req . $str_remote_ip . $str_remote_ua . mt_rand() . $this->str_secret);
	}
	/* */
	public function url($url)
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
	public function sess_close()
	{
		if ( $this->is_closed ) { return; }
		$this->is_closed = 1;
		$this->user_close();
		$this->ar_sess['mdate'] = $this->datetime_req;
		$id_sess = $this->id_sess;
		/* User ID could not be changed during session */
		if ( $this->id_sess && $this->id_user != $this->id_guest )
		{
			/* 7 Jan 2010: Switchable dynamic session number */
			if ( $this->is_use_dynamic_sid )
			{
				/* 12 Feb 2009: Dynamic session number */
				$this->ar_sess['id_sess'] = $this->make_session_id();
				$id_sess = $this->ar_sess['id_sess'];
			}
			$this->ar_sess['ip'] = $this->ar_sess['ip'];
			$this->oDb->where( 'id_sess', $this->id_sess );
			$this->oDb->update( $this->db_table_sessions, $this->ar_sess );
		}
		/* Continue cookie */
		@setcookie( $this->sid, $id_sess, $this->time_req + $this->int_timeout, '/' );
	}
	/* */
	public function user_start($id_user)
	{
		$this->id_user = $id_user;
		/* No session or guest user */
		if ( empty($this->ar_user) )
		{
			$this->user_load_values( $this->id_user, 'merge' );
		}
		/* $this->ar_user should exist now */
		if ( isset($this->ar_user['group_perm']) )
		{
			$this->user_register_permissions( $this->ar_user['group_perm'] );
		}
#prn_r( $this->ar_sess, 'ar_sess' );
#prn_r( $this->ar_user, 'ar_user'  );
#prn_r( $this->id_user, 'id_user'  );
	}
	/* */
	public function user_load_values($id_user, $mode = 'single')
	{
		$this->oDb->select( 'u.*, g.group_perm, g.group_name' );
		$this->oDb->from( array( $this->db_table_users.' AS u', $this->db_table_groups.' AS g') );
		$this->oDb->where( array( 'u.id_user' => $id_user, 'u.id_group = g.id_group' => NULL) );
		$this->oDb->limit( 1 );
		$query = $this->oDb->get();
		if ( !$query ){ return array(); }
		$arSql = $query->result_array();
		$arSql = isset($arSql[0]) ? $arSql[0] : array();
		/* No valid user found */
		if ( empty($arSql) && $mode == 'merge' )
		{
			/* No settings found. Protect againsts endless loop `!= 1`*/
			if ( $id_user != $this->id_guest )
			{
				$this->user_load_values( $this->id_guest, 'merge' );
			}
		}
		else if (!empty($arSql))
		{
			if ($mode == 'merge')
			{
				/* Merge received settings into the class */
				$this->id_user = $arSql['id_user'];
				$this->ar_sess['mdate'] = $this->datetime_req;
				$this->ar_sess['ip'] = $this->remote_ip;
				unset( $arSql['id_sess'], $arSql['mdate'], $arSql['ip'] );
				$arSql[$this->db_user_settings] = @unserialize( $arSql[$this->db_user_settings] );
				$this->ar_user =& $arSql;
			}
			else
			{
				/* Return only an array with settings */
				$arSql[$this->db_user_settings] = @unserialize( $arSql[$this->db_user_settings] );
				return $arSql;
			}
		}
	}
	/**
	 * Sets new permissions for usergroup.
	 * Usage:
	 * $oSess->group_new_permissions(4, 1);
	 * 
	 * @param integer $id_group Usergroup
	 * @param integer $is_allow Permission status [0 - deny, 1 - allow]
	 */
	public function group_new_permissions($id_group, $is_allow = 0)
	{
		if (!$id_group){ return; }
		$ar1 = $this->get_access_names();
		$ar2 = array();
		foreach ($ar1 as $k => $v)
		{
			$ar2[$k] = $is_allow;
		}
		$this->oDb->update($this->db_table_groups, array('group_perm' => serialize($ar2)), array('id_group' => $id_group));
	}
	/* */
	public function user_register_permissions($sp)
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
			$this->ar_permissions[strtolower($k)] = $v;
		}
	}
	/**
	 * Adds a variable to secondary user settings.
	 */
	public function user_set($varname, $value = '')
	{
		$this->is_changed = 1;
		$this->ar_user[$this->db_user_settings][$varname] = $value;
	}
	/**
	 * Adds a variable to primary user settings.
	 */
	public function user_set_val($varname, $value = '')
	{
		if ($varname == 'user_settings'){ return; }
		$this->is_changed = 1;
		$this->ar_user[$varname] = $value;
	}
	/**
	 * Gets user settings.
	 */
	public function user_get($varname = '')
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
			if (is_array($this->ar_user['user_settings']))
			{
				for (reset($this->ar_user['user_settings']); list($k, $v) = each($this->ar_user['user_settings']);)
				{
					if ($k == $varname)
					{
						return $v;
					}
				}
			}
		}
		return false;
	}
	/**
	 * Removes a variable from user settings.
	 */
	public function user_unset($varname = '')
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
	public function user_close()
	{
		if ($this->is_changed)
		{
			$this->_user_update();
		}
	}
	/* private function to update user settings */
	private function _user_update()
	{
		$this->ar_user['user_settings'] = serialize($this->ar_user['user_settings']);
		unset($this->ar_user['group_perm']);
		unset($this->ar_user['group_name']);
		unset($this->ar_user['is_remember'], $this->ar_user['ua'], $this->ar_user['sess_settings']);
		
		$this->oDb->update($this->db_table_users, $this->ar_user, array('id_user' => $this->id_user));
		$this->ar_user = array();
	}

	/**
	 * Remove session.
	 */
	public function logout()
	{
		$this->oDb->delete($this->oSess->db_table_sessions, array('id_user' => $this->oSess->id_user));
		$this->oDb->limit(1);
		/* "You have logged out" */
		$this->error(3);
	}
	/* */
	public function user_get_time_seconds()
	{
		$t = ($this->user_get('gmt_offset') * 3600);
		if ($this->user_get('is_dst'))
		{
			$t += 3600;
		}
		return $t;
	}
	public function user_get_time()
	{
		$t = $this->time_gmt + ($this->user_get('gmt_offset') * 3600);
		if ($this->user_get('is_dst'))
		{
			$t += 3600;
		}
		return $t;
	}
	/* */
	public function auth_info($id_user = '', $username = '', $email = '', $password = '')
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
	private function error($error_code = 0)
	{
		/* Remove cookies on eny error */
		setcookie( $this->sid.$this->str_secret, 'NULL', $this->time_req - 86400, '/' );
		setcookie( $this->sid.'r'.$this->str_secret, 'NULL', $this->time_req - 86400, '/' );
		$msg = '';
		switch ($error_code)
		{
			case 1:
				/* Session expired */
				$msg = $this->msg_1;
				$msg .= '<br />';
				$msg .= sprintf( $this->msg_5, '<strong>'.intval(($this->time_gmt - strtotime($this->datetime_changed)) / 60).'</strong>.' );
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
		print '<div><div style="font: 120% sans-serif; margin: 1em">';
		print $msg;
		print '<p><a href="'.$url.'">'.$this->oTkit->m(1072).'</a></p>';
		print '</div></div>';
		print '</body></html>';
		$this->user_close();
		exit;
	}

}
?>