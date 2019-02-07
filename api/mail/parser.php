<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

class WGMailParser
{
	protected $plain, $header, $body;
	protected $content_type, $content_subtype, $content_params, $content_encoding, $content_charset;
	protected $to, $from;
	protected $headers;

	public function __construct( $plain )
	{
		$this->plain = $plain;

		$this->to      = "";
		$this->from    = "";
		$this->subject = "";

		$this->headers          = array();
		$this->content_type     = "";
		$this->content_subtype  = "";
		$this->content_encoding = "iso-2022-jp";
		$this->content_charset  = "iso-2022-jp";
		$this->content_params   = array();
	}

	private function parseAddress( $addr )
	{
		if ( preg_match( '/<(.+?)>/', $addr, $m ) )
		{
			$addr = trim( $m[1] );
		}
		else
		{
			$addr = trim( $addr );
		}

		return $addr;
	}

	public function parseHeaders()
	{
		// ヘッダー解析
		foreach ( $this->headers as $hn => $hd )
		{
			list( $hk, $hv ) = $hd;
			switch ( $hk )
			{
				case "content-type":
					$p                     = explode( ";", $hv );
					$d                     = array_shift( $p );
					$s                     = explode( "/", $d );
					$this->content_type    = strtolower( trim( $s[0] ) );
					$this->content_subtype = strtolower( trim( $s[1] ) );
					$this->content_params  = array();
					foreach ( $p as $d )
					{
						list( $k, $v ) = explode( "=", trim( $d ), 2 );
						$this->content_params[ strtolower( $k ) ] = $v;
					}

					if( isset($this->content_params['charset']) )
					{
						$this->content_charset = $this->content_params['charset'];
					}
					break;

				case "content-transfer-encoding":
					$this->content_encoding = strtolower( $hv );
					break;

				case "to":
					$this->to = $hv;
					break;

				case "from":
					$this->from = $hv;
					break;

				case "subject":
					$this->subject = $hv;
			}
		}
	}

	public function analyze()
	{
		// \r\n を \n に変換
		$this->plain = strtr( $this->plain, array( "\r\n" => "\n", "\n\r" => "\n" ) );
		$this->plain = strtr( $this->plain, array( "\r" => "\n" ) );

		// Header 及び body の分離
		$ex          = explode( "\n\n", $this->plain, 2 );
		$header_temp = $ex[0];
		$body_temp   = $ex[1];

		$this->body = $body_temp;

		// マルチラインヘッダの正規化
		$header_array = explode( "\n", $header_temp );
		$t            = "";
		$p            = "";
		foreach ( $header_array as $h )
		{
			if ( ! preg_match( '/^\s+/', $h ) && $p != "" )
			{
				$this->headers[] = $p;
				$p               = $h;
			}
			else
			{
				$p .= " " . trim( $h );
			}
		}
		if ( $p != "" )
		{
			$this->headers[] = $p;
		}
		foreach ( $this->headers as $hk => $hv )
		{
			$p                    = explode( ":", $hv, 2 );
			$this->headers[ $hk ] = array( strtolower( $p[0] ), trim( $p[1] ) );
		}

		// ヘッダー解析
		$this->parseHeaders();


		// マルチパート解析
		if ( $this->content_type == "multipart" )
		{
			$bounding_array = explode( "--" . $boundary, $this->body );
			$result         = array();
			foreach ( $bounding_array as $id => $bounding_body )
			{
				if ( $id == 0 )
				{
					continue;
				}

				$bbody = new WGMailParser( $bounding_body );
				$r     = $bbody->analyze();
				foreach ( $r as $key => $body )
				{
					$result[] = $body;
				}
			}

			return $result;
		}

		//
		if ( $this->content_encoding == "base64" )
		{
			$body    = base64_decode( $this->body );
			$subject = iconv_mime_decode( $this->subject );
		}
		else
		{
			$body    = $this->body;
			$subject = $this->subject;
		}

		/*  Content-Type チェック */
		switch ( $this->content_type )
		{
			case "image/jpeg"  :
				$ext = "jpg";
				break;
			case "image/pjpeg" :
				$ext = "jpg";
				break;
			case "image/gif"   :
				$ext = "gif";
				break;
			case "image/png"   :
				$ext = "png";
				break;
			case "image/tiff"  :
				$ext = "tiff";
				break;
			case "text/plain"  :
				$ext  = "txt";
				$body = mb_convert_encoding( $body, "utf-8", $this->content_charset );
				break;
			case "text/html"   :
				$ext  = "html";
				$body = strip_tags( mb_convert_encoding( $body, "utf-8", $this->content_charset ) );
				break;
		}

		$this->body    = $body;
		$this->subject = $subject;

		$result[] = array(
			"Content-Type" => $this->content_type,
			"Body"         => $body,
			"Extension"    => $ext
		);

		return $result;
	}

	public function to()
	{
		return $this->parseAddress( $this->to );
	}

	public function from()
	{
		return $this->parseAddress( $this->from );
	}

	public function subject()
	{
		return trim( mb_convert_encoding( $this->subject, "utf-8" ) );
	}

	public function body()
	{
		return $this->body;
	}

	public function content_type()
	{
		return $this->content_type . "/" . $this->content_subtype;
	}
}
