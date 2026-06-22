<?php

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

const RECIPIENT = 'contact@crm.synapsedx.com';

$config = require __DIR__ . '/mail_config.php';

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

// Compose body
$body  = "Name:    " . $name . "\n";
$body .= "Email:   " . $email . "\n";
$body .= "Company: " . ($company !== '' ? $company : '—') . "\n";
$body .= "\n";
$body .= "Message:\n" . $message . "\n";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = $config['port'];
    $mail->CharSet    = 'UTF-8';

    // From must be the authenticated mailbox for DKIM/DMARC alignment
    $mail->setFrom($config['from'], 'SynapseDX Website');
    $mail->addAddress(RECIPIENT);
    $mail->addReplyTo($email, $name);

    $mail->Subject = 'New contact form submission from ' . $name;
    $mail->Body    = $body;

    $mail->send();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('contact_form mail error: ' . $mail->ErrorInfo);
    echo json_encode(['ok' => false, 'error' => 'Failed to send message, please try again']);
}
