<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


/* Switch between output modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
		
	break;
	default:
		
		/**
		 * ----------------------------------------------
		 * Document title and <H1>
		 * ----------------------------------------------
		 */
		$this->oOutput->append_html_title( $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1042 ) );
		$this->oTpl->assign( 'v:h1', $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1042 ) );

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
		
		/**
		 * ----------------------------------------------
		 * Select Infoblock
		 * ----------------------------------------------
		 */
		$this->oDb->select( 'b.*' );
		$this->oDb->from( array( 'blocks b' ) );
		/* User may not exist */
		$this->oDb->where( array( 'b.id_block' => $this->gv['id'] ) );
		$this->oDb->limit( 1 );
		$ar_sql = $this->oDb->get()->result_array();
		$arVF = isset( $ar_sql[0] ) ? $ar_sql[0] : array();

		if ( empty( $ar_sql ) )
		{
			/* No such block */
			$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1068 ).'</div>' );
			return;
		}
		
		/* Correct unknown settings */
		$ar_settings = array();
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
		
	break;
}


?>