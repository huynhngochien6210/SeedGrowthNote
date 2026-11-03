<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/database.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

if (empty($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Please log in again.'
    ]);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON.'
    ]);
}

$displayName = $data['display_name'] ?? '';
$dateOfBirth = $data['date_of_birth'] ?? null;
$gender = $data['gender'] ?? null;

if (
    !is_string($displayName) ||
    !is_string($dateOfBirth) && $dateOfBirth !== null ||
    !is_string($gender) && $gender !== null
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid profile data.'
    ]);
}

$displayName = trim($displayName);
$dateOfBirth = $dateOfBirth === '' ? null : $dateOfBirth;
$gender = $gender === '' ? null : $gender;

if (
    $displayName === '' ||
    mb_strlen($displayName, 'UTF-8') > 255
) {
    respond(400, [
        'success' => false,
        'message' => 'Fullname must be 1-255 characters.'
    ]);
}

if ($dateOfBirth !== null) {
    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $dateOfBirth
    );

    if (
        !$date ||
        $date->format('Y-m-d') !== $dateOfBirth ||
        $date > new DateTimeImmutable('today')
    ) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid date of birth.'
        ]);
    }
}

$allowedGenders = ['Male', 'Female', 'Other'];

if (
    $gender !== null &&
    !in_array($gender, $allowedGenders, true)
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid gender.'
    ]);
}

try {
    $stmt = $pdo->prepare(
        'UPDATE users
         SET display_name = :display_name,
             date_of_birth = :date_of_birth,
             gender = :gender,
             updated_at = NOW()
         WHERE id = :id'
    );

    $stmt->execute([
        'display_name' => $displayName,
        'date_of_birth' => $dateOfBirth,
        'gender' => $gender,
        'id' => $_SESSION['user_id']
    ]);

    $_SESSION['display_name'] = $displayName;

    respond(200, [
        'success' => true,
        'message' => 'Profile updated successfully.'
    ]);

} catch (Throwable $e) {
    error_log('Update profile error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to update profile.'
    ]);
}
