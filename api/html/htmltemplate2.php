<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class HtmlTemplateEncoder2
{
	static public function cleanup($data)
	{
		$data = str_replace(
			array(
				"\0",   "\x01", "\x02", "\x03", "\x04", "\x05",
				"\x06", "\x07", "\x08", "\x0b", "\x0c", "\x0e", "\x0f"
			) , '' , $data );
		return $data;
	}

	static public function CDATA($cdata)
	{
		$caps =
			"<![CDATA[".
			strtr(self::cleanup($cdata), array(
				"<![CDATA["		=>	"&lt;![CDATA[",
				"]]>"			=>	"]]&gt;")
			)."]]>";
		return $caps;
	}

	static public function VR($data)  { return self::cleanup($data); }
	static public function VN($data)  { return htmlspecialchars(number_format((int)self::cleanup($data))); }
	static public function VT($data)  { return htmlspecialchars(self::cleanup($data)); }
	static public function V($data)   { return nl2br(htmlspecialchars(self::cleanup($data))); }
	static public function VD($data)  { return wg_format_date(self::cleanup($data),false); }
	static public function VH($data)  { return wg_format_date(self::cleanup($data),true); }
}

class HtmlTemplate2
{
	static protected function hash_cachefile($file)
	{
		return md5(sprintf("%s/%s#%d", $_SERVER["PHP_SELF"], $file, filemtime(__FILE__))).".php";
	}

	static protected function t_cache($file)
	{
		$id    = self::hash_cachefile($file);
		$dir1  = substr($id,0,1);
		$dir2  = substr($id,1,1);
		$srctime   = @filemtime($file);
		$cachetime = @filemtime(WGCONF_CANVASCACHE."/$dir1/$dir2/$id");

		if( $srctime==false || $srctime>$cachetime ) return false;
		return true;
	}

	static protected function t_readcache($file)
	{
		$id    = self::hash_cachefile($file);
		$dir1  = substr($id,0,1);
		$dir2  = substr($id,1,1);
		$cfile = WGCONF_CANVASCACHE."/$dir1/$dir2/$id";
		$all   = fread(fopen($cfile,"r"),filesize($cfile));
		return $all;
	}

	static protected function t_runcache($file,$val)
	{
		$id    = self::hash_cachefile($file);
		$dir1  = substr($id,0,1);
		$dir2  = substr($id,1,1);
		$cfile = WGCONF_CANVASCACHE."/$dir1/$dir2/$id";
		include $cfile;
	}

	static protected function t_writecache($file,$eval)
	{
		$id    = self::hash_cachefile($file);
		$dir1  = substr($id,0,1);
		$dir2  = substr($id,1,1);
		$cfile = WGCONF_CANVASCACHE."/$dir1/$dir2/$id";

		$dir = WGCONF_CANVASCACHE."/$dir1";
		if( !is_dir($dir) )
		{
			mkdir($dir,0777);
			if( !is_dir($dir) )
			{
				printf("<html><body><p>テンプレートキャッシュディレクトリの作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($dir));
				return;
			}
		}

		$dir .= "/$dir2";
		if( !is_dir($dir) )
		{
			mkdir($dir,0777);
			if( !is_dir($dir) )
			{
				printf("<html><body><p>テンプレートキャッシュディレクトリ2の作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($dir));
				return;
			}
		}

		$fp = @fopen($cfile,"w");
		if( $fp!=false )
		{
			fwrite($fp,$eval);
			ftruncate($fp,ftell($fp));
			fclose($fp);
		}
		else
		{
			printf("<html><body><p>テンプレートキャッシュファイルの作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($cfile));
		}
	}

	/**
	 * Interprit a file on memory and output the result.
	 * @access public
	 * @param string $file Filename
	 * @param array $data a tree-like array
	 * @return void
	 */
	static public function t_Include($file,$data)
	{
		if( self::t_cache($file)==true )
		{
			self::t_runcache($file,$data);
		}
		else
		{
			$lk   = [];
			$lv   = [];
			$val  = $data;
			$all  = @file_get_contents($file);
			$code = self::_parsesrc($all);
			$src  = $code;
			self::t_writecache($file,$src);
			eval('?>'.$src);
		}
	}

	/**
	 * Interprit a file on memory and require the result as a string.
	 * @access public
	 * @param String $file Filename
	 * @param array $data a tree-like array
	 * @return string
	 */
	static public function t_Buffer($file,$data)
	{
		$val  = $data;
		$all  = @file_get_contents($file);
		$code = self::_parsesrc($all);

		ob_start();
		echo eval('?>'.$code);
		$ans=ob_get_contents();
		ob_end_clean();

		return $ans;
	}

	static public function xml_Include($file,$data,$encoding="utf-8")
	{
		$val=$data;
		$all=fread(fopen($file,"rb"),filesize($file));
		$code=self::_parsesrc($all);
		ob_start();
		echo eval('?>'.$code);
		$ans=ob_get_contents();
		ob_end_clean();

		switch( strtolower($encoding) )
		{
			case "sjis":
			case "shiftjis":
			case "shift-jis":
			case "shift_jis":
				$xmlenc = "Shift_JIS";
				$enc    = "SJIS";
				break;
			case "euc":
			case "eucjp":
			case "euc-jp":
			case "euc_jp":
				$xmlenc = "euc-jp";
				$enc    = "EUC-JP";
				break;
			case "utf8":
			case "utf-8":
			default:
				$xmlenc = "UTF-8";
				$enc    = "UTF-8";
				break;
		}

		header("Content-type: text/xml");
		$ans='<?xml version="1.0" encoding="'.$xmlenc.'"?>'."\r\n".$ans;
		echo mb_convert_encoding( $ans , $enc, "utf-8" );
	}

	static public function i_Include($file,$data)
	{
		$val=$data;
		$all=fread(fopen($file,"rb"),filesize($file));
		$code=self::_parsesrc($all);
		ob_start();
		echo eval('?>'.$code);
		$ans=ob_get_contents();
		ob_end_clean();
		echo mb_convert_encoding( $ans , "ShiftJIS", "utf-8" );
	}

	static public function i_Buffer($file,$data)
	{
		$val=$data;
		$all=fread(fopen($file,"rb"),filesize($file));
		$code=self::_parsesrc($all);
		ob_start();
		echo eval('?>'.$code);
		$ans=ob_get_contents();
		ob_end_clean();
		return mb_convert_encoding($ans,"SJIS","EUC-JP");
	}

	/**
	 * Parse HTML strings.
	 * @access private
	 * @param String $str HTML strings.
	 * @return String
	 */
	static protected function _parsesrc($str)
	{
		#translate \r\n to \n
		$str=str_replace("\r\n","\n",$str);
		$str=str_replace("\n\r","\n",$str);

		$kuri=array();
		$str2=$str;
		$acc=1;

		# interpretation of <!--{each }--><!--{/each}-->
		$kuri=array();

		preg_match_all('/<!--\{each ([^\}]+)\}-->/i', $str2, $eachlist, PREG_SET_ORDER);
		foreach($eachlist as $el)
		{
			list($m,$e) = $el;
			$f    = explode('/', $e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?
				"foreach(\$val['{$k1}'] as \$lk['${k2}'] => \$lv['{$k2}']) { " :
				"foreach(\$lv['{$k1}'] as \$lk['${k2}'] => \$lv['{$k2}']) { " ;
			$str2 = str_replace($m, "<?php {$c} ?>", $str2);
		}

		$str2=str_replace("<!--{/each}-->", "<?php } ?>", $str2);

		# interpretation of {?val }
		preg_match_all('/\{([a-z]*)val (.+?)\}/i', $str2, $vallist, PREG_SET_ORDER);
		foreach($vallist as $vl)
		{
			list($m,$r,$e) = $vl;
			$f    = explode("/",$e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?	"\$val['{$k1}']" : "\$lv['{$k1}']['{$k2}']" ;
			$r    = strtoupper($r);
			$c    = "HTE::V{$r}({$c})";
			$str2=str_replace($m, "<?php echo {$c}; ?>",$str2);
		}

		# interpretation of {CDATA }
		preg_match_all('/\{CDATA (.+?)\}/i', $str2, $vallist, PREG_SET_ORDER);
		foreach($vallist as $vl)
		{
			list($m,$e) = $vl;
			$f    = explode("/",$e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?	"\$val['{$k1}']" : "\$lv['{$k1}']['{$k2}']" ;
			$c    = "HTE::CDATA({$c})";
			$str2 = str_replace($m, "<?php echo {$c}; ?>",$str2);
		}

		# interpretation of {@}
		preg_match_all('/\{@(.+?)\}/i', $str2, $vallist, PREG_SET_ORDER);
		foreach($vallist as $vl)
		{
			list($m,$e) = $vl;
			$f    = explode("/",$e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?	"\$val['{$k1}']" : "\$lv['{$k1}']['{$k2}']" ;
			$str2 = str_replace($m, "<?php echo {$c}; ?>",$str2);
		}

		# interpretation of {%}
		preg_match_all('/\{%(.+?)\}/i', $str2, $vallist, PREG_SET_ORDER);
		foreach($vallist as $vl)
		{
			list($m,$e) = $vl;
			$f    = explode("/",$e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?	"\$val['{$k1}']" : "\$lv['{$k1}']['{$k2}']" ;
			$str2 = str_replace($m, "<?php echo {$c}; ?>",$str2);
		}

		# interpretation of {@|% }
		preg_match_all('/\{%(.+?)\}/i', $str2, $vallist, PREG_SET_ORDER);
		foreach($vallist as $vl)
		{
			list($m,$e) = $vl;
			$f    = explode("/",$e);
			$l    = count($f);
			$k1   = $f[max(0,$l-2)];
			$k2   = $f[max(0,$l-1)];
			$c    = $l==1 ?	"\$val['{$k1}']" : "\$lv['{$k1}']['{$k2}']" ;
			$str2 = str_replace($m, "<?php echo {$c}; ?>",$str2);

			$a    = !empty($a) ? "\":{$a}\"" : "\"\"";
			$str2 = str_replace("{".$rep."}","<?php echo \$val[\$val$ind.$a]; ?>", $str2);
		}

		# interpretation of <!--{(n?)def }--><!--{else}--><!--{/def}-->
		while(preg_match('/<!--\{(n?)def ([^\}]+)\}-->/i',$str2,$match))
		{
			$n=$match[1];
			$m=$match[2];
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$mattan=0;
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if($mattan!=count($ar)-1 && in_array(join("/",$rui),$kuri))
				{
					$ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				}
				else
				{
					$ind.="[\"".$x."\"]";
				}
				$mattan++;
			}
			$xor = ($n!="") ? "true" : "false";
			$str2=str_replace("<!--{{$n}def $m}-->",
				"<?php ".
				"if(".
				"((!is_array(\$val$ind) && \$val$ind!=\"\") or".
				" (is_array(\$val$ind) && count(\$val$ind)>0)) xor $xor){ ?>",
			$str2);
		}
		$str2=str_replace( "<!--{/def}-->","<?php } ?>" , $str2);
		$str2=str_replace( "<!--{else}-->","<?php } else { ?>" , $str2);

		# interpretation of {var?text}
		while(preg_match('/\{([\w\/:\-]+)\?([^\}]+)\}/i',$str2,$match))
		{
			$m=$match[1];
			$v=$match[2];
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$mattan=0;
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if($mattan!=count($ar)-1 && in_array(join("/",$rui),$kuri))
				{
					$ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				}
				else
				{
					$ind.="[\"".$x."\"]";
				}
				$mattan++;
			}
			$str2=str_replace($match[0],
				"<?php if((!is_array(\$val$ind) && \$val$ind!=\"\") or (is_array(\$val$ind) && count(\$val$ind)>0)) echo '".addslashes($v)."'; ?>",
				$str2);
		}

		# interpretation of <!--{switch }--><!--{case }--><!--{/case}--><!--{/switch}-->
		while(preg_match('/<!--\{switch ([^\}]+)\}-->/i',$str2,$match))
		{
			$m=$match[1];
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$mattan=0;
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if($mattan!=count($ar)-1 && in_array(join("/",$rui),$kuri))
				{
					$ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				}
				else
				{
					$ind.="[\"".$x."\"]";
				}
				$mattan++;
			}
			$str2=str_replace("<!--{switch $m}-->",
					"<?php ".
					"switch(\$val$ind) { ?>",$str2);
		}
		# case
		while(preg_match('/<!--\{case ([^\}]+)\}-->/i',$str2,$match))
		{
			$m = $match[1];
			$str2=str_replace("<!--{case $m}-->","<?php case \"".addslashes($m)."\": ?>",$str2);
		}
		$str2=str_replace( "<!--{default}-->","<?php default: ?>" , $str2);
		$str2=str_replace( "<!--{/case}-->","<?php break; ?>" , $str2);
		$str2=str_replace( "<!--{/switch}-->","<?php } ?>" , $str2);

		# end
		return $str2;
	}
}
