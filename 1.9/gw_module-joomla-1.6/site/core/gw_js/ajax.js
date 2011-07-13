Function.prototype.bind = function(object) 
{
	var __method = this;
	return function() { __method.apply(object, arguments); }
};
if ( !Array.prototype.push ) 
{
	Array.prototype.push = function()
	{
		var startLength = this.length;
		for (var i = 0; i < arguments.length; i++) { this[startLength + i] = arguments[i]; }
		return this.length;
	}
};
/* */
oAjax = new function()
{
	this.empty_function = function(){};
	/* */
	this.trythese = function()
	{
		var r;
		for ( var i = 0; i < arguments.length; i++ )
		{
			var lambda = arguments[i];
			try {
				r = lambda(); break; 
			} catch(e){};
		}
		return r;
	};
	/* */
	this.get_transport = function()
	{
		return oAjax.trythese(
			function(){return new ActiveXObject('Msxml2.XMLHTTP')},
			function(){return new ActiveXObject('Microsoft.XMLHTTP')},
			function(){return new XMLHttpRequest()}
		)||false;
	};
	this.set_options = function( destination, source )
	{
		for (property in source) {
			destination[property] = source[property];
		}
		return destination;
	};
	/* */
	this.Request = function( url, new_options )
	{
		this.events = ['Uninitialized','Loading','Loaded','Interactive','Complete'];
		this.options = { method:'post', asynchronous: true, parameters: '' };
		this.transport = oAjax.get_transport();
		this.request = function( url )
		{
			var parameters = this.options.parameters || '';
			if ( parameters.length > 0 ){ parameters += '&_='; }
			try {
				if ( this.options.method == 'get' )
				{
					url += '?' + parameters;
				}
				this.transport.open( this.options.method, url, this.options.asynchronous );
				if ( this.options.asynchronous )
				{
					this.transport.onreadystatechange = this.onStateChange.bind( this );
					setTimeout( (function(){ this.respondToReadyState(1)}).bind( this ), 10 );
				}
				this.setRequestHeaders();
				this.body = ( this.options.method == 'post' ? (this.options.postBody || parameters) : null );
				this.transport.send( this.body );
			} catch(e){};
		};
		this.onStateChange = function()
		{
			var readyState = this.transport.readyState;
			if ( readyState != 1 )
			{
				this.respondToReadyState( this.transport.readyState );
			}
		};
		this.respondToReadyState = function(readyState)
		{
			var event = this.events[readyState];
			if ( event == 'Complete')
			{
				(this.options['on'+this.transport.status]
				|| this.options['on'+(this.responseIsSuccess() ? 'Success' : 'Failure')]
				|| oAjax.empty_function)(this.transport);
			}
			( this.options['on'+event] || oAjax.empty_function )( this.transport );
			if ( event == 'Complete')
			{
				this.transport.onreadystatechange = oAjax.empty_function;
			}
		};
		this.responseIsSuccess = function()
		{
			return this.transport.status == undefined
				|| this.transport.status == 0 
				|| (this.transport.status >= 200 && this.transport.status < 300);
		};
		this.responseIsFailure = function()
		{
			return !this.responseIsSuccess();
		};
		this.setRequestHeaders = function()
		{
			var requestHeaders = ['X-Requested-With', 'XMLHttpRequest'];
			if ( this.options.method == 'post' ) 
			{
				requestHeaders.push( 'Content-type', 'application/x-www-form-urlencoded' );
				if ( this.transport.overrideMimeType ){ requestHeaders.push( 'Connection', 'close' ); }
			}
			if ( this.options.requestHeaders )
			{
				requestHeaders.push.apply( requestHeaders, this.options.requestHeaders );
			}
			for ( var i = 0; i < requestHeaders.length; i += 2) 
			{
				this.transport.setRequestHeader( requestHeaders[i], requestHeaders[i+1] );
			}
		};
		oAjax.set_options( this.options, new_options );
		this.request( url );
	};
};



