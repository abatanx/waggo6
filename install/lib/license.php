<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

function install_license()
{
	echo file_get_contents( __DIR__ . '/../../LICENSE' );
	echo "\n\n";
	return q("上記のライセンス条項に同意しますか (Yes/No) -> ", array("Yes","No"))==="Yes" ? true : false;
}

