<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function sendVerificationEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        $_ENV['SMTP_FROM_EMAIL'],
        $_ENV['SMTP_FROM_NAME']
    );

    $mail->addAddress($recipientEmail, $recipientName);

    $verifyUrl = rtrim($_ENV['APP_URL'], '/')
        . '/api/auth/verify_email.php?token='
        . urlencode($token);

    $safeUrl = htmlspecialchars(
        $verifyUrl,
        ENT_QUOTES,
        'UTF-8'
    );

    $mail->isHTML(true);
    $mail->Subject = 'Verify your SeedGrowthNote account';

    $mail->Body = "
        <h2>Welcome to SeedGrowthNote!</h2>
        <p>Please verify your email address.</p>
        <p><a href=\"{$safeUrl}\">Verify Email</a></p>
        <p>This link expires in 24 hours.</p>
    ";

    $mail->AltBody =
        "Verify your account using this link:\n" . $verifyUrl;

    $mail->send();
}

function sendPasswordResetEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): void {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        $_ENV['SMTP_FROM_EMAIL'],
        $_ENV['SMTP_FROM_NAME']
    );

    $mail->addAddress($recipientEmail, $recipientName);

    $resetUrl = rtrim($_ENV['APP_URL'], '/')
        . '/pages/reset_password.php?token='
        . urlencode($token);

    $safeUrl = htmlspecialchars(
        $resetUrl,
        ENT_QUOTES,
        'UTF-8'
    );

    $mail->isHTML(true);
    $mail->Subject = 'Reset your SeedGrowthNote password';

    $mail->Body = "
        <h2>Reset your password</h2>
        <p>We received a request to reset your password.</p>
        <p><a href=\"{$safeUrl}\">Reset Password</a></p>
        <p>This link expires in 30 minutes.</p>
        <p>If you did not request this, ignore this email.</p>
    ";

    $mail->AltBody =
        "Reset your password using this link:\n"
        . $resetUrl;

    $mail->send();
}