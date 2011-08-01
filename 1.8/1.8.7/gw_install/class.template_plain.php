<?php

class i_template
{
	var $pairsV = array();
	var $pairsC = array();
	/* File-based */
	function init()
	{
		global $sys, $gv;
		$this->set_path_src($sys['path_install'].'/template');
	}
	/**
	 * @access  public
	 */
	function set_path_src($d)
	{
		$this->path_source =& $d;
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
	/* shortcut to assign() */
	function a($k, $v)
	{
		$this->assign(array($k => $v));
	}
	function define($ar = array())
	{
		while (is_array($ar) && list($tplName, $filename) = each($ar))
		{
			$tplName = crc32($filename);
			if (isset($this->pairsC[$tplName]))
			{
				/* Do not load file second time */
				continue;
			}
			$this->pairsC[$tplName] = array(
				'filename' => $filename,
				'filedesc' => $this->_file_load('./' . $this->path_source . '/' . $filename)
			);
			eval($this->_compile($tplName));
		}
	}
	/* */
	function assign($ar = array())
	{
		for (reset($ar); list($n, $v) = each($ar);)
		{
			/* Put string value into array of variables */
			if (!is_array($v))
			{
				$this->pairsV[$n] = strval($v);
			}
		}
	}
	function _compile($tplName)
	{
		/* if current template exists in array (filename) */
		if (isset($this->pairsC[$tplName]) && isset($this->pairsC[$tplName]['filedesc']))
		{
			$arRpl = array();
			/* Source template content which will be replaced */
			$tmp['tpl_content'] =& $this->pairsC[$tplName]['filedesc'];
			/* Search for template tags */
			$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
			if (preg_match_all($preg, $tmp['tpl_content'], $tmp['tpl_matches']))
			{
				/* array with template commands */
				$arCmd = array();
				/* fix for `< ? x m l  ? >' */
				$arCmd[] = '<?xml';
				$arRpl[] = '<?php echo "<","?xml"; ?>'; // parameter works faster that concatenation
				while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
				{
					/* put command name into array */
					/* $tmp['tpl_matches'][1] and $tmp['tpl_matches'][3] are open/close tags */
					$arCmd[] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
					/* text filter */
					$tmp['cmd'] = trim($cmd_src);
					$arRpl[] = '<?'.'php $this->_e(\''.$tmp['cmd'] .'\');?'.'>';
					/* do replace */
					$tmp['str'] = str_replace($arCmd, $arRpl, $tmp['tpl_content']);
				}
				$this->pairsC[$tplName] = $tmp['str'];
			}
		}
	}
	/* */
	function _e($v)
	{
		/* simple variables only, no blocks */
		$tmp['var'] =& $this->pairsV[$v];
		echo $tmp['var'];
	}
	/* */
	function parse()
	{
		$tpl['value'] = implode($this->pairsC);
		ob_start();
		eval(' ?'.'>' . $tpl['value'] . '<?php ');
		$this->pairsV[0] = ob_get_contents();
		ob_end_clean();
	}
	/* */
	function output()
	{
		$str =& $this->pairsV[0];
		/* Post parsing, variables only */
		$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
		if (preg_match_all($preg, $str, $tmp['tpl_matches']))
		{
			for (reset($tmp['tpl_matches'][0]); list($k2, $v2) = each($tmp['tpl_matches'][0]);)
			{
				$str_key = $tmp['tpl_matches'][2][$k2];
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
		return $str;
	}
}
?>