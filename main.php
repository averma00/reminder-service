<?php
/**
 * Main PHP file that drives other funtions 
 * @author Atul Verma <averma00@gmail.com>
 */

require_once "config.php";
require_once "schedule.php";
require_once "reminder.php";
require_once "report.php";
require_once "options.php";

echo "Main: Script Started at " . strftime("%c") . "\n";

// Parse input options first
$gOptions = new Options();
$gMode = $gOptions->get_Mode();

// If we are allowed to send reminders, then look for schedule file. If file missing then send
// notification so office manager can upload the schedule file and restart application
$scheduler = new Schedule($gOptions);
if (!$scheduler->canSchedule())
    die("Main: Not allowed to schedule\n");	

if  (!$scheduler->getParsingFile($fname)) {
    $scheduler->sendNotification();	
    die("Main: Gave up on appointment file\n");
}

if ($gMode & MBITS_FILECHK_ONLY)
	die("Main: Done file check\n");		// no need to proceed further - used when debugging file path

// we are good to go - $fname contains the schedule file we need to parse - it is supplied in XML format. Change code below if
// it is in some other format, such as CSV
$sx = simplexml_load_file($fname);
if (!$sx)
	die("Main: Failed to open file [$fname]");

// Get reminder thresholds - how far in advance we need to send reminders
$e1Th = $scheduler->workingThresh($gOptions->get_eThresh());
$e2Th = $scheduler->workingThresh(DEFAULT_REM_EMAIL_THRESH_2);
$telTh = $scheduler->workingThresh($gOptions->get_tThresh());

// Instantiate reminder objects
$eReminder1 = new EmailReminder($e1Th, $gMode);
$eReminder2 = new EmailReminder($e2Th, $gMode);
$tReminder = new TelReminder($telTh, $gMode);

$gReport = new Report();	// will be used for building up summary report - that will be emailed to Admin
$str = sprintf("Email Thresholds [%d days and %d days]. Tel Threshold [%d days]", $e1Th, $e2Th, $telTh);
$gReport->addMisc($str);

if (!$eReminder1 || !$eReminder2 || !$tReminder || !$gReport)
	die("Main: Failed to create objects [$eReminder1, $eReminder2, $tReminder, $gReport]");

$numSkipped = $numTotal = 0; 
foreach($sx->Appointment as $appt)  {
    $name = trim($appt->PatientName);
    $patientId = $appt->PatientID;
    if ($name == "")          /* This is nornal based on generated XML */
    	continue;

    $numTotal++;
    $email = $appt->PatientEmail;
    
    /* clean up the tel number */
    $tel = preg_replace('/[^0-9]/','', $appt->PatientPhone);
    $apptDateTime = $appt->AppointDate;
    
    $pobj = new PatientRec($name, $apptDateTime, $appt->PatientEmail, $tel);
    /* Insert record */
    $gReport->insertRecord($pobj);
    
    // for this patient, do one of the reminders or skip
    if ($eReminder1->doReminder($pobj))  {
    } 
    else if ($eReminder2->doReminder($pobj))  {
    }
    else if ($tReminder->doReminder($pobj)) {
    }
    else
    {
        // Do not override any oher error status if it has been set already by other logic
        if (!$pobj->isStatusSet() )
            $pobj->setStatusDet("Skipped", "Future Date");
        $numSkipped++;
	}
}

/* For telephone reminders, we need to get status remotely */
$tReminder->updateTropoResults($gReport);

$eNum = $eReminder1->getNumReminders() + $eReminder2->getNumReminders();
$gReport->genReportFile($g_outFname, $numTotal, $tReminder->getNumReminders(), $eNum);

echo "Main: Finished #Scanned = $numTotal #Tel Reminders = " . $tReminder->getNumReminders() . " #Email Reminders = " . $eNum . " #Num Skipped = $numSkipped\n";

echo "Main: Script Ended at " . strftime("%c") . "\n";

?>
