<?php

require_once __DIR__ . '/config.php';

function show_result(int $status, string $title, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');

    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $safeTitle . ' — Kilele Fitness Studio</title>';
    echo '<link rel="stylesheet" href="style.css"></head><body><main>';
    echo '<h2>' . $safeTitle . '</h2><p>' . $safeMessage . '</p>';
    echo '<p><a class="btn" href="signup.html">Back to sign up</a></p>';
    echo '</main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    show_result(405, 'Method not allowed', 'Please submit the signup form to continue.');
}

$requiredFields = [
    'fullname', 'email', 'phone', 'age', 'gender', 'service',
    'membership', 'trainer', 'day', 'time',
];
$input = [];

foreach ($requiredFields as $field) {
    $input[$field] = trim((string) ($_POST[$field] ?? ''));
    if ($input[$field] === '') {
        show_result(422, 'Check your details', 'Please complete all required fields and try again.');
    }
}

$age = filter_var($input['age'], FILTER_VALIDATE_INT);
$allowedValues = [
    'gender' => ['female', 'male', 'other', 'prefer-not'],
    'service' => ['personal-training', 'group-classes', 'nutrition-guidance', 'all'],
    'membership' => ['basic', 'premium', 'elite'],
    'trainer' => ['john', 'jane', 'mike', 'any'],
    'day' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    'time' => ['morning', 'afternoon', 'evening'],
];

if (
    strlen($input['fullname']) > 120 ||
    !filter_var($input['email'], FILTER_VALIDATE_EMAIL) ||
    strlen($input['email']) > 254 ||
    strlen($input['phone']) > 40 ||
    $age === false || $age < 14 || $age > 100
) {
    show_result(422, 'Check your details', 'Please check your name, email, phone number, and age.');
}

foreach ($allowedValues as $field => $values) {
    if (!in_array($input[$field], $values, true)) {
        show_result(422, 'Check your details', 'One of your selected options is not valid.');
    }
}

$notes = trim((string) ($_POST['notes'] ?? ''));
if (strlen($notes) > 2000) {
    show_result(422, 'Check your details', 'Please keep additional notes under 2,000 characters.');
}

$supabaseUrl = rtrim(app_config('SUPABASE_URL'), '/');
$supabaseKey = app_config('SUPABASE_ANON_KEY');
$urlParts = parse_url($supabaseUrl);

if (
    !$urlParts ||
    ($urlParts['scheme'] ?? '') !== 'https' ||
    empty($urlParts['host']) ||
    $supabaseKey === ''
) {
    show_result(503, 'Signup is temporarily unavailable', 'The signup service is not configured yet. Please try again later.');
}

if (!function_exists('curl_init')) {
    show_result(503, 'Signup is temporarily unavailable', 'The server is missing the PHP cURL extension.');
}

$payload = [
    'fullname' => $input['fullname'],
    'email' => $input['email'],
    'phone' => $input['phone'],
    'age' => $age,
    'gender' => $input['gender'],
    'service' => $input['service'],
    'membership' => $input['membership'],
    'trainer' => $input['trainer'],
    'day' => $input['day'],
    'time' => $input['time'],
    'notes' => $notes === '' ? null : $notes,
];

$curl = curl_init($supabaseUrl . '/rest/v1/signups');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=minimal',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($curl);
$httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
    error_log('Supabase signup insert failed. HTTP status: ' . $httpStatus . '; ' . $curlError);
    show_result(502, 'Signup could not be submitted', 'We could not save your signup right now. Please try again later.');
}

show_result(201, 'Thanks for signing up', 'Your details have been received. Our team will be in touch soon.');