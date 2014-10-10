<?php
/////////////
//
// Cryptolingus Cracking Suite (CLCS) version 1.1
//
// Modified: 2014-10-09
// Unit: Core
// File: CLCS_Common.php
//
// Description: Provides functions that are common to all modules
//
////////////

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: CLASS_DEF CLCSConfiguration
// Inputs:  String $dbType
// Returns: null
// Description: Builds a CLCSConfiguraiton object
class CLCSConfiguration
{
    // Sections is list of sections,  settings is just settings, and filename is the locaiton of the config files
	private $sections = array();
	private $settings = array();
	private $filename = "default.ini";

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->{default constructor}
    // Inputs:  String $dbType
    // Returns: null
    // Description: Builds a new CLCSConfiguration object by reading in a configuration file
	function __construct($dbType) {
		$this->filename = "Config/" . $dbType . ".ini";
		$this->readConfigFile($this->filename);
	}
    //
    // END CLCSConfiguration->{default constructor}
    ///////////////////////////////////////////////////////////////////////////////////////////
    
	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->getSetting
    // Inputs:  String $cfgsection (the section to retrieve a setting from)
    //          String $cfgsetting (the actual setting key to retrieve)
    // Returns: String containing the specified value for the requested section/key
    // Description: Get the setting for a specified key in a specified section
	public function getSetting($cfgsection, $cfgsetting) {
		return $this->settings[$cfgsection][$cfgsetting];
	}
    //
    // END CLCSConfiguration->getSetting
    ///////////////////////////////////////////////////////////////////////////////////////////
    
	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->readConfigFile
    // Inputs:  String $theFile (file to be read in)
    // Returns: null
    // Description: Takes a specified PHP INI format file and reads it into a CLCSConfiguration object, enumerating sections as it goes
	private function readConfigFile($theFile) {
		$this->settings = parse_ini_file($theFile, TRUE) or die("Could not open config file: " . $theFile . "\n");
		foreach ($this->settings as $section => $setting) {
			$this->sections[] = $section;
		}
	}
    //
    // END CLCSConfiguration->readConfigFile
    ///////////////////////////////////////////////////////////////////////////////////////////

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->setValue
    // Inputs:  String $cfgsection    (the section to set the key/value in)
    //          String $cfgsetting    (the setting key to be set)
    //          String $settingValue  (the value to be set for the specified key)
    // Returns: null
    // Description: Sets a specified key in a specified section to a specified value
	public function setValue($cfgsection, $cfgsetting, $settingValue) {
		$this->settings[$cfgsection][$cfgsetting] = $settingValue;
	}
    //
    // END CLCSConfiguration->setValue
    ///////////////////////////////////////////////////////////////////////////////////////////

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->showAllSettings
    // Inputs:  null
    // Returns: null
    // Description: Prints all settings to the console
    public function showAllSettings() {
		foreach ($this->settings as $section => $setting) {
			echo $section . "\n";
			echo "-----------------------\n";
			foreach ($setting as $key => $value ) {
				echo "   " . $key . " : " . $value . "\n";
			}
		}
        userAck();
	}
    //
    // END CLCSConfiguration->showAllSettings
    ///////////////////////////////////////////////////////////////////////////////////////////
    
    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->showSections
    // Inputs:  null
    // Returns: null
    // Description: Prints a list of all sections to the console
	public function showSections() {
		echo "Listing sections:\n";
		echo "-----------------\n";
		foreach ($this->sections as $section) {
			echo $section . "\n";
		}
	}
    //
    // END CLCSConfiguration->showSections
    ///////////////////////////////////////////////////////////////////////////////////////////

	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLCSConfiguration->writeConfigurationFile
    // Inputs:  String $theFile (Specifies the configuration file to be written)
    // Returns: null
    // Description: Writes the CLCSConfiguration object out to a file
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
    //
    // END CLCSConfiguration->writeConfigFile
    ///////////////////////////////////////////////////////////////////////////////////////////
}
//
// END CLASS_DEF CLCSConfiguration
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
//                                                                                       //
//                                      Generic Functions                                //
//                                                                                       //
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
// Function name: clearScreen
// Inputs: null
// Returns: null
// Description: Creates an OS independent clear screen by outputting 80 blank lines
function clearScreen() {
	$looper = 1;
	while ($looper < 80) {
		echo "\n";
		$looper += 1;
	}
}
//
// END clearScreen
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: connectManual
// Inputs: String $dbHost       (The database server to connect to)
//         String $dbUser       (The username to connect as)
//         String $dbPort       (The port for the database to connect to)
//         String $dbPass       (The password to use)
//         String $theDB        (The name of the database in the database server)
//         Bool   $showErrors   (If true, show detailed error information, otherwise only show overall status)
// Returns: A mysqli connection object
// Description: Allows for manual specification of all parameters to establish a database connection
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
//
// END connectManual
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: connectTo
// Inputs: CLCSConfiguration $configFile    (A CLCSConfiguration object that contains all the database connector information)
//         String $dbType                   (The database type, used to pull the appropriate configuration items from the CLCSConfiguration object)
//         Bool $useDB                      (If ture, switch into the database, otherwise don't select one; allows for explicit versus implicit references)
//         Bool $showErrors                 (If true, show detailed error information, otherwise only show overall status)
// Returns: A mysqli connection object
// Description: Uses a CLCSConfiguration object to retrieve connection options, then connects to a database
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
//
// END connectTo
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: downloadFile
// Inputs: String $url              (The full URL to the file to download)
//         String $targetFile       (The full path - including file name - where the result should be saved)
// Returns: null
// Description: Downloads a specified file to a specified directory
function downloadFile($url, $targetFile) {
        $options = array(
                'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'GET',
                ),
        );
        $context  = stream_context_create($options);
        $fileData = file_get_contents($url, false, $context);
        writeFile($targetFile, $fileData);
}
//
// END downloadFile
///////////////////////////////////////////////////////////////////////////////////////////
    
    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: generateCrypto
// Inputs: String $filename     (The base filename to use when generating crypto keys)
// Returns: null
// Description: Generates RSA 4096 cryptographic keys
//              - Credit to John Snyder (Seoci) for the updated code here
function generateCrypto($filename) {
	echo "Generating new crypto...\n";

	// Remove any old key material
	$prvFile = "./Config/" . $filename;
    $pubFile = "./Config/" . $filename . ".pub";
    if (is_readable($prvFile)) {
		unlink($prvFile);
	}
	if (is_readable($pubFile)) {
		unlink($pubFile);
	}

	// Set options for the key material generation
	$keymatOptions = array (
		“private_key_bits” => 4096,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	);
	// Create a new key pair
	$rsaKey = openssl_pkey_new($keymatOptions);
	// Retrieve the public key to $pem variable
	$privKey = openssl_pkey_get_private($rsaKey);
	openssl_pkey_export($privKey, $privateKey);
	// Retrieve the public key
	$publicKey = sshEncodePublicKey($rsaKey);

	// Export the files
	writeFile($prvFile, $privateKey);
	writeFile($pubFile, $publicKey);

	if (!file_exists("./Config/$filename") || !file_exists("./Config/$filename.pub")) {
        	die("ERROR: Failed to create key material\n");
    }
}
//
// END generateCrypto
///////////////////////////////////////////////////////////////////////////////////////////
    
    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getOwnIPAddress
// Inputs: null
// Returns: String containing the calling system's IP address
// Description: Calls OpenDNS to find the calling system's IP address
function getOwnIPAddress()
{
	exec("dig +short myip.opendns.com @resolver1.opendns.com", $myIP);
	return $myIP[0];
}
//
// END getOwnIPAddress
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getPassword
// Inputs: Int $length (Optional variable, defaults to 15, length of the password to get)
// Returns: A random password of the specified length (maximum 32, default 15)
// Description: Generates a random password by taking the MD5 of a random value
function getPassword($length=15) {
	$quickSet = substr(md5(rand()), 0, $length);
	return $quickSet;
}
//
// END getPassword
///////////////////////////////////////////////////////////////////////////////////////////
 
    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getUserInput
// Inputs: String $prompt   (The string to provide the user as a prompt)
//         Bool $makeUpper  (If true, return the uppercase of the user input, defaults to return exact input)
// Returns: A string containing the user response
// Description: Displays the specified prompt to a user on the console, gets the response, and returns it
function getUserInput($prompt, $makeUpper=FALSE) {
	echo "$prompt ";
	if ($makeUpper) {
		return strtoupper(readline());	
	} else {
		return readline();
	}
}
//
// END getUserInput
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getValidFile
// Inputs: null
// Returns: String containing a filename validated to exist
// Description: Prompts a user for a valid file, confirms its existence, and then passes the result back
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
//
// END getValidFile
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: loadSQLFile
// Inputs: String $dbType               (The database type that should be loaded)
//         CLCSConfiguration $dbConfig  (A CLCSConfiguration object used to establish a database connection)
//         String $sqlFile              (A SQL file to be loaded into the specified database)
// Returns: null
// Description: Loads a SQL file into a database
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
//
// END loadSQLFile
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: loadTargets
// Inputs: Bool $processDirectory       (If true, process all the target files found in the specified directory from the configuration)
//         String $dbType               (The name of the database to connect to)
//         CLCSConfiguration $dbConfig  (A CLCSConfiguration object used to connect to the database server)
// Returns: null
// Description:
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
//
// END loadTargets
///////////////////////////////////////////////////////////////////////////////////////////

    

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: loadWordlist
// Inputs: Bool $processDirectory       (If true, process all the wordlist files found in the specified directory from the configuration)
//         String $dbType               (The name of the database to connect to)
//         CLCSConfiguration $dbConfig  (A CLCSConfiguration object used to connect to the database server)
// Returns: null
// Description: Loads wordlists into the database
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
//
// END loadWordlist
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printHTMLFooter
// Inputs: null
// Returns: null
// Description: Prints a standard table encapsulating footer
function printHTMLFooter() {
	echo "</td></tr>\n";
	echo "<tr><td width=\"100%\"><center>Cryptolingus Cracking Suite (CLCS)</center></td></tr></table>\n";
	echo "</body>\n";
	echo "</html>\n";
}
//
// END printHTMLFooter
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printHTMLHeader
// Inputs: null
// Returns: null
// Description: Prints a standard table encapsulating header
function printHTMLHeader($siteType) {
	echo "<html>\n";
	echo "<head><title>CLCS - " . $siteType . "</title></head>\n";
	echo "<body bgcolor=\"#000\" text=\"#fff\" link=\"#00f\" alink=\"#00f\" vlink=\"#00f\">\n";
	echo "<table width=\"100%\" height=\"100%\">\n";
	echo "<tr><td width=\"100%\"><center><font face=\"Lucida Console, Monaco, monospace\" size=\"+4\"><u>Cryptolingus Cracking Suite</u></font></center></td></tr>\n";
	echo "<tr with=\"100%\" height=\"100%\"><td>";
}
//
// END printHTMLHeader
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: resetCLCSdb
// Inputs: String $dbType               (The database type to reset)
//         CLCSConfiguration $dbConfig  (A CLCSConfiguration object used to connect to the database)
// Returns: null
// Description:
function resetCLCSdb($dbType, $dbConfig) {
	$dbCon = connectTo($dbConfig, $dbType, FALSE, TRUE);
	$configFile = "Config/" . $dbType . ".sql";
	$dbCon->query("DROP DATABASE IF EXISTS $dbType");
	$dbCon->query("CREATE DATABASE $dbType");
	$dbCon->close();
	loadSQLFile($dbType, $dbConfig, $configFile);
}
//
// END resetCLCSdb
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: showError
// Inputs: String $error    (The text to be displayed)
// Returns: null
// Description: Shows a standard formatted error message.
function showError($error="") {
	if (!defined($error)) {
		$error = "ERROR: Invalid input selected.  Please try again.\n";
	}
	echo "ERROR: $error\n";
	sleep(1);
}
//
// END showError
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: sshEncodePublicKey
// Inputs: OpenSSL_pkey $rsaKey    (An OpenSSL pkey object)
// Returns: A string containing the SSH encoded public key of the pkey object
// Description: Shows a standard formatted error message.
//              - Credit to John Snyder (Seoci) for the updated code here
function sshEncodePublicKey($rsaKey) {
    $keyInfo = openssl_pkey_get_details($rsaKey);
    $buffer  = pack("N", 7) . "ssh-rsa" .
        sshEncodeBuffer($keyInfo['rsa']['e']) .
        sshEncodeBuffer($keyInfo['rsa']['n']);
    return "ssh-rsa " . base64_encode($buffer);
}
//
// END sshEncodePublicKey
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: sshEncodeBuffer
// Inputs: String $buffer    (The text to be packed in SSH format)
// Returns: Binary encoded variant of $buffer, appropriately padded
// Description: Encodes the specified buffer after appropriately padding it
//              - Credit to John Snyder (Seoci) for the updated code here
function sshEncodeBuffer($buffer) {
    $len = strlen($buffer);
    // If RSA key n/e values and’ed with 0x80 then pad with a null
    if (ord($buffer[0]) & 0x80) {
        $len++;
        $buffer = "\x00" . $buffer;
    }
    return pack("Na*", $len, $buffer);
}
//
// END sshEncodeBuffer
///////////////////////////////////////////////////////////////////////////////////////////


    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: userAck
// Inputs: null
// Returns: null
// Description: Displays a standard "Press ENTER to continue" message
function userAck() {
	echo "Press ENTER to continue...\n";
	fgetc(STDIN);
}
//
// END userAck
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: writeFile
// Inputs: String $theFile  (The file to be written to)
//         String $theData  (The data to write to the file)
// Returns: null
// Description: Writes data to a specified file
function writeFile($theFile, $theData) {
	$fh = fopen($theFile, 'w');
	fwrite($fh, $theData);
	fclose($fh);
}
//
// END
///////////////////////////////////////////////////////////////////////////////////////////

?>
