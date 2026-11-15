<?php
// Sends a booking request to Nina by email. Needs a real mailbox on the domain
// (create nina@soundhands.co.uk or noreply@soundhands.co.uk in cPanel) so the mail is trusted.
const TO_EMAIL   = 'nina@soundhands.co.uk';
const FROM_EMAIL = 'noreply@soundhands.co.uk';
const SITE_NAME  = 'Sound Hands Therapies';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html#booking');
    exit;
}

// Spam trap: real visitors never fill this hidden field
if (!empty($_POST['website'])) {
    header('Location: thanks.html');
    exit;
}

function clean(string $key, int $max): string {
    $v = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    $v = str_replace(["\r", "\n"], ' ', $v); // stops header injection
    return mb_substr($v, 0, $max);
}

$name     = clean('name', 80);
$phone    = clean('phone', 30);
$email    = clean('email', 120);
$therapy  = clean('therapy', 60);
$location = clean('location', 60);
$date     = clean('date', 20);
$time     = clean('time', 20);
$message  = isset($_POST['message']) ? mb_substr(trim((string) $_POST['message']), 0, 1000) : '';

if ($name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $therapy === '' || empty($_POST['consent'])) {
    http_response_code(400);
    echo 'Sorry, some details were missing. Please go back and check the form.';
    exit;
}

$none = '(not given)';
$body = "New request from the website\n\n"
      . "Name:      $name\n"
      . "Phone:     $phone\n"
      . "Email:     $email\n"
      . "About:     $therapy\n"
      . "Where:     " . ($location !== '' ? $location : $none) . "\n"
      . "Date:      " . ($date !== '' ? $date : $none) . "\n"
      . "Time:      " . ($time !== '' ? $time : $none) . "\n\n"
      . "Message:\n" . ($message !== '' ? $message : '(none)') . "\n";

$headers = [
    'From'         => SITE_NAME . ' <' . FROM_EMAIL . '>',
    'Reply-To'     => $name . ' <' . $email . '>',
    'Content-Type' => 'text/plain; charset=UTF-8',
];

$ok = mail(TO_EMAIL, 'Booking request: ' . $therapy . ' (' . $name . ')', $body, $headers);

if ($ok) {
    header('Location: thanks.html');
} else {
    http_response_code(500);
    echo 'Sorry, your request could not be sent. Please text or WhatsApp 07729 393539 instead.';
}
