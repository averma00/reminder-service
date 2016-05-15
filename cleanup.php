<?php
/**
 * Cleanup tropo result records periodically so database does not grow indefinitely 
 * @author Atul Verma <averma00@gmail.com>
 */
require_once "config.php";

// Cleanup Call DB entries
function Invoke_CallDBCleanup()  {
	echo "Cleaning up Call DB Records\n";
	$url = TROPO_RESULT_BASEURL . 'cleanCallDb.php';
	$result = file_get_contents($url);
	echo $result . "\n";
}

Invoke_CallDBCleanup();

?>

