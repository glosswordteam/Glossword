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

/* Language ID */
$id_lang = isset( $ar['id_lang'] ) ? $ar['id_lang'] : 0;

/* Array with source data */
$ar_lines_raw = array();

/* SQL */
$q__languages = array();
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.import'."\x01\x01".'t.tvs' );

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
				if ( ( $ar['format'] == TKIT_INPUT_FMT_GWXML && $_FILES['arp']['type']['localfile'] != 'text/xml' )
					|| ( $ar['format'] == TKIT_INPUT_FMT_PHP1 && $_FILES['arp']['type']['localfile'] != 'application/x-httpd-php' )
					)
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
		$oHttp->cookies['gw_target'] = 'tvs';
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
if ( $ar['format'] == TKIT_INPUT_FMT_GWXML )
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
			$this->o->oTkit->_( 1095 ).'<br />'.$str_error , $href_redirect, GW_COLOR_FALSE
		));
		return;
	}
}


/* Empty string */
if ( $ar['input'] == '' )
{
	$this->o->oOutput->append_html( $this->o->soft_redirect(
		$this->o->oTkit->_( 1095 ), $href_redirect, GW_COLOR_FALSE
	));
	return;
}

$ar_id_pids = $ar_sorted = array();

/* Switch between formats */
switch ( $ar['format'] )
{
	case TKIT_INPUT_FMT_GWXML:
		/* xml: <tu><tv xml:lang="iso639-3"></tv></tu> */

		$oXml = new SimpleXMLElement( $ar['input'] );
		$ar['input'] = '';
		$cnt = 0;

		$ar_settings['int_items_total'] = sizeof( $oXml->tu );
		/* For each <entry> */
		foreach ( $oXml->tu as $tu )
		{
			$pid = (string) $tu['id'];
			$ar_id_pids[hash( 'md5', $pid )] = $pid;

			foreach ( $tu->children() as $o_child )
			{
				/* Detect Language ID */
				$xml_lang = '';
				/* Use namespace to retrieve :xml attributes */
				foreach( $o_child->attributes( 'http://www.w3.org/XML/1998/namespace' ) as $attr_k => $attr_v )
				{
					if ( $attr_k == 'lang' )
					{
						$attr_v = (string) $attr_v;
						$attr_v = str_replace( '-', '_', $attr_v ); /* en-US => en_US */
						$id_lang = $xml_lang = isset( $this->o->ar_languages_locale[$attr_v] ) ? $this->o->ar_languages_locale[$attr_v] : '';
					}
				}
				/* 1.9.2: Only installed languages */
				if ( $xml_lang )
				{
					$ar_sorted[$pid][$id_lang]['tv_value'] = (string) $o_child;
				}
			}
		}
	break;
	case TKIT_INPUT_FMT_PHP1:
		/* php: $array['PhraseID'] = 'Value'; */

		$ar_data = explode( CRLF, $ar['input'] );

		$pid = '';
		foreach( $ar_data as $k_line => $v_line )
		{
			$v_line = trim( $v_line );

			/* Strip PHP-tags */
			$v_line = preg_replace( "/^<\?"."php/", '', $v_line );
			$v_line = preg_replace( "/\?".">$/", '', $v_line );

			/* Strip comments */
			$v_line = preg_replace( "/(^| )+#(.*?[^ ])$/", '', $v_line );
			$v_line = preg_replace( "/\/\/(.*?)$/", '', $v_line );

			/* Remove quotes */
			$v_line = str_replace( '\\', '\\\\', $v_line );
			$v_line = stripslashes( $v_line );

			/* */
			$v_line = trim( $v_line );
			preg_match_all( "/^[$]+(.*?)\[[\"\']?(.*?)[\"\']?\]( )?=( )?['\"](.*)/", $v_line, $ar_match, PREG_PATTERN_ORDER );

			foreach( $ar_match as $k_match => $v_match )
			{
				if (isset($v_match[0]))
				{
					$pid = $ar_match[2][0];
					/* Phrase ID already exists - skip */
					if ( isset( $ar_sorted[$pid] ) )
					{
						break;
					}
					$ar_id_pids[hash( 'md5', $pid )] = $pid;
					/* remove "; or '; from the end of line */
					$ar_match[5][0] = preg_replace("/['\"];$/", '', $ar_match[5][0]);
					$ar_sorted[$pid][$ar['id_lang']]['tv_value'] = $ar_match[5][0];
				}
			}
			/* No matches */
			if ( empty( $ar_match[0] ) )
			{
				/* Try to find the end of a previous string */
				preg_match_all( "/(.*)['\"];$/", $v_line, $ar_match2, PREG_PATTERN_ORDER );
				foreach ($ar_match2 as $k_match2 => $v_match2 )
				{
					/* Fix for undefined `tv_value` */
					if ( isset( $v_match2[0] ) && isset( $ar_sorted[$pid][$ar['id_lang']]['tv_value'] ) )
					{
						$ar_sorted[$pid][$ar['id_lang']]['tv_value'] .= CRLF.$ar_match2[1][0];
						break;
					}
				}
				/* No matches second time, add to the end by default */
				if ( empty( $ar_match2[0] ) && isset( $ar_sorted[$pid][$ar['id_lang']]['tv_value'] ) && $v_line != '' )
				{
					$ar_sorted[$pid][$ar['id_lang']]['tv_value'] .= CRLF.$v_line;
				}
			}
			/* */
			#prn_r( $ar_match );
		}
		$ar_settings['int_items_total'] = sizeof( $ar_sorted );
	break;
}

/* */
$cnt_tv_added = $cnt_tv_updated = 0;

/* Check for an existent Phrase IDs */
$this->o->oDb->select( 'p.id_pid' );
$this->o->oDb->from( array('pid p') );
$this->o->oDb->where_in( 'id_pid', array_keys( $ar_id_pids )  );
$ar_sql = $this->o->oDb->get()->result_array();

/**
 * Update Phrase IDs
 */
foreach( $ar_sql as $k => $ar_v)
{
	$pid_value = $ar_id_pids[$ar_v['id_pid']];
	/**
	 * Update Translation variants
	 */
	foreach( $ar_sorted[$pid_value] as $id_lang => $ar_tv )
	{
		/* 1.9.4: Always string */
		$id_lang = (string) $id_lang;

		$q__tv['is_active'] = TKIT_STATUS_APPROVED;
		$q__tv['is_complete'] = 1;
		$q__tv['tv_value'] = $ar_tv['tv_value'];
		$q__tv['id_user_modified'] = $this->o->oSess->id_user;
		$q__tv['id_user_created'] = $this->o->oSess->id_user;
		$q__tv['mdate'] = $this->o->V->datetime_gmt;
		$q__tv['id_pid'] = $ar_v['id_pid'];
		$q__tv['id_lang'] = $id_lang;
		/* Length */
		$q__tv['cnt_bytes'] = strlen( $ar_tv['tv_value'] );
		/* Count the number of words, approx. */
		$q__tv['cnt_words'] = sizeof( explode(' ', strip_tags( $q__tv['tv_value'] ) ) );


		if ( trim( $q__tv['tv_value'] ) == '' )
		{
			/* Remove Phrase ID and all Translation Variants */
			$this->o->oDb->delete( 'tv', array( 'id_pid' => $ar_v['id_pid'] ) );
			$this->o->oDb->delete( 'pid', array( 'id_pid' => $ar_v['id_pid'] ) );
		}
		else
		{
			/* */
			$this->o->oDb->delete( 'tv', array( 'id_pid' => $ar_v['id_pid'], 'id_lang' => $id_lang ) );

			/* INSERT */
			if ( $this->o->oDb->insert( 'tv', $q__tv ) )
			{
				++$ar_settings['int_items_passed'];
				++$cnt_tv_updated;
			}
		}
	}
	/* Exclude existent Phrase IDs */
	unset( $ar_sorted[$pid_value] );
	unset( $ar_id_pids[$ar_v['id_pid']] );
}
$q__tv = $q__pid = array();



/**
 * Insert Phrase IDs and Translation variants
 */

/* Select MAX Translation Variant ID */
$this->o->oDb->select_max( 'id_tv', 'id' );
$this->o->oDb->from( 'tv' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_tv_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* For each Phrase ID */
foreach( $ar_id_pids as $id_pid => $pid_value )
{
	$q__pid['id_pid'] = $id_pid;
	$q__pid['pid_value'] = $pid_value;
	$q__pid['cdate'] = $q__pid['mdate'] = $this->o->V->datetime_gmt;
	/* INSERT */
	$this->o->oDb->insert( 'pid', $q__pid );

	/**
	 * Create new Translation variants
	 */
	/* Insert new values */
	foreach ( $ar_sorted[$pid_value] as $id_lang => $ar_tv )
	{
		$q__tv[$id_lang]['id_tv'] = $id_tv_max;
		$q__tv[$id_lang]['cdate'] = $q__tv[$id_lang]['mdate'] = $this->o->V->datetime_gmt;
		$q__tv[$id_lang]['id_pid'] = $q__pid['id_pid'];
		$q__tv[$id_lang]['id_user_created'] = $this->o->oSess->id_user;
		$q__tv[$id_lang]['id_user_modified'] = $this->o->oSess->id_user;
		/* Approved and translated */
		$q__tv[$id_lang]['is_active'] = TKIT_STATUS_APPROVED;
		$q__tv[$id_lang]['is_complete'] = 1;
		$q__tv[$id_lang]['id_lang'] = $id_lang;
		$q__tv[$id_lang]['tv_value'] = $ar_tv['tv_value'];
		/* Length */
		$q__tv[$id_lang]['cnt_bytes'] = strlen( $ar_tv['tv_value'] );
		/* Count the number of words, approx. */
		$q__tv[$id_lang]['cnt_words'] = sizeof( explode(' ', strip_tags( $ar_tv['tv_value'] ) ) );

		++$id_tv_max;
	}
	/* Multiple INSERT */
	if ( $this->o->oDb->insert( 'tv', $q__tv ) )
	{
		$ar_settings['int_items_passed'] += sizeof( $q__tv );
		$cnt_tv_added += sizeof( $q__tv );
	}
}


/* Items passed */
#	++$ar_settings['int_items_passed'];

/* Items left */
#	$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];


$str_report = '<table class="tbl-list" width="75%"><tbody>
	<tr><td style="width:75%">'.$this->o->oTkit->_( 1093 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_total'] ).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1092 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_passed'] ).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1091 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_left'] ).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1090 ).':</td><td class="b">'.$this->o->oTkit->number_format( $oTimer->end(), 5 ).'</td></tr>
	</table>';

/* Place progress bar */
$this->o->oOutput->append_html( $this->o->oFunc->text_progressbar( 100, '#FFF', '#6e9a18' ) );

/* Display report */
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.tvs' );
$this->o->oOutput->append_html( $this->o->soft_redirect(
	$str_report.'<p class="color-black">'.$this->o->oTkit->_( 1094 ).'</p>', $href_redirect, GW_COLOR_TRUE
));

/* */
$is_redirect = 0;

?>