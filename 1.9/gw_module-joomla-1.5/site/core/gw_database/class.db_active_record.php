<?php
/**
 * $Id$
 * Active records class.
 */
class site_database_active_record
{
	public $ar_select		= array();
	public $ar_distinct	= FALSE;
	public $ar_from		= array();
	public $ar_join		= array();
	public $ar_where		= array();
	public $ar_like		= array();
	public $ar_groupby		= array();
	public $ar_having		= array();
	public $ar_limit		= FALSE;
	public $ar_offset		= FALSE;
	public $ar_order		= FALSE;
	public $ar_orderby		= array();
	public $ar_set			= array();
	public $ar_wherein		= array();
	public $ar_aliased_tables		= array();
	public $ar_store_array	= array();
	public $ar_union = array();
	private $ar_caching = array();

	/**
	 * Generates the SELECT query.
	 */
	public function select($ar_select = '*', $protect_identifiers = TRUE)
	{
		if (is_string($ar_select))
		{
			if ($protect_identifiers !== FALSE)
			{
				$ar_select = explode(',', $ar_select);
			}
			else
			{
				$ar_select = array($ar_select);
			}
		}
		foreach ($ar_select as $v)
		{
			$v = trim($v);
			if ($v != '*' && $protect_identifiers !== FALSE)
			{
				if (strpos($v, '.') !== FALSE)
				{
					$v = $this->dbprefix.$v;
				}
				else
				{
					$v = $this->_protect_identifiers($v);
				}
			}
			if ($v != '')
			{
				$this->ar_select[] = $v;
			}
		}
		return $this;
	}

	/**
	 * Select Max
	 *
	 * Generates a SELECT MAX(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	public function select_max($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'MAX');
	}
	/**
	 * Select Min
	 *
	 * Generates a SELECT MIN(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	public function select_min($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'MIN');
	}
	/**
	 * Select Sum
	 *
	 * Generates a SELECT SUM(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function select_sum($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'SUM');
	}
	/**
	 * Processing Function for the four functions above:
	 *
	 *	select_max()
	 *	select_min()
	 *	select_avg()
	 *  select_sum()
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function _max_min_avg_sum($select = '', $alias = '', $type = 'MAX')
	{
		if ( !is_string($select) OR $select == '')
		{
			$this->display_error('db_invalid_query');
		}

		$type = strtoupper($type);

		if ( !in_array($type, array('MAX', 'MIN', 'AVG', 'SUM')))
		{
			$this->display_error('Invalid function type: '.$type);
		}

		if ($alias == '')
		{
			$alias = $this->_create_alias_from_table(trim($select));
		}

		$sql = $type.'('.$this->_protect_identifiers(trim($select)).') AS '.$alias;

		$this->ar_select[] = $sql;

		return $this;
	}
	/**
	 * Determines the alias name based on the table
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private static function _create_alias_from_table($item)
	{
		if (strpos($item, '.') !== FALSE)
		{
			return end(explode('.', $item));
		}
		return $item;
	}

	/**
	 * Adds DISTINCT to SELECT
	 */
	public function distinct($v = TRUE)
	{
		$this->ar_distinct = (is_bool($v)) ? $v : TRUE;
		return $this;
	}

	/* */
	public function from($from)
	{
		foreach ((array)$from as $v)
		{
			$this->ar_from[] = $this->_protect_identifiers($this->_track_aliases($v));
		}
		return $this;
	}

	/**
	 * Generates the JOIN portion of the query
	 */
	public function join($table, $cond, $type = '')
	{
		if ($type != '')
		{
			$type = strtoupper(trim($type));

			if ( !in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), TRUE))
			{
				$type = '';
			}
			else
			{
				$type .= ' ';
			}
		}

		/* If a DB prefix is used we might need to add it to the column names */
		if ($this->dbprefix)
		{
			$this->_track_aliases($table);
/* When prefix is part of field name, it produces error, for eample: g_ */
#$this->oDb->join( 'st.tag_crc32u = mts.tag_crc32u'  );
			// First we remove any existing prefixes in the condition to avoid duplicates
			#$cond = preg_replace('|('.$this->dbprefix.')([\w\.]+)([\W\s]+)|', "$2$3", $cond);

			// Next we add the prefixes to the condition
			#$cond = preg_replace('|([\w\.]+)([\W\s]+)(.+)|', $this->dbprefix . "$1$2" . $this->dbprefix . "$3", $cond);
		}

		$join = $type.'JOIN '.$this->_protect_identifiers($this->dbprefix.$table, TRUE).' ON '.$cond;

		$this->ar_join[] = $join;

		return $this;
	}


	/* */
	public function where($field, $value = NULL)
	{
		return $this->_where($field, $value, 'AND ');
	}
	/* */
	public function or_where($field, $value = NULL)
	{
		return $this->_where($field, $value, 'OR ');
	}
	/* */
	private function _where($field, $value = NULL, $type = 'AND ', $escape = TRUE)
	{
		if ( !is_array($field))
		{
			/* 22 Sep 2008: Use a manual WHERE statement */
			#$this->ar_where[] = $field;
			#return;
			/* */
			$field = array( $field => $value );
		}
		foreach ($field as $k => $v)
		{
			$prefix = (count($this->ar_where) == 0) ? '' : $type;
			if ( is_null( $v ) && !$this->_has_operator( $k ) )
			{
				/* value appears not to have been set, assign the test to IS NULL */
				$k .= ' IS NULL';
			}
			if ( !is_null( $v ) )
			{
				if ( $escape === TRUE )
				{
					/* exception for "field<=" keys */
					if ($this->_has_operator($k))
					{
						$k = preg_replace("/([A-Za-z_0-9]+)/", $this->_protect_identifiers('$1'), $k);
					}
					else
					{
						$k = $this->_protect_identifiers( $k );
					}
					/* ignore value on false */
					if ($v === false)
					{
					    $v = ' ';
					}
					else
					{
					    $v = ' '.$this->escape( $v );
					}
				}
				if ( !$this->_has_operator( $k ) )
				{
					$k .= ' =';
				}
			}
			else
			{
				if ($escape === TRUE)
				{
					$k = $this->_protect_identifiers($k, TRUE);
				}
			}
			$this->ar_where[] = $prefix.$k.$v;
		}
		return $this;
	}

	/**
	 * Generates a WHERE field IN ('item', 'item') SQL query joined with AND if appropriate
	 * Usage: $oDb->where_in( 'id', array(3, 5, 7) );
	 */
	public function where_in($field = NULL, $values = NULL)
	{
		return $this->_where_in($field, $values);
	}
	/**
	 * Generates a WHERE field IN ('item', 'item') SQL query joined with OR if appropriate
	 */
	public function or_where_in($field = NULL, $values = NULL)
	{
		return $this->_where_in($field, $values, FALSE, 'OR ');
	}

	/**
	 * Generates a WHERE field NOT IN ('item', 'item') SQL query joined with AND if appropriate
	 */
	public function where_not_in($field = NULL, $values = NULL)
	{
		return $this->_where_in($field, $values, TRUE);
	}
	/**
	 * Generates a WHERE field NOT IN ('item', 'item') SQL query joined with OR if appropriate
	 */
	public function or_where_not_in($field = NULL, $values = NULL)
	{
		return $this->_where_in($field, $values, TRUE, 'OR ');
	}
	/**
	 * Called from where_in(), where_in_or(), where_not_in(), where_not_in_or()
	 */
	private function _where_in($field = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
	{
		if ( $field === NULL || !is_array( $values ) )
		{
			return;
		}
		$not = ($not) ? ' NOT ' : '';
		foreach ($values as $value)
		{
			$this->ar_wherein[] = $this->escape( $value );
		}
		$prefix = (count($this->ar_where) == 0) ? '' : $type;
		$where_in = $prefix . $this->_protect_identifiers($field) . $not . ' IN (' . implode(", ", $this->ar_wherein) . ') ';
		$this->ar_where[] = $where_in;
		/* reset the array for multiple calls */
		$this->ar_wherein = array();
		return $this;
	}



	/**
	 * Generates a %LIKE% portion of the query.
	 * Separates multiple calls with AND.
	 */
	public function like($field, $match = '', $side = 'both')
	{
		return $this->_like($field, $match, 'AND ', $side);
	}
	/**
	 * Generates a NOT LIKE portion of the query.
	 * Separates multiple calls with AND.
	 */
	public function not_like($field, $match = '', $side = 'both')
	{
		return $this->_like($field, $match, 'AND ', $side, ' NOT');
	}
	/**
	 * Generates a NOT LIKE portion of the query.
	 * Separates multiple calls with OR.
	 */
	public function or_like($field, $match = '', $side = 'both')
	{
		return $this->_like($field, $match, 'OR ', $side);
	}
	/**
	 * Generates a NOT LIKE portion of the query.
	 * Separates multiple calls with OR.
	 */
	public function or_not_like($field, $match = '', $side = 'both')
	{
		return $this->_like($field, $match, 'OR ', $side, 'NOT ');
	}
	/**
	 * Generates a LIKE portion of the query.
	 * Called from or_not_like(), or_like(), not_like(), like().
	 */
	private function _like($field, $match = '', $type = 'AND ')
	{
		if ( !is_array($field))
		{
			$field = array($field => $match);
		}
		foreach ($field as $k => $v)
		{
			$prefix = (count($this->ar_like) == 0) ? '' : $type;
			$v = $this->escape_str($v);
			$this->ar_like[] = $prefix." $k LIKE '%{$v}%'";
		}
		return $this;
	}




	/**
	 * Sets the HAVING value. Separates multiple calls with AND.
	 */
	public function having($field, $value = '', $escape = TRUE)
	{
		return $this->_having($field, $value, 'AND ', $escape);
	}
	/**
	 * Sets the OR HAVING value. Separates multiple calls with OR.
	 */
	public function or_having($field, $value = '', $escape = TRUE)
	{
		return $this->_having($field, $value, 'OR ', $escape);
	}
	/**
	 * Sets the HAVING values. Called from having() or or_having()
	 */
	function _having($field, $value = '', $type = 'AND ', $escape = TRUE)
	{
		if ( !is_array($field))
		{
			$field = array($field => $value);
		}
		foreach ($field as $k => $v)
		{
			$prefix = (count($this->ar_having) == 0) ? '' : $type;
			if ($escape === TRUE)
			{
				$k = $this->_protect_identifiers($k);
			}
			if ( !$this->_has_operator($k))
			{
				$k .= ' = ';
			}
			if ($v != '')
			{
				$v = ' '.$this->escape_str($v);
			}
			$this->ar_having[] = $prefix.$k.$v;
		}
		return $this;
	}





	/*  Sets the GROUP BY value */
	public function group_by($by)
	{
		if (is_string($by))
		{
			$by = explode(',', $by);
		}
		foreach ($by as $val)
		{
			$val = trim($val);
			if ($val != '')
			{
				$this->ar_groupby[] = $this->_protect_identifiers($val);
				if ($this->ar_caching === TRUE)
				{
					$this->ar_cache_groupby[] = $this->_protect_identifiers($val);
				}
			}
		}
		return $this;
	}

	/*  Sets the ORDER BY value */
	public function order_by($by, $direction = '')
	{
		if (strtolower($direction) == 'random')
		{
			$by = ''; /* Random results want or don't need a field name */
			$direction = $this->_random_keyword;
		}
		elseif (trim($direction) != '')
		{
			$direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), TRUE)) ? ' '.$direction : ' ASC';
		}
		$orderby_statement = $this->_protect_identifiers($by, TRUE).$direction;
		$this->ar_orderby[] = $orderby_statement;
		return $this;
	}



	/**
	 * Sets the LIMIT value
	 */
	public function limit($value, $offset = '')
	{
		$this->ar_limit = $value;
		if ($offset != '')
		{
			$this->ar_offset = $offset;
		}
		return $this;
	}
	/**
	 * Sets the OFFSET value
	 */
	public function offset($offset)
	{
		$this->ar_offset = $offset;
		return $this;
	}


	/**
	 * Allows field/value pairs to be set for inserting or updating
	 */
	public function set( $field, $value = '', $escape = TRUE)
	{
		$field = $this->_object_to_array( $field );
		if ( !is_array( $field ) )
		{
			$field = array($field => $value);
		}
		foreach ( $field as $k => $v )
		{
			if ( $escape === FALSE )
			{
				$this->ar_set[$this->_protect_identifiers($k)] = $v;
				if ( $this->ar_caching === TRUE )
				{
					$this->ar_cache_offset[$this->_protect_identifiers($k)] = $v;
				}
			}
			else
			{
				/* Apr 1, 2010 */
				if ( $v === NULL )
				{
				    $this->ar_set[$k] = false;
				}
				else
				{
				    $v = $this->escape( $v );
					$this->ar_set[$this->_protect_identifiers($k)] = $v;
				}
				if ( $this->ar_caching === TRUE )
				{
					$this->ar_cache_offset[$this->_protect_identifiers($k)] = $this->escape($v);
				}
			}
		}
		return $this;
	}
	/**
	 * Compiles the select statement based on the other functions called
	 * and runs the query
	 */
	function get( $table = '', $limit = null, $offset = null )
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}
		if ( !is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->_compile_select();

		$result = $this->query($sql);
		$this->_reset_select();
		return $result;
	}
	/**
	 * Just compiles the select statement.
	 */
	function get_select($table = '', $limit = null, $offset = null)
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}
		if ( !is_null($limit))
		{
			$this->limit($limit, $offset);
		}
		$sql = $this->_compile_select();
		$this->_reset_select();
		return $sql;
	}

	/**
	 * "Count All Results" query.
	 * Generates a platform-specific query string that counts all records
	 * returned by an Active Record query.
	 */
	function count_all_results($table = '')
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}

		$sql = $this->_compile_select($this->_count_string . $this->_protect_identifiers('numrows'));

		$query = $this->query($sql);
		$this->_reset_select();

		if ($query->num_rows() == 0)
		{
			return '0';
		}

		$row = $query->row();
		return $row->numrows;
	}


	/**
	 * Allows the where clause, limit and offset to be added directly
	 */
	function get_where($table = '', $where = null, $limit = null, $offset = null)
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}
		if ( !is_null($where))
		{
			$this->where($where);
		}

		if ( !is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->_compile_select();

		$result = $this->query($sql);
		$this->_reset_select();
		return $result;
	}


	/**
	 * Compiles an insert string and runs the query.
	 */
	public function insert($table = '', $set = NULL)
	{
		if ( !is_null( $set ) )
		{
			$this->set( $set );
		}
		if (count($this->ar_set) == 0)
		{
			if ($this->is_debug)
			{
				return $this->display_error('db_must_use_set');
			}
			return FALSE;
		}
		if ( $table == '' )
		{
			if ( !isset($this->ar_from[0]))
			{
				if ($this->is_debug)
				{
					return $this->display_error('db_must_set_table');
				}
				return FALSE;
			}

			$table = $this->ar_from[0];
		}
		$sql = $this->_insert( $this->_protect_identifiers($this->dbprefix.$table), array_keys($this->ar_set), array_values($this->ar_set));
		$this->_reset_write();
		return $this->query($sql);
	}

	/**
	 * Compiles an update string and runs the query.
	 */
	function update($table = '', $set = NULL, $where = NULL, $limit = NULL)
	{
		if ( !is_null( $set ) )
		{
			$this->set($set);
		}
		if ( sizeof( $this->ar_set ) == 0 )
		{
			if ($this->is_debug)
			{
				return $this->display_error('db_must_use_set');
			}
			return FALSE;
		}
		if ($table == '')
		{
			if ( ! isset($this->ar_from[0]))
			{
				if ($this->is_debug)
				{
					return $this->display_error('db_must_set_table');
				}
				return FALSE;
			}

			$table = $this->ar_from[0];
		}
		if ($where != NULL)
		{
			$this->where($where);
		}
		if ($limit != NULL)
		{
			$this->limit( $limit );
		}
		$sql = $this->_update($this->_protect_identifiers($this->dbprefix.$table), $this->ar_set, $this->ar_where, $this->ar_orderby, $this->ar_limit);
		
		$this->_reset_write();
		return $this->query($sql);
	}

	/**
	 * Generates (SELECT) UNION (SELECT) statement.
	 */
	public function union($ar)
	{
		$this->ar_union[] = $this->_compile_select( '('.implode(') UNION (', $ar).')');
	}



	/**
	 * Generates and runs "DELETE FROM table".
	 */
	function empty_table($table = '')
	{
		if ($table == '')
		{
			if ( !isset($this->ar_from[0]))
			{
				if ($this->is_debug)
				{
					return $this->display_error('db_must_set_table');
				}
				return FALSE;
			}
			$table = $this->ar_from[0];
		}
		else
		{
			$table = $this->_protect_identifiers($this->dbprefix.$table);
		}
		$sql = $this->_delete($table);
		$this->_reset_write();
		return $this->query($sql);
	}


	/**
	 * Generates and runs "TRUNCATE table".
	 * If the database does not support the truncate() command
	 * This function maps to "DELETE FROM table"
	 */
	function truncate($table = '')
	{
		if ($table == '')
		{
			if ( !isset($this->ar_from[0]))
			{
				if ($this->is_debug)
				{
					return $this->display_error('db_must_set_table');
				}
				return FALSE;
			}
			$table = $this->ar_from[0];
		}
		else
		{
			$table = $this->_protect_identifiers($this->dbprefix.$table);
		}
		$sql = $this->_truncate($table);
		$this->_reset_write();
		return $this->query($sql);
	}



	/**
	 * Compiles a delete string and runs the query
	 */
	function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE)
	{
		if ($table == '')
		{
			if ( !isset($this->ar_from[0]))
			{
				if ($this->is_debug)
				{
					return $this->display_error('db_must_set_table');
				}
				return FALSE;
			}
			$table = $this->ar_from[0];
		}
		elseif (is_array($table))
		{
			foreach($table as $single_table)
			{
				$this->delete( $single_table, $where, $limit, FALSE );
			}
			$this->_reset_write();
			return;
		}
		else
		{
			$table = $this->_protect_identifiers( $this->dbprefix.$table );
		}
		if ($where != '')
		{
			$this->where($where);
		}
		if ($limit != NULL)
		{
			$this->limit($limit);
		}
		if (count($this->ar_where) == 0 && count($this->ar_like) == 0)
		{
			if ($this->is_debug)
			{
				return $this->display_error('db_del_must_use_where');
			}
			return FALSE;
		}
		$sql = $this->_delete( $table, $this->ar_where, $this->ar_like, $this->ar_limit );
		if ( $reset_data )
		{
			$this->_reset_write();
		}

		return $this->query( $sql );
	}


	/**
	 * Track Aliases. Tracks SQL statements written with aliased tables.
	 */
	private function _track_aliases($table)
	{
		/* if a table alias is used we can recognize it by a space */
		if (strpos($table, " ") !== FALSE)
		{
			/* if the alias is written with the AS keyowrd, get it out */
			$table = preg_replace('/ AS /i', ' ', $table);
			$this->ar_aliased_tables[] = trim(strrchr($table, " "));
		}
		return $this->dbprefix.$table;
	}

	/**
	 * Filter Table Aliases. Removes database prefixes from aliased tables
	 */
	private function _filter_table_aliases($statements)
	{
		foreach ($statements as $k => $v)
		{
			foreach ($this->ar_aliased_tables as $table)
			{
				$statements[$k] = preg_replace('/(\w+\.\w+)/', $this->_protect_identifiers('$0'), $statements[$k]); // makes `table.field`
				$statements[$k] = str_replace($this->dbprefix.$table.'.', $table.'.', $statements[$k]);
			}
		}
		return $statements;
	}

	/**
	 * Compile the SELECT statement.
	 *
	 * Generates a query string based on which functions were used.
	 * Should not be called directly. Called from get().
	 */
	private function _compile_select($select_override = FALSE)
	{
		$sql = ( !$this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';
		$sql .= (count($this->ar_select) == 0) ? '*' : implode(', ', $this->_filter_table_aliases($this->ar_select));

		if ($select_override !== FALSE)
		{
			$sql = $select_override;
		}

		if (count($this->ar_union) > 0)
		{
			$sql = implode(' ', $this->ar_union);
		}

		if (count($this->ar_from) > 0)
		{
			$sql .= "\nFROM ";
			$sql .= $this->_from_tables($this->ar_from);
		}

		if (count($this->ar_join) > 0)
		{
			$sql .= "\n";

			/* special consideration for table aliases */
			if (count($this->ar_aliased_tables) > 0 && $this->dbprefix)
			{
				$sql .= implode("\n", $this->_filter_table_aliases($this->ar_join));
			}
			else
			{
				$sql .= implode("\n", $this->ar_join);
			}
		}

		if (count($this->ar_where) > 0 OR count($this->ar_like) > 0)
		{
			$sql .= "\nWHERE ";
		}

		$sql .= implode("\n", $this->ar_where);

		if (count($this->ar_like) > 0)
		{
			if (count($this->ar_where) > 0)
			{
				$sql .= " AND ";
			}

			$sql .= implode("\n", $this->ar_like);
		}

		if (count($this->ar_groupby) > 0)
		{

			$sql .= "\nGROUP BY ";

			/* special consideration for table aliases */
			if (count($this->ar_aliased_tables) > 0 && $this->dbprefix)
			{
				$sql .= implode(", ", $this->_filter_table_aliases($this->ar_groupby));
			}
			else
			{
				$sql .= implode(', ', $this->ar_groupby);
			}
		}

		if (count($this->ar_having) > 0)
		{
			$sql .= "\nHAVING ";
			$sql .= implode("\n", $this->ar_having);
		}

		if (count($this->ar_orderby) > 0)
		{
			$sql .= "\nORDER BY ";
			$sql .= implode(', ', $this->ar_orderby);

			if ($this->ar_order !== FALSE)
			{
				$sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
			}
		}

		if (is_numeric($this->ar_limit))
		{
			$sql .= "\n";
			$sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
		}

		return $sql;
	}


	/**
	 * Object to Array
	 *
	 * Takes an object as input and converts the class variables to array key/vals
	 *
	 * @access	public
	 * @param	object
	 * @return	array
	 */
	private function _object_to_array($object)
	{
		if ( ! is_object($object))
		{
			return $object;
		}

		$array = array();
		foreach (get_object_vars($object) as $key => $val)
		{
			if ( ! is_object($val) AND ! is_array($val))
			{
				$array[$key] = $val;
			}
		}

		return $array;
	}
	/**
	 * Resets the active record values. Called from get().
	 */
	function _reset_run($ar_reset_items)
	{
		foreach ($ar_reset_items as $item => $default_value)
		{
			if ( !in_array($item, $this->ar_store_array))
			{
				$this->$item = $default_value;
			}
		}
	}

	/**
	 * Resets the active record values.  Called by the get() function
	 */
	private function _reset_select()
	{
		$ar_reset_items = array(
			'ar_select' => array(),
			'ar_from' => array(),
			'ar_join' => array(),
			'ar_where' => array(),
			'ar_like' => array(),
			'ar_groupby' => array(),
			'ar_having' => array(),
			'ar_orderby' => array(),
			'ar_wherein' => array(),
			'ar_union' => array(),
			'ar_aliased_tables' => array(),
			'ar_distinct' => FALSE,
			'ar_limit' => FALSE,
			'ar_offset' => FALSE,
			'ar_order' => FALSE,
		);
		$this->_reset_run($ar_reset_items);
	}

	/**
	 * Resets the active record "write" values.
	 *
	 * Called by the insert() or update() functions
	 */
	private function _reset_write()
	{
		$ar_reset_items = array(
			'ar_set' => array(),
			'ar_from' => array(),
			'ar_where' => array(),
			'ar_like' => array(),
			'ar_orderby' => array(),
			'ar_limit' => FALSE,
			'ar_order' => FALSE
		);
		$this->_reset_run($ar_reset_items);
	}
}
?>