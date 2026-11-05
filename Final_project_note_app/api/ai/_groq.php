<?php
declare(strict_types=1);

function askAi(
    string $system,
    string $prompt,
    bool $json = false
): string {
    $apiKey = getenv('GROQ_API_KEY')
        ?: ($_ENV['GROQ_API_KEY'] ?? '');

    $model = getenv('GROQ_MODEL')
        ?: ($_ENV['GROQ_MODEL'] ?? 'llama-3.1-8b-instant');

    if ($apiKey === '') {
        throw new RuntimeException('Missing GROQ_API_KEY');
    }

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.2,
        'max_completion_tokens' => 1400
    ];

    if ($json) {
        $payload['response_format'] = [
            'type' => 'json_object'
        ];
    }

    $ch = curl_init(
        'https://api.groq.com/openai/v1/chat/completions'
    );

    if ($ch === false) {
        throw new RuntimeException('Cannot initialize cURL');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60
    ]);

    try {
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException(
                'Groq connection failed: ' . curl_error($ch)
            );
        }
    } finally {
        curl_close($ch);
    }

    $data = json_decode($response, true);

    if ($status === 429) {
        throw new RuntimeException('Groq rate limit exceeded', 429);
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException(
            'Groq API error (' . $status . '): ' .
            ($data['error']['message'] ?? 'Unknown error'),
            $status
        );
    }

    $answer = $data['choices'][0]['message']['content'] ?? null;

    if (!is_string($answer) || trim($answer) === '') {
        throw new RuntimeException('Groq returned an empty response');
    }

    return trim($answer);
}
