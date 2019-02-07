<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicSelectElement extends WGV6BasicElement
{
	protected $options;
	protected $isPostCheck;

	public function __construct()
	{
		parent::__construct();
		$this->options     = [];
		$this->isPostCheck = true;
	}

	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
	}

	public function setPostCheck($flag)
	{
		$this->isPostCheck = $flag;
		return $this;
	}

	public function isPostCheck()
	{
		return $this->isPostCheck;
	}

	public function postCopy()
	{
		if(isset($_POST[$this->getKey()]))
		{
			if( $this->isPostCheck )
			{
				$skeys = [];
				foreach( array_keys($this->options) as $k ) $skeys[] = (string) $k;

				$v = (string) $_POST[$this->getKey()];
				if( in_array($v, $skeys, true) )
				{
					$this->setValue($v);
				}
				else
				{
					$this->setValue(false);
				}
			}
			else
			{
				$v = (string) $_POST[$this->getKey()];
				$this->setValue($v);
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
			$c->runJS("\$('#{$id}').attr({disabled:'disabled'});", $x);
		}
	}

	public function publish()
	{
		$opt = [];
		foreach( $this->options as $k => $v )
		{
			$selected = (string)$this->getValue()===(string)$k ? " selected" : "";
			$opt[] = sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($k), $selected, htmlspecialchars($v));
		}
		return parent::publish() + [ "options" => implode("", $opt) ];
	}
}
