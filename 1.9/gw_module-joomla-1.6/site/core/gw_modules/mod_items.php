<?php
/* The common functions for $target */
class gw_mod_items extends site_prepend
{
	public $parse__is_preview = 1;
	/* */
	public function autoexec()
	{
		if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
		{
			$this->oTpl->assign( 'v:h_tabs', '<div class="gw-actions">'.implode(' ', $this->get_actions() ).'</div>' );
		}
	}
	/* */
	public function get_statuses()
	{
 		return array( 
			GW_STATUS_OFF => $this->oTkit->_( 1070 ), 
			GW_STATUS_ON => $this->oTkit->_( 1069 ), 
			GW_STATUS_PENDING => $this->oTkit->_( 1071 ), 
			GW_STATUS_REMOVE => $this->oTkit->_( 1073 ) 
		);
	}
	/* */
	public function get_statuses_classnames()
	{
 		return array( 
			GW_STATUS_OFF => 'state-warning', 
			GW_STATUS_ON => 'state-allow', 
			GW_STATUS_PENDING => 'state-wait', 
			GW_STATUS_REMOVE => 'state-warning' 
		);
	}
	/* */
	public function get_actions()
	{
		$oHref = $this->oHtmlAdm->oHref();
		$oHref->set( 't', $this->gv['target'] );
		$ar_ctrl = array();
		if ( $this->oSess->is( 'items-own' ) || $this->oSess->is( 'items' ) )
		{
			$oHref->set( 'a', 'add' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1001 ).': '.$this->oTkit->_( 1003 ) ),
				$this->oTkit->_( 1001 )
			);
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1003 ) ),
				$this->oTkit->_( 1006 )
			);
			$oHref->set( 'a', 'import' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1077 ).': '.$this->oTkit->_( 1003 ) ),
				$this->oTkit->_( 1077 )
			);
			$oHref->set( 'a', 'export' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1079 ).': '.$this->oTkit->_( 1003 ) ),
				$this->oTkit->_( 1079 )
			);
		}
		return $ar_ctrl;
	}
	/* */
	public function parse_ar_item( $ar_item )
	{
		/*
		$ar_item[$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;

		[id_field1]
			[id_contents1]
				[contents_value_cached1]
		[id_field2]
			[id_contents2]
				[contents_value_cached2]
			[id_contents3]
				[contents_value_cached3]
		-----------
		[id_field1]
			[contents_value_cached1]
		[id_field2]
			[contents_value_cached2] [contents_value_cached3]
		*/

		$str_item = $str_item_cut = $str_item_url = $str_descr = $str_descr_cut = $contents_a = '';
		$ar_defn_complete = $ar_item_settings = array();
		$str_is_complete_single = '';
		foreach ( $ar_item as $id_field => $ar_content)
		{
			$str_is_complete = '';
			$ar_str_field = array();
			$fields_separator = ' ◊ ';
			foreach ( $ar_content as $id_contents => $ar_v)
			{
				if ( $this->V->id_field_root == $id_field )
				{
					if ( !$ar_v['is_complete'] )
					{
						$str_is_complete = $str_is_complete_single = '<strong>?</strong> ';
					}
					$ar_item_settings = $ar_v;
					$contents_a = $ar_v['contents_a'];
				}
				/* */
				switch ( $id_field )
				{
					case 2: /** Definition **/
						$ar_str_field[] = '<div>'.$ar_v['contents_value_cached'].'</div>';
					break;
					case 3: /** See Also **/
						if ( $ar_v['contents_value_cached'] != '' )
						{
							$oHref = $this->oHtml->oHref();
							$oHref->set( 't', 'items' );
							$oHref->set( 'a', 'search' );
							$oHref->set( 'q', $ar_v['contents_value_cached'] );
							$ar_str_field[] = $this->oHtml->a_href(
								array( $this->V->file_index, '#area' => $oHref->get() ),
								array( ), $ar_v['contents_value_cached'] 
							);
						}
						$fields_separator = ', ';
					break;
					default:
						$ar_str_field[] = $ar_v['contents_value_cached'];
					break;
				}
			}
			$str_field = implode( $fields_separator, $ar_str_field );
			
			
			if ( $this->parse__is_preview )
			{
				$str_field = implode( $fields_separator, $ar_str_field );
			}
			/* */
			if ( $this->V->id_field_root == $id_field )
			{
				/* At once */
				$str_item = $str_field;
				$str_item_cut = $this->oFunc->smart_substr( strip_tags( $str_item ), 0, 32 );
				/* View item */
				switch ( $this->V->link_mode )
				{
					case GW_LINK_ID: $item_uri = $ar_item_settings['id_item']; break;
					case GW_LINK_URI: $item_uri = $ar_item_settings['item_uri']; break;
					case GW_LINK_TEXT: $item_uri = $this->oHtml->urlencode( $str_item ); break;
				}
				/* */
				if ( $this->V->link_template_uri != '' && $this->V->link_mode != GW_LINK_ID )
				{
					$item_uri = str_replace( '%s', $item_uri, $this->V->link_template_uri );
				}
				/* Place link to item */
				$str_item_url = $this->oHtml->a_href( array( $this->V->file_index, '#area' => 'id.'.$item_uri ), array(), $str_item );
				if ( !$this->V->is_link_item 
					&& ( $this->V->int_max_chars_preview == "-1" || $this->V->int_max_chars_preview == "0" )
					)
				{
					$str_item_url = $str_item;
				}
				/* Edit item */
				$str_item_url_admin = '';
				if ( SITE_ADMIN_MODE
					|| $this->oSess->is( 'items' ) 
					|| ( $this->oSess->is( 'items-own' ) && $ar_item_settings['item_id_user_created'] == $this->oSess->id_user ) 
					)
				{
					$oHref = $this->oHtmlAdm->oHref();
					$oHref->set( 'a', 'edit' );
					$oHref->set( 't', 'items' );
					$oHref->set( 'id_item', $ar_v['id_item'] );
					if ( SITE_ADMIN_MODE )
					{
						/* Link to Edit Item */
						$str_item_url = $str_is_complete . $this->oHtmlAdm->a_href(
									array( $this->V->file_index, '#area' => $oHref->get(), '#uri' => base64_encode( $this->V->uri ) ),
									array( 'class' => 'btn edit' ), $str_item . $this->V->str_class_edit
							);
					}
					else
					{
						$str_item_url_admin = $this->oHtmlAdm->a_href(
									array( $this->V->file_index, '#area' => $oHref->get(), '#uri' => base64_encode( $this->V->uri ) ),
									array( 'class' => 'btn edit' ), $this->oTkit->_( 1042 ) . $this->V->str_class_edit
							);
						/* Link to Item and link to Edit Item onmouseover */
						$str_item_url = $str_is_complete . '<span class="hide-over">'. $str_item_url
							. ' <span class="hide-under">'. $str_item_url_admin. '</span></span>';
					}
				}
			}
			/* Collect fields for a common definition */
			switch ( $id_field )
			{
				/* Do not include Item into Definition text */
				case 1:
				break;
				case 3: /** See Also **/
					$ar_defn_complete[] = '<div><em>'.$this->oTkit->_( 1076 ) .':</em> ' . $str_field .'</div>';
				break;
				default:
					$ar_defn_complete[] = $str_field;
				break;
			}
		}
		$str_descr = implode( ' ', $ar_defn_complete );

		/* */
		if ( $this->parse__is_preview )
		{
			/* Add space for tags */
			$str_descr = str_replace( '><', '> <', $str_descr);
			/* Strip all tags from definition */
			$str_descr = strip_tags( $str_descr );
		}

		/* Smart substring */
		if ( $this->parse__is_preview && $this->oFunc->mb_strlen( $str_item ) > $this->V->int_max_chars_preview )
		{
			$str_item = $this->oFunc->smart_substr( $str_item, 0, $this->V->int_max_chars_preview );
		}
		if ( $this->parse__is_preview &&  $this->oFunc->mb_strlen( $str_descr ) > $this->V->int_max_chars_preview )
		{
			$str_descr = $this->oFunc->smart_substr( $str_descr, 0, $this->V->int_max_chars_preview );
		}
		$str_descr_cut = $this->oFunc->smart_substr( strip_tags( $str_descr ), 0, 32 );

		/* Do not link to an empty term */
		if ( SITE_WEB_MODE && $str_descr == '' )
		{
			/* Enable to edit not linked term */
			if ( $str_item_url_admin )
			{
				$str_item_url = '<span class="hide-over">'. $str_item .' <span class="hide-under">'. $str_item_url_admin .'</span></span>';
			}
			else
			{
				$str_item_url = $str_item;
			}
		}
		/* No preview */
		if ( $this->V->int_max_chars_preview == 0 )
		{
			$str_descr = '';
		}
		
		/* Complete / Incomplete Mark for a single term */
		$str_item = $str_is_complete_single . $str_item;

		/* Read user settings */
		$ar_item_settings['uc_user_displayed_name'] = $ar_item_settings['item_id_user_created'];
		if ( isset( $ar_item_settings['uc_user_settings'] ) )
		{
			$ar_item_settings['uc_user_settings'] = unserialize($ar_v['uc_user_settings']);
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
		}
		$ar_item_settings['contents_a'] = $contents_a;
		/*
		New variables:
		$ar_item_settings, $str_item, $str_item_cut, $str_item_url, $str_descr, $str_descr_cut, $contents_a 
		*/
		return array( $ar_item_settings, $str_item, $str_item_cut, $str_item_url, $str_descr, $str_descr_cut );
	}
	
}

?>