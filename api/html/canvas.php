<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once(dirname(__FILE__)."/htmltemplate.php");

abstract class WGCanvas
{
	/**
	 * @var array $html
	 */
	public $html;

	/**
	 * @var string $template
	 */
	public $template;

	public function __construct()
	{
		$this->html     = array();
		$this->template = null;
		if( WG_JSNOCACHE ) $this->html["_nocache"] = "?_nc_=".time();
	}
	public function setTemplate($template) { $this->template = $template; }
	public function getTemplate()          { return $this->template;      }
	abstract function build();
	abstract function buildAndFlush();
}

class WGHtmlCanvas extends WGCanvas
{
	function build()
	{
		return HtmlTemplate::buffer($this->template, $this->html);
	}
	function buildAndFlush()
	{
		HtmlTemplate::include($this->template, $this->html);
	}
}

class WGMobileHtmlCanvas extends WGCanvas
{
	const TO_ENCODING = "SJIS";
	function build()
	{
		return HtmlTemplate::buffer($this->template, $this->html);
	}
	function buildAndFlush()
	{
		$ie = mb_internal_encoding();
		ob_start();
		HtmlTemplate::include($this->template, $this->html);
		$c = mb_convert_encoding(ob_get_contents(),self::TO_ENCODING,$ie);
		ob_end_clean();
		$l = strlen($c);
		wg_log_write(WGLOG_INFO, sprintf("MOBILE TRANSFER SIZE = %dBytes (%.1fKBytes)",$l,$l/1024));
		echo $c;
	}
}

class WGXMLCanvas extends WGCanvas
{
	function build()
	{
		return HtmlTemplate::buffer($this->template, $this->html);
	}
	function buildAndFlush()
	{
		header("Content-Type: text/xml");
		HtmlTemplate::include($this->template, $this->html);
	}
}
