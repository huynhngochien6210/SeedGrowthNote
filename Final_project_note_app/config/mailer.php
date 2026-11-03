<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Tạo và cấu hình PHPMailer dùng chung.
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];

    $mail->Port = (int) $_ENV['SMTP_PORT'];

    $mail->SMTPSecure = $mail->Port === 465
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        $_ENV['SMTP_FROM_EMAIL'],
        $_ENV['SMTP_FROM_NAME']
    );

    $mail->isHTML(true);

    return $mail;
}

/**
 * Gửi email xác minh tài khoản.
 *
 * Token được tạo bởi API đăng ký hoặc gửi lại xác minh.
 * Link xác minh có thời hạn 24 giờ.
 */
function sendVerificationEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $mail = createMailer();

    $mail->addAddress(
        $recipientEmail,
        $recipientName
    );

    $verifyUrl = rtrim($_ENV['APP_URL'], '/')
        . '/api/auth/verify_email.php?token='
        . rawurlencode($token);

    $safeUrl = htmlspecialchars(
        $verifyUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeName = htmlspecialchars(
        $recipientName,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $mail->Subject = 'Verify your SeedGrowthNote account';

    $mail->Body = "
        <h2>Welcome to SeedGrowthNote!</h2>

        <p>Hello {$safeName},</p>

        <p>Thank you for creating an account.</p>

        <p>Please verify your email address by clicking
        the button below.</p>

        <p>
            <a href=\"{$safeUrl}\">
                Verify Email
            </a>
        </p>

        <p>This link expires in 24 hours.</p>

        <p>If you did not create this account,
        please ignore this email.</p>
    ";

    $mail->AltBody =
        "Welcome to SeedGrowthNote!\n\n"
        . "Verify your account using this link:\n"
        . $verifyUrl
        . "\n\nThis link expires in 24 hours.";

    $mail->send();
}

/**
 * Gửi email đặt lại mật khẩu.
 *
 * Token được tạo bởi forgot_password.php.
 * Link đặt lại mật khẩu có thời hạn 30 phút.
 */
function sendPasswordResetEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $mail = createMailer();

    $mail->addAddress(
        $recipientEmail,
        $recipientName
    );

    // Đi qua hệ thống routing của SeedGrowthNote.
    $resetUrl = rtrim($_ENV['APP_URL'], '/')
        . '/index.php?page=reset_password&token='
        . rawurlencode($token);

    $safeUrl = htmlspecialchars(
        $resetUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeName = htmlspecialchars(
        $recipientName,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $mail->Subject = 'Reset your SeedGrowthNote password';

    $mail->Body = "
        <h2>Reset your password</h2>

        <p>Hello {$safeName},</p>

        <p>We received a request to reset your
        SeedGrowthNote password.</p>

        <p>
            Click the link below to create a new password:
        </p>

        <p>
            <a href=\"{$safeUrl}\">
                Reset Password
            </a>
        </p>

        <p>
            This password reset link expires in
            <strong>30 minutes</strong>.
        </p>

        <p>
            If you did not request a password reset,
            you can safely ignore this email.
        </p>
    ";

    $mail->AltBody =
        "Hello {$recipientName},\n\n"
        . "We received a request to reset your password.\n\n"
        . "Reset your password using this link:\n"
        . $resetUrl
        . "\n\nThis link expires in 30 minutes."
        . "\n\nIf you did not request this, ignore this email.";

    $mail->send();
}
