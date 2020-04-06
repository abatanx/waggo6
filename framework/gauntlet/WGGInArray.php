<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGInArray extends WGG
{
	protected $valid_array;

	public static function _($valid_array)
	{
		return new static($valid_array);
	}

	public function __construct($valid_array)
	{
		parent::__construct();
		$this->valid_array = $valid_array;
	}

	public function makeErrorMessage()
	{
		return sprintf("入力を確認してください。");
	}

	public function validate(&$data)
	{
		if( in_array($data, $this->valid_array) )
		{
			return true;
		}
		else
		{
			if( !$this->isBranch() )
				$this->setError($this->makeErrorMessage());
			return false;
		}
	}
}

