<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicRadioElement extends WGV6BasicElement
{
	public function controller( $c )
	{
		/**
		 * @var WGFController $c
		 */
		parent::controller($c);

		$nm = htmlspecialchars($this->getName());
		$x  = !$c->isScriptBasedController() ? WGFPCController::RUNJS_ONLOAD : WGFXMLController::RUNJS_ONLOADED;

		if( $this->isLock() || $c->getInputType()==$c::SHOWHTML )
		{
			$c->runJS("\$('input[name=\"{$nm}\"').attr({disabled:'disabled'});", $x);
		}
		$c->runJS("\$('input[name=\"{$nm}\"]').val([".json_encode($this->getValue())."]);", $x);
	}

	public function publish()
	{
		$a = $this->getValue()!=false ? array('checked#'.htmlspecialchars($this->getValue()) => 'checked') : array();
		return parent::publish() + $a;
	}
}
