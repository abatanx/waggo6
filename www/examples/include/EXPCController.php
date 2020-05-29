<?php
/**
 * PC Controller for application.
 */

require_once( WGCONF_DIR_FRAMEWORK_CONTROLLER . "/WGFPCController.php" );

class EXPCController extends WGFPCController
{
	public function __construct()
	{
		parent::__construct();
		$this->appCanvas->setTemplate( WGCONF_DIR_WAGGO . '/initdata/tpl/pcroot.html' );
	}
}
