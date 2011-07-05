<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}


/**
 * ----------------------------------------------
 * Select items
 * ----------------------------------------------
 */
#$this->o->oDb->select( 'count(*) AS cnt' );
#$this->o->oDb->from( array( 'items' ) );
#$ar_sql = $this->o->oDb->get()->result_array();
#$cnt_records = isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0;

/* */
$this->o->oDb->select( 'i.id_item, i.item_id_user_created, i.is_complete, f.xml_tag, l.region, l.isocode1' );
$this->o->oDb->select( 'c.contents_value, c.contents_value_cached, c.id_field, c.id_contents, c.id_lang' );
if ( $ar['is_si'] )
{
	$this->o->oDb->select( 'csi.contents_si' );
}
if ( $ar['is_az'] )
{
	$this->o->oDb->select( 'c.contents_a, c.contents_b' );
}
if ( $ar['is_term_uri'] )
{
	$this->o->oDb->select( 'uri.item_uri' );
}
$this->o->oDb->from( array( 'items i', 'items_uri uri', 'contents c', 'contents_si csi', 'map_field_to_fieldset mftf', 'fields f', 'languages l' ) );
$this->o->oDb->where( array( 'c.id_contents = csi.id_contents' => NULL ) );
$this->o->oDb->where( array( 'f.id_field = c.id_field' => NULL ) );
$this->o->oDb->where( array( 'i.id_item = c.id_item' => NULL ) );
$this->o->oDb->where( array( 'i.id_item = uri.id_item' => NULL ) );
$this->o->oDb->where( array( 'l.id_lang = c.id_lang' => NULL ) );
$this->o->oDb->where( array( 'mftf.id_field = c.id_field' => NULL ) );
$this->o->oDb->where( array( 'mftf.id_fieldset' => '1' ) );
if ( $ar['td_mode'] == 't' )
{
	$this->o->oDb->where( array( 'c.id_field' => '1' ) );
}
$this->o->oDb->order_by( 'i.id_item, mftf.int_sort ASC' );
$this->o->oDb->limit( $this->o->V->int_search_max );

$ar_sql_items = $this->o->oDb->get()->result_array();

/* Re-arrange */
$ar_items = array();
foreach ( $ar_sql_items as $ar_v)
{
	$ar_items[$ar_v['id_item']][$ar_v['id_field']][$ar_v['id_contents']] = $ar_v;
}
unset( $ar_sql_items );

#prn_r( $ar );
#prn_r( $ar_items  );

$str = '';


switch ( $ar['format'] )
{
	case 1: /** Glossword XML **/
		$str .= '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';	
		$str .= CRLF.'<glossword version="'.$this->o->V->version.'">';
	break;
	case 2: /** CSV/TSV  **/
		/* Allow \t as separator */
		$ar['separator'] = str_replace( '\t', "\t", $ar['separator'] );
	
		if ( $ar['is_head'] )
		{
			$str .= implode( $ar['separator'], array( 'term', 'defn', 'seealso' ) ) . CRLF;
		}
	break;
}

$cnt = 1;
foreach ( $ar_items as $id_item => $ar_item )
{
	$ar_defn_complete = array();

	switch ( $ar['format'] )
	{
		case 1: /** Glossword XML **/

			foreach ( $ar_item as $id_field => $ar_content)
			{
				$ar_str_field = array();
				foreach ( $ar_content as $id_contents => $ar_v)
				{
					if ( $id_field == $this->o->V->id_field_root )
					{
						$attr_id = ( $ar['is_id_item'] ? ' id="'.$id_item.'"' : '' );
						$str .= CRLF . '<entry'.$attr_id.'>';
						if ( $ar['is_term_uri'] )
						{
							$str .= CRLF . "\t".'<uri>'.$ar_v['item_uri'].'</uri>';
						}
					}

					$ar_v['contents_a'] = str_replace( "\0", '', $ar_v['contents_a'] );
					$ar_v['contents_b'] = str_replace( "\0", '', $ar_v['contents_b'] );

					$attr_az = ( $ar['is_az'] ? ' a1="'.htmlspecialchars( $ar_v['contents_a'] ).'" a2="'.htmlspecialchars( $ar_v['contents_b'] ).'"' : '' );
					$attr_lang = ' xml:lang="'.$ar_v['isocode1'] .'_'. $ar_v['region'].'"';
					
					$ar_str_field[$id_contents] = CRLF . "\t".'<'.$ar_v['xml_tag'].$attr_az.$attr_lang.'>';
					$ar_str_field[$id_contents] .= '<![CDATA['. $ar_v['contents_value'] .']]>';

						if ( $ar['is_cached'] && $ar_v['contents_value_cached'] != '' )
						{
							$ar_str_field[$id_contents] .= CRLF . "\t\t".'<cached>';
							$ar_str_field[$id_contents] .= '<![CDATA['. $ar_v['contents_value_cached'] .']]>';
							$ar_str_field[$id_contents] .= '</cached>';
						}
						if ( $ar['is_si'] && $ar_v['contents_si'] != '' )
						{
							$ar_str_field[$id_contents] .= CRLF . "\t\t".'<si>';
							$ar_str_field[$id_contents] .= '<![CDATA['. $ar_v['contents_si'] .']]>';
							$ar_str_field[$id_contents] .= '</si>';
						}
					
					$ar_str_field[$id_contents] .= '</'.$ar_v['xml_tag']. '>';
				}
				$str_field = implode( " ", $ar_str_field );

				/* */
				if ( $id_field == $this->o->V->id_field_root )
				{
					/* At once */
					$str .= $str_field;
				}

				/* Collect fields for a common definition */
				switch ( $id_field )
				{
					/* Do not include Item into Definition text */
					case 1:
					break;
					default:
						$ar_defn_complete[] = $str_field;
					break;
				}
			}
			$str .= implode( " ", $ar_defn_complete );
			$str .= CRLF . '</entry>';
		break;
		case 2: /** CSV/TSV **/
		
			$ar_csv = $ar_dpl = array();
			
			/* Columns order, id_field => column order */
			$ar_order_csv = array(
				1 => 0,
				2 => 1,
				3 => 2,
			);

			foreach ( $ar_item as $id_field => $ar_content)
			{
				foreach ( $ar_content as $id_contents => $ar_v)
				{
					/* Fix escape sequences */
					$ar_esc = array( '\r\n' => "\r\n", '\n' => "\n", '\r' => "\r", '\t' => "\t" );
					$ar_v['contents_value'] = str_replace( array_keys($ar_esc), array_values($ar_esc), $ar_v['contents_value'] );
					
					/* Skip field */
					if ( !isset( $ar_order_csv[$id_field] ) ) { continue; }
					
					if ( isset( $ar_csv[$id_item][$ar_order_csv[$id_field]] ) )
					{
						/* Collect unique */
						$ar_dpl[$id_item][$ar_order_csv[$id_field]] = $ar_v['contents_value'];
					}
					else
					{
						/* Collect duplicates */
						$ar_csv[$id_item][$ar_order_csv[$id_field]] = $ar_v['contents_value'];
					}
				}
			}
			ksort( $ar_csv[$id_item] );
			if ( $cnt > 1 )
			{
				$str .= CRLF;
			}
			$str .= str_putcsv( $ar_csv[$id_item], $ar['separator'] );

			/* Second definitions */
			if ( isset( $ar_dpl[$id_item] ) ) 
			{
				/* */
				foreach( $ar_order_csv as $id_field => $cnt_order)
				{
					if ( !isset( $ar_dpl[$id_item][$cnt_order] ) ) 
					{
						$ar_dpl[$id_item][$cnt_order] = isset( $ar_csv[$id_item][$cnt_order] ) ? $ar_csv[$id_item][$cnt_order] : '';
					}
				}
				ksort( $ar_dpl[$id_item] );
				$str .= CRLF . str_putcsv( $ar_dpl[$id_item], $ar['separator'] );
			}
		break;
	}
	++$cnt;
}

/* */
switch ( $ar['format'] )
{
	case 1: /** Glossword XML **/
		$filename = 'gw_items_'.@date( "Y-m[M]-d", $this->o->V->time_gmt ).'.xml';
		$str .= CRLF . '</glossword>';
	break;
	case 2: /** CSV/TSV **/
		$filename = 'gw_items_'.@date( "Y-m[M]-d", $this->o->V->time_gmt ).'.csv';
	break;
}


/* */
$filename_abs = $this->o->V->path_temp_abs .'/'. $this->o->V->path_export .'/'.$filename;
$filename_rel = $this->o->V->server_dir.'/'.$this->o->V->path_temp_web.'/'.$this->o->V->path_export.'/'.$filename;

$filesize = strlen( $str );

if ( $ar['is_save'] )
{
	/* Send headers */
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) )
	{
		header( 'Content-Type: application/force-download' );
	}
	else
	{
		header( 'Content-Type: application/octet-stream' );
	}
	header( 'Content-Length: '. $filesize );
	header( 'Content-disposition: attachment; filename="'.$filename.'"' );
	print $str;
	exit;
}
else
{
	/* */
	$this->o->oOutput->append_html( '<div class="'.GW_COLOR_TRUE.' nostatus" id="status"><ul class="xt">' );
	$this->o->oOutput->append_html( '<li><span class="gray"><a href="'. $filename_rel .'?r='.$this->o->V->time_gmt.'">'. $filename.'</a>…</span> ' );

	/* Write file */
	$is_write = 0;
	$is_write = $this->o->oFunc->file_put_contents( $filename_abs, $str, 'w' );
	$this->o->oOutput->append_html( ( $is_write ) ? $this->o->oTkit->bytes( $filesize ) : $this->o->oTkit->_(1083, $filename) );
		
	$this->o->oOutput->append_html( '</li>' );
	$this->o->oOutput->append_html( '</ul></div>' );

	/* */
	$is_redirect = 0;
}


?>