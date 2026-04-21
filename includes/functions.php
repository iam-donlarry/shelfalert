<?php
// Ensure this is only defined once
function includeFile($path) {
    include BASE_PATH . '/' . $path;
}

function base_url($path = '') {
    // ShelfAlert base URL
    return '/' . ltrim($path, '/');
}

/**
 * Get URL for an asset with cache busting
 */
function asset_url($path) {
    $ver = '1.1'; // Increment this to force CSS refresh
    return base_url($path) . "?v=" . $ver;
}
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatCurrency($amount, $currency = 'NGN') {
    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€'
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

function formatDate($date, $format = 'F j, Y') {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

function logAction($action, $details = null) {
    // This would typically log to a file or database
    error_log("[" . date('Y-m-d H:i:s') . "] " . $action . ($details ? ": " . json_encode($details) : ""));
}

function sendEmail($to, $subject, $message, $headers = null) {
    // Basic email sending function
    $default_headers = "From: shelfalert@ace.com\r\n";
    $default_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $headers = $headers ?: $default_headers;
    
    return mail($to, $subject, $message, $headers);
}

