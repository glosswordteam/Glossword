<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword
 * @copyright	 Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see LICENSE.php
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );

class GlosswordController extends JController
{
	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		
	}
	/* Control Panel Home */
	function admin_cph()
	{
		global $mainframe;

		$jo_user =& JFactory::getUser();
		$jo_db =& JFactory::getDBO();
		$jo_document =& JFactory::getDocument();
		$jo_uri =& JFactory::getURI();
		$jo_cfg =& JFactory::getConfig();
		$jo_user =& JFactory::getUser();
		$jo_component = &JComponentHelper::getComponent('com_glossword');

		/* Load component configuration */
		$query = 'SELECT * FROM #__gw_config';
		$jo_db->setQuery( $query );
		$ar_sql = $jo_db->loadAssocList();
		$ar_cfg = array();
		foreach ( $ar_sql as $k => $v )
		{
			$ar_cfg[$v['setting_key']] = $v['setting_value'];
		}
		/* Default visual theme name */
		$ar_cfg['visualtheme'] = 'gw-joomla';
		/* Authorize using User ID */
		$ar_cfg['is_autoauth'] = $jo_user->id;
		/* Absolute path to CSS-files. Used in `file()` */
		$ar_cfg['path_css_abs'] = $ar_cfg['path_temp_abs'].'/'.$ar_cfg['visualtheme'];
		/* Path to CSS for HTML. Used in CSS and JS, for example `img src="{path_css}/header.png"`. */
		/* Variable `server_dir` is common for both back- and frontends. */
		$ar_cfg['path_css'] = $ar_cfg['server_dir'].'/'.$ar_cfg['path_temp_web'].'/'.$ar_cfg['visualtheme'];
		/* Absolute path to temporary folder. Used for writing a temporary data (html, cache, etc.) */
		$ar_cfg['path_temp'] = $ar_cfg['path_temp_abs'];
		/* Custom tablenames for a user sessions */
		$ar_cfg['db_table_users'] = 'users_jos';
		$ar_cfg['db_table_groups'] = 'usergroups_jos';
		$ar_cfg['db_table_sessions'] = 'sessions_jos';
		/* Send HTTP-headers */
		$ar_cfg['is_send_headers'] = 0;
		
		/* ------------------------------------------------- */
		error_reporting( E_ALL );
		if ( !defined( 'IS_IN_SITE' ) ) { define( 'IS_IN_SITE', 1 ); }
		if ( !defined( 'SITE_THIS_SCRIPT' ) ) { define( 'SITE_THIS_SCRIPT', 'admin.php' ); }

		/* mktime() called once */
		$_SERVER['REQUEST_TIME'] = isset( $_SERVER['REQUEST_TIME'] ) ? $_SERVER['REQUEST_TIME'] : mktime();

		/* Current script instance */
		$o = crc32( $_SERVER['REQUEST_TIME'] );

		/* Load configuration class */
		if ( file_exists( $ar_cfg['path_core_abs'].'/gw_config.php' ) )
		{
			require_once( $ar_cfg['path_core_abs'].'/gw_config.php' );
		}
		else
		{
			JError::raiseWarning( 100, JText::sprintf( 'LJ1000', '<strong>'. $ar_cfg['path_core_abs'].'/gw_config.php</strong>' ) );
		}

		/* */
		${$o} = new site_db_config();

		/* Correct path names - 1st time */
		${$o}->a( 'path_includes', $ar_cfg['path_core_abs'].'/'.${$o}->V->path_includes );

		/* Load everything */
		include( ${$o}->V->path_includes.'/core_prepend.php' );
		include( ${$o}->V->path_includes.'/core_engine.php' );
		include( ${$o}->V->path_includes.'/core_engine_utils.php' );
		
		/* */
		${$o} = new site_engine_utils();
		
		/* Correct path names - 2nd time */
		foreach ( array('path_includes', 'path_js', 'path_locale', 'path_images', 'path_views', 'path_db', 'path_mod') as $path )
		{
			${$o}->a( $path, $ar_cfg['path_core_abs'].'/'.${$o}->V->$path );
		}
		
		/* Append to URL - frontend */
		$ar_cfg['is_sef'] = 0;
		$ar_cfg['sef_output'] = 'html';
		$ar_cfg['sef_fileindex'] = 'index.php?';
		$ar_cfg['sef_append'] = array(
			'option' => $jo_component->option
		);

		/* Append to URL - backend */
		$ar_cfg['is_sef_admin'] = 0;
		$ar_cfg['sef_output_admin'] = 'html';
		$ar_cfg['sef_fileindex_admin'] = 'index.php?';
		$ar_cfg['sef_append_admin'] = array( 
			'option' => $jo_component->option
		);
		${$o}->a( 'sef_rule_admin', array() );
		
		
		/* Append to all URLs used for AJAX-requests */
		$ar_cfg['sef_append_ajax'] = array( 'format' => 'ajax' );

		/* Use existend database connection */
		$ar_cfg['db_host'] = $mainframe->getCfg( 'host' );
		$ar_cfg['db_user'] = $mainframe->getCfg( 'user' );
		$ar_cfg['db_pass'] = $mainframe->getCfg( 'password' );
		if ( $ar_cfg['db_name'] == $jo_cfg->getValue( 'db' ) )
		{
			/* Display error */
			/* *REMOVED* */
			/* Catch only mysqli */
			if ( $mainframe->getCfg( 'dbtype' ) == 'mysqli' )
			{
				$ar_cfg['db_conn'] =& $jo_db->_resource;
			}
		}

		/* Add $ar_cfg to Glossword configuration */
		foreach ( $ar_cfg as $setting_key => $setting_value )
		{
			${$o}->a( $setting_key, $setting_value );
		}

		/* SEF */
		${$o}->a( 'sef_rule', array() );
		
		${$o}->a( 'oTimer', new tkit_timer );

		/* Register a global variables */
		${$o}->global_variables( array( 'arg', 'arp', GW_SITE_SID, GW_SITE_SID.'r' ) );
		if ( !${$o}->init() )
		{
			JError::raiseWarning( 100, JText::sprintf( 'LJ1001', $ar_cfg['db_name'] ) );
			return;
		}

		/* Timer */
		${$o}->a( 'time_php_prepend', ${$o}->V->oTimer->end() );
		${$o}->a( 'oTimer', new tkit_timer );
		
		${$o}->page_header();
		${$o}->page_body();
		${$o}->page_footer();

		/* Add CSS */
		$jo_document->addStyleSheet( JRoute::_( 'index.php?option=com_glossword&&format=css&arg[files]='.${$o}->oOutput->get_css_collection().'&arg[sef_output]=css' ) );
		
		/* Add JS */
		$jo_document->addScript( JRoute::_( 'index.php?option=com_glossword&&format=js&arg[files]='.${$o}->oOutput->get_js_collection().'&arg[sef_output]=js' ) );
		
		/* Changing MIME-type */
		$format = JRequest::getVar( 'format' );

		switch ( $format )
		{
			case 'ajax': $jo_document->setMimeEncoding( 'text/plain' ); break;
			case 'css': $jo_document->setMimeEncoding( 'text/css' ); break;
			case 'js': $jo_document->setMimeEncoding( 'text/javascript' ); break;
		}
		
		/* Page title */
		$jo_document->setTitle( ${$o}->oOutput->get_html_title() );
		
	}
	/**
	 * Displays HTML-form to configure module settings
	 * 
	 * @return HTML-code
	 */
	function admin_configure()
	{
		$arp = JRequest::getVar( 'arp', array(), '', 'array' );
		$jo_db =& JFactory::getDBO();
		$jo_document =& JFactory::getDocument();
		
		/* Add CSS */
		$jo_document->addStyleSheet( JRoute::_( 'index.php?option=com_glossword&&format=css&arg[files]=admin&arg[sef_output]=css' ) );

		/* */
		$str = '';
		if ( empty( $arp ) )
		{
			/* Load component configuration */
			$query = 'SELECT * FROM #__gw_config ORDER BY `setting_key`';
			$jo_db->setQuery( $query );
			$ar_sql = $jo_db->loadAssocList();
			$ar_cfg = array();
			foreach ( $ar_sql as $k => $v )
			{
				$ar_cfg[$v['setting_key']] = $v['setting_value'];
			}
			
			/* Display HTML-form */
			$str .= HTML_glossword::showform( $ar_cfg );
			
			/* Register tasks */
			JToolBarHelper::save( 'configure' );
		}
		else
		{
			
			/* */
			GlosswordController::user_sync($arp, GW_ID_GROUP_ADMINS);
			GlosswordController::user_sync($arp, GW_ID_GROUP_USERS);
			
			foreach ( $arp as $k => $v )
			{
				/* Checking trailing slash */
				if ($k == 'path_temp_abs' || $k == 'path_core_abs' || $k == 'server_dir' || $k == 'server_dir_admin')
				{
					$v = str_replace( "\x5c", "\x2f", $v );
					$v = preg_replace( "/(\\x2f)$/", '', $v );
				}
				
				$query = 'UPDATE #__gw_config'
				. ' SET setting_value = \''. mysql_escape_string($v) .'\''
				. ' WHERE setting_key = \''. mysql_escape_string($k) .'\''
				;
				$jo_db->setQuery( $query );
				$jo_db->query();
			}
			$href_index = JRoute::_( 'index.php?option=com_glossword');
			$str .= JText::_( 'Settings saved' );
			$str .= '. <a href="'.$href_index.'">'.JText::_( 'Continue to Control Panel' ).'</a>.';
		}
		print $str;
	}

	/**
	 * Keeps both tables in sync.
	 * Limit for this component - 1000 users
	 * 
	 * @param int $id_usergroup Usergroup ID
	 * @param Array $ar Data from HTML-form
	 * @todo Ability to connect database using abother login/password.
	 */
	function user_sync($ar, $id_usergroup)
	{
		$jo_db =& JFactory::getDBO();
		
		/* Selecting Users ID from Joomla */
		if ( $id_usergroup == GW_ID_GROUP_ADMINS )
		{
			$query = 'SELECT id `id_user` FROM #__users WHERE ( usertype = "Super Administrator" OR usertype = "Administrator" ) AND id != "1"';
		}
		else if ( $id_usergroup == GW_ID_GROUP_USERS )
		{
			$query = 'SELECT id `id_user` FROM #__users WHERE usertype != "Super Administrator" AND usertype != "Administrator" AND id != "1" ORDER BY registerDate DESC LIMIT 1000';
		}
		$jo_db->setQuery( $query );
		$ar_sql = $jo_db->loadAssocList();
		if ( is_null($ar_sql) ){ $ar_sql = array(); }
		$ar_sql_joomla = array();
		foreach ( $ar_sql as $ar_v )
		{
			$ar_sql_joomla[$ar_v['id_user']] = '';
		}
		
		/* Selecting Users ID from Glossword */
		$query = 'SELECT id_user FROM '.$ar['db_name'].'.'.$ar['table_prefix'].'users_jos WHERE id_group = "'.$id_usergroup.'" AND id_user != "1" ORDER BY date_reg DESC LIMIT 1000';
		$jo_db->setQuery( $query );
		$ar_sql = $jo_db->loadAssocList();
		if ( is_null($ar_sql) ){ $ar_sql = array(); }
		$ar_sql_gw = array();
		foreach ( $ar_sql as $k => $ar_v )
		{
			$ar_sql_gw[$ar_v['id_user']] = '';
			unset( $ar_sql[$k] );
		}
		/* Find difference */
		$ar_sql_new_users = array_diff_key( $ar_sql_joomla, $ar_sql_gw );
		
		/* Update existent users */
		if ( !empty($ar_sql_gw) )
		{
			$query = 'SELECT id `id_user`, name `user_fname`, username `login`, email `user_email`, '.
						'registerDate `date_reg`, lastvisitDate `date_login` '.
						'FROM #__users WHERE id IN ('. implode(',', array_keys($ar_sql_gw)).')';
			$jo_db->setQuery( $query );
			$ar_sql = $jo_db->loadAssocList();
			if ( is_null( $ar_sql ) ){ $ar_sql = array(); }
			
			foreach ( $ar_sql as $k => $ar_v )
			{
				$query = 'UPDATE '.$ar['db_name'].'.'.$ar['table_prefix'].'users_jos'
				. ' SET login = '. $jo_db->Quote($ar_v['login']).', id_group = '.$id_usergroup.', date_reg = '.$jo_db->Quote($ar_v['date_reg']).', date_login = '.$jo_db->Quote($ar_v['date_login'])
				. ', user_fname = '.$jo_db->Quote($ar_v['user_fname']).', user_email = '.$jo_db->Quote($ar_v['user_email'])
				.' WHERE id_user = '. $ar_v['id_user'].' ';
				$jo_db->setQuery( $query );
				$jo_db->query();
				unset( $ar_sql[$k] );
			}
		}
		
		/* Insert new users */
		if ( !empty( $ar_sql_new_users ) )
		{
			$query = 'SELECT id `id_user`, name `user_fname`, username `login`, email `user_email`, '.
						'registerDate `date_reg`, lastvisitDate `date_login` '.
						'FROM #__users WHERE id IN ('. implode( ',', array_keys( $ar_sql_new_users ) ).')';
			$jo_db->setQuery( $query );
			$ar_sql = $jo_db->loadAssocList();
			if ( is_null( $ar_sql ) ){ $ar_sql = array(); }

			foreach ( $ar_sql as $k => $ar_v )
			{
				#id_user	login 	password 	id_group 	is_active is_visible 	is_moderated 	id_user_public 	date_reg 	date_login 	
				#cnt_terms 	cnt_comments 	cnt_kb 	user_fname 	user_sname 	user_nickname 	user_email 	user_location 	user_settings
				$query = 'INSERT INTO '.$ar['db_name'].'.'.$ar['table_prefix'].'users_jos'
				. ' ( id_user, login, password, id_group, id_user_public, date_reg, date_login, user_fname, user_sname, user_nickname, user_email, user_location, user_settings )'
				. ' VALUES ( '. $ar_v['id_user'].', '.$jo_db->Quote($ar_v['login']).', "", '.$id_usergroup.', '.sprintf("%u", crc32($ar_v['id_user']))
				.', '.$jo_db->Quote($ar_v['date_reg']).', '.$jo_db->Quote($ar_v['date_login']).', '.$jo_db->Quote($ar_v['user_fname']).', "", "", '.$jo_db->Quote($ar_v['user_email'])
				.', "", '.$jo_db->Quote(serialize(array())).' )';
				$jo_db->setQuery( $query );
				$jo_db->query();
				unset( $ar_sql[$k] );
			}
		}

		/* Remove users from Glossword */
		/* Add guest */
		$ar_sql_joomla[1] = '';
		$query = 'DELETE FROM '.$ar['db_name'].'.'.$ar['table_prefix'].'users_jos '
				. ' WHERE id_group ="'.$id_usergroup.'" AND id_user NOT IN ('. implode( ',', array_keys( $ar_sql_joomla ) ).')';
		$jo_db->setQuery( $query );
		$jo_db->query();
			
		#print '<br /><pre>';
		
		#print_r( $ar_sql_joomla );
		#print_r( $ar_sql_gw );
		#print_r( $ar_sql_new_users );		
	}
			
}
