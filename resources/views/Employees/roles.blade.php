<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$settings->title}} - Управление ролями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background: #eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Управление ролями</h4>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">Добавить роль</button>
        </div>
        <hr>

        <table class="table table-hover">
            <thead>
            <tr>
                <th>Название</th>
                <th>Тип</th>
                <th>Права на страницы</th>
                <th class="text-end">Действия</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{$role->name}}</td>
                    <td>@if($role->is_system)<span class="badge text-bg-secondary">Системная</span>@else<span class="badge text-bg-primary">Кастомная</span>@endif</td>
                    <td>
                        @foreach($role->pagePermissions as $perm)
                            <span class="badge text-bg-light border me-1">{{ $pages[$perm->page_key] ?? $perm->page_key }}</span>
                        @endforeach
                    </td>
                    <td class="text-end">
                        <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editRole{{$role->id}}">Редактировать</button>
                        @if(!$role->is_system)
                            <a href="/roles/{{$role->id}}/delete" class="btn btn-outline-danger btn-sm">Удалить</a>
                        @endif
                    </td>
                </tr>

                <div class="modal fade" id="editRole{{$role->id}}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Редактирование роли</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="/roles/{{$role->id}}/edit" method="post">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Название роли</label>
                                        @if($role->is_system)
                                            <input type="text" class="form-control bg-light" value="{{ $role->name }}" readonly aria-readonly="true">
                                            <div class="form-text">Название системной роли нельзя изменить.</div>
                                        @else
                                            <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                        @endif
                                    </div>
                                    <label class="form-label">Доступ к страницам</label>
                                    @foreach($groupedPages as $sectionTitle => $sectionItems)
                                        <h6 class="text-secondary small text-uppercase mt-3 mb-2">{{ $sectionTitle }}</h6>
                                        <div class="row">
                                            @foreach($sectionItems as $key => $label)
                                                <div class="col-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{$key}}" id="p_{{$role->id}}_{{$key}}" @if($role->pagePermissions->contains('page_key', $key)) checked @endif>
                                                        <label class="form-check-label" for="p_{{$role->id}}_{{$key}}">{{$label}}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-dark">Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая кастомная роль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/roles/create" method="post">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название роли</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <label class="form-label">Доступ к страницам</label>
                    @foreach($groupedPages as $sectionTitle => $sectionItems)
                        <h6 class="text-secondary small text-uppercase mt-3 mb-2">{{ $sectionTitle }}</h6>
                        <div class="row">
                            @foreach($sectionItems as $key => $label)
                                <div class="col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{$key}}" id="new_{{$key}}">
                                        <label class="form-check-label" for="new_{{$key}}">{{$label}}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-dark">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
