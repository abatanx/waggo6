<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once(dirname(__FILE__)."/WGFController.php");
require_once(dirname(__FILE__)."/../../api/mobile/mobile.php");

/**
 * 携帯電話用コントローラ
 */
class WGFMobileController extends WGFController
{
	public function __construct()
	{
		parent::__construct();
		$this->appCanvas->setTemplate(WGCONF_DIR_TPL."/iroot.html");
	}

	protected function startSession()
	{
		session_cache_limiter('nocache');
		wg_mobile_input_encoding_filter();
		wg_mobile_session();

		wg_errorlog("[[[ MOBILE SESSION STARTED ]]]");
	}

	protected function initCanvas()
	{
		$this->appCanvas  = new WGMobileHtmlCanvas();
		$this->pageCanvas = new WGMobileHtmlCanvas();
	}

	public function runJS( $key, $javascript )
	{
		return "";
	}

	public function isScriptBasedController()
	{
		return false;
	}
}
