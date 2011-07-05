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
		if ( isset( $this->gv['sts'] ) )
		{
			$q__langs['is_default'] = $this->gv['sts'];
			if ( $this->oDb->update( 'languages', $q__langs, array( 'id_lang' => $this->gv['id_lang'] ) ))
			{
				print json_encode( array( 'responseStatus' => '200' ) );
			}
		}
	break;
}


?>