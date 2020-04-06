<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGV6BasicPaginationState
{
	const
		STATE_INVISIBLE  = 0,		// 表示されない
		STATE_DISABLE    = 1,		// 表示はされるが、押せない
		STATE_ENABLE     = 2,		// 表示され、押せる（非選択）
		STATE_ACTIVE     = 3;		// 表示され、押せない（選択中）

	private $state;
	private $number;
	private $caption;
	private $js;

	public function __construct()
	{
		$this->state   = self::STATE_INVISIBLE;
		$this->number  = "";
		$this->caption = "";
		$this->js      = "";
	}

	public function setNumber($number)
	{
		$this->number  = $number;
	}

	public function setCaption($caption)
	{
		$this->caption = $caption;
	}

	public function isVisible()
	{
		return $this->state !== self::STATE_INVISIBLE;
	}

	public function setInvisible()
	{
		$this->state = self::STATE_INVISIBLE;
	}

	public function setDisable()
	{
		$this->state = self::STATE_DISABLE;
	}

	public function setEnable()
	{
		$this->state = self::STATE_ENABLE;
	}

	public function setActive()
	{
		$this->state = self::STATE_ACTIVE;
	}

	public function setJS($js)
	{
		$this->js = $js;
	}

	private function innerCaption()
	{
		$q = array();
		if( $this->number!=""  ) $q[] = $this->number;
		if( $this->caption!="" ) $q[] = $this->caption;
		return implode(" ", $q);
	}

	public function makeLI()
	{
		switch($this->state)
		{
			case self::STATE_INVISIBLE:
				return '';
			case self::STATE_DISABLE:
				return sprintf('<li class="disable"><a href="javascript:void(0)">%s</a>', $this->innerCaption());
			case self::STATE_ENABLE:
				return sprintf('<li><a href="javascript:void(0)" onclick="%s">%s</a></li>', $this->js, $this->innerCaption());
			case self::STATE_ACTIVE:
				return sprintf('<li class="active"><a href="javascript:void(0)" onclick="%s">%s</a></li>', $this->js, $this->innerCaption());
			default:
				return "";
		}
	}
	public function makeButton()
	{
		switch($this->state)
		{
			case self::STATE_INVISIBLE:
				return '';
			case self::STATE_DISABLE:
				return sprintf('<button type="button" class="btn btn-default disabled">%s</button>', $this->innerCaption());
			case self::STATE_ENABLE:
				return sprintf('<button type="button" class="btn btn-default" onclick="%s">%s</button>', $this->js, $this->innerCaption());
			case self::STATE_ACTIVE:
				return sprintf('<button type="button" class="btn btn-primary" onclick="%s">%s</button>', $this->js, $this->innerCaption());
			default:
				return "";
		}
	}
}

class WGV6BasicPagination extends WGV6Object
{
	private
		$limit     = 0,
		$count     = 0,
		$total     = 0,
		$page      = 0,
		$length    = 3;

	protected
		$limitList;

	protected
		$pageKey,
		$limitKey;

	public function __construct($limit,$pagekey="wgpp",$limitkey="wgpl")
	{
		parent::__construct();
		if(!is_numeric($limit)) die("WGV6BasicPagination, Invalid limit parameter, '{$limit}'.\n");

		foreach( $this->pagingLineNums() as $n )
		{
			$this->limitList[$n] = sprintf("%d件ずつ表示", $n);
		}

		$this->pageKey  = $pagekey;
		$this->limitKey = $limitkey;

		$this->page     = (!wg_inchk_int($this->page ,$_GET[$this->pageKey],1))  ? 1      : $this->page   ;
		$this->limit    = (!wg_inchk_int($this->limit,$_GET[$this->limitKey],1)) ? $limit : $this->limit  ;

		if( !in_array($this->limit, array_keys($this->limitList)) ) $this->limit = $this->pagingLineNums()[0];
	}

	public function pagingLineNums()
	{
	  return [10,50,100,500];
	}

	public function js($page,$remakeopts="")
	{
		$u   = wg_remake_uri(array( $this->pageKey =>$page, $this->limitKey =>$this->limit));
		return "WG6.get('#'+$(this).closest('.wg-form').attr('id'),WG6.remakeURI('{$u}',{{$remakeopts}}));";
	}

	public function setPagerLength($len)
	{
		$this->length = $len;
	}

	public function offset()
	{
		$offset = ($this->page - 1) * $this->limit;
		return $offset;
	}

	public function limit()
	{
		$limit = $this->limit;
		return $limit;
	}

	public function setTotal($total)
	{
		$this->total = $total;

		// ページ数チェック
		$mp = (int)(($this->total-1) / $this->limit) + 1;
		if( $this->page < 1   ) $this->page = 1;
		if( $this->page > $mp ) $this->page = $mp;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function isFirstPage()
	{
		return ($this->page<=1);
	}

	public function isLastPage()
	{
		$mp = (int)(($this->total-1) / $this->limit) + 1;
		return ($this->page>=$mp);
	}

	public function count()
	{
		$this->count ++;
		return $this->limit * ($this->page - 1) + $this->count;
	}

	public function countRevert()
	{
		$this->count ++;
		return $this->total - ($this->limit * ($this->page - 1) + $this->count) + 1;
	}

	protected function firstCaption() { return ""; }
	protected function lastCaption()  { return ""; }
	protected function allCaption()   { return ""; }

	public function formHtml()
	{
		return $this->showHtml();
	}

	public function showHtml()
	{
		if($this->total==0) return "";

		$max_page = (int)(($this->total-1)/$this->limit) + 1;
		$tags = array();

		// 初期化
		for( $p=1 ; $p<=$max_page ; $p++ )
		{
			$tags[$p] = new WGV6BasicPaginationState();
			$tags[$p]->setNumber(number_format($p));
			$tags[$p]->setJS( $this->js($p) );
		}

		// 現在のページの前後を「表示」に変更。
		for( $p  = $this->page - $this->length ;
			 $p <= $this->page + $this->length ;
			 $p ++ )
		{
			if( $p>=1 && $p<=$max_page ) $tags[$p]->setEnable();
		}

		// 最初のページ最後のページを、「表示」に変更
		$tags[1]->setEnable();
		$tags[$max_page]->setEnable();

		// 現在のページ
		if( isset($tags[$this->page]) ) $tags[$this->page]->setActive();

		// ページ数が複数ある場合
		if( $max_page > 1 )
		{
			// 「最初」「最後」のキャプションをセット
			$tags[1]->setCaption($this->firstCaption());
			$tags[$max_page]->setCaption($this->lastCaption());
		}
		// ページ数が１ページにおさまった場合
		else
		{
			// 「全部」をセット
			$tags[1]->setCaption($this->allCaption());
		}

		// 前後
		$prevtags = array();
		$nexttags = array();

		$lt = '<span aria-hidden="true">&laquo;</span>';
		$gt = '<span aria-hidden="true">&raquo;</span>';

		/**
		 * Prev page
		 */
		$pt = new WGV6BasicPaginationState();
		$pt->setNumber($lt);

		if( $this->page > 1 )
		{
			// 前に行ける
			$pt->setEnable();
			$pt->setJS($this->js($this->page - 1));
		}
		else
		{
			$pt->setDisable();
		}
		$prevtags[] = $pt;

		/**
		 * Next page
		 */
		$pt = new WGV6BasicPaginationState();
		$pt->setNumber($gt);

		if( $this->page < $max_page )
		{
			// 次へ行ける
			$pt->setEnable();
			$pt->setJS($this->js($this->page + 1));
		}
		else
		{
			$pt->setDisable();
		}
		$nexttags[] = $pt;

		/**
		 *  Skip page
		 */
		$alltags = array();
		$is_visible = false;

		/**
		 * @var WGV6BasicPaginationState $tag
		 */
		foreach( array_merge($prevtags, $nexttags, $tags) as $tag )
		{
			if( !$tag->isVisible() )
			{
				if( $is_visible )
				{
					$pt = new WGV6BasicPaginationState();
					$pt->setNumber("...");
					$pt->setDisable();

					$alltags[] = $pt;
				}
			}
			else
			{
				$alltags[] = $tag;
			}
			$is_visible = $tag->isVisible();
		}

		/**
		 * Rendering
		 */
		$li = array();
		foreach( $alltags as $tag ) $li[] = $tag->makeButton();
		$body = implode("\n", $li);

		$li = array();
		foreach( $this->limitList as $k=>$c)
		{
			$active = ((int)$k === (int)$this->limit) ? "active" : "";
			$li[] = sprintf(
				'<li role="presentation" class="%s"><a role="menuitem" tabindex="-1" href="javascript:void(0)" data-value="%s" data-caption="%s" onclick="%s">%s</a></li>',
				$active,
				htmlspecialchars($k), htmlspecialchars($c), $this->js(1, "{$this->limitKey}:{$k}"),
				htmlspecialchars($c)
			);
		}
		$cap = htmlspecialchars($this->limitList[$this->limit]);
		$lis = implode("\n",$li);

		if( $this->total > 0 )
		{
			$totalcap = sprintf('<span class="badge">%s件</span>', number_format($this->total));
		}
		else
		{
			$totalcap = '<span class="badge">データなし</span>';
		}

		/**
		 * view id
		 */
		$id = $this->getId();

		return <<<___END___
<nav>
	<div class="form-group">
		<div class="form-inline">
			<div id="{$id}_pagination_ul">
				<div class="btn-group">{$body}</div>
			</div>
			
			<div id="{$id}_pagination_dropdown" class="dropdown">
				<button class="btn btn-default" type="button" id="{$id}_toggle" data-toggle="dropdown">
					<span id="{$id}_caption">{$cap}</span> <span class="caret"></span>
				</button>
				<ul id="{$id}_ul" class="dropdown-menu" role="menu" aria-labelledby="{$id}_toggle">
			{$lis}
				</ul>
				{$totalcap}
			</div>
			
		</div>
	</div>
</nav>
<style>#{$id}_pagination_ul,#{$id}_pagination_dropdown { display:inline-block; }</style>

___END___;
	}
}
