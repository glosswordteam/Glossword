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
 * Easy confirm window constructor.
 *
 * Builds simple confirmation form HTML.
 */
class gwConfirmWindow
{
    /** @var string */
    public $strQuestion = 'Confirm?';
    /** @var string */
    public $strFields = '';
    /** @var string */
    public $inputFieldtype = 'hidden';
    /** @var string */
    public $tAlign = 'center';
    /** @var string */
    public $formwidth = '400';
    /** @var string */
    public $formname = 'post';
    /** @var string */
    public $enctype = 'application/x-www-form-urlencoded';
    /** @var string */
    public $action = 'post.php';
    /** @var string */
    public $submitok = ' Yes ';
    /** @var string */
    public $submitcancel = ' No ';
    /** @var string */
    public $formbgcolor = '#DDD';
    /** @var string */
    public $formbordercolor = '#444';
    /** @var string */
    public $formbordercolorL = '#FFF';
    /** @var string */
    public $css_align_right = 'right';
    /** @var string */
    public $css_align_left = 'left';
    /** @var string */
    public $submitclass = 'submitdel';

    /**
     * Construct <input> tag and append to internal fields buffer.
     *
     * @param string $field_type Field type (hidden|text|submit etc.).
     * @param string $name Field name.
     * @param string $value Field value.
     *
     * @return void
     */
    public function setField($field_type, $name, $value)
    {
        // NOTE: values are assumed to be pre-escaped by caller when needed.
        $this->strFields .=
            '<input type="' . $field_type . '" name="' . $name . '" value="' . $value . '" />';
    }

    /**
     * Set question text for form.
     *
     * @param string $text Question text (raw HTML allowed).
     *
     * @return void
     */
    public function setQuestion($text)
    {
        $this->strQuestion = $text;
    }

    /**
     * Build confirmation window HTML.
     *
     * @return string Full HTML code for form.
     */
    public function Form()
    {
        $html = '';

        $html .= '<div style="text-align:center">';
        $html .= '<form name="' . $this->formname . '" action="' . $this->action . '"';
        $html .= ' enctype="' . $this->enctype . '" method="post" style="margin:0">';

        $html .= '<table width="1%" border="0" cellspacing="1" cellpadding="1"';
        $html .= ' style="margin:0 auto;background:' . $this->formbordercolor . '">';
        $html .= '<tr><td style="background-color:' . $this->formbordercolorL . '">';

        $html .= '<table width="' . $this->formwidth . '" border="0" cellspacing="0" cellpadding="5"';
        $html .= ' style="background:' . $this->formbgcolor . '">';

        $html .= '<tr>';
        $html .= '<td align="' . $this->css_align_left . '" style="background:' . $this->formbgcolor . '">';
        $html .= $this->strQuestion;
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr align="center" style="background-color:' . $this->formbgcolor . '">';
        $html .= '<td>';
        $html .= '<table width="150" border="0" cellpadding="0" id="confirmboxtable"><tr align="center">';
        $html .= '<td width="50%">';
        $html .= '<input class="' . $this->submitclass . '" type="submit" value="' . $this->submitok . '" ';
        $html .= 'onclick="document.all.confirmboxtable.style.visibility=\'hidden\'" />';
        $html .= '</td>';
        $html .= '<td width="50%">';
        $html .= '<input type="reset" value="' . $this->submitcancel . '" class="submitcancel" ';
        $html .= 'onclick="history.back(-1);document.all.confirmboxtable.style.visibility=\'hidden\';" />';
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $this->setField('hidden', 'isConfirm', 1);
        $html .= $this->strFields;

        $html .= '</td></tr></table>';
        $html .= '</form></div>';

        return $html;
    }

    /**
     * Debug helper: return only fields HTML.
     *
     * @return string HTML code only for fields.
     */
    public function FieldsOnly()
    {
        return $this->strFields;
    }
}