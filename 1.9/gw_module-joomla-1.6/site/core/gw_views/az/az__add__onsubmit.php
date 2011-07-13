<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/* */
$q__az_letters = array(
	'id_lang' => $ar['id_lang'],
	'uc' => '',
	'lc' => '',
	'uc_crc32u' => sprintf( "%u", crc32() ),
	'int_sort' => 10,
);

/* INSERT */
$this->o->oDb->insert( 'az_letters', $q__az_letters );

/* Redirect */
$oHref_r = $this->o->oHtmlAdm->oHref();
$oHref_r->set( 'a', 'manage' );
$oHref_r->set( 't', 'az' );
$oHref_r->set( 'id_lang', $ar['id_lang'] );
$oHref_r->set( 'is_saved', '1' );

$is_delay = 0;
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=' . $oHref_r->get() );
$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );

?>