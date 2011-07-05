<?php
/**
 * $Id: user__htmlform.php 138 2009-09-25 09:13:52Z dshilnikov $
 */
if (!defined('IS_IN_SITE')){die();}

/* Correct unknown settings */
$ar_settings = array();
/* Checkboxes */
$ar_onoff = array();
/* Required fields */
$ar_required = array();

switch ($this->gv['action'])
{
	case 'edit':
		$ar_settings = array(
			'password1' => '', 'password2' => ''
		);
		$ar_onoff = array( 'is_visible' );
	break;
}

/* Switch between sections */
if (!isset($this->gv['area']['s'])){ $this->gv['area']['s'] = ''; }

/* */
foreach ($ar_settings as $k => $v)
{
	if (!isset($arV[$k])) { $arV[$k] = $v; }
}
/* */
class site_htmlforms extends site_forms3_validation
{
	/* make html-code */
	function get_form($ar)
	{
		$this->phrase_wait = $this->o->oTkit->_(1082);
		$this->phrase_incorrect = $this->o->oTkit->_(1065);
		$this->phrase_submit_ok = $this->o->oTkit->_(1017);
		$this->phrase_submit_cancel = $this->o->oTkit->_(1018);

		$this->set_tag( 'form', 'action', $this->o->V->server_dir_admin.'/'.$this->o->V->file_index );
		$this->set_tag( 'form', 'id', $this->o->gv['action'] );
		$this->set_tag( 'form', 'onkeypress', 'jsF.formKeypress(event, this)' );

		$this->is_htmlspecialchars = 0;
		$this->is_actions = 0;
		$this->is_actions_top = 1;
		$this->is_label_ids = 1;
		$this->is_submit_ok = 1;
		$this->is_submit_cancel = 1;

		/* Display `Settings saved` notice */
		if ( isset( $this->o->gv['area']['is_saved'] ) && $this->o->gv['area']['is_saved'] ) 
		{
			$this->o->notice_onsubmit( $this->o->oTkit->_( 1041 ), true );
		}
		
		$this->set_tag( 'select', 'class', 'inp' );
		$this->set_tag( 'input', 'class', 'inp' );
		
		if ($this->o->gv['action'] == 'add')
		{
			$this->phrase_submit_ok = $this->o->oTkit->_(1068);
		}
		
		/* The list of usergroups */
		/* @todo: put to cache */
		$this->o->oDb->select( 'ug.*' );
		$this->o->oDb->from( $this->o->V->db_table_groups.' ug' );
		if ($this->o->gv['action'] == 'add')
		{
			$this->o->oDb->where( array('ug.id_group NOT IN (1,4)' => NULL) );
		}
		$this->o->oDb->order_by( 'ug.group_name ASC' );
		$ar_groups_list = $this->o->oDb->get()->result_array();
		$ar_groups = array();
		foreach( $ar_groups_list as $v )
		{
			$ar_groups[$v['id_group']] = $v['group_name'];
		}
			
		/** 
		 * Conditions:
		 * - use full view when adding a new user
		 * - use full view when editing the user
		 * - use a compact view when editing own profile
		 */
		if ($this->o->gv['area']['s'] == '' && $this->o->gv['action'] == 'edit')
		{
			$this->is_submit_ok = 0;
			$str_date_format = "%d %M %Y %H:%s";
			
			$this->new_fieldset( 'user', $ar['login'] );
			$this->new_label( $this->o->oTkit->_(1019), $this->o->oTkit->date($str_date_format, strtotime($ar['date_login'])) );
			$this->new_label( $this->o->oTkit->_(1018), $this->o->oTkit->date($str_date_format, strtotime($ar['date_reg'])) );
			$this->new_label( $this->o->oTkit->_(1020), $ar_groups[$ar['id_group']] );
			#$this->new_label( ' ', $this->o->oTkit->number_format($this->o->oSess->user_get('cnt_comments')) );
			#$this->new_label( ' ', $this->o->oTkit->number_format($this->o->oSess->user_get('cnt_messages')) );
			#$this->new_label( $this->o->oTkit->_(1035), $this->o->oTkit->number_format($this->o->oSess->user_get('cnt_tv')).' ' );
			
			
			#prn_r( $this->o->oSess->user_get('date_login') );
			#prn_r( $this->o->oSess->user_get('date_reg') );
			#prn_r( $this->o->oSess->user_get('group_name') );
			#prn_r( $this->o->oSess->user_get('cnt_comments') );
			#prn_r( $this->o->oSess->user_get('cnt_items') );
		}
		
		/* */
		if (($this->o->gv['area']['s'] == 'permissions' && $this->o->V->is_profile_owner)
			|| ($this->o->gv['action'] == 'add' || $this->o->gv['action'] == 'edit') && !$this->o->V->is_profile_owner)
		{
			$this->is_submit_ok = 1;
			$this->new_fieldset( 'permissions', $this->o->oTkit->_(1147) );

			if ( $this->o->oSess->is('users') && !$this->o->V->is_profile_owner )
			{
				$this->new_label( $this->o->oTkit->_(1146), $this->field('select',  'arp[id_group]', $ar['id_group'], $ar_groups) );
			}
			else
			{
				$this->new_label( $this->o->oTkit->_(1146), '<em class="disabled">'.$ar_groups[$ar['id_group']].'</em>' );
			}

			/* Statements for the user */
			/* @todo: create function to retrieve statements */
			$ar_status = array( $this->o->oTkit->_(1022), GW_STATUS_ON => $this->o->oTkit->_(1023), GW_STATUS_PENDING => $this->o->oTkit->_(1024), GW_STATUS_REMOVE => $this->o->oTkit->_(1025) );
			
			if ($this->o->gv['action'] == 'edit')
			{
				if ($this->o->oSess->is('users') && !$this->o->V->is_profile_owner)
				{
					$this->new_label( $this->o->oTkit->_(1021), $this->field('select',  'arp[is_active]', $ar['is_active'], $ar_status) );
				}
				else
				{
					$this->new_label( $this->o->oTkit->_(1021), '<em class="disabled">'.$ar_status[$ar['is_active']].'</em>' );
				}
			}
			
			if ( $this->o->V->is_profile_owner )
			{
				/* */
				$this->new_fieldset( 'group-perm', $this->o->oTkit->_(1147) );
				$ar_access_names = $this->o->oSess->is();
				$ar_perms_map = $this->o->get_perm_map();
				$i = 0;
				$ar['group_perm'] = unserialize($ar['group_perm']);
				foreach ($ar_perms_map as $str_group => $arV)
				{
					$this->new_subfieldset( 'group-perm'.($i), $str_group );
					foreach ($arV as $id_perm => $str_perm)
					{
						$is_active = (isset($ar['group_perm'][$id_perm]) && $ar['group_perm'][$id_perm]);
						#$str_perm = $is_active ? '<strong class="on">'.$str_perm.'</strong>' : $str_perm;
						if ($is_active)
						{
							$this->new_label($str_perm, '' );
						}
					}
					++$i;
				}
			}
		}
		/* */
		if (($this->o->gv['area']['s'] == 'pass' && $this->o->V->is_profile_owner)
			|| ($this->o->gv['action'] == 'add' || $this->o->gv['action'] == 'edit') && !$this->o->V->is_profile_owner)
		{
			$this->new_fieldset( 'pass', $this->o->oTkit->_(1012) );

			/* Password is the same as login */
			if ( isset($ar['password']) && ($ar['password'] == hash('md5', $ar['login'])) )
			{
				$this->o->oOutput->append_js( 'jsF.formStatus("'.$this->o->oTkit->_(1295).'", 0);' );
			}
			
			$this->set_tag( 'input', 'class', 'inp w25' );
			$this->set_tag( 'password', 'class', 'inp w25' );

			/* As module: Shouldn't be changable */
			$this->new_label( $this->o->oTkit->_(1148), '<em class="disabled">'.$ar['login'].'</em>' );
			/* Used on error */
			$this->field( 'hidden', 'arp[login_old]', $ar['login'] );
	
			/* Password */
			/* As module: no password */
			$this->new_label( $this->o->oTkit->_(1149), '<em class="disabled">&#160;</em>' );
			
			/* E-mail */
			/* As module: Shouldn't be changable */
			$this->set_tag( 'input', 'class', 'inp w50' );
			$this->new_label( $this->o->oTkit->_(1151), '<em class="disabled">'.$ar['user_email'].'&#160;</em>' );
			
			/* As module: nothing to edit */
			$this->is_submit_ok = 0;

		}
		/* */
		if ($this->o->gv['area']['s'] == 'privacy' && $this->o->V->is_profile_owner)
		{
			$this->new_fieldset( 'privacy', $this->o->oTkit->_(1009) );
			$this->new_label( $this->o->oTkit->_(1155), $this->field('checkbox',  'arp[is_visible]', $ar['is_visible']), $this->o->oTkit->_(1156) );
		}
		/* */
		if (($this->o->gv['area']['s'] == 'profile' && $this->o->V->is_profile_owner)
			|| ($this->o->gv['action'] == 'add' || $this->o->gv['action'] == 'edit') && !$this->o->V->is_profile_owner)
		{
			$this->new_fieldset( 'profile', $this->o->oTkit->_(1013) );
			if ( $this->o->oSess->is('profile') )
			{
				$this->set_tag( 'input', 'class', 'inp w50' );
				$this->new_label( $this->o->oTkit->_(1062), $this->field( 'input',  'arp[user_nickname]', $ar['user_nickname'] ) );
				
				/* As module: Shouldn't be changable */
				$this->new_label( $this->o->oTkit->_(1060), '<em class="disabled">'.$ar['user_fname'].'&#160;</em>' );

				$this->new_label( $this->o->oTkit->_(1061), $this->field('input',  'arp[user_sname]', $ar['user_sname']) );
				/* Displayed name */
				$ar_displayed_name = array(
					1 => $this->o->oTkit->_(1062) . ' ('.$ar['user_nickname'].')',
					2 => $this->o->oTkit->_(1060) . ' ('.$ar['user_fname'].')',
					3 => $this->o->oTkit->_(1061) . ' ('.$ar['user_sname'].')',
					4 => $this->o->oTkit->_(1060).' '.$this->o->oTkit->_(1061). ' ('.$ar['user_fname'].' '.$ar['user_sname'].')',
				);
				$this->new_label( $this->o->oTkit->_(1063), $this->field( 'select',  'arp[user_settings][displayed_name]', $ar['user_settings']['displayed_name'], $ar_displayed_name ) );
			}
			else
			{
				$this->new_label( $this->o->oTkit->_(1060), '<em class="disabled">'.$ar['user_fname'].'&#160;</em>' );
				$this->new_label( $this->o->oTkit->_(1061), '<em class="disabled">'.$ar['user_sname'].'&#160;</em>' );
			}
			if ($this->o->V->is_profile_owner)
			{
				/* */
				$ar_sorting_projects = array(
					1 => $this->o->oTkit->_(1161),
					2 => $this->o->oTkit->_(1162),
					3 => $this->o->oTkit->_(1163),
					4 => $this->o->oTkit->_(1164)
				);
				#$this->new_label($this->o->oTkit->_(1193), $this->field('select',  'arp[user_settings][sort_projects]', $ar['user_settings']['sort_projects'], $ar_sorting_projects) );
			}
			/* */
			if ( isset($ar['id_user']) )
			{
				$this->field( 'hidden', 'arg[id_user]', $ar['id_user'] );
			}
			$this->field( 'hidden', 'arp[user_settings][gmt_offset]', 0 );
			$this->field( 'hidden', 'arp[user_settings][is_dst]', 0 );
			$this->field( 'hidden', 'arp[user_settings][locale]', 'english' );
		}

		$this->field( 'hidden', 'arg[action]', $this->o->gv['action'] );
		$this->field( 'hidden', 'arg[target]', $this->o->gv['target'] );
		$this->field( 'hidden', 'arg[area]', 's.'.$this->o->gv['area']['s'] );
		$this->field( 'hidden', 'arg[id_user]', $this->o->gv['id_user'] );

		/* Append URL */
		if ( !empty( $this->o->V->sef_append ) )
		{
			/* @todo: parse arrays */
			foreach ( $this->o->V->sef_append as $k1 => $v1 )
			{
				$this->field( 'hidden', $k1,  $v1 );
			}
		}

		$this->field( 'hidden', 'arg[uri]', $this->o->gv['uri'] );
		$this->field( 'hidden', 'arp[form]', '1' );
		
		return $this->form_output();
	}
	/* */
	function on_success($ar)
	{
		$ar = $this->check_onoff( $ar );
		
		
		/* Check for Usergroup ID */
		if (isset($ar['id_group']))
		{
			#if ($ar['id_group'] == 1 || $ar['id_group'] == 4)
			if ($ar['id_group'] == 1)
			{
				$ar['id_group'] = 5;
			}
		}
		/* */
		switch ($this->o->gv['action'])
		{
			case 'edit';
				$id_user = (isset($this->o->gv['id_user']) && $this->o->oSess->is('users')) ? $this->o->gv['id_user'] : $this->o->oSess->id_user;
				$q__users = array();

				/* Redirect */
				$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=t.user,a.edit,s.profile');

				/* */
				
				/* Privacy, available for profile owners only */
				if ($this->o->gv['area']['s'] == 'privacy' && $this->o->V->is_profile_owner)
				{
					$q__users['is_visible'] = $ar['is_visible'];

					/* Redirect to member home */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?r='.$_SERVER['REQUEST_TIME'] );
				}
				
				/* Profile */
				if (($this->o->gv['area']['s'] == 'profile' && $this->o->V->is_profile_owner) || !$this->o->V->is_profile_owner)
				{
					if ( $this->o->oSess->is('profile') )
					{
							/* When at least one of these fields exists */
						if ( isset( $ar['user_sname'] ) )
						{
							$q__users['user_nickname'] = strip_tags($ar['user_nickname']);
							$q__users['user_sname'] = strip_tags($ar['user_sname']);
						}
						/* */
					}
					/* Redirect to member home */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index.'?#area=a.edit,t.user,s.profile,is_saved.1&r='.$_SERVER['REQUEST_TIME'] );
				}
				
				/* */
				if (($this->o->gv['area']['s'] == 'permissions' && $this->o->V->is_profile_owner) || !$this->o->V->is_profile_owner)
				{
 					/* Redirect to member home */
					$href_redirect = $this->o->oHtmlAdm->url_normalize( $this->o->V->file_index .'?r='.$_SERVER['REQUEST_TIME'] );
 				}

				/* Join settings */
				if (isset($ar['user_settings']))
				{
					/* Change interface language */
					if ( isset($ar['user_settings']['il']) )
					{
						/* Add cookie */
						setcookie( 'arg[il]', $ar['user_settings']['il'], $this->o->V->time_req + $this->o->V->time_sec_y , '/');
					}

					$ar_user = $this->o->oSess->user_load_values($id_user);

					/* Apply other settings */
					$ar_user['user_settings'] = array_merge_clobber( $ar_user['user_settings'], $ar['user_settings'] );

					$q__users['user_settings'] = serialize($ar_user['user_settings']);
				}
				/* Update `users` table */
				if ( !empty( $q__users ) )
				{
					$this->o->oDb->update( $this->o->V->db_table_users, $q__users, array( 'id_user' => $id_user ) );
				}
			break;
		}

		/* Switch content modes */
		switch ($this->o->gv['sef_output'])
		{
			case 'ajax':
				print 1;
			break;
			default:
				/* */
				$this->o->redirect( $this->o->V->server_proto.$this->o->V->server_host.$href_redirect, 0 );

				#$this->o->oOutput->append_html( $this->o->soft_redirect(
				#	$this->o->oTkit->_(1031), $href_redirect, CC_COLOR_TRUE
				#));
			break;
		}
	}
}

?>