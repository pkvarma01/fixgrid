<?php
// routes/customer/create_job.php
// FIX: Was completely empty. Now delegates to create_job_v2.php (broadcast model)
require_once dirname(__DIR__, 2) . '/config.php';
require __DIR__ . '/create_job_v2.php';
