<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment
 */

// Ensure we're in test mode
define('TESTING', true);

// Autoload Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Prevent actual session start in tests
if (!function_exists('session_start_mock')) {
    function session_start_mock() {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        return true;
    }
}