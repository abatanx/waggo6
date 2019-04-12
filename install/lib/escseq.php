<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function cls()
{
	echo "\x1b[2J\x1b[1;1H";
}

function locate($x,$y)
{
	printf("\x1b[%d;%dH", $y+1, $x+1);
}

function stdin()
{
	$in = fgets(STDIN);
	return $in !== false ? trim($in) : die("\n");
}

function q($msg,$a=array())
{
	do {
		echo $msg;
		$s = stdin();
	}
	while(!in_array($s,$a));
	return $s;
}

function a($msg)
{
	echo $msg;
	stdin();
}

function in($msg)
{
	echo $msg;
	return stdin();
}

function indef($msg,$default,$require)
{
	do {
		echo $msg;
		$s = stdin();
		$r = empty($s) ? $default : $s;
	}
	while($require && empty($r));
	return $r;
}
