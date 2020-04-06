<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicSubmit extends WGV6Basic
{
	public function isSubmit()
	{
		return true;
	}

	public function controller( $c )
	{
		/**
		 * @var WGFController $c
		 */
		parent::controller($c);

		$id = htmlspecialchars($this->getId());
		$x  = !$c->isScriptBasedController() ? WGFPCController::RUNJS_ONLOAD : WGFXMLController::RUNJS_ONLOADED;

		if( $this->isLock() )
		{
			$c->runJS("\$('#{$id}').attr({disabled:'disabled'});", $x);
		}
		else
		{
			if( !$c->isScriptBasedController() )
			{
				$c->runJS(
					"\$('#{$id}').click(function(){".
					"\$(this).after(\$('<input>').attr({type:'hidden',value:$(this).val(),name:\$(this).attr('name')}));".
					"\$(this).closest('form').submit();});", $x
				);
			}
			else
			{
				$c->runJS(
					"\$('#{$id}').click(function(){".
					"\$(this).after(\$('<input>').attr({type:'hidden',value:$(this).val(),name:\$(this).attr('name')}));".
					"WG6.post(WG6.closestForm(\$(this)),'{$c->getNextURL()}');});", $x
				);
			}
		}
	}
}
