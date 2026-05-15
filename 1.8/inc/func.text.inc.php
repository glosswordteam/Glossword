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
 *  Functions for an HTML-code and text operations.
 */

/**
 * Constructs HTML for Virtual keyboard
 *
 * @required $oDb, $oSqlQ, $oL, $ar_theme
 * @param int $id_profile Virtual keyboard Profile
 * @param int $id_dict Dictionary ID.
 */
function gw_get_virtual_keyboard($id_profile = false, $id_dict = false)
{
    global $oDb, $oSqlQ, $oL, $ar_theme;

    $str        = '';
    $ar_letters = [];

    if ($id_dict && $id_profile) {
        /* Per dictionary */
        $arSql = $oDb->sqlRun($oSqlQ->getQ('get-vkbd-profile', $id_profile), $id_dict);
    } else {
        /* Default Virtual keyboard */
        $arSql = $oDb->sqlRun($oSqlQ->getQ('get-vkbd-default'), 'st');
    }

    foreach ($arSql as $arV) {
        $ar_letters = explode(',', $arV['vkbd_letters']);
    }

    if (!empty($ar_letters)) {
        array_walk($ar_letters, function (&$v) {
            $v = trim(addslashes($v));
        });

        /* "Virtual keyboard" button */
        $str .= '<a title="' . $oL->m('virtual_keyboard') . '" id="gwkbdcall" onclick="';
        $str .= 'return gwJS.showKbd(\'gw\', ['; // <form id="gw"><input id="gwq">
        $str .= "'" . implode('\',\'', $ar_letters) . "'";
        $str .= ']);"';
        $str .= ' class="plain">' . $ar_theme['txt_virtual_keyboard'] . '</a>';
        $str .= '<table style="position:absolute;top:-10;visibility:hidden" id="gwkbd" cellspacing="0"><tbody><tr><td></td></tr></tbody></table>';
    }

    return $str;
}

/* */
function gw_get_note_afterpost($text, $status = 0)
{
    $text = '<span class="' . ($status == true ? "green" : "red") . '">' . $text . '</span>';
    return '<div class="note-afterpost" id="note-afterpost">' .
        '<a href="#" onclick="gw_getElementById(\'note-afterpost\').style.display=\'none\';return false" style="font-size:120%;padding:0 5px;display:block;float:right">×</a>' .
        '<script type="text/javascript">/*<![CDATA[*/gwJS.FXfadeOpac(\'note-afterpost\');/*]]>*/</script>' .
        $text . '</div>';
}

/* */

class gw_restore_quotes
{
    /** @var int */
    public $is_strip_tags = 0;

    /** @var string */
    public $rule_proto;

    /** @var string */
    public $rule_ahref;

    /** @var string */
    public $rule_atext;

    /** @var string */
    public $rule_abracket;

    /** @var string */
    public $rule_attr;

    /** @var string */
    public $rule_spaces;

    /** @var string */
    public $rule_attr_regex;

    /* */
    public function init()
    {
        $this->is_strip_tags = 1;
        $this->rule_proto    = 'http:\/\/|https:\/\/';
        $this->rule_ahref    = '[^][<>"\\x00-\\x20\\x7F]';
        $this->rule_atext    = '[^\]\\x0a\\x0d]';
        $this->rule_abracket = '/\[(\b(' . ('http\:\/\/|https\:\/\/|ftp\:\/\/|mailto\:|news\:') . ')' . $this->rule_ahref . '+) *(' . $this->rule_atext . '*?)\]/S';
        $this->rule_attr     = '[A-Za-z0-9\:\-]';
        $this->rule_spaces   = '[\x09\x0a\x0d\x20]';
        /* */
        $this->rule_attr_regex = '/(?:^|' . $this->rule_spaces . ')(' . $this->rule_attr . '+)' .
            '(' . $this->rule_spaces . '*=)(.*?[\'"])(?=' . $this->rule_spaces . '|$)/xs';
    }

    /* */
    public function parse($s)
    {
        if ($this->is_strip_tags) {
            $s = $this->strip_tags($s);
        }
        $s = $this->_proc($s);
        return $s;
    }

    /* */
    private function _proc($s)
    {
        $ar = explode('<', $s);
        $s  = str_replace('>', '&gt;', array_shift($ar));
        foreach ($ar as $k) {
            $regs = [];
            preg_match('/^(\\/?)([\w!\?]+)([^>]*?)(\/{0,1}>)([^<]*)$/', $k, $regs);
            @list(/* $qbar */, $slash, $tag, $attrlist, $brace, $rest) = $regs;
            $newattrlist = $this->_attr_fix($attrlist, $tag);
            $rest        = str_replace('>', '&gt;', $rest);
            /* add space for non-pair XHTML tags */
            if ($brace == '/>') {
                $brace = ' ' . $brace;
            }
            $s .= '<' . $slash . $tag . $newattrlist . $brace . $rest;
        }
        /* fix for XHTML single tags */
        $s = str_replace('} />', '}/>', $s);
        return $s;
    }

    /* */
    private function _attr_fix($str_attr, $tag)
    {
        if (trim($tag) == '') {
            return '';
        }
        $str_attr = preg_replace('/' . $this->rule_spaces . '+/', ' ', $str_attr);
        $str_attr = str_replace('&quot;', '"', $str_attr);
        $str_attr = str_replace('&#039;', '\'', $str_attr);
        /* keep untouched < ?xml ... ? >, < !DOCTYPE ... >, and comments */
        if ($tag == '?xml' || $tag == '!DOCTYPE' || $tag == '!') {
            return $str_attr;
        }
        /* keep HTML-variables in attributes */
        if (preg_match('/' . $this->rule_spaces . '{([a-zA-Z_\:\-]+)}/', $str_attr)) {
            return $str_attr;
        }
        $ar_attr    = $this->_attr_expand($str_attr);
        $ar_newattr = [];
        foreach ($ar_attr as $attr => $val) {
            if ($attr == 'a') {
                $ar_newattr[] = $val;
            } else {
                $ar_newattr[] = $attr . '="' . $val . '"';
            }
        }
        return sizeof($ar_newattr) ? ' ' . implode(' ', $ar_newattr) : '';
    }

    /* */
    private function _attr_expand($str_attr)
    {
        $ar_pairs = $ar = [];
        if (trim($str_attr) == '') {
            return $ar;
        }
        /* */
        if (!preg_match_all($this->rule_attr_regex, $str_attr, $ar_pairs, PREG_SET_ORDER)) {
            return $ar;
        }
        foreach ($ar_pairs as $v) {
            $attr    = strtolower($v[1]);
            $val     = trim($v[3]);
            $val     = preg_replace('/(^["\'])/', "", $val);
            $val     = preg_replace('/(["\']$)/', "", $val);
            $onclick = '';
            /* Fix target="_blank" */
            if ($attr == 'target') {
                $val  = 'window.open(this);return false;';
                $attr = 'onclick';
            }
            $val = str_replace('"', '&quot;', $val);
            /* fix repeated attributes */
            if (isset($ar[$attr])) {
                $ar[$attr] .= $val;
            } else {
                $ar[$attr] = $val;
            }
        }
        ksort($ar);
        return $ar;
    }

    /* */
    public function strip_tags($s)
    {
        return strip_tags(
            $s,
            '<nowiki><stress><xref><abbr><acronym><address><blockquote><br><cite><code><dfn><div><em><h1><h2><h3><h4><h5><h6><kbd><p><pre><q><samp><span><strong><var><a><dl><dt><dd><ol><ul><li><object><param><b><big><hr><i><small><sub><sup><tt><del><ins><bdo><button><fieldset><form><input><label><legend><select><optgroup><option><textarea><caption><col><colgroup><table><tbody><td><tfoot><th><thead><tr><img><area><map><noscript><script><style>'
        );
    }
}

$gw_oW = new gw_restore_quotes;
$gw_oW->init();


/**
 * Encode protected tag body with Base64.
 *
 * @param array $matches
 *
 * @return string
 */
function gw_fix_input_to_db_encode_tag_body(array $matches)
{
    return $matches[1] . base64_encode($matches[2]) . $matches[3];
}

/**
 * Decode protected tag body from Base64.
 *
 * @param array $matches
 *
 * @return string
 */
function gw_fix_input_to_db_decode_tag_body(array $matches)
{
    return $matches[1] . base64_decode($matches[2]) . $matches[3];
}

/**
 * Prepare page content before saving to database.
 *
 * Protects script and nowiki blocks from parser modifications,
 * normalizes legacy tags and fixes HTML entities.
 *
 * @param string $text
 *
 * @return string
 */
function gw_fix_input_to_db($text)
{
    global $gw_oW;

    $text = (string)$text;

    if ($text === ' ') {
        $text = '&#032;';
    }

    /* Convert {%v:path_img%} => {v:path_img} */
    $text = str_replace(['{%', '%}'], ['{', '}'], $text);

    /* Protect contents of <script> from parser changes */
    $text = preg_replace_callback(
        '/(<script\b[^>]*>)(.*?)(<\/script>)/is',
        'gw_fix_input_to_db_encode_tag_body',
        $text
    );

    /* Protect contents of <nowiki> from parser changes */
    $text = preg_replace_callback(
        '/(<nowiki>)(.*?)(<\/nowiki>)/is',
        'gw_fix_input_to_db_encode_tag_body',
        $text
    );

    /* Replace old tags (HTML 4.01) with new ones */
    $text = gw_fix_tagnames(trim($text));

    /* Convert &amp;&"\' => &amp;&amp;&quot;&#039; */
    $text = gw_htmlspecials_amp(gw_unhtmlspecials_amp($text));

    /* Fix attributes, for example: title=&quot;123&quot;&quot; => title="123&quot;" */
    $text = $gw_oW->parse($text);

    /* Restore contents of <script> */
    $text = preg_replace_callback(
        '/(<script\b[^>]*>)(.*?)(<\/script>)/is',
        'gw_fix_input_to_db_decode_tag_body',
        $text
    );

    /* Restore contents of <nowiki> */
    $text = preg_replace_callback(
        '/(<nowiki>)(.*?)(<\/nowiki>)/is',
        'gw_fix_input_to_db_decode_tag_body',
        $text
    );

    return $text;
}

/* */
function gw_fix_db_to_field($s)
{
    if (is_string($s)) {
        $s = preg_replace('/{(d [0-9a-zA-Z_\:\-]+|[0-9a-zA-Z_\:\-\/]+)}/', '{%\\1%}', $s);
        $s = gw_unhtmlspecials_amp($s);
        $s = str_replace('&', '&amp;', $s);
        $s = str_replace('"', '&quot;', $s);
        $s = str_replace('<', '&lt;', $s);
        $s = str_replace('>', '&gt;', $s);
    }
    return $s;
}

/* */
function gethtml_metarefresh($url, $time_refresh = 5)
{
    return '<meta http-equiv="Refresh" content="' . $time_refresh . ';url=' . $url . '" />';
}

/* */
function gw_bbcode_htmlspecialchars($t)
{
    global $sys;
    $t = stripslashes($t);
    $t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    return $t;
}

/**
 * Converts BBCode-safe HTML fragments.
 *
 * @param string $text
 * @return string
 */
function gw_bbcode_html($text)
{
    $text = str_replace('&amp;', '&', $text);
    $text = str_replace('&quot;', '"', $text);
    $text = str_replace('&039;', '\'', $text);

    $text = preg_replace_callback(
        '/\&lt;(.+)\&gt;/isU',
        'gw_bbcode_html_replace_tag_callback',
        $text
    );

    // $text = str_replace('&#', '&amp;#', $text);
    $text = str_replace('![cdata', '![CDATA', $text);
    $text = str_replace('!doctype', '!DOCTYPE', $text);
    $text = str_replace("\t", '&#160;&#160;&#160;&#160;&#160;&#160;&#160;', $text);
    $text = str_replace("\n", '<br />', $text);
    $text = str_replace('  ', '&#160;&#160;', $text);

    return $text;
}

/**
 * Callback for escaped HTML-like tags.
 *
 * @param array $matches
 * @return string
 */
function gw_bbcode_html_replace_tag_callback(array $matches)
{
    return gw_bbcode_html_tag(
        gw_bbcode_htmlspecialchars($matches[1])
    );
}

/**
 * Formats escaped HTML-like tag for BBCode preview.
 *
 * @param string $t
 *
 * @return string
 */
function gw_bbcode_html_tag($t)
{
    $slash_s  = $slash_e = '';
    $spacepos = strpos($t, ' ');
    $attr     = '';
    $l        = strlen($t);
    if ($t !== '' && $t[0] == '/') {
        $slash_s = '/';
        $t       = substr($t, 1);
    }
    if (substr($t, -1) == '/') {
        $slash_e = '/';
        $t       = substr($t, 0, $l - 1);
    }
    if ($spacepos != false) {
        $attr = substr($t, $spacepos);
        $t    = substr($t, 0, $spacepos);
        $attr = preg_replace(
            '# ([a-z-:]+)=&quot;(.*)&quot;#siU',
            ' <span style="color:#C00">\1</span>=<span style="color:#C6C">&quot;\2&quot;</span>',
            $attr
        );
    }
    $t = strtolower($t);
    switch ($t) {
        case 'form';
        case 'input':
        case 'select':
        case 'option':
        case 'textarea':
        case 'label':
        case 'fieldset':
        case 'legend':
        case 'meta':
        case 'head':
        case 'link':
        case 'body':
        case 'html':
        case 'img':
        case 'script':
            $c = '#880';
            break;
        default:
            $c = '#808';
            break;
    }
    $t = '<span style="color:' . $c . '">' . $t . '</span>';
    $t = '<span style="color:#00F">&lt;' . $slash_s . '</span>' . $t . $attr . '<span style="color:#00F">' . $slash_e . '&gt;</span>';
    return $t;
}

/**
 * Draws progressbar in HTML+CSS
 *
 * @param int $percent
 * @param text $color_txt Progress bar text color
 * @param text $color_bg Progress bar Background color
 * @return    text    HTML-code
 */
function text_progressbar($percent = 100, $color_txt = '#000', $color_bg = '#6C3')
{
    return '<div style="text-align:center;background:#F6F6F6;margin:5px 0;width:100%;border:1px solid #CCC"><div style="font:90% sans-serif;color:' . $color_txt . ';background:' . $color_bg . ';width:' . $percent . '%">' . $percent . '%</div></div>';
}

/**
 * Converts string into decimal equivalent.
 *
 * @param string $s
 * @return    integer    A decimal numbers.
 */
function text_str2ord($s)
{
    $int_len = strlen($s);
    $t       = '';
    for ($i = 0; $i < $int_len; $i++) {
        $t .= ord(substr($s, $i, 1));
    }
    return $t;
}

/**
 * Converts string into hexademical.
 *
 * @param string $s
 * @return    integer    A decimal number.
 */
function text_hex2bin($s)
{
    return pack("H" . strlen($s), $s);
}

/* */
function gw_text_parse_href($t)
{
    /* */
    $t = str_replace('<![CDATA[', '', str_replace(']]>', '', $t));
    /* */
    $t = strip_tags($t);
    return $t;
}

/* */
function gw_text_parse_preview($t)
{
    /* */
#	$t = preg_replace("/(\r\n|\r|\n)/", ' ', $t);
    /* */
    $t = str_replace('><', '> <', $t);
    /* */
    $t = str_replace('<![CDATA[', '', str_replace(']]>', '', $t));
    /* */
    $t = strip_tags($t);
    /* removes `{TEMPLATES}' and `{TEMPLATES}:' */
    $t = preg_replace("/\{([A-Za-z0-9:\-_]+)\}([:])*/", ' ', $t);
    /* remove &#nnn(nn) */
#	$t = preg_replace('/&#[x0-9a-f]+;/', ' ', $t);
#	$t = preg_replace('/&[a-z]+;/', ' ', $t);
    return $t;
}

/**
 * Build table header for HTML form blocks.
 *
 * Title is displayed in the left cell.
 * Optional navigation HTML is displayed in the right cell.
 *
 * @param string $title Header text or trusted HTML.
 * @param string $funcnav Optional action HTML.
 *
 * @return string
 */
function gw_get_form_title_nav($title = 'title', $funcnav = '')
{
    global $sys;

    $align_left  = isset($sys['css_align_left']) ? $sys['css_align_left'] : 'left';
    $align_right = isset($sys['css_align_right']) ? $sys['css_align_right'] : 'right';

    $html = '';
    $html .= '<table cellspacing="0" cellpadding="2" border="0" width="100%">';
    $html .= '<tbody><tr class="xmtitle">';

    if ($funcnav !== '') {
        $html .= '<td style="width:50%;text-align:' . $align_left . '">' . $title . '</td>';
        $html .= '<td style="text-align:' . $align_right . '">' . $funcnav . '</td>';
    } else {
        $html .= '<td style="text-align:' . $align_left . '">' . $title . '</td>';
    }

    $html .= '</tr>';
    $html .= '</tbody></table>';

    return $html;
}

/**
 * Build HTML link for an automatically detected URL.
 *
 * Callback for preg_replace_callback().
 *
 * Match indexes:
 * - 1: prefix before URL
 * - 2: full URL
 * - 3: URL scheme
 *
 * @param array $matches Regex matches.
 *
 * @return string
 */
function gw_regex_url($matches)
{
    $scheme_pattern = '(http|https|ftp|news|aim|callto|ed2k)';
    $is_chunk_url   = 0;

    $link_start = isset($matches[1]) ? (string)$matches[1] : '';
    $link_html  = isset($matches[2]) ? (string)$matches[2] : '';
    $link_show  = $link_html;
    $link_end   = '';

    /* Move ending punctuation outside the link */
    if (preg_match('/([\.,\?]|&#33;)$/', $link_html, $match)) {
        $link_end  = $match[1];
        $link_html = preg_replace('/([\.,\?]|&#33;)$/', '', $link_html);
        $link_show = preg_replace('/([\.,\?]|&#33;)$/', '', $link_show);
    }

    /* Do not parse closing BBCode-like tags as links */
    if (preg_match('/\[<\/(html|quote|code|sql)/i', $link_html)) {
        return $link_start . $link_html . $link_end;
    }

    /* Normalize brackets and ampersands in href */
    $link_html = str_replace('&amp;', '&', $link_html);
    $link_html = str_replace('[', '%5B', $link_html);
    $link_html = str_replace(']', '%5D', $link_html);
    $link_html = str_replace('&', '&amp;', $link_html);

    /* Block javascript pseudo-protocol */
    $link_html = preg_replace('/javascript:/i', 'java script&#58; ', $link_html);

    /* Add default scheme if it is missing */
    if (!preg_match('/^' . $scheme_pattern . ':\/\//i', $link_html)) {
        $link_html = 'http://' . $link_html;
    }

    /* Normalize visible URL text */
    $link_show = str_replace('&amp;', '&', $link_show);
    $link_show = str_replace('&', '&amp;', $link_show);
    $link_show = preg_replace('/javascript:/i', 'javascript&#58; ', $link_show);

    /* Used for title attribute */
    $stripped_url = preg_replace(
        '/^' . $scheme_pattern . ':\/\/(\S+)$/i',
        '\\2',
        $link_show
    );

    if (strlen($stripped_url) > 40) {
        $is_chunk_url = 1;
    }

    if (!preg_match('/^' . $scheme_pattern . ':\/\//i', $link_show)) {
        $is_chunk_url = 1;
    }

    $link_text = $link_show;

    /* Shorten long URLs */
    if ($is_chunk_url) {
        $uri_type  = preg_replace(
            '/^' . $scheme_pattern . ':\/\/(\S+)$/i',
            '\\1',
            $link_show
        );
        $link_text = $uri_type . '://' . substr($stripped_url, 0, 25) . '&#8230;' . substr($stripped_url, -15);
    }

    /* Minimal attribute-safe filtering */
    $link_html  = str_replace(['"', '\'', '<', '>'], ['%22', '%27', '', ''], $link_html);
    $link_title = htmlspecialchars(rtrim($stripped_url, '/'), ENT_QUOTES, 'UTF-8');

    return $link_start
        . '<a class="ext" href="' . $link_html . '" onclick="window.open(this.href);return false" title="' . $link_title . '">'
        . $link_text
        . '</a>'
        . $link_end;
}

/* Automatically parse URLs */
function gw_regex_url2($url)
{
    $is_skip    = 0;
    $url['end'] = '';
    /* Fix punctuation */
    if (preg_match("/([\.,\?]|&#33;)$/", $url['html'], $match)) {
        $url['end']  .= $match[1];
        $url['html'] = preg_replace("/([\.,\?]|&#33;)$/", "", $url['html']);
        $url['show'] = preg_replace("/([\.,\?]|&#33;)$/", "", $url['show']);
    }
    /* Fix closing tag */
    if (preg_match("/\[<\/(html|quote|code|sql)/i", $url['html'])) {
        return $url['html'];
    }
    /* Fix ampersands and brackets */
    $url['html'] = str_replace('&amp;', '&', $url['html']);
    $url['html'] = str_replace('[', '%5b', $url['html']);
    $url['html'] = str_replace(']', '%5d', $url['html']);
    $url['html'] = str_replace('&', '&amp;', $url['html']);
    /* No Javascript */
    $url['html'] = preg_replace("/javascript:/i", 'java script&#58; ', $url['html']);
    /* http in front */
    if (!preg_match("/^(http|news|https|ftp|aim|callto|e2dk):\/\//", $url['html'])) {
        $url['html'] = 'http://' . $url['html'];
    }
    /* Fix ampersands */
    $url['show'] = str_replace('&amp;', '&', $url['show']);
    $url['show'] = str_replace('&', '&amp;', $url['show']);
    $url['show'] = preg_replace("/javascript:/i", "javascript&#58; ", $url['show']);
    /* Used for title="" also */
    $stripped = preg_replace("/^(http|ftp|https|news|aim|callto|e2dk):\/\/(\S+)$/i", "\\2", $url['show']);
    /* Chunk long URLs */
    if (strlen($stripped) > 40) {
        $is_skip = 1;
    }
    if (!preg_match("/^(http|ftp|https|news|aim|callto|e2dk):\/\//i", $url['show'])) {
        $is_skip = 1;
    }
    $str_show = $url['show'];
    if ($is_skip) {
        $uri_type = preg_replace("/^(http|ftp|https|news|aim|callto|e2dk):\/\/(\S+)$/i", "\\1", $url['show']);
        $str_show = $uri_type . '://' . substr($stripped, 0, 25) . '&#8230;' . substr($stripped, -15);
    }
    return $url['st'] . '<a class="ext" href="' . $url['html'] . '" onclick="window.open(this);return false" title="' . rtrim(
            $stripped,
            '/'
        ) . '">' . $str_show . '</a>' . $url['end'];
}

/* Function to highlight words inside a text */
function text_highlight($t, $q, $encoding = 'UTF-8')
{
#@header("content-type: text/html; charset=utf-8");
    $t = strip_tags($t);
    if ($q == '') {
        return $t;
    }

    $classname = 'highlight';

    /* #122 */
    $strong_start = '\x00';
    $strong_end   = '\x01';

    $is_center = $is_left = 0;
    $is_left   = preg_match("/^\*/", $q);
    $is_right  = preg_match("/\*$/", $q);
    $is_both   = preg_match("/^\*(.*?)\*$/", $q);
    $is_center = (!$is_left && !$is_right) ? 1 : 0;
    $is_both   = (!$is_center && preg_match("/\?/", $q)) ? 1 : $is_both;

    $q        = str_replace("*", ' ', $q);
    $q        = str_replace("?", ' ', $q);
    $ar_words = explode(' ', $q);

    foreach ($ar_words as $k => $v) {
        if ($v == '') {
            continue;
        }
        $v = str_replace("/", ' ', $v);
        $v = htmlspecialchars($v);
        /* */
        if ($is_both) {
            $t = preg_replace('#(' . preg_quote($v, '/') . ')#i', $strong_start . '\\1' . $strong_end, $t);
        } elseif ($is_center) {
            $t = preg_replace(
                '#(^|[\x09-\xff])(' . preg_quote($v, '/') . ')([\x09-\xff]|$)#iu',
                '\\1' . $strong_start . '\\2' . $strong_end . '\\3',
                $t
            );
        } elseif ($is_right) {
            $t = preg_replace(
                '#(^|[\x09-\xff])(' . preg_quote($v, '/') . ')#iu',
                '\\1' . $strong_start . '\\2' . $strong_end,
                $t
            );
        } elseif ($is_left) {
            $t = preg_replace(
                '#(' . preg_quote($v, '/') . ')([\x09-\xff]|$)#iu',
                $strong_start . '\\1' . $strong_end,
                $t
            );
        } else {
            $t = preg_replace('#(' . preg_quote($v, '/') . ')#i', $strong_start . '\\1' . $strong_end, $t);
        }
    }
    /* fix &#xn<span class="highlight">n</span>nn; */
    preg_match_all('/&(#)?([0-9a-z="<>\/ ]+);/u', $t, $ar);
    foreach ($ar[0] as $k => $v) {
        $t = str_replace($v, strip_tags($v), $t);
    }
    $t = str_replace($strong_start, '<strong class="' . $classname . '">', $t);
    $t = str_replace($strong_end, '</strong>', $t);

#	prn_r( $ar );
#	prn_r( $t, __LINE__ );
    return $t;
}

/* Clear value for a specified key, recursive */
function array_clear_key($ar, $key_value)
{
    if (!is_array($ar)) {
        return $ar;
    }
    foreach ($ar as $k => $v) {
        if (is_array($v)) {
            $ar[$k] = array_clear_key($v, $key_value);
        } else {
            if (isset($ar[$key_value])) {
                $ar[$k] = '';
            } else {
                $ar[$k] = $v;
            }
        }
    }
    return $ar;
}



/**
 * Convert user wildcards to SQL LIKE masks or remove them.
 *
 * In SQL mode:
 * - "*" becomes "%"
 * - "?" becomes "_"
 *
 * In non-SQL mode wildcard characters are removed.
 *
 * @param string|array $text
 * @param string $mode
 *
 * @return string|array
 */
function gw_text_wildcards($text = '', $mode = 'none')
{
    $search = ['*', '?'];
    $replace = ($mode === 'sql') ? ['%', '_'] : ['', ''];

    if (is_array($text)) {
        foreach ($text as $key => $value) {
            $text[$key] = str_replace($search, $replace, (string) $value);
        }

        return $text;
    }

    return str_replace($search, $replace, (string) $text);
}


/**
 * Generate pseudo-random readable string using a limited character set.
 *
 * This function is suitable for friendly identifiers.
 * Do not use it for passwords, reset tokens or session secrets.
 *
 * @param string $prefix Prefix for returned string
 * @param int $max_char Maximum returned string length
 * @param int $set Character set:
 *                 0 - all sets,
 *                 1 - lowercase,
 *                 2 - uppercase,
 *                 3 - digits
 *
 * @return string
 */
function gw_make_uid($prefix = '', $max_char = 8, $set = 0)
{
    $prefix   = (string)$prefix;
    $max_char = (int)$max_char;
    $set      = (int)$set;

    if ($max_char <= 0) {
        return '';
    }

    /*
     * Exclude ambiguous symbols.
     * Group 1 excludes: 0, 1, l, I, Y, V, y, v
     * Group 2 excludes: a, c, e, o, p, x, A, C, E, H, O, K, M, P, X
     */
    $char_sets = [
        1 => 'bdfghijkmnqrstuwz',
        2 => 'QWRUSDFGJLZN',
        3 => '23456789',
    ];

    $char_pool = isset($char_sets[$set]) ? $char_sets[$set] : implode('', $char_sets);
    $pool_len  = strlen($char_pool);

    if ($pool_len <= 0) {
        return substr($prefix, 0, $max_char);
    }

    if (strlen($prefix) >= $max_char) {
        return substr($prefix, 0, $max_char);
    }

    $result     = $prefix;
    $random_len = $max_char - strlen($prefix);

    for ($i = 0; $i < $random_len; $i++) {
        $char_index = mt_rand(0, $pool_len - 1);
        $result     .= $char_pool[$char_index];
    }

    return $result;
}


/**
 * Insert value into array after the specified offset.
 *
 * Works on the first array level only.
 *
 * @param array $ar
 * @param int $k
 * @param mixed $v
 *
 * @return bool|null
 */
function gw_array_insert(&$ar, $k, $v)
{
    if (!is_array($ar)) {
        return false;
    }

    $offset = ((int) $k) + 1;

    $ar = array_merge(
        array_slice($ar, 0, $offset),
        [$offset => $v],
        array_slice($ar, $offset)
    );

    return null;
}

/**
 * Convert array values into a delimited string.
 *
 * If a string is passed instead of an array, it is normalized by
 * splitting and joining with the same delimiter.
 *
 * @param array|string $ar
 * @param string $delimiter
 *
 * @return string
 */
function gw_array2str($ar, $delimiter = "\n")
{
    $items = [];

    if (is_array($ar)) {
        $items = array_values($ar);
    } else {
        $items = explode($delimiter, (string) $ar);
    }

    return implode($delimiter, $items);
}

/**
 * Return array key by value.
 *
 * If the value is not found, the original value is returned.
 *
 * @param array $ar
 * @param string $str
 *
 * @return mixed
 */
function gw_array_value(array $ar, $str)
{
    $key = array_search($str, $ar);

    if ($key === false) {
        return $str;
    }

    return $key;
}

/**
 * Exclude arrays. Target subtracts from Source.
 *
 * @param array $arA Source array
 * @param array $arB Target array
 * @return  array Result
 */
function gw_array_exclude($arA, $arB)
{
    if (empty($arB)) {
        return $arA;
    } // 15 Dec 2002
    $arC = array_diff($arA, $arB);
    $arC = array_intersect($arC, $arA);
    return $arC;
}

/**
 * Replace legacy HTML 4.01 tags with XHTML 1.1 compatible tags.
 *
 * @param string $text
 *
 * @return string
 */
function gw_fix_tagnames($text)
{
    $from = [
        '<br>',
        '<i>',
        '</i>',
        '<b>',
        '</b>',
        '<strike>',
        '</strike>',
        '<u>',
        '</u>',
        '<center>',
        '</center>',
    ];

    $to = [
        '<br />',
        '<em>',
        '</em>',
        '<strong>',
        '</strong>',
        '<span class="strike">',
        '</span>',
        '<span class="underline">',
        '</span>',
        '<div style="text-align:center">',
        '</div>',
    ];

    return str_replace($from, $to, (string)$text);
}


/**
 * Split text into unique keywords.
 *
 * Keywords are separated by a space character only.
 *
 * @param string $text
 * @param int $min_length
 * @param int $max_length
 * @param string $encoding
 *
 * @return array
 */
function text2keywords($text, $min_length = 1, $max_length = 25, $encoding = 'UTF-8')
{
    if ((int) $min_length === 0) {
        return [];
    }

    $keywords = [];
    $text = (string) $text . ' ';
    $str_temp = ' ';

    preg_match_all('/./u', $text, $ar_letters);

    foreach ($ar_letters[0] as $letter) {
        $str_temp .= $letter;

        if ($letter == ' ') {
            $word = trim($str_temp);
            $mb_len = mb_strlen($word, $encoding);

            if (($mb_len >= (int) $min_length) && ($mb_len < (int) $max_length)) {
                $keywords[] = $word;
            }

            $str_temp = '';
        }
    }

    return array_values(array_unique($keywords));
}


/**
 * Extract keywords from text and calculate unsigned CRC32 for each word.
 *
 * Result is grouped by normalized word length:
 * - words with length 1..2 are stored in group 2
 * - words with length 13..16 are stored in group 16
 * - words with length 17..32 are stored in group 32
 * - other words are stored in their exact length group
 *
 * @param string $text
 * @param int $min_length
 * @param int $max_length
 * @param string $encoding
 *
 * @return array
 */
function text2keywords_crc($text, $min_length = 1, $max_length = 25, $encoding = 'UTF-8')
{
    if ((int)$min_length === 0) {
        return [];
    }

    $result = [];
    $text   = (string)$text . ' ';
    $buffer = ' ';

    preg_match_all('/./u', $text, $matches);

    foreach ($matches[0] as $char) {
        $buffer .= $char;

        if ($char !== ' ') {
            continue;
        }

        $word        = trim($buffer);
        $word_length = mb_strlen($word, $encoding);

        if ($word_length >= (int)$min_length && $word_length <= (int)$max_length) {
            $length_group = $word_length;

            if ($word_length <= 2) {
                $length_group = 2;
            } elseif ($word_length > 12 && $word_length <= 16) {
                $length_group = 16;
            } elseif ($word_length > 16 && $word_length <= 32) {
                $length_group = 32;
            }

            $result[$length_group][] = sprintf('%u', crc32($word));
        }

        $buffer = '';
    }

    foreach ($result as $length_group => $values) {
        $result[$length_group] = array_values(array_unique($values));
    }

    return $result;
}

/**
 * Prepares text for a TERM field
 */
function text_normalize($t)
{
    $t = str_replace(['<![CDATA', ']]>'], ' ', $t);
    global $oCase;
    return $oCase->nc($oCase->rm_($t));
}

/**
 *
 */
function gw_html_block_small($title = '', $content = '', $classN = 0, $alignT = 'left', $alignN = 'left')
{
    global $sys, $gw_this;
    // 25 Jun 2006: no content to display
    if ($content == '') {
        return;
    }
    $classN = ($classN != '0') ? (' class="' . $classN . '"') : false;
    $alignT = ($alignT != '0') ? ('text-align:' . $alignT) : 'text-align:' . $sys['css_align_left'];
    // 12 december 2002, rtl
    $alignN                = ($alignN == 'center') ? $alignN : (($alignN == 'left') ? $sys['css_align_left'] : 'right');
    $str                   = '';
    $tmp['style_cont_str'] = '';
    if ($alignN != '') {
        $tmp['style_cont']     = ['text-align:' . $alignN];
        $tmp['style_cont_str'] = ' style="' . implode(';', $tmp['style_cont']) . '"';
    }
    /* 2 feb 2006 */
    $oTpl = new $sys['class_tpl'];
    $oTpl->init($gw_this['vars']['visualtheme']);
    $oTpl->set_tpl('tpl_smallblock');
    if (isset($sys['path_www_images'])) {
        $oTpl->addVal('v:path_img_www', $sys['dirname'] . '/' . $sys['path_www_images']);
    }
    $oTpl->addVal('v:path_img', $sys['server_dir'] . '/' . $sys['path_img']);
    $oTpl->addVal('v:head-text-align', $alignT);
    $oTpl->addVal('v:head-attr', $classN . $tmp['style_cont_str']);
    $oTpl->addVal('v:head-content', $content);
    $oTpl->addVal('v:head-title', $title);
    $oTpl->parse();
    return $oTpl->output();
}

/**
 * Returns date string in defined dateformat
 *
 * @param string $d date in timestamp(14) format
 * @param string $ftm date format
 * @return string   date
 */
function dateExtract($d, $fmt = "%d %M %Y %H:%i:%s")
{
    global $oCase;
    $monthsL = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_decl'));
    $monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
    if ((sizeof($monthsL) < 12) || (sizeof($monthsS) < 12)) {
        return '';
    }
    /* TIMESTAMP(14) is not YYYYMMDDHHMMSS anymore since Mysql 4.1! Shit! */
    $d = preg_replace('/[^0-9]/', '', $d);
    /**
     * %d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
     * %m - month; i.e. "01" to "12"
     * %FL - month, textual, long, lowercase; i.e. "january"
     * %F - month, textual, long; i.e. "January"
     * %ML - month, textual, 3 letters, lowercase; i.e. "jan"
     * %M - month, textual, 3 letters; i.e. "Jan"
     * %Y - year, 4 digits; i.e. "1999"
     * %H - hour, 24-hour format; i.e. "00..23"
     * %s - seconds; i.e. "00..59"
     */
    $fmt = str_replace("%d", (substr($d, 6, 2) / 1), $fmt); // removes leading 0 from date
    $fmt = str_replace("%m", substr($d, 4, 2), $fmt);
    $fmt = str_replace("%FL", str_replace('_', ' ', $oCase->lc($monthsL[(substr($d, 4, 2) / 1)])), $fmt);
    $fmt = str_replace("%F", str_replace('_', ' ', $monthsL[(substr($d, 4, 2) / 1)]), $fmt);
    $fmt = str_replace("%ML", str_replace('_', ' ', $oCase->lc($monthsS[(substr($d, 4, 2) / 1)])), $fmt);
    $fmt = str_replace("%M", str_replace('_', ' ', $monthsS[(substr($d, 4, 2) / 1)]), $fmt);
    $fmt = str_replace("%Y", substr($d, 0, 4), $fmt);
    $fmt = str_replace("%H", substr($d, 8, 2), $fmt);
    $fmt = str_replace("%i", substr($d, 10, 2), $fmt);
    $fmt = str_replace("%s", substr($d, 12, 2), $fmt);
    return $fmt;
}

/**
 * Returns date string in defined dateformat
 *
 * @param int $d date in integer(10) format
 * @param string $ftm date format
 * @return string   date
 */
function date_extract_int($d, $fmt = "%d %M %Y %H:%i:%s")
{
    global $oCase;
    /* 1099947600 */
    $monthsL = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_decl'));
    $monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
    if ((sizeof($monthsL) < 12) || (sizeof($monthsS) < 12)) {
        return '';
    }
    $YYYY = date("Y", $d);
    $MM   = date("n", $d);
    $dd   = date("j", $d);
    $HH   = date("H", $d);
    $ii   = date("i", $d);
    $ss   = date("s", $d);
    $hh   = date("h", $d);
    $a    = ($HH > 12) ? ('pm') : 'am';
    $A    = ($HH > 12) ? ('PM') : 'AM';
    $mL   = str_replace('_', ' ', $monthsL[$MM]);
    $mS   = str_replace('_', ' ', $monthsS[$MM]);
    $fmt  = str_replace("%d", $dd, $fmt);
    $fmt  = str_replace("%m", $MM, $fmt);
    $fmt  = str_replace("%FL", $oCase->lc($mL), $fmt);
    $fmt  = str_replace("%F", $mL, $fmt);
    $fmt  = str_replace("%ML", $oCase->lc($mS), $fmt);
    $fmt  = str_replace("%M", $mS, $fmt);
    $fmt  = str_replace("%Y", $YYYY, $fmt);
    $fmt  = str_replace("%A", $A, $fmt);
    $fmt  = str_replace("%a", $a, $fmt);
    $fmt  = str_replace("%H", $HH, $fmt);
    $fmt  = str_replace("%h", $hh, $fmt);
    $fmt  = str_replace("%i", $ii, $fmt);
    $fmt  = str_replace("%s", $ss, $fmt);
    return $fmt;
}

/**
 * Converts & into &amp; and encrypts url parameters
 *
 * @param string $url url with parameters
 * @param array $vars not in use
 * @return  string  converted and encrypted url parameters
 */
function append_url($url, $vars = [])
{
    global $oSess, $arDictParam, $sys;
    /* removes &amp; to avoid any problems with & */
    $url      = str_replace("&amp;", "&", $url);
    $str_file = $param = '';
    $ar_param = [];
    if (preg_match("/\?/", $url) && preg_match("/a=term/", $url) && !preg_match("/q=/", $url)) // encode link to a term
    {
        list($str_file, $param) = explode("?", $url);
        if (isset($arDictParam['is_leech']) && ($arDictParam['is_leech'] == 1)) {
            $url = $str_file . '?' . url_encrypt($sys['is_hideurl'], $param);
        }
    }
    if (isset($oSess) && $oSess->id_sess) {
        $url = str_replace(GW_SID . '=' . $oSess->id_sess, '', $url);
        $url = $oSess->url($url);
    }
    $url = str_replace("&", "&amp;", $url);
    return $url;
}

/**
 * Build HTML select element.
 *
 * @param array $ar_data Option list as value => label.
 * @param mixed $default Selected option value.
 * @param string $form_name Select name attribute.
 * @param string $css_class CSS class for select.
 * @param string $style Inline style for select.
 * @param string $dir Text direction.
 *
 * @return string
 */
function gw_html_forms_select(
    $ar_data,
    $default,
    $form_name = 'select',
    $css_class = 'input',
    $style = '',
    $dir = 'ltr'
) {
    global $sys, $oHtml;

    $ar_attr_select    = [];
    $html              = '';
    $max_char_combobox = isset($sys['max_char_combobox']) ? (int)$sys['max_char_combobox'] : 0;
    $is_lang_key_mode  = false;
    $ar_data           = is_array($ar_data) ? $ar_data : [];

    if ($css_class !== '') {
        $ar_attr_select['class'] = $css_class;
    }

    if ($style !== '') {
        $ar_attr_select['style'] = $style;
    }

    if ($dir !== '') {
        $ar_attr_select['dir'] = $dir;
    }

    if ($form_name !== '') {
        $ar_attr_select['name'] = $form_name;
    }

    if ((strpos($form_name, 'abbrlang') !== false) || (strpos($form_name, 'trnslang') !== false)) {
        $is_lang_key_mode = true;
    }

    $html .= '<select' . $oHtml->paramValue($ar_attr_select) . '>';

    foreach ($ar_data as $option_value => $option_label) {
        $ar_attr_option = [];

        /* Decode quotes to calculate the correct string length */
        $option_label     = htmlspecialchars_decode($option_label, ENT_QUOTES);
        $option_label_src = $option_label;

        /*
         * Cut long names for proper display.
         * trim() is used to handle values loaded from legacy MySQL data.
         */
        if (($max_char_combobox > 0) && (mb_strlen(trim($option_label)) > $max_char_combobox)) {
            $ar_attr_option['title'] = htmlspecialchars($option_label_src, ENT_QUOTES, 'UTF-8');
            $option_label            = mb_substr($option_label, 0, $max_char_combobox) . '…';
        }

        $ar_attr_option['value'] = $option_value;

        if ($option_value == $default) {
            $ar_attr_option['selected'] = 'selected';
        }

        $html .= PHP_EOL . '<option' . $oHtml->paramValue($ar_attr_option) . '>';

        if ($is_lang_key_mode) {
            $html .= htmlspecialchars($option_value, ENT_QUOTES, 'UTF-8');
        } else {
            $html .= htmlspecialchars($option_label, ENT_QUOTES, 'UTF-8');
        }

        $html .= '</option>';
    }

    $html .= '</select>';

    return $html;
}

/**
 * Outputs nice help window.
 */
function kTbHelp($title, $content, $w = "100%")
{
    global $ar_theme;
    $str = "";
    $str .= '<table cellspacing="1" style="border:1px solid ' . $ar_theme['color_4'] . '" cellpadding="3" border="0" width="' . $w . '">';
    $str .= '<tbody><tr class="gray"><td style="background:' . $ar_theme['color_3'] . '" class="xr">' . $title . '</td></tr>';
    $str .= '<tr><td style="background:' . $ar_theme['color_2'] . '" class="xt">' . $content . "</td></tr>";
    $str .= '</tbody></table>';
    return $str;
}


/**
 * Build normalized meta keywords string.
 *
 * Accepts an array of keywords, removes empty items,
 * normalizes line breaks, removes duplicates
 * and formats the result as a comma-space list.
 *
 * @param array $keywords
 *
 * @return string
 */
function gw_searchkeys(array $keywords)
{
    $keywords_string = implode(',', $keywords);
    $keywords_string = str_replace(
        ["\r\n", "\r", "\n", ', '],
        [' ', ' ', ' ', ','],
        $keywords_string
    );

    $words = explode(',', $keywords_string);

    foreach ($words as $key => $word) {
        $word = trim($word);

        if ($word === '') {
            unset($words[$key]);
            continue;
        }

        $words[$key] = $word;
    }

    $words = array_values(array_unique($words));

    return implode(', ', $words);
}

/**
 * Optimizes HTML-code. Light version
 */
function gw_text_smooth_light($t)
{
    $t = str_replace("<div", "\n<div", $t);
    $t = str_replace("<td", "\n<td", $t);
    $t = str_replace("<tr", "\n<tr", $t);
    $t = str_replace("<table", "\n<table", $t);
    $t = str_replace("\n</", '</', $t);
    return $t;
}
  
/**
 * Optimizes HTML-code
 */
function gw_text_smooth($t, $is_debug = 0)
{
    /* skip optimization when debug mode */
    if ($is_debug) {
        return $t;
    }
    $t = str_replace("\r\n", ' ', $t);
    $t = str_replace("\n", ' ', $t);
    $t = str_replace("\r", ' ', $t);
    $t = str_replace("\t", ' ', $t);
    $t = preg_replace("/ {2,}/", " ", $t);
    $t = preg_replace("/<br>/i", "<br />", $t);
    $t = str_replace('<center>', '', $t);
    $t = str_replace('</center>', '', $t);
    $t = str_replace(LF, "\n", $t);
#	$t = preg_replace("/<script(.*?)>(.*?)<\/script>/si", "<script type=\"text/javasctipt\">\\2</script>", $t);
    $t = preg_replace("/<!--(.*?)-->/s", '', $t);
    $t = preg_replace("/([, ])+([-]{2})([ \w+])/", '\\1&#8212;\\3', $t);
    $t = str_replace(' &#8212;', '&#160;&#8212;', $t);
    $t = preg_replace("/<script(.*?)>(.*?)<\/script>/si", "<script\\1>\\2</script>", $t);
#	$t = str_replace('//]]></script>', CRLF . '//]]></script>', $t);
    return $t;
}

/**
 * Filter for HTML-code of a definition text
 */
function gw_text_smooth_defn($t, $is_debug = 0)
{
    /* skip optimization when debug mode */
    if ($is_debug) {
        return $t;
    }
    /* preformatted text */
    /* (.*[^>]) */
    if (preg_match_all("/<pre(.*?)>(.*?)<\/pre>/s", $t, $pre)) {
        foreach ($pre[2] as $k => $v) {
            $pre[2][$k] = str_replace("\t", "&#160;&#160;&#160;", $pre[2][$k]);
            $pre[2][$k] = str_replace("  ", "&#160;&#160;", $pre[2][$k]);
            $pre[2][$k] = str_replace(CRLF, "<br />", $pre[2][$k]);
            $pre[2][$k] = str_replace("\n", "<br />", $pre[2][$k]);
            $t          = str_replace($pre[0][$k], '<tt' . $pre[1][$k] . '>' . $pre[2][$k] . '</tt>', $t);
            $t          = preg_replace("/<tt><br \/>/", '<tt' . "\\1" . ' class="pre">', $t);
        }
    }
    return $t;
}

/**
 * Depreciated function name
 *
 * @depreciated
 */
function textcodetoform($t)
{
    if (is_string($t)) {
        $t = str_replace("&#228;", chr("228"), $t);
        $t = htmlspecialchars($t);
    }
    return $t;
}

/**
 * Validates HTML-form
 *
 * @depreciated
 */
function validatePostWalk($a, $reqFieldsA = [])
{
    $brokenFieldsA = [];

    foreach ($a as $key1 => $value1) {
        foreach ($reqFieldsA as $reqKey1 => $reqValue1) // read required
        {
            if ($key1 == $reqValue1) // posted == required
            {
                if (!is_array($value1)) {
                    $value1 = gw_text_sql($value1);

                    // url check
                    if ($reqValue1 == 'url') {
                        if (str_replace('http://', '', $value1) == '') {
                            $value1 = '';
                        }
                    }

                    if ($value1 == '') {
                        $brokenFieldsA[$key1] = '';
                    }
                } else {
                    foreach ($value1 as $key2 => $value2) {
                        $value1[$key2] = gw_text_sql($value2);

                        if ($value1[$key2] == '') {
                            $brokenFieldsA[$key1] = '';
                        }
                    }
                }
            }
        }
    }

    return $brokenFieldsA;
}

//
## --------------------------------------------------------
## HTML-library
## (C) 1999 Dmitry Shilnikov
/**
 * Universal function for date selection.
 *
 * @param string $name field name
 * @param string $val timestamp
 * @return   string  HTML-code
 */
function htmlFormSelectDate($name, $val)
{
    global $arLs;
    // month names
    $arLs['0'] = "---";
    // fields width [ year | month | day | (time) ]
    $cfgWidth = ["33%", "34%", "33%"];
    // keep zero values?
    $cfgIsNulls = 0;
    // keep seconds?
    $cfgIsSec = 1;
    $str      = "";
    if (preg_match("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{6})/", $val, $rowDate)) {
        // variable       -> variableD
        // variable[]     -> variableD[]
        // variable[name] -> variable[nameD]
        if (preg_match("/(.*?)\[(.*?)\]$/", $name, $rowName)) {
            if ($rowName['2'] != "") {
                $rowName['1'] = $rowName['1'] . '[' . $rowName['2'];
                $rowName['2'] = ']';
            } else {
                $rowName['2'] = "[]";
            }
        } else {
            $rowName['1'] = $name;
            $rowName['2'] = "";
        }
        if ($cfgIsSec) {
            $cfgWidth = ["25%", "25%", "25%", "25%"];
        }
        // Year select #-------------------------------------------------
        $strA[0] = '<select name="' . $rowName['1'] . 'Y' . $rowName['2'] . '" class="input">';
        // for 0000
        if ($rowDate[1] == "0000") {
            $strA[0] .= '<option value="0000" selected="selected">0000</option>';
        } else {
            if ($cfgIsNulls) {
                $strA[0] .= '<option value="0000">0000</option>';
            }
        }
        $strA[0] .= '<option value="2037">2037</option>';
        for ($y = (date("Y") + 1); $y > (date("Y") - 7); $y--) {
            // autoselect
            if ($rowDate[1] == $y) {
                $s = ' selected="selected"';
            } else {
                $s = "";
            }
            $strA[0] .= '<option value="' . $y . '"' . $s . '>' . $y . '</option>';
        }
        $strA[0] .= '<option value="1970">1970</option>';
        $strA[0] .= '</select>';
        // Month select #-------------------------------------------------
        $strA[1] = '<select name="' . $rowName['1'] . 'M' . $rowName['2'] . '" class="input">';
        // 30 sep 2002
        $monthsS = explode(' ', ' ' . $GLOBALS['oL']->m('array_month_short'));
        for ($m = 0; $m <= 12; $m++) {
            // autoselect
            if ($rowDate[2] == $m) {
                $s = ' selected="selected"';
            } else {
                $s = "";
            }
            // output
            if ($m == 0) {
                if ($cfgIsNulls) {
                    $strA[1] .= '<option value="' . sprintf("%'02s", $m) . '"' . $s . '>' . sprintf(
                            "%'02s",
                            $m
                        ) . " " . $arLs[$m] . '</option>';
                }
            } else {
                $strA[1] .= '<option value="' . sprintf("%'02s", $m) . '"' . $s . '>' . @$monthsS[$m] . '</option>';
            }
        }
        $strA[1] .= "</select>";
        // Day select #-------------------------------------------------
        $strA[2] = '<select name="' . $rowName['1'] . 'D' . $rowName['2'] . '" class="input">';
        for ($d = 0; $d <= 31; $d++) {
            // autoselect
            if ($rowDate[3] == $d) {
                $s = ' selected="selected"';
            } else {
                $s = "";
            }
            // output
            if ($d == 0) {
                if ($cfgIsNulls) {
                    $strA[2] .= '<option value="' . sprintf("%'02s", $d) . '"' . $s . '>' . sprintf(
                            "%'02s",
                            $d
                        ) . '</option>';
                }
            } else {
                $strA[2] .= '<option value="' . sprintf("%'02s", $d) . '"' . $s . '>' . sprintf(
                        "%'02s",
                        $d
                    ) . '</option>';
            }
        }
        $strA[2] .= '</select>';
        // field for seconds
        if ($cfgIsSec) {
            $rowDate[4] = substr($rowDate[4], 0, 2) . ":" . substr($rowDate[4], 2, 2) . ":" . substr($rowDate[4], 4, 2);
            $strA[3]    = '<input class="input" size="8" maxlength="8" name="' . $rowName['1'] . 'S' . $rowName['2'] . '" value="' . $rowDate[4] . '" />';
        }
    } else {
        $str .= "Invalid date format: $val";
    }
    if (isset($strA) && is_array($strA)) {
        $str = '<table style="font-size:100%;" cellspacing="0" cellpadding="0" border="0" width="100%"><tbody><tr>';
        foreach ($strA as $k => $v) {
            $str .= '<td style="width:' . $cfgWidth[$k] . '">';
            $str .= $v;
            $str .= '</td>';
        }
        $str .= '</tr></tbody></table>';
    }
    return $str;
}



