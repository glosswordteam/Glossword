<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
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

		/* Display `Settings saved` notice */
		if ( isset( $this->o->gv['area']['is_saved'] ) && $this->o->gv['area']['is_saved'] ) 
		{
			$this->o->notice_onsubmit( $this->o->oTkit->_( 1041 ), true );
		}		
		
		$this->set_tag( 'select', 'class', 'inp' );
		$this->set_tag( 'input', 'class', 'inp' );
		$this->set_tag( 'textarea', 'class', 'inp' );
		

		$str_date_format = "%d %M %Y %H:%s";
		
		/* */
		$ar_sidebar['term'] = array( 
			$this->o->oTkit->_( 1002 ), '', ''
			#'<a href="#" class="btn add" onclick="alert(\'term\')">+</a>',
			#'<a href="#" class="btn remove">-</a>'
		);
		
		$ar_sidebar['defn'][0][] = array( 
			$this->o->oTkit->_( 1021 ), '', ''
			#'<a href="javascript:void(0)" class="btn add" onclick="alert(\'defn\')"> + </a>',
			#'<a href="javascript:void(0)" class="btn remove" onclick="return false"> - </a>'
		);

		/* */
		$str = '';
		$str .= '<h3>'.$this->o->oTkit->_( 1030 ).'</h3>';
		$str .= '<div class="dtdnav">';
		foreach( $ar_sidebar as $tag => $v1)
		{
			if ($tag == 'term')
			{
				$str .= '<table class="el on" id="dtd-table-'.$tag.'-0"><tbody><tr>';
				$str .= '<td><a class="el" href="javascript:void(0)" onclick="alert(\'switch\')">'.$v1[0].'</a></td>';
				$str .= '<td class="ctrl">';
				$str .= $v1[2];
				$str .= $v1[1];
				$str .= '</span></td>';
				$str .= '</tr><tbody></table>';
			}
			elseif ($tag == 'defn')
			{
				$str .= '<ol>';
				foreach( $v1 as $level => $v2)
				{
					$level += 1;
					foreach( $v2 as $k3 => $v3)
					{
						$str .= '<li id="dtd-li-'.$tag.'-'.$level.'"><span class="lifixer">&#160;</span>';
						$str .= '<table class="el" id="dtd-table-'.$tag.'-'.$level.'"><tbody><tr>';
						$str .= '<td><a class="el" href="javascript:void(0)" onclick="alert(\'switch\')">'.$v3[0].'</a></td>';
						$str .= '<td class="ctrl">';
						$str .= $v3[2];
						$str .= $v3[1];
						$str .= '</span></td>';
						$str .= '</tr><tbody></table>';

						$str .= '</li>';
					}
				}
				$str .= '</ol>';
			}
		}
		$str .= '</div>';
#		$this->o->oTpl->assign( 'v:sidebar_menu', $str );
		
#		prn_r( $ar_sidebar );
		#prn_r( $ar );

		/* Start  oGWiki, db -> input */
		$oGWiki_input = $this->o->_init_wiki_( 'db', 'input' );
	
		/* Switch between actions */
		switch ( $this->o->gv['action'] )
		{
			case 'add':
				$this->phrase_submit_ok = $this->o->oTkit->_(1001);
			case 'edit':
				
				/* @temp: Manual fields */
				foreach ( $ar[$ar['id_item']] as $id_field => $ar_v )
				{
					switch ( $id_field )
					{
						case 1: /** Term **/
							$ar_v['contents_value'] = $oGWiki_input->proc( $ar_v['contents_value'] );
						
							$this->new_fieldset( 'term', $this->o->oTkit->_( 1002 ) );
							$this->set_tag( 'textarea', 'rows', '2' );

							$this->new_label( '', $this->field( 'textarea', 'arp['.$ar['id_item'].']['.$id_field.'][contents_value]', $ar_v['contents_value'] )  );
							$this->new_label( $this->o->oTkit->_( 1026 ), $this->field( 'checkbox', 'arp['.$ar['id_item'].']['.$id_field.'][is_active]', (bool) $ar_v['is_active'] ) );
							$this->new_label( $this->o->oTkit->_( 1027 ), $this->field( 'checkbox', 'arp['.$ar['id_item'].']['.$id_field.'][is_complete]', (bool) $ar_v['is_complete'] ) );
							
							/* Languages */
							$url_manage_lang = '';
							if ( $this->o->oSess->is( 'sys-settings' ) )
							{
								$url_manage_lang = $this->o->oHtmlAdm->a_href(
										array( $this->o->V->file_index, '#area' => 'a.manage'."\x01\x01".'t.langs'  ), 
										array( 'class' => 'btn add' ),
										$this->o->oTkit->_( 1006 )
								);
							}
							$this->new_label( $this->o->oTkit->_( 1028 ),
								$this->field( 'select', 'arp['.$ar['id_item'].']['.$id_field.'][id_lang]', $ar_v['id_lang'], $this->o->ar_languages )
								. $url_manage_lang
							);
							
						break;
						case 2: /** Definition **/
							$ar_v['contents_value'] = $oGWiki_input->proc( $ar_v['contents_value'] );
						
							$this->new_fieldset( 'defn', $this->o->oTkit->_( 1021 ) );
							$this->set_tag( 'textarea', 'rows', '10' );

							$this->new_label( '', $this->field( 'textarea', 'arp['.$ar['id_item'].']['.$id_field.'][contents_value]', $ar_v['contents_value'] ),
								$this->o->oTkit->_( 1053 ). '<br />*bold*, _italics_, ~~strike~~, ``quote\'\', `quote\', ""quote"", \'\'quote\'\', <br /> ++keyboard key++, `program code`, {{{program code}}}, {{{php program code}}}'
							);
							$this->o->oOutput->append_js( 'timer_check_height = setInterval(function(){ jsF.checkFieldHeightChar("arp-'.$ar['id_item'].'-'.$id_field.'-contents-value-"); }, 201);');
							
							#$this->new_label( $this->o->oTkit->_( 1028 ),
							#	$this->field( 'select', 'arp['.$ar['id_item'].']['.$id_field.'][id_lang]', $ar_v['id_lang'], $this->o->ar_languages ) 
							#);
						break;
						case 3: /** See also **/
							$this->set_tag( 'textarea', 'rows', '1' );
							$this->new_fieldset( 'seealso', $this->o->oTkit->_( 1076 ) );
							$this->new_label( '',
								$this->field( 'input', 'arp['.$ar['id_item'].']['.$id_field.'][contents_value]', $ar_v['contents_value'] ) 
							);
							#$this->new_label( '', $this->field( 'input', 'arp['.$ar['id_item'].']['.$id_field.'][contents_value2]', $ar_v['contents_value'] ) );
							$this->o->oOutput->append_js( 'oAutoSeeAlso.init("arp-'.$ar['id_item'].'-'.$id_field.'-contents-value-");' );
						break;
					}
					$this->field( 'hidden', 'arp['.$ar['id_item'].']['.$id_field.'][id_contents]', $ar_v['id_contents'] );
					$this->field( 'hidden', 'arp['.$ar['id_item'].']['.$id_field.'][id_user_created]', $ar_v['id_user_created'] );
				}
				
				/**
				 * -----------------------
				 * Options
				 * -----------------------
				 */
				$this->new_fieldset( 'item-redirect', $this->o->oTkit->_( 1049 ) );
				if ( $this->o->gv['uri'] )
				{
					$this->set_tag( 'radio', 'id', 'arp-redirect-uri-' );
					$this->set_tag( 'radio', 'value', 4 );
					$this->new_label( $this->o->oTkit->_(1048), $this->field('radio', 'arp[redirect]', ($ar['redirect'] == 4) ) );
				}
				$this->set_tag( 'radio', 'id', 'arp-redirect-add-item' );
				$this->set_tag( 'radio', 'value', 1 );
				$this->new_label($this->o->oTkit->_(1047, '<strong>'.$this->o->oTkit->_( 1003 ).': '.$this->o->oTkit->_( 1001 ).'</strong>'), $this->field('radio', 'arp[redirect]', ($ar['redirect'] == 1)) );
				#$this->set_tag( 'radio', 'id', 'arp-redirect-item-' );
				#$this->set_tag( 'radio', 'value', 3 );
				#$this->new_label($this->o->oTkit->_(1047, '<strong>'.$this->o->oTkit->_( 1002 ).'</strong>'), $this->field('radio', 'arp[redirect]', ($ar['redirect'] == 3)) );
				$this->set_tag( 'radio', 'id', 'arp-redirect-myitems-' );
				$this->set_tag( 'radio', 'value', 2 );
				$this->new_label($this->o->oTkit->_(1047, '<strong>'.$this->o->oTkit->_( 1003 ).': '.$this->o->oTkit->_( 1006 ).'</strong>'), $this->field('radio', 'arp[redirect]', ($ar['redirect'] == 2)) );


				if ( $this->o->gv['id_item'] )
				{
					$this->field( 'hidden', 'arg[id_item]', $this->o->gv['id_item'] );
				}
				$this->field( 'hidden', 'arg[id_item]', $ar['id_item'] );
				
			break;
			case 'import':
				/* Allow to upload files */
				$this->set_tag( 'form', 'enctype', 'multipart/form-data' );

				$this->phrase_submit_ok = $this->o->oTkit->_( 1077 );
				
				$ar_import_formats = array(
					GW_INPUT_FMT_GWXML => $this->o->oTkit->_( 1120 ),
					GW_INPUT_FMT_CSV => $this->o->oTkit->_( 1121 ),
					GW_INPUT_FMT_XML => $this->o->oTkit->_( 1124 )
				);

				switch ( $ar['step'] )
				{
					case 1:

						/* Data formats */
						$this->new_fieldset( 'data-format', $this->o->oTkit->_( 1086 ) );
						foreach ( $ar_import_formats as $id_format => $formatname )
						{
							$this->set_tag( 'radio', 'id', 'arp-format-'.$id_format.'-' );
							$this->set_tag( 'radio', 'value', $id_format );
							$this->set_tag( 'radio', 'onclick', '' );
							$this->new_label( $formatname, $this->field('radio', 'arp[format]', (bool) $ar['format-'.$id_format] ) );
						}

						
						$this->field( 'hidden', 'arp[step]', 2 );
					break;
					case 2:
						$this->phrase_submit_cancel = $this->o->oTkit->_( 1125 );

						$this->o->oOutput->append_html_title( $ar_import_formats[$ar['format']] );

						$this->set_tag( 'textarea', 'rows', 10 );
						$this->set_tag( 'textarea', 'style', 'font: 80% consolas,monospace;' );
						$this->o->oOutput->append_js( 'oItems.form_init();' );
						
						/* Group of options */
						$this->new_fieldset( 'select-source', $this->o->oTkit->_( 1127 ) );
						
						/* Source: Direct input */
						if ( $ar['source-direct'] ) { $this->o->oOutput->append_js( 'oItems.form_select(\'subfieldset-source-direct\');' ); }
						$this->set_tag( 'radio', 'id', 'arp-source-direct-' );
						$this->set_tag( 'radio', 'value', 'direct' );
						$this->set_tag( 'radio', 'onclick', 'oItems.form_select(\'subfieldset-source-direct\');' );
						$this->new_label( $this->o->oTkit->_( 1085 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-direct']) );
						
						/* Source: A local file */
						if ( $ar['source-localfile'] ) { $this->o->oOutput->append_js( 'oItems.form_select(\'subfieldset-source-localfile\');' ); }
						$this->set_tag( 'radio', 'id', 'arp-source-localfile-' );
						$this->set_tag( 'radio', 'value', 'localfile' );
						$this->set_tag( 'radio', 'onclick', 'oItems.form_select(\'subfieldset-source-localfile\');' );
						$this->new_label( $this->o->oTkit->_( 1087 ), $this->field( 'radio', 'arp[source]', (bool) $ar['source-localfile']) );

						/* Source: A remote file */
						if ( $ar['source-direct'] ) { $this->o->oOutput->append_js( 'oItems.form_select(\'subfieldset-source-remotefile\');' ); }
						$this->set_tag( 'radio', 'id', 'arp-source-remotefile-' );
						$this->set_tag( 'radio', 'value', 'remotefile' );
						$this->set_tag( 'radio', 'onclick', 'oItems.form_select(\'subfieldset-source-remotefile\');' );
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
						$this->new_subfieldset( 'source-remotefile', $this->o->oTkit->_( 1109 ) );
						$this->new_label('', 
							$this->field( 'input', 'arp[remotefile]', $ar['input'] ), 
							$this->o->oTkit->_( 1210 ).': http://glossword.biz/ <br />'.
							$this->o->oTkit->_( 1089 ).': '. $this->o->oTkit->bytes( $this->o->V->upload_max_filesize )
						);
						
						
						/* */
						switch ( $ar['format'] )
						{
							case GW_INPUT_FMT_GWXML:
								$this->new_fieldset( 'format-1', $ar_import_formats[GW_INPUT_FMT_GWXML] );
								$this->set_tag( 'radio', 'id', 'arp-cmp-1' );
								$this->set_tag( 'radio', 'value', 'gw19' );
								$this->set_tag( 'radio', 'onclick', 'oItems.xml_sub_options(\'xml19\')' );
								$this->new_label( 'Glossword 1.9', $this->field('radio', 'arp[cmp]', (bool) $ar['cmp-1'] ) );
								$this->o->oOutput->append_js( 'oItems.xml_sub_options("xml19");' );
											
								$this->set_tag( 'checkbox', 'style', 'margin-left:4em' );
								$this->new_label( $this->o->oTkit->_( 1102 ), $this->field( 'checkbox', 'arp[gw19_is_az]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1100 ), $this->field( 'checkbox', 'arp[gw19_is_term_uri]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1103 ), $this->field( 'checkbox', 'arp[gw19_is_cached]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1105 ), $this->field( 'checkbox', 'arp[gw19_is_si]', 1 ) );
								$this->set_tag( 'checkbox', 'style', '' );

								$this->set_tag( 'radio', 'id', 'arp-cmp-2' );
								$this->set_tag( 'radio', 'value', 'gw18' );
								$this->set_tag( 'radio', 'onclick', 'oItems.xml_sub_options(\'xml18\')' );
								$this->new_label( 'Glossword 1.8.4+', $this->field('radio', 'arp[cmp]', (bool) $ar['cmp-2'] ) );
								
								$this->set_tag( 'checkbox', 'style', 'margin-left:4em' );
								$this->new_label( $this->o->oTkit->_( 1102 ), $this->field( 'checkbox', 'arp[gw18_is_az]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1100 ), $this->field( 'checkbox', 'arp[gw18_is_term_uri]', 1 ) );
								$this->set_tag( 'checkbox', 'style', '' );
								
							break;
							case GW_INPUT_FMT_CSV:
								$this->new_fieldset( 'format-2', $ar_import_formats[GW_INPUT_FMT_CSV] );
								$this->set_tag( 'input', 'class', 'inp w25' );
								$this->set_tag( 'input', 'maxlength', '5' );
								$this->new_label( $this->o->oTkit->_( 1113 ), $this->field('checkbox', 'arp[is_head]', (bool) $ar['is_head'] ) );
								$this->new_label( $this->o->oTkit->_( 1114 ), $this->field( 'checkbox', 'arp[is_convert_escape]', 1 ), $this->o->oTkit->_( 1118 ) );
								$this->set_tag( 'select', 'class', 'inp w25' );
								$this->set_tag( 'select', 'style', '' );
								$ar_separators = array( ';' => ';', ',' => ',', '\t' => '\t', '|' => '|', '#' => '#' );
								$this->new_label( $this->o->oTkit->_( 1104 ), $this->field( 'select', 'arp[separator]', $ar['separator'], $ar_separators ) );
								/* @todo: Read fields config */
								$this->new_label( $this->o->oTkit->_( 1115, 'Term' ), $this->field( 'input', 'arp[col_term]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1115, 'Definition' ), $this->field( 'input', 'arp[col_defn]', 2 ) );
								$this->new_label( $this->o->oTkit->_( 1115, 'See Also' ), $this->field( 'input', 'arp[col_seealso]', 3 ) );
							break;
							case GW_INPUT_FMT_XML:
								$this->new_fieldset( 'format-4', $ar_import_formats[GW_INPUT_FMT_XML] );
								$this->new_label( $this->o->oTkit->_( 1116 ), $this->field( 'input', 'arp[xml_entry]', '<entry>' ) );
								/* @todo: only term and definition */
								$this->new_label( $this->o->oTkit->_( 1117, $this->o->oTkit->_( 1002 ) ), $this->field( 'input', 'arp[xml_term]', '<term>' ) );
								$this->new_label( $this->o->oTkit->_( 1117, $this->o->oTkit->_( 1021 ) ), $this->field( 'input', 'arp[xml_defn]', '<definition>' ) );
								$this->new_label( $this->o->oTkit->_( 1176 ) . ': <a href="javascript:import_preset_default()">'.$this->o->oTkit->_( 1177 ).'</a>, <a href="javascript:import_preset_rss()">RSS</a>, <a href="javascript:import_preset_atom()">Atom</a>', '' );
								$this->o->oOutput->append_js( 'function import_preset_default(){ fn_getElementById("arp-xml-entry-").value="<entry>"; fn_getElementById("arp-xml-term-").value="<term>"; fn_getElementById("arp-xml-defn-").value="<definition>"; }' );
								$this->o->oOutput->append_js( 'function import_preset_rss(){ fn_getElementById("arp-xml-entry-").value="item"; fn_getElementById("arp-xml-term-").value="title"; fn_getElementById("arp-xml-defn-").value="description"; }' );
								$this->o->oOutput->append_js( 'function import_preset_atom(){ fn_getElementById("arp-xml-entry-").value="entry"; fn_getElementById("arp-xml-term-").value="title"; fn_getElementById("arp-xml-defn-").value="content"; }' );
							break;
						}

						$this->new_fieldset( 'options',  $this->o->oTkit->_( 1198 ) );
						$this->new_label( $this->o->oTkit->_( 1119 ), $this->field( 'checkbox', 'arp[is_publish]', 1 ) );
						
						/* Language ID for CSV format */
						if ( $ar['format'] == GW_INPUT_FMT_CSV || $ar['format'] == GW_INPUT_FMT_XML )
						{
							$this->set_tag( 'select', 'class', 'inp w50' );
							$this->set_tag( 'select', 'style', '' );
							$this->new_label( $this->o->oTkit->_( 1028 ),
								$this->field( 'select', 'arp[id_lang]', $ar['id_lang'], $this->o->ar_languages ) 
							);
						}
							
						#$this->new_label( $this->o->oTkit->_( 'Check for an existent term' ), $this->field( 'checkbox', 'arp[is_check_existent]', 1 ) );
						#$this->set_tag( 'checkbox', 'style', 'margin-left:3em' );
						#$this->new_label( $this->o->oTkit->_( 'Overwrite an existent values with a new values' ), $this->field( 'checkbox', 'arp[is_overwrite]', 1 ) );

						$this->field( 'hidden', 'arp[format]', $ar['format'] );
						$this->field( 'hidden', 'arp[step]', 3 );
					break;
				}
			
			
			break;
			case 'export':
				$this->phrase_submit_ok = $this->o->oTkit->_( 1079 );
			
				$filename = 'gw_terms_'.date( "Y-m[M]-d", $this->o->V->time_gmt );

				$this->o->oDb->select( 'count(*) AS cnt' );
				$this->o->oDb->from( array( 'items' ) );
				$ar_sql = $this->o->oDb->get()->result_array();
				$cnt_records = isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0;
				
				$ar_export_formats = array(
					1 => $this->o->oTkit->_( 1120 ),
					2 => $this->o->oTkit->_( 1121 ),
				);
				
				switch ( $ar['step'] )
				{
					case 1:
						
						/* Data formats */
						$this->new_fieldset( 'data-format', $this->o->oTkit->_( 1086 ) );
						foreach ( $ar_export_formats as $id_format => $formatname )
						{
							$this->set_tag( 'radio', 'id', 'arp-format-'.$id_format.'-' );
							$this->set_tag( 'radio', 'value', $id_format );
							$this->set_tag( 'radio', 'onclick', '');
							$this->new_label( $formatname, $this->field('radio', 'arp[format]', (bool) $ar['format-'.$id_format] ) );
						}
						
						/* Number of items */
						$this->new_fieldset( 'items-export', $this->o->oTkit->_( 1003 ) );
						$this->new_label( $this->o->oTkit->_( 1036 ), '<em class="disabled">' . $this->o->oTkit->number_format( $cnt_records ) .'</em>' );

						$this->field( 'hidden', 'arp[step]', 2 );
					break;
					case 2:
						$this->phrase_submit_cancel = $this->o->oTkit->_( 1125 );

						$this->o->oOutput->append_html_title( $ar_export_formats[$ar['format']] );
						
						$this->new_fieldset( 'items-export-format', $ar_export_formats[$ar['format']] );

						switch ( $ar['format'] )
						{
							case 1: /** Glossword XML **/
								$this->set_tag( 'radio', 'id', 'arp-td-mode-t-' );
								$this->set_tag( 'radio', 'value', 't' );
								$this->set_tag( 'radio', 'onclick', '' );
								$this->new_label( $this->o->oTkit->_( 1003 ), $this->field('radio', 'arp[td_mode]', (bool) $ar['td_mode-t'] ) );
								
								$this->set_tag( 'radio', 'id', 'arp-td-mode-td-' );
								$this->set_tag( 'radio', 'value', 'td' );
								$this->set_tag( 'radio', 'onclick', '' );
								$this->new_label( $this->o->oTkit->_( 1099 ), $this->field('radio', 'arp[td_mode]', (bool) $ar['td_mode-td'] ) );

								$this->new_label( $this->o->oTkit->_( 1102 ), $this->field('checkbox', 'arp[is_az]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1101 ), $this->field('checkbox', 'arp[is_id_item]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1100 ), $this->field('checkbox', 'arp[is_term_uri]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1103 ), $this->field('checkbox', 'arp[is_cached]', 1 ) );
								$this->new_label( $this->o->oTkit->_( 1105 ), $this->field('checkbox', 'arp[is_si]', 1 ) );
								
								$filename .= '.xml';
				
							break;
							case 2: /** CSV/TSV **/
								
								$this->set_tag( 'input', 'class', 'inp w25' );
								$this->set_tag( 'input', 'maxlength', '5' );

								$this->set_tag( 'radio', 'id', 'arp-td-mode-t-' );
								$this->set_tag( 'radio', 'value', 't' );
								$this->set_tag( 'radio', 'onclick', '' );
								$this->new_label( $this->o->oTkit->_( 1003 ), $this->field('radio', 'arp[td_mode]', (bool) $ar['td_mode-t'] ) );
								
								$this->set_tag( 'radio', 'id', 'arp-td-mode-td-' );
								$this->set_tag( 'radio', 'value', 'td' );
								$this->set_tag( 'radio', 'onclick', '' );
								$this->new_label( $this->o->oTkit->_( 1099 ), $this->field('radio', 'arp[td_mode]', (bool) $ar['td_mode-td'] ) );
								
								$this->new_label( $this->o->oTkit->_( 1106 ), $this->field('checkbox', 'arp[is_head]', 0 ) );
								$this->set_tag( 'select', 'class', 'inp w25' );
								$this->set_tag( 'select', 'style', '' );
								$ar_separators = array( ';' => ';', ',' => ',', '\t' => '\t', '|' => '|', '#' => '#' );
								$this->new_label( $this->o->oTkit->_( 1104 ), $this->field( 'select', 'arp[separator]', $ar['separator'], $ar_separators ) );

								$filename .= '.csv';

							break;
							
						}
						/* Options */
						$this->new_fieldset( 'items-export-options', $this->o->oTkit->_( 1198 ) );
						$this->new_label( $this->o->oTkit->_( 1081 ), $this->field( 'checkbox', 'arp[is_save]', $ar['is_save'] ) );
				
						$ar_split_lines = array( 100 => 100, 250 => 250, 500 => 500, 
							1000 => $this->o->oTkit->number_format( 1000 ), 
							2500 => $this->o->oTkit->number_format( 2500 ), 
							5000 => $this->o->oTkit->number_format( 5000 ), 
							10000 => $this->o->oTkit->number_format( 10000 )
						);
						#$this->new_label( $this->o->oTkit->_( 1097 ), $this->field('select', 'arp[split]', $ar['split'], $ar_split_lines ) );

						
						/* Number of items */
						$this->new_fieldset( 'items-export', $this->o->oTkit->_( 1003 ) );
						$this->new_label( $this->o->oTkit->_( 1080 ), '<em class="disabled">'. $this->o->V->path_temp_abs .'/'. $this->o->V->path_export .'/'.$filename.'</em>' );

						#$this->new_label( $this->o->oTkit->_( 1036 ), '<em class="disabled">' . $cnt_records .'</em>' );


						$this->field( 'hidden', 'arp[format]', $ar['format'] );
						$this->field( 'hidden', 'arp[step]', 3 );
					break;
				}
			
				
			
				
				/**
				 * -----------------------
				 * Options
				 * -----------------------
				 */

				#$this->new_label( $this->o->oTkit->_( 1080 ), '<em class="disabled">'. $this->o->V->path_temp_abs .'/'. $this->o->V->path_export .'/'.$filename.'</em>' );


			break;
		}	

	
		
		
		
		#$this->is_htmlspecialchars = 1;
		
#		$this->new_fieldset( 'fields', $this->o->oTkit->_( 1020 ) );
		#$this->new_label( '', '' );
#		$this->new_subfieldset( 'partofspeech-1', $this->o->oTkit->_( 1029 ), '<a href="#" class="btn add">+</a>' );
#		$this->new_label( '', $this->field( 'select', 'arp['.$ar['id_item'].'][1][id_partofspeech]', 1, array('1' => 'noun') ) );




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
		/* Used for redirecting to a changed URI */
		if ( isset( $this->o->gv['old_uri'] ) )
		{
			$this->field( 'hidden', 'arg[old_uri]', $this->o->gv['old_uri'] );
		}
		$this->field( 'hidden', 'arp[form]', '1' );
		
		return $this->form_output();
	}
	/* */
	function on_success($ar)
	{
		$ar = $this->check_onoff( $ar );

		/* Load Search Index */
		$oSearchIndex = $this->o->_init_search_index();
		
		$q__contents = $q__content_si = $q__items = $q__items_uri = $q__map_term_to_dict = array();
		$is_error = $is_redirect = 0;
		
		/* */
		if ( file_exists( $this->o->cur_htmlform_onsubmit ) )
		{
			include_once( $this->o->cur_htmlform_onsubmit );
			
			/* Error parsing input */ 
			if ( $is_error )
			{
				$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.add'."\x01\x01".'t.items' );
				$this->o->oOutput->append_html( $this->o->soft_redirect(
					$msg_error, $href_redirect, GW_COLOR_FALSE
				));
				#return $this->get_form( $ar );
				return false;
			}
		}

		if ( $is_redirect )
		{
			$is_delay = 0;
			switch ( $ar['redirect'] )
			{
				case 1:
					/* arp-redirect-add-item */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.add'."\x01\x01".'t.items'."\x01\x01".'is_saved.1' );
					$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
					#$this->o->oOutput->append_html( $this->o->soft_redirect(
					#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
					#));
				break;
				case 2:
					/* arp-redirect-manage-items */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.manage'."\x01\x01".'t.items'."\x01\x01".'is_saved.1' );
					$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
					#$this->o->oOutput->append_html( $this->o->soft_redirect(
					#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
					#));
				break;
				case 3:
					/* arp-redirect-edit-item */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.edit'."\x01\x01".'id_item.'.$ar['id_item'].',t.items'."\x01\x01".'is_saved.1' );
					$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
					#$this->o->oOutput->append_html( $this->o->soft_redirect(
					#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
					#));
				break;
				case 4:
					/* arp-redirect-uri */
					if ( isset( $this->o->gv['old_uri'] ) && isset( $q__items_uri['item_uri'] ) )
					{
						/* Construct new URI */
						$this->o->gv['uri'] = base64_decode( $this->o->gv['uri'] );

						switch ( $this->o->V->link_mode )
						{
							case GW_LINK_ID:	$new_item_uri = $id_item; break;
							case GW_LINK_URI:	$new_item_uri = $q__items_uri['item_uri']; break;
							case GW_LINK_TEXT:	$new_item_uri = $this->o->oHtml->urlencode( $q__contents[$this->o->V->id_field_root]['contents_value_cached'] ); break;
						}
						/* Template for URI */
						if ( $this->o->V->link_template_uri != '' && $this->o->V->link_mode != GW_LINK_ID )
						{
							$new_item_uri = str_replace( '%s', $new_item_uri, $this->o->V->link_template_uri );
						}
						$this->o->gv['uri'] = str_replace( $this->o->oHtml->urlencode( $this->o->gv['old_uri'] ).'/', $new_item_uri.'/', $this->o->gv['uri'] );

						$href_redirect = $this->o->V->server_proto.$this->o->V->server_host.$this->o->gv['uri'];
					}
					else
					{
						$href_redirect = $this->o->V->server_proto.$this->o->V->server_host.base64_decode( $this->o->gv['uri'] );
					}
#					$is_delay = 1;
					$this->o->redirect( $href_redirect, $is_delay );
					#$this->o->oOutput->append_html( $this->o->soft_redirect(
					#	$this->o->oTkit->_( 1041 ), $href_redirect, GW_COLOR_TRUE
					#));
				break;
			}
		}
		

		/* Switch content modes */
		switch ($this->o->gv['sef_output'])
		{
			case 'ajax':
				print 1;
			break;
			default:
				/* */
				#$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, 0 );

				#$this->o->oOutput->append_html( $this->o->soft_redirect(
				#	$this->o->oTkit->_(1031), $href_redirect, CC_COLOR_TRUE
				#));
			break;
		}
	}
}

?>