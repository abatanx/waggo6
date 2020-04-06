<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGMobileDevice
{
	const C_UNKNOWN=0;
	const C_DOCOMO=0x0100, C_AU=0x0200, C_SOFTBANK=0x0400, C_WILLCOM=0x0800, C_EMOBILE=0x1000;

	const D_NORMAL=0;
	const D_DOCOMO10=0x01, D_DOCOMO20=0x02;
	const D_IPHONE=0x03;

	const I_NONE=0, I_ZEN=1, I_HAN=2, I_ANK=3, I_NUM=4;

	private $carrier;
	private $ua = array(
		array(self::C_DOCOMO,  self::D_DOCOMO10,'/^DoCoMo\/1\.0/'),
		array(self::C_DOCOMO,  self::D_DOCOMO20,'/^DoCoMo\/2\.0/'),
		array(self::C_AU,      self::D_NORMAL  ,'/(^KDDI|^UP\.Browser)/'),
		array(self::C_SOFTBANK,self::D_NORMAL  ,'/(^SoftBank|^Vodafone|^J\-PHONE)/')
	);
	private $im = array(
		self::C_DOCOMO   => array("istyle",array(null,"1","2","3","4")),
		self::C_AU       => array("format",array(null,"*M","*x","*x","*N")),
		self::C_SOFTBANK => array("mode",  array(null,"hiragana","hankakukana","alphabet","numeric"))
	);

	public function __construct()
	{
		$this->carrier = self::C_UNKNOWN;
		foreach($this->ua as $ua)
		{
			if(preg_match($ua[2],$_SERVER["HTTP_USER_AGENT"]))
			{
				$this->carrier = $ua[0] | $ua[1];
				break;
			}
		}
	}

	public function getCarrier() { return $this->carrier & 0xff00; }
	public function getDevice()  { return $this->carrier & 0x00ff; }
	public function inputModeParam($inputmode=self::I_NONE)
	{
		$c = $this->getCarrier();
		if(!is_array($this->im[$c])) return array();
		return array($this->im[$c][0] => $this->im[$c][1][$inputmode]);
	}
}
