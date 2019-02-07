<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function wg_hash($str)
{
	return hash_hmac('sha256', $str, WGCONF_HASHKEY );
}

/**
 * パスワード用ハッシュキーを用いて、文字列をハッシュ化する。
 * @param string $str ハッシュ化する文字列
 * @return string ハッシュ化後文字列
 */
function wg_password_hash($str)
{
	return hash_hmac('sha256', $str, WGCONF_PASSWORD_HASHKEY );
}
