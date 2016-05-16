<?php
/**
 * Tropo Script file to make outsgoing calls and record results 
 * @author Atul Verma <averma00@gmail.com>
 */

$gResp = "UserError";   // Can be NoAnswer, CallFailure, UserError, None, Confirm, Cancel, AnsMac, Test
$gVoice = "Vanessa";

// Call progress timeouts
$gCpaMaxSilence = "900";
$gCpaMaxTime = "6000";
$gCpaRunTime = "30000";

$gSess = $sessId;     // session id for set of calls - for a given run
// $cfm = 'no';     // this is passed in URL params

$greeting = "Hello,  this is an automatic reminder service from, Example Company. We are calling to remind you, that you have, an appointment on ,";
$date_string = $day. " ,". $month . " ," .$date. " ," . "at" . ",";
$time_string = $hours . ":" . $minutes . ",  " . $ampm . "," . ".";
$cancel_extra = "If you are not able to keep this appointment, Please call the office at, 111-222-3333, at least 24 hours ahead  to cancel or reschedule your visit." ;
$repeat_string = "Your appointment is on ,";

$start_time = time();
if ($mode != "skipcall")  {     // skipcall is one of the testing modes 
    call('tel:+1'.$numberToDial, array( "callerId" => "3013015555",
   									"onTimeout" => "timeoutFCN",
   									"onCallFailure" => "callFailureFCN",
                                    "timeout" => 55
									));
    $start_time = time();
	if ($gResp == "UserError")  {    // Default we started with	

		// Need to make greeting as long as possible
		$greeting .= $date_string . $time_string . $cancel_extra;
		if ( cpaDetectAM($greeting) )
			$cfm = 'AnsMac';

		_log("**** Just called ".$numberToDial." with message: [".$greeting."]****\n");

		if ($cfm == 'no' || $cfm == 'AnsMac')  {    // cfm no means no confirmation needed - set in url params
    		mySay("Thank you and goodbye!");
    		($cfm == 'no' ? $gResp = 'None' : $gResp = 'AnsMac');
		}
		else 
    		getConfirmation($repeat_string, $date_string, $time_string);
	}
}
else 
	$gResp = "Test";    // we are just testing the flow - so set response code accordingly

$duration = time() - $start_time;
_log("**** Duration [$duration] seconds. Response code: [$gResp] ****\n");

// Post call results to specified server 
postResultsCurl($numberToDial, $duration, urldecode($postURL));

function timeoutFCN($event) {
	global $gResp;
	
	$gResp = "NoAnswer";
}

function callFailureFCN($event) {
	global $gResp;
	
	$gResp = "CallFailure";
}

function getConfirmation($repeat_string, $date_string, $time_string)
{
    global $gResp, $gVoice;
    $firstTime = true;
    $mainQ = "Please press 1, to confirm your appointment, or press 2, to cancel this appointment.";

    $tries = 2;
    while ( $tries > 0)  {
         
        if ($firstTime)  {
             $Q = $mainQ . " To repeat the appointment time, please press 3";
             $choices = "1,2,3";
        }  
        else  {
              $Q = $mainQ;
              $choices = "1,2";
        }
        $result = ask($Q,
                array(
                     "choices" => $choices,
                     "timeout" => 10.0,
                      "mode" => "dtmf",
                      "voice" => $gVoice
                 ));

_log("**** Main ask [$tries] finished with result code: [$result->value] ****\n");  
  
        if ( $result->value == "1" ) {
            mySay("Thank you. We look forward to seeing you");
            $tries = 0;
            $gResp = "Confirm";     /* Final response */
        }
        elseif ( $result->value == "2") {
            $tries = ( reconfirm() ? 0: $tries-1);
        }
        else {
            if ( $result->value == "3" && $firstTime)
                mySay($repeat_string.",".$date_string, $time_string);
            else
                $tries--;
        }
        $firstTime = false;
    }
}

function reconfirm()
{
    global $gResp, $gVoice;

    $ret = false;
    $result = ask("Are you sure you want to cancel your appointment? Press 1 to confirm. Press 2 to go back",
   array(
        "choices" => "1,2",
        "attempts" => "1",
        "mode" => "dtmf",
        "voice" => $gVoice
    ));

    if ( $result->value == "1" ) {
        mySay("Thank you. Your appointment has been cancelled");

        $ret = true;
        $gResp = "Cancel";
    }
    return $ret;
}

// Wrapper function so we can set a consistent voice
function mySay($str, $timeStr)   {
    global $gVoice;
    say($str . $timeStr, array("voice" => $gVoice));
}

// Crude function to determine if call was answered by an Answering Machine
function cpaDetectAM($initialCallerMessage)  {
	global $gCpaMaxSilence, $gCpaMaxTime, $gCpaRunTime;
	//$sip_normal = "sip:9996151102@sip-noproxy.voxeo.net";
	$sip_normal = "sip:9990051129@sbc-external.orl.voxeo.net";
	$sip_trace = "sip:9996135283@sip-noproxy.voxeo.net";
	
	$cpa_headers = array(
			"x-cpa-max-silence" => $gCpaMaxSilence,
			"x-cpa-max-time" => $gCpaMaxTime,
			"x-cpa-runtime" => $gCpaRunTime,
			"x-initial-message" => $initialCallerMessage,
			"x-initial-message-type" => "tts"
	);

	_log("**** Transfer to CPA started [$gCpaMaxSilence, $gCpaMaxTime, $gCpaRunTime] ****");
	$cpa_event = transfer($sip_normal, array("headers" => $cpa_headers ));
	
	// Back from CCXML.
	_log("**** Transfer to CPA script complete ****");
	
	// Get CPA result.
	$result = $cpa_event->value->getHeader('x-cparesult');
	//$headers = $cpa_event->value->result;
	_log("**** CPA result returned from CCXML: " . $result);
	
	if ($result == "unknown" | $result == "human") {
		return false;
	}
	return true;
}

function postResultsCurl($tel, $duration, $url)  {
	global $gResp, $gSess;
	
    $fields = array(
            'sessId'=>$gSess,
            'tel'=>urlencode($tel),
            'resp'=>urlencode($gResp),
            'duration'=>urlencode($duration)
        );

    //url-ify the data for the POST
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    $fields_string = rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);

    _log("**** Post URL [$url] Field String [$fields_string] Post result [$result] ****\n");
}

?>



