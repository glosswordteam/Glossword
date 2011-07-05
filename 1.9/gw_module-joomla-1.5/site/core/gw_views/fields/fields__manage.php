<?php
/**
 * $Id$
 */
if (!defined('IS_IN_SITE')){die();}

/* Set HTML-template group */
$this->a( 'id_tpl_page', GW_TPL_ADM );

/* Select Fieldsets */
$this->oDb->select( 'id_fieldset, fieldset_name' );
$this->oDb->from( 'fieldsets' );
$this->oDb->order_by( 'mdate DESC' );
$ar_fieldsets_sql = $this->oDb->get()->result_array();
$ar_fieldsets = array();
foreach ( $ar_fieldsets_sql as $ar_v)
{
	$ar_fieldsets[$ar_v['id_fieldset']] = $ar_v['fieldset_name'];
}
/* Default fieldset */
$id_fieldset = current( array_keys( $ar_fieldsets ) );


$oBlock->oTpl = $this->_init_html_tpl();
$oBlock->oTpl->set_tpl( 'fields.manage' );

$this->oDb->select( 'f.*' );
$this->oDb->from( array( 'fields f', 'map_field_to_fieldset mftf' ) );
$this->oDb->where( array( 'f.id_field = mftf.id_field' => NULL, 'mftf.id_fieldset' => $id_fieldset ) );
$this->oDb->order_by( 'int_sort ASC' );
$ar_fields_sql = $this->oDb->get()->result_array();
$cnt = 1;
foreach ( $ar_fields_sql as $ar_v)
{
	$oBlock->oTpl->assign( 'fields.list.cnt', $cnt );
	$oBlock->oTpl->assign( 'fields.list.xml_tag', $ar_v['xml_tag'] );
	$oBlock->oTpl->assign( 'fields.list.field_name', $ar_v['field_name'] );
	$oBlock->oTpl->parse_block('fields.list');
	++$cnt;
}

$oBlock->oTpl->assign( 'v:th_n', '№' );
$oBlock->oTpl->assign( 'l:1023', $this->oTkit->_(1023) );
$oBlock->oTpl->assign( 'l:1024', $this->oTkit->_(1024) );
$oBlock->oTpl->assign( 'l:1025', $this->oTkit->_(1025) );
$oBlock->oTpl->assign( 'v:id_table', 'fields-list' );
$this->oOutput->append_html( $oBlock->oTpl->get_html() );


/**
 * ----------------------------------------------
 * Document title and <H1>
 * ----------------------------------------------
 */
if ( $this->gv['sef_output'] != 'js' || $this->gv['sef_output'] != 'css' || $this->gv['sef_output'] != 'ajax' )
{
	$this->oOutput->append_html_title( $this->oTkit->_( 1020 ).': '.$this->oTkit->_( 1006 ) );
	$this->oTpl->addVal( 'v:h1', $this->oTkit->_( 1020 ).': '.$this->oTkit->_( 1006 ) );
}

?>