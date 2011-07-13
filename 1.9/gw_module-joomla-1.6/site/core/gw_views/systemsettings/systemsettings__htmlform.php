<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}


/* Display HTML-form */
class site_htmlforms extends site_forms3_validation
{
	function get_form( $ar = array() )
	{
	    /* Phrases */
		$this->phrase_wait = $this->o->oTkit->_(1082);
		$this->phrase_incorrect = $this->o->oTkit->_(1065);
		$this->phrase_submit_ok = $this->o->oTkit->_(1017);
		$this->phrase_submit_cancel = $this->o->oTkit->_(1018);

	    /* <form> settings */
		$this->set_tag( 'form', 'action', $this->o->V->server_dir_admin.'/'.$this->o->V->file_index );
		$this->set_tag( 'form', 'id', 'sys-settings' );
		$this->set_tag( 'form', 'onkeypress', 'jsF.formKeypress(event, this)' );

		$this->is_htmlspecialchars = 0;
		$this->is_actions = 0;
		$this->is_actions_top = 1;
		$this->is_label_ids = 1;
		$this->is_submit_ok = 1;
		$this->is_submit_cancel = 1;
	
		/* Display `Settings saved` notice */
		if ( isset( $this->o->gv['area']['is_saved'] ) && $this->o->gv['area']['is_saved'] ) 
		{
			$this->o->notice_onsubmit( $this->o->oTkit->_( 1041 ), true );
		}

		$this->set_tag( 'input', 'class', 'inp' );
		/* */
		$this->new_fieldset( 'meta', $this->o->oTkit->_( '' ) );
		$this->new_label( $this->o->oTkit->_( 1170 ), $this->field( 'input', 'arp[meta_title]', $ar['meta_title'] ) );
		
		/* */
		$this->new_fieldset( 'search', $this->o->oTkit->_( '' ) );
		
		$this->set_tag( 'input', 'class', 'inp w50' );
		$ar_max_chars_preview = array( '0' => $this->o->oTkit->_( 1142 ), '100' => 100, '250' => 250, '500' => 500, '-1' => $this->o->oTkit->_( 1143 ) );
		$this->set_tag( 'select', 'class', 'inp w50' );
		$this->set_tag( 'select', 'style', '' );
		$this->set_tag( 'select', 'onchange', 'gw_sub_options(this)' );
		$this->new_label( $this->o->oTkit->_( 1141 ), $this->field( 'select', 'arp[int_max_chars_preview]', $ar['int_max_chars_preview'], $ar_max_chars_preview ) );
		$this->set_tag( 'select', 'onchange', '' );
		$this->o->oOutput->append_js('function gw_sub_options(el){ 
			fn_getElementById("l-arp-is-link-item-").style.display = "none";
			if ( el.options.selectedIndex == 4 || el.options.selectedIndex == 0 ) { fn_getElementById("l-arp-is-link-item-").style.display = "block"; }
		};
		gw_sub_options( fn_getElementById("arp-int-max-chars-preview-") );
		');
		/* Place the link to term */
		$this->set_tag( 'checkbox', 'style', 'margin-left:4em' );
		$this->new_label( $this->o->oTkit->_( 1078 ), $this->field( 'checkbox', 'arp[is_link_item]', (bool) $ar['is_link_item'] ) );
		$this->set_tag( 'checkbox', 'style', '' );

		/* */
		$ar_items_per_page = array( 5 => 5, 10 => 10, 20 => 20, 25 => 25, 50 => 50, 100 => 100 );
		$this->new_label( $this->o->oTkit->_( 1163 ), $this->field( 'select', 'arp[items_per_page]', $ar['items_per_page'], $ar_items_per_page  ) );
		
		/* */
		$ar_search_max = array( 
			1 => 1, 
			100 => 100, 
			1000 => $this->o->oTkit->number_format( 1000 ), 
			5000 => $this->o->oTkit->number_format( 5000 ), 
			10000 => $this->o->oTkit->number_format( 10000 ), 
			25000 => $this->o->oTkit->number_format( 25000 ), 
			50000 => $this->o->oTkit->number_format( 50000 ),
			100000 => $this->o->oTkit->number_format( 100000 )
		);
		$this->new_label( $this->o->oTkit->_( 1164 ), $this->field( 'select', 'arp[int_search_max]', $ar['int_search_max'], $ar_search_max ) );
		
		/* Visual -> AZ */
		$this->set_tag( 'checkbox', 'onclick', 'gw_sub_options_az(this)' );
		$this->new_label( $this->o->oTkit->_( 1216 ), $this->field( 'checkbox', 'arp[is_show_az]', (bool) $ar['is_show_az'] ) );
		$this->set_tag( 'checkbox', 'onclick', '' );
		$this->set_tag( 'radio', 'id', 'arp-az-location-t-' );
		$this->set_tag( 'radio', 'style', 'margin-left:4em' );
		$this->set_tag( 'radio', 'value', 't' );
		$this->new_label( $this->o->oTkit->_( 1057 ).': '.$this->o->oTkit->_( 1059 ), $this->field( 'radio', 'arp[az_location]', $ar['az_location'] == 't' ) );
		$this->set_tag( 'radio', 'id', 'arp-az-location-l-' );
		$this->set_tag( 'radio', 'style', 'margin-left:4em' );
		$this->set_tag( 'radio', 'value', 'l' );
		$this->new_label( $this->o->oTkit->_( 1057 ).': '.$this->o->oTkit->_( 1214 ), $this->field( 'radio', 'arp[az_location]', $ar['az_location'] == 'l' ) );
		$this->set_tag( 'radio', 'id', 'arp-az-location-r-' );
		$this->set_tag( 'radio', 'style', 'margin-left:4em' );
		$this->set_tag( 'radio', 'value', 'r' );
		$this->new_label( $this->o->oTkit->_( 1057 ).': '.$this->o->oTkit->_( 1215 ), $this->field( 'radio', 'arp[az_location]', $ar['az_location'] == 'r' ) );
		$this->set_tag( 'radio', 'style', '' );
		$this->o->oOutput->append_js('function gw_sub_options_az(el){ 
			for ( var i = 0, ar = ["arp-az-location-t-", "arp-az-location-l-", "arp-az-location-r-"]; i < ar.length; i++ ) {
				fn_getElementById( "l-" + ar[i] ).style.display = ( el.checked ? "block" : "none" );
				fn_getElementById( ar[i] ).disabled = ( el.checked ? false : "disabled" );
			}
		};
		gw_sub_options_az( fn_getElementById("arp-is-show-az-") );
		');
		
		
		/* */
		$this->new_fieldset( 'sef', $this->o->oTkit->_( 1180 ) );
		$ar_link_modes = array(
			GW_LINK_ID => $this->o->oTkit->_( 1158 ).' (id.123)',
			GW_LINK_URI => $this->o->oTkit->_( 1159 ).' (id.123-term)',
			GW_LINK_TEXT => $this->o->oTkit->_( 1160 ).' (id.Term)'
		);
		$this->new_label( $this->o->oTkit->_( 1157 ), $this->field( 'select', 'arp[link_mode]', $ar['link_mode'], $ar_link_modes  ) );
		$this->new_label( $this->o->oTkit->_( 1161 ), $this->field( 'input', 'arp[link_template_text]', $ar['link_template_text'] ), $this->o->oTkit->_( 1178 ) );
		$this->new_label( $this->o->oTkit->_( 1162 ), $this->field( 'input', 'arp[link_template_uri]', $ar['link_template_uri'] ), $this->o->oTkit->_( 1179 ) );
		
		/* */
		$this->new_fieldset( 'debug', $this->o->oTkit->_( 1130 ) );
		foreach( array( 'is_debug_time' => 1165, 'is_debug_db' => 1166, 'is_debug_cache' => 1167 ) as $f => $l )
		{
			$this->new_label( $this->o->oTkit->_( $l ), $this->field( 'checkbox', 'arp['.$f.']', (bool) $ar[$f] ) );
		}
		
		/* */
		$this->new_fieldset( 'cache', $this->o->oTkit->_( 1108 ) );
		$ar_time_cache = array( 
			'60' => $this->o->oTkit->_( 1144, 1 ),
			'300' => $this->o->oTkit->_( 1144, 5 ),
			'600' => $this->o->oTkit->_( 1144, 10 ),
			'1800' => $this->o->oTkit->_( 1144, 30 ),
			'3600' => $this->o->oTkit->_( 1145, 1 ),
			'7200' => $this->o->oTkit->_( 1145, 2 ),
			'21600' => $this->o->oTkit->_( 1145, 6 ),
			'86400' => $this->o->oTkit->_( 1145, 24 ),
		);
		foreach( array( 'time_cache_az' => 1168, 'time_cache_html' => 1169 ) as $f => $l )
		{
			$this->new_label( $this->o->oTkit->_( $l ), $this->field( 'select', 'arp['.$f.']', $ar[$f], $ar_time_cache ) );
		}
		
		/* */
		#$this->set_tag( 'input', 'class', 'inp' );
		#$this->new_fieldset( 'feedback', $this->o->oTkit->_( '' ) );
		#foreach( array('feedback_from_name', 'feedback_from_email', 'feedback_to_name', 'feedback_to_email', 
		#	'feedback_subject_prefix', 'str_mailer') as $f )
		#{
		#	$this->new_label( $this->o->oTkit->_( $f ), $this->field( 'input', 'arp['.$f.']', $ar[$f] ) );
		#}
		
		/* */
		#$this->new_fieldset( 'html', $this->o->oTkit->_( '' ) );
		#foreach( array('str_class_dropdown', 'str_class_dropdownmenu', 'str_class_edit', 'str_class_remove') as $f )
		#{
		#	$this->new_label( $this->o->oTkit->_( $f ), $this->field( 'input', 'arp['.$f.']', $ar[$f] ) );
		#}
		
		/* */
		$this->field( 'hidden', 'arg[action]', $this->o->gv['action'] );
		$this->field( 'hidden', 'arg[target]', $this->o->gv['target'] );
		/* Append URL */
		if ( !empty( $this->o->V->sef_append ) )
		{
			foreach ( $this->o->V->sef_append as $k1 => $v1 )
			{
				$this->field( 'hidden', $k1,  $v1 );
			}
		}
		$this->field( 'hidden', 'arg[uri]', $this->o->gv['uri'] );
		$this->field( 'hidden', 'arp[form]', '1' );
	
		return $this->form_output();
	}
	/* */
	public function on_success($ar)
	{
		$ar = $this->check_onoff( $ar );
		
		unset( $ar['form'] );
		/* */
		foreach ($ar as $k => $v)
		{
			$this->o->oDb->update( 'settings', array( 'value' => $v ), array( 'id_varname' => $k ) );
		}

		/* Redirect */
		$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.setup'."\x01\x01".'t.systemsettings'."\x01\x01".'is_saved.1&r='.$_SERVER['REQUEST_TIME'] );
		$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, 0 );

		/* */
		#$this->o->oOutput->append_html( $this->o->soft_redirect(
		#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
		#));
	}
}

?>