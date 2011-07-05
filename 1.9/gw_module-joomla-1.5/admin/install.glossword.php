<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword
 * @copyright	 Dmitry N. Shilnikov, 2007-2010
 */

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.installer.installer' );
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );

function com_install()
{
	global $mainframe;
	$jo_db =& JFactory::getDBO();
	$jo_cfg =& JFactory::getConfig();

	/* Install plugin `Glossword Search` */
	$src = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_glossword'.DS.'plugins'.DS.'search'.DS;
	$dest = JPATH_ROOT.DS.'plugins'.DS.'search'.DS;
	
	$res = JFile::copy( $src.'gw_search.php', $dest.'gw_search.php' );
	$res = $res && JFile::copy( $src.'gw_search.xml', $dest.'gw_search.xml' );

	$jo_db->setQuery( "INSERT INTO #__plugins
				(id, name, element, folder, access, ordering, published, iscore, client_id, checked_out, checked_out_time, params)
				VALUES ('', 'Search - Glossword', 'gw_search', 'search', 0, 0, 1, 0, 0, 0, '0000-00-00 00:00:00', 'int_search_max=1000\r\nint_per_page=20')");
	$res = $res && $jo_db->query();
	if ( !$res )
	{
		JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'Glossword Search' ).': '.$src );
	}
	else
	{
		JFolder::delete( $src );
	}
	
	/* Install plugin `User - Glossword Login/Logout` */
	$src = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_glossword'.DS.'plugins'.DS.'user'.DS;
	$dest = JPATH_ROOT.DS.'plugins'.DS.'user'.DS;
	
	$res = JFile::copy( $src.'gw_login.php', $dest.'gw_login.php' );
	$res = $res && JFile::copy( $src.'gw_login.xml', $dest.'gw_login.xml' );

	$jo_db->setQuery( "INSERT INTO #__plugins
				(id, name, element, folder, access, ordering, published, iscore, client_id, checked_out, checked_out_time, params)
				VALUES ('', 'User - Glossword Login/Logout', 'gw_login', 'user', 0, 0, 1, 0, 0, 0, '0000-00-00 00:00:00', '')");
	$res = $res && $jo_db->query();
	if ( !$res )
	{
		JError::raiseWarning( 100, JText::sprintf( 'Plugin %s not installed. Please install it manually from the following folder', 'Glossword Login/Logout' ).': '.$src );
	}
	else
	{
		JFolder::delete( $src );
	}
	/* */
	JFolder::delete( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_glossword'.DS.'plugins'.DS );

	/**
	 * Copy visual theme files
	 */
	$ar_path_temp = explode( '/', str_replace( '\\', '/', $mainframe->getCfg( 'tmp_path' ) ) );
	$temp_folder = end( $ar_path_temp );
	$src = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_glossword'.DS.'gw-joomla'.DS;
	$dest = JPATH_ROOT.DS.$temp_folder.DS.'gw-joomla'.DS;
	JFolder::create( $dest );
	foreach ( glob( $src.'*.*' ) as $filename)
	{
		JFile::copy( $filename, $dest.basename( $filename ) );
	}
	JFolder::delete( $src );

	/* Try to update .htaccess */
	$htaccess_search = '(/|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)';
	$htaccess_replace = 'RewriteCond %{REQUEST_URI} (/|\.css|\.js|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]';
	
	if ( file_exists( JPATH_ROOT.'/.htaccess' ) && is_writable( JPATH_ROOT.'/.htaccess' ) )
	{
		$ar_file = file( JPATH_ROOT.'/.htaccess' );
		$is_write = 0;
		foreach ( $ar_file as $k => $line )
		{
			if ( strpos( $line, $htaccess_search ) !== false 
				&& strpos( $line, '### Commented ###' ) === false
				)
			{
				$ar_file[$k] = '### Commented ### ' . $ar_file[$k];
				/* Insert line */
				$ar_file = array_merge(
					array_slice( $ar_file, 0, ($k + 1) ),
					array( ($k + 1) => '## Glossword - End'."\n" ),
					array_slice( $ar_file, ($k + 1) )
				);
				$ar_file = array_merge(
					array_slice( $ar_file, 0, ($k + 1) ),
					array( ($k + 1) => $htaccess_replace."\n" ),
					array_slice( $ar_file, ($k + 1) )
				);
				$ar_file = array_merge(
					array_slice( $ar_file, 0, ($k + 1) ),
					array( ($k + 1) => '## Glossword - Start'."\n" ),
					array_slice( $ar_file, ($k + 1) )
				);
				$is_write = 1;
				print '<br />Found '. htmlspecialchars( $line );
			}
		}
		if ( $is_write && file_put_contents( JPATH_ROOT.'/.htaccess', implode('', $ar_file) ) )
		{
			print '<br />Updated `'.JPATH_ROOT.'/.htaccess`';
		}
	}
	else
	{
		/* Replace failed */
		print '<div>';
		print 'In order to use Apache mod_rewrite, update file `'.JPATH_ROOT.'/.htaccess`:';
		print '<br />Search:';
		print '<br />RewriteCond %{REQUEST_URI} '.$htaccess_search.'$  [NC]';
		print '<br />Replace with:';
		print '<br />'.$htaccess_replace;
		print '</div>';
	}
	
	/* */

	$href = JRoute::_( 'index.php?option=com_glossword&task=configure' );
	print '<div class="quote" style="text-align: center;">';
	print '<h1>Glossword installation complete.</h1>';
	print '<h3>'. JText::sprintf( 'Follow to <a href="%s">Configure component</a>, review the values and Save the configuration.', $href ).'</h3>';
	print '<h3>'. JText::sprintf( 'Перейдите к <a href="%s">настройкам компонента</a>, проверьте значения и Сохраните конфигурацию.', $href ).'</h3>';

}
?>