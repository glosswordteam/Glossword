<?php
/*
Usage:

-- HTML (e.tpl.html):

<ul>
{d START BLOCK}
<li> I would say that {n} times.</li>
<li> Here - {global} </li>
{/d}

см. {global}

</ul>

-- PHP (e.php):

include('class.site_template.php');
$oTpl = new site_class_templates;
$oTpl->new_template('e.tpl.html');
$oTpl->assign( 'ctime', @date("Y-M-d") );
for ($i = 1; $i < 5; $i++)
{
	$oTpl->assign('n', $i);
	$oTpl->parse_block('number');
}
$oTpl->assign_global('global', 'Глобальная переменная');

echo $oTpl->get_html();
// $oTpl->display();

*/
class site_class_templates
{
	/* All variables for the template */
	public $ar_variables = array();
	public $ar_variables_global = array();
	/* */
	public $phrase_not_found = 'Not found';
	/* */
	public $cfg;
	/* */
	public $ar_compiled = array();
	public $ar_run = array();
	public $ar_block_v = array();
	public $ar_block_i = array();
	public $ar_block_c = array();
	public $ar_file_events = array();
	/* */
	public $ar_d = array();

	/* */
	public function __construct($ar_cfg = array())
	{
		$ar_cfg_default = array(
			'template_extension' => '', /* Extensions for HTML-files */
			'path_source' => '.', /* Path to HTML-files */
			'path_cache' => 'cache' /* Path to store compiled PHP-code */
		);
		/* */
		foreach ($ar_cfg as $k => $v)
		{
			$ar_cfg_default[$k] = $v;
		}
		/* */
		$this->cfg =& $ar_cfg_default;
		/* Start new parser object */
		$this->oParser = new site_class_templates_parser();
	}
	/**
	 * Assigns a local variable.
	 *
	 * Usage:
	 * $oTpl->assign( array('varname' => 'value') );
	 * $oTpl->assign( 'varname', 'value' );
	 *
	 * @param $varname String or Array
	 * @param $value String
	 * @access public
	 */
	public function assign($varname, $value = '')
	{
		$str = '';
		if (is_array($varname))
		{
			foreach ($varname as $k => $v)
			{
				/* $k is variable, $variable => crc32($variable) */
				$this->ar_variables[$this->_parse_var($k)] = strval($v);
			}
		}
		else
		{
			$this->ar_variables[$this->_parse_var($varname)] = strval($value);
		}
	}
	/**
	 * Assigns a global variable.
	 *
	 * Usage:
	 * $oTpl->assign( array('varname' => 'value') );
	 * $oTpl->assign( 'varname', 'value' );
	 *
	 * @param $varname String or Array
	 * @param $value String
	 * @access public
	 */
	public function assign_global($varname, $value = '')
	{
		if (is_array($varname))
		{
			foreach ($ar as $k => $v)
			{
				/* $k is variable, $variable => crc32($variable) */
				$this->ar_variables_global[$this->_parse_var($k)] = strval($v);
			}
		}
		else
		{
			$this->ar_variables_global[$this->_parse_var($varname)] = strval($value);
		}
	}
	
	
	
	/* Shorthand for new_template_file() */
	public function new_template($template)
	{
		return $this->new_template_file($template);
	}
	/**
	 * Reads HTML-template and compiles it into PHP-code. File-based version.
	 */
	public function new_template_file($template)
	{
		$k = 0;
		$ar[$k]['settings_key'] =& $template;
		$ar[$k]['date_modified'] = $ar[$k]['date_compiled'] = 0;
		$ar[$k]['code'] = $ar[$k]['code_i'] = $ar[$k]['settings_value'] = '';
		/* if a compiled PHP-code exists */
		if (file_exists($this->cfg['path_cache'].'/code-'.$template.'.php'))
		{
			$ar[$k]['date_compiled'] = filemtime($this->cfg['path_cache'].'/code-'.$template.'.php');
			$ar[$k]['code'] = implode('', file($this->cfg['path_cache'].'/code-'.$template.'.php'));
			$ar[$k]['code_i'] = implode('', file($this->cfg['path_cache'].'/code_i-'.$template.'.php'));
		}
		/* Read the contents of file */
		if (file_exists($this->cfg['path_source'].'/'.$template.$this->cfg['template_extension']))
		{
			$ar[$k]['date_modified'] = filemtime($this->cfg['path_source'].'/'.$template.$this->cfg['template_extension']);
			/* Load a already compiled contents */
			if ($ar[$k]['date_compiled'] <= $ar[$k]['date_modified'])
			{
				$ar[$k]['settings_value'] = implode('', file($this->cfg['path_source'].'/'.$template.$this->cfg['template_extension']));
				$ar[$k]['settings_value'] = str_replace(array('{%', '%}'), array('{', '}'), $ar[$k]['settings_value']);
			}
		}
		else
		{
			print '<div>'.$this->phrase_not_found.': '.$this->cfg['path_source'].'/'.$template.$this->cfg['template_extension'].'</div>';
		}
		/* For each loaded template */
		foreach ($ar as $k => $ar_v)
		{
			/* Iterations in blocks */
			$ar_block_i = array();

			/* Create new template key to use in arrays with compiled code */
			$template_key = sprintf("%u", crc32($ar_v['settings_key']));

			/* Should be empty on first run */
			if (isset($this->ar_compiled[$template_key]))
			{
				/* Do not load HTML-file second time */
				continue;
			}
			/* Put template into array with compiled code */
			$this->ar_compiled[$template_key] = array(
					'filename' => $ar_v['settings_key'],
					'code' => $ar_v['code'],
					'html' => $ar_v['settings_value']
			);

			/* Load compiled contents */
			if ($ar_v['date_modified'] < $ar_v['date_compiled'])
			{
				/* Runs before $this->_parse() */
				eval(' ?'.'>' . $ar_v['code_i'] . '<?php ');
			}
			else
			{
				/* Compile new PHP-code */
				eval( $this->_compile($template_key) );
			}

			/* Merge local iterations with global iteration */
			foreach ($ar_block_i as $k => $v)
			{
				$this->ar_block_i[$k] = $v;
			}
			#$this->ar_block_i = array_merge($this->ar_block_i, $ar_block_i);
		}
	}
	/**
	 * Compiles PHP-code. Runs iterations ($ar_block_i).
	 */
	public function _compile($template_key)
	{
		$str_return = '';
		/* Reset settings */
		$this->oParser->_reset();
		/* */
		$tmp = array();
		$tmp['filename_c'] = '';
		$tmp['str_i'] = $tmp['str'] = '';

		/* Search for the template and source HTML-code for it. */
		if (!isset($this->ar_compiled[$template_key]) && !isset($this->ar_compiled[$template_key]['html']))
		{
			return false;
		}

		/* */
		$ar_html = array();

		/* */
		$ar_php = array();

		/* HTML-code to compile */
		$tmp['tpl_content'] =& $this->ar_compiled[$template_key]['html'];
		/* */
		$tmp['filename_c'] =& $this->ar_compiled[$template_key]['filename'];
		/* */
		$tmp['filename_i'] =& $this->ar_compiled[$template_key]['filename'];

		/* Prepare replacements pairs */
		$ar_html = $ar_php = array();

		/* Fix for XML prolog in HTML-file */
		$ar_html[] = '<'.'?xml';
		$ar_php[] = '<'.'?php echo "<","?xml"; ?'.'>';

		/* Search for blocks and variables */
		$preg = "/(<!--[ ]?(START|END) BLOCK: (.+)-->|{([A-Za-z0-9\.:\/\-_]+)})/i";
#		$preg = "/({)([ A-Za-z0-9:\/_-]+)(})/i";
		if ( preg_match_all($preg, $tmp['tpl_content'], $tmp['tpl_matches']) )
		{
			foreach ($tmp['tpl_matches'][1] as $k => $varblock_name)
			{
				$ar_html[] = $tmp['tpl_matches'][1][$k];
				$tmp['variable'] = trim($tmp['tpl_matches'][4][$k]);
				$tmp['block_name'] = trim($tmp['tpl_matches'][3][$k]);

				if (strstr($varblock_name, 'START'))
				{
					/* Block starts */
					$ar_php[] = $this->oParser->start_block($tmp['block_name']);
				}
				elseif (strstr($varblock_name, 'END'))
				{
					/* Block ends */
					$ar_php[] = $this->oParser->end_block($tmp['block_name']);
				}
				else
				{
					/* The variable appears */
					$ar_php[] = $this->oParser->_var($tmp['variable']);
				}
			}
		}
		$tmp['str'] = str_replace($ar_html, $ar_php, $tmp['tpl_content']);

		/* Save PHP-code */
		$this->_file_save($tmp['filename_c'], $tmp['str'], 'code');
		/* Display code on the first run */
		$this->ar_compiled[$template_key]['code'] = $tmp['str'];

		/* save new iteration */
		$str_internal = $this->oParser->make_iterations();

		$tmp['str_i'] = '<'.'?php'. "\n" . $str_internal . '?'.'>';

		/* Save iterations */
		$this->_file_save($tmp['filename_i'], $tmp['str_i'], 'code_i');

		return $str_internal;
	}
	/* */
	public function display()
	{
		$this->_parse();
		echo $this->ar_variables[$this->last_parsed];
	}
	/* */
	public function get_html()
	{
		$this->_parse();
		return $this->ar_variables[$this->last_parsed];
	}

	/**
	 * Executes compiled PHP-code and assigned variables.
	 */
	private function _parse( $varname = '')
	{
		$this->last_parsed = $varname.'_last';
		if ( isset( $this->ar_variables[$this->last_parsed] ) )
		{
			return $this->ar_variables[$this->last_parsed];
		}
		/* */
		ob_start();
		foreach ($this->ar_compiled as $k => $ar_v)
		{
			@eval (' ?'.'>' . $ar_v['code'] . '<?'.'php ');
			/* Clean up */
			unset( $this->ar_compiled[$k] );
		}
		$this->ar_variables[$this->last_parsed] = ob_get_clean();
	}

	/* Saves PHP-code */
	private function _file_save($filename, $contents, $prefix)
	{
		$filename = $this->cfg['path_cache'].'/'.$prefix.'-'.$filename.'.php';
		/* Debug events */
		$this->ar_file_events[] = $filename;
		/* */
		$this->file_put_contents($filename, $contents, 'w');
	}

	/* Alias for parse_block() */
	public function new_block($blockname)
	{
		$this->parse_block($blockname);
	}

	/**
	 * Assigns variables from $this->ar_variables to $this->ar_block_v.
	 * Called on each step inside the loop.
	 *
	 * @access public
	 */
	public function parse_block($blockname)
	{
		/* Optimize block name */
		$blockname = $this->_parse_var($blockname);

		/* Link to all variables in the current iteration */
		$vars =& $this->ar_block_i[$blockname]['var'];

		/* Link to an array */
		$linked_v =& $this->ar_block_v[$blockname][];

		/* Search for variable names in the block */
		if (is_array($vars))
		{
			foreach ($vars as $k => $varname)
			{
				@$linked_v[$varname] = $this->ar_variables[$varname];
			}
		}

		/* Search for childs */
		if (isset($this->ar_block_i[$blockname]['childs']) && is_array($this->ar_block_i[$blockname]['childs']))
		{
			foreach ($this->ar_block_i[$blockname]['childs'] as $k => $child)
			{
				$this->ar_block_v[$child][] = 'end';
			}
		}
	}

	/**
	 * Function used inside PHP-code.
	 * Start block. Used in while(_bStart('blockname'))
	 */
	public function _bStart($blockname)
	{
		static $block;

		/* while the last element of compiled blocks is not the given block */
		if (@end($this->ar_block_c) != $blockname)
		{
			$this->ar_block_c[] = $blockname;
		}

		/* See parse_block() for 'end' */
		if (!(list($k, $this->ar_run[$blockname]) = @each($this->ar_block_v[$blockname])) ||
			$this->ar_run[$blockname] == 'end')
		{
			/* remove the last element from array */
			array_pop($this->ar_block_c);
			return false;
		}
		return true;
	}

	 /**
	  * Reserved function. Can be executed at the end of block.
	  */
	 public function _bEnd($blockname)
	 {
	 	return true;
	 }


	 /**
	  * Retrieves value from the variable.
	  * $this->_v('varname') --> 123
	  */
	 public function _v($varname)
	 {
		$str = '';

		/* search for the variable in block */
		if ( $cur_block = @end($this->ar_block_c) )
		{
			/* a dynamic block */
			$str =& $this->ar_run[$cur_block][$varname];
		}
		else
		{
			/* a static variable */
			$str =& $this->ar_variables[$varname];
		}

		/* Global variable overwrites a local. */
		if (isset($this->ar_variables_global[$varname]))
		{
			$str =& $this->ar_variables_global[$varname];
		}

		echo $str;
	 }


	/**
	 * Optimizes variable or block name. Used on parsing source HTML-code.
	 */
	public static function _parse_var($varname)
	{
		#return $varname;
		return sprintf("%u", crc32($varname));
	}

	/**
	 * Put contents into a file. Binary and fail safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @param   string  $contents File contents
	 * @param   string  $mode [ w = write new file (default) | a = append ]
	 * @return  TRUE if success, FALSE otherwise
	 */
	static public function file_put_contents($filename, $contents, $mode = "w")
	{
		/* Correct Windows path */
		$filename = str_replace('\\', '/', $filename);
		/* Write a new file */
		if (!file_exists($filename))
		{
			/* Check & create directories first */
			$ar_parts = explode('/', $filename);
			$int_parts = (sizeof($ar_parts) - 1);
			$folder = '';
			for ($i = 0; $i < $int_parts; $i++)
			{
				$folder .= $ar_parts[$i].'/';
				if (is_dir($folder))
				{
					continue;
				}
				else
				{
					$oldumask = umask(0);
					@mkdir($folder, 0777);
					@chmod($folder, 0777);
					umask($oldumask);
				}
			}
			/* Nothing to write */
			if ($contents == '')
			{
				return true;
			}
			/* Write to file */
			$fp = @fopen($filename, 'wb');
			@chmod($filename, 0777);
			if ($fp)
			{
				fputs($fp, $contents);
			}
			else
			{
				return false;
			}
			fclose($fp);
		}
		else
		{
			/* Append to file, binary mode is transparent */
			if ($fp = @fopen($filename, $mode.'b'))
			{
				$is_allow = flock($fp, 2); /* lock for writing & reading */
				if ($is_allow)
				{
					fputs($fp, $contents, strlen($contents));
				}
				flock($fp, 3); /* unlock */
				fclose($fp);
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	/* Debug purposes */
	public function get_variables()
	{
		return array(
			'ar_block_i' => $this->ar_block_i,
			'ar_block_c' => $this->ar_block_c,
			'ar_block_v' => $this->ar_block_v,
			'ar_compiled' => $this->ar_compiled,
			'ar_variables' => $this->ar_variables,
			'ar_variables_global' => $this->ar_variables_global,
		);
	}




}
/**
 *
 */
class site_class_templates_parser extends site_class_templates
{
	/* */
	public function __construct()
	{
		$this->_reset();
	}
	/**
	 *
	 */
	public function _reset()
	{
		$this->ar_block_v = array();
		$this->ar_block_c = array();
		$this->ar_block_i = array();
		$this->ar_childs = array();
		$this->cur_level = 0;
	}
	/**
	 * Construct iteration array.
	 */
	 public function make_iterations($is_delete = 1)
	 {
		$ar_str = array();

		foreach ($this->ar_block_i as $block => $info)
		{
			if (isset($info['var']) && is_array($info['var']))
			{
				foreach ($info['var'] as $k => $v)
				{
					$ar_str[] = '$ar_block_i[\''.$block.'\'][\'var\'][] = \''.$v.'\';';
				}
			}
			if (isset($info['childs']) && is_array($info['childs']))
			{
				foreach ($info['childs'] as $k => $child)
				{
					$ar_str[] = '$ar_block_i[\''.$block.'\'][\'childs\'][] = \''.$child.'\';';
				}
			}
		}
		if ($is_delete)
		{
			$this->ar_blocks_c = $this->ar_block_i = array();
		}
		return implode("\n", $ar_str);
	 }


	/**
	 * Used to compile the variable defined in HTML-code into PHP-code.
	 */
	 public function _var($variable)
	 {
		$variable = $this->_parse_var($variable);
		if ($cur_block = @end($this->ar_block_c))
		{
			$this->ar_block_i[$cur_block]['var'][] = $variable;
		}
		return '<'.'?php $this->_v(\''.$variable.'\');?>';
	}

	/**
	 * Starts the block. Called on parsing source HTML-code.
	 */
	public function start_block($block_name)
	{
		/* Optimize block name */
		$block_name = $this->_parse_var($block_name);
		if (@end($this->ar_block_c))
		{
			$this->ar_block_i[current($this->ar_block_c)]['childs'][] = $block_name;
		}
		else
		{
			$this->ar_block_i[$block_name]['childs'] = array();
		}
		$this->ar_block_c[] = $block_name;
		return '<'.'?php while ($this->_bStart(\''.$block_name.'\')) : ?>';
	}
	/**
	 * Ends the block. Called on parsing source HTML-code.
	 */
	public function end_block($block_name)
	{
		array_pop($this->ar_block_c);
		return '<'.'?php endwhile; ?'.'>';
	}
}
?>