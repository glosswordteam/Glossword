<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
if (!defined('IS_IN_SITE')){die();}

/* Generate Language ID */
$ar['id_lang'] = $this->o->oFunc->get_crc_u( $ar['isocode3'].$ar['region'] );

/* */
foreach ( array( 'byte_units', 'day_of_week', 'month_decl', 'month_long', 'month_short' ) as $v )
{
	$ar[$v] = str_replace( $this->o->V->txt_magic_splitter, ' ', str_replace( ' ', '_', implode( $this->o->V->txt_magic_splitter, $ar[$v] ) ) );
}
unset( $ar['form'] );

/* UPDATE */
$this->o->oDb->update( 'languages', $ar, array( 'id_lang' => $this->o->gv['id'] ) );

$is_redirect = 1;

?>