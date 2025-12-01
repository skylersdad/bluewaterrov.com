<?php
// contact.php — email handler for BlueWater ROV contact form

// --- CONFIG --- //
$to      = "jmonetza@bluewaterrovservices.com";
$subject = "New BlueWater ROV Inspection Request";
// --------------- //

// Helper: detect if this is an AJAX / JSON-style request
$isJsonRequest = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

// Helper: send JSON or redirect depending on request type
function respond($data, int $statusCode = 200, bool $json = true)
{
    if ($json) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // For normal form submissions: redirect back with status in query string
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
    $query       = http_build_query([
        'contact_status'  => $data['status'] ?? 'error',
        'contact_message' => $data['message'] ?? ''
    ]);

    $separator = (parse_url($redirectUrl, PHP_URL_QUERY) === null) ? '?' : '&';
    header("Location: {$redirectUrl}{$separator}{$query}");
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(
        ['status' => 'error', 'message' => 'Method not allowed.'],
        405,
        $isJsonRequest
    );
}

// Collect and trim inputs
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$service  = trim($_POST['service'] ?? '');
$location = trim($_POST['location'] ?? '');
$message  = trim($_POST['message'] ?? '');

// Basic validation
$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
}
if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if ($service === '') {
    $errors[] = 'Service type is required.';
}
if ($location === '') {
    $errors[] = 'Lake / address is required.';
}
if ($message === '') {
    $errors[] = 'Please provide a brief description of what you would like inspected.';
}

if (!empty($errors)) {
    respond(
        [
            'status'  => 'error',
            'message' => implode(' ', $errors)
        ],
        400,
        $isJsonRequest
    );
}

// Protect against header injection in text fields
$emailSafe    = str_replace(["\r", "\n"], '', $email);
$nameSafe     = str_replace(["\r", "\n"], '', $name);
$serviceSafe  = str_replace(["\r", "\n"], '', $service);

// Build email body
$body  = "New inspection request from BlueWater ROV website:\n\n";
$body .= "Name: {$nameSafe}\n";
$body .= "Email: {$emailSafe}\n";
$body .= "Phone: {$phone}\n";
$body .= "Service Type: {$serviceSafe}\n";
$body .= "Location: {$location}\n\n";
$body .= "Message:\n{$message}\n";

// Email headers
$headers  = "From: BlueWater ROV Website <no-reply@bluewaterrovservices.com>\r\n";
$headers .= "Reply-To: {$emailSafe}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Attempt to send
if (mail($to, $subject, $body, $headers)) {
    respond(
        [
            'status'  => 'ok',
            'message' => 'Thank you. Your request has been sent successfully.'
        ],
        200,
        $isJsonRequest
    );
}

respond(
    [
        'status'  => 'error',
        'message' => 'We could not send your message. Please try again later or contact us by phone or email.'
    ],
    500,
    $isJsonRequest
);
