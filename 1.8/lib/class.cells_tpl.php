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
	Draws a table, X and Y
	Based on template engine, $oTpl = new sh_template();

	Usage:
		$arVar[0]['non:tp_parent'] = 'q';
		$arVar[0]['tp_subparent'][0]['non:tp_subparent'] = 'qwe';
		$arVar[0]['tp_subparent'][1]['non:tp_subparent'] = 'asd';
		$arVar[1]['non:tp_parent'] = 'w';
		$arVar[2]['non:tp_parent'] = 'e';
		$arVar[3]['non:tp_parent'] = 'r';
		$arVar[4]['non:tp_parent'] = 't';
		$arVar[5]['non:tp_parent'] = 'y';

		$oCells = new gw_cells_tpl();
		$oCells->tpl = 'tpl_links_index';
		$oCells->int_total = 123;
		$oCells->int_page = 1;
		$oCells->is_odd = 1;
		$oCells->X = 4;
		$oCells->Y = 3;
		$oCells->tSpacing = 1;
		$oCells->tPadding = 2;
		$oCells->tAttrClass = 'classname';
		$oCells->arK = $arVar;
		return $oCells->output();
-------------------------------------------------------- */

class gw_cells_tpl
{
    public $int_total = -1;
    public $int_page = 1;
    public $X = 1;
    public $Y = 99;
    public $arK = array();
    public $tBorder = 0;
    public $tSpacing = 1;
    public $is_odd = 0;
    public $tPadding = 0;
    public $tAttrClass = '';
    public $tpl = 'tpl_cells';
    public $id_theme = 1;
    public $class_tpl = 'sh_template';
    public $arVar = array();

    /* */
    public function RowsCols($numCols, $numRows, $col, $row, $pages = 0)
    {
        $numStart = $num = 0;
        for ($i = 1; $i <= $numRows; $i++) {
            if ($i == $row) {
                $numStart = ($i * $numCols) + $col - $numCols;
            }
        }
        $numStart = $numStart + ($numCols * $numRows) * $pages - ($numCols * $numRows);

        return $numStart;
    }

    /* */
    public function get_tmp()
    {
        $tmp = array();
        /* */
        $tmp['x']               = $this->X;
        $tmp['y']               = $this->Y;
        $tmp['int_page']        = $this->int_page;
        $tmp['int_total_items'] = ($this->int_total < 0) ? sizeof($this->arK) : $this->int_total;
        /* */
        $tmp['int_total_cells']     = ($tmp['x'] * $tmp['y']);
        $tmp['int_total_pages']     = ceil($tmp['int_total_items'] / $tmp['int_total_cells']);
        $tmp['int_columns_total']   = intval($tmp['int_total_cells'] / $tmp['x']);
        $tmp['float_columns_total'] = ($tmp['int_total_cells'] / $tmp['x']);
        if ($tmp['float_columns_total'] > $tmp['int_columns_total']) {
            $tmp['int_columns_total'] += 1;
        }
        $Y = $tmp['int_columns_total'];
        /* how many empty cells */
        $tmp['int_empty'] = 0;
        if (($tmp['int_total_items'] - ($tmp['int_total_cells'] * $tmp['int_page'])) < 0) {
            $tmp['int_empty'] = ((($tmp['x'] * $tmp['y']) * $tmp['int_total_pages']) - $tmp['int_total_items']);
        }
        /* correct the number of rows of table */
        if ($tmp['int_empty'] > 0) {
            $tmp['int_y_auto']   = intval(($tmp['int_total_cells'] - $tmp['int_empty']) / $tmp['x']);
            $tmp['float_y_auto'] = (($tmp['int_total_cells'] - $tmp['int_empty']) / $tmp['x']);
            if ($tmp['float_y_auto'] > $tmp['int_y_auto']) {
                $tmp['int_y_auto'] += 1;
            }
            $tmp['y'] = $tmp['int_columns_total'] = $tmp['int_y_auto'];
        }
        $tmp['auto_td_width'] = intval(100 / $tmp['x']) . "%";

        return $tmp;
    }

    /* */
    public function output()
    {
        /* -------------------------------------------- */
        /* Set internal Template class */
        $oTpl = new $this->class_tpl();
        $oTpl->init($this->id_theme);
        $oTpl->set_tpl($this->tpl);
        /* -------------------------------------------- */
        /* Renumber the keys */
        if ( ! $this->arK) {
            return false;
        }
        $this->arK = array_values( $this->arK );
        /* -------------------------------------------- */
        $arKeys = empty($this->arK[0]) ? array() : array_keys($this->arK[0]);
        $tmp    = $this->get_tmp();
        /* render <tr> */
        $bgcolorclass = 'even';
        for ($column_tr = 1; $column_tr <= $tmp['int_columns_total']; $column_tr++) {
            $tmp['int_td_width'] = 0;
            if ($this->is_odd) {
                /* Set current background color */
                $bgcolorclass = ($column_tr % 2) ? 'odd' : 'even';
            }
            $oTpl->assign(array('cells_tr:attr' => ' class="' . $bgcolorclass . '"'));
            /* render <td> */
            for ($column_td = 1; $column_td <= $tmp['x']; $column_td++) {
                $cur_cell = ($this->RowsCols($tmp['y'], $tmp['x'], $column_tr, $column_td, 1) - 1);
                $arV      = isset($this->arK[$cur_cell]) ? $this->arK[$cur_cell] : '';
                /* initialize internal variables */
                foreach ( $arKeys as $k => $v ) {
                    $oTpl->assign(array($v => ''));
                }
                /* set `width' attribute for the first row */
                if ($column_tr == 1) {
                    $tmp['int_td_width'] += intval(100 / $tmp['x']);
                    if ($column_td == $tmp['x']) {
                        $tmp['auto_td_width'] = $tmp['auto_td_width'] + (100 - $tmp['int_td_width']) . '%';
                    }
                    $oTpl->assign(array('cells_td:attr' => ' style="width:' . $tmp['auto_td_width'] . '"'));
                }
                /* not empty cell */
                if ($arV != '') {
                    /* define internal variables */
                    foreach( $arKeys as $k => $v ) {
                        /* TODO: unlimited sublevels */
                        #prn_r( $this->arK[$cur_cell], $v  );
                        if ( ! isset($this->arK[$cur_cell][$v])) {
                            continue;
                        }
                        if (is_array($this->arK[$cur_cell][$v])) {
                            foreach( $this->arK[$cur_cell][$v] as $k2 => $v2 ) {
                                $oTpl->assign($v2);
                                $oTpl->parseDynamic($v);
                            }
                        } else {
                            $oTpl->assign(array($v => $this->arK[$cur_cell][$v]));
                        }
                    }
                    $oTpl->parseDynamic('if:td');
                }
                $oTpl->parseDynamic('cells_td');
            }
            $oTpl->parseDynamic('cells_tr');
        }
        $oTpl->assign($this->arVar);
        $oTpl->assign(array(
            'v:cells_border'  => $this->tBorder,
            'v:cells_padding' => $this->tPadding,
            'v:cells_spacing' => $this->tSpacing,
            'v:cells_class'   => $this->tAttrClass,
        ));
        $oTpl->parse();

        return $oTpl->output();
    }
}
