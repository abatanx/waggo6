<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class HtmlTemplateEncoder
{
	static public function cleanup( $data )
	{
		$data = str_replace(
			array(
				"\0",
				"\x01",
				"\x02",
				"\x03",
				"\x04",
				"\x05",
				"\x06",
				"\x07",
				"\x08",
				"\x0b",
				"\x0c",
				"\x0e",
				"\x0f"
			), '', $data );

		return $data;
	}

	static public function CDATA( $cdata )
	{
		$caps =
			"<![CDATA[" .
			strtr( self::cleanup( $cdata ), array(
					"<![CDATA[" => "&lt;![CDATA[",
					"]]>"       => "]]&gt;"
				)
			) . "]]>";

		return $caps;
	}

	static public function rval( $data )
	{
		return self::cleanup( $data );
	}

	static public function nval( $data )
	{
		return htmlspecialchars( number_format( (int) self::cleanup( $data ) ) );
	}

	static public function tval( $data )
	{
		return htmlspecialchars( self::cleanup( $data ) );
	}

	static public function val( $data )
	{
		return nl2br( htmlspecialchars( self::cleanup( $data ) ) );
	}

	static public function dval( $data )
	{
		return wg_datetime_format( self::cleanup( $data ), false );
	}

	static public function hval( $data )
	{
		return wg_datetime_format( self::cleanup( $data ), true );
	}

	static public function jval( $data )
	{
		return json_encode( self::cleanup( $data ) );
	}
}

class HtmlTemplate
{
	static protected function hash_cachefile( $file )
	{
		return md5( sprintf( "%s/%s#%d", $_SERVER["PHP_SELF"], $file, filemtime( __FILE__ ) ) ) . ".php";
	}

	static protected function t_cache( $__file__ )
	{
		$__id__ = self::hash_cachefile( $__file__ );
		$__d1__ = substr( $__id__, 0, 1 );
		$__d2__ = substr( $__id__, 1, 1 );
		$__st__ = @filemtime( $__file__ );
		$__ct__ = @filemtime( WGCONF_CANVASCACHE . "/$__d1__/$__d2__/$__id__" );
		if ( $__st__ === false || $__st__ > $__ct__ )
		{
			return false;
		}

		return true;
	}

	static protected function t_runcache( $__file__, $__data__ )
	{
		$__id__      = self::hash_cachefile( $__file__ );
		$__d1__      = substr( $__id__, 0, 1 );
		$__d2__      = substr( $__id__, 1, 1 );
		$__incfile__ = WGCONF_CANVASCACHE . "/$__d1__/$__d2__/$__id__";
		$val         = $__data__;
		include $__incfile__;
	}

	static protected function t_writecache( $__file__, $__code__ )
	{
		$__id__      = self::hash_cachefile( $__file__ );
		$__d1__      = substr( $__id__, 0, 1 );
		$__d2__      = substr( $__id__, 1, 1 );
		$__incfile__ = WGCONF_CANVASCACHE . "/$__d1__/$__d2__/$__id__";
		$__d__       = WGCONF_CANVASCACHE . "/$__d1__";
		if ( ! is_dir( $__d__ ) )
		{
			mkdir( $__d__, 0777 );
			if ( ! is_dir( $__d__ ) )
			{
				printf( "<html><body><p>Can't create a directory of compiled template cache file.<br>%s</p></body></html>", htmlspecialchars( $__d__ ) );

				return false;
			}
		}

		$__d__ .= "/$__d2__";
		if ( ! is_dir( $__d__ ) )
		{
			mkdir( $__d__, 0777 );
			if ( ! is_dir( $__d__ ) )
			{
				printf( "<html><body><p>Can't create a directory of compiled template cache file.<br>%s</p></body></html>", htmlspecialchars( $__d__ ) );

				return false;
			}
		}

		if ( @file_put_contents( $__incfile__, $__code__ ) === false )
		{
			printf( "<html><body><p>Can't create compiled template cache file.<br>%s</p></body></html>", htmlspecialchars( $__incfile__ ) );
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
	static public function t_Include( $__file__, $__data__ )
	{
		if ( self::t_cache( $__file__ ) === true )
		{
			self::t_runcache( $__file__, $__data__ );
		}
		else
		{
			$val         = $__data__;
			$__code__    = self::_parsesrc( @file_get_contents( $__file__ ) );
			$__incfile__ = self::t_writecache( $__file__, $__code__ );
			if ( $__incfile__ !== false )
			{
				include( $__incfile__ );
			}
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
	static public function t_Buffer( $__file__, $__data__ )
	{
		ob_start();
		self::t_Include( $__file__, $__data__ );
		$__result__ = ob_get_contents();
		ob_end_clean();

		return $__result__;
	}

	static public function xml_Include( $__file__, $__data__, $__encoding__ = "utf-8" )
	{
		$val      = $__data__;
		$__code__ = self::_parsesrc( @file_get_contents( $__file__ ) );
		ob_start();
		echo eval( '?>' . $__code__ );
		$__result__ = ob_get_contents();
		ob_end_clean();

		switch ( strtolower( $__encoding__ ) )
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

		header( "Content-type: text/xml" );
		$__result__ = '<?xml version="1.0" encoding="' . $__encx__ . '"?>' . "\r\n" . $__result__;
		echo mb_convert_encoding( $__result__, $__encp__, "utf-8" );
	}

	/**
	 * Parse HTML strings.
	 * @access private
	 *
	 * @param String $str HTML strings.
	 *
	 * @return String
	 */
	static protected function _parsesrc( $str )
	{
		#translate \r\n to \n
		$str = str_replace( "\r\n", "\n", $str );
		$str = str_replace( "\n\r", "\n", $str );

		# interpretation of <!--{each }--><!--{/each}-->
		preg_match_all( "/<!--\{each ([^\}]+)\}-->/i", $str, $k, PREG_SET_ORDER );
		$kuri = array_map( function ( $v ) {
			return $v[1];
		}, $k );

		foreach ( $kuri as $m )
		{
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $idx => $x )
			{
				$rui[] = $x;
				if ( $idx != count( $ar ) - 1 && in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"$x\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"$x\"]";
				}
			}
			$n    = str_replace( "/", "_", $m );
			$str = str_replace( "<!--{each $m}-->",
				"<?php " .
				"if(isset(\$val$ind) && is_array(\$val$ind)) for(\$cnt[\"$n\"]=0;\$cnt[\"$n\"]<count(\$val$ind);\$cnt[\"$n\"]++){" .
				" ?>", $str );
		}

		$str = str_replace( "<!--{/each}-->", "<?php } ?>", $str );

		# interpretation of {?val }
		while ( preg_match( '/\{([a-z]*)val (.+?)\}/', $str, $match ) )
		{
			$r   = $match[1];
			$m   = $match[2];
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $x )
			{
				$rui[] = $x;
				if ( in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}

			$c   = strtolower( $r );
			$fmt = "HTE::{$c}val(\$val$ind)";

			$str = str_replace( "{" . $r . "val $m}", "<?php if( isset(\$val$ind) ) echo $fmt; ?>", $str );
		}

		# interpretation of {CDATA }
		while ( preg_match( '/\{CDATA (.+?)\}/', $str, $match ) )
		{
			$m   = $match[1];
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $x )
			{
				$rui[] = $x;
				if ( in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}

			$fmt  = "HTE::CDATA(\$val$ind)";
			$str = str_replace( "{CDATA $m}", "<?php echo $fmt; ?>", $str );
		}

		# interpretation of {@|% }
		while ( preg_match( '/\{(\@)(.+?)\}/', $str, $match ) )
		{
			$rep = $match[1] . $match[2];
			list( $m ) = explode( ",", $match[2] );
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			$a   = null;
			foreach ( $ar as $x )
			{
				$rui[] = $x;
				if ( in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}
			$str = str_replace( "{" . $rep . "}", "<?php echo \$val$ind; ?>", $str );
		}

		# interpretation of {@|% }
		while ( preg_match( '/\{(%)(.+?)\}/', $str, $match ) )
		{
			$rep = $match[1] . $match[2];
			list( $m ) = explode( ",", $match[2] );
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			$a   = null;
			foreach ( $ar as $x )
			{
				list( $x, $a ) = explode( ':', $x );
				$rui[] = $x;
				if ( in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}
			$a    = ! empty( $a ) ? "\":{$a}\"" : "\"\"";
			$str = str_replace( "{" . $rep . "}", "<?php echo \$val[\$val$ind.$a]; ?>", $str );
		}

		# interpretation of <!--{(n?)def }--><!--{else}--><!--{/def}-->
		while ( preg_match( '/<!--\{(n?)def ([^\}]+)\}-->/i', $str, $match ) )
		{
			$n   = $match[1];
			$m   = $match[2];
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $idx => $x )
			{
				$rui[] = $x;
				if ( $idx != count( $ar ) - 1 && in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}
			$xor  = ( $n != "" ) ? "true" : "false";
			$str = str_replace( "<!--{{$n}def $m}-->",
				"<?php " .
				"if(" .
				"((isset(\$val$ind) && !is_array(\$val$ind) && \$val$ind!=\"\") or" .
				" (isset(\$val$ind) && is_array(\$val$ind) && count(\$val$ind)>0)) xor $xor){ ?>",
				$str );
		}
		$str = str_replace( "<!--{/def}-->", "<?php } ?>", $str );
		$str = str_replace( "<!--{else}-->", "<?php } else { ?>", $str );

		# interpretation of {var?text}
		while ( preg_match( '/\{([\w\/:\-]+)\?([^\}]+)\}/i', $str, $match ) )
		{
			$m   = $match[1];
			$v   = $match[2];
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $idx => $x )
			{
				$rui[] = $x;
				if ( $idx != count( $ar ) - 1 && in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}
			$str = str_replace( $match[0],
				"<?php if((isset(\$val$ind) && !is_array(\$val$ind) && \$val$ind!=\"\") or (isset(\$val$ind) && is_array(\$val$ind) && count(\$val$ind)>0)) echo '" . addslashes( $v ) . "'; ?>",
				$str );
		}

		# interpretation of <!--{switch }--><!--{case }--><!--{/case}--><!--{/switch}-->
		while ( preg_match( '/<!--\{switch ([^\}]+)\}-->/i', $str, $match ) )
		{
			$m   = $match[1];
			$ar  = explode( "/", $m );
			$ind = "";
			$rui = [];
			foreach ( $ar as $idx => $x )
			{
				$rui[] = $x;
				if ( $idx != count( $ar ) - 1 && in_array( implode( "/", $rui ), $kuri ) )
				{
					$ind .= "[\"" . $x . "\"][\$cnt[\"" . implode( "_", $rui ) . "\"]]";
				}
				else
				{
					$ind .= "[\"" . $x . "\"]";
				}
			}
			$str = str_replace( "<!--{switch $m}-->",
				"<?php " .
				"switch(\$val$ind) { ?>", $str );
		}

		# case
		while ( preg_match( '/<!--\{case ([^\}]+)\}-->/i', $str, $match ) )
		{
			$m    = $match[1];
			$str = str_replace( "<!--{case $m}-->", "<?php case \"" . addslashes( $m ) . "\": ?>", $str );
		}
		$str = str_replace( "<!--{/case}-->", "<?php break; ?>", $str );
		$str = str_replace( "<!--{default}-->", "<?php default: ?>", $str );
		$str = str_replace( "<!--{/default}-->", "<?php break; ?>", $str );
		$str = str_replace( "<!--{/switch}-->", "<?php } ?>", $str );

		# end
		return $str;
	}
}
