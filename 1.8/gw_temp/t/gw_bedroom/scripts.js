var agent = navigator.userAgent.toLowerCase();
/* new window replacement */
function nw(href) {
	window.open(href);
}
var is_regexp = (window.RegExp) ? true : false;
/* select DOM model */
var gwDOMtype = "";
if (document.getElementById) {
	gwDOMtype = "std";
}
else if (document.all) {
	gwDOMtype = "ie4";
}
/* objects container */
var gw_o = new Array();
/* document.getElementById replacement */
function gw_getElementById(el_name) {
	if (typeof(gw_o[el_name]) == "undefined") {
		switch (gwDOMtype) {
			case "std": {
				gw_o[el_name] = (document.getElementById(el_name) == null) ? false : document.getElementById(el_name);
			}
			break;
			case "ie4": {
				gw_o[el_name] = (document.all[el_name] == null) ? false : document.all[el_name];
			}
			break;
		}
	}
	return gw_o[el_name];
}
/* new window replacement */
function nw(href) {
	window.open(href);
}
/* */
function gwshuffle(a){return a.sort(new Function("return 0.5-Math.random()"));}
/* */
function DOM_make_38x31(el_name, gw_ar_img) {
	el = document.getElementById(el_name);
	oImg = document.createElement("img");
	oImg.width = 38;
	oImg.height = 31;
	oA = document.createElement("a");
	oA.setAttribute("onclick", "nw(this.href);return false");
	for (i=0; i < gw_ar_img.length; i++) {
		oA = oA.cloneNode(false);
		oImg = oImg.cloneNode(false);
		oA.href = gw_ar_img[i][1];
		oImg.src = js_path_img+"/"+gw_ar_img[i][0];
		oImg.setAttribute("alt", gw_ar_img[i][2]);
		oImg.setAttribute("title", gw_ar_img[i][2]);
		oA.appendChild(oImg);
		el.appendChild(oA);
	}
}
/* */
function DOM_make_cnt(countername) {
	el_name = 'cntrs';
	el = document.getElementById(el_name);
	oImg = document.createElement('img');
	switch(countername) {
		case 'rax':
		oImg.src = 'http://counter.yadro.ru/hit?r'+escape(document.referrer)+((typeof(screen)=='undefined')?'':';s'+screen.width+'*'+screen.height+'*'+(screen.colorDepth?screen.colorDepth:screen.pixelDepth))+';'+Math.random();
		oImg.height = 1;
		oImg.width = 1;
		break;
	}
	el.appendChild(oImg);
}