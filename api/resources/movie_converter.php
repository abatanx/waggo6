<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function wg_movie_upexec($params)
{
	foreach($params as $pk=>$pv) if($pv=="") $params[$pk]="''";

	$exec = implode(" ",$params);
	// wg_errorlog("Exec: {$exec}\n");
	system($exec);
}

// イメージ変換
function wg_movie_converter($ffmpeg,$orgfile,$newfile,$params)
{
	/*
	wg_errorlog(
		"[+++MOVIE] ".
		"PX={$size} ".
		"ORG={$orgfile}".
		"NEW={$newfile}"
	);
	*/

	$pa = array();
	$pa[] = $ffmpeg;
	$pa[] = "-y";
	$pa[] = "-i";
	$pa[] = escapeshellarg($orgfile);
	$pa[] = $params;
	$pa[] = escapeshellarg($newfile);

	wg_movie_upexec($pa);

	if(!file_exists($newfile) || @filesize($newfile)==0 ) return false;
	if(!@chmod($newfile,0666))
	{
		@unlink($newfile);
		return false;
	}
	return true;
}
