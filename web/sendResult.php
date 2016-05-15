<?php
/**
 * Called by tropo script to store results in database
 * @author Atul Verma <averma00@gmail.com>
 */

require_once 'dbcommon.php';

// Connecting, selecting database
$db_conn = mysql_connect($db_host, $db_uname, $db_passwd)
	or die('Could not connect: ' . mysql_error());

mysql_select_db($db_name) or die('Could not select database');

$sql="INSERT INTO $db_table (TelNum, SessionId, Duration, Response)
VALUES
('$_POST[tel]','$_POST[sessId]','$_POST[duration]', '$_POST[resp]')";

// Insert Data
mysql_query($sql);

// Closing connection
mysql_close($db_conn);

?>
