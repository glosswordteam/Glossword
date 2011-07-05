<?php
/**
 * Translation Kit - (http://tkit.info/)
 * © 2002-2010 Dmitry N. Shilnikov <dmitry.shilnikov at gmail dot com>
 * Database version.
 * PHP 5
 *
 * @version $Id: class.tkit.php 134 2009-09-20 08:10:28Z dshilnikov $
 */
class tkit_db extends tkit
{
	public $oDb;
	/**
	 * The list of available languages for the project
	 */
	public function get_lang_list()
	{
		if ( !empty( $this->ar_lang_list ) )
		{
			return $this->ar_lang_list;
		}

		#$this->oDb->select( 'l.isocode3, l.region, l.id_lang, l.lang_name, l.lang_native', false );
		#$this->oDb->select( 'l.*, CONCAT(l.isocode3, "-", l.region) lang_uri', false );
		$this->oDb->select( 'l.*', false );
		$this->oDb->from( array( 'languages l' ) );
		$this->oDb->where( array( 'l.is_active' => '1' ) );
		$this->oDb->order_by( 'l.lang_name' );
		$ar_sql = $this->oDb->get()->result_array();

		$this->ar_lang_list =& $ar_sql;
		return $this->ar_lang_list;
	}
	/* */
	public function get_lang_default( $lang_uri )
	{
		foreach ( $this->ar_lang_list as $ar_v )
		{
			if ( $ar_v['is_default'] == 1 ) { return $ar_v['isocode1']; }
		}
		return $lang_uri;
	}
		
	/* Called from import_tag() */
	public function load_lang_settings( $lang_uri )
	{
		/* Default language settings */
		$a = array(
			'id_lang'=>'876608490',
			'is_active' => 1,
			'is_default' => 1,
			'lang_name'=>'English',
			'lang_native'=>'American',
			'region'=>'US',
			'isocode1'=>'en',
			'isocode3'=>'eng',
			'direction'=>'ltr',
			'thousands_separator'=>',',
			'decimal_separator'=>'.',
			'month_short'=>'Jan Feb Mar Apr May Jul Jul Aug Sep Oct Nov Dec',
			'month_long'=>'January February March April May June July August September October November December',
			'month_decl'=>'January February March April May June July August September October November December',
			'day_of_week'=>'Mon Tue Wed Thu Fri Sat Sun',
			'byte_units'=>'B KB MB GB TB PB EB'
		);
		/* */
		$is_found = 0;
		foreach ( $this->ar_lang_list as $ar_v )
		{
			if ( $ar_v['isocode1'] == $lang_uri ) { $a = $ar_v; $is_found = 1; break; }
		}
		/* */
		if ( !$is_found )
		{
			/* Select default language */
			foreach ( $this->ar_lang_list as $ar_v )
			{
				if ( $ar_v['is_default'] == 1 ) { $a = $ar_v; break; }
			}
		}
		/* */
		$this->ar_ls =& $a;
	}
	/**
	 * Loads phrases by tag.
	 *
	 * @access	public
	 * @uses array_merge_clobber() 
	 */
	public function import_tag( $ar = array(), $lang_uri )
	{
		settype( $ar, 'array' );
		$a = array();
		
		$this->load_lang_settings( $lang_uri );
		
		/* Load phrases usgin corrected Language URI */
		$this->oDb->select( 'tv.tv_value, p.pid_value' );
		$this->oDb->from( array(  'pid p', 'tv tv' ) );
		$this->oDb->where( array( 'p.id_pid = tv.id_pid' => NULL ) );
		$this->oDb->where( array( 'tv.id_lang' => (string) $this->ar_ls['id_lang'] ) );
		$ar_sql = $this->oDb->get()->result_array();
		
		foreach ($ar_sql as $ar_v )
		{
			$a[$ar_v['pid_value']] = $ar_v['tv_value'];
		}
		
		$this->a =& $a;
		
		return true;
	}
}
?>