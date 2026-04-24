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
            <p class="display-6">Настройки аутентификации в системе </p>
            <a href="/settings" class="text-decoration-none"> ← Назад</a>
        </div>
        <div class="p-5">

            <form action="/settings/save" method="post">
                @csrf
                <div class="row">
                    <div class="col-2"><p class="lead p-1">Метод аутентификации</p></div>
                    <div class="col">
                        <input type="text" name="page" value="authenticate" hidden>
                        <select class="form-select" name="auth_method" aria-label="Default select example">
                            <option selected>
    @switch($settings->auth_mode)
        @case('0')
            По паролю
                @break
        @case('1')
            Госуслуги
                @break
        @case('2')
           Госуслуги или пароль
                @break
    @endswitch
</option>
                            <option value="0">Пароль</option>
                            <option value="1">Госуслуги</option>
                            <option value="2">Госуслуги и пароль</option>
                        </select>
                    </div>
                </div>
                @if($settings->auth_mode == 2)
                    <div class="row pt-5">
                        <div class="col-2"><p class="lead p-1">Настройки аутентификации по паролю</p></div>
                        <div class="col">
                            <p>В разработке</p>
                        </div>
                    </div>
                    <div class="row pt-5">
                        <div class="col-2"><p class="lead p-1">Настройки аутентификации через Госуслуги</p></div>
                        <div class="col">
                            <p>В разработке</p>
                        </div>
                    </div>
                    @endif
                <div class="pt-5 d-flex justify-content-start"><button type="submit" class="btn btn-dark ">Сохранить изменения</button></div>
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
