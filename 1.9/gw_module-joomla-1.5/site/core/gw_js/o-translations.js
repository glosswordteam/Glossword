oTranslations = new function()
{
	/* Assigns a translation variants as default */
	this.d = function( el, id_lang )
	{
		el.className = "";
		el.blur();

		/* `Please wait...` */
		jsF.inner_text( el, jsF.Get("oTkit_1082") );

		/* */
		for ( var i = 0; i < jsF.Get( "ar_id_langs" ).length; i++ )
		{
			fn_getElementById( 'hide-under-' + jsF.Get( "ar_id_langs" )[i] ).className = "hide-under";
		}

		fn_getElementById( 'hide-under-' + id_lang ).className = "state-allow";

		var params = [
			"arg[sef_output]=ajax", "arg[action]=edit", "arg[target]=langs", "arg[sid]="+jsF.Get("id_sess"),
			"arg[is_default]=1",
			"arg[id_lang]=" + id_lang, "arg[r]="+Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join("&");

		var oncompletefn = function(r)
		{
			if ( !r.responseText || r.responseText.length == 0 ) { return; }
			var oJSon = eval( "(" + r.responseText + ")" );
			switch ( parseInt( oJSon.responseStatus ) )
			{
				case 403: /* No such permissions */ alert( "No such permissions . Error code: " + oJSon.responseStatus ); break;
				case 200:
 					/* `Settings saved.` */
					jsF.inner_text( el, jsF.Get("oTkit_1041") );
					/* `Default` */
					setTimeout( function() {
						jsF.inner_text( el, jsF.Get("oTkit_1177") );
						el.className = "btn add";
					}, 2000 )
				break;
				case 404: /* No such item */ alert( "No such item. Error code: " + oJSon.responseStatus ); break;
				default: /* Unknown error */ alert( oJSon.responseStatus ); break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	}
};