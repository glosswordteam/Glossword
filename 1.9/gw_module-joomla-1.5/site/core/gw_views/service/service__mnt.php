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


$this->gv['area']['s'] = isset( $this->gv['area']['s'] ) ? $this->gv['area']['s'] : '';


/* */
$ar_tasks = array(
	'clear_cache' => $this->oTkit->_( 1128 ),
	'clear_items' => $this->oTkit->_( 1107 ),
	'repair_optimize' => $this->oTkit->_( 1132 )
);

/**
 * ----------------------------------------------
 * Prepare page <title> and breadcrumbs
 * ----------------------------------------------
 */
$str_title_append = isset( $ar_tasks[$this->gv['area']['s']] ) ? ': '.$ar_tasks[$this->gv['area']['s']] : '';
$this->oTpl->assign_global( 'v:h1', $this->oTkit->_( 1110 ) . $str_title_append );
$this->oOutput->append_html_title( $this->oTkit->_( 1110 ) );

if ( isset( $ar_tasks[$this->gv['area']['s']]  ) )
{
	$this->oOutput->append_html_title( $ar_tasks[$this->gv['area']['s']] );
}

$this->oOutput->append_bc( $this->oTkit->_( 1110 ), '', '0' );


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

/* Load maintenance task */

/* Construct file name with HTML-form */
$cur_htmlform = $this->V->path_views.'/'.$this->gv['target'].'/'.$this->gv['target'].'__'.$this->gv['area']['s'].'__htmlform.php';

if ( file_exists( $cur_htmlform) )
{
	include_once( $cur_htmlform );
	
	/* */
	$oHtmlForms = new site_htmlforms( $this );
	$oHtmlForms->ar_onoff =& $ar_onoff;
	$oHtmlForms->ar_required =& $ar_required;
	
	/* */
	if ( empty( $this->gv['arp'] ) )
	{
		/* */
		foreach ($ar_settings as $k => $v)
		{
			if ( !isset( $arVF[$k] ) ) { $arVF[$k] = $v; }
		}
		$this->oOutput->append_html( $oHtmlForms->before_submit( $arVF ) );
	}
	else
	{
		$this->oOutput->append_html( $oHtmlForms->after_submit( $this->gv['arp'] ) );
	}
}
else
{
	$oHref = $this->oHtmlAdm->oHref();
	$oHref->set( 'a', 'mnt' );
	$oHref->set( 't', 'service' );
	/* */
	$ar_html = array();
	foreach ( $ar_tasks as $id_task => $task_name )
	{
		$oHref->set( 's', $id_task);
		$ar_html[] = $this->oHtmlAdm->a_href(
					array( $this->V->file_index, '#area' => $oHref->get() ), array(),
					$task_name
		);
	}
	$this->oOutput->append_html( '<ul class="gw-list">' );
	$this->oOutput->append_html( '<li>'. implode( '</li><li>',  $ar_html ) .'</li>' );
	$this->oOutput->append_html( '</ul>' );
}


?>