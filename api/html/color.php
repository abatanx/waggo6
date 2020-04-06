<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

class WGHtmlColor
{
	protected $r,$g,$b;

	private function check()
	{
		$this->r = $this->r<0 ? 0 : $this->r>255 ? 255 : (int)$this->r;
		$this->g = $this->g<0 ? 0 : $this->g>255 ? 255 : (int)$this->g;
		$this->b = $this->b<0 ? 0 : $this->b>255 ? 255 : (int)$this->b;
	}

	public function getArrayRGB()
	{
		return array($this->r, $this->g, $this->b);
	}

	public function getHexRGB()
	{
		return sprintf("%02x%02x%02x", $this->r, $this->g, $this->b);
	}

	public function getHtmlRGB()
	{
		return sprintf("#%02x%02x%02x", $this->r, $this->g, $this->b);
	}

	public function setRGB($r,$g,$b)
	{
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
		$this->check();
	}

	/**
	 * HSVカラーモデルで、色をセットする。
	 * @param int $h 色相(0～359)。範囲外は該当する色相に計算します。
	 * @param int $s 彩度(0～255)。範囲外は範囲内にトリミングします。
	 * @param int $v 明度(0～255)。範囲外は範囲内にトリミングします。
	 */
	public function setHSV($h,$s,$v)
	{
		$this->r = $this->g = $this->b = 0;
		$h = (360 - (-$h % 360)) % 360;
		$s = $s<0 ? 0 : $s>255 ? 255 : (int)$s;
		$v = $v<0 ? 0 : $v>255 ? 255 : (int)$v;

		if ($s==0)		// GRAY
		{
			$this->r = $this->g = $this->b = $v;
		}
		else
		{
			$s = $s / 255;

			$i = floor($h / 60) % 6;
			$f = ($h / 60) - $i;

			$p = $v * (1 - $s);
			$q = $v * (1 - $f * $s);
			$t = $v * (1 - (1 - $f) * $s);

			switch ($i) {
				case 0:
					$this->r = $v;
					$this->g = $t;
					$this->b = $p;
					break;
				case 1:
					$this->r = $q;
					$this->g = $v;
					$this->b = $p;
					break;
				case 2:
					$this->r = $p;
					$this->g = $v;
					$this->b = $t;
					break;
				case 3:
					$this->r = $p;
					$this->g = $q;
					$this->b = $v;
					break;
				case 4:
					$this->r = $t;
					$this->g = $p;
					$this->b = $v;
					break;
				case 5:
					$this->r = $v;
					$this->g = $p;
					$this->b = $q;
					break;
			}
		}
		$this->check();
	}
}
