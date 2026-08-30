-- MySQL dump 10.13  Distrib 8.0.30, for Linux (x86_64)
--
-- Host: <redacted>      Database: clonefrom
-- ------------------------------------------------------
-- Server version	8.0.28

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '084cd5c8-0589-11ed-85ef-cecf5c009eb4:1-1693,
5d8f1a0f-ad86-11ec-bde0-323bd252f440:1-20,
6b492ca4-a806-11ec-b492-2a677a7e602d:1-20,
7c5d6f8e-40b0-11ec-93b9-b6b326df9aa4:1-27';

--
-- Table structure for table `Assignment`
--

DROP TABLE IF EXISTS `Assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Parentid` int NOT NULL DEFAULT '0',
  `Childid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Parentid` (`Parentid`),
  KEY `Childid` (`Childid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Assignment`
--

LOCK TABLES `Assignment` WRITE;
/*!40000 ALTER TABLE `Assignment` DISABLE KEYS */;
/*!40000 ALTER TABLE `Assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AssociatedRecordStats`
--

DROP TABLE IF EXISTS `AssociatedRecordStats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AssociatedRecordStats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `AssociatedRecordCount` int NOT NULL DEFAULT '0',
  `AssociatedWordCount` int NOT NULL DEFAULT '0',
  `AssociatedCharacterCount` int NOT NULL DEFAULT '0',
  `Entryid` int NOT NULL DEFAULT '0',
  `IgnoreParents` varchar(255) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `IgnoreParents` (`IgnoreParents`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AssociatedRecordStats`
--

LOCK TABLES `AssociatedRecordStats` WRITE;
/*!40000 ALTER TABLE `AssociatedRecordStats` DISABLE KEYS */;
/*!40000 ALTER TABLE `AssociatedRecordStats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Association`
--

DROP TABLE IF EXISTS `Association`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Association` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `ChosenEntryid` int NOT NULL DEFAULT '0',
  `Type` varchar(255) NOT NULL DEFAULT '',
  `SubType` varchar(255) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Type` (`Type`),
  KEY `SubType` (`SubType`),
  KEY `Entryid` (`Entryid`),
  KEY `ChosenEntryid` (`ChosenEntryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Association`
--

LOCK TABLES `Association` WRITE;
/*!40000 ALTER TABLE `Association` DISABLE KEYS */;
/*!40000 ALTER TABLE `Association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AvailabilityDateRange`
--

DROP TABLE IF EXISTS `AvailabilityDateRange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AvailabilityDateRange` (
  `id` int NOT NULL AUTO_INCREMENT,
  `AvailabilityStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AvailabilityEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `AvailabilityStart` (`AvailabilityStart`),
  KEY `AvailabilityEnd` (`AvailabilityEnd`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AvailabilityDateRange`
--

LOCK TABLES `AvailabilityDateRange` WRITE;
/*!40000 ALTER TABLE `AvailabilityDateRange` DISABLE KEYS */;
/*!40000 ALTER TABLE `AvailabilityDateRange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ChildRecordStats`
--

DROP TABLE IF EXISTS `ChildRecordStats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChildRecordStats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ChildRecordCount` int NOT NULL DEFAULT '0',
  `ChildWordCount` int NOT NULL DEFAULT '0',
  `ChildCharacterCount` int NOT NULL DEFAULT '0',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChildRecordStats`
--

LOCK TABLES `ChildRecordStats` WRITE;
/*!40000 ALTER TABLE `ChildRecordStats` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChildRecordStats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Comment`
--

DROP TABLE IF EXISTS `Comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `Userid` int NOT NULL DEFAULT '0',
  `Approved` tinyint(1) NOT NULL DEFAULT '0',
  `Rejected` tinyint(1) NOT NULL DEFAULT '0',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `IPAddress` varchar(39) NOT NULL DEFAULT '',
  `Comment` text NOT NULL,
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `Userid` (`Userid`),
  KEY `Approved` (`Approved`),
  KEY `Rejected` (`Rejected`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `LastModificationDate` (`LastModificationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Comment`
--

LOCK TABLES `Comment` WRITE;
/*!40000 ALTER TABLE `Comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `Comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Definition`
--

DROP TABLE IF EXISTS `Definition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Definition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Term` varchar(255) NOT NULL DEFAULT '',
  `AlternateSpelling` varchar(255) NOT NULL DEFAULT '',
  `Pronunciation` varchar(255) NOT NULL DEFAULT '',
  `PartOfSpeech` varchar(255) NOT NULL DEFAULT '',
  `Etymology` varchar(255) NOT NULL DEFAULT '',
  `Definition` text NOT NULL,
  `Entryid` int NOT NULL DEFAULT '0',
  `Dictionaryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Term` (`Term`),
  KEY `Entryid` (`Entryid`),
  KEY `Dictionaryid` (`Dictionaryid`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `LastModificationDate` (`LastModificationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Definition`
--

LOCK TABLES `Definition` WRITE;
/*!40000 ALTER TABLE `Definition` DISABLE KEYS */;
/*!40000 ALTER TABLE `Definition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Description`
--

DROP TABLE IF EXISTS `Description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Description` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Description` varchar(512) NOT NULL DEFAULT '',
  `Source` varchar(512) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Description` (`Description`(255)),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Description`
--

LOCK TABLES `Description` WRITE;
/*!40000 ALTER TABLE `Description` DISABLE KEYS */;
/*!40000 ALTER TABLE `Description` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Entry`
--

DROP TABLE IF EXISTS `Entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Entry` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Publish` tinyint(1) NOT NULL DEFAULT '0',
  `Subtitle` varchar(255) NOT NULL DEFAULT '',
  `ListTitle` varchar(255) NOT NULL DEFAULT '',
  `ListTitleSortKey` varchar(255) NOT NULL DEFAULT '',
  `Code` varchar(255) NOT NULL DEFAULT '',
  `ChildAdjective` varchar(255) NOT NULL DEFAULT '',
  `ChildNoun` varchar(255) NOT NULL DEFAULT '',
  `ChildNounPlural` varchar(255) NOT NULL DEFAULT '',
  `GrandChildAdjective` varchar(255) NOT NULL DEFAULT '',
  `GrandChildNoun` varchar(255) NOT NULL DEFAULT '',
  `GrandChildNounPlural` varchar(255) NOT NULL DEFAULT '',
  `OriginalEntryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Code` (`Code`),
  KEY `LastModificationDate` (`LastModificationDate`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `listtitleindex` (`ListTitle`),
  KEY `listtitlesortkeyindex` (`ListTitleSortKey`),
  KEY `publish` (`Publish`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Entry`
--

LOCK TABLES `Entry` WRITE;
/*!40000 ALTER TABLE `Entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `Entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntryCodeReservation`
--

DROP TABLE IF EXISTS `EntryCodeReservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntryCodeReservation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `Assignmentid` int NOT NULL DEFAULT '0',
  `Code` varchar(510) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Code` (`Code`),
  KEY `Entryid` (`Entryid`),
  KEY `LastModificationDate` (`LastModificationDate`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntryCodeReservation`
--

LOCK TABLES `EntryCodeReservation` WRITE;
/*!40000 ALTER TABLE `EntryCodeReservation` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntryCodeReservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntryPermission`
--

DROP TABLE IF EXISTS `EntryPermission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntryPermission` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `Userid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `Userid` (`Userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntryPermission`
--

LOCK TABLES `EntryPermission` WRITE;
/*!40000 ALTER TABLE `EntryPermission` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntryPermission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntryTranslation`
--

DROP TABLE IF EXISTS `EntryTranslation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntryTranslation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Subtitle` varchar(255) NOT NULL DEFAULT '',
  `ListTitle` varchar(255) NOT NULL DEFAULT '',
  `ListTitleSortKey` varchar(255) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntryTranslation`
--

LOCK TABLES `EntryTranslation` WRITE;
/*!40000 ALTER TABLE `EntryTranslation` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntryTranslation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventDate`
--

DROP TABLE IF EXISTS `EventDate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventDate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `EventDateTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` varchar(255) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Approximate` tinyint(1) NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventDate`
--

LOCK TABLES `EventDate` WRITE;
/*!40000 ALTER TABLE `EventDate` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventDate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Image`
--

DROP TABLE IF EXISTS `Image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Image` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` varchar(1023) NOT NULL DEFAULT '',
  `FileName` varchar(255) NOT NULL DEFAULT '',
  `FileDirectory` varchar(255) NOT NULL DEFAULT '',
  `IconFileName` varchar(255) NOT NULL DEFAULT '',
  `StandardFileName` varchar(255) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `PixelWidth` int NOT NULL DEFAULT '0',
  `PixelHeight` int NOT NULL DEFAULT '0',
  `IconPixelWidth` int NOT NULL DEFAULT '0',
  `IconPixelHeight` int NOT NULL DEFAULT '0',
  `StandardPixelWidth` int NOT NULL DEFAULT '0',
  `StandardPixelHeight` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Image`
--

LOCK TABLES `Image` WRITE;
/*!40000 ALTER TABLE `Image` DISABLE KEYS */;
/*!40000 ALTER TABLE `Image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ImageTranslation`
--

DROP TABLE IF EXISTS `ImageTranslation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ImageTranslation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` varchar(1023) NOT NULL DEFAULT '',
  `FileName` varchar(255) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ImageTranslation`
--

LOCK TABLES `ImageTranslation` WRITE;
/*!40000 ALTER TABLE `ImageTranslation` DISABLE KEYS */;
/*!40000 ALTER TABLE `ImageTranslation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InternalServerError`
--

DROP TABLE IF EXISTS `InternalServerError`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InternalServerError` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Resolved` tinyint(1) NOT NULL DEFAULT '0',
  `ErrorMessage` text NOT NULL,
  `URL` varchar(1024) NOT NULL DEFAULT '',
  `ServerVariable` text NOT NULL,
  `PostVariable` text NOT NULL,
  `GetVariable` text NOT NULL,
  `EnvironmentVariables` text NOT NULL,
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Resolved` (`Resolved`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `LastModificationDate` (`LastModificationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InternalServerError`
--

LOCK TABLES `InternalServerError` WRITE;
/*!40000 ALTER TABLE `InternalServerError` DISABLE KEYS */;
/*!40000 ALTER TABLE `InternalServerError` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InternalServerIssue`
--

DROP TABLE IF EXISTS `InternalServerIssue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InternalServerIssue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IssueType` varchar(512) NOT NULL DEFAULT '',
  `URL` varchar(1024) NOT NULL DEFAULT '',
  `Description` varchar(2048) NOT NULL DEFAULT '',
  `Resolved` tinyint(1) NOT NULL DEFAULT '0',
  `ServerVariable` text NOT NULL,
  `PostVariable` text NOT NULL,
  `GetVariable` text NOT NULL,
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `IssueType` (`IssueType`),
  KEY `Resolved` (`Resolved`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `LastModificationDate` (`LastModificationDate`),
  KEY `URL_index` (`URL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InternalServerIssue`
--

LOCK TABLES `InternalServerIssue` WRITE;
/*!40000 ALTER TABLE `InternalServerIssue` DISABLE KEYS */;
/*!40000 ALTER TABLE `InternalServerIssue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LikeDislike`
--

DROP TABLE IF EXISTS `LikeDislike`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LikeDislike` (
  `id` int NOT NULL AUTO_INCREMENT,
  `LikeOrDislike` tinyint(1) NOT NULL DEFAULT '0',
  `Userid` int NOT NULL DEFAULT '0',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `LikeOrDislike` (`LikeOrDislike`),
  KEY `Userid` (`Userid`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LikeDislike`
--

LOCK TABLES `LikeDislike` WRITE;
/*!40000 ALTER TABLE `LikeDislike` DISABLE KEYS */;
/*!40000 ALTER TABLE `LikeDislike` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Link`
--

DROP TABLE IF EXISTS `Link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Link` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `URL` varchar(255) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Link`
--

LOCK TABLES `Link` WRITE;
/*!40000 ALTER TABLE `Link` DISABLE KEYS */;
/*!40000 ALTER TABLE `Link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LookupList`
--

DROP TABLE IF EXISTS `LookupList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LookupList` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LookupList`
--

LOCK TABLES `LookupList` WRITE;
/*!40000 ALTER TABLE `LookupList` DISABLE KEYS */;
/*!40000 ALTER TABLE `LookupList` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LookupListItem`
--

DROP TABLE IF EXISTS `LookupListItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LookupListItem` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ItemKey` varchar(1023) NOT NULL DEFAULT '',
  `ItemValue` varchar(1023) NOT NULL DEFAULT '',
  `Disabled` tinyint(1) NOT NULL DEFAULT '0',
  `LookupListid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `ItemKey` (`ItemKey`(255)),
  KEY `ItemValue` (`ItemValue`(255)),
  KEY `LookupListid` (`LookupListid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LookupListItem`
--

LOCK TABLES `LookupListItem` WRITE;
/*!40000 ALTER TABLE `LookupListItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `LookupListItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PrimaryHostRecord`
--

DROP TABLE IF EXISTS `PrimaryHostRecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PrimaryHostRecord` (
  `id` int NOT NULL AUTO_INCREMENT,
  `RecordKey` varchar(255) NOT NULL DEFAULT '',
  `RecordValue` varchar(255) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Parentid` (`RecordKey`),
  KEY `Childid` (`RecordValue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PrimaryHostRecord`
--

LOCK TABLES `PrimaryHostRecord` WRITE;
/*!40000 ALTER TABLE `PrimaryHostRecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `PrimaryHostRecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Quote`
--

DROP TABLE IF EXISTS `Quote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Quote` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Quote` varchar(2048) NOT NULL DEFAULT '',
  `Source` varchar(512) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Quote` (`Quote`(255)),
  KEY `Entryid` (`Entryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Quote`
--

LOCK TABLES `Quote` WRITE;
/*!40000 ALTER TABLE `Quote` DISABLE KEYS */;
/*!40000 ALTER TABLE `Quote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RecordChange`
--

DROP TABLE IF EXISTS `RecordChange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RecordChange` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `Userid` int NOT NULL DEFAULT '0',
  `RecordField` varchar(512) NOT NULL DEFAULT '0',
  `Recordid` int NOT NULL DEFAULT '0',
  `RecordType` varchar(512) NOT NULL DEFAULT '0',
  `OldValue` mediumtext NOT NULL,
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `Userid` (`Userid`),
  KEY `RecordField` (`RecordField`),
  KEY `Recordid` (`Recordid`),
  KEY `RecordType` (`RecordType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RecordChange`
--

LOCK TABLES `RecordChange` WRITE;
/*!40000 ALTER TABLE `RecordChange` DISABLE KEYS */;
/*!40000 ALTER TABLE `RecordChange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Suggestion`
--

DROP TABLE IF EXISTS `Suggestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Suggestion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Entryid` int NOT NULL DEFAULT '0',
  `Userid` int NOT NULL DEFAULT '0',
  `SuggestionType` varchar(255) NOT NULL DEFAULT '',
  `Suggestion` varchar(512) NOT NULL DEFAULT '',
  `Explanation` varchar(1024) NOT NULL DEFAULT '',
  `Approved` tinyint(1) NOT NULL DEFAULT '0',
  `Rejected` tinyint(1) NOT NULL DEFAULT '0',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `IPAddress` varchar(39) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `Userid` (`Userid`),
  KEY `Suggestion` (`Suggestion`(255)),
  KEY `SuggestionType` (`SuggestionType`),
  KEY `Rejected` (`Rejected`),
  KEY `Approved` (`Approved`),
  KEY `OriginalCreationDate` (`OriginalCreationDate`),
  KEY `LastModificationDate` (`LastModificationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Suggestion`
--

LOCK TABLES `Suggestion` WRITE;
/*!40000 ALTER TABLE `Suggestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `Suggestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tag`
--

DROP TABLE IF EXISTS `Tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Tag` varchar(255) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  KEY `Tag` (`Tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tag`
--

LOCK TABLES `Tag` WRITE;
/*!40000 ALTER TABLE `Tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TextBody`
--

DROP TABLE IF EXISTS `TextBody`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TextBody` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Text` mediumtext NOT NULL,
  `Source` varchar(512) NOT NULL DEFAULT '',
  `Language` varchar(32) NOT NULL DEFAULT '',
  `WordCount` int NOT NULL DEFAULT '0',
  `CharacterCount` int NOT NULL DEFAULT '0',
  `Entryid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Entryid` (`Entryid`),
  FULLTEXT KEY `Text` (`Text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TextBody`
--

LOCK TABLES `TextBody` WRITE;
/*!40000 ALTER TABLE `TextBody` DISABLE KEYS */;
/*!40000 ALTER TABLE `TextBody` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `User` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) NOT NULL DEFAULT '',
  `Password` binary(32) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `EmailAddress` varchar(255) NOT NULL DEFAULT '',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `User`
--

LOCK TABLES `User` WRITE;
/*!40000 ALTER TABLE `User` DISABLE KEYS */;
INSERT INTO `User` VALUES (1,'holdoffhunger',_binary ')Ll≠æ$o4|lß\ﬁ≈êdn˙\Ê<ê˘V∑\Êƒ∂\◊˛9É','holdoffhunger@gmail.com','0000-00-00 00:00:00','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserAdmin`
--

DROP TABLE IF EXISTS `UserAdmin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserAdmin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Userid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Userid` (`Userid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserAdmin`
--

LOCK TABLES `UserAdmin` WRITE;
/*!40000 ALTER TABLE `UserAdmin` DISABLE KEYS */;
INSERT INTO `UserAdmin` VALUES (1,1,'0000-00-00 00:00:00','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `UserAdmin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserPermission`
--

DROP TABLE IF EXISTS `UserPermission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserPermission` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Usernameid` int NOT NULL DEFAULT '0',
  `PermissionTypeid` int NOT NULL DEFAULT '0',
  `OwnedTable` varchar(255) NOT NULL DEFAULT '',
  `Ownedid` int NOT NULL DEFAULT '0',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Usernameid` (`Usernameid`),
  KEY `PermissionTypeid` (`PermissionTypeid`),
  KEY `OwnedTable` (`OwnedTable`),
  KEY `Ownedid` (`Ownedid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserPermission`
--

LOCK TABLES `UserPermission` WRITE;
/*!40000 ALTER TABLE `UserPermission` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserPermission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserPermissionType`
--

DROP TABLE IF EXISTS `UserPermissionType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserPermissionType` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Permission` enum('View','Edit') NOT NULL DEFAULT 'View',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserPermissionType`
--

LOCK TABLES `UserPermissionType` WRITE;
/*!40000 ALTER TABLE `UserPermissionType` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserPermissionType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserSession`
--

DROP TABLE IF EXISTS `UserSession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserSession` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Userid` int NOT NULL DEFAULT '0',
  `CookieToken` varchar(255) NOT NULL DEFAULT '',
  `LastAccess` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `CookieToken` (`CookieToken`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserSession`
--

LOCK TABLES `UserSession` WRITE;
/*!40000 ALTER TABLE `UserSession` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserSession` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-09-08 16:47:14
