<?php
/**
 * Contains class definitions and logic for Email & Telephone reminders 
 * @author Atul Verma <averma00@gmail.com>
 */

require_once "mymail.php";
require_once "utils.php";

abstract class Reminder {
	private $actionDate;	/* Date to act on - stored as string mm-dd-yy */
	protected $apptDT;		/* patient appt parsed as DateTime object */
	protected $numReminders, $mode; /* number of reminders made */
	
	public function __construct($thresh, $mode) {
		/* threshold is passed in # of days */
		$today = new DateTime;
		$actionDT = $today->add(new DateInterval('P'.$thresh.'D'));
		$this->actionDate = $actionDT->format('m-d-y');
		echo "Reminder: Action date is {$this->actionDate} \n";
		$this->numReminders = 0;
		$this->mode = $mode;
	}
	
	public function getNumReminders()  { return $this->numReminders; }
	
	public function getActionDate() { return $this->actionDate; }
	
	protected function doReminder($patientObj) {
		$this->apptDT = DateTime::createFromFormat('m/d/Y g:i:s a', $patientObj->getAppt());
		$apptDate = $this->apptDT->format('m-d-y');
		if (!strcmp($apptDate, $this->actionDate))
			return true;
		else
			return false;
	}
}

class EmailReminder extends Reminder {
	
	public function doReminder($patientObj) { 	
		
		if (!parent::doReminder($patientObj))
			return false;
		
		/* Check for valid email */
		$emailAddr = $patientObj->getEmail();

		if ($emailAddr == "") {
			$patientObj->setStatusDet("LocalError", "Missing Email Address");    /* updated object's status */
			return false;
		}
		if (!filter_var($emailAddr, FILTER_VALIDATE_EMAIL)) {
			$patientObj->setStatusDet("LocalError", "Invalid Email Address");    /* updated object's status */
			return false;
		}
		// Send email now
		$mail = new MyMail($emailAddr, DEFAULT_REM_EMAIL_SUBJ);
		// Create html content
		$htmlContent = getHTMLFromTemplate("templates/tplt_email_reminder.html", array($patientObj->getName(), $patientObj->getAppt()));
		
		$mail->setHTMLContentVar($htmlContent);
		$ret = $mail->send();
		
		if ($ret)
			$patientObj->setStatusDet("EmailSent", "");
		else
			$patientObj->setStatusDet("LocalError", "Could not send email");
		
		$this->numReminders++;
		
		return true;
	}	
}

class TelReminder extends Reminder {
	private $tvars = array();		/* Tropo variables */
	private $sessId;
	
	public function __construct($thresh, $mode) {
		parent::__construct($thresh, $mode);
		$this->sessId = time();		// create session id for this run
	}

	private function parseDateTime()  {
		$this->tvars['wday'] = $this->apptDT->format("l");
		$this->tvars['month'] = $this->apptDT->format("F");
		/* TODO append rd, th to days?? */
		$this->tvars['day'] = $this->apptDT->format("j");
		$this->tvars['hours'] = $this->apptDT->format("h");
		$this->tvars['min'] = $this->apptDT->format("i");
		// $this->tvars['ampm'] = trim($this->apptDT->format("a"), "m");  // get rid of m
		$this->tvars['ampm'] = $this->apptDT->format("a");
	}
	
	private function invokeTropo() {
		$tropoMode = "";
		$postResultURL = TROPO_RESULT_BASEURL . 'sendResult.php'; 
		if ($this->mode & MBITS_SKIP_TROPO_CALLS)
			$tropoMode = "skipcall";
		$vars = array('action' => 'create', 'token' => TROPO_TOKEN,
						'numberToDial' => $this->tvars['tel'],
						'day' => $this->tvars['wday'],
						'month' => $this->tvars['month'],
						'date' => $this->tvars['day'],
						'hours' => $this->tvars['hours'],
						'minutes' => $this->tvars['min'],
		        		'ampm' => $this->tvars['ampm'],
		        		'sessId' => $this->sessId,
		        		'mode' => $tropoMode,
						'cfm' => 'yes',
						'postURL' => $postResultURL);
		$query_string = http_build_query($vars);
		$url = 'http://api.tropo.com/1.0/sessions?' . $query_string;
		
		$result = file_get_contents($url);
		
		/* parse results from Tropo and record */
		$response = new SimpleXMLElement($result);
		$tmp = $this->tvars['tel'];
		//echo "Tropo Call Result [id:$response->id] [tel:$tmp] [Status:$response->success]\n";
		
		// If we did not skip call, then give a gap
		if (($this->mode & MBITS_SKIP_TROPO_CALLS) == 0)
			sleep(TROPO_INTERCALL_GAP*60);
	}
	
	public function doReminder($patientObj) {		
		/* Check for valid tel # */
		if (!$patientObj->isTelValid()) {
			$patientObj->setStatusDet("LocalError", "Missing Tel Number");    /* updated object's status */
			return false;
		}
		
		if (!parent::doReminder($patientObj))
			return false;
		
		/* Need to do telephone reminder */
		$this->tvars['tel'] = $patientObj->getTel();
		$this->parseDateTime();
		$patientObj->setStatusDet("Pending", "Awaiting Tropo");
		
		if ($this->mode & MBITS_SKIP_TROPO)
			$patientObj->setStatusDet("Confirm", "Tropo (Fake): Duration 60 secs");  // Fake status
		else
			$this->invokeTropo();		// Invoke Tropo API
		
		$this->numReminders++;
		
		return true;
	}
	
	public function updateTropoResults($report) {
		if ($this->mode & MBITS_SKIP_TROPO)		// we never went to Tropo
			return;
		
		echo "Reminder: Update tropo results for session $this->sessId\n";
		/* get all records for this session */
		$url = TROPO_RESULT_BASEURL . 'getResults.php';
		
		// The submitted form data, encoded as query-string-style
		// name-value pairs
		$body = "sessId=$this->sessId";
		
		$options = array('method' => 'POST',
		                 'header'  => 'Content-type: application/x-www-form-urlencoded',
		                 'content' => $body);
		
		// Create the stream context
		$context = stream_context_create(array('http' => $options));
		
		$tries = DB_QUERY_MAX_TRIES;
		while ($tries--)  {
			// Pass the context to file_get_contents()
			$result = file_get_contents($url, false, $context);
			
			/* Parse results and update status */
			$sx = simplexml_load_string($result);
			if (!$sx || $sx->QueryStatus->Code != 0)  {
				if (!$sx)
					echo "Reminder: Server Error\n";
				else
					echo "Reminder: Database Query Error [$sx->QueryStatus->Err]\n";
				sleep(DB_INTERQUERY_GAP*60);		// give it some time
				continue;
			}
			// if we are here that means DB query was successful
			$numResults = 0;
			foreach($sx->Results->Row as $row)  {
				// clean up what we got
				$tel = trim((string)$row->TelNum);
				$resp = trim((string)$row->Response);
				$dur = trim((string)$row->Duration);
				$report->modifyStatusDet($tel, $resp, "From Tropo: Duration " . $dur .  " secs");
				$numResults++;				
			}
			echo "Reminder: Try:" . (DB_QUERY_MAX_TRIES-$tries) . " Made " . $this->numReminders . " calls and got back $numResults results\n";
			// Did we get all the results??
			if ($numResults == $this->numReminders)  {
				$tries = 0;		// we are done
			}
			else  {
				sleep(DB_INTERQUERY_GAP*60);		// chill for sometime
			}
		}
	}
}

?>
