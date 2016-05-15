<?php
/**
 * Command-line options for the main application
 * @author Atul Verma <averma00@gmail.com>
 */
require_once "config.php";

class Options  {
	private static $allOpts  = array(
		"mode" 	=> array("Optional" => "::", "Help" => "Optional debugging mode", "Val" => MBITS_NONE),
		"sPath" => array("Optional" => "::", "Help" => "Optional Schedule File Path", "Val" => DEFAULT_PATH_INPUT),
		"eThresh" => array("Optional" => "::", "Help" => "Default Email Reminder Threshold", "Val" => DEFAULT_REM_EMAIL_THRESH_1),
		"tThresh" => array("Optional" => "::", "Help" => "Default Tel Reminder Threshold", "Val" => DEFAULT_REM_TEL_THRESH),
		"help" => array("Optional" => "::", "Help" => "Help", "Val" => ""),
        );
	
	public function __construct() {	
		// Extract longopts with colons
		$longopts = array();
		foreach (array_keys(Options::$allOpts) as $opt)  {
			array_push($longopts, $opt . Options::$allOpts[$opt]["Optional"]);
		}
		
		$options = getopt("", $longopts);
		foreach (array_keys($options) as $opt)  {
			if ($opt == "help")  {
				$this->Usage();
				die();
			}
			Options::$allOpts[$opt]["Val"] = $options[$opt];
		}
	}
	
	public function Usage()  {
		global $argv;
		
		printf("Usage: $argv[0] <Options> where <Options> are - \n");
		foreach (array_keys(Options::$allOpts) as $opt)  {
			echo "--$opt=". Options::$allOpts[$opt]['Help'] . "\n";			
		}
		echo "\nMode Definitions:\n";
		echo "MBITS_NONE = 0\n";
		echo "MBITS_SKIP_TROPO = 1\n";			
		echo "MBITS_SKIP_TROPO_CALLS = 2\n"; 	
		echo "MBITS_SKIP_SCHEDULE = 4\n";		
		echo "MBITS_SKIP_EMAILS = 8\n";			
		echo "MBITS_FILECHK_ONLY = 16\n";
	}
	
	public function get_Mode()  {
		return Options::$allOpts['mode']['Val'];
	}
	public function get_sPath()  {
		return Options::$allOpts['sPath']['Val'];
	}
	public function get_eThresh()  {
		return Options::$allOpts['eThresh']['Val'];
	}
	public function get_tThresh()  {
		return Options::$allOpts['tThresh']['Val'];
	}
}


?>
