<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

global $WG_CORE_DBMS; // SHARED DATABASE CONNECTION

/**
 * 規定のデータベースのインスタンスオブジェクトを返す。
 * インスタンスが生成されていない場合は、規定のデータベースのインスタンスを作成し、そのインスタンスオブジェクトを返す。
 * @return object|boolean WG_CORE_DBMSインスタンスオブジェクト
 */
function _QC()
{
	global $WG_CORE_DBMS;

	if( !$WG_CORE_DBMS instanceof WGDBMSPostgreSQL &&
		!$WG_CORE_DBMS instanceof WGDBMSMySQL )
	{
		switch(strtolower(WGCONF_DBMS_TYPE))
		{
			case 'pgsql':
			case 'postgres':
			case 'postgresql':
				require_once(dirname(__FILE__)."/postgresql.php");
				$WG_CORE_DBMS = new WGDBMSPostgreSQL(WGCONF_DBMS_HOST, WGCONF_DBMS_PORT, WGCONF_DBMS_DB, WGCONF_DBMS_USER, WGCONF_DBMS_PASSWD);
				if(!$WG_CORE_DBMS->open())
				{
					wg_log_write(WGLOG_FATAL, "'".WGCONF_DBMS_DB."' への接続に失敗しました。");
					return false;
				}
				break;

			case 'mysql':
			case 'mariadb':
			case 'maria':
				require_once(dirname(__FILE__)."/mysql.php");
				$WG_CORE_DBMS = new WGDBMSMySQL(WGCONF_DBMS_HOST, WGCONF_DBMS_PORT, WGCONF_DBMS_DB, WGCONF_DBMS_USER, WGCONF_DBMS_PASSWD);
				if(!$WG_CORE_DBMS->open())
				{
					wg_log_write(WGLOG_FATAL, "'".WGCONF_DBMS_DB."' への接続に失敗しました。");
					return false;
				}
				break;

			default:
				wg_log_write(WGLOG_FATAL, "WGCONF_DBMS_TYPE 種別が想定外です。");
				break;
		}
	}

	return $WG_CORE_DBMS;
}

/**
 * 規定のデータベースで、SQLクエリーを実行します。
 * @param string $q SQLクエリ文字列。
 * @return bool 正常に動作したか。
 */
function _E($q) { return ($d=_QC()) ? $d->E($q) : false ; }

/**
 * 規定のデータベースで、書式付きSQLクエリーを実行します。
 * @param string 書式付きフォーマット文字列。
 * @param mixed 書式に対応します変数。
 * @return int クエリ実行後のレコード数。
 */
function _Q()   { $p=func_get_args(); return ($d=_QC())?call_user_func_array(array($d,"Q"),$p) :false; }

/**
 * 規定のデータベースで、１レコードを取得する書式付きSQLクエリーを実行します。
 * @param string 書式付きフォーマット文字列。
 * @param mixed 書式に対応します変数。
 * @return mixed １レコードのSELECTが成功した場合 Array で、失敗した場合は False を返します。
 */
function _QQ()  { $p=func_get_args(); return ($d=_QC())?call_user_func_array(array($d,"QQ"),$p):false; }

/**
 * 規定のデータベースで、直前に実行したSQLが成功したかどうか取得します。
 * @return boolean 成功していた場合はTrueを、それ以外の場合はFalseを返します。
 */
function _QOK() { return ($d=_QC()) ? $d->OK()   : false   ; }

/**
 * 既定のデータベースで、直前に実行したSQLがエラーかどうか取得します。
 * @return boolean エラーだった場合はTrueを、それ以外の場合がFalseを返します。
 */
function _QNG() { return ($d=_QC()) ? $d->NG()   : true    ; }

/**
 * 既定のデータベースで、SQL実行結果から１レコード取得します。
 * @return mixed １レコード取得した場合は Array を、これ以上レコードがない場合は False を返します。
 * @return array レコードの場合は、Array( フィールド番号 => データ..., 及び、フィールド名 => データ...) で返されます。
 */
function _F()   { return ($d=_QC()) ? $d->F()    : false   ; }

/**
 * 規定のデータベースで、SQL実行結果から全レコードを配列として取得します。
 * @return array 全レコードの連想配列を返します。
 * @return array Array(Array( フィールド番号 => データ..., 及び、フィールド名 => データ...), Array...) で返されます。
 */
function _FALL(){ return ($d=_QC()) ? $d->FALL() : array() ; }

/**
 * 既定のデータベースで、SQL実行結果から特定のフィールドのデータを配列として返します。
 * @param string $field フィールド名。
 * @return array データが格納された配列。
 */
function _FARRAY($field) {
	return ($d=_QC()) ? $d->FARRAY($field) : array(); }

/**
 * 既定のデータベースで、SQL実行結果から２つの特定のフィールドが、キーとデータである連想配列として返します。
 * @param string $keyfield キーフィールド名。
 * @param string $datafield データフィールド名。
 * @return array データが格納された連想配列。
 */
function _FARRAYKEYDATA($keyfield,$datafield) {
	return ($d=_QC()) ? $d->FARRAYKEYDATA($keyfield,$datafield) : array(); }

/**
 * 規定のデータベースで、SQL実行結果のレコード数を返します。
 * @return int レコード数。
 */
function _R()                    {return ($d=_QC()) ? $d->RECS():0;}

/**
 * 文字列をSQL用にクォートする。
 * @param string $str クォートする文字列。
 * @return string クォート後の文字列。
 */
function _ESC($str)              {return ($d=_QC()) ? $d->ESC($str):die(); }

/**
 * 書式付きSQL発行用に、文字列を引用符付き文字列に変換する。
 * @param string $str 文字列。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。NULL以外の場合はクォート後両端に引用符が付加されます。
 */
function _S($str,$allow_nl=true) {return ($d=_QC()) ? $d->S($str,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、論理値を文字列に変換する。
 * @param boolean $bool 論理値。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。true, false, null が返されます。
 */
function _B($bool,$allow_nl=true){return ($d=_QC()) ? $d->B($bool,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、数値を文字列に変換する。
 * @param int $num 数値。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。
 */
function _N($num,$allow_nl=true) {return ($d=_QC()) ? $d->N($num,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、浮動小数点数を文字列に変換する。
 * @param double $num 浮動小数点数。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。
 */
function _D($num,$allow_nl=true) {return ($d=_QC()) ? $d->D($num,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、日付時刻を文字列に変換する。
 * @param string $t 日付時刻文字列。PostgreSQLでの日付関数表記も可能です。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。日付関数表記以外の場合、両端に引用符が付与されるだけです。
 */
function _T($t,$allow_nl=true)   {return ($d=_QC()) ? $d->T($t,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、位置の浮動小数点配列を文字列に変換する。
 * @param array $pos 浮動小数点配列。Array(X座標、Y座標)で与えられる配列です。
 * @param boolean $allow_nl Trueの場合NULL値を利用する。
 * @return string 変換後の文字列。NULL値以外の場合は、'(%f,%f)' の形式に変換されます。
 */
function _P($pos,$allow_nl=true) {return ($d=_QC()) ? $d->P($pos,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、バイナリデータを文字列に変換する。
 * @param $raw
 * @param bool $allow_nl
 */
function _BLOB($raw,$allow_nl=true) {return ($d=_QC()) ? $d->BLOB($raw,$allow_nl):die(); }

/**
 * 書式付きSQL発行用に、現在ログイン中のユーザーIDを文字列に変換する。
 * @return string ユーザーIDの文字列。
 */
function _U() { return ($d=_QC()) ? $d->N(wg_get_usercd()):die(); }

/**
 * トランザクションを開始します。
 */
function _QBEGIN()    { return ($d=_QC()) ? $d->BEGIN()    : false ; }

/**
 * トランザクションをロールバックします。
 */
function _QROLLBACK() { return ($d=_QC()) ? $d->ROLLBACK() : false ; }

/**
 * トランザクションをコミットします。
 */
function _QCOMMIT()   { return ($d=_QC()) ? $d->COMMIT()   : false ; }

/**
 * トランザクションを終了します。
 * 実質的には COMMIT されることと同等です。
 */
function _QEND()      { return ($d=_QC()) ? $d->END()      : false ; }

/**
 * DBMSが MySQL であるかチェックする。
 * @return boolean MySQLの場合、true。
 */
function wg_is_dbms_mysql()
{
	return in_array( strtolower(WGCONF_DBMS_TYPE), ['mysql'] );
}

/**
 * DBMSが mariadb であるかチェックする。
 * @return boolean mariadb の場合、true。
 */
function wg_is_dbms_mariadb()
{
	return in_array( strtolower(WGCONF_DBMS_TYPE), ['maria','mariadb'] );
}

/**
 * DBMSが PostgreSQL であるかチェックする。
 * @return boolean PostgreSQL の場合、true。
 */
function wg_is_dbms_postgresql()
{
	return in_array( strtolower(WGCONF_DBMS_TYPE), ['pgsql','postgres','postgresql'] );
}

/**
 * mariadb で nextval などのシーケンス利用できるバージョンであるかチェックする。
 * @return boolean mariadb 10.3 以降である場合、true。
 */
function wg_is_supported_sequence_mariadb()
{
	if( wg_is_dbms_mariadb() )
	{
		$v = explode(".", WGCONF_DBMS_VERSION);
		if( count($v) >= 2 && (int)$v[0] * 1000 + (int)$v[1] >= 10003 ) return true;
	}
	return false;
}
