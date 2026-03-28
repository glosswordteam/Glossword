var agent = navigator.userAgent.toLowerCase();
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