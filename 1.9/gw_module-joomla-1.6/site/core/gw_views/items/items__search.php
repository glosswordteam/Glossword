<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	© Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
if (!defined('IS_IN_SITE')){die();}

$oSearchIndex = $this->_init_search_index();

$q = isset( $this->gv['q'] ) ? $this->gv['q'] : ( isset( $this->gv['area']['q'] ) ? $this->gv['area']['q'] : '');

/* Settings for web-search by default. */
$phrase = isset( $this->gv['area']['phrase'] ) ? $this->gv['area']['phrase'] : 'direct';
$ordering = isset( $this->gv['area']['order'] ) ? $this->gv['area']['order'] : 'no';


$q = preg_replace( "/ {2,}/", ' ', $q );
$q = trim( $q );
#$q = 'собой';

if ( $q == '' )
{
	/* Switch between output modes */
	switch ($this->gv['sef_output'])
	{
		case 'ajax':
			
			#header( 'Content-Type: text/plain; charset=utf-8' );
			print 0;
		break;
	}
	return;
}

/* */
$q_si = $oSearchIndex->text_si( $this->items__filter( $q ) );

/* Need to test. Could not be equal. */
$ar_words_sql = explode( ' ', $q_si );
$ar_words_q = explode( ' ', $q );

/* 11 Apr 2008: Enable search with asterisk for Chinese, Japanese and Korean characters */
foreach ( $ar_words_q as $k => $word )
{
	if ( $phrase != 'partial' && preg_match( '/[\x{3040}-\x{312F}|\x{3400}-\x{9FFF}|\x{AC00}-\x{D7AF}]/u', $word, $ar_matches ) )
	{
		$ar_words_sql[$k] .= '*';
	}
}

/* Switch search modes */
switch ( $phrase )
{
	case 'direct':
		$sql_against = '"'.implode( ' ', $ar_words_sql ).'"';
	break;
	case 'any':
		$sql_against = implode( ' ', $ar_words_sql );
	break;
	case 'exact':
		$sql_against = '"'.implode( ' ', $ar_words_sql ).'"';
	break;
	case 'partial':
		$sql_against = implode( '* ', $ar_words_sql ).'*';
	break;
	default:
		$sql_against = '+'.implode( ' +', $ar_words_sql );
	break;
}

/**
 * ----------------------------------------------
 * Select Item IDs
 * ----------------------------------------------
 */
$query = 'SELECT uri.item_uri, csi.id_item, c.contents_value_cached ';
$query .= "\n".' FROM '.$this->V->db_name.'.'.$this->V->table_prefix.'contents_si csi, ';
$query .= $this->V->db_name.'.'.$this->V->table_prefix.'items i, ';
$query .= $this->V->db_name.'.'.$this->V->table_prefix.'items_uri uri, ';
$query .= $this->V->db_name.'.'.$this->V->table_prefix.'contents c';
$query .= "\n".' WHERE ';
$query .= "\n".' MATCH(csi.contents_si) AGAINST(\''.$this->oDb->escape_str( $sql_against ).'\' IN BOOLEAN MODE)';
$query .= ' AND i.id_item = c.id_item ';
$query .= ' AND i.id_item = uri.id_item ';
$query .= ' AND i.id_item = csi.id_item ';
$query .= ' AND c.id_contents = csi.id_contents ';
$query .= ' AND c.id_field = "'.$this->V->id_field_root.'" ';
$query .= "\n".' GROUP BY csi.id_item ';

/* Switch sorting modes */
switch ( $ordering )
{
	case 'no':
	break;
	case 'newest':
		$query .= "\n".' ORDER BY i.item_cdate DESC '; 
	break;
	case 'oldest': 
		$query .= "\n".' ORDER BY i.item_cdate ASC '; 
	break;
	case 'popular': 
		$query .= "\n".' ORDER BY i.cnt_hits DESC, c.contents_a ASC, c.contents_b ASC '; 
	break;
	default: 
		$query .= "\n".' ORDER BY c.contents_a ASC, c.contents_b ASC '; 
	break;
}
$query .= "\n".' LIMIT 20';

$ar_sql = $this->oDb->query( $query )->result_array();

#print_r(  $query );
#print_r(  $ar_sql );

/* Switch between output modes */
switch ($this->gv['sef_output'])
{
	case 'ajax':
		
		foreach ($ar_sql as $ar_v)
		{
			$ar_js[] = strip_tags( $ar_v['contents_value_cached'] );
		}
		
		if ( empty( $ar_js ) )
		{
			print 0;
			return;
		}
		
		$str_js = json_encode( $ar_js );
		
		print $str_js;
		return;
	break;
	default:

		foreach ($ar_sql as $ar_v)
		{
			$this->gv['id'] = $ar_v['item_uri'];
			break;
		}

		if ( empty( $ar_sql ) )
		{
			/* No such term */
			$this->oOutput->append_html( $this->soft_redirect(
		 		$this->oTkit->_( 1046 ), $this->oHtml->url_normalize( $this->V->file_index.'?#sef_output='.$this->gv['sef_output'] ), GW_COLOR_FALSE
			));
			return false;
		}
		else if ( sizeof( $ar_sql ) == 1 )
		{
			/* One term found */
			$this->gv['action'] = 'view';
			$this->gv['target'] = 'items';
			$this->page_body();
		}
		else
		{
			/* Several items found */
			
		}

		
	break;

}
	

?>