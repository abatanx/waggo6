<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

function wg_get_resource_dir( $uniqid )
{
	if ( strlen( $uniqid ) <= 2 )
	{
		return false;
	}
	clearstatcache();
	$path     = "";
	$fullpath = "";
	for ( $i = 0; $i <= 1; $i ++ )
	{
		$path     .= "/" . $uniqid[ $i ];
		$fullpath = WGCONF_DIR_RES . $path;
		if ( is_dir( $fullpath ) == false )
		{
			if ( ! @mkdir( $fullpath, 0777 ) )
			{
				return false;
			}
			if ( ! @chmod( $fullpath, 0777 ) )
			{
				return false;
			}
		}
		else if ( ! is_dir( $fullpath ) )
		{
			return false;
		}
		else if ( ! is_writable( $fullpath ) )
		{
			return false;
		}
	}
	$fullpath = $fullpath . "/{$uniqid}";
	if ( is_dir( $fullpath ) == false )
	{
		if ( ! @mkdir( $fullpath, 0777 ) )
		{
			return false;
		}
		if ( ! @chmod( $fullpath, 0777 ) )
		{
			return false;
		}
	}

	return $fullpath;
}

/**
 * リソースの保存先URLを取得する。
 * リソース保存先URLのエントリディレクトリ(/hoge/a/b/abab の hogeの部分) は不明なため、エントリ以降のディレクトリ(/a/b/abab)を返す。
 * 保存先ディレクトリがディレクトリではない場合や、読み込みができないパーミッション設定の場合、失敗する。
 *
 * @param string $uniqid リソースフォルダ文字列
 *
 * @return string|boolean リソースフォルダURL。失敗したら false を返す。
 */
function wg_get_resource_url( $uniqid )
{
	if ( strlen( $uniqid ) <= 2 )
	{
		return false;
	}
	clearstatcache();

	$entpath  = "/{$uniqid[0]}/{$uniqid[1]}/{$uniqid}";
	$realpath = WGCONF_DIR_RES . $entpath;

	if ( ! is_dir( $realpath ) || ! is_readable( $realpath ) )
	{
		return false;
	}

	return $entpath;
}

/**
 * リソースの保存先ディレクトリを消去する。
 *
 * @param string $uniqid リソースフォルダ文字列
 *
 * @return boolean 成功したらtrueを、失敗したらfalseを返す。
 */
function wg_clean_resource_dir( $uniqid )
{
	if ( ( $dir = wg_get_resource_dir( $uniqid ) ) === false )
	{
		return false;
	}
	if ( ( $fd = @opendir( $dir ) ) === false )
	{
		return false;
	}

	while ( ( $file = @readdir( $fd ) ) !== false )
	{
		$fullpath = "{$dir}/{$file}";
		wg_log_write( WGLOG_INFO, "Checking => ${fullpath}" );
		if ( is_file( $fullpath ) )
		{
			wg_log_write( WGLOG_INFO, "Deleting => ${fullpath}" );
			@unlink( $fullpath );
			clearstatcache();
			if ( file_exists( $fullpath ) )
			{
				closedir( $fd );

				return false;
			}
		}
	}
	closedir( $fd );

	return true;
}
