<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
class gw_setup_intro extends gw_setup
{
	/* */
	function intro_step_0()
	{
		$this->ar_tpl[] = 'i_intro.html';
		$this->oTpl->a( 'v:html_title', sprintf($this->oL->m('1161'), 'Glossword'));
		$this->oTpl->a( 'v:html_descr', $this->oL->m('1163'));
		$this->oTpl->a( 'l:lang', $this->oL->m('lang'));

		$this->oTpl->a( 'href:il_ru', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=ru'));
		$this->oTpl->a( 'href:il_en', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=en'));
		$this->oTpl->a( 'href:il_es', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=es'));
		$this->oTpl->a( 'href:il_du', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=du'));
		$this->oTpl->a( 'href:il_da', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=da'));
		$this->oTpl->a( 'href:il_fr', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=fr'));
		$this->oTpl->a( 'href:il_it', $this->oHtml->url_normalize(THIS_SCRIPT.'?'.GW_LANG_I.'=it'));

		$this->oTpl->a( 'url:setup_new', $this->oHtml->a(THIS_SCRIPT.'?a=install&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], $this->oL->m('1164')));
		// 1.8.1 - 2007-Apr-13
		$this->oTpl->a( 'url:setup_upgrade181', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_1&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.0', '1.8.1')));
		// 1.8.2 - 2007-May-31
		$this->oTpl->a( 'url:setup_upgrade182', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_2&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.1', '1.8.2')));
		// 1.8.3 - 2007-Jul-21
		$this->oTpl->a( 'url:setup_upgrade183', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_3&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.2', '1.8.3')));
		// 1.8.4 - 2007-Aug-23
		$this->oTpl->a( 'url:setup_upgrade184', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_4&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.3', '1.8.4')));
		// 1.8.5 - 2007-Sep-21
		$this->oTpl->a( 'url:setup_upgrade185', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_5&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.4', '1.8.5')));
		// 1.8.6 - 2007-Dec-05
		$this->oTpl->a( 'url:setup_upgrade186', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_6&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.5', '1.8.6')));
		// 1.8.7 - 2008
		$this->oTpl->a( 'url:setup_upgrade187', $this->oHtml->a(THIS_SCRIPT.'?a=upgrade_to_1_8_7&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], sprintf($this->oL->m('1165'), '1.8.6', '1.8.7')));
		$this->oTpl->a( 'url:setup_uninstall', $this->oHtml->a(THIS_SCRIPT.'?a=uninstall&step=1&'.GW_LANG_I.'='.$this->gv[GW_LANG_I], $this->oL->m('1166')));
	}
}
?>