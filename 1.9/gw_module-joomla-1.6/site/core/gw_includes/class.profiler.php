<?php

/**
 * Profiler.
 */
if ( !defined( 'IS_CLASS_PROFILER' ) )
{
	define( 'IS_CLASS_PROFILER', 1 );

	class oProfiler
	{

		private static $_ar;

		/* Shorthand for start() */


		public static function _ ( $identifier = '' )
		{
			self::start( $identifier );
		}

		/* */


		public static function start ( $identifier = '' )
		{
			$mtime = explode( ' ', microtime() );
			$v = ( float ) $mtime[1] + ( float ) $mtime[0];

			$identifier = ( string ) $identifier;

			self::$_ar[$identifier] = array (
				'time' => $v,
				'mem' => memory_get_usage()
			);
		}

		/* Shorthand for end() */


		public static function __ ( $identifier = '' )
		{
			self::end( $identifier );
		}

		/* */


		public static function end ( $identifier = '' )
		{
			$mtime = explode( ' ', microtime() );

			$v = ( float ) $mtime[1] + ( float ) $mtime[0];

			$identifier = ( string ) $identifier;

			if ( isset( self::$_ar[$identifier] ) )
			{
				self::$_ar[$identifier] = array (
					'time' => sprintf( "%1.5f", ( $v - self::$_ar[$identifier]['time'] ) ),
					'mem' => ( memory_get_usage() - self::$_ar[$identifier]['mem'] )
				);
			}
		}

		/* Shorthand for get() */


		public static function ___ ()
		{
			return self::get();
		}

		/* */


		public static function get ()
		{
			return self::$_ar;
		}

		/* */


		public static function pre ()
		{
			print '<pre title="oProfiler" style="margin:0;color:#000;background:#FFF;text-align:left;font:9pt consolas,monospace">';
			foreach ( self::$_ar as $identifier => &$ar )
			{
				print "\n";
				printf( "%9s", $ar['time'] );
				print " sec";
				printf( "%10s", number_format( $ar['mem'] ) );
				print " bytes  ";
				print $identifier;
			}
			unset( $ar );
			print '</pre>';
		}

		/* */


		public static function html ()
		{
			$s[] = '<ol title="oProfiler">';
			foreach ( self::$_ar as $identifier => &$ar )
			{
				$s[] = "\n<li>";
				$s[] = '<strong>' . $ar['time'] . '</strong> • ';
				$s[] = $identifier;
				$s[] = ' • ' . number_format( $ar['mem'] ) . ' bytes';
				$s[] = '</li>';
			}
			unset( $ar );
			$s[] = '</ol>';
			return implode( ' ', $s );
		}

	}

}
?>