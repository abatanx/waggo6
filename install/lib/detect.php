<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function detect_pear()
{
	$pear_dir  = false;
	$pear_exec = dirname( $_SERVER['_'] ) . '/pear';
	if( file_exists($pear_exec) && is_executable($pear_exec) )
	{
		$p = trim(exec("{$pear_exec} config-get php_dir"));
		if( !empty($p) )
		{
			$t_pear_dir = rtrim($p, '/\\');
			$t_pear_php = $t_pear_dir . '/PEAR.php';
			if( file_exists($t_pear_php) )
			{
				$pear_dir = $t_pear_dir;
			}
		}
	}
	return $pear_dir;
}

function detect_exec($file)
{
	$paths = preg_split('/[:;]/', getenv("PATH"));
	foreach( $paths as $path )
	{
		$t_exec_dir  = rtrim($path, '/\\');
		$t_exec_file = $t_exec_dir . '/' . $file;
		if( file_exists($t_exec_file) && is_executable($t_exec_file) )
		{
			return $t_exec_file;
		}
	}
	return false;
}

function detect_phpcli()
{
	$t_exec_file = $_SERVER['_'];
	return file_exists($t_exec_file) && is_executable($t_exec_file) ? $t_exec_file : false ;
}

function detect_convert()
{
	return detect_exec('convert');
}

function detect_ffmpeg()
{
	return detect_exec('ffmpeg');
}
