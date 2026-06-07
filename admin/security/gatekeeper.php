<?php

declare(strict_types=1);

require_once __DIR__ . '/../../shared/includes/auth.php';

// Every page in the admin silo requires staff to be logged in.
requireStaffLogin();

// Specific role checks should still be performed on individual pages 
// if they require more than just basic staff access.
