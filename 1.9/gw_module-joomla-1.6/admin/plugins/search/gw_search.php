<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	Glossword Search
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onSearch', 'plgSearchGlossword' );
$mainframe->registerEvent( 'onSearchAreas', 'plgSearchGlosswordAreas' );

JPlugin::loadLanguage( 'plg_search_glossword' );

define( 'GW_AREAOFSEARCH', JText::_( 'AREAOFSEARCH' ) );

/**
 * @return array An array of search areas
 */
function &plgSearchGlosswordAreas()
{
	static $areas = array( 
		'gw_search' => GW_AREAOFSEARCH
	);
	return $areas;
}


/* */
function search_index__filter_si( &$o, $s )
{
	#$s = $this->rm_specials( $s );
	$s = search_index__str_normalize( $o, $s );
	$s = search_index__text_si( $o, trim( $s ) );
	return $s;
}
function search_index__text_si( &$o, $s )
{
	/* Do lowercase */
#	$s = $o->oCase->lc( $s );
	
	/* Mask non-ASCII characters */
	$s = preg_replace( "/([\\xc0-\\xff][\\x80-\\xbf]*)/e", "'U8' . bin2hex( \"$1\" )", $s );
	
	/* Mask MySQL stopwords */
	$s = search_index__mask_stopwords( $s );
	
	return $s;
}	
/* */
function search_index__str_normalize( &$o, $s  )
{
	$jo_db =& JFactory::getDBO();
	
	/* Do lowercase */
	$s = $o->oCase->lc( $s );

	/* Use PECL extension */
	if ( class_exists( 'Normalizer' ) )
	{
		return Normalizer::normalize( $s, Normalizer::FORM_C );
	}
	/* */
	preg_match_all( "/./u", $s, $ar );
	$ar = $ar[0];
	$ar_c_crc = array();
	/* For each character */
	foreach ($ar AS $k => &$v )
	{
		/* Use values as key */
		/* PHP-bug: sometimes a string keys becomes interger */
		$ar_c_crc[$v] = sprintf( "%u", crc32( $v ) );
	}

	unset( $v );
	if ( empty( $ar_c_crc ) ){ return $s; }

	/* */
	$query = 'SELECT `str_from`, `str_to`'
		. ' FROM '.$o->V->db_name.'.'.$o->V->table_prefix.'unicode_normalization '
		. ' WHERE `crc32u` IN ('. implode(',', array_values( $ar_c_crc ) ).')';
	$jo_db->setQuery( $query );
	$ar_sql = $jo_db->loadAssocList();
	if ( is_null( $ar_sql ) ){ $ar_sql = array(); }

	/* Normalize text */
	foreach ($ar_sql AS $k => &$v )
	{
		$s = str_replace( urldecode( $v['str_from'] ), urldecode( $v['str_to'] ), $s );
		unset( $ar_sql[$k] );
	}
	unset( $v );

	return $s;
}
function search_index__mask_stopwords( $s )
{
	return preg_replace( '/\b([a-z]+)/', '_\\1', $s );
}
function search_index__get_crc_u( $s )
{
	return sprintf( "%u", crc32( $s ) );
}


/* replacement for urlencode */
function ohtml_urlencode( $s )
{
	/* Encode special characters first */
	$s = str_replace( array( ',', '/', '+' ), array( '%2C', '%2F', '%2B' ), $s );
	$s = urlencode( $s );
	/* Restore separators for #area */
	$s = str_replace( array( '%01%01', '%02%02' ), array( ',', '.' ), $s );
	return $s;
}


/* */
function &plgSearchGlossword( $q, $phrase='', $ordering='', $areas=null )
{
	global $mainframe;

	$jo_db =& JFactory::getDBO();
	#$jo_user =& JFactory::getUser();
	#$jo_cfg =& JFactory::getConfig();
	$jo_component = &JComponentHelper::getComponent('com_glossword');
	$jo_menu = &JSite::getMenu();
	$jo_items = $jo_menu->getItems('componentid', $jo_component->id, true);

	#require_once( JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php' );
	
	if ( is_array( $areas ) ) 
	{
		if ( !array_intersect( $areas, array_keys( plgSearchGlosswordAreas() ) ) ) 
		{
			return array();
		}
	}
	
	/* Load plugin parameters */
	$jo_plugin =& JPluginHelper::getPlugin( 'search', 'gw_search' );
	$pluginParams = new JParameter( $jo_plugin->params );
	
	$offset = JRequest::getVar( 'start', 0, '', 'int' );
	$per_page = JRequest::getVar( 'limit', $pluginParams->get( 'int_per_page' ), '', 'int' );
	
	/* Load component configuration */
	$query = 'SELECT * FROM #__gw_config';
	$jo_db->setQuery( $query );
	$ar_sql = $jo_db->loadAssocList();
	if ( is_null( $ar_sql ) ){ $ar_sql = array(); }
	
	$ar_cfg = array();
	foreach ( $ar_sql as $k => $v )
	{
		$ar_cfg[$v['setting_key']] = $v['setting_value'];
	}
	
	require_once( $ar_cfg['path_core_abs'].'/gw_config.php' );
	
	/* */
	$_SERVER['REQUEST_TIME'] = isset( $_SERVER['REQUEST_TIME'] ) ? $_SERVER['REQUEST_TIME'] : mktime();
	$o = crc32( $_SERVER['REQUEST_TIME'] );
	
	${$o} = new site_db_config();
	
	${$o}->a( 'path_includes', $ar_cfg['path_core_abs'].'/'.${$o}->V->path_includes );

	foreach ( $ar_cfg as $setting_key => $setting_value )
	{
		${$o}->a( $setting_key, $setting_value );
	}
	
	/* */
	include_once( ${$o}->V->path_includes.'/class.case.php' );
	${$o}->oCase = new gwv_casemap;
	${$o}->oCase->is_use_mbstring = 1;

	$q = preg_replace( "/ {2,}/", ' ', $q );
	$q = trim( $q );
	
	$q_si = search_index__filter_si( ${$o}, $q );

	/* Need to test. Could not be equal. */
	$ar_words_sql = explode( ' ', $q_si );
	$ar_words_q = explode( ' ', $q );
	
	/* 11 Apr 2008: Enable search with asterisk for Chinese, Japanese and Korean characters */
	foreach ( $ar_words_q as $k => $word )
	{
		if ( preg_match( '/[\x{3040}-\x{312F}|\x{3400}-\x{9FFF}|\x{AC00}-\x{D7AF}]/u', $word, $ar_matches ) )
		{
			$ar_words_sql[$k] .= '*';
		}
	}
	
	/* Switch search modes */
	switch ( $phrase )
	{
		case 'any':
			$sql_against = implode( ' ', $ar_words_sql );
		break;
		case 'exact':
			$sql_against = '"'.implode( ' ', $ar_words_sql ).'"';
		break;
		default:
			$sql_against = '+'.implode( ' +', $ar_words_sql );
		break;
	}
	
	/**
	 * ----------------------------------------------
	 * Count Item IDs
	 * ----------------------------------------------
	 */
	$query = 'SELECT csi.id_item ';
	#$query .= "\n".', MATCH(csi.contents_si) AGAINST(\''. $jo_db->getEscaped( $sql_against ).'\' IN BOOLEAN MODE) score ';
	$query .= "\n".' FROM '.${$o}->V->db_name.'.'.${$o}->V->table_prefix.'contents_si csi';
	$query .= "\n".' WHERE ';
	$query .= "\n".' MATCH(csi.contents_si) AGAINST(\''.$jo_db->getEscaped( $sql_against ).'\' IN BOOLEAN MODE)';
	$query .= "\n".' GROUP BY csi.id_item ';

	$jo_db->setQuery( $query, 0, $pluginParams->get( 'int_search_max' ) );
	$jo_db->query();
	$cnt_records = $jo_db->getNumRows();
	
	/**
	 * ----------------------------------------------
	 * Select Item IDs
	 * ----------------------------------------------
	 */
	$jo_db->setQuery( 'SET SQL_BIG_SELECTS=1' );
	$jo_db->query();
	
	$query = 'SELECT csi.id_item';

	/* 1.9.3: Custom alphabetic order */
	$ar_join = array();
	for ( $i = 1; $i <= 8; $i++ )
	{
		$query .= ', az'.$i.'.int_sort';
		$ar_join[$i] = "\n".'LEFT JOIN '.${$o}->V->db_name.'.'.${$o}->V->table_prefix.'az_letters az'.$i.' ON ';
		$ar_join[$i] .= 'az'.$i.'.uc_crc32u = c.contents_'.$i.' AND c.id_lang = az'.$i.'.id_lang';
	}
	$query .= "\n".' FROM '.${$o}->V->db_name.'.'.${$o}->V->table_prefix.'contents_si csi, ';
	$query .= ${$o}->V->db_name.'.'.${$o}->V->table_prefix.'items i, '.${$o}->V->db_name.'.'.${$o}->V->table_prefix.'contents c';
	$query .= implode( ' ', $ar_join );

	$query .= "\n".' WHERE ';
	$query .= "\n".' MATCH(csi.contents_si) AGAINST(\''.$jo_db->getEscaped( $sql_against ).'\' IN BOOLEAN MODE)';
	$query .= ' AND i.id_item = c.id_item ';
	$query .= ' AND i.id_item = csi.id_item ';
	$query .= "\n".' GROUP BY csi.id_item ';
	
	$ar_order = array( 'c.id_lang' );

	/* Switch sorting modes */
	switch ( $ordering )
	{
		case 'newest':
			$ar_order[] = 'i.item_cdate DESC';
		break;
		case 'oldest': 
			$ar_order[] = 'i.item_cdate ASC';
		break;
		case 'popular': 
			$ar_order[] = 'i.cnt_hits DESC';
		break;
		default: 
		break;
	}

	/* 1.9.3: Custom alphabetic order */
	for ( $i = 1; $i <= 8; $i++ )
	{
		$ar_order[] = 'az'.$i.'.int_sort, c.contents_so';
	}

	$query .= "\n".'ORDER BY '.implode( ', ', $ar_order );
	
	#$query .= ' HAVING score > 0 ';
	#$query .= ' ORDER BY score DESC ';
	
	/* Can't use pagination for requests because of Joomla */
	/* Using `int_search_max` instead */
	/* @todo: workaround */
	#$jo_db->setQuery( $query, $offset, $per_page );
	$jo_db->setQuery( $query, 0, $pluginParams->get( 'int_search_max' ) );
	$ar_sql = $jo_db->loadAssocList();
	if ( is_null( $ar_sql ) ){ $ar_sql = array(); }

	$ar_item_ids = array();
	foreach ( $ar_sql as $ar_v )
	{
		$ar_item_ids[] = $ar_v['id_item'];
	}
	
	if ( empty( $ar_item_ids ) )
	{
		return array();
	}
	/**
	 * ----------------------------------------------
	 * Select Items
	 * ----------------------------------------------
	 */
	$ar_sql_items = array();
	if ( !empty( $ar_item_ids ) )
	{
		$query = 'SELECT uri.item_uri, i.id_item, i.item_id_user_created, i.item_cdate, c.contents_value_cached, c.id_field ';
		$query .= "\n".' FROM '.${$o}->V->db_name.'.'.${$o}->V->table_prefix.'items i, ';
		$query .= ${$o}->V->db_name.'.'.${$o}->V->table_prefix.'contents c, ';
		$query .= ${$o}->V->db_name.'.'.${$o}->V->table_prefix.'items_uri uri, ';
		$query .= ${$o}->V->db_name.'.'.${$o}->V->table_prefix.'map_field_to_fieldset mftf ';
		$query .= "\n".' WHERE i.id_item = c.id_item ';
		$query .= ' AND i.id_item = uri.id_item ';
		$query .= ' AND mftf.id_field = c.id_field ';
		$query .= ' AND mftf.id_fieldset = \'1\' ';
		$query .= ' AND i.id_item IN ('. implode( ', ', $ar_item_ids ).') ';
		$ar_order_by = array();
		foreach ( $ar_item_ids as $id_item_in )
		{
			$ar_order_by[] = 'i.id_item = "'.$id_item_in.'" DESC';
		}
		$ar_order_by[] = 'mftf.int_sort ASC';
		$query .= "\n".' ORDER BY '. implode( ', ', $ar_order_by );
		
		$jo_db->setQuery( $query );
		$ar_sql_items = $jo_db->loadAssocList();
		if ( is_null( $ar_sql_items ) ){ $ar_sql_items = array(); }
	}
	/* Re-arrange */
	$ar_items = array();
	foreach ( $ar_sql_items as $k => $ar_v)
	{
		$ar_items[$ar_v['id_item']][$ar_v['id_field']] = $ar_v;
		unset( $ar_sql_items[$k] );
	}

	/* */
	$cnt = 0;
	$oResults[0] = (object) 'results';
	foreach ( $ar_items as $id_item => $ar_fields_content)
	{
		$ar_str_item_title = array();
		$ar_str_item_descr = array();
		foreach ( $ar_fields_content as $id_field => $ar_v)
		{
			switch ( $id_field )
			{
				case 1:
					$ar_str_item_title[] = $ar_v['contents_value_cached'];
				break;
				/* More to come */
				default:
					$ar_str_item_descr[] = $ar_v['contents_value_cached'];
				break;
			}
		}
		/* Item title */
		$str_item = implode( ' ', $ar_str_item_title );
		
		/* Hyperlink to item */
		$href_area = 'a.search,q.'. ohtml_urlencode( $str_item ). ',t.items';
		$href_area = urlencode( $href_area );
		
		$href = JRoute::_( 'index.php?option='.$jo_component->option.'&Itemid='.$jo_items->id.'&arg[area]='.$href_area.'&view=default' );

		/* Item description */
		$str_descr = strip_tags( implode( ' ', $ar_str_item_descr ) );

		/* */
		$oResults[$cnt]->browsernav = 0;
		$oResults[$cnt]->section = GW_AREAOFSEARCH;
		
		$oResults[$cnt]->href = $href;
		$oResults[$cnt]->text = $str_descr;
		$oResults[$cnt]->title = strip_tags( $str_item );
		
		#$oResults[$cnt]->title .= ' [href='.htmlspecialchars( $oResults[$cnt]->href ).']';
		#show_date
		$oResults[$cnt]->created = $ar_v['item_cdate'];
		++$cnt;
	}
#print '<pre>'.__FILE__.' '.__LINE__.'<br />';
#print_r( $oResults );
#print '</pre>';
#print '<div>$cnt_records='.$cnt_records.'</div>';
#print '<div>$per_page='.$per_page.'</div>';

	return $oResults;
}


?>