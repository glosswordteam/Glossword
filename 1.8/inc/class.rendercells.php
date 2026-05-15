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

/**
 * HTML table grid renderer
 * Renders an array of items into an HTML table grid with configurable columns/rows.
 *
 * Usage:
 *   $grid = new HtmlRenderCells();
 *   $grid->ar = $some_array;
 *   $grid->x = $columns;
 *   $grid->y = $rows_per_page;
 *   echo $grid->render_cells();
 */

class htmlRenderCells
{
    /** @var string Horizontal alignment for table rows */
    public $cell_align = '';

    /** @var string CSS class for table rows */
    public $cell_class = '';

    /** @var int Table border width */
    public $t_border = 0;

    /** @var int Number of columns */
    public $x = 1;

    /** @var int Max number of rows per page */
    public $y = 99;

    /** @var array Items to render */
    public $ar = [];

    /** @var int Current page number (1-based) */
    public $page = 1;

    /** @var string CSS class for the table element */
    public $t_class = '';

    /** @var int Total number of items currently in $ar; refreshed by RenderCells() */
    public $total_items = 0;

    /**
     * Calculates the flat array index for a cell at given grid position.
     *
     * @param int $num_cols  Number of columns in the grid
     * @param int $num_rows  Number of rows in the grid
     * @param int $col       Current column (1-based)
     * @param int $row       Current row (1-based)
     * @param int $pages     Current page offset (1-based)
     * @return int           Zero-based index into the items array
     */
    private function _rows_cols($num_cols, $num_rows, $col, $row, $pages = 0)
    {
        $num_start = 0;
        for ($i = 1; $i <= $num_rows; $i++) {
            if ($i == $row) {
                $num_start = ($i * $num_cols) + $col - $num_cols;
            }
        }
        $num_start = $num_start + ($num_cols * $num_rows) * $pages - ($num_cols * $num_rows);
        return $num_start;
    }

    public function RenderCells()
    {
        $href = "";
        $str = "";
        $navPagesA = array();
        $linkNext = $linkPrev = $linkCur = "";
        $cellAlign = ($this->cell_align != "") ? ' align="' . $this->cell_align . '"' : '';
        $cellClass = ($this->cell_class != "") ? ' class="' . $this->cell_class . '"' : '';
        $this->total_items = count($this->ar);

        $NumberOfAllThumbs = ($this->x * $this->y);
        $NumberOfPages = ceil($this->total_items / $NumberOfAllThumbs);
        $ColsTotalOne = intval($NumberOfAllThumbs / $this->x);
        $ColsTotalTwo = ($NumberOfAllThumbs / $this->x);
        if ($ColsTotalTwo > $ColsTotalOne){ $ColsTotalOne += 1; }
        $Y = $ColsTotalOne;
        $NumberOfEmpty = 0;
        if (($this->total_items - ($NumberOfAllThumbs * $this->page) ) < 0)
        {
            $NumberOfEmpty = ( ( ($this->x * $this->y) * $NumberOfPages) - $this->total_items );
        }
        if ($NumberOfEmpty > 0)
        {
            $Yauto = intval(($NumberOfAllThumbs - $NumberOfEmpty) / $this->x);
            $Yauto2 = (($NumberOfAllThumbs - $NumberOfEmpty) / $this->x);
            if ($Yauto2 > $Yauto){ $Yauto += 1; }
            $this->y = $ColsTotalOne = $Yauto;
        }

        $cellwidth = intval(100 / $this->x) . "%";

        $tbl_class = ($this->t_class) ? ' class="'.$this->t_class.'"' : '';
        $str .= '<table'.$tbl_class.' border="'.$this->t_border.'" cellspacing="1" cellpadding="0" width="100%">';
        for ($ThumbCols = 1; $ThumbCols <= $ColsTotalOne; $ThumbCols++)
        {
            // add <col width=""> after the first <tr>
            if ($ThumbCols == 1)
            {
                $intCellwidth = 0;
                for($ThumbRows = 1; $ThumbRows <= $this->x; $ThumbRows++)
                {
                    $intCellwidth += intval(100 / $this->x);
                    if ($ThumbRows == $this->x)
                    {
                        $cellwidth = $cellwidth + (100 - $intCellwidth) . '%';
                    }
                    $str.= '<col width="'.$cellwidth.'"/>';
                }
            }
            $str.= '<tr ' . $cellClass . $cellAlign . '>';
            // render <td>
            for($ThumbRows = 1; $ThumbRows <= $this->x; $ThumbRows++)
            {
                $NumberOfCell = ( $this->_rows_cols($this->y, $this->x, $ThumbCols, $ThumbRows, 1) - 1 );
                $str .= '<td>';
                $str .= isset($this->ar[$NumberOfCell]) ? $this->ar[$NumberOfCell] : '&#160;';
                $str.= '</td>';
            }
            $str.=  '</tr>';
        }
        $str .= '</table>';
        return $str;
    }

} // end of class
