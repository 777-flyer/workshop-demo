<?php
/**
 * api/availability.php
 *
 * Returns mechanic slot availability for a given month as JSON.
 *
 * GET params:
 *   year  int  e.g. 2025
 *   month int  1-12
 *
 * Response: { "YYYY-MM-DD": [ {mechanic_id, name, speciality, booked, free_slots}, ... ], ... }
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only JSON responses from this endpoint
header('Content-Type: application/json; charset=utf-8');

// Basic input validation
$year  = (int) ($_GET['year']  ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));

if ($year < 2020 || $year > 2099 || $month < 1 || $month > 12) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid year or month.']);
    exit;
}

try {
    $data = get_monthly_availability($year, $month);
    echo json_encode($data);
} catch (Exception $e) {
    error_log('availability.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error.']);
}
