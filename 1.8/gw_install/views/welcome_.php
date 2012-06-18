<?php
/**
 *  Glossword - Glossary Compiler
 *  © 2008 Glossword.biz team (http://glossword.biz/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 * $Id: welcome_.php 552 2008-08-17 17:40:40Z glossword_team $
 */
if (!defined('IS_IN_GW2')){die();}

/* */
$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(10013) );
$this->oHtml->append_html_title( $this->oTkit->_(10013) );

/* Item: New installation */
$ar_menu = array('.999999' =>
	$this->oHtmlTags->a_href(
		array($this->g('file_index'), 'arg[target]' => 'install', 'arg[il]' => $this->gv['il']), 
		array(), 
		$this->oTkit->_(20002)
	)
);

/* Check available files */
foreach (glob($this->g('path_views').'/update_1*.php') as $filename)
{
	/* Parse filenames */
	$filename = str_replace( array($this->g('path_views').'/', '.php'), '', $filename );
	list($target, $action) = explode("_", $filename);
	list($version_from, $version_to) = explode("-", $action);
	list($version_to1, $version_to2, $version_to3) = sscanf($version_to, "%d.%d.%d");
	/* sprintf() is used for re-ordering */
	/* Item: Update from %1 to %2 */
	$ar_menu[sprintf(".%02d%02d%02d", $version_to1, $version_to2, $version_to3)] = $this->oHtmlTags->a_href(
		array($this->g('file_index'), 'arg[target]' => 'update', 'arg[action]' => $action, 'arg[il]' => $this->gv['il']),
		array(), 
		$this->oTkit->_(20003, '<strong>'.$version_from.'</strong>', '<strong>'.$version_to.'</strong>')
	);
}
krsort($ar_menu);
/* Item: Uninstall */
$ar_menu[] = $this->oHtmlTags->a_href(
		array($this->g('file_index'), 'arg[target]' => 'uninstall', 'arg[il]' => $this->gv['il']), 
		array(), 
		$this->oTkit->_(20004)
);
/* Construct menu */
$this->oTpl->addVal( 'v:select_action', '<ul class="select-action"><li>'.implode('</li><li>', $ar_menu).'</li></ul>' );

$this->oTpl->addVal( 'v:text_before', 
	'<p>'.$this->oTkit->_(20075).'</p>'.'<p>'.$this->oTkit->_(20001).'</p>' 
);

/* */
$this->oTpl->tmp['d']['if:welcome'] = true;

?>