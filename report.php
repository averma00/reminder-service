<?php
/**
 * Contains class definitions & logic for report generation and patient object 
 * @author Atul Verma <averma00@gmail.com>
 */

require_once "HTML/Table.php";

class PatientRec {
	private static $sErr = 1;
	private $name, $appt, $email, $tel, $telValid;
	/* Status value can be local or from Tropo
	 * Local Codes: LocalError, Pending, Skipped, EmailSent
	 * Tropo Codes: NoAnswer, CallFailure, UserError, None, Confirm, Cancel, AnsMac, Test
	 */
	private $status;
	private $detail;		// Free form field
	
	// Color code table entries based on status. This defined status to HTML color and count mapping
	private static $cmap = array(	'LocalError'=> array('Attr' => 'Orange', 'Cnt' => 0),
					'Pending' => array('Attr' => 'Gainsboro', 'Cnt' => 0),
					'Skipped' => array('Attr' => 'Gainsboro', 'Cnt' => 0),
					'EmailSent' => array('Attr' => 'YellowGreen', 'Cnt' => 0),
					'NoAnswer' => array('Attr' => 'OrangeRed', 'Cnt' => 0),
					'CallFailure' => array('Attr' => 'OrangeRed', 'Cnt' => 0),
					'UserError' => array('Attr' => 'OrangeRed', 'Cnt' => 0),
					'None' => array('Attr' => 'YellowGreen', 'Cnt' => 0),
					'Confirm' => array('Attr' => 'YellowGreen', 'Cnt' => 0),
					'Cancel' => array('Attr' => 'OrangeRed', 'Cnt' => 0),
					'AnsMac' => array('Attr' => 'Gold', 'Cnt' => 0),
					'Test' => array('Attr' => 'YellowGreen', 'Cnt' => 0)
				);
					
	
	public function __construct($name, $appt, $email, $tel) {
		/* appt is passed as 8/4/2011 9:40:00 AM */
		$this->telValid = true;
		$this->name = $name;
		$this->appt = $appt;
		$this->email = $email;
		if ($tel == "")
		{
		    $tel = "Missing_" . self::$sErr++;	/* Fake a unique tel number since it is a key for report records */
		    $this->telValid = false;
		}
		$this->tel = $tel;	
		$this->status = "Init";		// some default	
	}
	
	public function setStatusDet($s, $d) {
		if ($this->status != "Init")
			self::$cmap[$this->status]['Cnt']--;		// we must be updating status
		$this->status = $s;
		$this->detail = $d;
		self::$cmap[$s]['Cnt']++;
	}
	
	public function getName() { return $this->name; }
	public function getAppt() { return $this->appt; }
	public function getEmail() { return $this->email;	}
	public function getTel()  { return $this->tel; }
	public function isTelValid()  {	return $this->telValid; }
	public function getStatus() { return $this->status; }
	public function getDetail() { return $this->detail; }
	public function getColor() { return self::$cmap[$this->status]['Attr']; }
	public function isStatusSet() { return ($this->status != "Init"); }

    // Summarize verious status counters
	public static function getCounterSummary()  {
		$tableStyle = array("border"=>1, "width"=>850, "cellpadding"=>1, "cellspacing"=>2);
		$table = new HTML_Table($tableStyle);
		$table->setAutoGrow(true);
		$table->setAutoFill('');
		 
		$cnt = $row = 0;
		$rows = array();
		foreach (self::$cmap as $status => $ref)  {
			$row = $cnt/5;                    // max 5 entries per row
			$c = $ref['Attr'];
			if ($ref['Cnt'])  {
				$rows[$row][] = "<span style=\"background-color:$c\"> $status" . ": " . $ref['Cnt'] . "</span>";
				$cnt++;
			}
		}
		foreach ($rows as $row=>$content) {
			$table->addRow($content);         // $content is an array
		}
		return $table->toHTML();
	}

}

class Report {
	private $patientRecs = array();	
	private $misc;
	
	public function __construct() {
		$this->misc = null;
	}
	
	public function insertRecord($patientRecObj) {
		$this->patientRecs[$patientRecObj->getTel()] = $patientRecObj;
	}
		
	public function modifyStatusDet($tel, $s, $d) {
		$tel = trim($tel);
		$pobj = $this->patientRecs[$tel];
		$pobj->setStatusDet($s, $d);
	}
	
	public function __toString() {
		return print_r($this->patientRecs, true);
	}
	
	// Add miscellaneous info to report
	public function addMisc($str) {
		$this->misc .= ($str . "<br/>");
	}
	
	private function getDetailedReport() {
		$tableStyle = array("border"=>1, "width"=>850, "cellpadding"=>1, "cellspacing"=>2);
		$table = new HTML_Table($tableStyle);
		
		$table->setAutoGrow(true);
		//$table->setAutoFill('n/a');
		
		$colHeaders = array("Name", "Tel Num", "Email Address", "Appointment", "Status", "Detail");
		$headerStyle = "bgcolor=gold nowrap";
		$table->addRow($colHeaders, $headerStyle, "TH");
		
		foreach ($this->patientRecs as $tel=>$pobj) {			
			$table->addRow(array($pobj->getName(), $tel, $pobj->getEmail(), $pobj->getAppt(), $pobj->getStatus(), $pobj->getDetail()), 
					array("bgcolor" => $pobj->getColor()) );
		}
		return $table->toHTML();
	}
	
	public function genReportFile($fname, $numTotal, $numT, $numE) {
		$fp = fopen($fname,"w");
		if (!$fp) {
			echo "Report: Could not create output file $fname \n";
			return false;
		}
		// Write Summary Stats
		$summary = <<<EOD
		<h3> Today's Reminder Summary </h3>
		<p><b>
		Records Scanned: $numTotal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Calls Made: $numT &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Emails Sent: $numE <br />
		</b></p>
EOD;
		fputs($fp, $summary);
		fputs($fp, $this->misc);
		fputs($fp, $this->getDetailedReport());
		fputs($fp, "<br /><h3> Counts Summary </h3>");
		fputs($fp,PatientRec::getCounterSummary());
		fputs($fp, "<br />");
		fclose($fp);
		
		$this->delStaleFiles();
		
		return true;
	}
	
	private function delStaleFiles() {
		$unixtime = time();
		for ($i=1; $i < NUM_OLD_FILES; $i++)  {
			$olddate = date('m-d-y', $unixtime - $i*24*3600);
			$stale = PATH_OUTPUT . "report_" . $olddate . ".html";
			echo "Report: Trying to remove old file: [$stale]\n";
			if (file_exists($stale))  {
				unlink($stale);
				break;
			}
		}
	}
}

?>
