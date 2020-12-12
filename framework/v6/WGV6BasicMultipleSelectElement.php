<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once dirname(__FILE__)."/WGV6Object.php";

class WGV6BasicMultipleSelectElement extends WGV6BasicSelectElement
{
	public function setValue($v)
	{
		if( $v === false || is_null($v) ) parent::setValue([]);
		else if( !is_array($v) )          parent::setValue([$v]);
		else parent::setValue($v);
	}

	public function getValue()
	{
		$v = parent::getValue();
		if( $v === false || is_null($v) ) return [];
		else if( !is_array($v) )          return [$v];
		else return $v;
	}

	public function postCopy()
	{
		if(isset($_POST[$this->getKey()]))
		{
			$postValue = $_POST[$this->getKey()];
			if( $postValue === false || is_null($postValue) ) $vs = [];
			else if( !is_array($postValue) )                  $vs = [$postValue];
			else $vs = $postValue;

			if( $this->isPostCheck )
			{
				$rs = [];

				$skeys = [];
				foreach( array_keys($this->options) as $k ) $skeys[] = (string) $k;

				foreach( $vs as $vp )
				{
					$v = (string) $vp;
					if( in_array($v, $skeys, true) )
					{
						$rs[] = $v;
					}
				}
				$this->setValue($rs);
			}
			else
			{
				$this->setValue($vs);
			}
		}
		$this->filterGauntlet();

		return $this;
	}

	public function publish()
	{
		$checkes = array_map(function($v){ return (string)$v; }, $this->getValue());

		$opt = [];
		foreach( $this->options as $k => $v )
		{
			$selected = in_array((string)$k, $checkes, true) ? " selected" : "";
			$opt[] = sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($k), $selected, htmlspecialchars($v));
		}

		return
			array(
				'id'		=> $this->getId(),
				"name"		=> $this->getKey() . '[]',
				'value'		=> false,
				'error'		=> htmlspecialchars($this->getError(), ENT_QUOTES | ENT_HTML5),
				'rawValue'	=> $this->getValue(),
				'rawError'	=> $this->getValue(),
				'params'	=> $this->params->toString(),
				'options'	=> implode("", $opt)
			);
	}
}
