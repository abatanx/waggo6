<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

function wg_exec_string($params=array(),$additional="")
{
	$e_params = array();
	foreach($params as $pv) $e_params[]=($pv!="") ? escapeshellarg($pv):"''";
	$exec = implode(" ",$e_params) . (($additional!="") ? " $additional" : "");
	return $exec;
}

/**
 * プロセスを実行する。実行は exec による。
 * @param array $params パラメータ。それぞれシェル用に escapeshellarg でクオートされます。
 * @param string $additional 最後に追加する文字列。
 * @return string exec()の返り値。
 */
function wg_exec($params=array(),$additional="")
{
	$e = wg_exec_string($params,$additional);
	wg_log("** CMD execute => [{$e}]");
	return exec($e);
}

/**
 * プロセスを実行する。実行は exec による。
 * @param array $params パラメータ。それぞれシェル用に escapeshellarg でクオートされます。
 * @param string $additional 最後に追加する文字列。
 * @return string exec()の返り値。
 */
function wg_exec_result($params=array(),$additional="")
{
	$o = array();
	$e = wg_exec_string($params,$additional);
	wg_log("** CMD execute => [{$e}]");
	exec($e,$o);
	return rtrim(implode("\n",$o));
}

/**
 * プロセスをバックグラウンドで実行する。実行は exec による。
 * @param array $params パラメータ。それぞれシェル用に escapeshellarg でクオートされます。
 * @param string $additional 最後に追加する文字列。
 * @return string exec()の返り値。
 */
function wg_exec_background($params=array(),$additional="")
{
	$additional = $additional . " >& /dev/null &";
	return wg_exec($params,$additional);
}

/**
 * １つの外部プロセスで、連続したバッチ処理をバックグラウンドで行う。
 * @param array $batch バッチ処理を記載した配列変数
 * @return string exec()の返り値。
 */
function wg_exec_background_batch($batch=array())
{
	$tmpfile = tempnam("/tmp", "wg_exec_batch_".uniqid());
	file_put_contents($tmpfile, implode("\n",$batch));
	return wg_exec_background(
		array(
			WGCONF_DIR_WAGGO."/exec/batch.sh",
			$tmpfile
		)
	);
}

/**
 * プロセスを実行する。実行は paththru による。
 * @param array $params パラメータ。それぞれシェル用に escapeshellarg でクオートされます。
 * @param string $additional 最後に追加する文字列。
 */
function wg_exec_passthru($params=array(),$additional="")
{
	$e = wg_exec_string($params,$additional);
	wg_log("** CMD execute(passthru) => [{$e}]");
	passthru($e);
}

/**
 * 外部プログラムを実行し、パイプを利用して標準入力からデータを差し込む。
 * @param string $cmd 外部プログラムファイル名。
 * @param string $stdin 標準入力に流す文字列。
 * @param string $cwd ワークディレクトリ(nullの場合、PHPファイルの位置)。
 * @param array  $env 環境変数。
 * @return string 標準出力の内容。
 */
function wg_pipe($cmd,$stdin,$cwd=null,$env=array())
{
	$descriptorspec = array(
		0 => array("pipe", "r"),
		1 => array("pipe", "w"),
		2 => array("pipe", "w")
	);

	if(is_null($cwd)) $cwd = dirname(realpath($_SERVER["SCRIPT_FILENAME"]));
	$process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);

	if (is_resource($process)) {
		fwrite($pipes[0],$stdin);
		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$ret = proc_close($process);
		return array($ret,$stdout,$stderr);
	}
	return false;
}
