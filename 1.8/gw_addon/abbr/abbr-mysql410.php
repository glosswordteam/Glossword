<?php
$tmp['ar_queries'] = array(
	'get-abbr-lang' => 'SELECT b.id_lang
				FROM `'.$sys['tbl_prefix'].'abbr_phrase` AS b
				GROUP BY b.id_lang
			',
	'get-abbr-adm' => 'SELECT b.id_abbr, a.is_active, b.abbr_short, b.abbr_long
				FROM `'.$sys['tbl_prefix'].'abbr` AS a, `'.$sys['tbl_prefix'].'abbr_phrase` AS b
				WHERE a.id_abbr = b.id_abbr
				AND b.id_lang = "%s"
				AND a.id_group = "%d"
				ORDER BY a.id_group ASC, b.id_abbr, b.abbr_short, b.id_abbr
				%s
			',
	'get-abbr-by-id' => 'SELECT a.id_abbr, a.id_dict, b.id_abbr_phrase, a.id_group, b.id_lang, a.is_active, b.abbr_short, b.abbr_long
				FROM `'.$sys['tbl_prefix'].'abbr` AS a, `'.$sys['tbl_prefix'].'abbr_phrase` AS b
				WHERE a.id_abbr = b.id_abbr
				AND b.id_abbr = "%d"
				AND b.id_lang = "%s"
				LIMIT 1
			'
);
?>