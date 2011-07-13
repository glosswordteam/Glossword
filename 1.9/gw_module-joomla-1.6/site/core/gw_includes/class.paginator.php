<?php
/**
 * $Id$
 * Creates page navigation: Prev 1 .. 4 5 6 .. 10 Next
 * 27 dec 2009: added oTkit for page numbers 
 * Usage:
 *
 * $ar_cfg = array(
 *     'url' => '', // Base URL
 *     'page_current' => '',  // The current page number
 *     'items_total' => '',   // The total number of items
 *     'items_per_page' => '', // The number of items per page
 *     'links_total' => '',  // The number of links in a navigation toolbar
 *     'links_tag' => '', // HTML-tag for links in a navigation toolbar
 *     'links_more' => '', // String placed between the first and the last page numbers
 *     'links_separator' => '', // String used to separate page numbers
 *     'current_tag' => '', // HTML-tag for the link to the current page in a navigation toolbar
 *     'phrase_next' => '',  // "Next" phrase
 *     'phrase_prev' => '',  // "Previous" phrase
 * );
 * $oPaginatior = new class_paginator($ar_cfg);
 * $oPaginatior->get();
 */
class site_class_paginator
{
	public $oTkit = false;
	private $cfg;
	/* */
	function __construct( $ar = array() )
	{
		/* Default confifuration */
		$this->cfg = array(
			'url' => 'page.php?page={#}',
			'page_current' => 10,
			'items_total' => 100,
			'items_per_page' => 5,
			'links_total' => 4,
			'links_tag' => '',
			'links_more' => '..',
			'links_separator' => '|',
			'current_tag' => 'strong',
			'current_classname' => 'on',
			'phrase_next' => 'Next',
			'phrase_prev' => 'Prev',
			'is_use_tkit' => 0,
		);
		/* Rewrite default settings */
		foreach ( $ar as $k => $v )
		{
			$this->cfg[$k] = $v;
		}
		/* Count the number of pages */
		if ( $this->cfg['items_per_page'] <= 0 )
		{
			$this->cfg['items_per_page'] = $this->cfg['items_total'];
		}
		$this->cfg['pages_total'] = ceil( $this->cfg['items_total'] / $this->cfg['items_per_page'] );
		if ( !isset( $this->cfg['pages_total_text'] ) )
		{
			$this->cfg['pages_total_text'] = $this->cfg['pages_total'];
		}
		/* Restore {#} for paginator */
		$this->cfg['url'] = str_replace( '%7B%23%7D', '{#}', $this->cfg['url'] );
	}
	/* */
	public function get()
	{
		/* No need for navigation */
		if ( $this->cfg['pages_total'] == 1 || $this->cfg['pages_total'] == 0 )
		{
			return '&#160;';
		}
		/* Prepage an array with links to pages */
		$ar_pages = array();
		/* The number of links to pages displayed before and after the current page. 1 2 (3) 1 2 */
		$int_max = $this->cfg['links_total'];
		/* Strart some counters */
		$cnt_max = $this->cfg['page_current'] + $int_max;
		$cnt_min = $this->cfg['page_current'] - $int_max;
		/* Fix the maximum number of pages */
		if ( $cnt_max > $this->cfg['pages_total'] )
		{
			$cnt_max = $this->cfg['pages_total'];
		}

		/* The first page */
		if ( $cnt_min > 1 )
		{
			$ar_pages[] = '<a href="'.str_replace( '{#}', 1, $this->cfg['url'] ).'">1</a>';
			/* Do not show `..` for `1 | .. | 2 | 3` */
			if ( ( $this->cfg['page_current'] - $int_max) != 0 )
			{
				$ar_pages[] = $this->cfg['links_more'];
			}
		}
		/* Set HTML-tag for the current page */
		$current_tag_open = $current_tag_close = '';
		if ( $this->cfg['current_tag'] )
		{
			$current_tag_open = '<'.$this->cfg['current_tag'].'>';
			$current_tag_close = '</'.$this->cfg['current_tag'].'>';
		}
		/* For each page number */
		for ($i = 1; $i <= $this->cfg['pages_total']; $i++)
		{
			if ( ( $i >= $cnt_min && $i <= $cnt_max ) )
			{
				$str_i = $i;
				if ( $this->oTkit !== false && $i > 999 )
				{
					$str_i = $this->oTkit->number_format( $i );
				}
				/* The current page */
				if ( $i == $this->cfg['page_current'] )
				{
					$classname = ( $this->cfg['current_classname'] ? ' class="'.$this->cfg['current_classname'].'"' : '' );
					$ar_pages[] = '<a href="'.str_replace( '{#}', $i, $this->cfg['url'] ).'"'.$classname.'>'.$current_tag_open.$str_i.$current_tag_close.'</a>';
				}
				else
				{
					$ar_pages[] = '<a href="'.str_replace( '{#}', $i, $this->cfg['url'] ).'">'.$str_i.'</a>';
				}
			}
		}
		/* The last page */
		if ( $cnt_max > 1 && ( $this->cfg['page_current'] + $int_max ) < $this->cfg['pages_total'] )
		{
			/* Do not show `..` for `25 | 26 | .. | 27` */
			if ( ( $this->cfg['page_current'] + $int_max + 1) < $this->cfg['pages_total'] )
			{
				$ar_pages[] = $this->cfg['links_more'];
			}
			$str_pages_total = $this->cfg['pages_total'];
			if ( $this->oTkit !== false && $this->cfg['pages_total'] > 999 )
			{
				$str_pages_total = $this->oTkit->number_format( $str_pages_total );
			}
			$ar_pages[] = '<a href="'.str_replace( '{#}', $this->cfg['pages_total'], $this->cfg['url'] ).'">'.$str_pages_total.'</a>';
		}
		/* Links to Next/Prev pages */
		if ( $this->cfg['page_current'] > 1 )
		{
			$ar_pages[] = '<a href="'.str_replace('{#}', ( $this->cfg['page_current'] - 1 ), $this->cfg['url'] ).'">'.$this->cfg['phrase_prev'].'</a>';
		}
		else
		{
			$ar_pages[] = '<span class="a">'.$this->cfg['phrase_prev'].'</span>';
		}
		/* Links to Next/Prev pages */
		if ( $this->cfg['page_current'] < $this->cfg['pages_total'] )
		{
			$ar_pages[] = '<a href="'.str_replace( '{#}', ( $this->cfg['page_current'] + 1 ), $this->cfg['url'] ).'">'.$this->cfg['phrase_next'].'</a>';
		}
		else
		{
			$ar_pages[] = '<span class="a">'.$this->cfg['phrase_next'].'</span>';
		}
		/* */
		return implode( $this->cfg['links_separator'], $ar_pages );
	}
}
?>