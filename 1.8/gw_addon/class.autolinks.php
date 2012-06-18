<?php
/**
 * Autolinks for Glossword
 * © 2004 Dmitry N. Shilnikov <dev at glossword dot info>
 * $Id: class.autolinks.php,v 1.5 2006/10/06 12:06:09 yrtimd Exp $
 * http://glossword.info/dev/
 * 
 * TODO:
 * 		Change parsing method for large lists
 * 		Add special sytnax
 * 			word1 = :google:     => <a href="google.com/q=word1">word1</a>
 * 			word2 = :glossword:  => <a href="glossword.info/q=word2">word2</a>
 * 			word3 = :this:       => <a href="this_domain.tld/q=word3">word3</a>
 */
	$tmp['mtime'] = explode(' ', microtime());
	$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];

class gw_autolinks
{
	var $arLinks = array();
	var $path_to_file = 'gw_xml/autolinks/words.txt';
	var $is_abbr = 1;
	var $is_url = 1;
	var $str_splitter = ' = ';
	var $str_abbreviation_tag = 'acronym';
	/* for <html> or [bbcode] tags */
	var $regexp_no = array('html' => '<[div|span|p](?:[^>]|\n)*>', 'bbcode' => '\[(?:[^\]]|\n)*\]');
	var $regexp_id = 'html';
	/* */
	function init($filename = '')
	{
		$this->load_file($filename);
	}
	/* */
	function load_file($filename = '')
	{
		$filename = ($filename == '') ? $this->path_to_file : $filename;
		$arLines = array();
		if (file_exists($filename))
		{
			$arLines = file($filename);
		}
		for (; list($k, $v) = each($arLines);)
		{
			$arKV = explode($this->str_splitter, $v);
			if (isset($arKV[0]) && isset($arKV[1]))
			{
				$arKV[0] = trim($arKV[0]);
				$arKV[1] = trim($arKV[1]);
				$arKV[1] = preg_replace("/^'/", '', $arKV[1]);
				$arKV[1] = preg_replace("/'$/", '', $arKV[1]);
				if ($this->is_url && preg_match("/^([a-zA-Z]+):\/\//", $arKV[1]))
				{
					$arKV[1] = '<a href="'.$arKV[1].'" onclick="window.open(this);return false">' . $arKV[0] . '</a>';
				}
				else if ($this->is_abbr)
				{
					$title = ($arKV[1] == '') ? '' : ' title="'.$arKV[1].'"';
					$arKV[1] = '<'.$this->str_abbreviation_tag.$title.'>' . $arKV[0] . '</'.$this->str_abbreviation_tag.'>';
				}
				$this->arLinks[sprintf("%02d",strlen($arKV[0])).' '.$arKV[0]] = $arKV[1];
			}
		}
		krsort($this->arLinks);
	}
	/* */
	function autolink($t)
	{
		/* Autolinks */
		$regexp_l = "([^=][ \"\',\.;\(\[\]\/\n]|^)";
		$regexp_r = "([ \"\'\,\.\-\!\?\&<\/;:\)\]\[]|\'s|s|ed|es|$)";
		/* Parse HTML or BBcode */
		$ar_preg_no = preg_split("/".$this->regexp_no[$this->regexp_id]."/", $t);
		for (; list($k1, $v1) = each($ar_preg_no);)
		{
			/* skip empty lines */
			if (trim($v1) == '') { unset($ar_preg_no[$k1]); continue; }
			/* copy line */
			$new_v1 = $v1;
			for (reset($this->arLinks); list($k2, $v2) = each($this->arLinks);)
			{
				$k2 = substr($k2, 3);
				if (preg_match_all("/".$regexp_l."($k2)".$regexp_r."/u", $new_v1, $ar_preg))
				{
					for (; list($k3, $v3) = each($ar_preg[2]);)
					{
						#$new_v1 = str_replace($ar_preg[1][$k3].$k2.$ar_preg[3][$k3], $ar_preg[1][$k3].$v2.$ar_preg[3][$k3], $new_v1);
						$t = str_replace($ar_preg[1][$k3].$k2.$ar_preg[3][$k3], $ar_preg[1][$k3].$v2.$ar_preg[3][$k3], $t);
					}
				}
			}
		}
		return $t;
	}
}
/* end of file */
?>