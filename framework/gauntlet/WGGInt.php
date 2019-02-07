<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGInt extends WGG
{
	protected $min,$max;

	public static function _($min=0,$max=255)
	{
		return new static($min,$max);
	}

	public function __construct($min=0,$max=2147483647)
	{
		parent::__construct();
		$this->min = $min;
		$this->max = $max;
	}

	public function makeErrorMessage()
	{
		return sprintf("%d〜%d の数値で入力してください。", $this->min, $this->max);
	}
	
	public function validate(&$data)
	{
		if( preg_match('/^\-?[0-9]+$/',$data) )
		{
			$n = (int)$data;
			if( $n >= $this->min && $n <= $this->max )
			{
				$data = $n;
				return true;
			}
			else
			{
				if( !$this->isBranch() )
					$this->setError($this->makeErrorMessage());
				return false;
			}
		}
		else
		{
			if( !$this->isBranch() )
				$this->setError($this->makeErrorMessage());
			return false;
		}
	}
}

