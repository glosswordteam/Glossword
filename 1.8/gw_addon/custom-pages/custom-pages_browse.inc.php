<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: custom-pages_browse.inc.php 372 2008-03-27 05:18:49Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


/* Old */
global $topic_mode;

/* */
$this->str .= '<table class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr>';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th>' . $this->oL->m('page') . '</th>';
$this->str .= '<th style="width:20%">' . $this->oL->m('order') . '</th>';
$this->str .= '<th style="width:32%">' . $this->oL->m('action') . '</th>';
$this->str .= '<th style="width:5%">' . $this->oL->m('1320') . '</th>';
$this->str .= '</tr></thead><tbody>';

$topic_mode = 'html';

$this->str .= gw_get_thread_pages($this->ar);

$this->str .= '</tbody></table>';
$this->str .= '<br />';

?>