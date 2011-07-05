<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-auto-seealso' );


/* Switch between output modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
		
	break;
	default:
		
		/**
		 * ----------------------------------------------
		 * Document title and <H1>
		 * ----------------------------------------------
		 */
		$this->oOutput->append_html_title( $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1042 ) );
		$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1042 ) );


		/**
		 * ----------------------------------------------
		 * Check for permissions
		 * ----------------------------------------------
		 */ 
		if ( !$this->oSess->is('items') && !$this->oSess->is('items-own') )
		{
			$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1045 ).'</div>' );
			return false;
		}
		
		/**
		 * ----------------------------------------------
		 * Load HTML-form
		 * ----------------------------------------------
		 */
		if ( file_exists( $this->cur_htmlform ) )
		{
			include_once( $this->cur_htmlform );
		}
		else
		{
			class site_htmlforms extends site_forms3_validation{}
		}
		
		/**
		 * ----------------------------------------------
		 * Select Item
		 * ----------------------------------------------
		 */
		 /* Item and Contents could have different owners */
		$this->oDb->select( 'i.id_item, i.item_id_user_created, i.item_id_user_modified, i.is_active, i.is_complete, uri.item_uri' );
		$this->oDb->select( 'c.contents_value, c.contents_a, c.id_field, c.id_contents, c.id_lang, c.id_user_created' );
		$this->oDb->select( 'i.item_cdate, i.item_mdate, CONCAT( l.isocode1,"_", l.region ) locale', false );
		$this->oDb->select( 'uc.user_fname uc_user_fname, uc.user_sname uc_user_sname, uc.user_nickname uc_user_nickname, uc.user_settings uc_user_settings' );
		$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf', 'languages l' ) );
		/* User may not exist */
		$this->oDb->join( $this->V->db_table_users.' uc', 'i.item_id_user_created = uc.id_user', 'left' );
		$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
		$this->oDb->where( array( 'c.id_lang = l.id_lang' => NULL ) );
		$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
		$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
		$this->oDb->where( array( 'mftf.id_fieldset' => '1' ) );
		$this->oDb->where( array( 'i.id_item' => $this->gv['id_item'] ) );
		$this->oDb->order_by( 'mftf.int_sort ASC' );
		$ar_sql_item = $this->oDb->get()->result_array();
		
		if ( empty( $ar_sql_item ) )
		{
			/* No such term */
			$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1046 ).'</div>' );
			return;
		}
		
		/* Detect item owner */
		$this->ar_item['item_id_user_created'] = $ar_sql_item[0]['item_id_user_created'];
		$this->ar_item['is_item_owner'] = 0;
		if ( $this->oSess->is('items-own') && $this->oSess->id_user == $this->ar_item['item_id_user_created'] )
		{
			$this->ar_item['is_item_owner'] = 1;
		}
		
		/* Check permission again */
		if ( !$this->ar_item['is_item_owner'] && !$this->oSess->is('items') )
		{
			$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1045 ).'</div>' );
			return false;
		}

		/* Re-arrange */
		$ar_item = array();
		foreach ( $ar_sql_item as $ar_v)
		{
			/* Sort keys for debug purposes */
			ksort( $ar_v );
			$ar_item[$ar_v['id_item']][$ar_v['id_field']] = $ar_v;
			/* @temp: Insert empty field */
			if ( !isset( $ar_item[$ar_v['id_item']][2] ) )
			{
				$ar_item[$ar_v['id_item']][2] = array(
					'contents_value' => '',
					'id_contents' => 0,
					'id_user_created' => 0,
					'id_lang' => $this->oFunc->get_crc_u('eng'.'US'),
				);
			}
		}
		
		if ( empty( $this->gv['arp'] ) )
		{
			/* Parse SQL into Array */
			foreach ( $ar_item as $id_item => $ar_fields_content )
			{
				/* @temp: See also  */
				if ( !isset( $ar_fields_content[3] ))
				{
					$ar_item[$id_item][3]['contents_value'] = '';
					$ar_item[$id_item][3]['id_contents'] = 0;
					$ar_item[$id_item][3]['id_user_created'] = $this->oSess->id_user;
				}
				foreach ( $ar_fields_content as $id_field => $ar_v)
				{
					switch ( $id_field )
					{
						case 1:
							$ar_item['id_item'] = $id_item;
							$ar_item['cdate'] = $ar_v['item_cdate'];
							$ar_item['mdate'] = $ar_v['item_mdate'];
							$ar_item['is_active'] = $ar_v['is_active'];
							$ar_item['is_complete'] = $ar_v['is_complete'];

						#	$contents_a = $ar_v['contents_a'];
							$ar_str_item_title[] = $ar_v['contents_value'];
							$item_uri = $ar_v['item_uri'];
							
							/* Read user`s settings */
							$ar_item_settings = $ar_v;
							$ar_item_settings['uc_user_settings'] = unserialize( $ar_v['uc_user_settings'] );
							/* Define displayed name */
							if ( !isset( $ar_item_settings['uc_user_settings']['displayed_name'] ) )
							{
								$ar_item_settings['uc_user_settings']['displayed_name'] = 2;
							}
							switch ( $ar_item_settings['uc_user_settings']['displayed_name'] )
							{
								case 1: $ar_item_settings['uc_user_displayed_name'] = $ar_v['uc_user_nickname']; break;
								case 2: $ar_item_settings['uc_user_displayed_name'] = $ar_v['uc_user_fname']; break;
								case 3: $ar_item_settings['uc_user_displayed_name'] = $ar_v['uc_user_sname']; break;
								case 4: $ar_item_settings['uc_user_displayed_name'] = $ar_v['uc_user_fname'].' '.$ar_v['uc_user_sname']; break;
								default: $ar_item_settings['uc_user_displayed_name'] = $ar_v['id_user']; break;
							}
							if ( !$ar_item_settings['uc_user_displayed_name'] )
							{
								$ar_item_settings['uc_user_displayed_name'] = $ar_v['item_id_user_created'];
							}

							$this->gv['area']['a1'] = $ar_v['contents_a'];
							$this->gv['area']['lc'] = $ar_v['locale'];

						break;
						/* More to come */
						default:
							$ar_str_item_descr[] = $ar_v['contents_value'];
						break;
					}
				}
			}
			
			/* @todo: read from user`s settings */
			$ar_item['redirect']  = 2;
			if ( $this->gv['uri'] )
			{
				$ar_item['redirect']  = 4;
			}
			
			/* @todo: Set a special flag for opened item */

			#prn_r( $ar_item );
			
			$arVF =& $ar_item;
		}

		/* Correct unknown settings */
		$ar_settings = array();
			
		/* Checkboxes */
		$ar_onoff = array( $this->gv['id_item'] => array( 1 => array( 'is_active', 'is_complete' ) ) );
		/* Required fields */
#		$ar_required = array( $this->gv['id_item'] => array( 1 => array( 'contents_value' ) ) );
		$ar_required  = array();

		/* */
		$oHtmlForms = new site_htmlforms( $this );
		$oHtmlForms->ar_onoff =& $ar_onoff;
		$oHtmlForms->ar_required =& $ar_required;
		
		/**
		 * ----------------------------------------------
		 * Select Alphabetic order
		 * ----------------------------------------------
		 */
		list( $ar_az, $ar_aazz ) = $this->items__get_az();
		$this->oOutput->append_html( '<div class="gw-az">'. implode( ' ', $ar_az ).'</div>' );
		
		
		/* */
		if ( empty( $this->gv['arp'] ) )
		{
			/* */
			foreach ($ar_settings as $k => $v)
			{
				if ( !isset( $arVF[$k] ) ) { $arVF[$k] = $v; }
			}
			$this->oOutput->append_html( $oHtmlForms->before_submit( $arVF ) );
		}
		else
		{
			$this->gv['arp']['id_item'] = $this->gv['id_item'];

			$this->oOutput->append_html( $oHtmlForms->after_submit( $this->gv['arp'] ) );
		}
		
	break;
}


?>