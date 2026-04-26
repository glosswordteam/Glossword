<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */

if (!defined('IN_GW')) {
    die('<!-- Not in App -->');
}
/**
 *  HTML-rendering
 *  primary used for Glossword (glossary compiler)
 */

if (!defined('IS_CLASS_GWXSLT')) {
    define('IS_CLASS_GWXSLT', 1);

    class gw_xslt
    {

        public $is_xslt_create = '';
        public $is_xml_parse   = '';
        public $encoding       = 'UTF-8';
        public $method         = "xml";

        public function __construct()
        {
            global $oL;
            $this->is_xslt_create = function_exists('xslt_create');
            $this->is_xml_parse = function_exists('xml_parse');
            if (!$this->is_xml_parse) {
                $this->showError(1, sprintf($oL->m('reason_8'), '<b>xml_parse</b>'));
            }
        }

        public function xslt_process($xml, $xslt) {}

        public function parse($strXmlData = "", $strXslData = "")
        {
            if ($strXmlData == '') {
                $strXmlData = $this->getXmlData();
            }
            if ($strXslData == '') {
                $strXslData = $this->getXslData();
            }
            //
            $this->xslt_process($strXmlData, $strXslData);
        }

        public function getXmlData()
        {
            $strXmlData = '<' . '?xml version="1.0"' . '?><body> Body </body>';
            return $strXmlData;
        }

        public function getXslData()
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


        public function showError($level, $msg)
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

