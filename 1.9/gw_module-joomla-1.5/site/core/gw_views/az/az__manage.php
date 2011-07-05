<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

/* Default language */
#$this->gv['area']['id_lang'] = isset(  $this->gv['area']['id_lang']  ) ?  $this->gv['area']['id_lang']  : $this->oTkit->ar_ls['id_lang'];

/**
 * ----------------------------------------------
 * Check for permissions
 * ----------------------------------------------
 */ 
if ( !$this->oSess->is( 'sys-settings' ) )
{
	$this->oOutput->append_html( '<div class="'.GW_COLOR_FALSE.' error" id="status">'.$this->oTkit->_( 1045 ).'</div>' );
	return false;
}

/**
 * ----------------------------------------------
 * Load Javascript
 * ----------------------------------------------
 */ 
 
$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-az' );


/* Select languages mentioned in a custom alphabetic order */
$this->oDb->select( 'l.id_lang, CONCAT(l.lang_name," - ", l.lang_native) lang', false );
$this->oDb->from( array( 'az_letters az', 'languages l' ) );
$this->oDb->where( array( 'l.id_lang = az.id_lang' => NULL ) );
$this->oDb->group_by( 'az.id_lang' );
$this->oDb->order_by( 'l.lang_name' );
$ar_sql = $this->oDb->get()->result_array();

$ar_langs = array();
foreach ( $ar_sql as $ar_v )
{
	$ar_langs[$ar_v['id_lang']] = $ar_v['lang'];
}
/* Default language */
$this->gv['area']['id_lang'] = isset( $this->gv['area']['id_lang'] ) ? $this->gv['area']['id_lang'] : key( $ar_langs );

$current_lang_name = isset( $ar_langs[$this->gv['area']['id_lang']] ) ? $ar_langs[$this->gv['area']['id_lang']] : '';


/* Select letters */
$this->oDb->select( 'id_letter, uc, lc');
$this->oDb->from( array( 'az_letters az' ) );
$this->oDb->where( array( 'az.id_lang' => $this->gv['area']['id_lang'] ) );
$this->oDb->order_by( 'az.int_sort ASC' );
$ar_sql = $this->oDb->get()->result_array();
$cnt_records = sizeof( $ar_sql );


/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'az.manage' );


/* */ 
$oHref_u = $this->oHtmlAdm->oHref();
$oHref_u->set( 'a', 'manage' );
$oHref_u->set( 't', 'az' );

$cnt = 1;
foreach ( $ar_langs as $id_lang => $lang_name )
{
	$oBlock->oTpl->assign( 'langs.list.cnt', ( $cnt ) );
	$oHref_u->set( 'id_lang', $id_lang );

	$classname = ( $this->gv['area']['id_lang'] == $id_lang ) ? 'highlight' : '';
	$url_lang = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref_u->get()  ),
				array( 'class' => $classname ),
				$lang_name
	);
	$oBlock->oTpl->assign( 'langs.list.lang_url', $url_lang );

	$oBlock->oTpl->parse_block('langs.list');
	++$cnt;
}

/* */
array_unshift( $ar_sql, array( 'id_letter' => '', 'uc' => '', 'lc' => '' ) );


$cnt = 0;
foreach ( $ar_sql as $ar_v )
{
	if ( $cnt == 0 )
	{
		$oBlock->oTpl->assign( 'az.list.cnt', '' );
		$oBlock->oTpl->assign( 'az.list.uc_lc', '' );
		$oBlock->oTpl->assign( 'az.list.uc', '<input onkeyup="oAz.add_onoff(oAz.validate_new())" id="letter-uc-new" maxlength="3" class="inp w50 " />' );
		$oBlock->oTpl->assign( 'az.list.lc', '<input onkeyup="oAz.add_onoff(oAz.validate_new())" id="letter-lc-new" maxlength="3" class="inp w50" />' );
		$oBlock->oTpl->assign( 'az.list.actions', '
			<div class="submit-buttons" style="padding: 0">
				<a class="submitnext disabled" href="javascript:void(0)" id="a-letter-add">Add</a>
			</div>'
		);
	}
	else
	{

		$oBlock->oTpl->assign( 'az.list.id_letter', $ar_v['id_letter'] );
		$oBlock->oTpl->assign( 'az.list.cnt', ( $cnt ) );
		$oBlock->oTpl->assign( 'az.list.uc', urldecode( $ar_v['uc'] ) );
		$oBlock->oTpl->assign( 'az.list.lc', urldecode( $ar_v['lc'] ) );
		$oBlock->oTpl->assign( 'az.list.uc_lc', urldecode( $ar_v['uc'] ).' '.urldecode( $ar_v['lc'] ) );

		$url_up = '<a class="btn add" href="javascript:void(0)" onclick="oAz.up(this, '.$ar_v['id_letter'].')">'.$this->oTkit->_( 1212 ).'</a>';
		$url_down = '<a class="btn add" href="javascript:void(0)" onclick="oAz.down(this, '.$ar_v['id_letter'].')">'.$this->oTkit->_( 1213 ).'</a>';
		$url_remove = '<a class="btn remove" href="javascript:void(0)" onclick="oAz.remove_confirm('.$ar_v['id_letter'].')">'. $this->oTkit->_( 1043 )  .'</a>';
		
		/* Disable links for first/last */
		if ( $cnt == 1 ) { $url_up = '<a class="btn disabled" href="javascript:void(0)">'.$this->oTkit->_( 1212 ).'</a>'; }
		if ( $cnt == $cnt_records ) { $url_down = '<a class="btn disabled" href="javascript:void(0)">'.$this->oTkit->_( 1213 ).'</a>'; }
		
		$oBlock->oTpl->assign( 'az.list.actions', $url_up.' '.$url_down.' '.$url_remove );
	}

	$oBlock->oTpl->parse_block('az.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'v:th_1',  $this->oTkit->_( 1211 ) );
$oBlock->oTpl->assign( 'v:th_2',  $this->oCase->uc( $this->oTkit->_( 1211 ) ) );
$oBlock->oTpl->assign( 'v:th_3',  $this->oCase->lc( $this->oTkit->_( 1211 ) ) );

$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_( 1023 ) ); /* Actions */
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) ); /* Pages */
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) ); /* Records found */
$oBlock->oTpl->assign( 'l:1184', $this->oTkit->_( 1184 ) ); /* Language name */
$oBlock->oTpl->assign( 'l:1177', $this->oTkit->_( 1177 ) ); /* By default */

$oBlock->oTpl->assign( 'v:id_table_az', 'azletters-list' );
$this->oOutput->append_js( 'jsF.stripe("azletters-list");' );

$oBlock->oTpl->assign( 'v:id_table_langs', 'langs-list' );
$this->oOutput->append_js( 'jsF.stripe("langs-list");' );

$oBlock->oTpl->assign( 'v:cnt_records', $cnt_records );

$this->oOutput->append_js( 'jsF.Set( "oTkit_1082", "'.$this->oTkit->_( 1082 ).'" );' ); /* Please, wait */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1041", "'.$this->oTkit->_( 1041 ).'" );' ); /* Settings saved */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1177", "'.$this->oTkit->_( 1177 ).'" );' ); /* By default */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1043", "'.$this->oTkit->_( 1043 ).'" );' ); /* Remove */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1051", "'.$this->oTkit->_( 1051 ).'" );' ); 
$this->oOutput->append_js( 'jsF.Set( "oTkit_1212", "'.$this->oTkit->_( 1212 ).'" );' ); /* Up */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1213", "'.$this->oTkit->_( 1213 ).'" );' ); /* Down */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1001", "'.$this->oTkit->_( 1001 ).'" );' ); /* Add */


$this->oOutput->append_js( 'jsF.Set( "id_lang", "'.$this->gv['area']['id_lang'].'");' );
$this->oOutput->append_js( 'oAz.build_index("azletters-list");' );

$this->oOutput->append_html( $oBlock->oTpl->get_html() );

/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
if ( $this->gv['sef_output'] != 'js' || $this->gv['sef_output'] != 'css' || $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1209 ).': '.$this->oTkit->_( 1006 ). ': '. $current_lang_name );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1209 ).': '.$this->oTkit->_( 1006 ). ': '. $current_lang_name );
}


?>