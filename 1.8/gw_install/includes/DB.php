<?php
if (!defined('IS_CLASS_CI_DB')) { define('IS_CLASS_CI_DB', 1);

function ci_log_message($id_err, $msg)
{
	# print '<br />'. $id_err .' '. $msg;
}
function show_error($msg)
{
	# print '<div class="debugwindow">'.$msg.'</div>';
}
function &ci_db($params, $path)
{
	global $gw2_oDb;
	include($path.'/DB_driver.php');
	include($path.'/DB_active_rec.php');
	include($path.'/DB_forge.php');
	if (!class_exists('CI_DB'))
	{
		eval('class CI_DB extends CI_DB_active_record { }');
	}
	$driver = 'CI_DB_'.$params['dbdriver'].'_driver';
	$forge = 'CI_DB_'.$params['dbdriver'].'_forge';
	include($path.'/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php');
	include($path.'/'.$params['dbdriver'].'/'.$params['dbdriver'].'_forge.php');
	$gw2_oDb = new $driver($params);
	$gw2_oDb->initialize();
	$gw2_oDb->forge = new $forge;
	return $gw2_oDb;
}
function gw2_get_db_instance()
{
	global $gw2_oDb;
	if (is_object($gw2_oDb))
	{
		return $gw2_oDb;
	}
}
}
?>