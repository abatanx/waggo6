<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGEmpty extends WGG
{
	public static function _()
	{
		return new static();
	}

	public function makeErrorMessage()
	{
		return sprintf("内容が空ではありません。");
	}

	public function validate(&$data)
	{
		if( strlen($data) === 0 )
		{
			return true;
		}
		else
		{
			if( !$this->isBranch() )
				$this->addError($this->makeErrorMessage());
			return false;
		}
	}
}

