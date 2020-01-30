<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once(WGCONF_PEAR . "/Mail.php");

class WGMail
{
	protected $mail_from;
	protected $mail_to;
	protected $mail_reply_to;
	protected $mail_error_to;
	protected $mail_subject;
	protected $mail_body;
	protected $to_address;
	protected $template;

	function __construct()
	{
		$this->mail_from = WGCONF_EMAIL;
		$this->mail_to = "";
		$this->mail_reply_to = WGCONF_EMAIL;
		$this->mail_error_to = WGCONF_EMAIL;
		$this->mail_subject = "";
		$this->mail_body = array();
		$this->template = WGCONF_DIR_TPL . "/mail.txt";
	}

	public function from($from, $nickname = "")
	{
		$addr = $from;
		if ($nickname != "") {
			$addr = sprintf('"%s"<%s>', mb_encode_mimeheader($nickname,"iso-2022-jp"), $addr);
		}
		$this->mail_from = $addr;
		return $this;
	}

	public function to($to, $nickname = "")
	{
		$addr = $to;
		if ($to[0] == '.' || strpos($to, "..") || strpos($to, ".@")) {
			// 特殊メールアドレス： "."から始まる、".."が含まれる、"@"の直前に"."がある
			list($name, $domain) = explode("@", $to);
			// "@"の前までを "" でくくる
			$addr = '"' . $name . '"@' . $domain;
		}
		if ($nickname != "") {
			$addr = mb_encode_mimeheader($nickname,"iso-2022-jp") . sprintf("<%s>", $addr);
		}
		$this->mail_to = $addr;
		$this->to_address = $to;
		return $this;
	}

	public function reply_to($reply)
	{
		$this->mail_reply_to = $reply;
		return $this;
	}

	public function error_to($error_to)
	{
		$this->mail_error_to = $error_to;
		return $this;
	}

	public function subject($sub)
	{
		$this->mail_subject = mb_encode_mimeheader("{$sub}","iso-2022-jp");
		return $this;
	}

	public function body($body=array())
	{
		$this->mail_body = $body;
		return $this;
	}

	public function setTemplate($template)
	{
		$this->template = $template;
		return $this;
	}

	private function makeHeader()
	{
		$headers = array();
		$headers["From"]      = $this->mail_from;

		if(!WGCONF_SMTP_TEST)
			$headers["To"]    = $this->mail_to;
		else
			$headers["To"]    = WGCONF_SMTP_TEST_RCPTTO;

		$headers["Subject"]   = $this->mail_subject;
		$headers["Errors-To"] = $this->mail_error_to;
		$headers["Reply-To"]  = $this->mail_reply_to;
		$headers["Date"]      = date("r"); // RFC822
		$headers["Content-Type"] = 'text/plain; charset="iso-2022-jp"';
		$headers["Content-Transfer-Encoding"] = '7bit';
		$headers["MIME-Version"] = '1.0';
		$headers["X-Mailer"]  = "waggo WGMail API ver.1.0";

		return $headers;
	}

	private function makeBody()
	{
		if (!empty($this->template))
		{
			$newbody = HtmlTemplate::buffer($this->template, $this->mail_body);
		}
		else
		{
			$newbody = $this->mail_body;
		}

		$newbody = preg_replace('/\r\n/',"\n",$newbody);
		$newbody = preg_replace('/\n/',"\r\n",$newbody);

		if(WGCONF_SMTP_TEST)
		{
			$newbody = "(DEBUG) {$this->to_address} 宛メールです。\r\n" . $newbody;
		}

		$newbody = mb_convert_encoding($newbody,"iso-2022-jp");

		return $newbody;
	}

	public function post()
	{
		$headers = $this->makeHeader();
		wg_log_dump(WGLOG_INFO, $headers);

		$newbody = $this->makeBody();

		$options = array();
		$options["host"]      = WGCONF_SMTP_HOST;
		$options["port"]      = WGCONF_SMTP_PORT;
		$options["auth"]      = WGCONF_SMTP_AUTH;
		$options["username"]  = WGCONF_SMTP_AUTH_USERNAME;
		$options["password"]  = WGCONF_SMTP_AUTH_PASSWORD;
		$options["localhost"] = WGCONF_SMTP_LOCALHOST;
		$mail_object =& Mail::factory("SMTP",$options);

		if(!WGCONF_SMTP_TEST)
			$result = $mail_object->send($this->mail_to, $headers, $newbody);
		else
			$result = $mail_object->send(WGCONF_SMTP_TEST_RCPTTO, $headers, $newbody);

		if (PEAR::isError($result)) {
			wg_log($result->getMessage());
		}
		return !PEAR::isError($result);
	}


	/**
	 * インターネット電子メールを送信する。
	 * @param string $to 宛先メールアドレス
	 * @param string $subject メールサブジェクト
	 * @param string $body 本文
	 * @param string $from Fromアドレス
	 * @param string $err エラーメールレポートアドレス
	 * @return boolean 成功した場合trueを、失敗した場合falseを返す。
	 */
	static function wg_mail($to,$subject,$body,$from=WGCONF_EMAIL,$err=WGCONF_ERRMAIL)
	{
		$data = array();

		$options = array();
		$options["host"]      = WGCONF_SMTP_HOST;
		$options["port"]      = WGCONF_SMTP_PORT;
		$options["auth"]      = WGCONF_SMTP_AUTH;
		$options["username"]  = WGCONF_SMTP_AUTH_USERNAME;
		$options["password"]  = WGCONF_SMTP_AUTH_PASSWORD;
		$options["localhost"] = WGCONF_SMTP_LOCALHOST;

		$newsubject = mb_encode_mimeheader("{$subject}","iso-2022-jp");

		$headers = array();
		$headers["From"]      = $from;

		if(!WGCONF_SMTP_TEST)
			$headers["To"]    = $to;
		else
			$headers["To"]    = WGCONF_SMTP_TEST_RCPTTO;

		$headers["Subject"]   = $newsubject;
		$headers["Errors-To"] = $err;
		$headers["Reply-To"]  = $from;
		$headers["Date"]      = date("r"); // RFC822
		$headers["Content-Type"] = 'text/plain; charset="iso-2022-jp"';
		$headers["Content-Transfer-Encoding"] = '7bit';
		$headers["MIME-Version"] = '1.0';
		$headers["X-Mailer"]  = "waggo postmail API ver.5.0";

		wg_log_dump(WGLOG_INFO, $headers);

		if(WGCONF_SMTP_TEST)
			$body = "(DEBUG) {$to} 宛メールです。\n" . $body;

		$data["body"] = $body;

		$newbody = HtmlTemplate::buffer( WGCONF_DIR_TPL . "/mail.txt", $data);

		$newbody = preg_replace('/\r\n/',"\n",$newbody);
		$newbody = preg_replace('/\n/',"\r\n",$newbody);
		$newbody = mb_convert_encoding($newbody,"iso-2022-jp");

		$mail_object =& Mail::factory("SMTP",$options);

		if(!WGCONF_SMTP_TEST)
			$result = $mail_object->send($to, $headers, $newbody);
		else
			$result = $mail_object->send(WGCONF_SMTP_TEST_RCPTTO, $headers, $newbody);

		return !PEAR::isError($result);
	}
}
