<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword
 * @copyright	© Dmitry N. Shilnikov, 2007-2010
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

/* no direct access */
defined( '_JEXEC' ) or die( 'Restricted access' );

/* Require the base controller */
require_once( JPATH_COMPONENT.DS.'controller.php' );
require_once( JApplicationHelper::getPath('admin_html') );

define( 'GW_ID_GROUP_USERS', 3 );
define( 'GW_ID_GROUP_ADMINS', 1);

$controller = new GlosswordController();
$task = JRequest::getVar( 'task' );

JSubMenuHelper::addEntry( JText::_( 'LJADM0001' ), 'index.php?option=com_glossword', true );
JSubMenuHelper::addEntry( JText::_( 'LJADM0002' ), 'index.php?option=com_glossword&task=configure', true );

switch ( strtolower( $task ) )
{
	case 'configure':
		GlosswordController::admin_configure();
	break;
	default:
		GlosswordController::admin_cph();
	break;
}
?>