<?php
/**
 * $Id$
 * 
 * Template engine.
 * File-based version.
 *  - Added support for the group of templates, set_tpl().
 */
class site_class_templates_file extends site_class_templates
{
	public $var_last_parsed;
	/* File-based */
	public function __construct( $ar_cfg = array() )
	{
		parent::__construct( $ar_cfg );
		$this->var_last_parsed = '';
	}
	/* @access public */
	public function addVal( $k, $v = '' )
	{
		if ( is_array( $k ) )
		{
			$this->assign($k);
		}
		else
		{
			$this->assign( array( $k => $v ) );
		}
	}
	/* @access public */
	public function getVal($k = '')
	{
		if ($k == '')
		{
			return array_merge_clobber($this->ar_variables, $this->ar_variables_global);
		}
		$k = $this->_parse_var($k);
		return isset($this->ar_variables[$k]) ? $this->ar_variables[$k] : (isset($this->ar_variables_global[$k]) ? $this->ar_variables_global[$k] : false);
	}
	/* */
	public function set_tpl($id_group)
	{
		$arSql = array();
		$ar_files = array( 'tpl_web' );
		switch ( $id_group )
		{
			/* Load the group of HTML-files */
			case GW_TPL_ADM:
			case GW_TPL_MEMBER:
				$ar_files = array( 'tpl_admin' );
			break;
			case GW_TPL_WEB_INDEX:
			case GW_TPL_WEB_INSIDE:
				$ar_files = array( 'tpl_web' );
			break;
			default:
				/* Load one html-template */
				$ar_files = array( $id_group );
			break;
		}
		$arSql = array();
		/* Load files */
		foreach ( $ar_files as $k => $template )
		{
			$ar[$k]['settings_key'] = $template;
			$ar[$k]['date_modified'] = $ar[$k]['date_compiled'] = 0;
			$ar[$k]['code'] = $ar[$k]['code_i'] = $ar[$k]['settings_value'] = '';
			/* if a compiled PHP-code exists */
			if ( file_exists( $this->cfg['path_cache'].'/code-'.$template.'.php' ) )
			{
				$ar[$k]['date_compiled'] = filemtime($this->cfg['path_cache'].'/code-'.$template.'.php');
				$ar[$k]['code'] = implode('', file($this->cfg['path_cache'].'/code-'.$template.'.php'));
				$ar[$k]['code_i'] = implode('', file($this->cfg['path_cache'].'/code_i-'.$template.'.php'));
			}
			/* Read the contents of file */
			if ( file_exists( $this->cfg['path_source'].'/'.$template.$this->cfg['template_extension'] ) )
			{
				$ar[$k]['date_modified'] = filemtime($this->cfg['path_source'].'/'.$template.$this->cfg['template_extension']);
				/* Load a already compiled contents */
				if ( $ar[$k]['date_compiled'] <= $ar[$k]['date_modified'] )
				{
					$ar[$k]['settings_value'] = implode('', file($this->cfg['path_source'].'/'.$template.$this->cfg['template_extension']));
					$ar[$k]['settings_value'] = str_replace(array('{%', '%}'), array('{', '}'), $ar[$k]['settings_value']);
				}
			}
			else
			{
				print '<div>'.$this->phrase_not_found.': '.$this->cfg['path_source'].'/'.$template.$this->cfg['template_extension'].'</div>';
				return;
			}
		}
#prn_r(  $this->ar_variables );

		/* For each loaded template */
		foreach ( $ar as $k => $ar_v )
		{
			/* Iterations in blocks */
			$ar_block_i = array();

			/* Create new template key to use in arrays with compiled code */
			$template_key = sprintf("%u", crc32($ar_v['settings_key']));

			/* Should be empty on first run */
			if ( isset($this->ar_compiled[$template_key]) )
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
			if ( $ar_v['date_modified'] < $ar_v['date_compiled'] )
			{
				/* Runs before $this->_parse() */
				eval( ' ?'.'>' . $ar_v['code_i'] . '<?php ' );
			}
			else
			{
				/* Compile new PHP-code */
				eval( $this->_compile($template_key) );
			}

			/* Merge local iterations with global iteration */
			foreach ( $ar_block_i as $k => $v )
			{
				$this->ar_block_i[$k] = $v;
			}
		}
	}
	function _file_save($filename, $contents, $prefix)
	{
		$filename = $this->cfg['path_cache'].'/'.$prefix.'-'.$filename.'.php';
		/* Debug events */
		$this->ar_file_events[] = $filename;
		/* */
		$this->file_put_contents($filename, $contents, 'w');
	}
	function _compiles($tplName)
	{
		$this->oParser->_reset();
		$tmp = array();
		$tmp['filename_c'] = '';
		$tmp['str_i'] = $tmp['str'] = '';
		if (isset($this->pairsC[$tplName]) && isset($this->pairsC[$tplName]['html']))
		{
			$arRpl = array();
			$tmp['tpl_content'] =& $this->pairsC[$tplName]['html'];
			$tmp['filename_c'] =& $this->pairsC[$tplName]['filename'];
			$tmp['filename_i'] =& $this->pairsC[$tplName]['filename'];
			$preg = "/({)([ A-Za-z0-9:\/_-]+)(})/i";
			if (preg_match_all($preg, $tmp['tpl_content'], $tmp['tpl_matches']))
			{
				$arCmd = array();
				$arCmd[] = '<?xml';
				$arRpl[] = '<?'.'php echo "<","?xml"; ?'.'>';

				while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
				{
					$arCmd[] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
					$tmp['cmd'] = trim($cmd_src);
					if (strstr($tmp['cmd'], ' '))
					{
						$arCmdParts = explode(' ', $tmp['cmd']);
						$arRpl[] = $this->oCmd->$arCmdParts[0]($arCmdParts[1]);
					}
					elseif (substr($tmp['cmd'], 0, 1) == "/")
					{
						$func = '_'.substr($tmp['cmd'], 1).'End';
						$arRpl[] = $this->oCmd->$func();
					}
					else
					{
						$arRpl[] = $this->oCmd->_var($tmp['cmd']);
					}
					$tmp['str'] = str_replace($arCmd, $arRpl, $tmp['tpl_content']);
				}
				$this->_file_save($tmp['filename_c'], $tmp['str'], 'code');
			}
			$strInternal = $this->oParser->make_iterations();
			$tmp['str_i'] = '<?'.'php'.
							CRLF . '$template_timestamp = ' . (@time() - 2) . ';'.
							CRLF . $strInternal . '?'.'>';
			$this->_file_save( $tmp['filename_i'], $tmp['str_i'], 'code_i' );
			/* 12 feb 2005: Run compiled code */
			$this->pairsC[$tplName] = array( 'code' => $tmp['str'] );
		}
		return $strInternal;
	}
	/* */
	function parse( $varName = '', $cacheKey = NULL )
	{
		$ar = debug_backtrace();
		print '<div class="gw-debug">Depreciated function: '. $ar[0]['file'] .': '. $ar[0]['line'].'</div>';
		return;
		ob_start();
		$tpl = array();
		$this->var_last_parsed = '';
		$str_code = '';
		
		for ( reset($this->pairsC); list($tkey, $arV) = each( $this->pairsC); )
		{
			if ($this->is_tpl_show_names)
			{
				print '<table border="1" cellspacing="0"><tr><td>'.$tkey.'</td></tr><tr><td>';
			}
			@eval (' ?'.'>' . $arV['code'] . '<?'.'php ');
			if ($this->is_tpl_show_names)
			{
				print '</td></tr></table>';
			}
			unset($this->pairsC[$tkey]);
		}
		$tmp['ob_contents'] = array( $varName.'_last' => ob_get_contents() );
		ob_end_clean();
		
		$this->var_last_parsed = $this->assign( $tmp['ob_contents'] );
		$this->is_cache_keypresent = 0;
	}
	/* */
}
?>