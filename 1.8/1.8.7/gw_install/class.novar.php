<?php
class gw_setup_novar extends gw_setup
{
	/* */
	function novar_step_0()
	{
		$this->ar_tpl[] = 'i_step_3.html';
		$this->oTpl->a( 'v:html_title', '');
		$this->oTpl->a( 'v:html_descr', '');
		
		$this->str_after .= '<p>';
		$this->str_after .= $this->oL->m('1237').' '.$this->oL->m('1238');
		$this->str_after .= '</p>';
		$this->str_after .= '<ul>';
		$this->str_after .= '<li>'.$this->oL->m('1240').' '.sprintf($this->oL->m('1239'), THIS_SCRIPT).'</li>';
		$this->str_after .= '<li>'.$this->oL->m('1242').' '.sprintf($this->oL->m('1241'), 'db_config.php').'</li>';
		$this->str_after .= '</ul>';
		
	}
}
?>