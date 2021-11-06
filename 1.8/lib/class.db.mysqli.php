<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2021 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if ( ! defined('IN_GW')) {
    die('<!-- Not in App  -->');
}
// --------------------------------------------------------
/**
 * Database class.
 *
 * uses also: class gw_timer;
 */
// --------------------------------------------------------
if ( ! class_exists('gwtkDataBase')) {

    /**
     * @property mysqli        $link_id  A link identifier returned by mysqli_connect() or mysqli_init()
     * @property mysqli_result $query_id A result set identifier returned by mysqli_query(), mysqli_store_result() or mysqli_use_result().
     */
    class gwtkDataBase
    {
        /* Free result on next record */
        const IS_FREE_RESULT = true;

        /* halt with message */
        const ON_ERROR_HALT = 1;

        /* ignore errors quietly */
        const ON_ERROR_IGNORE = 2;

        /* ignore error, but spit a warning */
        const ON_ERROR_MSG = 3;

        public $host = '';
        public $database = '';
        public $user = '';
        public $password = '';
        public $connect_time = 0;
        public $query_time = 0;
        public $query_array = array();
        public $on_error_default = self::ON_ERROR_MSG; // ON_ERROR_HALT | ON_ERROR_IGNORE | ON_ERROR_MSG
        public $max_queries_debug = 100;
        public $cnt_queries_debug = 0;

        /**
         * Transparent query caching
         */
        public $is_cache = 0;
        public $cache_lifetime = 5;
        public $is_print_events = 0;
        /**
         * public: result array and current row number
         */
        public $record = array();
        public $row = 0;
        public $errno = 0;
        public $error = '';
        /**
         * private: Link and query handles
         */
        private $link_id;
        private $query_id;

        /**
         * Calls Universal query-caching engine (if exists)
         */
        public function setCache($dir = '')
        {
            if (defined("IS_CLASS_CACHE")) {
                global $oCh;
                $oCh = new gwtkCache;
                $oCh->setPath($dir);
                $this->is_cache      = 1;
                $oCh->cache_lifetime = $this->cache_lifetime;
            }
        }

        /**
         *
         */
        public function connect(
            $db_host = '',
            $db_user = '',
            $db_password = '',
            $db_name = ''
        ) {
            if ($db_host != '') {
                $this->host = $db_host;
            }
            if ($db_user != '') {
                $this->user = $db_user;
            }
            if ($db_password != '') {
                $this->password = $db_password;
            }
            if ($db_name != '') {
                $this->database = $db_name;
            }
            /* */
            if ( ! $this->link_id) {
                $b_debug_time   = (defined("GW_DEBUG_SQL_TIME") && class_exists('gw_timer') && GW_DEBUG_SQL_TIME == 1);
                $this->database = trim(str_replace('`', '', $this->database));
                if ($b_debug_time) {
                    $t = new gw_timer('q');
                }
               if ( ! $this->link_id = mysqli_connect($this->host, $this->user, $this->password)) {
                    $halt_result = $this->halt( 'Could not connect to the database ' . $this->user. '@' . $this->host, $this->on_error_default);
                    if ( !$halt_result ) {
                        return false;
                    }
                }
                if ( ! mysqli_select_db($this->link_id, $this->database)) {
                    mysqli_close($this->link_id);
                    $halt_result = $this->halt('Could not select database (' . $this->database . ').', $this->on_error_default);
                    if ( !$halt_result ) {
                        return false;
                    }
                }
                mysqli_query($this->link_id, 'SET NAMES \'utf8\'');
                if ($b_debug_time) {
                    $this->connect_time = $t->end();
                }
            }

            return $this->link_id;
        } // end of connect();

        /**
         * Post a query to database
         */
        public function query($query = '')
        {
            if ($query != '') {
                $b_debug_time = (defined("GW_DEBUG_SQL_TIME") && class_exists('gw_timer') && GW_DEBUG_SQL_TIME == 1);

                $time_end = 0;
                if ( ! $this->connect()) {
                    return false;
                }
                if (isset($this->query_id)) {
                    $this->free_result();
                }
                if ($b_debug_time) {
                    $t = new gw_timer('q');
                }
                if ( ! $this->query_id = mysqli_query($this->link_id, $query)) {
                    $this->halt('Invalid SQL: ' . $query, $this->on_error_default);
                    die;
                }
                if ($b_debug_time) {
                    $time_end         = $t->end();
                    $this->query_time += $time_end;
                }
                if (count($this->query_array) < $this->max_queries_debug
                    && defined("GW_DEBUG_SQL_QUERY")
                    && class_exists('gw_timer')
                    && GW_DEBUG_SQL_QUERY == 1
                ) {
                    // faster than htmlspecialchars()
                    $this->query_array[] = sprintf('<strong>%1.5f</strong> %s', $time_end, htmlspecialchars_ltgt($query));
                }
                $this->cnt_queries_debug++;

                return $this->query_id;
            } // query is not empty

            return false;
        } // end of query();

        /**
         *
         */
        public function isDuplicate($query = '')
        {
            if ($query != '') {
                if ( ! $this->query_id = mysqli_query($this->link_id, $query)) {
                    return preg_match("/Duplicate entry/i", mysqli_error($this->link_id));
                }
            }

            return false;
        }

        /**
         *
         */
        public function next_record()
        {
            if ( ! $this->query_id) {
                $this->halt("next_record() called with no query pending.", $this->on_error_default);

                return 0;
            }
            $this->record = mysqli_fetch_assoc($this->query_id);
            $this->row    += 1;
            $stat         = is_array($this->record);
            if ( ! $stat && self::IS_FREE_RESULT) {
                $this->free_result();
            }

            return $stat;
        }

        /**
         *
         */
        public function free_result()
        {
            if ( $this->query_id ) {
                mysqli_free_result($this->query_id);
            }
        }

        /**
         *
         */
        public function close()
        {
            if ($this->link_id) {
                if ($this->is_print_events) {
                    print '<br>DB: close';
                }
                if ($this->query_id) {
                    $this->free_result();
                }

                return mysqli_close($this->link_id);
            } else {
                return false;
            }
        } // end of close();
        /* private: error handling */
        ## ------------------------------------------
        ##
        private function halt($msg, $errMode = self::ON_ERROR_HALT)
        {
            $result = false;
            if ($this->link_id) {
                $this->error = mysqli_error($this->link_id);
                $this->errno = mysqli_errno($this->link_id);
            }
            if ($errMode == self::ON_ERROR_IGNORE) {
                $result = true;
            }
            if ($errMode == self::ON_ERROR_HALT || $errMode == self::ON_ERROR_MSG) {
                $this->haltmsg($msg);
            }

            return $result;
        }

        private function haltmsg($msg)
        {
            echo '<div style="margin:3px 0;border:3px solid #EEE;font:10pt sans-serif;width:98%;overflow:hidden">'
                . '<dl>'
                . '<dt style="padding:0 1em;color:#C80"><strong>Database error</strong></dt>'
                . '<dd>'
                . substr($msg, 0, 1024)
                . '</dd>'
                . '</dl>'
                . ($this->errno ? '<dl><dt style="padding:0 1em;color:#C08"><strong>MySQL Error</strong></dt> <dd>' . $this->errno . ' (' . $this->error . ')</dd></dl>' : '')
                . '</div>';
        }
        ##
        ## ------------------------------------------
        /* public: table locking */
        public function lock($table, $mode = "WRITE")
        {
            $this->connect();
            $sql = 'LOCK TABLES ';
            if (is_array($table)) // many tables
            {
                foreach ($table as $k => $v) {
                    if (($k == "read") && ($k != 0)) {
                        $sql .= $v . ' READ, ';
                    } else {
                        $sql .= $v . ' ' . $mode . ', ';
                    }
                }
                $sql = substr($sql, 0, -2);
            } else {
                $sql .= $table . ' ' . $mode;
            }
            // $this->query($sql);
            $res = mysqli_query($this->link_id, $sql);
            if ( ! $res) {
                $str = is_array($table) ? implode(', ', $table) : $table;
                $this->halt("Can't lock($str, $mode)");

                return 0;
            }

            return $res;
        }

        public function unlock()
        {
            $this->connect();
            $res = mysqli_query($this->link_id, "UNLOCK TABLES");
            if ( ! $res) {
                $this->halt("Can't unlock().");

                return 0;
            }

            return $res;
        }

        //
        public function num_fields()
        {
            return mysqli_num_fields($this->query_id);
        }

        public function num_rows()
        {
            return mysqli_num_rows($this->query_id);
        }

        /* public: evaluate the result (size, width) */
        public function affected_rows()
        {
            return (mysqli_affected_rows($this->link_id) > 0 ? mysqli_affected_rows($this->link_id) : 0);
        }

        /* public: shorthand notation */
        public function r($name)
        {
            return isset($this->record[$name]) ? $this->record[$name] : 0;
        }

        /* Get the list of databases */
        public function get_databases()
        {
            $this->database = trim(str_replace('`', '', $this->database));
            if ($this->link_id) {
                $db_list = mysqli_query($this->link_id, 'SHOW DATABASES');
                $ar      = array();
                while ($row = mysqli_fetch_array($db_list)) {
                    $ar[$row['Database']] = $row['Database'];
                }
            } else {
                $sql = 'SHOW DATABASES';
                $this->query($sql);
                $ar = array();
                if ($this->query_id) {
                    while ($row = mysqli_fetch_array($this->query_id, MYSQLI_NUM)) {
                        $ar[$row[0]] = $row[0];
                    }
                }
            }

            return $ar;
        }

        /* Get the list of tables from current database */
        public function table_names($table_name = '')
        {
            $this->database = trim(str_replace('`', '', $this->database));
            $sql            = 'SHOW TABLES FROM `' . $this->database . '`';
            $ar             = array();
            if ($table_name != '') {
                $sql = 'SHOW TABLES FROM `' . $this->database . '` LIKE "' . $table_name . '"';
            }
            $this->query($sql);
            while ($row = mysqli_fetch_array($this->query_id, MYSQLI_NUM)) {
                $ar[] = $row[0];
            }

            return $ar;
        }

        /*
         * Get all information about table
         * works with MySQL version 3.23.10 or above
         */
        public function table_info($table_name = '')
        {
            if ($table_name != '') {
                $this->database = trim(str_replace('`', '', $this->database));
                $this->query('SHOW TABLE STATUS FROM `' . $this->database . '` LIKE "' . $table_name . '";');
                if ($this->query_id) {
                    return mysqli_fetch_assoc($this->query_id);
                }
            }

            return array();
        }

        /* get last known maximum value */
        public function MaxId($table_name, $field = 'id')
        {
            if ($table_name != '') {
                $this->query('SELECT MAX(' . $field . ') AS n FROM `' . $table_name . '`;');
                $arID = mysqli_fetch_assoc($this->query_id);

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
        public function sqlRun($q, $cache_prefix = '')
        {
            return ($this->_sql_is_cached($q, $cache_prefix) ? $this->_sql_return($q, $cache_prefix) : $this->sqlExec($q, $cache_prefix, 1));
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
         * @see     gw_fixslash();
         */
        public function sqlExec($q, $cache_prefix = '', $is_cache_def = 0)
        {
            $ar = array();
            // on error: Only `SELECT' query returns empty array, other returns FALSE
            $empty_value = preg_match("/^SELECT/", $q) ? array() : false;
            //
            // Connect and post query
            $query_id = $this->query($q);
            //
            // on error: returned status for UPDATE, DELETE etc. (not SELECT)
            if ($query_id) {
                if (($this->affected_rows() > 0) && ! $empty_value) {
                    $empty_value = true;
                } // on error: DELETE returns true when no affected rows
                elseif (($this->affected_rows() == 0)
                    && preg_match("/^(DELETE|DROP|CREATE|ALTER)/", $q)
                ) {
                    $empty_value = true;
                }
                // Is here any returned data row?
                if ($this->num_rows() > 0) {
                    while ($this->next_record()) {
                        $ar[] = $this->record;
                    }
                }
                // Fix quotes in array
                gw_fixslash($ar, 'runtime');
                //
                // Put query into cache
                if ($this->is_cache && $is_cache_def && preg_match("/^SELECT/", $q)) {
                    global $oCh;
                    $oCh->setKey($q, $cache_prefix);
                    $oCh->save($ar, 'array');
                }
                //
                if (empty($ar)) {
                    $ar = $empty_value;
                }
            }

            return $ar;
        }

        /**
         * @access  private
         */
        private function _sql_is_cached($q, $cache_prefix = '')
        {
            // only SELECT can be cached
            $is_cache = preg_match("/^SELECT/", $q) ? $this->is_cache : 0;
            if ($is_cache) {
                global $oCh;
                $oCh->setKey($q, $cache_prefix);
                $is_cache = $oCh->checkout();
            }

            return $is_cache;
        }

        /**
         * @access  private
         */
        private function _sql_return($q, $cache_prefix = '')
        {
            global $oCh;
            $oCh->setKey($q, $cache_prefix);

            return $oCh->load('array');
        }

        /**
         * Builds query "LIMIT n,n" for database
         *
         * @param   int  $total    total number of items
         * @param   int  $page     current page number
         * @param   int  $perpage  items per page
         *
         * @return   string  part of database query
         */
        public function prn_limit($total, $page, $perpage)
        {
            $numpages = ceil($total / $perpage);
            for ($i = 1; $i <= $numpages; $i++) {
                $position = ($i * $perpage);
                if ($i == ($page - 1)) {
                    return ' LIMIT ' . $position . ', ' . $perpage;
                }
            }

            return ' LIMIT 0, ' . $perpage;
        }

    } // end of class
}
//
class gwtkDb extends gwtkDataBase
{
    public $host = GW_DB_HOST;
    public $user = GW_DB_USER;
    public $password = GW_DB_PASSWORD;
    public $database = GW_DB_DATABASE;
    public $is_print_events = 0;
}

