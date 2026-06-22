<?php

const RECIPIENT = 'contact@crm.synapsedx.com';
const FROM      = 'noreply.website@synapsedx.com';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Honeypot — bots fill hidden fields, humans don't
$honeypot = trim($_POST['website'] ?? '');
if ($honeypot !== '') {
    // Silently pretend success
    echo json_encode(['ok' => true]);
    exit;
}

// Read and sanitize inputs
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$company = trim($_POST['company'] ?? '');
$message = trim($_POST['message'] ?? '');

// Header-injection protection: reject if name or email contain CR or LF
if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

// Required-field validation
if ($name === '' || $email === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields']);
    exit;
}

// Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address']);
    exit;
}

// Compose email
$subject = 'New contact form submission from ' . $name;

$body  = "Name:    " . $name    . "\n";
$body .= "Email:   " . $email   . "\n";
$body .= "Company: " . ($company !== '' ? $company : '—') . "\n";
$body .= "\n";
$body .= "Message:\n" . $message . "\n";

$headers  = "From: " . FROM . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail(RECIPIENT, $subject, $body, $headers);

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to send message, please try again']);
    exit;
}

echo json_encode(['ok' => true]);
