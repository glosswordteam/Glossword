<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
if (!defined('IS_IN_SITE')){die();}


if ( !isset( $ar['id_lang_export'] ) )
{
	/* No languages selected */
	$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.export,t.langs' );
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		'<p class="color-black">'.$this->o->oTkit->_( 1204 ).'</p>', $href_redirect, GW_COLOR_FALSE
	));
	return;
}


/**
 * ----------------------------------------------
 * Select Translation Variants
 * ----------------------------------------------
 */
$this->o->oDb->select( 'l.region, l.isocode1, tv.tv_value, p.pid_value, CONCAT(l.lang_name, " - ", l.lang_native) as lang', false  );
$this->o->oDb->from( array( 'languages l', 'pid p', 'tv tv' ) );
$this->o->oDb->where( array( 'p.id_pid = tv.id_pid' => NULL ) );
$this->o->oDb->where( array( 'l.id_lang = tv.id_lang' => NULL ) );
$this->o->oDb->where( array( 'l.id_lang' => $ar['id_lang_export'] ) );
$this->o->oDb->group_by( 'p.id_pid' );
$this->o->oDb->order_by( 'p.cdate DESC' );
$ar_sql = $this->o->oDb->get()->result_array();


$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
$xml .= CRLF.'<glossword version="'.$this->o->V->version.'">';
$xml .= CRLF.'<!-- Translation Variants for Glossword 1.9.3+ http://code.google.com/p/glossword/ -->';

switch ( $this->o->oSess->user_get('displayed_name') )
{
	case 1: $id_user_str = $this->o->oSess->user_get('user_nickname'); break;
	case 2: $id_user_str = $this->o->oSess->user_get('user_fname'); break;
	case 3: $id_user_str = $this->o->oSess->user_get('user_sname'); break;
	case 4: $id_user_str= $this->o->oSess->user_get('user_fname').' '.$this->o->oSess->user_get('user_sname'); break;
	default: $id_user_str = 'User ID '.$this->o->oSess->id_user; break;
}
$xml .= CRLF.'<!-- Exported by '. $id_user_str .' on '. @date( "M d, Y H:i", $this->o->V->time_gmt) .' UTC -->';

$lang_name  = $lang_code = '';
foreach ( $ar_sql as $k => $ar_v )
{
	if ( $k == 0 )
	{
		$lang_code = $ar_v['isocode1'] .'_'. $ar_v['region'];
		$lang_name = $ar_v['lang'];
		$xml .= CRLF.'<!-- '. $ar_v['lang'].' | Phrases: '.sizeof( $ar_sql ).' -->';
	}

	$xml .= CRLF . '<tu id="'.$ar_v['pid_value'].'">';
	$xml .= '<tv xml:lang="'.$lang_code.'">';
	$xml .= '<![CDATA[' . $ar_v['tv_value'] . ']]>';
	$xml .= '</tv>';
	$xml .= '</tu>';
}
$xml .= CRLF . '</glossword>';


/* */
$filename = 'glossword-'.$this->o->V->version.'-translation-'.$lang_code.'.xml';
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