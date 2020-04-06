<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/WGG.php';

class WGGFilterAnk extends WGG
{
	private $convertKanaParam;

	public static function _($convertKanaParam="KVas")
	{
		return new static($convertKanaParam);
	}

	public function __construct($convertKanaParam="KVas")
	{
		parent::__construct();
		$this->convertKanaParam = $convertKanaParam;
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
		$data = mb_convert_kana($data, $this->convertKanaParam);
		return true;
	}
}

