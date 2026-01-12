-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 15, 2025 at 06:22 AM
-- Server version: 5.7.36
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xee`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `case_categories`
--

DROP TABLE IF EXISTS `case_categories`;
CREATE TABLE IF NOT EXISTS `case_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=176 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `case_categories`
--

INSERT INTO `case_categories` (`id`, `category_name`) VALUES
(1, 'Criminal'),
(2, 'Civil'),
(3, 'Family'),
(4, 'Consumer'),
(5, 'Appeal'),
(11, '13-A Arms Ordinance'),
(12, '13-D Arms Ordinance'),
(13, '13-E Arms Ordinance'),
(14, '16-B Arms Ordinance'),
(15, '23 (I) A'),
(16, '23 Arms Ordinance'),
(17, '3/4 Explosive act'),
(18, 'Agricultural Act Cases'),
(19, 'Application u/s 491 CrPc'),
(20, 'Application u/s 516-A CrPc'),
(21, 'Bail Application in Cases'),
(22, 'Cancellation of Bail Appl.'),
(23, 'Car Snatching Cases'),
(24, 'Cases Under Gas Theft Act'),
(25, 'Chapter Proceeding Acts'),
(26, 'Copyright Acts'),
(27, 'Cr.P.C.'),
(28, 'Criminal Appeals'),
(29, 'Criminal Bail Application'),
(30, 'Criminal Enquires'),
(31, 'Criminal Misc. Applications'),
(32, 'Criminal Misc. Applications in session cases'),
(33, 'Criminal Petition U/S 22-A'),
(34, 'Criminal Petitions'),
(35, 'Criminal Preliminary Enquiry'),
(36, 'Criminal Reference'),
(37, 'Criminal Revisions'),
(38, 'Criminal Trans. Applications'),
(39, 'Darul Aman Acts'),
(40, 'Deleted Gamling Act'),
(41, 'Direct Complaint'),
(42, 'Ehteram-e-Ramzan'),
(43, 'Electricity Acts'),
(44, 'Environmental Act'),
(45, 'Estate Agency Act 2015'),
(46, 'Exit From Pakistan (Control) Ordinance, 1981'),
(47, 'Explosive act'),
(48, 'Fertilizer Acts'),
(49, 'Final report (A.T.A) A Class'),
(50, 'Final report (A.T.A) B Class'),
(51, 'Final report (A.T.A) C Class'),
(52, 'Fishries Acts'),
(53, 'Food Cases/Acts'),
(54, 'Foreign Exchange Regulation Act'),
(55, 'Foreigner Act'),
(56, 'Forest/Wild Life Acts'),
(57, 'Gambling Act'),
(58, 'Habeas Corpus'),
(59, 'Hudood Trial'),
(60, 'Human Rights Petition'),
(61, 'Illegal Dispossesion Act'),
(62, 'Irrigation Acts'),
(63, 'Islamic Laws'),
(64, 'JUVENILE JUSTICE SYSTEM ORDINANCE'),
(65, 'Judicial Inquiry'),
(66, 'K.M.C. Cases'),
(67, 'K.P.T./K.B.C.A. Acts'),
(68, 'Local Laws'),
(69, 'M.V.O. / Traffic Act'),
(70, 'Market/Factory/Shop Act'),
(71, 'Mines Act'),
(72, 'Minor Acts / Offences'),
(73, 'Murder Trial'),
(74, 'Other CR/Sessions Cases (If Any)'),
(75, 'PPC'),
(76, 'Pakistan Arms Ordinance, 1965'),
(77, 'Passport/Foriegn Act'),
(78, 'Pesticide Acts'),
(79, 'Petroleum Act'),
(80, 'Police Order 2002 Cases'),
(81, 'Police/FIA/Theft Acts'),
(82, 'President Ordinance'),
(83, 'Prevention of Electronic crime Act, 2016'),
(84, 'Price Control / Check Act'),
(85, 'Private Complaint'),
(86, 'Private Complaint'),
(87, 'Probation Ordinance'),
(88, 'Prohibition Ordinance (3/4)'),
(89, 'Railway/Cannt Acts'),
(90, 'S.I.Temp Resident Act 2015'),
(91, 'SBCA Cases'),
(92, 'SCMO'),
(93, 'Sindh Arms ACT, 2013'),
(94, 'Sindh Building Control Ordinance 1989/82'),
(95, 'Sindh Child Marriage Restraint Act 2013'),
(96, 'Sindh Crime Control Act'),
(97, 'Speaker / Loud Speaker Acts'),
(98, 'Special Bail Applications'),
(99, 'Special Cases (A.T.A)'),
(100, 'Special Cases (S.T.A)'),
(101, 'Special Cases Narcotics.'),
(102, 'Special Laws'),
(103, 'Summary'),
(104, 'Suo Moto Revisions'),
(105, 'T.V./V.C.R./Radio/Telegraph Acts'),
(106, 'Vegregency'),
(107, 'Weight & Measurement Acts'),
(108, 'Arbitration Appeals'),
(109, 'Cases Under Benami Transaction'),
(110, 'Civil Appeals'),
(111, 'Civil Defence'),
(112, 'Civil Executions'),
(113, 'Civil Executions Appeal'),
(114, 'Civil Misc. Appeals'),
(115, 'Civil Misc. Applications'),
(116, 'Civil Misc. Applications In Case'),
(117, 'Civil Petitions'),
(118, 'Civil References'),
(119, 'Civil Revisions'),
(120, 'Civil Transfer Applications'),
(121, 'Commercial- Civil Appeal'),
(122, 'Commercial- Civil Execution'),
(123, 'Commercial- Suit'),
(124, 'Commercial- Summary'),
(125, 'Consumer Appeal'),
(126, 'Defamation Suits'),
(127, 'Departmental Enquiry'),
(128, 'Distress Warrant Cases'),
(129, 'Election Appeals'),
(130, 'Election Petitions'),
(131, 'Encroachment Appeal'),
(132, 'Execution (Received from HC)'),
(133, 'First Rent Appeals (F.R.A.)'),
(134, 'IIIrd Class Civil Suits'),
(135, 'IInd Class Civil Suits'),
(136, 'Infrigments Suits'),
(137, 'Insolvency Petitions'),
(138, 'Interlocutory Applications'),
(139, 'Ist Class Civil Suits'),
(140, 'J.M(Received from HC)'),
(141, 'Judicial Misc. Applications'),
(142, 'Judicial Misc. Executions'),
(143, 'Land Acquisition Applications'),
(144, 'Lunacy Petitions'),
(145, 'Mental Health'),
(146, 'Misc. Rent Cases (M.R.C.)'),
(147, 'Other Civil Cases'),
(148, 'Rent Cases'),
(149, 'Rent Executions'),
(150, 'Review Application'),
(151, 'SCMO'),
(152, 'SMA (Received from HC)'),
(153, 'Sindh Waqf Properties'),
(154, 'Small Causes'),
(155, 'Suit (Received from HC)'),
(156, 'Suit for Damages'),
(157, 'Summary Executions'),
(158, 'Summary Suits'),
(159, 'Tenancy Application'),
(160, 'Tort Claims (Against Public Servants)'),
(161, 'Trade Mark Suits'),
(162, 'Trust Suits'),
(163, 'Waqaf Petitions'),
(164, 'Dissolution of Muslim Marriages Act, 1939'),
(165, 'Family Appeals'),
(166, 'Family Executions'),
(167, 'Family Precept'),
(168, 'Family Suits'),
(169, 'G&W Appeals'),
(170, 'G&W Applications'),
(171, 'G&W Cases'),
(172, 'G&W Executions'),
(173, 'Other Family Cases (If Any)'),
(174, 'S.M.A'),
(175, 'Transfer Applications');

-- --------------------------------------------------------

--
-- Table structure for table `case_documents`
--

DROP TABLE IF EXISTS `case_documents`;
CREATE TABLE IF NOT EXISTS `case_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `courtname` varchar(100) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `qc_status` enum('Approved','Pending') DEFAULT 'Pending',
  `confidentiality` enum('Non-Restricted','Restricted') DEFAULT 'Non-Restricted',
  `created_user` varchar(100) DEFAULT NULL,
  `created_court` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_id` (`case_id`)
) ENGINE=MyISAM AUTO_INCREMENT=494 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `case_status`
--

DROP TABLE IF EXISTS `case_status`;
CREATE TABLE IF NOT EXISTS `case_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `case_status`
--

INSERT INTO `case_status` (`id`, `status_name`) VALUES
(16, 'Disposed'),
(15, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `courts`
--

DROP TABLE IF EXISTS `courts`;
CREATE TABLE IF NOT EXISTS `courts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `court_code` varchar(50) NOT NULL,
  `court_fullname` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`id`, `court_code`, `court_fullname`) VALUES
(32, 'DJ-MALIR', 'District & Sessions Court Malir, Karachi'),
(34, 'ADJ-I-MALIR', 'Additional District & Sessions Court-I Malir, Karachi'),
(35, 'ADJ-II-MALIR', 'Additional District & Sessions Court-II Malir, Karachi'),
(36, 'ADJ-III-MALIR', 'Additional District & Sessions Court-III Malir, Karachi');

-- --------------------------------------------------------

--
-- Table structure for table `ctccc`
--

DROP TABLE IF EXISTS `ctccc`;
CREATE TABLE IF NOT EXISTS `ctccc` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `underSection` varchar(100) DEFAULT NULL,
  `courtname` varchar(100) DEFAULT NULL,
  `casecateg` varchar(100) DEFAULT NULL,
  `caseno` int(50) DEFAULT NULL,
  `year` varchar(15) DEFAULT NULL,
  `partyone` varchar(150) DEFAULT NULL,
  `partytwo` varchar(150) DEFAULT NULL,
  `crimeno` int(15) DEFAULT NULL,
  `crimeyear` int(15) DEFAULT NULL,
  `s_rbf` varchar(100) DEFAULT NULL,
  `dateInst` date DEFAULT NULL,
  `dateSubmission` date DEFAULT NULL,
  `dateDisp` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `cost` int(50) DEFAULT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `ps` varchar(100) DEFAULT NULL,
  `row` int(20) DEFAULT NULL,
  `shelf` varchar(20) DEFAULT NULL,
  `bundle` int(11) DEFAULT NULL,
  `file` int(11) DEFAULT NULL,
  `cfms_dc_casecode` varchar(100) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `qc_status` enum('Approved','Pending') DEFAULT 'Pending',
  `confidentiality` enum('Non-Restricted','Restricted') DEFAULT 'Non-Restricted',
  `created_by` varchar(100) DEFAULT NULL,
  `created_court` varchar(100) DEFAULT NULL,
  `ocr_complete` enum('Yes','No') DEFAULT 'No',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `document_access_logs`
--

DROP TABLE IF EXISTS `document_access_logs`;
CREATE TABLE IF NOT EXISTS `document_access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('view','download') NOT NULL,
  `accessed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`id`),
  KEY `doc_id` (`doc_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `accessed_at` (`accessed_at`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `document_metadata`
--

DROP TABLE IF EXISTS `document_metadata`;
CREATE TABLE IF NOT EXISTS `document_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
CREATE TABLE IF NOT EXISTS `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `type_name`, `created_at`) VALUES
(55, 'Judgment', '2025-12-15 05:57:17'),
(56, 'Order', '2025-12-15 05:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `policestations`
--

DROP TABLE IF EXISTS `policestations`;
CREATE TABLE IF NOT EXISTS `policestations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ps_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','guest') NOT NULL DEFAULT 'user',
  `courtname` varchar(100) NOT NULL,
  `pin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `courtname`, `pin`) VALUES
(1, 'admin', 'IT@110', 'admin', 'Main Court', '$2y$10$cDS8MNkph2s0IYdvVXEWpu2fp5N9tET3JZ8aNW7CT924i/Qb7rd06'),
(32, 'malir', '123456', 'user', 'ALL', '$2y$10$biDDZydK86G/woXR686/duxUYLeqHQqgOfHwKlK.4sZio9RaZUQd6');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
