<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


#$this->oOutput->append_js_collection( 'o-tvs' );

/**
 * ----------------------------------------------
 * Check for permissions
 * ----------------------------------------------
 */ 
if ( !$this->oSess->is('sys-settings') )
{
	$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1045 ).'</div>' );
	return false;
}

/**
 * ----------------------------------------------
 * Load HTML-form
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


/* Correct unknown settings */
$ar_settings_default = array( 'is_save' => 1 );
/* Checkboxes */
$ar_onoff = array( 'is_save' );
/* Required fields */
$ar_required = array( );

/* */
$oHtmlForms = new site_htmlforms( $this );
$oHtmlForms->ar_onoff =& $ar_onoff;
$oHtmlForms->ar_required =& $ar_required;

/* */
if ( empty( $this->gv['arp'] ) )
{
	/* */
	foreach ( $ar_settings_default as $k => $v )
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
	$this->oOutput->append_html_title( $this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1079 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1079 ) );
}


?>