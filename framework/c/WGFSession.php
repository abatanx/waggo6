<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGFSession
{
	static $isOpenSession = False;
	protected $sessionid, $transactionid, $combinedid;

	/**
	 * 固有セッション管理インスタンスを作成する。
	 * @param string $sessionid セッション管理ID。
	 * @param string $transactionid 画面遷移管理ID。
	 */
	public function __construct($sessionid,$transactionid) {
		$this->setId($sessionid,$transactionid);
	}

	/**
	 * 固有セッション管理インスタンスを結合キーから復帰する。
	 * @param string $combinedid 結合キー
	 * @return WGFSession|bool 成功した場合は復元した固有セッション管理インスタンスを、失敗した場合は false を返す。
	 */
	static public function restoreByCombinedId($combinedid)
	{
		if( is_array($_SESSION) )
		{
			$key_s = array_keys($_SESSION);
			foreach( $key_s as $ks )
			{
				if( is_array($_SESSION[$ks]) )
				{
					$key_t = array_keys($_SESSION[$ks]);
					foreach( $key_t as $kt )
					{
						if( is_array($_SESSION[$ks][$kt])
							&& @isset($_SESSION[$ks][$kt]['%combined'])
							&& $_SESSION[$ks][$kt]['%combined'] === $combinedid )
						{
							return new WGFSession($ks,$kt);
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * 固有セッション管理インスタンスを破棄する。
	 */
	public function __destruct()
	{
		if(WG_SESSIONDEBUG) wg_log_dump(WGLOG_INFO, $_SESSION);
	}

	/**
	 * PHPセッションを開始する。
	 * WGFSession は、このPHPセッションのうち、固有セッション管理IDで指定された部分を画面維持などに利用します。
	 */
	static public function open()
	{
		if(!self::$isOpenSession)
		{
			if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ waggo SESSION open ]]]");
			session_cache_limiter('nocache');

			if(!wg_is_mobile()) session_start();
			else				wg_mobile_session();

			self::$isOpenSession = True;
		}
	}

	/**
	 * PHPセッションを終了する。
	 */
	static public function close()
	{
		if(self::$isOpenSession)
		{
			session_write_close();
			if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ waggo SESSION close ]]]");
			self::$isOpenSession = False;
		}
	}

	/**
	 * 固有セッション管理IDを取得する。
	 * @retval array [0=>セッション管理ID, 1=>画面遷移ID] を返す。
	 */
	public function getId() { return array($this->sessionid,$this->transactionid); }
	public function setId($sessionid,$transactionid) {
		$this->sessionid     = $sessionid;
		$this->transactionid = $transactionid;
		$this->combinedid    = md5($this->transactionid . ' @ ' . $this->sessionid);
		if(!isset($_SESSION[$this->sessionid][$this->transactionid]) || !is_array($_SESSION[$this->sessionid][$this->transactionid]))
			$_SESSION[$this->sessionid][$this->transactionid] = array();

		$_SESSION[$this->sessionid][$this->transactionid]["%atime"] = time();
		$_SESSION[$this->sessionid][$this->transactionid]["%combined"] = $this->combinedid;

		if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ waggo SESSION started, {$this->sessionid} {$this->transactionid} ]]]");
	}

	/**
	 * 固有セッション管理IDのうち、複合IDを取得する。
	 * @return string 結合ID
	 */
	public function getCombinedId()		{	return $this->get('%combined');	}

	/**
	 * 固有セッション管理IDのうち、セッション管理IDを取得する。
	 * @retval string セッション管理ID。
	 */
	public function getSessionId()		{	return $this->sessionid;		}

	/**
	 * 固有セッション管理IDのうち、画面遷移IDを取得する。
	 * @retval string セッション管理ID。
	 */
	public function getTransactionId()	{	return $this->transactionid;	}

	/**
	 * 固有セッションのすべての情報を返します。通常は配列で得られます。
	 * @return mixed PHPSESSION[セッション管理ID][画面遷移ID] を返します。
	 */
	public function getAll()         { return $_SESSION[$this->sessionid][$this->transactionid]; }

	/**
	 * 固有セッション管理IDで管理している領域に、データをセットします。
	 * @param string $key キー。
	 * @param mixed $val データ。
	 */
	public function __set($key,$val) { $this->set($key,$val);   }

	/**
	 * 固有セッション管理IDで管理している領域から、データを取得します。
	 * @param string $key キー。
	 * @return mixed データ。
	 */
	public function __get($key)      { return $this->get($key); }

	/**
	 * 固有セッション管理IDで管理している領域に、データをセットします。
	 * @param string $key キー。
	 * @param mixed $val データ。
	 */
	public function set($key,$val)
	{
		if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"SESSION set '$key' = '$val'");
		if(is_null($val))
		{
			$_SESSION[$this->sessionid][$this->transactionid][$key] = null;
			unset($_SESSION[$this->sessionid][$this->transactionid][$key]);
		}
		else
			$_SESSION[$this->sessionid][$this->transactionid][$key] = $val;
	}

	/**
	 * 固有セッション管理IDで管理している領域から、データを取得します。
	 * @param string $key キー。
	 * @return mixed データ。
	 */
	public function get($key)
	{
		$val = null;
		if (isset($_SESSION[$this->sessionid][$this->transactionid][$key])) $val = $_SESSION[$this->sessionid][$this->transactionid][$key];
		if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"SESSION get '$key' = '$val'");
		return $val;
	}

	/**
	 * 固有セッション管理IDで管理している領域に、データがセットされているか確認します。
	 * @param string $key キー。
	 * @return boolean true データが存在する場合。存在性の確認は isset 関数で行います。false それ以外。
	 */
	public function isExists($key)	{ return isset($_SESSION[$this->sessionid][$this->transactionid][$key]); }

	/**
	 * 固有セッション管理IDで管理している領域で、該当するキーのデータが空の状態化か確認します。
	 * @param string $key キー。
	 * @return boolean true データが存在しない場合。存在性の確認は empty 関数で行います。false それ以外。
	 */
	public function isEmpty($key)	{ return empty($_SESSION[$this->sessionid][$this->transactionid][$key]); }

	/**
	 * 固有セッション管理IDで管理している領域で、該当するキーのデータを削除します。
	 * @param string $key キー。
	 */
	public function delete($key)	{ $this->set($key,null); }

	/**
	 * 固有セッション管理IDで管理している領域を GC対象領域としてマークします。
	 */
	public function release()		{ $_SESSION[$this->sessionid][$this->transactionid] = array("%release"=>true);	}

	/**
	 * 固有セッション管理IDで管理している領域を、すべてクリアします。
	 */
	public function cleanup()
	{
		$_SESSION[$this->sessionid][$this->transactionid] = null;
		unset($_SESSION[$this->sessionid][$this->transactionid]);
	}

	/**
	 * PHPセッションから、固有セッションの状態を確認し、利用されていない場合開放します。
	 */
	static public function gc()
	{
		if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ waggo SESSION garbage collection ]]]");
		self::open();

		$ntime = time();
		foreach($_SESSION as $sk=>$trs)
		{
			if(preg_match('/^[0-9a-zA-Z]/',$sk) && is_array($trs))
			{
				foreach($trs as $tk=>$td)
				{
					if((isset($td["%atime"]) && ($ntime-$td["%atime"])>WGCONF_SESSION_GCTIME) ||
						isset($td["%release"]))
					{
						$_SESSION[$sk][$tk] = null;
						unset($_SESSION[$sk][$tk]);
						if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ COLLECTED-TRANSACTION ]]] {$sk}{$tk}");
					}
				}
				if(count($_SESSION[$sk])==0)
				{
					$_SESSION[$sk] = null;
					unset($_SESSION[$sk]);
					if(WG_SESSIONDEBUG) wg_log_write(WGLOG_INFO,"[[[ COLLECTED-SESSION-KEY ]]] {$sk}");
				}
			}
		}
	}
}
