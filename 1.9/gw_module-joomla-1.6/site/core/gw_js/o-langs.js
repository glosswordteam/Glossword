oLangs = new function()
{
	/* HTML-form, Import */
	this.blocks = ["subfieldset-source-localfile", "subfieldset-source-direct", "subfieldset-source-remotefile"];
	this.form_init = function()
	{
		for (var i = 0; i < this.blocks.length; i++)
		{
			fn_getElementById( this.blocks[i] ).style.display = "none";
		}
	};
	this.form_select = function(id)
	{
		this.form_init();
		fn_getElementById(id).style.display = "block";
	};

	/* */
	this.onoff = function( a, id_lang, new_status )
	{
		var ar_sts_classnames = ["state-warning", "state-allow"];
		var ar_sts_phrase = [jsF.Get("oTkit_1070"), jsF.Get("oTkit_1069")];

		var params = [
			"arg[sef_output]=ajax", "arg[action]=edit", "arg[target]=langs", "arg[sid]="+jsF.Get("id_sess"), 
			"arg[sts]="+new_status,
			"arg[id_lang]="+id_lang, "arg[r]="+Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
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
					a.childNodes[0].replaceChild( document.createTextNode( ar_sts_phrase[new_status] ), a.childNodes[0].firstChild );
					a.childNodes[0].className = ar_sts_classnames[new_status];
					a.setAttribute("onclick", "oLangs.onoff(this," + id_lang+ ", "+ (new_status?0:1) +" )");
				break;
				case 404: /* No such item */ alert( "No such item. Error code: " + response ); break;
				default: /* Unknown error */ alert( response ); break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	};

	/* Export */
	this.checkall = function( is_active, el_name )
	{
		var rows = fn_getElementById( el_name ).getElementsByTagName( "input" );
		if ( !rows ) { return; }
		for ( var i = 0; i < rows.length; i++ )
		{
			if ( rows[i].type == 'checkbox' )
			{
				rows[i].checked = is_active;
			}
		}
	};
	
	/* */
	this.remove_confirm = function( id_lang )
	{
		if ( confirm( jsF.Get( "oTkit_1043" ) + ':\u0020' + jsF.Get( "oTkit_1051" ) ))
		{
			this.remove( id_lang );
		}
	};
	/* AJAX to remove Language */
	this.remove = function(id_lang)
	{
		var params = [
			"arg[sef_output]=ajax", "arg[action]=remove", "arg[target]=langs", "arg[sid]="+jsF.Get("id_sess"), 
			"arg[id_lang]="+id_lang, "arg[r]="+Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join("&");

		var oncompletefn = function(r)
		{
			var response = r.responseText;
			if (response && response.length > 0)
			{
				if ( response == "0" ) { /* No such permissions */ alert( "No such permissions . Error code: " + response ); }
				else if ( response == "1" )
				{
					/* Remove row `langsd-123` from table */
					var tbl = fn_getElementById( "langsd-" + id_lang );
					if ( tbl )
					{
						var tbl_remove_len = tbl.childNodes.length;
						for (var i = 0; i < tbl_remove_len; i++)
						{
							tbl.removeChild( tbl.lastChild );
						}
						tbl.parentNode.removeChild( tbl );
					}
					/* Reload the current page */
					/*
					document.location.href = document.location.href.replace("#", "");
					*/
				}
				else if (response == "2") { /* No such item */ alert( "No such item. Error code: " + response ); }
				else { /* Unknown error */ alert( response ); }
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	};
};