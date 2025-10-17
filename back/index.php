<?php
/**
 * Backend API Endpoint
 * 
 * This endpoint is protected by Azure Active Directory authentication.
 * Ensure proper authentication is configured in Azure App Service.
 */

// Security headers
header('Content-Security-Policy: default-src \'none\'; script-src \'none\'; object-src \'none\'');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');

// Set content type
header('Content-Type: application/json; charset=UTF-8');

// Prevent caching of sensitive data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Get hostname safely
$hostname = gethostname();
if ($hostname === false) {
    $hostname = 'unknown';
}

// Return JSON response with proper encoding
$response = [
    'server' => $hostname,
    'timestamp' => date('c')
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>