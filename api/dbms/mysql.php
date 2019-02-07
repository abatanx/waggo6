<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once(dirname(__FILE__)."/dbms.php");

/**
 * MySQLインターフェース。
 * @package API\データベース
 */
class WGDBMSMySQL extends WGDBMS
{
	public    $echo       = false;

	/**
	 * static
	 * @var PDO
	 */
	static public $current_connection = false;

	/**
	 * @var PDO
	 */
	protected $connection = false;

	/**
	 * @var PDOStatement
	 */
	protected $query      = false;

	protected $exectime   = 0.0;
	protected $row        = 0;
	protected $maxrows    = 0;
	protected $fetchmode  = PDO::FETCH_BOTH;

	protected $HOST       = "";
	protected $PORT       = 3306;
	protected $DB         = "";
	protected $USER       = "";
	protected $PASSWD     = "";

	/**
	 * MySQL接続インスタンスを作成する。
	 * @param string $host 接続先ホスト
	 * @param int $port 接続先ポート番号
	 * @param string $db 接続先データベース名
	 * @param string $user 接続ユーザ名
	 * @param string $passwd 認証パスワード
	 */
	public function __construct($host,$port,$db,$user,$passwd)
	{
		parent::__construct();
		$this->HOST   = $host;
		$this->PORT   = $port;
		$this->DB     = $db;
		$this->USER   = $user;
		$this->PASSWD = $passwd;
	}

	/**
	 * @inheritdoc
	 */
	public function open()
	{
		if($this->connection) return true; // Already opened.

		$params = array();
		if( $this->HOST  !="" ) $params[] = "host={$this->HOST}";
		if( $this->DB    !="" ) $params[] = "dbname={$this->DB}";
		$params[] = "charset=utf8";

		$param = implode(";",$params);
		$options = array();
		$dbms_ca = WGCONF_DBMS_CA;
		if (!empty($dbms_ca)) {
			$options = array(
				PDO::MYSQL_ATTR_SSL_CA => $dbms_ca
			);
		}
		$this->connection = new PDO("mysql:{$param}", $this->USER, $this->PASSWD, $options);

		if(!$this->connection) wgdie("Database connection error.");

		self::$current_connection = $this->connection;

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function close()
	{
//		if($this->connection) @pg_close($this->connection);
		$this->connection = false;
	}

	/**
	 * @inheritdoc
	 */
	public function E($q)
	{
		if(!$this->connection) return 0;

		$t1 = microtime(true);
		$this->query = $this->connection->query($q);
		$t2 = microtime(true);
		$td = $t2 - $t1;
		$this->exectime += $td;
		$maxrows = ($this->query) ? $this->query->rowCount() : 0 ;

		$e = (!$this->query) ? implode(" ", $this->connection->errorInfo()) : "" ;

		$b = array(
			"d" => $td ,
			"i" => $this->exectime ,
			"s" => $q ,
			"r" => ($this->query) ? true : false ,
			"e" => $e ,
			"maxrows" => $maxrows );
		$this->log[] = $b;

		$rt = $b["r"] ? "OK" : "**** ERROR *****";
		$et = $b["r"] ? "" : " <<<< {$e} >>>>";
		$lt = $b["r"] ? WGLOG_INFO : WGLOG_ERROR ;

		if( $this->logging || !$this->query )
		{
			wg_log_write($lt,
				sprintf("(%s %d row(s) +%.1f/%.1f) %s%s",
					$rt, $b["maxrows"], $td, $b["i"], $q, $et)
			);
		}

		$this->row     = 0;
		$this->maxrows = $maxrows;
		return $maxrows;
	}

	/**
	 * @inheritdoc
	 */
	public function Q()
	{
		$p = func_get_args();
		$f = array_shift($p);
		return $this->E(vsprintf($f,$p));
	}

	/**
	 * @inheritdoc
	 */
	public function QQ()
	{
		$p = func_get_args();
		$f = array_shift($p);
		$t = $this->E(vsprintf($f,$p));
		if( $t===false || $t!=1 ) return false;
		return $this->F();
	}

	/**
	 * @inheritdoc
	 */
	public function OK() { return ($this->query);  }

	/**
	 * @inheritdoc
	 */
	public function NG() { return (!$this->query); }

	/**
	 * @inheritdoc
	 */
	public function F()
	{
		if( !$this->connection  ) return false;
		if( !$this->query       ) return false;
		if(  $this->maxrows==-1 ) return false;
		return ($this->row++ < $this->maxrows) ? $this->query->fetch($this->fetchmode) : false ;
	}

	/**
	 * @inheritdoc
	 */
	public function FALL()
	{
		$r = array(); while($f=$this->F()) $r[]=$f; return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function FARRAY($field)
	{
		$r = array(); while($f=$this->F()) $r[]=$f[$field]; return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function FARRAYKEYDATA($kf,$df)
	{
		$r = array(); while($f=$this->F()) $r[$f[$kf]]=$f[$df]; return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function RECS()
	{
		if( !$this->connection  ) return 0;
		if( !$this->query       ) return 0;
		if(  $this->maxrows==-1 ) return 0;
		return $this->maxrows;
	}

	/**
	 * 文字列をSQL用にクォートする。
	 * @param string $str クォートする文字列。
	 * @return string クォート後の文字列。
	 */
	static public function ESC($str)
	{
		$v = self::$current_connection->quote($str);
		if( ($len=strlen($v))>=2 && $v[0]==$v[$len-1] ) return substr($v,1,$len-2);
		return $v;
	}

	/**
	 * 書式付きSQL発行用に、数値を文字列に変換する。
	 * @param int $num 数値。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。
	 */
	static public function N($num,$anl=true)
	{
		if($anl && (is_null($num)||!is_numeric($num))) return "null";
		return (int)$num;
	}

	/**
	 * 書式付きSQL発行用に、文字列を引用符付き文字列に変換する。
	 * @param string $str 文字列。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。NULL以外の場合はクォート後両端に引用符が付加されます。
	 */
	static public function S($str,$anl=true)
	{
		if($anl && is_null($str)) return "null";
		return "'".self::ESC($str)."'";
	}

	/**
	 * 書式付きSQL発行用に、論理値を文字列に変換する。
	 * @param boolean $bool 論理値。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。true, false, null が返されます。
	 */
	static public function B($bool,$anl=true)
	{
		if($anl && is_null($bool)) return "null";
		return (($bool) ? "1" : "0");
	}

	/**
	 * 書式付きSQL発行用に、日付時刻を文字列に変換する。
	 * @param string $d 日付時刻文字列。MySQLでの日付関数表記も可能です。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。日付関数表記以外の場合、両端に引用符が付与されるだけです。
	 */
	static public function T($d,$anl=true)
	{
		if($anl && (is_null($d)||$d===false||$d==="")) return "null";
		else if($anl && $d===false) return "null";
		else if(preg_match("/^(current|epoch|-?infinity|invalid|now|today|tomorrow|yesterday|zulu|allballs|z)/i",$d)) return $d;
		else return self::S($d);
	}

	/**
	 * 書式付きSQL発行用に、浮動小数点数を文字列に変換する。
	 * @param double $num 浮動小数点数。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。
	 */
	static public function D($num,$anl=true)
	{
		if($anl && (is_null($num)||!is_numeric($num))) return "null";
		return (double)$num;
	}

	/**
	 * 書式付きSQL発行用に、位置の浮動小数点配列を文字列に変換する。
	 * @param array $pos 浮動小数点配列。Array(X座標、Y座標)で与えられる配列です。
	 * @param boolean $anl Trueの場合NULL値を利用する。
	 * @return string 変換後の文字列。NULL値以外の場合は、'(%f,%f)' の形式に変換されます。
	 */
	static public function P($pos,$anl=true)
	{
		if( $anl && (is_null($pos) || !is_array($pos)) ) return "null";
		return sprintf("'(%f,%f)'",$pos[0],$pos[1]);
	}

	/**
	 * SQLから返されたデータが同じかどうか比較します。
	 * @param string $a 比較対象文字列１。
	 * @param string $b 比較対象文字列２。
	 * @param boolean $type NULLの場合はあいまい比較、stringの場合は文字列比較、datetimeの場合は日付時刻比較、boolの場合は論理値比較を行います。
	 * @return boolean 比較対象の片方がNULLの場合必ずTrueが、それ以外の場合は一致していればTrueを、それ以外の場合はFalseを返します。
	 */
	static public function CMP($a,$b,$type=null)
	{
		if(is_null($a) && is_null($b))return true;
		switch($type)
		{
			case "string":
				return ((string)$a==(string)$b);
			case "datetime":
				$a = strtotime($a);
				$b = strtotime($b);
				return ($a===$b);
			case "bool":
				if($a=="t") $a=true; else if($a=="f") $a=false;
				if($b=="t") $b=true; else if($b=="f") $b=false;
				return ($a===$b);
		}
		return ($a==$b);
	}

	/**
	 * @inheritdoc
	 */
	public function BEGIN()    { $this->E("begin;"); }

	/**
	 * @inheritdoc
	 */
	public function ROLLBACK() { $this->E("rollback;"); }

	/**
	 * @inheritdoc
	 */
	public function COMMIT()   { $this->E("commit;"); }

	/**
	 * @inheritdoc
	 */
	public function END()      { $this->E("commit;"); }
}
