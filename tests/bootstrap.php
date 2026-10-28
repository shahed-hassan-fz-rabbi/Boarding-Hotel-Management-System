<?php
/**
 * tests/bootstrap.php
 * Shared configuration for the CLI test suites in the Boarding Hotel Management System.
 *
 * Purpose
 *   - Provide a single place for target URL + database credentials.
 *   - Guard against accidental execution over HTTP (these files live inside the
 *     project folder which may be served by Apache/XAMPP, but tests must only
 *     ever run from the command line).
 *
 * Configuration via environment variables (all optional — XAMPP defaults shown):
 *   BHM_BASE_URL   default http://localhost/Boarding-Hotel-Management-System
 *   BHM_DB_HOST    default 127.0.0.1
 *   BHM_DB_USER    default root
 *   BHM_DB_PASS    default (empty)
 *   BHM_DB_NAME    default boarding_school
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not Found — tests run only from the command line. See tests/README.md.\n");
}

$TEST_BASE_URL = getenv('BHM_BASE_URL') ?: 'http://localhost/Boarding-Hotel-Management-System';
$TEST_DOCROOT  = dirname(__DIR__); // project root (this file sits in <project>/tests)

$TEST_DB = [
    'host' => getenv('BHM_DB_HOST') ?: '127.0.0.1',
    'user' => getenv('BHM_DB_USER') ?: 'root',
    'pass' => getenv('BHM_DB_PASS') ?: '',
    'name' => getenv('BHM_DB_NAME') ?: 'boarding_school',
];

$TEST_DB_HANDLE = function () use ($TEST_DB) {
    return new mysqli($TEST_DB['host'], $TEST_DB['user'], $TEST_DB['pass'], $TEST_DB['name']);
};
