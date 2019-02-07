<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function wg_mobile_inenc($value)
{
	$ie = mb_internal_encoding();
	$value = is_array($value)?array_map('wg_mobile_inenc',$value):stripslashes($value);
	return mb_convert_encoding($value,$ie,"SJIS");
}

function wg_mobile_input_encoding_filter()
{
	$_POST   = array_map('wg_mobile_inenc', $_POST);
	$_GET    = array_map('wg_mobile_inenc', $_GET);
	$_COOKIE = array_map('wg_mobile_inenc', $_COOKIE);
}

function wg_mobile_session()
{
	ini_set("session.use_cookies",false);
	ini_set("session.use_only_cookies",false);

	if(!preg_match('/^[a-z0-9]+$/',$_GET["psid"]))
	{
		session_start();
		$psid = session_id();
		$_GET["psid"] = $psid;
		wg_errorlog("NEW  PSID => {$psid}");
	}
	else
	{
		wg_errorlog("KEEP PSID => {$_GET[psid]}");
		session_id($_GET["psid"]);
		session_start();
		//wg_errordump($_SESSION);
	}
}
