oInfoblocks = new function()
{
	/* */
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
	
	
	/* */
	this.remove_confirm = function( id_item )
	{
		if (confirm( jsF.Get( "oTkit_1043" ) + ':\u0020' + jsF.Get( "oTkit_1051" ) ))
		{
			/* Unselect */
			var checkbox = fn_getElementById( "item-" + id_item);
			if ( checkbox && checkbox.checked !== false )
			{
				this.cnt--;
				checkbox.checked = false;
				fn_getElementById( "itemd-" + id_item ).className = "";
			}
			this.remove( id_item );
		}
	};
	/* AJAX to remove Item */
	this.remove = function(id_item)
	{
		var params = [
			"arg[sef_output]=ajax", "arg[action]=remove", "arg[target]=infoblocks", "arg[sid]="+jsF.Get("id_sess"), 
			"arg[id]="+id_item, "arg[r]="+Math.random(), jsF.Get("sef_append"), jsF.Get("sef_append_ajax")
		];
		params = params.join("&");

		var oncompletefn = function(r)
		{
			var response = r.responseText;
			if (response && response.length > 0)
			{
				if ( response == "0" )
				{
					/* No such permissions */
					alert( "No such permissions . Error code: " + response );
				}
				else if ( response == "1" )
				{
					/* */
					var tbl = fn_getElementById( "itemd-" + id_item );
					if (tbl !== false)
					{
						var tbl_remove_len = tbl.childNodes.length;
						for (var i = 0; i < tbl_remove_len; i++)
						{
							tbl.removeChild( tbl.lastChild );
						}
						tbl.parentNode.removeChild( tbl );
					}
					/* Reload the current page */
					/*
					document.location.href = document.location.href.replace("#", "");
					*/
				}
				else if (response == "2")
				{
					/* No such item */
					alert( "No such item. Error code: " + response );
				}
				else
				{
					/* Unknown error */
					alert( response );
				}
			}
		};
		new oAjax.Request( jsF.Get("path_server_dir_admin")+'/'+jsF.Get("file_index"), {method: "get", parameters: params, onComplete: oncompletefn} );
	};
};