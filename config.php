<?php
/**
 * Contains global definitions that need to be customized
 * @author Atul Verma <averma00@gmail.com>
 */

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_STRICT);

date_default_timezone_set('America/New_York');

// Mode bits for testing
const MBITS_NONE = 0x0;					// Production mode
const MBITS_SKIP_TROPO = 0x1;			// Skip tropo completely
const MBITS_SKIP_TROPO_CALLS = 0x2; 	// Call tropo but skip calls
const MBITS_SKIP_SCHEDULE = 0x4;		// Skip time/day schduling checks
const MBITS_SKIP_EMAILS = 0x8;			// Skip sending all emails
const MBITS_FILECHK_ONLY = 0x10;        // Only check for existence of files

// Scheduling start and end windows - no telephone calls outside these times
const WINDOW_START = 10;		// 10 AM
const WINDOW_END = 20;		// 8 PM

// File paths
const DEFAULT_PATH_INPUT = '/tmp/';
const PATH_OUTPUT = "/tmp/";		// reminder service output
const SCHEDULE_FNAME = "ScheduleExport.xml";

// Tropo constants
const TROPO_INTERCALL_GAP = 1;		// 1 minute 
const TROPO_TOKEN = "GET YOUR API TOKEN FROM tropo.com"; //CHANGE ME
const TROPO_RESULT_BASEURL = "http://www.example.com/tropo/";	// CHANGE ME

// Database query
const DB_QUERY_MAX_TRIES = 3;
const DB_INTERQUERY_GAP = 2;		// 2 minutes

// Reminder thresholds
const DEFAULT_REM_EMAIL_THRESH_1 = 5; 	/* # of days in advance for second email reminder */
const DEFAULT_REM_EMAIL_THRESH_2 = 20;	/* # of days in advance for first email reminder - disabled */
const DEFAULT_REM_TEL_THRESH =3;	/* # of days in advance for telephone reminder */

// Email subject for reminders
const DEFAULT_REM_EMAIL_SUBJ = "EXAMPLE CLINIC is looking forward to seeing you";

// Email notification addresses - belong to admin/manager
const EMAIL_TO_ADDR = "admin@example.com"; //CHANGE ME	
const EMAIL_CC_ADDR = ""; 
const EMAIL_BCC_ADDR = "";

// SMTP settings for sending out mail using google server
const SMTP_HOST = "ssl://smtp.gmail.com";	// CHANGE ME
const SMTP_PORT = "465";	// CHANGE ME IF NEEDED
const SMTP_USERNAME =  "youraccount@gmail.com";	// CHANGE ME
const SMTP_PASSWORD =  "yourpassword";	// CHANGE ME

// Number of stale files we will try to delete
const NUM_OLD_FILES = 8;

// Setup some globals
$today = date('m-d-y', time());
$g_outFname = PATH_OUTPUT . "report_" . $today . ".html";  // keeping it global because resultmailer needs it 

?>
