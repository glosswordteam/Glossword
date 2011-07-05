<?php
/**
 * $Id$
 * A general database class.
 */
class site_database extends site_database_active_record
{
	private $query_time = 0;
	private $query_count = 0;
	private $ar_queries = array();
	protected $id_conn = false;
	/* Change prefix for a table before running query */
	private $swap_pre = '';
	
	/* */
	public function __construct($ar_params)
	{
        $this->init( $ar_params );
	}
	/* Sets default values */
	public function init( $ar_params )
	{
		$ar_defaults = array(
			'hostname'	=> '',
			'database'	=> '',
			'port'		=> '3306',
			'username'	=> '',
			'password'	=> '',
			'dbprefix'	=> '',
			'is_debug'	=> FALSE,
			'is_debug_q'=> FALSE,
			'id_conn'	=> FALSE,
			'is_pconnect'	=> FALSE,
			'is_active_record'	=> FALSE,
		);
		
		foreach ($ar_defaults as $k => $v)
		{
			$this->$k = isset($ar_params[$k]) ? $ar_params[$k] : $v;
		}

		/* Do not connect to already connected database */
		if ( is_resource( $this->id_conn ) || is_object( $this->id_conn ) )
		{
			return TRUE;
		}

		/* Connect to the database */
		$time_start = list($sm, $ss) = explode(' ', microtime());
		
		$this->id_conn = ($this->is_pconnect === FALSE) ? $this->db_connect() : $this->db_pconnect();

		/* */
		$time_end = list($em, $es) = explode(' ', microtime());
		
		$this->ar_queries[] = '<strong>'.sprintf("%1.5f ", (($em + $es) - ($sm + $ss))).'</strong> CONNECT';

		/* Check for errors */
		if ( !$this->id_conn )
		{
			if ( $this->is_debug )
			{
				$this->display_error( 'db_unable_to_connect' );
			}
			return FALSE;
		}

		/* Select the database */
		if ( $this->database != '' )
		{
			if ( !$this->db_select() )
			{
				if ( $this->is_debug )
				{
					$this->display_error( 'db_unable_to_select' );
				}
				return FALSE;
			}
		}
		
		unset( $this->password );
	}
	/**
	 * Returns Database Version Number.  Returns a string containing the
	 * version of the database being used
	 */
	public function version()
	{
		if (FALSE === ($sql = $this->_version()))
		{
			if ($this->is_debug)
			{
				return $this->display_error('db_unsupported_function');
			}
			return FALSE;
		}
		$query = $this->query( $sql );
		$row = $query->row();
		return $row->version;
	}
	
	/**
	 * Executes the query.
	 */
	public function query( $sql )
	{
		$max_query_len = 2048;
		if ($sql == '')
		{
			if ($this->is_debug)
			{
				return $this->display_error('db_invalid_query');
			}
			return FALSE;
		}

		/* Verify table prefix and replace if necessary */
		if ( ($this->dbprefix != '' AND $this->swap_pre != '') && ($this->dbprefix != $this->swap_pre) )
		{
			$sql = preg_replace("/(\W)".$this->swap_pre."(\S+?)/", "\\1".$this->dbprefix."\\2", $sql);
		}

		/* Do not run queries in debug mode */
		if ( $this->is_debug_q )
		{
			$this->ar_queries[] = '<strong>-.-----</strong> '.htmlspecialchars($sql).' ';
			return false;
		}

		/* Start the Query Timer */
		$time_start = list($sm, $ss) = explode(' ', microtime());
		
		/* Run the Query */
		if ( ( $this->id_result = $this->simple_query($sql) ) === false )
		{
			$len = strlen( $sql );
			$sql_display = $sql;
			if ( $len > $max_query_len )
			{
				$sql_display = substr( $sql, 0, $max_query_len ).'...';
			}
			if ($this->is_debug)
			{
				return $this->display_error(array(
							'Error Number: '.$this->_error_number(),
							$this->_error_message(),
							$sql_display
				));
			}
			return FALSE;
		}
		
		/* Stop timer */
		$time_end = list($em, $es) = explode(' ', microtime());
		$this->query_time += ($em + $es) - ($sm + $ss);
		
		/* Save the query for debugging */
		/* Limit the number of queries included into debug information */
		if ( $this->is_debug && $this->query_count < 100 )
		{
			$len = strlen( $sql );
			if ( $len > $max_query_len )
			{
				$sql = mb_substr( $sql, 0, $max_query_len ).'...';
			}
			/* April 6, 2010: colored highlight */
			$style_attr = '';
			if ( strpos( $sql, 'UPDATE `' ) !== false ){ $style_attr = ' style="color:#A90"'; }
			else if ( strpos( $sql, 'DELETE FROM `' ) !== false ){ $style_attr = ' style="color:#C00"'; }
			else if ( strpos( $sql, 'INSERT INTO `' ) !== false ){ $style_attr = ' style="color:#0A0"'; }
			$this->ar_queries[] = '<strong>'.sprintf("%1.5f ", (($em + $es) - ($sm + $ss))).'</strong><span'.$style_attr.'>'. htmlspecialchars( $sql ) . '</span> &bull; <em>' . number_format( $len ) . ' bytes</em>';
		}
		
		/* Increment the query counter */
		++$this->query_count;

		/* Load the result driver */
		$r = new site_database_result_ext();
		$r->id_conn = $this->id_conn;
		$r->id_result = $this->id_result;
		#$t->num_rows = $r->num_rows();
		
		return $r;
	}
	
	/**
	 * Simple Query.
	 * This is a simplified version of the query() function.
	 * Used when the features of the main query() function are not needed.
	 */
	public function simple_query($sql)
	{
		if ( !$this->id_conn )
		{
			$this->init();
		}
		return $this->_execute( $sql );
	}
	/**
	 * Detects if a query is a "write" type.
	 */	
	public function is_write_type($sql)
	{
		if ( preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Returns the total number of queries.
	 */	
	public function get_query_count()
	{
		return $this->query_count;
	}
	/**
	 * Returns the text of queries.
	 */	
	public function get_queries()
	{
		return $this->ar_queries;
	}
	/**
	 * Returns total queries time.
	 */	
	public function get_query_time()
	{
		return sprintf("%1.8f", $this->query_time);
	}
	/**
	 * Returns the last query that was executed.
	 */	
	public function last_query()
	{
		return end( $this->ar_queries );
	}
	
	/**
	 * Protect Identifiers. Adds backticks if appropriate based on db type.
	 */
	protected function protect_identifiers($item, $first_word_only = FALSE)
	{
		return $this->_protect_identifiers($item, $first_word_only);
	}
	
	
	/**
	 * Escapes data based on type. Sets boolean and null types.
	 */	
	function escape( $s )
	{
		switch ( gettype( $s ) )
		{
			case 'string'	:	$s = "'".$this->escape_str($s)."'"; break;
			case 'boolean'	:	$s = ($s === FALSE) ? 0 : 1; break;
			default			:	$s = ($s === NULL) ? 'NULL' : $s; break;
		}
		return $s;
	}

	/**
	 * Returns an array with table statistics.
	 *
	 * @access	public
	 * @return	array
	 */	
	public function show_table_status( $tablename = '', $is_exact = FALSE )
	{
		if ( isset( $this->data_cache['table_status'][$tablename] ) )
		{
			return $this->data_cache['table_status'][$tablename];
		}
		if ( ( $sql = $this->_show_table_status( $tablename, $is_exact ) ) === FALSE  )
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_unsupported_function');
			}
			return FALSE;
		}
		$retval = array();
		$query = $this->query( $sql );
		$rows = $query->num_rows();
		$tablename = (string) $tablename;
		if ( $rows > 0 )
		{
			$retval = $query->result_array();
			if ( $rows == 1 )
			{
				$retval = $retval[0];
			}
		}
		$this->data_cache['table_status'][$tablename] = $retval;
		return $this->data_cache['table_status'][$tablename];
	}
	/**
	 * Returns an array of table names
	 *
	 * @access	public
	 * @return	array		
	 */	
	public function list_tables($constrain_by_prefix = FALSE)
	{
		/* Is there a cached result? */
		if (isset($this->data_cache['table_names']))
		{
			return $this->data_cache['table_names'];
		}
		if ( ($sql = $this->_list_tables($constrain_by_prefix) ) === FALSE  )
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_unsupported_function');
			}
			return FALSE;
		}
		$retval = array();
		$query = $this->query($sql);
		if ( $query->num_rows() > 0 )
		{
			foreach ($query->result_array() as $row)
			{
				if (isset($row['TABLE_NAME']))
				{
					$retval[] = $row['TABLE_NAME'];
				}
				else
				{
					$retval[] = array_shift($row);
				}
			}
		}
		$this->data_cache['table_names'] = $retval;
		return $this->data_cache['table_names'];
	}
	
	/**
	 * Detects if a particular table exists.
	 */
	public function table_exists($table_name)
	{
		return ( in_array($this->prep_tablename($table_name), $this->list_tables())) ? TRUE : FALSE;
	}
	
		
	/**
	 * Fetch MySQL Field Names.
	 */
	public function list_fields($table = '')
	{
		/* Is there a cached result? */
		if (isset($this->data_cache['field_names'][$table]))
		{
			return $this->data_cache['field_names'][$table];
		}
		if ($table == '')
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_field_param_missing');
			}
			return FALSE;			
		}
		if (FALSE === ($sql = $this->_list_columns($this->prep_tablename($table))))
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_unsupported_function');
			}
			return FALSE;		
		}
		$query = $this->query($sql);
		$retval = array();
		foreach ($query->result_array() as $row)
		{
			if (isset($row['COLUMN_NAME']))
			{
				$retval[] = $row['COLUMN_NAME'];
			}
			else
			{
				$retval[] = current($row);
			}		
		}
		$this->data_cache['field_names'][$table] = $retval;
		return $this->data_cache['field_names'][$table];
	}
	/**
	 * Detects if a particular field exists.
	 */
	public function field_exists($field_name, $table_name)
	{
		return ( in_array($field_name, $this->list_fields($table_name))) ? TRUE : FALSE;
	}
	/**
	 * Returns an object with field data.
	 */	
	public function field_data($table = '')
	{
		if ($table == '')
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_field_param_missing');
			}
			return FALSE;
		}
		$query = $this->query($this->_field_data($this->prep_tablename($table)));
		return $query->field_data();
	}	




	/**
	 * Tests whether the string has an SQL operator. Called from Active Record class.
	 */
	protected function _has_operator($str)
	{
		if ( !preg_match("/(\s|<|>|!|=|is null|is not null)/i", trim($str)))
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Prep the table name - simply adds the table prefix if needed.
	 */	
	public function prep_tablename($table = '')
	{
		/* Do we need to add the table prefix? */
		if ($this->dbprefix != '')
		{
			if (substr($table, 0, strlen($this->dbprefix)) != $this->dbprefix)
			{
				$table = $this->dbprefix.$table;
			}
		}
		return $table;
	}
	

	/**
	 * Closes connection to the database.
	 */
	public function close()
	{
		if (is_resource($this->id_conn) || is_object($this->id_conn))
		{
			$this->_close($this->id_conn);
		}
		$this->id_conn = FALSE;
	}
	

	/**
	 * Displays an error message
	 * @todo: Add phrases
	 */
	public function display_error($id_msg)
	{
		$heading = 'MySQL Error';
		$message = is_array($id_msg) ? '<div>'.implode('</div><div>', $id_msg).'</div>' : $id_msg;
		
		print '<div style="font: 100% sans-serif;padding:1em;background:#FFF;color:#000;text-align:left;">';
		print '<div style="color:#C33">';
		print $heading;
		print '</div>';
		print '<div>';
		print $message;
		print '</div>';
		print '</div>';
		
		exit;
		
		/* Load the result driver */
		$r = new site_database_result_ext();
		$r->id_conn = $this->id_conn;
		$r->id_result = $this->id_result;
		#$t->num_rows = $r->num_rows();
		return $r;
	}
}






/* */
class site_database_result
{
	public $id_conn		= NULL;
	public $id_result		= NULL;
	public $result_array	= array();
	public $result_object	= array();
	public $current_row 	= 0;
	public $num_rows		= 0;
	
	/* Return result as array or object */
	public function result($type = 'object')
	{	
		return ($type == 'object') ? $this->result_object() : $this->result_array();
	}
	public function result_object()
	{
		if (count($this->result_object) > 0)
		{
			return $this->result_object;
		}
		if ($this->id_result === FALSE OR $this->num_rows() == 0)
		{
			return array();
		}
		$this->_data_seek(0);
		while ($row = $this->_fetch_object())
		{
			$this->result_object[] = $row;
		}
		return $this->result_object;
	}
	/**
	 * Query result. "array" version.
	 *
	 * @access	public
	 * @return	array
	 */	
	public function result_array()
	{
		if ( count( $this->result_array ) > 0)
		{
			return $this->result_array;
		}
		if ( $this->id_result === FALSE || $this->num_rows() == 0 )
		{
			return array();
		}
		$this->_data_seek(0);
		while ($row = $this->_fetch_assoc())
		{
			$this->result_array[] = $row;
		}
		return $this->result_array;
	}
	
	/**
	 * Query result. Returns the result as array or object.
	 */	
	public function row($n = 0, $type = 'object')
	{
		if ( !is_numeric($n) )
		{
			/* Cache the row data for subsequent uses */
			if ( !is_array($this->row_data) )
			{
				$this->row_data = $this->row_array(0);
			}
			/* Use array_key_exists() instead of isset() to allow MySQL NULL values */
			if ( array_key_exists($n, $this->row_data) )
			{
				return $this->row_data[$n];
			}
			/* reset the $n variable if the result was not achieved */
			$n = 0;
		}
		return ($type == 'object') ? $this->row_object($n) : $this->row_array($n);
	}

	/**
	 * Returns a single result row - object version
	 *
	 * @access	public
	 * @return	object
	 */
	function row_object($n = 0)
	{
		$result = $this->result_object();
		
		if ( count($result) == 0 )
		{
			return $result;
		}
		if ( $n != $this->current_row AND isset($result[$n]) )
		{
			$this->current_row = $n;
		}
		return $result[$this->current_row];
	}
	
	/**
	 * Returns a single result row - array version
	 *
	 * @access	public
	 * @return	array
	 */	
	function row_array($n = 0)
	{
		$result = $this->result_array();
		if ( count($result) == 0 )
		{
			return $result;
		}
		if ($n != $this->current_row && isset($result[$n]))
		{
			$this->current_row = $n;
		}
		return $result[$this->current_row];
	}
	/* */
	function num_rows() { return $this->num_rows; }
	function num_fields() { return 0; }
	function list_fields() { return array(); }
	function free_result() { return TRUE; }
	function _data_seek() { return TRUE; }
	function _fetch_assoc() { return array(); }	
	function _fetch_object() { return array(); }
}


?>