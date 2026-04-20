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
    die();
}

/* Heading */
$this->oTpl->addVal('v:current_status', $this->oTkit->_(10004));
$this->oHtml->append_html_title($this->oTkit->_(10004));

/* Get installed languages */
$ar_languages = $this->oTkit->get_languages();

$str_il = '';
foreach ($ar_languages as $il => $iname) {
    $selected = '';
    if ($il == 'english') {
        $selected = ' selected="selected"';
    }
    $str_il .= '<option value="' . $il . '"' . $selected . '>' . $iname . '</option>';
}
$this->oTpl->addVal(
    'v:form_select_il',
    '<select size="3" id="arg-il-" name="arg[il]" class="inp">' . $str_il . '</select>'
);

/* */
$this->oTpl->tmp['d']['if:chooselanguage'] = true;
