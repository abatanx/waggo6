<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

define( 'WG_CLI' , true );
error_reporting( error_reporting() & ~E_NOTICE );

if( count($argv)<=1 )
{
	die("Usage: {$argv[0]} domain[:port] [parameters...]\n");
}

list($host,$port) = explode(':', $argv[1]);

$_SERVER['SERVER_NAME'] = !empty($host) ? $host : '';
$_SERVER['SERVER_PORT'] = !empty($port) ? $port : 80;

require_once dirname(__FILE__) . '/waggo.php';
