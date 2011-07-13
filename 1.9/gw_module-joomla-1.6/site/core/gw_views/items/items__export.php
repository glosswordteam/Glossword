<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


/**
 * ----------------------------------------------
 * Check for permissions
 * ----------------------------------------------
 */ 
if ( !$this->oSess->is( 'sys-settings' ) )
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
$ar_settings = array( 'step' => 1, 'redirect' => 2, 'is_save' => 0,
	'format-1' => 1, 'format-2' => 0, 'format-3' => 0, 'format-4' => 0,
	'td_mode' => 'td', 'td_mode-t' => 0, 'td_mode-td' => 1, 'split' => 500, 
	'is_az' => 1, 'is_aazz' => 1, 'is_id_item' => 1, 'is_term_uri' => 1, 'is_si' => 1,
	'is_head' => 1, 'is_cached' => 1, 'separator' => ';'
 );
	
/* Checkboxes */
$ar_onoff = array( 'is_az', 'is_aazz', 'is_id_item', 'is_term_uri', 'is_save', 'is_head', 'is_si', 'is_cached' );
/* Required fields */
$ar_required = array( );

/* */
$oHtmlForms = new site_htmlforms( $this );
$oHtmlForms->ar_onoff =& $ar_onoff;
$oHtmlForms->ar_required =& $ar_required;

/* */
if ( empty( $this->gv['arp'] ) )
{
	$this->gv['arp']['step'] = 1;
	/* */
	foreach ($ar_settings as $k => $v)
	{
		if ( !isset( $arVF[$k] ) ) { $arVF[$k] = $v; }
	}
	$this->oOutput->append_html( $oHtmlForms->before_submit( $arVF ) );
}
else
{
	if ( $this->gv['arp']['step'] == 2 )
	{
		foreach ($ar_settings as $k => $v)
		{
			if ( !isset( $this->gv['arp'][$k] ) ) { $this->gv['arp'][$k] = $v; }
		}
		$this->oOutput->append_html( $oHtmlForms->before_submit( $this->gv['arp'] ) );
	}
	else
	{
		$this->oOutput->append_html( $oHtmlForms->after_submit( $this->gv['arp'] ) );
	}
}


/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1079 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1079 ).': '.$this->oTkit->_( 1096, $this->gv['arp']['step'], 3 ) );
}


/* */
$is_redirect = 0;

?>