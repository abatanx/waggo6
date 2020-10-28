<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

/**
 * m10w31 を計算する。
 * @param $n
 *
 * @return int
 */
function wg_m10w31( $n )
{
	$v[0] = $v[1] = 0;
	for ( $p = 0, $a = (int) $n; $a > 0; $p = 1 - $p )
	{
		$v[ $p ] += ( $a % 10 );
		$a       = (int) ( $a / 10 );
	}
	$b = ( 10 - ( ( $v[0] * 3 + $v[1] ) % 10 ) ) % 10;

	return (int) $n * 10 + $b;
}

/**
 * 新しいリソース管理番号(id)を発行する。
 * 失敗した場合、エラーメッセージを排出したのち、強制的にスクリプトの実行を終了する。
 *
 * @param string $seq SEQUENCEテーブル名
 * @param boolean $use_m10w31 チェックサムを追加するか。
 *
 * @return int リソース管理番号(id)
 */
function wg_newid( $seq = 'seq_id', $use_m10w31 = true )
{
	if ( wg_is_dbms_mariadb() )
	{
		if ( wg_is_supported_sequence_mariadb() )
		{
			// mariadb シーケンスサポートしているので、sequence にて対応する。
			list( $newid ) = _QQ( "select nextval(%s) as newid;", $seq );
		}
		else
		{
			// mariadb シーケンスサポート外のため、function にて対応する。
			list( $newid ) = _QQ( "select nextval('%s') as newid;", $seq );
		}
	}
	else
	{
		// PostgreSQLの場合はnativeの、MySQLの場合は function にて対応する。
		list( $newid ) = _QQ( "select nextval('%s') as newid;", $seq );
	}

	if ( is_null( $newid ) )
	{
		wg_log_write( WGLOG_ERROR, "Failed, wg_newid({$seq})" );
		exit;
	}

	return $use_m10w31 ? wg_m10w31( $newid ) : $newid;
}

/**
 * 新しいリソース管理番号(id)を発行する。
 * 失敗した場合、エラーメッセージを排出したのち、強制的にスクリプトの実行を終了する。
 *
 * @param int $usercd リソースを所有するユーザーコード(nullの場合、ownerテーブルに追加しない。)
 *
 * @return int リソース管理番号(id)
 */
function wg_create_seqid( $usercd = null )
{
	$newid = wg_newid( 'seq_id' );
	if ( ! is_null( $usercd ) )
	{
		_Q(
			"INSERT INTO owner(id,usercd,initymd,updymd,enabled) " .
			"VALUES(%d,%d,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,true);",
			_N( $newid ), _N( $usercd ) );
	}

	return $newid;
}

/**
 * 新しいユーザ管理番号(usercd)を発行する。
 * 失敗した場合、エラーメッセージを排出したのち、強制的にスクリプトの実行を終了する。
 * @return integer ユーザ管理番号(id)
 */
function wg_create_usercd()
{
	list( $newusercd ) = _QQ( "SELECT nextval('seq_usercd') AS newusercd FROM seq_usercd;" );
	if ( is_null( $newusercd ) )
	{
		wg_log_write( WGLOG_ERROR, "Failed, wg_create_usercd()" );
		exit;
	}

	return wg_m10w31( $newusercd );
}

/**
 * 新しいグループ管理番号(grpcd)を発行する。
 * 失敗した場合、エラーメッセージを排出したのち、強制的にスクリプトの実行を終了する。
 * @return integer グループ管理番号(grpcd)
 */
function wg_create_grpcd()
{
	list( $newgrpcd ) = _QQ( "SELECT nextval('seq_grpcd') AS newusercd FROM seq_grpcd;" );
	if ( is_null( $newgrpcd ) )
	{
		wg_log_write( WGLOG_ERROR, "Failed, wg_create_grpcd()" );
		exit;
	}

	return wg_m10w31( $newgrpcd );
}

/**
 * ユニークな識別符号用にランダムな文字列32文字を生成する。
 * @param int $length 文字列の長さ
 * @return string ランダムな文字列
 */
function wg_create_uniqid( $length = 32 )
{
	$basechrs1 = "ghijklmnopqrstuvwxyz";
	$basechrs2 = "0123456789abcdefghijklmnopqrstuvwxyz";
	$uniqid    = "";
	list( $serial ) = _QQ( "SELECT nextval('seq_serial');" );
	while ( $serial > 0 )
	{
		$uniqid = sprintf( "%02x", $serial & 0xff ) . $uniqid;
		$serial >>= 8;
	}
	$uniqid = substr( $basechrs1, mt_rand( 0, strlen( $basechrs1 ) - 1 ), 1 ) . $uniqid;
	while ( strlen( $uniqid ) < $length )
	{
		$uniqid = substr( $basechrs2, mt_rand( 0, strlen( $basechrs2 ) - 1 ), 1 ) . $uniqid;
	}

	return substr( $uniqid, 0, $length );
}

/**
 * メールアドレスの識別用にランダムな文字列を生成する。
 * @return String ランダムな文字列
 */
function wg_create_email_uniqid()
{
	$id = "0123456789abcdefghijklmnopqrstuvwxyz----";
	$l  = strlen( $id );
	do
	{
		$ret = "gm.";
		for ( $i = 0; $i < 10; $i ++ )
		{
			$x   = mt_rand( 0, $l - 1 );
			$ret .= substr( $id, $x, 1 );
		}
	}
	while ( preg_match( '/^[^a-z]/', $ret ) || preg_match( '/[^0-9a-z]$/', $ret ) || preg_match( '/[^a-z0-9]{2}/', $ret ) );

	return $ret;
}

/**
 * @deprecated
 */
function wg_get_email_uniqid()
{
	return wg_create_email_uniqid();
}

/**
 * タイムラインで判定できるユニークIDを発行する。
 * 失敗した場合、エラーメッセージを排出したのち、強制的にスクリプトの実行を終了する。
 *
 * @param string $seq 利用するシーケンステーブル
 *
 * @return string ユニークID
 */
function wg_create_timeline_uniqid( $seq = 'seq_serial' )
{
	list( $newid ) = _QQ( "SELECT nextval(%s) as newid FROM seq_id;", _S( $seq ) );
	if ( is_null( $newid ) )
	{
		wg_log_write( WGLOG_ERROR, "Failed, wg_get_timeline_uniqid()" );
		exit;
	}
	$t = localtime( time(), true );

	return sprintf( "%04d%02d%02d%02d%02d%02d%06d",
		$t["tm_year"] + 1900, $t["tm_mon"] + 1, $t["tm_mday"],
		$t["tm_hour"], $t["tm_min"], $t["tm_sec"], $newid % 1000000 );
}
