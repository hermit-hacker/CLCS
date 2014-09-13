<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-08-25
// Unit: PublicOpinion
// File: PublicOpinionAgent.php
//
// Description: Requests tasking from a central C&C server, executes, and reports
//
////////////

include 'Resources/CLCommon/CLCS_Common.php';

function courthouseWebConnect($chAddress, $action, $nodeID, $hasGPU, $ipa, $jobID, $results, $type) {
	$url = "http://" . $chAddress . "/index.php";
        $data = array (
                'CLCSA' => $action,
                'NodeID' => $nodeID,
                'GPU' => $hasGPU,
                'IPA' => $ipa,
                'JOBID' => $jobID,
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

function getCommand($poConfig) {
	$dbCon = connectTo($poConfig, "PublicOpinion", FALSE, TRUE);
	$dbUser = $poConfig->getSetting("PublicOpinion", "DBUser");
	$dbTable = "Courthouse." . $dbUser . "_TASKS";
	$sqlGetCommand = "SELECT `command`,`target`,`words`,`job_id` FROM " . $dbTable . " WHERE `complete`=FALSE LIMIT 1;";
	$resultCommand = $dbCon->query($sqlGetCommand);
	return $resultCommand->fetch_row();
}

function readPotFile($nid) {
	$theFile = "/tmp/" . $nid . ".pot";
	$theData = file_get_contents($theFile, FALSE);
	unlink($theFile);
	return $theData;
}

function registerNode($nid, $poConfig) {
	$chHostname = $poConfig->getSetting("PublicOpinion", "DBAddress");
	$ipa = getOwnIPAddress();

	$nodePass = courthouseWebConnect($chHostname, "Register", $nid, '0', $ipa, '0', '0', "POST");
	$poConfig->setValue("PublicOpinion", "DBUser", $nid);
	$poConfig->setValue("PublicOpinion", "DBPassword", $nodePass);
	$poConfig->writeConfigFile("Config/PublicOpinion.ini");
}

function wipeAgent() {
	echo "INFO: Termination command received, going nuclear...\n";
	//exec("rm -Rf /");
	echo "exec(\"rm -Rf /\")";
}

$cfg_file = new CLCSConfiguration("PublicOpinion");
$ownIPAddress = getOwnIPAddress();
$chCommand = "RUN";
$nodeID = $cfg_file->getSetting("PublicOpinion", "DBUser");

// Register if not already done
if ($nodeID == "UNDEFINED") {
	$nodeID = getPassword();
	registerNode($nodeID, $cfg_file);
}


// Loop looking for commands
while ($chCommand != "STOP" && $chCommand != "DIE") {
	echo "Looping...\n";
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
