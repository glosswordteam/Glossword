<?php
/**********************************************************
*  HTML tools
*  =============================
*  Copyright (c) 2002 Dmitry Shilnikov <dev at glossword dot info>
*
*  $Id: class.rendercells.php 84 2007-06-19 13:01:21Z yrtimd $
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
*
* @author   Dmitry Shilnikov <dev at glossword dot info>
*
* Usage:
*   $class = new htmlRenderCells();
*   $class->ar = $somearray; // $ar[] = 'cell 1';
*   $class->X = $x;
*   $class->Y = $y;
*   print $class->RenderCells();
*
*/

class htmlRenderCells
{
    var $cellAlign = "";
    var $cellClass = "";
    var $tBorder = 0;
    var $X = 1;
    var $Y = 99;
    var $ar = array();
    var $totalItems = '';
    var $page = 1;
    var $tClass = '';

    function RowsCols($numCols, $numRows, $col, $row, $pages=0)
    {
        $numStart = $num = "0";
        for ($i=1; $i <= $numRows; $i++)
        {
            if ($i == $row)
            {
                $numStart = ($i * $numCols) + $col - $numCols;
            }
        }
        $numStart = $numStart + ($numCols*$numRows) * $pages - ($numCols*$numRows);
        return $numStart;
    }

function RenderCells()
{
    $href = "";
    $str = "";
    $navPagesA = array();
    $linkNext = $linkPrev = $linkCur = "";
    $cellAlign = ($this->cellAlign != "") ? ' align="' . $this->cellAlign . '"' : '';
    $cellClass = ($this->cellClass != "") ? ' class="' . $this->cellClass . '"' : '';
    $this->totalItems = count($this->ar);

    $NumberOfAllThumbs = ($this->X * $this->Y);
    $NumberOfPages = ceil($this->totalItems / $NumberOfAllThumbs);
    $ColsTotalOne = intval($NumberOfAllThumbs / $this->X);
    $ColsTotalTwo = ($NumberOfAllThumbs / $this->X);
    if ($ColsTotalTwo > $ColsTotalOne){ $ColsTotalOne += 1; }
    $Y = $ColsTotalOne;
    $NumberOfEmpty = 0;
    if (($this->totalItems - ($NumberOfAllThumbs * $this->page) ) < 0)
    {
        $NumberOfEmpty = ( ( ($this->X * $this->Y) * $NumberOfPages) - $this->totalItems );
    }
    if ($NumberOfEmpty > 0)
    {
        $Yauto = intval(($NumberOfAllThumbs - $NumberOfEmpty) / $this->X);
        $Yauto2 = (($NumberOfAllThumbs - $NumberOfEmpty) / $this->X);
        if ($Yauto2 > $Yauto){ $Yauto += 1; }
        $this->Y = $ColsTotalOne = $Yauto;
    }

    $cellwidth = intval(100 / $this->X) . "%";

	$tbl_class = ($this->tClass) ? ' class="'.$this->tClass.'"' : '';
    $str .= '<table'.$tbl_class.' border="'.$this->tBorder.'" cellspacing="1" cellpadding="0" width="100%">';
    for ($ThumbCols = 1; $ThumbCols <= $ColsTotalOne; $ThumbCols++)
    {
        // add <col width=""> after the first <tr>
        if ($ThumbCols == 1)
        {
            $intCellwidth = 0;
            for($ThumbRows = 1; $ThumbRows <= $this->X; $ThumbRows++)
            {
                $intCellwidth += intval(100 / $this->X);
                if ($ThumbRows == $this->X)
                {
                    $cellwidth = $cellwidth + (100 - $intCellwidth) . '%';
                }
                $str.= '<col width="'.$cellwidth.'"/>';
            }
        }
        $str.= '<tr ' . $cellClass . $cellAlign . '>';
        // render <td>
        for($ThumbRows = 1; $ThumbRows <= $this->X; $ThumbRows++)
        {
            $NumberOfCell = ( $this->RowsCols($this->Y, $this->X, $ThumbCols, $ThumbRows, 1) - 1 );
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


?>