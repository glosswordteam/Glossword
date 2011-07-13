<?php

// No direct access to this file
defined( '_JEXEC' ) or die( 'Restricted access' );

class com_glosswordInstallerScript {

	function install( $parent ) {
		
		$jo_app = & JFactory::getApplication();
		$jo_db = & JFactory::getDbo();
		$jo_cfg = & JFactory::getConfig();
		$jo_uri = & JFactory::getURI();
		$ar_url = parse_url( $jo_uri->toString() );
		$jo_user = & JFactory::getUser();
		
		/**
		 * -------------------------
		 * Configure Component 
		 * -------------------------
		 */		
		$ar_path = explode( '/', $ar_url['path'] );
		unset( $ar_path[sizeof( $ar_path ) - 1] );
		$server_dir_admin = implode( '/', $ar_path );
		unset( $ar_path[sizeof( $ar_path ) - 1] );
		$server_dir = implode( '/', $ar_path );
		$ar_path_temp = explode( '/', str_replace( '\\', '/', $jo_app->getCfg( 'tmp_path' ) ) );

		/* Load component configuration */
		$query = $jo_db->getQuery( true );
		$query->select( '*' )->from( '#__gw_config' );
		$jo_db->setQuery( ( string ) $query );
		$jo_db->query();
		$ar_sql = $jo_db->loadAssocList();

		$ar_cfg = array( );
		foreach ( $ar_sql as $k_row => $v_row ) {
			$k = $v_row['setting_key'];
			$v = $v_row['setting_value'];
			if ( $k == 'db_name' && !$v ) {
				$v = $jo_app->getCfg( 'db' );
			}
			if ( $k == 'server_proto' && !$v ) {
				$v = $jo_uri->getScheme() . '://';
			}
			if ( $k == 'server_host' && !$v ) {
				$v = $jo_uri->getHost();
			}
			if ( $k == 'table_prefix' && !$v ) {
				$v = 'jos_gw_';
			}
			if ( $k == 'server_dir' && !$v ) {
				$v = $server_dir;
			}
			if ( $k == 'server_dir_admin' && !$v ) {
				$v = $server_dir_admin;
			}
			if ( $k == 'path_temp_abs' && !$v ) {
				$v = str_replace( '\\', '/', $jo_app->getCfg( 'tmp_path' ) );
			}
			if ( $k == 'path_temp_web' && !$v ) {
				$v = end( $ar_path_temp );
			}
			if ( $k == 'path_core_abs' && !$v ) {
				$v = str_replace( '\\', '/', JPATH_ROOT . '/components/com_glossword/core' );
			}
			$ar_cfg[$k] = $v;
		}
		foreach ( $ar_cfg as $k => $v ) {
			$query = $jo_db->getQuery( true );
			$query->update( '#__gw_config' );
			$query->set( '`setting_value`=' . $jo_db->quote( $v ) );
			$query->where( '`setting_key`=' . $jo_db->quote( $k ) );
			$jo_db->setQuery( ( string ) $query );
			$jo_db->query();
		}
		
		/**
		 * -------------------------
		 * Install plugin `User - Glossword Login/Logout`
		 * -------------------------
		 */
		$src = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_glossword' . DS . 'plugins' . DS . 'user' . DS;
		$dest = JPATH_ROOT . DS . 'plugins' . DS . 'user' . DS . 'gw_login' . DS;
		$manifest_details = JApplicationHelper::parseXMLInstallFile( $src . 'gw_login.xml' );

		$res = JFolder::move( $src, $dest );
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'Glossword' ).': '.$src );
		}		
		$query = $jo_db->getQuery( true );
		$query->insert( '#__extensions' );
		foreach ( array(
			'name' => 'Glossword Log In/Log Out',
			'type' => 'plugin',
			'element' => 'gw_login',
			'folder' => 'user',
			'client_id' => $jo_user->id,
			'enabled' => 1,
			'access' => 0,
			'protected' => 0,
			'manifest_cache' => json_encode( $manifest_details ),
			'params' => '{}'
		) as $k => $v ) {
			$query->set( $k . '=' . $jo_db->quote( $v ) );
		}
		$jo_db->setQuery( ( string ) $query );
		$res = $res && $jo_db->query();
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'plg_login_glossword' ) . ': ' . $src );
		}
		
		/**
		 * -------------------------
		 * Install plugin `Search - Glossword Search`
		 * -------------------------
		 */
		$src = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_glossword' . DS . 'plugins' . DS . 'search' . DS;
		$dest = JPATH_ROOT . DS . 'plugins' . DS . 'search' . DS . 'gw_search' . DS;
		$manifest_details = JApplicationHelper::parseXMLInstallFile( $src . 'gw_search.xml' );
		$res = JFolder::move( $src, $dest );
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'Glossword' ).': '.$src );
		}
		$query = $jo_db->getQuery( true );
		$query->insert( '#__extensions' );
		foreach ( array(
			'name' => 'Glossword Search',
			'type' => 'plugin',
			'element' => 'gw_search',
			'folder' => 'search',
			'client_id' => $jo_user->id,
			'enabled' => 1,
			'access' => 0,
			'protected' => 0,
			'manifest_cache' => json_encode( $manifest_details ),
			'params' => '{}'
		) as $k => $v ) {
			$query->set( $k . '=' . $jo_db->quote( $v ) );
		}
		$jo_db->setQuery( ( string ) $query );
		$res = $res && $jo_db->query();
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'plg_login_glossword' ) . ': ' . $src );
		}
	
		
		/**
		 * -------------------------
		 *  Copy visual theme files
		 * -------------------------
		 */
		$ar_path_temp = explode( '/', str_replace( '\\', '/', $jo_app->getCfg( 'tmp_path' ) ) );
		$temp_folder = end( $ar_path_temp );
		$src = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_glossword' . DS . 'gw-joomla' . DS;
		$dest = JPATH_ROOT . DS . $temp_folder . DS . 'gw-joomla' . DS;
		$res = JFolder::move( $src, $dest );
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Visual theme is not installed. Please install it manually from the following folder', 'Glossword' ) . ': ' . $src );
		}
		
		
		/**
		 * -------------------------
		 * Remove empty folders
		 * -------------------------
		 */
		JFolder::delete( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_glossword' . DS . 'plugins' );
		JFolder::delete( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_glossword' . DS . 'gw-joomla' );
		
		/**
		 * -------------------------
		 * Installation complete
		 * -------------------------
		 */
		$href_menu = JRoute::_( 'index.php?option=com_menus&view=item&layout=edit&menutype=usermenu' );
		$href_cp = JRoute::_( 'index.php?option=com_glossword' );
		echo '<div class="quote" style="text-align:center">';
		echo '<h1>' . JText::_( 'COM_GLOSSWORD' ) . '</h1>';
		echo '<h3>' . JText::sprintf( 'COM_GLOSSWORD_INSTALL_CP', $href_cp ) . '</h3>';
		echo '<h3>' . JText::sprintf( 'COM_GLOSSWORD_INSTALL_ADDTOMENU', $href_menu ) . '</h3>';
		echo '</div>';
		
		/**
		 * -------------------------
		 * Try to update .htaccess
		 * -------------------------
		 */
		$htaccess_search = '(/|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)';
		$htaccess_replace = 'RewriteCond %{REQUEST_URI} (/|\.css|\.js|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]';

		if ( file_exists( JPATH_ROOT . '/.htaccess' ) && is_writable( JPATH_ROOT . '/.htaccess' ) ) {
			$ar_file = file( JPATH_ROOT . '/.htaccess' );
			$is_write = 0;
			foreach ( $ar_file as $k => $line ) {
				if ( strpos( $line, $htaccess_search ) !== false
						&& strpos( $line, '### Commented ###' ) === false
				) {
					$ar_file[$k] = '### Commented ### ' . $ar_file[$k];
					/* Insert line */
					$ar_file = array_merge(
							array_slice( $ar_file, 0, ($k + 1 ) ), array( ($k + 1) => '## Glossword - End' . "\n" ), array_slice( $ar_file, ($k + 1 ) )
					);
					$ar_file = array_merge(
							array_slice( $ar_file, 0, ($k + 1 ) ), array( ($k + 1) => $htaccess_replace . "\n" ), array_slice( $ar_file, ($k + 1 ) )
					);
					$ar_file = array_merge(
							array_slice( $ar_file, 0, ($k + 1 ) ), array( ($k + 1) => '## Glossword - Start' . "\n" ), array_slice( $ar_file, ($k + 1 ) )
					);
					$is_write = 1;
					print '<br />Found ' . htmlspecialchars( $line );
				}
			}
			if ( $is_write && file_put_contents( JPATH_ROOT . '/.htaccess', implode( '', $ar_file ) ) ) {
				print '<br />Updated `' . JPATH_ROOT . '/.htaccess`';
			}
		}
		else {
			/* Replace failed */
			print '<div>';
			print JText::sprintf( 'COM_GLOSSWORD_INSTALL_MODREWRITE_APACHE', JPATH_ROOT . '/.htaccess' );
			print '<br />' . JText::_( 'COM_GLOSSWORD_INSTALL_SEARCH' ) . ':';
			print '<br />RewriteCond %{REQUEST_URI} ' . $htaccess_search . '$  [NC]';
			print '<br />' . JText::_( 'COM_GLOSSWORD_INSTALL_REPLACE' ) . ':';
			print '<br />' . $htaccess_replace;
			print '</div>';
		}
		
	}


	function uninstall( $parent ) {
		
		echo '<ul><li>Uninstall starts.</li>';
		
		$jo_db = & JFactory::getDbo();
		$jo_cfg = & JFactory::getConfig();
		$jo_user = & JFactory::getUser();
		$jo_component = & JComponentHelper::getComponent( 'com_glossword' );

		/**
		 * -------------------------
		 * Remove menu items
		 * -------------------------
		 */
		echo '<li>Removing menu items... ';

		$query = $jo_db->getQuery( true );
		$query->delete( '#__menu' );
		$query->where( '`component_id`=' . $jo_db->quote( $jo_component->id ) );
		$query->where( '`menutype`<>' . $jo_db->quote( 'main' ) );
		$jo_db->setQuery( ( string ) $query );
		$res = $jo_db->query();

		echo ( $res ? 'OK' : 'Error: ' . $jo_db->getError() );
		echo '</li>';

		/**
		 * -------------------------
		 * Remove from assets
		 * -------------------------
		 */
		echo '<li>Checking database... ';
		$query = $jo_db->getQuery( true );
		$query->delete( '#__assets' );
		foreach ( array(
			'name' => 'com_glossword'
		) as $k => $v ) {
			$query->where( $k . '=' . $jo_db->quote( $v ) );
		}
		$jo_db->setQuery( ( string ) $query );
		$res = $jo_db->query();
		echo ( $res ? 'OK' : 'Error: ' . $jo_db->getError() );
		echo '</li>';
		
		/**
		 * -------------------------
		 * Uninstall plugin `Glossword Log In/Log Out` 
		 * -------------------------
		 */
		echo '<li>Removing "Glossword Log In/Log Out" plugin... ';
		$dest = JPATH_ROOT . DS . 'plugins' . DS . 'user' . DS . 'gw_login' . DS;
		
		$query = $jo_db->getQuery( true );
		$query->delete( '#__extensions' );
		foreach ( array(
			'type' => 'plugin',
			'folder' => 'user',
			'element' => 'gw_login',
		) as $k => $v ) {
			$query->where( $k . '=' . $jo_db->quote( $v ) );
		}
		$jo_db->setQuery( ( string ) $query );
		$res = $jo_db->query();
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s could not be uninstalled. Please, uninstall it manually.', 'Glossword' ) . ': ' . $dest );
		}
		else {
			$res = JFolder::delete( $dest );
		}
		echo ( $res ? 'OK' : 'Error: ' . $jo_db->getError() );
		echo '</li>';	
				
		
		/**
		 * -------------------------
		 * Uninstall plugin `Glossword Search` 
		 * -------------------------
		 */
		echo '<li>Removing "Glossword Search" plugin... ';
		$dest = JPATH_ROOT . DS . 'plugins' . DS . 'search' . DS . 'gw_search' . DS;
		
		$query = $jo_db->getQuery( true );
		$query->delete( '#__extensions' );
		foreach ( array(
			'type' => 'plugin',
			'folder' => 'search',
			'element' => 'gw_search',
		) as $k => $v ) {
			$query->where( $k . '=' . $jo_db->quote( $v ) );
		}
		$jo_db->setQuery( ( string ) $query );
		$res = $jo_db->query();
		if ( !$res ) {
			JError::raiseWarning( 100, JText::sprintf( 'Plugin %s could not be uninstalled. Please, uninstall it manually.', 'Glossword' ) . ': ' . $dest );
		}
		else {
			$res = JFolder::delete( $dest );
		}
		echo ( $res ? 'OK' : 'Error: ' . $jo_db->getError() );
		echo '</li>';
		
		
		
		echo '<li>Uninstall ends.</li></ul>';

		#print '<h3>Glossword succesfully uninstalled.</h3>';		
		
		return true;
	}


	function preflight( $type, $parent ) {

	}


	function postflight( $type, $parent ) {

	}

}