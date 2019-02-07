<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

function install_dirinfo()
{
	$dirinfo =
		array(	"installer"		=>	realpath(dirname(__FILE__)."/../install.php"),
				"install"		=>	realpath(dirname(__FILE__)."/.."),
				"waggo"			=>	realpath(dirname(__FILE__)."/../.."),
				"sys"			=>	realpath(dirname(__FILE__)."/../../.."),
				"inc"			=>	realpath(dirname(__FILE__)."/../../..")."/include",
				"application"	=>	realpath(dirname(__FILE__)."/../../../.."),
				"pub"			=>	realpath(dirname(__FILE__)."/../../../..")."/pub",
				"tpl"			=>	realpath(dirname(__FILE__)."/../../../..")."/tpl",
				"upload"		=>	realpath(dirname(__FILE__)."/../../../..")."/upload",
				"config"		=>	realpath(dirname(__FILE__)."/../../..")."/config",
				"resources"		=>	realpath(dirname(__FILE__)."/../../../..")."/resources",
				"temporary"		=>	realpath(dirname(__FILE__)."/../../../..")."/temporary",
				"logs"			=>	realpath(dirname(__FILE__)."/../../../..")."/logs",
				"inittpl"		=>	realpath(dirname(__FILE__)."/../..")."/initdata/tpl"
		)
	;

	$dirinfo["appname"] = basename($dirinfo["application"]);
	return $dirinfo;
}

function install_dircheck()
{
	$dirinfo = install_dirinfo();

	echo <<<___END___

+--<application>                                -> {$dirinfo['application']}
     | ({$dirinfo['appname']})
     |
     +--pub              (@ 公開ディレクトリ)   -> {$dirinfo['pub']}
     |   |
     |   +--resources    (>)
     |   +--wgcss        (>)
     |   +--wgjs         (>)
     |   +--examples     (>)
     |   +--tests        (>)
     |
     +--sys                                     -> {$dirinfo['sys']}
     |   |
     |   +--include      (@)                    -> {$dirinfo['inc']}
     |   |
     |   +--waggo6                              -> {$dirinfo['waggo']}
     |   |    |
     |   |    +--install (このディレクトリ)     -> {$dirinfo['install']}
     |   |        |
     |   |        +install.php                  -> {$dirinfo['installer']}
     |   |
     |   +--config       (@)                    -> {$dirinfo['config']}
     |
     +---tpl             (@)                    -> {$dirinfo['tpl']}
     |
     +---upload          (@)                    -> {$dirinfo['upload']}
     |
     +---resources       (@)                    -> {$dirinfo['resources']}
     |
     +---temporary       (@)                    -> {$dirinfo['temporary']}
     |
     +---logs            (@)                    -> {$dirinfo['logs']}

  <application>: ディレクトリ名については、任意の名前で構いません。
              @: 新規作成(更新)します。
              >: シンボリックリンクを新規作成(更新)します。

___END___;

	$has_error = false;

	if(!preg_match('/\/sys$/',$dirinfo["sys"]))
	{
		echo "\n\n";
		echo "[ERROR] waggo6.00.tar.gz は、<application>/sys を作成し、その中で展開してください。\n";
		echo "        % mkdir hogehoge\n";
		echo "        % cd hogehoge\n";
		echo "        % mkdir sys\n";
		echo "        % cd sys\n";
		echo "        % tar xvfz ~/Downloads/waggo6.00.tar.gz\n";
		$has_error = true;
	}

	if(!preg_match('/\/sys\/waggo6$/',$dirinfo["waggo"]))
	{
		echo "\n\n";
		echo "[ERROR] waggo6.00.tar.gz は、<application>/sys/waggo6 配下に install が作成されるよう展開してください。\n";
		echo "        % tar xvfz ~/Downloads/waggo6.00.tar.gz\n";
		echo "        % mv waggo6.00 waggo6\n";
		$has_error = true;
	}

	if( $has_error ) return false;

	echo "\n\n";
	return q("以上のディレクトリ構成で、セットアップを続行してもよろしいですか? (Yes/No) -> ",array("Yes","No")) === "Yes";
}

function install_mkdir()
{
	$dirinfo = install_dirinfo();
	$keys = array(
		"config"		=>	0777,
		"pub"			=>	0755,
		"inc"			=>	0755,
		"tpl"			=>	0755,
		"upload"		=>	0777,
		"resources"		=>	0777,
		"temporary"		=>	0777,
		"logs"			=>	0777
	);

	$symlinks = array(
		array(	$dirinfo["pub"]."/examples"		,	"../sys/waggo6/www/examples"	),
		array(	$dirinfo["pub"]."/tests"		,	"../sys/waggo6/www/tests"		),
		array(	$dirinfo["pub"]."/wg"			,	"../sys/waggo6/www/wg"			),
		array(	$dirinfo["pub"]."/wgjs"			,	"../sys/waggo6/www/wgjs"		),
		array(	$dirinfo["pub"]."/wgcss"		,	"../sys/waggo6/www/wgcss"		),
		array(	$dirinfo["pub"]."/resources"	,	"../resources"					)
	);

	foreach($keys as $key=>$permission)
	{
		$dir = $dirinfo[$key];
		echo sprintf("-> ディレクトリ %-50s の状態を確認しています。\n",$dir);

		clearstatcache();
		if(!is_dir($dirinfo[$key]))
		{
			@mkdir($dirinfo[$key]);
			if(!is_dir($dirinfo[$key]))
			{
				echo "【エラー】ディレクトリの作成に失敗しました。\n";
				return false;
			}
		}

		if(@chmod($dirinfo[$key], $permission)===false)
		{
			echo "【エラー】パーミッションの変更に失敗しました。\n";
			return false;
		}
	}

	foreach($symlinks as $symlink)
	{
		echo sprintf("-> シンボリックリンク %-50s を確認しています。\n",$symlink[0]);

		$dst = $symlink[0];
		$src = $symlink[1];
		@symlink($src,$dst);
	}

	return true;

}
