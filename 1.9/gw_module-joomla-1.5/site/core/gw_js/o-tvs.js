/* Management for Translation Variants */
oTvs = new function()
{
	this.contents = "";
	this.id_tv = 0;
	this.id_tv_source = 0;
	this.id_pid = 0;
	this.id_lang_target = 0;
	this.id_lang_source = 0;
	this.timer_autosave = false;
	this.timer_check_height = false;
	this.area = false;
	this.tv_classname = false;

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

	/* Build indexes for prev/next */
	this.build_index = function( id_pid )
	{
		this.id_index = 0;
		for (var i = 0, len = jsF.Get("ar_id_pids").length; i < len; i++)
		{
			if ( jsF.Get("ar_id_pids")[i] == id_pid )
			{
				this.id_index = i;
				break;
			}
		}
	};
	/* */
	this.next = function()
	{
		if ( typeof( jsF.Get("ar_id_pids")[(oTvs.id_index + 1)] ) != "undefined" )
		{
			if ( oTvs.id_lang_source == jsF.Get( "id_lang_source" ) )
			{
				oTvs.edit( jsF.Get("ar_id_tv_target")[(oTvs.id_index + 1)],
					jsF.Get("ar_id_pids")[(oTvs.id_index + 1)],
					jsF.Get("ar_id_tv_source")[(oTvs.id_index + 1)],
					oTvs.id_lang_source, oTvs.id_lang_target
				);
			}
			else
			{
				oTvs.edit( jsF.Get("ar_id_tv_source")[(oTvs.id_index + 1)],
					jsF.Get("ar_id_pids")[(oTvs.id_index+1)],
					jsF.Get("ar_id_tv_target")[(oTvs.id_index+1)],
					oTvs.id_lang_source, oTvs.id_lang_target
				);
			}
		}
	};
	/* */
	this.prev = function()
	{
		if (typeof( jsF.Get("ar_id_pids")[(oTvs.id_index - 1)] ) != "undefined" )
		{
			if ( oTvs.id_lang_source == jsF.Get( "id_lang_source" ) )
			{
				oTvs.edit( jsF.Get("ar_id_tv_target")[(oTvs.id_index - 1)],
					jsF.Get("ar_id_pids")[(oTvs.id_index - 1)],
					jsF.Get("ar_id_tv_source")[(oTvs.id_index - 1)],
					oTvs.id_lang_source, oTvs.id_lang_target
				);
			}
			else
			{
				oTvs.edit( jsF.Get("ar_id_tv_source")[(oTvs.id_index - 1)],
					jsF.Get("ar_id_pids")[(oTvs.id_index - 1)],
					jsF.Get("ar_id_tv_target")[(oTvs.id_index - 1)],
					oTvs.id_lang_source, oTvs.id_lang_target
				);
			}
		}
	};

	/* Updates position of element */
	this.updateXY = function()
	{
		var pos = jsF.GetRealPos( fn_getElementById( "row-" + oTvs.id_pid ) );
		scrollTo( 0, pos["top"] );
		var pos = jsF.AlignToPos( pos, oTvs.area.offsetWidth, oTvs.area.offsetHeight );
		oTvs.area.style.left = pos["left"] + 'px';
		oTvs.area.style.top = pos["top"] + 'px';
	};
	/* Copy from source */
	this.copy = function(id_tv_source)
	{
		fn_getElementById("tvedit").value = decodeURIComponent( fn_getElementById("v-" + id_tv_source).value );
		return false;
	};
	/* */
	this.is_over = function(e)
	{
		var x = e.clientX + document.body.scrollLeft;
		var y = e.clientY + jsF.scrollTop();
		var pos = jsF.GetRealPos( oTvs.area );

		/*
		fn_getElementById("search-query").value = "x:" + x + " >= " + pos["left"] + "; y: " + y + " >= " + pos["top"];
		*/

		if ( x >= pos["left"] && x <= pos["right"] && y >= pos["top"] && y <= pos["bottom"] )
		{
			return false;
		}

		oTvs.destroy();
	};
	/* Destroys editing window */
	this.destroy = function()
	{
		if ( !oTvs.area || oTvs.area.childNodes.length == 0 ) { return false; }

		/*
		setTimeout( function(){ jsF.FX_fade_out_set( oTvs.area.id, 0.8 ); }, 20 );
		*/

		jsF.removeEvent( document, "click", oTvs.is_over );
		jsF.removeEvent( document, "resize", oTvs.updateXY );
		jsF.removeEvent( oTvs.area, "keypress", oTvs.onKeyPress );

		clearInterval( oTvs.timer_style1 );
		clearInterval( oTvs.timer_style2 );
		clearInterval( oTvs.timer_check_height );
		clearInterval( oTvs.timer_autosave );

		fn_getElementById( "divtv-" + oTvs.id_tv ).className = oTvs.tv_classname;
		/*
		fn_getElementById( "divtv-" + oTvs.id_tv ).style.borderStyle = oTvs.tv_borderStyle;
		*/
		jsF.destroy_el( oTvs.area );
	};
	/* Draws translation window */
	this.edit = function( id_tv, id_pid, id_tv_source, id_lang_source, id_lang_target )
	{
		if ( this.tv_classname )
		{
			fn_getElementById( "divtv-" + this.id_tv ).className = this.tv_classname;
		}
		/* */
		this.build_index( id_pid );

		/*
		this.tv_borderStyle = fn_getElementById( "divtv-" + id_tv ).style.borderStyle;
		*/

		/* Set current Phrase ID */
		this.id_pid = id_pid;

		/*  Set Translation Variant ID */
		this.id_tv = id_tv;
		this.id_tv_source = id_tv_source;

		var div = document.createElement("div");
		var span = document.createElement( "span" );
		var a = document.createElement( "a" );
		var textarea = document.createElement( "textarea" );

		/* */
		this.destroy();

		/* Change border style for Translation Variant */
		this.tv_classname = fn_getElementById( "divtv-" + id_tv ).className;
		fn_getElementById( "divtv-" + id_tv ).className = "divtv-active";

		/* Create a new editing window */
		var area = div.cloneNode(false);
		area.id = "area";
		area.style.opacity = 1;
		area.style.width = "70%";
		area.style.visibility = "visible";
		area.style.position = "absolute";
		area.style.zIndex = 1000;
		area.style.left = 0;
		area.style.top = 0;


		/* Create textarea */
		var tvtextarea1 = textarea.cloneNode(false);
		tvtextarea1.setAttribute( "class", "inp" );
		tvtextarea1.setAttribute( "rows", "3" );
		tvtextarea1.setAttribute( "id", "tvedit" );
		/* Read value from hidden <input> */
		tvtextarea1.value = decodeURIComponent( fn_getElementById("v-" + id_tv).value );

		var tvtextarea2 = textarea.cloneNode(false);
		tvtextarea2.setAttribute( "class", "inp" );
		tvtextarea2.setAttribute( "rows", "3" );
		tvtextarea2.setAttribute( "disabled", "disabled" );
		tvtextarea2.style.color = "#333";
		tvtextarea2.style.backgroundColor = "#EEE";
		/* Read value from hidden <input> */
		tvtextarea2.value = decodeURIComponent( fn_getElementById("v-" + id_tv_source).value );

		/* Create three sections: header, body, footer */
		var div_wheader_clone = div.cloneNode(false);
		var div_wbody_clone = div.cloneNode(false);
		var div_wfooter_clone = div.cloneNode(false);

		div_wheader_clone.className = 'window-header';
		div_wbody_clone.className = 'window-body';
		div_wfooter_clone.className = 'window-footer';

		/* "Close window" button */
		var a_clone = a.cloneNode(false);
			a_clone.appendChild( document.createTextNode( jsF.Get("oTkit_1154") ) );
			a_clone.href = "#";
			a_clone.setAttribute( "title", "[ESC]" );
			a_clone.setAttribute( "onclick", "oTvs.destroy(); return false" );
			a_clone.className = "wsubmitremove";
			a_clone.style.float = "right";
			var span_clone = span.cloneNode(false);
				span_clone.className = "icon-rm";
				a_clone.appendChild( span_clone );
		/* Collect header */
		div_wheader_clone.appendChild( a_clone );

		var a_clone = a.cloneNode(false);
			a_clone.appendChild( document.createTextNode( "← " + jsF.Get("oTkit_1035") ));
			a_clone.setAttribute( "onclick", "oTvs.prev()" );
			a_clone.setAttribute( "title", "" );
		div_wheader_clone.appendChild( a_clone );
		var a_clone = a.cloneNode(false);
			a_clone.appendChild( document.createTextNode( jsF.Get("oTkit_1034") + " →" ));
			a_clone.setAttribute( "onclick", "oTvs.next()" );
			a_clone.setAttribute( "title", "" );
		div_wheader_clone.appendChild( a_clone );
		var a_clone = a.cloneNode(false);
			a_clone.setAttribute( "class", "wsubmitnext" );
			a_clone.appendChild( document.createTextNode(  jsF.Get("oTkit_1017") + " & " + jsF.Get("oTkit_1034" ) ));
			a_clone.setAttribute( "onclick", "oTvs.post('"+id_tv+"', 1);return false" );
			a_clone.setAttribute( "title", "[CTRL + ENTER]" );
		div_wheader_clone.appendChild( a_clone );
		var a_clone = a.cloneNode(false);
			a_clone.setAttribute( "class", "wsubmitok" );
			a_clone.appendChild( document.createTextNode(  jsF.Get("oTkit_1017") + " & " + jsF.Get("oTkit_1154") ));
			a_clone.setAttribute( "onclick", "oTvs.post('"+id_tv+"');return false" );
			a_clone.setAttribute( "title", "" );
		div_wheader_clone.appendChild( a_clone );

		jsF.disable_selection( div_wheader_clone );

		/* Apply header */
		area.appendChild( div_wheader_clone );

		var lang_name_source = ( id_lang_source == jsF.Get( "id_lang_source" ) ? jsF.Get( "lang_name_source" ) : jsF.Get( "lang_name_target" ) );
		var lang_name_target = ( id_lang_target == jsF.Get( "id_lang_target" ) ? jsF.Get( "lang_name_target" ) : jsF.Get( "lang_name_source" ) );
		this.id_lang_source = id_lang_source;
		this.id_lang_target = id_lang_target;

		/* Collect body */
		div_wbody_clone.appendChild( document.createTextNode( jsF.Get( "oTkit_1187" ) + ":\u0020" + lang_name_source ) );
		div_wbody_clone.appendChild( tvtextarea2 );
		div_wbody_clone.appendChild( document.createTextNode( jsF.Get( "oTkit_1206" ) + ":\u0020" + lang_name_target ) );
		div_wbody_clone.appendChild( tvtextarea1 );

		/* Apply body */
		area.appendChild( div_wbody_clone );


		/* Add editing window to the end of document
		setTimeout( function(){ document.body.appendChild( oTvs.area ); }, 20 );
		*/

		this.area = area;
		document.body.appendChild( this.area );

		/* Autosave */
		this.timer_autosave = setInterval( function(){ oTvs.store( fn_getElementById("tvedit").value ) }, 202 );

		/* Check height */
		this.timer_check_height = setInterval( function(){ jsF.checkFieldHeightChar("tvedit") }, 201 );

		/* */
		setTimeout( function(){ tvtextarea1.focus(); }, 110 );

/* Highlight border
		setTimeout( function(){ oTvs.timer_style1 = setInterval( function(){ fn_getElementById( "divtv-" + id_tv ).style.borderStyle = "dashed"; }, 1000 ) }, 10 );
		setTimeout( function(){ oTvs.timer_style2 = setInterval( function(){ fn_getElementById( "divtv-" + id_tv ).style.borderStyle = "dotted"; }, 1000 ) }, 500 );
*/

		/* Correct aligment */
		this.updateXY();

		/* Move editing window onResize */
		jsF.addEvent( window, "resize", oTvs.updateXY );

		/* Apply keyboard controls */
		jsF.addEvent( this.area, "keypress", oTvs.onKeyPress );

		/* Close editing window by clicking on empty area */
		setTimeout( function(){ jsF.addEvent( document, "click", oTvs.is_over ) }, 30 );

	};
	/* Stores text typed in textarea */
	this.store = function( value )
	{
		this.contents = value;
	};

	/* onclick */
	this.post = function( id_tv, is_next )
	{
		oTvs.id_tv = id_tv;
		/* Read the current value from autosave */
		oTvs.contents = fn_getElementById("tvedit").value;

		var str_compare1 = oTvs.contents;
		/* Value from hidden <input> */
		var str_compare2 = decodeURIComponent( fn_getElementById("v-" + id_tv).value );
		str_compare1 = str_compare1.replace( new RegExp( "\\r|\\n", "g" ), "-" );
		str_compare2 = str_compare2.replace( new RegExp( "\\r|\\n", "g" ), "-" );

		/* Do not post unchanged */
		if ( str_compare1 == str_compare2 )
		{
			oTvs.destroy();

			/* Clean buffer */
			oTvs.store("");

			/* Go Next */
			if ( typeof( is_next  ) != "undefined" && is_next ) { oTvs.next(); }
			return false;
		}

		/* Update Translation Variant */
		var params = [
			"arg[sef_output]=ajax", "arg[action]=edit", "arg[target]=tvs", "arg[sid]=" + jsF.Get("id_sess"),
			"arg[id_tv]=" + oTvs.id_tv, "arg[id_lang]=" + oTvs.id_lang_target, "arg[id_pid]=" + oTvs.id_pid,
			"arg[tv_value]=" + encodeURIComponent( oTvs.contents ), "arg[r]=" + Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join("&");

		var oncompletefn = function(r)
		{
			if ( r.responseText.length == 0 ) { return; }
			var oJSon = eval( "(" + r.responseText + ")" );
			switch ( parseInt( oJSon.responseStatus ) )
			{
				case 500: /* Too many requests */ break;
				case 200: /* Success */

					/* Replace old contents in hidden <input> */
					fn_getElementById( "v-" + oTvs.id_tv ).value = encodeURIComponent( oTvs.contents );

					var el_div = fn_getElementById( "divtv-" + oTvs.id_tv );
					var el_remove_len = el_div.childNodes.length;
					for ( var i = 0; i < el_remove_len; i++ )
					{
						el_div.removeChild( el_div.lastChild );
					}

					/* Chunk long values */
					if ( oTvs.contents.length > 255 )
					{
						oTvs.contents = oTvs.contents.substr(0, 255) + "…";
					}
					el_div.appendChild( document.createTextNode( oTvs.contents + "\u00A0" ) );

					oTvs.destroy();

					/* Clean buffer */
					oTvs.store("");

					/* New Translation Variant Added */
					if ( typeof( oJSon.id_tv ) != "undefined" )
					{
						/* Replace Translation Variant ID */
						oTvs.replace_id_tv( oTvs.id_tv, oJSon.id_tv );
					}

					/* Go Next */
					if ( typeof( is_next  ) != "undefined" && is_next ) { oTvs.next(); }
				break;
				case 403: /* Authorization required */ break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );

	};
	/* Replaces Translation Variant ID */
	this.replace_id_tv = function( id_old, id_new )
	{
		/* Update hidden source */
		fn_getElementById( "v-" + id_old ).id = "v-" + id_new;

		/* Update current translation variant div and onclick */
		var el = fn_getElementById( "divtv-" + id_old );
		el.setAttribute( "onclick", el.getAttribute( "onclick" ).replace( id_old, id_new ) );
		el.id = "divtv-" + id_new;

		/* Update source translation variant div and onclick */
		var el = fn_getElementById( "divtv-" + oTvs.id_tv_source );
		el.setAttribute( "onclick", el.getAttribute( "onclick" ).replace( id_old, id_new ) );

		/* Update index */
		if ( oTvs.id_lang_source == jsF.Get( "id_lang_source" ) )
		{
			var ar = jsF.Get("ar_id_tv_target");
			ar[oTvs.id_index] = id_new;
			jsF.Set( "ar_id_tv_target", ar );
		}
		else
		{
			var ar = jsF.Get("ar_id_tv_source");
			ar[oTvs.id_index] = id_new;
			jsF.Set( "ar_id_tv_source", ar );
		}
	};
	/* */
	this.onKeyPress = function(e)
	{
		var key = (window.event) ? window.event.keyCode : e.keyCode;
		var RETURN = 13;
		var ESC = 27;
		switch( key )
		{
			case RETURN:
			case 10: /* IE */
				if (e.ctrlKey == true)
				{
					oTvs.post( oTvs.id_tv, 1 );
					return false;
				}
				return false;
			break;
			case ESC:
				oTvs.destroy();
				return false;
			break;
		}
		return true;
	};

};