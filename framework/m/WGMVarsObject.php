<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

abstract class WGMVarsObject
{
	protected $vars, $qvars;
	public function __construct()
	{
		$this->vars  = array();
		$this->qvars = array();
		$this->_array_walk_vars(func_get_args());
		$this->vars  = array_unique($this->vars);
	}
	protected function _array_walk_vars($avars)
	{
		foreach($avars as $avar)
		{
			if(is_array($avar)) $this->_array_walk_vars($avar);
			else $this->vars[] = $avar;
		}
	}
	public function getVars()       { return $this->vars;  }
	public function getQuotedVars() { return $this->qvars; }
	public function setQuotedVars($qvars) { $this->qvars = $qvars; }
	abstract public function getWhereExpression($field);
}

class WGMVOr extends WGMVarsObject
{
	public function getWhereExpression($field)
	{
		if(count($this->qvars)==0) return "";
		return sprintf("(%s in (%s))", $field, implode(",",$this->qvars));
	}
}

class WGMVAnd extends WGMVarsObject
{
	public function getWhereExpression($field)
	{
		if(count($this->qvars)==0) return "";
		$dd = array();
		foreach($this->qvars as $qv) $dd[] = sprintf("%s=%s", $field,$qv);
		return "(".implode(" and ", $dd).")";
	}
}
