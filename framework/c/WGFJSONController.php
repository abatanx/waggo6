<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once(dirname(__FILE__)."/WGFController.php");

class WGFJSONController extends WGFController
{
	/**
	 * @var stdClass $jsonCanvas
	 */
	protected $jsonCanvas;
	protected $jsonOption;
	protected $jsonDepth;

	public function __construct()
	{
		parent::__construct();

		$this->jsonCanvas = new stdClass;
		$this->jsonOption = 0;
		$this->jsonDepth  = 512;
	}

	public function isScriptBasedController()
	{
		return false;
	}

	public function runJS($javascript,$event)
	{
		$this->abort('WGFJSONController does not support runJS method.');
	}

	public function runParts($selector,$url,$event)
	{
		$this->abort('WGFJSONController does not support runParts method.');
	}

	/**
	 * JSON出力
	 *
	 * @param mixed $data レスポンス用JSONインスタンス
	 */
	protected function renderJSON($data)
	{
		$response = json_encode($data, $this->jsonOption, $this->jsonDepth);
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Length: ' . strlen($response));

		echo $response;
	}

	protected function rollbackAndAbort($msg=false)
	{
		_QROLLBACK();
		$this->abort($msg);
	}

	protected function abort($msg=false)
	{
		http_response_code(500);
		$this->renderJSON($msg);
		exit;
	}

	protected function render()
	{
		$this->renderJSON($this->jsonCanvas);
	}

	protected function renderAndExit()
	{
		$this->render();
		exit;
	}
}
