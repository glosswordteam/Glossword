<?php
/** 
 * Glossword Wiki
 * 
 * Usage:
 *   $oGWiki = new gw_wiki_code( 'input', 'db' );
 *   $oGwiki->proc( $s );
 *   $oGwiki->output();
 */
class gw_wiki_code
{
	
	private $cfg;
	private $in, $out, $ar_out;
	private $ar_base64 = array();
	private $max_lvl_base64 = 3;
		
	/* */
	public function __construct( $ar_cfg )
	{
		$this->cfg =& $ar_cfg;
	}
	/* */
	private function fix__input_to_db() 
	{
		$s = $this->in;
		
		$s = $this->unhtmlspecialamp( $s );
		
		/* convert {%v:path_img%} => {v:path_img} */
		$s = str_replace( array('{%', '%}'), array('{', '}' ), $s );

		$this->out =& $s;
	}
	/* */
	private function fix__db_to_input() 
	{
		/* copy input value */
		$s = $this->in;
		
		$s = preg_replace( '/{([0-9a-zA-Z_\.\:\-\/]+)}/', '{%\\1%}', $s );
		#$s = htmlspecialchars_decode( $s );

		/* link with output value */
		$this->out =& $s;
	}
	
	private function do_protect_tag( $s, $tag_search = 'nowiki', $tag_enclose )
	{
		$ar_start = explode( '<'.$tag_search.'>', $s );
		$ar_txt = array();
		$ar_txt[] = array_shift( $ar_start );
		$nblock = sizeof( $ar_start ) - 1;
		foreach( $ar_start as $blocknum => $block )
		{
			$ar_end = explode( '</'.$tag_search.'>', $block );
			if ( sizeof( $ar_end ) > 1 || $blocknum < $nblock ) 
			{
				$key = sprintf("%u", crc32( $ar_end[0] ) );
				$this->ar_base64[$key] = $ar_end[0];
				#$ar_txt[] = '['.$tag_enclose.']'.base64_encode( $ar_end[0] ).'[/'.$tag_enclose.']';
				$ar_txt[] = '[base64]'.$key.'[/base64]';
				array_shift( $ar_end );
			}
			else
			{
				array_unshift( $ar_end, '<'.$tag_search.'>' );
			}
			$ar_txt[] = implode( '', $ar_end );
		}
		return implode( '', $ar_txt );
	}
	/* Restores base64-encoded text */
	private function do_decode_base64( $s, $level = 0 )
	{
		++$level;
		foreach ( $this->ar_base64 as $k => $v )
		{
			if ( strpos( $s, '[base64]'.$k.'[/base64]' ) !== false )
			{
				$s = str_replace( '[base64]'.$k.'[/base64]', $v, $s );
				unset( $this->ar_base64[$k] );
			}
		}
		if ( !empty( $this->ar_base64 ) )
		{
			/* Parse nested base64-code */
			if ( $level <= $this->max_lvl_base64)
			{
				$s = $this->do_decode_base64( $s, $level );
			}
		}
		return $s;
	}
	
	/* */
	private function fix__db_to_html() 
	{
		$s = $this->in;

		/* Protect <nowiki> */
		$s = $this->do_protect_tag( $s, 'nowiki', 'base64' );
		
		/* Protect program code */
		$s = $this->do_protect_code( $s );
		
		/* Parse quotes ``'', `', """", '''' */
		$s = $this->do_punctuation_quotes( $s );

		$s = htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' );
		
		/* bold, italic, underline, strike */
		$s = $this->do_font_styles( $s );

		$s = $this->do_punctuation( $s );
		
		$s = trim( $s );
		$s = str_replace( "\n\n", '<br /><br />', $s );
		#$s = nl2br( $s );

		/* Restore all base64_encode`d sections */
		$s = $this->do_decode_base64( $s );
		
		#prn_r( $s, __LINE__ );
		
		$this->out =& $s;
	}
	
	
	
	
	/* */
	public function do_font_styles( $s )
	{
		$output = '';
		$ar_lines = explode( "\n", $s );
		foreach ( $ar_lines as $line )
		{
			$output .= $this->_do_font_styles_helper( $line ) . "\n";
		}
		return $output;
	}
	/* */
	private function _do_font_styles_helper( $s )
	{
		/* */
		$ar = preg_split( "/(\*|_|~~|\+\+)/", $s, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( sizeof( $ar ) == 1 )
		{
			return $s;
		}
		
		$i = $cnt_bold = $cnt_italics = $cnt_strikeout = $cnt_kbd = 0;

		foreach ( $ar as $r )
		{
			if ( ( $i % 2 ) == 1 )
			{
				if (strpos($ar[$i], '_') !== false){ $cnt_italics++; }
				else if (strpos($ar[$i], '*') !== false){ $cnt_bold++; }
				else if (strpos($ar[$i], '~~') !== false){ $cnt_strikeout++; }
				else if (strpos($ar[$i], '++') !== false){ $cnt_kbd++; }
			}
			++$i;
		}
		/* */
		$output = '';

		$i = $is_bold = $is_italics = $is_strikeout = $is_kbd = 0;

		foreach ( $ar as $r )
		{
			if ( ($i % 2) == 0 )
			{
				$output .= $r;
			}
			else
			{
				if ( ($r == '*') && ($cnt_bold % 2) == 0 )
				{
					if ( $is_bold ) { $output .= '</strong>'; $is_b = 0; } else { $output .= '<strong>'; $is_bold = 1; }
				}
				else if ( ($r == '_') && ($cnt_italics % 2) == 0 )
				{
					if ( $is_italics ) { $output .= '</em>'; $is_i = 0; } else { $output .= '<em>'; $is_italics = 1; }
				}
				else if ( ($r == '~~') && ($cnt_strikeout % 2) == 0 )
				{
					if ( $is_strikeout ) { $output .= '</del>'; $is_s = 0; } else { $output .= '<del>'; $is_strikeout = 1; }
				}
				else if ( ($r == '++') && ($cnt_kbd % 2) == 0 )
				{
					if ( $is_kbd ) { $output .= '</kbd>'; $is_kbd = 0; } else { $output .= '<kbd>'; $is_kbd = 1; }
				}
				else
				{
					$output .= $r;
				}
			}
			++$i;
		}
		return $output;
	}
	/* */
	public function do_protect_code( $s )
	{
		/* */
		$s = $this->do_inline_code( $s );
		
		/* */
		$ar_start = explode( '{{{', $s );
		$ar_txt = array();
		$ar_txt[] = array_shift( $ar_start );
		$nblock = sizeof( $ar_start ) - 1;
		foreach ( $ar_start as $blocknum => $block )
		{
			$ar_end = explode( '}}}', $block );
			if ( sizeof( $ar_end ) > 1 || $blocknum < $nblock ) 
			{
				$key = sprintf( "%u", crc32( $ar_end[0] ) );
				$this->ar_base64[$key] = $this->do_program_code_format( $ar_end[0] );
				$ar_txt[] = '[base64]'.$key.'[/base64]';
				array_shift( $ar_end );
			}
			else
			{
				array_unshift( $ar_end, '{{{' );
			}
			$ar_txt[] = implode( '', $ar_end );
		}
		$s = implode( '', $ar_txt );

		/* Protect <nowiki> */
		$s = $this->do_protect_tag( $s, 'nowiki', 'base64' );
		
		#prn_r( $s, 'do_protect_code' );
		
		return $s;
	}
	/* */
	public function do_inline_code( $s )
	{
		$output = '';
		$ar_lines = explode( "\n", $s );
		foreach ( $ar_lines as $line )
		{
			$output .= $this->_do_inline_code_helper( $line ) . "\n";
		}
		return $output;
	}
	private function _do_inline_code_helper( $s )
	{
		/* */
		$ar = preg_split( "/(``|`)/", $s, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( sizeof( $ar ) == 1 )
		{
			return $s;
		}
		
		$i = $cnt_code = $cnt_code2 = 0;

		foreach ( $ar as $r )
		{
			if ( ( $i % 2 ) == 1 )
			{
				if (strpos($ar[$i], '``') !== false){ $cnt_code2++; }
				elseif (strpos($ar[$i], '`') !== false){ $cnt_code++; }
			}
			++$i;
		}
		/* */
		$output = '';

		$i = $is_code = $is_code2 = 0;

		foreach ( $ar as $r )
		{
			if ( ($i % 2) == 0 )
			{
				if ( $is_code || $is_code2 )
				{
					$r = htmlspecialchars( $r );
					#prn_r( ' $is_code='. $is_code. ' ' . $r, '_do_inline_code_helper'  );
				}
				$output .= $r;
			}
			else
			{
				if ( ($r == '`') && ($cnt_code % 2) == 0 )
				{
					if ( $is_code ) { $output .= '`</tt></nowiki>'; $is_code = 0; } else { $output .= '<nowiki><tt>`'; $is_code = 1; }
				}
				else if ( ($r == '``') && ($cnt_code2 % 2) == 0 )
				{
					if ( $is_code2 ) { $output .= '``</tt></nowiki>'; $is_code2 = 0; } else { $output .= '<nowiki><tt>``'; $is_code2 = 1; }
				}
				else
				{
					$output .= $r;
				}
			}
			++$i;
		}
		return $output;
	}
	/* */
	public function do_program_code_format( $s )
	{
		$s = htmlspecialchars( $s );
		$s = preg_replace("/(\r\n|\n|\r)/", "\n", $s);
		$s = str_replace("\t", '    ', $s);
		$s = trim( $s );
		
		if ( !$s ) { return $s; }
		
		/* Explode program code per line */
		$ar_lines = explode("\n", $s);
		
		/**
		 * Select code format.
		 * Variants to parse: {{{ php program_code }}}, {{{ php \n program_code }}}
		 */
		$ar_temp = explode( " ", $ar_lines[0] );
		$wiki_code_mode = strtolower( $ar_temp[0] );
 		switch ( $wiki_code_mode )
		{
			case 'php':
				/* Remove `php` from input */
				unset( $ar_lines[0], $ar_temp[0] );
				/* When `{{{ php program_code` */ 
				if ( sizeof( $ar_temp ) > 1 ) 
				{
					/* Restore the first line */
					array_unshift( $ar_lines, implode(" ", $ar_temp ) );
				}
				array_unshift( $ar_lines, '&lt;?php' );
				$ar_lines[] = '?&gt;';
			break;
			case 'css':
			case 'js':
			case 'perl':
			case 'python':
				unset( $ar_lines[0] );
			break;
			default:
				 $wiki_code_mode = '';
			break;
		}
		
		/* Add classname */
		$s = '<ol class="code'.($wiki_code_mode ? ' '.$wiki_code_mode : '').'">';
		foreach ($ar_lines as $v)
		{
			$s .= '<li>' . $v . '</li> ';
		}
		$s .= '</ol>';
		
		return $s;
	}
	
	
	
	
	/* Tune punctuation rules */
	public function do_punctuation( $s )
	{
		/* em-dash */
		$s = str_replace(' --', "\xC2\xA0".'—', $s);
		$s = str_replace(' —', "\xC2\xA0".'—', $s); /* em-dash */
		$s = str_replace(' –', "\xC2\xA0".'—', $s); /* en-dash */
		$s = str_replace('...', '…', $s);
		$s = str_replace(' г.', "\xC2\xA0".'г.', $s);
		/* */
		$s = str_replace( array('(C)', '(c)'), '©', $s );
		$s = str_replace( array('(R)', '(r)'), '®', $s );
		$s = str_replace( array('(TM)', '(tm)'), '™', $s );
		/* 36,6^oC, 150^oC */
		$s = preg_replace('/(\d+)\^o/', '\\1°', $s);
		/* 2002 -- 2010 -> 2002–2010 (en dash) */
		$s = preg_replace('/(\d+)'."\xC2\xA0".'— (\d+)/', '\\1–\\2', $s);
		/* 29^кв.м. */
		$s = preg_replace('/(\d+)\^кв.м/', '\\1'."\xC2\xA0".'м<sup>2</sup>', $s);
		return $s;
	}
	public function do_punctuation_quotes( $s )
	{
		/* the '80s */
		$s = preg_replace("/'(\d{2}s)/", '’\\1', $s);
		/* ""1 ''2'' 3"" => «1 „2“ 3» */
		$s = preg_replace('/""([^"].*?)""/s', '«\\1»', $s);
		$s = preg_replace("/''([^'\"].*?)''/s", '„\\1“', $s);
		/* ``1'' => “1”,  `2' => ‘2’ */
		$s = preg_replace("/``([^`'].*?)\'\'/s", '“\\1”', $s);
		$s = preg_replace("/`([^`'].*?)\'/s", '‘\\1’', $s);
		
		return $s;
	}
	
	
	/* */
	public function proc( $s )
	{
		$this->in = $this->out = '';
		
		$this->method = 'fix__'.$this->cfg['in'].'_to_'.$this->cfg['out'];
		
		if ( is_string( $s ) )
		{
			$s = trim( $s );
			$this->in =& $s;
			if ( method_exists( $this, $this->method ) )
			{
				$this->{$this->method}();
			}
		}
		return $this->out;
	}
	/* */
	public function output( )
	{
		if ( !empty( $this->ar_out ) )
		{
			return $this->ar_out;
		}
		return $this->out;
	}
	/* */
	public function htmlspecialamp( $s )
	{
		if (!is_string($s)){ return $s; }
		$s = str_replace('&', '&amp;', $s);
		$s = str_replace('"', '&quot;', $s);
		$s = str_replace('\'', '&#039;', $s);
		$s = preg_replace('/&amp;#([0-9]+);/', '&#\\1;', $s);
		return $s;
	}
	/* */
	public function unhtmlspecialamp( $s )
	{
		if (!is_string($s)){ return $s; }
		$s = str_replace('&amp;', '&', $s);
		$s = str_replace('&quot;', '"', $s);
		$s = str_replace('&AMP;', '&', $s);
		$s = str_replace('&QUOT;', '"', $s);
		$s = str_replace('&#039;', '\'', $s);
		return $s;
	}
	/**
	 * Get string length, multibyte.
	 *
	 * @param   string  $t Any string content
	 * @return  int     String length
	 */
	public static function mb_strlen($t, $encoding = 'UTF-8')
	{
		/* --enable-mbstring */
		if ( function_exists( 'mb_strlen' ) )
		{
			return mb_strlen( $t, $encoding );
		}
		else
		{
			return strlen( utf8_decode( $t ) );
		}
	}
}
?>




