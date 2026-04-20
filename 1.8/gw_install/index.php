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
if (!defined('IS_IN_GW2')) {
    define('IS_IN_GW2', 1);
}
if (!defined('GW2_THIS_SCRIPT')) {
    define('GW2_THIS_SCRIPT', 'index.php');
}

include_once('../inc/functions.php');
include_once('/includes/class.gw_mini_site.php');
include_once('/includes/class.gw_var_store.php');
include_once('/includes/class.gw_mini_timer.php');

define('GW2_TPL_WEB_INDEX', 1);

/* */
error_reporting(E_ALL);

/* */
$o = new gw_mini_site;
/* Register global variables */
$o->global_variables(['arg', 'arp', 'arv']);
$o->init();
$o->page_header();
$o->page_body();
$o->page_footer();

