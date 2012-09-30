<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 * 
 * Glossword Requirements Checker
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IS_IN_GW2')) { define('IS_IN_GW2', 1); }
if (!defined('GW2_THIS_SCRIPT')) { define('GW2_THIS_SCRIPT', 'index.php'); }

define('GW2_TPL_WEB_INDEX', 1);

/* */
error_reporting(E_ALL);
/* */
class gw_mini_site
{
	public $oTkit, $oHtml, $oTimer;
	/* Autoexec */
	function gw_mini_site()
	{
		$this->V = new gw_var_store(array(
			'is_debug_time' => 0,
			'is_debug_tkit' => 0,
			'is_debug_db' => 0,
			'is_show_debug_db' => 0,
			'visual-theme' => 'visual-theme',
			'path_locale' => 'locale',
			'path_views' => 'views',
			'path_css' => 'visual-theme',
			'path_js' => '.',
			'path_tpl' => 'visual-theme',
			'path_temp' => 'temp',
			'path_includes' => 'includes',
			'path_db' => 'includes',
			'file_index' => 'index.php',
			'version' => '1.8.12',
			'site_name' => 'Glossword',
			'site_desc' => 'Glossary compiler',
			'path_temp_app' => '../gw_temp',
			'file_lock' => '../gw_temp/install_lock.txt'
		));
	}
	/* Get a variable */
	public function g($variable = '')
	{
		if ($variable) { return $this->V->{$variable}; }
		else { return get_object_vars($this->V); }
	}
	/* Add a variable */
	public function a($variable, $value)
	{
		$this->V->{$variable} = $value;
	}
	/* */
	function init()
	{
		/* */
		$this->oTimer = $this->_init_timer('init');
		/* */
		$this->a( 'debug_memory_s', memory_get_usage() );

		/* Auto time for server */
		$this->a( 'time_req', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time() );
		$this->a( 'time_gmt', $this->V->time_req - @date('Z') );

		/* Get accepted encoding */
		$this->a('HTTP_ACCEPT_ENCODING', isset($_SERVER['HTTP_ACCEPT_ENCODING'])
				? $_SERVER['HTTP_ACCEPT_ENCODING']
				: (isset($_SERVER['HTTP_TE']) ? $_SERVER['HTTP_TE'] : ''));
		/* Get remote IP string */
		$this->a('REMOTE_ADDR', (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').
			(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown' ? ' FW: '.$_SERVER['HTTP_X_FORWARDED_FOR'] : '').
			(isset($_SERVER['HTTP_CLIENT_IP']) ? ' CLIENT_IP: '.$_SERVER['HTTP_CLIENT_IP'] : '').
			(isset($_SERVER['HTTP_VIA']) ? ' VIA: '.$_SERVER['HTTP_VIA'] : '') );

		/* Functions class */
		$this->oFunc = $this->_init_functions();
		
		switch ($this->gv['arv'])
		{
			case 'css':

			break;
			case 'js':

			break;
			default:
				/* HTML-templates */
				$this->oTpl = $this->_init_html_tpl();
				/* XMLReader */
				$this->oXml = $this->_init_xmlreader();
				/* Requirements Checker */
				$this->oChecker = $this->_init_reqchecker();
				/* HTML-tags */
				$this->oHtmlTags = $this->_init_html_tags();
				
				$this->set_steps();
				$this->oTpl->addVal( 'v:favicon', 'favicon.ico' );
				$this->ar_broken = array();
			break;
		}
		/* Translation Kit */
		$this->oTkit = $this->_init_tkit(array('gwreqcheck-global', 'gwinstall-global'), $this->gv['il']);
		/* Tkit: Correct interface language */
		$this->gv['il'] = $this->oTkit->arL['lang_uri'];
		/* */
		$this->oHtml = $this->_init_html();
		/* */
		$this->oTimer->end('init');
		$this->oTimer->start('proc');
	}

	/**
	 *
	 */
	/* */
	function _init_functions()
	{
		include_once( $this->V->path_includes.'/functions.php' );
		return new tkit_functions;
	}
	function _init_reqchecker()
	{
		include_once( $this->V->path_includes.'/reqchecker.php' );
		return new gw_reqcheck;
	}
	function _init_xmlreader()
	{
		include_once( $this->V->path_includes.'/xml_reader5.php' );
		return new gw2_xmlreader5;
	}
	function _init_tkit($ar_tkit_profiles, $il)
	{
		include_once( $this->g('path_includes').'/class.tkit.php' );
		$o = new tkit;
		$o->path_locale = $this->g('path_locale');
		$o->is_debug = $this->g('is_debug_tkit');
		/* Tkit: Load phrases */
		$o->import_tag($ar_tkit_profiles, $il);
		return $o;
	}
	/* */
	function _init_html()
	{
		include_once( $this->g('path_includes').'/class.html_gw2.php' );
		$o = new gw2_html;
		$o->path_css = $this->g('path_css');
		$o->path_js = $this->g('path_js');
		$o->HTTP_ACCEPT_ENCODING =& $this->g('HTTP_ACCEPT_ENCODING');
		return $o;
	}
	/* */
	function _init_html_tags()
	{
		include_once( $this->g('path_includes').'/class.html_tags.php' );
		$o = new gw2_html_tags;
		return $o;
	}
	/* */
	function _init_html_tpl()
	{
		$this->a('is_tpl_show_names', 0);
		include_once($this->g('path_includes').'/class.tpl.php');
		include_once($this->g('path_includes').'/class.template.ext.php');
		$o = new tkit_template();
#		$o->oDb =& $this->oDb;
		$o->init($this->g('visual-theme'));
		$o->path_source = $this->g('path_tpl');
		$o->path_cache = $this->g('path_temp');
		$o->tmp['d'] = array();
		return $o;
	}
	/* */
	function _init_db($ar_params = '', $is_return = FALSE)
	{
		define('BASEPATH', '');
		define('EXT', '.php');
		if (!class_exists('CI_Exceptions'))
		{
			include($this->g('path_includes').'/Exceptions.php');
		}
		include_once($this->g('path_includes').'/DB.php');
		$ar_params['hostname'] = $ar_params['db_host'];
		$ar_params['username'] = $ar_params['db_user'];
		$ar_params['database'] = $ar_params['db_name'];
		$ar_params['password'] = $ar_params['db_pass'];
		$ar_params['dbprefix'] = $ar_params['db_prefix'];
		$ar_params['dbdriver'] = $ar_params['db_type'];
		$ar_params['pconnect'] = false;
		$ar_params['active_r'] = true;
		$ar_params['db_debug'] = false;
		$ar_params['db_debug_q'] = false;
		$ar_params['cache_on'] = false;
		$ar_params['cachedir'] = '';
		if ($is_return === TRUE)
		{
			return ci_db( $ar_params, $this->g('path_includes') );
		}
		$oDb = '';
		$oDb = ci_db( $ar_params, $this->g('path_includes') );
		return $oDb;
	}
	/* */
	function _init_db_forge()
	{
		#include_once($this->g('path_includes').'/DB_forge.php');
		#return new CI_DB_forge;
	}
	/* */
	function _init_timer($prefix = '')
	{
		return new gw_mini_timer($prefix);
	}
	/**
	 *
	 */
	
	/**
	 * 
	 */
	
	function page_body()
	{

		$file_to_function = $this->V->path_views.'/'.$this->cur_function.'.php';
		$ar_required = $ar_onoff = array();
		/* */
		switch ($this->gv['arv'])
		{
			case 'css':
			case 'js':
			break;
			default:
				if (file_exists($file_to_function))
				{
					include($file_to_function);
				}
			break;
		}
	}
	/**
	 * Puts Translation Kit variables into HTML-template class.
	 * Unsets phrases. Should be called at the end.
	 */
	function import_tkit_phrases()
	{
		$a =& $this->oTkit->get_phrases_all();
		if (is_array($a))
		{
			for (reset($a); list($k, $v) = each($a);)
			{
				$this->oTpl->addVal('l:'.$k, $v);
				unset($a[$k]);
			}
		}
	}
	/**
	 * Managing installation steps
	 */
	function set_steps($int_steps = 0 )
	{
		$this->ar_steps = array();
		if ($int_steps < 0){ $int_steps = 0; }
		for ($i = 1; $i <= $int_steps; $i++)
		{
			$this->ar_steps[$i] = $i;
		}
	}
	function get_next_step($this_step)
	{
		return isset($this->ar_steps[$this_step+1]) ? $this->ar_steps[$this_step+1] : 0;
	}
	function get_html_steps()
	{
		$ar = array();
		foreach ($this->ar_steps as $k => $step)
		{
			/* Step n */
			$ar[$step] = '{l:10001} '.$step;
			if ($step == $this->gv['step'])
			{
				$ar[$step] = '<em>'.$ar[$step].'</em>';
			}
		}
		return implode(' &#8226; ', $ar);
	}
	/**
	 * 
	 */
	function import_topics_file($filename)
	{
		$this->oDb->truncate('topics');
		$this->oDb->truncate('topics_phrase');
		$this->oXml->is_skip_root = true;
		$arData = $this->oXml->get( $filename );
		for (reset($arData['topic']); list($k1, $arV1) = each($arData['topic']);)
		{
			$id_topic = $arV1['attributes']['id'];
			for (reset($arV1['value']); list($k2, $arV2) = each($arV1['value']);)
			{
				$arV2 = $arV2[0];
				switch ($arV2['tag'])
				{
					case 'parameters':
						$q1 = unserialize( $arV2['value'] );
						$q1['id_topic'] = $q2['id_topic'] = $id_topic;
					break;
					case 'entry':
						for (reset($arV2['value']); list($k3, $arV3) = each($arV2['value']);)
						{
							for (reset($arV3); list($k4, $arV4) = each($arV3);)
							{
								$id_lang = $arV4['attributes']['xml:lang'];
								for (reset($arV4['value'] ); list($k5, $arV5) = each($arV4['value'] );)
								{
									$arV5 = $arV5[0];
									$q2[$arV5['tag']] = $arV5['value'];
								}
								$q2['id_lang'] = $id_lang.'-utf8';
								$this->oDb->insert('topics_phrase', $q2);
							}
						}
					break;
				}
			}
			if (!isset($q1['date_created']))
			{
				$q1['date_created'] = $q1['date_modified'] = $this->g('time_gmt');
			}
			$this->oDb->insert('topics', $q1);
		}
		return true;
	}
	function import_custom_pages_file($filename)
	{
		$this->oDb->truncate('pages');
		$this->oDb->truncate('pages_phrase');
		$this->oXml->is_skip_root = true;
		$arData = $this->oXml->get( $filename );
		for (reset($arData['custom_page']); list($k1, $arV1) = each($arData['custom_page']);)
		{
			$id_page = $arV1['attributes']['id'];
			for (reset($arV1['value']); list($k2, $arV2) = each($arV1['value']);)
			{
				$arV2 = $arV2[0];
				switch ($arV2['tag'])
				{
					case 'parameters':
						$q2 = array();
						$q1 = unserialize($arV2['value']);
						$q1['id_page'] = $q2['id_page'] = $id_page;
					break;
					case 'entry':
						for (reset($arV2['value']); list($k3, $arV3) = each($arV2['value']);)
						{
							for (reset($arV3); list($k4, $arV4) = each($arV3);)
							{
								$id_lang = $arV4['attributes']['xml:lang'];
								for (reset($arV4['value'] ); list($k5, $arV5) = each($arV4['value'] );)
								{
									$arV5 = $arV5[0];
									$q2[$arV5['tag']] = $arV5['value'];
								}
								$q2['id_lang'] = $id_lang.'-utf8';
								if (!isset($q2['page_descr']))
								{
									$q2['page_descr'] = '';
								}
								if (!isset($q2['page_keywords']))
								{
									$q2['page_keywords'] = '';
								}
								if (!isset($q2['page_content']))
								{
									$q2['page_content'] = '';
								}
								$this->oDb->insert('pages_phrase', $q2);
							}
						}
					break;
					default:
						/* page_php_1, page_php_2 */
						$q1[$arV2['tag']] = $arV2['value'];
					break;
				}
			}
			if (!isset($q1['page_php_1']))
			{
				$q1['page_php_1'] = '';
			}
			if (!isset($q1['page_php_2']))
			{
				$q1['page_php_2'] = '';
			}
			/* Import by admin */
			$q1['id_user'] = '2';
			$q1['date_created'] = $q1['date_modified'] = $this->g('time_gmt');
			$this->oDb->insert('pages', $q1);
		}
		return true;
	}
	/* */
	function import_visual_themes_file($filename)
	{
		$this->oXml->is_skip_root = false;
		$arData = $this->oXml->get( $filename );
		$q1 = $arData['style'][0]['attributes'];
		list($q1['v1'], $q1['v2'], $q1['v3']) = explode('.', $q1['version']);
		unset($q1['version']);
		$theme_dir = $q1['id_theme'];
		/* Compatibility with old database */
		$q1['id_theme'] = str_replace('_', '\_',  $q1['id_theme']);
		/* Clear settings for existent theme */
		$this->oDb->delete( 'theme', array('id_theme' => $q1['id_theme']) );
		$this->oDb->delete( 'theme_settings', array('id_theme' => $q1['id_theme']) );
		/* Insert new */
		$this->oDb->insert( 'theme', $q1 );
		
		for (reset($arData['style'][0]['value']['group']); list($k1, $arV1) = each($arData['style'][0]['value']['group']);)
		{
			$id_group = $arV1['attributes']['id'];
			for (reset($arV1['value']); list($k2, $arV2) = each($arV1['value']);)
			{
				for (reset($arV2); list($k3, $arV3) = each($arV2);)
				{
					switch ($id_group)
					{
						case 'settings';
							/* Compatibility with old database */
							$arV3['attributes']['key'] = str_replace('_', '\_',  $arV3['attributes']['key']);
		
							$q2[$k3]['id_theme'] = $q1['id_theme'];
							$q2[$k3]['date_modified'] = $this->g('time_gmt');
							$q2[$k3]['settings_key'] = $arV3['attributes']['key'];
							$q2[$k3]['settings_value'] = $arV3['value'];
							$q2[$k3]['settings_value'] = str_replace('&lt;![CDATA[', '<![CDATA[', $q2[$k3]['settings_value']);
							$q2[$k3]['settings_value'] = str_replace(']]&gt;', ']]>', $q2[$k3]['settings_value']);
							$q2[$k3]['code'] = '';
							$q2[$k3]['code_i'] = '';
						break;
						case 'binary';
							$filename = $this->g('path_temp_app').'/t/'.$theme_dir.'/'.$arV3['attributes']['key'];
							$this->oFunc->file_put_contents($filename, pack("H".strlen($arV3['value']), $arV3['value']), 'w');
						break;
					}
				}
			}
		}
		/* Multiple INSERTs */
		/* 339 -> 62 */
		$this->oDb->insert('theme_settings', $q2 );
		return true;
	}
	/* */
	function import_custom_az_file($filename)
	{
		$this->oXml->is_skip_root = true;
		$arData = $this->oXml->get( $filename );
		$q2 = array();
		$q1 = $arData['custom_az'][0]['attributes'];
		/* -- Create a new -- */
		$id_profile = 1;
		$this->oDb->select_max('id_profile');
		$query = $this->oDb->get('custom_az_profiles');
		foreach ($query->result() as $row)
		{
			$id_profile += $row->id_profile;
		}
		$q1['id_profile'] = $id_profile;
		$this->oDb->insert('custom_az_profiles', $q1);
		/* */
		for (reset($arData['custom_az'][0]['value']['entry']); list($k1, $arV1) = each($arData['custom_az'][0]['value']['entry']);)
		{
			$q2[$k1]['id_profile'] = $id_profile;
			for (reset($arV1['value']); list($k2, $arV2) = each($arV1['value']);)
			{
				for (reset($arV2); list($k3, $arV3) = each($arV2);)
				{
					$q2[$k1][$arV3['tag']] = $arV3['value'];
				}
			}
			/* text_str2ord */
			$int_len = strlen($q2[$k1]['az_value']);
			$t = '';
			for ($i = 0; $i < $int_len; $i++)
			{
				$t .= ord(substr($q2[$k1]['az_value'], $i, 1));
			}
			$q2[$k1]['az_int'] = $t;
		}
		/* Multiple INSERTs */
		/* 528 -> 76 */
		$this->oDb->insert('custom_az', $q2 );
		return true;
	}
	
	
	/**
	 * 
	 */
	function page_header()
	{
		/* Construct function name */
		$this->cur_function = $this->gv['target'].'_'.$this->gv['action'];

		/* */
		switch ($this->gv['arv'])
		{
			case 'css':
			case 'js':
			break;
			default:
				/* Check for locked installation */
				if (file_exists($this->g('file_lock')))
				{
					$real_filename = realpath(dirname($this->g('file_lock'))) ? realpath(dirname($this->g('file_lock'))).'/'.basename($this->g('file_lock')) : $this->g('file_lock');
					$real_filename = str_replace('\\', '/', $real_filename);
					print '<div style="padding:1em;font: 100% sans-serif;">'.$this->oTkit->_(20017, '<samp>'.$real_filename.'</samp>').'</div>';
					exit;
				}
				/* Add header by default */
				$this->oHtml->append_html_title( $this->oTkit->_(20000).': '.$this->g('site_name').' '.$this->g('version') );
			break;
		}
	}
	/* */
	function page_footer()
	{
		$this->oTimer->end('proc');
		
		switch ($this->gv['arv'])
		{
			case 'css':
				header('Content-type: text/css');
				$this->oHtml->css_a_file( $this->gv['file'] );
				$this->oHtml->css_a( 'v:path_css', $this->g('path_css') );
				print $this->oHtml->css_g();
				return;
			break;
			case 'js':
				header('Content-type: javascript/text');

			break;
			default:
				$this->import_tkit_phrases();
				$this->oTpl->addVal( 'v:steps', $this->get_html_steps() );

				$this->oTpl->addVal( 'v:html_title', $this->oHtml->get_html_title() );
				$this->oTpl->addVal( 'v:file_index', GW2_THIS_SCRIPT );
				$this->oTpl->addVal( 'v:xml-lang', $this->oTkit->arL['isocode3'] );
				$this->oTpl->addVal( 'v:text_direction', $this->oTkit->arL['direction'] );
				$this->oTpl->addVal( 'v:charset', 'utf-8' );
				$this->oTpl->addVal( 'v:path_css', $this->g('path_css') );
				$this->oTpl->addVal( 'v:version', $this->g('version') );
				$this->oTpl->addVal( 'v:il', $this->gv['il'] );
				$this->oTpl->addVal( 'v:form_action', $this->g('file_index') );

				/* */
				header('Content-type: text/html');

				# '<'.'?xml version="1.0" encoding="UTF-8"?'.'>

				/* Set HTML-templates */
				$this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);
				/* Parse dynamic blocks */
				for (reset($this->oTpl->tmp['d']); list($id_dynamic, $arV) = each($this->oTpl->tmp['d']);)
				{
					if (is_array($arV))
					{
						for (reset($arV); list($k2, $v2) = each($arV);)
						{
							for (reset($v2); list($k, $v) = each($v2);)
							{
								$this->oTpl->assign(array($k => $v));
							}
								$this->oTpl->parseDynamic($id_dynamic);
						}
					}
					else
					{
						$this->oTpl->parseDynamic($id_dynamic);
					}
					unset($this->oTpl->tmp['d'][$id_dynamic]);
				}
				
				/* */
				$str_debug_sql = '';
				$query_count = 0;
				$db_time = 0;
				if (isset($this->oDb->queries))
				{
					$query_count = $this->oDb->query_count;
					if ($this->g('is_show_debug_db') )
					{
						for (;list($k, $v) = each($this->oDb->queries);)
						{
							$this->oDb->queries[$k] = str_replace('{', '&#123;', $v);
						}
						$str_debug_sql .= '<ol title="database"><li>' . implode('</li><li>', $this->oDb->queries) . '</li></ol>';
					}
					$db_time = $this->oDb->elapsed_time(3);
				}
				if ($this->g('is_debug_time'))
				{
					$time_php = $this->oTimer->_('init') + $this->oTimer->_('proc');
					$this->oTpl->addVal( 'v:debug', '<div class="debugwindow">'.
						'Total: <strong>'.sprintf("%1.3f", $time_php+$db_time).'</strong> - '.
						'PHP: <strong>'.sprintf("%1.3f", $time_php).
						'</strong> (init: '.$this->oTimer->_('init').' + proc: '.$this->oTimer->_('proc').
						') - SQL: <strong>'.$db_time.'</strong> (Queries: <strong>'.$query_count.'</strong>) - Memory, bytes: '. $this->oTkit->number_format( memory_get_usage() - $this->g('debug_memory_s') ).
						' - '.$this->V->path_views.'/'.$this->cur_function.'.php'.
						$str_debug_sql.
						'</div>'
					);
				}
				/* */
				/* Compile HTML-template */
				$this->oTpl->parse();
				$this->oHtml->append( $this->oTpl->output() );
			break;
		}
		/* */
		print $this->oHtml->g();
	}
	/* */
	function global_variables($ar = array())
	{
		include_once( $this->g('path_includes').'/class.register_globals.php' );
		$oGlobals = new tkit_register_globals( $ar );
		$this->gv = $oGlobals->register( $ar );

		/* Shorthand $this->gv['arg']['var'] => $this->gv['var'] */
		if (is_array($this->gv['arg']))
		{
			foreach( $this->gv['arg'] as $k => $v )
			{
				$this->gv[$k] = $v;
				unset( $this->gv['arg'][$k] );
			}
		}
		/* */
		$oGlobals->do_default( $this->gv['target'], 'chooselanguage' );
		$oGlobals->do_default( $this->gv['il'], 'english' );
		$oGlobals->do_default( $this->gv['step'], '1' );
		
		/* Filter incoming data */
		$oGlobals->do_alphanum( $this->gv['target'] );
		$oGlobals->do_alphanum( $this->gv['action'] );
		$oGlobals->do_alphanum( $this->gv['file'] );
		$oGlobals->do_alphanum( $this->gv['il'] );
		$oGlobals->do_alphanum( $this->gv['step'] );

		#unset($oGlobals);
	}
}
/* */
class gw_var_store {
	public function __construct($a)
	{
		foreach( $a as $k => $v )
		{
			$this->$k = $v;
		}
	}
}

class gw_mini_timer{public $p,$a;function gw_mini_timer($p=''){$this->start($p);}function start($p=''){$this->p=$p?$p:mktime();$this->a[$this->p.'s']=array_sum(explode(' ',microtime()));}function end($p=''){$p=$p?$p:$this->p;$this->a[$p.'e']=array_sum(explode(' ',microtime()));if(isset($this->a[$p.'s'])){$this->a[$p]=sprintf("%1.5f",$this->a[$p.'e']-$this->a[$p.'s']);return $this->a[$p];}return 0;}function _($p=''){return $this->get($p);}function get($p=''){if(isset($this->a[$p])){return $this->a[$p];}return $this->a;}}


/* */
$o = new gw_mini_site;
/* Register global variables */
$o->global_variables(array('arg','arp','arv'));
$o->init();
$o->page_header();
$o->page_body();
$o->page_footer();

?>