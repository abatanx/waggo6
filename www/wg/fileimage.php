<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/../../waggo.php";
require_once dirname(__FILE__)."/../../framework/c/WGFSession.php";
session_start();

$f = true;
$f &= wg_inchk_string($ssid,$_GET["ssid"],1,32);
$f &= wg_inchk_string($trid,$_GET["trid"],1,32);
$f &= wg_inchk_string($vk,$_GET["vk"],1,32);
if(!$f) die();

$v = new WGVFile();
$v->session = new WGFSession($ssid, $trid);
$v->setKey($vk);
$v->init();

if($v->isImage() && $v->getValue())
{
	Header("Content-Type: ".$v->mimetype());
	@readfile($v->getFullpath());
}
