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
 * $Id: sequence_.php 552 2008-08-17 17:40:40Z glossword_team $
 */
if (!defined('IS_IN_GW2')){die();}

/* */
$this->oChecker->SetCfg( $this->oXml->get('reqcheck_glossword.xml') );
$ar_info = $this->oChecker->GetInfo();
$ar_results = $this->oChecker->GetResults();
$points = $this->oChecker->GetPoints();

/* */
$this->oTpl->addVal( 'v:current_status', $this->oTkit->_(13) . ': <strong>'.$ar_info['product'].'</strong> '.$ar_info['version'] );

#prn_r( $points );
#prn_r( $ar_results );

#print '<pre>';
#var_dump( $ar_info );
#var_dump( $ar_results );
#var_dump( $points );
#print '</pre>';

$this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);
/* */
foreach ($ar_results as $k => $v)
{
	$class_li = $v['status'] ? 'status-ok' : 'status-error';
	$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == false ? $this->oTkit->_(17) : $v['val_ini'];
	$v['val_ini'] = is_bool($v['val_ini']) && $v['val_ini'] == true ? $this->oTkit->_(18) : $v['val_ini'];
	$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == false ? $this->oTkit->_(17) : $v['val_req'];
	$v['val_req'] = is_bool($v['val_req']) && $v['val_req'] == true ? $this->oTkit->_(18) : $v['val_req'];
	$v['val_req'] = ($v['val_req'] == '-1') ? $this->oTkit->_(19) : $v['val_req'];

#prn_r( $v );
	/* */
	switch ($v['name'])
	{
		case 'PHP_VERSION':
			$v['name'] = $this->oTkit->_(1000);
			$v['descr'] = $this->oTkit->_(1001);
		break;
		case 'register_globals':
			$v['descr'] = $this->oTkit->_(1003);
		break;
		case 'getimagesize':
			$v['descr'] = $this->oTkit->_(1005);
		break;
		case 'PCRE_UTF8':
			$v['name'] = $this->oTkit->_(1006);
			$v['descr'] = $this->oTkit->_(1007);
		break;
		case 'REQUEST_URI':
			$v['descr'] = $this->oTkit->_(1010);
		break;
		case 'mysql':
			$v['descr'] = $this->oTkit->_(1011);
		break;
		case 'xml':
			$v['descr'] = $this->oTkit->_(1012);
		break;
		case 'gd':
			$v['descr'] = $this->oTkit->_(1013);
		break;
		case 'mbstring':
			$v['descr'] = $this->oTkit->_(1014);
		break;
		case 'mbstring.func_overload':
			$v['descr'] = $this->oTkit->_(1015);
		break;
		case 'mbstring.encoding_translation':
			$v['descr'] = $this->oTkit->_(1016);
		break;
		case 'mbstring.http_input':
			$v['descr'] = $this->oTkit->_(1017);
		break;
		case 'mbstring.http_output':
			$v['descr'] = $this->oTkit->_(1018);
		break;
	}
	/* */
	switch ($v['tag'])
	{
		case 'ini':
			$v['name'] = $this->oTkit->_(1002, $v['name']);
		break;
		case 'extension':
			$v['name'] = $this->oTkit->_(1008, $v['name']);
		break;
		case 'function':
			$v['name'] = $this->oTkit->_(1004, $v['name']);
		break;
		case 'servervar':
			$v['name'] = $this->oTkit->_(1009, $v['name']);
		break;
	}
	/* */
	$this->oTpl->assign(array(
		'v:li_class' => $class_li,
		'v:subject' => $v['name'],
		'v:val_ini' => $v['val_ini'],
		'v:val_req' => $v['val_req'],
		'v:pts' => $v['point'],
		'v:description' => $v['descr'],
		'v:passed_failed' => $v['status'] ? $this->oTkit->_(8) : $this->oTkit->_(7),
	));
	$this->oTpl->parseDynamic('foreach:sequence');
}
/* */
$this->oTpl->assign(array(
	'v:total_points' => $this->oTkit->_(21, $points, $ar_info['max_points']),
	'v:total_passed_failed' => $this->oChecker->GetChecked() ? $this->oTkit->_(8) : $this->oTkit->_(7)
));
/* */
$this->oTpl->tmp['d']['if:sequence'] = true;

?>