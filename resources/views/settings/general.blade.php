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
<style>
    body{
        background: #eaeff6;


    }
</style>
@include('layout.nav')
<div class="row d-flex flex-grow-1 h-100">
    <div class="col bg-white">
        <div class="row bg-light border-bottom p-3">
            <p class="display-6">Основные настройки </p>
            <a href="/settings" class="text-decoration-none"> ← Назад</a>
        </div>
        <div class="p-5">

            <form action="/settings/save" method="post">
                @csrf
                <div class="row">
                    <div class="col-2"><p class="lead p-1">Заголовок сайта</p></div>
                    <div class="col"><input type="text" name="title" value="{{$settings->title}}" class="form-control" id=""></div>
                </div>
                <input type="text" name="page" value="general" hidden>
                <div class="row pt-5">
                    <div class="col-2"><p class="lead p-1">Техническое обслуживание</p></div>
                    <div class="col">
                        @if($settings->is_enabled == 1)
                        <a href="/settings/general/site/disable" class="btn btn-outline-danger">Выключить сайт</a>
                        @else
                            <a href="/settings/general/site/enable" class="btn btn-outline-success">Включить сайт</a>
                        @endif
                    </div>
                </div>

                <div class="row disabled">
                    <div class="col-2"><p class="lead p-1">Причина отключения</p></div>
                    <div class="col">
                        <div class="row ">
                            <div class="col-4 "> <textarea class="form-control" name="disable_reason" aria-label="With textarea">{{$settings->disable_reason}}</textarea></div>
                        </div>

                    </div>
                </div>
                <div class="pt-5 d-flex justify-content-start"><button type="submit" class="btn btn-dark ">Сохранить изменения</button></div>
            </form>

            <hr class="my-4">
            @php
                $deployResolved = \App\Support\DeployVersion::resolveLocalRef(base_path());
            @endphp
            <div class="row">
                <div class="col-2">
                    <p class="lead p-1">Обновления кода</p>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary" id="btn-git-check-and-pull">
                            Проверить обновления
                        </button>
                    </div>
                    @if ($deployResolved['source'] === 'env')
                        <p class="small text-secondary mb-2">
                            Сейчас используется <code>DEPLOY_GIT_REF</code> из <code>.env</code> (значение: <code class="user-select-all">{{ $deployResolved['ref'] }}</code>).
                            Файл <code>deploy.json</code> из панели не подхватится, пока задана эта переменная.
                        </p>
                    @else
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label for="deploy-ref-input" class="form-label small mb-0">Commit на сервере (SHA из GitHub после выкладки)</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="deploy-ref-input"
                                    value="{{ $deployResolved['ref'] ?? '' }}"
                                    placeholder="например aab3eed"
                                    autocomplete="off"
                                    maxlength="40">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deploy-ref-save" title="Пишет storage/app/deploy.json на сервере">
                                    Сохранить метку
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="small text-muted mt-2" id="git-update-status">
                        Если на сервере есть <code>.git</code> — проверка через git и при необходимости <code>git pull</code>.
                        Без <code>.git</code> сравнение идёт с GitHub: при первой проверке укажите SHA залитого коммита; если метка совпадает с веткой, система сама обновит <code>deploy.json</code>. После <code>git pull</code> метка тоже записывается автоматически (если не задан только <code>DEPLOY_GIT_REF</code> в <code>.env</code>).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deploy-update-modal" tabindex="-1" aria-labelledby="deploy-update-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="deploy-update-modal-title">Обновление кода</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть" id="deploy-modal-dismiss-x"></button>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar" id="deploy-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="small text-muted mb-1">Журнал операций</p>
                <div id="deploy-console" class="deploy-console border rounded bg-dark text-light px-3 py-2 small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="deploy-modal-close">Закрыть</button>
            </div>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
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
        statusBox.className = isError ? 'small text-danger mt-2' : 'small text-muted mt-2';
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
                logLine('Укажите commit на странице ниже (поле «Commit на сервере») и нажмите «Сохранить метку», затем снова «Проверить обновления».');
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
            const pull = await postJson('/settings/git/pull-updates');
            setProgress(100, { variant: 'bg-success' });
            if (pull.message) {
                logLine(pull.message);
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
{!! Toastr::message() !!}
</body>
</html>
