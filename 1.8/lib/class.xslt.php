<?php
/**
 *  HTML-rendering
 *  primary used for Glossword (glossary compiler)
 *  ==============================================
 *  Copyright (c) 2003 Dmitry Shilnikov <dev at glossword dot info>
 *  http://glossword.info/dev/
 */
// --------------------------------------------------------
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.xslt.php 470 2008-05-14 16:25:33Z yrtimd $ -->');
}
// --------------------------------------------------------

if (!defined('IS_CLASS_GWXSLT'))
{
	define('IS_CLASS_GWXSLT', 1);

class gw_xslt {

	var $is_xslt_create = '';
	var $is_xml_parse   = '';
	var $encoding       = 'UTF-8';
	var $method         = "xml";
	   
	function gw_xslt()
	{
		global $oL;
		$this->is_xslt_create = function_exists('xslt_create');
		$this->is_xml_parse   = function_exists('xml_parse');
		if (!$this->is_xml_parse)
		{
			$this->showError(1, sprintf($oL->m('reason_8'), '<b>xml_parse</b>') );
		}
	}
	
	function xslt_process($xml, $xslt)
	{

	}	

	function parse($strXmlData = "", $strXslData = "")
	{
		if ($strXmlData == ''){ $strXmlData = $this->getXmlData(); }
		if ($strXslData == ''){ $strXslData = $this->getXslData(); }
		//
		$this->xslt_process($strXmlData, $strXslData);    
	}

	function getXmlData()
	{
		$strXmlData = '<'.'?xml version="1.0"'.'?><body> Body </body>';
		return $strXmlData;
	}
	function getXslData()
	{
		$strXslData = '<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:output method="' . $this->method . '" indent="no" encoding="' . $this->encoding . '"/>
<xsl:preserve-space elements="pre"/>
<xsl:strip-space elements="*"/>
<xsl:template match="*">
<xsl:apply-templates/>
</xsl:template>
</xsl:stylesheet>
';
		return $strXslData;
	}



	
	function showError($level, $msg)
	{
		// Error levels:
		// 1 - critical
		// 2 - database
		// 3 - post 
		// 4 - get 
		exit($msg); 
	}

} // end of class

}



?>