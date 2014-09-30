<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-09-27
// Unit: Courtroom
// File: index.php
//
// Description: Frontend for pulling content
//
////////////

include 'Resources/CLCommon/CLCS_Common.php';

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: assignJob
// Inputs: CLCSConfiguration $chConfig  (A CLCSConfiguration object used to connect to the database)
//         String $nid                  (The unique node identifier)
// Returns: null
// Description: Finds the next available job and assigns it to a requesting node
function assignJob($chConfig, $nid) {
	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	// Find an available job
	$sqlNextJob = "SELECT `job_id`,`word_file`,`hash_file`,`hashtype_id` FROM `Jobs` WHERE `node_id` IS NULL AND `complete`=FALSE LIMIT 1;";
	$tempJobResults = $dbCon->query($sqlNextJob);
	$jobResults = $tempJobResults->fetch_row();
	$jobID = $jobResults[0];
	if (!is_null($jobID)) {
		$jobWordfile = $jobResults[1];
		$jobHashfile = $jobResults[2];
		$hType = $jobResults[3];
		// Capture the job in the Jobs table
		$sqlGrabJob = "UPDATE `Jobs` SET `node_id`='" . $nid . "' WHERE `job_id`='" . $jobID . "';";
		$dbCon->query($sqlGrabJob);
		//Find the John CLI type... no Hashcat right now, sorry people
		$sqlJohnCLI = "SELECT Johntype.cl_type FROM Johntype INNER JOIN Hashtype ON Johntype.id = Hashtype.johntype_id WHERE Hashtype.id = '" . $hType . "';";
		$tempTaskDetailResults = $dbCon->query($sqlJohnCLI);
		$taskDetailResults = $tempTaskDetailResults->fetch_row();
		$johnType = $taskDetailResults[0];
		// Update the _TASK table to contain the required information
		$sqlSetTask = "INSERT INTO `" . $nid . "_TASKS` (`command`, `target`, `words`, `job_id`) VALUES ('john --format=" . $johnType . "', '" . $jobWordfile . "', '" . $jobHashfile . "', '" . $jobID . "' );";
		$dbCon->query($sqlSetTask);
	}
	// Close the database
	$dbCon->close();
}
//
// END assignJob
////////////////////////////////////////////////////////////////////////////////////////////

    

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: createuserAndTable
// Inputs: CLCSConfiguration $chConfig  (A CLCSConfiguration object used to connect to the database)
//         String $nodeID               (The unique node identifier)
//         String $otp                  (The password used to identify the unique node to the database server)
// Returns: null
// Description: Process a new registration and build/grant access to the node-specific _TASKS and _RESULTS tables
function createUserAndTable($chConfig, $nodeID, $otp) {
	// Build a new set of tables and grant the user permissions to those tables
	$sqlAddTable = "CREATE TABLE Courthouse." . $nodeID . "_TASKS (`task` INT UNSIGNED NOT NULL AUTO_INCREMENT, `command` VARCHAR(255), `target` VARCHAR(255), `words` VARCHAR(255), `job_id` INT UNSIGNED NOT NULL, `complete` BOOL NOT NULL DEFAULT 0, PRIMARY KEY (`task`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
	$sqlAddTable .= "CREATE TABLE Courthouse." . $nodeID . "_RESULTS (`task` INT UNSIGNED NOT NULL AUTO_INCREMENT, `found` BOOL NOT NULL DEFAULT FALSE, `cleartext` VARCHAR(255), PRIMARY KEY (`task`) ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
	$sqlAddTable .= "CREATE USER '" . $nodeID . "'@'%' IDENTIFIED BY '" . $otp . "';";
	$sqlAddTable .= "GRANT SELECT ON Courthouse." . $nodeID . "_TASKS TO '" . $nodeID . "'@'%';";
	$sqlAddTable .= "GRANT INSERT ON Courthouse." . $nodeID . "_RESULTS TO '" . $nodeID . "'@'%';";

	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	$addResult = $dbCon->multi_query($sqlAddTable);
	$dbCon->close();
}
//
// END createUserAndTables
////////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: markJobComplete
// Inputs: CLCSConfiguration $chConfig  (A CLCSConfiguration object used to connect to the database)
//         String $nodeID               (The unique node identifier)
//         String $jobID                (The job ID to mark complete)
// Returns: null
// Description: Performs closeout when a node says it has completed a job)
function markJobComplete($chConfig, $nodeID, $jobID) {
	$sqlFinishJob = "UPDATE `Jobs` SET `complete`=TRUE WHERE `job_id`='" . $jobID . "';";
	$sqlFinishTask = "UPDATE `" . $nodeID . "_TASKS` SET `complete`=TRUE WHERE `job_id`='" . $jobID . "';";
	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	$dbCon->query($sqlFinishJob);
	$dbCon->query($sqlFinishTask);
	$dbCon->close();
}
//
// END markJobComplete
////////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: processResult
// Inputs: CLCSConfiguration $chConfig  (A CLCSConfiguration object used to connect to the database)
//         String $nodeID               (The unique node identifier)
//         String $jobID                (The job ID to process the results of)
//         String $results              (The reported results of the job)
// Returns: null
// Description: Process results from the provided results data
function processResult($chConfig, $nodeID, $jobID, $results) {
	// Flag the job as complete
	markJobComplete($chConfig, $nodeID, $jobID);
	// Get a new job
	assignJob($chConfig, $nodeID);
	// Process the results, assuming there are any...
	$sqlUpdateMatches="";
	if ($results != "CLCSUNDEFINED") {
		$resultLines = preg_split ('/$\R?^/m', $results);
		foreach ($resultLines as $line) {
			$resVals = explode(":", $line);
			$sqlUpdateMatches .= "UPDATE `Targets` SET `cleartext_value`='" . $resVals[1] . "',`confidence`=100 WHERE `hash_value`='" . $resVals[0] . "';"; 
		}
		$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
		$dbCon->multi_query($sqlUpdateMatches);
		$dbCon->close();
	}
}
//
// END processResult
////////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: registerNode
// Inputs: CLCSConfiguration $chConfig  (A CLCSConfiguration object used to connect to the database)
//         String $nodeID               (The unique node identifier)
//         Int $gpu                     (The number of GPU cores available)
//         String $ipa                  (The IP address of the node)
// Returns: null
// Description: Register a new node
function registerNode($chConfig, $nodeID, $gpu, $ipa) {
	$onetimePass = getPassword();
	createUserAndTable($chConfig, $nodeID, $onetimePass); 
	$hasGPU = "FALSE";
	if (is_int($gpu) && $gpu > 0) {
		$hasGPU = "TRUE";
	}
	$currentDate = date('Y-m-d H:i:s');
	$sqlRegisterNode = "INSERT INTO Nodes (`node_id`, `has_gpu`, `ip_address`, `last_checkin`) VALUES ( '" . $nodeID . "', '" . $hasGPU . "', '" . $ipa . "', '" . $currentDate . "' );";
	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	$addResult = $dbCon->query($sqlRegisterNode);
	echo $onetimePass;
	$dbCon->close();
	assignJob($chConfig, $nodeID);
}
//
// END registerNode
////////////////////////////////////////////////////////////////////////////////////////////


    
///////////////////////////////////////////////////////////////////////////////////////////
//                                                                                       //
//                              Main program execution                                   //
//                                                                                       //
///////////////////////////////////////////////////////////////////////////////////////////

// Establish a new CLCSConfiguration object
$cfg_file = new CLCSConfiguration("Courthouse");

// Check for a specified action, build out parameters, and execute
if( $_POST["CLCSA"] ) {
	$clcsAction = $_POST["CLCSA"];
	$clcsNodeID = $_POST["NodeID"];
	$clcsIPAddress = $_POST["IPA"];
	$clcsGPU = $_POST["GPU"];
	$clcsJobID = $_POST["JOBID"];
	$clcsResults = $_POST["RESULTS"];
	switch ($clcsAction) {
		case 'Register':
			registerNode($cfg_file, $clcsNodeID, $clcsGPU, $clcsIPAddress);
			break;
		case 'SubmitResult':
			processResult($cfg_file, $clcsNodeID, $clcsJobID, $clcsResults);
			break;
		default:
			echo "Received.\n";
			break;
	}
} else {
	printHTMLHeader("Courthouse");
	echo "No action specified.";
	printHTMLFooter();
}


?>
