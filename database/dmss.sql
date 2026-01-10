-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 20, 2025 at 06:10 AM
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
-- Database: `southrecord`
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
) ENGINE=MyISAM AUTO_INCREMENT=61 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `case_documents`
--

INSERT INTO `case_documents` (`id`, `case_id`, `type_id`, `file_name`, `file_path`, `uploaded_by`, `courtname`, `uploaded_at`, `qc_status`, `confidentiality`, `created_user`, `created_court`) VALUES
(58, 1808, 1, '0000_Judgment_v1.pdf', 'uploads/DJ-MALIR/0000/0000_Judgment_v1.pdf', 'admin', 'DJ-MALIR', '2025-11-11 04:41:48', 'Pending', 'Non-Restricted', NULL, NULL),
(59, 1808, 3, '0000_Decree_v1.pdf', 'uploads/DJ-MALIR/0000/0000_Decree_v1.pdf', 'admin', 'Main Court', '2025-11-20 05:50:11', 'Pending', 'Non-Restricted', NULL, NULL),
(60, 1808, 43, '0000_Deporsition_of_Witnesses_v1.pdf', 'uploads/DJ-MALIR/0000/0000_Deporsition_of_Witnesses_v1.pdf', 'admin', 'Main Court', '2025-11-20 05:55:02', 'Pending', 'Non-Restricted', NULL, NULL);

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
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`id`, `court_code`, `court_fullname`) VALUES
(23, 'ADJ-I', 'Additional District & Sessions Court, Malir, Karachi'),
(22, 'DJ-MALIR', 'District & Sessions Court, Malir, Karachi');

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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1809 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ctccc`
--

INSERT INTO `ctccc` (`id`, `underSection`, `courtname`, `casecateg`, `caseno`, `year`, `partyone`, `partytwo`, `crimeno`, `crimeyear`, `s_rbf`, `dateInst`, `dateSubmission`, `dateDisp`, `status`, `cost`, `remarks`, `ps`, `row`, `shelf`, `bundle`, `file`, `cfms_dc_casecode`, `last_updated`, `qc_status`, `confidentiality`, `created_by`, `created_court`, `ocr_complete`) VALUES
(1808, NULL, 'DJ-MALIR', '13-A Arms Ordinance', 1, '2025', 'The State', 'Shushhsuh', NULL, NULL, NULL, NULL, NULL, NULL, 'Disposed', NULL, 'Test Case for Pin', NULL, NULL, NULL, NULL, NULL, '0000', '2025-11-11 04:41:48', 'Approved', 'Restricted', 'admin', 'Main Court', 'Yes');

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `document_access_logs`
--

INSERT INTO `document_access_logs` (`id`, `doc_id`, `user_id`, `action`, `accessed_at`, `ip_address`, `user_agent`) VALUES
(3, 60, 1, 'view', '2025-11-20 05:58:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(4, 60, 1, 'view', '2025-11-20 06:07:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0');

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

--
-- Dumping data for table `document_metadata`
--

INSERT INTO `document_metadata` (`id`, `document_id`, `meta_key`, `meta_value`, `created_at`) VALUES
(1, 29, 'Case Code', '1111111111', '2025-10-31 11:14:46'),
(2, 29, 'Case Title', '1 vs 1', '2025-10-31 11:14:46'),
(3, 29, 'Case Type', 'Civil', '2025-10-31 11:14:46'),
(4, 29, 'Year', '1', '2025-10-31 11:14:46'),
(5, 29, 'Document Category', 'Judgment', '2025-10-31 11:14:46'),
(6, 29, 'OCR Status', 'Completed', '2025-10-31 11:14:46'),
(7, 29, 'Operator', 'admin', '2025-10-31 11:14:46'),
(8, 29, 'Confidentiality', 'Restricted', '2025-10-31 11:14:46'),
(9, 29, 'QC Status', 'Pending', '2025-10-31 11:14:46'),
(10, 30, 'Case Code', '1111111111', '2025-10-31 11:24:44'),
(11, 30, 'Case Title', '1 vs 1', '2025-10-31 11:24:44'),
(12, 30, 'Case Type', 'Civil', '2025-10-31 11:24:44'),
(13, 30, 'Year', '1', '2025-10-31 11:24:44'),
(14, 30, 'Document Category', 'Judgment', '2025-10-31 11:24:44'),
(15, 30, 'OCR Status', 'Completed', '2025-10-31 11:24:44'),
(16, 30, 'Operator', 'admin', '2025-10-31 11:24:44'),
(17, 30, 'Confidentiality', 'Restricted', '2025-10-31 11:24:44'),
(18, 30, 'QC Status', 'Pending', '2025-10-31 11:24:44');

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
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `type_name`, `created_at`) VALUES
(1, 'Judgment', '2025-10-30 08:08:14'),
(2, 'Order', '2025-10-30 08:08:50'),
(3, 'Decree', '2025-10-30 08:46:00'),
(39, 'Farming of Charge', '2025-11-07 05:56:16'),
(29, 'Evidence', '2025-11-03 11:51:31'),
(28, 'Plaint', '2025-11-03 11:51:18'),
(40, 'Plea of Accused', '2025-11-07 05:56:26'),
(38, 'Framing of issues', '2025-11-07 05:56:04'),
(41, 'Preliminary Decree', '2025-11-07 05:58:50'),
(42, 'Case Dairies', '2025-11-07 05:59:08'),
(43, 'Deporsition of Witnesses', '2025-11-07 05:59:19'),
(45, 'Statement of Accused', '2025-11-07 05:59:37');

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

--
-- Dumping data for table `policestations`
--

INSERT INTO `policestations` (`id`, `ps_name`) VALUES
(1, 'All'),
(2, 'TBK'),
(3, 'Sehwan'),
(4, 'Jamshoro'),
(5, 'Kotri'),
(6, 'Excise & ANF'),
(7, 'Coal Mines'),
(8, 'Looni Kot'),
(9, 'Railway'),
(10, 'Nooriabad'),
(11, 'Manjhand'),
(12, 'Khanoth'),
(13, 'Chachar'),
(14, 'Budhapur'),
(15, 'Rajri'),
(16, 'Thebat'),
(17, 'Mahi Otho'),
(18, 'Naing Shareef'),
(19, 'Amri'),
(20, 'Khero Dero'),
(21, 'Jhangara'),
(22, 'Bhan Saeedabad'),
(23, 'Other PS'),
(24, 'Nill'),
(25, 'NILLL');

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
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `courtname`, `pin`) VALUES
(1, 'admin', 'admin', 'admin', 'Main Court', '$2y$10$UzpJ2G0g2B6Q.ve3P/MvcOViWcBj/ehDRpaCP6HCaeV0SThn3BJDO');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
