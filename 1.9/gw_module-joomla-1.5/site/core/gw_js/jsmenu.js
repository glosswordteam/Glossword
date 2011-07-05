jsMenu = new function()
{
	this.el_menu = false;
	this.el_target = false;
	/* */
	this.updateXY = function()
	{
		return false;
		
	};
	/* onclick */
	/* element, href, text, classname */
	this.show = function(a)
	{
		a.blur();
		this.el_target = arguments[0];
		
		/* Check for already existent menu */
		if (this.el_menu)
		{
			document.body.removeChild( this.el_menu );
		}
		this.el_menu = document.createElement("div");
		this.el_menu.id = "jsmenu";

		var table = document.createElement("table");
		table.className = "jsmenu";
		table.setAttribute("cellspacing", 0);

		var tr = document.createElement('tr');
		var th = document.createElement('th');
		var td = document.createElement('td');
		var tbody = document.createElement('tbody');
		var a = document.createElement('a');
		
		var pos = jsF.GetRealPos(jsMenu.el_target);
		pos["left"] += 0;

		/* Create menu items */
		for (var i = 1; i < arguments.length; i++)
		{
			var tbody_tr = tr.cloneNode(false);
			var tbody_td = td.cloneNode(false);
			var tbody_a = a.cloneNode(false);

			tbody_a.href = arguments[i][0];
			tbody_a.setAttribute( "title", arguments[i][1] );
			tbody_a.onclick = jsMenu.hide;
			tbody_a.appendChild( document.createTextNode( arguments[i][1] ) );
			
			tbody_td.appendChild( tbody_a );
			
			tbody_tr.appendChild( tbody_td );
			tbody.appendChild( tbody_tr );
		}
		table.appendChild( tbody );

		this.el_menu.appendChild( table );
		
		/* Place menu under called element */
		this.el_menu.style.position = "absolute";
		this.el_menu.style.zIndex = 1000;
		this.el_menu.style.opacity = 1;
		this.el_menu.style.visibility = 'visible';

		document.body.appendChild( this.el_menu );

		/* Correct aligment */
		var pos = jsF.AlignToPos(pos, this.el_menu.offsetWidth, this.el_menu.offsetHeight);
		this.el_menu.style.left = pos["left"] + 'px';
		this.el_menu.style.top = pos["top"] + 'px';

		/* Close menu on clicking on empty area */
		setTimeout(function(){jsF.addEvent(document, "click", jsMenu.is_over)}, 30);

		/* Close menu by presseng Esc buttonn */
		setTimeout(function(){jsF.addEvent(document, "keypress", jsMenu.keypress)}, 20);

		return false;
	};
	/* */
	this.is_over = function(e)
	{
		var x = e.clientX + document.body.scrollLeft;
		var y = e.clientY + jsF.scrollTop();
		var pos = jsF.GetRealPos(jsMenu.el_target);
		var pos2 = jsF.GetRealPos(jsMenu.el_menu);
		
		/*
		fn_getElementById("search-query").value = "x:" + x + " >= " + pos["left"] + "; y: " + y + " >= " + pos["top"];
		*/
		
		if ((x >= pos["left"] && x <= pos["right"] && y >= pos["top"] && y <= pos["bottom"])
			|| (x >= pos2["left"] && x <= pos2["right"] && y >= pos2["top"] && y <= pos2["bottom"]))
		{
			return false;
		}
		/* hide menu */
		jsMenu.hide();
	};
	/* */
	/* Hide pop-up menu, remove events */
	this.hide = function()
	{
		setTimeout(function(){jsMenu.el_menu.style.opacity = 0.8}, 20);
		setTimeout(function(){jsMenu.el_menu.style.opacity = 0.6}, 40);
		setTimeout(function(){jsMenu.el_menu.style.opacity = 0.4}, 60);
		setTimeout(function(){jsMenu.el_menu.style.opacity = 0.2}, 80);
		setTimeout(function(){jsMenu.el_menu.style.visibility = "hidden"}, 100);
		/*
		setTimeout(function(){jsF.removeEvent(window, "resize", _this.menuUpdateXY)}, 10);
		setTimeout(function(){jsF.removeEvent(document, "click", jsMenu.is_over)}, 10);
		clearInterval(jsMenu.timer_is_over);
		*/
		jsF.removeEvent(document, "click", jsMenu.is_over);
		jsF.removeEvent(document, "keypress", jsMenu.keypress);

	};
	/* */
	this.keypress = function(e)
	{
		if (!e) {e = window.event;}
		if (!e) {return;}
		if (e.keyCode == 27){jsMenu.hide();}
	};
};