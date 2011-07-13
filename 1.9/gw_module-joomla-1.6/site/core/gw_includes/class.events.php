<?php

class site_events
{
	private $ar_events = array();
	/* */
	public function __construct()
	{
		
	}
	/* */
	public function register($event, $func)
	{
		$this->ar_events[$event][] = $func;
	}
	/* */
	private function onItemEdit( $s )
	{
		foreach ( $s as $id_field => &$ar_v )
		{
			$ar_v['contents_value'] = '!!'.$ar_v['contents_value'];
		}
	}
	/* */
	public function call( $event, $s )
	{
		if (isset( $this->ar_events[$event] ) )
		{
			foreach( $this->ar_events[$event] as $func )
			{
				if ( method_exists($this, $func) )
				{
					$this->$func( $s );
				}
			}
		}
		
	}
}

?>