<?php

namespace App\Http\Email;

use App\Models\MailDeliveryFailure;
use App\Models\Settings;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    /**
     * @param  array<string, mixed>|null  $context  category (const MailDeliveryFailure::CATEGORY_*), mail_type, recipient_employee_id, recipient_name, triggered_by_employee_id, meta
     */
    public function send(string $title, string $body, string $address, string $name, ?array $context = null): void
    {
        $address = trim($address);
        $ctx = $this->normalizeContext($context);
        $settings = Settings::query()->find(1);

        if ($address === '') {
            if ($ctx !== null) {
                $this->persistFailure(
                    $ctx,
                    'recipient_missing',
                    'Не указан e-mail получателя.',
                    $title,
                    '',
                    null,
                    $name
                );
            }

            return;
        }

        if (! $settings || (int) $settings->email_enabled !== 1) {
            if ($ctx !== null) {
                $this->persistFailure(
                    $ctx,
                    'mail_disabled',
                    'Отправка почтовых уведомлений отключена в настройках.',
                    $title,
                    $address,
                    null,
                    $name
                );
            }

            return;
        }

        $host = trim((string) ($settings->smtp_host ?? ''));
        if ($host === '') {
            Log::warning('Почта: не задан smtp_host в настройках, письмо не отправлено.');
            if ($ctx !== null) {
                $this->persistFailure(
                    $ctx,
                    'smtp_host_missing',
                    'Не задан SMTP-сервер (host) в настройках почты.',
                    $title,
                    $address,
                    null,
                    $name
                );
            }

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
                    if ($ctx !== null) {
                        $this->persistFailure(
                            $ctx,
                            'smtp_password_decrypt',
                            'Не удалось расшифровать пароль SMTP (проверьте APP_KEY).',
                            $title,
                            $address,
                            null,
                            $name
                        );
                    }

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
            if ($ctx !== null) {
                $this->persistFailure(
                    $ctx,
                    'smtp_send_error',
                    $e->getMessage(),
                    $title,
                    $address,
                    $mail->ErrorInfo,
                    $name
                );
            }
        }
    }

    /**
     * @return array{category: string, mail_type: ?string, recipient_employee_id: ?int, recipient_name: ?string, triggered_by_employee_id: ?int, meta: ?array}|null
     */
    private function normalizeContext(?array $context): ?array
    {
        if ($context === null) {
            return null;
        }
        $category = trim((string) ($context['category'] ?? ''));
        if ($category === '') {
            return null;
        }

        $recipientId = $context['recipient_employee_id'] ?? null;
        $triggeredBy = $context['triggered_by_employee_id'] ?? null;
        $meta = $context['meta'] ?? null;

        return [
            'category' => $category,
            'mail_type' => isset($context['mail_type']) ? (string) $context['mail_type'] : null,
            'recipient_employee_id' => $recipientId !== null && $recipientId !== '' ? (int) $recipientId : null,
            'recipient_name' => isset($context['recipient_name']) ? trim((string) $context['recipient_name']) : null,
            'triggered_by_employee_id' => $triggeredBy !== null && $triggeredBy !== '' ? (int) $triggeredBy : null,
            'meta' => is_array($meta) ? $meta : null,
        ];
    }

    /**
     * @param  array{category: string, mail_type: ?string, recipient_employee_id: ?int, recipient_name: ?string, triggered_by_employee_id: ?int, meta: ?array}  $ctx
     */
    private function persistFailure(
        array $ctx,
        string $failureCode,
        string $errorMessage,
        string $subject,
        string $recipientEmail,
        ?string $phpmailerErrorInfo,
        string $fromDisplayName,
    ): void {
        try {
            MailDeliveryFailure::create([
                'category' => $ctx['category'],
                'failure_code' => $failureCode,
                'subject' => mb_substr($subject, 0, 255, 'UTF-8'),
                'recipient_email' => mb_substr($recipientEmail, 0, 255, 'UTF-8'),
                'recipient_employee_id' => $ctx['recipient_employee_id'],
                'recipient_display' => $this->formatRecipientDisplay($ctx['recipient_name'], $recipientEmail),
                'triggered_by_employee_id' => $ctx['triggered_by_employee_id'],
                'mail_type' => $ctx['mail_type'],
                'error_message' => $errorMessage,
                'phpmailer_error_info' => $phpmailerErrorInfo,
                'meta' => array_filter(
                    array_merge(
                        $ctx['meta'] ?? [],
                        ['from_display_name' => $fromDisplayName]
                    ),
                    static fn ($v) => $v !== null && $v !== ''
                ) ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Почта: не удалось записать журнал ошибок доставки', ['exception' => $e->getMessage()]);
        }
    }

    private function formatRecipientDisplay(?string $name, string $email): ?string
    {
        $name = trim((string) $name);
        if ($name !== '' && $email !== '') {
            return $name.' <'.$email.'>';
        }
        if ($name !== '') {
            return $name;
        }
        if ($email !== '') {
            return $email;
        }

        return null;
    }
}
