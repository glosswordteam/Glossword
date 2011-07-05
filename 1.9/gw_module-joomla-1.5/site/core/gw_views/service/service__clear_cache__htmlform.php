<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Form values */
$arVF = array();
/* Correct unknown settings */
$ar_settings = array();
/* Checkboxes */
$ar_onoff = array();
/* Required fields */
$ar_required = array( );

/* */
class site_htmlforms extends site_forms3_validation
{
	/* make html-code */
	function get_form($ar)
	{
		$this->phrase_wait = $this->o->oTkit->_( 1082 );
		$this->phrase_incorrect = $this->o->oTkit->_( 1065 );
		$this->phrase_submit_ok = $this->o->oTkit->_( 1050 );
		$this->phrase_submit_cancel = $this->o->oTkit->_( 1125 );
		
		$this->set_tag( 'form', 'action', $this->o->V->server_dir_admin.'/'.$this->o->V->file_index );
		$this->set_tag( 'form', 'id', $this->o->gv['action'] );
		$this->set_tag( 'form', 'onkeypress', 'jsF.formKeypress(event, this)' );

		$this->is_htmlspecialchars = 0;
		$this->is_actions = 0;
		$this->is_actions_top = 1;
		$this->is_label_ids = 1;
		$this->is_submit_ok = 1;
		$this->is_submit_cancel = 1;
		
		/* Read cache statistics */
		$ar_table_stats = $this->o->oDb->show_table_status( 'cached_units', true );
		$ar_table_stats['Total_bytes'] = $ar_table_stats['Data_length'] + $ar_table_stats['Index_length'];
		
		if (  $ar_table_stats['Rows'] == 0 )
		{
			$this->is_submit_ok = 0;
		}

		/* */
		$this->new_fieldset( 'clear_cache', $this->o->oTkit->_( 1128 ) );
		$this->new_label( '', $this->o->oTkit->_( 1036 ).': <strong>'. $this->o->oTkit->number_format( $ar_table_stats['Rows'] ).'</strong>' );
		$this->new_label( '', $this->o->oTkit->_( 1129 ).': <strong>'. $this->o->oTkit->bytes( $ar_table_stats['Data_length'] ).'</strong>' );
		
		/* */
		$this->field( 'hidden', 'arg[action]', $this->o->gv['action'] );
		$this->field( 'hidden', 'arg[target]', $this->o->gv['target'] );
		$this->field( 'hidden', 'arg[area]', 's.'.$this->o->gv['area']['s'] );
		/* Append URL */
		if ( !empty( $this->o->V->sef_append ) )
		{
			/* @todo: parse arrays */
			foreach ( $this->o->V->sef_append as $k1 => $v1 )
			{
				$this->field( 'hidden', $k1,  $v1 );
			}
		}
		$this->field( 'hidden', 'arg[uri]', $this->o->gv['uri'] );
		$this->field( 'hidden', 'arp[form]', '1' );
		
		return $this->form_output();
	}
	/* */
	function on_success($ar)
	{
		$ar = $this->check_onoff( $ar );
		
		/* Clear cached objects */
		$this->o->oDb->truncate( 'cached_units' );
		
		/* Redirect */
		$is_delay = 0;
		$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.mnt,t.service,s.clear_cache' );
		$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
		
	}
}








?>