<?php
class gw_setup_uninstall extends gw_setup
{
	/* See also class.uninstall.php */
	var $arTables = array('abbr', 'abbr_phrase', 'component', 'component_map', 'component_actions',
		'dict', 'map_user_to_dict', 'map_user_to_term', 'pages', 'pages_phrase', 'search_results',
		'sessions', 'settings', 'stat_dict', 'stat_search', 'theme', 'theme_group', 'captcha',
		'theme_settings', 'topics', 'topics_phrase', 'users', 'wordlist', 'wordmap',
		'history_terms', 'custom_az', 'custom_az_profiles', 'virtual_keyboard','auth_restore'
	);
	/* */
	function uninstall_step_1()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1166') );
		$this->oTpl->a( 'v:html_descr', '' );

		$arSqlTables = array();
		$arSqlTablesRemove = array();
		$int_sum_kb = 0;
		for (reset($this->arTables); list($k, $v) = each($this->arTables);)
		{
			if ($arTableInfo = $this->oDb->table_info($this->sys['tbl_prefix'].$v) )
			{
				$int_sum_kb += ($arTableInfo['Data_length']+$arTableInfo['Index_length']);
				$arSqlTablesRemove[] = $this->sys['tbl_prefix'].$v;
			}
			else
			{
				$arSqlTables[] = $this->sys['tbl_prefix'].$v;
			}
		}
		$sql = sprintf('SELECT tablename FROM `%s`', $this->sys['tbl_prefix'].'dict');
		$arSql = $this->oDb->sqlExec($sql);
		while (list($k, $arV) = each($arSql))
		{
			if ($arTableInfo = $this->oDb->table_info($arV['tablename']) )
			{
				$int_sum_kb += ($arTableInfo['Data_length']+$arTableInfo['Index_length']);
				$arSqlTablesRemove[] = $arV['tablename'];
			}
			else
			{
				$arSqlTables[] = $arV['tablename'];
			}
		}
		$this->ar_status[0] = $this->oL->m('1169');
		$this->ar_status[0] .=  sprintf('<br/>%s: <b>%s</b>', $this->oL->m('1208'), GW_DB_DATABASE);
		$this->ar_status[0] .= '<br /><tt>' .implode('</tt>, <tt>', $arSqlTablesRemove). '</tt>';
		$this->ar_status[0] .= '<br />'.$this->oFunc->number_format($int_sum_kb).' '.$this->oL->m('bytes');
		/* Some of tables were not found */
		if (!empty($arSqlTables))
		{
			$this->ar_status[] = $this->oL->m('1233').':<br /><tt>'.implode('</tt>, <tt>', $arSqlTables). '</tt>';
		}
		$is_continue = 0;
		if ($int_sum_kb)
		{
			$is_continue = 1;
		}
		$this->ar_status[] = gw_next_step($is_continue, 'step=2&a='.$this->gv['a'].'&'.GW_LANG_I.'='.$this->gv[GW_LANG_I]);
	}
	/* */
	function uninstall_step_2()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', $this->oL->m('1166') );
		$this->oTpl->a( 'v:html_descr', '' );

		$arSqlTables = array();
		$arSqlTablesRemove = array();
		$int_sum_kb = 0;
		for (reset($this->arTables); list($k, $v) = each($this->arTables);)
		{
			if ($arTableInfo = $this->oDb->table_info($this->sys['tbl_prefix'].$v) )
			{
				$int_sum_kb += ($arTableInfo['Data_length']+$arTableInfo['Index_length']);
				$arSqlTablesRemove[] = $this->sys['tbl_prefix'].$v;
			}
		}
		$sql = sprintf('SELECT tablename FROM `%s`', $this->sys['tbl_prefix'].'dict');
		$arSql = $this->oDb->sqlExec($sql);
		while (list($k, $arV) = each($arSql))
		{
			if ($arTableInfo = $this->oDb->table_info($arV['tablename']) )
			{
				$int_sum_kb += ($arTableInfo['Data_length']+$arTableInfo['Index_length']);
				$arSqlTablesRemove[] = $arV['tablename'];
			}
		}
		#$arSqlTablesRemove = array();
		if (empty($arSqlTablesRemove))
		{
			$this->ar_status[] = sprintf($this->oL->m('1235'), $this->sys['server_dir']);
		}
		else
		{
			$sql = 'DROP TABLE `'.implode('`, `', $arSqlTablesRemove).'`';
			if ($this->sys['is_debug'])
			{
				$this->ar_status[] = $sql;
			}
			else if ($this->oDb->sqlExec($sql))
			{
				$this->ar_status[] = sprintf($this->oL->m('1235'), $this->sys['server_dir']);
			}
		}
		$this->ar_status[] = sprintf('<span class="red"><b>%s</b></span>', $this->oL->m('1236'));
	}
	/* */
	function get_html_steps_progress($step)
	{
		$ar = array();
		for ($i = 1; $i <= 2; $i++)
		{
			$ar[$i] = ' '. $this->oL->m('1168') . ' ' . $i.'  ';
			if ($step == $i) { $ar[$i] = '<b class="green">'.$ar[$i].'</b>'; }
		}
		return '<span class="gray">'.implode('&#x2192;', $ar).'</span>';
	}
}
?>