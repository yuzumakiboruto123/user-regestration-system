-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2025 at 01:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `AdminPINHash` varchar(255) NOT NULL,
  `AdminPasswordHash` varchar(255) NOT NULL,
  `AdminLevel` enum('SuperAdmin','Admin','Manager') NOT NULL DEFAULT 'Admin',
  `Email` varchar(100) NOT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `FailedPinAttempts` int(11) DEFAULT 0,
  `PinLastChanged` datetime DEFAULT NULL,
  `PinLockUntil` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `UserID`, `Username`, `AdminPINHash`, `AdminPasswordHash`, `AdminLevel`, `Email`, `LastLogin`, `CreatedAt`, `FailedPinAttempts`, `PinLastChanged`, `PinLockUntil`) VALUES
(1, 7, 'admin', '$2y$10$wLC9gwi7AnpV5CeMoGc5beprPqE1SjDILbbII3o1RorRxXdcc.VwS', '$2y$10$xdriwuGSEu1pi0sUXuvVJesN4.jJ8qdM1LcgRIOju.BA2jkOENKFS', 'Admin', 'admin1@school.edu', '2025-11-26 11:02:04', '2025-11-26 10:27:13', 0, '2025-11-26 11:59:44', NULL),
(2, 66, 'nepal', '$2y$10$jXzluy7GPMkSGfgD0I/bruAZXKVTm9JffTlKIVU6kC20TmfA.L7.K', '$2y$10$Oz4etTRUmyhZvo7GIRMA9.kHxHoZKx34iWGQRP4EIpZHZobYk2A.6', 'Admin', 'admin2@school.edu', '2025-11-24 15:16:19', '2025-11-26 10:27:13', 0, '2025-11-26 13:21:07', NULL),
(9, 80, 'anjil', '$2y$10$RWRldj6FZCjUqfyOO.WfBOUz2zkMi46LjCwtBxaFedNvNdkQNF0VO', '$2y$10$wS2wRea0DkzrufzTfiFSoOtjmaosyOfnqU71G6ztBUYHEgy2EE8Mu', 'Admin', 'anjil@school.edu', NULL, '2025-11-27 19:43:27', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `auditlogs`
--

CREATE TABLE `auditlogs` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(50) DEFAULT NULL,
  `TableName` varchar(100) DEFAULT NULL,
  `RowID` int(11) DEFAULT NULL,
  `Details` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auditlogs`
--

INSERT INTO `auditlogs` (`LogID`, `UserID`, `Action`, `TableName`, `RowID`, `Details`, `IPAddress`, `Timestamp`) VALUES
(1, NULL, 'DB_INIT', NULL, NULL, 'Database created by Anjil during Week 2 Sprint', NULL, '2025-10-28 16:13:34'),
(2, NULL, 'INSERT', NULL, NULL, 'Inserted sample data for testing all modules', NULL, '2025-10-28 16:13:34'),
(3, 66, 'Admin PIN verified for delete user', 'user', 12, 'Admin verified PIN to delete user \'rohit\' (Role: Student, Email: rohit.sharma@school.edu)', '::1', '2025-11-26 13:43:39'),
(4, 66, 'User deleted', 'user', 12, 'Deleted user \'rohit\' with role \'Student\' and email \'rohit.sharma@school.edu\'', '::1', '2025-11-26 13:43:39'),
(5, 66, 'Login Successful', 'User', 66, 'Correct credentials', '::1', '2025-11-26 13:54:39'),
(6, 66, 'Login Successful', 'User', 66, 'Correct credentials', '::1', '2025-11-26 15:03:56'),
(7, 66, 'Admin PIN verified for delete user', 'user', 74, 'Admin verified PIN to delete user \'royal\' (Role: Staff, Email: royal20@school.edu)', '::1', '2025-11-26 15:06:03'),
(8, 66, 'User deleted', 'user', 74, 'Deleted user \'royal\' with role \'Staff\' and email \'royal20@school.edu\'', '::1', '2025-11-26 15:06:03'),
(9, NULL, 'Created User', 'User', 75, 'Username: royal Role: Staff', '::1', '2025-11-26 15:07:35'),
(10, 66, 'Admin Action: create', 'Admin', NULL, 'Action executed via PIN confirmation', '::1', '2025-11-26 15:07:35'),
(11, 29, 'Login Successful', 'User', 29, 'Correct credentials', '::1', '2025-11-26 15:10:23'),
(12, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-26 15:10:59'),
(13, 7, 'OPEN_EDIT_USER', 'user', 7, 'Admin opened edit form for UserID 7', '::1', '2025-11-26 15:11:41'),
(14, 7, 'OPEN_EDIT_USER', 'user', 7, 'Admin opened edit form for UserID 7', '::1', '2025-11-26 15:11:45'),
(15, 7, 'FAILED_UPDATE_USER', 'user', 7, 'Validation failed while editing user', '::1', '2025-11-26 15:11:45'),
(16, 7, 'OPEN_EDIT_USER', 'user', 7, 'Admin opened edit form for UserID 7', '::1', '2025-11-26 15:11:59'),
(17, 7, 'UPDATE_USER', 'user', 7, 'Admin updated core user fields', '::1', '2025-11-26 15:11:59'),
(18, 7, 'SUCCESS_UPDATE_USER', 'user', 7, 'User update completed', '::1', '2025-11-26 15:11:59'),
(19, 7, 'OPEN_EDIT_USER', 'user', 75, 'Admin opened Edit Page', '::1', '2025-11-26 15:26:10'),
(20, 7, 'OPEN_EDIT_USER', 'user', 75, 'Admin opened Edit Page', '::1', '2025-11-26 15:26:17'),
(21, 7, 'Admin Action: update', 'Admin', 75, 'Action executed via PIN confirmation', '::1', '2025-11-26 15:26:24'),
(22, 7, 'OPEN_EDIT_USER', 'user', 7, 'Admin opened Edit Page', '::1', '2025-11-26 15:26:34'),
(23, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-26 15:31:07'),
(24, 7, 'OPEN_EDIT_USER', 'user', 8, 'Admin opened Edit Page', '::1', '2025-11-26 15:31:18'),
(25, 7, 'OPEN_EDIT_USER', 'user', 8, 'Admin opened Edit Page', '::1', '2025-11-26 15:31:24'),
(26, 7, 'FAILED_UPDATE_USER', 'user', 8, 'Validation failed', '::1', '2025-11-26 15:31:24'),
(27, 7, 'OPEN_EDIT_USER', 'user', 8, 'Admin opened Edit Page', '::1', '2025-11-26 15:31:34'),
(28, 7, 'Admin Action: update', 'Admin', 8, 'Action executed via PIN confirmation', '::1', '2025-11-26 15:31:41'),
(29, 70, 'Login Successful', 'User', 70, 'Correct credentials', '::1', '2025-11-26 15:37:17'),
(30, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-26 15:40:45'),
(31, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 17:40:10'),
(32, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 17:59:49'),
(33, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 18:25:18'),
(34, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 18:35:04'),
(35, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 19:20:06'),
(36, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 19:44:20'),
(37, 7, 'Admin PIN verified for delete user', 'user', 8, 'Admin verified PIN to delete user \'anjil2\' (Role: Student, Email: anjil.uprety@student.edu)', '::1', '2025-11-27 19:44:56'),
(38, 7, 'User deleted', 'user', 8, 'Deleted user \'anjil2\' with role \'Student\' and email \'anjil.uprety@student.edu\'', '::1', '2025-11-27 19:44:56'),
(39, NULL, 'Created User', 'User', 76, 'Username: anjil Role: Admin', '::1', '2025-11-27 19:55:41'),
(40, 7, 'Admin Action: create', 'Admin', NULL, 'Action executed via PIN confirmation', '::1', '2025-11-27 19:55:41'),
(41, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 20:11:49'),
(42, 7, 'Admin PIN verified for delete user', 'user', 76, 'Admin verified PIN to delete user \'anjil\' (Role: Admin, Email: anjil@school.edu)', '::1', '2025-11-27 20:12:40'),
(43, 7, 'User deleted', 'user', 76, 'Deleted user \'anjil\' with role \'Admin\' and email \'anjil@school.edu\'', '::1', '2025-11-27 20:12:40'),
(44, NULL, 'Created User', 'User', 77, 'Username: anjil Role: Admin', '::1', '2025-11-27 20:13:31'),
(45, 7, 'Create Admin', 'Admin', 44, 'Admin added to both user + admin tables', '::1', '2025-11-27 20:13:31'),
(46, 7, 'Admin Action: create', 'Admin', NULL, 'PIN confirmed action', '::1', '2025-11-27 20:13:31'),
(47, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 20:30:48'),
(48, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 20:31:01'),
(49, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 20:31:48'),
(50, 7, 'Admin PIN verified for delete user', 'user', 77, 'Admin verified PIN to delete user \'anjil\' (Role: Admin, Email: anjil@school.edu)', '::1', '2025-11-27 20:32:11'),
(51, 7, 'User deleted', 'user', 77, 'Deleted user \'anjil\' with role \'Admin\' and email \'anjil@school.edu\'', '::1', '2025-11-27 20:32:11'),
(52, NULL, 'Created User', 'User', 78, 'Username: anjil Role: Admin', '::1', '2025-11-27 20:33:27'),
(53, 7, 'Create User', 'Admin', 78, 'User created via PIN verification', '::1', '2025-11-27 20:33:27'),
(54, 7, 'Admin PIN verified for delete user', 'user', 78, 'Admin verified PIN to delete user \'anjil\' (Role: Admin, Email: anjil@school.edu)', '::1', '2025-11-27 20:38:53'),
(55, 7, 'User deleted', 'user', 78, 'Deleted user \'anjil\' with role \'Admin\' and email \'anjil@school.edu\'', '::1', '2025-11-27 20:38:53'),
(56, NULL, 'Created User', 'User', 79, 'Username: anjil Role: Admin', '::1', '2025-11-27 20:39:42'),
(57, 7, 'Create User', 'Admin', 79, 'User created via PIN verification', '::1', '2025-11-27 20:39:42'),
(58, NULL, 'Login Successful', 'User', 79, 'Correct credentials', '::1', '2025-11-27 20:41:08'),
(59, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 20:42:13'),
(60, 7, 'Admin PIN verified for delete user', 'user', 79, 'Admin verified PIN to delete user \'anjil\' (Role: Admin, Email: anjil@school.edu)', '::1', '2025-11-27 20:42:42'),
(61, 7, 'User deleted', 'user', 79, 'Deleted user \'anjil\' with role \'Admin\' and email \'anjil@school.edu\'', '::1', '2025-11-27 20:42:42'),
(62, NULL, 'Created User', 'User', 80, 'Username: anjil Role: Admin', '::1', '2025-11-27 20:43:27'),
(63, 7, 'Admin Action: create', 'Admin', NULL, 'Executed via verify_pin_action.php', '::1', '2025-11-27 20:43:27'),
(64, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 22:33:21'),
(65, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 23:24:54'),
(66, 7, 'OPEN_EDIT_USER', 'user', 7, 'Admin opened Edit Page', '::1', '2025-11-27 23:25:02'),
(67, 7, 'OPEN_EDIT_USER', 'user', 9, 'Admin opened Edit Page', '::1', '2025-11-27 23:25:53'),
(68, 7, 'OPEN_EDIT_USER', 'user', 10, 'Admin opened Edit Page', '::1', '2025-11-27 23:26:33'),
(69, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-27 23:42:27'),
(70, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-28 14:59:12'),
(71, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 10:49:32'),
(72, 80, 'Login Successful', 'User', 80, 'Correct credentials', '::1', '2025-11-29 10:59:37'),
(73, 80, 'Login Successful', 'User', 80, 'Correct credentials', '::1', '2025-11-29 11:17:34'),
(74, 80, 'Login Successful', 'User', 80, 'Correct credentials', '::1', '2025-11-29 11:36:54'),
(75, NULL, 'Login Successful', 'User', 81, 'Correct credentials', '::1', '2025-11-29 11:49:58'),
(76, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 11:50:49'),
(77, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:08:16'),
(78, 9, 'Login Successful', 'User', 9, 'Correct credentials', '::1', '2025-11-29 12:09:45'),
(79, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:10:31'),
(80, 29, 'Login Successful', 'User', 29, 'Correct credentials', '::1', '2025-11-29 12:12:52'),
(81, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:14:31'),
(82, 14, 'Login Successful', 'User', 14, 'Correct credentials', '::1', '2025-11-29 12:15:27'),
(83, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:15:54'),
(84, 60, 'Login Successful', 'User', 60, 'Correct credentials', '::1', '2025-11-29 12:18:08'),
(85, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:19:37'),
(86, NULL, 'Login Successful', 'User', 81, 'Correct credentials', '::1', '2025-11-29 12:21:53'),
(87, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:22:54'),
(88, 75, 'Login Successful', 'User', 75, 'Correct credentials', '::1', '2025-11-29 12:23:36'),
(89, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:24:26'),
(90, 9, 'Login Successful', 'User', 9, 'Correct credentials', '::1', '2025-11-29 12:47:53'),
(91, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 12:51:03'),
(92, 9, 'Login Successful', 'User', 9, 'Correct credentials', '::1', '2025-11-29 12:56:46'),
(93, 9, 'Login Successful', 'User', 9, 'Correct credentials', '::1', '2025-11-29 13:36:27'),
(94, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 13:39:12'),
(95, 9, 'Login Successful', 'User', 9, 'Correct credentials', '::1', '2025-11-29 13:40:18'),
(96, 69, 'Login Successful', 'User', 69, 'Correct credentials', '::1', '2025-11-29 13:44:44'),
(97, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 14:14:53'),
(98, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 14:15:07'),
(99, NULL, 'Login Successful', 'User', 82, 'Correct credentials', '::1', '2025-11-29 14:37:55'),
(100, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 14:40:06'),
(101, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 15:04:40'),
(102, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 15:32:45'),
(103, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 16:56:07'),
(104, 67, 'Login Successful', 'User', 67, 'Correct credentials', '::1', '2025-11-29 16:56:53'),
(105, 7, 'Login Successful', 'User', 7, 'Correct credentials', '::1', '2025-11-29 16:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` int(11) NOT NULL,
  `StaffID` int(11) DEFAULT NULL,
  `CourseName` varchar(100) NOT NULL,
  `CourseCode` varchar(20) NOT NULL,
  `Description` text DEFAULT NULL,
  `Credits` int(11) NOT NULL,
  `Fee` decimal(10,2) DEFAULT 0.00,
  `IsActive` tinyint(1) DEFAULT 1,
  `StartDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `StaffID`, `CourseName`, `CourseCode`, `Description`, `Credits`, `Fee`, `IsActive`, `StartDate`) VALUES
(1, NULL, 'Introduction to Programming', 'CS101', 'Learn C, C++, and programming basics', 3, 150.00, 1, '2025-01-10'),
(2, NULL, 'Web Development Fundamentals', 'WEB102', 'HTML, CSS, and JavaScript basics', 3, 120.00, 1, '2025-01-15'),
(3, NULL, 'Database Systems', 'DB201', 'MySQL, ERD, normalization', 4, 200.00, 1, '2025-02-01'),
(4, NULL, 'Software Engineering', 'SE202', 'SDLC, agile, and team projects', 4, 180.00, 1, '2025-02-10'),
(5, 3, 'Python Programming Essentials', 'PY101', 'Introduction to Python, syntax, and control structures.', 3, 160.00, 1, '2025-02-20'),
(6, 4, 'Machine Learning Basics', 'ML102', 'Supervised and unsupervised learning fundamentals.', 4, 220.00, 1, '2025-03-05'),
(7, 5, 'Data Structures in Java', 'DS103', 'Learn about stacks, queues, trees, and linked lists in Java.', 3, 180.00, 1, '2025-03-10'),
(8, 6, 'Network Security Fundamentals', 'NS104', 'Learn to secure networks and understand firewalls and encryption.', 3, 200.00, 1, '2025-03-25'),
(9, 7, 'Advanced Web Development', 'WD105', 'PHP, MySQL, and AJAX for dynamic websites.', 4, 190.00, 1, '2025-04-01'),
(10, 8, 'Database Design and SQL', 'DB106', 'Design relational databases and write complex SQL queries.', 3, 175.00, 1, '2025-04-15'),
(11, 9, 'Operating Systems Concepts', 'OS107', 'Learn process management, scheduling, and memory handling.', 4, 210.00, 1, '2025-05-01'),
(12, 10, 'Artificial Intelligence Concepts', 'AI108', 'Overview of AI models, algorithms, and decision systems.', 3, 250.00, 1, '2025-05-10'),
(13, 11, 'Computer Networks', 'CN109', 'Study TCP/IP, routing, and network troubleshooting.', 3, 185.00, 1, '2025-05-20'),
(14, 12, 'Software Project Managements', 'SP110', 'Learn to manage software projects and Agile frameworks.', 5, 240.00, 1, '2025-10-30'),
(17, 43, 'Introduction to Physics', 'ND200', '', 3, 20000.00, 1, '2025-11-29');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollmentID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `EnrollmentDate` date NOT NULL,
  `FinalGrade` decimal(5,2) DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'registered',
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`EnrollmentID`, `StudentID`, `CourseID`, `EnrollmentDate`, `FinalGrade`, `Status`, `IsActive`) VALUES
(92, 25, 3, '2025-07-12', 88.40, 'completed', 1),
(93, 25, 12, '2025-09-05', NULL, 'registered', 1),
(94, 26, 1, '2025-06-20', 85.90, 'completed', 1),
(95, 26, 8, '2025-07-15', NULL, 'registered', 1),
(98, 28, 5, '2025-07-05', 87.20, 'completed', 1),
(100, 29, 7, '2025-06-12', 90.10, 'completed', 1),
(110, 36, 13, '2025-11-24', 0.00, 'registered', 1),
(113, 28, 10, '2025-10-30', NULL, 'registered', 1),
(115, 37, 6, '2025-11-29', 80.00, 'completed', 1),
(116, 28, 9, '2025-11-28', NULL, 'registered', 1),
(121, 29, 11, '2025-12-01', 50.00, 'completed', 1);

-- --------------------------------------------------------

--
-- Table structure for table `loginattempts`
--

CREATE TABLE `loginattempts` (
  `AttemptID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `AttemptTime` datetime DEFAULT current_timestamp(),
  `IsSuccessful` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loginattempts`
--

INSERT INTO `loginattempts` (`AttemptID`, `UserID`, `AttemptTime`, `IsSuccessful`) VALUES
(28, 7, '2025-11-11 12:04:22', 1),
(29, 27, '2025-11-11 12:05:11', 1),
(30, 10, '2025-11-11 12:06:04', 0),
(31, 10, '2025-11-11 12:07:23', 1),
(32, 27, '2025-11-11 12:30:36', 0),
(33, 27, '2025-11-11 12:41:27', 1),
(34, 41, '2025-11-11 12:42:22', 1),
(35, 41, '2025-11-11 12:42:33', 0),
(36, 41, '2025-11-11 12:42:58', 1),
(37, 27, '2025-11-11 12:45:20', 1),
(38, 41, '2025-11-11 12:47:54', 0),
(39, 41, '2025-11-11 13:12:15', 1),
(40, 36, '2025-11-11 13:16:31', 0),
(41, 36, '2025-11-11 13:18:56', 1),
(42, 7, '2025-11-11 15:13:25', 1),
(43, 7, '2025-11-11 15:29:13', 1),
(44, 7, '2025-11-11 15:40:33', 0),
(45, 7, '2025-11-11 15:40:39', 0),
(46, 7, '2025-11-11 15:40:58', 0),
(47, 7, '2025-11-11 15:41:20', 0),
(48, 7, '2025-11-11 15:41:27', 1),
(49, 7, '2025-11-11 16:06:08', 0),
(50, 7, '2025-11-11 16:27:58', 0),
(51, 7, '2025-11-11 16:28:03', 1),
(52, 7, '2025-11-11 16:29:10', 1),
(53, 7, '2025-11-12 15:28:26', 1),
(54, 7, '2025-11-12 16:03:49', 1),
(55, 7, '2025-11-12 16:04:09', 1),
(56, 7, '2025-11-12 16:04:21', 1),
(57, 7, '2025-11-12 16:24:37', 1),
(58, 10, '2025-11-13 19:05:57', 1),
(59, 7, '2025-11-13 19:15:42', 1),
(60, 39, '2025-11-13 19:16:29', 1),
(61, 39, '2025-11-13 19:22:39', 1),
(62, 39, '2025-11-13 19:33:41', 1),
(63, 10, '2025-11-13 19:47:25', 0),
(64, 10, '2025-11-13 19:47:38', 0),
(65, 10, '2025-11-13 19:47:45', 1),
(66, 10, '2025-11-13 19:54:25', 1),
(67, 10, '2025-11-13 19:55:06', 1),
(68, 39, '2025-11-13 20:02:25', 0),
(69, 39, '2025-11-13 20:03:09', 0),
(70, 39, '2025-11-13 20:11:28', 0),
(71, 39, '2025-11-13 20:13:43', 1),
(72, 39, '2025-11-13 20:17:35', 1),
(73, 39, '2025-11-13 20:43:26', 0),
(74, 39, '2025-11-13 20:45:28', 1),
(75, 7, '2025-11-13 21:03:47', 1),
(76, 7, '2025-11-13 21:51:20', 1),
(77, 7, '2025-11-13 22:35:23', 1),
(78, 66, '2025-11-13 23:13:06', 1),
(79, NULL, '2025-11-13 23:19:34', 0),
(80, 66, '2025-11-13 23:20:40', 1),
(81, 66, '2025-11-13 23:20:47', 1),
(82, 66, '2025-11-14 13:49:40', 1),
(83, 66, '2025-11-14 13:49:47', 1),
(84, 67, '2025-11-14 13:51:26', 1),
(85, 7, '2025-11-14 13:51:49', 1),
(86, 10, '2025-11-14 14:00:12', 1),
(87, 7, '2025-11-14 14:10:59', 1),
(88, 41, '2025-11-14 14:15:21', 0),
(89, 41, '2025-11-14 14:15:26', 0),
(90, 41, '2025-11-14 14:15:31', 0),
(91, 41, '2025-11-14 14:15:38', 0),
(92, 41, '2025-11-14 14:15:46', 0),
(95, 29, '2025-11-14 14:16:53', 0),
(96, 7, '2025-11-14 14:17:20', 1),
(97, 9, '2025-11-14 14:17:45', 1),
(98, 9, '2025-11-14 14:24:50', 1),
(99, 27, '2025-11-14 14:41:55', 1),
(100, 9, '2025-11-14 14:42:48', 1),
(101, 7, '2025-11-14 14:44:13', 1),
(102, 27, '2025-11-14 14:44:48', 1),
(103, 27, '2025-11-15 11:18:42', 1),
(104, 27, '2025-11-15 11:27:08', 1),
(105, 27, '2025-11-15 11:40:21', 1),
(106, 9, '2025-11-15 11:41:09', 1),
(107, 7, '2025-11-15 11:45:39', 1),
(108, 7, '2025-11-15 11:58:19', 1),
(109, 7, '2025-11-15 11:59:47', 1),
(110, 27, '2025-11-15 12:00:34', 1),
(111, 27, '2025-11-15 12:07:12', 1),
(112, 7, '2025-11-15 12:07:30', 1),
(113, 7, '2025-11-15 13:05:00', 1),
(114, 7, '2025-11-15 13:34:40', 1),
(115, 7, '2025-11-15 13:53:15', 1),
(116, 7, '2025-11-15 15:15:38', 1),
(117, 68, '2025-11-15 15:19:40', 1),
(118, 7, '2025-11-15 15:29:21', 1),
(119, 7, '2025-11-15 15:39:58', 1),
(120, 68, '2025-11-15 15:40:43', 1),
(121, 7, '2025-11-15 16:01:32', 1),
(122, 70, '2025-11-15 16:02:37', 1),
(123, 70, '2025-11-15 16:07:09', 1),
(124, 29, '2025-11-15 16:07:42', 1),
(126, 7, '2025-11-15 16:57:36', 1),
(127, 7, '2025-11-15 18:44:46', 1),
(128, 69, '2025-11-15 18:47:00', 1),
(129, 70, '2025-11-15 19:15:47', 1),
(130, 7, '2025-11-17 15:29:46', 1),
(131, 7, '2025-11-17 15:31:47', 1),
(132, 29, '2025-11-17 15:35:24', 0),
(133, 29, '2025-11-17 15:35:28', 1),
(134, 7, '2025-11-18 10:43:09', 1),
(135, 7, '2025-11-18 14:27:16', 1),
(136, 7, '2025-11-18 15:09:54', 1),
(137, 29, '2025-11-18 15:12:45', 0),
(138, 29, '2025-11-18 15:12:51', 1),
(139, 7, '2025-11-18 15:18:03', 1),
(140, 7, '2025-11-18 15:45:58', 1),
(141, 7, '2025-11-18 16:51:12', 1),
(142, 7, '2025-11-18 16:59:26', 1),
(143, 7, '2025-11-18 16:59:46', 1),
(144, 70, '2025-11-24 10:29:54', 1),
(150, 7, '2025-11-24 15:03:41', 1),
(151, 70, '2025-11-24 15:05:06', 1),
(152, 70, '2025-11-24 15:05:57', 0),
(153, 68, '2025-11-24 15:07:02', 0),
(154, 69, '2025-11-24 15:07:11', 1),
(155, 7, '2025-11-24 15:07:37', 1),
(156, 69, '2025-11-24 15:14:27', 1),
(157, 69, '2025-11-24 15:16:08', 0),
(158, 66, '2025-11-24 15:16:19', 1),
(159, 66, '2025-11-24 15:45:20', 0),
(160, 70, '2025-11-24 15:45:29', 1),
(161, 7, '2025-11-24 16:42:46', 1),
(163, 7, '2025-11-24 16:47:01', 1),
(164, 7, '2025-11-24 16:59:27', 1),
(165, 7, '2025-11-24 19:20:56', 1),
(166, 7, '2025-11-24 19:40:00', 1),
(167, 7, '2025-11-26 11:02:04', 1),
(168, 7, '2025-11-26 11:27:28', 0),
(169, 7, '2025-11-26 11:27:43', 0),
(170, 29, '2025-11-26 15:10:19', 0),
(171, 29, '2025-11-26 15:10:42', 0),
(172, NULL, '2025-11-26 15:37:00', 0),
(173, 7, '2025-11-27 20:29:30', 0),
(174, 7, '2025-11-27 20:29:44', 0),
(175, 7, '2025-11-27 20:30:05', 0),
(176, 7, '2025-11-29 12:16:37', 0),
(177, 7, '2025-11-29 12:24:17', 0),
(178, 7, '2025-11-29 13:40:03', 0),
(179, 88, '2025-11-29 16:36:24', 0),
(180, NULL, '2025-11-29 16:56:40', 0);

-- --------------------------------------------------------

--
-- Table structure for table `passwordreset`
--

CREATE TABLE `passwordreset` (
  `ResetID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Token` varchar(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `Status` enum('Pending','Used','Expired') NOT NULL DEFAULT 'Pending',
  `UsedAt` datetime DEFAULT NULL,
  `RequestIP` varchar(45) DEFAULT NULL,
  `UserAgent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passwordreset`
--

INSERT INTO `passwordreset` (`ResetID`, `UserID`, `Token`, `ExpiresAt`, `Status`, `UsedAt`, `RequestIP`, `UserAgent`) VALUES
(6, 27, '57afe3b06cfd10ec0333e0531dab1e275b5f65e810f2265f99d24628254c2cb1', '2025-11-11 13:00:42', 'Expired', NULL, NULL, NULL),
(7, 27, '2b87255c2c767082ec1f46158853f5857309c1dde76e64c108fb78b0ccc7fc4f', '2025-11-11 13:04:40', 'Expired', NULL, NULL, NULL),
(15, 41, 'a20a39904e9de919', '2025-11-11 16:24:57', 'Expired', NULL, NULL, NULL),
(23, 10, '27bfde93bdc77992cb28063a86210c450682a9da8e6e2fb3a9113540f2f1f8c3', '2025-11-13 20:24:38', 'Used', '2025-11-13 19:54:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(25, 39, 'c839cc2fe9e55dd68db9b936ff9ac6708c5f3677783b7c757065c476adeb2a97', '2025-11-13 20:41:38', 'Used', '2025-11-13 20:13:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(26, 39, '5559377eacfa69ee51237fdcb45e4de6e91a8b3df9057286164c0600b41be54c', '2025-11-13 20:46:00', 'Used', '2025-11-13 20:16:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(28, 39, '6ebaede544c6dd904feb892fb60f4b12ca578dc21ea1dc3811060b90989a7fd1', '2025-11-13 21:12:52', 'Used', '2025-11-13 20:43:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(29, 39, 'dcc4f4f97f8de4ec00e89788e6ac22e89687e81a95375855230bb900780033f2', '2025-11-13 21:13:37', 'Used', '2025-11-13 20:45:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(30, 7, '1b8c9b3209c8de3cc213c03cddbba24c66ce7219c225729447bdb2c6f26db980', '2025-11-27 20:13:47', 'Used', '2025-11-27 19:44:05', NULL, NULL),
(31, 60, '7a920ffbed07b6e9b142605940b0f85377ec39140a236a75faf386497a6f0429', '2025-11-29 12:47:09', 'Used', '2025-11-29 12:17:46', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `StaffID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `CourseName` varchar(100) DEFAULT NULL,
  `Salary` decimal(10,2) DEFAULT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `AvatarImage` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `HireDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `UserID`, `FirstName`, `LastName`, `Email`, `CourseID`, `CourseName`, `Salary`, `ProfileImage`, `AvatarImage`, `IsActive`, `HireDate`) VALUES
(3, 17, 'Aarav', 'Shrestha', 'aarav.shrestha@school.edu', 12, 'Artificial Intelligence Concepts', 67000.00, NULL, NULL, 1, '2020-09-10'),
(4, 18, 'Meena', 'Thapa', 'meena.thapa@school.edu', 10, 'Database Design and SQL', 63000.00, NULL, NULL, 1, '2021-01-15'),
(5, 19, 'Raj', 'KC', 'raj.kc@school.edu', 1, 'Introduction to Programming', 71000.00, NULL, NULL, 1, '2019-06-20'),
(6, 20, 'Rita', 'Gurung', 'rita.gurung@school.edu', 17, 'Introduction to Physics', 68000.00, NULL, NULL, 1, '2021-04-02'),
(7, 21, 'Prakash', 'Bhandari', 'prakash.bhandari@school.edu', 12, 'Artificial Intelligence Concepts', 72000.00, NULL, NULL, 1, '2020-11-28'),
(8, 22, 'Sunita', 'Tamang', 'sunita.tamang@school.edu', 8, 'Network Security Fundamentals', 74000.00, NULL, NULL, 1, '2022-02-10'),
(9, 23, 'Nirajan', 'Rai', 'nirajan.rai@school.edu', 6, 'Machine Learning Basics', 69000.00, NULL, NULL, 1, '2021-06-19'),
(10, 24, 'Rekha', 'Basnet', 'rekha.basnet@school.edu', 7, 'Data Structures in Java', 61000.00, NULL, NULL, 1, '2018-12-12'),
(11, 25, 'Sagar', 'Sharma', 'sagar.sharma@school.edu', 14, 'Software Project Managements', 66500.00, NULL, NULL, 1, '2019-08-05'),
(12, 26, 'Kritika', 'Pandey', 'kritika.pandey@school.edu', 10, 'Database Design and SQL', 70000.00, NULL, NULL, 1, '2021-09-22'),
(13, 54, 'Manu', 'Shah', 'manu@school.edu', 4, 'Software Engineering', 25000.00, NULL, NULL, 1, '2025-11-02'),
(14, 55, 'Mohini', 'Sharma', 'mohini@school.edu', 5, 'Python Programming Essentials', 60000.00, NULL, NULL, 1, '2025-11-04'),
(16, 56, 'Hina', 'khan', 'hina@school.edu', 8, 'Network Security Fundamentals', 50000.00, NULL, NULL, 1, '2025-11-04'),
(26, 60, 'Salman', 'khan', 'salman@school.edu', 11, 'Operating Systems Concepts', 50000.00, NULL, NULL, 1, '2025-11-04'),
(30, 62, 'Naomi', 'Sharma', 'naomi@school.edu', 9, 'Advanced Web Development', 300.00, NULL, NULL, 1, '2025-11-04'),
(32, 63, 'Akela', 'mahato', 'akela@school.edu', 6, 'Machine Learning Basics', 15000.00, NULL, NULL, 1, '2025-11-04'),
(34, 9, 'Manisha', 'Baskota', 'manisha@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(35, 32, 'Ram', 'Staff', 'ram@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(36, 36, 'Rahul', 'Staff', 'rahul@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(37, 37, 'Ajay', 'Staff', 'ajay@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(38, 40, 'Ronaldo7', 'Staff', 'ronaldo7@school.edu', 11, 'Operating Systems Concepts', 0.00, NULL, NULL, 1, '2025-11-13'),
(39, 41, 'Pumpum', 'Staff', 'pumpum@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(40, 44, 'Lakhan', 'Staff', 'lakhan@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(41, 46, 'Yaya', 'Staff', 'yaya@school.edu', 7, 'Data Structures in Java', 0.00, NULL, NULL, 1, '2025-11-13'),
(42, 61, 'Harry', 'Staff', 'harry@school.edu', 13, 'Computer Networks', 0.00, NULL, NULL, 1, '2025-11-13'),
(43, 67, 'Kareena', 'Sherpa', 'Kareena@school.edu', 13, 'Computer Networks', 50000.00, NULL, NULL, 1, '2025-11-14'),
(44, 68, 'Jacky', 'Shroff', 'jacky@school.edu', 10, 'Database Design and SQL', 30000.00, 'https://cdn-icons-png.flaticon.com/512/2922/2922688.png', NULL, 1, '2025-11-15'),
(45, 70, 'Arjun', 'Tamang', 'arjun@school.edu', 1, 'Introduction to Programming', 0.00, 'https://cdn-icons-png.flaticon.com/512/2922/2922688.png', NULL, 1, '2025-11-15'),
(49, 75, 'Royal', 'fernandez', 'royal20@school.edu', 10, 'Database Design and SQL', 50000.00, NULL, NULL, 1, '2025-01-26');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Age` int(11) DEFAULT NULL,
  `GPA` decimal(3,2) DEFAULT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `AvatarImage` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `UserID`, `FirstName`, `LastName`, `DateOfBirth`, `Email`, `Age`, `GPA`, `ProfileImage`, `AvatarImage`, `IsActive`) VALUES
(25, 10, 'Pratik', 'Adhikari', '2002-07-19', 'pratik.adhikari@school.edu', 22, 3.68, NULL, NULL, 1),
(26, 11, 'Sneha', 'Lama', '2003-03-11', 'sneha.lama@school.edu', 21, 3.84, NULL, NULL, 1),
(28, 13, 'Anisha', 'Khadka', '2004-02-17', 'anisha.khadka@school.edu', 20, 3.91, NULL, NULL, 1),
(29, 14, 'Bibek', 'KC', '2002-12-09', 'bibek.kc@school.edu', 22, 3.74, NULL, NULL, 1),
(30, 15, 'Kritika', 'Rai', '2003-05-23', 'kritika.rai@school.edu', 21, 3.88, NULL, NULL, 1),
(31, 16, 'Sandeep', 'Gautam', '2001-09-30', 'sandeep.gautam@student.edu', 23, 3.77, NULL, NULL, 1),
(36, 27, 'anmol', 'shrestha', '2014-02-05', 'anmol@school.edu', 23, NULL, 'IMG_27_1763203156.jpg', NULL, 1),
(37, 28, 'Biplove', 'Karki', '2004-03-11', 'karkibiplove2060@gmail.com', 21, NULL, NULL, NULL, 1),
(38, 29, 'Aliya', 'Tamang', '2003-11-12', 'aliya@gmail.com', 22, NULL, NULL, NULL, 1),
(39, 31, 'Rabin', 'Bhattarai', '2025-10-31', 'rabin@gmail.com', 0, NULL, NULL, NULL, 1),
(40, 49, 'New', 'Student', '2025-11-02', 'manjil@futurebillionaire.edu', 18, 0.00, NULL, NULL, 1),
(44, 53, 'Lepe', 'Student', '2025-11-02', 'lepe@futurebillionaire.edu', 18, 0.00, NULL, NULL, 1),
(45, 64, 'Jeeshan', 'Sharma', '2000-06-07', 'jeesha@school.edu', 25, NULL, NULL, NULL, 1),
(46, 65, 'Faisal', 'Khan', '1998-06-23', 'faisal@school.edu', 27, NULL, NULL, NULL, 1),
(47, 30, 'Adam', 'Student', '2025-11-13', 'adam@school.edu', 18, 0.00, NULL, NULL, 1),
(48, 38, 'Lalu', 'Student', '2025-11-13', 'lalu@school.edu', 18, 0.00, NULL, NULL, 1),
(49, 39, 'Ronaldo', 'Student', '2025-11-13', 'ronaldo@school.edu', 18, 0.00, NULL, NULL, 1),
(50, 42, 'Sita', 'Student', '2025-11-13', 'sita@school.edu', 18, 0.00, NULL, NULL, 1),
(51, 43, 'Rita', 'Student', '2025-11-13', 'rita@school.edu', 18, 0.00, NULL, NULL, 1),
(52, 45, 'Hero', 'Student', '2025-11-13', 'hero@school.edu', 18, 0.00, NULL, NULL, 1),
(53, 47, 'Hoho', 'Student', '2025-11-13', 'hoho@school.edu', 18, 0.00, NULL, NULL, 1),
(54, 69, 'Kishan', 'Magar', '2000-07-13', 'kishan@school.edu', 22, 0.00, NULL, NULL, 1),
(55, 72, 'Max', 'Sharma', '2008-02-05', 'max@school.edu', 22, 0.00, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Admin','Staff','Student') NOT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `LoginCount` int(11) DEFAULT 0,
  `LastLogin` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `Email`, `PasswordHash`, `Role`, `ProfileImage`, `LoginCount`, `LastLogin`, `IsActive`, `CreatedDate`) VALUES
(7, 'admin', 'admin1@school.edu', '$2y$10$KLjlHW8IvAoGmQ29Jc.23uSowuGdbI.piS4SFudfzFedcAeIpbsaW', 'Admin', 'https://cdn-icons-png.flaticon.com/512/2922/2922716.png', 138, '2025-11-29 16:58:06', 1, '2025-10-28 19:23:49'),
(9, 'manisha', 'manisha@school.edu', '$2y$10$nlzEHWUpk6vHKI.ZweCbNeZ0B.QvKTO1EDtuW0pCRm1NL0nDJraGW', 'Staff', NULL, 9, '2025-11-29 13:40:18', 1, '2025-10-28 19:38:34'),
(10, 'pratik', 'pratik.adhikari@school.edu', '$2y$10$N4FHXXLOz/tKHUIdgQqwSufV0B6mPhEwsOi7mCZ9u7BUjun/H0flm', 'Student', NULL, 10, '2025-11-14 14:00:12', 1, '2025-10-28 19:55:57'),
(11, 'sneha', 'sneha.lama@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', NULL, 0, NULL, 1, '2025-10-28 19:55:57'),
(13, 'anisha', 'anisha.khadka@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', NULL, 0, NULL, 1, '2025-10-28 19:55:57'),
(14, 'bibek', 'bibek.kc@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', NULL, 1, '2025-11-29 12:15:27', 1, '2025-10-28 19:55:57'),
(15, 'kritika', 'kritika.rai@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', NULL, 0, NULL, 1, '2025-10-28 19:55:57'),
(16, 'sandeep', 'sandeep.gautam@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', NULL, 0, NULL, 1, '2025-10-28 19:55:57'),
(17, 'aaravshrestha', 'aarav.shrestha@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(18, 'meenathapa', 'meena.thapa@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 1, '2025-11-04 14:42:00', 1, '2025-10-28 20:27:26'),
(19, 'rajkc', 'raj.kc@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(20, 'ritagurung', 'rita.gurung@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(21, 'prakashbhandari', 'prakash.bhandari@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(22, 'sunitatamang', 'sunita.tamang@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(23, 'nirajanrai', 'nirajan.rai@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(24, 'rekhabasnet', 'rekha.basnet@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(25, 'sagarsharma', 'sagar.sharma@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 2, '2025-10-29 19:23:51', 1, '2025-10-28 20:27:26'),
(26, 'kritikapandey', 'kritika.pandey@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', NULL, 0, NULL, 1, '2025-10-28 20:27:26'),
(27, 'anmol', 'anmol@school.edu', '$2y$10$aaJKaNgUCVq6EoSECuakW.VRaY2amr4wyPuGWhUT5jACC5XMkSakO', 'Student', NULL, 11, '2025-11-15 12:07:12', 1, '2025-10-28 20:56:51'),
(28, 'biplove', 'karkibiplove2060@gmail.com', '$2y$10$B5jj9Un/NRmPC1YHjsIKWOf9vR6JoMzKuGfRvdjQRttGfhC7VXBq2', 'Student', NULL, 1, '2025-10-28 21:16:50', 1, '2025-10-28 21:16:11'),
(29, 'aliya', 'aliya@gmail.com', '$2y$10$CeR1UKCjtVdySqkXSZ.eOOZu/TMTtLIo0XJOzrmCjnsdfDDYW6IbS', 'Student', NULL, 6, '2025-11-29 12:12:52', 1, '2025-10-29 14:34:40'),
(30, 'adam', 'adam@school.edu', '$2y$10$PCSfJgSLboCZ04VOHXTHO.ixBAKxkBjlGjjH.NWbChQCw/6sGhUqS', 'Student', NULL, 0, NULL, 1, '2025-10-29 18:47:08'),
(31, 'rabin', 'rabin@gmail.com', '$2y$10$RnIPdwTNHtOJVvbsui6oHOiPkbSQMsu2/0pVNfqvpi2k4TF/tioUW', 'Student', NULL, 1, '2025-10-31 13:08:41', 1, '2025-10-31 13:07:51'),
(32, 'ram', 'ram@school.edu', '$2y$10$.PZ7wmBmZsHP6etLRTY5JO67muREbr.I7D03dpd3eCpHp8HbnNch2', 'Staff', NULL, 0, NULL, 1, '2025-11-02 15:12:41'),
(36, 'rahul', 'rahul@school.edu', '$2y$10$YFE9Opq.6OHEe1h9MHMeVOur02/OeLjOhR4qEgQgSj/7fiRztz9na', 'Staff', NULL, 4, '2025-11-11 13:18:56', 1, '2025-11-02 15:21:29'),
(37, 'ajay', 'ajay@school.edu', '$2y$10$IuSDGzgrq0KUROJM/ndFM.rAsGiV47M4rpd55wff5RG08NrIvtepu', 'Staff', NULL, 0, NULL, 1, '2025-11-02 15:31:53'),
(38, 'lalu', 'lalu@school.edu', '$2y$10$pKZgBu0pOphiAlMk5/xYBOAPKZuDp7zR1XHYKQPFRl0gQ3jGJBL6O', 'Student', NULL, 0, NULL, 1, '2025-11-02 15:32:34'),
(39, 'ronaldo', 'ronaldo@school.edu', '$2y$10$kLNoOIc6iycoHWZXQhjmfudhzo69UwJ06YjpX8CNyXno0hL8ThwTG', 'Student', NULL, 7, '2025-11-13 20:45:28', 1, '2025-11-02 15:39:44'),
(40, 'ronaldo7', 'ronaldo7@school.edu', '$2y$10$mkPx2u1ekB6QzCiSATUXtO/PmG21FVV4eSJZ/2aKMY6VXzR4nX6my', 'Staff', NULL, 0, NULL, 1, '2025-11-02 15:49:15'),
(41, 'pumpum', 'pumpum@school.edu', '$2y$10$zQMae8QpxT29SLeo2qNX5.KCLmO5lhWg/jy2PIkqHEU307tyzVDKy', 'Staff', NULL, 3, '2025-11-11 13:12:15', 1, '2025-11-02 15:52:19'),
(42, 'sita', 'sita@school.edu', '$2y$10$jD83nM/WNCzlO6NK6qgl2uuUCtiueLxRl8lO1/czXw/bBb3NyDdY2', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:02:10'),
(43, 'rita', 'rita@school.edu', '$2y$10$7R2jFaLvaYUhbAwL0kvwJuOQs8CnAecpVHbTNNYxwzMWs8pL4ZClG', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:02:28'),
(44, 'lakhan', 'lakhan@school.edu', '$2y$10$0tS4E0PiY6UQcEhDaa0AauH1oyvyYdx4kBPiiTuJVHyO3hT/Gx.Gi', 'Staff', NULL, 0, NULL, 1, '2025-11-02 16:08:52'),
(45, 'hero', 'hero@school.edu', '$2y$10$d5THYlToqudr7KF99oaoZeLHEe/5gCQhD1gzH2DhNojjt7Axx3QNa', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:12:15'),
(46, 'yaya', 'yaya@school.edu', '$2y$10$J45KSLjM0xXfCYcKzq69mOJ.60zQv7q49imsLhJIyLkINa7Er1bCO', 'Staff', NULL, 0, NULL, 1, '2025-11-02 16:16:18'),
(47, 'hoho', 'hoho@school.edu', '$2y$10$uvO03yRbbIuM3KIWrppwY.eYf6G0NqMF8.TOmMvcXndFrtetsbZTC', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:22:46'),
(49, 'manjil', 'manjil@futurebillionaire.edu', '$2y$10$QuM3hXSuz.lIMyOlfVl6x.2pc6hKrI7dz25tw980NdtgCcFp8WVpu', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:26:22'),
(53, 'lepe', 'lepe@futurebillionaire.edu', '$2y$10$nngyFHhdIwMzz3JMi31tQO07CYvg8VAc5YCQeFtP0GB4tghK9qbwu', 'Student', NULL, 0, NULL, 1, '2025-11-02 16:33:47'),
(54, 'manu', 'manu@school.edu', '$2y$10$nK9dQ9X9oP6Vy1RPqW7ICupTcjYgKpo6CkbVGf37.x313XOWHcgDu', 'Staff', NULL, 0, NULL, 1, '2025-11-02 18:35:47'),
(55, 'mohini', 'mohini@school.edu', '$2y$10$dvDToJopli4SjhgfnWdOVeUWRp7wwHw7yLBQcSxpixRB9kVx604gW', 'Staff', NULL, 0, NULL, 1, '2025-11-04 11:23:06'),
(56, 'hina', 'hina@school.edu', '$2y$10$FKxlu8FSOOy0EBMjD8Zhve2AIrQD9VyZRz5jmV871BE0LLmI1JvDu', 'Staff', NULL, 0, NULL, 1, '2025-11-04 11:25:24'),
(60, 'salman', 'salman@school.edu', '$2y$10$lDy1aX2Y1ewsCUA0z3jy5.f0xvmO4JAs7CHoVgELlIRZvOB/Ak/vi', 'Staff', NULL, 1, '2025-11-29 12:18:08', 1, '2025-11-04 11:53:41'),
(61, 'harry', 'harry@school.edu', '$2y$10$FJsSeYqIrY96OZ4Yj8Hr7OjPcvdfpUfeTSYbINtICpJzVreVr4SSy', 'Staff', NULL, 0, NULL, 1, '2025-11-04 12:00:23'),
(62, 'naomi', 'naomi@school.edu', '$2y$10$q4ZftldRQEcfbuG4Ka8Uq.QTtG7nkQtAjXNXHe5zEfl/t.qWgQgFi', 'Staff', NULL, 0, NULL, 1, '2025-11-04 12:32:38'),
(63, 'akela', 'akela@school.edu', '$2y$10$IHgLk86aWoicvipRXrfC.e/WK19Nc7V7kEt7l4hnBgMTwe7FGA2MS', 'Staff', NULL, 1, '2025-11-04 12:42:56', 1, '2025-11-04 12:42:43'),
(64, 'jeeshan', 'jeesha@school.edu', '$2y$10$o1Hrg1moLoxnm.ewCJVicu9.P8AI2/lOoGIhARlu4maog0diIP4Ty', 'Student', NULL, 2, '2025-11-04 14:38:02', 1, '2025-11-04 14:36:38'),
(65, 'faisal', 'faisal@school.edu', '$2y$10$oO5i8xUhNvCk4LNW89t0Q.7yrFiWTn5IUXCn//njSAypp1K5HyZ0i', 'Student', NULL, 0, NULL, 1, '2025-11-04 15:00:59'),
(66, 'nepal', 'nepal@school.edu', '$2y$10$mOw8Sz3Ot45bXOSevD4vTuUlD7RE67bifzrPBJi7dbw94s.jUQcmK', 'Admin', NULL, 9, '2025-11-26 15:03:56', 1, '2025-11-13 23:12:56'),
(67, 'Kareena', 'Kareena@school.edu', '$2y$10$0yyzZDdrZrmadOVxqqPnTeFYRojMbA6yXhUw3Q3kzbg3fnQGEBDWW', 'Staff', NULL, 2, '2025-11-29 16:56:53', 1, '2025-11-14 13:50:54'),
(68, 'jacky', 'jacky@school.edu', '$2y$10$5cDk.G4C5t//TZ6e489q5usLZnTuedL66aGvWoOYhCEuqbfUOlQYW', 'Staff', NULL, 2, '2025-11-15 15:40:43', 1, '2025-11-15 15:19:20'),
(69, 'kishan', 'kishan@school.edu', '$2y$10$HS4rSTURsbmuq9MJUHZUBOBsyHuzrigyVN7hl.0Vyo9aPrgA.Gj0K', 'Student', NULL, 4, '2025-11-29 13:44:44', 1, '2025-11-15 15:59:11'),
(70, 'arjun', 'arjun@school.edu', '$2y$10$9S5I8.NDuMt0FyPZ.2dGG.0k71TNHfrCrzetjA29NkJERqrhph7ZC', 'Staff', NULL, 7, '2025-11-26 15:37:17', 1, '2025-11-15 16:01:16'),
(72, 'max22', 'max@school.edu', '$2y$10$28/Xl0X6ye6KoiteM8/71e0A32odD4HqviuqGsSnKyHVYaMeSoPxG', 'Student', NULL, 0, NULL, 1, '2025-11-18 16:59:08'),
(75, 'royal', 'royal20@school.edu', '$2y$10$Xivqh573Leg8jZFfIzdY4uTXrOxMHsr6xbmJIjqtQ3tki96X.5CKC', 'Staff', NULL, 1, '2025-11-29 12:23:36', 1, '2025-11-26 15:07:35'),
(80, 'anjil', 'anjil@school.edu', '$2y$10$wS2wRea0DkzrufzTfiFSoOtjmaosyOfnqU71G6ztBUYHEgy2EE8Mu', 'Admin', NULL, 3, '2025-11-29 11:36:54', 1, '2025-11-27 20:43:27'),
(88, 'yanu', 'yanu@school.edu', '$2y$10$cTMhi8XX7ZLUEd5nD/DNXOaKJ5ocYqhyJLbX2gAsTvEPndlcoDrD6', 'Staff', NULL, 0, NULL, 1, '2025-11-29 16:24:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD UNIQUE KEY `CourseCode` (`CourseCode`),
  ADD KEY `StaffID` (`StaffID`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD UNIQUE KEY `uniq_student_course` (`StudentID`,`CourseID`),
  ADD UNIQUE KEY `uq_enrollment_student_course` (`StudentID`,`CourseID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `loginattempts`
--
ALTER TABLE `loginattempts`
  ADD PRIMARY KEY (`AttemptID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `passwordreset`
--
ALTER TABLE `passwordreset`
  ADD PRIMARY KEY (`ResetID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`StaffID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `auditlogs`
--
ALTER TABLE `auditlogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `CourseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `loginattempts`
--
ALTER TABLE `loginattempts`
  MODIFY `AttemptID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `passwordreset`
--
ALTER TABLE `passwordreset`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `StaffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD CONSTRAINT `auditlogs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`) ON DELETE SET NULL;

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`) ON DELETE CASCADE;

--
-- Constraints for table `loginattempts`
--
ALTER TABLE `loginattempts`
  ADD CONSTRAINT `loginattempts_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `passwordreset`
--
ALTER TABLE `passwordreset`
  ADD CONSTRAINT `passwordreset_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_course` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
