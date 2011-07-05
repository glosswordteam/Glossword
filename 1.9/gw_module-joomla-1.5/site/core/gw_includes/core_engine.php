<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	 Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */

/**
 * So-called "Engine".
 *
 * Methods:
 * - page_header()
 * - page_body()
 * - page_footer()
 */
if (!defined('IS_CLASS_GW_ENGINE')) { define('IS_CLASS_GW_ENGINE', 1);
class site_engine extends site_prepend {

	private $cur_function, $cur_htmlform;
	/**
	 *
	 */
	public function page_header()
	{
		
		/* Start collecting CSS-files with a common file */
		$this->oOutput->append_css_collection( 'common' );
		
		/* Backend/frontend settings */
		if ( defined('SITE_THIS_SCRIPT') && SITE_THIS_SCRIPT == $this->V->file_admin )
		{
			/* Visual links, controls, etc. */
			define( 'SITE_ADMIN_MODE', 1 );
			define( 'SITE_WEB_MODE', 0 );
			
			$this->oOutput->append_css_collection( 'admin' );
			$this->oOutput->append_js_collection( 'admin' );
			$this->oOutput->append_js_collection( 'jsmenu' );
			$ar_tkit_profiles = array( 'global' );
			$this->a( 'is_cache_http', 0 );
			$this->a( 'is_use_xhtml', 0 );
			$this->a( 'is_use_html_smooth', 0 );
		}
		else
		{
			/* Visual links, controls, etc. */
			define( 'SITE_ADMIN_MODE', 0 );
			define( 'SITE_WEB_MODE', 1 );
			
			$this->oOutput->append_css_collection( 'web' );
			$ar_tkit_profiles = array( 'global' );
			#$this->a( 'is_cache_http', 1 );
			$this->a( 'is_use_xhtml', 0 );
			$this->a( 'is_use_html_smooth', 0 );
		}

		/* Switch content modes */
		switch ($this->gv['sef_output'])
		{
			case 'ajax':
				$this->a( 'is_cache_http', 0 );
				$this->a( 'is_use_gzip', 0 );
				$this->a( 'is_use_xhtml', 0 );
				$this->a( 'is_use_html_smooth', 0 );

				/* HTML-forms and HTML-tags */
				$this->oForm = $this->_init_forms();
			break;
			case 'css':
			case 'js':
			break;
			default:
				/* The list of installed languages */
				$this->ar_languages = $this->langs__get_installed();
				$this->ar_languages_locale = $this->langs__get_locale_codes();
			
				/* Start preparing HTML-title */
				$this->oOutput->append_html_title( $this->V->meta_title );
				$this->oOutput->append_bc( 
					$this->V->meta_title,
					$this->oHtml->url_normalize( $this->V->file_index.'?#sef_output='.$this->gv['sef_output'] )
				);
			
				/* HTML-forms and HTML-tags */
				$this->oForm = $this->_init_forms();

				/* Default page, opens on error */
				$this->a( 'id_tpl_page', GW_TPL_WEB_INDEX );

				/* Built-in "cron" tasks */
				$this->cron__();

			break;
		}
	}
	/**
	 *
	 */
	public function page_body()
	{
		/* Construct function name */
		$this->cur_function = $this->gv['target'].'/'.$this->gv['target'].'__'.$this->gv['action'];

		/* Construct file name with function  */
		$this->file_to_function = $this->V->path_views.'/'.$this->cur_function.'.php';

		/* Construct file name with HTML-form */
		$this->cur_htmlform = $this->V->path_views.'/'.$this->gv['target'].'/'.$this->gv['target'].'__htmlform.php';
		
		/* Called after submit */
		$this->cur_htmlform_onsubmit = $this->V->path_views.'/'.$this->gv['target'].'/'.$this->gv['target'].'__'.$this->gv['action'].'__onsubmit.php';

		/* */
		$this->oTarget = $this->load_module( $this->gv['target'] );
		
		/* Switch content modes */
		switch ($this->gv['sef_output'])
		{
			case 'ajax':
			case 'css':
			case 'js':

			break;
			default:
				$this->oTpl->assign( 'v:navbar', $this->create_navbar() );
			break;
		}
		if ( file_exists( $this->file_to_function ) )
		{
			include( $this->file_to_function );
		}
	}
	/**
	 *
	 */
	public function page_footer()
	{
		/* Common variables for Output */
		$this->oOutput->set( 'v:path_css', $this->V->path_css );
		$this->oOutput->set( 'v:path_images', $this->V->path_images );

		/* Complete output */
		$s = '';
		/* Switch content modes */
		switch ($this->gv['sef_output'])
		{
			case 'ajax':


			break;
			case 'css':
				$this->oHdr->add('Content-Type: text/css; charset=utf-8');
				if (isset($this->gv['files']) && is_array($this->gv['files']))
				{
					foreach( $this->gv['files'] as $v )
					{
						$this->oOutput->append_css_file( $this->V->path_css_abs.'/'.$v );
					}
				}
				$s = $this->oOutput->get_css();
			break;
			case 'js':
				$this->oHdr->add('Content-Type: text/javascript; charset=utf-8');
				if (isset($this->gv['files']) && is_array($this->gv['files']))
				{
					foreach( $this->gv['files'] as $v )
					{
						$this->oOutput->append_js_file( $this->V->path_js.'/'.$v );
					}
				}
				$s = $this->oOutput->get_js();
			break;
			default:
				$this->oHdr->add('Content-Type: '.$this->V->content_type.'; charset=utf-8');

				$this->oOutput->append_js_collection( 'jsutils' );

				#$this->oOutput->append_js_collection( 'fat' );
				#$this->oOutput->append_js( 'Fat.fade_all(1000);' );

				$this->oOutput->append_js( 'jsF.Set("path_server_dir", "'.$this->V->server_url.'");' );
				$this->oOutput->append_js( 'jsF.Set("path_server_dir_admin", "'.$this->V->server_url_admin.'");' );
				
				$this->oOutput->append_js( 'jsF.Set("file_index", "'.$this->V->file_index.'");' );
				$this->oOutput->append_js( 'jsF.Set("path_css", "'.$this->V->path_css.'");' );
				$this->oOutput->append_js( 'jsF.Set("id_sess", "'.$this->oSess->id_sess.'");' );
				$this->oOutput->append_js( 'jsF.Set("sef_append", "'.http_build_query( $this->V->sef_append ).'");' );
				$this->oOutput->append_js( 'jsF.Set("sef_append_ajax", "'.http_build_query( $this->V->sef_append_ajax ).'");' );

				#$this->oOutput->append_js( 'uncollapse_all(true);' );

				$this->oTpl->assign( 'v:javascripts', '<script type="text/javascript">/*<![CDATA[*/'.$this->oOutput->get_js().'/*]]>*/</script>' );
				$this->oTpl->assign( 'v:js_files', $this->oOutput->get_js_collection() );
				$this->oTpl->assign( 'href:output_js', $this->oHtml->url_normalize( $this->V->server_dir.'/'.$this->V->file_index.'?arg[target]=js&arg[sef_output]=js&arg[files]='.$this->oOutput->get_js_collection() ));
				$this->oTpl->assign( 'href:output_css', $this->oHtml->url_normalize( $this->V->server_dir.'/'.$this->V->file_index.'?arg[target]=css&arg[sef_output]=css&arg[files]='.$this->oOutput->get_css_collection() ) );

				/* Assign variables */
				$this->oTpl->assign_global( 'v:path_css', $this->V->path_css );
				$this->oTpl->assign_global( 'v:path_js', $this->V->path_js );
				
				$this->oTpl->assign_global( 'v:language', $this->oTkit->ar_ls['isocode3'] );
				$this->oTpl->assign_global( 'v:xml_lang', $this->oTkit->ar_ls['isocode1'] );
				
				$this->oTpl->assign_global( 'v:random', $_SERVER['REQUEST_TIME'] );
				$this->oTpl->assign_global( 'v:server_dir', $this->V->server_dir );
				$this->oTpl->assign_global( 'v:contents', $this->oOutput->get_html() );
				$this->oTpl->assign_global( 'v:form_action', $this->V->server_dir.'/'.$this->V->file_index  );
				$this->oTpl->assign_global( 'v:content_type', $this->V->content_type );
				$this->oTpl->assign_global( 'v:charset', 'utf-8' );
				$this->oTpl->assign_global( 'v:html_title', $this->oOutput->get_html_title() );
				$this->oTpl->assign_global( 'v:meta_description', $this->V->meta_descr );
				$this->oTpl->assign_global( 'v:meta_keywords', $this->V->meta_keywords );
				$this->oTpl->assign_global( 'v:version', $this->V->version );
				$this->oTpl->assign_global( 'v:search_form', $this->get_search_form() );

				/* Tkit: Moves phrases to HTML. Must be after $oTpl. */
				$this->import_tkit_phrases();

				/* Close Session class. Set cookie. */
				$this->oSess->sess_close();

				/* Show debug information */
				$this->a( 'time_php', $this->V->oTimer->end() );
				$str_debug_time = $str_debug_sql = $str_debug_cache = $str_debug_file = '';
				if ($this->V->is_debug_cache && isset($this->oCache))
				{
					$str_debug_cache .= '<ol title="cache"><li>' . implode('</li><li>', $this->oCache->events()) . '</li></ol>';
				}
				if ($this->V->is_debug_db && isset($this->oDb))
				{
					$str_debug_sql .= '<ol title="database"><li>' . str_replace('{', '&#123;', implode('</li><li>', $this->oDb->get_queries()) ). '</li></ol>';
				}
				if ( $this->V->is_debug_time )
				{
					$int_debug_memory = 0;
					if (function_exists('memory_get_usage'))
					{
						$this->a( 'debug_memory_e', memory_get_usage() );
						$int_debug_memory = $this->oTkit->number_format( $this->V->debug_memory_e - $this->V->debug_memory_s );
					}
					$str_debug_func = $this->file_to_function;
					$str_debug_time = sprintf('<div>DB: <strong>%1.3f</strong> Queries: <strong>%s</strong> &bull; Memory: %s &bull; PHP prepend: %1.3f | PHP core %1.3f | PHP total <strong>%1.3f</strong> &bull; %s </div>',
									$this->oDb->get_query_time(), $this->oTkit->number_format( $this->oDb->get_query_count() ), $int_debug_memory, $this->V->time_php_prepend, $this->V->time_php, $this->V->time_php_prepend + $this->V->time_php, $str_debug_func
								);
				}
				/* Debug Tkit */
				$str_debug_tkit = '';
				if ( $this->V->is_debug_tkit )
				{
					foreach( $this->oTkit->ar_debug as $ar_v )
					{
						$ar_debug_tkit[] = '…'. substr( $ar_v['file'], -64 ) . ' → LINE <strong>'.$ar_v['line']. '</strong> ('. htmlspecialchars($ar_v['args']).' → '. htmlspecialchars( $this->oFunc->mb_substr( $ar_v['value'], 0, 200 ) ).')';
					}
					$str_debug_tkit = '<ol title="tkit">';
					if ( $this->V->is_debug_tkit_trace )
					{
						$str_debug_tkit .= '<li>'. implode( '</li><li>', $ar_debug_tkit ) .'</li>';
					}
					$str_debug_tkit .= '<li>Phrase IDs <strong>'. sizeof( $this->oTkit->get_phrases_called() ). '</strong>: '. implode( ', ', array_keys( $this->oTkit->get_phrases_called() ) ) .'</li></ol>';
				}
				
				/* */
				if ($str_debug_time || $str_debug_sql || $str_debug_cache || $str_debug_file)
				{
				#	print '<div class="debug">'.$str_debug_time.$str_debug_sql.$str_debug_cache.$str_debug_file.'</div>';
					$this->oTpl->assign( 'v:debug', '<div class="gw-debug">'.$str_debug_time.$str_debug_sql.$str_debug_cache.$str_debug_file.$str_debug_tkit.'</div>' );
				}

				/* Set HTML-templates */
				$this->oTpl->set_tpl( $this->V->id_tpl_page );

				/* Parse dynamic blocks */
				#$this->tpl_iterate( $this->oTpl->tmp['d'] );

				$s = $this->oTpl->get_html();

				/* Optimize HTML */
				if ($this->V->is_use_html_smooth)
				{
					$s = text_smooth($s);
				}

			break;
		}

		/* Send HTTP-headers */
		if ( $this->V->is_cache_http )
		{
			$this->oHdr->add( 'Expires: ' . @date("r", $this->V->time_req + $this->V->time_cache_http) );
			$this->oHdr->add( 'Last-Modified: ' . @date("r", $this->V->time_req) );
			$this->oHdr->add( 'Cache-Control: max-age='.$this->V->time_cache_http.', must-revalidate' );
		}
		else
		{
			$this->oHdr->add( 'Expires: ' . @date("r", $this->V->time_req) );
			$this->oHdr->add( 'Last-Modified: ' . @date("r", $this->V->time_req) );
			$this->oHdr->add( 'Cache-Control: no-store, no-cache, must-revalidate' );
		}

		/* Common functions for css, js, html */
		if ( $this->V->is_use_gzip && ($this->gv['sef_output'] != 'ajax') )
		{
			$s = $this->oOutput->gzip( $s );
			if ( $this->oOutput->gzip_enc )
			{
				$this->oHdr->add( 'Content-Encoding: ' . $this->oOutput->gzip_enc );
			}
		}
		$this->oHdr->add( 'Content-Length: ' . strlen($s) );

		if ( $this->V->is_send_headers )
		{
			$this->oHdr->output();
		}

		print $s;
		unset($s);
	}
}
}
?>