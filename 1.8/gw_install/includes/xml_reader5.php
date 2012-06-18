<?php
/**
 *  XMLReader, PHP5.
 *  Converts XML-file into Array.
 *  © 2008 Glossword.biz team (http://glossword.biz/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 * $Id: xml_reader5.php 3 2008-06-21 07:22:47Z glossword_team $
 */
class gw2_xmlreader5
{
	var $is_skip_root = 1;
	function get($filename)
	{
		if (!file_exists($filename)){ return $filename; }
		$o = new XMLReader();
		$o->open( $filename );
		/* Skip root node */
		if ($this->is_skip_root)
		{
			$o->read();
		}
		/* */
		return $this->xml2array($o);
	}
	/* */
	function xml2array($o)
	{
		$ar = null;
		while ($o->read())
		{
			switch ($o->nodeType)
			{
				case XMLReader::ELEMENT:
					$tag  = $o->localName;
					$attributes = array();
					if ($o->hasAttributes)
					{
						while ( $o->moveToNextAttribute() )
						{
							$attributes[$o->name] = $o->value;
						}
					}
					$ar[$tag][] = array(
						'tag' => $tag,
						'attributes' => $attributes,
						'value' => $o->isEmptyElement ? '' : $this->xml2array($o)
					);
				break;
				case XMLReader::TEXT:
				case XMLReader::CDATA:
					$ar .= $o->value;
				break;
				case XMLReader::END_ELEMENT: 
					return $ar;
				break;
			}
 		}
 		return $ar;
	}
}
?>