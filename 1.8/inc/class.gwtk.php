<?php
/**
 * Translation Kit class.
 * © 2003-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 * http://glossword.info/dev/
 */
/**
 * Core Translation Kit functions.
 *
 * Usage:
 *      $oL = new gwtk;
 *      $oL->setHomeDir("locale");  // path to ./your_project/locale
 *      $oL->setLocale("ru-utf8");  // path to ./your_project/locale/ru-utf8/
 *      print $oL->m("variable_name");
 *      // set another language
 *      $oL->setLocale("en-utf8");
 *      print $oL->m(1);
 *
 *      print_r( $oL->getLanguages() );
 *
 */
/* ------------------------------------------------------ */
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.gwtk.php 84 2007-06-19 13:01:21Z yrtimd $ -->');
}
/* ------------------------------------------------------ */
if (!defined('IS_CLASS_GWTK'))
{
	define('IS_CLASS_GWTK', 1);

	$tmp['mtime'] = explode(' ', microtime());
	$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];

class gwtk
{
	var $pathHomeDir = 'locale';
	var $pathLocale  = 'en';
	var $pathFile    = 'global';
	var $pathEx      = 'php';
	var $pathRoot    = '.';
	var $varMode     = 'names'; /* [ names | constants ], `names' is default */
	var $varName     = 'lang'; /* for "names" mode only */
	var $url_prefix  = 'il'; /* url parameter `&lang=' */
	var $arMessages  = array();
	var $lang = array();
	/**
	 * Sets directory name for ./your_project/$path
	 */
	function setHomeDir($path)
	{
		$this->pathHomeDir = $path;
	}
	function getLocale()
	{
		return $this->pathLocale;
	}
	function getHomeDir()
	{
		return $this->pathHomeDir;
	}
	/**
	 * Sets new url parameter instead of `&lang='
	 */
	function setPrefix($str)
	{
		$this->url_prefix = $str;
	}
	/**
	 * Appends '&lang=' to any url
	 */
	function url($url, $locale_id)
	{
		if ($locale_id != '')
		{
			$url = preg_replace("/[&?]" . $this->url_prefix . "=([0-9a-z]+)/", '', $url);
			$url = preg_replace("/[&?]+$/", '', $url);
			$url .= ((strpos($url, "?") != false) ?  "&" : "?") . $this->url_prefix . '=' . $locale_id;
		}
		return $url;
	}
	/**
	 * Sets directory name for ./your_project/locale/$localename
	 *
	 * @param	string	$localename Directory path name to phrases
	 * @see _getFileName();
	 */
	function setLocale($localename)
	{
		$this->pathLocale = $localename;
		$f = $this->_getFileName();
		/* load language configuration */
		if (file_exists($f))
		{
			global $tmp;
			include($f);
			if ($this->varMode == 'names')
			{
#				$this->lang = array_merge($this->lang, ${$this->varName});
				$this->lang = ${$this->varName};
			}
		}
	}
	/**
	 * Creates new array from custom file
	 * ./your_project/locale/$localename/$f_name
	 *
	 * @param	string	$f_name File name with phrases
	 * @param	string	$localename Directory path name to phrases
	 * @access	private
	 * @return	array	Phrases
	 * @see _getFileName();
	 */
	function _loadCustom($f_name, $localename)
	{
		/* custom language file */
		$f = $this->_getFileName();
		$f_custom = str_replace($this->pathFile, $f_name, $f);
		$f_custom = str_replace($this->pathLocale , $localename, $f_custom);
		$f_name = $this->varName.'_'.$f_name;
		if (file_exists($f_custom))
		{
			include($f_custom);
			if (isset(${$this->varName}))
			{
				$this->$f_name = ${$this->varName};
			}
#            asort($this->$f_name);
		}
	}
	/**
	 * Gets additional phrases in two ways:
	 * "join"   : merge custom data with existent phrases
	 * "return" : do not merge, just return new array
	 */
	function getCustom($f_name, $localename, $mode = 'join')
	{
		$this->_loadCustom($f_name, $localename);
		$f_name = $this->varName.'_'.$f_name;
		if ((isset($this->$f_name)) && ($mode == 'join'))
		{
			/* array_merge_clobber */
			$arNew = $this->lang;
			while (list($key, $val) = each($this->$f_name))
			{
				$arNew[$key] = $val;
			}
			$this->lang = $arNew;
			unset($this->$f_name);
		}
		elseif ($mode == 'return')
		{
			return isset($this->$f_name) ? $this->$f_name : array();
		}
	}
	/**
	 * Shows untranslated phrases
	 */
	function getMsgNames()
	{
		$this->arMessages = array_flip(array_values(array_unique($this->arMessages)));
		while (list($k, $v) = each($this->arMessages)) /* all phrases used on page */
		{
			if (isset($this->lang[$k])) /* global phrase list */
			{
				unset($this->arMessages[$k]); /* remove from used */
			}
		}
		return array_flip($this->arMessages);
	}
	/**
	 * Constructs filename to translation file
	 * @access	private
	 */
	function _getFileName()
	{
		$f = $this->pathHomeDir . '/' . $this->pathLocale . '/' . $this->pathFile . "." . $this->pathEx;
		return $f;
	}
	/**
	 * Main translation function.
	 * Last updated: 30 march 2003
	 */
	function m($t, $key = '')
	{
		$this->arMessages[] = $t;
		if ($this->varMode == 'names')
		{
			return (isset($this->lang[$t]) && is_array($this->lang[$t]) && isset($this->lang[$t][$key]))
					? $this->lang[$t][$key]
					: (isset($this->lang[$t]) ? $this->lang[$t] : $t);
		}
		else
		{
			return defined($t) ? eval("print $t;") : $t;
		}
	}
	/* */
	function getLanguages()
	{
		$d = opendir($this->pathHomeDir);
		$arLang = $arReturn =  array();
		$arSysLang = $this->languagelist();
		while (($f = readdir($d)) !== false)
		{
			if (isset($arSysLang[$f])
				&& !is_file($this->pathHomeDir . '/' . $f)
				&& !is_link($this->pathHomeDir . '/' . $f)
				)
			{
				$arReturn[$f] = $arSysLang[$f];
			}
		}
		closedir($d);
		asort($arReturn);
		reset($arReturn);
		return $arReturn;
	}
	/* */
	function languagelist($param = '')
	{
		/*
			'Locale directory name' => array(
				"ISO 639-2 or ISO 639-3 code",
				"Text direction",
				"ISO charset",
				"Display name",
				another settings
			);
		*/
		$a = array ( 
			'af-utf8' => 'a:5:{i:0;s:2:"af";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:9:"Afrikaans";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'sq-utf8' => 'a:5:{i:0;s:2:"sq";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:8:"Albanian";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ar-utf8' => 'a:5:{i:0;s:2:"ar";i:1;s:3:"rtl";i:2;s:5:"UTF-8";i:3;s:17:"Arabic - عربي";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'eu-utf8' => 'a:5:{i:0;s:2:"eu";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:16:"Basque - Euskara";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'bg-utf8' => 'a:5:{i:0;s:2:"bg";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:30:"Bulgarian - Българска";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'by-utf8' => 'a:5:{i:0;s:2:"by";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:33:"Byelorussian - Беларуски";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'km-utf8' => 'a:5:{i:0;s:2:"km";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:17:"Cambodian (Khmer)";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ca-utf8' => 'a:5:{i:0;s:2:"ca";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:17:"Catalan - Català";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'chr-utf8' => 'a:5:{i:0;s:3:"chr";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:28:"Cherokee - ᏣᎳᎩ Tsalagi";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'hr-utf8' => 'a:5:{i:0;s:2:"hr";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:19:"Croatian - Hrvatska";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'cs-utf8' => 'a:5:{i:0;s:2:"cs";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:15:"Czech - Česká";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'da-utf8' => 'a:5:{i:0;s:2:"da";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:14:"Danish - Dansk";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'du-utf8' => 'a:5:{i:0;s:2:"du";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Dutch - Nederlands";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'de-utf8' => 'a:5:{i:0;s:2:"de";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:16:"German - Deutsch";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'el-utf8' => 'a:5:{i:0;s:2:"el";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:24:"Greek - Ελληνικά";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'en-utf8' => 'a:5:{i:0;s:2:"en";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:10:"English US";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'es-utf8' => 'a:5:{i:0;s:2:"es";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Spanish - Español";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'et-utf8' => 'a:5:{i:0;s:2:"et";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:8:"Estonian";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'fa-utf8' => 'a:5:{i:0;s:2:"fa";i:1;s:3:"rtl";i:2;s:5:"UTF-8";i:3;s:18:"Farsi - فارسی";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'fi-utf8' => 'a:5:{i:0;s:2:"fi";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:15:"Finnish - Suomi";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'fr-utf8' => 'a:5:{i:0;s:2:"fr";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"French - Français";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'he-utf8' => 'a:5:{i:0;s:2:"he";i:1;s:3:"rtl";i:2;s:5:"UTF-8";i:3;s:19:"Hebrew - עברית";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:1:" ";}}',
			'he-iso' => 'a:5:{i:0;s:2:"he";i:1;s:3:"rtl";i:2;s:12:"ISO-8859-8-I";i:3;s:33:"Hebrew - עברית (ISO-Logical)";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:1:" ";}}',
			'hi-utf8' => 'a:5:{i:0;s:2:"hi";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:26:"Hindi - हिन्दी";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'hu-utf8' => 'a:5:{i:0;s:2:"hu";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Hungarian - Magyar";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ia-utf8' => 'a:5:{i:0;s:2:"ia";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:6:"Slovio";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:2:". ";}}',
			'id-utf8' => 'a:5:{i:0;s:2:"id";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:10:"Indonesian";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'it-utf8' => 'a:5:{i:0;s:2:"it";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Italian - Italiano";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ja-utf8' => 'a:5:{i:0;s:2:"ja";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:20:"Japanese - 日本語";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ka-utf8' => 'a:5:{i:0;s:2:"ka";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:8:"Georgian";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ku-utf8' => 'a:5:{i:0;s:2:"ku";i:1;s:3:"rtl";i:2;s:5:"UTF-8";i:3;s:27:"Kurdish - كوردی Kurdî";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ko-utf8' => 'a:5:{i:0;s:2:"ko";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Korean - 한국어";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'lt-utf8' => 'a:5:{i:0;s:2:"lt";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:22:"Lithuanian - Lietuviø";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'lv-utf8' => 'a:5:{i:0;s:2:"lv";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:7:"Latvian";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:2:". ";}}',
			'mk-utf8' => 'a:5:{i:0;s:2:"mk";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:10:"Macedonian";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'ms-utf8' => 'a:5:{i:0;s:2:"ms";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:14:"Malay - Melayu";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ml-utf8' => 'a:5:{i:0;s:2:"ml";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:9:"Malayalam";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'mn-utf8' => 'a:5:{i:0;s:2:"mn";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:24:"Mongolian - Монгол";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'no-utf8' => 'a:5:{i:0;s:2:"no";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:17:"Norwegian - Norsk";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ps-utf8' => 'a:5:{i:0;s:2:"ps";i:1;s:3:"rtl";i:2;s:5:"UTF-8";i:3;s:15:"Pashto (Afghan)";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'pl-utf8' => 'a:5:{i:0;s:2:"pl";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:15:"Polish - Polski";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'pt-utf8' => 'a:5:{i:0;s:2:"pt";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:23:"Portuguese - Português";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'pt-br-utf8' => 'a:5:{i:0;s:5:"pt-br";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:20:"Brazilian Portuguese";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'ro-utf8' => 'a:5:{i:0;s:2:"ro";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:19:"Romanian - Română";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:2:". ";}}',
			'ru-utf8' => 'a:5:{i:0;s:2:"ru";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:24:"Russian - Русский";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'ru-windows1251' => 'a:5:{i:0;s:2:"ru";i:1;s:3:"ltr";i:2;s:12:"windows-1251";i:3;s:16:"Russian - cp1251";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'rw-utf8' => 'a:5:{i:0;s:2:"rw";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:20:"Rwanda - Kinyarwanda";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'sk-utf8' => 'a:5:{i:0;s:2:"sk";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Slovak - Slovensky";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'sl-utf8' => 'a:5:{i:0;s:2:"sl";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:9:"Slovenian";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'sv-utf8' => 'a:5:{i:0;s:2:"sv";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:7:"Swedish";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'th-utf8' => 'a:5:{i:0;s:2:"th";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:16:"Thai - ไทย";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'tr-utf8' => 'a:5:{i:0;s:2:"tr";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:18:"Turkish - Türkçe";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'tt-utf8' => 'a:5:{i:0;s:2:"tt";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:5:"Tatar";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'ta-utf8' => 'a:5:{i:0;s:2:"ta";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:5:"Tamil";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'tzm-utf8' => 'a:5:{i:0;s:3:"tzm";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:21:"Tamazight - Tamaziɣt";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:2:". ";}}',
			'uz-utf8' => 'a:5:{i:0;s:2:"uz";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:5:"Uzbek";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'uk-utf8' => 'a:5:{i:0;s:2:"uk";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:30:"Ukraine - Українська";i:4;a:3:{s:19:"thousands_separator";s:1:" ";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'vi-utf8' => 'a:5:{i:0;s:2:"vi";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:31:"Vietnamese - Tiếng Việt Nam";i:4;a:3:{s:19:"thousands_separator";s:1:",";s:17:"decimal_separator";s:1:".";s:14:"part_separator";s:1:" ";}}',
			'zh-utf8' => 'a:5:{i:0;s:2:"zh";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:35:"Chinese Simplified - 中文(简体)";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}',
			'zh-tw-utf8' => 'a:5:{i:0;s:5:"zh-tw";i:1;s:3:"ltr";i:2;s:5:"UTF-8";i:3;s:36:"Chinese Traditional - 中文(繁体)";i:4;a:3:{s:19:"thousands_separator";s:1:".";s:17:"decimal_separator";s:1:",";s:14:"part_separator";s:2:". ";}}'
		);
		#
		# $ar = array( '', 'ltr', 'UTF-8', '', array('thousands_separator' => ',', 'decimal_separator' => '.', 'part_separator' => '. ') );
	
		if ($param != '')
		{
			/* return selected parameter */
			$ar_ser = isset($a[$this->pathLocale]) ? unserialize($a[$this->pathLocale]) : false;
			return isset($ar_ser[$param]) ? $ar_ser[$param] : false;
		}
		else
		{
			/* returns array [ISO 639 code] = "Display name" */
			while (list($k, $v) = each($a))
			{
				$a[$k] = unserialize($v);
				$ar[$k] = $a[$k][3];
			}
			return $ar;
		}
	}
} /* end of class */
$tmp['mtime'] = explode(' ', microtime());
$tmp['endtime'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
$tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
}
/* defined IS_CLASS_GWTK */

/* end of file */
?>