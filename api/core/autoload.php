<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

/**
 * @param string $dir Autoload target directory
 * @param string $class Autoload target class
 *
 * @return bool
 * @internal
 */
function waggo_class_autoload( $dir, $class )
{
	$file = "{$dir}/{$class}.php";
	if ( file_exists( $file ) )
	{
		wg_log_write( WGLOG_INFO, "[[ Autoload class : {$dir}/{$class} ]]" );

		/** @noinspection PhpIncludeInspection */
		require_once( $file );

		return true;
	}

	return false;
}

/**
 * @internal
 */
spl_autoload_register
(
	function ( $class ) {
		global $WGCONF_AUTOLOAD;
		foreach ( $WGCONF_AUTOLOAD as $dir )
		{
			if ( waggo_class_autoload( $dir, $class ) )
			{
				break;
			}
		}
	}
);
