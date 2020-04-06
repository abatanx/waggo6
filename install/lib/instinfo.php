<?php
/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @license MIT
 */

require_once __DIR__ . '/dircheck.php';
require_once __DIR__ . '/gencontroller.php';
require_once __DIR__ . '/detect.php';

function file_waggoconf()
{
	$tf = <<<___END___
<?php
/**
 * waggo6 configuration
 */
define( 'WG_DEBUG'							,	false ); // Log for debug
define( 'WG_SQLDEBUG'						,	false ); // Log for SQL debug
define( 'WG_SESSIONDEBUG'					,	false ); // Log for Session debug
define( 'WG_CONTROLLERDEBUG'				,	false ); // Log for Controller debug
define( 'WG_MODELDEBUG'						,	false ); // Log for Model debug
define( 'WG_JSNOCACHE'						,	false ); // Ignore cache for JS script
define( 'WG_INSTALLDIR'						,	'' );
define( 'WG_LOGDIR'							,	WG_INSTALLDIR . "/logs" );
define( 'WG_LOGNAME'						,	'' );
define( 'WG_LOGFILE'						,	WG_LOGDIR.'/'.WG_LOGNAME );
define( 'WG_LOGTYPE'						,	0 );
define( 'WG_ENCODING'						,	mb_internal_encoding() );

define( 'WGCONF_DIR_ROOT'					,	WG_INSTALLDIR );
define( 'WGCONF_DIR_WAGGO'					,	realpath( __DIR__ . '/../waggo6'));
define( 'WGCONF_DIR_PUB'					,	realpath( __DIR__ . '/../../pub'));
define( 'WGCONF_DIR_SYS'					,	realpath( __DIR__ . '/../../sys'));
define( 'WGCONF_DIR_TPL'					,	realpath( __DIR__ . '/../../tpl'));
define( 'WGCONF_CANVASCACHE'				,	WG_INSTALLDIR.'/temporary');
define( 'WGCONF_DIR_UP'						,	WG_INSTALLDIR.'/upload');
define( 'WGCONF_DIR_RES'					,	WG_INSTALLDIR.'/resources');

define( 'WGCONF_DIR_FRAMEWORK'				,	WGCONF_DIR_WAGGO.'/framework');
define( 'WGCONF_DIR_FRAMEWORK_MODEL'		,	WGCONF_DIR_FRAMEWORK.'/m');
define( 'WGCONF_DIR_FRAMEWORK_VIEW6'		,	WGCONF_DIR_FRAMEWORK.'/v6');
define( 'WGCONF_DIR_FRAMEWORK_CONTROLLER' 	,	WGCONF_DIR_FRAMEWORK.'/c');
define( 'WGCONF_DIR_FRAMEWORK_EXT'			,	WGCONF_DIR_FRAMEWORK.'/exts');
define( 'WGCONF_DIR_FRAMEWORK_GAUNTLET'		,	WGCONF_DIR_FRAMEWORK.'/gauntlet');

define( 'WGCONF_PEAR'						,	'/usr/local/lib/php' );
define( 'WGCONF_UP_PX'						,	640 );

define( 'WGCONF_SMTP_HOST'					,	'localhost' );
define( 'WGCONF_SMTP_PORT'					,	25 );
define( 'WGCONF_SMTP_AUTH'					,	false );
define( 'WGCONF_SMTP_AUTH_USERNAME'			,	'' );
define( 'WGCONF_SMTP_AUTH_PASSWORD'			,	'' );
define( 'WGCONF_SMTP_LOCALHOST'				,	'localhost' );

define( 'WGCONF_SMTP_TEST'					,	false );
define( 'WGCONF_SMTP_TEST_RCPTTO'			,	'root@localhost' );

define( 'WGCONF_EMAIL'						,	'root@localhost' );
define( 'WGCONF_ERRMAIL'					,	WGCONF_EMAIL );
define( 'WGCONF_REPORTMAIL'					,	WGCONF_EMAIL );

define( 'WGCONF_SESSION_GCTIME'				,	60 * 30 );

define( 'WGCONF_DBMS_TYPE'					,	'pgsql' );
define( 'WGCONF_DBMS_HOST'					,	'localhost' );
define( 'WGCONF_DBMS_PORT'					,	5432 );
define( 'WGCONF_DBMS_DB'					,	'' );
define( 'WGCONF_DBMS_USER'					,	'' );
define( 'WGCONF_DBMS_PASSWD'				,	'' );
define( 'WGCONF_DBMS_CA'					,	'');
define( 'WGCONF_URLBASE'					,	"http://{\$_SERVER['SERVER_NAME']}" );

define( 'WGCONF_GOOGLEMAPS_X'				,	139.767073 );
define( 'WGCONF_GOOGLEMAPS_Y'				,	35.681304 );
define( 'WGCONF_PHPCLI'						,	'/usr/local/bin/php' );
define( 'WGCONF_CONVERT'					,	'/usr/local/bin/convert' );
define( 'WGCONF_FFMPEG'						,	'/usr/local/bin/ffmpeg' );

define( 'WGCONF_HASHKEY'					,	'' );
define( 'WGCONF_PASSWORD_HASHKEY'			,	'' );

global \$WGCONF_AUTOLOAD;
\$WGCONF_AUTOLOAD = array(
	WGCONF_DIR_FRAMEWORK_VIEW6,
	WGCONF_DIR_FRAMEWORK_GAUNTLET,
	WGCONF_DIR_FRAMEWORK_MODEL,
	WGCONF_DIR_FRAMEWORK_EXT,
	WGCONF_DIR_SYS.'/include'
);
___END___;

	return $tf;
}

function file_conf()
{
	$tf = <<<___END___
<?php
//
// Common include script
//


___END___;
	return $tf;
}


function file_apache($domain, $dir, $email)
{
	$tf = <<<___END___
#
#
#
<VirtualHost *:80>
    ServerAdmin {$email}
    DocumentRoot "{$dir}/pub"
    ServerName {$domain}
    ErrorLog "{$domain}.error_log"
    CustomLog "{$domain}.access_log" common
</VirtualHost>

<Directory "{$dir}/pub">
    AllowOverride None
    Require all granted
    <FilesMatch "^_|~$|#$">
        Require all denied
    </FilesMatch>
    php_value include_path ".:{$dir}/sys/waggo"
</Directory>

___END___;

	return $tf;
}

function addsingleslashes($str)
{
	$r = "";
	for($i=0; $i<strlen($str); $i++)
	{
		switch($str[$i])
		{
			case "'":	$r .= "\\'";	break;
			case "\\":	$r .= "\\\\";	break;
			default:	$r .= $str[$i];	break;
		}
	}
	return $r;
}

function replace_waggoconf($filename,$dat)
{
	$dirinfo = install_dirinfo();
	$newconf = "";

	$edmsg = "// Edited by install.php at ".date("Y/m/d H:i:s");
	$lines = file($filename, FILE_IGNORE_NEW_LINES);
	foreach($lines as $line)
	{
		$checker = trim($line);
		if(preg_match('/^define(\s*\(\s*)([\'_0-9a-zA-Z]+)(\s*,\s*)([^\s+]+)\s*\)\s*;/',$checker,$m))
		{
			$key = trim($m[2], "'\t\n\r\0\x0B");
			switch($key)
			{
				case 'WG_INSTALLDIR':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dirinfo["application"]));
					break;
				case 'WG_LOGNAME':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes('waggo.'.$dat["domain"]["domain"].'.log'));
					break;
				case 'WGCONF_DBMS_DB':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["postgresql"]["dbname"]));
					break;
				case 'WGCONF_DBMS_USER':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["postgresql"]["username"]));
					break;
				case 'WGCONF_DBMS_PASSWD':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["postgresql"]["password"]));
					break;
				case 'WGCONF_DBMS_HOST':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["postgresql"]["host"]));
					break;
				case 'WGCONF_HASHKEY':
					$line = sprintf("define%s%s%s'%s' ); {$edmsg}", $m[1],$m[2],$m[3],addsingleslashes($dat["hash"]["general_hashkey"]));
					break;
				case 'WGCONF_PASSWORD_HASHKEY':
					$line = sprintf("define%s%s%s'%s' ); {$edmsg}", $m[1],$m[2],$m[3],addsingleslashes($dat["hash"]["password_hashkey"]));
					break;
				case 'WGCONF_PHPCLI':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["executable"]["phpcli"]));
					break;
				case 'WGCONF_CONVERT':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["executable"]["convert"]));
					break;
				case 'WGCONF_FFMPEG':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["executable"]["ffmpeg"]));
					break;
				case 'WGCONF_PEAR':
					$line = sprintf("define%s%s%s\"%s\" ); {$edmsg}", $m[1],$m[2],$m[3],addslashes($dat["pear"]["dir"]));
					break;
			}
		}
		$newconf .= rtrim($line) . "\n";
	}
	file_put_contents($filename, $newconf);
}

function install_gen_hash()
{
	$r = "";
	for($i=0; $i<32; $i++)
	{
		do
		{
			$c = chr(mt_rand(33,126));
		}
		while( $c=='"' || $c=="'" || $c=="\\" );
		$r .= $c;
	}
	return $r;
}

function install_instinfo()
{
	// データファイル検索
	$infs = array();
	$dir  = realpath( __DIR__ . '/..' );
	$handle = opendir($dir);
	$id   = 1;
	while( ($file = readdir($handle))!==false )
	{
		if( $file==='.' || $file==='..' ) continue;
		if( preg_match('/\.dat$/',$file) )
		{
			$datfile = "{$dir}/{$file}";
			$infs[$id++] = array($file, $datfile, parse_ini_file($datfile,true));
		}
	}
	closedir($handle);

	printf("================== 登録済みインストール情報 ==================\n");
	foreach($infs as $id=>$inf)
	{
		printf("  [%d] ... %s\n", $id, $inf[2]["domain"]["domain"]);
	}
	printf("  [0] ... 新規ドメイン\n");
	printf("--------------------------------------------------------------\n");
	printf("  [q] ... 終了\n");
	printf("==============================================================\n");

	// どのドメインを利用するか。
	do {
		$id = in("どのドメインを利用してインストールを行いますか？ -> ");
		if( $id=='q' ) exit;

		if(is_numeric($id)) $id = (int)$id;
	}
	while(!is_int($id) || ($id!=0 && !isset($infs[$id])));

	// デフォルト値生成
	$inf = ($id==0) ?
		array(
			'domain' =>
				array(
					'domain'   => '127.0.0.1'
				),
			'app' =>
				array(
					'prefix'   => 'App',
					'email'    => 'root@localhost'
				),
			'executable' =>
				array(
					'phpcli'   => detect_phpcli(),
					'convert'  => detect_convert(),
					'ffmpeg'   => detect_ffmpeg()
				),
			'pear' =>
				array(
					'dir'      => detect_pear()
				),
			'postgresql' =>
				array(
					'host'     => 'localhost',
					'dbname'   => 'waggo',
					'username' => 'waggo',
					'password' => 'password'
				),
			'app' =>
				array(
					'email'    => 'root@localhost'
				),
			'hash' =>
				array(
					'general_hashkey'  => install_gen_hash(),
					'password_hashkey' => install_gen_hash()
				)
		) : $infs[$id][2] ;

	// データ入力
	$settings = array(
		array('domain','domain',			'このフレームワークで構築するサイトのドメイン名',
			'127.0.0.1'),
		array('app','prefix',				'このフレームワークで構築するコントローラ等に付与する接頭句',
			'App'),
		array('app','email',				'連絡先メールアドレス',			'root@localhost'),
		array('executable','phpcli',		'PHP(CLI)',						detect_phpcli()),
		array('pear','dir',					'PEAR',							detect_pear()),
		array('executable','convert',		'convert(ImageMagick)',			detect_convert()),
		array('executable','ffmpeg',		'ffmpeg',						detect_ffmpeg()),
		array('postgresql','host',			'DB サーバアドレス',			'localhost'),
		array('postgresql','dbname',		'DB データベース名',			'waggo'),
		array('postgresql','username',		'DB 接続ユーザ名',				'waggo'),
		array('postgresql','password',		'DB 接続パスワード',			'password'),
		array('hash','general_hashkey',		'汎用ハッシュキー',				'通常は自動生成されています'),
		array('hash','password_hashkey',	'パスワード用ハッシュキー',		'通常は自動生成されています')
	);
	foreach($settings as $setting)
	{
		$def = @$inf[$setting[0]][$setting[1]];
		$inf[$setting[0]][$setting[1]] = indef(
			"● {$setting[2]}\n".
			"              例:({$setting[3]})\n".
			"   Enter規定値 -> {$def}\n".
			"          入力 -> ", $def, true);
		echo "\n";
	}

	// どのドメインを利用するか。
	if( q("設定ファイルを更新してもよいですか？ (Yes/No) -> ", array("Yes","No"))!=="Yes" ) return;

	// 設定値保存
	$filename = $dir."/install.".$inf["domain"]["domain"].".dat";
	$fp = fopen($filename,"w") or die("設定ファイルの保存に失敗しました。");
	foreach($inf as $k=>$kv)
	{
		fprintf($fp, "[%s]\n", $k);
		foreach($kv as $k=>$v)
		{
			fprintf($fp, "%s = \"%s\"\n", $k, $v);
		}
		fprintf($fp,"\n");
	}
	fclose($fp);

	// 設定ファイルの作成
	$dirinfo    = install_dirinfo();
	$domain     = $inf['domain']['domain'];
	$waggoconf  = $dirinfo['config']."/waggo.{$domain}.php";
	$apacheconf = $dirinfo['config']."/apache-vhosts.{$domain}.conf";
	$appconf    = $dirinfo['sys'].'/config.php';

	if(!file_exists($waggoconf)) file_put_contents($waggoconf, file_waggoconf());
	file_put_contents($apacheconf, file_apache($domain, $dirinfo["application"], $inf["app"]["email"] ));
	if(!file_exists($appconf))   file_put_contents($appconf, file_conf());

	// 初期テンプレートの複写
	$tpls = array('abort.html', 'iroot.html', 'mail.txt', 'null.html', 'pcroot.html', 'pcroot.xml');
	foreach($tpls as $name)
	{
		$src = "{$dirinfo['inittpl']}/{$name}";
		$dst = "{$dirinfo['tpl']}/{$name}";

		if(!file_exists($dirinfo["tpl"]."/".$name)) copy($dirinfo["inittpl"]."/".$name, $dirinfo["tpl"]."/".$name);
	}

	// 設定ファイルの更新
	replace_waggoconf($waggoconf,$inf);

	// コントローラー作成
	install_gencontroller($inf["app"]["prefix"]);

	//
	a("設定ファイルを更新しました。");
}
