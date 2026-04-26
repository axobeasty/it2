<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{$settings->title}}</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body>
<style>
    body{
        background: #eaeff6;


    }
</style>

<div class="container-fluid px-0">
    <div class="row g-0">
        <div class="col-12">
            @include('layout.nav')
        </div>
    </div>
</div>
<div class="container-fluid px-2 px-md-3 py-2 py-md-3">
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg order-1 order-lg-2">
            <main class="bg-white bg-gradient shadow-sm rounded p-3">

        <div class="p-3">
            <div class="row ps-3">
                <div class="col  border-start rounded border-4 border-primary">
                    <h3 class="">Типы инвентаря</h3>
                    <p class="text-secondary"></p>
                </div>
            </div>
            <div class="mt-5">

                @if(count($myes) > 0)
                    <table id="myTable" class="table table-hover">
                        <thead>
                        <tr>
                            <th>Инвентарный(е) номер(а)</th>
                            <th>Название</th>
                            <th>Кабинет</th>
                            <th>Количество</th>
                            <th>Тип</th>
                            <th>Принадлежит</th>
                            <th>Дата прикрепления</th>
                            <th>Дата открепления</th>

                        </tr>
                        </thead>
                        <tbody>
                        @foreach($myes as $data)

                            <tr class="table-success">
                                <td><span class="badge rounded-pill text-bg-primary">{{$data->inv_number}}</span></td>
                                <td>{{$data->name}}</td>
                                <td>{{$data->room}}</td>
                                <td>{{$data->count}}</td>
                                <td class=""><p class="badge text-bg-secondary">{{$inv_type[$data->inv_type_id-1]->name}}</p></td>
                                <td>{{$employees[$data->employees_id-1]->fio}}</td>
                                <td>{{$data->date_in}}</td>
                                <td>{{$data->date_out}}</td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="lead d-flex justify-content-center align-items-center">Ничего не найдено!</p>
                @endif
            </div>
        </div>
        <div class="table-responsive small">
        </div>
            </main>
        </div>
        <div class="col-12 col-lg-2 p-0 p-lg-3 pt-lg-2 sidebar-offcanvas-column order-2 order-lg-1">
            @include('layout.sidebar_offcanvas')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.3.1/js/dataTables.min.js"></script>
<script>
    let table = new DataTable('#myTable');
</script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
