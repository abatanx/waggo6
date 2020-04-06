<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicElement extends WGV6Basic
{
	public function isSubmit()
	{
		return false;
	}

	public function controller( $c )
	{
		/**
		 * @var WGFController $c
		 */
		parent::controller($c);

		$id = htmlspecialchars($this->getId());
		$x  = !$c->isScriptBasedController() ? WGFPCController::RUNJS_ONLOAD : WGFXMLController::RUNJS_ONLOADED;

		if( $this->isLock() || $c->getInputType()==$c::SHOWHTML )
		{
			$c->runJS("\$('#{$id}').attr({disabled:'disabled'});", $x);
		}
	}
}
