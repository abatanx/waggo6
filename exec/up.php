<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

clearstatcache();

array_shift($argv);

$opt_all    = false;
$opt_usercd = 0;
$opt_domain = false;
$opt_limit  = 0;
$opt_clean  = false;
while(count($argv)>0)
{
	$p = array_shift($argv);
	switch($p)
	{
	case "-d":
		$opt_domain = array_shift($argv);
		break;
	case "-u":
		$a = array_shift($argv);
		if($a=="all")
			$opt_all = true;
		else {
			$opt_all    = false;
			$opt_usercd = (int)$a;
		}
		break;
	case "-l":
		$opt_limit = (int)array_shift($argv);
		break;
	case "-clean":
		$opt_clean = true;
		break;
	}
}

if($opt_domain==false)
{
	die("No domain.\n");
}

if($opt_usercd==0 && !$opt_all)
{
	die("No usercd.\n");
}

$_SERVER["SERVER_NAME"] = trim($opt_domain);

define( WG_DEBUG      , true );
define( WG_SQLDEBUG   , true );
define( WG_MODELDEBUG , true );

require_once dirname(__FILE__)."/../waggo.php";
require_once dirname(__FILE__)."/../framework/m/WGMModel.php";
require_once dirname(__FILE__)."/../api/http/mime.php";
require_once dirname(__FILE__)."/../api/resources/thumbnail.php";
require_once dirname(__FILE__)."/../api/resources/image_converter.php";
require_once dirname(__FILE__)."/../api/resources/movie_converter.php";
require_once dirname(__FILE__)."/../api/resources/dir.php";

global $WG_THUMBNAIL_SIZES, $WG_THUMBNAIL_FORMATS, $WG_MOVIE_FORMATS;

/**
 * スタンバイテーブルから変換処理対象を抽出
 */
_QBEGIN();
$p_where = array();
if( !$opt_all ) $p_where[] = "usercd={$opt_usercd}";
$w_where = (count($p_where)>0) ? "where ".implode(" and ",$p_where) : "";

if( $opt_limit>0 )
	$w_limit = "limit {$opt_limit}";

_Q("lock table resourcestandby in ACCESS EXCLUSIVE MODE;");

_Q("create temporary table t_resourcestandby as ".
	"select usercd,num,id,filename,mimetype from resourcestandby ".
	"%s %s;", $w_where, $w_limit);

_Q("select * from t_resourcestandby order by usercd,num;");

$standbys = _FALL();

if($opt_clean)
{
	_Q("delete from resourcestandby where id in (select id from t_resourcestandby);");
}

_QCOMMIT();

wg_printf("[%d] UPLOADS",count($standbys));

foreach( $standbys as $k=>$standby )
{
	list($usercd,$num,$id,$filename,$mimetype) = $standby;
	list($uniqid) = wg_upimage_standby($usercd,$num,$id,$filename,$mimetype);
	$standbys[$k]["uniqid"] = $uniqid;
}

foreach( $standbys as $standby )
{
	list($usercd,$num,$id,$filename,$mimetype) = $standby;
	wg_upimage_convert($usercd,$num,$id,$filename,$mimetype,$standby["uniqid"]);
}

wg_printf("DONE");

/********************************************************************************************/
function wg_upimage_standby($usercd,$num,$id,$filename,$mimetype)
{
	global $WG_THUMBNAIL_SIZES, $WG_THUMBNAIL_FORMATS;

	$ext = (preg_match('/\.([^\.]*)$/',$filename,$match)) ? strtolower($match[1]) : "";

	wg_printf("** STANDBY ** u=[%d] n=[%d] id=[%d] fn=[%s] ext=[%s] mime=[%s]",
				$usercd,$num,$id,$filename,$ext,$mimetype);

	if( !file_exists($filename) || !is_readable($filename) )
	{
		wg_print("No file({$filename}).\n");
		return false;
	}

	// Prepare
	_QBEGIN();

	$u = _QQ("select * from base_normal where usercd=%s;", _N($usercd));
	if( !$u ) {_QROLLBACK(); wg_printf("No user."); /***return false;***/ }

	$m = new WGMModel("resource");
	$m->vars["id"] = $id;
	$r = $m->get("id");

	$filename = ($r==0) ? wg_get_uniqid() : $m->vars["filename"];

	$m->vars["id"]       = $id;
	$m->vars["usercd"]   = $usercd;
	$m->vars["type"]     = WGResource::RS_SYS_WAIT;
	$m->vars["mime"]     = $mimetype;
	$m->vars["filename"] = $filename;
	$m->vars["ext"]      = $ext;
	$m->vars["enable"]   = true;
	$m->vars["deny"]     = false;
	$m->setAutoTimestamp();
	$m->update("id");

	if(!_QOK()) {_ROLLBACK(); wg_printf("Can't insert to database.\n"); return false; }
	_QCOMMIT();

	return array($filename);
}

function wg_upimage_convert($usercd,$num,$id,$filename,$mimetype,$uniqid)
{
	global $WG_THUMBNAIL_SIZES, $WG_THUMBNAIL_FORMATS, $WG_MOVIE_FORMATS;

	$ext = (preg_match('/\.([^\.]*)$/',$filename,$match)) ? strtolower($match[1]) : "";

	wg_printf("@@ CONVERT @@ u=[%d] n=[%d] id=[%d] fn=[%s] ext=[%s] mime=[%s] uniq=[%s]",
				$usercd,$num,$id,$filename,$ext,$mimetype,$uniqid);

	if( !file_exists($filename) || !is_readable($filename) )
	{
		wg_print("No file({$filename}).\n");
		return false;
	}

	// Make Resource directory
	$dir = wg_get_resource_dir($uniqid);
	if( $dir===false ) { wg_print("Can't create resource directory.\n"); return false; }

	wg_printf("Uniqid=%s Dir=%s\n", $uniqid, $dir);
	if( !wg_clean_resource_dir($uniqid) ) { wg_print("Can't clear resource files."); return false; }

	// イメージ変換
	if( wg_mimetype_is_image($mimetype) )
	{
		$is_error = false;

		$basefile = "{$dir}/image.png"; // 大元になる画像(可逆圧縮のPNGで作成)

		wg_printf("*** Checking Image ***\n");
		if( wg_image_converter(WGCONF_CONVERT, $filename, $basefile, WGCONF_UP_PX)==false ) $is_error=true;
		wg_printf("    RESULT = [%s]\n", (!$is_error) ? "OK" : "ERROR" );

		wg_printf("*** Generating THUMBNAILS ***\n");
		$rslt = array();
		if( !$is_error )
		{
			foreach($WG_THUMBNAIL_FORMATS as $fext=>$f)
			{
				if( $fext=="" ) $fext = $ext;
				if( !$is_error )
				{
					foreach($WG_THUMBNAIL_SIZES as $s)
					{
						if( wg_image_converter(WGCONF_CONVERT, $basefile,"{$dir}/{$f}{$s[maxpx]}.{$fext}", $s["maxpx"])==false ) $is_error=true;
						$rslt[] = sprintf("{$f}{$s[maxpx]}:%s", (!$is_error) ? "OK" : "ERROR" );
					}
				}
			}
		}
		wg_printf("RESULT = [%s]",implode(",",$rslt));

		// wg_printf("*** Deleting Basefile ***\n");
		// wg_printf("    %s\n",$basefile);
		// @unlink($basefile); (通常は消さないでおこう。)
		// if( file_exists($basefile) ) $is_error = true;

		if($is_error)
		{
			_QBEGIN();
			_QQ("update resource set type=%d,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_SYS_ERROR, _N($id));
			_QCOMMIT();
			if( !wg_clean_resource_dir($uniqid) ) { wg_print("Can't clear resource files."); return false; }
		}
		else
		{
			_QBEGIN();
			_QQ("update resource set type=%d,mime=%s,ext=%s,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_FILE_IMAGE, _S($mimetype), _S($ext), _N($id));
			_QCOMMIT();
		}
	}
	// 動画変換
	else if( wg_mimetype_is_movie($mimetype) )
	{
		$is_error = false;

		wg_printf("*** Converting site movie format ***\n");
		if( !$is_error )
		{
			foreach($WG_MOVIE_FORMATS as $fext=>$p)
			{
				wg_printf("[%s] [%s] %s: ", $fext, $p["ffm_params"], $p["filename"]);

				if( wg_movie_converter(WGCONF_FFMPEG, $filename,"{$dir}/{$p[filename]}",
										$p["ffm_params"])==false ) $is_error=true;
				wg_printf("    RESULT = [%s]\n", (!$is_error) ? "OK" : "ERROR" );
			}
		}

		wg_printf("*** Generating THUMBNAILS(IMAGE) ***\n");
		$rslt = array();
		if( !$is_error )
		{
			foreach($WG_THUMBNAIL_FORMATS as $fext=>$f)
			{
				if( $fext=="" ) $fext = "jpg";
				if( !$is_error )
				{
					foreach($WG_THUMBNAIL_SIZES as $s)
					{
						if( wg_image_converter(WGCONF_CONVERT,
												"{$dir}/{$WG_MOVIE_FORMATS[jpg][filename]}",
												"{$dir}/{$f}{$s[maxpx]}.{$fext}",
												$s["maxpx"])==false ) $is_error=true;

						$rslt[] = sprintf("{$f}{$s[maxpx]}:%s", (!$is_error) ? "OK" : "ERROR" );
					}
				}
			}
		}
		wg_printf("RESULT = [%s]",implode(",",$rslt));

		if($is_error)
		{
			_QBEGIN();
			_QQ("update resource set type=%d,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_SYS_ERROR, _N($id));
			_QCOMMIT();
			if( !wg_clean_resource_dir($uniqid) ) { wg_print("Can't clear resource files."); return false; }
		}
		else
		{
			_QBEGIN();
			_QQ("update resource set type=%d,mime=%s,ext=%s,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_FILE_MOVIE, _S($mimetype), _S($ext), _N($id));
			_QCOMMIT();
		}
	}
	else
	{
		$is_error = false;

		$cpyfile = "{$dir}/file.{$ext}";
		if(!@copy($filename,$cpyfile)) $is_error = true;
		if(filesize($filename)!=filesize($cpyfile)) $is_error = true;
		if(!@chmod($cpyfile,0666)) $is_error = true;

		if($is_error)
		{
			_QBEGIN();
			_QQ("update resource set type=%d,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_FILE_ERROR, _N($id));
			_QCOMMIT();
			if( !wg_clean_resource_dir($uniqid) ) { wg_printf("Can't clear resource files."); return false; }
		}
		else
		{
			_QBEGIN();
			_QQ("update resource set type=%d,mime=%s,ext=%s,updymd=CURRENT_TIMESTAMP where id=%s;",
				WGResource::RS_FILE_BINARY, _S($mimetype), _S($ext), _N($id));
			_QCOMMIT();
		}
	}
}
