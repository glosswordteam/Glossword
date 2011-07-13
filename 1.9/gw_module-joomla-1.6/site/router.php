<?php
/**
 * @version		$Id$
 * @package		Glossword 1.9
 * @copyright	 Dmitry N. Shilnikov, 2002-2010
 * @license		GNU/GPL, see http://code.google.com/p/glossword/
 */
/* */
function GlosswordBuildRoute(&$query)
{
	$ar_segments = array();
	
	if ( isset( $query['view'] ) )
	{
		unset( $query['view'] );
	}
	if ( isset( $query['area'] ) )
	{
		$ar_segments[] = $query['area'];
		unset( $query['area'] );
	}
	elseif ( isset( $query['arg']['area'] ) )
	{
		$ar_segments[] = $query['arg']['area'];
		unset( $query['arg'] );
	}
	if ( isset( $query['alias'] ) )
	{
		unset( $query['alias'] );
	}
	$ar_segments[] = '/i';
 
	return $ar_segments;
}
/* */
function GlosswordParseRoute( $ar_segments )
{
	
	$vars = array();
	$vars['view'] = 'default';
	$vars['option'] = 'com_glossword';
	return $vars;
}
?>
