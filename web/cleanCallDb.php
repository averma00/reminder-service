<?php
/**
 * Call this file to delete all records older than 2 months
 * @author Atul Verma <averma00@gmail.com>
 */

require_once 'dbcommon.php';

// Connecting, selecting database
$db_conn = mysql_connect($db_host, $db_uname, $db_passwd)
	or Fatal('Could not connect: ' . mysql_error());

mysql_select_db($db_name) or Fatal('Could not select database');

// Performing SQL query
$query = "DELETE FROM $db_table WHERE Date < DATE_SUB(CURDATE(),INTERVAL 2 MONTH)";
$result = mysql_query($query);

printf("Records deleted: %d\n", mysql_affected_rows());

// Closing connection
mysql_close($db_conn);

?>
