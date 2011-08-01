<?php
define('IN_GW', 1);
/* Change current directory to access for website files */
if (!@chdir('../..'))
{
	exit("Can't change directory to `..'");
}
/* Load config */
include_once('db_config.php');

/* Constants */
define('CRLF', "\r\n");
define('LF', "\\n\\");

$sys['path_install'] = 'gw_install';
$sys['id_prepend'] = 0;
include_once( $sys['path_include_local'].'/config.inc.php' );
include_once( $sys['path_gwlib']. '/class.globals.php' );
/* Load theme colors */
include_once($sys['path_install'].'/template/theme.inc.php');
/* Global variables */
$gv = $oGlobals->register(array('dir'));
$sys['css_align_right'] = 'right';
$sys['css_align_left'] = 'left';
if ($gv['dir'] == 'rtl')
{
	$sys['css_align_right'] = 'left';
	$sys['css_align_left'] = 'right';
}
/* */
header('Content-Type: text/css; charset=UTF-8');
?>
body { margin: 0; padding: 0; background: <?php print $ar_theme['color_1'];?>; color: <?php print $ar_theme['color_black'];?> }
#theme { margin: 0 auto; padding: 0; width: 600px; }
form { margin: 0; padding: 0 }
label { cursor: pointer }
img { border: 0 }
tt { color: #805; background: transparent; font-size: 120% }
a, a:visited { cursor: pointer; color: <?php print $ar_theme['color_a_link'];?>; background: transparent; text-decoration: none; }
a:hover { text-decoration: underline }
.errormsg { padding: 10px; border: 3px solid #FAA; color: #E63; background: transparent; font: 78% verdana,arial,sans-serif  }
.black { color: #000; background: transparent }
.gray { color: #888; background: transparent }
.green { color: #390; background: transparent }
.red { color: #F63; background: transparent }
.white { color: #EEE; background: transparent }
.yellow { color: #C90; background: transparent }
.hr2 { height: 4px; overflow: hidden; background: <?php print $ar_theme['color_2'];?> }
.hr3 { height: 2px; overflow: hidden; background: <?php print $ar_theme['color_3'];?> }
.hr4 { height: 2px; overflow: hidden; background: <?php print $ar_theme['color_4'];?> }
.hr5 { height: 2px; overflow: hidden; background: <?php print $ar_theme['color_5'];?> }
h1 { font: bold 155% sans-serif; margin:0; padding: 0; letter-spacing: -1px; color: <?php print $ar_theme['color_6'];?>; background: inherit; }
h3 { font: bold 100% sans-serif; margin:0; padding: 4px; color: <?php print $ar_theme['color_6'];?>; background: <?php print $ar_theme['color_1'];?>; }
.vtop { vertical-align: top }
.contents { padding: 0.5em 1em; text-align: <?php print $sys['css_align_left'];?> }
.center { margin: 0 auto; text-align: center }

.xq { font: 61% verdana,arial,sans-serif }
.xw { font: bold 140% "trebuchet ms",verdana,sans-serif }
.xr { font: bold 91% verdana,arial,sans-serif }
.xt { font: 76% sans-serif }
.xu { font: 90% sans-serif }

.submitcancel { cursor: pointer; padding: 2px; border-left: #FFF 2px solid; border-top: #FFF 2px solid; border-right: #C7C4A9 2px solid; border-bottom: #C7C4A9 2px solid; 
	color: #000; background: #F0F0EB;
	width: 8em;
	font: 80% "microsoft sans serif","ms sans serif",serif;
}
.submitok { cursor: pointer; padding: 2px;
	border-left: <?php print $ar_theme['color_3'];?> 2px solid; 
	border-top: <?php print $ar_theme['color_3'];?> 2px solid; 
	border-right: <?php print $ar_theme['color_7'];?> 2px solid; 
	border-bottom: <?php print $ar_theme['color_7'];?> 2px solid; 
	color: #000; background: <?php print $ar_theme['color_5'];?>; 
	width: 8em;
	font: 80% "microsoft sans serif","ms sans serif",serif;
}

ul.gwstatus li { padding: 5px }
.debugwindow { width: 100%; font: 75% verdana,arial,sans-serif }
.iframe { text-align: justify; border: 3px solid #CEDBF0; padding: 1em; color: #000; background: #F9FAF8; height: 240px; overflow: auto }
input.input {
	font: 100% sans-serif;
	width: 100%;
	color: #000; background: #FFF;
	border: #c8c8c8 1px solid;
}
select.input {
	font: 100% sans-serif;
	width: 100%;
	color: #000; background: #FEFEFE;
	border: #c8c8c8 1px solid;
}
select.input:hover, input.input:hover, textarea.input:hover,
input.input50:hover, select.input50:hover { 
	color: #000; background: #FFF; border-color: #aecdf6;
}

table.gw2TableFieldset {
	border-spacing: 2px;
	border-collapse: separate;
}
table.gw2TableFieldset tr {
	vertical-align: top;
}
table.gw2TableFieldset table {
	font-size: 100%;
}
table.gw2TableFieldset td.td1, table.gw2TableFieldset td.td2 {
	padding: 1px;
	font: 76% sans-serif;
}
table.gw2TableFieldset td.td1 {
	vertical-align: top;
	text-align: <?php print $sys['css_align_right'];?>;
}
table.gw2TableFieldset td.td2, table.gw2TableFieldset td.disabled {
	vertical-align: top;
	text-align: <?php print $sys['css_align_left'];?>;
}
table.gw2TableFieldset td.tdinput,
table.gw2TableFieldset td.td1 .input,
table.gw2TableFieldset td.td1 .input50,
table.gw2TableFieldset td.td2 .input,
table.gw2TableFieldset td.td2 .input50
{
	font-size: 120%;
}