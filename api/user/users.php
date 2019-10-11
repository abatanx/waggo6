<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function wg_is_login()
{
	return (isset($_SESSION["_sUID"]) && $_SESSION["_sUID"]!=0);
}

/**
 * ログインしているのであればユーザコードを取得する。
 * @return int ログイン済みの場合ユーザコードを、未ログインの場合 0 を返す。
 */
function wg_get_usercd()
{
	return (wg_is_login()) ? $_SESSION["_sUID"] : 0 ;
}

/**
 * ユーザコードのユーザが存在するかチェックする。
 * @param int $usercd 判定するユーザコード
 * @return boolean 存在すればTrueを、存在しなければFalseを返す。
 */
function wg_is_user($usercd)
{
	$v = _QQ("SELECT true FROM base WHERE usercd=%s AND enable=true AND deny=false;", _N($usercd));
	return ($v) ? true : false;
}

/**
 * ユーザコードがログインしている自分自身か返す。
 * @param int $usercd 判定するユーザコード。
 * @return boolean 自分自身の場合Trueを、自分以外の場合はFalseを返す。
 */
function wg_is_myself($usercd)
{
	return wg_is_login() ? (wg_get_usercd()===$usercd) : false ;
}

/**
 * ユーザコードのユーザが管理権限を持っているかチェックする。
 * @param int $usercd 判定するユーザコード(null の場合は、ログインしているユーザのユーザコード)。
 * @return boolean 管理権限があればTrueを返す。
 */
function wg_is_admin($usercd=null)
{
	if(is_null($usercd)) $usercd = wg_get_usercd();
	list($sec) = _QQ("SELECT security FROM base_normal WHERE usercd=%s;", _N($usercd));
	return ($sec>=WGSECURE_SL_ADMIN);
}

/**
 * ログインを行う。
 * @param int $usercd セッションをユーザがログインした状態に変更する。
 */
function wg_set_login($usercd)
{
	$_SESSION["_sUID"]   = $usercd;
	$_SESSION["_sRHOST"] = wg_get_remote_adr();
}

/**
 * ログアウトを行う。
 */
function wg_unset_login()
{
	unset($_SESSION["_sUID"]);
	unset($_SESSION["_sRHOST"]);
}
