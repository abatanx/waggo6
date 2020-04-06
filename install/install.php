<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/lib/lib.php';
require_once __DIR__ . '/lib/escseq.php';

$state = "license";

$fw = detect_waggo_version();

for($endflag=false ; !$endflag ; )
{
	cls();
	echo <<<___END___
--------------------------------------------------------------------------------

\t{$fw["name"]} version {$fw["version"]} installer
\t{$fw["copyright"]}

--------------------------------------------------------------------------------


___END___;

	switch($state)
	{
		case "license":
			echo "ライセンスについて\n\n";
			require_once(dirname(__FILE__)."/lib/license.php");
			$state = install_license() ? "dircheck" : "abort" ;
			break;

		case "dircheck":
			echo "ディレクトリチェック\n\n";
			require_once(dirname(__FILE__)."/lib/dircheck.php");
			$state = install_dircheck() ? "dir" : "abort" ;
			break;

		case "dir":
			echo "ディレクトリの確認及び作成\n\n";
			require_once(dirname(__FILE__)."/lib/dircheck.php");
			$state = install_mkdir() ? "instinfo" : "abort" ;
			break;

		case "instinfo":
			echo "インストールのための各種情報入力\n\n";
			require_once(dirname(__FILE__)."/lib/instinfo.php");
			install_instinfo();
			break;

		default:
			echo "セットアップに異常が発生しました。\n\n";
			$state = "abort";
			break;
	}

	switch($state)
	{
		case "abort":
			echo "セットアップを中止します。\n\n";
			$endflag = true;
			break;

		case "end":
			echo "セットアップを終了します。\n\n";
			$endflag = true;
			break;
	}
}
