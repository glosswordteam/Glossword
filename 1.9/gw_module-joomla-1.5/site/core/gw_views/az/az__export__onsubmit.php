<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


if ( !isset( $ar['id_lang_export'] ) )
{
	/* No languages selected */
	$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.export'."\x01\x01".'t.az' );
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		'<p class="color-black">'.$this->o->oTkit->_( 1155 ).'</p>', $href_redirect, GW_COLOR_FALSE
	));
	return;
}

/**
 * ----------------------------------------------
 * Select Languages
 * ----------------------------------------------
 */
$this->o->oDb->select( 'az.id_lang, az.uc, az.lc, az.int_sort, CONCAT( l.isocode1, "_", l.region ) locale' , false );
$this->o->oDb->from( array( 'az_letters az', 'languages l' ) );
$this->o->oDb->where( array( 'az.id_lang = l.id_lang' => NULL, 'az.id_lang' => $ar['id_lang_export'] ) );
$this->o->oDb->order_by( 'az.int_sort ASC' );
$ar_sql = $this->o->oDb->get()->result_array();

$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
$xml .= CRLF.'<glossword version="'.$this->o->V->version.'">';

$cnt = 1;
$xml_lang = '';
foreach ( $ar_sql as $ar_v )
{
	if ( $cnt == 1 )
	{ 
		$xml_lang = $ar_v['locale'];
		$xml .= CRLF . '<custom_az xml:lang="'. $xml_lang .'">'; 
	}
	$xml .= CRLF . "\t".'<entry>';
	
	unset( $ar_v['id_lang'], $ar_v['locale'] );
	foreach ( $ar_v as $attr_k => $attr_v )
	{
		$xml .= " <". $attr_k.'>';
		$xml .= ($attr_v == '') ? '' : '<![CDATA['.$attr_v.']]>';
		$xml .= '</'. $attr_k.'>';
	}
	
	$xml .= '</entry>';
	++$cnt;
}
$xml .= CRLF . '</custom_az>';
$xml .= CRLF . '</glossword>';

/* */
$filename = 'gw-'.$this->o->V->version.'-custom_az-'. $xml_lang .'.xml';
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