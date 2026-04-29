<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailDeliveryFailure extends Model
{
    public const CATEGORY_AUTH = 'auth';

    public const CATEGORY_EMPLOYEES = 'employees';

    public const CATEGORY_INVENTORY = 'inventory';

    public const CATEGORY_SYSTEM = 'system';

    protected $fillable = [
        'category',
        'failure_code',
        'subject',
        'recipient_email',
        'recipient_employee_id',
        'recipient_display',
        'triggered_by_employee_id',
        'mail_type',
        'error_message',
        'phpmailer_error_info',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function recipientEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recipient_employee_id');
    }

    public function triggeredByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'triggered_by_employee_id');
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_AUTH => 'Авторизация',
            self::CATEGORY_EMPLOYEES => 'Сотрудники',
            self::CATEGORY_INVENTORY => 'Инвентарь',
            self::CATEGORY_SYSTEM => 'Система',
            default => $category,
        };
    }

    public static function mailTypeLabel(?string $mailType): string
    {
        if ($mailType === null || $mailType === '') {
            return '—';
        }

        return match ($mailType) {
            'login_success' => 'Успешный вход',
            'employee_invite' => 'Приглашение (новый сотрудник)',
            'employee_email_change' => 'Смена e-mail',
            'employee_password_change' => 'Смена пароля',
            'inventory_assign' => 'Закрепление инвентаря',
            'inventory_unassign' => 'Открепление инвентаря',
            'inventory_unassign_all' => 'Снятие всего инвентаря',
            'inventory_reassign' => 'Повторное закрепление',
            'password_reset' => 'Восстановление пароля',
            'settings_test' => 'Тест SMTP из настроек',
            default => $mailType,
        };
    }

    public static function failureCodeLabel(string $code): string
    {
        return match ($code) {
            'recipient_missing' => 'Нет адреса получателя',
            'mail_disabled' => 'Почта отключена в настройках',
            'smtp_host_missing' => 'Не задан SMTP-сервер',
            'smtp_password_decrypt' => 'Ошибка расшифровки пароля SMTP',
            'smtp_send_error' => 'Ошибка SMTP / отправки',
            default => $code,
        };
    }
}
