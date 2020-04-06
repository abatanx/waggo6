<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGFilterTrim extends WGG
{
	private $trimZenkakuSpace;

	public static function _($trimZenkakuSpace=false)
	{
		return new static($trimZenkakuSpace);
	}

	public function __construct($trimZenkakuSpace=false)
	{
		parent::__construct();
		$this->trimZenkakuSpace = $trimZenkakuSpace;
	}

	public function makeErrorMessage()
	{
		return '';
	}

	public function isFilter()
	{
		return true;
	}

	public function validate(&$data)
	{
		if( !$this->trimZenkakuSpace )	$data = trim($data);
		else 							$data = trim($data, " \t\n\r\0\x0B　");

		return true;
	}
}

