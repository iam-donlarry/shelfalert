<?php
/**
 * Email Configuration
 * 
 * Configure your SMTP settings here for sending payslip emails.
 * 
 * COMMON SETTINGS:
 * 
 * Gmail:
 *   Host: smtp.gmail.com
 *   Port: 587
 *   Secure: tls
 *   Note: Enable "App Passwords" or "Less Secure Apps"
 * 
 * cPanel Mail:
 *   Host: mail.yourdomain.com (or localhost)
 *   Port: 587 (TLS) or 465 (SSL)
 *   Secure: tls or ssl
 * 
 * Microsoft 365:
 *   Host: smtp.office365.com
 *   Port: 587
 *   Secure: tls
 */

return [
    // SMTP Server Settings
    'smtp_host'     => 'mail.bayeapp.com',              // e.g., 'smtp.gmail.com' or 'mail.yourdomain.com'
    'smtp_port'     => 465,                              // 587 for TLS, 465 for SSL
    'smtp_secure'   => 'ssl',                            // 'tls' or 'ssl'
    
    // Authentication
    'smtp_username' => 'noreply@bayeapp.com',            // Your email address
    'smtp_password' => 'K@dir!2000',                     // Your email password
    
    // Sender Information (used if company email not set)
    'from_email'    => 'noreply@bayeapp.com',            // Default sender email
    'from_name'     => 'BayePay',                      // Default sender name
    
    // Debug mode (set to true to see SMTP errors)
    'debug'         => false,
];
