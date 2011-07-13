<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/* Start oGWiki, input -> db */
$oGWiki_db = $this->o->_init_wiki_( 'input', 'db' );
/* Start  oGWiki, input -> html */
$oGWiki_html = $this->o->_init_wiki_( 'db', 'html' );

/* Select MAX Item ID */
$this->o->oDb->select_max( 'id_item', 'id' );
$this->o->oDb->from( 'items' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_item_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* Select MAX Contents ID */
$this->o->oDb->select_max( 'id_contents', 'id' );
$this->o->oDb->from( 'contents' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_contents_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* Unexpected input: id_term not specified */
if ( !isset( $this->o->gv['id_item'] ) || $this->o->gv['id_item'] == '' )
{
	$this->o->gv['id_item'] = 0;
}

/* Could be several items */
$ar_id_items = explode( ':', $this->o->gv['id_item'] );

/* For each item */
foreach ( $ar_id_items as $id_item )
{
	$q__items['id_item'] = $id_item_max;
	$q__items['item_cdate'] = $this->o->V->datetime_gmt;
	$q__items['item_mdate'] = $this->o->V->datetime_gmt;
	$q__items['item_id_user_created'] = $this->o->oSess->id_user;
	$q__items['item_id_user_modified'] = 0;

	$q__items_uri['id_item'] = $id_item_max;

	/* For each field */
	foreach ( $ar[$id_item] as $id_field => $ar_v )
	{
		$ar_v['contents_value'] = trim( $ar_v['contents_value'] );
		/* */
		switch ( $id_field )
		{
			case 1: /** Term **/
				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );
				
				if ( $ar_v['contents_value'] == '' )
				{
					$msg_error = $this->o->oTkit->_( 1052 );
					$is_error = 1;
					return;
				}
				
				/* Item URI */
				$q__items_uri['item_uri'] = $this->o->items__uri( $ar_v['contents_value'], $id_item_max );

				$q__items['is_active'] = $ar_v['is_active'];
				$q__items['is_complete'] = $ar_v['is_complete'];
				
				$id_lang = $ar_v['id_lang'];
			break;
			case 2: /** Definition **/
				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );
			break;
			case 3: /** See Also **/
				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );
			break;
		}

		/* Filter Item */
		$item_filtered = $this->o->items__filter( $ar_v['contents_value'] );

		/* Construct alphabetic order */
		$q__contents[$id_field] = $this->o->get_az_index( $item_filtered, $ar_v['contents_value'], $id_lang );

		/* */
		$q__contents[$id_field]['id_contents'] = $id_contents_max;
		$q__contents[$id_field]['id_item'] = $id_item_max;
		$q__contents[$id_field]['id_field'] = $id_field;
		$q__contents[$id_field]['id_user_created'] = $this->o->oSess->id_user;
		$q__contents[$id_field]['id_user_modified'] = 0;
		$q__contents[$id_field]['id_lang'] = $id_lang;
		$q__contents[$id_field]['cnt_bytes'] = strlen( $ar_v['contents_value'] );
		/* Count the number of words, approximately */
		$q__contents[$id_field]['cnt_words'] = $this->o->count_words( $ar_v['contents_value'] );
		$q__contents[$id_field]['contents_value'] = $ar_v['contents_value'];
		$q__contents[$id_field]['contents_value_cached'] = $ar_v['contents_value_cached'];
		
		/* */
		if ( $ar_v['contents_value'] == '' )
		{
			unset( $q__contents[$id_field] );
		}
		else
		{
			$oSearchIndex->update_contents( $id_contents_max, $id_item_max, $id_lang, $q__contents[$id_field]['_si'] );
			unset( $q__contents[$id_field]['_si'] );
		}
		++$id_contents_max;
	}
	
	$this->o->oDb->delete( 'contents', array( 'id_item' => $id_item_max ) );
	$this->o->oDb->delete( 'items', array( 'id_item' => $id_item_max ), 1 );
	$this->o->oDb->delete( 'items_uri', array( 'id_item' => $id_item_max ), 1 );
	
	$this->o->oDb->insert( 'contents', $q__contents );
	$this->o->oDb->insert( 'items_uri', $q__items_uri );
	$this->o->oDb->insert( 'items', $q__items );

	/* Update alphabetic order */ 
	$this->o->oDb->delete( 'items_tmp', array( 'id_item' => $id_item_max ), 1 );
	
	$this->o->oDb->query( 'SET SQL_BIG_SELECTS=1' );

	$this->o->oDb->select( 'i.id_item, c.id_lang, az1.id_lang' );
	$this->o->oDb->select( 'i.item_mdate, i.is_active, i.is_complete, i.cnt_hits, c.contents_a, c.contents_b, c.contents_so' );
	$this->o->oDb->from( array( 'items i', 'contents c' ) );
	$this->o->oDb->where( array( 'i.id_item = c.id_item' => NULL, 'c.id_field' => (string) $this->o->V->id_field_root ) );
	/* 1.9.3: Custom alphabetic order */
	for ( $i = 1; $i <= 8; $i++ )
	{
		$this->o->oDb->select( 'az'.$i.'.int_sort' );
		$this->o->oDb->join( 'az_letters az'.$i.'', 'az'.$i.'.uc_crc32u = c.contents_'.$i.' AND c.id_lang = az'.$i.'.id_lang', 'left', false );
	}
	$this->o->oDb->where( array( 'i.id_item' => $id_item_max ) );
	$this->o->oDb->group_by( 'i.id_item' );
	$this->o->oDb->query( 'INSERT INTO `'.$this->o->oDb->dbprefix.'items_tmp` '.$this->o->oDb->get_select() );
	
	#prn_r( $q__items );
	#prn_r( $q__items_uri );
	#prn_r( $q__contents );

	++$id_item_max;
}

$is_redirect = 1;

?>