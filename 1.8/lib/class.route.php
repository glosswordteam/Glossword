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
 * URL builder for legacy admin/index links.
 *
 * Compatible with PHP 5.6.
 */
class gwUrlBuilder
{

    /**
     * @var array
     */
    protected $sys = [];

    /**
     * Constructor.
     *
     * Expected keys in $sys:
     * - page_admin
     * - page_index
     *
     * @param array $sys
     */
    public function __construct(array $sys)
    {
        $this->sys = $sys;
    }

    /**
     * Build URL from base path and query params.
     *
     * @param string $base
     * @param array $params
     *
     * @return string
     */
    private function _build_url($base, array $params)
    {
        $query = http_build_query($this->filter_null_params($params), '', '&');

        if ($query === '') {
            return $base;
        }

        return $base . '?' . $query;
    }

    /**
     * Build admin URL with mandatory action and target params.
     *
     * @param string $action
     * @param string $target
     * @param array $params
     *
     * @return string
     */
    public function build_admin_url($action, $target, array $params = [])
    {
        $base_params = [
            GW_ACTION => $action,
            GW_TARGET => $target,
        ];

        return $this->_build_url(
            $this->get_page_admin(),
            $base_params + $params
        );
    }

    /**
     * Build index URL.
     *
     * @param array $params
     *
     * @return string
     */
    public function build_index_url($action, $target, array $params = [])
    {
        $base_params = [
            GW_ACTION => $action,
        ];

        if ($target !== null) {
            $base_params[GW_TARGET] = $target;
        }

        return $this->_build_url(
            $this->get_page_index(),
            $base_params + (array)$params
        );
    }


    /**
     * Return admin page path.
     *
     * @return string
     */
    protected function get_page_admin()
    {
        return isset($this->sys['page_admin']) ? $this->sys['page_admin'] : '';
    }

    /**
     * Return index page path.
     *
     * @return string
     */
    protected function get_page_index()
    {
        return isset($this->sys['page_index']) ? $this->sys['page_index'] : '';
    }

    /**
     * Remove params with NULL values.
     *
     * Keeps 0, '0', false and empty string intact.
     *
     * @param array $params
     *
     * @return array
     */
    protected function filter_null_params(array $params)
    {
        $result = [];

        foreach ($params as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

