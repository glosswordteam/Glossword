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

class gw_mini_timer
{

    public $p, $a;

    public function __construct($p = '')
    {
        $this->start($p);
    }

    public function start($p = '')
    {
        $this->p                 = $p ? $p : mktime();
        $this->a[$this->p . 's'] = array_sum(explode(' ', microtime()));
    }

    public function end($p = '')
    {
        $p                 = $p ? $p : $this->p;
        $this->a[$p . 'e'] = array_sum(explode(' ', microtime()));
        if (isset($this->a[$p . 's'])) {
            $this->a[$p] = sprintf("%1.5f", $this->a[$p . 'e'] - $this->a[$p . 's']);
            return $this->a[$p];
        }
        return 0;
    }

    public function _($p = '')
    {
        return $this->get($p);
    }

    public function get($p = '')
    {
        if (isset($this->a[$p])) {
            return $this->a[$p];
        }
        return $this->a;
    }
}
