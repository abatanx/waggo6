<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

define('WG_NAME'		,	"waggo" );
define('WG_VERSION'		,	"6.00" );
define('WG_COPYRIGHT'	,	"Copyright (c) 2013-2019 project waggo." );

function wgdie($msg)
{
	if( !defined('WG_CLI') )
	{
		include __DIR__ . '/api/boot/wgdie.php';
		die();
	}
	else
	{
		die("{$msg}\n");
	}
}

$wconfport = $_SERVER["SERVER_PORT"] != 80 ? ".${_SERVER['SERVER_PORT']}" : "";
$wconffile = __DIR__ . "/../config/waggo.{$_SERVER['SERVER_NAME']}{$wconfport}.php";
if(!file_exists($wconffile)) wgdie("'{$wconffile}' doesn't exist.\n");
else require_once($wconffile);

require_once(dirname(__FILE__)."/api/core/lib.php");

wg_log("++ ".WG_NAME." ".WG_VERSION);
wg_log("** PHP version     = [".phpversion()."]");
wg_log("** Server          = [".php_uname("a")."]");
wg_log("** REQUEST_URI     = [{$_SERVER['REQUEST_URI']}]");
wg_log("** REQUEST_METHOD  = [{$_SERVER['REQUEST_METHOD']}] {$_SERVER['SERVER_PROTOCOL']}");
wg_log("** HTTP_USER_AGENT = [{$_SERVER['HTTP_USER_AGENT']}]");
wg_log("** REMOTE          = [{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}]");
if(isset($argv) && is_array($argv)) wg_log("** ARGV            = ".implode(" ",$argv));

wg_log("[[ Loaded   framework config : {$wconffile}) ]]");

require_once(dirname(__FILE__)."/api/http/http.php");
if(wg_is_mobile())
{
	wg_log("[[ USER DEVICE IS MOBILE ]]");
	require_once(dirname(__FILE__)."/api/mobile/mobile.php");
	require_once(dirname(__FILE__)."/api/mobile/device.php");
}

foreach($_GET  as $k=>$v) wg_log("## GET  ".sprintf("%-10s = %s","[$k]","[$v]"));
foreach($_POST as $k=>$v) wg_log("## POST ".sprintf("%-10s = %s","[$k]","[$v]"));

require_once(dirname(__FILE__)."/api/core/autoload.php");
require_once(dirname(__FILE__)."/api/core/quotemeta.php");
require_once(dirname(__FILE__)."/api/core/check.php");
require_once(dirname(__FILE__)."/api/core/secure.php");
require_once(dirname(__FILE__)."/api/core/shell.php");
require_once(dirname(__FILE__)."/api/core/crypt.php");
require_once(dirname(__FILE__)."/api/resources/id.php");
require_once(dirname(__FILE__)."/api/user/users.php");
require_once(dirname(__FILE__)."/api/session/session.php");
require_once(dirname(__FILE__)."/api/html/wiki.php");
require_once(dirname(__FILE__)."/api/html/canvas.php");
require_once(dirname(__FILE__)."/api/html/color.php");
require_once(dirname(__FILE__)."/api/mail/mail.php");
require_once(dirname(__FILE__)."/api/dbms/interface.php");
require_once(dirname(__FILE__)."/api/datetime/datetime.php");

wg_log("[[ Loaded   framework APIs ]]");

require_once(dirname(__FILE__)."/../config.php");

wg_log("[[ Loaded application APIs ]]");
