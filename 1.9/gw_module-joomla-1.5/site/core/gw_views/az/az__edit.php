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
		
		
		/* Move Up or Down */
		if (  $this->gv['move'] == 'up' )
		{
			$this->oDb->update( 'az_letters', array( 'int_sort = `int_sort` - 15' => NULL ), array( 'id_letter' => $this->gv['id_letter'] ) );
		}
		else if (  $this->gv['move'] == 'down' )
		{
			$this->oDb->update( 'az_letters', array( 'int_sort = `int_sort` + 15' => NULL ), array( 'id_letter' => $this->gv['id_letter'] ) );
		}
	
		/* Resort */
		$this->oDb->select( "id_letter, uc, lc" );
		$this->oDb->from( "az_letters" );
		$this->oDb->where( array( 'id_lang' => $this->gv['id_lang'] ) );
		$this->oDb->order_by( "int_sort ASC" );
		$ar_sql = $this->oDb->get()->result_array();
		$int_sort = 10;
		$ar = array();
		foreach( $ar_sql as $ar_v )
		{
			/* Collect for JSON */
			$ar[] = array( $ar_v['id_letter'], urldecode( $ar_v['uc'] ), urldecode( $ar_v['lc'] ) );
			/* Update sorting order */
			$this->oDb->update( 'az_letters', array( 'int_sort' => $int_sort ), array( 'id_letter' => $ar_v['id_letter'] ) );
			$int_sort += 10;
		}

		print json_encode( array( 'responseStatus' => '200', 'ar' => $ar ) );

	break;

}

?>