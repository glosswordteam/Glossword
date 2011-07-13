<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Form values */
$arVF = array();
/* Correct unknown settings */
$ar_settings = array();
/* Checkboxes */
$ar_onoff = array( 'is_optimize', 'is_repair' );
/* Required fields */
$ar_required = array( );

/* */
class site_htmlforms extends site_forms3_validation
{
	/* make html-code */
	function get_form($ar)
	{
		$this->phrase_wait = $this->o->oTkit->_( 1082 );
		$this->phrase_incorrect = $this->o->oTkit->_( 1065 );
		$this->phrase_submit_ok = $this->o->oTkit->_( 1050 );
		$this->phrase_submit_cancel = $this->o->oTkit->_( 1125 );
		
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
		
		/* */
		$ar_tables = $this->o->oDb->show_table_status();

		/* */
		$this->new_fieldset( 'repair_optimize', $this->o->oTkit->_( 1132 ) );
		
		$cnt_items = sizeof( $ar_tables );
		$this->new_label( '', $this->o->oTkit->_( 1036 ).': <strong>'. $this->o->oTkit->number_format( $cnt_items ).'</strong>' );
		
		$this->new_label( $this->o->oTkit->_( 1138 ),  $this->field( 'checkbox', 'arp[is_optimize]', true ) );
		$this->new_label( $this->o->oTkit->_( 1139 ),  $this->field( 'checkbox', 'arp[is_repair]', false ) );

		$col_width = 'width:12%';
		/* Heading */
		$this->new_label( 
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.';float:left" title="' . $this->o->oTkit->_( 1133 ) . '">' . $this->o->oTkit->_( 1133 ) . '</em>' .
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'" title="' . $this->o->oTkit->_( 1136 ) . '">' . $this->o->oTkit->_( 1136 ) . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'" title="' . $this->o->oTkit->_( 1135 ) . '">' . $this->o->oTkit->_( 1135 ) . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'" title="' . $this->o->oTkit->_( 1134 ) . '">' . $this->o->oTkit->_( 1134 ) . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'" title="' . $this->o->oTkit->_( 1137 ) . '">' . $this->o->oTkit->_( 1137 ) . '</em>'
			, ''
		);
		$cnt_index = $cnt_data = $cnt_fragmented = $cnt_records = 0;
		/* */
		foreach ( $ar_tables as $k => $ar_v )
		{
			$this->set_tag( 'checkbox', 'id', $ar_v['Name'] );
			$str_col_fragm = ($ar_v['Data_free'] > 0 ) ? '<strong class="state-warning">'.$this->o->oTkit->bytes( $ar_v['Data_free'] ).'</strong>' : $this->o->oTkit->bytes( 0 );
			$this->new_label( 
				$ar_v['Name'] .
				'<em class="tbl-list-td textright" style="'.$col_width.'">'. $str_col_fragm .'</em>'.
				'<em class="tbl-list-td textright" style="'.$col_width.'">'. $this->o->oTkit->bytes( $ar_v['Index_length'] ) .'</em>'.
				'<em class="tbl-list-td textright" style="'.$col_width.'">'. $this->o->oTkit->bytes( $ar_v['Data_length'] ).'</em>'.
				'<em class="tbl-list-td textright" style="'.$col_width.'">'. $this->o->oTkit->number_format( $ar_v['Rows'] ).'</em>'
				,
				$this->field( 'checkbox', 'arp[tables]['.$ar_v['Name'].']', ($ar_v['Data_free'] > 0 ) )
			);
			$cnt_fragmented += $ar_v['Data_free'];
			$cnt_index += $ar_v['Index_length'];
			$cnt_data += $ar_v['Data_length'];
			$cnt_records += $ar_v['Rows'];
		}
		/* Summary */
		$str_col_fragm = ( $cnt_fragmented > 0 ) ? '<strong class="state-warning">'.$this->o->oTkit->bytes( $cnt_fragmented ).'</strong>' : $this->o->oTkit->bytes( 0 );
		$this->new_label( 
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'">' . $str_col_fragm . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'">' . $this->o->oTkit->bytes( $cnt_index ) . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'">' . $this->o->oTkit->bytes( $cnt_data ) . '</em>'.
			'<em class="tbl-list-th" style="text-align:center;'.$col_width.'">' . $this->o->oTkit->number_format( $cnt_records ) . '</em>'
			, ''
		);
		
		
		/* */
		$this->field( 'hidden', 'arg[action]', $this->o->gv['action'] );
		$this->field( 'hidden', 'arg[target]', $this->o->gv['target'] );
		$this->field( 'hidden', 'arg[area]', 's.'.$this->o->gv['area']['s'] );
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
		
		/* */
		if ( empty( $ar['tables'] ) || ( !$ar['is_optimize'] && !$ar['is_repair'] ) )
		{
			$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.mnt,t.service,s.repair_optimize' );
			$this->o->oOutput->append_html( $this->o->soft_redirect(
				'<p class="color-black">'.$this->o->oTkit->_( 1140 ).'</p>', $href_redirect, GW_COLOR_FALSE
			));
			return;
		}

		/* */
		foreach ( $ar['tables'] as $tablename => $v)
		{
			if ( $ar['is_repair'] )
			{
				$this->o->oDb->query( 'REPAIR TABLE `'.$tablename,'`' );
			}
			if ( $ar['is_optimize'] )
			{
				$this->o->oDb->query( 'OPTIMIZE TABLE `'.$tablename,'`' );
			}
		}
		/* Redirect */
		$is_delay = 0;
		$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.mnt,t.service,s.repair_optimize,is_saved.1' );
		$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, $is_delay );
	}
}


?>