<?php
/**
 * @version		$Id$
 * @package		Translation Kit
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

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


$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-langs' );


$this->gv['area']['per'] = 20;


/* Display `Settings saved` notice */
if ( isset( $this->gv['area']['is_saved'] ) && $this->gv['area']['is_saved'] ) 
{
	$this->notice_onsubmit( $this->oTkit->_( 1041 ), true );
}
		

/**
 * ----------------------------------------------
 * Count the number of records 
 * ----------------------------------------------
 */
$this->oDb->select('count(*) as cnt', false);
$this->oDb->from( array('languages l' ) );
$ar_sql = $this->oDb->get()->result_array();
$cnt_records = isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0; 

/* Count the number of pages */
$int_pages = @ceil( $cnt_records / $this->gv['area']['per'] );
$int_pages = (!$int_pages ? 1 : $int_pages);
/* */
$offset = $this->gv['area']['per'] * ($this->gv['page'] - 1);
$offset = ($offset > $cnt_records) ? $this->gv['area']['per'] * ($int_pages-1) : $offset;

/**
 * ----------------------------------------------
 * Pagination
 * ----------------------------------------------
 */
if ($int_pages > 1)
{
	$oHref = $this->oHtmlAdm->oHref();
	$oHref->set( 't', $this->gv['area']['t'] );
	$oHref->set( 'a', $this->gv['area']['a'] );
	$oHref->set( 'per', $this->gv['area']['per'] );
	$oHref->set( 'page', '{#}' );

	$ar_cfg = array(
		'url' => $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='. $oHref->get() ),
		'page_current' => $this->gv['page'],
		'items_total' => $cnt_records,
		'items_per_page' => $this->gv['area']['per'],
		'links_total' => $this->V->paginator_links_total,
		'links_more' => $this->V->paginator_links_more,
		'links_separator' => $this->V->paginator_links_separator,
		'current_tag' => 'strong',
		'phrase_next' => '<span style="font-size:75%">'.$this->oTkit->_( 1034 ).' &#8594;</span>',
		'phrase_prev' => '<span style="font-size:75%">&#8592; '.$this->oTkit->_( 1035 ).'</span>',
	);
	$oPaginatior = $this->_init_paginator( $ar_cfg );
	$oPaginatior->oTkit =& $this->oTkit;
}

/* Select all languages */
$this->oDb->select( 'l.*', false  );
$this->oDb->from( array( 'languages l' ) );
$this->oDb->order_by( 'l.is_active DESC, l.lang_name ASC' );
$this->oDb->limit( $this->gv['area']['per'], $offset );
$ar_sql = $this->oDb->get()->result_array();


/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'langs.manage' );

/* */
$ar_statuses = $this->oTarget->get_statuses();
/* */
$ar_classnames = $this->oTarget->get_statuses_classnames();
/* */
$ar_regions = $this->oTarget->get_regions();

/* */
$oHref_edit = $this->oHtmlAdm->oHref();
$oHref_edit->set( 'a', 'edit' );
$oHref_edit->set( 't', 'langs' );

$cnt = 1;
foreach ( $ar_sql as $ar_v )
{
	$oBlock->oTpl->assign( 'langs.list.cnt', ( $cnt + $offset ) );
	$oBlock->oTpl->assign( 'langs.list.id_lang', $ar_v['id_lang'] );

	/* Language name */
	$oHref_edit->set( 'id', $ar_v['id_lang'] );
	$lang_url = $this->oHtmlAdm->a_href(
					array( $this->V->file_index, '#area' => $oHref_edit->get() ),
					array( 'class' => 'btn edit' ),
					$ar_v['lang_name'].' - '.$ar_v['lang_native'].'<span class="icon-edt"></span>'
		);
	$oBlock->oTpl->assign( 'langs.list.lang_url', $lang_url );	

	$oBlock->oTpl->assign( 'langs.list.iso639-1', $ar_v['isocode1'] );
	$oBlock->oTpl->assign( 'langs.list.iso639-3', $ar_v['isocode3'] );
	$oBlock->oTpl->assign( 'langs.list.region', $ar_regions[$ar_v['region']] );
	$oBlock->oTpl->assign( 'langs.list.locale', $ar_v['isocode1'].'_'.$ar_v['region'] );

	$new_status = ( $ar_v['is_active'] ? GW_STATUS_OFF : GW_STATUS_ON );
	$oBlock->oTpl->assign( 'langs.list.status', '<a class="btn" href="javascript:void(0)" onclick="oLangs.onoff(this, '.$ar_v['id_lang'].', '. $new_status .')"><span class="'.$ar_classnames[$ar_v['is_active']].'">'.$ar_statuses[$ar_v['is_active']].'</span></a>' );

	/* Actions */
	$oJsMenuTr = new site_jsMenu();
	$oJsMenuTr->icon = '&#160;'.$this->V->str_class_dropdownmenu;
	$oJsMenuTr->event = 'onmouseover';
	$oJsMenuTr->classname = 'btn add';

	$oJsMenuTr->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area=' . $oHref_edit->get() ), $this->oTkit->_( 1042 ) );
	$oJsMenuTr->append( 'javascript:oLangs.remove_confirm('.$ar_v['id_lang'].')', $this->oTkit->_( 1043 ) );

	$oBlock->oTpl->assign( 'langs.list.actions', $oJsMenuTr->get_html() );

	$oBlock->oTpl->parse_block('langs.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) ); /* Pages */
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) ); /* Records found */
$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_( 1023 ) ); /* Actions */
$oBlock->oTpl->assign( 'l:1067', $this->oTkit->_( 1067 ) ); /* Status */

$oBlock->oTpl->assign( 'l:1184', $this->oTkit->_( 1184 ) ); /* Language name */
$oBlock->oTpl->assign( 'l:1185', $this->oTkit->_( 1185 ) ); /* Language URI */
$oBlock->oTpl->assign( 'l:1186', $this->oTkit->_( 1186 ) ); /* Region */
$oBlock->oTpl->assign( 'l:iso-639-1', $this->oTkit->_( 1188, 'ISO 639-1' ) ); /* 639-1 */
$oBlock->oTpl->assign( 'l:iso-639-3', $this->oTkit->_( 1188, 'ISO 639-3' ) ); /* 639-3 */
$oBlock->oTpl->assign( 'l:1188', $this->oTkit->_( 1188, '' ) ); /* Locale code */


$oBlock->oTpl->assign( 'v:id_table', 'langs-list' );
$this->oOutput->append_js( 'jsF.stripe("langs-list");' );
$oBlock->oTpl->assign( 'v:cnt_records', $cnt_records );

$this->oOutput->append_js( 'jsF.Set("oTkit_1043", "'.$this->oTkit->_( 1043 ).'");' ); /* Remove */
$this->oOutput->append_js( 'jsF.Set("oTkit_1051", "'.$this->oTkit->_( 1051 ).'");' ); /* Are you sure? */

$this->oOutput->append_js( 'jsF.Set("oTkit_1069", "'.$this->oTkit->_( 1069 ).'");' ); /* Active */
$this->oOutput->append_js( 'jsF.Set("oTkit_1070", "'.$this->oTkit->_( 1070 ).'");' ); /* Disabled */

if ( $int_pages > 1 )
{
	$oBlock->oTpl->assign( 'l:1038', $this->oTkit->_( 1038, '<strong>'.$this->oTkit->number_format( $this->gv['page'] ).'</strong>', $this->oTkit->number_format( $int_pages ) ) );
}
if ( isset( $oPaginatior ) ) 
{
	$oBlock->oTpl->assign( 'v:pagination', $oPaginatior->get() );
	$oBlock->oTpl->parse_block('if.paginator_top');
	$oBlock->oTpl->parse_block('if.paginator_bottom');
}


$this->oOutput->append_html( $oBlock->oTpl->get_html() );

/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
if ( $this->gv['sef_output'] != 'js' || $this->gv['sef_output'] != 'css' || $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1181 ).': '.$this->oTkit->_( 1006 ) );
}


?>