-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2025 at 10:24 AM
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
-- Database: `msms_db2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(3, 'Admin', 'Admin123', '2025-02-10 20:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `bill_date` date NOT NULL,
  `customer_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `total_amount`, `bill_date`, `customer_name`) VALUES
(5, 150.00, '2025-02-10', 'Rajesh'),
(6, 300.00, '2025-02-15', 'Rajesh'),
(7, 2000.00, '2025-02-19', 'Rajanna (Rajesh) Adeli'),
(8, 100.00, '2025-02-19', 'Rajesh'),
(9, 500.00, '2025-03-27', 'Ramesh'),
(10, 4000.00, '2025-03-28', 'Rahul'),
(11, 3000.00, '2025-03-28', 'Hashira');

-- --------------------------------------------------------

--
-- Table structure for table `bill_sales`
--

CREATE TABLE `bill_sales` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `sale_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_sales`
--

INSERT INTO `bill_sales` (`id`, `bill_id`, `sale_id`) VALUES
(13, 8, 13),
(14, 9, 14),
(15, 10, 15),
(16, 11, 16);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(4, 'Pfizer', 'One of the world&#039;s largest pharmaceutical companies, known for vaccines, oncology, and cardiovascular drugs.', '2025-02-15 19:36:59', '2025-02-15 19:36:59'),
(5, 'Johnson &amp; Johnson', 'A global healthcare giant producing pharmaceuticals, medical devices, and consumer health products.', '2025-02-15 19:37:17', '2025-02-15 19:37:17'),
(6, '	Novartis', 'A leading Swiss pharmaceutical company focused on innovative medicines in oncology, ophthalmology, and immunology.', '2025-02-15 19:37:34', '2025-02-15 19:37:34'),
(7, 'Roche', 'Specializes in biotechnology, cancer treatments, and diagnostics, known for its pioneering work in personalized healthcare.', '2025-02-15 19:37:49', '2025-02-15 19:37:49'),
(8, 'GlaxoSmithKline (GSK)', 'A multinational pharmaceutical company specializing in vaccines, respiratory medicines, and consumer healthcare.', '2025-02-15 19:38:01', '2025-02-15 19:38:01'),
(9, 'Merck &amp; Co.	', 'A global pharmaceutical leader in vaccines, oncology, and infectious disease research.', '2025-02-15 19:38:13', '2025-02-15 19:38:13');

-- --------------------------------------------------------

--
-- Table structure for table `diseases`
--

CREATE TABLE `diseases` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diseases`
--

INSERT INTO `diseases` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(5, 'Hypertension', 'High blood pressure', '2025-02-15 19:40:48', '2025-02-15 19:40:48'),
(6, 'Diabetes', 'High blood sugar levels', '2025-02-15 19:40:57', '2025-02-15 19:40:57'),
(7, 'Influenza (Flu)', 'Viral infection affecting the lungs', '2025-02-15 19:41:14', '2025-02-15 19:41:14'),
(8, 'Asthma', 'Chronic respiratory condition', '2025-02-15 19:41:25', '2025-02-15 19:41:25'),
(9, 'Tuberculosis (TB)', 'Bacterial lung infection', '2025-02-15 19:41:38', '2025-02-15 19:41:38'),
(10, 'Arthritis', 'Joint inflammation and pain', '2025-02-15 19:41:56', '2025-02-15 19:41:56'),
(11, 'Alzheimer&#039;s Disease', 'Memory loss and cognitive decline', '2025-02-15 19:42:03', '2025-02-15 19:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `use` varchar(255) DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `disease_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `use`, `selling_price`, `available_quantity`, `expiry_date`, `company_id`, `disease_id`) VALUES
(12, 'Metformin', '', 10.00, 40, '2025-06-04', 6, 10),
(13, 'New Medicine', '', 300.00, 70, '2025-03-28', 6, 6),
(14, 'naya', '', 10.00, 100, '2025-03-26', 4, 7);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread',
  `notification_type` enum('warning','expired') NOT NULL,
  `notified_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `medicine_id`, `expiry_date`, `message`, `created_at`, `status`, `notification_type`, `notified_until`) VALUES
(1, 14, NULL, 'EXPIRED: Medicine \'naya\' has expired on 2025-03-26. Remove from inventory immediately!', '2025-03-28 08:37:07', 'read', 'expired', '2025-03-26'),
(4, 14, '2025-03-26', 'EXPIRED: Medicine \'naya\' has expired on 2025-03-26. Remove from inventory immediately!', '2025-03-28 08:50:24', 'read', 'expired', '2025-03-26');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `total_cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `medicine_id`, `quantity`, `purchase_price`, `purchase_date`, `expiry_date`, `total_cost`) VALUES
(22, 12, 100, 8.00, '2025-02-19', '2025-06-04', 800.00),
(23, 13, 100, 200.00, '2025-03-27', '2025-03-28', 20000.00),
(24, 14, 100, 7.00, '2025-03-27', '2025-03-26', 700.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `medicine_id`, `quantity_sold`, `sale_price`, `sale_date`) VALUES
(13, 12, 10, 10.00, '2025-02-19'),
(14, 12, 50, 10.00, '2025-03-27'),
(15, 13, 20, 200.00, '2025-03-28'),
(16, 13, 10, 300.00, '2025-03-28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_sales`
--
ALTER TABLE `bill_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `diseases`
--
ALTER TABLE `diseases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicine_company` (`company_id`),
  ADD KEY `idx_medicine_disease` (`disease_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification` (`medicine_id`,`notification_type`,`expiry_date`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_medicine_id` (`medicine_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bill_sales`
--
ALTER TABLE `bill_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `diseases`
--
ALTER TABLE `diseases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_sales`
--
ALTER TABLE `bill_sales`
  ADD CONSTRAINT `bill_sales_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`),
  ADD CONSTRAINT `bill_sales_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `medicines_ibfk_2` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_medicine_id` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
