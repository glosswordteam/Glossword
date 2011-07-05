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
			header('Content-type: text/plain; charset=utf-8');
		}

		/* Check or permission first */
		if ( !$this->oSess->is( 'sys-settings' ) )
		{
			print 0;
			return;
		}

		/* Remove item from database */
		$this->oDb->delete( 'blocks', array( 'id_block' => $this->gv['id'] ), 1 );

		print 1;

	break;
}

?>