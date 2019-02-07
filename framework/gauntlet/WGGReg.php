<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGReg extends WGG
{
	protected $regex;

	public static function _($regex)
	{
		return new static($regex);
	}

	public function __construct($regex)
	{
		parent::__construct();
		$this->regex = $regex;
	}

	public function makeErrorMessage()
	{
		return '入力内容を見直してください';
	}
	
	public function validate(&$data)
	{
		if( preg_match($this->regex, $data) )
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

