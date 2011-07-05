<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-items' );


/* "My Items" or "All Items" */
$this->gv['area']['in'] = isset( $this->gv['area']['in'] ) ? $this->gv['area']['in'] : 'all';

/* Search query. "q = *" by default */
if ( !isset( $this->gv['q'] ) ){ $this->gv['q'] = '*'; }
if ( $this->gv['q'] == '' ){ $this->gv['q'] = '*'; }

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
if ( !$this->oSess->is_guest() && $this->oSess->user_get('area_per') != $this->gv['area']['per'] ) 
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
	$this->gv['area']['sort'] = $this->oSess->user_get( 'sort_items' ) ? $this->oSess->user_get( 'sort_items' ) : 1;
}


/**
 * ----------------------------------------------
 * Count the number of records 
 * ----------------------------------------------
 */
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
if ($int_pages > 1)
{
	$oHref = $this->oHtmlAdm->oHref();
	$oHref->set( 't', $this->gv['area']['t'] );
	$oHref->set( 'a', $this->gv['area']['a'] );
	$oHref->set( 'per', $this->gv['area']['per'] );
	$oHref->set( 'sort', $this->gv['area']['sort'] );
	$oHref->set( 'page', '{#}' );
	$oHref->set( 'a1', isset( $this->gv['area']['a1'] ) ? $this->gv['area']['a1'] : '' );
	$oHref->set( 'a2', isset( $this->gv['area']['a2'] ) ? $this->gv['area']['a2'] : '' );
	$oHref->set( 'tag', !empty( $this->gv['area']['tag'] ) ? implode( ':', $this->gv['area']['tag'] ) : '' );

	$ar_cfg = array(
		'url' => $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='. $oHref->get() ),
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

/* @todo: Select items for moderation */


/**
 * ----------------------------------------------
 * Select Alphabetic order
 * ----------------------------------------------
 */
list( $ar_az, $ar_aazz ) = $this->items__get_az();


/**
 * ----------------------------------------------
 * Select Item IDs
 * ----------------------------------------------
 */
/*
by alphabet: 
	SELECT it.id_item FROM (`jos_gw_items_tmp` it) 
	WHERE it.contents_a = 'A'
	ORDER BY it.id_lang, it.int_sort_1, it.contents_so

by modification date:
 	SELECT it.id_item FROM (`jos_gw_items_tmp` it) 
	ORDER BY it.item_mdate DESC, it.int_sort_1, it.contents_so

by status:
 	SELECT it.id_item FROM (`jos_gw_items_tmp` it) 
	ORDER BY it.is_active ASC, it.is_complete ASC, it.int_sort_1, it.contents_so
*/
#$this->oDb->query( 'SET SQL_BIG_SELECTS=1' );

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

/* Sorting orders */
switch ( $this->gv['area']['sort'] )
{
	case 1: $this->oDb->order_by( 'it.id_lang_c' ); break;
	case 2: $this->oDb->order_by( 'it.item_mdate DESC' ); break;
	case 3: $this->oDb->order_by( 'it.is_active ASC, it.is_complete ASC' ); break;
	case 4: $this->oDb->order_by( 'it.cnt_hits ASC' ); break;
}
/* 1.9.3: Custom alphabetic order */
for ( $i = 1; $i <= 8; $i++ )
{
	#$this->oDb->select( 'az'.$i.'.int_sort' );
	#$this->oDb->join( 'az_letters az'.$i.'', 'az'.$i.'.uc_crc32u = c.contents_'.$i.' AND c.id_lang = az'.$i.'.id_lang', 'left', false );
	$this->oDb->order_by( 'it.int_sort_'.$i.', it.contents_so' );
}

$this->oDb->limit( $this->gv['area']['per'], $offset );
$ar_sql = $this->oDb->get()->result_array();

$ar_item_ids = array();
foreach ( $ar_sql as $ar_v )
{
	$ar_item_ids[$ar_v['id_item']] = $ar_v['id_item'];
}
unset( $ar_v );
$ar_sql_items = array();
/**
 * ----------------------------------------------
 * Select Items
 * ----------------------------------------------
 */
if ( !empty( $ar_item_ids ) )
{
	$this->oDb->select( 'i.id_item, i.is_active, i.is_complete, i.item_id_user_created, uri.item_uri' );
	$this->oDb->select( 'c.contents_value_cached, c.contents_a, c.id_field, c.id_contents' );
	$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf' ) );
	$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
	$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
	$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
	$this->oDb->where( array( 'mftf.id_fieldset' => '1' ) );
	
	$this->oDb->where_in( 'i.id_item', $ar_item_ids );
	foreach ( $ar_item_ids as $id_item_in )
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
	$ar_items[$ar_v['id_item']][$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;
}
unset( $ar_v );

/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'items.manage' );

/* Definition preview */
$this->oTarget->parse__is_preview = 1;
/* Complete definition text */
if ( $this->V->int_max_chars_preview == '-1' )
{
	$this->oTarget->parse__is_preview = 0;
}

/* */
$ar_statuses = $this->oTarget->get_statuses();
/* */
$ar_classnames = $this->oTarget->get_statuses_classnames();

/* */
$oHref_edit = $this->oHtml->oHref();
$oHref_edit->set( 'a', 'edit' );
$oHref_edit->set( 't', 'items' );
	
$cnt = 1;
foreach ( $ar_items as $id_item => $ar_fields_content)
{
#prn_r( $ar_fields_content );

	list( $ar_item_settings, $str_item, $str_item_cut, $str_item_url, $str_descr, $str_descr_cut ) = $this->oTarget->parse_ar_item( $ar_fields_content );
	
	$id_checkbox = 'item-'.$id_item;
	$oBlock->oTpl->assign( 'items.list.cnt', ( $cnt + $offset ) );
	$oBlock->oTpl->assign( 'items.list.checkbox_html', '<input onclick="oItems.select(this.value)" type="checkbox" id="'.$id_checkbox.'" name="arp[ar_items]" value="'.$id_item.'" />' );
	$oBlock->oTpl->assign( 'items.list.id_checkbox', $id_checkbox );
	$oBlock->oTpl->assign( 'items.list.item_title', $str_item_url );
	$oBlock->oTpl->assign( 'items.list.item_descr', $str_descr );
	$oBlock->oTpl->assign( 'items.list.id_item', $id_item );
	$oBlock->oTpl->assign( 'items.list.status', '<span class="'.$ar_classnames[$ar_item_settings['is_active']].'">'.$ar_statuses[$ar_item_settings['is_active']].'</span>' );

	/* Actions */
	$oJsMenuTr = new site_jsMenu();
	$oJsMenuTr->icon = '&#160;'.$this->V->str_class_dropdownmenu;
	$oJsMenuTr->event = 'onmouseover';
	$oJsMenuTr->classname = 'btn add';
	
	$oHref_edit->set( 'id_item', $id_item );
	$oJsMenuTr->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=' . $oHref_edit->get() ), $this->oTkit->_( 1042 ) );
	$oJsMenuTr->append( 'javascript:oItems.remove_confirm('.$id_item.')', $this->oTkit->_( 1043 ) );

	$oBlock->oTpl->assign( 'items.list.actions', $oJsMenuTr->get_html() );

	$oBlock->oTpl->parse_block('items.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_( 1023 ) );
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) );
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) );
$oBlock->oTpl->assign( 'l:1067', $this->oTkit->_( 1067 ) ); /* Status */
$oBlock->oTpl->assign( 'l:1171', $this->oTkit->_( 1171 ) ); /* Sorting order */


/* Sorting orders */
$ar_str_sorting = array();
$oHref_sort = $this->oHtml->oHref();
$oHref_sort->set( array( 'a' => 'manage', 't' => 'items', 'per' => $this->gv['area']['per'] ) );
/* Specify letter */
if ( isset( $this->gv['area']['a1'] ) )
{
	$oHref_sort->set( 'a1', $this->gv['area']['a1'] );
}
/* Specify locale code */
if ( isset( $this->gv['area']['lc'] ) && isset( $this->ar_languages_locale[$this->gv['area']['lc']] ) )
{
	$oHref_sort->set( 'lc', $this->gv['area']['lc'] );
}
foreach ( array( 1 => 1172, 2 => 1173, 3 => 1174 ) as $id_sort => $l )
{
	$oHref_sort->set( 'sort', $id_sort );
	$classname = ( $id_sort == $this->gv['area']['sort'] ) ? 'highlight' : '';
	$ar_str_sorting[$id_sort] = $this->oHtmlAdm->a_href( 
		array( $this->V->file_index, '#area' => $oHref_sort->get() ), array( 'class' => $classname ), $this->oTkit->_( $l ) 
	);
}
$oBlock->oTpl->assign( 'v:sorting_order', implode( ', ', $ar_str_sorting ) );


$oBlock->oTpl->assign( 'v:id_table', 'items-list' );
$this->oOutput->append_js( 'jsF.stripe("items-list");' );
$oBlock->oTpl->assign( 'v:az', implode( ' ', $ar_az ) );
$oBlock->oTpl->assign( 'v:cnt_records', $cnt_total );

$this->oOutput->append_js( 'jsF.Set("oTkit_1043", "'.$this->oTkit->_( 1043 ).'");' ); /* Remove */
$this->oOutput->append_js( 'jsF.Set("oTkit_1051", "'.$this->oTkit->_( 1051 ).'");' ); /* Are you sure? */

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

/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
if ( $this->gv['sef_output'] != 'js' || $this->gv['sef_output'] != 'css' || $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1006 ) );
}


?>