/* */
var oGwNavbar = new function()
{
	this.sub = function( el_a )
	{
		var id = el_a.id.replace(/a-/, "");
		el_a.blur();
		this._showhide( id );
	};
	this._showhide = function( id )
	{
		var menublocks = jsF.Get( "menublocks" );
		for ( var i = 0, L = menublocks.length; i < L; i++ )
		{
			var el = fn_getElementById( "ch-" + menublocks[i] );
			var el_a = fn_getElementById( "a-" + menublocks[i] );
			if ( !el || !el_a ){ continue; }
			el.style.display = "none";
			el_a.className = "";
			if ( id == menublocks[i] )
			{
				el.style.display = "block";
				el_a.className = "on";
			}
		}
	};
};

