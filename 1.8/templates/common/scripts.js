var agent = navigator.userAgent.toLowerCase();
var is_regexp = (window.RegExp) ? true : false;
var is_Safari = (agent.indexOf('webkit') != -1);
/* select DOM model */
var gwDOMtype = "";
if (document.getElementById) {
	gwDOMtype = "std";
}
else if (document.all) {
	gwDOMtype = "ie4";
}
/* document.getElementById replacement */
function gw_getElementById(el_name) {
	switch (gwDOMtype) {
		case "std": {
			return (document.getElementById(el_name) == null) ? false : document.getElementById(el_name);
		}
		break;
		case "ie4": {
			return (document.all[el_name] == null) ? false : document.all[el_name];
		}
		break;
	}
}
/* new window replacement */
function nw(href) {
	window.open(href);
}

/* Cookies */
function set_cookie(name, value, expires) {
	if (!expires) {
		expires = new Date();
	}
	document.cookie = name + "=" + escape(value) + "; expires=" + expires.toGMTString() +  "; path=/";
}
function fetch_cookie(name) {
	cookie_name = name + "=";
	cookie_length = document.cookie.length;
	cookie_begin = 0;
	while (cookie_begin < cookie_length) {
		value_begin = cookie_begin + cookie_name.length;
		if (document.cookie.substring(cookie_begin, value_begin) == cookie_name) {
			var value_end = document.cookie.indexOf (";", value_begin);
			if (value_end == -1) {
				value_end = cookie_length;
			}
			return unescape(document.cookie.substring(value_begin, value_end));
		}
		cookie_begin = document.cookie.indexOf(" ", cookie_begin) + 1;
		if (cookie_begin == 0) {
			break;
		}
	}
	return null;
}
function delete_cookie(name) {
	var expireNow = new Date();
	document.cookie = name + "=" + "; expires=Thu, 01-Jan-70 00:00:01 GMT" +  "; path=/";
}


/* http://developer.mozilla.org/en/docs/ */
var jsUtils =
{
	arEvents: Array(),

	addEvent: function(el, evname, func, capture)
	{
		if(el.attachEvent){el.attachEvent("on" + evname, func);}
		else if(el.addEventListener){el.addEventListener(evname, func, false);}
		else {el["on" + evname] = func;}
		this.arEvents[this.arEvents.length] = {'element': el, 'event': evname, 'fn': func};
	},
	
	removeEvent: function(el, evname, func)
	{
		if(el.detachEvent) // IE
			el.detachEvent("on" + evname, func);
		else if(el.removeEventListener) // Gecko / W3C
			el.removeEventListener(evname, func, false);
		else
			el["on" + evname] = null;
	},

	removeAllEvents: function(el)
	{
		for(var i in this.arEvents)
		{
			if(this.arEvents[i] && (el==false || el==this.arEvents[i].element))
			{
				jsUtils.removeEvent(this.arEvents[i].element, this.arEvents[i].event, this.arEvents[i].fn);
				this.arEvents[i] = null;
			}
		}
		if(el==false)
			this.arEvents.length = 0;
	},

	AlignToPos: function(pos, w, h)
	{
		var x = pos["left"], y = pos["bottom"];
		var body = document.body;
		if ((body.clientWidth + body.scrollLeft) - (pos["left"] + w) < 0)
		{
			if (pos["right"] - w >= 0 ){ x = pos["right"] - w; }
			else { x = body.scrollLeft; }
		}
		if ((body.clientHeight + body.scrollTop) - (pos["bottom"] + h) < 0)
		{
			if (pos["top"] - h >= 0) { y = pos["top"] - h; }
			else { y = body.scrollTop; }
		}
		return {'left':x, 'top':y};
	},
	  
	GetRealPos: function(el)
	{
		if(!el || !el.offsetParent)
			return false;
		var res = Array();
		res["left"] = el.offsetLeft;
		res["top"] = el.offsetTop;
		var objParent = el.offsetParent;
		while(objParent && objParent.tagName != "BODY")
		{
			res["left"] += objParent.offsetLeft;
			res["top"] += objParent.offsetTop;
			objParent = objParent.offsetParent;
		}
		res["right"]=res["left"] + el.offsetWidth;
		res["bottom"]=res["top"] + el.offsetHeight;
		return res;
	}
	
}






/* Javascript functions */
function JSfunc()
{
	var _this = this;
	this.el = null;
	this.vars = new Array();
	/* Put a variable into the object*/
	this.Put = function(k, v)
	{
		this.vars[k] = v;
	}
	/* Get a variable from the object*/
	this.Get = function(k)
	{
		return (this.vars[k] == "undefined") ? k : this.vars[k];
	}
	/* Used for alpha-numeric fields &#160; required */
	this.strReplace0z = function(el)
	{
		if (!el) { return; }
		el.value = this.str0z(el.value);
		el.value = el.value.toLowerCase();
		el.value = el.value.substr(0, 255);
	}
	/* Making transliteration! */
	this.strTranslit = function(el)
	{
		A = new Array();
		A["Ё"]="YO";A["Й"]="J";A["Ц"]="TS";A["У"]="U";A["К"]="K";A["Е"]="E";A["Н"]="N";A["Г"]="G";A["Ш"]="SH";A["Щ"]="SCH";A["З"]="Z";A["Х"]="H";A["Ъ"]="'";
		A["ё"]="yo";A["й"]="j";A["ц"]="ts";A["у"]="u";A["к"]="k";A["е"]="e";A["н"]="n";A["г"]="g";A["ш"]="sh";A["щ"]="sch";A["з"]="z";A["х"]="h";A["ъ"]="'";
		A["Ф"]="F";A["Ы"]="Y";A["В"]="V";A["А"]="A";A["П"]="P";A["Р"]="R";A["О"]="O";A["Л"]="L";A["Д"]="D";A["Ж"]="ZH";A["Э"]="E";
		A["ф"]="f";A["ы"]="y";A["в"]="v";A["а"]="a";A["п"]="p";A["р"]="r";A["о"]="o";A["л"]="l";A["д"]="d";A["ж"]="zh";A["э"]="e";
		A["Я"]="YA";A["Ч"]="CH";A["С"]="S";A["М"]="M";A["И"]="I";A["Т"]="T";A["Ь"]="'";A["Б"]="B";A["Ю"]="YU";
		A["я"]="ya";A["ч"]="ch";A["с"]="s";A["м"]="m";A["и"]="i";A["т"]="t";A["ь"]="'";A["б"]="b";A["ю"]="yu";
		el.value = el.value.replace(/([\u0410-\u0451])/g,
			function (str,p1,offset,s) {
				if (A[str] != 'undefined'){return A[str];}
			}
		);
	}
	/* Converts some diacritics */
	this.strUmlauts = function(el)
	{
		A = new Array();
		A["À"]="A";A["Á"]="A";A["Â"]="A";A["Ã"]="A";A["Ä"]="A";A["Å"]="A";A["Æ"]="AE";A["Ç"]="C";A["È"]="E";A["É"]="E";A["Ê"]="E";A["Ë"]="E";A["Ì"]="I";A["Í"]="I";A["Î"]="I";A["Ï"]="I";A["Ð"]="E";A["Ñ"]="N";A["Ò"]="O";A["Ó"]="O";A["Ô"]="O";A["Õ"]="O";A["Ö"]="O";A["Ø"]="O";A["Ù"]="U";A["Ú"]="U";A["Û"]="U";A["Ü"]="U";A["Ý"]="Y";A["Þ"]="T";A["Ÿ"]="Y";
		A["à"]="a";A["á"]="a";A["â"]="a";A["ã"]="a";A["ä"]="a";A["å"]="a";A["æ"]="ae";A["ç"]="c";A["è"]="e";A["é"]="e";A["ê"]="e";A["ë"]="e";A["ì"]="i";A["í"]="i";A["î"]="i";A["ï"]="i";A["ð"]="e";A["ñ"]="n";A["ò"]="o";A["ó"]="o";A["ô"]="o";A["õ"]="o";A["ö"]="o";A["ø"]="o";A["ù"]="u";A["ú"]="u";A["û"]="u";A["ü"]="u";A["ý"]="y";A["þ"]="t";A["ÿ"]="y";
		el.value = el.value.replace(/([\u00c0-\u011f])/g,
			function (str,p1,offset,s) {
				if (A[str] != 'undefined'){return A[str];}
			}
		);
	}
	/* Normalizes a string, éю => eyu */
	this.strNormalize = function(el)
	{
		if (!el) { return; }
		this.strTranslit(el);
		this.strUmlauts(el);
		this.strReplace0z(el);
	}
	/* Used in <span> for previewing alphanumerical fields, DOM. &#160; required */
	this.preview0z = function(target_id, el)
	{
		if (!el) { return; }
		el.value = this.str0z(el.value);
		target_el = gw_getElementById(target_id);
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode(el.value));
	}
	/* Keeps alphanumerical characters only */
	this.str0z = function(s)
	{
		re = /[^0-9A-Za-z_\.]+/g;
		return s.replace(re, "-");
	}
	/* Trims empty spaces */
	this.strTrim = function(s)
	{
		return s.replace(/^\s+/, "").replace(/\s+$/, "");
	}
	/* Virtual keyboard */
	this.showKbd = function (id_form, arg)
	{
		_this.el_kbd = gw_getElementById("gwkbd");
		_this.el_target = gw_getElementById('gwq');
		_this.el_kbd.className = "gwkeyboard";
		_this.el_kbd.setAttribute("cellspacing", "0");
		var pos = jsUtils.GetRealPos(_this.el_target);
		/* Clear kbd items */
		while (_this.el_kbd.rows.length>0){ _this.el_kbd.deleteRow(0); }
		/* Start a new row */
		var row = _this.el_kbd.insertRow(-1);

		var a = document.createElement("a");
		var arletters = arguments[1];
		var cnt_letters = 0;
		for (var i = 0; i < arletters.length; i++)
		{
			var cell = row.insertCell(-1);
			a = a.cloneNode(false);
			a.href = "javascript:gwJS.letter('" + id_form + "','"+arletters[i]+"')";
			a.appendChild(document.createTextNode(arletters[i]));
			cell.appendChild(a);
			cnt_letters++;
			if (cnt_letters == 10)
			{
				cnt_letters = 0;
				var row = _this.el_kbd.insertRow(-1);
			}
		}
		/* Place kbd under called element */
		_this.el_kbd.style.position = "absolute";
		_this.el_kbd.style.zIndex = 1000;
		_this.el_kbd.style.opacity = 1;
		_this.el_kbd.style.visibility = 'visible';
		/* Correct aligment */
		var pos = jsUtils.AlignToPos(pos, _this.el_kbd.offsetWidth, _this.el_kbd.offsetHeight);
		_this.el_kbd.style.left = pos["left"] + 'px';
		_this.el_kbd.style.top = pos["top"] + 'px';
		/* Move kbd onResize */
		setTimeout(function(){jsUtils.addEvent(window, "resize", _this.menuUpdateXY);}, 10);
		/* Close kbd on clicking on empty area */
		setTimeout(function(){jsUtils.addEvent(document, "click", _this.menuIsOver)}, 20);
		/* Close kbd by presseng Esc button*/
		jsUtils.addEvent(document, "keypress", _this.menuKeyPress);
		return false;
	}
	this.letter = function (id_form, text)
	{
		gw_getElementById(id_form).gwq.value += text;
	}
	/* */
	this.menuIsOver = function(e)
	{
		var x = e.clientX + document.body.scrollLeft;
		var y = e.clientY + document.body.scrollTop;
		var pos = jsUtils.GetRealPos(_this.el_kbd);
		if (x >= pos["left"] && x <= pos["right"] && y >= pos["top"] && y <= pos["bottom"])
		{
			return;
		}
		/* hide menu */
		_this.menuHide();
	}
	this.menuKeyPress = function(e)
	{
		if (!e) {e = window.event;}
		if (!e) {return;}
		if (e.keyCode == 27){_this.menuHide();}
	}
	/* Hide pop-up menu, remove events */
	this.menuHide = function()
	{
		setTimeout(function(){_this.el_kbd.style.opacity=0.7}, 20);
		setTimeout(function(){_this.el_kbd.style.opacity=0.5}, 40);
		setTimeout(function(){_this.el_kbd.style.opacity=0.3}, 60);
		setTimeout(function(){_this.el_kbd.style.visibility='hidden'}, 100);
		setTimeout(function(){jsUtils.removeEvent(window, "resize", _this.menuUpdateXY)}, 10);
		setTimeout(function(){jsUtils.removeEvent(document, "click", _this.menuIsOver)}, 20);
	}
	/* Update menu position, onResize */
	this.menuUpdateXY = function()
	{
		var pos = jsUtils.GetRealPos(_this.el_target);
		var pos = jsUtils.AlignToPos(pos, _this.el_kbd.offsetWidth, _this.el_kbd.offsetHeight);
		_this.el_kbd.style.left = pos["left"] + 'px';
		_this.el_kbd.style.top = pos["top"] + 'px';
	}
	this.FXfadeOpac = function(id)
	{
		int_steps = 10;
		ms_step = 100;
		ms = 4000;

		opacity_step = 1 / int_steps;
		opacity = 1.1;
		for (i = 1; i <= int_steps; i++)
		{
			ms += ms_step;
			opacity -= opacity_step;
			setTimeout("gwJS.FXfadeOpacSet('"+id+"','"+opacity+"')", ms);
		}
		setTimeout(function(){ gw_getElementById(id).style.display='none'; }, ms);
	}
	this.FXfadeOpacSet = function (id, opacity)
	{
		gw_getElementById(id).style.opacity = opacity;
	}
}
var gwJS = new JSfunc();

/* Visual theme object */
function gw_visual_theme(){
	var _this = this;
	this.el = null;
	this.init = function()
	{
		/* */
	}
}
var gwVT = new gw_visual_theme();