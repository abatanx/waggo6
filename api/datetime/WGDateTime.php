<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGDateTime
{
	private $_ut;
	private $_utstr;

	const Y = 0;
	const M = 1;
	const D = 2;
	const H = 3;
	const I = 4;
	const S = 5;

	static function splitDateTime($ut)
	{
		$a = localtime($ut, true);
		return [
			$a["tm_year"] + 1900	,
			$a["tm_mon"]  + 1		,
			$a["tm_mday"]			,
			$a["tm_hour"]			,
			$a["tm_min"]			,
			$a["tm_sec"]
		];
	}

	static function makeDateTime($a)
	{
		return mktime(
			$a[self::H], $a[self::I], $a[self::S],
			$a[self::M], $a[self::D], $a[self::Y]
		);
	}

	/**
	 * 4,5,6,...,3 の月を 0,1,2...,11 のインデックスに変換する。
	 * @param int $month 月の数値(4〜12,1〜3)
	 * @return int 0〜11のインデックス(0:4, 1:5,..., 11:3)
	 */
	static function convertMonthToNendoIndex($month)
	{
		return (($month + 12) - 4) % 12 ;
	}

	/**
	 * 0,1,2...,11 のインデックスを、4,5,6,...,3 の月に変換する。
	 * @param int $nendoidx 月のインデックス(0〜11)
	 * @return int 月(4:0, 5:1, ..., 3:11)
	 */
	static function convertNendoIndexToMonth($nendoidx)
	{
		return ($nendoidx + 3) % 12 + 1;
	}

	public function __construct()
	{
		$this->setUT(0);
	}

	private function setUT($ut)
	{
		$this->_ut    = $ut;
		$this->_utstr = date("Y-m-d H:i:s", $ut);
	}

	private function getUT()
	{
		return $this->_ut;
	}

	public function set($y,$m=1,$d=1,$h=0,$i=0,$s=0)
	{
		$this->setUT(self::makeDateTime([$y,$m,$d,$h,$i,$s]));
		return $this;
	}

	public function setDate($y=null,$m=null,$d=null)
	{
		$a = self::splitDateTime($this->getUT());
		if( !is_null($y) ) $a[self::Y] = $y;
		if( !is_null($m) ) $a[self::M] = $m;
		if( !is_null($d) ) $a[self::D] = $d;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function setTime($h=null,$i=null,$s=null)
	{
		$a = self::splitDateTime($this->getUT());
		if( !is_null($h) ) $a[self::H] = $h;
		if( !is_null($i) ) $a[self::I] = $i;
		if( !is_null($s) ) $a[self::S] = $s;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function setNow()
	{
		$this->setUT(time());
		return $this;
	}

	public function setUnixTime($ut)
	{
		$this->setUT($ut);
		return $this;
	}

	public function setStrToTime($str)
	{
		$this->setUT(strtotime($str));
		return $this;
	}

	public function getTimeValue($k)
	{
		return self::splitDateTime($this->getUT())[$k];
	}

	public function setTimeValue($k,$v)
	{
		$a = self::splitDateTime($this->getUT());
		$a[$k] = $v;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function getYear()
	{
		return $this->getTimeValue(self::Y);
	}

	public function setYear($v)
	{
		$this->setTimeValue(self::Y, $v);
		return $this;
	}

	public function getMonth()
	{
		return $this->getTimeValue(self::M);
	}

	public function setMonth($v)
	{
		$this->setTimeValue(self::M, $v);
		return $this;
	}

	public function getDay()
	{
		return $this->getTimeValue(self::D);
	}

	public function setDay($v)
	{
		$this->setTimeValue(self::D, $v);
		return $this;
	}

	public function getHour()
	{
		return $this->getTimeValue(self::D);
	}

	public function setHour($v)
	{
		$this->setTimeValue(self::H, $v);
		return $this;
	}

	public function getMin()
	{
		return $this->getTimeValue(self::I);
	}

	public function setMin($v)
	{
		$this->setTimeValue(self::I, $v);
		return $this;
	}

	public function getSec()
	{
		return $this->getTimeValue(self::S);
	}

	public function setSec($v)
	{
		$this->setTimeValue(self::S, $v);
		return $this;
	}

	public function setYearMonth($yy,$mm)
	{
		$this->truncateMonth();
		$this->setMonth($mm);
		$this->setYear($yy);
		return $this;
	}

	public function getYearMonth()
	{
		return [$this->getYear(), $this->getMonth()];
	}

	public function setNendoMonth($nendo,$mm)
	{
		$this->truncateMonth();
		$this->setMonth($mm);
		if(      $mm>=4 && $mm<=12 ) $this->setYear($nendo);
		else if( $mm>=1 && $mm<=3  ) $this->setYear($nendo + 1);
		else throw new Exception("WGDateTime年度変換内部エラー");
		return $this;
	}

	public function getNendo()
	{
		$mm = $this->getMonth();
		if(      $mm>=4 && $mm<=12 ) return $this->getYear();
		else if( $mm>=1 && $mm<=3  ) return $this->getYear() - 1;
		else throw new Exception("WGDateTime年度変換内部エラー");
	}

	public function getNendoMonth()
	{
		return [$this->getNendo(), $this->getMonth()];
	}

	public function truncateYear()
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::M] = 1;
		$a[self::D] = 1;
		$a[self::H] = $a[self::I] = $a[self::S] = 0;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function truncateMonth()
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::D] = 1;
		$a[self::H] = $a[self::I] = $a[self::S] = 0;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function truncateDay()
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::H] = $a[self::I] = $a[self::S] = 0;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function truncateHour()
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::I] = $a[self::S] = 0;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function truncateMin()
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::S] = 0;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function addYear($v)
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::Y] += $v;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function addMonth($v)
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::M] += $v;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function addDay($v)
	{
		$a = self::splitDateTime($this->getUT());
		$a[self::D] += $v;
		$this->setUT(self::makeDateTime($a));
		return $this;
	}

	public function addHour($v)
	{
		$this->setUT($this->getUT() + (60 * 60) * $v);
		return $this;
	}

	public function addMin($v)
	{
		$this->setUT($this->getUT() + 60 * $v);
		return $this;
	}

	public function addSec($v)
	{
		$this->setUT($this->getUT() + 60 * $v);
		return $this;
	}

	public function getUnixTime()
	{
		return $this->getUT();
	}

	public function getByFormat($fmt)
	{
		return date($fmt, $this->getUT());
	}

	public function getYMDHISString()
	{
		return $this->getByFormat("Y-m-d H:i:s");
	}

	public function getYMDString()
	{
		return $this->getByFormat("Y-m-d");
	}

	public function getYMString()
	{
		return $this->getByFormat("Y-m");
	}

	public function getHISString()
	{
		return $this->getByFormat("H:i:s");
	}

	public function getHIString()
	{
		return $this->getByFormat("H:i");
	}

	public function getYMIndex()
	{
		$a = self::splitDateTime($this->getUT());
		return $a[self::Y] * 12 + ($a[self::M] - 1);
	}

	public function setYMIndex($v)
	{
		$y = (int)($v / 12);
		$m = ($v % 12) + 1;
		return $this->setYear($y)->setMonth($m)->setDay(1)->setTime(0,0,0);
	}

	public function copyFrom(WGDateTime $from)
	{
		$this->setUnixTime($from->getUnixTime());
		return $this;
	}

	public function compare($exp, WGDateTime $right)
	{
		switch($exp)
		{
			case "==":
				return $this->getUnixTime() ==  $right->getUnixTime();
			case "===":
				return $this->getUnixTime() === $right->getUnixTime();
			case "<":
				return $this->getUnixTime() <   $right->getUnixTime();
			case "<=":
				return $this->getUnixTime() <=  $right->getUnixTime();
			case ">":
				return $this->getUnixTime() >   $right->getUnixTime();
			case ">=":
				return $this->getUnixTime() >=  $right->getUnixTime();
			case "!=":
				return $this->getUnixTime() !=  $right->getUnixTime();
			case "!==":
				return $this->getUnixTime() !== $right->getUnixTime();
		}
		throw new Exception("WGDateTime::compare に不正な比較演算子があります。");
	}

	public function debug()
	{
		echo $this->getYMDHISString() . "\n";
		return $this;
	}
}
