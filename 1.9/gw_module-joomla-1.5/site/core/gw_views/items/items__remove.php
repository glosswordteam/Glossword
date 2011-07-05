<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

/* Switch between output modes */
switch( $this->gv['sef_output'] )
{
	case 'ajax':

		if ( $this->V->is_send_headers )
		{
			header('Content-type: text/plain; charset=utf-8');
		}

		/* Check or permission first */
		if ( !$this->oSess->is('items') )
		{
			print 0;
			return;
		}
		
		/**
		 * Read item settings
		 */
		$this->oDb->select( 'i.id_item, i.item_id_user_created', false );
		$this->oDb->from( array( 'items i' ) );
		$this->oDb->where( array( 'i.id_item' => $this->gv['id_item'] ) );
		$this->oDb->limit(1);
		$ar_item_sql = $this->oDb->get()->result_array();
		if ( empty($ar_item_sql) )
		{
			/* No such item */
			print 2;
			return;
		}
		$ar_item =& $ar_item_sql[0];

		/* Detect screen owner */
		$ar_item['is_item_owner'] = 1;
		if ( $this->oSess->is('items-own') && $this->oSess->id_user == $ar_item['item_id_user_created'] )
		{
			$ar_item['is_item_owner'] = 0;
		}
		
		/* Check permission again */
		if ( !$ar_item['is_item_owner'] && !$this->oSess->is('items') )
		{
			print 0;
			return;
		}

		/* Remove item from database */
		$this->oDb->delete( 'items', array( 'id_item' => $ar_item['id_item'] ), 1 );
		$this->oDb->delete( 'items_uri', array( 'id_item' => $ar_item['id_item'] ), 1 );
		$this->oDb->delete( 'items_tmp', array( 'id_item' => $ar_item['id_item'] ), 1 );
		$this->oDb->delete( 'contents', array( 'id_item' => $ar_item['id_item'] ) );
		$this->oDb->delete( 'contents_si', array( 'id_item' => $ar_item['id_item'] ) );
		$this->oDb->delete( 'map_item_to_tag', array( 'id_item' => $ar_item['id_item'] ) );

		/* Find empty tags */
#		$this->tags__find_empty();

		/* Rebuild a user stats */
		#$this->stat_user_rebuild($ar_screen['id_user']);

		/* Clear cached tags */
#		$this->oCache->remove_by_unit('tags_nav');

		print 1;

	break;
	default:

	break;
}

?>