<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$settings->title}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body>
<style>body{background:#eaeff6;}</style>
@include('layout.nav')

<div class="row d-flex flex-grow-1 h-100">
    <div class="col bg-white">
        <div class="row bg-light border-bottom p-3">
            <p class="display-6">Настройки базы данных</p>
            <a href="/settings" class="text-decoration-none"> ← Назад</a>
        </div>
        <div class="p-5">
            <div class="border rounded p-4">
                <h4 class="mb-2">Единый мастер настройки БД</h4>
                <p class="text-muted mb-3">
                    Нажмите кнопку и выполните шаги внутри модального окна:
                    профиль -> проверка подключения -> dry-run -> подтверждение -> выполнение.
                </p>
                <button type="button" class="btn btn-dark" id="btn-open-db-wizard">Начать настройку БД</button>
                <span class="small text-muted ms-3">Текущий профиль: <b id="current-active-profile">{{$activeProfile}}</b></span>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dbWizardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Мастер настройки БД</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-secondary" id="w-step-1">1. Профиль</span>
                    <span class="badge text-bg-secondary" id="w-step-2">2. Проверка</span>
                    <span class="badge text-bg-secondary" id="w-step-3">3. Dry-run</span>
                    <span class="badge text-bg-secondary" id="w-step-4">4. Подтверждение</span>
                    <span class="badge text-bg-secondary" id="w-step-5">5. Выполнение</span>
                </div>

                <div id="step1">
                    <label class="form-label">Профиль БД</label>
                    <select class="form-select mb-3" id="wiz-db-profile">
                        <option value="sqlite" {{$activeProfile === 'sqlite' ? 'selected' : ''}}>SQLite (локальная)</option>
                        <option value="remote" {{$activeProfile === 'remote' ? 'selected' : ''}}>Remote MySQL</option>
                    </select>
                    <div id="wiz-remote-fields">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Host</label><input type="text" id="wiz-remote-host" class="form-control" value="{{$remoteHost}}"></div>
                            <div class="col-md-3"><label class="form-label">Port</label><input type="text" id="wiz-remote-port" class="form-control" value="{{$remotePort}}"></div>
                            <div class="col-md-6"><label class="form-label">Database</label><input type="text" id="wiz-remote-database" class="form-control" value="{{$remoteDatabase}}"></div>
                            <div class="col-md-6"><label class="form-label">Username</label><input type="text" id="wiz-remote-username" class="form-control" value="{{$remoteUsername}}"></div>
                            <div class="col-md-6"><label class="form-label">Password</label><input type="text" id="wiz-remote-password" class="form-control" value="{{$remotePassword}}"></div>
                            <div class="col-md-3"><label class="form-label">Charset</label><input type="text" id="wiz-remote-charset" class="form-control" value="{{$remoteCharset}}"></div>
                            <div class="col-md-3"><label class="form-label">Collation</label><input type="text" id="wiz-remote-collation" class="form-control" value="{{$remoteCollation}}"></div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" id="wiz-save-profile">Сохранить профиль</button>
                </div>

                <div id="step2" class="d-none mt-3 text-muted small">
                    Автоматическая проверка подключения выполняется после сохранения профиля.
                </div>
                <div id="step3" class="d-none mt-3 text-muted small">
                    Dry-run выполняется автоматически после успешной проверки подключения.
                </div>
                <div id="step4" class="d-none mt-3">
                    <label class="form-label">Введите ПОДТВЕРЖДАЮ</label>
                    <input class="form-control" id="wiz-confirm" type="text">
                </div>
                <div id="step5" class="d-none mt-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-danger" id="wiz-init">Инициализировать remote БД</button>
                    <button class="btn btn-outline-primary" id="wiz-migrate">Миграция sqlite -> remote</button>
                    <button class="btn btn-success" id="wiz-activate-profile">Переключить профиль</button>
                </div>

                <div id="wiz-status" class="alert alert-secondary mt-3 mb-0">Ожидание запуска мастера...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="migrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="migrationModalLabel">Операция с БД</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="migration-progress-bar" style="width:0%">0%</div>
                </div>
                <pre id="migration-console" class="bg-dark text-light rounded p-3" style="min-height:280px;max-height:420px;white-space:pre-wrap;"></pre>
            </div>
            <div class="modal-footer">
                <span class="text-muted small me-auto" id="migration-status-hint">Ожидание запуска...</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const isCurrentlyRemote = '{{ $activeProfile }}' === 'remote';
    const wizardModal = new bootstrap.Modal(document.getElementById('dbWizardModal'));
    const streamModal = new bootstrap.Modal(document.getElementById('migrationModal'));
    const statusBox = document.getElementById('wiz-status');
    const profile = document.getElementById('wiz-db-profile');
    const confirmInput = document.getElementById('wiz-confirm');
    const currentProfileText = document.getElementById('current-active-profile');

    const state = { saved:false, checked:false, dry:false, confirmed:false };

    function setStatus(message, type) {
        const alertType = type || 'secondary';
        statusBox.className = `alert alert-${alertType} mt-3 mb-0`;
        statusBox.textContent = message;
    }

    function sync() {
        const remote = profile.value === 'remote';
        document.getElementById('wiz-remote-fields').classList.toggle('d-none', !remote);
        document.getElementById('step2').classList.toggle('d-none', !state.saved || !remote);
        document.getElementById('step3').classList.toggle('d-none', !state.checked || !remote);
        document.getElementById('step4').classList.toggle('d-none', !state.dry || !remote);
        document.getElementById('step5').classList.toggle('d-none', !state.saved || (remote && !state.confirmed));
        const isReadyForRemoteActions = state.saved && (!remote || (state.checked && state.dry && state.confirmed));
        document.getElementById('wiz-init').disabled = !isReadyForRemoteActions || (remote && isCurrentlyRemote);
        document.getElementById('wiz-migrate').disabled = !isReadyForRemoteActions;
        document.getElementById('wiz-activate-profile').disabled = !isReadyForRemoteActions;
    }

    function buildPayload() {
        return new URLSearchParams({
            db_profile: profile.value,
            remote_host: document.getElementById('wiz-remote-host').value || '',
            remote_port: document.getElementById('wiz-remote-port').value || '3306',
            remote_database: document.getElementById('wiz-remote-database').value || '',
            remote_username: document.getElementById('wiz-remote-username').value || '',
            remote_password: document.getElementById('wiz-remote-password').value || '',
            remote_charset: document.getElementById('wiz-remote-charset').value || 'utf8mb4',
            remote_collation: document.getElementById('wiz-remote-collation').value || 'utf8mb4_unicode_ci',
        });
    }

    async function postJson(url, body) {
        let response;
        try {
            response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                },
                body: body ? body.toString() : '',
            });
        } catch (_networkError) {
            throw new Error('Не удалось связаться с сервером (Failed to fetch). Проверьте, что сайт не перезапустился и откройте страницу заново.');
        }
        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || 'Ошибка запроса.');
        }
        return payload;
    }

    function appendLog(message) {
        const box = document.getElementById('migration-console');
        box.textContent += `[${new Date().toLocaleTimeString('ru-RU')}] ${message}\n`;
        box.scrollTop = box.scrollHeight;
    }

    function setProgress(percent) {
        const bar = document.getElementById('migration-progress-bar');
        const safe = Math.max(0, Math.min(100, percent));
        bar.style.width = `${safe}%`;
        bar.textContent = `${safe}%`;
    }

    async function runStream(url, body, title) {
        document.getElementById('migrationModalLabel').textContent = title;
        document.getElementById('migration-console').textContent = '';
        setProgress(0);
        streamModal.show();
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'text/plain',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: body ? body.toString() : '',
        });
        if (!response.body) {
            throw new Error('Сервер не вернул поток.');
        }
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let buffer = '';
        while (true) {
            const chunk = await reader.read();
            if (chunk.done) break;
            buffer += decoder.decode(chunk.value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop() || '';
            lines.forEach(function (line) {
                const trimmed = line.trim();
                if (!trimmed) return;
                try {
                    const event = JSON.parse(trimmed);
                    if (typeof event.percent === 'number') setProgress(event.percent);
                    if (event.message) appendLog(event.message);
                } catch (_e) {
                    appendLog(trimmed);
                }
            });
        }
        return true;
    }

    document.getElementById('btn-open-db-wizard').addEventListener('click', function () {
        setStatus('Начните с шага 1: сохраните профиль.');
        wizardModal.show();
        sync();
    });

    profile.addEventListener('change', function () {
        state.saved = false;
        state.checked = false;
        state.dry = false;
        state.confirmed = false;
        confirmInput.value = '';
        sync();
    });

    confirmInput.addEventListener('input', function () {
        state.confirmed = confirmInput.value.trim().toUpperCase() === 'ПОДТВЕРЖДАЮ';
        sync();
    });

    document.getElementById('wiz-save-profile').addEventListener('click', async function () {
        try {
            const endpoint = profile.value === 'remote'
                ? '/settings/database/save-remote-draft'
                : '/settings/database/save';
            const payload = await postJson(endpoint, buildPayload());
            state.saved = true;
            if (profile.value === 'sqlite') {
                state.checked = true;
                state.dry = true;
                state.confirmed = true;
            } else {
                state.checked = false;
                state.dry = false;
                state.confirmed = false;
                confirmInput.value = '';
                setStatus('Профиль сохранен. Автоматическая проверка подключения...', 'info');
                const checkPayload = await postJson('/settings/database/test-connection');
                state.checked = true;
                setStatus((checkPayload.message || 'Подключение проверено.') + ' Запускаем dry-run...', 'info');
                const dryPayload = await postJson('/settings/database/dry-run-init');
                state.dry = true;
                setStatus(dryPayload.message || 'Dry-run завершен.', 'primary');
            }
            currentProfileText.textContent = payload.active_profile || profile.value;
            if (profile.value === 'sqlite') {
                setStatus(payload.message || 'Профиль сохранен.', 'success');
            }
        } catch (error) {
            state.saved = false;
            state.checked = false;
            state.dry = false;
            state.confirmed = false;
            setStatus(error.message, 'danger');
        }
        sync();
    });

    document.getElementById('wiz-init').addEventListener('click', async function () {
        if (!window.confirm('Инициализировать удаленную БД?')) return;
        await runStream('/settings/database/initialize-stream', null, 'Инициализация удаленной БД');
        try {
            const activation = await postJson('/settings/database/activate-profile', new URLSearchParams({ db_profile: 'remote' }));
            currentProfileText.textContent = activation.active_profile || 'remote';
            setStatus(activation.message || 'Профиль переключен на remote.', 'success');
        } catch (error) {
            setStatus(error.message || 'Инициализация завершилась, но профиль не переключен.', 'warning');
        }
    });

    document.getElementById('wiz-migrate').addEventListener('click', async function () {
        const body = new URLSearchParams({ source_profile: 'sqlite', target_profile: 'remote' });
        await runStream('/settings/database/migrate-stream', body, 'Миграция sqlite -> remote');
        try {
            const activation = await postJson('/settings/database/activate-profile', new URLSearchParams({ db_profile: 'remote' }));
            currentProfileText.textContent = activation.active_profile || 'remote';
            setStatus(activation.message || 'Профиль переключен на remote.', 'success');
        } catch (error) {
            setStatus(error.message || 'Миграция завершилась, но профиль не переключен.', 'warning');
        }
    });

    document.getElementById('wiz-activate-profile').addEventListener('click', async function () {
        try {
            const targetProfile = profile.value === 'remote' ? 'remote' : 'sqlite';
            const activation = await postJson('/settings/database/activate-profile', new URLSearchParams({ db_profile: targetProfile }));
            currentProfileText.textContent = activation.active_profile || targetProfile;
            setStatus(activation.message || `Профиль переключен на ${targetProfile}.`, 'success');
        } catch (error) {
            setStatus(error.message || 'Не удалось переключить профиль.', 'warning');
        }
    });

    sync();
})();
</script>
{!! Toastr::message() !!}
</body>
</html>
