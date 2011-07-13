<?php

/**
 * @version		$Id$
 * @package		Filesystem
 * @copyright	© Dmitry N. Shilnikov, 2002-2011
 * @license		Commercial
 */
/**
 * Filesystem functions.
 *
 * oFile::file_get_contents()
 * oFile::file_put_contents()
 * oFile::file_remove() or oFile::rm()
 */
if ( !defined( 'IS_CLASS_FILESYSTEM' ) )
{
	define( 'IS_CLASS_FILESYSTEM', 1 );

	class SITE_oFile
	{

		private static $_ar_log = array ( );
		private static $_ar_fileinfo = array ( );
		private static $dir_root = '';


		/**
		 * Calculates size in bytes for a given files in folder structure.
		 *
		 * @param array $ar_fs Folder structure.
		 * @param string $root Root folder.
		 * @return array Hashed folder structure, array( 'pathname' => array( 'filename' => '', 'filesize' => '' ) );
		 */
		public static function file_sizes ( $ar_fs, $root = '.' )
		{
			$ar_filesizes = array ( );

			foreach ( $ar_fs as $folder => $ar_files )
			{
				foreach ( $ar_files as $filename )
				{
					$ar_filesizes[$folder][] = array (
						'filename' => $filename,
						'filesize' => filesize( $root . '/' . $folder . '/' . $filename )
					);
				}
			}
			return $ar_filesizes;
		}


		/**
		 * Calculates the md5 hash of a given files in folder structure.
		 *
		 * @param array $ar_fs Folder structure.
		 * @param string $root Root folder.
		 * @return array Hashed folder structure, array( 'pathname' => array( 'filename' => '', 'filehash' => '', 'filesize' => '' ) );
		 */
		public static function hash ( $ar_fs, $root = '.' )
		{
			$ar_hash = array ( );

			foreach ( $ar_fs as $folder => $ar_files )
			{
				foreach ( $ar_files as $filename )
				{
					$hash_file = hash_file( 'md5', $root . '/' . $folder . '/' . $filename );
					$ar_hash[$folder][] = array (
						'filename' => $filename,
						'filehash' => $hash_file,
						#'filesize' => filesize( $root.'/'.$folder.'/'.$filename )
					);
				}
			}
			return $ar_hash;
		}

		/**
		 * Resets the current folder name for directory listing
		 */
		public static function dir_reset ()
		{
			self::$dir_root = '';
		}


		/**
		 * Lists files and directories inside the specified path. Recursive.
		 *
		 * @param string $pathname Path to directory.
		 * @param array $ar_stopwords Stopwords for directory names.
		 * @return array Folder structure, array( 'pathname' => array( 'file1', 'file2') );
		 */
		public static function dir_list ( $pathname, $ar_stopwords = array ( ) )
		{
			$ar_f = array ( );

			if ( !is_dir( $pathname ) )
			{
				return $ar_f;
			}

			if ( self::$dir_root == '' )
			{
				self::$dir_root = $pathname;
			}

			if ( $dh = opendir( $pathname ) )
			{
				while ( ( $filename = readdir( $dh ) ) !== false )
				{
					if ( $filename != '.' && $filename != '..' )
					{
						$pathname_ar = str_replace( self::$dir_root, '.', $pathname );

						/* Exclude files */
						if ( !in_array( $pathname_ar, $ar_stopwords ) )
						{
							if ( is_file( $pathname . '/' . $filename ) )
							{
								/* Collect files */
								$ar_f[$pathname_ar][] = $filename;
							}
							else if ( is_dir( $pathname . '/' . $filename ) )
							{
								/* Read directory */
								$ar_f = array_merge( $ar_f, self::dir_list( $pathname . '/' . $filename, $ar_stopwords ) );
							}
						}
					}
				}
				closedir( $dh );
			}
			return $ar_f;
		}

		/**
		 * Gets file creation date
		 */
		public static function file_ctime ( $filename )
		{
			$ar_fileinfo = self::file_info( $filename );
			return isset( $ar_fileinfo['ctime'] ) ? $ar_fileinfo['ctime'] : 0;
		}

		/**
		 * Gets file modification date
		 */
		public static function file_mtime ( $filename )
		{
			$ar_fileinfo = self::file_info( $filename );
			return isset( $ar_fileinfo['mtime'] ) ? $ar_fileinfo['mtime'] : 0;
		}

		/**
		 * Gets information about a file
		 */
		public static function file_info ( $filename )
		{
			/* Correct path name */
			$filename = str_replace( '\\', '/', $filename );

			self::_log( __FUNCTION__, $filename );

			if ( isset( self::$_ar_fileinfo[$filename] ) )
			{
				return self::$_ar_fileinfo[$filename];
			}
			/* */
			if ( file_exists( $filename ) )
			{
				self::$_ar_fileinfo[$filename] = stat( $filename );
				return self::$_ar_fileinfo[$filename];
			}
			return array ( );
		}


		/**
		 * Put contents into a file. Binary- and fail-safe.
		 *
		 * 07 July 2010: fopen(), fwrite(), fclose() replaced with file_put_contents() (PHP5 only).
		 * 06 July 2010: New method for recursive mkdir (PHP5 only).
		 * 26 May 2010: Fixes for "open_basedir in effect".
		 *
		 * @param   string  $filename Full path to filename
		 * @param   string  $content File contents
		 * @param   string  $mode [ w = write a new file (default) | a = append ]
		 * @return  TRUE if success, FALSE otherwise
		 */
		public static function file_put_contents ( $filename, $content, $mode = 'w' )
		{
			clearstatcache();

			/* Correct path name */
			$filename = str_replace( '\\', '/', $filename );

			if ( file_exists( $filename ) )
			{
				self::_log( __FUNCTION__, $mode . 'b ' . $filename );

				if ( $mode == 'a' )
				{
					/* Append the contents to file */
					return file_put_contents( $filename, $content, FILE_APPEND );
				}
				else
				{
					/* Write to file */
					return file_put_contents( $filename, $content );
				}
			}
			else
			{
				self::_log( __FUNCTION__, $mode . ' ' . $filename );

				/* Create directories first */
				$dirname = dirname( $filename );

				if ( !is_dir( $dirname ) )
				{
					mkdir( $dirname, 0755, true );
					@chmod( $dirname, 0755 );
				}

				/* Nothing to write */
				if ( $content == '' )
				{
					return true;
				}

				/* Write to file */
				return file_put_contents( $filename, $content ) && @chmod( $filename, 0644 );
			}
			return true;
		}

		/**
		 * Shorthand for file_put_contents()
		 */
		public static function fwrite ( $filename, $content, $mode = 'w' )
		{
			self::file_put_contents( $filename, $content, $mode );
		}


		/**
		 * Get file contents. Binary- and fail-safe.
		 *
		 * @param   string  $filename Full path to filename
		 * @return  string  File contents
		 */
		public static function file_get_contents ( $filename )
		{
			/* Correct path name */
			$filename = str_replace( '\\', '/', $filename );

			self::_log( __FUNCTION__, $filename );

			if ( !file_exists( $filename ) )
			{
				self::_log( __FUNCTION__, 'File ' . $filename . ' does not exist.' );
				return '';
			}

			/* Read file contents */
			if ( function_exists( 'file_get_contents' ) ) /* since PHP4 CVS */
			{
				$str = file_get_contents( $filename );
			}
			else
			{
				$str = implode( '', file( $filename ) );
			}

			/* File is empty */
			if ( $str == '' )
			{
				self::_log( __FUNCTION__, 'File ' . $filename . ' is empty.' );
				return '';
			}

			/* Remove slashes, 23 March 2002 */
			if ( function_exists( 'get_magic_quotes_runtime' ) && @get_magic_quotes_runtime() )
			{
				$str = stripslashes( $str );
			}

			return $str;
		}


		/**
		 * Removes a file or an empty directory from disk. Not recursive.
		 *
		 * @param	string	$filename Full path to filename.
		 * @return	TRUE if success, FALSE otherwise.
		 */
		public static function file_remove ( $filename )
		{
			/* Correct path name */
			$filename = str_replace( '\\', '/', $filename );

			if ( file_exists( $filename ) && is_file( $filename ) && unlink( $filename ) )
			{
				self::_log( __FUNCTION__, 'file ' . $filename );
				return true;
			}
			else if ( file_exists( $filename ) && is_dir( $filename ) && @rmdir( $filename ) )
			{
				self::_log( __FUNCTION__, 'dir ' . $filename );
				return true;
			}
			return false;
		}

		/**
		 * Shorthand for file_remove()
		 */
		public static function rm ( $filename )
		{
			self::file_remove( $filename );
		}

		/**
		 * Rename file
		 */
		public static function file_rename ( $src, $dst )
		{
			$src = str_replace( '\\', '/', $src );
			$dst = str_replace( '\\', '/', $dst );

			$src_log = implode( '/', array_slice( explode( '/', $src ), -2, 2 ) );
			$dst_log = implode( '/', array_slice( explode( '/', $dst ), -2, 2 ) );

			/* Prepare folders */
			if ( !file_exists( $dst ) )
			{
				self::file_put_contents( $dst, '' );
			}

			if ( rename( $src, $dst ) )
			{
				self::_log( __FUNCTION__, $src_log . ' => ' . $dst_log );
			}
			else
			{
				self::_log( __FUNCTION__, 'Error renaming ' . $src_log . ' => ' . $dst_log );
			}
		}

		/**
		 * Copy file
		 */
		public static function file_copy ( $src, $dst )
		{
			$src = str_replace( '\\', '/', $src );
			$dst = str_replace( '\\', '/', $dst );

			$src_log = implode( '/', array_slice( explode( '/', $src ), -2, 2 ) );
			$dst_log = implode( '/', array_slice( explode( '/', $dst ), -2, 2 ) );

			/* Prepare folders */
			if ( !file_exists( $dst ) )
			{
				self::file_put_contents( $dst, '' );
			}

			if ( copy( $src, $dst ) )
			{
				self::_log( __FUNCTION__, $src_log . ' => ' . $dst_log );
			}
			else
			{
				self::_log( __FUNCTION__, 'Error copying ' . $src_log . ' => ' . $dst_log );
			}
		}

		/**
		 * Log results
		 */
		private static function _log ( $identifier, $str )
		{
			$identifier = ( string ) $identifier;
			/* Keep the last folder name */
			self::$_ar_log[$identifier][] = implode( '/', array_slice( explode( '/', $str ), -2, 2 ) );
		}

		/**
		 * Report results
		 */
		public static function html ()
		{
			$s[] = '<ol title="oFile">';
			foreach ( self::$_ar_log as $identifier => &$str )
			{
				$s[] = "\n<li>";
				$s[] = '<strong>' . $identifier . '</strong><br />• ';
				$s[] = implode( '<br />• ', $str );
				$s[] = '</li>';
			}
			unset( $str );
			$s[] = '</ol>';
			return implode( ' ', $s );
		}

	}

}
?>