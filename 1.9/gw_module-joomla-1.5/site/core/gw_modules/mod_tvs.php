<?php
/* The common functions for $target */
class gw_mod_tvs extends site_prepend
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
	public function count_pids_total()
	{
		$this->oDb->select( 'count(*) as cnt' );
		$this->oDb->from( array( 'pid p' ) );
		$ar_sql = $this->oDb->get()->result_array();
		return isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0; 
	}
	/* */
	public function count_translated()
	{
		$this->oDb->select( 'count(*) cnt, tv.id_lang' );
		$this->oDb->from( array( 'tv tv' ) );
		$this->oDb->where( array( 'tv.is_complete' => '1' ) );
		$this->oDb->where_in( 'tv.id_lang', array( (string) $this->gv['area']['source'], (string) $this->gv['area']['target'] ) );
		$this->oDb->group_by( 'tv.id_lang' );
		$ar_sql = $this->oDb->get()->result_array();
		$ar = array( $this->gv['area']['source'] => 0, $this->gv['area']['target'] => 0 );
		foreach ( $ar_sql as $ar_v )
		{
			 $ar[$ar_v['id_lang']] = $ar_v['cnt'];
		}
		return $ar;
	}
	/* */
	public function get_statuses()
	{
 		return array( 
			TKIT_STATUS_OFF => $this->oTkit->_( 1070 ), 
			TKIT_STATUS_APPROVED => $this->oTkit->_( 1069 )
		);
	}
	/* */
	public function get_statuses_classnames()
	{
 		return array( 
			TKIT_STATUS_OFF => 'state-warning', 
			TKIT_STATUS_APPROVED => 'state-allow'
		);
	}
	/* */
	public function get_statuses_borders()
	{
		return  array(
			TKIT_STATUS_OFF => 'state-warning-border',
			TKIT_STATUS_APPROVED => 'state-allow-border'
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
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1182 ) ),
				$this->oTkit->_( 1006 )
			);
			$oHref->set( 'a', 'import' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1077 ).': '.$this->oTkit->_( 1182 ) ),
				$this->oTkit->_( 1077 )
			);
			$oHref->set( 'a', 'export' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1079 ).': '.$this->oTkit->_( 1182 ) ),
				$this->oTkit->_( 1079 )
			);
			/* */
			$oHref->set( 't', 'langs' );
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1181 ) ),
				$this->oTkit->_( 1181 ) . $this->V->str_class_shortcut
			);
			$oHref->set( 't', 'translations' );
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1190 ) ),
				$this->oTkit->_( 1190 ) . $this->V->str_class_shortcut
			);


		}
		return $ar_ctrl;
	}
}

?>