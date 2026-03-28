/* Visual theme */
function gw_visual_theme(){
	var _this = this;
	this.el = null;
	this.init = function()
	{
		/* Placing HTML-forms */
		gw_getElementById('place-il').innerHTML = gwJS.Get('str_select_il');
		gw_getElementById('place-visualtheme').innerHTML = gwJS.Get('str_select_visualtheme');
		/* Making buttons look like "Cancel" */
		gw_getElementById('ok-il').className = 
		gw_getElementById('ok-visualtheme').className = "submitcancel";
	}
}
var gwVT = new gw_visual_theme();