<?php
/**
 * Sends out summary report - invoked from batch file. Reason to
 * to do so: we don't want to send from Reminder Service because in 
 * in case something crashed, we can atleast inform the admin  
 * @author Atul Verma <averma00@gmail.com>
 */
require_once "config.php";
require_once "mymail.php";

$gMode = MBITS_NONE;
$subject = "Reminder Service Daily Report";

// if HTML file does not exist it means we gave up because of error or missing file
if (!file_exists($g_outFname))  {
	$mail = new MyMail(EMAIL_TO_ADDR, $subject);
	$mail->setHTMLContentVar("<h2> Reminder Servive - no report generated </h2> <p> Pl. see error log. </p>");
} else  {
	$mail = new MyMail(EMAIL_TO_ADDR, $subject, EMAIL_CC_ADDR, EMAIL_BCC_ADDR);
	$mail->setHTMLContentFile($g_outFname);
}

// additional argument is for log file 
$mail->send("", ($argc >= 2 ? $argv[1] : ""));

?>
