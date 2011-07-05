<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/**
 * ----------------------------------------------
 * Select Infoblocks
 * ----------------------------------------------
 */

$this->o->oDb->select( 'b.*' );
$this->o->oDb->from( array( 'blocks b' ) );
$this->o->oDb->order_by( 'b.block_place ASC, b.block_name ASC' );
$ar_sql = $this->o->oDb->get()->result_array();

$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
$xml .= CRLF.'<glossword version="'.$this->o->V->version.'">';

foreach ( $ar_sql as $ar_v )
{
	$xml .= CRLF . '<infoblock id="'.$ar_v['id_block'].'">';
	unset( $ar_v['block_cdate'], $ar_v['block_mdate'], $ar_v['id_block'] );
	
	#$xml .= CRLF . "\t". '<parameters><![CDATA['. serialize($ar_v) .']]></parameters>';

	foreach ( $ar_v as $attr_k => $attr_v )
	{
		$xml .= CRLF . "\t<". $attr_k.'>';
		$xml .= ($attr_v == '') ? '' : '<![CDATA['.$attr_v.']]>';
		$xml .= '</'. $attr_k.'>';
	}
	$xml .= CRLF . '</infoblock>';
}
$xml .= CRLF . '</glossword>';

/* */
$filename = 'gw_infoblocks_'.@date( "Y-m[M]-d", $this->o->V->time_gmt ).'.xml';
$filename_abs = $this->o->V->path_temp_abs .'/'. $this->o->V->path_export .'/'.$filename;
$filename_rel = $this->o->V->server_dir.'/'.$this->o->V->path_temp_web.'/'.$this->o->V->path_export.'/'.$filename;

$filesize = strlen( $xml );

if ( $ar['is_save'] )
{
	/* Send headers */
	if ( isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') )
	{
		header( 'Content-Type: application/force-download' );
	}
	else
	{
		header( 'Content-Type: application/octet-stream' );
	}
	header( 'Content-Length: '. $filesize );
	header( 'Content-disposition: attachment; filename="'.$filename.'"' );
	print $xml;
	exit;
}
else
{
	
	/* */
	$this->o->oOutput->append_html( '<div class="'.GW_COLOR_TRUE.' nostatus" id="status"><ul class="xt">' );
	$this->o->oOutput->append_html( '<li><span class="gray"><a href="'. $filename_rel .'?r='.$this->o->V->time_gmt.'">'. $filename.'</a>…</span> ' );

	/* Write file */
	$is_write = $this->o->oFunc->file_put_contents( $filename_abs, $xml, 'w' );
	$this->o->oOutput->append_html( ( $is_write ) ? $this->o->oTkit->bytes( $filesize ) : $this->o->oTkit->_(1083, $filename) );
		
	$this->o->oOutput->append_html( '</li>' );
	$this->o->oOutput->append_html( '</ul></div>' );

	/* */
	$is_redirect = 0;
}


?>