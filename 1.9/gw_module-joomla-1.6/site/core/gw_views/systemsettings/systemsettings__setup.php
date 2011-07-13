<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}
/**
 * ----------------------------------------------
 * Check for permissions
 * ----------------------------------------------
 */ 
if ( !$this->oSess->is('sys-settings') )
{
	return false;
}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

/**
 * ----------------------------------------------
 * Loading HTML-form
 * ----------------------------------------------
 */
if ( file_exists( $this->cur_htmlform ) )
{
	include_once( $this->cur_htmlform );
}
else
{
	class site_htmlforms extends site_forms3_validation{}
}

$arVF = array();
/* Read system settings */
foreach ( $this->V as $k => $v )
{
	if ( is_string( $v ))
	{
		$arVF[$k] = $v;
	}
}

/* Correct unknown settings */
$ar_default_settings = array();
/* Checkboxes */
$ar_onoff = array( 'is_debug_time', 'is_debug_db', 'is_debug_cache', 'is_link_item', 'is_show_az' );
/* Required fields */
$ar_required = array();


/* */
$oHtmlForms = new site_htmlforms($this);
$oHtmlForms->ar_onoff =& $ar_onoff;
$oHtmlForms->ar_required =& $ar_required;

/* */
if ( empty( $this->gv['arp'] ) )
{
	/* */
	foreach ( $ar_default_settings as $k => $v )
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
if ( $this->gv['sef_output'] != 'js' || $this->gv['sef_output'] != 'css' || $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_(1040) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_(1040) );
}

?>