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


$this->oOutput->append_js_collection( 'o-items' );

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
$ar_settings = array( 
	'input' => '', 'step' => 1,
	'source-direct' => 0, 'source-localfile' => 1, 'source-remotefile' => 0,
	'format-1' => 0, 'format-2' => 1, 'format-3' => 0, 'format-4' => 0, 'cmp-1' => 1, 'cmp-2' => 0,
	'separator' => ';', 'is_publish' => 1, 'is_overwrite' => 1, 'is_head' => 1, 'is_convert_escape' => 1, 
	'id_lang' => $this->oFunc->get_crc_u('eng'.'US')
);
/* Restore from users settings */
foreach ( array( 'separator', 'is_convert_escape', 'is_head', 'is_overwrite', 'is_publish' ) as $v )
{
	$vs = (string) $this->oSess->user_get( 'items_import__'.$v );
	$ar_settings[$v] = $vs != '' ? $vs : $ar_settings[$v];
}
/* Radio-buttons */
foreach ( array( 1 => 'format-1', 2 => 'format-2', 3 => 'format-3', 4 => 'format-4', 'gw19' => 'cmp-1', 'gw18' => 'cmp-2' ) as $k => $v )
{
	$vs = (string) $this->oSess->user_get( 'items_import__'.$v );
	$ar_settings[$v] = $vs != '' ? $vs : $ar_settings[$v];
}


/* Checkboxes */
$ar_onoff = array( 'is_overwrite', 'is_publish', 'is_convert_escape', 'is_head',
	'gw19_is_az', 'gw19_is_term_uri', 'gw19_is_cached', 'gw19_is_si', 'gw18_is_az', 'gw18_is_term_uri'
 );
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
	$this->oOutput->append_html_title( $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1077 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1077 ).': '.$this->oTkit->_( 1096, $this->gv['arp']['step'], 3 ) );
}


?>