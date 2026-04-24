@extends('layout.settings', ['settingsSection' => 'general'])

@section('page_title', 'Основные настройки — ' . $settings->title)

@section('settings_heading', 'Основные настройки')
@section('settings_subheading', 'Параметры сайта, режим обслуживания и обновления кода.')

@section('settings_content')
            <form action="/settings/save" method="post" class="mb-2">
                @csrf
                <input type="hidden" name="page" value="general">

                <div class="mb-4">
                    <div class="settings-section-title">Сайт</div>
                    <label for="settings-site-title" class="settings-field-label">Заголовок сайта</label>
                    <input type="text" name="title" id="settings-site-title" value="{{ $settings->title }}" class="form-control form-control-lg rounded-3" maxlength="255">
                </div>

                <div class="mb-4">
                    <div class="settings-section-title">Доступность</div>
                    <label class="settings-field-label d-block">Техническое обслуживание</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($settings->is_enabled == 1)
                            <a href="/settings/general/site/disable" class="btn btn-outline-danger rounded-pill px-4">
                                <i class="bi bi-power me-1"></i> Выключить сайт
                            </a>
                            <span class="small text-success"><i class="bi bi-check-circle-fill me-1"></i>Сайт открыт для пользователей</span>
                        @else
                            <a href="/settings/general/site/enable" class="btn btn-success rounded-pill px-4">
                                <i class="bi bi-play-fill me-1"></i> Включить сайт
                            </a>
                            <span class="small text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Сайт в режиме обслуживания</span>
                        @endif
                    </div>
                </div>

                <div class="mb-4">
                    <label for="settings-disable-reason" class="settings-field-label">Текст при отключении</label>
                    <textarea class="form-control rounded-3" name="disable_reason" id="settings-disable-reason" rows="3" placeholder="Сообщение на странице заглушки">{{ $settings->disable_reason }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-check2 me-1"></i> Сохранить изменения
                </button>
            </form>

            <hr class="my-5 opacity-25">

            @php
                $deployResolved = \App\Support\DeployVersion::resolveLocalRef(base_path());
            @endphp
            <div class="settings-section-title">Обновления кода</div>
            <p class="text-muted small mb-3">
                Проверка относительно GitHub и при наличии <code>.git</code> — загрузка через <code>git pull</code>.
            </p>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary rounded-pill" id="btn-git-check-and-pull">
                    <i class="bi bi-cloud-download me-1"></i> Проверить обновления
                </button>
            </div>
            @if ($deployResolved['source'] === 'env')
                <div class="alert alert-info rounded-3 small mb-3 py-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Используется <code>DEPLOY_GIT_REF</code> в <code>.env</code>:
                    <code class="user-select-all">{{ $deployResolved['ref'] }}</code>.
                    Файл <code>deploy.json</code> из панели не используется, пока задана эта переменная.
                </div>
            @else
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-7 col-lg-6">
                        <label for="deploy-ref-input" class="form-label small fw-semibold mb-1">Commit на сервере (SHA после выкладки)</label>
                        <input type="text" class="form-control font-monospace rounded-3" id="deploy-ref-input"
                            value="{{ $deployResolved['ref'] ?? '' }}"
                            placeholder="например aab3eed"
                            autocomplete="off"
                            maxlength="40">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" id="btn-deploy-ref-save" title="Пишет storage/app/deploy.json">
                            <i class="bi bi-bookmark-plus me-1"></i> Сохранить метку
                        </button>
                    </div>
                </div>
            @endif
            <p class="small text-muted mb-0" id="git-update-status">
                Без <code>.git</code> укажите SHA залитого коммита; при совпадении с веткой метка в <code>deploy.json</code> обновится автоматически.
                После <code>git pull</code> метка тоже синхронизируется (если нет только <code>DEPLOY_GIT_REF</code> в <code>.env</code>).
            </p>

            <div class="modal fade" id="deploy-update-modal" tabindex="-1" aria-labelledby="deploy-update-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h2 class="modal-title fs-5 fw-semibold" id="deploy-update-modal-title">Обновление кода</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть" id="deploy-modal-dismiss-x"></button>
                        </div>
                        <div class="modal-body pt-2">
                            <div class="progress mb-3 rounded-pill" style="height: 8px;">
                                <div class="progress-bar rounded-pill" id="deploy-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="small text-muted mb-1">Журнал операций</p>
                            <div id="deploy-console" class="deploy-console border rounded-3 bg-dark text-light px-3 py-2 small"></div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal" id="deploy-modal-close">Закрыть</button>
                        </div>
                    </div>
                </div>
            </div>
@endsection

@push('settings_head')
<style>
    .deploy-console {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        max-height: min(320px, 45vh);
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .deploy-console .deploy-log-line {
        margin: 0;
        padding: 2px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .deploy-console .deploy-log-line:last-child {
        border-bottom: none;
    }
</style>
@endpush

@push('settings_scripts')
<script>
(function () {
    const button = document.getElementById('btn-git-check-and-pull');
    const saveRefBtn = document.getElementById('btn-deploy-ref-save');
    const deployRefInput = document.getElementById('deploy-ref-input');
    const statusBox = document.getElementById('git-update-status');
    const csrf = '{{ csrf_token() }}';
    const modalEl = document.getElementById('deploy-update-modal');
    const consoleEl = document.getElementById('deploy-console');
    const progressBar = document.getElementById('deploy-progress-bar');
    const closeBtn = document.getElementById('deploy-modal-close');
    const dismissX = document.getElementById('deploy-modal-dismiss-x');

    if (!statusBox || !modalEl || !consoleEl || !progressBar) {
        return;
    }

    const deployModal = bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: 'static',
        keyboard: false,
    });

    function setStatus(message, isError) {
        statusBox.className = isError ? 'small text-danger mb-0' : 'small text-muted mb-0';
        statusBox.textContent = message;
    }

    function clearConsole() {
        consoleEl.textContent = '';
    }

    function logLine(text) {
        const line = document.createElement('div');
        line.className = 'deploy-log-line';
        const time = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        line.textContent = '[' + time + '] ' + text;
        consoleEl.appendChild(line);
        consoleEl.scrollTop = consoleEl.scrollHeight;
    }

    function setProgress(pct, options) {
        const o = options || {};
        const n = Math.max(0, Math.min(100, Math.round(pct)));
        progressBar.style.width = n + '%';
        progressBar.setAttribute('aria-valuenow', String(n));
        progressBar.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'progress-bar-striped', 'progress-bar-animated');
        if (o.variant) {
            progressBar.classList.add(o.variant);
        }
        if (o.striped) {
            progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
        }
    }

    function setModalBusy(busy) {
        const dis = !!busy;
        if (button) {
            button.disabled = dis;
        }
        if (saveRefBtn) {
            saveRefBtn.disabled = dis;
        }
        if (deployRefInput) {
            deployRefInput.disabled = dis;
        }
        if (closeBtn) {
            closeBtn.disabled = dis;
        }
        if (dismissX) {
            dismissX.disabled = dis;
        }
    }

    async function postJsonWithBody(url, body) {
        let response;
        try {
            response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            });
        } catch (_networkError) {
            const err = new Error('Не удалось связаться с сервером.');
            err.network = true;
            throw err;
        }

        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.ok === false) {
            const err = new Error(payload.message || 'Ошибка запроса.');
            err.payload = payload;
            err.status = response.status;
            throw err;
        }
        return payload;
    }

    async function postJson(url) {
        let response;
        try {
            response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });
        } catch (_networkError) {
            const err = new Error('Не удалось связаться с сервером.');
            err.network = true;
            throw err;
        }

        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.ok === false) {
            const err = new Error(payload.message || 'Ошибка запроса.');
            err.payload = payload;
            err.status = response.status;
            throw err;
        }
        return payload;
    }

    if (saveRefBtn && deployRefInput) {
        saveRefBtn.addEventListener('click', async function () {
            const raw = (deployRefInput.value || '').trim();
            if (raw.length < 7) {
                toastr.error('Введите SHA коммита не короче 7 символов (только 0–9, a–f).');
                return;
            }
            if (!/^[0-9a-fA-F]+$/.test(raw)) {
                toastr.error('SHA должен содержать только шестнадцатеричные символы.');
                return;
            }
            saveRefBtn.disabled = true;
            try {
                const res = await postJsonWithBody('/settings/git/deploy-ref', { ref: raw });
                if (res.ref) {
                    deployRefInput.value = res.ref;
                }
                toastr.success(res.message || 'Метка сохранена.');
                setStatus(res.message || 'Метка версии сохранена. Можно нажать «Проверить обновления».', false);
            } catch (e) {
                toastr.error(e.message || 'Не удалось сохранить.');
            } finally {
                saveRefBtn.disabled = false;
            }
        });
    }

    if (!button) {
        return;
    }

    button.addEventListener('click', async function () {
        clearConsole();
        setProgress(0, { striped: true });
        setModalBusy(true);
        deployModal.show();
        logLine('Старт: проверка обновлений в фоне (страница не перезагружается).');
        setProgress(8, { striped: true });

        try {
            logLine('Запрос: POST /settings/git/check-updates');
            setProgress(22, { striped: true });
            const check = await postJson('/settings/git/check-updates');
            setProgress(48, { striped: true });

            logLine('Ответ получен.');
            if (check.check_method) {
                logLine('Метод проверки: ' + check.check_method);
            }
            if (check.local_ref) {
                logLine('Локальная метка: ' + check.local_ref + (check.local_ref_source ? ' (' + check.local_ref_source + ')' : ''));
            }
            if (check.remote_short || check.remote_ref) {
                logLine('Удалённая ветка ' + (check.remote_branch || '') + ': ' + (check.remote_short || check.remote_ref));
            }
            if (typeof check.behind_count === 'number') {
                logLine('Коммитов впереди на GitHub: ' + check.behind_count);
            }
            if (check.message) {
                logLine(check.message);
            }
            if (check.deploy_ref_note) {
                logLine(check.deploy_ref_note);
            }
            if (check.deploy_ref_saved && check.local_ref && deployRefInput) {
                deployRefInput.value = check.local_ref;
            }

            if (check.has_updates === null || check.has_updates === undefined) {
                setProgress(100, { variant: 'bg-warning' });
                setStatus(check.message || 'Укажите версию на сервере (deploy.json или DEPLOY_GIT_REF).', false);
                toastr.warning(check.message || 'Нужна метка версии на сервере.');
                logLine('Укажите commit на странице (поле «Commit на сервере») и нажмите «Сохранить метку», затем снова «Проверить обновления».');
                if (check.remote_short && deployRefInput && !deployRefInput.value.trim()) {
                    deployRefInput.placeholder = 'например ' + check.remote_short;
                }
                return;
            }

            if (!check.has_updates) {
                setProgress(100, { variant: 'bg-success' });
                setStatus(check.message || 'Обновлений нет.', false);
                toastr.info(check.message || 'Обновлений нет.');
                logLine('Готово: обновлений нет.');
                return;
            }

            if (!check.can_pull) {
                setProgress(100, { variant: 'bg-warning' });
                logLine('На сервере нет git clone — автоматический pull недоступен.');
                logLine('Выложите файлы вручную (FTP/SSH/CI) и обновите ref в storage/app/deploy.json.');
                setStatus(
                    (check.message || 'Есть обновления на GitHub.') + ' Выполните выкладку вручную и обновите deploy.json.',
                    false
                );
                toastr.warning(check.message || 'Обновите файлы вручную.');
                return;
            }

            logLine('Запрос: POST /settings/git/pull-updates (git pull --ff-only)');
            setProgress(62, { striped: true });
            let pull;
            try {
                pull = await postJson('/settings/git/pull-updates');
            } catch (pullErr) {
                if (pullErr.payload && pullErr.payload.code === 'working_tree_dirty' && pullErr.payload.can_retry_with_stash) {
                    if (pullErr.payload.dirty_lines && pullErr.payload.dirty_lines.length) {
                        logLine('Мешают обновлению (изменения в отслеживаемых файлах):');
                        pullErr.payload.dirty_lines.forEach(function (ln) { logLine('  ' + ln); });
                    }
                    if (window.confirm('Спрятать эти изменения в git stash и повторить pull? После обновления будет выполнен git stash pop.')) {
                        logLine('Повтор: git stash push → pull → stash pop...');
                        pull = await postJsonWithBody('/settings/git/pull-updates', { stash_first: true });
                    } else {
                        throw pullErr;
                    }
                } else {
                    throw pullErr;
                }
            }
            setProgress(100, { variant: 'bg-success' });
            if (pull.message) {
                logLine(pull.message);
            }
            if (pull.stash_pop_warning) {
                logLine('Предупреждение stash pop:');
                String(pull.stash_pop_warning).split(/\r?\n/).forEach(function (ln) {
                    if (ln.length) { logLine('  ' + ln); }
                });
            }
            if (pull.deploy_ref_note) {
                logLine(pull.deploy_ref_note);
            }
            if (pull.deploy_ref_saved && pull.current_ref && deployRefInput) {
                deployRefInput.value = pull.current_ref;
            }
            if (pull.output) {
                logLine('Вывод git:');
                String(pull.output).split(/\r?\n/).forEach(function (ln) {
                    if (ln.length) {
                        logLine('  ' + ln);
                    }
                });
            }
            logLine('Готово.');
            setStatus(pull.message || 'Обновления успешно скачаны и применены.', false);
            toastr.success(pull.message || 'Обновления успешно скачаны и применены.');
        } catch (error) {
            setProgress(100, { variant: 'bg-danger' });
            const msg = error.message || 'Не удалось выполнить операцию.';
            logLine('Ошибка: ' + msg);
            if (error.payload && error.payload.code) {
                logLine('Код: ' + error.payload.code);
            }
            if (error.status) {
                logLine('HTTP: ' + error.status);
            }
            setStatus(msg, true);
            toastr.error(msg);
        } finally {
            setModalBusy(false);
        }
    });
})();
</script>
@endpush
