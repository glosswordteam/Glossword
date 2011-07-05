<?php
if (!defined('IS_CLASS_SEARCH_INDEX')) { define('IS_CLASS_SEARCH_INDEX', 1);
class site_search_index
{
	private $oDb, $oCase;
	public $is_delete = true;
	public function __construct($oDb, $oCase)
	{
		$this->oDb =& $oDb;
		$this->oCase =& $oCase;
	}
	/* */
	public function text_normalize_lc( $s )
	{

#		$s = $this->oCase->rm_( $s );

		preg_match_all( "/./u", $s, $ar );
		$ar = $ar[0];
		$ar_c_crc = array();
		/* For each character */
		foreach ($ar AS $k => &$v )
		{
			/* PHP-bug: sometimes a string keys becomes interger */
			$ar_c_crc[$v] = $this->get_crc_u( $v );
		}
		unset( $v );
		if ( empty( $ar_c_crc ) ){ return $s; }
		/* */

		$is_debug_q = $this->oDb->is_debug_q;
		$this->oDb->is_debug_q = false;
		$this->oDb->select( 'str_from, str_to' );
		$this->oDb->from( 'unicode_normalization' );
		$this->oDb->where_in( 'crc32u', array_values( $ar_c_crc ) );
		$ar_sql = $this->oDb->get()->result_array();
		$this->oDb->is_debug_q = $is_debug_q;
		/* Normalize text */
		foreach ($ar_sql AS $k => &$v )
		{
			$s = str_replace( urldecode( $v['str_from'] ), urldecode( $v['str_to'] ), $s );
			unset( $ar_sql[$k] );
		}
		unset( $v );
		
		$s = $this->text_normalize( $s );

		return $s;
	}
	/* */
	public function text_si( $s )
	{
		/* Do lowercase */
		$s = $this->oCase->lc( $s );
		
		/* Mask non-ASCII characters */
		$s = preg_replace( "/([\\xc0-\\xff][\\x80-\\xbf]*)/e", "'U8' . bin2hex( \"$1\" )", $s );
		
		/* Mask MySQL stopwords */
		$s = $this->mask_stopwords( $s );
		
		return $s;
	}
	
	/**
	 * 
	 */
	public function add_contents( $id_contents, $id_item, $id_lang, $value )
	{
		$this->oDb->insert( 'contents_si', array(
			'id_contents' => $id_contents, 
			'id_item' => $id_item, 
			'id_lang' => $id_lang,
			'contents_si' => $this->text_si( $value )
		) );
	}
	/**
	 * 
	 */
	public function update_contents( $id_contents, $id_item, $id_lang, $value )
	{
#		$this->oDb->update( 'contents_si', 
#			array( 'id_item' => $id_item, 'id_lang' => $id_lang, 'contents_si' => $this->text_normalize_lc( $value ) ),
#			array( 'id_contents' => $id_contents ) 
#		);
		if ( $this->is_delete )
		{
			$this->remove_contents( $id_contents );
		}
		$this->add_contents( $id_contents, $id_item, $id_lang, $value );
	}
	public function remove_contents( $id_contents )
	{
		$this->oDb->delete( 'contents_si', array( 'id_contents' => $id_contents ), 1 );
	}
	
	/**
	 * 
	 */
	public static function get_crc_u( $s )
	{
		return sprintf( "%u", crc32( $s ) );
	}
	
	/* Stopwords for MySQL */
	private static function mask_stopwords( $s )
	{
		return preg_replace( '/\b([a-z]+)/', '_\\1', $s );
	}
}
}
?>