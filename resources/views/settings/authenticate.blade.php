@extends('layout.settings', ['settingsSection' => 'authenticate'])

@section('page_title', 'Аутентификация — ' . $settings->title)

@section('settings_heading', 'Аутентификация')
@section('settings_subheading', 'Способ входа пользователей в систему.')

@section('settings_content')
    <form action="/settings/save" method="post">
        @csrf
        <input type="hidden" name="page" value="authenticate">

        <div class="mb-4">
            <label for="auth_method" class="settings-field-label">Метод аутентификации</label>
            <select class="form-select form-select-lg rounded-3" name="auth_method" id="auth_method" aria-label="Метод аутентификации">
                <option value="0" {{ (string) $settings->auth_mode === '0' ? 'selected' : '' }}>Только пароль</option>
                <option value="1" {{ (string) $settings->auth_mode === '1' ? 'selected' : '' }}>Госуслуги</option>
                <option value="2" {{ (string) $settings->auth_mode === '2' ? 'selected' : '' }}>Госуслуги и пароль</option>
            </select>
        </div>

        @if((string) $settings->auth_mode === '2')
            <div class="alert alert-light border rounded-3 small mb-4">
                <div class="fw-semibold mb-2"><i class="bi bi-hammer me-1"></i> Комбинированный режим</div>
                <p class="mb-2 text-muted">Дополнительные параметры для пароля и Госуслуг — в разработке.</p>
            </div>
        @endif

        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check2 me-1"></i> Сохранить изменения
        </button>
    </form>
@endsection
