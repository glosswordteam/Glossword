<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW')) {
    die('<!-- Not in App  -->');
}
/**
 * Recount dictionary items for all topics including child topics.
 */
class gw_topics_recounter
{
    /**
     * @var object
     */
    public $oDb = null;

    /**
     * @var array
     */
    public $sys = [];

    /**
     * @var array
     */
    public $ar_sum_totals = [];

    /**
     * @var array
     */
    public $ar_parents = [];

    /**
     * @var array
     */
    public $ar_items_counted = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $oDb, $sys;

        $this->oDb = $oDb;
        $this->sys = $sys;
    }

    /**
     * Recount topic item totals.
     *
     * @return void
     */
    public function recount()
    {
        $this->ar_sum_totals = [];
        $this->ar_parents = [];
        $this->ar_items_counted = [];

        $this->_load_topic_parents();
        $this->_load_topic_item_counts();
        $this->_recount_root_topics();
        $this->_save_totals();
    }

    /**
     * Load topic parent-child relations.
     *
     * @return void
     */
    protected function _load_topic_parents()
    {
        $sql = 'SELECT id_topic, id_parent
                FROM `' . $this->sys['tbl_prefix'] . 'topics`';

        $ar_sql = $this->oDb->sqlExec($sql);

        foreach ((array) $ar_sql as $ar_row) {
            $id_topic = (int) $ar_row['id_topic'];
            $id_parent = (int) $ar_row['id_parent'];

            if (!isset($this->ar_parents[$id_parent])) {
                $this->ar_parents[$id_parent] = [];
            }

            $this->ar_parents[$id_parent][] = $id_topic;
        }
    }

    /**
     * Load direct dictionary item counts for each topic.
     *
     * @return void
     */
    protected function _load_topic_item_counts()
    {
        $sql = 'SELECT id_topic, COUNT(*) AS cnt
                FROM `' . $this->sys['tbl_prefix'] . 'dict`
                GROUP BY id_topic';

        $ar_sql = $this->oDb->sqlExec($sql);

        foreach ((array) $ar_sql as $ar_row) {
            $id_topic = (int) $ar_row['id_topic'];
            $cnt = (int) $ar_row['cnt'];

            $this->ar_items_counted[$id_topic] = $cnt;
        }
    }

    /**
     * Recount totals starting from root topics.
     *
     * @return void
     */
    protected function _recount_root_topics()
    {
        $sql = 'SELECT id_topic
                FROM `' . $this->sys['tbl_prefix'] . 'topics`
                WHERE id_parent = 0';

        $ar_sql = $this->oDb->sqlExec($sql);

        foreach ((array) $ar_sql as $ar_row) {
            $id_topic = (int) $ar_row['id_topic'];
            $visited = [];

            $this->_get_subitems($id_topic, $visited);
        }
    }

    /**
     * Save counted totals to the database.
     *
     * @return void
     */
    protected function _save_totals()
    {
        foreach ($this->ar_sum_totals as $id_topic => $int_items) {
            $this->oDb->sqlExec(
                gw_sql_update(
                    ['int_items' => (int) $int_items],
                    $this->sys['tbl_prefix'] . 'topics',
                    '`id_topic` = ' . (int) $id_topic
                )
            );
        }
    }

    /**
     * Count direct and child items for the specified topic.
     *
     * Protection from cyclic topic links is included.
     *
     * @param int $id_topic
     * @param array $visited
     * @return int
     */
    protected function _get_subitems($id_topic, array &$visited)
    {
        $id_topic = (int) $id_topic;

        if (isset($visited[$id_topic])) {
            return 0;
        }

        $visited[$id_topic] = 1;

        $cnt = isset($this->ar_items_counted[$id_topic])
            ? (int) $this->ar_items_counted[$id_topic]
            : 0;

        if (isset($this->ar_parents[$id_topic]) && is_array($this->ar_parents[$id_topic])) {
            foreach ($this->ar_parents[$id_topic] as $child_topic_id) {
                $cnt += $this->_get_subitems($child_topic_id, $visited);
            }
        }

        $this->ar_sum_totals[$id_topic] = $cnt;

        return $cnt;
    }
}