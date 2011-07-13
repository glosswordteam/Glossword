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
$this->oOutput->append_js_collection( 'o-tvs' );

/**
 * ----------------------------------------------
 * Default settings
 * ----------------------------------------------
 */ 
/* Records per page */
$this->gv['area']['per'] = isset( $this->gv['area']['per'] ) 
	? $this->gv['area']['per'] 
	: ( $this->oSess->user_get('area_tvs_per') ? $this->oSess->user_get('area_tvs_per') : $this->V->items_per_page );
if ( $this->gv['area']['per'] > 100 ) { $this->gv['area']['per'] = 100; }
else if ( $this->gv['area']['per'] <= 3 ) { $this->gv['area']['per'] = 3; }
/* Save to a user settings */
if ( !$this->oSess->is_guest() && $this->oSess->user_get( 'area_tvs_per' ) != $this->gv['area']['per'] ) 
{
	$this->oSess->user_set( 'area_tvs_per', $this->gv['area']['per'] );
}

/* Source and target languages */
$this->gv['area']['source'] = isset( $this->gv['area']['source'] ) 
	? $this->gv['area']['source'] 
	: ( $this->oSess->user_get('area_tvs_source') ? $this->oSess->user_get('area_tvs_source') : $this->oFunc->get_crc_u( 'eng'.'US' ) );
$this->gv['area']['source'] = isset( $this->ar_languages[$this->gv['area']['source']] ) ? $this->gv['area']['source'] : key( $this->ar_languages );

$this->gv['area']['target'] = isset( $this->gv['area']['target'] ) 
	? $this->gv['area']['target'] 
	: ( $this->oSess->user_get('area_tvs_target') ? $this->oSess->user_get('area_tvs_target') : $this->oFunc->get_crc_u( 'spa'.'ES' ) );
$this->gv['area']['target'] = isset( $this->ar_languages[$this->gv['area']['target']] ) ? $this->gv['area']['target'] : key( $this->ar_languages );

/* Save to a user settings */
if ( !$this->oSess->is_guest() && $this->oSess->user_get( 'area_tvs_source' ) != $this->gv['area']['source'] ) 
{
	$this->oSess->user_set( 'area_tvs_source', $this->gv['area']['source'] );
}
if ( !$this->oSess->is_guest() && $this->oSess->user_get( 'area_tvs_target' ) != $this->gv['area']['target'] ) 
{
	$this->oSess->user_set( 'area_tvs_target', $this->gv['area']['target'] );
}

/* Tabs - [ All | Incomplete ] */
$this->gv['area']['tab'] = isset( $this->gv['area']['tab'] ) ? $this->gv['area']['tab'] : 1;


/**
 * ----------------------------------------------
 * Display `Settings saved` notice
 * ----------------------------------------------
 */ 
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
$this->oDb->from( array('pid p' ) );
$this->oDb->join( 'tv tv1', 'tv1.id_pid = p.id_pid AND tv1.id_lang = \''.$this->gv['area']['source'].'\'', 'left', false );
$this->oDb->join( 'tv tv2', 'tv2.id_pid = p.id_pid AND tv2.id_lang = \''.$this->gv['area']['target'].'\'', 'left', false );
/* Incomplete */
if ( $this->gv['area']['tab'] == 2  )
{
	$this->oDb->where( ' ( 1=1 ', NULL ); /* Trick for ActiveRecord */
	$this->oDb->where( array( 'tv1.is_complete' => '0' ) );
	$this->oDb->or_where( array( 'tv2.is_complete' => '0' ) );
	$this->oDb->or_where( array( 'tv1.is_complete' => NULL, 'tv2.is_complete' => NULL ) );
	$this->oDb->where( ' 1=1 )', NULL ); /* Trick for ActiveRecord */
}
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
 * Count total number of Translation Variants
 * ----------------------------------------------
 */
$cnt_tvs_total = $this->oTarget->count_pids_total();

/**
 * ----------------------------------------------
 * Count translated and untranslated Translation Variants
 * ----------------------------------------------
 */
$ar_translated = $this->oTarget->count_translated();
$cnt_tvs_untranslated = 0;
foreach ( $ar_translated as $id_lang => $cnt )
{
	$cnt_tvs_untranslated += $cnt_tvs_total - $cnt;
}

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
	$oHref->set( 'source', $this->gv['area']['source'] );
	$oHref->set( 'target', $this->gv['area']['target'] );
	$oHref->set( 'tab', $this->gv['area']['tab'] );
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
 * Select Translation Variants
 * ----------------------------------------------
 */
$this->oDb->select( 'p.id_pid, p.pid_value', false  );
$this->oDb->select( 'tv1.id_tv id_tv1, tv1.is_active is_active1, tv1.is_complete is_complete1, tv1.tv_value tv_value1, tv1.id_user_modified id_user_modified1', false  );
$this->oDb->select( 'tv2.id_tv id_tv2, tv2.is_active is_active2, tv2.is_complete is_complete2, tv2.tv_value tv_value2, tv2.id_user_modified id_user_modified2', false  );
$this->oDb->from( array( 'pid p' ) );
$this->oDb->join( 'tv tv1', 'tv1.id_pid = p.id_pid AND tv1.id_lang = \''.$this->gv['area']['source'].'\'', 'left', false );
$this->oDb->join( 'tv tv2', 'tv2.id_pid = p.id_pid AND tv2.id_lang = \''.$this->gv['area']['target'].'\'', 'left', false );
/* Incomplete */
if ( $this->gv['area']['tab'] == 2  )
{
	$this->oDb->where( ' ( 1=1 ', NULL ); /* Trick for ActiveRecord */
	$this->oDb->where( array( 'tv1.is_complete' => '0' ) );
	$this->oDb->or_where( array( 'tv2.is_complete' => '0' ) );
	$this->oDb->or_where( array( 'tv1.is_complete' => NULL, 'tv2.is_complete' => NULL ) );
	$this->oDb->where( ' 1=1 )', NULL ); /* Trick for ActiveRecord */
}
$this->oDb->order_by( 'tv1.tv_value' );
$this->oDb->limit( $this->gv['area']['per'], $offset );
$ar_sql = $this->oDb->get()->result_array();



/**
 * ----------------------------------------------
 * New HTML-template
 * ----------------------------------------------
 */
$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'tvs.manage' );

/* */
#$ar_statuses = $this->oTarget->get_statuses();
/* */
#$ar_classnames = $this->oTarget->get_statuses_classnames();
/* */
$ar_classnames_borders = $this->oTarget->get_statuses_borders();

$ar_id_tv_source = $ar_id_tv_target = $ar_id_pids = array();

/* */
$cnt = 1;
foreach ( $ar_sql as $ar_v )
{
	if ( $ar_v['id_tv1'] == NULL ) { $ar_v['id_tv1'] = 'null1-'.$cnt; }
	if ( $ar_v['id_tv2'] == NULL ) { $ar_v['id_tv2'] = 'null2-'.$cnt; }
	if ( $ar_v['is_active1'] == NULL ) { $ar_v['is_active1'] = 0; }
	if ( $ar_v['is_active2'] == NULL ) { $ar_v['is_active2'] = 0; }
	if ( $ar_v['tv_value1'] == NULL ) { $ar_v['tv_value1'] = ''; }
	if ( $ar_v['tv_value2'] == NULL ) { $ar_v['tv_value2'] = ''; }
	
	$ar_id_tv_source[] = $ar_v['id_tv1'];
	$ar_id_tv_target[] = $ar_v['id_tv2'];
	$ar_id_pids[] = $ar_v['id_pid'];

	/* */
	$oBlock->oTpl->assign( 'tvs.list.cnt', ( $cnt + $offset ) );
	
	/* Store real values in hidden fields */
	$ar_form_hidden[] = '<input id="v-'. $ar_v['id_tv1'] .'" type="hidden" value="'. rawurlencode( $ar_v['tv_value1'] ).'" />';
	$ar_form_hidden[] = '<input id="v-'. $ar_v['id_tv2'] .'" type="hidden" value="'. rawurlencode( $ar_v['tv_value2'] ).'" />';

	/* Trim long Translation Variants */
	$ar_v['tv_value1'] = $this->oFunc->smart_substr( $ar_v['tv_value1'], 0, 100 );
	$ar_v['tv_value2'] = $this->oFunc->smart_substr( $ar_v['tv_value2'], 0, 100 );

	$oBlock->oTpl->assign( 'tvs.list.tv_value1', htmlspecialchars( $ar_v['tv_value1'] ).'&#160;' );
	$oBlock->oTpl->assign( 'tvs.list.tv_value2', htmlspecialchars( $ar_v['tv_value2'] ).'&#160;' );
	$oBlock->oTpl->assign( 'tvs.list.pid_value', htmlspecialchars( $ar_v['pid_value'] ).'&#160;' );
	$oBlock->oTpl->assign( 'tvs.list.id_pid', $ar_v['id_pid'] );

	/* */
	$ar_div_tv_attr1 = $ar_div_tv_attr2 = array();
	$ar_div_tv_attr1['class'] = 'class="divtv '.$ar_classnames_borders[$ar_v['is_active1']].'"';
	$ar_div_tv_attr1['id'] = 'id="divtv-'.$ar_v['id_tv1'].'"';
	$ar_div_tv_attr1['onclick'] = 'onclick="oTvs.edit( \''.$ar_v['id_tv1'].'\', \''.$ar_v['id_pid'].'\', \''.$ar_v['id_tv2'].'\', \''.$this->gv['area']['target'].'\', \''.$this->gv['area']['source'].'\' )"';

	$ar_div_tv_attr2['class'] = 'class="divtv '.$ar_classnames_borders[$ar_v['is_active2']].'"';
	$ar_div_tv_attr2['id'] = 'id="divtv-'.$ar_v['id_tv2'].'"';
	$ar_div_tv_attr2['onclick'] = 'onclick="oTvs.edit( \''.$ar_v['id_tv2'].'\', \''.$ar_v['id_pid'].'\', \''.$ar_v['id_tv1'].'\', \''.$this->gv['area']['source'].'\', \''.$this->gv['area']['target'].'\' )"';

	$oBlock->oTpl->assign( 'tvs.list.div_tv_attr1', implode( ' ', $ar_div_tv_attr1 ) );
	$oBlock->oTpl->assign( 'tvs.list.div_tv_attr2', implode( ' ', $ar_div_tv_attr2 ) );
	$oBlock->oTpl->assign( 'v:hidden_fields', implode( ' ', $ar_form_hidden ) );

	$oBlock->oTpl->parse_block('tvs.list');
	++$cnt;
}




/**
 * ----------------------------------------------
 * Tabs for Translation Variants
 * ----------------------------------------------
 */
$ar_str_tabs = array();
$oHref_tab = $this->oHtml->oHref();
$oHref_tab->set( array( 'a' => 'manage', 't' => 'tvs', 'per' => $this->gv['area']['per'], 
	'source' => $this->gv['area']['source'], 'target' => $this->gv['area']['target'] 
));
$attr_incomplete = $cnt_tvs_untranslated ? ' class="state-warning"' : '';
foreach ( array( 
		1 => $this->oTkit->_( 1044 ). ' <strong>('. ( $cnt_tvs_total * 2 ) .')</strong>', 
		2 => $this->oTkit->_( 1205 ). ' <strong'.$attr_incomplete.'>('.$cnt_tvs_untranslated.')</strong>' 
	) as $id_tab => $l )
{
	$oHref_tab->set( 'tab', $id_tab );
	$classname = ( $id_tab == $this->gv['area']['tab'] ) ? 'on' : '';
	$ar_str_tabs[$id_tab] = $this->oHtmlAdm->a_href( 
		array( $this->V->file_index, '#area' => $oHref_tab->get() ), array( 'class' => $classname ), $l 
	);
	/* Disable link to untranslated */
	if ( $id_tab == 2 && !$cnt_tvs_untranslated)
	{
		$ar_str_tabs[$id_tab] = $this->oHtmlAdm->a_href( 
			array( 'javascript:void(0)' ), array( 'class' => $classname ), $l 
		);
	}
}
$oBlock->oTpl->assign( 'v:tabs_tvs', '<div class="gw-tabs">'.implode(' ', $ar_str_tabs ).'</div>' );
	

/**
 * ----------------------------------------------
 * Source and Target Language selectors
 * ----------------------------------------------
 */
$oHref_select = $this->oHtmlAdm->oHref();
$oHref_select->set( 't', $this->gv['area']['t'] );
$oHref_select->set( 'a', $this->gv['area']['a'] );
$oHref_select->set( 'per', $this->gv['area']['per'] );
/* 1.9.4: Keep page number */
if ( isset( $this->gv['area']['page'] ) )
{
	$oHref_select->set( 'page', $this->gv['area']['page'] );
}
$oHref_select->set( 'source', $this->gv['area']['source'] );
$oHref_select->set( 'tab', $this->gv['area']['tab'] );

/* Select languages: Source */
$oHref_select->set( 'target', $this->gv['area']['target'] );
$oJsMenu = new site_jsMenu();
$oJsMenu->icon = '&#160;'.$this->ar_languages[$this->gv['area']['source']].$this->V->str_class_dropdown;
$oJsMenu->event = 'onclick';
$oJsMenu->classname = '';
foreach ( $this->ar_languages as $id_lang => $lang_name )
{
	if ( $id_lang == $this->gv['area']['source'] )
	{ 
		$this->oOutput->append_js( 'jsF.Set("lang_name_source", "'.$lang_name.'");' );
		continue; 
	}
	$oHref_select->set( 'source',  $id_lang );
	$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='. $oHref_select->get() ), $lang_name );
}
$oBlock->oTpl->assign( 'v:lang_select1', $oJsMenu->get_html() );

/* Select languages: Target */
$oHref_select->set( 'source', $this->gv['area']['source'] );
$oJsMenu = new site_jsMenu();
$oJsMenu->icon = '&#160;'.$this->ar_languages[$this->gv['area']['target']].$this->V->str_class_dropdown;
$oJsMenu->event = 'onclick';
$oJsMenu->classname = '';
foreach ( $this->ar_languages as $id_lang => $lang_name )
{
	if ( $id_lang == $this->gv['area']['target'] )
	{
		$this->oOutput->append_js( 'jsF.Set("lang_name_target", "'.$lang_name.'");' ); 
		continue;
	}
	$oHref_select->set( 'target',  $id_lang );
	$oJsMenu->append( $this->oHtmlAdm->url_normalize( $this->V->file_index.'?#area='. $oHref_select->get() ) , $lang_name );
}
$oBlock->oTpl->assign( 'v:lang_select2', $oJsMenu->get_html() );

$this->oOutput->append_js( 'jsF.Set( "id_lang_target", "'. $this->gv['area']['target'] .'" );' ); 
$this->oOutput->append_js( 'jsF.Set( "id_lang_source", "'. $this->gv['area']['source'] .'" );' ); 

$this->oOutput->append_js( 'jsF.Set( "ar_id_pids", ["'. implode('","', $ar_id_pids) .'"] );' ); 
$this->oOutput->append_js( 'jsF.Set( "ar_id_tv_source", ["'. implode('","', $ar_id_tv_source) .'"] );' ); 
$this->oOutput->append_js( 'jsF.Set( "ar_id_tv_target", ["'. implode('","', $ar_id_tv_target) .'"] );' ); 
	
/**
 * ----------------------------------------------
 * Assign values for HTML-template
 * ----------------------------------------------
 */
$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1033', $this->oTkit->_( 1033 ) ); /* Pages */
$oBlock->oTpl->assign( 'l:1036', $this->oTkit->_( 1036 ) ); /* Records found */
$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_( 1023 ) ); /* Actions */
$oBlock->oTpl->assign( 'l:1067', $this->oTkit->_( 1067 ) ); /* Status */
$oBlock->oTpl->assign( 'l:1208', $this->oTkit->_( 1208 ) ); /* Phrase ID */

/* */
$oBlock->oTpl->assign( 'v:id_table', 'tvs-list' );
$this->oOutput->append_js( 'jsF.stripe("tvs-list");' );
$oBlock->oTpl->assign( 'v:cnt_records', $cnt_records );

$this->oOutput->append_js( 'jsF.Set("oTkit_1043", "'.$this->oTkit->_( 1043 ).'");' ); /* Remove */
$this->oOutput->append_js( 'jsF.Set("oTkit_1051", "'.$this->oTkit->_( 1051 ).'");' ); /* Are you sure? */

$this->oOutput->append_js( 'jsF.Set("oTkit_1206", "'.$this->oTkit->_( 1206 ).'");' ); /* Target language */
$this->oOutput->append_js( 'jsF.Set("oTkit_1187", "'.$this->oTkit->_( 1187 ).'");' ); /* Source language */
$this->oOutput->append_js( 'jsF.Set("oTkit_1154", "'.$this->oTkit->_( 1154 ).'");' ); /* Close [x] */
$this->oOutput->append_js( 'jsF.Set("oTkit_1034", "'.$this->oTkit->_( 1034 ).'");' ); /* Next */
$this->oOutput->append_js( 'jsF.Set("oTkit_1035", "'.$this->oTkit->_( 1035 ).'");' ); /* Prev */
$this->oOutput->append_js( 'jsF.Set("oTkit_1017", "'.$this->oTkit->_( 1017 ).'");' ); /* Save */


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
	$this->oOutput->append_html_title( $this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1182 ).': '.$this->oTkit->_( 1006 ) );
}
/* Add page number to title */
if ( $int_pages > 1 && $this->gv['page'] > 1 )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1038, $this->gv['page'], $int_pages ) );
}


?>