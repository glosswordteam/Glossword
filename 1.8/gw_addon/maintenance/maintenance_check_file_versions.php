<?php
if (!defined('IN_GW'))
{
	die("<!-- $Id$ -->");
}
/*
	Maintenance task
*/
/* */
include($sys['path_addon'].'/class.gw_addon.php');
/* */
class gw_addon_check_file_versions extends gw_addon
{
	var $addon_name = 'check_file_versions';
	/* Autoexec */
	function gw_addon_check_file_versions()
	{
		$this->init_m();
	}
	/* */
	function _gw_create_rss()
	{
		/* */
		clearstatcache();
		/* */
		$ar_files[] = 'gw_addon/abbr/abbr_admin.php';
		$ar_files[] = 'gw_addon/abbr/abbr-mysql323.php';
		$ar_files[] = 'gw_addon/abbr/abbr-mysql410.php';
		$ar_files[] = 'gw_addon/custom_az/custom_az_admin.php';
		$ar_files[] = 'gw_addon/custom_az/custom_az-mysql323.php';
		$ar_files[] = 'gw_addon/custom_az/custom_az-mysql410.php';
		$ar_files[] = 'gw_addon/class.autolinks.php';
		$ar_files[] = 'gw_addon/class.gw_addon.php';
		$ar_files[] = 'gw_addon/custom_pages/custom_pages_admin.php';
		$ar_files[] = 'gw_addon/custom_pages/custom_pages-mysql323.php';
		$ar_files[] = 'gw_addon/custom_pages/custom_pages-mysql410.php';
		$ar_files[] = 'gw_addon/fields_extension.php';
		$ar_files[] = 'gw_addon/gw_feedback/img/bg.png';
		$ar_files[] = 'gw_addon/gw_feedback/img/font1.png';
		$ar_files[] = 'gw_addon/gw_feedback/img/font2.png';
		$ar_files[] = 'gw_addon/gw_feedback/img/font3.png';
		$ar_files[] = 'gw_addon/gw_feedback/img/font4.png';
		$ar_files[] = 'gw_addon/gw_feedback/index.php';
		$ar_files[] = 'gw_addon/gw_feedback/make_img.php';
		$ar_files[] = 'gw_addon/log-search/log-search_admin.php';
		$ar_files[] = 'gw_addon/log-search/log-search-mysql410.php';
		$ar_files[] = 'gw_addon/maintenance/maintenance_recount_dict.php';
		$ar_files[] = 'gw_addon/maintenance/maintenance_recount_user.php';
		$ar_files[] = 'gw_addon/maintenance/maintenance_check_file_versions.php';
		$ar_files[] = 'gw_addon/multilingual_vars.php';
		$ar_files[] = 'gw_addon/settings/sys_admin.php';
		$ar_files[] = 'gw_addon/settings/settings_admin.php';
		$ar_files[] = 'gw_addon/settings/settings_edit.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_1.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_2.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_3.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_4.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_5.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_7.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_8.inc.php';
		$ar_files[] = 'gw_addon/settings/settings_maintenance_9.inc.php';
		$ar_files[] = 'gw_addon/topics/topics_add.inc.php';
		$ar_files[] = 'gw_addon/topics/topics_admin.php';
		$ar_files[] = 'gw_addon/topics/topics_browse.inc.php';
		$ar_files[] = 'gw_addon/topics/topics_export.inc.php';
		$ar_files[] = 'gw_addon/topics/topics_import.inc.php';
		$ar_files[] = 'gw_addon/topics/topics_remove.inc.php';
		$ar_files[] = 'gw_addon/topics/topics-mysql410.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_add.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_admin.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_browse.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_edit.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_export.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_import.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes_remove.inc.php';
		$ar_files[] = 'gw_addon/visual-themes/visual-themes-mysql410.php';
		$ar_files[] = 'gw_install/class.install.php';
		$ar_files[] = 'gw_install/class.intro.php';
		$ar_files[] = 'gw_install/class.novar.php';
		$ar_files[] = 'gw_install/class.template_plain.php';
		$ar_files[] = 'gw_install/class.uninstall.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_1.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_2.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_3.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_4.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_5.php';
		$ar_files[] = 'gw_install/class.upgrade_to_1_8_6.php';
		$ar_files[] = 'inc/a.export.inc.php';
		$ar_files[] = 'inc/a.export.js.php';
		$ar_files[] = 'inc/a.import.inc.php';
		$ar_files[] = 'inc/a.import.js.php';
		$ar_files[] = 'inc/class.confirm.php';
		$ar_files[] = 'inc/class.forms.php';
		$ar_files[] = 'inc/class.gw_htmlforms.php';
		$ar_files[] = 'inc/class.gwtk.php';
		$ar_files[] = 'inc/class.rendercells.php';
		$ar_files[] = 'inc/class.session.ext.php';
		$ar_files[] = 'inc/class.template.ext.php';
		$ar_files[] = 'inc/class.xmlparse.php';
#		$ar_files[] = 'inc/config.inc.php';
		$ar_files[] = 'inc/constants.inc.php';
		$ar_files[] = 'inc/constructor.inc.php';
		$ar_files[] = 'inc/edcode.js.php';
		$ar_files[] = 'inc/export_CSV/index.inc.php';
		$ar_files[] = 'inc/export_CSV/index.js.php';
		$ar_files[] = 'inc/export_SQL/index.inc.php';
		$ar_files[] = 'inc/export_SQL/index.js.php';
		$ar_files[] = 'inc/export_XML/index.inc.php';
		$ar_files[] = 'inc/export_XML/index.js.php';
		$ar_files[] = 'inc/func.admin.inc.php';
		$ar_files[] = 'inc/func.browse.inc.php';
		$ar_files[] = 'inc/func.catalog.inc.php';
		$ar_files[] = 'inc/func.crypt.inc.php';
		$ar_files[] = 'inc/func.img.inc.php';
		$ar_files[] = 'inc/func.shuffle.php';
		$ar_files[] = 'inc/func.sql.inc.php';
		$ar_files[] = 'inc/func.srch.inc.php';
		$ar_files[] = 'inc/func.stat.inc.php';
		$ar_files[] = 'inc/func.text.inc.php';
		$ar_files[] = 'inc/lib.prepend.php';
		$ar_files[] = 'inc/page.footer.php';
		$ar_files[] = 'inc/query_storage.php';
		$ar_files[] = 'inc/query_storage_global-mysql323.php';
		$ar_files[] = 'inc/query_storage_global-mysql410.php';
		$ar_files[] = 'inc/query_storage_sess-mysql323.php';
		$ar_files[] = 'inc/query_storage_sess-mysql410.php';
		$ar_files[] = 'inc/t.dict.inc.php';
		$ar_files[] = 'inc/t.term.inc.php';
		$ar_files[] = 'inc/t.user.inc.php';
		$ar_files[] = 'inc/top.dict_averagehits.inc.php';
		$ar_files[] = 'inc/top.dict_newest.inc.php';
		$ar_files[] = 'inc/top.dict_updated.inc.php';
		$ar_files[] = 'inc/top.search_last.inc.php';
		$ar_files[] = 'inc/top.term_newest.inc.php';
		$ar_files[] = 'inc/top.term_updated.inc.php';
		$ar_files[] = 'inc/top.user_active.inc.php';
		$ar_files[] = 'gw_install/index.php';
		$ar_files[] = 'gw_install/install.php';
		$ar_files[] = 'gw_install/install_functions.php';
		$ar_files[] = 'gw_install/query_storage.php';
		$ar_files[] = 'gw_install/sql/glossword1_up185_data.sql';
		$ar_files[] = 'gw_install/template/i_footer.html';
		$ar_files[] = 'gw_install/template/i_header.html';
		$ar_files[] = 'gw_install/template/i_intro.html';
		$ar_files[] = 'gw_install/template/i_step.html';
		$ar_files[] = 'gw_install/template/i_step_3.html';
		$ar_files[] = 'gw_install/template/i_style.php';
		$ar_files[] = 'gw_install/template/scripts.js';
		$ar_files[] = 'gw_install/template/theme.inc.php';
		$ar_files[] = 'gw_locale/en-utf8/actions.php';
		$ar_files[] = 'gw_locale/ru-utf8/actions.php';
		$ar_files[] = 'lib/class.case.php';
		$ar_files[] = 'lib/class.cells_tpl.php';
		$ar_files[] = 'lib/class.db.cache.php';
		$ar_files[] = 'lib/class.db.mysql.php';
		$ar_files[] = 'lib/class.db.q.php';
		$ar_files[] = 'lib/class.domxml.php';
		$ar_files[] = 'lib/class.func.php';
		$ar_files[] = 'lib/class.globals.php';
		$ar_files[] = 'lib/class.headers.php';
		$ar_files[] = 'lib/class.html.php';
		$ar_files[] = 'lib/class.logwriter.php';
		$ar_files[] = 'lib/class.render.php';
		$ar_files[] = 'lib/class.session.php';
		$ar_files[] = 'lib/class.timer.php';
		$ar_files[] = 'lib/class.tpl.php';
		$ar_files[] = 'lib/class.ua.php';
		$ar_files[] = 'lib/class.xslt.php';
		$ar_files[] = 'templates/common/google_ads.txt';
		$ar_files[] = 'templates/common/gw_info1.html';
		$ar_files[] = 'templates/common/gw_tags.css';
		$ar_files[] = 'templates/common/opensearch.xml';
		$ar_files[] = 'templates/common/scripts.js';
		$ar_files[] = 'templates/common/search_form_1.html';
		$ar_files[] = 'templates/common/search_form_2.html';
		$ar_files[] = 'templates/common/search_form_3.html';
		$ar_files[] = 'favicon.ico';
		$ar_files[] = '.htaccess';
		$ar_files[] = 'css.php';
#		$ar_files[] = 'custom_vars.php';
		$ar_files[] = 'gw_admin.php';
		$ar_files[] = 'index.php';
		/* */
		$ar_fm = $ar_q = array();
		foreach($ar_files as $k => $filename)
		{
			$date_modified = (file_exists($filename) ? filemtime($filename) : 0);
			if ($date_modified)
			{
				/* Convert to GMT */
				$date_modified = $date_modified - @date('Z');
				$ar_fm[$date_modified.'-'.$k] = $filename;
			}
		}
		krsort($ar_fm);
		/* */
		$str_rss = '<rss version="2.0" 
			xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
			xmlns:content="http://purl.org/rss/1.0/modules/content/">
			<channel>
			<title>'. strip_tags($this->sys['site_name']) .': The list of modified files</title>
			<link>'.$this->sys['server_url'].'/</link>
			<description></description>
			<copyright></copyright>
			<lastBuildDate>'.@date('r', $this->sys['time_now_gmt_unix']).'</lastBuildDate>
			<ttl>3600</ttl>
		';
		$cnt = 1;
		foreach($ar_fm as $date_modified => $filename)
		{
			/* Limit per date */
			if (($this->sys['time_now_gmt_unix'] - $date_modified) > $this->sys['time_sec_m'] )
			{
				break;
			}
			/* Limit per number */
			if ($cnt >= 100){ break; }
			/* */
			$file_url = 'http://glossword.svn.sourceforge.net/viewvc/glossword/1.8/'.$filename.'?view=log';
			$str_rss .= CRLF.'<item>';
			$str_rss .= '<title>'.$filename.'</title>';
			$str_rss .= '<link>'.$file_url.'</link>';
			$str_rss .= '<pubDate>'.@date('r', $date_modified).'</pubDate>';
			/* */
			$ar_dir = explode('/', $filename);
			if (isset($ar_dir[1]))
			{
				$str_rss .= '<category>'.$ar_dir[0].'</category>';
			}
			$str_rss .= '</item>';
			$cnt++;
		}
		$str_rss .= '</channel></rss>';
		/* */
		$filename = $this->sys['path_temporary'] . '/versions.xml';
		$this->oFunc->file_put_contents( $filename, $str_rss, 'w' );
	}
	/* */
	function alpha()
	{
		if ((mt_rand() % 100) < $this->sys['prbblty_tasks'])
		{
			$this->_gw_create_rss();
		}
	}
	function omega()
	{
		$this->_gw_create_rss();
	}
}
/* */
$oM = new gw_addon_check_file_versions;
$oM->alpha();
unset($oM);
/* end of file */
?>