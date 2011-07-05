<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/* Glossword 1.8 fields configuration */
$gw18_ar_id_fields = array(
	'trsp' => false,
	'abbr' => false,
	'trns' => false,
	'usg' => false,
	'address' => false,
	'phone' => false,
	'syn' => false,
	'antonym' => false,
	'see' => 3,
	'src' => false,
);
/* Glossword 1.9 fields configuration */
$gw19_ar_id_fields = array(
	'term' => true,
	'defn' => true,
	'seealso' => true,
);
$gw19_ar_field_cols = array(
	'col_term' => 1,
	'col_defn' => 2,
	'col_seealso' => 3,
);


@set_time_limit(3600); /* hour */
$oTimer = new tkit_timer('items');

if ( !isset( $ar['input'] ) )
{
	$ar['input'] = '';
}

/* Total number of lines converted during import session */
$ar_settings['int_items_passed'] = 0;

/* Total number of lines in the data */
$ar_settings['int_items_total'] = 0;

/* Array with source data */
$ar_lines_raw = array();

/* Redirect after importing */
$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.import,t.items' );

/* Switch between sources */
switch ( $ar['source'] )
{
	case 'localfile':
		if ( isset( $_FILES['arp'] ) )
		{
			/* Construct a temporary filename */
			$filename_temp = $this->o->V->path_temp_abs.'/'.mktime().'_'.urlencode( basename( $_FILES['arp']['name']['localfile'] ) );
			/* Save uploaded file as temporary file */
			if ( move_uploaded_file( $_FILES['arp']['tmp_name']['localfile'], $filename_temp ) )
			{
				/* Check file type */
				if ( ( ($ar['format'] == GW_INPUT_FMT_GWXML || $ar['format'] == GW_INPUT_FMT_XML ) && $_FILES['arp']['type']['localfile'] != 'text/xml')
					|| ($ar['format'] == GW_INPUT_FMT_CSV && $_FILES['arp']['type']['localfile'] != 'text/plain')
					)
				{
					/* Delete a temporary file */
					@unlink( $filename_temp );
					/* Display error message */
					$this->o->oOutput->append_html( $this->o->soft_redirect(
						$this->o->oTkit->_( 1095 ).'<br />'. $this->o->oTkit->_( 1126 ) .': '. $_FILES['arp']['type']['localfile'], $href_redirect, GW_COLOR_FALSE
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
		$oHttp->cookies['gw_target'] = 'items';
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

/* Remove UTF-8 Signature (BOM, Byte Order Mark) */
if ( substr( $ar['input'], 0, 3 ) == sprintf( '%c%c%c', 239, 187, 191 ) )
{
	$ar['input']= substr( $ar['input'], 3 );
}

/* Vaidate XML */
if ( $ar['format'] == GW_INPUT_FMT_GWXML || $ar['format'] == GW_INPUT_FMT_XML )
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
		$this->o->oTkit->_( 1095 ).'<br />'.$this->o->oTkit->_( 1112 ), $href_redirect, GW_COLOR_FALSE
	));
	return;
}

/* Start oGWiki, input -> db */
$oGWiki_db = $this->o->_init_wiki_( 'input', 'db' );
/* Start  oGWiki, input -> html */
$oGWiki_html = $this->o->_init_wiki_( 'db', 'html' );

/* Select MAX Item ID */
$this->o->oDb->select_max( 'id_item', 'id' );
$this->o->oDb->from( 'items' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_item_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* Select MAX Contents ID */
$this->o->oDb->select_max( 'id_contents', 'id' );
$this->o->oDb->from( 'contents' );
$ar_sql = $this->o->oDb->get()->result_array();
$id_contents_max = isset( $ar_sql['0']['id'] ) ? ++$ar_sql['0']['id'] : 1;

/* Language ID */
$id_lang = isset( $ar['id_lang'] ) ? $ar['id_lang'] : $this->o->oFunc->get_crc_u( 'eng'.'US' );

$ar_settings['int_items_total'] = $ar_settings['int_items_left'] = 0;

/* Save user settings - radio-buttons */
foreach ( array( 1 => 'format-1', 2 => 'format-2', 3 => 'format-3', 4 => 'format-4' ) as $k => $v )
{
	$this->o->oSess->user_set( 'items_import__'.$v, ( $ar['format'] == $k ) ? 1 : 0 );
}

$ar_item_ids = array();

/* Switch between formats */
switch ( $ar['format'] )
{
	case GW_INPUT_FMT_GWXML:

		/* Save user settings */
		foreach ( array( 'cmp', 'is_overwrite', 'is_publish' ) as $v )
		{
			if ( $this->o->oSess->id_user != $this->o->oSess->id_guest
				&& (string) $this->o->oSess->user_get( 'items_import__'.$v ) != $ar[$v] )
			{
				$this->o->oSess->user_set( 'items_import__'.$v, $ar[$v] );
			}
		}
		foreach ( array( 'gw19' => 'cmp-1', 'gw18' => 'cmt-2' ) as $k => $v )
		{
			$this->o->oSess->user_set( 'items_import__'.$v, ( $ar['cmp'] == $k ) ? 1 : 0 );
		}

		/* */
		$oXml = new SimpleXMLElement( $ar['input'] );
		$xml_file_version = (string) $oXml['version'];

		$ar['input'] = '';
		$cnt = 0;

		if ( $ar['cmp'] == 'gw19' )
		{
			/* Glossword 1.9 */

			/* for 1.9 only */
			--$id_contents_max;

			$ar_settings['int_items_total'] = sizeof( $oXml->entry );
			/* For each <entry> */
			foreach ( $oXml->entry as $entry )
			{
				/* Unique time for each entry */
				$datetime_gmt = @date( $this->o->sdf, $this->o->V->time_gmt + $id_item_max );

				$q__contents = $q__items = $q__items_uri = $q__contents_si = array();
				/* No <term> found */
				if ( !isset( $entry->term ) )
				{
					continue;
				}
				/* */
				$q__items['id_item'] = $id_item_max;
				$q__items['item_cdate'] = $datetime_gmt;
				$q__items['item_mdate'] = $datetime_gmt;
				$q__items['item_id_user_created'] = $this->o->oSess->id_user;
				$q__items['item_id_user_modified'] = 0;
				$q__items['is_active'] = $ar['is_publish'];
				$q__items['is_complete'] = 1;

				$cnt_defn = 0;
				foreach ( $entry->children() as $o_child )
				{
					$xmltag = $o_child->getName();
					switch ( $xmltag )
					{
						case 'uri':
							$q__items_uri['id_item'] = $id_item_max;
							/* 1.9.3: checkbox */
							if ( $ar['gw19_is_term_uri'] )
							{
								$q__items_uri['item_uri'] = (string) $entry->uri;
							}
							$id_field = 0;
						break;
						case 'term':
							$id_field = 1;
							$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
						break;
						case 'defn':
							++$cnt_defn;
							$id_field = 2;
							$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
						break;
						case 'seealso':
							$id_field = 3;
							$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
						break;
					}
					/* Field is known */
					if ( $id_field  )
					{
						/* Use namespace to retrieve :xml attributes */
						foreach( $o_child->attributes( 'http://www.w3.org/XML/1998/namespace' ) as $attr_k => $attr_v )
						{
							if ( $attr_k == 'lang' )
							{
								$attr_v = (string) $attr_v;
								$attr_v = str_replace( '-', '_', $attr_v ); /* en-US => en_US */
								$id_lang = isset( $this->o->ar_languages_locale[$attr_v] ) ? $this->o->ar_languages_locale[$attr_v] : '';
							}
						}

						$value = trim( (string) $o_child );

						/* Filter Item */
						$item_filtered = $this->o->items__filter( $value );

						/* Construct alphabetic order */
						$q__contents[$id_field][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value, $id_lang );

						$q__contents[$id_field][$cnt_defn]['contents_value'] = $value;
						$q__contents[$id_field][$cnt_defn]['id_item'] = $id_item_max;
						$q__contents[$id_field][$cnt_defn]['id_contents'] = $id_contents_max;
						$q__contents[$id_field][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
						$q__contents[$id_field][$cnt_defn]['id_user_modified'] = 0;
						$q__contents[$id_field][$cnt_defn]['id_lang'] = $id_lang;
						$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
						$q__contents[$id_field][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_field][$cnt_defn]['contents_value'] );
						$q__contents[$id_field][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_field][$cnt_defn]['contents_value'] );

						/* Generate new Item URI */
						if ( $id_field == $this->o->V->id_field_root && !isset( $q__items_uri['item_uri'] ) )
						{
							/* Item URI: "123-term-text" */
							$q__items_uri['item_uri'] = $this->o->items__uri( $item_filtered, $id_item_max );
						}

						/* Detect Language ID */
						$xml_lang = '';
						/* Use namespace to retrieve :xml attributes */
						foreach( $o_child->attributes( 'http://www.w3.org/XML/1998/namespace' ) as $attr_k => $attr_v )
						{
							if ( $attr_k == 'lang' )
							{
								$attr_v = (string) $attr_v;
								$attr_v = str_replace( '-', '_', $attr_v ); /* en-US => en_US */
								$xml_lang = isset( $this->o->ar_languages_locale[$attr_v] ) ? $this->o->ar_languages_locale[$attr_v] : '';
							}
						}
						/* Only installed languages */
						if ( $xml_lang )
						{
							$q__contents[$id_field][$cnt_defn]['id_lang'] = $xml_lang;
						}

						/* Read Alphabetic order 1 */
						if ( $o_child['a1'] && $ar['gw19_is_az'] )
						{
							$q__contents[$id_field][$cnt_defn]['contents_a'] = (string) $o_child['a1'];
						}
						/* Read Alphabetic order 2 */
						if ( $o_child['t2'] && $ar['gw19_is_az'] )
						{
							$q__contents[$id_field][$cnt_defn]['contents_b'] = (string) $o_child['t2'];
						}
						/* 1.9.3: Convert to Binary */
						#$q__contents[$id_field][$cnt_defn]['contents_a'] = $this->oFunc->str_binary( $q__contents[$id_field][$cnt_defn]['contents_a'], 8 );
						#$q__contents[$id_field][$cnt_defn]['contents_b'] = $this->oFunc->str_binary( $q__contents[$id_field][$cnt_defn]['contents_b'], 8 );
						#$q__contents[$id_field][$cnt_defn]['contents_so'] = $this->oFunc->str_binary( $q__contents[$id_field][$cnt_defn]['contents_so'], 16 );
		
						/* Cache entries (generated Wiki-code) */
						if ( $ar['gw19_is_cached'] )
						{
							foreach ( $o_child->cached as $o_cached )
							{
								$q__contents[$id_field][$cnt_defn]['contents_value_cached'] = (string) $o_cached;
							}
						}
						/* Cache entries are required */
						if ( !isset( $q__contents[$id_field][$cnt_defn]['contents_value_cached'] ) )
						{
							$q__contents[$id_field][$cnt_defn]['contents_value_cached'] = strip_tags( $q__contents[$id_field][$cnt_defn]['contents_value'] );
						}
					
						/* Search Index */
						/* Read existed Search Index when version > 1.9.2 && `gw19_is_si` is checked */
						if ( $ar['gw19_is_si'] && version_compare( $xml_file_version, '1.9.2', '>') )
						{
							foreach ( $o_child->si as $o_si )
							{
								$q__contents_si[$id_contents_max]['id_contents'] = $id_contents_max;
								$q__contents_si[$id_contents_max]['id_item'] = $id_item_max;
								$q__contents_si[$id_contents_max]['id_lang'] = $id_lang;
								$q__contents_si[$id_contents_max]['contents_si'] = (string) $o_si;
							}
						}
					}
					++$id_contents_max;
				}

				/* */
				foreach ( $q__contents as $id_fields => &$q__ar_v )
				{
					foreach ( $q__ar_v as $int_contents => &$ar_v )
					{
						if ( $ar_v['contents_value'] == '' )
						{
							unset( $q__ar_v[$int_contents] );
						}
						else if ( !isset( $q__contents_si[$ar_v['id_contents']] ) )
						{
							/* Search Index: new values */
							$oSearchIndex->update_contents( $ar_v['id_contents'], $id_item_max, $id_lang, $q__ar_v[$int_contents]['_si'] );
						}
						if ( isset( $q__ar_v[$int_contents]['_si'] ) )
						{
							unset( $q__ar_v[$int_contents]['_si'] );
						}
					}
					unset( $ar_v );
					/* Multiple insert */
					$this->o->oDb->insert( 'contents', $q__ar_v );
				}
				unset( $q__ar_v );

				/* Search Index: Multiple insert */
				if ( !empty( $q__contents_si ) )
				{
					$this->o->oDb->insert( 'contents_si', $q__contents_si );
				}

				$this->o->oDb->insert( 'items_uri', $q__items_uri );
				$this->o->oDb->insert( 'items', $q__items );

				/* Items passed */
				++$ar_settings['int_items_passed'];

				/* Items left */
				$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];

				$ar_item_ids[] = $id_item_max;

				++$id_item_max;
			}
		}
		else if ( $ar['cmp'] == 'gw18' )
		{
			/* Glossword 1.8.4+ */
			$ar_settings['int_items_total'] = sizeof( $oXml->line );
			foreach ( $oXml->line as $entry )
			{
				/* Unique time for each entry */
				$datetime_gmt = @date( $this->o->sdf, $this->o->V->time_gmt + $id_item_max );

				$q__contents = array();

				$q__items['id_item'] = $id_item_max;
				$q__items['item_cdate'] = $datetime_gmt;
				$q__items['item_mdate'] = $datetime_gmt;
				$q__items['item_id_user_created'] = $this->o->oSess->id_user;
				$q__items['item_id_user_modified'] = 0;
				$q__items['is_active'] = $ar['is_publish'];
				$q__items['is_complete'] = 1;

				$q__items_uri['id_item'] = $id_item_max;

				/* For each <term> */
				$id_field = 1;
				foreach ( $entry->term as $oterm )
				{
					/* Always 0 because only one term per entry */
					$cnt_defn = 0;
					$value_term = (string) $oterm;

					/* Filter Item */
					$item_filtered = $this->o->items__filter( $value_term );

					/* Construct alphabetic order */
					$q__contents[$id_field][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value_term, $id_lang );

					/* Read Term URI */
					$q__items_uri['item_uri'] = trim( (string) $oterm['uri'] );

					$q__items_uri['item_uri'] = ( $ar['gw18_is_term_uri'] && $q__items_uri['item_uri'] != '' )
						? $q__items_uri['item_uri'] 
						: $this->o->items__uri( $item_filtered, $id_item_max );

					/* Read Alphabetic order 1 */
					$contents_a = $oterm['t1'] ? (string) $oterm['t1'] : '';
					if ( $ar['gw18_is_az'] && $contents_a != '' )
					{
						$q__contents[$id_field][$cnt_defn]['contents_a'] = $contents_a;
					}

					/* Read Alphabetic order 2 */
					$contents_b = $oterm['t2'] ? (string) $oterm['t2'] : '';
					if ( $ar['gw18_is_az'] && $contents_b != '' )
					{
						$q__contents[$id_field][$cnt_defn]['contents_b'] = $contents_b;
					}

					$q__contents[$id_field][$cnt_defn]['contents_value'] = $value_term;
					/* No Wiki */
					$q__contents[$id_field][$cnt_defn]['contents_value_cached'] = strip_tags( $q__contents[$id_field][$cnt_defn]['contents_value'] );
					if ( $id_field == 1 )
					{
						$q__contents[$id_field][$cnt_defn]['contents_value'] = str_replace( '&amp;', '&', $q__contents[$id_field][$cnt_defn]['contents_value'] );
						/* Lighter version of term */
						$q__contents[$id_field][$cnt_defn]['contents_value'] = strip_tags( $q__contents[$id_field][$cnt_defn]['contents_value'] );
					}
					else
					{
						if ( $id_field == 3 )
						{
							$q__contents[$id_field][$cnt_defn]['contents_value'] = str_replace( '&amp;', '&', $q__contents[$id_field][$cnt_defn]['contents_value'] );
						}
						$q__contents[$id_field][$cnt_defn]['contents_value'] = '<nowiki>'.$q__contents[$id_field][$cnt_defn]['contents_value'].'</nowiki>';
					}

					$q__contents[$id_field][$cnt_defn]['id_contents'] = $id_contents_max;
					$q__contents[$id_field][$cnt_defn]['id_item'] = $id_item_max;
					$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
					$q__contents[$id_field][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
					$q__contents[$id_field][$cnt_defn]['id_user_modified'] = 0;
					$q__contents[$id_field][$cnt_defn]['id_lang'] = $id_lang;
					$q__contents[$id_field][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_field][$cnt_defn]['contents_value'] );
					$q__contents[$id_field][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_field][$cnt_defn]['contents_value'] );

					++$id_contents_max;
					/* end of $entry->term */
				}

				/* For each <defn> */
				$cnt_defn = 0;
				foreach ( $entry->defn as $odefn )
				{
					$id_field = 2;
					$value_defn = trim( (string) $odefn );

					if ( $value_defn != '' )
					{
						/* Filter Item */
						$item_filtered = $this->o->items__filter( $value_defn );

						/* Construct alphabetic order */
						$q__contents[$id_field][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value_defn, $id_lang );

						$q__contents[$id_field][$cnt_defn]['contents_value'] = $value_defn;
						/* No Wiki */
						$q__contents[$id_field][$cnt_defn]['contents_value_cached'] = $q__contents[$id_field][$cnt_defn]['contents_value'];
						$q__contents[$id_field][$cnt_defn]['contents_value'] = '<nowiki>'.$q__contents[$id_field][$cnt_defn]['contents_value'].'</nowiki>';
						$q__contents[$id_field][$cnt_defn]['id_contents'] = $id_contents_max;
						$q__contents[$id_field][$cnt_defn]['id_item'] = $id_item_max;
						$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
						$q__contents[$id_field][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
						$q__contents[$id_field][$cnt_defn]['id_user_modified'] = 0;
						$q__contents[$id_field][$cnt_defn]['id_lang'] = $id_lang;
						$q__contents[$id_field][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_field][$cnt_defn]['contents_value'] );
						$q__contents[$id_field][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_field][$cnt_defn]['contents_value'] );

						++$id_contents_max;
					}


					/* Nested fields (trsp, trns, abbr ...) */
					$cnt_child = 0;
					foreach ( $odefn->children() as $ochild )
					{
						$xmltag = $ochild->getName();
						/* Include supported fields only */
						if ( isset( $gw18_ar_id_fields[$xmltag] ) && $gw18_ar_id_fields[$xmltag] !== false )
						{
							$xmltag = $gw18_ar_id_fields[$xmltag];

							$value_child = (string) $ochild;

							/* Filter Item */
							$item_filtered = $this->o->items__filter( $value_child );

							/* Construct alphabetic order */
							$q__contents[$xmltag][$cnt_child] = $this->o->get_az_index( $item_filtered, $value_child, $id_lang );

							$q__contents[$xmltag][$cnt_child]['contents_value'] = strip_tags( $value_child );
							/* No Wiki */
							$q__contents[$xmltag][$cnt_child]['contents_value_cached'] = gw_htmlspecialchars( $q__contents[$xmltag][$cnt_child]['contents_value'] );

							$q__contents[$xmltag][$cnt_child]['id_contents'] = $id_contents_max;
							$q__contents[$xmltag][$cnt_child]['id_item'] = $id_item_max;
							$q__contents[$xmltag][$cnt_child]['id_field'] = $xmltag;
							$q__contents[$xmltag][$cnt_child]['id_user_created'] = $this->o->oSess->id_user;
							$q__contents[$xmltag][$cnt_child]['id_user_modified'] = 0;
							$q__contents[$xmltag][$cnt_child]['id_lang'] = $id_lang;
							$q__contents[$xmltag][$cnt_child]['cnt_bytes'] = strlen( $q__contents[$xmltag][$cnt_child]['contents_value'] );
							$q__contents[$xmltag][$cnt_child]['cnt_words'] = $this->o->count_words( $q__contents[$xmltag][$cnt_child]['contents_value'] );
							++$cnt_child;
							++$id_contents_max;
						}
					}

					if ( empty( $q__contents[$id_field] ) )
					{
						unset( $q__contents[$id_field] );
					}

					++$cnt_defn;
					++$id_contents_max;
					/* end of $entry->defn */
				}

				#$this->o->oDb->delete( 'contents', array( 'id_item' => $id_item_max ) );

				foreach ( $q__contents as $id_fields => &$q__ar_v )
				{
					foreach ( $q__ar_v as $int_contents => &$ar_v )
					{
						if ( $ar_v['contents_value'] == '' )
						{
							unset( $q__ar_v[$int_contents] );
						}
						else
						{
							$oSearchIndex->update_contents( $ar_v['id_contents'], $id_item_max, $id_lang, $q__ar_v[$int_contents]['_si'] );
							unset( $q__ar_v[$int_contents]['_si'] );
						}
					}
					unset( $ar_v );
					/* Multiple insert */
					$this->o->oDb->insert( 'contents', $q__ar_v );
				}
				unset( $q__ar_v );
				
				# prn_r( $q__items );
				# prn_r( $q__items_uri );
				#prn_r( $q__contents );

				#$this->o->oDb->delete( 'items', array( 'id_item' => $id_item_max ), 1 );
				#$this->o->oDb->delete( 'items_uri', array( 'id_item' => $id_item_max ), 1 );

				$this->o->oDb->insert( 'items_uri', $q__items_uri );
				$this->o->oDb->insert( 'items', $q__items );

				/* Items are passed */
				++$ar_settings['int_items_passed'];

				/* Items are left */
				$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];

				$ar_item_ids[] = $id_item_max;
				
				++$id_item_max;
				/* end of $oXML->line */
			}
		}
	break;
	case GW_INPUT_FMT_CSV:

		$cnt = $cnt_item = 0;
		$id_item_cnt = $id_item_max;

		/* Save user settings */
		foreach ( array( 'separator', 'is_convert_escape', 'is_head', 'is_overwrite', 'is_publish' ) as $v )
		{
			if ( $this->o->oSess->id_user != $this->o->oSess->id_guest
				&& (string) $this->o->oSess->user_get( 'items_import__'.$v ) != $ar[$v] )
			{
				$this->o->oSess->user_set( 'items_import__'.$v, $ar[$v] );
			}
		}

		$ar_lines = explode( "\n", $ar['input'] );

		$ar_settings['int_items_total'] = sizeof( $ar_lines );
		$ar['separator'] = str_replace( array('\\t', '\\n', '\\r'), array( "\t", "\n", "\r" ), $ar['separator'] );

		$ar['input'] = '';

		/*
  [col_defn] => 2
  [col_seealso] => 3
  [col_term] => 1
  [is_check_existent] => 1
  [is_convert_escape] => 1
  [is_head] => 1
  [is_overwrite] => 1
  [is_publish] => 1
  [separator] => ;
		*/

		foreach( $ar_lines as $line )
		{
			$cnt_defn = 1;
			$q__contents = $q__items_uri = $q__items = array();

			/* Unique time for each entry */
			$datetime_gmt = @date( $this->o->sdf, $this->o->V->time_gmt + $cnt );

			/* Skip an empty line */
			if ( trim( $line ) == '' ) { --$ar_settings['int_items_left']; --$ar_settings['int_items_total']; continue; }

			/* Skip the first line */
			if ( $cnt == 0 && !$ar['is_head'] ) { ++$cnt; --$ar_settings['int_items_left']; --$ar_settings['int_items_total']; continue; }

			/* Parse CSV line into array */
			$ar_line = str_getcsv( $line, $ar['separator'] );

			/* Insert Contents ID to the 1st column, $ar_line[0] */
			array_unshift( $ar_line, $id_contents_max );
			$str_term_temp = $str_term = isset( $ar_line[$ar['col_term']] ) ? $ar_line[$ar['col_term']] : $ar_line[1];

			/* Detect second definition */
			$ar_item[$cnt] = $str_term_temp;
			if ( isset( $ar_item[$cnt-1] ) && $ar_item[$cnt-1] == $str_term_temp )
			{
				$str_term_temp = '';
				--$cnt_item;
				$id_item_cnt = $id_item_max + $cnt_item;
				++$cnt_defn;
			}
			#prn_r( $cnt_item . ' ' . $str_term_temp );

			foreach ( $ar_line as $id_col => $value )
			{
				/* Skip an empty line */
				$value = trim( $value );
				if ( $value == '' ) { continue; }

				if ( $ar['is_convert_escape'] )
				{
					$value = str_replace( array('\\t', '\\n', '\\r'), array( "\t", "\n", "\r" ), $value );
				}

				$item_filtered = '';

				/* @temp */
				switch ( $id_col )
				{
					case $ar['col_term']: $id_field = $gw19_ar_field_cols['col_term']; break;
					case $ar['col_defn']: $id_field = $gw19_ar_field_cols['col_defn']; break;
					case $ar['col_seealso']: $id_field = $gw19_ar_field_cols['col_seealso']; break;
				}

				/* */
				switch ( $id_col )
				{
					case $ar['col_term']:
						if ( $str_term_temp )
						{
							$q__items['id_item'] = $id_item_cnt;
							$q__items['item_cdate'] = $datetime_gmt;
							$q__items['item_mdate'] = $datetime_gmt;
							$q__items['item_id_user_created'] = $this->o->oSess->id_user;
							$q__items['item_id_user_modified'] = 0;
							$q__items['is_active'] = $ar['is_publish'];
							$q__items['is_complete'] = 1;

							$q__items_uri['id_item'] = $id_item_cnt;

							/* Filter Item */
							$item_filtered = $this->o->items__filter( $value );

							/* Construct alphabetic order */
							$q__contents[$id_col][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value, $id_lang );

							$q__contents[$id_col][$cnt_defn]['contents_value'] = $value;
							$q__contents[$id_col][$cnt_defn]['contents_value_cached'] = strip_tags( $q__contents[$id_col][$cnt_defn]['contents_value'] );
							$q__contents[$id_col][$cnt_defn]['id_contents'] = $id_contents_max;
							$q__contents[$id_col][$cnt_defn]['id_item'] = $id_item_cnt;
							$q__contents[$id_col][$cnt_defn]['id_field'] = $id_field;
							$q__contents[$id_col][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
							$q__contents[$id_col][$cnt_defn]['id_user_modified'] = 0;
							$q__contents[$id_col][$cnt_defn]['id_lang'] = $id_lang;
							$q__contents[$id_col][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_col][$cnt_defn]['contents_value'] );
							$q__contents[$id_col][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_col][$cnt_defn]['contents_value'] );
						}
					break;
					case $ar['col_defn']:
					case $ar['col_seealso']:
						/* Filter Item */
						$item_filtered = $this->o->items__filter( $value );

						/* Construct alphabetic order */
						$q__contents[$id_col][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value, $id_lang );

						$q__contents[$id_col][$cnt_defn]['contents_value'] = $value;
						$q__contents[$id_col][$cnt_defn]['contents_value_cached'] = strip_tags( $q__contents[$id_col][$cnt_defn]['contents_value'] );
						$q__contents[$id_col][$cnt_defn]['id_contents'] = $id_contents_max;
						$q__contents[$id_col][$cnt_defn]['id_item'] = $id_item_cnt;
						$q__contents[$id_col][$cnt_defn]['id_field'] = $id_field;
						$q__contents[$id_col][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
						$q__contents[$id_col][$cnt_defn]['id_user_modified'] = 0;
						$q__contents[$id_col][$cnt_defn]['id_lang'] = $id_lang;
						$q__contents[$id_col][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_col][$cnt_defn]['contents_value'] );
						$q__contents[$id_col][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_col][$cnt_defn]['contents_value'] );
					break;
				}
				/* Create Item URI */
				if ( $item_filtered != '' && $id_field == $this->o->V->id_field_root )
				{
					$q__items_uri['item_uri'] = $this->o->items__uri( $item_filtered,  $id_item_cnt );
				}
				++$id_contents_max;
			}

#			prn_r( $q__items_uri );
#			prn_r( $q__contents );
#			return;

			/* */
			foreach ( $q__contents as $id_fields => &$q__ar_v )
			{
				foreach ( $q__ar_v as $int_contents => &$ar_v )
				{
					if ( $ar_v['contents_value'] == '' )
					{
						unset( $q__ar_v[$int_contents] );
					}
					else
					{
						$oSearchIndex->is_delete = false;
						$oSearchIndex->update_contents( $ar_v['id_contents'], $id_item_cnt, $id_lang, $q__ar_v[$int_contents]['_si'] );
						unset( $q__ar_v[$int_contents]['_si'] );
					}
				}
				unset( $ar_v );
				/* Multiple insert */
				$this->o->oDb->insert( 'contents', $q__ar_v );
			}
			unset( $q__ar_v );

#prn_r( $q__contents );
#prn_r( $q__items );
#prn_r( $q__items_uri );

			if ( !empty( $q__items_uri ) )
			{
				$this->o->oDb->insert( 'items_uri', $q__items_uri );
			}
			if ( !empty( $q__items ) )
			{
				$this->o->oDb->insert( 'items', $q__items );
				$ar_item_ids[] = $id_item_cnt;
			}

			/* Items are passed */
			++$ar_settings['int_items_passed'];

			/* Items are left */
			$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];

			++$cnt_item;
			++$cnt;
			$id_item_cnt = $id_item_max + $cnt_item;
		}
	break;
	case GW_INPUT_FMT_XML:

		/* */
		$ar['input'] = str_replace( 'xmlns=', 'ns=', $ar['input'] );
		$oXml = new SimpleXMLElement( $ar['input'] );

		$ar['input'] = '';
		$cnt = 0;

		$ar['xml_entry'] = trim( str_replace( array('<', '>'), '', $ar['xml_entry'] ) );
		$ar['xml_term'] = trim( str_replace( array('<', '>'), '', $ar['xml_term'] ) );
		$ar['xml_defn'] = trim( str_replace( array('<', '>'), '', $ar['xml_defn'] ) );

		/* */
		$ar_settings['int_items_total'] = sizeof( $oXml->xpath ('//'.$ar['xml_entry'] ) );

		if ( !$ar_settings['int_items_total'] )
		{
			$this->o->oOutput->append_html( $this->o->soft_redirect(
				$this->o->oTkit->_( 1095 ).'<br />'.$this->o->oTkit->_( 1112 ), $href_redirect, GW_COLOR_FALSE
			));
			return;
		}

		/* For each <entry> */
		foreach ( $oXml->xpath('//'.$ar['xml_entry'] ) as $entry )
		{
			/* Unique time for each entry */
			$datetime_gmt = @date( $this->o->sdf, $this->o->V->time_gmt + $id_item_max );

			$q__contents = $q__items = $q__items_uri = $q__contents_si = array();

			/* No <term> found */
			if ( !isset( $entry->$ar['xml_term'] ) )
			{
				continue;
			}

			/* */
			$q__items['id_item'] = $id_item_max;
			$q__items['item_cdate'] = $datetime_gmt;
			$q__items['item_mdate'] = $datetime_gmt;
			$q__items['item_id_user_created'] = $this->o->oSess->id_user;
			$q__items['item_id_user_modified'] = 0;
			$q__items['is_active'] = $ar['is_publish'];
			$q__items['is_complete'] = 1;

			$q__items_uri['id_item'] = $id_item_max;

			$cnt_defn = 0;
			foreach ( $entry->children() as $o_child )
			{
				$id_field = 0;
				$xmltag = $o_child->getName();
				switch ( $xmltag )
				{
					case $ar['xml_term']:
						$id_field = $this->o->V->id_field_root;
					break;
					case $ar['xml_defn']:
						++$cnt_defn;
						$id_field = 2;
					break;
				}
				/* Field is known */
				if ( $id_field  )
				{
					$value = trim( (string) $o_child );
					
					/* Filter Item */
					$item_filtered = $this->o->items__filter( $value );

					/* Construct alphabetic order */
					$q__contents[$id_field][$cnt_defn] = $this->o->get_az_index( $item_filtered, $value, $id_lang );
							
					$q__contents[$id_field][$cnt_defn]['contents_value'] = '<nowiki>'.$value.'</nowiki>';
					/* Add space for HTML-tags */
					$q__contents[$id_field][$cnt_defn]['contents_value'] = str_replace('><', '> <', $q__contents[$id_field][$cnt_defn]['contents_value'] );
					$q__contents[$id_field][$cnt_defn]['id_item'] = $id_item_max;
					$q__contents[$id_field][$cnt_defn]['id_field'] = $id_field;
					$q__contents[$id_field][$cnt_defn]['id_contents'] = $id_contents_max;
					$q__contents[$id_field][$cnt_defn]['id_user_created'] = $this->o->oSess->id_user;
					$q__contents[$id_field][$cnt_defn]['id_user_modified'] = 0;
					$q__contents[$id_field][$cnt_defn]['id_lang'] = $id_lang;
					$q__contents[$id_field][$cnt_defn]['cnt_bytes'] = strlen( $q__contents[$id_field][$cnt_defn]['contents_value'] );
					$q__contents[$id_field][$cnt_defn]['cnt_words'] = $this->o->count_words( $q__contents[$id_field][$cnt_defn]['contents_value'] );

					if ( $id_field == $this->o->V->id_field_root )
					{
						/* Generate new Item URI */
						$q__items_uri['item_uri'] = $this->o->items__uri( $item_filtered, $id_item_max );
					}

					/* No Wiki */
					$q__contents[$id_field][$cnt_defn]['contents_value_cached'] = $q__contents[$id_field][$cnt_defn]['contents_value'];

				}
				++$id_contents_max;
			}

			/* */
			foreach ( $q__contents as $id_fields => &$q__ar_v )
			{
				foreach ( $q__ar_v as $int_contents => &$ar_v )
				{
					if ( $ar_v['contents_value'] == '' )
					{
						unset( $q__ar_v[$int_contents] );
					}
					else
					{
						$oSearchIndex->update_contents( $ar_v['id_contents'], $id_item_max, $id_lang, $q__ar_v[$int_contents]['_si']);
						unset( $q__ar_v[$int_contents]['_si'] );
					}
				}
				unset( $ar_v );
				/* Multiple insert */
				$this->o->oDb->insert( 'contents', $q__ar_v );
			}
			unset( $q__ar_v );

			$this->o->oDb->insert( 'items_uri', $q__items_uri );
			$this->o->oDb->insert( 'items', $q__items );

			/* Items passed */
			++$ar_settings['int_items_passed'];

			/* Items left */
			$ar_settings['int_items_left'] = $ar_settings['int_items_total'] - $ar_settings['int_items_passed'];

			$ar_item_ids[] = $id_item_max;
			
			++$id_item_max;
		}
	break;
}


/* Update alphabetic order */ 
#$this->o->oDb->where_in( 'id_item', $ar_item_ids );
#$this->o->oDb->delete( 'items_tmp' );

$this->o->oDb->query( 'SET SQL_BIG_SELECTS=1' );

$this->o->oDb->select( 'i.id_item, c.id_lang, az1.id_lang' );
$this->o->oDb->select( 'i.item_mdate, i.is_active, i.is_complete, i.cnt_hits, c.contents_a, c.contents_b, c.contents_so' );
$this->o->oDb->from( array( 'items i', 'contents c' ) );
$this->o->oDb->where( array( 'i.id_item = c.id_item' => NULL, 'c.id_field' => (string) $this->o->V->id_field_root ) );
/* 1.9.3: Custom alphabetic order */
for ( $i = 1; $i <= 8; $i++ )
{
	$this->o->oDb->select( 'az'.$i.'.int_sort' );
	$this->o->oDb->join( 'az_letters az'.$i.'', 'az'.$i.'.uc_crc32u = c.contents_'.$i.' AND c.id_lang = az'.$i.'.id_lang', 'left', false );
}
$this->o->oDb->where_in( 'i.id_item', $ar_item_ids );
$this->o->oDb->group_by( 'i.id_item' );
$this->o->oDb->query( 'INSERT INTO `'.$this->o->oDb->dbprefix.'items_tmp` '.$this->o->oDb->get_select() );



$str_report = '<table class="tbl-list" width="75%"><tbody>
	<tr><td style="width:75%">'.$this->o->oTkit->_( 1093 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_total'] ).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1092 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_passed']).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1091 ).':</td><td class="b">'.$this->o->oTkit->number_format( $ar_settings['int_items_left'] ).'</td></tr>
	<tr><td>'.$this->o->oTkit->_( 1090 ).':</td><td class="b">'.$this->o->oTkit->number_format( $oTimer->end(), 5 ).'</td></tr>
	</table>';

/* Place progress bar */
$this->o->oOutput->append_html( $this->o->oFunc->text_progressbar( 100, '#FFF', '#6e9a18' ) );

/* Display report */
$this->o->oOutput->append_html( $this->o->soft_redirect(
	$str_report.'<p class="color-black">'.$this->o->oTkit->_( 1094 ).'</p>', $href_redirect, GW_COLOR_TRUE
));


/* Clean cache */
$this->o->oCache->remove_by_group( 'items-az' );
$this->o->oCache->remove_by_group( 'items-browse' );
$this->o->oCache->remove_by_group( 'items-browse-html' );
$this->o->oCache->remove_by_group( 'items-prevnext' );

/* */
$is_redirect = 0;

?>