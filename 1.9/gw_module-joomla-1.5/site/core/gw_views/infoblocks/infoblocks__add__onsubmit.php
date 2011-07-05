<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/* Start oGWiki, input -> db */
$oGWiki_db = $this->o->_init_wiki_( 'input', 'db' );

$q__blocks['is_active'] = 1;
$q__blocks['block_type'] = $ar['block_type'];
$q__blocks['block_place'] = $ar['block_place'];
$q__blocks['block_contents'] = $oGWiki_db->proc( $ar['block_contents'] );
$q__blocks['block_name'] = $oGWiki_db->proc( $ar['block_name'] );
$q__blocks['block_cdate'] = $this->o->V->datetime_gmt;
$q__blocks['block_mdate'] = $this->o->V->datetime_gmt;

$this->o->oDb->insert( 'blocks', $q__blocks );

/* Redirect to Infoblocks: Manage */
$is_redirect = 1;

?>