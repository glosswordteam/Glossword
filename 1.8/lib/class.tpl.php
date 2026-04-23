<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */

if (!defined('IN_GW')) {
	die('<!-- Not in App -->');
}
/* -------------------------------------------------------- */
/**
 * Generic HTML-template class
 * © 2002-2006 Dmitry N. Shilnikov
 * Requires:
 *  - class.ua.php
 *
 * This class originally based on transformation of pre-compiled code,
 * © 2001 Peter Vrabel <kybu@users.sourceforge.net>
 *
 * In general, any class with pre-compiled code works faster
 * than any class based on code replacements.
 * Examples of HTML-templates:
 *   based on pre-compiled code - Smarty, DTE.
 *   based on code replacements - Fast Templates, the most of internal engines
 *                                of a forum software, like phpBB 1.x
 * TODO:
 *  - optimize reading/writing caсhe files
 *  - clean compiled files which are not in use
 */
/* -------------------------------------------------------- */

if (!defined('IS_CLASS_TPL'))
{
	define('IS_CLASS_TPL', 1);

class gwv_template
{
	/* all variables are internal */
	public $path_source = 'tpl';
	public $path_cache = 'cache/tpl';
	public $pairsC = array();
	public $pairsV = array();
	public $pairsN = array(); /* virtual namespaces */
	public $namespace_default = 'GW';
	public $tag_start = '';
	public $tag_end = '';
	public $tag_noncached = 'non';
	public $is_tpl_show_names = 0;
	public $o_encoding = 'utf-8';
	public $ua_type = 'is_ua_client';
	/* default caching rules */
	public $is_cache_write = 0;
	public $is_cache_parse = 0;
	public $is_in_cache = 1;
	public $is_cache_keypresent = 0;
	/* default blocks */
	public $arBlockV = array();
	public $arBlockC = array();
	public $arBlockI = array();
	public $arChilds = array();
	public $arNamespaces = array();
	public $curLevel = 0;
	public $arBlockPos = array();
	public $varsRun = array();
	/* */
	public $ua_number = false;

// --------------------------------------------------------
// Autostart
// --------------------------------------------------------
	public function __construct()
	{
		$this->namespace_default = 'GW';
		$this->oCmd = new gwv_template_cmd();
	}
// --------------------------------------------------------
// Supply functions
// --------------------------------------------------------
	/* */
	public function get_info_files()
	{
		$ar = array();
		foreach ($this->pairsC as $k => $v)
		{
			$ar[crc32($v['filename'])] = $v['filename'];
		}
		return $ar;
	}
	/**
	 * @access  public
	 */
	public function set_path_src($dir)
	{
		$this->path_source = $dir;
	}
	/**
	 * @access  public
	 */
	public function set_path_cache($dir)
	{
		$this->path_cache = $dir;
	}
	/**
	 * External load file function
	 * @global $objFunc
	 */
	public function _file_load($filename)
	{
		global $oFunc;
		return $oFunc->file_get_contents($filename);
	}
	/**
	 * External save file function
	 * @global $objFunc
	 */
	public function _file_save($filename, $str, $mode = "w")
	{
		global $oFunc;
		return $oFunc->file_put_contents($filename, $str, $mode);
	}
	/**
	 *
	 */
	public function _parse_var(&$n)
	{
		/* normalize template names using current namespace */
		$arParts = explode("::", $n);
		$vkey = sprintf("%u", crc32($n));
		switch(sizeof($arParts))
		{
			case 1:
			{
				/* no namespace, assign default */
				$n = $this->namespace_default . '::' . $vkey;
			}
			break;
			case 2:
			{
				/* assign namespace, exclude non-cached tags */
				$n = (strtolower($arParts[0]) == $this->tag_noncached)
					? $this->tag_noncached.'::'.$this->namespace_default.'::'.$arParts[1]
					: $arParts[0] . '::' . $arParts[1];
			}
			break;
		}
	}
// --------------------------------------------------------
	/* $ar - the list of files */
	public function define($ar = array())
	{
		foreach ((is_array($ar) ? $ar : array()) as $tplName => $filename)
		{
			$tplName = sprintf("%u", crc32($filename));
			if (isset($this->pairsC[$tplName]))
			{
				/* Do not load file second time */
				continue;
			}
			$this->pairsC[$tplName] = array(
				'filename' => $filename,
				'filedesc' => $this->_file_load('./' . $this->path_source . '/' . $filename)
			);
			$arBlockI = array();
			eval($this->_compile($tplName));
			if (!isset($arBlockI)) { $arBlockI = array(); }
			$this->arBlockI =& array_merge($this->arBlockI, $arBlockI);
		}
	}
	/* */
	public function assign($ar = array())
	{
		$str = '';
		foreach ($ar as $n => $v)
		{
			/* $v_parsed = {namespace::template_name} */
			$this->_parse_var($n);
			/* Go per browser type */
			if (preg_match("/\(ua=([a-z])+\)/", $n))
			{
				$ua_tpl = preg_replace("/(.*)\[ua=([a-z]?)([^\"']*)\\2]/sU", '\\3', $n);
				if ($ua_tpl == $this->ua_type)
				{
					$n = str_replace('[ua='.$this->ua_type.']', '', $n);
				}
			}
			/* Put string value into array of variables */
			if (!is_array($v))
			{
				$this->pairsV[$n] = strval($v);
			}
			$str = $n;
		}
		return $str;
	}
	/* */
	public function _compile($tplName)
	{
		/* call commands class file */
		$this->oCmd->_reset();
		$tmp = array();
		$tmp['filename_c'] = '';
		$tmp['str_i'] = '';
		/* if current template exists in array (filename) */
		if (isset($this->pairsC[$tplName]) && isset($this->pairsC[$tplName]['filedesc']))
		{
			$arRpl = array();
			/* Source template content which will be replaced */
			$tmp['tpl_content'] = $this->pairsC[$tplName]['filedesc'];
			/* Full path to cache files */
			$tmp['filename_c'] = './'.$this->path_cache.'/'.$this->pairsC[$tplName]['filename']. '.php';
			$tmp['filename_i'] = './'.$this->path_cache.'/'.$this->pairsC[$tplName]['filename']. '.i.php';
			/* Search for template tags */
			$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
			if (preg_match_all($preg, $tmp['tpl_content'], $tmp['tpl_matches']))
			{
				/* array with template commands */
				$arCmd = array();
				/* fix for `< ? x m l  ? >' */
				$arCmd[] = '<?xml';
				$arRpl[] = '<?php echo "<","?xml"; ?>'; // parameter works faster that concatenation
				/* */
				foreach ($tmp['tpl_matches'][2] as $k => $cmd_src)
				{
					/* put command name into array */
					/* $tmp['tpl_matches'][1] and $tmp['tpl_matches'][3] are open/close tags */
					$arCmd[] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
					/* text filter */
					$tmp['cmd'] = trim($cmd_src);
#					$tmp['cmd'] = trim(preg_replace("# +|\t+#", " ", $cmd_src));
					if (strstr($tmp['cmd'], ' ')) /* not a variable */
					{
						$arCmdParts = explode(' ', $tmp['cmd']);
						$arRpl[] = $this->oCmd->$arCmdParts[0]($arCmdParts[1]);
					}
					elseif (substr($tmp['cmd'], 0, 1) == "/") /* block */
					{
						/* search for functions */
						$func = '_'.substr($tmp['cmd'], 1).'End';
						$arRpl[] = $this->oCmd->$func();
					}
					else /* simple variable */
					{
						$arRpl[] = $this->oCmd->_var($tmp['cmd']);
					}
					/* do replace */
					$tmp['str'] = str_replace($arCmd, $arRpl, $tmp['tpl_content']);
				} /* end of while */
				/* Save all replaced template */
				$this->_file_save($tmp['filename_c'], $tmp['str'], "w");
			} /* end of preg_match templates */
			/* save new iteration */
			$strInternal = $this->oCmd->get_contents_c();
#			prn_r( $strInternal, __LINE__ );
			$tmp['str_i'] = '<?php'.
							CRLF . '$template_timestamp = ' . strval(time()-1) . ';'.
							CRLF . $strInternal . '?>';
			$this->_file_save($tmp['filename_i'], $tmp['str_i'], 'w');
		} /* end */
		return $strInternal;
	}

	public function _e($v)
	{
		$tmp['var'] = '';
		if ( $this->isNoCachedVar($v) && !$this->is_cache_parse && $this->is_cache_keypresent )
		{
			// checking for cache
			print '<br/>!is_cache_parse';
		}
		else
		{
			// new block or variable
			if ($currBlock = @end($this->arBlockC))
			{
				// dynamic block
				$tmp['var'] =& $this->varsRun[$currBlock][$v];
			}
			else
			{
				// static variable
				$tmp['var'] =& $this->pairsV[$v];
			}
		}
		if ($this->is_cache_write)
		{
#			$tmp['var'] = str_replace('<'.'?', '<'.'?php echo "<'.'?"; ?'.'>'.CRLF);
#			$tmp['var'] = str_replace('?'.'>', '<'.'?php echo '?'.'>'; ?'.'>'.CRLF);
			echo $tmp['var'];
		}
		else
		{
			echo $tmp['var'];
		}
	}
	public function parse($varName = '', $cacheKey = NULL)
	{
		// start
		ob_start();
		$tpl = array();
		$this->var_last_parsed = '';
		$tpl['value'] = '';
		foreach ($this->pairsC as $k => $arV)
		{
			$tpl['value'] .= $arV['filedesc'];
		}
		$is_cached = 0;

		if (!$is_cached)
		{
			$this->is_cache_write = is_null($cacheKey) ? 0 : 1;
			foreach ($this->pairsC as $k => $arV)
			{
				$tmp['filename_c'] = './'.$this->path_cache.'/'.$arV['filename']. '.php';
				if (file_exists($tmp['filename_c']))
				{
					if ($this->is_tpl_show_names)
					{
						print '<table border="1" cellspacing="0"><tr><td>'.$tmp['filename_c'].'</td></tr><tr><td>';
					}
					include( $tmp['filename_c'] );
					if ($this->is_tpl_show_names)
					{
						print '</tr></td></table>';
					}
				}
				else
				{
					$this->_halt($tmp['filename_c'] . ' not found');
				}
			}
			//
			// Save cached file
			//
			if ($this->is_cache_write)
			{
#				print '<br>Saving cached file...';
#				$this->_file_save(ob_get_contents());
				ob_end_clean();
				ob_start();
				$is_cached = 1;
			}
			$this->is_cache_write = 0;
			if ($is_cached)
			{
				$this->is_cache_parse = 1;
				// load cached file
				// ..
				$this->is_cache_parse = 0;
			}
			// put included contents into new assigned value
			$tmp['ob_contents'] = array($varName.'_last' => ob_get_contents());
			ob_end_clean();
			$this->var_last_parsed = $this->assign($tmp['ob_contents']);
			$this->is_cache_keypresent = 0;
		}
	}
	/**
	 * Called for each step in loop
	 *
	 * @access public
	 */
	public function parseDynamic($dynName)
	{
		$this->_parse_var($dynName);
		$vars =& $this->arBlockI[$dynName]['var'];
		$bpv =& $this->arBlockV[$dynName][];
		if (is_array($vars))
		{
			foreach ($vars as $k => $v)
			{
				@$bpv[$v] = $this->pairsV[$v];
			}
		}
		$a1 =& $this->arBlockI[$dynName]['childs'];
		if (is_array($a1))
		{
			foreach ($a1 as $k => $child)
			{
				$this->arBlockV[$child][] = 'end';
			}
		}
	}
	/* */
	public function _dRun($dynName)
	{
		static $dyn;
		# Writing cached file
		if ($this->isNoCachedVar($dynName) && !$this->is_cache_parse && $this->is_cache_keypresent)
		{
			print '<br/>cache run';
			exit;
		}
		if (@end($this->arBlockC) != $dynName)
		{
			$this->arBlockC[] = $dynName;
		}

		if (!isset($this->arBlockV[$dynName]) || !is_array($this->arBlockV[$dynName]))
		{
			array_pop($this->arBlockC);
			return false;
		}

		if (!isset($this->arBlockPos[$dynName]))
		{
			$this->arBlockPos[$dynName] = 0;
		}

		if (!array_key_exists($this->arBlockPos[$dynName], $this->arBlockV[$dynName]))
		{
			unset($this->arBlockPos[$dynName]);
			array_pop($this->arBlockC);
			return false;
		}

		$this->varsRun[$dynName] = $this->arBlockV[$dynName][$this->arBlockPos[$dynName]];
		$this->arBlockPos[$dynName]++;

		if ($this->varsRun[$dynName] == 'end')
		{
			unset($this->arBlockPos[$dynName]);
			array_pop($this->arBlockC);
			return false;
		}
		return true;
	}
	/* */
	public function _dEndHook()
	{
		# Writing cached file
		if (!$this->is_in_cache && !$this->is_cache_parse && $this->is_cache_keypresent)
		{
			$this->is_in_cache++;
			echo '<?php endwhile; ?>'.CRLF;
		}
	}
	/* */
	public function output($varName = NULL)
	{
		global $oFunc;
		if (is_null($varName))
		{
			$varName = $this->var_last_parsed;
		}
		else
		{
			$this->_parse_var($varName);
		}
		$str = isset($this->pairsV[$varName]) ? $this->pairsV[$varName] : $varName;
		/* Post parsing, variables only */
		$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
		if (preg_match_all($preg, $str, $tmp['tpl_matches']))
		{
			foreach ($tmp['tpl_matches'][0] as $k2 => $v2)
			{
				$str_key = $tmp['tpl_matches'][2][$k2];
				$this->_parse_var($str_key);
				if (isset($this->pairsV[$str_key]))
				{
					$str = str_replace($v2, $this->pairsV[$str_key], $str);
				}
				else
				{
					/* add variable name to the list of unmatched template variables */
					$str = str_replace($v2, '', $str);
				}
			}
		}
		/* set output encoding */
		$this->o_encoding = ($this->o_encoding == '') ? 'utf-8' : $this->o_encoding;
		$str = $oFunc->gwConvertCharset($str, 'utf-8', $this->o_encoding);
		return $str;
	}
	/* @access public */
	public function gw_text_replace_vars($t = '', $ar = array(), $is_keep = 0)
	{
		$arCmd = array();
		$arRpl = array();
		/* Search for template tags */
		$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
		if (preg_match_all($preg, $t, $tmp['tpl_matches']))
		{
			foreach ($tmp['tpl_matches'][2] as $k => $cmd_src)
			{
				$arCmd[$k] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
				$tmp['cmd'] = trim($cmd_src);
				$tmp['cmd'] = isset($ar[$tmp['cmd']]) ? $ar[$tmp['cmd']] : ($is_keep ? $arCmd[$k] : '');
				$arRpl[$k] = $tmp['cmd'];
			}
			/* replaces variables only */
			$t = str_replace($arCmd, $arRpl, $t);
		}
		return $t;
	}
	/* */
	public function _halt($str)
	{
		print '<br />[Template class: ' . $str . ']';
	}
	public function isNoCachedVar($varName)
	{
		return (substr($varName, 0, 3) == 'non');
	}

} /* end of class */

class gwv_template_cmd extends gwv_template
{
	/* */
	public function __construct()
	{
		$this->_reset();
	}
	/* */
	public function _reset()
	{
		$this->namespace_default = 'GW';
		$this->arBlockV = array();
		$this->arBlockC = array();
		$this->arBlockI = array();
		$this->arChilds = array();
		$this->arNamespaces = array();
		$this->arBlockPos = array();
		$this->varsRun = array();
		$this->curLevel = 0;
	}
	/* */
	public function get_contents_c($is_delete = 1)
	{
		$str = '';
		foreach ($this->arBlockI as $block => $info)
		{
			if (isset($info['var']) && is_array($info['var']))
			{
				foreach ($info['var'] as $k => $v)
				{
					$str .= "\$arBlockI[\"$block\"]['var'][] = \"$v\";\n";
				}
			}
			if (isset($info['childs']) && is_array($info['childs']))
			{
				foreach ($info['childs'] as $k => $child)
				{
					$str .= "\$arBlockI[\"$block\"]['childs'][] = \"$child\";\n";
				}
			}
		}
		if ($is_delete)
		{
			$this->arBlocksC = $this->arBlocksI = array();
		}
		return $str;
	}
	/* */
	public function _var($v)
	{
		$this->_parse_var($v);
		if ($currBlock = @end($this->arBlockC))
		{
			$this->arBlockI[$currBlock]['var'][] = $v;
		}
		return '<'.'?php $this->_e("'.$v.'");?>';
	}
	/* shorthand for dynamic() */
	public function d($dynName)
	{
		return $this->dynamic($dynName);
	}
	/* */
	public function dynamic($dynName)
	{
		$this->_parse_var($dynName);
		if (@end($this->arBlockC))
		{
			$this->arBlockI[current($this->arBlockC)]['childs'][] = $dynName;
		}
		else
		{
			$this->arBlockI[$dynName]['childs'] = array();
		}
		$this->arBlockC[] = $dynName;
		return '<'.'?php while ($this->_dRun("'.$dynName.'")) : ?>'.CRLF;
	}
	/* shorthand for dynamicEnd() */
	public function _dEnd()
	{
		return $this->dynamicEnd();
	}
	/* */
	public function dynamicEnd()
	{
		array_pop($this->arBlockC);
		return '<'.'?php $this->_dEndHook(); endwhile; ?>'.CRLF;
	}
}
$oTpl = new gwv_template();
}

