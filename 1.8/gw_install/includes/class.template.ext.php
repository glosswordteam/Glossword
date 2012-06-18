<?php
/**
 * Template engine
 * © 2002-2008 Dmitry N. Shilnikov <dev at wwwguru dot net>
 * File-based version. Added support for the group of templates.
 * 
 * Requires:
 * - class $o
 * 
 * @version $Id: class.template.ext.php 3 2008-06-21 07:22:47Z glossword_team $
 */
class tkit_template extends gwv_template
{
	var $path_source;
	var $path_cache;
	/* File-based */
	function init($id_style)
	{
		$this->id_style = $id_style;
		$this->var_last_parsed = '';
	}
	/* @access public */
	function addVal($k, $v)
	{
		$this->assign(array($k => $v));
	}
	/* @access public */
	function getVal($k = '')
	{
		if ($k == '')
		{
			return $this->pairsV;
		}
		$k = $this->namespace_default.'::'.sprintf("%u", crc32($k));
		return isset($this->pairsV[$k]) ? $this->pairsV[$k] : false;
	}
	function set_tpl($id_group, $theme_name = '')
	{
		if ($theme_name == '')
		{
			$theme_name = $this->id_style;
		}
		$arSql = array();
		$ar_files = array('tpl_index_header','tpl_index_body','tpl_index_footer');
		switch ($id_group)
		{
			/* Load the group of html-templates */
			case GW2_TPL_WEB_INDEX:
				$ar_files = array('tpl_index_header','tpl_index_body','tpl_index_footer');
			break;
			default:
				/* Load one html-template */
				$ar_files = array($id_group);
			break;
		}
		/* Load files */
		while (list($k, $tplname) = each($ar_files))
		{
			$arSql[$k]['settings_key'] = $tplname;
			$arSql[$k]['date_modified'] = $arSql[$k]['date_compiled'] = 0;
			$arSql[$k]['code'] = $arSql[$k]['code_i'] = $arSql[$k]['settings_value'] = '';
			/* if compiled html exists */
			if (file_exists($this->path_cache.'/code-'.$tplname.'.php'))
			{
				$arSql[$k]['date_compiled'] = filemtime($this->path_cache.'/code-'.$tplname.'.php');
				$arSql[$k]['code'] = implode('', file($this->path_cache.'/code-'.$tplname.'.php'));
				$arSql[$k]['code_i'] = implode('', file($this->path_cache.'/code_i-'.$tplname.'.php'));
			}
			if (file_exists($this->path_source.'/'.$tplname.'.html'))
			{
				$arSql[$k]['date_modified'] = filemtime($this->path_source.'/'.$tplname.'.html');
				/* Load modified contents */
				if ($arSql[$k]['date_compiled'] <= $arSql[$k]['date_modified'])
				{
					$arSql[$k]['settings_value'] = implode('', file($this->path_source.'/'.$tplname.'.html'));
					$arSql[$k]['settings_value'] = str_replace(array('{%', '%}'), array('{', '}'), $arSql[$k]['settings_value']);
				}
			}
			else
			{
				print '<div>'.$this->path_source.'/'.$tplname.'.html'.'</div>';
			}
		}
		/* */
		while (is_array($arSql) && list($k, $arV) = each($arSql))
		{
			$arBlockI = array();
			$tkey = sprintf("%u", crc32($arV['settings_key']));
			if (isset($this->pairsC[$tkey]))
			{
				/* Do not load file second time */
				continue;
			}
			$this->pairsC[$tkey] = array(
					'filename' => $arV['settings_key'],
					'code' => $arV['code'],
					'html' => $arV['settings_value']
			);
			if ($arV['date_modified'] < $arV['date_compiled'])
			{
				eval(' ?'.'>' . $arV['code_i'] . '<?php ');
			}
			else
			{
				eval($this->_compile($tkey));
			}
			if (!isset($arBlockI)) { $arBlockI = array(); }
			$this->arBlockI = array_merge($this->arBlockI, $arBlockI);
		}
	}
	function _file_load($filename, $field = 'html', $id_style = 1)
	{
		return $filename;
	}
	function _file_save($filename, $str, $field = '', $id_style = 1)
	{
		global $o;
#		${$o}->ar_file_events[] = $this->path_cache.'/'.$field.'-'.$filename.'.php';
		$o->oFunc->file_put_contents($this->path_cache.'/'.$field.'-'.$filename.'.php', $str, 'w');
	}
	function _compile($tplName)
	{
		$this->oCmd->_reset();
		$tmp = array();
		$tmp['filename_c'] = '';
		$tmp['str_i'] = $tmp['str'] = '';
		if (isset($this->pairsC[$tplName]) && isset($this->pairsC[$tplName]['html']))
		{
			$arRpl = array();
			$tmp['tpl_content'] =& $this->pairsC[$tplName]['html'];
			$tmp['filename_c'] =& $this->pairsC[$tplName]['filename'];
			$tmp['filename_i'] =& $this->pairsC[$tplName]['filename'];
			$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
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
				$this->_file_save($tmp['filename_c'], $tmp['str'], 'code', $this->id_style);
			}
			$strInternal = $this->oCmd->get_contents_c();
			$tmp['str_i'] = '<?'.'php'.
							CRLF . '$template_timestamp = ' . (time() - 2) . ';'.
							CRLF . $strInternal . '?'.'>';
			$this->_file_save($tmp['filename_i'], $tmp['str_i'], 'code_i', $this->id_style);
			/* 12 feb 2005: Run compiled code */
			$this->pairsC[$tplName] = array('code' => $tmp['str']);
		}
		return $strInternal;
	}
	function parse($varName = '', $cacheKey = NULL)
	{
		ob_start();
		$tpl = array();
		$this->var_last_parsed = '';
		$str_code = '';
		for (reset($this->pairsC); list($tkey, $arV) = each($this->pairsC);)
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
		$tmp['ob_contents'] = array($varName.'_last' => ob_get_contents());
		ob_end_clean();
		$this->var_last_parsed = $this->assign($tmp['ob_contents']);
		$this->is_cache_keypresent = 0;
	}
	/* */
}
?>