#!/usr/bin/php

<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-08-25
// Unit: Courtroom
// File: CourtroomAgent.php
//
// Description: Command and control interface
//
////////////

include 'Resources/CLCommon/CLCS_Common.php';

function runMenu($courthouseConfig) {
    $UserChoice = "I";

    while ($UserChoice <> "X") {
    	clearScreen();
		echo "+-------------------------------------------------------------------------+\n";
		echo "|                                                                         |\n";
		echo "|          Cryptolingus Cracking Suite (CLCS) - COURTHOUSE                |\n";
		echo "|                                                                         |\n";
		echo "+-------------------------------------------------------------------------+\n";
		echo "\n";
		echo "Commands:\n";
		echo "---------\n";
		echo "[I] Initialize Courthouse\n";
		echo "[B] Build jobs... for 'murica\n";
		echo "[S] Show configuration\n";
		echo "[V] View status\n";
		echo "[X] Exit Justice\n";
		echo"\n";
		$UserChoice = getUserInput("Choice:", TRUE);
		
		switch($UserChoice) {
			case 'B':
				buildJobs($courthouseConfig);
				break;
			case 'I':
				initializeCourthouse($courthouseConfig);
				break;
			case 'S':
				$courthouseConfig->showAllSettings();
				userAck();
				break;
			case 'V':
				showCourthouseStatus($courthouseConfig);
				break;
			case 'X':
				break;
			default:
				showError();
				break;
		}
    }
}

function buildJobs($chConfig) {
	echo "Building jobs from wordlists and targets... ";
	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	$tempTotalWordResults = $dbCon->query("SELECT COUNT(*) FROM `Words`;");
	$tempTotalWords = $tempTotalWordResults->fetch_row();
	$totalWords = $tempTotalWords[0];
	$loopCounter = 0;
	$loopJumper = $chConfig->getSetting("Courthouse", "JobChunkSize");
	$allWordlists = array();
	$allTargetlists = array();
	while ($loopCounter <= $totalWords) {
		$sqlGetWords = "SELECT * FROM `Words` ORDER BY `id` LIMIT " . $loopCounter . ", " . $loopJumper . ";";
		$wordListResults = $dbCon->query($sqlGetWords);
		$fileData = "";
		while ( $row = $wordListResults->fetch_row() ) {
			$fileData .= $row[1] . "\n";
		}
		if (strlen($fileData) ) {
			$fileName = "Resources/w/" . getPassword();
			writeFile($fileName, $fileData);
			array_push($allWordlists, $fileName);
		}
		$loopCounter += $loopJumper;
	}
	$allHashtypeResults = $dbCon->query("SELECT `id` FROM `Hashtype`;");
	while ( $htRow = $allHashtypeResults->fetch_row() ) {
		$htID = $htRow[0];
		$sqlGetHashes = "SELECT `hash_value` FROM `Targets` WHERE `hashtype_id` = '" . $htID . "';";
		if ($targetListResults = $dbCon->query($sqlGetHashes) ) {
			$fileData = "";
			while ( $row = $targetListResults->fetch_row() ) {
				$fileData .= $row[0] . "\n";
			}
			if (strlen($fileData) ) {
				$fileName = "Resources/t/" . getPassword();
				writeFile($fileName, $fileData);
				$allTargetlists[$htID]=$fileName;
			}
		}
	}
	$sqlBuildJobTable = "INSERT IGNORE INTO `Jobs` (`word_file`, `hash_file`, `hashtype_id`) VALUES ";
	foreach ($allTargetlists as $key=>$value) {
		foreach ($allWordlists as $wl) {
			$sqlBuildJobTable .= "( '" . $wl . "', '" . $value . "', '" . $key . "' ),";
			//echo "Job: ". $wl . " for " . $value . " (type: " . $key . " )\n";
		}
	}
	$sqlBuildJobTable = substr($sqlBuildJobTable, 0, -1) . ";";
	$dbCon->query($sqlBuildJobTable);
	$dbCon->close();
	echo "DONE";
	sleep(2);
}

function initializeCourthouse($chConfig) {
	resetCLCSdb("Courthouse", $chConfig);
	wipeTWDirectories();
	userAck();
}

function showCourthouseStatus($chConfig) {
	$dbCon = connectTo($chConfig, "Courthouse", TRUE, FALSE);
	$tempWordResults = $dbCon->query("SELECT COUNT(*) FROM Words");
	$tempWord = $tempWordResults->fetch_row();
	if (is_null($tempWord)) {
		$wordCount = 0;
	} else {
		$wordCount = $tempWord[0];
	}
	$tempTargetResults = $dbCon->query("SELECT COUNT(*) FROM Targets");
	$tempTarget = $tempTargetResults->fetch_row();
	if (is_null($tempTarget)) {
		$targetCount = 0;
	} else {
		$targetCount = $tempTarget[0];
	}
	$tempWordResults->close();
	$tempTargetResults->close();
	$dbCon->close();
	echo "Statistics:\n";
	echo "Words:   " . $wordCount . "\n";
	echo "Targets: " . $targetCount . "\n";
	userAck();
}

function wipeTWDirectories() {
	$scanFiles = preg_grep('/^([^.])/', scandir('Resources/w/'));
	foreach ($scanFiles as $tgtFile) {
		$fileName = "Resources/w/" . $tgtFile;
		unlink($fileName);
	}
	$scanFiles = preg_grep('/^([^.])/', scandir('Resources/t/'));
	foreach ($scanFiles as $tgtFile) {
		$fileName = "Resources/t/" . $tgtFile;
		unlink($fileName);
	}
}


/////// Main program execution /////////
$cfg_file = new CLCSConfiguration("Courthouse");
runMenu($cfg_file);

?>
