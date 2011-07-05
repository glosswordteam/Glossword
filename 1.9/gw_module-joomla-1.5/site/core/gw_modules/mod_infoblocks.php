<?php
/* The common functions for $target */
class gw_mod_infoblocks extends site_prepend
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
	public function get_statuses()
	{
 		return array( 
			GW_STATUS_OFF => $this->oTkit->_( 1070 ), 
			GW_STATUS_ON => $this->oTkit->_( 1069 ), 
			GW_STATUS_REMOVE => $this->oTkit->_( 1073 ) 
		);
	}
	/* */
	public function get_statuses_classnames()
	{
 		return array( 
			GW_STATUS_OFF => 'state-warning', 
			GW_STATUS_ON => 'state-allow', 
			GW_STATUS_REMOVE => 'state-warning' 
		);
	}
	/* */
	public function get_places()
	{
		return array( 
			'1' => $this->oTkit->_( 1059 ), 
			'2' => $this->oTkit->_( 1064 ) 
		);
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
				array( 'title' => $this->oTkit->_( 1001 ).': '.$this->oTkit->_( 1054 ) ),
				$this->oTkit->_( 1001 )
			);
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1054 ) ),
				$this->oTkit->_( 1006 )
			);
			$oHref->set( 'a', 'import' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1077 ).': '.$this->oTkit->_( 1054 ) ),
				$this->oTkit->_( 1077 )
			);
			$oHref->set( 'a', 'export' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1079 ).': '.$this->oTkit->_( 1054 ) ),
				$this->oTkit->_( 1079 )
			);
		}
		return $ar_ctrl;
	}
}

?>