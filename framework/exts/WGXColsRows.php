<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGXColsRows
{
	var $n=0, $t_row, $t_col, $max_cols, $percent=array();
	public function __construct($cols)
	{
		$this->max_cols = $cols;
		$t = 0;
		$u = 0;
		for($i=0; $i<$cols-1; $i++)
		{
			$p = (100*($i+1)/$this->max_cols);
			$t = (int)($p - $u + 0.5);
			$this->percent[$i] = $t;
			$u += $t;
		}
		$this->calc();
	}

	public function calc()
	{
		$this->t_row = (int)($this->n / $this->max_cols);
		$this->t_col = $this->n % $this->max_cols;
	}

	public function row()		{ return $this->t_row;						}
	public function col()		{ return $this->t_col;						}
	public function percent()	{ return $this->percent[$this->t_col];		}
	public function reset()		{ $this->n = 0;								}
	public function next()		{ $this->n++;								}

	public function begin()		{ $this->calc();							}
	public function end()		{ $this->next();							}

	public function hasRest()
	{
		if( $this->n==0 ) return false;
		else
		{
			$this->t_col ++;
			if( $this->t_col < $this->max_cols ) return true;
			return false;
		}
	}
}
