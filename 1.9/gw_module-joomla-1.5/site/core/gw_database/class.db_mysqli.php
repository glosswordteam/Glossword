<?php
/**
 * $Id$
 * Class to access database. Extends a general database class.
 */
class site_database_mysqli extends site_database
{
	private $_count_string = 'SELECT COUNT(*) AS ';

	/* database specific random keyword */
	private $_random_keyword = ' RAND()';

	function __construct($ar_params)
	{
        $this->init($ar_params);
	}
	/**
	 * Creates non-persistent database connection.
	 */
	protected function db_connect()
	{
	    return @mysqli_connect($this->hostname, $this->username, $this->password, $this->database, $this->port);
	}
	/**
	 * Creates persistent database connection.
	 */
	protected function db_pconnect()
	{
		return $this->db_connect();
	}
	/**
	 * Selects the database.
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	protected function db_select()
	{
	    return @mysqli_select_db($this->id_conn, $this->database);
	}
	/**
	 * Sets a client character set.
	 */
	protected function db_set_charset($charset, $collation)
	{
		return @mysqli_query("SET NAMES '".$this->escape_str($charset)."' COLLATE '".$this->escape_str($collation)."'", $this->conn_id);
	}
	/**
	 * Extends JOIN size
	 */
	protected function db_set_big_selects()
	{
		return @mysqli_query("SET OPTION SQL_BIG_SELECTS=1", $this->conn_id);
	}
	/**
	 * Returns version number.
	 */
	protected function _version()
	{
		return 'SELECT version() AS version';
	}
	/**
	 * Execute the query
	 *
	 * @access	private called by the base class
	 * @param	string	an SQL query
	 * @return	resource
	 */
	protected function _execute($sql)
	{
		$sql = $this->_prep_query($sql);
		return @mysqli_query($this->id_conn, $sql);
	}
	/**
	 * Prepare the query
	 */
	protected function _prep_query($sql)
	{
	    /* Make DELETE return the number of affected rows */
		if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql))
        {
		    $sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
		}
		return $sql;
	}



	/**
	 * Escapes String
	 */
	public function escape_str( $str )
	{
		if ( is_array($str) )
		{
			foreach($str as $k => $v)
			{
				$str[$k] = $this->escape_str($v);
			}
			return $str;
		}
		if ( function_exists('mysqli_real_escape_string') && is_resource( $this->id_conn ) )
		{
			return mysqli_real_escape_string( $this->id_conn, $str );
		}
		elseif ( function_exists('mysqli_escape_string') )
		{
			return mysqli_escape_string( $this->id_conn, $str );
		}
		else
		{
			return addslashes($str);
		}
	}
	/* */
	public function affected_rows()
	{
		return @mysqli_affected_rows($this->conn_id);
	}

	/**
	 * "Count All" query. Generates a platform-specific query string that counts all records
	 * in the specified database.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function count_all( $table = '' )
	{
		if ( $table == '' )
		{
			return 0;
		}
		$query = $this->query( $this->_count_string . $this->_protect_identifiers('numrows'). ' FROM ' . $this->_protect_identifiers($this->dbprefix.$table));
		$row = $query->row();
		return (int) $row->numrows;
	}
	/* */
	function _show_table_status( $tablename = FALSE, $is_exact = FALSE )
	{
		$sql = 'SHOW TABLE STATUS FROM `'.$this->database.'`';
		if ( $this->dbprefix != '' ) 
		{
			 $tablename = $this->dbprefix.$tablename;
		}
		if ( $tablename )
		{
			if ( $is_exact )
			{
				$sql .= ' LIKE \''.$tablename.'\'';
			}
			else
			{
				$sql .= ' LIKE \''.$tablename.'%\'';
			}
		}
		return $sql;
	}
	/**
	 * List table query.
	 * Generates a platform-specific query string so that the table names can be fetched
	 */
	function _list_tables( $prefix_limit = FALSE)
	{
		$sql = 'SHOW TABLES FROM `'.$this->database.'`';
		if ($prefix_limit !== FALSE && $this->dbprefix != '')
		{
			$sql .= ' LIKE \''.$this->dbprefix.'%\'';
		}
		return $sql;
	}
	/**
	 * Show column query.
	 */
	function _list_columns($table = '')
	{
		return 'SHOW COLUMNS FROM '.$this->_escape_table($table);
	}
	/**
	 * Field data query. Generates a platform-specific query so that the column data can be retrieved
	 */
	function _field_data($table)
	{
		return 'SELECT * FROM '.$this->_escape_table($table).' LIMIT 1';
	}
	/**
	 * The error message string
	 */
	protected function _error_message()
	{
		return mysqli_error($this->id_conn);
	}
	/**
	 * The error message number
	 */
	protected function _error_number()
	{
		return mysqli_errno($this->id_conn);
	}
	/**
	 * Escapes Table Name. Some DBs will get cranky unless periods are escaped.
	 */
	protected function _escape_table($table)
	{
		if (strpos($table, '.') !== FALSE)
		{
			$table = '`' . str_replace('.', '`.`', $table) . '`';
		}
		return $table;
	}
	/**
	 * Protect Identifiers. Adds backticks if appropriate based on db type
	 */
	protected function _protect_identifiers($item, $first_word_only = FALSE)
	{
		if (is_array($item))
		{
			$escaped_array = array();
			foreach($item as $k => $v)
			{
				$escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v, $first_word_only);
			}
			return $escaped_array;
		}
		/* This function may receive "item1 item2" as a string,
		   so it should be parsed as "`item1` `item2`" and as "`item1 item2`"
		 */
		if (ctype_alnum($item) === FALSE)
		{
			if (strpos($item, '.') !== FALSE)
			{
				$aliased_tables = implode(".",$this->ar_aliased_tables).'.';
				$table_name =  substr($item, 0, strpos($item, '.')+1);
				$item = (strpos($aliased_tables, $table_name) !== FALSE) ? $item = $item : $this->dbprefix.$item;
			}
			/* This function may get "field >= 1", and need it to return "`field` >= 1" */
			$lbound = ($first_word_only === TRUE) ? '' : '|\s|\(';
			$item = preg_replace('/(^'.$lbound.')([\w\d\-\_]+?)(\s|\)|$)/iS', '$1`$2`$3', $item);
		}
		else
		{
			return "`{$item}`";
		}
		$exceptions = array('AS', '/', '-', '%', '+', '*', 'OR', 'IS');
		foreach ($exceptions as $exception)
		{
			if (stristr($item, " `{$exception}` ") !== FALSE)
			{
				$item = preg_replace('/ `('.preg_quote($exception).')` /i', ' $1 ', $item);
			}
		}
		return $item;
	}
	/**
	 * From Tables. Groups FROM tables so there is no confusion
	 * about operator precedence in harmony with SQL standards.
	 */
	protected function _from_tables($tables)
	{
		if ( !is_array($tables))
		{
			$tables = array($tables);
		}
		return '('.implode(', ', $tables).')';
	}

	/**
	 * Generates a platform-specific insert string from the supplied data.
	 */
	function _insert($table, $keys, $values)
	{
		/* 26 june 2007: multiple INSERTs */
		if (is_array($values[0]))
		{
			$keys = array_keys($values[0]);
			$ar_sql = array();
			foreach ($values as $key => $arV)
			{
				foreach ($arV as $k => $v)
				{
					$arV[$k] = $this->escape($v);
				}
				$ar_sql[] = '('. implode(', ', $arV) .')';
				unset($values[$key]);
			}
			return "INSERT INTO ".$this->_escape_table($table)." (".implode(', ', $keys).") VALUES " . implode(', ', $ar_sql);
		}
		else
		{
			return "INSERT INTO ".$this->_escape_table($table)." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
		}
	}

	/**
	 * Generates a platform-specific UPDATE string from the supplied data.
	 */
	function _update($table, $values, $where, $order_by = array(), $limit = FALSE)
	{
		foreach ($values as $key => $val)
		{
			if ($val === false)
			{
				$valstr[] = $key;
			}
			else
			{
				$valstr[] = $key." = ".$val;
			}
		}
		$limit = ( ! $limit) ? '' : ' LIMIT '.$limit;
		$order_by = (count($order_by) >= 1) ? ' ORDER BY '.implode(", ", $order_by) : '';
		$sql = 'UPDATE '.$this->_escape_table($table).' SET '.implode(', ', $valstr);
		$sql .= ($where != '' AND count($where) >= 1) ? ' WHERE '.implode(" ", $where) : '';
		$sql .= $order_by.$limit;
		return $sql;
	}

	/**
	 * Generates a platform-specific TRUNCATE string from the supplied data.
	 */
	function _truncate($table)
	{
		return 'TRUNCATE '.$this->_escape_table($table);
	}

	/**
	 * Generates a platform-specific DELETE string from the supplied data.
	 */
	protected function _delete($table, $where = array(), $like = array(), $limit = FALSE)
	{
		$conditions = '';
		if (count($where) > 0 OR count($like) > 0)
		{
			$conditions = "\nWHERE ";
			$conditions .= implode("\n", $this->ar_where);
			if (count($where) > 0 && count($like) > 0)
			{
				$conditions .= ' AND ';
			}
			$conditions .= implode("\n", $like);
		}

		$limit = ( !$limit) ? '' : ' LIMIT '.$limit;

		return 'DELETE FROM '.$table.$conditions.$limit;
	}

	/**
	 * Generates a platform-specific LIMIT clause.
	 */
	protected function _limit($sql, $limit, $offset)
	{
		if ($offset == 0)
		{
			$offset = '';
		}
		else
		{
			$offset .= ", ";
		}
		return $sql.'LIMIT '.$offset.$limit;
	}


	/**
	 * Closes connection to the database.
	 */
	protected function _close($id_conn)
	{
		@mysqli_close($id_conn);
	}
}
/**
 * Class to manage results.
 */
class site_database_result_ext extends site_database_result
{
	public function num_rows()
	{
		return @mysqli_num_rows( $this->id_result );
	}
	public function num_fields()
	{
		return @mysqli_num_fields( $this->id_result );
	}
	public function list_fields()
	{
		$field_names = array();
		while ( $field = mysqli_fetch_field( $this->id_result ) )
		{
			$field_names[] = $field->name;
		}
		return $field_names;
	}
	public function free_result()
	{
		if ( is_resource( $this->id_result ) )
		{
			mysqli_free_result( $this->id_result );
			$this->id_result = FALSE;
		}
	}
	public function _data_seek($n = 0)
	{
		return mysqli_data_seek($this->id_result, $n);
	}
	public function _fetch_assoc()
	{
		return mysqli_fetch_assoc($this->id_result);
	}
	public function _fetch_object()
	{
		return mysqli_fetch_object($this->id_result);
	}
}

?>