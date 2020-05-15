<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

function wg_location($url)
{
	$url = wg_url($url);
	header("Location: $url");
	exit;
}

/**
 * 自分へのサイトへのアクセスを示すURLかを判定する。
 * @param String $url 判定するURL文字列
 */
function wg_is_myselfurl($url)
{
	$q = parse_url($url);
	$a = array("schema","host","port","user","pass");
	foreach( $a as $c ) if( $q[$c]!="" ) return false;
	return true;
}

/**
 * URIチェック及びurlencodeを行う。自ホスト以外へのURIは無効。
 * @param String $url URL文字列
 * @param Bool $is_encode urlencodeを行うか
 * @return String エンコード後のURI文字列
 */
function wg_encodeuri($url,$is_encode=true)
{
	if(!wg_is_myselfurl($url)) die("自サイト以外へのアクセスが発生したので、処理を中断しました。");
	return ($is_encode) ? urlencode($url) : $url;
}

/**
 * $_GETの再構築を行なって、パラメータ文字列を返す。
 * @param array $reget 置き換えるキー／内容の連想配列(NULLがキーのデータの場合、そのキーは削除される)
 * @return string 再構築を行った後のURLパラメータ文字列
 */
function wg_remake_get($reget=array())
{
	$nowget = $_GET;
	$newget = array();
	foreach( $reget  as $key => $val )
	{
		if( $val==="" || is_null($val) ) unset($nowget[$key]);
		else $nowget[$key]=$val;
	}
	foreach( $nowget as $key => $val )
	{
		$newget[] = urlencode($key).(($val!="")?("=".urlencode($val)):"");
	}
	return implode("&",$newget);
}

/**
 * $_GETの再構築を行なって、URLを返す。
 * @param array $reget 置き換えるキー／内容の連想配列(NULLがキーのデータの場合、そのキーは削除される)
 * @return String 再構築を行った後のURL文字列
 */
function wg_remake_uri($reget=array())
{
	$param = wg_remake_get($reget);
	return ($param=="") ? $_SERVER["SCRIPT_NAME"] : $_SERVER["SCRIPT_NAME"]."?${param}";
}

/**
 * 指定されたURLからパラメータの再構築を行なって、URLを返す。
 * @param array $reget 置き換えるキー／内容の連想配列(NULLがキーのデータの場合、そのキーは削除される)
 * @return String 再構築を行った後のURL文字列
 */
function wg_remake_url($url,$params=array())
{
	if( ($q=parse_url($url))==false ) return false;
	$a = array("scheme","host","port","user","pass");

	$qys = explode("&",$q["query"]);
	$qps = array();
	foreach($qys as $qq) { if($qq!=""){$qe=explode("=",$qq); $qps[$qe[0]]=urldecode($qe[1]);} }
	foreach($params as $k=>$p) $qps[$k]=$p;

	if(wg_is_mobile()) $qps["psid"] = session_id();

	$qys = array();
	foreach($qps as $k=>$v) if(!is_null($v)) $qys[] = urlencode($k).(($v!=="")?("=".urlencode($v)):"");
	$q["query"] = implode("&",$qys);

	return
		(($q["scheme"]!="") ? "{$q['scheme']}://" : "") .
		(($q["host"]!="") ? "{$q['host']}" : "" ).
		(($q["port"]!="") ? ":{$q['port']}" : "").
		$q["path"].(($q["query"]!="")?"?$q[query]":"").(($q["fragment"]!="")?"#$q[flagment]":"")
	;
}

/**
 * ブラウザが携帯かどうか調べる。
 */
function wg_is_mobile()
{
	if( !@isset($_SERVER['HTTP_USER_AGENT']) ) return false;
	$a = $_SERVER['HTTP_USER_AGENT'];
	return preg_match('/^(DoCoMo|J-PHONE|Vodafone|SoftBank|UP\.Browser|KDDI)/',$a)>0 ? true : false ;
}

/**
 * URLを調整する
 */
function wg_url($url)
{
	if(wg_is_mobile()) $url = wg_remake_url($url, array());
	return $url;
}
