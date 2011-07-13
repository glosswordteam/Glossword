<?php
/**
 * $Id$
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
			print json_encode( array( 'responseStatus' => '403' ) );
			return;
		}

		/* Remove letter from database */
		$this->oDb->delete( 'az_letters', array( 'id_letter' => $this->gv['id_letter'] ), 1 );

		print json_encode( array( 'responseStatus' => '200' ) );
		return;

	break;
	default:

	break;
}

?>