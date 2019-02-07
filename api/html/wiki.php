<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class WGHtmlWiki
{
	protected $patterns;
	protected $wiki;

	private $quote;
	private $quote_seq;

	public function __construct($wiki=null)
	{
		$this->patterns = array();
		$this->setWiki($wiki);
	}

	public function setWiki($wiki) {
		$this->quote     = array();
		$this->quote_seq = 0;
		$this->wiki      = $wiki;
	}

	public function getWiki() {
		return $this->wiki;
	}

	// QuoteIn
	protected function quoteInCallback($match)
	{
		$this->quote[$this->quote_seq] = $match[1];
		$out = sprintf("'&_QT(%d)",$this->quote_seq);
		$this->quote_seq ++;
		return $out;
	}

	public function quoteIn($data)
	{
		return $this->quoteInCallback(array(null,$data));
	}

	// QuoteOut
	protected function quoteOutCallback($m)
	{
		return $this->quote[$m[1]];
	}

	public function quoteOut($in)
	{
		for($i=0; $i<$this->quote_seq; $i++)
			$in = preg_replace_callback('/\'&amp;_QT\((\d+)\)/m',array($this,'quoteOutCallback'),$in);
		return $in;
	}

	protected function pattern($html)
	{
		foreach($this->patterns as $ptn)
		{
			$html = preg_replace_callback($ptn[0],array($this,$ptn[1]),$html);
		}
		return $html;
	}

	public function getHtml()
	{
		$html = preg_replace('/$\r?\n?/',"\n",$this->wiki);
		$html = preg_replace('/\r?\n/',"~~\n",$html);
		$html = $this->pattern($html);

		//wg_log($html);
		//wg_errordump($this->quote);
		$newhtml = "";
		$tmpline = explode("\n",trim($html));
		foreach( $tmpline as $l )
		{
			$newhtml .= htmlspecialchars($l);
		}

		$newhtml = $this->quoteOut($newhtml);
		return $newhtml;
	}
}
