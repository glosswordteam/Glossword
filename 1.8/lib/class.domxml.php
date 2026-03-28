<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2021 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* --------------------------------------------------------
 * Easy DOM XML class, helps to parse XML files
 * Obsoleted by XPath.
 * ----------------------------------------------------- */
if ( ! class_exists('gw_domxml')) {
    /* ------------------------------------------------------*/
    $tmp['mtime']      = explode(' ', microtime());
    $tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];

    /* ------------------------------------------------------*/

    class gw_domxml
    {
        /**
         *
         */
        public $strData = '<xml><line type="123">abc<level>qwerty</level></line><line type="456">def</line></xml>';
        public $arData = array();
        public $vals = '';
        public $index = '';
        public $is_skip_white = 1;
        public $is_case_folding = 0;
        public $max_nesting = 0;
        public $msg_error = '&#160;';
        public $txt_magic_splitter = "&#xA;";

        /**
         *
         */
        public function get_xml_content($tagname, $key = 3)
        {
            /* preg_match! */
            preg_match_all("/<" . $tagname . "( (.*?))*>(.*?)<\/" . $tagname . ">/si", $this->strData, $arFound);

            return isset($arFound[$key]) ? $arFound[$key] : array();
        }

        /**
         *
         */
        public function get_content($arElement, $tagname = '')
        {
            /* current $arElement only */
            if ($tagname == '') {
                $arReturn = array();
                if ( ! is_array($arElement)) {
                    $arReturn[] = $arElement;
                }
                /* single tag value */
                if (is_array($arElement) && isset($arElement['value'])) {
                    array_push($arReturn, $arElement['value']);
                } elseif (is_array($arElement)) {
                    /* multiple tags value */
                    /* each founded element: 0, 1 */
                    foreach ($arElement as $elK1 => $elV1) {
                        $arReturn = array_merge($arReturn, array($this->get_content($elV1)));
                    }
                }

                return implode(' ', $arReturn);
            }
            /* go for tree */
            $tagname = strtolower($tagname);
            foreach ($arElement as $elK => $elV) {
                if ( ! is_array($elV) && sprintf("%s", $elV) == $tagname) {
                    return isset($arElement['value']) ? $arElement['value'] : '';
                } elseif (sprintf("%s", $elK) == 'children') {
                    foreach ($elV as $elK2 => $elV2) {
                        if ($this->get_content($elV2, $tagname)) {
                            return $this->get_content($elV2, $tagname);
                        }
                    }

                    return array();
                }
            }
        }

        /**
         *
         */
        public function get_attribute($attrname, $tagname, $a = array())
        {
            $attrname = strtolower($attrname);
            $tagname  = strtolower($tagname);
            /* do not parse strings */
            if ( ! is_array($a)) {
                return '';
            }
            /* fix array without zero key [0] */
            if ( ! isset($a[0])) {
                $a = array($a);
            }
            /* fix empty array */
            if (empty($a)) {
                $a = $this->arData;
            }
            /* for each founded element: 0, 1 */
            foreach ($a as $elK1 => $elV1) {
                if (is_array($elV1) && isset($elV1['attributes']) && isset($elV1['tag']) && ($elV1['tag'] == $tagname)) {
                    // per attrib, tag, children
                    return isset($elV1['attributes'][$attrname]) ? $elV1['attributes'][$attrname] : '';
                } elseif (is_array($elV1) && isset($elV1['attributes']) && $tagname == '') {
                    /* no tag */
                    return isset($elV1['attributes'][$attrname]) ? $elV1['attributes'][$attrname] : '';
                } elseif (is_array($elV1) && isset($elV1['children']) && is_array($elV1['children'])) {
                    #return $this->get_attribute($attrname, $tagname, $elV1['children']);
                }
            }

            return '';
        }

        /* */
        public function get_elements_by_tagname($tagname, $a = array())
        {
            $tagname  = strtolower($tagname);
            $arReturn = array();
            if (empty($a)) {
                $a = $this->arData;
            }
            /* each founded element: 0, 1 */
            foreach ($a as $elK1 => $elV1) {
                if (isset($elV1['tag']) && ($elV1['tag'] == $tagname)) /* per attrib, tag, children */ {
                    array_push($arReturn, $elV1);
                } else {
                    if (isset($elV1['children']) && is_array($elV1['children'])) {
                        $arReturn = array_merge($arReturn, $this->get_elements_by_tagname($tagname, $elV1['children']));
                    }
                }
            }

            return $arReturn;
        }

        /**
         *
         */
        public function get_children($vals, &$i)
        {
            $children = array();
            $cntVals  = sizeof($vals);
            /* TODO: limit nesting levels */
            if ($vals[$i]['level'] > 4) {
                /*
                $str_value = '';
                switch ($vals[$i]['type'])
                {
                    case 'cdata':
                        $str_value .= $vals[$i]['value'];
                    break;
                    case 'complete':
                        $str_value .= '['.$vals[$i]['tag'].']'.$vals[$i]['value'].'[/'.$vals[$i]['tag'].']';
                    break;
                    case 'open':
                        $str_value .= '['.$vals[$i]['tag'].']';
                    break;
                }
                */
            }
            while (++$i < $cntVals) {
                /* Compare types */
                switch ($vals[$i]['type']) {
                    case 'cdata':
                        $children[] = $vals[$i]['value'];
                        break;
                    case 'complete':
                        $children[] = array(
                            'tag'        => $vals[$i]['tag'],
                            'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
                            'value'      => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
                        );
                        break;
                    case 'open':
                        $children[] = array(
                            'tag'        => $vals[$i]['tag'],
                            'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
                            'value'      => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
                            'children'   => $this->get_children($vals, $i)
                        );
                        break;
                    case 'close':
                        return $children;
                        break;
                }
            }
        }

        /**
         *
         */
        public function xml2array()
        {
            /* http://www.w3.org/TR/REC-xml/#AVNormalize */
            # $this->strData = preg_replace("/(\r\n|\n|\r)/", $this->txt_magic_splitter, $this->strData);
            $p = xml_parser_create('UTF-8');
            @xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, $this->is_skip_white);
            @xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, $this->is_case_folding);
            xml_parse_into_struct($p, $this->strData, $vals);
            xml_parser_free($p);
            /* */
            $ar_last = end($vals);
            if (isset($ar_last['level'])) {
                $this->msg_error = 'XML level: <strong>' . $ar_last['level'] . '</strong>, tag: <strong>' . $ar_last['tag'] . '</strong>, type: <strong>' . $ar_last['type'] . '</strong>';
            }
            if (isset($ar_last['value'])) {
                $this->msg_error .= ', value: ' . htmlspecialchars(substr($ar_last['value'], 0, 128));
            }
            $tree = array();
            $i    = 0;
            if ( ! empty($vals)) {
                $tree[] = array(
                    'tag'        => $vals[$i]['tag'],
                    'attributes' => isset($vals[$i]['attributes']) ? $vals[$i]['attributes'] : '',
                    'value'      => isset($vals[$i]['value']) ? $vals[$i]['value'] : '',
                    'children'   => $this->get_children($vals, $i),
                );
            } else {
                $this->msg_error = 'No XML data';
            }

            return $tree;
        }

        /**
         *
         */
        public function SetCustomArray($ar)
        {
            $this->arData = $ar;
        }

        /**
         *
         */
        public function parse()
        {
            /* Remove UTF-8 Signature */
            if (substr($this->strData, 0, 3) == sprintf('%c%c%c', 239, 187, 191)) {
                $this->strData = substr($this->strData, 3);
            }
            $this->arData = $this->xml2array();
        }
    } /* end of class */
    /* ------------------------------------------------------*/
    $tmp['mtime']          = explode(' ', microtime());
    $tmp['endtime']        = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
    $tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
}
