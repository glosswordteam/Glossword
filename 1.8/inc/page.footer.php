<?php

$gw_this['str_debug_stats'] = '';
if (defined("GW_DEBUG_SQL_TIME") && GW_DEBUG_SQL_TIME == 1)
{
    $cntCache = isset($oCh) ? $oCh->cnt_queries_debug : 0;
    $totaltime = $oTimer->end();

    $sql_time = isset($oDb) ? $oDb->query_time + $oDb->connect_time : 0;
    $sql_time = isset($sess->that) && isset($sess->that->db) ? ($sql_time + $sess->that->db->query_time) : $sql_time;
    $sql_time = isset($user->db) ? ($sql_time + $user->db->query_time) : $sql_time;
    $sql_time = isset($objDict->db) ? ($sql_time + $objDict->db->query_time) : $sql_time;

    $php_time = $totaltime - $sql_time;
    $totaltime = $totaltime + $tmp['time_php_init'];
    $query_count = isset($oDb) ? $oDb->cnt_queries_debug : 0;
    $query_count = isset($sess->that) && isset($sess->that->db) ? ($query_count + $sess->that->db->cnt_queries_debug) : $query_count;
    $query_count = isset($user->db) ? ($query_count + $user->db->cnt_queries_debug ) : $query_count;
    if (isset($ch)) { $cntCache = $ch->cnt_queries_debug; }

    $gw_this['str_debug_stats'] .= '<div style="text-align:left;padding:4px;background:'.$ar_theme['color_3'].'">';
    $gw_this['str_debug_stats'] .= sprintf('<span style="float:right">Prepend=&#160;<b>%1.5f</b> PHP=&#160;<b>%1.5f</b> SQL=&#160;<b>%1.5f</b> Total=&#160;<b>%1.5f</b></span>', $tmp['time_php_init'], $php_time, $sql_time, $totaltime);
    $gw_this['str_debug_stats'] .= sprintf('Queries=&#160;<b>%d</b> &#160;', $query_count);
    $gw_this['str_debug_stats'] .= sprintf('Cached Q.=&#160;<b>%s</b> &#160;', ($sys['is_cache_sql'] ? $cntCache : 'OFF'));
    $gw_this['str_debug_stats'] .= sprintf('Cache HTTP=&#160;<b>%s</b> &#160;', ($sys['is_cache_http'] ? 'ON' : 'OFF'));
    $gw_this['str_debug_stats'] .= '</div>';
}
if (defined("GW_DEBUG_SQL_QUERY") && GW_DEBUG_SQL_QUERY == 1)
{
	if (isset($oDb) && $query_count == 0){ $query_count = $oDb->cnt_queries_debug; }
	$gw_this['str_debug_stats'] .= '<div style="text-align:left;padding:5px;">';
	$gw_this['str_debug_stats'] .= '<div>Number of queries: <b>'. ($query_count) . '</b></div>';

	if (isset($oDb))
	{
		 $gw_this['str_debug_stats'] .= '<ul title="general database" style="padding:0;margin:0 3em"><li>'.
										sprintf('<b>%1.5f</b> Connect', $oDb->connect_time). '</li><li>'.
										implode('</li><li>', $oDb->query_array) . '</li></ul>';
	}
	if (isset($sess->that) && isset($sess->that->db))
	{
		$gw_this['str_debug_stats'] .= '<ul title="sessions" style="padding:0;margin:0 3em"><li>' . implode('</li><li>', $sess->that->db->query_array) . '</li></ul>';
	}
	if (isset($user->db))
	{
	    $gw_this['str_debug_stats'] .= '<ul title="users" style="padding:0;margin:0 3em"><li>' . implode("</li><li>", $user->db->query_array) . "</li></ul>";
	}
	if (isset($objDict->db))
	{
		$gw_this['str_debug_stats'] .= '<ul title="objDict" style="padding:0;margin:0 3em"><li>' . implode("</li><li>", $objDict->db->query_array) . "</li></ul>";
	}
	$gw_this['str_debug_stats'] .= '</div>';
}
if (defined("GW_DEBUG_CACHE") && GW_DEBUG_CACHE == 1)
{
	if (isset($oCh))
	{
		$gw_this['str_debug_stats'] .= '<div style="text-align:left;padding:5px;">';
		$gw_this['str_debug_stats'] .= '<div>File cache usage: <b>'. sizeof($oCh->query_array). '</b></div>';
		$gw_this['str_debug_stats'] .= '<ul style="padding:0;margin:0 3em"><li>' . implode('</li><li>', $oCh->query_array) . '</li></ul>';
		$gw_this['str_debug_stats'] .= '</div>';
	}
}
if (defined("GW_DEBUG_HTTP") && GW_DEBUG_HTTP == 1)
{
	$gw_this['str_debug_stats'] .= '<div style="text-align:left;padding:5px;">';
	$gw_this['str_debug_stats'] .= '<div>HTTP-headers: <b>'. sizeof($oHdr->get()). '</b> (except for Content-encoding)</div>';
	$gw_this['str_debug_stats'] .= '<ul title="http headers" style="padding:0;margin:0 3em"><li>'.
									implode('</li><li>', $oHdr->get()) . '</li></ul>';
	$gw_this['str_debug_stats'] .= '</div>';
}

$oTpl->addVal( 'v:debug',
				'<div style="font: 70% tahoma,sans-serif; width:100%; max-height: 30em; margin:0; padding:0; overflow: auto; background:'.$ar_theme['color_2'].'">'.
				$gw_this['str_debug_stats'].
				'<br /></div>'
			);

/* */
?>