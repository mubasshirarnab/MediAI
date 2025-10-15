-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 08:58 PM
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
-- Database: `mediai`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_role` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `admin_role`, `department`, `created_at`, `updated_at`) VALUES
(1, 999, 'Super Admin', 'System Administration', '2025-10-15 17:04:55', '2025-10-15 17:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `ai_conversations`
--

CREATE TABLE `ai_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT 'New Chat',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_conversations`
--

INSERT INTO `ai_conversations` (`id`, `user_id`, `title`, `created_at`, `updated_at`) VALUES
(1, 14, 'Hello', '2025-05-06 16:13:38', '2025-05-06 16:16:11'),
(2, 14, 'I have a back pain.', '2025-05-06 16:30:28', '2025-05-06 16:31:06'),
(3, 14, 'heyyyyyy', '2025-05-06 16:32:33', '2025-05-06 16:33:38'),
(4, 14, 'hi there', '2025-05-06 16:37:02', '2025-05-06 16:37:24'),
(5, 13, 'helllo', '2025-05-08 14:04:26', '2025-05-08 14:04:26'),
(6, 13, 'I\'m  feeling so lonely. what should...', '2025-05-08 14:07:22', '2025-05-08 14:10:52'),
(7, 13, 'I\'m feeling backpain. yestarday I played football....', '2025-05-08 14:17:25', '2025-05-08 14:18:37'),
(8, 14, 'hey how can you assist me ?', '2025-05-10 06:29:36', '2025-05-10 06:29:36'),
(9, 14, 'New Chat', '2025-05-10 06:29:58', '2025-05-10 06:29:58'),
(10, 14, 'New Chat', '2025-06-16 19:08:49', '2025-06-16 19:08:49'),
(11, 14, 'New Chat', '2025-06-16 19:08:50', '2025-06-16 19:08:50'),
(12, 14, 'I have a backpain. Help me.', '2025-06-25 07:06:47', '2025-06-25 07:06:47'),
(13, 16, 'Hey. Give me the code of adding...', '2025-06-30 04:30:13', '2025-06-30 04:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `timeslot` varchar(50) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `appointment_status` enum('pending','confirmed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `patient_name`, `doctor_id`, `notes`, `phone`, `email`, `timeslot`, `report_file`, `hospital_id`, `appointment_status`, `created_at`) VALUES
(1, 14, NULL, 15, 'Feeling pain in my heart ', '01319312217', 'schowdhury222152@bscse.uiu.ac.bd', '2025-06-30 10:00:00', NULL, NULL, 'pending', '2025-10-15 16:59:29'),
(5, 14, 'Shahin Chowdhury', 19, '0', '01319312217', 'schowdhury222152@bscse.uiu.ac.bd', '2025-10-25 20:18:00', NULL, 7, 'pending', '2025-10-15 18:51:38'),
(6, 14, 'Samad Ali', 19, '0', '01319312217', 'schowdhury222152@bscse.uiu.ac.bd', '2025-10-25 20:18:00', NULL, 7, 'pending', '2025-10-15 18:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `available_hours`
--

CREATE TABLE `available_hours` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `day_of_week` tinyint(4) NOT NULL CHECK (`day_of_week` between 1 and 7),
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `available_hours`
--

INSERT INTO `available_hours` (`id`, `user_id`, `hospital_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(5, 15, NULL, 1, '10:00:00', '19:00:00'),
(6, 15, NULL, 2, '10:00:00', '19:00:00'),
(7, 15, NULL, 3, '10:00:00', '19:00:00'),
(8, 15, NULL, 4, '10:00:00', '19:00:00'),
(10, 19, 7, 1, '20:18:00', '23:23:00'),
(11, 19, 7, 2, '20:20:00', '23:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('unpaid','paid','cancelled') DEFAULT 'unpaid',
  `issued_date` date DEFAULT NULL,
  `bill_type` enum('advance','interim','final') DEFAULT 'final',
  `due_date` date DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance_amount` decimal(10,2) DEFAULT 0.00,
  `insurance_claim_id` varchar(100) DEFAULT NULL,
  `corporate_client_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `patient_id`, `amount`, `status`, `issued_date`, `bill_type`, `due_date`, `discount_amount`, `tax_amount`, `total_amount`, `paid_amount`, `balance_amount`, `insurance_claim_id`, `corporate_client_id`, `created_by`, `updated_by`, `updated_at`) VALUES
(1, 16, 3000.00, 'paid', NULL, 'final', NULL, 600.00, 0.00, 2400.00, 2400.00, 0.00, '0', 0, 7, 7, '2025-10-15 17:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `item_type` enum('service','medicine','test','room','doctor_fee','nursing','surgery','other') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `service_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `item_type`, `item_name`, `item_description`, `quantity`, `unit_price`, `total_price`, `discount_percentage`, `discount_amount`, `final_price`, `service_date`, `created_at`) VALUES
(1, 1, 'nursing', 'Nursing Care', 'Nursing Care - Nursing', 1, 500.00, 500.00, 0.00, 0.00, 500.00, NULL, '2025-10-15 17:13:12'),
(2, 1, 'room', 'General Ward Bed', 'General Ward Bed - General Ward', 1, 2000.00, 2000.00, 0.00, 0.00, 2000.00, NULL, '2025-10-15 17:13:12'),
(3, 1, 'nursing', 'Nursing Care', 'Nursing Care - Nursing', 1, 500.00, 500.00, 0.00, 0.00, 500.00, NULL, '2025-10-15 17:13:12');

-- --------------------------------------------------------

--
-- Table structure for table `cabins`
--

CREATE TABLE `cabins` (
  `cabin_id` int(11) NOT NULL,
  `cabin_number` varchar(50) NOT NULL,
  `type` enum('general','deluxe','ICU') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `availability` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabins`
--

INSERT INTO `cabins` (`cabin_id`, `cabin_number`, `type`, `price`, `availability`, `created_at`, `updated_at`) VALUES
(1, '1', 'general', 250.00, 1, '2025-10-03 19:39:07', '2025-10-03 19:39:07'),
(2, '2', 'deluxe', 500.00, 1, '2025-10-03 19:39:24', '2025-10-03 19:39:24'),
(3, '3', 'ICU', 1000.00, 1, '2025-10-03 19:39:44', '2025-10-03 19:39:44'),
(7, '6', 'deluxe', 200.00, 1, '2025-10-03 20:00:49', '2025-10-03 20:00:49'),
(9, '7', 'general', 10.00, 1, '2025-10-08 18:24:35', '2025-10-08 18:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `cabin_bookings`
--

CREATE TABLE `cabin_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cabin_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `status` enum('booked','completed','cancelled') NOT NULL DEFAULT 'booked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabin_bookings`
--

INSERT INTO `cabin_bookings` (`booking_id`, `user_id`, `cabin_id`, `booking_date`, `check_in`, `check_out`, `status`) VALUES
(1, 14, 1, '2025-10-03 19:41:14', '2025-10-10', '2025-10-15', 'booked'),
(2, 14, 3, '2025-10-03 19:41:39', '2025-10-10', '2025-10-15', 'booked'),
(3, 14, 2, '2025-10-03 19:42:29', '2025-10-10', '2025-10-15', 'booked'),
(4, 14, 2, '2025-10-03 20:02:06', '2025-10-15', '2025-10-20', 'booked'),
(5, 14, 7, '2025-10-03 20:02:43', '2025-10-05', '2025-10-20', 'booked'),
(6, 14, 2, '2025-10-03 20:03:09', '2025-10-06', '2025-10-08', 'booked'),
(7, 14, 7, '2025-10-03 20:03:34', '2025-10-27', '2025-10-30', 'booked');

-- --------------------------------------------------------

--
-- Table structure for table `cabin_bookings_legacy`
--

CREATE TABLE `cabin_bookings_legacy` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `cabin_type` varchar(50) DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `status` enum('booked','cancelled','completed') DEFAULT 'booked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_queries`
--

CREATE TABLE `chatbot_queries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `conversation_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_queries`
--

INSERT INTO `chatbot_queries` (`id`, `user_id`, `conversation_id`, `message`, `response`, `created_at`) VALUES
(1, 14, 1, 'Hello', 'Hello! How can I assist you today?', '2025-05-06 16:13:38'),
(2, 14, 1, 'Hey there..', 'I\'m not allowed to answer this question.', '2025-05-06 16:15:09'),
(3, 14, 1, 'I have a back pain. How can I get rid of it?', 'To address your back pain, consider the following **general recommendations**:\r\n\r\n1. **Rest**: Avoid strenuous activities, but avoid prolonged bed rest—gentle movement can aid recovery.  \r\n2. **Cold/Heat Therapy**: Apply an ice pack (15–20 minutes) to reduce inflammation initially. After 48 hours, use a heating pad for muscle relaxation.  \r\n3. **OTC Pain Relief**: Medications like ibuprofen or acetaminophen can help, but follow dosage instructions.  \r\n4. **Stretching/Yoga**: Gentle stretches or yoga poses (e.g., cat-cow, child’s pose) may relieve tension.  \r\n5. **Posture Correction**: Ensure ergonomic seating and avoid slouching.  \r\n6. **Professional Consultation**: If pain persists beyond a few days, worsens, or includes numbness/tingling, consult a **healthcare provider** for personalized guidance.  \r\n\r\n*Always seek medical advice if pain is severe, sudden, or accompanied by other symptoms (e.g., fever, leg weakness).*', '2025-05-06 16:16:11'),
(4, 14, 2, 'I have a back pain.', 'I\'m sorry to hear you\'re experiencing back pain. While I can\'t provide medical advice, here are some general suggestions:\r\n\r\n1. **Rest**: Avoid strenuous activities that may worsen the pain.  \r\n2. **Heat/Cold Therapy**: Apply a warm compress or ice pack to the affected area for 15-20 minutes.  \r\n3. **Over-the-Counter Pain Relief**: Consider medications like ibuprofen or acetaminophen (if not contraindicated for you).  \r\n4. **Gentle Stretching**: Light stretches or yoga may help relieve tension (stop if pain increases).  \r\n5. **Posture Check**: Ensure your sitting/standing posture isn’t contributing to the discomfort.  \r\n\r\n**Consult a healthcare professional** if:  \r\n- Pain is severe, persistent, or worsening.  \r\n- You experience numbness, tingling, or weakness in limbs.  \r\n- The pain follows an injury or accident.  \r\n\r\nAlways prioritize personalized medical advice from a doctor or physical therapist. Feel better soon!', '2025-05-06 16:31:06'),
(5, 14, 3, 'heyyyyyy', 'Hello! How can I assist you today? Whether you need medical guidance or mental health support, I\'m here to help. Let me know how I can assist. 😊', '2025-05-06 16:33:38'),
(6, 14, 4, 'hi there', 'Hello! How can I assist you with medical or mental health concerns today?', '2025-05-06 16:37:24'),
(7, 13, 5, 'helllo', 'Hello! ðŸ‘‹ Thank you for reaching out. How can I assist you with your medical or mental health concerns today? Please feel free to share your thoughts, and Iâ€™ll do my best to guide you. ðŸŒ¼ (Note: For urgent medical issues, consult a licensed professional immediately.)', '2025-05-08 14:04:26'),
(8, 13, 6, 'I\'m  feeling so lonely. what should I do ?', 'No response received.', '2025-05-08 14:10:52'),
(9, 13, 7, 'I\'m feeling backpain. yestarday I played football. now what should I do?', 'I\'m sorry to hear about your back pain. Here are some general suggestions to help alleviate discomfort:\r\n\r\n1. **Rest**: Avoid strenuous activities and give your body time to recover.  \r\n2. **Ice/Heat Therapy**: Apply an ice pack (wrapped in a cloth) to the affected area for 15-20 minutes every few hours for the first 48 hours. After that, switch to a warm compress to relax muscles.  \r\n3. **Gentle Stretching**: Perform light stretches to ease tension, but avoid sudden movements.  \r\n4. **Over-the-Counter Pain Relief**: Consider anti-inflammatory medication like ibuprofen, following dosage instructions.  \r\n5. **Monitor Symptoms**: If pain worsens, persists beyond a few days, or is accompanied by numbness/tingling in the legs, **consult a healthcare professional immediately** to rule out serious injuries.  \r\n\r\nAlways prioritize medical advice for persistent or severe pain.', '2025-05-08 14:18:37'),
(10, 14, 8, 'hey how can you assist me ?', 'Hello! How can I assist you today? Whether you need medical advice or mental health support, I\'m here to help. Please feel free to share your concerns.', '2025-05-10 06:29:36'),
(11, 14, 12, 'I have a backpain. Help me.', 'I\'m sorry to hear about your back pain. While I can\'t provide medical advice, here are some general suggestions that might help:\r\n\r\n1. **Rest**: Avoid strenuous activities that may worsen the pain, but try to stay moderately active to prevent stiffness.  \r\n2. **Posture**: Ensure proper posture while sitting, standing, or lifting objects.  \r\n3. **Heat/Cold Therapy**: Apply a warm compress or ice pack to the affected area for short periods.  \r\n4. **Gentle Stretching**: Try mild stretches or yoga (avoid overexertion).  \r\n5. **Over-the-Counter Pain Relief**: Medications like ibuprofen or acetaminophen *may* help, but consult a pharmacist or doctor first.  \r\n\r\n**Important**: If the pain is severe, persistent, accompanied by numbness/tingling, or after an injury, **seek immediate medical care**. A healthcare professional can diagnose the cause and recommend treatment.  \r\n\r\nTake care and prioritize seeing a doctor for personalized guidance!', '2025-06-25 07:06:47'),
(12, 16, 13, 'Hey. Give me the code of adding two numbers.', 'Hi there! 👋 I\'m here to help with Mental health support, and Physical health-related questions only. If you have other queries, please consult a medical expert or explore other features of MediAI. Thanks for understanding! 💙', '2025-06-30 04:30:13'),
(13, 16, 13, 'I have a backpain. Give me some suggestions so that I can get rid of it.', 'I\'m sorry to hear about your back pain. Here are some **general suggestions** to help alleviate discomfort, but if the pain is severe, persistent, or accompanied by other symptoms (e.g., numbness, fever, or loss of bladder/bowel control), consult a healthcare provider immediately:\r\n\r\n---\r\n\r\n### 1. **Gentle Movement**  \r\n   - Avoid prolonged bed rest; light activity (e.g., walking) can reduce stiffness.  \r\n   - Try gentle stretches like **cat-cow pose** or **child’s pose** to ease tension.  \r\n\r\n### 2. **Heat or Cold Therapy**  \r\n   - Apply an **ice pack** (for acute pain/swelling) or a **heating pad** (for muscle stiffness) for 15-20 minutes.  \r\n\r\n### 3. **Posture Adjustments**  \r\n   - Avoid slouching. Use a chair with lumbar support if sitting for long periods.  \r\n   - Adjust your workstation to align your spine neutrally.  \r\n\r\n### 4. **Over-the-Counter Relief**  \r\n   - Pain relievers like **ibuprofen** or **acetaminophen** (if approved by your doctor) may help reduce inflammation.  \r\n\r\n### 5. **Strengthen Core Muscles**  \r\n   - Engage in low-impact exercises like **pilates** or **bridges** to support your spine.  \r\n\r\n### 6. **Avoid Heavy Lifting**  \r\n   - If lifting is necessary, bend at the knees (not waist) and hold objects close to your body.  \r\n\r\n### 7. **Mattress Check**  \r\n   - Ensure your mattress supports proper spinal alignment. A medium-firm mattress is often recommended.  \r\n\r\n### 8. **Stress Management**  \r\n   - Stress can worsen muscle tension. Practice relaxation techniques like deep breathing or meditation.  \r\n\r\n---\r\n\r\n**When to see a doctor:**  \r\n- Pain lasts >2 weeks.  \r\n- Radiates to legs or arms.  \r\n- Follows an injury or accident.  \r\n\r\nFeel better soon! 💙 Always consult a medical expert for personalized advice.', '2025-06-30 04:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `commented_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `post_id` int(11) NOT NULL,
  `commentor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `comment`, `commented_at`, `post_id`, `commentor`) VALUES
(1, 'Hello', '2025-05-10 06:35:29', 1, 14),
(2, 'Hello', '2025-06-30 07:09:24', 4, 16);

-- --------------------------------------------------------

--
-- Table structure for table `community`
--

CREATE TABLE `community` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `community_creator` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community`
--

INSERT INTO `community` (`id`, `name`, `description`, `photo`, `community_creator`) VALUES
(1, 'Mental Support', 'Mental Support is a compassionate community dedicated to providing emotional support, encouragement, and a safe space for those facing mental health challenges. ', '1.jpg', 14),
(2, 'Diabetics Support', 'Diabetics Support is a caring community focused on sharing guidance, experiences, and encouragement for those living with diabetes.\r\nTogether, we manage, motivate, and thrive with informed choices and mutual support.', '4.png', 6),
(3, 'CareNest', 'CareNest is a supportive online health community where people connect, share experiences, and access trustworthy information on wellness, mental health, fitness, chronic illness, and preventive care.', '3.png', 10),
(4, 'Soul Support', 'A safe and loving space for healing hearts and uplifting minds.\r\n', '4.jpg', 16),
(5, 'Rise Within', 'Empowering growth, resilience, and inner strength through shared support.', '5.jpg', 16),
(6, 'Hope Harbor', 'Anchored in empathy, we share hope and healing one day at a time.\r\n', '6.jpg', 16);

-- --------------------------------------------------------

--
-- Table structure for table `community_members`
--

CREATE TABLE `community_members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_members`
--

INSERT INTO `community_members` (`id`, `user_id`, `community_id`, `joined_at`) VALUES
(1, 14, 1, '2025-05-06 19:43:38'),
(2, 10, 3, '2025-05-08 17:20:58'),
(3, 10, 1, '2025-05-08 17:49:01'),
(4, 15, 1, '2025-05-09 10:25:31'),
(5, 15, 3, '2025-05-10 10:17:42'),
(6, 15, 2, '2025-05-10 10:17:49'),
(7, 16, 1, '2025-06-23 09:13:35'),
(8, 13, 1, '2025-06-24 20:52:15'),
(9, 16, 4, '2025-06-30 04:44:20'),
(10, 16, 2, '2025-06-30 04:44:52'),
(11, 16, 5, '2025-06-30 04:45:48'),
(12, 16, 6, '2025-06-30 04:47:45'),
(13, 14, 2, '2025-06-30 04:54:22'),
(14, 14, 3, '2025-06-30 04:54:24'),
(15, 14, 6, '2025-06-30 04:54:26'),
(16, 14, 4, '2025-06-30 04:54:29');

-- --------------------------------------------------------

--
-- Table structure for table `corporate_clients`
--

CREATE TABLE `corporate_clients` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `payment_terms_days` int(11) DEFAULT 30,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `corporate_clients`
--

INSERT INTO `corporate_clients` (`id`, `company_name`, `contact_person`, `contact_email`, `contact_phone`, `address`, `credit_limit`, `payment_terms_days`, `discount_percentage`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'ABC Corporation', 'Mr. Rahman', 'rahman@abc.com', '01700000005', NULL, 100000.00, 30, 10.00, 1, '2025-10-15 17:05:17', NULL),
(2, 'XYZ Limited', 'Ms. Begum', 'begum@xyz.com', '01700000006', NULL, 50000.00, 45, 5.00, 1, '2025-10-15 17:05:17', NULL),
(3, 'DEF Industries', 'Mr. Hossain', 'hossain@def.com', '01700000007', NULL, 200000.00, 60, 15.00, 1, '2025-10-15 17:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `discount_name` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `applicable_to` enum('all','specific_patient','corporate','insurance') DEFAULT 'all',
  `applicable_patient_id` int(11) DEFAULT NULL,
  `applicable_corporate_id` int(11) DEFAULT NULL,
  `applicable_insurance_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `discount_name`, `discount_type`, `discount_value`, `applicable_to`, `applicable_patient_id`, `applicable_corporate_id`, `applicable_insurance_id`, `start_date`, `end_date`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Senior Citizen Discount', 'percentage', 10.00, 'all', NULL, NULL, NULL, '2024-01-01', '2024-12-31', 1, '2025-10-15 17:05:17', NULL),
(2, 'Student Discount', 'percentage', 15.00, 'all', NULL, NULL, NULL, '2024-01-01', '2024-12-31', 1, '2025-10-15 17:05:17', NULL),
(3, 'Corporate Discount', 'percentage', 20.00, 'corporate', NULL, NULL, NULL, '2024-01-01', '2024-12-31', 1, '2025-10-15 17:05:17', NULL),
(4, 'Insurance Discount', 'percentage', 25.00, 'insurance', NULL, NULL, NULL, '2024-01-01', '2024-12-31', 1, '2025-10-15 17:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `disease_predictions`
--

CREATE TABLE `disease_predictions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `predicted_disease` varchar(100) DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `photo` varchar(255) NOT NULL,
  `available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`user_id`, `specialization`, `license_number`, `photo`, `available`) VALUES
(4, 'Cardiologist', 'L123MS8', 'portrait-medical-doctor-posing-office-16974063-1902546574.jpg', 1),
(15, 'Cardiologist', '', 'b-w-dr-image.jpg', 1),
(17, 'Cardiologist', '1', '68de4b0a3b298.jpg', 1),
(19, 'Skin', '2', '68de4c94373cf.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_hospital`
--

CREATE TABLE `doctor_hospital` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `doctor_hospital`
--

INSERT INTO `doctor_hospital` (`id`, `doctor_id`, `hospital_id`, `created_at`) VALUES
(1, 17, 7, '2025-10-02 09:51:06'),
(2, 19, 7, '2025-10-02 09:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_shares`
--

CREATE TABLE `doctor_shares` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `share_type` enum('consultation','surgery','procedure','commission') NOT NULL,
  `share_percentage` decimal(5,2) DEFAULT 0.00,
  `share_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expertise`
--

CREATE TABLE `expertise` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expertise_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `expertise`
--

INSERT INTO `expertise` (`id`, `user_id`, `expertise_name`) VALUES
(21, 15, 'Pediatric Cardiology'),
(22, 15, 'Echocardiography'),
(23, 15, 'Chronic Disease Management'),
(24, 15, 'Preventive Medicine'),
(25, 19, 'Hypertension, ECG Interpretation');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `user_id` int(11) NOT NULL,
  `hospital_name` varchar(100) NOT NULL,
  `registration_number` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`user_id`, `hospital_name`, `registration_number`, `location`) VALUES
(7, 'United Hospital', 'LM123Q', 'Plot 15, Road 71, Gulshan  Dhaka 1212, Bangladesh');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_settings`
--

CREATE TABLE `hospital_settings` (
  `id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `setting_category` varchar(50) NOT NULL,
  `setting_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`setting_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_settings`
--

INSERT INTO `hospital_settings` (`id`, `hospital_id`, `setting_category`, `setting_data`, `created_at`, `updated_at`) VALUES
(1, 7, 'general', '{\"hospital_name\": \"United Hospital\", \"hospital_address\": \"Plot 15, Road 71, Gulshan Dhaka 1212, Bangladesh\", \"hospital_phone\": \"+880-123-456-789\", \"hospital_email\": \"info@unitedhospital.com\", \"hospital_website\": \"https://unitedhospital.com\", \"operating_hours\": {\"monday\": \"24/7\", \"tuesday\": \"24/7\", \"wednesday\": \"24/7\", \"thursday\": \"24/7\", \"friday\": \"24/7\", \"saturday\": \"24/7\", \"sunday\": \"24/7\"}, \"timezone\": \"Asia/Dhaka\", \"currency\": \"BDT\", \"language\": \"en\", \"theme\": \"light\", \"date_format\": \"Y-m-d\", \"time_format\": \"H:i:s\"}', '2025-10-15 17:05:31', '2025-10-15 17:05:31'),
(2, 7, 'billing', '{\"tax_rate\": 0.00, \"service_charge\": 0.00, \"currency\": \"BDT\", \"auto_billing_enabled\": false}', '2025-10-15 17:05:31', '2025-10-15 17:05:31'),
(3, 7, 'notifications', '{\"email_enabled\": true, \"sms_enabled\": true, \"push_enabled\": true}', '2025-10-15 17:05:31', '2025-10-15 17:05:31'),
(4, 7, 'system', '{\"maintenance_mode\": false, \"backup_frequency\": \"daily\", \"audit_log_enabled\": true}', '2025-10-15 17:05:31', '2025-10-15 17:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_companies`
--

CREATE TABLE `insurance_companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `policy_prefix` varchar(50) DEFAULT NULL,
  `co_payment_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_companies`
--

INSERT INTO `insurance_companies` (`id`, `company_name`, `contact_person`, `contact_email`, `contact_phone`, `address`, `policy_prefix`, `co_payment_percentage`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Green Delta Insurance', 'Mr. Ahmed', 'ahmed@greendelta.com', '01700000001', NULL, NULL, 20.00, 1, '2025-10-15 17:05:17', NULL),
(2, 'Pragati Insurance', 'Ms. Fatima', 'fatima@pragati.com', '01700000002', NULL, NULL, 15.00, 1, '2025-10-15 17:05:17', NULL),
(3, 'Reliance Insurance', 'Mr. Karim', 'karim@reliance.com', '01700000003', NULL, NULL, 25.00, 1, '2025-10-15 17:05:17', NULL),
(4, 'MetLife Insurance', 'Ms. Rina', 'rina@metlife.com', '01700000004', NULL, NULL, 10.00, 1, '2025-10-15 17:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `hospital_id`, `item_name`, `item_description`, `category`, `unit`, `created_at`, `updated_at`) VALUES
(41, 7, 'Paracetamol 500mg', 'Pain reliever tablets', 'Medicine', 'Box', '2025-07-01 19:55:03', '2025-07-01 19:55:03'),
(42, 7, 'Surgical Gloves', 'Disposable latex gloves', 'Consumables', 'Box', '2025-07-01 19:56:20', '2025-07-01 19:56:20'),
(44, 7, 'Oxygen Cylinder', 'Medical oxygen supply', 'Equipment', 'Cylinder', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(45, 7, 'Hand Sanitizer', 'Alcohol-based sanitizer', 'Consumables', 'Bottle', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(46, 7, 'Syringe 5ml', 'Sterile disposable syringe', 'Consumables', 'Pack', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(47, 7, 'Bandage Roll', 'Cotton bandage', 'Consumables', 'Roll', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(48, 7, 'Digital Thermometer', 'For measuring temperature', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(49, 7, 'Amoxicillin 250mg', 'Antibiotic capsules', 'Medicine', 'Box', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(50, 7, 'Face Mask', '3-ply surgical mask', 'Consumables', 'Box', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(51, 7, 'Stethoscope', 'Medical stethoscope', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(52, 7, 'Blood Pressure Monitor', 'Digital BP monitor', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(54, 7, 'Saline Solution 500ml', 'IV saline solution', 'Medicine', 'Bottle', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(55, 7, 'Cotton Swabs', 'Sterile cotton swabs', 'Consumables', 'Pack', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(56, 7, 'Antiseptic Solution', 'For wound cleaning', 'Medicine', 'Bottle', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(57, 7, 'Wheelchair', 'Standard hospital wheelchair', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(58, 7, 'Pulse Oximeter', 'Measures blood oxygen', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(59, 7, 'Thermal Scanner', 'Infrared thermometer', 'Equipment', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53'),
(60, 7, 'Disposable Gown', 'Protective medical gown', 'Consumables', 'Piece', '2025-07-01 19:59:53', '2025-07-01 19:59:53');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock`
--

CREATE TABLE `inventory_stock` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_stock`
--

INSERT INTO `inventory_stock` (`id`, `item_id`, `quantity`, `last_updated`) VALUES
(1, 41, 5, '2025-07-01 19:55:26'),
(2, 42, 100, '2025-07-01 19:57:46'),
(4, 44, 200, '2025-07-01 20:02:23'),
(5, 45, 50, '2025-07-01 20:02:23'),
(6, 46, 10, '2025-07-01 20:02:23'),
(7, 47, 150, '2025-07-01 20:02:23'),
(8, 48, 300, '2025-07-01 20:02:23'),
(9, 49, 120, '2025-07-01 20:02:23'),
(10, 50, 40, '2025-07-01 20:02:23'),
(11, 51, 80, '2025-07-01 20:02:23'),
(12, 52, 500, '2025-07-01 20:02:23');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `item_id`, `hospital_id`, `transaction_type`, `quantity`, `transaction_date`, `remarks`) VALUES
(1, 41, 7, 'in', 5, '2025-07-01 19:55:26', 'Checked'),
(2, 42, 7, 'in', 100, '2025-07-01 19:57:46', ''),
(3, 54, 7, 'in', 10, '2025-07-01 20:14:02', '');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reports`
--

CREATE TABLE `lab_reports` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `report_file` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

<<<<<<< HEAD
--
-- Dumping data for table `lab_reports`
--

INSERT INTO `lab_reports` (`id`, `patient_id`, `test_name`, `report_file`, `uploaded_by`, `uploaded_at`, `report_date`) VALUES
(1, 16, 'blood count', 'uploads/reports/Assignment_3__1__pid16_20251008205617.pdf', 7, '2025-10-08 18:56:17', '2025-10-08'),
(2, 14, 'Heart test', 'uploads/reports/printLearner_pid14_20251008205735.pdf', 7, '2025-10-08 18:57:35', '2025-10-08'),
(3, 14, 'Brain test', 'uploads/reports/Assignment_3__1__pid14_20251008211132.pdf', 7, '2025-10-08 19:11:32', '2025-10-01');

=======
>>>>>>> 0f21c67daa7df8c7f8eff8de54b77d455440befc
-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `meal_time` enum('Before Meal','After Meal') NOT NULL,
  `begin_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication`
--

INSERT INTO `medication` (`id`, `user_id`, `medicine_name`, `meal_time`, `begin_date`, `end_date`, `created_at`) VALUES
(1, 14, 'Napa', 'After Meal', '2025-06-24', '2025-07-24', '2025-06-23 20:03:46'),
(5, 14, 'Entacyd', 'Before Meal', '2025-06-24', '2025-07-24', '2025-06-23 20:39:41'),
(6, 14, 'Maxpro', 'Before Meal', '2025-06-24', '2025-08-12', '2025-06-23 20:46:35'),
(7, 16, 'Napa', 'Before Meal', '2025-06-24', '2025-07-24', '2025-06-24 17:15:36'),
(8, 7, 'Entacyd', 'Before Meal', '2025-06-25', '2025-06-25', '2025-06-25 07:23:56'),
(9, 16, 'Pantonix', 'Before Meal', '2025-06-29', '2025-07-29', '2025-06-29 15:56:18');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(255) DEFAULT NULL,
  `meal_time` enum('Before Meal','After Meal') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medication_reminders_sent`
--

CREATE TABLE `medication_reminders_sent` (
  `id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `dose_time` time NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_reminders_sent`
--

INSERT INTO `medication_reminders_sent` (`id`, `medication_id`, `dose_time`, `sent_at`) VALUES
(1, 7, '21:26:00', '2025-06-29 15:33:35'),
(2, 7, '21:52:00', '2025-06-29 15:52:05'),
(4, 9, '10:26:00', '2025-06-30 04:26:50'),
(5, 9, '10:26:00', '2025-06-30 04:26:53'),
(6, 9, '10:49:00', '2025-06-30 04:49:51'),
(7, 9, '13:12:00', '2025-06-30 07:12:25'),
(3, 9, '21:57:00', '2025-06-29 15:57:10');

-- --------------------------------------------------------

--
-- Table structure for table `medication_times`
--

CREATE TABLE `medication_times` (
  `id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `dose_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_times`
--

INSERT INTO `medication_times` (`id`, `medication_id`, `dose_time`) VALUES
(1, 5, '10:00:00'),
(2, 5, '22:00:00'),
(3, 6, '09:00:00'),
(4, 6, '14:30:00'),
(5, 6, '00:09:00'),
(6, 7, '21:52:00'),
(7, 8, '13:23:00'),
(8, 9, '13:12:00');

-- --------------------------------------------------------

--
-- Table structure for table `meeting_code`
--

CREATE TABLE `meeting_code` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `meeting_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meeting_code`
--

INSERT INTO `meeting_code` (`id`, `patient_id`, `doctor_id`, `meeting_code`) VALUES
(1, 14, 15, '3911'),
(2, 14, 14, '6578');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_description` text DEFAULT NULL,
  `package_type` enum('maternity','surgery','cardiology','orthopedic','general','custom') NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `includes` text DEFAULT NULL,
  `exclusions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `package_name`, `package_description`, `package_type`, `total_price`, `duration_days`, `includes`, `exclusions`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Maternity Package', 'Complete maternity care including delivery', 'maternity', 50000.00, 3, 'Delivery, Room, Nursing, Medicine', 'Complications, ICU', 1, '2025-10-15 17:05:17', NULL),
(2, 'Heart Surgery Package', 'Complete heart surgery package', 'cardiology', 200000.00, 7, 'Surgery, ICU, Medicine, Follow-up', 'Pre-existing conditions', 1, '2025-10-15 17:05:17', NULL),
(3, 'General Surgery Package', 'General surgery with room and care', 'surgery', 75000.00, 5, 'Surgery, Room, Nursing, Medicine', 'Post-surgery complications', 1, '2025-10-15 17:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `item_type` enum('service','medicine','test','room','doctor_fee','nursing','surgery','other') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `user_id` int(11) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`user_id`, `gender`, `date_of_birth`, `address`) VALUES
(3, 'male', '2002-10-16', 'Sayednagar, B block Society.'),
(5, 'male', '1999-05-01', 'Mohammadpur'),
(6, 'male', '2000-12-25', 'Gulshan'),
(8, 'male', '2005-01-01', 'Motijheel'),
(9, 'male', '2002-10-16', 'Banglamotore'),
(10, 'male', '2002-02-22', 'notunbazar'),
(13, 'male', '2002-02-22', 'notunbazar'),
(14, 'female', '2001-04-03', 'Mirpur 2'),
(16, 'male', '2001-10-16', 'Sayednagar');

-- --------------------------------------------------------

--
-- Table structure for table `patient_ledger`
--

CREATE TABLE `patient_ledger` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `transaction_type` enum('charge','payment','refund','discount') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_ledger`
--

INSERT INTO `patient_ledger` (`id`, `patient_id`, `transaction_type`, `amount`, `description`, `reference_id`, `reference_type`, `created_at`, `created_by`) VALUES
(1, 16, 'charge', 2400.00, 'Bill created', 1, 'bill', '2025-10-15 17:13:12', 7),
(2, 16, 'payment', 2400.00, 'Payment received', 1, 'payment', '2025-10-15 17:13:26', 7);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `payment_method` enum('cash','card','mobile_banking','bank_transfer','cheque','insurance','corporate') NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `transaction_id` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `mobile_banking_provider` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `bill_id`, `patient_id`, `payment_method`, `payment_amount`, `payment_reference`, `payment_date`, `payment_status`, `transaction_id`, `bank_name`, `cheque_number`, `cheque_date`, `mobile_banking_provider`, `mobile_number`, `created_by`, `notes`) VALUES
(1, 1, 16, 'cash', 2400.00, 'dsd', '2025-10-15 17:13:26', 'completed', '', '', NULL, NULL, '', '', 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `caption` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `post_creator` int(11) NOT NULL,
  `community_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `caption`, `photo`, `created_at`, `post_creator`, `community_id`) VALUES
(1, '🌿 You\'re Not Alone – Let\'s Talk Mental Health 🌿\r\n\r\nHey everyone 💚\r\n\r\nLife can feel overwhelming sometimes, and it\'s okay to not be okay. Whether you\'re dealing with anxiety, stress, loneliness, or just need someone to talk to, this community is here for you.\r\n\r\n💬 Share your thoughts, ask for support, or simply say how you\'re feeling today.\r\n🤝 No judgment, just understanding.\r\n🧠 Let’s build a space where mental health matters, and every voice is heard.\r\n\r\nYou are valued. You are strong. And we\'re in this together. 💪✨\r\n\r\n#MentalHealthMatters #YouAreNotAlone #SupportAndStrength', NULL, '2025-05-06 19:48:09', 14, 1),
(2, 'Motivation Monday\r\n\r\n“Healing is not linear.”\r\nNo matter where you are in your journey, keep going.\r\nYou’re doing better than you think. 💪 Let’s lift each other up this week! 💚', NULL, '2025-06-18 06:07:36', 14, 1),
(3, 'What helps you when you’re feeling anxious, stressed, or low?\r\nLet’s share our coping strategies — music, journaling, breathing exercises, or even memes.\r\nYou never know who might need your idea today. 💡🧠', NULL, '2025-06-18 06:08:35', 14, 1),
(4, 'Good Morning', NULL, '2025-06-18 06:44:23', 14, 1);

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(3, 1, 10, '2025-05-08 17:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `pricing`
--

CREATE TABLE `pricing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` enum('Standard','Second Visit','Report Checkup') NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pricing`
--

INSERT INTO `pricing` (`id`, `user_id`, `service_type`, `price`) VALUES
(4, 15, 'Standard', 2000.00),
(5, 15, 'Second Visit', 1000.00),
(6, 15, 'Report Checkup', 500.00),
(13, 19, 'Standard', 1500.00),
(14, 19, 'Second Visit', 1000.00),
(15, 19, 'Report Checkup', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `qualifications`
--

CREATE TABLE `qualifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `institute` varchar(255) NOT NULL,
  `year_obtained` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `qualifications`
--

INSERT INTO `qualifications` (`id`, `user_id`, `qualification`, `institute`, `year_obtained`) VALUES
(16, 15, 'MBBS', 'Dhaka Medical College', '2015'),
(17, 15, 'MD (Cardiology)', 'National Heart Institute', '2019'),
(18, 15, 'MPH', 'BRAC University', '2021'),
(19, 19, 'MBBS, MD', 'Dhaka Medical College', '2015');

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` text NOT NULL,
  `refund_method` enum('cash','card','bank_transfer','mobile_banking') NOT NULL,
  `refund_reference` varchar(255) DEFAULT NULL,
  `refund_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `refund_status` enum('pending','approved','processed','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_predictions`
--

CREATE TABLE `risk_predictions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `predicted_risk` varchar(100) DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` enum('patient','doctor','hospital','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'patient'),
(2, 'doctor'),
(3, 'hospital'),
(4, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `service_charges`
--

CREATE TABLE `service_charges` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_type` enum('consultation','surgery','procedure','test','medicine','room','nursing','other') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'per_service',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_charges`
--

INSERT INTO `service_charges` (`id`, `service_name`, `service_type`, `department`, `base_price`, `unit`, `is_active`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'General Consultation', 'consultation', 'General Medicine', 500.00, 'per_visit', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(2, 'Cardiology Consultation', 'consultation', 'Cardiology', 800.00, 'per_visit', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(3, 'Surgery Consultation', 'consultation', 'Surgery', 1000.00, 'per_visit', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(4, 'Emergency Consultation', 'consultation', 'Emergency', 1200.00, 'per_visit', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(5, 'CBC Test', 'test', 'Laboratory', 300.00, 'per_test', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(6, 'X-Ray Chest', 'test', 'Radiology', 400.00, 'per_test', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(7, 'CT Scan', 'test', 'Radiology', 2000.00, 'per_test', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(8, 'MRI', 'test', 'Radiology', 5000.00, 'per_test', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(9, 'General Ward Bed', 'room', 'General Ward', 2000.00, 'per_day', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(10, 'Private Room', 'room', 'Private Ward', 5000.00, 'per_day', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(11, 'ICU Bed', 'room', 'ICU', 8000.00, 'per_day', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(12, 'Nursing Care', 'nursing', 'Nursing', 500.00, 'per_day', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(13, 'Surgery - Appendectomy', 'surgery', 'Surgery', 15000.00, 'per_surgery', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL),
(14, 'Surgery - Gallbladder', 'surgery', 'Surgery', 25000.00, 'per_surgery', 1, '2025-10-15 17:05:17', '2025-10-15 17:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_tariffs`
--

CREATE TABLE `service_tariffs` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price_group` enum('general','vip','corporate') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_tariffs`
--

INSERT INTO `service_tariffs` (`id`, `service_id`, `price_group`, `price`, `updated_at`) VALUES
(1, 1, 'general', 500.00, '2025-10-15 17:05:17'),
(2, 2, 'general', 800.00, '2025-10-15 17:05:17'),
(3, 3, 'general', 1000.00, '2025-10-15 17:05:17'),
(4, 4, 'general', 1200.00, '2025-10-15 17:05:17'),
(5, 5, 'general', 300.00, '2025-10-15 17:05:17'),
(6, 6, 'general', 400.00, '2025-10-15 17:05:17'),
(7, 7, 'general', 2000.00, '2025-10-15 17:05:17'),
(8, 8, 'general', 5000.00, '2025-10-15 17:05:17'),
(9, 9, 'general', 2000.00, '2025-10-15 17:05:17'),
(10, 10, 'general', 5000.00, '2025-10-15 17:05:17'),
(11, 11, 'general', 8000.00, '2025-10-15 17:05:17'),
(12, 12, 'general', 500.00, '2025-10-15 17:05:17'),
(13, 13, 'general', 15000.00, '2025-10-15 17:05:17'),
(14, 14, 'general', 25000.00, '2025-10-15 17:05:17'),
(16, 1, 'vip', 575.00, '2025-10-15 17:05:17'),
(17, 2, 'vip', 920.00, '2025-10-15 17:05:17'),
(18, 3, 'vip', 1150.00, '2025-10-15 17:05:17'),
(19, 4, 'vip', 1380.00, '2025-10-15 17:05:17'),
(20, 5, 'vip', 345.00, '2025-10-15 17:05:17'),
(21, 6, 'vip', 460.00, '2025-10-15 17:05:17'),
(22, 7, 'vip', 2300.00, '2025-10-15 17:05:17'),
(23, 8, 'vip', 5750.00, '2025-10-15 17:05:17'),
(24, 9, 'vip', 2300.00, '2025-10-15 17:05:17'),
(25, 10, 'vip', 5750.00, '2025-10-15 17:05:17'),
(26, 11, 'vip', 9200.00, '2025-10-15 17:05:17'),
(27, 12, 'vip', 575.00, '2025-10-15 17:05:17'),
(28, 13, 'vip', 17250.00, '2025-10-15 17:05:17'),
(29, 14, 'vip', 28750.00, '2025-10-15 17:05:17'),
(31, 1, 'corporate', 450.00, '2025-10-15 17:05:17'),
(32, 2, 'corporate', 720.00, '2025-10-15 17:05:17'),
(33, 3, 'corporate', 900.00, '2025-10-15 17:05:17'),
(34, 4, 'corporate', 1080.00, '2025-10-15 17:05:17'),
(35, 5, 'corporate', 270.00, '2025-10-15 17:05:17'),
(36, 6, 'corporate', 360.00, '2025-10-15 17:05:17'),
(37, 7, 'corporate', 1800.00, '2025-10-15 17:05:17'),
(38, 8, 'corporate', 4500.00, '2025-10-15 17:05:17'),
(39, 9, 'corporate', 1800.00, '2025-10-15 17:05:17'),
(40, 10, 'corporate', 4500.00, '2025-10-15 17:05:17'),
(41, 11, 'corporate', 7200.00, '2025-10-15 17:05:17'),
(42, 12, 'corporate', 450.00, '2025-10-15 17:05:17'),
(43, 13, 'corporate', 13500.00, '2025-10-15 17:05:17'),
(44, 14, 'corporate', 22500.00, '2025-10-15 17:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'MediAI', 'Website name', '2025-10-15 17:04:46'),
(2, 'maintenance_mode', '0', 'Enable/disable maintenance mode', '2025-10-15 17:04:46'),
(3, 'registration_enabled', '1', 'Allow new user registrations', '2025-10-15 17:04:46');

-- --------------------------------------------------------

--
-- Table structure for table `time_for_meeting`
--

CREATE TABLE `time_for_meeting` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `meeting_time` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_for_meeting`
--

INSERT INTO `time_for_meeting` (`id`, `patient_id`, `doctor_id`, `meeting_time`) VALUES
(1, 14, 15, 'we have a meeting at 3 PM'),
(2, 14, 15, '10:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `otp` int(11) NOT NULL,
  `status` enum('authorized','unauthorized') NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unblocked, 1=blocked by admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role_id`, `created_at`, `otp`, `status`, `is_blocked`) VALUES
(3, 'Mubasshir Ahmed', 'marnab222263@bscse.uiu.ac.bd', '12345', '01402038323', 3, '2025-04-25 21:44:38', 0, '', 0),
(4, 'John Doe', 'john@gmail.com', '12345', '01345678900', 2, '2025-04-25 21:47:06', 0, 'authorized', 0),
(5, 'James Anderson', 'james@gmail.com', 'james123', '01234567123', 3, '2025-04-25 22:11:51', 0, 'authorized', 0),
(6, 'Tom David', 'tomdavid12@gmail.com', '$2y$10$/qX5nsACrHLShjAFVh/IHup6ZJgnNuv49/lH1NqnebkVg5QSMl2G6', '01234567899', 3, '2025-04-28 13:27:34', 0, 'authorized', 0),
(7, 'United Hospital', 'unitedmedical56@gmail.com', '$2y$10$OO5KGe.m6J5r0W4R7LNWseDUrp8Y9xvx6SMOF5tk9MkLhUO.nRQO2', '01914001214', 3, '2025-04-28 13:39:58', 0, '', 0),
(8, 'Abu Affan', 'aaffan222290@bscse.uiu.ac.bd', '$2y$10$zSu/R8/0McQ8qALNZJ0Vn.IPILvlA51QNJFP/pBlgD4U4uP01y0Iy', '01796651373', 1, '2025-04-28 13:46:31', 0, '', 0),
(9, 'Mahdee Arnab', 'arnab0574@gmail.com', '$2y$10$i3GH8Ur.a2yJqqh6TMonO.7g.jP2s2zSORdso2FCfMabwh/xxgDNO', '01751423255', 1, '2025-04-28 16:34:00', 0, 'authorized', 0),
(10, 'Nuhan', 'abuaffan@gmail.com', '$2y$10$VDfPdH8icXAphBDGyP9s2esV8wS340R7nyVFV33nXQVeRbgJBM4cq', '01796651300', 2, '2025-04-28 16:58:40', 662856, 'authorized', 0),
(13, 'Nuhan', 'abuaffan1123@gmail.com', '$2y$10$3.TGFwA8e.B5lLKpxrftv.pRKQZwfvQQWiFNHutWL13xlBFqK8KUe', '01796651373', 1, '2025-04-28 17:11:51', 473792, 'authorized', 0),
(14, 'Shahin Chowdhury', 'schowdhury222152@bscse.uiu.ac.bd', '$2y$10$vPtPxs8BvW4JJxCWGgJJEeHruuG/m6Gtb2hYr9jdaSrE7sucWlEVa', '01319312217', 1, '2025-04-29 17:28:05', 141920, 'authorized', 0),
(15, 'Abu Affan', 'maffan222290@bscse.uiu.ac.bd', '$2y$10$WsTvFcuAJsqa8Q12vUJ4Xuh6CuSiqnVmAVqqzMTeeAIZVJcsUhTWK', '01796651373', 2, '2025-05-08 18:52:46', 534711, 'authorized', 0),
(16, 'Mahdee Arnab', 'mubasshirahmed263@gmail.com', '$2y$10$whzhyLxgabPfVKC/.2gaUuRGIWmVSlvjzajHZRCCNyxTKgU3k3VBC', '01751423255', 1, '2025-06-23 09:12:02', 415621, 'authorized', 0),
(17, 'John Smith', 'jsmith4250@mediai.com', '$2y$10$enzdtXnFsN9GDDXovFmA7ONRjo60K89QGACVfpN//b/vlKQtbnuYW', '01700000000', 2, '2025-10-02 09:51:06', 0, 'authorized', 0),
(19, 'Nurul Huda', 'nhuda2137@mediai.com', '$2y$10$StfKW8uY5zOAARdrJj0JsuCXcSFsOEZdPCiq/HMCZiUBNlpn14XCu', '01811111111', 2, '2025-10-02 09:57:40', 0, 'authorized', 0),
(999, 'System Admin', 'admin@mediai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000000', 4, '2025-10-15 17:04:55', 0, 'authorized', 0);

-- --------------------------------------------------------

--
-- Table structure for table `video_consultations`
--

CREATE TABLE `video_consultations` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_meeting`
--

CREATE TABLE `video_meeting` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `meeting_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `fk_hospital` (`hospital_id`);

--
-- Indexes for table `available_hours`
--
ALTER TABLE `available_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_available_hours_hospital` (`hospital_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `fk_bills_created_by` (`created_by`),
  ADD KEY `fk_bills_updated_by` (`updated_by`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bill_items` (`bill_id`,`item_type`),
  ADD KEY `idx_service_date` (`service_date`);

--
-- Indexes for table `cabins`
--
ALTER TABLE `cabins`
  ADD PRIMARY KEY (`cabin_id`),
  ADD UNIQUE KEY `cabin_number` (`cabin_number`);

--
-- Indexes for table `cabin_bookings`
--
ALTER TABLE `cabin_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_cabin_dates` (`cabin_id`,`check_in`,`check_out`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `cabin_bookings_legacy`
--
ALTER TABLE `cabin_bookings_legacy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `chatbot_queries`
--
ALTER TABLE `chatbot_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_conversation_id` (`conversation_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `commentor` (`commentor`);

--
-- Indexes for table `community`
--
ALTER TABLE `community`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_creator` (`community_creator`);

--
-- Indexes for table `community_members`
--
ALTER TABLE `community_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`community_id`),
  ADD KEY `community_id` (`community_id`);

--
-- Indexes for table `corporate_clients`
--
ALTER TABLE `corporate_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_corporate_active` (`is_active`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicable_patient_id` (`applicable_patient_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_discounts_active` (`is_active`,`start_date`,`end_date`),
  ADD KEY `idx_discounts_applicable` (`applicable_to`);

--
-- Indexes for table `disease_predictions`
--
ALTER TABLE `disease_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `doctor_hospital`
--
ALTER TABLE `doctor_hospital`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `doctor_shares`
--
ALTER TABLE `doctor_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_shares` (`doctor_id`,`payment_status`),
  ADD KEY `idx_bill_shares` (`bill_id`);

--
-- Indexes for table `expertise`
--
ALTER TABLE `expertise`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`);

--
-- Indexes for table `hospital_settings`
--
ALTER TABLE `hospital_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hospital_category` (`hospital_id`,`setting_category`);

--
-- Indexes for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_insurance_active` (`is_active`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `lab_reports`
--
ALTER TABLE `lab_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`);

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medication_reminders_sent`
--
ALTER TABLE `medication_reminders_sent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medication_time` (`medication_id`,`dose_time`,`sent_at`);

--
-- Indexes for table `medication_times`
--
ALTER TABLE `medication_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medication_id` (`medication_id`);

--
-- Indexes for table `meeting_code`
--
ALTER TABLE `meeting_code`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_packages_active` (`is_active`),
  ADD KEY `idx_packages_type` (`package_type`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_items` (`package_id`,`item_type`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `patient_ledger`
--
ALTER TABLE `patient_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_patient_ledger` (`patient_id`,`created_at`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_payments_bill` (`bill_id`),
  ADD KEY `idx_payments_patient` (`patient_id`),
  ADD KEY `idx_payments_method` (`payment_method`),
  ADD KEY `idx_payments_date` (`payment_date`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_creator` (`post_creator`),
  ADD KEY `community_id` (`community_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pricing`
--
ALTER TABLE `pricing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_per_doctor` (`user_id`,`service_type`);

--
-- Indexes for table `qualifications`
--
ALTER TABLE `qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_refunds_bill` (`bill_id`),
  ADD KEY `idx_refunds_patient` (`patient_id`),
  ADD KEY `idx_refunds_status` (`refund_status`);

--
-- Indexes for table `risk_predictions`
--
ALTER TABLE `risk_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_charges`
--
ALTER TABLE `service_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_service_charges` (`service_type`,`is_active`),
  ADD KEY `idx_service_department` (`department`);

--
-- Indexes for table `service_tariffs`
--
ALTER TABLE `service_tariffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_service_group` (`service_id`,`price_group`),
  ADD KEY `idx_tariff_group` (`price_group`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `time_for_meeting`
--
ALTER TABLE `time_for_meeting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tfm_patient` (`patient_id`),
  ADD KEY `fk_tfm_doctor` (`doctor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `video_consultations`
--
ALTER TABLE `video_consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `video_meeting`
--
ALTER TABLE `video_meeting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vm_patient` (`patient_id`),
  ADD KEY `fk_vm_doctor` (`doctor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `available_hours`
--
ALTER TABLE `available_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cabins`
--
ALTER TABLE `cabins`
  MODIFY `cabin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cabin_bookings`
--
ALTER TABLE `cabin_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cabin_bookings_legacy`
--
ALTER TABLE `cabin_bookings_legacy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_queries`
--
ALTER TABLE `chatbot_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `community`
--
ALTER TABLE `community`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `community_members`
--
ALTER TABLE `community_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `corporate_clients`
--
ALTER TABLE `corporate_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `disease_predictions`
--
ALTER TABLE `disease_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_hospital`
--
ALTER TABLE `doctor_hospital`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_shares`
--
ALTER TABLE `doctor_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expertise`
--
ALTER TABLE `expertise`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hospital_settings`
--
ALTER TABLE `hospital_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_reports`
--
ALTER TABLE `lab_reports`
<<<<<<< HEAD
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
=======
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
>>>>>>> 0f21c67daa7df8c7f8eff8de54b77d455440befc

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medication_reminders_sent`
--
ALTER TABLE `medication_reminders_sent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medication_times`
--
ALTER TABLE `medication_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `meeting_code`
--
ALTER TABLE `meeting_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_ledger`
--
ALTER TABLE `patient_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pricing`
--
ALTER TABLE `pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `qualifications`
--
ALTER TABLE `qualifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_predictions`
--
ALTER TABLE `risk_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_charges`
--
ALTER TABLE `service_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `service_tariffs`
--
ALTER TABLE `service_tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_for_meeting`
--
ALTER TABLE `time_for_meeting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT for table `video_consultations`
--
ALTER TABLE `video_consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_meeting`
--
ALTER TABLE `video_meeting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD CONSTRAINT `ai_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`user_id`);

--
-- Constraints for table `available_hours`
--
ALTER TABLE `available_hours`
  ADD CONSTRAINT `available_hours_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_available_hours_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_bills_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bills_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabin_bookings`
--
ALTER TABLE `cabin_bookings`
  ADD CONSTRAINT `fk_cb_cabin` FOREIGN KEY (`cabin_id`) REFERENCES `cabins` (`cabin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabin_bookings_legacy`
--
ALTER TABLE `cabin_bookings_legacy`
  ADD CONSTRAINT `cabin_bookings_legacy_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `chatbot_queries`
--
ALTER TABLE `chatbot_queries`
  ADD CONSTRAINT `chatbot_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `chatbot_queries_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`commentor`) REFERENCES `users` (`id`);

--
-- Constraints for table `community`
--
ALTER TABLE `community`
  ADD CONSTRAINT `community_ibfk_1` FOREIGN KEY (`community_creator`) REFERENCES `users` (`id`);

--
-- Constraints for table `community_members`
--
ALTER TABLE `community_members`
  ADD CONSTRAINT `community_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `community_members_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`);

--
-- Constraints for table `corporate_clients`
--
ALTER TABLE `corporate_clients`
  ADD CONSTRAINT `corporate_clients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`applicable_patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disease_predictions`
--
ALTER TABLE `disease_predictions`
  ADD CONSTRAINT `disease_predictions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctor_hospital`
--
ALTER TABLE `doctor_hospital`
  ADD CONSTRAINT `doctor_hospital_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_hospital_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_shares`
--
ALTER TABLE `doctor_shares`
  ADD CONSTRAINT `doctor_shares_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_shares_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expertise`
--
ALTER TABLE `expertise`
  ADD CONSTRAINT `expertise_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD CONSTRAINT `hospitals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  ADD CONSTRAINT `insurance_companies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `inventory_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medication`
--
ALTER TABLE `medication`
  ADD CONSTRAINT `medication_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medication_reminders_sent`
--
ALTER TABLE `medication_reminders_sent`
  ADD CONSTRAINT `medication_reminders_sent_ibfk_1` FOREIGN KEY (`medication_id`) REFERENCES `medication` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medication_times`
--
ALTER TABLE `medication_times`
  ADD CONSTRAINT `medication_times_ibfk_1` FOREIGN KEY (`medication_id`) REFERENCES `medication` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `package_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `patient_ledger`
--
ALTER TABLE `patient_ledger`
  ADD CONSTRAINT `patient_ledger_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_ledger_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`post_creator`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`);

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pricing`
--
ALTER TABLE `pricing`
  ADD CONSTRAINT `pricing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `qualifications`
--
ALTER TABLE `qualifications`
  ADD CONSTRAINT `qualifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `refunds_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `risk_predictions`
--
ALTER TABLE `risk_predictions`
  ADD CONSTRAINT `risk_predictions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `service_charges`
--
ALTER TABLE `service_charges`
  ADD CONSTRAINT `service_charges_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_tariffs`
--
ALTER TABLE `service_tariffs`
  ADD CONSTRAINT `service_tariffs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `service_charges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_for_meeting`
--
ALTER TABLE `time_for_meeting`
  ADD CONSTRAINT `fk_tfm_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfm_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `video_consultations`
--
ALTER TABLE `video_consultations`
  ADD CONSTRAINT `video_consultations_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);

--
-- Constraints for table `video_meeting`
--
ALTER TABLE `video_meeting`
  ADD CONSTRAINT `fk_vm_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vm_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
