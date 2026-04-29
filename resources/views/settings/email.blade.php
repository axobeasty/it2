@extends('layout.settings', ['settingsSection' => 'email'])

@section('page_title', 'Почта — ' . $settings->title)

@section('settings_heading', 'Почтовые уведомления')
@section('settings_subheading', 'Отправка системных писем пользователям.')

@section('settings_content')
    <form action="/settings/save" method="post">
        @csrf
        <input type="hidden" name="page" value="email">

        <div class="mb-4">
            <label for="email_enabled" class="settings-field-label">Отправка уведомлений</label>
            <select class="form-select form-select-lg rounded-3 @error('email_enabled') is-invalid @enderror" name="email_enabled" id="email_enabled" aria-label="Отправка уведомлений">
                @php $emailOn = (string) old('email_enabled', ($settings->email_enabled ? '1' : '0')); @endphp
                <option value="1" {{ $emailOn === '1' ? 'selected' : '' }}>Включено</option>
                <option value="0" {{ $emailOn === '0' ? 'selected' : '' }}>Отключено</option>
            </select>
            @error('email_enabled')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="settings-section-title mb-3">SMTP</div>
        <p class="small text-muted mb-3">Параметры сервера исходящей почты. Пароль хранится в базе в зашифрованном виде.</p>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-8">
                <label for="smtp_host" class="settings-field-label">Сервер (host)</label>
                <input type="text" name="smtp_host" id="smtp_host" class="form-control rounded-3 @error('smtp_host') is-invalid @enderror"
                    value="{{ old('smtp_host', $settings->smtp_host) }}"
                    placeholder="smtp.example.com"
                    autocomplete="off">
                @error('smtp_host')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="smtp_port" class="settings-field-label">Порт</label>
                <input type="number" name="smtp_port" id="smtp_port" class="form-control rounded-3 @error('smtp_port') is-invalid @enderror"
                    value="{{ old('smtp_port', $settings->smtp_port ?? 587) }}"
                    min="1" max="65535"
                    placeholder="587">
                @error('smtp_port')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="smtp_encryption" class="settings-field-label">Шифрование</label>
            <select class="form-select rounded-3 @error('smtp_encryption') is-invalid @enderror" name="smtp_encryption" id="smtp_encryption">
                @php $enc = old('smtp_encryption', $settings->smtp_encryption ?? 'tls'); @endphp
                <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>STARTTLS (обычно порт 587)</option>
                <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL/TLS (обычно порт 465)</option>
                <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>Без шифрования</option>
            </select>
            @error('smtp_encryption')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label for="smtp_username" class="settings-field-label">Имя пользователя SMTP</label>
                <input type="text" name="smtp_username" id="smtp_username" class="form-control rounded-3 @error('smtp_username') is-invalid @enderror"
                    value="{{ old('smtp_username', $settings->smtp_username) }}"
                    autocomplete="username">
                @error('smtp_username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="smtp_password" class="settings-field-label">Пароль SMTP</label>
                <input type="password" name="smtp_password" id="smtp_password" class="form-control rounded-3 @error('smtp_password') is-invalid @enderror"
                    value=""
                    placeholder="{{ $settings->smtp_password ? 'Оставьте пустым, чтобы не менять' : 'Пароль' }}"
                    autocomplete="new-password">
                @error('smtp_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="settings-section-title mb-3 mt-4">Отправитель</div>
        <p class="small text-muted mb-3">
            Адрес «От кого» должен совпадать с доменом, который вы подтвердили у своего SMTP-провайдера (например, в панели smtp.bz — раздел доменов).
            Публичные ящики вроде <code>@gmail.com</code> чаще всего <strong>нельзя</strong> указать как отправителя: сервер вернёт ошибку «domain not verified».
        </p>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <label for="mail_from_address" class="settings-field-label">Адрес «От кого»</label>
                <input type="email" name="mail_from_address" id="mail_from_address" class="form-control rounded-3 @error('mail_from_address') is-invalid @enderror"
                    value="{{ old('mail_from_address', $settings->mail_from_address) }}"
                    placeholder="noreply@example.com">
                @error('mail_from_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="mail_from_name" class="settings-field-label">Имя отправителя</label>
                <input type="text" name="mail_from_name" id="mail_from_name" class="form-control rounded-3 @error('mail_from_name') is-invalid @enderror"
                    value="{{ old('mail_from_name', $settings->mail_from_name) }}"
                    placeholder="{{ $settings->title }}">
                @error('mail_from_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check2 me-1"></i> Сохранить изменения
        </button>
    </form>

    <div class="settings-section-title mb-3 mt-4">Тест SMTP</div>
    <p class="small text-muted mb-3">Отправьте тестовое письмо на указанный адрес, чтобы проверить текущие настройки SMTP.</p>
    <form action="/settings/email/test" method="post" class="mb-0">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-8">
                <label for="test_email" class="settings-field-label">E-mail для теста</label>
                <input type="email" name="test_email" id="test_email" class="form-control rounded-3 @error('test_email') is-invalid @enderror"
                    value="{{ old('test_email', $user->email ?? '') }}"
                    placeholder="user@example.com">
                @error('test_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-outline-primary rounded-pill px-4 w-100">
                    <i class="bi bi-send me-1"></i> Отправить тестовое сообщение
                </button>
            </div>
        </div>
    </form>

    @php
        $failureTree = $mailFailureTree ?? [];
        $failureTotal = $mailFailureTotal ?? 0;
        $mailSendLogLines = $mailSendLogLines ?? [];
    @endphp

    <div class="settings-section-title mb-3 mt-5">Журнал отправки</div>
    <p class="small text-muted mb-3">
        Итоги отправки писем и при <code>APP_DEBUG=true</code> — отладочный обмен с SMTP пишутся только в файл
        <code class="small">storage/logs/mail.log</code> (в HTTP-ответ и на экран это не попадает). Ниже — последние строки этого файла.
    </p>
    @if (count($mailSendLogLines) === 0)
        <div class="alert alert-light border rounded-3 mb-0">Записей пока нет (файл журнала отсутствует или пуст).</div>
    @else
        <pre class="small bg-dark text-light border-0 rounded-3 p-3 mb-0 font-monospace" style="max-height: 320px; overflow: auto; white-space: pre-wrap; word-break: break-word;">@foreach ($mailSendLogLines as $line){{ $line }}
@endforeach</pre>
    @endif

    <div class="settings-section-title mb-3 mt-5">Журнал ошибок доставки</div>
    <p class="small text-muted mb-3">
        Записи о неудачных попытках отправки и конфигурационных препятствиях (отключённая почта, нет SMTP и т.д.), сгруппированные по разделу системы и получателю.
        @if ($failureTotal > 0)
            Всего записей в базе: <strong>{{ $failureTotal }}</strong>; на экране — до 400 последних.
        @else
            Записей пока нет.
        @endif
    </p>

    @if (count($failureTree) === 0)
        <div class="alert alert-light border rounded-3 mb-0">Ошибок доставки не зафиксировано (или таблица журнала ещё не создана — выполните миграции).</div>
    @else
        <div class="mail-failure-tree border rounded-3 overflow-hidden">
            @foreach ($failureTree as $catKey => $catData)
                <details class="mail-failure-cat border-bottom mb-0" @if ($loop->first) open @endif>
                    <summary class="px-3 py-2 bg-light fw-semibold user-select-none" style="cursor: pointer;">
                        {{ $catData['label'] }}
                        <span class="text-muted fw-normal small">({{ count($catData['recipients']) }} получ.)</span>
                    </summary>
                    <div class="ps-3 pe-2 pb-2 pt-1">
                        @foreach ($catData['recipients'] as $recipientData)
                            <details class="mb-2" @if ($loop->first && $loop->parent->first) open @endif>
                                <summary class="small fw-medium py-1 user-select-none" style="cursor: pointer;">
                                    {{ $recipientData['display'] }}
                                    @if (!empty($recipientData['email']))
                                        <span class="text-muted">— {{ $recipientData['email'] }}</span>
                                    @endif
                                    <span class="text-muted">({{ $recipientData['items']->count() }})</span>
                                </summary>
                                <ul class="list-unstyled small mb-0 ps-2 border-start ms-2">
                                    @foreach ($recipientData['items'] as $fail)
                                        <li class="mb-3 pb-2 border-bottom border-light">
                                            <div class="text-muted">{{ $fail->created_at?->format('d.m.Y H:i:s') }}</div>
                                            <div><span class="text-muted">Тип:</span> {{ \App\Models\MailDeliveryFailure::mailTypeLabel($fail->mail_type) }}</div>
                                            <div><span class="text-muted">Тема:</span> {{ $fail->subject }}</div>
                                            <div><span class="text-muted">Код:</span> {{ \App\Models\MailDeliveryFailure::failureCodeLabel($fail->failure_code) }} <code class="small">{{ $fail->failure_code }}</code></div>
                                            <div class="mt-1"><span class="text-muted">Сообщение:</span> {{ $fail->error_message }}</div>
                                            @if (!empty($fail->phpmailer_error_info))
                                                <div class="mt-1"><span class="text-muted">PHPMailer:</span> <code class="small d-block text-break">{{ $fail->phpmailer_error_info }}</code></div>
                                            @endif
                                            @if ($fail->triggered_by_employee_id)
                                                <div class="mt-1"><span class="text-muted">Инициатор (ID):</span> {{ $fail->triggered_by_employee_id }}</div>
                                            @endif
                                            @if (!empty($fail->meta) && is_array($fail->meta))
                                                <details class="mt-1">
                                                    <summary class="text-muted" style="cursor: pointer;">Доп. данные</summary>
                                                    <pre class="small bg-light p-2 rounded mb-0 mt-1 text-break" style="max-height: 12rem; overflow: auto;">{{ json_encode($fail->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                </details>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    @endif
@endsection
