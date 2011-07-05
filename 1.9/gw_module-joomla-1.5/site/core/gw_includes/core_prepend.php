<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */

/* Load timer */
include_once( ${$o}->V->path_includes.'/class.timer.php' );

if ( !defined( 'IS_CLASS_GW_PREPEND' ) ) { define( 'IS_CLASS_GW_PREPEND', 1 );

define( 'CRLF', "\n" );
define( 'GW_SITE_SID', 'gw-sid' );

/* Groups of HTML-templates. */
define( 'GW_TPL_ADM', 1 );
define( 'GW_TPL_WEB_INDEX', 2 );
define( 'GW_TPL_WEB_INSIDE', 3 );
define( 'GW_TPL_CSS', 4 );
define( 'GW_TPL_BLOCK', 5 );
define( 'GW_TPL_LOGIN', 6 );
define( 'GW_TPL_MEMBER', 7 );

/* Status for items */
define( 'GW_STATUS_OFF', 0 );
define( 'GW_STATUS_ON', 1 );
define( 'GW_STATUS_PENDING', 2 );
define( 'GW_STATUS_REMOVE', 3 );
define( 'GW_STATUS_EDITING', 4 );
define( 'GW_STATUS_ABUSE', 5 );

/* Colors for HTML-forms */
define( 'GW_COLOR_OFF', '#888' );
define( 'GW_COLOR_ON', '#0C0' );
define( 'GW_COLOR_PENDING', '#EC0' );
define( 'GW_COLOR_REMOVE', '#E00' );

/* Colors for fade effects */
define( 'GW_COLOR_TRUE', 'fade-CCFFCC' );
define( 'GW_COLOR_FALSE', 'fade-FFCCCC' );

/* Input formats */
define( 'GW_INPUT_FMT_GWXML', 1 );
define( 'GW_INPUT_FMT_CSV', 2 );
define( 'GW_INPUT_FMT_RSS', 3 );
define( 'GW_INPUT_FMT_XML', 4 );

/* Link modes */
define( 'GW_LINK_ID', 1 );
define( 'GW_LINK_URI', 2 );
define( 'GW_LINK_TEXT', 3 );

/* Input formats for Localization */
define( 'TKIT_INPUT_FMT_GWXML', 1 );
define( 'TKIT_INPUT_FMT_PHP1', 2 );


/* Status for Localization */
define( 'TKIT_STATUS_OFF', 0 );
define( 'TKIT_STATUS_APPROVED', 4 );

/* */
@ini_set( 'register_globals', 0 );
@ini_set( 'set_magic_quotes_gpc', 0 );
@ini_set( 'set_magic_quotes_runtime', 0 );
@ini_set( 'mbstring.internal_encoding', 'UTF-8' );

/* */
class site_prepend extends site_db_config {
	
	public $ar_errors;
	public $sdf = "Y-m-d H:i:s"; /* SQL Date Format */
	
	/* For every $target */
	public function load_module( $module_name, $ar_objects = array() )
	{
		if ( empty( $ar_objects ) )
		{
			/* The list of objects and variables imported by default */
			$ar_objects = array( 'gv', 'V', 'oDb', 'oHtml', 'oHtmlAdm', 'oOutput', 'oTkit', 'oTpl', 'oFunc', 'oCase', 'oSess', 'oCache' );
		}
		
		/* Construct module name */
		$this->cur_module = $this->V->path_mod.'/mod_'.$module_name.'.php';
		
		if ( file_exists( $this->cur_module ) )
		{
			include_once( $this->cur_module );
			$module_name = 'gw_mod_'.$module_name;
			if ( class_exists( $module_name ) ) 
			{
				$o = new $module_name;
				foreach ( $ar_objects as $obj )
				{
					$o->$obj =& $this->$obj;
				}
				$o->autoexec();
				return $o;
			}
		}
	}
	public function autoexec() { }
	/* Called from index file */
	public function init()
	{
		/* Debug purposes */
		$this->ar_errors = array();
		if (function_exists('memory_get_usage'))
		{
			$this->a( 'debug_memory_s', memory_get_usage() );
		}

		/* Auto time for server */
		$this->a( 'time_req', $_SERVER['REQUEST_TIME'] );
		$this->a( 'datetime_req', @date($this->sdf, $this->V->time_req) );
		$this->a( 'time_gmt', $this->V->time_req - @date('Z') );
		$this->a( 'datetime_gmt', @date($this->sdf, $this->V->time_gmt) );

		/* Does uploads allowed? */
		$this->a('upload_max_filesize', gw_ini_get('upload_max_filesize') );
		$this->a('is_upload', gw_ini_get('file_uploads') );
		
		/* Get accepted encoding */
		$this->a('HTTP_ACCEPT_ENCODING', isset($_SERVER['HTTP_ACCEPT_ENCODING'])
				? $_SERVER['HTTP_ACCEPT_ENCODING']
				: (isset($_SERVER['HTTP_TE']) ? $_SERVER['HTTP_TE'] : ''));
		
		/* Accepted language, ISO 639-1 */
		$this->a( 'HTTP_ACCEPT_LANGUAGE', isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en' );

		/* Get remote IP string */
		$this->a('REMOTE_ADDR', (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').
			(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown' ? ' FW: '.$_SERVER['HTTP_X_FORWARDED_FOR'] : '').
			(isset($_SERVER['HTTP_CLIENT_IP']) ? ' CLIENT_IP: '.$_SERVER['HTTP_CLIENT_IP'] : '').
			(isset($_SERVER['HTTP_VIA']) ? ' VIA: '.$_SERVER['HTTP_VIA'] : '') );
		
		/* Path to source files */
		$this->a( 'page_index', $this->V->server_dir.'/'.$this->V->file_index );
		$this->a( 'server_url', $this->V->server_proto.$this->V->server_host.$this->V->server_dir );
		$this->a( 'server_url_admin', $this->V->server_proto.$this->V->server_host.$this->V->server_dir_admin );
		
		/* Set Output class */
		$this->oOutput =& $this->_init_output();
		
		/* Uppercase/Lowercase */
		$this->oCase =& $this->_init_ocase();
		
		/* Events */
		$this->oEvents =& $this->_init_events();
		
		$is_settings = false;
		/* Switch content modes */
		switch ($this->gv['sef_output'])
		{
			case 'ajax':
			case 'file':
				/* Set Database class */
				$this->oDb =& $this->_init_db();
				
				/* Load site settings (Requires $oDb) */
				$is_settings = $this->_init_settings();
				
				/* Translation Kit (Requires $oDb) */
				if ( !$this->gv['il'] )
				{
					$this->gv['il'] = $this->V->HTTP_ACCEPT_LANGUAGE;
				}
				#$this->oTkit = $this->_init_tkit(array('global'), $this->gv['il']);
				
				/* Translation Kit */
				#$this->a( 'ar_lang_list', $this->oTkit->get_lang_list() );
				
				/* Tkit: Correct interface language */
				#$this->gv['il'] = $this->oTkit->ar_ls['lang_uri'];

				/* Open Session class. Must be closed at the end. (Requires $oTkit)  */
				if ( $is_settings )
				{
					$this->oSess = $this->_init_sess();
					$this->a( 'time_user', $this->oSess->user_get_time() );
				}
				
				/* Cached Units (Requires $oDb) */
				#$this->oCache = $this->_init_cached_units( $this->oDb );
			break;
			case 'css':
			case 'js':
				$is_settings = true;
			break;
			default:
				/* Set Database class */
				$this->oDb =& $this->_init_db();

				/* Load site settings (Requires $oDb) */
				$is_settings = $this->_init_settings();

				/* Translation Kit (Requires $oDb) */
				$this->oTkit = $this->_init_tkit( array( 'global' ), $this->V->HTTP_ACCEPT_LANGUAGE );
				
				/* Translation Kit: Correct interface language (for links etc.) */
				$this->gv['il'] = $this->oTkit->ar_ls['isocode1'];

				/* HTML-templates */
				$this->oTpl =& $this->_init_html_tpl();
		
				/* Open Session class. Must be closed at the end. (Requires $oTkit)  */
				if ( $is_settings )
				{
					$this->oSess =& $this->_init_sess();
					$this->a( 'time_user', $this->oSess->user_get_time() );
				}

				/* Cached Units (Requires $oDb) */
				$this->oCache =& $this->_init_cached_units( $this->oDb );
			break;
		}
		
		/* */
		#$this->oTarget = $this->load_module( $this->gv['target'] );
		
		return $is_settings;
	}
	
	/* Load Wiki */
	function _init_wiki_( $in = '', $out = '' )
	{
		include_once( $this->V->path_includes.'/class.gw_wiki.php' );
		return new gw_wiki_code(array(
			'in' => $in,
			'out' => $out,
			'path_img'=> '{v:path_img}/wiki',
			'server_dir'=> '{v:server_dir}',
			'path_img_abs'=> $this->V->path_temp_abs.'/wiki',
			'phrase__links_in_document' => 'Links in document'
		));
	}
		
	/* Load Events */
	private function _init_events()
	{
		include_once( $this->V->path_includes.'/class.events.php' );
		return new site_events();
	}
		
	/* Read settings from database  */
	private function _init_settings()
	{
		$this->oDb->select( '*' );
		$this->oDb->from( 'settings' );
		$ar_sql = $this->oDb->get()->result_array();
		foreach ( $ar_sql as $ar_v )
		{
			$this->a( $ar_v['id_varname'], $ar_v['value'] );
		}
		if ( empty( $ar_sql ) )
		{
			return false;
		}
		return true;
	}
	
	/* Common functions */
	private function _init_functions()
	{
		include_once( $this->V->path_includes.'/functions.php' );
		$this->a( 'REQUEST_URI', get_request_uri() );
		return new tkit_functions;
	}

	/* HTTP-headers */
	private function _init_headers()
	{
		include_once( $this->V->path_includes.'/class.headers.php' );
		return $oHdr;
	}

	/* Paginator */
	public function _init_paginator($ar_cfg)
	{
		include_once( $this->V->path_includes.'/class.paginator.php' );
		return new site_class_paginator($ar_cfg);
	}

	/* Cached Units */
	public function _init_cached_units($oDb)
	{
		include_once( $this->V->path_includes.'/class.cached_units.php' );
		return new site_cache( $oDb, (60 * 60) );
	}

	/* Content output */
	private function _init_output()
	{
		include_once( $this->V->path_includes.'/class.output.php' );
		$o = new site_output(array(
			'str_title_separator' => ' - ',
			'is_js_debug' => 1,
			'path_css' => $this->V->path_css, 
			'path_js' => $this->V->path_js)
		);
		return $o;
	}
	
	/* Search index. */
	public function _init_search_index()
	{
		include_once( $this->V->path_includes.'/class.search_index.php' );
		return new site_search_index( $this->oDb, $this->oCase );
	}

	/* HTML-templates */
	public function _init_html_tpl()
	{
		$this->a( 'is_tpl_show_names', 0 );
		include_once($this->V->path_includes.'/class.template3.php');
		include_once($this->V->path_includes.'/class.template3.ext.php');
		$o = new site_class_templates_file( array(
				'id_style' => $this->gv['visualtheme'],
				'path_source' => $this->V->path_temp.'/'.$this->gv['visualtheme'],
				'path_cache' => $this->V->path_temp.'/'.$this->gv['visualtheme'].'-tc',
				'template_extension' => '.html',
			)
		);
		$o->ar_d = array();
		return $o;
	}

	/* HTML Forms */
	public function _init_forms()
	{
		include_once( $this->V->path_includes.'/class.forms3.php' );
		return new site_forms3;
	}

	/* HTML-tags */
	public function _init_html_tags()
	{
		include_once( $this->V->path_includes.'/class.html_tags.php' );
		return new site_html_tags(array(
				'is_sef' => $this->V->is_sef,
				'sef_rule' => $this->V->sef_rule,
				'sef_filename' => $this->V->sef_filename,
				'sef_fileindex' => $this->V->sef_fileindex,
				'sef_append' => $this->V->sef_append,
				'sef_output' => $this->V->sef_output,
				'server_dir' => $this->V->server_dir,
				'v_get' => 'arg',
			)
		);
	}
	public function _init_html_tags_admin()
	{
		include_once( $this->V->path_includes.'/class.html_tags.php' );
		return new site_html_tags(array(
				'is_sef' => $this->V->is_sef_admin,
				'sef_rule' => $this->V->sef_rule_admin,
				'sef_filename' => $this->V->sef_filename_admin,
				'sef_fileindex' => $this->V->sef_fileindex_admin,
				'sef_append' => $this->V->sef_append_admin,
				'sef_output' => $this->V->sef_output_admin,
				'server_dir' => $this->V->server_dir_admin,
				'v_get' => 'arg',
			)
		);
	}
	/* Common functions */
	private function _init_user_agent()
	{
		include_once( $this->V->path_includes.'/class.ua.php' );
		return new site_user_agent;
	}	
	
	
	/* Database class */
	private function _init_db()
	{
        /* Apply database settigns */
	    $ar_params['hostname'] = $this->V->db_host;
		$ar_params['username'] = $this->V->db_user;
		$ar_params['database'] = $this->V->db_name;
        $ar_params['port'] = 3306;
		$ar_params['password'] = $this->V->db_pass;
		$ar_params['dbprefix'] = $this->V->table_prefix;
		$ar_params['dbdriver'] = $this->V->db_driver;
		$ar_params['is_debug'] = true;
		$ar_params['is_debug_q'] = false;
		$ar_params['is_pconnect'] = false;
		$ar_params['is_active_record'] = true;
		$ar_params['id_conn'] = isset($this->V->db_conn) ? $this->V->db_conn : false;

		/* Load Active Record class */
		require_once( $this->V->path_db.'/class.db_active_record.php' );
		if (!class_exists('site_database_active_record'))
		{
			eval('class site_database_active_record { }');
		}
		/* Load classes `site_database` and `site_database_result` */
		/* Extends `site_database_active_record` */
		require_once( $this->V->path_db.'/class.db.php' );

		/* Extends `site_database` */
		require_once( $this->V->path_db.'/class.db_mysqli.php' );

		return new site_database_mysqli($ar_params);
	}
	

	/* TranslationKit API class */
	public function _init_tkit( $ar_tkit_profiles, $il_http )
	{
		include_once( $this->V->path_includes.'/class.tkit.php' );
		include_once( $this->V->path_includes.'/class.tkit.ext.php' );
		$o = new tkit_db;
		$o->oDb =& $this->oDb;
		$o->is_debug =& $this->V->is_debug_tkit;
		
		/* Load the list of languages */
		$o->get_lang_list();
		
		/* 1.9.3: Select default language from the list of languages */
		$il = $o->get_lang_default( $il_http );

		/* 1.9.3: Select the user-defined language */
		if ( $this->gv['il'] ) { $il = $this->gv['il']; }

		/* Load phrases */
		$o->import_tag( $ar_tkit_profiles, $il );
		
		return $o;
	}
	/**
	 * Puts Translation Kit variables into HTML-template class.
	 * Unsets phrases. Should be called at the end.
	 */
	public function import_tkit_phrases()
	{
		$a =& $this->oTkit->get_phrases_all();
		foreach ( $a as $k => $v )
		{
			$this->oTpl->assign_global( 'l:'.$k, $v );
			unset( $a[$k] );
		}
	}
	
	/* Uppercase/Lowercase */
	public function _init_ocase()
	{
		include_once( $this->V->path_includes.'/class.case.php' );
		$o = new gwv_casemap;
		$o->is_use_mbstring = 1;
		$o->arp = array( 1, 2 );
		return $o;
	}
	
	/* Uppercase/Lowercase */
	public function _init_http()
	{
		include_once( $this->V->path_includes.'/snoopy.class.php' );
		$o = new Snoopy;
		$o->agent = 'Glossword ' . $this->V->version;
		$o->referer = $this->V->server_url.'/';
		$o->temp_dir = $this->V->path_temp;
		$o->maxlength = $this->V->upload_max_filesize;
		$o->_httpversion = 'HTTP/1.1';
		return $o;
	}
	
	/**
	 * Redirects correctly.
	 * Closes database session.
	 */
	public function redirect( $url, $is_debug = 1, $is_db_close = 0 )
	{
		if ( $is_db_close )
		{
			$this->oDb->close();
		}
		$this->oHdr->redirect( $url, $is_debug );
	}
	

	/* Session class */
	private function _init_sess()
	{
		include_once( $this->V->path_includes.'/class.session-2.0.php' );
		$o = new site_session_2_0();
		$o->oDb =& $this->oDb;
		$o->oTkit =& $this->oTkit;
		$o->remote_ip = ip2long( $this->V->REMOTE_ADDR );
		$o->remote_ua = substr( htmlspecialchars( getenv( 'HTTP_USER_AGENT' ) ), 0, 255 );
		$o->uri = $this->V->uri = $this->V->REQUEST_URI;
		$o->sid = GW_SITE_SID;
		$o->server_dir =& $this->V->server_dir;
		
		/* Server time */
		$o->time_gmt =& $this->V->time_gmt;
		$o->time_req =& $this->V->time_req;
		$o->datetime_req =& $this->V->datetime_req;
		$o->datetime_gmt =& $this->V->datetime_gmt;
		/* Redirect */
		$o->time_refresh = 60;
		$o->url_login = $this->V->server_url.'/'.$this->V->file_index.'?arg[action]=login&arg[target]=account&arg[uri]='.base64_encode($this->V->uri);
		
		/* Load the rest of settings */
		$o->load_settings();
		
		/* 18 Jab 2010 - Custom user table name */
		if ( isset( $this->V->db_table_users ) && $this->V->db_table_users )
		{
			$o->db_table_users =& $this->V->db_table_users;
		}
		if ( isset( $this->V->db_table_sessions ) && $this->V->db_table_sessions )
		{
			$o->db_table_sessions =& $this->V->db_table_sessions;
		}
		if ( isset( $this->V->db_table_groups ) && $this->V->db_table_groups )
		{
			$o->db_table_groups =& $this->V->db_table_groups;
		}

		/* Get Session ID */
		$id_sess = isset( $this->gv['_cookie'][$o->sid] ) ? $this->gv['_cookie'][$o->sid] : (isset($this->gv[$o->sid]) ? $this->gv[$o->sid] : 0);
		$is_remember = isset( $this->gv['_cookie'][$o->sid.'r'] ) ? $this->gv['_cookie'][$o->sid.'r'] : (isset($this->gv[$o->sid.'r']) ? $this->gv[$o->sid.'r'] : 0);
		if ( $is_remember )
		{
			$o->int_timeout = $this->V->time_sec_y;
		}
		else
		{
			$o->int_timeout = 60 * 60;
		}
		/* Start new session */
		$o->sess_init( $id_sess );
		/* */
		if ( isset($this->V->is_autoauth) && $this->V->is_autoauth 
			&& (!$id_sess || $this->V->is_autoauth != $o->id_user)
			)
		{
			/**
			 * Authorize user
			 */
			$o->id_sess = 1;
			/* Load user settings */
			$o->is_remember = 1;
			$o->ar_user = array();
			$o->user_start( $this->V->is_autoauth, 'merge' );
			/* Add session into databse */
			$o->sess_insert( $this->V->is_autoauth );
			/* Cookie expired after 'n' seconds, 3600 = 1 hour */
			$time_expire = 3600 * 24;
			/* Add cookie */
			setcookie( $o->sid, $o->id_sess, ($this->V->time_req + $time_expire), '/' );
			setcookie( $o->sid.'r', 0, ($this->V->time_req + $time_expire), '/' );
		}
		return $o;
	}
	
	
	/* Called from index.php or admin.php before `page_*()` functions. */
	public function global_variables($ar = array())
	{
		/* Functions class */
		$this->oFunc =& $this->_init_functions();

		/* HTML-tags (Requires site settings) */
		$this->oHtml =& $this->_init_html_tags();
		$this->oHtmlAdm =& $this->_init_html_tags_admin();

		/* Set Headers class */
		$this->oHdr =& $this->_init_headers();

		/* */
		include_once( $this->V->path_includes.'/class.register_globals.php' );
		$oGlobals = new site_register_globals();
		$this->gv = $oGlobals->register( $ar );

		/* $this->gv['arg']['var'] => $this->gv['var'] */
		if ( is_array( $this->gv['arg'] ) )
		{
			foreach( $this->gv['arg'] as $k => $v )
			{
				$this->gv[$k] = $this->oHtml->urldecode( $v );
				unset( $this->gv['arg'][$k] );
			}
		}

		/* Default values */
		$oGlobals->do_default( $this->gv['action'] );
		$oGlobals->do_default( $this->gv['target'], 'notarget' );
		$oGlobals->do_default( $this->gv['visualtheme'], $this->V->visualtheme );
		$oGlobals->do_default( $this->gv['il'] );
		$oGlobals->do_default( $this->gv['id'] );
		$oGlobals->do_default( $this->gv['id_item'] );
		$oGlobals->do_default( $this->gv['area'] );
		$oGlobals->do_default( $this->gv['uri'] );
		$oGlobals->do_default( $this->gv['page'], 1);
		$oGlobals->do_default( $this->gv['sef_output'], $this->V->sef_output );

		if ( strpos($this->V->REQUEST_URI, 'cmsrouter' ) !== false )
		{
			$this->V->is_sef = 0;
		}

		/* Decode URL parameters and merge */
		if ( $this->V->is_sef )
		{
			/* Redirect from old URLs */
			if ( !in_array( $this->gv['sef_output'], array('ajax','css','js') )
				&& strpos ( $this->V->REQUEST_URI, $this->V->file_index.'?' ) !== false
			)
			{
				if ( isset($this->gv['target']) || isset($this->gv['s']))
				{
					/* */
				}
				else
				{
					#$this->redirect( $this->V->server_url.'/'.$this->V->file_index, 1 );
				}
			}
			$this->gv = array_merge_clobber( $this->gv, $this->oHtml->url_undo_sef( $this->V->REQUEST_URI ) );
		}

		/* Parsing arg[area] */
		$this->gv['_area'] = $this->gv['area'];
		$this->gv['area'] = $oGlobals->subparam( $this->gv['_area'] );
		
	#	prn_r( $this->gv );
		
		/* 22 Jan 2010: assign variables from Area */
		$this->gv['action'] = isset( $this->gv['area']['a'] ) ? $this->gv['area']['a'] : $this->gv['action'];
		$this->gv['target'] = isset( $this->gv['area']['t'] ) ? $this->gv['area']['t'] : $this->gv['target'];
		$this->gv['page'] = isset( $this->gv['area']['page'] ) ? $this->gv['area']['page'] : $this->gv['page'];
		$this->gv['id'] = isset( $this->gv['area']['id'] ) ? $this->gv['area']['id'] : $this->gv['id'];
		$this->gv['id_item'] = isset( $this->gv['area']['id_item'] ) ? $this->gv['area']['id_item'] : $this->gv['id_item'];
		#$this->gv['id_user'] = isset( $this->gv['area']['id_user'] ) ? $this->gv['area']['id_user'] : $this->gv['id_user'];
		
		/* Filter incoming data */
		$oGlobals->do_alphanum( $this->gv['target'] );
		$oGlobals->do_alphanum( $this->gv['action'] );
		$oGlobals->do_numeric_one( $this->gv['page'] );

		/**
		 * A special rule:
		 *  arg[sef_output]=css&arg[files]=tkit_css,122c => tkit_css.css, 122c.css 
		 *  arg[sef_output]=js&arg[files]=lib,func => lib.js, func.js
		 */
		if ( isset( $this->gv['files'] ) )
		{
			$ar = explode( ',', $this->gv['files'] );
			$this->gv['files'] = array();
			foreach( $ar as $v )
			{
				$this->gv['files'][] = trim( $v ).'.'.$this->gv['sef_output'];
			}
		}
	}
}
}

?>