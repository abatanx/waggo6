<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function t_cls()
{
	echo "\x1b[2J\x1b[1;1H";
}

function t_locate($x,$y)
{
	$x++;
	$y++;
	echo "\x1b[{$y};{$x}H";
}

function q($msg,$a=array())
{
	do {
		echo $msg;
		$s = trim(fgets(STDIN));
	}
	while(!in_array($s,$a));
	return $s;
}

function a($msg)
{
	echo $msg;
	fgets(STDIN);
}

function in($msg)
{
	echo $msg;
	$s = trim(fgets(STDIN));
	return $s;
}

function indef($msg,$default,$require)
{
	do {
		echo $msg;
		$s = trim(fgets(STDIN));
		$r = empty($s) ? $default : $s;
	}
	while($require && empty($r));
	return $r;
}
