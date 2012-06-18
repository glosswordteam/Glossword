<?php
$tmp['ar_queries'] = array(
	'get-custom_az-profiles-adm' => 'SELECT *
						FROM `'.$sys['tbl_prefix'].'custom_az_profiles`
						ORDER BY profile_name
				',
	'get-custom_az-adm' => 'SELECT id_letter, int_sort, az_value, az_value_lc
				FROM `'.$sys['tbl_prefix'].'custom_az`
				WHERE id_profile = "%s"
				ORDER BY int_sort
				',
	'get-custom_az-profile' => 'SELECT *
				FROM `'.$sys['tbl_prefix'].'custom_az_profiles`
				WHERE id_profile = "%d"
				ORDER BY profile_name
				',
);
?>