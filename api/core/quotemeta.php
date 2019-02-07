<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

if( get_magic_quotes_gpc() )
{
	function stripslashes_deep($value)
	{
		$value = is_array($value)?array_map('stripslashes_deep',$value):stripslashes($value);
		return $value;
	}
	$_POST   = array_map('stripslashes_deep', $_POST);
	$_GET    = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}
