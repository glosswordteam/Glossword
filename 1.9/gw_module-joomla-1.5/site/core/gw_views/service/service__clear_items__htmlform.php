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
		$cnt_items = $this->o->oDb->count_all( 'items' );
		
		$ar_tables = $this->o->oDb->show_table_status();
		$cnt_bytes = 0;
		foreach ( $ar_tables as $k => $ar_v )
		{
			if ( $ar_v['Name'] == $this->o->V->table_prefix.'items'
				|| $ar_v['Name'] == $this->o->V->table_prefix.'items_uri'  
				|| $ar_v['Name'] == $this->o->V->table_prefix.'contents'  
				|| $ar_v['Name'] == $this->o->V->table_prefix.'contents_si' 
				)
			{
				$cnt_bytes += $ar_v['Data_length'];
			}
		}

		if (  $cnt_items == 0 )
		{
			$this->is_submit_ok = 0;
		}
		else
		{
			$this->o->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error">' );
			$this->o->oOutput->append_html( $this->o->oTkit->_( 1131 ) );
			$this->o->oOutput->append_html( '</div>' );
		}
		
		/* */
		$this->new_fieldset( 'clear_cache', $this->o->oTkit->_( 1107 ) );

		$this->new_label( '', $this->o->oTkit->_( 1036 ).': <strong>'. $this->o->oTkit->number_format( $cnt_items ).'</strong>' );
		$this->new_label( '', $this->o->oTkit->_( 1129 ).': <strong>'. $this->o->oTkit->bytes( $cnt_bytes ).'</strong>' );
		
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
		
		/* Clear tables */
		$this->o->oDb->truncate( 'items' );
		$this->o->oDb->truncate( 'items_uri' );
		$this->o->oDb->truncate( 'items_tmp' );
		$this->o->oDb->truncate( 'contents' );
		$this->o->oDb->truncate( 'contents_si' );
		$this->o->oDb->truncate( 'cached_units' );
		$this->o->oDb->truncate( 'map_item_to_tag' );
		

		/* Redirect */
		$is_delay = 0;
		$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.mnt'."\x01\x01".'t.service'."\x01\x01".'s.clear_items' );
		$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
		
	}
}








?>