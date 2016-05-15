<?php
/**
 * Called by ReminderService to get call results stored in the database
 * @author Atul Verma <averma00@gmail.com>
 */

require_once 'dbcommon.php';

$sessId = $_POST['sessId'];

// Start setting up XML so we have something for error case
$xml_output = "<?xml version=\"1.0\"?>\n";
$xml_output .= "<Root>\n";

// Connecting, selecting database
$db_conn = mysql_connect($db_host, $db_uname, $db_passwd)
	or Fatal('Could not connect: ' . mysql_error());

mysql_select_db($db_name) or Fatal('Could not select database');

// Performing SQL query
$query = "SELECT * FROM $db_table WHERE SessionId=$sessId";
$result = mysql_query($query) or Fatal('Query failed: ' . mysql_error());

// No more SQL Error handling
updateQueryStatus(0, "Ok");

$xml_output .= "<Results>\n";
while ($row = mysql_fetch_array($result)) {
	$xml_output .= "<Row>\n";
	
	$xml_output .= "<TelNum> " . $row['TelNum'] . " </TelNum>\n";
	$xml_output .= "<Duration> " . $row['Duration'] . " </Duration>\n";
	$xml_output .= "<Response> " . $row['Response'] . " </Response>\n";
	
	$xml_output .= "</Row>\n";
}
$xml_output .= "</Results>\n";

// Free resultset
mysql_free_result($result);

// Closing connection
mysql_close($db_conn);

spewXMLOutput();

function spewXMLOutput()  {
	global $xml_output;
	
	header("Content-type: text/xml");
	$xml_output .= "</Root>\n";
	
	echo $xml_output;
}

function updateQueryStatus($code, $errStr)  {
	global $xml_output;
	
	$xml_output .= "<QueryStatus>\n";
	$xml_output .= "<Code> $code </Code>\n";
	$xml_output .= "<Err> $errStr </Err>\n";
	$xml_output .= "</QueryStatus>\n";
}

function Fatal($errStr)  {
	global $db_conn;
	
	updateQueryStatus(-1, $errStr);
	if ($db_conn)
		mysql_close($db_conn);
	
	spewXMLOutput();
	
	die("My Fatal Error");
}

?>
