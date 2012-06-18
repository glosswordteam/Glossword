<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
// --------------------------------------------------------
/**
 * Usage:
 *  Special usage is not required.
 *  Implemented into database class, sqlExec() function
 */
if (!defined('IS_CLASS_CACHE'))
{
	define('IS_CLASS_CACHE', 1);
class gwtkCache {
// --------------------------------------------------------
// Variables
// --------------------------------------------------------
	var $path_root      = '.';
	var $path_store     = 'cache/sql';  // path to cache files
	var $cache_ex       = '.tmp';       // cache filename extension
	var $cache_prefix   = '';           // cache prefix filename
	var $cache_lifetime = 5;            // n - seconds | 0 - never expire
	var $cache_subdir   = '';           // cache subdirectory name
	var $filename       = '_';          // cache filename
	var $is_Zlib        = 0;            // compress files with Zlib
	var $q              = '_';          // cache key name, md5()
	var $query_array    = array();      // queries container
	var $cnt_queries_debug = 0;
// --------------------------------------------------------
// Supply functions
// --------------------------------------------------------
	/**
	 * @access  public
	 */
	function setPath($dir)
	{
		$this->path_store = $dir;
	}
	/**
	 * @access  public
	 */
	function setKey($str, $prefix)
	{
		$this->q = md5($str);
		$this->cache_prefix = $prefix;
	}
	/**
	 * @access  private
	 */
	function _setFilename()
	{
		$this->filename = $this->_filename();
	}
	/**
	 * @access  private
	 * @return  Path to current cache filename
	 */
	function _filename()
	{
		if ($this->is_Zlib)
		{
			$this->cache_ex = $this->cache_ex.'.gz';
		}
		if (!file_exists($this->path_root . '/' . $this->path_store))
		{
			@mkdir($this->path_root . '/'. $this->path_store, 0777);
		}
		if (!file_exists($this->path_root . '/' . $this->path_store . '/' . $this->cache_subdir))
		{
			@mkdir($this->path_root . '/' . $this->path_store . '/' . $this->cache_subdir, 0777);
		}
		$filename_subdir = ($this->cache_subdir != '') ? $this->cache_subdir . '/' : '';
		$str = $this->path_root . '/'. $this->path_store . '/' . $filename_subdir . $this->cache_prefix.'_'.md5($this->is_Zlib.$this->q). $this->cache_ex;
		// reset prefix
		$this->cache_prefix = '';
		return $str;
	}
	/**
	 * Removes cache file from disk
	 */
	function _delete()
	{
		if ( file_exists($this->filename) )
		{
			$this->query_array[] = 'Delete '.$this->filename;
			unlink( $this->filename );
		}
	}
	/**
	 * Saves cache contents using Zlib
	 */
	function _save_gz($content)
	{
		if (!function_exists("gzopen"))
		{
			$this->query_array[] = sprintf( "Function <b>%s</b> not installed.", "gzopen");
			return false;
		}
		$fp = gzopen($this->filename, "w");
		if ($fp)
		{
			gzwrite( $fp, $content );
			gzclose( $fp );
		}
		else
		{
			$this->query_array[] = 'Can\'t write with Zlib: '.$this->filename;
			return false;
		}
		return true;
	}
	/**
	 * Loads cache contents using Zlib
	 */
	function _load_gz($mode)
	{
		return implode('', gzfile( $this->filename ));
	}
// --------------------------------------------------------
// Public functions
// --------------------------------------------------------
	/**
	 * Checking for cache
	 *
	 * @return  TRUE when current cache file exists, otherwise FALSE
	 * @access  public
	 * @see     _setFilename();
	 */
	function checkout()
	{
		$this->_setFilename();
		if ( file_exists($this->filename) )
		{
			if ( $this->cache_lifetime != 0 ) // expire
			{
				$m1 = filemtime($this->filename);
				$m2 = (time() - $this->cache_lifetime);
				if ($m2 > $m1)
				{
					$this->_delete();
					return false;
				}
				else
				{
#					$this->query_array = ($m1 - $m2). ' second(s) left';
				}
			}
			return true;
		}
		return false;
	} // end of func checkout
	/**
	 * Saves cache contents
	 *
	 * @access  public
	 */
	function save($content, $mode = "string")
	{
		if ($mode == "array")
		{
			$content = serialize($content);
			$content = addslashes($content);
		}
		if ($this->is_Zlib)
		{
			return $this->_save_gz($content, $mode);
		}
		$this->query_array[] = 'Save ' . $this->filename;
		$fp = fopen($this->filename, "w");
		if ($fp)
		{
			fwrite( $fp, $content );
			fclose( $fp );
		}
		else
		{
			$this->query_array[] = 'Can\'t write: '.$this->filename;
			return false;
		}
		return true;
	}
	/**
	 * Loads cache contents
	 *
	 * @access  public
	 * @see gw_fixslash();
	 */
	function load($mode = 'string')
	{
		$this->query_array[] = 'Load ' . $this->filename;
		++$this->cnt_queries_debug;
		$content = implode('', file( $this->filename ));
		if ($this->is_Zlib)
		{
			$content = $this->_load_gz($mode);
		}
		// fix quotes in array
		gw_fixslash($content, 'runtime');
		if ($mode == 'array')
		{
			$content = stripslashes($content);
			$return = @unserialize($content);
			return empty($return) ? array() : $return;
		}
		// fix quotes in array
		return $content;
	}
} // end of class
}
?>