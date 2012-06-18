<?
class xmlTinyParser {

    var $encoding   = "UTF-8";
    var $method     = "xml";

function parse($strXmlData = "", $strXslData = "")
{
    if ($strXmlData == ''){ $strXmlData = $this->getXmlData(); }
    if ($strXslData == ''){ $strXslData = $this->getXslData(); }
        
    $strXslData = $this->getXslData();

#   $strXslData = iconv("windows-1251", "UTF-8", $strXslData);

    $arguments = array(
        '/_xml' => $strXmlData,
        '/_xsl' => $strXslData
    );

    $xh = xslt_create();
    error_reporting(0);
    if ( xslt_process($xh, 'arg:/_xml', 'arg:/_xsl', NULL, $arguments) )
    {
        return true;
    }
    else
    {   
        $str = "<ul><li>Error string: " . xslt_error($xh) . "</li>\n";
        $str .= "<li>Error code: " . xslt_errno($xh) . "</li></ul>\n";
        return false;
    }
    xslt_free($xh);
}


function getXmlData(){
    $strXmlData = '<'.'?xml version="1.0"'.'?><body> Body </body>';

return $strXmlData;
}

function getXslData()
{

$strXslData = '
<xsl:stylesheet
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


}

?>
