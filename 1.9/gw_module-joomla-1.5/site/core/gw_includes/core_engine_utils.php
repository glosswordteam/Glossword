<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_CLASS_GW_UTILS')) { define('IS_CLASS_GW_UTILS', 1);
class site_engine_utils extends site_engine {

	public $rm_specials_patterns, $uri_patterns, $az_order_patterns;


	/* Returns HTML-code for a search form (web) */
	public function get_search_form()
	{
		$s = '';
		/* */
		$s .= '<form action="'. $this->V->server_dir.'/'.$this->V->file_index.'" enctype="application/x-www-form-urlencoded" accept-charset="utf-8" method="post" >';
		$s .= '<span class="floatright" style="font-size:100%">';
		$s .= '<input style="width:10em" type="text" name="searchword" value="" />';
		$s .= '<input style="cursor:pointer" type="submit" value="'. $this->oTkit->_( 1175 ). '" />';
		$s .= '<input name="option" value="com_search" type="hidden" />';
		$s .= '<input name="task" value="search" type="hidden" />';
		$s .= '<input name="areas[]" value="gw_search" type="hidden" />';
		$s .= '</form></span>';
		
		return $s;
	}
	/* Counts the number of words, approximately */
	public function count_words( $s )
	{
		if ( $s == '' ) { return 0; }
		return sizeof( explode( ' ', $s ) );
	}

	/* */
	public function items__filter( $s )
	{
		$s = $this->rm_specials( $s );
		$s = $this->str_normalize( $s );
		return $s;
	}

	/* */
	public function get_az_order( $id_lang )
	{
		$this->oDb->select( 'uc, lc');
		$this->oDb->from( array( 'az_letters az' ) );
		$this->oDb->where( array( 'az.id_lang' => $id_lang ) );
		$this->oDb->order_by( 'az.int_sort' );
		$ar_sql = $this->oDb->get()->result_array();
		$ar = array();
		foreach ($ar_sql as $ar_v )
		{
			$ar[urldecode( $ar_v['lc'] )] = urldecode( $ar_v['uc'] );
		}
		return $ar;
	}
		
	/* Unicode normalization. Used for Search Index and Alphabetic Order */
	public function str_normalize( $s )
	{
		$s = $this->oCase->lc( $s );
		
		/* Use PECL extension */
		if ( class_exists( 'Normalizer' ) )
		{
			return Normalizer::normalize( $s, Normalizer::FORM_C );
		}
		/* */
		preg_match_all( "/./u", $s, $ar );
		$ar = $ar[0];
		$ar_c_crc = array();
		/* For each character */
		foreach ($ar AS $k => &$v )
		{
			/* Use values as key */
			/* PHP-bug: sometimes a string keys becomes interger */
			$ar_c_crc[$v] = sprintf( "%u", crc32( $v ) );
		}
		unset( $v );
		if ( empty( $ar_c_crc ) ){ return $s; }
		/* */
		$is_debug_q = $this->oDb->is_debug_q;
		$this->oDb->is_debug_q = false;
		$this->oDb->select( 'str_from, str_to' );
		$this->oDb->from( 'unicode_normalization' );
		$this->oDb->where_in( 'crc32u', array_values( $ar_c_crc ) );
		$ar_sql = $this->oDb->get()->result_array();
		$this->oDb->is_debug_q = $is_debug_q;
		/* Normalize text */
		foreach ($ar_sql AS $k => &$v )
		{
			$s = str_replace( urldecode( $v['str_from'] ), urldecode( $v['str_to'] ), $s );
			unset( $ar_sql[$k] );
		}
		unset( $v );

		return $s;
	}


	/* Normalized value, Source value, Language ID */
	public function get_az_index( $s, $s_src, $id_lang )
	{
		if ( !isset( $this->az_order_patterns[$id_lang] ) )
		{
			$this->az_order_patterns[$id_lang] = $this->get_az_order( $id_lang );
		}
		
		$ar_q = array();
		$ar_q['_si'] = $s; /* Temporary, used for Search Index */
		
		/** Prepare Sorting Order **/
		$s_src = $this->oCase->lc( $s_src );
		$s_src = trim( strip_tags( $s_src ) );
		$s_src = $this->oFunc->mb_substr( $s_src, 0, 16 );
		$str_az_order = str_replace( 
			array_keys( $this->az_order_patterns[$id_lang] ), 
			array_values( $this->az_order_patterns[$id_lang] ), 
			$s_src
		);
		$str_az_order = $this->oCase->uc( $str_az_order );
		preg_match_all( "/./u", $str_az_order, $ar );
		for ( $i = 1; $i <= 8; $i++ )
		{
			$ar_q['contents_'.$i] = isset( $ar[0][$i-1] ) ? sprintf( "%u", crc32( $ar[0][$i-1] ) ) : 0;
		}
		$ar_q['contents_so'] = $str_az_order;

		/** Prepare Alphabetic Order **/
		$ar_q['contents_a'] = isset( $ar[0][0] ) ? $ar[0][0] : '';
		$ar_q['contents_b'] = isset( $ar[0][1] ) ? $ar[0][1] : '';
		#$s = $this->oCase->uc( trim( $s ) );
		#$ar_q['contents_a'] = $this->oFunc->mb_substr( $s, 0, 1 );
		#$ar_q['contents_b'] = $this->oFunc->mb_substr( $s, 1, 1 );
		
		/* String is empty or consists of special characters only */
		if ( $ar_q['contents_a'] == '' ){ $ar_q['contents_a'] = '#'; }
		
		/* */
		#$ar_q['contents_1'] = sprintf( "%u", crc32( $ar_q['contents_a'] ) );

#prn_r( $ar_q );
#exit;
		return $ar_q;
	}


	/* Removes a special characters !#$%... 264 symbols */
	public function rm_specials( $s, $newchar = ' ' )
	{
		/* Always remove `.` */
		$s = str_replace( '.', ' ', $s );
		
		/* Remove lines feeds */
		$s = $this->oCase->rm_crlf( $s );
		
		/* Add space to HTML-tags */
		$s = str_replace('><', '> <', $s );
		$s = str_replace('<'.'?'.'php', '', $s );
		$s = strip_tags( $s );
		
		/* remove {TEMPLATES}, {%TEMPLATES%} */
		#$s = preg_replace( "/{(%)?([A-Za-z0-9:\-_]+)(%)?}/", '', $s );
		$s = $this->oCase->rm_entity( $s );

		$newchar_encd = urlencode( $newchar );
		
		/* Cache patterns using memory */
		if ( !isset( $this->rm_specials_patterns[$newchar_encd] ) )
		{
			/* Construct filename with cached replacement patterns */
			$filename = $this->V->path_temp.'/gw_translit_1_'.$newchar_encd.'.tmp';
			
			/* Cache patterns using files */
			if ( file_exists( $filename ) )
			{
				$this->rm_specials_patterns[$newchar_encd] = unserialize( implode( '', file( $filename ) ) );
			}
			else
			{
				/* Read patterns from database */
				$this->oDb->select( 'str_from, str_to' );
				$this->oDb->from( 'translit' );
				$this->oDb->where( array( 'id_profile' => '1' ) );
				$ar_sql = $this->oDb->get()->result_array();
				$ar_lines = array();
				foreach ( $ar_sql as $ar_v ) 
				{
					$this->rm_specials_patterns[$newchar_encd][urldecode($ar_v['str_from'])] = ( $newchar != '' && $ar_v['str_to'] != '+' ) 
						? urldecode( $ar_v['str_to'] ) 
						: $newchar;
				}
				$this->oFunc->file_put_contents( $filename, serialize( $this->rm_specials_patterns[$newchar_encd] ) );
			}
		}
		
		/* */
		$s = str_replace( 
			array_keys( $this->rm_specials_patterns[$newchar_encd] ), 
			array_values( $this->rm_specials_patterns[$newchar_encd] ), 
			$s
		);

		/* Leave one character only */
		if ( $newchar != '' )
		{
			/*
			 0.000395 - preg_replace
			 0.000031 - str_replace
			*/
			$cnt_replace = 1;
			while ( $cnt_replace )
			{
				$s = str_replace( $newchar.$newchar, $newchar, $s, $cnt_replace ); 
			}
		}

		return $s;
	}
	
	
	/* */
	public function strord( $s )
	{
		preg_match_all( "/./u", $s, $ar_letters );
		$ar_ord = array();
		for ($i = 1; $i <= 8; $i++)
		{
			$ar_ord[$i] = isset( $ar_letters[0][$i] ) ? implode('', unpack( "C*", $ar_letters[0][$i] ) ) : 0;
		}
		return $ar_ord;
	}

	/* Generates URI for an Item */
	public function items__uri( $s, $id_item = '' )
	{
		/* 327 symbols */
		/* 0.26, 110 Queries */
		/* 0.09, 10 Queries */
		
		/* Construct filename with cached replacement patterns */
		$filename = $this->V->path_temp.'/gw_translit_2.tmp';

		/* Cache patterns using memory */
		if ( !$this->uri_patterns )
		{
			/* Cache patterns using files */
			if ( file_exists( $filename ) )
			{
				$this->uri_patterns = unserialize( implode( '', file( $filename ) ) );
			}
			else
			{
				/* Read patterns from database */
				$this->oDb->select( 'str_from, str_to' );
				$this->oDb->from( 'translit' );
				$this->oDb->where( array( 'id_profile' => '2' ) );
				$ar_sql = $this->oDb->get()->result_array();
				$ar_lines = array();
				foreach ( $ar_sql as $ar_v ) 
				{
					$this->uri_patterns[urldecode($ar_v['str_from'])] = urldecode( $ar_v['str_to'] );
				}
				$this->oFunc->file_put_contents( $filename, serialize( $this->uri_patterns ) );
			}
		}

		/* */
		$s = str_replace( array_keys( $this->uri_patterns ), array_values( $this->uri_patterns ), $s );

		/* Keep alphanumeric only characters */
		$s = preg_replace( '/[^0-9A-Za-z_-]/', '-', $s );

		/* Remove repeated `-` */
		$cnt_replace = 1;
		while ( $cnt_replace )
		{
			$s = str_replace( '--', '-', $s, $cnt_replace ); 
		}
		$s = rtrim( $s, '-' );
		
		/* No alphanumeric characters found, use Item ID */
		if ( $s == '' )
		{
			$s = $id_item;
		}

		/* Limit length */
		$s = substr( $s, 0, 200 );

		return $s;
	}
	
	/* */
	public function notice_onsubmit( $phrase, $is_success = true )
	{
		$this->oOutput->append_html( '<div><div class="'.GW_COLOR_TRUE.' updated" id="status-onsumbit">' );
		$this->oOutput->append_html( '<a onclick="jsF.destroy_el(\'status-onsumbit\');" style="margin:-1em -1em;font-size:80%;" class="btn remove floatright" href="#" title="'. $this->oTkit->_( 1154 ) .'">'. $this->oTkit->_( 1154 ) .'<span class="icon-rm"></span></a>' );
		$this->oOutput->append_html( $phrase );
		$this->oOutput->append_html( '</div></div>' );
		/* Automatically close window */
		$this->oOutput->append_js( 'setTimeout( function(){ jsF.FX_fade_out(\'status-onsumbit\'); }, 4000 );' );
	}
	
	/* */
	public function langs__get_installed()
	{
		$ar = array();
		foreach ( $this->oTkit->ar_lang_list as $ar_v )
		{
			$ar[$ar_v['id_lang']] = $ar_v['lang_name'].' - '.$ar_v['lang_native'];
		}
		/* @todo: put in cache */
		return $ar;
	}
	/* */
	public function langs__get_locale_codes()
	{
		$ar = array();
		foreach ( $this->oTkit->ar_lang_list as $ar_v )
		{
			$ar[$ar_v['isocode1'].'_'.$ar_v['region']] = $ar_v['id_lang'];
		}
		/* @todo: put in cache */
		return $ar;
	}
	
	
	/**
	 * Constructs alphabetic order and returns HTML for navigation.
	 *
	 * @uses $oDb, $oHtml, $oCache.
	 * @return array [ az, aazz ]
	 */
	public function items__get_az()
	{
		$ar_az = $ar_aazz = array();
		
		/* Reduce cache time for admin area */
		$time_cache_az = ( SITE_ADMIN_MODE ) ? 60 * 2 : $this->V->time_cache_az;

		/* A-Z */

#$oTimer = new tkit_timer('az-sql');

		/**
		 * Use DB-based cache.
		 * Cache off: 0.003457 az sql + 0.007476 az html = 0.010933
		 * Cache on:  0.002011 az html
		*/

		$cache_key = 'az-' . $this->gv['il'] . $this->V->is_sef . 
			( isset( $this->gv['area']['a1'] ) ? $this->gv['area']['a1'] : '-' ) . 
			( isset( $this->gv['area']['lc'] ) ? $this->gv['area']['lc'] : '-' ) . 
			$this->V->id_field_root . SITE_ADMIN_MODE . SITE_WEB_MODE;
		if ( $this->V->is_cache_az && $this->oCache->in_cache( $cache_key, 'items-az', $time_cache_az ) )
		{
			$ar_az = $this->oCache->load();
		}
		else
		{
			/* Basic Multilingual Plane: */
			/* http://www.unicode.org/roadmaps/bmp/ */
			/* Break alphabetic toolbar per every set of characters */
			$ar_unicode_map = array(
				#array( '20', 'Basic Latin Digits' ),
				#array( '41', 'Basic Latin' ),
				array( '20', 'Basic Latin && Basic Latin Digits' ),
				array( 'c280', 'Latin Extended' ),
				array( 'c990', 'IPA Extensions' ),
				array( 'cab0', 'Spacing Modifiers' ),
				array( 'cc80', 'Combining Diacritics' ),
				array( 'cdb0', 'Greek' ),
				array( 'd080', 'Cyrillic, Cyrillic Supplement' ),
				array( 'd4b0', 'Armenian' )
			);
			$ar_tb_last = end( $ar_unicode_map );
			$int_tb_last = hexdec( $ar_tb_last[0] );
			
			/* Select first letters and Language ID */
#			$this->oDb->select( 'c.contents_a, az1.id_lang, CONCAT( l.isocode1,"_", l.region ) locale', false );
#			$this->oDb->from( array( 'items i', 'contents c', 'languages l' ) );
#			$this->oDb->where( array( 
#				'i.id_item = c.id_item' => NULL,
#				'l.id_lang = c.id_lang' => NULL, 
#				'c.id_field' => (string) $this->V->id_field_root, 
#				'i.is_active' => '1' 
#			) );
#			$this->oDb->group_by( 'az1.id_lang, c.contents_a' );
#			$this->oDb->order_by( 'c.id_lang' );

			/* 1.9.3: Custom alphabetic order */
#			$this->oDb->join( 'az_letters az1', 'az1.uc_crc32u = c.contents_1 AND c.id_lang = az1.id_lang', 'left', false );
#			$this->oDb->order_by( 'az1.int_sort' );
#			$this->oDb->order_by( 'c.contents_so' );
			
			$this->oDb->select( 'it.contents_a, it.id_lang_1 id_lang, CONCAT( l.isocode1,"_", l.region ) locale', false );
			$this->oDb->from( array( 'items_tmp it' ) );
			$this->oDb->join( 'languages l', 'it.id_lang_1 = l.id_lang', 'left' );
			$this->oDb->where( array( 'it.is_active' => '1'  ) );
			$this->oDb->group_by( 'it.id_lang_1, it.contents_a' );
			$this->oDb->order_by( 'it.int_sort_1, it.contents_so' );

			$ar_sql = $this->oDb->get()->result_array();


			
	#print $oTimer->endp('az sql');
	#$oTimer = new tkit_timer('az-html');
			
			$oHref = $this->oHtml->oHref();
			foreach ( $ar_sql as $ar_v )
			{
				$ar_v['contents_a'] = str_replace( "\0", '', $ar_v['contents_a'] );
				/* For each letter */
				$oHref = $this->oHtml->oHref();
				$oHref->set( 'a1', urlencode( $ar_v['contents_a'] ) );
				/* 1.9.3: Locale code */
				$oHref->set( 'lc', $ar_v['locale'] );

				$class_name = '';
				if ( isset( $this->gv['area']['a1'] ) && isset( $this->gv['area']['lc'] )
					&& $ar_v['contents_a'] == $this->gv['area']['a1'] 
					&& $ar_v['locale'] == $this->gv['area']['lc'] 
				)
				{
					$class_name = 'highlight';
				}
				
				/* 1.9.3: Group Alphabetic Order using Language ID */
				if ( SITE_ADMIN_MODE )
				{
					$oHref->set( 't', 'items' );
					$oHref->set( 'a', 'manage' );
					$ar_az[$ar_v['id_lang']][] = $this->oHtmlAdm->a_href( 
								array( $this->V->file_index, '#area' => $oHref->get() ), 
								array( 'class' => $class_name, 'title' => $ar_v['contents_a'] ), $ar_v['contents_a'] 
					);
				}
				else
				{
					$ar_az[$ar_v['id_lang']][] = $this->oHtml->a_href( 
								array( $this->V->file_index, '#area' => $oHref->get() ), 
								array( 'class' => $class_name, 'title' => $ar_v['contents_a'] ), $ar_v['contents_a']
					);
				}
			}
				
			/* Re-arrange */
			foreach ( $ar_az as $az_k => $az_v )
			{
				unset( $ar_az[$az_k] );
				$ar_az[] = '<div>';
				foreach ( $az_v as $k_tb => $v_tb )
				{
					$ar_az[] = $v_tb;
				}
				$ar_az[] = '</div>';
			}
			
			# prn_r( $ar_az );

			/* Show all */
			$oHref->set( 'a1' );
			$oHref->set( 'a2' );
			$oHref->set( 'lc' );
			if ( SITE_ADMIN_MODE )
			{
				$ar_az[-1] = $this->oHtmlAdm->a_href(
							array( $this->V->file_index, '#area' => $oHref->get() ),
							array( 'class' => ( !isset( $this->gv['area']['a1'] ) || ( isset( $this->gv['area']['a1'] ) && $this->gv['area']['a1'] ) == '' ? 'highlight' : '' ) ),
							$this->oTkit->_( 1044 )
				);
			}
			else
			{
				/* 1.9.3: Added `showall` classname */
				$ar_az[-1] = $this->oHtml->a_href(
							array( $this->V->file_index, '#area' => $oHref->get() ),
							array(
								'title' => $this->oTkit->_( 1044 ), 
								'class' => 'showall' . ( !isset( $this->gv['area']['a1'] ) || ( isset( $this->gv['area']['a1'] ) && $this->gv['area']['a1'] != '' ) == '' ? ' highlight' : '' ) ),
							$this->oTkit->_( 1044 )
				);
			}
			/* Cached Units: Save */
			if ( $this->V->is_cache_az )
			{
				 $ar_az = $this->oCache->save( $ar_az );
			}
		}

#print $oTimer->endp('az html');

		/* AA-ZZ */
		/* 1.9.3: disabled */
		if ( 0 && isset( $this->gv['area']['a1'] ) && $this->gv['area']['a1'] != '' )
		{

#$oTimer = new tkit_timer('aazz-sql');

			$cache_key = 'aazz-' . $this->gv['il'] . $this->V->is_sef . ( isset( $this->gv['area']['a1'] ) ? $this->gv['area']['a1'] : '' ) .
				( isset( $this->gv['area']['a2'] ) ? $this->gv['area']['a2'] : '' ) .
				$this->V->id_field_root . SITE_ADMIN_MODE . SITE_WEB_MODE;
			if ( $this->oCache->in_cache( $cache_key, 'items-az', $time_cache_az ) )
			{
				$ar_aazz = $this->oCache->load();
			}
			else
			{

				$this->oDb->select( 'c.contents_b' );
				$this->oDb->from( array( 'items i', 'contents c' ) );
				$this->oDb->where( array( 'i.id_item = c.id_item' => NULL, 'i.is_active' => 1, 'c.id_field' => $this->V->id_field_root, 'c.contents_a' => $this->gv['area']['a1']  ) );
				$this->oDb->group_by( 'c.contents_b' );
				$ar_sql = $this->oDb->get()->result_array();

#	print $oTimer->endp('aazz sql');
#	$oTimer = new tkit_timer('aazz-html');
				
				foreach ( $ar_sql as $ar_v )
				{
					$oHref = $this->oHtml->oHref();
					$oHref->set( 'a1', urlencode( $this->gv['area']['a1'] ) );
					$oHref->set( 'a2', urlencode( $ar_v['contents_b'] ) );

					$class_name = '';
					if ( isset( $this->gv['area']['a2'] ) && $ar_v['contents_b'] == $this->gv['area']['a2'] )
					{
						$class_name = 'highlight';
					}
					
					if ( SITE_ADMIN_MODE )
					{
						$oHref->set( 't', $this->gv['area']['t'] );
						$oHref->set( 'a', $this->gv['area']['a'] );
						$ar_aazz[] = $this->oHtmlAdm->a_href(
										array( $this->V->file_index, '#area' => $oHref->get() ),
										array( 'class' => $class_name ),
										$this->gv['area']['a1'].$ar_v['contents_b']
						);
					}
					else
					{
						$ar_aazz[] = $this->oHtml->a_href(
										array( $this->V->file_index, '#area' => $oHref->get() ),
										array( 'class' => $class_name ),
										$this->gv['area']['a1'].$ar_v['contents_b']
						);
					}
				}
				/* Cached Units: Save */
				$ar_aazz = $this->oCache->save( $ar_aazz );
			}
#print $oTimer->endp('aazz html');
		}
		return array( $ar_az, $ar_aazz );
	}

	/**
	 * Generates CAPTCHA image.
	 * 
	 * @uses $oDb, $oFunc, str_random().
	 */
	public function make_captcha()
	{
		if (file_exists($this->V->path_includes.'/func_make_captcha.php'))
		{
			include_once($this->V->path_includes.'/func_make_captcha.php');
			return array($this->V->server_dir.'/'.$filename);
		}
	}
	/**
	 * Generates a random string.
	 */
	static function str_random($alphabet, $maxchar = 8)
	{
		$str = '';
		$alphabet = ($alphabet) ? $alphabet : '23456789bdghkmnqsuvxyz';
		$alphabet = str_shuffle($alphabet);
		$len = strlen($alphabet);
		for ($i = 0; $i < $maxchar; $i++)
		{
			$sed = mt_rand(0, $len-1);
			$str .= $alphabet[$sed];
		}
		return $str;
	}
	


	/* */
	public function create_navbar()
	{
		if ( defined('SITE_THIS_SCRIPT') && SITE_THIS_SCRIPT == $this->V->file_index )
		{
			return;
		}
		#$ar_menu[$this->oTkit->_( '1000' )][] = '';
		
		$ar_menu[$this->oTkit->_( '1000' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => '' ),
				array( 'title' => $this->oTkit->_( '1152' ) ),
				$this->oTkit->_( '1152' )
		);
		/* Add */
		$ar_menu[$this->oTkit->_( '1001' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.add,t.items' ),
				array(),
				$this->oTkit->_( '1003' )
		);
		$ar_menu[$this->oTkit->_( '1001' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.add,t.infoblocks' ),
				array( 'title' => $this->oTkit->_( '1054' ) ),
				$this->oTkit->_( '1054' )
		);
		/* Manage */
		$ar_menu[$this->oTkit->_( '1006' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.manage,t.items' ),
				array( 'title' => $this->oTkit->_( '1003' ) ),
				$this->oTkit->_( '1003' )
		);
		$ar_menu[$this->oTkit->_( '1006' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.manage,t.fields' ),
				array( 'title' => $this->oTkit->_( '1020' ) ),
				$this->oTkit->_( '1020' )
		);
		$ar_menu[$this->oTkit->_( '1006' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.manage,t.infoblocks' ),
				array( 'title' => $this->oTkit->_( '1054' ) ),
				$this->oTkit->_( '1054' )
		);
		$ar_menu[$this->oTkit->_( '1006' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.manage,t.az' ),
				array( 'title' => $this->oTkit->_( 1209 ) ),
				$this->oTkit->_( 1209  )
		);
		$ar_menu[$this->oTkit->_( '1006' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.manage,t.translations' ),
				array( 'title' => $this->oTkit->_( 1190 ) ),
				$this->oTkit->_( 1190 )
		);


		/* Setup */
		$ar_menu[$this->oTkit->_( 1007 )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.setup,t.systemsettings' ), 
				array( 'title' => $this->oTkit->_( 1040 ) ),
				$this->oTkit->_( 1040 )
		);
		
		/* Service */
		/* Service - Maintenance */
		$ar_menu[$this->oTkit->_( 1008 )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 'a.mnt,t.service' ), 
				array( 'title' => $this->oTkit->_( 1110 ) ),
				$this->oTkit->_( 1110 )
		);
		/* Service - Localization */
		$oJsMenu = new site_jsMenu();
		$oJsMenu->icon = $this->oTkit->_( 1183 ).$this->V->str_class_dropdown;
		$oJsMenu->event = 'onmouseover';
		$oJsMenu->classname = '';
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.manage'."\x01\x01".'t.langs' ), $this->oTkit->_( 1181 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.manage'."\x01\x01".'t.tvs' ), $this->oTkit->_( 1182 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.manage'."\x01\x01".'t.translations' ), $this->oTkit->_( 1190 ) );
 		$ar_menu[$this->oTkit->_( 1008 )][] = $oJsMenu->get_html();

		/* Exchange - Export */
		$oJsMenu = new site_jsMenu();
		$oJsMenu->icon = $this->oTkit->_( 1079 ).$this->V->str_class_dropdown;
		$oJsMenu->event = 'onmouseover';
		$oJsMenu->classname = '';
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.export'."\x01\x01".'t.items' ), $this->oTkit->_( 1003 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.export'."\x01\x01".'t.infoblocks' ), $this->oTkit->_( 1054 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.export'."\x01\x01".'t.langs' ), $this->oTkit->_( 1181 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.export'."\x01\x01".'t.tvs' ), $this->oTkit->_( 1182 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.export'."\x01\x01".'t.az' ), $this->oTkit->_( 1209 ) );
 		$ar_menu[$this->oTkit->_( 1009 )][] = $oJsMenu->get_html();
		
		/* Exchange - Import */
		$oJsMenu = new site_jsMenu();
		$oJsMenu->icon = $this->oTkit->_( 1077 ).$this->V->str_class_dropdown;
		$oJsMenu->event = 'onmouseover';
		$oJsMenu->classname = '';
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.import'."\x01\x01".'t.items' ), $this->oTkit->_( 1003 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.import'."\x01\x01".'t.infoblocks' ), $this->oTkit->_( 1054 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.import'."\x01\x01".'t.langs' ), $this->oTkit->_( 1181 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.import'."\x01\x01".'t.tvs' ), $this->oTkit->_( 1182 ) );
		$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=a.import'."\x01\x01".'t.az' ), $this->oTkit->_( 1209 ) );
		$ar_menu[$this->oTkit->_( 1009 )][] = $oJsMenu->get_html();
 			

		/* Right-side menu*/
		$ar_menu_right[$this->oTkit->_( '1011' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 't.user,a.edit,s.pass'  ),
				array( 'title' => $this->oTkit->_( '1012' ) ),
				$this->oTkit->_( '1012' )
		);
		$ar_menu_right[$this->oTkit->_( '1011' )][] =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, 'arg[area]' => 't.user,a.edit,s.profile'  ),
				array( 'title' => $this->oTkit->_( '1013' ) ),
				$this->oTkit->_( '1013' )
		);
		/* */
		$ar_str = $ar_js_menublocks = array();
		$ar_str[] = '<ul>';
		$ar_str[] = '<li><p></p></li>';
		$cnt = 1;
		$cnt_current = 0;
		$ar_str_sub = array();
		foreach ($ar_menu as $str_menu => $ar_a)
		{
			/* @temp Indicators */
			foreach ( $ar_a as $sub_k => $sub_v )
			{
				
				$cmp_1 = preg_replace( '/href="(.*?)"(.*)/', "\\1", $sub_v );
				if ( strlen($this->gv['_area']) > 1 && strpos( $cmp_1, '='.$this->gv['_area'] ) !== false )
				{
					$ar_a[$sub_k] = str_replace( 'href=', 'class="on" href=', $sub_v );
					$cnt_current = $cnt;
				}
			}
			$ar_str[] = '<li>'.$this->oHtmlAdm->a_href(
				array( 'javascript:void(0)'  ),
				array( 'title' => $str_menu, 'onclick' => 'oGwNavbar.sub(this)', 'id' => 'a-'.$cnt ),
				$str_menu
			).'</li>';
			$ar_str_sub[] = '<div id="ch-'.$cnt.'" class="gw-navbar-sub hidden"><ul><li>'.implode( '</li><li>', $ar_a ).'</li></ul></div>';
			$ar_js_menublocks[] = $cnt;
			++$cnt;
		}
		
		$ar_str[] = '<li class="navbar-right"><p></p></li>';
		foreach ($ar_menu_right as $str_menu => $ar_a)
		{
			/* Indicators */
			/* @temp */
			foreach ( $ar_a as $sub_k => $sub_v )
			{
				if ( strlen($this->gv['_area']) > 1 && strpos($sub_v, '='.$this->gv['_area'] ) !== false )
				{
					$ar_a[$sub_k] = str_replace( 'href=', 'class="on" href=', $sub_v );
					$cnt_current = $cnt;
				}
			}
			$ar_str[] = '<li class="navbar-right">'.$this->oHtmlAdm->a_href(
				array( 'javascript:void(0)'  ),
				array( 'title' => $str_menu, 'onclick' => 'oGwNavbar.sub(this)', 'id' => 'a-'.$cnt ),
				$str_menu
			).'</li>';
			$ar_str_sub[] = '<div id="ch-'.$cnt.'" class="navbar-right gw-navbar-sub hidden"><ul><li>'.implode( '</li><li>', $ar_a ).'</li></ul></div>';
			$ar_js_menublocks[] = $cnt;
			++$cnt;
		}
		
		$this->oOutput->append_js( 'jsF.Set( "menublocks", "['.implode( ',', $ar_js_menublocks ).']" );' );
		
		/* Highlight the current section */
		if ( $cnt_current )
		{
			$this->oOutput->append_js( ' oGwNavbar.sub( fn_getElementById(\'a-'.$cnt_current.'\') ); ' );
		}
		else
		{
			$this->oOutput->append_js( ' oGwNavbar.sub( fn_getElementById(\'a-1\') ); ' );
		}
		
		$ar_str[] = '</ul>';

#		prn_r( $ar_str );
		#prn_r( $ar_str_sub );
		
		return implode( '', $ar_str ).implode( '', $ar_str_sub );
		
	}
	
	/** 
	 * Generates sidebar for user.
	 * 
	 * A temporary version.
	 */
	public function create_sidebar_menu()
	{
		$s = '';



		return $s;
	}
	


	/**
	 * Update some user stats.
	 */
	public function stat_user_rebuild($id_user)
	{
		$q__users['cnt_bytes'] = $this->stat_items_bytes($id_user);
		$q__users['cnt_terms'] = $this->stat_items_num($id_user);
		$this->oDb->update( $this->V->db_table_users, $q__users, array('id_user' => $id_user) );
	}
	/**
	 * Calculate total number of bytes.
	 */
	public function stat_items_bytes($id_user)
	{
		$this->oDb->select( 'SUM(cnt_bytes) as sum_bytes' );
		$this->oDb->from( array('contents') );
		$this->oDb->where( array('id_user_created' => $id_user) );
		$ar_sql = $this->oDb->get()->result_array();
		return isset($ar_sql[0]['sum_bytes']) ? $ar_sql[0]['sum_bytes'] : 0;
	}
	/**
	 * Calculate total number of items.
	 */
	public function stat_items_num($id_user)
	{
		$this->oDb->select( 'COUNT(*) as cnt_items' );
		$this->oDb->from( array('items') );
		$this->oDb->where( array('item_id_user_created' => $id_user) );
		$ar_sql = $this->oDb->get()->result_array();
		return isset($ar_sql[0]['cnt_items']) ? $ar_sql[0]['cnt_items'] : 0;
	}
	
	
	/**
	 * HTML-code for Meta Refresh
	 */
	public static function gethtml_meta_refresh($url, $delay = 1)
	{
		return '<meta http-equiv="refresh" content="'.$delay.';url='.$url.'" />';
	}
	/**
	 * "Soft" redirect to another location.
	 */
	public function soft_redirect($text = '', $url, $classname = '')
	{
		if ($classname == GW_COLOR_FALSE)
		{
			$classname = GW_COLOR_FALSE.' error';
		}
		else
		{
			$this->oTpl->assign( 'v:meta_refresh', $this->gethtml_meta_refresh($url, $this->V->time_refresh) );
			$classname = $classname.' updated';
		}
		return '<div class="'.$classname.'" id="status">'.$text.
		'<p><a href="'.$url.'">'.$this->oTkit->_(1050).'</a></p></div>';
	}



	
	/* */
	public function get_perm_map()
	{
		$ar_perms_map = array();
		$ar_perms_map[$this->oTkit->_(1211)]['sys-settings'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1211)]['sys-mnt'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1211)]['users'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1312)]['items'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1312)]['items-own'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1145)]['email'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1145)]['login'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1145)]['password'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1145)]['profile'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1145)]['pm'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1313)]['comments'] = $this->oTkit->_( );
		$ar_perms_map[$this->oTkit->_(1313)]['comments-own'] = $this->oTkit->_( );
		return $ar_perms_map;
	}
	
	/** 
	 * Draws progressbar in HTML+CSS 
	 *
	 * @param	int	$percent
	 * @param	text	$color_txt Progress bar text color
	 * @param	text	$color_bg Progress bar Background color
	 * @return	text	HTML-code
	 */
	public static function text_progressbar($percent = 100, $color_txt = '#000', $color_bg = '#6C3')
	{
		if ($percent > 100){ $percent = 100; }
		return '<div style="text-align:center;background:#F6F6F6;margin:1px 0;width:100%;border:1px solid #CCC"><div style="font:90% sans-serif;color:'.$color_txt.';background:'.$color_bg.';width:'.$percent.'%">'.$percent.'%</div></div>';
	}
	
	
	/**
	 * ----------------------------------------------
	 * Cron functions. Called on /cron.php
	 * ----------------------------------------------
	 */
	public function cron__()
	{
		if ( (mt_rand() % 100) < $this->V->prbblty_tasks )
		{
			$this->cron__user_stats();
		}
		if ( (mt_rand() % 100) < $this->V->prbblty_tasks )
		{
			$this->cron__user_clean_sessions();
		}
	}
	/* Recounts user stats */
	public function cron__user_stats()
	{
		$this->oDb->select('id_user', false);
		$this->oDb->from( array( $this->V->db_table_sessions ) );
		$this->oDb->where( array( 'mdate >' => @date($this->sdf, $this->V->time_gmt - $this->V->time_sec_h)) );
		$this->oDb->group_by('id_user');
		$this->oDb->limit( 100 );
		$ar_sql = $this->oDb->get()->result_array();

		foreach ($ar_sql as $ar_v)
		{
			$this->stat_user_rebuild($ar_v['id_user']);
		}
	}
	/* Cleans old user sessions */
	public function cron__user_clean_sessions()
	{
		# $this->oDb->delete( $this->V->db_table_sessions, array( 'mdate <' => @date($this->sdf, $this->V->time_gmt - $this->V->time_sec_w), 'is_remember' => '0' ) );
		$this->oDb->delete( $this->V->db_table_sessions, array( 'mdate <' => @date($this->sdf, $this->V->time_gmt - $this->V->time_sec_w) ) );
	}

}}

?>