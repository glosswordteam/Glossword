<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-auto-seealso' );


/* Loading HTML-form */
if ( file_exists( $this->cur_htmlform ) )
{
	include_once( $this->cur_htmlform );
}
else
{
	class site_htmlforms extends site_forms3_validation{}
}


/* Default values */
$arVF = array();
/* Correct unknown settings */
$ar_settings = array( 'id_item' => 0, 'redirect' => 1, 
	array(
		1 => array( 'contents_value' => '', 'id_lang' => $this->oFunc->get_crc_u('eng'.'US'), 'id_contents' => 0, 'id_user_created' => $this->oSess->id_user, 'is_active' => 1, 'is_complete' => 1 ), 
		2 => array( 'contents_value' => '', 'id_lang' => $this->oFunc->get_crc_u('eng'.'US'), 'id_contents' => 0, 'id_user_created' => $this->oSess->id_user ),
		3 => array( 'contents_value' => '', 'id_lang' => $this->oFunc->get_crc_u('eng'.'US'), 'id_contents' => 0, 'id_user_created' => $this->oSess->id_user )
	),
	'mdate' => @date( "Y-m-d H:i:s", $this->V->time_req + $this->oSess->user_get_time_seconds() ),
	'cdate' => @date( "Y-m-d H:i:s", $this->V->time_req + $this->oSess->user_get_time_seconds() ),
);
/* Checkboxes */
$ar_onoff = array( array( 1 => array( 'is_active', 'is_complete' ) ) );
/* Required fields */
$ar_required = array();



/* */
$oHtmlForms = new site_htmlforms( $this );
$oHtmlForms->ar_onoff =& $ar_onoff;
$oHtmlForms->ar_required =& $ar_required;

/* */
if ( empty( $this->gv['arp'] ) )
{
	/* */
	foreach ( $ar_settings as $k => $v )
	{
		if ( !isset( $arVF[$k] ) ) { $arVF[$k] = $v; }
	}
	$this->oOutput->append_html( $oHtmlForms->before_submit( $arVF ) );
}
else
{
	$this->oOutput->append_html( $oHtmlForms->after_submit( $this->gv['arp'] ) );
}


/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
$this->oOutput->append_html_title( $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1001 ) );
$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1001 ) );


?>