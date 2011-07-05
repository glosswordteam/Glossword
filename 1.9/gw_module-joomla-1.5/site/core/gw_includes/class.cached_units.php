<?php
/**
 * @version		$Id$
 * @copyright	Dmitry N. Shilnikov, 2006-2010
 * @license		Commercial
 */
/**
 * Class to store PHP-variables in a cache.
 * 
 * All cached units are stored in database. We can't use a file system because of 
 * problems with clearing expired units.
 * 
 * Changes:
 *  19 Jan 2010: make_bigint16() renamed to make_bigint().
 *  2 Sep 2009: Longer numbers for make_bigint16() (18 characters).
 *  18 Aug 2009: Changed algorithm for make_bigint16(). Numbers are always started with 1.
 */
class site_cache
{
	/* Cache life time in seconds */
	public $lifetime_sec = 60;
	public $id_unit = 0;
	public $arStatus = array();
	public $db_tablename = 'cached_units';
	public $unit_group = '';
	public $is_enable = 1;
	public $is_expired = 1;
	public $is_exist = 0;
	public $diff_sec = 0;
	public $oDb;
	public $value;
	public $str_seconds = 'sec.';
	public $str_minutes = 'min.';
	public $str_hours = 'hrs';
	public $str_days = 'days';
	private $datetime;
	
	/* */
	public function __construct(&$oDb, $lifetime_sec)
	{
		$this->datetime = @date( "Y-m-d H:i:s", mktime() );
		$this->oDb = $oDb;
		$this->lifetime_sec = $lifetime_sec;
	}
	/* */
	public function text_sec_to_str($sec)
	{
		$str['days'] = intval( ($sec / 3600) / 24 );
		$str['hours'] = intval( ($sec - (($str['days'] * 24) * 3600)) / 3600 );
		$str['minutes'] = intval(($sec - (($str['days'] * 24 * 3600) + ($str['hours'] * 3600))) / 60);
		$str['seconds'] = $sec - (($str['days'] * 24 * 3600) + ($str['hours'] * 3600) + ($str['minutes'] * 60));
		$str['days'] .= ' '.$this->str_days;
		$str['hours'] .= ' '.$this->str_hours;
		$str['minutes'] .= ' '.$this->str_minutes;
		$str['seconds'] .= ' '.$this->str_seconds;
		return implode(' ', $str);
	}
	/**
	 * Loads cached object.
	 * 
	 * @param  string Unit name.
	 * @param  string Unit group name. Default is ''.
	 * @param  int    Lifetime in seconds. Default is 0.
	 * @return TRUE if success, FALSE otherwise.
	 */
	public function in_cache( $id_unit, $unit_group = '', $lifetime_sec = 0 )
	{
		if ( !$this->is_enable ){ return false; }
		/* Generate unique numbers */
		$this->id_unit = $this->make_bigint( $id_unit );
		$this->unit_group = $this->make_bigint( $unit_group );
		/* An individual lifetime for each cached object */
		$this->lifetime_sec = $lifetime_sec;
		/* Check for cached object in database */
		$this->oDb->select( '*' );
		$this->oDb->from( $this->db_tablename );
		$this->oDb->where( array( 'id_unit' => $this->id_unit, 'unit_group' => $this->unit_group ) );
		$this->oDb->limit( 1 );
		$ar_sql = $this->oDb->get()->result_array();
		
		if ( empty($ar_sql) )
		{
			/* No results */
			$this->is_exist = 0;
			$this->is_expired = 1;
			return false;
		}
		$this->is_exist = 1;
		/* Check if the cache unit has been expired */
		if ( $lifetime_sec )
		{
			/* Set `expired` by default */
			$this->is_expired = 1;
			/* Calculate the difference */
			$this->diff_sec = mktime() - strtotime( $ar_sql[0]['cdate'] );
			if ( $this->diff_sec <= $lifetime_sec )
			{
				/* Set `not expired` */
				$this->is_expired = 0;
			}
			if ( $this->is_expired )
			{
				return false;
			}
		}
		/* Load cache unit contents */
		$this->value = $ar_sql[0]['unit_value'];
		return true;
	}
	/* */
	static public function make_bigint( $s )
	{
		$h = hash( 'md5', $s );
		$n = '1';
		for ( $i = 0; $i < 32; $i++ )
		{
			$n .= hexdec( substr( $h, $i, 2 ) );
			if ( strlen( $n ) > 18 )
			{
				$n = substr( $n, 0, 18 );
				break;
			}
			$i++;
		}
		return strval( $n );
	}
	/* */
	public function remove_by_group( $s )
	{
		$this->remove( 0, $s );
	}
	public function remove_by_unit( $s )
	{
		$this->remove( $s, 0 );
	}
	private function remove( $id_unit_ref = 0, $unit_group  = 0 )
	{
		$ar = array();
		if ( $id_unit_ref )
		{
			$ar['id_unit'] = $this->make_bigint( $id_unit_ref );
		}
		if ( $unit_group )
		{
			$ar['unit_group'] = $this->make_bigint( $unit_group );
		}
		$this->oDb->delete( $this->db_tablename, $ar );
	}
	/* */
	public function load()
	{
		$this->arStatus[] = 'cache load: ' . $this->id_unit. ' (expired in ' . $this->text_sec_to_str( $this->lifetime_sec-$this->diff_sec ) .')';
		return unserialize($this->value);
	}
	/* */
	public function save($value)
	{
		if ( !$this->is_enable ){ return $value; }
		/* $this->id_unit should exist */
		if ( $this->is_expired && !$this->is_exist )
		{
			$this->_add_unit( $this->id_unit, $value );
		}
		elseif ( $this->is_expired && $this->is_exist )
		{
			$this->_update_unit( $this->id_unit, $value );
		}
		return $value;
	}
	/* */
	public function events()
	{
		return $this->arStatus;
	}
	/* */
	public function debug()
	{
		print '<pre class="debug">';
		print_r( $this->arStatus );
		print '</pre>';
	}
	/* */
	private function _add_unit($id_unit, $value)
	{
		if ( $this->oDb->insert( $this->db_tablename, array(
			'id_unit' => $this->id_unit,
			'cdate' => $this->datetime,
			'unit_group' => $this->unit_group,
			'unit_value' => serialize( $value )
		) ) )
		{
			$this->arStatus[] = 'cache save: ' . $this->id_unit. ' (expired in ' . $this->text_sec_to_str( $this->lifetime_sec ) .')';
		}
	}
	/* */
	private function _update_unit($id_unit, $value)
	{
		if ($this->oDb->update( $this->db_tablename, array(
			'cdate' => $this->datetime,
			'unit_value' => serialize( $value )
		), array( 'id_unit' => $this->id_unit ) ) )
		{
			$this->arStatus[] = 'cache save: ' . $this->id_unit. ' (expired in ' . $this->text_sec_to_str( $this->lifetime_sec ) .')';
		}
	}
}
?>