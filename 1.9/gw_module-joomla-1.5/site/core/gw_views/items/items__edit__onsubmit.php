<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Select MAX Contents ID */
$this->o->oDb->select_max( 'id_contents', 'id' );
$this->o->oDb->from( 'contents' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_contents_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* Start oGWiki, input -> db */
$oGWiki_db = $this->o->_init_wiki_( 'input', 'db' );
/* Start  oGWiki, input -> html */
$oGWiki_html = $this->o->_init_wiki_( 'db', 'html' );

/* Could be several items */
$ar_id_items = explode( ':', $this->o->gv['id_item'] );

/* For each item */
foreach ( $ar_id_items as $id_item )
{
	$q__items['item_mdate'] = $this->o->V->datetime_gmt;
	$q__items['item_id_user_modified'] = $this->o->oSess->id_user;

	/* For each field */
	foreach ( $ar[$id_item] as $id_field => $ar_v )
	{
		/* */
		switch ( $id_field )
		{
			case 1: /* Term */

				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );

				/* Term cannot be empty */
				if ( $ar_v['contents_value'] == '' )
				{
					$msg_error = $this->o->oTkit->_( 1052 );
					$is_error = 1;
					return;
				}

				$q__items['is_active'] = $ar_v['is_active'];
				$q__items['is_complete'] = $ar_v['is_complete'];
				
				$id_lang = $ar_v['id_lang'];
			break;
			case 2: /* Definition */
				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );
			break;
			case 3: /* See also */
				/* Parse `contents_value` */
				$ar_v['contents_value'] = $oGWiki_db->proc( $ar_v['contents_value'] );
				$ar_v['contents_value_cached'] = $oGWiki_html->proc( $ar_v['contents_value'] );
			break;
		}

		/* Filter Item */
		$item_filtered = $this->o->items__filter( $ar_v['contents_value'] );

		/* Construct alphabetic order */
		$q__contents[$id_field] = $this->o->get_az_index( $item_filtered, $ar_v['contents_value'], $id_lang );

		/* Create Item URI */
		if ( $id_field == $this->o->V->id_field_root )
		{
			$q__items_uri['item_uri'] = $this->o->items__uri( $item_filtered, $id_item  );
		}

		/* */
		#$q__contents[$id_field]['id_field'] = $id_field;
		$q__contents[$id_field]['id_user_modified'] = $this->o->oSess->id_user;
		$q__contents[$id_field]['id_lang'] = $id_lang;
		$q__contents[$id_field]['cnt_bytes'] = strlen( $ar_v['contents_value'] );
		$q__contents[$id_field]['cnt_words'] = $this->o->count_words( $item_filtered );
		$q__contents[$id_field]['contents_value'] = $ar_v['contents_value'];
		$q__contents[$id_field]['contents_value_cached'] = $ar_v['contents_value_cached'];

		/** Remove empty values **/
		if ( $ar_v['id_contents'] && $ar_v['contents_value'] == '' )
		{
			/* @todo: fix for `id_user_created` */
			$oSearchIndex->remove_contents( $ar_v['id_contents'] );

			/* REMOVE `contents` */
			$this->o->oDb->where( array( 'id_contents' => $ar_v['id_contents'] ) );
			if ( !$this->o->oSess->is( 'items' ) )
			{
				/* No permissions to edit other`s records */
				$this->o->oDb->where( array( 'id_user_created' => $ar_v['id_user_created'] ) );
			}
			$this->o->oDb->delete( 'contents' );
		}
		/** Add new values **/
		if ( $ar_v['id_contents'] == 0 && $ar_v['contents_value'] != '' )
		{
			/* Search Index */
			$oSearchIndex->update_contents( $id_contents_max, $id_item, $id_lang, $q__contents[$id_field]['_si'] );
			unset( $q__contents[$id_field]['_si'] );

			/* INSERT `contents` */
			$q__contents[$id_field]['id_item'] = $id_item;
			$q__contents[$id_field]['id_field'] = $id_field;
			$q__contents[$id_field]['id_contents'] = $id_contents_max;
			$q__contents[$id_field]['id_user_created'] = $this->o->oSess->id_user;
			$q__contents[$id_field]['id_user_modified'] = 0;
			
			/* INSERT */
			$this->o->oDb->insert( 'contents', $q__contents[$id_field] );

			unset( $q__contents[$id_field] );
			++$id_contents_max;
		}
		/** Update existent values **/
		if ( $ar_v['id_contents'] && $ar_v['contents_value'] != '' )
		{
			/* Search Index */
			$oSearchIndex->update_contents( $ar_v['id_contents'], $id_item, $id_lang, $q__contents[$id_field]['_si'] );
			unset( $q__contents[$id_field]['_si'] );

			/* UPDATE `contents` */
			$this->o->oDb->where( array( 'id_contents' => $ar_v['id_contents'] ) );
			if ( !$this->o->oSess->is( 'items' ) )
			{
				/* No permissions to edit other`s records */
				$this->o->oDb->where( array( 'id_user_created' => $ar_v['id_user_created'] ) );
			}
		
			/* UPDATE */
			$this->o->oDb->update( 'contents', $q__contents[$id_field] );
		}
	}

	/* */
	$this->o->oDb->update( 'items', $q__items, array( 'id_item' => $id_item ) );
	$this->o->oDb->update( 'items_uri', $q__items_uri, array( 'id_item' => $id_item ) );

	/* Update alphabetic order */ 
	$this->o->oDb->delete( 'items_tmp', array( 'id_item' => $id_item ), 1 );
	
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
	$this->o->oDb->where( array( 'i.id_item' => $id_item ) );
	$this->o->oDb->group_by( 'i.id_item' );
	$this->o->oDb->query( 'INSERT INTO `'.$this->o->oDb->dbprefix.'items_tmp` '.$this->o->oDb->get_select() );

}

$is_redirect = 1;

?>