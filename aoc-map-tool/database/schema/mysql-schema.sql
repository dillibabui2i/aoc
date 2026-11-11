/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `NeedToHire_Accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) NOT NULL DEFAULT 0,
  `original_aoc_patient_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `NeedToHireAccountsID` int(11) DEFAULT NULL,
  `ClientID` varchar(25) NOT NULL DEFAULT '',
  `MatchInEMR` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedReason` varchar(255) NOT NULL DEFAULT '',
  `RemovalReason` varchar(255) NOT NULL DEFAULT '',
  `ServicesNeeded` varchar(255) NOT NULL DEFAULT '',
  `Recruiter` varchar(255) NOT NULL DEFAULT '',
  `Status` varchar(25) NOT NULL DEFAULT '',
  `IsPriority` tinyint(1) NOT NULL DEFAULT 0,
  `ScheduleText` varchar(255) NOT NULL DEFAULT '',
  `RespiteTerminalPayer` varchar(255) NOT NULL DEFAULT '',
  `TerminalPayer` varchar(255) NOT NULL DEFAULT '',
  `IntakeCoordinator` varchar(8000) NOT NULL DEFAULT '',
  `liftingRequirements` varchar(500) DEFAULT NULL,
  `clinicianDiscipline` varchar(500) DEFAULT NULL,
  `skills` varchar(500) DEFAULT NULL,
  `ClientCareCoordinator` varchar(100) DEFAULT NULL,
  `BD_Rep` varchar(100) DEFAULT NULL,
  `HoursAddedOn` datetime DEFAULT NULL,
  `CreatedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_weekly_followup` datetime DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `weekly_follow_up_attempts` datetime DEFAULT NULL,
  `result` varchar(100) DEFAULT NULL,
  `manager_comments` text DEFAULT NULL,
  `clientIsRelocating` tinyint(1) NOT NULL DEFAULT 0,
  `relocatingAddress1` varchar(100) DEFAULT NULL,
  `relocatingAddress2` varchar(100) DEFAULT NULL,
  `relocatingCity` varchar(50) DEFAULT NULL,
  `relocatingState` varchar(50) DEFAULT NULL,
  `relocatingZipCode` varchar(50) DEFAULT NULL,
  `isClientChangingPayer` tinyint(1) NOT NULL DEFAULT 0,
  `newTerminalPayer` varchar(100) DEFAULT NULL,
  `payerEffectiveDates` date DEFAULT NULL,
  `newRespiteTerminalPayer` varchar(50) DEFAULT NULL,
  `date_media_refused` date DEFAULT NULL,
  `pay_range_rn` varchar(255) DEFAULT NULL,
  `pay_range_lvn` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_NeedToHire_Accounts_IsActive_userid_aocpatientid_id` (`IsActive`,`user_id`,`aoc_patient_id`,`id`),
  KEY `idxr_fk_needtohire_accounts_aoc_patient_id` (`aoc_patient_id`),
  KEY `idxr_needtohire_accounts_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Accounts_Audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Accounts_Audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` int(11) NOT NULL,
  `aoc_patient_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `NeedToHireAccountsID` int(11) DEFAULT NULL,
  `ClientID` varchar(25) NOT NULL DEFAULT '',
  `MatchInEMR` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedReason` varchar(255) NOT NULL DEFAULT '',
  `RemovalReason` varchar(255) NOT NULL DEFAULT '',
  `ServicesNeeded` varchar(255) NOT NULL DEFAULT '',
  `Recruiter` varchar(255) NOT NULL DEFAULT '',
  `Status` varchar(25) NOT NULL DEFAULT '',
  `IsPriority` tinyint(1) NOT NULL DEFAULT 0,
  `ScheduleText` varchar(255) NOT NULL DEFAULT '',
  `RespiteTerminalPayer` varchar(255) NOT NULL DEFAULT '',
  `TerminalPayer` varchar(255) NOT NULL DEFAULT '',
  `IntakeCoordinator` varchar(8000) NOT NULL DEFAULT '',
  `CreatedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `HoursAddedOn` datetime DEFAULT NULL,
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `liftingRequirements` varchar(500) DEFAULT NULL,
  `clinicianDiscipline` varchar(500) DEFAULT NULL,
  `skills` varchar(500) DEFAULT NULL,
  `ClientCareCoordinator` varchar(100) DEFAULT NULL,
  `BD_Rep` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `clientIsRelocating` tinyint(1) NOT NULL DEFAULT 0,
  `relocatingAddress1` varchar(100) DEFAULT NULL,
  `relocatingAddress2` varchar(100) DEFAULT NULL,
  `relocatingCity` varchar(50) DEFAULT NULL,
  `relocatingState` varchar(50) DEFAULT NULL,
  `relocatingZipCode` varchar(50) DEFAULT NULL,
  `isClientChangingPayer` tinyint(1) NOT NULL DEFAULT 0,
  `newTerminalPayer` varchar(100) DEFAULT NULL,
  `payerEffectiveDates` date DEFAULT NULL,
  `newRespiteTerminalPayer` varchar(50) DEFAULT NULL,
  `pay_range_rn` varchar(255) DEFAULT NULL,
  `pay_range_lvn` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `StartDayOfWeek` varchar(25) NOT NULL,
  `StartDayOfWeekInt` tinyint(4) NOT NULL,
  `StartHour` tinyint(4) NOT NULL,
  `StartMinute` tinyint(4) NOT NULL,
  `EndHour` tinyint(4) NOT NULL,
  `EndMinute` tinyint(4) NOT NULL,
  `StartAndEndOnSameDay` tinyint(1) NOT NULL,
  `ShiftType` tinyint(4) NOT NULL,
  `Type` varchar(1) NOT NULL,
  `Reason` varchar(500) NOT NULL,
  `ShiftHours` double(8,2) NOT NULL,
  `ShiftTypeDescription` varchar(100) NOT NULL,
  `ChangeDescription` varchar(100) NOT NULL,
  `HoursAdded` double(8,2) NOT NULL,
  `HoursRemoved` double(8,2) NOT NULL,
  `NetChangeinHours` double(8,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `NeedToHireScheduleID` int(11) DEFAULT NULL,
  `previous` int(11) DEFAULT NULL,
  `prevhours` decimal(10,2) DEFAULT NULL,
  `isNet` bit(1) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_NeedToHire_Audit_schedule_Id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Checklist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mssql_id` bigint(20) NOT NULL,
  `NeedToHireAccountsID` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` bigint(20) NOT NULL DEFAULT 0,
  `CurrentStep` varchar(255) DEFAULT NULL,
  `HUBcity` tinyint(1) NOT NULL DEFAULT 0,
  `HUBcityStarted` datetime DEFAULT NULL,
  `HUBcityWorked` datetime DEFAULT NULL,
  `TextBlast` tinyint(1) NOT NULL DEFAULT 0,
  `TextBlastINT` int(11) DEFAULT NULL,
  `TextBlastAllTime` int(11) NOT NULL DEFAULT 0,
  `TextBlastStarted` datetime DEFAULT NULL,
  `TextBlastWorked` datetime DEFAULT NULL,
  `TextBlastEscalatedRate` double(8,2) DEFAULT NULL,
  `TextBlastEscalatedMargin` decimal(8,2) DEFAULT NULL,
  `TextBlastContent` text DEFAULT NULL,
  `PET` tinyint(1) NOT NULL DEFAULT 0,
  `PETINT` int(11) DEFAULT NULL,
  `PETAllTime` int(11) NOT NULL DEFAULT 0,
  `PETStarted` datetime DEFAULT NULL,
  `PETWorked` datetime DEFAULT NULL,
  `SocialMedia` tinyint(1) NOT NULL DEFAULT 0,
  `SocialMediaINT` int(11) DEFAULT NULL,
  `SocialMediaAllTime` int(11) NOT NULL DEFAULT 0,
  `SocialMediaPostsINT` int(11) NOT NULL DEFAULT 0,
  `SocialMediaPostsAllatime` int(11) NOT NULL DEFAULT 0,
  `SocialMediaStarted` datetime DEFAULT NULL,
  `SocialMediaWorked` datetime DEFAULT NULL,
  `PrivateMessage` tinyint(1) NOT NULL DEFAULT 0,
  `PrivateMessageINT` int(11) DEFAULT NULL,
  `PrivateMessageAllTime` int(11) NOT NULL DEFAULT 0,
  `PrivateMessageStarted` datetime DEFAULT NULL,
  `PrivateMessageWorked` datetime DEFAULT NULL,
  `LinkedIN` tinyint(1) NOT NULL DEFAULT 0,
  `LinkedININT` int(11) DEFAULT NULL,
  `LinkedINAllTime` int(11) NOT NULL DEFAULT 0,
  `LinkedINStarted` datetime DEFAULT NULL,
  `LinkedINWorked` datetime DEFAULT NULL,
  `Indeed` tinyint(1) NOT NULL DEFAULT 0,
  `IndeedINT` int(11) DEFAULT NULL,
  `IndeedAllTime` int(11) NOT NULL DEFAULT 0,
  `IndeedStarted` datetime DEFAULT NULL,
  `IndeedWorked` datetime DEFAULT NULL,
  `ColdCall` tinyint(1) NOT NULL DEFAULT 0,
  `ColdCallINT` int(11) DEFAULT NULL,
  `ColdCallAllTime` int(11) NOT NULL DEFAULT 0,
  `ColdCallStarted` datetime DEFAULT NULL,
  `ColdCallWorked` datetime DEFAULT NULL,
  `SnailMail` tinyint(1) NOT NULL DEFAULT 0,
  `SnailMailINT` int(11) DEFAULT NULL,
  `SnailMailAllTime` int(11) NOT NULL DEFAULT 0,
  `SnailMailStarted` datetime DEFAULT NULL,
  `SnailMailWorked` datetime DEFAULT NULL,
  `BONNursePM` tinyint(1) NOT NULL DEFAULT 0,
  `BONNursePMINT` int(11) DEFAULT NULL,
  `BONNursePMAllTime` int(11) NOT NULL DEFAULT 0,
  `BONNursePMStarted` datetime DEFAULT NULL,
  `BONNursePMWorked` datetime DEFAULT NULL,
  `SocialMediaSponsored` tinyint(1) NOT NULL DEFAULT 0,
  `SocialMediaSponsoredDATE` int(11) DEFAULT NULL,
  `SocialMediaSponsoredAllTime` int(11) NOT NULL DEFAULT 0,
  `SocialMediaSponsoredStarted` datetime DEFAULT NULL,
  `SocialMediaSponsoredWorked` datetime DEFAULT NULL,
  `Escalated` tinyint(1) NOT NULL DEFAULT 0,
  `EscalatedDATE` int(11) DEFAULT NULL,
  `EscalatedAllTime` int(11) NOT NULL DEFAULT 0,
  `EscalatedStarted` datetime DEFAULT NULL,
  `EscalatedWorked` datetime DEFAULT NULL,
  `CreatedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_needtohire_checklist_aoc_nthaccounts_id` (`aoc_nthaccounts_id`),
  KEY `idxr_needtohire_checklist_hubcity` (`HUBcity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Historical`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Historical` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 0,
  `ShiftHours` double(8,2) NOT NULL,
  `CreatedReason` varchar(255) NOT NULL DEFAULT '',
  `RemovalReason` varchar(255) NOT NULL DEFAULT '',
  `nth_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Hub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Hub` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `HubID` int(11) DEFAULT NULL,
  `State` varchar(55) NOT NULL DEFAULT '',
  `ShortState` varchar(2) NOT NULL DEFAULT '',
  `City` varchar(255) NOT NULL DEFAULT '',
  `HUB` varchar(255) NOT NULL DEFAULT '',
  `Branch` varchar(255) NOT NULL DEFAULT '',
  `FacilityID` varchar(255) NOT NULL DEFAULT '',
  `MilesFromHub` double(8,2) DEFAULT NULL,
  `Population` int(11) DEFAULT NULL,
  `Notes` longtext NOT NULL DEFAULT '',
  `CreatedOn` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `TextBlastRadius` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_needtohire_hub_city` (`City`),
  KEY `idxr_needtohire_hub_shortstate` (`ShortState`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_Schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_Schedules` (
  `NeedToHireScheduleID` int(11) NOT NULL,
  `NeedToHireAccountsID` int(11) NOT NULL,
  `aoc_nthaccounts_id` bigint(20) NOT NULL DEFAULT 0,
  `StartDayOfWeek` varchar(25) NOT NULL,
  `StartDayOfWeekInt` tinyint(4) NOT NULL,
  `StartHour` tinyint(4) NOT NULL,
  `StartMinute` tinyint(4) NOT NULL,
  `EndHour` tinyint(4) NOT NULL,
  `EndMinute` tinyint(4) NOT NULL,
  `StartAndEndOnSameDay` tinyint(1) NOT NULL,
  `ShiftType` tinyint(4) NOT NULL,
  `CreatedOn` date NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `ModifiedOn` date NOT NULL,
  `ModifiedBy` int(11) NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `AdditionReason` varchar(500) NOT NULL,
  `ModificationReason` varchar(500) NOT NULL,
  `RemovalReason` varchar(500) NOT NULL,
  `ShiftHours` double(8,2) NOT NULL,
  `ShiftTypeDescription` varchar(100) NOT NULL,
  `location_description` varchar(500) DEFAULT NULL,
  `alternate_address` text DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `AcceptsCoverage` tinyint(1) DEFAULT 0,
  `ScheduleType` tinyint(4) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_NeedToHire_Schedules_aoc_nthaccounts_id` (`aoc_nthaccounts_id`),
  KEY `idxr_NeedToHire_Schedules_isactive` (`IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_ShiftType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_ShiftType` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `NeedToHireShiftTypeID` int(11) DEFAULT NULL,
  `ShiftTypeDescription` varchar(255) NOT NULL DEFAULT '',
  `CreatedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `NeedToHire_ShiftTypeMapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NeedToHire_ShiftTypeMapping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `NeedToHireShiftTypeMappingID` int(11) DEFAULT NULL,
  `StartDayOfWeek` varchar(25) NOT NULL DEFAULT '',
  `StartDayOfWeekInt` int(11) DEFAULT NULL,
  `StartTimeAMPM` varchar(2) NOT NULL DEFAULT '',
  `EndTimeAMPM` varchar(2) NOT NULL DEFAULT '',
  `ShiftType` int(11) DEFAULT NULL,
  `CreatedOn` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModifiedBy` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ReferringProviders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ReferringProviders` (
  `ReferringProviderID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `BillingReferringProviderID` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `NPI_Number` varchar(50) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `CreatedOn` datetime DEFAULT NULL,
  `EMRID` int(11) NOT NULL,
  `ReferringProviderName` varchar(250) DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `is_deleted` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ReferringProviderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserTitles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserTitles` (
  `ail` varchar(32) DEFAULT NULL,
  `UserTitle` varchar(50) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `ip_address` varchar(50) NOT NULL DEFAULT '',
  `page_action` varchar(100) NOT NULL DEFAULT '',
  `log_info` text NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_log_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) NOT NULL,
  `level_1_type` enum('role','user') DEFAULT NULL,
  `level_1_id` bigint(20) unsigned DEFAULT NULL,
  `level_2_type` enum('role','user') DEFAULT NULL,
  `level_2_id` bigint(20) unsigned DEFAULT NULL,
  `level_3_type` enum('role','user') DEFAULT NULL,
  `level_3_id` bigint(20) unsigned DEFAULT NULL,
  `requires_ops_review` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `afterhours_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `afterhours_schedule` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `afterhours_shifts_id` bigint(20) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `misc1` varchar(255) DEFAULT '',
  `misc2` varchar(255) DEFAULT '',
  `misc3` varchar(255) DEFAULT '',
  `notification1` tinyint(4) NOT NULL DEFAULT 0 COMMENT '24 hours notification sent',
  `notification2` tinyint(4) NOT NULL DEFAULT 0 COMMENT '72 hours notification sent',
  `notification3` tinyint(4) NOT NULL DEFAULT 0,
  `notification4` tinyint(4) NOT NULL DEFAULT 0,
  `notification5` tinyint(4) DEFAULT 0 COMMENT 'Before 1 hour',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `afterhours_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `afterhours_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `roles_id` text DEFAULT NULL,
  `branchid` text DEFAULT NULL,
  `color_filled` varchar(6) DEFAULT NULL,
  `color_open` varchar(6) DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `repeat` varchar(255) DEFAULT NULL,
  `repeat_on` varchar(255) DEFAULT NULL,
  `send_notification` tinyint(4) DEFAULT 0,
  `send_notification_duration` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aoc_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aoc_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracker_id` varchar(255) DEFAULT NULL,
  `entity_id` varchar(200) DEFAULT NULL,
  `evaluation_id` int(11) DEFAULT NULL,
  `associate_id` int(11) DEFAULT NULL COMMENT 'field for any third table relation will be used along with document_type',
  `entity_type` varchar(200) DEFAULT NULL,
  `document_type` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `document_id` text DEFAULT NULL,
  `document_filename` text DEFAULT NULL,
  `document_link` longtext DEFAULT NULL,
  `document_url` text DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `extra_data` text DEFAULT NULL,
  `send_date` datetime DEFAULT NULL,
  `signed_date` datetime DEFAULT NULL,
  `reminder_date` date DEFAULT NULL,
  `reminder_count` int(11) DEFAULT NULL,
  `status` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sending_request_id` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aoc_documents_document_id` (`document_id`(1024)),
  KEY `idxr_aoc_documents_document_type` (`document_type`(1024)),
  KEY `idxr_aoc_documents_evaluation_id_entity_type` (`evaluation_id`,`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aoc_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aoc_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `event_id` text NOT NULL,
  `event_status` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `auth_id` int(11) DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `payer_id` int(11) DEFAULT NULL,
  `number` varchar(200) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `is_visitbased` tinyint(1) DEFAULT NULL,
  `totalunits` double(7,2) DEFAULT NULL,
  `visit_count` int(11) DEFAULT NULL,
  `service_code_id` int(11) DEFAULT NULL,
  `discipline_name` varchar(255) DEFAULT NULL,
  `unusedunits` double(7,2) DEFAULT NULL,
  `weekly_limit` double(7,2) DEFAULT NULL,
  `monthly_limit` double(7,2) DEFAULT NULL,
  `daily_limit` double(7,2) DEFAULT NULL,
  `is_auto` tinyint(1) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `payer_allocationorder` varchar(200) DEFAULT NULL,
  `is_hourlybased` tinyint(1) DEFAULT NULL,
  `sf_created_at` timestamp NULL DEFAULT NULL,
  `sf_updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `is_respite` tinyint(4) NOT NULL DEFAULT 0,
  `is_invalid` tinyint(4) DEFAULT 0,
  `PatientID` int(11) DEFAULT NULL,
  `auth_entered_by` int(11) DEFAULT NULL,
  `auth_requested_by` int(11) DEFAULT NULL,
  `auth_ack_by` int(11) DEFAULT NULL,
  `auth_received_date` date DEFAULT NULL,
  `auth_created_date` date DEFAULT NULL,
  `auth_requested_date` date DEFAULT NULL,
  `auth_ack_date` date DEFAULT NULL,
  `priority_type` varchar(10) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fk_authorizations_aoc_patient_id` (`aoc_patient_id`),
  KEY `idxr_authorizations_auth_id` (`auth_id`),
  KEY `idxr_authorizations_patient_payer_start_end_isrespite_isinvalid` (`PatientID`,`payer_id`,`start_date`,`end_date`,`is_respite`,`is_invalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authorizations_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorizations_service` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_internal_id` int(11) DEFAULT NULL,
  `payment_service_id` int(11) DEFAULT NULL,
  `authorization_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `auth_service_unique_id` varchar(1000) DEFAULT NULL,
  `auth_service_unique_id_old` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_authorizations_service_AUTH_SERVICE_UNIQUE_ID` (`auth_service_unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_inquery_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_inquery_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `note_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `payer_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `addedby_user_id` int(11) DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_billing_inquery_notes_note_id` (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch` varchar(100) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `street_address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zipcode` varchar(25) DEFAULT NULL,
  `IPEA_instructions` text DEFAULT NULL,
  `branch_email` varchar(255) DEFAULT NULL,
  `email_subject` text DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `lat` varchar(25) DEFAULT NULL,
  `lng` varchar(25) DEFAULT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-No,1-Yes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_ignored` tinyint(4) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_logs_8x8`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_logs_8x8` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `callId` varchar(255) DEFAULT NULL,
  `legId` int(11) DEFAULT NULL,
  `startTimeUTC` bigint(20) DEFAULT NULL,
  `startTime` datetime DEFAULT NULL,
  `connectTimeUTC` bigint(20) DEFAULT NULL,
  `connectTime` datetime DEFAULT NULL,
  `disconnectedTimeUTC` bigint(20) DEFAULT NULL,
  `disconnectedTime` datetime DEFAULT NULL,
  `talkTimeMS` bigint(20) DEFAULT NULL,
  `talkTime` varchar(255) DEFAULT NULL,
  `caller` varchar(255) DEFAULT NULL,
  `callerName` varchar(255) DEFAULT NULL,
  `callee` varchar(255) DEFAULT NULL,
  `calleeName` varchar(255) DEFAULT NULL,
  `lra` varchar(255) DEFAULT NULL,
  `direction` varchar(255) DEFAULT NULL,
  `parentCallId` varchar(255) DEFAULT NULL,
  `transferToCallId` varchar(255) DEFAULT NULL,
  `dnis` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `callerDeviceId` varchar(255) DEFAULT NULL,
  `calleeDeviceId` varchar(255) DEFAULT NULL,
  `callerDeviceModel` varchar(255) DEFAULT NULL,
  `calleeDeviceModel` varchar(255) DEFAULT NULL,
  `callerId` varchar(255) DEFAULT NULL,
  `missed` varchar(255) DEFAULT NULL,
  `abandoned` varchar(255) DEFAULT NULL,
  `answered` varchar(255) DEFAULT NULL,
  `cause` varchar(255) DEFAULT NULL,
  `callerSvcName` varchar(255) DEFAULT NULL,
  `callerSvcType` varchar(255) DEFAULT NULL,
  `calleeSvcName` varchar(255) DEFAULT NULL,
  `calleeSvcType` varchar(255) DEFAULT NULL,
  `lraType` int(11) DEFAULT NULL,
  `calleeHoldDurationMS` bigint(20) DEFAULT NULL,
  `calleeHoldDuration` varchar(255) DEFAULT NULL,
  `calleeDisconnectOnHold` varchar(255) DEFAULT NULL,
  `callerDisconnectOnHold` varchar(255) DEFAULT NULL,
  `pbxId` varchar(255) DEFAULT NULL,
  `sipCallId` varchar(255) DEFAULT NULL,
  `departments` text DEFAULT NULL,
  `branches` text DEFAULT NULL,
  `recordServiceOn` datetime DEFAULT NULL,
  `bargeServiceOn` datetime DEFAULT NULL,
  `masterSlaveExts` varchar(255) DEFAULT NULL,
  `propsLastPartyDisp` varchar(255) DEFAULT NULL,
  `accountCode` varchar(255) DEFAULT NULL,
  `aaPath` varchar(255) DEFAULT NULL,
  `callTime` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_application_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_application_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_state` varchar(100) NOT NULL,
  `subSection` varchar(255) NOT NULL,
  `status_keyword` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `conversion_id` int(11) NOT NULL,
  `sortIndex` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_application_states_03262025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_application_states_03262025` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_state` varchar(100) NOT NULL,
  `subSection` varchar(255) NOT NULL,
  `status_keyword` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `conversion_id` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT 0,
  `candidate_id` int(11) DEFAULT NULL,
  `consentStatus` varchar(100) NOT NULL,
  `customField` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `eId` varchar(20) NOT NULL,
  `application_date` date NOT NULL,
  `gender` varchar(50) NOT NULL,
  `hasArtifacts` tinyint(1) NOT NULL,
  `jobviteChannel` varchar(100) NOT NULL,
  `lastUpdatedDate` datetime NOT NULL,
  `personalDataProcessingStatus` varchar(100) NOT NULL,
  `race` varchar(100) NOT NULL,
  `resumeContent` text NOT NULL,
  `resumeFormat` varchar(100) NOT NULL,
  `resumeName` varchar(255) NOT NULL,
  `sentDate` datetime NOT NULL,
  `source` varchar(255) NOT NULL,
  `sourceType` varchar(255) NOT NULL,
  `startDate` datetime NOT NULL,
  `veteranStatus` varchar(100) NOT NULL,
  `workflowState` varchar(255) NOT NULL,
  `workFlowStateId` varchar(50) NOT NULL,
  `job` longtext NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `hireDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_trackable` int(11) NOT NULL DEFAULT 0,
  `is_complete` int(11) NOT NULL DEFAULT 0,
  `next_app_id` int(11) NOT NULL DEFAULT 0,
  `latest_tracker_id` int(11) DEFAULT NULL,
  `job_eId` varchar(10) DEFAULT '',
  `conversion_state` int(11) NOT NULL DEFAULT 1,
  `WeekStart` varchar(20) NOT NULL,
  `tmpOrigState` int(11) NOT NULL,
  `tmpLatestApp` int(11) NOT NULL,
  `next_app_date` date DEFAULT NULL,
  `tmpLastNote` varchar(1500) DEFAULT NULL,
  `isPRN` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_candidate_applications_latest_tracker_id` (`latest_tracker_id`),
  KEY `idxr_candidate_applications_application_date` (`application_date`),
  KEY `idxr_candidate_applications_candidate_id_id` (`candidate_id`,`id`),
  KEY `idxr_candidate_applications_eId` (`eId`),
  KEY `idxr_candidate_applications_job_id` (`job_id`),
  KEY `idxr_candidate_applications_source_sourcetype` (`source`,`sourceType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_applications_03112025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_applications_03112025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT 0,
  `candidate_id` int(11) DEFAULT NULL,
  `consentStatus` varchar(100) NOT NULL,
  `customField` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `eId` varchar(20) NOT NULL,
  `application_date` date NOT NULL,
  `gender` varchar(50) NOT NULL,
  `hasArtifacts` tinyint(1) NOT NULL,
  `jobviteChannel` varchar(100) NOT NULL,
  `lastUpdatedDate` datetime NOT NULL,
  `personalDataProcessingStatus` varchar(100) NOT NULL,
  `race` varchar(100) NOT NULL,
  `resumeContent` text NOT NULL,
  `resumeFormat` varchar(100) NOT NULL,
  `resumeName` varchar(255) NOT NULL,
  `sentDate` datetime NOT NULL,
  `source` varchar(255) NOT NULL,
  `sourceType` varchar(255) NOT NULL,
  `startDate` datetime NOT NULL,
  `veteranStatus` varchar(100) NOT NULL,
  `workflowState` varchar(255) NOT NULL,
  `workFlowStateId` varchar(50) NOT NULL,
  `job` longtext NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `hireDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `job_eId` varchar(10) DEFAULT '',
  `conversion_state` int(11) NOT NULL DEFAULT 1,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_applications_03202025_Nik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_applications_03202025_Nik` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT 0,
  `candidate_id` int(11) DEFAULT NULL,
  `consentStatus` varchar(100) NOT NULL,
  `customField` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `eId` varchar(20) NOT NULL,
  `application_date` date NOT NULL,
  `gender` varchar(50) NOT NULL,
  `hasArtifacts` tinyint(1) NOT NULL,
  `jobviteChannel` varchar(100) NOT NULL,
  `lastUpdatedDate` datetime NOT NULL,
  `personalDataProcessingStatus` varchar(100) NOT NULL,
  `race` varchar(100) NOT NULL,
  `resumeContent` text NOT NULL,
  `resumeFormat` varchar(100) NOT NULL,
  `resumeName` varchar(255) NOT NULL,
  `sentDate` datetime NOT NULL,
  `source` varchar(255) NOT NULL,
  `sourceType` varchar(255) NOT NULL,
  `startDate` datetime NOT NULL,
  `veteranStatus` varchar(100) NOT NULL,
  `workflowState` varchar(255) NOT NULL,
  `workFlowStateId` varchar(50) NOT NULL,
  `job` longtext NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `hireDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `job_eId` varchar(10) DEFAULT '',
  `conversion_state` int(11) NOT NULL DEFAULT 1,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_applications_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_applications_tmp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT 0,
  `candidate_id` int(11) DEFAULT NULL,
  `consentStatus` varchar(100) NOT NULL,
  `customField` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `eId` varchar(20) NOT NULL,
  `application_date` date NOT NULL,
  `gender` varchar(50) NOT NULL,
  `hasArtifacts` tinyint(1) NOT NULL,
  `jobviteChannel` varchar(100) NOT NULL,
  `lastUpdatedDate` datetime NOT NULL,
  `personalDataProcessingStatus` varchar(100) NOT NULL,
  `race` varchar(100) NOT NULL,
  `resumeContent` text NOT NULL,
  `resumeFormat` varchar(100) NOT NULL,
  `resumeName` varchar(255) NOT NULL,
  `sentDate` datetime NOT NULL,
  `source` varchar(255) NOT NULL,
  `sourceType` varchar(255) NOT NULL,
  `startDate` datetime NOT NULL,
  `veteranStatus` varchar(100) NOT NULL,
  `workflowState` varchar(255) NOT NULL,
  `workFlowStateId` varchar(50) NOT NULL,
  `job` longtext NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `hireDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_trackable` int(11) NOT NULL DEFAULT 0,
  `next_app_id` int(11) NOT NULL DEFAULT 0,
  `job_eId` varchar(10) DEFAULT '',
  `conversion_state` int(11) NOT NULL DEFAULT 1,
  `tmpMonday` varchar(20) NOT NULL,
  `tmpOrigState` int(11) NOT NULL,
  `tmpLatestApp` int(11) NOT NULL,
  `tmpNextApplication` date DEFAULT NULL,
  `tmpLastNote` varchar(1500) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_clinical_interview_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_clinical_interview_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `interview` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_screenings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_screenings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint(20) unsigned NOT NULL,
  `recruiter_id` bigint(20) unsigned NOT NULL,
  `licensing` tinyint(3) unsigned NOT NULL,
  `workload` tinyint(3) unsigned NOT NULL,
  `visits` smallint(5) unsigned NOT NULL,
  `pay_expectations` decimal(8,2) unsigned NOT NULL,
  `bilingual` tinyint(1) NOT NULL,
  `supervise` tinyint(1) NOT NULL,
  `qualified` tinyint(1) NOT NULL,
  `start_date` date NOT NULL,
  `experience` text NOT NULL,
  `areas_wishing_to_cover` text NOT NULL,
  `strengths` text NOT NULL,
  `areas_of_growth` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_temp_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_temp_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(100) DEFAULT '' COMMENT 'Module Or Table Name',
  `entity_id` int(11) DEFAULT NULL COMMENT 'Table Primary ID for Ref',
  `notes` text DEFAULT '',
  `type` varchar(100) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `misc1` varchar(255) DEFAULT '',
  `misc4` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_temp_notes2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_temp_notes2` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(100) DEFAULT '' COMMENT 'Module Or Table Name',
  `entity_id` int(11) DEFAULT NULL COMMENT 'Table Primary ID for Ref',
  `notes` text DEFAULT '',
  `type` varchar(100) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `misc1` varchar(255) DEFAULT '',
  `misc4` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `eId` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT '',
  `jobvite_email` varchar(250) NOT NULL,
  `companyName` varchar(255) DEFAULT '',
  `firstName` varchar(255) DEFAULT '',
  `lastName` varchar(255) DEFAULT '',
  `fullName` varchar(255) DEFAULT NULL,
  `Search_Name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `address2` varchar(255) DEFAULT '',
  `countryName` varchar(50) DEFAULT '',
  `country` varchar(10) DEFAULT '',
  `countryCode` varchar(10) DEFAULT '',
  `stateName` varchar(50) DEFAULT '',
  `stateCode` varchar(10) DEFAULT '',
  `state` varchar(10) DEFAULT '',
  `city` varchar(50) DEFAULT '',
  `homePhone` varchar(20) DEFAULT '',
  `location` varchar(255) DEFAULT '',
  `mobile` varchar(20) DEFAULT '',
  `postalCode` varchar(10) DEFAULT '',
  `aoc_cf_city` varchar(255) DEFAULT NULL,
  `aoc_cf_state` varchar(255) DEFAULT NULL,
  `aoc_cf_mile_radius` varchar(255) DEFAULT NULL,
  `aoc_cf_schedule` text DEFAULT NULL,
  `aoc_cf_environment` text DEFAULT NULL,
  `aoc_cf_lifting_requirements` text DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_language` varchar(255) DEFAULT NULL,
  `aoc_cf_default_order` varchar(255) DEFAULT NULL,
  `workPhone` varchar(20) DEFAULT '',
  `workStatus` varchar(20) DEFAULT '',
  `application` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `consentStatus` varchar(100) NOT NULL,
  `disposition` varchar(100) NOT NULL,
  `appId` varchar(50) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `hasArtifacts` tinyint(1) NOT NULL,
  `hireDate` datetime NOT NULL,
  `jobCategoryName` varchar(255) NOT NULL,
  `jobDepartment` varchar(255) NOT NULL,
  `jobType` varchar(255) NOT NULL,
  `jobId` varchar(50) NOT NULL,
  `jobLocation` varchar(255) NOT NULL,
  `jobBranch` varchar(100) NOT NULL,
  `jobPostingType` varchar(100) NOT NULL,
  `jobPrimaryRecruiter` varchar(255) NOT NULL,
  `jobPrimaryRecruiterJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `jobRecruiters` text NOT NULL,
  `jobRecruitersJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `jobRequisitionId` varchar(100) NOT NULL,
  `jobTitle` varchar(500) NOT NULL,
  `appJobviteChannel` varchar(255) NOT NULL,
  `appLastUpdatedDate` datetime NOT NULL,
  `appPersonalDataProcessingStatus` varchar(100) NOT NULL,
  `appRace` varchar(100) NOT NULL,
  `appResumeContent` text NOT NULL,
  `appResumeFormat` varchar(100) NOT NULL,
  `appResumeName` varchar(255) NOT NULL,
  `appSentDate` datetime NOT NULL,
  `appSource` varchar(255) NOT NULL,
  `appSourceType` varchar(255) NOT NULL,
  `appStartDate` datetime NOT NULL,
  `appVeteranStatus` varchar(100) NOT NULL,
  `appWorkflowState` varchar(255) NOT NULL,
  `appWorkFlowStateId` varchar(50) NOT NULL,
  `bilingual` tinyint(1) NOT NULL,
  `languages` varchar(255) NOT NULL,
  `skillset` varchar(50) NOT NULL,
  `application_date` date DEFAULT NULL,
  `workFlowStateChanged` date NOT NULL,
  `comments` text DEFAULT NULL,
  `workFlowStateChangeDate` datetime DEFAULT NULL,
  `recruiterPhoneScreenDate` datetime DEFAULT NULL,
  `outlookEventId` text DEFAULT NULL,
  `outlookEventOwnerId` bigint(20) unsigned DEFAULT NULL,
  `outlookEventICalUId` varchar(255) DEFAULT NULL,
  `scheduleInterviewDate` datetime DEFAULT NULL,
  `rescheduleInterviewDate` datetime DEFAULT NULL,
  `clinicalInterviewDate` datetime DEFAULT NULL,
  `offerExtendedDate` datetime DEFAULT NULL,
  `offerAcceptedDate` datetime DEFAULT NULL,
  `PET1Date` datetime DEFAULT NULL,
  `PET2Date` datetime DEFAULT NULL,
  `PET3Date` datetime DEFAULT NULL,
  `IPEAScheduledDate` datetime DEFAULT NULL,
  `nursingSchoolGradDate` datetime DEFAULT NULL,
  `addedToPotentialNurseDate` datetime DEFAULT NULL,
  `offerRejectedDate` datetime DEFAULT NULL,
  `rejectedByAgencyDate` datetime DEFAULT NULL,
  `appComments` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `syncToJobVite` tinyint(1) NOT NULL DEFAULT 0,
  `updatedToJobVite` datetime DEFAULT NULL,
  `FollowUpDateTime` datetime DEFAULT NULL,
  `Client` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `Pay_Rate` varchar(255) DEFAULT NULL,
  `GP` double(7,5) DEFAULT NULL,
  `OfferRejectedReason` varchar(500) DEFAULT NULL,
  `RejectedByAgencyReason` text DEFAULT NULL,
  `GraduationDate` date DEFAULT NULL,
  `outcomeNotes` text DEFAULT NULL,
  `donothireDate` datetime DEFAULT NULL,
  `donothireReason` varchar(255) DEFAULT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `lng` varchar(255) DEFAULT NULL,
  `is_updated` tinyint(4) DEFAULT NULL,
  `needs_geocoding` tinyint(4) DEFAULT NULL,
  `is_ignored` tinyint(4) DEFAULT NULL,
  `on_tracker` tinyint(1) NOT NULL DEFAULT 0,
  `discipline` varchar(255) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `candidate_type` varchar(255) DEFAULT NULL,
  `Agreed_Pay_Rate` double(5,2) DEFAULT NULL,
  `ipeaschedulehours` varchar(255) DEFAULT NULL,
  `ipeadays` varchar(255) DEFAULT NULL,
  `tmpAppDates` varchar(1000) NOT NULL,
  `tmpAppSteps` varchar(5000) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `interviewManager` bigint(20) unsigned DEFAULT NULL,
  `aoc_cf_previous_employer` varchar(255) DEFAULT NULL,
  `aoc_cf_current_employer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_candidates_needs_geocoding` (`needs_geocoding`),
  KEY `idxr_candidates_fullname` (`fullName`),
  KEY `idx_candidates_app_source_type_source` (`appSourceType`,`appSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `care_coordination_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `care_coordination_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `care_coordinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `care_coordinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `care_coordination_type_id` bigint(20) unsigned NOT NULL,
  `assigned_to_id` bigint(20) unsigned DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `care_coordinations_encounter_id_foreign` (`encounter_id`),
  KEY `care_coordinations_care_coordination_type_id_foreign` (`care_coordination_type_id`),
  KEY `care_coordinations_assigned_to_id_foreign` (`assigned_to_id`),
  KEY `care_coordinations_due_at_index` (`due_at`),
  CONSTRAINT `care_coordinations_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`),
  CONSTRAINT `care_coordinations_care_coordination_type_id_foreign` FOREIGN KEY (`care_coordination_type_id`) REFERENCES `care_coordination_types` (`id`),
  CONSTRAINT `care_coordinations_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ccc_followup_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ccc_followup_calls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccount_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `base_date` date DEFAULT NULL,
  `ccc_id` int(11) DEFAULT NULL,
  `last_weekly_followup` datetime DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `weekly_follow_up_attempts` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `outcome_json` varchar(255) DEFAULT NULL,
  `IsPriority` int(11) NOT NULL DEFAULT 0,
  `week_month_start` date DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ccc_followup_calls_apid_type_wms_outc_ac` (`aoc_patient_id`,`type`,`week_month_start`,`outcome`,`attempt_count`),
  KEY `idx_fk_ccc_followup_calls_aoc_patient_id` (`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cdc_vaccine_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cdc_vaccine_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_name` varchar(255) DEFAULT NULL,
  `cdc_name` varchar(255) DEFAULT NULL,
  `cdc_short_description` varchar(255) DEFAULT NULL,
  `cvx_group_code` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cdc_vaccine_groups_cvx_group_code_unique` (`cvx_group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cdc_vaccines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cdc_vaccines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cdc_full_name` varchar(255) DEFAULT NULL,
  `cdc_short_description` varchar(255) DEFAULT NULL,
  `cdc_status` varchar(64) DEFAULT NULL,
  `cvx_code` smallint(5) unsigned NOT NULL,
  `cvx_group_code` smallint(5) unsigned DEFAULT NULL,
  `cdc_last_updated` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cdc_vaccines_cvx_code_unique` (`cvx_code`),
  KEY `fk_cdc_vaccines_cvx_group_code` (`cvx_group_code`),
  CONSTRAINT `fk_cdc_vaccines_cvx_group_code` FOREIGN KEY (`cvx_group_code`) REFERENCES `cdc_vaccine_groups` (`cvx_group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claim_adjustment_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_adjustment_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_master_id` bigint(20) unsigned NOT NULL,
  `assignee_type` enum('role','user') NOT NULL,
  `assignee_id` bigint(20) unsigned NOT NULL,
  `stage` enum('level_1','level_2','level_3','operations','ar_supervisor') NOT NULL,
  `event_type` enum('assigned','approved','rejected','completed') NOT NULL,
  `acted_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claim_adjustment_histories_claim_master_id_index` (`claim_master_id`),
  KEY `claim_adjustment_histories_claim_master_id_created_at_index` (`claim_master_id`,`created_at`),
  KEY `claim_adjustment_histories_claim_master_id_event_type_index` (`claim_master_id`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claim_denials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_denials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `denial_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `paid_date` timestamp NULL DEFAULT NULL,
  `paidby_user_id` int(11) DEFAULT NULL,
  `createdby_user_id` int(11) DEFAULT NULL,
  `rejection_date` timestamp NULL DEFAULT NULL,
  `eob_date` timestamp NULL DEFAULT NULL,
  `lockedmonth_date` timestamp NULL DEFAULT NULL,
  `baddebt_notes` text DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `system_date` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_date` timestamp NULL DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_claim_denials_claim_id` (`claim_id`),
  KEY `idxr_claim_denials_denial_id` (`denial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claim_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_tag` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint(20) unsigned NOT NULL,
  `claim_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `claim_tag_tag_id_claim_id_unique` (`tag_id`,`claim_id`),
  CONSTRAINT `claim_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims_adjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_adjustment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `claim_detail_id` int(11) DEFAULT NULL,
  `amount` double(8,4) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `type` text DEFAULT NULL,
  `lockedmonth_date` timestamp NULL DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT NULL,
  `adjustment_date` timestamp NULL DEFAULT NULL,
  `account_type` text DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `createdon` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_claims_adjustment_adjustment_id` (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `claim_detail_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `schedule_date` timestamp NULL DEFAULT NULL,
  `units` double(8,4) DEFAULT NULL,
  `billed_amount` double(8,4) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `item_type` text DEFAULT NULL,
  `service_code_id` int(11) DEFAULT NULL,
  `billrate` double(8,4) DEFAULT NULL,
  `is_billrate_hourly` tinyint(1) DEFAULT NULL,
  `is_unitbased_billing` tinyint(1) DEFAULT NULL,
  `contractual_amount` double(8,4) DEFAULT NULL,
  `contractual_rate` double(8,4) DEFAULT NULL,
  `child_schedule_id` int(11) DEFAULT NULL,
  `billrate_hourly_contractual` tinyint(1) DEFAULT NULL,
  `is_contractual_unitbased_billing` tinyint(1) DEFAULT NULL,
  `is_denied` tinyint(1) DEFAULT NULL,
  `total_hours` double(8,4) DEFAULT NULL,
  `misc_visit_id` int(11) DEFAULT NULL,
  `paidamount` double(8,4) DEFAULT NULL,
  `requifee_amount` double(8,4) DEFAULT NULL,
  `is_merged` tinyint(1) DEFAULT NULL,
  `mergedclaim_detail_id` int(11) DEFAULT NULL,
  `hcpcs` text DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `is_additional_lineitem` tinyint(1) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_month` date DEFAULT NULL,
  `system_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_claims_detail_claim_detail_id` (`claim_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `agency_branch_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `payersource_id` int(11) DEFAULT NULL,
  `episode_id` int(11) DEFAULT NULL,
  `type` text DEFAULT NULL,
  `invoice_date` timestamp NULL DEFAULT NULL,
  `invoice_number` text DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `totalamount` decimal(14,4) DEFAULT 0.0000,
  `contractual_amount` decimal(14,4) DEFAULT 0.0000,
  `balance` decimal(14,4) DEFAULT 0.0000,
  `balance_contractual` decimal(14,4) DEFAULT 0.0000,
  `is_cancelled` tinyint(1) DEFAULT 0,
  `is_fully_paid` tinyint(1) DEFAULT 0,
  `is_partially_paid` tinyint(1) DEFAULT 0,
  `is_appealed` tinyint(1) DEFAULT 0,
  `is_denied` tinyint(1) DEFAULT 0,
  `contractual_adjustments` decimal(14,4) DEFAULT 0.0000,
  `other_adjustments` decimal(14,4) DEFAULT 0.0000,
  `paidamount` decimal(14,4) DEFAULT 0.0000,
  `is_secondary` tinyint(1) DEFAULT NULL,
  `is_tertiary` tinyint(1) DEFAULT NULL,
  `is_lupa` tinyint(1) DEFAULT NULL,
  `isoutlier` tinyint(1) DEFAULT NULL,
  `is_pep` tinyint(1) DEFAULT NULL,
  `is_transferred_copay` tinyint(1) DEFAULT NULL,
  `is_service_copay` tinyint(1) DEFAULT NULL,
  `is_manual` tinyint(1) DEFAULT NULL,
  `initial_hhrg` decimal(14,4) DEFAULT 0.0000,
  `final_hhrg` decimal(14,4) DEFAULT 0.0000,
  `hhrg_goal` decimal(14,4) DEFAULT 0.0000,
  `status` text DEFAULT NULL,
  `paid_date` timestamp NULL DEFAULT NULL,
  `is_edicreated` tinyint(1) DEFAULT NULL,
  `edi_createddate` timestamp NULL DEFAULT NULL,
  `edi_createdby_user_id` text DEFAULT NULL,
  `edi_file_id` int(11) DEFAULT NULL,
  `createdby_user_id` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `is_billed` tinyint(1) DEFAULT NULL,
  `invoice_status` text DEFAULT NULL,
  `parent_claimid` int(11) DEFAULT NULL,
  `primary_claimid` int(11) DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `addedon` timestamp NULL DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `billed_date` timestamp NULL DEFAULT NULL,
  `is_client_pay` tinyint(1) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_month` date DEFAULT NULL,
  `gl_locked_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `subreason` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `subreason_details` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `followedup_by` int(11) DEFAULT NULL,
  `followedup_at` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `adjustment_status` varchar(255) DEFAULT NULL,
  `remit_remark` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `adjustment_category_id` bigint(20) unsigned DEFAULT NULL,
  `biller` int(11) DEFAULT NULL,
  `teamlead_approved_by` int(11) DEFAULT NULL,
  `teamlead_approved_at` timestamp NULL DEFAULT NULL,
  `manager_approved_by` int(11) DEFAULT NULL,
  `manager_approved_at` timestamp NULL DEFAULT NULL,
  `director_approved_by` int(11) DEFAULT NULL,
  `director_approved_at` timestamp NULL DEFAULT NULL,
  `vp_approved_by` int(11) DEFAULT NULL,
  `vp_approved_at` timestamp NULL DEFAULT NULL,
  `operations_approved_by` int(11) DEFAULT NULL,
  `operations_approved_at` timestamp NULL DEFAULT NULL,
  `adjustment_completed_by` int(11) DEFAULT NULL,
  `adjustment_completed_at` timestamp NULL DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_level` varchar(255) DEFAULT NULL,
  `requested_ops_approval_date` date DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_claims_master_balance` (`balance`),
  KEY `idxr_claims_master_claim_id` (`claim_id`),
  KEY `idxr_claims_master_client_id` (`client_id`),
  KEY `idxr_claims_master_is_cancelled` (`is_cancelled`),
  KEY `idxr_claims_master_invoice_date` (`invoice_date`),
  KEY `idxr_claims_master_is_denied` (`is_denied`),
  KEY `idxr_claims_master_is_fully_paid` (`is_fully_paid`),
  KEY `idxr_claims_master_is_partially_paid` (`is_partially_paid`),
  KEY `claims_master_adjustment_category_id_index` (`adjustment_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims_master_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_master_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claims_master_id` int(10) unsigned DEFAULT NULL,
  `agency_id` int(10) unsigned DEFAULT NULL,
  `agency_branch_id` int(10) unsigned DEFAULT NULL,
  `claim_id` int(10) unsigned DEFAULT NULL,
  `client_id` int(10) unsigned DEFAULT NULL,
  `payersource_id` int(10) unsigned DEFAULT NULL,
  `episode_id` int(10) unsigned DEFAULT NULL,
  `type` text DEFAULT NULL,
  `invoice_date` timestamp NULL DEFAULT NULL,
  `invoice_number` text DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `totalamount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `contractual_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `balance` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `balance_contractual` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `is_fully_paid` tinyint(1) NOT NULL DEFAULT 0,
  `is_partially_paid` tinyint(1) NOT NULL DEFAULT 0,
  `is_appealed` tinyint(1) NOT NULL DEFAULT 0,
  `is_denied` tinyint(1) NOT NULL DEFAULT 0,
  `contractual_adjustments` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `other_adjustments` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `paidamount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `is_secondary` tinyint(1) DEFAULT NULL,
  `is_tertiary` tinyint(1) DEFAULT NULL,
  `is_lupa` tinyint(1) DEFAULT NULL,
  `isoutlier` tinyint(1) DEFAULT NULL,
  `is_pep` tinyint(1) DEFAULT NULL,
  `is_transferred_copay` tinyint(1) DEFAULT NULL,
  `is_service_copay` tinyint(1) DEFAULT NULL,
  `is_manual` tinyint(1) DEFAULT NULL,
  `initial_hhrg` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `final_hhrg` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `hhrg_goal` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `status` text DEFAULT NULL,
  `paid_date` timestamp NULL DEFAULT NULL,
  `is_edicreated` tinyint(1) DEFAULT NULL,
  `edi_createddate` timestamp NULL DEFAULT NULL,
  `edi_createdby_user_id` text DEFAULT NULL,
  `edi_file_id` int(10) unsigned DEFAULT NULL,
  `createdby_user_id` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `is_billed` tinyint(1) DEFAULT NULL,
  `invoice_status` varchar(55) DEFAULT NULL,
  `parent_claimid` int(10) unsigned DEFAULT NULL,
  `primary_claimid` int(10) unsigned DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `addedon` timestamp NULL DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `billed_date` timestamp NULL DEFAULT NULL,
  `is_client_pay` tinyint(1) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_month` date DEFAULT NULL,
  `gl_locked_date` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `subreason` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `subreason_details` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `followedup_by` int(10) unsigned DEFAULT NULL,
  `followedup_at` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `adjustment_status` varchar(255) DEFAULT NULL,
  `remit_remark` text DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(255) DEFAULT NULL,
  `biller` int(11) DEFAULT NULL,
  `teamlead_approved_by` int(11) DEFAULT NULL,
  `teamlead_approved_at` timestamp NULL DEFAULT NULL,
  `manager_approved_by` int(11) DEFAULT NULL,
  `manager_approved_at` timestamp NULL DEFAULT NULL,
  `director_approved_by` int(11) DEFAULT NULL,
  `director_approved_at` timestamp NULL DEFAULT NULL,
  `vp_approved_by` int(11) DEFAULT NULL,
  `vp_approved_at` timestamp NULL DEFAULT NULL,
  `operations_approved_by` int(11) DEFAULT NULL,
  `operations_approved_at` timestamp NULL DEFAULT NULL,
  `adjustment_completed_by` int(11) DEFAULT NULL,
  `adjustment_completed_at` timestamp NULL DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_level` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `claimpayment_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `paid_amt` double(8,4) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `posting_date` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `status` text DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `gl_locked_month` date DEFAULT NULL,
  `gl_locked_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_claims_payment_claimpayment_id` (`claimpayment_id`),
  KEY `idxr_claims_payment_claim_id` (`claim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_emergency_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `first_name` text DEFAULT NULL,
  `last_name` text DEFAULT NULL,
  `relation` text DEFAULT NULL,
  `priority` text DEFAULT NULL,
  `telephone` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `is_client_reprsentative` tinyint(1) DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_client_emergency_contacts_client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_map` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clientId` varchar(128) NOT NULL,
  `clientSlug` varchar(128) NOT NULL,
  `serviceUserId` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_client_map_clientid_unique` (`clientId`),
  UNIQUE KEY `api_client_map_clientslug_unique` (`clientSlug`),
  KEY `api_client_map_serviceuserid_foreign` (`serviceUserId`),
  CONSTRAINT `api_client_map_serviceuserid_foreign` FOREIGN KEY (`serviceUserId`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_master_additional_daas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_master_additional_daas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `client_client_id` int(11) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `first_episode_id` int(11) DEFAULT NULL,
  `last_episode_id` int(11) DEFAULT NULL,
  `rn_telnumber` text DEFAULT NULL,
  `pt_telnumber` text DEFAULT NULL,
  `ot_telnumber` text DEFAULT NULL,
  `st_telnumber` text DEFAULT NULL,
  `msw_telnumber` text DEFAULT NULL,
  `other_diagnosis` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `hospital_phone` text DEFAULT NULL,
  `insurance_group_no` text DEFAULT NULL,
  `ssn` text DEFAULT NULL,
  `billing_acuity_effperiod` text DEFAULT NULL,
  `last_schedule_date` timestamp NULL DEFAULT NULL,
  `last_recert_date` timestamp NULL DEFAULT NULL,
  `days_last_recert` text DEFAULT NULL,
  `last_supervisory_visit` timestamp NULL DEFAULT NULL,
  `poc_medication` text DEFAULT NULL,
  `poc_dme` text DEFAULT NULL,
  `poc_supplies` text DEFAULT NULL,
  `poc_safetymeasures` text DEFAULT NULL,
  `poc_nutrition_requirment` text DEFAULT NULL,
  `poc_food_allergies` text DEFAULT NULL,
  `poc_med_allergies` text DEFAULT NULL,
  `poc_environment_allergies` text DEFAULT NULL,
  `poc_functional_limitations` text DEFAULT NULL,
  `poc_activities_permitted` text DEFAULT NULL,
  `poc_mental_status` text DEFAULT NULL,
  `poc_prognosis` text DEFAULT NULL,
  `poc_verbal_soc_date` timestamp NULL DEFAULT NULL,
  `poc_hha_received_date` timestamp NULL DEFAULT NULL,
  `poc_certification` text DEFAULT NULL,
  `cma_agency_name` text DEFAULT NULL,
  `representative_avail` text DEFAULT NULL,
  `portal_avail` text DEFAULT NULL,
  `attphy_office_fax` text DEFAULT NULL,
  `attphy_office_phone` text DEFAULT NULL,
  `doc_received` text DEFAULT NULL,
  `intake_coordinator_lastname` text DEFAULT NULL,
  `intake_coordinator_firstname` text DEFAULT NULL,
  `refphy_office_fax` text DEFAULT NULL,
  `refphy_office_phone` text DEFAULT NULL,
  `days_on_service` int(11) DEFAULT NULL,
  `alert` text DEFAULT NULL,
  `payroll_export_id` text DEFAULT NULL,
  `billing_address_line1` text DEFAULT NULL,
  `billing_address_line2` text DEFAULT NULL,
  `billing_address_city` text DEFAULT NULL,
  `billing_address_state` text DEFAULT NULL,
  `billing_address_zip` text DEFAULT NULL,
  `gurantor_id` text DEFAULT NULL,
  `gurantor_name` text DEFAULT NULL,
  `gurantor_relation` text DEFAULT NULL,
  `other_diagnosis_desc` text DEFAULT NULL,
  `surg_diagnosis_desc` text DEFAULT NULL,
  `served_clinician` text DEFAULT NULL,
  `primary_diagnosis_desc` text DEFAULT NULL,
  `secondary_payersource_name` text DEFAULT NULL,
  `primary_elig_eff_period` text DEFAULT NULL,
  `payer_elig_notes` text DEFAULT NULL,
  `inpatient_facility` text DEFAULT NULL,
  `pharmacy_phone_no` text DEFAULT NULL,
  `vesta_id` text DEFAULT NULL,
  `attphy_contact` text DEFAULT NULL,
  `refphy_contact` text DEFAULT NULL,
  `pcphy_email` text DEFAULT NULL,
  `pcphy_fax` text DEFAULT NULL,
  `pcphy_contact` text DEFAULT NULL,
  `evv_aggregator` text DEFAULT NULL,
  `sn_service` text DEFAULT NULL,
  `pt_service` text DEFAULT NULL,
  `ot_service` text DEFAULT NULL,
  `st_service` text DEFAULT NULL,
  `aide_service` text DEFAULT NULL,
  `msw_service` text DEFAULT NULL,
  `facetoface` text DEFAULT NULL,
  `authorization` text DEFAULT NULL,
  `checklist` text DEFAULT NULL,
  `attphy_mobile` text DEFAULT NULL,
  `attphy_npi` text DEFAULT NULL,
  `attphy_email` text DEFAULT NULL,
  `attphy_fax` text DEFAULT NULL,
  `refphy_mobile` text DEFAULT NULL,
  `refphy_npi` text DEFAULT NULL,
  `refphy_email` text DEFAULT NULL,
  `refphy_fax` text DEFAULT NULL,
  `client_additional_unique_id` varchar(1000) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cmad_intakeid_clientadditionaluniqueid` (`intake_id`,`client_additional_unique_id`),
  KEY `idx_client_master_additional_daas_CLIENT_ADDITIONAL_UNIQUE_ID` (`client_additional_unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_medication_master_daas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_medication_master_daas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `medication_id` int(11) DEFAULT NULL,
  `episodeid` int(11) DEFAULT NULL,
  `level` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `dosage` text DEFAULT NULL,
  `route` text DEFAULT NULL,
  `frequency` text DEFAULT NULL,
  `medcode` text DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `planned_days` text DEFAULT NULL,
  `is_prn` tinyint(1) DEFAULT NULL,
  `dc_date` timestamp NULL DEFAULT NULL,
  `dc_time` text DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `frequency_type` text DEFAULT NULL,
  `frequency_dayson` int(11) DEFAULT NULL,
  `frequency_daysoff` int(11) DEFAULT NULL,
  `classification` text DEFAULT NULL,
  `med_profile` text DEFAULT NULL,
  `is_patientcaregiver_manages` tinyint(1) DEFAULT NULL,
  `is_antibiotic` tinyint(1) DEFAULT NULL,
  `is_high_risk` tinyint(1) DEFAULT NULL,
  `is_dc_ontime` tinyint(1) DEFAULT NULL,
  `is_hold` tinyint(1) DEFAULT NULL,
  `is_current_hold` tinyint(1) DEFAULT NULL,
  `classification_type` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `client_medication_unique_id` varchar(1000) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_client_medication_master_daas_client_medication_unique_id` (`client_medication_unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_payment_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_payment_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  `payertype` int(11) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `effectiveperiod` text DEFAULT NULL,
  `primary_id` text DEFAULT NULL,
  `parent_id` text DEFAULT NULL,
  `primary_payer_id` int(11) DEFAULT NULL,
  `additional_payer_id` int(11) DEFAULT NULL,
  `unique_id` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `clent_ps_insurance_no` text DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `effperiod_internal_id` int(11) DEFAULT NULL,
  `primary_internal_id` int(11) DEFAULT NULL,
  `addpayer_internal_id` int(11) DEFAULT NULL,
  `cs_unique_id` varchar(1000) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client_payment_source_cs_unique_id` (`cs_unique_id`),
  KEY `idxr_client_payment_source_client_id` (`client_id`),
  KEY `idxr_client_payment_source_filters` (`intake_id`,`payertype`,`start_date`,`primary_payer_id`,`additional_payer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_physician_master_daas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_physician_master_daas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `internal_id` int(11) DEFAULT NULL,
  `physician_id` int(11) DEFAULT NULL,
  `physician_identifier` text DEFAULT NULL,
  `lastseen_date` timestamp NULL DEFAULT NULL,
  `last_visit_type` text DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `fax` text DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `office_phonenumber` text DEFAULT NULL,
  `office_faxnumber` text DEFAULT NULL,
  `office_name` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consent_template_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consent_template_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `updated_date` datetime NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `document_type` enum('Privacy Policy','Terms & Conditions') NOT NULL,
  `action_type` enum('create','update','delete') NOT NULL,
  `document_version` decimal(4,1) DEFAULT NULL,
  `document_language` varchar(10) NOT NULL,
  `updated_content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consent_template_logs_created_by_foreign` (`created_by`),
  CONSTRAINT `consent_template_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consent_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consent_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `version` decimal(4,1) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `language` varchar(10) NOT NULL,
  `content` longtext NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consent_templates_created_by_foreign` (`created_by`),
  CONSTRAINT `consent_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `receiver_mobile` varchar(255) DEFAULT NULL,
  `sid` varchar(255) NOT NULL,
  `messaging_service_sid` varchar(255) NOT NULL,
  `chat_service_sid` varchar(255) DEFAULT NULL,
  `friendly_name` varchar(255) DEFAULT NULL,
  `unique_name` varchar(255) DEFAULT NULL,
  `participant_ids` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_archive` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_conversation_user_aocprovider_aoccandidate_receivermobile` (`user_id`,`aoc_provider_id`,`aoc_candidate_id`,`receiver_mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_reply` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `message_type` varchar(10) NOT NULL DEFAULT 'text' COMMENT 'message type eg text or media',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `isIncoming` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_conversation_reply_conversation_user_provider_candidate` (`conversation_id`,`user_id`,`aoc_provider_id`,`aoc_candidate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `core_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_setting` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `opt` varchar(255) DEFAULT '',
  `val` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coverage_tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coverage_tracker` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_date` date DEFAULT NULL,
  `filterDate` date DEFAULT NULL,
  `Patient_ID` varchar(100) DEFAULT NULL,
  `Client_First_Name` varchar(500) DEFAULT NULL,
  `Client_Last_Name` varchar(500) DEFAULT NULL,
  `Name` varchar(500) DEFAULT NULL,
  `Payer` varchar(200) DEFAULT NULL,
  `Discipline` varchar(200) DEFAULT NULL,
  `Service` varchar(200) DEFAULT NULL,
  `Schedule_Time` varchar(200) DEFAULT NULL,
  `Hours` varchar(200) DEFAULT NULL,
  `Authorization` varchar(200) DEFAULT NULL,
  `CF_Weight` varchar(200) DEFAULT NULL,
  `Male_Nurse_Accepted` varchar(3) DEFAULT NULL,
  `Pets_in_Home` varchar(200) DEFAULT NULL,
  `Non_Smoking` varchar(3) DEFAULT NULL,
  `Covid_Vaccine` varchar(3) DEFAULT NULL,
  `Lift_Available` varchar(200) DEFAULT NULL,
  `Attends_School` varchar(3) DEFAULT NULL,
  `Client_Address` varchar(500) DEFAULT NULL,
  `Client_County` varchar(500) DEFAULT NULL,
  `Client_City` varchar(500) DEFAULT NULL,
  `Client_ZipCode` varchar(500) DEFAULT NULL,
  `State` varchar(500) DEFAULT NULL,
  `Locations` varchar(500) DEFAULT NULL,
  `lat` varchar(500) DEFAULT NULL,
  `lng` varchar(500) DEFAULT NULL,
  `needs_geocoding` tinyint(1) DEFAULT 1,
  `escalated_pay_rate` double(5,2) DEFAULT NULL,
  `gp` double(7,5) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `date_filled` datetime DEFAULT NULL,
  `filled_by` int(11) DEFAULT NULL COMMENT 'User Ref',
  `approved_by` varchar(200) DEFAULT NULL,
  `is_updated` tinyint(1) DEFAULT NULL,
  `is_ignored` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_maps_coverageneeds_id` int(11) DEFAULT NULL,
  `text_call_client_schedule_regulars_activity_date` datetime DEFAULT NULL,
  `text_call_record_clinicians_activity_date` datetime DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_activity_date` datetime DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `call_nurses_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `check_cover_nurses_availability_activity_date` datetime DEFAULT NULL,
  `text_call_client_schedule_regulars_number` int(11) DEFAULT NULL,
  `text_call_record_clinicians_number` int(11) DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_number` int(11) DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_number` int(11) DEFAULT NULL,
  `call_nurses_from_text_blast_map_number` int(11) DEFAULT NULL,
  `check_cover_nurses_availability_number` varchar(100) DEFAULT NULL,
  `created_manually` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = when record created from coverage tracker form',
  `intake` varchar(10) DEFAULT NULL,
  `new_clinician_name` varchar(100) DEFAULT NULL,
  `request_ccr_hopsital_post_coverage_shift` tinyint(1) DEFAULT 0,
  `intake_been_addressed` tinyint(1) NOT NULL DEFAULT 0,
  `intake_addressed_by` int(11) NOT NULL DEFAULT 0,
  `intake_addressed_at` datetime DEFAULT NULL,
  `perm_filled` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `shift_type` varchar(255) DEFAULT NULL,
  `aoc_map_coverageclinician_id` varchar(255) DEFAULT NULL,
  `has_billable_visit` tinyint(4) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coverage_tracker_patient_shiftdate_intake_provider` (`aoc_patient_id`,`shift_date`,`intake_addressed_at`,`aoc_provider_id`),
  KEY `idx_coverage_tracker_filled_shiftdate_providerid_aocpatient` (`filled_by`,`shift_date`,`aoc_provider_id`,`aoc_patient_id`),
  KEY `idx_coverage_tracker_shiftdate_scheduletime` (`shift_date`,`Schedule_Time`),
  KEY `idx_fk_coverage_tracker_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_coverage_tracker_createdby_permfilled_intakeaddr` (`created_by`,`perm_filled`,`intake_addressed_at`),
  KEY `idx_coverage_tracker_schedule_time` (`Schedule_Time`),
  KEY `idxr_coverage_tracker_aoc_provider_id_aoc_patient_id` (`aoc_provider_id`,`aoc_patient_id`),
  KEY `idx_fk_coverage_tracker_aoc_provider_id` (`aoc_provider_id`),
  KEY `idx_fk_coverage_tracker_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coverage_tracker_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coverage_tracker_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coverage_tracker_id` int(11) DEFAULT NULL,
  `shift_date` date DEFAULT NULL,
  `filterDate` date DEFAULT NULL,
  `Patient_ID` varchar(100) DEFAULT NULL,
  `Client_First_Name` varchar(500) DEFAULT NULL,
  `Client_Last_Name` varchar(500) DEFAULT NULL,
  `Name` varchar(500) DEFAULT NULL,
  `Payer` varchar(200) DEFAULT NULL,
  `Discipline` varchar(200) DEFAULT NULL,
  `Service` varchar(200) DEFAULT NULL,
  `Schedule_Time` varchar(200) DEFAULT NULL,
  `Hours` varchar(200) DEFAULT NULL,
  `Authorization` varchar(200) DEFAULT NULL,
  `CF_Weight` varchar(200) DEFAULT NULL,
  `Male_Nurse_Accepted` varchar(3) DEFAULT NULL,
  `Pets_in_Home` varchar(200) DEFAULT NULL,
  `Non_Smoking` varchar(3) DEFAULT NULL,
  `Covid_Vaccine` varchar(3) DEFAULT NULL,
  `Lift_Available` varchar(200) DEFAULT NULL,
  `Attends_School` varchar(3) DEFAULT NULL,
  `Client_Address` varchar(500) DEFAULT NULL,
  `Client_County` varchar(500) DEFAULT NULL,
  `Client_City` varchar(500) DEFAULT NULL,
  `Client_ZipCode` varchar(500) DEFAULT NULL,
  `State` varchar(500) DEFAULT NULL,
  `Locations` varchar(500) DEFAULT NULL,
  `lat` varchar(500) DEFAULT NULL,
  `lng` varchar(500) DEFAULT NULL,
  `needs_geocoding` tinyint(1) DEFAULT 1,
  `escalated_pay_rate` double(5,2) DEFAULT NULL,
  `gp` double(7,5) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `date_filled` datetime DEFAULT NULL,
  `filled_by` int(11) DEFAULT NULL COMMENT 'User Ref',
  `approved_by` varchar(200) DEFAULT NULL,
  `is_updated` tinyint(1) DEFAULT NULL,
  `is_ignored` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_maps_coverageneeds_id` int(11) DEFAULT NULL,
  `text_call_client_schedule_regulars_activity_date` datetime DEFAULT NULL,
  `text_call_record_clinicians_activity_date` datetime DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_activity_date` datetime DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `call_nurses_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `check_cover_nurses_availability_activity_date` datetime DEFAULT NULL,
  `text_call_client_schedule_regulars_number` int(11) DEFAULT NULL,
  `text_call_record_clinicians_number` int(11) DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_number` int(11) DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_number` int(11) DEFAULT NULL,
  `call_nurses_from_text_blast_map_number` int(11) DEFAULT NULL,
  `check_cover_nurses_availability_number` varchar(100) DEFAULT NULL,
  `created_manually` tinyint(1) DEFAULT NULL,
  `reason_category` varchar(200) DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `intake` varchar(10) DEFAULT NULL,
  `new_clinician_name` varchar(100) DEFAULT NULL,
  `request_ccr_hopsital_post_coverage_shift` tinyint(1) DEFAULT NULL,
  `intake_been_addressed` tinyint(1) NOT NULL DEFAULT 0,
  `intake_addressed_by` int(11) NOT NULL DEFAULT 0,
  `intake_addressed_at` datetime DEFAULT NULL,
  `perm_filled` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `shift_type` varchar(255) DEFAULT NULL,
  `aoc_map_coverageclinician_id` varchar(255) DEFAULT NULL,
  `has_billable_visit` tinyint(4) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(20) DEFAULT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `db_field_name` varchar(150) DEFAULT NULL,
  `preference_tag` varchar(255) DEFAULT NULL,
  `input_type` varchar(20) DEFAULT NULL,
  `frontend_label` varchar(255) DEFAULT NULL,
  `call_script` varchar(255) DEFAULT NULL,
  `frontend_instruction` varchar(500) DEFAULT NULL,
  `default_value` varchar(255) DEFAULT NULL,
  `max_length` int(11) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT NULL,
  `backend_type` varchar(20) DEFAULT NULL,
  `options` text DEFAULT NULL,
  `sort` int(11) DEFAULT 0,
  `is_dependent` tinyint(1) DEFAULT 0,
  `dependent_on` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` int(11) DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_id` int(11) DEFAULT NULL,
  `app_version` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(30) DEFAULT NULL,
  `os` varchar(30) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `udid` varchar(40) DEFAULT NULL,
  `fcm_token` text DEFAULT NULL,
  `resolution` varchar(60) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=inactive,1=active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_department` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `facility_name` varchar(255) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_new_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_new_users` (
  `id` varchar(10) DEFAULT NULL,
  `name` varchar(27) DEFAULT NULL,
  `email` varchar(32) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `kantime_username` varchar(6) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_supervisors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_supervisors` (
  `Employee_Code` varchar(25) DEFAULT NULL,
  `Employee_Name` varchar(39) DEFAULT NULL,
  `Position` varchar(55) DEFAULT NULL,
  `Division_Desc` varchar(31) DEFAULT NULL,
  `Department_Desc` varchar(30) DEFAULT NULL,
  `Work_Location` varchar(39) DEFAULT NULL,
  `Supervisor_Primary` varchar(26) DEFAULT NULL,
  `Manager` varchar(24) DEFAULT NULL,
  `Manager_2` varchar(23) DEFAULT NULL,
  `Executive` varchar(21) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `supervisor_id` int(10) NOT NULL,
  `manager_id` int(10) NOT NULL,
  `exec_id` int(10) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encounter_diagnoses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_diagnoses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `icd_10_code` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `system` varchar(255) DEFAULT NULL,
  `rank` varchar(50) NOT NULL,
  `clinical_status` varchar(50) NOT NULL,
  `verification_status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounter_diagnoses_encounter_id_icd_10_code_unique` (`encounter_id`,`icd_10_code`),
  KEY `encounter_diagnoses_icd_10_code_index` (`icd_10_code`),
  KEY `fk_ht_diagnoses_created_by_id` (`created_by_id`),
  CONSTRAINT `encounter_diagnoses_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `fk_ht_diagnoses_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encounters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_parent_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `hospitalization_type` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `facility_name` varchar(255) DEFAULT NULL,
  `admission_at` timestamp NULL DEFAULT NULL,
  `admission_type` varchar(50) DEFAULT NULL,
  `admission_reason` text DEFAULT NULL,
  `transfer_reason` text DEFAULT NULL,
  `expected_discharge_at` timestamp NULL DEFAULT NULL,
  `discharged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounters_patient_id_foreign` (`patient_id`),
  KEY `encounters_encounter_parent_id_foreign` (`encounter_parent_id`),
  KEY `encounters_type_index` (`type`),
  KEY `encounters_status_index` (`status`),
  KEY `encounters_admission_at_index` (`admission_at`),
  CONSTRAINT `encounters_encounter_parent_id_foreign` FOREIGN KEY (`encounter_parent_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `encounters_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exclude_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exclude_cc` (
  `ClinicianFirstName` varchar(100) DEFAULT NULL,
  `ClinicianLastName` varchar(100) DEFAULT NULL,
  `ClinicianID` varchar(25) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_user_patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_user_patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'External user ID',
  `aoc_patient_id` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'aoc user id',
  `aoc_patient_id` int(11) NOT NULL DEFAULT 0,
  `aoc_provider_id` int(11) NOT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0=inactive,1=active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `flag` varchar(255) NOT NULL,
  `flag_description` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `users` text DEFAULT NULL,
  `ips` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_feature_flags_flag` (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `field_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `manager` int(11) DEFAULT NULL,
  `status` varchar(200) DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item1_note` varchar(255) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item2_note` varchar(255) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item3_note` varchar(255) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item4_note` varchar(255) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item5_note` varchar(255) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item6_note` varchar(255) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item7_note` varchar(255) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item8_note` varchar(255) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `productivity` varchar(255) DEFAULT NULL,
  `ie_re_time` varchar(255) DEFAULT NULL,
  `rv_sv_dc_time` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `first_advantage_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `first_advantage_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_candidate_id` bigint(20) NOT NULL,
  `fa_candidate_id` varchar(255) DEFAULT NULL,
  `fa_order_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `application_status` varchar(255) DEFAULT NULL,
  `result_status` varchar(40) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `first_advantage_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `first_advantage_errors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` smallint(5) unsigned NOT NULL,
  `message` text NOT NULL,
  `userId` bigint(20) unsigned DEFAULT NULL,
  `aoc_candidate_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gp_calculator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gp_calculator` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `aoc_patient_id` text DEFAULT NULL,
  `nurse_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gp_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gp_escalations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payer_id` int(11) NOT NULL,
  `payer_group` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `service` varchar(10) NOT NULL,
  `Level1` varchar(255) DEFAULT NULL,
  `Level2` varchar(255) DEFAULT NULL,
  `Level3` varchar(255) DEFAULT NULL,
  `Level4` varchar(255) DEFAULT NULL,
  `Level5` varchar(255) DEFAULT NULL,
  `Level6` varchar(255) DEFAULT NULL,
  `Level7` varchar(255) DEFAULT NULL,
  `Level8` varchar(255) DEFAULT NULL,
  `Level9` varchar(255) DEFAULT NULL,
  `Level10` varchar(255) DEFAULT NULL,
  `Level11` varchar(255) DEFAULT NULL,
  `Level12` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_coverage` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gross_margin_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gross_margin_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lob_id` int(11) DEFAULT NULL,
  `branch_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`branch_ids`)),
  `payer_id` bigint(20) unsigned NOT NULL,
  `lob_enum` int(11) DEFAULT NULL,
  `recruiter_coverage_target` decimal(5,1) DEFAULT NULL,
  `recruiter_escalated` decimal(5,1) DEFAULT NULL,
  `tl_manager_escalated` decimal(5,1) DEFAULT NULL,
  `tl_manager_whole_home_escalated` decimal(5,1) DEFAULT NULL,
  `exec_team_escalated` decimal(5,1) DEFAULT NULL,
  `coverage_escalated` decimal(5,1) DEFAULT NULL,
  `coverage_tl_manager_escalated` decimal(5,1) DEFAULT NULL,
  `coverage_tl_manager_whole_home_escalated` decimal(5,1) DEFAULT NULL,
  `coverage_exec_team_escalated` decimal(5,1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gross_margin_configurations_lob_id_foreign` (`lob_id`),
  KEY `gross_margin_configurations_payer_id_foreign` (`payer_id`),
  CONSTRAINT `gross_margin_configurations_payer_id_foreign` FOREIGN KEY (`payer_id`) REFERENCES `payment_source` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_report_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_report_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unique_key` varchar(200) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `context_type` int(11) DEFAULT NULL,
  `inititalsocroc_assessment_date` timestamp NULL DEFAULT NULL,
  `primary_diagnosis` varchar(1000) DEFAULT NULL,
  `transferoronhold_date` timestamp NULL DEFAULT NULL,
  `rocorresume_date` timestamp NULL DEFAULT NULL,
  `discharge_date` timestamp NULL DEFAULT NULL,
  `daysathome` int(11) DEFAULT NULL,
  `daysathospital` int(11) DEFAULT NULL,
  `typeoffacility` varchar(1000) DEFAULT NULL,
  `facility_transferredto` varchar(1000) DEFAULT NULL,
  `reason_id` varchar(1000) DEFAULT NULL,
  `reason_desc` varchar(1000) DEFAULT NULL,
  `episode_number` int(11) DEFAULT NULL,
  `episode_startdate` timestamp NULL DEFAULT NULL,
  `episode_enddate` timestamp NULL DEFAULT NULL,
  `other_diagnosis` varchar(1000) DEFAULT NULL,
  `secondary_payer` varchar(1000) DEFAULT NULL,
  `tertiarypayer` varchar(1000) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `surgical_diagnosis` varchar(1000) DEFAULT NULL,
  `billingacuity_effectiveperiod` varchar(1000) DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `discharge_dx` varchar(1000) DEFAULT NULL,
  `discharge_sec_dx` varchar(1000) DEFAULT NULL,
  `hosp_discharge_date` timestamp NULL DEFAULT NULL,
  `hosp_discharge_time` varchar(1000) DEFAULT NULL,
  `resumption_date` timestamp NULL DEFAULT NULL,
  `stayathome_days` int(11) DEFAULT NULL,
  `stayathome_minutes` int(11) DEFAULT NULL,
  `stayathospital_days` int(11) DEFAULT NULL,
  `stayathospital_minutes` int(11) DEFAULT NULL,
  `type` varchar(1000) DEFAULT NULL,
  `hospitalization_date` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospitalization_tracker_id` bigint(20) unsigned NOT NULL,
  `contact_type_id` bigint(20) unsigned NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_date` datetime NOT NULL,
  `received_by_id` bigint(20) unsigned NOT NULL,
  `contact_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ht_contacts_tracker_id` (`hospitalization_tracker_id`),
  KEY `fk_ht_contacts_contact_type_id` (`contact_type_id`),
  KEY `fk_ht_contacts_received_by_id` (`received_by_id`),
  CONSTRAINT `fk_ht_contacts_contact_type_id` FOREIGN KEY (`contact_type_id`) REFERENCES `contact_types` (`id`),
  CONSTRAINT `fk_ht_contacts_received_by_id` FOREIGN KEY (`received_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ht_contacts_tracker_id` FOREIGN KEY (`hospitalization_tracker_id`) REFERENCES `hospitalization_trackers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_custom_property_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_custom_property_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `default_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_value`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_followups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospitalization_tracker_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to_id` bigint(20) unsigned DEFAULT NULL,
  `requested_by_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT NULL,
  `attempted_by_id` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status_note` text DEFAULT NULL,
  `follow_ups_updates` text DEFAULT NULL,
  `needs` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ht_followups_tracker_id` (`hospitalization_tracker_id`),
  KEY `fk_ht_followups_patient_id` (`patient_id`),
  KEY `fk_ht_followups_assigned_to_id` (`assigned_to_id`),
  KEY `fk_ht_followups_requested_by_id` (`requested_by_id`),
  KEY `fk_ht_followups_completed_by_id` (`completed_by_id`),
  KEY `hospitalization_tracker_followups_status_index` (`status`),
  KEY `hospitalization_tracker_followups_scheduled_at_index` (`scheduled_at`),
  KEY `fk_ht_followups_attempted_by_id` (`attempted_by_id`),
  CONSTRAINT `fk_ht_followups_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ht_followups_attempted_by_id` FOREIGN KEY (`attempted_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ht_followups_completed_by_id` FOREIGN KEY (`completed_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ht_followups_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`),
  CONSTRAINT `fk_ht_followups_requested_by_id` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ht_followups_tracker_id` FOREIGN KEY (`hospitalization_tracker_id`) REFERENCES `hospitalization_trackers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospitalization_tracker_id` bigint(20) unsigned NOT NULL,
  `definition_id` bigint(20) unsigned NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ht_properties_tracker_definition_unique` (`hospitalization_tracker_id`,`definition_id`),
  KEY `fk_ht_properties_definition_id` (`definition_id`),
  CONSTRAINT `fk_ht_properties_definition_id` FOREIGN KEY (`definition_id`) REFERENCES `hospitalization_tracker_custom_property_definitions` (`id`),
  CONSTRAINT `fk_ht_properties_tracker_id` FOREIGN KEY (`hospitalization_tracker_id`) REFERENCES `hospitalization_trackers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_training_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_training_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_training_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_training_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_tracker_trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_tracker_trainings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospitalization_tracker_id` bigint(20) unsigned NOT NULL,
  `training_type_id` bigint(20) unsigned NOT NULL,
  `training_source_id` bigint(20) unsigned NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by_id` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ht_trainings_tracker_id_foreign` (`hospitalization_tracker_id`),
  KEY `hospitalization_tracker_trainings_training_type_id_foreign` (`training_type_id`),
  KEY `hospitalization_tracker_trainings_completed_by_id_foreign` (`completed_by_id`),
  KEY `hospitalization_tracker_trainings_completed_at_index` (`completed_at`),
  KEY `fk_ht_trainings_source_id` (`training_source_id`),
  CONSTRAINT `fk_ht_trainings_source_id` FOREIGN KEY (`training_source_id`) REFERENCES `hospitalization_tracker_training_sources` (`id`),
  CONSTRAINT `hospitalization_tracker_trainings_completed_by_id_foreign` FOREIGN KEY (`completed_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hospitalization_tracker_trainings_training_type_id_foreign` FOREIGN KEY (`training_type_id`) REFERENCES `hospitalization_tracker_training_types` (`id`),
  CONSTRAINT `ht_trainings_tracker_id_foreign` FOREIGN KEY (`hospitalization_tracker_id`) REFERENCES `hospitalization_trackers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hospitalization_trackers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalization_trackers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `icm_level` varchar(255) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `closing_reason` varchar(50) DEFAULT NULL,
  `closing_reason_details` text DEFAULT NULL,
  `assigned_to_id` bigint(20) unsigned DEFAULT NULL,
  `sent_to_review_by_id` bigint(20) unsigned DEFAULT NULL,
  `declined_program_contact` varchar(50) DEFAULT NULL,
  `aoc_care_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aoc_care_requirements`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hospitalization_trackers_encounter_id_foreign` (`encounter_id`),
  KEY `hospitalization_trackers_assigned_to_id_foreign` (`assigned_to_id`),
  KEY `hospitalization_trackers_sent_to_review_by_id_foreign` (`sent_to_review_by_id`),
  KEY `hospitalization_trackers_status_index` (`status`),
  KEY `hospitalization_trackers_author_id_foreign` (`author_id`),
  CONSTRAINT `hospitalization_trackers_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hospitalization_trackers_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hospitalization_trackers_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `hospitalization_trackers_sent_to_review_by_id_foreign` FOREIGN KEY (`sent_to_review_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hub_city_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hub_city_master` (
  `COL 1` varchar(15) DEFAULT NULL,
  `COL 2` varchar(27) DEFAULT NULL,
  `COL 3` varchar(19) DEFAULT NULL,
  `COL 4` varchar(16) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immunization_dose_ages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `immunization_dose_ages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recommendation_id` bigint(20) unsigned NOT NULL,
  `from_age` smallint(5) unsigned DEFAULT NULL,
  `from_age_unit` varchar(50) DEFAULT NULL,
  `to_age` smallint(5) unsigned DEFAULT NULL,
  `to_age_unit` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_imm_dose_ages_recommendation_id` (`recommendation_id`),
  CONSTRAINT `fk_imm_dose_ages_recommendation_id` FOREIGN KEY (`recommendation_id`) REFERENCES `immunization_recommendations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immunization_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `immunization_recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vaccine_cvx_code` smallint(5) unsigned NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `originating_state` varchar(2) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_imm_recommendations_vaccine_state` (`vaccine_cvx_code`,`originating_state`),
  KEY `immunization_recommendations_originating_state_index` (`originating_state`),
  KEY `immunization_recommendations_status_index` (`status`),
  CONSTRAINT `fk_imm_recommendations_vaccine_cvx_code` FOREIGN KEY (`vaccine_cvx_code`) REFERENCES `cdc_vaccines` (`cvx_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immunization_recommendations_mssql_patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `immunization_recommendations_mssql_patients` (
  `junction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `recommendation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`junction_id`),
  KEY `fk_imm_rec_patients_patient_id` (`patient_id`),
  KEY `fk_imm_rec_patients_recommendation_id` (`recommendation_id`),
  CONSTRAINT `fk_imm_rec_patients_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`),
  CONSTRAINT `fk_imm_rec_patients_recommendation_id` FOREIGN KEY (`recommendation_id`) REFERENCES `immunization_recommendations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immunization_recommended_for`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `immunization_recommended_for` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recommendation_id` bigint(20) unsigned NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_imm_recommended_for_recommendation_id` (`recommendation_id`),
  CONSTRAINT `fk_imm_recommended_for_recommendation_id` FOREIGN KEY (`recommendation_id`) REFERENCES `immunization_recommendations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immunization_trackers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `immunization_trackers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `vaccine_cvx_code` smallint(5) unsigned NOT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_imm_trackers_patient_id` (`patient_id`),
  KEY `fk_imm_trackers_vaccine_cvx_code` (`vaccine_cvx_code`),
  KEY `fk_imm_trackers_assignee_id` (`assignee_id`),
  KEY `fk_imm_trackers_created_by_id` (`created_by_id`),
  KEY `immunization_trackers_status_index` (`status`),
  CONSTRAINT `fk_imm_trackers_assignee_id` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_imm_trackers_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_imm_trackers_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`),
  CONSTRAINT `fk_imm_trackers_vaccine_cvx_code` FOREIGN KEY (`vaccine_cvx_code`) REFERENCES `cdc_vaccines` (`cvx_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_visit_original` int(11) DEFAULT NULL,
  `intake_first_billable_visit_original` int(11) DEFAULT NULL,
  `intake_completed_original` tinyint(4) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_intake_completed_admissionvisit_lobid` (`intake_completed`,`intake_admission_visit`,`LOBID`),
  KEY `idx_intake_outcome_nonadmit_reason_aoc_patient_id` (`intake_outcome`,`intake_nonadmit_reason`,`aoc_patient_id`),
  KEY `idx_fk_intake_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_fk_intake_lobid` (`LOBID`),
  KEY `idx_fk_intake_ic_id` (`ic_id`),
  KEY `idxr_intake_filters` (`aoc_patient_id`,`aoc_nthaccounts_id`,`goal_soc_date`,`admission_visit_date`,`ic_id`,`BD_Rep`,`LOBID`,`IntakeDate`,`intake_outcome`,`intake_bucket`),
  KEY `idxr_intake_intake_completed` (`intake_completed`),
  KEY `idxr_intake_intake_admission_visit` (`intake_admission_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake_07032025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_07032025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake_07082025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_07082025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake_24-Jul-2025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_24-Jul-2025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_tmp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_intake_completed_admissionvisit_lobid` (`intake_completed`,`intake_admission_visit`,`LOBID`),
  KEY `idx_intake_outcome_nonadmit_reason_aoc_patient_id` (`intake_outcome`,`intake_nonadmit_reason`,`aoc_patient_id`),
  KEY `idx_fk_intake_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_fk_intake_lobid` (`LOBID`),
  KEY `idx_fk_intake_ic_id` (`ic_id`),
  KEY `idxr_intake_filters` (`aoc_patient_id`,`aoc_nthaccounts_id`,`goal_soc_date`,`admission_visit_date`,`ic_id`,`BD_Rep`,`LOBID`,`IntakeDate`,`intake_outcome`,`intake_bucket`),
  KEY `idxr_intake_intake_completed` (`intake_completed`),
  KEY `idxr_intake_intake_admission_visit` (`intake_admission_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intake_tmp_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_tmp_2` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `referral_type` varchar(100) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `hospital_discharge_date` datetime DEFAULT NULL,
  `anticipated_hours` int(11) DEFAULT NULL,
  `goal_soc_date` datetime DEFAULT NULL,
  `admission_visit_scheduled` tinyint(1) DEFAULT NULL,
  `intake_nurse_id` int(11) DEFAULT NULL COMMENT 'ref users.id',
  `admission_visit_date` datetime DEFAULT NULL,
  `add_to_nth` tinyint(1) DEFAULT NULL,
  `kantime_url` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `IntakeStatus` varchar(255) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT NULL,
  `CF_ServicesNeeded` text DEFAULT NULL,
  `CF_Skills` text DEFAULT NULL,
  `NTHNotes` text DEFAULT NULL,
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(4) DEFAULT NULL,
  `intake_readytostart_reason` text DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(4) DEFAULT NULL,
  `intake_approved_date` datetime DEFAULT NULL,
  `CF_ReferralType` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `bd_id` int(11) DEFAULT NULL,
  `intake_completed` tinyint(4) DEFAULT 0,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_intake_completed_admissionvisit_lobid` (`intake_completed`,`intake_admission_visit`,`LOBID`),
  KEY `idx_intake_outcome_nonadmit_reason_aoc_patient_id` (`intake_outcome`,`intake_nonadmit_reason`,`aoc_patient_id`),
  KEY `idx_fk_intake_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_fk_intake_lobid` (`LOBID`),
  KEY `idx_fk_intake_ic_id` (`ic_id`),
  KEY `idxr_intake_filters` (`aoc_patient_id`,`aoc_nthaccounts_id`,`goal_soc_date`,`admission_visit_date`,`ic_id`,`BD_Rep`,`LOBID`,`IntakeDate`,`intake_outcome`,`intake_bucket`),
  KEY `idxr_intake_intake_completed` (`intake_completed`),
  KEY `idxr_intake_intake_admission_visit` (`intake_admission_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipeaChecklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipeaChecklist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `checklist` varchar(255) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sortOrder` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipeaChecklistRecruitingSchedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipeaChecklistRecruitingSchedule` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ipea_checklist_id` int(11) NOT NULL COMMENT 'Ref ID of ipeaChecklist',
  `schedule_id` int(11) NOT NULL COMMENT 'Ref ID of recruiting_schedule',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ipeaChecklistRecruitingSchedule_schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipea_email_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipea_email_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `facilities` text NOT NULL,
  `skills` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `State` varchar(20) DEFAULT '',
  `Type` varchar(100) DEFAULT '',
  `category` varchar(255) DEFAULT '',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) DEFAULT NULL COMMENT 'Users ID Ref who created the record',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` bigint(20) DEFAULT NULL COMMENT 'Users ID Ref who updated the record',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_category_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_category_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_category_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_category_logs_job_category_id_foreign` (`job_category_id`),
  CONSTRAINT `job_category_logs_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agencyAccessEmails` varchar(255) NOT NULL,
  `applyFormId` text NOT NULL,
  `applyLink` varchar(500) NOT NULL,
  `bonus` varchar(50) NOT NULL,
  `briefDescription` text NOT NULL,
  `category` varchar(255) NOT NULL,
  `branch` varchar(1000) DEFAULT NULL,
  `state` varchar(50) NOT NULL,
  `closeDate` datetime NOT NULL,
  `company` varchar(255) NOT NULL,
  `companyId` varchar(50) NOT NULL,
  `createdBy` varchar(255) NOT NULL,
  `createdByJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` text NOT NULL,
  `detailLink` varchar(500) NOT NULL,
  `distribution` tinyint(1) NOT NULL,
  `eId` varchar(50) NOT NULL,
  `eeoCategory` varchar(500) NOT NULL,
  `evaluationFormName` varchar(255) NOT NULL,
  `hiringManagers` varchar(500) NOT NULL,
  `hiringManagersJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `internalOnly` tinyint(1) NOT NULL,
  `jobSource` varchar(255) NOT NULL,
  `jobState` varchar(50) NOT NULL,
  `lastUpdatedDate` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `locationCity` varchar(255) NOT NULL,
  `locationCountry` varchar(255) NOT NULL,
  `locationEId` varchar(50) NOT NULL,
  `locationPostalCode` varchar(50) NOT NULL,
  `locationState` varchar(100) NOT NULL,
  `openings` varchar(255) NOT NULL,
  `jobLocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `otherLocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `postingType` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `primaryHiringManager` varchar(255) NOT NULL,
  `primaryHiringManagerJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `primaryHiringManagerEmail` varchar(255) NOT NULL,
  `primaryRecruiter` varchar(255) NOT NULL,
  `primaryRecruiterJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `primaryRecruiterEmail` varchar(255) NOT NULL,
  `private` tinyint(1) NOT NULL,
  `recruiters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `requisitionId` varchar(50) NOT NULL,
  `sentDate` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `workflow` varchar(100) NOT NULL,
  `jobType` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `customField` longtext NOT NULL,
  `department` varchar(255) NOT NULL,
  `preInterviewFormName` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `emailLanguage` varchar(255) DEFAULT NULL,
  `no_active_nth` int(11) NOT NULL DEFAULT 0,
  `no_active_nth_resolved_by` int(11) DEFAULT NULL,
  `no_active_nth_resolved_on` datetime DEFAULT NULL,
  `templateCode` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_jobs_branch` (`branch`),
  KEY `idxr_jobs_category_state_id` (`category`,`state`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs_manager` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobvite_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobvite_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` text DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobvite_category_blank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobvite_category_blank` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` text DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `note_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `context_type` varchar(200) DEFAULT NULL,
  `context_id` int(11) DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT NULL,
  `addedby_user_id` int(11) DEFAULT NULL,
  `modified_date` timestamp NULL DEFAULT NULL,
  `modifiedby_user_id` int(11) DEFAULT NULL,
  `category` varchar(250) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `lob_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `note_cleared_date` datetime DEFAULT NULL,
  `note_cleared_by` int(11) NOT NULL DEFAULT 0,
  `ProviderID` int(11) DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_journal_notes_filters` (`context_type`,`note_cleared_date`,`category`,`added_date`),
  KEY `idxr_journal_notes_category` (`category`),
  KEY `idxr_journal_notes_note_id` (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lob` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `LOBID` int(11) DEFAULT NULL,
  `LOBName` varchar(250) DEFAULT '',
  `EMRID` tinyint(4) DEFAULT NULL,
  `CreateDate` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_lob_lobid` (`LOBID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `location` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `address2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `address3` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `state` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `country` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '',
  `zipcode` int(11) DEFAULT NULL,
  `lat` decimal(12,9) DEFAULT NULL,
  `lng` decimal(12,9) DEFAULT NULL,
  `location_search` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_location_lat_lng` (`lat`,`lng`),
  KEY `idxr_location_location_search` (`location_search`(1024))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_restrictions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blacklisted` tinyint(1) DEFAULT NULL,
  `whitelisted` tinyint(1) DEFAULT NULL,
  `ipaddress` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempts` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map48`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map48` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LastName` text NOT NULL,
  `FirstName` text NOT NULL,
  `MiddleName` text NOT NULL,
  `Suffix` text NOT NULL,
  `BusinessName` text NOT NULL,
  `Phone` text NOT NULL,
  `Fax` text NOT NULL,
  `Address` text NOT NULL,
  `Address_2` text NOT NULL,
  `City` text NOT NULL,
  `StateCode` text NOT NULL,
  `ZipCode` text NOT NULL,
  `Description` text NOT NULL,
  `CredentialNumber` text NOT NULL,
  `Status` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map49`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map49` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LastName` text NOT NULL,
  `FirstName` text NOT NULL,
  `MiddleName` text NOT NULL,
  `Suffix` text NOT NULL,
  `BusinessName` text NOT NULL,
  `Phone` text NOT NULL,
  `Fax` text NOT NULL,
  `Address` text NOT NULL,
  `Address_2` text NOT NULL,
  `City` text NOT NULL,
  `StateCode` text NOT NULL,
  `ZipCode` text NOT NULL,
  `Description` text NOT NULL,
  `CredentialNumber` text NOT NULL,
  `Status` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map56`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map56` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Person_ID` text NOT NULL,
  `License_Number` text NOT NULL,
  `License_Type` text NOT NULL,
  `Compact_Type` text NOT NULL,
  `License_First_Issued` text NOT NULL,
  `License_Last_Issued` text NOT NULL,
  `License_Expiration_Date` text NOT NULL,
  `License_Status` text NOT NULL,
  `First_Name` text NOT NULL,
  `Middle_Name` text NOT NULL,
  `Last_Name` text NOT NULL,
  `Birth_Year` text NOT NULL,
  `Address_Line_1` text NOT NULL,
  `Address_Line_2` text NOT NULL,
  `City` text NOT NULL,
  `State/Province` text NOT NULL,
  `Postal_Code` text NOT NULL,
  `Country` text NOT NULL,
  `Email_Address` text NOT NULL,
  `Primary_Specialty` text NOT NULL,
  `Education_Basis_for_License` text NOT NULL,
  `Prescriptive_Authority` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map59`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map59` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `ClientID` text NOT NULL,
  `Location` text NOT NULL,
  `NTHStatus` text NOT NULL,
  `Client_Care_Coordinator` text NOT NULL,
  `CreatedReason` text NOT NULL,
  `Days_Since_Created` text NOT NULL,
  `CreatedOn` text NOT NULL,
  `Recruiter` text NOT NULL,
  `Special_Recruiter` text NOT NULL,
  `Schedule_Text` text NOT NULL,
  `Schedule_Free_Text` text NOT NULL,
  `hours_on_NTH` text NOT NULL,
  `Total_Authorized_PDN_Hours` text NOT NULL,
  `Total_Authorized_Respite_Hours` text NOT NULL,
  `Total_Authorized_CNA_Hours` text NOT NULL,
  `Age` text NOT NULL,
  `Perm_Staffing_%` text NOT NULL,
  `Gender` text NOT NULL,
  `Biography` text NOT NULL,
  `The_Sale` text NOT NULL,
  `Attending_School` text NOT NULL,
  `Language` text NOT NULL,
  `Nurse_Language` text NOT NULL,
  `Pets_In_Home` text NOT NULL,
  `Pets_Nurse_Can_not_Own` text NOT NULL,
  `Weight` text NOT NULL,
  `Lifting_Requirements` text NOT NULL,
  `Specialized_Client` text NOT NULL,
  `Skills` text NOT NULL,
  `Services_Needed` text NOT NULL,
  `Clinician_Gender` text NOT NULL,
  `Clinician_Discipline` text NOT NULL,
  `Payer` text NOT NULL,
  `Special_Payer_Group` text NOT NULL,
  `RespitePayer` text NOT NULL,
  `Address` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zipcode` text NOT NULL,
  `Phone` text NOT NULL,
  `Intake_Status` text NOT NULL,
  `Intake_Date` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Referral_Date` text NOT NULL,
  `Intake_Coordinator` text NOT NULL,
  `Business_Developer` text NOT NULL,
  `Meeting_Nurses` text NOT NULL,
  `Patient_DOB` text NOT NULL,
  `WorkForce_DevProj` text NOT NULL,
  `Is_Priority` text NOT NULL,
  `Is_Intake` text NOT NULL,
  `NTHNotes` text NOT NULL,
  `Covid19_Vaccine_Required` text NOT NULL,
  `Media_Consent` text NOT NULL,
  `NonSmoking_Nurses` text NOT NULL,
  `Smokers_LiveIn_Home` text NOT NULL,
  `CreatedBy` text NOT NULL,
  `Checklist_Bit` text NOT NULL,
  `School_District` text NOT NULL,
  `School_Name` text NOT NULL,
  `Orders_Received` text NOT NULL,
  `Count_of_IPEAs_Schedules` text NOT NULL,
  `Hours_for_IPEAs_Scheduled` text NOT NULL,
  `Count_of_PEPIs_Schedules` text NOT NULL,
  `Hours_for_PEPIs_Scheduled` text NOT NULL,
  `Count_of_WEPIs_Schedules` text NOT NULL,
  `Hours_for_WEPIs_Scheduled` text NOT NULL,
  `Count_of_CM_Scheduled` text NOT NULL,
  `Hours_for_CM_Scheduled` text NOT NULL,
  `The_last_escalated_rate` text NOT NULL,
  `Last_Escalation` text NOT NULL,
  `TextBlastContent` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map60`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map60` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `ClientID` text NOT NULL,
  `Location` text NOT NULL,
  `NTHStatus` text NOT NULL,
  `Client_Care_Coordinator` text NOT NULL,
  `CreatedReason` text NOT NULL,
  `Days_Since_Created` text NOT NULL,
  `CreatedOn` text NOT NULL,
  `Recruiter` text NOT NULL,
  `Special_Recruiter` text NOT NULL,
  `Schedule_Text` text NOT NULL,
  `Schedule_Free_Text` text NOT NULL,
  `hours_on_NTH` text NOT NULL,
  `Total_Authorized_PDN_Hours` text NOT NULL,
  `Total_Authorized_Respite_Hours` text NOT NULL,
  `Total_Authorized_CNA_Hours` text NOT NULL,
  `Age` text NOT NULL,
  `Perm_Staffing_%` text NOT NULL,
  `Gender` text NOT NULL,
  `Biography` text NOT NULL,
  `The_Sale` text NOT NULL,
  `Attending_School` text NOT NULL,
  `Language` text NOT NULL,
  `Nurse_Language` text NOT NULL,
  `Pets_In_Home` text NOT NULL,
  `Pets_Nurse_Can_not_Own` text NOT NULL,
  `Weight` text NOT NULL,
  `Lifting_Requirements` text NOT NULL,
  `Specialized_Client` text NOT NULL,
  `Skills` text NOT NULL,
  `Services_Needed` text NOT NULL,
  `Clinician_Gender` text NOT NULL,
  `Clinician_Discipline` text NOT NULL,
  `Payer` text NOT NULL,
  `Special_Payer_Group` text NOT NULL,
  `RespitePayer` text NOT NULL,
  `Address` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zipcode` text NOT NULL,
  `Phone` text NOT NULL,
  `Intake_Status` text NOT NULL,
  `Intake_Date` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Referral_Date` text NOT NULL,
  `Intake_Coordinator` text NOT NULL,
  `Business_Developer` text NOT NULL,
  `Meeting_Nurses` text NOT NULL,
  `Patient_DOB` text NOT NULL,
  `WorkForce_DevProj` text NOT NULL,
  `Is_Priority` text NOT NULL,
  `Is_Intake` text NOT NULL,
  `NTHNotes` text NOT NULL,
  `Covid19_Vaccine_Required` text NOT NULL,
  `Media_Consent` text NOT NULL,
  `NonSmoking_Nurses` text NOT NULL,
  `Smokers_LiveIn_Home` text NOT NULL,
  `CreatedBy` text NOT NULL,
  `Checklist_Bit` text NOT NULL,
  `School_District` text NOT NULL,
  `School_Name` text NOT NULL,
  `Orders_Received` text NOT NULL,
  `Count_of_IPEAs_Schedules` text NOT NULL,
  `Hours_for_IPEAs_Scheduled` text NOT NULL,
  `Count_of_PEPIs_Schedules` text NOT NULL,
  `Hours_for_PEPIs_Scheduled` text NOT NULL,
  `Count_of_WEPIs_Schedules` text NOT NULL,
  `Hours_for_WEPIs_Scheduled` text NOT NULL,
  `Count_of_CM_Scheduled` text NOT NULL,
  `Hours_for_CM_Scheduled` text NOT NULL,
  `The_last_escalated_rate` text NOT NULL,
  `Last_Escalation` text NOT NULL,
  `TextBlastContent` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map63`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map63` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `Non_VBC` text NOT NULL,
  `City` text NOT NULL,
  `Schedule` text NOT NULL,
  `Schedule_Details` text NOT NULL,
  `Payer` text NOT NULL,
  `NTH_Hours` text NOT NULL,
  `Nurse_Language` text NOT NULL,
  `Specialized` text NOT NULL,
  `Skills` text NOT NULL,
  `Lifting_Required` text NOT NULL,
  `Age` text NOT NULL,
  `Gender` text NOT NULL,
  `Weight` text NOT NULL,
  `Attending_School` text NOT NULL,
  `Language` text NOT NULL,
  `Pets_in_Home` text NOT NULL,
  `Pets_Nurse_Cannot_Own` text NOT NULL,
  `Service_Needed` text NOT NULL,
  `Biography` text NOT NULL,
  `The_Sale` text NOT NULL,
  `Clinician_Gender` text NOT NULL,
  `Clinician_Discipline` text NOT NULL,
  `Intake_Status` text NOT NULL,
  `Intake_Date` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Address` text NOT NULL,
  `State` text NOT NULL,
  `Zipcode` text NOT NULL,
  `Location` text NOT NULL,
  `Phone` text NOT NULL,
  `Created_On` text NOT NULL,
  `Created_Reason` text NOT NULL,
  `Meeting_Nurses` text NOT NULL,
  `WFD_Client` text NOT NULL,
  `The_NTH_Notes` text NOT NULL,
  `Covid_Vaccine_Required` text NOT NULL,
  `Media_Consent` text NOT NULL,
  `NonSmoking_Nurses` text NOT NULL,
  `Smokers_Live_in_Home` text NOT NULL,
  `Type_of_Shift` text NOT NULL,
  `PDN_Auth_Hours` text NOT NULL,
  `Respite_Auth_Hours` text NOT NULL,
  `CNA_Auth_Hours` text NOT NULL,
  `NTH_Schedule` text NOT NULL,
  `Recruiter` text NOT NULL,
  `Intake_Coordinator` text NOT NULL,
  `Client_Care_Coordinator` text NOT NULL,
  `Notes2` text NOT NULL,
  `Intake` text NOT NULL,
  `Client_ID` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map65`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map65` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `non_vbc` text NOT NULL,
  `City` text NOT NULL,
  `Schedule` text NOT NULL,
  `Schedule_Details` text NOT NULL,
  `Payer` text NOT NULL,
  `NTH_Hours` text NOT NULL,
  `Nurse_Language` text NOT NULL,
  `Specialized` text NOT NULL,
  `Skills` text NOT NULL,
  `Liftingrequirements` text NOT NULL,
  `Age` text NOT NULL,
  `Gender` text NOT NULL,
  `Weight` text NOT NULL,
  `Attending_School` text NOT NULL,
  `Language` text NOT NULL,
  `Pets_in_Home` text NOT NULL,
  `Pets_Nurse_Cannot_Own` text NOT NULL,
  `Service_Needed` text NOT NULL,
  `Biography` text NOT NULL,
  `The_Sale` text NOT NULL,
  `Clinician_Gender` text NOT NULL,
  `Clinician_Discipline` text NOT NULL,
  `Intake_Status` text NOT NULL,
  `Intake_Date` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Address` text NOT NULL,
  `State` text NOT NULL,
  `Zipcode` text NOT NULL,
  `Location` text NOT NULL,
  `Phone` text NOT NULL,
  `CreatedOn` text NOT NULL,
  `CreatedReason` text NOT NULL,
  `Meeting_Nurses` text NOT NULL,
  `WFD_Client` text NOT NULL,
  `Notes` text NOT NULL,
  `Covid_Vaccine_Required` text NOT NULL,
  `MediaConsent` text NOT NULL,
  `NonSmoking_Nurses` text NOT NULL,
  `Smokers_Live_in_Home` text NOT NULL,
  `Type_of_Shift` text NOT NULL,
  `PDN_Auth_Hrs` text NOT NULL,
  `Respite_Auth_Hrs` text NOT NULL,
  `CNA_Auth_Hrs` text NOT NULL,
  `NTH_Schedule` text NOT NULL,
  `Recruiter` text NOT NULL,
  `Intake_Coordinator` text NOT NULL,
  `Client_Care_Coordinator` text NOT NULL,
  `Intake` text NOT NULL,
  `ClientID` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map66`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map66` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Branch` text NOT NULL,
  `Address` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map67`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map67` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date` text NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Patient_ID` text NOT NULL,
  `SOC` text NOT NULL,
  `Episode` text NOT NULL,
  `Payer` text NOT NULL,
  `Discipline` text NOT NULL,
  `Service` text NOT NULL,
  `Schedule_Time` text NOT NULL,
  `Hours` text NOT NULL,
  `Authorization` text NOT NULL,
  `Client_Address` text NOT NULL,
  `Client_County` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_ZipCode` text NOT NULL,
  `Locations` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map68`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map68` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date` text NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Patient_ID` text NOT NULL,
  `SOC` text NOT NULL,
  `Episode` text NOT NULL,
  `Payer` text NOT NULL,
  `Discipline` text NOT NULL,
  `Service` text NOT NULL,
  `Schedule_Time` text NOT NULL,
  `Hours` text NOT NULL,
  `Authorization` text NOT NULL,
  `Client_Address` text NOT NULL,
  `Client_County` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_ZipCode` text NOT NULL,
  `Locations` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map69`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map69` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Branch` text NOT NULL,
  `Address` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map70`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map70` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Location` text NOT NULL,
  `Branch` text NOT NULL,
  `Address` text NOT NULL,
  `City` varchar(100) NOT NULL,
  `State` varchar(100) NOT NULL,
  `Zip` varchar(100) NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map71`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map71` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `This_is_a_test_upload` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map74`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map74` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Member_Name` text NOT NULL,
  `Age_(years)` text NOT NULL,
  `Phone_Number(s)` text NOT NULL,
  `Medicaid_ID` text NOT NULL,
  `SDA` text NOT NULL,
  `address1` text NOT NULL,
  `Members_Language_Preference` text NOT NULL,
  `Outstanding_Therapy_Requests` text NOT NULL,
  `Requested_time_for_services` text NOT NULL,
  `Diagnosis` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map76`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map76` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Age` text NOT NULL,
  `Client_Insurance_` text NOT NULL,
  `Client_Insurance_ID` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Client_Treatment_Address` text NOT NULL,
  `Client_Treatment_Zip_Code` text NOT NULL,
  `Client__Language_` text NOT NULL,
  `Discipline` text NOT NULL,
  `Availability` text NOT NULL,
  `Referral_Facility` varchar(500) DEFAULT NULL,
  `Diagnosis` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map77`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map77` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Age` text NOT NULL,
  `Client_Insurance_` text NOT NULL,
  `Client_Insurance_ID` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Client_Treatment_Address` text NOT NULL,
  `Client_Treatment_Zip_Code` text NOT NULL,
  `Client__Language_` text NOT NULL,
  `Discipline` text NOT NULL,
  `Availability` text NOT NULL,
  `Diagnosis` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map78`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map78` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Age` text NOT NULL,
  `Client_Insurance_` text NOT NULL,
  `Client_Insurance_ID` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Client_Treatment_Address` text NOT NULL,
  `Client_Treatment_Zip_Code` text NOT NULL,
  `Client__Language_` text NOT NULL,
  `Discipline` text NOT NULL,
  `Availability` text NOT NULL,
  `Referral_Facility` text NOT NULL,
  `Diagnosis` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map79`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map79` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Age` text NOT NULL,
  `Client_Insurance_` text NOT NULL,
  `Client_Insurance_ID` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Client_Treatment_Address` text NOT NULL,
  `Client_Treatment_City` text NOT NULL,
  `Client_Treatment_State` text NOT NULL,
  `Client_Treatment_Zip_Code` text NOT NULL,
  `Client__Language` text NOT NULL,
  `Discipline` text NOT NULL,
  `Availability` text NOT NULL,
  `Referral_Facility` text NOT NULL,
  `Diagnosis` text NOT NULL,
  `Client_Notes` text DEFAULT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map80`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map80` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Age/_DOB` text NOT NULL,
  `Client_Insurance_` text NOT NULL,
  `Client_Insurance_ID` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Client_Treatment_Address` text NOT NULL,
  `Client_Treatment_City` text NOT NULL,
  `Client_Treatment_State` text NOT NULL,
  `Client_Treatment_Zip_Code` text NOT NULL,
  `Client__Language` text NOT NULL,
  `Discipline` text NOT NULL,
  `Availability` text NOT NULL,
  `Referral_Facility` text NOT NULL,
  `Diagnosis` text NOT NULL,
  `Client_Notes` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map81`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map81` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `License_Number` text NOT NULL,
  `Name` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Status` text NOT NULL,
  `Issue_Date` text NOT NULL,
  `Expiry_Date` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map82`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map82` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Board` text NOT NULL,
  `Profession` text NOT NULL,
  `Rank` text NOT NULL,
  `LastName` text NOT NULL,
  `FirstName` text NOT NULL,
  `MiddleName` text NOT NULL,
  `Title` text NOT NULL,
  `LicenseNumber` text NOT NULL,
  `ExpirationDate` text NOT NULL,
  `OriginalDate` text NOT NULL,
  `Status` text NOT NULL,
  `LicenseActivity` text NOT NULL,
  `StatusEffectiveDate` text NOT NULL,
  `Disciplined` text NOT NULL,
  `Gender` text NOT NULL,
  `Race` text NOT NULL,
  `ModifierDescription` text NOT NULL,
  `ModifierType` text NOT NULL,
  `PracticeName` text NOT NULL,
  `PracticeAddress` text NOT NULL,
  `PracticeAddress2` text NOT NULL,
  `PracticeCity` text NOT NULL,
  `PracticeState` text NOT NULL,
  `PracticeZIP` text NOT NULL,
  `PracticeAreaCode` text NOT NULL,
  `PracticePhoneNumber` text NOT NULL,
  `PracticeExtension` text NOT NULL,
  `PracticeCounty` text NOT NULL,
  `DegreeEarned` text NOT NULL,
  `EducationProvider` text NOT NULL,
  `EducationProviderLocation` text NOT NULL,
  `GraduationDate` text NOT NULL,
  `OtherTrainingProvider` text NOT NULL,
  `OtherTrainingLocation` text NOT NULL,
  `OtherTrainingFromDate` text NOT NULL,
  `OtherTrainingEndDate` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map83`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map83` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `Payer` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Episode_Number` text NOT NULL,
  `Episode_Start_Date` text NOT NULL,
  `Episode_End_Date` text NOT NULL,
  `Admit_No` text NOT NULL,
  `Status` text NOT NULL,
  `Referred_By` text NOT NULL,
  `Referred_Physician_First_Name` text NOT NULL,
  `Referred_Physician_Last_Name` text NOT NULL,
  `Primary_Clinician` text NOT NULL,
  `Primary_Clinician_First_Name` text NOT NULL,
  `Primary_Clinician_Last_Name` text NOT NULL,
  `Address_1` text NOT NULL,
  `Address_2` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_State` text NOT NULL,
  `Client_Zip_Code` text NOT NULL,
  `LOB_Name` text NOT NULL,
  `Last_RECERT_Date` text NOT NULL,
  `Services_Needed` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map84`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map84` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client` text NOT NULL,
  `Payer` text NOT NULL,
  `SOC_Date` text NOT NULL,
  `Episode_Number` text NOT NULL,
  `Episode_Start_Date` text NOT NULL,
  `Episode_End_Date` text NOT NULL,
  `Admit_No` text NOT NULL,
  `Status` text NOT NULL,
  `Referred_By` text NOT NULL,
  `Referred_Physician_First_Name` text NOT NULL,
  `Referred_Physician_Last_Name` text NOT NULL,
  `Primary_Clinician` text NOT NULL,
  `Primary_Clinician_First_Name` text NOT NULL,
  `Primary_Clinician_Last_Name` text NOT NULL,
  `Address_1` text NOT NULL,
  `Address_2` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_State` text NOT NULL,
  `Client_Zip_Code` text NOT NULL,
  `Client_Location` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map85`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map85` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `First_Name` text NOT NULL,
  `Last_Name` text NOT NULL,
  `Address_1` text NOT NULL,
  `Address_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `Census` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Number` text NOT NULL,
  `License_Status` text NOT NULL,
  `License_Exp_Date` text NOT NULL,
  `License_Issue_Date` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map86`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map86` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Applicant_First_Name` text NOT NULL,
  `Applicant_Last_Name` text NOT NULL,
  `License:_Number` text NOT NULL,
  `Type` text NOT NULL,
  `Status` text NOT NULL,
  `Board_Action` text NOT NULL,
  `Issue_Date` text NOT NULL,
  `Renewed_Date` text NOT NULL,
  `Expiration_Date` text NOT NULL,
  `Applicant_Email` text NOT NULL,
  `Mailing_Address_Street` text NOT NULL,
  `Mailing_Address_City` text NOT NULL,
  `Public_Address_-_County` text NOT NULL,
  `Mailing_Address_State` text NOT NULL,
  `Mailing_Address_Zip` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map87`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map87` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `License_Number` text NOT NULL,
  `Licensee_Name` text NOT NULL,
  `Public_Status` text NOT NULL,
  `Issue_Date` text NOT NULL,
  `Expiration_Date` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `County` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map88`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map88` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LicenseNumber` text NOT NULL,
  `FullName` text NOT NULL,
  `Prerequisite_FullName` text NOT NULL,
  `AddressLine1` text NOT NULL,
  `AddressLine2` text NOT NULL,
  `AddressLine3` text NOT NULL,
  `PCity` text NOT NULL,
  `PState` text NOT NULL,
  `PZipcode` text NOT NULL,
  `PCounty` text NOT NULL,
  `LIssueDate` text NOT NULL,
  `LExpirationDate` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map89`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map89` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `License_Number` text NOT NULL,
  `Name` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Status` text NOT NULL,
  `Issue_Date` text NOT NULL,
  `Expiry_Date` text NOT NULL,
  `Address` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map90`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map90` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `Section` text NOT NULL,
  `Date_Added` text NOT NULL,
  `Expected_1st_Bill_Date` text NOT NULL,
  `Scheduler` text NOT NULL,
  `Total_Hours(30_Days)` text NOT NULL,
  `State` text NOT NULL,
  `Staffing_Status` text NOT NULL,
  `Service` text NOT NULL,
  `Referral_Type` text NOT NULL,
  `Client_Age` text NOT NULL,
  `Client_Type` text NOT NULL,
  `GA_Caregiver_Onboarding` text NOT NULL,
  `Open_to_Staff` text NOT NULL,
  `Sunday-Start` text NOT NULL,
  `Sunday-End` text NOT NULL,
  `Monday-Start` text NOT NULL,
  `Monday-End` text NOT NULL,
  `Tuesday-Start` text NOT NULL,
  `Tuesday-End` text NOT NULL,
  `Wednesday-Start` text NOT NULL,
  `Wednesday-End` text NOT NULL,
  `Thursday-Start` text NOT NULL,
  `Thursday-End` text NOT NULL,
  `Friday-Start` text NOT NULL,
  `Friday-End` text NOT NULL,
  `Saturday-Start` text NOT NULL,
  `Saturday-End` text NOT NULL,
  `Client_Address` text NOT NULL,
  `City` text NOT NULL,
  `Client_State` text NOT NULL,
  `Client_Zip` text NOT NULL,
  `Item_ID` text NOT NULL,
  `Client_Contact_Full_Name` text NOT NULL,
  `Client_City` text NOT NULL,
  `TA_Partners` text NOT NULL,
  `Active_Employee_Directory` text NOT NULL,
  `Days_Open` text NOT NULL,
  `Mirror` text NOT NULL,
  `Sunday_Hours` text NOT NULL,
  `Monday_Hours` text NOT NULL,
  `Tuesday_Hours` text NOT NULL,
  `Wednesday_Hours` text NOT NULL,
  `Thursday_Hours` text NOT NULL,
  `Friday_Hours` text NOT NULL,
  `Saturday_Hours` text NOT NULL,
  `Total_Week` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_configurations` (
  `map_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `map_tablename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `map_name` varchar(255) DEFAULT '',
  `mapList` varchar(100) NOT NULL,
  `map_category` varchar(255) DEFAULT '',
  `map_type` varchar(255) DEFAULT '',
  `map_filters` text DEFAULT NULL,
  `enable_address_filter` text DEFAULT NULL,
  `map_select_preference` int(11) DEFAULT NULL,
  `map_data_preference` text DEFAULT NULL,
  `map_boolean_field` text DEFAULT NULL,
  `map_default_filter` text DEFAULT NULL,
  `map_preferences` text DEFAULT NULL,
  `map_search_radius` text DEFAULT NULL,
  `map_search_fields` text DEFAULT NULL,
  `fields_on_map` text DEFAULT NULL,
  `fields_datatable` text DEFAULT NULL,
  `fields_datatable_detail` text DEFAULT NULL,
  `default_orderby_datatable` text DEFAULT NULL,
  `latlong_fields` text DEFAULT NULL,
  `latlong_fields2` text DEFAULT NULL,
  `map_status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-Disable,1-Enable',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `unique_identifier_col` text DEFAULT NULL,
  `is_protected` enum('0','1') NOT NULL DEFAULT '0',
  `description` longtext DEFAULT NULL,
  `last_uploaded_at` timestamp NULL DEFAULT NULL,
  `last_uploaded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `pin_icon` varchar(255) DEFAULT NULL,
  `pin_color` varchar(255) DEFAULT NULL,
  `layer_ring_radius` smallint(5) unsigned DEFAULT NULL,
  `ring_layer_id` bigint(20) unsigned DEFAULT NULL,
  `show_rings_by_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`map_id`),
  KEY `map_configurations_last_uploaded_at_index` (`last_uploaded_at`),
  KEY `map_configurations_last_uploaded_by_user_id_index` (`last_uploaded_by_user_id`),
  KEY `map_configurations_parent_id_foreign` (`parent_id`),
  KEY `map_configurations_ring_layer_id_foreign` (`ring_layer_id`),
  CONSTRAINT `map_configurations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `map_configurations` (`map_id`),
  CONSTRAINT `map_configurations_ring_layer_id_foreign` FOREIGN KEY (`ring_layer_id`) REFERENCES `map_configurations` (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_ignore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_ignore` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(11) DEFAULT NULL,
  `unique_identifier` text DEFAULT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_az`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_az` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FirstName` text NOT NULL,
  `LastName` text NOT NULL,
  `Address` text NOT NULL,
  `City` text NOT NULL,
  `StateProvince` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_az_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_co`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_co` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Formatted_Name` text NOT NULL,
  `Attention` text NOT NULL,
  `Address_Line_1` text NOT NULL,
  `Address_Line_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `County` text NOT NULL,
  `Mail_Zip_Code` text NOT NULL,
  `Mail_Zip_Code_+_4` text NOT NULL,
  `License_Type` text NOT NULL,
  `Sub_Category` text NOT NULL,
  `License_Number` text NOT NULL,
  `Nurse_Compact_Designation` text NOT NULL,
  `License_First_Issue_Date` text NOT NULL,
  `License_Last_Renewed_Date` text NOT NULL,
  `License_Expiration_Date` text NOT NULL,
  `License_Status_Description` text NOT NULL,
  `Specialty` text NOT NULL,
  `Title` text NOT NULL,
  `Degree(s)` text NOT NULL,
  `Case_Number` text NOT NULL,
  `Program_Action` text NOT NULL,
  `Discipline_Effective_Date` text NOT NULL,
  `Discipline_Complete_Date` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_co_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_fl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_fl` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `Rank-Code` text NOT NULL,
  `Discipline` text NOT NULL,
  `License-Number` text NOT NULL,
  `County-Description` text NOT NULL,
  `Mailing-Address-Line1` text NOT NULL,
  `Mailing-Address-line2` text NOT NULL,
  `Mailing-Address-City` text NOT NULL,
  `Mailing-Address-State` text NOT NULL,
  `Mailing-Address-ZIPcode` text NOT NULL,
  `Phone_Number` text NOT NULL,
  `Email` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_fl_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_nc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_nc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `Address_1` text NOT NULL,
  `Address_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `County` text NOT NULL,
  `Email` text NOT NULL,
  `Practice_Setting` text NOT NULL,
  `Position` text NOT NULL,
  `Field` text NOT NULL,
  `Basic_Degree` text NOT NULL,
  `Highest_Degree` text NOT NULL,
  `Certification_#` text NOT NULL,
  `Originally_Licensed` text NOT NULL,
  `Expiration_Date` text NOT NULL,
  `Method` text NOT NULL,
  `Compact_State` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `certification` varchar(100) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_nc_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_pa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_pa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LicenseNumber` text NOT NULL,
  `Prerequisite_FullName` text NOT NULL,
  `AddressLine1` text NOT NULL,
  `AddressLine2` text NOT NULL,
  `AddressLine3` text NOT NULL,
  `PCity` text NOT NULL,
  `PState` text NOT NULL,
  `PZipcode` text NOT NULL,
  `PCounty` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_pa_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_sc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_sc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Prefix` text NOT NULL,
  `Credential_Num` text NOT NULL,
  `Name` text NOT NULL,
  `Expiration_Date` text NOT NULL,
  `Address1` text NOT NULL,
  `Address2` text NOT NULL,
  `City` text NOT NULL,
  `County_Name` text NOT NULL,
  `State_Code` text NOT NULL,
  `ZIPCode` text NOT NULL,
  `Sub_Category` text NOT NULL,
  `Description` text NOT NULL,
  `Form_Of__Practice` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_sc_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bon_tx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bon_tx` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `License_Number` text NOT NULL,
  `Name` text NOT NULL,
  `Middle_Name` text NOT NULL,
  `Mailing_Address_Line_1` text NOT NULL,
  `Mailing_Address_Line_2` text NOT NULL,
  `Mailing_Address_Foreign_Line` text NOT NULL,
  `Mailing_City` text NOT NULL,
  `Mailing_State` text NOT NULL,
  `Mailing_Zip` text NOT NULL,
  `Region` text NOT NULL,
  `Gender` text NOT NULL,
  `Ethnicity` text NOT NULL,
  `License_Status` text NOT NULL,
  `County_of_Residence` text NOT NULL,
  `Employment_Status` text NOT NULL,
  `Current_Board_Action_(Y/N)` text NOT NULL,
  `Discipline` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bon_tx_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_coot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_coot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Formatted_Name` text NOT NULL,
  `Address_Line_1` text NOT NULL,
  `Address_Line_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `County` text NOT NULL,
  `Mail_Zip_Code` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Number` text NOT NULL,
  `License_Status_Description` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_copt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_copt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Formatted_Name` text NOT NULL,
  `Address_Line_1` text NOT NULL,
  `Address_Line_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `County` text NOT NULL,
  `Mail_Zip_Code` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Number` text NOT NULL,
  `License_Status_Description` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_cost` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Formatted_Name` text NOT NULL,
  `Address_Line_1` text NOT NULL,
  `Address_Line_2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `County` text NOT NULL,
  `Mail_Zip_Code` text NOT NULL,
  `License_Type` text NOT NULL,
  `License_Number` text NOT NULL,
  `License_Status_Description` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='test';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_txot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_txot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LastName` text NOT NULL,
  `FirstName` text NOT NULL,
  `MiddleName` text NOT NULL,
  `Suffix` text NOT NULL,
  `AddressLine1` text NOT NULL,
  `AddressLine2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Province` text NOT NULL,
  `Country` text NOT NULL,
  `LicenseType` text NOT NULL,
  `LicenseStatus` text NOT NULL,
  `LicenseNumber` text NOT NULL,
  `IssueDate` text NOT NULL,
  `ExpirationDate` text NOT NULL,
  `EmailAddress` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bot_txot_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_txpt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_txpt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LastName` text NOT NULL,
  `FirstName` text NOT NULL,
  `MiddleName` text NOT NULL,
  `Suffix` text NOT NULL,
  `AddressLine1` text NOT NULL,
  `AddressLine2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Province` text NOT NULL,
  `Country` text NOT NULL,
  `LicenseType` text NOT NULL,
  `LicenseStatus` text NOT NULL,
  `LicenseNumber` text NOT NULL,
  `IssueDate` text NOT NULL,
  `ExpirationDate` text NOT NULL,
  `EmailAddress` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_bot_txpt_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_bot_txst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_bot_txst` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LICENSE_TYPE_DESC` text NOT NULL,
  `LICENSE_NBR` text NOT NULL,
  `LICENSEE` text NOT NULL,
  `ADDRESS_LINE1` text NOT NULL,
  `CITY` text NOT NULL,
  `STATE` text NOT NULL,
  `ZIP` text NOT NULL,
  `PHONE` text NOT NULL,
  `COUNTY_DESC` text NOT NULL,
  `EXPIRATION_DTE` text NOT NULL,
  `ISSUE_DATE` text NOT NULL,
  `STATUS` text NOT NULL,
  `bg_accuracy` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-No,1-Yes',
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_branch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Branch` text NOT NULL,
  `Address` text NOT NULL,
  `StreetAddress` varchar(250) NOT NULL,
  `City` varchar(100) NOT NULL,
  `State` text NOT NULL,
  `ZipCode` varchar(50) NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_cclist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_cclist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Primary_Clinician_First_Name` text NOT NULL,
  `Primary_Clinician_Last_Name` text NOT NULL,
  `Clinician_FullName` varchar(150) NOT NULL,
  `Staff_Status` text NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Client_FullName` varchar(250) NOT NULL,
  `Client_Age_(Years)` text NOT NULL,
  `Client_Zip_Code` text NOT NULL,
  `Discipline` text NOT NULL,
  `Staff_Phone` text NOT NULL,
  `Client_Address1` text NOT NULL,
  `Client_Address2` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_LOB` text NOT NULL,
  `Payer` text NOT NULL,
  `Frequency_(initial)` text NOT NULL,
  `Staff_Team` text NOT NULL,
  `Staff_Zip` text NOT NULL,
  `Frequency_(Final)` text NOT NULL,
  `Secondary_Payer` text NOT NULL,
  `Case_Manager` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_clients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PatientName` text NOT NULL,
  `PatientAccount` text NOT NULL,
  `PatientStatus` text NOT NULL,
  `Patient_Address1` text NOT NULL,
  `Patient_Address2` text NOT NULL,
  `Patient_City` text NOT NULL,
  `Patient_State` text NOT NULL,
  `Patient_Zip` text NOT NULL,
  `FacilityName` text NOT NULL,
  `CaseManager` text NOT NULL,
  `Scheduler` text NOT NULL,
  `Specialized` text NOT NULL,
  `LOB` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_clients_needs_geocoding` (`needs_geocoding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_coldcall`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_coldcall` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Clinician_ID` text NOT NULL,
  `Provider_Name` text NOT NULL,
  `Period_Hours` text NOT NULL,
  `#_of_Weeks_in_Period` text NOT NULL,
  `Weekly_Average` text NOT NULL,
  `Period_Start_Date` text NOT NULL,
  `Period_End_Date` text NOT NULL,
  `Provider_Status` text NOT NULL,
  `Dicipline` text NOT NULL,
  `Language` text NOT NULL,
  `Provider_is_Bilingual` text NOT NULL,
  `Pay_Type` text NOT NULL,
  `Gender` text NOT NULL,
  `Address1` text NOT NULL,
  `Address2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Phone` text NOT NULL,
  `Mobile` text NOT NULL,
  `Provider_Home_Branch` text NOT NULL,
  `AOC_Email` text NOT NULL,
  `External_Email` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_covclin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_covclin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ClinicianID` varchar(50) NOT NULL,
  `ProviderID` text NOT NULL,
  `FirstName` text NOT NULL,
  `LastName` text NOT NULL,
  `Address1` text NOT NULL,
  `Address2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Mobile` text NOT NULL,
  `Phone` text NOT NULL,
  `Email` text NOT NULL,
  `Gender` text NOT NULL,
  `FacilityName` text NOT NULL,
  `DisciplineName` text NOT NULL,
  `JobTitle` text NOT NULL,
  `EmploymentStatus` text NOT NULL,
  `Language` text NOT NULL,
  `Hours` text NOT NULL,
  `AOC_Email` varchar(100) NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `PatientID` varchar(255) NOT NULL,
  `Patient_FirstName` varchar(255) NOT NULL,
  `Patient_LastName` varchar(255) NOT NULL,
  `Shift_Type` varchar(255) NOT NULL,
  `Days_Available` varchar(255) NOT NULL,
  `aoc_patient_id` int(11) NOT NULL,
  `aoc_provider_id` int(11) NOT NULL,
  `custom_sort_prn` tinyint(4) NOT NULL DEFAULT 0,
  `tracker_id` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_covclin_providerid` (`ProviderID`(1024))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_coverage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_coverage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date` text NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Column1` text NOT NULL,
  `Payer` text NOT NULL,
  `Discipline` text NOT NULL,
  `Service` text NOT NULL,
  `Schedule_Time` text NOT NULL,
  `Hours` text NOT NULL,
  `Authorization` text NOT NULL,
  `Client_Address` text NOT NULL,
  `Client_County` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_ZipCode` text NOT NULL,
  `State` text NOT NULL,
  `Locations` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_coverageneeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_coverageneeds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date` text NOT NULL,
  `filterDate` char(10) NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Name` text NOT NULL,
  `Payer` text DEFAULT NULL,
  `Discipline` text NOT NULL,
  `Service` text NOT NULL,
  `Schedule_Time` text NOT NULL,
  `Hours` text NOT NULL,
  `Authorization` text NOT NULL,
  `CF_Weight` varchar(50) DEFAULT NULL,
  `Male_Nurse_Accepted` varchar(3) DEFAULT NULL,
  `Pets_in_Home` varchar(255) DEFAULT NULL,
  `Non_Smoking` varchar(3) DEFAULT NULL,
  `Covid_Vaccine` varchar(3) DEFAULT NULL,
  `Lift_Available` varchar(100) DEFAULT NULL,
  `Attends_School` varchar(3) DEFAULT NULL,
  `Client_Address` text NOT NULL,
  `Client_County` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_ZipCode` text NOT NULL,
  `State` text NOT NULL,
  `Locations` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Patient_ID` varchar(50) NOT NULL,
  `is_updated` int(1) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `aoc_patient_id` int(11) NOT NULL DEFAULT 0,
  `shift_date` date DEFAULT NULL,
  `last_activity_date` timestamp NULL DEFAULT NULL,
  `last_activity_by` int(11) NOT NULL DEFAULT 0,
  `last_activity` varchar(255) DEFAULT NULL,
  `text_call_client_schedule_regulars_activity_date` datetime DEFAULT NULL,
  `text_call_record_clinicians_activity_date` datetime DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_activity_date` datetime DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `call_nurses_from_text_blast_map_activity_date` datetime DEFAULT NULL,
  `check_cover_nurses_availability_activity_date` datetime DEFAULT NULL,
  `text_call_client_schedule_regulars_number` int(11) DEFAULT NULL,
  `text_call_record_clinicians_number` int(11) DEFAULT NULL,
  `text_call_prn_clinicians_from_local_prn_schedule_number` int(11) DEFAULT NULL,
  `text_blast_180_mile_radius_from_text_blast_map_number` int(11) DEFAULT NULL,
  `call_nurses_from_text_blast_map_number` int(11) DEFAULT NULL,
  `check_cover_nurses_availability_number` varchar(100) DEFAULT NULL,
  `intake` varchar(10) NOT NULL DEFAULT 'No',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `coverageneeds_transform` (`Date`(20),`Name`(100),`Schedule_Time`(200)),
  KEY `idx_maps_coverageneeds_needs_geocoding` (`needs_geocoding`),
  KEY `idx_fk_maps_coverageneeds_aoc_patient_id` (`aoc_patient_id`),
  KEY `idxr_maps_coverageneeds_coverageneeds_patientid` (`Patient_ID`),
  KEY `idxr_maps_coverageneeds_schedule_time` (`Schedule_Time`(1024)),
  KEY `idxr_maps_coverageneeds_shift_date` (`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_nth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_nth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` bigint(20) NOT NULL,
  `NeedToHireAccountsID` varchar(100) DEFAULT NULL,
  `ClientID` varchar(50) DEFAULT NULL,
  `Client` varchar(250) DEFAULT NULL,
  `NTH_Status` varchar(50) DEFAULT NULL,
  `Type_of_Shift` text DEFAULT NULL,
  `Schedule_Details` text DEFAULT NULL,
  `Schedule` varchar(200) DEFAULT NULL,
  `ScheduleDetailsBak` text DEFAULT NULL,
  `NTH_Hours` text DEFAULT NULL,
  `PDN_Auth_Hrs` text DEFAULT NULL,
  `Respite_Auth_Hrs` text DEFAULT NULL,
  `CNA_Auth_Hrs` text DEFAULT NULL,
  `Age` text DEFAULT NULL,
  `Gender` text DEFAULT NULL,
  `Weight` text DEFAULT NULL,
  `Biography` text DEFAULT NULL,
  `The_Sale` text DEFAULT NULL,
  `Attending_School` text DEFAULT NULL,
  `Language` text DEFAULT NULL,
  `Nurse_Language` text DEFAULT NULL,
  `Pets_in_Home` text DEFAULT NULL,
  `Pets_Nurse_Cannot_Own` text DEFAULT NULL,
  `Liftingrequirements` text DEFAULT NULL,
  `Specialized` text DEFAULT NULL,
  `Skills` text DEFAULT NULL,
  `Service_Needed` text DEFAULT NULL,
  `Clinician_Gender` text DEFAULT NULL,
  `Clinician_Discipline` text DEFAULT NULL,
  `Payer` text DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `Respite_Payer` text DEFAULT NULL,
  `Intake` text DEFAULT NULL,
  `Intake_Status` text DEFAULT NULL,
  `Intake_Date` text DEFAULT NULL,
  `SOC_Date` text DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `City` text DEFAULT NULL,
  `State` text DEFAULT NULL,
  `Zipcode` text DEFAULT NULL,
  `Location` text DEFAULT NULL,
  `Phone` text DEFAULT NULL,
  `CreatedOn` text DEFAULT NULL,
  `CreatedReason` text DEFAULT NULL,
  `Meeting_Nurses` text DEFAULT NULL,
  `WFD_Client` text DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `Covid_Vaccine_Required` text DEFAULT NULL,
  `MediaConsent` text DEFAULT NULL,
  `NonSmoking_Nurses` text DEFAULT NULL,
  `Smokers_Live_in_Home` text DEFAULT NULL,
  `Client_Care_Coordinator` varchar(100) DEFAULT NULL,
  `Days_on_NTH` varchar(25) DEFAULT NULL,
  `Recruiter` varchar(100) DEFAULT NULL,
  `NTH_Schedule` text DEFAULT NULL,
  `Referral_Date` text DEFAULT NULL,
  `Assessment_Case_Manager` varchar(100) DEFAULT NULL,
  `Auth_Case_Manager` varchar(100) DEFAULT NULL,
  `Intake_Coordinator` varchar(100) DEFAULT NULL,
  `BD_Rep` varchar(100) DEFAULT NULL,
  `MeetingNursesBit` text DEFAULT NULL,
  `Client_DOB` text DEFAULT NULL,
  `Priority` varchar(1) DEFAULT NULL,
  `IsActive` varchar(1) DEFAULT NULL,
  `FacilityID` varchar(50) DEFAULT NULL,
  `HubID` varchar(50) DEFAULT NULL,
  `Hub` varchar(100) DEFAULT NULL,
  `MatchInEMR` varchar(5) DEFAULT NULL,
  `SpecializedclientBIT` varchar(1) DEFAULT NULL,
  `ChecklistBit` varchar(1) DEFAULT NULL,
  `CreatedBy` varchar(200) DEFAULT NULL,
  `ModifiedOn` varchar(23) DEFAULT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `lng` varchar(255) DEFAULT NULL,
  `lat2` varchar(255) DEFAULT NULL,
  `lng2` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` tinyint(1) DEFAULT NULL,
  `needs_geocoding` int(1) DEFAULT NULL,
  `is_ignored` tinyint(1) DEFAULT 0,
  `has_payer_group` int(11) DEFAULT 0,
  `isClientChangingPayer` tinyint(1) NOT NULL DEFAULT 0,
  `newTerminalPayer` varchar(100) DEFAULT NULL,
  `payerEffectiveDates` date DEFAULT NULL,
  `newRespiteTerminalPayer` varchar(50) DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_maps_nth_aoc_nthaccounts_id` (`aoc_nthaccounts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_nthalerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_nthalerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ClinicianID` varchar(255) DEFAULT NULL,
  `ProviderName` varchar(255) DEFAULT NULL,
  `Discipline` varchar(255) DEFAULT NULL,
  `MobilePhone` varchar(255) DEFAULT NULL,
  `ExternaleEmail` varchar(255) DEFAULT NULL,
  `ProviderStatus` varchar(255) DEFAULT NULL,
  `Gender` varchar(255) DEFAULT NULL,
  `WeeklyAverage` double DEFAULT NULL,
  `IsBilingual` varchar(255) DEFAULT NULL,
  `HomeBranch` varchar(255) DEFAULT NULL,
  `Address1` varchar(255) DEFAULT NULL,
  `Address2` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `State` varchar(255) DEFAULT NULL,
  `Zip` varchar(255) DEFAULT NULL,
  `Language` varchar(255) DEFAULT NULL,
  `City_Preference` varchar(255) DEFAULT NULL,
  `State_Preference` varchar(255) DEFAULT NULL,
  `SMS_Status` varchar(255) NOT NULL,
  `Mile_Preference` varchar(255) DEFAULT NULL,
  `Schedule_Preference` varchar(255) DEFAULT NULL,
  `Skill_Preference` varchar(255) DEFAULT NULL,
  `Environment_Preference` varchar(255) DEFAULT NULL,
  `Lifting_Preference` varchar(255) DEFAULT NULL,
  `Language_Preference` varchar(255) DEFAULT NULL,
  `Subscription_Status` varchar(255) DEFAULT NULL,
  `Preference_Status` varchar(255) DEFAULT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `lng` varchar(255) DEFAULT NULL,
  `needs_geocoding` tinyint(1) DEFAULT NULL,
  `is_ignored` smallint(6) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT NULL,
  `weekly_sms_attempts` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_nthalerts_ClinicianID` (`ClinicianID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_potential_nurse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_potential_nurse` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date_Created` text NOT NULL,
  `Nurse_First_Name` text NOT NULL,
  `Nurse_Last_Name` text NOT NULL,
  `Discipline` text NOT NULL,
  `Email_Address` text NOT NULL,
  `Address` text DEFAULT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `Cell_Phone` text NOT NULL,
  `Desired_Schedule/Shifts` text NOT NULL,
  `Gender` text NOT NULL,
  `Skills_Experience` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `pay_expectations` varchar(20) DEFAULT NULL,
  `desired_schedule` text DEFAULT NULL,
  `type_of_shift` text DEFAULT NULL,
  `Years_of_Experience` varchar(50) DEFAULT NULL,
  `Comments` varchar(1000) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_potential_nurse_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_potential_nurse_new` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date_Created` text NOT NULL,
  `Nurse_First_Name` text NOT NULL,
  `Nurse_Last_Name` text NOT NULL,
  `Discipline` text NOT NULL,
  `Email_Address` text NOT NULL,
  `Address` text DEFAULT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip_Code` text NOT NULL,
  `Cell_Phone` text NOT NULL,
  `Desired_Schedule/Shifts` text NOT NULL,
  `Gender` text NOT NULL,
  `Skills_Experience` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `pay_expectations` varchar(20) DEFAULT NULL,
  `desired_schedule` text DEFAULT NULL,
  `type_of_shift` text DEFAULT NULL,
  `Years_of_Experience` varchar(50) DEFAULT NULL,
  `Comments` varchar(1000) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_superior`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_superior` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` bigint(20) NOT NULL,
  `NeedToHireAccountsID` varchar(100) DEFAULT NULL,
  `ClientID` varchar(50) DEFAULT NULL,
  `Client` varchar(250) DEFAULT NULL,
  `NTH_Status` varchar(50) DEFAULT NULL,
  `Type_of_Shift` text DEFAULT NULL,
  `Schedule_Details` text DEFAULT NULL,
  `Schedule` varchar(200) DEFAULT NULL,
  `ScheduleDetailsBak` text DEFAULT NULL,
  `NTH_Hours` text DEFAULT NULL,
  `PDN_Auth_Hrs` text DEFAULT NULL,
  `Respite_Auth_Hrs` text DEFAULT NULL,
  `CNA_Auth_Hrs` text DEFAULT NULL,
  `Age` text DEFAULT NULL,
  `Gender` text DEFAULT NULL,
  `Weight` text DEFAULT NULL,
  `Biography` text DEFAULT NULL,
  `The_Sale` text DEFAULT NULL,
  `Attending_School` text DEFAULT NULL,
  `Language` text DEFAULT NULL,
  `Nurse_Language` text DEFAULT NULL,
  `Pets_in_Home` text DEFAULT NULL,
  `Pets_Nurse_Cannot_Own` text DEFAULT NULL,
  `Liftingrequirements` text DEFAULT NULL,
  `Specialized` text DEFAULT NULL,
  `Skills` text DEFAULT NULL,
  `Service_Needed` text DEFAULT NULL,
  `Clinician_Gender` text DEFAULT NULL,
  `Clinician_Discipline` text DEFAULT NULL,
  `Payer` text DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `Respite_Payer` text DEFAULT NULL,
  `Intake` text DEFAULT NULL,
  `Intake_Status` text DEFAULT NULL,
  `Intake_Date` text DEFAULT NULL,
  `SOC_Date` text DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `City` text DEFAULT NULL,
  `State` text DEFAULT NULL,
  `Zipcode` text DEFAULT NULL,
  `Location` text DEFAULT NULL,
  `Phone` text DEFAULT NULL,
  `CreatedOn` text DEFAULT NULL,
  `CreatedReason` text DEFAULT NULL,
  `Meeting_Nurses` text DEFAULT NULL,
  `WFD_Client` text DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `Covid_Vaccine_Required` text DEFAULT NULL,
  `MediaConsent` text DEFAULT NULL,
  `NonSmoking_Nurses` text DEFAULT NULL,
  `Smokers_Live_in_Home` text DEFAULT NULL,
  `Client_Care_Coordinator` varchar(100) DEFAULT NULL,
  `Days_on_NTH` varchar(25) DEFAULT NULL,
  `Recruiter` varchar(100) DEFAULT NULL,
  `NTH_Schedule` text DEFAULT NULL,
  `Referral_Date` text DEFAULT NULL,
  `Assessment_Case_Manager` varchar(100) DEFAULT NULL,
  `Auth_Case_Manager` varchar(100) DEFAULT NULL,
  `Intake_Coordinator` varchar(100) DEFAULT NULL,
  `BD_Rep` varchar(100) DEFAULT NULL,
  `MeetingNursesBit` text DEFAULT NULL,
  `Client_DOB` text DEFAULT NULL,
  `Priority` varchar(1) DEFAULT NULL,
  `IsActive` varchar(1) DEFAULT NULL,
  `FacilityID` varchar(50) DEFAULT NULL,
  `HubID` varchar(50) DEFAULT NULL,
  `Hub` varchar(100) DEFAULT NULL,
  `MatchInEMR` varchar(5) DEFAULT NULL,
  `SpecializedclientBIT` varchar(1) DEFAULT NULL,
  `ChecklistBit` varchar(1) DEFAULT NULL,
  `CreatedBy` varchar(200) DEFAULT NULL,
  `ModifiedOn` varchar(23) DEFAULT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `lng` varchar(255) DEFAULT NULL,
  `lat2` varchar(255) DEFAULT NULL,
  `lng2` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` tinyint(1) DEFAULT NULL,
  `needs_geocoding` int(1) DEFAULT NULL,
  `is_ignored` tinyint(1) DEFAULT 0,
  `has_payer_group` int(11) DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_textblast`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_textblast` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Clinician_ID` text NOT NULL,
  `Provider_Name` text NOT NULL,
  `Period_Hours` text NOT NULL,
  `Weeks_in_Period` text NOT NULL,
  `Weekly_Average` text NOT NULL,
  `Period_Start_Date` text NOT NULL,
  `Period_End_Date` text NOT NULL,
  `Provider_Status` text NOT NULL,
  `Dicipline` text NOT NULL,
  `Language` text NOT NULL,
  `Provider_is_Bilingual` text NOT NULL,
  `Pay_Type` text NOT NULL,
  `Gender` text NOT NULL,
  `Address1` text NOT NULL,
  `Address2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Phone` text NOT NULL,
  `Mobile` text NOT NULL,
  `Provider_Home_Branch` text NOT NULL,
  `AOC_Email` text NOT NULL,
  `External_Email` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_textblast_Clinician_ID` (`Clinician_ID`(1024))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_textblast_BU_2025_07_08`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_textblast_BU_2025_07_08` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Clinician_ID` text NOT NULL,
  `Provider_Name` text NOT NULL,
  `Period_Hours` text NOT NULL,
  `Weeks_in_Period` text NOT NULL,
  `Weekly_Average` text NOT NULL,
  `Period_Start_Date` text NOT NULL,
  `Period_End_Date` text NOT NULL,
  `Provider_Status` text NOT NULL,
  `Dicipline` text NOT NULL,
  `Language` text NOT NULL,
  `Provider_is_Bilingual` text NOT NULL,
  `Pay_Type` text NOT NULL,
  `Gender` text NOT NULL,
  `Address1` text NOT NULL,
  `Address2` text NOT NULL,
  `City` text NOT NULL,
  `State` text NOT NULL,
  `Zip` text NOT NULL,
  `Phone` text NOT NULL,
  `Mobile` text NOT NULL,
  `Provider_Home_Branch` text NOT NULL,
  `AOC_Email` text NOT NULL,
  `External_Email` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `tmplat` varchar(255) NOT NULL,
  `tmplng` varchar(255) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_therapy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps_therapy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_Name` text NOT NULL,
  `Client_LOB` text NOT NULL,
  `Client_Age` text NOT NULL,
  `Client_DOB` text NOT NULL,
  `Client_Address_1` text NOT NULL,
  `Client_Address_2` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_State` text NOT NULL,
  `Client_Zip` text NOT NULL,
  `Client_County` text NOT NULL,
  `Primary_Clinician_Name` text NOT NULL,
  `Discipline` text NOT NULL,
  `CGTaskID` int(12) NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `is_updated` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maps_therapy_cgtaskid` (`CGTaskID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mass_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_message` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `receivers` longtext DEFAULT NULL,
  `message` text NOT NULL,
  `last_updated_count` int(11) NOT NULL DEFAULT 0 COMMENT 'count where send sms stopped',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT 'count where send sms stopped',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mass_message_completed_at_updated_at` (`completed_at`,`updated_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mass_message_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_message_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mass_message_id` int(11) NOT NULL,
  `receiver_mobile` varchar(255) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `receive_status` varchar(255) DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_attachment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_types` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` longtext NOT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobile_app_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_app_menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_item_key` varchar(255) DEFAULT NULL COMMENT 'Unique for each menu',
  `menu_item_value` varchar(255) DEFAULT NULL COMMENT 'Menu name to display on the mobile app',
  `page_title` varchar(255) NOT NULL COMMENT 'Page Title',
  `description` varchar(255) NOT NULL COMMENT 'Page Information',
  `link` varchar(255) DEFAULT NULL COMMENT 'Link for external page',
  `sort_order` int(11) NOT NULL DEFAULT 1 COMMENT 'Menu item display position',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Id of the parent menu ',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `menu_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1= parent, 2=clinician',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobile_otp_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_otp_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mobile_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `attempt_count` tinyint(4) NOT NULL DEFAULT 0,
  `resend_count` int(10) unsigned NOT NULL DEFAULT 0,
  `limit_events_count` int(10) unsigned NOT NULL DEFAULT 0,
  `cooldown_until` timestamp NULL DEFAULT NULL,
  `send_at` datetime DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `invalidated_at` timestamp NULL DEFAULT NULL,
  `invalidated_reason` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_employmentstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_employmentstatus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `EmploymentStatusID` int(11) DEFAULT NULL,
  `EmploymentStatus` varchar(50) DEFAULT '',
  `EMRID` int(11) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employmentstatus_status` (`EmploymentStatus`),
  KEY `idxr_mssql_employmentstatus_employmentstatusid` (`EmploymentStatusID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_facilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `FacilityID` int(11) DEFAULT NULL,
  `BillingFacilityID` int(11) DEFAULT NULL,
  `FacilityName` varchar(250) DEFAULT '',
  `Address1` varchar(50) DEFAULT '',
  `Address2` varchar(50) DEFAULT '',
  `City` varchar(30) DEFAULT '',
  `State` varchar(20) DEFAULT '',
  `Zip` varchar(10) DEFAULT '',
  `Phone` varchar(20) DEFAULT '',
  `Mobile` varchar(20) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `RegionID` int(11) DEFAULT NULL,
  `GL_Acct` varchar(10) DEFAULT '',
  `AOC_FacilityClassificationID` int(11) DEFAULT NULL,
  `State_FacilityClassificationID` int(11) DEFAULT NULL,
  `BranchName` varchar(250) DEFAULT '',
  `IPEA_instructions` text DEFAULT NULL,
  `hide` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `email_subject` text DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `region` int(2) NOT NULL,
  `branchid` int(11) DEFAULT 0,
  `branch_coverage_booked_goal` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_facilities_state_hide_facilityname` (`State`,`hide`,`FacilityName`),
  KEY `idxr_mssql_facilities_facilityname` (`FacilityName`),
  KEY `idxr_mssql_facilities_id_facilityid` (`id`,`FacilityID`),
  KEY `idxr_mssql_facilities_branchid` (`branchid`),
  KEY `idxr_mssql_facilities_facilityid` (`FacilityID`),
  KEY `idxr_mssql_facilities_region` (`region`),
  KEY `idxr_mssql_facilities_state` (`State`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_intake`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_intake` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `PatientAccount` varchar(50) DEFAULT '',
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `ReferralSourceID` int(11) DEFAULT NULL,
  `Payer` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `aoc_cf_authorized_hha_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_acquisition` varchar(5) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `aoc_cf_most_recent_flu_vaccination` date DEFAULT NULL,
  `aoc_cf_declined_flu_vaccination` varchar(5) DEFAULT NULL,
  `CLIENT_INTAKE_SEQUENCE_NO` int(11) DEFAULT NULL,
  `aoc_cf_high_utilizer` varchar(5) DEFAULT NULL,
  `aoc_cf_training_required` varchar(5) DEFAULT NULL,
  `aoc_cf_training_provided_by` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mssql_patients_hh_status_id` (`hh_status`,`id`),
  KEY `idx_mssql_patients_payer` (`Payer`),
  KEY `idx_mssql_patients_payer_group` (`payer_group`),
  KEY `idxr_mssql_patients_ccc_id` (`ccc_id`),
  KEY `idxr_mssql_patients_filters` (`IsLatestAdmit`,`SOCDate`,`IntakeDate`,`Patient_CurrentStatusID`,`IsNonAdmit`,`CF_GoalSOCDate`,`IntakeStatus`),
  KEY `idxr_mssql_patients_lobid` (`LOBID`),
  KEY `idxr_mssql_patients_aoc_facility_id` (`aoc_facility_id`),
  KEY `idxr_mssql_patients_billinginternalpatientid` (`BillingInternalPatientID`),
  KEY `idxr_mssql_patients_intakeid` (`IntakeID`),
  KEY `idxr_mssql_patients_not_in_kantime` (`not_in_kantime`),
  KEY `idxr_mssql_patients_patient_state` (`Patient_State`),
  KEY `idxr_mssql_patients_needs_school_set_up` (`needs_school_set_up`),
  KEY `idxr_mssql_patients_patientaccount` (`PatientAccount`),
  KEY `idxr_mssql_patients_patientid` (`PatientID`),
  KEY `idxr_mssql_patients_patient_currentstatusid` (`Patient_CurrentStatusID`),
  KEY `idxr_mssql_patients_socclinicianid_icid_ccmid_bdid` (`SOCClinicianID`,`ic_id`,`ccm_id`,`bd_id`),
  KEY `idx_intakeid_sequence_no` (`IntakeID`,`CLIENT_INTAKE_SEQUENCE_NO`),
  KEY `idx_patients_prn` (`id`,`isPRN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_04032025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_04032025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_authorized_pdn_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_services_needed` text DEFAULT NULL,
  `aoc_cf_autorized_cna_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_authorized_respite_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_clinician_discipline` text DEFAULT NULL,
  `aoc_cf_specialized` varchar(5) DEFAULT NULL,
  `aoc_cf_nurse_language` varchar(255) DEFAULT NULL,
  `aoc_cf_pets_in_home` text DEFAULT NULL,
  `aoc_cf_pets_nurse_cannot_own` text DEFAULT NULL,
  `aoc_cf_the_biography` text DEFAULT NULL,
  `aoc_cf_nth_notes` text DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_services_needed_1718042143` text DEFAULT NULL,
  `aoc_cf_dme` varchar(255) DEFAULT NULL,
  `aoc_cf_goal_soc` date DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_multiple_client_home` varchar(5) DEFAULT NULL,
  `aoc_cf_referral_type` text DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_school_district` varchar(255) DEFAULT NULL,
  `aoc_cf_school_name` varchar(255) DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_shared_agency?` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_authorized_weekly_visits` varchar(255) DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information_1729540930` text DEFAULT NULL,
  `aoc_cf_parent_portal_information_1729540983` text DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_05132025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_05132025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `aoc_cf_authorized_hha_weekly_hours` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_24-JUL-2025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_24-JUL-2025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `aoc_cf_authorized_hha_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_acquisition` varchar(5) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_backup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `aoc_cf_authorized_hha_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_acquisition` varchar(5) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `aoc_cf_most_recent_flu_vaccination` date DEFAULT NULL,
  `aoc_cf_declined_flu_vaccination` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mssql_patients_hh_status_id` (`hh_status`,`id`),
  KEY `idx_mssql_patients_payer` (`Payer`),
  KEY `idx_mssql_patients_payer_group` (`payer_group`),
  KEY `idxr_mssql_patients_ccc_id` (`ccc_id`),
  KEY `idxr_mssql_patients_filters` (`IsLatestAdmit`,`SOCDate`,`IntakeDate`,`Patient_CurrentStatusID`,`IsNonAdmit`,`CF_GoalSOCDate`,`IntakeStatus`),
  KEY `idxr_mssql_patients_lobid` (`LOBID`),
  KEY `idxr_mssql_patients_aoc_facility_id` (`aoc_facility_id`),
  KEY `idxr_mssql_patients_billinginternalpatientid` (`BillingInternalPatientID`),
  KEY `idxr_mssql_patients_intakeid` (`IntakeID`),
  KEY `idxr_mssql_patients_not_in_kantime` (`not_in_kantime`),
  KEY `idxr_mssql_patients_patient_state` (`Patient_State`),
  KEY `idxr_mssql_patients_needs_school_set_up` (`needs_school_set_up`),
  KEY `idxr_mssql_patients_patientaccount` (`PatientAccount`),
  KEY `idxr_mssql_patients_patientid` (`PatientID`),
  KEY `idxr_mssql_patients_patient_currentstatusid` (`Patient_CurrentStatusID`),
  KEY `idxr_mssql_patients_socclinicianid_icid_ccmid_bdid` (`SOCClinicianID`,`ic_id`,`ccm_id`,`bd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_daas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_daas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT NULL,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_daas2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_daas2` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_authorized_pdn_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_services_needed` text DEFAULT NULL,
  `aoc_cf_autorized_cna_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_authorized_respite_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_clinician_discipline` text DEFAULT NULL,
  `aoc_cf_meeting_nurses` text DEFAULT NULL,
  `aoc_cf_lifting_required` text DEFAULT NULL,
  `aoc_cf_client_weight` varchar(255) DEFAULT NULL,
  `aoc_cf_specialized` varchar(5) DEFAULT NULL,
  `aoc_cf_nurse_language` varchar(255) DEFAULT NULL,
  `aoc_cf_pets_in_home` text DEFAULT NULL,
  `aoc_cf_pets_nurse_cannot_own` text DEFAULT NULL,
  `aoc_cf_the_biography` text DEFAULT NULL,
  `aoc_cf_the_sale` text DEFAULT NULL,
  `aoc_cf_nth_notes` text DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_services_needed_1718042143` text DEFAULT NULL,
  `aoc_cf_dme` varchar(255) DEFAULT NULL,
  `aoc_cf_goal_soc` date DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_multiple_client_home` varchar(5) DEFAULT NULL,
  `aoc_cf_referral_type` text DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_school_district` varchar(255) DEFAULT NULL,
  `aoc_cf_school_name` varchar(255) DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_shared_agency?` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_authorized_weekly_visits` varchar(255) DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information_1729540930` text DEFAULT NULL,
  `aoc_cf_parent_portal_information_1729540983` text DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patients_tmplynn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patients_tmplynn` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_status` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(50) DEFAULT '',
  `BillingInternalPatientID` int(11) DEFAULT NULL,
  `Patient_StatusID` varchar(50) DEFAULT '',
  `IntakeID` int(11) DEFAULT NULL,
  `is_intake` tinyint(4) DEFAULT 0,
  `PrimaryPayerID` int(11) DEFAULT NULL,
  `PrimaryPayerProgramTypeID` int(11) DEFAULT NULL,
  `PrimaryPayerGroupID` int(11) DEFAULT NULL,
  `Patient_Salutation` varchar(50) DEFAULT '',
  `Patient_FirstName` varchar(50) DEFAULT '',
  `Patient_MiddleName` varchar(50) DEFAULT '',
  `Patient_LastName` varchar(50) DEFAULT '',
  `Search_Name` varchar(255) DEFAULT NULL,
  `Patient_Gender` varchar(20) DEFAULT '',
  `Patient_Address1` varchar(50) DEFAULT '',
  `Patient_Address2` varchar(50) DEFAULT '',
  `Patient_City` varchar(50) DEFAULT '',
  `Patient_Zip` varchar(10) DEFAULT '',
  `Patient_Phone` varchar(20) DEFAULT '',
  `Patient_MobilPhone` varchar(20) DEFAULT '',
  `Patient_DOB` date DEFAULT NULL,
  `CaseManagerID` int(11) DEFAULT NULL,
  `AttendingPhysicianID` int(11) DEFAULT NULL,
  `PrimaryClinicianID` int(11) DEFAULT NULL,
  `PrimaryLVNClinicianID` int(11) DEFAULT NULL,
  `PrimaryOTID` int(11) DEFAULT NULL,
  `PrimarySTID` int(11) DEFAULT NULL,
  `PrimaryPT` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `IsLatestAdmit` tinyint(1) DEFAULT NULL,
  `AdmitNo` int(11) DEFAULT NULL,
  `SOCDate` timestamp NULL DEFAULT NULL,
  `AssessmentSequence` varchar(250) DEFAULT '',
  `IntakeDate` timestamp NULL DEFAULT NULL,
  `ActualIntakeDate` date DEFAULT NULL,
  `AcceptedDate` timestamp NULL DEFAULT NULL,
  `NonAdmitDate` timestamp NULL DEFAULT NULL,
  `InitialSOCdateCurrentAdmit` timestamp NULL DEFAULT NULL,
  `DischargeDate` timestamp NULL DEFAULT NULL,
  `TransferDate` timestamp NULL DEFAULT NULL,
  `LengthOfStay` int(11) DEFAULT NULL,
  `SalesPersonID` int(11) DEFAULT NULL,
  `MedicaidNumber` varchar(50) DEFAULT '',
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `LOBID` int(11) DEFAULT NULL,
  `Patient_CurrentStatusID` int(11) DEFAULT NULL,
  `IsNonAdmit` tinyint(1) DEFAULT NULL,
  `PatientName` varchar(100) DEFAULT '',
  `ReferralDate` timestamp NULL DEFAULT NULL,
  `DischargeReasonID` int(11) DEFAULT NULL,
  `AdmissionSourceID` int(11) DEFAULT NULL,
  `EpisodeNumber` int(11) DEFAULT NULL,
  `EpisodeStartDate` timestamp NULL DEFAULT NULL,
  `EpisodeEndDate` timestamp NULL DEFAULT NULL,
  `PrimaryAIDEClinicianID` int(11) DEFAULT NULL,
  `PrimaryMSWClinicianID` int(11) DEFAULT NULL,
  `ReferringProviderID` int(11) DEFAULT NULL,
  `Patient_State` varchar(20) DEFAULT '',
  `AuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `AuthorizedWeeklyVisits` double(8,2) DEFAULT NULL,
  `CNAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `HHAAuthorizedWeeklyHours` double(8,2) DEFAULT NULL,
  `CF_ClinicianDiscipline` text DEFAULT '',
  `CF_DME` text DEFAULT '',
  `CF_LiftingRequired` text DEFAULT '',
  `CF_MeetingNurses` text DEFAULT '',
  `CF_MultipleClientHome` text DEFAULT '',
  `CF_NeedToHire` tinyint(1) DEFAULT NULL,
  `CF_ReferralType` text DEFAULT '',
  `CF_RespWeeklyHrs` text DEFAULT '',
  `CF_ServicesNeeded` text DEFAULT '',
  `CF_SharedAgency` text DEFAULT '',
  `CF_Skills` text DEFAULT '',
  `CF_Specialized` text DEFAULT '',
  `CF_TerminalPayer` text DEFAULT '',
  `CF_TheSale` text DEFAULT '',
  `CF_WorkForceDevProj` text DEFAULT '',
  `PreferenceList` text DEFAULT '',
  `ClientCareCoordinator` text DEFAULT '',
  `PetsinHome` text DEFAULT '',
  `PetsNurseCannotOwn` text DEFAULT '',
  `NTHNotes` text DEFAULT '',
  `Biography` text DEFAULT '',
  `DrivingDirection` text DEFAULT '',
  `CC2` int(11) DEFAULT NULL,
  `CF_NurseLanguage` text DEFAULT '',
  `Languages` text DEFAULT '',
  `CF_Weight` varchar(50) DEFAULT '',
  `SOCClinicianID` int(11) DEFAULT NULL,
  `CF_GoalSOCDate` timestamp NULL DEFAULT NULL,
  `IntakeStatus` varchar(100) DEFAULT '',
  `Payer` varchar(255) DEFAULT NULL,
  `non_vbc` varchar(255) NOT NULL DEFAULT '',
  `payer_group` varchar(255) DEFAULT NULL,
  `RespitePayer` varchar(255) DEFAULT NULL,
  `ReferralSourceID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_monthly_followup` datetime DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `bd_id` int(11) DEFAULT NULL,
  `OrdersReceived` tinyint(4) NOT NULL DEFAULT 0,
  `SchoolDistrict` varchar(200) DEFAULT '',
  `SchoolName` varchar(200) DEFAULT '',
  `TreatmentAddressName` varchar(255) DEFAULT NULL,
  `TreatmentAddress1` varchar(255) DEFAULT NULL,
  `TreatmentAddress2` varchar(255) DEFAULT NULL,
  `TreatmentAddressCity` varchar(255) DEFAULT NULL,
  `TreatmentAddressState` varchar(255) DEFAULT NULL,
  `TreatmentAddressEmail` varchar(255) DEFAULT NULL,
  `TreatmentAddressZIPCode` varchar(255) DEFAULT NULL,
  `TreatmentAddressTelePhone` varchar(255) DEFAULT NULL,
  `TreatmentAddressCellPhone` varchar(255) DEFAULT NULL,
  `CF_Schooldistrict` varchar(255) DEFAULT NULL,
  `CF_SchoolName` varchar(255) DEFAULT NULL,
  `isPRN` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call` tinyint(1) NOT NULL DEFAULT 0,
  `ccc_intro_call_made_at` timestamp NULL DEFAULT NULL,
  `ccc_intro_call_made_by` int(11) NOT NULL DEFAULT 0 COMMENT 'links to users table',
  `authorization_from` date DEFAULT NULL,
  `authorization_to` date DEFAULT NULL,
  `respite_authorization_from` date DEFAULT NULL,
  `respite_authorization_to` date DEFAULT NULL,
  `authorization_status_future` varchar(255) DEFAULT NULL,
  `authorization_status_current` varchar(255) DEFAULT NULL,
  `authorization_outcome_future` varchar(255) DEFAULT NULL,
  `authorization_outcome_current` varchar(255) DEFAULT NULL,
  `authorization_reason_future` varchar(255) DEFAULT NULL,
  `authorization_reason_current` varchar(255) DEFAULT NULL,
  `respite_authorization_status_future` varchar(255) DEFAULT NULL,
  `respite_authorization_status_current` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_future` varchar(255) DEFAULT NULL,
  `respite_authorization_outcome_current` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_future` varchar(255) DEFAULT NULL,
  `respite_authorization_reason_current` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `requested_callback` datetime DEFAULT NULL,
  `monthly_follow_up_attempts` datetime DEFAULT NULL,
  `exception_addressed_date` datetime DEFAULT NULL,
  `testPayer` varchar(250) NOT NULL,
  `result` varchar(100) DEFAULT NULL,
  `ACTIVEDATE` timestamp NULL DEFAULT NULL,
  `ADMISSIONSOURCE` varchar(500) DEFAULT NULL,
  `ADMIT_DATE` timestamp NULL DEFAULT NULL,
  `ADMIT_GROUP_NO` int(11) DEFAULT NULL,
  `INTAKE_ACCEPTEDDATE` timestamp NULL DEFAULT NULL,
  `INTAKE_ADDEDBY` int(11) DEFAULT NULL,
  `INTAKE_DATE` timestamp NULL DEFAULT NULL,
  `INTAKE_DIAGNOSIS` varchar(1000) DEFAULT NULL,
  `INTAKE_DIAGNOSISCODE` varchar(1000) DEFAULT NULL,
  `PAYERCODE` varchar(100) DEFAULT NULL,
  `TerminalPayerID` int(11) DEFAULT NULL,
  `hh_chartid` varchar(20) DEFAULT NULL,
  `hh_soc` date DEFAULT NULL,
  `hh_status` varchar(100) DEFAULT NULL,
  `hh_eligible` int(11) DEFAULT NULL,
  `kantime_primary_payer_id` int(11) DEFAULT NULL,
  `secondary_payer_id` int(11) DEFAULT NULL,
  `tertiary_payer_id` int(11) DEFAULT NULL,
  `PrimarycarePhysicianID` varchar(255) NOT NULL,
  `date_not_meeting_nurses` date DEFAULT NULL,
  `hh_nurse_navigator` int(11) NOT NULL DEFAULT 0,
  `global_search` text DEFAULT NULL,
  `medicaid_type` text DEFAULT NULL,
  `eligibility_end_date` date DEFAULT NULL,
  `time_renew_date` date DEFAULT NULL,
  `needs_school_set_up` tinyint(1) NOT NULL DEFAULT 0,
  `LastModified` varchar(23) DEFAULT NULL,
  `HHA` varchar(10) DEFAULT NULL,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `respite_primary_payer` int(11) DEFAULT NULL,
  `respite_secondary_payer` int(11) DEFAULT NULL,
  `respite_tertiary_payer` int(11) DEFAULT NULL,
  `active_auth_id` int(11) DEFAULT NULL,
  `active_respite_auth_id` int(11) DEFAULT NULL,
  `future_auth_id` int(11) DEFAULT NULL,
  `future_respite_auth_id` int(11) DEFAULT NULL,
  `tmp_primary_auth` int(11) DEFAULT NULL,
  `tmp_secondary_auth` int(11) DEFAULT NULL,
  `tmp_tertiary_auth` int(11) DEFAULT NULL,
  `auth_corrected_at` datetime DEFAULT NULL,
  `auth_corrected_by` int(11) NOT NULL DEFAULT 0,
  `ccc_nth_call_frequency` int(11) DEFAULT 0,
  `ccc_client_call_frequency` int(11) DEFAULT 0,
  `auth_nurse_id` int(11) DEFAULT NULL,
  `aoc_cf_preferred_contact_day` text DEFAULT NULL,
  `aoc_cf_preferred_contact_time` text DEFAULT NULL,
  `aoc_cf_language_in_the_home` varchar(255) DEFAULT NULL,
  `aoc_cf_smokers_in_the_home` varchar(5) DEFAULT NULL,
  `aoc_cf_non-smoking_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_male_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_female_nurses` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent` varchar(5) DEFAULT NULL,
  `aoc_cf_media_consent_reasons` text DEFAULT NULL,
  `aoc_cf_covid-19_vaccination` varchar(5) DEFAULT NULL,
  `aoc_cf_attending_school` varchar(5) DEFAULT NULL,
  `aoc_cf_wifi` varchar(5) DEFAULT NULL,
  `client_classification` varchar(500) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `requested_soc_date` date DEFAULT NULL,
  `transport_level_assistance` varchar(50) DEFAULT NULL,
  `aoc_cf_infustion_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_medication_pharmacy` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory` varchar(255) DEFAULT NULL,
  `aoc_cf_respiratory_therapist` varchar(255) DEFAULT NULL,
  `aoc_cf_respite_schedule` varchar(255) DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_tablet` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043766` varchar(255) DEFAULT NULL,
  `aoc_cf_tablet_1718043800` varchar(255) DEFAULT NULL,
  `aoc_cf_therapy` varchar(255) DEFAULT NULL,
  `intake_pending_reason` varchar(255) DEFAULT NULL,
  `intake_outcome` varchar(255) DEFAULT NULL,
  `intake_outcome_by` int(11) DEFAULT NULL,
  `intake_outcome_at` datetime DEFAULT NULL,
  `intake_comment` text DEFAULT NULL,
  `intake_followup` datetime DEFAULT NULL,
  `intake_contact` varchar(255) DEFAULT NULL,
  `intake_readytostart` tinyint(1) DEFAULT NULL,
  `intake_readytostart_reason` varchar(255) DEFAULT NULL,
  `BD_Rep` varchar(255) DEFAULT NULL,
  `intake_nonadmit_reason` varchar(255) DEFAULT NULL,
  `intake_controllable` varchar(255) DEFAULT NULL,
  `intake_lead_exclude` tinyint(1) DEFAULT 0,
  `intake_approved_date` datetime DEFAULT NULL,
  `aoc_cf_accepts_coverage` varchar(5) DEFAULT NULL,
  `living_situation` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_rate` varchar(255) DEFAULT NULL,
  `aoc_cf_sca_start_date` date DEFAULT NULL,
  `aoc_cf_sca_end_date` date DEFAULT NULL,
  `aoc_cf_other_agency's_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_sick_plans_completed` varchar(5) DEFAULT NULL,
  `aoc_cf_sick_plan_last_reviewed` date DEFAULT NULL,
  `aoc_cf_sca` varchar(5) DEFAULT NULL,
  `aoc_cf_other_agency` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_information` varchar(255) DEFAULT NULL,
  `aoc_cf_parent_portal_password` varchar(255) DEFAULT NULL,
  `marked_careteam` datetime DEFAULT NULL,
  `intake_planned_admission_visit` int(11) DEFAULT NULL,
  `intake_admission_visit` int(11) DEFAULT NULL,
  `intake_first_billable_visit` int(11) DEFAULT NULL,
  `intake_admission_date` date DEFAULT NULL,
  `LastMDVisit` date DEFAULT NULL,
  `NextMDVisit` date DEFAULT NULL,
  `PlaceOfService` varchar(255) DEFAULT NULL,
  `Ethnicity` varchar(255) DEFAULT NULL,
  `intake_bucket` varchar(50) DEFAULT NULL,
  `aoc_cf_telehealth` varchar(5) DEFAULT NULL,
  `aoc_cf_telehealth_order` varchar(5) DEFAULT NULL,
  `aoc_cf_last_telehealth_visit` date DEFAULT NULL,
  `aoc_cf_vip_status` varchar(5) DEFAULT NULL,
  `aoc_cf_vip_facility` text DEFAULT NULL,
  `not_in_kantime` tinyint(4) NOT NULL DEFAULT 0,
  `aoc_cf_authorized_hha_weekly_hours` varchar(255) DEFAULT NULL,
  `aoc_cf_acquisition` varchar(5) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_patientstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_patientstatus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `PatientStatusID` int(11) DEFAULT NULL,
  `PatientStatus` varchar(50) DEFAULT '',
  `EMRID` int(11) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_payers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_payers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `InsuranceID` int(11) NOT NULL,
  `BillingInsuranceID` int(11) DEFAULT NULL,
  `PayerName` varchar(50) DEFAULT NULL,
  `ProgramTypeID` int(11) DEFAULT NULL,
  `PayerGroupID` int(11) DEFAULT NULL,
  `PayerFinancialClassID` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) DEFAULT NULL,
  `BillingFacilityID` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `ClinicianID` varchar(100) DEFAULT NULL,
  `Salutation` varchar(10) DEFAULT '',
  `FirstName` varchar(20) DEFAULT '',
  `MiddleName` varchar(20) DEFAULT '',
  `LastName` varchar(20) DEFAULT '',
  `fullName` varchar(255) NOT NULL,
  `Search_Name` varchar(255) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT '',
  `Address1` varchar(50) DEFAULT '',
  `Address2` varchar(50) DEFAULT '',
  `City` varchar(30) DEFAULT '',
  `State` varchar(20) DEFAULT '',
  `Zip` varchar(10) DEFAULT '',
  `Phone` varchar(20) DEFAULT '',
  `Mobile` varchar(20) DEFAULT '',
  `EmploymentStatusID` int(11) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PayrollID` varchar(15) DEFAULT '',
  `HiredDate` timestamp NULL DEFAULT NULL,
  `TerminatedDate` timestamp NULL DEFAULT NULL,
  `RehireDate` date DEFAULT NULL,
  `SupervisingClinician` int(11) DEFAULT NULL,
  `PayTypeID` int(11) DEFAULT NULL,
  `EmployerName` varchar(100) DEFAULT '',
  `DisciplineName` varchar(10) DEFAULT '',
  `IsClinician` tinyint(1) DEFAULT NULL,
  `IsOfficeStaff` tinyint(1) DEFAULT NULL,
  `EmployementType` varchar(20) DEFAULT '',
  `ProductivityPointsPerWeek` varchar(8) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `SSN` varchar(50) DEFAULT '',
  `ProviderName` varchar(200) DEFAULT '',
  `Language` varchar(200) DEFAULT '',
  `SkillsList` varchar(1000) DEFAULT NULL,
  `Bilingual` tinyint(1) DEFAULT NULL,
  `AOC_Email` varchar(200) DEFAULT '',
  `External_Email` varchar(200) DEFAULT '',
  `CF_IsSignOnBonus` tinyint(1) DEFAULT NULL,
  `CF_SignOnBonusEffectiveDate` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `CF_RemoveFromTextBlast` varchar(1) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `cc_id` int(11) DEFAULT NULL COMMENT 'Coverage Coordinator',
  `has_billable_visit` int(11) DEFAULT NULL,
  `latest_schedule` date DEFAULT NULL,
  `total_hours` double(8,2) DEFAULT NULL,
  `has_badge` tinyint(1) NOT NULL DEFAULT 0,
  `removed_from_bon_map` tinyint(1) NOT NULL DEFAULT 0,
  `BillingProviderID` int(11) DEFAULT NULL,
  `NcsbnId` varchar(200) DEFAULT NULL,
  `nursys_mismatch` int(11) DEFAULT 0,
  `requested_status` bigint(20) unsigned DEFAULT NULL,
  `request_outcome` varchar(255) DEFAULT NULL,
  `request_outcome_reason` varchar(255) DEFAULT NULL,
  `request_outcome_comment` text DEFAULT NULL,
  `request_pending_document` varchar(255) DEFAULT NULL,
  `request_reason` varchar(255) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `request_by` bigint(20) unsigned DEFAULT NULL,
  `request_last_followup` datetime DEFAULT NULL,
  `request_advocacy_specialist` bigint(20) unsigned DEFAULT NULL,
  `CF_NotEligibleForRehire` int(11) DEFAULT NULL,
  `nurse_lost_client` enum('0','1') NOT NULL DEFAULT '0',
  `nurse_lost_client_resolved` datetime DEFAULT NULL,
  `nurse_lost_client_resolved_by` int(11) DEFAULT NULL,
  `Designation` varchar(350) DEFAULT NULL,
  `PayrollGroup` varchar(20) DEFAULT NULL,
  `IsBlockFromFurtherScheduling` tinyint(1) NOT NULL DEFAULT 0,
  `BlockSchedulingEffectiveDate` datetime NOT NULL,
  `IsOverrideScheduleRule` tinyint(1) NOT NULL DEFAULT 0,
  `IsOverriedServicesinClinicianProfile` tinyint(1) NOT NULL DEFAULT 0,
  `IsNotAutoApproveTeleSchedules` tinyint(1) NOT NULL DEFAULT 0,
  `IsoverrideClinicianMaxHoursPerWeek` tinyint(1) NOT NULL DEFAULT 0,
  `MaxHoursPerWeek` double(8,2) NOT NULL,
  `WarningType_MaxHoursPerWeek` varchar(5) NOT NULL,
  `nurse_lost_patientid` varchar(255) DEFAULT NULL,
  `scr_original_status_id` tinyint(4) DEFAULT NULL,
  `request_oc` int(11) DEFAULT NULL,
  `kt_user_id` int(11) DEFAULT NULL,
  `CF_GraduateCNA` varchar(5) DEFAULT NULL,
  `mssqlProviderID` int(10) DEFAULT NULL,
  `CF_DoNotContact` tinyint(1) DEFAULT NULL,
  `SMS_Status` varchar(255) DEFAULT NULL,
  `Preference_Status` smallint(6) NOT NULL DEFAULT 0,
  `change_request_employment` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Default, 1: Part-time, 2: Full-time',
  `aoc_cf_city` varchar(255) DEFAULT NULL,
  `aoc_cf_state` varchar(255) DEFAULT NULL,
  `aoc_cf_mile_radius` varchar(255) DEFAULT NULL,
  `aoc_cf_schedule` text DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_environment` text DEFAULT NULL,
  `aoc_cf_lifting_requirements` text DEFAULT NULL,
  `aoc_cf_language` varchar(255) DEFAULT NULL,
  `aoc_cf_default_order` varchar(255) DEFAULT NULL,
  `aoc_cf_sms_status` int(11) NOT NULL DEFAULT 0,
  `aoc_cf_preference_status` int(11) NOT NULL DEFAULT 0,
  `aoc_cf_subscription_status` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_providers_employment_deleted` (`EmploymentStatusID`,`is_deleted`),
  KEY `idx_fk_mssql_providers_facilityid` (`FacilityID`),
  KEY `idx_mssql_providers_aoc_email` (`AOC_Email`),
  KEY `idx_mssql_providers_disciplinename` (`DisciplineName`),
  KEY `idxr_mssql_providers_facility_first_last_isoffice_etype_email` (`FacilityID`,`FirstName`,`LastName`,`IsOfficeStaff`,`EmployementType`,`AOC_Email`),
  KEY `idxr_mssql_providers_employmentstatusid` (`EmploymentStatusID`),
  KEY `idxr_mssql_providers_aoc_cf_preference_status` (`aoc_cf_preference_status`),
  KEY `idxr_mssql_providers_aoc_cf_sms_status` (`aoc_cf_sms_status`),
  KEY `idxr_mssql_providers_aoc_cf_subscription_status` (`aoc_cf_subscription_status`),
  KEY `idxr_mssql_providers_providerid` (`ProviderID`),
  KEY `idxr_mssql_providers_clinicianid` (`ClinicianID`),
  KEY `idxr_mssql_providers_billingproviderid` (`BillingProviderID`),
  KEY `idx_providers_employment` (`EmploymentStatusID`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_providers-03212025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_providers-03212025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) DEFAULT NULL,
  `BillingFacilityID` int(11) DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `ClinicianID` varchar(100) DEFAULT NULL,
  `Salutation` varchar(10) DEFAULT '',
  `FirstName` varchar(20) DEFAULT '',
  `MiddleName` varchar(20) DEFAULT '',
  `LastName` varchar(20) DEFAULT '',
  `fullName` varchar(255) NOT NULL,
  `Search_Name` varchar(255) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT '',
  `Address1` varchar(50) DEFAULT '',
  `Address2` varchar(50) DEFAULT '',
  `City` varchar(30) DEFAULT '',
  `State` varchar(20) DEFAULT '',
  `Zip` varchar(10) DEFAULT '',
  `Phone` varchar(20) DEFAULT '',
  `Mobile` varchar(20) DEFAULT '',
  `EmploymentStatusID` int(11) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PayrollID` varchar(15) DEFAULT '',
  `HiredDate` timestamp NULL DEFAULT NULL,
  `TerminatedDate` timestamp NULL DEFAULT NULL,
  `RehireDate` date DEFAULT NULL,
  `SupervisingClinician` int(11) DEFAULT NULL,
  `PayTypeID` int(11) DEFAULT NULL,
  `EmployerName` varchar(100) DEFAULT '',
  `DisciplineName` varchar(10) DEFAULT '',
  `IsClinician` tinyint(1) DEFAULT NULL,
  `IsOfficeStaff` tinyint(1) DEFAULT NULL,
  `EmployementType` varchar(20) DEFAULT '',
  `ProductivityPointsPerWeek` varchar(8) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `SSN` varchar(50) DEFAULT '',
  `ProviderName` varchar(200) DEFAULT '',
  `Language` varchar(200) DEFAULT '',
  `SkillsList` varchar(1000) DEFAULT NULL,
  `Bilingual` tinyint(1) DEFAULT NULL,
  `AOC_Email` varchar(200) DEFAULT '',
  `External_Email` varchar(200) DEFAULT '',
  `CF_IsSignOnBonus` tinyint(1) DEFAULT NULL,
  `CF_SignOnBonusEffectiveDate` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `CF_RemoveFromTextBlast` varchar(1) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `rdod_id` int(11) DEFAULT NULL COMMENT 'Regional Director of Operations',
  `dbo_id` int(11) DEFAULT NULL COMMENT 'Director of Branch Operations',
  `ccc_id` int(11) DEFAULT NULL COMMENT 'Client Care Coordinator',
  `ic_id` int(11) DEFAULT NULL COMMENT 'Intake Coordinator',
  `r_id` int(11) DEFAULT NULL COMMENT 'Recruiter',
  `ccm_id` int(11) DEFAULT NULL COMMENT 'Clinical Care Manager',
  `cc_id` int(11) DEFAULT NULL COMMENT 'Coverage Coordinator',
  `has_billable_visit` int(11) DEFAULT NULL,
  `latest_schedule` date DEFAULT NULL,
  `total_hours` double(8,2) DEFAULT NULL,
  `has_badge` tinyint(1) NOT NULL DEFAULT 0,
  `removed_from_bon_map` tinyint(1) NOT NULL DEFAULT 0,
  `BillingProviderID` int(11) DEFAULT NULL,
  `NcsbnId` varchar(200) DEFAULT NULL,
  `nursys_mismatch` int(11) DEFAULT 0,
  `requested_status` bigint(20) unsigned DEFAULT NULL,
  `request_outcome` varchar(255) DEFAULT NULL,
  `request_outcome_reason` varchar(255) DEFAULT NULL,
  `request_outcome_comment` text DEFAULT NULL,
  `request_pending_document` varchar(255) DEFAULT NULL,
  `request_reason` varchar(255) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `request_by` bigint(20) unsigned DEFAULT NULL,
  `request_last_followup` datetime DEFAULT NULL,
  `request_advocacy_specialist` bigint(20) unsigned DEFAULT NULL,
  `CF_NotEligibleForRehire` int(11) DEFAULT NULL,
  `nurse_lost_client` enum('0','1') NOT NULL DEFAULT '0',
  `nurse_lost_client_resolved` datetime DEFAULT NULL,
  `nurse_lost_client_resolved_by` int(11) DEFAULT NULL,
  `Designation` varchar(350) DEFAULT NULL,
  `PayrollGroup` varchar(20) DEFAULT NULL,
  `IsBlockFromFurtherScheduling` tinyint(1) NOT NULL DEFAULT 0,
  `BlockSchedulingEffectiveDate` datetime NOT NULL,
  `IsOverrideScheduleRule` tinyint(1) NOT NULL DEFAULT 0,
  `IsOverriedServicesinClinicianProfile` tinyint(1) NOT NULL DEFAULT 0,
  `IsNotAutoApproveTeleSchedules` tinyint(1) NOT NULL DEFAULT 0,
  `IsoverrideClinicianMaxHoursPerWeek` tinyint(1) NOT NULL DEFAULT 0,
  `MaxHoursPerWeek` double(8,2) NOT NULL,
  `WarningType_MaxHoursPerWeek` varchar(5) NOT NULL,
  `nurse_lost_patientid` varchar(255) DEFAULT NULL,
  `scr_original_status_id` tinyint(4) DEFAULT NULL,
  `request_oc` int(11) DEFAULT NULL,
  `kt_user_id` int(11) DEFAULT NULL,
  `CF_GraduateCNA` varchar(5) DEFAULT NULL,
  `mssqlProviderID` int(10) DEFAULT NULL,
  `CF_DoNotContact` tinyint(1) DEFAULT NULL,
  `SMS_Status` varchar(255) DEFAULT NULL,
  `Preference_Status` smallint(6) NOT NULL DEFAULT 0,
  `change_request_employment` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Default, 1: Part-time, 2: Full-time',
  `aoc_cf_city` varchar(255) DEFAULT NULL,
  `aoc_cf_state` varchar(255) DEFAULT NULL,
  `aoc_cf_mile_radius` varchar(255) DEFAULT NULL,
  `aoc_cf_schedule` text DEFAULT NULL,
  `aoc_cf_skills` text DEFAULT NULL,
  `aoc_cf_environment` text DEFAULT NULL,
  `aoc_cf_lifting_requirements` text DEFAULT NULL,
  `aoc_cf_language` varchar(255) DEFAULT NULL,
  `aoc_cf_default_order` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_recruitertracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_recruitertracker` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `original_aoc_patient_id` int(11) DEFAULT NULL,
  `nurse_status` int(11) DEFAULT NULL COMMENT '0 - new, 1 - current',
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `aoc_employmentstatus_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User table reference',
  `Candidate` varchar(255) DEFAULT '',
  `Date_Contacted` timestamp NULL DEFAULT NULL,
  `Clinician_ID` varchar(255) DEFAULT '',
  `Bilingual` varchar(255) DEFAULT '',
  `Skillset` varchar(255) DEFAULT '',
  `Candidate_Current_Status` varchar(255) DEFAULT '',
  `Status_Changed_Date` datetime DEFAULT NULL,
  `IPEA_Scheduled` timestamp NULL DEFAULT NULL,
  `IPEA_Date` timestamp NULL DEFAULT NULL,
  `IPEA_Time` varchar(255) NOT NULL,
  `PEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `PEPI_Date` timestamp NULL DEFAULT NULL,
  `PEPI_Time` varchar(255) DEFAULT '',
  `PEPI-Outcome` varchar(255) DEFAULT '',
  `WEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `WEPI_Date` timestamp NULL DEFAULT NULL,
  `WEPI_Time` varchar(255) DEFAULT '',
  `WEPI-Outcome` varchar(255) DEFAULT '',
  `Total_Hours` double(5,2) DEFAULT NULL,
  `Hours_Worked` double(8,2) DEFAULT NULL,
  `Client_Match_Date` timestamp NULL DEFAULT NULL,
  `Candidate_Start_Date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Candidate_Start_Time` varchar(50) DEFAULT NULL,
  `Client_Name` varchar(255) DEFAULT '',
  `Client_ID` varchar(255) DEFAULT '',
  `Branch_Location` varchar(255) DEFAULT '',
  `Payor_Source` varchar(255) DEFAULT '',
  `Agreed_Pay_Rate` double(5,2) DEFAULT NULL,
  `GP` double(7,5) DEFAULT NULL,
  `Approved_By` varchar(255) DEFAULT '',
  `Candidate_Notes` varchar(255) DEFAULT '',
  `Bonus_Status` varchar(255) DEFAULT '',
  `Bonus_Paid_Date` timestamp NULL DEFAULT NULL,
  `Recruiter` varchar(255) DEFAULT '',
  `mssqlID` int(11) NOT NULL,
  `Modified` timestamp NULL DEFAULT NULL,
  `Created` timestamp NULL DEFAULT NULL,
  `CreatedBy` varchar(255) DEFAULT '',
  `ModifiedBy` varchar(255) DEFAULT '',
  `UserID` int(11) NOT NULL,
  `ProviderID` int(11) NOT NULL,
  `CandidateID` int(10) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `FacilityID` int(11) NOT NULL,
  `Clinical_Competency_Review` timestamp NULL DEFAULT NULL,
  `Training_Shift` timestamp NULL DEFAULT NULL,
  `First_Shift_Not_Worked` varchar(255) DEFAULT '',
  `School_Set_Up_Due_Date` timestamp NULL DEFAULT NULL,
  `School_Set_Up_Completed` timestamp NULL DEFAULT NULL,
  `IPEA_Outcome` varchar(255) DEFAULT '',
  `Schedule` varchar(255) DEFAULT '',
  `live_ipea_status` varchar(255) DEFAULT NULL,
  `inactive_reason` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `whiteboard_row` varchar(100) NOT NULL,
  `application_date` date DEFAULT NULL,
  `no_candidate_match` int(1) NOT NULL,
  `no_provider_match` int(1) NOT NULL,
  `is_rehire` tinyint(1) NOT NULL,
  `why_nurse_chosen` text DEFAULT NULL,
  `clinician_availability` text DEFAULT NULL,
  `Training_Shift_Time` varchar(255) DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `linked_IPEA` int(11) DEFAULT NULL,
  `Reason_Nurse_Declined_AOC` varchar(500) DEFAULT NULL,
  `tmpPayer` varchar(355) NOT NULL,
  `better_offer_type` varchar(250) DEFAULT NULL,
  `better_offer_rate` double(5,2) DEFAULT NULL,
  `candidate_inactive_reason` varchar(255) NOT NULL DEFAULT '',
  `candidate_inactive_notes` varchar(8000) NOT NULL DEFAULT '',
  `aoc_intake_id` int(11) DEFAULT NULL COMMENT 'primary key of Intake table',
  `hard_stop` tinyint(1) DEFAULT NULL,
  `pay_rate_entered_in_kantime` tinyint(1) DEFAULT NULL,
  `right_signature_sent` tinyint(1) DEFAULT NULL,
  `first_week_created` tinyint(1) DEFAULT NULL,
  `kantime_patient_status` int(11) DEFAULT NULL,
  `kantime_candidate_status` int(11) DEFAULT NULL,
  `nth_schedule_ids` varchar(1000) DEFAULT NULL,
  `tmpStartingBucket` varchar(30) NOT NULL,
  `bonus_amount` decimal(6,2) NOT NULL DEFAULT 0.00,
  `uncontrollable` tinyint(4) NOT NULL DEFAULT 0,
  `referred_by` int(11) DEFAULT NULL,
  `schedules_built` tinyint(1) DEFAULT 0,
  `checked_off_by` int(11) DEFAULT 0,
  `checked_off_at` datetime DEFAULT NULL,
  `removal_type` varchar(255) DEFAULT NULL,
  `proposed_next_step` varchar(20) DEFAULT NULL,
  `proposed_ns_date` date DEFAULT NULL,
  `proposed_ns_time` time DEFAULT NULL,
  `assigned_ccr` int(11) DEFAULT NULL,
  `actual_gp` double(5,2) NOT NULL DEFAULT 0.00,
  `bonus_period` date NOT NULL,
  `bonus_tier` int(11) DEFAULT NULL,
  `bonus_override_by` int(11) NOT NULL,
  `bonus_override` double NOT NULL DEFAULT 0,
  `bonus_calc_goals` int(11) NOT NULL DEFAULT 0,
  `bonus_calc_pct_to_goal` double NOT NULL DEFAULT 0,
  `bonus_calc_total_hours` int(11) NOT NULL DEFAULT 0,
  `ccr_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_count` int(11) DEFAULT 0,
  `payrate_approved` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_override` varchar(255) DEFAULT NULL,
  `new_grad` tinyint(1) NOT NULL DEFAULT 0,
  `prn_first_schedule` text DEFAULT NULL,
  `pending_parent_attempt_count` int(11) NOT NULL DEFAULT 0,
  `pending_candidate_attempt_count` int(11) NOT NULL DEFAULT 0,
  `ipea_attempt_count` int(11) NOT NULL DEFAULT 0,
  `schedule_by_ra_attempt_count` int(11) NOT NULL DEFAULT 0,
  `assigned_candidatestart` int(11) DEFAULT NULL,
  `payrate_approved_by` int(11) DEFAULT NULL,
  `payrate_approved_on` datetime DEFAULT NULL,
  `intake_in_home` tinyint(4) NOT NULL DEFAULT 0,
  `is_prn_confirmed` tinyint(4) NOT NULL DEFAULT 0,
  `prn_confirmed_by` int(11) DEFAULT NULL,
  `prn_confirmed_date` datetime DEFAULT NULL,
  `prn_branch` varchar(255) DEFAULT NULL,
  `shift_type` varchar(255) DEFAULT NULL,
  `days_available` varchar(255) DEFAULT NULL,
  `activecoverage_outcome` varchar(255) DEFAULT NULL,
  `activecoverage_attempt` int(11) DEFAULT 0,
  `clientmatch_outcome` varchar(255) DEFAULT NULL,
  `clientmatch_comment` varchar(255) DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mssql_recruitertracker_lead_id_unique` (`lead_id`),
  KEY `idx_aoc_candidate_id_is_active` (`aoc_candidate_id`,`is_active`),
  KEY `idx_recruitertracker_statuses_start_date_hours_worked` (`Bonus_Status`,`Candidate_Current_Status`,`Candidate_Start_Date`,`Hours_Worked`),
  KEY `idx_fk_mssql_recruitertracker_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_mssql_recruitertracker_live_ipea_status` (`live_ipea_status`),
  KEY `idx_mssql_recruitertracker_isactive_status_userid_aocfacilityid` (`is_active`,`Candidate_Current_Status`,`user_id`,`aoc_facility_id`),
  KEY `idx_mssql_recruitertracker_candstatus_trainingshift_aocintakeid` (`Candidate_Current_Status`,`Training_Shift`,`aoc_intake_id`),
  KEY `idx_mssql_recruitertracker_clinicianid` (`Clinician_ID`),
  KEY `idxr_mssql_recruitertracker_patient_provider_candidate_facility` (`aoc_patient_id`,`aoc_provider_id`,`aoc_candidate_id`,`aoc_facility_id`),
  KEY `idxr_mssql_recruitertracker_ipea_date_ipea_time` (`IPEA_Date`,`IPEA_Time`),
  KEY `idxr_mssql_recruitertracker_is_active` (`is_active`),
  KEY `idxr_mssql_recruitertracker_user_id` (`user_id`),
  KEY `idxr_mssql_recruitertracker_whiteboard_row` (`whiteboard_row`),
  KEY `idx_recruitertracker_provider_id_id` (`aoc_provider_id`,`id`),
  KEY `idx_recruitertracker_prn` (`is_prn_confirmed`,`nurse_status`,`IPEA_Date`),
  CONSTRAINT `mssql_recruitertracker_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `nth_inquiries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_recruitertracker-03052025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_recruitertracker-03052025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `original_aoc_patient_id` int(11) DEFAULT NULL,
  `nurse_status` int(11) DEFAULT NULL COMMENT '0 - new, 1 - current',
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `aoc_employmentstatus_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User table reference',
  `Candidate` varchar(255) DEFAULT '',
  `Date_Contacted` timestamp NULL DEFAULT NULL,
  `Clinician_ID` varchar(255) DEFAULT '',
  `Bilingual` varchar(255) DEFAULT '',
  `Skillset` varchar(255) DEFAULT '',
  `Candidate_Current_Status` varchar(255) DEFAULT '',
  `Status_Changed_Date` datetime DEFAULT NULL,
  `IPEA_Scheduled` timestamp NULL DEFAULT NULL,
  `IPEA_Date` timestamp NULL DEFAULT NULL,
  `IPEA_Time` varchar(255) NOT NULL,
  `PEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `PEPI_Date` timestamp NULL DEFAULT NULL,
  `PEPI_Time` varchar(255) DEFAULT '',
  `PEPI-Outcome` varchar(255) DEFAULT '',
  `WEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `WEPI_Date` timestamp NULL DEFAULT NULL,
  `WEPI_Time` varchar(255) DEFAULT '',
  `WEPI-Outcome` varchar(255) DEFAULT '',
  `Total_Hours` double(5,2) DEFAULT NULL,
  `Hours_Worked` double(8,2) DEFAULT NULL,
  `Client_Match_Date` timestamp NULL DEFAULT NULL,
  `Candidate_Start_Date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Candidate_Start_Time` varchar(50) DEFAULT NULL,
  `Client_Name` varchar(255) DEFAULT '',
  `Client_ID` varchar(255) DEFAULT '',
  `Branch_Location` varchar(255) DEFAULT '',
  `Payor_Source` varchar(255) DEFAULT '',
  `Agreed_Pay_Rate` double(5,2) DEFAULT NULL,
  `GP` double(7,5) DEFAULT NULL,
  `Approved_By` varchar(255) DEFAULT '',
  `Candidate_Notes` varchar(255) DEFAULT '',
  `Bonus_Status` varchar(255) DEFAULT '',
  `Bonus_Paid_Date` timestamp NULL DEFAULT NULL,
  `Recruiter` varchar(255) DEFAULT '',
  `mssqlID` int(11) NOT NULL,
  `Modified` timestamp NULL DEFAULT NULL,
  `Created` timestamp NULL DEFAULT NULL,
  `CreatedBy` varchar(255) DEFAULT '',
  `ModifiedBy` varchar(255) DEFAULT '',
  `UserID` int(11) NOT NULL,
  `ProviderID` int(11) NOT NULL,
  `CandidateID` int(10) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `FacilityID` int(11) NOT NULL,
  `Clinical_Competency_Review` timestamp NULL DEFAULT NULL,
  `Training_Shift` timestamp NULL DEFAULT NULL,
  `First_Shift_Not_Worked` varchar(255) DEFAULT '',
  `School_Set_Up_Due_Date` timestamp NULL DEFAULT NULL,
  `School_Set_Up_Completed` timestamp NULL DEFAULT NULL,
  `IPEA_Outcome` varchar(255) DEFAULT '',
  `Schedule` varchar(255) DEFAULT '',
  `live_ipea_status` varchar(255) DEFAULT NULL,
  `inactive_reason` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `whiteboard_row` varchar(100) NOT NULL,
  `application_date` date DEFAULT NULL,
  `no_candidate_match` int(1) NOT NULL,
  `no_provider_match` int(1) NOT NULL,
  `is_rehire` tinyint(1) NOT NULL,
  `why_nurse_chosen` text DEFAULT NULL,
  `clinician_availability` text DEFAULT NULL,
  `Training_Shift_Time` varchar(255) DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `linked_IPEA` int(11) DEFAULT NULL,
  `Reason_Nurse_Declined_AOC` varchar(500) DEFAULT NULL,
  `tmpPayer` varchar(355) NOT NULL,
  `better_offer_type` varchar(250) DEFAULT NULL,
  `better_offer_rate` double(5,2) DEFAULT NULL,
  `candidate_inactive_reason` varchar(255) NOT NULL DEFAULT '',
  `candidate_inactive_notes` varchar(8000) NOT NULL DEFAULT '',
  `aoc_intake_id` int(11) DEFAULT NULL COMMENT 'primary key of Intake table',
  `hard_stop` tinyint(1) DEFAULT NULL,
  `pay_rate_entered_in_kantime` tinyint(1) DEFAULT NULL,
  `right_signature_sent` tinyint(1) DEFAULT NULL,
  `first_week_created` tinyint(1) DEFAULT NULL,
  `kantime_patient_status` int(11) DEFAULT NULL,
  `kantime_candidate_status` int(11) DEFAULT NULL,
  `nth_schedule_ids` varchar(1000) DEFAULT NULL,
  `tmpStartingBucket` varchar(30) NOT NULL,
  `bonus_amount` decimal(6,2) NOT NULL DEFAULT 0.00,
  `uncontrollable` tinyint(4) NOT NULL DEFAULT 0,
  `referred_by` int(11) DEFAULT NULL,
  `schedules_built` tinyint(1) DEFAULT 0,
  `checked_off_by` int(11) DEFAULT 0,
  `checked_off_at` datetime DEFAULT NULL,
  `removal_type` varchar(255) DEFAULT NULL,
  `proposed_next_step` varchar(20) DEFAULT NULL,
  `proposed_ns_date` date DEFAULT NULL,
  `proposed_ns_time` time DEFAULT NULL,
  `assigned_ccr` int(11) DEFAULT NULL,
  `actual_gp` double(5,2) NOT NULL DEFAULT 0.00,
  `bonus_period` date NOT NULL,
  `bonus_tier` int(11) DEFAULT NULL,
  `bonus_override_by` int(11) NOT NULL,
  `bonus_override` double NOT NULL DEFAULT 0,
  `bonus_calc_goals` int(11) NOT NULL DEFAULT 0,
  `bonus_calc_pct_to_goal` double NOT NULL DEFAULT 0,
  `bonus_calc_total_hours` int(11) NOT NULL DEFAULT 0,
  `ccr_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_count` int(11) DEFAULT 0,
  `payrate_approved` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_override` varchar(255) DEFAULT NULL,
  `new_grad` tinyint(1) NOT NULL DEFAULT 0,
  `prn_first_schedule` text DEFAULT NULL,
  `pending_parent_attempt_count` int(11) NOT NULL DEFAULT 0,
  `pending_candidate_attempt_count` int(11) NOT NULL DEFAULT 0,
  `ipea_attempt_count` int(11) NOT NULL DEFAULT 0,
  `schedule_by_ra_attempt_count` int(11) NOT NULL DEFAULT 0,
  `assigned_candidatestart` int(11) DEFAULT NULL,
  `payrate_approved_by` int(11) DEFAULT NULL,
  `payrate_approved_on` datetime DEFAULT NULL,
  `intake_in_home` tinyint(4) NOT NULL DEFAULT 0,
  `is_prn_confirmed` tinyint(4) NOT NULL DEFAULT 0,
  `prn_confirmed_by` int(11) DEFAULT NULL,
  `prn_confirmed_date` datetime DEFAULT NULL,
  `prn_branch` varchar(255) DEFAULT NULL,
  `shift_type` varchar(255) DEFAULT NULL,
  `days_available` varchar(255) DEFAULT NULL,
  `activecoverage_outcome` varchar(255) DEFAULT NULL,
  `activecoverage_attempt` int(11) DEFAULT 0,
  `clientmatch_outcome` varchar(255) DEFAULT NULL,
  `clientmatch_comment` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_recruitertracker-2025-06-05`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_recruitertracker-2025-06-05` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `original_aoc_patient_id` int(11) DEFAULT NULL,
  `nurse_status` int(11) DEFAULT NULL COMMENT '0 - new, 1 - current',
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `aoc_employmentstatus_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User table reference',
  `Candidate` varchar(255) DEFAULT '',
  `Date_Contacted` timestamp NULL DEFAULT NULL,
  `Clinician_ID` varchar(255) DEFAULT '',
  `Bilingual` varchar(255) DEFAULT '',
  `Skillset` varchar(255) DEFAULT '',
  `Candidate_Current_Status` varchar(255) DEFAULT '',
  `Status_Changed_Date` datetime DEFAULT NULL,
  `IPEA_Scheduled` timestamp NULL DEFAULT NULL,
  `IPEA_Date` timestamp NULL DEFAULT NULL,
  `IPEA_Time` varchar(255) NOT NULL,
  `PEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `PEPI_Date` timestamp NULL DEFAULT NULL,
  `PEPI_Time` varchar(255) DEFAULT '',
  `PEPI-Outcome` varchar(255) DEFAULT '',
  `WEPI_Scheduled` timestamp NULL DEFAULT NULL,
  `WEPI_Date` timestamp NULL DEFAULT NULL,
  `WEPI_Time` varchar(255) DEFAULT '',
  `WEPI-Outcome` varchar(255) DEFAULT '',
  `Total_Hours` double(5,2) DEFAULT NULL,
  `Hours_Worked` double(8,2) DEFAULT NULL,
  `Client_Match_Date` timestamp NULL DEFAULT NULL,
  `Candidate_Start_Date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Candidate_Start_Time` varchar(50) DEFAULT NULL,
  `Client_Name` varchar(255) DEFAULT '',
  `Client_ID` varchar(255) DEFAULT '',
  `Branch_Location` varchar(255) DEFAULT '',
  `Payor_Source` varchar(255) DEFAULT '',
  `Agreed_Pay_Rate` double(5,2) DEFAULT NULL,
  `GP` double(7,5) DEFAULT NULL,
  `Approved_By` varchar(255) DEFAULT '',
  `Candidate_Notes` varchar(255) DEFAULT '',
  `Bonus_Status` varchar(255) DEFAULT '',
  `Bonus_Paid_Date` timestamp NULL DEFAULT NULL,
  `Recruiter` varchar(255) DEFAULT '',
  `mssqlID` int(11) NOT NULL,
  `Modified` timestamp NULL DEFAULT NULL,
  `Created` timestamp NULL DEFAULT NULL,
  `CreatedBy` varchar(255) DEFAULT '',
  `ModifiedBy` varchar(255) DEFAULT '',
  `UserID` int(11) NOT NULL,
  `ProviderID` int(11) NOT NULL,
  `CandidateID` int(10) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `FacilityID` int(11) NOT NULL,
  `Clinical_Competency_Review` timestamp NULL DEFAULT NULL,
  `Training_Shift` timestamp NULL DEFAULT NULL,
  `First_Shift_Not_Worked` varchar(255) DEFAULT '',
  `School_Set_Up_Due_Date` timestamp NULL DEFAULT NULL,
  `School_Set_Up_Completed` timestamp NULL DEFAULT NULL,
  `IPEA_Outcome` varchar(255) DEFAULT '',
  `Schedule` varchar(255) DEFAULT '',
  `live_ipea_status` varchar(255) DEFAULT NULL,
  `inactive_reason` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_updated` int(1) NOT NULL,
  `whiteboard_row` varchar(100) NOT NULL,
  `application_date` date DEFAULT NULL,
  `no_candidate_match` int(1) NOT NULL,
  `no_provider_match` int(1) NOT NULL,
  `is_rehire` tinyint(1) NOT NULL,
  `why_nurse_chosen` text DEFAULT NULL,
  `clinician_availability` text DEFAULT NULL,
  `Training_Shift_Time` varchar(255) DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `linked_IPEA` int(11) DEFAULT NULL,
  `Reason_Nurse_Declined_AOC` varchar(500) DEFAULT NULL,
  `tmpPayer` varchar(355) NOT NULL,
  `better_offer_type` varchar(250) DEFAULT NULL,
  `better_offer_rate` double(5,2) DEFAULT NULL,
  `candidate_inactive_reason` varchar(255) NOT NULL DEFAULT '',
  `candidate_inactive_notes` varchar(8000) NOT NULL DEFAULT '',
  `aoc_intake_id` int(11) DEFAULT NULL COMMENT 'primary key of Intake table',
  `hard_stop` tinyint(1) DEFAULT NULL,
  `pay_rate_entered_in_kantime` tinyint(1) DEFAULT NULL,
  `right_signature_sent` tinyint(1) DEFAULT NULL,
  `first_week_created` tinyint(1) DEFAULT NULL,
  `kantime_patient_status` int(11) DEFAULT NULL,
  `kantime_candidate_status` int(11) DEFAULT NULL,
  `nth_schedule_ids` varchar(1000) DEFAULT NULL,
  `tmpStartingBucket` varchar(30) NOT NULL,
  `bonus_amount` decimal(6,2) NOT NULL DEFAULT 0.00,
  `uncontrollable` tinyint(4) NOT NULL DEFAULT 0,
  `referred_by` int(11) DEFAULT NULL,
  `schedules_built` tinyint(1) DEFAULT 0,
  `checked_off_by` int(11) DEFAULT 0,
  `checked_off_at` datetime DEFAULT NULL,
  `removal_type` varchar(255) DEFAULT NULL,
  `proposed_next_step` varchar(20) DEFAULT NULL,
  `proposed_ns_date` date DEFAULT NULL,
  `proposed_ns_time` time DEFAULT NULL,
  `assigned_ccr` int(11) DEFAULT NULL,
  `actual_gp` double(5,2) NOT NULL DEFAULT 0.00,
  `bonus_period` date NOT NULL,
  `bonus_tier` int(11) DEFAULT NULL,
  `bonus_override_by` int(11) NOT NULL,
  `bonus_override` double NOT NULL DEFAULT 0,
  `bonus_calc_goals` int(11) NOT NULL DEFAULT 0,
  `bonus_calc_pct_to_goal` double NOT NULL DEFAULT 0,
  `bonus_calc_total_hours` int(11) NOT NULL DEFAULT 0,
  `ccr_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_count` int(11) DEFAULT 0,
  `payrate_approved` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_override` varchar(255) DEFAULT NULL,
  `new_grad` tinyint(1) NOT NULL DEFAULT 0,
  `prn_first_schedule` text DEFAULT NULL,
  `pending_parent_attempt_count` int(11) NOT NULL DEFAULT 0,
  `pending_candidate_attempt_count` int(11) NOT NULL DEFAULT 0,
  `ipea_attempt_count` int(11) NOT NULL DEFAULT 0,
  `schedule_by_ra_attempt_count` int(11) NOT NULL DEFAULT 0,
  `assigned_candidatestart` int(11) DEFAULT NULL,
  `payrate_approved_by` int(11) DEFAULT NULL,
  `payrate_approved_on` datetime DEFAULT NULL,
  `intake_in_home` tinyint(4) NOT NULL DEFAULT 0,
  `is_prn_confirmed` tinyint(4) NOT NULL DEFAULT 0,
  `prn_confirmed_by` int(11) DEFAULT NULL,
  `prn_confirmed_date` datetime DEFAULT NULL,
  `prn_branch` varchar(255) DEFAULT NULL,
  `shift_type` varchar(255) DEFAULT NULL,
  `days_available` varchar(255) DEFAULT NULL,
  `activecoverage_outcome` varchar(255) DEFAULT NULL,
  `activecoverage_attempt` int(11) DEFAULT 0,
  `clientmatch_outcome` varchar(255) DEFAULT NULL,
  `clientmatch_comment` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_schedulemaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_schedulemaster` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `MSSQL_ID` int(11) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT NULL,
  `CreatedBy` timestamp NULL DEFAULT NULL,
  `FacilityID` int(11) DEFAULT NULL,
  `CGTaskID` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `ProviderID` int(11) DEFAULT NULL,
  `InsuranceID` int(11) DEFAULT NULL,
  `ServiceCodeID` int(11) DEFAULT NULL,
  `ServiceName` varchar(255) DEFAULT NULL,
  `PlannedStart` timestamp NULL DEFAULT NULL,
  `PlannedEnd` timestamp NULL DEFAULT NULL,
  `ActualStart` timestamp NULL DEFAULT NULL,
  `ActualEnd` timestamp NULL DEFAULT NULL,
  `PlannedHours` double(8,2) DEFAULT NULL,
  `ActualHours` double(8,2) DEFAULT NULL,
  `Hours` double(8,2) DEFAULT NULL,
  `ScheduleStatusID` int(5) DEFAULT NULL,
  `EChartStatusID` smallint(5) DEFAULT NULL,
  `Miles` varchar(8) DEFAULT NULL,
  `IsAuthRequired` int(11) DEFAULT NULL,
  `AuthorizationStatusID` varchar(5) DEFAULT NULL,
  `PaybleHours` varchar(10) DEFAULT NULL,
  `MissedVisitReasonID` varchar(5) DEFAULT NULL,
  `WeekStart` date DEFAULT NULL,
  `EpisodeID` int(11) DEFAULT NULL,
  `IsPRNVisit` int(11) DEFAULT NULL,
  `RevenueCode` varchar(255) DEFAULT NULL,
  `GCode` varchar(255) DEFAULT NULL,
  `Modifier1` varchar(255) DEFAULT NULL,
  `Modifier2` varchar(255) DEFAULT NULL,
  `Modifier3` varchar(255) DEFAULT NULL,
  `Modifier4` varchar(255) DEFAULT NULL,
  `Units` double(8,2) DEFAULT NULL,
  `HasChild` int(11) DEFAULT NULL,
  `IsChild` int(11) DEFAULT NULL,
  `ParentCgTaskID` int(11) DEFAULT NULL,
  `IsBillable` int(11) DEFAULT NULL,
  `IsPayable` int(11) DEFAULT NULL,
  `IsBilled` int(11) DEFAULT NULL,
  `IsPaid` int(11) DEFAULT NULL,
  `BillRate` double(8,2) DEFAULT NULL,
  `PayRate` double(8,2) DEFAULT NULL,
  `BillModeID` varchar(255) DEFAULT NULL,
  `PayMode` varchar(255) DEFAULT NULL,
  `BilledAmount` varchar(255) DEFAULT NULL,
  `PayrolledAmount` double(8,2) DEFAULT NULL,
  `RegularAmount` varchar(255) DEFAULT NULL,
  `OTAmount` varchar(255) DEFAULT NULL,
  `BonusAmount` varchar(255) DEFAULT NULL,
  `BaseRate` varchar(255) DEFAULT NULL,
  `CancellationReason` varchar(255) DEFAULT NULL,
  `ContractualRate` double(8,2) DEFAULT NULL,
  `ContractualModeID` varchar(255) DEFAULT NULL,
  `Contractualamount` decimal(8,2) DEFAULT NULL,
  `NonBillableReason` varchar(255) DEFAULT NULL,
  `RegularHours` double(8,2) DEFAULT NULL,
  `OTHours` double(8,2) DEFAULT NULL,
  `isMiscVisit` int(11) DEFAULT NULL,
  `Cost` varchar(255) DEFAULT NULL,
  `Revenue` varchar(255) DEFAULT NULL,
  `ContractualRevenue` varchar(255) DEFAULT NULL,
  `UserBranchID` varchar(255) DEFAULT NULL,
  `ScheduleDate` date DEFAULT NULL,
  `Profit` varchar(255) DEFAULT NULL,
  `ProfitContractual` varchar(255) DEFAULT NULL,
  `Margin` varchar(255) DEFAULT NULL,
  `MarginContractual` varchar(255) DEFAULT NULL,
  `BilledHours` double(8,2) DEFAULT NULL,
  `ScheduleProductivityPoints` double(8,2) DEFAULT NULL,
  `PayrollWeekStartDate` date DEFAULT NULL,
  `WeekNo` int(11) DEFAULT NULL,
  `ClinicianProductivityPerWeek` varchar(255) DEFAULT NULL,
  `AuthorizationID` int(11) DEFAULT NULL,
  `ScheduleMiscVisitID` varchar(255) DEFAULT NULL,
  `PrimaryIcdCode` varchar(555) DEFAULT NULL,
  `SurgicalIcdCode` varchar(555) DEFAULT NULL,
  `OtherDiagnosisCode` varchar(255) DEFAULT NULL,
  `HolidayHours` double(8,2) DEFAULT NULL,
  `HolidayPayrollAmount` double(8,2) DEFAULT NULL,
  `HolidayPayrollRates` double(8,2) DEFAULT NULL,
  `OTPayrollRate` double(8,2) DEFAULT NULL,
  `AddedOn` date DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `ClaimDetailID` int(11) DEFAULT NULL,
  `CalculatedRegularHours` varchar(255) DEFAULT NULL,
  `CalculatedRegularRate` varchar(255) DEFAULT NULL,
  `ReportingCompletedHours` double(8,2) DEFAULT NULL,
  `ReportingMissedHours` double(8,2) DEFAULT NULL,
  `ReportingPlannedHours` double(8,2) DEFAULT NULL,
  `MissedVisitReasonCategoryID` varchar(255) DEFAULT NULL,
  `CalculatedRevenue` double(8,2) DEFAULT NULL,
  `CalculatedCost` double(8,2) DEFAULT NULL,
  `GrossMargin` double(8,2) DEFAULT NULL,
  `MiscVisitReason` varchar(255) DEFAULT NULL,
  `DistinctServiceCodeID` int(11) DEFAULT NULL,
  `PlacedHours` double(8,2) DEFAULT NULL,
  `ReportingHours` double(8,2) DEFAULT NULL,
  `SecondaryPayer` int(11) DEFAULT NULL,
  `TertiaryPayer` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `checked_off_by` int(11) DEFAULT 0,
  `checked_off_at` datetime DEFAULT NULL,
  `billingReason` varchar(255) DEFAULT NULL,
  `billingSubReason` varchar(255) DEFAULT NULL,
  `billingNotes` varchar(255) DEFAULT NULL,
  `billingReasonAddedAt` datetime DEFAULT NULL,
  `billingReasonAddedBy` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `billingComments` text DEFAULT NULL,
  `billingResolvedDate` datetime DEFAULT NULL,
  `billingResolvedBy` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `SubmittedOn` datetime DEFAULT NULL,
  `ApprovedOn` datetime DEFAULT NULL,
  `ScheduleInvoiceDate` date DEFAULT NULL,
  `PaymentReceivedOn` date DEFAULT NULL,
  `SecondaryPayerSourceNames` varchar(250) DEFAULT NULL,
  `TertiaryPayerSourceNames` varchar(250) DEFAULT NULL,
  `BillingFacilityID` int(11) DEFAULT NULL,
  `BillingProviderID` int(11) DEFAULT NULL,
  `BillingPatientID` int(11) DEFAULT NULL,
  `MissedVisitReason` varchar(250) DEFAULT NULL,
  `is_deleted` int(11) NOT NULL DEFAULT 0,
  `mssqlPatientID` int(10) DEFAULT NULL,
  `mssqlProviderID` int(10) DEFAULT NULL,
  `is_missed_visit` tinyint(1) DEFAULT 0,
  `TerminalPayer` int(11) DEFAULT NULL,
  `zero_rate_fixed_at` datetime DEFAULT NULL,
  `zero_rate_fixed_by` int(11) NOT NULL DEFAULT 0,
  `etl_updated_timestamp` char(40) DEFAULT NULL,
  `billing_bucket_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=bill ready, 2=Not bill ready',
  `is_no_provider` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_schedulemaster_provider_status_date` (`ProviderID`,`ScheduleStatusID`,`ScheduleDate`),
  KEY `idx_mssql_schedulemaster_ProvID_PatID_SchedDate_SchedStatusID` (`ProviderID`,`PatientID`,`ScheduleDate`,`ScheduleStatusID`),
  KEY `idx_schedulemaster_patientid_scheduledate_statusid_isbillable` (`PatientID`,`ScheduleDate`,`ScheduleStatusID`,`IsBillable`),
  KEY `idxr_mssql_schedulemaster_billingReason` (`billingReason`),
  KEY `idxr_mssql_schedulemaster_billing_bucket_type` (`billing_bucket_type`),
  KEY `idxr_mssql_schedulemaster_cgtaskid` (`CGTaskID`),
  KEY `idxr_mssql_schedulemaster_echartstatusid` (`EChartStatusID`),
  KEY `idxr_mssql_schedulemaster_facilityid` (`FacilityID`),
  KEY `idxr_mssql_schedulemaster_haschild` (`HasChild`),
  KEY `idxr_mssql_schedulemaster_isbillable` (`IsBillable`),
  KEY `idxr_mssql_schedulemaster_is_no_provider` (`is_no_provider`),
  KEY `idxr_mssql_schedulemaster_providerid_actualend` (`ProviderID`,`ActualEnd`),
  KEY `idxr_mssql_schedulemaster_isBillable_scheddate_patid_ismissvisit` (`IsBillable`,`ScheduleDate`,`PatientID`,`is_missed_visit`),
  KEY `idxr_mssql_schedulemaster_patientid` (`PatientID`),
  KEY `idxr_mssql_schedulemaster_patientid_providerid` (`PatientID`,`ProviderID`),
  KEY `idxr_mssql_schedulemaster_providerid` (`ProviderID`),
  KEY `idxr_mssql_schedulemaster_scheduledate` (`ScheduleDate`),
  KEY `idxr_mssql_schedulemaster_servicename` (`ServiceName`),
  KEY `idxr_mssql_schedulemaster_schedulestatusid` (`ScheduleStatusID`),
  KEY `idx_bill_ready_opt` (`IsBillable`,`IsBilled`,`HasChild`,`PatientID`,`ScheduleDate`,`PlannedStart`,`ScheduleStatusID`,`EChartStatusID`),
  KEY `idx_msm_patient_missed_billable_date` (`PatientID`,`is_missed_visit`,`IsBillable`,`ScheduleDate`),
  KEY `idxr_mssql_schedulemaster_checked_off_at` (`checked_off_at`),
  KEY `idx_child_agg` (`IsChild`,`ParentCgTaskID`,`IsBilled`,`Hours`,`Contractualamount`),
  KEY `idx_msm_parent_child_billed_start` (`ParentCgTaskID`,`IsChild`,`IsBilled`,`PlannedStart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mssql_textblast_ignore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mssql_textblast_ignore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_mssql_textblast_ignore_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ncc_report_monthly_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ncc_report_monthly_calls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `month_start` timestamp NULL DEFAULT NULL,
  `ccc_id` int(11) DEFAULT NULL,
  `last_monthly_followup` timestamp NULL DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `requested_callback` timestamp NULL DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `monthly_follow_up_attempts` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fk_ncc_report_monthly_calls_aoc_patient_id` (`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ncc_report_weekly_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ncc_report_weekly_calls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccount_id` int(11) DEFAULT NULL,
  `week_start` timestamp NULL DEFAULT NULL,
  `ccc_id` int(11) DEFAULT NULL,
  `last_weekly_followup` timestamp NULL DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `weekly_follow_up_attempts` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(100) DEFAULT '' COMMENT 'Module Or Table Name',
  `entity_id` int(11) DEFAULT NULL COMMENT 'Table Primary ID for Ref',
  `notes` text DEFAULT '',
  `type` varchar(100) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `nth_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `due_date` datetime DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `priority` int(11) DEFAULT NULL COMMENT '0 - none, 1 - low, 2 - normal, 3 - escalated, 4 - high, 5 - urgent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `misc1` varchar(255) DEFAULT '',
  `misc2` varchar(255) DEFAULT '',
  `misc3` varchar(255) DEFAULT '',
  `misc4` text DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notes_assignedto_createdby` (`assigned_to`,`created_by`),
  KEY `idx_notes_patient_id` (`patient_id`),
  KEY `idx_notes_entity_entity_id` (`entity`,`entity_id`),
  KEY `idx_notes_tracker_id_entity_entity_id` (`tracker_id`,`entity`,`entity_id`),
  KEY `idx_notes_candidateid_entity_created_by` (`candidate_id`,`entity`,`created_by`),
  KEY `idxr_notes_assigned_to_completed_created_at` (`assigned_to`,`completed`,`created_at`),
  KEY `idxr_notes_entity_id` (`entity_id`),
  KEY `idxr_notes_entity` (`entity`),
  KEY `idxr_notes_misc1` (`misc1`),
  KEY `idx_notes_provider_id_entity` (`provider_id`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `related_item` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cleared` tinyint(1) NOT NULL DEFAULT 0,
  `push_notification` tinyint(1) NOT NULL DEFAULT 0,
  `snooze_until` datetime DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `misc1` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_notifications_userid_snoozeuntil_createdat` (`user_id`,`snooze_until`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nrr_candidates_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nrr_candidates_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `whiteboard_row` varchar(255) DEFAULT NULL,
  `Recruiter` varchar(255) DEFAULT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `region_name` varchar(255) DEFAULT NULL,
  `Monday` varchar(255) DEFAULT NULL,
  `created_at` date NOT NULL,
  `CandidateStartDate` varchar(255) DEFAULT NULL,
  `PatientName` varchar(255) DEFAULT NULL,
  `ProviderName` varchar(255) DEFAULT NULL,
  `Total_Hours` varchar(255) DEFAULT NULL,
  `CGTaskID` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nthSchedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nthSchedules` (
  `NeedToHireScheduleID` int(10) NOT NULL,
  `NeedToHireAccountsID` int(10) NOT NULL,
  `StartDayOfWeek` varchar(25) NOT NULL,
  `StartDayOfWeekInt` int(2) NOT NULL,
  `StartHour` int(2) NOT NULL,
  `StartMinute` int(2) NOT NULL,
  `EndHour` int(2) NOT NULL,
  `EndMinute` int(2) NOT NULL,
  `StartAndEndOnSameDay` int(1) NOT NULL,
  `ShiftType` int(2) NOT NULL,
  `CreatedOn` date NOT NULL,
  `CreatedBy` varchar(250) NOT NULL,
  `ModifiedOn` date NOT NULL,
  `ModifiedBy` varchar(250) NOT NULL,
  `IsActive` int(1) NOT NULL,
  `AdditionReason` varchar(500) NOT NULL,
  `ModificationReason` varchar(500) NOT NULL,
  `RemovalReason` varchar(500) NOT NULL,
  `ShiftHours` float NOT NULL,
  `ShiftTypeDescription` varchar(100) NOT NULL,
  `is_updated` int(1) NOT NULL,
  `updated_at` date NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nth_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nth_inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` bigint(20) unsigned NOT NULL,
  `ClinicianID` varchar(255) NOT NULL,
  `aoc_nthaccounts_id` bigint(20) NOT NULL,
  `aoc_patient_id` bigint(20) NOT NULL,
  `best_time_to_contact` varchar(255) DEFAULT NULL,
  `preferred_contact_method` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `Status` varchar(255) NOT NULL DEFAULT 'New',
  `outcome` varchar(255) DEFAULT NULL,
  `outcome_comment` text DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `recruiter_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `not_interested` tinyint(4) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) NOT NULL DEFAULT 0,
  `convertedToLead_at` timestamp NULL DEFAULT NULL,
  `convertedToLead_by` int(11) NOT NULL DEFAULT 0,
  `resolution` varchar(255) DEFAULT NULL,
  `resolution_detail` varchar(500) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_nth_inquiries_recruiter_id` (`recruiter_id`),
  KEY `idxr_nth_inquiries_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nursys_errors_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursys_errors_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `renewable_id` int(11) DEFAULT NULL,
  `error_log` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nursys_transaction_onetime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursys_transaction_onetime` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(255) NOT NULL,
  `transaction_id` text NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nursys_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursys_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(200) DEFAULT NULL,
  `transaction_id` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offer_compensation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offer_compensation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) unsigned NOT NULL,
  `version` tinyint(3) unsigned NOT NULL,
  `offer_field_id` bigint(20) unsigned DEFAULT NULL,
  `custom_label` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offer_compensation_items_offer_field_id_foreign` (`offer_field_id`),
  KEY `offer_compensation_items_offer_id_version_index` (`offer_id`,`version`),
  CONSTRAINT `offer_compensation_items_offer_field_id_foreign` FOREIGN KEY (`offer_field_id`) REFERENCES `offer_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offer_compensation_items_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offer_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offer_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `value_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offer_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offer_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 0,
  `value_string` text DEFAULT NULL,
  `value_decimal` decimal(8,2) DEFAULT NULL,
  `value_int` int(11) DEFAULT NULL,
  `value_date` datetime DEFAULT NULL,
  `value_bool` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `office_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `office_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `manager` int(11) DEFAULT NULL,
  `status` varchar(200) DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item9_score` int(11) DEFAULT NULL,
  `item10_score` int(11) DEFAULT NULL,
  `item11_score` int(11) DEFAULT NULL,
  `item12_score` int(11) DEFAULT NULL,
  `item13_score` int(11) DEFAULT NULL,
  `item14_score` int(11) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `development_areas` text DEFAULT NULL,
  `goal_setting_professional_development` text DEFAULT NULL,
  `completed_all_training` tinyint(1) DEFAULT 0,
  `subject_of_compliance` tinyint(1) DEFAULT 0,
  `commitment_to_compliance` tinyint(1) DEFAULT 0,
  `overall_performance` text DEFAULT NULL,
  `overall_performance_score` int(11) DEFAULT NULL,
  `supervisors_submitted` int(11) DEFAULT NULL,
  `supervisor_datetime` datetime DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `manager_submitted` int(11) DEFAULT NULL,
  `manager_datetime` datetime DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_team_datetime` datetime DEFAULT NULL,
  `exec_team_id` int(11) DEFAULT NULL,
  `exec_team_submitted` int(11) DEFAULT NULL,
  `hr_submitted` int(11) DEFAULT NULL,
  `hr_datetime` datetime DEFAULT NULL,
  `hr_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_evaluations_provider_id` (`aoc_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `office_evaluations_BU_2025_08_01`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `office_evaluations_BU_2025_08_01` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `manager` int(11) DEFAULT NULL,
  `status` varchar(200) DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item9_score` int(11) DEFAULT NULL,
  `item10_score` int(11) DEFAULT NULL,
  `item11_score` int(11) DEFAULT NULL,
  `item12_score` int(11) DEFAULT NULL,
  `item13_score` int(11) DEFAULT NULL,
  `item14_score` int(11) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `development_areas` text DEFAULT NULL,
  `goal_setting_professional_development` text DEFAULT NULL,
  `completed_all_training` tinyint(1) DEFAULT 0,
  `subject_of_compliance` tinyint(1) DEFAULT 0,
  `commitment_to_compliance` tinyint(1) DEFAULT 0,
  `overall_performance` text DEFAULT NULL,
  `overall_performance_score` int(11) DEFAULT NULL,
  `supervisors_submitted` int(11) DEFAULT NULL,
  `supervisor_datetime` datetime DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `manager_submitted` int(11) DEFAULT NULL,
  `manager_datetime` datetime DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_team_datetime` datetime DEFAULT NULL,
  `exec_team_id` int(11) DEFAULT NULL,
  `exec_team_submitted` int(11) DEFAULT NULL,
  `hr_submitted` int(11) DEFAULT NULL,
  `hr_datetime` datetime DEFAULT NULL,
  `hr_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `office_fa_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `office_fa_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `office_fa_history_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `contact_type_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_contacts_patient_id_foreign` (`patient_id`),
  KEY `patient_contacts_contact_type_id_foreign` (`contact_type_id`),
  KEY `patient_contacts_phone_index` (`phone`),
  KEY `patient_contacts_email_index` (`email`),
  CONSTRAINT `patient_contacts_contact_type_id_foreign` FOREIGN KEY (`contact_type_id`) REFERENCES `contact_types` (`id`),
  CONSTRAINT `patient_contacts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_custom_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_custom_properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `definition_id` bigint(20) unsigned NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_custom_properties_patient_id_definition_id_unique` (`patient_id`,`definition_id`),
  KEY `patient_custom_properties_definition_id_foreign` (`definition_id`),
  CONSTRAINT `patient_custom_properties_definition_id_foreign` FOREIGN KEY (`definition_id`) REFERENCES `patient_custom_property_definitions` (`id`),
  CONSTRAINT `patient_custom_properties_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_custom_property_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_custom_property_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `default_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_value`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_vacation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_vacation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `with_nurse` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_well_child_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_well_child_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) NOT NULL,
  `primary_care_physician_id` int(11) NOT NULL,
  `last_visit` date NOT NULL,
  `created_by` bigint(20) NOT NULL COMMENT 'Users ID Ref who created the record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_fk_patient_well_child_visits_aoc_patient_id` (`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paycom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `paycom` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `PaycomEmploymentStatus` varchar(255) DEFAULT NULL,
  `LocationGroup` varchar(255) DEFAULT NULL,
  `Division` varchar(255) DEFAULT NULL,
  `Department` varchar(255) DEFAULT NULL,
  `MatchType` varchar(255) DEFAULT NULL,
  `EmployeeCode` varchar(50) DEFAULT NULL,
  `DOLStatus` varchar(255) DEFAULT NULL,
  `Firstname` varchar(255) DEFAULT NULL,
  `Lastname` varchar(255) DEFAULT NULL,
  `Nickname` varchar(255) DEFAULT NULL,
  `Position` varchar(255) DEFAULT NULL,
  `State` varchar(10) DEFAULT NULL,
  `PaycomFacilityName` varchar(255) DEFAULT NULL,
  `WorkEmail` varchar(255) DEFAULT NULL,
  `SupervisorPrimaryClincianID` varchar(50) DEFAULT NULL,
  `PersonalEmail` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `id_fixed_by` int(11) NOT NULL DEFAULT 0,
  `id_fixed_at` datetime DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_paycom_EmployeeCode` (`EmployeeCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paycom_department_crosswalk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `paycom_department_crosswalk` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Department` varchar(255) NOT NULL,
  `Location_Desc` varchar(255) NOT NULL,
  `Division_Desc` varchar(255) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paycom_mssql_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `paycom_mssql_table` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `PaycomEmploymentStatus` varchar(255) DEFAULT NULL,
  `LocationGroup` varchar(255) DEFAULT NULL,
  `Division` varchar(255) DEFAULT NULL,
  `Department` varchar(255) DEFAULT NULL,
  `MatchType` varchar(255) DEFAULT NULL,
  `EmployeeCode` varchar(50) DEFAULT NULL,
  `DOLStatus` varchar(255) DEFAULT NULL,
  `Firstname` varchar(255) DEFAULT NULL,
  `Lastname` varchar(255) DEFAULT NULL,
  `Nickname` varchar(255) DEFAULT NULL,
  `Position` varchar(255) DEFAULT NULL,
  `State` varchar(10) DEFAULT NULL,
  `PaycomFacilityName` varchar(255) DEFAULT NULL,
  `WorkEmail` varchar(255) DEFAULT NULL,
  `SupervisorPrimaryClincianID` varchar(50) DEFAULT NULL,
  `PersonalEmail` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `id_fixed_by` int(11) NOT NULL DEFAULT 0,
  `id_fixed_at` datetime DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payer_priority_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payer_priority_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `state_abbr` varchar(255) NOT NULL,
  `rank` int(11) NOT NULL,
  `payer` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payer_rate_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payer_rate_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payer_rate_id` bigint(20) unsigned NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `rate` decimal(13,2) DEFAULT NULL,
  `pending_rate` decimal(13,2) DEFAULT NULL,
  `pending_deletion` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payer_rate_services_payer_rate_id_service_name_unique` (`payer_rate_id`,`service_name`),
  CONSTRAINT `payer_rate_services_payer_rate_id_foreign` FOREIGN KEY (`payer_rate_id`) REFERENCES `payer_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payer_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payer_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payer_id` int(11) DEFAULT NULL,
  `payer` varchar(255) DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `pending_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pending_data`)),
  `effectiveFrom` timestamp NULL DEFAULT NULL,
  `effectiveThrough` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `denied_by` int(11) DEFAULT NULL,
  `denied_at` timestamp NULL DEFAULT NULL,
  `hhagroupprimaryrate` decimal(13,2) DEFAULT NULL,
  `cnagroupprimaryrate` decimal(13,2) DEFAULT NULL,
  `lvnlpnrate` decimal(13,2) DEFAULT NULL,
  `lvnlpnspecrate` decimal(13,2) DEFAULT NULL,
  `rnrate` decimal(13,2) DEFAULT NULL,
  `rnspecrate` decimal(13,2) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_source` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payersource_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_childcare` tinyint(1) DEFAULT 0,
  `is_episodical` tinyint(1) DEFAULT 0,
  `program_id` int(11) DEFAULT NULL,
  `program_type` varchar(200) DEFAULT NULL,
  `payergroup_id` int(11) DEFAULT NULL,
  `payergroup_name` varchar(200) DEFAULT NULL,
  `caretype` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `timely_filing` int(11) NOT NULL,
  `contractual_payment_expectations` int(11) NOT NULL,
  `historical_payment_norm` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `state` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_source_payergroup_name` (`payergroup_name`),
  KEY `idxr_payment_source_name` (`name`),
  KEY `idxr_payment_source_payersource_id` (`payersource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `related_routes` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `physician_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `physician_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `physician_id` int(11) DEFAULT NULL,
  `sales_person_id` int(11) DEFAULT NULL,
  `first_name` text DEFAULT NULL,
  `last_name` text DEFAULT NULL,
  `middle_name` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `npi` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `external_id` text DEFAULT NULL,
  `licenseno` text DEFAULT NULL,
  `licenseexpirydate` timestamp NULL DEFAULT NULL,
  `upin` text DEFAULT NULL,
  `tpi` text DEFAULT NULL,
  `taxonomycode` text DEFAULT NULL,
  `pecosenrolled` text DEFAULT NULL,
  `address_1` text DEFAULT NULL,
  `address_2` text DEFAULT NULL,
  `zip` text DEFAULT NULL,
  `city` text DEFAULT NULL,
  `state` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `fax` text DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `category` text DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `etl_updated_timestamp` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polygons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `polygons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` varchar(255) NOT NULL,
  `user_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT '',
  `color` varchar(255) DEFAULT '',
  `coordinates` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `process_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `process` varchar(50) NOT NULL,
  `last_updated_date` char(26) NOT NULL,
  `last_updated_id` varchar(255) NOT NULL,
  `batch_size` int(5) NOT NULL,
  `last_batch_start` char(19) NOT NULL,
  `last_batch_end` char(19) NOT NULL,
  `running_now` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `info` text DEFAULT NULL COMMENT 'Comment added by User',
  `source` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_process_settings_process` (`process`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_patient` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) NOT NULL,
  `aoc_patient_id` int(11) NOT NULL,
  `last_schedule_date` date DEFAULT NULL,
  `first_schedule_date` date DEFAULT NULL,
  `Duration` int(5) NOT NULL,
  `ProviderID` varchar(100) NOT NULL,
  `PatientID` varchar(100) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_fk_provider_patient_aoc_provider_id` (`aoc_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raw_coverage_needs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `raw_coverage_needs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Date` text NOT NULL,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Patient_ID` text NOT NULL,
  `SOC` text NOT NULL,
  `Episode` text NOT NULL,
  `Payer` text NOT NULL,
  `Discipline` text NOT NULL,
  `Service` text NOT NULL,
  `Schedule_Time` text NOT NULL,
  `Hours` text NOT NULL,
  `Authorization` text NOT NULL,
  `Client_Address` text NOT NULL,
  `Client_County` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_ZipCode` text NOT NULL,
  `Locations` text NOT NULL,
  `processed` tinyint(4) NOT NULL DEFAULT 0,
  `lat` varchar(25) DEFAULT NULL,
  `lng` varchar(25) NOT NULL,
  `needs_geocoding` int(1) NOT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raw_therapy_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `raw_therapy_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Client_First_Name` text NOT NULL,
  `Client_Last_Name` text NOT NULL,
  `Client_LOB` text NOT NULL,
  `Client_Age` text NOT NULL,
  `Client_DOB` text NOT NULL,
  `Client_Address_1` text NOT NULL,
  `Client_Address_2` text NOT NULL,
  `Client_City` text NOT NULL,
  `Client_State` text NOT NULL,
  `Client_Zip` text NOT NULL,
  `Client_County` text NOT NULL,
  `Primary_Clinician_First_Name` text NOT NULL,
  `Primary_Clinician_Last_Name` text NOT NULL,
  `Discipline` text NOT NULL,
  `lat` varchar(255) NOT NULL,
  `lng` varchar(255) NOT NULL,
  `processed` bit(1) NOT NULL DEFAULT b'0',
  `needs_geocoding` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_ignored` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(500) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruiter_scorecards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruiter_scorecards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recruiter_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `scorecard_date` date DEFAULT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `grade` varchar(1) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `skills_recruiter_dashboard` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 7%',
  `skills_nth_checklists_worked` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 8%',
  `skills_jobvite` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 8%',
  `perform_headhunting` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 5%',
  `perform_ipea` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 8%',
  `perform_conversion` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 5%',
  `perform_pepiwepi` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 2%',
  `perform_hrsremoved` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 2%',
  `perform_first_shift_worked` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 20%',
  `perform_headhunting_goal` decimal(8,2) DEFAULT NULL,
  `perform_ipea_goal` decimal(8,2) DEFAULT NULL,
  `perform_conversion_goal` decimal(8,2) DEFAULT NULL,
  `perform_pepiwepi_goal` decimal(8,2) DEFAULT NULL,
  `perform_hrsremoved_goal` decimal(8,2) DEFAULT NULL,
  `perform_first_shift_worked_goal` decimal(8,2) DEFAULT NULL,
  `perform_headhunting_actual` decimal(8,2) DEFAULT NULL,
  `perform_ipea_actual` decimal(8,2) DEFAULT NULL,
  `perform_conversion_actual` decimal(8,2) DEFAULT NULL,
  `perform_pepiwepi_actual` decimal(8,2) DEFAULT NULL,
  `perform_hrsremoved_actual` decimal(8,2) DEFAULT NULL,
  `perform_first_shift_worked_actual` decimal(8,2) DEFAULT NULL,
  `phone_screen_tone_confidence` decimal(8,2) DEFAULT NULL,
  `phone_screen_aoc_values` decimal(8,2) DEFAULT NULL,
  `phone_screen_case_specifics` decimal(8,2) DEFAULT NULL,
  `phone_screen_hh_nurse_role` decimal(8,2) DEFAULT NULL,
  `phone_screen_pay_and_benefits` decimal(8,2) DEFAULT NULL,
  `phone_screen_clear_expectations_next_steps` decimal(8,2) DEFAULT NULL,
  `phone_call_screening_score` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 10%',
  `attendance_and_dependability` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 10%',
  `professionalism` decimal(8,2) DEFAULT NULL COMMENT 'Weight - 10%',
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruiting_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruiting_schedule` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `ipea_location_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL COMMENT 'mssql_recruitertracker.id Ref',
  `type_id` int(11) DEFAULT NULL COMMENT 'schedule_types.id Ref',
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `appointment_end_time` time DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL COMMENT 'mssql_patients.id Ref',
  `ClinicianID` int(11) DEFAULT NULL COMMENT 'mssql_providers.ClinicianID Ref',
  `candidate_id` int(11) DEFAULT NULL COMMENT 'candidates.id Ref',
  `pay_rate` varchar(20) DEFAULT '',
  `gp` double(5,2) DEFAULT NULL,
  `gp_approved_by` varchar(150) DEFAULT NULL COMMENT 'Users.id Ref',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `IPEA_Confirmed` enum('Yes','No') DEFAULT NULL,
  `IPEA_lunch_preference` varchar(255) DEFAULT NULL,
  `IPEA_clinician_arrived` enum('Yes','NC/NS','Rescheduled','Cancelled') DEFAULT NULL,
  `IPEA_drug_test` enum('Passed','Failed','Refused') DEFAULT NULL,
  `IPEA_background` enum('Passed','Failed','Refused','Initiated') DEFAULT NULL,
  `IPEA_interview` enum('Good Fit','Suggest Other Clients','Not Qualified') DEFAULT NULL,
  `IPEA_competency_check` enum('Passed','Failed') DEFAULT NULL,
  `IPEA_tpn_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_trachvent_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_onboarding_status` enum('Ready To Work','Incomplete','Ineligible for Hire/Rehire','Inactive') DEFAULT NULL,
  `IPEA_pending` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `branch_notes` text DEFAULT NULL,
  `why_not_qualified` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `assisted_by` int(11) DEFAULT NULL COMMENT 'User Ref',
  `ipea_status` varchar(250) DEFAULT NULL,
  `discipline` varchar(20) DEFAULT NULL,
  `removed_reason` text DEFAULT NULL,
  `removed_notes` text DEFAULT NULL,
  `ccm_id` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `first_followup_outcome` varchar(255) DEFAULT NULL,
  `sevenday_followup_outcome` varchar(255) DEFAULT NULL,
  `thirtyday_followup_outcome` varchar(255) DEFAULT NULL,
  `followed_up_at` datetime DEFAULT NULL,
  `followed_up_by` int(11) DEFAULT NULL,
  `ccr_donotuse_reason` text DEFAULT NULL,
  `due_ccr_notification` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1= notification sent for due ccr within 24 hours',
  `ccrNotes` text DEFAULT NULL,
  `IPEA_in_home_cargiver` enum('yes','no') DEFAULT NULL,
  `coverage_id` int(11) DEFAULT NULL,
  `first_shift_follow_up_que_one` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_two` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_three` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_four` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_five` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `ipeaschedulehours` varchar(255) NOT NULL,
  `ipeadays` varchar(255) NOT NULL,
  `candidate_application_id` int(11) NOT NULL,
  `outcome_reason` varchar(255) DEFAULT NULL,
  `has_billable_visit` tinyint(4) NOT NULL DEFAULT 0,
  `candidate_type` varchar(20) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_virtual` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_recruiting_schedule_aocpatientid_appointmentdate` (`aoc_patient_id`,`appointment_date`),
  KEY `idx_fk_recruiting_schedule_tracker_id` (`tracker_id`),
  KEY `idx_fk_recruiting_schedule_aoc_patient_id` (`aoc_patient_id`),
  KEY `idx_recruiting_schedule_typeid_outcome_createdat` (`type_id`,`outcome`,`created_at`),
  KEY `idx_recruiting_schedule_filters` (`type_id`,`is_deleted`,`ipea_location_id`,`IPEA_onboarding_status`,`outcome`),
  KEY `idxr_recruiting_schedule_appointment_date_appointment_time` (`appointment_date`,`appointment_time`),
  KEY `idxr_recruiting_schedule_candidate_application_id` (`candidate_application_id`),
  KEY `idxr_recruiting_schedule_id_patientid_providerid_scheduleddate` (`id`,`aoc_patient_id`,`aoc_provider_id`,`scheduled_date`),
  KEY `idxr_recruiting_schedule_aoc_patient_id_scheduled_date` (`aoc_patient_id`,`scheduled_date`),
  KEY `idxr_recruiting_schedule_typeid_trackerid_aocpatientid` (`type_id`,`tracker_id`,`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruiting_schedule-03052025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruiting_schedule-03052025` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `ipea_location_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL COMMENT 'mssql_recruitertracker.id Ref',
  `type_id` int(11) DEFAULT NULL COMMENT 'schedule_types.id Ref',
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `appointment_end_time` time DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL COMMENT 'mssql_patients.id Ref',
  `ClinicianID` int(11) DEFAULT NULL COMMENT 'mssql_providers.ClinicianID Ref',
  `candidate_id` int(11) DEFAULT NULL COMMENT 'candidates.id Ref',
  `pay_rate` varchar(20) DEFAULT '',
  `gp` double(5,2) DEFAULT NULL,
  `gp_approved_by` varchar(150) DEFAULT NULL COMMENT 'Users.id Ref',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `IPEA_Confirmed` enum('Yes','No') DEFAULT NULL,
  `IPEA_lunch_preference` varchar(255) DEFAULT NULL,
  `IPEA_clinician_arrived` enum('Yes','NC/NS','Rescheduled','Cancelled') DEFAULT NULL,
  `IPEA_drug_test` enum('Passed','Failed','Refused') DEFAULT NULL,
  `IPEA_background` enum('Passed','Failed','Refused','Initiated') DEFAULT NULL,
  `IPEA_interview` enum('Good Fit','Suggest Other Clients','Not Qualified') DEFAULT NULL,
  `IPEA_competency_check` enum('Passed','Failed') DEFAULT NULL,
  `IPEA_tpn_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_trachvent_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_onboarding_status` enum('Ready To Work','Incomplete','Ineligible for Hire/Rehire','Inactive') DEFAULT NULL,
  `IPEA_pending` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `branch_notes` text DEFAULT NULL,
  `why_not_qualified` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `assisted_by` int(11) DEFAULT NULL COMMENT 'User Ref',
  `ipea_status` varchar(250) DEFAULT NULL,
  `discipline` varchar(20) DEFAULT NULL,
  `removed_reason` text DEFAULT NULL,
  `removed_notes` text DEFAULT NULL,
  `ccm_id` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `first_followup_outcome` varchar(255) DEFAULT NULL,
  `sevenday_followup_outcome` varchar(255) DEFAULT NULL,
  `thirtyday_followup_outcome` varchar(255) DEFAULT NULL,
  `followed_up_at` datetime DEFAULT NULL,
  `followed_up_by` int(11) DEFAULT NULL,
  `ccr_donotuse_reason` text DEFAULT NULL,
  `due_ccr_notification` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1= notification sent for due ccr within 24 hours',
  `ccrNotes` text DEFAULT NULL,
  `IPEA_in_home_cargiver` enum('yes','no') DEFAULT NULL,
  `coverage_id` int(11) DEFAULT NULL,
  `first_shift_follow_up_que_one` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_two` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_three` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_four` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_five` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `ipeaschedulehours` varchar(255) NOT NULL,
  `ipeadays` varchar(255) NOT NULL,
  `candidate_application_id` int(11) NOT NULL,
  `outcome_reason` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruiting_schedule_BU_2025_03_20`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruiting_schedule_BU_2025_03_20` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `aoc_facility_id` int(11) DEFAULT NULL,
  `ipea_location_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL COMMENT 'mssql_recruitertracker.id Ref',
  `type_id` int(11) DEFAULT NULL COMMENT 'schedule_types.id Ref',
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `appointment_end_time` time DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL COMMENT 'mssql_patients.id Ref',
  `ClinicianID` int(11) DEFAULT NULL COMMENT 'mssql_providers.ClinicianID Ref',
  `candidate_id` int(11) DEFAULT NULL COMMENT 'candidates.id Ref',
  `pay_rate` varchar(20) DEFAULT '',
  `gp` double(5,2) DEFAULT NULL,
  `gp_approved_by` varchar(150) DEFAULT NULL COMMENT 'Users.id Ref',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `is_updated` int(11) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `IPEA_Confirmed` enum('Yes','No') DEFAULT NULL,
  `IPEA_lunch_preference` varchar(255) DEFAULT NULL,
  `IPEA_clinician_arrived` enum('Yes','NC/NS','Rescheduled','Cancelled') DEFAULT NULL,
  `IPEA_drug_test` enum('Passed','Failed','Refused') DEFAULT NULL,
  `IPEA_background` enum('Passed','Failed','Refused','Initiated') DEFAULT NULL,
  `IPEA_interview` enum('Good Fit','Suggest Other Clients','Not Qualified') DEFAULT NULL,
  `IPEA_competency_check` enum('Passed','Failed') DEFAULT NULL,
  `IPEA_tpn_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_trachvent_class` enum('Scheduled','Completed','N/A','Needs to be scheduled') DEFAULT NULL,
  `IPEA_onboarding_status` enum('Ready To Work','Incomplete','Ineligible for Hire/Rehire','Inactive') DEFAULT NULL,
  `IPEA_pending` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `branch_notes` text DEFAULT NULL,
  `why_not_qualified` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `assisted_by` int(11) DEFAULT NULL COMMENT 'User Ref',
  `ipea_status` varchar(250) DEFAULT NULL,
  `discipline` varchar(20) DEFAULT NULL,
  `removed_reason` text DEFAULT NULL,
  `removed_notes` text DEFAULT NULL,
  `ccm_id` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `first_followup_outcome` varchar(255) DEFAULT NULL,
  `sevenday_followup_outcome` varchar(255) DEFAULT NULL,
  `thirtyday_followup_outcome` varchar(255) DEFAULT NULL,
  `followed_up_at` datetime DEFAULT NULL,
  `followed_up_by` int(11) DEFAULT NULL,
  `ccr_donotuse_reason` text DEFAULT NULL,
  `due_ccr_notification` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1= notification sent for due ccr within 24 hours',
  `ccrNotes` text DEFAULT NULL,
  `IPEA_in_home_cargiver` enum('yes','no') DEFAULT NULL,
  `coverage_id` int(11) DEFAULT NULL,
  `first_shift_follow_up_que_one` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_two` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_three` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_four` varchar(255) DEFAULT NULL,
  `first_shift_follow_up_que_five` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `seven_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_one` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_two` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_three` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_four` varchar(255) DEFAULT NULL,
  `thirty_day_follow_up_que_five` varchar(255) DEFAULT NULL,
  `ipeaschedulehours` varchar(255) NOT NULL,
  `ipeadays` varchar(255) NOT NULL,
  `candidate_application_id` int(11) NOT NULL,
  `outcome_reason` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referralsource_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referralsource_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `agency_branch_id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `admission_source` int(11) DEFAULT NULL,
  `address_line1` text DEFAULT NULL,
  `address_line2` text DEFAULT NULL,
  `city` text DEFAULT NULL,
  `state` text DEFAULT NULL,
  `zip` text DEFAULT NULL,
  `phone_no` text DEFAULT NULL,
  `mobile_no` text DEFAULT NULL,
  `contact_name` text DEFAULT NULL,
  `contaxt_phone` text DEFAULT NULL,
  `salesperson_id` int(11) DEFAULT NULL,
  `category` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_referralsource_master_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `region_name` varchar(255) DEFAULT NULL,
  `team_leader` varchar(255) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT '',
  `jobvite_branches` varchar(255) DEFAULT '',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `renewable_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `renewable_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renewable_type_id` int(11) NOT NULL,
  `states` varchar(255) NOT NULL,
  `isClinician` tinyint(1) NOT NULL,
  `isOfficeStaff` tinyint(1) NOT NULL,
  `discipline` varchar(500) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_to_nurse` tinyint(1) NOT NULL DEFAULT 1,
  `Expiring` tinyint(1) NOT NULL,
  `Missing` tinyint(1) NOT NULL,
  `has_client_over21` enum('0','1') NOT NULL DEFAULT '0',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `renewables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `renewables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UniqueKey` varchar(50) DEFAULT NULL,
  `renewables_type` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `ProviderID` int(11) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `license_no` varchar(250) DEFAULT NULL,
  `license_state` varchar(100) DEFAULT NULL,
  `mandatory` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `updated_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `renewable_item_id` int(11) NOT NULL DEFAULT 0,
  `isMissing` tinyint(1) NOT NULL DEFAULT 0,
  `outcome` varchar(255) DEFAULT NULL,
  `last_followup_by` int(11) DEFAULT 0,
  `last_follow_up` datetime DEFAULT NULL,
  `thirtyzeroexpiring_attempt_count` int(11) DEFAULT 0,
  `ninetythirtyexpiring_attempt_count` int(11) DEFAULT 0,
  `missing_attempt_count` int(11) DEFAULT 0,
  `cccfifteenzeroexpiring_attempt_count` int(11) DEFAULT 0,
  `email_notification_status` int(11) DEFAULT NULL,
  `nursys_mismatch` int(11) DEFAULT 0,
  `nursys_outcome` varchar(255) DEFAULT '',
  `nursys_updated_by` int(11) DEFAULT NULL,
  `nursys_updated_at` datetime DEFAULT NULL,
  `last_nursys_sync` datetime DEFAULT NULL,
  `nursys_sync_status` tinyint(4) NOT NULL,
  `nursys_error` varchar(500) DEFAULT NULL,
  `nursys_stage` varchar(50) DEFAULT NULL,
  `mssqlProviderID` int(10) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_renewables_type_ismissing_expdate` (`renewables_type`,`isMissing`,`expiration_date`),
  KEY `idxr_renewables_aoc_provider_id` (`aoc_provider_id`),
  KEY `idxr_renewables_internal_item_id` (`renewable_item_id`),
  KEY `idxr_renewables_ismissing` (`isMissing`),
  KEY `idxr_renewables_renewables_type` (`renewables_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `renewables_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `renewables_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `updated_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `renewable_type_id` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `restricted_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `restricted_password` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `password` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permissions_id` bigint(20) unsigned NOT NULL,
  `roles_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permissions_id`,`roles_id`),
  KEY `role_has_permissions_roles_id_foreign` (`roles_id`),
  CONSTRAINT `role_has_permissions_permissions_id_foreign` FOREIGN KEY (`permissions_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_roles_id_foreign` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idxr_roles_name_department` (`name`,`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `school_setup_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_setup_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assigned_ccc` int(11) NOT NULL,
  `aoc_patient_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `school_district` varchar(255) DEFAULT NULL,
  `school_contact` varchar(255) DEFAULT NULL,
  `school_contact_info` varchar(255) DEFAULT NULL,
  `school_set_up_not_needed` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Open',
  `live_status` varchar(255) DEFAULT NULL,
  `clearance_packet` varchar(255) DEFAULT NULL,
  `clearance_packet_pending_nurse_reason` varchar(255) DEFAULT NULL,
  `finger_printing` varchar(255) DEFAULT NULL,
  `school_setup_status` varchar(255) DEFAULT NULL,
  `status_cancelled_reason` varchar(255) DEFAULT NULL,
  `ccc_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ssu_marked` int(11) DEFAULT NULL,
  `save_flag` enum('0','1') NOT NULL DEFAULT '0',
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `self_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `self_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `manager` int(11) DEFAULT NULL,
  `status` varchar(200) DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item9_score` int(11) DEFAULT NULL,
  `item10_score` int(11) DEFAULT NULL,
  `item11_score` int(11) DEFAULT NULL,
  `item12_score` int(11) DEFAULT NULL,
  `item13_score` int(11) DEFAULT NULL,
  `item14_score` int(11) DEFAULT NULL,
  `last_review_contributions` text NOT NULL,
  `performed_additional_duties` text NOT NULL,
  `areas_improve` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_code_crosswalk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_code_crosswalk` (
  `service` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `service_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dept_code` int(6) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_code_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_code_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) DEFAULT NULL,
  `serviceRateId` int(11) DEFAULT NULL,
  `serviceCodeId` int(11) DEFAULT NULL,
  `startDate` timestamp NULL DEFAULT NULL,
  `endDate` timestamp NULL DEFAULT NULL,
  `ContractualRate` decimal(13,2) DEFAULT NULL,
  `ContractualBillMode` varchar(50) DEFAULT NULL,
  `UnitType` varchar(50) DEFAULT NULL,
  `Unit` int(11) DEFAULT NULL,
  `MinuteInUnit` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ServiceCodeID` int(11) DEFAULT NULL,
  `ParentServiceCodeID` int(11) DEFAULT NULL,
  `BillingServiceCodeID` int(11) DEFAULT NULL,
  `BillingParentCodeID` int(11) DEFAULT NULL,
  `ServiceTypeID` int(11) DEFAULT NULL,
  `PayerID` int(11) DEFAULT NULL,
  `ServiceName` varchar(255) DEFAULT NULL,
  `ServiceCode` varchar(200) DEFAULT NULL,
  `PayCode` varchar(200) DEFAULT NULL,
  `FormID` int(11) DEFAULT NULL,
  `CreatedOn` datetime DEFAULT NULL,
  `EMRID` int(11) DEFAULT NULL,
  `DistinctServiceCodeID` int(11) DEFAULT NULL,
  `ExistsInKanTime` tinyint(1) DEFAULT 0,
  `Active` tinyint(1) DEFAULT 0,
  `ServiceCodeBridgeID` int(11) DEFAULT NULL,
  `CurrentContractualRate` float DEFAULT NULL,
  `UnitType` varchar(200) DEFAULT NULL,
  `Unit` int(11) DEFAULT NULL,
  `MinuteInUnit` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_servicecodes_servicecodeid` (`ServiceCodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_codes_daas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_codes_daas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT NULL,
  `parent_serviceid` int(11) DEFAULT NULL,
  `payerid` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `type` varchar(200) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `code` varchar(200) DEFAULT NULL,
  `paycode` varchar(200) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `form_id` int(11) DEFAULT NULL,
  `sf_created_by` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `ContractualRate` decimal(13,2) DEFAULT NULL,
  `ContractualBillMode` varchar(50) DEFAULT NULL,
  `UnitType` varchar(50) DEFAULT NULL,
  `Unit` int(11) DEFAULT NULL,
  `MinuteInUnit` int(11) DEFAULT NULL,
  `Category` int(11) DEFAULT NULL,
  `is_admissions` tinyint(4) NOT NULL,
  `is_respite` int(11) NOT NULL DEFAULT 0,
  `lobid` int(11) DEFAULT NULL,
  `is_billable` int(11) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_billable_daas` tinyint(1) NOT NULL DEFAULT 0,
  `is_terminated` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idxr_service_codes_daas_payerid` (`payerid`),
  KEY `idxr_service_codes_daas_serviceid_isadmissions_lobid` (`service_id`,`is_admissions`,`lobid`),
  KEY `idxr_service_codes_daas_service_id_name` (`service_id`,`name`),
  KEY `service_codes_daas_is_billable_daas_index` (`is_billable_daas`),
  KEY `service_codes_daas_is_terminated_index` (`is_terminated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sick_plan_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sick_plan_catalog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(100) NOT NULL,
  `activity_code` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sick_plan_catalog_domain_activity_code_unique` (`domain`,`activity_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sick_plan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sick_plan_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sick_plan_id` bigint(20) unsigned NOT NULL,
  `catalog_id` bigint(20) unsigned NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by_id` bigint(20) unsigned DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sick_plan_items_sick_plan_id_catalog_id_unique` (`sick_plan_id`,`catalog_id`),
  KEY `sick_plan_items_catalog_id_foreign` (`catalog_id`),
  KEY `sick_plan_items_completed_by_id_foreign` (`completed_by_id`),
  KEY `sick_plan_items_author_id_foreign` (`author_id`),
  CONSTRAINT `sick_plan_items_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sick_plan_items_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `sick_plan_catalog` (`id`),
  CONSTRAINT `sick_plan_items_completed_by_id_foreign` FOREIGN KEY (`completed_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sick_plan_items_sick_plan_id_foreign` FOREIGN KEY (`sick_plan_id`) REFERENCES `sick_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sick_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sick_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sick_plans_patient_id_foreign` (`patient_id`),
  KEY `sick_plans_status_index` (`status`),
  CONSTRAINT `sick_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `mssql_patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sm_snowflake_track`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sm_snowflake_track` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `current_date` datetime DEFAULT NULL,
  `etl_date` varchar(40) DEFAULT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snowflake_sm_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snowflake_sm_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `etl_updated_timestamp` timestamp NOT NULL,
  `schedule_unique_identifier` varchar(20) NOT NULL,
  `cgtaskid` int(11) NOT NULL,
  `is_valid_record` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_media_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_media_ads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_nthaccounts_id` int(11) DEFAULT NULL,
  `discipline` varchar(50) DEFAULT NULL,
  `skills_diagnosis` text DEFAULT NULL,
  `shifts_needed` text DEFAULT NULL,
  `the_sale` text DEFAULT NULL,
  `ad_name` varchar(250) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `state` varchar(250) DEFAULT NULL,
  `city` varchar(250) DEFAULT NULL,
  `zipcode` varchar(255) DEFAULT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `ad_placed` tinyint(1) DEFAULT NULL,
  `actual_spend` varchar(50) DEFAULT NULL,
  `messages_received` int(11) DEFAULT NULL,
  `reach` int(11) DEFAULT NULL,
  `post_engagement` int(11) DEFAULT NULL,
  `link_clicks` int(11) DEFAULT NULL,
  `cost_per_link` varchar(200) DEFAULT NULL,
  `cost_per_message` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `ad_type` varchar(250) DEFAULT NULL,
  `ads_close_reason` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(255) DEFAULT NULL,
  `date_posted` date DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_social_ads_ad_placed_aoc_nthaccounts_id` (`ad_placed`,`aoc_nthaccounts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_media_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_media_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) DEFAULT NULL COMMENT 'PDN Ad,Therapy Ad,Academy Ad',
  `type` varchar(255) DEFAULT NULL COMMENT 'Custom Ad,General Ad',
  `budget` int(11) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `special_payer_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_payer_group` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `SpecialPayerGroupMappingID` int(11) DEFAULT NULL,
  `SpecialPayerGroupName` varchar(255) DEFAULT NULL,
  `InsuranceID` int(11) DEFAULT NULL,
  `BillingInsuranceID` int(11) DEFAULT NULL,
  `PatientAccount` varchar(255) DEFAULT NULL,
  `SCA_RN_Rate` decimal(8,2) DEFAULT NULL,
  `SCA_RN_SP_Rate` decimal(8,2) DEFAULT NULL,
  `SCA_LVN_LPN_Rate` decimal(8,2) DEFAULT NULL,
  `SCA_LVN_LPN_SP_Rate` decimal(8,2) DEFAULT NULL,
  `SCA_CNA_Rate` decimal(8,2) DEFAULT NULL,
  `CreatedOn` timestamp NULL DEFAULT current_timestamp(),
  `CreatedBy` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisory_form_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisory_form_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `echart_subformid` int(11) DEFAULT NULL,
  `echart_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `form_date` timestamp NULL DEFAULT NULL,
  `form_status` text DEFAULT NULL,
  `completedby_cgid` int(11) DEFAULT NULL,
  `supervisee_cgid` int(11) DEFAULT NULL,
  `discipline` text DEFAULT NULL,
  `daas_last_modified_timestamp` timestamp NULL DEFAULT NULL,
  `isvalidrecord` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_supervisory_form_master_client_id` (`client_id`),
  KEY `idxr_supervisory_form_master_echart_subformid` (`echart_subformid`),
  KEY `idxr_supervisory_form_master_supervisee_cgid` (`supervisee_cgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisory_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisory_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `visit_date_due` date DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item9_score` int(11) DEFAULT NULL,
  `item10_score` int(11) DEFAULT NULL,
  `sv_uploaded_to_kantime` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `visit_not_matched` tinyint(1) NOT NULL DEFAULT 0,
  `form_sup_visit_date` date DEFAULT NULL,
  `last_visit` date DEFAULT NULL,
  `cycle` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sv_a_provider_patient_visit_status` (`aoc_provider_id`,`aoc_patient_id`,`visit_date_due`,`last_visit`,`is_deleted`),
  KEY `idx_supervisory_visits_assignedto_completedat_visitdatedue` (`assigned_to`,`completed_at`,`visit_date_due`),
  KEY `idxr_fk_supervisory_visits_aoc_provider_id` (`aoc_provider_id`),
  KEY `idxr_supervisory_visits_filters` (`aoc_provider_id`,`aoc_patient_id`,`assigned_to`,`visit_date_due`,`completed_at`,`form_sup_visit_date`),
  KEY `uq_sv_a_provider_patient_visit` (`aoc_provider_id`,`aoc_patient_id`,`visit_date_due`,`last_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisory_visits_matrix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisory_visits_matrix` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) DEFAULT NULL,
  `discipline` varchar(255) DEFAULT NULL,
  `visit_cycle` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisory_visits_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisory_visits_temp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `visit_date_due` date DEFAULT NULL,
  `item1_score` int(11) DEFAULT NULL,
  `item2_score` int(11) DEFAULT NULL,
  `item3_score` int(11) DEFAULT NULL,
  `item4_score` int(11) DEFAULT NULL,
  `item5_score` int(11) DEFAULT NULL,
  `item6_score` int(11) DEFAULT NULL,
  `item7_score` int(11) DEFAULT NULL,
  `item8_score` int(11) DEFAULT NULL,
  `item9_score` int(11) DEFAULT NULL,
  `item10_score` int(11) DEFAULT NULL,
  `sv_uploaded_to_kantime` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `visit_not_matched` tinyint(1) NOT NULL DEFAULT 0,
  `form_sup_visit_date` date DEFAULT NULL,
  `last_visit` date DEFAULT NULL,
  `cycle` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supply_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supply_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=New,1=Ordered,2=Completed',
  `ordered_at` datetime NOT NULL,
  `ordered_by` int(11) NOT NULL,
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` datetime NOT NULL,
  `acknowledged_by` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `notes_created_by` int(11) DEFAULT NULL,
  `notes_created_at` datetime DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fk_supply_requests_aoc_patient_id` (`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tags_created_by_foreign` (`created_by`),
  CONSTRAINT `tags_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp` (
  `State` varchar(10) DEFAULT NULL,
  `FacilityName` varchar(250) DEFAULT NULL,
  `PatientAccount` varchar(100) DEFAULT NULL,
  `PatientName` varchar(250) DEFAULT NULL,
  `PatientStatus` varchar(100) DEFAULT NULL,
  `is_intake` int(11) DEFAULT NULL,
  `Payer` varchar(250) DEFAULT NULL,
  `PrimaryPayer` varchar(250) DEFAULT NULL,
  `SecondaryPayer` varchar(250) DEFAULT NULL,
  `TertiaryPayer` varchar(250) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tempNRR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tempNRR` (
  `application_date` varchar(255) DEFAULT NULL,
  `clincianID` varchar(255) DEFAULT NULL,
  `HomePhone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `state1` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tempRenewables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tempRenewables` (
  `id` int(5) DEFAULT NULL,
  `ClinicianID` varchar(50) DEFAULT NULL,
  `ProviderName` varchar(50) DEFAULT NULL,
  `license_no` varchar(50) DEFAULT NULL,
  `license_state` varchar(10) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `nursys_stage` varchar(100) DEFAULT NULL,
  `nursys_mismatch` varchar(10) DEFAULT NULL,
  `nursys_sync_status` varchar(100) DEFAULT NULL,
  `nursys_error` varchar(1000) DEFAULT NULL,
  `new_expiry_date` date DEFAULT NULL,
  `matched_license_no` varchar(100) DEFAULT NULL,
  `matched_license_state` varchar(10) DEFAULT NULL,
  `Nursys_Expiry` date DEFAULT NULL,
  `mssqlProviderID` int(5) DEFAULT NULL,
  `correct_kt_license_no` varchar(100) DEFAULT NULL,
  `correct_kt_license_state` varchar(10) DEFAULT NULL,
  `bak_expiry_date` date DEFAULT NULL,
  `nursys_lastname` varchar(200) NOT NULL,
  `nursys_firstname` varchar(200) NOT NULL,
  `nursys_license_no` varchar(400) NOT NULL,
  `nursys_license_type` varchar(10) NOT NULL,
  `nursys_license_state` varchar(100) NOT NULL,
  `nursys_license_status` varchar(200) NOT NULL,
  `nursys_issue_date` date NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp_nrr_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_nrr_statistics` (
  `CalendarDate` date DEFAULT NULL,
  `Classification` varchar(255) DEFAULT NULL,
  `State` varchar(50) DEFAULT NULL,
  `PDN` int(11) DEFAULT NULL,
  `CNA` int(11) DEFAULT NULL,
  `ATT` int(11) DEFAULT NULL,
  `PNA` int(11) DEFAULT NULL,
  `PPEC` int(11) DEFAULT NULL,
  `NTH` int(11) DEFAULT NULL,
  `LHA` int(11) DEFAULT NULL,
  `IHSS` int(11) DEFAULT NULL,
  `ST` int(11) DEFAULT NULL,
  `PT` int(11) DEFAULT NULL,
  `OT` int(11) DEFAULT NULL,
  `Skilled_Visits` int(11) DEFAULT NULL,
  `Respite` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp_number`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_number` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `num` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp_rs_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_rs_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(100) DEFAULT '' COMMENT 'Module Or Table Name',
  `entity_id` int(11) DEFAULT NULL COMMENT 'Table Primary ID for Ref',
  `notes` text DEFAULT '',
  `type` varchar(100) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `nth_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `tracker_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `due_date` datetime DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `priority` int(11) DEFAULT NULL COMMENT '0 - none, 1 - low, 2 - normal, 3 - escalated, 4 - high, 5 - urgent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `misc1` varchar(255) DEFAULT '',
  `misc2` varchar(255) DEFAULT '',
  `misc3` varchar(255) DEFAULT '',
  `misc4` text DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` int(11) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `text_opt_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `text_opt_out` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(15) NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_text_opt_out_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tmpMassMessage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tmpMassMessage` (
  `id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `aoc_candidate_id` int(11) DEFAULT NULL,
  `receiver_mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `message_id` bigint(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trach_vent_class_occurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trach_vent_class_occurrences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL COMMENT 'primary key of trach_vent_classes',
  `class_date` date NOT NULL,
  `instructor_id` bigint(20) unsigned DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `class_time` time DEFAULT NULL,
  `class_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trach_vent_class_occurrences_class_id_class_date_index` (`class_id`,`class_date`),
  KEY `trach_vent_class_occurrences_instructor_id_foreign` (`instructor_id`),
  CONSTRAINT `trach_vent_class_occurrences_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `trach_vent_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trach_vent_class_occurrences_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trach_vent_class_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trach_vent_class_request` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trach_vent_class_occurrence_id` bigint(20) unsigned DEFAULT NULL COMMENT 'primary key of trach_vent_class_occurrences',
  `provider_id` int(11) DEFAULT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `discipline_snapshot` varchar(100) DEFAULT NULL,
  `phone_number_snapshot` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `outcome` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'Users ID Ref',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trach_vent_class_request_trach_vent_class_occurrence_id_foreign` (`trach_vent_class_occurrence_id`),
  CONSTRAINT `trach_vent_class_request_trach_vent_class_occurrence_id_foreign` FOREIGN KEY (`trach_vent_class_occurrence_id`) REFERENCES `trach_vent_class_occurrences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trach_vent_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trach_vent_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` bigint(20) unsigned NOT NULL,
  `location_id` int(11) NOT NULL COMMENT 'primary key of branch_locations',
  `class_type` varchar(255) DEFAULT NULL,
  `class_date` date DEFAULT NULL,
  `class_time` varchar(255) DEFAULT NULL,
  `repeat_every` enum('day','week','month') DEFAULT NULL,
  `repeat_on` varchar(255) DEFAULT NULL COMMENT 'comma-separated ISO weekday numbers (1=Monday..7=Sunday), only used when repeat_every=day',
  `repeat_until` date DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `instructor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trach_vent_classes_instructor_id_foreign` (`instructor_id`),
  KEY `trach_vent_classes_type_foreign` (`type`),
  CONSTRAINT `trach_vent_classes_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trach_vent_classes_type_foreign` FOREIGN KEY (`type`) REFERENCES `class_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `triageCalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `triageCalls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `aoc_provider_id` int(11) DEFAULT NULL,
  `callDate` date DEFAULT NULL,
  `callStartTime` time DEFAULT NULL,
  `callerFirstName` varchar(255) DEFAULT NULL,
  `callerLastName` varchar(255) DEFAULT NULL,
  `callerPhone` varchar(255) DEFAULT NULL,
  `callerEmail` varchar(255) DEFAULT NULL,
  `clientRelationship` varchar(50) DEFAULT NULL,
  `clientDOB` date DEFAULT NULL,
  `bodySystem` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `disposition` varchar(255) DEFAULT NULL,
  `isAmbulanceTransfer` tinyint(1) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `suggestedDatetime` varchar(500) DEFAULT NULL,
  `emergencyRoom` tinyint(1) DEFAULT NULL,
  `previous_action` varchar(255) DEFAULT NULL,
  `previous_additional_notes` text DEFAULT NULL,
  `step1D` tinyint(1) DEFAULT NULL,
  `step2D` tinyint(1) DEFAULT NULL,
  `step7D` tinyint(1) DEFAULT NULL,
  `step30D` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `careCoordination` varchar(500) DEFAULT NULL,
  `careCoordinationInfo` text DEFAULT NULL,
  `whereYouGone` varchar(500) DEFAULT NULL,
  `wereNewOrderRecieived` tinyint(1) DEFAULT NULL,
  `followUpAction` varchar(255) DEFAULT NULL,
  `interventionType` varchar(255) DEFAULT NULL,
  `orderDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `terminalPlayer` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `wasMedPrescribed` tinyint(1) DEFAULT NULL,
  `drugClassification` varchar(255) DEFAULT NULL,
  `hasMedicationPickedUp` tinyint(1) DEFAULT NULL,
  `emergencyDepartment` varchar(255) DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `outcome_attempt` int(11) NOT NULL DEFAULT 0,
  `attempts_24` int(11) DEFAULT 0,
  `attempts_48` int(11) DEFAULT 0,
  `attempts_7day` int(11) DEFAULT 0,
  `attempts_30day` int(11) DEFAULT 0,
  `hospitalized_1d` tinyint(1) NOT NULL DEFAULT 0,
  `ed_visited_1d` tinyint(1) NOT NULL DEFAULT 0,
  `hospitalized_2d` tinyint(1) NOT NULL DEFAULT 0,
  `ed_visited_2d` tinyint(1) NOT NULL DEFAULT 0,
  `hospitalized_7d` tinyint(1) NOT NULL DEFAULT 0,
  `ed_visited_7d` tinyint(1) NOT NULL DEFAULT 0,
  `hospitalized_30d` tinyint(1) NOT NULL DEFAULT 0,
  `ed_visited_30d` tinyint(1) NOT NULL DEFAULT 0,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_triagecalls_aoc_patient_id` (`aoc_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `triageFollowup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `triageFollowup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `triagecall_id` int(11) DEFAULT NULL,
  `aoc_patient_id` int(11) DEFAULT NULL,
  `followupType` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `contactWithCaller` tinyint(1) DEFAULT NULL,
  `callDate` date DEFAULT NULL,
  `callStartTime` time DEFAULT NULL,
  `callerFirstname` varchar(255) DEFAULT NULL,
  `callerLastname` varchar(255) DEFAULT NULL,
  `callerPhone` varchar(255) DEFAULT NULL,
  `callerEmail` varchar(255) DEFAULT NULL,
  `callerRelationship` varchar(255) DEFAULT NULL,
  `clientDOB` date DEFAULT NULL,
  `symptomStatus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `finalDisposition` varchar(255) DEFAULT NULL,
  `despositionActionDate` datetime DEFAULT NULL,
  `wasClientHospitalized` tinyint(1) DEFAULT NULL,
  `hospitalizationStartDate` datetime DEFAULT NULL,
  `hospitalizationEndDate` datetime DEFAULT NULL,
  `wasMedPrescribed` tinyint(1) DEFAULT NULL,
  `drugClassification` varchar(255) DEFAULT NULL,
  `hasMedicationPickedUp` tinyint(1) DEFAULT NULL,
  `wereNewOrderRecieived` tinyint(1) DEFAULT NULL,
  `followUpAction` varchar(255) DEFAULT NULL,
  `wasDiagnosisMade` tinyint(1) DEFAULT NULL,
  `ICDCode` varchar(255) DEFAULT NULL,
  `additionCoordination` varchar(255) DEFAULT NULL,
  `caseConferenceWithCaseManager` int(11) DEFAULT NULL,
  `questionsRegardingPlan` text DEFAULT NULL,
  `additionNote` text DEFAULT NULL,
  `previous_action` varchar(255) DEFAULT NULL,
  `previous_additional_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `interventionType` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `orderDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `survay_sent` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1= survay sent',
  `clientGoToED` tinyint(1) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_triageFollowup_triagecallid_contactWithCaller_createdat` (`triagecall_id`,`contactWithCaller`,`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `twilio_error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `twilio_error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(255) NOT NULL,
  `error_code` int(11) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `map_id` int(11) DEFAULT NULL,
  `mobile_field_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_twilio_error_logs_phone_number` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_user_id` bigint(20) unsigned NOT NULL,
  `consent_template_id` bigint(20) unsigned NOT NULL,
  `consent_version` varchar(20) NOT NULL,
  `accepted_at` datetime NOT NULL,
  `source` varchar(30) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_consents_external_user_id_foreign` (`external_user_id`),
  KEY `user_consents_consent_template_id_foreign` (`consent_template_id`),
  CONSTRAINT `user_consents_consent_template_id_foreign` FOREIGN KEY (`consent_template_id`) REFERENCES `consent_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_consents_external_user_id_foreign` FOREIGN KEY (`external_user_id`) REFERENCES `external_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_has_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_has_permission` (
  `permissions_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  KEY `user_has_permission_permissions_id_foreign` (`permissions_id`),
  CONSTRAINT `user_has_permission_permissions_id_foreign` FOREIGN KEY (`permissions_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `last_password_change_date` date DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `alternateRole` int(11) DEFAULT NULL,
  `recruiting_admin` varchar(255) DEFAULT NULL,
  `recruiting_manager` int(11) DEFAULT NULL,
  `recruiting_teamleader` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_manager_id` int(11) DEFAULT NULL,
  `branchid` text DEFAULT NULL,
  `recruiter` varchar(255) DEFAULT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '2-Disable,1-Enable',
  `google2fa_secret` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `kantime_username` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bilingual` tinyint(4) DEFAULT NULL,
  `facilityid` varchar(500) DEFAULT NULL,
  `sending_sms_number` varchar(255) DEFAULT '',
  `user_timezone` varchar(255) DEFAULT NULL,
  `sso_login` int(1) DEFAULT 0,
  `ooo_from_date` date DEFAULT NULL,
  `ooo_to_date` date DEFAULT NULL,
  `coverage_booked_goal` int(11) DEFAULT NULL,
  `tmpUpdated` tinyint(1) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `report_filters` text DEFAULT NULL,
  `ic_goal` int(11) DEFAULT NULL,
  `billing_team_lead` int(11) DEFAULT NULL,
  `billing_team_manager` int(11) DEFAULT NULL,
  `sms_radius_limit` int(11) NOT NULL DEFAULT 50,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_instructor` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_users_status_name` (`status`,`name`),
  KEY `idx_users_recruiting_teamleader` (`recruiting_teamleader`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_BU_2024_04_28`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_BU_2024_04_28` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `last_password_change_date` date DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `alternateRole` int(11) DEFAULT NULL,
  `recruiting_admin` varchar(255) DEFAULT NULL,
  `recruiting_manager` int(11) DEFAULT NULL,
  `recruiting_teamleader` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_manager_id` int(11) DEFAULT NULL,
  `branchid` text DEFAULT NULL,
  `recruiter` varchar(255) DEFAULT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-Disable,1-Enable',
  `google2fa_secret` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `kantime_username` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bilingual` tinyint(4) DEFAULT NULL,
  `facilityid` varchar(500) DEFAULT NULL,
  `sending_sms_number` varchar(255) DEFAULT '',
  `user_timezone` varchar(255) DEFAULT NULL,
  `sso_login` int(1) DEFAULT 0,
  `ooo_from_date` date DEFAULT NULL,
  `ooo_to_date` date DEFAULT NULL,
  `coverage_booked_goal` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_BU_2025_07_31`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_BU_2025_07_31` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `last_password_change_date` date DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `alternateRole` int(11) DEFAULT NULL,
  `recruiting_admin` varchar(255) DEFAULT NULL,
  `recruiting_manager` int(11) DEFAULT NULL,
  `recruiting_teamleader` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_manager_id` int(11) DEFAULT NULL,
  `branchid` text DEFAULT NULL,
  `recruiter` varchar(255) DEFAULT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-Disable,1-Enable',
  `google2fa_secret` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `kantime_username` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bilingual` tinyint(4) DEFAULT NULL,
  `facilityid` varchar(500) DEFAULT NULL,
  `sending_sms_number` varchar(255) DEFAULT '',
  `user_timezone` varchar(255) DEFAULT NULL,
  `sso_login` int(1) DEFAULT 0,
  `ooo_from_date` date DEFAULT NULL,
  `ooo_to_date` date DEFAULT NULL,
  `coverage_booked_goal` int(11) DEFAULT NULL,
  `tmpUpdated` tinyint(1) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `report_filters` text DEFAULT NULL,
  `ic_goal` int(11) DEFAULT NULL,
  `billing_team_lead` int(11) DEFAULT NULL,
  `billing_team_manager` int(11) DEFAULT NULL,
  `sms_radius_limit` int(11) NOT NULL DEFAULT 50,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_bad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_bad` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `last_password_change_date` date DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `alternateRole` int(11) DEFAULT NULL,
  `recruiting_admin` varchar(255) DEFAULT NULL,
  `recruiting_manager` int(11) DEFAULT NULL,
  `recruiting_teamleader` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `exec_manager_id` int(11) DEFAULT NULL,
  `branchid` text DEFAULT NULL,
  `recruiter` varchar(255) DEFAULT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0-Disable,1-Enable',
  `google2fa_secret` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `kantime_username` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bilingual` tinyint(4) DEFAULT NULL,
  `facilityid` varchar(500) DEFAULT NULL,
  `sending_sms_number` varchar(255) DEFAULT '',
  `user_timezone` varchar(255) DEFAULT NULL,
  `sso_login` int(1) DEFAULT 0,
  `ooo_from_date` date DEFAULT NULL,
  `ooo_to_date` date DEFAULT NULL,
  `coverage_booked_goal` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_has_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_users_has_roles_user_id_role_id` (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_has_roles_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_has_roles_new` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vaccine_doses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vaccine_doses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `immunization_tracker_id` bigint(20) unsigned NOT NULL,
  `sequence_number` tinyint(3) unsigned DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `earliest_date` datetime NOT NULL,
  `administered_date` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `status_reason` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_vaccine_doses_tracker_id` (`immunization_tracker_id`),
  KEY `vaccine_doses_status_index` (`status`),
  KEY `vaccine_doses_due_date_index` (`due_date`),
  CONSTRAINT `fk_vaccine_doses_tracker_id` FOREIGN KEY (`immunization_tracker_id`) REFERENCES `immunization_trackers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whiteboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whiteboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `row_number` int(11) NOT NULL,
  `row_id` varchar(100) NOT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `hire_date` varchar(50) DEFAULT NULL,
  `candidate_start_hour_goals` int(11) DEFAULT NULL,
  `ipea_goal` int(11) DEFAULT NULL,
  `pepi_wepi_goal` int(11) DEFAULT NULL,
  `client_match_goal` int(11) DEFAULT NULL,
  `client_match_hours_goal` int(11) DEFAULT NULL,
  `candidate_start_goal` int(11) DEFAULT NULL,
  `candidate_weekly_start_goal` int(11) DEFAULT NULL,
  `ipea_coversion_per` int(11) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_therapy` tinyint(4) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `primary` tinyint(1) NOT NULL DEFAULT 0,
  `tmpTotalCandidateStartHours` int(11) NOT NULL DEFAULT 0,
  `tmpCandidateStartHoursGoal` int(11) NOT NULL DEFAULT 0,
  `tmpPercentageOfGoal` double(5,2) NOT NULL DEFAULT 0.00,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idxr_whiteboard_userid_rowid_region` (`user_id`,`row_id`,`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whiteboard-2025-06-05`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whiteboard-2025-06-05` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `row_number` int(11) NOT NULL,
  `row_id` varchar(100) NOT NULL,
  `territory` varchar(255) DEFAULT NULL,
  `hire_date` varchar(50) DEFAULT NULL,
  `candidate_start_hour_goals` int(11) DEFAULT NULL,
  `ipea_goal` int(11) DEFAULT NULL,
  `pepi_wepi_goal` int(11) DEFAULT NULL,
  `client_match_goal` int(11) DEFAULT NULL,
  `client_match_hours_goal` int(11) DEFAULT NULL,
  `candidate_start_goal` int(11) DEFAULT NULL,
  `ipea_coversion_per` int(11) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_updated` tinyint(1) NOT NULL DEFAULT 0,
  `primary` tinyint(1) NOT NULL DEFAULT 0,
  `tmpTotalCandidateStartHours` int(11) NOT NULL DEFAULT 0,
  `tmpCandidateStartHoursGoal` int(11) NOT NULL DEFAULT 0,
  `tmpPercentageOfGoal` double(5,2) NOT NULL DEFAULT 0.00,
  `last_modified_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'IGNORE_SPACE,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `format_dates` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
CREATE FUNCTION `format_dates`(dates_str TEXT) RETURNS text CHARSET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci
    DETERMINISTIC
BEGIN
    DECLARE date_value TEXT;
    DECLARE formatted_dates TEXT DEFAULT "";
    DECLARE temp_date TEXT;

    WHILE CHAR_LENGTH(dates_str) > 0 DO
            SET date_value = TRIM(SUBSTRING_INDEX(dates_str, ",", 1));
            SET temp_date = DATE_FORMAT(STR_TO_DATE(date_value, "%Y-%m-%d"), "%m/%d/%Y");
            SET formatted_dates = CONCAT(formatted_dates, IF(formatted_dates != "", ", ", ""), temp_date);
            SET dates_str = SUBSTRING(dates_str FROM CHAR_LENGTH(date_value) + 2);
        END WHILE;

    RETURN formatted_dates;
END ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` VALUES (3,'2022_03_10_133130_create_map1s_table',2);
INSERT INTO `migrations` VALUES (4,'2022_03_10_133130_create_map2s_table',3);
INSERT INTO `migrations` VALUES (5,'2022_03_29_113357_add_status_field_in_users_table',4);
INSERT INTO `migrations` VALUES (6,'2022_03_30_100733_create_activity_log_table',4);
INSERT INTO `migrations` VALUES (7,'2022_04_01_140356_map_configurations',4);
INSERT INTO `migrations` VALUES (8,'2022_04_01_141010_map_configurations_add_field',4);
INSERT INTO `migrations` VALUES (9,'2022_03_31_163909_create_location_table',5);
INSERT INTO `migrations` VALUES (10,'2022_04_05_170145_create_table_cold_calling',6);
INSERT INTO `migrations` VALUES (11,'2022_04_06_104758_create_table_text_blast',6);
INSERT INTO `migrations` VALUES (12,'2022_04_06_113020_create_table_coverage_clinician',6);
INSERT INTO `migrations` VALUES (13,'2022_04_11_061408_core_setting',7);
INSERT INTO `migrations` VALUES (14,'2022_04_12_103328_update_field_default_value_in_location_table',8);
INSERT INTO `migrations` VALUES (15,'2022_04_12_103350_restricted_password',9);
INSERT INTO `migrations` VALUES (17,'2022_04_13_092626_add_location_search_field_in_location_table',11);
INSERT INTO `migrations` VALUES (18,'2022_04_12_102223_process_settings',12);
INSERT INTO `migrations` VALUES (19,'2022_04_15_110534_create_permission_roles_tables',13);
INSERT INTO `migrations` VALUES (20,'2022_04_20_090703_add_needs_geocoding_in_all_map_tables',14);
INSERT INTO `migrations` VALUES (21,'2022_04_21_152832_change_lat_lng_field_type_in_location_table',15);
INSERT INTO `migrations` VALUES (22,'2022_04_22_162434_add_column_to_map_configurations',16);
INSERT INTO `migrations` VALUES (23,'2022_04_25_111815_polygons',17);
INSERT INTO `migrations` VALUES (24,'2022_04_26_163255_employment_status',18);
INSERT INTO `migrations` VALUES (25,'2022_04_26_164406_facilities',18);
INSERT INTO `migrations` VALUES (26,'2022_04_26_165834_providers',18);
INSERT INTO `migrations` VALUES (27,'2022_04_27_063506_patients',18);
INSERT INTO `migrations` VALUES (28,'2022_04_27_112908_add_column_default_filter_to_map_configurations',18);
INSERT INTO `migrations` VALUES (29,'2022_04_28_105243_alter_employment_status_facilities_providers_patients',19);
INSERT INTO `migrations` VALUES (30,'2022_04_29_072216_mssql_recruiter_tracker',20);
INSERT INTO `migrations` VALUES (31,'2022_05_03_130616_candidates',20);
INSERT INTO `migrations` VALUES (32,'2022_05_03_130624_add_column_to_user_table_for_recruiter',20);
INSERT INTO `migrations` VALUES (33,'2022_05_11_110021_add_column_to_user_table_for_branchid',21);
INSERT INTO `migrations` VALUES (34,'2022_05_13_101611_add_user_id_column_to_mssql_recruiter_tracker',22);
INSERT INTO `migrations` VALUES (35,'2022_05_13_140643_add_column_to_map_configuration_for_boolean_type',22);
INSERT INTO `migrations` VALUES (36,'2022_05_16_071706_recruiting_schedule',23);
INSERT INTO `migrations` VALUES (37,'2022_05_16_094832_schedule_types',23);
INSERT INTO `migrations` VALUES (38,'2022_05_16_105411_notes',23);
INSERT INTO `migrations` VALUES (39,'2022_05_16_121146_add_live_ipea_status_column_to_mssql_recruiter_tracker',23);
INSERT INTO `migrations` VALUES (40,'2022_05_17_152534_add_column_to_map_configuration_for_map_data_load',24);
INSERT INTO `migrations` VALUES (41,'2022_05_20_024316_add_columns_to_candidate',25);
INSERT INTO `migrations` VALUES (42,'2022_05_18_152046_add_column_to_usertable_superior_field',26);
INSERT INTO `migrations` VALUES (43,'2022_05_19_165448_rename_column_to_user_table',26);
INSERT INTO `migrations` VALUES (44,'2022_05_21_035157_add-application-table',27);
INSERT INTO `migrations` VALUES (45,'2022_05_21_041025_add-jobs-table',27);
INSERT INTO `migrations` VALUES (46,'2022_05_21_214751_add_banned_whitelisted_ip_table',28);
INSERT INTO `migrations` VALUES (48,'2022_05_22_235321_add_column_to_mssql_recruitertracker',29);
INSERT INTO `migrations` VALUES (49,'2022_05_23_013532_add_column_to_recruiting_schedules',30);
INSERT INTO `migrations` VALUES (50,'2022_05_23_030055_add_whiteboard_table',31);
INSERT INTO `migrations` VALUES (51,'2022_05_23_161637_add_column_to_mssql_providers',32);
INSERT INTO `migrations` VALUES (52,'2022_05_20_085651_add_column_department_to_roles',33);
INSERT INTO `migrations` VALUES (53,'2022_05_20_095654_add_column_for_general_configuration',33);
INSERT INTO `migrations` VALUES (54,'2022_05_20_105344_add_columns_to_users',33);
INSERT INTO `migrations` VALUES (55,'2022_05_23_133734_remove_column_from_users',33);
INSERT INTO `migrations` VALUES (56,'2022_05_25_001500_add_new_id_cols_to_mssql_recruitertracker',34);
INSERT INTO `migrations` VALUES (57,'2022_05_25_010611_add_new_clinician_col_to_recruiting_schedules',35);
INSERT INTO `migrations` VALUES (58,'2022_05_25_014542_add_is_updated_to_recruiting_schedule',36);
INSERT INTO `migrations` VALUES (59,'2022_05_25_021558_add_full_name_to_candidates',37);
INSERT INTO `migrations` VALUES (60,'2022_05_25_022925_add_application_date_to_candidates',38);
INSERT INTO `migrations` VALUES (61,'2022_05_25_022940_add_application_date_to_mssql_recruiter_tracker',39);
INSERT INTO `migrations` VALUES (62,'2022_05_25_024514_add_fullname_to_mssql_providers',40);
INSERT INTO `migrations` VALUES (63,'2022_05_24_180445_change_field_type_for_gp_approved_by_in_recruiting_schedule_table',41);
INSERT INTO `migrations` VALUES (64,'2022_05_31_104848_add_fields_to_notes',42);
INSERT INTO `migrations` VALUES (65,'2022_06_01_073943_add_attempt_count_column_to_recruiting_schedules',42);
INSERT INTO `migrations` VALUES (66,'2022_06_01_104509_add_outcome_column_to_recruiting_schedules',42);
INSERT INTO `migrations` VALUES (67,'2022_06_02_104720_remove_fields_to_notes',42);
INSERT INTO `migrations` VALUES (68,'2022_06_03_084605_add_sort_column_to_schedule_types',42);
INSERT INTO `migrations` VALUES (69,'2022_06_03_123006_update_login_restrictions',42);
INSERT INTO `migrations` VALUES (70,'2022_06_07_020258_add-fields-to-mssql_facilities',42);
INSERT INTO `migrations` VALUES (71,'2022_06_07_022116_add-fields-to-mssql_recruitertracker_table',42);
INSERT INTO `migrations` VALUES (72,'2022_06_07_113707_add_why_nurse_chosen_and_is_rehire_to_recruitertracker',43);
INSERT INTO `migrations` VALUES (74,'2022_06_07_220802_recruiting_outcomes',44);
INSERT INTO `migrations` VALUES (76,'2022_06_08_143434_add-payer-to-mssql_patients',45);
INSERT INTO `migrations` VALUES (77,'2022_06_09_063506_intake',46);
INSERT INTO `migrations` VALUES (78,'2022_06_10_193535_add-intake-to-mssql_patients',47);
INSERT INTO `migrations` VALUES (79,'2022_06_07_130543_create_table_ipea_email_attachments',48);
INSERT INTO `migrations` VALUES (80,'2022_06_09_095613_add_field_to_users_table',48);
INSERT INTO `migrations` VALUES (81,'2022_06_09_131340_add_field_to_facility_table',48);
INSERT INTO `migrations` VALUES (82,'2022_06_13_101614_add_events_fields_to_recruiting_schedule',49);
INSERT INTO `migrations` VALUES (83,'2022_06_15_092122_add_ipea_fields_to_recruiting_schedule',49);
INSERT INTO `migrations` VALUES (84,'2022_06_16_172011_add_ipea_fields_to_mssql_recruitertracker',49);
INSERT INTO `migrations` VALUES (85,'2022_06_17_111659_add_recruiting_admin_to_users',49);
INSERT INTO `migrations` VALUES (86,'2022_06_17_230038_add_inactive_reason_to_tracker',49);
INSERT INTO `migrations` VALUES (87,'2022_06_20_164524_add_ipea_time_to_mssql_recruitertracker',18);
INSERT INTO `migrations` VALUES (88,'2022_06_20_235318_add_patient_name_to_mssql_patients',50);
INSERT INTO `migrations` VALUES (89,'2022_06_21_012349_add_search_name_to_mssql_providers',51);
INSERT INTO `migrations` VALUES (90,'2022_06_21_014459_add_search_name_to_candidates',52);
INSERT INTO `migrations` VALUES (91,'2022_06_21_150220_add_mssql_patientstatus_table',53);
INSERT INTO `migrations` VALUES (92,'2022_06_22_131030_notifications',54);
INSERT INTO `migrations` VALUES (93,'2022_06_22_225206_branch_locations',54);
INSERT INTO `migrations` VALUES (94,'2022_06_22_232544_add-ipea_location-to-branch_locations',54);
INSERT INTO `migrations` VALUES (95,'2022_06_27_003432_add_nurse_status_to_mssql_recuitertracker',55);
INSERT INTO `migrations` VALUES (96,'2022_06_28_004844_add_candidateid_to_candidate_applications',56);
INSERT INTO `migrations` VALUES (97,'2022_06_28_122210_add_and_rename_columns_notifications',57);
INSERT INTO `migrations` VALUES (98,'2022_06_29_203150_change_gp_length',57);
INSERT INTO `migrations` VALUES (99,'2022_06_30_205303_add_training_shift_time_to_mssql_recruitertracker',58);
INSERT INTO `migrations` VALUES (100,'2022_07_02_114310_add-deleted-fields',59);
INSERT INTO `migrations` VALUES (101,'2022_07_05_092833_add_created_by_fields',60);
INSERT INTO `migrations` VALUES (102,'2022_07_06_164090_create_featureflag_table',60);
INSERT INTO `migrations` VALUES (103,'2022_07_06_185126_add_owner_to_feature_flags',60);
INSERT INTO `migrations` VALUES (106,'2022_07_10_185508_mssql_schedulemaster_table',61);
INSERT INTO `migrations` VALUES (107,'2022_07_11_013039_alter_ipea_fields_to_recruiting_schedule',62);
INSERT INTO `migrations` VALUES (108,'2022_07_11_094355_add_branch_email_to_branch_locations',62);
INSERT INTO `migrations` VALUES (109,'2022_07_12_051410_add_branch_notes_and_reason_to_recruiting_schedule',62);
INSERT INTO `migrations` VALUES (110,'2022_07_17_193344_add_school_setup_date_to_mssql_recruitertracker',63);
INSERT INTO `migrations` VALUES (111,'2022_07_18_111812_alter_ipea_clinician_arrived_to_recruiting_schedule',64);
INSERT INTO `migrations` VALUES (112,'2022_07_18_222922_add_hire_date_to_whiteboard_table',65);
INSERT INTO `migrations` VALUES (113,'2022_07_20_220520_change_onboarding_status_options',66);
INSERT INTO `migrations` VALUES (114,'2022_07_20_035430_add_fields_to_candidates',67);
INSERT INTO `migrations` VALUES (115,'2022_07_20_044538_add_new_field_linked_ipea_to_mssql_recruitingtracker',67);
INSERT INTO `migrations` VALUES (116,'2022_07_21_040613_add_field_to_candidates',67);
INSERT INTO `migrations` VALUES (117,'2022_07_22_073615_addsync_to_job_vite_candidates',68);
INSERT INTO `migrations` VALUES (118,'2022_07_22_134414_change_field_type_in_feature_flag_table',69);
INSERT INTO `migrations` VALUES (119,'2022_07_25_052243_create_table_user_has_permission',69);
INSERT INTO `migrations` VALUES (120,'2022_07_27_183251_add_facility_id_to_mssql_patients',70);
INSERT INTO `migrations` VALUES (121,'2022_07_27_053507_add_field_to_candidates_for_jobivte',71);
INSERT INTO `migrations` VALUES (122,'2022_07_28_094006_add_aoc_status_to_mssql_patients',72);
INSERT INTO `migrations` VALUES (123,'2022_07_29_032729_add_field_to_msssql_recruiter_new',73);
INSERT INTO `migrations` VALUES (124,'2022_08_07_155059_add_hours_worked_to_mssql_recruitertracker',74);
INSERT INTO `migrations` VALUES (125,'2022_08_09_041606_add_reason_nurse_declined_a_o_c_to_mssql_recruitertracker',75);
INSERT INTO `migrations` VALUES (126,'2022_08_12_065112_change_field_type_in_recruiting_schedule_table',75);
INSERT INTO `migrations` VALUES (127,'2022_08_12_101241_create_table_need_to_hire_accounts',75);
INSERT INTO `migrations` VALUES (128,'2022_08_12_104727_create_table_need_to_hire_checklist',75);
INSERT INTO `migrations` VALUES (129,'2022_08_12_134302_create_table_need_to_hire_shift_type',75);
INSERT INTO `migrations` VALUES (130,'2022_08_12_135054_create_table_need_to_hire_hub',75);
INSERT INTO `migrations` VALUES (131,'2022_08_12_135939_create_table_need_to_hire_shift_type_mapping',75);
INSERT INTO `migrations` VALUES (133,'2022_08_14_133709_add-service-to-mssql_schedulemaster',76);
INSERT INTO `migrations` VALUES (134,'2022_08_14_173730_alter_hire_date_on_whiteboard_row',77);
INSERT INTO `migrations` VALUES (135,'2022_08_14_185020_add_branch_to_jobs',78);
INSERT INTO `migrations` VALUES (136,'2022_08_16_193117_add_fields_to_nth_tables',79);
INSERT INTO `migrations` VALUES (137,'2022_08_17_194251_add-payer-group-to-mssql_patients',80);
INSERT INTO `migrations` VALUES (138,'2022_08_16_090843_alter-branchid-to-users',81);
INSERT INTO `migrations` VALUES (139,'2022_08_17_051802_create_job_categories_table',81);
INSERT INTO `migrations` VALUES (140,'2022_08_18_151102_add_index_on_jobs_candidates_tables',81);
INSERT INTO `migrations` VALUES (141,'2022_08_19_055245_add_fields_to__need_to_hire__checklist',81);
INSERT INTO `migrations` VALUES (142,'2022_08_19_061348_add_fields_to__need_to_hire__accounts',81);
INSERT INTO `migrations` VALUES (143,'2022_08_29_132820_add_field_kantime_username_to_users_table',81);
INSERT INTO `migrations` VALUES (144,'2022_08_30_161246_add_columns_to__need_to_hire__accounts',81);
INSERT INTO `migrations` VALUES (145,'2022_08_30_212204_add-currentstep-to-needtohirechecklist',82);
INSERT INTO `migrations` VALUES (146,'2022_08_30_231658_add_field_is_deleted_to_whiteboard',83);
INSERT INTO `migrations` VALUES (147,'2022_08_30_234703_add_field_primary_to_whiteboard',83);
INSERT INTO `migrations` VALUES (148,'2022_08_31_062820_add_jobvite_outcome_fields_to_candidates',83);
INSERT INTO `migrations` VALUES (149,'2022_08_31_064411_add_field_to_map_configuration_for_address2',83);
INSERT INTO `migrations` VALUES (150,'2022_08_31_073841_add_aoc_nthaccounts_id_in_need_to_hire_checklist_table',83);
INSERT INTO `migrations` VALUES (151,'2022_08_31_100942_add_title_field_in_users_table',83);
INSERT INTO `migrations` VALUES (152,'2022_08_31_111332_add_address_fields_to_need_to_hire_map',83);
INSERT INTO `migrations` VALUES (153,'2022_09_01_044222_add_profile_picture_in_users_table',83);
INSERT INTO `migrations` VALUES (154,'2022_09_01_075151_create_table_social_media_ads',83);
INSERT INTO `migrations` VALUES (155,'2022_09_05_041916_add_jobvite_outcome_fields_of_graduation_form_to_candidates',83);
INSERT INTO `migrations` VALUES (156,'2022_09_05_232413_add_unique_identifier_col_field_to_map_configuration_table',83);
INSERT INTO `migrations` VALUES (157,'2022_09_05_234907_add_is_ignored_field_to_each_map_tables',83);
INSERT INTO `migrations` VALUES (158,'2022_09_06_054707_add_fields_to_social_media_ads',83);
INSERT INTO `migrations` VALUES (159,'2022_09_07_111101_create_map_ignore_table',83);
INSERT INTO `migrations` VALUES (160,'2022_09_09_000248_change_unique_identifier_col_type_in_map_configurations_table',83);
INSERT INTO `migrations` VALUES (161,'2022_09_12_222715_add_hubcitydate_to_checklist',83);
INSERT INTO `migrations` VALUES (162,'2022_09_13_015605_add_fields_for_outcome_notes_to_candidates',83);
INSERT INTO `migrations` VALUES (163,'2022_09_15_012106_create_table_for_renewables_types',84);
INSERT INTO `migrations` VALUES (164,'2022_09_15_012123_create_table_for_renewables',84);
INSERT INTO `migrations` VALUES (165,'2022_09_15_040513_add_care_team_ids_to_mssql_providers',84);
INSERT INTO `migrations` VALUES (166,'2022_09_15_074831_add_fields_assisted_by_to_recruiting_schedule',84);
INSERT INTO `migrations` VALUES (167,'2022_09_15_094736_create_lob_table',84);
INSERT INTO `migrations` VALUES (168,'2022_09_16_094343_add_follow_up_date_to_core_setting',85);
INSERT INTO `migrations` VALUES (169,'2022_09_18_230257_add_ipea_status_field_to_recruiting_schedule_table',85);
INSERT INTO `migrations` VALUES (170,'2022_09_19_032834_add_kantime_field_for_patient',85);
INSERT INTO `migrations` VALUES (171,'2022_09_26_235346_add-churn-fields-to-providers',86);
INSERT INTO `migrations` VALUES (172,'2022_09_21_051647_add_misc_fields_to_notes',87);
INSERT INTO `migrations` VALUES (173,'2022_09_21_082401_add_type_and_rate_fields_to_mssql_recruitertracker_table',87);
INSERT INTO `migrations` VALUES (174,'2022_09_27_081858_alter_ipea_enum_fields_to_recruiting_schedule',87);
INSERT INTO `migrations` VALUES (175,'2022_09_28_085633_modify_map_ignore_fields_type',87);
INSERT INTO `migrations` VALUES (176,'2022_09_29_125428_create_nth_schedules_table',87);
INSERT INTO `migrations` VALUES (177,'2022_09_29_134059_add-cols-to-nth_schedules',87);
INSERT INTO `migrations` VALUES (178,'2022_09_30_134060_add_state_to_candidates',88);
INSERT INTO `migrations` VALUES (179,'2022_10_01_172946_add-jobid-to-candidate_applications',89);
INSERT INTO `migrations` VALUES (180,'2022_10_01_175315_add-facility-id-to-jobs',90);
INSERT INTO `migrations` VALUES (181,'2022_09_30_073830_add_field_discipline_to_recruiting_schedule_table',91);
INSERT INTO `migrations` VALUES (182,'2022_10_04_183943_add_jobvite_email_to_candidates',92);
INSERT INTO `migrations` VALUES (183,'2022_10_07_075741_add_address_field_to_needtohire_schedule',93);
INSERT INTO `migrations` VALUES (184,'2022_10_10_133044_alter_n_t_h_account_to_add_columns',93);
INSERT INTO `migrations` VALUES (185,'2022_10_10_191148_need_to_hire__audit_table',93);
INSERT INTO `migrations` VALUES (186,'2022_10_10_213009_add_contact_to_nth_schedules',93);
INSERT INTO `migrations` VALUES (187,'2022_10_11_033217_add_fields_to_nth_audit',94);
INSERT INTO `migrations` VALUES (188,'2022_10_12_055826_remove_and_add_index_in_candidates_table',95);
INSERT INTO `migrations` VALUES (189,'2022_10_12_105817_modify_date_field_for_nth_audit',95);
INSERT INTO `migrations` VALUES (190,'2022_10_12_133851_add_userid_to_candidates',95);
INSERT INTO `migrations` VALUES (191,'2022_10_14_052125_add-indexer-to-nth-tables',96);
INSERT INTO `migrations` VALUES (192,'2022_10_17_130205_alter_n_t_h_schedule',96);
INSERT INTO `migrations` VALUES (193,'2022_10_18_030839_alter_user_to_add_alternate_role_to_users',96);
INSERT INTO `migrations` VALUES (194,'2022_10_18_092542_add_skills_to_mssql_providers',97);
INSERT INTO `migrations` VALUES (195,'2022_10_18_213634_add_fields_to__need_to_hire__audit',98);
INSERT INTO `migrations` VALUES (196,'2022_10_21_131457_create_fields_bd_id__orders_received__school_district__school_name_in_mssql_patients_table',99);
INSERT INTO `migrations` VALUES (197,'2022_10_23_202027_add-nthaccounts-history-table',100);
INSERT INTO `migrations` VALUES (198,'2022_10_21_140637_add_social_media_field_in_need_to_hire_checklist_table',101);
INSERT INTO `migrations` VALUES (199,'2022_10_27_040835_create_table_need_to_hire_accounts_audit',101);
INSERT INTO `migrations` VALUES (200,'2022_10_31_034022_add-indexer-on-table-recruiting_schedule',102);
INSERT INTO `migrations` VALUES (201,'2022_10_27_135050_create_trach_vent_classes_table',103);
INSERT INTO `migrations` VALUES (202,'2022_10_31_064008_add-fields-to-mssql_recruitertracker',103);
INSERT INTO `migrations` VALUES (203,'2022_10_31_073540_create_trach_vent_class_request_table',104);
INSERT INTO `migrations` VALUES (204,'2022_11_03_045836_alter_ipea_backgroung_check_to_recruiter_schedule',105);
INSERT INTO `migrations` VALUES (205,'2022_11_03_123612_add_void_field_for_nth_audit',106);
INSERT INTO `migrations` VALUES (206,'2022_11_04_044110_add_has_badge_to_provider',107);
INSERT INTO `migrations` VALUES (207,'2022_11_08_083305_alter_ipea_backgroung_check_for_refused_to_recruiter_schedule',107);
INSERT INTO `migrations` VALUES (208,'2022_11_09_003833_add-reason-move_remaining_budget-fields-to-social_media_ads-table',108);
INSERT INTO `migrations` VALUES (209,'2022_11_09_045402_create_table_intake',108);
INSERT INTO `migrations` VALUES (210,'2022_11_09_080728_add_region_to_whiteboard_table',108);
INSERT INTO `migrations` VALUES (211,'2022_11_10_132959_add-fields-to-mssql_patients',109);
INSERT INTO `migrations` VALUES (212,'2022_11_11_062344_add_intake_id_to_mssql_recruiter_tracker',110);
INSERT INTO `migrations` VALUES (213,'2022_11_10_074458_create_afterhours_shifts_table',111);
INSERT INTO `migrations` VALUES (214,'2022_11_10_130659_create_afterhours_schedule_table',111);
INSERT INTO `migrations` VALUES (215,'2022_11_20_105121_create_providerpatient_table',112);
INSERT INTO `migrations` VALUES (216,'2022_11_15_065758_add-region-table',113);
INSERT INTO `migrations` VALUES (217,'2022_11_16_033924_add_two_fields_for_text_blast_in_checklist_table',113);
INSERT INTO `migrations` VALUES (218,'2022_11_22_052052_add-nth_schedule_ids-field-to-mssql_recruitertracker-table',114);
INSERT INTO `migrations` VALUES (219,'2022_11_23_062538_create_table_triage_calls',114);
INSERT INTO `migrations` VALUES (220,'2022_11_23_103130_reasons',114);
INSERT INTO `migrations` VALUES (221,'2022_11_24_100059_create_triage_followup_table',114);
INSERT INTO `migrations` VALUES (222,'2022_11_24_105207_create_add_field_triage_followu',114);
INSERT INTO `migrations` VALUES (223,'2022_11_25_024849_alter_table_traige_call',114);
INSERT INTO `migrations` VALUES (224,'2022_11_25_050449_create_add_new_field_triage_follow',114);
INSERT INTO `migrations` VALUES (225,'2022_11_25_093732_create_add_new_field_triagfollow',114);
INSERT INTO `migrations` VALUES (226,'2022_11_25_101700_create_add_new_field_triag_calls',114);
INSERT INTO `migrations` VALUES (227,'2022_11_25_064533_add_misc_fields_in_afterhours_schedule_table',115);
INSERT INTO `migrations` VALUES (228,'2022_11_28_070257_add_repeat_field_in_afterhours_shifts_table',115);
INSERT INTO `migrations` VALUES (229,'2022_11_29_033019_create_four_add_new_field_triag_calls',115);
INSERT INTO `migrations` VALUES (230,'2022_11_30_104020_add-facilities-jobvite_branches-fields-to-regions-table',116);
INSERT INTO `migrations` VALUES (231,'2022_11_30_224620_create-recruiter_scorecards-table',116);
INSERT INTO `migrations` VALUES (232,'2022_12_02_041105_add_notification_fields_in_afterhours_schedule_table',116);
INSERT INTO `migrations` VALUES (233,'2022_12_01_063854_create_field_for_msssql_patient',117);
INSERT INTO `migrations` VALUES (234,'2022_12_02_025414_create_monthly_field_mssql_patient',117);
INSERT INTO `migrations` VALUES (235,'2022_12_05_072010_change_field_type_for_role_in_afterhours_shifts_table',117);
INSERT INTO `migrations` VALUES (236,'2022_12_07_055250_create_client_match_sns_form_additional_field',117);
INSERT INTO `migrations` VALUES (237,'2022_12_09_085923_add_removed_from_bon_to_mssql_providers',118);
INSERT INTO `migrations` VALUES (238,'2022_12_06_031615_add_bonus_amount_to_mssql_recruitertracker_table',119);
INSERT INTO `migrations` VALUES (239,'2022_12_06_035114_create_table_coverage_tracker',119);
INSERT INTO `migrations` VALUES (240,'2022_12_08_035144_jobvite_changes_to_candidates',119);
INSERT INTO `migrations` VALUES (241,'2022_12_09_061133_alter_coverage_to_tracker_table',119);
INSERT INTO `migrations` VALUES (242,'2022_12_11_231551_add-uncontrollable-field-to-mssql_recruitertracker',119);
INSERT INTO `migrations` VALUES (243,'2022_12_13_034133_add_referred_by_to_mssql_recruitertracker_table',119);
INSERT INTO `migrations` VALUES (244,'2022_12_13_055525_add_field_to_schedulemaster',119);
INSERT INTO `migrations` VALUES (245,'2022_12_13_073613_create_table_services_code',119);
INSERT INTO `migrations` VALUES (246,'2022_12_15_071251_add-fields-to-maps_potential_nurse-table',119);
INSERT INTO `migrations` VALUES (247,'2022_12_15_082526_alter_enum_type_for_onboarding_status',119);
INSERT INTO `migrations` VALUES (248,'2022_12_15_084312_add_field_to_recruiting_schedule',119);
INSERT INTO `migrations` VALUES (249,'2022_12_20_023852_alter_suggested_timeframe_to_triage_calls',119);
INSERT INTO `migrations` VALUES (250,'2022_12_20_044749_create_table_supply_requests',120);
INSERT INTO `migrations` VALUES (251,'2022_12_21_042924_add_field_to_mssql_recruitertracker_new',120);
INSERT INTO `migrations` VALUES (252,'2022_12_19_031606_add_coverage_fields_in_maps_coverageneeds',121);
INSERT INTO `migrations` VALUES (253,'2022_12_28_184046_add_renewable_id_to_renewables',122);
INSERT INTO `migrations` VALUES (254,'2022_12_20_224532_add-is_p_r_n-field-to-mssql_patients-table',123);
INSERT INTO `migrations` VALUES (255,'2022_12_26_024428_alter_social_media_ads_add_category',123);
INSERT INTO `migrations` VALUES (256,'2022_12_26_030249_create_table_social_media_budgets',123);
INSERT INTO `migrations` VALUES (257,'2022_12_26_044215_add_field_to_mssql_recruitertracker_removal_type',123);
INSERT INTO `migrations` VALUES (258,'2022_12_28_073244_add_intake_client_in_mysql_patients_table',124);
INSERT INTO `migrations` VALUES (259,'2022_12_29_223708_add-_years_of__experience-field-to-maps_potential_nurse-table',124);
INSERT INTO `migrations` VALUES (260,'2023_01_03_124347_add_typeid_to_renewables_types',125);
INSERT INTO `migrations` VALUES (261,'2022_12_30_073803_add-fields-to-recruiter_scorecards-table',126);
INSERT INTO `migrations` VALUES (262,'2023_01_02_053409_alter_table_patient_for_authorize',126);
INSERT INTO `migrations` VALUES (263,'2023_01_02_075031_add-comments-field-to-maps_potential_nurse-table',126);
INSERT INTO `migrations` VALUES (264,'2023_01_03_090212_add_ccm_id_in_recruiting_schedule_table',126);
INSERT INTO `migrations` VALUES (265,'2023_01_05_042139_add_reason_field_to_recruiting_schedule',126);
INSERT INTO `migrations` VALUES (266,'2023_01_05_070910_add_proposed_fields_in_mssql_recruitertracker_table',126);
INSERT INTO `migrations` VALUES (267,'2023_01_06_041518_add_intervention_type_to_triage_call_follow_up',126);
INSERT INTO `migrations` VALUES (268,'2023_01_06_103146_add_fields_to_schedulemaster',126);
INSERT INTO `migrations` VALUES (269,'2023_01_10_161937_add-authorization-table',127);
INSERT INTO `migrations` VALUES (270,'2023_01_10_192431_add_uniquekey_to_renewables_table',127);
INSERT INTO `migrations` VALUES (271,'2022_12_14_073506_create_table_users_has_roles',128);
INSERT INTO `migrations` VALUES (272,'2022_12_15_033811_move_roles_of_current_users_to_users_has_roles',128);
INSERT INTO `migrations` VALUES (273,'2023_01_11_051223_add_diagnosis_fields_to_triage_followup',128);
INSERT INTO `migrations` VALUES (274,'2023_01_10_055552_add_order_fields_to_triage',129);
INSERT INTO `migrations` VALUES (275,'2023_01_13_052544_add-assigned_ccr-field-to-mssql_recruitertracker-table',130);
INSERT INTO `migrations` VALUES (276,'2023_01_19_045108_make__address_nullable_on_maps_potential_nurse_table',130);
INSERT INTO `migrations` VALUES (277,'2023_01_20_030734_add_flag_description_to_feature_flags_table',130);
INSERT INTO `migrations` VALUES (278,'2023_01_20_111638_add_followup_fileds_for_recruiting_schedule',131);
INSERT INTO `migrations` VALUES (279,'2023_01_23_082304_add_followupby_fileds_for_recruiting_schedule',131);
INSERT INTO `migrations` VALUES (280,'2023_01_24_012828_create_conversation_table',148);
INSERT INTO `migrations` VALUES (281,'2023_01_24_015731_create_conversation_reply_table',148);
INSERT INTO `migrations` VALUES (282,'2023_01_24_022944_rename_fields_by_recruiting_schedule',131);
INSERT INTO `migrations` VALUES (283,'2023_01_25_052338_add_field_to_need_to_account_and_patient',131);
INSERT INTO `migrations` VALUES (284,'2023_01_25_073304_create_mass_message_table',148);
INSERT INTO `migrations` VALUES (285,'2023_01_27_000000_add-latest_schedule-field-to-mssql_providers-table',131);
INSERT INTO `migrations` VALUES (286,'2023_01_27_044513_add-class_type-field-to-trach_vent_classes-table',131);
INSERT INTO `migrations` VALUES (287,'2023_01_27_082716_add-region-field-to-mssql_facilities-table',132);
INSERT INTO `migrations` VALUES (288,'2023_01_27_105531_add_field_to_maps_nth',133);
INSERT INTO `migrations` VALUES (289,'2023_01_31_112333_add_bilingual_to_user_table',133);
INSERT INTO `migrations` VALUES (290,'2023_01_31_231906_add-send-notification-field-to-afterhours_shifts-table',133);
INSERT INTO `migrations` VALUES (291,'2023_02_01_034941_add_field_weeekly_monthly_attempt',134);
INSERT INTO `migrations` VALUES (292,'2023_02_03_093034_add_facilityid_to_users_table',135);
INSERT INTO `migrations` VALUES (293,'2023_02_06_234011_add-fields-to-triage_calls-table',135);
INSERT INTO `migrations` VALUES (294,'2023_02_07_045423_add_terminal_player_to_triage_calls_table',135);
INSERT INTO `migrations` VALUES (295,'2023_02_08_045749_create-table-message-attachment',136);
INSERT INTO `migrations` VALUES (296,'2023_02_10_082312_add-exception_addressed_date-field-to-mssql_patients-table',136);
INSERT INTO `migrations` VALUES (298,'2023_02_12_182811_add_fields_to_whiteboard',138);
INSERT INTO `migrations` VALUES (299,'2023_02_12_134106_add_gross_margin_to_mssql_recruitertracker',139);
INSERT INTO `migrations` VALUES (300,'2023_02_10_133600_add_do_not_use_reason_in_recruiting_schedule_table',140);
INSERT INTO `migrations` VALUES (301,'2023_02_14_100815_add_sending_sms_field_in_users_table',140);
INSERT INTO `migrations` VALUES (302,'2023_02_20_041621_add-user_timezone-field-to-users-table',141);
INSERT INTO `migrations` VALUES (303,'2023_02_20_075822_add-notification5-field-in-afterhours_schedule-table',141);
INSERT INTO `migrations` VALUES (304,'2023_02_21_082631_add-result-field-to-mssql_patients-table',141);
INSERT INTO `migrations` VALUES (305,'2023_02_22_051355_add_result_field_to_need_to_hire_accounts_table',142);
INSERT INTO `migrations` VALUES (306,'2023_02_23_091751_add_activity_and_number_fields_to_map_coverage',142);
INSERT INTO `migrations` VALUES (307,'2023_02_24_054411_add_created_manually_in_coverage_tracker_table',142);
INSERT INTO `migrations` VALUES (308,'2023_02_27_232132_rename_text_call_prn_clinicians_from_loca_prn_schedule_number_to_text_call_prn_clinicians_from_local_prn_schedule_number-coverage_tracker',143);
INSERT INTO `migrations` VALUES (309,'2023_03_06_110606_add_ccr_acknowledged_to_mssql_recruitertracker_table',144);
INSERT INTO `migrations` VALUES (310,'2023_03_07_033833_add_notes_fields_to_triage_call',144);
INSERT INTO `migrations` VALUES (311,'2023_03_07_091206_add_due_ccr_notification_field_in_recruiting_schedule_table',144);
INSERT INTO `migrations` VALUES (312,'2023_03_09_033859_add_was_med_prescribed_fields_in_triage_calls_table',144);
INSERT INTO `migrations` VALUES (313,'2023_03_09_112202_add-survay_sent-field-to-triage_followup-table',144);
INSERT INTO `migrations` VALUES (314,'2023_03_10_034807_add_emergency_departments_fields_in_triage_calls_table',145);
INSERT INTO `migrations` VALUES (315,'2023_03_15_043435_create_coverage_tracker_log_table',145);
INSERT INTO `migrations` VALUES (316,'2023_03_15_060704_rename_coverage_tracker_log_fields',145);
INSERT INTO `migrations` VALUES (317,'2023_03_15_093023_create_emergency_department_table',145);
INSERT INTO `migrations` VALUES (318,'2023_03_20_060939_add_do_not_hire_detail_to_candidates_table',145);
INSERT INTO `migrations` VALUES (319,'2023_03_21_040853_coveragetracker_log_field_change',145);
INSERT INTO `migrations` VALUES (320,'2023_03_22_014054_add-aoc_provider_id-to-triage_calls-table',146);
INSERT INTO `migrations` VALUES (321,'2023_03_24_141408_add-misc4-field-to-notes-table',147);
INSERT INTO `migrations` VALUES (322,'2023_03_28_080408_convert_candidate_table_to_candidatemap',149);
INSERT INTO `migrations` VALUES (323,'2023_03_31_003253_add_last_updated_count_in_mass_message_table',150);
INSERT INTO `migrations` VALUES (324,'2023_03_27_230942_add-_text_blast_escalated_margin-field-to-_need_to_hire__checklist-table',151);
INSERT INTO `migrations` VALUES (325,'2023_04_05_061312_add-attempt_count-field-to-mssql_recruitertracker-table',152);
INSERT INTO `migrations` VALUES (326,'2023_04_14_055514_add_outcome_to_triage_call',153);
INSERT INTO `migrations` VALUES (327,'2023_04_28_054832_add_on_tracker_in_candidates_table',154);
INSERT INTO `migrations` VALUES (328,'2023_04_21_042924_add_is_read_field_in_conversation_table',155);
INSERT INTO `migrations` VALUES (329,'2023_04_24_050858_create_table_for_rightsignature',155);
INSERT INTO `migrations` VALUES (330,'2023_04_25_041458_add_field_to_right_signature',155);
INSERT INTO `migrations` VALUES (331,'2023_04_28_030024_add_field_to_rightsignaturedocument',156);
INSERT INTO `migrations` VALUES (332,'2023_04_28_061226_add_is_archive_flag_to_conversation',156);
INSERT INTO `migrations` VALUES (333,'2023_05_01_053637_add_field_to_right_signature2',156);
INSERT INTO `migrations` VALUES (334,'2023_05_01_063634_add_new_reason_for_triage_form',156);
INSERT INTO `migrations` VALUES (335,'2023_05_03_053433_add_pay_rate_fields_to_recruiter_tracker_table',156);
INSERT INTO `migrations` VALUES (336,'2023_05_05_082919_add_tracker_field_to_rightsignature',157);
INSERT INTO `migrations` VALUES (337,'2023_04_28_082054_create_mass_message_detail_table',158);
INSERT INTO `migrations` VALUES (338,'2023_05_01_091017_add-payrate_approved-fields-to-mssql_recruitertracker-table',159);
INSERT INTO `migrations` VALUES (339,'2023_05_04_225540_create-field_evaluations-table',160);
INSERT INTO `migrations` VALUES (340,'2023_05_08_054922_add_is_protected_to_map_configurations_table',160);
INSERT INTO `migrations` VALUES (341,'2023_05_11_053037_add-text_blast_radius-field-to-_need_to_hire__hub-table',161);
INSERT INTO `migrations` VALUES (346,'2023_05_15_000000_create-mssql_authorizationsmaster_daas-table',162);
INSERT INTO `migrations` VALUES (347,'2023_05_15_163126_add_columns_to_mssql_patients',163);
INSERT INTO `migrations` VALUES (348,'2023_05_15_174122_add_billing_provider_id_to_providers',164);
INSERT INTO `migrations` VALUES (350,'2023_05_15_233103_create-payment_source-table',166);
INSERT INTO `migrations` VALUES (351,'2023_05_15_225935_create-service_codes_daas-table',167);
INSERT INTO `migrations` VALUES (352,'2023_05_17_121454_add-comment-field-to-process_settings-table',168);
INSERT INTO `migrations` VALUES (353,'2023_05_12_041755_add_incoming_field_to_the_conversation_reply_table',169);
INSERT INTO `migrations` VALUES (354,'2023_05_17_053414_add_outcome_attempt_to_triage_calls_table',169);
INSERT INTO `migrations` VALUES (355,'2023_05_17_060020_add_field_reminder_count_to_right_signrature',169);
INSERT INTO `migrations` VALUES (356,'2023_05_03_121119_create_mssql_payers_table',170);
INSERT INTO `migrations` VALUES (357,'2023_05_03_122330_create_gp_escalations_table',170);
INSERT INTO `migrations` VALUES (358,'2023_05_21_225229_add-additional_comments-goals-fields-to-field_evaluations-table',170);
INSERT INTO `migrations` VALUES (359,'2023_05_23_012036_rename_table_to_aoc_documents',170);
INSERT INTO `migrations` VALUES (360,'2023_05_23_114810_add__terminal_payer_i_d_to_mssql_patients_table',170);
INSERT INTO `migrations` VALUES (361,'2023_05_23_115350_add_is_coverage_to_gp_escalations_table',170);
INSERT INTO `migrations` VALUES (362,'2023_05_22_233118_create-office_evaluations-table',171);
INSERT INTO `migrations` VALUES (363,'2023_05_25_062301_add_new_grad_field_in_mssql_recruitertracker_table',171);
INSERT INTO `migrations` VALUES (364,'2023_05_25_092025_add-evaluation_id-into-aoc_documents-table',171);
INSERT INTO `migrations` VALUES (365,'2023_05_26_033919_add_c_c_r_notes_to_recruiting_schedule',171);
INSERT INTO `migrations` VALUES (366,'2023_05_26_085501_add-fields-to-field_evaluations-table',171);
INSERT INTO `migrations` VALUES (367,'2023_05_28_230209_add-fields-to-office_evaluations-table',171);
INSERT INTO `migrations` VALUES (368,'2023_05_29_115544_add-overall_performance_score-field-to-office_evaluations-table',171);
INSERT INTO `migrations` VALUES (369,'2023_06_01_023058_add_prn_first_schedule_in_mssql_recruitertracker_table',172);
INSERT INTO `migrations` VALUES (370,'2023_06_05_052848_add_appointment_end_time_to_scheduling',173);
INSERT INTO `migrations` VALUES (371,'2023_06_07_053520_add_fields_to_office_evaluations',174);
INSERT INTO `migrations` VALUES (372,'2023_06_13_044109_create-payer_rates-table',174);
INSERT INTO `migrations` VALUES (373,'2023_06_14_042032_create_table_g_p_calculator',174);
INSERT INTO `migrations` VALUES (374,'2023_06_14_223638_add-description-field-to-aoc_documents-table',174);
INSERT INTO `migrations` VALUES (375,'2023_06_16_052036_add_approval__field_to_office__evaluations',174);
INSERT INTO `migrations` VALUES (376,'2023_06_16_061959_add_approval__field_to_office__evaluations2',174);
INSERT INTO `migrations` VALUES (377,'2023_06_19_015751_add-payer-field-to-payer_rates-table',174);
INSERT INTO `migrations` VALUES (378,'2023_06_19_232414_create-self-evaluations-table',175);
INSERT INTO `migrations` VALUES (379,'2023_06_27_082522_add-manager_comments-field-to-_need_to_hire__accounts-table',175);
INSERT INTO `migrations` VALUES (380,'2023_03_16_224500_create_devices_table',176);
INSERT INTO `migrations` VALUES (381,'2023_03_20_140405_create_external_users_table',176);
INSERT INTO `migrations` VALUES (382,'2023_05_12_111418_add_field_in_mssql_patient_table',176);
INSERT INTO `migrations` VALUES (383,'2023_05_26_131032_create_patient_vacation_table',176);
INSERT INTO `migrations` VALUES (384,'2023_06_28_114407_add_manager_id_and_exec_manager_id_to_users_table',176);
INSERT INTO `migrations` VALUES (385,'2023_06_30_073803_add_hh_status_field_in_mssql_patient_table',177);
INSERT INTO `migrations` VALUES (386,'2023_07_06_045337_add_participant_id_to_conversation',178);
INSERT INTO `migrations` VALUES (387,'2023_07_11_225559_add-remove-fields-self-evaluation-tables',179);
INSERT INTO `migrations` VALUES (388,'2023_07_20_214454_add_hh_eligible_to_mssql_patients',180);
INSERT INTO `migrations` VALUES (390,'2023_07_24_102951_add_primary_payer_id_to_mssql_patients',181);
INSERT INTO `migrations` VALUES (391,'2023_07_24_133340_change_hh_amerigroup_to_hh_eligible',182);
INSERT INTO `migrations` VALUES (392,'2023_07_24_052318_add_attempt_count_for_all_triage_call_type',183);
INSERT INTO `migrations` VALUES (393,'2023_08_01_032309_create_renewable_rules_table',184);
INSERT INTO `migrations` VALUES (394,'2023_08_02_123246_add_branch_id_to_facililtly_table',184);
INSERT INTO `migrations` VALUES (395,'2023_08_02_143032_add_outcome_field_to_renewables',184);
INSERT INTO `migrations` VALUES (396,'2023_08_07_044141_add__i_p_e_a_in_home_cargiver_to_recruiting_schedule_table',185);
INSERT INTO `migrations` VALUES (397,'2023_08_08_164011_add_daas_last_modified_to_schedulemaster',186);
INSERT INTO `migrations` VALUES (398,'2023_08_13_204412_add_cols_to_service_codes_daaas',187);
INSERT INTO `migrations` VALUES (399,'2023_08_13_212527_add_service_rate_history_table',188);
INSERT INTO `migrations` VALUES (400,'2023_08_07_070051_add_fields_to_renewables',189);
INSERT INTO `migrations` VALUES (401,'2023_08_09_020046_add_email_field_to_renewable_rules',189);
INSERT INTO `migrations` VALUES (402,'2023_08_10_080001_add_indexing_to_renewables',189);
INSERT INTO `migrations` VALUES (403,'2023_08_16_080047_add_relocating_address_to_n_t_h',190);
INSERT INTO `migrations` VALUES (404,'2023_08_21_110826_add_bonus_tier_to_mssql_recruitertracker',191);
INSERT INTO `migrations` VALUES (405,'2023_08_17_042854_add_field_to_renewable_client_care',192);
INSERT INTO `migrations` VALUES (406,'2023_08_04_115831_add_email_notification_status_to_renewables_table',193);
INSERT INTO `migrations` VALUES (409,'2023_08_25_134125_create_referring_providers_table',194);
INSERT INTO `migrations` VALUES (410,'2023_08_28_185407_add_rehiredate_to_mssql_providers',195);
INSERT INTO `migrations` VALUES (411,'2023_08_28_133143_create_patient_well_child_visits_table',196);
INSERT INTO `migrations` VALUES (412,'2023_08_28_133824_drop_mssql_intake',196);
INSERT INTO `migrations` VALUES (415,'2023_08_30_200542_add_counties_to_mssql_patients',199);
INSERT INTO `migrations` VALUES (416,'2023_08_30_211547_add_nonvbc_to_maps_nth',200);
INSERT INTO `migrations` VALUES (417,'2023_08_31_055929_insert_triage_reasons_to_database_table',201);
INSERT INTO `migrations` VALUES (418,'2023_09_04_002021_remove_next_visit_field_from_patient_well_child_visits_table',201);
INSERT INTO `migrations` VALUES (419,'2023_09_06_130947_add_primarycare_to_mssql_patients',202);
INSERT INTO `migrations` VALUES (420,'2023_08_24_053843_add_nursys_transaction_table',203);
INSERT INTO `migrations` VALUES (421,'2023_08_25_041225_add_field_to_mssql_provider',203);
INSERT INTO `migrations` VALUES (422,'2023_08_29_073731_add_field_to_renewables_for_nursys',203);
INSERT INTO `migrations` VALUES (423,'2023_09_01_052748_create_nrr_candidates_data_table',203);
INSERT INTO `migrations` VALUES (424,'2023_09_05_044546_add_c_c_r_s_attempt_count_for_new_outcome',203);
INSERT INTO `migrations` VALUES (425,'2023_09_04_050417_add_field_to_mssql_providers',204);
INSERT INTO `migrations` VALUES (426,'2023_09_11_131046_add_intake_field_in_maps_coverageneeds_table',204);
INSERT INTO `migrations` VALUES (427,'2023_09_11_132324_add__expiring_and__missing_to_renewable_rules_table',204);
INSERT INTO `migrations` VALUES (428,'2023_09_12_002027_add-field-_accepts_coverage-to-_need_to_hire__schedules-talbe',204);
INSERT INTO `migrations` VALUES (429,'2023_09_12_072542_add_new_fieklds_in_nrr_candidates_data_table',204);
INSERT INTO `migrations` VALUES (430,'2023_09_12_130121_add_new_field_to_mssql_providers',205);
INSERT INTO `migrations` VALUES (431,'2023_09_25_133504_add__c_f__not_eligible_for_rehire_to_mssql_providers',206);
INSERT INTO `migrations` VALUES (432,'2023_09_28_051348_add_default_null_value_to__rehire_date',207);
INSERT INTO `migrations` VALUES (433,'2023_09_28_072504_add_ed_and_hospitalized_fields_to_triage',207);
INSERT INTO `migrations` VALUES (434,'2023_09_28_073036_add_renewable_id_field_to_aoc_documents',207);
INSERT INTO `migrations` VALUES (435,'2023_09_29_055121_change_document_link_to_longtext',207);
INSERT INTO `migrations` VALUES (436,'2023_09_29_080336_add-index-on-_provider_i_d-mssql_schedulemaster',207);
INSERT INTO `migrations` VALUES (437,'2023_10_04_131023_create_paycom_table',208);
INSERT INTO `migrations` VALUES (438,'2023_10_09_134244_paycom_department_crosswalk',209);
INSERT INTO `migrations` VALUES (439,'2023_10_11_021956_add-ipea_attempt_count-field-to-mssql_recruitertracker-table',209);
INSERT INTO `migrations` VALUES (440,'2023_10_09_050955_rename_clinician_column_in_paycom_table',210);
INSERT INTO `migrations` VALUES (441,'2023_10_12_001656_add-schedule-by-ra-attempt-count-field-to-mssql_recruitertracker',210);
INSERT INTO `migrations` VALUES (442,'2023_10_13_053440_add-index-into-mssql_providers-and-mssql_employmentstatus-tables',210);
INSERT INTO `migrations` VALUES (443,'2023_10_13_054034_notes_table_type_field_modification',210);
INSERT INTO `migrations` VALUES (444,'2023_10_12_041115_create_table_for_ipea_checklist',211);
INSERT INTO `migrations` VALUES (445,'2023_10_12_045108_insert_ipea_checklist',211);
INSERT INTO `migrations` VALUES (446,'2023_10_13_080230_add_denied_by_and_denied_at_to_payer_rates',212);
INSERT INTO `migrations` VALUES (447,'2023_10_16_052950_add-fields-to-paycom-table',212);
INSERT INTO `migrations` VALUES (448,'2023_10_23_042646_add_client_changing_payer_in_needto_hire_accounts_table',212);
INSERT INTO `migrations` VALUES (449,'2023_10_23_061058_change_field_type_for_payer_effective_dates_inn_needto_hire_accounts_table',212);
INSERT INTO `migrations` VALUES (450,'2023_10_23_073244_add_client_changing_payer_in_map_nth_table',213);
INSERT INTO `migrations` VALUES (451,'2023_10_25_002632_add-field-date-not-meeting-nurses-column-to-mssql-patients-table',214);
INSERT INTO `migrations` VALUES (452,'2023_10_25_054847_add-ooo_from_date-and-ooo_to_date-to-users-table',214);
INSERT INTO `migrations` VALUES (453,'2023_10_26_013637_add_columns_to_mssql_providers',214);
INSERT INTO `migrations` VALUES (454,'2023_10_26_043333_triage_followup_adding_client_go_to_e_d',214);
INSERT INTO `migrations` VALUES (455,'2023_10_12_062723_create_text_opt_out_table',215);
INSERT INTO `migrations` VALUES (456,'2023_10_26_062229_add_clinician_name_to_coverage_tracker',215);
INSERT INTO `migrations` VALUES (457,'2023_10_31_005815_change_nurse_lost_client_resolved_by_datatype',215);
INSERT INTO `migrations` VALUES (458,'2023_11_02_132455_add_navigator_field_in_mssql_patient_table',216);
INSERT INTO `migrations` VALUES (459,'2023_11_06_125005_add_global_search_field_in_mssql_patients_table',217);
INSERT INTO `migrations` VALUES (460,'2023_11_09_205746_add_columns_to_providers_for_exceptions',218);
INSERT INTO `migrations` VALUES (461,'2023_11_06_225412_add-indexes-on-mssql_schedulemaster-and-service_codes-tables',219);
INSERT INTO `migrations` VALUES (462,'2023_11_07_030559_add_cough_to_reasons_table',219);
INSERT INTO `migrations` VALUES (463,'2023_11_07_034307_add_description_to_map_configurations',219);
INSERT INTO `migrations` VALUES (464,'2023_11_08_042044_create_jobvite_categoty_table',219);
INSERT INTO `migrations` VALUES (465,'2023_11_08_074847_add-_patient_i_d-field-in-mssql_providers-table',219);
INSERT INTO `migrations` VALUES (467,'2023_11_08_141100_add_fields_to_jobs_table',219);
INSERT INTO `migrations` VALUES (468,'2023_11_09_001620_add_indexes_for_requisitions',219);
INSERT INTO `migrations` VALUES (469,'2023_11_16_114802_add-eligibility-fields-to-mssql-patients-table',220);
INSERT INTO `migrations` VALUES (470,'2023_11_17_065621_change-medicaid_type-field-length',221);
INSERT INTO `migrations` VALUES (471,'2023_11_21_062729_add_has_client_over21_to_renewable_rules',222);
INSERT INTO `migrations` VALUES (472,'2023_11_21_041539_add-ipea-conversion-per-field-to-whiteboard',223);
INSERT INTO `migrations` VALUES (473,'2023_11_21_043122_add_date_posted_to_social_media_ads',223);
INSERT INTO `migrations` VALUES (474,'2023_11_20_232354_add-new-field-to-office-evaluation',224);
INSERT INTO `migrations` VALUES (475,'2023_11_23_232014_add-goal-setting-professioanl-development-field-to-office-evaluation',224);
INSERT INTO `migrations` VALUES (476,'2023_12_08_044059_add_field_date_media_refused_in_need_to_hire__account_table',224);
INSERT INTO `migrations` VALUES (477,'2023_12_11_065948_create_supervisory_visits_table',224);
INSERT INTO `migrations` VALUES (478,'2023_11_08_114421_recruiter-scorecards-table-structure-changes',225);
INSERT INTO `migrations` VALUES (479,'2023_12_19_030045_add-request-ccr-hospotal-post-coverate_shift-field-to-coverage-tracker-table',226);
INSERT INTO `migrations` VALUES (480,'2023_12_20_062450_add-coverage-id-to-recruiting-schedule-table',226);
INSERT INTO `migrations` VALUES (481,'2023_12_21_015916_create_table_supervisory_visits_matrix',226);
INSERT INTO `migrations` VALUES (482,'2023_12_28_074631_branch_location_table_point_to_map',226);
INSERT INTO `migrations` VALUES (483,'2023_12_28_100642_add_field_to_branch_locations',226);
INSERT INTO `migrations` VALUES (484,'2024_01_02_073300_add_school_setup_columns_and_school_setup_requests_table',227);
INSERT INTO `migrations` VALUES (485,'2024_01_05_042141_add_outcome_field_to_supply_request',228);
INSERT INTO `migrations` VALUES (486,'2024_01_05_075739_change_status_type_school_setup_requests',228);
INSERT INTO `migrations` VALUES (487,'2024_01_09_120126_add__last_modified_and__h_h_a_to_mssql_patients_table',229);
INSERT INTO `migrations` VALUES (488,'2024_01_19_035246_add_scr_original_status_id_to_mssql_providers',230);
INSERT INTO `migrations` VALUES (489,'2024_01_19_100512_add_data_for_supervisory_matrix_co',231);
INSERT INTO `migrations` VALUES (490,'2024_01_15_225637_add-school-name-and-school-districtfields-to-school-setup-requests-table',232);
INSERT INTO `migrations` VALUES (491,'2024_01_24_194048_add_last_nursys_sync_date_to_renewables',233);
INSERT INTO `migrations` VALUES (492,'2024_01_23_010055_add-holiday-schedule-field-to-core-setting-table',234);
INSERT INTO `migrations` VALUES (493,'2024_01_25_194048_add_nursys_error_to_renewables',235);
INSERT INTO `migrations` VALUES (494,'2024_01_25_040527_add_bill_reason_fields_in_mssql_schedulemaster_table',236);
INSERT INTO `migrations` VALUES (495,'2024_01_25_113347_add_field_to_recruitertracker_for_reassign',236);
INSERT INTO `migrations` VALUES (496,'2024_01_26_012546_add_request_oc_field_to_mssql_providers',236);
INSERT INTO `migrations` VALUES (497,'2024_01_29_070404_add_comments_for_billingto_schedule_master',236);
INSERT INTO `migrations` VALUES (498,'2024_01_30_004303_change_column_name_of_billing_fields',236);
INSERT INTO `migrations` VALUES (499,'2024_02_01_164233_add_is_updated_to_paycom',237);
INSERT INTO `migrations` VALUES (500,'2024_02_03_051108_add_userid_to_mssql_providers',238);
INSERT INTO `migrations` VALUES (501,'2024_02_03_052655_create_schedule_status_table',239);
INSERT INTO `migrations` VALUES (504,'2024_02_03_060003_change_schedule_master_columns',240);
INSERT INTO `migrations` VALUES (505,'2024_02_05_175929_add__c_f__graduate_c_n_a_to_mssql_providers',241);
INSERT INTO `migrations` VALUES (506,'2024_01_31_101644_add_fields_to_recruitertracker',242);
INSERT INTO `migrations` VALUES (507,'2024_02_02_044316_add_coverage_booked_goal_to_users',242);
INSERT INTO `migrations` VALUES (508,'2024_02_06_053236_create-table-ncc-report-weekly-calls',243);
INSERT INTO `migrations` VALUES (509,'2024_02_07_060355_create-table-ncc-report-monthly-calls',244);
INSERT INTO `migrations` VALUES (510,'2024_02_05_070524_add_outcome_fields_in_school_setup_requests_table',245);
INSERT INTO `migrations` VALUES (511,'2024_02_22_062441_add_sorting_order_to_ipea_checklist',246);
INSERT INTO `migrations` VALUES (512,'2024_02_27_121952_add_donotcontact_to_mssql_providers',247);
INSERT INTO `migrations` VALUES (513,'2024_02_28_042949_add_respite_payer_columns_to_mssql_patients_table',248);
INSERT INTO `migrations` VALUES (514,'2024_02_29_045818_add_new_date_and_flag_in_supervisory_visits_table',249);
INSERT INTO `migrations` VALUES (515,'2024_03_01_044720_add-branch-coverage-booked-goal-field-mssql-facilities-table',249);
INSERT INTO `migrations` VALUES (516,'2024_03_01_054318_add_notes_to_supply_requests_table',250);
INSERT INTO `migrations` VALUES (517,'2024_03_07_001914_add_payer_cols_to_authorizations',251);
INSERT INTO `migrations` VALUES (518,'2024_03_08_041850_add_last_visit_and_cycle_to_supervisory_visits_table',252);
INSERT INTO `migrations` VALUES (519,'2024_03_07_054313_add-is-missed-visit-field-to-mssql-schedulemaster-table',253);
INSERT INTO `migrations` VALUES (520,'2024_03_07_114537_add-indexes-on-schedulemaster-facility-patient-table',254);
INSERT INTO `migrations` VALUES (521,'2024_03_09_212134_add_terminal_payer_to_mssql_schedulemaster',255);
INSERT INTO `migrations` VALUES (522,'2024_03_11_075217_add_auth_schedule_corrected_field_in_mssql_patients_table',256);
INSERT INTO `migrations` VALUES (523,'2024_03_08_143305_add_intake_address_fields_in_coverage_tracker_table',257);
INSERT INTO `migrations` VALUES (524,'2024_03_12_051227_add_change_request_employment_to_mssql_providers',258);
INSERT INTO `migrations` VALUES (525,'2024_03_12_124125_add_zero_rate_fixed_field_in_mssql_schedulemaster_table',259);
INSERT INTO `migrations` VALUES (526,'2024_03_22_130605_add-note-cleared-by-and-note-cleared-date-fields-to-journal-notes-table',260);
INSERT INTO `migrations` VALUES (527,'2024_03_26_051904_add-_provider_i_d-field-journal-notes-table',261);
INSERT INTO `migrations` VALUES (528,'2024_03_28_060350_create_custom_fields_table',262);
INSERT INTO `migrations` VALUES (529,'2024_04_02_052408_create_text_templates_table',263);
INSERT INTO `migrations` VALUES (530,'2024_04_02_052616_create_template_categories_table',263);
INSERT INTO `migrations` VALUES (531,'2024_04_02_081934_alter-custom-fields-table',263);
INSERT INTO `migrations` VALUES (532,'2024_04_04_050630_update_supervisory_matrix_for__p_a',263);
INSERT INTO `migrations` VALUES (533,'2024_04_08_040026_rename_text_templates_table_to_templates_table',264);
INSERT INTO `migrations` VALUES (534,'2024_04_10_232227_add-frequency-fields-to-mssql-patients-table',265);
INSERT INTO `migrations` VALUES (535,'2024_04_11_011321_create-ccc-followup-calls-table',265);
INSERT INTO `migrations` VALUES (536,'2024_04_11_060837_add-sort-field-in-custom-fields-table',266);
INSERT INTO `migrations` VALUES (537,'2024_04_12_140959_change-outcome-json-field-data-type-ccc-followup-calls',267);
INSERT INTO `migrations` VALUES (538,'2024_04_16_032911_add_field_to_followup_bucket',267);
INSERT INTO `migrations` VALUES (539,'2024_04_16_041918_add-call-script-field-to-custom-fields-table',267);
INSERT INTO `migrations` VALUES (540,'2024_04_17_084157_add-dependent-fields-to-custom-fields-table',268);
INSERT INTO `migrations` VALUES (541,'2024_04_18_053451_alter-ccc-followup-calls-table-to-allow-nullable',268);
INSERT INTO `migrations` VALUES (542,'2024_04_19_121755_add-preference-tag-field-in-custom-field-table',269);
INSERT INTO `migrations` VALUES (543,'2024_04_26_052058_add_ccc_acknowledged_field_in_school_setup_table',270);
INSERT INTO `migrations` VALUES (544,'2024_04_28_233855_create-payrate-approved-on-and-by-fields',270);
INSERT INTO `migrations` VALUES (545,'2024_04_26_061202_add_gp_discipline_approved_by_to_intake',271);
INSERT INTO `migrations` VALUES (546,'2024_04_26_064206_add_auth_nurse_id_to_mssql_patients',271);
INSERT INTO `migrations` VALUES (547,'2024_05_02_054926_add_phone_in_users_table',272);
INSERT INTO `migrations` VALUES (548,'2024_05_02_114205_add_candidate_id_field_in_school_setup_requests_table',272);
INSERT INTO `migrations` VALUES (549,'2024_05_06_055751_create_call_logs8_x8_table',273);
INSERT INTO `migrations` VALUES (550,'2024_05_07_001120_add_new_field_to_intake',273);
INSERT INTO `migrations` VALUES (551,'2024_05_08_141431_add_new_table_for_events',274);
INSERT INTO `migrations` VALUES (552,'2024_05_14_032637_create_table_for_onetime_process_nursys',274);
INSERT INTO `migrations` VALUES (553,'2024_05_16_054841_add_field_to_nursys_error_log',275);
INSERT INTO `migrations` VALUES (554,'2024_05_17_035157_add_field_to_aoc_events',276);
INSERT INTO `migrations` VALUES (555,'2024_05_08_063121_change-aoc-patient-id-field-type-gp-calculator',277);
INSERT INTO `migrations` VALUES (556,'2024_05_21_063432_add_field_to_recruitertracker',277);
INSERT INTO `migrations` VALUES (557,'2024_05_23_063551_add-few-columns-mssql-patients-table',277);
INSERT INTO `migrations` VALUES (558,'2024_05_29_052944_add-few-columns-daas-mssql-patient-table',278);
INSERT INTO `migrations` VALUES (559,'2024_05_27_114901_add_completed_by_and_completd_by_to_coverage_tracker',279);
INSERT INTO `migrations` VALUES (560,'2024_05_28_044011_add_fields_in_payertable',279);
INSERT INTO `migrations` VALUES (561,'2024_05_29_033806_add_field_in_home_hire_recruitertrcker',280);
INSERT INTO `migrations` VALUES (563,'2024_06_04_122515_add_completed_by_and_completed_at_to_coverage_tracker_log',281);
INSERT INTO `migrations` VALUES (564,'2024_05_29_053325_add_new_field_to_claims_master',282);
INSERT INTO `migrations` VALUES (565,'2024_06_10_052159_add_intake_pending_reason_to_mssql_patients',283);
INSERT INTO `migrations` VALUES (566,'2024_06_10_082009_copy_supervisory_visits_table',283);
INSERT INTO `migrations` VALUES (567,'2024_06_18_085840_update_branch_coverage_booked_goal_to_mssql_facilities',284);
INSERT INTO `migrations` VALUES (568,'2024_06_24_080234_create-some-intake-outcome-in-mssql-patient-table-fields',285);
INSERT INTO `migrations` VALUES (569,'2024_06_26_001524_add-intake-ready-to-start-reason-field-mssql-patient',286);
INSERT INTO `migrations` VALUES (570,'2024_06_24_003145_add-bd-rep-field-to-mssql-patients-table',287);
INSERT INTO `migrations` VALUES (571,'2024_06_26_075402_add-filter-field-to-users-table',288);
INSERT INTO `migrations` VALUES (572,'2024_07_01_114734_add-non-intake-outcome-fields-mssql-patient-table',289);
INSERT INTO `migrations` VALUES (573,'2024_07_04_144626_add_field_to_aoc_documents',289);
INSERT INTO `migrations` VALUES (574,'2024_07_02_042108_add_intake_approved_date_to_mssql_patients',290);
INSERT INTO `migrations` VALUES (575,'2024_07_17_143637_add_details_field_in_claim_master_table',291);
INSERT INTO `migrations` VALUES (576,'2024_08_12_131328_add_billing_sub_reason_field_in_mssql_schedulemaster_table',292);
INSERT INTO `migrations` VALUES (577,'2024_08_05_060452_modifications_to_paycom_table',293);
INSERT INTO `migrations` VALUES (578,'2024_08_28_065409_add_ic_goal_to_users',294);
INSERT INTO `migrations` VALUES (579,'2024_09_24_054459_add-deleted-reason-deleted-by-deleted-date-field-in-field-evaluation-table',295);
INSERT INTO `migrations` VALUES (580,'2024_09_24_074858_add_field_to_polygon',296);
INSERT INTO `migrations` VALUES (581,'2024_09_24_143615_create_twilio_error_logs_table',296);
INSERT INTO `migrations` VALUES (582,'2024_09_30_052127_add_field_in_twilio_error_logs_table',297);
INSERT INTO `migrations` VALUES (583,'2024_08_06_062623_add_ref_data_to_mssql_patients',298);
INSERT INTO `migrations` VALUES (584,'2024_09_30_142726_add_new_field_to_recruitertracker_for_prn',299);
INSERT INTO `migrations` VALUES (585,'2024_09_26_063116_create-prn-branch-crosswalk-table',300);
INSERT INTO `migrations` VALUES (586,'2024_09_27_075126_add-prn_branch-shift_type-in-tracker-table',300);
INSERT INTO `migrations` VALUES (587,'2024_10_02_054953_add-candidate-type-in-candidate-table',300);
INSERT INTO `migrations` VALUES (588,'2024_10_02_064351_add-coverage-coorinator-id-to-provider-table',300);
INSERT INTO `migrations` VALUES (589,'2024_10_03_050959_add-days-available-field-in-mssql-tracker-table',300);
INSERT INTO `migrations` VALUES (590,'2024_10_14_023547_add_field_for_active_coverageandattempt',301);
INSERT INTO `migrations` VALUES (591,'2024_10_16_002412_delete-prn-branch-crosswalk-table',302);
INSERT INTO `migrations` VALUES (592,'2024_10_21_045810_add_new_fields_to_intake',303);
INSERT INTO `migrations` VALUES (593,'2024_10_22_044652_add_field_to_mssql_patients',303);
INSERT INTO `migrations` VALUES (594,'2024_10_24_051922_add-few-fields-in-intake-table',304);
INSERT INTO `migrations` VALUES (595,'2024_10_28_054016_add_values_to_lob_table',305);
INSERT INTO `migrations` VALUES (598,'2024_11_06_140620_create-table-special-payer-group',306);
INSERT INTO `migrations` VALUES (599,'2024_11_07_044909_add_sub_reason_field_in_claims_master_table',307);
INSERT INTO `migrations` VALUES (600,'2024_11_12_072014_add_new_fields_to_intake',308);
INSERT INTO `migrations` VALUES (601,'2024_11_13_035027_add_new_fields_to_candidates',309);
INSERT INTO `migrations` VALUES (602,'2024_11_13_174100_add_intake_scheds_to_patients',310);
INSERT INTO `migrations` VALUES (603,'2024_11_15_030404_add-actual-intake-date-field-to-mssql-patients-table',311);
INSERT INTO `migrations` VALUES (604,'2024_11_19_032041_add_new_fields_to_mssql_patients',312);
INSERT INTO `migrations` VALUES (605,'2024_11_19_035704_add_new_fields_to_mssql_patients',313);
INSERT INTO `migrations` VALUES (606,'2024_11_19_052440_add-bucket-field-in-mssql-patients-and-intake-tables',314);
INSERT INTO `migrations` VALUES (607,'2024_11_28_043921_create_claims_master_log_table',315);
INSERT INTO `migrations` VALUES (608,'2024_11_28_053002_add_new_bd_id_to_intake',315);
INSERT INTO `migrations` VALUES (609,'2024_12_16_065129_add_new_billing_team_lead_and_billing_team_manager_to_users',316);
INSERT INTO `migrations` VALUES (610,'2024_12_16_065143_add_new_assigned_to_to_claims_master',316);
INSERT INTO `migrations` VALUES (611,'2024_12_16_114221_add_new_category_to_claims_master',316);
INSERT INTO `migrations` VALUES (612,'2024_12_18_123114_add_adjustment_fields_in_claim_master_table',317);
INSERT INTO `migrations` VALUES (613,'2024_12_18_123438_add_adjustment_fields_in_claims_master_log_table',317);
INSERT INTO `migrations` VALUES (614,'2024_12_20_091529_add_rejected_field_in_claims_master_table',317);
INSERT INTO `migrations` VALUES (615,'2024_12_24_124051_create-sm-snowflake-track-table',318);
INSERT INTO `migrations` VALUES (616,'2025_01_02_034300_add_clientmatch_outcome_and_clientmarch_comment_to_mssql_recruitertracker',319);
INSERT INTO `migrations` VALUES (617,'2023_07_06_093226_create_mobile_app_menu_items_table',320);
INSERT INTO `migrations` VALUES (618,'2023_07_19_094136_create_external_user_patients_table',320);
INSERT INTO `migrations` VALUES (619,'2023_07_27_113554_add_menu_type_in_mobile_app_menu_items_table',320);
INSERT INTO `migrations` VALUES (620,'2023_11_07_052948_create_mobile_otp_log_table',320);
INSERT INTO `migrations` VALUES (621,'2023_11_07_063402_add_mobile_number_in_external_users_table',320);
INSERT INTO `migrations` VALUES (622,'2024_05_15_114008_add_attempt_count_in_mobile_otp_log_table',320);
INSERT INTO `migrations` VALUES (623,'2024_07_10_094430_add_description_in_mobile_app_menu_items_table',320);
INSERT INTO `migrations` VALUES (624,'2024_08_21_141530_add_page_title_inmobile_app_menu_items_table',320);
INSERT INTO `migrations` VALUES (625,'2024_11_13_092510_add_fields_in_menu_item_table',320);
INSERT INTO `migrations` VALUES (626,'2025_01_08_133152_create_nth_inquiries_table',321);
INSERT INTO `migrations` VALUES (628,'2025_01_13_021914_add_ssu_marked_to_school_setup_requests',322);
INSERT INTO `migrations` VALUES (629,'2025_01_15_231103_add-intake_completed-field-into-intake-table',323);
INSERT INTO `migrations` VALUES (630,'2025_01_09_065742_add-schedulehours-ipeadays-columns-to-recruiting_schedule',324);
INSERT INTO `migrations` VALUES (631,'2025_01_20_131604_add_schedule_type_to_needtohire_schedule',325);
INSERT INTO `migrations` VALUES (632,'2025_01_21_041920_add_pay_range_field_in_need_to_hire__accounts_table',325);
INSERT INTO `migrations` VALUES (633,'2025_01_22_051705_add_nth_field_nth_inquiries_table',325);
INSERT INTO `migrations` VALUES (634,'2025_01_24_014844_add__field_to_social_ads',326);
INSERT INTO `migrations` VALUES (635,'2025_01_27_071848_add_requested_ops_approval_date_to_claims_master',326);
INSERT INTO `migrations` VALUES (636,'2025_02_03_041009_add_billing_bucket_in_mssql_schedulemaster_table',327);
INSERT INTO `migrations` VALUES (637,'2025_01_29_070951_add_record_and_create_tablemapalert',328);
INSERT INTO `migrations` VALUES (638,'2025_02_03_142344_add_update_fields_to_map_alertstable',329);
INSERT INTO `migrations` VALUES (639,'2025_02_04_015436_alter_field_map_alerts',329);
INSERT INTO `migrations` VALUES (640,'2025_02_07_055123_add_preference_status_field',329);
INSERT INTO `migrations` VALUES (641,'2025_02_11_041131_add_weekly_sms_attempts_in_maps_nthalerts',330);
INSERT INTO `migrations` VALUES (642,'2025_02_16_142946_change_preference_field_type',331);
INSERT INTO `migrations` VALUES (643,'2025_02_20_005914_add_fields_to_nth_inquiries',332);
INSERT INTO `migrations` VALUES (644,'2025_02_20_010719_set_autoincrements_to_nth_table',332);
INSERT INTO `migrations` VALUES (646,'2025_02_26_045905_add_field_to_mapcovclin_table',333);
INSERT INTO `migrations` VALUES (647,'2024_10_18_115336_create_temp_table_for_returnining_multiple',334);
INSERT INTO `migrations` VALUES (648,'2025_03_03_010944_add_field_to_coverage_tracker',334);
INSERT INTO `migrations` VALUES (649,'2025_02_26_124224_add_outcome_field_in_nth_inquiries_table',335);
INSERT INTO `migrations` VALUES (650,'2025_03_04_011444_add_sms_field_to_users_table',336);
INSERT INTO `migrations` VALUES (651,'2025_03_07_030324_add_new_field_to_coverage_tracker_log',336);
INSERT INTO `migrations` VALUES (652,'2025_03_04_040944_add-intake-admission-visit-first-billable-shift-field-into-intake-table',337);
INSERT INTO `migrations` VALUES (653,'2025_03_13_131602_add-lobid-field-in-service-code-daas-table',337);
INSERT INTO `migrations` VALUES (654,'2025_03_16_041629_add-remove-fields-from-payer-rates-table',338);
INSERT INTO `migrations` VALUES (655,'2025_03_25_112306_add_is_respite_to_service_codes_daas',339);
INSERT INTO `migrations` VALUES (656,'2025_03_25_141756_add_is_trackable_to_candidate_applications',340);
INSERT INTO `migrations` VALUES (657,'2025_03_31_061326_add_new_field_to_special_payer_group',341);
INSERT INTO `migrations` VALUES (658,'2025_03_31_062916_add_form_save_flage_to_school_setup_requests',342);
INSERT INTO `migrations` VALUES (659,'2025_03_31_130442_update_filed_nullable_to_school_setup_requests',342);
INSERT INTO `migrations` VALUES (660,'2025_04_08_082650_add-is-in-kantime-field-into-mssql-patients-table',343);
INSERT INTO `migrations` VALUES (661,'2025_04_14_042256_add_kantime_field_to_maps_nth',344);
INSERT INTO `migrations` VALUES (662,'2025_04_14_044535_add-has-billable-visit-to-coverage-tracker-table',345);
INSERT INTO `migrations` VALUES (663,'2025_04_17_065218_create_temp_number_table_coverage_clinicain',346);
INSERT INTO `migrations` VALUES (664,'2025_04_17_053751_add-has-billable-visit-to-recruiting-schedule-table',347);
INSERT INTO `migrations` VALUES (665,'2025_04_25_044730_add_field_to_coverage_tracker_log',348);
INSERT INTO `migrations` VALUES (666,'2025_04_28_060257_add-sort-field-into-candidate-application-state-table',349);
INSERT INTO `migrations` VALUES (667,'2025_05_02_041846_add-is-prn-to-candidate-application-table',350);
INSERT INTO `migrations` VALUES (668,'2025_05_01_093010_add_misc_field_in_notification_table',351);
INSERT INTO `migrations` VALUES (669,'2025_05_05_055525_add_info_fields_in_process_settings_table',352);
INSERT INTO `migrations` VALUES (670,'2025_05_13_061357_add_source_field_in_process_settings_table',352);
INSERT INTO `migrations` VALUES (671,'2025_06_04_092444_add-is-billed-field-into-service-code-daas-table',353);
INSERT INTO `migrations` VALUES (672,'2025_06_24_115542_add-indexer-in-service-daas-payment-service-authorize-service-authorization-table',354);
INSERT INTO `migrations` VALUES (673,'2025_08_26_062811_create_first_advantage_checks_table',355);
INSERT INTO `migrations` VALUES (674,'2025_09_02_142356_create_jobs_table',355);
INSERT INTO `migrations` VALUES (675,'2025_09_02_142817_create_failed_jobs_table',355);
INSERT INTO `migrations` VALUES (676,'2025_06_16_035100_create_tags_table',356);
INSERT INTO `migrations` VALUES (677,'2025_06_24_034426_create_claim_tag_table',356);
INSERT INTO `migrations` VALUES (678,'2025_07_11_063928_insert_reasons_seed_data',356);
INSERT INTO `migrations` VALUES (679,'2025_10_21_065339_create_cache_table',357);
INSERT INTO `migrations` VALUES (680,'2025_09_29_084915_alter_first_advantage_table_add_fi_application_status',358);
INSERT INTO `migrations` VALUES (681,'2025_10_02_164658_add_custom_fields_to_providers',358);
INSERT INTO `migrations` VALUES (682,'2025_10_03_032851_add_is_custom_to_payer_rates_table',358);
INSERT INTO `migrations` VALUES (683,'2025_10_08_075428_create_first_advantage_errors',358);
INSERT INTO `migrations` VALUES (684,'2025_10_08_082110_add_is_enabled_to_job_categories_table',358);
INSERT INTO `migrations` VALUES (685,'2025_10_08_090327_create_job_category_logs_table',358);
INSERT INTO `migrations` VALUES (686,'2025_10_13_013637_extend_nth_alert_map_config',358);
INSERT INTO `migrations` VALUES (687,'2025_10_14_132229_alter_recruiting_schedule_table_add_candidate_type',358);
INSERT INTO `migrations` VALUES (688,'2025_10_23_045027_add_resolution_field_to_nth_inquiries_table',358);
INSERT INTO `migrations` VALUES (689,'2025_11_13_014849_update_map_boolean_field_in_map_configurations',359);
INSERT INTO `migrations` VALUES (690,'2025_10_31_144745_add_background_check_permission',360);
INSERT INTO `migrations` VALUES (691,'2025_11_05_055422_move_background_chec_k_permission',360);
INSERT INTO `migrations` VALUES (692,'2025_11_05_135020_alter_first_advantage_checks_table_add_result_status',360);
INSERT INTO `migrations` VALUES (693,'2025_11_12_085632_alter_first_advantage_checks_add_type',360);
INSERT INTO `migrations` VALUES (694,'2025_12_03_073635_rollback_nth_alert_map_config',361);
INSERT INTO `migrations` VALUES (695,'2025_11_10_090608_add_custom_fields_to_candidates_table',362);
INSERT INTO `migrations` VALUES (696,'2025_11_26_065550_add_custom_field_aoc_cf_default_order_to_candidates_table',362);
INSERT INTO `migrations` VALUES (697,'2025_12_03_064741_alter_outcome_length_in_recruiting_schedule_table',362);
INSERT INTO `migrations` VALUES (698,'2025_12_04_065652_add_lead_id_to_mssql_recruitertracker_table',363);
INSERT INTO `migrations` VALUES (699,'2025_12_12_095305_add_source_notes_table',363);
INSERT INTO `migrations` VALUES (700,'2026_01_14_064500_add_last_modified_at_field_to_all_tables',364);
INSERT INTO `migrations` VALUES (701,'2026_01_09_091718_add_index_aoc_provider_id_id_to_mssql_recruitertracker_table',365);
INSERT INTO `migrations` VALUES (702,'2026_01_13_023853_add_app_source_type_source_index_to_candidates_table',366);
INSERT INTO `migrations` VALUES (703,'2025_12_26_043130_create_consent_templates_table',367);
INSERT INTO `migrations` VALUES (704,'2026_01_10_021257_create_user_consents_table',367);
INSERT INTO `migrations` VALUES (705,'2026_01_19_070956_add_idx_bill_ready_opt_index_to_mssql_schedulemaster',367);
INSERT INTO `migrations` VALUES (706,'2026_02_02_110838_add_schedule_interview_date_column_to_candidates_table',367);
INSERT INTO `migrations` VALUES (707,'2026_02_03_064802_rename_address_line_2_column_referralsource_table',367);
INSERT INTO `migrations` VALUES (708,'2019_12_14_000001_create_personal_access_tokens_table',368);
INSERT INTO `migrations` VALUES (709,'2026_02_02_173549_add_reschedule_interview_date_column_to_candidates_table',369);
INSERT INTO `migrations` VALUES (710,'2026_02_11_154625_create_offers_table',369);
INSERT INTO `migrations` VALUES (711,'2026_02_11_154715_create_offer_fields_table',369);
INSERT INTO `migrations` VALUES (712,'2026_02_11_154726_create_offer_values_table',369);
INSERT INTO `migrations` VALUES (713,'2026_02_13_090237_add_offer_extended_date_field_to_candidates_table',369);
INSERT INTO `migrations` VALUES (714,'2026_02_13_180051_create_candidate_screenings_table',369);
INSERT INTO `migrations` VALUES (715,'2026_02_16_060824_add_new_rates_to_payer_rates_table',369);
INSERT INTO `migrations` VALUES (716,'2026_02_16_160951_add_offer_accepted_date_field_to_candidates_table',369);
INSERT INTO `migrations` VALUES (717,'2026_02_02_164551_rename_fields_in_first_advantage_checks_table',370);
INSERT INTO `migrations` VALUES (718,'2026_02_03_164213_rename_fields_in_first_advantage_errors_table',370);
INSERT INTO `migrations` VALUES (719,'2026_02_03_184930_add_type_field_in_first_advantage_errors_table',370);
INSERT INTO `migrations` VALUES (720,'2026_02_05_150101_create_ipea_fa_history_table',370);
INSERT INTO `migrations` VALUES (721,'2026_02_25_132657_map_freequency_for_monthly_calls',370);
INSERT INTO `migrations` VALUES (722,'2026_02_25_161451_create_candidate_clinical_interview_questions_table',370);
INSERT INTO `migrations` VALUES (723,'2026_02_25_162524_fix_data_type_for_version_field_in_offer_values_table',370);
INSERT INTO `migrations` VALUES (724,'2026_02_26_175122_add__clinical_interview_date_filed_to_candidates_table',370);
INSERT INTO `migrations` VALUES (725,'2026_03_04_044744_delete_ff_add_hha_hour_field_to_client_profile',370);
INSERT INTO `migrations` VALUES (726,'2026_02_09_152142_additional_otp_fields',371);
INSERT INTO `migrations` VALUES (727,'2026_03_17_115619_add_index_idx_msm_patient_missed_billable_date_to_mssql_schedulemaster_table',372);
INSERT INTO `migrations` VALUES (728,'2026_03_11_045008_create_adjustments_table',373);
INSERT INTO `migrations` VALUES (729,'2026_03_27_082703_add_adjustment_category_to_claims_master_table',374);
INSERT INTO `migrations` VALUES (730,'2026_03_27_082704_crate_claim_adjustment_histories_table',374);
INSERT INTO `migrations` VALUES (731,'2026_04_20_000000_migrate_authorization_service_unique_key',375);
INSERT INTO `migrations` VALUES (732,'2026_04_23_144145_create_consent_template_logs_table',376);
INSERT INTO `migrations` VALUES (733,'2026_04_03_044955_add_candidate_weekly_start_goal_whiteboard_table',377);
INSERT INTO `migrations` VALUES (734,'2026_04_09_064601_add_last_uploaded_at_and_last_uploaded_by_user_id_columns_to_map_configurations_table',377);
INSERT INTO `migrations` VALUES (735,'2026_04_15_081750_add_pending_data_to_payer_rates_table',377);
INSERT INTO `migrations` VALUES (736,'2026_05_15_092725_add_checked_off_at_index_to_mssql_schedulemaster_table',378);
INSERT INTO `migrations` VALUES (737,'2026_05_05_073959_change_type_of_agreed_pay_rate_to_candidates_table',379);
INSERT INTO `migrations` VALUES (738,'2026_05_18_080241_add_payer_additional_fields',380);
INSERT INTO `migrations` VALUES (739,'2026_05_19_035051_create_manage_payer_gross_margin_configuration_table',381);
INSERT INTO `migrations` VALUES (740,'2026_05_19_070716_add_florida_aide_cycle_to_supervisory_visits_matrix',381);
INSERT INTO `migrations` VALUES (741,'2026_05_26_045329_add_columns_to_map_configurations_table',381);
INSERT INTO `migrations` VALUES (742,'2026_05_26_071720_replace_lyn_email_in_users_and_delete_user',381);
INSERT INTO `migrations` VALUES (743,'2026_06_09_000000_add_is_therapy_to_whiteboard_table',381);
INSERT INTO `migrations` VALUES (744,'2026_06_10_145424_encounters_table',381);
INSERT INTO `migrations` VALUES (745,'2026_06_10_145447_contacts_table',381);
INSERT INTO `migrations` VALUES (746,'2026_06_10_145458_procedures_table',381);
INSERT INTO `migrations` VALUES (747,'2026_06_10_145517_diagnosis_codes_table',381);
INSERT INTO `migrations` VALUES (748,'2026_06_10_145531_diagnosis_systems_table',381);
INSERT INTO `migrations` VALUES (749,'2026_06_10_145531_diagnosis_table',381);
INSERT INTO `migrations` VALUES (750,'2026_06_10_145554_sick_plan_catalog_table',381);
INSERT INTO `migrations` VALUES (751,'2026_06_10_145615_sick_plan_items_table',381);
INSERT INTO `migrations` VALUES (752,'2026_06_10_145632_communication_requests_table',381);
INSERT INTO `migrations` VALUES (753,'2026_06_10_145644_create_trainings_types_table',381);
INSERT INTO `migrations` VALUES (754,'2026_06_10_145645_create_trainings_table',381);
INSERT INTO `migrations` VALUES (755,'2026_06_11_162312_create_hospitalizations_table',381);
INSERT INTO `migrations` VALUES (756,'2026_06_11_165227_create_ed_visits_table',381);
INSERT INTO `migrations` VALUES (757,'2026_06_08_051858_create_payer_rate_services_table',382);
INSERT INTO `migrations` VALUES (759,'2026_06_22_000001_rename_facility_columns_in_encounters_table',383);
INSERT INTO `migrations` VALUES (760,'2026_06_23_000001_replace_encounter_with_patient_in_communication_requests_table',384);
INSERT INTO `migrations` VALUES (761,'2026_06_15_041907_soft_delete_specific_invoices',385);
INSERT INTO `migrations` VALUES (762,'2026_07_01_000001_add_unit_visit_fields_to_offer_fields_table',386);
INSERT INTO `migrations` VALUES (763,'2026_07_02_000001_backfill_unit_visit_fields_for_unsent_offers',386);
INSERT INTO `migrations` VALUES (764,'2026_07_03_000001_add_outlook_event_owner_columns_to_candidates_table',387);
INSERT INTO `migrations` VALUES (765,'2026_07_14_081346_create_api_client_map_table',388);
INSERT INTO `migrations` VALUES (766,'2026_07_14_102042_rename_api_client_map_table',388);
INSERT INTO `migrations` VALUES (768,'2026_07_24_101631_rename_ga_cna_hha_job_category',389);
INSERT INTO `migrations` VALUES (769,'2026_04_23_070951_mssql_patients_client_intake_sequence_no_field_added',390);
INSERT INTO `migrations` VALUES (774,'2026_07_28_100000_drop_legacy_vbc_tables',391);
INSERT INTO `migrations` VALUES (775,'2026_07_28_100001_create_encounters_table',391);
INSERT INTO `migrations` VALUES (776,'2026_07_28_100002_create_hospitalization_trackers_table',391);
INSERT INTO `migrations` VALUES (777,'2026_07_28_100003_create_encounter_diagnoses_table',391);
INSERT INTO `migrations` VALUES (778,'2026_07_28_100004_create_hospitalization_tracker_training_tables',392);
INSERT INTO `migrations` VALUES (779,'2026_07_28_100005_create_care_coordination_tables',392);
INSERT INTO `migrations` VALUES (780,'2026_07_28_100006_create_patient_contact_tables',392);
INSERT INTO `migrations` VALUES (781,'2026_07_28_100007_create_sick_plan_tables',392);
INSERT INTO `migrations` VALUES (782,'2026_07_28_100008_create_patient_custom_property_tables',392);
INSERT INTO `migrations` VALUES (783,'2026_07_28_100009_create_hospitalization_tracker_custom_property_tables',392);
INSERT INTO `migrations` VALUES (784,'2026_07_28_100010_create_hospitalization_tracker_followups_table',392);
INSERT INTO `migrations` VALUES (785,'2026_07_28_100011_drop_communication_requests_table',392);
INSERT INTO `migrations` VALUES (786,'2026_07_09_120001_modify_gross_margin_configurations_add_lob_enum',393);
INSERT INTO `migrations` VALUES (787,'2026_07_13_023905_soft_delete_supervisory_visits',393);
INSERT INTO `migrations` VALUES (788,'2026_07_13_023951_remove_duplicates_from_supervisory_visits',393);
INSERT INTO `migrations` VALUES (789,'2026_07_13_023959_add_unique_composite_key_to_supervisory_visits_table',393);
INSERT INTO `migrations` VALUES (790,'2026_07_27_031715_update_is_missing_field_in_renewables_table',393);
INSERT INTO `migrations` VALUES (791,'2026_08_06_120000_add-is-billable-daas-field-into-service-code-daas-table',393);
INSERT INTO `migrations` VALUES (792,'2026_08_07_120000_add-is-terminated-field-into-service-code-daas-table',393);
INSERT INTO `migrations` VALUES (793,'2026_07_15_041651_add_is_instructor_field_to_users_table',394);
INSERT INTO `migrations` VALUES (794,'2026_07_15_063851_add_instructor_id_field_to_trach_vent_classes_table',394);
INSERT INTO `migrations` VALUES (795,'2026_07_16_000001_create_class_categories_table',394);
INSERT INTO `migrations` VALUES (796,'2026_07_16_000002_seed_class_categories_table',394);
INSERT INTO `migrations` VALUES (797,'2026_07_16_000003_convert_type_to_class_category_foreign_key_on_trach_vent_classes_table',394);
INSERT INTO `migrations` VALUES (798,'2026_08_05_041135_soft_delete_school_setup_requests',394);
INSERT INTO `migrations` VALUES (799,'2026_08_05_041719_delete_invalid_records_from_school_setup_requests',394);
INSERT INTO `migrations` VALUES (800,'2026_08_11_140000_remove_duplicates_from_renewables_table',395);
INSERT INTO `migrations` VALUES (801,'2026_08_12_070356_hard_remove_duplicates_from_renewables_table',395);
INSERT INTO `migrations` VALUES (802,'2026_08_12_153133_hard_remove_invalid_rows_from_renewables',395);
INSERT INTO `migrations` VALUES (803,'2026_07_29_000001_add_clinician_enrollment_fields_to_trach_vent_class_request',396);
INSERT INTO `migrations` VALUES (804,'2026_08_14_100000_add_performance_indexes_to_notes_and_recruitertracker_tables',396);
INSERT INTO `migrations` VALUES (805,'2026_08_20_104154_delete_ga_providers_generated_by_system',397);
INSERT INTO `migrations` VALUES (806,'2026_08_26_100000_archive_stale_school_setup_requests',398);
INSERT INTO `migrations` VALUES (809,'2026_08_28_100001_change_related_routes_to_long_text_on_permissions_table',400);
INSERT INTO `migrations` VALUES (819,'2026_08_28_100000_add_tracker_fields_to_hospitalization_trackers_table',401);
INSERT INTO `migrations` VALUES (820,'2026_08_04_000001_fix_unit_visit_fields_value_type',402);
INSERT INTO `migrations` VALUES (821,'2026_08_10_000001_add_recurrence_fields_to_trach_vent_classes_table',402);
INSERT INTO `migrations` VALUES (822,'2026_08_10_000002_create_trach_vent_class_occurrences_table',402);
INSERT INTO `migrations` VALUES (823,'2026_08_10_000003_add_occurrence_id_to_trach_vent_class_request_table',402);
INSERT INTO `migrations` VALUES (824,'2026_08_10_000004_backfill_trach_vent_class_occurrences',402);
INSERT INTO `migrations` VALUES (825,'2026_08_10_000005_drop_trach_vent_class_id_from_trach_vent_class_request_table',402);
INSERT INTO `migrations` VALUES (826,'2026_08_11_120000_add_section_to_offer_fields_table',402);
INSERT INTO `migrations` VALUES (827,'2026_08_11_120100_create_offer_compensation_items_table',402);
INSERT INTO `migrations` VALUES (828,'2026_08_11_120200_backfill_offer_compensation_items_for_unsent_offers',402);
INSERT INTO `migrations` VALUES (829,'2026_08_26_120000_add_is_virtual_field_to_recruiting_schedule_table',402);
INSERT INTO `migrations` VALUES (831,'2026_09_02_042213_confirm_complete_old_mssql_recruitertrackers',403);
INSERT INTO `migrations` VALUES (832,'2026_09_15_100000_add_details_to_sick_plan_items_table',404);
INSERT INTO `migrations` VALUES (833,'2026_09_15_100001_seed_sick_plan_catalog',404);
INSERT INTO `migrations` VALUES (834,'2026_09_16_100000_add_documentation_fields_to_hospitalization_tracker_followups_table',404);
INSERT INTO `migrations` VALUES (835,'2026_09_18_100001_create_cdc_vaccine_tables',404);
INSERT INTO `migrations` VALUES (836,'2026_09_18_100002_create_immunization_recommendation_tables',404);
INSERT INTO `migrations` VALUES (837,'2026_09_18_100003_create_immunization_tracker_tables',404);
INSERT INTO `migrations` VALUES (841,'2026_09_23_100000_create_hospitalization_tracker_contacts_table',405);
INSERT INTO `migrations` VALUES (842,'2026_09_24_000000_widen_last_updated_date_in_process_settings_table',406);
INSERT INTO `migrations` VALUES (843,'2026_09_25_085614_add_deleted_at_column_for_mssql_recruitertracker_table',407);
INSERT INTO `migrations` VALUES (844,'2026_09_25_085712_cleanup_old_mssql_recruitertrackers',407);
INSERT INTO `migrations` VALUES (845,'2026_09_28_100000_add_created_by_id_to_encounter_diagnoses_table',407);
INSERT INTO `migrations` VALUES (846,'2026_09_23_000000_create_payer_priority_config_table',408);
INSERT INTO `migrations` VALUES (847,'2026_09_28_091925_cleanup_old_mssql_recruitertrackers_by_ipea_date',408);
INSERT INTO `migrations` VALUES (848,'2026_10_07_100000_create_hospitalization_tracker_training_sourcess_table',409);
INSERT INTO `migrations` VALUES (849,'2026_10_08_000000_add_original_intake_shift_columns_to_intake_table',410);
INSERT INTO `migrations` VALUES (850,'2026_10_14_000000_fix_branch_locations_latlong_fields_in_map_configurations',411);
INSERT INTO `migrations` VALUES (851,'2026_10_14_000001_requeue_branch_locations_for_geocoding',411);
INSERT INTO `migrations` VALUES (852,'2026_10_15_000000_add_child_agg_index_to_mssql_schedulemaster_table',412);
INSERT INTO `migrations` VALUES (853,'2026_10_19_113046_add_parent_child_billed_start_index_to_mssql_schedulemaster_table',413);
INSERT INTO `migrations` VALUES (854,'2026_10_20_100000_change_originating_state_to_abbrev_in_immunization_recommendations_table',414);
INSERT INTO `migrations` VALUES (855,'2026_10_26_100000_rename_hospitalization_tracker_training_sourcess_table',415);
INSERT INTO `migrations` VALUES (856,'2026_10_28_100000_add_unique_vaccine_state_to_immunization_recommendations_table',416);
