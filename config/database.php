<?php
/**
 * Database Configuration
 */

// Load .env values
$env = file_exists(__DIR__.'/../.env') ? parse_ini_file(__DIR__.'/../.env') : [];
// Database credentials (env overrides defaults)
$host = $env['DB_HOST'];
$db = $env['DB_NAME'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
