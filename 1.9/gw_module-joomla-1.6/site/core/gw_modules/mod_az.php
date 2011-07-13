<?php
/* The common functions for $target */
class gw_mod_az extends site_prepend
{
	/* */
	public function autoexec()
	{
		if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
		{
			$this->oTpl->assign( 'v:h_tabs', '<div class="gw-actions">'.implode(' ', $this->get_actions() ).'</div>' );
		}
	}
	/* */
	public function get_actions()
	{
		$oHref = $this->oHtmlAdm->oHref();
		$oHref->set( 't', $this->gv['target'] );
		$ar_ctrl = array();
		if ( $this->oSess->is( 'sys-settings' ) )
		{
			$oHref->set( 'a', 'add' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1001 ).': '.$this->oTkit->_( 1209 ) ),
				$this->oTkit->_( 1001 )
			);
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1209 ) ),
				$this->oTkit->_( 1006 )
			);
			$oHref->set( 'a', 'import' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1077 ).': '.$this->oTkit->_( 1209 ) ),
				$this->oTkit->_( 1077 )
			);
			$oHref->set( 'a', 'export' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1079 ).': '.$this->oTkit->_( 1209 ) ),
				$this->oTkit->_( 1079 )
			);
		}
		return $ar_ctrl;
	}
}

?>