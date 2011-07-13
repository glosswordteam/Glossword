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
switch( $this->gv['sef_output'] )
{
	case 'ajax':

		if ( $this->V->is_send_headers )
		{
			header( 'Content-type: text/plain; charset=utf-8' );
		}

		/* Check for permission first */
		if ( !$this->oSess->is( 'sys-settings' ) )
		{
			print 0;
			return;
		}

		/* Remove language from database */
		$this->oDb->delete( 'languages', array( 'id_lang' => $this->gv['id_lang'] ), 1 );

		print 1;

	break;
	default:

	break;
}

?>