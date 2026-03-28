<?php

if (!defined('IN_GW')) {
    die('<!-- Not in App  -->');
}
/*
	Maintenance task
*/
/* */
include($sys['path_addon'] . '/class.gw_addon.php');

/* */

class gw_addon_recount_dict extends gw_addon
{
    /** Addon name. */
    public $addon_name = 'recount_dict';

    /**
     * Constructor: initializes the addon.
     */
    public function __construct()
    {
        $this->init_m();
    }

    /**
     * Recounts bytes and active terms for all dictionaries.
     * Updates TBL_DICT table with statistics.
     */
    private function _recount()
    {
        $updateQueries = [];
        $dictList = $this->gw_this['ar_dict_list'];

        foreach ($dictList as $dictId => $dictParam) {
            // Calculate total bytes.
            $sql = 'SELECT SUM(int_bytes) AS bytes FROM `' . $dictParam['tablename'] . '`';
            $result = $this->oDb->sqlExec($sql);
            $dictStats = ['int_bytes' => isset($result[0]['bytes']) ? (int) $result[0]['bytes'] : 0];

            // Count active terms (created before now).
            $sql = 'SELECT COUNT(*) AS n FROM `' . $dictParam['tablename'] . '`
                    WHERE `is_active` = "1" AND `date_created` <= ' . (int) $this->sys['time_now_db'];
            $result = $this->oDb->sqlExec($sql);
            $dictStats['int_terms'] = isset($result[0]['n']) ? (int) $result[0]['n'] : 0;

            // Prepare update query (assumes gw_sql_update escapes properly).
            $updateQueries[] = gw_sql_update($dictStats, gw_get_tbl_name('dict'), "id = '" . $dictId . "'");
        }

        // Execute all updates.
        foreach ($updateQueries as $query) {
            $this->oDb->sqlExec($query);
        }
    }

    /**
     * Alpha hook: runs recount with probability check.
     */
    public function alpha()
    {
        if (mt_rand(0, 99) < $this->sys['prbblty_tasks']) {
            $this->_recount();
        }
    }

    /**
     * Omega hook: no-op.
     */
    public function omega()
    {
        // No action needed.
    }
}

// Instantiate and execute.
$addon = new gw_addon_recount_dict();
$addon->alpha();
$addon->omega();
unset($addon);
