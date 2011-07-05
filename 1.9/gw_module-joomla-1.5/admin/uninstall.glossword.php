<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword
 * @copyright	© Dmitry N. Shilnikov, 2007-2010
 */

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.installer.installer' );
jimport( 'joomla.filesystem.file' );

function com_uninstall()
{
	$jo_db =& JFactory::getDBO();

	/* Uninstall plugin `Glossword Search` */
	$path = JPATH_ROOT.DS.'plugins'.DS.'search'.DS;
	$res = JFile::delete($path.'gw_search.php');
	$res = $res && JFile::delete($path.'gw_search.xml');
	$jo_db->setQuery("DELETE FROM `#__plugins` WHERE `folder` = 'search' AND `element` = 'gw_search' LIMIT 2");
	$res = $res && $jo_db->query();
	if ( !$res )
	{
		JError::raiseWarning( 100, JText::sprintf( 'Plugin %s could not be uninstalled. Please, uninstall it manually.', 'Glossword Search' ).': '.$src );
	}

	/* Uninstall plugin `Glossword Login/Logout` */
	$path = JPATH_ROOT.DS.'plugins'.DS.'user'.DS;
	$res = JFile::delete($path.'gw_login.php');
	$res = $res && JFile::delete($path.'gw_login.xml');
	$jo_db->setQuery("DELETE FROM `#__plugins` WHERE `folder` = 'user' AND `element` = 'gw_login' LIMIT 2");
	$res = $res && $jo_db->query();
	if ( !$res )
	{
		JError::raiseWarning( 100, JText::sprintf( 'Plugin %s could not be uninstalled. Please, uninstall it manually.', 'Glossword Login/Logout' ).': '.$src );
	}

	print '<h3>Glossword succesfully uninstalled.</h3>';
}
?>