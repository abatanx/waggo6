<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/../../waggo.php";
require_once dirname(__FILE__)."/thumbnail.php";
require_once dirname(__FILE__)."/../http/mime.php";

/**
 *  Image resource class
 */
class WGResource
{
	const
		PM_IMAGE=1, PM_MOVIE=2;
	const
		RS_DIR=0,
		RS_FILE_IMAGE=10, RS_FILE_MOVIE=20, RS_FILE_BINARY=50,
		RS_SYS_WAIT=71, RS_SYS_PROGRESS=77, RS_SYS_ERROR=99;

	public $format, $px, $type;
	public $origkey, $origtype, $origext, $origmime, $origalt;
	public $movfile;
	public $hid, $ext, $id;
	public $is_visible;
	public $prevmode;

	public function __construct($id,$params=array())
	{
		$g = _QQ("SELECT filename,type,ext,mime,title FROM resource WHERE id=%s AND deny=false;", _N($id,false));
		if($g)	$p = _Q("SELECT is_accessible(%s,%s);", _N($id,false), _U());
		else	$p = 0;

		$this->origkey  = $g["filename"];
		$this->origtype = $g["type"];
		$this->origext  = $g["ext"];
		$this->origmime = $g["mime"];
		$this->origalt  = $g["title"];
		$this->is_visible = !($g===false || ($g!==false && $p===0));
		$this->id       = $id;

		$this->setPreviewFormat($params["format"]);
		$this->setPreviewSize($params["size"]);
	}

	public function getId()
	{
		return $this->id;
	}

	public function isMovie()    { return wg_mimetype_is_movie($this->origmime);    }
	public function isImage()    { return wg_mimetype_is_image($this->origmime);    }
	public function isDocument() { return wg_mimetype_is_document($this->origmime); }

	public function setPreviewFormat($format)
	{
		global $WG_THUMBNAIL_FORMATS;
		switch($format)
		{
			case "j":case "jpg":case "jpeg":
				$this->prevmode = self::PM_IMAGE;
				$this->hid = $WG_THUMBNAIL_FORMATS["jpg"];
				$this->ext = "jpg";
				break;
			case "p":case "png":
				$this->prevmode = self::PM_IMAGE;
				$this->hid = $WG_THUMBNAIL_FORMATS["png"];
				$this->ext = "png";
				break;
			case "f":case "flv":
				$this->prevmode = self::PM_MOVIE;
				if($this->origtype!=self::RS_FILE_MOVIE) return;
				$this->movfile = "pc.flv";
				break;
			case "3":case "3gp":
				$this->prevmode = self::PM_MOVIE;
				if($this->origtype!=self::RS_FILE_MOVIE) return;
				$this->movfile = "mob.3gp";
				break;
			case "o":case "orig":case "original":
			default:
				$this->prevmode = self::PM_IMAGE;
				$this->hid = $WG_THUMBNAIL_FORMATS[""];
				$this->ext = $this->origext;
				break;
		}
	}

	public function setPreviewSize($size)
	{
		global $WG_THUMBNAIL_SIZES;
		$sk = 0;
		foreach( $WG_THUMBNAIL_SIZES as $tkey => $sizelist )
		if(in_array($size,$sizelist["previd"])) { $sk = $tkey; break; }
		$this->px = $WG_THUMBNAIL_SIZES[$sk]["maxpx"];
	}

	public function getKeyURL() { return "/r/{$this->origkey[0]}/{$this->origkey[1]}/{$this->origkey}"; }
	public function getKeyDir() { return WGCONF_DIR_RES."/{$this->origkey[0]}/{$this->origkey[1]}/{$this->origkey}"; }
	public function getSkinURL() { return "/skin/faces"; }
	public function getSkinDir() { return WGCONF_DIR_PUB.$this->getSkinURL(); }
	public function getMovieSkinURL() { return "/skin/movies"; }
	public function getMovieSkinDir() { return WGCONF_DIR_PUB.$this->getMovieSkinURL(); }

	public function getAlt() { return $this->origalt; }

	/**
	 *  Image Location
	 */
	public function getNotAccessibleImageLocation()
	{
		$filename = "{$this->hid}{$this->px}_not_permitted.{$this->ext}";
		if(file_exists($this->getSkinDIR()."/{$filename}")) return $this->getSkinURL()."/{$filename}";
		else return "/skin/faces/not_permitted.png";
	}

	public function getWaitImageLocation()
	{
		$filename = "{$this->hid}{$this->px}_converting.{$this->ext}";
		if(file_exists($this->getSkinDIR()."/{$filename}")) return $this->getSkinURL()."/{$filename}";
		else return "/skin/faces/not_permitted.png";
	}

	public function getFileImageLocation()
	{
		if     (wg_mimetype_is_document($this->origmime))   $r="file_document";
		else if(wg_mimetype_is_pdf($this->origmime))        $r="file_pdf";
		else $r="file";

		$filename = "{$this->hid}{$this->px}_{$r}.{$this->ext}";
		if(file_exists($this->getSkinDIR()."/{$filename}")) return $this->getSkinURL()."/{$filename}";
		else return "/skin/faces/file.png";
	}

	public function getImageLocation()
	{
		if(!$this->is_visible) return $this->getNotAccessibleImageLocation();
		if($this->origtype==self::RS_SYS_WAIT) return $this->getWaitImageLocation();
		if($this->origtype==self::RS_FILE_BINARY) return $this->getFileImageLocation();
		$filename = "{$this->hid}{$this->px}.{$this->ext}";

		wg_errorlog($this->getKeyDIR()."/{$filename}");
		if(file_exists($this->getKeyDIR()."/{$filename}")) return $this->getKeyURL()."/{$filename}";
		else return $this->getNotAccessibleImageLocation();
	}

	/**
	 *  File Location
	 */
	public function getFileLocation()
	{
		if(!$this->is_visible) return false;
		$filename = "file.{$this->origext}";
		if(file_exists($this->getKeyDir()."/{$filename}")) return $this->getKeyURL()."/{$filename}";
		else return false;
	}

	/**
	 *  Movie Location
	 */
	public function getNotAccessibleMovieLocation()
	{
		$filename = "not_permitted.flv";
		if(file_exists($this->getMovieSkinDIR()."/{$filename}")) return $this->getMovieSkinURL()."/{$filename}";
		else return "/skin/movies/not_permitted.flv";
	}

	public function getMovieLocation()
	{
		if(!$this->is_visible) return $this->getNotAccessibleMovieLocation();
		if(file_exists($this->getKeyDir()."/{$this->movfile}")) return $this->getKeyURL()."/{$this->movfile}";
		else return $this->getNotAccessibleMovieLocation();
	}
}

