<?php
/**
 * $Id$
 */

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );


/* Switch between output modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
		if ( !$this->oSess->is( 'sys-settings') )
		{
			print json_encode( array( 'responseStatus' => '403' ) );
			return;
		}
		
		/* Select MAX Letter ID */
		$this->oDb->select_max( 'id_letter', 'id' );
		$this->oDb->from( 'az_letters' );
		$ar_sql = $this->oDb->get()->result_array();
		$id_letter_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

		/* */
		$q__az_letters = array(
			'id_letter' => $id_letter_max,
			'id_lang' => $this->gv['id_lang'],
			'uc' => urlencode( $this->gv['uc'] ),
			'lc' => urlencode( $this->gv['lc'] ),
			'uc_crc32u' => sprintf( "%u", crc32($this->gv['uc']) ),
			'int_sort' => 10000, /* Always last */
		);
		/* INSERT */
		$this->oDb->insert( 'az_letters', $q__az_letters );
		
		/* Resort */
		$this->oDb->select( "id_letter" );
		$this->oDb->from( "az_letters" );
		$this->oDb->where( array( 'id_lang' => $this->gv['id_lang'] ) );
		$this->oDb->order_by( "int_sort ASC" );
		$ar_sql = $this->oDb->get()->result_array();
		$int_sort = 10;
		foreach( $ar_sql as $ar_v )
		{
			$this->oDb->update( 'az_letters', array( 'int_sort' => $int_sort ), array( 'id_letter' => $ar_v['id_letter'] ) );
			$int_sort += 10;
		}

		print json_encode( array( 'responseStatus' => '200', 'id_letter' => $id_letter_max ) );

	break;
	default:
		
		/**
		 * ----------------------------------------------
		 * Document title and <H1>
		 * ----------------------------------------------
		 */
		$this->oOutput->append_html_title( $this->oTkit->_( 1209 ).': '.$this->oTkit->_( 1001 ) );
		$this->oTpl->assign( 'v:h1', $this->oTkit->_( 1209 ).': '.$this->oTkit->_( 1001 ) );

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
		
		/* Default values */
		$arVF = array();

		/* Correct unknown settings */
		$ar_default_settings = array();

		/* Checkboxes */
		$ar_onoff = array( 'is_active' );

		/* Required fields */
		$ar_required = array( 'id_lang' );

		/* */
		$oHtmlForms = new site_htmlforms( $this );
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
		
	break;
}

?>