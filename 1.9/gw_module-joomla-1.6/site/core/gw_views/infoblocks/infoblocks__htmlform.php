<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	 Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}



/* */
class site_htmlforms extends site_forms3_validation
{
	/* make html-code */
	function get_form($ar)
	{
		$this->phrase_wait = $this->o->oTkit->_(1082);
		$this->phrase_incorrect = $this->o->oTkit->_(1065);
		$this->phrase_submit_ok = $this->o->oTkit->_(1017);
		$this->phrase_submit_cancel = $this->o->oTkit->_(1018);

		$this->set_tag( 'form', 'action', $this->o->V->server_dir_admin.'/'.$this->o->V->file_index );
		$this->set_tag( 'form', 'id', $this->o->gv['action'] );
		$this->set_tag( 'form', 'onkeypress', 'jsF.formKeypress(event, this)' );

		$this->is_htmlspecialchars = 0;
		$this->is_actions = 0;
		$this->is_actions_top = 1;
		$this->is_label_ids = 1;
		$this->is_submit_ok = 1;
		$this->is_submit_cancel = 1;


		/* OnError */
		if ( !empty($this->ar_broken) )
		{
			$ar_fields = array(
				'block_type' => $this->o->oTkit->_( 1058 ),
				'block_place' => $this->o->oTkit->_( 1057 ),
				'block_name' => $this->o->oTkit->_( 1055 ),
				'block_content' => $this->o->oTkit->_( 1056 )
			);
			$this->o->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">' );
			$this->o->oOutput->append_html( $this->o->oTkit->_( 1052 ) );
			/* Ordered list of fields is first */
			$ar_fields_msg = array();
			foreach ($ar_fields as $field_cfg => $l)
			{
				foreach ($this->ar_broken as $field_broken => $k)
				{
					if ($field_cfg == $field_broken)
					{
						$ar_fields_msg[] = '<a href="#'.$this->text_field2id('l-arp['.$field_broken.']').'">'.$ar_fields[$field_broken].'</a>';
					}
				}
			}
			$this->o->oOutput->append_html( '<div>'.implode(', ', $ar_fields_msg).'</div>' );
			$this->o->oOutput->append_html( '</div>' );
		}
		
		$this->set_tag( 'select', 'class', 'inp' );
		$this->set_tag( 'input', 'class', 'inp' );
		$this->set_tag( 'textarea', 'class', 'inp' );
		
		/* Switch between actions */
		switch ( $this->o->gv['action'] )
		{
			case 'add':
				$this->phrase_submit_ok = $this->o->oTkit->_( 1001 );
			case 'edit':
				/* Group of options */
				$this->new_fieldset( 'infoblock', $this->o->oTkit->_( 1066 ) );

				/* Start  oGWiki, db -> input */
				$oGWiki_input = $this->o->_init_wiki_( 'db', 'input' );
			
				$ar['block_name'] = $oGWiki_input->proc( $ar['block_name'] );
				$ar['block_contents'] = $oGWiki_input->proc( $ar['block_contents'] );
			
				/* */
				$this->new_label( $this->o->oTkit->_( 1055 ), $this->field( 'input', 'arp[block_name]', $ar['block_name'] ) );

				/* */
				$this->set_tag( 'textarea', 'rows', '10' );
				$this->new_label( $this->o->oTkit->_( 1056 ), $this->field( 'textarea', 'arp[block_contents]', $ar['block_contents'] ) );
				$this->o->oOutput->append_js( 'timer_check_height = setInterval(function(){ jsF.checkFieldHeightChar("arp-block-contents-"); }, 201);');
				
				/* */
				$ar_places = $this->o->oTarget->get_places();
				$this->new_label( $this->o->oTkit->_( 1057 ),
					$this->field( 'select', 'arp[block_place]', $ar['block_place'], $ar_places ) 
				);

				/* */
				$ar_types = array( '1' => $this->o->oTkit->_( 1074 ), '2' => $this->o->oTkit->_( 1075 ) );
				$this->new_label( $this->o->oTkit->_( 1058 ),
					$this->field( 'select', 'arp[block_type]', $ar['block_type'], $ar_types ) 
				);
				
				/* */
				if ($this->o->gv['action'] == 'edit')
				{
					/* */
					$ar_statuses = $this->o->oTarget->get_statuses();
					$this->new_label( $this->o->oTkit->_( 1067 ),
						$this->field( 'select', 'arp[is_active]', $ar['is_active'], $ar_statuses ) 
					);
				}
				if ( isset( $this->o->gv['id'] ) && $this->o->gv['id'] )
				{
					$this->field( 'hidden', 'arg[id]', $this->o->gv['id'] );
				}
			break;
			case 'export':
				/* Group of options */
				$this->new_fieldset( 'infoblock', $this->o->oTkit->_( 1066 ) );
			
				$this->phrase_submit_ok = $this->o->oTkit->_( 1079 );
			
				$filename = 'gw_infoblocks_'.date( "Y-m[M]-d", $this->o->V->time_gmt ).'.xml';

				$this->o->oDb->select( 'count(*) AS cnt' );
				$this->o->oDb->from( array( 'blocks' ) );
				$ar_sql = $this->o->oDb->get()->result_array();
				$cnt_records = isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0; 

				$this->new_label( $this->o->oTkit->_( 1036 ), '<em class="disabled">' . $cnt_records .'</em>' );
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
				$this->o->oOutput->append_js( 'oInfoblocks.form_init();' );
				
				/* Source: Direct input */
				if ( $ar['source-direct'] ) { $this->o->oOutput->append_js( 'oInfoblocks.form_select(\'subfieldset-source-direct\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-direct-' );
				$this->set_tag( 'radio', 'value', 'direct' );
				$this->set_tag( 'radio', 'onclick', 'oInfoblocks.form_select(\'subfieldset-source-direct\');' );
				$this->new_label( $this->o->oTkit->_( 1085 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-direct']) );
				
				/* Source: A local file */
				if ( $ar['source-localfile'] ) { $this->o->oOutput->append_js( 'oInfoblocks.form_select(\'subfieldset-source-localfile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-localfile-' );
				$this->set_tag( 'radio', 'value', 'localfile' );
				$this->set_tag( 'radio', 'onclick', 'oInfoblocks.form_select(\'subfieldset-source-localfile\');' );
				$this->new_label( $this->o->oTkit->_( 1087 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-localfile']) );
				
				/* Source: A remote file */
				if ( $ar['source-remotefile'] ) { $this->o->oOutput->append_js( 'oAz.form_select(\'subfieldset-source-remotefile\');' ); }
				$this->set_tag( 'radio', 'id', 'arp-source-remotefile-' );
				$this->set_tag( 'radio', 'value', 'remotefile' );
				$this->set_tag( 'radio', 'onclick', 'oInfoblocks.form_select(\'subfieldset-source-remotefile\');' );
				$this->new_label( $this->o->oTkit->_( 1109 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-remotefile']) );

				/* Options: Direct input */
				$this->new_subfieldset( 'source-direct', $this->o->oTkit->_( 1085 ) );
				$this->new_label(
					'', $this->field('textarea', 'arp[input]', $ar['input']) 
				);

				/* Options: A local file */
				$this->new_subfieldset( 'source-localfile', $this->o->oTkit->_( 1087 ) );
				$this->new_label('', $this->field('file', 'arp[localfile]' ), 
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
		$this->field( 'hidden', 'arg[uri]', $this->o->gv['uri'] );
		$this->field( 'hidden', 'arp[form]', '1' );
		
		return $this->form_output();
	}
	/* */
	function on_success($ar)
	{
		$ar = $this->check_onoff( $ar );

		/* Load Search Index */
		$oSearchIndex = $this->o->_init_search_index();
		
		$q__blocks = array();
		$is_error = $is_redirect = 0;

		/* */
		if ( file_exists( $this->o->cur_htmlform_onsubmit ) )
		{
			include_once( $this->o->cur_htmlform_onsubmit );
		}

		if ( $is_redirect )
		{
			$is_delay = 0;
			/* arp-redirect-manage */
			$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage,t.infoblocks' );
			$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
		}
		
		#$this->o->oOutput->append_html( $this->o->soft_redirect(
		#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
		#));

		/* Switch content modes */
		switch ($this->o->gv['sef_output'])
		{
			case 'ajax':
				print 1;
			break;
		}
	}
}

?>