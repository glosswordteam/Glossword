<?php
/**
 * Easy confirm window constructor
 *
 * @author   Dmitry Shilnikov <dev at glossword dot info>
 * @version  1.3
 */
class gwConfirmWindow
{
    var $strQuestion     = "Confirm?";
    var $strFields       = "";
    var $inputFieldtype  = "hidden";
    var $tAlign          = "center";
    var $formwidth       = "400";
    var $formname        = "post";
    var $enctype         = "application/x-www-form-urlencoded";    
    var $action          = "post.php";
    var $submitok        = " Yes ";
    var $submitcancel    = " No ";
    var $formbgcolor     = "#DDD";
    var $formbordercolor = "#444";
    var $formbordercolorL= "#FFF";
    var $css_align_right = 'right';
    var $css_align_left  = 'left'; 
    var $submitclass     = 'submitdel';
    
    /**
     * Constructs <input> tag
     *
     * @param    string      field type [ hidden | input ]
     * @param    string      field name
     * @param    string      name value
     */
    function setField($fieldtype, $var, $val)
    {
        $this->strFields .= '<input type="'.$fieldtype.'" name="'.$var.'" value="'.$val.'" />';
    } // end of setField();

    /**
     * Sets question to form
     *
     * @param    string      Question text
     * @return   string      Question text
     */
    function setQuestion($text)
    {
        $this->strQuestion=$text;
    } // end of setQuestion();

    /**
     * Constructs confirmation window
     *
     * @return   string      full html-code for form
     */
    function Form()
    {
        $str = "";
        $str .= '<div style="text-align:center"><form name="'.$this->formname.'" action="'.$this->action.'" enctype="'.$this->enctype.'" method="post" style="margin:0">';
        $str .= '<table width="1%" border="0" cellspacing="1" cellpadding="1" style="margin:0 auto;background:'.$this->formbordercolor.'"><tr><td style="background-color:'.$this->formbordercolorL.'">';
        $str .= '<table width="'.$this->formwidth.'" border="0" cellspacing="0" cellpadding="5" style="background:'.$this->formbgcolor.'">';
    
        $str .= '<tr>';
        $str .= '<td align="'.$this->css_align_left.'" style="background:'.$this->formbgcolor.'">';
        $str .= $this->strQuestion;
        $str .= "</td>";
        $str .= "</tr>";
    
        $str .= '<tr align="center" style="background-color:'.$this->formbgcolor.'">';
        $str .= '<td>';
            $str .= '<table width="150" border="0" cellpadding="0" id="confirmboxtable"><tr align="center">';
            $str .= '<td width="50%">';
            $str .= '<input class="'.$this->submitclass.'" type="submit" value="'.$this->submitok.'" ';
            $str .= "onclick=\"document.all.confirmboxtable.style.visibility='hidden'\"/></td>";
            $str .= '<td width="50%">';
            $str .= '<input type="reset" value="'.$this->submitcancel.'" class="submitcancel" onclick="history.back(-1);document.all.confirmboxtable.style.visibility=\'hidden\';"/></td>';
            $str .= "</tr></table>";
        $str .= "</td>";
        $str .= "</tr>";
        $str .= "</table>";
        
        $this->setField("hidden", "isConfirm", 1);
        $str .= $this->strFields;
        
        $str .= "</td></tr></table>";
        $str .= "</form></div>";
        return $str;
    } // end of Form();

    /**
     * Debug helper
     *
     * @return   string  html-code only for fileds
     */
    function FieldsOnly()
    {
        return $this->strFields;
    } // end of FieldsOnly();

} // end of class gwConfirmWindow

?>