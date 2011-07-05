<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


/**
 * ----------------------------------------------
 * Check for permissions
 * ----------------------------------------------
 */ 
if ( !$this->oSess->is('users') )
{
	return false;
}

/* Edit own settings by default */
if ( !isset( $this->gv['id_user'] ) )
{
	$this->gv['id_user'] = $this->oSess->id_user;
}

/* Check permissions on editing profile of other user */
if ( ($this->gv['id_user'] != $this->oSess->id_user) && !$this->oSess->is('users') )
{
	$this->gv['id_user'] = $this->oSess->id_user;
}

/* Set a special attribute for editing further options */
$this->a( 'is_profile_owner', 0 );
if ( $this->gv['id_user'] == $this->oSess->id_user )
{
	$this->a('is_profile_owner', 1);
}

/* Load user settings */
if ( $this->V->is_profile_owner )
{
	/* Use settings of the current user */
	$arV = $this->oSess->user_get();
}
else
{
	/* Load settings of another user */
	$arV = $this->oSess->user_load_values( $this->gv['id_user'] );
	/* No such user */
	/* @todo: create function to display errors */
	if ( empty($arV) )
	{ 
		$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_(1015).'</div>' );
		return false;
	}
}

/* Load HTML-form */
if ( file_exists( $this->cur_htmlform ) )
{
	include_once( $this->cur_htmlform );
}
else
{
	$arV = array();
	class site_htmlforms extends site_forms3_validation{}
}

/* */
$oHtmlForms = new site_htmlforms( $this );
$oHtmlForms->ar_onoff =& $ar_onoff;
$oHtmlForms->ar_required =& $ar_required;

/* */
if ( empty( $this->gv['arp'] ) )
{
	/* Not posted yet */
	/* Fix unknown user settings */
	$ar_settings_default = array(
		'is_dst' => 0, 'locale' => 'english', 'gmt_offset' => 0, 
		'date_format' => 'd M H:i', 'sort_scr' => 1, 'displayed_name' => 4
	);
	foreach ( $ar_settings_default as $k => $v )
	{
		if ( !isset( $arV['user_settings'][$k] ) )
		{
			$arV['user_settings'][$k] = $v;
		}
	}
	$this->oOutput->append_html( $oHtmlForms->before_submit( $arV ) );
}
else
{
	/* Posted */
	$this->oOutput->append_html( $oHtmlForms->after_submit( $this->gv['arp'] ) );
}


if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
{
	/* Add to the title */
	$this->oOutput->append_html_title( $this->oTkit->_(1016) );
	$this->oTpl->assign( 'v:h1', $this->oTkit->_(1016) );
}

?>