<?php
$tmp['ar_queries'] = array(
	'get-theme-adm' => 'SELECT ths.settings_key, ths.settings_value
						FROM '.$sys['tbl_prefix'].'theme AS th, '.$sys['tbl_prefix'].'theme_settings AS ths
						WHERE th.id_theme = "%s"
						AND th.id_theme = ths.id_theme
						ORDER BY ths.setting_key
					',
	'get-settings-by-gp' => 'SELECT s.settings_key, s.settings_value, g.*
						FROM '.$sys['tbl_prefix'].'theme_settings AS s, '.$sys['tbl_prefix'].'theme_group AS g
						WHERE s.settings_key = g.settings_key
						AND s.id_theme = "%s"
						AND g.id_group = "%d"
						ORDER BY g.id_group, g.int_sort
			',
);
?>