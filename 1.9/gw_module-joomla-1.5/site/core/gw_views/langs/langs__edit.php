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


/* Switch between output modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
		if ( !$this->oSess->is( 'sys-settings') )
		{
			print json_encode( array( 'responseStatus' => '403' ) );
			return;
		}
		/* */
		if ( isset($this->gv['sts']) )
		{
			$q__langs['is_active'] = $this->gv['sts'];
			if ( $this->oDb->update( 'languages', $q__langs, array( 'id_lang' => $this->gv['id_lang'] ) ))
			{
				print json_encode( array( 'responseStatus' => '200' ) );
			}
		}
		if ( isset($this->gv['is_default']) )
		{
			if ( $this->oDb->update( 'languages', array( 'is_default' => '0' ) ) 
				&& $this->oDb->update( 'languages', array( 'is_default' => '1' ), array( 'id_lang' => $this->gv['id_lang'] ) ))
			{
				print json_encode( array( 'responseStatus' => '200' ) );
			}
		}
	break;
	default:
		
		/**
		 * ----------------------------------------------
		 * Document title and <H1>
		 * ----------------------------------------------
		 */
		$this->oOutput->append_html_title( $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1042 ) );
		$this->oTpl->assign( 'v:h1', $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1042 ) );

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
		 * Select Language settings
		 * ----------------------------------------------
		 */
		$this->oDb->select('l.*', false);
		$this->oDb->from( array('languages l' ) );
		$this->oDb->where(array('l.id_lang' => $this->gv['id']));
		$this->oDb->limit(1);
		$ar_sql = $this->oDb->get()->result_array();
		$arVF = isset( $ar_sql[0] ) ? $ar_sql[0] : array();

		if ( empty( $ar_sql ) )
		{
			/* No Language */
			$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1068 ).'</div>' );
			return;
		}
		
		/* Correct unknown settings */
		$ar_default_settings = array( 'is_active' => 1 );
		/* Checkboxes */
		$ar_onoff = array( 'is_active' );
		/* Required fields */
		$ar_required = array( 'lang_name', 'lang_native', 'isocode1', 'isocode3', 'region', 'direction' );

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