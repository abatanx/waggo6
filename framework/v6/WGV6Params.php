<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGV6Params
{
	public $params, $errstyle;
	public function __construct() { $this->params=array(); $this->errstyle=""; }
	public function add($kv) { foreach($kv as $k=>$v)$this->params[$k]=$v; }
	public function clear() { $this->params=array(); }
	public function delete($k) { unset($this->params[$k]);}
	public function get($k) { return $this->params[$k]; }

	public function setErrorStyle($e) { $this->errstyle = $e; }
	public function clearErrorStyle() { $this->errstyle = ""; }

	public function toString() {
		$tmp = $this->params;
		if( isset($tmp["style"]) ) $tmp["style"] .= $this->errstyle;	// errだけ別枠。
		if(empty($tmp["style"])) unset($tmp["style"]);
		$str = "";

		foreach($tmp as $k=>$v)
		{
			$str.= empty($v) ?
				sprintf("%s ",htmlspecialchars($k)) :
				sprintf("%s=\"%s\" ",htmlspecialchars($k),addcslashes($v,'"')) ;
		}
		return (trim($str)=="") ? "" : (" ".trim($str));
	}
}
