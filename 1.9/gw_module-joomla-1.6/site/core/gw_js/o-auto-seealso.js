var oAutoSeeAlso = new function()
{
	this.id_field = "";
	this.phrase = "";
	this.fld = false;
	this.variants = 0;
	this.int_v = 0;
	this.min_length = 4; /* Depends on database variable `ft_min_word_len` */
	this.ar_variants = [];
	/* */
	this.updateXY = function()
	{
		var pos = jsF.GetRealPos( oAutoSeeAlso.fld );
		var el_area = fn_getElementById("variants");
		var pos = jsF.AlignToPos( pos, el_area.offsetWidth, el_area.offsetHeight );
		el_area.style.left = pos["left"] + 'px';
		el_area.style.top = pos["top"] + 'px';
	};
	/* */
	this.init = function( id_field )
	{
		this.id_field = id_field;
		this.destroy();
		this.fld = fn_getElementById( this.id_field );
		
		jsF.addEvent( this.fld, "keyup", oAutoSeeAlso.onKeyUp );
		jsF.addEvent( this.fld, "keypress", oAutoSeeAlso.onKeyPress );
		
		this.fld.setAttribute("autocomplete", "off");
	};
	/* */
	this.onKeyPress = function(e)
	{
		var key = (window.event) ? window.event.keyCode : e.keyCode;
		var RETURN = 13;
		var TAB = 9;
		var ESC = 27;

		switch(key)
		{
			case RETURN:
				oAutoSeeAlso.setHighlightedValue();
				return false;
			break;
			case TAB:
				oAutoSeeAlso.setHighlightedValue();
				return false;
			break;
			case ESC:
				oAutoSeeAlso.destroy();
				return false;
			break;
		}
		return true;
	}
	this.onKeyUp = function(e)
	{
		var key = (window.event) ? window.event.keyCode : e.keyCode;
		var ARROW_DOWN = 40;
		var ARROW_UP = 38;
		var PAGE_DOWN = 34;
		var PAGE_UP = 33;
		switch( key )
		{
			case PAGE_UP:
				oAutoSeeAlso.changeHighlight(key);
			break;
			case PAGE_DOWN:
				oAutoSeeAlso.changeHighlight(key);
			break;
			case ARROW_UP:
				oAutoSeeAlso.changeHighlight(key);
			break;
			case ARROW_DOWN:
				oAutoSeeAlso.changeHighlight(key);
			break;
			default:
				oAutoSeeAlso.get_results( oAutoSeeAlso.fld.value );
				return true;
			break;
		}
		return false;
	};
	
	this.get_results = function( value )
	{
		oAutoSeeAlso.destroy();
		clearTimeout( oAutoSeeAlso.timer_ajax );
		if ( value == "" || value.length < oAutoSeeAlso.min_length )
		{
			return false;
		}
		oAutoSeeAlso.timer_ajax = setTimeout( function() { oAutoSeeAlso.ajax_request( value ) }, 500 );

	};
	this.ajax_request = function( value )
	{
		var params = [
			"arg[sef_output]=ajax", "arg[action]=search", "arg[target]=items", "arg[sid]="+jsF.Get("id_sess"), 
			"arg[area]=phrase.partial,ordering.alpha",
			"arg[q]="+value, "arg[r]="+Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join("&");

		var oncompletefn = function(r)
		{
			var response = r.responseText;
			if ( response && response.length > 0 )
			{
				oAutoSeeAlso.ar_variants = eval( "(" + response + ")" );
				if ( oAutoSeeAlso.ar_variants.length > 0 )
				{
					oAutoSeeAlso.draw_variants( oAutoSeeAlso.ar_variants );
					/* draw_variants() executed with a delay */
					oAutoSeeAlso.setHighlight(1);
				}
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	}
	/* */
	/* Draws box with the selection of variants */
	this.draw_variants = function(ar)
	{
		/* Create new table */
		var table = document.createElement("table");
		table.style.opacity = "0.9";
		table.style.position = "absolute";
		table.style.zIndex = "1000";
		table.id = "variants";
		table.style.backgroundColor = "#FFF";
		table.setAttribute("cellspacing", 1);
		table.setAttribute("width", "500px");
		table.setAttribute("border", 0);
		
		table.insertBefore( document.createElement('tbody'), table.firstChild );
		var table_tr = document.createElement('tr');
		var table_td = document.createElement('td');
		var selected = document.createElement('strong');
		/* */
		for (var i = 0; i < ar.length; i++)
		{
			var table_tr_clone = table_tr.cloneNode(false);
			var table_td_clone = table_td.cloneNode(false);
			var selected_clone = selected.cloneNode(false);

			table_td_clone.id = "oAutoSeeAlso_tr" + (i + 1);
			table_td_clone.onmouseover = oAutoSeeAlso.onMouseOverVariant;
			table_td_clone.onclick = oAutoSeeAlso.setHighlightedValue;
			
			/* Highlight words */
			var val = ar[i];
			var st = val.toLowerCase().indexOf( oAutoSeeAlso.fld.value.toLowerCase() );
			table_td_clone.appendChild( document.createTextNode( val.substring(0, st) ) );
			selected_clone.appendChild( document.createTextNode( val.substring(st, st + oAutoSeeAlso.fld.value.length ) ) );
			table_td_clone.appendChild( selected_clone );
			table_td_clone.appendChild( document.createTextNode( val.substring( st + oAutoSeeAlso.fld.value.length ) ) );

			table_tr_clone.appendChild( table_td_clone );
			table.tBodies[0].appendChild( table_tr_clone );
		}
		jsF.disable_selection( table );
		/* Add table to the document */
		document.body.appendChild( table );
		
		/* Correct aligment */
		this.updateXY();
		
	};
	/* */
	this.onMouseOverVariant = function()
	{
		oAutoSeeAlso.int_v = this.id.replace(/[^0-9]/g, "");
		oAutoSeeAlso.setHighlight( oAutoSeeAlso.int_v ); 
	};
	/* */
	this.destroy = function()
	{
		if (oAutoSeeAlso.fld)
		{
			oAutoSeeAlso.fld.focus();

			var tbl = fn_getElementById( "variants" );
			if ( tbl !== false )
			{
				var tbl_remove_len = tbl.childNodes.length;
				for (var i = 0; i < tbl_remove_len; i++) 
				{
					tbl.removeChild( tbl.lastChild );
				}
				tbl.parentNode.removeChild( tbl );
			}
		}
	}; 
	/* */
	this.setHighlightedValue = function()
	{
		if ( oAutoSeeAlso.ar_variants.length > 0 )
		{
			oAutoSeeAlso.fld.value = oAutoSeeAlso.ar_variants[oAutoSeeAlso.int_v-1];
		}
		oAutoSeeAlso.destroy();
	};
	/* */
	this.changeHighlight = function(key)
	{
		var ARROW_UP = 38;
		var ARROW_DOWN = 40;
		var PAGE_UP = 33;
		var PAGE_DOWN = 34;

		var n;
		/* Enable Up-Down arrow keys */
		if (key == ARROW_DOWN) { n = oAutoSeeAlso.int_v + 1; }
		else if (key == ARROW_UP){ n = oAutoSeeAlso.int_v - 1; }
		
		/* Allow loop through values  */
		if (n > oAutoSeeAlso.ar_variants.length){ n = 1; }
		if (n < 1) { n = oAutoSeeAlso.ar_variants.length; }
		
		/* Enable Page Up and Page Down keys */
		if (key == PAGE_UP) { n = 1; }
		if (key == PAGE_DOWN) { n = oAutoSeeAlso.ar_variants.length; }

		oAutoSeeAlso.setHighlight(n);
	};
	/* */
	this.setHighlight = function(n)
	{
		if ( !fn_getElementById("variants") ){ return false; }
		var tbodies = fn_getElementById("variants").getElementsByTagName("tbody");
		var list = tbodies[0].getElementsByTagName("tr");
		if (!list) { return false; }

		if (this.int_v > 0){ this.clearHighlight(); }
		oAutoSeeAlso.int_v = parseInt(n);

		var tds = list[oAutoSeeAlso.int_v-1].getElementsByTagName("td");
		for (var j = 0; j < tds.length; j++)
		{
			tds[j].className = "active";
		}
	};
	/* */
	this.clearHighlight = function()
	{
		var tbodies = fn_getElementById("variants").getElementsByTagName("tbody");
		var trs = tbodies[0].getElementsByTagName("tr");
		if (!trs) { return false; }

		for (var i = 0; i < trs.length; i++)
		{
			var tds = trs[i].getElementsByTagName("td");
			for (var j = 0; j < tds.length; j++)
			{
				tds[j].className = null;
			}
		}
	};
};
