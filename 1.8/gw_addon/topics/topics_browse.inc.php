<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: topics_browse.inc.php 497 2008-06-14 07:15:56Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* */
$this->str .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
$this->str .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
$this->str .= '<th style="width:1%">N</th>';
$this->str .= '<th>' . $this->oL->m('topic') . '</th>';
$this->str .= '<th style="width:10%">' . $this->oL->m('1335') . '</th>';
$this->str .= '<th style="width:20%">' . $this->oL->m('order') . '</th>';
$this->str .= '<th style="width:22%">' . $this->oL->m('action') . '</th>';
$this->str .= '<th style="width:5%">' . $this->oL->m('1320') . '</th>';
$this->str .= '</tr></thead><tbody>';

/* */
$this->sys['topic_mode'] = 'html';

$this->str .= gw_get_thread_pages($this->gw_this['ar_topics_list']);

$this->str .= '</tbody></table>';
$this->str .= '<br />';

?>