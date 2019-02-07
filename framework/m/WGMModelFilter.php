<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class WGMModelFilter
{
	public function __construct(){}
	public function input($v) { return $v; }
	public function output($v) { return $v; }
	public function modelToView($obj,$v) { $obj->setValue($v); }
	public function viewToModel($obj) { return $obj->getValue(); }
}
