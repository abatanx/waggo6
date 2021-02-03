<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicCheckElement extends WGV6BasicElement
{
	public function postCopy()
	{
		parent::postCopy();

		if(isset($_POST[$this->getKey()]))
		{
			$v = strtolower($_POST[$this->getKey()]);
			if( $v==false || $v==='' || $v==='off' || $v==='false' || $v==='0' )
			{
				$this->setValue(false);
			}
			else
			{
				$this->setValue(true);
			}
		}
		$this->filterGauntlet();

		return $this;
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
			$c->runJS("\$('#{$id}, #{$id}-init').attr({disabled:'disabled'});", $x);
		}
	}

	public function publish()
	{
		$a = $this->getValue() == false ?
			[] : ['checked' => 'checked']
		;
		$i = $this->controller->inputType == WGFController::SHOWHTML ?
			[] : ['init'    => sprintf('<input type="hidden" id="%s" name="%s" value="">', $this->getId().'-init' , $this->getName()) ]
		;

		// value は on が送信されるか否か(checkedに依存する)であるので、valueとしては'on'を適用する。
		$p = parent::publish() + $a + $i;
		$p['value'] = 'on';

		return $p;
	}
}
