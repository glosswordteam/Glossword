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
 * Stores and outputs HTTP headers.
 */
class gw_headers
{
    private $_headers = [];

    /**
     * Adds a header line to the queue.
     *
     * @param string $header_line
     * @return void
     */
    public function add($header_line)
    {
        if ($header_line !== '') {
            $this->_headers[] = $header_line;
        }
    }

    /**
     * Sends queued headers.
     *
     * @return void
     */
    public function output()
    {
        if (headers_sent()) {
            return;
        }

        foreach ($this->_headers as $header_line) {
            header($header_line);
        }
    }

    /**
     * Returns queued headers.
     *
     * @return array
     */
    public function get()
    {
        return $this->_headers;
    }

    /**
     * Returns headers registered in PHP.
     *
     * @return array
     */
    public function get_sent()
    {
        return headers_list();
    }
} /* end of class */

/* Autostart */
$oHdr = new gw_headers();


