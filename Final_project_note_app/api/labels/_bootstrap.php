<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $status, bool $success, string $message, mixed $data = null): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id']) || !filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)) {
    reply(401, false, 'Bạn chưa đăng nhập.');
}
$userId = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../../config/database.php';

function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        reply(405, false, 'Phương thức không được hỗ trợ.');
    }
}

function requestJson(): array
{
    requireMethod('POST');
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type !== 'application/json') {
        reply(415, false, 'Content-Type phải là application/json.');
    }
    // Basic same-origin protection for browser JSON writes.
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $origin = parse_url($_SERVER['HTTP_ORIGIN']);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $originHost = ($origin['host'] ?? '') . (isset($origin['port']) ? ':' . $origin['port'] : '');
        if (!$origin || strcasecmp($originHost, $host) !== 0) {
            reply(403, false, 'Nguồn yêu cầu không hợp lệ.');
        }
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 50000) {
        reply(400, false, 'Dữ liệu quá lớn hoặc không đọc được.');
    }
    $payload = json_decode($raw, true);
    if (!is_array($payload) || array_is_list($payload)) {
        reply(400, false, 'JSON không hợp lệ.');
    }
    return $payload;
}

function positiveId(mixed $value, string $field): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        reply(422, false, "{$field} không hợp lệ.");
    }
    return (int) $id;
}
