<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword Login/Logout
 * @copyright	 Dmitry N. Shilnikov, 2007-2010
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

/* Session ID. See also core_prepend.php */
define( 'GW_SITE_SID', 'gw-sid' );
$_SERVER['REQUEST_TIME'] = isset( $_SERVER['REQUEST_TIME'] ) ? $_SERVER['REQUEST_TIME'] : mktime();

$mainframe->registerEvent( 'onLogoutUser', 'plgLogoutUserGlossword' );

/*
$user = Array ( [username] => moderator [id] => 64 )
*/
function &plgLogoutUserGlossword( $user )
{
	$db =& JFactory::getDBO();

	/* Load component configuration */
	$query = 'SELECT * FROM #__gw_config ORDER BY `setting_key`';
	$db->setQuery( $query );
	$ar_sql = $db->loadAssocList();
	$ar_cfg = array();
	foreach ( $ar_sql as $k => $v )
	{
		$ar_cfg[$v['setting_key']] = $v['setting_value'];
	}
	/* Remove cookie */
	setcookie( GW_SITE_SID, 'NULL', ($_SERVER['REQUEST_TIME'] - 86400), '/' );
	setcookie( GW_SITE_SID.'r', 'NULL', ($_SERVER['REQUEST_TIME'] - 86400), '/' );

	/* Remove session */
	$query = 'DELETE FROM '.$ar_cfg['db_name'].'.'.$ar_cfg['table_prefix'].'sessions_jos WHERE `id_user` = "'.$user['id'].'"';
	$db->setQuery( $query );
	$db->query();

	return true;
}
?>