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


/**
* @package		Joomla
* @subpackage	Glossword
*/
class HTML_glossword
{
	function showform($ar)
	{
		global $mainframe;

		$jo_uri =& JFactory::getURI();
		$ar_url = parse_url( $jo_uri->toString() );

		$ar_path = explode('/', $ar_url['path']);
		unset($ar_path[sizeof($ar_path)-1]);
		$server_dir_admin = implode( '/', $ar_path );
		unset($ar_path[sizeof($ar_path)-1]);
		$server_dir = implode( '/', $ar_path );
		$ar_path_temp = explode( '/', str_replace( '\\', '/', $mainframe->getCfg( 'tmp_path' ) ) );

		$str = '';
		$str .= '<div class="col100">';
		$str .= '<form action="index.php" method="post" name="adminForm">';
		$str .= '<fieldset class="adminform">';
		$str .= '<legend>'. JText::_( 'LJADM0002' ) . '</legend>';
		$str .= '<table class="admintable" style="color:#888" width="100%">';
		$str .= '<tbody>';

		for (; list($k, $v) = each($ar); )
		{
			$str_prepend = $str_append = '';
			if ( $k == 'path_core_abs' )
			{
				$str_append = '<br />/public_html/joomla/components/com_glossword/core';
			}
			/* 16 Jan 2010 - Disable changing database settings */
			/* Read from the system */
			if ($k == 'db_driver' && !$v) { continue; $v = $mainframe->getCfg('dbtype'); }
			if ($k == 'db_host' && !$v) { continue; $v = $mainframe->getCfg('host'); }
			if ($k == 'db_name' && !$v) { $v = $mainframe->getCfg('db'); }
			if ($k == 'db_user' && !$v) { continue; $v = $mainframe->getCfg('user'); }
			if ($k == 'db_pass' && !$v) { continue; $v = $mainframe->getCfg('password'); }
			if ($k == 'server_proto' && !$v) { $v = $jo_uri->getScheme().'://'; }
			if ($k == 'server_host' && !$v) { $v = $jo_uri->getHost(); }
			if ($k == 'table_prefix' && !$v) { $v = 'jos_gw_'; }
			if ($k == 'server_dir' && !$v) { $v = $server_dir; }
			if ($k == 'server_dir_admin' && !$v) { $v = $server_dir_admin; }
			if ($k == 'path_temp_abs' && !$v) { $v = str_replace( '\\', '/', $mainframe->getCfg('tmp_path') ); }
			if ($k == 'path_temp_web' && !$v) { $v = end($ar_path_temp); }
			if ($k == 'path_core_abs' && !$v) { $v = str_replace( '\\', '/', JPATH_ROOT.'/components/com_glossword/core'); }

			if ( $k == 'path_temp_web' )
			{
				$str_append = '<br />'.JText::sprintf( 'LJADM0005', $ar['server_dir'].'/<strong>'.$v.'</strong>/gw_joomla/header.png');
			}
			
			$str_td2 = '<td>'.$str_prepend.'<input style="width:50%" size="45" class="inputbox" type="text" name="arp['.$k.']" value="'. $v .'" />'.$str_append.'</td>';
			$str_key = JText::_( $k );

			/* */
			if ( $k == 'db_driver' )
			{
				$ar_drivers = array('mysqli' => 'MySQLi');
				$str_option = '';
				for (;list($k2, $v2) = each($ar_drivers);)
				{
					$s = '';
					if (strval($k2) == strval($v))
					{
						/* Single */
						$s = ' selected="selected"';
					}
					$str_option .= sprintf('<option value="%s"%s>%s</option>', $k2, $s, $v2);
				}
				$str_td2 = '<td><select style="width:50%" name="arp['.$k.']">'.$str_option.'</select></td>';
			}
			$str .= '<tr>';
			$str .= '<td class="key">'. $str_key .'</td>';
			$str .= $str_td2;
			$str .= '</tr>';
		}
		$str .= '<tr><td style="width:30%"></td><td style="width:70%"></td></tr>';
		$str .= '</tbody>';
		$str .= '</table>';
		$str .= '</fieldset>';
		$str .= '<input type="hidden" name="option" value="com_glossword" />';
		$str .= '<input type="hidden" name="arp[form]" value="1" />';
		$str .= '<input type="hidden" name="task" value="configure" />';
		$str .= '</form>';
		$str .= '</div>';
		return $str;
	}
}
?>