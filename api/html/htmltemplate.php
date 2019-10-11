<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class HtmlTemplateEncoder
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
	static public function VD($data)  { return wg_datetime_format(self::cleanup($data),false); }
	static public function VH($data)  { return wg_datetime_format(self::cleanup($data),true); }
	static public function VJ($data)  { return json_encode(self::cleanup($data)); }
}

class HtmlTemplate
{
	static protected function hash_cachefile($file)
	{
		return md5(sprintf("%s/%s#%d", $_SERVER["PHP_SELF"], $file, filemtime(__FILE__))).".php";
	}

	static protected function t_cache($__file__)
	{
		$__id__ = self::hash_cachefile($__file__);
		$__d1__ = substr($__id__,0,1);
		$__d2__ = substr($__id__,1,1);
		$__st__ = @filemtime($__file__);
		$__ct__ = @filemtime(WGCONF_CANVASCACHE."/$__d1__/$__d2__/$__id__");
		if( $__st__ === false || $__st__ > $__ct__ ) return false;
		return true;
	}

	static protected function t_runcache($__file__,$__data__)
	{
		$__id__ = self::hash_cachefile($__file__);
		$__d1__ = substr($__id__,0,1);
		$__d2__ = substr($__id__,1,1);
		$__incfile__ = WGCONF_CANVASCACHE."/$__d1__/$__d2__/$__id__";
		$val = $__data__;
		include $__incfile__;
	}

	static protected function t_writecache($__file__,$__code__)
	{
		$__id__ = self::hash_cachefile($__file__);
		$__d1__ = substr($__id__,0,1);
		$__d2__ = substr($__id__,1,1);
		$__incfile__ = WGCONF_CANVASCACHE."/$__d1__/$__d2__/$__id__";
		$__d__ = WGCONF_CANVASCACHE."/$__d1__";
		if( !is_dir($__d__) )
		{
			mkdir($__d__,0777);
			if( !is_dir($__d__) )
			{
				printf("<html><body><p>テンプレートキャッシュディレクトリの作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($__d__));
				return false;
			}
		}

		$__d__ .= "/$__d2__";
		if( !is_dir($__d__) )
		{
			mkdir($__d__,0777);
			if( !is_dir($__d__) )
			{
				printf("<html><body><p>テンプレートキャッシュディレクトリ2の作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($__d__));
				return false;
			}
		}

		if( @file_put_contents($__incfile__, $__code__) === false )
		{
			printf("<html><body><p>テンプレートキャッシュファイルの作成に失敗しました。<br>%s</p></body></html>" , htmlspecialchars($__incfile__));
		}
		return $__incfile__;
	}

	/**
	 * Interprit a file on memory and output the result.
	 * @access public
	 *
	 * @param string $__file__ Filename
	 * @param array $__data__ a tree-like array
	 *
	 * @return void
	 */
	static public function t_Include($__file__,$__data__)
	{
		if( self::t_cache( $__file__) === true )
		{
			self::t_runcache( $__file__,$__data__);
		}
		else
		{
			$val = $__data__;
			$__code__ = self::_parsesrc(@file_get_contents($__file__));
			$__incfile__ = self::t_writecache($__file__,$__code__);
			if( $__incfile__ !== false ) include($__incfile__);
		}
	}

	/**
	 * Interprit a file on memory and require the result as a string.
	 * @access public
	 *
	 * @param String $__file__ Filename
	 * @param array $__data__ a tree-like array
	 *
	 * @return string
	 */
	static public function t_Buffer($__file__,$__data__)
	{
		ob_start();
		self::t_Include($__file__,$__data__);
		$__result__ = ob_get_contents();
		ob_end_clean();
		return $__result__;
	}

	static public function xml_Include($__file__,$__data__,$__encoding__="utf-8")
	{
		$val = $__data__;
		$__code__ = self::_parsesrc(@file_get_contents($__file__));
		ob_start();
		echo eval('?>'.$__code__);
		$__result__ = ob_get_contents();
		ob_end_clean();

		switch(strtolower($__encoding__))
		{
			case "sjis":
			case "shiftjis":
			case "shift-jis":
			case "shift_jis":
				$__encx__ = "Shift_JIS";
				$__encp__ = "SJIS";
				break;
			case "euc":
			case "eucjp":
			case "euc-jp":
			case "euc_jp":
				$__encx__ = "euc-jp";
				$__encp__ = "EUC-JP";
				break;
			case "utf8":
			case "utf-8":
			default:
				$__encx__ = "UTF-8";
				$__encp__ = "UTF-8";
				break;
		}

		header("Content-type: text/xml");
		$__result__ = '<?xml version="1.0" encoding="'.$__encx__.'"?>' . "\r\n" . $__result__ ;
		echo mb_convert_encoding( $__result__ , $__encp__, "utf-8" );
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
		preg_match_all("/<!--\{each ([^\}]+)\}-->/i",$str2,$k,PREG_SET_ORDER);
		while(list(,$x)=each($k)){
			$kuri[]=$x[1];
		}
		while( list(,$m)=each($kuri) )
		{
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$mattan=0;
			$loopid1=1;
			while(list(,$x)=each($ar))
			{
				array_push($rui,$x);
				if($mattan!=count($ar)-1 && in_array(join("/",$rui),$kuri))
				{
					$ind.="[\"$x\"][\$cnt[\"".join("_",$rui)."\"]]";
				}
				else
				{
					$ind.="[\"$x\"]";
				}
				$mattan++;
			}
			$n=str_replace("/","_",$m);
			$str2=str_replace("<!--{each $m}-->",
					"<?php ".
					"for(\$cnt[\"$n\"]=0;is_array(\$val$ind) && \$cnt[\"$n\"]<count(\$val$ind);\$cnt[\"$n\"]++){".
					" ?>", $str2);
		}
		reset($kuri);

		$str2=str_replace("<!--{/each}-->", "<?php } ?>", $str2);

		# interpretation of {?val }
		while(preg_match('/\{([a-z]*)val (.+?)\}/',$str2,$match))
		{
			$r=$match[1];
			$m=$match[2];
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if(in_array(join("/",$rui),$kuri)) $ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				else $ind.="[\"". $x."\"]";
			}

			$c = strtoupper($r);
			$fmt = "HTE::V{$c}(\$val$ind)";

			$str2=str_replace("{".$r."val $m}","<?php echo $fmt; ?>",$str2);
		}

		# interpretation of {CDATA }
		while(preg_match('/\{CDATA (.+?)\}/',$str2,$match))
		{
			$m=$match[1];
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if(in_array(join("/",$rui),$kuri)) $ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				else $ind.="[\"". $x."\"]";
			}

			$fmt ="HTE::CDATA(\$val$ind)";
			$str2=str_replace("{CDATA $m}","<?php echo $fmt; ?>",$str2);
		}

		# interpretation of {@|% }
		while(preg_match('/\{(\@)(.+?)\}/',$str2,$match))
		{
			$rep=$match[1].$match[2];
			list($m)=explode(",",$match[2]);
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$a = null;
			foreach($ar as $x)
			{
				array_push($rui,$x);
				if(in_array(join("/",$rui),$kuri)) $ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				else $ind.="[\"".$x."\"]";
			}
			$str2 = str_replace("{".$rep."}","<?php echo \$val$ind; ?>", $str2);
		}

		# interpretation of {@|% }
		while(preg_match('/\{(%)(.+?)\}/',$str2,$match))
		{
			$rep=$match[1].$match[2];
			list($m)=explode(",",$match[2]);
			$ar=explode("/",$m);
			$ind="";
			$rui=array();
			$a = null;
			foreach($ar as $x)
			{
				list($x,$a) = explode(':',$x );
				array_push($rui,$x);
				if(in_array(join("/",$rui),$kuri)) $ind.="[\"".$x."\"][\$cnt[\"".join("_",$rui)."\"]]";
				else $ind.="[\"".$x."\"]";
			}
			$a = !empty($a) ? "\":{$a}\"" : "\"\"";
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
		$str2=str_replace( "<!--{/case}-->","<?php break; ?>" , $str2);
		$str2=str_replace( "<!--{default}-->","<?php default: ?>" , $str2);
		$str2=str_replace( "<!--{/default}-->","<?php break; ?>" , $str2);
		$str2=str_replace( "<!--{/switch}-->","<?php } ?>" , $str2);

		# end
		return $str2;
	}
}
