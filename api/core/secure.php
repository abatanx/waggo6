<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

define( 'WGSECURE_SL_GUEST'   ,  0 );
define( 'WGSECURE_SL_USER'    , 10 );
define( 'WGSECURE_SL_MANAGER' , 40 );
define( 'WGSECURE_SL_ADMIN'   , 50 );

/**
 * 画面遷移クラス
 */
class WGTransition
{
	const
		TRANSKEY		=	"T",		///< 画面遷移で利用する$_GET用キー
		TRANSKEYCALL	=	"_TC",		///< WGFController で、他のコントローラーの呼び出しに利用する$_GET用キー
		TRANSKEYRET		=	"_TR",		///< WGFController で、他のコントローラーの呼び出しに対する返り値に利用する$_GET用キー
		TRANSKEYPAIR	=	"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", ///< 画面遷移確認IDのキーとして利用できる文字列。
		TRANSKEYLEN		=	6,			///< 画面遷移確認IDのキーとして利用する文字列の長さ。
		TRANSKEYLOOP	=	1;			///< @deprecated

	/**
	 * @var WGFSession
	 */
	public $session;	///< 画面遷移で利用している現在のセッション管理(WGFSession)インスタンス。

	/**
	 * 画面遷移確認IDを生成する。
	 * @retval string 画面遷移確認IDを文字列で返します。TRANSKEYPAIR で構成された、TRANSKEYLEN の長さの文字列です。
	 */
	public function createTransitionId()
	{
		$r = "";
		$s = self::TRANSKEYPAIR;
		$l = strlen($s);
		for($i=0; $i<self::TRANSKEYLEN; $i++) $r .= $s[mt_rand(0,$l-1)];
		return $r;
	}

	/**
	 * 画面遷移確認IDを確認する。
	 * @retval string 画面遷移が正常になされた場合、画面遷移確認IDの文字列を返します。
	 * @retval false それ以外の場合。
	 */
	public function getTransitionId()
	{
		$t = $_GET[self::TRANSKEY];
		if(strlen($t)!=self::TRANSKEYLEN) return false;
		if(!preg_match('/^['.self::TRANSKEYPAIR.']+$/',$t)) return false;
		return $t;
	}

	/**
	 * 画面遷移で利用している WGFSession インスタンスを返す。
	 * @retval WGFSession 現在のセッション管理インスタンス。
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * 画面遷移セッションを作成する。
	 * 画面遷移が多数なされるとセッションに不必要な情報が溜まるので、必要にあわせて GC を行います。
	 * @param string $sessionid セッションID
	 * @param string $tid トランザクションID
	 * @return boolean 画面遷移上、初回アクセスの場合(セッション内容が初期状態) true を、維持アクセスの場合 false を返す。
	 */
	public function firstpage($sessionid, $tid="")
	{
		$gpara    = array();
		$is_new   = true;
		foreach( $_GET as $k => $v )
		{
			switch($k)
			{
				case self::TRANSKEY:
					$is_new = false;
					$tid    = $v;
					break;

				default:
					$gpara[$k] = $v;
					break;
			}
		}

		// セッションのゴミ回収
		WGFSession::gc();

		// NEXT付きの場合は、セッションチェック
		if( !$is_new )
		{
			$this->session = new WGFSession($sessionid,$tid);
			if($this->session->get("%tid")!=$tid) $is_new = true;
			if($is_new) wg_log_write(WGLOG_INFO, "RESET ");
		}

		if( $is_new )
		{
			$tid = self::createTransitionId();

			$npara = array();
			$gpara[self::TRANSKEY] = $tid;
			foreach($gpara as $k=>$v) $npara[] = urlencode($k).(($v!="")?("=".urlencode($v)):"");
			$u = $_SERVER["SCRIPT_NAME"].((count($npara)!=0)?"?".implode('&',$npara):"");
			$_SERVER["REQUEST_URI"]  = $u;
			$_GET[self::TRANSKEY] = $tid;

			$this->session = new WGFSession($sessionid,$tid);
			$this->session->set("%tid",$tid);

			return true;
		}
		return false;
	}
}

/**
 * セキュリティレベルを確認する。
 * @param int $usercd セキュリティレベルを確認する対象ユーザーコード。
 * @param int $secid セキュリティレベル。
 * @return boolean $secid 以上のセキュリティレベルがある場合 true を、ユーザーのセキュリティレベルが低かったり、ユーザーがそもそも存在しない場合 false を返す。
 */
function wg_assess_security($usercd,$secid)
{
	if( ($u=_QQ("SELECT security FROM base_normal WHERE usercd=%s;",_N($usercd)))==false ) return false;
	if( ($s=_QQ("SELECT security FROM security WHERE name=%s;",_S($secid)))==false ) return false;
	return (intval($u["security"])>=intval($s["security"]));
}
