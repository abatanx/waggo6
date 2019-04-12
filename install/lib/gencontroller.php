<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once __DIR__ . '/dircheck.php';

function file_pccontroller($prefix)
{
	$tf = <<<___END___
<?php
/**
 * PC Controller for application.
 */

require_once(WGCONF_DIR_FRAMEWORK_CONTROLLER."/WGFPCController.php");

class {$prefix}PCController extends WGFPCController
{
}

___END___;

	return $tf;
}

function file_xmlcontroller($prefix)
{
	$tf = <<<___END___
<?php
/**
 * XML Controller for application.
 */

require_once(WGCONF_DIR_FRAMEWORK_CONTROLLER."/WGFXMLController.php");

class {$prefix}XMLController extends WGFXMLController
{
}

___END___;

	return $tf;
}

function file_jsoncontroller($prefix)
{
	$tf = <<<___END___
<?php
/**
 * JSON Controller for application.
 */

require_once(WGCONF_DIR_FRAMEWORK_CONTROLLER."/WGFJSONController.php");

class {$prefix}JSONController extends WGFJSONController
{
}

___END___;

	return $tf;
}

function file_hte()
{
	$tf = <<<___END___
<?php
/**
 * HTE htmltemplate encoder
 */
class HTE extends HtmlTemplateEncoder
{
}

___END___;

	return $tf;
}


function install_gencontroller($prefix)
{
	$dirinfo = install_dirinfo();

	$files = array(
		array(	$dirinfo["inc"]."/{$prefix}PCController.php"	,	file_pccontroller($prefix)		),
		array(	$dirinfo["inc"]."/{$prefix}XMLController.php"	,	file_xmlcontroller($prefix)		),
		array(	$dirinfo["inc"]."/{$prefix}JSONController.php"	,	file_jsoncontroller($prefix)	),
		array(	$dirinfo["inc"]."/HTE.php"						,	file_hte()						)
	);

	foreach($files as $file)
	{
		clearstatcache();
		if(!file_exists($file[0])) file_put_contents($file[0], $file[1]);
		clearstatcache();
	}
}
