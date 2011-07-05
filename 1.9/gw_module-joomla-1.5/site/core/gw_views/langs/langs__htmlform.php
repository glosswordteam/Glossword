<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
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

			case 'edit':

				$this->set_tag( 'input', 'class', 'inp' );
				$this->set_tag( 'textarea', 'class', 'inp' );
				$this->set_tag( 'select', 'class', 'inp' );
				$this->set_tag( 'select', 'style', '' );
				
				/* */
				$this->new_fieldset( 'lang-settings', $this->o->oTkit->_( 1198 ) );
				$this->new_label( $this->o->oTkit->_( 1069 ), $this->field( 'checkbox', 'arp[is_active]', (bool) $ar['is_active'] ) );
				
				$this->set_tag( 'input', 'maxlength', 64 );
				$this->new_label( $this->o->oTkit->_( 1184 ), $this->field( 'input', 'arp[lang_name]', $ar['lang_name']) );
				$this->new_label( $this->o->oTkit->_( 1191 ), $this->field( 'input', 'arp[lang_native]', $ar['lang_native']) );
				
				$this->set_tag( 'input', 'maxlength', 2 );
				$this->new_label( $this->o->oTkit->_( 1188, 'ISO 639-1' ), $this->field( 'input', 'arp[isocode1]', $ar['isocode1']) );
				$this->set_tag( 'input', 'maxlength', 3 );
				$this->new_label( $this->o->oTkit->_( 1188, 'ISO 639-3' ), $this->field( 'input', 'arp[isocode3]', $ar['isocode3']) );
				$this->new_label( $this->o->oTkit->_( 1186 ), $this->field( 'select', 'arp[region]', $ar['region'], $this->o->oTarget->get_regions() ) );

				$ar_direction = array( 'ltr' => $this->o->oTkit->_( 1197 ).' →', 'rtl' => $this->o->oTkit->_( 1196 ).' ←' );
				$this->new_label( $this->o->oTkit->_( 1192 ), $this->field('select', 'arp[direction]', $ar['direction'], $ar_direction) );
				$this->set_tag( 'input', 'maxlength', 5 );
				$this->new_label( $this->o->oTkit->_( 1193 ).' (1'.$ar['thousands_separator'].'000)', $this->field('input', 'arp[thousands_separator]', $ar['thousands_separator']) );
				$this->new_label( $this->o->oTkit->_( 1194 ).' (1'.$ar['decimal_separator'].'25)', $this->field('input', 'arp[decimal_separator]', $ar['decimal_separator']) );

				/* On error */
				if ( is_array( $ar['month_short'] ) )
				{
					$ar['month_short'] = implode( ' ', $ar['month_decl'] );
					$ar['month_long'] = implode( ' ', $ar['month_long'] );
					$ar['month_decl'] = implode( ' ', $ar['month_decl'] );
				}
				if ( is_array( $ar['day_of_week'] ) )
				{
					$ar['day_of_week'] = implode( ' ', $ar['day_of_week'] );
				}
				if ( is_array( $ar['byte_units'] ) )
				{
					$ar['byte_units'] = implode( ' ', $ar['byte_units'] );
				}

				/* */
				if ( trim( $ar['month_short'] ) == '' )
				{
					$ar['month_decl'] = $ar['month_long'] = $ar['month_short'] = str_repeat( ' ', 11 );
				}
				$ar_month_short = explode( " ", $ar['month_short'] );
				$ar_month_long = explode( " ", $ar['month_long'] );
				$ar_month_decl = explode( " ", $ar['month_decl'] );

				/* Months */
				$this->set_tag( 'input', 'maxlength', '24' );
				$this->new_fieldset( 'lang-month-short', $this->o->oTkit->_( 1199 ) );
				foreach ( $ar_month_short as $k => $str_month_short )
				{
					$this->new_label( '', $this->field('input', 'arp[month_short]['.$k.']', $ar_month_short[$k]) );
					$this->new_label( '', $this->field('input', 'arp[month_long]['.$k.']', $ar_month_long[$k]) );
					$this->new_label( '', $this->field('input', 'arp[month_decl]['.$k.']', $ar_month_decl[$k]) );
				}
				/* Days of week */
				$this->set_tag( 'input', 'maxlength', '7' );
				if ( trim( $ar['day_of_week'] ) == '' )
				{
					$ar['day_of_week'] = str_repeat( ' ', 6 );
				}
				$ar_week = explode( ' ', $ar['day_of_week'] );
				$this->new_fieldset( 'lang-week', $this->o->oTkit->_( 1200 ) );
				foreach ( $ar_week as $k => $str_week)
				{
					$this->new_label( '', $this->field( 'input', 'arp[day_of_week]['.$k.']', $str_week ) );
				}
				/* Bytes */
				$this->set_tag( 'input', 'maxlength', '7' );
				if ( trim( $ar['byte_units'] ) == '' )
				{
					$ar['byte_units'] = str_repeat( ' ', 6 );
				}
				$ar_bytes = explode( ' ', $ar['byte_units'] );
				$this->new_fieldset( 'lang-bytes', $this->o->oTkit->_( 1201 ) );
				foreach ( $ar_bytes as $k => $str_bytes)
				{
					$this->new_label('', $this->field('input', 'arp[byte_units]['.$k.']', $str_bytes) );
				}
			break;
			case 'export':
				$this->new_fieldset( 'project-languages', $this->o->oTkit->_( 1181 ), 
					'<a href="javascript:void(0)" onclick="oLangs.checkall( true, \'fs-project-languages\' )">'.$this->o->oTkit->_( 1202 ).'</a> / '.
					'<a href="javascript:void(0)" onclick="oLangs.checkall( false, \'fs-project-languages\' )">'.$this->o->oTkit->_( 1203 ).'</a>' );
			
				$this->phrase_submit_ok = $this->o->oTkit->_( 1079 );
			
				$filename = 'gw_langs_'.date( "Y-m[M]-d", $this->o->V->time_gmt ).'.xml';
				
				
				/* Select all languages */
				$this->o->oDb->select( 'l.is_active, l.id_lang, CONCAT(l.lang_name, " - ", l.lang_native) lang', false  );
				$this->o->oDb->from( array( 'languages l' ) );
				$this->o->oDb->order_by( 'l.is_active DESC, l.lang_name ASC' );
				$ar_sql = $this->o->oDb->get()->result_array();
				
				foreach ( $ar_sql as $ar_v )
				{
					$this->new_label( $ar_v['lang'], $this->field( 'checkbox', 'arp[id_lang_export]['.$ar_v['id_lang'].']', (bool) $ar_v['is_active'] ) );
				}

				$this->new_fieldset( 'lang-export', $this->o->oTkit->_( 1198 ) );
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
				$this->o->oOutput->append_js( 'oLangs.form_init();' );
				
				/* Source: Direct input */
				if ( $ar['source-direct'] ) { $this->o->oOutput->append_js( 'oLangs.form_select(\'subfieldset-source-direct\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-direct-' );
				$this->set_tag( 'radio', 'value', 'direct' );
				$this->set_tag( 'radio', 'onclick', 'oLangs.form_select(\'subfieldset-source-direct\');' );
				$this->new_label( $this->o->oTkit->_( 1085 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-direct']) );
				
				/* Source: A local file */
				if ( $ar['source-localfile'] ) { $this->o->oOutput->append_js( 'oLangs.form_select(\'subfieldset-source-localfile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-localfile-' );
				$this->set_tag( 'radio', 'value', 'localfile' );
				$this->set_tag( 'radio', 'onclick', 'oLangs.form_select(\'subfieldset-source-localfile\');' );
				$this->new_label( $this->o->oTkit->_( 1087 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-localfile']) );
				
				/* Source: A remote file */
				if ( $ar['source-remotefile'] ) { $this->o->oOutput->append_js( 'oLangs.form_select(\'subfieldset-source-remotefile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-remotefile-' );
				$this->set_tag( 'radio', 'value', 'remotefile' );
				$this->set_tag( 'radio', 'onclick', 'oLangs.form_select(\'subfieldset-source-remotefile\');' );
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
				
				/* Data format */
				$this->new_fieldset( 'data-format', $this->o->oTkit->_( 1086 ) );
				$this->set_tag( 'radio', 'id', 'arp-format-1-' );
				$this->set_tag( 'radio', 'value', '1' );
				$this->set_tag( 'radio', 'onclick', '');
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
			$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.langs'."\x01\x01".'is_saved.1' );
			$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
		}

	}
}

?>