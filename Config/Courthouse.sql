-- Host: localhost    Database: Courthouse 
-- ------------------------------------------------------
-- Server version	5.6.19

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Words`
--

DROP TABLE IF EXISTS `Words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Words` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each word',
  `word` VARCHAR(255) NOT NULL COMMENT 'The actual word',
  PRIMARY KEY (`id`),
  UNIQUE (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table of words from wordlists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Nodes`
--

DROP TABLE IF EXISTS `Nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Nodes` (
  `node_id` VARCHAR(255) NOT NULL COMMENT 'UID for each node',
  `has_gpu` BOOL NOT NULL DEFAULT FALSE COMMENT 'Does the node have GPU cracking?',
  `ip_address` VARCHAR(255) NOT NULL COMMENT 'System IP address',
  `add_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date the node was added',
  `last_checkin` DATETIME COMMENT 'Date of last checkin',
  PRIMARY KEY (`node_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table of nodes that perform actual cracking';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for `Jobs`
--

DROP TABLE IF EXISTS `Jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Jobs` (
  `job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each job',
  `word_file` VARCHAR(255) NOT NULL COMMENT 'The word file to use',
  `hash_file` VARCHAR(255) NOT NULL COMMENT 'The hash file to use',
  `node_id` VARCHAR(255) COMMENT 'Cross-reference to node_id in Nodes table',
  `hashtype_id` INT UNSIGNED NOT NULL COMMENT 'Cross-reference to id in Hashtype table',
  `complete` BOOL NOT NULL DEFAULT FALSE COMMENT 'Has the job been completed?',
  PRIMARY KEY (`job_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='List of jobs and assignments';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `Lists`
--

DROP TABLE IF EXISTS `Lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Lists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each list',
  `name` VARCHAR(255) NOT NULL COMMENT 'The name of the wordlist',
  `description` VARCHAR(255) COMMENT 'Description of the wordist',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table of wordlists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Wordlists`
--

DROP TABLE IF EXISTS `Wordlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Wordlists` (
  `list_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'List UID cross reference',
  `word_id` INT UNSIGNED NOT NULL COMMENT 'Word UID cross reference',
  PRIMARY KEY(`list_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Cross reference table of words to lists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Targets`
--


DROP TABLE IF EXISTS `Targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Targets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each target to crack',
  `ishash` BOOL NOT NULL DEFAULT 1 COMMENT 'Flags whether this is a hash or encrypted data',
  `isbenchmark` BOOL NOT NULL DEFAULT 0 COMMENT 'Flags whether this is a truth detector test',
  `hashtype_id` INT NOT NULL COMMENT 'Hashtype UID cross reference',
  `hash_value` VARCHAR(255) NOT NULL COMMENT 'The actual value to be cracked',
  `cleartext_value` VARCHAR(255) COMMENT 'The cleartext recovered',
  `confidence` INT NOT NULL DEFAULT 0 COMMENT 'Confidence rating, 0 - 100',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Targets for cracking';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `Hashtype`
--

DROP TABLE IF EXISTS `Hashtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Hashtype` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each hash type',
  `name` VARCHAR(255) NOT NULL COMMENT 'Human readable name for the hash type',
  `hashcattype_id` INT NOT NULL COMMENT 'Hashcat UID cross reference',
  `johntype_id` INT NOT NULL COMMENT 'John the Ripper UID cross reference',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='List of hashtypes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Insert default hash types
--
LOCK TABLE `Hashtype` WRITE;
INSERT INTO `Hashtype` (`id`, `name`, `johntype_id`, `hashcattype_id`) VALUES
    (1 , 'Application Adobe Portable Document Format MD5 RC4', 62, 0),
    (2 , 'Application Django', 7, 0),
    (3 , 'Application Drupal 7', 14, 0),
    (4 , 'Application EpiServer SID Salted SHA-1', 15, 0),
    (5 , 'Application EpiServer Salted SHA-1 SHA-256', 16, 0),
    (6 , 'Application hMailServer Salted SHA-256', 25, 0),
    (7 , 'Application Invision Powerboard 2.x Salted MD5', 26, 0),
    (8 , 'Application KeePass SHA-256', 27, 0),
    (9 , 'Application LinkedIn Raw SHA-1', 80, 0),
    (10 , 'Application Lotus Notes Domino 5', 32, 0),
    (11 , 'Application Lotus Notes Domino 6', 9, 0),
    (12 , 'Application MediaWiki MD5', 36, 0),
    (13 , 'Application Microsoft Office 2007-2010 SHA-1/AES', 58, 0),
    (14 , 'Application Microsoft SQL Pre-2005', 42, 0),
    (15 , 'Application Microsoft SQL 2005+', 44, 0),
    (16 , 'Application MySQL', 45, 0),
    (17 , 'Application MySQL 4.1 Double SHA-1', 46, 0),
    (18 , 'Application Netscape LDAP SHA-1', 53, 0),
    (19 , 'Application Open Document Format SHA-1 Blowfish', 57, 0),
    (20 , 'Application Oracle 10 DES', 59, 0),
    (21 , 'Application Oracle 11g SHA-1', 60, 0),
    (22 , 'Application osCommerce MD5', 61, 0),
    (23 , 'Application PasswordSafe SHA-256', 69, 0),
    (24 , 'Application PHPass MD5', 63, 0),
    (25 , 'Application PKZIP', 67, 0),
    (26 , 'Application Post.Office MD5', 68, 0),
    (27 , 'Application RAR', 72, 0),
    (28 , 'Application SAP BCODE', 89, 0),
    (29 , 'Application SAP Passcode', 90, 0),
    (30 , 'Application SSH RSA DSA', 96, 0),
    (31 , 'Application Sybase ASE Salted SHA-256', 98, 0),
    (32 , 'Application VNC DES', 100, 0),
    (33 , 'Application WinZip', 107, 0),
    (34 , 'Application WoltLab BB3 Salted SHA-1', 101, 0),
    (35 , 'Blowfish', 1, 0),
    (36 , 'Blowfish Eggdrop', 3, 0),
    (37 , 'CRC-32', 5, 0),
    (38 , 'DES BSDI', 4, 0),
    (39 , 'DES Traditional', 6, 0),
    (40 , 'GOST', 17, 0),
    (41 , 'HDAA HTTP Digest Access Authentication', 18, 0),
    (42 , 'HMAC MD5', 19, 0),
    (43 , 'HMAC SHA-1', 20, 0),
    (44 , 'HMAC SHA-224', 21, 0),
    (45 , 'HMAC SHA-256', 22, 0),
    (46 , 'HMAC SHA-384', 23, 0),
    (47 , 'HMAC SHA-512', 24, 0),
    (48 , 'Kerberos v4 TGT DES', 29, 0),
    (49 , 'Kerberos v5 Microsoft Kerberos', 41, 0),
    (50 , 'Kerberos v5 TGT 3DES', 30, 0),
    (51 , 'MD4 Generic salted MD4', 33, 0),
    (52 , 'MD4 Raw', 73, 0),
    (53 , 'MD5 Digest', 8, 0),
    (54 , 'MD5 Raw', 75, 0),
    (55 , 'MD5 Raw - Unicode', 77, 0),
    (56 , 'MD5 SIP', 95, 0),
    (57 , 'MD5 FreeBSD', 34, 0),
    (58 , 'MD5 Traditional FreeBSD', 4, 0),
    (59 , 'OS Cisco PIX MD5', 66, 0),
    (60 , 'OS Dragonfly BSD SHA-256 32-bit', 10, 0),
    (61 , 'OS Dragonfly BSD SHA-256 64-bit', 11, 0),
    (62 , 'OS Dragonfly BSD SHA-512 32-bit', 12, 0),
    (63 , 'OS Dragonfly BSD SHA-512 64-bit', 13, 0),
    (64 , 'OS IBM RACF DES', 71, 0),
    (65 , 'OS Juniper Netscreen MD5', 35, 0),
    (66 , 'OS Mac OSX Keychain', 28, 0),
    (67 , 'OS Mac OSX 10.4 - 10.6 Salted SHA-1', 104, 0),
    (68 , 'OS Mac OSX 10.7+ Salted SHA-512', 105, 0),
    (69 , 'OS Microsoft Cache Hash', 37, 0),
    (70 , 'OS Microsoft Cache Hash 2', 38, 0),
    (71 , 'OS Microsoft Challenge Handshake Authentication Protocol v2', 40, 0),
    (72 , 'OS Microsoft Half LM DES', 48, 0),
    (73 , 'OS Microsoft LM DES', 31, 0),
    (74 , 'OS Microsoft LM CR DES', 49, 0),
    (75 , 'OS Microsoft LM v2 CR MD4 HMAC-MD5', 50, 0),
    (76 , 'OS Microsoft NTLM v2 CR MD4 HMAC-MD5', 52, 0),
    (77 , 'OS Microsoft Windows NT MD4', 54, 0),
    (78 , 'OS Microsoft Windows NTv2 MD4', 56, 0),
    (79 , 'PHPS', 65, 0),
    (80 , 'SHA-0 Raw', 78, 0),
    (81 , 'SHA-1 Raw', 79, 0),
    (82 , 'SHA-1 Salted', 88, 0),
    (83 , 'SHA-1 Salted Generic', 91, 0),
    (84 , 'SHA-2 SHA-224 Raw', 83, 0),
    (85 , 'SHA-2 SHA-256 Raw', 84, 0),
    (86 , 'SHA-2 SHA-256 Crypt 5000 Rounds', 92, 0),
    (87 , 'SHA-2 SHA-384 Raw', 85, 0),
    (88 , 'SHA-2 SHA-512 Raw', 86, 0),
    (89 , 'SHA-2 SHA-512 Crypt 5000 Rounds', 93, 0),
    (90 , 'Tripcode', 99, 0),
    (91 , 'WPA PSK', 102, 0),
    (9999 , 'None Set', 9999, 9999);	
UNLOCK TABLES;

--
-- Table structure for table `Hashcattype`
--

DROP TABLE IF EXISTS `Hashcattype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Hashcattype` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each hashcat code',
  `cl_type` VARCHAR(255) NOT NULL DEFAULT 'NOTSET' COMMENT 'Command line switch value',
  `description` VARCHAR(255) DEFAULT 'NOTSET' COMMENT 'Description of hash type',
  `gpu_req` BOOL NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Contains hashcat command line information';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Insert default Hashcat entries
--
LOCK TABLE `Hashcattype` WRITE;
INSERT IGNORE INTO `Hashcattype` (`id`, `cl_type`, `description`, `gpu_req`) VALUES
	(9999, 'NOTAPPLICABLE', 'NOTSET', 0),
    (1, 0, 'MD5', 0),
    (2, 10, 'md5($pass.$salt)', 0),
    (3, 20, 'md5($salt.$pass)', 0),
    (4, 30, 'md5(unicode($pass).$salt)', 0),
    (5, 40, 'md5(unicode($pass).$salt)', 0),
    (6, 60, 'HMAC-MD5 (key = $salt)', 0),
    (7, 100, 'SHA1', 0),
    (8, 110, 'sha1($pass.$salt)', 0),
    (9, 120, 'sha1($salt.$pass)', 0),
    (10, 130, 'sha1(unicode($pass).$salt)', 0),
    (11, 140, 'sha1($salt.unicode($pass))', 0),
    (12, 150, 'HMAC-SHA1 (key = $pass)', 0),
    (13, 160, 'HMAC-SHA1 (key = $salt)', 0),
    (14, 200, 'MySQL', 0),
    (15, 300, 'MySQL4.1/MySQL5', 0),
    (16, 400, 'phpass, MD5(Wordpress), MD5(phpBB3)', 0),
    (17, 500, 'md5crypt, MD5(Unix), FreeBSD MD5, Cisco-IOS MD5', 0),
    (18, 800, 'SHA-1(Django)', 0),
    (19, 900, 'MD4', 0),
    (20, 1000, 'NTLM', 0),
    (21, 1100, 'Domain Cached Credentials, mscash', 0),
    (22, 1400, 'SHA256', 0),
    (23, 1410, 'sha256($pass.$salt)', 0),
    (24, 1420, 'sha256($salt.$pass)', 0),
    (25, 1450, 'HMAC-SHA256 (key = $pass)', 0),
    (26, 1460, 'HMAC-SHA256 (key = $salt)', 0),
    (27, 1600, 'md5apr1, MD5(APR), Apache MD5', 0),
    (28, 1700, 'SHA512', 0),
    (29, 1710, 'sha512($pass.$salt)', 0),
    (30, 1720, 'sha512($salt.$pass)', 0),
    (31, 1750, 'HMAC-SHA512 (key = $pass)', 0),
    (32, 1760, 'HMAC-SHA512 (key = $salt)', 0),
    (33, 1800, 'SHA-512(Unix)', 0),
    (34, 2400, 'Cisco-PIX MD5', 0),
    (35, 2500, 'WPA/WPA2', 0),
    (36, 2600, 'Double MD5', 0),
    (37, 3200, 'bcrypt, Blowfish(OpenBSD)', 0),
    (38, 3300, 'MD5(Sun)', 0),
    (39, 3500, 'md5(md5(md5($pass)))', 0),
    (40, 3610, 'md5(md5($salt).$pass)', 0),
    (41, 3710, 'md5($salt.md5($pass))', 0),
    (42, 3720, 'md5($pass.md5($salt))', 0),
    (43, 3810, 'md5($salt.$pass.$salt)', 0),
    (44, 3910, 'md5(md5($pass).md5($salt))', 0),
    (45, 4010, 'md5($salt.md5($salt.$pass))', 0),
    (46, 4110, 'md5($salt.md5($pass.$salt))', 0),
    (47, 4210, 'md5($username.0.$pass)', 0),
    (48, 4300, 'md5(strtoupper(md5($pass)))', 0),
    (49, 4400, 'md5(sha1($pass))', 0),
    (50, 4500, 'sha1(sha1($pass))', 0),
    (51, 4600, 'sha1(sha1(sha1($pass)))', 0),
    (52, 4700, 'sha1(md5($pass))', 0),
    (53, 4800, 'MD5(Chap)', 0),
    (54, 5000, 'SHA-3(Keccak)', 0),
    (55, 5100, 'Half MD5', 0),
    (56, 5200, 'Password Safe SHA-256', 0),
    (57, 5300, 'IKE-PSK MD5', 0),
    (58, 5400, 'IKE-PSK SHA1', 0),
    (59, 5500, 'NetNTLMv1-VANILLA / NetNTLMv1-ESS', 0),
    (60, 5600, 'NetNTLMv2', 0),
    (61, 5700, 'Cisco-IOS SHA256', 0),
    (62, 5800, 'Samsung Android Password/PIN', 0),
    (63, 6300, 'AIX {smd5}', 0),
    (64, 6400, 'AIX {ssha256}', 0),
    (65, 6500, 'AIX {ssha512}', 0),
    (66, 6700, 'AIX {ssha1}', 0),
    (67, 6900, 'GOST, GOST R 34.11-94', 0),
    (68, 7000, 'Fortigate (FortiOS)', 0),
    (69, 7100, 'OS X v10.8', 0),
    (70, 7200, 'GRUB 2', 0),
    (71, 7300, 'IPMI2 RAKP HMAC-SHA1', 0),
    (72, 11, 'Joomla', 0),
    (73, 21, 'osCommerce, xt', 0),
    (74, 101, 'nsldap SHA-1(Base64) Netscape LDAP SHA', 0),
    (75, 111, 'nsldaps SSHA-1(Base64) Netscape LDAP SSHA', 0),
    (76, 112, 'Oracle 11g', 0),
    (77, 121, 'SMF v1.1+', 0),
    (78, 122, 'OS X v10.4 - v10.6', 0),
    (79, 131, 'MSSQL(2000)', 0),
    (80, 132, 'MSSQL(2005)', 0),
    (81, 141, 'EPiServer 6.x', 0),
    (82, 1722, 'OS X v10.7', 0),
    (83, 1731, 'MSSQL(2012)', 0),
    (84, 2611, 'vBulletin < v3.8.5', 0),
    (85, 2711, 'vBulletin > v3.8.5', 0),
    (86, 2811, 'IPB2+, MyBB1.2+', 0),
    (87, 3721, 'WebEdition CMS', 0),
    (6, 50, 'HMAC-MD5 (key = $pass)', 0);
UNLOCK TABLES;


--
-- Table structure for table `Johntype`
--

DROP TABLE IF EXISTS `Johntype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Johntype` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'UID for each John code',
  `cl_type` VARCHAR(255) NOT NULL DEFAULT 'NOTSET' COMMENT 'Command line switch value',
  `description` VARCHAR(255) DEFAULT 'NOTSET' COMMENT 'Description of hash type',
  `gpu_req` BOOL NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Contains John command line information';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Insert default John the Ripper entries
--
LOCK TABLE `Johntype` WRITE;
INSERT INTO `Johntype` (`id`, `cl_type`, `gpu_req`) VALUES
    (9999, 'NOTAPPLICABLE', 0),
    (1, 'bf',0),
    (2, 'bf-opencl',1),
    (3, 'bfegg',0),
    (4, 'bsdi',0),
    (5, 'crc32',0),
    (6, 'des',0),
    (7, 'django',0),
    (8, 'dmd5',0),
    (9, 'dominosec',0),
    (10, 'dragonfly3-32',0),
    (11, 'dragonfly3-64',0),
    (12, 'dragonfly4-32',0),
    (13, 'dragonfly4-64',0),
    (14, 'drupal7',0),
    (15, 'epi',0),
    (16, 'episerver',0),
    (17, 'gost',0),
    (18, 'hdaa',0),
    (19, 'hmac-md5',0),
    (20, 'hmac-sha1',0),
    (21, 'hmac-sha224',0),
    (22, 'hmac-sha256',0),
    (23, 'hmac-sha384',0),
    (24, 'hmac-sha512',0),
    (25, 'hmailserver',0),
    (26, 'ipb2',0),
    (27, 'keepass',0),
    (28, 'keychain',0),
    (29, 'krb4',0),
    (30, 'krb5',0),
    (31, 'lm',0),
    (32, 'lotus5',0),
    (33, 'md4-gen',0),
    (34, 'md5',0),
    (35, 'md5ns',0),
    (36, 'mediawiki',0),
    (37, 'mscash',0),
    (38, 'mscash2',0),
    (39, 'mscash2-opencl',1),
    (40, 'mschapv2',0),
    (41, 'mskrb5',0),
    (42, 'mssql',0),
    (44, 'mssql05',0),
    (45, 'mysql',0),
    (46, 'mysql-sha1',0),
    (48, 'nethalflm',0),
    (49, 'netlm',0),
    (50, 'netlmv2',0),
    (51, 'netntlm',0),
    (52, 'netntlmv2',0),
    (53, 'nsldap',0),
    (54, 'nt',0),
    (55, 'nt-opencl',1),
    (56, 'nt2',0),
    (57, 'odf',0),
    (58, 'office',0),
    (59, 'oracle',0),
    (60, 'oracle11',0),
    (61, 'osc',0),
    (62, 'pdf',0),
    (63, 'phpass',0),
    (64, 'phpass-opencl',1),
    (65, 'phps',0),
    (66, 'pix-md5',0),
    (67, 'pkzip',0),
    (68, 'po',0),
    (69, 'pwsafe',0),
    (70, 'pwsafe-opencl',1),
    (71, 'racf',0),
    (72, 'rar',0),
    (73, 'raw-md4',0),
    (74, 'raw-md4-opencl',1),
    (75, 'raw-md5',0),
    (76, 'raw-md5-opencl',1),
    (77, 'raw-md5u',0),
    (78, 'raw-sha',0),
    (79, 'raw-sha1',0),
    (80, 'raw-sha1-linkedin',0),
    (81, 'raw-sha1-ng',0),
    (82, 'raw-sha1-opencl',1),
    (83, 'raw-sha224',0),
    (84, 'raw-sha256',0),
    (85, 'raw-sha384',0),
    (86, 'raw-sha512',0),
    (87, 'raw-sha512-opencl',1),
    (88, 'salted-sha1',0),
    (89, 'sapb',0),
    (90, 'sapg',0),
    (91, 'sha1-gen',0),
    (92, 'sha256crypt',0),
    (93, 'sha512crypt',0),
    (94, 'sha512crypt-opencl',1),
    (95, 'sip',0),
    (96, 'ssh',0),
    (97, 'ssha-opencl',1),
    (98, 'sybasease',0),
    (99, 'trip',0),
    (100, 'vnc',0),
    (101, 'wbb3',0),
    (102, 'wpapsk',0),
    (103, 'wpapsk-opencl',1),
    (104, 'xsha',0),
    (105, 'xsha512',0),
    (106, 'xsha512-opencl',1),
    (107, 'zip',0);
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
