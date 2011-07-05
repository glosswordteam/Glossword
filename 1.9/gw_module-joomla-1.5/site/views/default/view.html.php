<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword
 * @copyright	 Dmitry N. Shilnikov, 2007-2010
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class glosswordViewdefault extends JView
{
	function display($tpl = null)
	{
		global $mainframe;

		$jo_user =& JFactory::getUser();
		$jo_db =& JFactory::getDBO();
		$jo_document =& JFactory::getDocument();
		$jo_pathway = & $mainframe->getPathway();
		$jo_uri =& JFactory::getURI();
		$jo_cfg =& JFactory::getConfig();
		$jo_user =& JFactory::getUser();
		$jo_component = &JComponentHelper::getComponent('com_glossword');
		$jo_menu = &JSite::getMenu();
		$jo_items = $jo_menu->getItems('componentid', $jo_component->id, true);

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
		/* Custom users tablename */
		$ar_cfg['db_table_users'] = 'users_jos';
		$ar_cfg['db_table_groups'] = 'usergroups_jos';
		$ar_cfg['db_table_sessions'] = 'sessions_jos';
		/* Send HTTP-headers */
		$ar_cfg['is_send_headers'] = 0;

		/* ------------------------------------------------- */
		error_reporting(E_ALL);
		if (!defined('IS_IN_SITE')) { define('IS_IN_SITE', 1); }
		if (!defined('SITE_THIS_SCRIPT')) { define('SITE_THIS_SCRIPT', 'index.php'); }

		/* mktime() called once */
		$_SERVER['REQUEST_TIME'] = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : mktime();

		/* Current script instance */
		$o = crc32( $_SERVER['REQUEST_TIME'] );

		/* Load configuration class */
		if ( file_exists( $ar_cfg['path_core_abs'] ) )
		{
			require_once( $ar_cfg['path_core_abs'].'/gw_config.php' );
		}
		else
		{
			JError::raiseWarning( 100, JText::sprintf( 'LJ1000', '<strong>'. $ar_cfg['path_core_abs'].'/gw_config.php</strong>' ) );
			return;
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
		$ar_cfg['is_sef'] = $jo_cfg->getValue( 'sef' );
		
		/* Always use suffix */
		#$ar_cfg['sef_output'] = $jo_cfg->getValue( 'sef_suffix' ) ? 'html' : 'html';
		$ar_cfg['sef_output'] = 'html';
		$ar_cfg['sef_fileindex'] = 'index.php?';
		$ar_cfg['sef_append'] = array(
			'option' => $jo_component->option,
			'view' => isset( $jo_items->query['view'] ) ? $jo_items->query['view'] : 'default',
			'Itemid' => isset( $jo_items->id ) ? $jo_items->id : ''
		);
		/* Additional URL parameters for SEF-mode */
		if ( $ar_cfg['is_sef'] )
		{
			$ar_cfg['sef_fileindex'] = '/index.php';
			/* Used for SEF */
			$ar_cfg['sef_append']['alias'] = $ar_cfg['sef_append']['arg']['alias'] = isset( $jo_items->alias ) ?  $jo_items->alias : 'glossword';
		}
		/* Adjust for mod_rewrite */
		if ( $jo_cfg->getValue( 'sef_rewrite' ) )
		{
			$ar_cfg['sef_fileindex'] = '';
		}
	
		/* Append to URL - backend */
		$ar_cfg['is_sef_admin'] = 0;
		$ar_cfg['sef_output_admin'] = 'html';
		$ar_cfg['sef_fileindex_admin'] = 'index.php?';
		$ar_cfg['sef_append_admin'] = array( 'option' => $jo_component->option );
		$ar_cfg['sef_rule_admin'] = array();

		/* Append to all URLs used for AJAX-requests */
		$ar_cfg['sef_append_ajax'] = array( 'format' => 'ajax' );
		
		/* SEF. Only variables inside arg[]. arg[alias], arg[area], arg[sef_output] */
		$ar_cfg['sef_rule'] = array( '' => '/alias/area/sef_filename.sef_output' );

		#print_r( $ar_cfg );
		
		/* Use existend database connection */
		$ar_cfg['db_host'] = $mainframe->getCfg('host');
		$ar_cfg['db_user'] = $mainframe->getCfg('user');
		$ar_cfg['db_pass'] = $mainframe->getCfg('password');
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

		${$o}->a( 'oTimer', new tkit_timer );

		/* Register a global variables */
		${$o}->global_variables( array('arg', 'arp', GW_SITE_SID, GW_SITE_SID.'r') );
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
		$jo_document->addStyleSheet( JRoute::_( 'index.php?option=com_glossword&view=default&format=css&arg[files]='.${$o}->oOutput->get_css_collection().'&arg[sef_output]=css' ) );
		
		/* Add JS */
		$jo_document->addScript( JRoute::_( 'index.php?option=com_glossword&view=default&format=js&arg[files]='.${$o}->oOutput->get_js_collection().'&arg[sef_output]=js' ) );

		/* Changing MIME-type */
		$format = JRequest::getVar( 'format' );
		
		switch ( $format )
		{
			case 'ajax': $jo_document->setMimeEncoding( 'text/plain' ); break;
			case 'css': $jo_document->setMimeEncoding( 'text/css' ); break;
			case 'js': $jo_document->setMimeEncoding( 'text/javascript' ); break;
		}
		
		/* ------------------------------------------------- */
		
		/* Page title */
		$jo_document->setTitle( self::unhtmlspecialamp( ${$o}->oOutput->get_html_title() ) );
		
		/* Set Breadcrumbs */
		$ar_breadcrumbs = ${$o}->oOutput->get_breadcrumbs();
		foreach ( $ar_breadcrumbs as $k => $bc )
		{
			/* Last chunk */
			if ( $k == sizeof( $bc ) - 1 )
			{
				$bc[1] = '';
			}
			$jo_pathway->addItem( $bc[0], $bc[1] );
		}
		#parent::display($tpl);
	}
	public function unhtmlspecialamp( $s )
	{
		if (!is_string($s)){ return $s; }
		$s = str_replace('&amp;', '&', $s);
		$s = str_replace('&quot;', '"', $s);
		$s = str_replace('&AMP;', '&', $s);
		$s = str_replace('&QUOT;', '"', $s);
		$s = str_replace('&#039;', '\'', $s);
		return $s;
	}
}

?>