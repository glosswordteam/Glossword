<?php

$tmp['ar_queries'] = array(
			'get-user' => 'SELECT u.*, a.*
							FROM `'.$sys['tbl_prefix'].'users` as u,
							`'.$sys['tbl_prefix'].'auth` as a
							WHERE u.id_user = "%d"
							AND u.id_user = a.id_auth
						',
			'get-user-by-actkey' => 'SELECT a.id_auth, a.login, u.user_email, u.user_name
							FROM `'.$sys['tbl_prefix'].'users` as u,
							`'.$sys['tbl_prefix'].'auth` as a
							WHERE a.id_auth = u.id_user
							AND u.user_actkey = "%s"
							AND u.is_active = "0"
						',
			'get-session' => 'SELECT s.*
							FROM `'.$sys['tbl_prefix'].'sessions` as s
							WHERE s.id_sess = "%s"
						',
			'sess-purge' => 'DELETE FROM `'.$sys['tbl_prefix'].'sessions`
							WHERE changed < %s
						',
			'sess-purge-empty' => 'DELETE FROM `'.$sys['tbl_prefix'].'sessions`
							WHERE changed < %s
							AND sess_settings = "a:0:{}"
						',
			'cmp-login-pass' => 'SELECT a.id_auth, a.perm_bits, a.login, u.is_active, u.user_name
								FROM `'.$sys['tbl_prefix'].'auth` as a, `'.$sys['tbl_prefix'].'users` as u
							WHERE STRCMP(a.login, \'%s\') = 0
							AND STRCMP(a.password, \'%s\') = 0
							AND u.id_user = a.id_auth
							LIMIT 1
						',
			'cmp-login-email' => 'SELECT a.id_auth, a.login, u.is_active
							FROM `'.$sys['tbl_prefix'].'auth` as a, `'.$sys['tbl_prefix'].'users` as u
							WHERE STRCMP(a.login, \'%s\') = 0
							AND STRCMP(u.user_email, \'%s\') = 0
							AND u.id_user = a.id_auth
							LIMIT 1
						',
			'set-password' => 'UPDATE `'.$sys['tbl_prefix'].'auth`
							SET password = "%s"
							WHERE id_auth = "%d"
						',
			'set-user-active' => 'UPDATE `'.$sys['tbl_prefix'].'users`
							SET is_active = "%d", user_actkey = "%s"
							WHERE id_user = "%d"
						',


);

?>