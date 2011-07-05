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
		
	break;
	default:
		
		/**
		 * ----------------------------------------------
		 * Document title and <H1>
		 * ----------------------------------------------
		 */
		$this->oOutput->append_html_title( $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1001 ) );
		$this->oTpl->assign( 'v:h1', $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1001 ) );

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
		$ar_default_settings = array( 'is_active' => 1, 'lang_name' => '', 'lang_native' => '',
			'region' => 'ZZ', 'isocode1' => '', 'isocode3' => '', 'direction' => 'ltr',
			'thousands_separator' => ',', 'decimal_separator' => '.', 
			'month_short' => $this->oTkit->ar_ls['month_short'],
			'month_long' => $this->oTkit->ar_ls['month_long'],
			'month_decl' => $this->oTkit->ar_ls['month_decl'], 
			'day_of_week' => $this->oTkit->ar_ls['day_of_week'], 
			'byte_units' => $this->oTkit->ar_ls['byte_units'] 
		);

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