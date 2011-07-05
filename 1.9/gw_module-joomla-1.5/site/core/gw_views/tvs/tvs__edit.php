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
		if ( trim( $this->gv['tv_value'] ) == '' )
		{
			$q__tv['is_active'] = TKIT_STATUS_OFF;
			$q__tv['is_complete'] = 0;
		}
		else
		{
			$q__tv['is_active'] = TKIT_STATUS_APPROVED;
			$q__tv['is_complete'] = 1;
		}

		$q__tv['tv_value'] = $this->gv['tv_value'];
		$q__tv['id_user_modified'] = $this->oSess->id_user;
		$q__tv['id_user_created'] = $this->oSess->id_user;
		$q__tv['mdate'] = $this->V->datetime_gmt;
		$q__tv['id_pid'] = $this->gv['id_pid'];
		$q__tv['id_lang'] = $this->gv['id_lang'];
		/* Length */
		$q__tv['cnt_bytes'] = strlen( $q__tv['tv_value'] );
		/* Count the number of words, approx. */
		$q__tv['cnt_words'] = sizeof( explode(' ', strip_tags( $q__tv['tv_value'] ) ) );
		
		if ( strpos( $this->gv['id_tv'], 'null' ) !== false )
		{
			/* New Translation Variant ID */
			$this->oDb->select_max( 'id_tv', 'id' );
			$this->oDb->from( 'tv' );
			$ar_sql = $this->oDb->get()->result_array();
			$id_tv_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;
			$q__tv['id_tv'] = $id_tv_max;
			$q__tv['cdate'] = $this->V->datetime_gmt;
			
			if ( $this->oDb->insert( 'tv', $q__tv ) )
			{
				print json_encode( array( 'responseStatus' => '200', 'id_tv' =>  $q__tv['id_tv'] ) );
			}
		}
		else
		{
			/* Update */
			if ( $this->oDb->update( 'tv', $q__tv, array( 'id_pid' => $this->gv['id_pid'], 'id_lang' => $this->gv['id_lang'] ) ))
			{
				print json_encode( array( 'responseStatus' => '200' ) );
			}
		}
	break;
}


?>