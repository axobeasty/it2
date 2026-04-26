<?php

namespace App\Http\Email;

use App\Models\Settings;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    public function send(string $title, string $body, string $address, string $name): void
    {
        $settings = Settings::query()->find(1);
        if (! $settings || (int) $settings->email_enabled !== 1) {
            return;
        }

        $host = trim((string) ($settings->smtp_host ?? ''));
        if ($host === '') {
            Log::warning('Почта: не задан smtp_host в настройках, письмо не отправлено.');

            return;
        }

        $mail = new PHPMailer(false);
        try {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->SMTPDebug = config('app.debug') ? 2 : 0;
            $mail->isSMTP();
            $mail->Host = $host;

            $username = trim((string) ($settings->smtp_username ?? ''));
            $mail->SMTPAuth = $username !== '';
            $mail->Username = $username;

            $password = '';
            if (! empty($settings->smtp_password)) {
                try {
                    $password = Crypt::decryptString($settings->smtp_password);
                } catch (\Throwable) {
                    Log::error('Почта: не удалось расшифровать smtp_password (проверьте APP_KEY).');

                    return;
                }
            }
            $mail->Password = $password;

            $encryption = strtolower(trim((string) ($settings->smtp_encryption ?? 'tls')));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = true;
            } elseif ($encryption === 'none' || $encryption === '') {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = '';
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            }

            $port = (int) ($settings->smtp_port ?? 0);
            if ($port <= 0) {
                $port = $encryption === 'ssl' ? 465 : ($encryption === 'none' ? 25 : 587);
            }
            $mail->Port = $port;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ],
            ];

            $fromAddr = trim((string) ($settings->mail_from_address ?? ''));
            $fromName = trim((string) ($settings->mail_from_name ?? ''));
            if ($fromAddr === '') {
                $fromAddr = (string) config('mail.from.address', 'hello@example.com');
            }
            if ($fromName === '') {
                $fromName = $name;
            }

            $mail->setFrom($fromAddr, $fromName);
            $mail->addAddress($address);

            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body = '<html>'.$body.'</html>';

            $mail->send();
        } catch (Exception $e) {
            Log::error('Почта: ошибка отправки', [
                'mailer' => $mail->ErrorInfo,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
