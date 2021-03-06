SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE DATABASE IF NOT EXISTS `nidan` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `nidan`;

DROP TABLE IF EXISTS `Agents`;
CREATE TABLE IF NOT EXISTS `Agents` (
`ID` int(11) NOT NULL,
  `Name` varchar(16) NOT NULL,
  `apiKey` varchar(64) NOT NULL,
  `Description` text NOT NULL,
  `IP` varchar(32) DEFAULT NULL,
  `Hostname` varchar(64) DEFAULT NULL,
  `Version` varchar(16) DEFAULT NULL,
  `Plugins` text,
  `isEnable` tinyint(1) NOT NULL DEFAULT '0',
  `isOnline` tinyint(1) DEFAULT NULL,
  `addDate` datetime NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `stopDate` datetime DEFAULT NULL,
  `lastSeen` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Config`;
CREATE TABLE IF NOT EXISTS `Config` (
  `Name` varchar(16) NOT NULL,
  `Value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `EventsLog`;
CREATE TABLE IF NOT EXISTS `EventsLog` (
`ID` int(11) NOT NULL,
  `addDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jobId` int(11) DEFAULT NULL,
  `Event` varchar(16) NOT NULL,
  `Args` text
) ENGINE=InnoDB AUTO_INCREMENT=3211 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Hosts`;
CREATE TABLE IF NOT EXISTS `Hosts` (
`ID` int(11) NOT NULL,
  `netId` int(11) NOT NULL,
  `agentId` int(11) DEFAULT NULL,
  `IP` varchar(32) NOT NULL,
  `MAC` varchar(64) NOT NULL,
  `Vendor` varchar(64) NOT NULL,
  `Hostname` varchar(64) NOT NULL,
  `Note` text NOT NULL,
  `State` varchar(16) NOT NULL,
  `isOnline` tinyint(1) NOT NULL DEFAULT '0',
  `isIgnore` tinyint(1) NOT NULL DEFAULT '0',
  `lastCheck` datetime DEFAULT NULL,
  `scanTime` mediumint(9) DEFAULT NULL,
  `addDate` datetime NOT NULL,
  `stateChange` datetime DEFAULT NULL,
  `checkCycle` int(6) DEFAULT NULL,
  `chgDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=2069 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Inbox`;
CREATE TABLE IF NOT EXISTS `Inbox` (
`ID` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `Title` text NOT NULL,
  `Content` text NOT NULL,
  `isRead` tinyint(1) NOT NULL,
  `isImportant` tinyint(1) NOT NULL,
  `addDate` datetime NOT NULL,
  `readDate` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=424 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `JobsQueue`;
CREATE TABLE IF NOT EXISTS `JobsQueue` (
`ID` int(11) NOT NULL,
  `Job` varchar(16) NOT NULL,
  `itemId` mediumint(9) NOT NULL,
  `agentId` int(11) NOT NULL,
  `Args` text,
  `Cache` blob,
  `timeElapsed` decimal(10,3) DEFAULT NULL,
  `addDate` datetime NOT NULL,
  `scheduleDate` datetime DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=110223 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Log`;
CREATE TABLE IF NOT EXISTS `Log` (
  `addDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Context` varchar(16) NOT NULL,
  `Message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Networks`;
CREATE TABLE IF NOT EXISTS `Networks` (
`ID` int(11) NOT NULL,
  `Network` varchar(32) NOT NULL,
  `Description` text,
  `Prefs` text,
  `isEnable` tinyint(1) NOT NULL DEFAULT '0',
  `agentId` int(11) NOT NULL DEFAULT '0',
  `scanTime` int(11) DEFAULT NULL,
  `addDate` datetime NOT NULL,
  `chgDate` datetime DEFAULT NULL,
  `lastCheck` datetime DEFAULT NULL,
  `checkCycle` int(6) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Services`;
CREATE TABLE IF NOT EXISTS `Services` (
`ID` int(11) NOT NULL,
  `hostId` int(11) NOT NULL,
  `Port` int(11) NOT NULL,
  `Proto` varchar(3) NOT NULL,
  `State` varchar(16) NOT NULL,
  `Banner` text,
  `isIgnore` tinyint(1) NOT NULL DEFAULT '0',
  `addDate` datetime NOT NULL,
  `lastSeen` datetime DEFAULT NULL,
  `chgDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=361 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `SessionMessages`;
CREATE TABLE IF NOT EXISTS `SessionMessages` (
`ID` int(11) NOT NULL,
  `sessionId` varchar(64) NOT NULL,
  `Type` varchar(16) NOT NULL,
  `Message` text NOT NULL,
  `addDate` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Sessions`;
CREATE TABLE IF NOT EXISTS `Sessions` (
  `ID` varchar(64) NOT NULL,
  `IP` varchar(32) NOT NULL,
  `lastAction` datetime DEFAULT NULL,
  `userId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Snapshots`;
CREATE TABLE IF NOT EXISTS `Snapshots` (
`ID` int(11) NOT NULL,
  `itemId` int(11) NOT NULL,
  `itemType` varchar(16) NOT NULL,
  `itemData` blob NOT NULL,
  `addDate` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=99310 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Triggers`;
CREATE TABLE IF NOT EXISTS `Triggers` (
`ID` int(11) NOT NULL,
  `agentId` int(11) DEFAULT NULL,
  `Event` varchar(16) NOT NULL,
  `Action` varchar(16) NOT NULL,
  `Priority` varchar(16) NOT NULL,
  `Args` text,
  `userId` mediumint(9) NOT NULL,
  `isEnable` tinyint(1) NOT NULL DEFAULT '0',
  `raisedCount` int(11) NOT NULL,
  `lastRaised` datetime DEFAULT NULL,
  `lastProcessed` datetime DEFAULT NULL,
  `addDate` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Users`;
CREATE TABLE IF NOT EXISTS `Users` (
`ID` int(11) NOT NULL,
  `Name` varchar(32) NOT NULL,
  `Password` varchar(64) NOT NULL,
  `eMail` varchar(64) DEFAULT NULL,
  `Alias` varchar(32) NOT NULL,
  `ACL` text NOT NULL,
  `addDate` datetime NOT NULL,
  `lastLogin` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;


ALTER TABLE `Agents`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Config`
 ADD PRIMARY KEY (`Name`);

ALTER TABLE `EventsLog`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Hosts`
 ADD PRIMARY KEY (`ID`), ADD FULLTEXT KEY `Hostname` (`Hostname`,`Note`,`Vendor`);

ALTER TABLE `Inbox`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `JobsQueue`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Networks`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Services`
 ADD PRIMARY KEY (`ID`), ADD FULLTEXT KEY `Banner` (`Banner`);

ALTER TABLE `SessionMessages`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Sessions`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Snapshots`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Triggers`
 ADD PRIMARY KEY (`ID`);

ALTER TABLE `Users`
 ADD PRIMARY KEY (`ID`);


ALTER TABLE `Agents`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
ALTER TABLE `EventsLog`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3211;
ALTER TABLE `Hosts`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2069;
ALTER TABLE `Inbox`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=424;
ALTER TABLE `JobsQueue`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=110223;
ALTER TABLE `Networks`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
ALTER TABLE `Services`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=361;
ALTER TABLE `SessionMessages`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
ALTER TABLE `Snapshots`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=99310;
ALTER TABLE `Triggers`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
ALTER TABLE `Users`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
