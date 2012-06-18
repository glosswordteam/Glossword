<?php
$tmp['ar_queries'] = array(
	'get-custompages-lang-adm' => 'SELECT gpph.page_title, gpph.page_content, gpph.page_descr, gpph.page_keywords, gpph.id_lang, gpph.id_page_phrase
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE gp.id_page = "%d"
						AND gp.id_page = gpph.id_page
						GROUP BY gpph.id_lang
					',
	'get-custompages-adm' => 'SELECT gp.is_active, gp.date_created, gp.date_modified, 
						gp.page_php_1, gp.page_php_2, gp.page_icon, gp.page_uri, gp.id_user
						FROM `'.$sys['tbl_prefix'].'pages` AS gp
						WHERE gp.id_page = "%d"
					',
);
/* end of file */
?>