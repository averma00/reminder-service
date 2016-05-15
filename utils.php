<?php
/**
 * This file contains common utility functions 
 * @author Atul Verma <averma00@gmail.com>
 */

/* Given a HTML template file and substitution array, substitute upto 10 
 * parameters marked by $$N
 */
function getHTMLFromTemplate($tFile, $sArray)  {
	
	$html = file_get_contents($tFile);
	for ($i=0; $i < count($sArray); $i++ )  {
		$val = $sArray[$i];
		
		$param = $i+1;		// parameters in template start from 1
		/* replace PARAM-1 with first array element and so on */
		$html = preg_replace("/PARAM-$param/", $val, $html);
	}
	
	return $html;
}

?>
