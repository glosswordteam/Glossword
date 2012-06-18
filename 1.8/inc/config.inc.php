<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: config.inc.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/* ------------------------------------------------------- */
/**
 *  Configuration scheme:
 *  index -> config.inc -> lib.prepend -> constants.inc -> custom
 *           ^^^^^^^^^^
 */
/* ------------------------------------------------------- */
// Database settings
// see `glossword/db_config.php'
/* ------------------------------------------------------- */
$sys['const_prefix'] = 'GW_';  /* prefix for constant names, do not touch */
// Debug Level
define('GW_DEBUG',           0); /* [ 1 - on | 0 - off ] Debug mode */
define('GW_DEBUG_SQL_TIME',  0); /* displays Total execution time */
define('GW_DEBUG_SQL_QUERY', 0); /* displays SQL-queries */
define('GW_DEBUG_CACHE',     0); /* displays Cache usage */
define('GW_DEBUG_HTTP',      0); /* displays HTTP headers */
/* Switches placed in the first configuration file */
define('GW_PAGE_LINK_ID', 1);   /* id_page = 1 */
define('GW_PAGE_LINK_URI', 2);  /* id_image = one */
define('GW_PAGE_LINK_NAME', 3); /* id_image = %3F%3C */
/* */
define('GW_A_CUSTOMPAGE', 'viewpage');
/* --------------------------------------------------------
 * System settings
 * ----------------------------------------------------- */
## is_check_ip          [ 1 - on | 0 - off ] Set to 0 if you have dynamic IP or use DSL/ADSL connection
## is_cache_sql         [ 1 - on | 0 - off ] Enables SQL-query caching.
## is_cache_http        [ 1 - on | 0 - off ] Enables caching pages for browsers. Reduces the bandwidth up to 70% for second requests.
## is_cache_search      [ 1 - on | 0 - off ] Enables caching for search queries.
## is_use_gzip          [ 1 - on | 0 - off ] Enables compresed output. Reduces the bandwidth up to 80%.
## cache_zlib           [ 1 - on | 0 - off ] Use Zlib compression to store/read cache files. It saves some of space on HDD (Clear cache before to switch)
## cache_lifetime       [ 1440 - day | 10080 - week | 43200 - 30 days ] Cache expires after `n' minutes.
## refreshtime          [ 0-9 ] Time in seconds before redirect (Admin mode)
## path_img             Path to directory where images are stored.
## path_tpl             Path to directory where templates are stored.
## path_admin           Path to login directory.
## leech_factor         [ 1..99 ] Increases amount of pages in `n' times when anti-leecher is turned on.
## filters_output       [ array ] Additional function names to use for HTML-output
## filters_output_defn  [ array ] Additional function names to use for HTML-code of a definition
## gzip_level           [ 1..9 ] 9 is the best compression mode. Requires more CPU than mode 1.
## is_debug_output      [ 1 - on | 0 - off ] Set to 1 to disable all applied filters for HTML-output.
## is_delay_redirect    [ 1 - on | 0 - off ] Pause between redirect. For debug purposes.
## is_tpl_show_names    [ 1 - on | 0 - off ] Show teplate file names. Useful for customizing themes.
## path_temporary       Path to temporary directory. Do not try session_save_path(), because the directory must be available from web.
## path_cache_sql       Path to directory where cached sql-queries are stored.
## path_export          Path to directory where exported files are stored.
## is_LogGzip           [ 1 - on | 0 - off ] Save Gzip optimization results.
## path_logs            Path to directory where log-files are stored.
## path_gwlib           Path to directory where PHP-libraries are stored.
## content_type         [ text/html ] Content-type
## max_char_combobox    [ 0-9 ] Maximum number of characters for element (tag <option>) in combo-box (tag <select>).
## prbblty_tasks        Running probability for maintenance tasks, %. 3% means 3 times per 100 page requests.
## max_terms_in_index   [ 0-9 ] Maximum number of characters terms per a letter for The Contents page.
## max_lines_csv        [ 0-9 ] Maximum number of lines in CSV-file.
## max_terms_search     [ 0-9 ] Maximum number of terms in search results.
## mail_subject_prefix  [ a-z ] A word which will be added as prefix to e-mail subject. For spam filtering.
## max_page_links       [ 0-9 ] The number of links to pages displayed before and after the current page. 1 2 (3) 1 2.
## mod_rewrite_suffix   [ .xhtml ] Extension name for pages.
## mod_rewrite_index    [ index.xhtml ] Filename for homepages (title page, dictionary home page).
## meta_robots          [ index|archive|noindex|noarchive ] Rules for <meta content="" name="robots" />

/* ------------------------------------------------------- */
$sys['is_check_ip']        = 0;
$sys['is_cache_sql']       = 0;
$sys['is_cache_http']      = 0;
$sys['is_cache_search']    = 0;
$sys['is_use_gzip']        = 0;
$sys['gzip_level']         = 6;
$sys['cache_zlib']         = 0;
$sys['cache_lifetime']     = 10080; /* week */
$sys['path_temporary']     = 'gw_temp';
$sys['path_cache_sql']     = $sys['path_temporary'].'/gw_cache_sql';
$sys['path_export']        = $sys['path_temporary'].'/gw_export';
$sys['path_logs']          = $sys['path_temporary'].'/gw_logs';
$sys['refreshtime']        = 2;
$sys['leech_factor']       = 2;
$sys['is_tpl_show_names']  = 0;
$sys['is_delay_redirect']  = 0;
$sys['filters_output']     = array('gw_text_smooth');
$sys['filters_defn']       = array('gw_text_smooth_defn');
$sys['is_debug_output']    = 0;
$sys['ar_url_append']      = array();
$sys['content_type']       = 'text/html';
$sys['max_char_combobox']  = 45;
$sys['prbblty_tasks']      = 3;
$sys['max_terms_in_index'] = 200;
$sys['max_lines_csv']      = 10000;
$sys['max_terms_search']   = 1000;
$sys['max_page_links']     = 3;
$sys['mod_rewrite_suffix'] = '.xhtml';
$sys['mod_rewrite_index']  = 'index.xhtml';
$sys['meta_robots']        = 'index,follow,archive';

$sys['int_jpeg_compression'] = 51;
$sys['is_ext_fields'] = 1;
$sys['id_custom_page_on'] = 5;

/* PHP variables */
$sys['internal_encoding'] = 'UTF-8';

/* I request you to retain the copyright notice! Ask for copyright removal. */
$sys['str_branding'] = 'Powered&#160;by <a href="http://glossword.biz/" onclick="window.open(this);return false" title="Freeware dictionary/glossary PHP-script">Glossword</a>&#160;';

include_once( $sys['path_gwlib'] .'/class.func.php' );
/* ------------------------------------------------------- */
/* Autoexec */
if (isset($sys['is_prepend']) && $sys['is_prepend'])
{
	include_once( $sys['path_include'] . '/lib.prepend.php');
}
$sys['file_lock']   = 'gw_temp/gw_install.lock';

/* end of file */
?>