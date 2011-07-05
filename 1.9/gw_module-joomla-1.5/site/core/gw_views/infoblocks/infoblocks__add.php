<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


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
$ar_settings = array(
	'is_active' => 1, 'block_type' => 1, 'block_place' => 1, 'block_name' => '', 'block_contents' => ''
);
/* Checkboxes */
$ar_onoff = array();
/* Required fields */
$ar_required = array( 'block_type', 'block_place', 'block_name', 'block_contents' );


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
if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1001 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1001 ) );
}

?>