<?php
/**
 * @version		$Id$
 * @copyright	 Dmitry N. Shilnikov, 2002-2010
 * @license		Commercial
 */
/**
 * HTML-forms builder. 
 * 3rd generation. No <table>, only <label>s.
 */
if (!defined('IS_CLASS_HTMLFORMS')) { define('IS_CLASS_HTMLFORMS', 1);
class site_forms3
{
	public $ar_ltr = array();
	public $ar_tags = array();
	public $is_actions = 1;
	public $is_error = 0;
	public $is_htmlspecialchars = 0;
	public $is_click_legend = 1;
	public $is_label_ids = 0;
	
	private $int_tr = 0;
	private $int_actions = 0;

	public $is_submit_ok = 1;
	public $is_submit_cancel = 0;

	/* Shows submit button near every legend */
	public $is_submit_legend = 0;
	/* Shows submit on the top */
	public $is_actions_top = 0;

	public $ar_after_sublegend = array();
	public $ar_after_legend = array();
	public $ar_fields_new = array();
	public $ar_fields_hidden = array();
	public $ar_fields = array();
	public $ar_subfieldsets = array();
	public $ar_subsubfieldsets = array();

	public $div_fieldsets = 'inp';
	public $str_ajax_status = '<div id="ajax-status"></div>';
	public $ar_required = array();
	public $ar_onoff = array();
	public $ar_broken = array();
	public $phrase_actions = 'Actions';
	public $phrase_submit_ok = 'OK';
	public $phrase_submit_cancel = 'CANCEL';
	public $phrase_wait = 'Please wait...';
	public $phrase_incorrect = 'Incorrect';

	public $e_form_name = 'htmlform';

	private $cur_fieldset = 0;
	private $cur_subfieldset = 0;
	private $cur_subsubfieldset = 0;


	public function set_tag($tag, $v = '', $value = '')
	{
		if ($tag != '')
		{
			$this->ar_tags[$tag][$v] = $value;
		}
	}
	public function unset_tag($tag, $v = '')
	{
		if (isset($this->ar_tags[$tag][$v]))
		{
			/* Clean one attribute only */
			unset($this->ar_tags[$tag][$v]);
		}
		elseif (isset($this->ar_tags[$tag]))
		{
			/* Clean all attributes */
			$this->ar_tags[$tag] = array();
		}
	}
	/* */
	private function _render($s)
	{
		if ($this->is_htmlspecialchars)
		{
			return htmlspecialchars($s);
		}
		return $s;
	}
	/**
	 * Replaces htmlspecialchars()
	 */
	private static function do_htmlspecialchars($s)
	{
		return str_replace(array('&','<','>','{','[','"'), array('&amp;','&lt;','&gt;','&#123;','&#091;','&quot;'), $s);
	}
	/**
	 * Replaces htmlspecialchars_decode()
	 */
	private static function undo_htmlspecialchars($s)
	{
		return str_replace(array('&amp;','&lt;','&gt;','&#123;','&#091;','&quot;'), array('&','<','>','{','[','"'), $s);
	}

	/* replacement for http_build_query */
	public static function http_build_query($ar = array(), $pairs = ' ', $values = '=', $enclose = '"')
	{
		$str = '';
		if (is_array($ar))
		{
			/* Do sort attributes in a good manner. */
			ksort($ar);
			for (reset($ar); list($k, $v) = each($ar);)
			{
				$str .= ($v != '') ? ($pairs.$k.$values.$enclose.$v.$enclose) : '';
			}
			$str = ltrim($str, $pairs);
		}
		return $str;
	}
	/* */
	public function field($formtype = 'input', $formname = '', $value = '', $ar = array())
	{
		$str = '';
		/* Field ID */
		$formname_id = $this->text_field2id($formname);
		if (isset($this->ar_tags[$formtype]['id']))
		{
			$formname_id = $this->ar_tags[$formtype]['id'];
		}
		/* Indicate incorrect field */
		for (reset($this->ar_broken); list($k, $v) = each($this->ar_broken);)
		{
			$str_compared_from = preg_replace("/^(.*?)\[(.*?)\](.*)?$/", '\\2', $formname);
			if ($str_compared_from == $k)
			{
				$str .= '<span class="state-warning">'.$this->phrase_incorrect.'</span><br />';
			}
		}
		/* Print `field is required` mark */
		if ($formtype != 'hidden')
		{
			$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['req'] = '';
			$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['id'] = $formname_id;
			for (reset($this->ar_required); list($k, $v) = each($this->ar_required );)
			{
				$str_compared_from = preg_replace("/^(.*?)\[(.*?)\](.*)?$/", '\\2', $formname);
				if ($str_compared_from == $v)
				{
					$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['req'] = '<span class="state-warning">*</span>';
				}
			}
		}

		switch ($formtype)
		{
			case 'input':
				$this->set_tag($formtype, 'name',  $formname);
				$this->set_tag($formtype, 'type',  'text');
				$this->set_tag($formtype, 'id',    $formname_id);
				$this->set_tag($formtype, 'value', $this->do_htmlspecialchars($value));
				if (!isset($this->ar_tags['input']['class']))
				{
					$this->set_tag($formtype, 'class', 'input');
				}
				if (!isset($this->ar_tags['input']['size']))
				{
					$this->set_tag('input', 'size', '20');
				}
				if (!isset($this->ar_tags['input']['dir']) && in_array($formname, $this->ar_ltr))
				{
					$this->set_tag('input', 'dir', 'ltr');
				}
				$str .= '<input '.$this->http_build_query($this->ar_tags[$formtype]).' />';
			break;
			case 'password':
				$this->set_tag($formtype, 'name',  $formname);
				$this->set_tag($formtype, 'type',  'password');
				$this->set_tag($formtype, 'id',    $formname_id);
				$this->set_tag($formtype, 'value', $this->do_htmlspecialchars($value));
				if (!isset($this->ar_tags['input']['class']))
				{
					$this->set_tag($formtype, 'class', 'input');
				}
				if (!isset($this->ar_tags['input']['size']))
				{
					$this->set_tag('input', 'size', '20');
				}
				if (!isset($this->ar_tags['input']['dir']) && in_array($formname, $this->ar_ltr))
				{
					$this->set_tag('input', 'dir', 'ltr');
				}
				$str .= '<input '.$this->http_build_query($this->ar_tags[$formtype]).' />';
			break;
			case 'textarea':
				$this->set_tag($formtype, 'name',  $formname);
				$this->set_tag($formtype, 'id',    $formname_id);
				if (!isset($this->ar_tags['textarea']['rows']))
				{
					$this->set_tag($formtype, 'rows', '3');
				}
				if (!isset($this->ar_tags['textarea']['class']))
				{
					$this->set_tag($formtype, 'class', 'input');
				}
				if (!isset($this->ar_tags['textarea']['cols']))
				{
					$this->set_tag('textarea', 'cols', '40');
				}
				if (!isset($this->ar_tags['textarea']['dir']) && in_array($formname, $this->ar_ltr))
				{
					$this->set_tag('textarea', 'dir', 'ltr');
				}
				$str .= '<textarea '.$this->http_build_query($this->ar_tags[$formtype]).'>'.$this->do_htmlspecialchars($value).'</textarea>';
			break;
			case 'select':
				$this->set_tag($formtype, 'name',  $formname);
				$this->set_tag($formtype, 'id',    $formname_id);

				/* Unset some attributes before creating HTML */
				unset($this->ar_tags[$formtype]['type'], $this->ar_tags[$formtype]['value'], $this->ar_tags[$formtype]['options']);

				if (!isset($this->ar_tags['select']['class']))
				{
					$this->set_tag($formtype, 'class', 'input');
				}
				if (!isset($this->ar_tags['select']['style']))
				{
					$this->set_tag($formtype, 'style',  'width:50%');
				}
				$str .= '<select '.$this->http_build_query($this->ar_tags[$formtype]).'>';
				/* For each <option> */
				foreach ($ar as $k => $v)
				{
					$k = $this->do_htmlspecialchars($k);
					$this->unset_tag('option');
					$this->set_tag('option', 'value', $k);

					/* Add title for <option> */
					if (strlen($v) > 25)
					{
						$this->set_tag('option', 'title', $v);
					}
					if (is_array($value))
					{
						/* Multiple */
						foreach ($value as $kV => $vV)
						{
							if (strval($k) == strval($kV))
							{
								$this->set_tag('option', 'selected', 'selected');
							}
						}
					}
					else if ( strval( $k ) == strval( $value ) )
					{
						/* Single */
						$this->set_tag( 'option', 'selected', 'selected' );
					}
					/* Option color */
					$arC = array();
					if ( preg_match_all( '#^\#[A-Fa-f0-9]{3,6};#', $v, $arC ) )
					{
						$ar[$k] = preg_replace( '#^\#[A-Fa-f0-9]{3,6};#', '', $v );
						if ( isset( $arC[0][0] ) )
						{
							$this->set_tag( 'option', 'style', 'color:'.$arC[0][0] );
						}
					}
					$str .= '<option '.$this->http_build_query( $this->ar_tags['option'] ).'>'.$v.'</option>';
				}
				$this->unset_tag( 'option' );
				$str .= '</select>&#160;';
			break;
			case 'file':
				$this->set_tag( $formtype, 'name',  $formname );
				$this->set_tag( $formtype, 'type',  $formtype );
				$this->set_tag( $formtype, 'id',    $formname_id );
				$str .= '<input '.$this->http_build_query( $this->ar_tags[$formtype] ).' />';
				$this->unset_tag( $formtype );
			break;
			case 'checkbox':
				$this->set_tag( $formtype, 'name',  $formname );
				$this->set_tag( $formtype, 'type',  $formtype );
				$this->set_tag( $formtype, 'id',    $formname_id );
				$this->set_tag( $formtype, 'value', $value );
				if ( $value == '' )
				{
					$this->set_tag( $formtype, 'value', 1 );
				}
				if ( $value == 1 ) /* sometimes `value` is not just `1' or `0' */
				{
					$this->set_tag( $formtype, 'checked', 'checked' );
				}
				$str .= '<input '.$this->http_build_query( $this->ar_tags[$formtype] ).' />';
				$this->set_tag( $formtype, 'checked', '' );
				#$this->unset_tag( $formtype );
			break;
			case 'radio':
				$this->set_tag( $formtype, 'name',  $formname );
				$this->set_tag( $formtype, 'type',  $formtype );
				if ( !isset( $this->ar_tags[$formtype]['id'] ) )
				{
					$this->set_tag( $formtype, 'id', $formname_id );
				}
				/* */
				if ( is_bool( $value ) )
				{
					if ( !isset( $this->ar_tags[$formtype]['value'] ) )
					{
						$this->set_tag( $formtype, 'value', $value );
					}
					else if ( $this->ar_tags[$formtype]['value'] == '' )
					{
						$this->set_tag( $formtype, 'value', 1 );
					}
				}
				else
				{
					if ($value == '')
					{
						$this->set_tag( $formtype, 'value', 1 );
					}
				}
				/* value = 1     => checked */
				/* value = "xml" => checked */
				/* value = (bool) true => checked */
				/* value = (bool) false => not checked */
				/* use set_tag() to setup value for radio-button */
				if ( $value !== false )
				{
					$this->set_tag( $formtype, 'checked', 'checked' );
				}
				$str .= '<input '.$this->http_build_query( $this->ar_tags[$formtype] ).' />';
				$this->unset_tag( $formtype );
			break;
			case 'hidden':
				$this->ar_fields_hidden[] = '<input id="'.$formname_id.'" name="'.$formname.'" type="hidden" value="'.$this->do_htmlspecialchars($value).'" />';
			break;
		}
		unset( $this->ar_tags[$formtype]['id'] );
		return $str;
	}
	/* */
	public static function text_field2id($t)
	{
		return preg_replace("/-{2,}/", '-', preg_replace("/[^a-zA-Z0-9-]/", '-', $t));
	}
	/* */
	public function new_label($text, $field, $help = '')
	{
		$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['text'] = $text;
		$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['field'] = $field;
		$this->ar_fields_new[$this->cur_fieldset][$this->cur_subfieldset][$this->cur_subsubfieldset][$this->int_tr]['help'] = $help;
		$this->int_tr++;
	}
	/* */
	public function new_fieldset($id = '', $legend = '', $text = '')
	{
		$this->cur_fieldset= $id;
		$this->cur_subfieldset = 0;
		$this->cur_subsubfieldset = 0;
		$this->ar_fieldsets_new[$id] = $legend;
		$this->ar_after_legend[$id] = $text;
	}
	public function new_subfieldset($id = '', $legend = '', $text = '')
	{
		$this->cur_subfieldset = $id;
		$this->cur_subsubfieldset = 0;
		$this->ar_subfieldsets_new[$id] = $legend;
		$this->ar_after_sublegend[$id] = $text;
	}
	public function new_subsubfieldset($id = '', $legend = '')
	{
		$this->cur_subsubfieldset = $id;
		$this->ar_subsubfieldsets_new[$id] = $legend;
	}
	public function make_fieldsets()
	{
#prn_r( $this->ar_fieldsets_new );
		$str = '';
		
		if ($this->is_actions_top)
		{
			$str .= '<div class="submit-buttons" style="font-size:80%">';
			$str .= $this->get_actions($this->o->gv['action']);
			$str .= '</div>';
		}
			
		foreach ($this->ar_fields_new as $k_fieldset1 => $ar_subfields)
		{
			if ($this->is_submit_legend)
			{
				$str .= '<div class="submit-buttons" style="font-size:80%">';
				$str .= $this->get_actions($this->o->gv['action']);
				$str .= '</div>';
			}

			$id_fieldset = $this->text_field2id($k_fieldset1);
			$str .= '<div id="div-fs-'.$id_fieldset.'">';
			$str .= '<fieldset id="fs-'.$id_fieldset.'">';
			/* legend */
			$str .= '<legend>';
			if ($this->is_click_legend)
			{
				$str .= '<span onclick="return toggle_collapse(\'fs-'.$id_fieldset.'\',this,0)">'.$this->ar_fieldsets_new[$k_fieldset1].'</span>';
			}
			else
			{
				$str .= '<span>'.$this->ar_fieldsets_new[$k_fieldset1].'</span>';
			}
			/* 23 Jan 2010: added a special area for placing UI controls */
			if ( $this->ar_after_legend[$k_fieldset1] != '' )
			{
				$str .= $this->ar_after_legend[$k_fieldset1];
			}
			$str .= '</legend>';

			$str .= '<div id="co-fs-'.$id_fieldset.'"><div>';
			/* */
			foreach ($ar_subfields as $k_fieldset2 => $ar_subfields2)
			{
				if (isset($this->ar_subfieldsets_new[$k_fieldset2]))
				{
					$str .= '<fieldset id="subfieldset-'.$this->text_field2id($k_fieldset2).'">';
					$str .= '<legend>';
#					$str .= '<span onclick="return toggle_collapse(\'fs-sub-'.$id_fieldset.$k_fieldset2.'\',this)">'.$this->ar_subfieldsets_new[$k_fieldset2].'</span>';
					$str .= $this->ar_subfieldsets_new[$k_fieldset2];
					if ( $this->ar_after_sublegend[$k_fieldset2] != '' )
					{
						$str .= '&#160;'.$this->ar_after_sublegend[$k_fieldset2];
					}
					$str .= '</legend>';
					$str .= '<div id="co-fs-sub-'.$id_fieldset.$k_fieldset2.'"><div>';
				}
				foreach ($ar_subfields2 as $k_fieldset3 => $ar_subfields3)
				{
					if ( isset($this->ar_subsubfieldsets_new[$k_fieldset3]) )
					{
						$str .= '<fieldset id="subsubfieldset-'.$this->text_field2id($k_fieldset3).'">';
						$str .= '<legend>';
#						$str .= '<span onclick="return toggle_collapse(\'fs-subsub-'.$id_fieldset.$k_fieldset3.'\',this)">'.$this->ar_subsubfieldsets_new[$k_fieldset3].'</span>';
						$str .= $this->ar_subsubfieldsets_new[$k_fieldset3];
						$str .= '</legend>';
						$str .= '<div id="co-fs-subsub-'.$id_fieldset.$k_fieldset3.'"><div>';
					}
					foreach ( $ar_subfields3 as $k_tr => $ar_tr )
					{
						$required_mark = (isset($ar_tr['req']) ? $ar_tr['req'].'&#160;' : '');
						/* ID for label */
						$str_label_id = '';
						if ( $this->is_label_ids && isset( $ar_tr['id'] ) )
						{
							$str_label_id = ' id="l-'.$ar_tr['id'].'" ';
						}
						if ( isset( $ar_tr['id'] ) )
						{
							$str .= '<label'.$str_label_id.' for="'.$ar_tr['id'].'">';
						}
						else
						{
							$str .= '<label>';
						}
						if (strpos($ar_tr['field'], 'type="checkbox"') !== false
							|| strpos($ar_tr['field'], 'type="radio"') !== false)
						{
							$str .= ($ar_tr['text'] ? '<em>'.($ar_tr['field'] ? $ar_tr['field'] : '').$ar_tr['text'].'</em>' : '');
						}
						else
						{
							$str .= ($ar_tr['text'] ? '<em>'.$required_mark.$ar_tr['text'].'</em> ' : '');
							$str .= ($ar_tr['field'] ? $ar_tr['field'] : '');
						}
						$str .= ($ar_tr['help'] ? ' <em class="tip">'.$ar_tr['help'].'</em>' : '');
						$str .= '</label> ';
					}
					if (isset($this->ar_subsubfieldsets_new[$k_fieldset3]))
					{
						$str .= '</div></div>';
						$str .= '</fieldset>';
						unset($this->ar_subsubfieldsets_new[$k_fieldset3]);
					}
				}
				if (isset($this->ar_subfieldsets_new[$k_fieldset2]))
				{
					$str .= '</div></div>';
					$str .= '</fieldset>';
					unset($this->ar_subfieldsets_new[$k_fieldset2]);
				}
			}
			$str .= '</div></div>';
			$str .= '</fieldset>';
			$str .= '</div>';
		}
#		print htmlspecialchars( $str );
		return $str;
	}
	/* */
	public function get_actions()
	{
		++$this->int_actions;
		
		$str = '';
		$str .= '<div id="co-fs-actions" class="fleft">';

		if ( $this->is_submit_ok )
		{
			if ( isset( $this->ar_tags['form']['id'] ) )
			{
				$this->set_tag( 'submitok', 'id', $this->ar_tags['form']['id'].'-submitok-'.$this->int_actions);
			}
			else
			{
				$this->ar_tags['form']['id'] = '';
			}
			/* Apply default onclick event */
			if ( !isset( $this->ar_tags['submitok']['onclick'] ) )
			{
				$this->set_tag( 'submitok', 'onclick', 'function forms3onclick(e){if(typeof(this.s)==\'undefined\'){'.
					'e.replaceChild(document.createTextNode(\''.$this->phrase_wait.'\'),e.firstChild);'.
					'e.style.cursor=\'wait\';document.forms[\''.$this->ar_tags['form']['id'].'\'].submit();}else{e.blur()}this.s=1;};forms3onclick(this);return false'
				);
			}
			$this->set_tag( 'submitok', 'title', $this->phrase_submit_ok );
			
			#$str .= '<input '.$this->http_build_query($this->ar_tags['submitok']).' type="submit" class="submitok" value="'.$this->phrase_submit_ok.'" />';

			$str .= '<a '.$this->http_build_query($this->ar_tags['submitok']).'>';
			$str .= $this->phrase_submit_ok;
			$str .= '</a>';
			
			$this->unset_tag( 'submitok', 'onclick' );
		}
		if ( $this->is_submit_cancel )
		{
			if ( isset( $this->ar_tags['form']['id'] ) )
			{
				$this->set_tag( 'submitcancel', 'id', $this->ar_tags['form']['id'].'-submitcancel-'.$this->int_actions);
			}
			else
			{
				$this->ar_tags['form']['id'] = '';
			}
			/* Apply default onlick event */
			if ( !isset( $this->ar_tags['submitcancel']['onclick'] ) )
			{
				$this->set_tag( 'submitcancel', 'onclick', 'history.back();return false' );
			}
			$this->set_tag( 'submitcancel', 'title', $this->phrase_submit_cancel );
			$str .= '<a '.$this->http_build_query($this->ar_tags['submitcancel']).'>';
			$str .= $this->phrase_submit_cancel;
			$str .= '</a>';
			$this->unset_tag( 'submitcancel', 'onclick' );
		}
		
		$str .= '</div>';
		if ( $this->is_htmlspecialchars )
		{
			$str = htmlspecialchars( $str );
		}
		return $str;
	}
	/* */
	public function new_fieldset_actions()
	{
		$s = '<div>';
		$s .= '<fieldset id="fs-actions">';
		$s .= '<legend onclick="return toggle_collapse(\'fs-actions\')"><span>';
		$s .= $this->phrase_actions;
		$s .= '</span></legend>';
		$s .= $this->get_actions();
		$s .= '</fieldset>';
		$s .= '</div>';
		return $s;
	}
	/* */
	public function form_output()
	{
		$str = '';

		$str .= '<div class="inp">';

		$str .= '<div '.$this->http_build_query( $this->ar_tags['div-wrapper'] ).'>';

		$str .=  $this->str_ajax_status;

		$str .= '<form '.$this->http_build_query( $this->ar_tags['form'] ).'>';

		$str .= $this->make_fieldsets();

		if ( $this->is_actions )
		{
			$str .= $this->new_fieldset_actions();
		}

		/* Hidden values */
		$str .= '<div>'.implode( '', $this->ar_fields_hidden ).'</div>';
		
		$str .= '</form>';
		$str .= '</div>';

		$str .= '<div class="submit-buttons">';
		$str .= $this->get_actions( $this->o->gv['action'] );
		$str .= '</div>';

		$str .= '</div>';

		return $this->_render($str);
	}


}
/* */
class site_forms3_validation extends site_forms3
{
	public $str, $ar_onoff, $ar_required, $cur_htmlform;
	public function __construct($o)
	{
		$this->set_tag( 'form', 'method', 'post' );
		$this->set_tag( 'form', 'enctype', 'application/x-www-form-urlencoded' );
		$this->set_tag( 'form', 'accept-charset', 'utf-8' );
		$this->set_tag( 'div-wrapper' );
		$this->set_tag( 'submitok', 'href', '#ajax-status' );
		$this->set_tag( 'submitok', 'class', 'submitok' );

//		$this->set_tag( 'submitok', 'onclick', 'jsF.formSubmit(this)' );

		$this->set_tag( 'submitcancel', 'href', '#'  );
		$this->set_tag( 'submitcancel', 'class', 'submitcancel' );

		$this->o =& $o;
		/* Called after submit */
		#$this->cur_htmlform = $this->o->V->path_views.'/'.$this->o->gv['target'].'__'.$this->o->gv['action'].'__onsubmit.php';
	}
	/* */
	public function target_menu(){}
	/* */
	public function get_form()
	{
		/* make html-code */
		return $this->str;
	}
	/* Display HTML-form before submitting a data */
	public function before_submit($ar = array())
	{
		$this->target_menu();
		/* default values for data */
		/* */
		return $this->get_form($ar);
	}
	/* */
	public function after_submit($ar = array())
	{
		/* validate input data */
		return $this->process($ar);
	}
	/* */
	public function on_error($ar)
	{
		$ar = $this->check_onoff($ar);
		return $this->get_form($ar);
	}
	/* */
	public function on_success($ar)
	{
		/* do something with $ar */
	}
	/* 15 Jan 2010: Added 3rd-lvl arrays */
	public function check_onoff($ar)
	{
		foreach ( $this->ar_onoff as $k1 => $v1 )
		{
			if ( is_array( $v1 ) )
			{
				foreach ( $v1 as $k2 => $v2 )
				{
					if ( is_array( $v2 ) )
					{
						foreach ( $v2 as $k3 => $v3 )
						{
							$ar[$k1][$k2][$v3] = isset( $ar[$k1][$k2][$v3] ) ? '1' : '0';
						}
					}
					else
					{
 						$ar[$k1][$v2] = isset( $ar[$k1][$v2] ) ? '1' : '0';
 					}
					
				}
			}
			else
			{
				$ar[$v1] = isset($ar[$v1]) ? '1' : '0';
			}
		}
		return $ar;
	}
	/* */
	private function _validate_iterate( $req, $ar )
	{
		if ( is_array( $req ) )
		{
			/* ... */
		}
		else if ( is_string( $req ) ) 
		{
			if ( !isset( $ar[$req] ) || ( isset( $ar[$req] ) && trim( $ar[$req] ) == '' ) )
			{
				$this->ar_broken[$req] = '';
			}
		}
	}
	private function validate($ar)
	{
		$this->ar_broken = array();

		foreach ( $this->ar_required as $k1 => $v1  )
		{
			if ( is_array( $v1 ) )
			{
				$this->_validate_iterate( $this->ar_required, $ar );
			}
			else if ( is_string( $v1 ) ) 
			{
				$this->_validate_iterate( $v1, $ar );
			}
		}
		if ( empty( $this->ar_broken ) )
		{
			return true;
		}
		return false;
	}
	/* */
	public function process($ar = array())
	{
		/* Custom error events */
		if ( $this->is_error )
		{
			return $this->on_error($ar);
		}
		if ( $this->validate($ar) )
		{
			return $this->on_success($ar);
		}
		return $this->on_error($ar);
	}
}

}
?>