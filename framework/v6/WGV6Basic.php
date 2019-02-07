<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6Basic extends WGV6Object
{
	public function postCopy()
	{
		parent::postCopy();

		if(isset($_POST[$this->getKey()]))$this->setValue($_POST[$this->getKey()]);
		$this->filterGauntlet();

		return $this;
	}

	public function controller( $c )
	{
		/**
		 * @var WGFController $c
		 */
		parent::controller($c);

		return $this;
	}
}
