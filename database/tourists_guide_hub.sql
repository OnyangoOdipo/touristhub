-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2024 at 03:31 PM
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
-- Database: `tourists_guide_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `tourist_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `booking_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `tourist_id`, `guide_id`, `destination_id`, `status`, `booking_date`, `created_at`, `amount`) VALUES
(1, 2, 3, 11, 'Completed', '2024-11-23 00:00:00', '2024-11-01 21:32:03', 0.00),
(2, 2, 1, 20, 'Pending', '2024-11-04 00:00:00', '2024-11-01 21:34:10', 0.00),
(3, 1, 3, 14, 'Completed', '2024-11-06 00:00:00', '2024-11-01 22:13:13', 0.00),
(4, 1, 3, 20, 'Cancelled', '2024-11-12 00:00:00', '2024-11-01 22:13:43', 0.00),
(5, 1, 3, 24, 'Pending', '2024-11-03 00:00:00', '2024-11-02 10:29:28', 0.00);

--
-- Triggers `bookings`
--
DELIMITER $$
CREATE TRIGGER `after_booking_update` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
    IF NEW.status = 'Confirmed' THEN
        -- Mark the date as unavailable in guide_availability
        INSERT INTO guide_availability (guide_id, date, status)
        VALUES (NEW.guide_id, DATE(NEW.booking_date), 'unavailable')
        ON DUPLICATE KEY UPDATE status = 'unavailable';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `destination_id` int(11) NOT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`destination_id`, `guide_id`, `name`, `description`, `location`, `region`, `activities`, `image`, `status`) VALUES
(11, 3, 'Serengeti National Park', 'Experience the great wildebeest migration and witness Africa\'s most spectacular wildlife in their natural habitat. Home to the Big Five and over 500 bird species.', 'Northern Tanzania', 'East Africa', 'Safari,Wildlife Photography,Bird Watching', 'serengeti.jpg', 'active'),
(12, 3, 'Victoria Falls', 'Known locally as \'Mosi-oa-Tunya\' (The Smoke that Thunders), this magnificent waterfall is one of the Seven Natural Wonders of the World.', 'Livingstone, Zambia', 'Southern Africa', 'Adventure,Hiking,Water Sports', 'victoria-falls.jpg', 'active'),
(13, 3, 'Pyramids of Giza', 'Explore the last remaining wonder of the ancient world. These magnificent structures have fascinated travelers for millennia.', 'Cairo, Egypt', 'North Africa', 'Historical,Cultural,Photography', 'pyramids.jpg', 'active'),
(14, 3, 'Masai Mara', 'Famous for its exceptional population of big cats, the Masai Mara is also home to the Maasai people and their vibrant culture.', 'Narok County, Kenya', 'East Africa', 'Safari,Cultural,Photography', 'masai-mara.jpg', 'active'),
(15, 3, 'Cape Town', 'Discover the stunning beauty of Table Mountain, pristine beaches, and world-class wineries in this cosmopolitan city.', 'Western Cape, South Africa', 'Southern Africa', 'Beach,Hiking,Cultural,Wine Tasting', 'cape-town.jpg', 'active'),
(16, 3, 'Zanzibar Archipelago', 'Experience the perfect blend of pristine beaches, historic Stone Town, and spice plantations on this tropical paradise.', 'Tanzania', 'East Africa', 'Beach,Cultural,Water Sports', 'zanzibar.jpg', 'active'),
(17, 3, 'Mount Kilimanjaro', 'Climb Africa\'s highest peak and the world\'s highest free-standing mountain. Experience diverse ecosystems and breathtaking views.', 'Tanzania', 'East Africa', 'Hiking,Adventure,Mountain Climbing', 'kilimanjaro.jpg', 'active'),
(18, 3, 'Marrakech Medina', 'Lose yourself in the vibrant souks, admire ancient architecture, and experience the magic of this UNESCO World Heritage site.', 'Marrakech, Morocco', 'North Africa', 'Cultural,Shopping,Historical', 'marrakech.jpg', 'active'),
(19, 3, 'Okavango Delta', 'Explore the world\'s largest inland river delta, home to diverse wildlife and stunning landscapes. Perfect for safari and bird watching.', 'Botswana', 'Southern Africa', 'Safari,Water Sports,Bird Watching', 'okavango.jpg', 'active'),
(20, 3, 'Volcanoes National Park', 'Trek through misty forests to encounter magnificent mountain gorillas in their natural habitat. A truly unforgettable experience.', 'Rwanda', 'East Africa', 'Wildlife,Hiking,Adventure', 'volcanoes.jpg', 'active'),
(23, 3, 'Elliott Hodge', 'Earum magnam minus s', 'Tempora exercitation', 'East Africa', 'Array', '672568bc0b7e8_pc screen.png', 'inactive'),
(24, 3, 'Adena Carney', 'Voluptas et necessit', 'Rem voluptatem qui q', 'East Africa', 'Safari,Beach,Adventure,Historical,Wildlife Photography,Mountain Climbing,Bird Watching', '67256983b41c8_Admin login.png', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` enum('read','unread') DEFAULT 'unread',
  `status` enum('sent','delivered','read') DEFAULT 'sent',
  `file_attachment` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `content`, `timestamp`, `read_status`, `status`, `file_attachment`, `file_type`) VALUES
(1, 2, 3, 'hi', '2024-11-01 21:09:24', 'read', 'sent', NULL, NULL),
(2, 2, 3, 'I hope youre doing well', '2024-11-01 21:09:41', 'read', 'sent', NULL, NULL),
(3, 2, 3, 'I hope youre doing well', '2024-11-01 21:17:01', 'read', 'sent', NULL, NULL),
(4, 3, 2, 'yes I&#39;m well, how are you?', '2024-11-01 22:27:41', 'unread', 'sent', '672555dd7f3f1_HCI Lecture 27 Groupware.pdf', 'application/pdf'),
(5, 3, 2, 'hi', '2024-11-01 22:27:53', 'unread', 'sent', NULL, NULL),
(6, 3, 2, 'hi', '2024-11-01 22:27:59', 'unread', 'sent', NULL, NULL),
(7, 3, 1, 'hi', '2024-11-01 22:28:21', 'read', 'sent', NULL, NULL),
(8, 3, 1, 'Your booking for Volcanoes National Park has been cancelled by the guide.', '2024-11-01 23:25:52', 'read', 'sent', NULL, NULL),
(9, 3, 1, 'Your booking for Masai Mara has been confirmed by the guide.', '2024-11-01 23:26:02', 'read', 'sent', NULL, NULL),
(10, 3, 1, 'Your tour of Masai Mara has been marked as completed. Please leave a review!', '2024-11-02 00:12:29', 'read', 'sent', NULL, NULL),
(11, 3, 2, 'Your tour of Serengeti National Park has been marked as completed. Please leave a review!', '2024-11-02 00:12:36', 'unread', 'sent', NULL, NULL),
(12, 3, 1, 'Hi', '2024-11-02 17:17:07', 'unread', 'sent', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `partnerships`
--

CREATE TABLE `partnerships` (
  `partnership_id` int(11) NOT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `preferences` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `tourist_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `tourist_id`, `guide_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 1, 3, 5, 'Laboriosam quasi no', '2024-11-02 10:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `typing_status`
--

CREATE TABLE `typing_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `is_typing` tinyint(1) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` enum('Tourist','Guide','Hub') NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rating` decimal(2,1) DEFAULT 0.0,
  `status` enum('pending','verified','rejected') DEFAULT 'verified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `email`, `password`, `name`, `contact_info`, `created_at`, `rating`, `status`) VALUES
(1, 'Tourist', 'syle@mailinator.com', '$2y$10$G3pm.3HFybvNunHBZ1wOkena.fFzmD4EsGJud3l6JkTkjivTAnChS', 'Nero Scott', NULL, '2024-11-01 16:37:29', 0.0, 'pending'),
(2, 'Tourist', 'zyhemel@mailinator.com', '$2y$10$GFkPKCF.XHjifArQgT9/Qux5QLUXeMSr.hLMH84.3hrmHuEJEkGWK', 'Kennedy Crane', NULL, '2024-11-01 16:53:34', 0.0, 'verified'),
(3, 'Guide', 'juhipi@mailinator.com', '$2y$10$ofIRep8IszQXDq3L1eNSA.qlp0HrHKp1wh7ZQvyotASu39ALqY0Ee', 'Elliott Wall', NULL, '2024-11-01 16:59:18', 5.0, 'verified'),
(4, 'Hub', 'fifolir@mailinator.com', '$2y$10$CxB0NMzijYoyuR7oWLg.k.jM.H3tdyJXfKl6ZNTM69QwF7N8YoRoG', 'Veda Harris', NULL, '2024-11-02 06:47:12', 0.0, 'verified');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `tourist_id` (`tourist_id`),
  ADD KEY `guide_id` (`guide_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`destination_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD PRIMARY KEY (`partnership_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `tourist_id` (`tourist_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `destination_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `partnerships`
--
ALTER TABLE `partnerships`
  MODIFY `partnership_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `typing_status`
--
ALTER TABLE `typing_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`guide_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`destination_id`);

--
-- Constraints for table `destinations`
--
ALTER TABLE `destinations`
  ADD CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`guide_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD CONSTRAINT `partnerships_ibfk_1` FOREIGN KEY (`guide_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`tourist_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`guide_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `typing_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `typing_status_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
