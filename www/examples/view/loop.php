<?php

require_once 'waggo_example.php';

class EXLoopView extends EXPCController
{
	protected function create()
	{
	}

	protected function views()
	{
		return [
			'submit' => new WGV6BasicSubmit()
		];
	}

	protected function initFirst()
	{
		$list = [];
		for ( $i = 0; $i < 10; $i ++ )
		{
			$list[] = [
				'a' => mt_rand( 0, 100 ),
				'b' => mt_rand( 0, 100 )
			];
		}
		$this->session->set( 'list', $list );
	}

	protected function init()
	{
		$list = $this->session->get( 'list' );

		foreach ( $list as $idx => $l )
		{
			$key_a = 'a-' . $idx;
			$this->addView( $key_a, new WGV6BasicElement() );

			$key_b = 'b-' . $idx;
			$this->addView( $key_b, new WGV6BasicElement() );

			if ( $this->isFirst() )
			{
				$this->view( $key_a )->setValue( $l['a'] );
				$this->view( $key_b )->setValue( $l['b'] );
			}

			$this->pageCanvas->html['lists'][] = [
				'a' => $key_a,
				'b' => $key_b
			];
		}
	}

	protected function beforeBuild()
	{
		foreach ( $this->pageCanvas->html['lists'] as &$l )
		{
			$a      = $this->view( $l['a'] )->getValue();
			$b      = $this->view( $l['b'] )->getValue();
			$l['c'] = $a + $b;
		}
	}

	protected function input()
	{
		return $this->defaultTemplate();
	}

	protected function _submit()
	{
		return false;
	}
}

EXLoopView::START();
