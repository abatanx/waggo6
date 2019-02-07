<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

abstract class WGXPager
{
	private
		$has_prior = false,
		$has_next  = false,
		$offset    = 0,
		$limit     = 0,
		$count     = 0,
		$total     = 0,
		$page      = 0,
		$postmode  = false,
		$postid    = "",
		$postform  = null,
		$length    = 2,
		$is_directinput = false;
	protected
		$pagekey   = "page";

	public function __construct($limit,$pagekey="page")
	{
		if(!is_numeric($limit)) die("WGXPager, Invalid limit parameter, '{$limit}'.\n");

		$this->limit   = $limit;
		$this->pagekey = $pagekey;
		$this->page    = (!wg_inchk_int($this->page,$_GET[$this->pagekey],1,100000)) ? 0 : $this->page-1 ;
	}

	abstract public function js($page);

	public function setPagerLength($len)
	{
		$this->length = $len;
	}

	public function setDirectInputMode($f)
	{
		$this->is_directinput = $f;
	}

	public function offset()
	{
		$offset = $this->page * $this->limit;
		return $offset;
	}

	public function limit()
	{
		$limit = $this->limit;
		return $limit + 1;
	}

	public function setTotal($count)
	{
		$this->total = $count;
	}

	public function getPage()
	{
		return $this->page+1;
	}

	public function isFirstPage()
	{
		return ($this->page<=0);
	}

	public function isLastPage()
	{
		$mp = (int)(($this->total-1)/$this->limit);
		return ($this->page>=$mp);
	}

	public function isLimit()
	{
		$this->count ++;
		if( $this->count >= $this->limit+1 )
		{
			$this->has_next = true;
			return true;
		}
		return false;
	}

	public function count()
	{
		return $this->limit * $this->page + $this->count;
	}

	public function countRevert()
	{
		return $this->total - ($this->limit * $this->page + $this->count) + 1;
	}

	protected function firstCaption() { return "最初"; }
	protected function lastCaption()  { return "最後"; }
	protected function allCaption()   { return "全部"; }

	public function build()
	{
		if($this->total==0) return "<!-- NO PAGER SERVICE -->";

		$tags = array();

		$mp = (int)(($this->total-1)/$this->limit) + 1;
		$pf = array();
		for($p=0; $p<$mp; $p++) $pf[$p] = array(0,"");

		if($mp-1!=0)
		{
			$pf[0]     = array(1,":".$this->firstCaption());
			$pf[$mp-1] = array(1,":".$this->lastCaption());
		}
		else
		{
			$pf[0]     = array(1,":".$this->allCaption());
		}

		$np = $this->page;
		if($np<0) $np = 0;
		for($p=$np-$this->length;$p<=$np+$this->length;$p++) if($p>=0 && $p<$mp) $pf[$p][0] = 1;
		$pf[$np][0] = 2;

		$bf = false;
		foreach($pf as $p=>$n)
		{
			switch($n[0])
			{
				case 2:
					$bf=false;
					$tags[] = sprintf("<span class=\"pgact\">%d%s</span>",$p+1,$n[1]); break;
				case 1:
					$bf=false;
					$js = $this->js($p+1);
					$tags[] = sprintf("<span class=\"pginact\" onclick=\"%s\">%d%s</span>",$js,$p+1,$n[1]);
					break;
				case 0:
					if(!$bf)
					{
						$bf=true;
						$tags[] = "～<wbr>";
					}
					break;
			}
		}

		$tags[] = sprintf('<wbr><strong style="color:black;">%s</strong>件<wbr>', number_format($this->total));

		if($mp>1)
		{
			$tags[] = "<br>";

			$symbol = "&lt;&lt;";
			$tags[] = ($this->page>0) ?
				sprintf("<span class=\"pginact\" onclick=\"%s\">{$symbol}</span>",$this->js($this->page-1+1)) :
				sprintf("<span class=\"pgdisable\">{$symbol}</span>") ;

			$tags[] = sprintf(
				'<input type="text" size="3" maxlength="5" value="%d"'.
				' style="text-align:center;" onkeydown="if(IsEnter())alert(this.value);return false;">', $this->page+1);

			$symbol = "&gt;&gt;";
			$tags[] = ($this->page<$mp-1) ?
				sprintf("<span class=\"pginact\" onclick=\"%s\">{$symbol}</span>",$this->js($this->page+1+1)) :
				sprintf("<span class=\"pgdisable\">{$symbol}</span>") ;
		}

		return (count($tags)!=1) ? implode("",$tags) : "";
	}
}
