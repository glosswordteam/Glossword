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

$this->gv['id_old'] = $this->gv['id'];
/* Extract real $this->gv['id'] */
if ( $this->V->link_template_uri != '' )
{
	$ar_temp = explode( '%s', $this->V->link_template_uri );
	$this->gv['id'] = preg_replace( "/^".$ar_temp[0]."/", "", $this->gv['id'] );
	if ( isset($ar_temp[1]) )
	{
		$this->gv['id'] = preg_replace( "/".$ar_temp[1]."$/", "", $this->gv['id'] );
	}
	$this->gv['area']['id'] = $this->gv['id'];
}


/**
 * ----------------------------------------------
 * Select Item ID
 * ----------------------------------------------
 */
$this->oDb->select( 'i.id_item' );
$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf' ) );
$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
$this->oDb->where( array( 'mftf.id_fieldset' => $this->V->id_field_root ) );
switch ( $this->V->link_mode ) {
  case GW_LINK_ID:
    if ( isset( $this->gv['area']['a'] ) && $this->gv['area']['a'] == 'search' ){
      $this->oDb->where( array( 'uri.item_uri' => $this->gv['id'] ) );
    }
    else {
      $this->oDb->where( array( 'i.id_item' => $this->gv['id'] ) );
    }
    break;
  case GW_LINK_URI:
    $this->oDb->where( array( 'uri.item_uri' => $this->gv['id'] ) );
    break;
  case GW_LINK_TEXT:
    $this->oDb->where( array( 'c.contents_value_cached' => $this->gv['area']['id'] ) );
    break;
}
/* Allow view offline items */
if ( !$this->oSess->is( 'items' ) && !$this->oSess->is( 'items-own' ) )
{
	$this->oDb->where( array( 'i.is_active' => '1' ) );
}
$this->oDb->limit( 1 );

$ar_sql_item = $this->oDb->get()->result_array();

/* No such term */
if ( empty( $ar_sql_item  ) )
{
	$this->oOutput->append_html( $this->soft_redirect(
		 $this->oTkit->_( 1046 ), $this->oHtml->url_normalize( $this->V->file_index.'?#sef_output='.$this->gv['sef_output'] ), GW_COLOR_FALSE
	));
	return false;
}
$id_item =& $ar_sql_item[0]['id_item'];



/**
 * ----------------------------------------------
 * Select Item using $id_item
 * ----------------------------------------------
 */
$this->oDb->select( 'i.id_item, i.item_id_user_created, i.item_id_user_modified, i.is_complete, i.is_active, uri.item_uri' );
$this->oDb->select( 'c.id_lang, c.contents_value_cached, c.contents_a, c.id_field, c.id_contents' );
$this->oDb->select( 'i.item_cdate, i.item_mdate' );
$this->oDb->select( 'CONCAT( l.isocode1, "_", l.region) locale', false );
$this->oDb->select( 'uc.id_user uc_id_user, uc.user_fname uc_user_fname, uc.user_sname uc_user_sname, uc.user_nickname uc_user_nickname, uc.user_settings uc_user_settings' );
#$this->oDb->select( 'um.user_fname um_user_fname, um.user_sname um_user_sname, um.user_nickname um_user_nickname, um.user_settings um_user_settings' );
$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf', 'languages l' ) );

/* User may not exist */
$this->oDb->join( $this->V->db_table_users.' uc', 'i.item_id_user_created = uc.id_user', 'left' );
#$this->oDb->join( $this->V->db_table_users.' um', 'i.id_user_modified = um.id_user', 'left' );

$this->oDb->where( array( 'c.id_lang = l.id_lang' => NULL ) );
$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
$this->oDb->where( array( 'mftf.id_fieldset' => $this->V->id_field_root ) );
$this->oDb->where( array( 'i.id_item' => $id_item ) );
$this->oDb->order_by( 'mftf.int_sort ASC' );
$ar_sql_item = $this->oDb->get()->result_array();


/* Re-arrange */
$this->gv['id_item'] = 0;
$ar_item = array();
foreach ( $ar_sql_item as $ar_v)
{
	$ar_v['contents_a'] = str_replace( "\0", '', $ar_v['contents_a'] );
	$this->gv['id_item'] = $ar_v['id_item'];
	$ar_item[$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;
}

/* Detect item owner */
$this->ar_item['item_id_user_created'] = $ar_sql_item[0]['item_id_user_created'];
$this->ar_item['is_item_owner'] = 0;
if ( $this->oSess->is('items-own') && $this->oSess->id_user == $this->ar_item['item_id_user_created'] )
{
	$this->ar_item['is_item_owner'] = 1;
}

/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'items.view' );

$this->oTarget->parse__is_preview = 0;
list( $ar_item_settings, $str_item, $str_item_cut, $str_item_url, $str_descr, $str_descr_cut ) = $this->oTarget->parse_ar_item( $ar_item );

/* Used to highlight A-Z toolbar */
$this->gv['area']['a1'] = $ar_item_settings['contents_a'];
$this->gv['area']['lc'] = $ar_item_settings['locale'];

/* */
$oBlock->oTpl->assign( 'v:item_descr', $str_descr );
$oBlock->oTpl->assign( 'v:item_descr_cut', $str_descr_cut );
$oBlock->oTpl->assign( 'v:item_title', $str_item );
$oBlock->oTpl->assign( 'v:item_title_cut', $str_item_cut );

$date_format = '%d %f %Y %H:%i';
$oBlock->oTpl->assign( 'v:item_cdate', $this->oTkit->date( $date_format, strtotime( $ar_item_settings['item_cdate'] ) ) );
$oBlock->oTpl->assign( 'v:item_mdate', $this->oTkit->date( $date_format, strtotime( $ar_item_settings['item_mdate'] ) ) );

/* id_user_created */
$oBlock->oTpl->assign( 'v:uc_user_displayed_name', $ar_item_settings['uc_user_displayed_name'] );


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


/**
 * ----------------------------------------------
 * Next/Previous items
 * ----------------------------------------------
 */
$ar_prev_next = array( 'prev' => '', 'next' => '');
$cache_key = $this->V->link_mode.'-'.$ar_item_settings['item_cdate'];
if ( $this->V->is_cache_items_prevnext && $this->oCache->in_cache( $cache_key, 'items-prevnext', $this->V->time_cache_az ) )
{
	$ar_prev_next = $this->oCache->load();
}
else
{
	/* Previous item */
	$this->oDb->select( 'i.id_item, c.contents_value_cached, uri.item_uri', false );
	$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c' ) );
	$this->oDb->where( array( 
		'i.id_item = uri.id_item' => NULL, 
		'i.id_item = c.id_item' => NULL, 
		'c.id_field' => '1',
		'i.is_active' => '1',
		'i.item_cdate <' => $ar_item_settings['item_cdate']
	));
	$this->oDb->order_by( 'i.item_cdate DESC, i.id_item' );
	$this->oDb->limit( 1 );
	$ar_sql_pn = $this->oDb->get()->result_array();

	if ( !empty( $ar_sql_pn ) )
	{
		switch ( $this->V->link_mode )
		{
			case GW_LINK_ID:	$item_uri = $ar_sql_pn[0]['id_item']; break;
			case GW_LINK_URI:	$item_uri = $ar_sql_pn[0]['item_uri']; break;
			case GW_LINK_TEXT:	$item_uri = $this->oHtml->urlencode( $ar_sql_pn[0]['contents_value_cached'] ); break;
		}
		/* */
		if ( $this->V->link_template_uri != '' && $this->V->link_mode != GW_LINK_ID )
		{
			$item_uri = str_replace( '%s', $item_uri, $this->V->link_template_uri );
		}
		/* */
		$oHref = $this->oHtml->oHref();
		$oHref->set( 'id', $item_uri );
		$ar_prev_next['prev'] = $this->oHtml->a_href(
					array( $this->V->file_index, '#area' => $oHref->get() ), array(),
					'&#8592; '. $this->oFunc->smart_substr( $ar_sql_pn[0]['contents_value_cached'], 0, 32 )
		);
	}
	/* Next item */
	$this->oDb->select( 'i.id_item, c.contents_value_cached, uri.item_uri', false );
	$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c' ) );
	$this->oDb->where( array( 
		'i.id_item = uri.id_item' => NULL, 
		'i.id_item = c.id_item' => NULL, 
		'c.id_field' => '1',
		'i.is_active' => '1', 
		'i.item_cdate >' => $ar_item_settings['item_cdate']
	));
	$this->oDb->order_by( 'i.item_cdate ASC, i.id_item' );
	$this->oDb->limit( 1 );
	$ar_sql_pn = $this->oDb->get()->result_array();
	if ( !empty( $ar_sql_pn ) )
	{
		switch ( $this->V->link_mode )
		{
			case GW_LINK_ID:	$item_uri = $ar_sql_pn[0]['id_item']; break;
			case GW_LINK_URI:	$item_uri = $ar_sql_pn[0]['item_uri']; break;
			case GW_LINK_TEXT:	$item_uri = $this->oHtml->urlencode( $ar_sql_pn[0]['contents_value_cached'] ); break;
		}
		/* Template for URI */
		if ( $this->V->link_template_uri != '' && $this->V->link_mode != GW_LINK_ID )
		{
			$item_uri = str_replace( '%s', $item_uri, $this->V->link_template_uri );
		}
		/* */
		$oHref = $this->oHtml->oHref();
		$oHref->set( 'id', $item_uri );
		$ar_prev_next['next'] = $this->oHtml->a_href(
					array( $this->V->file_index, '#area' => $oHref->get() ), array(),
					$this->oFunc->smart_substr( $ar_sql_pn[0]['contents_value_cached'], 0, 32 ).' &#8594;'
		);
	}
	/* Cached Units: Save */
	if ( $this->V->is_cache_items_prevnext )
	{
		$this->oCache->save( $ar_prev_next );
	}
}


#prn_r( $ar_sql_pn );
#prn_r( $ar_prev_next );

/**
 * ----------------------------------------------
 * Assign HTML-variables
 * ----------------------------------------------
 */
$oBlock->oTpl->assign( 'v:item_next', $ar_prev_next['next'] );
$oBlock->oTpl->assign( 'v:item_prev',  $ar_prev_next['prev'] );
$oBlock->oTpl->assign( 'v:az', implode( ' ', $ar_az ) );
$oBlock->oTpl->assign( 'v:aazz', implode( ' ', $ar_aazz ) );
$oBlock->oTpl->assign( 'l:1039', $this->oTkit->_( 1039 ) );


$this->oOutput->append_html( $oBlock->oTpl->get_html() );

/* Link to edit item */
if ( $this->ar_item['is_item_owner'] || $this->oSess->is( 'items' ) )
{
	$oHref_edit = $this->oHtmlAdm->oHref();
	$oHref_edit->set( 't', 'items' );
	$oHref_edit->set( 'a', 'edit' );
	$oHref_edit->set( 'id_item', $this->gv['id_item'] );
	$this->oTpl->assign_global( 'v:h1_edit', ' '.$this->oHtmlAdm->a_href(
			array( $this->V->file_index, '#area' => $oHref_edit->get(), '#old_uri' => $this->gv['id_old'], '#uri' =>  base64_encode( $this->V->uri )  ),
			array( 'class' => 'btn edit' ),
			$this->oTkit->_( 1042 ) . $this->V->str_class_edit
	));
}

/**
 * ----------------------------------------------
 * Prepare page <title> and breadcrumbs
 * ----------------------------------------------
 */
if ( $this->V->link_template_text != '' )
{
	$str_item = str_replace( '%s', $str_item, $this->V->link_template_text );
}

$this->oOutput->append_html_title( $str_item_cut );

$this->oTpl->assign_global( 'v:h1', $str_item );
$this->oOutput->append_bc( $str_item_cut, '', '0' );

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