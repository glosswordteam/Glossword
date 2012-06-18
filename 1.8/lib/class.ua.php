<?php
/* 
	Use Agent Class.
	Adds new available conditions:
	if ($sys['is_ua_client']) ... 'normal' browsers
	if ($sys['is_ua_se']) ... search engines
	if ($sys['is_ua_dm']) ... download managers
	$sys['ua_type'] = is_ua_client | is_ua_se | is_ua_dm
*/
class gw_user_agent 
{
	var $remote_ua;
	function gw_user_agent()
	{
		global $sys;
		$this->remote_ua = trim(substr(getenv('HTTP_USER_AGENT'), 0, 255));
		$sys['ua_type'] = $this->get_ua_type();
		$sys[$sys['ua_type']] = 1;
		if (!isset($sys['is_ua_client'])) { $sys['is_ua_client'] = 0; }
		if (!isset($sys['is_ua_se'])) { $sys['is_ua_se'] = 0; }
		if (!isset($sys['is_ua_dm'])) { $sys['is_ua_dm'] = 0; }
	}
	/* */
	function get_ua_type()
	{
		global $sys;
		if (preg_match("/(". implode("|",array_keys($this->get_se_alias())) .")+/i", $this->remote_ua ))
		{
			return 'is_ua_se';
		}
		elseif (preg_match("/(". implode("|",array_keys($this->get_dm_alias())) .")+/i", $this->remote_ua ))
		{
			return 'is_ua_dm';
		}
		else
		{
			/* Let unknown user agent be a browser */
			return 'is_ua_client';
		}
	}
	/* */
	function get_browser_str($ua = '')
	{
		if ($ua == '') { $ua = $this->remote_ua; }
		if (preg_match( "/msie[\/\sa-z]*([\d\.]*)/i", $ua, $m )
			&& !preg_match( "/webtv/i", $ua )
			&& !preg_match( "/omniweb/i", $ua )
			&& !preg_match( "/opera/i", $ua )) {
			/* IE */
			return "MS Internet Explorer $m[1]";
		}
		else if (preg_match( "/netscape.?\/([\d\.]*)/i", $ua, $m ))
		{
			/* Netscape 6.x, 7.x ... */
			return 'Netscape '.@$m[1];
		}
		else if ( preg_match( "/mozilla[\/\sa-z]*([\d\.]*)/i", $ua, $m )
			&& !preg_match( "/gecko/i", $ua )
			&& !preg_match( "/compatible/i", $ua )
			&& !preg_match( "/opera/i", $ua )
			&& !preg_match( "/galeon/i", $ua )
			&& !preg_match( "/safari/i", $ua )) {
			/* Netscape 3.x, 4.x ... */
			return 'Netscape '.@$m[2];
		}
		else
		{
			/* Other */
			$ar = $this->get_browsers();
			$a = array_merge($this->get_browsers_alias(), $this->get_se_alias());
			$a = array_merge($a, $this->get_dm_alias());
			$a = array_merge($a, array('mozilla'=>'Mozilla', 'libwww'=>'LibWWW'));
			for (; list($k, $v) = each($ar);)
			{
				if (preg_match( "/$v.?\/([\d\.]*)/i", $ua, $m ))
				{
					return $a[$v].' '.$m[1];
					break;
				}
			}
		}
		return 'Unknown';
	}
	/* */
	function get_os_str($ua = '')
	{
		if ($ua == '') { $ua = $this->remote_ua; }
		$ar =& $this->get_os();
		$ar_alias = $this->get_os_alias();
		for (; list($k, $v) = each($ar);)
		{
			if (preg_match( "/$v/i", $ua ))
			{
				return $ar_alias[$v];
				break;
			}
		}
		return 'Unknown';
	}
	/* */
	function get_os()
	{
		return array_keys($this->get_os_alias());
	}
	/* */
	function get_browsers()
	{
		$a = array_merge($this->get_browsers_alias(), $this->get_se_alias());
		$a = array_merge($a, $this->get_dm_alias());
		$a = array_merge($a, array('mozilla'=>'Mozilla','libwww'=>'LibWWW'));
		return array_keys($a);
	}
	/* */
	function get_browsers_alias()
	{
		return array (
// Common web browsers text (IE and Netscape must not be in this list)
'amaya'=>'Amaya',
'amigavoyager'=>'AmigaVoyager',
"aol\\-iweng"=>'AOL-Iweng',
'aweb'=>'AWeb',
'chimera'=>'Chimera',
'cyberdog'=>'Cyberdog',
'dillo'=>'Dillo',
'dreamcast'=>'Dreamcast',
'emailsiphon'=>'EmailSiphon',
'encompass'=>'Encompass',
'firebird'=>'Mozilla Firebird',
'firefox'=>'Mozilla Firefox',
'fresco'=>'ANT Fresco',
'galeon'=>'Galeon',
'go!zilla'=>'Go!Zilla',
'hotjava'=>'Sun HotJava',
'ibrowse'=>'IBrowse',
'icab'=>'iCab',
'intergo'=>'InterGO',
'k-meleon'=>'K-Meleon',
'konqueror'=>'Konqueror',
'linemodebrowser'=>'W3C Line Mode Browser',
'links'=>'Links',
'lotus-notes'=>'Lotus Notes web client',
'lynx'=>'Lynx',
'macweb'=>'MacWeb',
'msfrontpageexpress'=>'MS FrontPage Express',
"msie 6\.0"=>'Microsoft Internet Explorer 6.0',
'multizilla'=>'MultiZilla',
'ncsa_mosaic'=>'NCSA Mosaic',
'netpositive'=>'NetPositive',
'nutscrape'=>'Nutscrape',
'omniweb'=>'OmniWeb',
'opera'=>'Opera',
'phoenix'=>'Phoenix',
'safari'=>'Safari',
'viking'=>'Viking',
'webexplorer'=>'IBM-WebExplorer',
// Music only browsers
'real'=>'RealAudio or compatible (media player)',
'winamp'=>'WinAmp (media player)',				// Works for winampmpeg and winamp3httprdr
'windows-media-player'=>'Windows Media Player (media player)',
'audion'=>'Audion (media player)',
'freeamp'=>'FreeAmp (media player)',
'itunes'=>'Apple iTunes (media player)',
'jetaudio'=>'JetAudio (media player)',
'mint_audio'=>'Mint Audio (media player)',
'mpg123'=>'mpg123 (media player)',
'nsplayer'=>'NetShow Player (media player)',
'sonique'=>'Sonique (media player)',
'uplayer'=>'Ultra Player (media player)',
'xmms'=>'XMMS (media player)',
'xaudio'=>'Some XAudio Engine based MPEG player (media player)',
// PDA/Phonecell browsers
'alcatel'=>'Alcatel Browser (PDA/Phone browser)',
'ericsson'=>'Ericsson Browser (PDA/Phone browser)',
'mmef'=>'Microsoft Mobile Explorer (PDA/Phone browser)',
'mot-'=>'Motorola Browser (PDA/Phone browser)',
'mspie'=>'MS Pocket Internet Explorer (PDA/Phone browser)',
'nokia'=>'Nokia Browser (PDA/Phone browser)',
'panasonic'=>'Panasonic Browser (PDA/Phone browser)',
'philips'=>'Philips Browser (PDA/Phone browser)',
'sonyericsson'=>'Sony/Ericsson Browser (PDA/Phone browser)',
"up\."=>'UP.Browser (PDA/Phone browser)',					// Works for UP.Browser and UP.Link
'wapalizer'=>'WAPalizer (PDA/Phone browser)',
'wapsilon'=>'WAPsilon (PDA/Phone browser)',
'webcollage'=>'WebCollage (PDA/Phone browser)',
// PDA/Phonecell I-Mode browsers
'docomo'=>'I-Mode phone (PDA/Phone browser)',
'portalmmm'=>'I-Mode phone (PDA/Phone browser)',
// Others (TV)
'webtv'=>'WebTV browser',
// Other kind of browsers
'csscheck'=>'WDG CSS Validator',
'staroffice'=>'StarOffice',
'w3c_css_validator'=>'W3C CSS Validator',
'w3c_validator'=>'W3C HTML Validator',
'w3m'=>'w3m',
'wdg_validator'=>'WDG HTML Validator',
);
	}
	/* */
	function get_se_alias()
	{
		return array (
'irlbot'=>'IRLbot',
'msnbot'=>'MSN Search',
'noxtrumbot'=>'Noxtrum Search',
'friendlyspider'=>'FriendlySpider',
'apachebench'=>'ApacheBench',
'googlebot'=>'Googlebot',
'headdump'=>'HeadDump',
		);
	}
	/* */
	function get_dm_alias()
	{
		return array (
'22acidownload'=>'22AciDownload',
'bpftp'=>'BPFTP',
'downloadagent'=>'DownloadAgent',
'ecatch'=>'eCatch',
'getright'=>'GetRight',
'teleport'=>'TelePort Pro',
'tzgeturl'=>'TzGetURL',
'webcapture'=>'Acrobat',
'webcopier'=>'WebCopier',
'webfetcher'=>'WebFetcher',
'webmirror'=>'WebMirror',
'webvcr'=>'WebVCR',
'webzip'=>'WebZIP',
'wget'=>'Wget',
		);
	}
	/* */
	function get_os_alias()
	{
		return array (
"windows nt 6\.0"=>'Windows Vista',
"windows nt 5\.2"=>'Windows 2003',
"windows nt 5\.0"=>'Windows 2000',
"windows nt 5\.1"=>'Windows XP',
'winnt'=>'Windows NT',
"winnt 4\.0"=>'Windows NT',
'windows 98'=>'Windows 98',
'win98'=>'Windows 98',
'windows 95'=>'Windows 95',
'win95'=>'Windows 95',
'sunos'=>'Sun Solaris',
'freebsd'=>'FreeBSD',
'ppc'=>'Macintosh',
'mac os x'=>'Mac OS X',
'linux'=>'Linux',
'debian'=>'Debian',
'beos'=>'BeOS',
"winnt4\.0"=>'Windows NT 4.0',
'apachebench'=>'ApacheBench',
'aix'=>'AIX',
'irix'=>'Irix',
'osf'=>'DEC OSF',
'hp-ux'=>'HP-UX',
'netbsd'=>'NetBSD',
'bsdi'=>'BSDi',
'openbsd'=>'OpenBSD',
'gnu'=>'GNU/Linux',
'unix'=>'Unknown Unix system'
);
	}
	/* end of class */
}
/* */
$oUa = new gw_user_agent;
define('REMOTE_UA', $oUa->remote_ua);
#prn_r( $oUa->get_browser_str( $oUa->remote_ua ) );
#prn_r( $oUa->get_os_str( $oUa->remote_ua ) );
/* end of file */
?>