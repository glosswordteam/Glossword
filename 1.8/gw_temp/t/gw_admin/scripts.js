/* Collapsed objects */
var ar_coll_obj = new Array();
function collapse_all() {
	for (i = 0, length1 = ar_coll_obj.length; i < length1; i++) {
		gw_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
		gw_getElementById("ci-" + ar_coll_obj[i]).src = path_img + "collapse_off.png";
	}
}
function uncollapse_all(is_save) {
	/* saves state */
	if (is_save) {
		is_cookie = fetch_cookie("gw_collapse");
		/* do not save state if already set */
		if (is_cookie != null) {
			/* check states */
			collapsed = is_cookie.split("\n");
			/* array elements in Javascript are stored in random order */
			for (i = 0, length1 = ar_coll_obj.length; i < length1; i++) {
				for (i2 = 0, length2 = collapsed.length; i2 < length2; i2++) {
					if (collapsed[i2] == ar_coll_obj[i]) {
						gw_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
						gw_getElementById("ci-" + ar_coll_obj[i]).src = path_img + "collapse_off.png";
					}
				}
			}
		}
		else
		{
			for (i = 0, length1 = ar_coll_obj.length; i < length1; i++) {
				gw_getElementById("co-" + ar_coll_obj[i]).style.display = "none";
				toggle_collapse(ar_coll_obj[i]);
			}
		}
	}
	else
	{
		/* just open all collapsed objects, unsaved state */
		for (i = 0, length1 = ar_coll_obj.length; i < length1; i++) {
			gw_getElementById("co-" + ar_coll_obj[i]).style.display = "block";
			gw_getElementById("ci-" + ar_coll_obj[i]).src = path_img + "collapse_on.png";
		}
	}
}
function toggle_collapse(id_obj)
{
	if (!is_regexp) {
		return false;
	}
	obj = gw_getElementById("co-" + id_obj);
	img = gw_getElementById("ci-" + id_obj);
	if (!obj) {
		if (img) {
			img.style.display = "none";
		}
		return false;
	}
	if (obj.style.display == "none") {
		obj.style.display = "";
		save_collapsed(id_obj, false);
		if (img) {
			img_re = new RegExp("_off\\.png$");
			img.src = img.src.replace(img_re, '_on.png');
		}
	}
	else {
		obj.style.display = "none";
		save_collapsed(id_obj, true);
		if (img) {
			img_re = new RegExp("\\_on.png$");
			img.src = img.src.replace(img_re, '_off.png');
		}
	}
	return false;
}
function save_collapsed(id_obj, addcollapsed)
{
	var collapsed = fetch_cookie("gw_collapse");
	var tmp = new Array();
	if (collapsed != null) {
		collapsed = collapsed.split("\n");
		for (i2 = 0, length2 = collapsed.length; i2 < length2; i2++) {
			if (collapsed[i2] != id_obj && collapsed[i2] != "") {
				tmp[tmp.length] = collapsed[i2];
			}
		}
	}
	if (addcollapsed) {
		tmp[tmp.length] = id_obj;
	}
	expires = new Date();
	expires.setTime(expires.getTime() + (1000 * 86400 * 365));
	set_cookie("gw_collapse", tmp.join("\n"), expires);
}




/* User administration */
function setCheckboxesUser(is_check)
{
	items = document.forms["vbform"]["arPost[is_permissions][]"];
	if (items.length == undefined)
	{
		len = 1;
		items.checked = is_check;
	}
	else
	{
		len = items.length;
		for (i = 0, s = len; i < s; i++) {
			items[i].checked = is_check;
		}
	}
	return false;
}

function setCheckboxesDict(is_check, ar)
{
	ardict = ar.split(",");
	for (i = 0; i < ardict.length; i++) {
		gw_getElementById('arPost_dictionaries_' + ardict[i] + '_').checked = is_check;
	}
	return false;
}

function setCheckboxesPerm(id)
{
	ar = new Array();
	ar[1] = new Array(0,1,2,9);
	ar[2] = new Array(0,1,2,7,9);
	ar[3] = new Array(0,1,2,4);
	ar[4] = new Array(0,1,2,4,5,6,7,8,9,10,11,14);
	for (i = 0; i < arPerm.length; i++)
	{
		if (ar[id] == undefined)
		{
			gw_getElementById('arPost_' + arPerm[i] + '_').checked = false;
		}
		else
		{
			gw_getElementById('arPost_' + arPerm[i] + '_').checked = false;
			for (i2 = 0; i2 < ar[id].length; i2++)
			{
				gw_getElementById('arPost_' + arPerm[ar[id][i2]] + '_').checked = true;
			}
		}
	}
	return false;
}

function is_allow_checkbox(e, id)
{
	if (gw_getElementById(id).value == '')
	{
		e.checked = false;
	}
}


/* Search results */
var ar_checked_terms = new Array();

function term_selected(id)
{
	ar_checked_terms[id] = gw_getElementById('id-term-' + id).checked ? true : false;
	cnt = 0;
	for (i = 0, s = ar_checked_terms.length; i < s; i++)
	{
		if (ar_checked_terms[i] == true)
		{
			cnt++;
		}
	}
	gw_getElementById('checked').innerHTML = cnt;
	return false;
}
/* check/uncheck all */
function term_check(is_check)
{
	items = document.forms["term-browse"]["arPost[ar_id][]"];
	if (items.length == undefined)
	{
		len = 1;
		items.checked = is_check;
	}
	else
	{
		len = items.length;
		for (i = 0, s = len; i < s; i++) {
			items[i].checked = is_check;
			ar_checked_terms[items[i].value] = is_check;
		}
	}
	gw_getElementById('checked').innerHTML = is_check ? len : 0;
	return false;
}

function term_alert_checking(alltermchecked, msg) {
	alltermchecked = gw_getElementById(alltermchecked);
	if (alltermchecked.checked == true)
	{
		alltermchecked.checked = window.confirm(msg);
	}
	return false;
}
