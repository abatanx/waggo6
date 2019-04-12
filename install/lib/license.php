<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function install_license()
{
	echo file_get_contents( __DIR__ . '/../../LICENSE' );
	echo "\n\n";
	return q("上記のライセンス条項に同意しますか (Yes/No) -> ", array("Yes","No"))==="Yes" ? true : false;
}

