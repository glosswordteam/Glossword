<?php
$tmp['ar_queries'] = array(
	'get-topics-lang-adm' => 'SELECT tpph.topic_title, tpph.topic_descr, tpph.id_lang, tpph.id_topic_phrase
						FROM `'.$sys['tbl_prefix'].'topics` AS tp, `'.$sys['tbl_prefix'].'topics_phrase` AS tpph
						WHERE tp.id_topic = "%d"
						AND tp.id_topic = tpph.id_topic
						GROUP BY tpph.id_lang
					',
	'get-topics-adm' => 'SELECT tp.is_active, tp.date_created, tp.date_modified, 
						tp.topic_icon, tp.id_user
						FROM `'.$sys['tbl_prefix'].'topics` AS tp
						WHERE tp.id_topic = "%d"
					',

);
/* end of file */
?>