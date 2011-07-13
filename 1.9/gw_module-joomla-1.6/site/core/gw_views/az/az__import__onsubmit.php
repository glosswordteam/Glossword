<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


@set_time_limit( 3600 ); /* hour */
$oTimer = new tkit_timer( 'az' );

if ( !isset( $ar['input'] ) ) { $ar['input'] = ''; }

/* Total number of lines converted during import session */
$ar_settings['int_items_passed'] = 0;

/* Total number of lines in the data */
$ar_settings['int_items_total'] = 0;

/* Array with source data */
$ar_lines_raw = array();

/* SQL */
$q__blocks = array();
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.az' );

/* Switch between sources */
switch ( $ar['source'] )
{
	case 'localfile':
		if ( isset( $_FILES['arp'] ) )
		{
			/* Construct a temporary filename */
			$filename_temp = $this->o->V->path_temp_abs.'/'.mktime().'_'.urlencode(basename($_FILES['arp']['name']['localfile']));
			/* Save uploaded file as temporary file */
			if ( move_uploaded_file( $_FILES['arp']['tmp_name']['localfile'], $filename_temp) )
			{
				/* Check file type */
				if ( $_FILES['arp']['type']['localfile'] != 'text/xml' )
				{
					/* Delete a temporary file */
					@unlink( $filename_temp );
					/* Display error message */
					$this->o->oOutput->append_html( $this->o->soft_redirect(
						$this->o->oTkit->_( 1095 ).'<br />'.$_FILES['arp']['type']['localfile'], $href_redirect, GW_COLOR_FALSE
					));
					return;
				}
				/* Read file */
				$ar['input'] = implode( '', file( $filename_temp ) );
				/* Delete a temporary file */
				@unlink( $filename_temp );
			}
		}
	break;
	case 'remotefile';
		$oHttp = $this->o->_init_http();
		$oHttp->cookies['gw_format'] = $ar['format'];
		$oHttp->cookies['gw_target'] = $this->o->gv['target'];
		$oHttp->fetch( $ar['remotefile'] );
		if ( $oHttp->status == 200 && $oHttp->results != '' )
		{
			$ar['input'] = $oHttp->results;
		}
		else
		{
			/* Display error message */
			$this->o->oOutput->append_html( $this->o->soft_redirect(
				$this->o->oTkit->_( 1095 ).'<br />'. $ar['remotefile'].'<br />'.$oHttp->status, $href_redirect, GW_COLOR_FALSE
			));
			return;
		}
	break;
}

/* Check XML */
if ( $ar['format'] == GW_INPUT_FMT_GWXML )
{
	/* No XML-class */
	if ( !class_exists( 'SimpleXMLElement' ) )
	{
		$this->o->oOutput->append_html( $this->o->soft_redirect(
			$this->o->oTkit->_( 1095 ).'<br />SimpleXMLElement', $href_redirect, GW_COLOR_FALSE
		));
		return;
	}
	/* Validate XML */
	libxml_use_internal_errors( true );
	$oXml = simplexml_load_string( $ar['input'] );
	if ( !$oXml )
	{
		$str_error = '';
		foreach( libxml_get_errors() as $error )
		{
			$str_error .= '<br />'.$error->message;
		}
		/* Display error message */
		$this->o->oOutput->append_html( $this->o->soft_redirect(
			$this->o->oTkit->_( 1095 ).'<br />'.$str_error, $href_redirect, GW_COLOR_FALSE
		));
		return;
	}
}

/* Check empty string */
if ( $ar['input'] == '' )
{
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		$this->o->oTkit->_( 1095 ), $href_redirect, GW_COLOR_FALSE
	));
	return;
}


/* */
$oXml = new SimpleXMLElement( $ar['input'] );
$xml_file_version = (string) $oXml['version'];

$ar_settings['int_items_total'] = sizeof( $oXml->custom_az );


/* Detect Language ID */
$xml_lang = '';
/* Use namespace to retrieve :xml attributes */
foreach( $oXml->custom_az->attributes( 'http://www.w3.org/XML/1998/namespace' ) as $attr_k => $attr_v )
{
	if ( $attr_k == 'lang' )
	{
		$attr_v = (string) $attr_v;
		$attr_v = str_replace( '-', '_', $attr_v ); /* en-US => en_US */
		$xml_lang = isset( $this->o->ar_languages_locale[$attr_v] ) ? $this->o->ar_languages_locale[$attr_v] : '';
	}
}
if ( $xml_lang == '' )
{
	/* Cannot detect target language */
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		$this->o->oTkit->_( 1095 ).'<br />xml:lang is incorrect', $href_redirect, GW_COLOR_FALSE
	));
	return;
}
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.az'."\x01\x01".'id_lang.'.$xml_lang );

$q__az_letters = array();
$cnt = 1;
/* for each <custom_az> */
foreach ( $oXml->custom_az as $oCustomAz )
{
	/* for each <entry> */
	foreach( $oCustomAz->entry as $oEntry )
	{
		$q__az_letters[$cnt]['id_lang'] = $xml_lang;
		if ( version_compare( $xml_file_version, '1.9.3', '<' ) )
		{
			/* Read Alphabetic Orders from 1.8.6+ */
			$q__az_letters[$cnt]['uc'] = (string) $oEntry->az_value;
			$q__az_letters[$cnt]['lc'] = (string) $oEntry->az_value_lc;
			$q__az_letters[$cnt]['int_sort'] = (int) $oEntry->int_sort;
		}
		else
		{
			/* for each tag inside <entry> */
			foreach( $oEntry->children() as $oChild )
			{
				$tag_name = $oChild->getName();
				$q__az_letters[$cnt][$tag_name] = (string) $oChild;
			}
		}
		$q__az_letters[$cnt]['uc_crc32u'] = sprintf( "%u", crc32( $q__az_letters[$cnt]['uc'] ) );
		$q__az_letters[$cnt]['uc'] = urlencode( $q__az_letters[$cnt]['uc'] );
		$q__az_letters[$cnt]['lc'] = urlencode( $q__az_letters[$cnt]['lc'] );
		++$cnt;
	}
	/* Items are passed */	
	++$ar_settings['int_items_passed'];
	
	/* Items are left */
	$int_items_left = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];
} 

/* DELETE existent rules for this language */
$this->o->oDb->delete( 'az_letters', array( 'id_lang' => $xml_lang ) );

/* INSERT */
$this->o->oDb->insert( 'az_letters', $q__az_letters );

$str_report = '<table class="tbl-list" width="75%"><tbody>
	<tr><td style="width:75%">'.$this->o->oTkit->_( 1093 ).':</td><td class="b">'.$this->o->oTkit->number_format($ar_settings['int_items_total']).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1092 ).':</td><td class="b">'.$this->o->oTkit->number_format($ar_settings['int_items_passed']).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1091 ).':</td><td class="b">'.$this->o->oTkit->number_format($int_items_left).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1090 ).':</td><td class="b">'.$this->o->oTkit->number_format($oTimer->end(), 5).'</td></tr>
	</table>';

/* Place progress bar */
$this->o->oOutput->append_html( $this->o->oFunc->text_progressbar( 100, '#FFF', '#6e9a18' ) );

/* Display report */
$this->o->oOutput->append_html( $this->o->soft_redirect(
	$str_report.'<p class="color-black">'.$this->o->oTkit->_( 1094 ).'</p>', $href_redirect, GW_COLOR_TRUE
));

/* */
$is_redirect = 0;

?>