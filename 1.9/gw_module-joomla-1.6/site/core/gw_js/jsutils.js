/**
 * ----------------------------------------------
 * Javascript library
 * ----------------------------------------------
 */
var expires = new Date();
expires.setTime(expires.getTime() + (1000 * 86400 * 365));
/* check for RegExp() */
var is_regexp = (window.RegExp) ? true : false;
var fontSize;
  
  
/**
 * document.getElementById replacement.
 * 
 * @param string HTML-tag ID.
 * @return HTML-object if exists, FALSE otherwise.
 */
function fn_getElementById(el_name)
{
	if (document.getElementById)
	{
		return (document.getElementById(el_name) == null) ? false : document.getElementById(el_name);
	}
	else if (document.all)
	{
		return (document.all[el_name] == null) ? false : document.all[el_name];
	}
	return false;
}

/* new window replacement */
function nw(href)
{
	window.open(href);
	return false;
}




/**
 * ----------------------------------------------
 * Collapsed objects
 * ----------------------------------------------
 */
var ar_coll_obj = [];
function collapse_all()
{
	for (var i = 0, length1 = ar_coll_obj.length; i < length1; i++)
	{
		fn_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
		fn_getElementById("ci-" + ar_coll_obj[i]).src = jsF.Get("path_css") + "/collapse_off.png";
	}
}

function uncollapse_all(is_save)
{
	/* saves state */
	if (is_save)
	{
		is_cookie = fetch_cookie("site_collapse");
		/* do not save state if already set */
		if (is_cookie != null)
		{
			/* check states */
			collapsed = is_cookie.split("\n");
			/* array elements in Javascript are stored in a random order */
			for (var i = 0, length1 = ar_coll_obj.length; i < length1; i++)
			{
				for (var i2 = 0, length2 = collapsed.length; i2 < length2; i2++)
				{
					if (collapsed[i2] == ar_coll_obj[i])
					{
						fn_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
						fn_getElementById("ci-" + ar_coll_obj[i]).src = jsF.Get("path_css") + "/collapse_off.png";
					}
				}
			}
		}
		else
		{
			for (var i = 0, length1 = ar_coll_obj.length; i < length1; i++)
			{
				fn_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
				toggle_collapse( ar_coll_obj[i] );
			}
		}
	}
	else
	{
		/* just open all collapsed objects, unsaved state */
		for (var i = 0, length1 = ar_coll_obj.length; i < length1; i++)
		{
			fn_getElementById( "co-" + ar_coll_obj[i] ).style.display = "block";
			fn_getElementById( "ci-" + ar_coll_obj[i] ).src = jsF.Get("path_css") + "/collapse_on.png";
		}
	}
}
function toggle_collapse(id_obj, el, is_save)
{
	if (!is_regexp)
	{
		return false;
	}
	var obj = fn_getElementById("co-" + id_obj);
	var img = fn_getElementById("ci-" + id_obj);
	if (!obj)
	{
		if (img)
		{
			img.style.display = "none";
		}
		return false;
	}
	
	/* Set the current status */
	jsF.Set( "slider_is_progress", true );
	jsF.Set( "slider_id_slided", false );
	
	/* Source of toggle */
	if (el)
	{
		jsF.disable_selection(el);
		el.style.cursor = "pointer";
	}

	if (obj.style.display == "none")
	{
		oToggle = fn_getElementById("co-" + id_obj);
		oToggle.style.display = "block";
		jsSlider.slideContent( id_obj, jsF.Get( "slider_pixels" ) );

		save_collapsed(id_obj, false);

		if (img)
		{
			img_re = new RegExp("_off\\.png$");
			img.src = img.src.replace(img_re, '_on.png');
		}
	}
	else
	{
		jsSlider.slideContent( id_obj, (jsF.Get( "slider_pixels" ) * -1) );

		if (is_save)
		{
			save_collapsed(id_obj, true);
		}
		if (img)
		{
			img_re = new RegExp("\\_on.png$");
			img.src = img.src.replace(img_re, '_off.png');
		}
	}
	return false;
}
/* */
function save_collapsed(id_obj, addcollapsed)
{
	var collapsed = fetch_cookie("site_collapse");
	var tmp = new Array();
	if (collapsed != null)
	{
		collapsed = collapsed.split("\n");
		for (var i2 = 0, length2 = collapsed.length; i2 < length2; i2++)
		{
			if (collapsed[i2] != id_obj && collapsed[i2] != "")
			{
				tmp[tmp.length] = collapsed[i2];
			}
		}
	}
	if (addcollapsed)
	{
		tmp[tmp.length] = id_obj;
	}
	expires = new Date();
	expires.setTime(expires.getTime() + (1000 * 86400 * 365));
	set_cookie("site_collapse", tmp.join("\n"), expires);
}




/**
 * ----------------------------------------------
 * Manage cookies
 * ----------------------------------------------
 */
function set_cookie(name, value, expires)
{
	if (!expires)
	{
		expires = new Date();
	}
	document.cookie = name + "=" + escape(value) + "; expires=" + expires.toGMTString() +  "; path=/";
}
function fetch_cookie(name)
{
	var cookie_name = name + "=";
	var cookie_length = document.cookie.length;
	var cookie_begin = 0;
	while (cookie_begin < cookie_length)
	{
		var value_begin = cookie_begin + cookie_name.length;
		if (document.cookie.substring(cookie_begin, value_begin) == cookie_name)
		{
			var value_end = document.cookie.indexOf (";", value_begin);
			if (value_end == -1) 
			{
				value_end = cookie_length;
			}
			return unescape(document.cookie.substring(value_begin, value_end));
		}
		cookie_begin = document.cookie.indexOf(" ", cookie_begin) + 1;
		if (cookie_begin == 0)
		{
			break;
		}
	}
	return null;
}
function delete_cookie(name)
{
	var expireNow = new Date();
	document.cookie = name + "=" + "; expires=Thu, 01-Jan-70 00:00:01 GMT" +  "; path=/";
}



/**
 * ----------------------------------------------
 * Prepares variables and arrays to be saved in cookies.
 * ----------------------------------------------
 */
var jsVarObj = new function()
{
	var _this = this;
	this.A = "";
	this._ = function(ar)
	{
		_this.A = ar;
		return _this;
	};
	this.get = function(varname)
	{
		if (!varname){ return _this.A; }
		var len = _this.A.length;
		for (var i = 0; i < len; i++)
		{
			if (typeof(_this.A[i]) == "object")
			{
				if (_this.A[i][0] == varname)
				{
					return _this.A[i][1];
				}
			}
		}
		return -1;
	};
	this.set = function(varname, value)
	{
		var len = _this.A.length;
		for (var i = 0; i < len; i++)
		{
			if (_this.A[i][0] == varname)
			{
				return _this.A[i][1] = value;
			}
		}
		return _this.A[len] = [varname, value];
		/*
			alert( "l: " + _this.A.length + " A: " + _this.A + " varname: " + varname + ' fn: set' );
		*/
	};
	this.unset = function(varname)
	{
		var len = _this.A.length;
		for (var i = 0; i < len; i++)
		{
			if (_this.A[i][0] == varname)
			{
				_this.A.splice(i, 1);
				/*
				alert( "l:{" + _this.A.length + "} A:{" + _this.A + "} varname:{" + varname + '} fn:{unset}' );
				*/
				return;
			}
		}
	};
	return this;
};




/**
 * ----------------------------------------------
 * Javascript functions
 * ----------------------------------------------
 */
var jsF = new function()
{
	var _this = this;
	this.el = null;
	this.fontSize = 100;
	/* replacement for var */
	this.vars = [];
	/* Add a variable into the object */
	this.Set = function(k, v)
	{
		this.vars[k] = v;
	};
	/* Get a variable from the object*/
	this.Get = function(k)
	{
		return (typeof(this.vars[k]) == "undefined") ? k : this.vars[k];
	};
	

	/* Set automatic height for textarea using characters and a new line feeds */
	this.checkFieldHeightChar = function(id)
	{
		var el = fn_getElementById(id);
		if (!el) { return; }
		/* Set height to 3em by default */
		var height = 3 + (el.value.length / 75 );
		height += this.countLF(el.value);
		/* Limit height */
		if ((height > 25)) { height = 25; }
		el.style.height = height + 'em';
		el.style.lineHeight = "100%";
	};
	/* Count line feeds, used in checkFieldHeight() */
	this.countLF = function(s)
	{
		var cnt = 1;
		var re = /\n+/g;
		while ((ar = re.exec(s)) != null) { cnt++; }
		return cnt;
	};
	/* Count the number of characters and show how many characters left */
	this.checkFieldLength = function(id_field, target_id)
	{
		var el = fn_getElementById(id_field);
		if (!el) { return; }
		var target_el = fn_getElementById(target_id);
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode(el.value.length));
	};
	/* */
	this.checkFieldLengthLeft = function(id_field, target_id, max)
	{
		var el = fn_getElementById(id_field);
		if (!el) { return; }
		var left = (max - el.value.length);
		if (el.value.length > max)
		{
			left = 0;
			el.value = el.value.substring(0, max);
		}
		var target_el = fn_getElementById(target_id);
		if (!target_el)
		{
			return;
		}
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode(left));
	};
	
	this.checkFieldLengt2h = function(id_field, target_id, max)
	{
		var el = fn_getElementById(id_field);
		if (!el) { return; }
		var left = (max - el.value.length);
		if (el.value.length > max)
		{
			left = 0;
			el.value = el.value.substring(0, max);
		}
		target_el = fn_getElementById(target_id);
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode(left));
	};
	/* Used in <span> for previewing alphanumerical fields, DOM. &#160; required */
	this.preview0z = function(target_id, el)
	{
		if (!el) { return; }
		el.value = this.str0z(el.value);
		var target_el = fn_getElementById(target_id);
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode(el.value));
	};
	/* Checks URL */
	this.checkURL = function(id)
	{
		var el = fn_getElementById(id);
		if (!el) { return; }
		re = /[^0-9A-Za-z.,:\?&=+%#@!\*\(\)\[\]\/-]+/g;
		el.value = this.strTrim(el.value.replace(re, ""));
	};
	/* Trim empty spaces, by Flagrant Badassery, trim12() */
	this.strTrim = function(s)
	{
		var s = s.replace(/^\s\s*/, ''), ws = /\s/, i = s.length;
		while (ws.test(s.charAt(--i)));
		return s.slice(0, i + 1);
	};
	/* Keeps alphanumerical characters */
	this.str0z = function(s)
	{
		re = /[^0-9A-Za-z-]+/g;
		return s.replace(re, "-");
	};
	/* Used for alpha-numeric fields &#160; required */
	this.strReplace0z = function(el)
	{
		if (!el) { return; }
		el.value = this.str0z(el.value);
		el.value = el.value.toLowerCase();
		el.value = el.value.substr(0, 255);
	};
	/* Normalizes a string, éю => eyu */
	this.strNormalize = function(el)
	{
		if (!el) { return; }
		this.strReplace0z(el);
	};
	
	/* */
	this.FindParentObject = function(obj, tag_name, class_name)
	{
		if(!obj){ return null; }
		var o = obj;
		var tag = tag_name.toUpperCase();
		var cl = (class_name? class_name.toLowerCase() : null);
		while (o.parentNode)
		{
			var parent = o.parentNode;
			if (parent.tagName && parent.tagName.toUpperCase() == tag)
			{
				if (!class_name || parent.className.toLowerCase() == cl)
				{
					return parent;
				}
			}
			o = parent;
		}
		return null;
	};
	
	/* Changes the class name */
	/* @uses jsF.hasClass() */
	this.css_class_change = function(tag, src, trg)
	{
		var el = document.getElementsByTagName(tag);
		for (var i = 0; i < el.length; i++)
		{
			if (jsF.hasClass(el[i]) == src)
			{
				el[i].className = trg;
			}
		}
	};
	/* @Uses jsF.scrollTop() */
	this.AlignToPos = function(pos, w, h)
	{
		var x = pos["left"];
		var y = pos["bottom"];
		var body = document.body;
		if ((body.clientWidth + body.scrollLeft) - (pos["left"] + w) < 0)
		{
			if (pos["right"] - w >= 0 ){ x = pos["right"] - w; }
			else { x = body.scrollLeft; }
		}
		if ((body.clientHeight + jsF.scrollTop()) - (pos["bottom"] + h) < 0)
		{
			if (pos["top"] - h >= 0) { y = pos["top"] - h; }
			else { y = jsF.scrollTop(); }
		}
		return {'left':x, 'top':y};
	};
	/* */
	this.GetRealPos = function(el)
	{
		if (!el || !el.offsetParent)
		{
			return false;
		}
		var res = [];
		res["left"] = el.offsetLeft;
		res["top"] = el.offsetTop;
		var objParent = el.offsetParent;
		while (objParent && objParent.tagName != "BODY")
		{
			res["left"] += objParent.offsetLeft;
			res["top"] += objParent.offsetTop;
			objParent = objParent.offsetParent;
		}
		res["right"] = res["left"] + el.offsetWidth;
		res["bottom"] = res["top"] + el.offsetHeight;
		return res;
	};
	this.arEvents = [];
	this.addEvent = function(el, evname, func, capture)
	{
		if (el.attachEvent){el.attachEvent("on" + evname, func);}
		else if (el.addEventListener){el.addEventListener(evname, func, false);}
		else {el["on" + evname] = func;}
		this.arEvents[this.arEvents.length] = {'element': el, 'event': evname, 'fn': func};
	};
	this.removeEvent = function(el, evname, func)
	{
		if (el.detachEvent){ el.detachEvent("on" + evname, func); }
		else if(el.removeEventListener) { el.removeEventListener(evname, func, false); }
		else { el["on" + evname] = null; }
		for (var i in this.arEvents)
		{
			if (this.arEvents[i] && this.arEvents[i].event == evname)
			{
				this.arEvents[i] = null;
			}
		}
	};
	this.removeAllEvents = function(el)
	{
		for (var i in this.arEvents)
		{
			if (this.arEvents[i] && (el==false || el==this.arEvents[i].element))
			{
				jsF.removeEvent(this.arEvents[i].element, this.arEvents[i].event, this.arEvents[i].fn);
				this.arEvents[i] = null;
			}
		}
		if (el == false)
		{
			this.arEvents.length = 0;
		}
	};
	this.cancelEvent = function()
	{
		return false;
	};
	/* Get the currect scroll position */
	this.scrollTop = function()
	{
		if (document.documentElement && document.documentElement.scrollTop)
		{
			/* PC IE6 strict, Mac IE 5, Mac Firefox strict */
			return document.documentElement.scrollTop;
		}
		else if (document.body && document.body.scrollTop)
		{
			/* Safari, PC IE6 trans, Mac Firefox trans */
			return document.body.scrollTop;
		}
		else if (window.scrollY) 
		{
			/* Mozilla browsers (incl. Firefox and Safari) */
			return window.scrollY;
		}
		return 0;
	};
	
	/* <a href="javascript:formSubmit(this, 'id_form')"> instead of ugly <input type="submit"> */
	this.formSubmit = function(el_a, id_form)
	{
		/* Prevent double clicking */
		if (this.Get("is_clicked"+id_form) == 1)
		{
			return false;
		}
		this.Set("is_clicked"+id_form, 1);
		/* Change button text */
		this.inner_text( el_a, this.Get("oTkit_1067") ); /* Please wait... */
		/* */
		el_form = fn_getElementById(id_form);
		if (el_form)
		{
			el_form.submit();
		}
		return false;
	};
	/* */
	this.inner_text = function( el, text )
	{
		if ( !el ) { return; }
		
		for ( var i = 0, el_remove_len = ( el.childNodes && el.childNodes.length ) || 0; i < el_remove_len; i++ ) { el.removeChild( el.lastChild ); }
		el.appendChild( document.createTextNode( text ) );

			/*
			el.replaceChild( document.createTextNode( text ), el.firstChild );
		*/
	};
	/* Enables CTRL+Enter to submit a form */
	this.formKeypress = function(e, o)
	{
		e = (e) ? e : window.event;
		if ( ((e.keyCode == 13)||(e.keyCode == 10)) && (e.ctrlKey == true) )
		{
			/* Prevent double clicking */
			if (this.Get("is_clicked" + o.id) == 1)
			{
				return false;
			}
			this.Set("is_clicked" + o.id, 1);
			//o.submit();
		}
		return false;
	};
	/* */
	this.formStatus = function(phrase, state)
	{
		var el = fn_getElementById('ajax-status');
		if (!el) { return; }
		switch(state)
		{
			case 0:
				el.className = "fade-FFCCCC error";
			break;
			case 1:
				el.className = "fade-CCFFCC updated";
			break;
			case 2:
				el.className = "pending";
			break;
			case 3:
				el.style.display = "none";
				el.className = "";
				return;
			break;
		}
		var el_s = document.createTextNode(phrase);
		if (el.childNodes.length > 0)
		{
			el.replaceChild(el_s, el.firstChild);
		}
		else
		{
			el.appendChild(el_s);
		}
		el.style.display = "block";
		Fat.fade_all(1000);
	};
	
	/* Disables the selection for an element */
	this.disable_selection = function(el)
	{
		if ( typeof( el.onselectstart ) != "undefined" )
		{
			/* IE */
			el.onselectstart = function(){ return false }
		}
		else if ( typeof( el.style.MozUserSelect ) != "undefined" )
		{
			/* Firefox */
			el.style.MozUserSelect = "none";
		}
		else
		{
			/* Other */
			el.onmousedown = function(){ return false }
		}
		el.style.cursor = "default";
	};
	
	/* Checks for a class name */
	this.hasClass = function(obj)
	{
		if (obj.getAttributeNode("class") != null)
		{
			return obj.getAttributeNode("class").value;
		}
		return false;
	};
	/* Checks for a specified class name */
	this.hasClassName = function(obj, classname)
	{
		if (obj.getAttributeNode("class") != null)
		{
			var ar = obj.className.split(" ");
			for (var i = 0; i < ar.length; i++)
			{
				if (ar[i] == classname)
				{
					return true;
				}
			}
		}
		return false;
	};
	
	/* Zebra tables */
	/* @Uses jsF.hasClass() */
	this.stripe = function( id )
	{
		var even = false;
		var oddColor = arguments[2] ? arguments[2] : "#fffdf1";
		var evenColor = arguments[1] ? arguments[1] : "#f8f6ea";
		var table = fn_getElementById(id);

		if (!table) { return; }
		var tbodies = table.getElementsByTagName("tbody");
		for (var h = 0; h < tbodies.length; h++)
		{
			var trs = tbodies[h].getElementsByTagName("tr");
			for (var i = 0; i < trs.length; i++)
			{
				if (!trs[i].style.backgroundColor)
				{
					var tds = trs[i].getElementsByTagName("td");
					for (var j = 0; j < tds.length; j++)
					{
						if (!tds[j].style.backgroundColor && !jsF.hasClassName(tds[j], "n"))
						{
							tds[j].style.backgroundColor = even ? evenColor : oddColor;
						}
					}
				}
				even = !even;
			}
		}
	};
	this.stripe_double = function(id)
	{
		var even = false;
		var oddColor = arguments[2] ? arguments[2] : "#fffdf1";
		var evenColor = arguments[1] ? arguments[1] : "#f8f6ea";
		var table = fn_getElementById(id);
		if (!table) { return; }
		var tbodies = table.getElementsByTagName("tbody");
		for (var h = 0; h < tbodies.length; h++)
		{
			var trs = tbodies[h].getElementsByTagName("tr");
			for (var i = 0; i < trs.length; i++)
			{
				if (!trs[i].style.backgroundColor)
				{
					var tds = trs[i].getElementsByTagName("td");
					for (var j = 0; j < tds.length; j++)
					{
						if (!tds[j].style.backgroundColor && !jsF.hasClassName(tds[j], "n"))
						{
							tds[j].style.backgroundColor = even ? evenColor : oddColor;
						}
					}
				}
				if (i % 2)
				{
					even = !even;
				}
			}
		}
	};
	/* Checking for Object, kevin.vanzonneveld.net */
	this.is_object = function(mixed_var)
	{
		if (mixed_var instanceof Array)
		{
			return false;
		}
		else
		{
			return (mixed_var !== null) && (typeof(mixed_var) == 'object');
		}
	};
	/* wordwrap(), kevin.vanzonneveld.net */
	this.wordwrap = function(str, int_width, str_break, is_binary)
	{
		int_width = ((arguments.length >= 2) ? arguments[1] : 25 );
		str_break = ((arguments.length >= 3) ? arguments[2] : "\n" );
		is_binary = ((arguments.length >= 4) ? arguments[3] : false);

		var i, j, l, s, r;

		str += '';

		if (int_width < 1)
		{
			return str;
		}
		for (i = -1, l = (r = str.split(/\r\n|\n|\r/)).length; ++i < l; r[i] += s)
		{
			for (s = r[i], r[i] = ""; s.length > int_width; r[i] += s.slice(0, j) + ((s = s.slice(j)).length ? str_break : ""))
			{
				j = is_binary == 2
					|| (j = s.slice(0, int_width + 1).match(/\S*(\s)?$/))[1] ? int_width : j.input.length - j[0].length 
					|| is_binary == 1 && int_width 
					|| j.input.length + (j = s.slice(int_width).match(/^\S*/)).input.length;
			}
		}
	    return r.join("\n");
	};
	/* */
	this.FX_fade_out = function(id)
	{
		var el = fn_getElementById( id );
		if ( !el || !el.parentNode ) { return false; }
		var h = el.parentNode.clientHeight;

		var int_steps = 15, ms_step = 30, ms = 1;
		var opacity_step = 1 / int_steps;
		var height_step = h / int_steps;
		var opacity = 1.1;
		for (var i = 1; i <= int_steps; i++)
		{
			ms += ms_step;
			opacity -= opacity_step;
			h -= height_step;
			setTimeout( "jsF.FX_fade_out_set('"+id+"','"+opacity+"')", ms );
			/* Move up with a delay */
			setTimeout( "jsF.FX_height_set('"+id+"','"+h+"')", 200 + ms );
		}
		/* Hide message box */
		setTimeout( function(){ el.style.display = 'none'; }, 300 + ms );
		/* Destroy parent */
		setTimeout( function(){ jsF.destroy_el( el.parentNode ) }, 300 + ms );
	};
	/* */
	this.FX_height_set = function( id, h )
	{
		var el = fn_getElementById( id );
		if (h < 0) { h = 0; }
		el.parentNode.style.height = h + "px";
		el.style.position = "relative";
		el.style.top = h - el.clientHeight + "px";
	};
	/* */
	this.FX_fade_out_set = function( id, opacity )
	{
		var el = fn_getElementById( id );
		if (!el) { return false; }
		/* CSS3 */
		el.style.opacity = opacity;
		/* IE */
		el.style.filter = "alpha(opacity="+ (opacity * 100) +")";
	};
	/* Destroys Object or Element ID */
	this.destroy_el = function( id_or_el ) 
	{
		if ( typeof( id_or_el ) == "object" )
		{
			var el = id_or_el;
		}
		else
		{
			var el = fn_getElementById( id_or_el );
		}
		if ( !el ) { return false; }
		var el_remove_len = el.childNodes.length;
		for (var i = 0; i < el_remove_len; i++) 
		{
			el.removeChild( el.lastChild );
		}
		el.parentNode.removeChild( el );
		return false;
	};
		
};


/**
 * ----------------------------------------------
 * Slider functions
 * Uses jsF
 * ----------------------------------------------
 */
function JS_slider()
{
	this.init = function()
	{
		/* 1 - Slowest, 10 - Fastest. The number of pixels for each step. */
		jsF.Set( "slider_pixels", 55 );
	};
	/* Called from setTimeout(). Recursive. */
	this.slideContent = function( id_target, pixels )
	{
		/* oToggle and oToggleDiv are globals */
		oToggle = fn_getElementById("co-" + id_target);
		oToggle.style.overflow = "hidden";
		oToggle.style.position = "static";
		oToggleDiv = oToggle.childNodes[0];
		oToggleDiv.style.overflow = "hidden";
		oToggleDiv.style.position = "relative";
		oToggleDiv.style.marginTop = 0;
		oToggleDiv.style.top = 0;

		/* Set flag to repeat slideContent() */
		jsF.Set( "slider_is_repeat", true );

		/* Calculate element height */
		var height = oToggle.clientHeight;
		
		if (height == 0)
		{
			height = oToggle.offsetHeight;
		}
		height = height + pixels;
		if ( height > oToggleDiv.offsetHeight )
		{
			height = oToggleDiv.offsetHeight;
			jsF.Set( "slider_is_repeat", false );
		}
		if ( parseFloat(height) <= 1 )
		{
			height = 1;
			jsF.Set( "slider_is_repeat", false );
		}
		
		var y_pos = height - oToggleDiv.offsetHeight;
		if (y_pos > 0)
		{
			y_pos = 0;
		}

		/* Manual exponent */
		if ( height < 50 ){ pixels = -20; }
		if ( height < 25 ){ pixels = -10; }
		if ( height < 10 ){ pixels = -3; }
		if ( oToggleDiv.offsetHeight - height < 50 ){ pixels = 20; }
		if ( oToggleDiv.offsetHeight - height < 25 ){ pixels = 10; }
		if ( oToggleDiv.offsetHeight - height < 10 ){ pixels = 3; }

		/* 
		*/
		oToggleDiv.style.marginTop = y_pos + "px";
		oToggle.style.height = height + "px";

		if ( jsF.Get( "slider_is_repeat" ) )
		{
			/* Need to slide again */
			setTimeout('jsSlider.slideContent("' + id_target + '",' + pixels + ')', 0 );
		}
		else
		{
			/* Animation finished */
			if (height <= 1)
			{
				/* Hide DIV.slider-toggle */
				oToggle.style.display = "none";
			}
			else
			{
				oToggle.style.height = ''; 
				jsF.Set( "slider_id_active", id_target );
			}
		}
	};
}
/* */
var jsSlider = new JS_slider();
jsSlider.init();






/**
 * ----------------------------------------------
 * Counters
 * ----------------------------------------------
 */
/* */
function DOM_make_38x31(el_name, gw_ar_img)
{
	var el = document.getElementById(el_name);
	var oImg = document.createElement("img");
	oImg.width = 38;
	oImg.height = 31;
	var oA = document.createElement("a");
	oA.setAttribute("onclick", "nw(this.href);return false");
	for (var i = 0; i < gw_ar_img.length; i++)
	{
		oA = oA.cloneNode(false);
		oImg = oImg.cloneNode(false);
		oA.href = gw_ar_img[i][1];
		oImg.src = js_path_img+"/"+gw_ar_img[i][0];
		oImg.setAttribute("alt", gw_ar_img[i][2]);
		oImg.setAttribute("title", gw_ar_img[i][2]);
		oA.appendChild(oImg);
		el.appendChild(oA);
	}
};
/* */
function DOM_make_cnt(countername)
{
	var el_name = 'cntrs';
	var el = document.getElementById(el_name);
	var oImg = document.createElement('img');
	switch(countername)
	{
		case 'rax':
			oImg.src = 'http://counter.yadro.ru/hit?r'+escape(document.referrer)+((typeof(screen)=='undefined')?'':';s'+screen.width+'*'+screen.height+'*'+(screen.colorDepth?screen.colorDepth:screen.pixelDepth))+';'+Math.random();
			oImg.height = 1;
			oImg.width = 1;
		break;
	}
	el.appendChild(oImg);
};

