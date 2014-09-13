<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-08-22
// Unit: Justice
// File: JusticeMenu.php
//
// Description: Displays the control menu for the Justice unit
//
////////////

include 'Resources/CLCommon/CLCS_Common.php';

function runMenu($justiceConfig) {
	$UserChoice = "I";
	$TgtCH = "localhost";

    while ($UserChoice <> "X") {
    	clearScreen();
		echo "+-------------------------------------------------------------------------+\n";
		echo "|                                                                         |\n";
		echo "|          Cryptolingus Cracking Suite (CLCS) - JUSTICE                   |\n";
		echo "|                                                                         |\n";
		echo "+-------------------------------------------------------------------------+\n";
		echo "\n";
		echo "Current Courthouse: " . $TgtCH . "\n";
		echo "\n";
		echo "Commands:\n";
		echo "---------\n";
		echo "[W] Load wordlist\n";
		echo "[T] Load targets\n";
		echo "\n";
		echo "[V] View status\n";
		echo "[Q] Query Courthouses\n";
		echo "[S] Sync Courthouses\n";
		echo "\n";
		echo "[C] Show configuration\n";
		echo "[I] Initiate/Reset Database\n";
		echo "[A] Add Courthouse\n";
		echo "[K] Shutdown Courthouse\n";
		echo "[X] Exit Justice\n";
		echo"\n";
		echo "\n";
		$UserChoice = getUserInput("Choice:", TRUE);
		
		switch($UserChoice) {
			case 'A':
				addCourthouse($justiceConfig);
				break;
			case 'I':
				initializeJustice($justiceConfig);
				break;
			case 'K':
				$TargetCourthouse = chooseCourthouse();
				stopCourthouse($TargetCourthouse);
				userAck();
				break;
			case 'Q':
				updateFromCourthouses($justiceConfig);
				break;
			case 'C':
				$justiceConfig->showAllSettings();
				userAck();
				break;
			case 'T':
				loadTargets(FALSE, "Justice", $justiceConfig);
				userAck();
				break;
			case 'V':
				showJusticeStatus($justiceConfig);
				break;
			case 'W':
				loadWordlist(FALSE, "Justice", $justiceConfig);
				userAck();
				break;
			case 'X':
				break;
			case 'S':
				updateFromCourthouses($justiceConfig);
				updateAllCourthouses($justiceConfig);
				break;
			default:
				showError();
				break;
		}
    }
}

// Add a Courthouse to be controlled by this Justice
function addCourthouse($jConfig) {
	$chAddress = getUserInput("New address (FQDN or IP address):");
	$chPort = getUserInput("Port:");
	$chUsername = getUserInput("Username:");
	$chPassword = getUserInput("Password:");
	$sqlAddCourthouse = "INSERT INTO Courthouses (`address`, `port`, `username`, `userpass` ) VALUES ('" . $chAddress . "', '" . $chPort . "', '" . $chUsername . "', '" . $chPassword . "' );";
	$dbCon = connectTo($jConfig, "Justice", TRUE, FALSE);
	if ($dbCon->query($sqlAddCourthouse)) {
		echo "Courthouse added!\n";
	}
	userAck();
}

// Stub to select a Courthouse
function chooseCourthouse() {
	$newCH = getUserInput("What Courthouse would you like to interface with?");
	return $newCH;
}

// Generate new crypto for Justice
function configureCLCS() {
	if (getUserInput("Generate new key material? ") == "Y") {
		generateCrypto("host", "justice");
	}
	sleep(2);
}

// Reset the database, read in wordlists and targets (if so desired)
function initializeJustice($jConfig) {
        resetCLCSdb("Justice", $jConfig);
        $processTargetsDirectory = getUserInput("Process the targets directory to load targets [Y/N]?", TRUE);
        if ($processTargetsDirectory == "Y") {
                loadTargets(TRUE, "Justice", $jConfig);
        }
        $processWordlistsDirectory = getUserInput("Process the wordlists directory to load wordlists [Y/N]?", TRUE);
        if ($processWordlistsDirectory == "Y") {
                loadWordlist(TRUE, "Justice", $jConfig);
        }
        userAck();
}

// Show status of cracking efforts
function showJusticeStatus($jConfig) {
        $dbCon = connectTo($jConfig, "Justice", TRUE, FALSE);
        $tempSolvedResult = $dbCon->query("SELECT COUNT(*) FROM Justice.Targets WHERE `isbenchmark`=false AND `cleartext_value` IS NOT NULL");
        $tempSolved = $tempSolvedResult->fetch_row();
        if (is_null($tempSolved)) {
                $resSolved = 0;
        } else {
                $resSolved = $tempSolved[0];
        }
        $tempSolvedResult->close();
        $tempTotalResult = $dbCon->query("SELECT COUNT(*) FROM Justice.Targets WHERE `isbenchmark`=false");
        $tempTotal = $tempTotalResult->fetch_row();
        if (is_null($tempTotal)) {
                $resTotal = 0;
        } else {
                $resTotal = $tempTotal[0];
        }
        $tempTotalResult->close();
        if ($resTotal == 0) {
                echo "There are no targets defined.  Perhaps you should add some?\n";
        } else {
                $resPercent = 100 * ( floatval($resSolved) / floatval($resTotal) );
                echo "There are currently $resSolved / $resTotal ($resPercent %) cracked.\n";
        }
	$dbCon->close();
        userAck();
}

// Stop a courthouse
function stopCourthouse($TgtCourthouse) {
	echo "Stopping Courthouse: $TgtCourthouse\n";
	sleep(2);
}

// Push wordlists and targets to all Courthouses
function updateAllCourthouses($jconfig) {
	$dbCon = connectTo($jconfig, "Justice", TRUE, FALSE);
	$sqlAllCourthouses = "SELECT `address`,`port`,`username`,`userpass` FROM Courthouses;";
        $allCH = $dbCon->query($sqlAllCourthouses);
	$sqlAllWords = "SELECT * from Words ORDER BY `id`;";
	echo "Retrieving all words...\n";
	$allWords = $dbCon->query($sqlAllWords);
	// Convert the words to a suitable insertion statement
	$sqlInsertWords = "INSERT IGNORE INTO `Words` (`id`, `word`) VALUES ";
	while ($wordRecord = $allWords->fetch_row()) {
		$wid = $wordRecord[0];
		$word = $wordRecord[1];
		$sqlInsertWords .= "( '" . $wid . "', '" . $word . "' ),";
	}
	$sqlInsertWords = substr($sqlInsertWords, 0, -1) . ";";

	$sqlAllTargets = "SELECT * from `Targets` ORDER BY `id`;";
	echo "Retrieving all unsolved targets...\n";
	$allTargets = $dbCon->query($sqlAllTargets);
	// Convert the targets to a suitable insertion statement
	$sqlInsertTargets = "TRUNCATE TABLE `Targets`; INSERT IGNORE INTO `Targets` (`id`, `ishash`, `isbenchmark`, `hashtype_id`, `hash_value`, `confidence` ) VALUES ";
        while ($targetRecord = $allTargets->fetch_row()) {
                if (is_null($targetRecord[5])) {
			$tID = $targetRecord[0];
			$tIH = $targetRecord[1];
			$tBM = $targetRecord[2];
			$tHI = $targetRecord[3];
			$tHV = $targetRecord[4];
			$tCF = $targetRecord[6];
			$sqlInsertTargets .= "( '" . $tID . "', '" . $tIH . "', '" . $tBM . "', '" . $tHI . "', '" . $tHV . "', '" . $tCF . "' ),";
		}
        }
        $sqlInsertTargets = substr($sqlInsertTargets, 0, -1) . ";";
        while ($chRecord = $allCH->fetch_row()) {
                $chAddress = $chRecord[0];
                $chPort = $chRecord[1];
                $chUser = $chRecord[2];
                $chPass = $chRecord[3];
                $chCon = connectManual($chAddress, $chUser, $chPort, $chPass, "Courthouse", FALSE);
		echo "Pushing updated wordlist to " . $chAddress . ":" . $chPort . "\n";
                $pushWords = $chCon->query($sqlInsertWords);
		echo "Pushing updated targets to " . $chAddress . ":" . $chPort . "\n";
		$pushTargets = $chCon->multi_query($sqlInsertTargets);
		$chCon->close();
        }

	$dbCon->close();
	userAck();
}

// Pull results back form all Courthouses
function updateFromCourthouses($jConfig) {
	$dbCon = connectTo($jConfig, "Justice", TRUE, FALSE);
	$sqlAllCourthouses="SELECT `address`,`port`,`username`,`userpass` FROM Courthouses;";
	$allCH = $dbCon->query($sqlAllCourthouses);
	$sqlSolvedTargets="SELECT `hash_value`,`cleartext_value` FROM Targets WHERE `isbenchmark`=false and `cleartext_value` IS NOT NULL;";
	$solutions = array();
	while ($chRecord = $allCH->fetch_row()) {
		$chAddress = $chRecord[0];
		$chPort = $chRecord[1];
		$chUser = $chRecord[2];
		$chPass = $chRecord[3];
		$chCon = connectManual($chAddress, $chUser, $chPort, $chPass, "Courthouse", FALSE);
		$solvedTargets = $chCon->query($sqlSolvedTargets);
		while ($targetSolution = $solvedTargets->fetch_row()) {
			$hVal = $targetSolution[0];
			$cVal = $targetSolution[1];
			$solutions[$hVal] = $cVal;
		}
		$solvedTargets->close();
	}
	$chCon->close();
	$numSolved = count($solutions);
	echo "Found " . $numSolved . " new solutions across all Courthouses.\n";
	if ($numSolved) {
		$sqlSolutions = "";
		foreach ($solutions as $key=>$value) {
			$sqlSolutions .= "UPDATE `Targets` SET `cleartext_value`='". $value . "',  `confidence`=100 WHERE `hash_value` = '" . $key . "';";
		}
		$dbCon->multi_query($sqlSolutions);
		$dbCon->close();
	} else {
		$dbCon->close();
	}
	sleep(1);
}

/////// Main program execution /////////
$cfg_file = new CLCSConfiguration("Justice");
runMenu($cfg_file);

?>
