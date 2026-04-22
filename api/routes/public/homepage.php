<?php
// routes/public/homepage.php — Public: read homepage content (no auth needed)
require_once dirname(__DIR__, 2) . '/config.php';

$db = getDB();
$rows = $db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'hp_%' OR setting_key IN ('company_name','company_phone','company_email')")->fetchAll(PDO::FETCH_KEY_PAIR);

// Defaults for all homepage sections
$defaults = [
    // Hero
    'hp_hero_badge'       => 'Technician at your doorstep in 60 minutes',
    'hp_hero_title1'      => 'Your Local',
    'hp_hero_title2'      => 'Professional',
    'hp_hero_title3'      => 'Service Network.',
    'hp_hero_subtitle'    => 'Fast, reliable and verified professionals for AC repair, electrician, IT support, CCTV, plumbing and more — across Gurgaon NCR and Bihar.',
    'hp_hero_btn1'        => '⚡ Book a Technician',
    'hp_hero_btn2'        => 'View Services',
    'hp_hero_trust1'      => 'Verified professionals',
    'hp_hero_trust2'      => '7-day guarantee',
    'hp_hero_trust3'      => 'Transparent pricing',
    'hp_hero_stat1_num'   => '500+',
    'hp_hero_stat1_lbl'   => 'Happy clients',
    'hp_hero_stat2_num'   => '60 min',
    'hp_hero_stat2_lbl'   => 'Response',
    'hp_hero_stat3_num'   => '100%',
    'hp_hero_stat3_lbl'   => 'Verified',
    // Marquee
    'hp_marquee'          => 'Technician at your doorstep in 60 minutes|Fast service. Trusted professionals.|From home repairs to IT support – all in one app|Skip the hassle. Book FixGrid.|Verified professionals. Service guarantee included.',
    // Services
    'hp_services_tag'     => 'What We Do',
    'hp_services_title'   => 'All Services, One Platform',
    'hp_services_sub'     => 'Certified professionals for every home and business need. Book in under 2 minutes.',
    'hp_services_list'    => 'AC Repair & Service|❄️|From ₹499|Electrician|⚡|From ₹299|IT Support|💻|From ₹499|CCTV Installation|📷|From ₹2,999|Plumbing|🔧|From ₹199|Appliance Repair|🏠|From ₹399|Cleaning Services|🧹|From ₹799|Networking & WiFi|📡|From ₹999',
    // How it works
    'hp_how_tag'          => 'How It Works',
    'hp_how_title'        => 'Book in 3 Simple Steps',
    'hp_how_sub'          => 'No complicated forms. No long waits. Just fast professional service.',
    'hp_how_steps'        => 'Choose Service|Pick from our 8+ service categories on the app or website.|Get Matched|We find the nearest verified professional in your zone automatically.|Technician Arrives|Your professional arrives within 60 minutes, ready to fix the problem.|Rate & Pay|Pay after the job. Rate your experience. 7-day service guarantee.',
    // Why FixGrid
    'hp_why_title'        => 'Why Choose FixGrid?',
    'hp_why_sub'          => "We're not just another service app. We're your trusted local network of verified professionals.",
    'hp_why_feats'        => '⚡|60-Minute Response|Zone-based dispatch ensures a technician reaches you faster than anyone else.|🔒|Background Verified|Every professional undergoes ID verification, skill testing and background screening.|💰|Transparent Pricing|Fixed quotes upfront. No surprise charges. Pay only after the job is done.|⭐|7-Day Guarantee|Not satisfied? We send another professional at no additional cost.',
    'hp_stat1_num'        => '500+',
    'hp_stat1_lbl'        => 'Clients Served',
    'hp_stat2_num'        => '60 min',
    'hp_stat2_lbl'        => 'Avg Response Time',
    'hp_stat3_num'        => '100%',
    'hp_stat3_lbl'        => 'Verified Professionals',
    'hp_stat4_num'        => '7-day',
    'hp_stat4_lbl'        => 'Service Guarantee',
    // Zones
    'hp_zones_tag'        => 'Service Areas',
    'hp_zones_title'      => 'Where We Operate',
    'hp_zones_sub'        => 'Active zones across Gurgaon NCR and Bihar — expanding every month.',
    'hp_zones_list'       => 'Cyber City|green|Gurgaon|Gurgaon|Sector 14-17|green|Gurgaon|MG Road|green|Gurgaon|Sohna Road|green|Gurgaon|Golf Course Rd|green|Gurgaon|Udyog Vihar|green|Gurgaon|South City|green|Gurgaon|Manesar|green|Gurgaon|Aurangabad|yellow|Bihar|Patna|yellow|Bihar',
    // Corporate
    'hp_corp_tag'         => 'For Businesses',
    'hp_corp_title'       => 'Corporate & AMC Services',
    'hp_corp_sub'         => 'FixGrid provides reliable, scalable professional services for businesses — including IT support, AMC contracts and on-demand technical assistance with guaranteed SLAs.',
    'hp_corp_points'      => 'Dedicated account manager for your business|Monthly AMC with priority 2-hour response|Verified engineers with enterprise experience|GST invoices and digital service reports',
    'hp_corp_card_tag'    => 'Enterprise Ready',
    'hp_corp_card_title'  => "What's Included in AMC",
    'hp_corp_card_sub'    => 'Annual Maintenance Contract covers all your IT and facility service needs under one agreement with fixed monthly pricing.',
    'hp_corp_items'       => 'IT Support|CCTV Maintenance|Network Upkeep|Server Monitoring|AC Servicing|Electrical Checks',
    // Testimonials
    'hp_testi_tag'        => 'Testimonials',
    'hp_testi_title'      => 'What Our Clients Say',
    'hp_testi_sub'        => 'Thousands of happy customers across Gurgaon and Bihar trust FixGrid.',
    'hp_testi_list'       => 'Rahul Kumar|RK|#1E88E5|Cyber City, Gurgaon|Booked an AC service at 10 AM and the technician arrived by 11 AM. Excellent work, very professional. Will definitely use FixGrid again.|Priya Sharma|PS|#FF6F00|Sohna Road, Gurgaon|Got CCTV installed at my shop. The engineer was knowledgeable and transparent about pricing. No hidden charges. Great service!|Amit Mishra|AM|#0B3C5D|Aurangabad, Bihar|FixGrid handles all our office IT support. Their AMC package is very cost-effective and their response time is excellent.',
    // App section
    'hp_app_tag'          => 'Mobile App',
    'hp_app_title1'       => 'Book on the',
    'hp_app_title2'       => 'FixGrid App',
    'hp_app_sub'          => 'Track your technician live, chat in-app, pay securely and access full service history — all in one place.',
    'hp_app_feats'        => 'Real-time technician tracking on map|In-app chat with your assigned technician|Digital invoice and full service history|UPI, card and cash payment options',
    'hp_app_store_url'    => '#',
    'hp_app_play_url'     => '#',
    // CTA
    'hp_cta_title1'       => 'Ready to',
    'hp_cta_title2'       => 'Skip the Hassle?',
    'hp_cta_sub'          => 'Book a verified professional in under 2 minutes. Fast response. Service guarantee included.',
    'hp_cta_btn'          => '⚡ Book a Technician Now',
    'hp_cta_wa_btn'       => '💬 WhatsApp Us',
    // Contact
    'hp_wa_number'        => '919810519169',
    // Footer
    'hp_footer_about'     => 'Your Local Professional Service Network. Verified professionals for every home and business need across Bihar and Gurgaon NCR.',
    'hp_footer_tagline'   => '⚡ Fast. Reliable. On-Demand.',
    'hp_footer_copy'      => '© 2026 FixGrid. All rights reserved. Managed by PH Digital Technology Services Pvt Ltd · CIN: U72502BR2020PTC047510',
    // Navbar
    'hp_nav_links'        => 'Services|#services|How it Works|#how|Areas|#zones|Corporate|#corporate',
    'hp_nav_cta'          => '⚡ Book Now',
    // SEO
    'hp_seo_title'        => 'FixGrid — Your Local Professional Service Network',
    'hp_seo_desc'         => 'Book verified professionals for AC repair, electrician, IT support, CCTV, plumbing and more. 60-minute response across Gurgaon and Bihar.',
];

$data = array_merge($defaults, $rows);
jsonResponse(true, $data);
