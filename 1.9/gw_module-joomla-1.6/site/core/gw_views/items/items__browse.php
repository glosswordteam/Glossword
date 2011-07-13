<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_WEB_INSIDE );




/* "My Items" or "All Items" */
$this->gv['area']['in'] = isset( $this->gv['area']['in'] ) ? $this->gv['area']['in'] : 'all';


/* Records per page */
if ( !isset( $this->gv['area']['per'] ) )
{
	$this->gv['area']['per'] = $this->V->items_per_page;
	/* Get value from users settings */
	if ( $this->oSess->user_get('area_per') )
	{
		$this->gv['area']['per'] = $this->oSess->user_get('area_per');
	}
}
if ( $this->gv['area']['per'] > 100 )
{
	$this->gv['area']['per'] = 100;
}
else if ( $this->gv['area']['per'] <= 3 )
{
	$this->gv['area']['per'] = 3;
}

/* Save "Records per page" to a user settings */
if ( $this->oSess->id_user != $this->oSess->id_guest 
	&& $this->oSess->user_get( 'area_per' ) != $this->gv['area']['per'] ) 
{
	$this->oSess->user_set( 'area_per', $this->gv['area']['per'] );
}

/* Default values for is_active */
if ( !isset( $this->gv['is_active'] ) )
{
	$this->gv['is_active'] = -1;
}

/* Default sorting order */
if ( !isset( $this->gv['area']['sort'] ) )
{
	$this->gv['area']['sort'] = ($this->oSess->user_get( 'sort_items' ) != '') ? $this->oSess->user_get( 'sort_items' ) : 1;
}


/**
 * ----------------------------------------------
 * Count the number of records 
 * ----------------------------------------------
 */
$cache_key = 'cnt-' . 
	( isset( $this->gv['area']['a1'] ) ? $this->gv['area']['a1'] : '' ) .'-'.
	( isset( $this->gv['area']['a2'] ) ? $this->gv['area']['a2'] : '' ) .'-'.
	( isset( $this->gv['area']['lc'] ) ? $this->gv['area']['lc'] : '' ) .'-'.
	$this->V->id_field_root . SITE_ADMIN_MODE . SITE_WEB_MODE . $this->gv['il'];
if ( $this->V->is_cache_items_browse && $this->oCache->in_cache( $cache_key, 'items-az', $this->V->time_cache_az ) )
{
	$cnt_records = $this->oCache->load();
}
else
{
	$this->oDb->select( 'count(*) AS cnt' );
	$this->oDb->from( array( 'items_tmp it' ) );
	/* Specify letter */
	if ( isset( $this->gv['area']['a1'] ) )
	{
		$this->oDb->where( array( 'it.contents_a' => $this->oFunc->str_binary( $this->gv['area']['a1'], 8 ) ) );
		/* Specify second letter */
		if ( isset( $this->gv['area']['a2'] ) )
		{
		$this->oDb->where( array( 'it.contents_b' => $this->oFunc->str_binary( $this->gv['area']['a2'], 8 ) ) );
		}
	}
	/* Specify locale code */
	if ( isset( $this->gv['area']['lc'] ) && isset( $this->ar_languages_locale[$this->gv['area']['lc']] ) )
	{
		$this->oDb->where( array( 'it.id_lang_c' => $this->ar_languages_locale[$this->gv['area']['lc']] ) );
	}
	$this->oDb->where( array( 'it.is_active' => '1' ) );
	$ar_sql = $this->oDb->get()->result_array();
	$cnt_records = isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0;

	/* Cached Units: Save */
	if ( $this->V->is_cache_items_browse )
	{
		$cnt_records = $this->oCache->save( $cnt_records );
	}
}

/* Limit the total number of items */
$cnt_displayed = 0;
/* $cnt_total is visual only */
$cnt_total = $this->oTkit->number_format( $cnt_records );
if ( $cnt_records > $this->V->int_search_max )
{
	$cnt_records = $this->V->int_search_max;
	$cnt_displayed = $this->oTkit->number_format( $cnt_records );
}

/* Count the number of pages */
$int_pages = @ceil( $cnt_records / $this->gv['area']['per'] );
$int_pages = (!$int_pages ? 1 : $int_pages);
/* */
$offset = $this->gv['area']['per'] * ($this->gv['page'] - 1);
$offset = ($offset > $cnt_records) ? $this->gv['area']['per'] * ($int_pages-1) : $offset;

/**
 * ----------------------------------------------
 * Pagination
 * ----------------------------------------------
 */
if ( $int_pages > 1 )
{
	$oHref = $this->oHtml->oHref();
	$oHref->set( 'per', $this->gv['area']['per'] );
	$oHref->set( 'page', '{#}' );
	$oHref->set( 'a1', isset( $this->gv['area']['a1'] ) ? $this->gv['area']['a1'] : '' );
	$oHref->set( 'a2', isset( $this->gv['area']['a2'] ) ? $this->gv['area']['a2'] : '' );
	$oHref->set( 'tag', !empty( $this->gv['area']['tag'] ) ? implode( ':', $this->gv['area']['tag'] ) : '' );

	$ar_cfg = array(
		'url' => $this->oHtml->url_normalize( $this->V->file_index.'?#area='. $oHref->get() ),
		'page_current' => $this->gv['page'],
		'items_total' => $cnt_records,
		'items_per_page' => $this->gv['area']['per'],
		'links_total' => $this->V->paginator_links_total,
		'links_more' => $this->V->paginator_links_more,
		'links_separator' => $this->V->paginator_links_separator,
		'current_tag' => 'strong',
		'phrase_next' => '<span style="font-size:75%">'.$this->oTkit->_( 1034 ).' &#8594;</span>',
		'phrase_prev' => '<span style="font-size:75%">&#8592; '.$this->oTkit->_( 1035 ).'</span>',
	);
	$oPaginatior = $this->_init_paginator( $ar_cfg );
	$oPaginatior->oTkit =& $this->oTkit;
}

/**
 * ----------------------------------------------
 * Select Alphabetic order
 * ----------------------------------------------
 */
$ar_az = $ar_aazz = array();
if ( $this->V->is_show_az )
{
	switch ( $this->V->az_location )
	{
		case 't': $this->oOutput->append_css_collection( 'az-top' ); break;
		case 'l': $this->oOutput->append_css_collection( 'az-left' ); break;
		case 'r': $this->oOutput->append_css_collection( 'az-right' ); break;
	}
	list( $ar_az, $ar_aazz ) = $this->items__get_az();
}


$cache_key .= '-'.$this->gv['area']['per'].'-'.$this->gv['page'].'-'.$this->V->link_mode;
/* Create `$ar_items` on any settings for `is_cache_html` */
if ( $this->V->is_cache_items_browse && $this->oCache->in_cache( $cache_key, 'items-browse', $this->V->time_cache_az ) )
{
	$ar_items = $this->oCache->load();
}
else
{
	/**
	 * ----------------------------------------------
	 * Select Item IDs
	 * ----------------------------------------------
	 */
	/* 2.46047 SELECT FROM items i, contents c */
	/* 0.05199 SELECT FROM items_tmp it */

	$this->oDb->select( 'it.id_item' );
	$this->oDb->from( array( 'items_tmp it' ) );

	/* Specify letter */
	if ( isset( $this->gv['area']['a1'] ) )
	{
		$this->oDb->where( array( 'it.contents_a' => $this->oFunc->str_binary( $this->gv['area']['a1'], 8 ) ) );
	}

	/* Specify locale code */
	if ( isset( $this->gv['area']['lc'] ) && isset( $this->ar_languages_locale[$this->gv['area']['lc']] ) )
	{
		$this->oDb->where( array( 'it.id_lang_c' => $this->ar_languages_locale[$this->gv['area']['lc']] ) );
	}

	$this->oDb->order_by( 'it.id_lang_c' );

	/* 1.9.3: Custom alphabetic order */
	for ( $i = 1; $i <= 8; $i++ )
	{
		$this->oDb->order_by( 'it.int_sort_'.$i.', it.contents_so' );
	}

	/* 31 Jan 2010: Correct last page offset when the limit of `int_search_max` has reached */
	$local_per = $this->gv['area']['per'];
	if ( $this->gv['area']['per'] * $this->gv['page'] > $this->V->int_search_max ) 
	{
		$local_per = $this->gv['area']['per'] - ( ( $this->gv['area']['per'] * $this->gv['page'] ) - $this->V->int_search_max );
		/* Incorrect `per` manually entered */
		if ( $local_per <= 0 )
		{
			$local_per = $this->gv['area']['per'];
		}
	}
	$this->oDb->limit( $local_per, $offset );
	$ar_sql = $this->oDb->get()->result_array();


	/* Collect Item IDs */
	$ar_item_ids = array();
	foreach ( $ar_sql as $ar_v )
	{
		$ar_item_ids[] = $ar_v['id_item'];
	}

	/**
	 * ----------------------------------------------
	 * Select Items
	 * ----------------------------------------------
	 */
	$ar_sql_items = array();
	if ( !empty($ar_item_ids) )
	{
		$this->oDb->select( 'i.id_item, i.item_id_user_created, i.is_complete, uri.item_uri, c.contents_value_cached, c.contents_a, c.id_field, c.id_contents' );
		$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf' ) );
		$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
		$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
		$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
		$this->oDb->where( array( 'mftf.id_fieldset' => '1' ) );
		
		$this->oDb->where_in( 'i.id_item', $ar_item_ids );
		foreach ($ar_item_ids as $id_item_in)
		{
			$this->oDb->order_by( 'i.id_item = "'.$id_item_in.'" DESC' );
		}
		$this->oDb->order_by( 'mftf.int_sort ASC' );
		$ar_sql_items = $this->oDb->get()->result_array();
	}
	/* Re-arrange */
	$ar_items = array();
	foreach ( $ar_sql_items as $ar_v)
	{
		$ar_v['contents_a'] = str_replace( "\0", '', $ar_v['contents_a'] );
		$ar_items[$ar_v['id_item']][$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;
	}
	/* Cached Units: Save */
	if ( $this->V->is_cache_items_browse )
	{
		$this->oCache->save( $ar_items );
	}
}
/* */
$cache_key .= '-html-' . (string) $this->V->int_max_chars_preview;
if ( $this->V->is_cache_items_browse && $this->V->is_cache_html
	&& $this->oCache->in_cache( $cache_key, 'items-browse-html', $this->V->time_cache_html ) )
{
	$this->oOutput->append_html( $this->oCache->load() );
}
else
{
	/* */
	$oBlock->oTpl = $this->_init_html_tpl();
	$oBlock->oTpl->set_tpl( 'items.browse' );

	/* Definition preview */
	$this->oTarget->parse__is_preview = ( $this->V->int_max_chars_preview == '-1' ) ? 0 : 1;

	$cnt = 1;
	foreach ( $ar_items as $id_item => $ar_fields_content )
	{
	#	prn_r( $ar_fields_content );

		list( $ar_item_settings, $str_item, $str_item_cut, $str_item_url, $str_descr, $str_descr_cut ) = $this->oTarget->parse_ar_item( $ar_fields_content );

		/* Heading letters */
		$contents_a = $ar_item_settings['contents_a'];
		$str_letter_a[$cnt] = $contents_a;
		if ( isset( $str_letter_a[$cnt-1] ) && $str_letter_a[$cnt-1] == $contents_a )
		{
			$contents_a = '';
		}
		$id_checkbox = 'item-'.$id_item;
		$oBlock->oTpl->assign( 'items.list.letter_a', ( $contents_a != '' ? '<h3>'. $ar_item_settings['contents_a'] .'</h3>' : '' ) );
		$oBlock->oTpl->assign( 'items.list.cnt', $cnt );
		$oBlock->oTpl->assign( 'items.list.item_title', $str_item_url );
		$oBlock->oTpl->assign( 'items.list.item_descr', $str_descr );
		
		$oBlock->oTpl->parse_block('items.list');
		++$cnt;
	}
	$oBlock->oTpl->assign( 'v:th_n', '№' );
	$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_( 1023 ) );
	$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) );
	$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) );
	$oBlock->oTpl->assign( 'v:az', implode( ' ', $ar_az ) );
	$oBlock->oTpl->assign( 'v:cnt_records', $cnt_total );

	if ( $int_pages > 1 )
	{
		$oBlock->oTpl->assign( 'l:1038', $this->oTkit->_( 1038, '<strong>'.$this->oTkit->number_format( $this->gv['page'] ).'</strong>', $this->oTkit->number_format( $int_pages ) ) );
	}
	if ( isset( $oPaginatior ) ) 
	{
		$oBlock->oTpl->assign( 'v:pagination', $oPaginatior->get() );
		$oBlock->oTpl->parse_block('if.paginator_top');
		$oBlock->oTpl->parse_block('if.paginator_bottom');
	}
	if ( $cnt_displayed )
	{
		$oBlock->oTpl->assign( 'v:cnt_displayed', $cnt_displayed );
		$oBlock->oTpl->assign( 'l:1037', $this->oTkit->_( 1037 ) );
		$oBlock->oTpl->parse_block('if.cnt_displayed');
	}
	
	$this->oOutput->append_html( $oBlock->oTpl->get_html() );
	/* Cached Units: Save */
	if ( $this->V->is_cache_items_browse && $this->V->is_cache_html )
	{
		$this->oCache->save( $oBlock->oTpl->get_html() );
	}
}

/**
 * ----------------------------------------------
 * Prepare page <title> and breadcrumbs
 * ----------------------------------------------
 */
$this->oTpl->assign_global( 'v:h1', $this->V->meta_title );
if ( isset( $this->gv['area']['a1'] ) )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1032, $this->gv['area']['a1'] ) );
	$this->oTpl->assign_global( 'v:h1', $this->oTkit->_( 1032, $this->gv['area']['a1'] ) );
	
	$this->oOutput->append_bc( $this->oTkit->_( 1032, $this->gv['area']['a1'] ), '', '0' );
}
/* Add page number to title */
if ( $int_pages > 1 && $this->gv['page'] > 1 )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1038, $this->gv['page'], $int_pages ) );
}


/**
 * ----------------------------------------------
 * Infoblocks
 * ----------------------------------------------
 */
$this->oDb->select( 'b.block_contents, b.block_place, b.block_type' );
$this->oDb->from( array( 'blocks b' ) );
$this->oDb->where( array( 'is_active' => '1' ) );
$this->oDb->order_by( 'b.block_name ASC' );
$this->oDb->limit( 100 );
$ar_sql = $this->oDb->get()->result_array();
$ar_blocks_top = $ar_blocks_bottom = array();
foreach ( $ar_sql as $ar_v)
{
	if ( $ar_v['block_type'] == 2 )
	{
		/* PHP */
		ob_start();
		@eval( $ar_v['block_contents'] );
		$ar_v['block_contents'] = ob_get_contents();
		ob_end_clean();
	}
	if ( $ar_v['block_place'] == 1 )
	{
		/* Top */
		$ar_blocks_top[] = $ar_v['block_contents'];
	}
	else if ( $ar_v['block_place'] == 2 )
	{
		/* Buttom */
		$ar_blocks_bottom[] = $ar_v['block_contents'];
	}
}

$this->oTpl->assign( 'v:blocks_top', implode(' ', $ar_blocks_top ) );
$this->oTpl->assign( 'v:blocks_bottom', implode(' ', $ar_blocks_bottom ) );

?>