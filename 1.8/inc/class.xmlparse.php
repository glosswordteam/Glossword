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
 * Validate XML string syntax.
 */
class gw_xml_validator
{
    /** @var string */
    public $encoding = 'UTF-8';

    /** @var string */
    public $last_error = '';

    /**
     * Validate XML syntax.
     *
     * @param string $xml_data
     * @return bool
     */
    public function parse($xml_data = '')
    {
        $this->last_error = '';

        if (!is_string($xml_data) || $xml_data === '') {
            $this->last_error = 'XML data is empty.';

            return false;
        }

        if (!extension_loaded('dom')) {
            $this->last_error = 'Required PHP extension `dom` is not loaded.';

            return false;
        }

        $xml_document = new DOMDocument('1.0', $this->encoding);

        libxml_use_internal_errors(true);

        $is_loaded = $xml_document->loadXML($xml_data);

        if (!$is_loaded) {
            $this->last_error = $this->_get_libxml_error_message();
            libxml_clear_errors();

            return false;
        }

        libxml_clear_errors();

        return true;
    }

    /**
     * Return last validation error.
     *
     * @return string
     */
    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * Build readable libxml error message.
     *
     * @return string
     */
    private function _get_libxml_error_message()
    {
        $errors = libxml_get_errors();

        if (empty($errors)) {
            return 'Unknown XML parsing error.';
        }

        $first_error = reset($errors);

        return trim($first_error->message) . ' at line ' . (int) $first_error->line;
    }
}