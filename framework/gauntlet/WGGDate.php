<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGDate extends WGG
{
	public static function _()
	{
		return new static();
	}

	public function makeErrorMessage()
	{
		return sprintf("有効な日付を入力してください。");
	}

	public function validate(&$data)
	{
		if( wg_datetime_checkdate($data) )
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

