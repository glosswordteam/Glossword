<?php
class site_db_config {
	public $V;
	/* Autoload */
	public function __construct()
	{
		/* Debug settings */
		$sys['is_debug_cache'] = 0;
		$sys['is_debug_time'] = 0;
		$sys['is_debug_db'] = 0;
		$sys['is_debug_tkit'] = 0;
		$sys['is_debug_tkit_trace'] = 0;
		$sys['is_debug_mail'] = 0;
		/* Path to sources */
		$sys['path_includes'] = 'gw_includes';
		$sys['path_js'] = 'gw_js';
		$sys['path_locale'] = 'gw_locale';
		$sys['path_images'] = 'gw_images';
		$sys['path_views'] = 'gw_views';
		$sys['path_temp'] = 'gw_temp';
		$sys['path_db'] = 'gw_database';
		$sys['path_mod'] = 'gw_modules';
		$sys['path_export'] = 'gw_export';

		$sys['file_index'] = 'index.php';
		$sys['file_admin'] = 'admin.php';

		/* More variables */
		$sys['content_type'] = 'text/html';
		$sys['is_use_gzip'] = 0;
		$sys['is_gzip_js'] = 0;
		$sys['is_send_headers'] = 1;
		$sys['time_cache_http'] = 86400;
		$sys['gzip_ratio'] = 5;
		$sys['prbblty_tasks'] = 5;
		$sys['sef_output'] = 'xhtml';
		$sys['sef_output_admin'] = 'xhtml';
		$sys['sef_filename'] = 'i';
		$sys['sef_filename_admin'] = 'i';
		$sys['sef_fileindex'] = '';
		$sys['sef_fileindex_admin'] = '';
		$sys['paginator_links_total'] = 4;
		$sys['paginator_links_separator'] = ', ';
		$sys['paginator_links_more'] = '…';
		$sys['id_field_root'] = 1;
		
		$sys['is_cache_http'] = 0;
		$sys['is_cache_az'] = 1;
		$sys['is_cache_items_browse'] = 1;
		$sys['is_cache_html'] = 0;
		$sys['is_cache_items_prevnext'] = 1;

		$this->V = new t_var_store($sys);
		$this->_switch_cfg_profile();
	}
	/* Get a variable */
	public function g($variable = '')
	{
		if ($variable) { return $this->V->{$variable}; }
		else { return get_object_vars($this->V); }
	}
	/* Add a variable */
	public function a($variable, $value)
	{
		$this->V->{$variable} = $value;
	}
	/* Switch between configuration profiles */
	private function _switch_cfg_profile()
	{
		/* Alternative settings */
		if ( $_SERVER['HTTP_HOST'] == '127.0.0.1' || $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			/* Debug settings */
			$this->a( 'is_debug_cache', 1 );
			$this->a( 'is_debug_time', 1 );
			$this->a( 'is_debug_db', 1 );
			$this->a( 'is_debug_tkit', 0 );
			$this->a( 'is_debug_tkit_trace', 0 );
			$this->a( 'is_debug_mail', 1 );
			/* Path names */
			$this->a( 'is_sef', 0 );
			$this->a( 'sef_fileindex', '/index.php' );
		}
	}
}
/* */
class t_var_store {
	public function __construct($a)
	{
		foreach( $a as $k => $v )
		{
			$this->$k = $v;
		}
	}
}
?>