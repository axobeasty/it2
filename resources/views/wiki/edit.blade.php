<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>Правка: {{ $page->title }} — {{ $settings?->title ?? 'Система' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    @include('wiki.partials.styles')
</head>
<body class="wiki-page">
<div class="container-fluid p-0 wiki-shell">
    <div class="row g-0">
        <div class="col-12 p-0">
            @include('layout.nav')
        </div>
        <div class="col-12 p-3">
            <div class="container-fluid px-0 px-sm-3" style="max-width: 960px;">
                <div class="wiki-panel p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="wiki-header-title">Правка статьи</h1>
                        <a href="{{ route('wiki.show', $page->slug) }}" class="btn btn-outline-secondary btn-sm">Просмотр</a>
                    </div>

                    <form action="{{ route('wiki.update', $page->slug) }}" method="post" class="wiki-form">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label">Заголовок</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required maxlength="255">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL-имя (slug)</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page->slug) }}" maxlength="191" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Родительский раздел</label>
                            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">— Корень —</option>
                                @foreach($parents as $p)
                                    <option value="{{ $p->id }}" @selected((string) old('parent_id', $page->parent_id) === (string) $p->id)>{{ $p->title }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Порядок сортировки</label>
                            <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $page->sort_order) }}" min="0">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Текст (Markdown)</label>
                            <textarea name="body" class="form-control font-monospace @error('body') is-invalid @enderror" rows="18">{{ old('body', $page->body) }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
