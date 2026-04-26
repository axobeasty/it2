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
@endsection
