<?php

require_once 'waggo_example.php';

class EXA extends EXPCController
{
	protected function views()
	{
		return [
			'call'	=>	new WGV6BasicSubmit(),
			'val'	=>	new WGV6BasicElement()
		];
	}

	protected function _call()
	{
		$this->call('ret_by_b', 'b.php', $this->view('val')->getValue());
	}

	protected function ret_by_b($val)
	{
		if( $val !== false ) $this->view('val')->setValue($val);
	}
}

EXA::START();
