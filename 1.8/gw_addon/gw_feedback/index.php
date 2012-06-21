<?php

if ( !defined( 'IN_GW' ) )
{
	die( '<!-- $Id$ -->' );
}
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2010 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
include($sys['path_addon'] . '/class.gw_addon.php');
/* */

class gw_addon_feedback extends gw_addon
{

	var $str_before;
	var $str_after;
	var $int_max_length = 4096;
	var $str;
	var $html_title;
	/* Autoexec */


	function gw_addon_feedback ()
	{
		$this->init();
	}

	/* */


	function get_form_feedback ( $vars, $runtime = 0, $ar_broken = array ( ), $ar_req = array ( ) )
	{

		$str_hidden = '';
		$str_form = '';
		$v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->isButtonCancel = 1;
		$oForm->Set( 'action', $this->sys['page_index'] );
		$oForm->Set( 'submitdel', $this->oL->m( '3_remove' ) );
		$oForm->Set( 'submitok', $this->oL->m( '1036' ) );
		$oForm->Set( 'submitcancel', $this->oL->m( '3_cancel' ) );
		$oForm->Set( 'formbgcolor', $this->ar_theme['color_2'] );
		$oForm->Set( 'formbordercolor', $this->ar_theme['color_4'] );
		$oForm->Set( 'formbordercolorL', $this->ar_theme['color_1'] );
		$oForm->Set( 'align_buttons', $this->sys['css_align_right'] );
		$oForm->Set( 'formwidth', 500 );
		$oForm->Set( 'charset', $this->sys['internal_encoding'] );
		$oForm->Set( 'arLtr', array ( 'email' ) );

		$ar_req = array_flip( $ar_req );
		/* mark fields as "Required" and display error message */
		while ( is_array( $vars ) && list($k, $v) = each( $vars ) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if ( isset( $ar_req[$k] ) )
			{
				$ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>';
			}
			if ( isset( $ar_broken[$k] ) )
			{
				$ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m( 'reason_9' ) . '</b></span><br />';
			}
		}
		/* */
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody><tr><td style="width:' . $v_td1_width . '"></td><td>';
		$str_form .= '</td></tr>';

		$oForm->setTag( 'select', 'class', 'input50' );
		$oForm->setTag( 'input', 'class', 'input' );

		switch ( $this->gw_this['vars']['uid'] )
		{
			case 'newterm':

				$this->html_title = $this->oL->m( '1095' );

				$str_hidden .= $oForm->field( 'hidden', 'd', $this->gw_this['vars'][GW_ID_DICT] );
				$str_hidden .= $oForm->field( 'hidden', 'arPost[subject]', $this->subject_add_a_term );
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_name' ) . ':' . $ar_req_msg['name'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['name'] . $oForm->field( 'input', 'name', textcodetoform( $vars['name'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_email' ) . ':' . $ar_req_msg['email'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['email'] . $oForm->field( 'input', 'email', textcodetoform( $vars['email'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'dict' ) . ':</td>' .
						'<td class="disabled">' . $this->oHtml->a( $this->sys['page_index'] . '?a=list&d=' . $this->arDictParam['uri'], $this->arDictParam['title'] ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'term' ) . ':' . $ar_req_msg['term'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['term'] . $oForm->field( 'textarea', 'arPost[term]', textcodetoform( $vars['term'] ), 2 ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'defn' ) . ':' . $ar_req_msg['defn'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['defn'] . $oForm->field( 'textarea', 'arPost[defn]', textcodetoform( $vars['defn'] ), 10 ) . '</td>' .
						'</tr>';
				break;
			case 'report':

				$this->html_title = $this->oL->m( 'bug_report' );

				$arTerm = getTermParam( $this->gw_this['vars']['t'] );
				$str_hidden .= $oForm->field( 'hidden', 'arPost[fb_type]', 'report' );
				$str_hidden .= $oForm->field( 'hidden', GW_ID_DICT, $this->gw_this['vars'][GW_ID_DICT] );
				$str_hidden .= $oForm->field( 'hidden', 't', $this->gw_this['vars']['t'] );
				$str_hidden .= $oForm->field( 'hidden', 'arPost[subject]', $this->subject_report );
				$str_hidden .= $oForm->field( 'hidden', 'arPost[term]', htmlspecialchars_ltgt( $arTerm['term'] ) );
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_name' ) . ':' . $ar_req_msg['name'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['name'] . $oForm->field( 'input', 'name', textcodetoform( $vars['name'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_email' ) . ':' . $ar_req_msg['email'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['email'] . $oForm->field( 'input', 'email', textcodetoform( $vars['email'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'dict' ) . ':' . '</td>' .
						'<td class="disabled">' . $this->oHtml->a( $this->sys['page_index'] . '?a=list&d=' . $this->arDictParam['uri'], $this->arDictParam['title'] ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'term' ) . ':' . $ar_req_msg['term'] . '</td>' .
						'<td class="disabled">' . $this->oHtml->a( $this->sys['page_index'] . '?a=term&d=' . $this->arDictParam['uri'] . '&t=' . $arTerm['uri'], $arTerm['term'] ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'defn' ) . ':' . $ar_req_msg['defn'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['defn'] . $oForm->field( 'textarea', 'arPost[defn]', textcodetoform( $vars['defn'] ), 10 ) . '</td>' .
						'</tr>';
				break;
			case "sendpage":

				$this->html_title = $this->oL->m( '1275' );

				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'y_name' ) . ':' . $ar_req_msg['name'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['name'] . $oForm->field( 'input', 'name', textcodetoform( $vars['name'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'y_email' ) . ':' . $ar_req_msg['email'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['email'] . $oForm->field( 'input', 'email', textcodetoform( $vars['email'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( '1278' ) . ':' . $ar_req_msg['email1'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['email1'] . $oForm->field( 'input', 'arPost[email1]', textcodetoform( $vars['email1'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( '1080' ) . ':' . $ar_req_msg['subject'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['subject'] . $oForm->field( 'input', 'arPost[subject]', textcodetoform( $vars['subject'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'message' ) . ':' . $ar_req_msg['message'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['message'] . $oForm->field( 'textarea', 'arPost[message]', textcodetoform( $vars['message'] ), 10 ) . '</td>' .
						'</tr>';
				break;
			default:
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_name' ) . ':' . $ar_req_msg['name'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['name'] . $oForm->field( 'input', 'name', textcodetoform( $vars['name'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'contact_email' ) . ':' . $ar_req_msg['email'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['email'] . $oForm->field( 'input', 'email', textcodetoform( $vars['email'] ) ) . '</td>' .
						'</tr>';
				$str_form .= '<tr>' .
						'<td class="td1">' . $this->oL->m( 'message' ) . ':' . $ar_req_msg['message'] . '</td>' .
						'<td class="td2">' . $ar_broken_msg['message'] . $oForm->field( 'textarea', 'arPost[message]', textcodetoform( $vars['message'] ), 10 ) . '</td>' .
						'</tr>';
				break;
		}
		$oForm->setTag( 'input', 'class', 'input50' );
		$oForm->setTag( 'input', 'style', 'font-size:200%' );
		/* CAPTCHA, 20 june 2007 */
		if ( function_exists( 'imagecreatefrompng' ) )
		{
			$this->oHtml->setTag( 'a', 'onclick', 'gw_getElementById(\'captcha\').src=\'' . $this->sys['server_dir'] . '/' . $this->sys['path_addon'] . '/gw_feedback/make_img.php?' . '\'+Math.random();return false' );
			$oForm->setTag( 'input', 'maxlength', '6' );
			$url_page_refresh = $this->oHtml->a( '#', $this->oL->m( '1279' ) );
			$str_form .= '<tr>' .
					'<td class="td1">' . $this->oL->m( '1269' ) . ':' . $ar_req_msg['captcha'] . '</td>' .
					'<td class="td2">' .
					'<span style="float:right;margin:2em 2em 0 0">' . $url_page_refresh . '</span>' .
					'<img id="captcha" src="' . $this->sys['server_dir'] . '/' . $this->sys['path_addon'] . '/gw_feedback/make_img.php?' . time() . '" width="175" height="60" alt="CAPTCHA" />' .
					'<br />' . $ar_broken_msg['captcha'] . $oForm->field( 'input', 'arPost[captcha]', textcodetoform( $vars['captcha'] ) ) .
					'<br />' . $this->oL->m( '1280' ) . '</td>' .
					'</tr>';
			$oForm->setTag( 'input', 'maxlength', '' );
		}
		$str_form .= '</tbody></table>';
		$str_form .= $oForm->field( 'hidden', GW_ACTION, GW_A_CUSTOMPAGE );
		$str_form .= $oForm->field( 'hidden', 'id', $this->gw_this['vars']['id'] );
		$str_form .= $oForm->field( 'hidden', 'uid', $this->gw_this['vars']['uid'] );
		/* Append to URL */
		foreach ( $this->sys['ar_url_append'] as $k => $v )
		{
			$str_form .= $oForm->field( 'hidden', $k, $v );
		}
		$str_form .= $str_hidden;
		if ( isset( $this->oSess->sid ) )
		{
			$str_form .= $oForm->field( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		}
		return $oForm->Output( $str_form );
	}

	/* */


	function alpha ()
	{
		global $str;

		$fields = array ( 'message', 'term', 'defn', 'title', 'url', 'subject', 'email1' );
		foreach ( $fields as $f )
		{
			$vars[$f] = (isset( $this->gw_this['vars']['arPost'][$f] )
					&& (trim( $this->gw_this['vars']['arPost'][$f] ) != '' )) ? $this->gw_this['vars']['arPost'][$f] : '';
		}
		/* */
		switch ( $this->gw_this['vars']['uid'] )
		{
			case 'newterm':
				$ar_req_fields = array ( 'term', 'defn', 'captcha' );
				if ( isset( $this->gw_this['vars']['q'] ) && strlen( $this->gw_this['vars']['q'] ) ) {
					$vars['term'] = strip_tags( $this->gw_this['vars']['q'] );
				}
				break;
			case 'report':
				$ar_req_fields = array ( 'defn', 'captcha' );
				break;
			case 'sendpage':
				$ar_req_fields = array ( 'name', 'email', 'email1', 'subject', 'message', 'captcha' );
				$vars['subject'] = ($vars['subject']) ? $vars['subject'] : $this->oL->m( '1277' );
				$vars['message'] = ($vars['message']) ? $vars['message'] : sprintf( $this->oL->m( '1276' ), CRLF . $vars['title'], CRLF . str_replace( '&amp;', '&', $vars['url'] ) );
				break;
			default:
				$ar_req_fields = array ( 'message', 'captcha' );
				break;
		}
		/* */
		$vars['name'] = strip_tags( $this->gw_this['vars']['name'] );
		$vars['email'] = strip_tags( $this->gw_this['vars']['email'] );
		$vars['captcha'] = isset( $this->gw_this['vars']['arPost']['captcha'] ) ? $this->gw_this['vars']['arPost']['captcha'] : '';
		/* */
		if ( $this->gw_this['vars']['post'] == '' )
		{
			switch ( $this->gw_this['vars']['uid'] )
			{
				case 'sendpage':
					$this->str_before = '<p> ' . $this->oL->m( 'fb_fill' ) . '</p><p> ' . $this->oL->m( '1285' ) . '</p>';
					break;
			}
			/* Not submitted */
			$vars['captcha'] = '';
			$this->str .= $this->str_before;
			$this->str .= $this->get_form_feedback( $vars, 0, 0, $ar_req_fields );
		}
		else
		{
			/* Checking posted vars */
			$is_post_error = 1;
			$errorStr = '';
			$ar_broken = validatePostWalk( $vars, $ar_req_fields );
			if ( empty( $ar_broken ) )
			{
				$is_post_error = 0;
			}
			else
			{
				$vars['captcha'] = '';
				$this->str .= $this->str_before;
				$this->str .= $this->get_form_feedback( $vars, 1, $ar_broken, $ar_req_fields );
				return;
			}
			$is_post_error = 1;
			/* Check captcha, 20 june 2007 */
			$vars['captcha'] = strtoupper( preg_replace( "/[^a-zA-Z0-9]/", '', $vars['captcha'] ) );
			$arSql = $this->oDb->sqlExec( 'SELECT id FROM `' . $this->sys['tbl_prefix'] . 'captcha` WHERE `captcha` = "' . $vars['captcha'] . '"' );
			$id_captcha = 0;
			for (; list($arK, $arV) = each( $arSql ); )
			{
				$id_captcha = $arV['id'];
			}
			if ( $id_captcha )
			{
				$is_post_error = 0;
				$this->oDb->sqlExec( 'DELETE FROM `' . $this->sys['tbl_prefix'] . 'captcha` WHERE id = ' . $id_captcha );
			}
			else
			{
				$ar_broken['captcha'] = '';
				$this->str .= $this->str_before;
				$this->str .= $this->get_form_feedback( $vars, 1, $ar_broken, $ar_req_fields );
			}
			/* */
			if ( !$is_post_error )
			{
#				$this->oL->getCustom('mail', $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
				/* Feedback messages should be in the same language as the system */
				$this->oL->getCustom( 'mail', $this->sys['locale_name'], 'join' );

				$mail_body = '';
				$vars['message'] = (htmlspecialchars_ltgt( $vars['message'] ));
				/* Parse links */
				$vars['message'] = preg_replace( "/(^|\[|\s)((http|https|news|ftp|aim|callto):\/\/\w+[^\s\[\\]]+)/ie", "gw_regex_url(array('html' => '\\2', 'show' => '\\2', 'st' => '\\1'))", $vars['message'] );
				$vars['message'] = nl2br( $vars['message'] );
				$vars['name'] = ($vars['name']) ? $vars['name'] : 'Anonymous';
				$vars['email'] = ($vars['email']) ? $vars['email'] : 'anonymous@' . $this->sys['server_host'];
				/* Limit the string length */
				for ( reset( $vars ); list($k, $v) = each( $vars ); )
				{
					$vars[$k] = $this->oFunc->mb_substr( $v, 0, $this->int_max_length );
				}
				/* Checking subject */
				$vars['subject'] = htmlspecialchars_ltgt( strip_tags( $vars['subject'] ) );
				/* */
				$mail_body .= $vars['message'];
				/* Composite message */
				if ( $vars['defn'] )
				{
					$mail_body .= '<br />' . $this->oL->m( 'dict' ) . ': ' . $this->oHtml->a( $this->sys['page_index'] . '?a=list&d=' . $this->gw_this['vars'][GW_ID_DICT], $this->arDictParam['title'] );
					$vars['defn'] = nl2br( htmlspecialchars_ltgt( $vars['defn'] ) );
				}
				if ( $vars['term'] )
				{
					$arTerm = getTermParam( $this->gw_this['vars']['t'] );
					$mail_body .= '<br />' . $this->oL->m( 'term' ) . ': ' . $this->oHtml->a( $this->sys['page_index'] . '?a=term&d=' . $this->gw_this['vars'][GW_ID_DICT] . '&t=' . $this->gw_this['vars']['t'], $arTerm['term'] );
					$vars['term'] = nl2br( htmlspecialchars_ltgt( $vars['term'] ) );
					if ( !$this->gw_this['vars']['t'] )
					{
						$mail_body .= '<br /><br />' . $vars['term'] . '<br />';
					}
				}
				if ( $vars['defn'] )
				{
					$mail_body .= '<br />' . $this->oL->m( 'defn' ) . ': <br />';
				}
				$mail_body .= '<br />' . $vars['defn'];
				$mail_body .= '<br />';

				$mail_subject = $vars['subject'] ? $vars['subject'] : htmlspecialchars_ltgt( $this->sys['site_name'] ) . ' ' . $this->oL->m( 'web_m_fb' );
				$mail_to = $vars['email1'] ? htmlspecialchars_ltgt( $vars['email1'] ) : $this->sys['y_email'];
				$mail_body .= '<br /><div>' . $vars['name'] . '<br/> ' . $vars['email'];
				$mail_body .= '</div><br /><div style="font-size:70%">' . REMOTE_IP;
				$mail_body .= '<br />' . REMOTE_UA . '</div>';

				/* Start new messenger */
				$oMail = new tkit_mail( 'mail_feedback' );
				$this->sys['is_debug_mail'] = 0;

				/**
				 * Send mail: from_name, to_name
				 */
				$oMail->send(
						htmlspecialchars_ltgt( $vars['name'] ),
						htmlspecialchars_ltgt( $vars['email'] ),
						$this->sys['site_name'],
						$this->sys['site_email'],
						$this->sys['mail_subject_prefix'] . ' ' . $mail_subject,
						$oMail->create_message( $mail_subject, $mail_body ),
						$this->sys['is_debug_mail']
				);

				$ar_query = array ( );
				if ( $vars['defn'] && $vars['term'] )
				{
					$q1['id'] = $this->oDb->MaxId( $this->arDictParam['tablename'], 'id' );
					$q1['is_active'] = '0';
					$q1['is_complete'] = '0';
					$q1['date_created'] = $this->sys['time_now_gmt_unix'];
					$q1['date_modified'] = $this->sys['time_now_gmt_unix'];
					$q1['term'] = $q1['term_uri'] = $vars['term'];
					$q1['term_order'] = $this->oCase->uc( $vars['term'] );
					$q1['defn'] = '<defn><![CDATA[' . $vars['defn'] . ']]></defn>';
					$ar_query[] = gw_sql_replace( $q1, $this->arDictParam['tablename'] );
					$q2['user_id'] = 1;
					$q2['term_id'] = $q1['id'];
					$q2['dict_id'] = $this->arDictParam['id'];
					$ar_query[] = gw_sql_replace( $q2, TBL_MAP_USER_TERM );
				}
				/* Post queries */
				for ( reset( $ar_query ); list($qk, $qv) = each( $ar_query ); )
				{
					$this->oDb->sqlExec( $qv );
				}
				$this->str .= $this->str_after;
			}
		}
	}

	/* */


	function make_html_title ( $old_html_title = '' )
	{
		return ($this->html_title ? $this->html_title : $old_html_title);
	}

	/* */


	function omega ()
	{
		$this->oTpl->addVal( 'block:feedback', $this->str );
#		$this->oTpl->addVal( 'block:feedback', htmlspecialchars($this->str).$this->str);
	}

}

$oFeedback = new gw_addon_feedback;

/* Text before posting */
$oFeedback->str_before = '
<p> ' . $oL->m( 'fb_fill' ) . '</p>
';

/* Text after posting */
$oFeedback->str_after = '
<p>' . $oL->m( 'fb_complete' ) . '</p>
';

$oFeedback->subject_add_a_term = $oL->m( '1095' );
$oFeedback->subject_report = $oL->m( 'bug_report' );
/* */
$oFeedback->alpha();
$oFeedback->omega();

/* Change title for HTML-page */
$arV['page_title'] = $oFeedback->make_html_title( $arV['page_title'] );

/* end of file */
?>