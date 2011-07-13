<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
if (!defined('IS_IN_SITE')){die();}


@set_time_limit(3600); /* hour */
$oTimer = new tkit_timer();

if ( !isset( $ar['input'] ) ) { $ar['input'] = ''; }

/* Total number of lines converted during import session */
$ar_settings['int_items_passed'] = 0;

/* Total number of lines in the data */
$ar_settings['int_items_total'] = $ar_settings['int_items_left'] = 0;

/* Array with source data */
$ar_lines_raw = array();

/* SQL */
$q__languages = array();
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.langs' );

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

/* No class */
if ( !class_exists('SimpleXMLElement') )
{
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		$this->o->oTkit->_( 1095 ).'<br />SimpleXMLElement', $href_redirect, GW_COLOR_FALSE
	));
	return;
}
/* Empty string */
if ( $ar['input'] == '' )
{
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		$this->o->oTkit->_( 1095 ), $href_redirect, GW_COLOR_FALSE
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
		$this->o->oTkit->_( 1095 ).'<br />'.$str_error , $href_redirect, GW_COLOR_FALSE
	));
	return;
}
	
/* */
$oXml = new SimpleXMLElement( $ar['input'] );

$ar_settings['int_items_total'] = sizeof( $oXml->language );

/* for each <language> */
foreach ( $oXml->language as $lang )
{
	/* interger unsigned needed (4 294 967 294) */
	$id_lang = (string) $lang['id'];

	foreach ( $lang as $k => $v )
	{
		$q__languages[$id_lang][(string)$k] = (string) $v;
	}
	$q__languages[$id_lang]['is_active'] = 1;
	$q__languages[$id_lang]['is_default'] = 0;
	$q__languages[$id_lang]['id_lang'] = $id_lang;
	
	/* Remove existent languages */
	$this->o->oDb->delete( 'languages', array( 'id_lang' => $id_lang ), 1 );

	/* Items are passed */	
	++$ar_settings['int_items_passed'];
	
	/* Items are left */
	$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];
}

/* INSERT */
$this->o->oDb->insert( 'languages', $q__languages );

$str_report = '<table class="tbl-list" width="75%"><tbody>
	<tr><td style="width:75%">'.$this->o->oTkit->_( 1093 ).':</td><td class="b">'.$this->o->oTkit->number_format($ar_settings['int_items_total']).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1092 ).':</td><td class="b">'.$this->o->oTkit->number_format($ar_settings['int_items_passed']).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1091 ).':</td><td class="b">'.$this->o->oTkit->number_format($ar_settings['int_items_left']).'</td></tr>
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