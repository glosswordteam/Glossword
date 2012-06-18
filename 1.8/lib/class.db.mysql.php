<?php
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
// --------------------------------------------------------
/**
 * Database class.
 *
 * uses also: class gw_timer;
 * @version $Id: class.db.mysql.php 492 2008-06-13 22:58:27Z glossword_team $
 */
// --------------------------------------------------------
if (!defined('IS_CLASS_DB'))
{
	define('IS_CLASS_DB', 1);
	//
	define('IS_FREE_RESULT', 1);
	/* halt with message */
	define('ON_ERROR_HALT', 1);
	/* ignore errors quietly */
	define('ON_ERROR_IGNORE', 2);
	/* ignore error, but spit a warning */
	define('ON_ERROR_MSG', 3);
class gwtkDataBase
{
	var $host         = '';
	var $database     = '';
	var $user         = '';
	var $password     = '';
	var $connect_type = '';
	var $connect_time = 0;
	var $query_time   = 0;
	var $query_array  = array();
	var $on_error_default = ON_ERROR_MSG;
	var $max_queries_debug = 100;
	var $cnt_queries_debug = 0;
	
	/**
	 * Transparent query caching
	 */
	var $is_cache = 0;
	var $cache_lifetime = 5;
	var $is_print_events = 0;
	/**
	 * public: result array and current row number
	 */
	var $record   = array();
	var $row      = 0;
	var $errno    = 0;
	var $error    = '';
	/**
	 * private: Link and query handles
	 */
	var $link_id  = 0;
	var $query_id = 0;
	/**
	 * Calls Universal query-caching engine (is exist)
	 * by Dmitry Shilnikov <dev at glossword dot info>
	 */
	function setCache($dir = '')
	{
		if (defined("IS_CLASS_CACHE"))
		{
			global $oCh;
			$oCh = new gwtkCache;
			$oCh->setPath($dir);
			$this->is_cache = 1;
			$oCh->cache_lifetime = $this->cache_lifetime;
		}
	}
	/**
	 *
	 */
	function connect($db_host = '', $db_user = '', $db_password = '', $db_name = '', $is_pconnect = 0)
	{
		if ($db_host != '') $this->host = $db_host;
		if ($db_user != '') $this->user = $db_user;
		if ($db_password != '') $this->password = $db_password;
		if ($db_name != '') $this->database = $db_name;
		/* */
		if (!$this->link_id)
		{
			$this->database = trim(str_replace('`', '', $this->database));
			if (defined("GW_DEBUG_SQL_TIME") && defined("IS_CLASS_TIMER") && GW_DEBUG_SQL_TIME == 1)
			{
				$t = new gw_timer('q');
			}
			$connect_type = ($is_pconnect) ? 'mysql_pconnect' : 'mysql_connect';
			$this->connect_type = $connect_type . '('.$this->host.');';
			if (!$this->link_id = @$connect_type($this->host, $this->user, $this->password))
			{
				$this->halt("Could not " . $this->connect_type . " to the database server ($this->host, $this->user).", $this->on_error_default);
			}
			if (!@mysql_select_db($this->database))
			{
				@mysql_close($this->link_id);
				$this->halt('Could not select database ('.$this->database.').', $this->on_error_default);
			}
			@mysql_query('SET NAMES \'utf8\'', $this->link_id);
			if (defined("GW_DEBUG_SQL_TIME") && defined("IS_CLASS_TIMER") && GW_DEBUG_SQL_TIME == 1)
			{
				$this->connect_time = $t->end();
			}
		}
		return $this->link_id;
	} // end of connect();
	/**
	 * Post a query to database
	 */
	function query($query = '')
	{
		if ($query != '')
		{
			$time_end = 0;
			if (!$this->connect())
			{
				return false;
			}
			if (isset($this->query_id))
			{
				$this->free_result();
			}
			if (defined("GW_DEBUG_SQL_TIME") && defined("IS_CLASS_TIMER") && GW_DEBUG_SQL_TIME == 1)
			{
				$t = new gw_timer('q');
			}
			if (!$this->query_id = @mysql_query($query, $this->link_id))
			{
				$this->halt('Invalid SQL: ' . $query, $this->on_error_default);
			}
			if (defined("GW_DEBUG_SQL_TIME") && defined("IS_CLASS_TIMER") && GW_DEBUG_SQL_TIME == 1)
			{
				$time_end = $t->end();
				$this->query_time += $time_end;
			}
			if (sizeof($this->query_array) < $this->max_queries_debug 
				&& defined("GW_DEBUG_SQL_QUERY") 
				&& defined("IS_CLASS_TIMER") 
				&& GW_DEBUG_SQL_QUERY == 1)
			{
				// faster than htmlspecialchars()
				$this->query_array[] = sprintf('<strong>%1.5f</strong> %s', $time_end,  htmlspecialchars_ltgt($query) );
			}
			$this->cnt_queries_debug++;
			return $this->query_id;
		} // query is not empty
		return false;
	} // end of query();
	/**
	 *
	 */
	function isDuplicate($query = '')
	{
		if ($query != '')
		{
			if (!$this->query_id = @mysql_query($query, $this->link_id))
			{
				return preg_match("/Duplicate entry/i", @mysql_error($this->link_id));
			}
		}
		return false;
	}
	/**
	 *
	 */
	function next_record()
	{
		if (!$this->query_id)
		{
			$this->halt("next_record() called with no query pending.", $this->on_error_default);
			return 0;
		}
		$this->record = @mysql_fetch_assoc($this->query_id);
		$this->row   += 1;
		$stat = is_array($this->record);
		if (!$stat && IS_FREE_RESULT)
		{
			$this->free_result();
		}
		return $stat;
	} //
	/**
	 *
	 */
	function free_result($query_id = -1)
	{
		if ($query_id != -1)
		{
			$this->query_id = $query_id;
			if ($this->is_print_events)
			{
				print '<br/>DB: free ' . $query_id;
			}
		}
		return @mysql_free_result($this->query_id);
	} // end of free_result();
	/**
	 *
	 */
	function close()
	{
		if ($this->link_id)
		{
			if ($this->is_print_events)
			{
				print '<br>DB: close';
			}
			if ($this->query_id)
			{
				$this->free_result($this->query_id);
			}
			return @mysql_close($this->link_id);
		}
		else
		{
			return false;
		}
	} // end of close();
	/* private: error handling */
	## ------------------------------------------
	##
	function halt($msg, $errMode = 1)
	{
		$this->Error = @mysql_error($this->link_id);
		$this->Errno = @mysql_errno($this->link_id);
		if ($errMode == ON_ERROR_IGNORE) { return; }
		$this->haltmsg($msg);
		if ($errMode == ON_ERROR_HALT)
		{
			die("Session halted.");
		}
	}
	function haltmsg($msg)
	{
		echo '<div style="margin:3px 0;border:3px solid #EEE;font:10pt sans-serif;width:98%;overflow:hidden">',
			 '<dl><dt style="padding:0 1em;color:#C80"><strong>Database error</strong></dt> <dd>', substr($msg, 0, 1024), '<br /><em style="color:#888">(', strlen($msg), ' characters)</em></dd></dl>',
			 '<dl><dt style="padding:0 1em;color:#C08"><strong>MySQL Error</strong></dt> <dd>', $this->Errno, ' (', $this->Error,')</dd></dl></div>';
	}
	##
	## ------------------------------------------
	/* public: table locking */
	function lock($table, $mode = "WRITE")
	{
		$this->connect();
		$sql = 'LOCK TABLES ';
		if (is_array($table)) // many tables
		{
			while (list($k, $v) = each($table))
			{
				if (($k == "read") && ($k != 0))
				{
					$sql .= $v .' READ, ';
				}
				else
				{
					$sql .= $v .' ' . $mode . ', ';
				}
			}
			$sql = substr($sql, 0, -2);
		}
		else
		{
			$sql .= $table . ' ' . $mode;
		}
		// $this->query($sql);
		$res = @mysql_query($sql, $this->link_id);
		if (!$res)
		{
			$str = is_array($table) ? implode(', ', $table) : $table;
			$this->halt("Can't lock($str, $mode)");
			return 0;
		}
		return $res;
	}
	function unlock()
	{
		$this->connect();
		$res = @mysql_query("UNLOCK TABLES", $this->link_id);
		if (!$res)
		{
			$this->halt("Can't unlock().");
			return 0;
		}
		return $res;
	}
	//
	function num_fields()
	{
		return @mysql_num_fields($this->query_id);
	}
	function num_rows()
	{
		return @mysql_num_rows($this->query_id);
	}
	/* public: evaluate the result (size, width) */
	function affected_rows()
	{
		return (@mysql_affected_rows($this->link_id) > 0) ? mysql_affected_rows($this->link_id) : 0;
	}
	/* public: shorthand notation */
	function r($name)
	{
		return isset($this->record[$name]) ? $this->record[$name] : 0;
	}
	/* Get the list of databases */
	function get_databases()
	{
		$this->database = trim(str_replace('`', '', $this->database));
		if ($this->link_id)
		{
			$db_list = mysql_list_dbs($this->link_id);
			$ar = array();
			while ($row = mysql_fetch_array($db_list))
			{
				$ar[$row['Database']] = $row['Database'];
			}
		}
		else
		{
			$sql = 'SHOW DATABASES';
			$this->query($sql);
			$ar = array();
			if ($this->query_id)
			{
				while ($row = mysql_fetch_array($this->query_id, MYSQL_NUM))
				{
					$ar[$row[0]] = $row[0];
				}
			}
		}
		return $ar;
	}
	/* Get the list of tables from current database */
	function table_names($tablename = '')
	{
		$this->database = trim(str_replace('`', '', $this->database));
		$sql = 'SHOW TABLES FROM `' . $this->database.'`';
		$ar = array();
		if ($tablename != '')
		{
			$sql = 'SHOW TABLES FROM `' . $this->database . '` LIKE "' . $tablename . '"';
		}
		$this->query($sql);
		while ($row = mysql_fetch_array($this->query_id, MYSQL_NUM))
		{
			$ar[] = $row[0];
		}
		return $ar;
	}
	/*
	 * Get all information about table
	 * works with MySQL version 3.23.10 or above
	 */
	function table_info($tablename = '')
	{
		if ($tablename != '')
		{
			$this->database = trim(str_replace('`', '', $this->database));
			$this->query('SHOW TABLE STATUS FROM `' . $this->database . '` LIKE "' . $tablename . '";');
			if ($this->query_id)
			{
				return mysql_fetch_assoc($this->query_id);
			}
		}
		return array();
	}
	/* get last known maximum value */
	function MaxId($tablename, $field = 'id')
	{
		if ($tablename != '')
		{
			$this->query('SELECT MAX(' . $field . ') AS n FROM `' . $tablename . '`;');
			$arID = mysql_fetch_assoc($this->query_id);
			return ($arID['n'] + 1);
		}
		return 1;
	}
	// New from Glossword 2.0
	/**
	 * All queries for cache
	 *
	 * @access  public
	 */
	function sqlRun($q, $cache_prefix = '')
	{
		return ( $this->_sql_is_cached($q, $cache_prefix) ) ? $this->_sql_return($q, $cache_prefix) : $this->sqlExec($q, $cache_prefix, 1);
		/* 02 feb 2006: Memory cache 
		global $gw_ar_cache_sql;
		$qkey = sprintf("%u", crc32($q));
		if (!isset($gw_ar_cache_sql['cnt']))
		{
			$gw_ar_cache_sql['cnt'] = 0;
		}
		if (isset($gw_ar_cache_sql[$qkey]))
		{
			$gw_ar_cache_sql['cnt']++;
			return unserialize($gw_ar_cache_sql[$qkey]);
		}
		else
		{
			$o = $this->_sql_is_cached($q, $cache_prefix) 
				? ($this->_sql_return($q, $cache_prefix)) 
				: ($this->sqlExec($q, $cache_prefix, 1));
			$gw_ar_cache_sql[$qkey] = serialize($o);
			return $o;
		}
		*/
	}
	/**
	 * Executes query without cache
	 *
	 * @access  public
	 * @see gw_fixslash();
	 */
	function sqlExec($q, $cache_prefix = '', $is_cache_def = 0)
	{
		// on error: Only `SELECT' query returns empty array, other returns FALSE
		$empty_value = preg_match("/^SELECT/", $q) ? array() : false;
		//
		// Connect and post query
		$this->query($q);
		//
		// on error: returned status for UPDATE, DELETE etc. (not SELECT)
		if (($this->affected_rows() > 0) && !$empty_value)
		{
			$empty_value = true;
		}
		// on error: DELETE returns true when no affected rows
		elseif (($this->affected_rows() == 0) && preg_match("/^(DELETE|DROP|CREATE|ALTER)/", $q))
		{
			$empty_value = true;
		}
		$ar = array();

		// Is here any returned data row?
		if ($this->num_rows() > 0)
		{
			while ($this->next_record())
			{
				$ar[] = $this->record;
			}
		}
		// Fix quotes in array
		gw_fixslash($ar, 'runtime');
		//
		// Put query into cache
		if ($this->is_cache && $is_cache_def && preg_match("/^SELECT/", $q))
		{
			global $oCh;
			$oCh->setKey($q, $cache_prefix);
			$oCh->save($ar, 'array');
		}
		//
		if ( empty($ar) ) { $ar = $empty_value; }
		return $ar;
	}
	/**
	 * @access  private
	 */
	function _sql_is_cached($q, $cache_prefix = '')
	{
		// only SELECT can be cached
		$is_cache = preg_match("/^SELECT/", $q) ? $this->is_cache : 0;
		if ($is_cache)
		{
			global $oCh;
			$oCh->setKey($q, $cache_prefix);
			$is_cache = $oCh->checkout();
		}
		return $is_cache;
	}
	/**
	 * @access  private
	 */
	function _sql_return($q, $cache_prefix = '')
	{
		global $oCh;
		$oCh->setKey($q, $cache_prefix);
		return $oCh->load('array');
	}
	/**
	 * Builds query "LIMIT n,n" for database
	 *
	 * @param    int   $total    total number of items
	 * @param    int   $page     current page number
	 * @param    int   $perpage  items per page
	 * @return   string  part of database query
	 */
	function prn_limit($total, $page, $perpage)
	{
		$numpages = ceil($total / $perpage);
		for ($i = 1; $i <= $numpages; $i++)
		{
			$position = ($i * $perpage);
			if ($i == ($page - 1))
			{
				return ' LIMIT '.$position.', '.$perpage;
			}
		}
		return ' LIMIT 0, '.$perpage;
	}

} // end of class
} // defined IS_CLASS_DB
//
class gwtkDb extends gwtkDataBase
{
	var $host     = GW_DB_HOST;
	var $user     = GW_DB_USER;
	var $password = GW_DB_PASSWORD;
	var $database = GW_DB_DATABASE;
	var $is_print_events = 0;
}
?>