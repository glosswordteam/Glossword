<?php
/**
 * PHP-class to construct links and HTML-tags.
 * 
 *  © 1999-2004 Dmitry N. Shilnikov
 *  © 2009-2010 Glossword.biz Team
 * 
 * @version $Id$
 */
if (!defined('IS_CLASS_HTMLTAGS')) { define('IS_CLASS_HTMLTAGS', 1);
class site_html_tags
{
	public $ar_synonyms1 = array();
	public $ar_synonyms2 = array();
	public $ar_stopwords = array('member.php', 'admin.php');
	/* &arg[variable]=value, `arg` is $v_get */
	public $v_get;
	
	private $sep1 = "\x01\x01";
	private $sep2 = "\x02\x02";

	public function __construct($ar)
	{
		/* Default confifuration */
		$this->cfg = array(
			'is_htmlspecialchars' => 0,
			'is_sef' => 0,
			'sef_rule' => array(),
			'server_dir' => '/',
			'sef_filename' => 'index',
			'sef_output' => 'xhtml',
			'file_index' => 'index.php',
			'sef_il' => 'eng',
			'sef_append' => ''
		);
		/* Rewrite default settings */
		foreach ($ar as $k => $v)
		{
			$this->cfg[$k] = $v;
		}
	}
	/* */
	public function set_tag($tag, $v, $value)
	{
		if ($tag != '')
		{
			$this->tags[$tag][$v] = $value;
		}
	}
	/* */
	public function unset_tag($tag, $v = '')
	{
		if (isset($this->tags[$tag][$v]))
		{
			unset($this->tags[$tag][$v]);
		}
		elseif (isset($this->tags[$tag]))
		{
			$this->tags[$tag] = array();
		}
	}
	/* 02 oct 2007: new <a href=""> builder */
	/* 09 feb 2009: Auto-clear for attributes */
	public function a_href( $ar_href = array(), $ar_attr = array(), $text = false )
	{
		if (!is_array($ar_href)){ $ar_href = array($ar_href); }
		$filename = $ar_href[0];
		unset($ar_href[0]);
		$this->set_tag('a', 'href', $filename);
		if (!empty($ar_href))
		{
			$this->set_tag('a', 'href', $this->url_normalize( $filename.'?'.$this->http_build_query_href($ar_href)) );
		}
		/* 26 Jan 2008: print url as text automatically */
		if ( $text === false )
		{
			$text = $this->tags['a']['href'];
		}
		foreach ($ar_attr as $k => $v )
		{
			/* Fix target="_blank" */
			if ($k == 'target')
			{
				unset($ar_attr[$k]);
				$ar_attr['onclick'] ='window.open(this);return false';
			}
		}
		$this->tags['a'] = array_merge( $this->tags['a'], $ar_attr );
		$str = '<a '.$this->http_build_query( $this->tags['a'] ).'>'.$text.'</a>';
		$this->unset_tag( 'a' );
		return $this->_render( $str );
	}
	/* Alias for http_build_query() */
	public function http_build_query_href_arg( $ar )
	{
		return $this->http_build_query( $ar, ',', '.', '' );
	}
	/* Alias for http_build_query() */
	public function http_build_query_href( $ar )
	{
		return $this->http_build_query( $ar, '&', '=', '' );
	}
	/* replacement for http_build_query */
	public function http_build_query($ar = array(), $pairs = ' ', $values_sep = '=', $enclose = '"')
	{
		$str = '';
		if ( is_array( $ar ) )
		{
			/* Do sort attributes in a good manner. */
			ksort( $ar );
			for ( reset($ar); list($k, $v) = each($ar); )
			{
				$str .= (strval($v) == '') ? '' : ($pairs.$k.$values_sep.$enclose.$v.$enclose);
			}
			$str = ltrim( $str, $pairs );
		}
		return $str;
	}
	/* replacement for urlencode */
	public static function urlencode( $s )
	{
#		print 	 urlencode('%');
		/* Encode special characters first */
		$s = str_replace( array( ',', '/', '+' ), array( '%2C', '%2F', '%2B' ), $s );
		$s = urlencode( $s );
		/* Restore separators for #area */
		$s = str_replace( array( '%01%01', '%02%02' ), array( ',', '.' ), $s );
		return $s;
	}
	/* replacement for urlencode */
	public static function urldecode( $s )
	{
		$s = urldecode( $s );
		#$s = str_replace( array( '%2C', '%2F', '%2B' ), array( ',', '/', '+' ), $s );
		return $s;
	}
	/**
	 * Normalizes URL.
	 * 25 May 2008: debug mode added.
	 */
	public function url_normalize( $url, $is_debug = 0 )
	{
		if ( $is_debug )
		{
			return $url;
		}
#prn_r( $url );
		/* Convert &amp for sure */
		$url = str_replace( '&amp;', '&', $url );
#		$url = str_replace('&', '&amp;', $url);
		#$url .= $this->cfg['sef_append'];
		/* $ar_path[1] is url parameters */
		preg_match( "/\?(.*?)$/", $url, $ar_path );
		/* */
		if ( isset($ar_path[1]) )
		{
			$filepath = str_replace( $ar_path[1], '', $url );
			parse_str( $ar_path[1], $ar_url );

			/* */
			if ( !empty( $this->cfg['sef_append'] ) )
			{
				$ar_url = array_merge( $ar_url, $this->cfg['sef_append'] );
			}

			/* Use stopwords (do not SEF, but append URL) */
			foreach ($this->ar_stopwords as $stopword)
			{
				if ( strpos( $url, $stopword ) !== false )
				{
					#$url = str_replace( '&', '&amp;', $url );
					#prn_r( $ar_url );
					return $this->cfg['server_dir'].'/'.$url;
				}
			}
			/* */
			foreach ( $ar_url as $k1 => $v1 )
			{
				if ( is_array( $v1 ) )
				{
					foreach ( $v1 as $k2 => $v2 )
					{
						if ( is_array( $v2 ) )
						{
							$v2 = implode( ',', $v2 );
						}
						$v3[] = $k1.'['.$k2.']='.$v2;
					}
					$ar_url_fmt[] = implode( '&amp;', $v3 );
				}
				else
				{
					if ( preg_match( '/^\#(.*?)$/', $k1, $ar_matches) )
					{
						unset( $ar_url[$k1] );
						$k1 = $this->cfg['v_get'].'['.$ar_matches[1].']';
						$ar_url[$this->cfg['v_get']][$ar_matches[1]] = $v1;
					}
					/* A custom urlencode */
					$ar_url_fmt[] = $k1 .'='. $this->urlencode( $v1 );
				}
			}
			sort( $ar_url_fmt );
			$url = $this->cfg['server_dir'].'/'.$this->cfg['sef_fileindex'].implode( '&amp;', $ar_url_fmt );

			#prn_r(  $ar_url_fmt );
			#prn_r(  $url );
			
			if ( $this->cfg['is_sef'] && isset( $this->cfg['sef_rule'] ) && !empty( $this->cfg['sef_rule'] ) )
			{
				foreach ( $this->cfg['sef_rule'] as $expr => $rule )
				{
					if ( isset( $ar_url[$this->cfg['v_get']] )
						&& ( !$expr || strpos( $ar_path[1], $expr ) )
					)
					{
						$url = $this->url_do_sef( $rule, $ar_url[$this->cfg['v_get']] );
						#$url = $this->url_do_sef( $rule, $ar_url );
					}
				}
				$url = ltrim( $url, '/' );
#prn_r( $this->cfg['server_dir'].$this->cfg['sef_fileindex'].'/'.$url, __FILE__.' '.__LINE__ );
				return $this->cfg['server_dir'].$this->cfg['sef_fileindex'].'/'.$url;
			}
		}
		return $url;
	}
	/**
	 * Converts an array to a short URL.
	 * array('id' => 'domain.tld', 'sef_index' => 'index', 'sef_output' => 'xhtml', 'target' => 'item') => /i/domain.tld/index.xhtml
	 *
	 * @param   array   Rule how to rewrite URL
	 * @param   string  URL written as array
	 * @return  string  A short URL
	 */
	public function url_do_sef( $rule, $ar_url )
	{
		if ( !isset($ar_url['sef_filename']) )
		{
			$ar_url['sef_filename'] = $this->cfg['sef_filename'];
		}
		if ( !isset($ar_url['sef_output']) )
		{
			$ar_url['sef_output'] = $this->cfg['sef_output'];
		}
		if ( !isset($ar_url['il']) )
		{
			$ar_url['il'] = $this->cfg['sef_il'];
		}

		$rule = str_replace(array('/', '.'), array('/$', '.$'), $rule);
		/* @todo: Optimize */
		for (reset($ar_url); list($k, $v) = each($ar_url);)
		{
			$rule = preg_replace('/\$'.$k.'\b/', $this->urlencode( $v ), $rule);
		}
		/* remove undefinied variables from uri like /$area/ */
		$rule = preg_replace('#\$\w+#', ' ', $rule);
		$url = '';
		$rule = str_replace(array(', ', '. '), ',', $rule);
		/* First part */
		$ar_pt1 = explode('/', $rule);
		/* Second part */
		$ar_pt2 = explode(',', end($ar_pt1));
		sort( $ar_pt2 );
		$ar_pt1[sizeof($ar_pt1)-1] = '';

		/* 29 jan 2009: Enable synonyms */
		foreach ($ar_pt1 as $k => $value)
		{
			foreach ($this->ar_synonyms1 as $from => $to)
			{
				if ($value == $from)
				{
					$ar_pt1[$k] = $to;
				}
			}
			$ar_pt1[$k] = str_replace(array_keys($this->ar_synonyms2), array_values($this->ar_synonyms2), $ar_pt1[$k]);
		}

		$url .= implode(' ', $ar_pt1);
		$url = rtrim($url);
		$url = preg_replace('# {2,}#', ' ', $url);
		$url = str_replace(' ', '/', $url);

		$rule2 = implode(' ', $ar_pt2);
		$rule2 = rtrim($rule2);
		$rule2 = str_replace(' ', ',', $rule2);
		$url .= ($rule2) ? '/'.$rule2 : '';

#		prn_r( $url, 'url_do_sef' );
		
		return $url;
	}
	/**
	 * Converts a short URL into an array.
	 * /i/domain.tld/index.xhtml => array('id' => 'domain.tld', 'sef_index' => 'index', 'sef_output' => 'xhtml', 'target' => 'item')
	 *
	 * @param   string  A short URL
	 * @return  array   URL written as array
	 */
	public function url_undo_sef( $url )
	{
		$ar_url = array();
		$url = str_replace( $this->cfg['server_dir'], '', $url );
		$url = str_replace( $this->cfg['sef_fileindex'], '', $url );
#		$url = preg_replace('#'.$this->sys['sef_append'].'$#', '', $url);
		$url = str_replace(array('/'), "\x01", $url);

		/* Parse urls with question mark '?' */
		if ( strpos( $url, '?' ) !== false)
		{
			list( $url_no_q, $url_q ) = explode( '?', $url );
#			parse_str( $url_q, $ar_url );
			$url = $url_no_q;
		}
		/* */
		if (!$url || !$this->cfg['sef_rule']) { return array(); }
		foreach ($this->cfg['sef_rule'] as $expr => $rule)
		{
			$rule = str_replace( array('/', ','), "\x01", $rule);
			$ar_rule = explode("\x01", $rule);
			$ar_p = explode("\x01", $url);
			array_shift( $ar_p );
		
			/* Enable synonyms */
			foreach ($ar_p as $k => $value)
			{
				$ar_p[$k] = $this->urldecode( $value );
				foreach ($this->ar_synonyms1 as $to => $from)
				{
					if ($value == $from)
					{
						$ar_p[$k] = $to;
					}
				}
				#prn_r( array_keys($this->ar_synonyms2) );
				$ar_p[$k] = str_replace(array_values($this->ar_synonyms2), array_keys($this->ar_synonyms2), $ar_p[$k]);
			}
			if ($ar_rule[0] == $ar_p[0])
			{
				unset($ar_rule[0], $ar_p[0]);
				/* read `target` first */
				$ar_expr = explode('=', $expr);
				if (isset($ar_expr[1]))
				{
					$ar_url['target'] = $ar_expr[1];
				}
				/* */
				foreach($ar_p as $k => $v)
				{
# prn_r( $url );
					$ar_url[$ar_rule[$k]] = $v;
				}
				/* Last parameters */
				$ar_p_last = explode('.', end($ar_p));
				$ar_rule_last = explode('.', end($ar_rule));
				foreach ($ar_p_last as $k => $v)
				{
					if (isset($ar_rule_last[$k]))
					{
						$ar_url[$ar_rule_last[$k]] = $v;
					}
				}
				unset($ar_url[end($ar_rule)]);
			}
			else if ( !$expr )
			{
				/* Default rule */
				array_shift( $ar_rule );

				foreach( $ar_p as $k => $v )
				{
					if ( isset( $ar_rule[$k] ) )
					{
						$ar_url[$ar_rule[$k]] = $v;
					}
				}
				/* Last parameters */
				$ar_p_last = explode( '.', end($ar_p) );
				$ar_rule_last = explode( '.', end($ar_rule) );

				foreach ($ar_p_last as $k => $v)
				{
					if (isset($ar_rule_last[$k]))
					{
						$ar_url[$ar_rule_last[$k]] = $v;
					}
				}
				unset($ar_url[end($ar_rule)]);
			}
		}
		#prn_r( $ar_url, __LINE__ );
		return $ar_url;
	}
	/* */
	private function _render($s)
	{
		if ($this->cfg['is_htmlspecialchars'])
		{
			$s = htmlspecialchars($s);
		}
		return $s;
	}
	/* */
	public function oHref()
	{
		return new site_html_tags_href;
	}
}

/* */
class site_html_tags_href
{
	private $ar = array();
	private $sep1 = "\x01\x01";
	private $sep2 = "\x02\x02";
	public function set( $k, $v = '' )
	{
		if ( is_array($k) )
		{
			foreach ( $k as $pk => $pv)
			{
				$this->ar[$pk] = $pv;
			}
		}
		if ( $v != '' )
		{
			$this->ar[$k] = $v;
		}
		if ( $v == '' && !is_array($k) && isset( $this->ar[$k] ) )
		{
			unset( $this->ar[$k] ); 
		}
	}
	public function get( $ar = '' )
	{
		if ( is_array( $ar ) )
		{
			$this->ar =& $ar;
		}
		return $this->http_build_query( $this->ar, $this->sep1, $this->sep2, '' );
	}
	/* replacement for http_build_query */
	public static function http_build_query( $ar = array(), $pairs = ' ', $values_sep = '=', $enclose = '"' )
	{
		$str = '';
		if ( is_array( $ar ) )
		{
			/* Do sort attributes in a good manner. */
			ksort( $ar );
			for ( reset($ar); list($k, $v) = each($ar); )
			{
				$str .= (strval($v) == '') ? '' : ($pairs.$k.$values_sep.$enclose.$v.$enclose);
			}
			$str = ltrim( $str, $pairs );
		}
		return $str;
	}
}

}
?>