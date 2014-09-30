<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-09-27
// Unit: PublicOpinion
// File: PublicOpinionAgent.php
//
// Description: Requests tasking from a central C&C server, executes, and reports
//
////////////

include 'Resources/CLCommon/CLCS_Common.php';

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: courthouseWebConnect
// Inputs: String $chAddress    (The address of the Courthouse)
//         String $action       (The action -- keyword -- being set by the node)
//         String $nid          (The unique node identifier)
//         Bool $hasGPU         (True if the node supports GPU operations)
//         String $ipa          (The node's IP address)
//         String $jid          (The Job ID)
//         String $results      (Any results to be pushed to the Courthouse)
//         String $type         (The type of connection to make, e.g. GET or PUSH)
// Returns: A string containing the full server response
// Description: Performs a formatted webserver-based connection to a Courthouse
function courthouseWebConnect($chAddress, $action, $nid, $hasGPU, $ipa, $jid, $results, $type) {
	$url = "http://" . $chAddress . "/index.php";
        $data = array (
                'CLCSA' => $action,
                'NodeID' => $nid,
                'GPU' => $hasGPU,
                'IPA' => $ipa,
                'JOBID' => $jid,
                'RESULTS' => $results );
        $options = array(
                'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => $type,
                        'content' => http_build_query($data),
                ),
        );
        $context  = stream_context_create($options);
        $svrResponse = file_get_contents($url, false, $context);
	return $svrResponse;
}
//
// END courthouseWebConnect
////////////////////////////////////////////////////////////////////////////////////////////


    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getCommand
// Inputs: CLCSConfiguration $poConfig  (A CLCSConfiguraiton object used to connect to the database)
// Returns: A string containing the command to execute
// Description: Grabs the next command to run from the Courthouse for this node
function getCommand($poConfig) {
	$dbCon = connectTo($poConfig, "PublicOpinion", FALSE, TRUE);
	$dbUser = $poConfig->getSetting("PublicOpinion", "DBUser");
	$dbTable = "Courthouse." . $dbUser . "_TASKS";
	$sqlGetCommand = "SELECT `command`,`target`,`words`,`job_id` FROM " . $dbTable . " WHERE `complete`=FALSE LIMIT 1;";
	$resultCommand = $dbCon->query($sqlGetCommand);
	return $resultCommand->fetch_row();
}
//
// END getCommand
////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: readPotFile
// Inputs: String $nid      (The unique node identifer)
// Returns: String containing the data from the specified result file
// Description: Reads in the contents of the pot file, parses it into a data array, and removes the original file
function readPotFile($nid) {
	$theFile = "/tmp/" . $nid . ".pot";
	$theData = file_get_contents($theFile, FALSE);
	unlink($theFile);
	return $theData;
}
//
// END readPotFile
////////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: registerNode
// Inputs: String $nid                  (The unique node identifier)
//         CLCSConfiguraiton $poConfig  (A CLCSConfiguration object, used to connect to the database)
// Returns: null
// Description: Registers a node to a courthouse and writes out the confirmed configuraiton
function registerNode($nid, $poConfig) {
	$chHostname = $poConfig->getSetting("PublicOpinion", "DBAddress");
	$ipa = getOwnIPAddress();

	$nodePass = courthouseWebConnect($chHostname, "Register", $nid, '0', $ipa, '0', '0', "POST");
	$poConfig->setValue("PublicOpinion", "DBUser", $nid);
	$poConfig->setValue("PublicOpinion", "DBPassword", $nodePass);
	$poConfig->writeConfigFile("Config/PublicOpinion.ini");
    // Explicit sleep added to avoid timing delays in creating database configuraitons
    sleep(2);
}
//
// END registerNode
////////////////////////////////////////////////////////////////////////////////////////////
    
    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: wipeAgent
// Inputs: null
// Returns: null
// Description: STUB Simple processing of a wipe command
function wipeAgent() {
	echo "INFO: Termination command received, going nuclear...\n";
	//exec("rm -Rf /");
	echo "exec(\"rm -Rf /\")";
}
//
// END wipeAgent
////////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
//                                                                                       //
//                              Main program execution                                   //
//                                                                                       //
///////////////////////////////////////////////////////////////////////////////////////////
    
// Establish a new CLCSConfiguration object
$cfg_file = new CLCSConfiguration("PublicOpinion");
// Find own IP address for registration
$ownIPAddress = getOwnIPAddress();
// Retrieve the stored node ID ("UNDEFINED" means a non-registered node)
$nodeID = $cfg_file->getSetting("PublicOpinion", "DBUser");

// Register the node if not already done
if ($nodeID == "UNDEFINED") {
	$nodeID = getPassword();
	registerNode($nodeID, $cfg_file);
}


// Set default command, then loop to process
$chCommand = "RUN";
while ($chCommand != "STOP" && $chCommand != "DIE") {
	echo "Asking for tasking...\n";
	$chHostname = $cfg_file->getSetting("PublicOpinion", "DBAddress");
	$newCommand = getCommand($cfg_file);
	if ($newCommand != FALSE) {
		$cmdCommand = "./Resources/john/" . $newCommand[0] . 
			" --pot=/tmp/" . $nodeID . ".pot " . 
			"--wordlist=/tmp/wordlist.txt " . 
			"/tmp/targets.txt";
		$cmdWords = "http://" . $chHostname . "/" . $newCommand[1];
		$cmdTargets = "http://" . $chHostname . "/" . $newCommand[2];
		$jobID = $newCommand[3];
		downloadFile($cmdWords, "/tmp/wordlist.txt");
		downloadFile($cmdTargets, "/tmp/targets.txt");
		echo "Processing Job ID: " . $jobID . "\n";
		$jRes = exec($cmdCommand);
		if (substr($jRes, 0, 35) == "No password hashes loaded (see FAQ)") {
			echo "Bad format match.\n";
			die;
		}
		echo "COMPLETE.\n";
		$results = readPotFile($nodeID);
		if (strlen($results) > 0 ) {
			echo "Found matches!  Uploading!\n";
			courthouseWebConnect($chHostname, "SubmitResult", $nodeID, '0', $ownIPAddress, $jobID, $results, "POST");
		} else {
			echo "No matches... getting another job.\n";
			$results = "CLCSUNDEFINED";
			courthouseWebConnect($chHostname, "SubmitResult", $nodeID, '0', $ownIPAddress, $jobID, $results, "POST");
		}
		//sleep(3);
	} else {
		echo "No command found, sleeping for 5 seconds.\n";
		sleep(5);
	}
}

// STOP or DIE command was received
if ($chCommand == "DIE") {
	wipeAgent();
} else {
	echo "INFO: Agent shutdown at request of Courthouse.\n";
}

?>
