<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

abstract class WGG
{
	/**
	 * @var string[] エラーメッセージ
	 */
	private $e_msg;

	/**
	 * @var WGG 後部連結するガントレット
	 */
	private $e_add_wgg_valid;
	private $e_add_wgg_invalid;

	public function __construct()
	{
		$this->e_msg           = [];
		$this->e_add_wgg_valid = null;
	}

	/**
	 * @param WGG ... 引数の最初は評価後、正常の場合（invalid が null の場合は、すべてのケースで）に追評価するガントレット
	 * 次の引数は評価後、異常の場合に追評価するガントレット
	 * @return WGG
	 */
	public function add()
	{
		$args = func_get_args();
		$wggs = [];
		array_walk_recursive($args, function($v) use (&$wggs)
		{
			if( $v instanceof WGG ) $wggs[] = $v;
		});

		if( count($wggs) < 1 || count($wggs) > 2 )
		{
			wg_log_write(WGLOG_FATAL, "WGG::add method requires one or two parameters.");
		}

		$this->e_add_wgg_valid   = $wggs[0];
		$this->e_add_wgg_invalid = isset($wggs[1]) ? $wggs[1] : null;
		return $this;
	}

	public function setError($msg)			{ $this->e_msg = [$msg];				return $this;	}
	public function addError($msg)			{ $this->e_msg[] = $msg;				return $this;	}
	public function listError()				{ return $this->e_msg;									}
	public function getError()				{ return implode("," , $this->e_msg);					}
	public function unsetError()			{ $this->e_msg = [];					return $this;	}
	public function hasError()				{ return count($this->e_msg) > 0;						}
	public function isFilter()				{ return false;											}

	/**
	 * @return string エラーメッセージテンプレート
	 */
	abstract public function makeErrorMessage();

	/**
	 * @param mixed $data 通過させるデータ
	 * @return boolean 検証の結果、終了させるか否か。
	 */
	abstract public function validate(&$data);

	/**
	 * ガントレットの結果によって分岐する場合は、エラーメッセージを追記しない。
	 */
	public function isBranch()
	{
		return
			$this->e_add_wgg_valid   instanceof WGG &&
			$this->e_add_wgg_invalid instanceof WGG;
	}

	/**
	 * @param mixed $data ガントレット対象データ
	 * @return WGG ガントレットインスタンス
	 */
	public function check(&$data)
	{
		$cur_wgg = $this;
		while( $cur_wgg instanceof WGG )
		{
			$result = $cur_wgg->unsetError()->validate($data);
			if( $cur_wgg !== $this)
			{
				$this->e_msg = array_merge($this->e_msg, $cur_wgg->listError());
			}

			if( $cur_wgg->isBranch() )
			{
				$cur_wgg = $result ? $cur_wgg->e_add_wgg_valid : $cur_wgg->e_add_wgg_invalid;
			}
			else
			{
				if( $result===false && !$cur_wgg->isFilter() ) break;
				$cur_wgg = $cur_wgg->e_add_wgg_valid;
			}
		}
		return $this;
	}
}
