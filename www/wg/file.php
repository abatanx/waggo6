<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/../../waggo.php";
require_once dirname(__FILE__)."/../../framework/c/WGFSession.php";
session_start();

//wg_errordump($_SESSION);

$f = true;
$f &= wg_inchk_string($ssid,$_GET["ssid"],1,32);
$f &= wg_inchk_string($trid,$_GET["trid"],1,32);
$f &= wg_inchk_string($vk,$_GET["vk"],1,32);
$f &= wg_inchk_string($mode,$_GET["m"],1,32);
$f &= wg_inchk_string($uid,$_GET["uid"],1,32);
wg_inchk_string($hid,$_GET["hid"],1,32);

?>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="/wgcss/waggo.css">
<link rel="stylesheet" type="text/css" href="/wgcss/table.css">
<link rel="stylesheet" type="text/css" href="/wgcss/uiarea.css">
<link rel="stylesheet" type="text/css" href="/wgcss/button.css">
<link rel="stylesheet" type="text/css" href="/wgcss/wiki.css">
<script type="text/javascript" charset="utf-8" src="/wgjs/waggo.js"></script>
<script type="text/javascript" charset="utf-8" src="/wgjs/waggoview.js"></script>
<style type="text/css">
body,form,table {
	margin: 0px;
	padding: 0px;
	color: inherit;
	background-color: inherit;
	font-size: 10pt;
}
</style>
<script type="text/javascript" charset="utf-8">
function wgvajaxfile_resize_iframe()
{
	var height = document.body.scrollHeight;
	window.parent.document.getElementById('<?=$uid?>').style.height = height + 'px';
}
function wgvajaxfile_submit(hid)
{
	parent.document.getElementById(hid).click();
}
</script>
</head>
<html>
	<body onload="wgvajaxfile_resize_iframe();">
<?php
if(!$f) die("\t\tファイルのアップロードはできません。\n\t</body>\n</html>");
$v = new WGVFile();
$v->session = new WGFSession($ssid, $trid);
$v->setKey($vk);
$v->init();
if(strtoupper($_SERVER["REQUEST_METHOD"])=="POST")
{
	$v->unsetError();
	$v->postCopy();
	if($hid)
	{
		printf('<script type="text/javascript" charset="utf-8">wgvajaxfile_submit("%s");</script>', $hid);
		printf('</body></html>');
		exit;
	}
}
$h = $mode=="form" ? $v->formHtml() : $v->showHtml();
if($v->getValue()==false) $s = '<input id="'.$uid.'_upload" name="submit" type="submit" value="アップロード">';
$a = wg_remake_uri();
?>
		<form action="<?=$a?>" method="post" enctype="multipart/form-data"><?=$h?><?=$s?></form>
	</body>
</html>
