<?php 
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* --------------------------------------------------------
 * $Id: class.domxml.php 483 2008-06-11 02:39:36Z glossword_team $
 * Easy DOM XML class, helps to parse XML files
 * Obsoleted by XPath.
 * ----------------------------------------------------- */
if (!defined('IS_CLASS_DOMXML'))
{
	define('IS_CLASS_DOMXML', 1);
	/* ------------------------------------------------------*/
	$tmp['mtime'] = explode(' ', microtime());
	$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
	/* ------------------------------------------------------*/
class gw_domxml
{
	/**
	 *
	 */
	var $strData = '<xml><line type="123">abc<level>qwerty</level></line><line type="456">def</line></xml>';
	var $arData = array();
	var $vals = '';
	var $index = '';
	var $is_skip_white = 1;
	var $is_case_folding = 0;
	var $max_nesting = 0;
	var $msg_error = '&#160;';
	var $txt_magic_splitter = "&#xA;";
	/**
	 *
	 */
	function get_xml_content($tagname, $key = 3)
	{
		/* preg_match! */
		preg_match_all("/<" . $tagname . "( (.*?))*>(.*?)<\/".$tagname.">/si", $this->strData, $arFound);
		return isset($arFound[$key]) ? $arFound[$key] : array();
	}
	/**
	 *
	 */
	function get_content($arElement, $tagname = '')
	{
		/* current $arElement only */
		if ($tagname == '')
		{
			$arReturn = array();
			if (!is_array($arElement))
			{
				$arReturn[] = $arElement;
			}
			/* single tag value */
			if (is_array($arElement) && isset($arElement['value']))
			{
				array_push($arReturn, $arElement['value']);
			}
			elseif (is_array($arElement))
			{
				/* multiple tags value */
				while (list($elK1, $elV1) = each($arElement)) /* each founded element: 0, 1 */
				{
					$arReturn = array_merge($arReturn, array($this->get_content($elV1)));
				}
			}
			return implode(' ', $arReturn);
		}
		/* go for tree */
		$tagname = strtolower($tagname);
		for ( reset($arElement); list($elK, $elV) = each($arElement); )
		{
			if ( !is_array($elV) && sprintf("%s", $elV) == $tagname )
			{
				return isset($arElement['value']) ? $arElement['value'] : '';
			}
			elseif ( sprintf("%s", $elK) == 'children' )
			{
				for ( reset($elV); list($elK2, $elV2) = each($elV); )
				{
					if ($this->get_content($elV2, $tagname))
					{
						return $this->get_content($elV2, $tagname);
					}
				}
				return array();
			}
		}
	}
	/**
	 *
	 */
	function get_attribute($attrname, $tagname, $a = array())
	{
		$attrname = strtolower($attrname);
		$tagname = strtolower($tagname);
		/* do not parse strings */
		if (!is_array($a)){ return; }
		/* fix array without zero key [0] */
		if (!isset($a[0]))
		{
			$a = array($a);
		}
		/* fix empty array */
		if (empty($a))
		{
			$a = $this->arData;
		}
		/* for each founded element: 0, 1 */
		while (list($elK1, $elV1) = each($a))
		{
			if (is_array($elV1) && isset($elV1['attributes']) && isset($elV1['tag']) && ($elV1['tag'] == $tagname)) // per attrib, tag, children
			{
				return isset($elV1['attributes'][$attrname]) ? $elV1['attributes'][$attrname] : '';
			}        
			elseif (is_array($elV1) && isset($elV1['attributes']) && $tagname == '' ) /* no tag */
			{
				return isset($elV1['attributes'][$attrname]) ? $elV1['attributes'][$attrname] : '';
			}
			elseif ( is_array($elV1) && isset($elV1['children']) && is_array($elV1['children']) )
			{
				#return $this->get_attribute($attrname, $tagname, $elV1['children']);
			}
		}
		return '';
	}
	/* */
	function get_elements_by_tagname($tagname, $a = array())
	{
		$tagname = strtolower($tagname);
		$arReturn = array();
		if (empty($a)){ $a = $this->arData; }
		while (list($elK1, $elV1) = each($a)) /* each founded element: 0, 1 */
		{
			if (isset($elV1['tag']) && ($elV1['tag'] == $tagname)) /* per attrib, tag, children */
			{
				array_push($arReturn, $elV1);
			}
			else if ( isset($elV1['children']) && is_array($elV1['children']) )
			{
				$arReturn = array_merge($arReturn, $this->get_elements_by_tagname($tagname, $elV1['children']));
			}
		}
		return $arReturn;
	}
	/**
	 *
	 */
	function get_children($vals, &$i)
	{
		$children = array();
		$cntVals = sizeof($vals);
		/* TODO: limit nesting levels */
		if ($vals[$i]['level'] > 4)
		{
			/* 
			$str_value = '';
			switch ($vals[$i]['type'])
			{
				case 'cdata':
					$str_value .= $vals[$i]['value'];
				break;
				case 'complete':
					$str_value .= '['.$vals[$i]['tag'].']'.$vals[$i]['value'].'[/'.$vals[$i]['tag'].']';
				break;
				case 'open':
					$str_value .= '['.$vals[$i]['tag'].']';
				break;
			}
			*/
		}
		while (++$i < $cntVals)
		{
			/* Compare types */
			switch ($vals[$i]['type'])
			{
				case 'cdata':
					$children[] = $vals[$i]['value'];
				break;
				case 'complete':
					$children[] = array(
						'tag' => $vals[$i]['tag'],
						'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
						'value' => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
					);
				break;
				case 'open':
					$children[] = array(
						'tag' => $vals[$i]['tag'],
						'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
						'value' => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
						'children' => $this->get_children($vals, $i)
					);
				break;
				case 'close':
					return $children;
				break;
			}
		}
	}
	/**
	 *
	 */
	function xml2array()
	{
		/* http://www.w3.org/TR/REC-xml/#AVNormalize */
#		$this->strData = preg_replace("/(\r\n|\n|\r)/", $this->txt_magic_splitter, $this->strData);
		$p = xml_parser_create('UTF-8');
		@xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, $this->is_skip_white);
		@xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, $this->is_case_folding);
		xml_parse_into_struct($p, $this->strData, $vals );
		xml_parser_free($p);
		/* */
		$ar_last = end($vals);
		if (isset($ar_last['level']))
		{
			$this->msg_error = 'XML level: <strong>'.$ar_last['level'].'</strong>, tag: <strong>'.$ar_last['tag'].'</strong>, type: <strong>'.$ar_last['type'].'</strong>';
		}
		if (isset($ar_last['value']))
		{
			$this->msg_error .= ', value: '.htmlspecialchars(substr($ar_last['value'], 0, 128));
		}
		$tree = array();
		$i = 0;
		if (!empty($vals))
		{
			array_push($tree,
					array('tag'        => $vals[$i]['tag'],
						  'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
						  'value'      => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
						  'children'   => $this->get_children($vals, $i),
						)
					);
		}
		else
		{
			$this->msg_error = 'No XML data';
		}
		return $tree;
	}
	/**
	 *
	 */
	function SetCustomArray($ar)
	{
		$this->arData = $ar;
	}    
	/**
	 *
	 */
	function parse()
	{
		/* Remove UTF-8 Signature */
		if (substr($this->strData, 0, 3) == sprintf('%c%c%c', 239, 187, 191))
		{
			$this->strData = substr($this->strData, 3);
		}
		$this->arData = $this->xml2array();
	}
} /* end of class */
/* ------------------------------------------------------*/
$tmp['mtime'] = explode(' ', microtime());
$tmp['endtime'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
$tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
}

?>