<?php
declare(strict_types=1);

/**
 * Gemini REST integration for SeedGrowthNote.
 * IMPORTANT: Only use an API key from a Google AI Studio project in the FREE tier,
 * with no billing account attached. An API call cannot itself enforce free billing.
 * We deliberately FIX the model, disable tools, and never retry/fallback.
 */
final class GeminiApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 502,
        public readonly string $errorCode = 'AI_PROVIDER_ERROR'
    ) {
        parent::__construct($message);
    }
}

function askAi(string $system, string $prompt, bool $json = false): string
{
    $key = trim((string) ($_ENV['GEMINI_API_KEY'] ?? ''));
    if ($key === '') {
        throw new GeminiApiException('Chưa cấu hình GEMINI_API_KEY trong .env.', 503, 'AI_NOT_CONFIGURED');
    }
    if (!function_exists('curl_init')) {
        throw new GeminiApiException('PHP chưa bật extension curl.', 503, 'PHP_CURL_MISSING');
    }

    // A fixed Free-Tier-eligible text model; no environment override or fallback.
    // This is NOT a billing guarantee: project billing tier controls actual charges.
    $model = 'gemini-2.5-flash-lite';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . $model . ':generateContent';

    $payload = [
        'systemInstruction' => [
            'parts' => [['text' => $system]],
        ],
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 1400,
        ],
    ];
    if ($json) {
        $payload['generationConfig']['responseMimeType'] = 'application/json';
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $key,
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 55,
    ]);
    $response = curl_exec($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        error_log('Gemini connection error: ' . $curlError);
        throw new GeminiApiException(
            'Không kết nối được dịch vụ AI. Vui lòng thử lại sau.',
            502,
            'AI_NETWORK_ERROR'
        );
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new GeminiApiException('Phản hồi AI không hợp lệ.', 502, 'AI_INVALID_RESPONSE');
    }

    // Stop immediately, with no retries, no paid-model fallback.
    if ($httpStatus === 429) {
        throw new GeminiApiException(
            'Đã hết hạn mức Gemini miễn phí hoặc vượt giới hạn lượt gọi/token. ' .
            'Không gửi thêm yêu cầu. Vui lòng thử lại khi hạn mức được làm mới.',
            429,
            'AI_QUOTA_EXCEEDED'
        );
    }
    if ($httpStatus === 401 || $httpStatus === 403) {
        throw new GeminiApiException(
            'API key không hợp lệ hoặc dự án không được phép sử dụng model. ' .
            'Hãy kiểm tra API key và trạng thái Free Tier trong Google AI Studio.',
            503,
            'AI_ACCESS_DENIED'
        );
    }
    if ($httpStatus === 400 || $httpStatus === 404) {
        throw new GeminiApiException(
            'Yêu cầu AI hoặc model không được hỗ trợ trên dự án hiện tại. ' .
            'Không tự chuyển sang model trả phí.',
            502,
            'AI_MODEL_UNAVAILABLE'
        );
    }
    if ($httpStatus < 200 || $httpStatus >= 300) {
        error_log('Gemini HTTP ' . $httpStatus . ': ' . substr($response, 0, 500));
        throw new GeminiApiException('Dịch vụ AI tạm thời không khả dụng.', 502);
    }

    $candidates = $data['candidates'] ?? [];
    $parts = $candidates[0]['content']['parts'] ?? [];
    $result = '';
    foreach ($parts as $part) {
        if (isset($part['text']) && is_string($part['text'])) {
            $result .= $part['text'];
        }
    }
    if (trim($result) === '') {
        throw new GeminiApiException(
            'AI không trả về nội dung (có thể do bộ lọc an toàn hoặc giới hạn đầu ra).',
            502,
            'AI_EMPTY_RESPONSE'
        );
    }
    return trim($result);
}
