<?php
/**
 * Scheduling related functionality 
 * @author Atul Verma <averma00@gmail.com>
 */
require_once "config.php";

/* Class that determines for which date we are sending reminders today and if
 * it ok to schedule a run 
 */

class Schedule {
	// Fine tune these below
	const windowStart = WINDOW_START;
	const windowEnd = WINDOW_END;
	private $offDays = array("Sat", "Sun");
	private $holidays = array(
			"05-25-15" => "Memorial Day",
			"07-04-15" => "Independence Day",
			"09-07-15" => "Labor Day",
			"11-26-15" => "Thanksgiving-1",
			"11-27-15" => "Thanksgiving-2",
			"12-25-15" => "Christmas",
			"01-01-16" => "New Year's",
	);

    	private $options;	
	private $mode, $errorStr;
	private $unixtime, $wday, $today, $tomorrow;
	
	public function __construct($options)  {
		$this->options = $options;
		$this->mode = $options->get_Mode();
		$this->errorStr = "";
		$this->unixtime = time();
		$this->wday = date('D', $this->unixtime);
		$this->today = date('m-d-y', $this->unixtime);
		$this->tomorrow = date('m-d-y', $this->unixtime + (1*24*3600));
		echo "Schedule: Today is $this->wday $this->today \n";		
	}
	
	private function isHoliday($wday, $targetDate, &$verbose=NULL)  {
		// we don't work on weekends
		if (in_array($wday, $this->offDays))  {
			if ($verbose != NULL)
				$verbose = $wday;
			return true;
		}
		if (array_key_exists($targetDate, $this->holidays))  {
			if ($verbose != NULL)
				$verbose = $this->holidays[$targetDate];
			return true;
		}
		return false;
	}
	
	// Defensive check because the Practice is sensitive about making calls over weekend or late in the evenings
	public function canSchedule()  {
		if ($this->mode & MBITS_SKIP_SCHEDULE)
			return true;		// skip all checks for testing
			
		// we don't work on weekends or afterhours
		$verbose = "abc";
		if ($this->isHoliday($this->wday, $this->today, $verbose))  {
			echo "Schedule: Cannot schedule on $verbose \n";
			return false;
		}
		
		$hr = date('H', $this->unixtime);
		if (!in_array($hr+1, range(self::windowStart, self::windowEnd)))  {
			echo "Schedule: Hour $hr outside schedule window\n";
			return false;
		}
		return true;
	}
	
	// Given reminder threshold, deterime how many days to skip for real. For example, saye have confgured telephone
	// reminders to be sent 2 days ($thresh) in advance. If today is Mon then we will  make calls for patients scheduled for Wed
	// and in code below $realThresh will be the same as configured $thresh. However if today is Fri, then we will skip Sat, Sun 
	// from our accounting and in this case $realThresh will be 4. Same skipping goes if there are any intervening holidays. This
	// was the requirement imposed by the Practice but you can change it  
	// 
	public function workingThresh($thresh)  {
		// Check action date against holidays and skip 
		$saved = $thresh;		// for printing only
		$realThresh = 0;
		while ($thresh)  {
			$today = new DateTime;
			$actionDT = $today->add(new DateInterval('P'.($realThresh+1).'D'));
			$actionDate = $actionDT->format('m-d-y');
			$actionDay = $actionDT->format('D');
			if ($this->isHoliday($actionDay, $actionDate))
				$realThresh++;
			else  {
				$thresh--;
				$realThresh++;
			}
			unset($today);
		}
		echo "Schedule: Thresh: $saved  Real Threshold: $realThresh\n"; 
		return ($realThresh);
	}
	
	public function getParsingFile(&$fname)  {
		$fname = DEFAULT_PATH_INPUT . SCHEDULE_FNAME;
		if (!file_exists($fname))  {
			echo "Schedule: Parsing file [$fname] not available\n";
			return false;
		}
		
		echo "Schedule: Parsing file [$fname] found!!\n";
		if ($this->mode & MBITS_FILECHK_ONLY)
			return true;	// for testing - nothing else to do for now
		
		return true;		
	}
	
	public function sendNotification()  {
		$mail = new MyMail( EMAIL_TO_ADDR, 
                            "Notification - Appointment File Not Found",
                            EMAIL_CC_ADDR, EMAIL_BCC_ADDR );
		
		// Create html content
		$htmlContent = <<<EOD
				<h2> Error Detected: </h2>
				<p> Reminder service is not able to find appointment export file. Please
				    export the file and start service manually. </p>
EOD;
		$mail->setHTMLContentVar($htmlContent);
		$ret = $mail->send();		
		if ($ret)
			echo "Schedule: Sent reminder about missing Appointment export file\n";
		else
			echo "Schedule: Failed to send reminder about missing Appointment export file\n";
	}
}

?>
