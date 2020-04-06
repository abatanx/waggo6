<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once(dirname(__FILE__)."/WGFController.php");

class WGFXMLController extends WGFController
{
	public function __construct()
	{
		parent::__construct();
		$this->appCanvas->setTemplate(WGCONF_DIR_TPL."/pcroot.xml");
	}

	public function isScriptBasedController()
	{
		return true;
	}

	/**
	 * JavaScript実行用仮想メソッド。
	 * @param string $javascript スクリプト
	 * @param string $event 実行タイミングイベント (self::RUNJS_ONPRELOAD, self::RUNJS_ONLOADED)
	 * @return string キー
	 */
	const
		RUNJS_ONPRELOAD = 'onpreload',
		RUNJS_ONLOADED  = 'onloaded';

	public function runJS($javascript,$event = self::RUNJS_ONLOADED)
	{
		$keyseq = $this->getKeySeq("js-");
		$this->appCanvas->html["script"][] =
			array(
				"key"	=>	$keyseq,
				"event"	=>	$event,
				"src"	=>	$javascript
			);
		return $keyseq;
	}

	/**
	 * @inheritdoc
	 */
	public function runParts($selector,$url,$event = self::RUNJS_ONLOADED)
	{
		return $this->runJS("WG6.get('{$selector}','$url');", $event);
	}

	protected function newPage($url)
	{
		$this->runJS("window.location='{$url}';", self::RUNJS_ONPRELOAD);
	}

	protected function render()
	{
		if(!is_null($this->pageCanvas->getTemplate()))
		{
			$this->appCanvas->html['action'] = wg_remake_uri();
			$this->appCanvas->html['contents'] = $this->pageCanvas->build();
			$this->appCanvas->html['has_contents'] = "t";
		}

		header("Content-Type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
		$this->appCanvas->buildAndFlush();
	}
}
