<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-08-22
// Unit: Core
// File: CLCS_Common.php
//
// Description: Provides functions that are common to all modules
//
////////////

/// Class definition
class CLCSConfiguration
{
	private $sections = array();
	private $settings = array();
	private $filename = "default.ini";

	function __construct($dbType) {
		$this->filename = "Config/" . $dbType . ".ini";
		$this->readConfigFile($this->filename);
	}

	private function readConfigFile($theFile) {
		$this->settings = parse_ini_file($theFile, TRUE) or die("Could not open config file: " . $theFile . "\n");
		foreach ($this->settings as $section => $setting) {
			$this->sections[] = $section;
		}
	}
	
	public function showSections() {
		echo "Listing sections:\n";
		echo "-----------------\n";
		foreach ($this->sections as $section) {
			echo $section . "\n";
		}
	}
	
	public function getSetting($cfgsection, $cfgsetting) {
		return $this->settings[$cfgsection][$cfgsetting];
	}
	
	public function setValue($cfgsection, $cfgsetting, $settingValue) {
		$this->settings[$cfgsection][$cfgsetting] = $settingValue;
	}
	
	public function showAllSettings() {
		foreach ($this->settings as $section => $setting) {
			echo $section . "\n";
			echo "-----------------------\n";
			foreach ($setting as $key => $value ) {
				echo "   " . $key . " : " . $value . "\n";
			}
		}
	}
	
	public function writeConfigFile($theFile) {
		$fh = fopen($theFile, 'w');
		$newData = "";
		foreach ($this->settings as $section => $setting) {
			$newData .= "[" . $section . "]\n";
			foreach ($setting as $key => $value) {
				$newData .= $key . " = " . $value . "\n";
			}
		}
		fwrite($fh, $newData);
		fclose($fh);
	}	
}

//////////////// Generic Functions /////////////////////

function clearScreen() {
	$looper = 1;
	while ($looper < 80) {
		echo "\n";
		$looper += 1;
	}
}

function connectManual($dbHost, $dbUser, $dbPort, $dbPass, $theDB, $showErrors) {
	$dbConnector = new mysqli($dbHost, $dbUser, $dbPass, $theDB, $dbPort);
	if ($dbConnector->connect_errno) {
		if ($showErrors) {
			die ("ERROR: Failed to connect to $dbHost.  Error was:\n(" . $dbConnector->connect_errno . ")" . $dbConnector->connect_error );
		} else {
			die;
		}
	} else {
		if ($showErrors) {
			echo "INFO: Connected to $dbHost\n";
		}
	}
	return $dbConnector;
}

function connectTo($configFile, $dbType, $useDB, $showErrors) {
	$dbHost = $configFile->getSetting($dbType, "DBAddress");
	$dbUser = $configFile->getSetting($dbType, "DBUser");
	$dbPort = $configFile->getSetting($dbType, "DBPort");
	$dbPass = $configFile->getSetting($dbType, "DBPassword");
	if ($useDB) {
		$theDB = $dbType;
	} else {
		$theDB = '';
	}
	$dbConnector = new mysqli($dbHost, $dbUser, $dbPass, $theDB, $dbPort);
        if ($dbConnector->connect_errno) {
		if ($showErrors) {
                	die ("ERROR: Failed to connect to $dbType.  Error was:\n(" . $dbConnector->connect_errno . ")" . $dbConnector->connect_error );
		} else { 
			die;
		}
        } else {
                if ($showErrors) {
			echo "INFO: Connected to $dbType on $dbHost\n";
		}
        }
	return $dbConnector;
}

function downloadFile($url, $targetDirectory) {
        $options = array(
                'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'GET',
                ),
        );
        $context  = stream_context_create($options);
        $fileData = file_get_contents($url, false, $context);
	writeFile($targetDirectory, $fileData);
}

function generateCrypto($type, $filename) {
	echo "Generating new crypto...\n";
	// Remove any old key material
	system("rm -f ./Config/$filename");
	system("rm -f ./Config/$filename.pub");
	// Generate new key material
	if ($type == "host") {
		system("ssh-keygen -q -t rsa -b 4096 -f ./$filename -h -N '' ");
		if (!file_exists("./Config/$filename") || !file_exists("./Config/$filename.pub")) {
			die("ERROR: Failed to create key material\n");
		}	
	} else {
		system("ssh-keygen -q -t rsa -b 4096 -f ./$filename -N '' ");
		if (!file_exists("./Config/$filename") || !file_exists("./Config/$filename.pub")) {
			die("ERROR: Failed to create key material\n");
		}
	}
}

function getOwnIPAddress()
{
	exec("dig +short myip.opendns.com @resolver1.opendns.com", $myIP);
	return $myIP[0];
}

function getPassword($length=15) {
	$quickSet = substr(md5(rand()), 0, $length);
	return $quickSet;
}

function getUserInput($prompt, $makeUpper=FALSE) {
	echo "$prompt ";
	if ($makeUpper) {
		return strtoupper(readline());	
	} else {
		return readline();
	}
}

function getValidFile() {
	$someFile = NULL;
	while (!file_exists($someFile)) {
		echo "Please enter the file to be used: ";
		$someFile = readline();
		if (!file_exists($someFile)) {
			echo "ERROR: File not found.  Please specify a valid file.\n";
		}
	};
	return $someFile;
}

function loadSQLFile($dbType, $dbConfig, $sqlFile) {
	$dbCon = connectTo($dbConfig, $dbType, FALSE, TRUE);
	$sqlCommands = file_get_contents($sqlFile);
	$sqlQuery = explode(";", $sqlCommands);
	$sqlIndex = 0;
	$dbCon->query("USE $dbType");
	if ($dbCon->multi_query($sqlCommands)) {
		do {
			$dbCon->next_result();
			$sqlIndex++;
		} while ( $dbCon->more_results() );
	}
	if ( $dbCon->errno ) {
		die ("ERROR: Loading of $sqlFile to $dbType database failed at line: $sqlIndex \nCommand: $sqlQuery[$sqlIndex]\n");
	} else {
		echo "INFO: Successfully loaded $sqlFile to $dbType database without error.\n";
	}
	$dbCon->close();
}

function loadTargets($processDirectory, $dbType, $dbConfig) {
	$dbCon = connectTo($dbConfig, $dbType, FALSE, TRUE);
	$dbTdir = $dbConfig->getSetting($dbType, "TargetsDirectory");
	$sqlAddTgts = "USE " . $dbType . ";";
	$tgtFiles = array();
	if ($processDirectory==TRUE) {
		$scanFiles = preg_grep('/^([^.])/', scandir($dbTdir));
		foreach($scanFiles as $tgtFile) {
			$fileLoc = $dbTdir . "/" . $tgtFile;
			array_push($tgtFiles, $fileLoc);
		}
	} else {
		$userFile = getValidFile();
		echo "Using file: $userFile\n";
		array_push($tgtFiles, $userFile);
	}
	foreach ($tgtFiles as $targetfile) {
		echo "INFO: Processing $targetfile\n";
		$pushlist = "INSERT IGNORE INTO Targets (`hashtype_id`, `hash_value`, `confidence`) VALUES ";
		$filecontents = file($targetfile, FILE_IGNORE_NEW_LINES);
		$readInfo = FALSE;
		$hashID = 9999;
		$skipfile = FALSE;
		foreach ($filecontents as $hashline) {
			if (!$readInfo) {
				$hashtype = substr($hashline, 2);
				$sqlQuery = "SELECT `id` FROM $dbType.Hashtype WHERE `name` = '" . $hashtype . "';";
				$tempHashResult = $dbCon->query($sqlQuery);
				$tempHash = $tempHashResult->fetch_row();
				if (is_null($tempHash)) {
					echo "ERROR: Unable to find specified hashtype of $hashtype in $targetfile\n";
					$skipfile = TRUE;
				} else {
					$hashID = $tempHash[0];
					echo "INFO: Found matching HashID of $hashID\n";
				}
				$readInfo = TRUE;
				$tempHashResult->close();
			} else {
				$pushlist .= "(" . $hashID . ", '" . $hashline . "', 0 ),";
			}
			if ($skipfile) {
				echo "ERROR: Skipping file $targetfile due to invalid hash type\n";
				break;
			}
		}
		if (!$skipfile) {
			$pushlist = substr($pushlist, 0, -1) . ";";
			$sqlAddTgts .= $pushlist;
		}
	}
	$sqlQuery = explode(";", $sqlAddTgts);
	$sqlIndex = 0;
	if ($dbCon->multi_query($sqlAddTgts)) {
		do {
			$dbCon->next_result();
			$sqlIndex++;
		} while ( $dbCon->more_results() );
	}
	if ( $dbCon->errno ) {
		var_dump($sqlAddTgts);
		die ("ERROR: Loading targets to $dbType database failed at line: $sqlIndex \nCommand: $sqlQuery[$sqlIndex]\n");
	} else {
		echo "INFO: Successfully loaded targets to $dbType database without error.\n";
	}
	$dbCon->close();
	echo "INFO: Closed connection.\n";
}

function loadWordlist($processDirectory, $dbType, $dbConfig) {
	$dbCon = connectTo($dbConfig, $dbType, FALSE, TRUE);
	$dbWdir = $dbConfig->getSetting($dbType, "WordlistDirectory");
	$sqlAddWords = "USE " . $dbType . ";";
	$wlFiles = array();
	if ($processDirectory==TRUE) {
		$scanFiles = preg_grep('/^([^.])/', scandir($dbWdir));
		foreach($scanFiles as $wlFile) {
			$fileLoc = $dbWdir . "/" . $wlFile;
			array_push($wlFiles, $fileLoc);
		}
	} else {
		$userFile = getValidFile();
		echo "Using file: $userFile\n";
		array_push($wlFiles, $userFile);
	}
	foreach ($wlFiles as $wordlist) {
		echo "INFO: Processing $wordlist\n";
		$pushlist = "INSERT IGNORE INTO Words (`Word`) VALUES ";
		$filecontents = file($wordlist, FILE_IGNORE_NEW_LINES);
		foreach ($filecontents as $word) {
			$pushlist .= "('" . $word . "'),";
		}
		$pushlist = substr($pushlist, 0, -1);
		$sqlAddWords .= $pushlist . ";";
	}
	$sqlQuery = explode(";", $sqlAddWords);
	$sqlIndex = 0;
	if ($dbCon->multi_query($sqlAddWords)) {
		do {
			$dbCon->next_result();
			$sqlIndex++;
		} while ( $dbCon->more_results() );
	}
	if ( $dbCon->errno ) {
		die ("ERROR: Loading words to $dbType database failed at line: $sqlIndex \nCommand: $sqlQuery[$sqlIndex]\n");
	} else {
		echo "INFO: Successfully loaded words to $dbType database without error.\n";
	}
	$dbCon->close();
	echo "INFO: Closed connection.\n";
}

function printHTMLFooter() {
	echo "</td></tr>\n";
	echo "<tr><td width=\"100%\"><center>Cryptolingus Cracking Suite (CLCS)</center></td></tr></table>\n";
	echo "</body>\n";
	echo "</html>\n";
}

function printHTMLHeader($siteType) {
	echo "<html>\n";
	echo "<head><title>CLCS - " . $siteType . "</title></head>\n";
	echo "<body bgcolor=\"#000\" text=\"#fff\" link=\"#00f\" alink=\"#00f\" vlink=\"#00f\">\n";
	echo "<table width=\"100%\" height=\"100%\">\n";
	echo "<tr><td width=\"100%\"><center><font face=\"Lucida Console, Monaco, monospace\" size=\"+4\"><u>Cryptolingus Cracking Suite</u></font></center></td></tr>\n";
	echo "<tr with=\"100%\" height=\"100%\"><td>";
}

function resetCLCSdb($dbType, $dbConfig) {
	$dbCon = connectTo($dbConfig, $dbType, FALSE, TRUE);
	$configFile = "Config/" . $dbType . ".sql";
	$dbCon->query("DROP DATABASE IF EXISTS $dbType");
	$dbCon->query("CREATE DATABASE $dbType");
	$dbCon->close();
	loadSQLFile($dbType, $dbConfig, $configFile);
}

function showError($error="") {
	if (!defined($error)) {
		$error = "ERROR: Invalid input selected.  Please try again.\n";
	}
	echo "ERROR: $error\n";
	sleep(1);
}

function userAck() {
	echo "Press ENTER to continue...\n";
	fgetc(STDIN);
}

function writeFile($theFile, $theData) {
	$fh = fopen($theFile, 'w');
	fwrite($fh, $theData);
	fclose($fh);
}
