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

/**
 * ----------------------------------------------
 * Load Javascript
 * ----------------------------------------------
 */ 
 
$this->oOutput->append_js_collection( 'ajax' );
$this->oOutput->append_js_collection( 'o-translations' );


$this->gv['area']['per'] = 20;

/* Source language */
$this->gv['area']['source'] = $this->oFunc->get_crc_u('eng'.'US');
$this->gv['area']['source'] = isset( $this->ar_languages[$this->gv['area']['source']] ) ? $this->gv['area']['source'] : key( $this->ar_languages );



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
$this->oDb->select( 'count(*) cnt' );
$this->oDb->from( array( 'tv tv', 'languages l' ) );
$this->oDb->where( array( 'tv.is_complete' => '1' ) );
$this->oDb->where( array( 'l.is_active' => '1' ) );
$this->oDb->where( array( 'l.id_lang = tv.id_lang' => NULL ) );
$this->oDb->group_by( 'tv.id_lang' );
$ar_sql = $this->oDb->get()->result_array();
$cnt_records = sizeof( $ar_sql );

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



/* */
$ar_statuses = $this->oTarget->get_statuses();
/* */
$ar_classnames = $this->oTarget->get_statuses_classnames();
/* */
$ar_regions = $this->oTarget->get_regions();

/* */
$cnt_pids = $this->oTarget->count_pids_total();



/* Select languages */
$this->oDb->select( 'count(*) cnt, tv.id_lang, l.is_active, l.is_default, l.region, CONCAT(l.lang_name, " - ", l.lang_native) as lang', false );
$this->oDb->from( array( 'tv tv', 'languages l' ) );
$this->oDb->where( array( 'tv.is_complete' => '1' ) );
$this->oDb->where( array( 'l.is_active' => '1' ) );
$this->oDb->where( array( 'l.id_lang = tv.id_lang' => NULL ) );
$this->oDb->group_by( 'tv.id_lang' );
$this->oDb->order_by( 'l.is_active DESC, cnt DESC, l.lang_name ASC' );
$this->oDb->limit( $this->gv['area']['per'], $offset );
$ar_sql = $this->oDb->get()->result_array();

$ar_id_langs = array();

/* */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'translations.manage' );


/* */ 
$oHref_u = $this->oHtmlAdm->oHref();
$oHref_u->set( 'a', 'manage' );
$oHref_u->set( 't', 'tvs' );
$oHref_u->set( 'source', $this->gv['area']['source'] );

$cnt = 1;
foreach ( $ar_sql as $ar_v )
{
	$oBlock->oTpl->assign( 'translations.list.cnt', ( $cnt + $offset ) );
	$oBlock->oTpl->assign( 'translations.list.id_lang', $ar_v['id_lang'] );

	$percent = ( $ar_v['cnt'] / $cnt_pids ) * 100;
	$classname_percent = ( $percent < 100 ) ? 'state-warning' : 'state-allow';
	$classname_default = $ar_v['is_default'] ? 'state-allow' : 'hide-under';

	/* */
	if ( $percent == 100 )
	{
		$oHref_u->set( 'tab', '1' );
		$oHref_u->set( 'source', $ar_v['id_lang'] );
		$href_tvs = $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='.$oHref_u->get() );
		/* Link to translated phrases */
		$url_untranslated = '<a class="btn" href="'.$href_tvs.'">0</a>';
		$oBlock->oTpl->assign( 'translations.list.lang_url', 
			'<span class="hide-over"><a class="btn" href="'.$href_tvs.'">'.$ar_v['lang'].'</a> '.
			'<span id="hide-under-'.$ar_v['id_lang'].'" class="'.$classname_default.'"><a class="btn add" href="javascript:void(0)" onclick="oTranslations.d( this, '.$ar_v['id_lang'].' )">'. $this->oTkit->_( 1177 ).'</a> </span></span>'
		);
		$ar_id_langs[] = $ar_v['id_lang'];
	}
	else
	{
		$oHref_u->set( 'tab', '2' );
		$oHref_u->set( 'target', $ar_v['id_lang'] );
		$href_tvs = $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='.$oHref_u->get() );
		/* Link to untranslated phrases */
		$url_untranslated = '<a class="btn add" title="'.$this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1006 ).'" href="'.$href_tvs.'">'. $this->oTkit->number_format( $cnt_pids - $ar_v['cnt'] ) .'</a>';
		$oBlock->oTpl->assign( 'translations.list.lang_url', '<a class="btn edit" title="'.$this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1006 ).'" href="'.$href_tvs.'">'.$ar_v['lang'] .'</a>' );
	}
	
	$oBlock->oTpl->assign( 'translations.list.percent', '<span class="'.$classname_percent.'">'.$this->oTkit->number_format( $percent, 2 ).'%</span>' ) ;
	
	$oBlock->oTpl->assign( 'translations.list.untranslated', $url_untranslated );
	$oBlock->oTpl->assign( 'translations.list.region', $ar_regions[$ar_v['region']] );

	$oBlock->oTpl->parse_block('translations.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) ); /* Pages */
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) ); /* Records found */

$oBlock->oTpl->assign( 'l:1184', $this->oTkit->_( 1184 ) ); /* Language name */
$oBlock->oTpl->assign( 'l:1186', $this->oTkit->_( 1186 ) ); /* Region */
$oBlock->oTpl->assign( 'l:1205', $this->oTkit->_( 1205 ) ); /* Untranslated */
$oBlock->oTpl->assign( 'l:1189', $this->oTkit->_( 1189 ) ); /* Translated */
$oBlock->oTpl->assign( 'l:1177', $this->oTkit->_( 1177 ) ); /* By default */

$oBlock->oTpl->assign( 'v:id_table', 'trns-list' );
$this->oOutput->append_js( 'jsF.stripe("trns-list");' );
$oBlock->oTpl->assign( 'v:cnt_records', $cnt_records );

$this->oOutput->append_js( 'jsF.Set( "oTkit_1082", "'.$this->oTkit->_( 1082 ).'" );' ); /* Please, wait */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1041", "'.$this->oTkit->_( 1041 ).'" );' ); /* Settings saved */
$this->oOutput->append_js( 'jsF.Set( "oTkit_1177", "'.$this->oTkit->_( 1177 ).'" );' ); /* By default */

$this->oOutput->append_js( 'jsF.Set( "ar_id_langs", ["'. implode('","', $ar_id_langs) .'"] );' ); 


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
	$this->oOutput->append_html_title( $this->oTkit->_( 1190 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1190 ).': '.$this->oTkit->_( 1006 ) );
}


?>