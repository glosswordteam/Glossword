<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-infoblocks' );

/* Records per page */
if ( !isset( $this->gv['area']['per'] ) )
{
	$this->gv['area']['per'] = $this->V->items_per_page;
	/* Get value from users settings */
	if ( $this->oSess->user_get('area_per') )
	{
		$this->gv['area']['per'] = $this->oSess->user_get('area_per');
	}
}
if ( $this->gv['area']['per'] > 100 )
{
	$this->gv['area']['per'] = 100;
}
else if ( $this->gv['area']['per'] <= 3 )
{
	$this->gv['area']['per'] = 3;
}


/**
 * ----------------------------------------------
 * Count the number of records 
 * ----------------------------------------------
 */
$this->oDb->select( 'count(*) AS cnt' );
$this->oDb->from( array( 'blocks' ) );
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


/**
 * ----------------------------------------------
 * Select Infoblocks
 * ----------------------------------------------
 */
     
$this->oDb->select( 'b.*' );
$this->oDb->from( array( 'blocks b' ) );
$this->oDb->order_by( 'b.block_place ASC, b.block_name ASC' );
$this->oDb->limit( $this->gv['area']['per'], $offset );
$ar_sql = $this->oDb->get()->result_array();

/* */
$ar_places = $this->oTarget->get_places();
/* */
$ar_statuses = $this->oTarget->get_statuses();
/* */
$ar_classnames = $this->oTarget->get_statuses_classnames();


/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'infoblocks.manage' );

$oHref = $this->oHtmlAdm->oHref();

$cnt = 1;
foreach ( $ar_sql as $ar_v)
{
	$oHref->set( 't', 'infoblocks' );
	$oHref->set( 'a', 'edit' );
	$oHref->set( 'id', $ar_v['id_block'] );
	
	$str_block_name = $ar_v['block_name'];
	$str_block_url =  $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get(), '#uri' =>  base64_encode( $this->V->uri ) ),
				array( 'class' => 'btn edit' ),
				$str_block_name . $this->V->str_class_edit
		);

	/* Item descr */
	$str_block_contents = htmlspecialchars( $ar_v['block_contents'] );
	/* Smart substr */
	if ( $this->oFunc->mb_strlen( $str_block_contents ) > $this->V->int_max_chars_preview )
	{
		$str_block_contents = $this->oFunc->smart_substr( $str_block_contents, 0, $this->V->int_max_chars_preview );
	}

	$id_checkbox = 'item-'.$ar_v['id_block'];
	$oBlock->oTpl->assign( 'infoblocks.list.cnt', ( $cnt + $offset ) );
	$oBlock->oTpl->assign( 'infoblocks.list.id_checkbox', $id_checkbox );
	$oBlock->oTpl->assign( 'infoblocks.list.block_name', $str_block_url );
	$oBlock->oTpl->assign( 'infoblocks.list.block_contents', $str_block_contents );
	$oBlock->oTpl->assign( 'infoblocks.list.id_block', $ar_v['id_block'] );
	$oBlock->oTpl->assign( 'infoblocks.list.placement', $ar_places[$ar_v['block_place']] );
	$oBlock->oTpl->assign( 'infoblocks.list.status', '<span class="'.$ar_classnames[$ar_v['is_active']].'">'.$ar_statuses[$ar_v['is_active']].'</span>' );

	/* Actions */
	$oJsMenuTr = new site_jsMenu();
	$oJsMenuTr->icon = '&#160;'.$this->V->str_class_dropdownmenu;
	$oJsMenuTr->event = 'onmouseover';
	$oJsMenuTr->classname = 'btn add';
	
	$oJsMenuTr->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='. $oHref->get() ), $this->oTkit->_( 1042 ) );
	$oJsMenuTr->append( 'javascript:oInfoblocks.remove_confirm('.$ar_v['id_block'].')', $this->oTkit->_( 1043 ) );

	$oBlock->oTpl->assign( 'infoblocks.list.actions', $oJsMenuTr->get_html() );

	$oBlock->oTpl->parse_block('infoblocks.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_(1023) );
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) );
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) ); /* Records found */
$oBlock->oTpl->assign( 'l:1057', $this->oTkit->_( 1057 ) ); /* Placement */
$oBlock->oTpl->assign( 'l:1067', $this->oTkit->_( 1067 ) ); /* Status */


$oBlock->oTpl->assign( 'v:id_table', 'infoblocks-list' );
$this->oOutput->append_js( 'jsF.stripe("infoblocks-list");' );
$oBlock->oTpl->assign( 'v:cnt_records', $cnt_records );

$this->oOutput->append_js( 'jsF.Set("oTkit_1043", "'.$this->oTkit->_( 1043 ).'");' ); /* Remove */
$this->oOutput->append_js( 'jsF.Set("oTkit_1051", "'.$this->oTkit->_( 1051 ).'");' ); /* Are you sure? */

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
if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1054 ).': '.$this->oTkit->_( 1006 ) );
}


?>