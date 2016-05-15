<?php
/**
 * Helper class for sending out mails 
 * @author Atul Verma <averma00@gmail.com>
 */
require_once "config.php";
require_once "Mail.php";
require_once "Mail/mime.php";

class MyMail {
	const from = "Example Company Auto-Mailer <no-reply@example.com>";
	
	private $to, $cc, $subject, $htmlContent;
	
	public function __construct($to, $subject, $cc="", $bcc="") {
		$this->to = $to;
		$this->subject = $subject;
		$this->cc = $cc;
		$this->bcc = $bcc;
	}
	
	public function setHTMLContentFile($htmlFile)  {
		$this->htmlContent = file_get_contents($htmlFile);
	}
	
	public function setHTMLContentVar($htmlVar)  {
		$this->htmlContent = $htmlVar;
	}
	
	public function send($htmlImageFile = "", $attachFile = "")  {
	
		if ($GLOBALS['gMode'] & MBITS_SKIP_EMAILS)  {
			echo "MyMail: Email sending is Skipped\n";
			return true;
		}
		
		$ret = true;
		
		$message = new Mail_mime();
		$message->setHTMLBody($this->htmlContent);
		if ($htmlImageFile)
			$message->addHTMLImage($htmlImageFile, 'image/gif');
		if ($attachFile)
			$message->addAttachment($attachFile);
		$body = $message->get();
		
		$extraheaders = array (	'From' => self::from,
								'Reply-To' => self::from,
		        				'To' => $this->to,
		        				'Subject' => $this->subject );
		if ($this->cc) {
			$extraheaders['Cc'] = $this->cc;
			$recipients = $this->to .", " . $this->cc;
		}
		else
			$recipients = $this->to;
		
		if ($this->bcc)
			$recipients .= ", " . $this->bcc;
		$headers = $message->headers($extraheaders);
		
		$smtp = Mail::factory('smtp',
					array (	'host' => SMTP_HOST,
		            		'auth' => true,
		            		'username' => SMTP_USERNAME,
		            		'password' => SMTP_PASSWORD,
		            		'port' => SMTP_PORT) );
		
		$mail = $smtp->send($recipients, $headers, $body);
		
		if (PEAR::isError($mail)) {
			echo "MyMail: Error sending mail to [$this->to]\n";
			echo ($mail->getMessage());
            echo "\n";
			$ret = false;
		}
		return $ret;				
	}
}

?>
