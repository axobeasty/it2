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
            <div class="row">
                <div class="col-2">
                    <p class="lead p-1">Git обновления</p>
                </div>
                <div class="col">
                    <button type="button" class="btn btn-outline-primary" id="btn-git-check-and-pull">
                        Проверить изменения в git репозитории
                    </button>
                    <div class="small text-muted mt-2" id="git-update-status">
                        Нажмите кнопку, чтобы проверить и скачать обновления из удаленного репозитория.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
<script>
(function () {
    const button = document.getElementById('btn-git-check-and-pull');
    const statusBox = document.getElementById('git-update-status');
    const csrf = '{{ csrf_token() }}';

    if (!button || !statusBox) {
        return;
    }

    function setStatus(message, isError) {
        statusBox.className = isError ? 'small text-danger mt-2' : 'small text-muted mt-2';
        statusBox.textContent = message;
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
            throw new Error('Не удалось связаться с сервером.');
        }

        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || 'Ошибка запроса.');
        }
        return payload;
    }

    button.addEventListener('click', async function () {
        button.disabled = true;
        setStatus('Проверяем обновления в удаленном git-репозитории...', false);

        try {
            const check = await postJson('/settings/git/check-updates');
            if (!check.has_updates) {
                setStatus(check.message || 'Обновлений нет.', false);
                toastr.info(check.message || 'Обновлений нет.');
                return;
            }

            setStatus((check.message || 'Найдены обновления.') + ' Скачиваем...', false);
            const pull = await postJson('/settings/git/pull-updates');
            setStatus(pull.message || 'Обновления успешно скачаны и применены.', false);
            toastr.success(pull.message || 'Обновления успешно скачаны и применены.');
        } catch (error) {
            setStatus(error.message || 'Не удалось обновить репозиторий.', true);
            toastr.error(error.message || 'Не удалось обновить репозиторий.');
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
{!! Toastr::message() !!}
</body>
</html>
