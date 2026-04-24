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
            <p class="display-6">Настройки базы данных</p>
            <a href="/settings" class="text-decoration-none"> ← Назад</a>
        </div>
        <div class="p-5">
            <form action="/settings/save" method="post" class="border rounded p-4 mb-4">
                @csrf
                <input type="text" name="page" value="database" hidden>
                <h4 class="mb-3">Активная БД</h4>
                <div class="mb-3">
                    <select class="form-select" name="db_profile">
                        <option value="sqlite" {{$activeProfile === 'sqlite' ? 'selected' : ''}}>SQLite (локальная)</option>
                        <option value="remote" {{$activeProfile === 'remote' ? 'selected' : ''}}>Remote MySQL</option>
                    </select>
                </div>

                <h4 class="mb-3 mt-4">Параметры удаленной БД</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Host</label>
                        <input type="text" class="form-control" name="remote_host" value="{{$remoteHost}}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Port</label>
                        <input type="text" class="form-control" name="remote_port" value="{{$remotePort}}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Database</label>
                        <input type="text" class="form-control" name="remote_database" value="{{$remoteDatabase}}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="remote_username" value="{{$remoteUsername}}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="remote_password" value="{{$remotePassword}}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Charset</label>
                        <input type="text" class="form-control" name="remote_charset" value="{{$remoteCharset}}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Collation</label>
                        <input type="text" class="form-control" name="remote_collation" value="{{$remoteCollation}}">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn btn-dark">Сохранить параметры БД</button>
                </div>
            </form>

            <form action="/settings/database/migrate" method="post" class="border rounded p-4">
                @csrf
                <h4 class="mb-3">Миграция данных между БД</h4>
                <p class="text-muted">
                    Перед переносом убедитесь, что структура БД создана миграциями. Существующие данные в целевой БД будут перезаписаны.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Источник</label>
                        <select class="form-select" name="source_profile">
                            <option value="sqlite">SQLite</option>
                            <option value="remote">Remote</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Назначение</label>
                        <select class="form-select" name="target_profile">
                            <option value="remote">Remote</option>
                            <option value="sqlite">SQLite</option>
                        </select>
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" class="btn btn-outline-primary">Запустить перенос данных</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
