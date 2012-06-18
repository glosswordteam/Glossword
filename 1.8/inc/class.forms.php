<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 * HTML-Form constructor
 *
 * @version $Id: class.forms.php 531 2008-07-09 19:20:16Z glossword_team $
 */
class gwForms {
	var $title              = '';
	var $action             = 'post.php';
	var $align_buttons      = 'right';
	var $arReq              = array();
	var $enctype            = 'application/x-www-form-urlencoded';
	var $method             = 'post';
	var $formbgcolor        = 'DDD';
	var $formbordercolor    = '444';
	var $formbordercolorL   = '#FFF';
	var $formname           = 'vbform';
	var $formvalue          = 'none';
	var $formwidth          = '100%';
	var $isButtonDel        = 0;
	var $isButtonHelp       = 0;
	var $isButtonCancel     = 1;
	var $isButtonSubmit     = 1;
	var $onclickCancel      = 'history.back(-1);document.getElementById(\'vbcontrol\').style.visibility=\'hidden\'';
	var $onclickSubmit      = 'document.getElementById(\'vbcontrol\').style.visibility=\'hidden\'';
	var $str                = '';
	var $strNotes           = '&#160;';
	var $submitcancel       = ' Cancel ';
	var $submitdel          = ' Remove ';
	var $submitdelname      = 'remove';
	var $submitok           = ' OK ';
	var $arLtr              = array();
	var $charset            = 'utf-8';
	var $is_htmlspecialchars= 0;
	var $cnt_submit         = 0;
	var $cnt_cancel         = 0;
	var $cnt_delete         = 0;
	/**
	 *
	 */
	function setTag($tag, $var, $value)
	{
		if ($tag != '')
		{
			$this->tags[$tag][$var] = $value;
		}
	}
	function unsetTag($tag, $var = '')
	{
		if (isset($this->tags[$tag][$var]))
		{
			unset($this->tags[$tag][$var]);
		}
		elseif (isset($this->tags[$tag]))
		{
			$this->tags[$tag] = array();
		}
	}
	/**
	 *
	 */
	function htmlParamValue($ar)
	{
		$str = '';
		if (is_array($ar))
		{
			// Do sort attributes in a good manner.
			ksort($ar);
			for (reset($ar); list($k, $v) = each($ar);)
			{
				$str .= ($v != '') ? (' ' . $k . '="' . $v . '"') : '';
			}
		}
		return $str;
	} // end of htmlParamValue()
	/* */
	function Set($varname, $value)
	{
		$this->$varname = $value;
	}
	function text_field2id($t)
	{
		$t = preg_replace("/[^a-zA-Z0-9-_]/", "_", $t);
		$t = preg_replace("/_{2,}/" , "_", $t);
		return $t;
	}
	/* */
	function field($formtype = 'input', $formname = '', $value = '', $textareaheight = 2, $array = '', $autofocus = 0)
	{
		global $oFunc;
		$str = $strForm = '';
		$strSep = "..";
		if ($autofocus) // TODO: add to attribute instead of replacing
		{
			$this->setTag($formtype, 'onmouseover', 'this.focus()');
		}
		$ar['type'] = $formtype;
		$ar['name'] = $formname;
		$formname_id = $this->text_field2id($formname);
		switch ($formtype)
		{
		case "input":
			$this->setTag($formtype, 'name',  $formname);
			$this->setTag($formtype, 'value', $value);
			$this->setTag($formtype, 'id',    $formname_id);
			if (!isset($this->tags['input']['style']))
			{
				$this->setTag($formtype, 'style', '');
			}
			if (!isset($this->tags['input']['class']))
			{
				$this->setTag($formtype, 'class', 'input');
			}
			if (!isset($this->tags['input']['type']))
			{
				$this->setTag($formtype, 'type', 'text');
			}
			if (!isset($this->tags['input']['size']) || !$this->tags['input']['size'])
			{
				$this->setTag('input', 'size', '20');
			}
			/* fix for too long strings */
			if ($oFunc->mb_strlen($value) > $this->tags['input']['size'])
			{
				$this->setTag('input', 'style', 'width:'.intval($this->tags['input']['size']/1.2).'em');
			}
			if (in_array($formname, $this->arLtr))
			{
				$this->setTag('input', 'dir', 'ltr');
			}
			$extras = $this->htmlParamValue($this->tags[$formtype]);
			$str = sprintf('<input%s />', $extras);
			#$str = htmlspecialchars($str);
		break;
		case "file":
			$this->setTag($formtype, 'name',  $formname);
			$this->setTag($formtype, 'value', $value);
			$this->setTag($formtype, 'id', (isset($this->tags[$formtype]['id']) ? $this->tags[$formtype]['id'] : $formname_id) );
			$this->setTag($formtype, 'style', '');
			$this->setTag($formtype, 'type', 'file');
			if (!isset($this->tags['file']['class']))
			{
				$this->setTag($formtype, 'class', 'input');
			}
			if ($textareaheight > 2)
			{
				$this->setTag($formtype, 'maxlength', $textareaheight);
			}
			$extras = $this->htmlParamValue($this->tags[$formtype]);
			$str = sprintf('<input%s />', $extras);
#		$str .= '<input';
#			$str .= $this->htmlParamValue(array_merge($ar, is_array($array) ? $array : array()));
#			$str .= ' />';
		break;
		case "pass":
			$this->setTag('input', 'name',  $formname);
			$this->setTag('input', 'id',    $formname_id);
			if ($textareaheight > 2)
			{
				$this->setTag('input', 'maxlength', $textareaheight);
			}
			if (isset($this->tags['input']['style']))
			{
				$this->setTag('input', 'style', $this->tags['input']['style']);
			}
			if (in_array($formname, $this->arLtr))
			{
				$this->setTag('input', 'dir', 'ltr');
			}
			$this->setTag('input', 'type', 'password');
			$this->setTag('input', 'value', $value);
			$this->setTag('input', 'class', 'input');
			$this->setTag('input', 'size', 20);
			$extras = $this->htmlParamValue($this->tags['input']);
			$str = sprintf('<input%s />', $extras);
			$this->unsetTag('input');
		break;
		case "input90":
			$str .= "<input name=\"$formname\" value=\"$value\" style=\"width:60px\" class=\"input\"$mouseEvents />";
		break;
		case "radio":
			if ($textareaheight) { $ar['checked'] = "checked"; }
			$ar['type']     = 'radio';
			$ar['value']    = $value;
			$str .= '<input';
			$str .= $this->htmlParamValue(array_merge($ar, $array));
			$str .= ' />';
		break;
		case "checkbox":
			$this->setTag($formtype, 'name',  $formname);
			$this->setTag($formtype, 'type',  $formtype);
			$this->setTag($formtype, 'id',    $formname_id);
			$this->setTag($formtype, 'value', 1);
			if ($value == 1) // sometimes value is not just `1' or `0'
			{
				$this->setTag($formtype, 'checked', 'checked');
			}
			$extras = $this->htmlParamValue($this->tags[$formtype]);
			$str = sprintf('<input%s />', $extras);
			$this->unsetTag('checkbox');
#            $str = htmlspecialchars($str);
		break;
		case "hidden":
			$str .= '<input id="'.$formname_id.'" name="'.$formname.'" value="'.$value.'" type="hidden" />';
		break;
		case "textarea":
			$this->setTag($formtype, 'name', $formname);
			$this->setTag($formtype, 'id', $formname_id);
			$this->setTag($formtype, 'class', 'input');
			$this->setTag($formtype, 'rows', $textareaheight);
			$this->setTag($formtype, 'cols', (isset($this->tags['textarea']['cols']) ? $this->tags['textarea']['cols'] : 25) );
			$extras = $this->htmlParamValue($this->tags[$formtype]);
#prn_r( $this->tags[$formtype] );
			$str = sprintf('<textarea%s>%s</textarea>', $extras, $value);
#            $str = htmlspecialchars($str);
		break;
		case "select":
			$this->setTag($formtype, 'name', $formname);
			$this->setTag($formtype, 'id',   $formname_id);
			if (!isset($this->tags['select']['style']))
			{
				$this->setTag($formtype, 'style',  'width:50%');
			}
			if (in_array($formname, $this->arLtr))
			{
				$this->setTag($formtype, 'dir', 'ltr');
			}
			if ($textareaheight && is_string($textareaheight))
			{
				if (!preg_match("/%/", $textareaheight))
				{
					$textareaheight = $textareaheight. 'px';
				}
				$this->setTag($formtype, 'style', 'width:' . $textareaheight);
			}
			$extras = $this->htmlParamValue($this->tags[$formtype]);
			$str = sprintf('<select%s>', $extras);
			while (is_array($array) && list($k, $v) = each($array) )
			{
				$s = '';
				$title = '';
				if (is_array($value))
				{
					/* Multiple */
					for (reset($value); list($kV, $vV) = each($value);)
					{
						if (strval($k) == strval($kV))
						{
							$s = ' selected="selected"';
						}
					}
				}
				else if (strval($k) == strval($value))
				{
					/* Single */
					$s = ' selected="selected"';
				}
				if (isset($textareaheight[$k]))
				{
					$title = ' title="'.$textareaheight[$k].'"';
				}
				$str .= sprintf(CRLF . "\t". '<option value="%s"%s%s>%s</option>', $k, $title, $s, $array[$k]);
			}
			$str .= '</select>';
		break;
		default:
		break; // should never be
	}
		if ($this->is_htmlspecialchars)
		{
			$str .= htmlspecialchars($str);
		}
		return $str;
	} // end of field();
	/* */
	function Output($formedhtml = "")
	{
		$str = "";
		$ar = array();
		$ar['id']       = $this->formname;
		$ar['action']   = $this->action;
		$ar['enctype']  = $this->enctype;
		$ar['method']   = $this->method;
		$ar['style']    = 'margin:0;padding:0';
		$ar['accept-charset'] = $this->charset;
		$ar['onkeypress'] = 'gwctrlenter=function(e,o){e=(e)?e:window.event;if(((e.keyCode==13)||(e.keyCode==10))&amp;&amp;(e.ctrlKey==true)){document.forms[o].submit1.click()}};return gwctrlenter(event,\''.$this->formname.'\')';
		// HTML start here
		$str .= '<div style="text-align:center">';
		$str .= '<form'. $this->htmlParamValue($ar) . '>';
		$str .= '<table width="'.$this->formwidth.'" border="0" cellspacing="1" cellpadding="1" style="margin: 0 auto;background:'.$this->formbordercolor.'">';
		$str .= '<tbody><tr><td style="background:'.$this->formbordercolorL.'">';
		$str .= '<table width="100%" border="0" cellspacing="0" cellpadding="3" style="background:'.$this->formbgcolor.'"><tbody>';
		if ($this->title != '')
		{
			$str .= '<tr style="vertical-align:top"><td colspan="2" style="background:'.$this->formbordercolor.'">';
			$str .= $this->title;
			$str .= "</td></tr>";
		}
		$str .= '<tr style="vertical-align:top"><td colspan="2" style="background:'.$this->formbgcolor.'">';
		$str .= $formedhtml;
		$str .= '</td></tr>';
		$str .= '<tr>';
		$str .= '<td style="text-align:left;background:'.$this->formbgcolor.'">';
		$str .= $this->strNotes;
		$str .= '</td>';
		$str .= '<td style="background:'.$this->formbgcolor.'">';
		$str .= '<table style="float:'.$this->align_buttons.'" width="1%" border="0" cellspacing="0" cellpadding="2" id="vbcontrol"><tbody><tr align="center">';
		if ($this->isButtonHelp)
		{
			$str .= '<td><input type="button" name="'.$this->submithelpname.'" value="'.$this->submithelp.'" class="submithelp" /></td>';
		}
		if ($this->isButtonCancel)
		{
			$str .= '<td>'.$this->get_button('cancel').'</td>';
		}
		if ($this->isButtonDel)
		{
			$str .= '<td>'.$this->get_button('delete').'</td>';
		}
		if ($this->isButtonSubmit)
		{
			$str .= '<td>'.$this->get_button('submit').'</td>';
		}
		$str .= '</tr></tbody></table>';
		$str .= '</td>';
		$str .= '</tr>';
		$str .= '</tbody></table>';
		$str .= '</td></tr></tbody></table>';
		$str .= '</form></div>';
		return $str;
	}
	/* Get HTML-code for buttons */
	function get_button($b)
	{
		switch ($b)
		{
			case 'submit':
				$this->cnt_submit++;
				return '<input id="submit'.$this->cnt_submit.'" accesskey="S" tabindex="1" onclick="'.$this->onclickSubmit.'" type="submit" name="post" title="'.$this->submitok.' (CTRL+Enter)" value="'.$this->submitok.'" class="submitok" />';
			break;
			case 'cancel':
				$this->cnt_cancel++;
				return '<input id="cancel'.$this->cnt_cancel.'" onclick="'.$this->onclickCancel.'" type="reset" value="'.$this->submitcancel.'" class="submitcancel "/>';
			break;
			case 'delete':
				$this->cnt_delete++;
				return '<input id="delete'.$this->cnt_delete.'" onclick="document.getElementById(\'vbcontrol\').style.visibility=\'hidden\'" type="submit" name="'.$this->submitdelname.'" value="'.$this->submitdel.'" class="submitdel" />';
			break;
		}
	}
}
/* end of file */
?>