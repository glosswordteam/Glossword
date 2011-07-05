oAz = new function()
{
	this.cache = [];
	this.tbl = false;
	/* HTML-form */
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


	/* Moves a letter up */
	this.up = function( el, id_letter )
	{
		/* Please wait... */
		el.className = "btn disabled";
		jsF.inner_text( el, jsF.Get("oTkit_1082") );

		this._updown( id_letter, "up" );
	};
	/* Moves a letter down */
	this.down = function( el, id_letter )
	{
		el.className = "btn disabled";
		jsF.inner_text( el, jsF.Get("oTkit_1082") );
		
		this._updown( id_letter, "down" );
	};
	/* Use JSON array on Up/Down */
	this._updown = function( id_letter, move )
	{
		var params = [
			"arg[sef_output]=ajax", "arg[action]=edit", "arg[target]=az", "arg[sid]=" + jsF.Get("id_sess"),
			"arg[id_letter]=" + id_letter, "arg[id_lang]=" + jsF.Get("id_lang"), "arg[move]=" + move,
			"arg[r]=" + Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join( "&" );

		var oncompletefn = function( r )
		{
			if ( !r.responseText || r.responseText.length == 0 ) { return; }
			var oJSon = eval( "(" + r.responseText + ")" );
			switch ( parseInt( oJSon.responseStatus ) )
			{
				case 200:
					/* var ar = [[123, "A", "a"], [97, "Z", "z"]]; */
					oAz._draw_table( oJSon.ar );
					/* Rebuild cache */
//					var el = oAz.tbl.tBodies[0];
//					var int_rows = (el && el.rows.length) || 0;
//					oAz.cache = [];
//					for ( var i = 0, cnt = 0; i < int_rows; i++ ) { if ( i > 0 ){ oAz.cache[cnt] = el.rows[i]; cnt++; } }

				break;
				case 403: /* No such permissions */ alert( "No such permissions . Error code: " + oJSon.responseStatus ); break;
				case 404: /* No such item */ alert( "No such item. Error code: " + oJSon.responseStatus ); break;
				default: /* Unknown error */ alert( r.responseText ); break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	};

	/* */
	this.add_onoff = function( is_enable )
	{
		var a = fn_getElementById( "a-letter-add" );
		if ( is_enable ) { a.className = "submitnext"; a.href = "javascript:oAz.add()"; }
		else { a.className = "submitnext disabled"; a.href = "javascript:void(0)"; }
	}

	/* */
	this.build_index = function( id_table )
	{
		this.tbl = fn_getElementById( id_table );
		var el = this.tbl.tBodies[0];

		/* Read the current rows */
		/* el.rows[i].id */
		var int_rows = (el && el.rows.length) || 0;
		for ( var i = 0, cnt = 0; i < int_rows; i++ )
		{
			if ( i > 0 ){ this.cache[cnt] = el.rows[i]; cnt++; }
		}
	};
	/* Redraws table using array */
	this._draw_table = function( ar )
	{
		var el = this.tbl.tBodies[0];
		/* Remove all of the current rows and all its childs */
		for (var i = 0, el_remove_len = el.childNodes.length; i < el_remove_len; i++)
		{
			if (i > 1)
			{
				el.removeChild( el.lastChild );
			}
		}
		/* Clear cache */
		oAz.cache = [];
		for (var i = 0, len = ar.length, cnt = 0; i < len; i++)
		{
			/* Add a new row */
			el.appendChild( this.new_tr_el( ar[i], i, len ) );
			
				/* Rebuild cache */
			oAz.cache[cnt] = el.rows[i];
			
			cnt++;
		}
		jsF.stripe("azletters-list");
	};
	/* Draws a row. [123, "A", "a"] */
	this.new_tr_el = function( ar, cnt, len )
	{
		var int_rows = oAz.cache.length;

		var tr = document.createElement( "tr" );
		var td = document.createElement( "td" );
		var a = document.createElement( "a" );

		var tr_clone = tr.cloneNode(false);
		var td1_clone = td.cloneNode(false),
			td2_clone = td.cloneNode(false),
			td3_clone = td.cloneNode(false),
			td4_clone = td.cloneNode(false),
			td5_clone = td.cloneNode(false);

		td1_clone.className = "n";
		td1_clone.appendChild( document.createTextNode( int_rows + 1 ) );
		tr_clone.appendChild( td1_clone );

		td2_clone.className = "m";
		td2_clone.appendChild( document.createTextNode( ar[1] + " " + ar[2] ) );
		tr_clone.appendChild( td2_clone );

		td3_clone.className = "m";
		td3_clone.appendChild( document.createTextNode( ar[1] ) );
		tr_clone.appendChild( td3_clone );

		td4_clone.className = "m";
		td4_clone.appendChild( document.createTextNode( ar[2] ) );
		tr_clone.appendChild( td4_clone );

		td5_clone.className = "textleft";
		var a_clone = a.cloneNode(false);
			a_clone.className = "btn add";
			a_clone.href = "javascript:void(0)";
			a_clone.appendChild( document.createTextNode( jsF.Get("oTkit_1212") ));
			a_clone.setAttribute( "onclick", "oAz.up(this, " + ar[0] + ")" );
			if ( cnt == 0 )
			{
				a_clone.className = "btn disabled";
				a_clone.setAttribute( "onclick", "" );
			}
		td5_clone.appendChild( a_clone );
		td5_clone.appendChild( document.createTextNode( " " ) );
		var a_clone = a.cloneNode(false);
			a_clone.className = "btn add";
			a_clone.href = "javascript:void(0)";
			a_clone.appendChild( document.createTextNode( jsF.Get("oTkit_1213") ));
			a_clone.setAttribute( "onclick", "oAz.down(this, " + ar[0] + ")" );
			if ( cnt + 1 == len )
			{
				a_clone.className = "btn disabled";
				a_clone.setAttribute( "onclick", "" );
			}
		td5_clone.appendChild( a_clone );
		td5_clone.appendChild( document.createTextNode( " " ) );
		var a_clone = a.cloneNode(false);
			a_clone.className = "btn remove";
			a_clone.href = "javascript:void(0)";
			a_clone.appendChild( document.createTextNode( jsF.Get("oTkit_1043") ));
			a_clone.setAttribute( "onclick", "oAz.remove_confirm(" + ar[0] + ")" );
		td5_clone.appendChild( a_clone );
		tr_clone.appendChild( td5_clone );
		tr_clone.id = "lettersd-" + ar[0];

		return tr_clone;
	};

	/* Submit a new letters pair, receive a new Letter ID */
	/* Use cached rows on Add */
	this.add = function()
	{
		if ( !oAz.validate_new() ){ return; }

		/* Please wait... */
		fn_getElementById( "a-letter-add" ).className = "submitnext disabled";
		jsF.inner_text( fn_getElementById( "a-letter-add" ), jsF.Get("oTkit_1082") );

		var params = [
			"arg[sef_output]=ajax", "arg[action]=add", "arg[target]=az", "arg[sid]=" + jsF.Get("id_sess"),
			"arg[id_lang]=" + jsF.Get("id_lang"),
			"arg[uc]=" + encodeURIComponent( fn_getElementById( "letter-uc-new" ).value ),
			"arg[lc]=" + encodeURIComponent( fn_getElementById( "letter-lc-new" ).value ),
			"arg[r]=" + Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join( "&" );

		var oncompletefn = function( r )
		{
			if ( !r.responseText || r.responseText.length == 0 ) { return; }
			var oJSon = eval( "(" + r.responseText + ")" );
			switch ( parseInt( oJSon.responseStatus ) )
			{
				case 200:

					/* Clean rows */
					var el = oAz.tbl.tBodies[0];
					for (var i = 0, el_remove_len = el.childNodes.length; i < el_remove_len; i++)
					{
						if (i > 1) { el.removeChild( el.lastChild ); }
					}

					/* Append new row, note oJSon.id_letter */
					var new_row = oAz.new_tr_el( 
						[oJSon.id_letter, fn_getElementById( "letter-uc-new" ).value, fn_getElementById( "letter-lc-new" ).value], 
						1, 
						1 
					);
					oAz.cache.push( new_row );

					/* Reset entered values */
					fn_getElementById( "letter-uc-new" ).value = fn_getElementById( "letter-lc-new" ).value = "";

					/* Append cached rows */
					for ( var i = 0; i < oAz.cache.length; i++ ) { el.appendChild( oAz.cache[i] ); }

					/* Restore button text */
					jsF.inner_text( fn_getElementById( "a-letter-add" ), jsF.Get("oTkit_1001") );

				break;
				case 403: /* No such permissions */ alert( "No such permissions . Error code: " + oJSon.responseStatus ); break;
				case 404: /* No such item */ alert( "No such item. Error code: " + oJSon.responseStatus ); break;
				default: /* Unknown error */ alert( r.responseText ); break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	}

	/* */
	this.validate_new = function()
	{
		oAz.is_valid_new = 1;
		var uc = fn_getElementById( "letter-uc-new" ).value;
		var lc = fn_getElementById( "letter-lc-new" ).value;
		if ( uc == '' || lc == '' ) { oAz.is_valid_new = 0; }
		return oAz.is_valid_new;
	}
	/* */
	this.remove_confirm = function( id_letter )
	{
		if ( confirm( jsF.Get( "oTkit_1043" ) + ':\u0020' + jsF.Get( "oTkit_1051" ) ) )
		{
			this.remove( id_letter );
		}
	};
	/* */
	this.remove = function( id_letter )
	{
		var params = [
			"arg[sef_output]=ajax", "arg[action]=remove", "arg[target]=az", "arg[sid]=" + jsF.Get("id_sess"),
			"arg[id_letter]=" + id_letter, "arg[r]=" + Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join( "&" );
		var oncompletefn = function( r )
		{
			if ( !r.responseText || r.responseText.length == 0 ) { return; }
			var oJSon = eval( "(" + r.responseText + ")" );
			switch ( parseInt( oJSon.responseStatus ) )
			{
				case 200:

					/* Remove row `lettersd-123` from table */
					jsF.destroy_el( fn_getElementById( "lettersd-" + id_letter ) );

					/* Rebuild cache */
					var el = oAz.tbl.tBodies[0];
					var int_rows = (el && el.rows.length) || 0;
					oAz.cache = [];
					for ( var i = 0, cnt = 0; i < int_rows; i++ ) { if ( i > 0 ){ oAz.cache[cnt] = el.rows[i]; cnt++; } }

				break;
				case 403: /* No such permissions */ alert( "No such permissions . Error code: " + oJSon.responseStatus ); break;
				case 404: /* No such item */ alert( "No such item. Error code: " + oJSon.responseStatus ); break;
				default: /* Unknown error */ alert( r.responseText ); break;
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	};
};