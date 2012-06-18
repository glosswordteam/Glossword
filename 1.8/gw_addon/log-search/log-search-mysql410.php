<?php
$tmp['ar_queries'] = array(
	'get-search_results' => 'SELECT *
				FROM `'.$sys['tbl_prefix'].'stat_search` AS sr
				WHERE %s
				ORDER BY sr.date_created DESC
				%s
			',
	'cnt-rp01' => 'SELECT count(*) AS n
				FROM `'.$sys['tbl_prefix'].'stat_search` AS sr
				WHERE %s
				%s
				ORDER BY sr.date_created DESC
				%s
			',
	'get-rp01' => 'SELECT *
				FROM `'.$sys['tbl_prefix'].'stat_search` AS sr
				WHERE %s
				%s
				ORDER BY sr.date_created DESC
				%s
			',
	'cnt-rp03' => 'SELECT count(*) AS n
				FROM `'.$sys['tbl_prefix'].'stat_search` 
				WHERE found = 0
				GROUP BY q
			',
	'get-rp03' => 'SELECT count(*) AS n, q, found
				FROM `'.$sys['tbl_prefix'].'stat_search` 
				WHERE found = 0
				GROUP BY q
				ORDER BY n DESC
				%s
			',
	'cnt-rp04' => 'SELECT count(*) AS n
				FROM `'.$sys['tbl_prefix'].'stat_search` 
				WHERE found > 0
				GROUP BY q
			',
	'get-rp04' => 'SELECT count(*) AS n, q, found
				FROM `'.$sys['tbl_prefix'].'stat_search` 
				WHERE found > 0
				GROUP BY q
				ORDER BY n DESC
				%s
			',
	'cnt-search_results' => 'SELECT count(*) AS n
				FROM `'.$sys['tbl_prefix'].'stat_search` AS sr
				WHERE %s
			',
);
?>