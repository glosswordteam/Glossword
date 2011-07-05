<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Switch content modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
	case 'css':
	case 'js':
	break;
	default:
		if (defined('SITE_THIS_SCRIPT') && SITE_THIS_SCRIPT == $this->V->file_admin)
		{
			/**
			 * ----------------------------------------------
			 * Title page for the administrator
			 * ----------------------------------------------
			 */
			$this->a( 'id_tpl_page', GW_TPL_ADM );

			/* Everything below requires authorization */
			if ( $this->oSess->is_auth_needed() ) { return; }

			$str = '';

			$str .= '<table width="100%"><tbody><tr><td style="width:50%;vertical-align:top">';

			/**
			 * Quick add
			 */
			 $str .= '<h2>'.$this->oTkit->_( 1003 ).': '.$this->oTkit->_( 1001 ).'</h2>';
			 $str .= '<div class="inp">';
			 $str .= '<form accept-charset="utf-8" action="'.$this->V->server_dir_admin.'/'.$this->V->file_index.'" enctype="application/x-www-form-urlencoded" id="quick-add" method="post" onkeypress="jsF.formKeypress(event, this)">';
			 $str .= '<fieldset><label><em>'.$this->oTkit->_( 1002 ).'</em>';
			 $str .= '<textarea name="arp[0][1][contents_value] type="text" value="" class="inp" col="30" rows="1"></textarea></label>';
			 $str .= '<label><em>'.$this->oTkit->_( 1021 ).'</em>';
			 $str .= '<textarea name="arp[0][2][contents_value] type="text" value="" class="inp" col="30" rows="4"></textarea></label>';

			 $str .= '<label><em>'.$this->oTkit->_( 1028 ).'</em>';
			 $str .= '<select name="arp[0][1][id_lang]" class="inp">';
			 foreach( $this->ar_languages as $id_lang => $lang_name )
			 {
			 	 $str .= '<option value="'.$id_lang.'">'.$lang_name.'</option>';
			 }
			 $str .= '</select></label>';

			 $str .= '<div class="submit-buttons" style="font-size:80%">';
			 $str .= '<a class="submitok" href="javascript:void(0)" ';
			 $str .= ' onclick="function forms3onclick(e){if(typeof(this.s)==\'undefined\'){e.replaceChild(document.createTextNode(\''.$this->oTkit->_( 1082 ).'\'),e.firstChild);e.style.cursor=\'wait\';document.forms[\'quick-add\'].submit();}else{e.blur()}this.s=1;};forms3onclick(this);return false"';
			 $str .= ' title="'.$this->oTkit->_( 1001 ).'">'.$this->oTkit->_( 1001 ).'</a>';
			 $str .= '</div>';
			 $str .= '<input type="hidden" name="arp[0][1][id_contents]" value="0" />';
			 $str .= '<input type="hidden" name="arp[0][1][is_active]" value="1" />';
			 $str .= '<input type="hidden" name="arp[0][1][is_complete]" value="1" />';
			 $str .= '<input type="hidden" name="arp[0][2][id_contents]" value="0" />';
			 $str .= '<input type="hidden" name="arp[0][2][id_lang]" value="1" />';
			 $str .= '<input type="hidden" name="arg[action]" value="add" />';
			 $str .= '<input type="hidden" name="arg[target]" value="items" />';
			 $str .= '<input type="hidden" name="arp[form]" value="1" />';
			 $str .= '<input type="hidden" name="arp[redirect]" value="2" />';
			/* Append URL */
			if ( !empty( $this->V->sef_append ) )
			{
				/* @todo: parse arrays */
				foreach ( $this->V->sef_append as $k1 => $v1 )
				{
				 	$str .= '<input type="hidden" name="'.$k1.'" value="'.$v1.'" />';
				}
			}
			$str .= '</fieldset></form>';
			$str .= '</div>';

			$str .= '</td><td style="width:50%;vertical-align:top">';

			/**
			 * Recently updated terms
			 */
			$this->oDb->select( 'i.id_item, i.item_mdate, i.item_id_user_created, c.contents_value_cached, c.contents_a, c.id_field, c.id_contents' );
			$this->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'map_field_to_fieldset mftf' ) );
			$this->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
			$this->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
			$this->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
			$this->oDb->where( array( 'mftf.id_fieldset' => '1' ) );
			$this->oDb->order_by( 'mftf.int_sort ASC, i.item_mdate DESC ' );
			$this->oDb->limit( 10 );
			$ar_sql_items = $this->oDb->get()->result_array();
			/* Re-arrange */
			$ar_items = array();
			foreach ( $ar_sql_items as $ar_v)
			{
				$ar_items[$ar_v['id_item']][$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;
			}

			#prn_r( $ar_items );

			$str .= '<h2>'.$this->oTkit->_( 1153 ).'</h2>';
			$str .= '<table class="tbl-list" id="terms-recent" cellspacing="1" width="100%">
			<thead>
			<tr>
			<th style="width:2%">№</th>
			<th style="width:60%"></th>
			<th style="width:38%">'.$this->oTkit->_( 1039 ).'</th>
			</tr>
			</thead>
			<tbody>';

			$oHref = $this->oHtmlAdm->oHref();
			$oHref->set( 'a' , 'edit' );
			$oHref->set( 't' , 'items' );
			$cnt = 1;
			$date_format = '%d %f %Y %H:%i';
			foreach ( $ar_items as $id_item => $ar_fields_content )
			{
				$str .= '<tr>';
				foreach ( $ar_fields_content as $id_field => $ar_v_contents )
				{
					$ar_contents = array();
					foreach ( $ar_v_contents as $id_content => $ar_v )
					{
						$ar_contents[] = $ar_v['contents_value_cached'];
					}
					if ( $id_field == $this->V->id_field_root )
					{
						$str_item = strip_tags( implode(' ', $ar_contents ) );
						$str_item_cut = $this->oFunc->smart_substr( strip_tags( $str_item ), 0, 32 );
					}
					$oHref->set( 'id_item' , $id_item );
					$url_term = $this->oHtmlAdm->a_href(
						array( $this->V->file_index, '#area' => $oHref->get() ),
						array( 'class' => 'btn edit', 'title' => $this->oTkit->_( 1042 ).': '.$this->oTkit->_( 1002 ) ),
						$str_item_cut . $this->V->str_class_edit
					);
				}
					$str .= '<td class="n">'. $cnt .'</td>';
					$str .= '<td>'. $url_term .'</td>';
					$str .= '<td class="date">'. $this->oTkit->date( $date_format, strtotime( $ar_v['item_mdate'] ) ) .'</td>';
				++$cnt;
				$str .= '</tr>';
			}
			if ( empty( $ar_items ) )
			{
				$str .= '<tr><td colspan="2" class="center"> ' . $this->oTkit->_( 1155 ) . '</td></tr>';
			}
			$str .= '</tbody></table>';

			$str .= '</tr></tbody></table>';


			$this->oOutput->append_html( $str );


			/* */
			$this->oOutput->append_html_title( $this->oTkit->_( 1152 ) );
			$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1152 ) );

		}
		else
		{
			/**
			 * ----------------------------------------------
			 * Title page for website
			 * ----------------------------------------------
			 */
			$this->a( 'id_tpl_page', GW_TPL_WEB_INSIDE );

			/* View item */
			if ( isset( $this->gv['area']['id'] ) && !$this->gv['action'] )
			{
				$this->gv['action'] = 'view';
				$this->gv['target'] = 'items';
				$this->page_body();
				return;
			}

			/* Browse items */
			$this->gv['action'] = 'browse';
			$this->gv['target'] = 'items';
			$this->page_body();

		}
	break;
}

?>