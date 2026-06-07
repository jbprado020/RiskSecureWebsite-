<?php

declare(strict_types=1);

require_once __DIR__ . '/../../shared/includes/auth.php';

// Every page in the customer silo requires a customer to be logged in.
requireCustomerLogin();
