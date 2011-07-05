<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}



/* */
class site_htmlforms extends site_forms3_validation
{
	/* make html-code */
	function get_form($ar)
	{
		$this->phrase_wait = $this->o->oTkit->_( 1082 );
		$this->phrase_incorrect = $this->o->oTkit->_( 1065 );
		$this->phrase_submit_ok = $this->o->oTkit->_( 1017 );
		$this->phrase_submit_cancel = $this->o->oTkit->_( 1018 );

		$this->set_tag( 'form', 'action', $this->o->V->server_dir_admin.'/'.$this->o->V->file_index );
		$this->set_tag( 'form', 'id', $this->o->gv['action'] );
		$this->set_tag( 'form', 'onkeypress', 'jsF.formKeypress(event, this)' );

		$this->is_htmlspecialchars = 0;
		$this->is_actions = 0;
		$this->is_actions_top = 1;
		$this->is_label_ids = 1;
		$this->is_submit_ok = 1;
		$this->is_submit_cancel = 1;

		/* Switch between actions */
		switch ( $this->o->gv['action'] )
		{
			case 'add':
				$this->phrase_submit_ok = $this->o->oTkit->_( 1001 );
			
				/* Group of options */
				$this->new_fieldset( 'select-lang', $this->o->oTkit->_( 1209 ) );
				
				/* Select languages NOT mentioned in a custom alphabetic order */
				$this->o->oDb->select( 'l.isocode1, l.region, l.id_lang, CONCAT(l.lang_name," - ", l.lang_native) lang', false );
				$this->o->oDb->from( array( 'languages l' ) );
				$this->o->oDb->join( 'az_letters az', 'az.id_lang = l.id_lang', 'left' );
				$this->o->oDb->where( array( 'az.id_lang is NULL' => NULL ) );
				$this->o->oDb->group_by( 'l.id_lang' );
				$this->o->oDb->order_by( 'l.lang_name' );
				$ar_sql = $this->o->oDb->get()->result_array();

				$ar_languages = array();
				foreach ( $ar_sql as $ar_v )
				{
					$ar_languages[$ar_v['id_lang']] = $ar_v['lang'];
				}
				
				/* Languages */
				$this->set_tag( 'select', 'class', 'inp' );
				$url_manage_lang = '';
				if ( $this->o->oSess->is( 'sys-settings' ) )
				{
					$url_manage_lang = $this->o->oHtmlAdm->a_href(
							array( $this->o->V->file_index, '#area' => 'a.manage'."\x01\x01".'t.langs'  ), 
							array( 'class' => 'btn add' ),
							$this->o->oTkit->_( 1006 )
					);
				}
				if ( empty( $ar_languages ) )
				{
					$this->new_label( $this->o->oTkit->_( 1155 ), '' );
					$this->is_submit_ok = $this->is_submit_cancel = 0;
				}
				else
				{
					$this->new_label( $this->o->oTkit->_( 1028 ),
						$this->field( 'select', 'arp[id_lang]', $ar_v['id_lang'], $ar_languages )
						. $url_manage_lang
					);
				}
				
				
			break;
			case 'export':
				$this->new_fieldset( 'project-languages', $this->o->oTkit->_( 1209 ) );
			
				$this->phrase_submit_ok = $this->o->oTkit->_( 1079 );
			
				$filename = 'gw-'.$this->o->V->version.'-custom_az-<strong id="lang-code">&#160;</strong>.xml';

				/* Select languages mentioned in a custom alphabetic order */
				$this->o->oDb->select( 'l.isocode1, l.region, l.id_lang, CONCAT(l.lang_name," - ", l.lang_native) lang', false );
				$this->o->oDb->from( array( 'az_letters az', 'languages l' ) );
				$this->o->oDb->where( array( 'l.id_lang = az.id_lang' => NULL ) );
				$this->o->oDb->group_by( 'az.id_lang' );
				$this->o->oDb->order_by( 'l.lang_name' );
				$ar_sql = $this->o->oDb->get()->result_array();
				
				foreach ( $ar_sql as $ar_v )
				{
					$lang_code = $ar_v['isocode1'] .'_'. $ar_v['region'];
					$this->set_tag( 'radio', 'value', $ar_v['id_lang'] );
					$this->set_tag( 'radio', 'id', 'id_lang-'.$ar_v['id_lang'].'-' );
					$this->set_tag( 'radio', 'onclick', 'jsF.inner_text( fn_getElementById(\'lang-code\'), \''.$lang_code.'\' )');
					$this->new_label( $ar_v['lang'], $this->field( 'radio', 'arp[id_lang_export]', false ) );
					
				}

				#$this->o->oOutput->append_js( 'jsF.inner_text( fn_getElementById(\'lang-code\'), \''.$lang_code.'\');' );
		
				$this->new_fieldset( 'tvs-settings', $this->o->oTkit->_( 1198 ) );
				$this->new_label( $this->o->oTkit->_( 1080 ), '<em class="disabled">'. $this->o->V->path_temp_abs .'/'. $this->o->V->path_export .'/'.$filename.'</em>' );
				$this->new_label( $this->o->oTkit->_( 1081 ), $this->field( 'checkbox', 'arp[is_save]', $ar['is_save'] ) );
				
			break;
			case 'import':
				/* Group of options */
				$this->new_fieldset( 'select-source', $this->o->oTkit->_( 1127 ) );
				
				/* Allow to upload files */
				$this->set_tag( 'form', 'enctype', 'multipart/form-data' );

				$this->phrase_submit_ok = $this->o->oTkit->_( 1077 );
				
				$this->set_tag( 'textarea', 'rows', 10 );
				$this->set_tag( 'textarea', 'style', 'font: 80% consolas,monospace;' );
				$this->o->oOutput->append_js( 'oAz.form_init();' );
				
				/* Source: Direct input */
				if ( $ar['source-direct'] ) { $this->o->oOutput->append_js( 'oAz.form_select(\'subfieldset-source-direct\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-direct-' );
				$this->set_tag( 'radio', 'value', 'direct' );
				$this->set_tag( 'radio', 'onclick', 'oAz.form_select(\'subfieldset-source-direct\');' );
				$this->new_label( $this->o->oTkit->_( 1085 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-direct']) );
				
				/* Source: A local file */
				if ( $ar['source-localfile'] ) { $this->o->oOutput->append_js( 'oAz.form_select(\'subfieldset-source-localfile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-localfile-' );
				$this->set_tag( 'radio', 'value', 'localfile' );
				$this->set_tag( 'radio', 'onclick', 'oAz.form_select(\'subfieldset-source-localfile\');' );
				$this->new_label( $this->o->oTkit->_( 1087 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-localfile']) );
				
				/* Source: A remote file */
				if ( $ar['source-remotefile'] ) { $this->o->oOutput->append_js( 'oAz.form_select(\'subfieldset-source-remotefile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-remotefile-' );
				$this->set_tag( 'radio', 'value', 'remotefile' );
				$this->set_tag( 'radio', 'onclick', 'oAz.form_select(\'subfieldset-source-remotefile\');' );
				$this->new_label( $this->o->oTkit->_( 1109 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-remotefile']) );

				/* Options: Direct input */
				$this->set_tag( 'textarea', 'class', 'inp' );
				$this->new_subfieldset( 'source-direct', $this->o->oTkit->_( 1085 ) );
				$this->new_label( '',
					 $this->field('textarea', 'arp[input]', $ar['input']) 
				);

				/* Options: A local file */
				$this->new_subfieldset( 'source-localfile', $this->o->oTkit->_( 1087 ) );
				$this->new_label( '', $this->field('file', 'arp[localfile]' ), 
					$this->o->oTkit->_( 1089 ).': '. $this->o->oTkit->bytes( $this->o->V->upload_max_filesize )
				);
				
				/* Options: Remote file */
				$this->set_tag( 'input', 'class', 'inp' );
				$this->new_subfieldset( 'source-remotefile', $this->o->oTkit->_( 1109 ) );
				$this->new_label( '', 
					$this->field( 'input', 'arp[remotefile]', $ar['input'] ), 
					$this->o->oTkit->_( 1210 ).': http://glossword.biz/ <br />'.
					$this->o->oTkit->_( 1089 ).': '. $this->o->oTkit->bytes( $this->o->V->upload_max_filesize )
				);
				
				/* Trick to make text in a one line, Firefox Bug #302710 */
				$this->o->oOutput->append_js( 'var textareanowrap = fn_getElementById(\'arp-input-\'); textareanowrap.setAttribute(\'wrap\', \'off\'); ');
				$this->o->oOutput->append_js( 'var parNod = textareanowrap.parentNode; var nxtSib = textareanowrap.nextSibling; ');
				$this->o->oOutput->append_js( 'parNod.removeChild(textareanowrap); parNod.insertBefore(textareanowrap, nxtSib); ');
				
				/* Group of options: Data format */
				$this->new_fieldset( 'data-format', $this->o->oTkit->_( 1086 ) );
				$this->set_tag( 'radio', 'id', 'arp-format-'.GW_INPUT_FMT_GWXML.'-' );
				$this->set_tag( 'radio', 'value', GW_INPUT_FMT_GWXML );
				$this->set_tag( 'radio', 'onclick', 'fn_getElementById(\'l-arp-id-lang-\').style.display=\'none\';');
				$this->new_label( 'xml: ' . $this->o->oTkit->_( 1120 ), $this->field('radio', 'arp[format]', true ) );

			break;
		}
		/* */
		$this->field( 'hidden', 'arg[action]', $this->o->gv['action'] );
		$this->field( 'hidden', 'arg[target]', $this->o->gv['target'] );

		/* Append URL */
		if ( !empty( $this->o->V->sef_append ) )
		{
			/* @todo: parse arrays */
			foreach ( $this->o->V->sef_append as $k1 => $v1 )
			{
				$this->field( 'hidden', $k1,  $v1 );
			}
		}
		
		if ( isset( $this->o->gv['id'] ) )
		{
			$this->field( 'hidden', 'arg[id]', $this->o->gv['id'] );
		}
		$this->field( 'hidden', 'arp[form]', '1' );
		return $this->form_output();
	}
	/* */
	public function on_success($ar)
	{

		/* Checkboxes */
		$ar = $this->check_onoff( $ar );
		
		$is_error = $is_redirect = 0;
		
		/* */
		if ( file_exists( $this->o->cur_htmlform_onsubmit ) )
		{
			include_once( $this->o->cur_htmlform_onsubmit );
		}
		
		/* Redirect to the list of languages */
		if ( $is_redirect )
		{
			$is_delay = 0;
			$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.az'."\x01\x01".'is_saved.1' );
			$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
		}

	}
}

?>