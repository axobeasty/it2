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
            <p class="display-6">Настройки системы</p>
        </div>
        <div>
            <div class="row border-bottom p-3">
                <div class="col-3">
                    <a class="row text-decoration-none text-dark border-end" href="/settings/general">

                        <div class="col-3"><img class="rounded-circle" src="https://img.icons8.com/?size=100&id=xsg8DrPwYfql&format=png&color=000000" alt=""></div>
                        <div class="col">
                            <div class="d-flex align-self-center"><p class="display-6 p-3 d-flex align-self-center">Настройки
                                </p>
                            </div>

                        </div>
                    </a>
                </div>
                <div class="col-3">
                    <a class="row text-decoration-none text-dark border-end" href="/settings/general">

                        <div class="col-3"><img class="" src="https://img.icons8.com/?size=100&id=g5dx3VExcn2X&format=png&color=000000" alt=""></div>
                        <div class="col">
                            <div class="d-flex align-self-center"><p class="display-6 p-3 d-flex align-self-center">Основное меню
                                </p>
                            </div>

                        </div>
                    </a>
                </div>
                <div class="col-3">
                    <a class="row text-decoration-none text-dark border-end" href="/settings/authenticate">

                        <div class="col-3"><img class="" src="https://img.icons8.com/?size=100&id=AZOFoSZnC0QG&format=png&color=000000" alt=""></div>
                        <div class="col">
                            <div class="d-flex align-self-center"><p class="display-6 p-3 d-flex align-self-center">Аутентификация
                                </p>
                            </div>

                        </div>
                    </a>
                </div>
                <div class="col-3">
                    <a class="row text-decoration-none text-dark" href="/settings/database">
                        <div class="col-3"><img class="" src="https://img.icons8.com/?size=100&id=114023&format=png&color=000000" alt=""></div>
                        <div class="col">
                            <div class="d-flex align-self-center"><p class="display-6 p-3 d-flex align-self-center">База данных
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>




        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
