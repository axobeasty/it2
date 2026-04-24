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
            <select class="form-select form-select-lg rounded-3" name="email_enabled" id="email_enabled" aria-label="Отправка уведомлений">
                <option value="1" {{ (string) $settings->email_enabled === '1' ? 'selected' : '' }}>Включено</option>
                <option value="0" {{ (string) $settings->email_enabled === '0' ? 'selected' : '' }}>Отключено</option>
            </select>
            <p class="small text-muted mt-2 mb-0">Параметры SMTP задаются в конфигурации приложения (<code>.env</code>).</p>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check2 me-1"></i> Сохранить изменения
        </button>
    </form>
@endsection
