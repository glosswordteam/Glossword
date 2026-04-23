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

/**
 * Autolinks for Glossword
 */
class gw_autolinks
{
	public $arLinks = array();
	public $path_to_file = 'gw_xml/autolinks/words.txt';
	public $is_abbr = 1;
	public $is_url = 1;
	public $str_splitter = ' = ';
	public $str_abbreviation_tag = 'acronym';
	/* for <html> or [bbcode] tags */
	public $regexp_no = array('html' => '<[div|span|p](?:[^>]|\n)*>', 'bbcode' => '\[(?:[^\]]|\n)*\]');
	public $regexp_id = 'html';
	/* */
	public function init($filename = '')
	{
		$this->load_file($filename);
	}
	/* */
	public function load_file($filename = '')
	{
		$filename = ($filename == '') ? $this->path_to_file : $filename;
		$arLines = array();
		if (file_exists($filename))
		{
			$arLines = file($filename);
		}
		foreach ($arLines as $k => $v)
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
	public function autolink($t)
	{
		/* Autolinks */
		$regexp_l = "([^=][ \"\',\.;\(\[\]\/\n]|^)";
		$regexp_r = "([ \"\'\,\.\-\!\?\&<\/;:\)\]\[]|\'s|s|ed|es|$)";
		/* Parse HTML or BBcode */
		$ar_preg_no = preg_split("/".$this->regexp_no[$this->regexp_id]."/", $t);
		foreach ($ar_preg_no as $k1 => $v1)
		{
			/* skip empty lines */
			if (trim($v1) == '') { unset($ar_preg_no[$k1]); continue; }
			/* copy line */
			$new_v1 = $v1;
			foreach ($this->arLinks as $k2 => $v2)
			{
				$k2 = substr($k2, 3);
				if (preg_match_all("/".$regexp_l."($k2)".$regexp_r."/u", $new_v1, $ar_preg))
				{
					foreach ($ar_preg[2] as $k3 => $v3)
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