<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function wg_unset_session($regptn)
{
	foreach($_SESSION as $k=>$v)
	{
		if(preg_match($regptn,$k))
		{
			$_SESSION[$k] = null;
			unset($_SESSION[$k]);
		}
	}
}
