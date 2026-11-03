<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Load .env for local development.
 * Render uses Environment Variables.
 */
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

/**
 * Read environment variable.
 */
function gmailEnv(string $key): string
{
    $value = $_ENV[$key] ?? getenv($key);

    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException(
            "Missing environment variable: {$key}"
        );
    }

    return trim($value);
}

/**
 * Get OAuth 2.0 access token from refresh token.
 */
function getGmailAccessToken(): string
{
    $clientId = gmailEnv('GMAIL_CLIENT_ID');
    $clientSecret = gmailEnv('GMAIL_CLIENT_SECRET');
    $refreshToken = gmailEnv('GMAIL_REFRESH_TOKEN');

    if (!function_exists('curl_init')) {
        throw new RuntimeException(
            'PHP cURL extension is not installed.'
        );
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');

    $body = http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    try {
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException(
                'OAuth connection error: ' . curl_error($ch)
            );
        }

        $data = json_decode($response, true);

        if (
            $status !== 200 ||
            !is_array($data) ||
            empty($data['access_token'])
        ) {
            $error = is_array($data)
                ? ($data['error_description']
                    ?? $data['error']
                    ?? 'Unknown OAuth error')
                : 'Invalid OAuth response';

            throw new RuntimeException(
                "OAuth HTTP {$status}: " .
                (is_string($error) ? $error : 'Unknown error')
            );
        }

        return $data['access_token'];
    } finally {
        curl_close($ch);
    }
}

/**
 * Encode MIME message for Gmail API.
 */
function gmailBase64Url(string $message): string
{
    return rtrim(
        strtr(base64_encode($message), '+/', '-_'),
        '='
    );
}

/**
 * Send a Gmail message through HTTPS API.
 */
function sendGmailEmail(
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $html,
    string $plainText
): void {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException(
            'Invalid recipient email address.'
        );
    }

    $senderEmail = gmailEnv('GMAIL_SENDER_EMAIL');
    $accessToken = getGmailAccessToken();

    // PHPMailer only constructs MIME.
    // No SMTP connection is used.
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($senderEmail, 'SeedGrowthNote');
    $mail->addAddress($recipientEmail, $recipientName);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = $plainText;

    if (!$mail->preSend()) {
        throw new RuntimeException(
            'Could not generate email MIME message.'
        );
    }

    $rawMessage = gmailBase64Url(
        $mail->getSentMIMEMessage()
    );

    $ch = curl_init(
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
    );

    $payload = json_encode(
        ['raw' => $rawMessage],
        JSON_THROW_ON_ERROR
    );

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    try {
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException(
                'Gmail API connection error: ' .
                curl_error($ch)
            );
        }

        $data = json_decode($response, true);

        if (
            $status < 200 ||
            $status >= 300 ||
            !is_array($data) ||
            empty($data['id'])
        ) {
            $error = is_array($data)
                ? ($data['error']['message']
                    ?? 'Gmail API did not return a message ID')
                : 'Invalid Gmail API response';

            throw new RuntimeException(
                "Gmail API HTTP {$status}: " .
                (is_string($error) ? $error : 'Unknown error')
            );
        }
    } finally {
        curl_close($ch);
    }
}

/**
 * Send verification email.
 */
function sendVerificationEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $verifyUrl = rtrim(gmailEnv('APP_URL'), '/')
        . '/api/auth/verify_email.php?token='
        . rawurlencode($token);

    $safeName = htmlspecialchars(
        $recipientName,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeUrl = htmlspecialchars(
        $verifyUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family:Arial,sans-serif;
                 background:#f4f7f4;padding:30px;">
        <div style="max-width:550px;margin:auto;
                    background:white;padding:30px;
                    border-radius:12px;">
            <h2 style="color:#2e7d32;">
                Welcome to SeedGrowthNote!
            </h2>

            <p>Hello {$safeName},</p>

            <p>
                Thank you for registering.
                Please verify your email address.
            </p>

            <p style="margin:30px 0;">
                <a href="{$safeUrl}"
                   style="background:#2e7d32;color:white;
                          padding:12px 24px;
                          border-radius:8px;
                          text-decoration:none;">
                    Verify Email
                </a>
            </p>

            <p>This link expires in 24 hours.</p>

            <p>
                If you did not create this account,
                please ignore this message.
            </p>

            <p>SeedGrowthNote Team</p>
        </div>
    </body>
    </html>
    HTML;

    $plainText =
        "Hello {$recipientName},\n\n"
        . "Welcome to SeedGrowthNote!\n\n"
        . "Verify your email:\n{$verifyUrl}\n\n"
        . "This link expires in 24 hours.";

    sendGmailEmail(
        $recipientEmail,
        $recipientName,
        'Verify your SeedGrowthNote account',
        $html,
        $plainText
    );
}

/**
 * Send password reset email.
 */
function sendPasswordResetEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $resetUrl = rtrim(gmailEnv('APP_URL'), '/')
        . '/index.php?page=reset_password&token='
        . rawurlencode($token);

    $safeName = htmlspecialchars(
        $recipientName,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeUrl = htmlspecialchars(
        $resetUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family:Arial,sans-serif;
                 background:#f4f7f4;padding:30px;">
        <div style="max-width:550px;margin:auto;
                    background:white;padding:30px;
                    border-radius:12px;">
            <h2 style="color:#2e7d32;">
                Reset Your Password
            </h2>

            <p>Hello {$safeName},</p>

            <p>
                We received a request to reset
                your SeedGrowthNote password.
            </p>

            <p style="margin:30px 0;">
                <a href="{$safeUrl}"
                   style="background:#2e7d32;color:white;
                          padding:12px 24px;
                          border-radius:8px;
                          text-decoration:none;">
                    Reset Password
                </a>
            </p>

            <p>This link expires in 30 minutes.</p>

            <p>
                If you did not request a password reset,
                please ignore this message.
            </p>

            <p>SeedGrowthNote Team</p>
        </div>
    </body>
    </html>
    HTML;

    $plainText =
        "Hello {$recipientName},\n\n"
        . "Reset your SeedGrowthNote password:\n"
        . "{$resetUrl}\n\n"
        . "This link expires in 30 minutes.";

    sendGmailEmail(
        $recipientEmail,
        $recipientName,
        'Reset your SeedGrowthNote password',
        $html,
        $plainText
    );
}

/**
 * Email notification after a note is shared or its permission changes.
 * This function must be called AFTER the database transaction commits.
 */
function sendNoteShareEmail(
    string $recipientEmail,
    string $recipientName,
    string $senderName,
    string $noteTitle,
    string $permission,
    bool $permissionChanged = false
): void {
    $mail = createMailer();
    $mail->addAddress($recipientEmail, $recipientName);

    $accessLabel = $permission === 'edit' ? 'Can edit' : 'Can view';
    $actionLabel = $permissionChanged ? 'updated your permission for' : 'shared';
    $subject = $permissionChanged
        ? 'SeedGrowthNote: your note access has changed'
        : 'SeedGrowthNote: a note was shared with you';

    // Open the app and navigate to Shared with me. Do not send note content.
    $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    if ($appUrl === '') {
        throw new RuntimeException('APP_URL is not configured');
    }
    $url = $appUrl . '/index.php';

    $esc = static fn(string $text): string => htmlspecialchars(
        $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
    );
    $safeName = $esc($recipientName);
    $safeSender = $esc($senderName);
    $safeTitle = $esc($noteTitle);
    $safeLabel = $esc($accessLabel);
    $safeUrl = $esc($url);

    $mail->Subject = $subject;
    $mail->Body = <<<HTML
<!doctype html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#253229;line-height:1.6">
  <h2>SeedGrowthNote</h2>
  <p>Hello {$safeName},</p>
  <p><strong>{$safeSender}</strong> {$actionLabel} a note with you:</p>
  <p><strong>{$safeTitle}</strong></p>
  <p>Permission: <strong>{$safeLabel}</strong></p>
  <p><a href="{$safeUrl}">Open SeedGrowthNote</a> and go to <strong>Shared with me</strong>.</p>
  <p>If the note is password protected, ask its owner for the note password separately.</p>
</body>
</html>
HTML;
    $mail->AltBody = "Hello {$recipientName},\n\n"
        . "{$senderName} {$actionLabel} a note with you: {$noteTitle}\n"
        . "Permission: {$accessLabel}\n\n"
        . "Open SeedGrowthNote: {$url}\n"
        . "Then choose Shared with me.\n"
        . "If the note is password protected, request its password separately.";

    $mail->send();
}
