<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2006 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
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
	var $path_source = 'tpl';
	var $path_cache = 'cache/tpl';
	var $pairsC = array();
	var $pairsV = array();
	var $pairsN = array(); /* virtual namespaces */
	var $namespace_default = 'GW';
	var $tag_start = '';
	var $tag_end = '';
	var $tag_noncached = 'non';
	var $is_tpl_show_names = 0;
	var $o_encoding = 'utf-8';
	var $ua_type = 'is_ua_client';
	/* default caching rules */
	var $is_cache_write = 0;
	var $is_cache_parse = 0;
	var $is_in_cache = 1;
	var $is_cache_keypresent = 0;
	/* default blocks */
	var $arBlockV = array();
	var $arBlockC = array();
	var $arBlockI = array();
	var $arChilds = array();
	var $arNamespaces = array();
	var $curLevel = 0;
	/* */
	var $ua_number = false;

// --------------------------------------------------------
// Autostart
// --------------------------------------------------------
	function gwv_template()
	{
		$this->namespace_default = 'GW';
		$this->oCmd = new gwv_template_cmd();
	}
// --------------------------------------------------------
// Supply functions
// --------------------------------------------------------
	/* */
	function get_info_files()
	{
		$ar = array();
		for (reset($this->pairsC); list($k, $v) = each($this->pairsC);)
		{
			$ar[crc32($v['filename'])] = $v['filename'];
		}
		return $ar;
	}
	/**
	 * @access  public
	 */
	function set_path_src($dir)
	{
		$this->path_source = $dir;
	}
	/**
	 * @access  public
	 */
	function set_path_cache($dir)
	{
		$this->path_cache = $dir;
	}
	/**
	 * External load file function
	 * @global $objFunc
	 */
	function _file_load($filename)
	{
		global $oFunc;
		return $oFunc->file_get_contents($filename);
	}
	/**
	 * External save file function
	 * @global $objFunc
	 */
	function _file_save($filename, $str, $mode = "w")
	{
		global $oFunc;
		return $oFunc->file_put_contents($filename, $str, $mode);
	}
	/**
	 *
	 */
	function _parse_var(&$n)
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
	function define($ar = array())
	{
		while (is_array($ar) && list($tplName, $filename) = each($ar))
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
	function assign($ar = array())
	{
		$str = '';
		for (reset($ar); list($n, $v) = each($ar);)
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
	function _compile($tplName)
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
				while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
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

	function _e($v)
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
	function parse($varName = '', $cacheKey = NULL)
	{
		// start
		ob_start();
		$tpl = array();
		$this->var_last_parsed = '';
		$tpl['value'] = '';
		for (reset($this->pairsC); list($k, $arV) = each($this->pairsC);)
		{
			$tpl['value'] .= $arV['filedesc'];
		}
		$is_cached = 0;

		if (!$is_cached)
		{
			$this->is_cache_write = is_null($cacheKey) ? 0 : 1;
			for (reset($this->pairsC); list($k, $arV) = each($this->pairsC);)
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
	function parseDynamic($dynName)
	{
		$this->_parse_var($dynName);
		$vars =& $this->arBlockI[$dynName]['var'];
		$bpv =& $this->arBlockV[$dynName][];
		if (is_array($vars))
		{
			for (reset($vars); list($k, $v) = each($vars);)
			{
				@$bpv[$v] = $this->pairsV[$v];
			}
		}
		$a1 =& $this->arBlockI[$dynName]['childs'];
		if (is_array($a1))
		{
			for (reset($a1); list($k, $child) = each($a1);)
			{
				$this->arBlockV[$child][] = 'end';
			}
		}
	}
	/* */
	function _dRun($dynName)
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
		if (!(list($k, $this->varsRun[$dynName]) = @each($this->arBlockV[$dynName])) ||
			$this->varsRun[$dynName] == 'end')
		{
			array_pop($this->arBlockC);
			return false;
		}
		return true;
	}
	/* */
	function _dEndHook()
	{
		# Writing cached file
		if (!$this->is_in_cache && !$this->is_cache_parse && $this->is_cache_keypresent)
		{
			$this->is_in_cache++;
			echo '<?php endwhile; ?>'.CRLF;
		}
	}
	/* */
	function output($varName = NULL)
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
			for (reset($tmp['tpl_matches'][0]); list($k2, $v2) = each($tmp['tpl_matches'][0]);)
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
	function gw_text_replace_vars($t = '', $ar = array(), $is_keep = 0)
	{
		$arCmd = array();
		/* Search for template tags */
		$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
		if (preg_match_all($preg, $t, $tmp['tpl_matches']))
		{
			while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
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
	function _halt($str)
	{
		print '<br />[Template class: ' . $str . ']';
	}
	function isNoCachedVar($varName)
	{
		return (substr($varName, 0, 3) == 'non');
	}

} /* end of class */

class gwv_template_cmd extends gwv_template
{
	/* */
	function gwv_template_cmd()
	{
		$this->_reset();
	}
	/* */
	function _reset()
	{
		$this->namespace_default = 'GW';
		$this->arBlockV = array();
		$this->arBlockC = array();
		$this->arBlockI = array();
		$this->arChilds = array();
		$this->arNamespaces = array();
		$this->curLevel = 0;
	}
	/* */
	function get_contents_c($is_delete = 1)
	{
		$str = '';
		for (reset($this->arBlockI); list($block, $info) = each($this->arBlockI);)
		{
			if (isset($info['var']) && is_array($info['var']))
			{
				for (reset($info['var']); list($k, $v) = each($info['var']);)
				{
					$str .= "\$arBlockI[\"$block\"]['var'][] = \"$v\";\n";
				}
			}
			if (isset($info['childs']) && is_array($info['childs']))
			{
				for (reset($info['childs']); list($k, $child) = each($info['childs']);)
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
	function _var($v)
	{
		$this->_parse_var($v);
		if ($currBlock = @end($this->arBlockC))
		{
			$this->arBlockI[$currBlock]['var'][] = $v;
		}
		return '<'.'?php $this->_e("'.$v.'");?>';
	}
	/* shorthand for dynamic() */
	function d($dynName)
	{
		return $this->dynamic($dynName);
	}
	/* */
	function dynamic($dynName)
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
	function _dEnd()
	{
		return $this->dynamicEnd();
	}
	/* */
	function dynamicEnd()
	{
		array_pop($this->arBlockC);
		return '<'.'?php $this->_dEndHook(); endwhile; ?>'.CRLF;
	}
}
$oTpl = new gwv_template();
}
?>