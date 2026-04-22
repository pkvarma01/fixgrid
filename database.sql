-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 22, 2026 at 10:44 PM
-- Server version: 8.0.46
-- PHP Version: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fixgridin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `device_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`, `device_token`) VALUES
(1, 'Super Admin', 'admin@fixgrid.in', '$2y$12$QS5ssG8YYik5W4hr5jwGJu40fgP4GXTdOQvEUFXdZyPwq3.TOC5eS', 'super_admin', 1, '2026-03-18 08:22:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('assign_radius_km', '20', '2026-04-05 15:06:57'),
('company_email', 'support@fixgrid.in', '2026-04-05 15:06:57'),
('company_gstin', '', '2026-04-05 15:06:57'),
('company_name', 'FixGrid', '2026-04-05 15:06:57'),
('company_pan', '', '2026-04-05 15:06:57'),
('company_phone', '+917982733201', '2026-04-05 15:06:57'),
('currency', 'INR', '2026-04-05 15:06:57'),
('currency_symbol', '₹', '2026-04-05 15:06:57'),
('fcm_app_id', '', '2026-03-22 16:01:26'),
('fcm_enabled', '1', '2026-04-05 15:06:57'),
('fcm_messaging_sender_id', '', '2026-03-22 16:01:26'),
('fcm_project_id', 'hridya-tech', '2026-03-22 16:01:26'),
('fcm_server_key', '', '2026-03-18 18:47:33'),
('fcm_service_account_json', '{\n  \"type\": \"service_account\",\n  \"project_id\": \"hridya-tech\",\n  \"private_key_id\": \"75a2e61162516951d5ea2342c817c66d4dbe28ec\",\n  \"private_key\": \"-----BEGIN PRIVATE KEY-----\\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDgfNxGXA9mLQB2\\nnsyXJwNX1bpUmXwgwPspHtSYMQ1iCRCPAw10zRMJnGOouuxObDt1sktcB+B9crXY\\nUkT5tiPBQ9zS4Khe3/QKCRsDgRRhMM0mkDsQkQmgkYXu560b3f5YDl/MQ2ow2h/d\\nRm6aiqhWDQVtMWSuUqf3J1PaBOVWHlqZHacf2a9JRSLG8qEf5jbezwRWAfHNICTo\\nnWIEpOossm7mux3wC9SMnH9ePErgO/wyS9HUVUQ1TIZx2Vbq560Uu7pSIziBixxd\\nDGK322F2r7XOI/ZUpcIvMkhImh4SHealWD7Pioy2CI5mt2izG4tlijgnFgFRyPdT\\n5QXpeuVNAgMBAAECggEAAQYiKyvngJlljwkhOPJ/acaQ3NG1Rt5GTfc2XI8YBkBB\\n2w6IWwc69jIb4PXHv1T1W1fXAYGcpfD4Pyfl9G2MwlUHJq3KDGJ6NzU9/Wkq0Teh\\nT+1ZZ/gib8f/BI08zlExBDj7WFFI7OlLL98MAO7C2WqAyfxY+V8FGqMssu+PFeR9\\nUjvZZyEPMlcLtXDbu+zdnouuo8onnZZx+fna+pKju+5T0Bn0v1q6byw2Ej9VCuDA\\nqKWyvXfJ/6maukmZu1wsxVOBpwYKovh21h9e4VRLPFhJ5+vQWDgNe1T60hYn3ziu\\nBGgUVramuiPk6T0H1GiaFo9wR1yfybcvJu45wisdvwKBgQD0s5VZ9rFg052dTpd+\\nzD8yTeRShyiryE9E/Zf7RCloGJCvyRDdKmOEPtgZcIRV6uYCnOR3FW1VCMHw7bWg\\nxXZivIenJvG7LBiBFNO0hp+99AgBJRBXaFR4p23lEazlL0WyqoUasRg3nAzgYxdt\\n5mDBq4SNO/buNzahw2scFZBdOwKBgQDq2li/rDvRMtq7Uk01NBE51I3cdvLNydw2\\nl0vZU1Ldid4p1SoJvEudhKL+06nIgfAljJvWtAcEB3Fb89HuB89Xng7vGc5/Pa5F\\ncKqAWqL7HTDrmu0BXANtZF9MPY5LFj0Rx/4VuCMYH7ANLnmW3F7Xn/ROP0v2PGwY\\nz/1o4qM/FwKBgQDduFKdX2+9YkEwJeQTGhIxDG7TcHAjpq5cvsMVAt/Z2FhRTwge\\nvafKTf8UneTqzJp4x5wjYqDcKcFBAsJN2F26fUUU6a6igAP8AdkJe+oF/bW7A9BY\\nwsm3QU3L/0X/q+OlTeipFMnyqUzpjB/QjqwhD75dEnZdxl2UGTNWERvrvQKBgE3l\\nF/STAtATRvtEg/k2iNQejOLnzQa2gkTVD8JIYb67IKXNXwEVpBrdyFYvDCxF9Mnv\\nhizDT2Rlt5KhHysNChdshFWHwLbxVWEQymyCI3aYqwYqP5vo0fYxiW43KH/9I0pH\\nhB3eEj3JHNJybL+93fM0yYo+ckp8pfng2pM0QKMHAoGBAIR2+I0K1T6duBg3Ach6\\n92ADlVzLxvFyi8oJ0ZPZW0rW0JzItfdaabTf0rb8t+6HKXrT1Adcx40E8F+Tg6y4\\n9EV76Zvw101AB+sV0qtXTjk6Caq7YUJqROnrvokMvB13ygNGeGJ/ejrhwJXrMGpZ\\nv2x64FJuHdvgIg6EKHcR6L++\\n-----END PRIVATE KEY-----\\n\",\n  \"client_email\": \"firebase-adminsdk-fbsvc@hridya-tech.iam.gserviceaccount.com\",\n  \"client_id\": \"109775471000102287856\",\n  \"auth_uri\": \"https://accounts.google.com/o/oauth2/auth\",\n  \"token_uri\": \"https://oauth2.googleapis.com/token\",\n  \"auth_provider_x509_cert_url\": \"https://www.googleapis.com/oauth2/v1/certs\",\n  \"client_x509_cert_url\": \"https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40hridya-tech.iam.gserviceaccount.com\",\n  \"universe_domain\": \"googleapis.com\"\n}', '2026-03-20 14:35:45'),
('fcm_vapid_key', '', '2026-03-22 16:01:26'),
('fcm_web_api_key', '', '2026-03-22 16:01:26'),
('google_maps_key', 'AIzaSyCgyo58TzjN_ikaG1a0O9r2r9NfKkGT4rA', '2026-04-05 15:06:57'),
('gps_interval', '5', '2026-04-05 15:06:57'),
('gst_rate', '0', '2026-04-05 15:06:57'),
('hp_app_feats', 'Real-time technician tracking on map|In-app chat with your assigned technician|Digital invoice and full service history|UPI, card and cash payment options', '2026-04-06 10:34:12'),
('hp_app_play_url', '#', '2026-04-06 10:34:12'),
('hp_app_store_url', '#', '2026-04-06 10:34:12'),
('hp_app_sub', 'Track your technician live, chat in-app, pay securely and access full service history — all in one place.', '2026-04-06 10:34:12'),
('hp_app_tag', 'Mobile App', '2026-04-06 10:34:12'),
('hp_app_title1', 'Book on the', '2026-04-06 10:34:12'),
('hp_app_title2', 'FixGrid App', '2026-04-06 10:34:12'),
('hp_corp_card_sub', 'Annual Maintenance Contract covers all your IT and facility service needs under one agreement with fixed monthly pricing.', '2026-04-06 10:34:12'),
('hp_corp_card_tag', 'Enterprise Ready', '2026-04-06 10:34:12'),
('hp_corp_card_title', 'What\'s Included in AMC', '2026-04-06 10:34:12'),
('hp_corp_items', 'IT Support|CCTV Maintenance|Network Upkeep|Server Monitoring|AC Servicing|Electrical Checks', '2026-04-06 10:34:12'),
('hp_corp_points', 'Dedicated account manager for your business|Monthly AMC with priority 2-hour response|Verified engineers with enterprise experience|GST invoices and digital service reports', '2026-04-06 10:34:12'),
('hp_corp_sub', 'FixGrid provides reliable, scalable professional services for businesses — including IT support, AMC contracts and on-demand technical assistance with guaranteed SLAs.', '2026-04-06 10:34:12'),
('hp_corp_tag', 'For Businesses', '2026-04-06 10:34:12'),
('hp_corp_title', 'Corporate & AMC Services', '2026-04-06 10:34:12'),
('hp_cta_btn', '⚡ Book a Technician Now', '2026-04-06 10:34:12'),
('hp_cta_sub', 'Book a verified professional in under 2 minutes. Fast response. Service guarantee included.', '2026-04-06 10:34:12'),
('hp_cta_title1', 'Ready to', '2026-04-06 10:34:12'),
('hp_cta_title2', 'Skip the Hassle?', '2026-04-06 10:34:12'),
('hp_cta_wa_btn', '💬 WhatsApp Us', '2026-04-06 10:34:12'),
('hp_footer_about', 'Your Local Professional Service Network. Verified professionals for every home and business need across Gurgaon NCR.', '2026-04-06 10:34:12'),
('hp_footer_copy', '© 2026 FixGrid. All rights reserved. ', '2026-04-06 10:34:12'),
('hp_footer_tagline', '⚡ Fast. Reliable. On-Demand.', '2026-04-06 10:34:12'),
('hp_hero_badge', 'Choose Service | Top Left badge — e.g. \"Now Live in Gurgaon\"', '2026-04-06 10:34:12'),
('hp_hero_btn1', '⚡ Book a Technician', '2026-04-06 10:34:12'),
('hp_hero_btn2', 'View Services', '2026-04-06 10:34:12'),
('hp_hero_stat1_lbl', 'Happy clients', '2026-04-06 10:34:12'),
('hp_hero_stat1_num', '800+', '2026-04-06 10:34:12'),
('hp_hero_stat2_lbl', 'Response', '2026-04-06 10:34:12'),
('hp_hero_stat2_num', '60 min', '2026-04-06 10:34:12'),
('hp_hero_stat3_lbl', 'Verified', '2026-04-06 10:34:12'),
('hp_hero_stat3_num', '100%', '2026-04-06 10:34:12'),
('hp_hero_subtitle', 'Fast, reliable and verified professionals for AC repair, electrician, IT support, CCTV, plumbing and more — across Gurgaon NCR and Bihar.', '2026-04-06 10:34:12'),
('hp_hero_title1', 'Your Local', '2026-04-06 10:34:12'),
('hp_hero_title2', 'Professional', '2026-04-06 10:34:12'),
('hp_hero_title3', 'Service Network.', '2026-04-06 10:34:12'),
('hp_hero_trust1', 'Verified professionals', '2026-04-06 10:34:12'),
('hp_hero_trust2', '7-day guarantee', '2026-04-06 10:34:12'),
('hp_hero_trust3', 'Transparent pricing', '2026-04-06 10:34:12'),
('hp_how_steps', 'Choose Service|Pick from our 8+ service categories on the app or website.|Get Matched|We find the nearest verified professional in your zone automatically.|Technician Arrives|Your professional arrives within 60 minutes, ready to fix the problem.|Rate & Pay|Pay after the job. Rate your experience. 7-day service guarantee.', '2026-04-06 10:34:12'),
('hp_how_sub', 'No complicated forms. No long waits. Just fast professional service.', '2026-04-06 10:34:12'),
('hp_how_tag', 'How It Works', '2026-04-06 10:34:12'),
('hp_how_title', 'Book in 3 Simple Steps', '2026-04-06 10:34:12'),
('hp_marquee', 'Technician at your doorstep in 60 minutes|Fast service. Trusted professionals.|From home repairs to IT support – all in one app|Skip the hassle. Book FixGrid.|Verified professionals. Service guarantee included.', '2026-04-06 10:34:12'),
('hp_nav_cta', '⚡ Book Now', '2026-04-06 10:34:12'),
('hp_nav_links', 'Services|#services|How it Works|#how|Areas|#zones|Corporate|#corporate', '2026-04-06 10:34:12'),
('hp_seo_desc', 'FixGrid provides fast and reliable home services in Gurgaon including AC repair, electrician, IT support, and CCTV installation. Book verified professionals with quick response and affordable pricing.', '2026-04-06 10:34:12'),
('hp_seo_title', 'FixGrid — Your Local Professional Service Network', '2026-04-06 10:34:12'),
('hp_services_list', 'AC Repair & Service|❄️|From ₹499|Electrician|⚡|From ₹299|IT Support|💻|From ₹499|CCTV Installation|📷|From ₹2,999|Plumbing|🔧|From ₹199|Appliance Repair|🏠|From ₹399|Cleaning Services|🧹|From ₹799|Networking & WiFi|📡|From ₹999', '2026-04-06 10:34:12'),
('hp_services_sub', 'Certified professionals for every home and business need. Book in under 3 minutes.', '2026-04-06 10:34:12'),
('hp_services_tag', 'What We Do', '2026-04-06 10:34:12'),
('hp_services_title', 'All Services, One Platform', '2026-04-06 10:34:12'),
('hp_stat1_lbl', 'Clients Served', '2026-04-06 10:34:12'),
('hp_stat1_num', '500+', '2026-04-06 10:34:12'),
('hp_stat2_lbl', 'Avg Response Time', '2026-04-06 10:34:12'),
('hp_stat2_num', '60 min', '2026-04-06 10:34:12'),
('hp_stat3_lbl', 'Verified Professionals', '2026-04-06 10:34:12'),
('hp_stat3_num', '100%', '2026-04-06 10:34:12'),
('hp_stat4_lbl', 'Service Guarantee', '2026-04-06 10:34:12'),
('hp_stat4_num', '7-day', '2026-04-06 10:34:12'),
('hp_testi_list', 'Rahul Kumar|RK|#1e88e5|Cyber City, Gurgaon|Booked an AC service at 10 AM and the technician arrived by 11 AM. Excellent work, very professional. Will definitely use FixGrid again.|Priya Sharma|PS|#ff6f00|Sohna Road, Gurgaon|Got CCTV installed at my shop. The engineer was knowledgeable and transparent about pricing. No hidden charges. Great service!|Amit Mishra|AM|#0b3c5d|Aurangabad, Bihar|FixGrid handles all our office IT support. Their AMC package is very cost-effective and their response time is excellent.', '2026-04-06 10:34:12'),
('hp_testi_sub', 'Thousands of happy customers across Gurgaon and Bihar trust FixGrid.', '2026-04-06 10:34:12'),
('hp_testi_tag', 'Testimonials', '2026-04-06 10:34:12'),
('hp_testi_title', 'What Our Clients Say', '2026-04-06 10:34:12'),
('hp_wa_number', '7982733201', '2026-04-06 10:34:12'),
('hp_why_feats', '⚡|60-Minute Response|Zone-based dispatch ensures a technician reaches you faster than anyone else.|🔒|Background Verified|Every professional undergoes ID verification, skill testing and background screening.|💰|Transparent Pricing|Fixed quotes upfront. No surprise charges. Pay only after the job is done.|⭐|7-Day Guarantee|Not satisfied? We send another professional at no additional cost.', '2026-04-06 10:34:12'),
('hp_why_sub', 'We\'re not just another service app. We\'re your trusted local network of verified professionals.', '2026-04-06 10:34:12'),
('hp_why_title', 'Why Choose FixGrid?', '2026-04-06 10:34:12'),
('hp_zones_list', 'Cyber City|green|Gurgaon|MG Road|green|Gurgaon|Sohna Road|green|Gurgaon|Golf Course Rd|green|Gurgaon|Udyog Vihar|green|Gurgaon|Sector 14-17|green|Gurgaon|South City|green|Gurgaon|Manesar|green|Gurgaon|Aurangabad|yellow|Bihar|Patna|yellow|Bihar', '2026-04-06 10:34:12'),
('hp_zones_sub', 'Active zones across Gurgaon NCR and Bihar — expanding every month.', '2026-04-06 10:34:12'),
('hp_zones_tag', 'Service Areas', '2026-04-06 10:34:12'),
('hp_zones_title', 'Where We Operate', '2026-04-06 10:34:12'),
('hsn_code', '', '2026-04-05 15:06:57'),
('kyc_auto_approve', '0', '2026-03-28 17:55:54'),
('kyc_face_match_min', '70', '2026-03-22 05:06:24'),
('kyc_required', '0', '2026-04-05 15:06:57'),
('meta_wa_phone_id', '', '2026-03-22 16:01:26'),
('meta_wa_template', 'otp_message', '2026-03-22 16:01:26'),
('meta_wa_token', '', '2026-03-20 03:21:46'),
('otp_expiry_min', '10', '2026-04-05 15:06:57'),
('payment_gateway', 'cash_upi', '2026-04-05 15:06:57'),
('platform_charge_pct', '20', '2026-04-05 15:06:57'),
('razorpay_key_id', '', '2026-03-22 16:01:26'),
('razorpay_key_secret', '', '2026-03-22 16:01:26'),
('razorpay_webhook_secret', '', '2026-03-28 06:21:32'),
('sandbox_api_key', 'key_live_6003fefe865a40bfbd502249531ff5df', '2026-03-28 14:11:10'),
('sandbox_api_secret', 'secret_live_0ff28e6d384a4dd7a2af7d0015824fb4', '2026-03-28 14:11:10'),
('sandbox_auth_token', 'eyJ0eXAiOiJKV1MiLCJhbGciOiJSU0FTU0FfUFNTX1NIQV81MTIiLCJraWQiOiIwYzYwMGUzMS01MDAwLTRkYTItYjM3YS01ODdkYTA0ZTk4NTEifQ.eyJ3b3Jrc3BhY2VfaWQiOiJkMzY5OTk5ZC05MGE2LTRhMTMtODkzOC1hYmNiNjUxMTZkYmUiLCJzdWIiOiJrZXlfbGl2ZV82MDAzZmVmZTg2NWE0MGJmYmQ1MDIyNDk1MzFmZjVkZiIsImFwaV9rZXkiOiJrZXlfbGl2ZV82MDAzZmVmZTg2NWE0MGJmYmQ1MDIyNDk1MzFmZjVkZiIsImF1ZCI6IkFQSSIsImludGVudCI6IkFDQ0VTU19UT0tFTiIsImlzcyI6InByb2QxLWFwaS5zYW5kYm94LmNvLmluIiwiaWF0IjoxNzc0NzU0MTA5LCJleHAiOjE3NzQ4NDA1MDl9.fL5shkd38OaPhmfSYMlmGq_Dbz7wxp5pXfwccxHqkS2SxAv7Ju7qskMwiaLzr9GI53AfIUd8uONNJOq9bx-Fkf-LfDlE3sqLv_gNYqiOIrJzC4Ab3EYuo3HIE7UVCIDt-oxhv2G-dv0pkYMRtIH4ezODgfLsr7bOMG3YkoNn6jynQJXV2nyoRzrG2Aog_LYD55eT9pD7q5APuwczl8QdlRGVOWPyesDSwCFwbCKtmcQVY9xlz4c43VSBJL0oVvHmOcN1BPf55UQTKi-_EKkp1wuwdj2Twv3vOvoRxLoCIFjfiNDjj6jOUtk4dZPBFM8tubR1jTTRwz0f9cyQu_XpCw', '2026-03-29 03:15:09'),
('sandbox_base_url', 'https://api.sandbox.co.in', '2026-03-28 17:55:54'),
('smtp_enabled', '1', '2026-04-05 15:06:57'),
('smtp_from_email', 'otp@fixgrid.in', '2026-04-05 08:33:32'),
('smtp_from_name', 'FixGrid', '2026-04-05 08:33:32'),
('smtp_host', 'mail.fixgrid.in', '2026-04-05 08:33:32'),
('smtp_pass', 'AF@~PQux}Iw]P6Rm', '2026-04-05 08:33:32'),
('smtp_port', '465', '2026-04-05 08:33:32'),
('smtp_user', 'otp@fixgrid.in', '2026-04-05 08:33:32'),
('twilio_account_sid', '', '2026-03-22 16:01:26'),
('twilio_auth_token', '', '2026-03-20 03:21:46'),
('twilio_whatsapp_from', 'whatsapp:+14155238886', '2026-03-22 16:01:26'),
('visit_base_charge', '199', '2026-04-05 15:06:57'),
('visit_free_km', '2', '2026-04-05 15:06:57'),
('visit_max_km_charge', '300', '2026-04-05 15:06:57'),
('visit_per_km_rate', '12', '2026-04-05 15:06:57'),
('whatsapp_enabled', '0', '2026-04-05 15:06:57'),
('whatsapp_provider', 'twilio', '2026-03-22 16:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int UNSIGNED NOT NULL,
  `user_type` enum('customer','engineer','admin') NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`id`, `user_type`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 'admin', 1, '6bbd2b138b87a23374e367cb88e4212f53f15d108e9e995f51df68ddc3eab655', '2026-04-28 02:40:52', '2026-03-29 02:40:52'),
(7, 'admin', 1, '32ee81589c7944698c07b0d735394ea5aaa6aa9253a1b01dd6a7d840425f50cb', '2026-05-04 15:27:59', '2026-04-04 15:27:59'),
(8, 'admin', 1, '6e602a879bc52355625e8fecd6d0ae84bd9d380d5e28e01beb5dc07f0bd9c76e', '2026-05-05 07:40:24', '2026-04-05 07:40:24'),
(9, 'admin', 1, 'e092452b52e3464fb22251537df3ee4307c33ce6753afdfbc095f18716e6d5d2', '2026-05-05 07:58:21', '2026-04-05 07:58:21'),
(10, 'admin', 1, 'caae3b184e53d0371206c88042074771ec20d26fde3016d34219ac75a31930f4', '2026-05-05 08:03:36', '2026-04-05 08:03:36'),
(13, 'customer', 1, 'acd245c6210c5eb36e8d095f85256f55b305b441d1d491d329a0b97f59678157', '2026-05-05 08:27:09', '2026-04-05 08:27:09'),
(15, 'admin', 1, 'bcc3fdf745c15506d055b14a9fca9e1fadadec1549457c14308780e9974eb7e9', '2026-05-05 14:36:04', '2026-04-05 14:36:04'),
(16, 'admin', 1, '883cee3c585114cc785deb177ed4a9e6257d9c08102b4f8493b8f641d418c59d', '2026-05-06 10:32:44', '2026-04-06 10:32:44'),
(17, 'customer', 1, '757190f4ce268b8c0b930a98b1020a3443bdade293c7bdbf990cc082f44e0f4c', '2026-05-07 16:17:37', '2026-04-07 16:17:37'),
(18, 'engineer', 1, 'e4d30f4eba18cd3c50ca0384f8e28b3dbc02ae4dd40573417d313babac00a814', '2026-05-07 16:18:33', '2026-04-07 16:18:33'),
(19, 'customer', 1, '3a2baee903d70b39ce21123c8bcc17ffdf2b76e509d9d5f00c655ae57457247e', '2026-05-13 09:11:02', '2026-04-13 09:11:02'),
(21, 'admin', 1, '5e6f39a0ef63880c3598cd19f2e5c98c892a8d1b5c9b69c17e1bb0894011dfd5', '2026-05-15 19:11:28', '2026-04-15 19:11:28'),
(22, 'engineer', 1, '980bc994f602c77fe3cfa09982cd50b004200347c7be871e11c77f07094ff2a1', '2026-05-15 19:34:57', '2026-04-15 19:34:57'),
(24, 'admin', 1, 'a887cd200fcbbd48b6ee77570e671f608817e2779201f37b0ace104fc46c560d', '2026-05-16 07:11:13', '2026-04-16 07:11:13'),
(25, 'admin', 1, '01d98ae6a5dc371c603576a2848e45e20cb6bc0cb9d0f559295303b2a38da556', '2026-05-16 15:57:55', '2026-04-16 15:57:55'),
(26, 'admin', 1, '0d0f234865749b772c38bcdacd7d6c8f6e020a7fcc5f4688c5dc85bdbf47d4f1', '2026-05-16 16:03:57', '2026-04-16 16:03:57'),
(27, 'admin', 1, 'e530081436189106154d51acbf87d1116fbe9a49cd689867a595d497bc248107', '2026-05-16 16:13:15', '2026-04-16 16:13:15'),
(28, 'engineer', 1, 'e7a1550f1cadcb65597e74376f5922cab73855e6d1d1d9a0bc62fd58957c802b', '2026-05-16 16:17:04', '2026-04-16 16:17:04'),
(29, 'engineer', 1, '89a27a21876cff592b19bfba040070cc5b4a2a2b10595c1573afccf8c3d2eaf2', '2026-05-19 18:55:26', '2026-04-19 18:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int UNSIGNED NOT NULL,
  `room_id` int UNSIGNED NOT NULL,
  `sender_type` enum('customer','engineer','admin') NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `message` text,
  `media_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room_id`, `sender_type`, `sender_id`, `message`, `media_url`, `is_read`, `created_at`) VALUES
(1, 4, 'engineer', 1, 'fghjkl', NULL, 1, '2026-03-29 17:22:21'),
(2, 4, 'engineer', 1, 'vkjevkjenvjk', NULL, 1, '2026-03-29 17:22:53'),
(3, 4, 'customer', 1, 'sdfghjkl', NULL, 1, '2026-03-29 17:23:38'),
(4, 9, 'engineer', 1, 'asdfghjklsdfghjk', NULL, 0, '2026-04-07 16:24:45');

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_rooms`
--

INSERT INTO `chat_rooms` (`id`, `job_id`, `customer_id`, `engineer_id`, `is_active`, `created_at`) VALUES
(1, 1, 1, 1, 1, '2026-03-29 16:05:50'),
(2, 2, 1, NULL, 1, '2026-03-29 16:31:30'),
(3, 3, 1, 1, 1, '2026-03-29 17:00:17'),
(4, 4, 1, 1, 1, '2026-03-29 17:21:47'),
(5, 5, 1, NULL, 1, '2026-03-31 19:36:44'),
(6, 6, 1, NULL, 1, '2026-03-31 19:37:53'),
(7, 7, 1, NULL, 1, '2026-03-31 20:01:04'),
(8, 8, 1, 1, 1, '2026-04-01 01:56:13'),
(9, 9, 1, 1, 1, '2026-04-07 16:20:21'),
(10, 10, 1, 1, 1, '2026-04-19 19:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `completion_otps`
--

CREATE TABLE `completion_otps` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int UNSIGNED NOT NULL,
  `contract_number` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `visits_total` int DEFAULT '1',
  `visits_done` int DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `next_service_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `profile_photo` varchar(255) DEFAULT NULL,
  `device_token` varchar(255) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_attempts` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `profile_photo`, `device_token`, `otp`, `otp_expires_at`, `otp_attempts`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Pawan kumar', '9958036005', 'pkvarma01@gmail.com', 'delhi', 'https://www.fixgrid.in/api/uploads/customers/69c94d45ce004.jpg', NULL, NULL, NULL, 0, 1, '2026-03-29 15:57:58', '2026-04-13 09:13:31');

-- --------------------------------------------------------

--
-- Table structure for table `customer_wallet`
--

CREATE TABLE `customer_wallet` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_wallet`
--

INSERT INTO `customer_wallet` (`id`, `customer_id`, `balance`, `updated_at`) VALUES
(1, 1, 0.00, '2026-03-29 16:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `device_pickups`
--

CREATE TABLE `device_pickups` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `pickup_notes` text COMMENT 'What device and issue description',
  `device_desc` varchar(255) DEFAULT NULL COMMENT 'Device name/model',
  `pickup_address` text COMMENT 'Address to pick device from',
  `status` enum('requested','scheduled','picked','repaired','delivered') DEFAULT 'requested',
  `pickup_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `repair_notes` text,
  `repair_charge` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `device_pickups`
--

INSERT INTO `device_pickups` (`id`, `job_id`, `engineer_id`, `pickup_notes`, `device_desc`, `pickup_address`, `status`, `pickup_date`, `delivery_date`, `repair_notes`, `repair_charge`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'ISSUE CANT BE DETECTED IT SHOULD BE CHECKED THROUGH MCB', 'DELL VOSTRO 3420', 'D1 , NEAR HDFC BANK', 'delivered', NULL, NULL, 'SCAHJ', 599.00, '2026-03-29 16:08:02', '2026-03-29 16:10:13');

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `resolution` text,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `engineers`
--

CREATE TABLE `engineers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('available','busy','offline') DEFAULT 'offline',
  `service_area` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `kyc_status` enum('pending','submitted','approved','rejected') DEFAULT 'pending',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `last_online` timestamp NULL DEFAULT NULL,
  `device_token` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `kyc_selfie_url` varchar(500) DEFAULT NULL,
  `kyc_rejection_reason` text,
  `kyc_reviewed_at` timestamp NULL DEFAULT NULL,
  `kyc_reviewed_by` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `engineers`
--

INSERT INTO `engineers` (`id`, `name`, `phone`, `email`, `password`, `profile_photo`, `status`, `service_area`, `city`, `kyc_status`, `latitude`, `longitude`, `last_online`, `device_token`, `is_active`, `created_at`, `updated_at`, `kyc_selfie_url`, `kyc_rejection_reason`, `kyc_reviewed_at`, `kyc_reviewed_by`) VALUES
(1, 'PAWAN KUMAR', '9810519169', 'bookelsc@gmail.com', '$2y$12$tHhTFIkyDs3IaL8V7e2UAOGRHgq4V3TClvg3.uulQyvAuuj5duHD.', 'https://www.fixgrid.in/api/uploads/engineers/69c94d84295a3.jpg', 'busy', 'NOIDA', 'delhi', 'approved', 28.41393705, 77.04139254, '2026-04-20 03:54:38', NULL, 1, '2026-03-29 02:42:11', '2026-04-20 03:54:38', 'https://www.fixgrid.in/api/uploads/kyc/selfies/69c947f850c3a.jpg', NULL, '2026-03-29 15:40:40', NULL),
(2, 'Akash Kumar', '8002966669', 'abc@gmail.com', '$2y$12$xM.VZEERGm2Bx4QTrUiam.CK40pD34kq/KEEz71AlNecHoIMdpbEG', NULL, 'offline', NULL, 'GURGAON', 'pending', NULL, NULL, NULL, NULL, 0, '2026-04-15 19:45:26', '2026-04-15 19:45:26', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `engineer_availability`
--

CREATE TABLE `engineer_availability` (
  `engineer_id` int UNSIGNED NOT NULL,
  `slot_id` int UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `is_available` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `engineer_availability`
--

INSERT INTO `engineer_availability` (`engineer_id`, `slot_id`, `date`, `is_available`) VALUES
(1, 1, '2026-03-29', 1),
(1, 1, '2026-03-30', 1),
(1, 1, '2026-03-31', 1),
(1, 1, '2026-04-01', 1),
(1, 1, '2026-04-02', 1),
(1, 1, '2026-04-03', 1),
(1, 1, '2026-04-04', 1),
(1, 2, '2026-03-29', 1),
(1, 2, '2026-03-30', 1),
(1, 2, '2026-03-31', 1),
(1, 2, '2026-04-01', 1),
(1, 2, '2026-04-02', 1),
(1, 2, '2026-04-03', 1),
(1, 2, '2026-04-04', 1),
(1, 3, '2026-03-29', 1),
(1, 3, '2026-03-30', 1),
(1, 3, '2026-03-31', 1),
(1, 3, '2026-04-01', 1),
(1, 3, '2026-04-02', 1),
(1, 3, '2026-04-03', 1),
(1, 3, '2026-04-04', 1),
(1, 4, '2026-03-29', 1),
(1, 4, '2026-03-30', 1),
(1, 4, '2026-03-31', 1),
(1, 4, '2026-04-01', 1),
(1, 4, '2026-04-02', 1),
(1, 4, '2026-04-03', 1),
(1, 4, '2026-04-04', 1),
(1, 5, '2026-03-29', 1),
(1, 5, '2026-03-30', 1),
(1, 5, '2026-03-31', 1),
(1, 5, '2026-04-01', 1),
(1, 5, '2026-04-02', 1),
(1, 5, '2026-04-03', 1),
(1, 5, '2026-04-04', 1),
(1, 6, '2026-03-29', 1),
(1, 6, '2026-03-30', 1),
(1, 6, '2026-03-31', 1),
(1, 6, '2026-04-01', 1),
(1, 6, '2026-04-02', 1),
(1, 6, '2026-04-03', 1),
(1, 6, '2026-04-04', 1),
(1, 7, '2026-03-29', 1),
(1, 7, '2026-03-30', 1),
(1, 7, '2026-03-31', 1),
(1, 7, '2026-04-01', 1),
(1, 7, '2026-04-02', 1),
(1, 7, '2026-04-03', 1),
(1, 7, '2026-04-04', 1),
(1, 8, '2026-03-29', 1),
(1, 8, '2026-03-30', 1),
(1, 8, '2026-03-31', 1),
(1, 8, '2026-04-01', 1),
(1, 8, '2026-04-02', 1),
(1, 8, '2026-04-03', 1),
(1, 8, '2026-04-04', 1),
(1, 9, '2026-03-29', 1),
(1, 9, '2026-03-30', 1),
(1, 9, '2026-03-31', 1),
(1, 9, '2026-04-01', 1),
(1, 9, '2026-04-02', 1),
(1, 9, '2026-04-03', 1),
(1, 9, '2026-04-04', 1),
(1, 10, '2026-03-29', 1),
(1, 10, '2026-03-30', 1),
(1, 10, '2026-03-31', 1),
(1, 10, '2026-04-01', 1),
(1, 10, '2026-04-02', 1),
(1, 10, '2026-04-03', 1),
(1, 10, '2026-04-04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `engineer_earnings`
--

CREATE TABLE `engineer_earnings` (
  `id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `engineer_inventory`
--

CREATE TABLE `engineer_inventory` (
  `id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `part_id` int UNSIGNED NOT NULL,
  `qty` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `engineer_locations`
--

CREATE TABLE `engineer_locations` (
  `id` bigint UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `speed` float DEFAULT '0',
  `heading` float DEFAULT '0',
  `accuracy` float DEFAULT '0',
  `job_id` int UNSIGNED DEFAULT NULL,
  `timestamp` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `engineer_locations`
--

INSERT INTO `engineer_locations` (`id`, `engineer_id`, `latitude`, `longitude`, `speed`, `heading`, `accuracy`, `job_id`, `timestamp`, `created_at`) VALUES
(1, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329211109, '2026-03-29 15:41:09'),
(2, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329211110, '2026-03-29 15:41:10'),
(3, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212720, '2026-03-29 15:57:20'),
(4, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212720, '2026-03-29 15:57:20'),
(5, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212723, '2026-03-29 15:57:23'),
(6, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212728, '2026-03-29 15:57:28'),
(7, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212733, '2026-03-29 15:57:33'),
(8, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212738, '2026-03-29 15:57:38'),
(9, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212743, '2026-03-29 15:57:43'),
(10, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329212748, '2026-03-29 15:57:48'),
(11, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212757, '2026-03-29 15:57:57'),
(12, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212758, '2026-03-29 15:57:58'),
(13, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212803, '2026-03-29 15:58:03'),
(14, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212808, '2026-03-29 15:58:08'),
(15, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212813, '2026-03-29 15:58:13'),
(16, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212818, '2026-03-29 15:58:18'),
(17, 1, 28.60815083, 77.37587775, 0, 0, 99, NULL, 20260329212823, '2026-03-29 15:58:23'),
(18, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329212905, '2026-03-29 15:59:05'),
(19, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329213005, '2026-03-29 16:00:05'),
(20, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329213105, '2026-03-29 16:01:05'),
(21, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213200, '2026-03-29 16:02:00'),
(22, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213214, '2026-03-29 16:02:14'),
(23, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213217, '2026-03-29 16:02:17'),
(24, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213228, '2026-03-29 16:02:28'),
(25, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213228, '2026-03-29 16:02:28'),
(26, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213239, '2026-03-29 16:02:39'),
(27, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213239, '2026-03-29 16:02:39'),
(28, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213243, '2026-03-29 16:02:43'),
(29, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213248, '2026-03-29 16:02:48'),
(30, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213253, '2026-03-29 16:02:53'),
(31, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213258, '2026-03-29 16:02:58'),
(32, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213303, '2026-03-29 16:03:03'),
(33, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213308, '2026-03-29 16:03:08'),
(34, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213316, '2026-03-29 16:03:16'),
(35, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213318, '2026-03-29 16:03:18'),
(36, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213354, '2026-03-29 16:03:54'),
(37, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213357, '2026-03-29 16:03:57'),
(38, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213402, '2026-03-29 16:04:02'),
(39, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213407, '2026-03-29 16:04:07'),
(40, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213412, '2026-03-29 16:04:12'),
(41, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213417, '2026-03-29 16:04:17'),
(42, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213426, '2026-03-29 16:04:26'),
(43, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213427, '2026-03-29 16:04:27'),
(44, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213432, '2026-03-29 16:04:32'),
(45, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213437, '2026-03-29 16:04:37'),
(46, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213443, '2026-03-29 16:04:43'),
(47, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213448, '2026-03-29 16:04:48'),
(48, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213453, '2026-03-29 16:04:53'),
(49, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213500, '2026-03-29 16:05:00'),
(50, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213503, '2026-03-29 16:05:03'),
(51, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213508, '2026-03-29 16:05:08'),
(52, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213513, '2026-03-29 16:05:13'),
(53, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213518, '2026-03-29 16:05:18'),
(54, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213523, '2026-03-29 16:05:23'),
(55, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329213528, '2026-03-29 16:05:28'),
(56, 1, 28.60851841, 77.37617256, 0, 0, 146, NULL, 20260329213536, '2026-03-29 16:05:36'),
(57, 1, 28.60851841, 77.37617256, 0, 0, 146, NULL, 20260329213538, '2026-03-29 16:05:38'),
(58, 1, 28.60851841, 77.37617256, 0, 0, 146, NULL, 20260329213554, '2026-03-29 16:05:54'),
(59, 1, 28.60851841, 77.37617256, 0, 0, 146, NULL, 20260329213556, '2026-03-29 16:05:56'),
(60, 1, 28.60851841, 77.37617256, 0, 0, 146, NULL, 20260329213601, '2026-03-29 16:06:01'),
(61, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213610, '2026-03-29 16:06:10'),
(62, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213612, '2026-03-29 16:06:12'),
(63, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213617, '2026-03-29 16:06:17'),
(64, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213622, '2026-03-29 16:06:22'),
(65, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213626, '2026-03-29 16:06:26'),
(66, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213631, '2026-03-29 16:06:31'),
(67, 1, 28.60822500, 77.37670900, 0, 0, 49, NULL, 20260329213636, '2026-03-29 16:06:36'),
(68, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213643, '2026-03-29 16:06:43'),
(69, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213646, '2026-03-29 16:06:46'),
(70, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213651, '2026-03-29 16:06:51'),
(71, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213656, '2026-03-29 16:06:56'),
(72, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213701, '2026-03-29 16:07:01'),
(73, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213706, '2026-03-29 16:07:06'),
(74, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213711, '2026-03-29 16:07:11'),
(75, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213723, '2026-03-29 16:07:23'),
(76, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213723, '2026-03-29 16:07:23'),
(77, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213727, '2026-03-29 16:07:27'),
(78, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213731, '2026-03-29 16:07:31'),
(79, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213736, '2026-03-29 16:07:36'),
(80, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213741, '2026-03-29 16:07:41'),
(81, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213746, '2026-03-29 16:07:46'),
(82, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213751, '2026-03-29 16:07:51'),
(83, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213756, '2026-03-29 16:07:56'),
(84, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213812, '2026-03-29 16:08:12'),
(85, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213812, '2026-03-29 16:08:12'),
(86, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213812, '2026-03-29 16:08:12'),
(87, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213817, '2026-03-29 16:08:17'),
(88, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213822, '2026-03-29 16:08:22'),
(89, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213826, '2026-03-29 16:08:26'),
(90, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213831, '2026-03-29 16:08:31'),
(91, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213837, '2026-03-29 16:08:37'),
(92, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213849, '2026-03-29 16:08:49'),
(93, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213849, '2026-03-29 16:08:49'),
(94, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213852, '2026-03-29 16:08:52'),
(95, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213857, '2026-03-29 16:08:57'),
(96, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213902, '2026-03-29 16:09:02'),
(97, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213907, '2026-03-29 16:09:07'),
(98, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213912, '2026-03-29 16:09:12'),
(99, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213917, '2026-03-29 16:09:17'),
(100, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213922, '2026-03-29 16:09:22'),
(101, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213934, '2026-03-29 16:09:34'),
(102, 1, 28.63900000, 77.23600000, 0, 0, 42761, 1, 20260329213934, '2026-03-29 16:09:34'),
(103, 1, 28.60781279, 77.37593621, 0, 0, 187, 1, 20260329213937, '2026-03-29 16:09:37'),
(104, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213938, '2026-03-29 16:09:38'),
(105, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213949, '2026-03-29 16:09:49'),
(106, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213949, '2026-03-29 16:09:49'),
(107, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213954, '2026-03-29 16:09:54'),
(108, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329213959, '2026-03-29 16:09:59'),
(109, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214004, '2026-03-29 16:10:04'),
(110, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214013, '2026-03-29 16:10:13'),
(111, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214014, '2026-03-29 16:10:14'),
(112, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214019, '2026-03-29 16:10:19'),
(113, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214024, '2026-03-29 16:10:24'),
(114, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214029, '2026-03-29 16:10:29'),
(115, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214034, '2026-03-29 16:10:34'),
(116, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214039, '2026-03-29 16:10:39'),
(117, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214045, '2026-03-29 16:10:45'),
(118, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214048, '2026-03-29 16:10:48'),
(119, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214054, '2026-03-29 16:10:54'),
(120, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214059, '2026-03-29 16:10:59'),
(121, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214104, '2026-03-29 16:11:04'),
(122, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214109, '2026-03-29 16:11:09'),
(123, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214114, '2026-03-29 16:11:14'),
(124, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214121, '2026-03-29 16:11:21'),
(125, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214124, '2026-03-29 16:11:24'),
(126, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214129, '2026-03-29 16:11:29'),
(127, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214134, '2026-03-29 16:11:34'),
(128, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214139, '2026-03-29 16:11:39'),
(129, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214144, '2026-03-29 16:11:44'),
(130, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214149, '2026-03-29 16:11:49'),
(131, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329214205, '2026-03-29 16:12:05'),
(132, 1, 28.60779919, 77.37582500, 0, 0, 131, NULL, 20260329214302, '2026-03-29 16:13:02'),
(133, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214402, '2026-03-29 16:14:02'),
(134, 1, 28.60788439, 77.37592007, 0, 0, 183, NULL, 20260329214501, '2026-03-29 16:15:01'),
(135, 1, 28.60785362, 77.37591002, 0, 0, 187, NULL, 20260329214603, '2026-03-29 16:16:03'),
(136, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214659, '2026-03-29 16:16:59'),
(137, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214800, '2026-03-29 16:18:00'),
(138, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329214902, '2026-03-29 16:19:02'),
(139, 1, 28.60781279, 77.37593621, 0, 0, 187, NULL, 20260329215000, '2026-03-29 16:20:00'),
(140, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329215101, '2026-03-29 16:21:01'),
(141, 1, 28.60825588, 77.37608665, 0, 0, 122, NULL, 20260329215200, '2026-03-29 16:22:00'),
(142, 1, 28.60774156, 77.37581713, 0, 0, 128, NULL, 20260329215300, '2026-03-29 16:23:00'),
(143, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220043, '2026-03-29 16:30:43'),
(144, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220044, '2026-03-29 16:30:44'),
(145, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220049, '2026-03-29 16:30:49'),
(146, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220054, '2026-03-29 16:30:54'),
(147, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220100, '2026-03-29 16:31:00'),
(148, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220105, '2026-03-29 16:31:05'),
(149, 1, 28.60775400, 77.37589500, 0, 0, 25, NULL, 20260329220110, '2026-03-29 16:31:10'),
(150, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220116, '2026-03-29 16:31:16'),
(151, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220120, '2026-03-29 16:31:20'),
(152, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220125, '2026-03-29 16:31:25'),
(153, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220130, '2026-03-29 16:31:30'),
(154, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220135, '2026-03-29 16:31:35'),
(155, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220139, '2026-03-29 16:31:39'),
(156, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220140, '2026-03-29 16:31:40'),
(157, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220142, '2026-03-29 16:31:42'),
(158, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220153, '2026-03-29 16:31:53'),
(159, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220153, '2026-03-29 16:31:53'),
(160, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220158, '2026-03-29 16:31:58'),
(161, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220203, '2026-03-29 16:32:03'),
(162, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220208, '2026-03-29 16:32:08'),
(163, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220212, '2026-03-29 16:32:12'),
(164, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220214, '2026-03-29 16:32:14'),
(165, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220215, '2026-03-29 16:32:15'),
(166, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220221, '2026-03-29 16:32:21'),
(167, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220227, '2026-03-29 16:32:27'),
(168, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220230, '2026-03-29 16:32:30'),
(169, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220235, '2026-03-29 16:32:35'),
(170, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220240, '2026-03-29 16:32:40'),
(171, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220245, '2026-03-29 16:32:45'),
(172, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220251, '2026-03-29 16:32:51'),
(173, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220256, '2026-03-29 16:32:56'),
(174, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329220308, '2026-03-29 16:33:08'),
(175, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329220308, '2026-03-29 16:33:08'),
(176, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220311, '2026-03-29 16:33:11'),
(177, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220315, '2026-03-29 16:33:15'),
(178, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220320, '2026-03-29 16:33:20'),
(179, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220326, '2026-03-29 16:33:26'),
(180, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220331, '2026-03-29 16:33:31'),
(181, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220336, '2026-03-29 16:33:36'),
(182, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220341, '2026-03-29 16:33:41'),
(183, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220349, '2026-03-29 16:33:49'),
(184, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220351, '2026-03-29 16:33:51'),
(185, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329220356, '2026-03-29 16:33:56'),
(186, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329222549, '2026-03-29 16:55:49'),
(187, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329222656, '2026-03-29 16:56:56'),
(188, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329222715, '2026-03-29 16:57:15'),
(189, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329222716, '2026-03-29 16:57:16'),
(190, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329222723, '2026-03-29 16:57:23'),
(191, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329222726, '2026-03-29 16:57:26'),
(192, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222734, '2026-03-29 16:57:34'),
(193, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222736, '2026-03-29 16:57:36'),
(194, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222741, '2026-03-29 16:57:41'),
(195, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222746, '2026-03-29 16:57:46'),
(196, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222751, '2026-03-29 16:57:51'),
(197, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222756, '2026-03-29 16:57:56'),
(198, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329222801, '2026-03-29 16:58:01'),
(199, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329222813, '2026-03-29 16:58:13'),
(200, 1, 28.63900000, 77.23600000, 0, 0, 42761, NULL, 20260329222813, '2026-03-29 16:58:13'),
(201, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222817, '2026-03-29 16:58:17'),
(202, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222821, '2026-03-29 16:58:21'),
(203, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222826, '2026-03-29 16:58:26'),
(204, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222831, '2026-03-29 16:58:31'),
(205, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222836, '2026-03-29 16:58:36'),
(206, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222841, '2026-03-29 16:58:41'),
(207, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222849, '2026-03-29 16:58:49'),
(208, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222852, '2026-03-29 16:58:52'),
(209, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222857, '2026-03-29 16:58:57'),
(210, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222902, '2026-03-29 16:59:02'),
(211, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222907, '2026-03-29 16:59:07'),
(212, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222912, '2026-03-29 16:59:12'),
(213, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222917, '2026-03-29 16:59:17'),
(214, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222924, '2026-03-29 16:59:24'),
(215, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222927, '2026-03-29 16:59:27'),
(216, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222932, '2026-03-29 16:59:32'),
(217, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222937, '2026-03-29 16:59:37'),
(218, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222942, '2026-03-29 16:59:42'),
(219, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222947, '2026-03-29 16:59:47'),
(220, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329222952, '2026-03-29 16:59:52'),
(221, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223002, '2026-03-29 17:00:02'),
(222, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223002, '2026-03-29 17:00:02'),
(223, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223007, '2026-03-29 17:00:07'),
(224, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223012, '2026-03-29 17:00:12'),
(225, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223017, '2026-03-29 17:00:17'),
(226, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223022, '2026-03-29 17:00:22'),
(227, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223027, '2026-03-29 17:00:27'),
(228, 1, 28.60793793, 77.37590941, 0, 0, 137, NULL, 20260329223032, '2026-03-29 17:00:32'),
(229, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223039, '2026-03-29 17:00:39'),
(230, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223042, '2026-03-29 17:00:42'),
(231, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223049, '2026-03-29 17:00:49'),
(232, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223119, '2026-03-29 17:01:19'),
(233, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223119, '2026-03-29 17:01:19'),
(234, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329223121, '2026-03-29 17:01:21'),
(235, 1, 28.60800162, 77.37611891, 0, 0, 202, 3, 20260329223126, '2026-03-29 17:01:26'),
(236, 1, 28.60800162, 77.37611891, 0, 0, 202, 3, 20260329223131, '2026-03-29 17:01:31'),
(237, 1, 28.60800162, 77.37611891, 0, 0, 202, 3, 20260329223136, '2026-03-29 17:01:36'),
(238, 1, 28.60800162, 77.37611891, 0, 0, 202, 3, 20260329223141, '2026-03-29 17:01:41'),
(239, 1, 28.60800162, 77.37611891, 0, 0, 202, 3, 20260329223146, '2026-03-29 17:01:46'),
(240, 1, 28.63900000, 77.23600000, 0, 0, 42761, 3, 20260329223158, '2026-03-29 17:01:58'),
(241, 1, 28.63900000, 77.23600000, 0, 0, 42761, 3, 20260329223158, '2026-03-29 17:01:58'),
(242, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223202, '2026-03-29 17:02:02'),
(243, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223206, '2026-03-29 17:02:06'),
(244, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223211, '2026-03-29 17:02:11'),
(245, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223216, '2026-03-29 17:02:16'),
(246, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223221, '2026-03-29 17:02:21'),
(247, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223226, '2026-03-29 17:02:26'),
(248, 1, 28.60814300, 77.37653400, 0, 0, 46, 3, 20260329223231, '2026-03-29 17:02:31'),
(249, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223240, '2026-03-29 17:02:40'),
(250, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223242, '2026-03-29 17:02:42'),
(251, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223247, '2026-03-29 17:02:47'),
(252, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223252, '2026-03-29 17:02:52'),
(253, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223257, '2026-03-29 17:02:57'),
(254, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223302, '2026-03-29 17:03:02'),
(255, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223307, '2026-03-29 17:03:07'),
(256, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223314, '2026-03-29 17:03:14'),
(257, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223317, '2026-03-29 17:03:17'),
(258, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223322, '2026-03-29 17:03:22'),
(259, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223327, '2026-03-29 17:03:27'),
(260, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223332, '2026-03-29 17:03:32'),
(261, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223337, '2026-03-29 17:03:37'),
(262, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223351, '2026-03-29 17:03:51'),
(263, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223442, '2026-03-29 17:04:42'),
(264, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223442, '2026-03-29 17:04:42'),
(265, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223442, '2026-03-29 17:04:42'),
(266, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223447, '2026-03-29 17:04:47'),
(267, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223452, '2026-03-29 17:04:52'),
(268, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223457, '2026-03-29 17:04:57'),
(269, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223502, '2026-03-29 17:05:02'),
(270, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223507, '2026-03-29 17:05:07'),
(271, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223514, '2026-03-29 17:05:14'),
(272, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223517, '2026-03-29 17:05:17'),
(273, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223522, '2026-03-29 17:05:22'),
(274, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223527, '2026-03-29 17:05:27'),
(275, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223532, '2026-03-29 17:05:32'),
(276, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223537, '2026-03-29 17:05:37'),
(277, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223552, '2026-03-29 17:05:52'),
(278, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223653, '2026-03-29 17:06:53'),
(279, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223711, '2026-03-29 17:07:11'),
(280, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223711, '2026-03-29 17:07:11'),
(281, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223716, '2026-03-29 17:07:16'),
(282, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223721, '2026-03-29 17:07:21'),
(283, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329223728, '2026-03-29 17:07:28'),
(284, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329223732, '2026-03-29 17:07:32'),
(285, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329223737, '2026-03-29 17:07:37'),
(286, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223742, '2026-03-29 17:07:42'),
(287, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223747, '2026-03-29 17:07:47'),
(288, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223753, '2026-03-29 17:07:53'),
(289, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223758, '2026-03-29 17:07:58'),
(290, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223807, '2026-03-29 17:08:07'),
(291, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223808, '2026-03-29 17:08:08'),
(292, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223813, '2026-03-29 17:08:13'),
(293, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223818, '2026-03-29 17:08:18'),
(294, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223823, '2026-03-29 17:08:23'),
(295, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223828, '2026-03-29 17:08:28'),
(296, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223833, '2026-03-29 17:08:33'),
(297, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223840, '2026-03-29 17:08:40'),
(298, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223843, '2026-03-29 17:08:43'),
(299, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329223848, '2026-03-29 17:08:48'),
(300, 1, 28.63900000, 77.23600000, 0, 0, 42761, 3, 20260329223956, '2026-03-29 17:09:56'),
(301, 1, 28.63900000, 77.23600000, 0, 0, 42761, 3, 20260329224056, '2026-03-29 17:10:56'),
(302, 1, 28.60788439, 77.37592007, 0, 0, 183, 3, 20260329224155, '2026-03-29 17:11:55'),
(303, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224253, '2026-03-29 17:12:53'),
(304, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224344, '2026-03-29 17:13:44'),
(305, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224344, '2026-03-29 17:13:44'),
(306, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224347, '2026-03-29 17:13:47'),
(307, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224353, '2026-03-29 17:13:53'),
(308, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224357, '2026-03-29 17:13:57'),
(309, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224402, '2026-03-29 17:14:02'),
(310, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224407, '2026-03-29 17:14:07'),
(311, 1, 28.60785563, 77.37593477, 0, 0, 143, 3, 20260329224415, '2026-03-29 17:14:15'),
(312, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224419, '2026-03-29 17:14:19'),
(313, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(314, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(315, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(316, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(317, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224459, '2026-03-29 17:14:59'),
(318, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(319, 1, 28.60792443, 77.37594087, 0, 0, 183, 3, 20260329224459, '2026-03-29 17:14:59'),
(320, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224459, '2026-03-29 17:14:59'),
(321, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224514, '2026-03-29 17:15:14'),
(322, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224514, '2026-03-29 17:15:14'),
(323, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224526, '2026-03-29 17:15:26'),
(324, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224526, '2026-03-29 17:15:26'),
(325, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224526, '2026-03-29 17:15:26'),
(326, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224536, '2026-03-29 17:15:36'),
(327, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224536, '2026-03-29 17:15:36'),
(328, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224538, '2026-03-29 17:15:38'),
(329, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224542, '2026-03-29 17:15:42'),
(330, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224547, '2026-03-29 17:15:47'),
(331, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224552, '2026-03-29 17:15:52'),
(332, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224557, '2026-03-29 17:15:57'),
(333, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224607, '2026-03-29 17:16:07'),
(334, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224607, '2026-03-29 17:16:07'),
(335, 1, 28.60830950, 77.37589475, 0, 0, 381, 3, 20260329224612, '2026-03-29 17:16:12'),
(336, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224613, '2026-03-29 17:16:13'),
(337, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224617, '2026-03-29 17:16:17'),
(338, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224623, '2026-03-29 17:16:23'),
(339, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224628, '2026-03-29 17:16:28'),
(340, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224633, '2026-03-29 17:16:33'),
(341, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224641, '2026-03-29 17:16:41'),
(342, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224643, '2026-03-29 17:16:43'),
(343, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224648, '2026-03-29 17:16:48'),
(344, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224653, '2026-03-29 17:16:53'),
(345, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224658, '2026-03-29 17:16:58'),
(346, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224703, '2026-03-29 17:17:03'),
(347, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224707, '2026-03-29 17:17:07'),
(348, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224716, '2026-03-29 17:17:16'),
(349, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224718, '2026-03-29 17:17:18'),
(350, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224723, '2026-03-29 17:17:23'),
(351, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224728, '2026-03-29 17:17:28'),
(352, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224733, '2026-03-29 17:17:33'),
(353, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224738, '2026-03-29 17:17:38'),
(354, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224743, '2026-03-29 17:17:43'),
(355, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224750, '2026-03-29 17:17:50'),
(356, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224753, '2026-03-29 17:17:53'),
(357, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224758, '2026-03-29 17:17:58'),
(358, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224803, '2026-03-29 17:18:03'),
(359, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329224808, '2026-03-29 17:18:08'),
(360, 1, 28.60825441, 77.37586443, 0, 0, 107, NULL, 20260329224851, '2026-03-29 17:18:51'),
(361, 1, 28.60830049, 77.37595216, 0, 0, 102, NULL, 20260329224953, '2026-03-29 17:19:53'),
(362, 1, 28.60830049, 77.37595216, 0, 0, 102, NULL, 20260329225045, '2026-03-29 17:20:45'),
(363, 1, 28.60830049, 77.37595216, 0, 0, 102, NULL, 20260329225053, '2026-03-29 17:20:53'),
(364, 1, 28.60830049, 77.37595216, 0, 0, 102, NULL, 20260329225101, '2026-03-29 17:21:01'),
(365, 1, 28.60830049, 77.37595216, 0, 0, 102, NULL, 20260329225109, '2026-03-29 17:21:09'),
(366, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225122, '2026-03-29 17:21:22'),
(367, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225125, '2026-03-29 17:21:25'),
(368, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225133, '2026-03-29 17:21:33'),
(369, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225141, '2026-03-29 17:21:41'),
(370, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225149, '2026-03-29 17:21:49'),
(371, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225159, '2026-03-29 17:21:59'),
(372, 1, 28.60846510, 77.37624606, 0, 0, 202, NULL, 20260329225204, '2026-03-29 17:22:04'),
(373, 1, 28.60846510, 77.37624606, 0, 0, 202, 4, 20260329225212, '2026-03-29 17:22:12'),
(374, 1, 28.60846510, 77.37624606, 0, 0, 202, 4, 20260329225220, '2026-03-29 17:22:20'),
(375, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225232, '2026-03-29 17:22:32'),
(376, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225237, '2026-03-29 17:22:37'),
(377, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225245, '2026-03-29 17:22:45'),
(378, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225252, '2026-03-29 17:22:52'),
(379, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225301, '2026-03-29 17:23:01'),
(380, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225313, '2026-03-29 17:23:13'),
(381, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225317, '2026-03-29 17:23:17'),
(382, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225325, '2026-03-29 17:23:25'),
(383, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225333, '2026-03-29 17:23:33'),
(384, 1, 28.60830049, 77.37595216, 0, 0, 102, 4, 20260329225341, '2026-03-29 17:23:41'),
(385, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225352, '2026-03-29 17:23:52'),
(386, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225356, '2026-03-29 17:23:56'),
(387, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225405, '2026-03-29 17:24:05'),
(388, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225412, '2026-03-29 17:24:12'),
(389, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225420, '2026-03-29 17:24:20'),
(390, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225428, '2026-03-29 17:24:28'),
(391, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225436, '2026-03-29 17:24:36'),
(392, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225447, '2026-03-29 17:24:47'),
(393, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225453, '2026-03-29 17:24:53'),
(394, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225501, '2026-03-29 17:25:01'),
(395, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225509, '2026-03-29 17:25:09'),
(396, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225523, '2026-03-29 17:25:23'),
(397, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225525, '2026-03-29 17:25:25'),
(398, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225533, '2026-03-29 17:25:33'),
(399, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225541, '2026-03-29 17:25:41'),
(400, 1, 28.60825441, 77.37586443, 0, 0, 107, 4, 20260329225549, '2026-03-29 17:25:49'),
(401, 1, 28.60800162, 77.37611891, 0, 0, 202, 4, 20260329225651, '2026-03-29 17:26:51'),
(402, 1, 28.60800162, 77.37611891, 0, 0, 202, 4, 20260329225754, '2026-03-29 17:27:54'),
(403, 1, 28.60800162, 77.37611891, 0, 0, 202, 4, 20260329225854, '2026-03-29 17:28:54'),
(404, 1, 28.60793004, 77.37616883, 0, 0, 209, 4, 20260329225951, '2026-03-29 17:29:51'),
(405, 1, 28.63900000, 77.23600000, 0, 0, 42761, 4, 20260329230056, '2026-03-29 17:30:56'),
(406, 1, 28.63900000, 77.23600000, 0, 0, 42761, 4, 20260329230157, '2026-03-29 17:31:57'),
(407, 1, 28.63900000, 77.23600000, 0, 0, 42761, 4, 20260329230256, '2026-03-29 17:32:56'),
(408, 1, 28.60800162, 77.37611891, 0, 0, 202, 4, 20260329230353, '2026-03-29 17:33:53'),
(409, 1, 28.60800162, 77.37611891, 0, 0, 202, 4, 20260329230450, '2026-03-29 17:34:50'),
(410, 1, 28.60830950, 77.37589475, 0, 0, 381, 4, 20260329230551, '2026-03-29 17:35:51'),
(411, 1, 28.60846302, 77.37631504, 0, 0, 209, 4, 20260329230651, '2026-03-29 17:36:51'),
(412, 1, 28.60820111, 77.37626127, 0, 0, 159, 4, 20260329230751, '2026-03-29 17:37:51'),
(413, 1, 28.60822194, 77.37622563, 0, 0, 160, 4, 20260329230834, '2026-03-29 17:38:34'),
(414, 1, 28.60822194, 77.37622563, 0, 0, 160, 4, 20260329230836, '2026-03-29 17:38:36'),
(415, 1, 28.60822194, 77.37622563, 0, 0, 160, NULL, 20260329230837, '2026-03-29 17:38:37'),
(416, 1, 28.60822194, 77.37622563, 0, 0, 160, NULL, 20260329230846, '2026-03-29 17:38:46'),
(417, 1, 28.60822194, 77.37622563, 0, 0, 160, NULL, 20260329230854, '2026-03-29 17:38:54'),
(418, 1, 28.60822194, 77.37622563, 0, 0, 160, NULL, 20260329230902, '2026-03-29 17:39:02'),
(419, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329230912, '2026-03-29 17:39:12'),
(420, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329230918, '2026-03-29 17:39:18'),
(421, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329230926, '2026-03-29 17:39:26'),
(422, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329230934, '2026-03-29 17:39:34'),
(423, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329230942, '2026-03-29 17:39:42'),
(424, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329231045, '2026-03-29 17:40:45'),
(425, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329231049, '2026-03-29 17:40:49'),
(426, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329231152, '2026-03-29 17:41:52'),
(427, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329231835, '2026-03-29 17:48:35'),
(428, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329231849, '2026-03-29 17:48:49'),
(429, 1, 28.60830950, 77.37589475, 0, 0, 381, NULL, 20260329231953, '2026-03-29 17:49:53'),
(430, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329232052, '2026-03-29 17:50:52'),
(431, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329232155, '2026-03-29 17:51:55'),
(432, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329233936, '2026-03-29 18:09:36'),
(433, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329233949, '2026-03-29 18:09:49'),
(434, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234052, '2026-03-29 18:10:52'),
(435, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234153, '2026-03-29 18:11:53'),
(436, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234252, '2026-03-29 18:12:52'),
(437, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234351, '2026-03-29 18:13:51'),
(438, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234452, '2026-03-29 18:14:52'),
(439, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234553, '2026-03-29 18:15:53'),
(440, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234648, '2026-03-29 18:16:48'),
(441, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234648, '2026-03-29 18:16:48'),
(442, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234654, '2026-03-29 18:16:54'),
(443, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234702, '2026-03-29 18:17:02'),
(444, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234710, '2026-03-29 18:17:10'),
(445, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234721, '2026-03-29 18:17:21'),
(446, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234726, '2026-03-29 18:17:26'),
(447, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234734, '2026-03-29 18:17:34'),
(448, 1, 28.60819454, 77.37621709, 0, 0, 137, NULL, 20260329234742, '2026-03-29 18:17:42'),
(449, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234846, '2026-03-29 18:18:46'),
(450, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234849, '2026-03-29 18:18:49'),
(451, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329234952, '2026-03-29 18:19:52'),
(452, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329235053, '2026-03-29 18:20:53'),
(453, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329235153, '2026-03-29 18:21:53'),
(454, 1, 28.60800162, 77.37611891, 0, 0, 202, NULL, 20260329235206, '2026-03-29 18:22:06'),
(455, 1, 28.41388639, 77.04141210, 0, 0, 59, NULL, 20260330110436, '2026-03-30 05:34:36'),
(456, 1, 28.41388639, 77.04141210, 0, 0, 59, NULL, 20260330110437, '2026-03-30 05:34:37'),
(457, 1, 28.41388639, 77.04141210, 0, 0, 59, NULL, 20260330110445, '2026-03-30 05:34:45'),
(458, 1, 28.41388639, 77.04141210, 0, 0, 59, NULL, 20260330110453, '2026-03-30 05:34:53'),
(459, 1, 28.41388639, 77.04141210, 0, 0, 59, NULL, 20260330110502, '2026-03-30 05:35:02'),
(460, 1, 28.41387138, 77.04141403, 0, 0, 58, NULL, 20260330110517, '2026-03-30 05:35:17'),
(461, 1, 28.41387138, 77.04141403, 0, 0, 58, NULL, 20260330110518, '2026-03-30 05:35:18'),
(462, 1, 28.41387138, 77.04141403, 0, 0, 58, NULL, 20260330110526, '2026-03-30 05:35:26'),
(463, 1, 28.41387138, 77.04141403, 0, 0, 58, NULL, 20260330110534, '2026-03-30 05:35:34'),
(464, 1, 28.41387138, 77.04141403, 0, 0, 58, NULL, 20260330110542, '2026-03-30 05:35:42'),
(465, 1, 28.41387685, 77.04140913, 0, 0, 58, NULL, 20260330110553, '2026-03-30 05:35:53'),
(466, 1, 28.41387685, 77.04140913, 0, 0, 58, NULL, 20260330110558, '2026-03-30 05:35:58'),
(467, 1, 28.41383934, 77.04142051, 0, 0, 58, NULL, 20260330110644, '2026-03-30 05:36:44'),
(468, 1, 28.41383701, 77.04141764, 0, 0, 55, NULL, 20260330110744, '2026-03-30 05:37:44'),
(469, 1, 28.41383268, 77.04141409, 0, 0, 56, NULL, 20260330110843, '2026-03-30 05:38:43'),
(470, 1, 28.41382193, 77.04141378, 0, 0, 59, NULL, 20260330110944, '2026-03-30 05:39:44'),
(471, 1, 28.41385104, 77.04141546, 0, 0, 62, NULL, 20260330111044, '2026-03-30 05:40:44'),
(472, 1, 28.41386698, 77.04141711, 0, 0, 61, NULL, 20260330111144, '2026-03-30 05:41:44'),
(473, 1, 28.41384654, 77.04141939, 0, 0, 59, NULL, 20260330111244, '2026-03-30 05:42:44'),
(474, 1, 28.41385500, 77.04141389, 0, 0, 59, NULL, 20260330111344, '2026-03-30 05:43:44'),
(475, 1, 28.41387929, 77.04141269, 0, 0, 62, NULL, 20260330111443, '2026-03-30 05:44:43'),
(476, 1, 28.41385549, 77.04141087, 0, 0, 59, NULL, 20260330111543, '2026-03-30 05:45:43'),
(477, 1, 28.41391793, 77.04140025, 0, 0, 70, NULL, 20260330111643, '2026-03-30 05:46:43'),
(478, 1, 28.41389378, 77.04140796, 0, 0, 65, NULL, 20260330111743, '2026-03-30 05:47:43'),
(479, 1, 28.41385366, 77.04140469, 0, 0, 61, NULL, 20260330111843, '2026-03-30 05:48:43'),
(480, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330111948, '2026-03-30 05:49:48'),
(481, 1, 28.41385961, 77.04141649, 0, 0, 65, NULL, 20260330112044, '2026-03-30 05:50:44'),
(482, 1, 28.41386560, 77.04141016, 0, 0, 64, NULL, 20260330112144, '2026-03-30 05:51:44'),
(483, 1, 28.41385389, 77.04140371, 0, 0, 62, NULL, 20260330112243, '2026-03-30 05:52:43'),
(484, 1, 28.41383996, 77.04141065, 0, 0, 58, NULL, 20260330112348, '2026-03-30 05:53:48'),
(485, 1, 28.41383716, 77.04141548, 0, 0, 56, NULL, 20260330112444, '2026-03-30 05:54:44'),
(486, 1, 28.41384828, 77.04141216, 0, 0, 59, NULL, 20260330112543, '2026-03-30 05:55:43'),
(487, 1, 28.41387975, 77.04140735, 0, 0, 59, NULL, 20260330112643, '2026-03-30 05:56:43'),
(488, 1, 28.41390775, 77.04140681, 0, 0, 61, NULL, 20260330112743, '2026-03-30 05:57:43'),
(489, 1, 28.41388284, 77.04140643, 0, 0, 65, NULL, 20260330112843, '2026-03-30 05:58:43'),
(490, 1, 28.41392184, 77.04140629, 0, 0, 68, NULL, 20260330112944, '2026-03-30 05:59:44'),
(491, 1, 28.41398075, 77.04140604, 0, 0, 73, NULL, 20260330113044, '2026-03-30 06:00:44'),
(492, 1, 28.41386732, 77.04140913, 0, 0, 61, NULL, 20260330113144, '2026-03-30 06:01:44'),
(493, 1, 28.41387529, 77.04140477, 0, 0, 59, NULL, 20260330113244, '2026-03-30 06:02:44'),
(494, 1, 28.41389077, 77.04140253, 0, 0, 61, NULL, 20260330113347, '2026-03-30 06:03:47'),
(495, 1, 28.41390413, 77.04140319, 0, 0, 61, NULL, 20260330113444, '2026-03-30 06:04:44'),
(496, 1, 28.41391291, 77.04140608, 0, 0, 61, NULL, 20260330113543, '2026-03-30 06:05:43'),
(497, 1, 28.41387226, 77.04140753, 0, 0, 61, NULL, 20260330113644, '2026-03-30 06:06:44'),
(498, 1, 28.41381983, 77.04140729, 0, 0, 60, NULL, 20260330113744, '2026-03-30 06:07:44'),
(499, 1, 28.41382108, 77.04140510, 0, 0, 58, NULL, 20260330113844, '2026-03-30 06:08:44'),
(500, 1, 28.41386582, 77.04140812, 0, 0, 62, NULL, 20260330113944, '2026-03-30 06:09:44'),
(501, 1, 28.41382372, 77.04140502, 0, 0, 59, NULL, 20260330114044, '2026-03-30 06:10:44'),
(502, 1, 28.41381466, 77.04140866, 0, 0, 56, NULL, 20260330114143, '2026-03-30 06:11:43'),
(503, 1, 28.41386574, 77.04140825, 0, 0, 58, NULL, 20260330114243, '2026-03-30 06:12:43'),
(504, 1, 28.41388520, 77.04141069, 0, 0, 62, NULL, 20260330114344, '2026-03-30 06:13:44'),
(505, 1, 28.41388410, 77.04141820, 0, 0, 59, NULL, 20260330114444, '2026-03-30 06:14:44'),
(506, 1, 28.41388296, 77.04141008, 0, 0, 59, NULL, 20260330114543, '2026-03-30 06:15:43'),
(507, 1, 28.41392429, 77.04140597, 0, 0, 66, NULL, 20260330114644, '2026-03-30 06:16:44'),
(508, 1, 28.41393834, 77.04140526, 0, 0, 66, NULL, 20260330114744, '2026-03-30 06:17:44'),
(509, 1, 28.41390778, 77.04140231, 0, 0, 69, NULL, 20260330114843, '2026-03-30 06:18:43'),
(510, 1, 28.41392643, 77.04139997, 0, 0, 72, NULL, 20260330114944, '2026-03-30 06:19:44'),
(511, 1, 28.41390168, 77.04141178, 0, 0, 67, NULL, 20260330115048, '2026-03-30 06:20:48'),
(512, 1, 28.41386183, 77.04142097, 0, 0, 61, NULL, 20260330115143, '2026-03-30 06:21:43'),
(513, 1, 28.41388067, 77.04141526, 0, 0, 65, NULL, 20260330115243, '2026-03-30 06:22:43'),
(514, 1, 28.41395659, 77.04139940, 0, 0, 74, NULL, 20260330115344, '2026-03-30 06:23:44'),
(515, 1, 28.41383431, 77.04141873, 0, 0, 66, NULL, 20260330115444, '2026-03-30 06:24:44'),
(516, 1, 28.41384460, 77.04141994, 0, 0, 59, NULL, 20260330115543, '2026-03-30 06:25:43'),
(517, 1, 28.41389506, 77.04141150, 0, 0, 68, NULL, 20260330115643, '2026-03-30 06:26:43'),
(518, 1, 28.41389506, 77.04141150, 0, 0, 68, NULL, 20260330115743, '2026-03-30 06:27:43'),
(519, 1, 28.41389791, 77.04140335, 0, 0, 62, NULL, 20260330115843, '2026-03-30 06:28:43'),
(520, 1, 28.41386298, 77.04141300, 0, 0, 61, NULL, 20260330115944, '2026-03-30 06:29:44'),
(521, 1, 28.41392761, 77.04141641, 0, 0, 68, NULL, 20260330120043, '2026-03-30 06:30:43'),
(522, 1, 28.41388263, 77.04141558, 0, 0, 66, NULL, 20260330120143, '2026-03-30 06:31:43'),
(523, 1, 28.41386687, 77.04142048, 0, 0, 67, NULL, 20260330120243, '2026-03-30 06:32:43'),
(524, 1, 28.41385571, 77.04139970, 0, 0, 67, NULL, 20260330120344, '2026-03-30 06:33:44'),
(525, 1, 28.41381985, 77.04139988, 0, 0, 65, NULL, 20260330120444, '2026-03-30 06:34:44'),
(526, 1, 28.41391341, 77.04140371, 0, 0, 68, NULL, 20260330120543, '2026-03-30 06:35:43'),
(527, 1, 28.41387753, 77.04140376, 0, 0, 65, NULL, 20260330120643, '2026-03-30 06:36:43'),
(528, 1, 28.41387052, 77.04140595, 0, 0, 65, NULL, 20260330120743, '2026-03-30 06:37:43'),
(529, 1, 28.41395254, 77.04141667, 0, 0, 74, NULL, 20260330120844, '2026-03-30 06:38:44'),
(530, 1, 28.41388038, 77.04140137, 0, 0, 68, NULL, 20260330120951, '2026-03-30 06:39:51'),
(531, 1, 28.41389939, 77.04139804, 0, 0, 67, NULL, 20260330121044, '2026-03-30 06:40:44'),
(532, 1, 28.41387663, 77.04140993, 0, 0, 68, NULL, 20260330121143, '2026-03-30 06:41:43'),
(533, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330121248, '2026-03-30 06:42:48'),
(534, 1, 28.41385693, 77.04139585, 0, 0, 76, NULL, 20260330121343, '2026-03-30 06:43:43'),
(535, 1, 28.41386457, 77.04141397, 0, 0, 74, NULL, 20260330121444, '2026-03-30 06:44:44'),
(536, 1, 28.41394321, 77.04141444, 0, 0, 74, NULL, 20260330121544, '2026-03-30 06:45:44'),
(537, 1, 28.41393574, 77.04140507, 0, 0, 65, NULL, 20260330121644, '2026-03-30 06:46:44'),
(538, 1, 28.41390727, 77.04140699, 0, 0, 69, NULL, 20260330121748, '2026-03-30 06:47:48'),
(539, 1, 28.41392908, 77.04140334, 0, 0, 70, NULL, 20260330121847, '2026-03-30 06:48:47'),
(540, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330121948, '2026-03-30 06:49:48'),
(541, 1, 28.41392441, 77.04140105, 0, 0, 78, NULL, 20260330122047, '2026-03-30 06:50:47'),
(542, 1, 28.41389784, 77.04140690, 0, 0, 66, NULL, 20260330122144, '2026-03-30 06:51:44'),
(543, 1, 28.41392938, 77.04140431, 0, 0, 69, NULL, 20260330122248, '2026-03-30 06:52:48'),
(544, 1, 28.41390786, 77.04140774, 0, 0, 61, NULL, 20260330122343, '2026-03-30 06:53:43'),
(545, 1, 28.41390120, 77.04140839, 0, 0, 62, NULL, 20260330122444, '2026-03-30 06:54:44'),
(546, 1, 28.41384003, 77.04140557, 0, 0, 59, NULL, 20260330122544, '2026-03-30 06:55:44'),
(547, 1, 28.41384758, 77.04141391, 0, 0, 61, NULL, 20260330122644, '2026-03-30 06:56:44'),
(548, 1, 28.41391118, 77.04140695, 0, 0, 65, NULL, 20260330122744, '2026-03-30 06:57:44'),
(549, 1, 28.41389460, 77.04140779, 0, 0, 62, NULL, 20260330122844, '2026-03-30 06:58:44'),
(550, 1, 28.41390567, 77.04141446, 0, 0, 69, NULL, 20260330122944, '2026-03-30 06:59:44'),
(551, 1, 28.41393859, 77.04140563, 0, 0, 71, NULL, 20260330123138, '2026-03-30 07:01:38'),
(552, 1, 28.41388701, 77.04140456, 0, 0, 59, NULL, 20260330123244, '2026-03-30 07:02:44'),
(553, 1, 28.41385804, 77.04140575, 0, 0, 58, NULL, 20260330123344, '2026-03-30 07:03:44'),
(554, 1, 28.41384984, 77.04141103, 0, 0, 59, NULL, 20260330123448, '2026-03-30 07:04:48'),
(555, 1, 28.41386469, 77.04141107, 0, 0, 64, NULL, 20260330123544, '2026-03-30 07:05:44'),
(556, 1, 28.41387081, 77.04141097, 0, 0, 64, NULL, 20260330123644, '2026-03-30 07:06:44'),
(557, 1, 28.41390361, 77.04140851, 0, 0, 61, NULL, 20260330123748, '2026-03-30 07:07:48'),
(558, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330123848, '2026-03-30 07:08:48'),
(559, 1, 28.41382610, 77.04140993, 0, 0, 60, NULL, 20260330123948, '2026-03-30 07:09:48'),
(560, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330124048, '2026-03-30 07:10:48'),
(561, 1, 28.41392062, 77.04140779, 0, 0, 68, NULL, 20260330124147, '2026-03-30 07:11:47');
INSERT INTO `engineer_locations` (`id`, `engineer_id`, `latitude`, `longitude`, `speed`, `heading`, `accuracy`, `job_id`, `timestamp`, `created_at`) VALUES
(562, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330124248, '2026-03-30 07:12:48'),
(563, 1, 28.41385700, 77.04140921, 0, 0, 58, NULL, 20260330124347, '2026-03-30 07:13:47'),
(564, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330124448, '2026-03-30 07:14:48'),
(565, 1, 28.41387295, 77.04140904, 0, 0, 68, NULL, 20260330124544, '2026-03-30 07:15:44'),
(566, 1, 28.41386632, 77.04141467, 0, 0, 61, NULL, 20260330124644, '2026-03-30 07:16:44'),
(567, 1, 28.41385703, 77.04141439, 0, 0, 61, NULL, 20260330124743, '2026-03-30 07:17:43'),
(568, 1, 28.41383365, 77.04140929, 0, 0, 61, NULL, 20260330124844, '2026-03-30 07:18:44'),
(569, 1, 28.41383187, 77.04141117, 0, 0, 61, NULL, 20260330124944, '2026-03-30 07:19:44'),
(570, 1, 28.41387463, 77.04140722, 0, 0, 60, NULL, 20260330125044, '2026-03-30 07:20:44'),
(571, 1, 28.41386914, 77.04140611, 0, 0, 58, NULL, 20260330125144, '2026-03-30 07:21:44'),
(572, 1, 28.41386241, 77.04140941, 0, 0, 58, NULL, 20260330125244, '2026-03-30 07:22:44'),
(573, 1, 28.41383829, 77.04141288, 0, 0, 58, NULL, 20260330125344, '2026-03-30 07:23:44'),
(574, 1, 28.41385131, 77.04140634, 0, 0, 61, NULL, 20260330125444, '2026-03-30 07:24:44'),
(575, 1, 28.41382571, 77.04140908, 0, 0, 62, NULL, 20260330125708, '2026-03-30 07:27:08'),
(576, 1, 28.41384551, 77.04141028, 0, 0, 60, NULL, 20260330125744, '2026-03-30 07:27:44'),
(577, 1, 28.41385296, 77.04141088, 0, 0, 56, NULL, 20260330125844, '2026-03-30 07:28:44'),
(578, 1, 28.41385287, 77.04140744, 0, 0, 58, NULL, 20260330125943, '2026-03-30 07:29:43'),
(579, 1, 28.41387715, 77.04140675, 0, 0, 60, NULL, 20260330130044, '2026-03-30 07:30:44'),
(580, 1, 28.55240000, 77.26740000, 0, 0, 30225, NULL, 20260330130148, '2026-03-30 07:31:48'),
(581, 1, 28.41381694, 77.04142295, 0, 0, 56, NULL, 20260330130541, '2026-03-30 07:35:41'),
(582, 1, 28.41383064, 77.04141971, 0, 0, 55, NULL, 20260330130641, '2026-03-30 07:36:41'),
(583, 1, 28.41387015, 77.04140541, 0, 0, 59, NULL, 20260330130742, '2026-03-30 07:37:42'),
(584, 1, 28.41391875, 77.04141402, 0, 0, 61, NULL, 20260330130842, '2026-03-30 07:38:42'),
(585, 1, 28.41389975, 77.04141717, 0, 0, 63, NULL, 20260330130944, '2026-03-30 07:39:44'),
(586, 1, 28.41392397, 77.04142015, 0, 0, 68, NULL, 20260330131044, '2026-03-30 07:40:44'),
(587, 1, 28.41391806, 77.04140798, 0, 0, 68, NULL, 20260330131143, '2026-03-30 07:41:43'),
(588, 1, 28.41391465, 77.04140409, 0, 0, 68, NULL, 20260330131244, '2026-03-30 07:42:44'),
(589, 1, 28.41390459, 77.04140015, 0, 0, 69, NULL, 20260330131344, '2026-03-30 07:43:44'),
(590, 1, 28.41389224, 77.04140852, 0, 0, 67, NULL, 20260330131444, '2026-03-30 07:44:44'),
(591, 1, 28.41393298, 77.04140870, 0, 0, 71, NULL, 20260330131544, '2026-03-30 07:45:44'),
(592, 1, 28.41389641, 77.04141072, 0, 0, 61, NULL, 20260330131643, '2026-03-30 07:46:43'),
(593, 1, 28.41386504, 77.04141932, 0, 0, 60, NULL, 20260330131744, '2026-03-30 07:47:44'),
(594, 1, 28.41384497, 77.04142319, 0, 0, 61, NULL, 20260330131844, '2026-03-30 07:48:44'),
(595, 1, 28.41387959, 77.04141570, 0, 0, 59, NULL, 20260330131944, '2026-03-30 07:49:44'),
(596, 1, 28.41390830, 77.04141472, 0, 0, 61, NULL, 20260330132044, '2026-03-30 07:50:44'),
(597, 1, 28.41387580, 77.04142369, 0, 0, 61, NULL, 20260330132144, '2026-03-30 07:51:44'),
(598, 1, 28.41386374, 77.04141568, 0, 0, 58, NULL, 20260330132244, '2026-03-30 07:52:44'),
(599, 1, 28.41386286, 77.04141035, 0, 0, 58, NULL, 20260330132343, '2026-03-30 07:53:43'),
(600, 1, 28.41389138, 77.04141407, 0, 0, 59, NULL, 20260330132444, '2026-03-30 07:54:44'),
(601, 1, 28.41391009, 77.04141058, 0, 0, 61, NULL, 20260330132544, '2026-03-30 07:55:44'),
(602, 1, 28.41388032, 77.04140884, 0, 0, 60, NULL, 20260330132644, '2026-03-30 07:56:44'),
(603, 1, 28.41388793, 77.04141199, 0, 0, 60, NULL, 20260330132748, '2026-03-30 07:57:48'),
(604, 1, 28.41388793, 77.04141199, 0, 0, 60, NULL, 20260330132841, '2026-03-30 07:58:41'),
(605, 1, 28.47532508, 77.06154876, 0, 0, 74, NULL, 20260401005024, '2026-03-31 19:20:24'),
(606, 1, 28.47532508, 77.06154876, 0, 0, 74, NULL, 20260401005029, '2026-03-31 19:20:29'),
(607, 1, 28.47532508, 77.06154876, 0, 0, 74, NULL, 20260401005037, '2026-03-31 19:20:37'),
(608, 1, 28.47532508, 77.06154876, 0, 0, 74, NULL, 20260401005039, '2026-03-31 19:20:39'),
(609, 1, 28.47532508, 77.06154876, 0, 0, 74, NULL, 20260401005047, '2026-03-31 19:20:47'),
(610, 1, 28.47532165, 77.06155454, 0, 0, 74, NULL, 20260401005057, '2026-03-31 19:20:57'),
(611, 1, 28.47532165, 77.06155454, 0, 0, 74, NULL, 20260401005103, '2026-03-31 19:21:03'),
(612, 1, 28.47532165, 77.06155454, 0, 0, 74, NULL, 20260401005111, '2026-03-31 19:21:11'),
(613, 1, 28.47532165, 77.06155454, 0, 0, 74, NULL, 20260401005119, '2026-03-31 19:21:19'),
(614, 1, 28.47532165, 77.06155454, 0, 0, 74, NULL, 20260401005127, '2026-03-31 19:21:27'),
(615, 1, 28.47529904, 77.06155579, 0, 0, 74, NULL, 20260401005140, '2026-03-31 19:21:40'),
(616, 1, 28.47529904, 77.06155579, 0, 0, 74, NULL, 20260401005143, '2026-03-31 19:21:43'),
(617, 1, 28.47529904, 77.06155579, 0, 0, 74, NULL, 20260401005151, '2026-03-31 19:21:51'),
(618, 1, 28.47529904, 77.06155579, 0, 0, 74, NULL, 20260401005159, '2026-03-31 19:21:59'),
(619, 1, 28.47529904, 77.06155579, 0, 0, 74, NULL, 20260401005207, '2026-03-31 19:22:07'),
(620, 1, 28.47534180, 77.06157089, 0, 0, 70, NULL, 20260401005217, '2026-03-31 19:22:17'),
(621, 1, 28.47534180, 77.06157089, 0, 0, 70, NULL, 20260401005223, '2026-03-31 19:22:23'),
(622, 1, 28.47534180, 77.06157089, 0, 0, 70, NULL, 20260401005231, '2026-03-31 19:22:31'),
(623, 1, 28.47534180, 77.06157089, 0, 0, 70, NULL, 20260401005239, '2026-03-31 19:22:39'),
(624, 1, 28.47534180, 77.06157089, 0, 0, 70, NULL, 20260401005247, '2026-03-31 19:22:47'),
(625, 1, 28.47536913, 77.06155401, 0, 0, 70, NULL, 20260401005258, '2026-03-31 19:22:58'),
(626, 1, 28.47536913, 77.06155401, 0, 0, 70, NULL, 20260401005303, '2026-03-31 19:23:03'),
(627, 1, 28.47536913, 77.06155401, 0, 0, 70, NULL, 20260401005311, '2026-03-31 19:23:11'),
(628, 1, 28.47536913, 77.06155401, 0, 0, 70, NULL, 20260401005319, '2026-03-31 19:23:19'),
(629, 1, 28.47536913, 77.06155401, 0, 0, 70, NULL, 20260401005327, '2026-03-31 19:23:27'),
(630, 1, 28.47538711, 77.06156879, 0, 0, 70, NULL, 20260401005338, '2026-03-31 19:23:38'),
(631, 1, 28.47538711, 77.06156879, 0, 0, 70, NULL, 20260401005343, '2026-03-31 19:23:43'),
(632, 1, 28.47538711, 77.06156879, 0, 0, 70, NULL, 20260401005351, '2026-03-31 19:23:51'),
(633, 1, 28.47538711, 77.06156879, 0, 0, 70, NULL, 20260401005359, '2026-03-31 19:23:59'),
(634, 1, 28.47538711, 77.06156879, 0, 0, 70, NULL, 20260401005407, '2026-03-31 19:24:07'),
(635, 1, 28.47534189, 77.06156074, 0, 0, 71, NULL, 20260401005417, '2026-03-31 19:24:17'),
(636, 1, 28.47534189, 77.06156074, 0, 0, 71, NULL, 20260401005423, '2026-03-31 19:24:23'),
(637, 1, 28.47534189, 77.06156074, 0, 0, 71, NULL, 20260401005431, '2026-03-31 19:24:31'),
(638, 1, 28.47534189, 77.06156074, 0, 0, 71, NULL, 20260401005439, '2026-03-31 19:24:39'),
(639, 1, 28.47534189, 77.06156074, 0, 0, 71, NULL, 20260401005447, '2026-03-31 19:24:47'),
(640, 1, 28.47533906, 77.06155249, 0, 0, 71, NULL, 20260401005459, '2026-03-31 19:24:59'),
(641, 1, 28.47533906, 77.06155249, 0, 0, 71, NULL, 20260401005503, '2026-03-31 19:25:03'),
(642, 1, 28.47533906, 77.06155249, 0, 0, 71, NULL, 20260401005511, '2026-03-31 19:25:11'),
(643, 1, 28.47533906, 77.06155249, 0, 0, 71, NULL, 20260401005519, '2026-03-31 19:25:19'),
(644, 1, 28.47533906, 77.06155249, 0, 0, 71, NULL, 20260401005527, '2026-03-31 19:25:27'),
(645, 1, 28.47537607, 77.06153968, 0, 0, 70, NULL, 20260401005542, '2026-03-31 19:25:42'),
(646, 1, 28.47537607, 77.06153968, 0, 0, 70, NULL, 20260401005543, '2026-03-31 19:25:43'),
(647, 1, 28.47537607, 77.06153968, 0, 0, 70, NULL, 20260401005551, '2026-03-31 19:25:51'),
(648, 1, 28.47537607, 77.06153968, 0, 0, 70, NULL, 20260401005559, '2026-03-31 19:25:59'),
(649, 1, 28.47537501, 77.06153831, 0, 0, 70, NULL, 20260401005638, '2026-03-31 19:26:38'),
(650, 1, 28.47531584, 77.06155383, 0, 0, 71, NULL, 20260401005738, '2026-03-31 19:27:38'),
(651, 1, 28.47534428, 77.06156477, 0, 0, 71, NULL, 20260401010550, '2026-03-31 19:35:50'),
(652, 1, 28.47534428, 77.06156477, 0, 0, 71, NULL, 20260401010556, '2026-03-31 19:35:56'),
(653, 1, 28.47534428, 77.06156477, 0, 0, 71, NULL, 20260401010604, '2026-03-31 19:36:04'),
(654, 1, 28.47534428, 77.06156477, 0, 0, 71, NULL, 20260401010613, '2026-03-31 19:36:13'),
(655, 1, 28.47532822, 77.06153673, 0, 0, 66, NULL, 20260401010624, '2026-03-31 19:36:24'),
(656, 1, 28.47532822, 77.06153673, 0, 0, 66, NULL, 20260401010629, '2026-03-31 19:36:29'),
(657, 1, 28.47532822, 77.06153673, 0, 0, 66, NULL, 20260401010637, '2026-03-31 19:36:37'),
(658, 1, 28.47532822, 77.06153673, 0, 0, 66, NULL, 20260401010645, '2026-03-31 19:36:45'),
(659, 1, 28.47532822, 77.06153673, 0, 0, 66, NULL, 20260401010652, '2026-03-31 19:36:52'),
(660, 1, 28.47534504, 77.06153004, 0, 0, 66, NULL, 20260401010708, '2026-03-31 19:37:08'),
(661, 1, 28.47534504, 77.06153004, 0, 0, 66, NULL, 20260401010708, '2026-03-31 19:37:08'),
(662, 1, 28.47534504, 77.06153004, 0, 0, 66, NULL, 20260401010716, '2026-03-31 19:37:16'),
(663, 1, 28.47534504, 77.06153004, 0, 0, 66, NULL, 20260401010725, '2026-03-31 19:37:25'),
(664, 1, 28.47534504, 77.06153004, 0, 0, 66, NULL, 20260401010733, '2026-03-31 19:37:33'),
(665, 1, 28.47532530, 77.06153219, 0, 0, 66, NULL, 20260401010743, '2026-03-31 19:37:43'),
(666, 1, 28.47532530, 77.06153219, 0, 0, 66, NULL, 20260401010749, '2026-03-31 19:37:49'),
(667, 1, 28.47532530, 77.06153219, 0, 0, 66, NULL, 20260401010756, '2026-03-31 19:37:56'),
(668, 1, 28.47532530, 77.06153219, 0, 0, 66, NULL, 20260401010804, '2026-03-31 19:38:04'),
(669, 1, 28.47532530, 77.06153219, 0, 0, 66, NULL, 20260401010812, '2026-03-31 19:38:12'),
(670, 1, 28.47533941, 77.06159789, 0, 0, 68, NULL, 20260401010822, '2026-03-31 19:38:22'),
(671, 1, 28.47533941, 77.06159789, 0, 0, 68, NULL, 20260401010828, '2026-03-31 19:38:28'),
(672, 1, 28.47533941, 77.06159789, 0, 0, 68, NULL, 20260401010836, '2026-03-31 19:38:36'),
(673, 1, 28.47533941, 77.06159789, 0, 0, 68, NULL, 20260401010844, '2026-03-31 19:38:44'),
(674, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010854, '2026-03-31 19:38:54'),
(675, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010900, '2026-03-31 19:39:00'),
(676, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010908, '2026-03-31 19:39:08'),
(677, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010916, '2026-03-31 19:39:16'),
(678, 1, 28.56480000, 76.98220000, 0, 0, 55403, NULL, 20260401010931, '2026-03-31 19:39:31'),
(679, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010932, '2026-03-31 19:39:32'),
(680, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010940, '2026-03-31 19:39:40'),
(681, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010948, '2026-03-31 19:39:48'),
(682, 1, 28.47533068, 77.06161545, 0, 0, 68, NULL, 20260401010956, '2026-03-31 19:39:56'),
(683, 1, 28.47530413, 77.06164796, 0, 0, 68, NULL, 20260401011006, '2026-03-31 19:40:06'),
(684, 1, 28.47530413, 77.06164796, 0, 0, 68, NULL, 20260401011012, '2026-03-31 19:40:12'),
(685, 1, 28.47530413, 77.06164796, 0, 0, 68, NULL, 20260401011020, '2026-03-31 19:40:20'),
(686, 1, 28.47530413, 77.06164796, 0, 0, 68, NULL, 20260401011028, '2026-03-31 19:40:28'),
(687, 1, 28.47530413, 77.06164796, 0, 0, 68, NULL, 20260401011036, '2026-03-31 19:40:36'),
(688, 1, 28.47531668, 77.06162719, 0, 0, 68, NULL, 20260401011051, '2026-03-31 19:40:51'),
(689, 1, 28.47531668, 77.06162719, 0, 0, 68, NULL, 20260401011052, '2026-03-31 19:40:52'),
(690, 1, 28.47531668, 77.06162719, 0, 0, 68, NULL, 20260401011100, '2026-03-31 19:41:00'),
(691, 1, 28.47531668, 77.06162719, 0, 0, 68, NULL, 20260401011108, '2026-03-31 19:41:08'),
(692, 1, 28.47531668, 77.06162719, 0, 0, 68, NULL, 20260401011116, '2026-03-31 19:41:16'),
(693, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011126, '2026-03-31 19:41:26'),
(694, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011132, '2026-03-31 19:41:32'),
(695, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011140, '2026-03-31 19:41:40'),
(696, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011148, '2026-03-31 19:41:48'),
(697, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011158, '2026-03-31 19:41:58'),
(698, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011205, '2026-03-31 19:42:05'),
(699, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011213, '2026-03-31 19:42:13'),
(700, 1, 28.47533346, 77.06162928, 0, 0, 68, NULL, 20260401011221, '2026-03-31 19:42:21'),
(701, 1, 28.47536369, 77.06159422, 0, 0, 68, NULL, 20260401011232, '2026-03-31 19:42:32'),
(702, 1, 28.47536369, 77.06159422, 0, 0, 68, NULL, 20260401011237, '2026-03-31 19:42:37'),
(703, 1, 28.47536369, 77.06159422, 0, 0, 68, NULL, 20260401011245, '2026-03-31 19:42:45'),
(704, 1, 28.47536369, 77.06159422, 0, 0, 68, NULL, 20260401011253, '2026-03-31 19:42:53'),
(705, 1, 28.47537700, 77.06157137, 0, 0, 68, NULL, 20260401011537, '2026-03-31 19:45:37'),
(706, 1, 28.47533949, 77.06163330, 0, 0, 68, NULL, 20260401011637, '2026-03-31 19:46:37'),
(707, 1, 28.47531406, 77.06155087, 0, 0, 66, NULL, 20260401011738, '2026-03-31 19:47:38'),
(708, 1, 28.47531357, 77.06152532, 0, 0, 66, NULL, 20260401011837, '2026-03-31 19:48:37'),
(709, 1, 28.47532589, 77.06159486, 0, 0, 70, NULL, 20260401011937, '2026-03-31 19:49:37'),
(710, 1, 28.47534007, 77.06162327, 0, 0, 71, NULL, 20260401012038, '2026-03-31 19:50:38'),
(711, 1, 28.47534007, 77.06162327, 0, 0, 71, NULL, 20260401012048, '2026-03-31 19:50:48'),
(712, 1, 28.47534007, 77.06162327, 0, 0, 71, NULL, 20260401012053, '2026-03-31 19:50:53'),
(713, 1, 28.47534007, 77.06162327, 0, 0, 71, NULL, 20260401012101, '2026-03-31 19:51:01'),
(714, 1, 28.47536084, 77.06159894, 0, 0, 68, NULL, 20260401012111, '2026-03-31 19:51:11'),
(715, 1, 28.47536084, 77.06159894, 0, 0, 68, NULL, 20260401012117, '2026-03-31 19:51:17'),
(716, 1, 28.47536084, 77.06159894, 0, 0, 68, NULL, 20260401012125, '2026-03-31 19:51:25'),
(717, 1, 28.47536084, 77.06159894, 0, 0, 68, NULL, 20260401012133, '2026-03-31 19:51:33'),
(718, 1, 28.47536084, 77.06159894, 0, 0, 68, NULL, 20260401012141, '2026-03-31 19:51:41'),
(719, 1, 28.47534013, 77.06163397, 0, 0, 68, NULL, 20260401012152, '2026-03-31 19:51:52'),
(720, 1, 28.47534013, 77.06163397, 0, 0, 68, NULL, 20260401012237, '2026-03-31 19:52:37'),
(721, 1, 28.47534013, 77.06163397, 0, 0, 68, NULL, 20260401012337, '2026-03-31 19:53:37'),
(722, 1, 28.47534931, 77.06161730, 0, 0, 68, NULL, 20260401012437, '2026-03-31 19:54:37'),
(723, 1, 28.47534146, 77.06160771, 0, 0, 70, NULL, 20260401012536, '2026-03-31 19:55:36'),
(724, 1, 28.47531757, 77.06155188, 0, 0, 66, NULL, 20260401012638, '2026-03-31 19:56:38'),
(725, 1, 28.47538686, 77.06149682, 0, 0, 66, NULL, 20260401012737, '2026-03-31 19:57:37'),
(726, 1, 28.47538355, 77.06156736, 0, 0, 70, NULL, 20260401012837, '2026-03-31 19:58:37'),
(727, 1, 28.47538355, 77.06156736, 0, 0, 70, NULL, 20260401012844, '2026-03-31 19:58:44'),
(728, 1, 28.47538355, 77.06156736, 0, 0, 70, NULL, 20260401012847, '2026-03-31 19:58:47'),
(729, 1, 28.47538355, 77.06156736, 0, 0, 70, NULL, 20260401012855, '2026-03-31 19:58:55'),
(730, 1, 28.47538355, 77.06156736, 0, 0, 70, NULL, 20260401012903, '2026-03-31 19:59:03'),
(731, 1, 28.47535469, 77.06159879, 0, 0, 68, NULL, 20260401012913, '2026-03-31 19:59:13'),
(732, 1, 28.47535469, 77.06159879, 0, 0, 68, NULL, 20260401012919, '2026-03-31 19:59:19'),
(733, 1, 28.47535469, 77.06159879, 0, 0, 68, NULL, 20260401012927, '2026-03-31 19:59:27'),
(734, 1, 28.47535469, 77.06159879, 0, 0, 68, NULL, 20260401012936, '2026-03-31 19:59:36'),
(735, 1, 28.47531505, 77.06157717, 0, 0, 66, NULL, 20260401012946, '2026-03-31 19:59:46'),
(736, 1, 28.47531505, 77.06157717, 0, 0, 66, NULL, 20260401012952, '2026-03-31 19:59:52'),
(737, 1, 28.47531505, 77.06157717, 0, 0, 66, NULL, 20260401013000, '2026-03-31 20:00:00'),
(738, 1, 28.47531505, 77.06157717, 0, 0, 66, NULL, 20260401013008, '2026-03-31 20:00:08'),
(739, 1, 28.47531505, 77.06157717, 0, 0, 66, NULL, 20260401013016, '2026-03-31 20:00:16'),
(740, 1, 28.56480000, 76.98220000, 0, 0, 55403, NULL, 20260401013030, '2026-03-31 20:00:30'),
(741, 1, 28.47530821, 77.06159378, 0, 0, 66, NULL, 20260401013031, '2026-03-31 20:00:31'),
(742, 1, 28.47530821, 77.06159378, 0, 0, 66, NULL, 20260401013040, '2026-03-31 20:00:40'),
(743, 1, 28.47530821, 77.06159378, 0, 0, 66, NULL, 20260401013047, '2026-03-31 20:00:47'),
(744, 1, 28.47530821, 77.06159378, 0, 0, 66, NULL, 20260401013056, '2026-03-31 20:00:56'),
(745, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013104, '2026-03-31 20:01:04'),
(746, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013111, '2026-03-31 20:01:11'),
(747, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013112, '2026-03-31 20:01:12'),
(748, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013113, '2026-03-31 20:01:13'),
(749, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013121, '2026-03-31 20:01:21'),
(750, 1, 28.47532615, 77.06156102, 0, 0, 66, NULL, 20260401013130, '2026-03-31 20:01:30'),
(751, 1, 28.47530584, 77.06158075, 0, 0, 66, NULL, 20260401013139, '2026-03-31 20:01:39'),
(752, 1, 28.47530584, 77.06158075, 0, 0, 66, NULL, 20260401013145, '2026-03-31 20:01:45'),
(753, 1, 28.47530584, 77.06158075, 0, 0, 66, NULL, 20260401013153, '2026-03-31 20:01:53'),
(754, 1, 28.47530584, 77.06158075, 0, 0, 66, NULL, 20260401013201, '2026-03-31 20:02:01'),
(755, 1, 28.47533567, 77.06161738, 0, 0, 68, NULL, 20260401013213, '2026-03-31 20:02:13'),
(756, 1, 28.47533567, 77.06161738, 0, 0, 68, NULL, 20260401013217, '2026-03-31 20:02:17'),
(757, 1, 28.47533567, 77.06161738, 0, 0, 68, NULL, 20260401013225, '2026-03-31 20:02:25'),
(758, 1, 28.47533567, 77.06161738, 0, 0, 68, NULL, 20260401013232, '2026-03-31 20:02:32'),
(759, 1, 28.47533567, 77.06161738, 0, 0, 68, NULL, 20260401013241, '2026-03-31 20:02:41'),
(760, 1, 28.47535688, 77.06158307, 0, 0, 68, NULL, 20260401013254, '2026-03-31 20:02:54'),
(761, 1, 28.47535688, 77.06158307, 0, 0, 68, NULL, 20260401013257, '2026-03-31 20:02:57'),
(762, 1, 28.47535688, 77.06158307, 0, 0, 68, NULL, 20260401013305, '2026-03-31 20:03:05'),
(763, 1, 28.47535688, 77.06158307, 0, 0, 68, NULL, 20260401013313, '2026-03-31 20:03:13'),
(764, 1, 28.47535688, 77.06158307, 0, 0, 68, NULL, 20260401013321, '2026-03-31 20:03:21'),
(765, 1, 28.47533380, 77.06156387, 0, 0, 66, NULL, 20260401013334, '2026-03-31 20:03:34'),
(766, 1, 28.47533380, 77.06156387, 0, 0, 66, NULL, 20260401013337, '2026-03-31 20:03:37'),
(767, 1, 28.47533380, 77.06156387, 0, 0, 66, NULL, 20260401013345, '2026-03-31 20:03:45'),
(768, 1, 28.47533380, 77.06156387, 0, 0, 66, NULL, 20260401013353, '2026-03-31 20:03:53'),
(769, 1, 28.47533380, 77.06156387, 0, 0, 66, NULL, 20260401013401, '2026-03-31 20:04:01'),
(770, 1, 28.47535300, 77.06154647, 0, 0, 66, NULL, 20260401013439, '2026-03-31 20:04:39'),
(771, 1, 28.47535300, 77.06154647, 0, 0, 66, NULL, 20260401013444, '2026-03-31 20:04:44'),
(772, 1, 28.47535300, 77.06154647, 0, 0, 66, NULL, 20260401013448, '2026-03-31 20:04:48'),
(773, 1, 28.47535300, 77.06154647, 0, 0, 66, NULL, 20260401013457, '2026-03-31 20:04:57'),
(774, 1, 28.47535300, 77.06154647, 0, 0, 66, NULL, 20260401013505, '2026-03-31 20:05:05'),
(775, 1, 28.47531672, 77.06156908, 0, 0, 66, NULL, 20260401013517, '2026-03-31 20:05:17'),
(776, 1, 28.47531672, 77.06156908, 0, 0, 66, NULL, 20260401013521, '2026-03-31 20:05:21'),
(777, 1, 28.47531672, 77.06156908, 0, 0, 66, NULL, 20260401013529, '2026-03-31 20:05:29'),
(778, 1, 28.47531672, 77.06156908, 0, 0, 66, NULL, 20260401013537, '2026-03-31 20:05:37'),
(779, 1, 28.47531672, 77.06156908, 0, 0, 66, NULL, 20260401013545, '2026-03-31 20:05:45'),
(780, 1, 28.47533982, 77.06159229, 0, 0, 68, NULL, 20260401013556, '2026-03-31 20:05:56'),
(781, 1, 28.47535841, 77.06157747, 0, 0, 68, NULL, 20260401013638, '2026-03-31 20:06:38'),
(782, 1, 28.47534046, 77.06163066, 0, 0, 68, NULL, 20260401013739, '2026-03-31 20:07:39'),
(783, 1, 28.47538902, 77.06156310, 0, 0, 70, NULL, 20260401013837, '2026-03-31 20:08:37'),
(784, 1, 28.47534584, 77.06165552, 0, 0, 73, NULL, 20260401070836, '2026-04-01 01:38:36'),
(785, 1, 28.47533180, 77.06165462, 0, 0, 79, NULL, 20260401070939, '2026-04-01 01:39:39'),
(786, 1, 28.47534006, 77.06155207, 0, 0, 68, NULL, 20260401071037, '2026-04-01 01:40:37'),
(787, 1, 28.47534731, 77.06154836, 0, 0, 68, NULL, 20260401071138, '2026-04-01 01:41:38'),
(788, 1, 28.47533586, 77.06165466, 0, 0, 74, NULL, 20260401071236, '2026-04-01 01:42:36'),
(789, 1, 28.47529231, 77.06167023, 0, 0, 72, NULL, 20260401071339, '2026-04-01 01:43:39'),
(790, 1, 28.47530071, 77.06167445, 0, 0, 72, NULL, 20260401071439, '2026-04-01 01:44:39'),
(791, 1, 28.47524791, 77.06171686, 0, 0, 70, NULL, 20260401071539, '2026-04-01 01:45:39'),
(792, 1, 28.47525568, 77.06167208, 0, 0, 68, NULL, 20260401071636, '2026-04-01 01:46:36'),
(793, 1, 28.47528720, 77.06165084, 0, 0, 70, NULL, 20260401071738, '2026-04-01 01:47:38'),
(794, 1, 28.47527180, 77.06173124, 0, 0, 71, NULL, 20260401071841, '2026-04-01 01:48:41'),
(795, 1, 28.47528059, 77.06167715, 0, 0, 72, NULL, 20260401071937, '2026-04-01 01:49:37'),
(796, 1, 28.47529616, 77.06164629, 0, 0, 70, NULL, 20260401072018, '2026-04-01 01:50:18'),
(797, 1, 28.47529616, 77.06164629, 0, 0, 70, NULL, 20260401072018, '2026-04-01 01:50:18'),
(798, 1, 28.47529616, 77.06164629, 0, 0, 70, NULL, 20260401072025, '2026-04-01 01:50:25'),
(799, 1, 28.47529616, 77.06164629, 0, 0, 70, NULL, 20260401072033, '2026-04-01 01:50:33'),
(800, 1, 28.47529616, 77.06164629, 0, 0, 70, NULL, 20260401072041, '2026-04-01 01:50:41'),
(801, 1, 28.47533161, 77.06156387, 0, 0, 68, NULL, 20260401072055, '2026-04-01 01:50:55'),
(802, 1, 28.47533161, 77.06156387, 0, 0, 68, NULL, 20260401072057, '2026-04-01 01:50:57'),
(803, 1, 28.47533161, 77.06156387, 0, 0, 68, NULL, 20260401072105, '2026-04-01 01:51:05'),
(804, 1, 28.47538579, 77.06155896, 0, 0, 70, NULL, 20260401072137, '2026-04-01 01:51:37'),
(805, 1, 28.47537361, 77.06157838, 0, 0, 70, NULL, 20260401072238, '2026-04-01 01:52:38'),
(806, 1, 28.47536764, 77.06162477, 0, 0, 70, NULL, 20260401072340, '2026-04-01 01:53:40'),
(807, 1, 28.47537611, 77.06157717, 0, 0, 70, NULL, 20260401072436, '2026-04-01 01:54:36'),
(808, 1, 28.47541389, 77.06152813, 0, 0, 71, NULL, 20260401072537, '2026-04-01 01:55:37'),
(809, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072622, '2026-04-01 01:56:22'),
(810, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072628, '2026-04-01 01:56:28'),
(811, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072637, '2026-04-01 01:56:37'),
(812, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072645, '2026-04-01 01:56:45'),
(813, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072650, '2026-04-01 01:56:50'),
(814, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072651, '2026-04-01 01:56:51'),
(815, 1, 28.47542152, 77.06151152, 0, 0, 71, NULL, 20260401072652, '2026-04-01 01:56:52'),
(816, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072703, '2026-04-01 01:57:03'),
(817, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072708, '2026-04-01 01:57:08'),
(818, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072716, '2026-04-01 01:57:16'),
(819, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072720, '2026-04-01 01:57:20'),
(820, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072728, '2026-04-01 01:57:28'),
(821, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072737, '2026-04-01 01:57:37'),
(822, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072744, '2026-04-01 01:57:44'),
(823, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072752, '2026-04-01 01:57:52'),
(824, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072800, '2026-04-01 01:58:00'),
(825, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072814, '2026-04-01 01:58:14'),
(826, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072816, '2026-04-01 01:58:16'),
(827, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072818, '2026-04-01 01:58:18'),
(828, 1, 28.47542370, 77.06153756, 0, 0, 71, NULL, 20260401072826, '2026-04-01 01:58:26'),
(829, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405140952, '2026-04-05 08:39:52'),
(830, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405140952, '2026-04-05 08:39:52'),
(831, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405140953, '2026-04-05 08:39:53'),
(832, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141002, '2026-04-05 08:40:02'),
(833, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141010, '2026-04-05 08:40:10'),
(834, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141018, '2026-04-05 08:40:18'),
(835, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141028, '2026-04-05 08:40:28'),
(836, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141031, '2026-04-05 08:40:31'),
(837, 1, 28.47527236, 77.06164028, 0, 0, 71, NULL, 20260405141039, '2026-04-05 08:40:39'),
(838, 1, 28.47527236, 77.06164028, 0, 0, 71, 8, 20260405141047, '2026-04-05 08:40:47'),
(839, 1, 28.47527236, 77.06164028, 0, 0, 71, 8, 20260405141055, '2026-04-05 08:40:55'),
(840, 1, 28.47526474, 77.06166278, 0, 0, 68, 8, 20260405141107, '2026-04-05 08:41:07'),
(841, 1, 28.47526474, 77.06166278, 0, 0, 68, 8, 20260405141111, '2026-04-05 08:41:11'),
(842, 1, 28.47526474, 77.06166278, 0, 0, 68, 8, 20260405141119, '2026-04-05 08:41:19'),
(843, 1, 28.47526474, 77.06166278, 0, 0, 68, 8, 20260405141127, '2026-04-05 08:41:27'),
(844, 1, 28.47526474, 77.06166278, 0, 0, 68, 8, 20260405141135, '2026-04-05 08:41:35'),
(845, 1, 28.47530554, 77.06162939, 0, 0, 70, 8, 20260405141146, '2026-04-05 08:41:46'),
(846, 1, 28.41305500, 77.04181900, 0, 0, 364, NULL, 20260406115902, '2026-04-06 06:29:02'),
(847, 1, 28.41305500, 77.04181900, 0, 0, 364, NULL, 20260406115903, '2026-04-06 06:29:03'),
(848, 1, 28.41095400, 77.03714800, 0, 0, 46, NULL, 20260407214841, '2026-04-07 16:18:41'),
(849, 1, 28.41095400, 77.03714800, 0, 0, 46, NULL, 20260407214841, '2026-04-07 16:18:41'),
(850, 1, 28.41099314, 77.03718996, 0, 0, 105, NULL, 20260407214857, '2026-04-07 16:18:57'),
(851, 1, 28.41099314, 77.03718996, 0, 0, 105, NULL, 20260407214857, '2026-04-07 16:18:57'),
(852, 1, 28.41133200, 77.03662300, 0, 0, 100, NULL, 20260407214912, '2026-04-07 16:19:12'),
(853, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214913, '2026-04-07 16:19:13'),
(854, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214921, '2026-04-07 16:19:21'),
(855, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214929, '2026-04-07 16:19:29'),
(856, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214937, '2026-04-07 16:19:37'),
(857, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214948, '2026-04-07 16:19:48'),
(858, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407214953, '2026-04-07 16:19:53'),
(859, 1, 28.41101756, 77.03730955, 0, 0, 97, NULL, 20260407215011, '2026-04-07 16:20:11'),
(860, 1, 28.41101467, 77.03736297, 0, 0, 99, NULL, 20260407215030, '2026-04-07 16:20:30'),
(861, 1, 28.41101467, 77.03736297, 0, 0, 99, NULL, 20260407215033, '2026-04-07 16:20:33'),
(862, 1, 28.41101467, 77.03736297, 0, 0, 99, NULL, 20260407215041, '2026-04-07 16:20:41'),
(863, 1, 28.41101467, 77.03736297, 0, 0, 99, NULL, 20260407215049, '2026-04-07 16:20:49'),
(864, 1, 28.41101467, 77.03736297, 0, 0, 99, NULL, 20260407215057, '2026-04-07 16:20:57'),
(865, 1, 28.41101340, 77.03738704, 0, 0, 102, NULL, 20260407215105, '2026-04-07 16:21:05'),
(866, 1, 28.41101340, 77.03738704, 0, 0, 102, NULL, 20260407215109, '2026-04-07 16:21:09'),
(867, 1, 28.41101340, 77.03738704, 0, 0, 102, NULL, 20260407215117, '2026-04-07 16:21:17'),
(868, 1, 28.41101340, 77.03738704, 0, 0, 102, NULL, 20260407215125, '2026-04-07 16:21:25'),
(869, 1, 28.41101340, 77.03738704, 0, 0, 102, NULL, 20260407215133, '2026-04-07 16:21:33'),
(870, 1, 28.41133200, 77.03662300, 0, 0, 100, NULL, 20260407215148, '2026-04-07 16:21:48'),
(871, 1, 28.41102556, 77.03730399, 0, 0, 105, NULL, 20260407215149, '2026-04-07 16:21:49'),
(872, 1, 28.41102556, 77.03730399, 0, 0, 105, NULL, 20260407215157, '2026-04-07 16:21:57'),
(873, 1, 28.41102556, 77.03730399, 0, 0, 105, NULL, 20260407215205, '2026-04-07 16:22:05'),
(874, 1, 28.41102556, 77.03730399, 0, 0, 105, NULL, 20260407215213, '2026-04-07 16:22:13'),
(875, 1, 28.41133200, 77.03662300, 0, 0, 100, NULL, 20260407215228, '2026-04-07 16:22:28'),
(876, 1, 28.41133200, 77.03662300, 0, 0, 100, NULL, 20260407215229, '2026-04-07 16:22:29'),
(877, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215237, '2026-04-07 16:22:37'),
(878, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215245, '2026-04-07 16:22:45'),
(879, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215253, '2026-04-07 16:22:53'),
(880, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215302, '2026-04-07 16:23:02'),
(881, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215309, '2026-04-07 16:23:09'),
(882, 1, 28.41102873, 77.03722989, 0, 0, 100, NULL, 20260407215317, '2026-04-07 16:23:17'),
(883, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215325, '2026-04-07 16:23:25'),
(884, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215336, '2026-04-07 16:23:36'),
(885, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215341, '2026-04-07 16:23:41'),
(886, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215349, '2026-04-07 16:23:49'),
(887, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215357, '2026-04-07 16:23:57'),
(888, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215405, '2026-04-07 16:24:05'),
(889, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215417, '2026-04-07 16:24:17'),
(890, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215421, '2026-04-07 16:24:21'),
(891, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215429, '2026-04-07 16:24:29'),
(892, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215437, '2026-04-07 16:24:37'),
(893, 1, 28.41102873, 77.03722989, 0, 0, 100, 9, 20260407215445, '2026-04-07 16:24:45'),
(894, 1, 28.41097935, 77.03715299, 0, 0, 124, 9, 20260407215457, '2026-04-07 16:24:57'),
(895, 1, 28.41097935, 77.03715299, 0, 0, 124, 9, 20260407215501, '2026-04-07 16:25:01'),
(896, 1, 28.41097935, 77.03715299, 0, 0, 124, 9, 20260407215509, '2026-04-07 16:25:09'),
(897, 1, 28.41097935, 77.03715299, 0, 0, 124, 9, 20260407215517, '2026-04-07 16:25:17'),
(898, 1, 28.41097935, 77.03715299, 0, 0, 124, 9, 20260407215525, '2026-04-07 16:25:25'),
(899, 1, 28.41106774, 77.03703837, 0, 0, 105, 9, 20260407215535, '2026-04-07 16:25:35'),
(900, 1, 28.41106774, 77.03703837, 0, 0, 105, 9, 20260407215541, '2026-04-07 16:25:41'),
(901, 1, 28.41106774, 77.03703837, 0, 0, 105, 9, 20260407215549, '2026-04-07 16:25:49'),
(902, 1, 28.41106774, 77.03703837, 0, 0, 105, 9, 20260407215557, '2026-04-07 16:25:57'),
(903, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010510, '2026-04-15 19:35:10'),
(904, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010510, '2026-04-15 19:35:10'),
(905, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010513, '2026-04-15 19:35:13'),
(906, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010521, '2026-04-15 19:35:21'),
(907, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010529, '2026-04-15 19:35:29'),
(908, 1, 28.47541422, 77.06154180, 0, 0, 76, NULL, 20260416010538, '2026-04-15 19:35:38'),
(909, 1, 28.47541422, 77.06154361, 0, 0, 76, NULL, 20260416010548, '2026-04-15 19:35:48'),
(910, 1, 28.47541422, 77.06154361, 0, 0, 76, NULL, 20260416010554, '2026-04-15 19:35:54'),
(911, 1, 28.47541422, 77.06154361, 0, 0, 76, NULL, 20260416010602, '2026-04-15 19:36:02'),
(912, 1, 28.47541422, 77.06154361, 0, 0, 76, NULL, 20260416010610, '2026-04-15 19:36:10'),
(913, 1, 28.47541422, 77.06154361, 0, 0, 76, NULL, 20260416010618, '2026-04-15 19:36:18'),
(914, 1, 28.47536892, 77.06155634, 0, 0, 70, NULL, 20260416010629, '2026-04-15 19:36:29'),
(915, 1, 28.47536892, 77.06155634, 0, 0, 70, NULL, 20260416010634, '2026-04-15 19:36:34'),
(916, 1, 28.47536892, 77.06155634, 0, 0, 70, NULL, 20260416010642, '2026-04-15 19:36:42'),
(917, 1, 28.47536892, 77.06155634, 0, 0, 70, NULL, 20260416010650, '2026-04-15 19:36:50'),
(918, 1, 28.47536892, 77.06155634, 0, 0, 70, NULL, 20260416010658, '2026-04-15 19:36:58'),
(919, 1, 28.47533922, 77.06157520, 0, 0, 70, NULL, 20260416010707, '2026-04-15 19:37:07'),
(920, 1, 28.47533922, 77.06157520, 0, 0, 70, NULL, 20260416010714, '2026-04-15 19:37:14'),
(921, 1, 28.47533922, 77.06157520, 0, 0, 70, NULL, 20260416010722, '2026-04-15 19:37:22'),
(922, 1, 28.47533922, 77.06157520, 0, 0, 70, NULL, 20260416010730, '2026-04-15 19:37:30'),
(923, 1, 28.47532791, 77.06153253, 0, 0, 70, NULL, 20260416010827, '2026-04-15 19:38:27'),
(924, 1, 28.47532791, 77.06153253, 0, 0, 70, NULL, 20260416010841, '2026-04-15 19:38:41'),
(925, 1, 28.47532791, 77.06153253, 0, 0, 70, NULL, 20260416010850, '2026-04-15 19:38:50'),
(926, 1, 28.47532937, 77.06156167, 0, 0, 70, NULL, 20260416010900, '2026-04-15 19:39:00'),
(927, 1, 28.47532937, 77.06156167, 0, 0, 70, NULL, 20260416010906, '2026-04-15 19:39:06'),
(928, 1, 28.47532937, 77.06156167, 0, 0, 70, NULL, 20260416010914, '2026-04-15 19:39:14'),
(929, 1, 28.47532937, 77.06156167, 0, 0, 70, NULL, 20260416010922, '2026-04-15 19:39:22'),
(930, 1, 28.47529235, 77.06159205, 0, 0, 67, NULL, 20260416010931, '2026-04-15 19:39:31'),
(931, 1, 28.47529235, 77.06159205, 0, 0, 67, NULL, 20260416010938, '2026-04-15 19:39:38'),
(932, 1, 28.47529002, 77.06158810, 0, 0, 67, NULL, 20260416011029, '2026-04-15 19:40:29'),
(933, 1, 28.47526047, 77.06154845, 0, 0, 63, NULL, 20260416011128, '2026-04-15 19:41:28'),
(934, 1, 28.47528665, 77.06154101, 0, 0, 63, NULL, 20260416011227, '2026-04-15 19:42:27'),
(935, 1, 28.65320000, 77.21080000, 0, 0, 54316, NULL, 20260416011333, '2026-04-15 19:43:33'),
(936, 1, 28.47537780, 77.06166459, 0, 0, 72, NULL, 20260416011347, '2026-04-15 19:43:47'),
(937, 1, 28.47537780, 77.06166459, 0, 0, 72, NULL, 20260416011353, '2026-04-15 19:43:53'),
(938, 1, 28.47537780, 77.06166459, 0, 0, 72, NULL, 20260416011401, '2026-04-15 19:44:01'),
(939, 1, 28.47539317, 77.06162907, 0, 0, 71, NULL, 20260416011411, '2026-04-15 19:44:11'),
(940, 1, 28.47539317, 77.06162907, 0, 0, 71, NULL, 20260416011417, '2026-04-15 19:44:17'),
(941, 1, 28.47539317, 77.06162907, 0, 0, 71, NULL, 20260416011425, '2026-04-15 19:44:25'),
(942, 1, 28.47539317, 77.06162907, 0, 0, 71, NULL, 20260416011433, '2026-04-15 19:44:33'),
(943, 1, 28.47533398, 77.06155748, 0, 0, 67, NULL, 20260416011447, '2026-04-15 19:44:47'),
(944, 1, 28.47533398, 77.06155748, 0, 0, 67, NULL, 20260416011449, '2026-04-15 19:44:49'),
(945, 1, 28.47533398, 77.06155748, 0, 0, 67, NULL, 20260416011457, '2026-04-15 19:44:57'),
(946, 1, 28.47533398, 77.06155748, 0, 0, 67, NULL, 20260416011505, '2026-04-15 19:45:05'),
(947, 1, 28.47533398, 77.06155748, 0, 0, 67, NULL, 20260416011513, '2026-04-15 19:45:13'),
(948, 1, 28.47534742, 77.06156314, 0, 0, 67, NULL, 20260416011523, '2026-04-15 19:45:23'),
(949, 1, 28.47534742, 77.06156314, 0, 0, 67, NULL, 20260416011529, '2026-04-15 19:45:29'),
(950, 1, 28.47534742, 77.06156314, 0, 0, 67, NULL, 20260416011537, '2026-04-15 19:45:37'),
(951, 1, 28.47534742, 77.06156314, 0, 0, 67, NULL, 20260416011545, '2026-04-15 19:45:45'),
(952, 1, 28.47534742, 77.06156314, 0, 0, 67, NULL, 20260416011554, '2026-04-15 19:45:54'),
(953, 1, 28.47541900, 77.06146925, 0, 0, 67, NULL, 20260416011603, '2026-04-15 19:46:03'),
(954, 1, 28.47541900, 77.06146925, 0, 0, 67, NULL, 20260416011609, '2026-04-15 19:46:09'),
(955, 1, 28.47541900, 77.06146925, 0, 0, 67, NULL, 20260416011618, '2026-04-15 19:46:18'),
(956, 1, 28.47541900, 77.06146925, 0, 0, 67, NULL, 20260416011625, '2026-04-15 19:46:25'),
(957, 1, 28.47532942, 77.06149789, 0, 0, 61, NULL, 20260416011654, '2026-04-15 19:46:54'),
(958, 1, 28.47540778, 77.06148371, 0, 0, 67, NULL, 20260416011654, '2026-04-15 19:46:54'),
(959, 1, 28.65320000, 77.21080000, 0, 0, 54316, NULL, 20260416011654, '2026-04-15 19:46:54'),
(960, 1, 28.47532942, 77.06149789, 0, 0, 61, NULL, 20260416011657, '2026-04-15 19:46:57'),
(961, 1, 28.41382242, 77.04141986, 0, 0, 68, NULL, 20260416105156, '2026-04-16 05:21:56'),
(962, 1, 28.41379853, 77.04141277, 0, 0, 61, NULL, 20260416105225, '2026-04-16 05:22:25'),
(963, 1, 28.41379853, 77.04141277, 0, 0, 61, NULL, 20260416105233, '2026-04-16 05:22:33'),
(964, 1, 28.41380904, 77.04141474, 0, 0, 61, NULL, 20260416105245, '2026-04-16 05:22:45'),
(965, 1, 28.41380904, 77.04141474, 0, 0, 61, NULL, 20260416105249, '2026-04-16 05:22:49'),
(966, 1, 28.41380904, 77.04141474, 0, 0, 61, NULL, 20260416105257, '2026-04-16 05:22:57'),
(967, 1, 28.41380904, 77.04141474, 0, 0, 61, NULL, 20260416105305, '2026-04-16 05:23:05'),
(968, 1, 28.41380904, 77.04141474, 0, 0, 61, NULL, 20260416105313, '2026-04-16 05:23:13'),
(969, 1, 28.41380700, 77.04141504, 0, 0, 61, NULL, 20260416105325, '2026-04-16 05:23:25'),
(970, 1, 28.41380700, 77.04141504, 0, 0, 61, NULL, 20260416105329, '2026-04-16 05:23:29'),
(971, 1, 28.41380700, 77.04141504, 0, 0, 61, NULL, 20260416105337, '2026-04-16 05:23:37'),
(972, 1, 28.41380700, 77.04141504, 0, 0, 61, NULL, 20260416105345, '2026-04-16 05:23:45'),
(973, 1, 28.41380700, 77.04141504, 0, 0, 61, NULL, 20260416105353, '2026-04-16 05:23:53'),
(974, 1, 28.41383852, 77.04140840, 0, 0, 69, NULL, 20260416105404, '2026-04-16 05:24:04'),
(975, 1, 28.41383852, 77.04140840, 0, 0, 69, NULL, 20260416105409, '2026-04-16 05:24:09'),
(976, 1, 28.41383852, 77.04140840, 0, 0, 69, NULL, 20260416105417, '2026-04-16 05:24:17'),
(977, 1, 28.41383852, 77.04140840, 0, 0, 69, NULL, 20260416105425, '2026-04-16 05:24:25'),
(978, 1, 28.41381964, 77.04141746, 0, 0, 65, NULL, 20260416105528, '2026-04-16 05:25:28'),
(979, 1, 28.41382710, 77.04141885, 0, 0, 65, NULL, 20260416105627, '2026-04-16 05:26:27'),
(980, 1, 28.41395386, 77.04140094, 0, 0, 82, NULL, 20260416105728, '2026-04-16 05:27:28'),
(981, 1, 28.41395306, 77.04140152, 0, 0, 76, NULL, 20260416105827, '2026-04-16 05:28:27'),
(982, 1, 28.41390907, 77.04142212, 0, 0, 76, NULL, 20260416105928, '2026-04-16 05:29:28'),
(983, 1, 28.41390624, 77.04142073, 0, 0, 79, NULL, 20260416110027, '2026-04-16 05:30:27'),
(984, 1, 28.41402439, 77.04139819, 0, 0, 90, NULL, 20260416110128, '2026-04-16 05:31:28'),
(985, 1, 28.41402337, 77.04140719, 0, 0, 92, NULL, 20260416110228, '2026-04-16 05:32:28'),
(986, 1, 28.41403057, 77.04147106, 0, 0, 159, NULL, 20260416110328, '2026-04-16 05:33:28'),
(987, 1, 28.41383297, 77.04141739, 0, 0, 78, NULL, 20260416110428, '2026-04-16 05:34:28'),
(988, 1, 28.41383297, 77.04141739, 0, 0, 78, NULL, 20260416110528, '2026-04-16 05:35:28'),
(989, 1, 28.41403057, 77.04147106, 0, 0, 159, NULL, 20260416110627, '2026-04-16 05:36:27'),
(990, 1, 28.41400399, 77.04141405, 0, 0, 82, NULL, 20260416110728, '2026-04-16 05:37:28'),
(991, 1, 28.41400399, 77.04141405, 0, 0, 82, NULL, 20260416110828, '2026-04-16 05:38:28'),
(992, 1, 28.41389633, 77.04142316, 0, 0, 72, NULL, 20260416110929, '2026-04-16 05:39:29'),
(993, 1, 28.41389830, 77.04143178, 0, 0, 75, NULL, 20260416111027, '2026-04-16 05:40:27'),
(994, 1, 28.41396288, 77.04142099, 0, 0, 87, NULL, 20260416111128, '2026-04-16 05:41:28'),
(995, 1, 28.41395406, 77.04141075, 0, 0, 82, NULL, 20260416111228, '2026-04-16 05:42:28'),
(996, 1, 28.41387942, 77.04140157, 0, 0, 72, NULL, 20260416111328, '2026-04-16 05:43:28'),
(997, 1, 28.41383608, 77.04139844, 0, 0, 73, NULL, 20260416111428, '2026-04-16 05:44:28'),
(998, 1, 28.41385997, 77.04141649, 0, 0, 72, NULL, 20260416111528, '2026-04-16 05:45:28'),
(999, 1, 28.41378088, 77.04141919, 0, 0, 67, NULL, 20260416111627, '2026-04-16 05:46:27'),
(1000, 1, 28.41379955, 77.04141686, 0, 0, 68, NULL, 20260416111728, '2026-04-16 05:47:28'),
(1001, 1, 28.41386658, 77.04141690, 0, 0, 72, NULL, 20260416111827, '2026-04-16 05:48:27'),
(1002, 1, 28.41392276, 77.04141274, 0, 0, 76, NULL, 20260416111928, '2026-04-16 05:49:28'),
(1003, 1, 28.41392248, 77.04141519, 0, 0, 76, NULL, 20260416112028, '2026-04-16 05:50:28'),
(1004, 1, 28.41387346, 77.04141830, 0, 0, 72, NULL, 20260416112128, '2026-04-16 05:51:28'),
(1005, 1, 28.41382591, 77.04141256, 0, 0, 67, NULL, 20260416112228, '2026-04-16 05:52:28'),
(1006, 1, 28.41380923, 77.04141437, 0, 0, 68, NULL, 20260416112328, '2026-04-16 05:53:28'),
(1007, 1, 28.41379451, 77.04141487, 0, 0, 66, NULL, 20260416112428, '2026-04-16 05:54:28'),
(1008, 1, 28.41382558, 77.04140296, 0, 0, 74, NULL, 20260416112528, '2026-04-16 05:55:28'),
(1009, 1, 28.41384962, 77.04140486, 0, 0, 75, NULL, 20260416112628, '2026-04-16 05:56:28'),
(1010, 1, 28.41387837, 77.04140425, 0, 0, 82, NULL, 20260416112728, '2026-04-16 05:57:28'),
(1011, 1, 28.41390323, 77.04141533, 0, 0, 94, NULL, 20260416112828, '2026-04-16 05:58:28'),
(1012, 1, 28.41377597, 77.04141437, 0, 0, 68, NULL, 20260416112928, '2026-04-16 05:59:28'),
(1013, 1, 28.41381222, 77.04141242, 0, 0, 65, NULL, 20260416113028, '2026-04-16 06:00:28'),
(1014, 1, 28.41385079, 77.04140673, 0, 0, 75, NULL, 20260416113128, '2026-04-16 06:01:28'),
(1015, 1, 28.41378775, 77.04141591, 0, 0, 76, NULL, 20260416113228, '2026-04-16 06:02:28'),
(1016, 1, 28.41384641, 77.04140663, 0, 0, 75, NULL, 20260416113328, '2026-04-16 06:03:28'),
(1017, 1, 28.41377992, 77.04141326, 0, 0, 62, NULL, 20260416113428, '2026-04-16 06:04:28'),
(1018, 1, 28.41378264, 77.04140919, 0, 0, 65, NULL, 20260416113528, '2026-04-16 06:05:28'),
(1019, 1, 28.41388242, 77.04143393, 0, 0, 72, NULL, 20260416113628, '2026-04-16 06:06:28'),
(1020, 1, 28.41386546, 77.04143942, 0, 0, 71, NULL, 20260416113728, '2026-04-16 06:07:28'),
(1021, 1, 28.41381463, 77.04141475, 0, 0, 71, NULL, 20260416113828, '2026-04-16 06:08:28'),
(1022, 1, 28.41391067, 77.04139940, 0, 0, 82, NULL, 20260416113928, '2026-04-16 06:09:28'),
(1023, 1, 28.41403057, 77.04147106, 0, 0, 159, NULL, 20260416114656, '2026-04-16 06:16:56'),
(1024, 1, 28.41403057, 77.04147106, 0, 0, 159, NULL, 20260416114725, '2026-04-16 06:17:25'),
(1025, 1, 28.41379519, 77.04140854, 0, 0, 62, NULL, 20260416114827, '2026-04-16 06:18:27'),
(1026, 1, 28.41384042, 77.04140408, 0, 0, 69, NULL, 20260416114928, '2026-04-16 06:19:28'),
(1027, 1, 28.41387346, 77.04141830, 0, 0, 72, NULL, 20260416115028, '2026-04-16 06:20:28'),
(1028, 1, 28.41386341, 77.04142311, 0, 0, 65, NULL, 20260416115128, '2026-04-16 06:21:28'),
(1029, 1, 28.41394083, 77.04142301, 0, 0, 75, NULL, 20260416115228, '2026-04-16 06:22:28'),
(1030, 1, 28.41390230, 77.04141888, 0, 0, 77, NULL, 20260416115332, '2026-04-16 06:23:32'),
(1031, 1, 28.63900000, 77.23600000, 0, 0, 56582, NULL, 20260416115432, '2026-04-16 06:24:32'),
(1032, 1, 28.41392897, 77.04140972, 0, 0, 85, NULL, 20260416115528, '2026-04-16 06:25:28'),
(1033, 1, 28.41396730, 77.04141593, 0, 0, 82, NULL, 20260416115628, '2026-04-16 06:26:28'),
(1034, 1, 28.63900000, 77.23600000, 0, 0, 56582, NULL, 20260416115732, '2026-04-16 06:27:32'),
(1035, 1, 28.41386341, 77.04142311, 0, 0, 65, NULL, 20260416115828, '2026-04-16 06:28:28'),
(1036, 1, 28.41387459, 77.04141784, 0, 0, 72, NULL, 20260416115928, '2026-04-16 06:29:28'),
(1037, 1, 28.41382640, 77.04141675, 0, 0, 70, NULL, 20260416120028, '2026-04-16 06:30:28'),
(1038, 1, 28.63900000, 77.23600000, 0, 0, 56582, NULL, 20260416120132, '2026-04-16 06:31:32'),
(1039, 1, 28.41318740, 77.04216870, 0.0503927, 0, 10.585, NULL, 20260416120139, '2026-04-16 06:31:39'),
(1040, 1, 28.41320780, 77.04216660, 0.0627413, 0, 10.441, NULL, 20260416120145, '2026-04-16 06:31:45'),
(1041, 1, 28.41319890, 77.04216670, 0.0481429, 0, 9.337, NULL, 20260416120153, '2026-04-16 06:31:53'),
(1042, 1, 28.41320000, 77.04216240, 0.0672418, 0, 9.201, NULL, 20260416120200, '2026-04-16 06:32:00'),
(1043, 1, 28.41318050, 77.04216370, 0.118888, 189.334, 9.549, NULL, 20260416120208, '2026-04-16 06:32:08'),
(1044, 1, 28.41318640, 77.04213760, 0.0605447, 0, 10.156, NULL, 20260416120217, '2026-04-16 06:32:17'),
(1045, 1, 28.41337500, 77.04209270, 0, 0, 18, NULL, 20260416120225, '2026-04-16 06:32:25'),
(1046, 1, 28.63900000, 77.23600000, 0, 0, 56582, NULL, 20260416120232, '2026-04-16 06:32:32'),
(1047, 1, 28.41337500, 77.04208200, 0, 0, 17, NULL, 20260416120233, '2026-04-16 06:32:33'),
(1048, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1049, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1050, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1051, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1052, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1053, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1054, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1055, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1056, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1057, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1058, 1, 28.41335360, 77.04208200, 0, 0, 138.598, NULL, 20260416120632, '2026-04-16 06:36:32'),
(1059, 1, 28.41335360, 77.04208200, 0, 0, 16.5, NULL, 20260416120637, '2026-04-16 06:36:37'),
(1060, 1, 28.41335360, 77.04207660, 0, 0, 19, NULL, 20260416120645, '2026-04-16 06:36:45'),
(1061, 1, 28.41378690, 77.04141620, 0, 0, 60, NULL, 20260416122321, '2026-04-16 06:53:21'),
(1062, 1, 28.41378690, 77.04141620, 0, 0, 60, NULL, 20260416122325, '2026-04-16 06:53:25'),
(1063, 1, 28.41378690, 77.04141620, 0, 0, 60, NULL, 20260416122425, '2026-04-16 06:54:25'),
(1064, 1, 28.41377825, 77.04141602, 0, 0, 58, NULL, 20260416122525, '2026-04-16 06:55:25'),
(1065, 1, 28.41384578, 77.04141205, 0, 0, 69, NULL, 20260416122628, '2026-04-16 06:56:28'),
(1066, 1, 28.41385428, 77.04141389, 0, 0, 75, NULL, 20260416122728, '2026-04-16 06:57:28'),
(1067, 1, 28.41382446, 77.04141511, 0, 0, 68, NULL, 20260416122827, '2026-04-16 06:58:27'),
(1068, 1, 28.41386786, 77.04140830, 0, 0, 72, NULL, 20260416122927, '2026-04-16 06:59:27'),
(1069, 1, 28.41402479, 77.04139145, 0, 0, 92, NULL, 20260416123028, '2026-04-16 07:00:28'),
(1070, 1, 28.41396174, 77.04139795, 0, 0, 86, NULL, 20260416123128, '2026-04-16 07:01:28'),
(1071, 1, 28.41396231, 77.04139387, 0, 0, 81, NULL, 20260416123227, '2026-04-16 07:02:27'),
(1072, 1, 28.41396953, 77.04138695, 0, 0, 76, NULL, 20260416123327, '2026-04-16 07:03:27'),
(1073, 1, 28.41397852, 77.04139934, 0, 0, 82, NULL, 20260416123427, '2026-04-16 07:04:27'),
(1074, 1, 28.41402750, 77.04141010, 0, 0, 86, NULL, 20260416123528, '2026-04-16 07:05:28'),
(1075, 1, 28.41411100, 77.04157500, 0, 0, 20, NULL, 20260416123629, '2026-04-16 07:06:29'),
(1076, 1, 28.41401570, 77.04138882, 0, 0, 102, NULL, 20260416123727, '2026-04-16 07:07:27'),
(1077, 1, 28.41402751, 77.04139755, 0, 0, 86, NULL, 20260416123827, '2026-04-16 07:08:27'),
(1078, 1, 28.41392084, 77.04140482, 0, 0, 76, NULL, 20260416123927, '2026-04-16 07:09:27'),
(1079, 1, 28.41388897, 77.04140709, 0, 0, 74, NULL, 20260416124027, '2026-04-16 07:10:27'),
(1080, 1, 28.41385781, 77.04141248, 0, 0, 80, NULL, 20260416124128, '2026-04-16 07:11:28'),
(1081, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416124227, '2026-04-16 07:12:27'),
(1082, 1, 28.41391047, 77.04138454, 0, 0, 76, NULL, 20260416124327, '2026-04-16 07:13:27'),
(1083, 1, 28.41386980, 77.04138651, 0, 0, 84, NULL, 20260416124428, '2026-04-16 07:14:28'),
(1084, 1, 28.41401753, 77.04144346, 0, 0, 88, NULL, 20260416124528, '2026-04-16 07:15:28'),
(1085, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416124628, '2026-04-16 07:16:28'),
(1086, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416124728, '2026-04-16 07:17:28'),
(1087, 1, 28.41401769, 77.04144466, 0, 0, 82, NULL, 20260416124827, '2026-04-16 07:18:27'),
(1088, 1, 28.41391737, 77.04138953, 0, 0, 88, NULL, 20260416124927, '2026-04-16 07:19:27'),
(1089, 1, 28.41395483, 77.04138818, 0, 0, 88, NULL, 20260416125027, '2026-04-16 07:20:27'),
(1090, 1, 28.41394567, 77.04143398, 0, 0, 84, NULL, 20260416125128, '2026-04-16 07:21:28'),
(1091, 1, 28.41395053, 77.04142613, 0, 0, 77, NULL, 20260416125227, '2026-04-16 07:22:27'),
(1092, 1, 28.41400685, 77.04139893, 0, 0, 104, NULL, 20260416125327, '2026-04-16 07:23:27'),
(1093, 1, 28.41400229, 77.04140420, 0, 0, 109, NULL, 20260416125427, '2026-04-16 07:24:27'),
(1094, 1, 28.41398540, 77.04139806, 0, 0, 95, NULL, 20260416125527, '2026-04-16 07:25:27'),
(1095, 1, 28.41386122, 77.04140261, 0, 0, 84, NULL, 20260416125628, '2026-04-16 07:26:28'),
(1096, 1, 28.41393812, 77.04146063, 0, 0, 78, NULL, 20260416125727, '2026-04-16 07:27:27'),
(1097, 1, 28.41396002, 77.04144142, 0, 0, 69, NULL, 20260416125827, '2026-04-16 07:28:27'),
(1098, 1, 28.41390142, 77.04140603, 0, 0, 76, NULL, 20260416125927, '2026-04-16 07:29:27'),
(1099, 1, 28.41390142, 77.04140603, 0, 0, 76, NULL, 20260416130028, '2026-04-16 07:30:28'),
(1100, 1, 28.41408295, 77.04146231, 0, 0, 87, NULL, 20260416130128, '2026-04-16 07:31:28'),
(1101, 1, 28.41418595, 77.04159474, 0, 0, 136, NULL, 20260416130227, '2026-04-16 07:32:27'),
(1102, 1, 28.41378029, 77.04137628, 0, 0, 136, NULL, 20260416130327, '2026-04-16 07:33:27'),
(1103, 1, 28.41377423, 77.04136300, 0, 0, 135, NULL, 20260416130428, '2026-04-16 07:34:28'),
(1104, 1, 28.41384670, 77.04139186, 0, 0, 119, NULL, 20260416130528, '2026-04-16 07:35:28'),
(1105, 1, 28.41396259, 77.04139569, 0, 0, 91, NULL, 20260416130628, '2026-04-16 07:36:28'),
(1106, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130728, '2026-04-16 07:37:28'),
(1107, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130742, '2026-04-16 07:37:42'),
(1108, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130745, '2026-04-16 07:37:45'),
(1109, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130753, '2026-04-16 07:37:53'),
(1110, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130804, '2026-04-16 07:38:04'),
(1111, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130809, '2026-04-16 07:38:09'),
(1112, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130817, '2026-04-16 07:38:17'),
(1113, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130825, '2026-04-16 07:38:25'),
(1114, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130833, '2026-04-16 07:38:33'),
(1115, 1, 28.41407425, 77.04145343, 0, 0, 83, NULL, 20260416130844, '2026-04-16 07:38:44'),
(1116, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416130928, '2026-04-16 07:39:28'),
(1117, 1, 28.41396485, 77.04140372, 0, 0, 86, NULL, 20260416131028, '2026-04-16 07:40:28'),
(1118, 1, 28.41396485, 77.04140372, 0, 0, 86, NULL, 20260416131128, '2026-04-16 07:41:28');
INSERT INTO `engineer_locations` (`id`, `engineer_id`, `latitude`, `longitude`, `speed`, `heading`, `accuracy`, `job_id`, `timestamp`, `created_at`) VALUES
(1119, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416131227, '2026-04-16 07:42:27'),
(1120, 1, 28.63900000, 77.23600000, 0, 0, 56582, NULL, 20260416131328, '2026-04-16 07:43:28'),
(1121, 1, 28.41406400, 77.04138200, 0, 0, 152, NULL, 20260416131428, '2026-04-16 07:44:28'),
(1122, 1, 28.41405615, 77.04138836, 0, 0, 109, NULL, 20260416131528, '2026-04-16 07:45:28'),
(1123, 1, 28.41404212, 77.04139402, 0, 0, 159, NULL, 20260416131627, '2026-04-16 07:46:27'),
(1124, 1, 28.41387340, 77.04140115, 0, 0, 78, NULL, 20260416131727, '2026-04-16 07:47:27'),
(1125, 1, 28.41384241, 77.04140246, 0, 0, 74, NULL, 20260416131827, '2026-04-16 07:48:27'),
(1126, 1, 28.41404150, 77.04142000, 0, 0, 166, NULL, 20260416131927, '2026-04-16 07:49:27'),
(1127, 1, 28.41400212, 77.04140569, 0, 0, 106, NULL, 20260416132028, '2026-04-16 07:50:28'),
(1128, 1, 28.41382459, 77.04140536, 0, 0, 80, NULL, 20260416132127, '2026-04-16 07:51:27'),
(1129, 1, 28.41382459, 77.04140536, 0, 0, 80, NULL, 20260416132227, '2026-04-16 07:52:27'),
(1130, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416132328, '2026-04-16 07:53:28'),
(1131, 1, 28.41396963, 77.04140003, 0, 0, 88, NULL, 20260416132428, '2026-04-16 07:54:28'),
(1132, 1, 28.41396963, 77.04140003, 0, 0, 88, NULL, 20260416132528, '2026-04-16 07:55:28'),
(1133, 1, 28.41388127, 77.04140861, 0, 0, 89, NULL, 20260416132628, '2026-04-16 07:56:28'),
(1134, 1, 28.41390768, 77.04140230, 0, 0, 92, NULL, 20260416132728, '2026-04-16 07:57:28'),
(1135, 1, 28.41398540, 77.04139806, 0, 0, 95, NULL, 20260416132828, '2026-04-16 07:58:28'),
(1136, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416132928, '2026-04-16 07:59:28'),
(1137, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416133028, '2026-04-16 08:00:28'),
(1138, 1, 28.41402304, 77.04139768, 0, 0, 90, NULL, 20260416133128, '2026-04-16 08:01:28'),
(1139, 1, 28.41387523, 77.04140042, 0, 0, 76, NULL, 20260416133227, '2026-04-16 08:02:27'),
(1140, 1, 28.41391306, 77.04138972, 0, 0, 76, NULL, 20260416133327, '2026-04-16 08:03:27'),
(1141, 1, 28.41405190, 77.04137132, 0, 0, 92, NULL, 20260416133428, '2026-04-16 08:04:28'),
(1142, 1, 28.41399799, 77.04139631, 0, 0, 116, NULL, 20260416133528, '2026-04-16 08:05:28'),
(1143, 1, 28.41380647, 77.04140081, 0, 0, 84, NULL, 20260416133628, '2026-04-16 08:06:28'),
(1144, 1, 28.41376163, 77.04139951, 0, 0, 73, NULL, 20260416133727, '2026-04-16 08:07:27'),
(1145, 1, 28.41383437, 77.04139598, 0, 0, 89, NULL, 20260416133828, '2026-04-16 08:08:28'),
(1146, 1, 28.41393195, 77.04139150, 0, 0, 95, NULL, 20260416133928, '2026-04-16 08:09:28'),
(1147, 1, 28.41393195, 77.04139150, 0, 0, 95, NULL, 20260416133946, '2026-04-16 08:09:46'),
(1148, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1149, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1150, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1151, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1152, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1153, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1154, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1155, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1156, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1157, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1158, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1159, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1160, 1, 28.47511410, 77.06167280, 0.23, 255, 13.936, NULL, 20260416214539, '2026-04-16 16:15:39'),
(1161, 1, 28.47505950, 77.06172090, 0, 0, 13.221, NULL, 20260416214543, '2026-04-16 16:15:43'),
(1162, 1, 28.47510070, 77.06171020, 0.22, 251, 12.06, NULL, 20260416214544, '2026-04-16 16:15:44'),
(1163, 1, 28.47516240, 77.06169260, 0, 0, 20.352, NULL, 20260416214724, '2026-04-16 16:17:24'),
(1164, 1, 28.47509800, 77.06172110, 0, 0, 294.171, NULL, 20260416214732, '2026-04-16 16:17:32'),
(1165, 1, 28.47508100, 77.06174030, 0, 0, 289.374, NULL, 20260416214740, '2026-04-16 16:17:40'),
(1166, 1, 28.47508630, 77.06173960, 0, 0, 289.38, NULL, 20260416214748, '2026-04-16 16:17:48'),
(1167, 1, 28.47508730, 77.06173180, 0, 0, 300.917, NULL, 20260416214756, '2026-04-16 16:17:56'),
(1168, 1, 28.47509260, 77.06173720, 0, 0, 296.544, NULL, 20260416214804, '2026-04-16 16:18:04'),
(1169, 1, 28.47506050, 77.06167820, 0, 0, 295.666, NULL, 20260416214812, '2026-04-16 16:18:12'),
(1170, 1, 28.47511950, 77.06160840, 0, 0, 302.081, NULL, 20260416214820, '2026-04-16 16:18:20'),
(1171, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1172, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1173, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1174, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1175, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1176, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1177, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1178, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1179, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1180, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1181, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1182, 1, 28.47509490, 77.06165560, 0, 0, 19.458, NULL, 20260416215454, '2026-04-16 16:24:54'),
(1183, 1, 28.47559089, 77.06147321, 0, 0, 81, NULL, 20260420002531, '2026-04-19 18:55:31'),
(1184, 1, 28.47559089, 77.06147321, 0, 0, 81, NULL, 20260420002534, '2026-04-19 18:55:34'),
(1185, 1, 28.47547443, 77.06158011, 0, 0, 85, NULL, 20260420002544, '2026-04-19 18:55:44'),
(1186, 1, 28.47547443, 77.06158011, 0, 0, 85, NULL, 20260420002550, '2026-04-19 18:55:50'),
(1187, 1, 28.47547443, 77.06158011, 0, 0, 85, NULL, 20260420002558, '2026-04-19 18:55:58'),
(1188, 1, 28.47547443, 77.06158011, 0, 0, 85, NULL, 20260420002606, '2026-04-19 18:56:06'),
(1189, 1, 28.47546956, 77.06153513, 0, 0, 80, NULL, 20260420002620, '2026-04-19 18:56:20'),
(1190, 1, 28.47546956, 77.06153513, 0, 0, 80, NULL, 20260420002622, '2026-04-19 18:56:22'),
(1191, 1, 28.47546956, 77.06153513, 0, 0, 80, NULL, 20260420002630, '2026-04-19 18:56:30'),
(1192, 1, 28.47546956, 77.06153513, 0, 0, 80, NULL, 20260420002638, '2026-04-19 18:56:38'),
(1193, 1, 28.47546956, 77.06153513, 0, 0, 80, NULL, 20260420002646, '2026-04-19 18:56:46'),
(1194, 1, 28.47543196, 77.06146573, 0, 0, 83, NULL, 20260420002715, '2026-04-19 18:57:15'),
(1195, 1, 28.47545970, 77.06141887, 0, 0, 83, NULL, 20260420002814, '2026-04-19 18:58:14'),
(1196, 1, 28.47547163, 77.06138625, 0, 0, 87, NULL, 20260420002915, '2026-04-19 18:59:15'),
(1197, 1, 28.47550693, 77.06135271, 0, 0, 84, NULL, 20260420003016, '2026-04-19 19:00:16'),
(1198, 1, 28.47542373, 77.06141611, 0, 0, 89, NULL, 20260420003113, '2026-04-19 19:01:13'),
(1199, 1, 28.47540071, 77.06145885, 0, 0, 90, NULL, 20260420003213, '2026-04-19 19:02:13'),
(1200, 1, 28.47548506, 77.06134335, 0, 0, 86, NULL, 20260420003313, '2026-04-19 19:03:13'),
(1201, 1, 28.47548506, 77.06134335, 0, 0, 86, NULL, 20260420003415, '2026-04-19 19:04:15'),
(1202, 1, 28.47548506, 77.06134335, 0, 0, 86, NULL, 20260420003513, '2026-04-19 19:05:13'),
(1203, 1, 28.47544267, 77.06129739, 0, 0, 82, NULL, 20260420003613, '2026-04-19 19:06:13'),
(1204, 1, 28.47547848, 77.06125376, 0, 0, 81, NULL, 20260420003716, '2026-04-19 19:07:16'),
(1205, 1, 28.47551462, 77.06127697, 0, 0, 83, NULL, 20260420003814, '2026-04-19 19:08:14'),
(1206, 1, 28.47552257, 77.06128827, 0, 0, 83, NULL, 20260420003915, '2026-04-19 19:09:15'),
(1207, 1, 28.47552864, 77.06129551, 0, 0, 89, NULL, 20260420004016, '2026-04-19 19:10:16'),
(1208, 1, 28.47552807, 77.06138698, 0, 0, 89, NULL, 20260420004116, '2026-04-19 19:11:16'),
(1209, 1, 28.47552807, 77.06138698, 0, 0, 89, NULL, 20260420004214, '2026-04-19 19:12:14'),
(1210, 1, 28.47549852, 77.06142851, 0, 0, 89, NULL, 20260420004314, '2026-04-19 19:13:14'),
(1211, 1, 28.47556761, 77.06124924, 0, 0, 81, NULL, 20260420004412, '2026-04-19 19:14:12'),
(1212, 1, 28.47558555, 77.06121533, 0, 0, 83, NULL, 20260420004449, '2026-04-19 19:14:49'),
(1213, 1, 28.47558555, 77.06121533, 0, 0, 83, NULL, 20260420004454, '2026-04-19 19:14:54'),
(1214, 1, 28.47558555, 77.06121533, 0, 0, 83, NULL, 20260420004502, '2026-04-19 19:15:02'),
(1215, 1, 28.47558555, 77.06121533, 0, 0, 83, NULL, 20260420004510, '2026-04-19 19:15:10'),
(1216, 1, 28.47558555, 77.06121533, 0, 0, 83, NULL, 20260420004518, '2026-04-19 19:15:18'),
(1217, 1, 28.47555432, 77.06124933, 0, 0, 86, NULL, 20260420004528, '2026-04-19 19:15:28'),
(1218, 1, 28.47555432, 77.06124933, 0, 0, 86, NULL, 20260420004534, '2026-04-19 19:15:34'),
(1219, 1, 28.47555432, 77.06124933, 0, 0, 86, NULL, 20260420004542, '2026-04-19 19:15:42'),
(1220, 1, 28.47555432, 77.06124933, 0, 0, 86, NULL, 20260420004550, '2026-04-19 19:15:50'),
(1221, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004558, '2026-04-19 19:15:58'),
(1222, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004607, '2026-04-19 19:16:07'),
(1223, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004614, '2026-04-19 19:16:14'),
(1224, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004622, '2026-04-19 19:16:22'),
(1225, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004630, '2026-04-19 19:16:30'),
(1226, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004639, '2026-04-19 19:16:39'),
(1227, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004646, '2026-04-19 19:16:46'),
(1228, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004702, '2026-04-19 19:17:02'),
(1229, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004702, '2026-04-19 19:17:02'),
(1230, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004713, '2026-04-19 19:17:13'),
(1231, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004718, '2026-04-19 19:17:18'),
(1232, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004726, '2026-04-19 19:17:26'),
(1233, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004734, '2026-04-19 19:17:34'),
(1234, 1, 28.47555432, 77.06124933, 0, 0, 86, 10, 20260420004742, '2026-04-19 19:17:42'),
(1235, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004754, '2026-04-19 19:17:54'),
(1236, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004812, '2026-04-19 19:18:12'),
(1237, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004828, '2026-04-19 19:18:28'),
(1238, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004830, '2026-04-19 19:18:30'),
(1239, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004838, '2026-04-19 19:18:38'),
(1240, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004846, '2026-04-19 19:18:46'),
(1241, 1, 28.47555999, 77.06133996, 0, 0, 85, 10, 20260420004854, '2026-04-19 19:18:54'),
(1242, 1, 28.47554433, 77.06137571, 0, 0, 85, 10, 20260420004904, '2026-04-19 19:19:04'),
(1243, 1, 28.47554433, 77.06137571, 0, 0, 85, 10, 20260420004910, '2026-04-19 19:19:10'),
(1244, 1, 28.47554433, 77.06137571, 0, 0, 85, 10, 20260420004918, '2026-04-19 19:19:18'),
(1245, 1, 28.47554433, 77.06137571, 0, 0, 85, 10, 20260420004926, '2026-04-19 19:19:26'),
(1246, 1, 28.47554433, 77.06137571, 0, 0, 85, 10, 20260420004934, '2026-04-19 19:19:34'),
(1247, 1, 28.41393705, 77.04139254, 0, 0, 77, 10, 20260420092415, '2026-04-20 03:54:15'),
(1248, 1, 28.41393705, 77.04139254, 0, 0, 77, 10, 20260420092424, '2026-04-20 03:54:24'),
(1249, 1, 28.41393705, 77.04139254, 0, 0, 77, 10, 20260420092430, '2026-04-20 03:54:30'),
(1250, 1, 28.41393705, 77.04139254, 0, 0, 77, 10, 20260420092438, '2026-04-20 03:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `engineer_skills`
--

CREATE TABLE `engineer_skills` (
  `engineer_id` int UNSIGNED NOT NULL,
  `skill_id` int UNSIGNED NOT NULL,
  `certified_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `engineer_wallet`
--

CREATE TABLE `engineer_wallet` (
  `id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_earned` decimal(10,2) DEFAULT '0.00',
  `total_withdrawn` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `engineer_wallet`
--

INSERT INTO `engineer_wallet` (`id`, `engineer_id`, `balance`, `updated_at`, `total_earned`, `total_withdrawn`) VALUES
(1, 1, 1030.40, '2026-04-07 16:25:33', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `engineer_wallet_transactions`
--

CREATE TABLE `engineer_wallet_transactions` (
  `id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED DEFAULT NULL,
  `type` enum('credit','debit','withdrawal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `engineer_wallet_transactions`
--

INSERT INTO `engineer_wallet_transactions` (`id`, `engineer_id`, `job_id`, `type`, `amount`, `description`, `created_at`) VALUES
(1, 1, 3, 'credit', 957.60, 'Job #JOB-260329-4268 completed. Platform fee: ₹239.40', '2026-03-29 17:14:59'),
(2, 1, 4, 'credit', 557.60, 'Job #JOB-260329-9076 completed. Platform fee: ₹139.40', '2026-03-29 17:24:38'),
(3, 1, 8, 'credit', 557.60, 'Job #JOB-260401-4180 completed. Platform fee: ₹139.40', '2026-04-05 08:41:03'),
(4, 1, NULL, 'withdrawal', 2000.00, 'cash', '2026-04-05 15:07:21'),
(5, 1, 9, 'credit', 957.60, 'Job #JOB-260407-3443 completed. Platform fee: ₹239.40', '2026-04-07 16:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(30) NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `discount` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) DEFAULT '0.00',
  `status` enum('draft','sent','paid','cancelled') DEFAULT 'draft',
  `payment_method` enum('cash','upi','card','wallet','online') DEFAULT 'cash',
  `payment_gateway` varchar(50) DEFAULT NULL,
  `gateway_txn_id` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `pdf_url` varchar(500) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `job_id`, `customer_id`, `subtotal`, `discount`, `tax`, `total`, `status`, `payment_method`, `payment_gateway`, `gateway_txn_id`, `paid_at`, `pdf_url`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'INV-260329-2719', 3, 1, 0.00, 0.00, 0.00, 1197.00, 'draft', 'cash', NULL, NULL, NULL, NULL, NULL, '2026-03-29 17:14:59', '2026-03-29 17:14:59'),
(2, 'INV-260329-1208', 4, 1, 0.00, 0.00, 0.00, 697.00, 'draft', 'cash', NULL, NULL, NULL, NULL, NULL, '2026-03-29 17:24:38', '2026-03-29 17:24:38'),
(3, 'INV-260405-7336', 8, 1, 0.00, 0.00, 0.00, 697.00, 'draft', 'cash', NULL, NULL, NULL, NULL, NULL, '2026-04-05 08:41:03', '2026-04-05 08:41:03'),
(4, 'INV-260407-5564', 9, 1, 0.00, 0.00, 0.00, 1197.00, 'draft', 'cash', NULL, NULL, NULL, NULL, NULL, '2026-04-07 16:25:33', '2026-04-07 16:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int UNSIGNED NOT NULL,
  `job_number` varchar(20) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED DEFAULT NULL,
  `preferred_engineer_id` int UNSIGNED DEFAULT NULL,
  `service_id` int UNSIGNED DEFAULT '1',
  `service_type` varchar(100) NOT NULL,
  `description` text,
  `photo` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text NOT NULL,
  `status` enum('pending','assigned','accepted','on_the_way','arrived','working','awaiting_quotation','quotation_sent','quotation_approved','quotation_rejected','pickup_requested','device_picked','revisit_scheduled','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `scheduled_date` date DEFAULT NULL,
  `slot_id` int UNSIGNED DEFAULT NULL,
  `is_emergency` tinyint(1) DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `final_amount` decimal(10,2) DEFAULT NULL,
  `visit_charge` decimal(10,2) DEFAULT '0.00',
  `platform_charge` decimal(10,2) DEFAULT '0.00',
  `promo_code` varchar(50) DEFAULT NULL,
  `emergency_fee` decimal(10,2) DEFAULT '0.00',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `km_charge` decimal(10,2) DEFAULT '0.00',
  `engineer_distance_km` decimal(8,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_method` varchar(30) DEFAULT 'cash',
  `razorpay_payment_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `job_number`, `customer_id`, `engineer_id`, `preferred_engineer_id`, `service_id`, `service_type`, `description`, `photo`, `latitude`, `longitude`, `address`, `status`, `priority`, `scheduled_date`, `slot_id`, `is_emergency`, `amount`, `discount_amount`, `final_amount`, `visit_charge`, `platform_charge`, `promo_code`, `emergency_fee`, `start_time`, `end_time`, `notes`, `created_at`, `updated_at`, `km_charge`, `engineer_distance_km`, `payment_status`, `payment_method`, `razorpay_payment_id`) VALUES
(1, 'JOB-260329-3226', 1, 1, NULL, 40, 'Battery', 'NOT CHARGING', NULL, 28.60825600, 77.37608700, 'D1 , NEAR HDFC BANK', 'completed', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 0.00, NULL, 0.00, '2026-03-29 16:06:35', '2026-03-29 16:10:13', NULL, '2026-03-29 16:05:50', '2026-03-29 16:10:13', 0.00, 0.03, 'pending', 'cash', NULL),
(2, 'JOB-260329-6478', 1, NULL, NULL, 37, 'Laptop Not Turning On', 'not power on', NULL, 28.60831000, 77.37589500, 'fghj', 'cancelled', 'normal', NULL, NULL, 0, 599.00, 0.00, 898.00, 299.00, 0.00, NULL, 0.00, NULL, NULL, ' | Customer cancellation: Cancelled by customer', '2026-03-29 16:31:30', '2026-03-29 17:16:32', 0.00, NULL, 'pending', 'cash', NULL),
(3, 'JOB-260329-4268', 1, 1, NULL, 39, 'Keyboard', 'not power onsdfghjk', NULL, 28.60793800, 77.37590900, 'fghj', 'completed', 'normal', NULL, NULL, 0, 599.00, 0.00, 898.00, 299.00, 239.40, NULL, 0.00, '2026-03-29 17:00:38', '2026-03-29 17:14:59', NULL, '2026-03-29 17:00:17', '2026-03-29 17:14:59', 0.00, NULL, 'pending', 'cash', NULL),
(4, 'JOB-260329-9076', 1, 1, NULL, 40, 'Battery', 'battery not charging', NULL, 28.60846500, 77.37624600, ';lkjhgfd', 'completed', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 139.40, NULL, 0.00, '2026-03-29 17:22:08', '2026-03-29 17:24:38', NULL, '2026-03-29 17:21:47', '2026-03-29 17:24:38', 0.00, NULL, 'pending', 'cash', NULL),
(5, 'JOB-260401-1378', 1, NULL, NULL, 40, 'Battery', 'cvbnm', NULL, 28.47532800, 77.06153700, 'dfghjk', 'cancelled', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 0.00, NULL, 0.00, NULL, NULL, ' | Customer cancellation: no', '2026-03-31 19:36:44', '2026-03-31 19:37:34', 0.00, NULL, 'pending', 'cash', NULL),
(6, 'JOB-260401-7595', 1, NULL, NULL, 40, 'Battery', 'dfghjk', NULL, 28.47532500, 77.06153200, 'dfghjkl', 'cancelled', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 0.00, NULL, 0.00, NULL, NULL, ' | Customer cancellation: GHJK', '2026-03-31 19:37:53', '2026-03-31 20:00:52', 0.00, NULL, 'pending', 'cash', NULL),
(7, 'JOB-260401-3652', 1, NULL, NULL, 40, 'Battery', 'dfghjk', NULL, 28.47532600, 77.06156100, 'dfghjkl', 'cancelled', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 0.00, NULL, 0.00, NULL, NULL, NULL, '2026-03-31 20:01:04', '2026-04-01 01:55:44', 0.00, NULL, 'pending', 'cash', NULL),
(8, 'JOB-260401-4180', 1, 1, NULL, 40, 'Battery', 'SDFGHJ', NULL, 28.47541400, 77.06152800, 'WERTYUIO', 'completed', 'normal', NULL, NULL, 0, 299.00, 0.00, 498.00, 199.00, 139.40, NULL, 0.00, '2026-04-05 08:40:36', '2026-04-05 08:41:03', NULL, '2026-04-01 01:56:13', '2026-04-05 08:41:03', 0.00, NULL, 'pending', 'cash', NULL),
(9, 'JOB-260407-3443', 1, 1, NULL, 37, 'Laptop Not Turning On', 'asdfghjkl', NULL, 28.41101800, 77.03731000, 'asdfghjkl', 'completed', 'normal', NULL, NULL, 0, 599.00, 0.00, 898.00, 299.00, 239.40, NULL, 0.00, '2026-04-07 16:23:19', '2026-04-07 16:25:33', NULL, '2026-04-07 16:20:21', '2026-04-07 16:25:33', 0.00, NULL, 'pending', 'cash', NULL),
(10, 'JOB-260420-9043', 1, 1, NULL, 38, 'Screen', 'sdfghjkl', NULL, 28.47556800, 77.06124900, 'Ward no-26, Shahpur Road', 'revisit_scheduled', 'normal', '2026-04-21', 3, 0, 599.00, 0.00, 1501.00, 299.00, 0.00, NULL, 0.00, '2026-04-19 19:15:44', NULL, NULL, '2026-04-19 19:14:40', '2026-04-19 19:19:02', 0.00, NULL, 'pending', 'cash', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
  `offered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `job_offers`
--

INSERT INTO `job_offers` (`id`, `job_id`, `engineer_id`, `status`, `offered_at`, `responded_at`) VALUES
(1, 1, 1, 'accepted', '2026-03-29 16:05:50', '2026-03-29 16:06:02'),
(2, 3, 1, 'accepted', '2026-03-29 17:00:17', '2026-03-29 17:00:31'),
(3, 4, 1, 'accepted', '2026-03-29 17:21:47', '2026-03-29 17:21:55'),
(4, 5, 1, 'rejected', '2026-03-31 19:36:44', '2026-03-31 19:37:03'),
(5, 6, 1, 'expired', '2026-03-31 19:37:53', '2026-03-31 20:00:52'),
(6, 10, 1, 'accepted', '2026-04-19 19:14:40', '2026-04-19 19:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `job_parts_used`
--

CREATE TABLE `job_parts_used` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `part_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `qty` int NOT NULL,
  `unit_price` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_photos`
--

CREATE TABLE `job_photos` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `photo_type` enum('before','after','during') NOT NULL DEFAULT 'before',
  `photo_url` varchar(500) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `job_photos`
--

INSERT INTO `job_photos` (`id`, `job_id`, `engineer_id`, `photo_type`, `photo_url`, `caption`, `created_at`) VALUES
(1, 3, 1, 'before', 'https://www.fixgrid.in/api/uploads/job_photos/69c95dea26219.jpg', '', '2026-03-29 17:14:18'),
(2, 3, 1, 'after', 'https://www.fixgrid.in/api/uploads/job_photos/69c95e3849d9d.jpg', '', '2026-03-29 17:15:36'),
(3, 4, 1, 'before', 'https://www.fixgrid.in/api/uploads/job_photos/69c9604a51f3b.jpg', '', '2026-03-29 17:24:26'),
(4, 4, 1, 'after', 'https://www.fixgrid.in/api/uploads/job_photos/69c9605188215.jpg', '', '2026-03-29 17:24:33'),
(5, 8, 1, 'before', 'https://www.fixgrid.in/api/uploads/job_photos/69d2201bb3862.jpg', '', '2026-04-05 08:40:59'),
(6, 8, 1, 'after', 'https://www.fixgrid.in/api/uploads/job_photos/69d2201eab222.jpg', '', '2026-04-05 08:41:02'),
(7, 9, 1, 'before', 'https://www.fixgrid.in/api/uploads/job_photos/69d52fec4a6a4.png', '', '2026-04-07 16:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `job_quotations`
--

CREATE TABLE `job_quotations` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `request_notes` text COMMENT 'Engineer notes about parts needed',
  `parts_details` text COMMENT 'JSON array of parts [{name,qty,est_price}]',
  `quotation_amount` decimal(10,2) DEFAULT NULL COMMENT 'Admin filled quotation total',
  `admin_notes` text,
  `status` enum('requested','sent','approved','rejected') DEFAULT 'requested',
  `customer_approved_at` timestamp NULL DEFAULT NULL,
  `revisit_date` date DEFAULT NULL,
  `revisit_slot_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parts_cost` decimal(10,2) DEFAULT '0.00',
  `installation_charge` decimal(10,2) DEFAULT '0.00',
  `first_visit_charge` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `job_quotations`
--

INSERT INTO `job_quotations` (`id`, `job_id`, `engineer_id`, `request_notes`, `parts_details`, `quotation_amount`, `admin_notes`, `status`, `customer_approved_at`, `revisit_date`, `revisit_slot_id`, `created_at`, `updated_at`, `parts_cost`, `installation_charge`, `first_visit_charge`) VALUES
(1, 10, 1, 'sdfghjkl', '[{\"name\":\"keyboard(abcd)\",\"qty\":1,\"est_price\":\"\",\"unit\":\"piece\"}]', 1501.00, '', 'approved', '2026-04-19 19:19:02', '2026-04-21', 3, '2026-04-19 19:16:40', '2026-04-19 19:19:02', 1500.00, 300.00, 299.00);

-- --------------------------------------------------------

--
-- Table structure for table `message_log`
--

CREATE TABLE `message_log` (
  `id` int UNSIGNED NOT NULL,
  `channel` enum('whatsapp','sms','email','push') NOT NULL,
  `recipient` varchar(100) NOT NULL,
  `template` varchar(100) DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `user_type` enum('customer','engineer','admin') NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `title`, `body`, `data`, `is_read`, `created_at`) VALUES
(1, 'engineer', 1, '🔔 New Job', 'Job #JOB-260329-3226 — Battery | Score: 0.3', '{\"job_id\": \"1\"}', 1, '2026-03-29 16:05:50'),
(2, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260329-3226.', '{\"job_id\": 1}', 0, '2026-03-29 16:06:02'),
(3, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 1}', 0, '2026-03-29 16:06:30'),
(4, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 1}', 0, '2026-03-29 16:06:33'),
(5, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 1}', 0, '2026-03-29 16:06:35'),
(6, 'admin', 0, 'Device Pickup Requested', 'Engineer PAWAN KUMAR requested pickup for job #JOB-260329-3226: DELL VOSTRO 3420', '{\"type\": \"pickup_requested\", \"job_id\": 1, \"pickup_id\": \"1\"}', 0, '2026-03-29 16:08:02'),
(7, 'customer', 1, 'Device Pickup', 'Your DELL VOSTRO 3420 will be picked up for repair. We will contact you to schedule.', '{\"job_id\": 1}', 0, '2026-03-29 16:08:02'),
(8, 'customer', 1, 'Device Update', 'Device picked up', '{\"job_id\": \"1\"}', 0, '2026-03-29 16:09:22'),
(9, 'customer', 1, 'Device Update', 'Device delivered', '{\"job_id\": \"1\"}', 0, '2026-03-29 16:10:13'),
(10, 'engineer', 1, '🔔 New Job', 'Job #JOB-260329-4268 — Keyboard | Score: 1', '{\"job_id\": \"3\"}', 1, '2026-03-29 17:00:17'),
(11, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260329-4268.', '{\"job_id\": 3}', 0, '2026-03-29 17:00:31'),
(12, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 3}', 0, '2026-03-29 17:00:33'),
(13, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 3}', 0, '2026-03-29 17:00:35'),
(14, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 3}', 0, '2026-03-29 17:00:38'),
(15, 'customer', 1, 'Job Completed', 'Job #JOB-260329-4268 completed.', '{\"job_id\": 3}', 0, '2026-03-29 17:14:59'),
(16, 'customer', 1, 'Job Cancelled', 'Your job #JOB-260329-6478 has been cancelled.', '{\"job_id\": 2}', 0, '2026-03-29 17:16:32'),
(17, 'engineer', 1, '🔔 New Job', 'Job #JOB-260329-9076 — Battery | Score: 1', '{\"job_id\": \"4\"}', 1, '2026-03-29 17:21:47'),
(18, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260329-9076.', '{\"job_id\": 4}', 0, '2026-03-29 17:21:55'),
(19, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 4}', 0, '2026-03-29 17:21:57'),
(20, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 4}', 0, '2026-03-29 17:21:59'),
(21, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 4}', 0, '2026-03-29 17:22:08'),
(22, 'customer', 1, 'Job Completed', 'Job #JOB-260329-9076 completed.', '{\"job_id\": 4}', 0, '2026-03-29 17:24:38'),
(23, 'engineer', 1, '🔔 New Job', 'Job #JOB-260401-1378 — Battery | Score: 1', '{\"job_id\": \"5\"}', 1, '2026-03-31 19:36:44'),
(24, 'admin', 0, 'Job Rejected', 'PAWAN KUMAR rejected job #JOB-260401-1378. Reason: no idea', '{\"job_id\": 5, \"reason\": \"no idea\"}', 0, '2026-03-31 19:37:03'),
(25, 'customer', 1, 'Job Cancelled', 'Your job #JOB-260401-1378 has been cancelled.', '{\"job_id\": 5}', 0, '2026-03-31 19:37:34'),
(26, 'engineer', 1, '🔔 New Job', 'Job #JOB-260401-7595 — Battery | Score: 1', '{\"job_id\": \"6\"}', 1, '2026-03-31 19:37:53'),
(27, 'customer', 1, 'Job Cancelled', 'Your job #JOB-260401-7595 has been cancelled.', '{\"job_id\": 6}', 0, '2026-03-31 20:00:52'),
(28, 'engineer', 1, 'Job Assigned', 'Job #JOB-260401-4180 assigned by admin.', '{\"job_id\": 8}', 1, '2026-04-05 08:40:12'),
(29, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260401-4180.', '{\"job_id\": 8}', 0, '2026-04-05 08:40:28'),
(30, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 8}', 0, '2026-04-05 08:40:33'),
(31, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 8}', 0, '2026-04-05 08:40:35'),
(32, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 8}', 0, '2026-04-05 08:40:36'),
(33, 'customer', 1, 'Job Completed', 'Job #JOB-260401-4180 completed.', '{\"job_id\": 8}', 0, '2026-04-05 08:41:03'),
(34, 'engineer', 1, 'Job Assigned', 'Job #JOB-260407-3443 assigned by admin.', '{\"job_id\": 9}', 1, '2026-04-07 16:20:56'),
(35, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260407-3443.', '{\"job_id\": 9}', 0, '2026-04-07 16:21:16'),
(36, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 9}', 0, '2026-04-07 16:22:30'),
(37, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 9}', 0, '2026-04-07 16:22:51'),
(38, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 9}', 0, '2026-04-07 16:23:19'),
(39, 'customer', 1, 'Job Completed', 'Job #JOB-260407-3443 completed.', '{\"job_id\": 9}', 0, '2026-04-07 16:25:33'),
(40, 'engineer', 1, '📍 New Job — Outside Your Zone', 'Job #JOB-260420-9043 — Screen | No zone engineers available in this area', '{\"type\": \"nearby_fallback\", \"job_id\": \"10\", \"outside_zone\": true}', 1, '2026-04-19 19:14:40'),
(41, 'customer', 1, 'Engineer Accepted', 'PAWAN KUMAR accepted job #JOB-260420-9043.', '{\"job_id\": 10}', 0, '2026-04-19 19:14:57'),
(42, 'customer', 1, 'Job Update', 'Engineer is on the way.', '{\"job_id\": 10}', 0, '2026-04-19 19:15:40'),
(43, 'customer', 1, 'Job Update', 'Engineer has arrived.', '{\"job_id\": 10}', 0, '2026-04-19 19:15:42'),
(44, 'customer', 1, 'Job Update', 'Engineer has started working.', '{\"job_id\": 10}', 0, '2026-04-19 19:15:44'),
(45, 'admin', 0, 'Quotation Requested', 'Engineer PAWAN KUMAR requested parts quotation for job #JOB-260420-9043', '{\"type\": \"quotation_requested\", \"job_id\": 10, \"quotation_id\": \"1\"}', 0, '2026-04-19 19:16:40'),
(46, 'customer', 1, 'Parts Required', 'Engineer identified parts needed for job #JOB-260420-9043. Quotation coming soon.', '{\"job_id\": 10}', 0, '2026-04-19 19:16:40'),
(47, 'customer', 1, 'Quotation Ready', 'Quotation ready for job #JOB-260420-9043:\nParts: ₹1,500.00\nInstallation: ₹300.00\nVisit paid: -₹299.00\nYou pay on revisit: ₹1,501.00\nRevisit: 2026-04-21 (Slot 3  — 10:00 AM – 11:30 AM)', '{\"job_id\": \"10\", \"quotation_id\": 1}', 0, '2026-04-19 19:18:25'),
(48, 'engineer', 1, 'Quotation Approved', 'Customer approved job #JOB-260420-9043. Revisit on 2026-04-21', '{\"job_id\": \"10\"}', 1, '2026-04-19 19:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','engineer') NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `user_type`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 'bookelsc@gmail.com', 'engineer', 'bd2143cd974704e35b6b0fb35646a34730c112f8dcc98dc7f11677d0cef19d17', '2026-04-05 08:58:26', 1, '2026-04-05 08:28:26'),
(2, 'bookelsc@gmail.com', 'engineer', 'e90c01dd25214b02e818066e543d6c18934aa3f1f83966d48cdb7105a37eb128', '2026-04-05 09:07:59', 1, '2026-04-05 08:37:59');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `txn_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purpose` varchar(20) DEFAULT 'wallet',
  `job_id` int UNSIGNED DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `status` enum('pending','paid','failed','initiated','success','refunded') DEFAULT 'pending',
  `raw_response` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` timestamp NULL DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_fee_collections`
--

CREATE TABLE `platform_fee_collections` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','collected') DEFAULT 'pending',
  `collected_at` timestamp NULL DEFAULT NULL,
  `collected_by` int UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `platform_fee_collections`
--

INSERT INTO `platform_fee_collections` (`id`, `job_id`, `engineer_id`, `amount`, `status`, `collected_at`, `collected_by`, `note`, `created_at`) VALUES
(1, 8, 1, 139.40, 'collected', '2026-04-05 15:07:36', NULL, 'Cash collected', '2026-04-05 15:07:36');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percent','flat') DEFAULT 'flat',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int UNSIGNED NOT NULL,
  `job_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `rating` tinyint NOT NULL,
  `feedback` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `job_id`, `customer_id`, `engineer_id`, `rating`, `feedback`, `created_at`) VALUES
(1, 1, 1, 1, 5, NULL, '2026-03-29 16:10:21'),
(2, 4, 1, 1, 5, NULL, '2026-03-29 17:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int UNSIGNED NOT NULL,
  `referrer_id` int UNSIGNED NOT NULL,
  `referred_id` int UNSIGNED NOT NULL,
  `reward_given` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_types`
--

CREATE TABLE `service_types` (
  `id` int UNSIGNED NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = parent category, set = sub-service',
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 0xF09F94A7,
  `base_price` decimal(10,2) DEFAULT '0.00',
  `visit_charge` decimal(10,2) DEFAULT '0.00',
  `platform_charge_pct` decimal(5,2) DEFAULT '20.00',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `required_skill_id` int UNSIGNED DEFAULT NULL,
  `per_km_rate` decimal(8,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_types`
--

INSERT INTO `service_types` (`id`, `parent_id`, `name`, `icon`, `base_price`, `visit_charge`, `platform_charge_pct`, `is_active`, `sort_order`, `required_skill_id`, `per_km_rate`) VALUES
(36, NULL, 'LAPTOP', '🔧', 0.00, 0.00, 20.00, 1, 0, NULL, 0.00),
(37, 36, 'Laptop Not Turning On', '🔧', 599.00, 299.00, 10.00, 1, 0, NULL, 0.00),
(38, 36, 'Screen', '🔧', 599.00, 299.00, 10.00, 1, 0, NULL, 0.00),
(39, 36, 'Keyboard', '🔧', 599.00, 299.00, 10.00, 1, 0, NULL, 0.00),
(40, 36, 'Battery', '🔧', 299.00, 199.00, 10.00, 1, 0, NULL, 12.00),
(42, NULL, 'AC', '🔧', 0.00, 0.00, 20.00, 1, 0, NULL, 0.00),
(43, 42, 'Low cooling / No cooling', '🔧', 399.00, 299.00, 20.00, 1, 0, NULL, 12.00),
(44, 42, 'General Service / Cleaning', '🔧', 300.00, 299.00, 20.00, 1, 0, NULL, 12.00),
(45, 42, 'Electrical Repairs', '🔧', 500.00, 299.00, 20.00, 1, 0, NULL, 12.00),
(46, 42, 'Water Leakage Issues', '🔧', 500.00, 299.00, 20.00, 1, 0, NULL, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spare_parts`
--

CREATE TABLE `spare_parts` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `description` text,
  `unit` varchar(20) DEFAULT 'piece',
  `cost_price` decimal(10,2) DEFAULT '0.00',
  `sell_price` decimal(10,2) DEFAULT '0.00',
  `stock_qty` int DEFAULT '0',
  `min_stock` int DEFAULT '5',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hsn_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int UNSIGNED NOT NULL,
  `label` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `label`, `start_time`, `end_time`, `is_active`) VALUES
(1, 'Slot 1  — 7:00 AM – 8:30 AM', '07:00:00', '08:30:00', 1),
(2, 'Slot 2  — 8:30 AM – 10:00 AM', '08:30:00', '10:00:00', 1),
(3, 'Slot 3  — 10:00 AM – 11:30 AM', '10:00:00', '11:30:00', 1),
(4, 'Slot 4  — 11:30 AM – 1:00 PM', '11:30:00', '13:00:00', 1),
(5, 'Slot 5  — 1:00 PM – 2:30 PM', '13:00:00', '14:30:00', 1),
(6, 'Slot 6  — 2:30 PM – 4:00 PM', '14:30:00', '16:00:00', 1),
(7, 'Slot 7  — 4:00 PM – 5:00 PM', '16:00:00', '17:00:00', 1),
(8, 'Slot 8  — 5:00 PM – 6:00 PM', '17:00:00', '18:00:00', 1),
(9, 'Slot 9  — 6:00 PM – 7:00 PM', '18:00:00', '19:00:00', 1),
(10, 'Slot 10 — 7:00 PM – 8:00 PM', '19:00:00', '20:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ref_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `description` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_km` decimal(6,2) DEFAULT '10.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`id`, `name`, `city`, `state`, `description`, `latitude`, `longitude`, `radius_km`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cyber City', 'Gurgaon', 'Haryana', 'DLF Cyber City — corporate IT hub', 28.49508100, 77.08849600, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:07:00'),
(2, 'MG Road', 'Gurgaon', 'Haryana', 'MG Road corridor — retail and commercial', 28.47983700, 77.02664700, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:05:50'),
(3, 'Sohna Road', 'Gurgaon', 'Haryana', 'Sohna Road — residential and business parks', 28.42188700, 77.05770000, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:09:13'),
(4, 'Golf Course Road', 'Gurgaon', 'Haryana', 'Golf Course Road — premium residential offices', 28.45779500, 77.10019800, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:06:47'),
(5, 'Udyog Vihar', 'Gurgaon', 'Haryana', 'Udyog Vihar Phase 1-6 — IT parks', 28.50623100, 77.07831400, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:07:11'),
(6, 'Sector 14-17', 'Gurgaon', 'Haryana', 'Old Gurgaon — mixed residential and commercial', 28.47213600, 77.02387500, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:06:04'),
(7, 'South City', 'Gurgaon', 'Haryana', 'South City 1 and 2 — residential township', 28.41710000, 77.01080000, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:08:41'),
(8, 'Manesar', 'Gurgaon', 'Haryana', 'IMT Manesar — industrial and DSIDC zone', 28.35683500, 76.93888200, 2.00, 1, '2026-03-31 19:15:58', '2026-04-16 19:07:30');

-- --------------------------------------------------------

--
-- Table structure for table `zone_engineers`
--

CREATE TABLE `zone_engineers` (
  `id` int UNSIGNED NOT NULL,
  `zone_id` int UNSIGNED NOT NULL,
  `engineer_id` int UNSIGNED NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `zone_engineers`
--

INSERT INTO `zone_engineers` (`id`, `zone_id`, `engineer_id`, `is_available`, `created_at`) VALUES
(5, 2, 1, 1, '2026-04-16 16:17:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user` (`user_type`,`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_id` (`job_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `engineer_id` (`engineer_id`);

--
-- Indexes for table `completion_otps`
--
ALTER TABLE `completion_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `customer_wallet`
--
ALTER TABLE `customer_wallet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`);

--
-- Indexes for table `device_pickups`
--
ALTER TABLE `device_pickups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_id` (`job_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `engineers`
--
ALTER TABLE `engineers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `engineer_availability`
--
ALTER TABLE `engineer_availability`
  ADD PRIMARY KEY (`engineer_id`,`slot_id`,`date`),
  ADD KEY `engineer_id` (`engineer_id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `engineer_earnings`
--
ALTER TABLE `engineer_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `engineer_id` (`engineer_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `engineer_inventory`
--
ALTER TABLE `engineer_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `engineer_id` (`engineer_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `engineer_locations`
--
ALTER TABLE `engineer_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_et` (`engineer_id`,`timestamp`);

--
-- Indexes for table `engineer_skills`
--
ALTER TABLE `engineer_skills`
  ADD PRIMARY KEY (`engineer_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `engineer_wallet`
--
ALTER TABLE `engineer_wallet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `engineer_id` (`engineer_id`);

--
-- Indexes for table `engineer_wallet_transactions`
--
ALTER TABLE `engineer_wallet_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_number` (`job_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `engineer_id` (`engineer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_date`,`status`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_job_engineer` (`job_id`,`engineer_id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_engineer_id` (`engineer_id`);

--
-- Indexes for table `job_parts_used`
--
ALTER TABLE `job_parts_used`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `part_id` (`part_id`),
  ADD KEY `engineer_id` (`engineer_id`);

--
-- Indexes for table `job_photos`
--
ALTER TABLE `job_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `job_quotations`
--
ALTER TABLE `job_quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_engineer_id` (`engineer_id`);

--
-- Indexes for table `message_log`
--
ALTER TABLE `message_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `platform_fee_collections`
--
ALTER TABLE `platform_fee_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_job` (`job_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_id` (`job_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `engineer_id` (`engineer_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Indexes for table `service_types`
--
ALTER TABLE `service_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `spare_parts`
--
ALTER TABLE `spare_parts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zone_engineers`
--
ALTER TABLE `zone_engineers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_zone_engineer` (`zone_id`,`engineer_id`),
  ADD KEY `idx_zone_id` (`zone_id`),
  ADD KEY `idx_engineer_id` (`engineer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `completion_otps`
--
ALTER TABLE `completion_otps`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_wallet`
--
ALTER TABLE `customer_wallet`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `device_pickups`
--
ALTER TABLE `device_pickups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `engineers`
--
ALTER TABLE `engineers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `engineer_earnings`
--
ALTER TABLE `engineer_earnings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `engineer_inventory`
--
ALTER TABLE `engineer_inventory`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `engineer_locations`
--
ALTER TABLE `engineer_locations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1251;

--
-- AUTO_INCREMENT for table `engineer_wallet`
--
ALTER TABLE `engineer_wallet`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `engineer_wallet_transactions`
--
ALTER TABLE `engineer_wallet_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_parts_used`
--
ALTER TABLE `job_parts_used`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_photos`
--
ALTER TABLE `job_photos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `job_quotations`
--
ALTER TABLE `job_quotations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `message_log`
--
ALTER TABLE `message_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_fee_collections`
--
ALTER TABLE `platform_fee_collections`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_types`
--
ALTER TABLE `service_types`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spare_parts`
--
ALTER TABLE `spare_parts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `zone_engineers`
--
ALTER TABLE `zone_engineers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD CONSTRAINT `chat_rooms_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_rooms_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `chat_rooms_ibfk_3` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`);

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `customer_wallet`
--
ALTER TABLE `customer_wallet`
  ADD CONSTRAINT `customer_wallet_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `engineer_availability`
--
ALTER TABLE `engineer_availability`
  ADD CONSTRAINT `engineer_availability_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `engineer_availability_ibfk_2` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `engineer_earnings`
--
ALTER TABLE `engineer_earnings`
  ADD CONSTRAINT `engineer_earnings_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`),
  ADD CONSTRAINT `engineer_earnings_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`);

--
-- Constraints for table `engineer_inventory`
--
ALTER TABLE `engineer_inventory`
  ADD CONSTRAINT `engineer_inventory_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `engineer_inventory_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `spare_parts` (`id`);

--
-- Constraints for table `engineer_locations`
--
ALTER TABLE `engineer_locations`
  ADD CONSTRAINT `engineer_locations_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `engineer_skills`
--
ALTER TABLE `engineer_skills`
  ADD CONSTRAINT `engineer_skills_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `engineer_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Constraints for table `engineer_wallet`
--
ALTER TABLE `engineer_wallet`
  ADD CONSTRAINT `engineer_wallet_ibfk_1` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `job_parts_used`
--
ALTER TABLE `job_parts_used`
  ADD CONSTRAINT `job_parts_used_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_parts_used_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `spare_parts` (`id`),
  ADD CONSTRAINT `job_parts_used_ibfk_3` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`);

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`engineer_id`) REFERENCES `engineers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
