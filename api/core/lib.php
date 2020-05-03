<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

define( 'WGLOG_APP',     0 );    ///< 情報タイプのログメッセージである。
define( 'WGLOG_INFO',    1 );    ///< 情報タイプのログメッセージである。
define( 'WGLOG_WARNING', 2 );    ///< 警告タイプのログメッセージである。
define( 'WGLOG_ERROR',   3 );    ///< エラータイプのログメッセージである。
define( 'WGLOG_FATAL',   9 );    ///< 致命的エラータイプのログメッセージである。

$wg_log_write_colors = array(
	mt_rand( 1, 7 ),
	mt_rand( 1, 7 ),
	mt_rand( 1, 7 )
);

$wg_log_write_types = array(
	WGLOG_APP     => "APP",
	WGLOG_INFO    => "INFO",
	WGLOG_WARNING => "WARN",
	WGLOG_ERROR   => "ERROR",
	WGLOG_FATAL   => "FATAL"
);

/**
 * 非推奨関数についてログにその旨を記録し、転送先関数に処理を転送します。
 *
 * @param boolean $is_forward trueの場合、新しい関数に転送する。
 * @param string $func __FUNCTION__ を指定する。
 * @param string $solver 転送先の関数名。
 * @param array $params func_get_args() を指定する。
 *
 * @return mixed 転送先関数からの戻り値。
 */
function wg_deplicated( $is_forward, $func, $solver, $params = null )
{
	wg_log_write( WGLOG_WARNING, "{$func} は非推奨APIです。今後 {$solver} を利用してください。" );
	if ( $is_forward )
	{
		return call_user_func_array( $solver, $params );
	}
	return null;
}

/**
 * ログに情報を出力する。
 *
 * @param string $log ログ記録内容
 */
function wg_log_write_error_log( $log )
{
	switch ( WG_LOGTYPE )
	{
		case 0:
		default:
			error_log( trim( $log ) );
			break;

		case 1:
			error_log( $log, 3, WG_LOGFILE );
			break;
	}
}

/**
 * ログに情報を出力する。
 *
 * @param integer $logtype 出力するメッセージのタイプ(WGLOG_APP|WGLOG_INFO|WGLOG_WARNING|WGLOG_ERROR|WGLOG_FATAL)
 * @param string $msg メッセージ。
 */
function wg_log_write( $logtype, $msg )
{
	if ( WG_LOGFILE == "" )
	{
		return;
	}

	if ( $logtype == WGLOG_APP ||
		 ( $logtype == WGLOG_INFO && WG_DEBUG == true ) ||
		 $logtype == WGLOG_WARNING ||
		 $logtype == WGLOG_ERROR ||
		 $logtype == WGLOG_FATAL )
	{
		global $wg_log_write_colors, $wg_log_write_types;
		// $pid = posix_getpid();
		$pid = getmygid();
		$es0 = sprintf( "\x1b[%dm", $wg_log_write_colors[0] + 30 );
		$es1 = sprintf( "\x1b[%dm", $wg_log_write_colors[1] + 30 );
		$es2 = sprintf( "\x1b[%dm", $wg_log_write_colors[2] + 30 );
		$es9 = "\x1b[m";

		$msg = rtrim( $msg );
		$dd  = date( "Ymd H:i:s" );
		$msg = str_replace( "\0", "\\0", $msg );    // NULL文字が入ると正常にロギングできない対処。
		$im  = $wg_log_write_types[ $logtype ];
		$log = sprintf( "{$es0}[%6d] {$es1}%-15s %s {$es2}[%s] %s {$es9}\n", $pid, $dd, $_SERVER["SCRIPT_NAME"], $im, $msg );
		wg_log_write_error_log( $log );

		// 致命的エラーの場合、バックトレースを表示して終了する。
		if ( $logtype == WGLOG_FATAL )
		{
			foreach ( debug_backtrace() as $b )
			{
				$cn = sprintf( "%s::%s", $b["class"], $b["function"] );

				$log = sprintf( "   --> %-40s %s (%s)\n", $cn, $b["file"], $b["line"] );
				wg_log_write_error_log( $log );
			}
			die();
		}
	}
}

function wg_log_dump( $logtype, $var )
{
	ob_start();
	var_dump( $var );
	$s = ob_get_contents();
	ob_end_clean();
	wg_log_write( $logtype, "----" );
	foreach ( explode( "\n", $s ) as $ss )
	{
		wg_log_write( $logtype, $ss );
	}
	wg_log_write( $logtype, "----" );
}

/**
 * エラーをログファイルに出力し、スクリプトを強制終了する。
 *
 * @param string $msg エラーメッセージ。
 */
function wg_die( $msg )
{
	wg_log_write( WGLOG_ERROR, "DIE\n{$msg}" );
	exit;
}

/**
 * wikiテキスト内の改行を削除する。
 *
 * @param string $s wikiテキスト内改行が含まれる文字列
 *
 * @return string wikiテキスト内改行を削除した文字列
 */
function wg_nobr( $s )
{
	return preg_replace( '/[\r\n\t]/i', '', $s );
}

/**
 * REMOTE IPアドレス取得
 * @return mixed|string
 */
function wg_get_remote_adr()
{
	$addr = $_SERVER['REMOTE_ADDR'];
	if ( isset( $_SERVER['HTTP_X_CLIENT_IP'] ) )
	{
		// for Azure
		$client = explode( ',', $_SERVER['HTTP_X_CLIENT_IP'] );
		$addr   = trim( array_shift( $client ) );
	}

	return $addr;
}
