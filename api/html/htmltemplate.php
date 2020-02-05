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

	static public function cdata( $cdata )
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
	static protected function die( $msg )
	{
		if ( function_exists( 'wgdie' ) )
		{
			wgdie( $msg );
		}
		else
		{
			$m = htmlspecialchars( $msg );
			echo <<<___END___
<html lang="en">
<body>
	<p>{$m}</p>
</body>
</html>
___END___;
			die();
		}
	}

	static protected function __SANDBOX__( $__FILENAME__, $__ARY__ )
	{
		// Don't change argument variable names, $__FILENAME__, $__VAL__ .
		$__IDX__ = [];
		$__VAL__ = [];
		echo $__FILENAME__;
		include( $__FILENAME__ );
	}

	static protected function getCacheHash( $file )
	{
		return md5( sprintf( "%s/%s#%d", $_SERVER["PHP_SELF"], $file, filemtime( __FILE__ ) ) ) . ".php";
	}

	static protected function isCached( $file )
	{
		$hs = self::getCacheHash( $file );
		$d1 = substr( $hs, 0, 1 );
		$d2 = substr( $hs, 1, 1 );
		$st = @filemtime( $file );
		$ct = @filemtime( WGCONF_CANVASCACHE . "/$d1/$d2/$hs" );

		return ! ( $st === false || $st > $ct );
	}

	static protected function runCache( $file, $val )
	{
		$hs = self::getCacheHash( $file );
		$d1 = substr( $hs, 0, 1 );
		$d2 = substr( $hs, 1, 1 );
		$fi = WGCONF_CANVASCACHE . "/$d1/$d2/$hs";
		self::__SANDBOX__( $fi, $val );
	}

	static protected function storeCache( $file, $code )
	{
		$hs = self::getCacheHash( $file );
		$d1 = substr( $hs, 0, 1 );
		$d2 = substr( $hs, 1, 1 );
		$fi = WGCONF_CANVASCACHE . "/$d1/$d2/$hs";
		$d0 = WGCONF_CANVASCACHE . "/$d1";
		if ( ! is_dir( $d0 ) )
		{
			@mkdir( $d0, 0777 );
			if ( ! is_dir( $d0 ) || ! is_readable( $d0 ) || ! is_writable( $d0 ) )
			{
				self::die( sprintf( "Can't create a directory of compiled template cache file.\n%s", htmlspecialchars( $d0 ) ) );
			}
		}

		$d0 .= "/$d2";
		if ( ! is_dir( $d0 ) )
		{
			@mkdir( $d0, 0777 );
			if ( ! is_dir( $d0 ) || ! is_readable( $d0 ) || ! is_writable( $d0 ) )
			{
				self::die( sprintf( "Can't create a directory of compiled template cache file.\n%s", htmlspecialchars( $d0 ) ) );
			}
		}

		if ( @file_put_contents( $fi, $code ) === false )
		{
			self::die( sprintf( "Can't create a directory of compiled template cache file.\n%s", htmlspecialchars( $fi ) ) );
		}

		return $fi;
	}

	/**
	 * Include template before parse and caching
	 *
	 * @param string $file Filename
	 * @param array $val a tree-like array
	 *
	 * @return void
	 */
	static public function include( $file, $val )
	{
		if ( self::isCached( $file ) === true )
		{
			self::runCache( $file, $val );
		}
		else
		{
			$code = self::parse( @file_get_contents( $file ) );
			$fi   = self::storeCache( $file, $code );
			if ( $fi !== false )
			{
				self::__SANDBOX__( $fi, $val );
			}
		}
	}

	/**
	 * Include template as string before parse and caching
	 *
	 * @param String $file Filename
	 * @param array $val a tree-like array
	 *
	 * @return string
	 */
	static public function buffer( $file, $val )
	{
		ob_start();
		self::include( $file, $val );
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * Quote PHP tag
	 *
	 * @param string Format string
	 * @param mixed Format args
	 *
	 * @return string
	 */
	static protected function __CODE__()
	{
		$args = func_get_args();
		$fmt  = array_shift( $args );

		return '<?php ' . vsprintf( $fmt, $args ) . ' ?>';
	}

	/**
	 * Quote RegEx
	 *
	 * @param string $re RegEx string
	 *
	 * @return string
	 */
	static protected function __RE__( $re )
	{
		return sprintf( '~%s~ui', $re );
	}

	/**
	 * Parse syntax and variables
	 *
	 * @param string $subject Template source code
	 * @param string $regex Regular expression
	 * @param string $index Index number of group by regular expression
	 * @param array $each_targets Replace last variable if provided each target list.
	 * @param mixed|callable $prepare Prepare callback
	 * @param callable $translation Translation code
	 */
	static protected function parser( &$subject, $regex, $index, $each_targets, $prepare, $translation )
	{
		$state   = new stdClass();
		$subject = preg_replace_callback( $regex,
			function ( $match ) use ( $state, $prepare, $translation, $index, $each_targets ) {
				$state->match = $match;
				$val_string   = $prepare ? $prepare( $state, $state->match[ $index ] ) : $state->match[ $index ];

				if ( preg_match( self::__RE__( '^(#|\*)(.+)$' ), $val_string, $m2 ) )
				{
					switch($m2[1])
					{
						case '#':
							$state->var = sprintf( '$__IDX__[\'%s\']', addslashes( $m2[2] ) );
							break;
						case '*':
							$state->var = sprintf( '$__VAL__[\'%s\']', addslashes( $m2[2] ) );
							break;
						default:
							$state->var = '';
							break;
					}

				}
				else
				{
					$ary_path = [];
					$val_path = [];
					if ( $index !== false )
					{
						$path_elements = explode( "/", $val_string );
						foreach ( $path_elements as $idx => $x )
						{
							$val_path[] = $x;
							if ( $idx != count( $path_elements ) - 1 && is_array( $each_targets ) && in_array( join( "/", $val_path ), $each_targets ) )
							{
								$ary_path[] = "['" . addslashes($x) . "'][\$__IDX__['" . addslashes( implode( "/", $val_path ) ) . "']]";
							}
							else
							{
								$ary_path[] = "['" . addslashes($x) . "']";
							}
						}
					}
					$state->var = sprintf( '$__ARY__%s', implode( '', $ary_path ) );
				}

				return $translation( $state );
			}, $subject );
	}

	/**
	 * Parse HTML strings.
	 *
	 * @param String $code HTML strings.
	 *
	 * @return String
	 */
	static protected function parse( $code )
	{
		#translate \r\n to \n
		$code = str_replace( "\r\n", "\n", $code );
		$code = str_replace( "\n\r", "\n", $code );

		/**
		 * <!--{each n}-->
		 * <!--{/each}-->
		 */
		$each_targets = [];
		$each_regex   = self::__RE__( '<!--{each (.+?)}-->' );

		preg_match_all( $each_regex, $code, $eachs, PREG_SET_ORDER );
		$each_targets = array_merge( $each_targets, array_map( function ( $v ) {
			return $v[1];
		}, $eachs ) );
		self::parser( $code, $each_regex, 1, $each_targets, false, function ( $state ) {
			return self::__CODE__(
				'if(isset(%1$s) && is_array(%1$s)) foreach(%1$s as $__IDX__[\'%2$s\'] => $__VAL__[\'%2$s\'] ){',
				$state->var, addslashes($state->match[1])
			);
		} );
		self::parser( $code, self::__RE__( '<!--{/each}-->' ), false, [], false, function () {
			return self::__CODE__( '}' );
		} );

		/**
		 * <!--{def n}-->
		 * <!--{ndef n}-->
		 * <!--{else}-->
		 * <!--{/def}-->
		 */
		self::parser( $code, self::__RE__( '<!--{(n?)def (.+?)}-->' ), 2, $each_targets, false,
			function ( $state ) {
				return self::__CODE__(
					'if(((isset(%1$s) && !is_array(%1$s) && !empty(%1$s)) or (isset(%1$s) && is_array(%1$s) && count(%1$s)>0)) xor %2$s){',
					$state->var, ( $state->match[1] !== '' ) ? 'true' : 'false'
				);
			} );
		self::parser( $code, self::__RE__( '<!--{else}-->' ), false, [], false, function () {
			return self::__CODE__( '} else {' );
		} );
		self::parser( $code, self::__RE__( '<!--{/def}-->' ), false, [], false, function () {
			return self::__CODE__( '}' );
		} );

		/**
		 * <!--{switch n}-->
		 * <!--{case x}--><!--{/case}-->
		 * <!--{default}--><!--{/default}-->
		 * <!--{/switch}-->
		 */
		self::parser( $code, self::__RE__( '<!--{switch (.+?)}-->' ), 1, $each_targets, false, function ( $state ) {
			return self::__CODE__( 'switch(%s){', $state->var );
		} );
		self::parser( $code, self::__RE__( '<!--{case (.+?)}-->' ), false, [], false, function ( $state ) {
			return self::__CODE__( 'case \'%s\':', addslashes( $state->match[1] ) );
		} );
		self::parser( $code, self::__RE__( '<!--{/case}-->' ), false, [], false, function () {
			return self::__CODE__( 'break;' );
		} );
		self::parser( $code, self::__RE__( '<!--{default}-->' ), false, [], false, function () {
			return self::__CODE__( 'default:' );
		} );
		self::parser( $code, self::__RE__( '<!--{/default}-->' ), false, [], false, function () {
			return self::__CODE__( 'break;' );
		} );
		self::parser( $code, self::__RE__( '<!--{/switch}-->' ), false, [], false, function () {
			return self::__CODE__( '}' );
		} );

		/**
		 * {val n}
		 */
		self::parser( $code, self::__RE__( '{([0-9a-z_]+) (.+?)}' ), 2, $each_targets, false, function ( $state ) {
			return self::__CODE__( 'if(isset(%1$s)) echo HTE::%2$s(%1$s);', $state->var, strtolower( $state->match[1] ) );
		} );

		/**
		 * {@n}
		 */
		self::parser( $code, self::__RE__( '{@(.+?)}' ), 1, $each_targets, false, function ( $state ) {
			return self::__CODE__( 'if(isset(%1$s)) echo %1$s;', $state->var );
		} );

		/**
		 * {%n}
		 */
		self::parser( $code, self::__RE__( '{%(.+?)}' ), 1, $each_targets,
			function ( $state, $val_string ) {
				$state->e = '';
				$s        = explode( '/', $val_string );
				if ( count( $s ) > 0 )
				{
					list( $k, $e ) = explode( ':', array_pop( $s ) );
					$state->e = '.\':' . addslashes($e) . '\'';

					return implode( '/', array_merge( $s, [ $k ] ) );
				}
				else
				{
					return $val_string;
				}
			},
			function ( $state ) {
				return self::__CODE__( 'echo $__ARY__[%s%s];', $state->var, $state->e );
			} );

		/**
		 * {n?text}
		 */
		self::parser( $code, self::__RE__( '{([\w\/:\-]+)\?(.+?)}' ), 1, $each_targets, false,
			function ( $state ) {
				return self::__CODE__(
					'if((isset(%1$s) && !is_array(%1$s) && %1$s!="") || (isset(%1$s) && is_array(%1$s) && count(%1$s)>0)) ' .
					'echo \'' . addslashes( $state->match[2] ) . '\';', $state->var );
			} );

		/**
		 * {$n}
		 */
		$immediate_references = [];
		self::parser( $code, self::__RE__( '{\$(.+?)}' ), 1, $each_targets, false, function ( $state ) use (&$immediate_references) {
			$immediate_var = sprintf('$__IRF%d__', count($immediate_references));
			$immediate_references[] = sprintf('%s=&%s;', $immediate_var, $state->var);
			return $immediate_var;
		} );
		if( count($immediate_references) > 0 ) $code = self::__CODE__(implode("", $immediate_references)) . $code;
		unset($immediate_references);

		/**
		 * end
		 */
		return $code;
	}
}
