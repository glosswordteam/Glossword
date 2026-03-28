<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: edcode.js.php 84 2007-06-19 13:01:21Z yrtimd $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/dev/)
 *  © 2002-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
// --------------------------------------------------------
/**
 *  Javascript functions for HTML-editor.
 */

$tmp['strform'] .= '<script type="text/javascript">/*<![CDATA[*/
	var clientPC = navigator.userAgent.toLowerCase();
	var clientVer = parseInt(navigator.appVersion);
	var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
	var is_nav = ((clientPC.indexOf(\'mozilla\')!=-1) && (clientPC.indexOf(\'spoofer\')==-1)
                && (clientPC.indexOf(\'compatible\') == -1) && (clientPC.indexOf(\'opera\')==-1)
                && (clientPC.indexOf(\'webtv\')==-1) && (clientPC.indexOf(\'hotjava\')==-1));
	var is_win = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
	var is_mac = (clientPC.indexOf("mac")!=-1);

	// Type 1, open and close tags
	function sTextTag(id, text) {
		var n = gw_getElementById(\'arPre_defn_\' + id + \'_value_\');
		if ((clientVer >= 4) && is_ie && is_win) {
			if ("Text" == document.selection.type) {
				var tr = document.selection.createRange();
				if (tr == null || tr.text == null) { return; }
				// string fix, 17 july 2003
				fstr = "<" + text + ">" + tr.text + "</" + text + ">";
				re = /\s(<\/\w+>)/;
				fstr = fstr.replace(re, "$1 ");
				re = /(<\w+>)\s/;
				fstr = fstr.replace(re, " $1");
				tr.text = fstr;
				//
				tr.select();
			}
			else {
				n.focus();
			}
		}
		else if (n.selectionEnd && (n.selectionEnd - n.selectionStart > 0)) {
			mozWrap(n, "<" + text + ">", "</" + text + ">");
			return;
		}
		else {
			n.value += "<" + text + ">" + "</" + text + ">";
			n.focus();
		}
	}
	// Type 2, place pair
	function sTextDoubleSymbol(id, textStart, textEnd)
	{
		var n = gw_getElementById(\'arPre_defn_\' + id + \'_value_\');

		if ((clientVer >= 4) && is_ie && is_win) {
			if ("Text" == document.selection.type)
			{
				var tr = document.selection.createRange();
				if (tr == null || tr.text == null) { return; }
				// string fix, 17 july 2003
				re = /(\w)\s/;
				fstr = textStart + tr.text + textEnd;
				// trim attributes, 19 july 2003
				re = /\s(">)/;
				fstr = fstr.replace(re, "$1 ");
				re = /(<.*?=")\s/;
				fstr = fstr.replace(re, " $1");
				//
				re = /\s(<\/\w+>)/;
				fstr = fstr.replace(re, "$1 ");
				re = /(<\w+>)\s/;
				fstr = fstr.replace(re, " $1");
				tr.text = fstr;
				//
				tr.select();
				n.focus();
				return;
			}
			else
			{
				n.focus();
			}
		}
		else if (n.selectionEnd && (n.selectionEnd - n.selectionStart > 0))
		{
			mozWrap(n, textStart, textEnd);
			n.focus();
			return;
		}
		else
		{
			n.value += textStart + textEnd;
			n.focus();
		}
	}
	/* Type 4 */
	function sTextDoubleSymbol2(id, textPattern)
	{
		var n = gw_getElementById(\'arPre_defn_\' + id + \'_value_\');
		arParts = textPattern.split("|");
			
		if ((clientVer >= 4) && is_ie && is_win) {
			if ("Text" == document.selection.type)
			{
				var tr = document.selection.createRange();
				if (tr == null || tr.text == null) { return; }
				re = /\|/g;
				textPattern = textPattern.replace(re, tr.text);
				fstr = textPattern;
				
				// trim attributes, 19 july 2003
				re = /\s(">)/;
				fstr = fstr.replace(re, "$1 ");
				re = /(<.*?=")\s/;
				fstr = fstr.replace(re, " $1");
				//
				re = /\s(<\/\w+>)/;
				fstr = fstr.replace(re, "$1 ");
				re = /(<\w+>)\s/;
				fstr = fstr.replace(re, " $1");
				tr.text = fstr;
				//
				tr.select();
				n.focus();
				return;
			}
			else
			{
				n.focus();
			}
		}
		else if (n.selectionEnd && (n.selectionEnd - n.selectionStart > 0))
		{
			selLength = n.textLength;
			selStart = n.selectionStart;
			selEnd = n.selectionEnd;
			if (selEnd == 1 || selEnd == 2) { selEnd = selLength; }
			s1 = (n.value).substring(0, selStart);
			s2 = (n.value).substring(selStart, selEnd)
			s3 = (n.value).substring(selEnd, selLength);
			re = /\s(">)/;
			s2 = s2.replace(/^\s+/, "").replace(/\s+$/, "");
			fstr = s1;
			for (i=0; i < arParts.length; i++)
			{
				fstr += arParts[i];
				if (arParts.length - 1 != i)
				{
					fstr += s2;
				}
			}
			fstr += s3;
			/* */
			n.value = fstr;
			n.focus();
		}
		else
		{
			n.value += textStart + textEnd;
			n.focus();
		}
	}
	// Type 3, insert single symbol
	function sTextSymbol(id, text)
	{
		var n = gw_getElementById(\'arPre_defn_\' + id + \'_value_\');
		if (n.createTextRange && n.caretPos)
        {
            var caretPos = n.caretPos;
            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == \' \' ? text + \'\': text;
        }
        else
        {
            n.value += text;
        }
        n.focus();
    }
    //
    function edPrompt(id, tag, thetype)
    {
		var n = gw_getElementById(\'arPre_defn_\' + id + \'_value_\');
        var link_txt_prompt = "' . $this->oL->m('prompt_txt') . '";
        var link_lnk_prompt = "' . $this->oL->m('prompt_lnk') . '";
        var link_url_prompt = "' . $this->oL->m('prompt_url') . '";
        var linktext = prompt(link_txt_prompt,"");
        var prompttext;
        var text = "";
        if (thetype == "url")
        {
            prompt_text = link_url_prompt;
            prompt_contents = "http://";
        }
        else if (thetype == "link")
        {
            prompt_text = link_lnk_prompt;
            prompt_contents = "";
        }
        else {}

        linkurl = prompt(prompt_text, prompt_contents);
        if ((linkurl != null) && (linkurl != ""))
        {
            if ((linktext != null) && (linktext != ""))
            {
                text = "<" + tag + " link=\"" + linkurl + "\">" + linktext + "</" + tag + ">";
            }
            else
            {
                text = "<" + tag + " link=\"" + linkurl + "\">" + linkurl + "</" + tag + ">";
            }
        }
        if (n.createTextRange && n.caretPos)
        {
            var caretPos = n.caretPos;
            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == \' \' ? text + \'\': text;
        }
        else
        {
            n.value += text;
        }
        n.focus();
    }
	// classic solution
	function storeCaret(textEl) {
		if (textEl.createTextRange) { textEl.caretPos = document.selection.createRange().duplicate(); }
	}
	function mozWrap(txtarea, textStart, textEnd)
	{
		var selLength = txtarea.textLength;
		var selLength = txtarea.textLength;
		var selStart = txtarea.selectionStart;
		var selEnd = txtarea.selectionEnd;
		if (selEnd == 1 || selEnd == 2) { selEnd = selLength; }
		var s1 = (txtarea.value).substring(0,selStart);
		var s2 = (txtarea.value).substring(selStart, selEnd)
		var s3 = (txtarea.value).substring(selEnd, selLength);
		fstr = s1 + textStart + s2 + textEnd + s3;

		txtarea.value = fstr;
		return;
	}

/*]]>*/</script>';
?>