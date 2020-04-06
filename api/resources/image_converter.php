<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

function wg_image_upexec($params)
{
	foreach($params as $pk=>$pv) if($pv=="") $params[$pk]="''";

	$exec = implode(" ",$params);
	wg_log_write(WGLOG_INFO, "wg_image_upexec: {$exec}");
	system($exec);
}

/**
 * アップロードされたイメージを変換する。
 * @param string $convert ImageMagickのconvertコマンドフルパス
 * @param string $srcfile 変換前ファイル
 * @param string $dstfile 返還後ファイル
 * @param int|boolean $size 縦横の最大サイズ。falseの場合、geometry を指示しない。
 * @param array $opts 追加オプション
 * @return boolean 変換に成功したかどうか
 */
function wg_image_converter($convert,$srcfile,$dstfile,$size=false,$opts=[])
{
	$pa = [];
	$pa[] = $convert;

	if( $size != false )
	{
		$pa[] = escapeshellarg("-geometry");
		$pa[] = escapeshellarg("{$size}>x{$size}>");
	}

	foreach($opts as $opt) $pa[] = escapeshellarg($opt);

	$pa[] = escapeshellarg($srcfile . '[0]');
	$pa[] = escapeshellarg($dstfile);

	wg_image_upexec($pa);

	clearstatcache();
	if( !file_exists($dstfile) || @filesize($dstfile)==0 ) return false;

	if( !@chmod($dstfile,0666) )
	{
		@unlink($dstfile);
		return false;
	}

	return true;
}
