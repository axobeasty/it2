<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$settings->title}} — Мой инвентарь</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body>
<style>body{background:#eaeff6;}</style>

@include('layout.nav')
<div class="container-fluid px-2 px-md-3 py-2 py-md-3">
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-2 p-0 p-lg-3 pt-lg-2 sidebar-offcanvas-column">
            @include('layout.sidebar_offcanvas')
        </div>
        <div class="col-12 col-lg">
            <main class="bg-white bg-gradient shadow-sm rounded p-3">

        <div class="p-3">
            <div class="row ps-3">
                <div class="col border-start rounded border-4 border-primary">
                    <h3 class="">Ваш инвентарь</h3>
                    <p class="text-secondary">На данной странице отображается весь закрепленный за Вами инвентарь.</p>
                </div>
            </div>
            <div class="mt-5">

                @if(count($numbers) > 0)
                    <table id="myTable" class="table table-hover">
                        <thead>
                        <tr>
                            <th>Инвентарный номер</th>
                            <th>Название</th>
                            <th>Кабинет</th>
                            <th>Тип</th>
                            <th>Дата прикрепления</th>
                            <th>Дата открепления</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($numbers as $data)
                            <tr>
                                <td><span class="badge rounded-pill text-bg-primary">{{$data->number}}</span></td>
                                <td>{{$data->store->name ?? '—'}}</td>
                                <td>{{$data->room ?: '—'}}</td>
                                <td><span class="badge text-bg-secondary">{{$data->store->type->name ?? '—'}}</span></td>
                                <td>{{$data->date_in ?: '—'}}</td>
                                <td>{{$data->date_out ?: '—'}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="lead d-flex justify-content-center align-items-center">За вами пока ничего не закреплено.</p>
                @endif
            </div>
        </div>
        <div class="table-responsive small">
        </div>
            </main>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.1/js/dataTables.min.js"></script>
<script>
    if (document.getElementById('myTable')) {
        new DataTable('#myTable');
    }
</script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
