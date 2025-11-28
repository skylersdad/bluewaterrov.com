<?php
// contact.php — basic email handler for BlueWater ROV contact form

// --- CONFIG --- //
$to      = "jmonetza@bluewaterrovservices.com";
$subject = "New BlueWater ROV Inspection Request";
// --------------- //

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$name     = trim($_POST["name"] ?? "");
$email    = trim($_POST["email"] ?? "");
$phone    = trim($_POST["phone"] ?? "");
$service  = trim($_POST["service"] ?? "");
$location = trim($_POST["location"] ?? "");
$message  = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $service === "" || $location === "" || $message === "") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$body = "New inspection request from BlueWater ROV website:\n\n";
$body .= "Name: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Phone: {$phone}\n";
$body .= "Service Type: {$service}\n";
$body .= "Location: {$location}\n\n";
$body .= "Message:\n{$message}\n";

$headers = "From: BlueWater ROV Website <no-reply@bluewaterrovservices.com>\r\n";
$headers .= "Reply-To: {$email}\r\n";

if (mail($to, $subject, $body, $headers)) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not send email"]);
}
