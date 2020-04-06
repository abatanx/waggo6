<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

abstract class WGDBMS
{
	public $echo;
	public $logging, $log;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->echo    = false;
		$this->logging = WG_SQLDEBUG;
		$this->log     = array();
	}

	/**
	 * データベース接続を開始します。
	 */
	abstract public function open();

	/**
	 * データベース接続を終了します。
	 */
	abstract public function close();

	/**
	 * SQLクエリーを実行します。
	 * @param string $q SQLクエリ文字列。
	 */
	abstract public function E($q);

	/**
	 * 書式付きSQLクエリーを実行します。
	 * @param string $format 書式付きフォーマット文字列。
	 * @param mixed $args... 書式に対応します変数。
	 * @return int クエリ実行後のレコード数。
	 */
	abstract public function Q();

	/**
	 * １レコードを取得する書式付きSQLクエリーを実行します。
	 * @param string $format 書式付きフォーマット文字列。
	 * @param mixed $args... 書式に対応します変数。
	 * @return mixed １レコードのSELECTが成功した場合 Array で、失敗した場合は False を返します。
	 */
	abstract public function QQ();

	/**
	 * 直前に実行したSQLが成功したかどうか取得します。
	 * @return boolean 成功していた場合はTrueを、それ以外の場合はFalseを返します。
	 */
	abstract public function OK();

	/**
	 * 直前に実行したSQLがエラーかどうか取得します。
	 * @return boolean エラーだった場合はTrueを、それ以外の場合がFalseを返します。
	 */
	abstract public function NG();

	/**
	 * SQL実行結果から、１レコード取得します。
	 * @return mixed １レコード取得した場合は Array を、これ以上レコードがない場合は False を返します。
	 * @return array レコードの場合は、Array( フィールド番号 => データ..., 及び、フィールド名 => データ...) で返されます。
	 */
	abstract public function F();

	/**
	 * SQL実行結果から、全レコードを配列として取得します。
	 * @return array 全レコードの連想配列を返します。
	 * @return array Array(Array( フィールド番号 => データ..., 及び、フィールド名 => データ...), Array...) で返されます。
	 */
	abstract public function FALL();

	/**
	 * SQL実行結果から、特定のフィールドのデータを配列として返します。
	 * @param string $field フィールド名。
	 * @return array データが格納された配列。
	 */
	abstract public function FARRAY($field);

	/**
	 * SQL実行結果のレコード数を返します。
	 * @return int レコード数。
	 */
	abstract public function RECS();

	/**
	 * トランザクションを開始します。
	 */
	abstract public function BEGIN();

	/**
	 * トランザクションをロールバックします。
	 */
	abstract public function ROLLBACK();

	/**
	 * トランザクションをコミットします。
	 */
	abstract public function COMMIT();

	/**
	 * トランザクションを終了します。
	 * 実質的には COMMIT されることと同等です。
	 */
	abstract public function END();
}
