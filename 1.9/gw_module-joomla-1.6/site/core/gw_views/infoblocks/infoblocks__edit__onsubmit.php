<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


if ( $ar['is_active'] == GW_STATUS_REMOVE )
{
	/* Remove item from database */
	$this->o->oDb->delete( 'blocks', array( 'id_block' => $this->o->gv['id'] ), 1 );
	return;
}
		

/* Start oGWiki, input -> db */
$oGWiki_db = $this->o->_init_wiki_( 'input', 'db' );

$q__blocks['is_active'] = $ar['is_active'];
$q__blocks['block_type'] = $ar['block_type'];
$q__blocks['block_place'] = $ar['block_place'];
$q__blocks['block_contents'] = $oGWiki_db->proc( $ar['block_contents'] );
$q__blocks['block_name'] = $oGWiki_db->proc( $ar['block_name'] );
$q__blocks['block_mdate'] = $this->o->V->datetime_gmt;

$this->o->oDb->update( 'blocks', $q__blocks, array( 'id_block' => $this->o->gv['id'] ) );

/* Redirect to Infoblocks: Manage */
$is_redirect = 1;

?>