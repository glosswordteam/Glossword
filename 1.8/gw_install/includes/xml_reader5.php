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

/**
 * XMLReader wrapper for PHP 5.
 *
 * Converts XML file into array.
 */
class gw2_xmlreader5
{
    /**
     * Skip root XML element before parsing children.
     *
     * @var int
     */
    public $is_skip_root = 1;

    /**
     * Load XML from file and convert it to array.
     *
     * Returns false when file cannot be opened or parsed.
     *
     * @param string $filename
     *
     * @return mixed
     */
    public function get($filename)
    {
        if (!is_string($filename) || $filename === '') {
            return false;
        }

        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }

        $xml_reader = new XMLReader();
        $libxml_options = defined('LIBXML_NONET') ? LIBXML_NONET : 0;

        if (!xmlreader_open($xml_reader, $filename, null, $libxml_options)) {
            return false;
        }

        if ($this->is_skip_root) {
            $xml_reader->read();
        }

        $result = $this->xml2array($xml_reader);
        $xml_reader->close();

        return $result;
    }

    /**
     * Convert XMLReader node tree to array.
     *
     * @param XMLReader $xml_reader
     *
     * @return array|string|null
     */
    public function xml2array($xml_reader)
    {
        $result = null;

        while ($xml_reader->read()) {
            switch ($xml_reader->nodeType) {
                case XMLReader::ELEMENT:
                    $tag_name = $xml_reader->localName;
                    $attributes = $this->_read_attributes($xml_reader);

                    $result[$tag_name][] = [
                        'tag' => $tag_name,
                        'attributes' => $attributes,
                        'value' => $xml_reader->isEmptyElement ? '' : $this->xml2array($xml_reader),
                    ];
                    break;

                case XMLReader::TEXT:
                case XMLReader::CDATA:
                case XMLReader::SIGNIFICANT_WHITESPACE:
                    $result .= $xml_reader->value;
                    break;

                case XMLReader::END_ELEMENT:
                    return $result;
            }
        }

        return $result;
    }

    /**
     * Read attributes from current XML element.
     *
     * @param XMLReader $xml_reader
     *
     * @return array
     */
    private function _read_attributes($xml_reader)
    {
        $attributes = [];

        if (!$xml_reader->hasAttributes) {
            return $attributes;
        }

        if ($xml_reader->moveToFirstAttribute()) {
            do {
                $attributes[$xml_reader->name] = $xml_reader->value;
            } while ($xml_reader->moveToNextAttribute());

            $xml_reader->moveToElement();
        }

        return $attributes;
    }
}