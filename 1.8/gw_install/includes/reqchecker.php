<?php
/**
 *  Glossword Requirements Checker
 *  © 2008 Glossword.biz team (http://glossword.biz/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/**
 * $Id: reqchecker.php 3 2008-06-21 07:22:47Z glossword_team $
 */
class gw_reqcheck
{
	var $phrase_status_true, $phrase_status_false;
	var $points = 0;
	var $ar_results, $ar_info;
	var $is_checked = 1;
	var $is_checked_total = 1;
	/* */
	public function SetVars($ar = array())
	{
		foreach ($ar as $k => $v)
		{
			$this->$k = $v;
		}
	}
	/* */
	public function SetCfg($map)
	{
		$this->map =& $map;
	}
	/* */
	function GetInfo()
	{
		$ar_info = array();
		foreach ( $this->map as $tag => $arV )
		{
			foreach ($arV as $k1 => $arParams )
			{
				if (!is_array($arParams['value']))
				{
					$ar_info[$arParams['tag']] = $arParams['value'];
					unset($this->map[$tag]);
				}
			}
		}
		return $ar_info;
	}
	/* */
	private function _Parse($map)
	{
		foreach ( $map as $tag => $arV )
		{
			foreach ( $arV as $k1 => $arParams )
			{
				if ( is_array($arParams['value']) )
				{
					if ( isset($arParams['attributes']['name']) )
					{
						$this->is_checked = $this->_Check($arParams['tag'], $arParams['attributes']);
#prn_r( $arParams['tag']  );
#prn_r( $arParams['attributes']  );
					}
					if ( $this->is_checked )
					{
						$this->_Parse( $arParams['value'] );
					}
				}
				else
				{
#prn_r( $arParams, __LINE__ );
					$this->is_checked = $this->_Check($arParams['tag'], $arParams['attributes']);
				}
			}
		}
	}
	function GetResults()
	{
		$this->_Parse($this->map);
		return $this->ar_results;
	}
	private function _Check($tag, $p)
	{
		$return = false;
		$val_ini = $val_req = '-';
		$p['descr'] = '';
		
		$is_hidden = 0;
		if (isset($p['hidden']))
		{
			$is_hidden = $p['hidden'];
		}

		if (isset($p['value']))
		{
			$val_req = $p['value'];
		}
		/* */
		switch ($tag)
		{
			case 'ini':
				$val_ini = strtolower(ini_get($p['name']));
				$val_ini = ($val_ini == 'on' || $val_ini == '1') ? true : $val_ini;
				$val_ini = ($val_ini === 'off' || $val_ini === '0' || $val_ini === '') ? false : $val_ini;
				if (isset($p['value']))
				{
					/* Try to detect constants */
					/* Unstable */
					if ( preg_match( '/([A-Z_:]+)/', $p['value'] ) )
					{
						if (strpos($p['value'], '|') !== false)
						{
							@eval( '?'.'><'.'?php $val_req = '. $p['value'] .';?'.'>' );
						}
						else
						{
							$val_req = @constant($p['value']);
						}
					}
					else
					{
						$val_req = strtolower($p['value']);
					}
					$val_req = ($val_req == 'on' || $val_req == '1') ? true : $val_req;
					$val_req = ($val_req === 'off' || $val_req === '0' || $val_req === '') ? false : $val_req;
				}
				switch ($p['compare'])
				{
					case '-1':
						/* Ignore setting */
						$return = true;
						$val_req = -1;
					break;
					case 'eq':
					case '==':
						if ( $val_ini == $val_req )
						{
							if (isset($p['return']))
							{
								$return = $p['return'];
							}
							else
							{
								$return = true;
							}
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
					break;
					case '!=':
						if ( $val_ini != $val_req )
						{
							$return = $p['return'];
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
						else
						{
							$return = true;
						}
					break;
					case '&':
						if ( intval($val_ini) & $val_req )
						{
							$return = $p['return'];
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
						else
						{
							$return = true;
						}
					break;
				}
			break;
			case 'constant':
				switch ($p['compare'])
				{
					case '-1':
						$val_req = -1;
					break;
					case '>':
						/* Special rule for PHP_VERSION */
						if ($p['name'] == 'PHP_VERSION')
						{
							/* the left is lower than the right */
							$return = version_compare(PHP_VERSION, $p['value']) > 0;
							$val_ini = PHP_VERSION;
						}
					break;
					case '==':
						if (defined($p['name']) && constant($p['name']) == $p['value'])
						{
							$return = true;
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
							$val_ini = constant($p['name']);
						}
					break;
				}
			break;
			case 'function':
				switch ($p['compare'])
				{
					case '-1':
						$val_req = -1;
					break;
					case '1':
						$val_req = true;
						$val_ini = false;
						if ( function_exists($p['name']) )
						{
							$return = true;
							$val_ini = true;
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
					break;
				}
			break;
			case 'extension':
				switch ($p['compare'])
				{
					case '1':
						$val_req = true;
						$val_ini = false;
						if ( extension_loaded($p['name']) )
						{
							$return = true;
							$val_ini = true;
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
					break;
				}
			break;
			case 'servervar':
				switch ($p['compare'])
				{
					case '1':
						$val_req = true;
						$val_ini = false;
						if ( isset($_SERVER[$p['name']]) )
						{
							$return = true;
							$val_ini = true;
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
					break;
				}
			break;
			case 'set':
				$val_req = true;
				$val_ini = false;
				switch ($p['name'])
				{
					case 'PCRE_UTF8':
						if (@preg_match('//u', ''))
						{
							$return = true;
							$val_ini = true;
							if (isset($p['point']))
							{
								$this->points += $p['point'];
							}
						}
					break;
				}
			break;
		}
		/* */
		if (isset($p['return']) && $return !== false)
		{
			#$return = $p['return'];
			/* Make sure that return value is a boolean */
			#$p['return'] = ($p['return'] == 'true' || $p['return'] == 'on' || $p['return'] == '1') ? true : false;
		}

		if ( !is_bool($return) )
		{
			$return = ($return == 'true' || $return == 'on' || $return == '1') ? true : false;
		}

		if (!isset($p['point']))
		{
			$p['point'] = 0;
		}
		/* */
		if (!$is_hidden)
		{
			if (!$return && $this->is_checked_total)
			{
				$this->is_checked_total = 0;
			}
			$this->ar_results[] = array(
				'tag' => $tag,
				'name' => $p['name'],
				'val_ini' => $val_ini,
				'val_req' => $val_req,
				'status' => $return,
				'point' => $p['point'],
				'descr' => $p['descr']
			);
		}

#prn_r( $p, $tag );
#prn_r( var_dump( $return ) );
#prn_r( $this->points );
		return $return;
		/* Debug purposes */
#		return false;
	}
	public function GetPoints()
	{
		return $this->points;
	}
	public function GetChecked()
	{
		return $this->is_checked_total;
	}

}
?>