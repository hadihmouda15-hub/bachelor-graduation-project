-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 10:13 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homecare`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `AddressID` int(11) NOT NULL,
  `Country` varchar(100) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Street` varchar(100) DEFAULT NULL,
  `Building` varchar(100) DEFAULT NULL,
  `Latitude` decimal(10,8) DEFAULT NULL,
  `Longitude` decimal(11,8) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`AddressID`, `Country`, `City`, `Street`, `Building`, `Latitude`, `Longitude`, `Notes`) VALUES
(45, 'Lebanon', 'Aakkar El Attiqa', '...', '2', '34.53184650', '36.23925600', 'Abbas Station'),
(46, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', 'Abbas Station'),
(47, 'Lebanon', 'tripoli', 'third', '2', '34.43670000', '35.84970000', 'Tripoli'),
(48, 'Lebanon', 'Beirut', 'beirut...', '3', '33.89380000', '35.50180000', ''),
(49, 'Lebanon', 'saida', '...', '2', '33.56080000', '35.37580000', ''),
(50, 'Lebanon', 'baalbek', '..', '5', '34.00590000', '36.21810000', 'In quas akkar al atika'),
(51, 'Lebanon', 'Zahle', '..', '5', '33.84600000', '35.90200000', 'Abbas Station'),
(52, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', '...........'),
(53, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', '...........'),
(54, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', 'In quas akkar al atika'),
(55, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', 'In quas akkar al atika'),
(56, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', 'In quas akkar al atika'),
(57, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', ''),
(58, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', 'In quas akkar al atika'),
(59, 'Lebanon', 'Aakkar El Attiqa', 'strett', '2', '34.53184650', '36.23925600', ''),
(60, 'Lebanon', 'kroum arab', 'In quas akkar al atika', '2', '34.56685536', '36.08656084', '...'),
(61, NULL, 'saida', '..', '2', NULL, NULL, NULL),
(62, 'Lebanon', 'Akkar', 'Hmouda', NULL, NULL, NULL, NULL),
(63, 'Lebanon', 'Koueikhat', 'Halba', '2', '34.56683229', '36.08657443', '...'),
(64, 'Lebanon', 'Koueikhat', 'Halba', '2', '34.56683229', '36.08657443', '...'),
(65, 'Lebanon', 'Koueikhat', 'Halba', '2', '34.56683229', '36.08657443', '...'),
(66, 'Lebanon', 'Koueikhat', 'Halba', '2', '34.56683229', '36.08657443', '...'),
(67, 'Lebanon', 'Koueikhat', 'Halba', '2', '34.56678400', '36.08658200', '');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `care_needed`
--

CREATE TABLE `care_needed` (
  `CareID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `care_needed`
--

INSERT INTO `care_needed` (`CareID`, `Name`) VALUES
(1, 'Waste Management'),
(2, 'Mobility Assistance'),
(3, 'IV Administration'),
(4, 'Wound Dressing'),
(5, 'Vital Signs Monitoring'),
(6, 'Post-Surgery Care'),
(7, 'Palliative Care');

-- --------------------------------------------------------

--
-- Table structure for table `certification`
--

CREATE TABLE `certification` (
  `CertificationID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Comment` text DEFAULT NULL,
  `Status` varchar(20) NOT NULL,
  `NurseID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `certification`
--

INSERT INTO `certification` (`CertificationID`, `Name`, `Image`, `Comment`, `Status`, `NurseID`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Nursing', 'uploads/certifications/Screenshot (11).png', 'nothing..', 'approved', 29, '2025-05-28 18:16:59', '2025-05-28 19:14:01'),
(2, 'nursing', 'uploads/certifications/cert.jpg', '..', 'approved', 36, '2025-05-29 05:05:57', '2025-05-29 05:06:29'),
(3, 'First Aid Certificate', 'uploads/certifications/cert.jpg', '2020', 'approved', 29, '2025-05-29 05:21:10', '2025-05-29 05:55:43'),
(4, 'Basic Life Support ( BLS )', 'uploads/certifications/cert.jpg', 'Essential for all healthcare providers : coders CPR and emergency response skills.', 'approved', 29, '2025-05-29 05:23:47', '2025-05-29 05:55:39'),
(5, 'Certified Critical Care Registered Nurse', 'uploads/certifications/cert.jpg', 'Certifies advanced knowledge in caring for critically ill adult, pediatric, or neonatal patients.\r\n\r\n', 'approved', 30, '2025-05-29 05:28:34', '2025-05-29 05:56:07'),
(6, 'Certified Pediatric Nurse (CPN)', 'uploads/certifications/OIP (1).jpg', 'Verifies professional skill in providing care to children across various settings.\r\n\r\n', 'approved', 30, '2025-05-29 05:31:21', '2025-05-29 05:56:05'),
(7, 'Maternal Newborn Nursing (RNC-MNN)', 'uploads/certifications/R.jpg', 'Focused on postnatal care of mothers and newborns in hospital settings.\r\n\r\n', 'approved', 31, '2025-05-29 05:33:07', '2025-05-29 05:55:56'),
(8, 'Advanced Cardiovascular Life Support (ACLS)', 'uploads/certifications/OIP (1).jpg', 'Focuses on managing cardiac emergencies such as heart attacks and arrhythmias.\r\n\r\n', 'approved', 31, '2025-05-29 05:34:14', '2025-05-29 05:56:15'),
(9, 'Wound, Ostomy, and Continence Nurse (WOCN)', 'uploads/certifications/OIP (1).jpg', 'Specializes in wound care, stoma care, and managing incontinence.\r\n\r\n', 'approved', 33, '2025-05-29 05:35:31', '2025-05-29 05:55:52'),
(10, 'Gerontological Nursing Certification (GERO-BC)', 'uploads/certifications/OIP (2).jpg', 'Indicates expertise in caring for elderly and aging populations.\r\n\r\n', 'approved', 33, '2025-05-29 05:35:50', '2025-05-29 08:49:36'),
(11, 'Neonatal Resuscitation Program (NRP)', 'uploads/certifications/cert.jpg', 'Specialized training for newborn resuscitation in delivery and neonatal units.\r\n\r\n', 'approved', 34, '2025-05-29 05:38:01', '2025-05-29 05:55:46'),
(12, 'Advanced Cardiovascular Life Support (ACLS)', 'uploads/certifications/OIP (1).jpg', '🗨️ Focuses on managing cardiac emergencies such as heart attacks and arrhythmias.\r\n\r\n', 'pending', 35, '2025-05-29 05:39:41', '2025-05-29 05:39:41'),
(13, 'Basic Life Support (BLS)', 'uploads/certifications/OIP (2).jpg', 'Essential for all healthcare providers; covers CPR and emergency response skills.\r\n\r\n', 'approved', 35, '2025-05-29 05:40:00', '2025-05-29 05:55:36');

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `ChatID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  `RecipientID` int(11) NOT NULL,
  `Message` text NOT NULL,
  `Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(20) DEFAULT 'Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

CREATE TABLE `contract` (
  `ContractID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Terms` text DEFAULT NULL,
  `StaffID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  `SenderType` varchar(50) NOT NULL,
  `RecipientID` int(11) NOT NULL,
  `RecipientType` varchar(50) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text DEFAULT NULL,
  `Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Type` varchar(50) NOT NULL,
  `Status` varchar(20) DEFAULT 'Unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`NotificationID`, `SenderID`, `SenderType`, `RecipientID`, `RecipientType`, `Title`, `Message`, `Date`, `Type`, `Status`) VALUES
(1, 21, 'staff', 29, 'nurse', 'Certification approved', 'Your certification of name  \"Nursing\" has approved', '2025-05-28 19:14:01', 'approved', 'Unread'),
(2, 21, 'staff', 36, 'nurse', 'Certification approved', 'Your certification of name  \"nursing\" has approved', '2025-05-29 05:06:29', 'approved', 'Unread'),
(3, 21, 'staff', 35, 'nurse', 'Certification approved', 'Your certification of name  \"Basic Life Support (BLS)\" has approved', '2025-05-29 05:55:36', 'approved', 'Unread'),
(4, 21, 'staff', 29, 'nurse', 'Certification approved', 'Your certification of name  \"Basic Life Support ( BLS )\" has approved', '2025-05-29 05:55:39', 'approved', 'Unread'),
(5, 21, 'staff', 29, 'nurse', 'Certification approved', 'Your certification of name  \"First Aid Certificate\" has approved', '2025-05-29 05:55:43', 'approved', 'Unread'),
(6, 21, 'staff', 34, 'nurse', 'Certification approved', 'Your certification of name  \"Neonatal Resuscitation Program (NRP)\" has approved', '2025-05-29 05:55:46', 'approved', 'Unread'),
(7, 21, 'staff', 33, 'nurse', 'Certification approved', 'Your certification of name  \"Wound, Ostomy, and Continence Nurse (WOCN)\" has approved', '2025-05-29 05:55:52', 'approved', 'Unread'),
(8, 21, 'staff', 31, 'nurse', 'Certification approved', 'Your certification of name  \"Maternal Newborn Nursing (RNC-MNN)\" has approved', '2025-05-29 05:55:56', 'approved', 'Unread'),
(9, 21, 'staff', 30, 'nurse', 'Certification approved', 'Your certification of name  \"Certified Pediatric Nurse (CPN)\" has approved', '2025-05-29 05:56:05', 'approved', 'Unread'),
(10, 21, 'staff', 30, 'nurse', 'Certification approved', 'Your certification of name  \"Certified Critical Care Registered Nurse\" has approved', '2025-05-29 05:56:07', 'approved', 'Unread'),
(11, 21, 'staff', 31, 'nurse', 'Certification approved', 'Your certification of name  \"Advanced Cardiovascular Life Support (ACLS)\" has approved', '2025-05-29 05:56:15', 'approved', 'Unread'),
(12, 1, 'admin', 43, 'specific', 'hello karim', 'kifak', '2025-05-29 07:51:50', 'message', 'unread'),
(13, 21, 'staff', 33, 'nurse', 'Certification approved', 'Your certification of name  \"Gerontological Nursing Certification (GERO-BC)\" has approved', '2025-05-29 08:49:36', 'approved', 'Unread'),
(14, 21, 'staff', 43, 'patient', 'Report Update', 'Regarding report #1:\n\n[Enter your message here]', '2025-05-29 08:50:28', 'report_update', 'Unread'),
(15, 21, 'staff', 42, 'nurse', 'Report Update', 'Regarding report #1:\n\n[Enter your message here]', '2025-05-29 08:50:28', 'report_update', 'Unread'),
(16, 21, 'Staff', 20, 'Admin', 'hello', 'ajsjahs', '2025-05-29 09:17:06', 'info', 'Unread');

-- --------------------------------------------------------

--
-- Table structure for table `nurse`
--

CREATE TABLE `nurse` (
  `NurseID` int(11) NOT NULL,
  `Bio` text DEFAULT NULL,
  `Availability` tinyint(1) DEFAULT 1,
  `NAID` int(11) DEFAULT NULL,
  `UserID` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `nurse`
--

INSERT INTO `nurse` (`NurseID`, `Bio`, `Availability`, `NAID`, `UserID`, `image_path`) VALUES
(29, 'Professional nurse specializing in Nursing', 1, 18, 42, NULL),
(30, 'Professional nurse specializing in Nursing', 1, 25, 48, NULL),
(31, 'Professional nurse specializing in Nursing', 1, 24, 49, NULL),
(32, 'Professional nurse specializing in Nursing', 1, 23, 50, NULL),
(33, 'Professional nurse specializing in Nursing', 1, 22, 51, NULL),
(34, 'Professional nurse specializing in Nursing', 1, 21, 52, 'uploads/profile_photos/nurse_34_1748497008.jpg'),
(35, 'Professional nurse specializing in Nursing', 1, 19, 53, NULL),
(36, 'Community health nurse with experience in home visits and chronic care.', 1, 26, 54, 'uploads/profile_photos/nurse_36_1748497315.jpg'),
(37, 'Professional nurse specializing in Nursing', 1, 30, 57, NULL),
(40, 'Professional nurse specializing in Nursing', 1, 31, 60, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nurseapplication`
--

CREATE TABLE `nurseapplication` (
  `NAID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Picture` varchar(255) DEFAULT NULL,
  `URL_CV` varchar(255) DEFAULT NULL,
  `Language` varchar(50) DEFAULT NULL,
  `Gender` varchar(20) NOT NULL,
  `SyndicateNumber` varchar(50) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `Specialization` varchar(100) DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `RejectedReason` text DEFAULT NULL,
  `RejectedBy` int(11) DEFAULT NULL,
  `RejectionDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `nurseapplication`
--

INSERT INTO `nurseapplication` (`NAID`, `FullName`, `DateOfBirth`, `PhoneNumber`, `Email`, `Picture`, `URL_CV`, `Language`, `Gender`, `SyndicateNumber`, `Comments`, `Specialization`, `Status`, `RejectedReason`, `RejectedBy`, `RejectionDate`) VALUES
(18, 'Dayan Chebli', '0000-00-00', '71 82 92 81', 'dayan@gmail.com', 'uploads/images/nurse_683749accf9b33.73056584.jpg', 'uploads/cvs/cv_683749accfeb90.74419724.pdf', 'English', 'Female', '1231232', 'comments..', 'Nursing', 'approved', NULL, NULL, NULL),
(19, 'Elie Najm', '2000-02-23', '71 82 92 81', 'elie@gmail.com', 'uploads/images/nurse_68377020ea3706.42928079.jpg', 'uploads/cvs/cv_68377020ea77e8.92255264.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(20, 'Elie Najm', '2000-02-23', '71 82 92 81', 'elie@gmail.com', 'uploads/images/nurse_6837705a275b04.63859924.jpg', 'uploads/cvs/cv_6837705a27aa86.55714608.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'rejected', 'Because you send the application twice I will reject one and accept the other', NULL, NULL),
(21, 'Ziad Barakat', '2000-02-22', '71825397', 'ziad@gmail.com', 'uploads/images/nurse_683770a75cac10.25657575.jpg', 'uploads/cvs/cv_683770a75d0104.03029274.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(22, 'Bassel Merhej', '2000-03-31', '71825397', 'bassel@gmail.com', 'uploads/images/nurse_6837712aa2d410.08242424.jpg', 'uploads/cvs/cv_6837712aa33f64.07114265.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(23, 'Nabil Azar', '2000-02-21', '71825397', 'nabil@gmail.com', 'uploads/images/nurse_6837718a380077.83292713.jpg', 'uploads/cvs/cv_6837718a387325.29352489.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(24, 'Maher Tannous', '2000-02-22', '71825397', 'maher@gmail.com', 'uploads/images/nurse_6837724194fab4.99501129.jpg', 'uploads/cvs/cv_68377241955720.95208755.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(25, 'Sami Ghosn', '2000-02-21', '71825397', 'sami@gmail.com', 'uploads/images/nurse_6837728ad54f21.48635852.jpg', 'uploads/cvs/cv_6837728ad59552.72297687.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(26, 'Khoder Daher', '2000-03-31', '71825397', 'khoder@gmail.com', 'uploads/images/nurse_683774549f3221.68381624.jpg', 'uploads/cvs/cv_683774549f7746.00526863.pdf', 'English', 'Male', '1231232', '', 'Nursing', 'approved', NULL, NULL, NULL),
(27, 'Razi Shaaban', '2003-01-31', '71825397', 'razi@gmail.com', 'uploads/images/nurse_68380c36aa48d9.26297932.jpg', 'uploads/cvs/cv_68380c36aaa109.78619503.pdf', 'English , Arabic', 'Male', '234223', '...', 'Nursing', 'pending', NULL, NULL, NULL),
(28, 'Razi Shaaban', '2003-01-31', '71825397', 'razi@gmail.com', 'uploads/images/nurse_68381dff4f7539.76946040.jpg', 'uploads/cvs/cv_68381dff4fa9d6.73117074.pdf', 'English , Arabic', 'Male', '234223', '...', 'Nursing', 'pending', NULL, NULL, NULL),
(29, 'Razi Shaaban', '2003-01-31', '71825397', 'razi@gmail.com', 'uploads/images/nurse_68381e575aac42.11452098.jpg', 'uploads/cvs/cv_68381e575ae449.72285175.pdf', 'English , Arabic', 'Male', '234223', '...', 'Nursing', 'pending', NULL, NULL, NULL),
(30, 'Razi Shaaban', '2003-01-31', '71825397', 'ilham.mourad@', 'uploads/images/nurse_68381e6a749024.83355667.jpg', 'uploads/cvs/cv_68381e6a74ccf8.93296285.pdf', 'English , Arabic', 'Male', '234223', '...', 'Nursing', 'approved', NULL, NULL, NULL),
(31, 'ilham', '2000-03-31', '71825397', 'ilham.mourad@liu.edu.lb', 'uploads/images/nurse_68382726b73692.97527616.jpg', 'uploads/cvs/cv_68382726b7b625.20156384.pdf', 'English , Arabic', 'Female', '234223', '', 'Nursing', 'approved', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nurseservices`
--

CREATE TABLE `nurseservices` (
  `NurseID` int(11) NOT NULL,
  `ServiceID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `nurseservices`
--

INSERT INTO `nurseservices` (`NurseID`, `ServiceID`, `Price`, `CreatedAt`, `UpdatedAt`) VALUES
(29, 1, '50.00', '2025-05-28 18:16:37', '2025-05-28 18:16:37'),
(29, 2, '20.00', '2025-05-29 05:20:18', '2025-05-29 05:20:18'),
(29, 8, '40.00', '2025-05-29 05:20:27', '2025-05-29 05:20:27'),
(30, 1, '50.00', '2025-05-29 05:26:33', '2025-05-29 05:26:33'),
(30, 2, '20.00', '2025-05-29 05:26:25', '2025-05-29 05:26:25'),
(30, 3, '60.00', '2025-05-29 05:27:00', '2025-05-29 05:27:00'),
(30, 4, '40.00', '2025-05-29 05:27:17', '2025-05-29 05:27:17'),
(30, 8, '10.00', '2025-05-29 05:27:08', '2025-05-29 05:27:08'),
(31, 4, '10.00', '2025-05-29 05:32:30', '2025-05-29 05:32:30'),
(31, 7, '30.00', '2025-05-29 05:32:37', '2025-05-29 05:32:37'),
(31, 8, '10.00', '2025-05-29 05:32:45', '2025-05-29 05:32:45'),
(33, 2, '50.00', '2025-05-29 05:35:08', '2025-05-29 05:35:08'),
(34, 4, '40.00', '2025-05-29 05:37:19', '2025-05-29 05:37:19'),
(35, 1, '30.00', '2025-05-29 05:39:12', '2025-05-29 05:39:12'),
(35, 2, '100.00', '2025-05-29 05:39:05', '2025-05-29 05:39:05'),
(35, 4, '40.00', '2025-05-29 05:39:18', '2025-05-29 05:39:18'),
(35, 8, '20.00', '2025-05-29 05:39:26', '2025-05-29 05:39:26'),
(36, 3, '54.00', '2025-05-29 05:42:08', '2025-05-29 05:42:08'),
(36, 4, '30.00', '2025-05-29 05:06:53', '2025-05-29 05:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PatientID` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PatientID`, `image_path`, `UserID`) VALUES
(9, NULL, 41),
(10, NULL, 43),
(11, NULL, 44),
(12, NULL, 45),
(13, NULL, 46),
(14, NULL, 47),
(15, NULL, 55);

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `RID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL,
  `Description` text DEFAULT NULL,
  `PatientID` int(11) NOT NULL,
  `NurseID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `rating`
--

INSERT INTO `rating` (`RID`, `RequestID`, `Rating`, `Description`, `PatientID`, `NurseID`) VALUES
(1, 104, 4, 'The best care ever. Thank you, Dayan!', 9, 29),
(2, 110, 3, 'good service', 10, 33);

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `ReportID` int(11) NOT NULL,
  `ReporterID` int(11) NOT NULL,
  `ReporterRole` varchar(50) NOT NULL,
  `ReportedID` int(11) NOT NULL,
  `ReportedRole` varchar(50) NOT NULL,
  `RequestID` int(11) DEFAULT NULL,
  `File` varchar(255) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Status` varchar(20) NOT NULL,
  `Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`ReportID`, `ReporterID`, `ReporterRole`, `ReportedID`, `ReportedRole`, `RequestID`, `File`, `Type`, `Description`, `Status`, `Date`) VALUES
(1, 43, 'patient', 42, 'nurse', 113, 'reports/report_6837fe5e24b901.70006352.pdf', 'Billing Issue', 'He is a bad nurse', 'pending', '2025-05-29'),
(2, 43, 'patient', 51, 'nurse', 125, NULL, 'Service Quality', 'He has a bad service .', 'pending', '2025-05-29'),
(3, 43, 'patient', 42, 'nurse', 113, NULL, 'Other', 'nothingg...', 'pending', '2025-05-29');

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `RequestID` int(11) NOT NULL,
  `NurseGender` varchar(20) NOT NULL,
  `AgeType` varchar(50) NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Type` int(50) DEFAULT NULL,
  `NumberOfNurses` int(11) NOT NULL,
  `SpecialInstructions` text DEFAULT NULL,
  `MedicalCondition` varchar(255) DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL,
  `NurseStatus` varchar(20) DEFAULT NULL,
  `PatientStatus` varchar(20) DEFAULT NULL,
  `RequestStatus` varchar(20) NOT NULL,
  `ServiceFeePercentage` decimal(5,2) DEFAULT NULL,
  `PatientID` int(11) NOT NULL,
  `NurseID` int(11) DEFAULT NULL,
  `AddressID` int(11) DEFAULT NULL,
  `ispublic` tinyint(1) NOT NULL DEFAULT 1,
  `declinereason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`RequestID`, `NurseGender`, `AgeType`, `Date`, `Time`, `Type`, `NumberOfNurses`, `SpecialInstructions`, `MedicalCondition`, `Duration`, `NurseStatus`, `PatientStatus`, `RequestStatus`, `ServiceFeePercentage`, `PatientID`, `NurseID`, `AddressID`, `ispublic`, `declinereason`) VALUES
(104, 'Male', 'Adult', '2025-06-06', '10:29:00', 1, 1, 'nothing', 'Diabetes', 1, 'pending', 'completed', 'completed', '10.00', 9, NULL, 45, 1, NULL),
(105, 'No Preference', 'No Preference', '2025-06-13', '00:02:00', 1, 1, 'Waste Management, IV Administration', NULL, 2, 'pending', 'completed', 'inprocess', '10.00', 9, 29, 45, 0, NULL),
(106, 'Male', 'Mature', '2025-06-03', '14:22:00', 4, 2, 'emty', 'No medical condition ', 12, 'pending', 'completed', 'pending', '10.00', 15, NULL, 60, 1, NULL),
(107, 'Male', 'Adult', '2025-06-07', '08:12:00', 1, 1, 'Reduce salt intake ', 'Hypertension', 2, 'pending', 'completed', 'pending', '10.00', 15, NULL, 60, 1, NULL),
(108, 'Male', 'No Preference', '2025-06-06', '11:12:00', 3, 3, 'Always carry a rescue inhaler', 'Asthma', 4, 'pending', 'completed', 'pending', '10.00', 15, NULL, 60, 1, NULL),
(109, 'No Preference', 'No Preference', '2025-05-31', '06:14:00', 1, 1, 'Waste Management, Mobility Assistance, IV Administration', NULL, 2, 'pending', 'completed', 'pending', '10.00', 15, 29, 60, 0, NULL),
(110, 'Male', 'Adult', '2025-06-06', '11:16:00', 3, 1, 'Quit smoking imeediately', 'COPD', 4, 'pending', 'completed', 'completed', '10.00', 10, NULL, 47, 1, NULL),
(111, 'Male', 'Adult', '2025-06-07', '09:17:00', 7, 1, 'Get enough sleep', 'Epiledpsy', 1, 'pending', 'rejected', 'rejected', '10.00', 10, NULL, 47, 1, NULL),
(112, 'Male', 'Adult', '2025-06-04', '10:18:00', 3, 1, 'Follow rehab and physical therapy strictly', 'Stroke ( post-care )', 2, 'pending', 'completed', 'pending', '10.00', 10, NULL, 47, 1, NULL),
(113, 'No Preference', 'No Preference', '2025-06-03', '00:00:00', 1, 1, 'IV Administration, Vital Signs Monitoring', NULL, 2, 'pending', 'completed', 'pending', '10.00', 10, 29, 47, 0, NULL),
(114, 'Male', 'Mature', '2025-06-07', '08:43:00', 2, 1, 'Take anti-seizure medication at the same time daily\r\n\r\n', 'Epilepsy', 2, 'pending', 'completed', 'pending', '10.00', 11, NULL, 48, 1, NULL),
(115, 'Male', 'Adult', '2025-06-06', '11:44:00', 2, 1, 'Use pain relief medications as prescribed (NSAIDs)', 'Osteoarthritis', 2, 'pending', 'completed', 'pending', '10.00', 11, NULL, 48, 1, NULL),
(116, 'No Preference', 'Adult', '2025-06-07', '10:45:00', 7, 3, 'Create a safe environment to prevent falls\r\n\r\n', 'Dementia ', 2, 'pending', 'completed', 'pending', '10.00', 11, NULL, 48, 1, NULL),
(117, 'No Preference', 'No Preference', '2025-06-06', '11:46:00', 4, 1, 'IV Administration, Vital Signs Monitoring', NULL, 1, 'pending', 'completed', 'pending', '10.00', 11, 31, 48, 0, NULL),
(118, 'No Preference', 'Adult', '2025-06-06', '09:47:00', 3, 2, 'Take anti-seizure medication at the same time daily\r\n\r\n', 'Epilepsy', 2, 'pending', 'completed', 'pending', '10.00', 12, NULL, 49, 1, NULL),
(119, 'No Preference', 'Adult', '2025-06-05', '10:48:00', 4, 1, 'Follow prescribed rehab (physical, speech, occupational therapy)\r\n\r\n', 'Stroke ', 1, 'pending', 'completed', 'pending', '10.00', 12, NULL, 61, 1, NULL),
(120, 'No Preference', 'Adult', '2025-06-07', '10:49:00', 4, 1, 'Take anticoagulants (blood thinners) consistently\r\n\r\n', 'Deep Vein Thrombosis (DVT)\r\n', 3, 'pending', 'completed', 'pending', '10.00', 12, NULL, 49, 1, NULL),
(121, 'No Preference', 'No Preference', '2025-06-06', '10:22:00', 2, 1, 'Vital Signs Monitoring', NULL, 1, 'pending', 'completed', 'pending', '10.00', 12, 35, 49, 0, NULL),
(122, 'No Preference', 'Adult', '2025-06-20', '02:52:00', 2, 1, 'Follow a low-sodium, low-potassium, and low-protein diet\r\n\r\n', 'Chronic Kidney Disease (CKD)\r\n', 24, 'pending', 'completed', 'pending', '10.00', 14, NULL, 51, 1, NULL),
(123, 'No Preference', 'Mature', '2025-06-26', '01:57:00', 4, 1, 'Avoid smoking and air pollution\r\n\r\n', 'Chronic Obstructive Pulmonary Disease (COPD)\r\n', 12, 'pending', 'completed', 'pending', '10.00', 14, NULL, 51, 1, NULL),
(124, 'No Preference', 'No Preference', '2025-06-03', '14:22:00', 2, 1, 'Vital Signs Monitoring', NULL, 1, 'pending', 'completed', 'completed', '10.00', 14, 33, 51, 0, NULL),
(125, 'No Preference', 'No Preference', '2025-06-06', '00:32:00', 2, 1, 'Palliative Care', NULL, 2, 'pending', 'completed', 'pending', '10.00', 10, 33, 47, 0, NULL),
(126, 'No Preference', 'Adult', '2025-06-06', '11:11:00', 3, 2, '.....', 'Presentation....', 1, 'pending', 'completed', 'pending', '10.00', 10, NULL, 47, 1, NULL),
(127, 'Male', 'Adult', '2025-06-06', '14:22:00', 1, 2, 'oooooooooooooooooooooooooooooooooooooo', 'oooooooooooooooooooooooooooooooooooooooo', 12, 'pending', 'completed', 'inprocess', '10.00', 10, NULL, 47, 1, NULL),
(128, 'No Preference', 'No Preference', '2025-06-04', '08:08:00', 4, 1, 'Vital Signs Monitoring', NULL, 1, 'pending', 'completed', 'inprocess', '10.00', 10, 34, 47, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request_applications`
--

CREATE TABLE `request_applications` (
  `ApplicationID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `NurseID` int(11) NOT NULL,
  `ApplicationStatus` enum('pending','confirmed','accepted','rejected','inprocess','selected') DEFAULT 'pending',
  `ApplicationDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `request_applications`
--

INSERT INTO `request_applications` (`ApplicationID`, `RequestID`, `NurseID`, `ApplicationStatus`, `ApplicationDate`) VALUES
(1, 104, 29, 'accepted', '2025-05-28 22:22:51'),
(2, 110, 33, 'accepted', '2025-05-29 08:59:58'),
(3, 127, 33, 'accepted', '2025-05-29 12:01:37'),
(4, 127, 34, 'accepted', '2025-05-29 12:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `request_care_needed`
--

CREATE TABLE `request_care_needed` (
  `RequestID` int(11) NOT NULL,
  `CareID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `request_care_needed`
--

INSERT INTO `request_care_needed` (`RequestID`, `CareID`) VALUES
(104, 1),
(104, 2),
(104, 3),
(105, 1),
(105, 3),
(106, 2),
(106, 3),
(106, 4),
(107, 5),
(107, 6),
(108, 4),
(109, 1),
(109, 2),
(109, 3),
(110, 2),
(110, 3),
(110, 4),
(111, 2),
(111, 4),
(111, 7),
(112, 3),
(113, 3),
(113, 5),
(114, 1),
(115, 1),
(115, 2),
(115, 5),
(116, 1),
(116, 2),
(117, 3),
(117, 5),
(118, 2),
(118, 4),
(119, 5),
(119, 6),
(119, 7),
(120, 6),
(120, 7),
(121, 5),
(122, 4),
(122, 6),
(123, 3),
(124, 5),
(125, 7),
(126, 3),
(126, 4),
(126, 5),
(127, 3),
(127, 5),
(128, 5);

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `ScheduleID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `Notes` text DEFAULT NULL,
  `Status` varchar(20) NOT NULL,
  `NurseID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`ScheduleID`, `Date`, `StartTime`, `EndTime`, `Notes`, `Status`, `NurseID`) VALUES
(1, '2025-06-13', '00:02:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 29),
(2, '2025-05-31', '06:14:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 29),
(3, '2025-06-06', '00:00:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 29),
(4, '2025-06-06', '11:46:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 31),
(5, '2025-06-06', '10:22:00', '12:38:00', 'Auto-generated from weekly availability', 'booked', 35),
(6, '2025-06-06', '11:22:00', '12:38:00', 'Remaining slot', 'available', 35),
(7, '2025-06-03', '14:22:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 33),
(8, '2025-06-06', '00:32:00', '00:00:00', 'Auto-generated from weekly availability', 'booked', 33),
(9, '2025-06-04', '08:08:00', '14:07:00', 'Auto-generated from weekly availability', 'booked', 34),
(10, '2025-06-04', '09:08:00', '14:07:00', 'Remaining slot', 'available', 34);

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `ServiceID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`ServiceID`, `Name`, `Type`, `Duration`, `Description`) VALUES
(1, 'Wound Care', 'Medical', 60, 'Cleaning and dressing of wounds, including post-surgical care.'),
(2, 'Elderly Care', 'Non-Medical', 120, 'Assistance with daily activities for elderly patients.'),
(3, 'Pediatric Nursing', 'Medical', 90, 'Specialized care for infants and children.'),
(4, 'Physical Therapy', 'Rehabilitation', 60, 'Exercises and therapy to improve mobility.'),
(7, 'Critical Care', 'Medical', 120, 'Intensive care for critical conditions.'),
(8, 'Palliative Care', 'Medical', 90, 'Supportive care for terminal illnesses.');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `SiteName` varchar(255) NOT NULL,
  `ContactEmail` varchar(255) NOT NULL,
  `ContactPhone` varchar(50) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `MaintenanceMode` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`SiteName`, `ContactEmail`, `ContactPhone`, `Location`, `MaintenanceMode`) VALUES
('Home Care Platform', 'customer@Care.com', '+961 123456', '12,Akkar', 0);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `StaffID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `UserID`) VALUES
(2, 56);

-- --------------------------------------------------------

--
-- Table structure for table `subscribe`
--

CREATE TABLE `subscribe` (
  `SID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `PaymentDate` date NOT NULL,
  `PaymentMethod` varchar(50) NOT NULL,
  `expiryDate` date NOT NULL,
  `PlanType` varchar(50) DEFAULT NULL,
  `Status` varchar(20) NOT NULL,
  `NurseID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `FullName` varchar(150) NOT NULL,
  `Gender` varchar(20) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(60) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `Status` varchar(20) NOT NULL,
  `AddressID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `FullName`, `Gender`, `DateOfBirth`, `PhoneNumber`, `Email`, `Password`, `Role`, `Status`, `AddressID`) VALUES
(1, 'AliHadi', 'male', '0000-00-00', '', 'admin@gmail.com', '$2y$10$/3QnlIUYh6u5SpEV9WYrA.aH4TUy5/YYujiKcN8PIuLa7uukFcceK', 'admin', 'active', 0),
(2, 'Ali Chebli', 'male', '0000-00-00', '', 'staff@gmail.com', '$2y$10$VumyCa8tC4h/53ntBQTd1uqwLWQ5ZUReNTKEriMNYSLbzV3aIdAuu', 'staff', 'active', 0),
(41, 'Rachid Chebli', 'Male', '1999-01-06', '71 00 56 42', 'rachid@gmail.com', '$2y$10$F3IKh9VwyO7HF5GYDRWBIe0etOoLRp2n0JtTyLg5FYagkLa20k8D.', 'patient', 'active', 45),
(42, 'Dayan Chebli', 'Female', '0000-00-00', '71 82 92 81', 'dayan@gmail.com', '$2y$10$.jhippT358jC6Ie4HNrPO.0RZHHU5OypG4vnuHbMR3qaU6K7VPBjK', 'nurse', 'active', NULL),
(43, 'Karim Haddad', 'Male', '1999-03-02', '71825397', 'karim@gmail.com', '$2y$10$ZB4AoCj2EZ/KVLuIx6q4MONg1tGy2FuBt9zL9URNuvVVXp7f.WfPu', 'patient', 'active', 47),
(44, 'Jad Khoury', 'Male', '2000-03-02', '71005642', 'jad@gmail.com', '$2y$10$qZRfru9mFXTdwMqE1W0p0eGkdd7UcfsgzoROxFVGp31/hotJ3xm2S', 'patient', 'active', 48),
(45, 'Rami Hajj', 'Male', '2005-06-07', '71 00 56 42', 'rami@gmail.com', '$2y$10$iUgxAPUpatMwCzm0qqpuQuANjTASoGSF/IFIN.azLBj.3mrAt.QGy', 'patient', 'active', 49),
(46, 'Maya Fares', 'Female', '2001-04-02', '71825397', 'maya@gmail.com', '$2y$10$i7aG2gFnunuS3ZG2oee.q.bkuwrZp5MeXsZYWQfV0uNdzt9HYeDZ6', 'patient', 'active', 50),
(47, 'Layan Saade', 'Female', '2003-02-03', '71829281', 'layan@gmail.com', '$2y$10$dyTn3Dpc.OL1mY4kwywmMehymU2phnYp69ayl9U75EHJ7Y5lHntnq', 'patient', 'active', 51),
(48, 'Sami Ghosn', 'Male', '2000-02-21', '71825397', 'sami@gmail.com', '$2y$10$oWR6XwU3fqZCt.nTuU75beLIH5vdybzdeJ91sa/Y9uuZyjDWlgJbG', 'nurse', 'active', NULL),
(49, 'Maher Tannous', 'Male', '2000-02-22', '71825397', 'maher@gmail.com', '$2y$10$qmXKOpVLh66WSNMGXNL5DePkk8fsPacfT7JE75oUs4VLhheHrLChK', 'nurse', 'active', NULL),
(50, 'Nabil Azar', 'Male', '2000-02-21', '71825397', 'nabil@gmail.com', '$2y$10$mt36YrFMmT/Vv4o99kd0F.Q4EOt0Dq3E/OHzpvKM.aJ2dAwm87m06', 'nurse', 'active', NULL),
(51, 'Bassel Merhej', 'Male', '2000-03-31', '71825397', 'bassel@gmail.com', '$2y$10$zf25LgM1aT9P9CRcxsNZ1Oz75Qh1TKlEB.y/2Qb5TKdx1VVPGhOwa', 'nurse', 'active', NULL),
(52, 'Ziad Barakat', 'Male', '2000-02-22', '71825397', 'ziad@gmail.com', '$2y$10$DgoJu/ufvCDqiV6x0yXI3OQGPwaWHsuEuIOTyDt.7B8DVxKHQXW/y', 'nurse', 'blocked', NULL),
(53, 'Elie Najm', 'Male', '2000-02-23', '71 82 92 81', 'elie@gmail.com', '$2y$10$ElNL0bbAlHO3pe2NJT/1hORqfPoMLJ/P4GI2ZDEMno0aaVeK9Swwm', 'nurse', 'active', NULL),
(54, 'Khoder Daher', 'Male', '2000-03-31', '71825397', 'khoder@gmail.com', '$2y$10$48P7SMwE1Afrq9DatwJ6FuPTWUp8BnQzR..x/o93xPSsWYAef3sja', 'nurse', 'active', NULL),
(55, 'Sajed Sleiman', 'Male', '2000-02-04', '71825397', 'sajed@gmail.com', '$2y$10$wwB6jMPgTc9CT0eaC8tbRepMNOMcZiaTAgx/Sy5H.xX25GprCUoKm', 'patient', 'active', 60),
(56, 'Hadi', 'male', '2000-10-18', '7688', 'staff1@gmail.com', 'staff123', 'staff', 'active', 62),
(57, 'Razi Shaaban', 'Male', '2003-01-31', '71825397', '', '$2y$10$rEkSOXEXchOxI7qMHL7BrOsEb2i55MgNaDBTls1/FXTHLYIMLYY8K', 'nurse', 'active', NULL),
(60, 'ilham', 'Female', '2000-03-31', '71825397', 'ilham.mourad@liu.edu.lb', '$2y$10$0XnTiEqrq5nm5xZ6Vg5wjufe9tfiNkw/rvjn7WZd41ILoaxsIniuC', 'nurse', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `weekly_availability`
--

CREATE TABLE `weekly_availability` (
  `AvailabilityID` int(11) NOT NULL,
  `NurseID` int(11) NOT NULL,
  `Sunday` tinyint(1) DEFAULT 0,
  `Monday` tinyint(1) DEFAULT 0,
  `Tuesday` tinyint(1) DEFAULT 0,
  `Wednesday` tinyint(1) DEFAULT 0,
  `Thursday` tinyint(1) DEFAULT 0,
  `Friday` tinyint(1) DEFAULT 0,
  `Saturday` tinyint(1) DEFAULT 0,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `weekly_availability`
--

INSERT INTO `weekly_availability` (`AvailabilityID`, `NurseID`, `Sunday`, `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `StartTime`, `EndTime`) VALUES
(1, 29, 1, 1, 1, 1, 1, 1, 1, '00:00:00', '00:00:00'),
(2, 30, 0, 0, 1, 0, 0, 0, 0, '08:00:00', '12:00:00'),
(3, 31, 0, 1, 1, 0, 0, 1, 1, '00:00:00', '00:00:00'),
(4, 33, 1, 1, 1, 1, 1, 1, 1, '00:00:00', '00:00:00'),
(5, 34, 0, 1, 1, 1, 0, 0, 0, '08:08:00', '14:07:00'),
(6, 35, 0, 0, 0, 1, 0, 1, 1, '09:38:00', '12:38:00'),
(7, 36, 0, 0, 1, 1, 1, 1, 1, '00:00:00', '00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`AddressID`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `certification`
--
ALTER TABLE `certification`
  ADD PRIMARY KEY (`CertificationID`),
  ADD KEY `NurseID` (`NurseID`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`ChatID`),
  ADD KEY `SenderID` (`SenderID`),
  ADD KEY `RecipientID` (`RecipientID`);

--
-- Indexes for table `contract`
--
ALTER TABLE `contract`
  ADD PRIMARY KEY (`ContractID`),
  ADD KEY `StaffID` (`StaffID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`NotificationID`);

--
-- Indexes for table `nurse`
--
ALTER TABLE `nurse`
  ADD PRIMARY KEY (`NurseID`),
  ADD KEY `NAID` (`NAID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `nurseapplication`
--
ALTER TABLE `nurseapplication`
  ADD PRIMARY KEY (`NAID`),
  ADD KEY `RejectedBy` (`RejectedBy`);

--
-- Indexes for table `nurseservices`
--
ALTER TABLE `nurseservices`
  ADD PRIMARY KEY (`NurseID`,`ServiceID`),
  ADD KEY `ServiceID` (`ServiceID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PatientID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`RID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `NurseID` (`NurseID`),
  ADD KEY `rating_ibfk_3` (`RequestID`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `RequestID` (`RequestID`);

--
-- Indexes for table `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `NurseID` (`NurseID`),
  ADD KEY `AddressID` (`AddressID`),
  ADD KEY `type` (`Type`);

--
-- Indexes for table `request_applications`
--
ALTER TABLE `request_applications`
  ADD PRIMARY KEY (`ApplicationID`),
  ADD KEY `RequestID` (`RequestID`),
  ADD KEY `NurseID` (`NurseID`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `NurseID` (`NurseID`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`ServiceID`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`SiteName`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`StaffID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `subscribe`
--
ALTER TABLE `subscribe`
  ADD PRIMARY KEY (`SID`),
  ADD KEY `NurseID` (`NurseID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `AddressID` (`AddressID`);

--
-- Indexes for table `weekly_availability`
--
ALTER TABLE `weekly_availability`
  ADD PRIMARY KEY (`AvailabilityID`),
  ADD KEY `NurseID` (`NurseID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certification`
--
ALTER TABLE `certification`
  MODIFY `CertificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `ChatID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contract`
--
ALTER TABLE `contract`
  MODIFY `ContractID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `nurse`
--
ALTER TABLE `nurse`
  MODIFY `NurseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `nurseapplication`
--
ALTER TABLE `nurseapplication`
  MODIFY `NAID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `RID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `request_applications`
--
ALTER TABLE `request_applications`
  MODIFY `ApplicationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `ServiceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `StaffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscribe`
--
ALTER TABLE `subscribe`
  MODIFY `SID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `weekly_availability`
--
ALTER TABLE `weekly_availability`
  MODIFY `AvailabilityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `certification`
--
ALTER TABLE `certification`
  ADD CONSTRAINT `certification_ibfk_1` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`);

--
-- Constraints for table `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`SenderID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`RecipientID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `contract_ibfk_1` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `nurse`
--
ALTER TABLE `nurse`
  ADD CONSTRAINT `nurse_ibfk_1` FOREIGN KEY (`NAID`) REFERENCES `nurseapplication` (`NAID`),
  ADD CONSTRAINT `nurse_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `nurseapplication`
--
ALTER TABLE `nurseapplication`
  ADD CONSTRAINT `fk_rejectedby_staff` FOREIGN KEY (`RejectedBy`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `nurseservices`
--
ALTER TABLE `nurseservices`
  ADD CONSTRAINT `nurseservices_ibfk_1` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `nurseservices_ibfk_2` FOREIGN KEY (`ServiceID`) REFERENCES `service` (`ServiceID`);

--
-- Constraints for table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `request` (`RequestID`);

--
-- Constraints for table `request`
--
ALTER TABLE `request`
  ADD CONSTRAINT `fk_request_address` FOREIGN KEY (`AddressID`) REFERENCES `address` (`AddressID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_nurse` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_patient` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `type` FOREIGN KEY (`Type`) REFERENCES `service` (`ServiceID`);

--
-- Constraints for table `weekly_availability`
--
ALTER TABLE `weekly_availability`
  ADD CONSTRAINT `weekly_availability_ibfk_1` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
