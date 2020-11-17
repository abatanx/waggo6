<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGInArrayStrict extends WGGInArray
{
	public function validate(&$data)
	{
		$d = strval( $data );
		$a = array_map(function($v) { return strval($v); }, $this->valid_array);

		if( in_array($d, $a, true) )
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

